<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'workplace',
        'license_number',
        'years_experience',
        'bio',
        'photo_path',
        'is_available',
    ];

    protected $casts = [
        'is_available' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function specialties()
    {
        return $this->belongsToMany(
            Specialty::class,
            'doctor_specialty',
            'doctor_profile_id',
            'specialty_id'
        )->withTimestamps();
    }

    /** مواعيد هاد الطبيب (جدول patient_appointments) */
    public function appointments()
    {
        return $this->hasMany(\App\Models\PatientAppointment::class, 'doctor_profile_id');
    }

    /** ملفات المرضى المرتبطين بهاد الطبيب فعلياً (patient_profiles.doctor_profile_id) */
    public function patientProfiles()
    {
        return $this->hasMany(PatientProfile::class, 'doctor_profile_id');
    }

    /** طلبات الاستشارة الموجهة لهاد الطبيب */
    public function patientRequests()
    {
        return $this->hasMany(\App\Models\PatientDoctorRequest::class, 'doctor_profile_id');
    }

    /** تقييمات المرضى لهاد الطبيب */
    public function reviews()
    {
        return $this->hasMany(\App\Models\DoctorReview::class, 'doctor_profile_id');
    }

    /** سجلات وزن المرضى المتابعين مع هاد الطبيب */
    public function weightLogs()
    {
        return $this->hasMany(\App\Models\PatientWeightLog::class, 'doctor_profile_id');
    }

    /** أهداف السعرات اليومية يلي حددها هاد الطبيب لمرضاه */
    public function calorieGoals()
    {
        return $this->hasMany(\App\Models\PatientDailyCalorieGoal::class, 'doctor_profile_id');
    }

    /** جدول ساعات العمل الأسبوعي لهاد الطبيب */
    public function schedules()
    {
        return $this->hasMany(\App\Models\DoctorSchedule::class, 'doctor_profile_id')
            ->orderBy('day_of_week');
    }
}
