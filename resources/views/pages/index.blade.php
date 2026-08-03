@extends('layouts.public')

@section('title', 'الصفحة الرئيسية | ' . setting('site_name', 'اتزان'))

@push('styles')
<link rel="stylesheet" href="{{ asset('front/css/index.css') }}">
@endpush

@section('content')

@php
    $imageUrl = function ($path, $fallback) {
        if (!$path) {
            return asset($fallback);
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, '/')) {
            return $path;
        }

        if (str_starts_with($path, 'storage/')) {
            return asset($path);
        }

        if (str_starts_with($path, 'front/') || str_starts_with($path, 'assets/')) {
            return asset($path);
        }

        return asset('storage/' . $path);
    };

    $getAny = function ($item, array $keys, $default = '') {
        foreach ($keys as $key) {
            $value = data_get($item, $key);

            if (!blank($value)) {
                return $value;
            }
        }

        return $default;
    };

    $doctorLimit = (int) setting('home_doctors_limit', 4);
    $articleLimit = (int) setting('home_articles_limit', 4);

    $homepageDoctors = collect($doctors ?? [])->take($doctorLimit);
    $homepageArticles = collect($articles ?? [])->take($articleLimit);

    $doctorUrl = function ($doctor) use ($getAny) {
        $customUrl = $getAny($doctor, ['url', 'profile_url']);

        if ($customUrl) {
            return $customUrl;
        }

        try {
            if (\Illuminate\Support\Facades\Route::has('doctors.show')) {
                return route('doctors.show', $getAny($doctor, ['slug', 'id']));
            }
        } catch (\Throwable $e) {
            return '#';
        }

        return '#';
    };

    $doctorBookingUrl = function ($doctor) use ($getAny) {
        $customUrl = $getAny($doctor, ['booking_url', 'consultation_url']);

        if ($customUrl) {
            return $customUrl;
        }

        try {
            if (\Illuminate\Support\Facades\Route::has('appointments.create')) {
                return route('appointments.create', $getAny($doctor, ['id']));
            }
        } catch (\Throwable $e) {
            return '#';
        }

        return '#';
    };

    $doctorPhotoPath = function ($doctor) use ($getAny) {
        return $getAny($doctor, [
            'doctorProfile.photo_path',
            'doctor_profile.photo_path',
            'image',
            'photo',
            'avatar',
            'profile_photo',
            'profile_image',
            'doctor_profile.image',
            'doctorProfile.image',
            'profile.image',
        ]);
    };

    $doctorImage = function ($doctor) use ($doctorPhotoPath, $imageUrl) {
        $path = $doctorPhotoPath($doctor);

        return $path ? $imageUrl($path, '') : null;
    };

    $articleUrl = function ($article) use ($getAny) {
        $customUrl = $getAny($article, ['url', 'link']);

        if ($customUrl) {
            return $customUrl;
        }

        try {
            if (\Illuminate\Support\Facades\Route::has('articles.show')) {
                return route('articles.show', $getAny($article, ['slug', 'id']));
            }
        } catch (\Throwable $e) {
            return '#';
        }

        return '#';
    };

    $articleImage = function ($article, $index) use ($getAny, $imageUrl) {
        return $imageUrl(
            $getAny($article, [
                'cover_image',
                'image',
                'cover',
                'thumbnail',
                'photo',
                'main_image',
                'featured_image',
            ]),
            'front/image/articles/article-' . $index . '.jpg'
        );
    };

    $articleCategoryKey = function ($article) use ($getAny) {
        return $getAny($article, [
            'category.slug',
            'category.key',
            'category_key',
            'category',
            'type',
        ], 'general');
    };

    $articleCategoryLabel = function ($article) use ($getAny) {
        return $getAny($article, [
            'category.name',
            'category.title',
            'category.label',
            'label',
            'category_name',
        ], 'عام');
    };

    $articleCategories = $homepageArticles
        ->map(fn ($article) => [
            'key' => $articleCategoryKey($article),
            'label' => $articleCategoryLabel($article),
        ])
        ->unique('key')
        ->values();

    $howSteps = [
        1 => [
            'icon' => 'fa-regular fa-user',
            'title' => setting('home_how_step_1_title', 'أنشئ حسابك'),
            'description' => setting('home_how_step_1_description', 'ابدأ بتسجيل بياناتك الأساسية خلال لحظات.'),
        ],
        2 => [
            'icon' => 'fa-solid fa-heart-pulse',
            'title' => setting('home_how_step_2_title', 'أدخل معلوماتك الصحية'),
            'description' => setting('home_how_step_2_description', 'أدخل وزنك وطولك وهدفك الغذائي والصحي.'),
        ],
        3 => [
            'icon' => 'fa-regular fa-clipboard',
            'title' => setting('home_how_step_3_title', 'اختر المسار المناسب'),
            'description' => setting('home_how_step_3_description', 'احصل على التوصيات أو الخطة الأنسب لك.'),
        ],
        4 => [
            'icon' => 'fa-solid fa-chart-line',
            'title' => setting('home_how_step_4_title', 'تابع تقدمك'),
            'description' => setting('home_how_step_4_description', 'راقب تطورك واستمر في رحلتك بثقة.'),
        ],
    ];

    $services = [
        1 => ['icon' => 'fa-solid fa-utensils', 'class' => 'service-card-a'],
        2 => ['icon' => 'fa-solid fa-heart-pulse', 'class' => 'service-card-b'],
        3 => ['icon' => 'fa-solid fa-user-doctor', 'class' => 'service-card-c'],
        4 => ['icon' => 'fa-solid fa-fire', 'class' => 'service-card-d'],
        5 => ['icon' => 'fa-solid fa-chart-column', 'class' => 'service-card-e'],
        6 => ['icon' => 'fa-solid fa-book-open-reader', 'class' => 'service-card-f'],
    ];

    $serviceDefaults = [
        1 => ['خدمة أساسية', 'توصيات غذائية مخصصة', 'احصل على اقتراحات غذائية تناسب حالتك الصحية واحتياجاتك اليومية بشكل أكثر دقة ووضوح، مع تجربة سهلة ومريحة تدعم أهدافك الصحية.'],
        2 => ['', 'متابعة صحية مستمرة', 'تابع حالتك الصحية وتقدّمك بشكل منتظم من خلال أدوات عرض سهلة ومريحة.'],
        3 => ['دعم موثوق', 'استشارات مع مختصين', 'تواصل مع مختصي التغذية للحصول على دعم موثوق وإرشاد مناسب لك.'],
        4 => ['', 'حساب الاحتياج اليومي', 'اعرف السعرات والاحتياجات الغذائية اليومية بطريقة سهلة وواضحة.'],
        5 => ['عرض بصري', 'تقارير ورسوم توضيحية', 'استعرض تقدمك من خلال تقارير ورسوم مبسطة تساعدك على فهم نتائجك بسرعة واتخاذ قرارات صحية أوضح.'],
        6 => ['', 'محتوى توعوي موثوق', 'اقرأ مقالات ونصائح صحية وغذائية مكتوبة بلغة مبسطة وسهلة الفهم.'],
    ];

    $faqs = [
        1 => ['بدء الاستخدام', 'هل أحتاج إلى إنشاء حساب للاستفادة من اتزان؟', 'اعرف ما الذي يمكنك استخدامه قبل التسجيل وبعده.', 'يمكنك تصفح الصفحات العامة مثل المقالات والمعلومات التوعوية دون تسجيل دخول، لكن للوصول إلى الخدمات الشخصية مثل المتابعة أو التوصيات المخصصة أو لوحة المريض، ستحتاج إلى إنشاء حساب.', 'المقالات متاحة للجميع', 'الخدمات الشخصية تحتاج حساب', 'البدء سهل وسريع'],
        2 => ['المقالات', 'هل يمكنني تصفح المقالات دون تسجيل دخول؟', 'المحتوى التوعوي متاح للزوار بشكل عام.', 'نعم، يمكنك قراءة المقالات والمحتوى التوعوي بشكل عام دون الحاجة إلى تسجيل الدخول، لأن هدف اتزان هو إتاحة المعرفة الصحية والغذائية بشكل مبسط للجميع.', 'الوصول للمحتوى العام متاح', 'بدون حساب', 'معرفة صحية مبسطة'],
        3 => ['الخصوصية', 'هل تبقى بياناتي الصحية محفوظة وآمنة؟', 'خصوصيتك عنصر أساسي في تجربة اتزان.', 'نعم، نهتم بخصوصية البيانات الصحية ونحرص على أن تكون المعلومات الشخصية ضمن بيئة آمنة وواضحة الصلاحيات.', 'خصوصية أعلى', 'وصول منظم', 'بيئة آمنة'],
        4 => ['الاستشارات', 'هل يمكنني التواصل مع مختص تغذية؟', 'يمكنك التوجه للمختص عندما تحتاج دعمًا أدق.', 'نعم، يتيح لك اتزان الوصول إلى الأطباء أو المختصين وفق آلية المنصة، بحيث تتمكن من الحصول على توجيه أوضح عند الحاجة إلى استشارة غذائية أو متابعة أدق.', 'مختصون موثوقون', 'دعم أوضح', 'خطوة مناسبة عند الحاجة'],
        5 => ['الحالات الصحية', 'هل المنصة مناسبة للحالات المزمنة؟', 'تعرف متى يكون المحتوى وحده كافيًا ومتى تحتاج مختصًا.', 'يمكن أن تكون اتزان مفيدة للمستخدمين الذين يرغبون في فهم احتياجهم الغذائي بشكل أفضل، لكن الحالات المزمنة قد تستفيد أكثر عند الجمع بين المنصة واستشارة مختص للحصول على توجيه أدق.', 'تفيد في التوعية والمتابعة', 'مناسبة كبداية منظمة', 'الاستشارة تضيف دقة أكبر'],
    ];
