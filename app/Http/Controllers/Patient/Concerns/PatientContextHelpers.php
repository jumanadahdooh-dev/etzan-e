<?php

namespace App\Http\Controllers\Patient\Concerns;

use App\Http\Controllers\Concerns\ConversationHelpers;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\PatientDailyCalorieGoal;
use App\Models\PatientMeal;
use App\Services\AppNotificationService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * كل الدوال المساعدة المشتركة يلي كانت جوا PatientHomeController (4,698 سطر)
 * قبل ما ينقسم لعدة controllers حسب الميزة. نُقلت هون حرفياً بدون أي تغيير
 * على منطقها الداخلي حتى يضل سلوك التطبيق مطابق 100% لما كان عليه.
 *
 * دوال فحص جداول/أعمدة الرسائل (tableExists/columnExists/firstExistingColumn/
 * conversationStatusValue) انتقلت لـ ConversationHelpers المشترك مع جهة الدكتور.
 */
trait PatientContextHelpers
{
    use ConversationHelpers;

        private function dailyCalorieGoalForDate(?int $userId, ?object $profile, string $selectedDate): array
    {
        $empty = [
            'target' => null,
            'status' => 'missing',
            'protein_target' => 90,
            'carbs_target' => 220,
            'fat_target' => 65,
            'note' => null,
        ];

        if (! $userId || ! $this->tableExists('patient_daily_calorie_goals')) {
            return $empty;
        }

        $goal = PatientDailyCalorieGoal::query()
            ->where('user_id', $userId)
            ->whereDate('goal_date', $selectedDate)
            ->whereIn('status', ['approved', 'suggested'])
            ->latest('id')
            ->first();

        if (! $goal) {
            return $empty;
        }

        return [
            'target' => $goal->calories_goal && $goal->calories_goal > 0
                ? (int) $goal->calories_goal
                : null,

            'status' => $goal->status ?: 'approved',

            'protein_target' => $goal->protein_goal && $goal->protein_goal > 0
                ? (int) $goal->protein_goal
                : 90,

            'carbs_target' => $goal->carbs_goal && $goal->carbs_goal > 0
                ? (int) $goal->carbs_goal
                : 220,

            'fat_target' => $goal->fat_goal && $goal->fat_goal > 0
                ? (int) $goal->fat_goal
                : 65,

            'note' => $goal->doctor_note,
        ];
    }


    private function ensureSupportConversation(int $userId): ?int
    {
        if (!$this->tableExists('conversations')) {
            return null;
        }

        if (!$this->columnExists('conversations', 'user_id')) {
            return null;
        }

        $query = DB::table('conversations')
            ->where('user_id', $userId);

        if ($this->columnExists('conversations', 'type')) {
            $query->where('type', 'support');
        } elseif ($this->columnExists('conversations', 'subject')) {
            $query->where('subject', 'دعم فني');
        }

        $existingId = $query->orderByDesc('id')->value('id');

        if ($existingId) {
            return (int) $existingId;
        }

        $payload = [
            'user_id' => $userId,
        ];

        if ($this->columnExists('conversations', 'type')) {
            $payload['type'] = 'support';
        }

        if ($this->columnExists('conversations', 'subject')) {
            $payload['subject'] = 'دعم فني';
        }

        if ($this->columnExists('conversations', 'status')) {
            $statusValue = $this->conversationStatusValue();

            if ($statusValue !== null) {
                $payload['status'] = $statusValue;
            }
        }

        if ($this->columnExists('conversations', 'last_message_at')) {
            $payload['last_message_at'] = now();
        }

        if ($this->columnExists('conversations', 'unread_by_admin')) {
            $payload['unread_by_admin'] = 0;
        }

        if ($this->columnExists('conversations', 'created_at')) {
            $payload['created_at'] = now();
        }

        if ($this->columnExists('conversations', 'updated_at')) {
            $payload['updated_at'] = now();
        }

        return (int) DB::table('conversations')->insertGetId($payload);
    }


    private function dashboardData(array $extra = []): array
    {
        Carbon::setLocale('ar');

        $user = auth()->user();
        $patientProfile = $this->patientProfile($user?->id);
        $profileCompletion = $this->profileCompletion($patientProfile);
        $hasCompletedProfile = $profileCompletion >= 100;

        $doctor = $this->selectedDoctor($patientProfile);
        $hasSelectedDoctor = $doctor['is_selected'] ?? false;
        $doctorRequestStatus = $doctor['request_status'] ?? null;
        $isDoctorApproved = $hasSelectedDoctor && $doctorRequestStatus === 'approved';

        $nextAppointment = $this->nextAppointment($user?->id, $patientProfile?->id ?? null);
        $appointmentStatus = $nextAppointment['status'] ?? null;
        $isAppointmentPending = $appointmentStatus === 'pending';
        $isAppointmentConfirmed = in_array($appointmentStatus, ['confirmed', 'approved'], true);
        $isAppointmentRescheduleRequested = $appointmentStatus === 'reschedule_requested';

        $hasStartedFollowup = $hasCompletedProfile && $isDoctorApproved && !empty($nextAppointment);
        $hasDailyPlan = $hasStartedFollowup;

        $profileForm = $this->profileFormData($patientProfile);
        $recommendedDoctors = $this->recommendedDoctors($patientProfile);
        $todayTasks = $this->realTodayTasks($user);
        $journeyTasks = $this->journeyTasks($todayTasks);
        $tasksTotal = max(count($journeyTasks), 1);
        $tasksCompleted = collect($journeyTasks)->where('completed', true)->count();
        $todayProgress = (int) round(($tasksCompleted / $tasksTotal) * 100);
        $messages = $this->latestMessages($user?->id, $doctor['user_id'] ?? null);
        $supportMessages = $this->supportMessages($user?->id);
        $articles = $this->realRecommendedArticles();
        $dailyWisdom = $this->dailyPatientContentCard(['health_wisdom'], $patientProfile, $doctor['id'] ?? null, $doctor['user_id'] ?? null, 'حكمة اليوم', 'كل عادة صحية صغيرة هي تصويت لصالح النسخة الأقوى منك.');
        $dailyMotivation = $this->dailyPatientContentCard(['motivational_quote'], $patientProfile, $doctor['id'] ?? null, $doctor['user_id'] ?? null, 'رسالة اليوم', 'لا تحتاجين يومًا مثاليًا؛ فقط خطوة صحية واحدة الآن تكفي لتغيير اتجاه اليوم.');
        $patientNotifications = $this->patientNotifications($user?->id);
        $unreadAppNotifications = $this->unreadAppNotificationsCount($user?->id);
        $patientName = $this->patientDisplayName($user, $patientProfile);

        $base = [
            'pageTitle' => 'لوحة المريض',
            'activePage' => 'home',
            'todayDate' => $this->todayDate(),
            'patient' => [
                'name' => $patientName,
                'email' => $user?->email ?: '',
                'initial' => mb_substr($patientName, 0, 1, 'UTF-8'),
                'avatar' => $this->patientAvatar($user, $patientProfile),
                'welcome' => 'مرحباً بك مجددًا!',
                'profile_completion' => $profileCompletion,
                'has_completed_profile' => $hasCompletedProfile,
                'has_selected_doctor' => $hasSelectedDoctor,
                'has_daily_plan' => $hasDailyPlan,
                'is_doctor_approved' => $isDoctorApproved,
                'doctor_request_status' => $doctorRequestStatus,
                'has_started_followup' => $hasStartedFollowup,
                'appointment_status' => $appointmentStatus,
                'is_appointment_pending' => $isAppointmentPending,
                'is_appointment_confirmed' => $isAppointmentConfirmed,
                'is_appointment_reschedule_requested' => $isAppointmentRescheduleRequested,
                'profile' => $patientProfile ? (array) $patientProfile : [],
            ],
            'profileForm' => $profileForm,
            'profileViewMode' => !$hasCompletedProfile ? 'complete_profile' : (!$hasSelectedDoctor ? 'choose_doctor' : 'profile_summary'),
            'profileStatus' => $this->profileStatus($profileCompletion, $hasSelectedDoctor, $isDoctorApproved, $hasStartedFollowup, $appointmentStatus),
            'doctor' => $doctor,
            'nextAppointment' => $nextAppointment,
            'journeyHero' => $this->journeyHero($profileCompletion, $isDoctorApproved, $hasStartedFollowup, $doctor, $nextAppointment),
            'recommendedDoctors' => $recommendedDoctors,
            'recommendationMeta' => $this->recommendationMeta($patientProfile, $recommendedDoctors),
            'unreadMessages' => $this->unreadMessagesCount($user?->id),
            'notificationsCount' => $unreadAppNotifications,
            'patientNotifications' => $patientNotifications,
            'homeStats' => $this->homeStats($patientProfile, $todayProgress),
            'homeProgress' => $this->realTaskProgress($user),
            'homeTasks' => $todayTasks,
            'journeyTasks' => $journeyTasks,
            'messages' => $messages,
            'supportMessages' => $supportMessages,
            'aiCalories' => $this->realCaloriesData($user, $patientProfile),
            'nutrition' => [
                'calories' => '0',
                'title' => 'ملخص التغذية',
                'carbs' => 0,
                'protein' => 0,
                'fats' => 0,
            ],
            'articles' => $articles,
            'dailyWisdom' => $dailyWisdom,
            'dailyMotivation' => $dailyMotivation,
            'chart' => $this->weeklyHealthChart($user, $patientProfile),
        ];

        return array_replace_recursive($base, $extra);
    }


    private function journeyHero(int $profileCompletion, bool $isDoctorApproved, bool $hasStartedFollowup, array $doctor, ?array $nextAppointment = null): array
    {
        if ($profileCompletion < 100) {
            return [
                'title' => 'أكمل ملفك الصحي أولًا',
                'subtitle' => 'بعد إكمال بياناتك الصحية سنقدر نرتب لك الطبيب المناسب والخطة اليومية.',
                'actions' => [
                    ['label' => 'إكمال الملف', 'icon' => 'clipboard-check'],
                    ['label' => 'خصوصية آمنة', 'icon' => 'shield-check'],
                ],
            ];
        }

        if (!$isDoctorApproved) {
            return [
                'title' => 'بانتظار موافقة الطبيب',
                'subtitle' => 'تم اختيار الطبيب، وسيتم تفعيل الحجز والخطة اليومية بعد موافقته على طلب المتابعة.',
                'actions' => [
                    ['label' => 'متابعة الطلب', 'icon' => 'clock-3'],
                    ['label' => 'عرض الأطباء', 'icon' => 'stethoscope'],
                ],
            ];
        }

        if (!$hasStartedFollowup) {
            return [
                'title' => 'احجز أول موعد لبدء المتابعة',
                'subtitle' => 'تم اعتماد طبيبك. احجز موعدك الأول حتى تبدأ رحلة المتابعة داخل اتزان.',
                'actions' => [
                    ['label' => 'حجز موعد', 'icon' => 'calendar-days'],
                    ['label' => 'ملفي الصحي', 'icon' => 'id-card'],
                ],
            ];
        }

        if (($nextAppointment['status'] ?? null) === 'pending') {
            return [
                'title' => 'موعدك بانتظار التأكيد',
                'subtitle' => 'تم إرسال طلب الموعد للطبيب. سيراجع الطبيب الطلب، وسيظهر تحديث الموعد هنا بعد مراجعته.',
                'actions' => [
                    ['label' => 'عرض الموعد', 'icon' => 'calendar-clock'],
                    ['label' => 'تعديل الطلب', 'icon' => 'pencil'],
                ],
            ];
        }

        if (($nextAppointment['status'] ?? null) === 'reschedule_requested') {
            return [
                'title' => 'الطبيب اقترح موعدًا جديدًا',
                'subtitle' => 'راجع الموعد المقترح، ثم اختر قبول الموعد الجديد أو رفض الاقتراح.',
                'actions' => [
                    ['label' => 'مراجعة الاقتراح', 'icon' => 'calendar-clock'],
                    ['label' => 'الإشعارات', 'icon' => 'bell-ring'],
                ],
            ];
        }

        if (in_array(($nextAppointment['status'] ?? null), ['confirmed', 'approved'], true)) {
            return [
                'title' => !empty($nextAppointment['meeting_url']) ? 'موعدك مؤكد واللقاء جاهز' : 'موعدك مؤكد والمتابعة جاهزة',
                'subtitle' => !empty($nextAppointment['meeting_url']) ? 'رابط اللقاء أصبح جاهزًا. ادخل إلى الاستشارة في وقت الموعد وتابع مهامك اليومية.' : 'استعد لموعدك القادم، وسيظهر رابط اللقاء بعد إضافته من الطبيب.',
                'actions' => [
                    ['label' => !empty($nextAppointment['meeting_url']) ? 'الدخول إلى اللقاء' : 'عرض الموعد', 'icon' => 'video'],
                    ['label' => 'رسالة للطبيب', 'icon' => 'message-circle'],
                    ['label' => 'تسجيل وجبة', 'icon' => 'utensils'],
                ],
            ];
        }

        return [
            'title' => 'رحلتك الصحية اليوم',
            'subtitle' => 'تابع مهامك اليومية، سجل الماء والوجبات، وابدأ المتابعة مع طبيبك.',
            'actions' => [
                ['label' => 'تسجيل الماء', 'icon' => 'droplet'],
                ['label' => 'تسجيل وجبة', 'icon' => 'utensils'],
                ['label' => 'حجز موعد', 'icon' => 'calendar-days'],
                ['label' => 'رسالة للطبيب', 'icon' => 'message-circle'],
            ],
        ];
    }


