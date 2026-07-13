<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PatientMiddleware
{
    /**
     * يحمي كل روابط /patient/* — كانت هاي المجموعة تتطلب تسجيل دخول فقط
     * بدون التحقق من الدور، فأي حساب أدمن أو طبيب كان يقدر يفتحها
     * (اكتشاف من التدقيق الشامل).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        if (Auth::user()->role !== 'patient') {
            abort(403, 'غير مصرح لك بدخول لوحة المريض.');
        }

        return $next($request);
    }
}
