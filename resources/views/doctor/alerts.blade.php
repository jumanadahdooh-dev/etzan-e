@extends('layouts.doctor')

@php
    $pageTitle = 'تنبيهات المرضى';
    $activePage = 'alerts';
@endphp

@section('content')
<section class="doctor-page">
    <div class="doctor-filterbar">
        <div><span class="home-v2-kicker"><span><i data-lucide="sparkles"></i></span> متابعة الطبيب</span></div>
    </div>

    <div class="doctor-panel">
        <div class="doctor-panel-head">
            <div>
                <span>Patient Alerts</span>
                <h2>تنبيهات تحتاج متابعة ({{ $alerts->count() }})</h2>
            </div>
            <i data-lucide="triangle-alert"></i>
        </div>

        @if ($alerts->isEmpty())
            <p style="padding:16px 4px;color:var(--et-muted,#6b7280)">
                ولا في تنبيه حالياً — كل مرضاك مسجلين وجباتهم بانتظام وما في مهام متأخرة. 👏
            </p>
        @else
            <div class="doctor-list">
                @foreach ($alerts as $alert)
                    <div class="doctor-item compact">
                        <div>
                            <span class="doctor-status {{ $alert->severity }}">{{ $alert->severity_label }}</span>
                            <strong>{{ $alert->title }}</strong>
                            <small>{{ $alert->detail }}</small>
                        </div>
                        <a class="doctor-btn primary" href="{{ route('doctor.patient-profile.show', $alert->profile_id) }}">فتح الملف</a>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
@endsection