    private function profileStatus(int $completion, bool $hasDoctor, bool $isDoctorApproved, bool $hasStartedFollowup, ?string $appointmentStatus = null): array
    {
        if ($completion < 100) {
            return [
                'variant' => 'incomplete',
                'eyebrow' => 'ملفك يحتاج تحديث',
                'title' => 'أكمل ملفك الصحي حتى نخصص تجربتك',
                'body' => 'كلما كانت بياناتك الصحية أدق، أصبحت التوصيات واختيار الطبيب والخطة اليومية أنسب لك.',
                'cta' => 'إكمال الملف',
                'url' => route('patient.profile', ['edit' => 1]),
                'completion' => $completion,
                'icon' => 'clipboard-check',
            ];
        }

        if (!$hasDoctor) {
            return [
                'variant' => 'doctor',
                'eyebrow' => 'ملف صحي مكتمل',
                'title' => 'بقي اختيار الطبيب المناسب لحالتك',
                'body' => 'تم حفظ ملفك الصحي. يمكنك الآن عرض الأطباء المناسبين حسب حالتك وهدفك وتفضيلاتك.',
                'cta' => 'اختيار الطبيب',
                'url' => route('patient.doctors.recommended'),
                'completion' => 100,
                'icon' => 'stethoscope',
            ];
        }

        if (!$isDoctorApproved) {
            return [
                'variant' => 'waiting',
                'eyebrow' => 'بانتظار موافقة الطبيب',
                'title' => 'تم إرسال طلب المتابعة للطبيب',
                'body' => 'بعد موافقة الطبيب سيتم فتح الحجز والاستشارة وباقي خطوات المتابعة.',
                'cta' => 'عرض الملف الصحي',
                'url' => route('patient.profile'),
                'completion' => 100,
                'icon' => 'clock-3',
            ];
        }

        if (!$hasStartedFollowup) {
            return [
                'variant' => 'appointment',
                'eyebrow' => 'الطبيب وافق',
                'title' => 'احجز أول موعد لبدء المتابعة',
                'body' => 'تم اعتماد طبيبك. احجز موعدك الأول حتى تبدأ رحلة المتابعة وتظهر لوحة المريض الكاملة.',
                'cta' => 'حجز موعد',
                'url' => route('patient.followup'),
                'completion' => 100,
                'icon' => 'calendar-days',
            ];
        }

        if ($appointmentStatus === 'pending') {
            return [
                'variant' => 'appointment_pending',
                'eyebrow' => 'طلب موعد مرسل',
                'title' => 'موعدك بانتظار التأكيد',
                'body' => 'تم إرسال طلب الموعد للطبيب. سيراجع الطبيب الطلب، وسيظهر تحديث الموعد هنا بعد مراجعته.',
                'cta' => 'عرض الموعد',
                'url' => route('patient.followup'),
                'completion' => 100,
                'icon' => 'calendar-clock',
            ];
        }

        if ($appointmentStatus === 'reschedule_requested') {
            return [
                'variant' => 'reschedule_requested',
                'eyebrow' => 'اقتراح موعد جديد',
                'title' => 'الطبيب اقترح وقتًا آخر',
                'body' => 'راجع الموعد المقترح، ثم اختر قبول الموعد الجديد أو رفض الاقتراح.',
                'cta' => 'مراجعة الاقتراح',
                'url' => route('patient.followup'),
                'completion' => 100,
                'icon' => 'calendar-clock',
            ];
        }

        return [
            'variant' => 'ready',
            'eyebrow' => 'جاهز لليوم',
            'title' => 'رحلتك الصحية اليوم مرتبة وواضحة',
            'body' => 'تابع مهامك، حلل وجباتك، وراجع تقدمك خطوة بخطوة.',
            'cta' => 'متابعة المهام',
            'url' => route('patient.journey'),
            'completion' => 100,
            'icon' => 'sparkles',
        ];
    }


    private function patientProfile(?int $userId): ?object
    {
        if (!$userId || !$this->tableExists('patient_profiles') || !$this->columnExists('patient_profiles', 'user_id')) {
            return null;
        }

        return DB::table('patient_profiles')->where('user_id', $userId)->first();
    }


    private function profileCompletion(?object $profile): int
    {
        if (!$profile) {
            return 0;
        }

        /*
        |--------------------------------------------------------------------------
        | أولًا: اقرأ نسبة الاكتمال المحفوظة في قاعدة البيانات
        |--------------------------------------------------------------------------
        | هذا أهم تعديل.
        | لأن completeProfile يحفظ 100 في profile_completion أو completion_percentage،
        | فلازم نثق بهذه القيمة قبل ما نحسب يدويًا.
        */
        $storedCompletion = $this->valueFrom($profile, [
            'profile_completion',
            'completion',
            'completion_percentage',
            'profile_completion_percentage',
        ]);

        if ($storedCompletion !== null && $storedCompletion !== '' && is_numeric($storedCompletion)) {
            $storedCompletion = (int) round((float) $storedCompletion);

            if ($storedCompletion >= 100) {
                return 100;
            }

            if ($storedCompletion > 0) {
                return max(0, min(100, $storedCompletion));
            }
        }

        /*
        |--------------------------------------------------------------------------
        | ثانيًا: اقرأ أعمدة اكتمال الملف لو موجودة
        |--------------------------------------------------------------------------
        */
        $explicitCompleted = $this->valueFrom($profile, [
            'profile_completed',
            'has_completed_profile',
            'is_profile_complete',
            'completed',
            'is_completed',
        ]);

        if (
            $explicitCompleted === true ||
            $explicitCompleted === 1 ||
            $explicitCompleted === '1' ||
            $explicitCompleted === 'true' ||
            $explicitCompleted === 'yes'
        ) {
            return 100;
        }

        /*
        |--------------------------------------------------------------------------
        | ثالثًا: حساب احتياطي فقط لو ما في نسبة محفوظة
        |--------------------------------------------------------------------------
        */
        $checks = [
            'height' => $this->validNumber($this->valueFrom($profile, ['height', 'height_cm']), 80, 240),
            'weight' => $this->validNumber($this->valueFrom($profile, ['weight', 'weight_kg', 'current_weight']), 25, 350),
            'birth_date' => $this->filledValue($this->valueFrom($profile, ['birth_date', 'date_of_birth'])),
            'gender' => in_array((string) $this->valueFrom($profile, ['gender']), ['female', 'male'], true),
            'health_goal' => $this->filledValue($this->valueFrom($profile, ['health_goal', 'goal', 'main_goal', 'target_goal', 'goal_type', 'main_health_goal', 'health_objective'])),
            'activity_level' => $this->filledValue($this->valueFrom($profile, ['activity_level'])),
            'medical_conditions' => count($this->profileConditions($profile)) > 0,
        ];

        return (int) round((collect($checks)->filter()->count() / count($checks)) * 100);
    }


    private function profileFormData(?object $profile): array
    {
        if (!$profile) {
            return [];
        }

        return [
            'height_cm' => $this->valueFrom($profile, ['height', 'height_cm']),
            'weight_kg' => $this->valueFrom($profile, ['weight', 'weight_kg', 'current_weight']),
            'birth_date' => $this->valueFrom($profile, ['birth_date', 'date_of_birth']),
            'gender' => $this->valueFrom($profile, ['gender']),
            'phone' => $this->valueFrom($profile, ['phone']),
            'city' => $this->valueFrom($profile, ['city']),
            'health_goal' => $this->valueFrom($profile, ['health_goal', 'goal', 'main_goal', 'target_goal', 'goal_type', 'main_health_goal', 'health_objective']),
            'activity_level' => $this->valueFrom($profile, ['activity_level']),
            'medical_conditions' => $this->profileConditions($profile),
            'medications' => $this->valueFrom($profile, ['medications']),
            'allergies' => $this->valueFrom($profile, ['allergies']),
            'meals_per_day' => $this->valueFrom($profile, ['meals_per_day']),
            'sleep_hours' => $this->valueFrom($profile, ['sleep_hours']),
            'water_cups' => $this->valueFrom($profile, ['water_cups']),
            'preferred_doctor_gender' => $this->valueFrom($profile, ['preferred_doctor_gender'], 'any'),
            'preferred_consultation_type' => $this->valueFrom($profile, ['preferred_consultation_type'], 'any'),
            'notes' => $this->valueFrom($profile, ['notes', 'patient_notes']),
        ];
    }


    private function profileConditions(?object $profile): array
    {
        if (!$profile) {
            return [];
        }

        return $this->decodeArrayValue($this->valueFrom($profile, ['medical_conditions', 'health_condition', 'chronic_diseases', 'diseases']));
    }


    private function selectedDoctor(?object $profile): array
    {
        $doctorId = $this->valueFrom($profile, ['doctor_profile_id', 'doctor_id', 'selected_doctor_id']);

        if (!$doctorId || !$this->tableExists('doctor_profiles') || !$this->tableExists('users')) {
            return [
                'is_selected' => false,
                'name' => 'لم يتم اختيار طبيب بعد',
                'specialty' => 'اختر الطبيب المناسب لحالتك',
                'avatar' => $this->placeholderImage('طبيب'),
                'date' => 'لا يوجد موعد قادم',
                'time' => '—',
                'request_status' => null,
            ];
        }

        $select = ['doctor_profiles.id', 'doctor_profiles.user_id', 'users.name as user_name'];

        foreach (['avatar', 'profile_photo_path', 'image', 'photo'] as $column) {
            if ($this->columnExists('users', $column)) {
                $select[] = 'users.' . $column . ' as user_' . $column;
            }
        }

        foreach (['photo_path', 'avatar', 'image'] as $column) {
            if ($this->columnExists('doctor_profiles', $column)) {
                $select[] = 'doctor_profiles.' . $column . ' as doctor_' . $column;
            }
        }

        $doctor = DB::table('doctor_profiles')
            ->join('users', 'users.id', '=', 'doctor_profiles.user_id')
            ->where('doctor_profiles.id', $doctorId)
            ->select($select)
            ->first();

        if (!$doctor) {
            return [
                'is_selected' => false,
                'name' => 'لم يتم اختيار طبيب بعد',
                'specialty' => 'اختر الطبيب المناسب لحالتك',
                'avatar' => $this->placeholderImage('طبيب'),
                'date' => 'لا يوجد موعد قادم',
                'time' => '—',
                'request_status' => null,
            ];
        }

        $photo = $this->valueFrom($doctor, ['doctor_photo_path', 'doctor_avatar', 'doctor_image', 'user_avatar', 'user_profile_photo_path', 'user_image', 'user_photo']);

        return [
            'is_selected' => true,
            'id' => (int) $doctor->id,
            'user_id' => (int) $doctor->user_id,
            'name' => 'د. ' . ($doctor->user_name ?: 'طبيب اتزان'),
            'specialty' => $this->doctorSpecialty((int) $doctor->id),
            'avatar' => $this->imageUrl($photo) ?: $this->placeholderImage($doctor->user_name ?: 'طبيب'),
            'date' => 'لم يتم تحديد موعد بعد',
            'time' => '—',
            'request_status' => $this->valueFrom($profile, ['doctor_request_status'], 'pending'),
        ];
    }


