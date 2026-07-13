<?php

namespace App\Enums;

/**
 * مرجع فقط للـ validation — يستخدم لأي عمود "موافقة" بنفس النمط
 * (doctor_applications.status, patient_doctor_requests.status,
 * patient_profiles.doctor_request_status). مش مربوط بالموديل كـ cast
 * (راجع ملاحظة AppointmentStatus).
 */
enum ApprovalStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
