@extends('layouts.doctor')

@php
    $pageTitle = 'التقارير';
    $activePage = 'reports';
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/doctor/dashboard.css') }}">
@endpush

@section('content')
<section class="ddash">

    <div class="ddash-section-head">
        <h2>التقارير</h2>
        <span>ملخص حقيقي لأداء مرضاك ونشاطك على اتزان</span>
    </div>

    <div class="ddash-stats-grid" style="grid-template-columns:repeat(4,1fr)">
        <div class="ddash-stat-card ddash-accent--blue">
            <div class="ddash-stat-card__top"><span class="ddash-stat-card__icon"><i data-lucide="users-round"></i></span></div>
            <div class="ddash-stat-card__num">{{ $activePatients }}</div>
            <div class="ddash-stat-card__label">مرضى نشطون</div>
            <div class="ddash-stat-card__desc">تحت متابعتك</div>
        </div>
        <div class="ddash-stat-card ddash-accent--green">
            <div class="ddash-stat-card__top"><span class="ddash-stat-card__icon"><i data-lucide="check-check"></i></span></div>
            <div class="ddash-stat-card__num">{{ $completionRate }}%</div>
            <div class="ddash-stat-card__label">إنجاز المهام</div>
            <div class="ddash-stat-card__desc">آخر 30 يوم</div>
        </div>
        <div class="ddash-stat-card ddash-accent--teal">
            <div class="ddash-stat-card__top"><span class="ddash-stat-card__icon"><i data-lucide="utensils"></i></span></div>
            <div class="ddash-stat-card__num">{{ $mealsThisMonth }}</div>
            <div class="ddash-stat-card__label">وجبة مسجّلة</div>
            <div class="ddash-stat-card__desc">هالشهر</div>
        </div>
        <div class="ddash-stat-card ddash-accent--rose">
            <div class="ddash-stat-card__top"><span class="ddash-stat-card__icon"><i data-lucide="newspaper"></i></span></div>
            <div class="ddash-stat-card__num">{{ $publishedArticles }}</div>
            <div class="ddash-stat-card__label">مقالات منشورة</div>
            <div class="ddash-stat-card__desc">باسمك</div>
        </div>
    </div>

    <div class="ddash-grid ddash-grid--half">
        <div class="ddash-card ddash-accent--teal">
            <div class="ddash-card__head">
                <div><span>آخر 7 أيام</span><h2>الوجبات المسجّلة يومياً</h2></div>
                <i data-lucide="line-chart" class="ddash-card__icon"></i>
            </div>
            @if (collect($weeklyMeals)->sum('count') > 0)
                <div class="ddash-chart-wrap"><canvas id="doctorMealsChart"></canvas></div>
            @else
                <div class="ddash-empty"><i data-lucide="calendar-off"></i><p>ما في وجبات مسجّلة بآخر 7 أيام لعرضها.</p></div>
            @endif
        </div>

        <div class="ddash-card ddash-accent--amber">
            <div class="ddash-card__head">
                <div><span>Compliance</span><h2>أكثر المرضى التزاماً</h2></div>
                <i data-lucide="trophy" class="ddash-card__icon"></i>
            </div>
            @if ($topPatients->isEmpty())
                <div class="ddash-empty"><i data-lucide="utensils"></i><p>ولا مريض سجّل وجبات لسا آخر 30 يوم.</p></div>
            @else
                <div>
                    @foreach ($topPatients as $patient)
                        <div class="ddash-item">
                            <span class="ddash-avatar ddash-avatar--amber">{{ mb_substr($patient->name, 0, 1) }}</span>
                            <div class="ddash-item__body"><strong>{{ $patient->name }}</strong></div>
                            <span class="ddash-pill ddash-pill--green">{{ $patient->meals_count }} وجبة</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var canvas = document.getElementById('doctorMealsChart');
    if (canvas && typeof Chart !== 'undefined') {
        var labels = @json(collect($weeklyMeals)->pluck('label'));
        var counts = @json(collect($weeklyMeals)->pluck('count'));
        var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        var ctx = canvas.getContext('2d');
        var grad = ctx.createLinearGradient(0, 0, 0, 250);
        grad.addColorStop(0, 'rgba(20,184,166,.85)');
        grad.addColorStop(1, 'rgba(20,184,166,.35)');

        new Chart(canvas, {
            type: 'bar',
            data: { labels: labels, datasets: [{ data: counts, backgroundColor: grad, hoverBackgroundColor: '#14b8a6', borderRadius: 8, maxBarThickness: 40 }] },
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
});
</script>
@endpush
