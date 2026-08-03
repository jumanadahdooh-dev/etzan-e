
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
    <meta name="keywords" content="{{ setting('seo_keywords', 'تغذية، صحة، أطباء، توصيات غذائية') }}">

    @if(setting('site_favicon'))
        <link rel="icon" href="{{ asset('storage/' . setting('site_favicon')) }}">
    @else
        <link rel="icon" href="{{ asset('front/image/logo.png') }}">
    @endif


    <!-- Bootstrap RTL -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css"
        rel="stylesheet"
    />

    <!-- Bootstrap Icons -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap"
        rel="stylesheet"
    >

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    />

    <!-- مصدر واحد لألوان وخط اتزان (يتحكم بالوضع الفاتح/الغامق) -->
    <link rel="stylesheet" href="{{ asset('front/css/design-tokens.css') }}">

    <!-- ستايل مشترك للهيدر والفوتر -->
    <link rel="stylesheet" href="{{ asset('front/css/shared.css') }}">

    @stack('styles')

</head>

<body class="admin-body">

    <!-- زر تبديل المظهر الفاتح/الغامق — عائم بمكانه الخاص، مش داخل الهيدر،
         عشان يضل ظاهر ومتاح دائمًا حتى وقائمة الجوال مسكرة. -->
    <button
        type="button"
        class="etzan-theme-toggle etzan-theme-toggle--floating"
        id="etzanThemeToggle"
        aria-label="التبديل بين المظهر الفاتح والغامق"
        title="تبديل المظهر"
    >
        <i class="fa-solid fa-sun etzan-theme-toggle__icon etzan-theme-toggle__icon--sun"></i>
        <i class="fa-solid fa-moon etzan-theme-toggle__icon etzan-theme-toggle__icon--moon"></i>
    </button>

    <!-- Header -->
    <header class="main-header">
        <nav class="navbar navbar-expand-lg">
            <div class="container-fluid">

                <a class="navbar-brand m-0" href="{{ route('home') }}#hero">
                    <div class="brand-box">
                        <div class="brand-logo">
                            @if(setting('site_logo'))
                                <img
                                    src="{{ asset('storage/' . setting('site_logo')) }}"
                                    alt="{{ setting('site_name', 'اتزان') }}"
                                >
                            @else
                                <img
                                    src="{{ asset('front/image/logo.png') }}"
                                    alt="{{ setting('site_name', 'اتزان') }}"
                                >
                            @endif
                        </div>

                        <div class="brand-text">
                            <h4>{{ setting('site_name', 'اتزان') }}</h4>
                            <span>{{ setting('site_description', 'التوعية الصحية والتغذية') }}</span>
                        </div>
                    </div>
                </a>

                <button
                    class="navbar-toggler"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#mainNavbar"
                    aria-controls="mainNavbar"
                    aria-expanded="false"
                    aria-label="Toggle navigation"
                >
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="mainNavbar">
                    <ul class="navbar-nav mx-auto mb-2 mb-lg-0">

                        <li class="nav-item">
                            <a
                                class="nav-link nav-scroll-link"
                                data-section="hero"
                                href="{{ route('home') }}#hero"
                            >
                                الرئيسية
                            </a>
                        </li>

                        <li class="nav-item">
                            <a
                                class="nav-link nav-scroll-link"
                                data-section="how-it-works"
                                href="{{ route('home') }}#how-it-works"
                            >
                                عن المنصة
                            </a>
                        </li>

                        <li class="nav-item">
                            <a
                                class="nav-link nav-scroll-link"
                                data-section="doctors"
                                href="{{ route('home') }}#doctors"
                            >
                                الأطباء
                            </a>
                        </li>

                        <li class="nav-item">
                            <a
                                class="nav-link nav-scroll-link"
                                data-section="articles"
                                href="{{ route('home') }}#articles"
                            >
                                المقالات التوعوية
                            </a>
                        </li>

                        <li class="nav-item">
                            <a
                                class="nav-link {{ request()->routeIs('contact') ? 'active' : '' }}"
                                href="{{ route('contact') }}"
                            >
                                اتصل بنا
                            </a>
                        </li>
                    </ul>

                    <div class="header-actions">
                        <a href="{{ route('login') }}" class="btn-login text-decoration-none">
                            تسجيل الدخول
                        </a>

                        <a href="{{ route('register') }}" class="btn-start text-decoration-none">
                            ابدأ رحلتك الآن
                        </a>
                    </div>
                </div>

            </div>
        </nav>

        <div class="bottom-line"></div>
    </header>

    <main>
        @yield('content')
    </main>

   @php
    $normalizeFooterUrl = function ($url, $type = 'link') {
        if (blank($url)) {
            return null;
        }

        $url = trim($url);

        if ($type === 'email') {
            return str_starts_with($url, 'mailto:') ? $url : 'mailto:' . $url;
        }

        if ($type === 'phone') {
            return str_starts_with($url, 'tel:') ? $url : 'tel:' . $url;
        }

        if ($type === 'whatsapp') {
            if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
                return $url;
            }

            $phone = preg_replace('/[^0-9]/', '', $url);

            return $phone ? 'https://wa.me/' . $phone : null;
        }

        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            return 'https://' . $url;
        }

        return $url;
    };

    $footerSocialLinks = [
        [
            'key' => 'instagram_url',
            'type' => 'link',
            'icon' => 'fa-brands fa-instagram',
            'label' => 'Instagram',
        ],
        [
            'key' => 'x_url',
            'type' => 'link',
            'icon' => 'fa-brands fa-x-twitter',
            'label' => 'X',
        ],
        [
            'key' => 'facebook_url',
            'type' => 'link',
            'icon' => 'fa-brands fa-facebook-f',
            'label' => 'Facebook',
        ],
        [
            'key' => 'youtube_url',
            'type' => 'link',
            'icon' => 'fa-brands fa-youtube',
            'label' => 'YouTube',
        ],
        [
            'key' => 'contact_whatsapp',
            'type' => 'whatsapp',
            'icon' => 'fa-brands fa-whatsapp',
            'label' => 'WhatsApp',
        ],
    ];

    $hasFooterSocialLinks = collect($footerSocialLinks)->contains(function ($social) use ($normalizeFooterUrl) {
        return filled($normalizeFooterUrl(setting($social['key']), $social['type']));
    });

    $footerEmail = setting('contact_email', 'info@etzan.com');
    $footerPhone = setting('contact_phone', '+970599000000');
    $footerWhatsapp = setting('contact_whatsapp');
    $footerAddress = setting('contact_address', 'فلسطين - غزة');
    $footerWorkingHours = setting('working_hours', 'السبت - الخميس | 9:00 ص - 5:00 م');

    $footerEmailUrl = $normalizeFooterUrl($footerEmail, 'email');
    $footerPhoneUrl = $normalizeFooterUrl($footerPhone, 'phone');
    $footerWhatsappUrl = $normalizeFooterUrl($footerWhatsapp, 'whatsapp');
