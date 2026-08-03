<?php

namespace Tests\Feature;

use App\Models\DoctorProfile;
use App\Models\PatientProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * setCalorieGoal()/logWeight() كانوا يكتبوا البيانات صح بس بدون ما
 * يوصل أي إشعار للمريض — كان لازم يرجع يفتح صفحة السعرات يدوياً ليعرف.
 */
class DoctorNotifiesPatientOnGoalAndWeightTest extends TestCase
{
    use RefreshDatabase;

    public function test_setting_a_calorie_goal_notifies_the_patient(): void
    {
        $doctorProfile = DoctorProfile::factory()->create();
        $patientProfile = PatientProfile::factory()->create([
            'doctor_profile_id' => $doctorProfile->id,
            'doctor_request_status' => 'approved',
        ]);

        $response = $this->actingAs($doctorProfile->user)
            ->post(route('doctor.patients.calorie-goal', $patientProfile->id), [
                'calories_goal' => 1900,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('app_notifications', [
            'recipient_user_id' => $patientProfile->user_id,
            'type' => 'calorie_goal_set',
        ]);
    }

    public function test_logging_weight_notifies_the_patient(): void
    {
        $doctorProfile = DoctorProfile::factory()->create();
        $patientProfile = PatientProfile::factory()->create([
            'doctor_profile_id' => $doctorProfile->id,
            'doctor_request_status' => 'approved',
        ]);

        $response = $this->actingAs($doctorProfile->user)
            ->post(route('doctor.patients.log-weight', $patientProfile->id), [
                'weight_kg' => 76.5,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('app_notifications', [
            'recipient_user_id' => $patientProfile->user_id,
            'type' => 'weight_logged',
        ]);
    }
}
