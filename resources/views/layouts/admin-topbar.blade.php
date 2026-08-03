@php
    $admin = auth()->user();
    $adminName = $admin->name ?? 'Admin';
    $adminEmail = $admin->email ?? 'admin@etzan.com';
    $adminInitial = mb_substr($adminName, 0, 1, 'UTF-8') ?: 'A';

    $routeUrl = function ($names, $fallback = '#') {
        foreach ((array) $names as $name) {
            if (\Illuminate\Support\Facades\Route::has($name)) {
                return route($name);
            }
        }

        return url($fallback);
    };

    // عدّاد حقيقي بكل صفحات الأدمن (مش بس الداشبورد) — كان يعتمد على
    // $stats['admin_notifications_unread'] يلي بس صفحة الداشبورد بتمرره،
    // فباقي صفحات الأدمن كانت دايماً بتطلع صفر بغض النظر عن الحقيقة.
    $notificationsCount = \App\Models\AppNotification::forUser(auth()->id())->unread()->count();
@endphp

<header class="admin-topbar" id="adminTopbar">
    <div class="admin-topbar__right">
        <button
            type="button"
            class="admin-mobile-menu-btn"
            id="adminMobileMenuBtn"
            aria-label="فتح القائمة"
        >
            <i class="fa-solid fa-bars"></i>
        </button>

        <span class="admin-topbar__title">لوحة الإدارة</span>
    </div>

    <div class="admin-topbar__left">
        <div class="admin-global-search" data-admin-search>
            <i class="fa-solid fa-magnifying-glass"></i>

            <input
                type="search"
                data-admin-search-input
                data-search-url="{{ \Illuminate\Support\Facades\Route::has('admin.search') ? route('admin.search') : url('/admin/search') }}"
                placeholder="ابحث داخل الإدارة..."
                autocomplete="off"
                aria-label="البحث داخل الإدارة"
            >

            <span class="admin-search__shortcut">Ctrl<br>K</span>

            <div class="admin-search-panel" data-admin-search-panel>
                <div class="admin-search-live" data-search-live></div>
                <a href="{{ $routeUrl(['admin.dashboard'], '/admin/dashboard') }}" data-search-text="لوحة التحكم الرئيسية dashboard احصائيات">
                    <i class="fa-solid fa-gauge-high"></i>
                    <span>لوحة التحكم<small>الإحصائيات والمتابعة</small></span>
                </a>

                <a href="{{ $routeUrl(['admin.users.index', 'admin.admin-users'], '/admin/users') }}" data-search-text="المستخدمون المرضى الأطباء users">
                    <i class="fa-solid fa-users"></i>
                    <span>المستخدمون<small>إدارة المرضى والأطباء</small></span>
                </a>

                <a href="{{ $routeUrl(['admin.articles.index'], '/admin/articles') }}" data-search-text="المقالات المحتوى articles">
                    <i class="fa-regular fa-newspaper"></i>
                    <span>المقالات<small>إدارة المحتوى الصحي</small></span>
                </a>

                <a href="{{ $routeUrl(['admin.specialties.index'], '/admin/specialties') }}" data-search-text="التخصصات تخصصات الأطباء specialties">
                    <i class="fa-solid fa-stethoscope"></i>
                    <span>التخصصات<small>تخصصات الأطباء</small></span>
                </a>

                <a href="{{ $routeUrl(['admin.doctor-applications', 'admin.doctor-applications.index'], '/admin/doctor-applications') }}" data-search-text="طلبات الأطباء طلبات الانضمام doctors applications">
                    <i class="fa-solid fa-user-doctor"></i>
                    <span>طلبات الأطباء<small>قبول ورفض الطلبات</small></span>
                </a>

                <a href="{{ $routeUrl(['admin.messages.index'], '/admin/messages') }}" data-search-text="الرسائل التواصل messages">
                    <i class="fa-regular fa-envelope"></i>
                    <span>الرسائل<small>رسائل المستخدمين</small></span>
                </a>

                <a href="{{ $routeUrl(['admin.notifications.index'], '/admin/notifications') }}" data-search-text="الإشعارات notifications">
                    <i class="fa-regular fa-bell"></i>
                    <span>الإشعارات<small>تنبيهات النظام</small></span>
                </a>

                <a href="{{ $routeUrl(['admin.settings.index'], '/admin/settings') }}" data-search-text="الإعدادات settings">
                    <i class="fa-solid fa-gear"></i>
                    <span>الإعدادات<small>إعدادات المنصة</small></span>
                </a>

                <div class="admin-search-empty" data-search-empty>لا توجد نتائج مطابقة</div>
            </div>
        </div>

        <div class="admin-notif-wrap" id="notifWrap">
            <button
                type="button"
                class="admin-notif-btn"
                id="notifBtn"
                data-admin-notifications-trigger
                data-notifications-url="{{ \Illuminate\Support\Facades\Route::has('admin.notifications.dropdown') ? route('admin.notifications.dropdown') : url('/admin/notifications/dropdown') }}"
                aria-label="الإشعارات"
            >
                <span class="admin-icon-btn__box">
                    <svg class="admin-icon-btn__svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"></path>
                    </svg>

                    @if ($notificationsCount > 0)
                        <span class="admin-notif-badge">{{ $notificationsCount }}</span>
                    @endif
                </span>
            </button>

            <div class="admin-notif-dropdown" id="notifDropdown" data-admin-notifications-menu>
                <div class="admin-notif-dropdown__head">
                    <strong>الإشعارات</strong>
                    <i class="fa-regular fa-bell"></i>
                </div>

                <div class="admin-notif-dropdown__body">
                    <div class="admin-notif-empty">
                        <i class="fa-regular fa-bell-slash"></i>
                        <span>لا توجد إشعارات جديدة</span>
                    </div>
                </div>

                <div class="admin-notif-dropdown__footer">
                    <a href="{{ $routeUrl(['admin.notifications.index'], '/admin/notifications') }}">عرض كل الإشعارات</a>
                </div>
            </div>
        </div>

        <div class="admin-profile-menu">
            <button
                type="button"
                class="admin-profile-trigger"
                data-admin-profile-trigger
                aria-label="قائمة الأدمن"
            >
                <span class="admin-icon-btn__box">
                    <svg class="admin-icon-btn__svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"></path>
                    </svg>
                </span>
                <span class="admin-profile-trigger__chevron" aria-hidden="true">
                    <i class="fa-solid fa-chevron-down"></i>
                </span>
            </button>

            <div class="admin-profile-dropdown admin-profile-dropdown--dark" data-admin-profile-menu>
                <button type="button" class="admin-profile-option is-featured" id="adminThemeToggle">
                    <i id="adminThemeIcon" class="fa-regular fa-moon"></i>
                    <span id="adminThemeText"> الوضع الداكن</span>
                    <span class="admin-toggle-switch" aria-hidden="true">
                        <span class="admin-toggle-switch__knob"></span>
                    </span>
                </button>

                <a href="{{ $routeUrl(['admin.settings.index'], '/admin/settings') }}" class="admin-profile-option">
                    <i class="fa-solid fa-gear"></i>
                    <span>الإعدادات</span>
                </a>

                <form method="POST" action="{{ \Illuminate\Support\Facades\Route::has('logout') ? route('logout') : url('/logout') }}" class="admin-logout-form">
                    @csrf
                    <button type="submit" class="admin-profile-option admin-logout-action">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        <span>تسجيل الخروج</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
