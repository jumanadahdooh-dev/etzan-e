<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientAppointment extends Model
{
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
    ];

    protected $casts = [
        'appointment_date' => 'date',
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
