<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientDailyCalorieGoal extends Model
{
    protected $fillable = [
        'user_id',
        'patient_profile_id',
        'doctor_profile_id',
        'doctor_user_id',
        'goal_date',
        'calories_goal',
        'protein_goal',
        'carbs_goal',
        'fat_goal',
        'status',
        'doctor_note',
        'approved_at',
    ];

    protected $casts = [
        'goal_date' => 'date',
        'approved_at' => 'datetime',
        'calories_goal' => 'integer',
        'protein_goal' => 'integer',
        'carbs_goal' => 'integer',
        'fat_goal' => 'integer',
    ];

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

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_user_id');
    }
}
