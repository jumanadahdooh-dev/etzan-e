@extends('layouts.doctor')

@php
    $pageTitle = 'الرئيسية';
    $activePage = 'dashboard';

    $doctorName = auth()->user()->name ?? 'طبيب';
    $specialtyName = optional($doctorProfile?->specialties?->first())->name ?? 'طبيب عام';

    $stats = $stats ?? [];
    $trend = $stats['appointments_trend'] ?? ['percent' => 0, 'direction' => 'flat'];
    $statusBreakdown = $statusBreakdown ?? ['confirmed' => 0, 'pending' => 0, 'completed' => 0, 'cancelled' => 0];
    $genderBreakdown = $genderBreakdown ?? ['male' => 0, 'female' => 0];
    $consultationBreakdown = $consultationBreakdown ?? ['online' => 0, 'in_person' => 0];
    $weeklyChart = $weeklyChart ?? [];
    $upcomingAppointments = $upcomingAppointments ?? collect();
    $todayPatients = $todayPatients ?? collect();
    $pendingRequests = $pendingRequests ?? collect();
    $recentReviews = $recentReviews ?? collect();

    $statusTotal = max(array_sum($statusBreakdown), 1);
    $genderTotal = max(($genderBreakdown['male'] ?? 0) + ($genderBreakdown['female'] ?? 0), 1);
    $consultTotal = max(($consultationBreakdown['online'] ?? 0) + ($consultationBreakdown['in_person'] ?? 0), 1);

    $statusLabels = ['confirmed' => 'مؤكد', 'pending' => 'بانتظار', 'completed' => 'مكتمل', 'cancelled' => 'ملغي'];
    $statusColors = ['confirmed' => '#1D9E75', 'pending' => '#f59e0b', 'completed' => '#3b82f6', 'cancelled' => '#f43f5e'];

    $todayTotal = $todayPatients->count();
    $todayDone = $todayPatients->where('status', 'completed')->count();
    $todayDonePct = $todayTotal > 0 ? round(($todayDone / $todayTotal) * 100) : 0;

    // المريض الحالي/القادم (أقرب موعد لسا ما خلص)
    $nextPatient = $todayPatients->firstWhere('status', 'in_progress')
        ?? $todayPatients->first(fn ($a) => !in_array($a->status, ['completed', 'cancelled']));

    $completionRate = $stats['completion_rate'] ?? 0;

    $attentionCounts = $attentionCounts ?? ['unread_messages' => 0, 'meals_to_review' => 0, 'active_alerts' => 0];

    // شبكة الإحصائيات (8 كروت، صفين) — بيانات حقيقية بالكامل، والي بيحتاج
    // إجراء من الطبيب فيها رابط مباشر لصفحته (طلبات/رسائل/وجبات/تنبيهات)
    $statCards = [
        ['icon' => 'calendar-check',  'accent' => 'green',  'value' => $stats['appointments_today'] ?? 0, 'label' => 'مواعيد اليوم', 'desc' => 'استشارات مجدولة', 'href' => route('doctor.appointments')],
        ['icon' => 'calendar-clock',  'accent' => 'violet', 'value' => $upcomingAppointments->count(), 'label' => 'القادمة', 'desc' => 'خلال الأيام الجاية', 'href' => route('doctor.appointments')],
        ['icon' => 'users-round',     'accent' => 'blue',   'value' => $stats['active_patients'] ?? 0, 'label' => 'مرضى نشطون', 'desc' => 'تحت متابعتك', 'href' => route('doctor.patients')],
        ['icon' => 'triangle-alert',  'accent' => 'rose',   'value' => $attentionCounts['active_alerts'], 'label' => 'تنبيهات فعّالة', 'desc' => 'مرضى بحاجة متابعة', 'href' => route('doctor.alerts')],
        ['icon' => 'clipboard-list',  'accent' => 'amber',  'value' => $stats['pending_requests'] ?? 0, 'label' => 'طلبات معلّقة', 'desc' => 'بانتظار قرارك', 'href' => route('doctor.patient-requests.index')],
        ['icon' => 'message-circle',  'accent' => 'sky',    'value' => $attentionCounts['unread_messages'], 'label' => 'رسائل غير مقروءة', 'desc' => 'من مرضاك', 'href' => route('doctor.messages')],
        ['icon' => 'bot',             'accent' => 'teal',   'value' => $attentionCounts['meals_to_review'], 'label' => 'وجبات تحتاج مراجعة', 'desc' => 'تحليل AI بانتظارك', 'href' => route('doctor.meal_reviews')],
        ['icon' => 'star',            'accent' => 'amber',  'value' => ($stats['avg_rating'] ?? 0) > 0 ? $stats['avg_rating'] : '—', 'label' => 'تقييمك العام', 'desc' => number_format($stats['reviews_count'] ?? 0).' مراجعة', 'href' => null],
    ];
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/doctor/dashboard.css') }}?v={{ file_exists(public_path('front/css/doctor/doctor-dashboard.css')) ? filemtime(public_path('front/css/doctor/doctor-dashboard.css')) : '1' }}">
    <style>
        a.ddash-stat-card { display: block; color: inherit; text-decoration: none; }
    </style>
