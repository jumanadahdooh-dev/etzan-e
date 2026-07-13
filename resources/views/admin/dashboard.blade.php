@extends('layouts.admin')

@section('title', 'لوحة تحكم الإدارة | إتزان')

@push('styles')
<link rel="stylesheet" href="{{ asset('front/css/admin/dashboard.css') }}?v={{ filemtime(public_path('front/css/admin/dashboard.css')) }}">
@endpush

@section('content')
@php
    $stats = $stats ?? [];
    $rates = $rates ?? [];
    $charts = $charts ?? [];
    $quickHealth = $quickHealth ?? [];

    $topDoctors = $topDoctors ?? collect();
    $latestApplications = $latestApplications ?? collect();
    $latestUsers = $latestUsers ?? collect();
    $latestArticles = $latestArticles ?? collect();

    // الداشبورد يعرض آخر عناصر فقط، أما كل البيانات تظهر من صفحات الإدارة عبر أزرار عرض الكل.
    $topDoctorsPreview = collect($topDoctors)->take(4);
    $latestApplicationsPreview = collect($latestApplications)->take(3);
    $latestUsersPreview = collect($latestUsers)->take(5);
    $latestArticlesPreview = collect($latestArticles)->take(5);

    $statusLabels = [
        'pending' => ['label' => 'معلق', 'class' => 'warning'],
        'approved' => ['label' => 'مقبول', 'class' => 'success'],
        'confirmed' => ['label' => 'مؤكد', 'class' => 'success'],
        'published' => ['label' => 'منشور', 'class' => 'success'],
        'draft' => ['label' => 'مسودة', 'class' => 'secondary'],
        'pending_review' => ['label' => 'قيد المراجعة', 'class' => 'warning'],
        'rejected' => ['label' => 'مرفوض', 'class' => 'danger'],
        'completed' => ['label' => 'مكتمل', 'class' => 'success'],
        'cancelled' => ['label' => 'ملغي', 'class' => 'danger'],
        'reschedule_requested' => ['label' => 'إعادة جدولة', 'class' => 'info'],
        'منشور' => ['label' => 'منشور', 'class' => 'success'],
        'منشورة' => ['label' => 'منشور', 'class' => 'success'],
        'مسودة' => ['label' => 'مسودة', 'class' => 'secondary'],
        'قيد المراجعة' => ['label' => 'قيد المراجعة', 'class' => 'warning'],
        'تحت المراجعة' => ['label' => 'قيد المراجعة', 'class' => 'warning'],
        'مرفوض' => ['label' => 'مرفوض', 'class' => 'danger'],
        'مرفوضة' => ['label' => 'مرفوض', 'class' => 'danger'],
        'معلق' => ['label' => 'معلق', 'class' => 'warning'],
        'مؤكد' => ['label' => 'مؤكد', 'class' => 'success'],
        'مكتمل' => ['label' => 'مكتمل', 'class' => 'success'],
        'ملغي' => ['label' => 'ملغي', 'class' => 'danger'],
    ];

    $roleLabels = [
        'admin' => 'إدارة',
        'doctor' => 'طبيب',
        'patient' => 'مريض',
        'user' => 'مستخدم',
    ];

    $roleClasses = [
        'admin' => 'danger',
        'doctor' => 'success',
        'patient' => 'info',
        'user' => 'secondary',
    ];

    $needsAttention = (int) ($quickHealth['needs_attention'] ?? 0);
    $pendingDoctorApplications = (int) ($stats['pending_doctor_applications'] ?? 0);
    $articlesPendingReview = (int) ($stats['articles_pending_review'] ?? 0);
    $appointmentsPending = (int) ($stats['appointments_pending'] ?? 0);
    $appointmentsReschedule = (int) ($stats['appointments_reschedule'] ?? 0);
    $appointmentsNeedAction = $appointmentsPending + $appointmentsReschedule;
    $unreadAdminMessages = (int) ($stats['unread_admin_messages'] ?? 0);
    $adminNotificationsUnread = (int) ($stats['admin_notifications_unread'] ?? 0);

    $totalUsers = (int) ($stats['total_users'] ?? 0);
    $newUsersThisMonth = (int) ($stats['new_users_this_month'] ?? 0);
    $newUsersRate = $totalUsers > 0 ? min(100, max(0, round(($newUsersThisMonth / $totalUsers) * 100))) : 0;

    $patientProfiles = (int) ($stats['patient_profiles'] ?? 0);
    $profileRate = min(100, max(0, (int) ($rates['profile_completion'] ?? 0)));

    $appointmentsTotal = (int) ($stats['appointments_total'] ?? 0);
    $appointmentsToday = (int) ($stats['appointments_today'] ?? 0);
    $appointmentsUpcoming = (int) ($stats['appointments_upcoming'] ?? 0);
    $appointmentsRate = $appointmentsTotal > 0 ? min(100, max(0, round(($appointmentsUpcoming / $appointmentsTotal) * 100))) : 0;

    $averageDoctorRating = (float) ($stats['average_doctor_rating'] ?? 0);
    $ratingRate = min(100, max(0, round(($averageDoctorRating / 5) * 100)));

    $aiRate = min(100, max(0, (int) ($rates['ai_content'] ?? 0)));

    $tasksTotal = (int) ($stats['tasks_total'] ?? 0);
    $tasksCompleted = (int) ($stats['tasks_completed'] ?? 0);
    $tasksRate = $tasksTotal > 0 ? round(($tasksCompleted / $tasksTotal) * 100) : 0;
    $tasksRate = min(100, max(0, $tasksRate));

    $doctorMaxPatients = max(1, (int) $topDoctorsPreview->max('patients_count'));

    $doctorApplicationsUrl = \Illuminate\Support\Facades\Route::has('admin.doctor-applications')
        ? route('admin.doctor-applications')
        : url('/admin/doctor-applications');

    $articlesUrl = \Illuminate\Support\Facades\Route::has('admin.articles.index')
        ? route('admin.articles.index')
        : url('/admin/articles');

    $messagesUrl = \Illuminate\Support\Facades\Route::has('admin.messages.index')
        ? route('admin.messages.index')
        : url('/admin/messages');

    $usersUrl = \Illuminate\Support\Facades\Route::has('admin.admin-users')
        ? route('admin.admin-users')
        : url('/admin/users');

    $chartDefaults = [
        'monthly_users' => ['labels' => [], 'values' => []],
        'monthly_appointments' => ['labels' => [], 'values' => []],
        'roles' => ['labels' => [], 'values' => []],
        'appointments_status' => ['labels' => [], 'values' => []],
        'articles_status' => ['labels' => [], 'values' => []],
    ];

    $chartPayload = array_replace_recursive($chartDefaults, (array) $charts);

    $accentClasses = ['green', 'blue', 'purple', 'orange', 'red', 'teal'];

    $roleLabelMap = [
        'patient' => 'مرضى',
        'patients' => 'مرضى',
        'مريض' => 'مرضى',
        'مرضى' => 'مرضى',
        'doctor' => 'أطباء',
        'doctors' => 'أطباء',
        'طبيب' => 'أطباء',
        'أطباء' => 'أطباء',
        'admin' => 'إدارة',
        'admins' => 'إدارة',
        'إدارة' => 'إدارة',
        'user' => 'مستخدمون',
        'users' => 'مستخدمون',
        'مستخدم' => 'مستخدمون',
        'مستخدمون' => 'مستخدمون',
    ];

    $roleIconMap = [
        'patient' => 'fa-bed-pulse',
        'patients' => 'fa-bed-pulse',
        'مريض' => 'fa-bed-pulse',
        'مرضى' => 'fa-bed-pulse',
        'doctor' => 'fa-user-doctor',
        'doctors' => 'fa-user-doctor',
        'طبيب' => 'fa-user-doctor',
        'أطباء' => 'fa-user-doctor',
        'admin' => 'fa-user-shield',
        'admins' => 'fa-user-shield',
        'إدارة' => 'fa-user-shield',
        'user' => 'fa-users',
        'users' => 'fa-users',
        'مستخدم' => 'fa-users',
        'مستخدمون' => 'fa-users',
    ];

    $appointmentIconMap = [
        'pending' => 'fa-hourglass-half',
        'confirmed' => 'fa-circle-check',
        'approved' => 'fa-circle-check',
        'completed' => 'fa-calendar-check',
        'cancelled' => 'fa-circle-xmark',
        'reschedule_requested' => 'fa-clock-rotate-left',
        'معلق' => 'fa-hourglass-half',
        'مؤكد' => 'fa-circle-check',
        'مكتمل' => 'fa-calendar-check',
        'ملغي' => 'fa-circle-xmark',
        'إعادة جدولة' => 'fa-clock-rotate-left',
    ];

    $articleIconMap = [
        'published' => 'fa-circle-check',
        'draft' => 'fa-pen-ruler',
        'pending_review' => 'fa-magnifying-glass-chart',
        'rejected' => 'fa-circle-xmark',
        'منشور' => 'fa-circle-check',
        'مسودة' => 'fa-pen-ruler',
        'قيد المراجعة' => 'fa-magnifying-glass-chart',
        'مرفوض' => 'fa-circle-xmark',
        'منشورة' => 'fa-circle-check',
        'تحت المراجعة' => 'fa-magnifying-glass-chart',
        'مرفوضة' => 'fa-circle-xmark',
    ];

    $classByStatus = [
        'pending' => 'orange',
        'confirmed' => 'green',
        'approved' => 'green',
        'completed' => 'green',
        'cancelled' => 'red',
        'reschedule_requested' => 'blue',
        'published' => 'green',
        'draft' => 'purple',
        'pending_review' => 'orange',
        'rejected' => 'red',
        'معلق' => 'orange',
        'مؤكد' => 'green',
        'مقبول' => 'green',
        'مكتمل' => 'green',
        'ملغي' => 'red',
        'إعادة جدولة' => 'blue',
        'منشور' => 'green',
        'مسودة' => 'purple',
        'قيد المراجعة' => 'orange',
        'مرفوض' => 'red',
        'منشورة' => 'green',
        'تحت المراجعة' => 'orange',
        'مرفوضة' => 'red',
    ];

    $roleRawLabels = (array) data_get($chartPayload, 'roles.labels', []);
    $roleRawValues = (array) data_get($chartPayload, 'roles.values', []);
    $roleTotal = array_sum(array_map('intval', $roleRawValues));
    $roleItems = [];

    foreach ($roleRawLabels as $index => $rawLabel) {
        $key = trim((string) $rawLabel);
        $lowerKey = mb_strtolower($key, 'UTF-8');
        $value = (int) ($roleRawValues[$index] ?? 0);
        $percent = $roleTotal > 0 ? min(100, max(0, round(($value / $roleTotal) * 100))) : 0;

        $roleItems[] = [
            'label' => $roleLabelMap[$lowerKey] ?? $roleLabelMap[$key] ?? $key,
            'value' => $value,
            'percent' => $percent,
            'icon' => $roleIconMap[$lowerKey] ?? $roleIconMap[$key] ?? 'fa-user',
            'accent' => $accentClasses[$index % count($accentClasses)],
        ];
    }

    $appointmentRawLabels = (array) data_get($chartPayload, 'appointments_status.labels', []);
    $appointmentRawValues = (array) data_get($chartPayload, 'appointments_status.values', []);
    $appointmentStatusTotal = array_sum(array_map('intval', $appointmentRawValues));
    $appointmentStatusItems = [];

    foreach ($appointmentRawLabels as $index => $rawLabel) {
        $key = trim((string) $rawLabel);
        $value = (int) ($appointmentRawValues[$index] ?? 0);
        $percent = $appointmentStatusTotal > 0 ? min(100, max(0, round(($value / $appointmentStatusTotal) * 100))) : 0;
        $badge = $statusLabels[$key] ?? ['label' => $key, 'class' => 'secondary'];

        $appointmentStatusItems[] = [
            'label' => $badge['label'],
            'value' => $value,
            'percent' => $percent,
            'icon' => $appointmentIconMap[$key] ?? $appointmentIconMap[$badge['label']] ?? 'fa-calendar-day',
            'accent' => $classByStatus[$key] ?? $classByStatus[$badge['label']] ?? $accentClasses[$index % count($accentClasses)],
        ];
    }

    $articleRawLabels = (array) data_get($chartPayload, 'articles_status.labels', []);
    $articleRawValues = (array) data_get($chartPayload, 'articles_status.values', []);
    $articleStatusTotal = array_sum(array_map('intval', $articleRawValues));
    $articleStatusItems = [];

    foreach ($articleRawLabels as $index => $rawLabel) {
        $key = trim((string) $rawLabel);
        $value = (int) ($articleRawValues[$index] ?? 0);
        $percent = $articleStatusTotal > 0 ? min(100, max(0, round(($value / $articleStatusTotal) * 100))) : 0;
        $badge = $statusLabels[$key] ?? ['label' => $key, 'class' => 'secondary'];

        $articleStatusItems[] = [
            'label' => $badge['label'],
            'value' => $value,
            'percent' => $percent,
            'icon' => $articleIconMap[$key] ?? $articleIconMap[$badge['label']] ?? 'fa-file-lines',
            'accent' => $classByStatus[$key] ?? $classByStatus[$badge['label']] ?? $accentClasses[$index % count($accentClasses)],
        ];
    }

    $accentCssVars = [
        'green' => 'var(--et-primary)',
        'blue' => 'var(--et-blue)',
        'purple' => 'var(--et-purple)',
        'orange' => 'var(--et-orange)',
        'red' => 'var(--et-red)',
        'teal' => 'var(--et-teal)',
    ];

    $roleConicParts = [];
    $roleCursor = 0;
    foreach ($roleItems as $index => $item) {
        $degrees = $roleTotal > 0 ? (($item['value'] / $roleTotal) * 360) : 0;
        $end = $roleCursor + $degrees;
        $color = $accentCssVars[$item['accent']] ?? 'var(--et-primary)';
        $roleItems[$index]['color'] = $color;
        $roleItems[$index]['start'] = round($roleCursor, 2);
        $roleItems[$index]['end'] = round($end, 2);
        $roleConicParts[] = $color . ' ' . round($roleCursor, 2) . 'deg ' . round($end, 2) . 'deg';
        $roleCursor = $end;
    }
    $roleConicGradient = count($roleConicParts) ? implode(', ', $roleConicParts) : 'rgba(120,140,134,.18) 0deg 360deg';
    $roleActiveItem = $roleItems[0] ?? [
        'label' => 'لا توجد بيانات',
        'value' => 0,
        'percent' => 0,
        'icon' => 'fa-users',
        'accent' => 'green',
        'color' => 'var(--et-primary)',
    ];

    $articleConicParts = [];
    $articleCursor = 0;
    foreach ($articleStatusItems as $index => $item) {
        $degrees = $articleStatusTotal > 0 ? (($item['value'] / $articleStatusTotal) * 360) : 0;
        $end = $articleCursor + $degrees;
        $color = $accentCssVars[$item['accent']] ?? 'var(--et-primary)';
        $articleStatusItems[$index]['color'] = $color;
        $articleStatusItems[$index]['start'] = round($articleCursor, 2);
        $articleStatusItems[$index]['end'] = round($end, 2);
        $articleConicParts[] = $color . ' ' . round($articleCursor, 2) . 'deg ' . round($end, 2) . 'deg';
        $articleCursor = $end;
    }
    $articleConicGradient = count($articleConicParts) ? implode(', ', $articleConicParts) : 'rgba(120,140,134,.18) 0deg 360deg';
    $articleActiveItem = $articleStatusItems[0] ?? [
        'label' => 'لا توجد بيانات',
        'value' => 0,
        'percent' => 0,
        'icon' => 'fa-file-lines',
        'accent' => 'green',
        'color' => 'var(--et-primary)',
    ];

    $mealsToday = (int) ($stats['meals_today'] ?? 0);
    $mealsThisMonth = (int) ($stats['meals_this_month'] ?? 0);
    $caloriesThisMonth = (int) ($stats['calories_this_month'] ?? 0);
    $articlesAi = (int) ($stats['articles_ai'] ?? 0);
    $avgCaloriesPerMeal = $mealsThisMonth > 0 ? round($caloriesThisMonth / $mealsThisMonth) : 0;
    $aiMealsTodayRate = $mealsThisMonth > 0 ? min(100, max(0, round(($mealsToday / $mealsThisMonth) * 100))) : 0;
    $aiMealsMonthRate = ($mealsThisMonth + $articlesAi) > 0 ? min(100, max(0, round(($mealsThisMonth / max(1, $mealsThisMonth + $articlesAi)) * 100))) : 0;
    $aiCaloriesRate = min(100, max(0, round(($avgCaloriesPerMeal / 2500) * 100)));

    $aiItems = [
        [
            'key' => 'today',
            'title' => 'وجبات اليوم',
            'value' => number_format($mealsToday),
            'unit' => 'وجبة',
            'percent' => $aiMealsTodayRate,
            'accent' => 'green',
            'icon' => 'fa-bowl-food',
            'kicker' => 'نشاط اليوم',
            'description' => 'النسبة تمثل وجبات اليوم مقارنة بإجمالي وجبات هذا الشهر.',
            'meta_one' => 'وجبات اليوم: ' . number_format($mealsToday),
            'meta_two' => 'وجبات الشهر: ' . number_format($mealsThisMonth),
            'meta_three' => 'الحصة اليومية: ' . $aiMealsTodayRate . '%',
        ],
        [
            'key' => 'month',
            'title' => 'وجبات هذا الشهر',
            'value' => number_format($mealsThisMonth),
            'unit' => 'وجبة',
            'percent' => $aiMealsMonthRate,
            'accent' => 'blue',
            'icon' => 'fa-calendar-week',
            'kicker' => 'نشاط الشهر',
            'description' => 'النسبة تقيس مساهمة الوجبات ضمن نشاط التغذية والمحتوى الذكي.',
            'meta_one' => 'وجبات الشهر: ' . number_format($mealsThisMonth),
            'meta_two' => 'محتوى AI: ' . number_format($articlesAi),
            'meta_three' => 'مؤشر النشاط: ' . $aiMealsMonthRate . '%',
        ],
        [
            'key' => 'calories',
            'title' => 'سعرات هذا الشهر',
            'value' => number_format($caloriesThisMonth),
            'unit' => 'سعرة',
            'percent' => $aiCaloriesRate,
            'accent' => 'orange',
            'icon' => 'fa-fire-flame-curved',
            'kicker' => 'تحليل السعرات',
            'description' => 'النسبة مبنية على متوسط السعرات لكل وجبة مقارنة بمؤشر 2500 سعرة.',
            'meta_one' => 'إجمالي السعرات: ' . number_format($caloriesThisMonth),
            'meta_two' => 'متوسط الوجبة: ' . number_format($avgCaloriesPerMeal),
            'meta_three' => 'مؤشر السعرات: ' . $aiCaloriesRate . '%',
        ],
        [
            'key' => 'content',
            'title' => 'محتوى AI',
            'value' => number_format($articlesAi),
            'unit' => 'مقال',
            'percent' => $aiRate,
            'accent' => 'purple',
            'icon' => 'fa-robot',
            'kicker' => 'اعتماد الذكاء الاصطناعي',
            'description' => 'النسبة تمثل اعتماد المحتوى المولّد بالذكاء الاصطناعي داخل المحتوى الصحي.',
            'meta_one' => 'محتوى AI: ' . number_format($articlesAi),
            'meta_two' => 'اعتماد AI: ' . $aiRate . '%',
            'meta_three' => 'تصنيفات المقالات: ' . number_format($stats['article_categories'] ?? 0),
        ],
    ];

    $activeAiItem = $aiItems[0];

    $monthlyLabels = (array) data_get($chartPayload, 'monthly_users.labels', []);
    $monthlyUserValues = array_map('intval', (array) data_get($chartPayload, 'monthly_users.values', []));
    $monthlyAppointmentValues = array_map('intval', (array) data_get($chartPayload, 'monthly_appointments.values', []));
    $totalUsersSixMonths = array_sum($monthlyUserValues);
    $totalAppointmentsSixMonths = array_sum($monthlyAppointmentValues);
    $lastMonthIndex = max(count($monthlyLabels), count($monthlyUserValues), count($monthlyAppointmentValues)) - 1;
    $lastMonthLabel = $lastMonthIndex >= 0 ? ($monthlyLabels[$lastMonthIndex] ?? 'آخر شهر') : 'آخر شهر';
    $lastMonthUsers = $lastMonthIndex >= 0 ? (int) ($monthlyUserValues[$lastMonthIndex] ?? 0) : 0;
    $lastMonthAppointments = $lastMonthIndex >= 0 ? (int) ($monthlyAppointmentValues[$lastMonthIndex] ?? 0) : 0;
    $prevMonthUsers = $lastMonthIndex > 0 ? (int) ($monthlyUserValues[$lastMonthIndex - 1] ?? 0) : 0;
    $prevMonthAppointments = $lastMonthIndex > 0 ? (int) ($monthlyAppointmentValues[$lastMonthIndex - 1] ?? 0) : 0;
    $usersTrend = $prevMonthUsers > 0 ? round((($lastMonthUsers - $prevMonthUsers) / $prevMonthUsers) * 100) : ($lastMonthUsers > 0 ? 100 : 0);
    $appointmentsTrend = $prevMonthAppointments > 0 ? round((($lastMonthAppointments - $prevMonthAppointments) / $prevMonthAppointments) * 100) : ($lastMonthAppointments > 0 ? 100 : 0);
    $maxMonthlyUsers = max(1, !empty($monthlyUserValues) ? max($monthlyUserValues) : 1);
    $maxMonthlyAppointments = max(1, !empty($monthlyAppointmentValues) ? max($monthlyAppointmentValues) : 1);
