@php
    $form = $profileForm ?? [];
    $patientData = $patient ?? [];
    $doctorData = $doctor ?? [];
    $statusData = $profileStatus ?? [];

    $completion = (int) (
        data_get($patientData, 'profile_completion')
        ?? $profileCompletion
        ?? data_get($form, 'profile_completion')
        ?? data_get($form, 'completion_percentage')
        ?? 100
    );

    $completion = max(0, min(100, $completion));

    $doctorIsSelected = (bool) data_get($doctorData, 'is_selected', false);

    $doctorRequestStatus =
        data_get($doctorData, 'request_status')
        ?? data_get($patientData, 'doctor_request_status')
        ?? null;

    $statusLabel = match ($doctorRequestStatus) {
        'pending' => 'بانتظار موافقة الطبيب',
        'approved' => 'تمت موافقة الطبيب',
        'rejected', 'declined' => 'تم رفض طلب المتابعة',
        default => $doctorIsSelected ? 'بانتظار موافقة الطبيب' : 'لم يتم اختيار طبيب بعد',
    };

    $statusTitle = match ($doctorRequestStatus) {
        'pending' => 'طلب المتابعة قيد المراجعة',
        'approved' => 'طبيبك وافق على المتابعة',
        'rejected', 'declined' => 'الطبيب اعتذر عن المتابعة',
        default => $doctorIsSelected ? 'طلب المتابعة قيد المراجعة' : 'اختر الطبيب المناسب لحالتك',
    };

    $statusIcon = match ($doctorRequestStatus) {
        'pending' => 'clock-3',
        'approved' => 'badge-check',
        'rejected', 'declined' => 'circle-alert',
        default => $doctorIsSelected ? 'clock-3' : 'stethoscope',
    };

    $healthGoalLabels = [
        'healthy_lifestyle' => 'تحسين نمط الحياة',
        'diabetes_management' => 'تنظيم السكر',
        'hypertension_management' => 'تنظيم الضغط',
        'cholesterol_management' => 'تحسين الكوليسترول',
        'chronic_care' => 'متابعة حالة مزمنة',
        'therapeutic_nutrition' => 'تغذية علاجية',
        'weight_loss' => 'خسارة وزن',
        'weight_gain' => 'زيادة وزن صحية',
    ];

    $activityLabels = [
        'low' => 'قليل الحركة',
        'light' => 'نشاط خفيف',
        'moderate' => 'نشاط متوسط',
        'high' => 'نشاط عالي',
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
        'pcos' => 'تكيس',
        'pregnancy' => 'حمل أو رضاعة',
        'digestive' => 'مشاكل هضمية',
        'anemia' => 'أنيميا',
        'food_allergy' => 'حساسية غذائية',
    ];

    $conditions = data_get($form, 'medical_conditions', []);

    if (!is_array($conditions)) {
        $decodedConditions = json_decode((string) $conditions, true);
        $conditions = is_array($decodedConditions) ? $decodedConditions : [];
    }

    $conditions = array_values(array_filter($conditions));

    $patientName =
        data_get($patientData, 'name')
        ?? data_get($form, 'full_name')
        ?? data_get($form, 'name')
        ?? data_get(auth()->user(), 'name')
        ?? 'مريض اتزان';

    $patientInitial = mb_substr($patientName, 0, 1, 'UTF-8');

    $patientAvatar =
        data_get($patientData, 'avatar')
        ?? data_get($form, 'avatar')
        ?? data_get($form, 'photo')
        ?? data_get(auth()->user(), 'avatar')
        ?? data_get(auth()->user(), 'profile_photo_path')
        ?? null;

    $patientAvatarUrl = null;

    if (!empty($patientAvatar)) {
        $patientAvatarUrl = \Illuminate\Support\Str::startsWith($patientAvatar, ['http://', 'https://'])
            ? $patientAvatar
            : asset('storage/' . ltrim($patientAvatar, '/'));
    }

    $height = data_get($form, 'height_cm') ?? data_get($form, 'height');
    $weight = data_get($form, 'weight_kg') ?? data_get($form, 'weight');

    $birthDate =
        data_get($form, 'birth_date')
        ?? data_get($form, 'date_of_birth')
        ?? data_get($patientData, 'birth_date')
        ?? data_get($patientData, 'date_of_birth');

    $phone =
        data_get($form, 'phone')
        ?? data_get($form, 'mobile')
        ?? data_get($form, 'phone_number')
        ?? data_get($patientData, 'phone')
        ?? data_get($patientData, 'mobile')
        ?? data_get(auth()->user(), 'phone');

    $city =
        data_get($form, 'city')
        ?? data_get($form, 'address')
        ?? data_get($patientData, 'city')
        ?? data_get($patientData, 'address');

    $bmi = null;

    if (is_numeric($height) && is_numeric($weight) && (float) $height > 0) {
        $heightMeter = ((float) $height) / 100;
        $bmi = round(((float) $weight) / ($heightMeter * $heightMeter), 1);
    }

    $bmiLabel = match (true) {
        is_null($bmi) => 'غير محسوب',
        $bmi < 18.5 => 'أقل من الطبيعي',
        $bmi < 25 => 'طبيعي',
        $bmi < 30 => 'زيادة وزن',
        default => 'سمنة',
    };

    $genderLabel = match (data_get($form, 'gender') ?? data_get($patientData, 'gender')) {
        'female', 'أنثى' => 'أنثى',
        'male', 'ذكر' => 'ذكر',
        default => 'غير محدد',
    };

    $healthGoal = data_get($form, 'health_goal') ?? data_get($form, 'goal');
    $activityLevel = data_get($form, 'activity_level');

    $displayValue = function ($value, $fallback = 'غير مسجل') {
        return filled($value) ? $value : $fallback;
    };

    $doctorAvatar =
        data_get($doctorData, 'avatar')
        ?? data_get($doctorData, 'photo')
        ?? data_get($doctorData, 'image')
        ?? null;

    $doctorAvatarUrl = null;

    if (!empty($doctorAvatar)) {
        $doctorAvatarUrl = \Illuminate\Support\Str::startsWith($doctorAvatar, ['http://', 'https://'])
            ? $doctorAvatar
            : asset('storage/' . ltrim($doctorAvatar, '/'));
    }

    $doctorsUrl = Route::has('patient.doctors.recommended')
        ? route('patient.doctors.recommended')
        : route('patient.profile');

    $editUrl = route('patient.profile', ['edit' => 1]);
