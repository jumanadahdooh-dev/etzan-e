<?php

namespace App\Http\Requests\Concerns;

/**
 * قاعدة موحدة لأي حقل وزن بالكيلوغرام بكل الفورمات (تسجيل وزن، تعديله،
 * هدف الوزن، تسجيل الطبيب لوزن مريض). كانت هاي القاعدة مكررة بـ 4 أماكن
 * مختلفة بنفس القيم بالضبط — صارت هون مصدر واحد.
 *
 * الحدود (25-350 كغم) مبنية على عمود patient_weight_logs.weight_kg
 * وعمود patient_profiles.target_weight_kg، وهما decimal(5,2) — أقصى قيمة
 * ممكنة بهاد النوع 999.99، بس حددنا 350 كحد طبي معقول لوزن إنسان.
 */
trait HasWeightKgRule
{
    protected function weightKgRules(): array
    {
        return ['required', 'numeric', 'min:25', 'max:350'];
    }

    protected function weightKgMessages(string $field = 'weight_kg'): array
    {
        return [
            $field . '.required' => 'الوزن مطلوب.',
            $field . '.numeric' => 'الوزن يجب أن يكون رقماً.',
            $field . '.min' => 'الوزن المدخل قليل جداً — لازم يكون 25 كغم على الأقل (تأكد إنك مدخل الوزن بالكيلوغرام).',
            $field . '.max' => 'الوزن المدخل كبير جداً — الحد الأقصى المسموح 350 كغم.',
        ];
    }
}
