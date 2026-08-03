<?php

namespace Tests\Feature;

use App\Models\DoctorProfile;
use App\Models\PatientProfile;
use App\Models\PatientTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * قبل هالتصليح: الميزة كانت جاهزة بالكامل بقاعدة البيانات وبواجهة
 * المريض ("رحلتي" بتعرض مهام الطبيب بشكل مختلف) بس ما كان في أي
 * route/controller عند الطبيب ينشئ مهمة أصلاً.
 */
class DoctorAssignTaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_doctor_can_assign_a_task_to_their_approved_patient(): void
    {
        $doctorProfile = DoctorProfile::factory()->create();
        $patientProfile = PatientProfile::factory()->create([
            'doctor_profile_id' => $doctorProfile->id,
            'doctor_request_status' => 'approved',
        ]);

        $response = $this->actingAs($doctorProfile->user)
            ->post(route('doctor.patients.tasks.assign', $patientProfile->id), [
                'title' => 'امشي 30 دقيقة',
                'description' => 'مشي خفيف بعد العشا',
                'task_date' => now()->toDateString(),
                'task_time' => '18:00',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('patient_tasks', [
            'patient_user_id' => $patientProfile->user_id,
            'doctor_user_id' => $doctorProfile->user_id,
            'source' => 'doctor',
            'title' => 'امشي 30 دقيقة',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('app_notifications', [
            'recipient_user_id' => $patientProfile->user_id,
            'type' => 'task_reminder',
        ]);
    }

    public function test_the_new_task_appears_on_the_patients_journey_page(): void
    {
        $doctorProfile = DoctorProfile::factory()->create();
        $patientProfile = PatientProfile::factory()->create([
            'doctor_profile_id' => $doctorProfile->id,
            'doctor_request_status' => 'approved',
        ]);

        $this->actingAs($doctorProfile->user)->post(route('doctor.patients.tasks.assign', $patientProfile->id), [
            'title' => 'اشرب 8 أكواب ماء',
            'task_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($patientProfile->user)->get(route('patient.journey'));

        $response->assertOk();
        $response->assertSee('اشرب 8 أكواب ماء');
    }

    public function test_doctor_cannot_assign_a_task_to_a_patient_who_is_not_theirs(): void
    {
        $doctorProfile = DoctorProfile::factory()->create();
        $otherDoctorProfile = DoctorProfile::factory()->create();
        $patientProfile = PatientProfile::factory()->create([
            'doctor_profile_id' => $otherDoctorProfile->id,
            'doctor_request_status' => 'approved',
        ]);

        $response = $this->actingAs($doctorProfile->user)
            ->post(route('doctor.patients.tasks.assign', $patientProfile->id), [
                'title' => 'محاولة غير مصرح فيها',
                'task_date' => now()->toDateString(),
            ]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('patient_tasks', ['title' => 'محاولة غير مصرح فيها']);
    }
}
