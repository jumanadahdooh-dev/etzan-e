<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * يتأكد إنه فورم "تواصل معنا" لسا بيسجل الإشعار الصحيح للأدمن
 * (admin_notifications) بعد ما شلنا الكتابة المعطّلة على app_notifications.
 */
class ContactNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_contact_message_notifies_admin_and_creates_no_orphaned_app_notification(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->post(route('contact.messages.store'), [
            'guest_name' => 'زائر تجريبي',
            'guest_email' => 'guest@example.com',
            'subject' => 'استفسار',
            'message' => 'عندي سؤال عن الخدمة.',
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('admin_notifications', [
            'type' => 'message',
        ]);

        $this->assertDatabaseMissing('app_notifications', [
            'recipient_user_id' => null,
        ]);

        $this->assertSame(0, DB::table('app_notifications')->count());
    }
}
