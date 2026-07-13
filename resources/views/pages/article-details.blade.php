@extends('layouts.public')

@section('title', $article->title . ' | اتزان')

@push('styles')
<link rel="stylesheet" href="{{ asset('front/css/article-details.css') }}">
@endpush

@section('content')

<section class="article-details-page">
  <div class="container">

    <div class="article-breadcrumb">
      <a href="{{ route('home') }}#hero">الرئيسية</a>
      <span>/</span>
      <a href="{{ route('articles') }}">المقالات التوعوية</a>
      <span>/</span>
      <strong>{{ $article->title }}</strong>
    </div>

    <div class="article-details-grid">

      <main class="article-main">

        <div class="article-hero-card">
          <div class="article-hero-meta">
            <span class="article-tag">
              {{ optional($article->category)->name ?? 'عام' }}
            </span>

            <div class="article-meta-row">
              <span>
                <i class="fa-regular fa-calendar"></i>
                {{ $article->publish_date_label }}
              </span>

              <span>
                <i class="fa-regular fa-clock"></i>
                {{ $article->reading_time_label }}
              </span>

              <span>
                <i class="fa-regular fa-user"></i>
                {{ $article->public_author_name }}
              </span>
            </div>
          </div>

          <h1>{{ $article->title }}</h1>

          @if($article->excerpt)
            <p class="article-intro">
              {{ $article->excerpt }}
            </p>
          @endif

          <div class="article-cover">
            @if($article->cover_image)
              <img
                src="{{ $article->cover_image_url }}"
                alt="{{ $article->title }}"
                style="width:100%;height:100%;object-fit:cover;"
              >
            @else
              <div class="article-cover__icon">
                <i class="{{ $article->icon ?: optional($article->category)->icon ?: 'fa-regular fa-newspaper' }}"></i>
              </div>
            @endif
          </div>
        </div>

        <article class="article-content-card">
          @if($article->content !== strip_tags($article->content))
            {!! $article->content !!}
          @else
            <section class="article-section">
              <p>{!! nl2br(e($article->content)) !!}</p>
            </section>
          @endif
        </article>

        <div class="article-bottom-box">
          <div class="article-bottom-box__text">
            <h3>هل تبحث عن المزيد من المقالات المشابهة؟</h3>
            <p>
              تصفح مقالات التوعية الصحية للوصول إلى محتوى يساعدك على بناء أسلوب
              حياة أكثر وعيًا وتوازنًا.
            </p>
          </div>

          <a href="{{ route('articles') }}" class="article-back-btn">
            العودة إلى المقالات
          </a>
        </div>

      </main>

      <aside class="article-sidebar">
        <div class="sidebar-sticky">

          <div class="article-side-card">
            <h3>معلومات سريعة</h3>

            <div class="quick-info-list">
              <div class="quick-info-item">
                <span>التصنيف</span>
                <strong>{{ optional($article->category)->name ?? 'عام' }}</strong>
              </div>

              <div class="quick-info-item">
                <span>مدة القراءة</span>
                <strong>{{ $article->reading_time_label }}</strong>
              </div>

              <div class="quick-info-item">
                <span>تاريخ النشر</span>
                <strong>{{ $article->publish_date_label }}</strong>
              </div>
            </div>
          </div>

          <div class="article-side-card">
            <h3>مقالات مشابهة</h3>

            @forelse($relatedArticles as $relatedArticle)
              <a href="{{ route('articles.show', $relatedArticle->slug) }}" class="related-article">
                <span class="related-article__tag">
                  {{ optional($relatedArticle->category)->name ?? 'عام' }}
                </span>

                <strong>{{ $relatedArticle->title }}</strong>
              </a>
            @empty
              <p>لا توجد مقالات مشابهة حاليًا.</p>
            @endforelse
          </div>

        </div>
      </aside>

    </div>
  </div>
</section>

@endsection
