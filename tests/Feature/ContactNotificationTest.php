<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * يتأكد إنه فورم "تواصل معنا" بيسجل إشعار حقيقي شخصي لكل أدمن حالي عبر
 * app_notifications (بعد توحيد نظام الإشعارات) — بدل الجدول المشترك القديم
 * admin_notifications يلي عاد ما حدا بيكتب فيه.
 */
class ContactNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_contact_message_notifies_every_current_admin_personally(): void
    {
        $admin = User::factory()->admin()->create();
        $secondAdmin = User::factory()->admin()->create();

        $response = $this->post(route('contact.messages.store'), [
            'guest_name' => 'زائر تجريبي',
            'guest_email' => 'guest@example.com',
            'subject' => 'استفسار',
            'message' => 'عندي سؤال عن الخدمة.',
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('app_notifications', [
            'recipient_user_id' => $admin->id,
            'recipient_role' => 'admin',
            'type' => 'message',
        ]);

        $this->assertDatabaseHas('app_notifications', [
            'recipient_user_id' => $secondAdmin->id,
            'recipient_role' => 'admin',
            'type' => 'message',
        ]);

        $this->assertDatabaseMissing('app_notifications', [
            'recipient_user_id' => null,
        ]);

        $this->assertSame(0, DB::table('admin_notifications')->count());
    }
}
