@extends('layouts.patient')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/patient/recommended-doctors.css') }}">
@endpush

@section('content')
@php
    $doctors = $recommendedDoctors ?? [];
    $meta = $recommendationMeta ?? [];
    $form = $profileForm ?? [];

    $goalLabels = [
        'healthy_lifestyle' => 'تحسين نمط الحياة',
        'diabetes_management' => 'تنظيم السكر',
        'hypertension_management' => 'تنظيم الضغط',
        'cholesterol_management' => 'تحسين الكوليسترول',
        'chronic_care' => 'متابعة حالة مزمنة',
        'therapeutic_nutrition' => 'تغذية علاجية',
        'weight_loss' => 'خسارة وزن',
        'weight_gain' => 'زيادة وزن صحية',
    ];

    $conditionLabels = [
        'none' => 'لا يوجد',
        'diabetes' => 'سكري',
        'hypertension' => 'ضغط',
        'cholesterol' => 'كوليسترول',
        'heart' => 'أمراض قلب',
        'kidney' => 'مشاكل كلى',
        'liver' => 'مشاكل كبد',
        'thyroid' => 'الغدة الدرقية',
        'digestive' => 'مشاكل هضمية',
        'anemia' => 'أنيميا',
        'food_allergy' => 'حساسية غذائية',
    ];

    $goalValue = $form['health_goal'] ?? null;
    $goalLabel = $goalLabels[$goalValue] ?? 'غير محدد';

    $conditions = $form['medical_conditions'] ?? [];
    if (!is_array($conditions)) {
        $conditions = [];
    }
@endphp

