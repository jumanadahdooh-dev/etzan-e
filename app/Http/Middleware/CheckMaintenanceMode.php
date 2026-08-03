<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldBypassMaintenance($request)) {
            return $next($request);
        }

        try {
            $maintenanceMode = Setting::where('key', 'maintenance_mode')->value('value');

            if ($maintenanceMode !== '1') {
                return $next($request);
            }

            $message = Setting::where('key', 'maintenance_message')->value('value')
                ?: 'الموقع قيد التحديث حاليًا، يرجى المحاولة لاحقًا.';

            return response()->view('errors.maintenance', [
                'message' => $message,
            ], 503);
        } catch (\Throwable $e) {
            // فشل فحص وضع الصيانة (مثلاً اتصال قاعدة بيانات خاطئ لحظيًا) ما لازم
            // يطيح الموقع كله — منكمل الطلب عادي ونسجّل الخطأ عشان يظل مرئي.
            Log::error('CheckMaintenanceMode: فشل فحص وضع الصيانة', ['error' => $e->getMessage()]);

            return $next($request);
        }
    }

    private function shouldBypassMaintenance(Request $request): bool
    {
        return $request->is('admin')
            || $request->is('admin/*')
            || $request->is('login')
            || $request->is('logout')
            || $request->is('storage/*')
            || $request->is('front/*')
            || $request->is('assets/*')
            || $request->is('build/*')
            || $request->is('css/*')
            || $request->is('js/*')
            || $request->is('images/*')
            || $request->is('favicon.ico');
    }
}
