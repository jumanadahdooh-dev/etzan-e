<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DoctorPlansController extends Controller
{
    /**
     * قائمة "الخطط الغذائية" الحقيقية — تعرض الخطة الكاملة لكل مريض
     * (السعرات، الوجبات، المهام، الوزن، والمواعيد القادمة).
     */
    public function index(): View
    {
        $doctorProfile = DB::table('doctor_profiles')->where('user_id', auth()->id())->first();

        if (!$doctorProfile) {
            return view('doctor.plans', [
                'pageTitle' => 'الخطط الغذائية',
                'activePage' => 'plans',
                'plans' => collect(),
                'patientsWithoutPlan' => collect(),
            ]);
        }

        // 1. جلب جميع المرضى المقبولين لدى الطبيب
        $patients = DB::table('patient_profiles')
            ->join('users', 'users.id', '=', 'patient_profiles.user_id')
            ->where('patient_profiles.doctor_profile_id', $doctorProfile->id)
            ->where('patient_profiles.doctor_request_status', 'approved')
            ->select(
                'patient_profiles.id as profile_id',
                'patient_profiles.user_id',
                'users.name as patient_name',
                'patient_profiles.created_at'
            )
            ->get();

        // 2. جلب أهداف السعرات (آخر هدف لكل مريض)
        $goals = collect();
        if (Schema::hasTable('patient_daily_calorie_goals') && $patients->isNotEmpty()) {
            $goals = DB::table('patient_daily_calorie_goals')
                ->whereIn('user_id', $patients->pluck('user_id'))
                ->orderByDesc('goal_date')
                ->get()
                ->groupBy('user_id')
                ->map(fn ($rows) => $rows->first());
        }

        // ==============================================================
        // 3. جلب الوجبات (اكتشاف العمود تلقائياً)
        // ==============================================================
        $meals = collect();
        if (Schema::hasTable('patient_meals') && $patients->isNotEmpty()) {
            // الحصول على أسماء الأعمدة في الجدول
            $columns = Schema::getColumnListing('patient_meals');
            // تحديد العمود الصحيح للمريض
            $idColumn = 'user_id';
            if (!in_array($idColumn, $columns) && in_array('patient_user_id', $columns)) {
                $idColumn = 'patient_user_id';
            } elseif (!in_array($idColumn, $columns) && in_array('patient_id', $columns)) {
                $idColumn = 'patient_id';
            }

            $meals = DB::table('patient_meals')
                ->whereIn($idColumn, $patients->pluck('user_id'))
                ->orderByDesc('meal_date')
                ->get()
                ->groupBy($idColumn)
                ->map(fn ($rows) => $rows->take(3));
        }

        // ==============================================================
        // 4. جلب المهام (اكتشاف العمود تلقائياً)
        // ==============================================================
        $tasks = collect();
        if (Schema::hasTable('patient_tasks') && $patients->isNotEmpty()) {
            $columns = Schema::getColumnListing('patient_tasks');
            $idColumn = 'user_id';
            if (!in_array($idColumn, $columns) && in_array('patient_user_id', $columns)) {
                $idColumn = 'patient_user_id';
            } elseif (!in_array($idColumn, $columns) && in_array('patient_id', $columns)) {
                $idColumn = 'patient_id';
            }

            $tasks = DB::table('patient_tasks')
                ->whereIn($idColumn, $patients->pluck('user_id'))
                ->whereIn('status', ['pending', 'in_progress'])
                ->orderByDesc('task_date')
                ->get()
                ->groupBy($idColumn)
                ->map(fn ($rows) => $rows->first());
        }

        // ==============================================================
        // 5. جلب الوزن (اكتشاف العمود تلقائياً)
        // ==============================================================
        $weights = collect();
        if (Schema::hasTable('patient_weight_logs') && $patients->isNotEmpty()) {
            $columns = Schema::getColumnListing('patient_weight_logs');
            $idColumn = 'user_id';
            if (!in_array($idColumn, $columns) && in_array('patient_user_id', $columns)) {
                $idColumn = 'patient_user_id';
            } elseif (!in_array($idColumn, $columns) && in_array('patient_id', $columns)) {
                $idColumn = 'patient_id';
            }

            $weights = DB::table('patient_weight_logs')
                ->whereIn($idColumn, $patients->pluck('user_id'))
                ->orderByDesc('logged_date')
                ->get()
                ->groupBy($idColumn)
                ->map(fn ($rows) => $rows->first());
        }

        // ==============================================================
        // 6. جلب الموعد القادم (اكتشاف العمود تلقائياً)
        // ==============================================================
        $appointments = collect();
        if (Schema::hasTable('appointments') && $patients->isNotEmpty()) {
            $columns = Schema::getColumnListing('appointments');
            $idColumn = 'patient_user_id'; // المواعيد غالباً patient_user_id
            if (!in_array($idColumn, $columns) && in_array('user_id', $columns)) {
                $idColumn = 'user_id';
            } elseif (!in_array($idColumn, $columns) && in_array('patient_id', $columns)) {
                $idColumn = 'patient_id';
            }

            $now = now();
            $appointments = DB::table('appointments')
                ->whereIn($idColumn, $patients->pluck('user_id'))
                ->where('doctor_user_id', auth()->id())
                ->where('appointment_date', '>=', $now->toDateString())
                ->whereIn('status', ['pending', 'approved', 'confirmed'])
                ->orderBy('appointment_date')
                ->orderBy('appointment_time')
                ->get()
                ->groupBy($idColumn)
                ->map(fn ($rows) => $rows->first());
        }

        // 7. تجميع البيانات وبناء المصفوفة النهائية
        $plans = collect();
        $patientsWithoutPlan = collect();

        foreach ($patients as $patient) {
            $goal = $goals->get($patient->user_id);

            if ($goal) {
                // إضافة البيانات الإضافية للمريض
                $planData = (object) [
                    'profile_id' => $patient->profile_id,
                    'patient_name' => $patient->patient_name,
                    'calories_goal' => $goal->calories_goal,
                    'protein_goal' => $goal->protein_goal,
                    'carbs_goal' => $goal->carbs_goal,
                    'fat_goal' => $goal->fat_goal,
                    'status' => $goal->status,
                    'goal_date' => $goal->goal_date,
                    'doctor_note' => $goal->doctor_note,

                    // البيانات الجديدة
                    'recent_meals' => $meals->get($patient->user_id, collect()),
                    'recent_task' => $tasks->get($patient->user_id),
                    'last_weight' => $weights->get($patient->user_id),
                    'upcoming_appointment' => $appointments->get($patient->user_id),
                ];

                $plans->push($planData);
            } else {
                $patientsWithoutPlan->push($patient);
            }
        }

        return view('doctor.plans', [
            'pageTitle' => 'الخطط الغذائية',
            'activePage' => 'plans',
            'plans' => $plans,
            'patientsWithoutPlan' => $patientsWithoutPlan,
        ]);
    }
}
