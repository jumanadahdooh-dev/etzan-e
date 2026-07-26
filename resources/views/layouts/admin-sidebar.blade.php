@php
    $routeUrl = function ($names, $fallback = '#') {
        foreach ((array) $names as $name) {
            if (\Illuminate\Support\Facades\Route::has($name)) {
                return route($name);
            }
        }

        return url($fallback);
    };

    $isActivePath = function ($patterns) {
        foreach ((array) $patterns as $pattern) {
            if (request()->is(trim($pattern, '/') . '*')) {
                return true;
            }
        }

        return false;
    };

    // عدّادين حقيقيين بكل صفحات الأدمن (مش بس الداشبورد يلي كان الوحيد
    // يمرر $stats) — العدد الاحتياطي كان صفر أو (أسوأ) عدد إشعارات مشترك
    // من موديل admin_notifications القديم بدل عدد الأدمن الحالي الحقيقي.
    $pendingDoctorRequests = (int) ($pendingDoctorRequests
        ?? ($stats['pending_doctor_applications']
            ?? (\Illuminate\Support\Facades\Schema::hasTable('doctor_applications')
                ? \Illuminate\Support\Facades\DB::table('doctor_applications')->where('status', 'pending')->count()
                : 0)));

    $unreadAdminNotifications = (int) ($unreadAdminNotifications
        ?? \App\Models\AppNotification::forUser(auth()->id())->unread()->count());

    $navGroups = [
        [
            'title' => 'الرئيسية',
            'items' => [
                [
                    'label' => 'لوحة التحكم',
                    'icon' => 'fa-solid fa-gauge-high',
                    'routes' => ['admin.dashboard'],
                    'fallback' => '/admin/dashboard',
                    'active' => ['admin/dashboard'],
                ],
            ],
        ],
        [
            'title' => 'الإدارة',
            'items' => [
                [
                    'label' => 'المستخدمون',
                    'icon' => 'fa-solid fa-users',
                    'routes' => ['admin.users.index', 'admin.admin-users'],
                    'fallback' => '/admin/users',
                    'active' => ['admin/users'],
                ],
                [
                    'label' => 'طلبات الأطباء',
                    'icon' => 'fa-solid fa-user-doctor',
                    'routes' => ['admin.doctor-applications', 'admin.doctor-applications.index'],
                    'fallback' => '/admin/doctor-applications',
                    'active' => ['admin/doctor-applications'],
                    'badge' => $pendingDoctorRequests,
                ],
            ],
        ],
        [
            'title' => 'المحتوى',
            'items' => [
                [
                    'label' => 'المقالات',
                    'icon' => 'fa-regular fa-newspaper',
                    'routes' => ['admin.articles.index'],
                    'fallback' => '/admin/articles',
                    'active' => ['admin/articles'],
                ],
                [
                    'label' => 'التخصصات',
                    'icon' => 'fa-solid fa-stethoscope',
                    'routes' => ['admin.specialties.index'],
                    'fallback' => '/admin/specialties',
                    'active' => ['admin/specialties'],
                ],
            ],
        ],
        [
            'title' => 'التواصل',
            'items' => [
                [
                    'label' => 'الرسائل',
                    'icon' => 'fa-regular fa-envelope',
                    'routes' => ['admin.messages.index'],
                    'fallback' => '/admin/messages',
                    'active' => ['admin/messages'],
                ],
                [
                    'label' => 'الإشعارات',
                    'icon' => 'fa-regular fa-bell',
                    'routes' => ['admin.notifications.index'],
                    'fallback' => '/admin/notifications',
                    'active' => ['admin/notifications'],
                    'badge' => $unreadAdminNotifications,
                ],
                [
                    'label' => 'الإعدادات',
                    'icon' => 'fa-solid fa-gear',
                    'routes' => ['admin.settings.index'],
                    'fallback' => '/admin/settings',
                    'active' => ['admin/settings'],
                    'children' => [
                        ['label' => 'هوية الموقع', 'hash' => 'identity', 'icon' => 'fa-solid fa-leaf'],
                        ['label' => 'معلومات التواصل', 'hash' => 'contact', 'icon' => 'fa-solid fa-address-book'],
                        ['label' => 'روابط التواصل', 'hash' => 'social', 'icon' => 'fa-solid fa-share-nodes'],
                        ['label' => 'تحسين الظهور', 'hash' => 'seo', 'icon' => 'fa-solid fa-magnifying-glass-chart'],
                        ['label' => 'المظهر العام', 'hash' => 'appearance', 'icon' => 'fa-solid fa-palette'],
                        ['label' => 'وضع الصيانة', 'hash' => 'maintenance', 'icon' => 'fa-solid fa-screwdriver-wrench'],
                        ['label' => 'الصفحة الرئيسية', 'hash' => 'home', 'icon' => 'fa-solid fa-house-chimney'],
                    ],
                ],
            ],
        ],
    ];