    private function recommendedDoctors(?object $profile): array
    {
        if (!$profile || !$this->tableExists('doctor_profiles') || !$this->tableExists('users')) {
            return [];
        }

        $select = ['doctor_profiles.id', 'doctor_profiles.user_id', 'users.name as user_name'];

        foreach (['avatar', 'profile_photo_path', 'image', 'photo', 'gender'] as $column) {
            if ($this->columnExists('users', $column)) {
                $select[] = 'users.' . $column . ' as user_' . $column;
            }
        }

        foreach (['bio', 'about', 'description', 'specialty', 'specialization', 'years_experience', 'experience_years', 'photo_path', 'avatar', 'image', 'consultation_type', 'gender', 'doctor_gender'] as $column) {
            if ($this->columnExists('doctor_profiles', $column)) {
                $select[] = 'doctor_profiles.' . $column . ' as doctor_' . $column;
            }
        }

        $rows = DB::table('doctor_profiles')->join('users', 'users.id', '=', 'doctor_profiles.user_id')->select($select)->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $preferredGender = (string) $this->valueFrom($profile, ['preferred_doctor_gender'], 'any');
        $preferredType = (string) $this->valueFrom($profile, ['preferred_consultation_type'], 'any');
        $preferredGender = in_array($preferredGender, ['female', 'male'], true) ? $preferredGender : 'any';
        $preferredType = in_array($preferredType, ['online', 'clinic'], true) ? $preferredType : 'any';

        $doctors = $rows->map(function ($doctor) use ($profile, $preferredGender, $preferredType) {
            $specialty = $this->doctorSpecialty((int) $doctor->id);
            $gender = $this->normalizeGender($this->valueFrom($doctor, ['doctor_doctor_gender', 'doctor_gender', 'user_gender'], 'unknown'));
            $consultationKey = $this->normalizeConsultationType($this->valueFrom($doctor, ['doctor_consultation_type'], 'online'));
            $photo = $this->valueFrom($doctor, ['doctor_photo_path', 'doctor_avatar', 'doctor_image', 'user_avatar', 'user_profile_photo_path', 'user_image', 'user_photo']);
            $bio = $this->valueFrom($doctor, ['doctor_bio', 'doctor_about', 'doctor_description'], 'طبيب مختص يساعدك على بناء متابعة صحية مناسبة لحالتك وهدفك.');
            $experience = (int) $this->valueFrom($doctor, ['doctor_years_experience', 'doctor_experience_years'], 5);
            $review = $this->doctorReviewSummary((int) $doctor->id);
            $match = $this->doctorMatch($profile, $specialty, $doctor, $gender, $consultationKey, $preferredGender, $preferredType, $experience);

            return [
                'id' => (int) $doctor->id,
                'is_fallback' => false,
                'name' => 'د. ' . ($doctor->user_name ?: 'طبيب اتزان'),
                'specialty' => $specialty,
                'avatar' => $this->imageUrl($photo) ?: $this->placeholderImage($doctor->user_name ?: 'طبيب'),
                'bio' => $bio,
                'gender' => $gender,
                'gender_label' => $gender === 'female' ? 'طبيبة' : ($gender === 'male' ? 'طبيب' : 'غير محدد'),
                'consultation_key' => $consultationKey,
                'consultation_type' => $consultationKey === 'clinic' ? 'حضوري' : 'أونلاين',
                'experience' => $experience,
                'rating' => $review['average'],
                'reviews_count' => $review['count'],
                'has_reviews' => $review['has_reviews'] ?? false,
                'match_score' => $match['score'],
                'match_reason' => $match['reason'],
                'badges' => $match['badges'],
                'articles' => $this->doctorArticles((int) $doctor->id, (int) $doctor->user_id),
            ];
        });

        if ($preferredGender !== 'any' && $doctors->where('gender', $preferredGender)->count() > 0) {
            $doctors = $doctors->where('gender', $preferredGender);
        }

        if ($preferredType !== 'any' && $doctors->where('consultation_key', $preferredType)->count() > 0) {
            $doctors = $doctors->where('consultation_key', $preferredType);
        }

        return $doctors->sortByDesc('match_score')->values()->take(24)->toArray();
    }


    private function doctorMatch(?object $profile, string $specialty, object $doctor, string $doctorGender, string $consultationType, string $preferredGender, string $preferredType, int $experience): array
    {
        $goal = (string) $this->valueFrom($profile, ['health_goal', 'goal', 'main_goal', 'target_goal', 'goal_type', 'main_health_goal', 'health_objective'], '');
        $conditions = array_values(array_filter($this->profileConditions($profile), fn ($item) => $item && $item !== 'none'));
        $bio = (string) $this->valueFrom($doctor, ['doctor_bio', 'doctor_about', 'doctor_description'], '');
        $doctorSpecialty = (string) $this->valueFrom($doctor, ['doctor_specialty', 'doctor_specialization'], '');
        $searchText = mb_strtolower($specialty . ' ' . $doctorSpecialty . ' ' . $bio, 'UTF-8');
        $score = 58;
        $reasons = [];
        $badges = [];

        if ($preferredGender !== 'any') {
            if ($doctorGender === $preferredGender) {
                $score += 12;
                $badges[] = $preferredGender === 'female' ? 'حسب تفضيلك: طبيبة' : 'حسب تفضيلك: طبيب';
            } else {
                $score -= 8;
            }
        }

        if ($preferredType !== 'any') {
            if ($consultationType === $preferredType) {
                $score += 8;
                $badges[] = $preferredType === 'clinic' ? 'حضوري' : 'أونلاين';
            } else {
                $score -= 4;
            }
        }

        if ($experience >= 10) {
            $score += 6;
            $badges[] = 'خبرة عالية';
        } elseif ($experience >= 5) {
            $score += 3;
        }

        $rules = [
            ['diabetes', 'diabetes_management', ['سكري', 'سكر', 'diabetes', 'تغذية علاجية'], 'مناسب لمتابعة السكر وتنظيم الوجبات اليومية.', 'سكري', 16, 9],
            ['hypertension', 'hypertension_management', ['ضغط', 'hypertension', 'قلب', 'مزمنة'], 'مناسب لمتابعة الضغط والعادات الغذائية المرتبطة به.', 'ضغط', 14, 8],
            ['cholesterol', 'cholesterol_management', ['كوليسترول', 'cholesterol', 'قلب'], 'مناسب لتحسين الكوليسترول ودعم صحة القلب.', 'كوليسترول', 13, 7],
            ['digestive', '', ['هضم', 'قولون', 'digestive', 'معدة'], 'مناسب لمتابعة المشاكل الهضمية والغذاء اليومي.', 'مشاكل هضمية', 12, 6],
        ];

        foreach ($rules as [$condition, $goalKey, $needles, $reason, $badge, $strong, $light]) {
            if (in_array($condition, $conditions, true) || ($goalKey && $goal === $goalKey)) {
                $score += $this->textHas($searchText, $needles) ? $strong : $light;
                $reasons[] = $reason;
                $badges[] = $badge;
            }
        }

        if ($goal === 'weight_loss') {
            $score += $this->textHas($searchText, ['سمنة', 'وزن', 'weight', 'تخسيس', 'نمط حياة']) ? 13 : 8;
            $reasons[] = 'مناسب لهدف خسارة الوزن بطريقة صحية ومتدرجة.';
            $badges[] = 'خسارة وزن';
        }

        if ($goal === 'weight_gain') {
            $score += $this->textHas($searchText, ['زيادة وزن', 'نحافة', 'weight', 'تغذية']) ? 13 : 8;
            $reasons[] = 'مناسب لهدف زيادة الوزن بطريقة صحية.';
            $badges[] = 'زيادة وزن';
        }

        if (empty($reasons)) {
            $reasons[] = 'مناسب للمتابعة الصحية العامة وبناء خطة يومية أوضح.';
            $badges[] = 'متابعة صحية';
        }

        return [
            'score' => max(50, min(98, $score)),
            'reason' => implode(' ', array_slice($reasons, 0, 2)),
            'badges' => array_values(array_unique(array_slice($badges, 0, 5))),
        ];
    }


    private function recommendationMeta(?object $profile, array $doctors): array
    {
        $preferredGender = (string) $this->valueFrom($profile, ['preferred_doctor_gender'], 'any');
        $preferredType = (string) $this->valueFrom($profile, ['preferred_consultation_type'], 'any');

        return [
            'preferred_gender_label' => match ($preferredGender) {
                'female' => 'طبيبة',
                'male' => 'طبيب',
                default => 'لا يهم',
            },
            'preferred_type_label' => match ($preferredType) {
                'online' => 'أونلاين',
                'clinic' => 'حضوري',
                default => 'لا يهم',
            },
            'total' => count($doctors),
        ];
    }


    private function doctorSpecialty(int $doctorProfileId): string
    {
        if ($this->tableExists('doctor_profiles')) {
            foreach (['specialty', 'specialization'] as $column) {
                if ($this->columnExists('doctor_profiles', $column)) {
                    $value = DB::table('doctor_profiles')->where('id', $doctorProfileId)->value($column);

                    if ($this->filledValue($value)) {
                        return (string) $value;
                    }
                }
            }
        }

        if ($this->tableExists('doctor_specialty') && $this->tableExists('specialties')) {
            $specialtyNameColumn = $this->firstExistingColumn('specialties', ['name', 'title', 'specialty_name']);

            if ($specialtyNameColumn && $this->columnExists('doctor_specialty', 'doctor_profile_id')) {
                $specialty = DB::table('doctor_specialty')
                    ->join('specialties', 'specialties.id', '=', 'doctor_specialty.specialty_id')
                    ->where('doctor_specialty.doctor_profile_id', $doctorProfileId)
                    ->value('specialties.' . $specialtyNameColumn);

                if ($specialty) {
                    return $specialty;
                }
            }
        }

        return 'استشاري صحي';
    }


    private function doctorReviewSummary(int $doctorProfileId): array
    {
        if (!$this->tableExists('doctor_reviews')) {
            return [
                'average' => null,
                'count' => 0,
                'has_reviews' => false,
            ];
        }

        $doctorColumn = $this->firstExistingColumn('doctor_reviews', [
            'doctor_profile_id',
            'doctor_id',
        ]);

        $ratingColumn = $this->firstExistingColumn('doctor_reviews', [
            'rating',
            'stars',
            'rate',
        ]);

        if (!$doctorColumn || !$ratingColumn) {
            return [
                'average' => null,
                'count' => 0,
                'has_reviews' => false,
            ];
        }

        $query = DB::table('doctor_reviews')
            ->where($doctorColumn, $doctorProfileId);

        $count = (int) $query->count();

        if ($count === 0) {
            return [
                'average' => null,
                'count' => 0,
                'has_reviews' => false,
            ];
        }

        $average = DB::table('doctor_reviews')
            ->where($doctorColumn, $doctorProfileId)
            ->avg($ratingColumn);

        return [
            'average' => number_format((float) $average, 1),
            'count' => $count,
            'has_reviews' => true,
        ];
    }


