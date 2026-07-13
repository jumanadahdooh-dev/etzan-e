<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'patient_user_id',
        'doctor_user_id',
        'created_by_id',
        'created_by_type',
        'source',
        'title',
        'description',
        'task_date',
        'task_time',
        'status',
        'completed_at',
        'repeat_type',
        'reminder_minutes',
        'reminder_sent_at',
        'due_notification_sent_at',
        'late_notification_sent_at',
        'requires_attachment',
        'attachment_path',
        'patient_note',
    ];

    protected $casts = [
        'task_date' => 'date',
        'completed_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'due_notification_sent_at' => 'datetime',
        'late_notification_sent_at' => 'datetime',
        'requires_attachment' => 'boolean',
        'reminder_minutes' => 'integer',
    ];

    public function getIsCompletedAttribute(): bool
    {
        return $this->status === 'completed';
    }

    public function getIsDoctorTaskAttribute(): bool
    {
        return $this->source === 'doctor' || $this->created_by_type === 'doctor';
    }

    public function getIsPatientTaskAttribute(): bool
    {
        return ! $this->is_doctor_task;
    }
}