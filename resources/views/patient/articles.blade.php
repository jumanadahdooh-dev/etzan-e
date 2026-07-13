@extends('layouts.patient')

@section('title', 'مقالات ونصائح | اتزان')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/patient/articles.css') }}">
@endpush

@section('content')
@php
    $patientArticles = $patientArticles ?? collect();
    $doctorHighlights = collect($doctorArticleHighlights ?? []);
    $featured = $featuredPatientArticle ?? null;
    $categories = collect($patientArticleCategories ?? []);
    $doctorInfo = $articlesDoctor ?? [];
    $hasDoctorArticles = $doctorHighlights->isNotEmpty();
    $currentFilter = $articleFilter ?? 'all';
    $currentCategory = $selectedArticleCategory ?? 'all';
    $currentSearch = $articleSearch ?? '';
    $hasActiveFilter = $hasActiveArticleFilter ?? false;
    $doctorArticlesCount = $doctorArticlesCount ?? ($hasDoctorArticles ? $doctorHighlights->count() : 0);
    $etzanArticlesCount = $etzanArticlesCount ?? 0;
    $allVisibleArticlesCount = $allVisibleArticlesCount ?? (method_exists($patientArticles, 'total') ? $patientArticles->total() : (method_exists($patientArticles, 'count') ? $patientArticles->count() : 0));
    $recommendedArticlesCount = $recommendedArticlesCount ?? 0;
    $researchArticlesCount = $researchArticlesCount ?? 0;
    $tipsArticlesCount = $tipsArticlesCount ?? 0;
    $factsArticlesCount = $factsArticlesCount ?? 0;
    $ideasArticlesCount = $ideasArticlesCount ?? 0;
    $wisdomArticlesCount = $wisdomArticlesCount ?? 0;
    $motivationArticlesCount = $motivationArticlesCount ?? 0;
    $resultsTitle = $articleResultsTitle ?? 'مقالات صحية مختارة لك';
    $resultsSubtitle = $articleResultsSubtitle ?? 'نعرض هنا المقالات الصحية المناسبة للمريض فقط.';

    $makeArticleUrl = function ($article) {
        $slug = data_get($article, 'slug');
        return $slug ? route('patient.articles.show', $slug) : route('patient.articles');
    };
@endphp

