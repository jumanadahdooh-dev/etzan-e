<?php

namespace Tests\Feature;

use App\Models\PatientDailyCalorieGoal;
use App\Models\PatientProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * caloriesSummary['goal_note'] (يجيب PatientDailyCalorieGoal.doctor_note)
 * كان محسوباً بالكونترولر بس ما ظهر إطلاقاً بـ patient/calories.blade.php.
 */
class PatientSeesDoctorGoalNoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_sees_the_doctors_note_on_their_calorie_goal(): void
    {
        $patientProfile = PatientProfile::factory()->create();

        PatientDailyCalorieGoal::create([
            'user_id' => $patientProfile->user_id,
            'patient_profile_id' => $patientProfile->id,
            'goal_date' => now()->toDateString(),
            'calories_goal' => 1800,
            'status' => 'approved',
            'doctor_note' => 'قللي الملح هالأسبوع.',
            'approved_at' => now(),
        ]);

        $response = $this->actingAs($patientProfile->user)->get(route('patient.calories'));

        $response->assertOk();
        $response->assertSee('قللي الملح هالأسبوع.');
    }
}
