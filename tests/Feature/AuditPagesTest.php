<?php

namespace Tests\Feature;

use App\Models\ArticleCategory;
use App\Models\DoctorProfile;
use App\Models\PatientAppointment;
use App\Models\PatientProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * صفحات كانت ترجع 500 حسب تدقيق المشروع: تصنيفات المقالات للأدمن،
 * إشعارات الطبيب، ملف المريض عند الطبيب، وعمود الموعد القادم بالخطط.
 */
class AuditPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_create_edit_and_delete_article_categories(): void
    {
        $admin = User::factory()->admin()->create();
        $category = ArticleCategory::create([
            'name' => 'تغذية علاجية',
            'slug' => 'clinical-nutrition',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $this->actingAs($admin)->get(route('admin.article-categories.index'))
            ->assertOk()
            ->assertSee('تغذية علاجية');

        $this->actingAs($admin)->get(route('admin.article-categories.edit', $category))
            ->assertOk()
            ->assertSee('تغذية علاجية');

        $this->actingAs($admin)->post(route('admin.article-categories.store'), [
            'name' => 'رياضة',
            'sort_order' => 1,
            'is_active' => '1',
        ])->assertRedirect();
        $this->assertDatabaseHas('article_categories', ['name' => 'رياضة', 'sort_order' => 1, 'is_active' => true]);

        $this->actingAs($admin)->put(route('admin.article-categories.update', $category), [
            'name' => 'تغذية علاجية',
            'sort_order' => 5,
            'is_active' => '0',
        ])->assertRedirect(route('admin.article-categories.index'));
        $this->assertDatabaseHas('article_categories', ['id' => $category->id, 'sort_order' => 5, 'is_active' => false]);

        $this->actingAs($admin)->delete(route('admin.article-categories.destroy', $category))->assertRedirect();
        $this->assertDatabaseMissing('article_categories', ['id' => $category->id]);
    }

    public function test_doctor_notifications_page_renders(): void
    {
        $doctorProfile = DoctorProfile::factory()->create();

        $this->actingAs($doctorProfile->user)->get(route('doctor.notifications.index'))->assertOk();
    }

    #[DataProvider('followupStartProvider')]
    public function test_doctor_patient_detail_renders_for_any_followup_start(string $offset): void
    {
        Carbon::setTestNow('2026-10-06 14:30:00');

        $doctorProfile = DoctorProfile::factory()->create();
        $patientProfile = PatientProfile::factory()->create([
            'doctor_profile_id' => $doctorProfile->id,
            'doctor_request_status' => 'approved',
        ]);

        DB::table('patient_profiles')->where('id', $patientProfile->id)
            ->update(['updated_at' => Carbon::parse($offset)]);

        $response = $this->actingAs($doctorProfile->user)
            ->get(route('doctor.patient-profile.show', $patientProfile->id));

        $response->assertOk();

        $trend = $response->viewData('caloriesTrend');
        $this->assertIsArray($trend);
        $this->assertGreaterThanOrEqual(1, count($trend));
        $this->assertLessThanOrEqual(30, count($trend));
        $this->assertSame(now()->locale('ar')->translatedFormat('j M'), end($trend)['label']);

        Carbon::setTestNow();
    }

    public static function followupStartProvider(): array
    {
        return [
            'just now' => ['2026-10-06 14:29:00'],
            'earlier today' => ['2026-10-06 00:05:00'],
            'yesterday late' => ['2026-10-05 23:59:00'],
            'thirty hours ago' => ['2026-10-05 08:30:00'],
            'ten and a half days ago' => ['2026-09-26 02:00:00'],
            'over a month ago' => ['2026-07-01 18:45:00'],
            'clock skew in future' => ['2026-10-07 09:00:00'],
        ];
    }

    public function test_plans_page_shows_upcoming_appointment_from_patient_appointments(): void
    {
        $doctorProfile = DoctorProfile::factory()->create();
        $patientProfile = PatientProfile::factory()->create([
            'doctor_profile_id' => $doctorProfile->id,
            'doctor_request_status' => 'approved',
        ]);

        DB::table('patient_daily_calorie_goals')->insert([
            'user_id' => $patientProfile->user_id,
            'patient_profile_id' => $patientProfile->id,
            'doctor_profile_id' => $doctorProfile->id,
            'doctor_user_id' => $doctorProfile->user_id,
            'goal_date' => now()->toDateString(),
            'calories_goal' => 1700,
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $upcoming = PatientAppointment::factory()->confirmed()->create([
            'user_id' => $patientProfile->user_id,
            'patient_profile_id' => $patientProfile->id,
            'doctor_profile_id' => $doctorProfile->id,
            'appointment_date' => now()->addDays(3)->toDateString(),
            'appointment_time' => '10:30',
        ]);

        // موعد ملغي وموعد لطبيب آخر لازم ما يظهروا
        PatientAppointment::factory()->create([
            'user_id' => $patientProfile->user_id,
            'doctor_profile_id' => $doctorProfile->id,
            'appointment_date' => now()->addDay()->toDateString(),
            'status' => 'rejected',
        ]);
        PatientAppointment::factory()->confirmed()->create([
            'user_id' => $patientProfile->user_id,
            'appointment_date' => now()->addDay()->toDateString(),
        ]);

        $response = $this->actingAs($doctorProfile->user)->get(route('doctor.plans'));

        $response->assertOk();
        $plan = $response->viewData('plans')->first();
        $this->assertNotNull($plan->upcoming_appointment);
        $this->assertSame($upcoming->id, $plan->upcoming_appointment->id);
        $response->assertSee('10:30');
    }
}
