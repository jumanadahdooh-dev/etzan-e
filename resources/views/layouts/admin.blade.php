@php
    $themeMode = setting('theme_mode', 'light');
    $primaryColor = setting('primary_color', '#1D9E75');
    $secondaryColor = setting('secondary_color', '#4DA8DA');
@endphp

<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="{{ $themeMode }}">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'لوحة الإدارة | إتزان')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800;900&family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        :root{
            --brand-primary: {{ $primaryColor }};
            --brand-secondary: {{ $secondaryColor }};
        }
    </style>

    <link rel="stylesheet" href="{{ asset('front/css/admin/admin-tokens.css') }}?v={{ filemtime(public_path('front/css/admin/admin-tokens.css')) }}">
    <link rel="stylesheet" href="{{ asset('front/css/admin/admin.css') }}?v={{ filemtime(public_path('front/css/admin/admin.css')) }}">

    @stack('styles')
</head>

<body data-theme="{{ $themeMode }}" class="admin-body theme-{{ $themeMode }} {{ $themeMode === 'dark' ? 'dark theme-dark' : 'theme-light' }}">
    <div class="admin-mobile-overlay" id="adminMobileOverlay" aria-hidden="true"></div>

    <div class="admin-shell" id="adminShell">
        @include('layouts.admin-sidebar')

        <div class="admin-main" id="adminMain">
            @include('layouts.admin-topbar')

            <main class="admin-content">
                @yield('content')
            </main>
        </div>
    </div>

    @stack('modals')

    <script src="{{ asset('front/js/admin.js') }}?v={{ filemtime(public_path('front/js/admin.js')) }}"></script>
    <script src="{{ asset('front/js/admin-notifications.js') }}?v={{ filemtime(public_path('front/js/admin-notifications.js')) }}"></script>

    @stack('scripts')
</body>
</html>