@endphp

<section class="ps-page-premium">
    @if (session('success'))
        <div class="ps-alert is-success">
            <i data-lucide="badge-check"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="ps-alert is-error">
            <i data-lucide="circle-alert"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <section class="ps-hero-modern ps-clean-card ps-patient-hero">
        <div class="ps-profile-photo-large">
            @if (!empty($patientAvatarUrl))
                <img src="{{ $patientAvatarUrl }}" alt="{{ $patientName }}">
            @else
                <span>{{ $patientInitial }}</span>
            @endif

            <b class="ps-photo-check">
                <i data-lucide="check"></i>
            </b>
        </div>

        <div class="ps-hero-content">
            <span class="ps-kicker">
                <i data-lucide="shield-heart"></i>
                ملفك الصحي
            </span>

            <h1>{{ $patientName }}</h1>

            <div class="ps-hero-meta">
                <span>{{ $genderLabel }}</span>
                <span>{{ $healthGoalLabels[$healthGoal] ?? $displayValue($healthGoal) }}</span>
                <span>{{ $statusLabel }}</span>
            </div>

            <p>
                هنا تظهر بياناتك الصحية التي أدخلتها، وحالة طلب الطبيب، والخطوة التالية في رحلتك داخل اتزان.
            </p>

            <div class="ps-hero-actions">
                <a href="{{ $editUrl }}" class="ps-outline-btn">
                    <i data-lucide="pencil"></i>
                    تعديل البيانات
                </a>

                @if (!$doctorIsSelected || in_array($doctorRequestStatus, ['rejected', 'declined'], true))
                    <a href="{{ $doctorsUrl }}" class="ps-main-action">
                        <i data-lucide="stethoscope"></i>
                        اختيار طبيب
                    </a>
                @elseif ($doctorRequestStatus === 'approved')
                    <a href="{{ route('patient.followup') }}" class="ps-main-action">
                        <i data-lucide="calendar-days"></i>
                        حجز موعد
                    </a>
                @else
                    <span class="ps-outline-btn">
                        <i data-lucide="clock-3"></i>
                        بانتظار موافقة الطبيب
                    </span>
                @endif
            </div>
        </div>

        <aside class="ps-hero-status">
            <div class="ps-status-ring">
                <strong>{{ $completion }}%</strong>
                <span>اكتمال الملف</span>
            </div>

            <div class="ps-status-mini">
                <i data-lucide="{{ $statusIcon }}"></i>
                <div>
                    <strong>{{ $statusLabel }}</strong>
                    <small>{{ $statusTitle }}</small>
                </div>
            </div>
        </aside>
    </section>

    <section class="ps-top-grid">
        <div class="ps-summary-card ps-clean-card">
            <div class="ps-card-head">
                <div>
                    <span>Health Snapshot</span>
                    <h2>ملخص بياناتك الصحية</h2>
                </div>

                <a href="{{ $editUrl }}" class="ps-soft-link">
                    <i data-lucide="pencil"></i>
                    تعديل
                </a>
            </div>

            <div class="ps-metrics-modern">
                <div>
                    <i data-lucide="ruler"></i>
                    <span>الطول</span>
                    <strong>{{ $displayValue($height) }} {{ filled($height) ? 'سم' : '' }}</strong>
                </div>

                <div>
                    <i data-lucide="scale"></i>
                    <span>الوزن</span>
                    <strong>{{ $displayValue($weight) }} {{ filled($weight) ? 'كغ' : '' }}</strong>
                </div>

                <div>
                    <i data-lucide="gauge"></i>
                    <span>مؤشر الكتلة</span>
                    <strong>{{ $bmi ? $bmi . ' - ' . $bmiLabel : 'غير محسوب' }}</strong>
                </div>

                <div>
                    <i data-lucide="target"></i>
                    <span>الهدف الصحي</span>
                    <strong>{{ $healthGoalLabels[$healthGoal] ?? $displayValue($healthGoal) }}</strong>
                </div>
            </div>

            <div class="ps-health-tags">
                <span class="ps-tags-title">الحالات الصحية المسجلة</span>

                <div>
                    @forelse ($conditions as $condition)
                        <span>{{ $conditionLabels[$condition] ?? $condition }}</span>
                    @empty
                        <span>لا توجد حالات مسجلة</span>
                    @endforelse
                </div>
            </div>
        </div>

        <aside class="ps-next-card ps-clean-card">
            <div class="ps-next-compact">
                <div class="ps-next-icon">
                    <i data-lucide="user-round-check"></i>
                </div>

                <div class="ps-next-content">
                    <span>طبيب المتابعة</span>

                    @if ($doctorIsSelected)
                        <h2>{{ data_get($doctorData, 'name', 'طبيب اتزان') }}</h2>

                        <p>
                            {{ data_get($doctorData, 'specialty', 'استشاري صحي') }}
                            <br>
                            الحالة الحالية: {{ $statusLabel }}
                        </p>

                        <div class="ps-current-doctor">
                            @if (!empty($doctorAvatarUrl))
                                <img src="{{ $doctorAvatarUrl }}" alt="{{ data_get($doctorData, 'name', 'الطبيب') }}">
                            @else
                                <span class="ps-current-doctor-avatar">
                                    <i data-lucide="stethoscope"></i>
                                </span>
                            @endif

                            <div>
                                <strong>{{ data_get($doctorData, 'name', 'طبيب اتزان') }}</strong>
                                <small>{{ $statusLabel }}</small>
                            </div>
                        </div>
                    @else
                        <h2>لم يتم اختيار طبيب بعد</h2>
                        <p>اختر الطبيب المناسب حتى يتم إرسال طلب المتابعة.</p>

                        <a href="{{ $doctorsUrl }}" class="ps-main-action">
                            <i data-lucide="stethoscope"></i>
                            اختيار طبيب
                        </a>
                    @endif
                </div>
            </div>
        </aside>
    </section>

    <section class="ps-details-modern">
        <article class="ps-detail-card ps-clean-card">
            <div class="ps-detail-head">
                <span><i data-lucide="id-card"></i></span>
                <div>
                    <small>Basic Info</small>
                    <h3>البيانات الأساسية</h3>
                </div>
            </div>

            <div class="ps-basic-grid">
                <div class="ps-basic-item">
                    <i data-lucide="user-round"></i>
                    <span>الاسم</span>
                    <strong>{{ $displayValue($patientName) }}</strong>
                </div>

                <div class="ps-basic-item">
                    <i data-lucide="calendar-days"></i>
                    <span>تاريخ الميلاد</span>
                    <strong>{{ $displayValue($birthDate) }}</strong>
                </div>

                <div class="ps-basic-item">
                    <i data-lucide="venus-and-mars"></i>
                    <span>الجنس</span>
                    <strong>{{ $genderLabel }}</strong>
                </div>

                <div class="ps-basic-item">
                    <i data-lucide="phone"></i>
                    <span>رقم الجوال</span>
                    <strong>{{ $displayValue($phone) }}</strong>
                </div>

                <div class="ps-basic-item ps-basic-item-wide">
                    <i data-lucide="map-pinned"></i>
                    <span>المدينة</span>
                    <strong>{{ $displayValue($city) }}</strong>
                </div>
            </div>
        </article>

        <article class="ps-detail-card ps-clean-card">
            <div class="ps-detail-head">
                <span><i data-lucide="activity"></i></span>
                <div>
                    <small>Lifestyle</small>
                    <h3>نمط الحياة</h3>
                </div>
            </div>

            <div class="ps-lifestyle-grid">
                <div>
                    <i data-lucide="activity"></i>
                    <span>مستوى النشاط</span>
                    <strong>{{ $activityLabels[$activityLevel] ?? $displayValue($activityLevel) }}</strong>
                </div>

                <div>
                    <i data-lucide="utensils"></i>
                    <span>عدد الوجبات</span>
                    <strong>{{ $displayValue(data_get($form, 'meals_per_day')) }}</strong>
                </div>

                <div>
                    <i data-lucide="moon"></i>
                    <span>ساعات النوم</span>
                    <strong>{{ $displayValue(data_get($form, 'sleep_hours')) }}</strong>
                </div>

                <div>
                    <i data-lucide="droplet"></i>
                    <span>أكواب الماء</span>
                    <strong>{{ $displayValue(data_get($form, 'water_cups')) }}</strong>
                </div>
            </div>
        </article>

        <article class="ps-detail-card ps-detail-card-wide ps-clean-card">
            <div class="ps-detail-head">
                <span><i data-lucide="clipboard-plus"></i></span>
                <div>
                    <small>Medical Notes</small>
                    <h3>الملاحظات الصحية</h3>
                </div>
            </div>

            <div class="ps-detail-list">
                <div>
                    <strong>الأدوية الحالية</strong>
                    <p>{{ $displayValue(data_get($form, 'medications'), 'لا توجد أدوية مسجلة') }}</p>
                </div>

                <div>
                    <strong>الحساسية أو الأطعمة الممنوعة</strong>
                    <p>{{ $displayValue(data_get($form, 'allergies'), 'لا توجد حساسية مسجلة') }}</p>
                </div>

                <div>
                    <strong>ملاحظات إضافية</strong>
                    <p class="ps-notes-text">{{ $displayValue(data_get($form, 'notes'), 'لا توجد ملاحظات إضافية') }}</p>
                </div>
            </div>
        </article>
    </section>
</section>
