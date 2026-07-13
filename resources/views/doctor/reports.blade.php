@extends('layouts.doctor')

@php
    $pageTitle = 'التقارير';
    $activePage = 'reports';
@endphp

@section('content')
<section class="doctor-page"><div class="doctor-filterbar"><div><span class="home-v2-kicker"><span><i data-lucide="sparkles"></i></span> متابعة الطبيب</span></div><input class="doctor-search" placeholder="ابحث عن مريض، موعد، مقال..."></div><div class="doctor-grid cols-4"><div class="doctor-card"><h3>Active Patients</h3><div class="doctor-number">28</div><p>+12% هذا الشهر</p></div><div class="doctor-card"><h3>Average Commitment</h3><div class="doctor-number">74%</div><p>متوسط المرضى</p></div><div class="doctor-card"><h3>Meal Reviews</h3><div class="doctor-number">156</div><p>تمت مراجعتها</p></div><div class="doctor-card"><h3>Articles</h3><div class="doctor-number">6</div><p>منشورة</p></div></div><div class="doctor-grid cols-2"><div class="doctor-panel"><div class="doctor-panel-head"><div><span>Progress</span><h2>متوسط نزول الوزن</h2></div><i data-lucide="line-chart"></i></div><div class="home-v2-empty"><i data-lucide="bar-chart-3"></i><strong>Chart Placeholder</strong><span>مكان الرسم البياني بنفس ستايل المريض.</span></div></div><div class="doctor-panel"><div class="doctor-panel-head"><div><span>Commitment</span><h2>التزام المرضى</h2></div><i data-lucide="pie-chart"></i></div><div class="home-v2-ring" style="--value:76"><div><strong>76%</strong><span>متوسط</span></div></div></div></div></section>
@endsection
