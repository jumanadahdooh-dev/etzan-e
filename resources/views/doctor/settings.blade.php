@extends('layouts.doctor')

@php
    $pageTitle = 'الإعدادات';
    $activePage = 'settings';
@endphp

@section('content')
<section class="doctor-page"><div class="doctor-filterbar"><div><span class="home-v2-kicker"><span><i data-lucide="sparkles"></i></span> متابعة الطبيب</span></div><input class="doctor-search" placeholder="ابحث عن مريض، موعد، مقال..."></div><div class="doctor-grid cols-2"><div class="doctor-panel"><div class="doctor-panel-head"><div><span>Settings</span><h2>إعدادات الحساب</h2></div><i data-lucide="settings"></i></div><form class="doctor-form"><input value="dr.ahmad@etzan.com"><input value="0599000000"><select><option>استقبال مرضى جدد: نعم</option><option>لا</option></select><button type="button" class="doctor-btn primary">حفظ</button></form></div><div class="doctor-panel"><div class="doctor-panel-head"><div><span>Availability</span><h2>أوقات التوفر</h2></div><i data-lucide="clock"></i></div><div class="doctor-list"><div class="doctor-item compact"><strong>الأحد: 10:00 - 16:00</strong><span class="doctor-status">متاح</span></div><div class="doctor-item compact"><strong>الاثنين: 12:00 - 18:00</strong><span class="doctor-status">متاح</span></div><div class="doctor-item compact"><strong>الثلاثاء</strong><span class="doctor-status danger">غير متاح</span></div></div></div></div></section>
@endsection
