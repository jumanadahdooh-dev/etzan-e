<?php

namespace Tests\Feature;

use App\Models\DoctorProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DoctorSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_page_shows_availability_and_schedule_form(): void
    {
        $doctorProfile = DoctorProfile::factory()->create(['is_available' => true]);

        $response = $this->actingAs($doctorProfile->user)->get(route('doctor.settings'));

        $response->assertOk();
        $response->assertSee('متاح لاستقبال طلبات جديدة');
        $response->assertSee('جدول ساعات العمل');
    }

    public function test_doctor_can_change_their_password(): void
    {
        $doctorProfile = DoctorProfile::factory()->create();
        $doctorProfile->user->update(['password' => Hash::make('old-password-123')]);

        $response = $this->actingAs($doctorProfile->user)->put(route('doctor.settings.password'), [
            'current_password' => 'old-password-123',
            'password' => 'new-password-456',
            'password_confirmation' => 'new-password-456',
        ]);

        $response->assertRedirect(route('doctor.settings'));
        $this->assertTrue(Hash::check('new-password-456', $doctorProfile->user->fresh()->password));
    }

    public function test_password_change_rejects_wrong_current_password(): void
    {
        $doctorProfile = DoctorProfile::factory()->create();
        $doctorProfile->user->update(['password' => Hash::make('old-password-123')]);

        $response = $this->actingAs($doctorProfile->user)->put(route('doctor.settings.password'), [
            'current_password' => 'wrong-password',
            'password' => 'new-password-456',
            'password_confirmation' => 'new-password-456',
        ]);

        $response->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('old-password-123', $doctorProfile->user->fresh()->password));
    }

    public function test_doctor_can_save_a_weekly_schedule(): void
    {
        $doctorProfile = DoctorProfile::factory()->create();

        $response = $this->actingAs($doctorProfile->user)->put(route('doctor.settings.schedule'), [
            'days' => [
                0 => ['active' => '1', 'start_time' => '10:00', 'end_time' => '16:00'],
                1 => ['active' => '1', 'start_time' => '12:00', 'end_time' => '18:00'],
            ],
        ]);

        $response->assertRedirect(route('doctor.settings'));

        $this->assertDatabaseHas('doctor_schedules', [
            'doctor_profile_id' => $doctorProfile->id,
            'day_of_week' => 0,
        ]);

        $this->assertSame(2, $doctorProfile->schedules()->count());
    }

    public function test_saving_schedule_removes_days_that_were_unchecked(): void
    {
        $doctorProfile = DoctorProfile::factory()->create();
        $doctorProfile->schedules()->create(['day_of_week' => 2, 'start_time' => '09:00', 'end_time' => '17:00']);

        $response = $this->actingAs($doctorProfile->user)->put(route('doctor.settings.schedule'), [
            'days' => [],
        ]);

        $response->assertRedirect(route('doctor.settings'));
        $this->assertSame(0, $doctorProfile->schedules()->count());
    }
}
