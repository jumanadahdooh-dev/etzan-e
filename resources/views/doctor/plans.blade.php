@extends('layouts.doctor')

@php
    $pageTitle = 'الخطط الغذائية';
    $activePage = 'plans';

    $statusLabels = ['approved' => 'معتمدة', 'pending' => 'بانتظار المراجعة'];
    $statusClasses = ['approved' => '', 'pending' => 'warn'];
@endphp

@section('content')
<section class="doctor-page">
    <div class="doctor-filterbar">
        <div><span class="home-v2-kicker"><span><i data-lucide="sparkles"></i></span> متابعة الطبيب</span></div>
    </div>

    <div class="doctor-panel">
        <div class="doctor-panel-head">
            <div>
                <span>Nutrition Plans</span>
                <h2>خطط المرضى الحاليين ({{ $plans->count() }})</h2>
            </div>
            <i data-lucide="clipboard-check"></i>
        </div>

        @if ($plans->isEmpty())
            <p style="padding:16px 4px;color:var(--et-muted,#6b7280)">
                ما في خطط سعرات محددة لأي مريض لسا. افتحي ملف أي مريض من قائمة "مرضاي" وحددي هدف السعرات من هناك.
            </p>
        @else
            <div class="doctor-list">
                @foreach ($plans as $plan)
                    <div class="doctor-item compact">
                        <div>
                            <strong>{{ $plan->patient_name }}</strong>
                            <small>
                                {{ $plan->calories_goal }} kcal/day
                                @if ($plan->protein_goal || $plan->carbs_goal || $plan->fat_goal)
                                    · بروتين {{ $plan->protein_goal ?? '—' }}غ
                                    · كارب {{ $plan->carbs_goal ?? '—' }}غ
                                    · دهون {{ $plan->fat_goal ?? '—' }}غ
                                @endif
                                · آخر تحديث {{ \Illuminate\Support\Carbon::parse($plan->goal_date)->locale('ar')->translatedFormat('j M Y') }}
                            </small>
                            @if ($plan->doctor_note)
                                <small style="display:block;margin-top:4px;opacity:.8">ملاحظتك: {{ $plan->doctor_note }}</small>
                            @endif
                        </div>
                        <div class="doctor-actions">
                            <span class="doctor-status {{ $statusClasses[$plan->status] ?? '' }}">
                                {{ $statusLabels[$plan->status] ?? $plan->status }}
                            </span>
                            <a class="doctor-btn primary" href="{{ route('doctor.patient-profile.show', $plan->profile_id) }}">تعديل الخطة</a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    @if ($patientsWithoutPlan->isNotEmpty())
        <div class="doctor-panel" style="margin-top:16px">
            <div class="doctor-panel-head">
                <div>
                    <span>Needs a Plan</span>
                    <h2>مرضى بدون خطة سعرات ({{ $patientsWithoutPlan->count() }})</h2>
                </div>
                <i data-lucide="triangle-alert"></i>
            </div>
            <div class="doctor-list">
                @foreach ($patientsWithoutPlan as $patient)
                    <div class="doctor-item compact">
                        <div><strong>{{ $patient->patient_name }}</strong></div>
                        <a class="doctor-btn" href="{{ route('doctor.patient-profile.show', $patient->profile_id) }}">تحديد خطة</a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</section>
@endsection
