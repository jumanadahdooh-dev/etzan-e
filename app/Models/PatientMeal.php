<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientMeal extends Model
{
    protected $fillable = [
        'user_id',
        'patient_profile_id',
        'doctor_profile_id',
        'doctor_user_id',
        'meal_date',
        'meal_type',
        'meal_name',
        'description',
        'image_path',
        'calories',
        'protein',
        'carbs',
        'fat',
        'confidence',
        'ai_notes',
        'patient_note',
        'ai_response',
        'source',
        'status',
    ];

    protected $casts = [
        'meal_date' => 'date',
        'ai_response' => 'array',
        'calories' => 'integer',
        'protein' => 'integer',
        'carbs' => 'integer',
        'fat' => 'integer',
        'confidence' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
