<?php

namespace Tests\Feature;

use App\Models\DoctorProfile;
use App\Models\PatientProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * اختبار "دخان" شامل بعد تقسيم PatientHomeController لعدة controllers —
 * بيفتح كل صفحة GET رئيسية للمريض ويتأكد إنها ما تنهار (500)، حتى نطمن
 * إنه نقل الكود لملفات جديدة ما كسر أي مسار.
 */
class PatientSplitControllersSmokeTest extends TestCase
{
    use RefreshDatabase;

    private User $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->patient = User::factory()->patient()->create();
        PatientProfile::factory()->create(['user_id' => $this->patient->id]);
    }

    /** @return array<string, array{0: string}> */
    public static function routesProvider(): array
    {
        return [
            'dashboard index' => ['patient.index'],
            'home' => ['patient.home'],
            'followup' => ['patient.followup'],
            'profile' => ['patient.profile'],
            'recommended doctors' => ['patient.doctors.recommended'],
            'my doctor' => ['patient.doctor.current'],
            'calories' => ['patient.calories'],
            'articles' => ['patient.articles'],
            'messages' => ['patient.messages'],
            'support' => ['patient.support'],
            'notifications' => ['patient.notifications'],
            'live notifications' => ['patient.live.notifications'],
        ];
    }

    /** @dataProvider routesProvider */
    public function test_patient_page_does_not_crash(string $routeName): void
    {
        $response = $this->actingAs($this->patient)->get(route($routeName));

        $this->assertNotEquals(500, $response->getStatusCode(), "Route [$routeName] returned a 500 error.");
    }

    public function test_doctor_details_page_does_not_crash(): void
    {
        $doctorProfile = DoctorProfile::factory()->create();

        $response = $this->actingAs($this->patient)->get(
            route('patient.doctors.details', $doctorProfile->id)
        );

        $this->assertNotEquals(500, $response->getStatusCode());
    }
}
