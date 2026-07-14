<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
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

class PatientHomeController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('patient.home');
    }

    public function home(): View
    {
        return view('patient.home', $this->dashboardData([
            'pageTitle' => 'الرئيسية',
            'activePage' => 'home',
        ]));
    }

    public function journey(): View
    {
        return view('patient.journey', $this->dashboardData([
            'pageTitle' => 'رحلتي اليوم',
            'activePage' => 'journey',
        ]));
    }

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

    public function profile(): View
    {
        return view('patient.profile', $this->dashboardData([
            'pageTitle' => 'ملفي الصحي',
            'activePage' => 'profile',
        ]));
    }

    public function recommendedDoctorsPage(): View
    {
        return view('patient.recommended-doctors', $this->dashboardData([
            'pageTitle' => 'الأطباء المناسبون',
            'activePage' => 'profile',
        ]));
    }


    public function myDoctor(): View
{
    $user = auth()->user();

    $data = $this->dashboardData([
        'pageTitle' => 'طبيبي',
        'activePage' => 'my-doctor',
    ]);

    $profile = $this->patientProfile($user?->id);
    $selectedDoctor = $this->selectedDoctor($profile);

    $state = 'no_doctor';

    if (! empty($selectedDoctor['is_selected'])) {
        $requestStatus = $selectedDoctor['request_status'] ?? null;

        $state = match ($requestStatus) {
            'approved' => 'approved',
            'rejected', 'declined' => 'rejected',
            default => 'pending',
        };
    }

    $doctor = $selectedDoctor;
    $doctorArticles = [];

    if (! empty($selectedDoctor['id']) && $this->tableExists('doctor_profiles') && $this->tableExists('users')) {
        $select = [
            'doctor_profiles.id',
            'doctor_profiles.user_id',
            'users.name as user_name',
            'users.email as user_email',
        ];

        foreach (['avatar', 'profile_photo_path', 'image', 'photo', 'gender'] as $column) {
            if ($this->columnExists('users', $column)) {
                $select[] = 'users.' . $column . ' as user_' . $column;
            }
        }

        foreach ([
            'bio',
            'about',
            'description',
            'specialty',
            'specialization',
            'years_experience',
            'experience_years',
            'photo_path',
            'avatar',
            'image',
            'consultation_type',
            'gender',
            'doctor_gender',
        ] as $column) {
            if ($this->columnExists('doctor_profiles', $column)) {
                $select[] = 'doctor_profiles.' . $column . ' as doctor_' . $column;
            }
        }

        $doctorRow = DB::table('doctor_profiles')
            ->join('users', 'users.id', '=', 'doctor_profiles.user_id')
            ->where('doctor_profiles.id', $selectedDoctor['id'])
            ->select($select)
            ->first();

        if ($doctorRow) {
            $specialty = $this->doctorSpecialty((int) $doctorRow->id);

            $gender = $this->normalizeGender(
                $this->valueFrom($doctorRow, ['doctor_doctor_gender', 'doctor_gender', 'user_gender'], 'unknown')
            );

            $consultationKey = $this->normalizeConsultationType(
                $this->valueFrom($doctorRow, ['doctor_consultation_type'], 'online')
            );

            $photo = $this->valueFrom($doctorRow, [
                'doctor_photo_path',
                'doctor_avatar',
                'doctor_image',
                'user_avatar',
                'user_profile_photo_path',
                'user_image',
                'user_photo',
            ]);

            $experience = (int) $this->valueFrom($doctorRow, [
                'doctor_years_experience',
                'doctor_experience_years',
            ], 5);

            $bio = $this->valueFrom($doctorRow, [
                'doctor_bio',
                'doctor_about',
                'doctor_description',
            ], 'طبيب مختص يساعدك على بناء متابعة صحية مناسبة لحالتك وهدفك.');

            $review = $this->doctorReviewSummary((int) $doctorRow->id);

            $preferredGender = (string) $this->valueFrom($profile, ['preferred_doctor_gender'], 'any');
            $preferredType = (string) $this->valueFrom($profile, ['preferred_consultation_type'], 'any');

            $preferredGender = in_array($preferredGender, ['female', 'male'], true) ? $preferredGender : 'any';
            $preferredType = in_array($preferredType, ['online', 'clinic'], true) ? $preferredType : 'any';

            $match = $this->doctorMatch(
                $profile,
                $specialty,
                $doctorRow,
                $gender,
                $consultationKey,
                $preferredGender,
                $preferredType,
                $experience
            );

            $hasReviews = (bool) ($review['has_reviews'] ?? ((int) ($review['count'] ?? 0) > 0));

            $doctorArticles = $this->doctorArticles((int) $doctorRow->id, (int) $doctorRow->user_id);

            $doctor = array_merge($selectedDoctor, [
                'id' => (int) $doctorRow->id,
                'user_id' => (int) $doctorRow->user_id,
                'name' => 'د. ' . ($doctorRow->user_name ?: 'طبيب اتزان'),
                'email' => $doctorRow->user_email ?? null,
                'specialty' => $specialty,
                'avatar' => $this->imageUrl($photo) ?: $this->placeholderImage($doctorRow->user_name ?: 'طبيب'),
                'bio' => $bio,
                'gender' => $gender,
                'gender_label' => $gender === 'female' ? 'طبيبة' : ($gender === 'male' ? 'طبيب' : 'غير محدد'),
                'consultation_key' => $consultationKey,
                'consultation_type' => $consultationKey === 'clinic' ? 'حضوري' : 'أونلاين',
                'experience' => $experience,
                'rating' => $review['average'] ?? null,
                'reviews_count' => $review['count'] ?? 0,
                'has_reviews' => $hasReviews,
                'match_score' => $match['score'],
                'match_reason' => $match['reason'],
                'badges' => $match['badges'],
            ]);
        }
    }

    $myReview = null;

        if (! empty($doctor['id']) && $this->tableExists('doctor_reviews')) {
            $doctorReviewColumn = $this->firstExistingColumn('doctor_reviews', [
                'doctor_profile_id',
                'doctor_id',
            ]);

            $patientReviewColumn = $this->firstExistingColumn('doctor_reviews', [
                'patient_user_id',
                'user_id',
                'patient_id',
            ]);

            if ($doctorReviewColumn && $patientReviewColumn) {
                $patientReviewValue = (int) $user?->id;

                $myReview = DB::table('doctor_reviews')
                    ->where($doctorReviewColumn, $doctor['id'])
                    ->where($patientReviewColumn, $patientReviewValue)
                    ->first();
                }
        }


    $data['myDoctorPage'] = [
        'state' => $state,
        'doctor' => $doctor,
        'articles' => $doctorArticles,
        'nextAppointment' => $data['nextAppointment'] ?? null,
        'can_book' => $state === 'approved',
        'can_message' => $state === 'approved',
        'can_review' => $state === 'approved',
        'my_review' => $myReview,
    ];

    return view('patient.my-doctor', $data);
}


    public function doctorDetails(int $doctorProfile): View
{
    $user = auth()->user();

    $data = $this->dashboardData([
        'pageTitle' => 'تفاصيل الطبيب',
        'activePage' => 'profile',
    ]);

    $patientProfile = $this->patientProfile($user?->id);

    if (! $this->tableExists('doctor_profiles') || ! $this->tableExists('users')) {
        abort(404);
    }

    $select = [
        'doctor_profiles.id',
        'doctor_profiles.user_id',
        'users.name as user_name',
        'users.email as user_email',
    ];

    foreach (['avatar', 'profile_photo_path', 'image', 'photo', 'gender'] as $column) {
        if ($this->columnExists('users', $column)) {
            $select[] = 'users.' . $column . ' as user_' . $column;
        }
    }

    foreach ([
        'bio',
        'about',
        'description',
        'specialty',
        'specialization',
        'years_experience',
        'experience_years',
        'photo_path',
        'avatar',
        'image',
        'consultation_type',
        'gender',
        'doctor_gender',
    ] as $column) {
        if ($this->columnExists('doctor_profiles', $column)) {
            $select[] = 'doctor_profiles.' . $column . ' as doctor_' . $column;
        }
    }

    $doctorRow = DB::table('doctor_profiles')
        ->join('users', 'users.id', '=', 'doctor_profiles.user_id')
        ->where('doctor_profiles.id', $doctorProfile)
        ->select($select)
        ->first();

    abort_if(! $doctorRow, 404);

    $specialty = $this->doctorSpecialty((int) $doctorRow->id);

    $gender = $this->normalizeGender(
        $this->valueFrom($doctorRow, ['doctor_doctor_gender', 'doctor_gender', 'user_gender'], 'unknown')
    );

    $consultationKey = $this->normalizeConsultationType(
        $this->valueFrom($doctorRow, ['doctor_consultation_type'], 'online')
    );

    $photo = $this->valueFrom($doctorRow, [
        'doctor_photo_path',
        'doctor_avatar',
        'doctor_image',
        'user_avatar',
        'user_profile_photo_path',
        'user_image',
        'user_photo',
    ]);

    $experience = (int) $this->valueFrom($doctorRow, [
        'doctor_years_experience',
        'doctor_experience_years',
    ], 5);

    $bio = $this->valueFrom($doctorRow, [
        'doctor_bio',
        'doctor_about',
        'doctor_description',
    ], 'طبيب مختص يساعدك على بناء متابعة صحية مناسبة لحالتك وهدفك.');

    $review = $this->doctorReviewSummary((int) $doctorRow->id);

    $preferredGender = (string) $this->valueFrom($patientProfile, ['preferred_doctor_gender'], 'any');
    $preferredType = (string) $this->valueFrom($patientProfile, ['preferred_consultation_type'], 'any');

    $preferredGender = in_array($preferredGender, ['female', 'male'], true) ? $preferredGender : 'any';
    $preferredType = in_array($preferredType, ['online', 'clinic'], true) ? $preferredType : 'any';

    $match = $this->doctorMatch(
        $patientProfile,
        $specialty,
        $doctorRow,
        $gender,
        $consultationKey,
        $preferredGender,
        $preferredType,
        $experience
    );

    $currentDoctor = $this->selectedDoctor($patientProfile);

    $isCurrentDoctor = ! empty($currentDoctor['id'])
        && (int) $currentDoctor['id'] === (int) $doctorRow->id;

    $doctorRequestStatus = $currentDoctor['request_status'] ?? null;

    $doctor = [
        'id' => (int) $doctorRow->id,
        'user_id' => (int) $doctorRow->user_id,
        'name' => 'د. ' . ($doctorRow->user_name ?: 'طبيب اتزان'),
        'email' => $doctorRow->user_email ?? null,
        'specialty' => $specialty,
        'avatar' => $this->imageUrl($photo) ?: $this->placeholderImage($doctorRow->user_name ?: 'طبيب'),
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
        'articles' => $this->doctorArticles((int) $doctorRow->id, (int) $doctorRow->user_id),
        'is_current_doctor' => $isCurrentDoctor,
        'request_status' => $isCurrentDoctor ? $doctorRequestStatus : null,
    ];

    $data['doctorDetails'] = $doctor;

    return view('patient.doctor-details', $data);
}


    public function storeDoctorReview(Request $request, int $doctorProfile): RedirectResponse
    {
        $user = auth()->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $profile = $this->patientProfile($user?->id);
        $selectedDoctor = $this->selectedDoctor($profile);

        $isSelectedDoctor = ! empty($selectedDoctor['is_selected'])
            && (int) ($selectedDoctor['id'] ?? 0) === (int) $doctorProfile;

        $isApproved = ($selectedDoctor['request_status'] ?? null) === 'approved';

        if (! $isSelectedDoctor || ! $isApproved) {
            return back()->with('error', 'لا يمكنك تقييم الطبيب قبل اعتماد المتابعة.');
        }

        if (! $this->tableExists('doctor_reviews')) {
            return back()->with('error', 'جدول تقييمات الأطباء غير موجود.');
        }

        $doctorColumn = $this->firstExistingColumn('doctor_reviews', [
            'doctor_profile_id',
            'doctor_id',
        ]);

        $patientColumn = $this->firstExistingColumn('doctor_reviews', [
            'patient_user_id',
            'user_id',
            'patient_id',
        ]);

        $ratingColumn = $this->firstExistingColumn('doctor_reviews', [
            'rating',
            'stars',
            'rate',
        ]);

        $commentColumn = $this->firstExistingColumn('doctor_reviews', [
            'comment',
            'review',
            'body',
        ]);

        if (! $doctorColumn || ! $patientColumn || ! $ratingColumn) {
            return back()->with('error', 'أعمدة جدول تقييمات الأطباء غير مكتملة.');
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $patientReviewValue = (int) $user->id;

        $lookup = [
            $doctorColumn => $doctorProfile,
            $patientColumn => $patientReviewValue,
        ];

        $payload = [
            $ratingColumn => (int) $validated['rating'],
        ];

        if ($this->columnExists('doctor_reviews', 'is_recommended')) {
            $payload['is_recommended'] = $request->boolean('is_recommended');
        }

        if ($commentColumn) {
            $payload[$commentColumn] = $validated['comment'] ?? null;
        }

        if ($this->columnExists('doctor_reviews', 'status')) {
            $payload['status'] = 'published';
        }

        if ($this->columnExists('doctor_reviews', 'updated_at')) {
            $payload['updated_at'] = now();
        }

        if ($this->columnExists('doctor_reviews', 'created_at')) {
            $payload['created_at'] = now();
        }

        DB::table('doctor_reviews')->updateOrInsert($lookup, $payload);

        return back()->with('success', 'تم حفظ تقييمك للطبيب بنجاح.');
    }


    public function calories(): View
    {
        $user = auth()->user();

        $data = $this->dashboardData([
            'pageTitle' => 'تحليل الوجبات',
            'activePage' => 'calories',
        ]);

        $selectedDate = request('date')
            ? Carbon::parse(request('date'))->toDateString()
            : now()->toDateString();

        $profile = $this->patientProfile($user?->id);

        /*
        |--------------------------------------------------------------------------
        | 1) وجبات التاريخ المختار فقط
        |--------------------------------------------------------------------------
        | هذه هي التي تدخل في مجموع السعرات الظاهر في كرت اليوم.
        */
        $todayMeals = PatientMeal::query()
            ->where('user_id', $user?->id)
            ->whereDate('meal_date', $selectedDate)
            ->where('status', 'confirmed')
            ->latest('id')
            ->get();

        $consumed = (int) $todayMeals->sum('calories');
        $protein = (int) $todayMeals->sum('protein');
        $carbs = (int) $todayMeals->sum('carbs');
        $fat = (int) $todayMeals->sum('fat');

        /*
        |--------------------------------------------------------------------------
        | 2) آخر الوجبات المحفوظة من كل الأيام
        |--------------------------------------------------------------------------
        | لا تدخل في مجموع اليوم، لكنها تظهر كسجل سريع.
        */
        $recentAnalyses = PatientMeal::query()
            ->where('user_id', $user?->id)
            ->where('status', 'confirmed')
            ->latest('meal_date')
            ->latest('id')
            ->limit(8)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 3) هدف السعرات
        |--------------------------------------------------------------------------
        | لا يوجد 2000 افتراضي.
        | الهدف يظهر فقط إذا كان موجودًا في ملف المريض.
        */
        $dailyGoal = $this->dailyCalorieGoalForDate($user?->id, $profile, $selectedDate);

        $target = $dailyGoal['target'];
        $goalStatus = $dailyGoal['status'];

        $proteinTarget = $dailyGoal['protein_target'];
        $carbsTarget = $dailyGoal['carbs_target'];
        $fatTarget = $dailyGoal['fat_target'];

        $data['selectedDate'] = $selectedDate;

        $data['caloriesSummary'] = [
            'target' => $target,
            'goal_status' => $goalStatus,

            'consumed' => $consumed,
            'remaining' => $target ? max($target - $consumed, 0) : null,
            'progress' => $target ? min(100, (int) round(($consumed / $target) * 100)) : 0,

            'protein' => $protein,
            'carbs' => $carbs,
            'fat' => $fat,

            // مؤقتًا، إلى أن نعمل أهداف الماكروز من الطبيب أو من الملف الصحي
            'protein_target' => $proteinTarget,
            'carbs_target' => $carbsTarget,
            'fat_target' => $fatTarget,
            'goal_note' => $dailyGoal['note'],
            'goal_date' => $selectedDate,
        ];

        $data['todayMeals'] = $todayMeals;
        $data['recentAnalyses'] = $recentAnalyses;

        $weekStart = Carbon::parse($selectedDate)
        ->copy()
        ->startOfWeek(\Carbon\CarbonInterface::SATURDAY);

        $data['weeklyCalories'] = collect(range(0, 6))->map(function ($offset) use ($user, $profile, $weekStart) {
            $date = $weekStart->copy()->addDays($offset)->toDateString();

            $dailyGoal = $this->dailyCalorieGoalForDate($user?->id, $profile, $date);

            return [
                'date' => $date,
                'label' => Carbon::parse($date)->locale('ar')->translatedFormat('D'),
                'calories' => PatientMeal::query()
                    ->where('user_id', $user?->id)
                    ->where('status', 'confirmed')
                    ->whereDate('meal_date', $date)
                    ->sum('calories'),
                'target' => $dailyGoal['target'],
                'goal_status' => $dailyGoal['status'],
            ];
        })->toArray();

        $data['aiMealDraft'] = session('ai_meal_draft');

        $weightLogs = collect();
        if ($this->tableExists('patient_weight_logs')) {
            $weightLogs = DB::table('patient_weight_logs')
                ->where('user_id', $user?->id)
                ->orderBy('logged_date')
                ->get();

            // لو ما في أي سجل وزن بعد، بس عند المريض وزن أولي من وقت التسجيل،
            // نستورده تلقائياً كأول نقطة بالسجل — حتى ما يبين الجدول فاضي بالغلط.
            if ($weightLogs->isEmpty() && $profile) {
                $initialWeight = $this->valueFrom($profile, ['weight', 'weight_kg', 'current_weight']);

                if ($initialWeight && (float) $initialWeight > 0) {
                    DB::table('patient_weight_logs')->insert([
                        'user_id' => $user->id,
                        'patient_profile_id' => $profile->id ?? null,
                        'doctor_profile_id' => $profile->doctor_profile_id ?? null,
                        'weight_kg' => $initialWeight,
                        'logged_date' => $profile->created_at ?? now()->toDateString(),
                        'source' => 'profile_initial',
                        'note' => 'الوزن الأولي وقت إكمال الملف الصحي',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $weightLogs = DB::table('patient_weight_logs')
                        ->where('user_id', $user->id)
                        ->orderBy('logged_date')
                        ->get();
                }
            }
        }
        $data['weightLogs'] = $weightLogs;

        return view('patient.calories', $data);
    }
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

    public function analyzeMeal(Request $request, AiMealAnalysisService $mealAnalysisService): RedirectResponse
    {
        $validated = $request->validate([
            'meal_type' => ['required', 'string', 'max:30'],
            'meal_date' => ['nullable', 'date'],

            // نقبل الاسمين عشان لو البلايد يستخدم meal_text أو description
            'meal_text' => ['nullable', 'string', 'max:2000'],
            'description' => ['nullable', 'string', 'max:2000'],

            // نقبل الاسمين عشان لو البلايد يستخدم meal_photo أو meal_image
            'meal_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'meal_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $user = auth()->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $description = trim((string) (
            $validated['meal_text']
            ?? $validated['description']
            ?? ''
        ));

        $photoInputName = $request->hasFile('meal_photo')
            ? 'meal_photo'
            : ($request->hasFile('meal_image') ? 'meal_image' : null);

        if ($description === '' && ! $photoInputName) {
            return back()
                ->withInput()
                ->with('error', 'اكتب وصف الوجبة أو ارفع صورة قبل التحليل.');
        }

        if ($description === '' && $photoInputName) {
            return back()
                ->withInput()
                ->with('error', 'حاليًا التحليل المجاني يحتاج وصفًا نصيًا مع الصورة. اكتب مكونات الوجبة باختصار.');
        }

        $imagePath = null;
        $imageUrl = null;

        if ($photoInputName) {
            $imagePath = $request->file($photoInputName)->store('patient-meals', 'public');
            $imageUrl = asset('storage/' . $imagePath);
        }

        $mealDate = ! empty($validated['meal_date'])
            ? Carbon::parse($validated['meal_date'])->toDateString()
            : now()->toDateString();

        $result = $mealAnalysisService->analyzeTextMeal(
            description: $description,
            mealType: $validated['meal_type']
        );

        $result['meal_date'] = $mealDate;
        $result['image_path'] = $imagePath;
        $result['image_url'] = $imageUrl;

        session(['ai_meal_draft' => $result]);

        return redirect()
            ->route('patient.calories', ['date' => $mealDate])
            ->with('success', 'تم تحليل الوجبة. راجع النتيجة ثم اضغط اعتماد وحفظ.');
    }

    public function confirmMeal(Request $request): RedirectResponse
    {
        $draft = session('ai_meal_draft');

        if (! $draft) {
            return redirect()
                ->route('patient.calories')
                ->with('error', 'لا توجد نتيجة تحليل لاعتمادها.');
        }

        $validated = $request->validate([
            'meal_name' => ['required', 'string', 'max:255'],
            'calories' => ['required', 'integer', 'min:0', 'max:5000'],
            'protein' => ['nullable', 'integer', 'min:0', 'max:400'],
            'carbs' => ['nullable', 'integer', 'min:0', 'max:700'],
            'fat' => ['nullable', 'integer', 'min:0', 'max:400'],
            'patient_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = auth()->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $profile = $this->patientProfile($user->id);
        $doctor = $this->selectedDoctor($profile);

        PatientMeal::create([
            'user_id' => $user->id,
            'patient_profile_id' => $profile?->id,
            'doctor_profile_id' => $doctor['id'] ?? null,
            'doctor_user_id' => $doctor['user_id'] ?? null,

            'meal_date' => $draft['meal_date'] ?? now()->toDateString(),
            'meal_type' => $draft['meal_type'] ?? 'lunch',

            'meal_name' => $validated['meal_name'],
            'description' => $draft['description'] ?? null,
            'image_path' => $draft['image_path'] ?? null,

            'calories' => (int) $validated['calories'],
            'protein' => (int) ($validated['protein'] ?? 0),
            'carbs' => (int) ($validated['carbs'] ?? 0),
            'fat' => (int) ($validated['fat'] ?? 0),
            'confidence' => (int) ($draft['confidence'] ?? 0),

            'ai_notes' => $draft['ai_notes'] ?? null,
            'patient_note' => $validated['patient_note'] ?? null,
            'ai_response' => $draft,

            'source' => 'ai',
            'status' => 'confirmed',
        ]);

        session()->forget('ai_meal_draft');

        return redirect()
            ->route('patient.calories', ['date' => $draft['meal_date'] ?? now()->toDateString()])
            ->with('success', 'تم حفظ الوجبة في سجل اليوم.');
    }

    public function destroyMeal(PatientMeal $meal): RedirectResponse
    {
        abort_if((int) $meal->user_id !== (int) auth()->id(), 403);

        $date = $meal->meal_date?->toDateString() ?? now()->toDateString();

        $meal->delete();

        return redirect()
            ->route('patient.calories', ['date' => $date])
            ->with('success', 'تم حذف الوجبة من سجل اليوم.');
    }

    public function articles(Request $request): View
    {
        $data = $this->dashboardData([
            'pageTitle' => 'المقالات',
            'activePage' => 'articles',
        ]);

        $search = trim((string) $request->query('q', ''));
        $filter = (string) $request->query('filter', 'recommended');
        $selectedCategory = (string) $request->query('category', 'all');

        if ($filter === 'admin') {
            $filter = 'etzan';
        }

        if (! in_array($filter, ['recommended', 'all', 'doctor', 'etzan', 'research', 'tips', 'facts', 'ideas', 'wisdom', 'motivation'], true)) {
            $filter = 'recommended';
        }

        $doctor = $data['doctor'] ?? [];
        $doctorProfileId = !empty($doctor['id']) ? (int) $doctor['id'] : null;
        $doctorUserId = !empty($doctor['user_id']) ? (int) $doctor['user_id'] : $this->doctorUserIdFromProfile($doctorProfileId);
        $hasSelectedDoctor = !empty($doctor['is_selected']);

        $articleCategories = $this->patientArticleCategories();
        $doctorArticles = collect();
        $patientArticles = new LengthAwarePaginator([], 0, 9, 1, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);
        $featuredArticle = null;
        $doctorArticlesCount = 0;
        $etzanArticlesCount = 0;
        $allVisibleArticlesCount = 0;
        $recommendedArticlesCount = 0;
        $researchArticlesCount = 0;
        $tipsArticlesCount = 0;
        $factsArticlesCount = 0;
        $ideasArticlesCount = 0;
        $wisdomArticlesCount = 0;
        $motivationArticlesCount = 0;

        if ($this->tableExists('articles')) {
            $baseQuery = $this->patientVisibleArticlesQuery();

            if ($search !== '') {
                $baseQuery->where(function (Builder $query) use ($search) {
                    $query->where('title', 'like', '%' . $search . '%');

                    foreach (['excerpt', 'content', 'author_name'] as $column) {
                        if ($this->columnExists('articles', $column)) {
                            $query->orWhere($column, 'like', '%' . $search . '%');
                        }
                    }

                    if ($this->tableExists('article_categories')) {
                        $query->orWhereHas('category', function (Builder $categoryQuery) use ($search) {
                            $categoryQuery->where('name', 'like', '%' . $search . '%');
                        });
                    }

                    if ($this->tableExists('specialties')) {
                        $query->orWhereHas('specialty', function (Builder $specialtyQuery) use ($search) {
                            $specialtyQuery->where('name', 'like', '%' . $search . '%');
                        });
                    }
                });
            }

            if ($selectedCategory !== 'all' && $selectedCategory !== '') {
                if ($this->columnExists('articles', 'article_category_id')) {
                    $baseQuery->where('article_category_id', $selectedCategory);
                } elseif ($this->columnExists('articles', 'specialty_id')) {
                    $baseQuery->where('specialty_id', $selectedCategory);
                }
            }

            if ($filter === 'recommended') {
                $this->applyPatientRecommendedArticleScope($baseQuery, $data, $doctorProfileId, $doctorUserId);
            } elseif ($filter === 'doctor') {
                if ($doctorProfileId || $doctorUserId) {
                    $this->applyDoctorArticleScope($baseQuery, $doctorProfileId, $doctorUserId);
                } else {
                    $baseQuery->whereRaw('1 = 0');
                }
            } elseif ($filter === 'etzan') {
                $this->applyEtzanArticleScope($baseQuery, $doctorProfileId, $doctorUserId);
            } elseif ($filter === 'research') {
                $this->applyArticleTypeScope($baseQuery, ['research_summary']);
            } elseif ($filter === 'tips') {
                $this->applyArticleTypeScope($baseQuery, ['quick_tip']);
            } elseif ($filter === 'facts') {
                $this->applyArticleTypeScope($baseQuery, ['general_info']);
            } elseif ($filter === 'ideas') {
                $this->applyArticleTypeScope($baseQuery, ['wellness_idea']);
            } elseif ($filter === 'wisdom') {
                $this->applyArticleTypeScope($baseQuery, ['health_wisdom']);
            } elseif ($filter === 'motivation') {
                $this->applyArticleTypeScope($baseQuery, ['motivational_quote']);
            }

            $patientArticles = $baseQuery
                ->paginate(9)
                ->withQueryString();

            if ($doctorProfileId || $doctorUserId) {
                $doctorCounterQuery = $this->patientVisibleArticlesQuery();
                $this->applyDoctorArticleScope($doctorCounterQuery, $doctorProfileId, $doctorUserId);
                $doctorArticlesCount = (int) $doctorCounterQuery->count();

                $doctorQuery = $this->patientVisibleArticlesQuery();
                $this->applyDoctorArticleScope($doctorQuery, $doctorProfileId, $doctorUserId);
                $doctorArticles = $doctorQuery->limit(4)->get();
            }

            $etzanCounterQuery = $this->patientVisibleArticlesQuery();
            $this->applyEtzanArticleScope($etzanCounterQuery, $doctorProfileId, $doctorUserId);
            $etzanArticlesCount = (int) $etzanCounterQuery->count();

            $allVisibleArticlesCount = (int) $this->patientVisibleArticlesQuery()->count();

            $recommendedCounterQuery = $this->patientVisibleArticlesQuery();
            $this->applyPatientRecommendedArticleScope($recommendedCounterQuery, $data, $doctorProfileId, $doctorUserId);
            $recommendedArticlesCount = (int) $recommendedCounterQuery->count();

            $researchCounterQuery = $this->patientVisibleArticlesQuery();
            $this->applyArticleTypeScope($researchCounterQuery, ['research_summary']);
            $researchArticlesCount = (int) $researchCounterQuery->count();

            $tipsCounterQuery = $this->patientVisibleArticlesQuery();
            $this->applyArticleTypeScope($tipsCounterQuery, ['quick_tip']);
            $tipsArticlesCount = (int) $tipsCounterQuery->count();

            $factsCounterQuery = $this->patientVisibleArticlesQuery();
            $this->applyArticleTypeScope($factsCounterQuery, ['general_info']);
            $factsArticlesCount = (int) $factsCounterQuery->count();

            $ideasCounterQuery = $this->patientVisibleArticlesQuery();
            $this->applyArticleTypeScope($ideasCounterQuery, ['wellness_idea']);
            $ideasArticlesCount = (int) $ideasCounterQuery->count();

            $wisdomCounterQuery = $this->patientVisibleArticlesQuery();
            $this->applyArticleTypeScope($wisdomCounterQuery, ['health_wisdom']);
            $wisdomArticlesCount = (int) $wisdomCounterQuery->count();

            $motivationCounterQuery = $this->patientVisibleArticlesQuery();
            $this->applyArticleTypeScope($motivationCounterQuery, ['motivational_quote']);
            $motivationArticlesCount = (int) $motivationCounterQuery->count();

            $featuredQuery = $this->patientVisibleArticlesQuery();

            if ($this->columnExists('articles', 'is_featured')) {
                $featuredQuery->where('is_featured', true);
            }

            $featuredArticle = $featuredQuery->first() ?: $this->patientVisibleArticlesQuery()->first();
        }

        $hasActiveArticleFilter = $search !== '' || ! in_array($filter, ['recommended', 'all'], true) || ($selectedCategory !== 'all' && $selectedCategory !== '');

        $articleResultsTitle = match ($filter) {
            'recommended' => 'محتوى مناسب لحالتك',
            'doctor' => 'مقالات طبيبك',
            'etzan' => 'مكتبة اتزان الصحية',
            'research' => 'ملخصات بحثية مبسطة',
            'tips' => 'نصائح سريعة',
            'facts' => 'معلومات صحية عامة',
            'ideas' => 'أفكار صحية يومية',
            'wisdom' => 'حكمة اليوم',
            'motivation' => 'رسائل تحفيزية',
            default => $search !== '' ? 'نتائج البحث' : 'مقالات صحية مختارة لك',
        };

        if ($selectedCategory !== 'all' && $selectedCategory !== '') {
            $selectedCategoryName = optional($articleCategories->firstWhere('id', (int) $selectedCategory))->name;
            if ($selectedCategoryName) {
                $articleResultsTitle = 'مقالات ' . $selectedCategoryName;
            }
        }

        $articleResultsSubtitle = $search !== ''
            ? 'نعرض فقط المقالات الصحية المطابقة لبحثك داخل لوحة المريض.'
            : match ($filter) {
                'recommended' => 'نعرض محتوى مناسبًا لهدفك الصحي وبيانات ملفك مثل الوزن، النشاط، النوم، الماء والحالات الصحية عند توفرها.',
                'doctor' => 'هذه المقالات منشورة من طبيب المتابعة المختار عند توفرها.',
                'etzan' => 'مقالات عامة من فريق اتزان والإدارة، بدون المقالات التقنية أو التجريبية.',
                'research' => 'ملخصات بحثية مبسطة للمريض، وتحتاج دائمًا لمصدر ومراجعة قبل النشر.',
                'tips' => 'نصائح قصيرة قابلة للتطبيق اليوم.',
                'facts' => 'معلومات صحية عامة بلغة بسيطة.',
                'ideas' => 'أفكار صغيرة تساعدك تبني عادة صحية واحدة في اليوم.',
                'wisdom' => 'حكم صحية قصيرة من اتزان، بدون نسبتها لأشخاص أو علماء إلا بوجود مصدر واضح.',
                'motivation' => 'رسائل تحفيزية قصيرة تساعدك على الالتزام اليومي بدون مبالغة أو وعود.',
                default => 'مقالات صحية فقط؛ المقالات التقنية مثل Back-end و Laravel لا تظهر هنا.',
            };

        return view('patient.articles', array_merge($data, [
            'patientArticles' => $patientArticles,
            'patientArticleCategories' => $articleCategories,
            'doctorArticleHighlights' => $doctorArticles,
            'featuredPatientArticle' => $featuredArticle,
            'articleSearch' => $search,
            'articleFilter' => $filter,
            'selectedArticleCategory' => $selectedCategory,
            'hasActiveArticleFilter' => $hasActiveArticleFilter,
            'articleResultsTitle' => $articleResultsTitle,
            'articleResultsSubtitle' => $articleResultsSubtitle,
            'doctorArticlesCount' => $doctorArticlesCount,
            'etzanArticlesCount' => $etzanArticlesCount,
            'allVisibleArticlesCount' => $allVisibleArticlesCount,
            'recommendedArticlesCount' => $recommendedArticlesCount,
            'researchArticlesCount' => $researchArticlesCount,
            'tipsArticlesCount' => $tipsArticlesCount,
            'factsArticlesCount' => $factsArticlesCount,
            'ideasArticlesCount' => $ideasArticlesCount,
            'wisdomArticlesCount' => $wisdomArticlesCount,
            'motivationArticlesCount' => $motivationArticlesCount,
            'articlesDoctor' => [
                'id' => $doctorProfileId,
                'user_id' => $doctorUserId,
                'name' => $doctor['name'] ?? 'طبيب المتابعة',
                'specialty' => $doctor['specialty'] ?? null,
                'avatar' => $doctor['avatar'] ?? null,
                'is_selected' => $hasSelectedDoctor,
            ],
        ]));
    }

    public function articleDetails(string $slug): View
    {
        if (! $this->tableExists('articles')) {
            abort(404);
        }

        $article = $this->patientVisibleArticlesQuery()
            ->where('slug', $slug)
            ->firstOrFail();

        $data = $this->dashboardData([
            'pageTitle' => $article->title ?? 'تفاصيل المقال',
            'activePage' => 'articles',
        ]);

        $doctor = $data['doctor'] ?? [];
        $doctorProfileId = !empty($doctor['id']) ? (int) $doctor['id'] : null;
        $doctorUserId = !empty($doctor['user_id']) ? (int) $doctor['user_id'] : $this->doctorUserIdFromProfile($doctorProfileId);

        $relatedQuery = $this->patientVisibleArticlesQuery()
            ->where('articles.id', '!=', $article->id);

        if ($this->columnExists('articles', 'article_category_id') && !empty($article->article_category_id)) {
            $relatedQuery->where('article_category_id', $article->article_category_id);
        } elseif ($this->columnExists('articles', 'specialty_id') && !empty($article->specialty_id)) {
            $relatedQuery->where('specialty_id', $article->specialty_id);
        }

        $relatedArticles = $relatedQuery->limit(3)->get();

        if ($relatedArticles->isEmpty()) {
            $relatedArticles = $this->patientVisibleArticlesQuery()
                ->where('articles.id', '!=', $article->id)
                ->limit(3)
                ->get();
        }

        $isCurrentDoctorArticle = false;

        if ($doctorUserId && $this->columnExists('articles', 'user_id')) {
            $isCurrentDoctorArticle = (int) ($article->user_id ?? 0) === (int) $doctorUserId;
        }

        if (! $isCurrentDoctorArticle && $doctorProfileId && $this->columnExists('articles', 'doctor_profile_id')) {
            $isCurrentDoctorArticle = (int) ($article->doctor_profile_id ?? 0) === (int) $doctorProfileId;
        }

        return view('patient.article-details', array_merge($data, [
            'patientArticle' => $article,
            'relatedPatientArticles' => $relatedArticles,
            'isCurrentDoctorArticle' => $isCurrentDoctorArticle,
            'articlesDoctor' => [
                'id' => $doctorProfileId,
                'user_id' => $doctorUserId,
                'name' => $doctor['name'] ?? 'طبيب المتابعة',
                'specialty' => $doctor['specialty'] ?? null,
                'avatar' => $doctor['avatar'] ?? null,
            ],
        ]));
    }

    public function messages(): View
    {
        return view('patient.messages', $this->dashboardData([
            'pageTitle' => 'الرسائل',
            'activePage' => 'messages',
        ]));
    }

    public function support(): View
    {
        return view('patient.support', $this->dashboardData([
            'pageTitle' => 'الدعم',
            'activePage' => 'support',
        ]));
    }

    public function notifications(): View
    {
        return view('patient.notifications', $this->dashboardData([
            'pageTitle' => 'الإشعارات',
            'activePage' => 'notifications',
        ]));
    }

    public function completeProfile(CompleteProfileRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$this->tableExists('patient_profiles')) {
            return back()->withInput()->with('error', 'جدول patient_profiles غير موجود بعد.');
        }

        $conditions = $validated['medical_conditions'] ?? [];

        if (empty($conditions)) {
            $conditions = ['none'];
        }

        $payload = [];
        $avatarPath = null;

        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('patients/avatars', 'public');
        }

        $this->setFirstExistingColumn($payload, 'patient_profiles', ['user_id'], $user->id);
        $this->setFirstExistingColumn($payload, 'patient_profiles', ['height', 'height_cm'], $validated['height_cm']);
        $this->setFirstExistingColumn($payload, 'patient_profiles', ['weight', 'weight_kg', 'current_weight'], $validated['weight_kg']);
        $this->setFirstExistingColumn($payload, 'patient_profiles', ['birth_date', 'date_of_birth'], $validated['birth_date']);
        $this->setFirstExistingColumn($payload, 'patient_profiles', ['gender'], $validated['gender']);
        $this->setFirstExistingColumn($payload, 'patient_profiles', ['phone'], $validated['phone'] ?? null);
        $this->setFirstExistingColumn($payload, 'patient_profiles', ['city'], $validated['city'] ?? null);
        $this->setFirstExistingColumn($payload, 'patient_profiles', ['health_goal', 'goal', 'main_goal', 'target_goal', 'goal_type', 'main_health_goal', 'health_objective'], $validated['health_goal']);
        $this->setFirstExistingColumn($payload, 'patient_profiles', ['activity_level'], $validated['activity_level']);
        $this->setFirstExistingColumn($payload, 'patient_profiles', ['medical_conditions', 'health_condition', 'chronic_diseases', 'diseases'], json_encode($conditions, JSON_UNESCAPED_UNICODE));
        $this->setFirstExistingColumn($payload, 'patient_profiles', ['medications'], $validated['medications'] ?? null);
        $this->setFirstExistingColumn($payload, 'patient_profiles', ['allergies'], $validated['allergies'] ?? null);
        $this->setFirstExistingColumn($payload, 'patient_profiles', ['meals_per_day'], $validated['meals_per_day'] ?? null);
        $this->setFirstExistingColumn($payload, 'patient_profiles', ['sleep_hours'], $validated['sleep_hours'] ?? null);
        $this->setFirstExistingColumn($payload, 'patient_profiles', ['water_cups'], $validated['water_cups'] ?? null);
        $this->setFirstExistingColumn($payload, 'patient_profiles', ['preferred_doctor_gender'], $validated['preferred_doctor_gender'] ?? 'any');
        $this->setFirstExistingColumn($payload, 'patient_profiles', ['preferred_consultation_type'], $validated['preferred_consultation_type'] ?? 'any');
        $this->setFirstExistingColumn($payload, 'patient_profiles', ['notes', 'patient_notes'], $validated['notes'] ?? null);

        if ($avatarPath) {
            $this->setFirstExistingColumn($payload, 'patient_profiles', ['avatar', 'profile_photo', 'photo', 'image', 'profile_photo_path'], $avatarPath);

            $userPayload = [];

            foreach (['avatar', 'profile_photo_path', 'image', 'photo'] as $column) {
                if ($this->columnExists('users', $column)) {
                    $userPayload[$column] = $avatarPath;
                    break;
                }
            }

            if (!empty($userPayload)) {
                DB::table('users')->where('id', $user->id)->update($userPayload);
            }
        }

        $this->setFirstExistingColumn($payload, 'patient_profiles', ['profile_completion', 'completion', 'completion_percentage', 'profile_completion_percentage'], 100);
        $this->setFirstExistingColumn($payload, 'patient_profiles', ['profile_completed', 'has_completed_profile', 'is_profile_complete', 'completed', 'is_completed'], true);

        if ($this->columnExists('patient_profiles', 'updated_at')) {
            $payload['updated_at'] = now();
        }

        $profile = $this->patientProfile($user->id);

        if ($profile) {
            DB::table('patient_profiles')->where('id', $profile->id)->update($payload);
        } else {
            if ($this->columnExists('patient_profiles', 'created_at')) {
                $payload['created_at'] = now();
            }

            DB::table('patient_profiles')->insert($payload);
        }

        return redirect()
            ->route('patient.doctors.recommended')
            ->with('success', 'تم حفظ ملفك الصحي بنجاح. هذه قائمة الأطباء المناسبين لحالتك.');
   }

    public function selectDoctor(Request $request, int $doctorProfile): RedirectResponse
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$this->tableExists('patient_profiles')) {
            return back()->with('error', 'جدول patient_profiles غير موجود.');
        }

        if (!$this->tableExists('doctor_profiles')) {
            return back()->with('error', 'جدول doctor_profiles غير موجود.');
        }

        $doctorExists = DB::table('doctor_profiles')->where('id', $doctorProfile)->exists();

        if (!$doctorExists) {
            return back()->with('error', 'الطبيب المحدد غير موجود.');
        }

        $profile = $this->patientProfile($user->id);

        if (!$profile || $this->profileCompletion($profile) < 100) {
            return redirect()->route('patient.profile')->with('error', 'أكمل ملفك الصحي أولاً قبل اختيار الطبيب.');
        }

        $payload = [];
        $this->setFirstExistingColumn($payload, 'patient_profiles', ['doctor_profile_id', 'selected_doctor_id', 'doctor_id'], $doctorProfile);
        $this->setFirstExistingColumn($payload, 'patient_profiles', ['has_selected_doctor', 'doctor_selected', 'is_doctor_selected'], true);
        $this->setFirstExistingColumn($payload, 'patient_profiles', ['doctor_request_status'], 'pending');

        if ($this->columnExists('patient_profiles', 'updated_at')) {
            $payload['updated_at'] = now();
        }

        if (empty($payload)) {
            return back()->with('error', 'لا يوجد عمود مناسب لحفظ طلب الطبيب في جدول patient_profiles.');
        }

        DB::table('patient_profiles')->where('id', $profile->id)->update($payload);

        $this->createAppNotification(
            recipientUserId: $user->id,
            actorUserId: null,
            type: 'doctor_request_pending',
            title: 'تم إرسال طلب المتابعة',
            body: 'تم إرسال طلب اختيار الطبيب، وسيتم إشعارك عند موافقة الطبيب أو اعتذاره.',
            url: route('patient.profile')
        );

        $doctorUserId = $this->doctorUserIdFromProfile($doctorProfile);

        if ($doctorUserId) {
            $this->createAppNotification(
                recipientUserId: $doctorUserId,
                actorUserId: $user->id,
                type: 'doctor_followup_request_received',
                title: 'طلب متابعة جديد',
                body: 'وصل طلب متابعة جديد من مريض. راجع الطلب ثم اختر الموافقة أو الاعتذار.',
                url: url('/doctor/patients'),
                relatedId: $profile->id ?? null,
                relatedType: 'doctor_followup_request',
                recipientRole: 'doctor',
                data: [
                    'patient_user_id' => $user->id,
                    'patient_profile_id' => $profile->id ?? null,
                    'doctor_profile_id' => $doctorProfile,
                ]
            );
        }

        $this->setFirstExistingColumn($payload, 'patient_profiles', ['doctor_request_status'], 'pending');

        return redirect()
            ->route('patient.doctor.current')
            ->with('success', 'تم إرسال طلب اختيار الطبيب. بانتظار موافقة الطبيب.');

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

    public function sendDoctorMessage(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$this->tableExists('messages') || !$this->tableExists('conversations')) {
            return back()
                ->withInput()
                ->with('error', 'جداول الرسائل غير جاهزة.');
        }

        $profile = $this->patientProfile($user->id);
        $doctor = $this->selectedDoctor($profile);

        if (empty($doctor['is_selected']) || empty($doctor['user_id'])) {
            return back()
                ->withInput()
                ->with('error', 'لا يوجد طبيب متابعة لإرسال الرسالة.');
        }

        $bodyColumn = $this->firstExistingColumn('messages', ['body', 'message', 'content', 'text']);

        if (!$bodyColumn) {
            return back()
                ->withInput()
                ->with('error', 'لا يوجد عمود مناسب لحفظ نص الرسالة في جدول messages.');
        }

        $conversationId = $this->ensureConversation(
            patientUserId: $user->id,
            doctorUserId: (int) $doctor['user_id'],
            patientProfileId: $profile?->id,
            doctorProfileId: $doctor['id'] ?? null
        );

        if (!$conversationId) {
            return back()
                ->withInput()
                ->with('error', 'لم يتم إنشاء محادثة للطبيب، لذلك لا يمكن حفظ الرسالة.');
        }

        $messageText = trim((string) $validated['message']);

        $payload = [
            'conversation_id' => $conversationId,
            $bodyColumn => $messageText,
        ];

        if ($this->columnExists('messages', 'sender_id')) {
            $payload['sender_id'] = $user->id;
        }

        if ($this->columnExists('messages', 'receiver_id')) {
            $payload['receiver_id'] = (int) $doctor['user_id'];
        }

        if ($this->columnExists('messages', 'from_user_id')) {
            $payload['from_user_id'] = $user->id;
        }

        if ($this->columnExists('messages', 'to_user_id')) {
            $payload['to_user_id'] = (int) $doctor['user_id'];
        }

        if ($this->columnExists('messages', 'user_id')) {
            $payload['user_id'] = $user->id;
        }

        if ($this->columnExists('messages', 'patient_profile_id') && $profile) {
            $payload['patient_profile_id'] = $profile->id;
        }

        if ($this->columnExists('messages', 'doctor_profile_id') && !empty($doctor['id'])) {
            $payload['doctor_profile_id'] = $doctor['id'];
        }

        if ($this->columnExists('messages', 'sender_type')) {
            $payload['sender_type'] = 'patient';
        }

        if ($this->columnExists('messages', 'sender_role')) {
            $payload['sender_role'] = 'patient';
        }

        if ($this->columnExists('messages', 'direction')) {
            $payload['direction'] = 'outgoing';
        }

        if ($this->columnExists('messages', 'type')) {
            $payload['type'] = 'doctor_message';
        }

        if ($this->columnExists('messages', 'metadata')) {
            $payload['metadata'] = json_encode([
                'source' => 'patient_doctor_chat',
                'patient_id' => $user->id,
                'doctor_user_id' => (int) $doctor['user_id'],
            ], JSON_UNESCAPED_UNICODE);
        }

        if ($this->columnExists('messages', 'is_read')) {
            $payload['is_read'] = false;
        }

        if ($this->columnExists('messages', 'read_at')) {
            $payload['read_at'] = null;
        }

        if ($this->columnExists('messages', 'created_at')) {
            $payload['created_at'] = now();
        }

        if ($this->columnExists('messages', 'updated_at')) {
            $payload['updated_at'] = now();
        }

        DB::table('messages')->insert($payload);

        $conversationPayload = [];

        if ($this->columnExists('conversations', 'last_message_at')) {
            $conversationPayload['last_message_at'] = now();
        }

        if ($this->columnExists('conversations', 'updated_at')) {
            $conversationPayload['updated_at'] = now();
        }

        if ($this->columnExists('conversations', 'unread_by_admin')) {
            $conversationPayload['unread_by_admin'] = DB::raw('unread_by_admin + 1');
        }

        if (!empty($conversationPayload)) {
            DB::table('conversations')
                ->where('id', $conversationId)
                ->update($conversationPayload);
        }

        $this->createAppNotification(
            recipientUserId: (int) $doctor['user_id'],
            actorUserId: $user->id,
            type: 'message_received',
            title: 'رسالة جديدة من المريض',
            body: 'وصلتك رسالة جديدة من المريض داخل صفحة الرسائل.',
            url: url('/doctor/messages'),
            recipientRole: 'doctor'
        );

        return redirect()
            ->route('patient.messages')
            ->with('success', 'تم إرسال رسالتك للطبيب.');
    }

    public function sendSupportMessage(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$this->tableExists('conversations') || !$this->tableExists('messages')) {
            return back()
                ->withInput()
                ->with('error', 'جداول الرسائل غير جاهزة.');
        }

        $conversationId = $this->ensureSupportConversation($user->id);

        if (!$conversationId) {
            return back()
                ->withInput()
                ->with('error', 'لم يتم إنشاء محادثة الدعم.');
        }

        $bodyColumn = $this->firstExistingColumn('messages', ['body', 'message', 'content', 'text']);

        if (!$bodyColumn) {
            return back()
                ->withInput()
                ->with('error', 'لا يوجد عمود مناسب لحفظ نص الرسالة في جدول messages.');
        }

        $messageText = trim((string) $validated['message']);

        $payload = [
            'conversation_id' => $conversationId,
            $bodyColumn => $messageText,
        ];

        if ($this->columnExists('messages', 'user_id')) {
            $payload['user_id'] = $user->id;
        }

        if ($this->columnExists('messages', 'sender_id')) {
            $payload['sender_id'] = $user->id;
        }

        if ($this->columnExists('messages', 'from_user_id')) {
            $payload['from_user_id'] = $user->id;
        }

        if ($this->columnExists('messages', 'sender_type')) {
            $payload['sender_type'] = 'patient';
        }

        if ($this->columnExists('messages', 'sender_role')) {
            $payload['sender_role'] = 'patient';
        }

        if ($this->columnExists('messages', 'direction')) {
            $payload['direction'] = 'incoming';
        }

        if ($this->columnExists('messages', 'type')) {
            $payload['type'] = 'text';
        }

        if ($this->columnExists('messages', 'metadata')) {
            $payload['metadata'] = json_encode([
                'source' => 'patient_support',
                'patient_id' => $user->id,
            ], JSON_UNESCAPED_UNICODE);
        }

        if ($this->columnExists('messages', 'is_read')) {
            $payload['is_read'] = false;
        }

        if ($this->columnExists('messages', 'read_at')) {
            $payload['read_at'] = null;
        }

        if ($this->columnExists('messages', 'created_at')) {
            $payload['created_at'] = now();
        }

        if ($this->columnExists('messages', 'updated_at')) {
            $payload['updated_at'] = now();
        }

        DB::table('messages')->insert($payload);

        $conversationPayload = [];

        if ($this->columnExists('conversations', 'status')) {
            $statusValue = $this->conversationStatusValue();

            if ($statusValue !== null) {
                $conversationPayload['status'] = $statusValue;
            }
        }

        if ($this->columnExists('conversations', 'last_message_at')) {
            $conversationPayload['last_message_at'] = now();
        }

        if ($this->columnExists('conversations', 'unread_by_admin')) {
            $conversationPayload['unread_by_admin'] = DB::raw('unread_by_admin + 1');
        }

        if ($this->columnExists('conversations', 'updated_at')) {
            $conversationPayload['updated_at'] = now();
        }

        if (!empty($conversationPayload)) {
            DB::table('conversations')
                ->where('id', $conversationId)
                ->update($conversationPayload);
        }

        $this->createAdminNotification(
            type: 'message',
            title: 'رسالة دعم جديدة',
            body: 'وصلت رسالة دعم جديدة من ' . ($user->name ?? 'مريض اتزان') . '.',
            url: route('admin.messages.show', $conversationId),
            relatedId: $conversationId,
            relatedType: 'conversation'
        );

        return redirect()
            ->route('patient.support')
            ->with('success', 'تم إرسال رسالتك للدعم بنجاح.');
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

    public function liveNotifications(): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'notifications_count' => 0,
                'unread_messages' => 0,
                'notifications' => [],
                'messages' => [],
                'support_messages' => [],
            ]);
        }

        $profile = $this->patientProfile($user->id);
        $doctor = $this->selectedDoctor($profile);

        return response()->json([
            'notifications_count' => $this->unreadAppNotificationsCount($user->id),
            'unread_messages' => $this->unreadMessagesCount($user->id),
            'notifications' => $this->patientNotifications($user->id, 5),
            'messages' => $this->latestMessages($user->id, $doctor['user_id'] ?? null),
            'support_messages' => $this->supportMessages($user->id),
        ]);
    }

    public function markNotificationRead(int $notification): RedirectResponse
    {
        $user = auth()->user();

        if (! $user) {
            return back();
        }

        app(AppNotificationService::class)->markAsRead(
            notificationId: $notification,
            recipientUserId: (int) $user->id
        );

        return back();
    }

    public function markAllNotificationsRead(): RedirectResponse
        {
            $user = auth()->user();

            if (! $user) {
                return back();
            }

            app(AppNotificationService::class)->markAllAsRead(
                recipientUserId: (int) $user->id,
                recipientRole: 'patient'
            );

            return back()->with('success', 'تم تحديد كل الإشعارات كمقروءة.');
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
        $todayTasks = $this->todayTasks($user, $patientProfile);
        $journeyTasks = $this->journeyTasks($todayTasks);
        $tasksTotal = max(count($journeyTasks), 1);
        $tasksCompleted = collect($journeyTasks)->where('completed', true)->count();
        $todayProgress = (int) round(($tasksCompleted / $tasksTotal) * 100);
        $messages = $this->latestMessages($user?->id, $doctor['user_id'] ?? null);
        $supportMessages = $this->supportMessages($user?->id);
        $articles = $this->dashboardArticles();
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
            'homeProgress' => [
                'title' => 'تقدمك اليوم',
                'percentage' => $todayProgress,
                'completed' => $tasksCompleted,
                'total' => $tasksTotal,
                'text' => $todayProgress >= 70 ? 'أحسنت! يومك يسير باتزان واضح.' : 'خطوات صغيرة الآن تصنع فرقاً كبيراً في نهاية اليوم.',
            ],
            'homeTasks' => $todayTasks,
            'journeyTasks' => $journeyTasks,
            'messages' => $messages,
            'supportMessages' => $supportMessages,
            'aiCalories' => [
                'title' => 'تحليل السعرات بالذكاء الاصطناعي',
                'target' => null,
                'consumed' => 0,
                'remaining' => null,
                'progress' => 0,
                'carbs' => 0,
                'protein' => 0,
                'fats' => 0,
                'note' => 'ارفع صورة الوجبة أو اكتب وصفها، وسيظهر تقدير السعرات قبل اعتمادها.',
            ],
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
            return $this->fallbackRecommendedDoctors();
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
            return $this->fallbackRecommendedDoctors();
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

    private function fallbackRecommendedDoctors(): array
    {
        $samples = [
            ['name' => 'د. أحمد سالم', 'specialty' => 'تغذية علاجية وسكري', 'score' => 94, 'reason' => 'مناسب لمتابعة التغذية العلاجية وتنظيم الوجبات حسب الحالة الصحية.', 'badges' => ['تغذية علاجية', 'سكري', 'متابعة غذائية'], 'gender' => 'male', 'consultation' => 'online'],
            ['name' => 'د. ليان منصور', 'specialty' => 'صحة عامة وأمراض مزمنة', 'score' => 91, 'reason' => 'مناسبة للمتابعة اليومية وتنظيم العادات الصحية لأصحاب الحالات المزمنة.', 'badges' => ['أمراض مزمنة', 'ضغط', 'نمط حياة'], 'gender' => 'female', 'consultation' => 'online'],
            ['name' => 'د. سامر خليل', 'specialty' => 'تغذية وسمنة ونمط حياة', 'score' => 86, 'reason' => 'مناسب لبناء خطة صحية متدرجة ومتابعة الوزن والعادات اليومية.', 'badges' => ['وزن', 'عادات صحية', 'تغذية'], 'gender' => 'male', 'consultation' => 'clinic'],
        ];

        return collect($samples)->map(function ($doctor, $index) {
            return [
                'id' => $index + 1,
                'is_fallback' => true,
                'name' => $doctor['name'],
                'specialty' => $doctor['specialty'],
                'avatar' => $this->placeholderImage($doctor['name']),
                'bio' => 'طبيب مختص يساعدك على بناء متابعة صحية مناسبة لحالتك وهدفك.',
                'gender' => $doctor['gender'],
                'gender_label' => $doctor['gender'] === 'female' ? 'طبيبة' : 'طبيب',
                'consultation_key' => $doctor['consultation'],
                'consultation_type' => $doctor['consultation'] === 'clinic' ? 'حضوري' : 'أونلاين',
                'experience' => 5 + $index,
                'rating' => number_format(4.8 - ($index * 0.1), 1),
                'reviews_count' => 0,
                'match_score' => $doctor['score'],
                'match_reason' => $doctor['reason'],
                'badges' => $doctor['badges'],
                'articles' => [
                    ['title' => 'كيف تبدأ متابعة صحية بطريقة بسيطة؟', 'read_time' => '4 دقائق قراءة'],
                    ['title' => 'أخطاء شائعة في تنظيم الوجبات اليومية', 'read_time' => '5 دقائق قراءة'],
                ],
            ];
        })->toArray();
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

    private function createAdminNotification(
        string $type,
        string $title,
        string $body,
        ?string $url = null,
        ?int $relatedId = null,
        ?string $relatedType = null
    ): void {
        /*
        | نحافظ على جدول admin_notifications القديم حتى لا تنكسر صفحات الأدمن الحالية.
        */
        if ($this->tableExists('admin_notifications')) {
            $payload = [];

            $this->setFirstExistingColumn($payload, 'admin_notifications', ['type'], $type);
            $this->setFirstExistingColumn($payload, 'admin_notifications', ['title'], $title);
            $this->setFirstExistingColumn($payload, 'admin_notifications', ['body'], $body);
            $this->setFirstExistingColumn($payload, 'admin_notifications', ['icon'], 'fa-headset');
            $this->setFirstExistingColumn($payload, 'admin_notifications', ['url'], $url);

            if ($relatedId) {
                $this->setFirstExistingColumn($payload, 'admin_notifications', ['related_id'], $relatedId);
            }

            if ($relatedType) {
                $this->setFirstExistingColumn($payload, 'admin_notifications', ['related_type'], $relatedType);
            }

            if ($this->columnExists('admin_notifications', 'is_read')) {
                $payload['is_read'] = 0;
            }

            if ($this->columnExists('admin_notifications', 'read_at')) {
                $payload['read_at'] = null;
            }

            if ($this->columnExists('admin_notifications', 'created_at')) {
                $payload['created_at'] = now();
            }

            if ($this->columnExists('admin_notifications', 'updated_at')) {
                $payload['updated_at'] = now();
            }

            if (! empty($payload)) {
                DB::table('admin_notifications')->insert($payload);
            }
        }

        /*
        | نسخة موحدة داخل app_notifications للأدمن.
        */
        foreach ($this->adminUserIds() as $adminUserId) {
            app(AppNotificationService::class)->send(
                recipientUserId: (int) $adminUserId,
                recipientRole: 'admin',
                type: 'admin_' . $type,
                title: $title,
                body: $body,
                url: $url,
                actorUserId: auth()->id(),
                relatedId: $relatedId,
                relatedType: $relatedType,
                data: [
                    'source' => 'patient_controller',
                    'legacy_table' => 'admin_notifications',
                ]
            );
        }
    }

    private function todayTasks($user, ?object $profile): array
    {
        if (!$user || !$this->tableExists('patient_tasks')) {
            return $this->fallbackTasks();
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
            return $this->fallbackTasks();
        }

        if ($this->columnExists('patient_tasks', 'task_date')) {
            $query->whereDate('task_date', now()->toDateString());
        }

        if ($this->columnExists('patient_tasks', 'created_at')) {
            $query->orderByDesc('created_at');
        }

        $rows = $query->limit(8)->get();

        if ($rows->isEmpty()) {
            return $this->fallbackTasks();
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

    private function fallbackTasks(): array
    {
        return [
            ['title' => 'شرب 8 أكواب ماء', 'description' => null, 'completed' => false, 'time' => '12:00', 'type' => 'water', 'icon' => 'droplet', 'progress' => 0, 'progressText' => '0/8 أكواب'],
            ['title' => 'تسجيل وجبة اليوم', 'description' => null, 'completed' => false, 'time' => '13:00', 'type' => 'meal', 'icon' => 'utensils', 'progress' => 0, 'progressText' => '0/3 وجبات'],
        ];
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

    private function unreadMessagesCount(?int $userId): int
    {
        if (!$userId || !$this->tableExists('messages')) {
            return 0;
        }

        $query = DB::table('messages');

        if ($this->columnExists('messages', 'receiver_id')) {
            $query->where('receiver_id', $userId);
        } elseif ($this->columnExists('messages', 'user_id')) {
            $query->where('user_id', $userId);
        } else {
            return 0;
        }

        if ($this->columnExists('messages', 'read_at')) {
            $query->whereNull('read_at');
        } elseif ($this->columnExists('messages', 'is_read')) {
            $query->where('is_read', false);
        } else {
            return 0;
        }

        return $query->count();
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
            return $this->fallbackArticles();
        }

        $rows = $this->patientVisibleArticlesQuery()->limit(3)->get();

        if ($rows->isEmpty()) {
            return $this->fallbackArticles();
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

    private function fallbackArticles(): array
    {
        return [
            [
                'title' => 'أطعمة تعزز المناعة بطريقة طبيعية',
                'tag' => 'تغذية',
                'read_time' => '4 دقائق قراءة',
                'image' => $this->placeholderImage('مقال صحي'),
                'url' => route('patient.articles'),
            ],
        ];
    }

    private function homeStats(?object $profile, int $todayProgress): array
    {
        $weight = $this->valueFrom($profile, ['weight', 'weight_kg', 'current_weight']);

        return [
            ['title' => 'مهام اليوم', 'value' => $todayProgress, 'unit' => '%', 'subtitle' => 'نسبة الإنجاز', 'icon' => 'badge-check', 'progress' => $todayProgress, 'color' => 'green'],
            ['title' => 'الوزن', 'value' => $weight ?: '—', 'unit' => $weight ? 'كغ' : '', 'subtitle' => $weight ? 'آخر وزن مسجل' : 'لم يتم تسجيل الوزن', 'icon' => 'scale', 'progress' => $weight ? 65 : 0, 'color' => 'purple'],
        ];
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

    private function firstExistingColumn(string $table, array $columns): ?string
    {
        foreach ($columns as $column) {
            if ($this->columnExists($table, $column)) {
                return $column;
            }
        }

        return null;
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

    private function adminUserIds(): array
    {
        if (! $this->tableExists('users')) {
            return [];
        }

        $query = DB::table('users');

        if ($this->columnExists('users', 'role')) {
            $query->whereIn('role', ['admin', 'super_admin', 'superadmin']);
        } elseif ($this->columnExists('users', 'user_type')) {
            $query->whereIn('user_type', ['admin', 'super_admin', 'superadmin']);
        } elseif ($this->columnExists('users', 'type')) {
            $query->whereIn('type', ['admin', 'super_admin', 'superadmin']);
        } elseif ($this->columnExists('users', 'is_admin')) {
            $query->where('is_admin', true);
        } else {
            return [];
        }

        return $query
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->toArray();
    }

    private function tableExists(string $table): bool
    {
        static $cache = [];

        if (!array_key_exists($table, $cache)) {
            $cache[$table] = Schema::hasTable($table);
        }

        return $cache[$table];
    }

    private function columnExists(string $table, string $column): bool
    {
        static $cache = [];

        $key = $table . '.' . $column;

        if (!array_key_exists($key, $cache)) {
            $cache[$key] = Schema::hasTable($table) && Schema::hasColumn($table, $column);
        }

        return $cache[$key];
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

    private function conversationStatusValue(): ?string
{
    if (! Schema::hasTable('conversations') || ! Schema::hasColumn('conversations', 'status')) {
        return null;
    }

    try {
        $column = DB::selectOne("SHOW COLUMNS FROM conversations WHERE Field = 'status'");

        $type = $column->Type ?? '';

        if (str_contains($type, "enum")) {
            preg_match_all("/'([^']+)'/", $type, $matches);

            $allowed = $matches[1] ?? [];

            foreach (['open', 'active', 'pending', 'new'] as $status) {
                if (in_array($status, $allowed, true)) {
                    return $status;
                }
            }

            return $allowed[0] ?? null;
        }

        return 'open';
    } catch (\Throwable $e) {
        return 'open';
    }
}









}
