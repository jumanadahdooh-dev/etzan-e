@extends('layouts.admin')

@section('title', 'مجلة المقالات | اتزان')

@push('styles')
<link rel="stylesheet" href="{{ asset('front/css/admin/admin-articles.css') }}">
@endpush

@section('content')
@php
    $isCreateError = $errors->any() && old('_form') === 'create';
    $isAiError = $errors->any() && old('_form') === 'ai_generate';
    $editErrorId = old('_form') === 'edit' ? (int) old('article_id') : null;
    $rejectErrorId = old('_form') === 'reject' ? (int) old('article_id') : null;

    $currentStatus = request('status', 'all');

    $articleItems = collect($articles->items());
    $featuredArticle = $articleItems->firstWhere('is_featured', true) ?? $articleItems->first();
    $regularArticles = $articleItems->filter(fn ($item) => !$featuredArticle || $item->id !== $featuredArticle->id);

    $statusTabs = [
        'all' => [
            'label' => 'الكل',
            'count' => $stats['total'],
            'icon' => 'fa-solid fa-layer-group',
        ],
        'published' => [
            'label' => 'منشور',
            'count' => $stats['published'],
            'icon' => 'fa-solid fa-circle-check',
        ],
        'pending_review' => [
            'label' => 'قيد المراجعة',
            'count' => $stats['pending'],
            'icon' => 'fa-solid fa-hourglass-half',
        ],
        'draft' => [
            'label' => 'مسودات',
            'count' => $stats['draft'],
            'icon' => 'fa-regular fa-pen-to-square',
        ],
        'rejected' => [
            'label' => 'مرفوض',
            'count' => $stats['rejected'],
            'icon' => 'fa-solid fa-circle-xmark',
        ],
    ];

    $queryBase = request()->except(['page', 'status']);

    $statusUrl = function ($status) use ($queryBase) {
        return route(
            'admin.articles.index',
            $status === 'all'
                ? $queryBase
                : array_merge($queryBase, ['status' => $status])
        );
    };

    $selectedSpecialty = $specialties->firstWhere('id', request('specialty_id'));
@endphp

