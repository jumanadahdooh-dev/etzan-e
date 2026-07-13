@extends('layouts.public')

@section('title', 'المقالات التوعوية | اتزان')

@push('styles')
<link rel="stylesheet" href="{{ asset('front/css/articles.css') }}">
@endpush

@section('content')

<main class="articles-page">

  <!-- Hero -->
  <section class="articles-hero">
    <div class="container">
      <div class="articles-hero__box">
        <span class="articles-badge">
          <i class="fa-regular fa-newspaper"></i>
          مكتبة اتزان التوعوية
        </span>

        <h1>المقالات التوعوية</h1>

        <p>
          محتوى صحي واضح ومريح للقراءة يساعدك على فهم التغذية، الأمراض المزمنة،
          ونمط الحياة المتوازن بطريقة بسيطة وعصرية.
        </p>

        <form class="articles-search" action="{{ route('articles') }}" method="GET">
          @if(($selectedCategory ?? 'all') !== 'all')
            <input type="hidden" name="category" value="{{ $selectedCategory }}">
          @endif

          <i class="fa-solid fa-magnifying-glass"></i>

          <input
            type="text"
            id="articleSearch"
            name="q"
            value="{{ $search ?? '' }}"
            placeholder="ابحث عن مقال أو موضوع صحي..."
          />
        </form>
      </div>
    </div>
  </section>

  <!-- Categories -->
  <section class="articles-topbar">
    <div class="container">
      <div class="articles-topbar__head">
        <h2>التصنيفات</h2>
        <p>اختر التصنيف المناسب أو ابحث للوصول السريع للمقال.</p>
      </div>

      <div class="articles-categories" id="categoryFilters">
        <a
          href="{{ route('articles', ['q' => $search ?? '']) }}"
          class="category-chip {{ ($selectedCategory ?? 'all') === 'all' ? 'is-active' : '' }}"
          data-category="all"
        >
          الكل
        </a>

        @foreach($categories as $category)
          <a
            href="{{ route('articles', ['category' => $category->id, 'q' => $search ?? '']) }}"
            class="category-chip {{ (string)($selectedCategory ?? 'all') === (string)$category->id ? 'is-active' : '' }}"
            data-category="{{ $category->id }}"
          >
            {{ $category->name }}
          </a>
        @endforeach
      </div>
    </div>
  </section>

  <!-- Featured Article -->
  @if($featuredArticle)
    <section class="articles-featured-section">
      <div class="container">
        <article
          class="articles-featured article-item"
          data-category="{{ $featuredArticle->specialty_id ?? 'general' }}"
          data-title="{{ $featuredArticle->title }}"
          data-excerpt="{{ $featuredArticle->excerpt }}"
        >
          <div class="articles-featured__cover">
            @if($featuredArticle->cover_image)
              <img
                src="{{ $featuredArticle->cover_image_url }}"
                alt="{{ $featuredArticle->title }}"
                class="articles-featured__img"
              >
            @else
              <div class="articles-featured__label">مقال مميز</div>

              <div class="articles-featured__icon">
                <i class="{{ $featuredArticle->icon ?: optional($featuredArticle->specialty)->icon ?: 'fa-regular fa-newspaper' }}"></i>
              </div>
            @endif
          </div>

          <div class="articles-featured__content">
            <span class="article-tag">
              {{ optional($featuredArticle->specialty)->name ?? 'عام' }}
            </span>

            <h3>{{ $featuredArticle->title }}</h3>

            <p>
              {{ $featuredArticle->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($featuredArticle->content), 180) }}
            </p>

            <div class="article-meta">
              <span>
                <i class="fa-regular fa-clock"></i>
                {{ $featuredArticle->reading_time_label }}
              </span>

              <span>
                <i class="fa-regular fa-calendar"></i>
                {{ $featuredArticle->publish_date_label }}
              </span>
            </div>

            <a href="{{ route('articles.show', $featuredArticle->slug) }}" class="article-read-btn">
              قراءة المقال
              <i class="fa-solid fa-arrow-left-long"></i>
            </a>
          </div>
        </article>
      </div>
    </section>
  @endif

  <!-- Articles List -->
  <section class="articles-list-section">
    <div class="container">

      <div class="articles-list-head">
        <h2>جميع المقالات</h2>
      </div>

      @if($articles->count())
        <div class="articles-list" id="articlesList">

          @foreach($articles as $article)
            <article
              class="article-row article-item"
              data-category="{{ $article->specialty_id ?? 'general' }}"
              data-title="{{ $article->title }}"
              data-excerpt="{{ $article->excerpt }}"
            >
              <div class="article-row__cover">
                @if($article->cover_image)
                  <img
                    src="{{ $article->cover_image_url }}"
                    alt="{{ $article->title }}"
                    class="article-row__img"
                  >
                @else
                  <i class="{{ $article->icon ?: optional($article->specialty)->icon ?: 'fa-regular fa-newspaper' }}"></i>
                @endif
              </div>

              <div class="article-row__content">
                <span class="article-tag">
                  {{ optional($article->specialty)->name ?? 'عام' }}
                </span>

                <h3>{{ $article->title }}</h3>

                <p>
                  {{ $article->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($article->content), 120) }}
                </p>

                <div class="article-meta">
                  <span>
                    <i class="fa-regular fa-clock"></i>
                    {{ $article->reading_time_label }}
                  </span>

                  <span>
                    <i class="fa-regular fa-calendar"></i>
                    {{ $article->publish_date_label }}
                  </span>
                </div>
              </div>

              <a href="{{ route('articles.show', $article->slug) }}" class="article-row__link">
                قراءة المقال
              </a>
            </article>
          @endforeach

        </div>

        <div class="articles-pagination">
          {{ $articles->links() }}
        </div>
      @else
        <div class="articles-empty" id="articlesEmptyState" style="display:block;">
          <div class="articles-empty__icon">
            <i class="fa-regular fa-folder-open"></i>
          </div>
          <h3>لا توجد نتائج مطابقة</h3>
          <p>جرّب البحث بكلمات مختلفة أو اختر تصنيفًا آخر.</p>
        </div>
      @endif

    </div>
  </section>

</main>

@endsection

@push('scripts')
<script src="{{ asset('front/js/articles.js') }}"></script>
@endpush
