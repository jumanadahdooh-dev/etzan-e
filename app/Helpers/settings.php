<?php

use App\Models\Setting;

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        static $settings = null;

        if ($settings === null) {
            try {
                $settings = Setting::pluck('value', 'key')->toArray();
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('setting(): فشل قراءة إعدادات الموقع', ['error' => $e->getMessage()]);
                $settings = [];
            }
        }

        return $settings[$key] ?? $default;
    }
}
