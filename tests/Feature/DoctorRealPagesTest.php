<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\DoctorProfile;
use App\Models\PatientProfile;
use App\Models\PatientTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DoctorRealPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_plans_page_shows_real_calorie_goal_for_a_patient(): void
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
            'calories_goal' => 1850,
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($doctorProfile->user)->get(route('doctor.plans'));

        $response->assertOk();
        $response->assertSee('1850');
        $response->assertSee($patientProfile->user->name);
    }

    public function test_plans_page_lists_patients_without_a_plan_separately(): void
    {
        $doctorProfile = DoctorProfile::factory()->create();
        $patientProfile = PatientProfile::factory()->create([
            'doctor_profile_id' => $doctorProfile->id,
            'doctor_request_status' => 'approved',
        ]);

        $response = $this->actingAs($doctorProfile->user)->get(route('doctor.plans'));

        $response->assertOk();
        $response->assertSee('بدون خطة سعرات');
        $response->assertSee($patientProfile->user->name);
    }

    public function test_alerts_page_flags_patient_with_no_recent_meals(): void
    {
        $doctorProfile = DoctorProfile::factory()->create();
        $patientProfile = PatientProfile::factory()->create([
            'doctor_profile_id' => $doctorProfile->id,
            'doctor_request_status' => 'approved',
        ]);

        DB::table('patient_meals')->insert([
            'user_id' => $patientProfile->user_id,
            'meal_date' => now()->subDays(10)->toDateString(),
            'meal_type' => 'lunch',
            'meal_name' => 'وجبة قديمة',
            'calories' => 400,
            'protein' => 20,
            'carbs' => 40,
            'fat' => 10,
            'confidence' => 80,
            'source' => 'ai',
            'status' => 'confirmed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($doctorProfile->user)->get(route('doctor.alerts'));

        $response->assertOk();
        $response->assertSee('ما سجّل وجبات من');
    }

    public function test_alerts_page_is_empty_when_patient_is_up_to_date(): void
    {
        $doctorProfile = DoctorProfile::factory()->create();
        $patientProfile = PatientProfile::factory()->create([
            'doctor_profile_id' => $doctorProfile->id,
            'doctor_request_status' => 'approved',
        ]);

        DB::table('patient_meals')->insert([
            'user_id' => $patientProfile->user_id,
            'meal_date' => now()->toDateString(),
            'meal_type' => 'lunch',
            'meal_name' => 'وجبة اليوم',
            'calories' => 400,
            'protein' => 20,
            'carbs' => 40,
            'fat' => 10,
            'confidence' => 80,
            'source' => 'ai',
            'status' => 'confirmed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($doctorProfile->user)->get(route('doctor.alerts'));

        $response->assertOk();
        $response->assertSee('ولا في تنبيه حالياً');
    }

    public function test_alerts_page_flags_overdue_doctor_assigned_task(): void
    {
        $doctorProfile = DoctorProfile::factory()->create();
        $patientProfile = PatientProfile::factory()->create([
            'doctor_profile_id' => $doctorProfile->id,
            'doctor_request_status' => 'approved',
        ]);

        PatientTask::factory()->create([
            'patient_id' => $patientProfile->user_id,
            'patient_user_id' => $patientProfile->user_id,
            'source' => 'doctor',
            'status' => 'pending',
            'task_date' => now()->subDays(2)->toDateString(),
        ]);

        $response = $this->actingAs($doctorProfile->user)->get(route('doctor.alerts'));

        $response->assertOk();
        $response->assertSee('مهمة متأخرة');
    }

    public function test_reports_page_shows_real_counts(): void
    {
        $doctorProfile = DoctorProfile::factory()->create();
        PatientProfile::factory()->count(3)->create([
            'doctor_profile_id' => $doctorProfile->id,
            'doctor_request_status' => 'approved',
        ]);

        Article::factory()->create(['user_id' => $doctorProfile->user_id, 'status' => 'published']);

        $response = $this->actingAs($doctorProfile->user)->get(route('doctor.reports'));

        $response->assertOk();
        $response->assertSee('>3<', false);
        $response->assertSee('>1<', false);
    }
}
