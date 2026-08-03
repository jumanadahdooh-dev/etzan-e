<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Doctor\LogPatientWeightRequest;
use App\Http\Requests\Doctor\SetCalorieGoalRequest;
use App\Models\PatientTask;
use App\Services\AppNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DoctorPatientDetailController extends Controller
{
    public function show(int $patientProfile): View|RedirectResponse
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $doctorProfile = DB::table('doctor_profiles')->where('user_id', $user->id)->first();

        if (!$doctorProfile) {
            return back()->with('error', 'لا يوجد ملف طبيب مرتبط بهذا الحساب.');
        }

        // أمان: التأكد إنه هالمريض فعلاً مرتبط بنفس الطبيب المسجّل دخوله،
        // حتى ما يقدر طبيب يشوف بيانات مريض طبيب تاني عبر تغيير الرقم بالرابط.
        $profile = DB::table('patient_profiles')
            ->join('users', 'users.id', '=', 'patient_profiles.user_id')
            ->where('patient_profiles.id', $patientProfile)
            ->where('patient_profiles.doctor_profile_id', $doctorProfile->id)
            ->select('patient_profiles.*', 'users.name as patient_name', 'users.email as patient_email')
            ->first();

        if (!$profile) {
            return back()->with('error', 'هذا المريض غير مرتبط بحسابك.');
        }

        $conditions = [];
        if (!empty($profile->medical_conditions)) {
            $decoded = json_decode($profile->medical_conditions, true);
            if (is_array($decoded)) {
                $conditions = array_values(array_filter($decoded, fn ($c) => $c !== 'none'));
            }
        }

        $age = null;
        if (!empty($profile->birth_date)) {
            $age = (int) \Illuminate\Support\Carbon::parse($profile->birth_date)->age;
        }

        $avatarUrl = $this->resolveAvatarUrl($profile);

        $appointments = collect();
        if (Schema::hasTable('patient_appointments')) {
            $appointments = DB::table('patient_appointments')
                ->where('user_id', $profile->user_id)
                ->where('doctor_profile_id', $doctorProfile->id)
                ->orderByDesc('appointment_date')
                ->orderByDesc('appointment_time')
                ->get();
        }

        $genderLabels = ['male' => 'ذكر', 'female' => 'أنثى'];
        $statusLabels = ['pending' => 'معلّق', 'approved' => 'مقبول', 'rejected' => 'مرفوض'];

        // ملخص التغذية: وجبات آخر 7 أيام (حقيقية 100% من patient_meals)
        $mealsCount = 0;
        $avgCalories = null;
        $avgProtein = null;
        $recentMeals = collect();

        if (Schema::hasTable('patient_meals')) {
            $mealsQuery = DB::table('patient_meals')
                ->where('user_id', $profile->user_id)
                ->where('status', 'confirmed')
                ->where('meal_date', '>=', now()->subDays(7)->toDateString());

            $mealsCount = (int) $mealsQuery->count();

            if ($mealsCount > 0) {
                $avgCalories = (int) round((clone $mealsQuery)->avg('calories'));
                $avgProtein = (int) round((clone $mealsQuery)->avg('protein'));
            }

            $recentMeals = DB::table('patient_meals')
                ->where('user_id', $profile->user_id)
                ->where('status', 'confirmed')
                ->orderByDesc('meal_date')
                ->orderByDesc('id')
                ->limit(5)
                ->get();
        }

        // هدف السعرات الحالي (لليوم) — لو الطبيب حدده قبل هيك
        $currentGoal = null;
        if (Schema::hasTable('patient_daily_calorie_goals')) {
            $currentGoal = DB::table('patient_daily_calorie_goals')
                ->where('user_id', $profile->user_id)
                ->whereDate('goal_date', now()->toDateString())
                ->first();
        }

        // سجل الوزن الحقيقي (جدول جديد، بيبلش فاضي وبيتعبى تدريجياً)
        $weightLogs = collect();
        if (Schema::hasTable('patient_weight_logs')) {
            $weightLogs = DB::table('patient_weight_logs')
                ->where('user_id', $profile->user_id)
                ->orderBy('logged_date')
                ->get();
        }

        // المهام يلي حددها الطبيب لهالمريض — جديد كلياً، بيبلش فاضي وبيتراكم
        $tasks = collect();
        if (Schema::hasTable('patient_tasks')) {
            $tasks = DB::table('patient_tasks')
                ->where('patient_user_id', $profile->user_id)
                ->where('source', 'doctor')
                ->orderByDesc('task_date')
                ->orderByDesc('id')
                ->limit(20)
                ->get();
        }

        // اتجاه السعرات من تاريخ الموافقة (أو آخر تحديث للملف كأقرب تقدير حقيقي متوفر)
        $followupStart = $profile->updated_at
            ? \Illuminate\Support\Carbon::parse($profile->updated_at)
            : now();
        $daysSinceStart = min(30, max(1, $followupStart->diffInDays(now()) + 1));

        $caloriesTrend = [];
        if (Schema::hasTable('patient_meals')) {
            for ($i = $daysSinceStart - 1; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $dayTotal = DB::table('patient_meals')
                    ->where('user_id', $profile->user_id)
                    ->where('status', 'confirmed')
                    ->whereDate('meal_date', $date->toDateString())
                    ->sum('calories');

                $caloriesTrend[] = [
                    'label' => $date->locale('ar')->translatedFormat('j M'),
                    'calories' => (int) $dayTotal,
                ];
            }
        }

        return view('doctor.patient-detail', [
            'pageTitle' => 'ملف المريض',
            'activePage' => 'patients',
            'profile' => $profile,
            'age' => $age,
            'avatarUrl' => $avatarUrl,
            'genderLabel' => $genderLabels[$profile->gender ?? ''] ?? '—',
            'conditions' => $conditions,
            'appointments' => $appointments,
            'requestStatus' => $profile->doctor_request_status ?? 'pending',
            'requestStatusLabel' => $statusLabels[$profile->doctor_request_status ?? 'pending'] ?? '—',
            'mealsCount' => $mealsCount,
            'avgCalories' => $avgCalories,
            'avgProtein' => $avgProtein,
            'recentMeals' => $recentMeals,
            'currentGoal' => $currentGoal,
            'weightLogs' => $weightLogs,
            'currentWeightKg' => $profile->weight ?? $profile->weight_kg ?? $profile->current_weight ?? null,
            'followupStart' => $followupStart,
            'caloriesTrend' => $caloriesTrend,
            'tasks' => $tasks,
        ]);
    }


    public function setCalorieGoal(SetCalorieGoalRequest $request, int $patientProfile): RedirectResponse
    {
        $validated = $request->validated();

        $user = auth()->user();
        $doctorProfile = DB::table('doctor_profiles')->where('user_id', $user->id)->first();

        $profile = DB::table('patient_profiles')
            ->where('id', $patientProfile)
            ->where('doctor_profile_id', $doctorProfile?->id)
            ->first();

        if (!$profile) {
            return back()->with('error', 'هذا المريض غير مرتبط بحسابك.');
        }

        $durationDays = (int) ($validated['duration_days'] ?? 1);

        for ($i = 0; $i < $durationDays; $i++) {
            DB::table('patient_daily_calorie_goals')->updateOrInsert(
                [
                    'user_id' => $profile->user_id,
                    'goal_date' => now()->addDays($i)->toDateString(),
                ],
                [
                    'patient_profile_id' => $profile->id,
                    'doctor_profile_id' => $doctorProfile->id,
                    'doctor_user_id' => $user->id,
                    'calories_goal' => $validated['calories_goal'],
                    'protein_goal' => $validated['protein_goal'] ?? null,
                    'carbs_goal' => $validated['carbs_goal'] ?? null,
                    'fat_goal' => $validated['fat_goal'] ?? null,
                    'status' => 'approved',
                    'doctor_note' => $validated['doctor_note'] ?? null,
                    'approved_at' => now(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        app(AppNotificationService::class)->send(
            recipientUserId: $profile->user_id,
            recipientRole: 'patient',
            type: 'calorie_goal_set',
            title: 'طبيبك حدد هدف سعرات جديد',
            body: $durationDays > 1
                ? 'حدد طبيبك هدف ' . $validated['calories_goal'] . ' سعرة يوميًا لمدة ' . $durationDays . ' يوم.'
                : 'حدد طبيبك هدف ' . $validated['calories_goal'] . ' سعرة لليوم.',
            url: route('patient.calories'),
            actorUserId: $user->id
        );

        return back()->with('success', $durationDays > 1
            ? "تم تحديد هدف السعرات لـ{$durationDays} يوم بنجاح."
            : 'تم تحديد هدف السعرات لليوم بنجاح.');
    }

    /**
     * تسجيل قياس وزن جديد للمريض (من الطبيب، وقت الزيارة/المتابعة).
     * الجدول جديد كلياً، فبيبلش فاضي وبيتراكم بمرور الوقت بس.
     */
    public function logWeight(LogPatientWeightRequest $request, int $patientProfile): RedirectResponse
    {
        $validated = $request->validated();

        $user = auth()->user();
        $doctorProfile = DB::table('doctor_profiles')->where('user_id', $user->id)->first();

        $profile = DB::table('patient_profiles')
            ->where('id', $patientProfile)
            ->where('doctor_profile_id', $doctorProfile?->id)
            ->first();

        if (!$profile) {
            return back()->with('error', 'هذا المريض غير مرتبط بحسابك.');
        }

        if (!Schema::hasTable('patient_weight_logs')) {
            return back()->with('error', 'جدول سجل الوزن غير موجود بعد — لازم تشغّلي هجرة قاعدة البيانات أول.');
        }

        DB::table('patient_weight_logs')->insert([
            'user_id' => $profile->user_id,
            'patient_profile_id' => $profile->id,
            'doctor_profile_id' => $doctorProfile->id,
            'weight_kg' => $validated['weight_kg'],
            'logged_date' => $validated['logged_date'] ?? now()->toDateString(),
            'source' => 'doctor',
            'note' => $validated['note'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(AppNotificationService::class)->send(
            recipientUserId: $profile->user_id,
            recipientRole: 'patient',
            type: 'weight_logged',
            title: 'طبيبك سجّل قياس وزن جديد',
            body: 'سجّل طبيبك قياس وزن ' . $validated['weight_kg'] . ' كغم إلك.',
            url: route('patient.calories'),
            actorUserId: $user->id
        );

        return back()->with('success', 'تم تسجيل قياس الوزن بنجاح.');
    }

    /**
     * إسناد مهمة للمريض — كانت هاي الميزة جاهزة بالكامل بجدول patient_tasks
     * وواجهة "رحلتي" عند المريض (بتعرض مهام الطبيب بشكل مختلف عن مهام
     * المريض نفسه)، بس ما كان في أي route/controller عند الطبيب ينشئها.
     */
    public function assignTask(Request $request, int $patientProfile): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'min:3', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'task_date' => ['required', 'date'],
            'task_time' => ['nullable', 'date_format:H:i'],
        ], [
            'title.required' => 'اكتب عنوان المهمة.',
            'title.min' => 'عنوان المهمة يجب أن يكون 3 أحرف على الأقل.',
            'task_date.required' => 'اختر تاريخ المهمة.',
        ]);

        $user = auth()->user();
        $doctorProfile = DB::table('doctor_profiles')->where('user_id', $user->id)->first();

        $profile = DB::table('patient_profiles')
            ->where('id', $patientProfile)
            ->where('doctor_profile_id', $doctorProfile?->id)
            ->first();

        if (!$profile) {
            return back()->with('error', 'هذا المريض غير مرتبط بحسابك.');
        }

        PatientTask::create([
            'patient_id' => $profile->user_id, // <--- تم التصحيح هنا!
            'patient_user_id' => $profile->user_id,
            'doctor_user_id' => $user->id,
            'created_by_id' => $user->id,
            'created_by_type' => 'doctor',
            'source' => 'doctor',

            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'task_date' => $validated['task_date'],
            'task_time' => $validated['task_time'] ?? null,

            'status' => 'pending',
            'repeat_type' => 'once',
        ]);

        app(AppNotificationService::class)->send(
            recipientUserId: $profile->user_id,
            recipientRole: 'patient',
            type: 'task_reminder',
            title: 'مهمة جديدة من طبيبك',
            body: 'أضاف طبيبك مهمة جديدة إلك: "' . $validated['title'] . '".',
            url: route('patient.journey'),
            actorUserId: $user->id
        );

        return back()->with('success', 'تم إسناد المهمة للمريض.');
    }

    /**
     * بناء رابط صورة المريض الحقيقية لو موجودة (مرفوعة وقت إكمال الملف الصحي)،
     * وإلا بترجع null فيرجع نستخدم الحرف الأول كـ fallback صادق.
     */
    private function resolveAvatarUrl(object $profile): ?string
    {
        $path = null;

        foreach (['avatar', 'profile_photo', 'photo', 'image', 'profile_photo_path'] as $column) {
            if (!empty($profile->{$column} ?? null)) {
                $path = $profile->{$column};
                break;
            }
        }

        if (!$path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, 'data:image')) {
            return $path;
        }

        return asset('storage/' . ltrim($path, '/'));
    }
}
