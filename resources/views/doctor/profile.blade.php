@extends('layouts.doctor')

@php
    $pageTitle = 'ملفي الشخصي';
    $activePage = 'profile';
@endphp

@section('content')
<section class="doctor-page"><div class="doctor-filterbar"><div><span class="home-v2-kicker"><span><i data-lucide="sparkles"></i></span> متابعة الطبيب</span></div><input class="doctor-search" placeholder="ابحث عن مريض، موعد، مقال..."></div><div class="doctor-grid cols-2"><div class="doctor-panel"><div class="doctor-panel-head"><div><span>Doctor Profile</span><h2>البيانات التي تظهر للمريض</h2></div><i data-lucide="user-round"></i></div><form class="doctor-form"><input value="د. أحمد منصور"><input value="تغذية علاجية"><textarea>طبيب تغذية متخصص في خسارة الوزن ومقاومة الإنسولين.</textarea><div class="doctor-split"><input value="8 سنوات خبرة"><input value="150 شيكل"></div><button type="button" class="doctor-btn primary">حفظ التغييرات</button></form></div><div class="doctor-panel"><div class="doctor-panel-head"><div><span>Public Preview</span><h2>معاينة ملف الطبيب</h2></div><i data-lucide="eye"></i></div><div class="doctor-card"><div class="doctor-card-row"><span class="avatar">د</span><div><h3>د. أحمد منصور</h3><p>تغذية علاجية · ⭐ 4.8 · متاح</p></div></div><br><p>متخصص في بناء خطط غذائية صحية ومتابعة المرضى عبر Etzan.</p><button class="doctor-btn primary">طلب استشارة</button></div></div></div></section>
@endsection
