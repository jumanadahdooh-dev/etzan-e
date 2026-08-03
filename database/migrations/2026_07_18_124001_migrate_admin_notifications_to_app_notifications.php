<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * توحيد نظام الإشعارات: admin_notifications كان نظام مشترك (صف واحد
     * لكل الأدمنز، وحالة قراءة مشتركة — لو أدمن قرأه بيختفي عند الباقيين).
     * هون بننسخ كل صف قديم كصف مستقل شخصي لكل أدمن حالي بجدول
     * app_notifications (recipient_role='admin')، حتى ولا إشعار قديم يضيع.
     *
     * ما بنحذف جدول admin_notifications نفسه — بيضل موجود بدون استخدام،
     * نفس القرار السابق بالمشروع بعدم حذف جداول إشعارات قديمة.
     */
    public function up(): void
    {
        if (!Schema::hasTable('admin_notifications') || !Schema::hasTable('app_notifications')) {
            return;
        }

        $adminIds = DB::table('users')->where('role', 'admin')->pluck('id');

        if ($adminIds->isEmpty()) {
            return;
        }

        $oldNotifications = DB::table('admin_notifications')->get();

        foreach ($oldNotifications as $old) {
            $rows = $adminIds->map(fn ($adminId) => [
                'recipient_user_id' => $adminId,
                'recipient_role' => 'admin',
                'actor_user_id' => null,
                'type' => $old->type ?? 'system',
                'title' => $old->title ?? 'إشعار',
                'body' => $old->body ?? null,
                'url' => $old->url ?? null,
                'is_read' => !is_null($old->read_at ?? null),
                'read_at' => $old->read_at ?? null,
                'created_at' => $old->created_at ?? now(),
                'updated_at' => $old->updated_at ?? now(),
            ])->all();

            DB::table('app_notifications')->insert($rows);
        }
    }

    /**
     * لا رجوع — هاي نسخ إضافية فقط، حذفها رجوعاً مش ضروري ومحفوف بمخاطر
     * (ممكن تتخلط مع إشعارات حقيقية جديدة انكتبت بعد الترحيل).
     */
    public function down(): void
    {
        //
    }
};
