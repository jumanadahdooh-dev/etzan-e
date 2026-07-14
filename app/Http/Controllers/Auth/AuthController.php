<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
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

    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'patient',
        ]);

        PatientProfile::create([
            'user_id' => $user->id,
            'profile_completed' => false,
        ]);

        return redirect()->route('login')->with('success', 'تم إنشاء الحساب بنجاح. يمكنك الآن تسجيل الدخول.');
    }

    public function login(LoginRequest $request)
    {
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
