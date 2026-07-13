@extends('layouts.doctor')

@php
    $pageTitle = 'مقالاتي';
    $activePage = 'articles';
@endphp

@section('content')
<section class="doctor-page"><div class="doctor-filterbar"><div><span class="home-v2-kicker"><span><i data-lucide="sparkles"></i></span> متابعة الطبيب</span></div><input class="doctor-search" placeholder="ابحث عن مريض، موعد، مقال..."></div><div class="doctor-panel"><div class="doctor-panel-head"><div><span>My Articles</span><h2>مقالاتي وموافقة الأدمن</h2></div><button class="doctor-btn primary"><i data-lucide="plus"></i> مقال جديد</button></div><div class="doctor-grid cols-4"><div class="doctor-card"><h3>Published</h3><div class="doctor-number">6</div></div><div class="doctor-card"><h3>Pending</h3><div class="doctor-number">2</div></div><div class="doctor-card"><h3>Needs Changes</h3><div class="doctor-number">1</div></div><div class="doctor-card"><h3>Drafts</h3><div class="doctor-number">3</div></div></div><br><div class="doctor-list"><div class="doctor-item compact"><div><span class="doctor-status warn">Pending Review</span><strong>How to Build a Balanced Plate</strong><small>Nutrition · 5 min reading</small></div><div class="doctor-actions"><button class="doctor-btn">Preview</button><button class="doctor-btn ghost">Withdraw</button></div></div><div class="doctor-item compact"><div><span class="doctor-status">Approved</span><strong>Managing Night Cravings</strong><small>يمكن إرساله للمرضى</small></div><button class="doctor-btn primary">Recommend</button></div></div></div></section>
@endsection
