<?php

namespace App\Enums;

/**
 * مرجع فقط للـ validation — مش مربوط بالموديل كـ cast (راجع ملاحظة AppointmentStatus).
 */
enum TaskStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
