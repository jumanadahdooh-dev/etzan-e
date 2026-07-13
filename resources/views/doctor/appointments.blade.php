@extends('layouts.doctor')

@php
    $pageTitle = 'المواعيد';
    $activePage = 'appointments';
@endphp

@section('content')
<section class="doctor-page"><div class="doctor-filterbar"><div><span class="home-v2-kicker"><span><i data-lucide="sparkles"></i></span> متابعة الطبيب</span></div><input class="doctor-search" placeholder="ابحث عن مريض، موعد، مقال..."></div><div class="doctor-panel"><div class="doctor-panel-head"><div><span>المواعيد</span><h2>إدارة جدول الاستشارات</h2></div><button class="doctor-btn primary"><i data-lucide="plus"></i> موعد جديد</button></div><div class="doctor-tabs"><button class="doctor-tab is-active">اليوم</button><button class="doctor-tab">الأسبوع</button><button class="doctor-tab">الشهر</button><button class="doctor-tab">قائمة</button></div><div class="doctor-table-wrap"><table class="doctor-table"><thead><tr><th>الوقت</th><th>المريض</th><th>النوع</th><th>الحالة</th><th>الإجراءات</th></tr></thead><tbody><tr><td>10:00 - 10:30</td><td>سارة أحمد</td><td>فيديو</td><td><span class="doctor-status">مؤكد</span></td><td><button class="doctor-btn primary">بدء الجلسة</button></td></tr><tr><td>11:30 - 12:00</td><td>خالد عمر</td><td>Chat</td><td><span class="doctor-status warn">بانتظار</span></td><td><button class="doctor-btn">تأكيد</button></td></tr><tr><td>02:00 - 02:30</td><td>مريم خليل</td><td>متابعة</td><td><span class="doctor-status">مؤكد</span></td><td><button class="doctor-btn ghost">تعديل</button></td></tr></tbody></table></div></div></section>
@endsection
