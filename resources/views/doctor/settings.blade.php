@extends('layouts.doctor')

@php
    $pageTitle = 'الإعدادات';
    $activePage = 'settings';
    $dayLabels = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/doctor/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('front/css/doctor/requests.css') }}">
@endpush

@section('content')
<section class="ddash">

    <div class="ddash-section-head">
        <h2>الإعدادات</h2>
        <span>كلمة المرور، التوفر للاستشارات، وجدول ساعات عملك</span>
    </div>

    @if (session('success'))
        <div class="doctor-status" style="margin-bottom:14px">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="doctor-status danger" style="margin-bottom:14px">{{ $errors->first() }}</div>
    @endif

    <div class="doctor-grid cols-2">

        <div class="doctor-panel">
            <div class="doctor-panel-head">
                <div><span>Availability</span><h2>التوفر للاستشارات</h2></div>
                <i data-lucide="calendar-check"></i>
            </div>
            <div class="doctor-card-row">
                <span class="doctor-status {{ $doctorProfile->is_available ? '' : 'danger' }}">
                    <span class="doctor-state-dot"></span>
                    {{ $doctorProfile->is_available ? 'متاح لاستقبال طلبات جديدة' : 'غير متاح حالياً' }}
                </span>
                <form method="POST" action="{{ route('doctor.availability.toggle') }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="doctor-btn {{ $doctorProfile->is_available ? 'ghost' : 'primary' }}">
                        {{ $doctorProfile->is_available ? 'اجعلني غير متاح' : 'اجعلني متاح' }}
                    </button>
                </form>
            </div>
        </div>

        <details class="doctor-panel ddash-toggle" @if($errors->has('current_password') || $errors->has('password')) open @endif>
            <summary style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px;">
                <div style="display:flex; align-items:center; gap:10px;">
                    <i data-lucide="lock" style="width:20px;height:20px;color:var(--primary-dark)"></i>
                    <div>
                        <span style="display:block;font-size:12px;color:var(--primary-dark);font-weight:950">Security</span>
                        <h2 style="margin:3px 0 0;font-size:18px;line-height:1.45;color:var(--text);font-weight:950">تغيير كلمة المرور</h2>
                    </div>
                </div>
                <i data-lucide="chevron-down" class="chev"></i>
            </summary>

            <form class="doctor-form ddash-toggle-body" method="POST" action="{{ route('doctor.settings.password') }}">
                @csrf
                @method('PUT')
                <input type="password" name="current_password" placeholder="كلمة المرور الحالية" required>
                <input type="password" name="password" placeholder="كلمة المرور الجديدة" required>
                <input type="password" name="password_confirmation" placeholder="تأكيد كلمة المرور الجديدة" required>
                <button type="submit" class="doctor-btn primary">تغيير كلمة المرور</button>
            </form>
        </details>
    </div>

    <details class="ddash-card ddash-accent--blue ddash-toggle" @if($errors->has('days')) open @endif>
        <summary style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px;">
            <div style="display:flex; align-items:center; gap:10px;">
                <i data-lucide="clock" style="width:20px;height:20px;color:var(--d-blue)"></i>
                <div>
                    <span style="display:block;font-size:.72rem;font-weight:700;color:var(--d-blue);text-transform:uppercase;letter-spacing:.04em">Weekly Hours</span>
                    <h2 style="margin:4px 0 0;font-size:1.04rem;font-weight:800;color:var(--d-title);display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                        جدول ساعات العمل
                        <span class="ddash-pill ddash-pill--blue">{{ $schedulesByDay->count() }} أيام مفعّلة</span>
                    </h2>
                </div>
            </div>
            <i data-lucide="chevron-down" class="chev"></i>
        </summary>

        <form method="POST" action="{{ route('doctor.settings.schedule') }}" class="ddash-toggle-body">
            @csrf
            @method('PUT')

            <div data-sched-list>
                @foreach ($days as $day)
                    @php $existing = $schedulesByDay->get($day); @endphp
                    <div class="ddash-item">
                        <label style="display:flex; align-items:center; gap:8px; min-width:110px; font-weight:800;">
                            <input type="checkbox" name="days[{{ $day }}][active]" value="1" @checked($existing)>
                            {{ $dayLabels[$day] }}
                        </label>
                        <div style="display:flex; gap:8px; align-items:center; flex:1;">
                            <input type="time" name="days[{{ $day }}][start_time]" value="{{ $existing?->start_time?->format('H:i') ?? '09:00' }}"
                                   style="min-height:38px; border-radius:12px; border:1px solid var(--home-v2-border); padding:0 10px;">
                            <span style="color:var(--muted);">إلى</span>
                            <input type="time" name="days[{{ $day }}][end_time]" value="{{ $existing?->end_time?->format('H:i') ?? '17:00' }}"
                                   style="min-height:38px; border-radius:12px; border:1px solid var(--home-v2-border); padding:0 10px;">
                        </div>
                    </div>
                @endforeach
            </div>

            <button type="submit" class="doctor-btn primary" style="margin-top:14px;">حفظ جدول الساعات</button>
        </form>
    </details>
</section>
@endsection
