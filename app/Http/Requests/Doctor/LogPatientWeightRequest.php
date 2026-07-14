<?php

namespace App\Http\Requests\Doctor;

use App\Http\Requests\Concerns\HasWeightKgRule;
use Illuminate\Foundation\Http\FormRequest;

class LogPatientWeightRequest extends FormRequest
{
    use HasWeightKgRule;

    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'weight_kg' => $this->weightKgRules(),
            'logged_date' => ['nullable', 'date', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:300'],
        ];
    }

    public function messages(): array
    {
        return array_merge($this->weightKgMessages(), [
            'logged_date.date' => 'تاريخ القياس غير صحيح.',
            'logged_date.before_or_equal' => 'ما فيك تسجل قياس بتاريخ مستقبلي.',
            'note.max' => 'الملاحظة طويلة كتير — الحد الأقصى 300 حرف.',
        ]);
    }
}