@endpush

@section('content')
<section class="ddash">

    {{-- ══════════ HERO + جانبيات ══════════ --}}
    <div class="ddash-hero-row">
        <div class="ddash-hero">
            <div class="ddash-hero__top">
                <span class="ddash-hero__pill"><i class="ddash-dot"></i> <span data-time-greeting>مساء الخير</span></span>
            </div>

            <h1 class="ddash-hero__title">د. {{ $doctorName }}</h1>
            <p class="ddash-hero__subtitle">{{ $specialtyName }} · مركز اتزان الصحي</p>

            <div class="ddash-hero__meta">
                <span><i data-lucide="calendar"></i> {{ now()->locale('ar')->translatedFormat('l j F Y') }}</span>
                <span><i data-lucide="clock"></i> <em data-live-clock style="font-style:normal"></em></span>
                @if (($doctorProfile->is_available ?? true))
                    <span><i data-lucide="activity"></i> متاح للاستشارات</span>
                @else
                    <span><i data-lucide="pause-circle"></i> غير متاح حالياً</span>
                @endif
                @if (($stats['avg_rating'] ?? 0) > 0)
                    <span><i data-lucide="star"></i> {{ $stats['avg_rating'] }} تقييم</span>
                @endif
            </div>
        </div>

        <div class="ddash-side">
            <div class="ddash-side__card">
                <span class="ddash-side__label">مواعيد اليوم</span>
                <div class="ddash-side__num">{{ $todayTotal }}</div>
                <div class="ddash-side__sub">{{ $todayDone }} منجز · {{ $todayTotal - $todayDone }} متبقي</div>
                <div class="ddash-progress"><div class="ddash-progress__fill" style="width: {{ $todayDonePct }}%"></div></div>
            </div>

            <div class="ddash-side__card ddash-side__card--next">
                <span class="ddash-side__label">المريض القادم</span>
                @if ($nextPatient)
                    <div class="ddash-side__patient"><i class="ddash-dot"></i> {{ $nextPatient->patient->name ?? 'مريض' }}</div>
                    <div class="ddash-side__sub">
                        {{ $nextPatient->status === 'in_progress' ? 'جاري الآن' : ($nextPatient->appointment_time ? \Illuminate\Support\Carbon::parse($nextPatient->appointment_time)->format('H:i') : '—') }}
                    </div>
                @else
                    <div class="ddash-side__patient" style="color:var(--d-muted)">لا يوجد موعد قادم اليوم</div>
                    <div class="ddash-side__sub">استمتع بيومك 🌿</div>
                @endif
            </div>
        </div>
    </div>

    {{-- ══════════ STATS GRID (8 كروت) ══════════ --}}
    <div class="ddash-section-head">
        <h2>نظرة عامة</h2>
        <span>تحديث مباشر من قاعدة البيانات</span>
    </div>
    <div class="ddash-stats-grid">
        @foreach ($statCards as $i => $c)
            @php $cardTag = $c['href'] ? 'a' : 'div'; @endphp
            <{{ $cardTag }}
                @if ($c['href']) href="{{ $c['href'] }}" @endif
                class="ddash-stat-card ddash-accent--{{ $c['accent'] }}"
                style="animation-delay: {{ $i * 55 }}ms"
            >
                <div class="ddash-stat-card__top">
                    <span class="ddash-stat-card__icon"><i data-lucide="{{ $c['icon'] }}"></i></span>
                </div>
                <div class="ddash-stat-card__num">{{ is_numeric($c['value']) ? number_format($c['value']) : $c['value'] }}</div>
                <div class="ddash-stat-card__label">{{ $c['label'] }}</div>
                <div class="ddash-stat-card__desc">{{ $c['desc'] }}</div>
            </{{ $cardTag }}>
        @endforeach
    </div>

    {{-- ══════════ ANALYTICS (تبويبات) ══════════ --}}
    <div class="ddash-card ddash-accent--green">
        <div class="ddash-card__head">
            <div>
                <span>تحليلات</span>
                <h2>نشاطك على اتزان</h2>
            </div>
            <div class="ddash-tabs" role="tablist">
                <button type="button" class="ddash-tab is-active" data-ddash-tab="weekly">أسبوعي</button>
                <button type="button" class="ddash-tab" data-ddash-tab="status">حالة المواعيد</button>
                <button type="button" class="ddash-tab" data-ddash-tab="gender">المرضى</button>
                <button type="button" class="ddash-tab" data-ddash-tab="type">نوع الاستشارة</button>
            </div>
        </div>

        {{-- تبويب 1: أسبوعي (Chart.js حقيقي) --}}
        <div class="ddash-tab-panel is-active" data-ddash-panel="weekly">
            <div class="ddash-card__head" style="margin-bottom:6px">
                <div>
                    <span>آخر 7 أيام</span>
                    <h2 style="font-size:.94rem">مواعيدك الأسبوعية</h2>
                </div>
                @if (($trend['direction'] ?? 'flat') === 'up')
                    <span class="ddash-trend ddash-trend--up"><i data-lucide="trending-up"></i> +{{ $trend['percent'] }}%</span>
                @elseif (($trend['direction'] ?? 'flat') === 'down')
                    <span class="ddash-trend ddash-trend--down"><i data-lucide="trending-down"></i> -{{ $trend['percent'] }}%</span>
                @else
                    <span class="ddash-trend"><i data-lucide="minus"></i> ثابت</span>
                @endif
            </div>
            @if (collect($weeklyChart)->sum('count') > 0)
                <div class="ddash-chart-wrap"><canvas id="ddashWeeklyChart"></canvas></div>
            @else
                <div class="ddash-empty">
                    <i data-lucide="calendar-off"></i>
                    <p>ما في مواعيد بآخر 7 أيام — أول ما تجيك مواعيد رح يظهر الرسم هون.</p>
                </div>
            @endif
        </div>

        {{-- تبويب 2: حالة المواعيد --}}
        <div class="ddash-tab-panel" data-ddash-panel="status">
            <div class="ddash-card__head" style="margin-bottom:6px">
                <div>
                    <span>هالشهر</span>
                    <h2 style="font-size:.94rem">حالة المواعيد</h2>
                </div>
                <strong class="ddash-trend">{{ number_format($stats['month_total'] ?? 0) }} موعد</strong>
            </div>
            @if (($stats['month_total'] ?? 0) > 0)
                <div class="ddash-status" style="margin-top:14px">
                    @foreach ($statusBreakdown as $key => $count)
                        <div class="ddash-status__row">
                            <span class="ddash-status__label"><i class="ddash-dot" style="background: {{ $statusColors[$key] }}"></i> {{ $statusLabels[$key] }}</span>
                            <div class="ddash-status__bar"><div class="ddash-status__fill" style="width: {{ round(($count / $statusTotal) * 100) }}%; background: {{ $statusColors[$key] }}"></div></div>
                            <strong>{{ $count }}</strong>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="ddash-empty"><i data-lucide="pie-chart"></i><p>ما في مواعيد هالشهر لعرض التوزيع.</p></div>
            @endif
        </div>

        {{-- تبويب 3: توزيع المرضى --}}
        <div class="ddash-tab-panel" data-ddash-panel="gender">
            @if (($genderBreakdown['male'] + $genderBreakdown['female']) > 0)
                <div class="ddash-split">
                    <div class="ddash-split__item">
                        <div class="ddash-ring" style="--pct: {{ round(($genderBreakdown['male'] / $genderTotal) * 100) }}; --clr: #3b82f6"><strong>{{ round(($genderBreakdown['male'] / $genderTotal) * 100) }}%</strong></div>
                        <span>ذكور ({{ $genderBreakdown['male'] }})</span>
                    </div>
                    <div class="ddash-split__item">
                        <div class="ddash-ring" style="--pct: {{ round(($genderBreakdown['female'] / $genderTotal) * 100) }}; --clr: #ec4899"><strong>{{ round(($genderBreakdown['female'] / $genderTotal) * 100) }}%</strong></div>
                        <span>إناث ({{ $genderBreakdown['female'] }})</span>
                    </div>
                </div>
            @else
                <div class="ddash-empty"><i data-lucide="users"></i><p>لسا ما عندك مرضى مسجّل جنسهم.</p></div>
            @endif
        </div>

        {{-- تبويب 4: نوع الاستشارة --}}
        <div class="ddash-tab-panel" data-ddash-panel="type">
            @if (($consultationBreakdown['online'] + $consultationBreakdown['in_person']) > 0)
                <div class="ddash-split">
                    <div class="ddash-split__item">
                        <div class="ddash-ring" style="--pct: {{ round(($consultationBreakdown['online'] / $consultTotal) * 100) }}; --clr: #1D9E75"><strong>{{ round(($consultationBreakdown['online'] / $consultTotal) * 100) }}%</strong></div>
                        <span>عن بُعد ({{ $consultationBreakdown['online'] }})</span>
                    </div>
                    <div class="ddash-split__item">
                        <div class="ddash-ring" style="--pct: {{ round(($consultationBreakdown['in_person'] / $consultTotal) * 100) }}; --clr: #8b5cf6"><strong>{{ round(($consultationBreakdown['in_person'] / $consultTotal) * 100) }}%</strong></div>
                        <span>حضوري ({{ $consultationBreakdown['in_person'] }})</span>
                    </div>
                </div>
            @else
                <div class="ddash-empty"><i data-lucide="video-off"></i><p>ما في مواعيد بعد لعرض أنواع الاستشارات.</p></div>
            @endif
        </div>
    </div>

    {{-- ══════════ MAIN GRID: يسار (جدول اليوم) | يمين (إجراءات + نظرة سريرية + أداء) ══════════ --}}
    <div class="ddash-grid ddash-grid--main">

        {{-- ─────── يسار ─────── --}}
        <div class="ddash-col-flex">

            <div class="ddash-card ddash-accent--blue">
                <div class="ddash-card__head">
                    <div>
                        <span>جدولك</span>
                        <h2>مواعيد اليوم</h2>
                    </div>
                    <a href="{{ route('doctor.appointments') }}" class="ddash-link">عرض الكل <i data-lucide="arrow-left" style="width:14px;height:14px"></i></a>
                </div>

                @if ($todayPatients->isNotEmpty())
                    @php
                        $schedCounts = [
                            'all' => $todayPatients->count(),
                            'upcoming' => $todayPatients->whereIn('status', ['pending', 'confirmed', 'approved'])->count(),
                            'in_progress' => $todayPatients->where('status', 'in_progress')->count(),
                            'completed' => $todayPatients->where('status', 'completed')->count(),
                        ];
                    @endphp
                    <div class="ddash-schedule-filters">
                        <button type="button" class="ddash-filter is-active" data-sched-filter="all">الكل <span class="ddash-count">{{ $schedCounts['all'] }}</span></button>
                        <button type="button" class="ddash-filter" data-sched-filter="upcoming">قادمة <span class="ddash-count">{{ $schedCounts['upcoming'] }}</span></button>
                        <button type="button" class="ddash-filter" data-sched-filter="in_progress">جارية <span class="ddash-count">{{ $schedCounts['in_progress'] }}</span></button>
                        <button type="button" class="ddash-filter" data-sched-filter="completed">مكتملة <span class="ddash-count">{{ $schedCounts['completed'] }}</span></button>
                    </div>

                    <div data-sched-list>
                        @foreach ($todayPatients as $apt)
                            @php
                                $bucket = in_array($apt->status, ['pending','confirmed','approved']) ? 'upcoming' : $apt->status;
                            @endphp
                            <div class="ddash-sched-item" data-status="{{ $bucket }}">
                                <div class="ddash-sched-time">
                                    <strong>{{ $apt->appointment_time ? \Illuminate\Support\Carbon::parse($apt->appointment_time)->format('H:i') : '—' }}</strong>
                                    <small>{{ ($apt->consultation_type ?? '') === 'online' ? 'عن بُعد' : 'حضوري' }}</small>
                                </div>
                                <div class="ddash-sched-body">
                                    <div class="ddash-sched-body__top">
                                        <strong>{{ $apt->patient->name ?? 'مريض' }}</strong>
                                        @if ($bucket === 'in_progress')
                                            <span class="ddash-pill ddash-pill--blue">جارية الآن</span>
                                        @elseif ($bucket === 'completed')
                                            <span class="ddash-pill ddash-pill--green">مكتمل</span>
                                        @elseif ($bucket === 'cancelled')
                                            <span class="ddash-pill ddash-pill--rose">ملغي</span>
                                        @else
                                            <span class="ddash-pill ddash-pill--muted">قادم</span>
                                        @endif
                                    </div>
                                    @if ($apt->reason)
                                        <p>{{ \Illuminate\Support\Str::limit($apt->reason, 60) }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="ddash-empty"><i data-lucide="calendar-x"></i><p>ما في مواعيد اليوم — استمتع بيومك 🌿</p></div>
                @endif
            </div>

            {{-- طلبات الاستشارة --}}
            <div class="ddash-card ddash-accent--amber">
                <div class="ddash-card__head">
                    <div><span>بانتظار قرارك</span><h2>طلبات الاستشارة</h2></div>
                    <a href="{{ route('doctor.patient-requests.index') }}" class="ddash-link">عرض الكل</a>
                </div>
                @forelse ($pendingRequests as $req)
                    <div class="ddash-item">
                        <span class="ddash-avatar ddash-avatar--amber">{{ mb_substr($req->patient->name ?? '؟', 0, 1) }}</span>
                        <div class="ddash-item__body">
                            <strong>{{ $req->patient->name ?? 'مريض' }}</strong>
                            <small>طلب متابعة · {{ $req->created_at?->locale('ar')->diffForHumans() }}</small>
                        </div>
                        <a href="{{ route('doctor.patient-requests.index') }}" class="ddash-btn">مراجعة</a>
                    </div>
                @empty
                    <div class="ddash-empty"><i data-lucide="inbox"></i><p>ما في طلبات معلقة — كل شي مراجَع 🎉</p></div>
                @endforelse
            </div>

            {{-- مواعيدك القادمة هالأسبوع (بيانات حقيقية موجودة أصلاً) --}}
            @php
                $weekAhead = $upcomingAppointments->filter(function ($a) {
                    return \Illuminate\Support\Carbon::parse($a->appointment_date)->isAfter(today());
                })->take(4);
            @endphp
            <div class="ddash-card ddash-accent--blue">
                <div class="ddash-card__head">
                    <div><span>الأيام الجاية</span><h2>مواعيدك القادمة هالأسبوع</h2></div>
                    <a href="{{ route('doctor.appointments') }}" class="ddash-link">الكل</a>
                </div>
                @forelse ($weekAhead as $apt)
                    @php
                        $aptDate = \Illuminate\Support\Carbon::parse($apt->appointment_date);
                        $isOnline = ($apt->consultation_type ?? '') === 'online';
                    @endphp
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
                        <span class="ddash-week-type ddash-week-type--{{ $isOnline ? 'online' : 'person' }}">
                            <i data-lucide="{{ $isOnline ? 'video' : 'building-2' }}"></i>
                            {{ $isOnline ? 'عن بُعد' : 'حضوري' }}
                        </span>
                    </div>
                @empty
                    <div class="ddash-empty"><i data-lucide="calendar-check"></i><p>ما في مواعيد إضافية مجدولة الأسبوع الجاي لسا.</p></div>
                @endforelse
            </div>

            @if (isset($weeklyMealsCount))
                {{-- نشاط التغذية هالأسبوع (يحتاج إضافة صغيرة بالكونترولر — راجعي التعليمات) --}}
                <div class="ddash-card ddash-accent--teal">
                    <div class="ddash-card__head">
                        <div><span>آخر 7 أيام</span><h2>نشاط التغذية</h2></div>
                        <i data-lucide="utensils" class="ddash-card__icon"></i>
                    </div>
                    <div class="ddash-mini-grid" style="grid-template-columns:1fr">
                        <div class="ddash-mini">
                            <i data-lucide="utensils"></i>
                            <strong>{{ $weeklyMealsCount }}</strong>
                            <span>وجبة سجّلها مرضاك هالأسبوع</span>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- ─────── يمين ─────── --}}
        <div class="ddash-col-flex">

            {{-- إجراءات سريعة --}}
            <div class="ddash-card ddash-accent--violet">
                <div class="ddash-card__head"><div><span>اختصارات</span><h2>إجراءات سريعة</h2></div></div>
                <div class="ddash-quick">
                    <a href="{{ route('doctor.patient-requests.index') }}" class="ddash-quick__item ddash-accent--amber"><i data-lucide="clipboard-list"></i><strong>الطلبات</strong><span>مراجعة الطلبات</span></a>
                    <a href="{{ route('doctor.appointments') }}" class="ddash-quick__item ddash-accent--blue"><i data-lucide="calendar"></i><strong>المواعيد</strong><span>جدولة موعد</span></a>
                    <a href="{{ route('doctor.patients') }}" class="ddash-quick__item ddash-accent--green"><i data-lucide="users-round"></i><strong>مرضاي</strong><span>القائمة الكاملة</span></a>
                    <a href="{{ route('doctor.meal_reviews') }}" class="ddash-quick__item ddash-accent--teal"><i data-lucide="bot"></i><strong>وجبات AI</strong><span>مراجعة واعتماد</span></a>
                    <a href="{{ route('doctor.messages') }}" class="ddash-quick__item ddash-accent--sky"><i data-lucide="message-circle"></i><strong>الرسائل</strong><span>تواصل المرضى</span></a>
                    <a href="{{ route('doctor.articles') }}" class="ddash-quick__item ddash-accent--rose"><i data-lucide="newspaper"></i><strong>مقالاتي</strong><span>كتابة ونشر</span></a>
                </div>
            </div>

            {{-- نظرة سريرية سريعة (بيانات حقيقية بس) --}}
            <div class="ddash-card ddash-accent--rose">
                <div class="ddash-card__head"><div><span>نظرة سريعة</span><h2>ملخص مرضاك</h2></div><i data-lucide="stethoscope" class="ddash-card__icon"></i></div>
                <div class="ddash-mini-grid">
                    <div class="ddash-mini"><i data-lucide="users-round"></i><strong>{{ $stats['active_patients'] ?? 0 }}</strong><span>مرضى نشطون</span></div>
                    <div class="ddash-mini"><i data-lucide="calendar-check"></i><strong>{{ $stats['month_total'] ?? 0 }}</strong><span>مواعيد الشهر</span></div>
                    <div class="ddash-mini"><i data-lucide="clipboard-list"></i><strong>{{ $stats['pending_requests'] ?? 0 }}</strong><span>طلبات معلّقة</span></div>
                </div>
                <div class="ddash-attn-label">يحتاجون متابعتك</div>
                @forelse ($pendingRequests->take(3) as $req)
                    <div class="ddash-attn-item">
                        <span class="ddash-avatar ddash-avatar--rose" style="width:34px;height:34px;font-size:.78rem">{{ mb_substr($req->patient->name ?? '؟', 0, 1) }}</span>
                        <div class="ddash-attn-item__body">
                            <strong>{{ $req->patient->name ?? 'مريض' }}</strong>
                            <small>طلب متابعة بانتظار ردّك</small>
                        </div>
                        <i class="ddash-dot" style="background:var(--d-amber)"></i>
                    </div>
                @empty
                    <div class="ddash-empty" style="padding:16px"><i data-lucide="check-circle" style="width:34px;height:34px;padding:8px"></i><p>ما حدا محتاج متابعة عاجلة هلق 👍</p></div>
                @endforelse
            </div>

            {{-- الأداء (بيانات حقيقية بس: التقييم ونسبة الإنجاز) --}}
            <div class="ddash-card ddash-accent--teal">
                <div class="ddash-card__head">
                    <div><span>هالشهر</span><h2>أداؤك</h2></div>
                    @if (($stats['avg_rating'] ?? 0) > 0)
                        <span class="ddash-trend ddash-trend--up"><i data-lucide="star"></i> {{ $stats['avg_rating'] }}</span>
                    @endif
                </div>

                <div class="ddash-perf-row">
                    <div class="ddash-perf-head"><span>نسبة إنجاز المواعيد</span><strong>{{ $completionRate }}%</strong></div>
                    <div class="ddash-perf-bar"><div class="ddash-perf-fill" style="width:{{ $completionRate }}%;background:var(--d-green)"></div></div>
                </div>

                @php
                    $ratingPct = $stats['avg_rating'] ? round(($stats['avg_rating'] / 5) * 100) : 0;
                @endphp
                @if ($ratingPct > 0)
                    <div class="ddash-perf-row">
                        <div class="ddash-perf-head"><span>رضا المرضى (من التقييمات)</span><strong>{{ $ratingPct }}%</strong></div>
                        <div class="ddash-perf-bar"><div class="ddash-perf-fill" style="width:{{ $ratingPct }}%;background:var(--d-blue)"></div></div>
                    </div>
                @endif

                @if (($stats['reviews_count'] ?? 0) === 0 && $completionRate === 0)
                    <div class="ddash-empty" style="padding:10px 0 0"><i data-lucide="bar-chart-3" style="width:36px;height:36px;padding:9px"></i><p>لسا ما في بيانات كافية لعرض مؤشرات الأداء.</p></div>
                @endif
            </div>
        </div>
    </div>

    {{-- ══════════ آخر التقييمات + السجل الزمني الحقيقي ══════════ --}}
    <div class="ddash-grid ddash-grid--half">

        <div class="ddash-card ddash-accent--rose">
            <div class="ddash-card__head"><div><span>شو بيحكوا عنك</span><h2>آخر التقييمات</h2></div><i data-lucide="star" class="ddash-card__icon"></i></div>
            @forelse ($recentReviews as $review)
                <div class="ddash-item">
                    <span class="ddash-avatar ddash-avatar--rose">{{ mb_substr($review->patient->name ?? '؟', 0, 1) }}</span>
                    <div class="ddash-item__body">
                        <strong>{{ $review->patient->name ?? 'مريض' }}</strong>
                        <small>
                            @for ($i = 1; $i <= 5; $i++){{ $i <= ($review->rating ?? 0) ? '★' : '☆' }}@endfor
                            @if ($review->comment) · {{ \Illuminate\Support\Str::limit($review->comment, 40) }}@endif
                        </small>
                    </div>
                </div>
            @empty
                <div class="ddash-empty"><i data-lucide="message-square"></i><p>لسا ما وصلك تقييمات — بعد أول متابعة رح تبلّش توصل.</p></div>
            @endforelse
        </div>

        @php
            // سجل زمني حقيقي: نجمّع الأحداث الفعلية (تقييمات + طلبات جديدة + مواعيد مكتملة اليوم) ونرتبهم بالوقت
            $timelineEvents = collect();

            foreach ($recentReviews as $r) {
                $timelineEvents->push([
                    'icon' => 'star', 'accent' => 'amber',
                    'title' => 'تقييم جديد',
                    'desc' => ($r->patient->name ?? 'مريض').' — '.$r->rating.' نجوم',
                    'time' => $r->created_at,
                ]);
            }
            foreach ($pendingRequests as $r) {
                $timelineEvents->push([
                    'icon' => 'user-plus', 'accent' => 'blue',
                    'title' => 'طلب متابعة جديد',
                    'desc' => ($r->patient->name ?? 'مريض').' طلب الانضمام لقائمة مرضاك',
                    'time' => $r->created_at,
                ]);
            }
            foreach ($todayPatients->where('status', 'completed') as $a) {
                $timelineEvents->push([
                    'icon' => 'check-circle', 'accent' => 'green',
                    'title' => 'استشارة مكتملة',
                    'desc' => ($a->patient->name ?? 'مريض').' — '.(($a->consultation_type ?? '') === 'online' ? 'عن بُعد' : 'حضوري'),
                    'time' => $a->updated_at,
                ]);
            }

            $timelineEvents = $timelineEvents->sortByDesc('time')->take(6)->values();
        @endphp

        <div class="ddash-card ddash-accent--blue">
            <div class="ddash-card__head"><div><span>سجل اليوم</span><h2>آخر الأحداث</h2></div></div>
            @if ($timelineEvents->isNotEmpty())
                <div class="ddash-timeline">
                    @foreach ($timelineEvents as $ev)
                        <div class="ddash-tl-item">
                            <span class="ddash-tl-icon ddash-accent--{{ $ev['accent'] }}"><i data-lucide="{{ $ev['icon'] }}"></i></span>
                            <div class="ddash-tl-body">
                                <div><strong>{{ $ev['title'] }}</strong><p>{{ $ev['desc'] }}</p></div>
                                <span class="ddash-tl-time">{{ $ev['time']?->locale('ar')->diffForHumans() }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="ddash-empty"><i data-lucide="history"></i><p>ما في أحداث حديثة لعرضها اليوم.</p></div>
            @endif
        </div>
    </div>

    {{-- ══════════ جدول المرضى (اليوم + القادمين) ══════════ --}}
    @php
        $recentPatientsTable = collect()
            ->merge($todayPatients)
            ->merge($upcomingAppointments)
            ->unique(fn ($a) => $a->patient->id ?? $a->id)
            ->take(6);
    @endphp

    <div class="ddash-card ddash-accent--green">
        <div class="ddash-card__head">
            <div><span>مرضاك</span><h2>مرضى اليوم والقادمون</h2></div>
            <a href="{{ route('doctor.patients') }}" class="ddash-link">كل المرضى</a>
        </div>

        @if ($recentPatientsTable->isNotEmpty())
            <div class="ddash-table-wrap">
                <table class="ddash-table">
                    <thead>
                        <tr>
                            <th>المريض</th>
                            <th>الموعد</th>
                            <th>نوع الاستشارة</th>
                            <th>الحالة</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentPatientsTable as $apt)
                            <tr>
                                <td>
                                    <div class="ddash-tbl-patient">
                                        <span class="ddash-avatar">{{ mb_substr($apt->patient->name ?? '؟', 0, 1) }}</span>
                                        <div><strong>{{ $apt->patient->name ?? 'مريض' }}</strong><small>{{ $apt->patient->email ?? '' }}</small></div>
                                    </div>
                                </td>
                                <td class="ddash-td-muted">{{ \Illuminate\Support\Carbon::parse($apt->appointment_date)->locale('ar')->translatedFormat('j M') }}</td>
                                <td class="ddash-td-muted">{{ ($apt->consultation_type ?? '') === 'online' ? 'عن بُعد' : 'حضوري' }}</td>
                                <td>
                                    <span class="ddash-pill ddash-pill--{{ in_array($apt->status, ['confirmed','approved']) ? 'green' : ($apt->status === 'pending' ? 'amber' : ($apt->status === 'completed' ? 'blue' : 'muted')) }}">
                                        {{ $statusLabels[$apt->status] ?? $apt->status }}
                                    </span>
                                </td>
                                <td>
                                    @if ($apt->patient_profile_id)
                                        <a href="{{ route('doctor.patient-profile.show', $apt->patient_profile_id) }}" class="ddash-link">التفاصيل</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="ddash-empty"><i data-lucide="users"></i><p>ما في مرضى لعرضهم حالياً.</p></div>
        @endif
    </div>