@endphp

{{-- Hero --}}
@if(setting('home_hero_enabled', '1') === '1')
<section class="hero-section" id="hero">
    <div class="hero-shape"></div>

    <div class="container position-relative">
        <div class="row align-items-center gy-5">

            <div class="col-lg-6">
                <div class="hero-content">
                    <span class="hero-badge">
                        {{ setting('home_hero_badge', 'منصة صحية وغذائية متكاملة') }}
                    </span>

                    <h1 class="hero-title">
                        {{ setting('home_hero_title', 'توازن في غذائك..') }}<br>
                        <span>{{ setting('home_hero_highlight', 'استقرار في صحتك') }}</span>
                    </h1>

                    <p class="hero-text">
                        {{ setting('home_hero_description', 'منصة اتزان تربطك بخبراء التغذية وتساعدك على متابعة حالتك الصحية، واكتشاف توصيات غذائية مناسبة، وبناء نمط حياة أكثر وعيًا وتوازنًا.') }}
                    </p>

                    <div class="hero-buttons">
                        <a href="{{ setting('home_hero_primary_btn_url', route('register')) }}" class="hero-btn hero-btn-primary">
                            {{ setting('home_hero_primary_btn_text', 'ابدأ رحلتك الآن') }}
                        </a>

                        <a href="{{ setting('home_hero_secondary_btn_url', '#doctors') }}" class="hero-btn hero-btn-outline">
                            {{ setting('home_hero_secondary_btn_text', 'تعرّف على الأطباء') }}
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="hero-visual">
                    <div class="hero-frame">

                        <div class="floating-card fc-1">
                            {{ setting('home_hero_card_1', 'خطة غذائية مناسبة') }}
                        </div>

                        <div class="floating-card fc-2">
                            {{ setting('home_hero_card_2', 'محتوى توعوي موثوق') }}
                        </div>

                        <div class="floating-card fc-3">
                            {{ setting('home_hero_card_3', 'متابعة صحية مستمرة') }}
                        </div>

                        @php
                            $heroSlide1 = setting('home_hero_slide_1')
                                ? asset('storage/' . setting('home_hero_slide_1'))
                                : asset('front/image/hero1.jpeg');

                            $heroSlide2 = setting('home_hero_slide_2')
                                ? asset('storage/' . setting('home_hero_slide_2'))
                                : asset('front/image/hero2.jpeg');

                            $heroSlide3 = setting('home_hero_slide_3')
                                ? asset('storage/' . setting('home_hero_slide_3'))
                                : asset('front/image/hero3.jpeg');
                        @endphp

                        <div class="hero-slider">
                            <img src="{{ $heroSlide1 }}" class="hero-slide active" alt="صورة البداية الأولى">
                            <img src="{{ $heroSlide2 }}" class="hero-slide" alt="صورة البداية الثانية">
                            <img src="{{ $heroSlide3 }}" class="hero-slide" alt="صورة البداية الثالثة">
                        </div>

                        <div class="slider-controls">
                            <button class="slider-arrow prev" type="button">&#10095;</button>

                            <div class="slider-dots">
                                <span class="slider-dot active" data-index="0"></span>
                                <span class="slider-dot" data-index="1"></span>
                                <span class="slider-dot" data-index="2"></span>
                            </div>

                            <button class="slider-arrow next" type="button">&#10094;</button>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>
