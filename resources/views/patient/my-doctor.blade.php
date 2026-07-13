@extends('layouts.patient')

@php
    $pageTitle = 'طبيبي';
    $activePage = $activePage ?? 'my-doctor';

    $page = $myDoctorPage ?? [];
    $doctor = data_get($page, 'doctor', []);
    $state = data_get($page, 'state', 'no_doctor');
    $nextAppointment = data_get($page, 'nextAppointment');
    $myReview = data_get($page, 'my_review');

    $doctorId = data_get($doctor, 'id');
    $hasDoctor = $state !== 'no_doctor' && !empty($doctorId);

    $doctorName = data_get($doctor, 'name', 'طبيب اتزان');
    $doctorSpecialty = data_get($doctor, 'specialty', 'استشاري صحي');
    $doctorAvatar = data_get($doctor, 'avatar');
    $doctorEmail = data_get($doctor, 'email', 'غير متوفر');
    $doctorBio = data_get($doctor, 'bio', 'طبيب مختص يساعدك على بناء متابعة صحية مناسبة لحالتك وهدفك.');
    $doctorGender = data_get($doctor, 'gender_label', 'غير محدد');
    $doctorExperience = data_get($doctor, 'experience', 0);
    $consultationType = data_get($doctor, 'consultation_type', 'أونلاين');
    $matchScore = data_get($doctor, 'match_score', '—');
    $matchReason = data_get($doctor, 'match_reason', 'مناسب لحالتك وهدفك الصحي بناءً على بيانات ملفك الصحي وتفضيلاتك.');

    $recommendedRoute = Route::has('patient.doctors.recommended')
        ? route('patient.doctors.recommended')
        : url('/patient/doctors/recommended');

    $detailsRoute = ($doctorId && Route::has('patient.doctors.details'))
        ? route('patient.doctors.details', $doctorId)
        : '#';

    $appointmentRoute = Route::has('patient.followup')
        ? route('patient.followup')
        : '#';

    $messagesRoute = Route::has('patient.messages')
        ? route('patient.messages')
        : '#';

    $caloriesRoute = Route::has('patient.calories')
        ? route('patient.calories')
        : '#';

    $reviewRoute = ($doctorId && Route::has('patient.doctors.review'))
        ? route('patient.doctors.review', $doctorId)
        : '#';

    $canSendReview = $state === 'approved' && $doctorId && Route::has('patient.doctors.review');

    $stateLabel = match ($state) {
        'approved' => 'متابعة مفعّلة',
        'pending' => 'بانتظار موافقة الطبيب',
        'rejected' => 'تم رفض المتابعة',
        default => 'لم يتم اختيار طبيب',
    };

    $stateText = match ($state) {
        'approved' => 'يمكنك الآن حجز موعد، مراسلة الطبيب، ومتابعة أهداف السعرات اليومية.',
        'pending' => 'طلبك وصل للطبيب. سيتم تفعيل الحجز والرسائل والتقييم بعد موافقته على المتابعة.',
        'rejected' => 'يمكنك اختيار طبيب آخر مناسب لحالتك وبدء طلب متابعة جديد.',
        default => 'اختر طبيب المتابعة المناسب حتى تبدأ رحلة المتابعة داخل اتزان.',
    };

    $stateIcon = match ($state) {
        'approved' => 'badge-check',
        'pending' => 'clock-3',
        'rejected' => 'circle-x',
        default => 'stethoscope',
    };

    $stateClass = match ($state) {
        'approved' => 'docdeck-approved',
        'pending' => 'docdeck-pending',
        'rejected' => 'docdeck-rejected',
        default => 'docdeck-empty',
    };

    $hasReviews = (bool) data_get($doctor, 'has_reviews', false);
    $reviewsCount = (int) data_get($doctor, 'reviews_count', 0);
    $rating = data_get($doctor, 'rating');
    $currentRating = (int) old('rating', data_get($myReview, 'rating', 0));

    $latestDoctorNote = data_get($page, 'latest_doctor_note')
        ?? data_get($page, 'last_doctor_note')
        ?? data_get($page, 'latest_note');

    $latestNoteText = data_get($latestDoctorNote, 'note')
        ?? data_get($latestDoctorNote, 'content')
        ?? data_get($latestDoctorNote, 'message');

    $latestNoteDate = data_get($latestDoctorNote, 'created_at')
        ?? data_get($latestDoctorNote, 'date')
        ?? data_get($latestDoctorNote, 'time');

    $dailyGoal = data_get($page, 'daily_goal', []);
    $nutritionPlan = data_get($page, 'nutrition_plan', []);

    $targetCalories = data_get($dailyGoal, 'calories')
        ?? data_get($dailyGoal, 'target_calories')
        ?? data_get($nutritionPlan, 'calories')
        ?? data_get($nutritionPlan, 'target_calories');

    $consumedCalories = data_get($dailyGoal, 'consumed')
        ?? data_get($dailyGoal, 'consumed_calories')
        ?? data_get($nutritionPlan, 'consumed_calories');

    $remainingCalories = data_get($dailyGoal, 'remaining')
        ?? data_get($dailyGoal, 'remaining_calories');

    $proteinTarget = data_get($dailyGoal, 'protein')
        ?? data_get($dailyGoal, 'protein_g')
        ?? data_get($nutritionPlan, 'protein');

    $carbsTarget = data_get($dailyGoal, 'carbs')
        ?? data_get($dailyGoal, 'carbs_g')
        ?? data_get($nutritionPlan, 'carbs');

    $fatTarget = data_get($dailyGoal, 'fat')
        ?? data_get($dailyGoal, 'fat_g')
        ?? data_get($nutritionPlan, 'fat');

    $hasTargetCalories = is_numeric($targetCalories) && (float) $targetCalories > 0;
    $hasConsumedCalories = is_numeric($consumedCalories);

    if ($remainingCalories === null && $hasTargetCalories && $hasConsumedCalories) {
        $remainingCalories = max(0, (float) $targetCalories - (float) $consumedCalories);
    }

    $caloriePercent = null;

    if ($hasTargetCalories && $hasConsumedCalories) {
        $caloriePercent = min(100, max(0, round(((float) $consumedCalories / (float) $targetCalories) * 100)));
    }

    $halfGaugeLength = 267;
    $halfGaugeDash = $caloriePercent !== null
        ? round($halfGaugeLength * ($caloriePercent / 100))
        : 0;

    $hasNutritionData =
        $hasTargetCalories
        || $hasConsumedCalories
        || is_numeric($remainingCalories)
        || is_numeric($proteinTarget)
        || is_numeric($carbsTarget)
        || is_numeric($fatTarget);
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/patient/my-doctor.css') }}">
@endpush

