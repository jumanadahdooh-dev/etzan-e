<?php

namespace App\Enums;

/**
 * مرجع لكل القيم الممكنة لعمود patient_appointments.status كما هي مستخدمة فعلياً
 * بالكود (PatientAppointmentController وPatientContextHelpers). ملاحظة: القيمتين 'cancelled' و 'canceled'
 * موجودتين مع بعض بالكود (خطأ إملائي تاريخي) — تركناهم متل ما هم بدل ما نعدل
 * بيانات حقيقية بدون تأكيد. هاد enum مرجعي فقط للـ validation، مش مربوط
 * بالموديل كـ cast حتى ما ينهار تحميل أي صف قديم فيه قيمة غير متوقعة.
 */
enum AppointmentStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case CancelledAlt = 'canceled';
    case Rejected = 'rejected';
    case Declined = 'declined';
    case RescheduleRequested = 'reschedule_requested';
    case PatientDeclinedReschedule = 'patient_declined_reschedule';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