</section>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    /* ─── الرسم الأسبوعي (Chart.js) ─── */
    var canvas = document.getElementById('ddashWeeklyChart');
    if (canvas && typeof Chart !== 'undefined') {
        var labels = @json(collect($weeklyChart)->pluck('label'));
        var counts = @json(collect($weeklyChart)->pluck('count'));
        var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        var ctx = canvas.getContext('2d');
        var grad = ctx.createLinearGradient(0, 0, 0, 250);
        grad.addColorStop(0, 'rgba(29,158,117,.85)');
        grad.addColorStop(1, 'rgba(29,158,117,.35)');

        new Chart(canvas, {
            type: 'bar',
            data: { labels: labels, datasets: [{ data: counts, backgroundColor: grad, hoverBackgroundColor: '#1D9E75', borderRadius: 8, maxBarThickness: 40 }] },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { backgroundColor: isDark ? '#1e293b' : '#0f172a', padding: 10, cornerRadius: 10, displayColors: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0, color: isDark ? '#6b8479' : '#94a3b8' }, grid: { color: isDark ? 'rgba(255,255,255,.05)' : 'rgba(0,0,0,.04)' } },
                    x: { ticks: { color: isDark ? '#6b8479' : '#94a3b8' }, grid: { display: false } }
                }
            }
        });
    }

    /* ─── تبويبات التحليلات ─── */
    document.querySelectorAll('[data-ddash-tab]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var target = btn.getAttribute('data-ddash-tab');
            btn.closest('.ddash-card').querySelectorAll('[data-ddash-tab]').forEach(function (b) { b.classList.remove('is-active'); });
            btn.classList.add('is-active');
            btn.closest('.ddash-card').querySelectorAll('[data-ddash-panel]').forEach(function (p) {
                p.classList.toggle('is-active', p.getAttribute('data-ddash-panel') === target);
            });
        });
    });

    /* ─── فلاتر جدول اليوم ─── */
    document.querySelectorAll('[data-sched-filter]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var filter = btn.getAttribute('data-sched-filter');
            btn.parentElement.querySelectorAll('[data-sched-filter]').forEach(function (b) { b.classList.remove('is-active'); });
            btn.classList.add('is-active');

            var list = btn.closest('.ddash-card').querySelector('[data-sched-list]');
            if (!list) return;
            list.querySelectorAll('.ddash-sched-item').forEach(function (item) {
                var status = item.getAttribute('data-status');
                item.style.display = (filter === 'all' || status === filter) ? 'flex' : 'none';
            });
        });
    });
});
</script>
@endpush
@endsection
