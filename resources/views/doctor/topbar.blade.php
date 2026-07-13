@php
    $doctorUser = auth()->user();
    $doctorName = $doctorUser->name ?? 'طبيب';
    $doctorEmail = $doctorUser->email ?? '';
    $doctorInitial = mb_substr($doctorName, 0, 1, 'UTF-8');
    $unreadMessages = $unreadMessages ?? null;

    $unreadNotifications = 0;
    if (\Illuminate\Support\Facades\Schema::hasTable('app_notifications')) {
        $unreadNotifications = \App\Models\AppNotification::forUser(auth()->id())->unread()->count();
    }
@endphp

<header class="patient-topbar patient-topbar-simple">
    <div class="topbar-simple-greeting">
        <button type="button" class="mobile-menu-btn simple-mobile-menu" data-sidebar-open aria-label="فتح القائمة">
            <i data-lucide="menu"></i>
        </button>

        <div class="topbar-greeting-icon">
            <i data-lucide="stethoscope"></i>
        </div>

        <div class="topbar-greeting-copy">
            <span>لوحة الطبيب</span>
            <h1>{{ $pageTitle ?? 'الرئيسية' }}</h1>
        </div>
    </div>

    <div class="topbar-simple-actions">
        <a href="{{ route('doctor.messages') }}" class="simple-icon-btn" aria-label="الرسائل">
            <i data-lucide="mail"></i>
            @if (($unreadMessages ?? 0) > 0)
                <span class="topbar-badge">{{ $unreadMessages > 9 ? '9+' : $unreadMessages }}</span>
            @endif
        </a>

        <div class="topbar-notif"
             data-doctor-notif-wrap
             data-dropdown-url="{{ Route::has('doctor.notifications.dropdown') ? route('doctor.notifications.dropdown') : '' }}"
             data-unread-url="{{ Route::has('doctor.notifications.unread-count') ? route('doctor.notifications.unread-count') : '' }}"
             data-mark-all-url="{{ Route::has('doctor.notifications.mark-all-read') ? route('doctor.notifications.mark-all-read') : '' }}">
            <button type="button" class="simple-icon-btn" data-doctor-notif-toggle aria-label="الإشعارات" aria-expanded="false">
                <i data-lucide="bell"></i>
                <span class="topbar-badge" data-doctor-notif-dot style="{{ $unreadNotifications > 0 ? '' : 'display:none' }}">{{ $unreadNotifications > 9 ? '9+' : $unreadNotifications }}</span>
            </button>

            <div class="topbar-notif__menu" data-doctor-notif-menu>
                <div class="topbar-notif__head">
                    <strong>الإشعارات</strong>
                    <button type="button" data-doctor-notif-mark-all>تعليم الكل كمقروء</button>
                </div>
                <div class="topbar-notif__list" data-doctor-notif-list>
                    <div class="ddash-empty" style="padding:24px 16px"><i data-lucide="bell-off"></i><p>ما في إشعارات جديدة.</p></div>
                </div>
            </div>
        </div>

        <div class="topbar-profile">
            <button type="button" class="simple-avatar-btn" data-profile-toggle aria-expanded="false" aria-label="قائمة حساب الطبيب">
                <span class="profile-avatar"><span>{{ $doctorInitial }}</span></span>
            </button>

            <div class="profile-menu" data-profile-menu>
                <div class="profile-menu-head">
                    <span class="profile-menu-avatar"><span>{{ $doctorInitial }}</span></span>
                    <div>
                        <strong>{{ $doctorName }}</strong>
                        <small>{{ $doctorEmail }}</small>
                    </div>
                </div>

                <a href="{{ route('doctor.profile') }}">
                    <i data-lucide="user-round"></i>
                    <span>الملف الشخصي</span>
                </a>

                <a href="{{ route('doctor.settings') }}">
                    <i data-lucide="settings"></i>
                    <span>الإعدادات</span>
                </a>

                <button type="button" class="theme-toggle" data-theme-toggle>
                    <i data-lucide="moon"></i>
                    <span data-theme-label>الوضع الداكن</span>
                </button>

                @if (Route::has('logout'))
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="logout-menu-btn">
                            <i data-lucide="log-out"></i>
                            <span>تسجيل الخروج</span>
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</header>

<script src="{{ asset('front/js/patient-live.js') }}"></script>