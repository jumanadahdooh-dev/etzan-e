<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorApplication extends Model
{
    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'workplace',
        'specialty',
        'experience_years',
        'license_number',
        'bio',
        'profile_photo_path',
        'license_file_path',
        'cv_file_path',
        'status',
        'reviewed_by',
        'reviewed_at',
        'admin_note',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
