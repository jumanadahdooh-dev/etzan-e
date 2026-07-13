<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientProfile extends Model
{
    protected $fillable = [
        'user_id',
        'phone',
        'birth_date',
        'gender',
        'city',
        'height',
        'weight',
        'notes',
        'profile_completed',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'profile_completed' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
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