    private function patientPublishedArticlesQuery(): Builder
    {
        $query = Article::query()->with(['specialty', 'category', 'author']);

        if ($this->columnExists('articles', 'status')) {
            $query->where('status', 'published');
        }

        if ($this->columnExists('articles', 'published_at')) {
            $query->where(function (Builder $query) {
                $query->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
        }

        if ($this->columnExists('articles', 'published_at')) {
            $query->latest('published_at');
        }

        if ($this->columnExists('articles', 'created_at')) {
            $query->latest('created_at');
        }

        return $query;
    }


    private function patientVisibleArticlesQuery(): Builder
    {
        $query = $this->patientPublishedArticlesQuery();

        return $this->applyPatientArticleVisibilityScope($query);
    }


    private function applyPatientArticleVisibilityScope(Builder $query): Builder
    {
        $blockedTerms = $this->patientArticleBlockedTerms();

        $query->where(function (Builder $query) use ($blockedTerms) {
            foreach (['title', 'excerpt'] as $column) {
                if (! $this->columnExists('articles', $column)) {
                    continue;
                }

                foreach ($blockedTerms as $term) {
                    $query->where($column, 'not like', '%' . $term . '%');
                }
            }
        });

        if ($this->tableExists('article_categories')) {
            $query->whereDoesntHave('category', function (Builder $categoryQuery) use ($blockedTerms) {
                $categoryQuery->where(function (Builder $innerQuery) use ($blockedTerms) {
                    foreach ($blockedTerms as $term) {
                        $innerQuery->orWhere('name', 'like', '%' . $term . '%');
                    }
                });
            });
        }

        if ($this->tableExists('specialties')) {
            $query->whereDoesntHave('specialty', function (Builder $specialtyQuery) use ($blockedTerms) {
                $specialtyQuery->where(function (Builder $innerQuery) use ($blockedTerms) {
                    foreach ($blockedTerms as $term) {
                        $innerQuery->orWhere('name', 'like', '%' . $term . '%');
                    }
                });
            });
        }

        return $query;
    }


    private function patientArticleBlockedTerms(): array
    {
        return [
            'back-end',
            'backend',
            'back end',
            'front-end',
            'frontend',
            'front end',
            'laravel',
            'php',
            'javascript',
            'programming',
            'development',
            'developer',
            'database',
            'برمجة',
            'مبرمج',
            'مطور',
            'تطوير مواقع',
            'تطوير الويب',
            'لارافيل',
            'كود',
            'أكواد',
            'باك اند',
            'فرونت اند',
            'واجهات برمجية',
        ];
    }


    private function applyArticleTypeScope(Builder $query, array $types): Builder
    {
        if (! $this->columnExists('articles', 'article_type')) {
            return $query;
        }

        return $query->whereIn('article_type', $types);
    }


    private function applyPatientRecommendedArticleScope(Builder $query, array $dashboardData, ?int $doctorProfileId = null, ?int $doctorUserId = null): Builder
    {
        $audiences = $this->patientRecommendedAudiences($dashboardData);
        $keywords = $this->patientRecommendedKeywords($dashboardData);

        return $query->where(function (Builder $query) use ($audiences, $keywords, $doctorProfileId, $doctorUserId) {
            $hasCondition = false;

            if ($this->columnExists('articles', 'audience')) {
                $query->whereIn('audience', $audiences);
                $hasCondition = true;
            }

            if ($keywords !== []) {
                $method = $hasCondition ? 'orWhere' : 'where';
                $query->{$method}(function (Builder $innerQuery) use ($keywords) {
                    foreach ($keywords as $keyword) {
                        foreach (['title', 'excerpt', 'content'] as $column) {
                            if ($this->columnExists('articles', $column)) {
                                $innerQuery->orWhere($column, 'like', '%' . $keyword . '%');
                            }
                        }
                    }
                });
                $hasCondition = true;
            }

            if ($doctorProfileId && $this->columnExists('articles', 'doctor_profile_id')) {
                $hasCondition ? $query->orWhere('doctor_profile_id', $doctorProfileId) : $query->where('doctor_profile_id', $doctorProfileId);
                $hasCondition = true;
            }

            if ($doctorUserId && $this->columnExists('articles', 'user_id')) {
                $hasCondition ? $query->orWhere('user_id', $doctorUserId) : $query->where('user_id', $doctorUserId);
                $hasCondition = true;
            }

            if (! $hasCondition) {
                $query->whereRaw('1 = 1');
            }
        });
    }


    private function patientRecommendedAudiences(array $dashboardData): array
    {
        $profile = (array) data_get($dashboardData, 'patient.profile', []);
        $audiences = ['patient', 'general'];
        $goal = (string) ($profile['health_goal'] ?? '');
        $activity = (string) ($profile['activity_level'] ?? '');
        $conditions = $profile['medical_conditions'] ?? [];

        if (is_string($conditions)) {
            $decoded = json_decode($conditions, true);
            $conditions = is_array($decoded) ? $decoded : [$conditions];
        }

        $haystack = mb_strtolower(implode(' ', array_filter(array_merge([$goal, $activity], (array) $conditions))), 'UTF-8');

        if (str_contains($haystack, 'loss') || str_contains($haystack, 'lose') || str_contains($haystack, 'خس') || str_contains($haystack, 'تنحيف')) {
            $audiences[] = 'weight_loss';
        }

        if (str_contains($haystack, 'gain') || str_contains($haystack, 'زيادة') || str_contains($haystack, 'نحافة')) {
            $audiences[] = 'weight_gain';
        }

        if (str_contains($haystack, 'diabetes') || str_contains($haystack, 'سكر')) {
            $audiences[] = 'diabetes';
        }

        if (str_contains($haystack, 'heart') || str_contains($haystack, 'قلب') || str_contains($haystack, 'ضغط')) {
            $audiences[] = 'heart_health';
        }

        if (str_contains($activity, 'low') || str_contains($activity, 'sedentary') || str_contains($activity, 'قليل')) {
            $audiences[] = 'low_activity';
        }

        if (isset($profile['sleep_hours']) && is_numeric($profile['sleep_hours']) && (float) $profile['sleep_hours'] < 7) {
            $audiences[] = 'sleep_health';
        }

        if (isset($profile['water_cups']) && is_numeric($profile['water_cups']) && (int) $profile['water_cups'] < 6) {
            $audiences[] = 'hydration';
        }

        return array_values(array_unique($audiences));
    }


    private function patientRecommendedKeywords(array $dashboardData): array
    {
        $profile = (array) data_get($dashboardData, 'patient.profile', []);
        $keywords = ['تغذية', 'صحة', 'عادات'];
        $goal = (string) ($profile['health_goal'] ?? '');
        $conditions = $profile['medical_conditions'] ?? [];

        if (is_string($conditions)) {
            $decoded = json_decode($conditions, true);
            $conditions = is_array($decoded) ? $decoded : [$conditions];
        }

        $haystack = mb_strtolower(implode(' ', array_filter(array_merge([$goal], (array) $conditions))), 'UTF-8');

        if (str_contains($haystack, 'خس') || str_contains($haystack, 'تنحيف') || str_contains($haystack, 'loss')) {
            array_push($keywords, 'وزن', 'سعرات', 'تنحيف');
        }

        if (str_contains($haystack, 'زيادة') || str_contains($haystack, 'gain')) {
            array_push($keywords, 'زيادة وزن', 'بروتين');
        }

        if (str_contains($haystack, 'سكر') || str_contains($haystack, 'diabetes')) {
            array_push($keywords, 'سكري', 'سكر', 'وجبات');
        }

        if (isset($profile['sleep_hours']) && is_numeric($profile['sleep_hours']) && (float) $profile['sleep_hours'] < 7) {
            array_push($keywords, 'نوم', 'روتين');
        }

        if (isset($profile['water_cups']) && is_numeric($profile['water_cups']) && (int) $profile['water_cups'] < 6) {
            array_push($keywords, 'ماء', 'ترطيب');
        }

        return array_values(array_unique(array_filter($keywords)));
    }


    private function applyDoctorArticleScope(Builder $query, ?int $doctorProfileId, ?int $doctorUserId = null): Builder
    {
        return $query->where(function (Builder $query) use ($doctorProfileId, $doctorUserId) {
            $hasCondition = false;

            if ($doctorProfileId && $this->columnExists('articles', 'doctor_profile_id')) {
                $query->where('doctor_profile_id', $doctorProfileId);
                $hasCondition = true;
            }

            if ($doctorUserId && $this->columnExists('articles', 'user_id')) {
                $hasCondition ? $query->orWhere('user_id', $doctorUserId) : $query->where('user_id', $doctorUserId);
                $hasCondition = true;
            }

            if ($doctorUserId && $this->columnExists('articles', 'author_id')) {
                $hasCondition ? $query->orWhere('author_id', $doctorUserId) : $query->where('author_id', $doctorUserId);
                $hasCondition = true;
            }

            if ($hasCondition && $this->columnExists('articles', 'source')) {
                $query->orWhere(function (Builder $query) use ($doctorUserId) {
                    $query->where('source', 'doctor');

                    if ($doctorUserId && $this->columnExists('articles', 'user_id')) {
                        $query->where('user_id', $doctorUserId);
                    }
                });
            }

            if (! $hasCondition) {
                $query->whereRaw('1 = 0');
            }
        });
    }


    private function applyEtzanArticleScope(Builder $query, ?int $doctorProfileId = null, ?int $doctorUserId = null): Builder
    {
        if ($this->columnExists('articles', 'source')) {
            $query->where(function (Builder $query) {
                $query->where('source', 'admin')->orWhereNull('source');
            });
        }

        if ($doctorProfileId || $doctorUserId) {
            $query->where(function (Builder $query) use ($doctorProfileId, $doctorUserId) {
                $query->whereRaw('1 = 1');

                if ($doctorProfileId && $this->columnExists('articles', 'doctor_profile_id')) {
                    $query->where(function (Builder $innerQuery) use ($doctorProfileId) {
                        $innerQuery->whereNull('doctor_profile_id')->orWhere('doctor_profile_id', '!=', $doctorProfileId);
                    });
                }

                if ($doctorUserId && $this->columnExists('articles', 'user_id')) {
                    $query->where(function (Builder $innerQuery) use ($doctorUserId) {
                        $innerQuery->whereNull('user_id')->orWhere('user_id', '!=', $doctorUserId);
                    });
                }

                if ($doctorUserId && $this->columnExists('articles', 'author_id')) {
                    $query->where(function (Builder $innerQuery) use ($doctorUserId) {
                        $innerQuery->whereNull('author_id')->orWhere('author_id', '!=', $doctorUserId);
                    });
                }
            });
        }

        return $query;
    }


    private function patientArticleCategories()
    {
        if (! $this->tableExists('article_categories') || ! $this->tableExists('articles') || ! $this->columnExists('articles', 'article_category_id')) {
            return collect();
        }

        $categoryIds = $this->patientVisibleArticlesQuery()
            ->whereNotNull('article_category_id')
            ->pluck('article_category_id')
            ->filter()
            ->unique()
            ->values();

        if ($categoryIds->isEmpty()) {
            return collect();
        }

        return ArticleCategory::query()
            ->whereIn('id', $categoryIds)
            ->orderBy('name')
            ->get();
    }


    private function doctorArticles(int $doctorProfileId, ?int $doctorUserId = null): array
    {
        if (! $this->tableExists('articles')) {
            return [];
        }

        $query = $this->patientVisibleArticlesQuery();
        $this->applyDoctorArticleScope($query, $doctorProfileId, $doctorUserId);

        return $query->limit(3)->get()->map(function (Article $article) {
            return [
                'id' => $article->id,
                'title' => $article->title ?? 'مقال صحي',
                'slug' => $article->slug ?? null,
                'url' => !empty($article->slug) && \Illuminate\Support\Facades\Route::has('patient.articles.show')
                    ? route('patient.articles.show', $article->slug)
                    : null,
                'excerpt' => $article->excerpt ?: Str::limit(strip_tags((string) $article->content), 90),
                'tag' => optional($article->category)->name ?? optional($article->specialty)->name ?? 'مقال طبي',
                'read_time' => $article->reading_time_label ?? 'قراءة قصيرة',
                'image' => $article->cover_image_url ?? null,
            ];
        })->toArray();
    }


private function appointmentSlots(?int $doctorProfileId = null, ?string $date = null, ?int $ignoreAppointmentId = null): array
    {
        $groups = $this->defaultAppointmentSlotGroups();

        if (!$doctorProfileId || !$date) {
            return $groups;
        }

        try {
            $appointmentDate = Carbon::parse($date)->toDateString();
        } catch (\Throwable $exception) {
            return $groups;
        }

        if (Carbon::parse($appointmentDate)->startOfDay()->lt(now()->startOfDay())) {
            return $this->emptyAppointmentSlotGroups($groups);
        }

        $bookedTimes = $this->bookedAppointmentTimes($doctorProfileId, $appointmentDate, $ignoreAppointmentId);

        return collect($groups)
            ->map(function ($period) use ($bookedTimes) {
                $period['times'] = collect($period['times'] ?? [])
                    ->map(fn ($time) => $this->normalizeAppointmentTime($time))
                    ->reject(fn ($time) => in_array($time, $bookedTimes, true))
                    ->values()
                    ->all();

                return $period;
            })
            ->toArray();
    }


private function defaultAppointmentSlotGroups(): array
    {
        return [
            'morning' => ['label' => 'الفترة الصباحية', 'icon' => 'sun', 'times' => ['09:00', '09:30', '10:00', '10:30', '11:00']],
            'afternoon' => ['label' => 'فترة الظهيرة', 'icon' => 'cloud-sun', 'times' => ['12:00', '12:30', '13:00', '13:30', '14:00']],
            'evening' => ['label' => 'الفترة المسائية', 'icon' => 'moon', 'times' => ['16:00', '16:30', '17:00', '17:30', '18:00']],
        ];
    }


    private function emptyAppointmentSlotGroups(array $groups): array
    {
        return collect($groups)
            ->map(function ($period) {
                $period['times'] = [];
                return $period;
            })
            ->toArray();
    }


    private function normalizeAppointmentTime(?string $time): string
    {
        $time = trim((string) $time);

        if ($time === '') {
            return '';
        }

        try {
            return Carbon::parse($time)->format('H:i');
        } catch (\Throwable $exception) {
            if (preg_match('/^(\d{1,2}):(\d{2})/', $time, $matches)) {
                return str_pad($matches[1], 2, '0', STR_PAD_LEFT) . ':' . $matches[2];
            }

            return $time;
        }
    }


    private function flatAppointmentTimes(array $groups): array
    {
        return collect($groups)
            ->flatMap(fn ($period) => $period['times'] ?? [])
            ->map(fn ($time) => $this->normalizeAppointmentTime($time))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }


    private function bookedAppointmentTimes(int $doctorProfileId, string $date, ?int $ignoreAppointmentId = null): array
    {
        if (!$doctorProfileId || !$this->tableExists('patient_appointments')) {
            return [];
        }

        if (!$this->columnExists('patient_appointments', 'doctor_profile_id') || !$this->columnExists('patient_appointments', 'appointment_date') || !$this->columnExists('patient_appointments', 'appointment_time')) {
            return [];
        }

        $query = DB::table('patient_appointments')
            ->where('doctor_profile_id', $doctorProfileId)
            ->whereDate('appointment_date', $date);

        if ($ignoreAppointmentId) {
            $query->where('id', '!=', $ignoreAppointmentId);
        }

        if ($this->columnExists('patient_appointments', 'status')) {
            $query->whereIn('status', [
                'pending',
                'confirmed',
                'approved',
                'reschedule_requested',
            ]);
        }

        return $query
            ->pluck('appointment_time')
            ->map(fn ($time) => $this->normalizeAppointmentTime((string) $time))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }


    private function isAppointmentSlotAvailable(int $doctorProfileId, string $date, string $time, ?int $ignoreAppointmentId = null): bool
    {
        $time = $this->normalizeAppointmentTime($time);

        if (!$doctorProfileId || !$date || !$time) {
            return false;
        }

        $availableTimes = $this->flatAppointmentTimes(
            $this->appointmentSlots($doctorProfileId, $date, $ignoreAppointmentId)
        );

        return in_array($time, $availableTimes, true);
    }


    private function appointmentMonthMeta(int $doctorProfileId, string $monthDate, ?int $ignoreAppointmentId = null): array
    {
        try {
            $baseDate = Carbon::parse($monthDate);
        } catch (\Throwable $exception) {
            $baseDate = now();
        }

        $start = $baseDate->copy()->startOfMonth();
        $end = $baseDate->copy()->endOfMonth();
        $today = now()->startOfDay();
        $availableDates = [];
        $fullyBookedDates = [];

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            if ($day->copy()->startOfDay()->lt($today)) {
                continue;
            }

            $date = $day->toDateString();
            $defaultTimes = $this->flatAppointmentTimes($this->defaultAppointmentSlotGroups());
            $availableTimes = $this->flatAppointmentTimes($this->appointmentSlots($doctorProfileId, $date, $ignoreAppointmentId));

            if (count($availableTimes) > 0) {
                $availableDates[] = $date;
            } elseif (count($defaultTimes) > 0) {
                $fullyBookedDates[] = $date;
            }
        }

        return [
            'availableAppointmentDates' => array_values(array_unique($availableDates)),
            'fullyBookedAppointmentDates' => array_values(array_unique($fullyBookedDates)),
        ];
    }


    private function calendarAppointmentsForPatient(?int $userId, ?int $profileId, ?string $monthDate = null): array
    {
        if (!$this->tableExists('patient_appointments') || (!$userId && !$profileId)) {
            return [];
        }

        try {
            $baseDate = Carbon::parse($monthDate ?: now());
        } catch (\Throwable $exception) {
            $baseDate = now();
        }

        $start = $baseDate->copy()->startOfMonth()->toDateString();
        $end = $baseDate->copy()->endOfMonth()->toDateString();

        $query = DB::table('patient_appointments')
            ->where(function ($q) use ($userId, $profileId) {
                if ($userId && $this->columnExists('patient_appointments', 'user_id')) {
                    $q->orWhere('user_id', $userId);
                }

                if ($profileId && $this->columnExists('patient_appointments', 'patient_profile_id')) {
                    $q->orWhere('patient_profile_id', $profileId);
                }
            });

        if ($this->columnExists('patient_appointments', 'appointment_date')) {
            $query->whereBetween('appointment_date', [$start, $end]);
        }

        if ($this->columnExists('patient_appointments', 'appointment_date')) {
            $query->orderBy('appointment_date');
        }

        if ($this->columnExists('patient_appointments', 'appointment_time')) {
            $query->orderBy('appointment_time');
        }

        return $query->get()->map(function ($appointment) {
            $status = $appointment->status ?? 'pending';
            $date = $appointment->appointment_date ?? null;
            $time = $this->normalizeAppointmentTime((string) ($appointment->appointment_time ?? ''));
            $reason = $appointment->reason ?? null;

            return [
                'id' => $appointment->id ?? null,
                'date' => $date,
                'appointment_date' => $date,
                'time' => $time,
                'appointment_time' => $time,
                'status' => $status,
                'db_status' => $status,
                'reason' => $reason,
                'reason_label' => $reason,
                'label' => $this->appointmentStatusTextForCalendar($status),
            ];
        })->toArray();
    }


    private function appointmentStatusTextForCalendar(?string $status): string
    {
        return match ($status) {
            'pending' => 'بانتظار تأكيد الطبيب',
            'starting_now' => 'بدأ الآن',
            'confirmed', 'approved' => 'مؤكد',
            'completed' => 'منتهي',
            'missed' => 'فائت',
            'reschedule_requested' => 'اقتراح وقت جديد',
            'rejected', 'patient_declined_reschedule' => 'مرفوض',
            'cancelled', 'canceled' => 'ملغى',
            default => 'موعد مسجل',
        };
    }


    private function doctorUserIdFromProfile(?int $doctorProfileId): ?int
    {
        if (!$doctorProfileId || !$this->tableExists('doctor_profiles') || !$this->columnExists('doctor_profiles', 'user_id')) {
            return null;
        }

        $userId = DB::table('doctor_profiles')
            ->where('id', $doctorProfileId)
            ->value('user_id');

        return $userId ? (int) $userId : null;
    }


private function nextAppointment(?int $userId, ?int $profileId): ?array
    {
        if (!$this->tableExists('patient_appointments') || (!$userId && !$profileId)) {
            return null;
        }

        $query = DB::table('patient_appointments');

        $query->where(function ($q) use ($userId, $profileId) {
            if ($userId && $this->columnExists('patient_appointments', 'user_id')) {
                $q->orWhere('user_id', $userId);
            }

            if ($profileId && $this->columnExists('patient_appointments', 'patient_profile_id')) {
                $q->orWhere('patient_profile_id', $profileId);
            }
        });

        if ($this->columnExists('patient_appointments', 'created_at')) {
            $query->orderByDesc('created_at');
        } else {
            $query->orderByDesc('id');
        }

        $rows = $query->limit(30)->get();

        if ($rows->isEmpty()) {
            return null;
        }

        $now = now();

        $items = $rows->map(function ($appointment) use ($now) {
            $displayStatus = $this->appointmentDisplayStatus($appointment, $now);
            $appointmentDateTime = $this->appointmentDateTime($appointment);
            $timestamp = $appointmentDateTime ? $appointmentDateTime->timestamp : 0;


            $priority = match ($displayStatus) {
                'starting_now' => 1,
                'pending' => 2,
                'reschedule_requested' => 3,
                'confirmed', 'approved' => 4,
                'rejected', 'patient_declined_reschedule', 'cancelled', 'canceled' => 5,
                'completed' => 6,
                'missed' => 7,
                default => 9,
            };

            $sortTimestamp = in_array($priority, [5, 6, 7], true)
                ? -$timestamp
                : $timestamp;

            return [
                'row' => $appointment,
                'display_status' => $displayStatus,
                'appointment_datetime' => $appointmentDateTime,
                'priority' => $priority,
                'sort_timestamp' => $sortTimestamp,
            ];
        })->sort(function ($a, $b) {
            if ($a['priority'] !== $b['priority']) {
                return $a['priority'] <=> $b['priority'];
            }

            return $a['sort_timestamp'] <=> $b['sort_timestamp'];
        })->values();

        $selected = $items->first();

        if (!$selected) {
            return null;
        }

        $appointment = $selected['row'];
        $rawStatus = $appointment->status ?? 'pending';
        $status = $selected['display_status'];
        $consultationType = $appointment->consultation_type ?? 'online';
        $meetingUrl = $appointment->meeting_url ?? null;
        $appointmentDateTime = $selected['appointment_datetime'];

        $durationMinutes = (int) ($appointment->duration_minutes ?? $appointment->meeting_duration ?? 60);
        $durationMinutes = max($durationMinutes, 15);

        return [
            'id' => $appointment->id ?? null,
            'date' => $appointment->appointment_date ?? null,
            'time' => $appointment->appointment_time ?? null,
            'starts_at' => $appointmentDateTime?->toDateTimeString(),
            'ends_at' => $appointmentDateTime?->copy()->addMinutes($durationMinutes)->toDateTimeString(),
            'duration_minutes' => $durationMinutes,

            'consultation_type' => $consultationType,
            'consultation_label' => $consultationType === 'clinic' ? 'حضوري' : 'أونلاين',
            'reason' => $appointment->reason ?? null,
            'notes' => $appointment->notes ?? null,

            'db_status' => $rawStatus,
            'status' => $status,

            'meeting_platform' => $appointment->meeting_platform ?? null,
            'meeting_url' => $meetingUrl,
            'meeting_notes' => $appointment->meeting_notes ?? null,
            'meeting_added_at' => $appointment->meeting_added_at ?? null,
            'has_meeting_link' => !empty($meetingUrl),

            'status_label' => match ($status) {
                'starting_now' => 'موعدك بدأ الآن',
                'confirmed', 'approved' => 'مؤكد',
                'completed' => 'تم انتهاء الموعد',
                'missed' => 'فات الموعد',
                'rejected' => 'مرفوض',
                'reschedule_requested' => 'اقتراح موعد جديد',
                'patient_declined_reschedule' => 'تم رفض الموعد المقترح',
                'cancelled', 'canceled' => 'تم إلغاء الموعد',
                default => 'بانتظار التأكيد',
            },

            'doctor_response_message' => $appointment->doctor_response_message ?? null,
            'doctor_after_session_notes' => $appointment->doctor_after_session_notes
                ?? $appointment->doctor_session_notes
                ?? $appointment->session_notes
                ?? null,

            'suggested_date' => $appointment->suggested_date ?? null,
            'suggested_time' => $appointment->suggested_time ?? null,
            'patient_response_status' => $appointment->patient_response_status ?? null,
            'patient_response_message' => $appointment->patient_response_message ?? null,
        ];
    }


    private function appointmentDisplayStatus(object $appointment, Carbon $now): string
    {
        $status = $appointment->status ?? 'pending';

        if (in_array($status, [
            'completed',
            'missed',
            'rejected',
            'cancelled',
            'canceled',
            'patient_declined_reschedule',
            'reschedule_requested',
            'pending',
        ], true)) {
            return $status;
        }

        if (!in_array($status, ['confirmed', 'approved'], true)) {
            return $status;
        }

        $start = $this->appointmentDateTime($appointment);

        if (!$start) {
            return $status;
        }

        $durationMinutes = (int) ($appointment->duration_minutes ?? $appointment->meeting_duration ?? 60);
        $durationMinutes = max($durationMinutes, 15);

        $end = $start->copy()->addMinutes($durationMinutes);

        if ($now->betweenIncluded($start, $end)) {
            return 'starting_now';
        }

        if ($now->greaterThan($end)) {
            return 'completed';
        }

        return $status;
    }


    private function appointmentDateTime(object $appointment): ?Carbon
    {
        $date = $appointment->appointment_date ?? null;
        $time = $appointment->appointment_time ?? null;

        if (!$date || !$time) {
            return null;
        }

        try {
            return Carbon::parse($date . ' ' . $time);
        } catch (\Throwable $e) {
            return null;
        }
    }


    private function patientNotifications(?int $userId, int $limit = 8): array
    {
        return app(AppNotificationService::class)->latestUnread(
            recipientUserId: $userId,
            limit: $limit,
            recipientRole: 'patient'
        );
    }


    private function unreadAppNotificationsCount(?int $userId): int
    {
        return app(AppNotificationService::class)->unreadCount(
            recipientUserId: $userId,
            recipientRole: 'patient'
        );
    }


   private function createAppNotification(
        ?int $recipientUserId,
        ?int $actorUserId,
        string $type,
        string $title,
        string $body,
        ?string $url = null,
        ?int $appointmentId = null,
        ?int $relatedId = null,
        ?string $relatedType = null,
        ?string $recipientRole = 'patient',
        array $data = []
    ): void {
        if (! $recipientUserId) {
            return;
        }

        $finalRelatedId = $relatedId ?? $appointmentId;
        $finalRelatedType = $relatedType;

        if ($appointmentId && ! $finalRelatedType) {
            $finalRelatedType = 'patient_appointment';
        }

        app(AppNotificationService::class)->send(
            recipientUserId: $recipientUserId,
            recipientRole: $recipientRole,
            type: $type,
            title: $title,
            body: $body,
            url: $url,
            actorUserId: $actorUserId,
            relatedId: $finalRelatedId,
            relatedType: $finalRelatedType,
            data: $data
        );
    }


    /**
     * إشعار كل الأدمنز الحاليين — بعد توحيد نظام الإشعارات، نقطة الكتابة
     * الوحيدة صارت AppNotificationService::sendToAllAdmins() (نفس النوع
     * الحقيقي بدون بادئة، حتى يتوافق مع type_label/type_icon الموحّدين).
     * قبل هيك كانت هاي الدالة بتكتب مرتين: مرة بجدول admin_notifications
     * القديم (مشترك، حالة قراءة واحدة لكل الأدمنز)، ومرة تانية بـ
     * app_notifications بنوع مبدوء بـ"admin_" ما كان أي شاشة بتقرأه.
     */
    private function createAdminNotification(
        string $type,
        string $title,
        string $body,
        ?string $url = null,
        ?int $relatedId = null,
        ?string $relatedType = null
    ): void {
        app(AppNotificationService::class)->sendToAllAdmins(
            type: $type,
            title: $title,
            body: $body,
            url: $url,
            actorUserId: auth()->id(),
            relatedId: $relatedId,
            relatedType: $relatedType
        );
    }


    private function todayTasks($user, ?object $profile): array
    {
        if (!$user || !$this->tableExists('patient_tasks')) {
            return [];
        }

        $query = DB::table('patient_tasks');

        if ($this->columnExists('patient_tasks', 'patient_id')) {
            $ids = array_values(array_unique(array_filter([$user->id, $profile->id ?? null])));

            if (!empty($ids)) {
                $query->whereIn('patient_id', $ids);
            }
        } elseif ($this->columnExists('patient_tasks', 'user_id')) {
            $query->where('user_id', $user->id);
        } else {
            return [];
        }

        if ($this->columnExists('patient_tasks', 'task_date')) {
            $query->whereDate('task_date', now()->toDateString());
        }

        if ($this->columnExists('patient_tasks', 'created_at')) {
            $query->orderByDesc('created_at');
        }

        $rows = $query->limit(8)->get();

        if ($rows->isEmpty()) {
            return [];
        }

        return $rows->map(function ($task) {
            $title = $task->title ?? $task->name ?? 'مهمة صحية';
            $completed = (bool) ($task->completed ?? $task->is_completed ?? false);

            return [
                'title' => $title,
                'description' => $task->description ?? null,
                'completed' => $completed,
                'time' => $task->time ?? $task->task_time ?? '—',
                'type' => $task->type ?? 'general',
                'icon' => $this->taskIcon($task->type ?? $title),
                'progress' => $completed ? 100 : 0,
                'progressText' => $completed ? 'مكتملة' : 'بانتظار التنفيذ',
            ];
        })->values()->toArray();
    }


    private function journeyTasks(array $tasks): array
    {
        return collect($tasks)->take(8)->map(function ($task) {
            return [
                'title' => $task['title'],
                'time' => $task['time'] ?: '—',
                'progressText' => $task['progressText'] ?? 'بانتظار التنفيذ',
                'progress' => $task['progress'] ?? 0,
                'completed' => $task['completed'] ?? false,
                'icon' => $task['icon'] ?? 'circle-check',
            ];
        })->values()->toArray();
    }


    private function latestMessages(?int $userId, ?int $doctorUserId = null): array
    {
        if (!$userId || !$this->tableExists('messages')) {
            return [];
        }

        $conversationId = null;

        if ($this->tableExists('conversations') && $this->columnExists('conversations', 'user_id')) {
            $conversationQuery = DB::table('conversations')
                ->where('user_id', $userId);

            if ($this->columnExists('conversations', 'type')) {
                $conversationQuery->where('type', 'doctor');
            } elseif ($this->columnExists('conversations', 'subject')) {
                $conversationQuery->where('subject', 'رسائل الطبيب');
            }

            if ($doctorUserId && $this->columnExists('conversations', 'doctor_user_id')) {
                $conversationQuery->where('doctor_user_id', $doctorUserId);
            }

            if ($doctorUserId && $this->columnExists('conversations', 'doctor_id')) {
                $conversationQuery->where('doctor_id', $doctorUserId);
            }

            $conversationId = $conversationQuery
                ->orderByDesc('id')
                ->value('id');
        }

        $query = DB::table('messages');

        if ($conversationId && $this->columnExists('messages', 'conversation_id')) {
            $query->where('conversation_id', $conversationId);
        } elseif (
            $doctorUserId
            && $this->columnExists('messages', 'sender_id')
            && $this->columnExists('messages', 'receiver_id')
        ) {
            $query->where(function ($q) use ($userId, $doctorUserId) {
                $q->where(function ($inner) use ($userId, $doctorUserId) {
                    $inner->where('sender_id', $userId)
                        ->where('receiver_id', $doctorUserId);
                })->orWhere(function ($inner) use ($userId, $doctorUserId) {
                    $inner->where('sender_id', $doctorUserId)
                        ->where('receiver_id', $userId);
                });
            });
        } elseif ($this->columnExists('messages', 'user_id')) {
            $query->where('user_id', $userId);
        } else {
            return [];
        }

        if ($this->columnExists('messages', 'created_at')) {
            $query->orderBy('created_at');
        }

        $query->orderBy('id');

        return $query
            ->limit(120)
            ->get()
            ->map(function ($message) use ($userId) {
                $body = $message->body
                    ?? $message->message
                    ?? $message->content
                    ?? $message->text
                    ?? '';

                $senderId = $message->sender_id ?? null;
                $senderType = $message->sender_type ?? null;

                $isMe = false;

                if ($senderType === 'patient') {
                    $isMe = true;
                }

                if ($senderId !== null && (int) $senderId === (int) $userId) {
                    $isMe = true;
                }

                return [
                    'id' => $message->id ?? null,
                    'sender' => $isMe ? 'أنت' : 'الطبيب',
                    'body' => $body,
                    'time' => isset($message->created_at) ? $this->humanTime($message->created_at) : '',
                    'avatar' => null,
                    'is_me' => $isMe,
                ];
            })
            ->toArray();
    }


    private function supportMessages(?int $userId): array
    {
        if (!$userId || !$this->tableExists('conversations') || !$this->tableExists('messages')) {
            return [];
        }

        $conversationQuery = DB::table('conversations')
            ->where('user_id', $userId);

        if ($this->columnExists('conversations', 'type')) {
            $conversationQuery->where('type', 'support');
        } elseif ($this->columnExists('conversations', 'subject')) {
            $conversationQuery->where('subject', 'دعم فني');
        }

        $conversationId = $conversationQuery->orderByDesc('id')->value('id');

        if (!$conversationId) {
            return [];
        }

        $query = DB::table('messages')->where('conversation_id', $conversationId);

        if ($this->columnExists('messages', 'created_at')) {
            $query->orderBy('created_at');
        }

        return $query
            ->limit(80)
            ->get()
            ->map(function ($message) use ($userId) {
                $body = $message->body
                    ?? $message->message
                    ?? $message->content
                    ?? $message->text
                    ?? '';

                $senderType = $message->sender_type ?? null;
                $senderRole = $message->sender_role ?? null;
                $direction = $message->direction ?? null;
                $senderId = $message->sender_id ?? null;

                $isAdmin = $senderType === 'admin'
                    || $senderRole === 'admin'
                    || $direction === 'outgoing';

                if (!$isAdmin && $senderId !== null) {
                    $isAdmin = (int) $senderId !== (int) $userId;
                }

                return [
                    'id' => $message->id ?? null,
                    'sender_type' => $isAdmin ? 'admin' : 'patient',
                    'message' => $body,
                    'time' => isset($message->created_at)
                        ? Carbon::parse($message->created_at)->diffForHumans()
                        : '',
                ];
            })
            ->toArray();
    }


    /**
     * كان هاد بيتحقق من عمود receiver_id/user_id غير موجودين أصلاً بجدول
     * messages (الأعمدة الحقيقية: conversation_id, sender_id, sender_type,
     * is_read فقط)، فكان دايماً بيرجع صفر بغض النظر عن الرسائل الحقيقية.
     * التصليح: رسائل المريض الغير مقروءة هي الرسائل يلي إرسلها الطبيب
     * (sender_type='doctor') بمحادثته هو (conversations.user_id = المريض).
     */
    private function unreadMessagesCount(?int $userId): int
    {
        if (!$userId || !$this->tableExists('messages') || !$this->tableExists('conversations')) {
            return 0;
        }

        if (!$this->columnExists('messages', 'sender_type') || !$this->columnExists('messages', 'is_read')) {
            return 0;
        }

        return DB::table('messages')
            ->join('conversations', 'conversations.id', '=', 'messages.conversation_id')
            ->where('conversations.user_id', $userId)
            ->where('messages.sender_type', 'doctor')
            ->where('messages.is_read', false)
            ->count();
    }


        private function ensureConversation(
        int $patientUserId,
        int $doctorUserId,
        ?int $patientProfileId = null,
        ?int $doctorProfileId = null
    ): ?int {
        if (!$this->tableExists('conversations')) {
            return null;
        }

        if (!$this->columnExists('conversations', 'user_id')) {
            return null;
        }

        $query = DB::table('conversations')
            ->where('user_id', $patientUserId);

        if ($this->columnExists('conversations', 'type')) {
            $query->where('type', 'doctor');
        } elseif ($this->columnExists('conversations', 'subject')) {
            $query->where('subject', 'رسائل الطبيب');
        }

        if ($this->columnExists('conversations', 'doctor_user_id')) {
            $query->where('doctor_user_id', $doctorUserId);
        }

        if ($this->columnExists('conversations', 'doctor_id')) {
            $query->where('doctor_id', $doctorUserId);
        }

        if ($this->columnExists('conversations', 'doctor_profile_id') && $doctorProfileId) {
            $query->where('doctor_profile_id', $doctorProfileId);
        }

        $existingId = $query->orderByDesc('id')->value('id');

        if ($existingId) {
            return (int) $existingId;
        }

        $payload = [
            'user_id' => $patientUserId,
        ];

        if ($this->columnExists('conversations', 'type')) {
            $payload['type'] = 'doctor';
        }

        if ($this->columnExists('conversations', 'subject')) {
            $payload['subject'] = 'رسائل الطبيب';
        }

        if ($this->columnExists('conversations', 'doctor_user_id')) {
            $payload['doctor_user_id'] = $doctorUserId;
        }

        if ($this->columnExists('conversations', 'doctor_id')) {
            $payload['doctor_id'] = $doctorUserId;
        }

        if ($this->columnExists('conversations', 'patient_profile_id') && $patientProfileId) {
            $payload['patient_profile_id'] = $patientProfileId;
        }

        if ($this->columnExists('conversations', 'doctor_profile_id') && $doctorProfileId) {
            $payload['doctor_profile_id'] = $doctorProfileId;
        }

        /*
        * لا نرسل status هنا؛ لأن قاعدة البيانات عندك رفضت open/active سابقًا.
        */

        if ($this->columnExists('conversations', 'last_message_at')) {
            $payload['last_message_at'] = now();
        }

        if ($this->columnExists('conversations', 'unread_by_admin')) {
            $payload['unread_by_admin'] = 0;
        }

        if ($this->columnExists('conversations', 'created_at')) {
            $payload['created_at'] = now();
        }

        if ($this->columnExists('conversations', 'updated_at')) {
            $payload['updated_at'] = now();
        }

        return (int) DB::table('conversations')->insertGetId($payload);
    }

    /* ==================================================
       Weekly Health Chart
       يحسب الرسم البياني من بيانات حقيقية فقط.
       المصدر الحالي: جدول patient_tasks آخر 7 أيام.
       لا يغيّر أي شيء في الرسائل أو المواعيد.
    ================================================== */


    private function weeklyHealthChart($user, ?object $profile): array
    {
        $days = collect(range(6, 0))->map(function ($offset) {
            $date = now()->copy()->subDays($offset);

            return [
                'date' => $date->toDateString(),
                'label' => $date->locale('ar')->translatedFormat('D'),
            ];
        });

        $labels = $days->pluck('label')->toArray();

        $emptyChart = [
            'title' => 'التزامك الصحي هذا الأسبوع',
            'filters' => ['الأسبوع', 'الشهر', '3 أشهر'],
            'labels' => $labels,
            'series' => [
                [
                    'name' => 'الالتزام',
                    'values' => [],
                ],
            ],
        ];

        if (!$user || !$this->tableExists('patient_tasks')) {
            return $emptyChart;
        }

        $query = DB::table('patient_tasks');

        if ($this->columnExists('patient_tasks', 'patient_id')) {
            $ids = array_values(array_unique(array_filter([
                $user->id,
                $profile->id ?? null,
            ])));

            if (empty($ids)) {
                return $emptyChart;
            }

            $query->whereIn('patient_id', $ids);
        } elseif ($this->columnExists('patient_tasks', 'user_id')) {
            $query->where('user_id', $user->id);
        } else {
            return $emptyChart;
        }

        $dateColumn = null;

        foreach (['task_date', 'date', 'day', 'created_at'] as $column) {
            if ($this->columnExists('patient_tasks', $column)) {
                $dateColumn = $column;
                break;
            }
        }

        if (!$dateColumn) {
            return $emptyChart;
        }

        $startDate = now()->copy()->subDays(6)->startOfDay();
        $endDate = now()->copy()->endOfDay();

        if ($dateColumn === 'created_at') {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        } else {
            $query->whereBetween($dateColumn, [
                $startDate->toDateString(),
                $endDate->toDateString(),
            ]);
        }

        $rows = $query->get();

        if ($rows->isEmpty()) {
            return $emptyChart;
        }

        $values = $days->map(function ($day) use ($rows, $dateColumn) {
            $dayRows = $rows->filter(function ($task) use ($day, $dateColumn) {
                $rawDate = $task->{$dateColumn} ?? null;

                if (!$rawDate) {
                    return false;
                }

                try {
                    return Carbon::parse($rawDate)->toDateString() === $day['date'];
                } catch (\Throwable $e) {
                    return false;
                }
            });

            $total = $dayRows->count();

            if ($total === 0) {
                return 0;
            }

            $completed = $dayRows->filter(function ($task) {
                return (bool) (
                    $task->completed
                    ?? $task->is_completed
                    ?? $task->done
                    ?? false
                );
            })->count();

            return (int) round(($completed / $total) * 100);
        })->toArray();

        $hasAnyRealActivity = collect($values)->filter(fn ($value) => $value > 0)->count() > 0
            || $rows->count() > 0;

        if (!$hasAnyRealActivity) {
            return $emptyChart;
        }

        return [
            'title' => 'التزامك الصحي هذا الأسبوع',
            'filters' => ['الأسبوع', 'الشهر', '3 أشهر'],
            'labels' => $labels,
            'series' => [
                [
                    'name' => 'الالتزام',
                    'values' => $values,
                ],
            ],
        ];
    }


    private function dailyPatientContentCard(array $types, ?object $profile, ?int $doctorProfileId, ?int $doctorUserId, string $fallbackTitle, string $fallbackText): array
    {
        $fallback = [
            'title' => $fallbackTitle,
            'text' => $fallbackText,
            'tag' => $fallbackTitle,
            'url' => \Illuminate\Support\Facades\Route::has('patient.articles') ? route('patient.articles', ['filter' => in_array('motivational_quote', $types, true) ? 'motivation' : 'wisdom']) : '#',
            'image' => null,
            'source' => 'fallback',
        ];

        if (! $this->tableExists('articles')) {
            return $fallback;
        }

        $query = $this->patientVisibleArticlesQuery();
        $this->applyArticleTypeScope($query, $types);

        $seedData = [
            'patient' => [
                'profile' => $profile ? (array) $profile : [],
            ],
        ];

        $audiences = $this->patientRecommendedAudiences($seedData);

        if ($this->columnExists('articles', 'audience')) {
            $query->orderByRaw('CASE WHEN audience IN ('.implode(',', array_fill(0, count($audiences), '?')).') THEN 0 ELSE 1 END', $audiences);
        }

        if ($this->columnExists('articles', 'is_featured')) {
            $query->orderByDesc('is_featured');
        }

        if ($this->columnExists('articles', 'published_at')) {
            $query->latest('published_at');
        }

        $items = $query->limit(10)->get();

        if ($items->isEmpty()) {
            return $fallback;
        }

        $index = ((int) now()->format('z')) % max(1, $items->count());
        $article = $items->values()->get($index) ?: $items->first();

        return [
            'title' => $article->title ?? $fallbackTitle,
            'text' => $article->excerpt ?: Str::limit(strip_tags((string) $article->content), 140),
            'tag' => $article->article_type_label ?? $fallbackTitle,
            'url' => !empty($article->slug) && \Illuminate\Support\Facades\Route::has('patient.articles.show')
                ? route('patient.articles.show', $article->slug)
                : route('patient.articles', ['filter' => in_array('motivational_quote', $types, true) ? 'motivation' : 'wisdom']),
            'image' => $article->cover_image_url ?? null,
            'source' => 'article',
        ];
    }


    private function dashboardArticles(): array
    {
        if (! $this->tableExists('articles')) {
            return [];
        }

        $rows = $this->patientVisibleArticlesQuery()->limit(3)->get();

        if ($rows->isEmpty()) {
            return [];
        }

        return $rows->map(function (Article $article, int $index) {
            $title = $article->title ?? 'مقال صحي';
            $readTime = $article->reading_time_label ?? '4 دقائق قراءة';
            $tag = optional($article->category)->name ?? optional($article->specialty)->name ?? $article->source_label ?? 'تغذية';

            return [
                'title' => $title,
                'subtitle' => $article->excerpt ?? trim($tag . ' • ' . $readTime),
                'tag' => $tag,
                'read_time' => $readTime,
                'slug' => $article->slug ?? null,
                'url' => !empty($article->slug) && \Illuminate\Support\Facades\Route::has('patient.articles.show')
                    ? route('patient.articles.show', $article->slug)
                    : route('patient.articles'),
                'gradient' => ['mint', 'sky', 'peach'][$index % 3],
                'image' => $article->cover_image_url ?? $this->placeholderImage($title),
            ];
        })->toArray();
    }


    private function homeStats(?object $profile, int $todayProgress): array
    {
        $weight = $this->valueFrom($profile, ['weight', 'weight_kg', 'current_weight']);

        return [
            ['title' => 'مهام اليوم', 'value' => $todayProgress, 'unit' => '%', 'subtitle' => 'نسبة الإنجاز', 'icon' => 'badge-check', 'progress' => $todayProgress, 'color' => 'green'],
            ['title' => 'الوزن', 'value' => $weight ?: '—', 'unit' => $weight ? 'كغ' : '', 'subtitle' => $weight ? 'آخر وزن مسجل' : 'لم يتم تسجيل الوزن', 'icon' => 'scale', 'progress' => $this->weightGoalProgress($profile), 'color' => 'purple'],
        ];
    }


    /**
     * نسبة التقدم نحو الوزن المستهدف: (وزن البداية - الوزن الحالي) / (وزن البداية - الهدف).
     * البداية = أول وزن مسجل، الحالي = آخر وزن مسجل (وإلا وزن الملف)، وتشتغل للنزول والزيادة.
     */
    private function weightGoalProgress(?object $profile): int
    {
        $target = $this->valueFrom($profile, ['target_weight_kg']);
        $profileWeight = $this->valueFrom($profile, ['weight', 'weight_kg', 'current_weight']);
        $startWeight = $currentWeight = $profileWeight;

        if ($profile && !empty($profile->user_id) && $this->tableExists('patient_weight_logs')) {
            $logs = DB::table('patient_weight_logs')
                ->where('user_id', $profile->user_id)
                ->orderBy('logged_date')
                ->orderBy('id');

            $startWeight = (clone $logs)->value('weight_kg') ?? $startWeight;
            $currentWeight = (clone $logs)->reorder()->orderByDesc('logged_date')->orderByDesc('id')->value('weight_kg') ?? $currentWeight;
        }

        if (!is_numeric($target) || !is_numeric($startWeight) || !is_numeric($currentWeight)) {
            return 0;
        }

        $totalChange = (float) $startWeight - (float) $target;

        if (abs($totalChange) < 0.01) {
            return abs((float) $currentWeight - (float) $target) < 0.01 ? 100 : 0;
        }

        $progress = ((float) $startWeight - (float) $currentWeight) / $totalChange * 100;

        return (int) round(max(0, min(100, $progress)));
    }


    private function todayDate(): string
    {
        return Carbon::now()->locale('ar')->translatedFormat('l، d F Y');
    }


    private function humanTime($date): string
    {
        return $date ? Carbon::parse($date)->diffForHumans() : '';
    }


    private function taskIcon(?string $value): string
    {
        $value = mb_strtolower((string) $value, 'UTF-8');

        if (str_contains($value, 'water') || str_contains($value, 'ماء')) {
            return 'droplet';
        }

        if (str_contains($value, 'meal') || str_contains($value, 'وجبة')) {
            return 'utensils';
        }

        if (str_contains($value, 'walk') || str_contains($value, 'activity') || str_contains($value, 'نشاط')) {
            return 'activity';
        }

        return 'circle-check';
    }


    private function patientDisplayName($user, ?object $profile): string
    {
        $nameFromProfile = $this->valueFrom($profile, ['full_name', 'patient_name', 'name']);

        if ($this->filledValue($nameFromProfile)) {
            return trim((string) $nameFromProfile);
        }

        if ($user && $this->filledValue($user->name ?? null)) {
            return trim((string) $user->name);
        }

        return 'مريض اتزان';
    }


    private function patientAvatar($user, ?object $profile): ?string
    {
        $avatar = $this->valueFrom($profile, ['avatar', 'profile_photo', 'photo', 'image', 'profile_photo_path']);

        if (!$avatar && $user) {
            foreach (['avatar', 'profile_photo_path', 'image', 'photo'] as $column) {
                if (isset($user->{$column}) && $this->filledValue($user->{$column})) {
                    $avatar = $user->{$column};
                    break;
                }
            }
        }

        return $this->imageUrl($avatar);
    }


    private function imageUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', 'data:image'])) {
            return $path;
        }