<section class="rd-page">
    @if (session('success'))
        <div class="rd-alert is-success">
            <i data-lucide="badge-check"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="rd-alert is-error">
            <i data-lucide="circle-alert"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <div class="rd-hero">
        <div class="rd-hero-copy">
            <span class="rd-kicker">
                <i data-lucide="sparkles"></i>
                ترشيح حسب ملفك الصحي
            </span>

            <h1>اختر الطبيب المناسب لحالتك</h1>

            <p>
                هذه النتائج مبنية على بيانات ملفك الصحي، هدفك، الحالات الصحية المسجلة،
                وتفضيلاتك في المتابعة.
            </p>

            <div class="rd-context">
                <span>
                    <i data-lucide="target"></i>
                    الهدف: {{ $goalLabel }}
                </span>

                <span>
                    <i data-lucide="heart-pulse"></i>
                    الحالة:
                    @if (!empty($conditions))
                        {{ collect($conditions)->map(fn($item) => $conditionLabels[$item] ?? $item)->join('، ') }}
                    @else
                        غير محددة
                    @endif
                </span>

                <span>
                    <i data-lucide="user-round"></i>
                    التفضيل: {{ $meta['preferred_gender_label'] ?? 'لا يهم' }}
                </span>

                <span>
                    <i data-lucide="video"></i>
                    الاستشارة: {{ $meta['preferred_type_label'] ?? 'لا يهم' }}
                </span>
            </div>
        </div>

        <div class="rd-hero-side">
            <div class="rd-score-box">
                <strong>{{ count($doctors) }}</strong>
                <span>طبيب مناسب</span>
            </div>

            <a href="{{ route('patient.profile') }}" class="rd-back-link">
                <i data-lucide="arrow-right"></i>
                العودة للملف
            </a>
        </div>
    </div>

    <div class="rd-toolbar">
        <div>
            <span>نتائج الترشيح</span>
            <strong>مرتبة من الأعلى تطابقًا</strong>
        </div>

        <div class="rd-filter-chips">
            <button type="button" class="is-active" data-rd-filter="all">الكل</button>
            <button type="button" data-rd-filter="female">طبيبات</button>
            <button type="button" data-rd-filter="male">أطباء</button>
            <button type="button" data-rd-filter="online">أونلاين</button>
            <button type="button" data-rd-filter="clinic">حضوري</button>
        </div>
    </div>

    <div class="rd-grid" data-rd-grid>
        @forelse ($doctors as $doctorItem)
            <article
                class="rd-card"
                data-gender="{{ $doctorItem['gender'] ?? 'unknown' }}"
                data-consultation="{{ $doctorItem['consultation_key'] ?? 'online' }}"
            >
                <div class="rd-card-top">
                    <img src="{{ $doctorItem['avatar'] }}" alt="صورة {{ $doctorItem['name'] }}">

                    <div>
                        <h3>{{ $doctorItem['name'] }}</h3>
                        <span>{{ $doctorItem['specialty'] }}</span>
                    </div>

                    <div class="rd-match">
                        <strong>{{ $doctorItem['match_score'] }}%</strong>
                        <small>تطابق</small>
                    </div>
                </div>

                <p class="rd-reason">
                    {{ $doctorItem['match_reason'] }}
                </p>

                <div class="rd-meta">
                    <span>
                        <i data-lucide="user-round"></i>
                        {{ $doctorItem['gender_label'] }}
                    </span>

                    <span>
                        <i data-lucide="video"></i>
                        {{ $doctorItem['consultation_type'] }}
                    </span>

                    <span>
                        <i data-lucide="briefcase-medical"></i>
                        {{ $doctorItem['experience'] }} سنوات
                    </span>
                </div>

                <div class="rd-stars">
                    @if (!empty($doctorItem['has_reviews']))
                        @for ($i = 1; $i <= 5; $i++)
                            <i data-lucide="star" class="{{ $i <= round((float) $doctorItem['rating']) ? 'is-filled' : '' }}"></i>
                        @endfor

                        <strong>{{ $doctorItem['rating'] }}</strong>
                        <span>{{ $doctorItem['reviews_count'] }} تقييم</span>
                    @else
                        @for ($i = 1; $i <= 5; $i++)
                            <i data-lucide="star"></i>
                        @endfor

                        <strong>—</strong>
                        <span>لا توجد تقييمات بعد</span>
                    @endif
                </div>

                <div class="rd-badges">
                    @foreach (($doctorItem['badges'] ?? []) as $badge)
                        <span>{{ $badge }}</span>
                    @endforeach
                </div>

                @if (!empty($doctorItem['articles']))
                    <div class="rd-articles">
                        <strong>من مقالات الطبيب</strong>

                        @foreach (array_slice($doctorItem['articles'], 0, 2) as $article)
                            <span>{{ $article['title'] }}</span>
                        @endforeach
                    </div>
                @endif

                <div class="rd-actions">
                        @if (Route::has('patient.doctors.details') && empty($doctorItem['is_fallback']))
                            <a href="{{ route('patient.doctors.details', $doctorItem['id']) }}" class="rd-outline-btn">
                                تفاصيل الطبيب
                            </a>
                        @else
                            <button type="button" class="rd-outline-btn" disabled>
                                تفاصيل الطبيب
                            </button>
                        @endif

                        @if (!empty($doctorItem['is_fallback']))
                            <button type="button" class="rd-primary-btn" disabled>
                                بيانات تجريبية
                            </button>
                        @else
                            <form method="POST" action="{{ route('patient.doctors.select', $doctorItem['id']) }}">
                                @csrf

                                <button type="submit" class="rd-primary-btn">
                                    اختيار الطبيب
                                </button>
                            </form>
                        @endif
                    </div>
            </article>
        @empty
            <div class="rd-empty">
                <i data-lucide="stethoscope"></i>
                <h3>لا يوجد أطباء بعد</h3>
                <p>
                    سيظهر هنا الأطباء المناسبون لحالتك فور انضمامهم إلى المنصة.
                </p>
            </div>
        @endforelse
    </div>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const buttons = document.querySelectorAll('[data-rd-filter]');
    const cards = document.querySelectorAll('.rd-card');

    buttons.forEach(function (button) {
        button.addEventListener('click', function () {
            const filter = button.dataset.rdFilter;

            buttons.forEach(btn => btn.classList.remove('is-active'));
            button.classList.add('is-active');

            cards.forEach(function (card) {
                const gender = card.dataset.gender;
                const consultation = card.dataset.consultation;

                const show =
                    filter === 'all' ||
                    gender === filter ||
                    consultation === filter;

                card.style.display = show ? '' : 'none';
            });
        });
    });
});
</script>
@endpush
