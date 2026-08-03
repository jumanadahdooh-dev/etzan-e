@extends('layouts.doctor')

@php
    $pageTitle = 'الخطط الغذائية';
    $activePage = 'plans';

    $statusLabels = ['approved' => 'معتمدة', 'pending' => 'بانتظار المراجعة'];
    $statusPill = ['approved' => 'green', 'pending' => 'amber'];
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/doctor/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('front/css/doctor/requests.css') }}">

    <style>
        /* ==========================================================================
           ديزاين صفحة الخطط التفصيلية الجديد
           ========================================================================== */

        .dplan-section-title {
            display: flex; align-items: center; gap: 10px;
            font-size: 1.1rem; font-weight: 800; color: var(--d-title);
            margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid var(--d-border);
        }
        .dplan-section-title i { width: 22px; height: 22px; color: var(--d-green); }
        .dplan-badge {
            background: var(--d-soft-bg); color: var(--d-muted);
            font-size: 0.75rem; font-weight: 700; padding: 2px 10px; border-radius: 50px; margin-right: auto;
        }

        .dplan-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); /* تكبير البطاقات */
            gap: 20px;
        }

        /* تصميم الكارت التفصيلي الكبير */
        .dplan-card {
            background: var(--d-card); border: 1px solid var(--d-border);
            border-radius: 20px; padding: 22px 24px;
            box-shadow: var(--d-shadow);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            display: flex; flex-direction: column; gap: 16px;
        }
        .dplan-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--d-shadow-hover);
            border-color: var(--d-green);
        }

        /* رأس الكارت */
        .dplan-card-header {
            display: flex; justify-content: space-between; align-items: flex-start;
            border-bottom: 1px solid var(--d-border); padding-bottom: 14px;
        }
        .dplan-user { display: flex; align-items: center; gap: 12px; }
        .dplan-user h3 { font-size: 1rem; font-weight: 800; color: var(--d-title); margin: 0; line-height: 1.2; }
        .dplan-user small { font-size: 0.75rem; color: var(--d-muted); font-weight: 600; }

        /* قسم الماكروز (الأشرطة الملونة) */
        .dplan-macros-wrap {
            background: var(--d-soft-bg); border-radius: 14px; padding: 14px; display: flex; flex-direction: column; gap: 8px;
        }
        .dplan-macro-row { display: flex; justify-content: space-between; align-items: center; gap: 10px; }
        .dplan-macro-label { font-size: 0.75rem; font-weight: 700; color: var(--d-muted); min-width: 50px; display: flex; align-items: center; gap: 4px; }
        .dplan-macro-label i { width: 14px; height: 14px; }

        .dplan-macro-bar {
            flex: 1; height: 16px; background: var(--d-card); border-radius: 50px; overflow: hidden; position: relative;
        }
        .dplan-macro-fill {
            height: 100%; border-radius: 50px;
            display: flex; align-items: center; justify-content: flex-end;
            padding-right: 6px; font-size: 0.6rem; font-weight: 800; color: #fff; white-space: nowrap;
        }

        /* شبكة التفاصيل (4 أقسام أسفل البطاقة) */
        .dplan-details-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 4px;
        }
        .dplan-detail-box {
            background: var(--d-soft-bg); border-radius: 12px; padding: 12px 14px;
            border: 1px solid transparent; transition: border-color 0.2s;
        }
        .dplan-detail-box:hover { border-color: var(--d-border); }

        .dplan-detail-label {
            font-size: 0.65rem; font-weight: 700; color: var(--d-muted);
            text-transform: uppercase; letter-spacing: 0.3px; display: flex; align-items: center; gap: 4px; margin-bottom: 4px;
        }
        .dplan-detail-label i { width: 12px; height: 12px; }

        .dplan-detail-content { font-size: 0.85rem; font-weight: 700; color: var(--d-title); line-height: 1.4; }
        .dplan-detail-content.empty { color: var(--d-muted); font-weight: 600; font-size: 0.8rem; }

        /* زر مخصص للدخول للملف */
        .dplan-btn-profile {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            padding: 10px; margin-top: 8px; background: var(--d-green); color: #fff !important;
            border-radius: 14px; font-weight: 800; font-size: 0.9rem; text-decoration: none !important;
            transition: background 0.2s ease;
        }
        .dplan-btn-profile:hover { background: #0d6e4f; }

        /* مرضى بلا خطط */
        .dplan-waiting-list { display: flex; flex-direction: column; gap: 12px; }
        .dplan-waiting-item {
            display: flex; justify-content: space-between; align-items: center;
            padding: 14px 18px; background: var(--d-card); border: 1px solid var(--d-border);
            border-radius: 14px; border-right: 4px solid #f59e0b;
        }
        .dplan-waiting-item .dplan-user h4 { font-size: 0.9rem; font-weight: 700; margin:0; color: var(--d-title); }
        .dplan-btn-create {
            display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px;
            background: #f59e0b; color: #fff !important; border-radius: 10px; font-weight: 700; font-size: 0.8rem;
            text-decoration: none !important; transition: background 0.2s ease;
        }
        .dplan-btn-create:hover { background: #d97706; }

        @media (max-width: 768px) {
            .dplan-grid { grid-template-columns: 1fr; }
            .dplan-details-grid { grid-template-columns: 1fr; }
            .dplan-waiting-item { flex-direction: column; align-items: flex-start; gap: 12px; }
            .dplan-waiting-item .dplan-btn-create { width: 100%; justify-content: center; }
        }
    </style>
@endpush

@section('content')
<section class="ddash new-plans-page">

    <!-- الهيدر -->
    <div class="ddash-section-head">
        <div>
            <h2>الخطط الغذائية</h2>
            <span>مراجعة الخطط، المهام، المواعيد، والوزن لكل مريض</span>
        </div>
        <div class="dreq-back-btn" style="cursor:default; pointer-events:none; opacity:0.5;">
            <i data-lucide="calendar-days"></i> {{ now()->format('d M Y') }}
        </div>
    </div>

    <!-- الإحصائيات -->
    <div class="ddash-stats-grid" style="grid-template-columns:repeat(3,1fr)">
        <div class="ddash-stat-card ddash-accent--green">
            <div class="ddash-stat-card__top"><span class="ddash-stat-card__icon"><i data-lucide="clipboard-check"></i></span></div>
            <div class="ddash-stat-card__num">{{ $plans->count() }}</div>
            <div class="ddash-stat-card__label">خطط نشطة</div>
        </div>
        <div class="ddash-stat-card ddash-accent--amber">
            <div class="ddash-stat-card__top"><span class="ddash-stat-card__icon"><i data-lucide="user-round-plus"></i></span></div>
            <div class="ddash-stat-card__num">{{ $patientsWithoutPlan->count() }}</div>
            <div class="ddash-stat-card__label">بحاجة لخطة</div>
        </div>
        <div class="ddash-stat-card ddash-accent--blue">
            <div class="ddash-stat-card__top"><span class="ddash-stat-card__icon"><i data-lucide="users-round"></i></span></div>
            <div class="ddash-stat-card__num">{{ $plans->count() + $patientsWithoutPlan->count() }}</div>
            <div class="ddash-stat-card__label">إجمالي المرضى</div>
        </div>
    </div>


    <!-- ========================================== -->
    <!-- قسم: الخطط النشطة (التفاصيل الكاملة) -->
    <!-- ========================================== -->
    <div class="dplan-section-title">
        <i data-lucide="users"></i> خطط المرضى الحالية
        <span class="dplan-badge">{{ $plans->count() }}</span>
    </div>

    @if ($plans->isEmpty())
        <div class="ddash-empty"><i data-lucide="clipboard-list"></i><p>ما في خطط سعرات محددة لأي مريض لسا. ابدأ بتحديد الأهداف الآن.</p></div>
    @else
        <div class="dplan-grid">
            @foreach ($plans as $plan)
                <div class="dplan-card">
                    <!-- 1. رأس البطاقة -->
                    <div class="dplan-card-header">
                        <div class="dplan-user">
                            <span class="ddash-avatar ddash-avatar--green">{{ mb_substr($plan->patient_name, 0, 1) }}</span>
                            <div>
                                <h3>{{ $plan->patient_name }}</h3>
                                <small>آخر تحديث: {{ \Illuminate\Support\Carbon::parse($plan->goal_date)->locale('ar')->translatedFormat('j M Y') }}</small>
                            </div>
                        </div>
                        <span class="ddash-pill ddash-pill--{{ $statusPill[$plan->status] ?? 'muted' }}" style="font-size:0.7rem;">
                            {{ $statusLabels[$plan->status] ?? $plan->status }}
                        </span>
                    </div>

                    <!-- 2. الماكروز (بتصميم شريطي أنيق) -->
                    <div class="dplan-macros-wrap">
                        <div class="dplan-macro-row">
                            <span class="dplan-macro-label"><i data-lucide="flame"></i> سعرات</span>
                            <div class="dplan-macro-bar">
                                <div class="dplan-macro-fill" style="width: 100%; background: #f59e0b;">{{ number_format($plan->calories_goal) }} kcal</div>
                            </div>
                        </div>
                        <div class="dplan-macro-row">
                            <span class="dplan-macro-label"><i data-lucide="drumstick"></i> بروتين</span>
                            <div class="dplan-macro-bar">
                                <div class="dplan-macro-fill" style="width: {{ min(100, ($plan->protein_goal / 200) * 100) }}%; background: #3b82f6;">{{ $plan->protein_goal ?? '0' }} غ</div>
                            </div>
                        </div>
                        <div class="dplan-macro-row">
                            <span class="dplan-macro-label"><i data-lucide="wheat"></i> كارب</span>
                            <div class="dplan-macro-bar">
                                <div class="dplan-macro-fill" style="width: {{ min(100, ($plan->carbs_goal / 300) * 100) }}%; background: #10b981;">{{ $plan->carbs_goal ?? '0' }} غ</div>
                            </div>
                        </div>
                        <div class="dplan-macro-row">
                            <span class="dplan-macro-label"><i data-lucide="droplet"></i> دهون</span>
                            <div class="dplan-macro-bar">
                                <div class="dplan-macro-fill" style="width: {{ min(100, ($plan->fat_goal / 100) * 100) }}%; background: #ef4444;">{{ $plan->fat_goal ?? '0' }} غ</div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. تفاصيل الخطة (4 مربعات) -->
                    <div class="dplan-details-grid">
                        {{-- مربع 1: آخر الوجبات --}}
                        <div class="dplan-detail-box">
                            <div class="dplan-detail-label"><i data-lucide="utensils"></i> آخر الوجبات</div>
                            @if(isset($plan->recent_meals) && $plan->recent_meals->count() > 0)
                                <div class="dplan-detail-content" style="font-size:0.8rem; line-height:1.6;">
                                    {{-- تم التصحيح هنا: استخدام take(2) بدلاً من array_slice --}}
                                    @foreach($plan->recent_meals->take(2) as $meal)
                                        <div>• {{ $meal->meal_name ?? 'وجبة' }} ({{ $meal->calories }} سعرة)</div>
                                    @endforeach
                                    @if($plan->recent_meals->count() > 2)
                                        <div style="color:var(--d-muted); font-size:0.75rem;">+ {{ $plan->recent_meals->count() - 2 }} وجبات أخرى...</div>
                                    @endif
                                </div>
                            @else
                                <div class="dplan-detail-content empty">لم يسجل وجبات</div>
                            @endif
                        </div>

                        {{-- مربع 2: آخر مهمة --}}
                        <div class="dplan-detail-box">
                            <div class="dplan-detail-label"><i data-lucide="list-checks"></i> آخر مهمة</div>
                            @if(isset($plan->recent_task))
                                <div class="dplan-detail-content">
                                    {{ $plan->recent_task->title }}
                                    <div style="font-size:0.7rem; color:var(--d-muted); font-weight:600; margin-top:2px;">
                                        {{ \Illuminate\Support\Carbon::parse($plan->recent_task->task_date)->locale('ar')->translatedFormat('j M') }}
                                        · <span class="ddash-pill ddash-pill--{{ $plan->recent_task->status === 'completed' ? 'green' : 'amber' }}" style="font-size:0.55rem; padding:1px 8px;">{{ $plan->recent_task->status === 'completed' ? 'أنجزها' : 'بانتظار' }}</span>
                                    </div>
                                </div>
                            @else
                                <div class="dplan-detail-content empty">لا توجد مهام</div>
                            @endif
                        </div>

                        {{-- مربع 3: آخر قياس وزن --}}
                        <div class="dplan-detail-box">
                            <div class="dplan-detail-label"><i data-lucide="scale"></i> آخر وزن</div>
                            @if(isset($plan->last_weight))
                                <div class="dplan-detail-content">{{ $plan->last_weight->weight_kg }} كغم <small style="color:var(--d-muted); font-weight:600;">({{ \Illuminate\Support\Carbon::parse($plan->last_weight->logged_date)->locale('ar')->translatedFormat('j M') }})</small></div>
                            @else
                                <div class="dplan-detail-content empty">لم يسجل وزن</div>
                            @endif
                        </div>

                        {{-- مربع 4: الموعد القادم --}}
                        <div class="dplan-detail-box">
                            <div class="dplan-detail-label"><i data-lucide="calendar"></i> الموعد القادم</div>
                            @if(isset($plan->upcoming_appointment))
                                <div class="dplan-detail-content">
                                    {{ \Illuminate\Support\Carbon::parse($plan->upcoming_appointment->appointment_date)->locale('ar')->translatedFormat('D j M') }}
                                    <div style="font-size:0.7rem; color:var(--d-muted); font-weight:600;">
                                        @if($plan->upcoming_appointment->appointment_time) {{ \Illuminate\Support\Carbon::parse($plan->upcoming_appointment->appointment_time)->format('H:i') }} @endif
                                    </div>
                                </div>
                            @else
                                <div class="dplan-detail-content empty">لا يوجد موعد</div>
                            @endif
                        </div>
                    </div>

                    <!-- 4. ملاحظة الطبيب -->
                    @if ($plan->doctor_note)
                        <div style="margin-top:4px; font-size:0.8rem; color:var(--d-text); font-weight:600; padding:6px 12px; background:var(--d-card); border-radius:8px; border-right:3px solid var(--d-green);">
                            <i data-lucide="message-square" style="width:14px;height:14px;color:var(--d-green);"></i> {{ $plan->doctor_note }}
                        </div>
                    @endif

                    <!-- 5. زر عرض الملف الكامل -->
                    <a class="dplan-btn-profile" href="{{ route('doctor.patient-profile.show', $plan->profile_id) }}">
                        <i data-lucide="user"></i> عرض الملف الكامل للمريض
                    </a>
                </div>
            @endforeach
        </div>
    @endif


    <!-- ========================================== -->
    <!-- قسم: مرضى بدون خطة -->
    <!-- ========================================== -->
    @if ($patientsWithoutPlan->isNotEmpty())
        <div class="dplan-section-title" style="margin-top: 40px;">
            <i data-lucide="triangle-alert" style="color:#f59e0b;"></i> مرضى بحاجة لخطة سعرات
            <span class="dplan-badge" style="background: #f59e0b; color:#fff;">{{ $patientsWithoutPlan->count() }}</span>
        </div>

        <div class="dplan-waiting-list">
            @foreach ($patientsWithoutPlan as $patient)
                <div class="dplan-waiting-item">
                    <div class="dplan-user">
                        <span class="ddash-avatar ddash-avatar--amber">{{ mb_substr($patient->patient_name, 0, 1) }}</span>
                        <div>
                            <h4>{{ $patient->patient_name }}</h4>
                            <small>لم يتم تحديد أهداف بعد</small>
                        </div>
                    </div>
                    <a class="dplan-btn-create" href="{{ route('doctor.patient-profile.show', $patient->profile_id) }}">
                        <i data-lucide="plus-circle"></i> تحديد خطة
                    </a>
                </div>
            @endforeach
        </div>
    @endif
</section>
@endsection
