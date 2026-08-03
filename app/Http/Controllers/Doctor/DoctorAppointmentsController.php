<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\DoctorProfile;
use App\Models\PatientAppointment;
use App\Services\AppNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DoctorAppointmentsController extends Controller
{
    public function index(): View
    {
        $doctorProfile = DoctorProfile::where('user_id', auth()->id())->first();

        if (!$doctorProfile) {
            return view('doctor.appointments', [
                'pageTitle' => 'المواعيد',
                'activePage' => 'appointments',
                'todayAppointments' => collect(),
                'upcomingAppointments' => collect(),
                'pastAppointments' => collect(),
            ]);
        }

        $today = now()->toDateString();

        $todayAppointments = $doctorProfile->appointments()
            ->with('patient')
            ->whereDate('appointment_date', $today)
            ->orderBy('appointment_time')
            ->get();

        $upcomingAppointments = $doctorProfile->appointments()
            ->with('patient')
            ->whereDate('appointment_date', '>', $today)
            ->where('status', '!=', 'cancelled')
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->get();

        $pastAppointments = $doctorProfile->appointments()
            ->with('patient')
            ->whereDate('appointment_date', '<', $today)
            ->orderByDesc('appointment_date')
            ->orderByDesc('appointment_time')
            ->limit(20)
            ->get();

        return view('doctor.appointments', [
            'pageTitle' => 'المواعيد',
            'activePage' => 'appointments',
            'todayAppointments' => $todayAppointments,
            'upcomingAppointments' => $upcomingAppointments,
            'pastAppointments' => $pastAppointments,
        ]);
    }

    /**
     * تأكيد موعد "بانتظار". قبل هيك كان هاد الإجراء موجود بس بدون ما
     * يوصل أي إشعار للمريض — كان لازم يرجع يفتح الصفحة يدوياً ليعرف.
     */
    public function confirm(PatientAppointment $appointment): RedirectResponse
    {
        $doctorProfile = $this->authorizedDoctorProfile($appointment);

        $appointment->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        $this->notifyPatient(
            $appointment,
            type: 'appointment_confirmed',
            title: 'تم تأكيد موعدك',
            body: 'أكّد طبيبك موعدك بتاريخ ' . $appointment->appointment_date->locale('ar')->translatedFormat('j M Y') . '.'
        );

        return back()->with('success', 'تم تأكيد الموعد.');
    }

    /**
     * رفض موعد "بانتظار" — كان مش موجود إطلاقاً قبل هيك، رغم إنه رسالة
     * الإشعار يلي بتوصل للطبيب وقت الحجز كانت توعد بهالخيار.
     */
    public function reject(Request $request, PatientAppointment $appointment): RedirectResponse
    {
        $validated = $request->validate([
            'doctor_response_message' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->authorizedDoctorProfile($appointment);

        $appointment->update([
            'status' => 'rejected',
            'doctor_response_message' => $validated['doctor_response_message'] ?? null,
            'rejected_at' => now(),
        ]);

        $this->notifyPatient(
            $appointment,
            type: 'appointment_rejected',
            title: 'اعتذر طبيبك عن هالموعد',
            body: $validated['doctor_response_message'] ?? 'اعتذر طبيبك عن الموعد يلي طلبتيه. تقدري تحجزي موعد بوقت تاني.'
        );

        return back()->with('success', 'تم رفض الموعد وإشعار المريض.');
    }

    /**
     * اقتراح وقت/تاريخ بديل — نفس الميزة يلي واجهة المريض كانت جاهزة
     * إلها بالكامل (قبول/رفض الاقتراح) بس ما كان في طريقة الطبيب يبعتها.
     */
    public function suggestTime(Request $request, PatientAppointment $appointment): RedirectResponse
    {
        $validated = $request->validate([
            'suggested_date' => ['required', 'date', 'after_or_equal:today'],
            'suggested_time' => ['required', 'string', 'max:20'],
            'doctor_response_message' => ['nullable', 'string', 'max:1000'],
        ], [
            'suggested_date.required' => 'التاريخ المقترح مطلوب.',
            'suggested_date.after_or_equal' => 'ما بينفع تقترح تاريخ فات.',
            'suggested_time.required' => 'الوقت المقترح مطلوب.',
        ]);

        $this->authorizedDoctorProfile($appointment);

        $appointment->update([
            'status' => 'reschedule_requested',
            'suggested_date' => $validated['suggested_date'],
            'suggested_time' => $validated['suggested_time'],
            'doctor_response_message' => $validated['doctor_response_message'] ?? null,
        ]);

        $this->notifyPatient(
            $appointment,
            type: 'appointment_reschedule_requested',
            title: 'طبيبك اقترح موعد بديل',
            body: 'اقترح طبيبك موعد بديل، افتحي صفحة المتابعة لقبوله أو رفضه.'
        );

        return back()->with('success', 'تم إرسال اقتراح الموعد للمريض.');
    }

    private function authorizedDoctorProfile(PatientAppointment $appointment): DoctorProfile
    {
        $doctorProfile = DoctorProfile::where('user_id', auth()->id())->first();

        abort_unless($doctorProfile && (int) $appointment->doctor_profile_id === (int) $doctorProfile->id, 403);

        return $doctorProfile;
    }

    private function notifyPatient(PatientAppointment $appointment, string $type, string $title, string $body): void
    {
        app(AppNotificationService::class)->send(
            recipientUserId: $appointment->user_id,
            recipientRole: 'patient',
            type: $type,
            title: $title,
            body: $body,
            url: route('patient.followup'),
            actorUserId: auth()->id(),
            relatedId: $appointment->id,
            relatedType: 'patient_appointment'
        );
    }
}
