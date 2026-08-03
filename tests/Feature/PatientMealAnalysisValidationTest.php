<?php

namespace Tests\Feature;

use App\Models\PatientProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bug 2: analyzeMeal() كان يستخدم $request->validate([...]) مباشرة بدون
 * رسائل عربية، فرسالة meal_type المفقود كانت ترجع بالإنجليزية.
 *
 * Bug 3: لما meal_type موجود بس بدون وصف/صورة (فحص يدوي بالكود، مش
 * FormRequest)، السيرفر كان يرجع 302 redirect دايماً حتى لو الطلب طالب
 * Accept: application/json.
 */
class PatientMealAnalysisValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->patient = User::factory()->patient()->create();
        PatientProfile::factory()->create(['user_id' => $this->patient->id]);
    }

    public function test_missing_meal_type_returns_arabic_validation_message(): void
    {
        $response = $this->actingAs($this->patient)->post(route('patient.calories.analyze'), []);

        $response->assertSessionHasErrors('meal_type');

        $errors = session('errors');

        $this->assertSame(
            'نوع الوجبة مطلوب.',
            $errors->first('meal_type')
        );
    }

    public function test_missing_meal_type_returns_arabic_validation_message_as_json(): void
    {
        $response = $this->actingAs($this->patient)
            ->postJson(route('patient.calories.analyze'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('meal_type');
        $this->assertSame('نوع الوجبة مطلوب.', $response->json('errors.meal_type.0'));
    }

    public function test_meal_type_without_description_or_photo_redirects_back_for_normal_request(): void
    {
        $response = $this->actingAs($this->patient)->post(route('patient.calories.analyze'), [
            'meal_type' => 'lunch',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('error');
    }

    public function test_meal_type_without_description_or_photo_returns_json_422_when_json_requested(): void
    {
        $response = $this->actingAs($this->patient)
            ->postJson(route('patient.calories.analyze'), [
                'meal_type' => 'lunch',
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'اكتب وصف الوجبة أو ارفع صورة قبل التحليل.',
        ]);
    }
}
