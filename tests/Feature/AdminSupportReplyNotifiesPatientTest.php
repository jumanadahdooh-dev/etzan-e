<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\PatientProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * قبل هالتصليح: رد الأدمن على محادثة دعم لمستخدم مسجّل كان بس بينكتب
 * بجدول messages بدون أي إشعار — المريض ما كان يعرف إنه في رد إلا
 * لما يرجع يفتح صفحة الدعم يدوياً.
 */
class AdminSupportReplyNotifiesPatientTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_reply_notifies_a_registered_patient(): void
    {
        $admin = User::factory()->admin()->create();
        $patientProfile = PatientProfile::factory()->create();

        $conversation = Conversation::create([
            'user_id' => $patientProfile->user_id,
            'subject' => 'استفسار دعم',
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.messages.send', $conversation), [
                'message' => 'تم حل المشكلة، جربي هلق.',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('app_notifications', [
            'recipient_user_id' => $patientProfile->user_id,
            'recipient_role' => 'patient',
            'type' => 'support_reply',
        ]);
    }

    public function test_admin_reply_to_a_guest_conversation_does_not_create_a_broken_notification(): void
    {
        $admin = User::factory()->admin()->create();

        $conversation = Conversation::create([
            'user_id' => null,
            'guest_name' => 'زائر',
            'guest_email' => 'guest@example.com',
            'subject' => 'استفسار',
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.messages.send', $conversation), [
                'message' => 'شكراً لتواصلك معنا.',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseMissing('app_notifications', [
            'type' => 'support_reply',
        ]);
    }
}
