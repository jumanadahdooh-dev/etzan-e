<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DoctorPlansController extends Controller
{
    /**
     * قائمة "الخطط الغذائية" الحقيقية — هدف السعرات/البروتين/الكارب/الدهون
     * يلي الطبيب حدده لكل مريض من صفحة ملف المريض (patient_daily_calorie_goals).
     * قبل هيك كانت هاي الصفحة بس أسماء وأرقام وهمية مكتوبة بالكود مباشرة.
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

        $patients = DB::table('patient_profiles')
            ->join('users', 'users.id', '=', 'patient_profiles.user_id')
            ->where('patient_profiles.doctor_profile_id', $doctorProfile->id)
            ->where('patient_profiles.doctor_request_status', 'approved')
            ->select('patient_profiles.id as profile_id', 'patient_profiles.user_id', 'users.name as patient_name')
            ->get();

        $goals = collect();
        if (Schema::hasTable('patient_daily_calorie_goals') && $patients->isNotEmpty()) {
            // آخر هدف مسجّل لكل مريض (مش بس هدف اليوم) حتى الصفحة تعرض
            // "آخر خطة محددة" حتى لو الطبيب ما حدثها اليوم بالذات.
            $goals = DB::table('patient_daily_calorie_goals')
                ->whereIn('user_id', $patients->pluck('user_id'))
                ->orderByDesc('goal_date')
                ->get()
                ->groupBy('user_id')
                ->map(fn ($rows) => $rows->first());
        }

        $plans = collect();
        $patientsWithoutPlan = collect();

        foreach ($patients as $patient) {
            $goal = $goals->get($patient->user_id);

            if ($goal) {
                $plans->push((object) [
                    'profile_id' => $patient->profile_id,
                    'patient_name' => $patient->patient_name,
                    'calories_goal' => $goal->calories_goal,
                    'protein_goal' => $goal->protein_goal,
                    'carbs_goal' => $goal->carbs_goal,
                    'fat_goal' => $goal->fat_goal,
                    'status' => $goal->status,
                    'goal_date' => $goal->goal_date,
                    'doctor_note' => $goal->doctor_note,
                ]);
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
