@extends('layouts.admin')

@section('title', 'إعدادات الموقع')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/admin/settings.css') }}?v={{ filemtime(public_path('front/css/admin/settings.css')) }}">
@endpush

@section('content')
@php
    $value = function ($key, $default = '') use ($settings) {
        return old($key, $settings[$key] ?? $default);
    };

    $checked = function ($key, $default = '1') use ($settings) {
        return old($key, $settings[$key] ?? $default) === '1';
    };

    $imagePreview = function ($key) use ($settings) {
        return !empty($settings[$key]) ? asset('storage/' . $settings[$key]) : null;
    };

    $homeSteps = [
        1 => ['أنشئ حسابك', 'ابدأ بتسجيل بياناتك الأساسية خلال لحظات.'],
        2 => ['أدخل معلوماتك الصحية', 'أدخل وزنك وطولك وهدفك الغذائي والصحي.'],
        3 => ['اختر المسار المناسب', 'احصل على التوصيات أو الخطة الأنسب لك.'],
        4 => ['تابع تقدمك', 'راقب تطورك واستمر في رحلتك بثقة.'],
    ];

    $homeServices = [
        1 => ['خدمة أساسية', 'توصيات غذائية مخصصة', 'احصل على اقتراحات غذائية تناسب حالتك الصحية واحتياجاتك اليومية بشكل أكثر دقة ووضوح.'],
        2 => ['', 'متابعة صحية مستمرة', 'تابع حالتك الصحية وتقدّمك بشكل منتظم من خلال أدوات عرض سهلة ومريحة.'],
        3 => ['دعم موثوق', 'استشارات مع مختصين', 'تواصل مع مختصي التغذية للحصول على دعم موثوق وإرشاد مناسب لك.'],
        4 => ['', 'حساب الاحتياج اليومي', 'اعرف السعرات والاحتياجات الغذائية اليومية بطريقة سهلة وواضحة.'],
        5 => ['عرض بصري', 'تقارير ورسوم توضيحية', 'استعرض تقدمك من خلال تقارير ورسوم مبسطة تساعدك على فهم نتائجك بسرعة.'],
        6 => ['', 'محتوى توعوي موثوق', 'اقرأ مقالات ونصائح صحية وغذائية مكتوبة بلغة مبسطة وسهلة الفهم.'],
    ];

    $homeFaqs = [
        1 => ['بدء الاستخدام', 'هل أحتاج إلى إنشاء حساب للاستفادة من اتزان؟', 'اعرف ما الذي يمكنك استخدامه قبل التسجيل وبعده.', 'يمكنك تصفح الصفحات العامة مثل المقالات والمعلومات التوعوية دون تسجيل دخول، لكن للوصول إلى الخدمات الشخصية مثل المتابعة أو التوصيات المخصصة أو لوحة المريض، ستحتاج إلى إنشاء حساب.', 'المقالات متاحة للجميع', 'الخدمات الشخصية تحتاج حساب', 'البدء سهل وسريع'],
        2 => ['المقالات', 'هل يمكنني تصفح المقالات دون تسجيل دخول؟', 'المحتوى التوعوي متاح للزوار بشكل عام.', 'نعم، يمكنك قراءة المقالات والمحتوى التوعوي بشكل عام دون الحاجة إلى تسجيل الدخول، لأن هدف اتزان هو إتاحة المعرفة الصحية والغذائية بشكل مبسط للجميع.', 'الوصول للمحتوى العام متاح', 'بدون حساب', 'معرفة صحية مبسطة'],
        3 => ['الخصوصية', 'هل تبقى بياناتي الصحية محفوظة وآمنة؟', 'خصوصيتك عنصر أساسي في تجربة اتزان.', 'نعم، نهتم بخصوصية البيانات الصحية ونحرص على أن تكون المعلومات الشخصية ضمن بيئة آمنة وواضحة الصلاحيات.', 'خصوصية أعلى', 'وصول منظم', 'بيئة آمنة'],
        4 => ['الاستشارات', 'هل يمكنني التواصل مع مختص تغذية؟', 'يمكنك التوجه للمختص عندما تحتاج دعمًا أدق.', 'نعم، يتيح لك اتزان الوصول إلى الأطباء أو المختصين وفق آلية المنصة، بحيث تتمكن من الحصول على توجيه أوضح عند الحاجة إلى استشارة غذائية أو متابعة أدق.', 'مختصون موثوقون', 'دعم أوضح', 'خطوة مناسبة عند الحاجة'],
        5 => ['الحالات الصحية', 'هل المنصة مناسبة للحالات المزمنة؟', 'تعرف متى يكون المحتوى وحده كافيًا ومتى تحتاج مختصًا.', 'يمكن أن تكون اتزان مفيدة للمستخدمين الذين يرغبون في فهم احتياجهم الغذائي بشكل أفضل، لكن الحالات المزمنة قد تستفيد أكثر عند الجمع بين المنصة واستشارة مختص للحصول على توجيه أدق.', 'تفيد في التوعية والمتابعة', 'مناسبة كبداية منظمة', 'الاستشارة تضيف دقة أكبر'],
    ];

    $enabledHomeSections = collect([
    'home_hero_enabled',
    'home_how_enabled',
    'home_features_enabled',
    'home_services_enabled',
    'home_doctors_enabled',
    'home_articles_enabled',
    'home_quiz_enabled',
    'home_faq_enabled',
    'home_cta_enabled',
])->filter(function ($key) use ($settings) {
    return ($settings[$key] ?? '1') === '1';
})->count();


    $quizRules = \App\Models\QuizRecommendationRule::with('article')
        ->orderBy('priority')
        ->orderBy('id')
        ->get();

    $quizArticles = class_exists(\App\Models\Article::class)
        ? \App\Models\Article::query()->orderBy('id', 'desc')->get()
        : collect();

    $quizContentTypes = [
        'recommendation' => 'توصية',
        'task' => 'مهمة يومية',
        'article' => 'مقال',
    ];

    $quizResultTypes = [
        '' => 'كل النتائج',
        'doctor' => 'يحتاج طبيب',
        'nutritionist' => 'أخصائي تغذية',
        'self_care' => 'بداية بسيطة',
    ];

    $quizGoals = [
        '' => 'أي هدف',
        'loss' => 'خسارة وزن',
        'gain' => 'زيادة وزن',
        'balance' => 'توازن غذائي',
        'health' => 'تحسين الصحة',
    ];

    $quizConditions = [
        '' => 'أي حالة',
        'none' => 'لا يوجد',
        'diabetes' => 'سكري',
        'pressure' => 'ضغط',
        'allergy' => 'حساسية طعام',
    ];

    $quizActivities = [
        '' => 'أي نشاط',
        'low' => 'قليل',
        'medium' => 'متوسط',
        'high' => 'عالي',
    ];

    $quizSymptoms = [
        '' => 'أي أعراض',
        'none' => 'لا يوجد',
        'dizziness' => 'دوخة أو تعب شديد',
        'weight_change' => 'تغير وزن غير مفسر',
        'pain' => 'ألم أو مشكلة مستمرة',
    ];

    $quizMedication = [
        '' => 'أي اختيار',
        'no' => 'لا يستخدم أدوية',
        'yes' => 'يستخدم أدوية',
    ];

@endphp

