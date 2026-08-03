<?php

namespace Tests\Feature;

use App\Models\DoctorProfile;
use App\Models\PatientMeal;
use App\Models\PatientProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorMealReviewsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_meal_reviews_page_shows_a_confirmed_meal_for_an_approved_patient(): void
    {
        $doctorProfile = DoctorProfile::factory()->create();
        $patientProfile = PatientProfile::factory()->create([
            'doctor_profile_id' => $doctorProfile->id,
            'doctor_request_status' => 'approved',
        ]);

        PatientMeal::create([
            'user_id' => $patientProfile->user_id,
            'meal_date' => now()->toDateString(),
            'meal_type' => 'lunch',
            'meal_name' => 'صدر دجاج مشوي مع أرز',
            'calories' => 620,
            'protein' => 45,
            'carbs' => 60,
            'fat' => 12,
            'confidence' => 85,
            'ai_notes' => 'وجبة متوازنة نسبياً.',
            'source' => 'ai',
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($doctorProfile->user)->get(route('doctor.meal_reviews'));

        $response->assertOk();
        $response->assertSee($patientProfile->user->name);
        $response->assertSee('صدر دجاج مشوي مع أرز');
        $response->assertSee('بانتظار مراجعتك');
    }

    public function test_doctor_can_save_a_review_note_on_a_patients_meal(): void
    {
        $doctorProfile = DoctorProfile::factory()->create();
        $patientProfile = PatientProfile::factory()->create([
            'doctor_profile_id' => $doctorProfile->id,
            'doctor_request_status' => 'approved',
        ]);

        $meal = PatientMeal::create([
            'user_id' => $patientProfile->user_id,
            'meal_date' => now()->toDateString(),
            'meal_type' => 'dinner',
            'meal_name' => 'سلطة عدس',
            'calories' => 350,
            'source' => 'ai',
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($doctorProfile->user)
            ->post(route('doctor.meal_reviews.review', $meal), [
                'doctor_note' => 'ممتازة، كمّلي على هيك.',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('patient_meals', [
            'id' => $meal->id,
            'doctor_note' => 'ممتازة، كمّلي على هيك.',
        ]);

        $this->assertNotNull($meal->fresh()->reviewed_at);
    }

    public function test_doctor_cannot_review_a_meal_that_is_not_their_patients(): void
    {
        $doctorProfile = DoctorProfile::factory()->create();
        $otherDoctorProfile = DoctorProfile::factory()->create();
        $patientProfile = PatientProfile::factory()->create([
            'doctor_profile_id' => $otherDoctorProfile->id,
            'doctor_request_status' => 'approved',
        ]);

        $meal = PatientMeal::create([
            'user_id' => $patientProfile->user_id,
            'meal_date' => now()->toDateString(),
            'meal_type' => 'breakfast',
            'meal_name' => 'شوفان',
            'calories' => 300,
            'source' => 'ai',
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($doctorProfile->user)
            ->post(route('doctor.meal_reviews.review', $meal), [
                'doctor_note' => 'محاولة غير مصرح فيها',
            ]);

        $response->assertForbidden();
    }
}
