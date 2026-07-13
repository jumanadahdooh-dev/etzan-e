@php
    $activePage = $activePage ?? 'dashboard';

    $liveBadges = $sidebarBadges ?? null;

    // عداد طلبات الاستشارة: حقيقي دايماً بكل صفحات الطبيب، مش بس بالداشبورد
    // (بيقرا من patient_profiles.doctor_request_status — نفس المصدر الحقيقي
    // يلي DoctorPatientRequestController بيستخدمه، مش الجدول القديم المقطوع)
    $realPendingRequestsCount = null;
    if (\Illuminate\Support\Facades\Schema::hasTable('patient_profiles')) {
        $currentDoctorProfileId = \Illuminate\Support\Facades\DB::table('doctor_profiles')
            ->where('user_id', auth()->id())
            ->value('id');

        if ($currentDoctorProfileId) {
            $realPendingRequestsCount = \Illuminate\Support\Facades\DB::table('patient_profiles')
                ->where('doctor_profile_id', $currentDoctorProfileId)
                ->where('doctor_request_status', 'pending')
                ->count();
        }
    }

    // عداد الإشعارات: حقيقي دايماً بكل صفحات الطبيب
    $realUnreadNotifications = null;
    if (\Illuminate\Support\Facades\Schema::hasTable('app_notifications')) {
        $realUnreadNotifications = \App\Models\AppNotification::forUser(auth()->id())->unread()->count();
    }

    $navGroups = [
        [
            'title' => 'الرئيسية',
            'items' => [
                ['label' => 'الرئيسية', 'hint' => 'لوحة الطبيب اليومية', 'route' => 'doctor.dashboard', 'match' => 'dashboard', 'icon' => 'layout-dashboard', 'badge' => null],
            ],
        ],
        [
            'title' => 'المرضى',
            'items' => [
                ['label' => 'طلبات الاستشارة', 'hint' => 'طلبات المرضى الجدد', 'route' => 'doctor.patient-requests.index', 'match' => 'requests', 'icon' => 'clipboard-list', 'badge' => $realPendingRequestsCount],
                ['label' => 'مرضاي', 'hint' => 'المرضى المقبولون', 'route' => 'doctor.patients', 'match' => 'patients', 'icon' => 'users-round', 'badge' => null],
                ['label' => 'المواعيد', 'hint' => 'جدول الاستشارات', 'route' => 'doctor.appointments', 'match' => 'appointments', 'icon' => 'calendar-days', 'badge' => null],
            ],
        ],
        [
            'title' => 'المتابعة',
            'items' => [
                ['label' => 'الإشعارات', 'hint' => 'كل التحديثات الواصلة لحسابك', 'route' => 'doctor.notifications.index', 'match' => 'notifications', 'icon' => 'bell', 'badge' => $realUnreadNotifications],
                ['label' => 'الرسائل', 'hint' => 'محادثات المرضى', 'route' => 'doctor.messages', 'match' => 'messages', 'icon' => 'message-circle', 'badge' => $liveBadges ? ($liveBadges['messages'] ?? null) : 12],
                ['label' => 'مراجعة الوجبات AI', 'hint' => 'وجبات تحتاج اعتماد', 'route' => 'doctor.meal_reviews', 'match' => 'meal-reviews', 'icon' => 'bot', 'badge' => $liveBadges ? ($liveBadges['meal-reviews'] ?? null) : 8],
                ['label' => 'الخطط الغذائية', 'hint' => 'إنشاء ومتابعة الخطط', 'route' => 'doctor.plans', 'match' => 'plans', 'icon' => 'clipboard-check', 'badge' => null],
                ['label' => 'تنبيهات المرضى', 'hint' => 'حالات تحتاج متابعة', 'route' => 'doctor.alerts', 'match' => 'alerts', 'icon' => 'triangle-alert', 'badge' => $liveBadges ? ($liveBadges['alerts'] ?? null) : 5],
            ],
        ],
        [
            'title' => 'المحتوى',
            'items' => [
                ['label' => 'مقالاتي', 'hint' => 'محتوى ينتظر الاعتماد', 'route' => 'doctor.articles', 'match' => 'articles', 'icon' => 'newspaper', 'badge' => $liveBadges ? ($liveBadges['articles'] ?? null) : 2],
                ['label' => 'التقارير', 'hint' => 'تحليلات الأداء والمتابعة', 'route' => 'doctor.reports', 'match' => 'reports', 'icon' => 'bar-chart-3', 'badge' => null],
            ],
        ],
        [
            'title' => 'الحساب',
            'items' => [
                ['label' => 'ملفي الشخصي', 'hint' => 'بيانات الطبيب العامة', 'route' => 'doctor.profile', 'match' => 'profile', 'icon' => 'user-round', 'badge' => null],
                ['label' => 'الإعدادات', 'hint' => 'التوفر والإشعارات', 'route' => 'doctor.settings', 'match' => 'settings', 'icon' => 'settings', 'badge' => null],
            ],
        ],
    ];
