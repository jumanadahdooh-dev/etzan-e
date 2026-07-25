<?php

namespace App\Services;

use App\Models\PatientDailyCalorieGoal;
use App\Models\PatientProfile;
use Carbon\Carbon;

class CalorieSuggestionService
{
    private const ACTIVITY_MULTIPLIERS = [
        'low' => 1.2,
        'light' => 1.375,
        'moderate' => 1.55,
        'high' => 1.725,
    ];

    private const GOAL_ADJUSTMENTS = [
        'weight_loss' => 0.85,
        'weight_gain' => 1.15,
    ];

    /**
     * يرجع null إذا نواقص بيانات أساسية (طول/وزن/عمر/جنس/نشاط) بدل تخمين قيم.
     *
     * @return array{calories: int, protein: int, carbs: int, fat: int, bmi: float, bmi_category: string}|null
     */
    public function suggestFor(PatientProfile $profile): ?array
    {
        $heightCm = (float) ($profile->height ?? 0);
        $weightKg = (float) ($profile->weight ?? 0);
        $activityLevel = $profile->activity_level;
        $gender = $profile->gender;
        $age = $this->ageFrom($profile->birth_date);

        if ($heightCm < 80 || $weightKg <= 0 || $age === null || !in_array($gender, ['male', 'female'], true)) {
            return null;
        }

        $multiplier = self::ACTIVITY_MULTIPLIERS[$activityLevel] ?? null;

        if ($multiplier === null) {
            return null;
        }

        $bmr = $gender === 'male'
            ? (10 * $weightKg) + (6.25 * $heightCm) - (5 * $age) + 5
            : (10 * $weightKg) + (6.25 * $heightCm) - (5 * $age) - 161;

        $calories = $bmr * $multiplier;

        $goalAdjustment = self::GOAL_ADJUSTMENTS[$profile->health_goal] ?? 1.0;
        $calories *= $goalAdjustment;

        $calories = (int) round(max(1200, $calories));

        [$bmi, $bmiCategory] = $this->bmi($heightCm, $weightKg);

        return [
            'calories' => $calories,
            'protein' => (int) round(($calories * 0.30) / 4),
            'carbs' => (int) round(($calories * 0.40) / 4),
            'fat' => (int) round(($calories * 0.30) / 9),
            'bmi' => $bmi,
            'bmi_category' => $bmiCategory,
        ];
    }

    /**
     * يحسب الاقتراح ويكتبه لهدف اليوم الحالي بحالة "suggested" — بس إذا ما في
     * صف أصلاً لهذا التاريخ أو كان آخر صف بحالة "suggested" (ما بنلمس أبداً
     * صف اعتمده الطبيب "approved").
     */
    public function refreshSuggestionForUser(int $userId): void
    {
        $profile = PatientProfile::where('user_id', $userId)->first();

        if (!$profile) {
            return;
        }

        $existing = PatientDailyCalorieGoal::query()
            ->where('user_id', $userId)
            ->whereDate('goal_date', now()->toDateString())
            ->latest('id')
            ->first();

        if ($existing && $existing->status === 'approved') {
            return;
        }

        $suggestion = $this->suggestFor($profile);

        if (!$suggestion) {
            return;
        }

        PatientDailyCalorieGoal::updateOrCreate(
            ['user_id' => $userId, 'goal_date' => now()->toDateString()],
            [
                'patient_profile_id' => $profile->id,
                'doctor_profile_id' => $profile->doctor_profile_id,
                'calories_goal' => $suggestion['calories'],
                'protein_goal' => $suggestion['protein'],
                'carbs_goal' => $suggestion['carbs'],
                'fat_goal' => $suggestion['fat'],
                'status' => 'suggested',
            ]
        );
    }

    private function ageFrom(?string $birthDate): ?int
    {
        if (!$birthDate) {
            return null;
        }

        try {
            return (int) Carbon::parse($birthDate)->age;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @return array{0: float, 1: string}
     */
    private function bmi(float $heightCm, float $weightKg): array
    {
        $bmi = round($weightKg / (($heightCm / 100) ** 2), 1);

        $category = match (true) {
            $bmi < 18.5 => 'المؤشر أقل من الطبيعي، يفضل رفع السعرات بشكل صحي.',
            $bmi < 25 => 'المؤشر ضمن النطاق الطبيعي تقريباً.',
            $bmi < 30 => 'المؤشر أعلى من الطبيعي قليلاً.',
            default => 'المؤشر مرتفع، والمتابعة مع طبيب مختص أفضل.',
        };

        return [$bmi, $category];
    }
}