@section('content')
<section class="docdeck-page {{ $stateClass }}">
    @if (session('success'))
        <div class="docdeck-alert docdeck-alert-success">
            <i data-lucide="badge-check"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error') || $errors->any())
        <div class="docdeck-alert docdeck-alert-error">
            <i data-lucide="circle-alert"></i>
            <span>{{ session('error') ?: $errors->first() }}</span>
        </div>
    @endif

    @if (!$hasDoctor)
        <section class="docdeck-empty">
            <div class="docdeck-empty-orb">
                <i data-lucide="stethoscope"></i>
            </div>

            <span class="docdeck-kicker">My Doctor</span>
            <h1>اختار طبيبك وابدأ المتابعة</h1>

            <p>
                لم يتم اختيار طبيب متابعة بعد. بعد الاختيار ستظهر هنا معلومات الطبيب،
                حالة الموافقة، المواعيد، آخر ملاحظاته، أهدافك الغذائية، والتقييم.
            </p>

            <a href="{{ $recommendedRoute }}" class="docdeck-main-btn">
                <i data-lucide="sparkles"></i>
                عرض الأطباء المناسبين
            </a>
        </section>
    @else
        <section class="docdeck-hero">
            <div class="docdeck-hero-content">
                <div class="docdeck-photo">
                    @if ($doctorAvatar)
                        <img src="{{ $doctorAvatar }}" alt="{{ $doctorName }}">
                    @else
                        <div class="docdeck-photo-fallback">
                            <i data-lucide="stethoscope"></i>
                        </div>
                    @endif

                    <span class="docdeck-photo-mark">
                        <i data-lucide="{{ $stateIcon }}"></i>
                    </span>
                </div>

                <div class="docdeck-doctor-copy">
                    <span class="docdeck-status-pill">
                        <i data-lucide="{{ $stateIcon }}"></i>
                        {{ $stateLabel }}
                    </span>

                    <h1>{{ $doctorName }}</h1>
                    <p>{{ $doctorSpecialty }}</p>

                    <div class="docdeck-info-grid">
                        <div>
                            <i data-lucide="video"></i>
                            <span>نوع الاستشارة</span>
                            <strong>{{ $consultationType }}</strong>
                        </div>

                        <div>
                            <i data-lucide="mail"></i>
                            <span>البريد الإلكتروني</span>
                            <strong>{{ $doctorEmail }}</strong>
                        </div>

                        <div>
                            <i data-lucide="user-round"></i>
                            <span>الجنس</span>
                            <strong>{{ $doctorGender }}</strong>
                        </div>

                        <div>
                            <i data-lucide="badge-check"></i>
                            <span>سنوات الخبرة</span>
                            <strong>{{ $doctorExperience }}+ سنوات</strong>
                        </div>
                    </div>
                </div>
            </div>

            <aside class="docdeck-hero-side">
                <div class="docdeck-compact-status">
                    <i data-lucide="{{ $stateIcon }}"></i>

                    <div>
                        <strong>{{ $stateLabel }}</strong>
                        <span>{{ $stateText }}</span>
                    </div>
                </div>

                <div class="docdeck-hero-mini-stats">
                    <div>
                        <span>التوافق</span>
                        <strong>{{ $matchScore }}%</strong>
                    </div>

                    <div>
                        <span>التقييم</span>

                        @if ($hasReviews)
                            <strong>{{ $rating }}</strong>
                            <small>{{ $reviewsCount }} تقييم</small>
                        @else
                            <strong>—</strong>
                            <small>لا تقييمات</small>
                        @endif
                    </div>
                </div>
            </aside>
        </section>

        <section class="docdeck-actions">
            @if ($state === 'approved')
                <a href="{{ $appointmentRoute }}" class="docdeck-action is-primary">
                    <i data-lucide="calendar-days"></i>
                    <span>حجز موعد جديد</span>
                </a>

                <a href="{{ $messagesRoute }}" class="docdeck-action">
                    <i data-lucide="message-circle"></i>
                    <span>إرسال رسالة</span>
                </a>

                <a href="{{ $caloriesRoute }}" class="docdeck-action">
                    <i data-lucide="flame"></i>
                    <span>هدفك الغذائي</span>
                </a>

                <a href="{{ $detailsRoute }}" class="docdeck-action">
                    <i data-lucide="file-text"></i>
                    <span>الملف الكامل</span>
                </a>
            @elseif ($state === 'pending')
                <button type="button" class="docdeck-action is-disabled">
                    <i data-lucide="lock"></i>
                    <span>الحجز بعد الموافقة</span>
                </button>

                <button type="button" class="docdeck-action is-disabled">
                    <i data-lucide="lock"></i>
                    <span>الرسائل بعد الموافقة</span>
                </button>

                <a href="{{ $detailsRoute }}" class="docdeck-action">
                    <i data-lucide="file-text"></i>
                    <span>ملف الطبيب</span>
                </a>

                <a href="{{ $recommendedRoute }}" class="docdeck-action">
                    <i data-lucide="users"></i>
                    <span>الأطباء المناسبين</span>
                </a>
            @else
                <a href="{{ $recommendedRoute }}" class="docdeck-action is-primary">
                    <i data-lucide="users"></i>
                    <span>اختيار طبيب آخر</span>
                </a>

                <a href="{{ $detailsRoute }}" class="docdeck-action">
                    <i data-lucide="file-text"></i>
                    <span>تفاصيل الطبيب السابق</span>
                </a>
            @endif
        </section>

        <section class="docdeck-bio-panel">
            <div class="docdeck-bio-main">
                <span class="docdeck-kicker">About Doctor</span>
                <h2>نبذة عن طبيبك</h2>

                <p>{{ $doctorBio }}</p>
            </div>

            <aside class="docdeck-bio-side">
                <i data-lucide="sparkles"></i>
                <strong>لماذا هذا الطبيب مناسب لك؟</strong>
                <span>{{ $matchReason }}</span>
            </aside>
        </section>

        <section class="docdeck-main-grid">
            <article class="docdeck-note-card">
                <div class="docdeck-card-head">
                    <span>Doctor Note</span>
                    <h2>آخر ملاحظة من الدكتور</h2>
                </div>

                @if ($latestNoteText)
                    <div class="docdeck-quote-mark">“</div>

                    <p>{{ $latestNoteText }}</p>

                    <div class="docdeck-note-footer">
                        <span>{{ $latestNoteDate ? $latestNoteDate : 'تم تسجيل الملاحظة مؤخرًا' }}</span>
                        <i data-lucide="file-text"></i>
                    </div>
                @else
                    <div class="docdeck-empty-state">
                        <i data-lucide="clipboard-list"></i>
                        <strong>لا توجد ملاحظات من الطبيب بعد</strong>
                        <span>عندما يضيف الطبيب ملاحظة للمتابعة ستظهر هنا مباشرة.</span>
                    </div>
                @endif
            </article>

            <article class="docdeck-appointment-card">
                <div class="docdeck-card-head">
                    <span>Next Appointment</span>
                    <h2>الموعد القادم</h2>
                </div>

                @if ($state === 'approved' && !empty($nextAppointment))
                    <div class="docdeck-date-block">
                        <strong>{{ data_get($nextAppointment, 'date', '—') }}</strong>
                        <span>{{ data_get($nextAppointment, 'time', '—') }}</span>
                        <small>{{ data_get($nextAppointment, 'consultation_label', 'أونلاين') }}</small>
                    </div>
                @elseif ($state === 'approved')
                    <div class="docdeck-empty-state">
                        <i data-lucide="calendar-plus"></i>
                        <strong>لا يوجد موعد قادم</strong>
                        <span>احجز أول موعد لبدء المتابعة مع طبيبك.</span>
                    </div>
                @else
                    <div class="docdeck-empty-state">
                        <i data-lucide="lock"></i>
                        <strong>المواعيد غير مفعّلة</strong>
                        <span>سيتم تفعيلها بعد موافقة الطبيب.</span>
                    </div>
                @endif
            </article>
        </section>

        <section class="docdeck-lower-grid">
            <article class="docdeck-nutrition-card">
                <div class="docdeck-card-head">
                    <span>Nutrition Goal</span>
                    <h2>هدفك الغذائي اليومي</h2>
                </div>

                @if ($hasNutritionData)
                    <div class="docdeck-half-layout">
                        @if ($hasTargetCalories)
                            <div class="docdeck-half-gauge">
                                <svg viewBox="0 0 220 130" aria-hidden="true">
                                    <path
                                        class="docdeck-half-track"
                                        d="M 25 110 A 85 85 0 0 1 195 110"
                                        pathLength="267"
                                    />

                                    @if ($caloriePercent !== null)
                                        <path
                                            class="docdeck-half-progress"
                                            d="M 25 110 A 85 85 0 0 1 195 110"
                                            pathLength="267"
                                            style="stroke-dasharray: {{ $halfGaugeDash }} 267;"
                                        />
                                    @endif
                                </svg>

                                <div class="docdeck-half-content">
                                    <strong>{{ number_format((float) $targetCalories) }}</strong>
                                    <span>سعرة</span>

                                    @if ($caloriePercent !== null)
                                        <small>{{ $caloriePercent }}%</small>
                                    @else
                                        <small>هدف اليوم</small>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="docdeck-calorie-placeholder">
                                <i data-lucide="flame"></i>
                                <strong>لم يحدد هدف السعرات بعد</strong>
                                <span>سيظهر الهدف هنا بعد اعتماده من الطبيب.</span>
                            </div>
                        @endif

                        <div class="docdeck-calorie-side">
                            <div>
                                <span>المستهلك</span>
                                <strong>{{ $hasConsumedCalories ? number_format((float) $consumedCalories) : '—' }}</strong>
                            </div>

                            <div>
                                <span>المتبقي</span>
                                <strong>{{ is_numeric($remainingCalories) ? number_format((float) $remainingCalories) : '—' }}</strong>
                            </div>
                        </div>
                    </div>

                    <div class="docdeck-macro-list">
                        <div>
                            <span>بروتين</span>
                            <strong>{{ is_numeric($proteinTarget) ? $proteinTarget . 'g' : '—' }}</strong>
                        </div>

                        <div>
                            <span>دهون</span>
                            <strong>{{ is_numeric($fatTarget) ? $fatTarget . 'g' : '—' }}</strong>
                        </div>

                        <div>
                            <span>كربوهيدرات</span>
                            <strong>{{ is_numeric($carbsTarget) ? $carbsTarget . 'g' : '—' }}</strong>
                        </div>
                    </div>

                    <a href="{{ $caloriesRoute }}" class="docdeck-wide-link">
                        عرض التفاصيل
                        <i data-lucide="arrow-left"></i>
                    </a>
                @else
                    <div class="docdeck-empty-state">
                        <i data-lucide="flame"></i>
                        <strong>لا يوجد هدف غذائي لهذا اليوم</strong>
                        <span>عندما يحدد الطبيب هدف السعرات أو البروتين سيظهر هنا بشكل واضح.</span>
                    </div>
                @endif
            </article>

            <article class="docdeck-review-card">
                <div class="docdeck-card-head">
                    <span>Doctor Rating</span>
                    <h2>تقييمك لطبيبك</h2>
                </div>

                @if ($hasReviews)
                    <p class="docdeck-review-summary">
                        متوسط التقييم الحالي {{ $rating }} من 5 بناءً على {{ $reviewsCount }} تقييم.
                    </p>
                @else
                    <p class="docdeck-review-summary">
                        لا توجد تقييمات بعد. يمكنك إضافة تقييمك بعد اعتماد المتابعة.
                    </p>
                @endif

                @if ($canSendReview)
                    <form method="POST" action="{{ $reviewRoute }}" class="docdeck-star-form" data-star-review>
                        @csrf

                        <input type="hidden" name="rating" value="{{ $currentRating }}" data-rating-input required>

                        <div class="docdeck-stars" dir="ltr">
                            @for ($i = 1; $i <= 5; $i++)
                                <button
                                    type="button"
                                    class="docdeck-star {{ $currentRating >= $i ? 'is-active' : '' }}"
                                    data-star="{{ $i }}"
                                    aria-label="{{ $i }} نجوم"
                                >
                                    <i data-lucide="star"></i>
                                </button>
                            @endfor
                        </div>

                        <small data-rating-text>
                            @if ($currentRating > 0)
                                تقييمك الحالي: {{ $currentRating }} من 5
                            @else
                                اضغط على النجوم لاختيار التقييم
                            @endif
                        </small>

                        <textarea
                            name="comment"
                            rows="4"
                            placeholder="اكتب ملاحظتك عن تجربة المتابعة..."
                        >{{ old('comment', data_get($myReview, 'comment')) }}</textarea>

                        <button type="submit">
                            <i data-lucide="send"></i>
                            {{ $myReview ? 'تحديث التقييم' : 'إضافة تقييم جديد' }}
                        </button>
                    </form>
                @else
                    <div class="docdeck-empty-state">
                        <i data-lucide="lock"></i>
                        <strong>التقييم غير متاح الآن</strong>
                        <span>{{ $state === 'approved' ? 'تأكدي من Route حفظ التقييم.' : 'يمكنك التقييم بعد موافقة الطبيب.' }}</span>
                    </div>
                @endif
            </article>
        </section>

        <section class="docdeck-privacy">
            <i data-lucide="shield-check"></i>
            <span>محادثاتك وملاحظاتك الطبية محفوظة بسرية داخل اتزان.</span>
        </section>
    @endif
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-star-review]').forEach(function (form) {
        const input = form.querySelector('[data-rating-input]');
        const text = form.querySelector('[data-rating-text]');
        const stars = form.querySelectorAll('[data-star]');

        function paint(value) {
            stars.forEach(function (star) {
                star.classList.toggle('is-active', Number(star.dataset.star) <= value);
            });

            if (text) {
                text.textContent = value > 0
                    ? 'تقييمك الحالي: ' + value + ' من 5'
                    : 'اضغط على النجوم لاختيار التقييم';
            }
        }

        stars.forEach(function (star) {
            star.addEventListener('click', function () {
                const value = Number(star.dataset.star);
                input.value = value;
                paint(value);
            });

            star.addEventListener('mouseenter', function () {
                paint(Number(star.dataset.star));
            });
        });

        form.querySelector('.docdeck-stars')?.addEventListener('mouseleave', function () {
            paint(Number(input.value || 0));
        });

        paint(Number(input.value || 0));
    });
});
</script>
@endpush