</section>
@endif

{{-- كيف يعمل النظام --}}
@if(setting('home_how_enabled', '1') === '1')
<section class="how-it-works" id="how-it-works">
    <div class="container">
        <div class="section-heading">
            <span class="section-badge">
                {{ setting('home_how_badge', 'رحلتك مع اتزان') }}
            </span>

            <h2>
                {{ setting('home_how_title', 'كيف يعمل') }}
                <span>{{ setting('home_how_highlight', 'اتزان') }}</span>؟
            </h2>

            <p>
                {{ setting('home_how_description', 'خطوات بسيطة ومنظمة تبدأ من إنشاء الحساب، وتنتهي بمتابعة تقدمك الصحي والغذائي بسهولة ووضوح.') }}
            </p>
        </div>

        <div class="how-it-works-scene">
            <div class="scene-bg-shape shape-1"></div>
            <div class="scene-bg-shape shape-2"></div>
            <div class="scene-bg-shape shape-3"></div>

            <div class="steps-path path-1"></div>
            <div class="steps-path path-2"></div>
            <div class="steps-path path-3"></div>
            <div class="steps-path path-4"></div>

            <div class="how-doctor-character">
                <div class="how-doctor-glow"></div>
                <img src="{{ setting('home_how_image') ? asset('storage/' . setting('home_how_image')) : asset('front/image/who_Etsan.png') }}" alt="دكتورة اتزان">
            </div>

            <div class="floating-icon icon-1">
                <i class="fa-solid fa-heart-pulse"></i>
            </div>

            <div class="floating-icon icon-2">
                <i class="fa-solid fa-check"></i>
            </div>

            <div class="floating-icon icon-3">
                <i class="fa-solid fa-plus"></i>
            </div>

            <div class="floating-icon icon-4">
                <i class="fa-solid fa-chart-simple"></i>
            </div>

            @foreach($howSteps as $index => $step)
                <div class="step-card step-card-{{ $index }} {{ $index === 1 ? 'active' : '' }}">
                    <div class="step-number">{{ str_pad($index, 2, '0', STR_PAD_LEFT) }}</div>

                    <div class="step-icon">
                        <i class="{{ $step['icon'] }}"></i>
                    </div>

                    <div class="step-content">
                        <h3>{{ $step['title'] }}</h3>
                        <p>{{ $step['description'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- قسم ما يميزنا --}}
@if(setting('home_features_enabled', '1') === '1')
<section class="features-showcase-section" id="features">
    <div class="container">
        <div class="features-head text-center">
            <span class="features-badge">
                {{ setting('home_features_badge', 'ماذا يميزنا ؟') }}
            </span>

            <h2>
                {{ setting('home_features_title', 'اكتشف كيف نجعل رحلتك الصحية') }}
                <span>{{ setting('home_features_highlight', 'أبسط وأوضح') }}</span>
            </h2>

            <p>
                {{ setting('home_features_description', 'نقدم لك أدوات ذكية وتجربة مريحة تساعدك على فهم حالتك الصحية واتخاذ قرارات غذائية أفضل بطريقة منظمة وسهلة.') }}
            </p>
        </div>

        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <div class="feature-showcase-wrap feature-showcase-right">
                    <div class="feature-card-box" id="featureCard">
                        <div class="feature-card-accent"></div>

                        <div class="feature-content fade-in" id="featureContent">
                            <span class="feature-mini-label" id="featureMiniLabel">
                                {{ setting('home_feature_mini_label', 'ميزة ذكية') }}
                            </span>

                            <h3 id="featureTitle">
                                {{ setting('home_feature_title', 'نظام توصيات غذائية ذكي') }}
                            </h3>

                            <p id="featureDescription">
                                {{ setting('home_feature_description', 'يعتمد النظام على بياناتك الصحية مثل الوزن، الحالة المرضية، ومستوى النشاط لتقديم توصيات غذائية مناسبة تساعدك على اتخاذ قرارات أفضل في حياتك اليومية.') }}
                            </p>

                            <div class="feature-note" id="featureNote">
                                {{ setting('home_feature_note', 'مناسب للمستخدمين الذين يحتاجون إلى توجيه غذائي أوضح وأكثر تخصيصًا.') }}
                            </div>

                            <div class="feature-highlights">
                                <div class="feature-highlight-chip">
                                    <i class="fa-solid fa-check"></i>
                                    <span id="featureChip1">{{ setting('home_feature_chip_1', 'توصيات مخصصة') }}</span>
                                </div>

                                <div class="feature-highlight-chip">
                                    <i class="fa-solid fa-sparkles"></i>
                                    <span id="featureChip2">{{ setting('home_feature_chip_2', 'واجهة مبسطة') }}</span>
                                </div>

                                <div class="feature-highlight-chip">
                                    <i class="fa-solid fa-bolt"></i>
                                    <span id="featureChip3">{{ setting('home_feature_chip_3', 'نتائج أسرع') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="feature-controls">
                            <button class="feature-arrow prev-feature" type="button" aria-label="السابق">
                                <i class="fa-solid fa-arrow-right"></i>
                            </button>

                            <div class="feature-dots">
                                <span class="feature-dot active" data-index="0"></span>
                                <span class="feature-dot" data-index="1"></span>
                                <span class="feature-dot" data-index="2"></span>
                                <span class="feature-dot" data-index="3"></span>
                                <span class="feature-dot" data-index="4"></span>
                            </div>

                            <button class="feature-arrow next-feature" type="button" aria-label="التالي">
                                <i class="fa-solid fa-arrow-left"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="feature-doctor-showcase feature-doctor-left">
                    <div class="feature-doctor-glow"></div>

                    <div class="feature-doctor-card">
                        <div class="feature-doctor-ornament orb-1"></div>
                        <div class="feature-doctor-ornament orb-2"></div>

                        <div class="feature-floating-badge badge-1">
                            <i class="fa-solid fa-heart-pulse"></i>
                        </div>

                        <div class="feature-floating-badge badge-2">
                            <i class="fa-solid fa-shield-heart"></i>
                        </div>

                        <div class="feature-floating-badge badge-3">
                            <i class="fa-solid fa-chart-line"></i>
                        </div>

                        <div class="feature-doctor-character">
                            <img src="{{ setting('home_features_image') ? asset('storage/' . setting('home_features_image')) : asset('front/image/What_makes.png') }}" alt="دكتورة اتزان">
                        </div>

                        <div class="feature-doctor-bubble fade-in" id="featureDoctorBubble">
                            {{ setting('home_feature_bubble', 'نعرض لك الميزة الحالية بشكل واضح ومبسط لتفهم كيف تساعدك المنصة.') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endif

{{-- خدماتنا --}}
@if(setting('home_services_enabled', '1') === '1')
<section class="services-section" id="services">
    <div class="container">
        <div class="services-head text-center">
            <span class="services-badge">
                {{ setting('home_services_badge', 'خدمات اتزان') }}
            </span>

            <h2>
                {{ setting('home_services_title', 'كل ما تحتاجه لرحلة صحية') }}
                <span>{{ setting('home_services_highlight', 'أكثر توازنًا') }}</span>
            </h2>

            <p>
                {{ setting('home_services_description', 'نوفر لك مجموعة من الخدمات الذكية التي تساعدك على متابعة صحتك، تحسين نمطك الغذائي، والوصول إلى قرارات صحية أوضح وأسهل.') }}
            </p>
        </div>

        <div class="services-layout">
            @foreach($services as $index => $service)
                <div class="service-card {{ $service['class'] }}">
                    <span class="service-shape"></span>

                    <div class="service-icon">
                        <i class="{{ $service['icon'] }}"></i>
                    </div>

                    @if(setting("home_service_{$index}_badge", $serviceDefaults[$index][0]))
                        <span class="service-mini-badge">
                            {{ setting("home_service_{$index}_badge", $serviceDefaults[$index][0]) }}
                        </span>
                    @endif

                    <h3>
                        {{ setting("home_service_{$index}_title", $serviceDefaults[$index][1]) }}
                    </h3>

                    <p>
                        {{ setting("home_service_{$index}_description", $serviceDefaults[$index][2]) }}
                    </p>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- الأطباء --}}
@if(setting('home_doctors_enabled', '1') === '1')
<section class="doctors-stack-section" id="doctors">
    <div class="container">
        <div class="doctors-stack-head text-center">
            <span class="doctors-stack-badge">
                {{ setting('home_doctors_badge', 'فريق اتزان') }}
            </span>

            <h2>
                {{ setting('home_doctors_title', 'خبراء يرافقون رحلتك نحو') }}
                <span>{{ setting('home_doctors_highlight', 'صحة أكثر توازنًا') }}</span>
            </h2>

            <p>
                {{ setting('home_doctors_description', 'نخبة من الأطباء والمختصين في التغذية والصحة لمساعدتك على اتخاذ قرارات صحية أوضح، والحصول على دعم موثوق يناسب احتياجك.') }}
            </p>
        </div>

        @if($homepageDoctors->isNotEmpty())
            @php
                $firstDoctor = $homepageDoctors->first();

                $firstDoctorName = $getAny($firstDoctor, ['name', 'full_name'], 'طبيب اتزان');
                $firstDoctorSpecialty = $getAny($firstDoctor, ['specialty', 'specialization', 'doctor_profile.specialty', 'doctorProfile.specialty'], 'مختص تغذية');
                $firstDoctorDescription = $getAny($firstDoctor, ['description', 'bio', 'about', 'doctor_profile.bio', 'doctorProfile.bio'], 'مختص يساعدك على فهم احتياجك الغذائي بشكل أوضح.');
                $firstDoctorExp = $getAny($firstDoctor, ['experience', 'experience_years', 'doctor_profile.experience'], 'خبرة موثوقة');
                $firstDoctorSessions = $getAny($firstDoctor, ['sessions', 'sessions_count', 'appointments_count'], 'جلسات متعددة');
                $firstDoctorStatus = $getAny($firstDoctor, ['availability', 'status_label', 'status'], 'متاح');
            @endphp

            <div class="doctors-stack-shell">
                <div class="doctors-stack-info-card" id="doctorInfoCard">
                    <span class="doctor-info-label">الملف النشط</span>

                    <h3 id="doctorInfoName">{{ $firstDoctorName }}</h3>

                    <div class="doctor-info-specialty" id="doctorInfoSpecialty">
                        {{ $firstDoctorSpecialty }}
                    </div>

                    <p id="doctorInfoDescription">
                        {{ $firstDoctorDescription }}
                    </p>

                    <div class="doctor-info-meta">
                        <div class="doctor-info-meta-item">
                            <i class="fa-solid fa-briefcase-medical"></i>
                            <span id="doctorInfoExp">{{ $firstDoctorExp }}</span>
                        </div>

                        <div class="doctor-info-meta-item">
                            <i class="fa-solid fa-users"></i>
                            <span id="doctorInfoSessions">{{ $firstDoctorSessions }}</span>
                        </div>

                        <div class="doctor-info-meta-item">
                            <i class="fa-solid fa-circle-check"></i>
                            <span id="doctorInfoStatus">{{ $firstDoctorStatus }}</span>
                        </div>
                    </div>

                    <div class="doctor-info-tags">
                        <span class="doctor-info-tag" id="doctorInfoTag1">
                            {{ $getAny($firstDoctor, ['tag_1', 'doctor_profile.tag_1'], 'تغذية') }}
                        </span>
                        <span class="doctor-info-tag" id="doctorInfoTag2">
                            {{ $getAny($firstDoctor, ['tag_2', 'doctor_profile.tag_2'], 'متابعة') }}
                        </span>
                        <span class="doctor-info-tag" id="doctorInfoTag3">
                            {{ $getAny($firstDoctor, ['tag_3', 'doctor_profile.tag_3'], 'استشارة') }}
                        </span>
                    </div>

                    <div class="doctor-info-actions">
                        <a href="{{ $doctorBookingUrl($firstDoctor) }}" class="doctor-main-btn doctor-main-btn-primary" id="doctorInfoBookingBtn">
                            احجز استشارة
                        </a>

                        <a href="{{ $doctorUrl($firstDoctor) }}" class="doctor-main-btn doctor-main-btn-outline" id="doctorInfoProfileBtn">
                            عرض الملف
                        </a>
                    </div>

                    @if(setting('home_doctor_join_enabled', '1') === '1')
                        <div class="doctor-join-box">
                            <div class="doctor-join-icon">
                                <i class="fa-solid fa-user-plus"></i>
                            </div>

                            <div class="doctor-join-content">
                                <h4>{{ setting('home_doctor_join_title', 'هل أنت طبيب أو مختص تغذية؟') }}</h4>

                                <p>
                                    {{ setting('home_doctor_join_description', 'انضم إلى شبكة اتزان الطبية وساهم في تقديم تجربة صحية أكثر وعيًا واحترافية للمستخدمين.') }}
                                </p>

                                <a href="{{ setting('home_doctor_join_btn_url', route('join-doctor')) }}" class="doctor-join-btn">
                                    {{ setting('home_doctor_join_btn_text', 'انضم إلى فريق اتزان الطبي') }}
                                </a>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="doctors-stack-stage">
                    <div class="doctors-stage-glow glow-1"></div>
                    <div class="doctors-stage-glow glow-2"></div>

                    <div class="doctors-stack-controls">
                        <button class="doctor-stack-arrow doctor-stack-prev" type="button" aria-label="السابق">
                            <i class="fa-solid fa-arrow-right"></i>
                        </button>

                        <button class="doctor-stack-arrow doctor-stack-next" type="button" aria-label="التالي">
                            <i class="fa-solid fa-arrow-left"></i>
                        </button>
                    </div>

                    <div class="doctors-stack-wrap">
                        @foreach($homepageDoctors as $index => $doctor)
                            @php
                                $doctorName = $getAny($doctor, ['name', 'full_name'], 'طبيب اتزان');
                                $doctorSpecialty = $getAny($doctor, ['specialty', 'specialization', 'doctor_profile.specialty', 'doctorProfile.specialty'], 'مختص تغذية');
                                $doctorDescription = $getAny($doctor, ['description', 'bio', 'about', 'doctor_profile.bio', 'doctorProfile.bio'], 'مختص يساعدك على فهم احتياجك الغذائي بشكل أوضح.');
                                $doctorExp = $getAny($doctor, ['experience', 'experience_years', 'doctor_profile.experience'], 'خبرة موثوقة');
                                $doctorSessions = $getAny($doctor, ['sessions', 'sessions_count', 'appointments_count'], 'جلسات متعددة');
                                $doctorStatus = $getAny($doctor, ['availability', 'status_label', 'status'], 'متاح');
                                $doctorTag1 = $getAny($doctor, ['tag_1', 'doctor_profile.tag_1'], 'تغذية');
                                $doctorTag2 = $getAny($doctor, ['tag_2', 'doctor_profile.tag_2'], 'متابعة');
                                $doctorTag3 = $getAny($doctor, ['tag_3', 'doctor_profile.tag_3'], 'استشارة');
                            @endphp

                            <article
                                class="doctor-stack-card {{ $loop->first ? 'active' : '' }}"
                                data-name="{{ $doctorName }}"
                                data-specialty="{{ $doctorSpecialty }}"
                                data-description="{{ $doctorDescription }}"
                                data-exp="{{ $doctorExp }}"
                                data-sessions="{{ $doctorSessions }}"
                                data-status="{{ $doctorStatus }}"
                                data-tag1="{{ $doctorTag1 }}"
                                data-tag2="{{ $doctorTag2 }}"
                                data-tag3="{{ $doctorTag3 }}"
                                data-booking-url="{{ $doctorBookingUrl($doctor) }}"
                                data-profile-url="{{ $doctorUrl($doctor) }}"
                            >
                                <span class="doctor-card-status">{{ $doctorStatus }}</span>

                                <div class="doctor-card-image">
                                    @if ($doctorImage($doctor))
                                        <img src="{{ $doctorImage($doctor) }}" alt="{{ $doctorName }}">
                                    @else
                                        <span class="doctor-card-image__placeholder" aria-hidden="true">
                                            <i class="fa-solid fa-user-doctor"></i>
                                        </span>
                                    @endif
                                </div>

                                <div class="doctor-card-body">
                                    <h4>{{ $doctorName }}</h4>
                                    <p>{{ $doctorSpecialty }}</p>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div class="doctor-stack-dots" id="doctorStackDots"></div>
                </div>
            </div>
        @else
            <div class="doctors-stack-shell">
                <div class="doctors-stack-info-card">
                    <span class="doctor-info-label">الأطباء</span>
                    <h3>لا يوجد أطباء للعرض حاليًا</h3>
                    <p>بعد اعتماد الأطباء من لوحة التحكم سيظهرون تلقائيًا في هذا القسم.</p>

                    @if(setting('home_doctor_join_enabled', '1') === '1')
                        <div class="doctor-join-box">
                            <div class="doctor-join-icon">
                                <i class="fa-solid fa-user-plus"></i>
                            </div>

                            <div class="doctor-join-content">
                                <h4>{{ setting('home_doctor_join_title', 'هل أنت طبيب أو مختص تغذية؟') }}</h4>
                                <p>{{ setting('home_doctor_join_description', 'انضم إلى شبكة اتزان الطبية وساهم في تقديم تجربة صحية أكثر وعيًا واحترافية للمستخدمين.') }}</p>
                                <a href="{{ setting('home_doctor_join_btn_url', route('join-doctor')) }}" class="doctor-join-btn">
                                    {{ setting('home_doctor_join_btn_text', 'انضم إلى فريق اتزان الطبي') }}
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</section>
@endif

{{-- المقالات التوعوية --}}
@if(setting('home_articles_enabled', '1') === '1')
<section class="etzan-articles-section" id="articles">
    <div class="container">
        <div class="etzan-articles-head text-center">
            <span class="etzan-articles-badge">
                {{ setting('home_articles_badge', 'المعرفة الصحية') }}
            </span>

            <h2>
                {{ setting('home_articles_title', 'محتوى توعوي يساعدك على اتخاذ') }}
                <span>{{ setting('home_articles_highlight', 'قرارات أفضل') }}</span>
            </h2>

            <p>
                {{ setting('home_articles_description', 'اكتشف مقالات صحية وغذائية مبسطة وموثوقة تساعدك على فهم حالتك الصحية وتبني عادات يومية أكثر وعيًا وتوازنًا.') }}
            </p>
        </div>

        @if($homepageArticles->isNotEmpty())
            <div class="etzan-articles-filters">
                <button class="etzan-filter-btn active" data-filter="all">الكل</button>

                @foreach($articleCategories as $category)
                    <button class="etzan-filter-btn" data-filter="{{ $category['key'] }}">
                        {{ $category['label'] }}
                    </button>
                @endforeach
            </div>

            @php
                $featuredArticle = $homepageArticles->first();
            @endphp

            <div class="etzan-articles-shell">
                @if(setting('home_articles_featured_enabled', '1') === '1')
                    <div class="etzan-featured-article" id="etzanFeaturedArticle">
                        <div class="etzan-featured-image-wrap">
                            <span class="etzan-featured-category" id="featuredCategory">
                                {{ $articleCategoryLabel($featuredArticle) }}
                            </span>

                            <img
                                id="featuredImage"
                                src="{{ $articleImage($featuredArticle, 1) }}"
                                alt="{{ $getAny($featuredArticle, ['title', 'name'], 'مقال توعوي') }}"
                            >
                        </div>

                        <div class="etzan-featured-content">
                            <div class="etzan-featured-meta">
                                <span id="featuredReadingTime">
                                    {{ $getAny($featuredArticle, ['reading_time', 'reading', 'duration'], '5 دقائق قراءة') }}
                                </span>

                                <span id="featuredDate">
                                    {{ $getAny($featuredArticle, ['published_at', 'date', 'created_at'], '') }}
                                </span>
                            </div>

                            <h3 id="featuredTitle">
                                {{ $getAny($featuredArticle, ['title', 'name'], 'مقال توعوي') }}
                            </h3>

                            <p id="featuredDescription">
                                {{ $getAny($featuredArticle, ['description', 'summary', 'excerpt', 'short'], 'مقال صحي وغذائي مبسط يساعدك على فهم حالتك بشكل أوضح.') }}
                            </p>

                            <a href="{{ $articleUrl($featuredArticle) }}" class="etzan-featured-link">
                                اقرأ المقال
                                <i class="fa-solid fa-arrow-left"></i>
                            </a>
                        </div>
                    </div>
                @endif

                <div class="etzan-articles-side-list">
                    @foreach($homepageArticles as $index => $article)
                        @php
                            $articleIndex = $index + 1;
                            $articleTitle = $getAny($article, ['title', 'name'], 'مقال توعوي');
                            $articleShort = $getAny($article, ['short', 'summary', 'excerpt', 'description'], 'محتوى توعوي مبسط يساعدك على اتخاذ قرارات صحية أوضح.');
                            $articleDescription = $getAny($article, ['description', 'summary', 'excerpt'], $articleShort);
                            $articleReading = $getAny($article, ['reading_time', 'reading', 'duration'], '5 دقائق قراءة');
                            $articleDate = $getAny($article, ['published_at', 'date', 'created_at'], '');
                            $articleLabel = $articleCategoryLabel($article);
                            $articleKey = $articleCategoryKey($article);
                            $articleImg = $articleImage($article, $articleIndex);
                        @endphp

                        <article
                            class="etzan-article-card {{ $loop->first ? 'active' : '' }}"
                            data-category="{{ $articleKey }}"
                            data-title="{{ $articleTitle }}"
                            data-description="{{ $articleDescription }}"
                            data-image="{{ $articleImg }}"
                            data-reading="{{ $articleReading }}"
                            data-date="{{ $articleDate }}"
                            data-label="{{ $articleLabel }}"
                        >
                            <div class="etzan-article-thumb">
                                <img src="{{ $articleImg }}" alt="{{ $articleTitle }}">
                            </div>

                            <div class="etzan-article-info">
                                <span class="etzan-article-mini-cat">{{ $articleLabel }}</span>
                                <h4>{{ $articleTitle }}</h4>
                                <p>{{ $articleShort }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        @else
            <div class="etzan-articles-shell">
                <div class="etzan-featured-article">
                    <div class="etzan-featured-content">
                        <div class="etzan-featured-meta">
                            <span>المقالات</span>
                        </div>

                        <h3>لا توجد مقالات منشورة حاليًا</h3>

                        <p>
                            بعد إضافة المقالات ونشرها من لوحة التحكم ستظهر تلقائيًا في هذا القسم.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <div class="etzan-articles-footer">
            <a href="{{ setting('home_articles_btn_url', route('articles')) }}" class="etzan-articles-btn">
                {{ setting('home_articles_btn_text', 'تصفح جميع المقالات') }}
                <i class="fa-solid fa-arrow-left"></i>
            </a>
        </div>
    </div>
</section>
@endif

{{-- الكويز --}}
@php
    $quizEnabled = setting('home_quiz_enabled', '1') === '1';

    $quizBadge = setting('home_quiz_badge', 'التقييم السريع');
    $quizTitle = setting('home_quiz_title', 'اكتشفي البداية الأنسب');
    $quizHighlight = setting('home_quiz_highlight', 'لرحلتك الغذائية');
    $quizDescription = setting('home_quiz_description', 'جاوبي على أسئلة قصيرة بطريقة تفاعلية، واتزان يقترح لك بداية مناسبة حسب هدفك وحالتك ونشاطك.');

    $quizBubble = setting('home_quiz_bubble_text', 'خلّينا نبدأ بخطوة بسيطة، وأنا أساعدك تعرفي الاتجاه الأنسب لك.');

    $quizImage = setting('home_quiz_image');
    $quizImageUrl = $quizImage ? asset('storage/' . $quizImage) : asset('front/image/quiz-doctor.png');

    $quizResultBadge = setting('home_quiz_result_badge', 'نتيجتك الأولية');
    $quizResultTitle = setting('home_quiz_result_title', 'بداية مناسبة حسب إجاباتك');
    $quizResultDescription = setting('home_quiz_result_description', 'إجاباتك تساعدنا نقترح لك بداية أوضح داخل اتزان.');

    $quizPrimaryText = setting('home_quiz_primary_btn_text', 'تعرّف على الأطباء');
    $quizPrimaryUrl = setting('home_quiz_primary_btn_url', '#doctors');

    $quizSecondaryText = setting('home_quiz_secondary_btn_text', 'استكشف المقالات');
    $quizSecondaryUrl = setting('home_quiz_secondary_btn_url', '#articles');
@endphp

@if($quizEnabled)
<section class="etzan-quiz-section etzan-quick-quiz" id="quiz">
    <div class="quiz-orb quiz-orb-1"></div>
    <div class="quiz-orb quiz-orb-2"></div>
    <div class="quiz-orb quiz-orb-3"></div>

    <div class="container">
        <div class="etzan-quiz-grid">
            <div class="etzan-quiz-content">
                <span class="etzan-quiz-badge">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                    {{ $quizBadge }}
                </span>

                <h2>
                    {{ $quizTitle }}
                    <span>{{ $quizHighlight }}</span>
                </h2>

                <p>
                    {{ $quizDescription }}
                </p>

                <div class="etzan-quiz-steps-preview">
                    <div class="quiz-preview-step is-active">
                        <strong>01</strong>
                        <span>الهدف</span>
                    </div>

                    <div class="quiz-preview-line"></div>

                    <div class="quiz-preview-step">
                        <strong>02</strong>
                        <span>الحالة</span>
                    </div>

                    <div class="quiz-preview-line"></div>

                    <div class="quiz-preview-step">
                        <strong>03</strong>
                        <span>النشاط</span>
                    </div>
                </div>

               <div class="etzan-quiz-actions">
                    <a href="{{ route('quiz.index') }}" class="etzan-quiz-start-btn">
                        ابدأ التقييم السريع
                        <i class="fa-solid fa-arrow-left"></i>
                    </a>

                    <a href="{{ $quizSecondaryUrl }}" class="etzan-quiz-soft-link">
                        {{ $quizSecondaryText }}
                    </a>
                </div>

                <div class="etzan-quiz-mini-benefits">
                    <span><i class="fa-solid fa-check"></i> سريع</span>
                    <span><i class="fa-solid fa-check"></i> تفاعلي</span>
                    <span><i class="fa-solid fa-check"></i> نتيجة فورية</span>
                </div>
            </div>

            <div class="etzan-quiz-visual">
                <div class="etzan-quiz-character-stage">
                    <div class="quiz-stage-glow"></div>

                    <div class="quiz-floating-pill pill-1">
                        <i class="fa-solid fa-apple-whole"></i>
                        <span>هدف غذائي</span>
                    </div>

                    <div class="quiz-floating-pill pill-2">
                        <i class="fa-solid fa-heart-pulse"></i>
                        <span>حالة صحية</span>
                    </div>

                    <div class="quiz-floating-pill pill-3">
                        <i class="fa-solid fa-person-walking"></i>
                        <span>نشاط يومي</span>
                    </div>

                    <div class="etzan-quiz-character-card">
                        <div class="quiz-character-top">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>

                        <div class="quiz-character-image">
                            <img src="{{ $quizImageUrl }}" alt="شخصية الكويز">
                        </div>

                        <div class="quiz-character-message">
                            <i class="fa-solid fa-comment-dots"></i>
                            <p>{{ $quizBubble }}</p>
                        </div>

                        <div class="quiz-character-result">
                            <small>{{ $quizResultBadge }}</small>
                            <strong>{{ $quizResultTitle }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="etzan-quiz-modal" id="homeQuizModal" aria-hidden="true">
        <div class="etzan-quiz-modal__overlay" id="closeHomeQuizOverlay"></div>

        <div class="etzan-quiz-modal__box">
            <button type="button" class="etzan-quiz-modal__close" id="closeHomeQuizBtn" aria-label="إغلاق">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <div class="etzan-quiz-modal__head">
                <span>تقييم اتزان السريع</span>
                <h3>جاوبي على 3 أسئلة فقط</h3>
                <p>وبناءً على إجاباتك نعرض لك بداية مناسبة.</p>
            </div>

            <div class="etzan-quiz-modal__progress">
                <span class="is-active"></span>
                <span></span>
                <span></span>
            </div>

            <div class="etzan-quiz-step is-active" data-step="1">
                <h4>ما هدفك الحالي؟</h4>

                <div class="etzan-quiz-options">
                    <button type="button" data-answer="loss">
                        <i class="fa-solid fa-arrow-trend-down"></i>
                        خسارة وزن
                    </button>

                    <button type="button" data-answer="gain">
                        <i class="fa-solid fa-arrow-trend-up"></i>
                        زيادة وزن
                    </button>

                    <button type="button" data-answer="balance">
                        <i class="fa-solid fa-scale-balanced"></i>
                        توازن غذائي
                    </button>

                    <button type="button" data-answer="health">
                        <i class="fa-solid fa-heart-pulse"></i>
                        تحسين الصحة
                    </button>
                </div>
            </div>

            <div class="etzan-quiz-step" data-step="2">
                <h4>هل لديك حالة صحية تحتاج اهتمام؟</h4>

                <div class="etzan-quiz-options">
                    <button type="button" data-answer="diabetes">
                        <i class="fa-solid fa-droplet"></i>
                        سكري
                    </button>

                    <button type="button" data-answer="pressure">
                        <i class="fa-solid fa-stethoscope"></i>
                        ضغط
                    </button>

                    <button type="button" data-answer="allergy">
                        <i class="fa-solid fa-wheat-awn-circle-exclamation"></i>
                        حساسية طعام
                    </button>

                    <button type="button" data-answer="none">
                        <i class="fa-solid fa-circle-check"></i>
                        لا يوجد
                    </button>
                </div>
            </div>

            <div class="etzan-quiz-step" data-step="3">
                <h4>ما مستوى نشاطك اليومي؟</h4>

                <div class="etzan-quiz-options">
                    <button type="button" data-answer="low">
                        <i class="fa-solid fa-couch"></i>
                        قليل
                    </button>

                    <button type="button" data-answer="medium">
                        <i class="fa-solid fa-person-walking"></i>
                        متوسط
                    </button>

                    <button type="button" data-answer="high">
                        <i class="fa-solid fa-person-running"></i>
                        عالي
                    </button>
                </div>
            </div>

            <div class="etzan-quiz-result" id="homeQuizResult">
                <span>{{ $quizResultBadge }}</span>
                <h4 id="homeQuizResultTitle">{{ $quizResultTitle }}</h4>
                <p id="homeQuizResultDescription">{{ $quizResultDescription }}</p>

                <div class="etzan-quiz-result-tags">
                    <small>خطة أوضح</small>
                    <small>توجيه مناسب</small>
                    <small>خطوة أولى</small>
                </div>

                <div class="etzan-quiz-result-actions">
                    <a href="{{ $quizPrimaryUrl }}" class="quiz-result-primary">
                        {{ $quizPrimaryText }}
                    </a>

                    <a href="{{ $quizSecondaryUrl }}" class="quiz-result-secondary">
                        {{ $quizSecondaryText }}
                    </a>
                </div>

                <button type="button" class="quiz-result-restart" id="restartHomeQuiz">
                    إعادة التقييم
                </button>
            </div>
        </div>
    </div>
</section>
@endif

{{-- الأسئلة الشائعة --}}
@if(setting('home_faq_enabled', '1') === '1')
<section class="etzan-faq-section" id="faq">
    <div class="container">
        <div class="etzan-faq-head text-center">
            <span class="etzan-faq-badge">
                {{ setting('home_faq_badge', 'الأسئلة الشائعة') }}
            </span>

            <h2>
                {{ setting('home_faq_title', 'كل ما تحتاج معرفته') }}
                <span>{{ setting('home_faq_highlight', 'قبل البدء') }}</span>
            </h2>

            <p>
                {{ setting('home_faq_description', 'جمعنا لك أهم الأسئلة التي قد تدور في بالك قبل إنشاء الحساب أو البدء باستخدام اتزان، حتى تكون الصورة أوضح وأسهل.') }}
            </p>
        </div>

        <div class="etzan-faq-shell">
            <div class="etzan-faq-answer-card" id="etzanFaqAnswerCard">
                <div class="etzan-faq-shape shape-1"></div>
                <div class="etzan-faq-shape shape-2"></div>

                <span class="etzan-faq-answer-badge" id="etzanFaqAnswerBadge">
                    {{ setting('home_faq_1_badge', $faqs[1][0]) }}
                </span>

                <h3 id="etzanFaqAnswerTitle">
                    {{ setting('home_faq_1_question', $faqs[1][1]) }}
                </h3>

                <p id="etzanFaqAnswerText">
                    {{ setting('home_faq_1_answer', $faqs[1][3]) }}
                </p>

                <div class="etzan-faq-answer-points" id="etzanFaqAnswerPoints">
                    <div class="etzan-faq-point">{{ setting('home_faq_1_point_1', $faqs[1][4]) }}</div>
                    <div class="etzan-faq-point">{{ setting('home_faq_1_point_2', $faqs[1][5]) }}</div>
                    <div class="etzan-faq-point">{{ setting('home_faq_1_point_3', $faqs[1][6]) }}</div>
                </div>

                <div class="etzan-faq-trust-box">
                    <div class="etzan-faq-trust-icon">
                        <i class="fa-solid fa-shield-heart"></i>
                    </div>
                    <div class="etzan-faq-trust-content">
                        <h4>{{ setting('home_faq_trust_title', 'إجابات واضحة وتجربة مطمئنة') }}</h4>
                        <p>
                            {{ setting('home_faq_trust_description', 'نحاول أن نجعل رحلتك في اتزان واضحة من أول خطوة، مع معلومات بسيطة وتجربة سهلة قبل التسجيل وبعده.') }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="etzan-faq-questions">
                @foreach($faqs as $index => $faq)
                    <button
                        class="etzan-faq-question {{ $index === 1 ? 'active' : '' }}"
                        type="button"
                        data-badge="{{ setting('home_faq_' . $index . '_badge', $faq[0]) }}"
                        data-title="{{ setting('home_faq_' . $index . '_question', $faq[1]) }}"
                        data-text="{{ setting('home_faq_' . $index . '_answer', $faq[3]) }}"
                        data-point1="{{ setting('home_faq_' . $index . '_point_1', $faq[4]) }}"
                        data-point2="{{ setting('home_faq_' . $index . '_point_2', $faq[5]) }}"
                        data-point3="{{ setting('home_faq_' . $index . '_point_3', $faq[6]) }}"
                    >
                        <span class="etzan-faq-question-number">
                            {{ str_pad($index, 2, '0', STR_PAD_LEFT) }}
                        </span>

                        <div class="etzan-faq-question-text">
                            <h4>{{ setting('home_faq_' . $index . '_question', $faq[1]) }}</h4>
                            <p>{{ setting('home_faq_' . $index . '_short', $faq[2]) }}</p>
                        </div>

                        <span class="etzan-faq-question-arrow">
                            <i class="fa-solid fa-arrow-left"></i>
                        </span>
                    </button>
                @endforeach
            </div>
        </div>
    </div>
</section>
@endif

{{-- CTA --}}
@if(setting('home_cta_enabled', '1') === '1')
<section class="etzan-cta-section" id="start-now">
    <div class="container">
        <div class="etzan-cta-box">
            <div class="etzan-cta-shape shape-1"></div>
            <div class="etzan-cta-shape shape-2"></div>

            <div class="etzan-cta-content">
                <span class="etzan-cta-badge">
                    {{ setting('home_cta_badge', 'ابدأ الآن') }}
                </span>

                <h2>
                    {{ setting('home_cta_title', 'جاهز تبدأ رحلتك مع') }}
                    <span>{{ setting('home_cta_highlight', 'اتزان') }}</span>؟
                </h2>

                <p>
                    {{ setting('home_cta_description', 'بعد ما تعرّفت على خدمات اتزان، واستعرضت الأطباء، واطلعت على المحتوى التوعوي، يمكنك الآن اختيار الخطوة المناسبة لك والبدء في رحلة صحية أكثر وعيًا وتوازنًا.') }}
                </p>

                <div class="etzan-cta-points">
                    <div class="etzan-cta-point">
                        <i class="fa-solid fa-check"></i>
                        <span>{{ setting('home_cta_point_1', 'توصيات مخصصة') }}</span>
                    </div>

                    <div class="etzan-cta-point">
                        <i class="fa-solid fa-user-doctor"></i>
                        <span>{{ setting('home_cta_point_2', 'دعم من مختصين') }}</span>
                    </div>

                    <div class="etzan-cta-point">
                        <i class="fa-solid fa-chart-line"></i>
                        <span>{{ setting('home_cta_point_3', 'متابعة مستمرة') }}</span>
                    </div>
                </div>

                <div class="etzan-cta-actions">
                    <a href="{{ setting('home_cta_primary_btn_url', route('register')) }}" class="etzan-cta-btn etzan-cta-btn-primary">
                        {{ setting('home_cta_primary_btn_text', 'ابدأ رحلتك الآن') }}
                    </a>

                    <a href="{{ setting('home_cta_secondary_btn_url', route('join-doctor')) }}" class="etzan-cta-btn etzan-cta-btn-outline">
                        {{ setting('home_cta_secondary_btn_text', 'انضم كطبيب') }}
                    </a>
                </div>
            </div>

            <div class="etzan-cta-visual">
                <div class="etzan-cta-visual-card main-card">
                    <div class="etzan-cta-card-icon">
                        <i class="fa-solid fa-heart-pulse"></i>
                    </div>

                    <h3>{{ setting('home_cta_card_title', 'خطتك تبدأ بخطوة واضحة') }}</h3>

                    <p>
                        {{ setting('home_cta_card_description', 'حدّد احتياجك، استكشف الحلول المناسبة، وابدأ رحلتك الصحية بطريقة أسهل وأكثر تنظيمًا.') }}
                    </p>
                </div>

                <div class="etzan-cta-floating-card floating-card-1">
                    <i class="fa-solid fa-sparkles"></i>
                    <span>{{ setting('home_cta_float_1', 'بداية سهلة') }}</span>
                </div>

                <div class="etzan-cta-floating-card floating-card-2">
                    <i class="fa-solid fa-shield-heart"></i>
                    <span>{{ setting('home_cta_float_2', 'خصوصية آمنة') }}</span>
                </div>

                <div class="etzan-cta-floating-card floating-card-3">
                    <i class="fa-solid fa-clipboard-check"></i>
                    <span>{{ setting('home_cta_float_3', 'خطوات واضحة') }}</span>
                </div>
            </div>
        </div>
    </div>
</section>
@endif

@endsection

@push('scripts')
<script src="{{ asset('front/js/index.js') }}"></script>

@endpush
