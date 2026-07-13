@extends('layouts.patient')

@php
    $doctor = $doctorDetails ?? [];
    $activePage = $activePage ?? 'profile';

    $selectRoute = Route::has('patient.doctors.select')
        ? route('patient.doctors.select', data_get($doctor, 'id'))
        : '#';

    $backRoute = Route::has('patient.doctors.recommended')
        ? route('patient.doctors.recommended')
        : url('/patient/doctors/recommended');

    $profileCompleted = (bool) data_get($patient ?? [], 'has_completed_profile', false);
    $isCurrentDoctor = (bool) data_get($doctor, 'is_current_doctor', false);
    $requestStatus = data_get($doctor, 'request_status');

    $statusLabel = match ($requestStatus) {
        'pending' => 'طلب المتابعة بانتظار موافقة الطبيب',
        'approved' => 'هذا هو طبيب المتابعة الحالي',
        'rejected' => 'تم رفض طلب المتابعة سابقًا',
        default => null,
    };
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/patient/doctor-details.css') }}">
@endpush

@section('content')
<section class="docd-page">
    <a href="{{ $backRoute }}" class="docd-back">
        <i data-lucide="arrow-right"></i>
        العودة للأطباء المناسبين
    </a>

    <section class="docd-hero">
        <div class="docd-hero-card">
            <div class="docd-avatar-wrap">
                <img src="{{ data_get($doctor, 'avatar') }}" alt="{{ data_get($doctor, 'name') }}">
                <span>{{ data_get($doctor, 'match_score', 0) }}%</span>
            </div>

            <div class="docd-main-info">
                <span class="docd-kicker">
                    <i data-lucide="stethoscope"></i>
                    تفاصيل الطبيب
                </span>

                <h1>{{ data_get($doctor, 'name', 'طبيب اتزان') }}</h1>

                <p>{{ data_get($doctor, 'specialty', 'استشاري صحي') }}</p>

                <div class="docd-badges">
                    <span>
                        <i data-lucide="star"></i>
                            @if (data_get($doctor, 'has_reviews'))
                                {{ data_get($doctor, 'rating') }} تقييم
                            @else
                                لا توجد تقييمات بعد
                            @endif
                      </span>

                    <span>
                        <i data-lucide="briefcase-medical"></i>
                        {{ data_get($doctor, 'experience', 0) }} سنوات خبرة
                    </span>

                    <span>
                        <i data-lucide="video"></i>
                        {{ data_get($doctor, 'consultation_type', 'أونلاين') }}
                    </span>

                    <span>
                        <i data-lucide="user-round"></i>
                        {{ data_get($doctor, 'gender_label', 'غير محدد') }}
                    </span>
                </div>
            </div>

            <div class="docd-action-box">
                @if ($isCurrentDoctor && $requestStatus === 'approved')
                    <div class="docd-status docd-status-approved">
                        <i data-lucide="badge-check"></i>
                        <span>{{ $statusLabel }}</span>
                    </div>

                    <a href="{{ route('patient.followup') }}" class="docd-primary-btn">
                        <i data-lucide="calendar-days"></i>
                        حجز موعد
                    </a>
                @elseif ($isCurrentDoctor && $requestStatus === 'pending')
                    <div class="docd-status docd-status-pending">
                        <i data-lucide="clock-3"></i>
                        <span>{{ $statusLabel }}</span>
                    </div>
                @elseif (! $profileCompleted)
                    <div class="docd-status docd-status-warning">
                        <i data-lucide="circle-alert"></i>
                        <span>أكمل ملفك الصحي أولًا قبل اختيار الطبيب.</span>
                    </div>

                    <a href="{{ route('patient.profile') }}" class="docd-primary-btn">
                        <i data-lucide="clipboard-check"></i>
                        إكمال الملف الصحي
                    </a>
                @else
                    <form action="{{ $selectRoute }}" method="POST">
                        @csrf
                        <button type="submit" class="docd-primary-btn">
                            <i data-lucide="user-check"></i>
                            اختيار هذا الطبيب
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </section>

    <section class="docd-grid">
        <article class="docd-card docd-match-card">
            <div class="docd-card-head">
                <span>Match Reason</span>
                <h2>لماذا هذا الطبيب مناسب لك؟</h2>
            </div>

            <p>{{ data_get($doctor, 'match_reason', 'مناسب للمتابعة الصحية العامة وبناء خطة يومية أوضح.') }}</p>

            <div class="docd-match-tags">
                @foreach (data_get($doctor, 'badges', []) as $badge)
                    <span>{{ $badge }}</span>
                @endforeach
            </div>
        </article>

        <article class="docd-card">
            <div class="docd-card-head">
                <span>About Doctor</span>
                <h2>نبذة عن الطبيب</h2>
            </div>

            <p>{{ data_get($doctor, 'bio') }}</p>
        </article>
    </section>

    <section class="docd-card docd-services">
        <div class="docd-card-head">
            <span>Services</span>
            <h2>ما الذي يستطيع الطبيب مساعدتك فيه؟</h2>
        </div>

        <div class="docd-services-grid">
            <div>
                <i data-lucide="target"></i>
                <strong>تحديد أهداف السعرات</strong>
                <span>يحدد الطبيب هدف كل يوم حسب حالتك وخطتك.</span>
            </div>

            <div>
                <i data-lucide="utensils"></i>
                <strong>متابعة الوجبات</strong>
                <span>يراجع الوجبات المحفوظة ونتائج تحليل الذكاء الاصطناعي.</span>
            </div>

            <div>
                <i data-lucide="calendar-days"></i>
                <strong>مواعيد المتابعة</strong>
                <span>تقدر تحجز موعدًا وتتابع حالة طلبك من داخل المنصة.</span>
            </div>

            <div>
                <i data-lucide="message-circle"></i>
                <strong>التواصل والرسائل</strong>
                <span>تقدر ترسل للطبيب أسئلتك بعد اعتماد المتابعة.</span>
            </div>
        </div>
    </section>

    <section class="docd-card docd-articles">
        <div class="docd-card-head">
            <span>Doctor Articles</span>
            <h2>مقالات الطبيب</h2>
        </div>

        @if (!empty(data_get($doctor, 'articles', [])))
            <div class="docd-articles-grid">
                @foreach (data_get($doctor, 'articles', []) as $article)
                    @php($articleUrl = data_get($article, 'url') ?: (!empty(data_get($article, 'slug')) ? route('patient.articles.show', data_get($article, 'slug')) : route('patient.articles')))
                    <a href="{{ $articleUrl }}">
                        <span>{{ data_get($article, 'read_time', 'قراءة قصيرة') }}</span>
                        <strong>{{ data_get($article, 'title', 'مقال صحي') }}</strong>
                    </a>
                @endforeach
            </div>
        @else
            <div class="docd-empty">
                <i data-lucide="file-text"></i>
                <strong>لا توجد مقالات منشورة لهذا الطبيب بعد</strong>
                <span>ستظهر مقالات الطبيب هنا عند إضافتها.</span>
            </div>
        @endif
    </section>
</section>
@endsection
