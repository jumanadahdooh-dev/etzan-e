<?php

namespace App\Http\Requests\Patient;

use Illuminate\Foundation\Http\FormRequest;

class BookAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'appointment_time' => ['required', 'string', 'max:20'],
            'consultation_type' => ['required', 'in:online,clinic'],
            'reason' => ['required', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'appointment_date.required' => 'تاريخ الموعد مطلوب.',
            'appointment_date.date' => 'صيغة التاريخ غير صحيحة.',
            'appointment_date.after_or_equal' => 'ما فيك تحجز موعد بتاريخ فات — اختر تاريخ اليوم أو بعده.',
            'appointment_time.required' => 'وقت الموعد مطلوب.',
            'consultation_type.required' => 'نوع الاستشارة مطلوب.',
            'consultation_type.in' => 'نوع الاستشارة يجب يكون "أونلاين" أو "عيادة".',
            'reason.required' => 'سبب الزيارة مطلوب حتى يقدر الطبيب يستعد للموعد.',
            'reason.max' => 'سبب الزيارة طويل كتير — الحد الأقصى 120 حرف.',
            'notes.max' => 'الملاحظات طويلة كتير — الحد الأقصى 1000 حرف.',
        ];
    }
}
