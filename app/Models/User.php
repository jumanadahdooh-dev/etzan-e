<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

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

    public function appointments(): HasMany
    {
        return $this->hasMany(PatientAppointment::class, 'user_id');
    }

    public function patientTasks(): HasMany
    {
        return $this->hasMany(PatientTask::class, 'patient_user_id');
    }

    public function doctorReviews(): HasMany
    {
        return $this->hasMany(DoctorReview::class, 'patient_id');
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'user_id');
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function reviewedDoctorApplications(): HasMany
    {
        return $this->hasMany(DoctorApplication::class, 'reviewed_by');
    }

    public function patientDoctorRequests(): HasMany
    {
        return $this->hasMany(PatientDoctorRequest::class, 'patient_id');
    }

    public function weightLogs(): HasMany
    {
        return $this->hasMany(PatientWeightLog::class, 'user_id');
    }

    public function calorieGoals(): HasMany
    {
        return $this->hasMany(PatientDailyCalorieGoal::class, 'user_id');
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'user_id');
    }
}
