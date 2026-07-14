<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * القواعد مطابقة تماماً لأعمدة جدول users:
     * - name: string, لا يوجد حد بقاعدة البيانات، بس محدد 255 (حد الـ varchar الافتراضي بلارافيل).
     * - email: string فريد (unique) وبحد أقصى 255 حرف.
     * - password: عمود hashed، بحد أدنى 8 أحرف. أضفنا max:72 لأنه bcrypt
     *   (المستخدم فعلياً بالمشروع) بيتجاهل أي حرف بعد الـ 72 بايت بصمت —
     *   لازم نمنع المستخدم يدخل كلمة سر أطول ويتفاجأ لاحقاً إنها اتقصّت.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed'],
            'agree_terms' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'الاسم الكامل مطلوب.',
            'name.max' => 'الاسم طويل كتير — الحد الأقصى 255 حرف.',
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.email' => 'صيغة البريد الإلكتروني غير صحيحة.',
            'email.max' => 'البريد الإلكتروني طويل كتير — الحد الأقصى 255 حرف.',
            'email.unique' => 'هذا البريد مستخدم بالفعل — جربي تسجيل الدخول أو استخدمي بريد آخر.',
            'password.required' => 'كلمة المرور مطلوبة.',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل حتى تكون آمنة.',
            'password.max' => 'كلمة المرور طويلة كتير — الحد الأقصى 72 حرف.',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق مع كلمة المرور.',
            'agree_terms.accepted' => 'يجب الموافقة على الشروط والأحكام وسياسة الخصوصية للمتابعة.',
        ];
    }
}
