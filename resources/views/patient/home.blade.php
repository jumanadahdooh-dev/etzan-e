@extends('layouts.patient')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/patient/home.css') }}">
@endpush


@section('content')
@php
    /* ==================================================
       1) Incoming Data Defaults
       ملاحظة: هذا الملف لا يغير أي منطق في الكنترول.
       فقط يقرأ البيانات القادمة من قاعدة البيانات/الكنترول.
    ================================================== */
    $incomingProfileStatus = $profileStatus ?? [];
    $nextAppointment = $nextAppointment ?? null;
    $patientNotifications = $patientNotifications ?? [];
    $messages = $messages ?? [];
    $homeTasks = $homeTasks ?? [];
    $homeStats = $homeStats ?? [];
    $articles = $articles ?? [];
    $dailyWisdom = $dailyWisdom ?? [];
    $dailyMotivation = $dailyMotivation ?? [];
    $chart = $chart ?? [];
    $homeProgress = $homeProgress ?? [];
    $aiCalories = $aiCalories ?? [];
    $patient = $patient ?? [];

    /* ==================================================
       2) Patient State Logic
       نفس منطق الصفحة القديمة: الملف، الطبيب، الموعد.
    ================================================== */
    $statusCompletion = (int) (
        $patient['profile_completion']
        ?? ($incomingProfileStatus['completion'] ?? 0)
    );

    $statusCompletion = max(0, min(100, $statusCompletion));

    $isProfileComplete = $statusCompletion >= 100;
    $hasSelectedDoctor = $isProfileComplete && !empty($patient['has_selected_doctor']);

    $doctorRequestStatus = $patient['doctor_request_status'] ?? null;
    $isDoctorApproved = $hasSelectedDoctor && !empty($patient['is_doctor_approved']);

    $appointmentStatus = $patient['appointment_status'] ?? ($nextAppointment['status'] ?? null);
    $hasStartedFollowup = !empty($patient['has_started_followup']);

    $isAppointmentPending = $appointmentStatus === 'pending';
    $isAppointmentConfirmed = in_array($appointmentStatus, ['confirmed', 'approved'], true);
    $isAppointmentRescheduleRequested = $appointmentStatus === 'reschedule_requested';
    $isAppointmentRejected = in_array($appointmentStatus, ['rejected', 'patient_declined_reschedule'], true);

    if (!$isProfileComplete) {
        $statusVariant = 'incomplete';
    } elseif (!$hasSelectedDoctor) {
        $statusVariant = 'doctor';
    } elseif (!$isDoctorApproved) {
        $statusVariant = 'doctor_waiting';
    } elseif (!$hasStartedFollowup) {
        $statusVariant = 'appointment';
    } elseif ($isAppointmentPending) {
        $statusVariant = 'appointment_pending';
    } elseif ($isAppointmentRescheduleRequested) {
        $statusVariant = 'reschedule_requested';
    } elseif ($isAppointmentRejected) {
        $statusVariant = 'appointment_rejected';
    } else {
        $statusVariant = 'ready';
    }

    /* ==================================================
       3) Hero Content By State
    ================================================== */
    $defaultMissingFields = [
        ['label' => 'الوزن', 'icon' => 'scale'],
        ['label' => 'الطول', 'icon' => 'ruler'],
        ['label' => 'الهدف الصحي', 'icon' => 'target'],
        ['label' => 'الحالة الصحية', 'icon' => 'heart-pulse'],
    ];

    $profileStatus = match ($statusVariant) {
        'doctor' => [
            'eyebrow' => 'ملف صحي مكتمل',
            'title' => 'بقي اختيار الطبيب المناسب لحالتك',
            'body' => 'تم حفظ ملفك الصحي. اختاري الطبيب المناسب لتبدأ رحلة المتابعة بشكل منظم.',
            'cta' => 'اختيار الطبيب',
            'url' => route('patient.doctors.recommended'),
            'missing_fields' => [],
            'secondary_cta' => null,
            'icon' => 'stethoscope',
        ],
        'doctor_waiting' => [
            'eyebrow' => 'بانتظار موافقة الطبيب',
            'title' => 'طلب المتابعة قيد المراجعة',
            'body' => 'تم إرسال طلب اختيار الطبيب. سيتم تفعيل الحجز والاستشارة بعد موافقة الطبيب.',
            'cta' => 'عرض ملفي الصحي',
            'url' => route('patient.profile'),
            'missing_fields' => [],
            'secondary_cta' => null,
            'icon' => 'clock-3',
        ],
        'appointment' => [
            'eyebrow' => 'الطبيب وافق',
            'title' => 'احجزي أول موعد لبدء المتابعة',
            'body' => 'تم اعتماد طبيبك. الخطوة التالية هي حجز موعدك الأول داخل اتزان.',
            'cta' => 'حجز موعد',
            'url' => route('patient.followup'),
            'missing_fields' => [],
            'secondary_cta' => null,
            'icon' => 'calendar-days',
        ],
        'appointment_pending' => [
            'eyebrow' => 'طلب موعد مرسل',
            'title' => 'موعدك بانتظار تأكيد الطبيب',
            'body' => 'تم إرسال طلب الموعد للطبيب. ستصلك إشعارات عند التأكيد أو اقتراح وقت آخر.',
            'cta' => 'عرض الموعد',
            'url' => route('patient.followup'),
            'missing_fields' => [],
            'secondary_cta' => null,
            'icon' => 'calendar-clock',
        ],
        'reschedule_requested' => [
            'eyebrow' => 'اقتراح موعد جديد',
            'title' => 'الطبيب اقترح وقتًا آخر',
            'body' => 'راجعي الموعد المقترح، ثم اختاري قبول الموعد الجديد أو رفض الاقتراح.',
            'cta' => 'مراجعة الاقتراح',
            'url' => route('patient.followup'),
            'missing_fields' => [],
            'secondary_cta' => null,
            'icon' => 'calendar-clock',
        ],
        'appointment_rejected' => [
            'eyebrow' => 'تعذر تأكيد الموعد',
            'title' => 'لم يتم اعتماد هذا الموعد',
            'body' => 'يمكنك حجز موعد آخر أو مراجعة الإشعارات لمعرفة سبب الرفض.',
            'cta' => 'حجز موعد آخر',
            'url' => route('patient.followup'),
            'missing_fields' => [],
            'secondary_cta' => null,
            'icon' => 'circle-alert',
        ],
        'ready' => [
            'eyebrow' => 'جاهزة لليوم',
            'title' => $isAppointmentConfirmed ? 'موعدك مؤكد ورحلتك واضحة' : 'رحلتك الصحية اليوم مرتبة',
            'body' => $isAppointmentConfirmed
                ? 'تابعي مهامك اليومية واستعدي للموعد القادم مع الطبيب.'
                : 'تابعي تقدمك، راجعي السعرات، وشوفي آخر تحديثات الطبيب من مكان واحد.',
            'cta' => 'متابعة المهام',
            'url' => route('patient.journey'),
            'missing_fields' => [],
            'secondary_cta' => null,
            'icon' => 'sparkles',
        ],
        default => [
            'eyebrow' => 'ملفك يحتاج تحديث',
            'title' => 'أكملي ملفك الصحي حتى نخصص تجربتك',
            'body' => 'كلما كانت بياناتك أدق، أصبحت التوصيات واختيار الطبيب والخطة اليومية مناسبة أكثر لحالتك.',
            'cta' => 'إكمال الملف',
            'url' => route('patient.profile', ['edit' => 1]),
            'missing_fields' => $incomingProfileStatus['missing_fields'] ?? $defaultMissingFields,
            'secondary_cta' => 'لماذا نحتاج هذه البيانات؟',
            'icon' => 'clipboard-check',
        ],
    };

    $statusIcon = $profileStatus['icon'] ?? 'clipboard-check';

    /* ==================================================
       4) Activation Steps
    ================================================== */
    $activationSteps = [
        [
            'title' => 'إكمال الملف الصحي',
            'body' => 'إضافة الوزن، الطول، الهدف، والحالة الصحية.',
            'icon' => 'clipboard-check',
            'done' => $isProfileComplete,
            'url' => route('patient.profile', ['edit' => 1]),
        ],
        [
            'title' => 'اختيار الطبيب',
            'body' => 'اختيار الطبيب المناسب للمتابعة.',
            'icon' => 'stethoscope',
            'done' => $hasSelectedDoctor,
            'url' => route('patient.doctors.recommended'),
        ],
        [
            'title' => 'موافقة الطبيب',
            'body' => 'اعتماد طلب المتابعة من الطبيب المختار.',
            'icon' => 'badge-check',
            'done' => $isDoctorApproved,
            'url' => route('patient.profile'),
        ],
        [
            'title' => 'طلب أول موعد',
            'body' => 'إرسال طلب موعد لبدء المتابعة.',
            'icon' => 'calendar-clock',
            'done' => $hasStartedFollowup,
            'url' => route('patient.followup'),
        ],
    ];

    $completedActivationSteps = collect($activationSteps)->where('done', true)->count();
    $activationProgress = (int) round(($completedActivationSteps / count($activationSteps)) * 100);
    $activationProgress = max(0, min(100, $activationProgress));

    if (!$isProfileComplete) {
        $currentStepIndex = 0;
    } elseif (!$hasSelectedDoctor) {
        $currentStepIndex = 1;
    } elseif (!$isDoctorApproved) {
        $currentStepIndex = 2;
    } else {
        $currentStepIndex = 3;
    }

    /* ==================================================
       5) Appointment + Progress Labels
    ================================================== */
    $appointmentStatusLabel = $nextAppointment['status_label'] ?? match ($appointmentStatus) {
        'pending' => 'بانتظار التأكيد',
        'confirmed', 'approved' => 'مؤكد',
        'reschedule_requested' => 'اقتراح موعد جديد',
        'rejected' => 'مرفوض',
        'patient_declined_reschedule' => 'تم رفض الاقتراح',
        default => 'لا يوجد موعد',
    };

    $taskProgressValue = (int) ($homeProgress['percentage'] ?? 0);
    $taskProgressValue = max(0, min(100, $taskProgressValue));

    /* ==================================================
       6) Calories Summary
       مختصر فقط في الرئيسية. لا يوجد فورم تحليل وجبة هنا.
    ================================================== */
    $caloriesConsumed = (int) (
        $aiCalories['consumed']
        ?? $aiCalories['today_calories']
        ?? $aiCalories['calories_today']
        ?? $aiCalories['total_today']
        ?? 0
    );

    $calorieTarget = (int) (
        $aiCalories['target']
        ?? $aiCalories['daily_goal']
        ?? $aiCalories['goal']
        ?? ($patient['daily_calorie_goal'] ?? 0)
    );

    $caloriesRemaining = $calorieTarget > 0
        ? max(0, $calorieTarget - $caloriesConsumed)
        : null;

    $caloriesProgress = $calorieTarget > 0
        ? max(0, min(100, (int) round(($caloriesConsumed / $calorieTarget) * 100)))
        : 0;

    $lastMeal = $aiCalories['last_meal'] ?? $aiCalories['latest_meal'] ?? null;

    $lastMealTitle = is_array($lastMeal)
        ? ($lastMeal['title'] ?? $lastMeal['name'] ?? $lastMeal['meal_name'] ?? null)
        : $lastMeal;

    /* ==================================================
       7) Health Data Index
       مؤشر بصري فقط، ولا يغير أي بيانات في قاعدة البيانات.
    ================================================== */
    $doctorSignalValue = $isDoctorApproved ? 100 : ($hasSelectedDoctor ? 65 : 20);
    $appointmentSignalValue = $isAppointmentConfirmed ? 100 : ($hasStartedFollowup ? 60 : 25);

    $dataIndexScore = (int) round(
        ($statusCompletion * 0.42)
        + ($activationProgress * 0.22)
        + ($taskProgressValue * 0.18)
        + ($doctorSignalValue * 0.10)
        + ($appointmentSignalValue * 0.08)
    );

    $dataIndexScore = max(0, min(100, $dataIndexScore));

    $dataIndexLabel = match (true) {
        $dataIndexScore >= 90 => 'بيانات ممتازة',
        $dataIndexScore >= 75 => 'بيانات قوية',
        $dataIndexScore >= 55 => 'بيانات متوسطة',
        default => 'تحتاج تحديث',
    };

    $dataIndexTone = match (true) {
        $dataIndexScore >= 90 => 'is-excellent',
        $dataIndexScore >= 75 => 'is-good',
        $dataIndexScore >= 55 => 'is-medium',
        default => 'is-low',
    };

    $summaryCards = [
        [
            'label' => 'اكتمال الملف',
            'value' => $statusCompletion . '%',
            'hint' => $isProfileComplete ? 'مكتمل' : 'يحتاج تحديث',
            'icon' => 'clipboard-check',
            'progress' => $statusCompletion,
            'url' => route('patient.profile'),
        ],
        [
            'label' => 'الموعد',
            'value' => $appointmentStatusLabel,
            'hint' => !empty($nextAppointment) ? (($nextAppointment['date'] ?? '') . ' ' . ($nextAppointment['time'] ?? '')) : 'لا يوجد موعد نشط',
            'icon' => 'calendar-check',
            'progress' => $appointmentSignalValue,
            'url' => route('patient.followup'),
        ],
        [
            'label' => 'مهام اليوم',
            'value' => $taskProgressValue . '%',
            'hint' => ($homeProgress['completed'] ?? 0) . ' من ' . ($homeProgress['total'] ?? 0) . ' مهام',
            'icon' => 'list-checks',
            'progress' => $taskProgressValue,
            'url' => route('patient.journey'),
        ],
        [
            'label' => 'مؤشر البيانات',
            'value' => $dataIndexScore . '%',
            'hint' => $dataIndexLabel,
            'icon' => 'activity',
            'progress' => $dataIndexScore,
            'url' => route('patient.profile'),
        ],
    ];

    /* ==================================================
       8) Real Chart Data Guard
       لا نعرض رسم وهمي. إذا لم تصل بيانات حقيقية، نعرض Empty State.
    ================================================== */
    $chartLabels = is_array($chart['labels'] ?? null) ? $chart['labels'] : [];
    $rawSeries = $chart['series'] ?? $chart['data'] ?? [];
    $firstSeries = [];

    if (is_array($rawSeries) && !empty($rawSeries)) {
        $first = array_values($rawSeries)[0] ?? [];

        if (is_array($first) && isset($first['values']) && is_array($first['values'])) {
            $firstSeries = $first['values'];
        } elseif (is_array($first)) {
            $firstSeries = $first;
        } elseif (collect($rawSeries)->every(fn ($value) => is_numeric($value))) {
            $firstSeries = $rawSeries;
        }
    }

    $firstSeries = collect($firstSeries)
        ->filter(fn ($value) => is_numeric($value))
        ->map(fn ($value) => (float) $value)
        ->values()
        ->toArray();

    $chartHasRealData = count($firstSeries) > 0;
    $chartMax = $chartHasRealData ? max($firstSeries) : 0;
    $chartMax = $chartMax > 0 ? $chartMax : 1;

    /* ==================================================
       9) Smart Dashboard Replacement Cards
       هذه المتغيرات للعرض فقط، ولا تحفظ أو تعدل أي بيانات.
       الهدف: إزالة تكرار الإشعارات والرسائل من الصفحة الرئيسية.
    ================================================== */

    /* ---------- 9.1 Priority Today ---------- */
    if (!$isProfileComplete) {
        $todayPriority = [
            'eyebrow' => 'أولوية اليوم',
            'title' => 'أكملي ملفك الصحي',
            'body' => 'إكمال البيانات الأساسية يجعل التوصيات واختيار الطبيب أدق.',
            'icon' => 'clipboard-check',
            'url' => route('patient.profile', ['edit' => 1]),
            'cta' => 'إكمال الملف',
            'tone' => 'is-warning',
        ];
    } elseif (!$hasSelectedDoctor) {
        $todayPriority = [
            'eyebrow' => 'أولوية اليوم',
            'title' => 'اختاري الطبيب المناسب',
            'body' => 'بعد اختيار الطبيب تبدأ رحلة المتابعة بشكل واضح داخل اتزان.',
            'icon' => 'stethoscope',
            'url' => route('patient.doctors.recommended'),
            'cta' => 'اختيار الطبيب',
            'tone' => 'is-info',
        ];
    } elseif (!$isDoctorApproved) {
        $todayPriority = [
            'eyebrow' => 'أولوية اليوم',
            'title' => 'طلبك قيد مراجعة الطبيب',
            'body' => 'سيتم فتح الحجز والتذكيرات بعد موافقة الطبيب على المتابعة.',
            'icon' => 'clock-3',
            'url' => route('patient.profile'),
            'cta' => 'عرض الملف',
            'tone' => 'is-info',
        ];
    } elseif (!$hasStartedFollowup) {
        $todayPriority = [
            'eyebrow' => 'أولوية اليوم',
            'title' => 'احجزي أول موعد',
            'body' => 'ابدئي المتابعة بحجز أول موعد مع طبيبك.',
            'icon' => 'calendar-plus',
            'url' => route('patient.followup'),
            'cta' => 'حجز موعد',
            'tone' => 'is-primary',
        ];
    } elseif ($isAppointmentPending) {
        $todayPriority = [
            'eyebrow' => 'أولوية اليوم',
            'title' => 'تابعي حالة طلب الموعد',
            'body' => 'موعدك بانتظار تأكيد الطبيب، وستظهر التحديثات في صفحة المواعيد والجرس.',
            'icon' => 'calendar-clock',
            'url' => route('patient.followup'),
            'cta' => 'عرض الموعد',
            'tone' => 'is-info',
        ];
    } elseif ($isAppointmentRescheduleRequested) {
        $todayPriority = [
            'eyebrow' => 'أولوية اليوم',
            'title' => 'راجعي الموعد المقترح',
            'body' => 'الطبيب اقترح وقتًا آخر. يمكنك قبول الموعد أو رفض الاقتراح.',
            'icon' => 'calendar-clock',
            'url' => route('patient.followup'),
            'cta' => 'مراجعة الاقتراح',
            'tone' => 'is-warning',
        ];
    } elseif (!empty($nextAppointment) && $isAppointmentConfirmed) {
        $todayPriority = [
            'eyebrow' => 'أولوية اليوم',
            'title' => 'استعدي لموعدك القادم',
            'body' => 'راجعي وقت الموعد ورابط اللقاء، وستصلك التذكيرات تلقائيًا قبل الموعد.',
            'icon' => 'calendar-check',
            'url' => route('patient.followup'),
            'cta' => 'فتح الموعد',
            'tone' => 'is-primary',
        ];
    } elseif ($caloriesConsumed <= 0) {
        $todayPriority = [
            'eyebrow' => 'أولوية اليوم',
            'title' => 'سجّلي أول وجبة اليوم',
            'body' => 'تسجيل الوجبات يساعد على بناء ملخص سعرات حقيقي ودقيق.',
            'icon' => 'flame',
            'url' => route('patient.calories'),
            'cta' => 'تحليل وجبة',
            'tone' => 'is-primary',
        ];
    } elseif ($taskProgressValue < 100) {
        $todayPriority = [
            'eyebrow' => 'أولوية اليوم',
            'title' => 'أكملي مهام اليوم',
            'body' => 'باقي مهام بسيطة تساعدك على تثبيت عاداتك الصحية.',
            'icon' => 'list-checks',
            'url' => route('patient.journey'),
            'cta' => 'فتح رحلتي اليوم',
            'tone' => 'is-primary',
        ];
    } else {
        $todayPriority = [
            'eyebrow' => 'أولوية اليوم',
            'title' => 'راجعي تقدمك',
            'body' => 'يومك مكتمل بشكل جيد. يمكنك مراجعة التحليل أو المقالات المناسبة لك.',
            'icon' => 'sparkles',
            'url' => route('patient.journey'),
            'cta' => 'عرض التقدم',
            'tone' => 'is-success',
        ];
    }

    /* ---------- 9.2 Daily Readiness ---------- */
    $mealReadinessText = ($caloriesConsumed > 0 || !empty($lastMealTitle))
        ? 'مسجلة'
        : 'غير مسجلة';

    $readinessItems = [
        [
            'label' => 'الملف',
            'value' => $isProfileComplete ? 'مكتمل' : 'يحتاج تحديث',
            'icon' => 'clipboard-check',
            'tone' => $isProfileComplete ? 'is-good' : 'is-warning',
        ],
        [
            'label' => 'الموعد',
            'value' => !empty($nextAppointment) ? $appointmentStatusLabel : 'لا يوجد',
            'icon' => 'calendar-check',
            'tone' => !empty($nextAppointment) ? 'is-good' : 'is-muted',
        ],
        [
            'label' => 'الوجبات',
            'value' => $mealReadinessText,
            'icon' => 'utensils',
            'tone' => ($caloriesConsumed > 0 || !empty($lastMealTitle)) ? 'is-good' : 'is-warning',
        ],
        [
            'label' => 'المهام',
            'value' => ($homeProgress['completed'] ?? 0) . ' من ' . ($homeProgress['total'] ?? 0),
            'icon' => 'list-checks',
            'tone' => $taskProgressValue >= 100 ? 'is-good' : ($taskProgressValue > 0 ? 'is-info' : 'is-muted'),
        ],
    ];

    /* ---------- 9.3 Last Health Measurement ---------- */
    $latestWeight = $patient['latest_weight']
        ?? $patient['current_weight']
        ?? $patient['weight']
        ?? $patient['last_weight']
        ?? null;

    $previousWeight = $patient['previous_weight']
        ?? $patient['start_weight']
        ?? $patient['initial_weight']
        ?? null;

    $weightChange = null;

    if (is_numeric($latestWeight) && is_numeric($previousWeight)) {
        $weightChange = round(((float) $latestWeight) - ((float) $previousWeight), 1);
    }

    $weightUpdatedAt = $patient['weight_updated_at']
        ?? $patient['last_weight_at']
        ?? $patient['updated_at']
        ?? null;

    /* ---------- 9.4 Doctor Plan / Note ---------- */
    $doctorPlanText = $patient['doctor_note']
        ?? $patient['doctor_plan']
        ?? $patient['care_plan']
        ?? $patient['plan_note']
        ?? ($nextAppointment['doctor_notes'] ?? null)
        ?? ($nextAppointment['doctor_response_message'] ?? null);

    $doctorPlanTitle = $isDoctorApproved
        ? 'توجيه الطبيب'
        : 'خطة الطبيب';
