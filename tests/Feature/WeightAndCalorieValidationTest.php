<?php

namespace Tests\Feature;

use App\Models\DoctorProfile;
use App\Models\PatientProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeightAndCalorieValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_can_log_a_valid_weight(): void
    {
        $patient = User::factory()->patient()->create();
        PatientProfile::factory()->create(['user_id' => $patient->id]);

        $response = $this->actingAs($patient)->post(route('patient.weight.log'), [
            'weight_kg' => 82.5,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('patient_weight_logs', ['user_id' => $patient->id, 'weight_kg' => 82.5]);
    }

    public function test_patient_weight_below_minimum_is_rejected_with_clear_message(): void
    {
        $patient = User::factory()->patient()->create();
        PatientProfile::factory()->create(['user_id' => $patient->id]);

        $response = $this->actingAs($patient)->post(route('patient.weight.log'), [
            'weight_kg' => 10,
        ]);

        $response->assertSessionHasErrors('weight_kg');
        $this->assertDatabaseMissing('patient_weight_logs', ['user_id' => $patient->id]);
    }

    public function test_patient_weight_missing_is_rejected(): void
    {
        $patient = User::factory()->patient()->create();

        $response = $this->actingAs($patient)->post(route('patient.weight.log'), []);

        $response->assertSessionHasErrors('weight_kg');
    }

    public function test_doctor_calorie_goal_below_minimum_is_rejected(): void
    {
        $doctorProfile = DoctorProfile::factory()->create();
        $patientProfile = PatientProfile::factory()->create(['doctor_profile_id' => $doctorProfile->id]);

        $response = $this->actingAs($doctorProfile->user)->post(
            route('doctor.patients.calorie-goal', $patientProfile->id),
            ['calories_goal' => 500]
        );

        $response->assertSessionHasErrors('calories_goal');
    }

    public function test_doctor_calorie_goal_valid_value_is_accepted(): void
    {
        $doctorProfile = DoctorProfile::factory()->create();
        $patientProfile = PatientProfile::factory()->create(['doctor_profile_id' => $doctorProfile->id]);

        $response = $this->actingAs($doctorProfile->user)->post(
            route('doctor.patients.calorie-goal', $patientProfile->id),
            ['calories_goal' => 2200]
        );

        $response->assertSessionHasNoErrors();
    }
}