<div class="admin-settings-page">

    <div class="admin-page-head">
        <div>
            <span class="admin-page-badge">إدارة الموقع</span>
            <h1>إعدادات الموقع</h1>
            <p>اختر القسم الذي تريد تعديله، ثم احفظ التغييرات عند الانتهاء.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="admin-alert admin-alert-success">
            <i class="fa-solid fa-circle-check"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="admin-alert admin-alert-error">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span>راجع البيانات المدخلة ثم حاول مرة أخرى.</span>
        </div>
    @endif

    <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="settings-shell">

            <div class="settings-tabs settings-tabs-clean">

                <button type="button" class="settings-tab is-active" data-target="identity">
                    <span class="settings-tab-icon">
                        <i class="fa-solid fa-leaf"></i>
                    </span>
                    <span class="settings-tab-text">
                        <strong>هوية الموقع</strong>
                        <small>الاسم والشعار والوصف</small>
                    </span>
                </button>

                <button type="button" class="settings-tab" data-target="contact">
                    <span class="settings-tab-icon">
                        <i class="fa-solid fa-address-book"></i>
                    </span>
                    <span class="settings-tab-text">
                        <strong>معلومات التواصل</strong>
                        <small>البريد والهاتف والعنوان</small>
                    </span>
                </button>

                <button type="button" class="settings-tab" data-target="social">
                    <span class="settings-tab-icon">
                        <i class="fa-solid fa-share-nodes"></i>
                    </span>
                    <span class="settings-tab-text">
                        <strong>روابط التواصل</strong>
                        <small>حسابات الموقع الرسمية</small>
                    </span>
                </button>

                <button type="button" class="settings-tab" data-target="seo">
                    <span class="settings-tab-icon">
                        <i class="fa-solid fa-magnifying-glass-chart"></i>
                    </span>
                    <span class="settings-tab-text">
                        <strong>تحسين الظهور</strong>
                        <small>بيانات محركات البحث</small>
                    </span>
                </button>

                <button type="button" class="settings-tab" data-target="appearance">
                    <span class="settings-tab-icon">
                        <i class="fa-solid fa-palette"></i>
                    </span>
                    <span class="settings-tab-text">
                        <strong>المظهر العام</strong>
                        <small>الألوان والوضع الداكن</small>
                    </span>
                </button>

                <button type="button" class="settings-tab" data-target="maintenance">
                    <span class="settings-tab-icon">
                        <i class="fa-solid fa-screwdriver-wrench"></i>
                    </span>
                    <span class="settings-tab-text">
                        <strong>وضع الصيانة</strong>
                        <small>تعطيل الموقع مؤقتًا</small>
                    </span>
                </button>

                <button type="button" class="settings-tab" data-target="home">
                    <span class="settings-tab-icon">
                        <i class="fa-solid fa-house-chimney"></i>
                    </span>
                    <span class="settings-tab-text">
                        <strong>الصفحة الرئيسية</strong>
                        <small>تحكم بكل أقسام الواجهة</small>
                    </span>
                </button>

            </div>

            <div class="settings-content">

                {{-- هوية الموقع --}}
                <section class="settings-panel is-active" data-panel="identity">
                    <div class="settings-card-head">
                        <div class="settings-icon">
                            <i class="fa-solid fa-leaf"></i>
                        </div>
                        <div>
                            <h2>هوية الموقع</h2>
                            <p>عدّل الاسم، الوصف، والشعار المستخدم في الواجهة.</p>
                        </div>
                    </div>

                    <div class="settings-fields">
                        <div class="settings-field">
                            <label>اسم الموقع</label>
                            <input type="text" name="site_name" value="{{ $value('site_name', 'اتزان') }}" placeholder="مثال: اتزان">
                        </div>

                        <div class="settings-field settings-field-full">
                            <label>وصف الموقع</label>
                            <textarea name="site_description" rows="4" placeholder="اكتب وصفًا مختصرًا يوضح فكرة الموقع">{{ $value('site_description') }}</textarea>
                        </div>

                        <div class="settings-upload-row settings-field-full">
                            <div class="settings-field">
                                <label>شعار الموقع</label>
                                <input type="file" name="site_logo" accept="image/*">

                                @if($imagePreview('site_logo'))
                                    <div class="settings-preview">
                                        <img src="{{ $imagePreview('site_logo') }}" alt="شعار الموقع">
                                    </div>
                                @endif
                            </div>

                            <div class="settings-field">
                                <label>أيقونة الموقع</label>
                                <input type="file" name="site_favicon" accept="image/*,.ico">

                                @if($imagePreview('site_favicon'))
                                    <div class="settings-preview settings-preview-small">
                                        <img src="{{ $imagePreview('site_favicon') }}" alt="أيقونة الموقع">
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </section>

                {{-- معلومات التواصل --}}
                <section class="settings-panel" data-panel="contact">
                    <div class="settings-card-head">
                        <div class="settings-icon">
                            <i class="fa-solid fa-address-book"></i>
                        </div>
                        <div>
                            <h2>معلومات التواصل</h2>
                            <p>أضف بيانات التواصل التي تظهر للمستخدم عند الحاجة.</p>
                        </div>
                    </div>

                    <div class="settings-fields">
                        <div class="settings-field">
                            <label>البريد الإلكتروني</label>
                            <input type="email" name="contact_email" value="{{ $value('contact_email') }}" placeholder="info@example.com">
                        </div>

                        <div class="settings-field">
                            <label>رقم الهاتف</label>
                            <input type="text" name="contact_phone" value="{{ $value('contact_phone') }}" placeholder="مثال: 0590000000">
                        </div>

                        <div class="settings-field">
                            <label>رقم واتساب</label>
                            <input type="text" name="contact_whatsapp" value="{{ $value('contact_whatsapp') }}" placeholder="مثال: 0590000000">
                        </div>

                        <div class="settings-field">
                            <label>العنوان</label>
                            <input type="text" name="contact_address" value="{{ $value('contact_address') }}" placeholder="اكتب عنوانًا مختصرًا">
                        </div>
                    </div>
                </section>

                {{-- روابط التواصل --}}
                <section class="settings-panel" data-panel="social">
                    <div class="settings-card-head">
                        <div class="settings-icon">
                            <i class="fa-solid fa-share-nodes"></i>
                        </div>
                        <div>
                            <h2>روابط التواصل</h2>
                            <p>أضف الروابط الرسمية حتى يصل إليها الزائر بسهولة.</p>
                        </div>
                    </div>

                    <div class="settings-fields">
                        <div class="settings-field">
                            <label>فيسبوك</label>
                            <input type="url" name="facebook_url" value="{{ $value('facebook_url') }}" placeholder="https://facebook.com/...">
                        </div>

                        <div class="settings-field">
                            <label>إنستغرام</label>
                            <input type="url" name="instagram_url" value="{{ $value('instagram_url') }}" placeholder="https://instagram.com/...">
                        </div>

                        <div class="settings-field">
                            <label>منصة X</label>
                            <input type="url" name="x_url" value="{{ $value('x_url') }}" placeholder="https://x.com/...">
                        </div>

                        <div class="settings-field">
                            <label>يوتيوب</label>
                            <input type="url" name="youtube_url" value="{{ $value('youtube_url') }}" placeholder="https://youtube.com/...">
                        </div>
                    </div>
                </section>

                {{-- تحسين الظهور --}}
                <section class="settings-panel" data-panel="seo">
                    <div class="settings-card-head">
                        <div class="settings-icon">
                            <i class="fa-solid fa-magnifying-glass-chart"></i>
                        </div>
                        <div>
                            <h2>تحسين الظهور</h2>
                            <p>اضبط بيانات محركات البحث الخاصة بالموقع.</p>
                        </div>
                    </div>

                    <div class="settings-fields">
                        <div class="settings-field">
                            <label>عنوان SEO</label>
                            <input type="text" name="seo_title" value="{{ $value('seo_title') }}" placeholder="مثال: اتزان | منصة صحية وغذائية">
                        </div>

                        <div class="settings-field settings-field-full">
                            <label>وصف SEO</label>
                            <textarea name="seo_description" rows="4" placeholder="اكتب وصفًا مختصرًا يظهر في نتائج البحث">{{ $value('seo_description') }}</textarea>
                        </div>

                        <div class="settings-field settings-field-full">
                            <label>الكلمات المفتاحية</label>
                            <input type="text" name="seo_keywords" value="{{ $value('seo_keywords') }}" placeholder="تغذية، صحة، أطباء، توصيات غذائية">
                        </div>
                    </div>
                </section>

                {{-- المظهر العام --}}
               {{-- المظهر العام --}}
                <section class="settings-panel" data-panel="appearance">
                    <div class="settings-card-head">
                        <div class="settings-icon">
                            <i class="fa-solid fa-palette"></i>
                        </div>

                        <div>
                            <h2>المظهر العام</h2>
                            <p>اختاري شكل عرض الموقع: فاتح أو داكن فقط.</p>
                        </div>
                    </div>

                    <div class="settings-fields">
                        <div class="settings-field settings-field-full">
                            <label>وضع الواجهة</label>

                            <div class="appearance-mode-list">
                                <label class="appearance-mode-row">
                                    <input
                                        type="radio"
                                        name="theme_mode"
                                        value="light"
                                        @checked($value('theme_mode', 'light') === 'light')
                                    >

                                    <span class="appearance-mode-icon light">
                                        <i class="fa-solid fa-sun"></i>
                                    </span>

                                    <span class="appearance-mode-content">
                                        <strong>الوضع الفاتح</strong>
                                        <small>مظهر فاتح وناعم يناسب واجهة اتزان.</small>
                                    </span>
                                </label>

                                <label class="appearance-mode-row">
                                    <input
                                        type="radio"
                                        name="theme_mode"
                                        value="dark"
                                        @checked($value('theme_mode', 'light') === 'dark')
                                    >

                                    <span class="appearance-mode-icon dark">
                                        <i class="fa-solid fa-moon"></i>
                                    </span>

                                    <span class="appearance-mode-content">
                                        <strong>الوضع الداكن</strong>
                                        <small>مظهر داكن وهادئ بنفس ألوان الموقع.</small>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <label class="settings-switch settings-field-full">
                            <input type="checkbox" name="enable_animations" value="1" @checked($checked('enable_animations', '1'))>
                            <span></span>
                            <div>
                                <strong>تفعيل الحركة</strong>
                                <small>اعرض التأثيرات البصرية داخل الواجهة.</small>
                            </div>
                        </label>
                    </div>
                </section>

                {{-- وضع الصيانة --}}
                <section class="settings-panel" data-panel="maintenance">
                    <div class="settings-card-head">
                        <div class="settings-icon">
                            <i class="fa-solid fa-screwdriver-wrench"></i>
                        </div>
                        <div>
                            <h2>وضع الصيانة</h2>
                            <p>أوقف الموقع مؤقتًا عند تنفيذ تعديل مهم.</p>
                        </div>
                    </div>

                    <div class="settings-fields">
                        <label class="settings-switch settings-field-full">
                            <input type="checkbox" name="maintenance_mode" value="1" @checked($checked('maintenance_mode', '0'))>
                            <span></span>
                            <div>
                                <strong>فعّل وضع الصيانة</strong>
                                <small>سيظهر للزائر تنبيه بدل محتوى الموقع.</small>
                            </div>
                        </label>

                        <div class="settings-field settings-field-full">
                            <label>رسالة الصيانة</label>
                            <textarea name="maintenance_message" rows="4" placeholder="اكتب رسالة مختصرة تظهر للزائر">{{ $value('maintenance_message', 'الموقع قيد التحديث، حاول زيارته لاحقًا.') }}</textarea>
                        </div>
                    </div>
                </section>

                {{-- الصفحة الرئيسية --}}
                 <section class="settings-panel" data-panel="home">
                    <div class="settings-card-head">
                        <div class="settings-icon">
                            <i class="fa-solid fa-house-chimney"></i>
                        </div>

                        <div>
                            <h2>الصفحة الرئيسية</h2>
                            <p>تحكم بأقسام الصفحة الرئيسية من مكان واحد بطريقة مرتبة وواضحة.</p>
                        </div>
                    </div>

                    <div class="home-builder">
                        <div class="home-builder-intro">
                            <div>
                                <span class="home-builder-intro__tag">محرر الصفحة الرئيسية</span>
                                <h3>عدّل كل قسم بدون زحمة النماذج الطويلة</h3>
                                <p>
                                    اختر القسم الذي تريد تعديله من البطاقات التالية، وسيظهر لك محتواه فقط.
                                    هذا يجعل إدارة الصفحة الرئيسية أوضح وأسهل.
                                </p>
                            </div>

                            <div class="home-builder-summary">
                                <div class="home-builder-summary__item">
                                    <span>الأقسام المفعّلة</span>
                                    <strong>{{ $enabledHomeSections }} / 9</strong>
                                </div>

                                <div class="home-builder-summary__item">
                                    <span>الأطباء والمقالات</span>
                                    <strong>من قاعدة البيانات</strong>
                                </div>

                                <div class="home-builder-summary__item">
                                    <span>طريقة التعديل</span>
                                    <strong>قسم بقسم</strong>
                                </div>
                            </div>
                        </div>

                        <div class="home-section-tabs home-builder-tabs">
                            <button type="button" class="home-section-tab is-active" data-home-target="hero">
                                <span class="home-section-tab__icon">
                                    <i class="fa-solid fa-star"></i>
                                </span>
                                <span class="home-section-tab__content">
                                    <strong>Hero</strong>
                                    <small>أول قسم يراه الزائر</small>
                                </span>
                            </button>

                            <button type="button" class="home-section-tab" data-home-target="how">
                                <span class="home-section-tab__icon">
                                    <i class="fa-solid fa-route"></i>
                                </span>
                                <span class="home-section-tab__content">
                                    <strong>كيف يعمل</strong>
                                    <small>خطوات استخدام المنصة</small>
                                </span>
                            </button>

                            <button type="button" class="home-section-tab" data-home-target="features">
                                <span class="home-section-tab__icon">
                                    <i class="fa-solid fa-gem"></i>
                                </span>
                                <span class="home-section-tab__content">
                                    <strong>المميزات</strong>
                                    <small>ما يميز تجربة اتزان</small>
                                </span>
                            </button>

                            <button type="button" class="home-section-tab" data-home-target="services">
                                <span class="home-section-tab__icon">
                                    <i class="fa-solid fa-layer-group"></i>
                                </span>
                                <span class="home-section-tab__content">
                                    <strong>الخدمات</strong>
                                    <small>الخدمات الأساسية</small>
                                </span>
                            </button>

                            <button type="button" class="home-section-tab" data-home-target="doctors">
                                <span class="home-section-tab__icon">
                                    <i class="fa-solid fa-user-doctor"></i>
                                </span>
                                <span class="home-section-tab__content">
                                    <strong>الأطباء</strong>
                                    <small>يظهرون تلقائيًا</small>
                                </span>
                            </button>

                            <button type="button" class="home-section-tab" data-home-target="articles">
                                <span class="home-section-tab__icon">
                                    <i class="fa-solid fa-newspaper"></i>
                                </span>
                                <span class="home-section-tab__content">
                                    <strong>المقالات</strong>
                                    <small>محتوى من قاعدة البيانات</small>
                                </span>
                            </button>

                            <button type="button" class="home-section-tab" data-home-target="quiz">
                                <span class="home-section-tab__icon">
                                    <i class="fa-solid fa-brain"></i>
                                </span>
                                <span class="home-section-tab__content">
                                    <strong>الكويز</strong>
                                    <small>قسم تفاعلي للمستخدم</small>
                                </span>
                            </button>

                            <button type="button" class="home-section-tab" data-home-target="faq">
                                <span class="home-section-tab__icon">
                                    <i class="fa-solid fa-circle-question"></i>
                                </span>
                                <span class="home-section-tab__content">
                                    <strong>الأسئلة</strong>
                                    <small>إجابات مختصرة وواضحة</small>
                                </span>
                            </button>

                            <button type="button" class="home-section-tab" data-home-target="cta">
                                <span class="home-section-tab__icon">
                                    <i class="fa-solid fa-bullhorn"></i>
                                </span>
                                <span class="home-section-tab__content">
                                    <strong>CTA</strong>
                                    <small>الدعوة الأخيرة للبدء</small>
                                </span>
                            </button>
                        </div>

                        <div class="home-section-panels">

                            {{-- Hero --}}
                            <div class="home-section-panel is-active" data-home-panel="hero">
                                <div class="home-settings-group">
                                    <div class="home-settings-group-head">
                                        <div>
                                            <h3>قسم البداية Hero</h3>
                                            <span>أول قسم يظهر للزائر في الصفحة الرئيسية.</span>
                                        </div>
                                    </div>

                                    <div class="settings-fields">
                                        <label class="settings-switch settings-field-full">
                                            <input type="checkbox" name="home_hero_enabled" value="1" @checked($checked('home_hero_enabled', '1'))>
                                            <span></span>
                                            <div>
                                                <strong>إظهار قسم Hero</strong>
                                                <small>فعّل أو أخفِ قسم البداية من الصفحة.</small>
                                            </div>
                                        </label>

                                        <div class="settings-field">
                                            <label>الشارة العلوية</label>
                                            <input type="text" name="home_hero_badge" value="{{ $value('home_hero_badge', 'منصة صحية وغذائية متكاملة') }}">
                                        </div>

                                        <div class="settings-field">
                                            <label>العنوان الرئيسي</label>
                                            <input type="text" name="home_hero_title" value="{{ $value('home_hero_title', 'توازن في غذائك..') }}">
                                        </div>

                                        <div class="settings-field">
                                            <label>النص المميز</label>
                                            <input type="text" name="home_hero_highlight" value="{{ $value('home_hero_highlight', 'استقرار في صحتك') }}">
                                        </div>

                                        <div class="settings-field settings-field-full">
                                            <label>وصف القسم</label>
                                            <textarea name="home_hero_description" rows="4">{{ $value('home_hero_description', 'منصة اتزان تربطك بخبراء التغذية وتساعدك على متابعة حالتك الصحية، واكتشاف توصيات غذائية مناسبة، وبناء نمط حياة أكثر وعيًا وتوازنًا.') }}</textarea>
                                        </div>

                                        <div class="settings-field">
                                            <label>نص الزر الأول</label>
                                            <input type="text" name="home_hero_primary_btn_text" value="{{ $value('home_hero_primary_btn_text', 'ابدأ رحلتك الآن') }}">
                                        </div>

                                        <div class="settings-field">
                                            <label>رابط الزر الأول</label>
                                            <input type="text" name="home_hero_primary_btn_url" value="{{ $value('home_hero_primary_btn_url', route('register')) }}">
                                        </div>

                                        <div class="settings-field">
                                            <label>نص الزر الثاني</label>
                                            <input type="text" name="home_hero_secondary_btn_text" value="{{ $value('home_hero_secondary_btn_text', 'تعرّف على الأطباء') }}">
                                        </div>

                                        <div class="settings-field">
                                            <label>رابط الزر الثاني</label>
                                            <input type="text" name="home_hero_secondary_btn_url" value="{{ $value('home_hero_secondary_btn_url', '#doctors') }}">
                                        </div>

                                        <div class="settings-field">
                                            <label>البطاقة العائمة الأولى</label>
                                            <input type="text" name="home_hero_card_1" value="{{ $value('home_hero_card_1', 'خطة غذائية مناسبة') }}">
                                        </div>

                                        <div class="settings-field">
                                            <label>البطاقة العائمة الثانية</label>
                                            <input type="text" name="home_hero_card_2" value="{{ $value('home_hero_card_2', 'محتوى توعوي موثوق') }}">
                                        </div>

                                        <div class="settings-field">
                                            <label>البطاقة العائمة الثالثة</label>
                                            <input type="text" name="home_hero_card_3" value="{{ $value('home_hero_card_3', 'متابعة صحية مستمرة') }}">
                                        </div>

                                        <div class="settings-upload-row settings-field-full">
                                            @foreach(['home_hero_slide_1' => 'الصورة الأولى', 'home_hero_slide_2' => 'الصورة الثانية', 'home_hero_slide_3' => 'الصورة الثالثة'] as $key => $label)
                                                <div class="settings-field">
                                                    <label>{{ $label }}</label>
                                                    <input type="file" name="{{ $key }}" accept="image/*">

                                                    @if($imagePreview($key))
                                                        <div class="settings-preview">
                                                            <img src="{{ $imagePreview($key) }}" alt="{{ $label }}">
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- How it works --}}
                            <div class="home-section-panel" data-home-panel="how">
                                <div class="home-settings-group">
                                    <div class="home-settings-group-head">
                                        <div>
                                            <h3>كيف يعمل اتزان</h3>
                                            <span>عنوان القسم والخطوات الأربع.</span>
                                        </div>
                                    </div>

                                    <div class="settings-fields">
                                        <label class="settings-switch settings-field-full">
                                            <input type="checkbox" name="home_how_enabled" value="1" @checked($checked('home_how_enabled', '1'))>
                                            <span></span>
                                            <div>
                                                <strong>إظهار القسم</strong>
                                                <small>تحكم بظهور قسم كيف يعمل.</small>
                                            </div>
                                        </label>

                                        <div class="settings-field">
                                            <label>الشارة</label>
                                            <input type="text" name="home_how_badge" value="{{ $value('home_how_badge', 'رحلتك مع اتزان') }}">
                                        </div>

                                        <div class="settings-field">
                                            <label>العنوان</label>
                                            <input type="text" name="home_how_title" value="{{ $value('home_how_title', 'كيف يعمل') }}">
                                        </div>

                                        <div class="settings-field">
                                            <label>النص المميز</label>
                                            <input type="text" name="home_how_highlight" value="{{ $value('home_how_highlight', 'اتزان') }}">
                                        </div>

                                        <div class="settings-field settings-field-full">
                                            <label>وصف القسم</label>
                                            <textarea name="home_how_description" rows="4">{{ $value('home_how_description', 'خطوات بسيطة ومنظمة تبدأ من إنشاء الحساب، وتنتهي بمتابعة تقدمك الصحي والغذائي بسهولة ووضوح.') }}</textarea>
                                        </div>

                                        <div class="settings-field settings-field-full">
                                            <label>صورة القسم</label>
                                            <input type="file" name="home_how_image" accept="image/*">

                                            @if($imagePreview('home_how_image'))
                                                <div class="settings-preview">
                                                    <img src="{{ $imagePreview('home_how_image') }}" alt="صورة القسم">
                                                </div>
                                            @endif
                                        </div>

                                        @foreach($homeSteps as $i => $step)
                                            <div class="settings-field">
                                                <label>عنوان الخطوة {{ $i }}</label>
                                                <input type="text" name="home_how_step_{{ $i }}_title" value="{{ $value('home_how_step_' . $i . '_title', $step[0]) }}">
                                            </div>

                                            <div class="settings-field">
                                                <label>وصف الخطوة {{ $i }}</label>
                                                <input type="text" name="home_how_step_{{ $i }}_description" value="{{ $value('home_how_step_' . $i . '_description', $step[1]) }}">
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            {{-- باقي الأقسام الموجودة عندك --}}
                            {{-- خليه كما هو من ملفك القديم من features إلى cta إذا كان موجودًا بعد هذا الجزء --}}


                        {{-- Features --}}
                        <div class="home-section-panel" data-home-panel="features">
                            <div class="home-settings-group">
                                <div class="home-settings-group-head">
                                    <div>
                                        <h3>المميزات</h3>
                                        <span>قسم ماذا يميزنا.</span>
                                    </div>
                                </div>

                                <div class="settings-fields">
                                    <label class="settings-switch settings-field-full">
                                        <input type="checkbox" name="home_features_enabled" value="1" @checked($checked('home_features_enabled', '1'))>
                                        <span></span>
                                        <div>
                                            <strong>إظهار القسم</strong>
                                            <small>تحكم بظهور قسم المميزات.</small>
                                        </div>
                                    </label>

                                    <div class="settings-field">
                                        <label>الشارة</label>
                                        <input type="text" name="home_features_badge" value="{{ $value('home_features_badge', 'ماذا يميزنا ؟') }}">
                                    </div>

                                    <div class="settings-field">
                                        <label>العنوان</label>
                                        <input type="text" name="home_features_title" value="{{ $value('home_features_title', 'اكتشف كيف نجعل رحلتك الصحية') }}">
                                    </div>

                                    <div class="settings-field">
                                        <label>النص المميز</label>
                                        <input type="text" name="home_features_highlight" value="{{ $value('home_features_highlight', 'أبسط وأوضح') }}">
                                    </div>

                                    <div class="settings-field settings-field-full">
                                        <label>وصف القسم</label>
                                        <textarea name="home_features_description" rows="4">{{ $value('home_features_description', 'نقدم لك أدوات ذكية وتجربة مريحة تساعدك على فهم حالتك الصحية واتخاذ قرارات غذائية أفضل بطريقة منظمة وسهلة.') }}</textarea>
                                    </div>

                                    <div class="settings-field">
                                        <label>وسم الميزة</label>
                                        <input type="text" name="home_feature_mini_label" value="{{ $value('home_feature_mini_label', 'ميزة ذكية') }}">
                                    </div>

                                    <div class="settings-field">
                                        <label>عنوان الميزة</label>
                                        <input type="text" name="home_feature_title" value="{{ $value('home_feature_title', 'نظام توصيات غذائية ذكي') }}">
                                    </div>

                                    <div class="settings-field settings-field-full">
                                        <label>وصف الميزة</label>
                                        <textarea name="home_feature_description" rows="4">{{ $value('home_feature_description', 'يعتمد النظام على بياناتك الصحية لتقديم توصيات غذائية مناسبة تساعدك على اتخاذ قرارات أفضل في حياتك اليومية.') }}</textarea>
                                    </div>

                                    <div class="settings-field settings-field-full">
                                        <label>ملاحظة الميزة</label>
                                        <textarea name="home_feature_note" rows="3">{{ $value('home_feature_note', 'مناسب للمستخدمين الذين يحتاجون إلى توجيه غذائي أوضح وأكثر تخصيصًا.') }}</textarea>
                                    </div>

                                    <div class="settings-field">
                                        <label>النقطة الأولى</label>
                                        <input type="text" name="home_feature_chip_1" value="{{ $value('home_feature_chip_1', 'توصيات مخصصة') }}">
                                    </div>

                                    <div class="settings-field">
                                        <label>النقطة الثانية</label>
                                        <input type="text" name="home_feature_chip_2" value="{{ $value('home_feature_chip_2', 'واجهة مبسطة') }}">
                                    </div>

                                    <div class="settings-field">
                                        <label>النقطة الثالثة</label>
                                        <input type="text" name="home_feature_chip_3" value="{{ $value('home_feature_chip_3', 'نتائج أسرع') }}">
                                    </div>

                                    <div class="settings-field settings-field-full">
                                        <label>فقاعة الدكتورة</label>
                                        <textarea name="home_feature_bubble" rows="3">{{ $value('home_feature_bubble', 'نعرض لك الميزة الحالية بشكل واضح ومبسط لتفهم كيف تساعدك المنصة.') }}</textarea>
                                    </div>

                                    <div class="settings-field settings-field-full">
                                        <label>صورة القسم</label>
                                        <input type="file" name="home_features_image" accept="image/*">

                                        @if($imagePreview('home_features_image'))
                                            <div class="settings-preview">
                                                <img src="{{ $imagePreview('home_features_image') }}" alt="صورة المميزات">
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Services --}}
                        <div class="home-section-panel" data-home-panel="services">
                            <div class="home-settings-group">
                                <div class="home-settings-group-head">
                                    <div>
                                        <h3>الخدمات</h3>
                                        <span>عنوان القسم وست خدمات رئيسية.</span>
                                    </div>
                                </div>

                                <div class="settings-fields">
                                    <label class="settings-switch settings-field-full">
                                        <input type="checkbox" name="home_services_enabled" value="1" @checked($checked('home_services_enabled', '1'))>
                                        <span></span>
                                        <div>
                                            <strong>إظهار القسم</strong>
                                            <small>تحكم بظهور قسم الخدمات.</small>
                                        </div>
                                    </label>

                                    <div class="settings-field">
                                        <label>الشارة</label>
                                        <input type="text" name="home_services_badge" value="{{ $value('home_services_badge', 'خدمات اتزان') }}">
                                    </div>

                                    <div class="settings-field">
                                        <label>العنوان</label>
                                        <input type="text" name="home_services_title" value="{{ $value('home_services_title', 'كل ما تحتاجه لرحلة صحية') }}">
                                    </div>

                                    <div class="settings-field">
                                        <label>النص المميز</label>
                                        <input type="text" name="home_services_highlight" value="{{ $value('home_services_highlight', 'أكثر توازنًا') }}">
                                    </div>

                                    <div class="settings-field settings-field-full">
                                        <label>وصف القسم</label>
                                        <textarea name="home_services_description" rows="4">{{ $value('home_services_description', 'نوفر لك مجموعة من الخدمات الذكية التي تساعدك على متابعة صحتك وتحسين نمطك الغذائي.') }}</textarea>
                                    </div>

                                    @foreach($homeServices as $i => $service)
                                        <div class="settings-field settings-field-full">
                                            <hr>
                                            <strong>الخدمة {{ $i }}</strong>
                                        </div>

                                        <div class="settings-field">
                                            <label>شارة الخدمة {{ $i }}</label>
                                            <input type="text" name="home_service_{{ $i }}_badge" value="{{ $value('home_service_' . $i . '_badge', $service[0]) }}">
                                        </div>

                                        <div class="settings-field">
                                            <label>عنوان الخدمة {{ $i }}</label>
                                            <input type="text" name="home_service_{{ $i }}_title" value="{{ $value('home_service_' . $i . '_title', $service[1]) }}">
                                        </div>

                                        <div class="settings-field settings-field-full">
                                            <label>وصف الخدمة {{ $i }}</label>
                                            <textarea name="home_service_{{ $i }}_description" rows="3">{{ $value('home_service_' . $i . '_description', $service[2]) }}</textarea>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        {{-- Doctors --}}
                        <div class="home-section-panel" data-home-panel="doctors">
                            <div class="home-settings-group">
                                <div class="home-settings-group-head">
                                    <div>
                                        <h3>الأطباء</h3>
                                        <span>الأطباء يظهرون تلقائيًا من قاعدة البيانات، وهنا تتحكم بطريقة العرض فقط.</span>
                                    </div>
                                </div>

                                <div class="settings-fields">
                                    <label class="settings-switch settings-field-full">
                                        <input type="checkbox" name="home_doctors_enabled" value="1" @checked($checked('home_doctors_enabled', '1'))>
                                        <span></span>
                                        <div>
                                            <strong>إظهار قسم الأطباء</strong>
                                            <small>فعّل أو أخفِ قسم الأطباء من الصفحة الرئيسية.</small>
                                        </div>
                                    </label>

                                    <div class="settings-field">
                                        <label>الشارة</label>
                                        <input type="text" name="home_doctors_badge" value="{{ $value('home_doctors_badge', 'فريق اتزان') }}">
                                    </div>

                                    <div class="settings-field">
                                        <label>العنوان</label>
                                        <input type="text" name="home_doctors_title" value="{{ $value('home_doctors_title', 'خبراء يرافقون رحلتك نحو') }}">
                                    </div>

                                    <div class="settings-field">
                                        <label>النص المميز</label>
                                        <input type="text" name="home_doctors_highlight" value="{{ $value('home_doctors_highlight', 'صحة أكثر توازنًا') }}">
                                    </div>

                                    <div class="settings-field settings-field-full">
                                        <label>وصف القسم</label>
                                        <textarea name="home_doctors_description" rows="4">{{ $value('home_doctors_description', 'نخبة من الأطباء والمختصين في التغذية والصحة لمساعدتك على اتخاذ قرارات صحية أوضح، والحصول على دعم موثوق يناسب احتياجك.') }}</textarea>
                                    </div>

                                    <div class="settings-field">
                                        <label>عدد الأطباء المعروضين</label>
                                        <input type="number" min="1" max="12" name="home_doctors_limit" value="{{ $value('home_doctors_limit', '4') }}">
                                    </div>

                                    <div class="settings-field">
                                        <label>طريقة ترتيب الأطباء</label>
                                        <select name="home_doctors_order">
                                            <option value="latest" @selected($value('home_doctors_order', 'latest') === 'latest')>الأحدث أولًا</option>
                                            <option value="oldest" @selected($value('home_doctors_order', 'latest') === 'oldest')>الأقدم أولًا</option>
                                            <option value="random" @selected($value('home_doctors_order', 'latest') === 'random')>عشوائي</option>
                                        </select>
                                    </div>

                                    <label class="settings-switch settings-field-full">
                                        <input type="checkbox" name="home_doctors_only_active" value="1" @checked($checked('home_doctors_only_active', '1'))>
                                        <span></span>
                                        <div>
                                            <strong>عرض الأطباء المعتمدين فقط</strong>
                                            <small>يعرض الأطباء المقبولين أو النشطين فقط في الصفحة الرئيسية.</small>
                                        </div>
                                    </label>

                                    <label class="settings-switch settings-field-full">
                                        <input type="checkbox" name="home_doctor_join_enabled" value="1" @checked($checked('home_doctor_join_enabled', '1'))>
                                        <span></span>
                                        <div>
                                            <strong>إظهار صندوق انضمام الطبيب</strong>
                                            <small>يعرض دعوة للطبيب أو أخصائي التغذية للانضمام للمنصة.</small>
                                        </div>
                                    </label>

                                    <div class="settings-field">
                                        <label>عنوان صندوق الانضمام</label>
                                        <input type="text" name="home_doctor_join_title" value="{{ $value('home_doctor_join_title', 'هل أنت طبيب أو مختص تغذية؟') }}">
                                    </div>

                                    <div class="settings-field settings-field-full">
                                        <label>وصف صندوق الانضمام</label>
                                        <textarea name="home_doctor_join_description" rows="3">{{ $value('home_doctor_join_description', 'انضم إلى شبكة اتزان الطبية وساهم في تقديم تجربة صحية أكثر وعيًا واحترافية للمستخدمين.') }}</textarea>
                                    </div>

                                    <div class="settings-field">
                                        <label>نص زر الانضمام</label>
                                        <input type="text" name="home_doctor_join_btn_text" value="{{ $value('home_doctor_join_btn_text', 'انضم إلى فريق اتزان الطبي') }}">
                                    </div>

                                    <div class="settings-field">
                                        <label>رابط زر الانضمام</label>
                                        <input type="text" name="home_doctor_join_btn_url" value="{{ $value('home_doctor_join_btn_url', route('join-doctor')) }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Articles --}}
                        <div class="home-section-panel" data-home-panel="articles">
                            <div class="home-settings-group">
                                <div class="home-settings-group-head">
                                    <div>
                                        <h3>المقالات</h3>
                                        <span>المقالات تظهر تلقائيًا من قاعدة البيانات، وهنا تتحكم بطريقة العرض فقط.</span>
                                    </div>
                                </div>

                                <div class="settings-fields">
                                    <label class="settings-switch settings-field-full">
                                        <input type="checkbox" name="home_articles_enabled" value="1" @checked($checked('home_articles_enabled', '1'))>
                                        <span></span>
                                        <div>
                                            <strong>إظهار قسم المقالات</strong>
                                            <small>فعّل أو أخفِ قسم المقالات من الصفحة الرئيسية.</small>
                                        </div>
                                    </label>

                                    <div class="settings-field">
                                        <label>الشارة</label>
                                        <input type="text" name="home_articles_badge" value="{{ $value('home_articles_badge', 'المعرفة الصحية') }}">
                                    </div>

                                    <div class="settings-field">
                                        <label>العنوان</label>
                                        <input type="text" name="home_articles_title" value="{{ $value('home_articles_title', 'محتوى توعوي يساعدك على اتخاذ') }}">
                                    </div>

                                    <div class="settings-field">
                                        <label>النص المميز</label>
                                        <input type="text" name="home_articles_highlight" value="{{ $value('home_articles_highlight', 'قرارات أفضل') }}">
                                    </div>

                                    <div class="settings-field settings-field-full">
                                        <label>وصف القسم</label>
                                        <textarea name="home_articles_description" rows="4">{{ $value('home_articles_description', 'اكتشف مقالات صحية وغذائية مبسطة وموثوقة تساعدك على فهم حالتك الصحية وتبني عادات يومية أكثر وعيًا وتوازنًا.') }}</textarea>
                                    </div>

                                    <div class="settings-field">
                                        <label>عدد المقالات المعروضة</label>
                                        <input type="number" min="1" max="12" name="home_articles_limit" value="{{ $value('home_articles_limit', '4') }}">
                                    </div>

                                    <div class="settings-field">
                                        <label>طريقة ترتيب المقالات</label>
                                        <select name="home_articles_order">
                                            <option value="latest" @selected($value('home_articles_order', 'latest') === 'latest')>الأحدث أولًا</option>
                                            <option value="oldest" @selected($value('home_articles_order', 'latest') === 'oldest')>الأقدم أولًا</option>
                                            <option value="random" @selected($value('home_articles_order', 'latest') === 'random')>عشوائي</option>
                                        </select>
                                    </div>

                                    <div class="settings-field">
                                        <label>تصنيف المقالات المعروض</label>
                                        <input type="text" name="home_articles_category" value="{{ $value('home_articles_category', '') }}" placeholder="اتركه فارغًا لعرض كل التصنيفات">
                                    </div>

                                    <label class="settings-switch settings-field-full">
                                        <input type="checkbox" name="home_articles_only_published" value="1" @checked($checked('home_articles_only_published', '1'))>
                                        <span></span>
                                        <div>
                                            <strong>عرض المقالات المنشورة فقط</strong>
                                            <small>لا تعرض المقالات المسودة أو غير المنشورة في الصفحة الرئيسية.</small>
                                        </div>
                                    </label>

                                    <label class="settings-switch settings-field-full">
                                        <input type="checkbox" name="home_articles_featured_enabled" value="1" @checked($checked('home_articles_featured_enabled', '1'))>
                                        <span></span>
                                        <div>
                                            <strong>إظهار المقال المميز</strong>
                                            <small>يعرض أول مقال بشكل أكبر ومميز داخل قسم المقالات.</small>
                                        </div>
                                    </label>

                                    <div class="settings-field">
                                        <label>نص زر المقالات</label>
                                        <input type="text" name="home_articles_btn_text" value="{{ $value('home_articles_btn_text', 'تصفح جميع المقالات') }}">
                                    </div>

                                    <div class="settings-field">
                                        <label>رابط زر المقالات</label>
                                        <input type="text" name="home_articles_btn_url" value="{{ $value('home_articles_btn_url', route('articles')) }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Quiz --}}
                        {{-- Quiz --}}
                        {{-- Quiz --}}
                    <div class="home-section-panel" data-home-panel="quiz">
                        <div class="home-settings-group">
                            <div class="home-settings-group-head">
                                <div>
                                    <h3>الكويز</h3>
                                    <span>إعدادات ظهور الكويز وإدارة توصياته من نفس المكان.</span>
                                </div>
                            </div>

                            <div class="settings-fields">
                                <label class="settings-switch settings-field-full">
                                    <input type="checkbox" name="home_quiz_enabled" value="1" @checked($checked('home_quiz_enabled', '1'))>
                                    <span></span>
                                    <div>
                                        <strong>إظهار القسم</strong>
                                        <small>تحكم بظهور قسم الكويز في الصفحة الرئيسية.</small>
                                    </div>
                                </label>

                                <div class="settings-field">
                                    <label>الشارة</label>
                                    <input type="text" name="home_quiz_badge" value="{{ $value('home_quiz_badge', 'التقييم السريع') }}">
                                </div>

                                <div class="settings-field">
                                    <label>العنوان</label>
                                    <input type="text" name="home_quiz_title" value="{{ $value('home_quiz_title', 'خلّينا نتعرف على') }}">
                                </div>

                                <div class="settings-field">
                                    <label>النص المميز</label>
                                    <input type="text" name="home_quiz_highlight" value="{{ $value('home_quiz_highlight', 'احتياجك الغذائي') }}">
                                </div>

                                <div class="settings-field settings-field-full">
                                    <label>وصف القسم</label>
                                    <textarea name="home_quiz_description" rows="4">{{ $value('home_quiz_description', 'أجب عن مجموعة قصيرة من الأسئلة لنقترح عليك البداية الأنسب داخل اتزان.') }}</textarea>
                                </div>

                                <div class="settings-field settings-field-full">
                                    <label>رسالة الفقاعة</label>
                                    <textarea name="home_quiz_bubble_text" rows="3">{{ $value('home_quiz_bubble_text', 'خلّينا نبدأ بسؤال بسيط حتى نحدد الأنسب لك.') }}</textarea>
                                </div>

                                <div class="settings-field settings-field-full">
                                    <label>صورة الكويز</label>
                                    <input type="file" name="home_quiz_image" accept="image/*">

                                    @if($imagePreview('home_quiz_image'))
                                        <div class="settings-preview">
                                            <img src="{{ $imagePreview('home_quiz_image') }}" alt="صورة الكويز">
                                        </div>
                                    @endif
                                </div>

                                <div class="settings-field">
                                    <label>شارة النتيجة</label>
                                    <input type="text" name="home_quiz_result_badge" value="{{ $value('home_quiz_result_badge', 'نتيجتك الأولية') }}">
                                </div>

                                <div class="settings-field">
                                    <label>عنوان النتيجة</label>
                                    <input type="text" name="home_quiz_result_title" value="{{ $value('home_quiz_result_title', 'الأنسب لك: توصيات غذائية مخصصة') }}">
                                </div>

                                <div class="settings-field settings-field-full">
                                    <label>وصف النتيجة</label>
                                    <textarea name="home_quiz_result_description" rows="3">{{ $value('home_quiz_result_description', 'يبدو أنك ستستفيد من بداية منظمة تتضمن توجيهًا غذائيًا أوضح يناسب احتياجك الحالي.') }}</textarea>
                                </div>

                                <div class="settings-field">
                                    <label>وسم النتيجة 1</label>
                                    <input type="text" name="home_quiz_result_tag_1" value="{{ $value('home_quiz_result_tag_1', 'بداية واضحة') }}">
                                </div>

                                <div class="settings-field">
                                    <label>وسم النتيجة 2</label>
                                    <input type="text" name="home_quiz_result_tag_2" value="{{ $value('home_quiz_result_tag_2', 'دعم مناسب') }}">
                                </div>

                                <div class="settings-field">
                                    <label>وسم النتيجة 3</label>
                                    <input type="text" name="home_quiz_result_tag_3" value="{{ $value('home_quiz_result_tag_3', 'خطوات عملية') }}">
                                </div>

                                <div class="settings-field">
                                    <label>نص الزر الأول</label>
                                    <input type="text" name="home_quiz_primary_btn_text" value="{{ $value('home_quiz_primary_btn_text', 'تعرّف على الأطباء') }}">
                                </div>

                                <div class="settings-field">
                                    <label>رابط الزر الأول</label>
                                    <input type="text" name="home_quiz_primary_btn_url" value="{{ $value('home_quiz_primary_btn_url', '#doctors') }}">
                                </div>

                                <div class="settings-field">
                                    <label>نص الزر الثاني</label>
                                    <input type="text" name="home_quiz_secondary_btn_text" value="{{ $value('home_quiz_secondary_btn_text', 'استكشف المقالات') }}">
                                </div>

                                <div class="settings-field">
                                    <label>رابط الزر الثاني</label>
                                    <input type="text" name="home_quiz_secondary_btn_url" value="{{ $value('home_quiz_secondary_btn_url', '#articles') }}">
                                </div>

                                <div class="settings-field settings-field-full">
                                    <div class="quiz-rules-manager">
                                        <div class="quiz-rules-manager__head">
                                            <div>
                                                <h3>إدارة نتائج وتوصيات الكويز</h3>
                                                <p>
                                                    أضيفي توصيات، مهام يومية، أو مقالات تظهر حسب إجابات المستخدم ونتيجة الكويز.
                                                </p>
                                            </div>

                                            <button type="button" class="quiz-add-rule-btn" id="addQuizRuleBtn">
                                                <i class="fa-solid fa-plus"></i>
                                                إضافة قاعدة
                                            </button>
                                        </div>

                                        <div class="quiz-rules-list" id="quizRulesList">
                                            @forelse($quizRules as $index => $rule)
                                                <details class="quiz-rule-card">
                                                    <summary class="quiz-rule-summary">
                                                        <div>
                                                            <strong>قاعدة كويز #{{ $loop->iteration }}</strong>
                                                            <small>
                                                                {{ $quizContentTypes[$rule->content_type] ?? 'قاعدة' }}
                                                                —
                                                                {{ $quizResultTypes[$rule->result_type ?? ''] ?? 'كل النتائج' }}
                                                                @if(!empty($rule->goal))
                                                                    —
                                                                    {{ $quizGoals[$rule->goal] ?? $rule->goal }}
                                                                @endif
                                                                @if(!empty($rule->condition))
                                                                    —
                                                                    {{ $quizConditions[$rule->condition] ?? $rule->condition }}
                                                                @endif
                                                                @if(!empty($rule->activity))
                                                                    —
                                                                    نشاط: {{ $quizActivities[$rule->activity] ?? $rule->activity }}
                                                                @endif
                                                                @if(!empty($rule->symptoms))
                                                                    —
                                                                    {{ $quizSymptoms[$rule->symptoms] ?? $rule->symptoms }}
                                                                @endif
                                                                @if(!empty($rule->medication))
                                                                    —
                                                                    {{ $quizMedication[$rule->medication] ?? $rule->medication }}
                                                                @endif
                                                            </small>
                                                        </div>

                                                        <span>اضغطي للتعديل</span>
                                                    </summary>

                                                    <div class="quiz-rule-body">
                                                        <input type="hidden" name="quiz_rules[{{ $index }}][id]" value="{{ $rule->id }}">

                                                        <div class="quiz-rule-card__top">
                                                            <label class="quiz-rule-delete">
                                                                <input type="checkbox" name="quiz_rules[{{ $index }}][delete]" value="1">
                                                                حذف هذه القاعدة
                                                            </label>
                                                        </div>

                                                        <div class="quiz-rule-grid">
                                                            <div class="settings-field">
                                                                <label>نوع المحتوى</label>
                                                                <select name="quiz_rules[{{ $index }}][content_type]" class="quiz-content-type-select">
                                                                    @foreach($quizContentTypes as $key => $label)
                                                                        <option value="{{ $key }}" @selected($rule->content_type === $key)>{{ $label }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>

                                                            <div class="settings-field">
                                                                <label>نوع النتيجة</label>
                                                                <select name="quiz_rules[{{ $index }}][result_type]">
                                                                    @foreach($quizResultTypes as $key => $label)
                                                                        <option value="{{ $key }}" @selected(($rule->result_type ?? '') === $key)>{{ $label }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>

                                                            <div class="settings-field">
                                                                <label>الهدف</label>
                                                                <select name="quiz_rules[{{ $index }}][goal]">
                                                                    @foreach($quizGoals as $key => $label)
                                                                        <option value="{{ $key }}" @selected(($rule->goal ?? '') === $key)>{{ $label }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>

                                                            <div class="settings-field">
                                                                <label>الحالة الصحية</label>
                                                                <select name="quiz_rules[{{ $index }}][condition]">
                                                                    @foreach($quizConditions as $key => $label)
                                                                        <option value="{{ $key }}" @selected(($rule->condition ?? '') === $key)>{{ $label }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>

                                                            <div class="settings-field">
                                                                <label>النشاط</label>
                                                                <select name="quiz_rules[{{ $index }}][activity]">
                                                                    @foreach($quizActivities as $key => $label)
                                                                        <option value="{{ $key }}" @selected(($rule->activity ?? '') === $key)>{{ $label }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>

                                                            <div class="settings-field">
                                                                <label>الأعراض</label>
                                                                <select name="quiz_rules[{{ $index }}][symptoms]">
                                                                    @foreach($quizSymptoms as $key => $label)
                                                                        <option value="{{ $key }}" @selected(($rule->symptoms ?? '') === $key)>{{ $label }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>

                                                            <div class="settings-field">
                                                                <label>الأدوية</label>
                                                                <select name="quiz_rules[{{ $index }}][medication]">
                                                                    @foreach($quizMedication as $key => $label)
                                                                        <option value="{{ $key }}" @selected(($rule->medication ?? '') === $key)>{{ $label }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>

                                                            <div class="settings-field">
                                                                <label>الأولوية</label>
                                                                <input type="number" name="quiz_rules[{{ $index }}][priority]" value="{{ $rule->priority ?? 1 }}" min="1">
                                                            </div>

                                                            <div class="settings-field settings-field-full quiz-rule-text-field">
                                                                <label>نص التوصية / المهمة</label>
                                                                <textarea name="quiz_rules[{{ $index }}][text]" rows="3" placeholder="اكتبي نص التوصية أو المهمة">{{ $rule->text }}</textarea>
                                                            </div>

                                                            <div class="settings-field settings-field-full quiz-rule-article-field">
                                                                <label>المقال المرتبط</label>
                                                                <select name="quiz_rules[{{ $index }}][article_id]">
                                                                    <option value="">بدون مقال</option>
                                                                    @foreach($quizArticles as $article)
                                                                        <option value="{{ $article->id }}" @selected((int) $rule->article_id === (int) $article->id)>
                                                                            {{ $article->title ?? $article->name ?? ('مقال #' . $article->id) }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </div>

                                                            <label class="settings-switch settings-field-full">
                                                                <input type="checkbox" name="quiz_rules[{{ $index }}][is_active]" value="1" @checked($rule->is_active)>
                                                                <span></span>
                                                                <div>
                                                                    <strong>تفعيل القاعدة</strong>
                                                                    <small>إذا كانت غير مفعّلة لن تظهر في نتيجة الكويز.</small>
                                                                </div>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </details>
                                            @empty
                                                <div class="quiz-rules-empty">
                                                    لا توجد قواعد بعد. اضغطي على “إضافة قاعدة” لإضافة أول توصية أو مهمة أو مقال.
                                                </div>
                                            @endforelse
                                        </div>

                                        <template id="quizRuleTemplate">
                                            <details class="quiz-rule-card" open>
                                                <summary class="quiz-rule-summary">
                                                    <div>
                                                        <strong>قاعدة جديدة</strong>
                                                        <small>توصية — كل النتائج</small>
                                                    </div>

                                                    <span>اضغطي للتعديل</span>
                                                </summary>

                                                <div class="quiz-rule-body">
                                                    <input type="hidden" name="quiz_rules[__INDEX__][id]" value="">

                                                    <div class="quiz-rule-card__top">
                                                        <button type="button" class="quiz-remove-rule-btn">
                                                            حذف
                                                        </button>
                                                    </div>

                                                    <div class="quiz-rule-grid">
                                                        <div class="settings-field">
                                                            <label>نوع المحتوى</label>
                                                            <select name="quiz_rules[__INDEX__][content_type]" class="quiz-content-type-select">
                                                                @foreach($quizContentTypes as $key => $label)
                                                                    <option value="{{ $key }}">{{ $label }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>

                                                        <div class="settings-field">
                                                            <label>نوع النتيجة</label>
                                                            <select name="quiz_rules[__INDEX__][result_type]">
                                                                @foreach($quizResultTypes as $key => $label)
                                                                    <option value="{{ $key }}">{{ $label }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>

                                                        <div class="settings-field">
                                                            <label>الهدف</label>
                                                            <select name="quiz_rules[__INDEX__][goal]">
                                                                @foreach($quizGoals as $key => $label)
                                                                    <option value="{{ $key }}">{{ $label }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>

                                                        <div class="settings-field">
                                                            <label>الحالة الصحية</label>
                                                            <select name="quiz_rules[__INDEX__][condition]">
                                                                @foreach($quizConditions as $key => $label)
                                                                    <option value="{{ $key }}">{{ $label }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>

                                                        <div class="settings-field">
                                                            <label>النشاط</label>
                                                            <select name="quiz_rules[__INDEX__][activity]">
                                                                @foreach($quizActivities as $key => $label)
                                                                    <option value="{{ $key }}">{{ $label }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>

                                                        <div class="settings-field">
                                                            <label>الأعراض</label>
                                                            <select name="quiz_rules[__INDEX__][symptoms]">
                                                                @foreach($quizSymptoms as $key => $label)
                                                                    <option value="{{ $key }}">{{ $label }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>

                                                        <div class="settings-field">
                                                            <label>الأدوية</label>
                                                            <select name="quiz_rules[__INDEX__][medication]">
                                                                @foreach($quizMedication as $key => $label)
                                                                    <option value="{{ $key }}">{{ $label }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>

                                                        <div class="settings-field">
                                                            <label>الأولوية</label>
                                                            <input type="number" name="quiz_rules[__INDEX__][priority]" value="1" min="1">
                                                        </div>

                                                        <div class="settings-field settings-field-full quiz-rule-text-field">
                                                            <label>نص التوصية / المهمة</label>
                                                            <textarea name="quiz_rules[__INDEX__][text]" rows="3" placeholder="اكتبي نص التوصية أو المهمة"></textarea>
                                                        </div>

                                                        <div class="settings-field settings-field-full quiz-rule-article-field">
                                                            <label>المقال المرتبط</label>
                                                            <select name="quiz_rules[__INDEX__][article_id]">
                                                                <option value="">بدون مقال</option>
                                                                @foreach($quizArticles as $article)
                                                                    <option value="{{ $article->id }}">
                                                                        {{ $article->title ?? $article->name ?? ('مقال #' . $article->id) }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>

                                                        <label class="settings-switch settings-field-full">
                                                            <input type="checkbox" name="quiz_rules[__INDEX__][is_active]" value="1" checked>
                                                            <span></span>
                                                            <div>
                                                                <strong>تفعيل القاعدة</strong>
                                                                <small>إذا كانت غير مفعّلة لن تظهر في نتيجة الكويز.</small>
                                                            </div>
                                                        </label>
                                                    </div>
                                                </div>
                                            </details>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                        {{-- FAQ --}}
                        <div class="home-section-panel" data-home-panel="faq">
                            <div class="home-settings-group">
                                <div class="home-settings-group-head">
                                    <div>
                                        <h3>الأسئلة الشائعة</h3>
                                        <span>عنوان القسم والأسئلة.</span>
                                    </div>
                                </div>

                                <div class="settings-fields">
                                    <label class="settings-switch settings-field-full">
                                        <input type="checkbox" name="home_faq_enabled" value="1" @checked($checked('home_faq_enabled', '1'))>
                                        <span></span>
                                        <div>
                                            <strong>إظهار القسم</strong>
                                            <small>تحكم بظهور قسم الأسئلة.</small>
                                        </div>
                                    </label>

                                    <div class="settings-field">
                                        <label>الشارة</label>
                                        <input type="text" name="home_faq_badge" value="{{ $value('home_faq_badge', 'الأسئلة الشائعة') }}">
                                    </div>

                                    <div class="settings-field">
                                        <label>العنوان</label>
                                        <input type="text" name="home_faq_title" value="{{ $value('home_faq_title', 'كل ما تحتاج معرفته') }}">
                                    </div>

                                    <div class="settings-field">
                                        <label>النص المميز</label>
                                        <input type="text" name="home_faq_highlight" value="{{ $value('home_faq_highlight', 'قبل البدء') }}">
                                    </div>

                                    <div class="settings-field settings-field-full">
                                        <label>وصف القسم</label>
                                        <textarea name="home_faq_description" rows="4">{{ $value('home_faq_description', 'جمعنا لك أهم الأسئلة التي قد تدور في بالك قبل إنشاء الحساب أو البدء باستخدام اتزان.') }}</textarea>
                                    </div>

                                    <div class="settings-field">
                                        <label>عنوان صندوق الثقة</label>
                                        <input type="text" name="home_faq_trust_title" value="{{ $value('home_faq_trust_title', 'إجابات واضحة وتجربة مطمئنة') }}">
                                    </div>

                                    <div class="settings-field settings-field-full">
                                        <label>وصف صندوق الثقة</label>
                                        <textarea name="home_faq_trust_description" rows="3">{{ $value('home_faq_trust_description', 'نحاول أن نجعل رحلتك في اتزان واضحة من أول خطوة، مع معلومات بسيطة وتجربة سهلة قبل التسجيل وبعده.') }}</textarea>
                                    </div>

                                    @foreach($homeFaqs as $i => $faq)
                                        <div class="settings-field settings-field-full">
                                            <hr>
                                            <strong>السؤال {{ $i }}</strong>
                                        </div>

                                        <div class="settings-field">
                                            <label>شارة السؤال</label>
                                            <input type="text" name="home_faq_{{ $i }}_badge" value="{{ $value('home_faq_' . $i . '_badge', $faq[0]) }}">
                                        </div>

                                        <div class="settings-field settings-field-full">
                                            <label>نص السؤال</label>
                                            <input type="text" name="home_faq_{{ $i }}_question" value="{{ $value('home_faq_' . $i . '_question', $faq[1]) }}">
                                        </div>

                                        <div class="settings-field settings-field-full">
                                            <label>وصف قصير</label>
                                            <textarea name="home_faq_{{ $i }}_short" rows="2">{{ $value('home_faq_' . $i . '_short', $faq[2]) }}</textarea>
                                        </div>

                                        <div class="settings-field settings-field-full">
                                            <label>الإجابة</label>
                                            <textarea name="home_faq_{{ $i }}_answer" rows="4">{{ $value('home_faq_' . $i . '_answer', $faq[3]) }}</textarea>
                                        </div>

                                        <div class="settings-field">
                                            <label>النقطة الأولى</label>
                                            <input type="text" name="home_faq_{{ $i }}_point_1" value="{{ $value('home_faq_' . $i . '_point_1', $faq[4]) }}">
                                        </div>

                                        <div class="settings-field">
                                            <label>النقطة الثانية</label>
                                            <input type="text" name="home_faq_{{ $i }}_point_2" value="{{ $value('home_faq_' . $i . '_point_2', $faq[5]) }}">
                                        </div>

                                        <div class="settings-field">
                                            <label>النقطة الثالثة</label>
                                            <input type="text" name="home_faq_{{ $i }}_point_3" value="{{ $value('home_faq_' . $i . '_point_3', $faq[6]) }}">
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        {{-- CTA --}}
                        <div class="home-section-panel" data-home-panel="cta">
                            <div class="home-settings-group">
                                <div class="home-settings-group-head">
                                    <div>
                                        <h3>الدعوة للبدء CTA</h3>
                                        <span>آخر قسم في الصفحة الرئيسية.</span>
                                    </div>
                                </div>

                                <div class="settings-fields">
                                    <label class="settings-switch settings-field-full">
                                        <input type="checkbox" name="home_cta_enabled" value="1" @checked($checked('home_cta_enabled', '1'))>
                                        <span></span>
                                        <div>
                                            <strong>إظهار القسم</strong>
                                            <small>تحكم بظهور قسم الدعوة للبدء.</small>
                                        </div>
                                    </label>

                                    <div class="settings-field">
                                        <label>الشارة</label>
                                        <input type="text" name="home_cta_badge" value="{{ $value('home_cta_badge', 'ابدأ الآن') }}">
                                    </div>

                                    <div class="settings-field">
                                        <label>العنوان</label>
                                        <input type="text" name="home_cta_title" value="{{ $value('home_cta_title', 'جاهز تبدأ رحلتك مع') }}">
                                    </div>

                                    <div class="settings-field">
                                        <label>النص المميز</label>
                                        <input type="text" name="home_cta_highlight" value="{{ $value('home_cta_highlight', 'اتزان') }}">
                                    </div>

                                    <div class="settings-field settings-field-full">
                                        <label>الوصف</label>
                                        <textarea name="home_cta_description" rows="4">{{ $value('home_cta_description', 'يمكنك الآن اختيار الخطوة المناسبة لك والبدء في رحلة صحية أكثر وعيًا وتوازنًا.') }}</textarea>
                                    </div>

                                    <div class="settings-field">
                                        <label>النقطة الأولى</label>
                                        <input type="text" name="home_cta_point_1" value="{{ $value('home_cta_point_1', 'توصيات مخصصة') }}">
                                    </div>

                                    <div class="settings-field">
                                        <label>النقطة الثانية</label>
                                        <input type="text" name="home_cta_point_2" value="{{ $value('home_cta_point_2', 'دعم من مختصين') }}">
                                    </div>

                                    <div class="settings-field">
                                        <label>النقطة الثالثة</label>
                                        <input type="text" name="home_cta_point_3" value="{{ $value('home_cta_point_3', 'متابعة مستمرة') }}">
                                    </div>

                                    <div class="settings-field">
                                        <label>نص الزر الأول</label>
                                        <input type="text" name="home_cta_primary_btn_text" value="{{ $value('home_cta_primary_btn_text', 'ابدأ رحلتك الآن') }}">
                                    </div>

                                    <div class="settings-field">
                                        <label>رابط الزر الأول</label>
                                        <input type="text" name="home_cta_primary_btn_url" value="{{ $value('home_cta_primary_btn_url', route('register')) }}">
                                    </div>

                                    <div class="settings-field">
                                        <label>نص الزر الثاني</label>
                                        <input type="text" name="home_cta_secondary_btn_text" value="{{ $value('home_cta_secondary_btn_text', 'انضم كطبيب') }}">
                                    </div>

                                    <div class="settings-field">
                                        <label>رابط الزر الثاني</label>
                                        <input type="text" name="home_cta_secondary_btn_url" value="{{ $value('home_cta_secondary_btn_url', route('join-doctor')) }}">
                                    </div>

                                    <div class="settings-field">
                                        <label>عنوان البطاقة</label>
                                        <input type="text" name="home_cta_card_title" value="{{ $value('home_cta_card_title', 'خطتك تبدأ بخطوة واضحة') }}">
                                    </div>

                                    <div class="settings-field settings-field-full">
                                        <label>وصف البطاقة</label>
                                        <textarea name="home_cta_card_description" rows="3">{{ $value('home_cta_card_description', 'حدّد احتياجك، استكشف الحلول المناسبة، وابدأ رحلتك الصحية بطريقة أسهل وأكثر تنظيمًا.') }}</textarea>
                                    </div>

                                    <div class="settings-field">
                                        <label>البطاقة العائمة الأولى</label>
                                        <input type="text" name="home_cta_float_1" value="{{ $value('home_cta_float_1', 'بداية سهلة') }}">
                                    </div>

                                    <div class="settings-field">
                                        <label>البطاقة العائمة الثانية</label>
                                        <input type="text" name="home_cta_float_2" value="{{ $value('home_cta_float_2', 'خصوصية آمنة') }}">
                                    </div>

                                    <div class="settings-field">
                                        <label>البطاقة العائمة الثالثة</label>
                                        <input type="text" name="home_cta_float_3" value="{{ $value('home_cta_float_3', 'خطوات واضحة') }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </section>

            </div>
        </div>

        <div class="settings-actions">
            <button type="submit" class="settings-save-btn">
                <i class="fa-solid fa-floppy-disk"></i>
                احفظ التغييرات
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tabs = document.querySelectorAll('.settings-tab');
        const panels = document.querySelectorAll('.settings-panel');
        const storageKey = 'etzan_active_settings_tab';

        function activateTab(target) {
            tabs.forEach(function (tab) {
                tab.classList.toggle('is-active', tab.dataset.target === target);
            });

            panels.forEach(function (panel) {
                panel.classList.toggle('is-active', panel.dataset.panel === target);
            });

            localStorage.setItem(storageKey, target);
        }

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                activateTab(tab.dataset.target);
            });
        });

        const savedTab = localStorage.getItem(storageKey);
        const hashTab = window.location.hash ? window.location.hash.replace('#', '') : null;

        if (hashTab && document.querySelector(`[data-target="${hashTab}"]`)) {
            activateTab(hashTab);
        } else if (savedTab && document.querySelector(`[data-target="${savedTab}"]`)) {
            activateTab(savedTab);
        }

        const homeTabs = document.querySelectorAll('.home-section-tab');
        const homePanels = document.querySelectorAll('.home-section-panel');
        const homeStorageKey = 'etzan_home_inner_tab';

        function activateHomeSection(target) {
            homeTabs.forEach(function (tab) {
                tab.classList.toggle('is-active', tab.dataset.homeTarget === target);
            });

            homePanels.forEach(function (panel) {
                panel.classList.toggle('is-active', panel.dataset.homePanel === target);
            });

            localStorage.setItem(homeStorageKey, target);
        }

        homeTabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                activateHomeSection(tab.dataset.homeTarget);
            });
        });

        const savedHomeTab = localStorage.getItem(homeStorageKey);

        if (savedHomeTab && document.querySelector(`[data-home-target="${savedHomeTab}"]`)) {
            activateHomeSection(savedHomeTab);
        }

        const addQuizRuleBtn = document.getElementById('addQuizRuleBtn');
        const quizRulesList = document.getElementById('quizRulesList');
        const quizRuleTemplate = document.getElementById('quizRuleTemplate');

        function refreshQuizRuleVisibility(scope) {
            const cards = scope ? [scope] : document.querySelectorAll('.quiz-rule-card');

            cards.forEach(function (card) {
                const typeSelect = card.querySelector('.quiz-content-type-select');
                const textField = card.querySelector('.quiz-rule-text-field');
                const articleField = card.querySelector('.quiz-rule-article-field');

                if (!typeSelect || !textField || !articleField) {
                    return;
                }

                const isArticle = typeSelect.value === 'article';

                textField.style.display = isArticle ? 'none' : '';
                articleField.style.display = isArticle ? '' : 'none';
            });
        }

        document.addEventListener('change', function (event) {
            if (event.target.classList.contains('quiz-content-type-select')) {
                const card = event.target.closest('.quiz-rule-card');
                refreshQuizRuleVisibility(card);
            }
        });

        document.addEventListener('click', function (event) {
            if (event.target.closest('.quiz-remove-rule-btn')) {
                event.preventDefault();

                const card = event.target.closest('.quiz-rule-card');

                if (card) {
                    card.remove();
                }
            }
        });

        if (addQuizRuleBtn && quizRulesList && quizRuleTemplate) {
            addQuizRuleBtn.addEventListener('click', function () {
                const index = Date.now();
                const html = quizRuleTemplate.innerHTML.replaceAll('__INDEX__', index);

                const wrapper = document.createElement('div');
                wrapper.innerHTML = html.trim();

                const empty = quizRulesList.querySelector('.quiz-rules-empty');

                if (empty) {
                    empty.remove();
                }

                const card = wrapper.firstElementChild;
                quizRulesList.appendChild(card);

                refreshQuizRuleVisibility(card);
            });
        }

        refreshQuizRuleVisibility();
    });
</script>
@endpush
