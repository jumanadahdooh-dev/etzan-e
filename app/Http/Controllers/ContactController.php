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

    private function createAdminMessageNotification(
        int $conversationId,
        int $messageId,
        string $guestName,
        string $guestEmail,
        string $subject,
        string $messageText
    ): void {
        try {
            $title = 'رسالة دعم جديدة';
            $body = 'وصلت رسالة جديدة من ' . $guestName . ' بخصوص: ' . $subject;

            $url = route('admin.messages.show', $conversationId);

            $data = [
                'conversation_id' => $conversationId,
                'message_id' => $messageId,
                'guest_name' => $guestName,
                'guest_email' => $guestEmail,
                'subject' => $subject,
                'message_preview' => mb_substr($messageText, 0, 180),
                'url' => $url,
            ];

            /*
             |--------------------------------------------------------------------------
             | جدول admin_notifications
             |--------------------------------------------------------------------------
             | إذا جدول إشعارات الأدمن موجود، بنضيف فيه إشعار.
             | الكود مرن مع أسماء الأعمدة المختلفة عندك.
            */
            if (Schema::hasTable('admin_notifications')) {
                $adminIds = $this->getAdminIds();

                if (!empty($adminIds) && $this->tableHasAnyColumn('admin_notifications', [
                    'admin_id',
                    'user_id',
                    'recipient_id',
                    'notifiable_id',
                ])) {
                    foreach ($adminIds as $adminId) {
                        $notificationData = $this->filterColumns('admin_notifications', [
                            'admin_id' => $adminId,
                            'user_id' => $adminId,
                            'recipient_id' => $adminId,
                            'notifiable_id' => $adminId,
                            'notifiable_type' => 'App\\Models\\User',

                            'title' => $title,
                            'message' => $body,
                            'body' => $body,
                            'content' => $body,
                            'description' => $body,

                            'type' => 'message',
                            'category' => 'message',
                            'icon' => 'fa-regular fa-message',

                            'url' => $url,
                            'link' => $url,
                            'route' => $url,
                            'action_url' => $url,

                            'conversation_id' => $conversationId,
                            'message_id' => $messageId,

                            'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
                            'meta' => json_encode($data, JSON_UNESCAPED_UNICODE),
                            'payload' => json_encode($data, JSON_UNESCAPED_UNICODE),

                            'is_read' => false,
                            'read' => false,
                            'seen' => false,
                            'read_at' => null,
                            'seen_at' => null,

                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        if (!empty($notificationData)) {
                            DB::table('admin_notifications')->insert($notificationData);
                        }
                    }
                } else {
                    $notificationData = $this->filterColumns('admin_notifications', [
                        'title' => $title,
                        'message' => $body,
                        'body' => $body,
                        'content' => $body,
                        'description' => $body,

                        'type' => 'message',
                        'category' => 'message',
                        'icon' => 'fa-regular fa-message',

                        'url' => $url,
                        'link' => $url,
                        'route' => $url,
                        'action_url' => $url,

                        'conversation_id' => $conversationId,
                        'message_id' => $messageId,

                        'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
                        'meta' => json_encode($data, JSON_UNESCAPED_UNICODE),
                        'payload' => json_encode($data, JSON_UNESCAPED_UNICODE),

                        'is_read' => false,
                        'read' => false,
                        'seen' => false,
                        'read_at' => null,
                        'seen_at' => null,

                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    if (!empty($notificationData)) {
                        DB::table('admin_notifications')->insert($notificationData);
                    }
                }
            }

            /*
             |--------------------------------------------------------------------------
             | ملاحظة: كان هون كود بيحاول يكتب كمان بجدول app_notifications، بس كان
             | معطّل بالكامل — أسماء الأعمدة يلي كان يحاول يستخدمها (user_id, admin_id,
             | recipient_id, notifiable_id) ما وحدة منهم بتطابق العمود الحقيقي
             | (recipient_user_id)، فكانت النتيجة صفوف بدون مستلم محدد، وما في
             | أي شاشة أدمن أصلاً بتقرأ من app_notifications (إشعارات الأدمن كلها
             | من admin_notifications فوق). شيلناه لأنه كان عم يكتب صفوف ميتة
             | بقاعدة البيانات بدون أي فايدة مع كل رسالة تواصل.
            */
        } catch (\Throwable $e) {
            /*
             | لا نخلي فشل الإشعار يمنع إرسال رسالة الزائر.
            */
            report($e);
        }
    }

    private function getAdminIds()
    {
        if (!Schema::hasTable('users')) {
            return [];
        }

        if (Schema::hasColumn('users', 'role')) {
            return DB::table('users')
                ->where('role', 'admin')
                ->pluck('id')
                ->toArray();
        }

        return [];
    }

    private function tableHasAnyColumn(string $table, array $columns): bool
    {
        if (!Schema::hasTable($table)) {
            return false;
        }

        foreach ($columns as $column) {
            if (Schema::hasColumn($table, $column)) {
                return true;
            }
        }

        return false;
    }

    private function filterColumns(string $table, array $data): array
    {
        if (!Schema::hasTable($table)) {
            return [];
        }

        $columns = Schema::getColumnListing($table);

        return array_intersect_key($data, array_flip($columns));
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
