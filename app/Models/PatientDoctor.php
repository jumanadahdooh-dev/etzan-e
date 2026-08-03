<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientDoctor extends Model
{
    protected $table = 'patient_doctors';

    protected $fillable = [
        'patient_id',
        'doctor_profile_id',
        'status',
        'is_selected',
        'requested_at',
        'approved_at',
        'rejected_at',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'is_selected' => 'boolean',
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * العلاقة مع المريض
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * العلاقة مع ملف الطبيب
     */
    public function doctorProfile(): BelongsTo
    {
        return $this->belongsTo(DoctorProfile::class);
    }

    /**
     * نطاق لجلب الطلبات المعلقة
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * نطاق لجلب الطلبات المقبولة
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * نطاق لجلب الطلبات المرفوضة
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * نطاق لجلب الطلبات المكتملة
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * نطاق لجلب الطلب المختار (المتابع)
     */
    public function scopeSelected($query)
    {
        return $query->where('is_selected', true);
    }

    /**
     * الحصول على تسمية الحالة بالعربية
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'بانتظار الموافقة',
            'approved' => 'تمت الموافقة',
            'rejected' => 'تم الاعتذار',
            'completed' => 'مكتمل',
            default => '—',
        };
    }
}
