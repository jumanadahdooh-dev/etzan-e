
<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="{{ setting('theme_mode', 'light') }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- تفضيل الزائر الشخصي (لو بدّل الزر) بيغلب إعداد الإدارة العام —
         لازم تنفيذ قبل أي رسم عشان ما يصير وميض بالثيم الغلط. --}}
    <script>
        (function () {
            var saved = localStorage.getItem('etzan-theme');
            if (saved === 'dark' || saved === 'light') {
                document.documentElement.setAttribute('data-theme', saved);
            }
        })();
    </script>

    <title>@yield('title', setting('seo_title', setting('site_name', 'اتزان')))</title>

    <meta name="description" content="{{ setting('seo_description', setting('site_description', 'منصة صحية وغذائية متكاملة')) }}">

    @if(setting('site_favicon'))
        <link rel="icon" href="{{ asset('storage/' . setting('site_favicon')) }}">
    @else
        <link rel="icon" href="{{ asset('front/image/logo.png') }}">
    @endif

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    />

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap"
        rel="stylesheet"
    >

    <!-- مصدر واحد لألوان وخط اتزان (يتحكم بالوضع الفاتح/الغامق) -->
    <link rel="stylesheet" href="{{ asset('front/css/design-tokens.css') }}">

    {{-- بنحمّل shared.css هون عشان زر تبديل المظهر (.etzan-theme-toggle) والفوتر
         البسيط (.auth-footer) المُعرّفين فيه — نفس الهيدر والفوتر على كل صفحات الدخول. --}}
    <link rel="stylesheet" href="{{ asset('front/css/shared.css') }}">

    @stack('styles')
</head>

<body class="auth-minimal-body">

    <header class="auth-topbar">
        <div class="auth-topbar__inner">
            <a href="{{ route('home') }}" class="auth-topbar__brand">
                @if(setting('site_logo'))
                    <img src="{{ asset('storage/' . setting('site_logo')) }}" alt="{{ setting('site_name', 'اتزان') }}">
                @else
                    <img src="{{ asset('front/image/logo.png') }}" alt="{{ setting('site_name', 'اتزان') }}">
                @endif
                <span>{{ setting('site_name', 'اتزان') }}</span>
            </a>

            <div class="auth-topbar__actions">
                <a href="{{ route('home') }}" class="auth-topbar__home-link">
                    <i class="fa-solid fa-house"></i>
                    الرئيسية
                </a>

                <button
                    type="button"
                    class="etzan-theme-toggle"
                    id="etzanThemeToggle"
                    aria-label="التبديل بين المظهر الفاتح والغامق"
                    title="تبديل المظهر"
                >
                    <i class="fa-solid fa-sun etzan-theme-toggle__icon etzan-theme-toggle__icon--sun"></i>
                    <i class="fa-solid fa-moon etzan-theme-toggle__icon etzan-theme-toggle__icon--moon"></i>
                </button>
            </div>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    <footer class="auth-footer">
        <div class="auth-footer__inner">
            <span class="auth-footer__copy">
                &copy; {{ date('Y') }} {{ setting('site_name', 'اتزان') }}. جميع الحقوق محفوظة.
            </span>

            <div class="auth-footer__links">
                <a href="{{ route('privacy') }}">سياسة الخصوصية</a>
                <a href="{{ route('terms') }}">الشروط والأحكام</a>
            </div>
        </div>
    </footer>

    <script src="{{ asset('front/js/shared.js') }}"></script>

    @stack('scripts')
</body>
</html>