<section class="pa-page">
    <div class="pa-hero">
        <div class="pa-hero-copy">
            <span class="pa-kicker">
                <i data-lucide="book-open-check"></i>
                مكتبة المريض
            </span>

            <h1>مقالات تفهم حالتك وتساعدك تلتزم بخطتك</h1>
            <p>
                هنا بتلاقي مقالات عامة من اتزان، ومعها مقالات الطبيب الخاص فيك لما يكون عنده محتوى منشور مناسب للمتابعة.
            </p>

            <form action="{{ route('patient.articles') }}" method="GET" class="pa-search">
                @if($currentFilter !== 'all')
                    <input type="hidden" name="filter" value="{{ $currentFilter }}">
                @endif

                @if($currentCategory !== 'all')
                    <input type="hidden" name="category" value="{{ $currentCategory }}">
                @endif

                <i data-lucide="search"></i>
                <input type="search" name="q" value="{{ $currentSearch }}" placeholder="ابحثي عن تغذية، نوم، سكر، وزن...">
                <button type="submit">بحث</button>
            </form>
        </div>

        <aside class="pa-hero-side">
            <div class="pa-doctor-mini">
                @if(!empty($doctorInfo['avatar']))
                    <img src="{{ $doctorInfo['avatar'] }}" alt="{{ $doctorInfo['name'] ?? 'طبيب المتابعة' }}">
                @else
                    <span><i data-lucide="stethoscope"></i></span>
                @endif

                <div>
                    <small>مقالات طبيبك</small>
                    <strong>{{ $doctorInfo['name'] ?? 'لم يتم اختيار طبيب' }}</strong>
                    <p>{{ $doctorInfo['specialty'] ?? 'اختاري طبيب المتابعة لتظهر مقالاته هنا' }}</p>
                </div>
            </div>

            <div class="pa-hero-stats">
                <div class="pa-hero-stat">
                    <span>{{ $doctorArticlesCount }}</span>
                    <small>مقالات من طبيبك</small>
                </div>

                <div class="pa-hero-stat">
                    <span>{{ $allVisibleArticlesCount }}</span>
                    <small>مقال صحي متاح</small>
                </div>
            </div>
        </aside>
    </div>

    <div class="pa-filter-bar">
        <div class="pa-filter-group pa-filter-group--smart">
            <a href="{{ route('patient.articles', ['filter' => 'recommended', 'q' => $currentSearch, 'category' => $currentCategory]) }}" class="pa-chip {{ $currentFilter === 'recommended' ? 'is-active' : '' }}">
                <i data-lucide="sparkles"></i>
                مناسب لحالتي
                <b>{{ $recommendedArticlesCount }}</b>
            </a>

            <a href="{{ route('patient.articles', ['filter' => 'all', 'q' => $currentSearch, 'category' => $currentCategory]) }}" class="pa-chip {{ $currentFilter === 'all' ? 'is-active' : '' }}">
                الكل
                <b>{{ $allVisibleArticlesCount }}</b>
            </a>

            <a href="{{ route('patient.articles', ['filter' => 'doctor', 'q' => $currentSearch, 'category' => $currentCategory]) }}" class="pa-chip {{ $currentFilter === 'doctor' ? 'is-active' : '' }}">
                مقالات طبيبي
                <b>{{ $doctorArticlesCount }}</b>
            </a>

            <a href="{{ route('patient.articles', ['filter' => 'etzan', 'q' => $currentSearch, 'category' => $currentCategory]) }}" class="pa-chip {{ in_array($currentFilter, ['etzan', 'admin'], true) ? 'is-active' : '' }}">
                مقالات اتزان
                <b>{{ $etzanArticlesCount }}</b>
            </a>

            <a href="{{ route('patient.articles', ['filter' => 'research', 'q' => $currentSearch, 'category' => $currentCategory]) }}" class="pa-chip {{ $currentFilter === 'research' ? 'is-active' : '' }}">
                أبحاث مبسطة
                <b>{{ $researchArticlesCount }}</b>
            </a>

            <a href="{{ route('patient.articles', ['filter' => 'tips', 'q' => $currentSearch, 'category' => $currentCategory]) }}" class="pa-chip {{ $currentFilter === 'tips' ? 'is-active' : '' }}">
                نصائح
                <b>{{ $tipsArticlesCount }}</b>
            </a>

            <a href="{{ route('patient.articles', ['filter' => 'facts', 'q' => $currentSearch, 'category' => $currentCategory]) }}" class="pa-chip {{ $currentFilter === 'facts' ? 'is-active' : '' }}">
                معلومات عامة
                <b>{{ $factsArticlesCount }}</b>
            </a>

            <a href="{{ route('patient.articles', ['filter' => 'ideas', 'q' => $currentSearch, 'category' => $currentCategory]) }}" class="pa-chip {{ $currentFilter === 'ideas' ? 'is-active' : '' }}">
                أفكار يومية
                <b>{{ $ideasArticlesCount }}</b>
            </a>

            <a href="{{ route('patient.articles', ['filter' => 'wisdom', 'q' => $currentSearch, 'category' => $currentCategory]) }}" class="pa-chip {{ $currentFilter === 'wisdom' ? 'is-active' : '' }}">
                حكمة اليوم
                <b>{{ $wisdomArticlesCount }}</b>
            </a>

            <a href="{{ route('patient.articles', ['filter' => 'motivation', 'q' => $currentSearch, 'category' => $currentCategory]) }}" class="pa-chip {{ $currentFilter === 'motivation' ? 'is-active' : '' }}">
                رسائل تحفيزية
                <b>{{ $motivationArticlesCount }}</b>
            </a>
        </div>

        @if($categories->isNotEmpty())
            <div class="pa-category-row">
                <a href="{{ route('patient.articles', ['filter' => $currentFilter, 'q' => $currentSearch]) }}" class="pa-category {{ $currentCategory === 'all' ? 'is-active' : '' }}">كل التصنيفات</a>
                @foreach($categories as $category)
                    <a href="{{ route('patient.articles', ['filter' => $currentFilter, 'category' => $category->id, 'q' => $currentSearch]) }}" class="pa-category {{ (string)$currentCategory === (string)$category->id ? 'is-active' : '' }}">
                        {{ $category->name }}
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    @if($featured && !$hasActiveFilter)
        <article class="pa-featured">
            <div class="pa-featured-cover">
                @if(!empty($featured->cover_image))
                    <img src="{{ $featured->cover_image_url }}" alt="{{ $featured->title }}">
                @else
                    <i data-lucide="newspaper"></i>
                @endif
            </div>

            <div class="pa-featured-copy">
                <span class="pa-tag">{{ $featured->article_type_label ?? (optional($featured->category)->name ?? optional($featured->specialty)->name ?? 'مقال مميز') }}</span>
                <h2>{{ $featured->title }}</h2>
                <p>{{ $featured->excerpt ?: \Illuminate\Support\Str::limit(strip_tags((string)$featured->content), 180) }}</p>

                <div class="pa-meta">
                    <span><i data-lucide="clock-3"></i>{{ $featured->reading_time_label }}</span>
                    <span><i data-lucide="calendar-days"></i>{{ $featured->publish_date_label }}</span>
                    <span><i data-lucide="user-round"></i>{{ $featured->public_author_name }}</span>
                </div>

                <a href="{{ $makeArticleUrl($featured) }}" class="pa-primary-link">
                    قراءة المقال داخل لوحة المريض
                    <i data-lucide="arrow-left"></i>
                </a>
            </div>
        </article>
    @endif

    @if(!$hasActiveFilter)
    <section class="pa-doctor-section {{ $hasDoctorArticles ? '' : 'is-empty' }}">
        <div class="pa-section-head">
            <div>
                <span>Doctor Picks</span>
                <h2>مقالات الطبيب الخاص بك</h2>
            </div>

            <a href="{{ route('patient.articles', ['filter' => 'doctor']) }}">عرض مقالات الطبيب</a>
        </div>

        @if($hasDoctorArticles)
            <div class="pa-doctor-grid">
                @foreach($doctorHighlights as $article)
                    <a href="{{ $makeArticleUrl($article) }}" class="pa-doctor-card">
                        <span><i data-lucide="stethoscope"></i></span>
                        <small>{{ $article->reading_time_label ?? 'قراءة قصيرة' }}</small>
                        <strong>{{ $article->title ?? 'مقال طبي' }}</strong>
                        <p>{{ $article->excerpt ?: \Illuminate\Support\Str::limit(strip_tags((string)$article->content), 80) }}</p>
                    </a>
                @endforeach
            </div>
        @else
            <div class="pa-empty-doctor">
                <i data-lucide="file-text"></i>
                <strong>لا توجد مقالات منشورة من طبيبك بعد</strong>
                <span>ستظهر هنا مقالات الطبيب المختار بمجرد نشرها واعتمادها.</span>
            </div>
        @endif
    </section>

    @endif

    <section class="pa-list-section">
        <div class="pa-section-head">
            <div>
                <span>{{ $hasActiveFilter ? 'Filtered Results' : 'Health Library' }}</span>
                <h2>{{ $resultsTitle }}</h2>
                <p class="pa-section-subtitle">{{ $resultsSubtitle }}</p>
            </div>

            @if($hasActiveFilter)
                <a href="{{ route('patient.articles') }}" class="pa-clear-filter">
                    مسح الفلترة
                    <i data-lucide="x"></i>
                </a>
            @endif
        </div>

        @if(method_exists($patientArticles, 'count') && $patientArticles->count())
            <div class="pa-grid">
                @foreach($patientArticles as $article)
                    <a href="{{ $makeArticleUrl($article) }}" class="pa-card">
                        <div class="pa-card-cover">
                            @if(!empty($article->cover_image))
                                <img src="{{ $article->cover_image_url }}" alt="{{ $article->title }}">
                            @else
                                <i data-lucide="file-text"></i>
                            @endif
                        </div>

                        <div class="pa-card-body">
                            <span class="pa-tag">{{ $article->article_type_label ?? (optional($article->category)->name ?? optional($article->specialty)->name ?? ($article->source_label ?? 'مقال صحي')) }}</span>
                            <h3>{{ $article->title }}</h3>
                            <p>{{ $article->excerpt ?: \Illuminate\Support\Str::limit(strip_tags((string)$article->content), 110) }}</p>

                            <div class="pa-meta">
                                <span><i data-lucide="clock-3"></i>{{ $article->reading_time_label }}</span>
                                <span><i data-lucide="user-round"></i>{{ $article->public_author_name }}</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            @if(method_exists($patientArticles, 'links'))
                <div class="pa-pagination">
                    {{ $patientArticles->links() }}
                </div>
            @endif
        @else
            <div class="pa-empty-state">
                <i data-lucide="folder-open"></i>
                <strong>لا توجد مقالات صحية مطابقة</strong>
                <span>جرّبي تغيير البحث أو الفلتر. المحتوى هنا صحي فقط، ويظهر حسب نوعه أو حسب حالة المريض.</span>
            </div>
        @endif
    </section>
</section>
@endsection
