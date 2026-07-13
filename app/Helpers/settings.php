<?php

use App\Models\Setting;

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        static $settings = null;

        if ($settings === null) {
            $settings = Setting::pluck('value', 'key')->toArray();
        }

        return $settings[$key] ?? $default;
    }
}
