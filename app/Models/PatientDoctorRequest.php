<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientDoctorRequest extends Model
{
    protected $table = 'patient_doctor_requests';

    protected $fillable = [
        'patient_id',
        'patient_profile_id',
        'doctor_profile_id',
        'doctor_user_id',
        'status',
        'patient_message',
        'doctor_response',
        'approved_at',
        'rejected_at',
        'cancelled_at',
        'completed_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function patientProfile()
    {
        return $this->belongsTo(PatientProfile::class, 'patient_profile_id');
    }

    public function doctorProfile()
    {
        return $this->belongsTo(DoctorProfile::class, 'doctor_profile_id');
    }
}
