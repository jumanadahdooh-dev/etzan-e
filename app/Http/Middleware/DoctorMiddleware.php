<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class DoctorMiddleware
{
    /**
     * يحمي كل روابط /doctor/* — كانت هاي المجموعة بدون أي حماية دخول نهائياً
     * (اكتشاف من التدقيق الشامل)، أي حد كان يقدر يفتحها بدون تسجيل دخول.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        if (Auth::user()->role !== 'doctor') {
            abort(403, 'غير مصرح لك بدخول لوحة الطبيب.');
        }

        return $next($request);
    }
}
