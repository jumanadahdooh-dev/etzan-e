<?php

namespace App\Http\Requests\Patient;

use Illuminate\Foundation\Http\FormRequest;

class AnalyzeMealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'meal_type' => ['required', 'string', 'max:30'],
            'meal_date' => ['nullable', 'date'],

            // نقبل الاسمين عشان لو البلايد يستخدم meal_text أو description
            'meal_text' => ['nullable', 'string', 'max:2000'],
            'description' => ['nullable', 'string', 'max:2000'],

            // نقبل الاسمين عشان لو البلايد يستخدم meal_photo أو meal_image
            'meal_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'meal_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'meal_type.required' => 'نوع الوجبة مطلوب.',
            'meal_type.string' => 'نوع الوجبة غير صحيح.',
            'meal_type.max' => 'نوع الوجبة طويل جداً.',
            'meal_date.date' => 'تاريخ الوجبة غير صحيح.',
            'meal_text.string' => 'وصف الوجبة غير صحيح.',
            'meal_text.max' => 'وصف الوجبة طويل جداً — الحد الأقصى 2000 حرف.',
            'description.string' => 'وصف الوجبة غير صحيح.',
            'description.max' => 'وصف الوجبة طويل جداً — الحد الأقصى 2000 حرف.',
            'meal_photo.image' => 'الصورة يجب أن تكون ملف صورة صحيح.',
            'meal_photo.mimes' => 'صيغة الصورة يجب أن تكون jpg أو jpeg أو png أو webp.',
            'meal_photo.max' => 'حجم الصورة كبير كتير — الحد الأقصى 4 ميغابايت.',
            'meal_image.image' => 'الصورة يجب أن تكون ملف صورة صحيح.',
            'meal_image.mimes' => 'صيغة الصورة يجب أن تكون jpg أو jpeg أو png أو webp.',
            'meal_image.max' => 'حجم الصورة كبير كتير — الحد الأقصى 4 ميغابايت.',
        ];
    }
}
