@extends('layouts.doctor')

@php
    $pageTitle = 'التقارير';
    $activePage = 'reports';
@endphp

@section('content')
<section class="doctor-page">
    <div class="doctor-filterbar">
        <div><span class="home-v2-kicker"><span><i data-lucide="sparkles"></i></span> متابعة الطبيب</span></div>
    </div>

    <div class="doctor-grid cols-4">
        <div class="doctor-card">
            <h3>Active Patients</h3>
            <div class="doctor-number">{{ $activePatients }}</div>
            <p>مرضى معتمدين حالياً</p>
        </div>
        <div class="doctor-card">
            <h3>Task Completion</h3>
            <div class="doctor-number">{{ $completionRate }}%</div>
            <p>إنجاز المهام آخر 30 يوم</p>
        </div>
        <div class="doctor-card">
            <h3>Meals Logged</h3>
            <div class="doctor-number">{{ $mealsThisMonth }}</div>
            <p>وجبة مسجّلة هذا الشهر</p>
        </div>
        <div class="doctor-card">
            <h3>Articles</h3>
            <div class="doctor-number">{{ $publishedArticles }}</div>
            <p>منشورة باسمك</p>
        </div>
    </div>

    <div class="doctor-grid cols-2">
        <div class="doctor-panel">
            <div class="doctor-panel-head">
                <div>
                    <span>Compliance</span>
                    <h2>أكثر المرضى التزاماً بالتسجيل (آخر 30 يوم)</h2>
                </div>
                <i data-lucide="trophy"></i>
            </div>

            @if ($topPatients->isEmpty())
                <p style="padding:16px 4px;color:var(--et-muted,#6b7280)">
                    ولا مريض سجّل وجبات لسا آخر 30 يوم.
                </p>
            @else
                <div class="doctor-list">
                    @foreach ($topPatients as $patient)
                        <div class="doctor-item compact">
                            <div><strong>{{ $patient->name }}</strong></div>
                            <span class="doctor-status">{{ $patient->meals_count }} وجبة</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="doctor-panel">
            <div class="doctor-panel-head">
                <div>
                    <span>Commitment</span>
                    <h2>إنجاز المهام الإجمالي</h2>
                </div>
                <i data-lucide="pie-chart"></i>
            </div>
            <div class="home-v2-ring" style="--value:{{ $completionRate }}">
                <div><strong>{{ $completionRate }}%</strong><span>متوسط</span></div>
            </div>
        </div>
    </div>
</section>
@endsection
