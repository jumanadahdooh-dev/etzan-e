<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PatientProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    public function redirectToGoogle(Request $request)
    {
        session(['google_auth_from' => $request->get('from', 'login')]);

        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        $googleUser = Socialite::driver('google')->stateless()->user();

        $email = $googleUser->getEmail();

        if (!$email) {
            return redirect()->route('login')->withErrors([
                'email' => 'تعذر الحصول على البريد الإلكتروني من حساب Google.',
            ]);
        }

        $from = session('google_auth_from', 'login');

        $user = User::where('email', $email)->first();
        $isNewUser = false;

        if (!$user) {
            $user = User::create([
                'name' => $googleUser->getName() ?: 'Google User',
                'email' => $email,
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'password' => Hash::make(Str::random(24)),
                'role' => 'patient',
            ]);

            PatientProfile::firstOrCreate(
                ['user_id' => $user->id],
                ['profile_completed' => false]
            );

            $isNewUser = true;
        } else {
            $user->update([
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
            ]);
        }

        if ($from === 'register') {
            if ($isNewUser) {
                $redirectTo = route('login') . '?google_registered=1';
            } else {
                $redirectTo = route('login') . '?google_exists=1';
            }

            return response()->view('auth.social-close', compact('redirectTo'));
        }

        Auth::login($user, true);

        $redirectTo = $this->redirectByRole($user);

        return response()->view('auth.social-close', compact('redirectTo'));
    }

    private function redirectByRole($user)
    {
        return match ($user->role) {
            'admin' => url('/admin/dashboard'),
            'doctor' => url('/doctor/dashboard'),
            default => url('/patient/home'),
        };
    }



    public function redirectToFacebook(\Illuminate\Http\Request $request)
{
    session(['social_auth_from' => $request->get('from', 'login')]);

    return \Laravel\Socialite\Facades\Socialite::driver('facebook')
        ->scopes(['email'])
        ->redirect();
}

public function handleFacebookCallback()
{
    $facebookUser = \Laravel\Socialite\Facades\Socialite::driver('facebook')->stateless()->user();

    $email = $facebookUser->getEmail();

    if (!$email) {
        return redirect()->route('login')->withErrors([
            'email' => 'تعذر الحصول على البريد الإلكتروني من Facebook. تأكدي أن الحساب يسمح بمشاركته.',
        ]);
    }

    $from = session('social_auth_from', 'login');

    $user = \App\Models\User::where('email', $email)->first();
    $isNewUser = false;

    if (!$user) {
        $user = \App\Models\User::create([
            'name' => $facebookUser->getName() ?: 'Facebook User',
            'email' => $email,
            'facebook_id' => $facebookUser->getId(),
            'avatar' => $facebookUser->getAvatar(),
            'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(24)),
            'role' => 'patient',
        ]);

        \App\Models\PatientProfile::firstOrCreate(
            ['user_id' => $user->id],
            ['profile_completed' => false]
        );

        $isNewUser = true;
    } else {
        $user->update([
            'facebook_id' => $facebookUser->getId(),
            'avatar' => $facebookUser->getAvatar(),
        ]);
    }

    if ($from === 'register') {
        $redirectTo = route('login') . ($isNewUser ? '?facebook_registered=1' : '?facebook_exists=1');
        return response()->view('auth.social-close', compact('redirectTo'));
    }

    \Illuminate\Support\Facades\Auth::login($user, true);

    $redirectTo = $this->redirectByRole($user);

    return response()->view('auth.social-close', compact('redirectTo'));
}
}
