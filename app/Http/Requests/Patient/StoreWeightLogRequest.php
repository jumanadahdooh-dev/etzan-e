<?php

namespace App\Http\Requests\Patient;

use App\Http\Requests\Concerns\HasWeightKgRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreWeightLogRequest extends FormRequest
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
        ];
    }

    public function messages(): array
    {
        return array_merge($this->weightKgMessages(), [
            'logged_date.date' => 'تاريخ القياس غير صحيح.',
            'logged_date.before_or_equal' => 'ما فيك تسجل قياس بتاريخ مستقبلي.',
        ]);
    }
}
