@extends('layouts.doctor')

@php
    $pageTitle = 'تنبيهات المرضى';
    $activePage = 'alerts';
@endphp

@section('content')
<section class="doctor-page"><div class="doctor-filterbar"><div><span class="home-v2-kicker"><span><i data-lucide="sparkles"></i></span> متابعة الطبيب</span></div><input class="doctor-search" placeholder="ابحث عن مريض، موعد، مقال..."></div><div class="doctor-panel"><div class="doctor-panel-head"><div><span>Patient Alerts</span><h2>تنبيهات تحتاج متابعة</h2></div><i data-lucide="triangle-alert"></i></div><div class="doctor-tabs"><button class="doctor-tab is-active">الكل</button><button class="doctor-tab">High</button><button class="doctor-tab">Nutrition</button><button class="doctor-tab">Weight</button><button class="doctor-tab">Unread</button></div><br><div class="doctor-list"><div class="doctor-item compact"><div><span class="doctor-status danger">High Alert</span><strong>خالد: السعرات أقل من الهدف 4 أيام</strong><small>آخر تسجيل: 980 kcal / target 1700 kcal</small></div><div class="doctor-actions"><button class="doctor-btn primary">فتح الملف</button><button class="doctor-btn">Mark Reviewed</button></div></div><div class="doctor-item compact"><div><span class="doctor-status warn">Medium</span><strong>سارة تجاوزت السعرات 4 أيام هذا الأسبوع</strong><small>مراجعة وجبات العشاء مطلوبة</small></div><button class="doctor-btn">Review Meals</button></div></div></div></section>
@endsection