@endphp

{{-- ==================================================
   Auto Toast For Incomplete Profile
================================================== --}}
@if ($statusVariant === 'incomplete')
    <div
        class="visually-hidden"
        data-auto-toast="ملفك الصحي غير مكتمل. أكملي البيانات الأساسية حتى نخصص تجربتك الصحية."
    ></div>
@endif

<div class="patient-home-page home-v2">

    {{-- ==================================================
       A) Hero Section
    ================================================== --}}
    <section class="home-v2-hero home-v2-state-{{ $statusVariant }}">
        <div class="home-v2-hero-copy">
            <div class="home-v2-kicker">
                <span>
                    <i data-lucide="{{ $statusIcon }}"></i>
                </span>
                {{ $profileStatus['eyebrow'] ?? 'لوحة المريض' }}
            </div>

            <h1>{{ $profileStatus['title'] ?? 'رحلتك الصحية اليوم' }}</h1>
            <p>{{ $profileStatus['body'] ?? 'تابعي يومك الصحي بخطوات واضحة.' }}</p>

            @if ($statusVariant === 'incomplete')
                <div class="home-v2-missing">
                    <strong>البيانات المتبقية</strong>

                    @foreach (($profileStatus['missing_fields'] ?? []) as $field)
                        @php
                            $fieldLabel = is_array($field) ? ($field['label'] ?? '') : $field;
                            $fieldIcon = is_array($field) ? ($field['icon'] ?? 'circle-dot') : 'circle-dot';
                        @endphp

                        @if (!empty($fieldLabel))
                            <span>
                                <i data-lucide="{{ $fieldIcon }}"></i>
                                {{ $fieldLabel }}
                            </span>
                        @endif
                    @endforeach
                </div>
            @endif

            <div class="home-v2-actions">
                <a href="{{ $profileStatus['url'] ?? route('patient.profile') }}" class="home-v2-primary-btn">
                    {{ $profileStatus['cta'] ?? 'إكمال الملف' }}
                    <i data-lucide="arrow-left"></i>
                </a>

                @if (!empty($profileStatus['secondary_cta']))
                    <button type="button" class="home-v2-soft-btn" data-profile-reason-open>
                        {{ $profileStatus['secondary_cta'] }}
                        <i data-lucide="help-circle"></i>
                    </button>
                @endif

                @if ($statusVariant === 'ready')
                    <a href="{{ route('patient.messages') }}" class="home-v2-soft-btn">
                        راسلي الطبيب
                        <i data-lucide="message-circle"></i>
                    </a>
                @endif
            </div>
        </div>

        <aside class="home-v2-hero-panel">
            <div class="home-v2-ring" style="--value: {{ $dataIndexScore }}">
                <div>
                    <strong>{{ $dataIndexScore }}%</strong>
                    <span>{{ $dataIndexLabel }}</span>
                </div>
            </div>

            <div class="home-v2-hero-panel-copy">
                <strong>جودة بياناتك</strong>
                <small>مبنية على الملف، التفعيل، الموعد، ومهام اليوم.</small>
            </div>
        </aside>
    </section>

    {{-- ==================================================
       B) Four Summary Indicators
    ================================================== --}}
    <section class="home-v2-summary-row">
        @foreach ($summaryCards as $card)
            <a href="{{ $card['url'] }}" class="home-v2-summary-card">
                <span class="home-v2-summary-icon">
                    <i data-lucide="{{ $card['icon'] }}"></i>
                </span>

                <div>
                    <small>{{ $card['label'] }}</small>
                    <strong>{{ $card['value'] }}</strong>
                    <em>{{ $card['hint'] }}</em>

                    <span class="home-v2-mini-progress">
                        <b style="width: {{ $card['progress'] }}%"></b>
                    </span>
                </div>
            </a>
        @endforeach
    </section>

    {{-- ==================================================
       B2) Etzan Daily Content
       حكمة اليوم + رسالة اليوم، تظهر حسب المحتوى المناسب للمريض
    ================================================== --}}
    <section class="home-v2-daily-content">
        <article class="home-v2-daily-card home-v2-daily-card--wisdom">
            <div class="home-v2-daily-icon">
                <i data-lucide="quote"></i>
            </div>

            <div class="home-v2-daily-copy">
                <span>{{ $dailyWisdom['tag'] ?? 'حكمة اليوم' }}</span>
                <h2>{{ $dailyWisdom['title'] ?? 'حكمة اليوم' }}</h2>
                <p>{{ $dailyWisdom['text'] ?? 'كل عادة صحية صغيرة هي تصويت لصالح النسخة الأقوى منك.' }}</p>
            </div>

            <a href="{{ $dailyWisdom['url'] ?? route('patient.articles', ['filter' => 'wisdom']) }}" class="home-v2-daily-link" aria-label="عرض حكمة اليوم">
                <i data-lucide="arrow-left"></i>
            </a>
        </article>

        <article class="home-v2-daily-card home-v2-daily-card--motivation">
            <div class="home-v2-daily-icon">
                <i data-lucide="sparkles"></i>
            </div>

            <div class="home-v2-daily-copy">
                <span>{{ $dailyMotivation['tag'] ?? 'رسالة اليوم' }}</span>
                <h2>{{ $dailyMotivation['title'] ?? 'رسالة اليوم' }}</h2>
                <p>{{ $dailyMotivation['text'] ?? 'لا تحتاجين يومًا مثاليًا؛ فقط خطوة صحية واحدة الآن تكفي لتغيير اتجاه اليوم.' }}</p>
            </div>

            <a href="{{ $dailyMotivation['url'] ?? route('patient.articles', ['filter' => 'motivation']) }}" class="home-v2-daily-link" aria-label="عرض رسالة اليوم">
                <i data-lucide="arrow-left"></i>
            </a>
        </article>
    </section>

    {{-- ==================================================
       C) Main Dashboard Grid
    ================================================== --}}
    <section class="home-v2-grid">

        {{-- --------------------------------------------------
           C1) Next Appointment
        -------------------------------------------------- --}}
        <article class="home-v2-card home-v2-appointment-card">
            <div class="home-v2-card-head">
                <div>
                    <span>الموعد القادم</span>
                    <h2>{{ !empty($nextAppointment) ? $appointmentStatusLabel : 'لا يوجد موعد نشط' }}</h2>
                </div>
                <i data-lucide="calendar-heart"></i>
            </div>

            @if (!empty($nextAppointment))
                <div class="home-v2-appointment-list">
                    <div>
                        <span>التاريخ</span>
                        <strong>{{ $nextAppointment['date'] ?? 'غير محدد' }}</strong>
                    </div>

                    <div>
                        <span>الوقت</span>
                        <strong>{{ $nextAppointment['time'] ?? 'غير محدد' }}</strong>
                    </div>

                    <div>
                        <span>النوع</span>
                        <strong>{{ $nextAppointment['consultation_label'] ?? 'أونلاين' }}</strong>
                    </div>
                </div>

                <a href="{{ route('patient.followup') }}" class="home-v2-card-link">
                    فتح صفحة الموعد
                    <i data-lucide="arrow-left"></i>
                </a>
            @else
                <div class="home-v2-empty compact">
                    <i data-lucide="calendar-plus"></i>
                    <strong>لا يوجد موعد حاليًا</strong>
                    <span>سيظهر موعدك هنا بعد حجزه وتأكيد الطبيب.</span>
                </div>

                <a href="{{ route('patient.followup') }}" class="home-v2-card-link">
                    الذهاب للمواعيد
                    <i data-lucide="arrow-left"></i>
                </a>
            @endif
        </article>

        {{-- --------------------------------------------------
           C2) Today Progress
        -------------------------------------------------- --}}
        <article class="home-v2-card home-v2-progress-card">
            <div class="home-v2-card-head">
                <div>
                    <span>اليوم</span>
                    <h2>{{ $homeProgress['title'] ?? 'تقدمك اليوم' }}</h2>
                </div>
                <i data-lucide="list-checks"></i>
            </div>

            <div class="home-v2-progress-body">
                <div class="home-v2-ring small" style="--value: {{ $taskProgressValue }}">
                    <div>
                        <strong>{{ $taskProgressValue }}%</strong>
                        <span>مهام</span>
                    </div>
                </div>

                <p>{{ $homeProgress['text'] ?? 'ابدئي بخطوة صغيرة اليوم.' }}</p>
            </div>

            <a href="{{ route('patient.journey') }}" class="home-v2-card-link">
                عرض رحلة اليوم
                <i data-lucide="arrow-left"></i>
            </a>
        </article>

        {{-- --------------------------------------------------
           C3) Calories Summary Only
           لا يوجد فورم وجبة هنا. التحليل في صفحة السعرات.
        -------------------------------------------------- --}}
        <article class="home-v2-card home-v2-calories-card">
            <div class="home-v2-card-head">
                <div>
                    <span>السعرات</span>
                    <h2>ملخص اليوم</h2>
                </div>
                <i data-lucide="flame"></i>
            </div>

            <div class="home-v2-calorie-value">
                <strong>{{ $caloriesConsumed }}</strong>
                <span>
                    @if ($calorieTarget > 0)
                        من {{ $calorieTarget }} سعرة
                    @else
                        سعرة مسجلة
                    @endif
                </span>
            </div>

            <div class="home-v2-calorie-bar">
                <span style="width: {{ $caloriesProgress }}%"></span>
            </div>

            <div class="home-v2-calorie-meta">
                <div>
                    <small>المتبقي</small>
                    <strong>{{ $caloriesRemaining !== null ? $caloriesRemaining : '—' }}</strong>
                </div>

                <div>
                    <small>آخر وجبة</small>
                    <strong>{{ $lastMealTitle ?: 'لا توجد وجبة اليوم' }}</strong>
                </div>
            </div>

            <a href="{{ route('patient.calories') }}" class="home-v2-card-link">
                تحليل وجبة
                <i data-lucide="sparkles"></i>
            </a>
        </article>

        {{-- --------------------------------------------------
           C4) Today Tasks Preview
        -------------------------------------------------- --}}
        <article class="home-v2-card home-v2-tasks-card">
            <div class="home-v2-card-head">
                <div>
                    <span>قائمة مختصرة</span>
                    <h2>مهام اليوم</h2>
                </div>
                <a href="{{ route('patient.journey') }}">عرض الكل</a>
            </div>

            <div class="home-v2-task-list">
                @forelse (collect($homeTasks)->take(4) as $task)
                    <div class="home-v2-task {{ !empty($task['completed']) ? 'is-done' : '' }}">
                        <i data-lucide="{{ !empty($task['completed']) ? 'check-circle-2' : 'circle' }}"></i>

                        <div>
                            <strong>{{ $task['title'] ?? 'مهمة صحية' }}</strong>
                            <small>{{ $task['time'] ?? '—' }}</small>
                        </div>
                    </div>
                @empty
                    <div class="home-v2-empty">
                        <i data-lucide="check-circle-2"></i>
                        <strong>لا توجد مهام اليوم</strong>
                        <span>ستظهر مهامك بعد تفعيل الخطة.</span>
                    </div>
                @endforelse
            </div>
        </article>

        {{-- --------------------------------------------------
           C5) Real Weekly Insight / Empty State
        -------------------------------------------------- --}}
        <article class="home-v2-card home-v2-chart-card">
            <div class="home-v2-card-head">
                <div>
                    <span>تحليل أسبوعي</span>
                    <h2>{{ $chart['title'] ?? 'نظرة على صحتك' }}</h2>
                </div>

                @if (!empty($chart['filters']) && is_array($chart['filters']))
                    <div class="home-v2-tabs" data-home-tabs>
                        @foreach ($chart['filters'] as $filter)
                            <button type="button" class="{{ $loop->first ? 'is-active' : '' }}">{{ $filter }}</button>
                        @endforeach
                    </div>
                @endif
            </div>

            @if ($chartHasRealData)
                <div class="home-v2-bars" aria-label="مخطط أسبوعي">
                    @foreach ($firstSeries as $index => $value)
                        @php
                            $height = (int) round(($value / $chartMax) * 100);
                            $label = $chartLabels[$index] ?? ('يوم ' . ($index + 1));
                        @endphp

                        <div class="home-v2-bar-item">
                            <span style="height: {{ max(8, $height) }}%"></span>
                            <small>{{ $label }}</small>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="home-v2-empty chart-empty">
                    <i data-lucide="bar-chart-3"></i>
                    <strong>لا توجد بيانات كافية للرسم البياني</strong>
                    <span>عند تسجيل المهام، السعرات، أو النشاط لعدة أيام سيظهر التحليل هنا.</span>
                </div>
            @endif
        </article>

        {{-- --------------------------------------------------
           C6) Today Priority
           بديل عن كرت آخر التحديثات حتى لا يتكرر مع الجرس.
        -------------------------------------------------- --}}
        <article class="home-v2-card home-v2-priority-card {{ $todayPriority['tone'] ?? 'is-primary' }}">
            <div class="home-v2-card-head">
                <div>
                    <span>{{ $todayPriority['eyebrow'] ?? 'أولوية اليوم' }}</span>
                    <h2>{{ $todayPriority['title'] ?? 'خطوتك التالية' }}</h2>
                </div>

                <i data-lucide="{{ $todayPriority['icon'] ?? 'sparkles' }}"></i>
            </div>

            <div class="home-v2-priority-body">
                <p>{{ $todayPriority['body'] ?? 'تابعي خطوتك الصحية التالية داخل اتزان.' }}</p>

                <a href="{{ $todayPriority['url'] ?? route('patient.journey') }}" class="home-v2-card-link">
                    {{ $todayPriority['cta'] ?? 'ابدئي الآن' }}
                    <i data-lucide="arrow-left"></i>
                </a>
            </div>
        </article>

        {{-- --------------------------------------------------
           C7) Daily Readiness
           مؤشرات اليوم بدل تكرار الإشعارات والرسائل.
        -------------------------------------------------- --}}
        <article class="home-v2-card home-v2-readiness-card">
            <div class="home-v2-card-head">
                <div>
                    <span>جاهزية اليوم</span>
                    <h2>ملخص سريع لحالتك</h2>
                </div>

                <i data-lucide="gauge"></i>
            </div>

            <div class="home-v2-readiness-grid">
                @foreach ($readinessItems as $item)
                    <div class="home-v2-readiness-item {{ $item['tone'] ?? 'is-muted' }}">
                        <span>
                            <i data-lucide="{{ $item['icon'] ?? 'circle' }}"></i>
                        </span>

                        <div>
                            <strong>{{ $item['label'] }}</strong>
                            <small>{{ $item['value'] }}</small>
                        </div>
                    </div>
                @endforeach
            </div>
        </article>

        {{-- --------------------------------------------------
           C8) Last Health Measurement
           كرت صحي عملي بدل كرت آخر رسالة.
        -------------------------------------------------- --}}
        <article class="home-v2-card home-v2-measurement-card">
            <div class="home-v2-card-head">
                <div>
                    <span>آخر قياس صحي</span>
                    <h2>متابعة الوزن</h2>
                </div>

                <i data-lucide="scale"></i>
            </div>

            @if (!empty($latestWeight))
                <div class="home-v2-measurement-body">
                    <div class="home-v2-measurement-value">
                        <strong>{{ $latestWeight }}</strong>
                        <span>كغ</span>
                    </div>

                    <div class="home-v2-measurement-meta">
                        <div>
                            <small>آخر تحديث</small>
                            <strong>{{ $weightUpdatedAt ?: 'غير محدد' }}</strong>
                        </div>

                        <div>
                            <small>التغير</small>
                            <strong>
                                @if ($weightChange !== null)
                                    {{ $weightChange > 0 ? '+' : '' }}{{ $weightChange }} كغ
                                @else
                                    —
                                @endif
                            </strong>
                        </div>
                    </div>
                </div>
            @else
                <div class="home-v2-empty compact">
                    <i data-lucide="scale"></i>
                    <strong>لا يوجد قياس بعد</strong>
                    <span>سجّلي أول وزن حتى يظهر تقدمك هنا.</span>
                </div>
            @endif

            <a href="{{ route('patient.profile') }}" class="home-v2-card-link">
                تسجيل أو تحديث الوزن
                <i data-lucide="arrow-left"></i>
            </a>
        </article>

        {{-- --------------------------------------------------
           C9) Doctor Plan / Note
           بديل عن كرت آخر الرسائل. الرسائل مكانها صفحة الرسائل.
        -------------------------------------------------- --}}
        <article class="home-v2-card home-v2-doctor-plan-card">
            <div class="home-v2-card-head">
                <div>
                    <span>خطة الطبيب</span>
                    <h2>{{ $doctorPlanTitle }}</h2>
                </div>

                <i data-lucide="stethoscope"></i>
            </div>

            @if (!empty($doctorPlanText))
                <div class="home-v2-doctor-note">
                    <span>
                        <i data-lucide="quote"></i>
                    </span>

                    <p>{{ $doctorPlanText }}</p>
                </div>
            @else
                <div class="home-v2-empty compact">
                    <i data-lucide="clipboard-heart"></i>
                    <strong>لا توجد ملاحظة من الطبيب بعد</strong>
                    <span>بعد اعتماد خطة أو إضافة ملاحظة من الطبيب ستظهر هنا.</span>
                </div>
            @endif

            <a href="{{ route('patient.messages') }}" class="home-v2-card-link">
                فتح التواصل مع الطبيب
                <i data-lucide="arrow-left"></i>
            </a>
        </article>
    </section>

    {{-- ==================================================
       D) Activation Journey For Non-Ready States
    ================================================== --}}
    @if ($statusVariant !== 'ready')
        <section class="home-v2-activation">
            <div class="home-v2-section-head">
                <div>
                    <span>تفعيل تجربتك</span>
                    <h2>خطواتك داخل اتزان</h2>
                </div>

                <strong>{{ $completedActivationSteps }}/{{ count($activationSteps) }}</strong>
            </div>

            <div class="home-v2-steps">
                @foreach ($activationSteps as $step)
                    @php
                        $isLocked = empty($step['done']) && $loop->index !== $currentStepIndex;

                        $state = !empty($step['done'])
                            ? 'is-done'
                            : ($loop->index === $currentStepIndex ? 'is-current' : 'is-locked');

                        $badge = !empty($step['done'])
                            ? 'مكتملة'
                            : ($loop->index === $currentStepIndex ? 'ابدئي هنا' : 'لاحقًا');

                        $stepUrl = $isLocked ? 'javascript:void(0)' : $step['url'];
                    @endphp

                    <a href="{{ $stepUrl }}" class="home-v2-step {{ $state }}" @if ($isLocked) aria-disabled="true" @endif>
                        <span>
                            <i data-lucide="{{ !empty($step['done']) ? 'check' : $step['icon'] }}"></i>
                        </span>

                        <div>
                            <em>{{ $badge }}</em>
                            <strong>{{ $step['title'] }}</strong>
                            <small>{{ $step['body'] }}</small>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ==================================================
       E) Recommended Articles
    ================================================== --}}
    <section class="home-v2-card home-v2-articles">
        <div class="home-v2-card-head">
            <div>
                <span>محتوى مناسب لك</span>
                <h2>مقالات ونصائح موصى بها</h2>
            </div>

            <a href="{{ route('patient.articles') }}">عرض الكل</a>
        </div>

        <div class="home-v2-articles-grid">
            @forelse (collect($articles)->take(3) as $article)
                <article class="home-v2-article">
                    @if (!empty($article['image']))
                        <img src="{{ $article['image'] }}" alt="{{ $article['title'] ?? 'مقال' }}">
                    @endif

                    <div>
                        <span>{{ $article['tag'] ?? 'صحة' }}</span>
                        <h3>{{ $article['title'] ?? 'مقال صحي' }}</h3>
                        <p>{{ $article['read_time'] ?? '' }}</p>
                    </div>
                </article>
            @empty
                <div class="home-v2-empty">
                    <i data-lucide="book-open"></i>
                    <strong>لا توجد مقالات موصى بها الآن</strong>
                    <span>ستظهر المقالات المناسبة لحالتك بعد توفرها.</span>
                </div>
            @endforelse
        </div>
    </section>
