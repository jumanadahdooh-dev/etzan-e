@extends('layouts.doctor')

@php
    $pageTitle = 'المواعيد';
    $activePage = 'appointments';

    $statusPill = ['confirmed' => 'green', 'pending' => 'muted', 'completed' => 'blue', 'cancelled' => 'rose'];
    $statusLabel = ['confirmed' => 'مؤكد', 'pending' => 'بانتظار التأكيد', 'completed' => 'مكتمل', 'cancelled' => 'ملغي'];
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/doctor/dashboard.css') }}">

        
@endpush

@section('content')
<section class="ddash">

    <div class="ddash-section-head">
        <h2>المواعيد</h2>
        <span>جدول استشاراتك الحقيقي — اليوم، القادمة، والسابقة</span>
    </div>

    <div class="ddash-stats-grid" style="grid-template-columns:repeat(3,1fr)">
        <div class="ddash-stat-card ddash-accent--green">
            <div class="ddash-stat-card__top"><span class="ddash-stat-card__icon"><i data-lucide="calendar-check"></i></span></div>
            <div class="ddash-stat-card__num">{{ $todayAppointments->count() }}</div>
            <div class="ddash-stat-card__label">مواعيد اليوم</div>
        </div>
        <div class="ddash-stat-card ddash-accent--violet">
            <div class="ddash-stat-card__top"><span class="ddash-stat-card__icon"><i data-lucide="calendar-clock"></i></span></div>
            <div class="ddash-stat-card__num">{{ $upcomingAppointments->count() }}</div>
            <div class="ddash-stat-card__label">القادمة</div>
        </div>
        <div class="ddash-stat-card ddash-accent--amber">
            <div class="ddash-stat-card__top"><span class="ddash-stat-card__icon"><i data-lucide="hourglass"></i></span></div>
            <div class="ddash-stat-card__num">{{ $todayAppointments->where('status', 'pending')->count() + $upcomingAppointments->where('status', 'pending')->count() }}</div>
            <div class="ddash-stat-card__label">بانتظار تأكيدك</div>
        </div>
    </div>

    <div class="ddash-card ddash-accent--green">
        <div class="ddash-card__head">
            <div><span>اليوم</span><h2>مواعيد اليوم ({{ $todayAppointments->count() }})</h2></div>
            <i data-lucide="calendar-check" class="ddash-card__icon"></i>
        </div>

        @if ($todayAppointments->isEmpty())
            <div class="ddash-empty"><i data-lucide="calendar-x"></i><p>ما في مواعيد اليوم.</p></div>
        @else
            <div>
                @foreach ($todayAppointments as $apt)
                    <div class="ddash-sched-item">
                        <div class="ddash-sched-time">
                            <strong>{{ $apt->appointment_time ? \Illuminate\Support\Carbon::parse($apt->appointment_time)->format('H:i') : '—' }}</strong>
                            <small>{{ ($apt->consultation_type ?? '') === 'online' ? 'عن بُعد' : 'حضوري' }}</small>
                        </div>
                        <div class="ddash-sched-body">
                            <div class="ddash-sched-body__top">
                                <strong>{{ $apt->patient->name ?? 'مريض' }}</strong>
                                <span class="ddash-pill ddash-pill--{{ $statusPill[$apt->status] ?? 'muted' }}">{{ $statusLabel[$apt->status] ?? $apt->status }}</span>
                            </div>
                            @if ($apt->reason)
                                <p>{{ \Illuminate\Support\Str::limit($apt->reason, 80) }}</p>
                            @endif
                        </div>
                        @if ($apt->status === 'pending')
                            <div style="display:flex; flex-direction:column; gap:6px; align-items:flex-end;">
                                <div style="display:flex; gap:6px;">
                                    <form method="POST" action="{{ route('doctor.appointments.confirm', $apt) }}">
                                        @csrf
                                        <button type="submit" class="ddash-btn">تأكيد</button>
                                    </form>
                                    <form method="POST" action="{{ route('doctor.appointments.reject', $apt) }}"
                                          onsubmit="return confirm('رفض هالموعد؟');">
                                        @csrf
                                        <button type="submit" class="ddash-btn" style="background:var(--d-red,#e35a4c);">رفض</button>
                                    </form>
                                </div>
                                <details style="width:100%;">
                                    <summary style="cursor:pointer; font-size:12px; color:var(--muted); text-align:left;">أو اقترح وقت بديل</summary>
                                    <form method="POST" action="{{ route('doctor.appointments.suggest-time', $apt) }}"
                                          style="display:flex; gap:6px; margin-top:8px; flex-wrap:wrap; align-items:center;">
                                        @csrf
                                        <input type="date" name="suggested_date" min="{{ now()->toDateString() }}" required
                                               style="min-height:34px; border-radius:10px; border:1px solid var(--home-v2-border); padding:0 8px;">
                                        <input type="time" name="suggested_time" required
                                               style="min-height:34px; border-radius:10px; border:1px solid var(--home-v2-border); padding:0 8px;">
                                        <button type="submit" class="ddash-btn">اقتراح</button>
                                    </form>
                                </details>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="ddash-card ddash-accent--blue">
        <div class="ddash-card__head">
            <div><span>الأيام الجاية</span><h2>المواعيد القادمة ({{ $upcomingAppointments->count() }})</h2></div>
            <i data-lucide="calendar-clock" class="ddash-card__icon"></i>
        </div>

        @if ($upcomingAppointments->isEmpty())
            <div class="ddash-empty"><i data-lucide="calendar-x"></i><p>ما في مواعيد قادمة مجدولة لسا.</p></div>
        @else
            <div>
                @foreach ($upcomingAppointments as $apt)
                    @php $aptDate = \Illuminate\Support\Carbon::parse($apt->appointment_date); @endphp
                    <div class="ddash-week-item">
                        <div class="ddash-week-day">
                            <strong>{{ $aptDate->format('d') }}</strong>
                            <span>{{ $aptDate->locale('ar')->translatedFormat('M') }}</span>
                        </div>
                        <div class="ddash-week-sep"></div>
                        <span class="ddash-avatar ddash-avatar--blue">{{ mb_substr($apt->patient->name ?? '؟', 0, 1) }}</span>
                        <div class="ddash-item__body">
                            <strong>{{ $apt->patient->name ?? 'مريض' }}</strong>
                            <small>
                                {{ $aptDate->locale('ar')->translatedFormat('l') }}
                                @if ($apt->appointment_time) · {{ \Illuminate\Support\Carbon::parse($apt->appointment_time)->format('H:i') }} @endif
                            </small>
                        </div>
                        <span class="ddash-week-type {{ ($apt->consultation_type ?? '') === 'online' ? 'ddash-week-type--online' : 'ddash-week-type--person' }}">
                            <i data-lucide="{{ ($apt->consultation_type ?? '') === 'online' ? 'video' : 'building-2' }}"></i>
                            {{ ($apt->consultation_type ?? '') === 'online' ? 'عن بُعد' : 'حضوري' }}
                        </span>
                        <span class="ddash-pill ddash-pill--{{ $statusPill[$apt->status] ?? 'muted' }}">{{ $statusLabel[$apt->status] ?? $apt->status }}</span>
                        @if ($apt->status === 'pending')
                            <div style="display:flex; flex-direction:column; gap:6px; align-items:flex-end;">
                                <div style="display:flex; gap:6px;">
                                    <form method="POST" action="{{ route('doctor.appointments.confirm', $apt) }}">
                                        @csrf
                                        <button type="submit" class="ddash-btn">تأكيد</button>
                                    </form>
                                    <form method="POST" action="{{ route('doctor.appointments.reject', $apt) }}"
                                          onsubmit="return confirm('رفض هالموعد؟');">
                                        @csrf
                                        <button type="submit" class="ddash-btn" style="background:var(--d-red,#e35a4c);">رفض</button>
                                    </form>
                                </div>
                                <details style="width:100%;">
                                    <summary style="cursor:pointer; font-size:12px; color:var(--muted); text-align:left;">أو اقترح وقت بديل</summary>
                                    <form method="POST" action="{{ route('doctor.appointments.suggest-time', $apt) }}"
                                          style="display:flex; gap:6px; margin-top:8px; flex-wrap:wrap; align-items:center;">
                                        @csrf
                                        <input type="date" name="suggested_date" min="{{ now()->toDateString() }}" required
                                               style="min-height:34px; border-radius:10px; border:1px solid var(--home-v2-border); padding:0 8px;">
                                        <input type="time" name="suggested_time" required
                                               style="min-height:34px; border-radius:10px; border:1px solid var(--home-v2-border); padding:0 8px;">
                                        <button type="submit" class="ddash-btn">اقتراح</button>
                                    </form>
                                </details>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="ddash-card ddash-accent--violet">
        <div class="ddash-card__head">
            <div><span>السجل</span><h2>مواعيد سابقة</h2></div>
            <i data-lucide="history" class="ddash-card__icon"></i>
        </div>

        @if ($pastAppointments->isEmpty())
            <div class="ddash-empty"><i data-lucide="calendar-x"></i><p>ما في مواعيد سابقة لعرضها لسا.</p></div>
        @else
            <div class="ddash-table-wrap">
                <table class="ddash-table">
                    <thead>
                        <tr><th>التاريخ</th><th>المريض</th><th>النوع</th><th>الحالة</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($pastAppointments as $apt)
                            <tr>
                                <td>{{ \Illuminate\Support\Carbon::parse($apt->appointment_date)->locale('ar')->translatedFormat('j M Y') }}</td>
                                <td>
                                    <div class="ddash-tbl-patient">
                                        <span class="ddash-avatar ddash-avatar--violet">{{ mb_substr($apt->patient->name ?? '؟', 0, 1) }}</span>
                                        <strong>{{ $apt->patient->name ?? 'مريض' }}</strong>
                                    </div>
                                </td>
                                <td class="ddash-td-muted">{{ ($apt->consultation_type ?? '') === 'online' ? 'عن بُعد' : 'حضوري' }}</td>
                                <td><span class="ddash-pill ddash-pill--{{ $statusPill[$apt->status] ?? 'muted' }}">{{ $statusLabel[$apt->status] ?? $apt->status }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>
@endsection
