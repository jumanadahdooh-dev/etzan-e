<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\DoctorProfile;
use App\Models\Condition;
use App\Models\Article;

class Specialty extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'icon',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function doctorProfiles()
    {
        return $this->belongsToMany(
            DoctorProfile::class,
            'doctor_specialty',
            'specialty_id',
            'doctor_profile_id'
        )->withTimestamps();
    }

    public function conditions()
    {
        return $this->belongsToMany(
            Condition::class,
            'condition_specialty',
            'specialty_id',
            'condition_id'
        )->withTimestamps();
    }

    public function articles()
    {
        return $this->hasMany(Article::class);
    }
}