@endphp

<footer class="etzan-footer">
    <div class="container">

        <div class="etzan-footer-top" id="footer-contact">

            {{-- نبذة --}}
            <div class="etzan-footer-col etzan-footer-about">
                <a href="{{ route('home') }}#hero" class="etzan-footer-logo">
                    <span class="logo-mark">
                        <i class="fa-solid fa-heart-pulse"></i>
                    </span>

                    <span class="logo-text">
                        {{ setting('site_name', 'اتزان') }}
                    </span>
                </a>

                <p>
                    {{ setting('site_description', 'منصة صحية وتوعوية تساعدك على بناء نمط حياة أكثر توازنًا من خلال التوعية، المتابعة، والخدمات الصحية والغذائية المناسبة لك.') }}
                </p>

                @if($hasFooterSocialLinks)
                    <div class="etzan-footer-socials">
                        @foreach($footerSocialLinks as $social)
                            @php
                                $socialUrl = $normalizeFooterUrl(setting($social['key']), $social['type']);
                            @endphp

                            @if($socialUrl)
                                <a
                                    href="{{ $socialUrl }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    aria-label="{{ $social['label'] }}"
                                    title="{{ $social['label'] }}"
                                >
                                    <i class="{{ $social['icon'] }}"></i>
                                </a>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- روابط سريعة --}}
            <div class="etzan-footer-col">
                <h3>روابط سريعة</h3>

                <ul>
                    <li>
                        <a href="{{ route('home') }}#hero">الرئيسية</a>
                    </li>

                    <li>
                        <a href="{{ route('home') }}#how-it-works">كيف يعمل اتزان</a>
                    </li>

                    <li>
                        <a href="{{ route('home') }}#features">ما يميزنا</a>
                    </li>

                    <li>
                        <a href="{{ route('home') }}#services">خدمات اتزان</a>
                    </li>

                    <li>
                        <a href="{{ route('home') }}#doctors">الأطباء</a>
                    </li>
                </ul>
            </div>

            {{-- المحتوى والمساعدة --}}
            <div class="etzan-footer-col">
                <h3>المحتوى والمساعدة</h3>

                <ul>
                    <li>
                        <a href="{{ route('home') }}#articles">المقالات</a>
                    </li>

                    <li>
                        <a href="{{ route('home') }}#quiz">الكويز السريع</a>
                    </li>

                    <li>
                        <a href="{{ route('home') }}#faq">الأسئلة الشائعة</a>
                    </li>

                    <li>
                        <a href="{{ route('privacy') }}">سياسة الخصوصية</a>
                    </li>

                    <li>
                        <a href="{{ route('terms') }}">الشروط والأحكام</a>
                    </li>
                </ul>
            </div>

            {{-- تواصل معنا --}}
            <div class="etzan-footer-col">
                <h3>تواصل معنا</h3>

                <ul class="etzan-footer-contact">

                    @if($footerEmail)
                        <li>
                            <i class="fa-solid fa-envelope"></i>

                            <a href="{{ $footerEmailUrl }}">
                                {{ $footerEmail }}
                            </a>
                        </li>
                    @endif

                    @if($footerPhone)
                        <li>
                            <i class="fa-solid fa-phone"></i>

                            <a href="{{ $footerPhoneUrl }}">
                                {{ $footerPhone }}
                            </a>
                        </li>
                    @endif

                    @if($footerWhatsappUrl)
                        <li>
                            <i class="fa-brands fa-whatsapp"></i>

                            <a href="{{ $footerWhatsappUrl }}" target="_blank" rel="noopener noreferrer">
                                {{ $footerWhatsapp }}
                            </a>
                        </li>
                    @endif

                    @if($footerAddress)
                        <li>
                            <i class="fa-solid fa-location-dot"></i>
                            <span>{{ $footerAddress }}</span>
                        </li>
                    @endif

                    @if($footerWorkingHours)
                        <li>
                            <i class="fa-solid fa-clock"></i>
                            <span>{{ $footerWorkingHours }}</span>
                        </li>
                    @endif
                </ul>
            </div>
        </div>

        <div class="etzan-footer-bottom">
            <p>
                © {{ date('Y') }} {{ setting('site_name', 'اتزان') }}. جميع الحقوق محفوظة.
            </p>

            <div class="etzan-footer-bottom-links">
                <a href="{{ route('privacy') }}">سياسة الخصوصية</a>
                <a href="{{ route('terms') }}">الشروط والأحكام</a>
            </div>
        </div>

    </div>
</footer>
    <!-- Bootstrap JS مرة واحدة فقط -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- JS مشترك للهيدر والفوتر -->
    <script src="{{ asset('front/js/shared.js') }}"></script>

    @stack('scripts')
</body>
</html>
