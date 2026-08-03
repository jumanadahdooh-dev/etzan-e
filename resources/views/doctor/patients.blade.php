@extends('layouts.doctor')

@php
    $pageTitle = 'مرضاي';
    $activePage = 'patients';
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/doctor/dashboard.css') }}?v={{ file_exists(public_path('front/css/doctor/dashboard.css')) ? filemtime(public_path('front/css/doctor/dashboard.css')) : '1' }}">
    <link rel="stylesheet" href="{{ asset('front/css/doctor/requests.css') }}?v={{ file_exists(public_path('front/css/doctor/requests.css')) ? filemtime(public_path('front/css/doctor/requests.css')) : '1' }}">
@endpush

@section('content')
<section class="ddash">

    {{-- ══════════ رأس + بحث ══════════ --}}
    <div class="ddash-section-head">
        <h2>مرضاي</h2>
        <span>مرضاك المقبولون حالياً تحت متابعتك</span>
    </div>

    <form method="GET" action="{{ route('doctor.patients') }}" class="dpat-search">
        <div class="dpat-search__wrap">
            <i data-lucide="search"></i>
            <input type="text" name="search" value="{{ $search }}" placeholder="ابحثي بالاسم أو الإيميل...">
        </div>
        <button type="submit" class="dreq-btn dreq-btn--success">بحث</button>
        @if ($search)
            <a href="{{ route('doctor.patients') }}" class="ddash-link">مسح البحث</a>
        @endif
    </form>

    {{-- ══════════ قائمة المرضى ══════════ --}}
    @if ($patients->isNotEmpty())
        <div class="dreq-list">
            @foreach ($patients as $patient)
                <div class="dreq-card">
                    <div class="dreq-card__top">
                        <div class="dreq-card__who">
                            @if (!empty($patient->avatarUrl))
                                <img src="{{ $patient->avatarUrl }}" class="ddash-avatar-photo" alt="{{ $patient->patient_name }}">
                            @else
                                <span class="ddash-avatar">{{ mb_substr($patient->patient_name ?? '؟', 0, 1) }}</span>
                            @endif
                            <div>
                                <strong>{{ $patient->patient_name ?? 'مريض' }}</strong>
                                <small>{{ $patient->patient_email ?? '' }}</small>
                            </div>
                        </div>
                        <a href="{{ route('doctor.patient-profile.show', $patient->id) }}" class="dreq-icon-btn" title="عرض الملف" aria-label="عرض الملف">
                            <i data-lucide="eye"></i>
                        </a>
                    </div>

                    <div class="dreq-card__tags">
                        @if ($patient->age)<span class="ddash-pill ddash-pill--muted">{{ $patient->age }} سنة</span>@endif
                        @if ($patient->gender)<span class="ddash-pill ddash-pill--muted">{{ $patient->gender === 'female' ? 'أنثى' : 'ذكر' }}</span>@endif
                        @if (!empty($patient->health_goal))<span class="ddash-pill ddash-pill--green">{{ $patient->health_goal }}</span>@endif
                        @foreach (array_slice($patient->conditions, 0, 3) as $cond)
                            <span class="ddash-pill ddash-pill--rose">{{ is_array($cond) ? ($cond['name'] ?? '') : $cond }}</span>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="ddash-card ddash-accent--green">
            <div class="ddash-empty">
                <i data-lucide="users"></i>
                <p>
                    @if ($search)
                        ما في نتائج تطابق بحثك.
                    @else
                        ما في مرضى مقبولين لسا — بعد ما توافقي على طلبات الاستشارة، رح يظهروا هون.
                    @endif
                </p>
            </div>
        </div>
    @endif

</section>
@endsection
