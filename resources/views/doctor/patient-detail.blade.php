@extends('layouts.doctor')

@php
    $pageTitle = 'ملف المريض';
    $statusColors = ['pending' => 'amber', 'approved' => 'green', 'rejected' => 'rose'];
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/doctor/dashboard.css') }}?v={{ file_exists(public_path('front/css/doctor/dashboard.css')) ? filemtime(public_path('front/css/doctor/dashboard.css')) : '1' }}">
    <link rel="stylesheet" href="{{ asset('front/css/doctor/requests.css') }}?v={{ file_exists(public_path('front/css/doctor/requests.css')) ? filemtime(public_path('front/css/doctor/requests.css')) : '1' }}">

    {{-- تصميم CSS جديد بالكامل للصفحة --}}
    <style>
        /* ========================================================= */
        /* 1. تنسيق الصفحة العامة والكروت */
        /* ========================================================= */
        .dpat-wrapper { display: flex; flex-direction: column; gap: 22px; }

        .dpat-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .dpat-grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; }

        @media (max-width: 992px) { .dpat-grid-2 { grid-template-columns: 1fr; } }
        @media (max-width: 768px) { .dpat-grid-4 { grid-template-columns: repeat(2, 1fr); } }

        /* كارت موحد لجميع أقسام الصفحة */
        .dpat-card {
            background: var(--d-card);
            border: 1px solid var(--d-border);
            border-radius: 18px;
            padding: 20px 24px;
            box-shadow: var(--d-shadow);
            transition: box-shadow 0.2s ease;
        }
        .dpat-card:hover { box-shadow: var(--d-shadow-hover); }

        .dpat-card-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--d-border);
        }
        .dpat-card-header h3 {
            font-size: 1rem; font-weight: 800; color: var(--d-title); margin: 0;
            display: flex; align-items: center; gap: 8px;
        }
        .dpat-card-header h3 i { width: 20px; height: 20px; color: var(--d-green); }
        .dpat-card-header .dpat-badge { font-size: 0.7rem; padding: 4px 12px; border-radius: 50px; font-weight: 700; background: var(--d-soft-bg); color: var(--d-muted); }

        /* ========================================================= */
        /* 2. بطاقة المريض الرئيسية (Top Card) */
        /* ========================================================= */
        .dpat-hero {
            display: flex; align-items: center; justify-content: space-between;
            flex-wrap: wrap; gap: 20px;
            background: linear-gradient(135deg, var(--d-card) 0%, var(--d-soft-bg) 100%);
            border: 1px solid var(--d-border); border-radius: 20px;
            padding: 22px 28px; box-shadow: var(--d-shadow);
        }
        .dpat-hero-info { display: flex; align-items: center; gap: 18px; }
        .dpat-hero-avatar {
            width: 60px; height: 60px; border-radius: 18px; overflow: hidden;
            background: var(--d-green); color: #fff;
            display: grid; place-items: center;
            font-size: 1.4rem; font-weight: 800;
            border: 2px solid #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .dpat-hero-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .dpat-hero-text h2 { font-size: 1.3rem; font-weight: 800; color: var(--d-title); margin: 0 0 4px; }
        .dpat-hero-text p { font-size: 0.85rem; color: var(--d-muted); margin: 0; font-weight: 600; }
        .dpat-hero-tags { display: flex; gap: 8px; margin-top: 6px; flex-wrap: wrap; }

        .dpat-hero-actions { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .dpat-btn {
            padding: 8px 20px; border-radius: 12px; border: 0;
            font-size: 0.85rem; font-weight: 800; cursor: pointer;
            transition: 0.2s ease; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;
        }
        .dpat-btn-success { background: var(--d-green); color: #fff; }
        .dpat-btn-success:hover { background: #0f7a57; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(29,158,117,.3); }
        .dpat-btn-danger { background: #ef4444; color: #fff; }
        .dpat-btn-danger:hover { background: #dc2626; transform: translateY(-2px); }
        .dpat-btn-muted { background: var(--d-soft-bg); color: var(--d-text); }
        .dpat-btn-muted:hover { background: var(--d-border); }

        /* ========================================================= */
        /* 3. كروت الإحصائيات السريعة (4 كروت) */
        /* ========================================================= */
        .dpat-mini-stat {
            background: var(--d-card); border: 1px solid var(--d-border);
            border-radius: 16px; padding: 16px 18px;
            box-shadow: var(--d-shadow); text-align: center;
        }
        .dpat-mini-stat i { width: 22px; height: 22px; color: var(--d-green); margin-bottom: 4px; }
        .dpat-mini-stat strong { display: block; font-size: 1rem; font-weight: 800; color: var(--d-title); margin-bottom: 2px; }
        .dpat-mini-stat span { font-size: 0.75rem; font-weight: 600; color: var(--d-muted); }

        /* ========================================================= */
        /* 4. كارت الملف الطبي */
        /* ========================================================= */
        .dpat-med-row {
            display: flex; align-items: center; justify-content: space-between;
            padding: 10px 14px; background: var(--d-soft-bg);
            border-radius: 12px; margin-bottom: 8px;
        }
        .dpat-med-row:last-child { margin-bottom: 0; }
        .dpat-med-row__label { font-size: 0.8rem; font-weight: 700; color: var(--d-muted); display: flex; align-items: center; gap: 6px; }
        .dpat-med-row__label i { width: 16px; height: 16px; }
        .dpat-med-row__value { font-size: 0.9rem; font-weight: 750; color: var(--d-title); }

        /* ========================================================= */
        /* 5. كارت التغذية (الوجبات) + نموذج السعرات */
        /* ========================================================= */
        .dpat-food-list { display: flex; flex-direction: column; gap: 8px; margin-top: 12px; }
        .dpat-food-item {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 14px; background: var(--d-soft-bg); border-radius: 12px;
            border-right: 4px solid var(--d-green);
        }
        .dpat-food-item i { width: 16px; height: 16px; color: var(--d-green); }
        .dpat-food-item div { flex: 1; }
        .dpat-food-item strong { display: block; font-size: 0.85rem; font-weight: 700; color: var(--d-title); }
        .dpat-food-item small { font-size: 0.75rem; color: var(--d-muted); font-weight: 600; }

        .dpat-goal-form { margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--d-border); display: flex; flex-direction: column; gap: 12px; }
        .dpat-goal-row { display: flex; flex-direction: column; gap: 4px; }
        .dpat-goal-row label { font-size: 0.75rem; font-weight: 700; color: var(--d-muted); }
        .dpat-goal-row input, .dpat-goal-row select, .dpat-goal-row textarea {
            padding: 8px 12px; border-radius: 10px; border: 1px solid var(--d-border);
            background: var(--d-card); font-size: 0.85rem; font-weight: 600; color: var(--d-title);
            font-family: inherit; transition: 0.2s; width: 100%;
        }
        .dpat-goal-row input:focus, .dpat-goal-row select:focus, .dpat-goal-row textarea:focus { outline: none; border-color: var(--d-green); }
        .dpat-goal-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }

        /* كلاس الإخفاء للفورم */
        .d-none { display: none !important; }

        /* ========================================================= */
        /* 6. كارت الوزن والرسم البياني */
        /* ========================================================= */
        .dpat-chart-box { height: 180px; position: relative; margin-top: 10px; }
        .dpat-weight-summary {
            display: flex; justify-content: space-around; padding: 12px;
            background: var(--d-soft-bg); border-radius: 14px; margin-bottom: 12px;
        }
        .dpat-weight-summary div { text-align: center; }
        .dpat-weight-summary span { display: block; font-size: 0.7rem; font-weight: 700; color: var(--d-muted); }
        .dpat-weight-summary strong { font-size: 1rem; font-weight: 800; color: var(--d-title); }

        /* ========================================================= */
        /* 7. كارت المهام */
        /* ========================================================= */
        .dpat-task-item {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 0; border-bottom: 1px solid var(--d-border);
        }
        .dpat-task-item:last-child { border-bottom: 0; }
        .dpat-task-item .dpat-check { width: 22px; height: 22px; border-radius: 50%; display: grid; place-items: center; }
        .dpat-task-item .dpat-check.completed { background: #10b981; color: #fff; }
        .dpat-task-item .dpat-check.pending { background: var(--d-soft-bg); color: var(--d-muted); }
        .dpat-task-item div { flex: 1; }
        .dpat-task-item strong { display: block; font-size: 0.85rem; font-weight: 700; color: var(--d-title); }
        .dpat-task-item small { font-size: 0.75rem; color: var(--d-muted); font-weight: 600; }

        /* ========================================================= */
        /* 8. كارت المواعيد */
        /* ========================================================= */
        .dpat-apt-item {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 14px; background: var(--d-soft-bg); border-radius: 12px; margin-bottom: 8px;
        }
        .dpat-apt-item:last-child { margin-bottom: 0; }
        .dpat-apt-item i { width: 18px; height: 18px; color: var(--d-green); }
        .dpat-apt-item div { flex: 1; }
        .dpat-apt-item strong { display: block; font-size: 0.85rem; font-weight: 700; color: var(--d-title); }
        .dpat-apt-item small { font-size: 0.75rem; color: var(--d-muted); font-weight: 600; }

        /* التجاوب للجوال */
        @media (max-width: 640px) {
            .dpat-hero { flex-direction: column; align-items: flex-start; }
            .dpat-hero-actions { width: 100%; justify-content: flex-start; }
            .dpat-goal-grid-3 { grid-template-columns: 1fr 1fr; }
        }
    </style>
@endpush

@section('content')
<section class="ddash">

    {{-- زر العودة --}}
    <div style="margin-bottom: 10px;">
        <a href="{{ route('doctor.patient-requests.index') }}" class="dreq-back-btn">
            <i data-lucide="arrow-right" style="width:15px;height:15px"></i> رجوع لطلبات الاستشارة
        </a>
    </div>

    <div class="dpat-wrapper">

        {{-- ========================================== --}}
        {{-- 1. بطاقة المريض الرئيسية (Hero) --}}
        {{-- ========================================== --}}
        <div class="dpat-hero">
            <div class="dpat-hero-info">
                <div class="dpat-hero-avatar">
                    @if (!empty($avatarUrl))
                        <img src="{{ $avatarUrl }}" alt="{{ $profile->patient_name }}">
                    @else
                        {{ mb_substr($profile->patient_name ?? '؟', 0, 1) }}
                    @endif
                </div>
                <div class="dpat-hero-text">
                    <h2>{{ $profile->patient_name ?? 'مريض' }}</h2>
                    <p>{{ $profile->patient_email ?? '' }}</p>
                    <div class="dpat-hero-tags">
                        @if ($age)<span class="ddash-pill ddash-pill--muted">{{ $age }} سنة</span>@endif
                        <span class="ddash-pill ddash-pill--muted">{{ $genderLabel }}</span>
                    </div>
                </div>
            </div>

            <div class="dpat-hero-actions">
                @if ($requestStatus === 'pending')
                    <button type="button" class="dpat-btn dpat-btn-danger" data-reject-open="{{ route('doctor.patient-requests.reject', $profile->id) }}">رفض</button>
                    <form method="POST" action="{{ route('doctor.patient-requests.approve', $profile->id) }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="dpat-btn dpat-btn-success">قبول</button>
                    </form>
                @elseif ($requestStatus === 'approved')
                    <form method="POST" action="{{ route('doctor.patient-requests.complete', $profile->id) }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="dpat-btn dpat-btn-muted">إنهاء المتابعة</button>
                    </form>
                @endif
                <span class="ddash-pill ddash-pill--{{ $statusColors[$requestStatus] ?? 'muted' }}" style="font-size:.8rem;padding:6px 16px; background: var(--d-green); color:#fff;">
                    {{ $requestStatusLabel }}
                </span>
            </div>
        </div>

        {{-- ========================================== --}}
        {{-- 2. كروت الإحصائيات السريعة (4 كروت) --}}
        {{-- ========================================== --}}
        <div class="dpat-grid-4">
            <div class="dpat-mini-stat">
                <i data-lucide="target"></i>
                <strong>{{ $profile->health_goal ?: '—' }}</strong>
                <span>الهدف الصحي</span>
            </div>
            <div class="dpat-mini-stat">
                <i data-lucide="activity"></i>
                <strong>{{ $profile->activity_level ?: '—' }}</strong>
                <span>مستوى النشاط</span>
            </div>
            <div class="dpat-mini-stat">
                <i data-lucide="moon"></i>
                <strong>{{ $profile->sleep_hours ?: '—' }}</strong>
                <span>ساعات النوم</span>
            </div>
            <div class="dpat-mini-stat">
                <i data-lucide="glass-water"></i>
                <strong>{{ $profile->water_cups ?: '—' }}</strong>
                <span>أكواب الماء</span>
            </div>
        </div>

        {{-- ========================================== --}}
        {{-- 3. الملف الطبي --}}
        {{-- ========================================== --}}
        <div class="dpat-card">
            <div class="dpat-card-header">
                <h3><i data-lucide="clipboard-list"></i> الملف الطبي</h3>
            </div>
            <div style="display:flex; flex-wrap:wrap; gap:8px; margin-bottom:14px;">
                @forelse ($conditions as $cond)
                    <span class="ddash-pill ddash-pill--rose dpat-tag">{{ is_array($cond) ? ($cond['name'] ?? '') : $cond }}</span>
                @empty
                    <span class="ddash-pill ddash-pill--green dpat-tag">لا يوجد حالات مزمنة مسجّلة</span>
                @endforelse
            </div>
            <div class="dpat-med-row">
                <span class="dpat-med-row__label"><i data-lucide="pill"></i> الأدوية</span>
                <span class="dpat-med-row__value">{{ $profile->medications ?: 'لا يوجد' }}</span>
            </div>
            <div class="dpat-med-row">
                <span class="dpat-med-row__label"><i data-lucide="triangle-alert"></i> الحساسية</span>
                <span class="dpat-med-row__value">{{ $profile->allergies ?: 'لا يوجد' }}</span>
            </div>
        </div>

        {{-- ========================================== --}}
        {{-- 4. قسم التغذية (فقط إذا كان الطلب مقبولاً) --}}
        {{-- ========================================== --}}
        @if ($requestStatus === 'approved')
            <div class="dpat-grid-2">

                {{-- 4-أ. كارت ملخص التغذية --}}
                <div class="dpat-card">
                    <div class="dpat-card-header">
                        <h3><i data-lucide="utensils"></i> ملخص التغذية (7 أيام)</h3>
                        <span class="dpat-badge">{{ $mealsCount }} وجبة</span>
                    </div>

                    @if ($mealsCount > 0)
                        <div style="display:flex; justify-content:space-between; text-align:center; padding:10px; background:var(--d-soft-bg); border-radius:12px; margin-bottom:12px;">
                            <div><span style="display:block; font-size:0.7rem; color:var(--d-muted); font-weight:700;">متوسط سعرات</span><strong style="font-size:1rem; color:var(--d-title);">{{ $avgCalories }}</strong></div>
                            <div><span style="display:block; font-size:0.7rem; color:var(--d-muted); font-weight:700;">متوسط بروتين</span><strong style="font-size:1rem; color:var(--d-title);">{{ $avgProtein }} غ</strong></div>
                        </div>

                        <div class="dpat-food-list">
                            @foreach ($recentMeals->take(3) as $meal)
                                <div class="dpat-food-item">
                                    <i data-lucide="utensils"></i>
                                    <div>
                                        <strong>{{ $meal->meal_name ?? 'وجبة' }}</strong>
                                        <small>{{ \Illuminate\Support\Carbon::parse($meal->meal_date)->locale('ar')->translatedFormat('j M') }} · {{ $meal->calories }} سعرة</small>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="ddash-empty" style="padding: 10px 0;"><i data-lucide="utensils"></i><p style="font-size:0.9rem;">ما سجّل المريض أي وجبة آخر 7 أيام.</p></div>
                    @endif
                </div>

                {{-- 4-ب. كارت تحديد هدف السعرات (تصميم أنيق ومريح للعين) --}}
                <div class="dpat-card">
                    <div class="dpat-card-header">
                        <h3><i data-lucide="target"></i> خطة السعرات اليومية</h3>
                        <div style="display:flex; gap:8px; align-items:center;">
                            <span class="dpat-badge">{{ $currentGoal ? 'محدثة' : 'جديدة' }}</span>
                            @if($currentGoal)
                                <button type="button" class="dpat-btn dpat-btn-muted" style="padding:4px 12px; font-size:0.7rem;" onclick="document.getElementById('editGoalForm').classList.toggle('d-none'); this.style.display='none'; document.getElementById('cancelEditGoalBtn').style.display='inline-flex';">تعديل</button>
                            @endif
                        </div>
                    </div>

                    {{-- 1. وضع العرض (View Mode) - بتصميم بطاقات أنيق --}}
                    <div id="viewGoalMode">
                        @if ($currentGoal)
                            {{-- شبكة عرض السعرات --}}
                            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 14px;">
                                <div style="background: var(--d-soft-bg); border-radius: 14px; padding: 12px; text-align: center; border: 1px solid var(--d-border);">
                                    <div style="font-size: 0.7rem; font-weight: 700; color: #f59e0b; display: flex; justify-content: center; align-items: center; gap: 4px; margin-bottom: 2px;">
                                        <i data-lucide="flame" style="width:14px;height:14px;"></i> السعرات
                                    </div>
                                    <strong style="font-size: 1.4rem; font-weight: 800; color: var(--d-title);">{{ $currentGoal->calories_goal }}</strong>
                                    <span style="display: block; font-size: 0.7rem; font-weight: 600; color: var(--d-muted);">Kcal</span>
                                </div>

                                <div style="background: var(--d-soft-bg); border-radius: 14px; padding: 12px; text-align: center; border: 1px solid var(--d-border);">
                                    <div style="font-size: 0.7rem; font-weight: 700; color: #3b82f6; display: flex; justify-content: center; align-items: center; gap: 4px; margin-bottom: 2px;">
                                        <i data-lucide="drumstick" style="width:14px;height:14px;"></i> بروتين
                                    </div>
                                    <strong style="font-size: 1.4rem; font-weight: 800; color: var(--d-title);">{{ $currentGoal->protein_goal ?: '—' }}</strong>
                                    <span style="display: block; font-size: 0.7rem; font-weight: 600; color: var(--d-muted);">غ</span>
                                </div>

                                <div style="background: var(--d-soft-bg); border-radius: 14px; padding: 12px; text-align: center; border: 1px solid var(--d-border);">
                                    <div style="font-size: 0.7rem; font-weight: 700; color: #10b981; display: flex; justify-content: center; align-items: center; gap: 4px; margin-bottom: 2px;">
                                        <i data-lucide="wheat" style="width:14px;height:14px;"></i> كارب
                                    </div>
                                    <strong style="font-size: 1.4rem; font-weight: 800; color: var(--d-title);">{{ $currentGoal->carbs_goal ?: '—' }}</strong>
                                    <span style="display: block; font-size: 0.7rem; font-weight: 600; color: var(--d-muted);">غ</span>
                                </div>

                                <div style="background: var(--d-soft-bg); border-radius: 14px; padding: 12px; text-align: center; border: 1px solid var(--d-border);">
                                    <div style="font-size: 0.7rem; font-weight: 700; color: #ef4444; display: flex; justify-content: center; align-items: center; gap: 4px; margin-bottom: 2px;">
                                        <i data-lucide="droplet" style="width:14px;height:14px;"></i> دهون
                                    </div>
                                    <strong style="font-size: 1.4rem; font-weight: 800; color: var(--d-title);">{{ $currentGoal->fat_goal ?: '—' }}</strong>
                                    <span style="display: block; font-size: 0.7rem; font-weight: 600; color: var(--d-muted);">غ</span>
                                </div>
                            </div>

                            {{-- عرض ملاحظة الطبيب --}}
                            @if($currentGoal->doctor_note)
                                <div style="font-size: 0.8rem; color: var(--d-text); font-weight: 600; padding: 8px 14px; background: var(--d-card); border-radius: 10px; border-right: 4px solid var(--d-green); display: flex; align-items: center; gap: 8px;">
                                    <i data-lucide="message-square" style="width:16px;height:16px;color:var(--d-green);"></i> {{ $currentGoal->doctor_note }}
                                </div>
                            @endif
                        @else
                            <div class="ddash-empty" style="padding: 10px 0;"><i data-lucide="target"></i><p style="font-size:0.9rem;">لم يتم تحديد هدف سعرات بعد.</p></div>
                        @endif
                    </div>

                    {{-- 2. وضع التعديل (Edit Mode) - مخفي افتراضياً --}}
                    <form id="editGoalForm" method="POST" action="{{ route('doctor.patients.calorie-goal', $profile->id) }}" class="dpat-goal-form d-none" style="margin-top:16px; padding-top:16px; border-top:1px solid var(--d-border);">
                        @csrf
                        <div class="dpat-goal-row">
                            <label>السعرات المستهدفة</label>
                            <input type="number" name="calories_goal" value="{{ $currentGoal->calories_goal ?? '' }}" placeholder="مثلاً 1800" required>
                        </div>
                        <div class="dpat-goal-row">
                            <label>مدة الخطة</label>
                            <select name="duration_days">
                                <option value="1">يوم واحد (اليوم فقط)</option>
                                <option value="7">7 أيام</option>
                                <option value="14">14 يوم</option>
                                <option value="30">30 يوم</option>
                            </select>
                        </div>
                        <div class="dpat-goal-grid-3">
                            <div class="dpat-goal-row"><label>بروتين (غ)</label><input type="number" name="protein_goal" value="{{ $currentGoal->protein_goal ?? '' }}"></div>
                            <div class="dpat-goal-row"><label>كارب (غ)</label><input type="number" name="carbs_goal" value="{{ $currentGoal->carbs_goal ?? '' }}"></div>
                            <div class="dpat-goal-row"><label>دهون (غ)</label><input type="number" name="fat_goal" value="{{ $currentGoal->fat_goal ?? '' }}"></div>
                        </div>
                        <div class="dpat-goal-row">
                            <label>ملاحظة للمريض</label>
                            <textarea name="doctor_note" rows="2" placeholder="مثلاً: قلّل الملح هالأسبوع...">{{ $currentGoal->doctor_note ?? '' }}</textarea>
                        </div>
                        <div style="display:flex; gap:10px; justify-content:flex-end;">
                            <button type="button" id="cancelEditGoalBtn" class="dpat-btn dpat-btn-muted" style="display:none;" onclick="document.getElementById('editGoalForm').classList.add('d-none'); document.getElementById('cancelEditGoalBtn').style.display='none'; document.querySelector('.dpat-card .dpat-btn-muted').style.display='inline-flex';">إلغاء</button>
                            <button type="submit" class="dpat-btn dpat-btn-success" style="padding:10px 24px;">حفظ التغييرات</button>
                        </div>
                    </form>
                </div>

            </div>
        @else
            {{-- رسالة إذا لم يتم قبول الطلب --}}
            <div class="dpat-card" style="background: rgba(245,158,11,.05); border-color: rgba(245,158,11,.2);">
                <div class="ddash-empty" style="padding: 10px 0; flex-direction:row; gap:10px;">
                    <i data-lucide="lock" style="color:#f59e0b;"></i>
                    <p style="color:#b45309; font-weight:600; margin:0;">متابعة التغذية وتحديد هدف السعرات متاحة فقط بعد الموافقة على طلب المتابعة.</p>
                </div>
            </div>
        @endif


        {{-- ========================================== --}}
        {{-- 5. الوزن والرسم البياني (للمقبولين فقط) --}}
        {{-- ========================================== --}}
        @if ($requestStatus === 'approved')
            <div class="dpat-grid-2">

                {{-- 5-أ. كارت الوزن --}}
                <div class="dpat-card">
                    <div class="dpat-card-header">
                        <h3><i data-lucide="scale"></i> تتبّع الوزن</h3>
                        <span class="dpat-badge">منذ {{ $followupStart->locale('ar')->translatedFormat('j F') }}</span>
                    </div>

                    @if ($weightLogs->count() >= 2)
                        @php
                            $firstW = (float) $weightLogs->first()->weight_kg;
                            $lastW = (float) $weightLogs->last()->weight_kg;
                            $diffW = round($lastW - $firstW, 1);
                        @endphp
                        <div class="dpat-weight-summary">
                            <div><span>أول قياس</span><strong>{{ $firstW }} كغم</strong></div>
                            <div><span>آخر قياس</span><strong>{{ $lastW }} كغم</strong></div>
                            <div><span>الفرق</span><strong style="color: {{ $diffW <= 0 ? 'var(--d-green)' : 'var(--d-rose)' }}">{{ $diffW > 0 ? '+' : '' }}{{ $diffW }} كغم</strong></div>
                        </div>
                        <div class="dpat-chart-box"><canvas id="dpatWeightChart"></canvas></div>
                    @elseif ($weightLogs->count() === 1)
                        <div style="text-align:center; padding:10px; background:var(--d-soft-bg); border-radius:12px; margin-bottom:12px;">
                            <strong style="font-size:1.2rem; color:var(--d-title);">{{ $weightLogs->first()->weight_kg }} كغم</strong>
                            <p style="font-size:0.8rem; color:var(--d-muted); margin:4px 0 0;">أول قياس مسجّل — بانتظار القياس التالي</p>
                        </div>
                    @else
                        <div class="ddash-empty" style="padding: 5px 0;"><i data-lucide="scale"></i><p>لسا ما في قياس وزن مسجّل.</p></div>
                    @endif

                    {{-- نموذج إضافة وزن --}}
                    <form method="POST" action="{{ route('doctor.patients.log-weight', $profile->id) }}" class="dpat-goal-form" style="margin-top:12px;">
                        @csrf
                        <div class="dpat-goal-grid-3" style="grid-template-columns: 1fr 1fr 1.5fr;">
                            <div class="dpat-goal-row"><label>الوزن (كغم)</label><input type="number" step="0.1" name="weight_kg" placeholder="مثلاً 78.5" required></div>
                            <div class="dpat-goal-row"><label>التاريخ</label><input type="date" name="logged_date" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}"></div>
                            <div class="dpat-goal-row"><label>ملاحظة</label><input type="text" name="note" placeholder="مثلاً: بعد الفحص"></div>
                        </div>
                        <button type="submit" class="dpat-btn dpat-btn-success" style="justify-content:center; width:100%; padding:10px;">تسجيل القياس</button>
                    </form>
                </div>

                {{-- 5-ب. كارت اتجاه السعرات --}}
                <div class="dpat-card">
                    <div class="dpat-card-header">
                        <h3><i data-lucide="trending-up"></i> اتجاه السعرات</h3>
                        <span class="dpat-badge">يومي</span>
                    </div>
                    @if (collect($caloriesTrend)->sum('calories') > 0)
                        <div class="dpat-chart-box" style="height: 220px;"><canvas id="dpatCaloriesChart"></canvas></div>
                    @else
                        <div class="ddash-empty" style="padding: 20px 0;"><i data-lucide="utensils"></i><p>ما في وجبات مسجّلة لعرض الاتجاه.</p></div>
                    @endif
                </div>

            </div>
        @endif


        {{-- ========================================== --}}
        {{-- 6. المهام والمواعيد (للمقبولين فقط) --}}
        {{-- ========================================== --}}
        @if ($requestStatus === 'approved')
            <div class="dpat-grid-2">

                {{-- 6-أ. كارت المهام --}}
                <div class="dpat-card">
                    <div class="dpat-card-header">
                        <h3><i data-lucide="list-checks"></i> مهام المريض</h3>
                        <div style="display:flex; gap:8px; align-items:center;">
                            <span class="dpat-badge">{{ $tasks->count() }} مهمة</span>
                            <button type="button" class="dpat-btn dpat-btn-muted" style="padding:4px 12px; font-size:0.7rem;" onclick="document.getElementById('addTaskForm').classList.toggle('d-none'); this.style.display='none'; document.getElementById('cancelAddTaskBtn').style.display='inline-flex';">+ إضافة مهمة</button>
                        </div>
                    </div>

                    {{-- 1. قائمة المهام --}}
                    <div style="display:flex; flex-direction:column;">
                        @forelse ($tasks as $task)
                            <div class="dpat-task-item">
                                <div class="dpat-check {{ $task->status === 'completed' ? 'completed' : 'pending' }}">
                                    <i data-lucide="{{ $task->status === 'completed' ? 'check' : 'clock' }}" style="width:12px;height:12px;"></i>
                                </div>
                                <div>
                                    <strong>{{ $task->title }}</strong>
                                    <small>{{ \Illuminate\Support\Carbon::parse($task->task_date)->locale('ar')->translatedFormat('j M Y') }} @if ($task->task_time) · {{ \Illuminate\Support\Carbon::parse($task->task_time)->format('H:i') }} @endif</small>
                                </div>
                                <span class="ddash-pill ddash-pill--{{ $task->status === 'completed' ? 'green' : 'amber' }}" style="font-size:0.7rem;">{{ $task->status === 'completed' ? 'أنجزها' : 'بانتظار' }}</span>
                            </div>
                        @empty
                            <div class="ddash-empty" style="padding: 10px 0;"><i data-lucide="list-checks"></i><p style="font-size:0.9rem;">ما حددتلها أي مهمة لسا.</p></div>
                        @endforelse
                    </div>

                    {{-- 2. نموذج إضافة مهمة (مخفي افتراضياً) --}}
                    <form id="addTaskForm" method="POST" action="{{ route('doctor.patients.tasks.assign', $profile->id) }}" class="dpat-goal-form d-none" style="margin-top:16px; padding-top:16px; border-top:1px solid var(--d-border);">
                        @csrf
                        <div class="dpat-goal-row"><label>عنوان المهمة</label><input type="text" name="title" placeholder="مثلاً: امشي 30 دقيقة" required minlength="3" maxlength="160"></div>
                        <div class="dpat-goal-grid-3">
                            <div class="dpat-goal-row"><label>التاريخ</label><input type="date" name="task_date" value="{{ now()->toDateString() }}" required></div>
                            <div class="dpat-goal-row"><label>الوقت</label><input type="time" name="task_time"></div>
                            <div class="dpat-goal-row"><label>وصف</label><input type="text" name="description" placeholder="تفاصيل إضافية"></div>
                        </div>
                        <div style="display:flex; gap:10px; justify-content:flex-end;">
                            <button type="button" id="cancelAddTaskBtn" class="dpat-btn dpat-btn-muted" style="display:none;" onclick="document.getElementById('addTaskForm').classList.add('d-none'); document.getElementById('cancelAddTaskBtn').style.display='none'; document.querySelector('.dpat-card .dpat-btn-muted').style.display='inline-flex';">إلغاء</button>
                            <button type="submit" class="dpat-btn dpat-btn-success" style="padding:10px 24px;">إسناد المهمة</button>
                        </div>
                    </form>
                </div>

                {{-- 6-ب. كارت المواعيد --}}
                <div class="dpat-card">
                    <div class="dpat-card-header">
                        <h3><i data-lucide="calendar-clock"></i> سجل المواعيد</h3>
                        <span class="dpat-badge">{{ $appointments->count() }} موعد</span>
                    </div>

                    <div style="display:flex; flex-direction:column;">
                        @forelse ($appointments as $apt)
                            <div class="dpat-apt-item">
                                <i data-lucide="{{ ($apt->consultation_type ?? '') === 'online' ? 'video' : 'building-2' }}"></i>
                                <div>
                                    <strong>{{ \Illuminate\Support\Carbon::parse($apt->appointment_date)->locale('ar')->translatedFormat('D j M Y') }}</strong>
                                    <small>
                                        @if ($apt->appointment_time) {{ \Illuminate\Support\Carbon::parse($apt->appointment_time)->format('H:i') }} · @endif
                                        {{ ($apt->consultation_type ?? '') === 'online' ? 'عن بُعد' : 'حضوري' }}
                                        @if (!empty($apt->reason)) · {{ $apt->reason }} @endif
                                    </small>
                                </div>
                                <span class="ddash-pill ddash-pill--{{ in_array($apt->status, ['confirmed','approved']) ? 'green' : ($apt->status === 'pending' ? 'amber' : ($apt->status === 'completed' ? 'blue' : 'muted')) }}" style="font-size:0.7rem;">
                                    {{ $apt->status }}
                                </span>
                            </div>
                        @empty
                            <div class="ddash-empty" style="padding: 10px 0;"><i data-lucide="calendar-x"></i><p>ما في مواعيد مسجّلة مع هالمريض بعد.</p></div>
                        @endforelse
                    </div>
                </div>

            </div>
        @endif

    </div>

    {{-- ========================================== --}}
    {{-- مودال سبب الرفض (اختياري) --}}
    {{-- ========================================== --}}
    <div class="dreq-modal-overlay" data-reject-modal>
        <div class="dreq-modal">
            <h3>رفض طلب المتابعة</h3>
            <p>ممكن تكتبي سبب الرفض (اختياري) — رح يوصل للمريض ضمن الإشعار.</p>
            <form method="POST" data-reject-form>
                @csrf
                <textarea name="doctor_response" rows="3" placeholder="مثلاً: التخصص غير مناسب لحالتك..."></textarea>
                <div class="dreq-modal__actions">
                    <button type="button" class="dreq-btn dreq-btn--muted" data-reject-cancel>إلغاء</button>
                    <button type="submit" class="dreq-btn dreq-btn--danger">تأكيد الرفض</button>
                </div>
            </form>
        </div>
    </div>

</section>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var rejectModal = document.querySelector('[data-reject-modal]');
    var rejectForm = document.querySelector('[data-reject-form]');
    var rejectCancel = document.querySelector('[data-reject-cancel]');

    if (rejectModal && rejectForm) {
        document.querySelectorAll('[data-reject-open]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                rejectForm.setAttribute('action', btn.getAttribute('data-reject-open'));
                rejectForm.querySelector('textarea').value = '';
                rejectModal.classList.add('is-open');
            });
        });

        function closeRejectModal() { rejectModal.classList.remove('is-open'); }

        if (rejectCancel) rejectCancel.addEventListener('click', closeRejectModal);
        rejectModal.addEventListener('click', function (e) { if (e.target === rejectModal) closeRejectModal(); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeRejectModal(); });
    }

    /* ألوان محاور/تسميات الرسم البياني */
    var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    var chartTickColor = isDark ? '#6b8479' : '#94a3b8';
    var chartGridColor = isDark ? 'rgba(255,255,255,.05)' : 'rgba(0,0,0,.04)';

    function verticalGradient(ctx, area, colorTop, colorBottom) {
        var gradient = ctx.createLinearGradient(0, area.top, 0, area.bottom);
        gradient.addColorStop(0, colorTop);
        gradient.addColorStop(1, colorBottom);
        return gradient;
    }

    /* رسم بياني الوزن */
    var weightCanvas = document.getElementById('dpatWeightChart');
    if (weightCanvas && typeof Chart !== 'undefined') {
        new Chart(weightCanvas, {
            type: 'line',
            data: {
                labels: @json($weightLogs->map(fn($w) => \Illuminate\Support\Carbon::parse($w->logged_date)->locale('ar')->translatedFormat('j M'))),
                datasets: [{
                    data: @json($weightLogs->pluck('weight_kg')),
                    borderColor: '#8b5cf6',
                    backgroundColor: function (context) {
                        var chart = context.chart;
                        var chartArea = chart.chartArea;
                        if (!chartArea) return 'rgba(139,92,246,.1)';
                        return verticalGradient(chart.ctx, chartArea, 'rgba(139,92,246,.28)', 'rgba(139,92,246,.02)');
                    },
                    fill: true,
                    tension: .35,
                    pointRadius: 4,
                    pointBackgroundColor: '#8b5cf6',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { ticks: { precision: 1, color: chartTickColor }, grid: { color: chartGridColor } },
                    x: { ticks: { color: chartTickColor }, grid: { display: false } }
                }
            }
        });
    }

    /* رسم بياني السعرات */
    var calCanvas = document.getElementById('dpatCaloriesChart');
    if (calCanvas && typeof Chart !== 'undefined') {
        var goalValue = {{ $currentGoal->calories_goal ?? 'null' }};
        var calLabels = @json(collect($caloriesTrend)->pluck('label'));
        var calDatasets = [{
            label: 'السعرات الفعلية',
            data: @json(collect($caloriesTrend)->pluck('calories')),
            borderColor: '#14b8a6',
            backgroundColor: function (context) {
                var chart = context.chart;
                var chartArea = chart.chartArea;
                if (!chartArea) return 'rgba(20,184,166,.12)';
                return verticalGradient(chart.ctx, chartArea, 'rgba(20,184,166,.30)', 'rgba(20,184,166,.02)');
            },
            fill: true,
            tension: .35,
            pointRadius: 3,
            pointBackgroundColor: '#14b8a6',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
        }];

        if (goalValue) {
            calDatasets.push({
                label: 'هدف الطبيب',
                data: calLabels.map(function () { return goalValue; }),
                borderColor: 'rgba(180,83,9,.55)',
                borderDash: [6, 6],
                borderWidth: 2,
                pointRadius: 0,
                fill: false,
                tension: 0,
            });
        }

        new Chart(calCanvas, {
            type: 'line',
            data: { labels: calLabels, datasets: calDatasets },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { display: !!goalValue, position: 'top', align: 'end', labels: { boxWidth: 12, font: { size: 11 }, color: chartTickColor } },
                    tooltip: { backgroundColor: isDark ? '#1e293b' : '#0f172a' }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0, color: chartTickColor }, grid: { color: chartGridColor } },
                    x: { ticks: { color: chartTickColor }, grid: { display: false } }
                }
            }
        });
    }
});
</script>
@endpush
@endsection