@endphp

<div class="sidebar-inner">
    <div class="sidebar-head">
        <a href="{{ route('doctor.dashboard') }}" class="brand-block" aria-label="اتزان" data-brand-toggle>
            <span class="brand-mark">
                <i data-lucide="stethoscope"></i>
            </span>
            <span class="brand-text">
                <strong>اتزان</strong>
                <small>لوحة الطبيب</small>
            </span>
        </a>

        <button type="button" class="sidebar-toggle-btn" data-sidebar-collapse aria-label="طي القائمة">
            <i data-lucide="panel-right-close"></i>
        </button>
        <button type="button" class="sidebar-close-btn" data-sidebar-close aria-label="إغلاق القائمة">
            <i data-lucide="x"></i>
        </button>
    </div>

    <nav class="sidebar-nav premium-sidebar-nav" aria-label="قائمة الطبيب">
        @foreach ($navGroups as $group)
            @php
                $groupItems = collect($group['items'])->filter(fn ($item) => Route::has($item['route']))->values();
            @endphp

            @if ($groupItems->isNotEmpty())
                <div class="sidebar-nav-group">
                    <span class="sidebar-nav-group__label">{{ $group['title'] }}</span>

                    @foreach ($groupItems as $item)
                        @php
                            $isActive = ($activePage ?? '') === $item['match']
                                || request()->routeIs($item['route'])
                                || request()->routeIs($item['route'] . '*');
                            $badgeValue = (int) ($item['badge'] ?? 0);
                        @endphp

                        <a
                            href="{{ route($item['route']) }}"
                            class="sidebar-link premium-sidebar-link {{ $isActive ? 'is-active' : '' }}"
                            data-title="{{ $item['label'] }}"
                            data-tooltip="{{ $item['label'] }}"
                            aria-label="{{ $item['label'] }}"
                        >
                            <span class="nav-icon">
                                <i data-lucide="{{ $item['icon'] }}"></i>
                            </span>
                            <span class="nav-label">{{ $item['label'] }}</span>

                            @if ($badgeValue > 0)
                                <span class="nav-badge">{{ $badgeValue > 99 ? '+99' : $badgeValue }}</span>
                            @endif

                            <span class="collapsed-nav-card" aria-hidden="true">
                                <span class="collapsed-nav-icon"><i data-lucide="{{ $item['icon'] }}"></i></span>
                                <span class="collapsed-nav-copy">
                                    <strong>{{ $item['label'] }}</strong>
                                    <small>{{ $item['hint'] }}</small>
                                </span>
                                @if ($badgeValue > 0)
                                    <span class="collapsed-nav-badge">{{ $badgeValue > 99 ? '+99' : $badgeValue }}</span>
                                @endif
                            </span>
                        </a>
                    @endforeach
                </div>
            @endif
        @endforeach
    </nav>

    <div class="sidebar-bottom">
        <div class="sidebar-tip">
            <div class="tip-title">
                <span>حالة العيادة</span>
                <i data-lucide="activity"></i>
            </div>
            <p><span class="doctor-state-dot"></span> متاح لاستقبال المرضى الجدد هذا الأسبوع.</p>
        </div>
    </div>
</div>