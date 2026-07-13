<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientWeightLog extends Model
{
    protected $fillable = [
        'user_id',
        'patient_profile_id',
        'doctor_profile_id',
        'weight_kg',
        'logged_date',
        'source',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'logged_date' => 'date',
            'weight_kg' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function patientProfile()
    {
        return $this->belongsTo(PatientProfile::class);
    }

    public function doctorProfile()
    {
        return $this->belongsTo(DoctorProfile::class);
    }
}