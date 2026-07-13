<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PatientProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'agree_terms' => ['accepted'],
        ], [
            'name.required' => 'الاسم الكامل مطلوب.',
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.email' => 'صيغة البريد الإلكتروني غير صحيحة.',
            'email.unique' => 'هذا البريد مستخدم بالفعل.',
            'password.required' => 'كلمة المرور مطلوبة.',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل.',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
            'agree_terms.accepted' => 'يجب الموافقة على الشروط والأحكام وسياسة الخصوصية.',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'patient',
        ]);

        PatientProfile::create([
            'user_id' => $user->id,
            'profile_completed' => false,
        ]);

        return redirect()->route('login')->with('success', 'تم إنشاء الحساب بنجاح. يمكنك الآن تسجيل الدخول.');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.email' => 'صيغة البريد الإلكتروني غير صحيحة.',
            'password.required' => 'كلمة المرور مطلوبة.',
        ]);

        $credentials = $request->only('email', 'password');
        $remember = $request->has('remember');

        if (!Auth::attempt($credentials, $remember)) {
            return back()
                ->withErrors([
                    'email' => 'بيانات تسجيل الدخول غير صحيحة.',
                ])
                ->withInput($request->only('email', 'remember'));
        }

        $authedUser = Auth::user();

        if (($authedUser->status ?? 'active') === 'suspended') {
            Auth::logout();

            return back()
                ->withErrors([
                    'email' => 'هذا الحساب موقوف مؤقتاً. تواصل مع الإدارة لمزيد من التفاصيل.',
                ])
                ->withInput($request->only('email', 'remember'));
        }

        $authedUser->forceFill(['last_login_at' => now()])->save();

        $request->session()->regenerate();

        return redirect($this->redirectByRole($authedUser));
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function redirectByRole($user)
    {
        return match ($user->role) {
            'admin' => url('/admin/dashboard'),
            'doctor' => url('/doctor/dashboard'),
            'patient' => url('/patient/home'),
            default => url('/patient/home'),
        };
    }
}
