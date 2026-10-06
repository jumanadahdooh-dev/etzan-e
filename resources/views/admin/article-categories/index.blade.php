@extends('layouts.admin')

@section('title', 'تصنيفات المقالات | اتزان')

@push('styles')
<link rel="stylesheet" href="{{ asset('front/css/admin/admin-specialties.css') }}">
@endpush

@section('content')
@php
    $isCreateError = $errors->any() && old('_form') === 'create';
    $editErrorId = old('_form') === 'edit' ? (int) old('category_id') : null;

    $activeCount = $categories->where('is_active', true)->count();
    $inactiveCount = $categories->where('is_active', false)->count();
@endphp

<section class="admin-specialties-page specialties-v2-page">

    @if (session('success'))
        <div class="specialties-toast specialties-toast--success">
            <i class="fa-solid fa-circle-check"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->has('delete'))
        <div class="specialties-toast specialties-toast--danger">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span>{{ $errors->first('delete') }}</span>
        </div>
    @endif

    {{-- Hero --}}
    <section class="specialties-hero-v2 specialties-hero-v2--compact">
        <div>
            <span class="specialties-kicker-v2">
                <i class="fa-regular fa-folder"></i>
                إدارة المحتوى
            </span>

            <h1>تصنيفات المقالات</h1>

            <p>
                أضيفي التصنيفات، رتبي ظهورها، وأخفي غير المستخدم منها.
            </p>
        </div>

        <button type="button" class="specialties-primary-btn-v2" data-open-modal="createCategoryModal">
            <i class="fa-solid fa-plus"></i>
            <span>إضافة تصنيف</span>
        </button>
    </section>

    {{-- Insights --}}
    <section class="specialties-insights-v2 specialties-insights-v2--compact">
        <article class="specialties-insight-card is-total">
            <div class="specialties-insight-icon">
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <div>
                <span>إجمالي التصنيفات</span>
                <strong>{{ $categories->count() }}</strong>
            </div>
        </article>

        <article class="specialties-insight-card is-active">
            <div class="specialties-insight-icon">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div>
                <span>تصنيفات فعالة</span>
                <strong>{{ $activeCount }}</strong>
            </div>
        </article>

        <article class="specialties-insight-card is-hidden">
            <div class="specialties-insight-icon">
                <i class="fa-solid fa-eye-slash"></i>
            </div>
            <div>
                <span>تصنيفات مخفية</span>
                <strong>{{ $inactiveCount }}</strong>
            </div>
        </article>

        <article class="specialties-insight-card is-latest">
            <div class="specialties-insight-icon">
                <i class="fa-regular fa-newspaper"></i>
            </div>
            <div>
                <span>إجمالي المقالات المصنفة</span>
                <strong>{{ $categories->sum('articles_count') }}</strong>
            </div>
        </article>
    </section>

    {{-- Main Card --}}
    <section class="specialties-board-v2 specialties-board-v2--v4">
        <div class="specialties-board-head-v2">
            <div>
                <span>القائمة</span>
                <h2>كل التصنيفات</h2>
                <p>مرتبة حسب ترتيب الظهور ثم الاسم.</p>
            </div>

            <div class="specialties-count-pill-v2">
                <i class="fa-solid fa-list-check"></i>
                <span>{{ $categories->count() }}</span>
                تصنيف
            </div>
        </div>

        @if ($categories->count())
            <div class="specialties-grid-v2">
                @foreach ($categories as $category)
                    <article class="specialty-card-v2 {{ !$category->is_active ? 'is-muted' : '' }}">
                        <div class="specialty-card-v2__top">
                            <div class="specialty-icon-v2">
                                <i class="{{ $category->icon ?: 'fa-regular fa-folder' }}"></i>
                            </div>

                            @if ($category->is_active)
                                <span class="specialty-status-v2 is-active">فعّال</span>
                            @else
                                <span class="specialty-status-v2 is-inactive">مخفي</span>
                            @endif
                        </div>

                        <div class="specialty-card-v2__body">
                            <h3>{{ $category->name }}</h3>
                            <p>{{ $category->slug }}</p>
                        </div>

                        <div class="specialty-card-v2__meta">
                            <span>
                                <i class="fa-solid fa-arrow-down-1-9"></i>
                                الترتيب: {{ $category->sort_order }}
                            </span>
                            <span>
                                <i class="fa-regular fa-newspaper"></i>
                                {{ $category->articles_count }} مقال
                            </span>
                        </div>

                        <div class="specialty-card-v2__actions">
                            <button type="button"
                                    class="specialty-action-v2"
                                    title="تعديل"
                                    data-open-modal="editCategoryModal-{{ $category->id }}">
                                <i class="fa-regular fa-pen-to-square"></i>
                            </button>

                            <button type="button"
                                    class="specialty-action-v2 is-danger"
                                    title="{{ $category->articles_count ? 'لا يمكن حذف تصنيف يحتوي على مقالات' : 'حذف' }}"
                                    data-open-modal="deleteCategoryModal-{{ $category->id }}"
                                    @disabled($category->articles_count > 0)>
                                <i class="fa-regular fa-trash-can"></i>
                            </button>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="specialties-empty-v2">
                <div>
                    <i class="fa-regular fa-folder-open"></i>
                </div>
                <h3>لا توجد تصنيفات بعد</h3>
                <p>اضغطي على زر إضافة تصنيف لبدء تنظيم المقالات.</p>

                <button type="button" class="specialties-primary-btn-v2" data-open-modal="createCategoryModal">
                    <i class="fa-solid fa-plus"></i>
                    إضافة أول تصنيف
                </button>
            </div>
        @endif
    </section>

