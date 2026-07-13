{{-- =========================================================
FILE 1:
resources/views/admin/notifications/index.blade.php
========================================================= --}}

@extends('layouts.admin')

@section('title', 'الإشعارات | اتزان')

@push('styles')
<link rel="stylesheet" href="{{ asset('front/css/admin/admin-notifications-and-files.css') }}">
@endpush

@section('content')
<section class="admin-notifications-page notifications-v4-page">

    @if (session('success'))
        <div class="notifications-toast notifications-toast--success">
            <i class="fa-solid fa-circle-check"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- Hero --}}
    <section class="notifications-hero-v4">
        <div>
            <h1>الإشعارات</h1>

            <p>تابعي تحديثات لوحة الإدارة من مكان واحد: الطلبات، الرسائل، وتنبيهات النظام.</p>
        </div>

        <form action="{{ route('admin.notifications.mark-all-read') }}" method="POST">
            @csrf
            <button type="submit" class="notifications-primary-btn-v4">
                <i class="fa-solid fa-check-double"></i>
                <span>تعليم الكل كمقروء</span>
            </button>
        </form>
    </section>

    {{-- Stats --}}
    <section class="notifications-stats-v4">
        <article class="notifications-stat-v4">
            <div class="notifications-stat-icon-v4">
                <i class="fa-regular fa-bell"></i>
            </div>
            <div>
                <span>كل الإشعارات</span>
                <strong>{{ $stats['total'] }}</strong>
            </div>
        </article>

        <article class="notifications-stat-v4 is-unread">
            <div class="notifications-stat-icon-v4">
                <i class="fa-solid fa-circle"></i>
            </div>
            <div>
                <span>غير مقروءة</span>
                <strong>{{ $stats['unread'] }}</strong>
            </div>
        </article>

        <article class="notifications-stat-v4 is-today">
            <div class="notifications-stat-icon-v4">
                <i class="fa-regular fa-calendar"></i>
            </div>
            <div>
                <span>اليوم</span>
                <strong>{{ $stats['today'] }}</strong>
            </div>
        </article>

        <article class="notifications-stat-v4 is-doctor">
            <div class="notifications-stat-icon-v4">
                <i class="fa-solid fa-user-doctor"></i>
            </div>
            <div>
                <span>طلبات الأطباء</span>
                <strong>{{ $stats['doctor_applications'] }}</strong>
            </div>
        </article>
    </section>

    {{-- Clean Categories --}}
    <section class="notifications-categories-v4">
        <div class="notifications-categories-v4__list">
            <a href="{{ route('admin.notifications.index') }}"
               class="notifications-category-v4 {{ !request('status') && !request('type') ? 'is-active' : '' }}">
                <i class="fa-solid fa-layer-group"></i>
                <span>الكل</span>
            </a>

            <a href="{{ route('admin.notifications.index', ['status' => 'unread']) }}"
               class="notifications-category-v4 {{ request('status') === 'unread' ? 'is-active' : '' }}">
                <i class="fa-solid fa-circle"></i>
                <span>غير مقروءة</span>
            </a>

            <a href="{{ route('admin.notifications.index', ['type' => 'doctor_application']) }}"
               class="notifications-category-v4 {{ request('type') === 'doctor_application' ? 'is-active' : '' }}">
                <i class="fa-solid fa-user-doctor"></i>
                <span>الأطباء</span>
            </a>

            <a href="{{ route('admin.notifications.index', ['type' => 'message']) }}"
               class="notifications-category-v4 {{ request('type') === 'message' ? 'is-active' : '' }}">
                <i class="fa-regular fa-message"></i>
                <span>الرسائل</span>
            </a>

            <a href="{{ route('admin.notifications.index', ['type' => 'system']) }}"
               class="notifications-category-v4 {{ request('type') === 'system' ? 'is-active' : '' }}">
                <i class="fa-solid fa-gear"></i>
                <span>النظام</span>
            </a>
        </div>

        @if(request('status') || request('type'))
            <a href="{{ route('admin.notifications.index') }}" class="notifications-clear-filter-v4">
                <i class="fa-solid fa-xmark"></i>
                إزالة الفلتر
            </a>
        @endif
    </section>

    {{-- Notifications List --}}
    <section class="notifications-panel-v4">
        <div class="notifications-panel-head-v4">
            <div>
                <h2>آخر التحديثات</h2>
            </div>

            <div class="notifications-count-v4">
                <i class="fa-regular fa-folder-open"></i>
                <strong>{{ $notifications->total() }}</strong>
                <span>إشعار</span>
            </div>
        </div>

        @if ($notifications->count())
            <div class="notifications-list-v4">
                @foreach ($notifications as $notification)
                    <article class="notification-card-v4 {{ $notification->is_unread ? 'is-unread' : '' }}">
                        <div class="notification-icon-v4 {{ $notification->type_class }}">
                            <i class="{{ $notification->type_icon }}"></i>
                        </div>

                        <div class="notification-content-v4">
                            <div class="notification-top-v4">
                                <div>
                                    <div class="notification-meta-v4">
                                        <span>{{ $notification->type_label }}</span>
                                        <i class="fa-solid fa-circle"></i>
                                        <span>{{ $notification->created_at?->diffForHumans() }}</span>
                                    </div>

                                    <h3>{{ $notification->title }}</h3>
                                </div>

                                @if ($notification->is_unread)
                                    <span class="notification-new-v4">جديد</span>
                                @else
                                    <span class="notification-read-v4">
                                        <i class="fa-solid fa-check-double"></i>
                                        مقروء
                                    </span>
                                @endif
                            </div>

                            @if ($notification->body)
                                <p>{{ $notification->body }}</p>
                            @endif

                            <div class="notification-actions-v4">
                                @if ($notification->url)
                                    <a href="{{ route('admin.notifications.open', $notification) }}"
                                    class="notification-action-v4 notification-action-v4--primary">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        <span>الذهاب للإشعار</span>
                                    </a>
                                @endif

                                @if ($notification->is_unread)
                                    <form action="{{ route('admin.notifications.mark-read', $notification) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="notification-action-v4">
                                            <i class="fa-solid fa-check"></i>
                                            <span>تعليم كمقروء</span>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="notifications-pagination-v4">
                {{ $notifications->links() }}
            </div>
        @else
            <div class="notifications-empty-v4">
                <div class="notifications-empty-icon-v4">
                    <i class="fa-regular fa-bell-slash"></i>
                </div>

                <h3>لا توجد إشعارات حاليًا</h3>
                <p>ستظهر هنا أي تحديثات جديدة داخل لوحة الإدارة.</p>
            </div>
        @endif
    </section>
</section>
@endsection


