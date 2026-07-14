<?php

namespace App\Http\Requests\Patient;

use App\Http\Requests\Concerns\HasWeightKgRule;
use Illuminate\Foundation\Http\FormRequest;

class CompleteProfileRequest extends FormRequest
{
    use HasWeightKgRule;

    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'height_cm' => ['required', 'numeric', 'min:80', 'max:240'],
            'weight_kg' => $this->weightKgRules(),
            'birth_date' => ['required', 'date', 'before:today'],
            'gender' => ['required', 'in:female,male'],
            'phone' => ['nullable', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:120'],
            'health_goal' => ['required', 'string', 'max:80'],
            'activity_level' => ['required', 'string', 'max:80'],
            'medical_conditions' => ['nullable', 'array'],
            'medical_conditions.*' => ['nullable', 'string', 'max:80'],
            'medications' => ['nullable', 'string', 'max:1000'],
            'allergies' => ['nullable', 'string', 'max:1000'],
            'meals_per_day' => ['nullable', 'integer', 'min:1', 'max:8'],
            'sleep_hours' => ['nullable', 'numeric', 'min:0', 'max:16'],
            'water_cups' => ['nullable', 'integer', 'min:0', 'max:20'],
            'preferred_doctor_gender' => ['nullable', 'in:any,female,male'],
            'preferred_consultation_type' => ['nullable', 'in:any,online,clinic'],
            'notes' => ['nullable', 'string', 'max:1500'],
        ];
    }

    public function messages(): array
    {
        return array_merge($this->weightKgMessages(), [
            'avatar.image' => 'الصورة يجب أن تكون ملف صورة صحيح.',
            'avatar.mimes' => 'صيغة الصورة يجب أن تكون jpg أو jpeg أو png أو webp.',
            'avatar.max' => 'حجم الصورة كبير كتير — الحد الأقصى 2 ميغابايت.',
            'height_cm.required' => 'الطول مطلوب.',
            'height_cm.min' => 'الطول المدخل قليل جداً — لازم يكون 80 سم على الأقل.',
            'height_cm.max' => 'الطول المدخل كبير جداً — الحد الأقصى 240 سم.',
            'birth_date.required' => 'تاريخ الميلاد مطلوب.',
            'birth_date.before' => 'تاريخ الميلاد لازم يكون بالماضي.',
            'gender.required' => 'الجنس مطلوب.',
            'gender.in' => 'الجنس يجب يكون "ذكر" أو "أنثى".',
            'health_goal.required' => 'الهدف الصحي مطلوب حتى نقدر نساعدك صح.',
            'activity_level.required' => 'مستوى النشاط اليومي مطلوب.',
            'meals_per_day.min' => 'عدد الوجبات يجب أن يكون وجبة واحدة على الأقل.',
            'meals_per_day.max' => 'عدد الوجبات كبير كتير — الحد الأقصى 8 وجبات باليوم.',
            'sleep_hours.max' => 'ساعات النوم كبيرة كتير — الحد الأقصى 16 ساعة.',
            'water_cups.max' => 'عدد أكواب الماء كبير كتير — الحد الأقصى 20 كوب.',
        ]);
    }
}