@endphp

<aside class="admin-sidebar" id="adminSidebar" aria-label="قائمة الإدارة الجانبية">
    <div class="admin-sidebar__inner">
        <div class="admin-sidebar__top">
            <a
                href="{{ $routeUrl(['admin.dashboard'], '/admin/dashboard') }}"
                class="admin-brand"
                id="adminBrandToggle"
                data-tooltip="فتح القائمة"
                aria-label="إتزان"
            >
                <span class="admin-brand__logo">
                    <i class="fa-solid fa-heart-pulse admin-brand__logo-main"></i>
                    <i class="fa-solid fa-bars-staggered admin-brand__logo-open"></i>
                </span>

                <span class="admin-brand__text">
                    <strong>إتزان</strong>
                    <small>منصة الصحة والتوازن</small>
                </span>
            </a>

            <button
                type="button"
                class="admin-sidebar-toggle"
                id="adminSidebarToggle"
                aria-label="طي القائمة"
                data-tooltip="طي القائمة"
            >
                <span class="admin-sidebar-toggle__icon" aria-hidden="true">
                    <span></span>
                    <span></span>
                    <span></span>
                </span>
            </button>
        </div>

        <nav class="admin-sidebar__nav" aria-label="روابط الإدارة">
            @foreach ($navGroups as $group)
                <section class="admin-nav-group">
                    <span class="admin-nav-group__label">{{ $group['title'] }}</span>

                    <div class="admin-nav">
                        @foreach ($group['items'] as $item)
                            @php
                                $href = $routeUrl($item['routes'], $item['fallback']);
                                $isActive = $isActivePath($item['active']);
                                $badge = (int) ($item['badge'] ?? 0);
                                $hasChildren = !empty($item['children']);
                            @endphp

                            @if ($hasChildren)
                                <div class="admin-nav__accordion {{ $isActive ? 'is-open' : '' }}">
                                    <button
                                        type="button"
                                        class="admin-nav__link admin-nav__link--parent {{ $isActive ? 'is-active' : '' }}"
                                        data-nav-accordion-trigger
                                        data-tooltip="{{ $item['label'] }}"
                                        aria-label="{{ $item['label'] }}"
                                        aria-expanded="{{ $isActive ? 'true' : 'false' }}"
                                    >
                                        <span class="admin-nav__icon">
                                            <i class="{{ $item['icon'] }}"></i>
                                        </span>
                                        <span class="admin-nav__label">{{ $item['label'] }}</span>
                                        <span class="admin-nav__chevron" aria-hidden="true">
                                            <i class="fa-solid fa-chevron-down"></i>
                                        </span>
                                    </button>

                                    <div class="admin-nav__submenu" data-nav-accordion-panel>
                                        <div class="admin-nav__submenu-inner">
                                            @foreach ($item['children'] as $child)
                                                <a href="{{ $href }}#{{ $child['hash'] }}" class="admin-nav__sublink" data-tooltip="{{ $child['label'] }}">
                                                    <span class="admin-nav__sublink-dot" aria-hidden="true"></span>
                                                    <i class="{{ $child['icon'] }}"></i>
                                                    <span class="admin-nav__sublink-label">{{ $child['label'] }}</span>
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @else
                                <a
                                    href="{{ $href }}"
                                    class="admin-nav__link {{ $isActive ? 'is-active' : '' }}"
                                    data-tooltip="{{ $item['label'] }}"
                                    aria-label="{{ $item['label'] }}"
                                >
                                    <span class="admin-nav__icon">
                                        <i class="{{ $item['icon'] }}"></i>
                                    </span>

                                    <span class="admin-nav__label">{{ $item['label'] }}</span>

                                    @if ($badge > 0)
                                        <span class="admin-nav__badge {{ $badge > 9 ? 'admin-nav__badge--warn' : '' }}">{{ $badge }}</span>
                                    @endif
                                </a>
                            @endif
                        @endforeach
                    </div>
                </section>
            @endforeach
        </nav>

        <div class="admin-sidebar__footer">
            <div class="admin-sidebar__status">
                <span class="admin-sidebar__status-dot"></span>
                <span class="admin-sidebar__status-text">
                    <strong>النظام يعمل</strong>
                    <small>لوحة الإدارة جاهزة</small>
                </span>
            </div>
        </div>
    </div>
</aside>
