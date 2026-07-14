<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Patient\Concerns\PatientContextHelpers;
use App\Http\Requests\Patient\BookAppointmentRequest;
use App\Http\Requests\Patient\CompleteProfileRequest;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\PatientDailyCalorieGoal;
use App\Models\PatientMeal;
use App\Services\AiMealAnalysisService;
use App\Services\AppNotificationService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PatientAppointmentController extends Controller
{
    use PatientContextHelpers;

public function followUp(): View
    {
        $data = $this->dashboardData([
            'pageTitle' => 'حجز موعد',
            'activePage' => 'followup',
        ]);

        $user = auth()->user();
        $profile = $this->patientProfile($user?->id);
        $doctor = $data['doctor'] ?? $this->selectedDoctor($profile);
        $nextAppointment = $data['nextAppointment'] ?? null;

        $selectedDate = request('appointment_date')
            ?: request('date')
            ?: old('appointment_date')
            ?: ($nextAppointment['date'] ?? now()->toDateString());

        try {
            $selectedDate = Carbon::parse($selectedDate)->toDateString();
        } catch (\Throwable $exception) {
            $selectedDate = now()->toDateString();
        }

        $ignoreAppointmentId = request()->boolean('edit') && !empty($nextAppointment['id'])
            ? (int) $nextAppointment['id']
            : null;

        $doctorProfileId = !empty($doctor['id']) ? (int) $doctor['id'] : null;

        $data['selectedAppointmentDate'] = $selectedDate;
        $data['availableSlots'] = $this->appointmentSlots($doctorProfileId, $selectedDate, $ignoreAppointmentId);

        $monthMeta = $doctorProfileId
            ? $this->appointmentMonthMeta($doctorProfileId, $selectedDate, $ignoreAppointmentId)
            : [
                'availableAppointmentDates' => [],
                'fullyBookedAppointmentDates' => [],
            ];

        $data['availableAppointmentDates'] = $monthMeta['availableAppointmentDates'];
        $data['availableDates'] = $monthMeta['availableAppointmentDates'];
        $data['fullyBookedAppointmentDates'] = $monthMeta['fullyBookedAppointmentDates'];
        $data['fullyBookedDates'] = $monthMeta['fullyBookedAppointmentDates'];
        $data['calendarAppointments'] = $this->calendarAppointmentsForPatient(
            $user?->id,
            $profile?->id ?? null,
            $selectedDate
        );

        $data['appointmentReasons'] = [
            'first_consultation' => 'استشارة أولى',
            'followup' => 'متابعة دورية',
            'nutrition_plan' => 'مراجعة الخطة الغذائية',
            'medical_question' => 'استفسار صحي',
            'progress_review' => 'مراجعة التقدم',
        ];

        return view('patient.appointments', $data);
    }


public function bookAppointment(BookAppointmentRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $profile = $this->patientProfile($user->id);

        if (!$profile) {
            return redirect()->route('patient.profile')->with('error', 'أكمل ملفك الصحي أولًا قبل حجز موعد.');
        }

        $doctor = $this->selectedDoctor($profile);
        $doctorRequestStatus = $doctor['request_status'] ?? null;

        if (($doctor['is_selected'] ?? false) !== true || $doctorRequestStatus !== 'approved') {
            return redirect()->route('patient.profile')->with('error', 'لا يمكنك حجز موعد قبل موافقة الطبيب على طلب المتابعة.');
        }

        if (!$this->tableExists('patient_appointments')) {
            return back()->withInput()->with('error', 'جدول patient_appointments غير موجود.');
        }

        $doctorProfileId = (int) ($doctor['id'] ?? 0);
        $appointmentDate = Carbon::parse($validated['appointment_date'])->toDateString();
        $appointmentTime = $this->normalizeAppointmentTime($validated['appointment_time']);

        if (!$doctorProfileId || !$this->isAppointmentSlotAvailable($doctorProfileId, $appointmentDate, $appointmentTime)) {
            return back()
                ->withInput()
                ->with('error', 'هذا الوقت لم يعد متاحًا، اختاري وقتًا آخر من الأوقات المتاحة.');
        }

        $payload = [
            'user_id' => $user->id,
            'patient_profile_id' => $profile->id ?? null,
            'doctor_profile_id' => $doctorProfileId,
            'appointment_date' => $appointmentDate,
            'appointment_time' => $appointmentTime,
            'consultation_type' => $validated['consultation_type'],
            'reason' => $validated['reason'],
            'notes' => $validated['notes'] ?? null,
            'status' => 'pending',
        ];

        if ($this->columnExists('patient_appointments', 'created_at')) {
            $payload['created_at'] = now();
        }

        if ($this->columnExists('patient_appointments', 'updated_at')) {
            $payload['updated_at'] = now();
        }

        $appointmentId = DB::table('patient_appointments')->insertGetId($payload);

        $this->createAppNotification(
            recipientUserId: $user->id,
            actorUserId: null,
            type: 'appointment_pending',
            title: 'تم إرسال طلب الموعد',
            body: 'موعدك بانتظار تأكيد الطبيب. سيتم تحديث حالة الموعد بعد مراجعة الطبيب.',
            url: route('patient.followup'),
            appointmentId: $appointmentId
        );

        if (!empty($doctor['user_id'])) {
            $this->createAppNotification(
                recipientUserId: (int) $doctor['user_id'],
                actorUserId: $user->id,
                type: 'appointment_request_received',
                title: 'طلب موعد جديد',
                body: 'وصل طلب موعد جديد من المريض، راجع التاريخ والوقت ثم أكد الطلب أو اقترح وقتًا بديلًا.',
                url: url('/doctor/appointments'),
                appointmentId: $appointmentId,
                recipientRole: 'doctor',
                data: [
                    'appointment_date' => $appointmentDate,
                    'appointment_time' => $appointmentTime,
                    'patient_user_id' => $user->id,
                    'doctor_profile_id' => $doctorProfileId,
                ]
            );
        }

        return redirect()
            ->route('patient.followup')
            ->with('success', 'تم إرسال طلب الموعد بنجاح. سيراجع الطبيب الطلب.');
    }


public function updateAppointment(Request $request, int $appointment): RedirectResponse
    {
        $validated = $request->validate([
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'appointment_time' => ['required', 'string', 'max:20'],
            'consultation_type' => ['required', 'in:online,clinic'],
            'reason' => ['required', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$this->tableExists('patient_appointments')) {
            return back()->with('error', 'جدول المواعيد غير موجود.');
        }

        $appointmentRow = DB::table('patient_appointments')
            ->where('id', $appointment)
            ->where('user_id', $user->id)
            ->first();

        if (!$appointmentRow) {
            return redirect()->route('patient.followup')->with('error', 'طلب الموعد غير موجود.');
        }

        if (($appointmentRow->status ?? null) !== 'pending') {
            return redirect()->route('patient.followup')->with('error', 'لا يمكن تعديل الموعد بعد رد الطبيب عليه.');
        }

        $doctorProfileId = (int) ($appointmentRow->doctor_profile_id ?? 0);
        $appointmentDate = Carbon::parse($validated['appointment_date'])->toDateString();
        $appointmentTime = $this->normalizeAppointmentTime($validated['appointment_time']);

        if ($doctorProfileId && !$this->isAppointmentSlotAvailable($doctorProfileId, $appointmentDate, $appointmentTime, $appointment)) {
            return back()
                ->withInput()
                ->with('error', 'هذا الوقت لم يعد متاحًا، اختاري وقتًا آخر من الأوقات المتاحة.');
        }

        $payload = [];

        if ($this->columnExists('patient_appointments', 'appointment_date')) {
            $payload['appointment_date'] = $appointmentDate;
        }

        if ($this->columnExists('patient_appointments', 'appointment_time')) {
            $payload['appointment_time'] = $appointmentTime;
        }

        foreach (['consultation_type', 'reason', 'notes'] as $column) {
            if ($this->columnExists('patient_appointments', $column)) {
                $payload[$column] = $validated[$column] ?? null;
            }
        }

        if ($this->columnExists('patient_appointments', 'status')) {
            $payload['status'] = 'pending';
        }

        if ($this->columnExists('patient_appointments', 'updated_at')) {
            $payload['updated_at'] = now();
        }

        DB::table('patient_appointments')
            ->where('id', $appointment)
            ->where('user_id', $user->id)
            ->update($payload);

        $doctorUserId = $this->doctorUserIdFromProfile($doctorProfileId);

        if ($doctorUserId) {
            $this->createAppNotification(
                recipientUserId: $doctorUserId,
                actorUserId: $user->id,
                type: 'appointment_request_updated',
                title: 'تم تعديل طلب الموعد',
                body: 'عدّل المريض طلب الموعد قبل التأكيد. راجع التاريخ والوقت المحدّثين.',
                url: url('/doctor/appointments'),
                appointmentId: $appointment,
                recipientRole: 'doctor',
                data: [
                    'appointment_date' => $appointmentDate,
                    'appointment_time' => $appointmentTime,
                    'patient_user_id' => $user->id,
                    'doctor_profile_id' => $doctorProfileId,
                ]
            );
        }

        return redirect()->route('patient.followup')->with('success', 'تم تعديل طلب الموعد بنجاح. سيراجع الطبيب الموعد المحدّث.');
    }


    public function acceptSuggestedAppointment(Request $request, int $appointment): RedirectResponse
    {
        $user = auth()->user();

        if (!$user || !$this->tableExists('patient_appointments')) {
            return back();
        }

        $row = DB::table('patient_appointments')
            ->where('id', $appointment)
            ->where('user_id', $user->id)
            ->first();

        if (!$row) {
            return back()->with('error', 'الموعد غير موجود.');
        }

        if (($row->status ?? null) !== 'reschedule_requested') {
            return back()->with('error', 'لا يوجد موعد مقترح لقبوله.');
        }

        $payload = [];

        if ($this->columnExists('patient_appointments', 'appointment_date')) {
            $payload['appointment_date'] = $row->suggested_date ?? $row->appointment_date;
        }

        if ($this->columnExists('patient_appointments', 'appointment_time')) {
            $payload['appointment_time'] = $row->suggested_time ?? $row->appointment_time;
        }

        if ($this->columnExists('patient_appointments', 'status')) {
            $payload['status'] = 'confirmed';
        }

        if ($this->columnExists('patient_appointments', 'patient_response_status')) {
            $payload['patient_response_status'] = 'accepted';
        }

        if ($this->columnExists('patient_appointments', 'patient_response_message')) {
            $payload['patient_response_message'] = $request->input('patient_response_message');
        }

        if ($this->columnExists('patient_appointments', 'confirmed_at')) {
            $payload['confirmed_at'] = now();
        }

        if ($this->columnExists('patient_appointments', 'updated_at')) {
            $payload['updated_at'] = now();
        }

        DB::table('patient_appointments')
            ->where('id', $appointment)
            ->where('user_id', $user->id)
            ->update($payload);

        $this->createAppNotification(
            recipientUserId: $user->id,
            actorUserId: null,
            type: 'appointment_reschedule_accepted',
            title: 'تم قبول الموعد المقترح',
            body: 'تم تثبيت الموعد المقترح بنجاح.',
            url: route('patient.followup'),
            appointmentId: $appointment
        );

        return redirect()->route('patient.followup')->with('success', 'تم قبول الموعد المقترح وتأكيده.');
    }


    public function declineSuggestedAppointment(Request $request, int $appointment): RedirectResponse
    {
        $user = auth()->user();

        if (!$user || !$this->tableExists('patient_appointments')) {
            return back();
        }

        $row = DB::table('patient_appointments')
            ->where('id', $appointment)
            ->where('user_id', $user->id)
            ->first();

        if (!$row) {
            return back()->with('error', 'الموعد غير موجود.');
        }

        $payload = [];

        if ($this->columnExists('patient_appointments', 'status')) {
            $payload['status'] = 'patient_declined_reschedule';
        }

        if ($this->columnExists('patient_appointments', 'patient_response_status')) {
            $payload['patient_response_status'] = 'declined';
        }

        if ($this->columnExists('patient_appointments', 'patient_response_message')) {
            $payload['patient_response_message'] = $request->input('patient_response_message');
        }

        if ($this->columnExists('patient_appointments', 'updated_at')) {
            $payload['updated_at'] = now();
        }

        DB::table('patient_appointments')
            ->where('id', $appointment)
            ->where('user_id', $user->id)
            ->update($payload);

        $this->createAppNotification(
            recipientUserId: $user->id,
            actorUserId: null,
            type: 'appointment_reschedule_declined',
            title: 'تم رفض الموعد المقترح',
            body: 'تم تسجيل رفضك للموعد المقترح. يمكنك طلب موعد آخر.',
            url: route('patient.followup'),
            appointmentId: $appointment
        );

        return redirect()->route('patient.followup')->with('success', 'تم رفض الموعد المقترح.');
    }

}
