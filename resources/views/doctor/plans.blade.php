@extends('layouts.doctor')

@php
    $pageTitle = 'الخطط الغذائية';
    $activePage = 'plans';
@endphp

@section('content')
<section class="doctor-page"><div class="doctor-filterbar"><div><span class="home-v2-kicker"><span><i data-lucide="sparkles"></i></span> متابعة الطبيب</span></div><input class="doctor-search" placeholder="ابحث عن مريض، موعد، مقال..."></div><div class="doctor-grid cols-2"><div class="doctor-panel"><div class="doctor-panel-head"><div><span>Nutrition Plans</span><h2>خطط المرضى</h2></div><button class="doctor-btn primary"><i data-lucide="plus"></i> خطة جديدة</button></div><div class="doctor-list"><div class="doctor-item compact"><div><strong>Sara Weight Loss Plan</strong><small>1700 kcal/day · Active · الالتزام 78%</small><div class="doctor-progress"><span style="width:78%"></span></div></div><button class="doctor-btn">تعديل</button></div><div class="doctor-item compact"><div><strong>Khaled Balanced Plan</strong><small>1800 kcal/day · Needs Update</small></div><span class="doctor-status warn">Updated</span></div></div></div><div class="doctor-panel"><div class="doctor-panel-head"><div><span>Create Plan</span><h2>نموذج سريع</h2></div><i data-lucide="clipboard-check"></i></div><form class="doctor-form"><input placeholder="اسم الخطة"><div class="doctor-split"><input placeholder="السعرات"><input placeholder="البروتين"></div><textarea placeholder="تعليمات عامة للمريض"></textarea><button type="button" class="doctor-btn primary">حفظ كمسودة</button></form></div></div></section>
@endsection
