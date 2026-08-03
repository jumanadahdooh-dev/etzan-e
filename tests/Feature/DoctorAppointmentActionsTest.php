<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\DoctorProfile;
use App\Models\PatientAppointment;
use App\Models\PatientProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * قبل هالتصليح: الدكتور بس كان يقدر "يؤكد" موعد، وبدون ما يوصل إشعار
 * للمريض. الرفض واقتراح وقت بديل ما كانا موجودين إطلاقاً — رغم إنه
 * واجهة المريض كانت جاهزة بالكامل لقبول/رفض اقتراح موعد بديل.
 */
class DoctorAppointmentActionsTest extends TestCase
{
    use RefreshDatabase;

    private function makeAppointment(DoctorProfile $doctorProfile, PatientProfile $patientProfile, array $overrides = []): PatientAppointment
    {
        return PatientAppointment::factory()->create(array_merge([
            'user_id' => $patientProfile->user_id,
            'doctor_profile_id' => $doctorProfile->id,
            'status' => 'pending',
            'appointment_date' => now()->addDay()->toDateString(),
        ], $overrides));
    }

    public function test_confirming_an_appointment_notifies_the_patient(): void
    {
        $doctorProfile = DoctorProfile::factory()->create();
        $patientProfile = PatientProfile::factory()->create();
        $appointment = $this->makeAppointment($doctorProfile, $patientProfile);

        $response = $this->actingAs($doctorProfile->user)
            ->post(route('doctor.appointments.confirm', $appointment));

        $response->assertRedirect();

        $this->assertDatabaseHas('patient_appointments', [
            'id' => $appointment->id,
            'status' => 'confirmed',
        ]);

        $this->assertDatabaseHas('app_notifications', [
            'recipient_user_id' => $patientProfile->user_id,
            'type' => 'appointment_confirmed',
        ]);
    }

    public function test_doctor_can_reject_a_pending_appointment_and_patient_is_notified(): void
    {
        $doctorProfile = DoctorProfile::factory()->create();
        $patientProfile = PatientProfile::factory()->create();
        $appointment = $this->makeAppointment($doctorProfile, $patientProfile);

        $response = $this->actingAs($doctorProfile->user)
            ->post(route('doctor.appointments.reject', $appointment), [
                'doctor_response_message' => 'عندي ظرف طارئ هالموعد.',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('patient_appointments', [
            'id' => $appointment->id,
            'status' => 'rejected',
            'doctor_response_message' => 'عندي ظرف طارئ هالموعد.',
        ]);

        $this->assertDatabaseHas('app_notifications', [
            'recipient_user_id' => $patientProfile->user_id,
            'type' => 'appointment_rejected',
        ]);
    }

    public function test_doctor_can_suggest_an_alternate_time_and_patient_can_then_accept_it(): void
    {
        $doctorProfile = DoctorProfile::factory()->create();
        $patientProfile = PatientProfile::factory()->create();
        $appointment = $this->makeAppointment($doctorProfile, $patientProfile);

        $suggestedDate = now()->addDays(3)->toDateString();

        $response = $this->actingAs($doctorProfile->user)
            ->post(route('doctor.appointments.suggest-time', $appointment), [
                'suggested_date' => $suggestedDate,
                'suggested_time' => '14:30',
            ]);

        $response->assertRedirect();

        $appointment->refresh();

        $this->assertSame('reschedule_requested', $appointment->status);
        $this->assertSame($suggestedDate, $appointment->suggested_date->toDateString());
        $this->assertSame('14:30', $appointment->suggested_time);

        $this->assertDatabaseHas('app_notifications', [
            'recipient_user_id' => $patientProfile->user_id,
            'type' => 'appointment_reschedule_requested',
        ]);

        // والمريض هلق فعلياً يقدر يقبل الاقتراح (كانت هاي الوظيفة موجودة
        // وجاهزة بس ما في طريقة توصلها حالة reschedule_requested قبل هيك).
        $acceptResponse = $this->actingAs($patientProfile->user)
            ->post(route('patient.appointments.suggestion.accept', $appointment->id));

        $acceptResponse->assertRedirect();

        $appointment->refresh();

        $this->assertSame('confirmed', $appointment->status);
        $this->assertSame($suggestedDate, $appointment->appointment_date->toDateString());
    }

    public function test_doctor_cannot_act_on_another_doctors_appointment(): void
    {
        $doctorProfile = DoctorProfile::factory()->create();
        $otherDoctorProfile = DoctorProfile::factory()->create();
        $patientProfile = PatientProfile::factory()->create();
        $appointment = $this->makeAppointment($otherDoctorProfile, $patientProfile);

        $this->actingAs($doctorProfile->user)
            ->post(route('doctor.appointments.confirm', $appointment))
            ->assertForbidden();

        $this->actingAs($doctorProfile->user)
            ->post(route('doctor.appointments.reject', $appointment))
            ->assertForbidden();
    }
}
