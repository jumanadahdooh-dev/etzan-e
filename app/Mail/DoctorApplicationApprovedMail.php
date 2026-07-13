<?php

namespace App\Mail;

use App\Models\DoctorApplication;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DoctorApplicationApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public DoctorApplication $doctorApplication;
    public User $user;
    public string $setupUrl;

    public function __construct(DoctorApplication $doctorApplication, User $user, string $setupUrl)
    {
        $this->doctorApplication = $doctorApplication;
        $this->user = $user;
        $this->setupUrl = $setupUrl;
    }

    public function build()
    {
        return $this->subject('تم قبول طلب انضمامك كطبيب في منصة اتزان')
            ->view('emails.doctor-application-approved');
    }
}
