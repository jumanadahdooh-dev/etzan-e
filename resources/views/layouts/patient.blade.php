<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle ?? 'لوحة المريض' }} | اتزان</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">

    <script>
        (function () {
            var savedTheme = localStorage.getItem('etzan-theme');
            var theme = savedTheme === 'dark' ? 'dark' : 'light';

            document.documentElement.setAttribute('data-theme', theme);
            document.documentElement.classList.toggle('dark', theme === 'dark');
            document.documentElement.classList.toggle('light', theme !== 'dark');
        })();
    </script>

    <link rel="stylesheet" href="{{ asset('front/css/patient/patient.css') }}">
    <link rel="stylesheet" href="{{ asset('front/css/patient/dashboard-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('front/css/patient/ux-premium-v3.css') }}">
    <link rel="stylesheet" href="{{ asset('front/css/patient/etzan-patient-premium.css') }}">

    @stack('styles')
</head>

<body class="patient-app">
    <div class="mobile-backdrop" data-sidebar-backdrop></div>

    <div class="patient-shell">
        <aside class="patient-sidebar" data-patient-sidebar>
            @include('patient.sidebar')
        </aside>

        <div class="patient-main">
            @include('patient.topbar')

            <main class="patient-content">
                @yield('content')
            </main>

            <footer class="patient-footer">
                <span>© {{ date('Y') }} اتزان</span>
                <span>صحة تعيشها كل يوم</span>
            </footer>
        </div>
    </div>

    <div class="patient-toast" data-toast-container></div>

    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <script src="{{ asset('front/js/patient.js') }}"></script>

    @stack('scripts')
</body>
</html>
