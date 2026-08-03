<?php

namespace App\Services;

/**
 * محرك تقييم غذائي حتمي (rule-based) يبني على الأرقام التي يرجعها
 * AiMealAnalysisService (سعرات/بروتين/كربوهيدرات/دهون/ألياف/سكر/صوديوم).
 *
 * قصدًا هذا المحرك لا يطلب من الذكاء الاصطناعي نفسه أن يخترع Health Score
 * أو نسب توافق الأنظمة الغذائية، لأن ذلك غير متّسق بين استدعاء وآخر وغير
 * قابل للتفسير. بدل هذا، نحسب كل شيء بقواعد ثابتة فوق البيانات الرقمية.
 */
class NutritionInsightService
{
    private const DIET_LABELS = [
        'weight_loss' => 'إنقاص الوزن',
        'muscle_gain' => 'بناء العضلات',
        'keto' => 'كيتو',
        'mediterranean' => 'حمية البحر المتوسط',
        'diabetic_friendly' => 'مناسب لمرضى السكري',
        'heart_healthy' => 'صحة القلب',
        'high_protein' => 'عالي البروتين',
        'low_carb' => 'قليل الكربوهيدرات',
    ];

    public function analyze(array $nutrition): array
    {
        $calories = (int) ($nutrition['calories'] ?? 0);
        $protein = (int) ($nutrition['protein'] ?? 0);
        $carbs = (int) ($nutrition['carbs'] ?? 0);
        $fat = (int) ($nutrition['fat'] ?? 0);
        $fiber = (int) ($nutrition['fiber'] ?? 0);
        $sugar = (int) ($nutrition['sugar'] ?? 0);
        $sodium = (int) ($nutrition['sodium'] ?? 0);
        $ingredientCount = is_array($nutrition['ingredients'] ?? null) ? count($nutrition['ingredients']) : 0;

        $healthScore = $this->healthScore($calories, $protein, $carbs, $fat, $fiber, $sugar, $sodium, $ingredientCount);
        $warnings = $this->warnings($calories, $protein, $fat, $fiber, $sugar, $sodium);
        $advantages = $this->advantages($calories, $protein, $fat, $fiber, $sugar, $sodium);

        return [
            'health_score' => $healthScore,
            'health_grade' => $this->healthGrade($healthScore),
            'advantages' => $advantages,
            'warnings' => $warnings,
            'diet_compatibility' => $this->dietCompatibility($calories, $protein, $carbs, $fat, $fiber, $sugar, $sodium, $ingredientCount),
            'recommendations' => $this->recommendations($warnings, $protein, $calories),
        ];
    }

    private function healthScore(int $calories, int $protein, int $carbs, int $fat, int $fiber, int $sugar, int $sodium, int $ingredientCount): int
    {
        $score = 70;

        $score += match (true) {
            $protein >= 25 => 10,
            $protein >= 15 => 5,
            $protein < 5 => -5,
            default => 0,
        };

        $score += match (true) {
            $fiber >= 8 => 10,
            $fiber >= 4 => 5,
            $fiber < 2 => -5,
            default => 0,
        };

        $score += match (true) {
            $sugar > 25 => -15,
            $sugar > 15 => -8,
            $sugar <= 5 => 5,
            default => 0,
        };

        $score += match (true) {
            $sodium > 1500 => -15,
            $sodium > 900 => -8,
            $sodium <= 400 => 5,
            default => 0,
        };

        $score += match (true) {
            $fat > 35 => -10,
            $fat > 25 => -5,
            $fat > 0 && $fat <= 10 => 3,
            default => 0,
        };

        $score += match (true) {
            $calories > 900 => -10,
            $calories > 700 => -5,
            $calories > 0 && $calories < 150 => -5,
            $calories >= 300 && $calories <= 700 => 5,
            default => 0,
        };

        $score += match (true) {
            $ingredientCount >= 5 => 5,
            $ingredientCount <= 1 => -5,
            default => 0,
        };

        return max(0, min(100, $score));
    }

    private function healthGrade(int $score): string
    {
        return match (true) {
            $score >= 85 => 'ممتاز',
            $score >= 70 => 'جيد جدًا',
            $score >= 55 => 'جيد',
            $score >= 40 => 'متوسط',
            default => 'يحتاج انتباه',
        };
    }

    /**
     * @return string[]
     */
    private function advantages(int $calories, int $protein, int $fat, int $fiber, int $sugar, int $sodium): array
    {
        $advantages = [];

        if ($protein >= 25) {
            $advantages[] = 'غني بالبروتين';
        }

        if ($fiber >= 8) {
            $advantages[] = 'مصدر جيد للألياف';
        }

        if ($fat > 0 && $fat <= 20) {
            $advantages[] = 'دهون ضمن الحد المعتدل';
        }

        if ($sodium > 0 && $sodium <= 600) {
            $advantages[] = 'صوديوم منخفض نسبيًا';
        }

        if ($sugar <= 8) {
            $advantages[] = 'سكر منخفض';
        }

        if ($protein >= 25 && $fiber >= 5) {
            $advantages[] = 'شبع جيد، مناسبة بعد التمرين';
        }

        if ($calories >= 300 && $calories <= 700) {
            $advantages[] = 'كمية سعرات متوازنة لوجبة رئيسية';
        }

        return $advantages;
    }

