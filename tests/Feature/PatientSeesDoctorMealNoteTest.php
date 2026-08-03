<?php

namespace Tests\Feature;

use App\Models\PatientMeal;
use App\Models\PatientProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * doctor_note على PatientMeal كان يُكتب بقاعدة البيانات (صفحة مراجعة
 * الوجبات AI عند الدكتور) بس ما كان يظهر للمريض بأي مكان — يضيع.
 */
class PatientSeesDoctorMealNoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_sees_the_doctors_note_on_todays_meal(): void
    {
        $patientProfile = PatientProfile::factory()->create();

        PatientMeal::create([
            'user_id' => $patientProfile->user_id,
            'meal_date' => now()->toDateString(),
            'meal_type' => 'lunch',
            'meal_name' => 'صدر دجاج مع أرز',
            'calories' => 600,
            'source' => 'ai',
            'status' => 'confirmed',
            'doctor_note' => 'ممتازة، بس قلّلي الأرز شوي.',
            'reviewed_at' => now(),
        ]);

        $response = $this->actingAs($patientProfile->user)->get(route('patient.calories'));

        $response->assertOk();
        $response->assertSee('ممتازة، بس قلّلي الأرز شوي.');
    }
}
