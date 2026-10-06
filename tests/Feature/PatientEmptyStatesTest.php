<?php

namespace Tests\Feature;

use App\Models\PatientProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * لما ما في بيانات حقيقية، الصفحات لازم تعرض حالة فارغة بدل أطباء/مهام/مقالات وهمية،
 * ونسبة تقدم الوزن لازم تنحسب من البيانات بدل رقم ثابت.
 */
class PatientEmptyStatesTest extends TestCase
{
    use RefreshDatabase;

    private function patient(array $profile = []): User
    {
        $user = User::factory()->patient()->create();
        PatientProfile::factory()->create(array_merge(['user_id' => $user->id], $profile));

        return $user;
    }

    public function test_home_shows_empty_states_instead_of_fake_tasks_and_articles(): void
    {
        $response = $this->actingAs($this->patient())->get(route('patient.home'));

        $response->assertOk();
        $this->assertSame([], $response->viewData('homeTasks'));
        $this->assertSame([], $response->viewData('articles'));
        $response->assertSee('لا توجد مهام اليوم');
        $response->assertSee('لا توجد مقالات موصى بها الآن');
        $response->assertDontSee('شرب 8 أكواب ماء');
        $response->assertDontSee('أطعمة تعزز المناعة بطريقة طبيعية');
    }

    public function test_recommended_doctors_shows_empty_state_instead_of_fake_doctors(): void
    {
        $response = $this->actingAs($this->patient())->get(route('patient.doctors.recommended'));

        $response->assertOk();
        $response->assertSee('لا يوجد أطباء بعد');
        $response->assertDontSee('د. أحمد سالم');
        $response->assertDontSee('بيانات تجريبية');
    }

    #[DataProvider('weightProvider')]
    public function test_weight_progress_is_calculated_from_real_data(?float $target, array $logs, int $expected): void
    {
        $user = $this->patient(['weight' => $logs ? end($logs) : 90, 'target_weight_kg' => $target]);

        foreach ($logs as $i => $kg) {
            DB::table('patient_weight_logs')->insert([
                'user_id' => $user->id,
                'weight_kg' => $kg,
                'logged_date' => now()->subDays(count($logs) - $i)->toDateString(),
                'source' => 'patient',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $response = $this->actingAs($user)->get(route('patient.home'));

        $response->assertOk();
        $weightStat = collect($response->viewData('homeStats'))->firstWhere('title', 'الوزن');
        $this->assertSame($expected, $weightStat['progress']);
    }

    public static function weightProvider(): array
    {
        return [
            'losing: 100 -> 90, target 80' => [80.0, [100.0, 95.0, 90.0], 50],
            'gaining: 50 -> 55, target 60' => [60.0, [50.0, 55.0], 50],
            'target reached' => [80.0, [100.0, 80.0], 100],
            'moved away from target' => [80.0, [100.0, 104.0], 0],
            'overshot target' => [80.0, [100.0, 76.0], 100],
            'no target set' => [null, [100.0, 90.0], 0],
            'no logs: profile weight only' => [80.0, [], 0],
        ];
    }
}