@endphp

<div class="et-dashboard">
    <section class="et-hero-clean mb-4">
        <div class="et-hero-clean__header">
            <span data-time-greeting>مساء الخير</span>
            <em data-live-clock></em>
            @if($needsAttention > 0)
                <span class="et-hero-clean__alert">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <strong>{{ $needsAttention }}</strong> تحتاج انتباهك
                </span>
            @else
                <span class="et-hero-clean__alert et-hero-clean__alert--ok">
                    <i class="fa-solid fa-circle-check"></i>
                    كل شي تمام
                </span>
            @endif
        </div>

        <h1 class="et-hero-clean__title">إتزان بين يديك <span class="et-hero-mark">.</span></h1>

        @php
            $tickerMessages = [];
            if ($pendingDoctorApplications > 0) $tickerMessages[] = ['type' => 'warning', 'text' => $pendingDoctorApplications.' طلبات أطباء بانتظار المراجعة'];
            if ($articlesPendingReview > 0) $tickerMessages[] = ['type' => 'info', 'text' => $articlesPendingReview.' مقالات تحتاج مراجعة'];
            if (($appointmentsNeedAction ?? 0) > 0) $tickerMessages[] = ['type' => 'primary', 'text' => $appointmentsNeedAction.' مواعيد تحتاج تأكيد'];
            if ($unreadAdminMessages > 0) $tickerMessages[] = ['type' => 'purple', 'text' => $unreadAdminMessages.' رسائل دعم غير مقروءة'];
            if (empty($tickerMessages)) $tickerMessages[] = ['type' => 'primary', 'text' => 'كل شي تحت السيطرة 🎉'];
            $tickerMessages[] = ['type' => 'muted', 'text' => number_format($stats['total'] ?? 0).' حساب مسجّل'];
        @endphp

        <div class="et-hero-ticker" aria-live="polite" data-hero-ticker>
            <i class="fa-solid fa-bolt" data-ticker-icon></i>
            <div class="et-hero-ticker__track">
                @foreach ($tickerMessages as $tIndex => $tMsg)
                    <span class="et-hero-ticker__item {{ $tIndex === 0 ? 'is-active' : '' }} et-hero-ticker__item--{{ $tMsg['type'] }}" data-tone="{{ $tMsg['type'] }}">{{ $tMsg['text'] }}</span>
                @endforeach
            </div>
            @if (count($tickerMessages) > 1)
                <div class="et-hero-ticker__pager" role="tablist" aria-label="تنقل بين التنبيهات">
                    @foreach ($tickerMessages as $tIndex => $tMsg)
                        <button type="button" class="et-hero-ticker__pager-dot {{ $tIndex === 0 ? 'is-active' : '' }}" data-ticker-goto="{{ $tIndex }}" aria-label="التنبيه {{ $tIndex + 1 }} من {{ count($tickerMessages) }}"></button>
                    @endforeach
                </div>
            @endif
        </div>

        @php
            $heroTrends = $heroTrends ?? [];

            // كل مؤشر إله لون هوية ثابت (مش لون بيتغير حسب "منيح/مش منيح")
            // عشان الألوان تضل متناسقة وما ترجع رمادية لو الاتجاه نزل.
            $heroStatCards = [
                [
                    'label' => 'موعد اليوم',
                    'value' => $appointmentsToday,
                    'url' => Route::has('admin.dashboard') ? route('admin.dashboard') : url('/admin/dashboard'),
                    'trend' => $heroTrends['appointments'] ?? null,
                    'accent' => 'primary',
                ],
                [
                    'label' => 'طلب طبيب',
                    'value' => $pendingDoctorApplications,
                    'url' => $doctorApplicationsUrl,
                    'trend' => $heroTrends['doctor_applications'] ?? null,
                    'accent' => 'blue',
                ],
                [
                    'label' => 'مقال منشور',
                    'value' => $stats['articles_published'] ?? 0,
                    'url' => $articlesUrl,
                    'trend' => $heroTrends['articles_published'] ?? null,
                    'accent' => 'purple',
                ],
                [
                    'label' => 'رسالة دعم',
                    'value' => $unreadAdminMessages,
                    'url' => $messagesUrl,
                    'trend' => $heroTrends['support_messages'] ?? null,
                    'accent' => 'orange',
                ],
            ];
        @endphp

        <div class="et-hero-stats">
            @foreach ($heroStatCards as $card)
                @php
                    $trend = $card['trend'];
                    $direction = $trend['direction'] ?? 'flat';
                    $percent = $trend['percent'] ?? 0;

                    $trendIcon = match ($direction) {
                        'up' => 'fa-arrow-trend-up',
                        'down' => 'fa-arrow-trend-down',
                        default => 'fa-minus',
                    };
                @endphp
                <a href="{{ $card['url'] }}" class="et-hero-stat et-hero-stat--{{ $card['accent'] }}">
                    <span class="et-hero-stat__label">{{ $card['label'] }}</span>
                    <span class="et-hero-stat__value">{{ number_format($card['value']) }}</span>
                    <span class="et-hero-stat__trend et-hero-stat__trend--{{ $direction }}">
                        <i class="fa-solid {{ $trendIcon }}"></i>
                        {{ $percent }}٪
                        <small>مقارنة بالأسبوع الماضي</small>
                    </span>
                </a>
            @endforeach
        </div>
    </section>


    <section class="et-metrics-orbit mb-4">
        <article class="et-orb-card accent-blue" style="--ring: {{ $newUsersRate }}%;">
            <div class="et-orb-ring">
                <div class="et-orb-ring-inner">
                    <i class="fa-solid fa-users"></i>
                    <small>{{ $newUsersRate }}%</small>
                </div>
            </div>
            <div class="et-orb-body">
                <span>إجمالي المستخدمين</span>
                <strong>{{ number_format($totalUsers) }}</strong>
                <p>هذا الشهر: {{ number_format($newUsersThisMonth) }} مستخدم جديد.</p>
                <em class="et-orb-mini"><i class="fa-solid fa-arrow-trend-up"></i> نمو الحسابات</em>
            </div>
        </article>

        <article class="et-orb-card accent-green" style="--ring: {{ $profileRate }}%;">
            <div class="et-orb-ring">
                <div class="et-orb-ring-inner">
                    <i class="fa-solid fa-heart-pulse"></i>
                    <small>{{ $profileRate }}%</small>
                </div>
            </div>
            <div class="et-orb-body">
                <span>ملفات المرضى</span>
                <strong>{{ number_format($patientProfiles) }}</strong>
                <p>اكتمال الملفات: {{ $profileRate }}% من بيانات المرضى.</p>
                <em class="et-orb-mini"><i class="fa-solid fa-shield-heart"></i> صحة البيانات</em>
            </div>
        </article>

        <article class="et-orb-card accent-orange" style="--ring: {{ $appointmentsRate }}%;">
            <div class="et-orb-ring">
                <div class="et-orb-ring-inner">
                    <i class="fa-solid fa-calendar-check"></i>
                    <small>{{ $appointmentsRate }}%</small>
                </div>
            </div>
            <div class="et-orb-body">
                <span>المواعيد الطبية</span>
                <strong>{{ number_format($appointmentsTotal) }}</strong>
                <p>اليوم: {{ $appointmentsToday }} | القادمة: {{ $appointmentsUpcoming }}.</p>
                <em class="et-orb-mini"><i class="fa-solid fa-clock"></i> حركة المواعيد</em>
            </div>
        </article>

        <article class="et-orb-card accent-purple" style="--ring: {{ $ratingRate }}%;">
            <div class="et-orb-ring">
                <div class="et-orb-ring-inner">
                    <i class="fa-solid fa-star"></i>
                    <small>{{ $ratingRate }}%</small>
                </div>
            </div>
            <div class="et-orb-body">
                <span>تقييم الأطباء</span>
                <strong>{{ $averageDoctorRating }} <small>/ 5</small></strong>
                <p>عدد التقييمات: {{ number_format($stats['doctor_reviews'] ?? 0) }}.</p>
                <em class="et-orb-mini"><i class="fa-solid fa-award"></i> جودة الخدمة</em>
            </div>
        </article>
    </section>

    <section class="et-ai-command mb-4">
        <div class="et-ai-command-head">
            <div>
                <span class="et-ai-kicker"><i class="fa-solid fa-wand-magic-sparkles"></i> Nutrition AI Hub</span>
                <h2><i class="fa-solid fa-robot"></i> التغذية والذكاء الاصطناعي</h2>
            </div>

            <div class="et-ai-total-orb" style="--ring: {{ $aiRate }}%;">
                <span>اعتماد AI</span>
                <strong>{{ $aiRate }}%</strong>
            </div>
        </div>

        <div class="et-ai-command-grid">
            <div class="et-ai-orbit-list">
                @foreach($aiItems as $item)
                    <button
                        type="button"
                        class="et-ai-orb accent-{{ $item['accent'] }} {{ $loop->first ? 'is-active' : '' }}"
                        style="--ring: {{ $item['percent'] }}%;"
                        data-ai-orb
                        data-title="{{ $item['title'] }}"
                        data-value="{{ $item['value'] }}"
                        data-unit="{{ $item['unit'] }}"
                        data-percent="{{ $item['percent'] }}%"
                        data-kicker="{{ $item['kicker'] }}"
                        data-description="{{ $item['description'] }}"
                        data-meta-one="{{ $item['meta_one'] }}"
                        data-meta-two="{{ $item['meta_two'] }}"
                        data-meta-three="{{ $item['meta_three'] }}"
                    >
                        <span class="et-ai-orb-ring">
                            <i class="fa-solid {{ $item['icon'] }}"></i>
                            <b>{{ $item['percent'] }}%</b>
                        </span>

                        <span class="et-ai-orb-copy">
                            <strong>{{ $item['title'] }}</strong>
                            <small>{{ $item['value'] }} {{ $item['unit'] }}</small>
                        </span>
                    </button>
                @endforeach
            </div>

            <aside class="et-ai-detail-panel" data-ai-detail-panel>
                <span class="et-ai-detail-kicker" data-ai-detail-kicker>{{ $activeAiItem['kicker'] }}</span>
                <h3 data-ai-detail-title>{{ $activeAiItem['title'] }}</h3>

                <div class="et-ai-detail-value">
                    <strong data-ai-detail-value>{{ $activeAiItem['value'] }}</strong>
                    <small data-ai-detail-unit>{{ $activeAiItem['unit'] }}</small>
                </div>

                <p data-ai-detail-description>{{ $activeAiItem['description'] }}</p>

                <div class="et-ai-detail-progress">
                    <div>
                        <span>النسبة</span>
                        <b data-ai-detail-percent>{{ $activeAiItem['percent'] }}%</b>
                    </div>
                    <div class="et-ai-detail-track"><span data-ai-detail-bar style="width: {{ $activeAiItem['percent'] }}%"></span></div>
                </div>

                <div class="et-ai-detail-meta">
                    <span data-ai-detail-meta-one>{{ $activeAiItem['meta_one'] }}</span>
                    <span data-ai-detail-meta-two>{{ $activeAiItem['meta_two'] }}</span>
                    <span data-ai-detail-meta-three>{{ $activeAiItem['meta_three'] }}</span>
                </div>
            </aside>
        </div>
    </section>

    <section class="et-chart-grid growth mb-4">
        <div class="et-card et-chart-card et-growth-card">
            <div class="et-section-title mb-3">
                <div>
                    <h2><i class="fa-solid fa-chart-area"></i> نشاط المنصة خلال آخر 6 أشهر</h2>
                </div>
                <span class="et-growth-month-pill"><i class="fa-regular fa-calendar"></i> {{ $lastMonthLabel }}</span>
            </div>

            <div class="et-growth-command">
                <div class="et-growth-spotlight">
                    <span>إجمالي النشاط خلال 6 أشهر</span>
                    <strong>{{ number_format($totalUsersSixMonths + $totalAppointmentsSixMonths) }}</strong>
                    <p>يشمل المستخدمين الجدد والمواعيد المسجلة خلال الفترة المعروضة.</p>
                </div>

                <div class="et-growth-kpis">
                    <div class="et-growth-kpi accent-green">
                        <i class="fa-solid fa-user-plus"></i>
                        <span>مستخدمون جدد</span>
                        <b>{{ number_format($totalUsersSixMonths) }}</b>
                        <small class="{{ $usersTrend >= 0 ? 'up' : 'down' }}">{{ $usersTrend >= 0 ? '+' : '' }}{{ $usersTrend }}% آخر شهر</small>
                    </div>

                    <div class="et-growth-kpi accent-blue">
                        <i class="fa-solid fa-calendar-check"></i>
                        <span>المواعيد</span>
                        <b>{{ number_format($totalAppointmentsSixMonths) }}</b>
                        <small class="{{ $appointmentsTrend >= 0 ? 'up' : 'down' }}">{{ $appointmentsTrend >= 0 ? '+' : '' }}{{ $appointmentsTrend }}% آخر شهر</small>
                    </div>

                    <div class="et-growth-kpi accent-purple">
                        <i class="fa-solid fa-bolt"></i>
                        <span>آخر شهر</span>
                        <b>{{ number_format($lastMonthUsers + $lastMonthAppointments) }}</b>
                        <small>{{ number_format($lastMonthUsers) }} مستخدم | {{ number_format($lastMonthAppointments) }} موعد</small>
                    </div>
                </div>
            </div>

            <div class="et-month-strip">
                @forelse($monthlyLabels as $index => $monthLabel)
                    @php
                        $monthUsers = (int) ($monthlyUserValues[$index] ?? 0);
                        $monthAppointments = (int) ($monthlyAppointmentValues[$index] ?? 0);
                        $monthUsersRate = min(100, max(0, round(($monthUsers / $maxMonthlyUsers) * 100)));
                        $monthAppointmentsRate = min(100, max(0, round(($monthAppointments / $maxMonthlyAppointments) * 100)));
                    @endphp

                    <article class="et-month-card">
                        <b>{{ $monthLabel }}</b>
                        <div class="et-month-bars">
                            <span class="users" style="--w: {{ $monthUsersRate }}%"><i></i></span>
                            <span class="appointments" style="--w: {{ $monthAppointmentsRate }}%"><i></i></span>
                        </div>
                        <small>{{ number_format($monthUsers) }} مستخدم | {{ number_format($monthAppointments) }} موعد</small>
                    </article>
                @empty
                    <div class="et-empty">لا توجد بيانات شهرية لعرض نشاط المنصة.</div>
                @endforelse
            </div>

            <div class="et-chart-wrap large"><canvas id="growthChart"></canvas></div>
            <div class="et-chart-note"><i class="fa-solid fa-circle-info"></i> الرسم والخط الزمني يعتمدان على بيانات آخر 6 أشهر من قاعدة البيانات.</div>
        </div>
    </section>

    <section class="et-chart-grid two mb-4">
        <div class="et-card et-chart-card">
            <div class="et-section-title mb-3">
                <div>
                    <h2><i class="fa-solid fa-users-gear"></i> توزيع الحسابات</h2>
                </div>
            </div>

            <div class="et-single-donut" data-segment-panel="rolesDonut">
                <div class="et-single-donut-main">
                    <button type="button" class="et-donut-orb" style="--donut-bg: conic-gradient({{ $roleConicGradient }});" data-donut-cycle="rolesDonut" data-segments='@json($roleItems)' aria-label="توزيع الحسابات - اضغط على أي لون">
                        <span class="et-donut-core">
                            <i data-donut-icon class="fa-solid {{ $roleActiveItem['icon'] }}"></i>
                            <b data-donut-percent>{{ $roleActiveItem['percent'] }}%</b>
                            <small>{{ number_format($roleTotal) }} حساب</small>
                        </span>
                    </button>

                    <div class="et-donut-detail">
                        <span>النوع المحدد</span>
                        <h3 data-donut-title>{{ $roleActiveItem['label'] }}</h3>
                        <strong><span data-donut-value>{{ number_format($roleActiveItem['value']) }}</span> <small data-donut-unit>حساب</small></strong>
                        <p data-donut-description>يمثل {{ $roleActiveItem['percent'] }}% من إجمالي الحسابات.</p>
                        <div class="et-donut-track"><span data-donut-bar style="width: {{ $roleActiveItem['percent'] }}%"></span></div>
                    </div>
                </div>

                <div class="et-segment-pills">
                    @forelse($roleItems as $item)
                        <button
                            type="button"
                            class="et-segment-pill accent-{{ $item['accent'] }} {{ $loop->first ? 'is-active' : '' }}"
                            data-segment-trigger
                            data-target="rolesDonut"
                            data-title="{{ $item['label'] }}"
                            data-value="{{ number_format($item['value']) }}"
                            data-unit="حساب"
                            data-percent="{{ $item['percent'] }}%"
                            data-icon="{{ $item['icon'] }}"
                            data-description="يمثل {{ $item['percent'] }}% من إجمالي الحسابات."
                        >
                            <i class="et-segment-color"></i>
                            <span class="et-segment-name">{{ $item['label'] }}</span>
                            <small class="et-segment-count">{{ number_format($item['value']) }} حساب</small>
                            <b class="et-segment-percent">{{ $item['percent'] }}%</b>
                        </button>
                    @empty
                        <div class="et-empty">لا توجد بيانات لتوزيع الحسابات.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="et-card et-chart-card">
            <div class="et-section-title mb-3">
                <div>
                    <h2><i class="fa-solid fa-clock"></i> ساعات الذروة</h2>
                    <p>أكتر ساعات اليوم نشاطاً (حجوزات + تسجيل وجبات)</p>
                </div>
            </div>

            @php
                $peakHours = $peakHours ?? ['labels' => [], 'values' => [], 'max' => 1, 'peak_index' => 0, 'peak_label' => '—', 'has_data' => false];
                $peakHoursLabels = $peakHours['labels'];
                $peakHoursValues = $peakHours['values'];
                $peakHoursMax = $peakHours['max'];
                $peakBusiestIndex = $peakHours['peak_index'];
            @endphp

            <div class="et-peak-stat">
                <strong>{{ $peakHours['has_data'] ? $peakHours['peak_label'] : '—' }}</strong>
                <span>{{ $peakHours['has_data'] ? 'أكتر وقت نشاطاً بالمنصة (بيانات حقيقية)' : 'ما في مواعيد كافية لحساب وقت الذروة بعد' }}</span>
            </div>

            <div class="et-peak-bars">
                @forelse ($peakHoursValues as $i => $val)
                    <div class="et-peak-bar-col">
                        <div class="et-peak-bar {{ $i === $peakBusiestIndex && $peakHours['has_data'] ? 'is-peak' : '' }}" style="--h: {{ $val > 0 ? round(($val / $peakHoursMax) * 100) : 2 }}%;" title="{{ $val }} موعد"></div>
                        <small>{{ $peakHoursLabels[$i] }}</small>
                    </div>
                @empty
                    <div class="et-empty">لا توجد بيانات مواعيد بعد.</div>
                @endforelse
            </div>
        </div>
    </section>
    <section class="et-chart-grid two mb-4">
        <div class="et-card et-chart-card">
            <div class="et-section-title mb-3">
                <div>
                    <h2><i class="fa-solid fa-calendar-days"></i> حالات المواعيد</h2>
                </div>
            </div>

            <div class="et-peak-stat">
                <strong>{{ number_format($appointmentStatusTotal) }}</strong>
                <span>إجمالي حالات المواعيد المسجّلة</span>
            </div>

            @php
                $apptMax = collect($appointmentStatusItems)->max('value') ?: 1;
            @endphp

            <div class="et-peak-bars et-status-bars">
                @forelse($appointmentStatusItems as $item)
                    <div class="et-peak-bar-col">
                        <div class="et-peak-bar accent-{{ $item['accent'] }}" style="--h: {{ $item['value'] > 0 ? round(($item['value'] / $apptMax) * 100) : 2 }}%;" title="{{ $item['label'] }}: {{ $item['value'] }}"></div>
                        <small>{{ $item['label'] }}</small>
                        <b>{{ number_format($item['value']) }}</b>
                    </div>
                @empty
                    <div class="et-empty">لا توجد بيانات لحالات المواعيد.</div>
                @endforelse
            </div>
        </div>

        <div class="et-card et-chart-card">
            <div class="et-section-title mb-3">
                <div>
                    <h2><i class="fa-solid fa-file-medical"></i> حالة المحتوى الصحي</h2>
                </div>
            </div>

            <div class="et-chart-insights">
                <div class="et-mini-metric">
                    <span>قيد المراجعة</span>
                    <b>{{ number_format($articlesPendingReview) }}</b>
                </div>
                <div class="et-mini-metric">
                    <span>محتوى AI</span>
                    <b>{{ number_format($stats['articles_ai'] ?? 0) }}</b>
                </div>
                <div class="et-mini-metric">
                    <span>مجموع الحالات</span>
                    <b>{{ number_format($articleStatusTotal) }}</b>
                </div>
            </div>

            <div class="et-single-donut compact" data-segment-panel="articlesDonut">
                <div class="et-single-donut-main">
                    <button type="button" class="et-donut-orb" style="--donut-bg: conic-gradient({{ $articleConicGradient }});" data-donut-cycle="articlesDonut" data-segments='@json($articleStatusItems)' aria-label="حالة المحتوى الصحي - اضغط على أي لون">
                        <span class="et-donut-core">
                            <i data-donut-icon class="fa-solid {{ $articleActiveItem['icon'] }}"></i>
                            <b data-donut-percent>{{ $articleActiveItem['percent'] }}%</b>
                            <small>{{ number_format($articleStatusTotal) }} مقال</small>
                        </span>
                    </button>

                    <div class="et-donut-detail">
                        <span>الحالة المحددة</span>
                        <h3 data-donut-title>{{ $articleActiveItem['label'] }}</h3>
                        <strong><span data-donut-value>{{ number_format($articleActiveItem['value']) }}</span> <small data-donut-unit>مقال</small></strong>
                        <p data-donut-description>يمثل {{ $articleActiveItem['percent'] }}% من إجمالي المقالات.</p>
                        <div class="et-donut-track"><span data-donut-bar style="width: {{ $articleActiveItem['percent'] }}%"></span></div>
                    </div>
                </div>

                <div class="et-segment-pills">
                    @forelse($articleStatusItems as $item)
                        <button
                            type="button"
                            class="et-segment-pill accent-{{ $item['accent'] }} {{ $loop->first ? 'is-active' : '' }}"
                            data-segment-trigger
                            data-target="articlesDonut"
                            data-title="{{ $item['label'] }}"
                            data-value="{{ number_format($item['value']) }}"
                            data-unit="مقال"
                            data-percent="{{ $item['percent'] }}%"
                            data-icon="{{ $item['icon'] }}"
                            data-description="يمثل {{ $item['percent'] }}% من إجمالي المقالات."
                        >
                            <i class="et-segment-color"></i>
                            <span class="et-segment-name">{{ $item['label'] }}</span>
                            <small class="et-segment-count">{{ number_format($item['value']) }} مقال</small>
                            <b class="et-segment-percent">{{ $item['percent'] }}%</b>
                        </button>
                    @empty
                        <div class="et-empty">لا توجد بيانات لحالة المحتوى الصحي.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    <section class="et-ops-grid mb-4">
        <div class="et-card">
            <div class="et-section-title mb-3">
                <div>
                    <h2><i class="fa-solid fa-trophy"></i> أفضل الأطباء نشاطًا</h2>
                </div>
            </div>

            <div class="et-leaderboard">
                @forelse($topDoctorsPreview as $index => $doctor)
                    @php
                        $doctorName = data_get($doctor, 'name', 'طبيب بدون اسم');
                        $doctorEmail = data_get($doctor, 'email', 'لا يوجد بريد');
                        $patientsCount = (int) data_get($doctor, 'patients_count', 0);
                        $barRate = $doctorMaxPatients > 0 ? min(100, max(0, round(($patientsCount / $doctorMaxPatients) * 100))) : 0;
                    @endphp

                    <article class="et-doctor-rank-card" style="--bar: {{ $barRate }}%;">
                        <div class="et-rank-badge">{{ $index + 1 }}</div>

                        <div class="et-rank-info">
                            <b>{{ $doctorName }}</b>
                            <span>{{ $doctorEmail }}</span>
                        </div>

                        <div class="et-rank-score">
                            <b>{{ $patientsCount }}</b>
                            <span>مريض</span>
                        </div>

                        <div class="et-rank-bar"><span></span></div>
                    </article>
                @empty
                    <div class="et-empty">لا يوجد أطباء مرتبطون بمرضى حتى الآن.</div>
                @endforelse
            </div>
        </div>

        <div class="et-card">
            <div class="et-section-title mb-3">
                <div>
                    <h2><i class="fa-solid fa-id-card-clip"></i> آخر طلبات انضمام الأطباء</h2>
                </div>
                <a href="{{ $doctorApplicationsUrl }}" class="et-link">عرض الكل</a>
            </div>

            <div class="et-review-queue">
                @forelse($latestApplicationsPreview as $app)
                    @php
                        $badge = $statusLabels[data_get($app, 'status', 'pending')] ?? ['label' => data_get($app, 'status', 'غير معروف'), 'class' => 'secondary'];
                        $doctorName = data_get($app, 'full_name') ?? data_get($app, 'name') ?? 'طبيب متقدم';
                        $doctorInitial = mb_substr($doctorName, 0, 1, 'UTF-8');
                        $doctorEmail = data_get($app, 'email');
                    @endphp

                    <article class="et-review-card status-{{ $badge['class'] }}">
                        <div class="et-review-main">
                            <div class="et-avatar et-avatar-doctor">{{ $doctorInitial }}</div>

                            <div class="et-review-person">
                                <div class="et-review-titleline">
                                    <h3>{{ $doctorName }}</h3>
                                    <span class="et-status {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                                </div>

                                @if(!empty($doctorEmail))
                                    <button type="button" class="et-email" data-copy-email="{{ $doctorEmail }}">
                                        <span>{{ $doctorEmail }}</span>
                                        <i class="fa-regular fa-copy"></i>
                                    </button>
                                @else
                                    <span class="et-email">لا يوجد بريد</span>
                                @endif
                            </div>
                        </div>

                        <div class="et-review-meta">
                            <div>
                                <span>التخصص</span>
                                <b>{{ data_get($app, 'specialty', 'غير محدد') }}</b>
                            </div>
                            <div>
                                <span>سنوات الخبرة</span>
                                <b>{{ data_get($app, 'experience_years', 0) }} سنوات</b>
                            </div>
                            <div>
                                <span>نوع الطلب</span>
                                <b>انضمام طبيب</b>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="et-empty">لا توجد طلبات أطباء بعد.</div>
                @endforelse
            </div>
        </div>
    </section>

    <section class="et-activity-grid mb-4">
        <div class="et-card">
            <div class="et-section-title mb-3">
                <div>
                    <h2><i class="fa-solid fa-user-plus"></i> آخر المستخدمين</h2>
                </div>
                <a href="{{ $usersUrl }}" class="et-link">إدارة المستخدمين</a>
            </div>

            <div class="et-feed-list">
                @forelse($latestUsersPreview as $user)
                    @php
                        $userName = data_get($user, 'name', 'مستخدم');
                        $userInitial = mb_substr($userName, 0, 1, 'UTF-8');
                        $userRole = data_get($user, 'role', 'user');
                        $roleLabel = $roleLabels[$userRole] ?? $userRole;
                        $roleClass = $roleClasses[$userRole] ?? 'secondary';
                        $userEmail = data_get($user, 'email');
                        $userDate = !empty(data_get($user, 'created_at')) ? \Carbon\Carbon::parse(data_get($user, 'created_at'))->format('Y-m-d') : '-';
                    @endphp

                    <article class="et-feed-item">
                        <div class="et-avatar et-avatar-user">{{ $userInitial }}</div>

                        <div class="et-feed-body">
                            <div class="et-feed-top">
                                <h3>{{ $userName }}</h3>
                                <span class="et-status {{ $roleClass }}">{{ $roleLabel }}</span>
                            </div>

                            @if(!empty($userEmail))
                                <button type="button" class="et-email" data-copy-email="{{ $userEmail }}">
                                    <span>{{ $userEmail }}</span>
                                    <i class="fa-regular fa-copy"></i>
                                </button>
                            @else
                                <span class="et-email">لا يوجد بريد</span>
                            @endif
                        </div>

                        <time class="et-date-pill">{{ $userDate }}</time>
                    </article>
                @empty
                    <div class="et-empty">لا يوجد مستخدمون بعد.</div>
                @endforelse
            </div>
        </div>

        <div class="et-card">
            <div class="et-section-title mb-3">
                <div>
                    <h2><i class="fa-solid fa-newspaper"></i> آخر المقالات الصحية</h2>
                </div>
                <a href="{{ $articlesUrl }}" class="et-link">إدارة المقالات</a>
            </div>

            <div class="et-content-list">
                @forelse($latestArticlesPreview as $article)
                    @php
                        $badge = $statusLabels[data_get($article, 'status', 'draft')] ?? ['label' => data_get($article, 'status', 'مسودة'), 'class' => 'secondary'];
                        $articleDate = !empty(data_get($article, 'created_at')) ? \Carbon\Carbon::parse(data_get($article, 'created_at'))->format('Y-m-d') : '-';
                        $isAi = !empty(data_get($article, 'generated_by_ai'));
                    @endphp

                    <article class="et-content-item {{ $isAi ? 'is-ai' : 'is-admin' }}">
                        <div class="et-content-icon">
                            <i class="fa-solid {{ $isAi ? 'fa-robot' : 'fa-user-pen' }}"></i>
                        </div>

                        <div class="et-content-body">
                            <h3>{{ data_get($article, 'title', 'مقال بدون عنوان') }}</h3>

                            <div class="et-content-meta">
                                <span class="et-meta-chip">
                                    <i class="fa-regular fa-calendar"></i>
                                    {{ $articleDate }}
                                </span>

                                <span class="et-meta-chip {{ $isAi ? 'ai' : 'admin' }}">
                                    <i class="fa-solid {{ $isAi ? 'fa-robot' : 'fa-pen-nib' }}"></i>
                                    {{ $isAi ? 'AI' : (data_get($article, 'source', 'admin')) }}
                                </span>

                                <span class="et-status {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="et-empty">لا توجد مقالات بعد.</div>
                @endforelse
            </div>
        </div>
    </section>

    <section class="et-kpi-strip mb-2">
        <div class="et-kpi-card accent-blue">
            <div class="et-kpi-head">
                <div class="et-kpi-icon"><i class="fa-solid fa-comments"></i></div>
            </div>
            <span>المحادثات المفتوحة</span>
            <b>{{ number_format($stats['open_conversations'] ?? 0) }}</b>
            <small>تحتاج متابعة دورية</small>
        </div>

        <div class="et-kpi-card accent-purple">
            <div class="et-kpi-head">
                <div class="et-kpi-icon"><i class="fa-solid fa-message"></i></div>
            </div>
            <span>إجمالي الرسائل</span>
            <b>{{ number_format($stats['messages_total'] ?? 0) }}</b>
            <small>داخل محادثات المنصة</small>
        </div>

        <div class="et-kpi-card accent-green">
            <div class="et-kpi-head">
                <div class="et-kpi-icon"><i class="fa-solid fa-list-check"></i></div>
            </div>
            <span>مهام المرضى المكتملة</span>
            <b>{{ number_format($tasksCompleted) }} / {{ number_format($tasksTotal) }}</b>
            <small>{{ $tasksRate }}% نسبة الإنجاز</small>
            <div class="et-progress"><span style="width: {{ $tasksRate }}%"></span></div>
        </div>

        <div class="et-kpi-card accent-orange">
            <div class="et-kpi-head">
                <div class="et-kpi-icon"><i class="fa-solid fa-user-doctor"></i></div>
            </div>
            <span>طلبات ربط طبيب معلقة</span>
            <b>{{ number_format($stats['patient_doctor_requests_pending'] ?? 0) }}</b>
            <small>بانتظار قرار الطبيب/الإدارة</small>
        </div>

        <div class="et-kpi-card accent-blue">
            <div class="et-kpi-head">
                <div class="et-kpi-icon"><i class="fa-solid fa-stethoscope"></i></div>
            </div>
            <span>الأقسام الطبية</span>
            <b>{{ number_format($stats['specialties'] ?? 0) }}</b>
            <small>تخصصات متاحة داخل إتزان</small>
        </div>

        <div class="et-kpi-card accent-green">
            <div class="et-kpi-head">
                <div class="et-kpi-icon"><i class="fa-solid fa-layer-group"></i></div>
            </div>
            <span>تصنيفات المقالات</span>
            <b>{{ number_format($stats['article_categories'] ?? 0) }}</b>
            <small>لتنظيم المحتوى الصحي</small>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // نسخ الإيميل بلمسة واحدة بدل ما نفتح برنامج بريد النظام القبيح
    (function copyEmailButtons() {
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('[data-copy-email]');
            if (!btn) return;

            const email = btn.getAttribute('data-copy-email');
            navigator.clipboard?.writeText(email).then(function () {
                btn.classList.add('is-copied');
                const icon = btn.querySelector('i');
                if (icon) icon.className = 'fa-solid fa-check';
                setTimeout(function () {
                    btn.classList.remove('is-copied');
                    if (icon) icon.className = 'fa-regular fa-copy';
                }, 1600);
            });
        });
    })();

    // Hero: تيكر متحرك يبدّل بين الرسائل الحية كل بضع ثواني
    (function heroTicker() {
        const wrap = document.querySelector('[data-hero-ticker]');
        const items = document.querySelectorAll('.et-hero-ticker__item');
        const dots = document.querySelectorAll('.et-hero-ticker__pager-dot');
        if (!wrap || !items.length) return;

        const toneClass = (tone) => 'et-hero-ticker--tone-' + (tone || 'primary');

        function applyTone(index) {
            const tone = items[index]?.dataset.tone || 'primary';
            wrap.className = wrap.className.replace(/et-hero-ticker--tone-\S+/g, '').trim();
            wrap.classList.add(toneClass(tone));
        }

        function goTo(index) {
            items[i]?.classList.remove('is-active');
            dots[i]?.classList.remove('is-active');
            i = index;
            items[i]?.classList.add('is-active');
            dots[i]?.classList.add('is-active');
            applyTone(i);
        }

        let i = 0;
        applyTone(0);

        let timer = setInterval(function () {
            goTo((i + 1) % items.length);
        }, 3200);

        dots.forEach(function (dot) {
            dot.addEventListener('click', function () {
                clearInterval(timer);
                goTo(parseInt(dot.dataset.tickerGoto, 10));
                timer = setInterval(function () {
                    goTo((i + 1) % items.length);
                }, 3200);
            });
        });
    })();

    // Hero: تحية حسب وقت اليوم + ساعة حية
    (function heroGreetingClock() {
        const greetingEl = document.querySelector('[data-time-greeting]');
        const clockEl = document.querySelector('[data-live-clock]');
        if (!greetingEl && !clockEl) return;

        function update() {
            const now = new Date();
            const h = now.getHours();
            if (greetingEl) {
                greetingEl.textContent = h < 12 ? 'صباح الخير' : (h < 18 ? 'مساء الخير' : 'مساء النور');
            }
            if (clockEl) {
                clockEl.textContent = now.toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit' });
            }
        }
        update();
        setInterval(update, 60000);
    })();

    if (typeof Chart === 'undefined') {
        return;
    }

    const chartData = @json($chartPayload);

    function cssVar(name, fallback = '') {
        const bodyValue = getComputedStyle(document.body).getPropertyValue(name).trim();
        const rootValue = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
        return bodyValue || rootValue || fallback;
    }

    function hexToRgba(hex, alpha) {
        if (!hex || !hex.startsWith('#')) return hex;
        let value = hex.replace('#', '');
        if (value.length === 3) {
            value = value.split('').map(ch => ch + ch).join('');
        }
        const r = parseInt(value.substring(0, 2), 16);
        const g = parseInt(value.substring(2, 4), 16);
        const b = parseInt(value.substring(4, 6), 16);
        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    }

    const textColor = cssVar('--et-text', '#102f2a');
    const titleColor = cssVar('--et-title', '#061f1b');
    const mutedColor = cssVar('--et-muted', '#4f6962');
    const surfaceColor = cssVar('--et-surface', '#ffffff');
    const borderColor = cssVar('--et-border', 'rgba(21,155,117,.18)');
    const gridColor = borderColor;

    const palette = [
        cssVar('--et-primary', '#159b75'),
        cssVar('--et-blue', '#2fafe4'),
        cssVar('--et-purple', '#8b5cf6'),
        cssVar('--et-orange', '#f3a325'),
        cssVar('--et-red', '#ef4444'),
        cssVar('--et-teal', '#14b8a6')
    ];

    Chart.defaults.font.family = 'Tajawal, Cairo, sans-serif';
    Chart.defaults.color = textColor;

    function makeGradient(ctx, color) {
        const gradient = ctx.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, hexToRgba(color, .34));
        gradient.addColorStop(1, hexToRgba(color, .04));
        return gradient;
    }

    const legendBottom = {
        position: 'bottom',
        rtl: true,
        labels: {
            usePointStyle: true,
            pointStyle: 'circle',
            boxWidth: 9,
            boxHeight: 9,
            padding: 18,
            color: textColor,
            font: {
                size: 12,
                weight: '700',
                family: 'Tajawal, Cairo, sans-serif'
            }
        }
    };

    const tooltipStyle = {
        rtl: true,
        backgroundColor: surfaceColor,
        titleColor: titleColor,
        bodyColor: textColor,
        borderColor: borderColor,
        borderWidth: 1,
        padding: 12,
        displayColors: true,
        usePointStyle: true,
        boxPadding: 5
    };

    const aiOrbs = document.querySelectorAll('[data-ai-orb]');
    const aiPanel = document.querySelector('[data-ai-detail-panel]');

    if (aiOrbs.length && aiPanel) {
        const fields = {
            kicker: aiPanel.querySelector('[data-ai-detail-kicker]'),
            title: aiPanel.querySelector('[data-ai-detail-title]'),
            value: aiPanel.querySelector('[data-ai-detail-value]'),
            unit: aiPanel.querySelector('[data-ai-detail-unit]'),
            description: aiPanel.querySelector('[data-ai-detail-description]'),
            percent: aiPanel.querySelector('[data-ai-detail-percent]'),
            bar: aiPanel.querySelector('[data-ai-detail-bar]'),
            metaOne: aiPanel.querySelector('[data-ai-detail-meta-one]'),
            metaTwo: aiPanel.querySelector('[data-ai-detail-meta-two]'),
            metaThree: aiPanel.querySelector('[data-ai-detail-meta-three]')
        };

        aiOrbs.forEach((orb) => {
            orb.addEventListener('click', () => {
                aiOrbs.forEach(item => item.classList.remove('is-active'));
                orb.classList.add('is-active');

                fields.kicker.textContent = orb.dataset.kicker || '';
                fields.title.textContent = orb.dataset.title || '';
                fields.value.textContent = orb.dataset.value || '0';
                fields.unit.textContent = orb.dataset.unit || '';
                fields.description.textContent = orb.dataset.description || '';
                fields.percent.textContent = orb.dataset.percent || '0%';
                fields.bar.style.width = orb.dataset.percent || '0%';
                fields.metaOne.textContent = orb.dataset.metaOne || '';
                fields.metaTwo.textContent = orb.dataset.metaTwo || '';
                fields.metaThree.textContent = orb.dataset.metaThree || '';
            });
        });
    }

    function updateSegmentPanel(button) {
        const targetName = button.dataset.target;
        const panel = document.querySelector(`[data-segment-panel="${targetName}"]`);
        if (!panel) return;

        const groupButtons = document.querySelectorAll(`[data-segment-trigger][data-target="${targetName}"]`);
        groupButtons.forEach(item => item.classList.remove('is-active'));
        button.classList.add('is-active');

        const icon = panel.querySelector('[data-donut-icon]');
        const percent = panel.querySelector('[data-donut-percent]');
        const title = panel.querySelector('[data-donut-title]');
        const value = panel.querySelector('[data-donut-value]');
        const unit = panel.querySelector('[data-donut-unit]');
        const description = panel.querySelector('[data-donut-description]');
        const bar = panel.querySelector('[data-donut-bar]');

        if (icon) icon.className = `fa-solid ${button.dataset.icon || 'fa-circle'}`;
        if (percent) percent.textContent = button.dataset.percent || '0%';
        if (title) title.textContent = button.dataset.title || '';
        if (value) value.textContent = button.dataset.value || '0';
        if (unit) unit.textContent = button.dataset.unit || '';
        if (description) description.textContent = button.dataset.description || '';
        if (bar) bar.style.width = button.dataset.percent || '0%';
    }

    document.querySelectorAll('[data-segment-trigger]').forEach((button) => {
        button.addEventListener('click', () => updateSegmentPanel(button));
    });

    document.querySelectorAll('[data-donut-cycle]').forEach((donut) => {
        donut.addEventListener('click', (event) => {
            const targetName = donut.dataset.donutCycle;
            const buttons = Array.from(document.querySelectorAll(`[data-segment-trigger][data-target="${targetName}"]`));
            if (!buttons.length) return;

            let selectedIndex = -1;
            try {
                const segments = JSON.parse(donut.dataset.segments || '[]');
                const rect = donut.getBoundingClientRect();
                const x = event.clientX - rect.left - rect.width / 2;
                const y = event.clientY - rect.top - rect.height / 2;
                let angle = Math.atan2(y, x) * 180 / Math.PI + 90;
                if (angle < 0) angle += 360;

                selectedIndex = segments.findIndex((segment) => {
                    const start = Number(segment.start || 0);
                    const end = Number(segment.end || 0);
                    return angle >= start && angle <= end;
                });
            } catch (error) {
                selectedIndex = -1;
            }

            if (selectedIndex < 0 || !buttons[selectedIndex]) {
                const currentIndex = buttons.findIndex(item => item.classList.contains('is-active'));
                selectedIndex = (currentIndex + 1) % buttons.length;
            }

            updateSegmentPanel(buttons[selectedIndex]);
        });
    });

    const growthCanvas = document.getElementById('growthChart');
    if (growthCanvas) {
        const ctx = growthCanvas.getContext('2d');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.monthly_users.labels || [],
                datasets: [
                    {
                        label: 'مستخدمون جدد',
                        data: chartData.monthly_users.values || [],
                        borderColor: palette[0],
                        backgroundColor: makeGradient(ctx, palette[0]),
                        pointBackgroundColor: surfaceColor,
                        pointBorderColor: palette[0],
                        pointHoverBackgroundColor: palette[0],
                        pointHoverBorderColor: '#ffffff',
                        pointRadius: 4,
                        pointHoverRadius: 7,
                        borderWidth: 3,
                        tension: .44,
                        fill: true
                    },
                    {
                        label: 'المواعيد',
                        data: chartData.monthly_appointments.values || [],
                        borderColor: palette[1],
                        backgroundColor: makeGradient(ctx, palette[1]),
                        pointBackgroundColor: surfaceColor,
                        pointBorderColor: palette[1],
                        pointHoverBackgroundColor: palette[1],
                        pointHoverBorderColor: '#ffffff',
                        pointRadius: 4,
                        pointHoverRadius: 7,
                        borderWidth: 3,
                        tension: .44,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: legendBottom,
                    tooltip: tooltipStyle
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: gridColor,
                            drawBorder: false
                        },
                        ticks: {
                            precision: 0,
                            color: mutedColor,
                            font: { weight: '700' }
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: {
                            color: mutedColor,
                            font: { weight: '700' }
                        }
                    }
                }
            }
        });
    }

    function doughnutChart(id, labels, values, cutout = '68%') {
        const el = document.getElementById(id);
        if (!el) return;

        new Chart(el, {
            type: 'doughnut',
            data: {
                labels: labels || [],
                datasets: [
                    {
                        data: values || [],
                        backgroundColor: palette,
                        borderColor: surfaceColor,
                        borderWidth: 4,
                        hoverOffset: 10,
                        spacing: 3,
                        borderRadius: 10
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: cutout,
                plugins: {
                    legend: legendBottom,
                    tooltip: tooltipStyle
                }
            }
        });
    }

    function horizontalBarChart(id, labels, values) {
        const el = document.getElementById(id);
        if (!el) return;

        new Chart(el, {
            type: 'bar',
            data: {
                labels: labels || [],
                datasets: [
                    {
                        label: 'العدد',
                        data: values || [],
                        backgroundColor: palette.map(color => hexToRgba(color, .78)),
                        borderColor: palette,
                        borderWidth: 1,
                        borderRadius: 14,
                        borderSkipped: false,
                        maxBarThickness: 34
                    }
                ]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: tooltipStyle
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: {
                            color: gridColor,
                            drawBorder: false
                        },
                        ticks: {
                            precision: 0,
                            color: mutedColor,
                            font: { weight: '700' }
                        }
                    },
                    y: {
                        grid: { display: false },
                        ticks: {
                            color: textColor,
                            font: { weight: '800' }
                        }
                    }
                }
            }
        });
    }

    doughnutChart('rolesChart', chartData.roles.labels, chartData.roles.values, '68%');
    horizontalBarChart('appointmentsChart', chartData.appointments_status.labels, chartData.appointments_status.values);
    doughnutChart('articlesChart', chartData.articles_status.labels, chartData.articles_status.values, '64%');

    // تحصين: نجبر كل الشارتات تعيد حساب حجمها الحقيقي بعد أي تغيير بحجم النافذة
    // أو مستوى الزوم، عشان نتجنب أي "تجمّد" برسمة قديمة فوق تخطيط جديد
    let resizeTimer;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
            Object.values(Chart.instances || {}).forEach(function (instance) {
                instance.resize();
            });
        }, 150);
    });
});
</script>
@endpush
