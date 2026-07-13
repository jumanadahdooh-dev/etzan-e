@extends('layouts.patient')

@section('title', ($patientArticle->title ?? 'تفاصيل المقال') . ' | اتزان')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/patient/articles.css') }}">
@endpush

@section('content')
@php
    $article = $patientArticle;
    $related = collect($relatedPatientArticles ?? []);
@endphp

<section class="pa-page pa-detail-page">
    <div class="pa-breadcrumb">
        <a href="{{ route('patient.home') }}">الرئيسية</a>
        <i data-lucide="chevron-left"></i>
        <a href="{{ route('patient.articles') }}">المقالات</a>
        <i data-lucide="chevron-left"></i>
        <strong>{{ $article->title }}</strong>
    </div>

    <div class="pa-detail-layout">
        <main class="pa-article-main">
            <article class="pa-detail-hero">
                <div class="pa-detail-meta-top">
                    <span class="pa-tag">{{ $article->article_type_label ?? (optional($article->category)->name ?? optional($article->specialty)->name ?? 'مقال صحي') }}</span>

                    @if($isCurrentDoctorArticle ?? false)
                        <span class="pa-doctor-badge"><i data-lucide="stethoscope"></i> من طبيبك</span>
                    @endif
                </div>

                <h1>{{ $article->title }}</h1>

                @if($article->excerpt)
                    <p>{{ $article->excerpt }}</p>
                @endif

                <div class="pa-meta pa-detail-meta">
                    <span><i data-lucide="clock-3"></i>{{ $article->reading_time_label }}</span>
                    <span><i data-lucide="calendar-days"></i>{{ $article->publish_date_label }}</span>
                    <span><i data-lucide="user-round"></i>{{ $article->public_author_name }}</span>
                </div>

                <div class="pa-detail-cover">
                    @if($article->cover_image)
                        <img src="{{ $article->cover_image_url }}" alt="{{ $article->title }}">
                    @else
                        <i data-lucide="newspaper"></i>
                    @endif
                </div>
            </article>

            <article class="pa-content-card">
                @if($article->content !== strip_tags($article->content))
                    {!! $article->content !!}
                @else
                    <p>{!! nl2br(e($article->content)) !!}</p>
                @endif

                @if(!empty($article->medical_disclaimer))
                    <div class="pa-medical-disclaimer">
                        <i data-lucide="shield-heart"></i>
                        <span>{{ $article->medical_disclaimer }}</span>
                    </div>
                @endif
            </article>

            <div class="pa-bottom-actions">
                <a href="{{ route('patient.articles') }}" class="pa-primary-link">
                    العودة لمكتبة المقالات
                    <i data-lucide="arrow-left"></i>
                </a>
            </div>
        </main>

        <aside class="pa-article-side">
            <div class="pa-side-card">
                <span>معلومات سريعة</span>
                <div class="pa-info-list">
                    <div>
                        <small>التصنيف</small>
                        <strong>{{ optional($article->category)->name ?? optional($article->specialty)->name ?? 'عام' }}</strong>
                    </div>
                    <div>
                        <small>الكاتب</small>
                        <strong>{{ $article->public_author_name }}</strong>
                    </div>
                    <div>
                        <small>مدة القراءة</small>
                        <strong>{{ $article->reading_time_label }}</strong>
                    </div>
                    <div>
                        <small>نوع المحتوى</small>
                        <strong>{{ $article->article_type_label ?? 'مقال صحي' }}</strong>
                    </div>
                    <div>
                        <small>مناسب لفئة</small>
                        <strong>{{ $article->audience_label ?? 'مريض عام' }}</strong>
                    </div>
                </div>
            </div>

            @if(!empty($article->source_title) || !empty($article->source_url) || !empty($article->source_year))
                <div class="pa-side-card pa-source-card">
                    <span>المصدر أو المرجع</span>
                    <strong>{{ $article->source_title ?: 'مصدر يحتاج مراجعة' }}</strong>
                    @if(!empty($article->source_year))
                        <small>{{ $article->source_year }}</small>
                    @endif
                    @if(!empty($article->source_url))
                        <a href="{{ $article->source_url }}" target="_blank" rel="noopener noreferrer">فتح المصدر</a>
                    @endif
                </div>
            @endif

            <div class="pa-side-card">
                <span>مقالات مشابهة</span>

                @forelse($related as $relatedArticle)
                    <a href="{{ route('patient.articles.show', $relatedArticle->slug) }}" class="pa-related-link">
                        <small>{{ optional($relatedArticle->category)->name ?? optional($relatedArticle->specialty)->name ?? 'مقال' }}</small>
                        <strong>{{ $relatedArticle->title }}</strong>
                    </a>
                @empty
                    <p class="pa-side-empty">لا توجد مقالات مشابهة حاليًا.</p>
                @endforelse
            </div>
        </aside>
    </div>
</section>
@endsection
