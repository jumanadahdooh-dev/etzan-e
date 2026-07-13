<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        if (Auth::user()->role !== 'admin') {
            abort(403, 'غير مصرح لك بدخول لوحة الإدارة.');
        }

        // لو الجلسة الحالية أقدم من ميزة "آخر دخول" (last_login_at لسا فاضية)
        // بنسجلها هلق، عشان ما يضل يبين "لسا ما دخل" لحد هو فعلياً جوا اللوحة.
        $user = Auth::user();
        if (empty($user->last_login_at)) {
            $user->forceFill(['last_login_at' => now()])->saveQuietly();
        }

        return $next($request);
    }
}
