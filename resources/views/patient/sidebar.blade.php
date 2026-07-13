@php
    $activePage = $activePage ?? 'home';

    $notificationsBadge = (int) (
        $notificationsCount
        ?? $unreadNotifications
        ?? $unreadAppNotifications
        ?? 0
    );

    $messagesBadge = (int) ($unreadMessages ?? 0);

    $navGroups = [
        [
            'title' => 'الرئيسية',
            'items' => [
                ['label' => 'الرئيسية', 'hint' => 'نظرة عامة على يومك', 'route' => 'patient.home', 'match' => 'home', 'icon' => 'layout-dashboard', 'badge' => null],
            ],
        ],
        [
            'title' => 'صحتي',
            'items' => [
                ['label' => 'رحلتي اليوم', 'hint' => 'مهامك الصحية اليومية', 'route' => 'patient.journey', 'match' => 'journey', 'icon' => 'route', 'badge' => null],
                ['label' => 'ملفي الصحي', 'hint' => 'بياناتك وخطتك الصحية', 'route' => 'patient.profile', 'match' => 'profile', 'icon' => 'heart-pulse', 'badge' => null],
                ['label' => 'طبيبي', 'hint' => 'طبيب المتابعة وحالة الطلب', 'route' => 'patient.doctor.current', 'match' => 'my-doctor', 'icon' => 'stethoscope', 'badge' => null],
                ['label' => 'السعرات', 'hint' => 'تحليل الوجبات بالذكاء الاصطناعي', 'route' => 'patient.calories', 'match' => 'calories', 'icon' => 'flame', 'badge' => null],
                ['label' => 'المواعيد', 'hint' => 'متابعة الطبيب والجلسات', 'route' => 'patient.followup', 'match' => 'followup', 'icon' => 'calendar-days', 'badge' => null],
            ],
        ],
        [
            'title' => 'المحتوى',
            'items' => [
                ['label' => 'المقالات', 'hint' => 'مقالات اتزان وطبيبك', 'route' => 'patient.articles', 'match' => 'articles', 'icon' => 'newspaper', 'badge' => null],
            ],
        ],
        [
            'title' => 'التواصل',
            'items' => [
                ['label' => 'الرسائل', 'hint' => 'تواصلك مع الطبيب والدعم', 'route' => 'patient.messages', 'match' => 'messages', 'icon' => 'message-circle', 'badge' => $messagesBadge],
                ['label' => 'الإشعارات', 'hint' => 'تنبيهات المواعيد والمهام', 'route' => 'patient.notifications', 'match' => 'notifications', 'icon' => 'bell', 'badge' => $notificationsBadge],
                ['label' => 'المساعد الذكي', 'hint' => 'اسأل مساعد اتزان', 'route' => 'patient.ai-chat.index', 'match' => 'ai-chat', 'icon' => 'bot', 'badge' => null],
                ['label' => 'الدعم', 'hint' => 'مساعدة فريق اتزان', 'route' => 'patient.support', 'match' => 'support', 'icon' => 'life-buoy', 'badge' => null],
            ],
        ],
    ];
@endphp

<div class="sidebar-inner">
    <div class="sidebar-head">
        <a href="{{ route('patient.home') }}" class="brand-block" aria-label="اتزان" data-brand-toggle>
            <span class="brand-mark">
                <i data-lucide="heart-pulse"></i>
            </span>
            <span class="brand-text">
                <strong>اتزان</strong>
                <small>صحة تعيشها كل يوم</small>
            </span>
        </a>

        <button type="button" class="sidebar-toggle-btn" data-sidebar-collapse aria-label="طي القائمة">
            <i data-lucide="panel-right-close"></i>
        </button>

        <button type="button" class="sidebar-close-btn" data-sidebar-close aria-label="إغلاق القائمة">
            <i data-lucide="x"></i>
        </button>
    </div>

    <nav class="sidebar-nav premium-sidebar-nav" aria-label="قائمة المريض">
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
                <span>نصيحة اليوم</span>
                <i data-lucide="leaf"></i>
            </div>
            <p>ابدأ يومك بكوب ماء، خطوة صغيرة تساعد جسمك على التوازن.</p>
        </div>
    </div>
</div>