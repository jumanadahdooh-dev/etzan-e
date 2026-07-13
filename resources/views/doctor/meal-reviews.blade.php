@extends('layouts.doctor')

@php
    $pageTitle = 'مراجعة الوجبات AI';
    $activePage = 'meal-reviews';
@endphp

@section('content')
<section class="doctor-page"><div class="doctor-filterbar"><div><span class="home-v2-kicker"><span><i data-lucide="sparkles"></i></span> متابعة الطبيب</span></div><input class="doctor-search" placeholder="ابحث عن مريض، موعد، مقال..."></div><div class="doctor-panel"><div class="doctor-panel-head"><div><span>AI Meal Reviews</span><h2>مراجعة الوجبات المحللة بالذكاء الاصطناعي</h2></div><i data-lucide="bot"></i></div><div class="doctor-list"><div class="doctor-item"><span class="avatar">س</span><div><strong>سارة أحمد · Lunch</strong><small>AI Estimate: 780 kcal · Confidence: Medium · Patient: 750 kcal</small><div class="doctor-progress"><span style="width:78%"></span></div></div><div class="doctor-actions"><button class="doctor-btn primary">Approve</button><button class="doctor-btn">Adjust</button><button class="doctor-btn ghost">Feedback</button></div></div><div class="doctor-item"><span class="avatar">م</span><div><strong>مريم خليل · Dinner</strong><small>AI Estimate: 620 kcal · Confidence: Low</small></div><div class="doctor-actions"><button class="doctor-btn warn">Review</button><button class="doctor-btn ghost">Open</button></div></div></div></div></section>
@endsection
