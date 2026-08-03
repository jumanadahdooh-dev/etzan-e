@extends('layouts.doctor')

@php
    $pageTitle = 'تنبيهات المرضى';
    $activePage = 'alerts';

    $severityIcon = ['danger' => 'octagon-alert', 'warn' => 'triangle-alert'];
    $severityLabel = ['danger' => 'خطر', 'warn' => 'تنبيه'];
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/doctor/dashboard.css') }}">
@endpush

@section('content')
<section class="ddash">

    <div class="ddash-section-head">
        <h2>تنبيهات المرضى</h2>
        <span>حالات حقيقية تحتاج متابعتك، محسوبة من نشاط مرضاك</span>
    </div>

    <div class="ddash-card ddash-accent--rose">
        <div class="ddash-card__head">
            <div><span>Patient Alerts</span><h2>تنبيهات تحتاج متابعة ({{ $alerts->count() }})</h2></div>
            <i data-lucide="triangle-alert" class="ddash-card__icon"></i>
        </div>

        @if ($alerts->isEmpty())
            <div class="ddash-empty"><i data-lucide="check-circle"></i><p>ولا في تنبيه حالياً — كل مرضاك مسجلين وجباتهم بانتظام وما في مهام متأخرة 👏</p></div>
        @else
            <div>
                @foreach ($alerts as $alert)
                    <div class="ddash-item">
                        <span class="ddash-avatar ddash-avatar--{{ $alert->severity === 'danger' ? 'rose' : 'amber' }}">{{ mb_substr($alert->patient_name, 0, 1) }}</span>
                        <div class="ddash-item__body">
                            <strong>{{ $alert->title }}</strong>
                            <small>{{ $alert->detail }}</small>
                        </div>
                        <span class="ddash-pill ddash-pill--{{ $alert->severity === 'danger' ? 'rose' : 'amber' }}">
                            <i data-lucide="{{ $severityIcon[$alert->severity] ?? 'bell' }}" style="width:12px;height:12px"></i>
                            {{ $alert->severity_label }}
                        </span>
                        <a class="ddash-btn" href="{{ route('doctor.patient-profile.show', $alert->profile_id) }}">فتح الملف</a>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
@endsection
