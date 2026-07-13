@extends('layouts.admin')

@section('title', 'التخصصات الطبية | اتزان')

@push('styles')
<link rel="stylesheet" href="{{ asset('front/css/admin/admin-specialties.css') }}">
@endpush

@section('content')
@php
    $isCreateError = $errors->any() && old('_form') === 'create';
    $editErrorId = old('_form') === 'edit' ? (int) old('specialty_id') : null;

    $activeCount = $specialties->where('is_active', true)->count();
    $inactiveCount = $specialties->where('is_active', false)->count();
    $latestSpecialty = $specialties->sortByDesc('created_at')->first();

    $resolveSpecialtyIcon = function ($specialty) {
        $storedIcon = trim((string) ($specialty->icon ?? ''));

        if ($storedIcon && $storedIcon !== 'fa-solid fa-stethoscope') {
            return $storedIcon;
        }

        $name = mb_strtolower($specialty->name ?? '');

        $icons = [
            'قلب' => 'fa-solid fa-heart-pulse',
            'شرايين' => 'fa-solid fa-heart-pulse',
            'جلد' => 'fa-solid fa-hand-sparkles',
            'بشرة' => 'fa-solid fa-hand-sparkles',
            'أسنان' => 'fa-solid fa-tooth',
            'اسنان' => 'fa-solid fa-tooth',
            'عيون' => 'fa-regular fa-eye',
            'عين' => 'fa-regular fa-eye',
            'أطفال' => 'fa-solid fa-child-reaching',
            'اطفال' => 'fa-solid fa-child-reaching',
            'نساء' => 'fa-solid fa-venus',
            'ولادة' => 'fa-solid fa-baby',
            'عظام' => 'fa-solid fa-bone',
            'مخ' => 'fa-solid fa-brain',
            'أعصاب' => 'fa-solid fa-brain',
            'اعصاب' => 'fa-solid fa-brain',
            'نفس' => 'fa-solid fa-head-side-virus',
            'نفسي' => 'fa-solid fa-head-side-virus',
            'تغذية' => 'fa-solid fa-apple-whole',
            'غذاء' => 'fa-solid fa-apple-whole',
            'باطنية' => 'fa-solid fa-lungs',
            'صدر' => 'fa-solid fa-lungs',
            'تنفس' => 'fa-solid fa-lungs',
            'كلى' => 'fa-solid fa-droplet',
            'مسالك' => 'fa-solid fa-droplet',
            'أنف' => 'fa-solid fa-ear-listen',
            'اذن' => 'fa-solid fa-ear-listen',
            'أذن' => 'fa-solid fa-ear-listen',
            'حنجرة' => 'fa-solid fa-ear-listen',
            'جراحة' => 'fa-solid fa-user-doctor',
            'سكر' => 'fa-solid fa-cubes-stacked',
            'غدد' => 'fa-solid fa-vial-circle-check',
            'طوارئ' => 'fa-solid fa-truck-medical',
            'أورام' => 'fa-solid fa-ribbon',
            'اورام' => 'fa-solid fa-ribbon',
            'مختبر' => 'fa-solid fa-flask-vial',
            'تحاليل' => 'fa-solid fa-flask-vial',
            'أشعة' => 'fa-solid fa-x-ray',
            'اشعة' => 'fa-solid fa-x-ray',
            'علاج طبيعي' => 'fa-solid fa-person-walking',
            'طب عام' => 'fa-solid fa-stethoscope',
            'عام' => 'fa-solid fa-stethoscope',
        ];

        foreach ($icons as $keyword => $icon) {
            if (str_contains($name, $keyword)) {
                return $icon;
            }
        }

        return 'fa-solid fa-stethoscope';
    };
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
                <i class="fa-solid fa-stethoscope"></i>
                إدارة المنصة الطبية
            </span>

            <h1>التخصصات الطبية</h1>

            <p>
                رتب التخصصات، ابحث بسرعة، وفلترِ بين التخصصات الفعالة والمخفية.
            </p>
        </div>

        <button type="button" class="specialties-primary-btn-v2" data-open-modal="createSpecialtyModal">
            <i class="fa-solid fa-plus"></i>
            <span>إضافة تخصص</span>
        </button>
    </section>

    {{-- Insights --}}
    <section class="specialties-insights-v2 specialties-insights-v2--compact">
        <article class="specialties-insight-card is-total">
            <div class="specialties-insight-icon">
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <div>
                <span>إجمالي التخصصات</span>
                <strong>{{ $specialties->count() }}</strong>
            </div>
        </article>

        <article class="specialties-insight-card is-active">
            <div class="specialties-insight-icon">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div>
                <span>تخصصات فعالة</span>
                <strong>{{ $activeCount }}</strong>
            </div>
        </article>

        <article class="specialties-insight-card is-hidden">
            <div class="specialties-insight-icon">
                <i class="fa-solid fa-eye-slash"></i>
            </div>
            <div>
                <span>تخصصات مخفية</span>
                <strong>{{ $inactiveCount }}</strong>
            </div>
        </article>

        <article class="specialties-insight-card is-latest">
            <div class="specialties-insight-icon">
                <i class="fa-regular fa-clock"></i>
            </div>
            <div>
                <span>آخر تخصص مضاف</span>
                <strong>{{ $latestSpecialty?->name ?? '—' }}</strong>
            </div>
        </article>
    </section>

    {{-- Clean Specialty Categories --}}
    <section class="specialties-categories-v4">
        <div class="specialties-categories-v4__list">
            <button type="button" class="specialties-category-v4 is-active" data-specialty-status="all">
                <i class="fa-solid fa-layer-group"></i>
                <span>الكل</span>
                <b>{{ $specialties->count() }}</b>
            </button>

            <button type="button" class="specialties-category-v4" data-specialty-status="active">
                <i class="fa-solid fa-circle-check"></i>
                <span>فعالة</span>
                <b>{{ $activeCount }}</b>
            </button>

            <button type="button" class="specialties-category-v4" data-specialty-status="inactive">
                <i class="fa-solid fa-eye-slash"></i>
                <span>مخفية</span>
                <b>{{ $inactiveCount }}</b>
            </button>
        </div>

        <button type="button" class="specialties-clear-filter-v4 is-hidden" id="specialtiesClearFilter">
            <i class="fa-solid fa-xmark"></i>
            إزالة الفلتر
        </button>
    </section>

    {{-- Search --}}
    <section class="specialties-searchbar-v4">
        <div class="specialties-searchbar-form-v4">
            <div class="specialties-searchbox-v4">
                <i class="fa-solid fa-magnifying-glass"></i>

                <input
                    type="text"
                    id="specialtiesSearchInput"
                    placeholder="ابحثي باسم التخصص أو الوصف..."
                    autocomplete="off"
                >

                <button type="button" class="specialties-search-clear-v4 is-hidden" id="specialtiesSearchClear" title="مسح البحث">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>
    </section>

    {{-- Main Card --}}
    <section class="specialties-board-v2 specialties-board-v2--v4">
        <div class="specialties-board-head-v2">
            <div>
                <span>نتائج التخصصات</span>
                <h2>التخصصات المطابقة</h2>
                <p>يتم عرض التخصصات حسب البحث والتصنيف المختار.</p>
            </div>

            <div class="specialties-count-pill-v2">
                <i class="fa-solid fa-list-check"></i>
                <span id="specialtiesVisibleCount">{{ $specialties->count() }}</span>
                تخصص
            </div>
        </div>

        @if ($specialties->count())
            <div class="specialties-grid-v2" id="specialtiesGrid">
                @foreach ($specialties as $specialty)
                    @php
                        $isThisEditError = $editErrorId === $specialty->id;
                        $specialtyIcon = $resolveSpecialtyIcon($specialty);
                    @endphp

                    <article class="specialty-card-v2 {{ !$specialty->is_active ? 'is-muted' : '' }}"
                             data-specialty-card
                             data-status="{{ $specialty->is_active ? 'active' : 'inactive' }}"
                             data-search="{{ mb_strtolower($specialty->name . ' ' . ($specialty->description ?? '')) }}">
                        <div class="specialty-card-v2__top">
                            <div class="specialty-icon-v2">
                                <i class="{{ $specialtyIcon }}"></i>
                            </div>

                            @if ($specialty->is_active)
                                <span class="specialty-status-v2 is-active">فعّال</span>
                            @else
                                <span class="specialty-status-v2 is-inactive">مخفي</span>
                            @endif
                        </div>

                        <div class="specialty-card-v2__body">
                            <h3>{{ $specialty->name }}</h3>
                            <p>{{ $specialty->description ?: 'لا يوجد وصف لهذا التخصص بعد.' }}</p>
                        </div>

                        <div class="specialty-card-v2__meta">
                            <span>
                                <i class="fa-solid fa-arrow-down-1-9"></i>
                                الترتيب: {{ $specialty->sort_order }}
                            </span>
                            <span>
                                <i class="fa-regular fa-calendar"></i>
                                {{ $specialty->created_at?->format('Y-m-d') }}
                            </span>
                        </div>

                        <div class="specialty-card-v2__actions">
                            <button type="button"
                                    class="specialty-action-v2"
                                    title="تعديل"
                                    data-open-modal="editSpecialtyModal-{{ $specialty->id }}">
                                <i class="fa-regular fa-pen-to-square"></i>
                            </button>

                            <button type="button"
                                    class="specialty-action-v2 is-danger"
                                    title="حذف"
                                    data-open-modal="deleteSpecialtyModal-{{ $specialty->id }}">
                                <i class="fa-regular fa-trash-can"></i>
                            </button>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="specialties-empty-v2 specialties-filter-empty-v4 is-hidden" id="specialtiesFilterEmpty">
                <div>
                    <i class="fa-regular fa-folder-open"></i>
                </div>
                <h3>لا توجد نتائج مطابقة</h3>
                <p>جرّبي تغيير كلمة البحث أو اختيار تصنيف آخر.</p>
            </div>
        @else
            <div class="specialties-empty-v2">
                <div>
                    <i class="fa-regular fa-folder-open"></i>
                </div>
                <h3>لا توجد تخصصات بعد</h3>
                <p>اضغطي على زر إضافة تخصص لبدء بناء قائمة التخصصات داخل الموقع.</p>

                <button type="button" class="specialties-primary-btn-v2" data-open-modal="createSpecialtyModal">
                    <i class="fa-solid fa-plus"></i>
                    إضافة أول تخصص
                </button>
            </div>
        @endif
    </section>

