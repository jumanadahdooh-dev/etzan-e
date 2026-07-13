<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\SupportReplyMail;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class AdminMessagesController extends Controller
{
    public function index()
    {
        $conversations = Conversation::query()
            ->with(['user', 'latestMessage'])
            ->latest('last_message_at')
            ->latest()
            ->get();

        $conversation = $conversations->first();

        if ($conversation) {
            $conversation->load(['user', 'messages.sender', 'latestMessage']);

            /*
             | بما إن صفحة الرسائل تعرض أول محادثة مباشرة،
             | نعتبرها مقروءة بمجرد فتح صفحة الرسائل.
            */
            $this->markConversationAsReadForAdmin($conversation);
        }

        $messages = $conversation ? $conversation->messages : collect();

        return view('admin.messages', compact(
            'conversations',
            'conversation',
            'messages'
        ));
    }

    public function show(Conversation $conversation)
    {
        $conversation->load(['user', 'messages.sender', 'latestMessage']);

        /*
         | عند فتح محادثة معينة:
         | 1. نصفر عداد الرسائل غير المقروءة.
         | 2. نخلي إشعارات هذه المحادثة مقروءة.
        */
        $this->markConversationAsReadForAdmin($conversation);

        $conversations = Conversation::query()
            ->with(['user', 'latestMessage'])
            ->latest('last_message_at')
            ->latest()
            ->get();

        $messages = $conversation->messages;

        return view('admin.messages', compact(
            'conversations',
            'conversation',
            'messages'
        ));
    }

    public function send(Request $request, Conversation $conversation)
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'min:1', 'max:3000'],
        ]);

        $messageText = trim($validated['message']);

        DB::transaction(function () use ($conversation, $messageText) {
            $messageData = $this->filterColumns('messages', [
                'conversation_id' => $conversation->id,

                'user_id' => auth()->id(),
                'sender_id' => auth()->id(),
                'from_user_id' => auth()->id(),

                'sender_type' => 'admin',
                'sender_role' => 'admin',
                'direction' => 'outgoing',
                'type' => 'text',

                'message' => $messageText,
                'body' => $messageText,
                'content' => $messageText,
                'text' => $messageText,

                'metadata' => json_encode([
                    'source' => 'admin_reply',
                    'admin_id' => auth()->id(),
                ], JSON_UNESCAPED_UNICODE),

                'is_read' => false,
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('messages')->insert($messageData);

            $conversationUpdate = $this->filterColumns('conversations', [
                'status' => 'open',
                'last_message_at' => now(),
                'unread_by_admin' => 0,
                'updated_at' => now(),
            ]);

            if (!empty($conversationUpdate)) {
                DB::table('conversations')
                    ->where('id', $conversation->id)
                    ->update($conversationUpdate);
            }
        });

        /*
         | إذا المحادثة من زائر:
         | الرد يروح على الإيميل.
         |
         | إذا المحادثة لمستخدم مسجل:
         | الرد يبقى داخل نظام الرسائل فقط.
        */
        $this->sendReplyToGuestEmailIfNeeded($conversation, $messageText);

        return back()->with('success', 'تم إرسال الرد بنجاح.');
    }

    public function poll(Conversation $conversation)
    {
        $conversation->load(['messages.sender']);

        return response()->json([
            'success' => true,
            'messages_count' => $conversation->messages->count(),
        ]);
    }

    public function updateStatus(Request $request, Conversation $conversation)
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:open,closed'],
        ]);

        $conversation->update([
            'status' => $validated['status'],
        ]);

        return back()->with('success', 'تم تحديث حالة المحادثة.');
    }

    private function markConversationAsReadForAdmin(Conversation $conversation): void
    {
        try {
            /*
             | 1. تصفير عداد الرسائل في جدول conversations
            */
            if (
                Schema::hasTable('conversations') &&
                Schema::hasColumn('conversations', 'unread_by_admin')
            ) {
                $conversation->update([
                    'unread_by_admin' => 0,
                ]);
            }

            /*
             | 2. تعليم إشعارات هذه المحادثة كمقروءة
            */
            $this->markMessageNotificationsAsRead($conversation);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function markMessageNotificationsAsRead(Conversation $conversation): void
    {
        try {
            $messageIds = $conversation->messages()
                ->pluck('id')
                ->toArray();

            if ($conversation->latestMessage?->id) {
                $messageIds[] = $conversation->latestMessage->id;
            }

            $messageIds = array_values(array_filter(array_unique($messageIds)));

            $messageUrl = route('admin.messages.show', $conversation->id);

            /*
             |--------------------------------------------------------------------------
             | admin_notifications
             |--------------------------------------------------------------------------
            */
            if (Schema::hasTable('admin_notifications')) {
                $updateData = $this->notificationReadColumns('admin_notifications');

                if (!empty($updateData)) {
                    $query = DB::table('admin_notifications');

                    $hasCondition = false;

                    $query->where(function ($q) use (
                        $conversation,
                        $messageIds,
                        $messageUrl,
                        &$hasCondition
                    ) {
                        if (Schema::hasColumn('admin_notifications', 'conversation_id')) {
                            $q->orWhere('conversation_id', $conversation->id);
                            $hasCondition = true;
                        }

                        if (
                            Schema::hasColumn('admin_notifications', 'message_id') &&
                            !empty($messageIds)
                        ) {
                            $q->orWhereIn('message_id', $messageIds);
                            $hasCondition = true;
                        }

                        if (Schema::hasColumn('admin_notifications', 'url')) {
                            $q->orWhere('url', $messageUrl);
                            $hasCondition = true;
                        }

                        if (Schema::hasColumn('admin_notifications', 'link')) {
                            $q->orWhere('link', $messageUrl);
                            $hasCondition = true;
                        }

                        if (Schema::hasColumn('admin_notifications', 'route')) {
                            $q->orWhere('route', $messageUrl);
                            $hasCondition = true;
                        }

                        if (Schema::hasColumn('admin_notifications', 'action_url')) {
                            $q->orWhere('action_url', $messageUrl);
                            $hasCondition = true;
                        }
                    });

                    if ($hasCondition) {
                        $query->update($updateData);
                    }
                }
            }

            /*
             |--------------------------------------------------------------------------
             | app_notifications
             |--------------------------------------------------------------------------
            */
            if (Schema::hasTable('app_notifications')) {
                $updateData = $this->notificationReadColumns('app_notifications');

                if (!empty($updateData)) {
                    $query = DB::table('app_notifications');

                    if (Schema::hasColumn('app_notifications', 'user_id')) {
                        $query->where('user_id', auth()->id());
                    }

                    $hasCondition = false;

                    $query->where(function ($q) use (
                        $conversation,
                        $messageIds,
                        $messageUrl,
                        &$hasCondition
                    ) {
                        if (Schema::hasColumn('app_notifications', 'conversation_id')) {
                            $q->orWhere('conversation_id', $conversation->id);
                            $hasCondition = true;
                        }

                        if (
                            Schema::hasColumn('app_notifications', 'message_id') &&
                            !empty($messageIds)
                        ) {
                            $q->orWhereIn('message_id', $messageIds);
                            $hasCondition = true;
                        }

                        if (Schema::hasColumn('app_notifications', 'url')) {
                            $q->orWhere('url', $messageUrl);
                            $hasCondition = true;
                        }

                        if (Schema::hasColumn('app_notifications', 'link')) {
                            $q->orWhere('link', $messageUrl);
                            $hasCondition = true;
                        }

                        if (Schema::hasColumn('app_notifications', 'route')) {
                            $q->orWhere('route', $messageUrl);
                            $hasCondition = true;
                        }

                        if (Schema::hasColumn('app_notifications', 'action_url')) {
                            $q->orWhere('action_url', $messageUrl);
                            $hasCondition = true;
                        }
                    });

                    if ($hasCondition) {
                        $query->update($updateData);
                    }
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function notificationReadColumns(string $table): array
    {
        $data = [];

        if (!Schema::hasTable($table)) {
            return $data;
        }

        if (Schema::hasColumn($table, 'is_read')) {
            $data['is_read'] = 1;
        }

        if (Schema::hasColumn($table, 'read')) {
            $data['read'] = 1;
        }

        if (Schema::hasColumn($table, 'seen')) {
            $data['seen'] = 1;
        }

        if (Schema::hasColumn($table, 'read_at')) {
            $data['read_at'] = now();
        }

        if (Schema::hasColumn($table, 'seen_at')) {
            $data['seen_at'] = now();
        }

        if (Schema::hasColumn($table, 'updated_at')) {
            $data['updated_at'] = now();
        }

        return $data;
    }

    private function sendReplyToGuestEmailIfNeeded(Conversation $conversation, string $replyText): void
    {
        $conversation->refresh();

        /*
         | إذا المحادثة مربوطة بمستخدم، ما نرسل إيميل هنا.
         | هذا خاص بالزائر فقط.
        */
        if (!empty($conversation->user_id)) {
            return;
        }

        if (empty($conversation->guest_email)) {
            return;
        }

        $guestName = $conversation->guest_name ?: 'زائر';

        try {
            Mail::to($conversation->guest_email)->send(
                new SupportReplyMail($guestName, $replyText, $conversation)
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function filterColumns(string $table, array $data): array
    {
        if (!Schema::hasTable($table)) {
            return [];
        }

        $columns = Schema::getColumnListing($table);

        return array_intersect_key($data, array_flip($columns));
    }
}
