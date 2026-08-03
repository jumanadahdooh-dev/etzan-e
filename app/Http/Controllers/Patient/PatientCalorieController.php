<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Patient\Concerns\PatientContextHelpers;
use App\Http\Requests\Patient\AnalyzeMealRequest;
use App\Http\Requests\Patient\BookAppointmentRequest;
use App\Http\Requests\Patient\CompleteProfileRequest;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\PatientDailyCalorieGoal;
use App\Models\PatientMeal;
use App\Services\AiMealAnalysisService;
use App\Services\AppNotificationService;
use App\Services\NutritionInsightService;
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

class PatientCalorieController extends Controller
{
    use PatientContextHelpers;

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
        $fiber = (int) $todayMeals->sum('fiber');
        $sugar = (int) $todayMeals->sum('sugar');
        $sodium = (int) $todayMeals->sum('sodium');

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
            'fiber' => $fiber,
            'sugar' => $sugar,
            'sodium' => $sodium,

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


    public function analyzeMeal(AnalyzeMealRequest $request, AiMealAnalysisService $mealAnalysisService, NutritionInsightService $insightService): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();

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
            $message = 'اكتب وصف الوجبة أو ارفع صورة قبل التحليل.';

            if ($request->wantsJson()) {
                return response()->json(['message' => $message, 'errors' => ['description' => [$message]]], 422);
            }

            return back()
                ->withInput()
                ->with('error', $message);
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

        $result = $mealAnalysisService->analyze(
            description: $description,
            imageStoragePath: $imagePath,
            mealType: $validated['meal_type']
        );

        if ($result['status'] === 'success') {
            $result = array_merge($result, $insightService->analyze($result));
        }

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
            'fiber' => ['nullable', 'integer', 'min:0', 'max:200'],
            'sugar' => ['nullable', 'integer', 'min:0', 'max:400'],
            'sodium' => ['nullable', 'integer', 'min:0', 'max:10000'],
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
            'fiber' => (int) ($validated['fiber'] ?? $draft['fiber'] ?? 0),
            'sugar' => (int) ($validated['sugar'] ?? $draft['sugar'] ?? 0),
            'sodium' => (int) ($validated['sodium'] ?? $draft['sodium'] ?? 0),
            'confidence' => (int) ($draft['confidence'] ?? 0),
            'health_score' => $draft['health_score'] ?? null,

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


    public function updateMeal(Request $request, PatientMeal $meal): RedirectResponse
    {
        $this->authorize('update', $meal);

        $validated = $request->validate([
            'meal_name' => ['required', 'string', 'max:255'],
            'calories' => ['required', 'integer', 'min:0', 'max:5000'],
            'protein' => ['nullable', 'integer', 'min:0', 'max:400'],
            'carbs' => ['nullable', 'integer', 'min:0', 'max:700'],
            'fat' => ['nullable', 'integer', 'min:0', 'max:400'],
            'patient_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $meal->update([
            'meal_name' => $validated['meal_name'],
            'calories' => (int) $validated['calories'],
            'protein' => (int) ($validated['protein'] ?? 0),
            'carbs' => (int) ($validated['carbs'] ?? 0),
            'fat' => (int) ($validated['fat'] ?? 0),
            'patient_note' => $validated['patient_note'] ?? null,
        ]);

        return redirect()
            ->route('patient.calories', ['date' => $meal->meal_date?->toDateString() ?? now()->toDateString()])
            ->with('success', 'تم تحديث الوجبة بنجاح.');
    }

    public function destroyMeal(PatientMeal $meal): RedirectResponse
    {
        $this->authorize('delete', $meal);

        $date = $meal->meal_date?->toDateString() ?? now()->toDateString();

        $meal->delete();

        return redirect()
            ->route('patient.calories', ['date' => $date])
            ->with('success', 'تم حذف الوجبة من سجل اليوم.');
    }

}
