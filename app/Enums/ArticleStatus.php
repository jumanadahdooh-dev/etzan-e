<?php

namespace App\Enums;

/**
 * مرجع فقط للـ validation — مش مربوط بالموديل كـ cast (راجع ملاحظة AppointmentStatus).
 */
enum ArticleStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Published = 'published';
    case Rejected = 'rejected';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
