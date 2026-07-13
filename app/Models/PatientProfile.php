<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'doctor_profile_id',
        'has_selected_doctor',
        'doctor_request_status',
        'phone',
        'birth_date',
        'gender',
        'city',
        'height',
        'weight',
        'target_weight_kg',
        'notes',
        'profile_completed',
        'health_goal',
        'activity_level',
        'medical_conditions',
        'medications',
        'allergies',
        'meals_per_day',
        'sleep_hours',
        'water_cups',
        'preferred_doctor_gender',
        'preferred_consultation_type',
        'avatar',
        'suggested_calorie_goal',
        'doctor_calorie_goal',
        'calorie_goal_status',
        'calorie_goal_note',
        'calorie_goal_updated_at',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'profile_completed' => 'boolean',
        'has_selected_doctor' => 'boolean',
        'medical_conditions' => 'array',
        'calorie_goal_updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function doctorProfile()
    {
        return $this->belongsTo(DoctorProfile::class);
    }

    public function conditions()
    {
        return $this->belongsToMany(
            Condition::class,
            'patient_condition',
            'patient_profile_id',
            'condition_id'
        )->withTimestamps();
    }
}
