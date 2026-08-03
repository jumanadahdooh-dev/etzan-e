<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\DoctorProfile;
use App\Models\Message;
use App\Models\PatientProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorMessagesPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_messages_page_lists_conversation_from_an_approved_patient(): void
    {
        $doctorProfile = DoctorProfile::factory()->create();
        $patientProfile = PatientProfile::factory()->create([
            'doctor_profile_id' => $doctorProfile->id,
            'doctor_request_status' => 'approved',
        ]);

        $conversation = Conversation::create([
            'user_id' => $patientProfile->user_id,
            'subject' => 'رسائل الطبيب',
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $patientProfile->user_id,
            'sender_type' => 'patient',
            'body' => 'دكتور هل وجبة الغداء مناسبة؟',
            'is_read' => false,
        ]);

        $response = $this->actingAs($doctorProfile->user)->get(route('doctor.messages'));

        $response->assertOk();
        $response->assertSee($patientProfile->user->name);
        $response->assertSee('دكتور هل وجبة الغداء مناسبة؟');
    }

    public function test_doctor_can_reply_to_a_patient_conversation(): void
    {
        $doctorProfile = DoctorProfile::factory()->create();
        $patientProfile = PatientProfile::factory()->create([
            'doctor_profile_id' => $doctorProfile->id,
            'doctor_request_status' => 'approved',
        ]);

        $conversation = Conversation::create([
            'user_id' => $patientProfile->user_id,
            'subject' => 'رسائل الطبيب',
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        $response = $this->actingAs($doctorProfile->user)
            ->post(route('doctor.messages.send', $conversation), [
                'message' => 'الوجبة جيدة، قللي الخبز المرة الجاية.',
            ]);

        $response->assertRedirect(route('doctor.messages.show', $conversation));

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $doctorProfile->user_id,
            'sender_type' => 'doctor',
            'body' => 'الوجبة جيدة، قللي الخبز المرة الجاية.',
        ]);
    }

    public function test_doctor_cannot_reply_to_a_conversation_that_is_not_their_patients(): void
    {
        $doctorProfile = DoctorProfile::factory()->create();
        $otherDoctorProfile = DoctorProfile::factory()->create();
        $patientProfile = PatientProfile::factory()->create([
            'doctor_profile_id' => $otherDoctorProfile->id,
            'doctor_request_status' => 'approved',
        ]);

        $conversation = Conversation::create([
            'user_id' => $patientProfile->user_id,
            'subject' => 'رسائل الطبيب',
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        $response = $this->actingAs($doctorProfile->user)
            ->post(route('doctor.messages.send', $conversation), [
                'message' => 'محاولة غير مصرح فيها',
            ]);

        $response->assertForbidden();
    }
}
