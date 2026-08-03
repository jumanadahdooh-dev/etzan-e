<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ContactController extends Controller
{
    public function create()
    {
        return view('pages.contact-chat');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'guest_name' => ['required', 'string', 'max:120'],
            'guest_email' => ['required', 'email', 'max:190'],
            'subject' => ['nullable', 'string', 'max:190'],
            'message' => ['required', 'string', 'min:3', 'max:3000'],
        ], [
            'guest_name.required' => 'اكتبي اسمك حتى نعرف كيف نخاطبك.',
            'guest_email.required' => 'اكتبي بريدك الإلكتروني حتى نقدر نرد عليك.',
            'guest_email.email' => 'صيغة البريد الإلكتروني غير صحيحة.',
            'message.required' => 'اكتبي رسالتك أولًا.',
            'message.min' => 'الرسالة قصيرة جدًا.',
        ]);

        if (!Schema::hasTable('conversations') || !Schema::hasTable('messages')) {
            return $this->contactResponse($request, false, 'جداول الرسائل غير موجودة.');
        }

        $guestName = trim($validated['guest_name']);
        $guestEmail = trim($validated['guest_email']);
        $subject = !empty($validated['subject'])
            ? trim($validated['subject'])
            : 'رسالة من صفحة اتصل بنا';

        $messageText = trim($validated['message']);

        try {
            $result = DB::transaction(function () use ($guestName, $guestEmail, $subject, $messageText) {
                $conversationId = $this->findOpenGuestConversation($guestEmail);

                if ($conversationId) {
                    DB::table('conversations')->where('id', $conversationId)->update(
                        $this->filterColumns('conversations', [
                            'last_message_at' => now(),
                            'unread_by_admin' => 1,
                            'updated_at' => now(),
                        ])
                    );
                } else {
                    $conversationData = $this->filterColumns('conversations', [
                        'user_id' => null,
                        'guest_name' => $guestName,
                        'guest_email' => $guestEmail,
                        'subject' => $subject,
                        'source' => 'contact_guest',
                        'status' => 'open',
                        'last_message_at' => now(),
                        'unread_by_admin' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $conversationId = DB::table('conversations')->insertGetId($conversationData);
                }

                $messageData = $this->filterColumns('messages', [
                    'conversation_id' => $conversationId,

                    'user_id' => null,
                    'sender_id' => null,
                    'from_user_id' => null,

                    'sender_type' => 'guest',
                    'sender_role' => 'guest',
                    'direction' => 'incoming',
                    'type' => 'text',

                    'guest_name' => $guestName,
                    'guest_email' => $guestEmail,
                    'from_name' => $guestName,
                    'from_email' => $guestEmail,

                    'message' => $messageText,
                    'body' => $messageText,
                    'content' => $messageText,
                    'text' => $messageText,

                    'metadata' => json_encode([
                        'source' => 'contact_page',
                        'contact_type' => 'guest',
                        'subject' => $subject,
                        'sender_name' => $guestName,
                        'sender_email' => $guestEmail,
                    ], JSON_UNESCAPED_UNICODE),

                    'is_read' => false,
                    'read_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $messageId = DB::table('messages')->insertGetId($messageData);

                /*
                 | إشعار للأدمن:
                 | الرسالة تنحفظ عادي، وبعدها نحاول نضيف إشعار.
                 | لو صار خطأ في الإشعار ما نخرب إرسال الرسالة.
                */
                $this->createAdminMessageNotification(
                    $conversationId,
                    $messageId,
                    $guestName,
                    $guestEmail,
                    $subject,
                    $messageText
                );

                return [
                    'conversation_id' => $conversationId,
                    'message_id' => $messageId,
                ];
            });

            return $this->contactResponse(
                $request,
                true,
                'تم إرسال رسالتك بنجاح، سيتم الرد عليك عبر البريد الإلكتروني.',
                $result
            );
        } catch (\Throwable $e) {
            report($e);

            return $this->contactResponse(
                $request,
                false,
                config('app.debug')
                    ? 'خطأ: ' . $e->getMessage()
                    : 'تعذر إرسال الرسالة الآن، جرّبي مرة أخرى.'
            );
        }
    }

    /**
     * إشعار كل الأدمنز الحاليين برسالة تواصل جديدة — نقطة كتابة واحدة
     * نظيفة عبر AppNotificationService::sendToAllAdmins() بدل ~90 سطر
     * كود دفاعي كان يجرب أعمدة كتير (admin_id/user_id/recipient_id...)
     * ولا وحدة منها موجودة فعلياً بالجدول، فكان دايماً بيوقع بمسار واحد
     * مشترك (حالة قراءة واحدة لكل الأدمنز).
     */
    private function createAdminMessageNotification(
        int $conversationId,
        int $messageId,
        string $guestName,
        string $guestEmail,
        string $subject,
        string $messageText
    ): void {
        try {
            app(\App\Services\AppNotificationService::class)->sendToAllAdmins(
                type: 'message',
                title: 'رسالة دعم جديدة',
                body: 'وصلت رسالة جديدة من ' . $guestName . ' بخصوص: ' . $subject,
                url: route('admin.messages.show', $conversationId),
                relatedId: $conversationId,
                relatedType: 'conversation',
                data: [
                    'conversation_id' => $conversationId,
                    'message_id' => $messageId,
                    'guest_name' => $guestName,
                    'guest_email' => $guestEmail,
                    'subject' => $subject,
                    'message_preview' => mb_substr($messageText, 0, 180),
                ]
            );
        } catch (\Throwable $e) {
            /*
             | لا نخلي فشل الإشعار يمنع إرسال رسالة الزائر.
            */
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

    /**
     * لو نفس الزائر (بنفس الإيميل) بعت رسالة قبل هيك وضلت المحادثة "مفتوحة"
     * (ما ردّ عليها الأدمن وأغلقها لسا)، منستخدم نفس المحادثة بدل ما نعمل
     * وحدة جديدة كل مرة — بالضبط نفس الأسلوب المستخدم بمحادثة الدعم الفني
     * للمريض المسجل دخول (ensureSupportConversation).
     */
    private function findOpenGuestConversation(string $guestEmail): ?int
    {
        if (!Schema::hasTable('conversations') || !Schema::hasColumn('conversations', 'guest_email')) {
            return null;
        }

        $query = DB::table('conversations')
            ->whereNull('user_id')
            ->where('guest_email', $guestEmail);

        if (Schema::hasColumn('conversations', 'status')) {
            $query->where('status', 'open');
        }

        $id = $query->orderByDesc('id')->value('id');

        return $id ? (int) $id : null;
    }

    private function contactResponse(Request $request, bool $success, string $message, array $extra = [])
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(array_merge([
                'success' => $success,
                'message' => $message,
            ], $extra), $success ? 200 : 422);
        }

        return back()
            ->with($success ? 'success' : 'error', $message)
            ->withInput();
    }
}
