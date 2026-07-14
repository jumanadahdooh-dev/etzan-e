<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileAndAppointmentValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_profile_accepts_valid_data(): void
    {
        $patient = User::factory()->patient()->create();

        $response = $this->actingAs($patient)->post(route('patient.profile.complete'), [
            'height_cm' => 170,
            'weight_kg' => 70,
            'birth_date' => '1995-01-01',
            'gender' => 'male',
            'health_goal' => 'lose_weight',
            'activity_level' => 'moderate',
        ]);

        $response->assertSessionHasNoErrors();
    }

    public function test_complete_profile_rejects_weight_out_of_range(): void
    {
        $patient = User::factory()->patient()->create();

        $response = $this->actingAs($patient)->post(route('patient.profile.complete'), [
            'height_cm' => 170,
            'weight_kg' => 900,
            'birth_date' => '1995-01-01',
            'gender' => 'male',
            'health_goal' => 'lose_weight',
            'activity_level' => 'moderate',
        ]);

        $response->assertSessionHasErrors('weight_kg');
    }

    public function test_complete_profile_rejects_invalid_gender(): void
    {
        $patient = User::factory()->patient()->create();

        $response = $this->actingAs($patient)->post(route('patient.profile.complete'), [
            'height_cm' => 170,
            'weight_kg' => 70,
            'birth_date' => '1995-01-01',
            'gender' => 'other',
            'health_goal' => 'lose_weight',
            'activity_level' => 'moderate',
        ]);

        $response->assertSessionHasErrors('gender');
    }

    public function test_book_appointment_rejects_past_date(): void
    {
        $patient = User::factory()->patient()->create();

        $response = $this->actingAs($patient)->post(route('patient.appointments.book'), [
            'appointment_date' => now()->subDay()->toDateString(),
            'appointment_time' => '10:00',
            'consultation_type' => 'online',
            'reason' => 'متابعة دورية',
        ]);

        $response->assertSessionHasErrors('appointment_date');
    }

    public function test_book_appointment_rejects_invalid_consultation_type(): void
    {
        $patient = User::factory()->patient()->create();

        $response = $this->actingAs($patient)->post(route('patient.appointments.book'), [
            'appointment_date' => now()->addDay()->toDateString(),
            'appointment_time' => '10:00',
            'consultation_type' => 'phone',
            'reason' => 'متابعة دورية',
        ]);

        $response->assertSessionHasErrors('consultation_type');
    }

    public function test_book_appointment_requires_reason(): void
    {
        $patient = User::factory()->patient()->create();

        $response = $this->actingAs($patient)->post(route('patient.appointments.book'), [
            'appointment_date' => now()->addDay()->toDateString(),
            'appointment_time' => '10:00',
            'consultation_type' => 'online',
        ]);

        $response->assertSessionHasErrors('reason');
    }
}
