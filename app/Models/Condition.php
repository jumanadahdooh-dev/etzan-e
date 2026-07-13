<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Condition extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function patientProfiles()
    {
        return $this->belongsToMany(
            PatientProfile::class,
            'patient_condition',
            'condition_id',
            'patient_profile_id'
        )->withTimestamps();
    }

    public function specialties()
    {
        return $this->belongsToMany(
            Specialty::class,
            'condition_specialty',
            'condition_id',
            'specialty_id'
        )->withTimestamps();
    }
}