        if (Str::startsWith($path, ['storage/', '/storage/'])) {
            return asset(ltrim($path, '/'));
        }

        return asset('storage/' . ltrim($path, '/'));
    }


    private function valueFrom(?object $row, array $columns, mixed $default = null): mixed
    {
        if (!$row) {
            return $default;
        }

        foreach ($columns as $column) {
            if (property_exists($row, $column) && $row->{$column} !== null && $row->{$column} !== '') {
                return $row->{$column};
            }
        }

        return $default;
    }


    private function filledValue(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        if (is_array($value)) {
            return count($value) > 0;
        }

        return true;
    }


    private function decodeArrayValue(mixed $value): array
    {
        if (!$value) {
            return [];
        }

        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (is_array($decoded)) {
                return $decoded;
            }

            return array_values(array_filter(array_map('trim', explode(',', $value))));
        }

        return [];
    }


    private function setFirstExistingColumn(array &$payload, string $table, array $columns, mixed $value): void
    {
        foreach ($columns as $column) {
            if ($this->columnExists($table, $column)) {
                $payload[$column] = $value;
                return;
            }
        }
    }


    private function validNumber(mixed $value, float $min, float $max): bool
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return false;
        }

        $number = (float) $value;

        return $number >= $min && $number <= $max;
    }


    private function normalizeGender(mixed $value): string
    {
        $value = mb_strtolower(trim((string) $value), 'UTF-8');

        return match ($value) {
            'female', 'woman', 'f', 'أنثى', 'طبيبة' => 'female',
            'male', 'man', 'm', 'ذكر', 'طبيب' => 'male',
            default => 'unknown',
        };
    }


    private function normalizeConsultationType(mixed $value): string
    {
        $value = mb_strtolower(trim((string) $value), 'UTF-8');

        return match ($value) {
            'clinic', 'offline', 'حضوري', 'عيادة' => 'clinic',
            'online', 'remote', 'virtual', 'أونلاين', 'اونلاين' => 'online',
            default => 'online',
        };
    }


    private function textHas(string $text, array $needles): bool
    {
        foreach ($needles as $needle) {
            $needle = mb_strtolower((string) $needle, 'UTF-8');

            if ($needle !== '' && str_contains($text, $needle)) {
                return true;
            }
        }

        return false;
    }


    private function placeholderImage(string $text): string
    {
        $safeText = htmlspecialchars(mb_substr($text, 0, 18, 'UTF-8'), ENT_QUOTES, 'UTF-8');

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="520" height="320" viewBox="0 0 520 320">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="#EAF8F4"/>
      <stop offset="100%" stop-color="#DFF1FA"/>
    </linearGradient>
  </defs>
  <rect width="520" height="320" rx="38" fill="url(#g)"/>
  <circle cx="420" cy="72" r="68" fill="#FFFFFF" opacity=".55"/>
  <circle cx="96" cy="248" r="80" fill="#1D9E75" opacity=".11"/>
  <rect x="135" y="92" width="250" height="136" rx="34" fill="#FFFFFF" opacity=".72"/>
  <text x="260" y="170" text-anchor="middle" font-family="Arial" font-size="26" font-weight="800" fill="#18333B">$safeText</text>
</svg>
SVG;

        return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
    }

        /* ==================================================
       🔥 دوال جديدة لجلب البيانات الحقيقية للـ Home
       ================================================== */

    /**
     * جلب مهام اليوم الحقيقية
     */
    private function realTodayTasks($user): array
    {
        if (!$user || !$this->tableExists('patient_tasks')) {
            return [];
        }

        $today = Carbon::today()->toDateString();

        $query = DB::table('patient_tasks')
            ->where('patient_user_id', $user->id)
            ->whereDate('task_date', $today)
            ->where('status', '!=', 'cancelled')
            ->orderBy('task_time');

        $rows = $query->get();

        if ($rows->isEmpty()) {
            return [];
        }

        return $rows->map(function ($task) {
            $completed = $task->status === 'completed';
            $time = $task->task_time ? Carbon::parse($task->task_time)->format('g:i A') : null;

            return [
                'id' => $task->id,
                'title' => $task->title ?? 'مهمة صحية',
                'description' => $task->description ?? null,
                'completed' => $completed,
                'time' => $time ?: '—',
                'type' => $task->source ?? 'patient',
                'icon' => $this->taskIcon($task->title ?? ''),
                'progress' => $completed ? 100 : 0,
                'progressText' => $completed ? 'مكتملة' : 'بانتظار التنفيذ',
            ];
        })->values()->toArray();
    }

    /**
     * حساب تقدم المهام الحقيقي
     */
    private function realTaskProgress($user): array
    {
        if (!$user || !$this->tableExists('patient_tasks')) {
            return [
                'total' => 0,
                'completed' => 0,
                'percentage' => 0,
                'title' => 'تابعي تقدمك اليوم',
                'text' => 'ابدئي بإضافة مهامك اليومية',
            ];
        }

        $today = Carbon::today()->toDateString();

        $total = DB::table('patient_tasks')
            ->where('patient_user_id', $user->id)
            ->whereDate('task_date', $today)
            ->where('status', '!=', 'cancelled')
            ->count();

        $completed = DB::table('patient_tasks')
            ->where('patient_user_id', $user->id)
            ->whereDate('task_date', $today)
            ->where('status', 'completed')
            ->count();

        $percentage = $total > 0 ? round(($completed / $total) * 100) : 0;

        return [
            'total' => $total,
            'completed' => $completed,
            'percentage' => $percentage,
            'title' => $percentage >= 100 ? 'أحسنت! يومك مكتمل ✅' : 'تابعي تقدمك اليوم',
            'text' => $percentage >= 100 
                ? 'أكملت جميع مهام اليوم!'
                : ($total > 0 ? 'أنتِ في منتصف الطريق، استمري 💪' : 'ابدئي بإضافة مهامك اليومية'),
        ];
    }

    /**
     * جلب بيانات السعرات الحقيقية
     */
    /**
 * جلب بيانات السعرات الحقيقية
 */
