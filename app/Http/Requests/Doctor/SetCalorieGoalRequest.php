<?php

namespace App\Http\Requests\Doctor;

use Illuminate\Foundation\Http\FormRequest;

class SetCalorieGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'calories_goal' => ['required', 'integer', 'min:800', 'max:6000'],
            'protein_goal' => ['nullable', 'integer', 'min:0', 'max:400'],
            'carbs_goal' => ['nullable', 'integer', 'min:0', 'max:700'],
            'fat_goal' => ['nullable', 'integer', 'min:0', 'max:400'],
            'doctor_note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'calories_goal.required' => 'هدف السعرات اليومي مطلوب.',
            'calories_goal.integer' => 'هدف السعرات يجب أن يكون رقماً صحيحاً.',
            'calories_goal.min' => 'هدف السعرات قليل جداً — الحد الأدنى 800 سعرة (أقل من هيك خطر على صحة المريض).',
            'calories_goal.max' => 'هدف السعرات كبير جداً — الحد الأقصى 6000 سعرة.',
            'protein_goal.max' => 'هدف البروتين أعلى من الحد المعقول (400 غرام).',
            'carbs_goal.max' => 'هدف الكاربوهيدرات أعلى من الحد المعقول (700 غرام).',
            'fat_goal.max' => 'هدف الدهون أعلى من الحد المعقول (400 غرام).',
            'doctor_note.max' => 'ملاحظة الطبيب طويلة كتير — الحد الأقصى 500 حرف.',
        ];
    }
}