<section class="admin-magazine-page">

    @if (session('success'))
        <div class="magazine-toast magazine-toast--success">
            <i class="fa-solid fa-circle-check"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- Hero --}}
    <section class="magazine-hero-v2 magazine-hero-v2--compact">
        <div>
            <span class="magazine-kicker-v2">
                <i class="fa-regular fa-newspaper"></i>
                مجلة اتزان الطبية
            </span>

            <h1>إدارة المقالات</h1>

            <p>
                تابعي المقالات، ابحثي بسرعة، وفلترِي حسب الحالة والمصدر والتخصص.
            </p>
        </div>

        <div class="magazine-hero-actions-v2">
            <button type="button" class="magazine-ai-btn-v2" data-open-modal="aiArticleModal">
                <i class="fa-solid fa-wand-magic-sparkles"></i>
                <span>توليد مسودة AI</span>
            </button>

            <button type="button" class="magazine-primary-btn-v2" data-open-modal="createArticleModal">
                <i class="fa-solid fa-plus"></i>
                <span>إضافة مقال</span>
            </button>
        </div>
    </section>

    {{-- Insight Dashboard --}}
    <section class="articles-dashboard-grid">
        <div class="articles-chart-card">
            <div class="articles-section-head">
                <span>نظرة عامة</span>
                <h2>حالة المقالات</h2>
                <p>اضغطي على أي جزء من الدائرة لعرض تفاصيل الحالة.</p>
            </div>

            <div class="articles-chart-layout">
                <div class="articles-donut-wrap">
                    <svg class="articles-donut-svg" viewBox="0 0 200 200">
                        <circle class="articles-donut-track" cx="100" cy="100" r="72" pathLength="100"></circle>
                        <g id="articlesSegGroup"></g>
                        <circle class="articles-donut-inner" cx="100" cy="100" r="55"></circle>

                        <g class="articles-donut-center">
                            <text class="articles-donut-percent" x="100" y="95" id="articlesCenterPercent">0%</text>
                            <text class="articles-donut-label" x="100" y="115" id="articlesCenterLabel">منشور</text>
                        </g>
                    </svg>
                </div>

                <div class="articles-chart-info">
                    <span>التفاصيل الحالية</span>
                    <h3 id="articlesInfoTitle">المقالات المنشورة</h3>
                    <p id="articlesInfoDesc">المقالات التي تظهر حاليًا للمستخدمين داخل الموقع.</p>

                    <div class="articles-count-box">
                        <div>
                            <small>العدد</small>
                            <strong id="articlesInfoCount">{{ $stats['published'] }}</strong>
                        </div>
                        <b id="articlesInfoPercent">0%</b>
                    </div>

                    <div class="articles-legend">
                        <button type="button" class="articles-legend-btn active" data-article-seg="0">
                            <span class="articles-dot dot-published"></span>
                            منشور
                        </button>

                        <button type="button" class="articles-legend-btn" data-article-seg="1">
                            <span class="articles-dot dot-pending"></span>
                            مراجعة
                        </button>

                        <button type="button" class="articles-legend-btn" data-article-seg="2">
                            <span class="articles-dot dot-draft"></span>
                            مسودات
                        </button>

                        <button type="button" class="articles-legend-btn" data-article-seg="3">
                            <span class="articles-dot dot-rejected"></span>
                            مرفوض
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <aside class="articles-priority-card">
            <span>أولوية المحتوى</span>
            <h2>ما الذي يحتاج مراجعة؟</h2>

            <div class="articles-priority-list">
                <a href="{{ $statusUrl('pending_review') }}" class="articles-priority-item warning">
                    <div>
                        <i class="fa-solid fa-hourglass-half"></i>
                        <span>قيد المراجعة</span>
                    </div>
                    <strong>{{ $stats['pending'] }}</strong>
                </a>

                <a href="{{ $statusUrl('draft') }}" class="articles-priority-item blue">
                    <div>
                        <i class="fa-regular fa-pen-to-square"></i>
                        <span>المسودات</span>
                    </div>
                    <strong>{{ $stats['draft'] }}</strong>
                </a>

                <a href="{{ $statusUrl('published') }}" class="articles-priority-item success">
                    <div>
                        <i class="fa-solid fa-circle-check"></i>
                        <span>منشورة</span>
                    </div>
                    <strong>{{ $stats['published'] }}</strong>
                </a>

                <a href="{{ $statusUrl('rejected') }}" class="articles-priority-item danger">
                    <div>
                        <i class="fa-solid fa-circle-xmark"></i>
                        <span>مرفوضة</span>
                    </div>
                    <strong>{{ $stats['rejected'] }}</strong>
                </a>
            </div>
        </aside>
    </section>

    {{-- Clean Article Categories --}}
    <section class="articles-categories-v4">
        <div class="articles-categories-v4__list">
            @foreach ($statusTabs as $statusKey => $tab)
                <a href="{{ $statusUrl($statusKey) }}"
                   class="articles-category-v4 {{ $currentStatus === $statusKey ? 'is-active' : '' }}">
                    <i class="{{ $tab['icon'] }}"></i>
                    <span>{{ $tab['label'] }}</span>
                    <b>{{ $tab['count'] }}</b>
                </a>
            @endforeach
        </div>

        @if($currentStatus !== 'all')
            <a href="{{ $statusUrl('all') }}" class="articles-clear-filter-v4">
                <i class="fa-solid fa-xmark"></i>
                إزالة الفلتر
            </a>
        @endif
    </section>

    {{-- Search & Filters --}}
    <section class="articles-searchbar-v4">
        <form action="{{ route('admin.articles.index') }}"
              method="GET"
              class="articles-searchbar-form-v4"
              id="articlesFilterForm">

            @if ($currentStatus !== 'all')
                <input type="hidden" name="status" value="{{ $currentStatus }}">
            @endif

            @if(request('source'))
                <input type="hidden" name="source" value="{{ request('source') }}">
            @endif

            @if(request('specialty_id'))
                <input type="hidden" name="specialty_id" value="{{ request('specialty_id') }}">
            @endif

            <div class="articles-searchbox-v4">
                <i class="fa-solid fa-magnifying-glass"></i>

                <input
                    type="text"
                    name="search"
                    id="articlesSearchInput"
                    value="{{ request('search') }}"
                    placeholder="ابحثي بعنوان المقال أو اسم الكاتب..."
                    autocomplete="off"
                >

                @if(request('search'))
                    <a href="{{ route('admin.articles.index', request()->except(['page', 'search'])) }}"
                       class="articles-search-clear-v4"
                       title="مسح البحث">
                        <i class="fa-solid fa-xmark"></i>
                    </a>
                @endif
            </div>

            <div class="articles-filter-wrap-v4">
                <button type="button"
                        class="articles-filter-btn-v4 {{ request('source') && request('source') !== 'all' ? 'is-active' : '' }}"
                        id="articlesSourceToggle">
                    <i class="fa-solid fa-user-pen"></i>
                    <span>
                        @if(request('source') === 'admin')
                            الإدارة
                        @elseif(request('source') === 'doctor')
                            الأطباء
                        @elseif(request('source') === 'ai')
                            مسودات AI
                        @else
                            كل المصادر
                        @endif
                    </span>
                    <b><i class="fa-solid fa-chevron-down"></i></b>
                </button>

                <div class="articles-filter-menu-v4" id="articlesSourceMenu">
                    <div class="articles-filter-menu-head-v4">
                        <span>المصدر</span>
                    </div>

                    <div class="articles-filter-list-v4">
                        <a href="{{ route('admin.articles.index', request()->except(['page', 'source'])) }}"
                           class="articles-filter-option-v4 {{ !request('source') || request('source') === 'all' ? 'is-active' : '' }}">
                            <i class="fa-solid fa-layer-group"></i>
                            <span>كل المصادر</span>
                        </a>

                        <a href="{{ route('admin.articles.index', array_merge(request()->except(['page', 'source']), ['source' => 'admin'])) }}"
                           class="articles-filter-option-v4 {{ request('source') === 'admin' ? 'is-active' : '' }}">
                            <i class="fa-solid fa-user-shield"></i>
                            <span>الإدارة</span>
                        </a>

                        <a href="{{ route('admin.articles.index', array_merge(request()->except(['page', 'source']), ['source' => 'doctor'])) }}"
                           class="articles-filter-option-v4 {{ request('source') === 'doctor' ? 'is-active' : '' }}">
                            <i class="fa-solid fa-user-doctor"></i>
                            <span>الأطباء</span>
                        </a>


                        <a href="{{ route('admin.articles.index', array_merge(request()->except(['page', 'source']), ['source' => 'ai'])) }}"
                           class="articles-filter-option-v4 {{ request('source') === 'ai' ? 'is-active' : '' }}">
                            <i class="fa-solid fa-wand-magic-sparkles"></i>
                            <span>مسودات AI</span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="articles-filter-wrap-v4">
                <button type="button"
                        class="articles-filter-btn-v4 {{ request('specialty_id') && request('specialty_id') !== 'all' ? 'is-active' : '' }}"
                        id="articlesSpecialtyToggle">
                    <i class="fa-solid fa-stethoscope"></i>
                    <span>{{ $selectedSpecialty?->name ?? 'كل التخصصات' }}</span>
                    <b><i class="fa-solid fa-chevron-down"></i></b>
                </button>

                <div class="articles-filter-menu-v4" id="articlesSpecialtyMenu">
                    <div class="articles-filter-menu-head-v4">
                        <span>التخصص</span>
                    </div>

                    <div class="articles-filter-list-v4">
                        <a href="{{ route('admin.articles.index', request()->except(['page', 'specialty_id'])) }}"
                           class="articles-filter-option-v4 {{ !request('specialty_id') || request('specialty_id') === 'all' ? 'is-active' : '' }}">
                            <i class="fa-solid fa-layer-group"></i>
                            <span>كل التخصصات</span>
                        </a>

                        @foreach ($specialties as $specialty)
                            <a href="{{ route('admin.articles.index', array_merge(request()->except(['page', 'specialty_id']), ['specialty_id' => $specialty->id])) }}"
                               class="articles-filter-option-v4 {{ (string) request('specialty_id') === (string) $specialty->id ? 'is-active' : '' }}">
                                <i class="fa-solid fa-stethoscope"></i>
                                <span>{{ $specialty->name }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            @if(request('search') || request('source') || request('specialty_id'))
                <a href="{{ route('admin.articles.index', request()->except(['page', 'search', 'source', 'specialty_id'])) }}"
                   class="articles-filter-clear-v4">
                    <i class="fa-solid fa-filter-circle-xmark"></i>
                    <span>مسح</span>
                </a>
            @endif
        </form>
    </section>



    {{-- Featured --}}
    @if ($featuredArticle)
        <article class="magazine-featured-v2">
            <div class="magazine-featured-v2__image">
                @if ($featuredArticle->cover_image)
                    <img src="{{ asset('storage/' . $featuredArticle->cover_image) }}" alt="{{ $featuredArticle->title }}">
                @else
                    <div class="magazine-placeholder-v2">
                        <i class="fa-regular fa-newspaper"></i>
                    </div>
                @endif
            </div>

            <div class="magazine-featured-v2__content">
                <div class="magazine-badges-v2">
                    @if ($featuredArticle->is_featured)
                        <span class="is-featured">
                            <i class="fa-solid fa-star"></i>
                            مقال مميز
                        </span>
                    @endif

                    <span class="status-{{ $featuredArticle->status }}">
                        {{ $featuredArticle->status_label }}
                    </span>

                    <span>{{ $featuredArticle->source_label }}</span>

                    @if ($featuredArticle->generated_by_ai ?? false)
                        <span class="magazine-ai-badge">
                            <i class="fa-solid fa-wand-magic-sparkles"></i>
                            {{ $featuredArticle->article_type_label ?? 'AI' }}
                        </span>
                    @endif
                </div>

                <h2>{{ $featuredArticle->title }}</h2>
                <p>{{ $featuredArticle->excerpt }}</p>

                <div class="magazine-meta-v2">
                    <span><i class="fa-regular fa-user"></i>{{ $featuredArticle->public_author_name }}</span>
                    <span><i class="fa-solid fa-stethoscope"></i>{{ $featuredArticle->specialty?->name ?? 'عام' }}</span>
                    <span><i class="fa-regular fa-clock"></i>{{ $featuredArticle->reading_minutes }} دقائق</span>
                </div>

                <div class="magazine-actions-v2">
                    <button type="button" data-open-modal="editArticleModal-{{ $featuredArticle->id }}">
                        <i class="fa-regular fa-pen-to-square"></i>
                        تعديل
                    </button>

                    @if ($featuredArticle->status === 'pending_review')
                        <button type="button" class="success" data-open-modal="approveArticleModal-{{ $featuredArticle->id }}">
                            <i class="fa-solid fa-check"></i>
                            قبول
                        </button>

                        <button type="button" class="danger" data-open-modal="rejectArticleModal-{{ $featuredArticle->id }}">
                            <i class="fa-solid fa-xmark"></i>
                            رفض
                        </button>
                    @endif

                    <button type="button" class="danger" data-open-modal="deleteArticleModal-{{ $featuredArticle->id }}">
                        <i class="fa-regular fa-trash-can"></i>
                        حذف
                    </button>
                </div>
            </div>
        </article>
    @endif

    {{-- Articles --}}
    @if ($articleItems->count())
        <section class="magazine-grid-v2">
            @foreach ($regularArticles as $article)
                <article class="magazine-card-v2">
                    <div class="magazine-card-v2__image">
                        @if ($article->cover_image)
                            <img src="{{ asset('storage/' . $article->cover_image) }}" alt="{{ $article->title }}">
                        @else
                            <div class="magazine-placeholder-v2">
                                <i class="fa-regular fa-newspaper"></i>
                            </div>
                        @endif

                        <span class="magazine-status-v2 status-{{ $article->status }}">
                            {{ $article->status_label }}
                        </span>
                    </div>

                    <div class="magazine-card-v2__body">
                        <div class="magazine-card-tags-v2">
                            <span class="magazine-category-v2">{{ $article->specialty?->name ?? 'عام' }}</span>

                            @if ($article->generated_by_ai ?? false)
                                <span class="magazine-ai-mini">
                                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                                    AI
                                </span>
                            @endif
                        </div>

                        <h3>{{ $article->title }}</h3>
                        <p>{{ \Illuminate\Support\Str::limit($article->excerpt, 110) }}</p>

                        @if ($article->status === 'rejected' && $article->rejection_reason)
                            <div class="magazine-rejection-v2">
                                <strong>سبب الرفض:</strong>
                                <span>{{ \Illuminate\Support\Str::limit($article->rejection_reason, 80) }}</span>
                            </div>
                        @endif

                        <div class="magazine-card-v2__footer">
                            <div class="magazine-author-v2">
                                <div>{{ mb_substr($article->public_author_name ?? 'أ', 0, 1) }}</div>
                                <span>{{ $article->public_author_name }}</span>
                            </div>

                            <div class="magazine-mini-actions-v2">
                                <button type="button" data-open-modal="editArticleModal-{{ $article->id }}">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </button>

                                @if ($article->status === 'pending_review')
                                    <button type="button" class="success" data-open-modal="approveArticleModal-{{ $article->id }}">
                                        <i class="fa-solid fa-check"></i>
                                    </button>

                                    <button type="button" class="danger" data-open-modal="rejectArticleModal-{{ $article->id }}">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                @endif

                                <button type="button" class="danger" data-open-modal="deleteArticleModal-{{ $article->id }}">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </article>
            @endforeach
        </section>

        <div class="magazine-pagination">
            {{ $articles->links() }}
        </div>
    @else
        <section class="magazine-empty-v2">
            <i class="fa-regular fa-newspaper"></i>
            <h3>لا توجد مقالات بعد</h3>
            <p>ابدئي بإضافة أول مقال طبي داخل منصة اتزان.</p>
            <button type="button" class="magazine-primary-btn-v2" data-open-modal="createArticleModal">
                <i class="fa-solid fa-plus"></i>
                إنشاء مقال
            </button>
        </section>
    @endif
</section>
@endsection

@push('modals')
    {{-- Article Modals --}}
    @foreach ($articleItems as $article)
        @php
            $isThisEditError = $editErrorId === $article->id;
            $isThisRejectError = $rejectErrorId === $article->id;
        @endphp

        {{-- Edit Modal --}}
        <div class="magazine-modal {{ $isThisEditError ? 'is-open' : '' }}" id="editArticleModal-{{ $article->id }}">
            <div class="magazine-modal__backdrop" data-close-modal></div>

            <div class="magazine-modal__dialog">
                <div class="magazine-modal__head">
                    <div>
                        <span>تعديل مقال</span>
                        <h2>{{ $article->title }}</h2>
                    </div>

                    <button type="button" class="magazine-modal__close" data-close-modal>
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                @if ($article->generated_by_ai ?? false)
                    <div class="magazine-ai-notice">
                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                        <div>
                            <strong>هذه مسودة مولدة بالذكاء الاصطناعي</strong>
                            <span>راجعي المعلومات والمصادر والصياغة قبل النشر. {{ $article->medical_disclaimer ?? 'المحتوى تثقيفي ولا يغني عن الطبيب.' }}</span>
                        </div>
                    </div>
                @endif

                <form action="{{ route('admin.articles.update', $article) }}" method="POST" enctype="multipart/form-data" class="magazine-form">
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="_form" value="edit">
                    <input type="hidden" name="article_id" value="{{ $article->id }}">

                    <div class="magazine-form-grid">
                        <div class="magazine-field magazine-field--full">
                            <label>عنوان المقال</label>
                            <input type="text" name="title" value="{{ $isThisEditError ? old('title') : $article->title }}" class="{{ $isThisEditError && $errors->has('title') ? 'is-invalid' : '' }}">
                            @if ($isThisEditError && $errors->has('title'))
                                <small class="magazine-field-error">{{ $errors->first('title') }}</small>
                            @endif
                        </div>

                        <div class="magazine-field">
                            <label>التخصص</label>
                            <select name="specialty_id" class="{{ $isThisEditError && $errors->has('specialty_id') ? 'is-invalid' : '' }}">
                                <option value="">اختاري التخصص</option>
                                @foreach ($specialties as $specialty)
                                    <option value="{{ $specialty->id }}" @selected((string) ($isThisEditError ? old('specialty_id') : $article->specialty_id) === (string) $specialty->id)>
                                        {{ $specialty->name }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($isThisEditError && $errors->has('specialty_id'))
                                <small class="magazine-field-error">{{ $errors->first('specialty_id') }}</small>
                            @endif
                        </div>

                        <div class="magazine-field">
                            <label>حالة المقال</label>
                            <select name="status" class="{{ $isThisEditError && $errors->has('status') ? 'is-invalid' : '' }}">
                                <option value="draft" @selected(($isThisEditError ? old('status') : $article->status) === 'draft')>مسودة</option>
                                <option value="pending_review" @selected(($isThisEditError ? old('status') : $article->status) === 'pending_review')>قيد المراجعة</option>
                                <option value="published" @selected(($isThisEditError ? old('status') : $article->status) === 'published')>منشور</option>
                                <option value="rejected" @selected(($isThisEditError ? old('status') : $article->status) === 'rejected')>مرفوض</option>
                            </select>
                            @if ($isThisEditError && $errors->has('status'))
                                <small class="magazine-field-error">{{ $errors->first('status') }}</small>
                            @endif
                        </div>

                        <div class="magazine-field">
                            <label>اسم الكاتب</label>
                            <input type="text" name="author_name" value="{{ $isThisEditError ? old('author_name') : $article->author_name }}" placeholder="مثال: فريق اتزان">
                        </div>

                        <div class="magazine-field">
                            <label>مدة القراءة</label>
                            <input type="number" name="reading_minutes" min="1" value="{{ $isThisEditError ? old('reading_minutes') : $article->reading_minutes }}" class="{{ $isThisEditError && $errors->has('reading_minutes') ? 'is-invalid' : '' }}">
                            @if ($isThisEditError && $errors->has('reading_minutes'))
                                <small class="magazine-field-error">{{ $errors->first('reading_minutes') }}</small>
                            @endif
                        </div>

                        <div class="magazine-field magazine-field--full">
                            <label>ملخص المقال</label>
                            <textarea name="excerpt" class="{{ $isThisEditError && $errors->has('excerpt') ? 'is-invalid' : '' }}">{{ $isThisEditError ? old('excerpt') : $article->excerpt }}</textarea>
                            @if ($isThisEditError && $errors->has('excerpt'))
                                <small class="magazine-field-error">{{ $errors->first('excerpt') }}</small>
                            @endif
                        </div>

                        <div class="magazine-field magazine-field--full">
                            <label>محتوى المقال</label>
                            <textarea name="content" class="{{ $isThisEditError && $errors->has('content') ? 'is-invalid' : '' }}">{{ $isThisEditError ? old('content') : $article->content }}</textarea>
                            @if ($isThisEditError && $errors->has('content'))
                                <small class="magazine-field-error">{{ $errors->first('content') }}</small>
                            @endif
                        </div>

                        <div class="magazine-field magazine-field--full">
                            <label>صورة الغلاف</label>
                            <input type="file" name="cover_image" accept=".jpg,.jpeg,.png,.webp" class="{{ $isThisEditError && $errors->has('cover_image') ? 'is-invalid' : '' }}">
                            @if ($article->cover_image)
                                <small>الصورة الحالية محفوظة. ارفعي صورة جديدة فقط إذا أردتِ استبدالها.</small>
                            @endif
                            @if ($isThisEditError && $errors->has('cover_image'))
                                <small class="magazine-field-error">{{ $errors->first('cover_image') }}</small>
                            @endif
                        </div>

                        <label class="magazine-switch magazine-field--full">
                            <input type="hidden" name="is_featured" value="0">
                            <input type="checkbox" name="is_featured" value="1" @checked($isThisEditError ? old('is_featured') == '1' : $article->is_featured)>
                            <span></span>
                            <strong>مقال مميز</strong>
                        </label>
                    </div>

                    <div class="magazine-modal__actions">
                        <button type="button" class="magazine-btn magazine-btn--muted" data-close-modal>إلغاء</button>
                        <button type="submit" class="magazine-btn magazine-btn--primary">
                            <i class="fa-solid fa-floppy-disk"></i>
                            حفظ التعديل
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Approve Modal --}}
        <div class="magazine-modal" id="approveArticleModal-{{ $article->id }}">
            <div class="magazine-modal__backdrop" data-close-modal></div>

            <div class="magazine-modal__dialog magazine-modal__dialog--small">
                <div class="magazine-confirm-icon magazine-confirm-icon--success">
                    <i class="fa-solid fa-check"></i>
                </div>

                <div class="magazine-confirm-text">
                    <h2>قبول المقال؟</h2>
                    <p>سيتم نشر مقال <strong>{{ $article->title }}</strong> في صفحة المقالات العامة.</p>
                </div>

                <form action="{{ route('admin.articles.approve', $article) }}" method="POST">
                    @csrf
                    @method('PATCH')

                    <div class="magazine-modal__actions">
                        <button type="button" class="magazine-btn magazine-btn--muted" data-close-modal>إلغاء</button>
                        <button type="submit" class="magazine-btn magazine-btn--primary">
                            <i class="fa-solid fa-check"></i>
                            قبول ونشر
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Reject Modal --}}
        <div class="magazine-modal {{ $isThisRejectError ? 'is-open' : '' }}" id="rejectArticleModal-{{ $article->id }}">
            <div class="magazine-modal__backdrop" data-close-modal></div>

            <div class="magazine-modal__dialog magazine-modal__dialog--small">
                <div class="magazine-confirm-icon magazine-confirm-icon--danger">
                    <i class="fa-solid fa-xmark"></i>
                </div>

                <div class="magazine-confirm-text">
                    <h2>رفض المقال</h2>
                    <p>اكتبي سبب الرفض حتى يبقى واضحًا عند مراجعة المقال لاحقًا.</p>
                </div>

                <form action="{{ route('admin.articles.reject', $article) }}" method="POST" class="magazine-form">
                    @csrf
                    @method('PATCH')

                    <input type="hidden" name="_form" value="reject">
                    <input type="hidden" name="article_id" value="{{ $article->id }}">

                    <div class="magazine-field">
                        <label>سبب الرفض</label>
                        <textarea name="rejection_reason" class="{{ $isThisRejectError && $errors->has('rejection_reason') ? 'is-invalid' : '' }}" placeholder="مثال: المقال يحتاج مراجع أو صياغة أو مراجعة طبية...">{{ $isThisRejectError ? old('rejection_reason') : '' }}</textarea>

                        @if ($isThisRejectError && $errors->has('rejection_reason'))
                            <small class="magazine-field-error">{{ $errors->first('rejection_reason') }}</small>
                        @endif
                    </div>

                    <div class="magazine-modal__actions">
                        <button type="button" class="magazine-btn magazine-btn--muted" data-close-modal>إلغاء</button>
                        <button type="submit" class="magazine-btn magazine-btn--danger">
                            <i class="fa-solid fa-xmark"></i>
                            رفض المقال
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Delete Modal --}}
        <div class="magazine-modal" id="deleteArticleModal-{{ $article->id }}">
            <div class="magazine-modal__backdrop" data-close-modal></div>

            <div class="magazine-modal__dialog magazine-modal__dialog--small">
                <div class="magazine-confirm-icon magazine-confirm-icon--danger">
                    <i class="fa-regular fa-trash-can"></i>
                </div>

                <div class="magazine-confirm-text">
                    <h2>حذف المقال؟</h2>
                    <p>هل أنتِ متأكدة من حذف مقال <strong>{{ $article->title }}</strong>؟</p>
                </div>

                <form action="{{ route('admin.articles.destroy', $article) }}" method="POST">
                    @csrf
                    @method('DELETE')

                    <div class="magazine-modal__actions">
                        <button type="button" class="magazine-btn magazine-btn--muted" data-close-modal>إلغاء</button>
                        <button type="submit" class="magazine-btn magazine-btn--danger">
                            <i class="fa-regular fa-trash-can"></i>
                            حذف
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach

    {{-- AI Draft Modal --}}
    <div class="magazine-modal {{ $isAiError ? 'is-open' : '' }}" id="aiArticleModal">
        <div class="magazine-modal__backdrop" data-close-modal></div>

        <div class="magazine-modal__dialog magazine-modal__dialog--wide">
            <div class="magazine-modal__head">
                <div>
                    <span>مقالات اتزان الذكية</span>
                    <h2>توليد محتوى صحي ذكي وصورة غلاف مناسبة</h2>
                </div>

                <button type="button" class="magazine-modal__close" data-close-modal>
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="magazine-ai-notice">
                <i class="fa-solid fa-shield-heart"></i>
                <div>
                    <strong>المسودة لا تُنشر تلقائيًا</strong>
                    <span>سيتم حفظ المحتوى كمسودة فقط مع صورة غلاف رمزية بدون نص داخل الصورة. راجعي النص والمصادر طبيًا قبل النشر.</span>
                </div>
            </div>

            <form action="{{ route('admin.articles.ai-generate') }}" method="POST" class="magazine-form magazine-ai-form">
                @csrf
                <input type="hidden" name="_form" value="ai_generate">

                <div class="magazine-form-grid">
                    <div class="magazine-field magazine-field--full">
                        <label>فكرة المحتوى أو العنوان</label>
                        <input
                            type="text"
                            name="topic"
                            value="{{ $isAiError ? old('topic') : '' }}"
                            class="{{ $isAiError && $errors->has('topic') ? 'is-invalid' : '' }}"
                            placeholder="مثال: معلومة قصيرة عن شرب الماء، أو ملخص بحثي عن النشاط، أو فكرة يومية لمريض السكري"
                        >
                        @if ($isAiError && $errors->has('topic'))
                            <small class="magazine-field-error">{{ $errors->first('topic') }}</small>
                        @endif
                    </div>

                    <div class="magazine-field">
                        <label>التخصص</label>
                        <select name="specialty_id" class="{{ $isAiError && $errors->has('specialty_id') ? 'is-invalid' : '' }}">
                            <option value="">اختاري التخصص</option>
                            @foreach ($specialties as $specialty)
                                <option value="{{ $specialty->id }}" @selected((string) old('specialty_id') === (string) $specialty->id)>
                                    {{ $specialty->name }}
                                </option>
                            @endforeach
                        </select>
                        @if ($isAiError && $errors->has('specialty_id'))
                            <small class="magazine-field-error">{{ $errors->first('specialty_id') }}</small>
                        @endif
                    </div>

                    <div class="magazine-field">
                        <label>نوع المسودة</label>
                        <select name="article_type" class="{{ $isAiError && $errors->has('article_type') ? 'is-invalid' : '' }}">
                            <option value="health_article" @selected(old('article_type', 'health_article') === 'health_article')>مقال صحي تثقيفي</option>
                            <option value="research_summary" @selected(old('article_type') === 'research_summary')>ملخص بحثي مبسط</option>
                            <option value="quick_tip" @selected(old('article_type') === 'quick_tip')>نصائح سريعة</option>
                            <option value="general_info" @selected(old('article_type') === 'general_info')>معلومة صحية عامة</option>
                            <option value="wellness_idea" @selected(old('article_type') === 'wellness_idea')>فكرة صحية يومية</option>
                            <option value="health_wisdom" @selected(old('article_type') === 'health_wisdom')>حكمة صحية من اتزان</option>
                            <option value="motivational_quote" @selected(old('article_type') === 'motivational_quote')>رسالة تحفيزية من اتزان</option>
                        </select>
                        @if ($isAiError && $errors->has('article_type'))
                            <small class="magazine-field-error">{{ $errors->first('article_type') }}</small>
                        @endif
                    </div>

                    <div class="magazine-field">
                        <label>الجمهور المستهدف</label>
                        <select name="audience" class="{{ $isAiError && $errors->has('audience') ? 'is-invalid' : '' }}">
                            <option value="patient" @selected(old('audience', 'patient') === 'patient')>مريض عام</option>
                            <option value="weight_loss" @selected(old('audience') === 'weight_loss')>خسارة وزن</option>
                            <option value="weight_gain" @selected(old('audience') === 'weight_gain')>زيادة وزن صحية</option>
                            <option value="diabetes" @selected(old('audience') === 'diabetes')>مريض سكري</option>
                            <option value="heart_health" @selected(old('audience') === 'heart_health')>صحة القلب</option>
                            <option value="low_activity" @selected(old('audience') === 'low_activity')>نشاط قليل</option>
                            <option value="sleep_health" @selected(old('audience') === 'sleep_health')>النوم والعادات المسائية</option>
                            <option value="hydration" @selected(old('audience') === 'hydration')>شرب الماء</option>
                            <option value="general" @selected(old('audience') === 'general')>عام</option>
                        </select>
                        @if ($isAiError && $errors->has('audience'))
                            <small class="magazine-field-error">{{ $errors->first('audience') }}</small>
                        @endif
                    </div>

                    <div class="magazine-field">
                        <label>طول المقال</label>
                        <select name="length" class="{{ $isAiError && $errors->has('length') ? 'is-invalid' : '' }}">
                            <option value="short" @selected(old('length') === 'short')>قصير</option>
                            <option value="medium" @selected(old('length', 'medium') === 'medium')>متوسط</option>
                            <option value="long" @selected(old('length') === 'long')>طويل</option>
                        </select>
                        @if ($isAiError && $errors->has('length'))
                            <small class="magazine-field-error">{{ $errors->first('length') }}</small>
                        @endif
                    </div>

                    <div class="magazine-field">
                        <label>اسم المصدر/البحث اختياري</label>
                        <input type="text" name="source_title" value="{{ $isAiError ? old('source_title') : '' }}" placeholder="مثال: إرشادات منظمة الصحة العالمية حول النشاط البدني">
                    </div>

                    <div class="magazine-field">
                        <label>سنة المصدر اختياري</label>
                        <input type="number" name="source_year" min="1950" max="{{ now()->year + 1 }}" value="{{ $isAiError ? old('source_year') : '' }}" placeholder="مثال: 2024">
                    </div>

                    <div class="magazine-field magazine-field--full">
                        <label>رابط المصدر اختياري</label>
                        <input
                            type="url"
                            name="source_url"
                            value="{{ $isAiError ? old('source_url') : '' }}"
                            class="{{ $isAiError && $errors->has('source_url') ? 'is-invalid' : '' }}"
                            placeholder="https://..."
                        >
                        @if ($isAiError && $errors->has('source_url'))
                            <small class="magazine-field-error">{{ $errors->first('source_url') }}</small>
                        @endif
                    </div>

                    <div class="magazine-field magazine-field--full">
                        <label>ملاحظات إضافية للـ AI</label>
                        <textarea name="extra_notes" placeholder="مثال: اجعلي المقال بسيطًا، وفيه خطوات عملية، وتجنبي المصطلحات الطبية الصعبة...">{{ $isAiError ? old('extra_notes') : '' }}</textarea>
                    </div>
                </div>

                <div class="magazine-ai-rules">
                    <span><i class="fa-solid fa-circle-check"></i> لا نشر تلقائي</span>
                    <span><i class="fa-solid fa-circle-check"></i> بدون تشخيص أو أدوية</span>
                    <span><i class="fa-solid fa-circle-check"></i> لا اقتباسات منسوبة بدون مصدر</span>
                    <span><i class="fa-solid fa-circle-check"></i> صورة غلاف صحية بدون كتابة داخل الصورة</span>
                </div>

                <div class="magazine-modal__actions">
                    <button type="button" class="magazine-btn magazine-btn--muted" data-close-modal>إلغاء</button>
                    <button type="submit" class="magazine-btn magazine-btn--primary">
                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                        توليد وحفظ كمسودة
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Create Modal --}}
    <div class="magazine-modal {{ $isCreateError ? 'is-open' : '' }}" id="createArticleModal">
        <div class="magazine-modal__backdrop" data-close-modal></div>

        <div class="magazine-modal__dialog">
            <div class="magazine-modal__head">
                <div>
                    <span>مقال جديد</span>
                    <h2>إضافة مقال</h2>
                </div>

                <button type="button" class="magazine-modal__close" data-close-modal>
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form action="{{ route('admin.articles.store') }}" method="POST" enctype="multipart/form-data" class="magazine-form">
                @csrf
                <input type="hidden" name="_form" value="create">

                <div class="magazine-form-grid">
                    <div class="magazine-field magazine-field--full">
                        <label>عنوان المقال</label>
                        <input type="text" name="title" value="{{ $isCreateError ? old('title') : '' }}" class="{{ $isCreateError && $errors->has('title') ? 'is-invalid' : '' }}" placeholder="مثال: كيف تحافظ على صحة القلب يوميًا؟">
                        @if ($isCreateError && $errors->has('title'))
                            <small class="magazine-field-error">{{ $errors->first('title') }}</small>
                        @endif
                    </div>

                    <div class="magazine-field">
                        <label>التخصص</label>
                        <select name="specialty_id" class="{{ $isCreateError && $errors->has('specialty_id') ? 'is-invalid' : '' }}">
                            <option value="">اختاري التخصص</option>
                            @foreach ($specialties as $specialty)
                                <option value="{{ $specialty->id }}" @selected((string) old('specialty_id') === (string) $specialty->id)>
                                    {{ $specialty->name }}
                                </option>
                            @endforeach
                        </select>
                        @if ($isCreateError && $errors->has('specialty_id'))
                            <small class="magazine-field-error">{{ $errors->first('specialty_id') }}</small>
                        @endif
                    </div>

                    <div class="magazine-field">
                        <label>حالة المقال</label>
                        <select name="status" class="{{ $isCreateError && $errors->has('status') ? 'is-invalid' : '' }}">
                            <option value="draft" @selected(old('status') === 'draft')>مسودة</option>
                            <option value="published" @selected(old('status') === 'published')>منشور</option>
                        </select>
                        @if ($isCreateError && $errors->has('status'))
                            <small class="magazine-field-error">{{ $errors->first('status') }}</small>
                        @endif
                    </div>

                    <div class="magazine-field">
                        <label>اسم الكاتب</label>
                        <input type="text" name="author_name" value="{{ $isCreateError ? old('author_name') : '' }}" placeholder="اتركيه فارغًا ليظهر اسم الأدمن">
                    </div>

                    <div class="magazine-field">
                        <label>مدة القراءة</label>
                        <input type="number" name="reading_minutes" min="1" value="{{ $isCreateError ? old('reading_minutes') : '' }}" class="{{ $isCreateError && $errors->has('reading_minutes') ? 'is-invalid' : '' }}" placeholder="مثال: 5">
                        @if ($isCreateError && $errors->has('reading_minutes'))
                            <small class="magazine-field-error">{{ $errors->first('reading_minutes') }}</small>
                        @endif
                    </div>

                    <div class="magazine-field magazine-field--full">
                        <label>ملخص المقال</label>
                        <textarea name="excerpt" class="{{ $isCreateError && $errors->has('excerpt') ? 'is-invalid' : '' }}" placeholder="ملخص قصير يظهر في كرت المقال...">{{ $isCreateError ? old('excerpt') : '' }}</textarea>
                        @if ($isCreateError && $errors->has('excerpt'))
                            <small class="magazine-field-error">{{ $errors->first('excerpt') }}</small>
                        @endif
                    </div>

                    <div class="magazine-field magazine-field--full">
                        <label>محتوى المقال</label>
                        <textarea name="content" class="{{ $isCreateError && $errors->has('content') ? 'is-invalid' : '' }}" placeholder="اكتبي محتوى المقال كاملًا هنا...">{{ $isCreateError ? old('content') : '' }}</textarea>
                        @if ($isCreateError && $errors->has('content'))
                            <small class="magazine-field-error">{{ $errors->first('content') }}</small>
                        @endif
                    </div>

                    <div class="magazine-field magazine-field--full">
                        <label>صورة الغلاف</label>
                        <input type="file" name="cover_image" accept=".jpg,.jpeg,.png,.webp" class="{{ $isCreateError && $errors->has('cover_image') ? 'is-invalid' : '' }}">
                        @if ($isCreateError && $errors->has('cover_image'))
                            <small class="magazine-field-error">{{ $errors->first('cover_image') }}</small>
                        @endif
                    </div>

                    <label class="magazine-switch magazine-field--full">
                        <input type="hidden" name="is_featured" value="0">
                        <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured') == '1')>
                        <span></span>
                        <strong>مقال مميز</strong>
                    </label>
                </div>

                <div class="magazine-modal__actions">
                    <button type="button" class="magazine-btn magazine-btn--muted" data-close-modal>إلغاء</button>
                    <button type="submit" class="magazine-btn magazine-btn--primary">
                        <i class="fa-solid fa-plus"></i>
                        إضافة المقال
                    </button>
                </div>
            </form>
        </div>
    </div>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const openButtons = document.querySelectorAll('[data-open-modal]');
    const closeButtons = document.querySelectorAll('[data-close-modal]');

    const articlesForm = document.getElementById('articlesFilterForm');
    const articlesSearchInput = document.getElementById('articlesSearchInput');

    const sourceToggle = document.getElementById('articlesSourceToggle');
    const sourceMenu = document.getElementById('articlesSourceMenu');

    const specialtyToggle = document.getElementById('articlesSpecialtyToggle');
    const specialtyMenu = document.getElementById('articlesSpecialtyMenu');

    let articlesSearchTimer = null;

    function openModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;

        modal.classList.add('is-open');
        document.body.classList.add('modal-open');
    }

    function closeModal(button) {
        const modal = button.closest('.magazine-modal');
        if (!modal) return;

        modal.classList.remove('is-open');

        if (!document.querySelector('.magazine-modal.is-open')) {
            document.body.classList.remove('modal-open');
        }
    }

    function closeArticleMenus() {
        sourceMenu?.classList.remove('is-open');
        specialtyMenu?.classList.remove('is-open');
    }

    openButtons.forEach((button) => {
        button.addEventListener('click', function () {
            openModal(this.dataset.openModal);
        });
    });

    closeButtons.forEach((button) => {
        button.addEventListener('click', function () {
            closeModal(this);
        });
    });

    if (articlesForm && articlesSearchInput) {
        articlesSearchInput.addEventListener('input', function () {
            clearTimeout(articlesSearchTimer);

            articlesSearchTimer = setTimeout(() => {
                articlesForm.submit();
            }, 450);
        });
    }

    sourceToggle?.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();

        specialtyMenu?.classList.remove('is-open');
        sourceMenu?.classList.toggle('is-open');
    });

    specialtyToggle?.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();

        sourceMenu?.classList.remove('is-open');
        specialtyMenu?.classList.toggle('is-open');
    });

    document.addEventListener('click', function (event) {
        if (!event.target.closest('.articles-filter-wrap-v4') && !event.target.closest('.magazine-modal')) {
            closeArticleMenus();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            document.querySelectorAll('.magazine-modal.is-open').forEach((modal) => {
                modal.classList.remove('is-open');
            });

            closeArticleMenus();
            document.body.classList.remove('modal-open');
        }
    });

    if (document.querySelector('.magazine-modal.is-open')) {
        document.body.classList.add('modal-open');
    }

    document.querySelectorAll('.is-invalid').forEach(input => {
        input.addEventListener('input', () => {
            input.classList.remove('is-invalid');
        });

        input.addEventListener('change', () => {
            input.classList.remove('is-invalid');
        });
    });

    const articlesStats = {
        total: {{ max((int) $stats['total'], 1) }},
        published: {{ (int) $stats['published'] }},
        pending: {{ (int) $stats['pending'] }},
        draft: {{ (int) $stats['draft'] }},
        rejected: {{ (int) $stats['rejected'] }}
    };

    const publishedPercent = Math.round((articlesStats.published / articlesStats.total) * 100);
    const pendingPercent = Math.round((articlesStats.pending / articlesStats.total) * 100);
    const draftPercent = Math.round((articlesStats.draft / articlesStats.total) * 100);
    const rejectedPercent = Math.max(100 - publishedPercent - pendingPercent - draftPercent, 0);

    const chartData = [
        {
            label: 'منشور',
            title: 'المقالات المنشورة',
            count: articlesStats.published,
            percent: publishedPercent,
            color: '#1D9E75',
            desc: 'المقالات التي تظهر حاليًا للمستخدمين داخل الموقع.'
        },
        {
            label: 'مراجعة',
            title: 'مقالات قيد المراجعة',
            count: articlesStats.pending,
            percent: pendingPercent,
            color: '#F5A623',
            desc: 'مقالات تحتاج قرار قبول أو رفض من الإدارة.'
        },
        {
            label: 'مسودات',
            title: 'مسودات المقالات',
            count: articlesStats.draft,
            percent: draftPercent,
            color: '#38b8f2',
            desc: 'مقالات محفوظة ولم يتم نشرها بعد.'
        },
        {
            label: 'مرفوض',
            title: 'المقالات المرفوضة',
            count: articlesStats.rejected,
            percent: rejectedPercent,
            color: '#ef4444',
            desc: 'مقالات تم رفضها مع إمكانية مراجعة سبب الرفض.'
        }
    ];

    const segGroup = document.getElementById('articlesSegGroup');
    if (!segGroup) return;

    const cx = 100;
    const cy = 100;
    const r = 72;
    let startPercent = 0;
    const gap = 1.2;
    const segments = [];

    chartData.forEach((item, index) => {
        const value = Math.max(item.percent, 0);

        if (value <= 0) {
            segments.push({ el: null });
            return;
        }

        const visibleValue = Math.max(value - gap, 0.8);

        const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        circle.setAttribute('cx', cx);
        circle.setAttribute('cy', cy);
        circle.setAttribute('r', r);
        circle.setAttribute('pathLength', '100');
        circle.setAttribute('class', 'articles-donut-seg');
        circle.setAttribute('fill', 'none');
        circle.setAttribute('stroke', item.color);
        circle.setAttribute('stroke-width', '16');
        circle.setAttribute('stroke-dasharray', `${visibleValue} ${100 - visibleValue}`);
        circle.setAttribute('stroke-dashoffset', `${25 - startPercent}`);
        circle.addEventListener('click', () => selectSegment(index));

        segGroup.appendChild(circle);
        segments.push({ el: circle });
        startPercent += value;
    });

    function selectSegment(index) {
        const item = chartData[index];
        if (!item) return;

        segments.forEach((segment, i) => {
            if (!segment.el) return;

            if (i === index) {
                segment.el.setAttribute('stroke-width', '18');
                segment.el.style.opacity = '1';
                segment.el.style.filter = `drop-shadow(0 0 12px ${item.color})`;
            } else {
                segment.el.setAttribute('stroke-width', '16');
                segment.el.style.opacity = '.85';
                segment.el.style.filter = 'none';
            }
        });

        document.getElementById('articlesCenterPercent').textContent = item.percent + '%';
        document.getElementById('articlesCenterLabel').textContent = item.label;
        document.getElementById('articlesInfoTitle').textContent = item.title;
        document.getElementById('articlesInfoDesc').textContent = item.desc;
        document.getElementById('articlesInfoCount').textContent = item.count;
        document.getElementById('articlesInfoCount').style.color = item.color;
        document.getElementById('articlesInfoPercent').textContent = item.percent + '%';

        document.querySelectorAll('.articles-legend-btn').forEach((btn, i) => {
            btn.classList.toggle('active', i === index);
        });
    }

    document.querySelectorAll('.articles-legend-btn').forEach((button, index) => {
        button.addEventListener('click', () => selectSegment(index));
    });

    const firstVisible = chartData.findIndex(item => item.count > 0);
    selectSegment(firstVisible >= 0 ? firstVisible : 0);
});
</script>
@endpush
