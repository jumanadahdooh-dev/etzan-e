<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DoctorAlertsController extends Controller
{
    /**
     * تنبيهات حقيقية مبنية على بيانات فعلية (مش أسماء وأرقام مكتوبة بالكود):
     * 1) مريض ما سجل ولا وجبة من 3 أيام أو أكتر (انقطاع عن المتابعة).
     * 2) مهمة كانت الطبيب حددها للمريض وصار وقتها "متأخرة" ولسا مش منجزة.
     */
    public function index(): View
    {
        $doctorProfile = DB::table('doctor_profiles')->where('user_id', auth()->id())->first();

        return view('doctor.alerts', [
            'pageTitle' => 'تنبيهات المرضى',
            'activePage' => 'alerts',
            'alerts' => $doctorProfile ? $this->resolveAlerts($doctorProfile->id) : collect(),
        ]);
    }

    /**
     * نفس منطق التنبيهات المستخدم بصفحة "تنبيهات المرضى"، معروض هون كدالة
     * قابلة لإعادة الاستخدام حتى الداشبورد يقدر يحسب عدد التنبيهات الحقيقي
     * بدون ما يكرر نفس المنطق (nutritionGapAlert/overdueTaskAlert).
     */
    public function resolveAlerts(int $doctorProfileId): \Illuminate\Support\Collection
    {
        $patients = DB::table('patient_profiles')
            ->join('users', 'users.id', '=', 'patient_profiles.user_id')
            ->where('patient_profiles.doctor_profile_id', $doctorProfileId)
            ->where('patient_profiles.doctor_request_status', 'approved')
            ->select('patient_profiles.id as profile_id', 'patient_profiles.user_id', 'users.name as patient_name')
            ->get();

        $alerts = collect();

        foreach ($patients as $patient) {
            $alerts = $alerts->merge($this->nutritionGapAlert($patient));
            $alerts = $alerts->merge($this->overdueTaskAlert($patient));
        }

        return $alerts->sortByDesc('severity_rank')->values();
    }

    private function nutritionGapAlert(object $patient): array
    {
        if (!Schema::hasTable('patient_meals')) {
            return [];
        }

        $lastMealDate = DB::table('patient_meals')
            ->where('user_id', $patient->user_id)
            ->where('status', 'confirmed')
            ->max('meal_date');

        $daysSince = $lastMealDate
            ? Carbon::parse($lastMealDate)->diffInDays(now())
            : null;

        if ($daysSince === null || $daysSince < 3) {
            return [];
        }

        return [(object) [
            'profile_id' => $patient->profile_id,
            'patient_name' => $patient->patient_name,
            'type' => 'nutrition',
            'severity' => $daysSince >= 6 ? 'danger' : 'warn',
            'severity_rank' => $daysSince >= 6 ? 2 : 1,
            'severity_label' => $daysSince >= 6 ? 'انقطاع طويل' : 'انقطاع متابعة',
            'title' => $patient->patient_name . ': ما سجّل وجبات من ' . $daysSince . ' يوم',
            'detail' => $lastMealDate
                ? 'آخر وجبة مسجّلة بتاريخ ' . Carbon::parse($lastMealDate)->locale('ar')->translatedFormat('j M Y')
                : 'ما سجّل أي وجبة منذ ما انضم.',
        ]];
    }

    private function overdueTaskAlert(object $patient): array
    {
        if (!Schema::hasTable('patient_tasks')) {
            return [];
        }

        $overdueCount = DB::table('patient_tasks')
            ->where('patient_user_id', $patient->user_id)
            ->where('source', 'doctor')
            ->where('status', '!=', 'completed')
            ->whereDate('task_date', '<', now()->toDateString())
            ->count();

        if ($overdueCount === 0) {
            return [];
        }

        return [(object) [
            'profile_id' => $patient->profile_id,
            'patient_name' => $patient->patient_name,
            'type' => 'task',
            'severity' => 'warn',
            'severity_rank' => 1,
            'severity_label' => 'مهمة متأخرة',
            'title' => $patient->patient_name . ': عنده ' . $overdueCount . ' مهمة متأخرة',
            'detail' => 'مهام حددتها إنت للمريض وفات وقتها بدون إنجاز.',
        ]];
    }
}
