@extends('layouts.doctor')

@php
    $pageTitle = 'الرسائل';
    $activePage = 'messages';
@endphp

@section('content')
<section class="doctor-page"><div class="doctor-filterbar"><div><span class="home-v2-kicker"><span><i data-lucide="sparkles"></i></span> متابعة الطبيب</span></div><input class="doctor-search" placeholder="ابحث عن مريض، موعد، مقال..."></div><div class="doctor-chat"><div class="doctor-panel"><div class="doctor-panel-head"><div><span>Inbox</span><h2>المحادثات</h2></div><i data-lucide="message-circle"></i></div><div class="conversation-list"><div class="doctor-item"><span class="avatar">س</span><div><strong>سارة أحمد</strong><small>سؤال عن وجبة الغداء</small></div><span class="nav-badge">3</span></div><div class="doctor-item"><span class="avatar">خ</span><div><strong>خالد عمر</strong><small>لم يتم تسجيل الوجبات</small></div><span class="doctor-status danger">Urgent</span></div><div class="doctor-item"><span class="avatar">م</span><div><strong>مريم خليل</strong><small>شكراً دكتور</small></div></div></div></div><div class="doctor-panel chat-window"><div class="doctor-panel-head"><div><span>سارة أحمد</span><h2>محادثة المريض</h2></div><a class="doctor-btn" href="{{ route('doctor.patient_details') }}">فتح الملف</a></div><div class="chat-messages"><div class="bubble">دكتور هل وجبة الغداء مناسبة للخطة؟</div><div class="bubble me">الوجبة جيدة، لكن قللي كمية الخبز المرة القادمة.</div><div class="bubble">تمام، شكراً.</div></div><div class="chat-compose"><input placeholder="اكتب ردك هنا..."><button class="doctor-btn primary"><i data-lucide="send"></i> إرسال</button></div></div></div></section>
@endsection
