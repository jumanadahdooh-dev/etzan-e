<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\DoctorProfile;
use App\Models\Message;
use App\Models\PatientMeal;
use App\Models\PatientProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class DoctorDashboardAttentionCountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_real_unread_message_and_meal_review_counts(): void
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
            'body' => 'سؤال للدكتور',
            'is_read' => false,
        ]);

        PatientMeal::create([
            'user_id' => $patientProfile->user_id,
            'meal_date' => now()->toDateString(),
            'meal_type' => 'lunch',
            'meal_name' => 'وجبة تحتاج مراجعة',
            'calories' => 500,
            'source' => 'ai',
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($doctorProfile->user)->get(route('doctor.dashboard'));

        $response->assertOk();
        $response->assertSee('رسائل غير مقروءة');
        $response->assertSee('وجبات تحتاج مراجعة');
        $response->assertSee('>1<', false);
    }

    public function test_dead_doctor_requests_and_patient_details_routes_were_removed(): void
    {
        $this->assertFalse(Route::has('doctor.requests'));
        $this->assertFalse(Route::has('doctor.patient_details'));
        $this->assertTrue(Route::has('doctor.patient-requests.index'));
    }
}