private function realCaloriesData($user, $patientProfile): array
{
    $empty = [
        'consumed' => 0,
        'target' => 0,
        'today_calories' => 0,
        'daily_goal' => 2000,
        'last_meal' => null,
        'latest_meal' => null,
        'title' => 'تحليل السعرات بالذكاء الاصطناعي',
        'note' => 'ارفع صورة الوجبة أو اكتب وصفها، وسيظهر تقدير السعرات قبل اعتمادها.',
    ];

    if (!$user || !$this->tableExists('patient_meals')) {
        return $empty;
    }

    $today = Carbon::today()->toDateString();

    // ✅ جلب هدف السعرات - باستخدام العمود الصحيح
    $calorieGoal = null;
    if ($this->tableExists('patient_daily_calorie_goals')) {
        // تحقق من وجود الأعمدة الصحيحة
        $columns = $this->getTableColumns('patient_daily_calorie_goals');
        
        $query = DB::table('patient_daily_calorie_goals');
        
        // استخدام العمود الصحيح
        if (in_array('user_id', $columns)) {
            $query->where('user_id', $user->id);
        } elseif (in_array('patient_id', $columns)) {
            $query->where('patient_id', $patientProfile?->id ?? 0);
        } elseif (in_array('patient_user_id', $columns)) {
            $query->where('patient_user_id', $user->id);
        } else {
            return $empty;
        }
        
        // استخدام اسم العمود الصحيح للتاريخ
        if (in_array('goal_date', $columns)) {
            $query->whereDate('goal_date', $today);
        } elseif (in_array('date', $columns)) {
            $query->whereDate('date', $today);
        } elseif (in_array('created_at', $columns)) {
            $query->whereDate('created_at', $today);
        } else {
            return $empty;
        }
        
        $calorieGoal = $query->first();
    }

    $target = $calorieGoal ? (int) ($calorieGoal->calories ?? $calorieGoal->calories_goal ?? 0) : 2000;

    // ✅ جلب وجبات اليوم - باستخدام العمود الصحيح
    $columns = $this->getTableColumns('patient_meals');
    
    $mealsQuery = DB::table('patient_meals');
    
    if (in_array('user_id', $columns)) {
        $mealsQuery->where('user_id', $user->id);
    } elseif (in_array('patient_id', $columns)) {
        $mealsQuery->where('patient_id', $patientProfile?->id ?? 0);
    } elseif (in_array('patient_user_id', $columns)) {
        $mealsQuery->where('patient_user_id', $user->id);
    } else {
        return $empty;
    }
    
    if (in_array('created_at', $columns)) {
        $mealsQuery->whereDate('created_at', $today);
    } elseif (in_array('meal_date', $columns)) {
        $mealsQuery->whereDate('meal_date', $today);
    } elseif (in_array('date', $columns)) {
        $mealsQuery->whereDate('date', $today);
    } else {
        return $empty;
    }
    
    $meals = $mealsQuery->get();

    // حساب السعرات
    $consumed = 0;
    $lastMeal = null;
    
    foreach ($meals as $meal) {
        $calories = (int) ($meal->calories ?? $meal->calories_consumed ?? $meal->total_calories ?? 0);
        $consumed += $calories;
        
        if (!$lastMeal || $meal->created_at > $lastMeal->created_at) {
            $lastMeal = $meal;
        }
    }

    return [
        'consumed' => (int) $consumed,
        'target' => $target,
        'today_calories' => (int) $consumed,
        'daily_goal' => $target,
        'last_meal' => $lastMeal ? ($lastMeal->meal_name ?? $lastMeal->title ?? 'وجبة') : null,
        'latest_meal' => $lastMeal ? [
            'name' => $lastMeal->meal_name ?? $lastMeal->title ?? 'وجبة',
            'title' => $lastMeal->meal_name ?? $lastMeal->title ?? 'وجبة',
            'calories' => (int) ($lastMeal->calories ?? $lastMeal->calories_consumed ?? 0),
        ] : null,
        'title' => 'تحليل السعرات بالذكاء الاصطناعي',
        'note' => $consumed > 0 ? 'تم تسجيل ' . $consumed . ' سعرة اليوم' : 'ارفع صورة الوجبة أو اكتب وصفها',
    ];
}

