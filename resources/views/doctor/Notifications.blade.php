@extends('layouts.doctor')

@php
    $pageTitle = 'الإشعارات';
    $activePage = 'notifications';

    $iconLabels = [
        'doctor_followup_request_received' => 'طلب متابعة جديد',
        'appointment_request_received' => 'طلب موعد جديد',
        'appointment_request_updated' => 'تعديل موعد',
        'message_received' => 'رسالة جديدة',
        'doctor_request_approved' => 'موافقة على طلب',
        'doctor_request_rejected' => 'رفض طلب',
        'doctor_followup_completed' => 'إنهاء متابعة',
    ];
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/doctor/doctor-dashboard.css') }}?v={{ file_exists(public_path('front/css/doctor/doctor-dashboard.css')) ? filemtime(public_path('front/css/doctor/doctor-dashboard.css')) : '1' }}">
    <link rel="stylesheet" href="{{ asset('front/css/doctor/doctor-requests.css') }}?v={{ file_exists(public_path('front/css/doctor/doctor-requests.css')) ? filemtime(public_path('front/css/doctor/doctor-requests.css')) : '1' }}">
@endpush

@section('content')
<section class="ddash">

    {{-- ══════════ رأس + إحصائيات سريعة ══════════ --}}
    <div class="dreq-head">
        <p>كل الإشعارات الحقيقية الواصلة لحسابك</p>
    </div>

    <div class="ddash-stats-grid" style="grid-template-columns:repeat(3,1fr)">
        <div class="ddash-stat-card ddash-accent--blue">
            <div class="ddash-stat-card__top"><span class="ddash-stat-card__icon"><i data-lucide="bell"></i></span></div>
            <div class="ddash-stat-card__num">{{ $stats['total'] }}</div>
            <div class="ddash-stat-card__label">الإجمالي</div>
        </div>
        <div class="ddash-stat-card ddash-accent--amber">
            <div class="ddash-stat-card__top"><span class="ddash-stat-card__icon"><i data-lucide="bell-ring"></i></span></div>
            <div class="ddash-stat-card__num">{{ $stats['unread'] }}</div>
            <div class="ddash-stat-card__label">غير مقروءة</div>
        </div>
        <div class="ddash-stat-card ddash-accent--green">
            <div class="ddash-stat-card__top"><span class="ddash-stat-card__icon"><i data-lucide="calendar-days"></i></span></div>
            <div class="ddash-stat-card__num">{{ $stats['today'] }}</div>
            <div class="ddash-stat-card__label">اليوم</div>
        </div>
    </div>

    {{-- ══════════ قائمة الإشعارات ══════════ --}}
    <div class="ddash-card ddash-accent--blue">
        <div class="ddash-card__head">
            <div><span>كل الإشعارات</span><h2>السجل الكامل</h2></div>
            @if ($stats['unread'] > 0)
                <form method="POST" action="{{ route('doctor.notifications.mark-all-read') }}">
                    @csrf
                    <button type="submit" class="ddash-link" style="background:none;border:0;cursor:pointer">تعليم الكل كمقروء</button>
                </form>
            @endif
        </div>

        @forelse ($notifications as $notification)
            <a href="{{ route('doctor.notifications.open', $notification) }}" class="dnotif-item {{ is_null($notification->read_at) ? 'is-unread' : '' }}">
                <span class="ddash-avatar ddash-avatar--blue" style="border-radius:12px">
                    <i data-lucide="{{ $typeIcons[$notification->type] ?? 'bell-ring' }}" style="width:17px;height:17px"></i>
                </span>
                <div class="dnotif-item__body">
                    <div class="dnotif-item__top">
                        <strong>{{ $notification->title }}</strong>
                        @if (is_null($notification->read_at))
                            <span class="ddash-dot" style="background:#ef4444;position:static;display:inline-block;width:7px;height:7px;border-radius:50%"></span>
                        @endif
                    </div>
                    @if ($notification->body)
                        <p>{{ $notification->body }}</p>
                    @endif
                    <small>{{ $iconLabels[$notification->type] ?? 'إشعار' }} · {{ $notification->created_at?->diffForHumans() }}</small>
                </div>
            </a>
        @empty
            <div class="ddash-empty"><i data-lucide="bell-off"></i><p>ما وصلك أي إشعار بعد.</p></div>
        @endforelse

        @if ($notifications->hasPages())
            <div style="margin-top:16px">{{ $notifications->links() }}</div>
        @endif
    </div>

</section>
@endsection
