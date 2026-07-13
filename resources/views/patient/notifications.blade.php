@extends('layouts.patient')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/patient/notifications.css') }}">
@endpush

@section('content')
@php
    $notifications = $patientNotifications ?? [];
    $notificationsCount = $notificationsCount ?? 0;
@endphp

<section class="notifications-page">
    @if (session('success'))
        <div class="notify-alert is-success">
            <i data-lucide="badge-check"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="notify-alert is-error">
            <i data-lucide="circle-alert"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <div class="notifications-hero notifications-hero-with-cards">
        <div class="notifications-hero-copy">
            <span class="notifications-kicker">
                <i data-lucide="bell-ring"></i>
                مركز الإشعارات
            </span>

            <h1>إشعاراتك الصحية</h1>

            <p>
                هنا تظهر تنبيهات المواعيد، تحديثات الطبيب، الرسائل الجديدة،
                وتذكيرات المهام اليومية داخل اتزان.
            </p>

            @if (!empty($notificationsCount) && $notificationsCount > 0)
                <form method="POST" action="{{ route('patient.notifications.read-all') }}">
                    @csrf

                    <button
                        type="submit"
                        class="notify-outline-btn notify-check-all-btn"
                        title="تحديد الكل كصحيح"
                        aria-label="تحديد الكل كصحيح"
                    >
                        صحيح للكل
                        <i data-lucide="check-check"></i>
                    </button>
                </form>
            @endif
        </div>

        <div class="notifications-hero-mini-panel">
            <div class="hero-mini-pill is-green">
                <span class="mini-dot"></span>
                <strong>تنبيهات المواعيد فعّالة</strong>
            </div>

            <div class="hero-mini-pill is-blue">
                <i data-lucide="stethoscope"></i>
                <strong>تحديثات الطبيب</strong>
            </div>

            <div class="hero-mini-pill is-soft">
                <i data-lucide="message-circle"></i>
                <strong>الرسائل والمهام</strong>
            </div>
        </div>
    </div>

    <div class="notifications-layout notifications-layout-full">
        <div class="notifications-list">
            @forelse ($notifications as $notification)
                @php
                    $type = $notification['type'] ?? 'general';

                    $icon = match ($type) {
                        'appointment_pending' => 'clock-3',
                        'appointment_confirmed' => 'badge-check',
                        'appointment_rejected' => 'circle-alert',
                        'appointment_reschedule_requested' => 'calendar-clock',
                        'appointment_reschedule_accepted' => 'badge-check',
                        'appointment_reschedule_declined' => 'circle-x',
                        'appointment_reminder_60' => 'alarm-clock',
                        'appointment_reminder_10' => 'timer',
                        'appointment_starting_now' => 'radio',

                        'doctor_request_pending' => 'stethoscope',
                        'doctor_request_approved' => 'badge-check',
                        'doctor_request_rejected' => 'circle-alert',

                        'message_received' => 'message-circle',
                        'task_reminder' => 'list-checks',
                        'daily_plan_ready' => 'clipboard-check',
                        'calorie_reminder' => 'sparkles',

                        default => 'bell-ring',
                    };

                    $class = match ($type) {
                        'appointment_confirmed',
                        'appointment_reschedule_accepted',
                        'doctor_request_approved',
                        'daily_plan_ready' => 'is-success',

                        'appointment_rejected',
                        'appointment_reschedule_declined',
                        'doctor_request_rejected' => 'is-danger',

                        'appointment_reschedule_requested',
                        'message_received' => 'is-blue',

                        'appointment_reminder_60',
                        'appointment_reminder_10',
                        'appointment_starting_now',
                        'task_reminder',
                        'calorie_reminder' => 'is-warning',

                        default => 'is-default',
                    };

                    $url = $notification['url'] ?? route('patient.followup');
                    $isRead = !empty($notification['is_read']);
                @endphp

                <article class="notification-card {{ $class }} {{ !$isRead ? 'is-unread' : '' }}">
                    <div class="notification-icon">
                        <i data-lucide="{{ $icon }}"></i>
                    </div>

                    <div class="notification-content">
                        <div class="notification-title-row">
                            <h2>{{ $notification['title'] ?? 'إشعار' }}</h2>

                            @if (!$isRead)
                                <span class="unread-dot">جديد</span>
                            @endif
                        </div>

                        <p>{{ $notification['body'] ?? '' }}</p>

                        <div class="notification-meta">
                            <span>
                                <i data-lucide="clock"></i>
                                {{ $notification['time'] ?? '' }}
                            </span>
                        </div>
                    </div>

                    <div class="notification-actions">
                        <a href="{{ $url }}" class="notify-primary-btn">
                            فتح
                            <i data-lucide="arrow-left"></i>
                        </a>

                        @if (!$isRead)
                            <form method="POST" action="{{ route('patient.notifications.read', $notification['id']) }}">
                                @csrf

                                <button
                                    type="submit"
                                    class="notify-outline-btn mini notify-check-btn"
                                    title="صحيح"
                                    aria-label="صحيح"
                                >
                                    <i data-lucide="check"></i>
                                </button>
                            </form>
                        @endif
                    </div>
                </article>
            @empty
                <div class="notifications-empty">
                    <span>
                        <i data-lucide="bell-off"></i>
                    </span>

                    <h2>لا توجد إشعارات جديدة الآن</h2>

                    <p>
                        عند وصول تنبيه موعد، رسالة جديدة، أو تحديث من الطبيب سيظهر هنا وفي نافذة الجرس.
                    </p>

                    <a href="{{ route('patient.home') }}" class="notify-primary-btn">
                        العودة للرئيسية
                        <i data-lucide="arrow-left"></i>
                    </a>
                </div>
            @endforelse
        </div>
    </div>
</section>
@endsection
