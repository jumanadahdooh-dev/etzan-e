@extends('layouts.patient')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/patient/appointments.css') }}">

    {{-- CSS لإصلاح التقويم وتجاوز الأخطاء --}}
    <style>
        /* إصلاح حاوية التقويم */
        .appt-calendar-card {
            position: absolute !important;
            top: 100% !important;
            left: 0 !important;
            right: 0 !important;
            z-index: 9999 !important;
            background: var(--d-card, #ffffff) !important;
            border: 1px solid var(--d-border, #e2e8f0) !important;
            border-radius: 16px !important;
            padding: 20px !important;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15) !important;
            margin-top: 8px !important;
            width: auto !important;
            min-width: 320px !important;
            max-width: 100% !important;
            display: block !important;
            opacity: 1 !important;
            visibility: visible !important;
            transform: none !important;
            pointer-events: auto !important;
            overflow: visible !important;
            height: auto !important;
        }

        .appt-calendar-grid {
            display: grid !important;
            grid-template-columns: repeat(7, 1fr) !important;
            gap: 6px !important;
            margin-top: 12px !important;
        }

        .appt-calendar-day {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 8px 4px !important;
            border-radius: 10px !important;
            background: var(--d-soft-bg, #f1f5f9) !important;
            border: 1px solid transparent !important;
            font-weight: 700 !important;
            font-size: 0.85rem !important;
            cursor: pointer !important;
            color: var(--d-title, #0f172a) !important;
            transition: 0.2s !important;
            min-height: 40px !important;
            width: 100% !important;
            box-shadow: none !important;
        }
        .appt-calendar-day:hover:not(:disabled) {
            background: var(--d-green, #1d9e75) !important;
            color: #fff !important;
            transform: scale(1.02) !important;
        }
        .appt-calendar-day.is-selected {
            background: var(--d-green, #1d9e75) !important;
            color: #fff !important;
            border-color: var(--d-green, #1d9e75) !important;
        }
        .appt-calendar-day.is-today {
            border-color: var(--d-green, #1d9e75) !important;
            font-weight: 800 !important;
        }
        .appt-calendar-day.is-has-appointment {
            background: rgba(29, 158, 117, 0.15) !important;
            border-color: var(--d-green, #1d9e75) !important;
        }
        .appt-calendar-day.is-disabled {
            opacity: 0.4 !important;
            cursor: not-allowed !important;
            background: var(--d-border, #e2e8f0) !important;
        }
        .appt-calendar-day.is-muted {
            opacity: 0.5 !important;
        }

        .appt-calendar-head {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            flex-wrap: wrap !important;
            gap: 10px !important;
        }
        .appt-calendar-head h3 { margin: 0 !important; font-size: 1.1rem !important; font-weight: 800 !important; color: var(--d-title) !important; }
        .appt-calendar-close {
            padding: 6px !important; border-radius: 50% !important; background: var(--d-soft-bg) !important;
            border: 0 !important; cursor: pointer !important; transition: 0.2s !important;
        }
        .appt-calendar-close:hover { background: #ef4444 !important; color: #fff !important; }

        .appt-calendar-weekdays {
            display: grid !important; grid-template-columns: repeat(7, 1fr) !important;
            margin-top: 12px !important; font-weight: 700 !important; font-size: 0.75rem !important;
            color: var(--d-muted) !important; text-align: center !important; gap: 6px !important;
        }

        .appt-calendar-legend {
            display: flex !important; flex-wrap: wrap !important; gap: 8px !important;
            margin-top: 12px !important; font-size: 0.7rem !important; font-weight: 600 !important;
            color: var(--d-muted) !important; justify-content: center !important;
        }

        .appt-orbit-dial, .appt-orbit-center, .appt-orbit-panel, .appt-orbit-stage {
            background: transparent !important;
            box-shadow: none !important;
            border: none !important;
            transform: none !important;
            position: static !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        .appt-orbit-dial .orbit-slot {
            position: static !important;
            transform: none !important;
            width: auto !important;
            height: auto !important;
            background: var(--d-card) !important;
            border: 1px solid var(--d-border) !important;
            border-radius: 10px !important;
            padding: 8px 16px !important;
            font-size: 0.9rem !important;
            color: var(--d-title) !important;
            display: inline-block !important;
            margin: 4px !important;
        }
        .appt-orbit-dial .orbit-slot.is-active {
            background: var(--d-green) !important;
            color: #fff !important;
            border-color: var(--d-green) !important;
        }
        .appt-orbit-dial .appt-orbit-center span,
        .appt-orbit-dial .appt-orbit-center strong,
        .appt-orbit-dial .appt-orbit-center small {
            position: static !important;
            transform: none !important;
            margin: 0 !important;
        }

        @media (max-width: 768px) {
            .appt-calendar-card {
                min-width: unset !important;
                position: fixed !important;
                top: 50% !important;
                left: 50% !important;
                transform: translate(-50%, -50%) !important;
                width: 90% !important;
                max-height: 80vh !important;
                overflow-y: auto !important;
                box-shadow: 0 30px 60px rgba(0,0,0,0.3) !important;
            }
        }

        /* ============================================
           🔥 إزالة الشاشة السوداء من التقويم 🔥
           ============================================ */

        [data-appt-form].is-calendar-open::before,
        .appt-month-checker.is-open::before {
            display: none !important;
            opacity: 0 !important;
            background: transparent !important;
            backdrop-filter: none !important;
            content: none !important;
        }

        .appt-calendar-card {
            position: absolute !important;
            top: calc(100% + 8px) !important;
            left: 0 !important;
            right: 0 !important;
            z-index: 9999 !important;
            background: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 16px !important;
            padding: 20px !important;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15) !important;
            width: auto !important;
            min-width: 320px !important;
            max-width: 100% !important;
            display: block !important;
            opacity: 1 !important;
            visibility: visible !important;
            transform: none !important;
            pointer-events: auto !important;
            overflow: visible !important;
            height: auto !important;
            transition: none !important;
        }

        [data-appt-form]:not(.is-calendar-open) .appt-calendar-card {
            display: none !important;
            opacity: 0 !important;
            visibility: hidden !important;
            pointer-events: none !important;
        }

        [data-appt-form].is-calendar-open .appt-calendar-card {
            display: block !important;
            opacity: 1 !important;
            visibility: visible !important;
            pointer-events: auto !important;
        }

        .appt-time-popover {
            background: rgba(0, 0, 0, 0.5) !important;
            backdrop-filter: blur(4px) !important;
        }

        body.appt-month-is-open::before,
        body > .appt-month-checker.is-open::before {
            display: none !important;
            content: none !important;
        }

        html.dark .appt-calendar-card,
        body.dark .appt-calendar-card,
        [data-theme="dark"] .appt-calendar-card {
            background: #1a2a2e !important;
            border-color: rgba(105, 232, 199, 0.2) !important;
            color: #eefdf9 !important;
        }

        html.dark .appt-calendar-day,
        body.dark .appt-calendar-day,
        [data-theme="dark"] .appt-calendar-day {
            background: #0d1f22 !important;
            color: #eefdf9 !important;
        }

        html.dark .appt-calendar-day.is-selected,
        body.dark .appt-calendar-day.is-selected,
        [data-theme="dark"] .appt-calendar-day.is-selected {
            background: #1d9e75 !important;
            color: #ffffff !important;
        }

        @media (max-width: 768px) {
            .appt-calendar-card {
                position: fixed !important;
                top: 50% !important;
                left: 50% !important;
                transform: translate(-50%, -50%) !important;
                width: 92% !important;
                max-height: 80vh !important;
                overflow-y: auto !important;
                z-index: 99999 !important;
            }
        }




        /* ============================================================
   🎨 إصلاح شكل الأوقات - تصميم مرتب ومنظم
   ============================================================ */

/* 1. إزالة شكل Orbit الدائري */
.appt-orbit-dial,
.appt-orbit-center,
.appt-orbit-panel,
.appt-orbit-stage {
    background: transparent !important;
    box-shadow: none !important;
    border: none !important;
    transform: none !important;
    position: static !important;
    padding: 0 !important;
    margin: 0 !important;
    min-height: auto !important;
    height: auto !important;
}

/* 2. إخفاء الدائرة الخضراء والمركز */
.appt-orbit-dial::before,
.appt-orbit-dial::after,
.appt-orbit-dial .appt-orbit-center {
    display: none !important;
}

/* 3. عرض الأوقات كشبكة مرتبة */
.appt-orbit-panel.is-active {
    display: block !important;
}

.orbit-slot.slot-btn {
    position: relative !important;
    left: auto !important;
    top: auto !important;
    width: auto !important;
    min-width: 80px !important;
    min-height: 44px !important;
    padding: 8px 18px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    border-radius: 12px !important;
    background: #f8fafc !important;
    border: 1px solid #e2e8f0 !important;
    color: #0f172a !important;
    font-weight: 700 !important;
    font-size: 0.9rem !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05) !important;
    transform: none !important;
    margin: 6px !important;
    cursor: pointer !important;
    transition: all 0.2s ease !important;
}

.orbit-slot.slot-btn:hover {
    background: #1d9e75 !important;
    color: #fff !important;
    border-color: #1d9e75 !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 8px 20px rgba(29, 158, 117, 0.25) !important;
}

.orbit-slot.slot-btn.is-active {
    background: #1d9e75 !important;
    color: #fff !important;
    border-color: #1d9e75 !important;
    box-shadow: 0 8px 24px rgba(29, 158, 117, 0.35) !important;
    transform: translateY(-2px) !important;
}

/* 4. إزالة النقطة الخضراء قبل الوقت */
.orbit-slot.slot-btn::before {
    display: none !important;
    content: none !important;
}

/* 5. إخفاء أيقونة "مختار" الزائدة */
.orbit-slot.slot-btn.is-active::after {
    display: none !important;
    content: none !important;
}

/* 6. ترتيب الأزرار في شبكة */
.appt-orbit-panel .appt-orbit-dial {
    display: flex !important;
    flex-wrap: wrap !important;
    justify-content: center !important;
    gap: 8px !important;
    padding: 12px !important;
    background: #ffffff !important;
    border-radius: 16px !important;
    border: 1px solid #e2e8f0 !important;
}

/* 7. إزالة أي خلفيات سوداء */
.appt-orbit-dial {
    background: transparent !important;
}

/* 8. تصميم الفترات (الصباحية / المسائية) */
.appt-period-tabs {
    display: flex !important;
    flex-wrap: wrap !important;
    gap: 10px !important;
    margin-bottom: 16px !important;
    justify-content: center !important;
}

.appt-period-tab {
    padding: 10px 22px !important;
    border-radius: 999px !important;
    border: 1px solid #e2e8f0 !important;
    background: #f8fafc !important;
    color: #0f172a !important;
    font-weight: 700 !important;
    font-size: 0.85rem !important;
    cursor: pointer !important;
    transition: all 0.2s ease !important;
}

.appt-period-tab.is-active {
    background: #1d9e75 !important;
    color: #fff !important;
    border-color: #1d9e75 !important;
    box-shadow: 0 4px 16px rgba(29, 158, 117, 0.25) !important;
}

/* 9. إصلاح نافذة الوقت */
.appt-time-popover-card {
    padding: 24px !important;
    border-radius: 24px !important;
}

.appt-picker-head {
    margin-bottom: 18px !important;
}

.appt-picker-head h3 {
    font-size: 1.5rem !important;
    font-weight: 800 !important;
}

/* 10. إصلاح زر اختيار الوقت */
.appt-time-launch {
    min-height: 60px !important;
    padding: 12px 18px !important;
    border-radius: 16px !important;
    border: 1px solid #e2e8f0 !important;
    background: #ffffff !important;
    display: flex !important;
    align-items: center !important;
    gap: 14px !important;
    cursor: pointer !important;
    transition: all 0.2s ease !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04) !important;
}

.appt-time-launch:hover {
    border-color: #1d9e75 !important;
    box-shadow: 0 4px 16px rgba(29, 158, 117, 0.12) !important;
}

.time-launch-icon {
    width: 44px !important;
    height: 44px !important;
    border-radius: 12px !important;
    background: rgba(29, 158, 117, 0.1) !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    color: #1d9e75 !important;
}

.time-launch-copy small {
    font-size: 0.7rem !important;
    color: #94a3b8 !important;
}

.time-launch-copy strong {
    font-size: 1.1rem !important;
    color: #0f172a !important;
}

/* 11. إخفاء تأثيرات الإهتزاز الزائدة */
.is-shaking {
    animation: none !important;
}

/* 12. تحسين الموبايل */
@media (max-width: 640px) {
    .appt-orbit-panel .appt-orbit-dial {
        gap: 6px !important;
        padding: 8px !important;
    }

    .orbit-slot.slot-btn {
        min-width: 60px !important;
        min-height: 38px !important;
        padding: 6px 12px !important;
        font-size: 0.75rem !important;
        margin: 4px !important;
    }

    .appt-period-tab {
        padding: 8px 14px !important;
        font-size: 0.75rem !important;
    }
}

/* 13. الوضع المظلم */
html.dark .appt-orbit-panel .appt-orbit-dial,
body.dark .appt-orbit-panel .appt-orbit-dial,
[data-theme="dark"] .appt-orbit-panel .appt-orbit-dial {
    background: #1e293b !important;
    border-color: #334155 !important;
}

html.dark .orbit-slot.slot-btn,
body.dark .orbit-slot.slot-btn,
[data-theme="dark"] .orbit-slot.slot-btn {
    background: #1e293b !important;
    border-color: #334155 !important;
    color: #e2e8f0 !important;
}

html.dark .orbit-slot.slot-btn:hover,
body.dark .orbit-slot.slot-btn:hover,
[data-theme="dark"] .orbit-slot.slot-btn:hover {
    background: #1d9e75 !important;
    color: #fff !important;
}

html.dark .appt-time-launch,
body.dark .appt-time-launch,
[data-theme="dark"] .appt-time-launch {
    background: #1e293b !important;
    border-color: #334155 !important;
}

html.dark .time-launch-copy strong,
body.dark .time-launch-copy strong,
[data-theme="dark"] .time-launch-copy strong {
    color: #e2e8f0 !important;
}

html.dark .appt-period-tab,
body.dark .appt-period-tab,
[data-theme="dark"] .appt-period-tab {
    background: #1e293b !important;
    border-color: #334155 !important;
    color: #e2e8f0 !important;
}

html.dark .appt-period-tab.is-active,
body.dark .appt-period-tab.is-active,
[data-theme="dark"] .appt-period-tab.is-active {
    background: #1d9e75 !important;
    color: #fff !important;
}


    </style>
@endpush

@push('scripts')
    <script src="{{ asset('front/js/patient-appointments.js') }}"></script>
@endpush

@section('content')
@php
    $doctorData = $doctor ?? [];
    $doctorStatus = $doctorData['request_status'] ?? null;

    $hasApprovedDoctor = !empty($doctorData['is_selected']) && $doctorStatus === 'approved';

    $nextAppointment = $nextAppointment ?? null;
    $appointmentStatus = $nextAppointment['status'] ?? null;
    $appointmentDbStatus = $nextAppointment['db_status'] ?? $appointmentStatus;

    $hasAppointment = !empty($nextAppointment);

    $isAppointmentPending = $appointmentStatus === 'pending';
    $isAppointmentStarting = $appointmentStatus === 'starting_now';
    $isAppointmentConfirmed = in_array($appointmentStatus, ['confirmed', 'approved'], true);
    $isAppointmentCompleted = $appointmentStatus === 'completed';
    $isAppointmentMissed = $appointmentStatus === 'missed';
    $isRescheduleRequested = $appointmentStatus === 'reschedule_requested';
    $isAppointmentRejected = in_array($appointmentStatus, ['rejected', 'patient_declined_reschedule'], true);
    $isAppointmentCancelled = in_array($appointmentStatus, ['cancelled', 'canceled'], true);

    $isFinishedAppointment = $isAppointmentCompleted || $isAppointmentMissed;

    $isBookingNewAfterFinished = request()->boolean('new')
        && $hasApprovedDoctor
        && ($isFinishedAppointment || $isAppointmentRejected || $isAppointmentCancelled);

    $isEditingAppointment = request()->boolean('edit')
        && !empty($nextAppointment['id'])
        && $isAppointmentPending;

    $appointmentReasonValue = $nextAppointment['reason'] ?? null;
    $appointmentReasonLabel = ($appointmentReasons ?? [])[$appointmentReasonValue]
        ?? ($appointmentReasonValue ?: 'متابعة صحية');

    $shouldShowAppointmentStatus =
        $hasApprovedDoctor
        && $hasAppointment
        && !$isBookingNewAfterFinished
        && (
            $isAppointmentPending
            || $isAppointmentStarting
            || $isAppointmentConfirmed
            || $isRescheduleRequested
            || $isAppointmentCompleted
            || $isAppointmentMissed
        );

    $doctorStatusLabel = match ($doctorStatus) {
        'approved' => 'تمت الموافقة',
        'pending' => 'بانتظار موافقة الطبيب',
        'rejected', 'declined' => 'تم الاعتذار',
        default => 'لم يتم اختيار طبيب',
    };

    $doctorStatusIcon = match ($doctorStatus) {
        'approved' => 'badge-check',
        'pending' => 'clock-3',
        'rejected', 'declined' => 'circle-alert',
        default => 'stethoscope',
    };

    $doctorStatusClass = match ($doctorStatus) {
        'approved' => 'is-approved',
        'pending' => 'is-pending',
        'rejected', 'declined' => 'is-rejected',
        default => 'is-empty',
    };

    $appointmentStatusLabel = match ($appointmentStatus) {
        'pending' => 'بانتظار تأكيد الطبيب',
        'starting_now' => 'موعدك بدأ الآن',
        'confirmed', 'approved' => 'موعد مؤكد',
        'completed' => 'تم انتهاء الموعد',
        'missed' => 'فات الموعد',
        'reschedule_requested' => 'الطبيب اقترح وقتًا جديدًا',
        'rejected' => 'تم رفض الموعد',
        'patient_declined_reschedule' => 'تم رفض الموعد المقترح',
        'cancelled', 'canceled' => 'تم إلغاء الموعد',
        default => 'لا يوجد موعد حالي',
    };

    $appointmentStatusIcon = match ($appointmentStatus) {
        'pending' => 'clock-3',
        'starting_now' => 'radio',
        'confirmed', 'approved' => 'badge-check',
        'completed' => 'check-circle-2',
        'missed' => 'circle-alert',
        'reschedule_requested' => 'calendar-clock',
        'rejected', 'patient_declined_reschedule' => 'circle-alert',
        'cancelled', 'canceled' => 'ban',
        default => 'calendar-days',
    };

    $appointmentStatusClass = match ($appointmentStatus) {
        'pending' => 'is-pending',
        'starting_now' => 'is-starting',
        'confirmed', 'approved' => 'is-approved',
        'completed' => 'is-completed',
        'missed' => 'is-missed',
        'reschedule_requested' => 'is-reschedule',
        'rejected', 'patient_declined_reschedule', 'cancelled', 'canceled' => 'is-rejected',
        default => 'is-empty',
    };

    $isOnlineConsultation = ($nextAppointment['consultation_type'] ?? 'online') === 'online';
    $meetingUrl = $nextAppointment['meeting_url'] ?? null;
    $meetingPlatform = $nextAppointment['meeting_platform'] ?? null;
    $meetingNotes = $nextAppointment['meeting_notes'] ?? null;

    $meetingPlatformLabel = match (strtolower((string) $meetingPlatform)) {
        'zoom' => 'Zoom',
        'meet', 'google meet', 'google_meet' => 'Google Meet',
        default => $meetingPlatform ?: 'رابط اللقاء',
    };

    $heroTitle = 'رتب موعدك مع الطبيب';
    $heroText = 'اختر نوع الاستشارة، التاريخ، والوقت المناسب لك. سيتم تسجيل الطلب ضمن مواعيدك بانتظار تأكيد الطبيب.';
    $heroKicker = 'حجز موعد';
    $heroIcon = 'calendar-heart';

    if ($isBookingNewAfterFinished) {
        $heroTitle = 'احجز موعد متابعة جديد';
        $heroText = 'يمكنك اختيار موعد جديد مع الطبيب بعد انتهاء الموعد السابق أو فواته.';
        $heroKicker = 'موعد جديد';
        $heroIcon = 'calendar-plus';
    } elseif ($isEditingAppointment) {
        $heroTitle = 'تعديل طلب الموعد';
        $heroText = 'يمكنك تعديل التاريخ أو الوقت أو سبب الموعد طالما أن الطبيب لم يؤكد الطلب بعد.';
        $heroKicker = 'تعديل الطلب';
        $heroIcon = 'pencil';
    } elseif ($isAppointmentPending) {
        $heroTitle = 'موعدك بانتظار التأكيد';
        $heroText = 'تم إرسال طلب الموعد للطبيب. سيراجع الطبيب الطلب، وعند التأكيد أو اقتراح وقت بديل سيظهر التحديث هنا.';
        $heroKicker = 'طلب موعد مرسل';
        $heroIcon = 'clock-3';
    } elseif ($isAppointmentStarting) {
        $heroTitle = 'موعدك بدأ الآن';
        $heroText = $meetingUrl
            ? 'يمكنك الدخول إلى اللقاء الآن عبر الرابط الموجود في تفاصيل الموعد.'
            : 'وقت الموعد بدأ الآن. إذا لم يظهر رابط اللقاء بعد، يمكنك مراسلة الطبيب.';
        $heroKicker = 'وقت اللقاء';
        $heroIcon = 'radio';
    } elseif ($isRescheduleRequested) {
        $heroTitle = 'الطبيب اقترح وقتًا جديدًا';
        $heroText = 'راجع الموعد المقترح، ثم اختر قبول الموعد الجديد أو رفض الاقتراح.';
        $heroKicker = 'اقتراح تعديل الموعد';
        $heroIcon = 'calendar-clock';
    } elseif ($isAppointmentConfirmed) {
        $heroTitle = $meetingUrl ? 'موعدك مؤكد واللقاء جاهز' : 'موعدك مؤكد';
        $heroText = $meetingUrl
            ? 'تم تأكيد موعدك وإضافة رابط اللقاء. يمكنك الدخول إلى الاستشارة في وقت الموعد.'
            : 'تم تأكيد موعدك مع الطبيب. سيظهر رابط اللقاء هنا بعد أن يضيفه الطبيب.';
        $heroKicker = 'موعد مؤكد';
        $heroIcon = 'badge-check';
    } elseif ($isAppointmentCompleted) {
        $heroTitle = 'تم انتهاء الموعد';
        $heroText = 'انتهى وقت الموعد. يمكنك متابعة ملاحظات الطبيب أو حجز موعد متابعة جديد.';
        $heroKicker = 'موعد سابق';
        $heroIcon = 'check-circle-2';
    } elseif ($isAppointmentMissed) {
        $heroTitle = 'فات موعدك';
        $heroText = 'لم يتم تسجيل حضور الموعد. يمكنك مراسلة الطبيب أو حجز موعد جديد.';
        $heroKicker = 'موعد فائت';
        $heroIcon = 'circle-alert';
    } elseif ($isAppointmentRejected || $isAppointmentCancelled) {
        $heroTitle = 'يمكنك حجز موعد جديد';
        $heroText = 'لم يتم اعتماد الموعد السابق أو تم إلغاؤه. اختر موعدًا جديدًا يناسبك.';
        $heroKicker = 'موعد جديد';
        $heroIcon = 'calendar-plus';
    }
    $selectedAppointmentDateForCalendar = old('appointment_date');

    if (!filled($selectedAppointmentDateForCalendar)) {
        $selectedAppointmentDateForCalendar = $isEditingAppointment
            ? ($nextAppointment['date'] ?? now()->toDateString())
            : now()->toDateString();
    }

    try {
        $calendarBaseDate = \Carbon\Carbon::parse($selectedAppointmentDateForCalendar);
    } catch (\Throwable $exception) {
        $calendarBaseDate = now();
        $selectedAppointmentDateForCalendar = now()->toDateString();
    }

    $calendarMonth = $calendarBaseDate->copy()->startOfMonth();

    $arabicMonths = [
        1 => 'يناير',
        2 => 'فبراير',
        3 => 'مارس',
        4 => 'أبريل',
        5 => 'مايو',
        6 => 'يونيو',
        7 => 'يوليو',
        8 => 'أغسطس',
        9 => 'سبتمبر',
        10 => 'أكتوبر',
        11 => 'نوفمبر',
        12 => 'ديسمبر',
    ];

    $calendarMonthTitle = ($arabicMonths[$calendarMonth->month] ?? $calendarMonth->format('F')) . ' ' . $calendarMonth->year;
    $calendarWeekdays = ['السبت', 'الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة'];
    $calendarStart = $calendarMonth->copy()->startOfMonth()->startOfWeek(\Carbon\Carbon::SATURDAY);
    $calendarEnd = $calendarMonth->copy()->endOfMonth()->endOfWeek(\Carbon\Carbon::FRIDAY);
    $calendarToday = now()->startOfDay();
    $calendarAppointmentDate = $hasAppointment ? ($nextAppointment['date'] ?? null) : null;

    $normalizeDateList = function ($items) {
        return collect($items ?? [])
            ->map(function ($item) {
                if (is_array($item)) {
                    $item = $item['date'] ?? $item['appointment_date'] ?? $item['day'] ?? $item['value'] ?? null;
                }

                if (is_object($item) && isset($item->date)) {
                    $item = $item->date;
                }

                if (!filled($item)) {
                    return null;
                }

                try {
                    return \Carbon\Carbon::parse($item)->toDateString();
                } catch (\Throwable $exception) {
                    return null;
                }
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    };

    $availableCalendarDateKeys = $normalizeDateList($availableAppointmentDates ?? $availableDates ?? []);
    $fullyBookedDateKeys = $normalizeDateList($fullyBookedAppointmentDates ?? $fullyBookedDates ?? $unavailableDates ?? []);

    $appointmentStatusText = function ($status) {
        return match ($status) {
            'pending' => 'بانتظار تأكيد الطبيب',
            'starting_now' => 'بدأ الآن',
            'confirmed', 'approved' => 'مؤكد',
            'completed' => 'منتهي',
            'missed' => 'فائت',
            'reschedule_requested' => 'اقتراح وقت جديد',
            'rejected', 'patient_declined_reschedule' => 'مرفوض',
            'cancelled', 'canceled' => 'ملغى',
            default => 'موعد مسجل',
        };
    };

    $appointmentCalendarMap = [];
    $rawMonthAppointments = $monthlyAppointments
        ?? $appointmentsInMonth
        ?? $doctorMonthAppointments
        ?? $calendarAppointments
        ?? [];

    foreach (collect($rawMonthAppointments) as $monthAppointment) {
        $dateCandidate = is_array($monthAppointment)
            ? ($monthAppointment['date'] ?? $monthAppointment['appointment_date'] ?? $monthAppointment['day'] ?? null)
            : $monthAppointment;

        try {
            $dateKey = $dateCandidate ? \Carbon\Carbon::parse($dateCandidate)->toDateString() : null;
        } catch (\Throwable $exception) {
            $dateKey = null;
        }

        if (!$dateKey) {
            continue;
        }

        $timeText = is_array($monthAppointment) ? ($monthAppointment['time'] ?? $monthAppointment['appointment_time'] ?? null) : null;
        $statusText = is_array($monthAppointment) ? $appointmentStatusText($monthAppointment['status'] ?? $monthAppointment['db_status'] ?? null) : 'موعد مسجل';
        $reasonText = is_array($monthAppointment) ? ($monthAppointment['reason_label'] ?? $monthAppointment['reason'] ?? null) : null;

        $appointmentCalendarMap[$dateKey] = trim('يوجد موعد في هذا اليوم' . ($timeText ? ' - الساعة ' . $timeText : '') . ' - الحالة: ' . $statusText . ($reasonText ? ' - ' . $reasonText : ''));
    }

    foreach ($normalizeDateList($patientAppointmentDates ?? $appointmentCalendarDates ?? []) as $dateKey) {
        $appointmentCalendarMap[$dateKey] = $appointmentCalendarMap[$dateKey] ?? 'يوجد موعد مسجل في هذا اليوم.';
    }

    if ($calendarAppointmentDate) {
        try {
            $dateKey = \Carbon\Carbon::parse($calendarAppointmentDate)->toDateString();
            $appointmentCalendarMap[$dateKey] = trim('موعدك الحالي'
                . (!empty($nextAppointment['time']) ? ' - الساعة ' . $nextAppointment['time'] : '')
                . ' - الحالة: ' . $appointmentStatusLabel);
        } catch (\Throwable $exception) {
            // ignore invalid date
        }
    }

    $patientAppointmentDateKeys = collect(array_keys($appointmentCalendarMap))->filter()->unique()->values()->all();
// ✅ إصلاح مشكلة عدم توفر الأيام - تعطيل التقييد مؤقتاً
$shouldLimitCalendarToAvailableDates = false;

    $calendarDays = [];
    $viewerCalendarDays = [];

    for ($day = $calendarStart->copy(); $day->lte($calendarEnd); $day->addDay()) {
        $dayValue = $day->toDateString();
        $isSameMonth = $day->month === $calendarMonth->month;
        $isPastDay = $day->copy()->startOfDay()->lt($calendarToday);
        $isSelectedDay = $dayValue === $selectedAppointmentDateForCalendar;
        $isCurrentAppointmentDay = $calendarAppointmentDate && $dayValue === $calendarAppointmentDate;
        $hasPatientAppointment = in_array($dayValue, $patientAppointmentDateKeys, true);
        $isFullyBooked = in_array($dayValue, $fullyBookedDateKeys, true);
        $isAllowedByDoctorSchedule = !$shouldLimitCalendarToAvailableDates || in_array($dayValue, $availableCalendarDateKeys, true) || $isCurrentAppointmentDay;
        $isDisabledDay = $isPastDay || !$isSameMonth || !$isAllowedByDoctorSchedule || $isFullyBooked;

        $dayClasses = [];
        $viewerClasses = [];
        $status = 'available';
        $message = 'هذا اليوم متاح. اختاري الوقت المناسب من القائمة.';

        if (!$isSameMonth) {
            $dayClasses[] = 'is-muted';
            $viewerClasses[] = 'is-muted';
            $status = 'muted';
            $message = 'هذا اليوم خارج الشهر الحالي.';
        }

        if ($day->isSameDay($calendarToday)) {
            $dayClasses[] = 'is-today';
            $viewerClasses[] = 'is-today';
        }

        if ($isSelectedDay) {
            $dayClasses[] = 'is-selected';
            $viewerClasses[] = 'is-selected';
        }

        if ($isCurrentAppointmentDay || $hasPatientAppointment) {
            $dayClasses[] = 'is-has-appointment';
            $viewerClasses[] = 'is-has-appointment';
        }

        if ($isFullyBooked) {
            $dayClasses[] = 'is-full';
            $status = 'full';
            $message = 'هذا اليوم ممتلئ بالكامل، اختاري يومًا آخر.';
        } elseif (!$isAllowedByDoctorSchedule && $isSameMonth) {
            $dayClasses[] = 'is-no-schedule';
            $status = 'no-schedule';
            $message = 'لا توجد مواعيد متاحة للطبيب في هذا اليوم.';
        } elseif ($isPastDay) {
            $status = 'past';
            $message = 'لا يمكن اختيار تاريخ سابق.';
        }

        if ($isDisabledDay) {
            $dayClasses[] = 'is-disabled';
        } else {
            $dayClasses[] = 'is-available';
        }

        $viewerMessage = $appointmentCalendarMap[$dayValue]
            ?? 'لا يوجد موعد مسجل في هذا اليوم.';

        $calendarDays[] = [
            'value' => $dayValue,
            'day' => $day->day,
            'label' => $day->translatedFormat('d/m/Y'),
            'classes' => implode(' ', $dayClasses),
            'disabled' => $isDisabledDay,
            'pressed' => $isSelectedDay ? 'true' : 'false',
            'status' => $status,
            'message' => $message,
        ];

        $viewerDetail = [
            'title' => ($hasPatientAppointment || $isCurrentAppointmentDay) ? 'يوجد موعد في هذا اليوم' : 'يوم فارغ من المواعيد',
            'date' => $day->format('d/m/Y'),
            'time' => '—',
            'status' => ($hasPatientAppointment || $isCurrentAppointmentDay) ? 'موعد مسجل' : 'لا يوجد',
            'reason' => ($hasPatientAppointment || $isCurrentAppointmentDay) ? 'متابعة صحية' : 'لا يوجد موعد مسجل',
            'consultation' => '—',
            'message' => $viewerMessage,
            'action' => ($hasPatientAppointment || $isCurrentAppointmentDay) ? 'راجعي تفاصيل الموعد في الصفحة' : 'اختاري يومًا آخر من التقويم',
            'tone' => ($hasPatientAppointment || $isCurrentAppointmentDay) ? 'has-appointment' : 'empty',
        ];

        if ($isCurrentAppointmentDay) {
            $viewerDetail = [
                'title' => $appointmentStatusLabel,
                'date' => $day->format('d/m/Y'),
                'time' => $nextAppointment['time'] ?? 'غير محدد',
                'status' => $appointmentStatusLabel,
                'reason' => $appointmentReasonLabel,
                'consultation' => $nextAppointment['consultation_label'] ?? 'أونلاين',
                'message' => $viewerMessage,
                'action' => match ($appointmentStatus) {
                    'pending' => 'يمكنك تعديل الطلب قبل تأكيد الطبيب',
                    'confirmed', 'approved' => 'موعدك مؤكد، راقبي رابط اللقاء',
                    'reschedule_requested' => 'راجعي الموعد المقترح وردّي عليه',
                    'completed' => 'يمكنك حجز موعد متابعة جديد',
                    'missed' => 'يمكنك مراسلة الطبيب أو حجز موعد جديد',
                    default => 'راجعي تفاصيل الموعد في الصفحة',
                },
                'tone' => match ($appointmentStatus) {
                    'pending' => 'pending',
                    'confirmed', 'approved' => 'confirmed',
                    'reschedule_requested' => 'reschedule',
                    'completed' => 'completed',
                    'missed' => 'missed',
                    default => 'has-appointment',
                },
            ];
        }

        $viewerCalendarDays[] = [
            'value' => $dayValue,
            'day' => $day->day,
            'label' => $day->translatedFormat('d/m/Y'),
            'classes' => implode(' ', $viewerClasses),
            'message' => $viewerMessage,
            'detail' => $viewerDetail,
            'has_appointment' => $hasPatientAppointment || $isCurrentAppointmentDay,
            'disabled' => !$isSameMonth,
        ];
    }

    $initialViewerDay = collect($viewerCalendarDays)
        ->firstWhere('value', $calendarAppointmentDate ?: $selectedAppointmentDateForCalendar)
        ?? collect($viewerCalendarDays)->first();

    $initialViewerDetail = $initialViewerDay['detail'] ?? [
        'title' => 'اختاري يومًا من التقويم',
        'date' => $selectedCalendarLabel ?? now()->format('d/m/Y'),
        'time' => '—',
        'status' => 'بانتظار الاختيار',
        'reason' => 'اضغطي على أي يوم لمعرفة حالته',
        'consultation' => '—',
        'message' => 'اختاري يومًا من التقويم لمعرفة هل يوجد موعد عليه أم لا.',
        'action' => 'اختاري تاريخًا من التقويم',
        'tone' => 'empty',
    ];

    $selectedCalendarLabel = $calendarBaseDate->format('d/m/Y');

    // ============================================
    // ✅ الأوقات المتاحة - مع بيانات وهمية للاختبار
    // ============================================

    // جلب الأوقات من الـ Controller أو استخدام البيانات الوهمية
    $slotsData = $availableSlots ?? [];

    // إذا كانت الأوقات فارغة، استخدم البيانات الوهمية
    if (empty($slotsData)) {
        $slotsData = [
            [
                'icon' => 'sunrise',
                'label' => 'الفترة الصباحية',
                'times' => ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30'],
            ],
            [
                'icon' => 'sunset',
                'label' => 'الفترة المسائية',
                'times' => ['16:00', '16:30', '17:00', '17:30', '18:00', '18:30'],
            ],
        ];
    }

    $slotGroups = collect($slotsData)
        ->map(function ($period) {
            $times = collect(data_get($period, 'times', []))->filter()->values()->all();

            return [
                'icon' => data_get($period, 'icon', 'clock-3'),
                'label' => data_get($period, 'label', 'أوقات متاحة'),
                'times' => $times,
            ];
        })
        ->filter(fn ($period) => !empty($period['times']))
        ->values();

    $hasAvailableSlots = $slotGroups->isNotEmpty();
    $selectedAppointmentTimeForPicker = old('appointment_time', $isEditingAppointment ? ($nextAppointment['time'] ?? '') : '');
    $firstAvailableSlot = $slotGroups
        ->flatMap(fn ($period) => $period['times'] ?? [])
        ->filter()
        ->values()
        ->first();

    $selectedPeriodIndex = $slotGroups->search(fn ($period) => in_array($selectedAppointmentTimeForPicker, $period['times'] ?? [], true));

    if ($selectedPeriodIndex === false) {
        $selectedPeriodIndex = 0;
    }
@endphp

<section class="appt-page">
    @if (session('success'))
        <div class="appt-alert is-success">
            <i data-lucide="badge-check"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="appt-alert is-error">
            <i data-lucide="circle-alert"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="appt-alert is-error">
            <i data-lucide="circle-alert"></i>
            <span>راجِع بيانات الموعد، يوجد حقول تحتاج تعديل.</span>
        </div>
    @endif

    <div class="appt-hero">
        <div class="appt-hero-copy">
            <span class="appt-kicker">
                <i data-lucide="{{ $heroIcon }}"></i>
                {{ $heroKicker }}
            </span>

            <h1>{{ $heroTitle }}</h1>

            <p>{{ $heroText }}</p>

            @if ($hasAppointment && !$isBookingNewAfterFinished)
                <div class="appt-status-pill {{ $appointmentStatusClass }}">
                    <i data-lucide="{{ $appointmentStatusIcon }}"></i>
                    <span>{{ $appointmentStatusLabel }}</span>
                </div>
            @else
                <div class="appt-status-pill {{ $doctorStatusClass }}">
                    <i data-lucide="{{ $doctorStatusIcon }}"></i>
                    <span>{{ $doctorStatusLabel }}</span>
                </div>
            @endif
        </div>

        <div class="appt-doctor-card">
            <div class="doctor-avatar">
                <img src="{{ $doctorData['avatar'] ?? '' }}" alt="صورة الطبيب">
            </div>

            <div>
                <span>طبيب المتابعة</span>
                <h2>{{ $doctorData['name'] ?? 'لم يتم اختيار طبيب' }}</h2>
                <p>{{ $doctorData['specialty'] ?? 'اختر الطبيب المناسب أولًا' }}</p>
            </div>
        </div>
    </div>

    @if (!$hasApprovedDoctor)
        <div class="appt-locked-card">
            <div class="appt-locked-icon">
                <i data-lucide="lock-keyhole"></i>
            </div>

            <h2>
                @if ($doctorStatus === 'pending')
                    بانتظار موافقة الطبيب
                @elseif (in_array($doctorStatus, ['rejected', 'declined'], true))
                    تم الاعتذار عن طلب المتابعة
                @else
                    الحجز غير متاح الآن
                @endif
            </h2>

            <p>
                @if ($doctorStatus === 'pending')
                    تم إرسال طلب المتابعة للطبيب. بعد موافقة الطبيب سيتم تفعيل حجز المواعيد.
                @elseif (in_array($doctorStatus, ['rejected', 'declined'], true))
                    يمكنك اختيار طبيب آخر مناسب لحالتك حتى تبدأ المتابعة وحجز المواعيد.
                @else
                    لا يمكنك حجز موعد قبل اختيار طبيب المتابعة وموافقته على طلبك.
                @endif
            </p>

            <div class="appt-locked-actions">
                <a href="{{ route('patient.profile') }}" class="appt-primary-btn">
                    العودة للملف الصحي
                    <i data-lucide="arrow-left"></i>
                </a>

                <a href="{{ route('patient.doctors.recommended') }}" class="appt-outline-btn">
                    اختيار طبيب آخر
                </a>
            </div>
        </div>
    @elseif ($shouldShowAppointmentStatus)
        @if ($isEditingAppointment)
            <div class="appt-layout">
                <form method="POST" action="{{ route('patient.appointments.update', $nextAppointment['id']) }}" class="appt-form-card" data-appt-form>
                    @csrf
                    @method('PUT')

                    <div class="appt-card-head">
                        <div>
                            <span>تعديل طلب الموعد</span>
                            <h2>عدّل التاريخ أو الوقت المناسب</h2>
                        </div>

                        <i data-lucide="pencil"></i>
                    </div>

                    <div class="appt-alert is-success">
                        <i data-lucide="info"></i>
                        <span>يمكنك تعديل الطلب طالما أن الطبيب لم يؤكد الموعد بعد.</span>
                    </div>

                    <div class="appt-form-grid">
                        <label class="appt-field">
                            <span>نوع الاستشارة</span>

                            <div class="appt-input">
                                <i data-lucide="video"></i>

                                <select name="consultation_type">
                                    <option value="online" @selected(old('consultation_type', $nextAppointment['consultation_type'] ?? 'online') === 'online')>
                                        أونلاين
                                    </option>

                                    <option value="clinic" @selected(old('consultation_type', $nextAppointment['consultation_type'] ?? 'online') === 'clinic')>
                                        حضوري
                                    </option>
                                </select>
                            </div>

                            @error('consultation_type')
                                <small>{{ $message }}</small>
                            @enderror
                        </label>

                        <div class="appt-field appt-date-field">
                            <span>تاريخ الموعد</span>

                            <button type="button" class="appt-input appt-date-trigger" data-appt-date-trigger>
                                <i data-lucide="calendar"></i>

                                <span class="appt-date-value" data-calendar-selected-label>
                                    {{ filled(old('appointment_date', $nextAppointment['date'] ?? '')) ? $selectedCalendarLabel : 'اختاري التاريخ' }}
                                </span>
                            </button>

                            <input
                                type="hidden"
                                name="appointment_date"
                                data-appt-date-input
                                value="{{ old('appointment_date', $nextAppointment['date'] ?? '') }}"
                                min="{{ now()->toDateString() }}"
                            >

                            <small class="appt-date-message" data-date-message></small>

                            @error('appointment_date')
                                <small>{{ $message }}</small>
                            @enderror

                            <div class="appt-calendar-card" data-appt-calendar>
                                <div class="appt-calendar-head">
                                    <div>
                                        <span>تقويم المواعيد</span>
                                        <h3>{{ $calendarMonthTitle }}</h3>
                                    </div>

                                    <small>
                                        اليوم المختار:
                                        <b data-calendar-selected-label>{{ $selectedCalendarLabel }}</b>
                                    </small>

                                    <button type="button" class="appt-calendar-close" data-calendar-close aria-label="إغلاق التقويم">
                                        <i data-lucide="x"></i>
                                    </button>
                                </div>

                                <div class="appt-calendar-weekdays">
                                    @foreach ($calendarWeekdays as $weekday)
                                        <span>{{ $weekday }}</span>
                                    @endforeach
                                </div>

                                <div class="appt-calendar-grid">
                                    @foreach ($calendarDays as $calendarDay)
                                        <button
                                            type="button"
                                            class="appt-calendar-day {{ $calendarDay['classes'] }}"
                                            data-appt-calendar-day
                                            data-date-value="{{ $calendarDay['value'] }}"
                                            data-date-label="{{ $calendarDay['label'] }}"
                                            data-date-status="{{ $calendarDay['status'] }}"
                                            data-date-message="{{ $calendarDay['message'] }}"
                                            aria-pressed="{{ $calendarDay['pressed'] }}"
                                            title="{{ $calendarDay['message'] }}"
                                            @disabled($calendarDay['disabled'])
                                        >
                                            <span>{{ $calendarDay['day'] }}</span>
                                        </button>
                                    @endforeach
                                </div>

                                <div class="appt-calendar-legend">
                                    <span><b class="legend-dot is-selected"></b> مختار</span>
                                    <span><b class="legend-dot is-appointment"></b> موعد</span>
                                    <span><b class="legend-dot is-today"></b> اليوم</span>
                                    <span><b class="legend-dot is-disabled"></b> غير متاح</span>
                                </div>
                            </div>
                        </div>

                        <label class="appt-field appt-field-wide">
                            <span>سبب الحجز</span>

                            <div class="appt-input">
                                <i data-lucide="clipboard-list"></i>

                                <select name="reason">
                                    <option value="">اختر سبب الموعد</option>

                                    @foreach (($appointmentReasons ?? []) as $key => $label)
                                        <option value="{{ $key }}" @selected(old('reason', $nextAppointment['reason'] ?? '') === $key)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            @error('reason')
                                <small>{{ $message }}</small>
                            @enderror
                        </label>
                    </div>
                    <input
                        type="hidden"
                        name="appointment_time"
                        value="{{ old('appointment_time', $nextAppointment['time'] ?? '') }}"
                        data-selected-time
                    >
                    <div class="appt-slots-box appt-orbit-time-box" data-slot-picker>
                        <div class="appt-subhead">
                            <span>اختيار الوقت</span>
                            <strong>اختاري ساعة الموعد</strong>
                        </div>

                        @if (!$hasAvailableSlots)
                            <div class="appt-empty-slots">
                                <i data-lucide="calendar-x-2"></i>
                                <div>
                                    <strong>لا توجد أوقات متاحة لهذا التاريخ</strong>
                                    <span>اختاري يومًا آخر من التقويم، أو انتظري تحديث جدول الطبيب.</span>
                                </div>
                            </div>
                        @else
                            <div class="appt-time-launch-row">
                                <button type="button" class="appt-time-launch" data-time-picker-trigger>
                                    <span class="time-launch-icon"><i data-lucide="clock-3"></i></span>

                                    <span class="time-launch-copy">
                                        <small>الوقت المختار</small>
                                        <strong data-selected-time-label>{{ $selectedAppointmentTimeForPicker ?: 'اختاري الوقت' }}</strong>
                                    </span>

                                    <span class="time-launch-action" aria-hidden="true"><i data-lucide="clock-3"></i></span>
                                </button>
                            </div>

                            <div class="appt-time-popover" data-time-picker aria-hidden="true">
                                <div class="appt-time-popover-card appt-orbit-popover-card" role="dialog" aria-modal="true" aria-label="اختيار الوقت المناسب">
                                    <div class="appt-picker-head">
                                        <div>
                                            <span>اختيار الوقت</span>
                                            <h3>اختاري الوقت المناسب</h3>
                                        </div>

                                        <button type="button" class="appt-picker-close" data-time-picker-close aria-label="إغلاق محدد الوقت">
                                            <i data-lucide="x"></i>
                                        </button>
                                    </div>

                                    <div class="appt-period-tabs" role="tablist" aria-label="فترات اليوم">
                                        @foreach ($slotGroups as $period)
                                            <button
                                                type="button"
                                                class="appt-period-tab {{ (int) $selectedPeriodIndex === $loop->index ? 'is-active' : '' }}"
                                                data-time-period-tab
                                                data-period-key="period-{{ $loop->index }}"
                                                aria-pressed="{{ (int) $selectedPeriodIndex === $loop->index ? 'true' : 'false' }}"
                                            >
                                                <i data-lucide="{{ $period['icon'] }}"></i>
                                                <span>{{ $period['label'] }}</span>
                                            </button>
                                        @endforeach
                                    </div>

                                    <div class="appt-orbit-stage">
                                        @foreach ($slotGroups as $period)
                                            @php
                                                $periodTimes = $period['times'] ?? [];
                                                $totalPeriodTimes = max(count($periodTimes), 1);
                                                $periodFirstTime = $periodTimes[0] ?? $firstAvailableSlot;
                                            @endphp

                                            <section
                                                class="appt-orbit-panel {{ (int) $selectedPeriodIndex === $loop->index ? 'is-active' : '' }}"
                                                data-time-period-panel
                                                data-period-key="period-{{ $loop->index }}"
                                            >
                                                <div class="appt-orbit-dial" data-clock-face>
                                                    <div class="appt-orbit-center">
                                                        <span>{{ $period['label'] }}</span>
                                                        <strong data-selected-time-label>{{ $selectedAppointmentTimeForPicker ?: 'اختاري الوقت' }}</strong>
                                                        <small>الأوقات المتاحة</small>
                                                    </div>

                                                    @foreach ($periodTimes as $time)
                                                        @php
                                                            $slotAngle = round($loop->index * (360 / $totalPeriodTimes), 3);
                                                        @endphp

                                                        <button
                                                            type="button"
                                                            class="slot-btn orbit-slot {{ $selectedAppointmentTimeForPicker === $time ? 'is-active' : '' }}"
                                                            data-time-value="{{ $time }}"
                                                            style="--slot-angle: {{ $slotAngle }}deg"
                                                        >
                                                            <span class="slot-time">{{ $time }}</span>
                                                        </button>
                                                    @endforeach
                                                </div>
                                            </section>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif

                        <small class="appt-error" data-slot-unavailable-message></small>

                        @error('appointment_time')
                            <small class="appt-error is-visible">{{ $message }}</small>
                        @enderror
                    </div>

                    <label class="appt-field">
                        <span>ملاحظات للطبيب</span>

                        <textarea
                            name="notes"
                            rows="4"
                            placeholder="اكتب أي تفاصيل تريد أن يعرفها الطبيب قبل الموعد..."
                        >{{ old('notes', $nextAppointment['notes'] ?? '') }}</textarea>

                        @error('notes')
                            <small>{{ $message }}</small>
                        @enderror
                    </label>

                    <div class="appt-form-actions">
                        <button type="submit" class="appt-primary-btn">
                            حفظ التعديل
                            <i data-lucide="save"></i>
                        </button>

                        <a href="{{ route('patient.followup') }}" class="appt-outline-btn">
                            إلغاء التعديل
                        </a>
                    </div>
                </form>

                <aside class="appt-side-card">
                    <div class="appt-card-head compact">
                        <div>
                            <span>تنبيه مهم</span>
                            <h2>قبل اعتماد الموعد</h2>
                        </div>
                    </div>

                    <div class="appt-note">
                        <i data-lucide="info"></i>

                        <p>
                            بعد حفظ التعديل، سيظهر الموعد المحدّث للطبيب حتى يراجع الطلب ويؤكده أو يقترح وقتًا بديلًا.
                        </p>
                    </div>
                </aside>
            </div>
        @else
            <div class="appt-layout">
                <article class="appt-form-card appt-current-card">
                    <div class="appt-card-head">
                        <div>
                            <span>حالة الموعد</span>

                            <h2>
                                @if ($isAppointmentPending)
                                    موعدك بانتظار التأكيد
                                @elseif ($isAppointmentStarting)
                                    موعدك بدأ الآن
                                @elseif ($isRescheduleRequested)
                                    الطبيب اقترح موعدًا جديدًا
                                @elseif ($isAppointmentConfirmed)
                                    موعدك مؤكد
                                @elseif ($isAppointmentCompleted)
                                    تم انتهاء الموعد
                                @elseif ($isAppointmentMissed)
                                    فات موعدك
                                @else
                                    تفاصيل الموعد
                                @endif
                            </h2>
                        </div>

                        <div class="appt-card-head-tools">
                            <div class="appt-month-checker" data-appointment-checker>
                                <div class="appt-month-checker-copy">
                                    <span>مواعيد الشهر</span>
                                    <strong>استعراض سريع</strong>
                                </div>

                                <button type="button" class="appt-month-trigger" data-calendar-view-trigger>
                                    <i data-lucide="calendar-search"></i>
                                    <span>مواعيد الشهر</span>
                                </button>

                                <div class="appt-month-popover appt-month-experience" role="dialog" aria-modal="true" aria-label="تقويم مواعيد الشهر">
                                    <div class="appt-month-surface">
                                        <div class="appt-month-calendar-pane">
                                            <div class="appt-calendar-head">
                                                <div>
                                                    <span>تقويم الشهر</span>
                                                    <h3>{{ $calendarMonthTitle }}</h3>
                                                </div>

                                                <button type="button" class="appt-calendar-close" data-calendar-close aria-label="إغلاق التقويم">
                                                    <i data-lucide="x"></i>
                                                </button>
                                            </div>

                                            <div class="appt-calendar-weekdays">
                                                @foreach ($calendarWeekdays as $weekday)
                                                    <span>{{ $weekday }}</span>
                                                @endforeach
                                            </div>

                                            <div class="appt-calendar-grid is-viewer">
                                                @foreach ($viewerCalendarDays as $calendarDay)
                                                    <button
                                                        type="button"
                                                        class="appt-calendar-day {{ $calendarDay['classes'] }}"
                                                        data-calendar-view-day
                                                        data-date-value="{{ $calendarDay['value'] }}"
                                                        data-date-label="{{ $calendarDay['label'] }}"
                                                        data-date-message="{{ $calendarDay['message'] }}"
                                                        data-detail-title="{{ $calendarDay['detail']['title'] }}"
                                                        data-detail-date="{{ $calendarDay['detail']['date'] }}"
                                                        data-detail-time="{{ $calendarDay['detail']['time'] }}"
                                                        data-detail-status="{{ $calendarDay['detail']['status'] }}"
                                                        data-detail-reason="{{ $calendarDay['detail']['reason'] }}"
                                                        data-detail-consultation="{{ $calendarDay['detail']['consultation'] }}"
                                                        data-detail-message="{{ $calendarDay['detail']['message'] }}"
                                                        data-detail-action="{{ $calendarDay['detail']['action'] }}"
                                                        data-detail-tone="{{ $calendarDay['detail']['tone'] }}"
                                                        title="{{ $calendarDay['message'] }}"
                                                        aria-pressed="{{ str_contains($calendarDay['classes'], 'is-selected') ? 'true' : 'false' }}"
                                                        @disabled($calendarDay['disabled'])
                                                    >
                                                        <span>{{ $calendarDay['day'] }}</span>
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>

                                        <aside class="appt-month-day-panel" data-calendar-detail-card data-detail-tone="{{ $initialViewerDetail['tone'] }}">
                                            <div class="appt-day-orb">
                                                <i data-lucide="calendar-heart"></i>
                                            </div>

                                            <span class="appt-day-kicker">تفاصيل اليوم المختار</span>

                                            <h3 data-detail-title>{{ $initialViewerDetail['title'] }}</h3>

                                            <p data-detail-message>{{ $initialViewerDetail['message'] }}</p>

                                            <div class="appt-day-status-chip" data-detail-status>
                                                {{ $initialViewerDetail['status'] }}
                                            </div>

                                            <div class="appt-day-detail-grid">
                                                <div>
                                                    <small>التاريخ</small>
                                                    <strong data-detail-date>{{ $initialViewerDetail['date'] }}</strong>
                                                </div>

                                                <div>
                                                    <small>الوقت</small>
                                                    <strong data-detail-time>{{ $initialViewerDetail['time'] }}</strong>
                                                </div>

                                                <div>
                                                    <small>نوع الاستشارة</small>
                                                    <strong data-detail-consultation>{{ $initialViewerDetail['consultation'] }}</strong>
                                                </div>

                                                <div>
                                                    <small>السبب</small>
                                                    <strong data-detail-reason>{{ $initialViewerDetail['reason'] }}</strong>
                                                </div>
                                            </div>

                                            <div class="appt-day-action-note">
                                                <i data-lucide="sparkles"></i>
                                                <span data-detail-action>{{ $initialViewerDetail['action'] }}</span>
                                            </div>
                                        </aside>
                                    </div>
                                </div>
                            </div>
                            <span class="appt-head-status-icon" aria-hidden="true">
                                <i data-lucide="{{ $appointmentStatusIcon }}"></i>
                            </span>
                        </div>
                    </div>

                    <div class="appt-current-status {{ $appointmentStatusClass }}">
                        <i data-lucide="{{ $appointmentStatusIcon }}"></i>

                        <div>
                            <strong>{{ $appointmentStatusLabel }}</strong>

                            @if ($isAppointmentPending)
                                <span>تم إرسال الطلب للطبيب. يمكنك انتظار الرد أو تعديل الطلب قبل اعتماده.</span>
                            @elseif ($isAppointmentStarting)
                                <span>
                                    @if ($meetingUrl)
                                        الموعد بدأ الآن. يمكنك الدخول إلى اللقاء من الزر الموجود بالأسفل.
                                    @else
                                        الموعد بدأ الآن، لكن رابط اللقاء غير متاح بعد.
                                    @endif
                                </span>
                            @elseif ($isRescheduleRequested)
                                <span>الطبيب لم يعتمد الوقت السابق واقترح وقتًا آخر.</span>
                            @elseif ($isAppointmentConfirmed)
                                <span>
                                    @if ($meetingUrl)
                                        تم تأكيد الموعد وإضافة رابط اللقاء.
                                    @else
                                        تم تأكيد الموعد. سيظهر رابط اللقاء بعد إضافته من الطبيب.
                                    @endif
                                </span>
                            @elseif ($isAppointmentCompleted)
                                <span>انتهى وقت الموعد. يمكنك متابعة توصيات الطبيب أو حجز موعد متابعة جديد.</span>
                            @elseif ($isAppointmentMissed)
                                <span>فات وقت الموعد. يمكنك مراسلة الطبيب أو حجز موعد جديد.</span>
                            @endif
                        </div>
                    </div>

                    <div class="appt-current-grid">
                        <div>
                            <span>تاريخ الموعد</span>
                            <strong>{{ $nextAppointment['date'] ?? 'غير محدد' }}</strong>
                        </div>

                        <div>
                            <span>وقت الموعد</span>
                            <strong>{{ $nextAppointment['time'] ?? 'غير محدد' }}</strong>
                        </div>

                        <div>
                            <span>نوع الاستشارة</span>
                            <strong>{{ $nextAppointment['consultation_label'] ?? 'أونلاين' }}</strong>
                        </div>

                        <div>
                            <span>سبب الموعد</span>
                            <strong>{{ $appointmentReasonLabel }}</strong>
                        </div>
                    </div>

                    @if ($isAppointmentConfirmed || $isAppointmentStarting)
                        <div class="appt-meeting-box {{ $meetingUrl ? 'is-ready' : 'is-waiting' }}">
                            <div class="appt-meeting-icon">
                                <i data-lucide="{{ $meetingUrl ? 'video' : ($isOnlineConsultation ? 'clock-3' : 'map-pin') }}"></i>
                            </div>

                            <div class="appt-meeting-content">
                                @if ($isOnlineConsultation)
                                    @if ($meetingUrl)
                                        <span>{{ $isAppointmentStarting ? 'اللقاء بدأ الآن' : 'اللقاء جاهز' }}</span>
                                        <h3>رابط الاستشارة متاح الآن</h3>

                                        <p>
                                            يمكنك الدخول إلى اللقاء عبر {{ $meetingPlatformLabel }} في وقت الموعد المحدد.
                                        </p>

                                        @if (!empty($meetingNotes))
                                            <div class="appt-meeting-note">
                                                {{ $meetingNotes }}
                                            </div>
                                        @endif

                                        <a href="{{ $meetingUrl }}" target="_blank" rel="noopener noreferrer" class="appt-meeting-btn">
                                            الدخول إلى اللقاء
                                            <i data-lucide="external-link"></i>
                                        </a>
                                    @else
                                        <span>بانتظار رابط اللقاء</span>
                                        <h3>سيظهر رابط الاستشارة هنا</h3>

                                        <p>
                                            بعد أن يضيف الطبيب رابط Google Meet أو Zoom سيظهر زر الدخول إلى اللقاء مباشرة في هذه الصفحة.
                                        </p>
                                    @endif
                                @else
                                    <span>زيارة حضورية</span>
                                    <h3>هذا الموعد داخل العيادة</h3>

                                    <p>
                                        ستظهر تفاصيل العنوان وتعليمات الحضور هنا عندما يضيفها الطبيب أو الإدارة.
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if ($isAppointmentCompleted)
                        <div class="appt-post-box is-completed">
                            <div class="appt-post-icon">
                                <i data-lucide="check-circle-2"></i>
                            </div>

                            <div>
                                <span>ملخص بعد الموعد</span>
                                <h3>تم انتهاء موعدك مع الطبيب</h3>

                                <p>
                                    يمكنك الآن متابعة ملاحظات الطبيب، مراسلته عند الحاجة، أو حجز موعد متابعة جديد.
                                </p>

                                @if (!empty($nextAppointment['doctor_after_session_notes']))
                                    <div class="appt-current-note">
                                        <span>ملاحظات الطبيب بعد الجلسة</span>
                                        <p>{{ $nextAppointment['doctor_after_session_notes'] }}</p>
                                    </div>
                                @else
                                    <div class="appt-current-note">
                                        <span>ملاحظات الطبيب بعد الجلسة</span>
                                        <p>لم يضف الطبيب ملاحظات بعد.</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if ($isAppointmentMissed)
                        <div class="appt-post-box is-missed">
                            <div class="appt-post-icon">
                                <i data-lucide="circle-alert"></i>
                            </div>

                            <div>
                                <span>موعد فائت</span>
                                <h3>لم يتم تسجيل حضور الموعد</h3>

                                <p>
                                    يمكنك التواصل مع الطبيب لمعرفة الخطوة التالية، أو حجز موعد جديد.
                                </p>
                            </div>
                        </div>
                    @endif

                    @if (!empty($nextAppointment['notes']))
                        <div class="appt-current-note">
                            <span>ملاحظاتك للطبيب</span>
                            <p>{{ $nextAppointment['notes'] }}</p>
                        </div>
                    @endif

                    @if ($isAppointmentPending)
                        <div class="appt-waiting-box">
                            <i data-lucide="hourglass"></i>

                            <div>
                                <strong>بانتظار رد الطبيب</strong>
                                <p>سيظهر هنا التحديث فور التأكيد أو عند اقتراح وقت بديل.</p>
                            </div>
                        </div>
                    @endif

                    @if ($isRescheduleRequested)
                        <div class="appt-suggestion-box">
                            <div class="appt-subhead">
                                <span>الموعد المقترح</span>
                                <strong>راجع الوقت الجديد</strong>
                            </div>

                            <div class="appt-current-grid">
                                <div>
                                    <span>التاريخ المقترح</span>
                                    <strong>{{ $nextAppointment['suggested_date'] ?? 'غير محدد' }}</strong>
                                </div>

                                <div>
                                    <span>الوقت المقترح</span>
                                    <strong>{{ $nextAppointment['suggested_time'] ?? 'غير محدد' }}</strong>
                                </div>
                            </div>

                            @if (!empty($nextAppointment['doctor_response_message']))
                                <div class="appt-current-note">
                                    <span>رسالة الطبيب</span>
                                    <p>{{ $nextAppointment['doctor_response_message'] }}</p>
                                </div>
                            @endif

                            <div class="appt-form-actions">
                                <form method="POST" action="{{ route('patient.appointments.suggestion.accept', $nextAppointment['id']) }}">
                                    @csrf

                                    <button type="submit" class="appt-primary-btn">
                                        قبول الموعد المقترح
                                        <i data-lucide="check"></i>
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('patient.appointments.suggestion.decline', $nextAppointment['id']) }}">
                                    @csrf

                                    <button type="submit" class="appt-outline-btn">
                                        رفض الاقتراح
                                        <i data-lucide="x"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif

                    <div class="appt-form-actions">
                        @if ($isAppointmentPending)
                            <a href="{{ route('patient.followup', ['edit' => 1]) }}" class="appt-primary-btn">
                                تعديل الطلب
                                <i data-lucide="pencil"></i>
                            </a>
                        @endif

                        @if ($isAppointmentCompleted || $isAppointmentMissed)
                            <a href="{{ route('patient.messages') }}" class="appt-primary-btn">
                                مراسلة الطبيب
                                <i data-lucide="message-circle"></i>
                            </a>

                            <a href="{{ route('patient.followup', ['new' => 1]) }}" class="appt-outline-btn">
                                حجز موعد جديد
                                <i data-lucide="calendar-plus"></i>
                            </a>
                        @endif

                        <a href="{{ route('patient.home') }}" class="appt-outline-btn">
                            العودة للرئيسية
                            <i data-lucide="arrow-left"></i>
                        </a>
                    </div>
                </article>

                <aside class="appt-side-card">
                    <div class="appt-card-head compact">
                        <div>
                            <span>متابعة الطلب</span>
                            <h2>الخطوات القادمة</h2>
                        </div>
                    </div>

                    <div class="appt-flow">
                        <div>
                            <span>1</span>
                            <div>
                                <strong>إرسال الطلب</strong>
                                <small>تم حفظ الموعد في حسابك.</small>
                            </div>
                        </div>

                        <div>
                            <span>2</span>
                            <div>
                                <strong>
                                    @if ($isAppointmentStarting)
                                        الموعد بدأ الآن
                                    @elseif ($isAppointmentConfirmed)
                                        تم تأكيد الموعد
                                    @elseif ($isAppointmentCompleted)
                                        انتهى الموعد
                                    @elseif ($isAppointmentMissed)
                                        فات الموعد
                                    @elseif ($isRescheduleRequested)
                                        اقتراح وقت جديد
                                    @else
                                        مراجعة الموعد
                                    @endif
                                </strong>

                                <small>
                                    @if ($isAppointmentStarting)
                                        يمكنك الدخول إلى اللقاء الآن إذا كان الرابط متاحًا.
                                    @elseif ($isAppointmentConfirmed)
                                        @if ($meetingUrl)
                                            رابط اللقاء جاهز.
                                        @else
                                            بانتظار إضافة رابط اللقاء.
                                        @endif
                                    @elseif ($isAppointmentCompleted)
                                        يمكنك متابعة ملاحظات الطبيب أو حجز موعد متابعة جديد.
                                    @elseif ($isAppointmentMissed)
                                        يمكنك مراسلة الطبيب أو حجز موعد جديد.
                                    @elseif ($isRescheduleRequested)
                                        بانتظار ردك على الاقتراح.
                                    @else
                                        الطبيب يراجع التاريخ والوقت.
                                    @endif
                                </small>
                            </div>
                        </div>

                        <div>
                            <span>3</span>
                            <div>
                                <strong>
                                    @if ($isAppointmentCompleted || $isAppointmentMissed)
                                        خطوة المتابعة
                                    @elseif ($meetingUrl)
                                        الدخول إلى اللقاء
                                    @else
                                        تذكير قبل الموعد
                                    @endif
                                </strong>

                                <small>
                                    @if ($isAppointmentCompleted || $isAppointmentMissed)
                                        راسل الطبيب أو احجز موعدًا جديدًا.
                                    @elseif ($meetingUrl)
                                        ادخل عبر الرابط في وقت الموعد.
                                    @else
                                        سيظهر لك تنبيه قبل الموعد بعد تأكيده.
                                    @endif
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="appt-note">
                        <i data-lucide="info"></i>

                        <p>
                            لا يظهر نموذج حجز موعد جديد طالما يوجد طلب موعد قيد الانتظار أو موعد مؤكد.
                            بعد انتهاء الموعد يمكنك حجز موعد متابعة جديد.
                        </p>
                    </div>
                </aside>
            </div>
        @endif
    @else
        @if ($isAppointmentRejected || $isAppointmentCancelled || $isBookingNewAfterFinished)
            <div class="appt-alert is-success">
                <i data-lucide="calendar-plus"></i>

                <span>
                    يمكنك الآن اختيار موعد جديد يناسبك وإرسال الطلب للطبيب.
                </span>
            </div>
        @endif

        <div class="appt-layout">
            <form method="POST" action="{{ route('patient.appointments.book') }}" class="appt-form-card" data-appt-form>
                @csrf

                <div class="appt-card-head">
                    <div>
                        <span>تفاصيل الموعد</span>
                        <h2>اختر الموعد المناسب</h2>
                    </div>

                    <i data-lucide="calendar-days"></i>
                </div>

                <div class="appt-form-grid">
                    <label class="appt-field">
                        <span>نوع الاستشارة</span>

                        <div class="appt-input">
                            <i data-lucide="video"></i>

                            <select name="consultation_type">
                                <option value="online" @selected(old('consultation_type') === 'online')>أونلاين</option>
                                <option value="clinic" @selected(old('consultation_type') === 'clinic')>حضوري</option>
                            </select>
                        </div>

                        @error('consultation_type')
                            <small>{{ $message }}</small>
                        @enderror
                    </label>

                    <div class="appt-field appt-date-field">
                        <span>تاريخ الموعد</span>

                        <button type="button" class="appt-input appt-date-trigger" data-appt-date-trigger>
                            <i data-lucide="calendar"></i>

                            <span class="appt-date-value" data-calendar-selected-label>
                                {{ filled(old('appointment_date')) ? $selectedCalendarLabel : 'اختاري التاريخ' }}
                            </span>
                        </button>

                        <input
                            type="hidden"
                            name="appointment_date"
                            data-appt-date-input
                            value="{{ old('appointment_date') }}"
                            min="{{ now()->toDateString() }}"
                        >

                        <small class="appt-date-message" data-date-message></small>

                        @error('appointment_date')
                            <small>{{ $message }}</small>
                        @enderror

                        <div class="appt-calendar-card" data-appt-calendar>
                            <div class="appt-calendar-head">
                                <div>
                                    <span>تقويم المواعيد</span>
                                    <h3>{{ $calendarMonthTitle }}</h3>
                                </div>

                                <small>
                                    اليوم المختار:
                                    <b data-calendar-selected-label>{{ $selectedCalendarLabel }}</b>
                                </small>

                                <button type="button" class="appt-calendar-close" data-calendar-close aria-label="إغلاق التقويم">
                                    <i data-lucide="x"></i>
                                </button>
                            </div>

                            <div class="appt-calendar-weekdays">
                                @foreach ($calendarWeekdays as $weekday)
                                    <span>{{ $weekday }}</span>
                                @endforeach
                            </div>

                            <div class="appt-calendar-grid">
                                @foreach ($calendarDays as $calendarDay)
                                    <button
                                        type="button"
                                        class="appt-calendar-day {{ $calendarDay['classes'] }}"
                                        data-appt-calendar-day
                                        data-date-value="{{ $calendarDay['value'] }}"
                                        data-date-label="{{ $calendarDay['label'] }}"
                                        data-date-status="{{ $calendarDay['status'] }}"
                                        data-date-message="{{ $calendarDay['message'] }}"
                                        aria-pressed="{{ $calendarDay['pressed'] }}"
                                        title="{{ $calendarDay['message'] }}"
                                        @disabled($calendarDay['disabled'])
                                    >
                                        <span>{{ $calendarDay['day'] }}</span>
                                    </button>
                                @endforeach
                            </div>

                            <div class="appt-calendar-legend">
                                <span><b class="legend-dot is-selected"></b> مختار</span>
                                <span><b class="legend-dot is-appointment"></b> موعد</span>
                                <span><b class="legend-dot is-today"></b> اليوم</span>
                                <span><b class="legend-dot is-disabled"></b> غير متاح</span>
                            </div>
                        </div>
                    </div>

                    <label class="appt-field appt-field-wide">
                        <span>سبب الحجز</span>

                        <div class="appt-input">
                            <i data-lucide="clipboard-list"></i>

                            <select name="reason">
                                <option value="">اختر سبب الموعد</option>

                                @foreach (($appointmentReasons ?? []) as $key => $label)
                                    <option value="{{ $key }}" @selected(old('reason') === $key)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        @error('reason')
                            <small>{{ $message }}</small>
                        @enderror
                    </label>
                </div>
                <input type="hidden" name="appointment_time" value="{{ old('appointment_time') }}" data-selected-time>
                <div class="appt-slots-box appt-orbit-time-box" data-slot-picker>
                    <div class="appt-subhead">
                        <span>اختيار الوقت</span>
                        <strong>اختاري ساعة الموعد</strong>
                    </div>

                    @if (!$hasAvailableSlots)
                        <div class="appt-empty-slots">
                            <i data-lucide="calendar-x-2"></i>
                            <div>
                                <strong>لا توجد أوقات متاحة لهذا التاريخ</strong>
                                <span>اختاري يومًا آخر من التقويم، أو انتظري تحديث جدول الطبيب.</span>
                            </div>
                        </div>
                    @else
                        <div class="appt-time-launch-row">
                            <button type="button" class="appt-time-launch" data-time-picker-trigger>
                                <span class="time-launch-icon"><i data-lucide="clock-3"></i></span>

                                <span class="time-launch-copy">
                                    <small>الوقت المختار</small>
                                    <strong data-selected-time-label>{{ $selectedAppointmentTimeForPicker ?: 'اختاري الوقت' }}</strong>
                                </span>

                                <span class="time-launch-action" aria-hidden="true"><i data-lucide="clock-3"></i></span>
                            </button>
                        </div>

                        <div class="appt-time-popover" data-time-picker aria-hidden="true">
                            <div class="appt-time-popover-card appt-orbit-popover-card" role="dialog" aria-modal="true" aria-label="اختيار الوقت المناسب">
                                <div class="appt-picker-head">
                                    <div>
                                        <span>اختيار الوقت</span>
                                        <h3>اختاري الوقت المناسب</h3>
                                    </div>

                                    <button type="button" class="appt-picker-close" data-time-picker-close aria-label="إغلاق محدد الوقت">
                                        <i data-lucide="x"></i>
                                    </button>
                                </div>

                                <div class="appt-period-tabs" role="tablist" aria-label="فترات اليوم">
                                    @foreach ($slotGroups as $period)
                                        <button
                                            type="button"
                                            class="appt-period-tab {{ (int) $selectedPeriodIndex === $loop->index ? 'is-active' : '' }}"
                                            data-time-period-tab
                                            data-period-key="period-{{ $loop->index }}"
                                            aria-pressed="{{ (int) $selectedPeriodIndex === $loop->index ? 'true' : 'false' }}"
                                        >
                                            <i data-lucide="{{ $period['icon'] }}"></i>
                                            <span>{{ $period['label'] }}</span>
                                        </button>
                                    @endforeach
                                </div>

                                <div class="appt-orbit-stage">
                                    @foreach ($slotGroups as $period)
                                        @php
                                            $periodTimes = $period['times'] ?? [];
                                            $totalPeriodTimes = max(count($periodTimes), 1);
                                            $periodFirstTime = $periodTimes[0] ?? $firstAvailableSlot;
                                        @endphp

                                        <section
                                            class="appt-orbit-panel {{ (int) $selectedPeriodIndex === $loop->index ? 'is-active' : '' }}"
                                            data-time-period-panel
                                            data-period-key="period-{{ $loop->index }}"
                                        >
                                            <div class="appt-orbit-dial" data-clock-face>
                                                <div class="appt-orbit-center">
                                                    <span>{{ $period['label'] }}</span>
                                                    <strong data-selected-time-label>{{ $selectedAppointmentTimeForPicker ?: 'اختاري الوقت' }}</strong>
                                                    <small>الأوقات المتاحة</small>
                                                </div>

                                                @foreach ($periodTimes as $time)
                                                    @php
                                                        $slotAngle = round($loop->index * (360 / $totalPeriodTimes), 3);
                                                    @endphp

                                                    <button
                                                        type="button"
                                                        class="slot-btn orbit-slot {{ $selectedAppointmentTimeForPicker === $time ? 'is-active' : '' }}"
                                                        data-time-value="{{ $time }}"
                                                        style="--slot-angle: {{ $slotAngle }}deg"
                                                    >
                                                        <span class="slot-time">{{ $time }}</span>
                                                    </button>
                                                @endforeach
                                            </div>
                                        </section>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    <small class="appt-error" data-slot-unavailable-message></small>

                    @error('appointment_time')
                        <small class="appt-error is-visible">{{ $message }}</small>
                    @enderror
                </div>

                <label class="appt-field">
                    <span>ملاحظات للطبيب</span>

                    <textarea
                        name="notes"
                        rows="4"
                        placeholder="اكتب أي تفاصيل تريد أن يعرفها الطبيب قبل الموعد..."
                    >{{ old('notes') }}</textarea>

                    @error('notes')
                        <small>{{ $message }}</small>
                    @enderror
                </label>

                <div class="appt-form-actions">
                    <button type="submit" class="appt-primary-btn">
                        إرسال طلب الموعد
                        <i data-lucide="send"></i>
                    </button>

                    <a href="{{ route('patient.profile') }}" class="appt-outline-btn">
                        العودة للملف
                    </a>
                </div>
            </form>

            <aside class="appt-side-card">
                <div class="appt-card-head compact">
                    <div>
                        <span>ماذا يحدث بعد الحجز؟</span>
                        <h2>خطوات بسيطة</h2>
                    </div>
                </div>

                <div class="appt-flow">
                    <div>
                        <span>1</span>
                        <div>
                            <strong>إرسال الطلب</strong>
                            <small>يتم حفظ الموعد في حسابك.</small>
                        </div>
                    </div>

                    <div>
                        <span>2</span>
                        <div>
                            <strong>مراجعة الموعد</strong>
                            <small>الطبيب يراجع الموعد ويؤكده أو يقترح وقتًا بديلًا.</small>
                        </div>
                    </div>

                    <div>
                        <span>3</span>
                        <div>
                            <strong>تحديث حالة الموعد</strong>
                            <small>سيظهر التحديث داخل صفحة الموعد مباشرة.</small>
                        </div>
                    </div>
                </div>

                <div class="appt-note">
                    <i data-lucide="info"></i>
                    <p>
                        بعد إرسال الطلب، سيراجع الطبيب الموعد. عند التأكيد أو اقتراح وقت بديل سيظهر التحديث هنا في صفحة الموعد.
                    </p>
                </div>
            </aside>
        </div>
    @endif
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const checker = document.querySelector('.appt-current-card [data-appointment-checker]');

    if (!checker || checker.dataset.fullPageCalendarReady === '1') {
        return;
    }

    checker.dataset.fullPageCalendarReady = '1';

    const home = document.createComment('appt-month-calendar-home');
    checker.parentNode.insertBefore(home, checker);

    function syncMonthCalendarPosition() {
        const isOpen = checker.classList.contains('is-open');

        document.body.classList.toggle('appt-month-is-open', isOpen);

        if (isOpen && checker.parentNode !== document.body) {
            document.body.appendChild(checker);
        }

        if (!isOpen && checker.parentNode === document.body && home.parentNode) {
            home.parentNode.insertBefore(checker, home.nextSibling);
        }
    }

    const observer = new MutationObserver(syncMonthCalendarPosition);

    observer.observe(checker, {
        attributes: true,
        attributeFilter: ['class']
    });

    checker.addEventListener('click', function (event) {
        if (checker.classList.contains('is-open') && event.target === checker) {
            checker.classList.remove('is-open');
            syncMonthCalendarPosition();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && checker.classList.contains('is-open')) {
            checker.classList.remove('is-open');
            syncMonthCalendarPosition();
        }
    });

    syncMonthCalendarPosition();
});
</script>
@endpush
