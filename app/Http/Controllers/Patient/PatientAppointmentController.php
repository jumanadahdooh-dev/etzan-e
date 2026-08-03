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

    /**
     * عرض صفحة حجز الموعد
     */
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

    /**
     * جلب الأوقات المتاحة للطبيب في تاريخ معين
     */
    private function appointmentSlots($doctorProfileId, $date, $ignoreAppointmentId = null)
    {
        // ✅ إذا لم يكن هناك طبيب، أرجع أوقات وهمية
        if (!$doctorProfileId) {
            return $this->getFallbackSlots();
        }

        // التحقق من وجود جدول الطبيب
        if (!$this->tableExists('doctor_schedules')) {
            return $this->getFallbackSlots();
        }

        $dayOfWeek = Carbon::parse($date)->dayOfWeek;

        // جلب جدول الطبيب لهذا اليوم
        $schedule = DB::table('doctor_schedules')
            ->where('doctor_profile_id', $doctorProfileId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', 1)
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

        // حجز المواعيد المحجوزة لهذا اليوم
        $bookedTimes = [];
        if ($this->tableExists('patient_appointments')) {
            $query = DB::table('patient_appointments')
                ->where('doctor_profile_id', $doctorProfileId)
                ->where('appointment_date', $date)
                ->whereIn('status', ['pending', 'confirmed', 'approved']);

            if ($ignoreAppointmentId) {
                $query->where('id', '!=', $ignoreAppointmentId);
            }

            $bookedTimes = $query->pluck('appointment_time')->toArray();
        }

        // إزالة الأوقات المحجوزة
        $availableTimes = array_diff($times, $bookedTimes);
        $availableTimes = array_values($availableTimes); // إعادة ترتيب المصفوفة

        if (empty($availableTimes)) {
            return [];
        }

        // تقسيم الأوقات إلى فترات
        $morning = [];
        $afternoon = [];
        $evening = [];

        foreach ($availableTimes as $time) {
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
     * أوقات افتراضية للاختبار (إذا لم يكن هناك جدول للطبيب)
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
     * جلب بيانات الشهر للمواعيد
     */
    private function appointmentMonthMeta($doctorProfileId, $date, $ignoreAppointmentId = null)
    {
        if (!$doctorProfileId) {
            return [
                'availableAppointmentDates' => [],
                'fullyBookedAppointmentDates' => [],
            ];
        }

        $schedules = DB::table('doctor_schedules')
            ->where('doctor_profile_id', $doctorProfileId)
            ->where('is_active', 1)
            ->get();

        $workingDays = $schedules->pluck('day_of_week')->toArray();

        $startDate = Carbon::parse($date)->startOfMonth();
        $endDate = Carbon::parse($date)->endOfMonth();

        $availableDates = [];
        $fullyBookedDates = [];

        for ($day = $startDate->copy(); $day->lte($endDate); $day->addDay()) {
            $dayOfWeek = $day->dayOfWeek;

            // إذا كان اليوم ضمن أيام العمل
            if (in_array($dayOfWeek, $workingDays)) {
                // التحقق من وجود مواعيد محجوزة في هذا اليوم
                $bookedCount = DB::table('patient_appointments')
                    ->where('doctor_profile_id', $doctorProfileId)
                    ->where('appointment_date', $day->toDateString())
                    ->whereIn('status', ['pending', 'confirmed', 'approved'])
                    ->count();

                // إذا كان اليوم مكتمل (جميع المواعيد محجوزة)
                if ($bookedCount >= 8) { // 8 مواعيد كحد أقصى في اليوم
                    $fullyBookedDates[] = $day->toDateString();
                } else {
                    $availableDates[] = $day->toDateString();
                }
            }
        }

        return [
            'availableAppointmentDates' => $availableDates,
            'fullyBookedAppointmentDates' => $fullyBookedDates,
        ];
    }

    /**
     * جلب مواعيد المريض في الشهر
     */
    private function calendarAppointmentsForPatient($userId, $patientProfileId, $date)
    {
        if (!$userId || !$patientProfileId) {
            return [];
        }

        return DB::table('patient_appointments')
            ->where('user_id', $userId)
            ->where('patient_profile_id', $patientProfileId)
            ->whereMonth('appointment_date', Carbon::parse($date)->month)
            ->whereYear('appointment_date', Carbon::parse($date)->year)
            ->get()
            ->toArray();
    }

    /**
     * التحقق من وجود جدول في قاعدة البيانات
     */
    private function tableExists($table)
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * التحقق من وجود عمود في الجدول
     */
    private function columnExists($table, $column)
    {
        try {
            return Schema::hasColumn($table, $column);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * توحيد صيغة الوقت
     */
    private function normalizeAppointmentTime($time)
    {
        if (empty($time)) {
            return null;
        }

        try {
            return Carbon::parse($time)->format('H:i');
        } catch (\Throwable $e) {
            return $time;
        }
    }

    /**
     * التحقق من توفر الوقت
     */
    private function isAppointmentSlotAvailable($doctorProfileId, $date, $time, $ignoreAppointmentId = null)
    {
        if (!$doctorProfileId || !$date || !$time) {
            return false;
        }

        $query = DB::table('patient_appointments')
            ->where('doctor_profile_id', $doctorProfileId)
            ->where('appointment_date', $date)
            ->where('appointment_time', $time)
            ->whereIn('status', ['pending', 'confirmed', 'approved']);

        if ($ignoreAppointmentId) {
            $query->where('id', '!=', $ignoreAppointmentId);
        }

        return $query->count() === 0;
    }

    /**
     * جلب معرف المستخدم للطبيب من ملفه
     */
    private function doctorUserIdFromProfile($doctorProfileId)
    {
        if (!$doctorProfileId) {
            return null;
        }

        $profile = DB::table('doctor_profiles')
            ->where('id', $doctorProfileId)
            ->first();

        return $profile->user_id ?? null;
    }

    /**
     * إنشاء إشعار
     */
    private function createAppNotification(
        $recipientUserId,
        $actorUserId,
        $type,
        $title,
        $body,
        $url,
        $appointmentId = null,
        $recipientRole = 'patient',
        $data = []
    ) {
        try {
            $notificationData = [
                'user_id' => $recipientUserId,
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'url' => $url,
                'appointment_id' => $appointmentId,
                'data' => json_encode($data),
                'role' => $recipientRole,
            ];

            DB::table('notifications')->insert($notificationData);
        } catch (\Throwable $e) {
            // تجاهل خطأ الإشعار
        }
    }

    // ============================================
    // دوال حجز وتعديل المواعيد
    // ============================================

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