</section>
@endsection

@push('modals')
    @foreach ($categories as $category)
        @php
            $isThisEditError = $editErrorId === $category->id;
        @endphp

        {{-- Edit Modal --}}
        <div class="admin-specialty-modal {{ $isThisEditError ? 'is-open' : '' }}"
             id="editCategoryModal-{{ $category->id }}">
            <div class="admin-specialty-modal__backdrop" data-close-modal></div>

            <div class="admin-specialty-modal__dialog">
                <div class="admin-specialty-modal__head">
                    <div>
                        <span>تعديل تصنيف</span>
                        <h2>{{ $category->name }}</h2>
                    </div>

                    <button type="button" class="admin-specialty-modal__close" data-close-modal>
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <form action="{{ route('admin.article-categories.update', $category) }}"
                      method="POST"
                      class="admin-specialty-form">
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="_form" value="edit">
                    <input type="hidden" name="category_id" value="{{ $category->id }}">

                    @include('admin.article-categories.partials.fields', [
                        'values' => $isThisEditError ? null : $category,
                        'showErrors' => $isThisEditError,
                    ])

                    <div class="admin-specialty-modal__actions">
                        <button type="button" class="admin-specialty-cancel" data-close-modal>
                            إلغاء
                        </button>

                        <button type="submit" class="admin-specialty-save">
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span>حفظ التعديل</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Delete Modal --}}
        @if (! $category->articles_count)
            <div class="admin-specialty-modal" id="deleteCategoryModal-{{ $category->id }}">
                <div class="admin-specialty-modal__backdrop" data-close-modal></div>

                <div class="admin-specialty-modal__dialog admin-specialty-modal__dialog--small">
                    <div class="admin-delete-modal-icon">
                        <i class="fa-regular fa-trash-can"></i>
                    </div>

                    <div class="admin-delete-modal-text">
                        <h2>حذف التصنيف؟</h2>
                        <p>
                            هل أنتِ متأكدة من حذف تصنيف
                            <strong>{{ $category->name }}</strong>؟
                            لا يمكن التراجع عن هذه العملية.
                        </p>
                    </div>

                    <form action="{{ route('admin.article-categories.destroy', $category) }}" method="POST">
                        @csrf
                        @method('DELETE')

                        <div class="admin-specialty-modal__actions">
                            <button type="button" class="admin-specialty-cancel" data-close-modal>
                                إلغاء
                            </button>

                            <button type="submit" class="admin-specialty-delete-confirm">
                                <i class="fa-regular fa-trash-can"></i>
                                <span>نعم، حذف</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @endforeach

    {{-- Create Modal --}}
    <div class="admin-specialty-modal {{ $isCreateError ? 'is-open' : '' }}" id="createCategoryModal">
        <div class="admin-specialty-modal__backdrop" data-close-modal></div>

        <div class="admin-specialty-modal__dialog">
            <div class="admin-specialty-modal__head">
                <div>
                    <span>تصنيف جديد</span>
                    <h2>إضافة تصنيف مقالات</h2>
                </div>

                <button type="button" class="admin-specialty-modal__close" data-close-modal>
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form action="{{ route('admin.article-categories.store') }}" method="POST" class="admin-specialty-form">
                @csrf

                <input type="hidden" name="_form" value="create">

                @include('admin.article-categories.partials.fields', [
                    'values' => null,
                    'showErrors' => $isCreateError,
                ])

                <div class="admin-specialty-modal__actions">
                    <button type="button" class="admin-specialty-cancel" data-close-modal>
                        إلغاء
                    </button>

                    <button type="submit" class="admin-specialty-save">
                        <i class="fa-solid fa-plus"></i>
                        <span>إضافة التصنيف</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-open-modal]').forEach((button) => {
        button.addEventListener('click', function () {
            const modal = document.getElementById(this.dataset.openModal);
            if (!modal) return;

            modal.classList.add('is-open');
            document.body.classList.add('modal-open');
        });
    });

    document.querySelectorAll('[data-close-modal]').forEach((button) => {
        button.addEventListener('click', function () {
            this.closest('.admin-specialty-modal')?.classList.remove('is-open');

            if (!document.querySelector('.admin-specialty-modal.is-open')) {
                document.body.classList.remove('modal-open');
            }
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            document.querySelectorAll('.admin-specialty-modal.is-open').forEach((modal) => {
                modal.classList.remove('is-open');
            });

            document.body.classList.remove('modal-open');
        }
    });

    if (document.querySelector('.admin-specialty-modal.is-open')) {
        document.body.classList.add('modal-open');
    }
});
</script>
@endpush
