<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\DoctorSchedule;
use App\Models\DoctorProfile;
use App\Models\PatientDoctor;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PatientAppointmentController extends Controller
{
    /**
     * عرض صفحة حجز الموعد / المتابعة
     */
    public function followUp()
    {
        $user = Auth::user();

        // ✅ التحقق من وجود المستخدم
        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'يرجى تسجيل الدخول أولاً.');
        }

        // ✅ التحقق من وجود Patient
        if (!$user->patient) {
            return redirect()->route('patient.profile')
                ->with('error', 'يرجى إكمال بيانات ملفك الشخصي أولاً.');
        }

        $patient = $user->patient;

        // ============================================
        // 1. جلب بيانات الطبيب المتابع
        // ============================================
        $patientDoctor = PatientDoctor::where('patient_id', $patient->id)
            ->where('is_selected', true)
            ->with('doctorProfile.user')
            ->first();

        $doctor = null;
        $doctorStatus = null;

        if ($patientDoctor && $patientDoctor->doctorProfile) {
            $doctor = [
                'id' => $patientDoctor->doctorProfile->id,
                'name' => $patientDoctor->doctorProfile->user->name ?? 'طبيب',
                'specialty' => $patientDoctor->doctorProfile->specialty ?? 'اختصاصي',
                'avatar' => $patientDoctor->doctorProfile->user->avatar ?? asset('images/default-avatar.png'),
                'is_selected' => true,
                'request_status' => $patientDoctor->status ?? 'pending',
            ];
            $doctorStatus = $doctor['request_status'];
        }

        // ============================================
        // 2. جلب الموعد القادم للمريض
        // ============================================
        $nextAppointment = Appointment::where('patient_id', $patient->id)
            ->whereIn('status', ['pending', 'confirmed', 'approved', 'starting_now', 'reschedule_requested'])
            ->orderBy('date', 'asc')
            ->first();

        if (!$nextAppointment) {
            $nextAppointment = Appointment::where('patient_id', $patient->id)
                ->whereIn('status', ['completed', 'missed', 'rejected', 'cancelled'])
                ->orderBy('date', 'desc')
                ->first();
        }

        // تحويل الموعد إلى array مع البيانات المطلوبة
        if ($nextAppointment) {
            $nextAppointment = $nextAppointment->toArray();
            $nextAppointment['consultation_label'] = ($nextAppointment['consultation_type'] ?? 'online') === 'online' ? 'أونلاين' : 'حضوري';
            $nextAppointment['doctor_after_session_notes'] = $nextAppointment['doctor_notes'] ?? null;
        }

        // ============================================
        // 3. جلب الأوقات المتاحة بناءً على جدول الطبيب
        // ============================================
        $doctorId = $doctor['id'] ?? null;
        $availableSlots = $this->getAvailableSlotsForDoctor($doctorId);

        // ============================================
        // 4. تجهيز البيانات للـ View
        // ============================================
        $appointmentReasons = [
            'checkup' => 'فحص دوري',
            'follow_up' => 'متابعة حالة',
            'consultation' => 'استشارة طبية',
            'emergency' => 'حالة طارئة',
            'lab_results' => 'مناقشة نتائج تحاليل',
            'medication' => 'وصف علاج',
            'other' => 'أخرى',
        ];

        // بيانات إضافية للتقويم
        $availableDates = $this->getAvailableDatesForDoctor($doctorId);
        $fullyBookedDates = [];

        $monthlyAppointments = Appointment::where('patient_id', $patient->id)
            ->whereMonth('date', now()->month)
            ->get()
            ->toArray();

        return view('patient.followup', [
            'doctor' => $doctor,
            'nextAppointment' => $nextAppointment,
            'appointmentReasons' => $appointmentReasons,
            'availableSlots' => $availableSlots,
            'availableAppointmentDates' => $availableDates,
            'fullyBookedAppointmentDates' => $fullyBookedDates,
            'monthlyAppointments' => $monthlyAppointments,
            'availableDates' => $availableDates,
        ]);
    }

    /**
     * جلب الأوقات المتاحة للطبيب بناءً على جدول عمله
     */
    private function getAvailableSlotsForDoctor($doctorId, $date = null)
    {
        // ✅ إذا لم يكن هناك طبيب، أرجع أوقات وهمية للاختبار
        if (!$doctorId) {
            return $this->getFallbackSlots();
        }

        $date = $date ? Carbon::parse($date) : Carbon::now();
        $dayOfWeek = $date->dayOfWeek; // 0=Sunday, 6=Saturday

        // جلب جدول الطبيب لهذا اليوم
        $schedule = DoctorSchedule::where('doctor_profile_id', $doctorId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->first();

        if (!$schedule) {
            return $this->getFallbackSlots();
        }

        // توليد الأوقات بين start_time و end_time
        $startTime = Carbon::parse($schedule->start_time);
        $endTime = Carbon::parse($schedule->end_time);
        $interval = 30; // دقيقة

        $times = [];
        $current = $startTime->copy();

        while ($current->lt($endTime)) {
            $times[] = $current->format('H:i');
            $current->addMinutes($interval);
        }

        if (empty($times)) {
            return $this->getFallbackSlots();
        }

        // تقسيم الأوقات إلى فترات (صباحية / مسائية)
        $morning = [];
        $afternoon = [];
        $evening = [];

        foreach ($times as $time) {
            $hour = (int) substr($time, 0, 2);
            if ($hour < 12) {
                $morning[] = $time;
            } elseif ($hour < 17) {
                $afternoon[] = $time;
            } else {
                $evening[] = $time;
            }
        }

        $slots = [];

        if (!empty($morning)) {
            $slots[] = [
                'icon' => 'sunrise',
                'label' => 'الفترة الصباحية',
                'times' => $morning,
            ];
        }

        if (!empty($afternoon)) {
            $slots[] = [
                'icon' => 'sun',
                'label' => 'الفترة المسائية',
                'times' => $afternoon,
            ];
        }

        if (!empty($evening)) {
            $slots[] = [
                'icon' => 'moon',
                'label' => 'الفترة المسائية المتأخرة',
                'times' => $evening,
            ];
        }

        return !empty($slots) ? $slots : $this->getFallbackSlots();
    }

    /**
     * جلب التواريخ المتاحة للطبيب (الأيام التي يعمل بها)
     */
    private function getAvailableDatesForDoctor($doctorId)
    {
        if (!$doctorId) {
            return [];
        }

        $schedules = DoctorSchedule::where('doctor_profile_id', $doctorId)
            ->where('is_active', true)
            ->get();

        $dates = [];
        $startDate = Carbon::now();
        $endDate = Carbon::now()->addDays(30);

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dayOfWeek = $date->dayOfWeek;
            if ($schedules->contains('day_of_week', $dayOfWeek)) {
                $dates[] = $date->toDateString();
            }
        }

        return $dates;
    }

    /**
     * أوقات افتراضية (للاختبار)
     */
    private function getFallbackSlots()
    {
        return [
            [
                'icon' => 'sunrise',
                'label' => 'الفترة الصباحية',
                'times' => ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30'],
            ],
            [
                'icon' => 'sunset',
                'label' => 'الفترة المسائية',
                'times' => ['16:00', '16:30', '17:00', '17:30', '18:00', '18:30'],
            ],
        ];
    }

    /**
     * حجز موعد جديد
     */
    public function bookAppointment(Request $request)
    {
        $request->validate([
            'consultation_type' => 'required|in:online,clinic',
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $user = Auth::user();

        if (!$user || !$user->patient) {
            return redirect()->back()
                ->with('error', 'يرجى إكمال بيانات ملفك الشخصي أولاً.')
                ->withInput();
        }

        $patient = $user->patient;

        // التحقق من وجود طبيب متابعة
        $patientDoctor = PatientDoctor::where('patient_id', $patient->id)
            ->where('is_selected', true)
            ->where('status', 'approved')
            ->first();

        if (!$patientDoctor) {
            return redirect()->back()
                ->with('error', 'لا يمكنك حجز موعد قبل اختيار طبيب متابعة معتمد.')
                ->withInput();
        }

        // التحقق من عدم وجود موعد قيد الانتظار أو مؤكد
        $existingAppointment = Appointment::where('patient_id', $patient->id)
            ->whereIn('status', ['pending', 'confirmed', 'approved', 'starting_now', 'reschedule_requested'])
            ->first();

        if ($existingAppointment) {
            return redirect()->back()
                ->with('error', 'يوجد موعد قيد الانتظار أو مؤكد. لا يمكنك حجز موعد جديد.')
                ->withInput();
        }

        // إنشاء الموعد الجديد
        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'doctor_profile_id' => $patientDoctor->doctor_profile_id,
            'consultation_type' => $request->consultation_type,
            'date' => $request->appointment_date,
            'time' => $request->appointment_time,
            'reason' => $request->reason,
            'notes' => $request->notes,
            'status' => 'pending',
        ]);

        return redirect()->route('patient.followup')
            ->with('success', 'تم إرسال طلب الموعد بنجاح. بانتظار تأكيد الطبيب.');
    }

    /**
     * تحديث موعد موجود
     */
    public function updateAppointment(Request $request, $appointmentId)
    {
        $request->validate([
            'consultation_type' => 'required|in:online,clinic',
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $user = Auth::user();

        if (!$user || !$user->patient) {
            return redirect()->back()
                ->with('error', 'يرجى إكمال بيانات ملفك الشخصي أولاً.')
                ->withInput();
        }

        $patient = $user->patient;

        $appointment = Appointment::where('id', $appointmentId)
            ->where('patient_id', $patient->id)
            ->where('status', 'pending')
            ->first();

        if (!$appointment) {
            return redirect()->back()
                ->with('error', 'لا يمكن تعديل هذا الموعد. إما أنه غير موجود أو تم تأكيده بالفعل.')
                ->withInput();
        }

        $appointment->update([
            'consultation_type' => $request->consultation_type,
            'date' => $request->appointment_date,
            'time' => $request->appointment_time,
            'reason' => $request->reason,
            'notes' => $request->notes,
        ]);

        return redirect()->route('patient.followup')
            ->with('success', 'تم تحديث طلب الموعد بنجاح.');
    }

    /**
     * قبول الموعد المقترح من الطبيب
     */
    public function acceptSuggestedAppointment($appointmentId)
    {
        $user = Auth::user();

        if (!$user || !$user->patient) {
            return redirect()->back()
                ->with('error', 'يرجى إكمال بيانات ملفك الشخصي أولاً.');
        }

        $patient = $user->patient;

        $appointment = Appointment::where('id', $appointmentId)
            ->where('patient_id', $patient->id)
            ->where('status', 'reschedule_requested')
            ->first();

        if (!$appointment) {
            return redirect()->back()
                ->with('error', 'لا يوجد موعد مقترح لقبوله.');
        }

        // تحديث الموعد بالوقت المقترح
        $appointment->update([
            'date' => $appointment->suggested_date ?? $appointment->date,
            'time' => $appointment->suggested_time ?? $appointment->time,
            'status' => 'pending',
            'suggested_date' => null,
            'suggested_time' => null,
            'doctor_response_message' => null,
        ]);

        return redirect()->route('patient.followup')
            ->with('success', 'تم قبول الموعد المقترح بنجاح. بانتظار تأكيد الطبيب.');
    }

    /**
     * رفض الموعد المقترح من الطبيب
     */
    public function declineSuggestedAppointment($appointmentId)
    {
        $user = Auth::user();

        if (!$user || !$user->patient) {
            return redirect()->back()
                ->with('error', 'يرجى إكمال بيانات ملفك الشخصي أولاً.');
        }

        $patient = $user->patient;

        $appointment = Appointment::where('id', $appointmentId)
            ->where('patient_id', $patient->id)
            ->where('status', 'reschedule_requested')
            ->first();

        if (!$appointment) {
            return redirect()->back()
                ->with('error', 'لا يوجد موعد مقترح لرفضه.');
        }

        $appointment->update([
            'status' => 'patient_declined_reschedule',
            'doctor_response_message' => null,
        ]);

        return redirect()->route('patient.followup')
            ->with('success', 'تم رفض الموعد المقترح. يمكنك حجز موعد جديد.');
    }
}
