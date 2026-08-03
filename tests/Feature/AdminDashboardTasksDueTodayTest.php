<?php

namespace Tests\Feature;

use App\Models\PatientMeal;
use App\Models\PatientProfile;
use App\Models\PatientTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTasksDueTodayTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_counts_tasks_due_today_from_the_real_task_date_column(): void
    {
        $admin = User::factory()->admin()->create();
        $patientProfile = PatientProfile::factory()->create();

        PatientTask::factory()->create([
            'patient_id' => $patientProfile->user_id,
            'patient_user_id' => $patientProfile->user_id,
            'task_date' => now()->toDateString(),
            'status' => 'pending',
        ]);

        PatientTask::factory()->create([
            'patient_id' => $patientProfile->user_id,
            'patient_user_id' => $patientProfile->user_id,
            'task_date' => now()->addDays(3)->toDateString(),
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewHas('stats', function (array $stats) {
            return $stats['tasks_due_today'] === 1;
        });
    }

    public function test_dashboard_counts_meals_logged_today_even_when_stored_with_a_time_component(): void
    {
        $admin = User::factory()->admin()->create();
        $patientProfile = PatientProfile::factory()->create();

        // PatientMeal يخزّن meal_date عبر Eloquent (cast 'date')، فبتحت SQLite
        // (بيئة الاختبار) بيتخزن بجزء وقت 00:00:00 — لازم يتقارن بـ whereDate().
        PatientMeal::create([
            'user_id' => $patientProfile->user_id,
            'meal_date' => now()->toDateString(),
            'meal_type' => 'lunch',
            'meal_name' => 'وجبة اليوم',
            'calories' => 400,
            'source' => 'ai',
            'status' => 'confirmed',
        ]);

        PatientMeal::create([
            'user_id' => $patientProfile->user_id,
            'meal_date' => now()->subDays(2)->toDateString(),
            'meal_type' => 'dinner',
            'meal_name' => 'وجبة قديمة',
            'calories' => 400,
            'source' => 'ai',
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewHas('stats', function (array $stats) {
            return $stats['meals_today'] === 1;
        });
    }
}
