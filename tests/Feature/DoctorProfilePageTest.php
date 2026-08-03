<?php

namespace Tests\Feature;

use App\Models\DoctorProfile;
use App\Models\Specialty;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorProfilePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_shows_the_doctors_real_data(): void
    {
        $doctorProfile = DoctorProfile::factory()->create([
            'workplace' => 'عيادة اتزان المركزية',
            'bio' => 'طبيب تغذية علاجية.',
            'years_experience' => 9,
        ]);

        $response = $this->actingAs($doctorProfile->user)->get(route('doctor.profile'));

        $response->assertOk();
        $response->assertSee('عيادة اتزان المركزية');
        $response->assertSee('طبيب تغذية علاجية.');
        $response->assertSee($doctorProfile->user->name);
    }

    public function test_doctor_can_update_their_profile_and_specialties(): void
    {
        $doctorProfile = DoctorProfile::factory()->create();
        $specialty = Specialty::create(['name' => 'تغذية علاجية', 'slug' => 'nutrition-therapy', 'is_active' => true]);

        $response = $this->actingAs($doctorProfile->user)->put(route('doctor.profile.update'), [
            'workplace' => 'مستشفى الأمل',
            'years_experience' => 12,
            'bio' => 'نبذة محدّثة عني.',
            'specialties' => [$specialty->id],
        ]);

        $response->assertRedirect(route('doctor.profile'));

        $this->assertDatabaseHas('doctor_profiles', [
            'id' => $doctorProfile->id,
            'workplace' => 'مستشفى الأمل',
            'years_experience' => 12,
        ]);

        $this->assertTrue($doctorProfile->fresh()->specialties->contains($specialty->id));
    }
}
