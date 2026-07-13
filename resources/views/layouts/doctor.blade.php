<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle ?? 'لوحة الطبيب' }} | اتزان</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">

    {{-- نفس آلية الوضع الغامق تبع صفحة المريض بالحرف — localStorage قبل ما الصفحة توصل ترسم --}}
    <script>
        (function () {
            var savedTheme = localStorage.getItem('etzan-theme');
            var theme = savedTheme === 'dark' ? 'dark' : 'light';

            document.documentElement.setAttribute('data-theme', theme);
            document.documentElement.classList.toggle('dark', theme === 'dark');
            document.documentElement.classList.toggle('light', theme !== 'dark');
        })();
    </script>

    {{-- بالضبط نفس ملفات CSS يلي بتحمّلها صفحة المريض — نفس المصدر، صفر تكرار --}}
    <link rel="stylesheet" href="{{ asset('front/css/patient/patient.css') }}">
    <link rel="stylesheet" href="{{ asset('front/css/patient/dashboard-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('front/css/patient/ux-premium-v3.css') }}">

    <link rel="stylesheet" href="{{ asset('front/css/doctor/doctor.css') }}?v={{ file_exists(public_path('front/css/doctor/doctor.css')) ? filemtime(public_path('front/css/doctor/doctor.css')) : '1' }}">
    <link rel="stylesheet" href="{{ asset('front/css/doctor/doctor-layout.css') }}?v={{ filemtime(public_path('front/css/doctor/doctor-layout.css')) }}">

    @stack('styles')
</head>

<body class="patient-app doctor-app">
    <div class="mobile-backdrop" data-sidebar-backdrop></div>

    <div class="patient-shell">
        <aside class="patient-sidebar" data-patient-sidebar>
            @include('doctor.sidebar')
        </aside>

        <div class="patient-main">
            @include('doctor.topbar')

            <main class="patient-content">
                @yield('content')
            </main>

            <footer class="patient-footer">
                <span>© {{ date('Y') }} اتزان</span>
                <span>لوحة الطبيب</span>
            </footer>
        </div>
    </div>

    <div class="patient-toast" data-toast-container></div>

    @stack('modals')

    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <script src="{{ asset('front/js/patient.js') }}"></script>

    {{-- Chart.js لمخططات الداشبورد الحقيقية --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>

    {{-- تفاعلات خاصة بالطبيب بس (زي الساعة الحية) — الباقي كله من patient.js --}}
    <script src="{{ asset('front/js/doctor.js') }}?v={{ filemtime(public_path('front/js/doctor.js')) }}"></script>

    @stack('scripts')
</body>
</html>
