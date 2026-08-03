<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\User;
use App\Services\AppNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * تغطية توحيد نظام الإشعارات: admin_notifications (مشترك، حالة قراءة
 * واحدة لكل الأدمنز) صار app_notifications (شخصي لكل أدمن)، بدون ما
 * يضيع أي إشعار وبدون ما أدمن يأثر على قراءة أدمن تاني.
 */
class AdminNotificationsUnifiedTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_to_all_admins_creates_one_personal_row_per_current_admin(): void
    {
        $adminA = User::factory()->admin()->create();
        $adminB = User::factory()->admin()->create();
        $doctor = User::factory()->doctor()->create();

        app(AppNotificationService::class)->sendToAllAdmins(
            type: 'doctor_application',
            title: 'طلب طبيب جديد',
            body: 'تم إرسال طلب انضمام جديد.'
        );

        $this->assertDatabaseHas('app_notifications', [
            'recipient_user_id' => $adminA->id,
            'recipient_role' => 'admin',
            'type' => 'doctor_application',
        ]);

        $this->assertDatabaseHas('app_notifications', [
            'recipient_user_id' => $adminB->id,
            'recipient_role' => 'admin',
            'type' => 'doctor_application',
        ]);

        $this->assertDatabaseMissing('app_notifications', [
            'recipient_user_id' => $doctor->id,
            'type' => 'doctor_application',
        ]);
    }

    public function test_one_admin_marking_a_notification_read_does_not_affect_the_other_admins_copy(): void
    {
        $adminA = User::factory()->admin()->create();
        $adminB = User::factory()->admin()->create();

        app(AppNotificationService::class)->sendToAllAdmins(
            type: 'message',
            title: 'رسالة دعم جديدة',
            body: 'رسالة تجريبية.'
        );

        $notificationForA = AppNotification::where('recipient_user_id', $adminA->id)->firstOrFail();

        $this->actingAs($adminA)
            ->post(route('admin.notifications.mark-read', $notificationForA))
            ->assertRedirect();

        $this->assertDatabaseHas('app_notifications', [
            'id' => $notificationForA->id,
            'is_read' => true,
        ]);

        // نفس الحدث، بس نسخة الأدمن التاني — لازم تضل غير مقروءة (هون كانت
        // المشكلة الحقيقية بالنظام القديم: قراءة أدمن واحد كانت تخفي الإشعار
        // عن الجميع).
        $notificationForB = AppNotification::where('recipient_user_id', $adminB->id)->firstOrFail();
        $this->assertFalse((bool) $notificationForB->fresh()->is_read);
    }

    public function test_admin_cannot_mark_another_admins_notification_as_read(): void
    {
        $adminA = User::factory()->admin()->create();
        $adminB = User::factory()->admin()->create();

        app(AppNotificationService::class)->sendToAllAdmins(
            type: 'message',
            title: 'رسالة دعم جديدة',
        );

        $notificationForB = AppNotification::where('recipient_user_id', $adminB->id)->firstOrFail();

        $this->actingAs($adminA)
            ->post(route('admin.notifications.mark-read', $notificationForB))
            ->assertForbidden();
    }

    public function test_notification_badge_shows_real_count_on_a_non_dashboard_admin_page(): void
    {
        $admin = User::factory()->admin()->create();

        app(AppNotificationService::class)->sendToAllAdmins(
            type: 'doctor_application',
            title: 'طلب طبيب جديد',
        );

        // صفحة المستخدمين، مش الداشبورد — هون كان العداد دايماً بيطلع صفر
        // قبل التصليح لأنه بس الداشبورد كان يمرر $stats['admin_notifications_unread'].
        $response = $this->actingAs($admin)->get(route('admin.admin-users'));

        $response->assertOk();
        $response->assertSee('admin-notif-badge');
        $response->assertSee('>1<', false);
    }

    public function test_admin_notifications_index_page_shows_real_personal_data(): void
    {
        $admin = User::factory()->admin()->create();

        app(AppNotificationService::class)->sendToAllAdmins(
            type: 'doctor_application',
            title: 'طلب طبيب جديد تجريبي',
            body: 'تفاصيل الطلب.'
        );

        $response = $this->actingAs($admin)->get(route('admin.notifications.index'));

        $response->assertOk();
        $response->assertSee('طلب طبيب جديد تجريبي');
    }
}
