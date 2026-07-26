<?php

namespace App\Console\Commands;

use App\Services\AppNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckCalorieOverageAlerts extends Command
{
    protected $signature = 'patient-alerts:calorie-overage';

    protected $description = 'Notify a doctor when an approved patient exceeded their calorie goal by 20%+ for 3 consecutive days.';

    private const OVERAGE_MULTIPLIER = 1.20;

    private const CONSECUTIVE_DAYS = 3;

    /** لا نرسل نفس التنبيه لنفس المريض إذا كان في تنبيه حديث خلال هالمدة، حتى ما نزعج الطبيب كل يوم لنفس الاستمرار */
    private const RENOTIFY_AFTER_DAYS = 3;

    public function handle(): int
    {
        if (! Schema::hasTable('patient_meals') || ! Schema::hasTable('patient_daily_calorie_goals')) {
            $this->info('Required tables missing, skipping.');

            return self::SUCCESS;
        }

        // آخر 3 أيام كاملة (منبدأ من أمس، لأن اليوم الحالي لسا ما خلص وما منقدر نحكم عليه)
        $checkDates = collect(range(1, self::CONSECUTIVE_DAYS))
            ->map(fn (int $daysAgo) => now()->subDays($daysAgo)->toDateString())
            ->values();

        $patients = DB::table('patient_profiles')
            ->join('doctor_profiles', 'doctor_profiles.id', '=', 'patient_profiles.doctor_profile_id')
            ->join('users as patient_user', 'patient_user.id', '=', 'patient_profiles.user_id')
            ->where('patient_profiles.doctor_request_status', 'approved')
            ->select(
                'patient_profiles.id as profile_id',
                'patient_profiles.user_id',
                'patient_user.name as patient_name',
                'doctor_profiles.user_id as doctor_user_id'
            )
            ->get();

        if ($patients->isEmpty()) {
            $this->info('No approved patients to check.');

            return self::SUCCESS;
        }

        $userIds = $patients->pluck('user_id')->all();

        // مجموع سعرات كل مريض لكل يوم من الأيام التلاتة — استعلام واحد لكل المرضى بدل استعلام لكل مريض (N+1)
        $mealTotals = DB::table('patient_meals')
            ->whereIn('user_id', $userIds)
            ->where('status', 'confirmed')
            ->whereIn('meal_date', $checkDates)
            ->groupBy('user_id', 'meal_date')
            ->select('user_id', 'meal_date', DB::raw('SUM(calories) as total_calories'))
            ->get()
            ->groupBy('user_id');

        // هدف السعرات لكل مريض لنفس الأيام — نفس المنطق، استعلام واحد
        $goals = DB::table('patient_daily_calorie_goals')
            ->whereIn('user_id', $userIds)
            ->whereIn('goal_date', $checkDates)
            ->whereIn('status', ['approved', 'suggested'])
            ->select('user_id', 'goal_date', 'calories_goal')
            ->get()
            ->groupBy('user_id');

        // آخر تنبيه مماثل انبعت لأي مريض من هدول، حتى ما نكرر لو استمر نفس النمط
        $recentAlerts = DB::table('app_notifications')
            ->where('type', 'calorie_overage_alert')
            ->whereIn('related_id', $patients->pluck('profile_id')->all())
            ->where('related_type', 'patient_profile')
            ->where('created_at', '>=', now()->subDays(self::RENOTIFY_AFTER_DAYS))
            ->pluck('related_id')
            ->unique();

        $notified = 0;

        foreach ($patients as $patient) {
            if (! $patient->doctor_user_id || $recentAlerts->contains($patient->profile_id)) {
                continue;
            }

            $patientMeals = $mealTotals->get($patient->user_id, collect())->keyBy('meal_date');
            $patientGoals = $goals->get($patient->user_id, collect())->keyBy('goal_date');

            $exceededAllDays = true;
            $worstDayPercent = 0;

            foreach ($checkDates as $date) {
                $goalRow = $patientGoals->get($date);
                $mealRow = $patientMeals->get($date);

                $goalCalories = (int) ($goalRow->calories_goal ?? 0);
                $actualCalories = (int) ($mealRow->total_calories ?? 0);

                if ($goalCalories <= 0 || $actualCalories <= 0) {
                    $exceededAllDays = false;
                    break;
                }

                if ($actualCalories < $goalCalories * self::OVERAGE_MULTIPLIER) {
                    $exceededAllDays = false;
                    break;
                }

                $worstDayPercent = max($worstDayPercent, (int) round((($actualCalories / $goalCalories) - 1) * 100));
            }

            if (! $exceededAllDays) {
                continue;
            }

            app(AppNotificationService::class)->send(
                recipientUserId: (int) $patient->doctor_user_id,
                recipientRole: 'doctor',
                type: 'calorie_overage_alert',
                title: 'تجاوز سعرات متكرر: ' . $patient->patient_name,
                body: 'المريض تجاوز هدف السعرات بنسبة تزيد عن 20% لثلاثة أيام متتالية (أعلى تجاوز: +' . $worstDayPercent . '%).',
                url: route('doctor.patient-profile.show', $patient->profile_id),
                relatedId: (int) $patient->profile_id,
                relatedType: 'patient_profile',
                data: [
                    'profile_id' => $patient->profile_id,
                    'check_dates' => $checkDates->values()->all(),
                    'worst_day_percent' => $worstDayPercent,
                ]
            );

            $notified++;
        }

        $this->info("Calorie overage check complete. notified={$notified}");

        return self::SUCCESS;
    }
}
