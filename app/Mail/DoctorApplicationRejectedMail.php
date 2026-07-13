<?php

namespace App\Mail;

use App\Models\DoctorApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DoctorApplicationRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public DoctorApplication $doctorApplication;

    public function __construct(DoctorApplication $doctorApplication)
    {
        $this->doctorApplication = $doctorApplication;
    }

    public function build()
    {
        return $this->subject('تحديث بخصوص طلب انضمامك في منصة اتزان')
            ->view('emails.doctor-application-rejected');
    }
}
