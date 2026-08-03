<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\PatientProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * unreadMessagesCount() كانت دايماً بترجع صفر لأنها بتتحقق من عمود
 * receiver_id/user_id مش موجودين أصلاً بجدول messages. التصليح: نعتمد
 * على sender_type='doctor' + is_read=false ضمن محادثة هالمريض.
 */
class PatientUnreadMessagesCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_shows_real_unread_doctor_message_count(): void
    {
        $patientProfile = PatientProfile::factory()->create();

        $conversation = Conversation::create([
            'user_id' => $patientProfile->user_id,
            'subject' => 'رسائل الطبيب',
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => null,
            'sender_type' => 'doctor',
            'body' => 'كيف حالك اليوم؟',
            'is_read' => false,
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $patientProfile->user_id,
            'sender_type' => 'patient',
            'body' => 'رسالة مني أنا، ما لازم تنعد',
            'is_read' => false,
        ]);

        $response = $this->actingAs($patientProfile->user)->get(route('patient.home'));

        $response->assertOk();
        $response->assertViewHas('unreadMessages', 1);
    }

    public function test_unread_count_ignores_already_read_doctor_messages(): void
    {
        $patientProfile = PatientProfile::factory()->create();

        $conversation = Conversation::create([
            'user_id' => $patientProfile->user_id,
            'subject' => 'رسائل الطبيب',
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'doctor',
            'body' => 'رسالة قديمة مقروءة',
            'is_read' => true,
        ]);

        $response = $this->actingAs($patientProfile->user)->get(route('patient.home'));

        $response->assertOk();
        $response->assertViewHas('unreadMessages', 0);
    }
}
