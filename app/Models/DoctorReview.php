<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorReview extends Model
{
    protected $table = 'doctor_reviews';

    protected $fillable = [
        'doctor_profile_id',
        'patient_id',
        'patient_doctor_request_id',
        'rating',
        'comment',
        'is_recommended',
    ];

    protected $casts = [
        'is_recommended' => 'boolean',
    ];

    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function doctorProfile()
    {
        return $this->belongsTo(DoctorProfile::class, 'doctor_profile_id');
    }
}