/**
 * الحصول على أسماء الأعمدة في جدول
 */
private function getTableColumns(string $table): array
{
    if (!$this->tableExists($table)) {
        return [];
    }
    
    try {
        return Schema::getColumnListing($table);
    } catch (\Throwable $e) {
        return [];
    }
}

    /**
     * جلب المقالات الموصى بها
     */
    private function realRecommendedArticles(): array
    {
        if (!$this->tableExists('articles')) {
            return [];
        }

        $query = DB::table('articles');

        if ($this->columnExists('articles', 'is_published')) {
            $query->where('is_published', true);
        }

        if ($this->columnExists('articles', 'status')) {
            $query->where('status', 'published');
        }

        if ($this->columnExists('articles', 'published_at')) {
            $query->where('published_at', '<=', now());
        }

        if ($this->columnExists('articles', 'is_featured')) {
            $query->orderByDesc('is_featured');
        }

        if ($this->columnExists('articles', 'published_at')) {
            $query->orderByDesc('published_at');
        }

        if ($this->columnExists('articles', 'created_at')) {
            $query->orderByDesc('created_at');
        }

        $rows = $query->limit(3)->get();

        if ($rows->isEmpty()) {
            return [];
        }

        return $rows->map(function ($article) {
            $title = $article->title ?? 'مقال صحي';
            $category = $article->category ?? 'صحة';
            $readTime = $article->reading_time_label ?? $article->read_time ?? '4 دقائق قراءة';

            return [
                'id' => $article->id ?? null,
                'title' => $title,
                'slug' => $article->slug ?? null,
                'tag' => $category,
                'read_time' => $readTime,
                'image' => $article->cover_image_url ?? $article->image_url ?? $this->placeholderImage($title),
                'url' => !empty($article->slug) ? route('patient.articles.show', $article->slug) : route('patient.articles'),
            ];
        })->toArray();
    }

}