    /**
     * @return string[]
     */
    private function warnings(int $calories, int $protein, int $fat, int $fiber, int $sugar, int $sodium): array
    {
        $warnings = [];

        if ($sodium > 1200) {
            $warnings[] = 'صوديوم مرتفع';
        }

        if ($sugar > 20) {
            $warnings[] = 'سكر مرتفع';
        }

        if ($fiber < 3) {
            $warnings[] = 'ألياف منخفضة';
        }

        if ($fat > 30) {
            $warnings[] = 'دهون مرتفعة';
        }

        if ($calories > 900) {
            $warnings[] = 'كمية سعرات كبيرة لوجبة واحدة';
        }

        if ($protein < 8) {
            $warnings[] = 'بروتين منخفض لوجبة رئيسية';
        }

        return $warnings;
    }

    /**
     * @return array<string, array{label: string, score: int, compatible: bool}>
     */
    private function dietCompatibility(int $calories, int $protein, int $carbs, int $fat, int $fiber, int $sugar, int $sodium, int $ingredientCount): array
    {
        $safeCalories = max(1, $calories);
        $proteinPct = min(100, ($protein * 4 / $safeCalories) * 100);
        $fatPct = min(100, ($fat * 9 / $safeCalories) * 100);

        $scores = [
            'weight_loss' => $this->clampScore(
                50
                + ($calories > 0 && $calories <= 600 ? 20 : 0)
                + ($proteinPct >= 25 ? 15 : 0)
                + ($fiber >= 5 ? 10 : 0)
                - ($sugar > 15 ? 15 : 0)
                - ($calories > 800 ? 10 : 0)
            ),

            'muscle_gain' => $this->clampScore(
                50
                + ($protein >= 30 ? 25 : ($protein >= 20 ? 10 : 0))
                + ($calories >= 500 ? 15 : 0)
                - ($protein < 15 ? 10 : 0)
            ),

            'keto' => $this->clampScore(
                50
                + ($carbs <= 20 ? 30 : ($carbs <= 50 ? -10 : -30))
                + ($fatPct >= 60 ? 15 : 0)
            ),

            'mediterranean' => $this->clampScore(
                50
                + ($fiber >= 5 ? 15 : 0)
                + ($sodium <= 700 ? 10 : ($sodium > 1200 ? -10 : 0))
                + ($fatPct >= 25 && $fatPct <= 40 ? 10 : 0)
                + ($ingredientCount >= 4 ? 10 : 0)
            ),

            'diabetic_friendly' => $this->clampScore(
                50
                + ($sugar <= 10 ? 20 : ($sugar > 25 ? -20 : 0))
                + ($fiber >= 5 ? 15 : 0)
                + ($carbs <= 45 ? 10 : ($carbs > 75 ? -10 : 0))
            ),

            'heart_healthy' => $this->clampScore(
                50
                + ($sodium <= 600 ? 20 : ($sodium > 1500 ? -20 : 0))
                + ($fiber >= 5 ? 15 : 0)
                + ($fat <= 20 ? 10 : ($fat > 35 ? -10 : 0))
            ),

            'high_protein' => $this->clampScore(match (true) {
                $protein >= 40 => 95,
                $protein >= 30 => 85,
                $protein >= 20 => 70,
                $protein >= 10 => 50,
                default => 30,
            }),

            'low_carb' => $this->clampScore(match (true) {
                $carbs <= 20 => 95,
                $carbs <= 40 => 80,
                $carbs <= 60 => 60,
                $carbs <= 90 => 40,
                default => 20,
            }),
        ];

        $result = [];

        foreach ($scores as $key => $score) {
            $result[$key] = [
                'label' => self::DIET_LABELS[$key],
                'score' => $score,
                'compatible' => $score >= 60,
            ];
        }

        return $result;
    }

    private function clampScore(int|float $score): int
    {
        return (int) max(0, min(100, round($score)));
    }

    /**
     * @param  string[]  $warnings
     * @return string[]
     */
    private function recommendations(array $warnings, int $protein, int $calories): array
    {
        $recommendations = [];

        if (in_array('صوديوم مرتفع', $warnings, true)) {
            $recommendations[] = 'قلّل الصوصات المالحة أو كمية الملح المضاف.';
        }

        if (in_array('سكر مرتفع', $warnings, true)) {
            $recommendations[] = 'استبدل المصادر السكرية بفواكه طازجة أو قلّل الكمية.';
        }

        if (in_array('ألياف منخفضة', $warnings, true)) {
            $recommendations[] = 'أضف خضار أو حبوب كاملة لزيادة الألياف.';
        }

        if (in_array('دهون مرتفعة', $warnings, true)) {
            $recommendations[] = 'جرّب طريقة طهي مشوية أو مسلوقة بدل المقلية.';
        }

        if (in_array('كمية سعرات كبيرة لوجبة واحدة', $warnings, true)) {
            $recommendations[] = 'قلّل حجم الحصة أو شارك الوجبة على وجبتين.';
        }

        if (in_array('بروتين منخفض لوجبة رئيسية', $warnings, true)) {
            $recommendations[] = 'أضف مصدر بروتين مثل الدجاج أو البيض أو البقوليات.';
        }

        if ($recommendations === []) {
            $recommendations[] = 'الوجبة متوازنة بشكل جيد، حافظ على هذا النمط.';
        }

        return $recommendations;
    }
}
