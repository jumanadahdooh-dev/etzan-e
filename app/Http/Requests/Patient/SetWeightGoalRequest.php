<?php

namespace App\Http\Requests\Patient;

use App\Http\Requests\Concerns\HasWeightKgRule;
use Illuminate\Foundation\Http\FormRequest;

class SetWeightGoalRequest extends FormRequest
{
    use HasWeightKgRule;

    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'target_weight_kg' => $this->weightKgRules(),
        ];
    }

    public function messages(): array
    {
        return $this->weightKgMessages('target_weight_kg');
    }
}
