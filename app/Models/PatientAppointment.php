<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientAppointment extends Model
{
    use HasFactory;

    protected $table = 'patient_appointments';

    protected $fillable = [
        'user_id',
        'patient_profile_id',
        'doctor_profile_id',
        'appointment_date',
        'appointment_time',
        'consultation_type',
        'reason',
        'notes',
        'status',
        'doctor_response_message',
        'suggested_date',
        'suggested_time',
        'patient_response_status',
        'patient_response_message',
        'confirmed_at',
        'rejected_at',
        'cancelled_at',
        'completed_at',
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'suggested_date' => 'date',
        'confirmed_at' => 'datetime',
        'rejected_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function doctorProfile()
    {
        return $this->belongsTo(DoctorProfile::class, 'doctor_profile_id');
    }
}
