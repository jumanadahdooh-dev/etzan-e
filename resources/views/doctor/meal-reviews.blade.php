@extends('layouts.doctor')

@php
    $pageTitle = 'مراجعة الوجبات AI';
    $activePage = 'meal-reviews';

    $mealTypeLabels = [
        'breakfast' => 'فطور',
        'lunch' => 'غداء',
        'dinner' => 'عشاء',
        'snack' => 'سناك',
    ];
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/doctor/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('front/css/doctor/requests.css') }}">
    <style>
        .mrev-filterbar { display: flex; flex-wrap: wrap; align-items: center; gap: 16px; }
        .mrev-filterbar__field { display: flex; flex-direction: column; gap: 6px; }
        .mrev-filterbar__field label { font-size: .74rem; font-weight: 800; color: var(--d-muted); }
        .mrev-filterbar__check { display: flex; align-items: center; gap: 8px; font-size: .82rem; font-weight: 750; color: var(--d-text); margin-top: auto; padding-bottom: 2px; }

        .mrev-card { background: var(--d-card); border: 1px solid var(--d-border); border-radius: 16px; padding: 16px 20px; box-shadow: var(--d-shadow); }
        .mrev-card + .mrev-card { margin-top: 14px; }
        .mrev-card__top { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
        .mrev-card__who { display: flex; align-items: center; gap: 12px; }
        .mrev-card__who strong { display: block; font-size: .92rem; font-weight: 800; color: var(--d-title); }
        .mrev-card__who small { display: block; font-size: .76rem; color: var(--d-muted); font-weight: 650; margin-top: 2px; }

        .mrev-stats { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 12px; }
        .mrev-stats span { padding: 4px 11px; border-radius: 999px; font-size: .72rem; font-weight: 750; background: var(--d-soft-bg); color: var(--d-text); }

        .mrev-quote { display: flex; gap: 8px; margin-top: 10px; padding: 9px 12px; border-radius: 12px; background: var(--d-soft-bg); font-size: .78rem; font-weight: 650; color: var(--d-text); line-height: 1.6; }
        .mrev-quote i { width: 15px; height: 15px; flex-shrink: 0; margin-top: 2px; color: var(--d-muted); }
        .mrev-quote strong { font-weight: 800; color: var(--d-title); }

        .mrev-note { margin-top: 12px; border-top: 1px solid var(--d-border); padding-top: 12px; }
        .mrev-note-summary { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; cursor: pointer; }
        .mrev-note-summary p { flex: 1; margin: 0; font-size: .82rem; font-weight: 700; color: var(--d-title); line-height: 1.6; }
        .mrev-note-summary .mrev-note-empty { color: var(--d-muted); font-weight: 650; }
        .mrev-note-summary .mrev-note-edit-btn { display: inline-flex; align-items: center; gap: 5px; font-size: .74rem; font-weight: 800; color: var(--primary-dark, var(--d-green)); flex-shrink: 0; }
        .mrev-note-summary .mrev-note-edit-btn i { width: 14px; height: 14px; }
        .mrev-note-form { display: flex; gap: 8px; margin-top: 10px; }
        .mrev-note-form input { flex: 1; min-height: 38px; border-radius: 12px; border: 1px solid var(--home-v2-border); padding: 0 12px; background: var(--home-v2-card); color: var(--d-title); }
    </style>
@endpush

@section('content')
<section class="ddash">

    <div class="ddash-section-head">
        <h2>مراجعة الوجبات AI</h2>
        <span>وجبات مرضاك المسجّلة وتحليل الذكاء الاصطناعي لها</span>
    </div>

    @if (session('success'))
        <div class="doctor-status" style="margin-bottom:14px">{{ session('success') }}</div>
    @endif

    <div class="ddash-card ddash-accent--blue">
        <form method="GET" action="{{ route('doctor.meal_reviews') }}" class="mrev-filterbar">
            <div class="mrev-filterbar__field">
                <label for="mrev-patient">اختاري مريض</label>
                <select id="mrev-patient" name="patient" class="doctor-search" onchange="this.form.submit()">
                    <option value="">كل المرضى</option>
                    @foreach ($patients as $patient)
                        <option value="{{ $patient->user_id }}" @selected($selectedPatientId == $patient->user_id)>
                            {{ $patient->user->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <label class="mrev-filterbar__check">
                <input type="checkbox" name="unreviewed" value="1" @checked($onlyUnreviewed) onchange="this.form.submit()">
                بس اللي لسا ما راجعتها
            </label>
        </form>
    </div>

    <div class="ddash-card ddash-accent--green">
        <div class="ddash-card__head">
            <div><span>AI Meal Log</span><h2>الوجبات المسجّلة</h2></div>
            <i data-lucide="bot" class="ddash-card__icon"></i>
        </div>

        @if ($meals->isEmpty())
            <div class="ddash-empty"><i data-lucide="utensils"></i><p>ما في وجبات مسجّلة تطابق الفلتر الحالي.</p></div>
        @else
            <div>
                @foreach ($meals as $meal)
                    <div class="mrev-card">
                        <div class="mrev-card__top">
                            <div class="mrev-card__who">
                                <span class="ddash-avatar ddash-avatar--green">{{ mb_substr($meal->user?->name ?? '؟', 0, 1) }}</span>
                                <div>
                                    <strong>{{ $meal->user?->name ?? 'مريض' }}</strong>
                                    <small>
                                        {{ $meal->meal_name }}
                                        ({{ $mealTypeLabels[$meal->meal_type] ?? $meal->meal_type }})
                                        · {{ \Illuminate\Support\Carbon::parse($meal->meal_date)->locale('ar')->translatedFormat('j M Y') }}
                                    </small>
                                </div>
                            </div>
                            <span class="ddash-pill ddash-pill--{{ $meal->reviewed_at ? 'green' : 'amber' }}">
                                {{ $meal->reviewed_at ? 'تمت المراجعة' : 'بانتظار مراجعتك' }}
                            </span>
                        </div>

                        <div class="mrev-stats">
                            <span>{{ number_format($meal->calories ?? 0) }} kcal</span>
                            <span>بروتين {{ $meal->protein ?? '—' }}غ</span>
                            <span>كارب {{ $meal->carbs ?? '—' }}غ</span>
                            <span>دهون {{ $meal->fat ?? '—' }}غ</span>
                            @if ($meal->confidence)
                                <span>ثقة الـAI {{ $meal->confidence }}%</span>
                            @endif
                        </div>

                        @if ($meal->ai_notes)
                            <div class="mrev-quote"><i data-lucide="bot"></i><span><strong>ملاحظة الـAI:</strong> {{ $meal->ai_notes }}</span></div>
                        @endif
                        @if ($meal->patient_note)
                            <div class="mrev-quote"><i data-lucide="user-round"></i><span><strong>ملاحظة المريض:</strong> {{ $meal->patient_note }}</span></div>
                        @endif

                        <details class="mrev-note ddash-toggle">
                            <summary class="mrev-note-summary">
                                @if ($meal->doctor_note)
                                    <p>{{ $meal->doctor_note }}</p>
                                @else
                                    <p class="mrev-note-empty">ما ضفتيش ملاحظة على هاي الوجبة بعد.</p>
                                @endif
                                <span class="mrev-note-edit-btn">
                                    <i data-lucide="{{ $meal->doctor_note ? 'pencil' : 'plus' }}"></i>
                                    {{ $meal->doctor_note ? 'تعديل' : 'إضافة ملاحظة' }}
                                </span>
                            </summary>
                            <form method="POST" action="{{ route('doctor.meal_reviews.review', $meal) }}" class="mrev-note-form ddash-toggle-body">
                                @csrf
                                <input type="text" name="doctor_note" maxlength="1000"
                                       value="{{ $meal->doctor_note }}"
                                       placeholder="ملاحظتك على هاي الوجبة...">
                                <button type="submit" class="doctor-btn primary">حفظ</button>
                            </form>
                        </details>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
@endsection
