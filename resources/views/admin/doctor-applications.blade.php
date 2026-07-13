@extends('layouts.admin')

@section('title', 'طلبات الأطباء | اتزان')

@push('styles')
<link rel="stylesheet" href="{{ asset('front/css/admin/admin-doctor-applications.css') }}">
@endpush

@section('content')
<section class="doctor-apps-page doctor-apps-v2-page">

    @if (session('success'))
        <div class="doctor-apps-toast doctor-apps-toast--success">
            <i class="fa-solid fa-circle-check"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- Hero --}}
    <section class="doctor-apps-hero-v2 doctor-apps-hero-v2--compact">
        <div>
            <span class="doctor-apps-kicker-v2">
                <i class="fa-solid fa-user-doctor"></i>
                إدارة طلبات الأطباء
            </span>

            <h1>طلبات الأطباء</h1>

            <p>
                راجع طلبات الانضمام، وابحث أو فلترِ حسب الحالة والتخصص بسرعة.
            </p>
        </div>

        <div class="doctor-apps-hero-badge-v2 doctor-apps-hero-badge-v2--compact">
            <span>قيد المراجعة</span>
            <strong>{{ $stats['pending'] }}</strong>
        </div>
    </section>

    {{-- Clean Doctor Application Categories --}}
    <section class="doctor-apps-categories-v4">
        <div class="doctor-apps-categories-v4__list">
            <a href="{{ route('admin.doctor-applications', request()->except(['page', 'status'])) }}"
               class="doctor-apps-category-v4 {{ request('status') === null || request('status') === '' ? 'is-active' : '' }}">
                <i class="fa-solid fa-layer-group"></i>
                <span>الكل</span>
                <b>{{ $stats['total'] }}</b>
            </a>

            <a href="{{ route('admin.doctor-applications', array_merge(request()->except(['page', 'status']), ['status' => 'pending'])) }}"
               class="doctor-apps-category-v4 {{ request('status') === 'pending' ? 'is-active' : '' }}">
                <i class="fa-regular fa-hourglass-half"></i>
                <span>قيد المراجعة</span>
                <b>{{ $stats['pending'] }}</b>
            </a>

            <a href="{{ route('admin.doctor-applications', array_merge(request()->except(['page', 'status']), ['status' => 'approved'])) }}"
               class="doctor-apps-category-v4 {{ request('status') === 'approved' ? 'is-active' : '' }}">
                <i class="fa-solid fa-circle-check"></i>
                <span>مقبولة</span>
                <b>{{ $stats['approved'] }}</b>
            </a>

            <a href="{{ route('admin.doctor-applications', array_merge(request()->except(['page', 'status']), ['status' => 'rejected'])) }}"
               class="doctor-apps-category-v4 {{ request('status') === 'rejected' ? 'is-active' : '' }}">
                <i class="fa-solid fa-circle-xmark"></i>
                <span>مرفوضة</span>
                <b>{{ $stats['rejected'] }}</b>
            </a>
        </div>

        @if(request('status'))
            <a href="{{ route('admin.doctor-applications', request()->except(['page', 'status'])) }}"
               class="doctor-apps-clear-filter-v4">
                <i class="fa-solid fa-xmark"></i>
                إزالة الفلتر
            </a>
        @endif
    </section>

    {{-- Search & Specialty Filter --}}
    <section class="doctor-apps-searchbar-v4">
        <form method="GET"
              action="{{ route('admin.doctor-applications') }}"
              class="doctor-apps-searchbar-form-v4"
              id="doctorAppsFilterForm">

            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif

            <div class="doctor-apps-searchbox-v4">
                <i class="fa-solid fa-magnifying-glass"></i>

                <input
                    type="text"
                    name="search"
                    id="doctorAppsSearchInput"
                    value="{{ request('search') }}"
                    placeholder="ابحث باسم الطبيب، البريد، أو رقم الترخيص..."
                    autocomplete="off"
                >

                @if(request('search'))
                    <a href="{{ route('admin.doctor-applications', request()->except(['page', 'search'])) }}"
                       class="doctor-apps-search-clear-v4"
                       title="مسح البحث">
                        <i class="fa-solid fa-xmark"></i>
                    </a>
                @endif
            </div>

            <div class="doctor-apps-specialty-filter-v4">
                <button type="button"
                        class="doctor-apps-specialty-btn-v4 {{ request('specialty') ? 'is-active' : '' }}"
                        id="doctorAppsFilterToggle"
                        aria-label="فلترة حسب التخصص"
                        title="فلترة حسب التخصص">
                    <i class="fa-solid fa-stethoscope"></i>
                    <span>{{ request('specialty') ?: 'كل التخصصات' }}</span>
                    <b><i class="fa-solid fa-chevron-down"></i></b>
                </button>

                <div class="doctor-apps-specialty-menu-v4" id="doctorAppsFilterPopover">
                    <div class="doctor-apps-specialty-menu-head-v4">
                        <span>فلترة حسب التخصص</span>
                    </div>

                    <div class="doctor-apps-specialty-list-v4">
                        <a href="{{ route('admin.doctor-applications', request()->except(['page', 'specialty'])) }}"
                           class="doctor-apps-specialty-option-v4 {{ request('specialty') === null || request('specialty') === '' ? 'is-active' : '' }}">
                            <i class="fa-solid fa-layer-group"></i>
                            <span>كل التخصصات</span>
                        </a>

                        @foreach ($specialties as $specialty)
                            <a href="{{ route('admin.doctor-applications', array_merge(request()->except(['page', 'specialty']), ['specialty' => $specialty])) }}"
                               class="doctor-apps-specialty-option-v4 {{ request('specialty') === $specialty ? 'is-active' : '' }}">
                                <i class="fa-solid fa-stethoscope"></i>
                                <span>{{ $specialty }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            @if(request('search') || request('specialty'))
                <a href="{{ route('admin.doctor-applications', request()->except(['page', 'search', 'specialty'])) }}"
                   class="doctor-apps-filter-clear-v4">
                    <i class="fa-solid fa-filter-circle-xmark"></i>
                    <span>مسح</span>
                </a>
            @endif
        </form>
    </section>

    {{-- Results --}}
    <section class="doctor-apps-results-v2">
        <div class="doctor-apps-results-head-v2">
            <div>
                <span>نتائج الطلبات</span>
                <h2>الطلبات المطابقة</h2>
                <p>
                    يتم عرض الطلبات حسب التصنيف والبحث والتخصص المختار.
                </p>
            </div>

            <div class="doctor-apps-results-count-v2">
                <i class="fa-regular fa-folder-open"></i>
                <strong>{{ $applications->total() }}</strong>
                <span>طلب</span>
            </div>
        </div>

        <div class="doctor-apps-list-v2">
            @forelse ($applications as $application)
                <article class="doctor-app-card-v2 doctor-app-card-v2--{{ $application->status }}">
                    <div class="doctor-app-card-v2__main">
                        <div class="doctor-app-avatar-v2">
                            @if ($application->profile_photo_path)
                                <img src="{{ asset('storage/' . $application->profile_photo_path) }}" alt="{{ $application->full_name }}">
                            @else
                                <span>{{ mb_substr($application->full_name, 0, 1) }}</span>
                            @endif
                        </div>

                        <div class="doctor-app-info-v2">
                            <div class="doctor-app-title-row-v2">
                                <div>
                                    <h3>{{ $application->full_name }}</h3>
                                    <p>{{ $application->email }}</p>
                                </div>

                                <span class="doctor-app-status-v2 doctor-app-status-v2--{{ $application->status }}">
                                    @if ($application->status === 'pending')
                                        <i class="fa-regular fa-hourglass-half"></i>
                                        مراجعة
                                    @elseif ($application->status === 'approved')
                                        <i class="fa-solid fa-circle-check"></i>
                                        مقبول
                                    @else
                                        <i class="fa-solid fa-circle-xmark"></i>
                                        مرفوض
                                    @endif
                                </span>
                            </div>

                            <div class="doctor-app-chips-v2">
                                <span>
                                    <i class="fa-solid fa-stethoscope"></i>
                                    {{ $application->specialty }}
                                </span>

                                <span>
                                    <i class="fa-solid fa-hospital"></i>
                                    {{ $application->workplace }}
                                </span>

                                <span>
                                    <i class="fa-solid fa-briefcase"></i>
                                    {{ $application->experience_years }} سنوات
                                </span>

                                <span>
                                    <i class="fa-solid fa-id-card"></i>
                                    {{ $application->license_number }}
                                </span>

                                <span>
                                    <i class="fa-solid fa-phone"></i>
                                    {{ $application->phone }}
                                </span>

                                @if ($application->status === 'approved')
                                    <span class="doctor-app-chip-insight">
                                        <i class="fa-solid fa-users"></i>
                                        {{ $application->admin_patients_count ?? 0 }} مريض تحت الاستشارة
                                    </span>

                                    <span class="doctor-app-chip-insight">
                                        <i class="fa-regular fa-calendar-check"></i>
                                        {{ $application->admin_appointments_count ?? 0 }} موعد
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="doctor-app-card-v2__actions">
                        <a href="{{ route('admin.doctor-applications-show', $application) }}"
                           class="doctor-app-view-btn-v2"
                           title="رؤية التفاصيل"
                           aria-label="رؤية التفاصيل">
                            <i class="fa-regular fa-eye"></i>
                            <span>التفاصيل</span>
                        </a>
                    </div>
                </article>
            @empty
                <div class="doctor-apps-empty-v2">
                    <div class="doctor-apps-empty-icon-v2">
                        <i class="fa-regular fa-folder-open"></i>
                    </div>

                    <h3>لا توجد طلبات</h3>
                    <p>لا يوجد شيء مطابق للبحث أو الفلاتر الحالية.</p>
                </div>
            @endforelse
        </div>

        @if ($applications->hasPages())
            <div class="doctor-apps-pagination-v2">
                {{ $applications->links() }}
            </div>
        @endif
    </section>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('doctorAppsFilterForm');
    const searchInput = document.getElementById('doctorAppsSearchInput');
    const filterToggle = document.getElementById('doctorAppsFilterToggle');
    const filterPopover = document.getElementById('doctorAppsFilterPopover');

    let searchTimer = null;

    if (form && searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);

            searchTimer = setTimeout(() => {
                form.submit();
            }, 450);
        });
    }

    if (filterToggle && filterPopover) {
        filterToggle.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            filterPopover.classList.toggle('is-open');
        });

        document.addEventListener('click', function (event) {
            if (!filterPopover.contains(event.target) && !filterToggle.contains(event.target)) {
                filterPopover.classList.remove('is-open');
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                filterPopover.classList.remove('is-open');
            }
        });
    }
});
</script>
@endpush