</div>

{{-- ==================================================
   Profile Data Reason Modal
================================================== --}}
<div class="home-v2-modal" data-profile-reason-modal aria-hidden="true">
    <div class="home-v2-modal-panel" role="dialog" aria-modal="true" aria-labelledby="profileReasonTitle">
        <button type="button" class="home-v2-modal-close" data-profile-reason-close aria-label="إغلاق">
            <i data-lucide="x"></i>
        </button>

        <div class="home-v2-modal-head">
            <span>
                <i data-lucide="shield-check"></i>
            </span>

            <div>
                <small>خصوصية وتخصيص</small>
                <h3 id="profileReasonTitle">لماذا نحتاج هذه البيانات؟</h3>
            </div>
        </div>

        <p>
            نستخدم بياناتك الصحية الأساسية حتى تكون التوصيات، اختيار الطبيب، الخطة اليومية، وتقدير السعرات أكثر دقة ومناسبة لحالتك.
        </p>

        <div class="home-v2-modal-list">
            <div>
                <i data-lucide="scale"></i>
                <strong>الوزن</strong>
                <span>يساعدنا في حساب الاحتياج اليومي ومتابعة التقدم.</span>
            </div>

            <div>
                <i data-lucide="ruler"></i>
                <strong>الطول</strong>
                <span>مهم لحساب المؤشرات الصحية بشكل أدق.</span>
            </div>

            <div>
                <i data-lucide="target"></i>
                <strong>الهدف الصحي</strong>
                <span>يحدد نوع الخطة والتوصيات المناسبة.</span>
            </div>

            <div>
                <i data-lucide="heart-pulse"></i>
                <strong>الحالة الصحية</strong>
                <span>تساعد الطبيب والنظام على تجنب توصيات غير مناسبة.</span>
            </div>
        </div>

        <div class="home-v2-modal-actions">
            <a href="{{ route('patient.profile', ['edit' => 1]) }}" class="home-v2-primary-btn">
                إكمال الملف الآن
                <i data-lucide="arrow-left"></i>
            </a>

            <button type="button" class="home-v2-soft-btn" data-profile-reason-close>
                لاحقًا
            </button>
        </div>
    </div>
</div>
@endsection
