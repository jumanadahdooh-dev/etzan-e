@extends('layouts.doctor')

@php
    $pageTitle = 'ملف المريض';
    $statusColors = ['pending' => 'amber', 'approved' => 'green', 'rejected' => 'rose'];
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/doctor/dashboard.css') }}?v={{ file_exists(public_path('front/css/doctor/doctor-dashboard.css')) ? filemtime(public_path('front/css/doctor/doctor-dashboard.css')) : '1' }}">
    <link rel="stylesheet" href="{{ asset('front/css/doctor/requests.css') }}?v={{ file_exists(public_path('front/css/doctor/doctor-requests.css')) ? filemtime(public_path('front/css/doctor/doctor-requests.css')) : '1' }}">
@endpush

@section('content')
<section class="ddash">

    {{-- ══════════ زر العودة ══════════ --}}
    <div>
        <a href="{{ route('doctor.patient-requests.index') }}" class="dreq-back-btn">
            <i data-lucide="arrow-right" style="width:15px;height:15px"></i> رجوع لطلبات الاستشارة
        </a>
    </div>

    {{-- ══════════ رأس + حالة الطلب ══════════ --}}
    <div class="dreq-featured">
        <div class="dreq-featured__info">
            @if (!empty($avatarUrl))
                <img src="{{ $avatarUrl }}" class="ddash-avatar-photo" style="width:46px;height:46px" alt="{{ $profile->patient_name }}">
            @else
                <span class="ddash-avatar" style="width:46px;height:46px;font-size:1rem">{{ mb_substr($profile->patient_name ?? '؟', 0, 1) }}</span>
            @endif
            <div>
                <span class="dreq-featured__eyebrow">{{ $profile->patient_email ?? '' }}</span>
                <strong>{{ $profile->patient_name ?? 'مريض' }}</strong>
                <div class="dreq-featured__tags">
                    @if ($age)<span class="ddash-pill ddash-pill--muted">{{ $age }} سنة</span>@endif
                    <span class="ddash-pill ddash-pill--muted">{{ $genderLabel }}</span>
                </div>
            </div>
        </div>

        <div class="dreq-featured__actions">
            @if ($requestStatus === 'pending')
                <button type="button" class="dreq-btn dreq-btn--danger" data-reject-open="{{ route('doctor.patient-requests.reject', $profile->id) }}">رفض</button>
                <form method="POST" action="{{ route('doctor.patient-requests.approve', $profile->id) }}">
                    @csrf
                    <button type="submit" class="dreq-btn dreq-btn--success">قبول</button>
                </form>
            @elseif ($requestStatus === 'approved')
                <form method="POST" action="{{ route('doctor.patient-requests.complete', $profile->id) }}">
                    @csrf
                    <button type="submit" class="dreq-btn dreq-btn--muted">إنهاء المتابعة</button>
                </form>
            @endif
            <span class="ddash-pill ddash-pill--{{ $statusColors[$requestStatus] ?? 'muted' }}" style="font-size:.78rem;padding:6px 16px">
            {{ $requestStatusLabel }}
        </span>
        </div>
    </div>

    {{-- ══════════ ملخص سريع ══════════ --}}
    <div class="ddash-stats-grid">
        <div class="ddash-stat-card ddash-accent--green">
            <div class="ddash-stat-card__top"><span class="ddash-stat-card__icon"><i data-lucide="target"></i></span></div>
            <div class="ddash-stat-card__num" style="font-size:1.1rem">{{ $profile->health_goal ?: '—' }}</div>
            <div class="ddash-stat-card__label">الهدف الصحي</div>
        </div>
        <div class="ddash-stat-card ddash-accent--blue">
            <div class="ddash-stat-card__top"><span class="ddash-stat-card__icon"><i data-lucide="activity"></i></span></div>
            <div class="ddash-stat-card__num" style="font-size:1.1rem">{{ $profile->activity_level ?: '—' }}</div>
            <div class="ddash-stat-card__label">مستوى النشاط</div>
        </div>
        <div class="ddash-stat-card ddash-accent--violet">
            <div class="ddash-stat-card__top"><span class="ddash-stat-card__icon"><i data-lucide="moon"></i></span></div>
            <div class="ddash-stat-card__num">{{ $profile->sleep_hours ?: '—' }}</div>
            <div class="ddash-stat-card__label">ساعات النوم</div>
        </div>
        <div class="ddash-stat-card ddash-accent--sky">
            <div class="ddash-stat-card__top"><span class="ddash-stat-card__icon"><i data-lucide="glass-water"></i></span></div>
            <div class="ddash-stat-card__num">{{ $profile->water_cups ?: '—' }}</div>
            <div class="ddash-stat-card__label">أكواب الماء يومياً</div>
        </div>
    </div>

    <div class="ddash-card ddash-accent--rose">
        <div class="ddash-card__head"><div><span>الملف الطبي</span><h2>الحالات والأدوية</h2></div><i data-lucide="clipboard-list" class="ddash-card__icon"></i></div>

        <div class="dpat-conditions" style="margin-bottom:18px">
            @forelse ($conditions as $cond)
                <span class="ddash-pill ddash-pill--rose dpat-tag">{{ is_array($cond) ? ($cond['name'] ?? '') : $cond }}</span>
            @empty
                <span class="ddash-pill ddash-pill--green dpat-tag">لا يوجد حالات مزمنة مسجّلة</span>
            @endforelse
        </div>

        <div class="dpat-info-list">
            <div class="dpat-info-row">
                <span class="dpat-info-row__label"><i data-lucide="pill"></i> الأدوية</span>
                <span class="dpat-info-row__value">{{ $profile->medications ?: 'لا يوجد' }}</span>
            </div>
            <div class="dpat-info-row">
                <span class="dpat-info-row__label"><i data-lucide="triangle-alert"></i> الحساسية</span>
                <span class="dpat-info-row__value">{{ $profile->allergies ?: 'لا يوجد' }}</span>
            </div>
        </div>
    </div>

    {{-- ══════════ ملخص التغذية + هدف السعرات (بس بعد قبول الطلب) ══════════ --}}
    @if ($requestStatus === 'approved')
        <div class="ddash-grid ddash-grid--half">

            <div class="ddash-card ddash-accent--teal">
                <div class="ddash-card__head"><div><span>آخر 7 أيام</span><h2>ملخص التغذية</h2></div><i data-lucide="utensils" class="ddash-card__icon"></i></div>

                @if ($mealsCount > 0)
                    <div class="ddash-mini-grid">
                        <div class="ddash-mini"><i data-lucide="utensils"></i><strong>{{ $mealsCount }}</strong><span>وجبة سُجّلت</span></div>
                        <div class="ddash-mini"><i data-lucide="flame"></i><strong>{{ $avgCalories }}</strong><span>متوسط سعرات/يوم</span></div>
                        <div class="ddash-mini"><i data-lucide="drumstick"></i><strong>{{ $avgProtein }}</strong><span>متوسط بروتين (غ)</span></div>
                    </div>

                    <div class="ddash-attn-label">آخر الوجبات</div>
                    @foreach ($recentMeals->take(3) as $meal)
                        <div class="ddash-item">
                            <span class="ddash-avatar ddash-avatar--teal"><i data-lucide="utensils" style="width:15px;height:15px"></i></span>
                            <div class="ddash-item__body">
                                <strong>{{ $meal->meal_name ?? 'وجبة' }}</strong>
                                <small>{{ \Illuminate\Support\Carbon::parse($meal->meal_date)->locale('ar')->translatedFormat('j M') }} · {{ $meal->calories }} سعرة</small>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="ddash-empty"><i data-lucide="utensils"></i><p>ما سجّل المريض أي وجبة آخر 7 أيام.</p></div>
                @endif
            </div>

            <div class="ddash-card ddash-accent--amber">
                <div class="ddash-card__head"><div><span>لليوم</span><h2>هدف السعرات</h2></div><i data-lucide="target" class="ddash-card__icon"></i></div>

                @if ($currentGoal)
                    <div class="ddash-mini-grid" style="margin-bottom:16px">
                        <div class="ddash-mini"><i data-lucide="flame"></i><strong>{{ $currentGoal->calories_goal }}</strong><span>سعرة</span></div>
                        <div class="ddash-mini"><i data-lucide="drumstick"></i><strong>{{ $currentGoal->protein_goal ?: '—' }}</strong><span>بروتين (غ)</span></div>
                        <div class="ddash-mini"><i data-lucide="wheat"></i><strong>{{ $currentGoal->carbs_goal ?: '—' }}</strong><span>كارب (غ)</span></div>
                    </div>
                    <p style="font-size:.76rem;color:var(--d-muted);font-weight:650;margin:0 0 14px">محدّد فعلياً لليوم — عدّليه بالنموذج تحت لو بدك تغييره.</p>
                @endif

                <form method="POST" action="{{ route('doctor.patients.calorie-goal', $profile->id) }}" class="dpat-goal-form">
                    @csrf
                    <div class="dpat-goal-form__row">
                        <label>السعرات المستهدفة</label>
                        <input type="number" name="calories_goal" value="{{ $currentGoal->calories_goal ?? '' }}" placeholder="مثلاً 1800" required>
                    </div>
                    <div class="dpat-goal-form__row">
                        <label>مدة الخطة</label>
                        <select name="duration_days">
                            <option value="1">يوم واحد (اليوم فقط)</option>
                            <option value="7">7 أيام</option>
                            <option value="14">14 يوم</option>
                            <option value="30">30 يوم</option>
                        </select>
                    </div>
                    <div class="dpat-goal-form__grid">
                        <div class="dpat-goal-form__row">
                            <label>بروتين (غ)</label>
                            <input type="number" name="protein_goal" value="{{ $currentGoal->protein_goal ?? '' }}">
                        </div>
                        <div class="dpat-goal-form__row">
                            <label>كارب (غ)</label>
                            <input type="number" name="carbs_goal" value="{{ $currentGoal->carbs_goal ?? '' }}">
                        </div>
                        <div class="dpat-goal-form__row">
                            <label>دهون (غ)</label>
                            <input type="number" name="fat_goal" value="{{ $currentGoal->fat_goal ?? '' }}">
                        </div>
                    </div>
                    <div class="dpat-goal-form__row">
                        <label>ملاحظة للمريض (اختياري)</label>
                        <textarea name="doctor_note" rows="2" placeholder="مثلاً: قلّل الملح هالأسبوع...">{{ $currentGoal->doctor_note ?? '' }}</textarea>
                    </div>
                    <button type="submit" class="dreq-btn dreq-btn--success" style="width:100%;justify-content:center;display:flex">
                        {{ $currentGoal ? 'تحديث الهدف' : 'تحديد الهدف' }}
                    </button>
                </form>
            </div>

        </div>
    @else
        <div class="ddash-card ddash-accent--amber">
            <div class="ddash-empty">
                <i data-lucide="lock"></i>
                <p>متابعة التغذية وتحديد هدف السعرات بتصير متاحة بعد ما توافقي على طلب المتابعة.</p>
            </div>
        </div>
    @endif

    {{-- ══════════ الوزن والسعرات منذ بداية المتابعة ══════════ --}}
    @if ($requestStatus === 'approved')
        <div class="ddash-grid ddash-grid--half">

            {{-- سجل الوزن --}}
            <div class="ddash-card ddash-accent--violet">
                <div class="ddash-card__head">
                    <div><span>منذ {{ $followupStart->locale('ar')->translatedFormat('j F') }}</span><h2>تتبّع الوزن</h2></div>
                    <i data-lucide="scale" class="ddash-card__icon"></i>
                </div>

                @if ($weightLogs->count() >= 2)
                    @php
                        $firstW = (float) $weightLogs->first()->weight_kg;
                        $lastW = (float) $weightLogs->last()->weight_kg;
                        $diffW = round($lastW - $firstW, 1);
                    @endphp
                    <div class="ddash-mini-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:16px">
                        <div class="ddash-mini"><i data-lucide="flag"></i><strong>{{ $firstW }}</strong><span>أول قياس</span></div>
                        <div class="ddash-mini"><i data-lucide="{{ $diffW <= 0 ? 'trending-down' : 'trending-up' }}"></i><strong style="color: {{ $diffW <= 0 ? 'var(--d-green)' : 'var(--d-rose)' }}">{{ $diffW > 0 ? '+' : '' }}{{ $diffW }}</strong><span>الفرق (كغم)</span></div>
                        <div class="ddash-mini"><i data-lucide="target"></i><strong>{{ $lastW }}</strong><span>آخر قياس</span></div>
                    </div>
                    <div class="ddash-chart-wrap" style="height:180px"><canvas id="dpatWeightChart"></canvas></div>
                @elseif ($weightLogs->count() === 1)
                    <div class="ddash-mini-grid" style="margin-bottom:16px">
                        <div class="ddash-mini" style="grid-column:span 3"><i data-lucide="scale"></i><strong>{{ $weightLogs->first()->weight_kg }} كغم</strong><span>أول قياس مسجّل — بانتظار قياس تاني للمقارنة</span></div>
                    </div>
                @else
                    <div class="ddash-empty"><i data-lucide="scale"></i><p>لسا ما في قياس وزن مسجّل. سجّلي أول قياس من النموذج تحت.</p></div>
                @endif

                <form method="POST" action="{{ route('doctor.patients.log-weight', $profile->id) }}" class="dpat-goal-form" style="margin-top:16px;border-top:1px solid var(--d-border);padding-top:16px">
                    @csrf
                    <div class="dpat-goal-form__grid" style="grid-template-columns:1fr 1fr 1.4fr">
                        <div class="dpat-goal-form__row">
                            <label>الوزن (كغم)</label>
                            <input type="number" step="0.1" name="weight_kg" placeholder="مثلاً 78.5" required>
                        </div>
                        <div class="dpat-goal-form__row">
                            <label>التاريخ</label>
                            <input type="date" name="logged_date" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}">
                        </div>
                        <div class="dpat-goal-form__row">
                            <label>ملاحظة (اختياري)</label>
                            <input type="text" name="note" placeholder="مثلاً: بعد الفحص السريري">
                        </div>
                    </div>
                    <button type="submit" class="dreq-btn dreq-btn--success" style="width:100%;justify-content:center;display:flex">تسجيل القياس</button>
                </form>
            </div>

            {{-- اتجاه السعرات --}}
            <div class="ddash-card ddash-accent--teal">
                <div class="ddash-card__head">
                    <div><span>منذ {{ $followupStart->locale('ar')->translatedFormat('j F') }}</span><h2>اتجاه السعرات اليومي</h2></div>
                    <i data-lucide="trending-up" class="ddash-card__icon"></i>
                </div>

                @if (collect($caloriesTrend)->sum('calories') > 0)
                    <div class="ddash-chart-wrap" style="height:220px"><canvas id="dpatCaloriesChart"></canvas></div>
                @else
                    <div class="ddash-empty"><i data-lucide="utensils"></i><p>ما في وجبات مسجّلة منذ بداية المتابعة لعرض الاتجاه.</p></div>
                @endif
            </div>

            {{-- المهام --}}
            <div class="ddash-card ddash-accent--violet">
                <div class="ddash-card__head">
                    <div><span>Journey</span><h2>مهام حددتها للمريض</h2></div>
                    <i data-lucide="list-checks" class="ddash-card__icon"></i>
                </div>

                @forelse ($tasks as $task)
                    <div class="ddash-item">
                        <span class="ddash-avatar ddash-avatar--violet"><i data-lucide="{{ $task->status === 'completed' ? 'check' : 'clock' }}"></i></span>
                        <div class="ddash-item__body">
                            <strong>{{ $task->title }}</strong>
                            <small>
                                {{ \Illuminate\Support\Carbon::parse($task->task_date)->locale('ar')->translatedFormat('j M Y') }}
                                @if ($task->task_time) · {{ \Illuminate\Support\Carbon::parse($task->task_time)->format('H:i') }} @endif
                            </small>
                        </div>
                        <span class="ddash-pill ddash-pill--{{ $task->status === 'completed' ? 'green' : 'amber' }}">
                            {{ $task->status === 'completed' ? 'أنجزها' : 'بانتظار' }}
                        </span>
                    </div>
                @empty
                    <div class="ddash-empty"><i data-lucide="list-checks"></i><p>ما حددتلها أي مهمة لسا.</p></div>
                @endforelse

                <form method="POST" action="{{ route('doctor.patients.tasks.assign', $profile->id) }}" class="dpat-goal-form" style="margin-top:16px;border-top:1px solid var(--d-border);padding-top:16px">
                    @csrf
                    <div class="dpat-goal-form__row">
                        <label>عنوان المهمة</label>
                        <input type="text" name="title" placeholder="مثلاً: امشي 30 دقيقة" required minlength="3" maxlength="160">
                    </div>
                    <div class="dpat-goal-form__grid" style="grid-template-columns:1fr 1fr">
                        <div class="dpat-goal-form__row">
                            <label>التاريخ</label>
                            <input type="date" name="task_date" value="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="dpat-goal-form__row">
                            <label>الوقت (اختياري)</label>
                            <input type="time" name="task_time">
                        </div>
                    </div>
                    <div class="dpat-goal-form__row">
                        <label>وصف (اختياري)</label>
                        <input type="text" name="description" placeholder="تفاصيل إضافية">
                    </div>
                    <button type="submit" class="dreq-btn dreq-btn--success" style="width:100%;justify-content:center;display:flex">إسناد المهمة</button>
                </form>
            </div>
        </div>
    @endif

    {{-- ══════════ سجل المواعيد ══════════ --}}
    <div class="ddash-card ddash-accent--blue">
        <div class="ddash-card__head"><div><span>معك</span><h2>سجل المواعيد</h2></div><i data-lucide="calendar-clock" class="ddash-card__icon"></i></div>

        @forelse ($appointments as $apt)
            <div class="ddash-item">
                <span class="ddash-avatar ddash-avatar--blue"><i data-lucide="{{ ($apt->consultation_type ?? '') === 'online' ? 'video' : 'building-2' }}" style="width:16px;height:16px"></i></span>
                <div class="ddash-item__body">
                    <strong>{{ \Illuminate\Support\Carbon::parse($apt->appointment_date)->locale('ar')->translatedFormat('D j M Y') }}</strong>
                    <small>
                        @if ($apt->appointment_time){{ \Illuminate\Support\Carbon::parse($apt->appointment_time)->format('H:i') }} · @endif
                        {{ ($apt->consultation_type ?? '') === 'online' ? 'عن بُعد' : 'حضوري' }}
                        @if (!empty($apt->reason)) · {{ $apt->reason }}@endif
                    </small>
                </div>
                <span class="ddash-pill ddash-pill--{{ in_array($apt->status, ['confirmed','approved']) ? 'green' : ($apt->status === 'pending' ? 'amber' : ($apt->status === 'completed' ? 'blue' : 'muted')) }}">
                    {{ $apt->status }}
                </span>
            </div>
        @empty
            <div class="ddash-empty"><i data-lucide="calendar-x"></i><p>ما في مواعيد مسجّلة مع هالمريض بعد.</p></div>
        @endforelse
    </div>

    {{-- ══════════ مودال سبب الرفض (اختياري) ══════════ --}}
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

    /* تدرّج تحت الخط — نفس أسلوب لمعة الكروت الموحّدة، بس هون لتعبئة الرسم البياني */
    function verticalGradient(ctx, area, colorTop, colorBottom) {
        var gradient = ctx.createLinearGradient(0, area.top, 0, area.bottom);
        gradient.addColorStop(0, colorTop);
        gradient.addColorStop(1, colorBottom);
        return gradient;
    }

    /* رسم بياني الوزن — خط ناعم بتدرّج حقيقي تحته */
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
                scales: { y: { ticks: { precision: 1 } }, x: { grid: { display: false } } }
            }
        });
    }

    /* رسم بياني السعرات — خط ناعم بتدرّج + خط مقارنة مع هدف الطبيب */
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
                plugins: { legend: { display: !!goalValue, position: 'top', align: 'end', labels: { boxWidth: 12, font: { size: 11 } } } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false } } }
            }
        });
    }
});
</script>
@endpush
@endsection