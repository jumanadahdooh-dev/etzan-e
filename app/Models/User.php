<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function patientProfile()
    {
        return $this->hasOne(PatientProfile::class);
    }

    public function doctorProfile()
    {
        return $this->hasOne(DoctorProfile::class);
    }

    public function isPatient()
    {
        return $this->role === 'patient';
    }

    public function isDoctor()
    {
        return $this->role === 'doctor';
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }



    public function aiChatConversations(): HasMany
    {
        return $this->hasMany(AiChatConversation::class);
    }


    public function patientMeals(): HasMany
    {
        return $this->hasMany(PatientMeal::class);
    }
}
