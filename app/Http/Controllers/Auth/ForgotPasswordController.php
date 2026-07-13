<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\SendResetCodeMail;
use App\Models\PasswordResetCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class ForgotPasswordController extends Controller
{
    /**
     * أقصى عدد محاولات خاطئة لرمز التحقق قبل ما نلغي الرمز
     * ونطلب من المستخدم يطلب رمز جديد (حماية من التخمين العشوائي).
     */
    private const MAX_VERIFY_ATTEMPTS = 5;

    public function show(Request $request)
    {
        $currentStep = (int) $request->query('step', 1);

        if (!in_array($currentStep, [1, 2, 3])) {
            $currentStep = 1;
        }

        if ($currentStep === 2 && !$request->query('email')) {
            $currentStep = 1;
        }

        if ($currentStep === 3 && (!session('password_reset_verified') || !session('password_reset_email'))) {
            $currentStep = 1;
        }

        return view('auth.forgot-password', compact('currentStep'));
    }

    public function sendCode(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ], [
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.email' => 'صيغة البريد الإلكتروني غير صحيحة.',
            'email.exists' => 'هذا البريد غير مسجل في النظام.',
        ]);

        $code = (string) random_int(1000, 9999);

        PasswordResetCode::where('email', $request->email)->delete();

        PasswordResetCode::create([
            'email' => $request->email,
            'code' => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
        ]);

        Mail::to($request->email)->send(new SendResetCodeMail($code));

        return redirect()->route('forgot-password', [
            'step' => 2,
            'email' => $request->email,
        ])->with('success', 'تم إرسال رمز التحقق إلى بريدك الإلكتروني.');
    }

    public function verifyCode(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:4'],
        ], [
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.email' => 'صيغة البريد الإلكتروني غير صحيحة.',
            'code.required' => 'رمز التحقق مطلوب.',
            'code.digits' => 'رمز التحقق يجب أن يكون 4 أرقام.',
        ]);

        $reset = PasswordResetCode::where('email', $request->email)
            ->whereNull('used_at')
            ->latest()
            ->first();

        if (!$reset) {
            return redirect()->route('forgot-password', [
                'step' => 2,
                'email' => $request->email,
            ])->withErrors([
                'code' => 'لا يوجد طلب استعادة صالح لهذا البريد.',
            ])->withInput();
        }

        if (now()->greaterThan($reset->expires_at)) {
            return redirect()->route('forgot-password', [
                'step' => 2,
                'email' => $request->email,
            ])->withErrors([
                'code' => 'انتهت صلاحية رمز التحقق. اطلب رمزًا جديدًا.',
            ])->withInput();
        }

        if (!Hash::check($request->code, $reset->code)) {
            $reset->increment('attempts');

            if ($reset->attempts >= self::MAX_VERIFY_ATTEMPTS) {
                // تجاوز الحد المسموح من المحاولات: نلغي الرمز الحالي بالكامل
                // بدل ما نترك فرصة لتخمينه لباقي مدة الصلاحية (10 دقائق).
                $reset->update(['used_at' => now()]);

                return redirect()->route('forgot-password', [
                    'step' => 1,
                    'email' => $request->email,
                ])->withErrors([
                    'code' => 'تجاوزت عدد المحاولات المسموح بها. الرجاء طلب رمز جديد.',
                ]);
            }

            return redirect()->route('forgot-password', [
                'step' => 2,
                'email' => $request->email,
            ])->withErrors([
                'code' => 'رمز التحقق غير صحيح.',
            ])->withInput();
        }

        session([
            'password_reset_verified' => true,
            'password_reset_email' => $request->email,
        ]);

        return redirect()->route('forgot-password', [
            'step' => 3,
            'email' => $request->email,
        ])->with('success', 'تم التحقق من الرمز بنجاح.');
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.required' => 'كلمة المرور الجديدة مطلوبة.',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل.',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
        ]);

        if (!session('password_reset_verified') || session('password_reset_email') !== $request->email) {
            return redirect()->route('forgot-password')->withErrors([
                'password' => 'يجب التحقق من الرمز أولًا.',
            ]);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return redirect()->route('forgot-password')->withErrors([
                'password' => 'لم يتم العثور على المستخدم.',
            ]);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        PasswordResetCode::where('email', $request->email)
            ->whereNull('used_at')
            ->update([
                'used_at' => now(),
            ]);

        session()->forget(['password_reset_verified', 'password_reset_email']);

        return redirect()->route('login')
            ->with('success', 'تم تحديث كلمة المرور بنجاح. يمكنك الآن تسجيل الدخول.');
    }

    public function resendCode(Request $request)
    {
        return $this->sendCode($request);
    }




    public function directSetup(Request $request)
{
    $email = $request->query('email');

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return redirect()->route('forgot-password')->withErrors([
            'email' => 'الرابط غير صالح.',
        ]);
    }

    $user = User::where('email', $email)->first();

    if (!$user) {
        return redirect()->route('forgot-password')->withErrors([
            'email' => 'لم يتم العثور على المستخدم.',
        ]);
    }

    session([
        'password_reset_verified' => true,
        'password_reset_email' => $email,
    ]);

    return redirect()->route('forgot-password', [
        'step' => 3,
        'email' => $email,
    ])->with('success', 'يمكنك الآن إنشاء كلمة المرور الخاصة بك.');
}
}
