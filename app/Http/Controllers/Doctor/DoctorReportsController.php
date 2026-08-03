<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DoctorReportsController extends Controller
{
    /**
     * تقرير حقيقي 100% من بيانات الطبيب الفعلية — قبل هيك كانت كل
     * الأرقام هون مكتوبة يدوياً بالكود (28 مريض، 74%، 156 مراجعة...).
     */
    public function index(): View
    {
        $doctorProfile = DB::table('doctor_profiles')->where('user_id', auth()->id())->first();

        if (!$doctorProfile) {
            return view('doctor.reports', [
                'pageTitle' => 'التقارير',
                'activePage' => 'reports',
                'activePatients' => 0,
                'completionRate' => 0,
                'mealsThisMonth' => 0,
                'publishedArticles' => 0,
                'topPatients' => collect(),
                'weeklyMeals' => [],
            ]);
        }

        $patientUserIds = DB::table('patient_profiles')
            ->where('doctor_profile_id', $doctorProfile->id)
            ->where('doctor_request_status', 'approved')
            ->pluck('user_id');

        $activePatients = $patientUserIds->count();

        // نسبة إنجاز المهام يلي حددها الطبيب لمرضاه (آخر 30 يوم)
        $completionRate = 0;
        if (Schema::hasTable('patient_tasks') && $patientUserIds->isNotEmpty()) {
            $tasks = DB::table('patient_tasks')
                ->whereIn('patient_user_id', $patientUserIds)
                ->where('source', 'doctor')
                ->where('task_date', '>=', now()->subDays(30)->toDateString())
                ->get();

            $completionRate = $tasks->count() > 0
                ? (int) round(($tasks->where('status', 'completed')->count() / $tasks->count()) * 100)
                : 0;
        }

        // إجمالي الوجبات المسجّلة لكل مرضاه هذا الشهر
        $mealsThisMonth = 0;
        if (Schema::hasTable('patient_meals') && $patientUserIds->isNotEmpty()) {
            $mealsThisMonth = DB::table('patient_meals')
                ->whereIn('user_id', $patientUserIds)
                ->where('status', 'confirmed')
                ->whereYear('meal_date', now()->year)
                ->whereMonth('meal_date', now()->month)
                ->count();
        }

        $publishedArticles = Article::where('user_id', auth()->id())
            ->where('status', 'published')
            ->count();

        // أكثر المرضى التزاماً — الأكثر تسجيلاً للوجبات آخر 30 يوم
        $topPatients = collect();
        if (Schema::hasTable('patient_meals') && $patientUserIds->isNotEmpty()) {
            $mealCounts = DB::table('patient_meals')
                ->whereIn('user_id', $patientUserIds)
                ->where('status', 'confirmed')
                ->where('meal_date', '>=', now()->subDays(30)->toDateString())
                ->select('user_id', DB::raw('count(*) as meals_count'))
                ->groupBy('user_id')
                ->orderByDesc('meals_count')
                ->limit(5)
                ->get()
                ->keyBy('user_id');

            if ($mealCounts->isNotEmpty()) {
                $names = DB::table('users')->whereIn('id', $mealCounts->keys())->pluck('name', 'id');

                $topPatients = $mealCounts->map(fn ($row, $userId) => (object) [
                    'name' => $names[$userId] ?? 'مريض',
                    'meals_count' => $row->meals_count,
                ])->values();
            }
        }

        // عدد الوجبات المسجّلة لكل مرضاه، يوم بيوم، لآخر 7 أيام (للرسم البياني)
        $weeklyMeals = [];
        if (Schema::hasTable('patient_meals') && $patientUserIds->isNotEmpty()) {
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i);

                $weeklyMeals[] = [
                    'label' => $date->locale('ar')->translatedFormat('D'),
                    'count' => DB::table('patient_meals')
                        ->whereIn('user_id', $patientUserIds)
                        ->where('status', 'confirmed')
                        ->whereDate('meal_date', $date->toDateString())
                        ->count(),
                ];
            }
        }

        return view('doctor.reports', [
            'pageTitle' => 'التقارير',
            'activePage' => 'reports',
            'activePatients' => $activePatients,
            'completionRate' => $completionRate,
            'mealsThisMonth' => $mealsThisMonth,
            'publishedArticles' => $publishedArticles,
            'topPatients' => $topPatients,
            'weeklyMeals' => $weeklyMeals,
        ]);
    }
}