</section>
@endsection

@push('modals')
    @foreach ($specialties as $specialty)
        @php
            $isThisEditError = $editErrorId === $specialty->id;
        @endphp

        {{-- Edit Modal --}}
        <div class="admin-specialty-modal {{ $isThisEditError ? 'is-open' : '' }}"
             id="editSpecialtyModal-{{ $specialty->id }}">
            <div class="admin-specialty-modal__backdrop" data-close-modal></div>

            <div class="admin-specialty-modal__dialog">
                <div class="admin-specialty-modal__head">
                    <div>
                        <span>تعديل تخصص</span>
                        <h2>{{ $specialty->name }}</h2>
                    </div>

                    <button type="button" class="admin-specialty-modal__close" data-close-modal>
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <form action="{{ route('admin.specialties.update', $specialty) }}"
                      method="POST"
                      class="admin-specialty-form">
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="_form" value="edit">
                    <input type="hidden" name="specialty_id" value="{{ $specialty->id }}">

                    <div class="admin-specialty-field">
                        <label>اسم التخصص</label>
                        <input type="text"
                               name="name"
                               value="{{ $isThisEditError ? old('name') : $specialty->name }}"
                               placeholder="مثال: قلب وشرايين"
                               class="{{ $isThisEditError && $errors->has('name') ? 'is-invalid' : '' }}">

                        @if ($isThisEditError && $errors->has('name'))
                            <small class="admin-field-error">{{ $errors->first('name') }}</small>
                        @endif
                    </div>

                    <div class="admin-specialty-field">
                        <label>وصف مختصر</label>
                        <textarea name="description"
                                  placeholder="وصف بسيط يظهر لاحقًا في الموقع أو صفحة التخصص."
                                  class="{{ $isThisEditError && $errors->has('description') ? 'is-invalid' : '' }}">{{ $isThisEditError ? old('description') : $specialty->description }}</textarea>

                        @if ($isThisEditError && $errors->has('description'))
                            <small class="admin-field-error">{{ $errors->first('description') }}</small>
                        @endif
                    </div>

                    <div class="admin-specialty-field">
                        <label>ترتيب الظهور</label>
                        <input type="number"
                               name="sort_order"
                               value="{{ $isThisEditError ? old('sort_order') : $specialty->sort_order }}"
                               min="0"
                               class="{{ $isThisEditError && $errors->has('sort_order') ? 'is-invalid' : '' }}">

                        @if ($isThisEditError && $errors->has('sort_order'))
                            <small class="admin-field-error">{{ $errors->first('sort_order') }}</small>
                        @endif
                    </div>

                    <label class="admin-specialty-switch">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox"
                               name="is_active"
                               value="1"
                               @checked($isThisEditError ? old('is_active') == '1' : $specialty->is_active)>
                        <span></span>
                        <strong>تخصص فعّال</strong>
                    </label>

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
        <div class="admin-specialty-modal" id="deleteSpecialtyModal-{{ $specialty->id }}">
            <div class="admin-specialty-modal__backdrop" data-close-modal></div>

            <div class="admin-specialty-modal__dialog admin-specialty-modal__dialog--small">
                <div class="admin-delete-modal-icon">
                    <i class="fa-regular fa-trash-can"></i>
                </div>

                <div class="admin-delete-modal-text">
                    <h2>حذف التخصص؟</h2>
                    <p>
                        هل أنتِ متأكدة من حذف تخصص
                        <strong>{{ $specialty->name }}</strong>؟
                        لا يمكن التراجع عن هذه العملية.
                    </p>
                </div>

                <form action="{{ route('admin.specialties.destroy', $specialty) }}" method="POST">
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
    @endforeach

    {{-- Create Modal --}}
    <div class="admin-specialty-modal {{ $isCreateError ? 'is-open' : '' }}" id="createSpecialtyModal">
        <div class="admin-specialty-modal__backdrop" data-close-modal></div>

        <div class="admin-specialty-modal__dialog">
            <div class="admin-specialty-modal__head">
                <div>
                    <span>تخصص جديد</span>
                    <h2>إضافة تخصص طبي</h2>
                </div>

                <button type="button" class="admin-specialty-modal__close" data-close-modal>
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form action="{{ route('admin.specialties.store') }}" method="POST" class="admin-specialty-form">
                @csrf

                <input type="hidden" name="_form" value="create">

                <div class="admin-specialty-field">
                    <label>اسم التخصص</label>
                    <input type="text"
                           name="name"
                           value="{{ $isCreateError ? old('name') : '' }}"
                           placeholder="مثال: قلب وشرايين"
                           class="{{ $isCreateError && $errors->has('name') ? 'is-invalid' : '' }}">

                    @if ($isCreateError && $errors->has('name'))
                        <small class="admin-field-error">{{ $errors->first('name') }}</small>
                    @endif
                </div>

                <div class="admin-specialty-field">
                    <label>وصف مختصر</label>
                    <textarea name="description"
                              placeholder="وصف بسيط يظهر لاحقًا في الموقع أو صفحة التخصص."
                              class="{{ $isCreateError && $errors->has('description') ? 'is-invalid' : '' }}">{{ $isCreateError ? old('description') : '' }}</textarea>

                    @if ($isCreateError && $errors->has('description'))
                        <small class="admin-field-error">{{ $errors->first('description') }}</small>
                    @endif
                </div>

                <div class="admin-specialty-field">
                    <label>ترتيب الظهور</label>
                    <input type="number"
                           name="sort_order"
                           value="{{ $isCreateError ? old('sort_order') : '' }}"
                           min="0"
                           placeholder="مثال: 1"
                           class="{{ $isCreateError && $errors->has('sort_order') ? 'is-invalid' : '' }}">

                    @if ($isCreateError && $errors->has('sort_order'))
                        <small class="admin-field-error">{{ $errors->first('sort_order') }}</small>
                    @endif
                </div>

                <label class="admin-specialty-switch">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', '1') == '1')>
                    <span></span>
                    <strong>تخصص فعّال</strong>
                </label>

                <div class="admin-specialty-modal__actions">
                    <button type="button" class="admin-specialty-cancel" data-close-modal>
                        إلغاء
                    </button>

                    <button type="submit" class="admin-specialty-save">
                        <i class="fa-solid fa-plus"></i>
                        <span>إضافة التخصص</span>
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

    const specialtyCards = Array.from(document.querySelectorAll('[data-specialty-card]'));
    const categoryButtons = document.querySelectorAll('[data-specialty-status]');
    const searchInput = document.getElementById('specialtiesSearchInput');
    const searchClear = document.getElementById('specialtiesSearchClear');
    const clearFilter = document.getElementById('specialtiesClearFilter');
    const visibleCount = document.getElementById('specialtiesVisibleCount');
    const filterEmpty = document.getElementById('specialtiesFilterEmpty');

    let currentStatus = 'all';

    function openModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;

        modal.classList.add('is-open');
        document.body.classList.add('modal-open');
    }

    function closeModal(button) {
        const modal = button.closest('.admin-specialty-modal');
        if (!modal) return;

        modal.classList.remove('is-open');

        if (!document.querySelector('.admin-specialty-modal.is-open')) {
            document.body.classList.remove('modal-open');
        }
    }

    function normalize(text) {
        return String(text || '').toLowerCase().trim();
    }

    function updateSpecialtiesView() {
        const query = normalize(searchInput?.value || '');
        let count = 0;

        specialtyCards.forEach((card) => {
            const matchesStatus = currentStatus === 'all' || card.dataset.status === currentStatus;
            const matchesSearch = !query || normalize(card.dataset.search).includes(query);
            const isVisible = matchesStatus && matchesSearch;

            card.classList.toggle('is-hidden-by-filter', !isVisible);

            if (isVisible) {
                count++;
            }
        });

        if (visibleCount) {
            visibleCount.textContent = count;
        }

        filterEmpty?.classList.toggle('is-hidden', count !== 0);
        searchClear?.classList.toggle('is-hidden', query.length === 0);
        clearFilter?.classList.toggle('is-hidden', currentStatus === 'all' && query.length === 0);
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

    categoryButtons.forEach((button) => {
        button.addEventListener('click', function () {
            currentStatus = this.dataset.specialtyStatus || 'all';

            categoryButtons.forEach((item) => item.classList.remove('is-active'));
            this.classList.add('is-active');

            updateSpecialtiesView();
        });
    });

    searchInput?.addEventListener('input', updateSpecialtiesView);

    searchClear?.addEventListener('click', function () {
        searchInput.value = '';
        updateSpecialtiesView();
        searchInput.focus();
    });

    clearFilter?.addEventListener('click', function () {
        currentStatus = 'all';
        if (searchInput) {
            searchInput.value = '';
        }

        categoryButtons.forEach((item) => {
            item.classList.toggle('is-active', item.dataset.specialtyStatus === 'all');
        });

        updateSpecialtiesView();
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

    document.querySelectorAll('.admin-specialty-field .is-invalid').forEach((input) => {
        input.addEventListener('input', () => input.classList.remove('is-invalid'));
        input.addEventListener('change', () => input.classList.remove('is-invalid'));
    });

    updateSpecialtiesView();
});
</script>
@endpush
