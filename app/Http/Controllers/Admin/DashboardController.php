<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();
        $monthStart = now()->copy()->startOfMonth();
        $monthEnd = now()->copy()->endOfMonth();

        $totalUsers = $this->countTable('users');
        $totalPatients = $this->countWhere('users', 'role', 'patient');
        $totalDoctors = $this->countWhere('users', 'role', 'doctor');
        $totalAdmins = $this->countWhere('users', 'role', 'admin');

        $patientProfiles = $this->countTable('patient_profiles');
        $doctorProfiles = $this->countTable('doctor_profiles');
        $completedProfiles = $this->countWhere('patient_profiles', 'profile_completed', 1);
        $patientsWithDoctor = $this->countWhere('patient_profiles', 'doctor_request_status', 'approved');

        $doctorApplicationsTotal = $this->countTable('doctor_applications');
        $doctorApplicationsPending = $this->countWhere('doctor_applications', 'status', 'pending');
        $doctorApplicationsApproved = $this->countWhere('doctor_applications', 'status', 'approved');
        $doctorApplicationsRejected = $this->countWhere('doctor_applications', 'status', 'rejected');

        $articlesTotal = $this->countTable('articles');
        $articlesPublished = $this->countWhere('articles', 'status', 'published');
        $articlesDraft = $this->countWhere('articles', 'status', 'draft');
        $articlesPending = $this->countWhere('articles', 'status', 'pending_review');
        $articlesRejected = $this->countWhere('articles', 'status', 'rejected');
        $articlesAi = $this->countWhere('articles', 'generated_by_ai', 1);
        $dailyContent = $this->countWhereIn('articles', 'article_type', ['daily_idea', 'daily_wisdom', 'motivation_message']);

        $appointmentsTotal = $this->countTable('patient_appointments');
        $appointmentsPending = $this->countWhere('patient_appointments', 'status', 'pending');
        $appointmentsConfirmed = $this->countWhereIn('patient_appointments', 'status', ['confirmed', 'approved']);
        $appointmentsCompleted = $this->countWhere('patient_appointments', 'status', 'completed');
        $appointmentsCancelled = $this->countWhereIn('patient_appointments', 'status', ['cancelled', 'rejected']);
        $appointmentsReschedule = $this->countWhere('patient_appointments', 'status', 'reschedule_requested');
        $appointmentsToday = $this->countWhere('patient_appointments', 'appointment_date', $today);
        $appointmentsUpcoming = $this->countUpcomingAppointments($today);

        $tasksTotal = $this->countTable('patient_tasks');
        $tasksPending = $this->countWhere('patient_tasks', 'status', 'pending');
        $tasksCompleted = $this->countWhere('patient_tasks', 'status', 'completed');
        $tasksDueToday = $this->countWhere('patient_tasks', 'due_date', $today);

        $mealsToday = $this->countWhere('patient_meals', 'meal_date', $today);
        $mealsThisMonth = $this->countBetweenDates('patient_meals', 'meal_date', $monthStart, $monthEnd);
        $mealsAi = $this->countWhere('patient_meals', 'source', 'ai');
        $caloriesThisMonth = $this->sumBetweenDates('patient_meals', 'meal_date', 'calories', $monthStart, $monthEnd);

        $openConversations = $this->countWhere('conversations', 'status', 'open');
        $unreadAdminMessages = $this->sumColumn('conversations', 'unread_by_admin');
        $unreadNotifications = $this->countNull('admin_notifications', 'read_at');

        $stats = [
            'total_users' => $totalUsers,
            'total_patients' => $totalPatients,
            'total_doctors' => $totalDoctors,
            'total_admins' => $totalAdmins,
            'new_users_this_month' => $this->countCreatedBetween('users', $monthStart, $monthEnd),

            'patient_profiles' => $patientProfiles,
            'doctor_profiles' => $doctorProfiles,
            'completed_profiles' => $completedProfiles,
            'patients_with_doctor' => $patientsWithDoctor,

            'doctor_applications_total' => $doctorApplicationsTotal,
            'pending_doctor_applications' => $doctorApplicationsPending,
            'approved_doctor_applications' => $doctorApplicationsApproved,
            'rejected_doctor_applications' => $doctorApplicationsRejected,

            'articles_total' => $articlesTotal,
            'articles_published' => $articlesPublished,
            'articles_draft' => $articlesDraft,
            'articles_pending_review' => $articlesPending,
            'articles_rejected' => $articlesRejected,
            'articles_ai' => $articlesAi,
            'articles_daily_content' => $dailyContent,
            'article_categories' => $this->countTable('article_categories'),
            'specialties' => $this->countTable('specialties'),

            'appointments_total' => $appointmentsTotal,
            'appointments_pending' => $appointmentsPending,
            'appointments_confirmed' => $appointmentsConfirmed,
            'appointments_completed' => $appointmentsCompleted,
            'appointments_cancelled' => $appointmentsCancelled,
            'appointments_reschedule' => $appointmentsReschedule,
            'appointments_today' => $appointmentsToday,
            'appointments_upcoming' => $appointmentsUpcoming,

            'tasks_total' => $tasksTotal,
            'tasks_pending' => $tasksPending,
            'tasks_completed' => $tasksCompleted,
            'tasks_due_today' => $tasksDueToday,

            'meals_today' => $mealsToday,
            'meals_this_month' => $mealsThisMonth,
            'meals_ai' => $mealsAi,
            'calories_this_month' => $caloriesThisMonth,
            'daily_calorie_goals' => $this->countTable('patient_daily_calorie_goals'),

            'conversations_total' => $this->countTable('conversations'),
            'open_conversations' => $openConversations,
            'messages_total' => $this->countTable('messages'),
            'unread_admin_messages' => $unreadAdminMessages,
            'admin_notifications_unread' => $unreadNotifications,

            'doctor_reviews' => $this->countTable('doctor_reviews'),
            'average_doctor_rating' => $this->avgColumn('doctor_reviews', 'rating'),
            'patient_doctor_requests_pending' => $this->countWhere('patient_doctor_requests', 'status', 'pending'),
        ];

        $rates = [
            'profile_completion' => $this->percent($completedProfiles, $patientProfiles),
            'doctor_approval' => $this->percent($doctorApplicationsApproved, $doctorApplicationsTotal),
            'published_articles' => $this->percent($articlesPublished, $articlesTotal),
            'ai_content' => $this->percent($articlesAi, $articlesTotal),
            'appointments_success' => $this->percent($appointmentsConfirmed + $appointmentsCompleted, $appointmentsTotal),
            'tasks_completion' => $this->percent($tasksCompleted, $tasksTotal),
            'patients_with_doctor' => $this->percent($patientsWithDoctor, $patientProfiles),
        ];

        $quickHealth = [
            'needs_attention' => $doctorApplicationsPending + $appointmentsPending + $appointmentsReschedule + $articlesPending + $unreadAdminMessages + $unreadNotifications,
            'content_waiting' => $articlesDraft + $articlesPending,
            'engagement_today' => $appointmentsToday + $mealsToday + $tasksDueToday,
        ];

        $charts = [
            'monthly_users' => $this->monthlyCounts('users', 'created_at', 6),
            'monthly_appointments' => $this->monthlyCounts('patient_appointments', 'appointment_date', 6),
            'roles' => [
                'labels' => ['مرضى', 'أطباء', 'إدارة'],
                'values' => [$totalPatients, $totalDoctors, $totalAdmins],
            ],
            'appointments_status' => [
                'labels' => ['معلقة', 'مؤكدة', 'مكتملة', 'ملغاة', 'إعادة جدولة'],
                'values' => [$appointmentsPending, $appointmentsConfirmed, $appointmentsCompleted, $appointmentsCancelled, $appointmentsReschedule],
            ],
            'articles_status' => [
                'labels' => ['منشورة', 'مسودة', 'تحت المراجعة', 'مرفوضة'],
                'values' => [$articlesPublished, $articlesDraft, $articlesPending, $articlesRejected],
            ],
        ];

        $latestApplications = $this->latestRows('doctor_applications', 6);
        $latestUsers = $this->latestRows('users', 6);
        $latestArticles = $this->latestRows('articles', 6);
        $topDoctors = $this->topDoctorsByPatients();
        $peakHours = $this->peakHours();

        // مقارنة أسبوعية (آخر 7 أيام مقابل الـ 7 أيام اللي قبلها) لأرقام الهيرو
        // في لوحة تحكم الإدارة، عشان يبين اتجاه كل مؤشر مش بس رقمه اللحظي.
        $heroTrends = [
            'appointments' => $this->weeklyTrend('patient_appointments', 'created_at'),
            'doctor_applications' => $this->weeklyTrend('doctor_applications', 'created_at'),
            'articles_published' => $this->weeklyTrend('articles', 'published_at', 'status', 'published'),
            'support_messages' => $this->weeklyTrend('messages', 'created_at', 'sender_type', 'user'),
        ];

        return view('admin.dashboard', compact(
            'stats',
            'rates',
            'quickHealth',
            'charts',
            'latestApplications',
            'latestUsers',
            'latestArticles',
            'topDoctors',
            'peakHours',
            'heroTrends'
        ));
    }

    /**
     * ساعات الذروة: بنجمع مواعيد المرضى الحقيقية من جدول patient_appointments
     * حسب عمود appointment_time (نص متل "14:30")، ونوزّعها على 8 فترات مدة كل
     * وحدة ساعتين (8ص، 10ص، 12م، 2م، 4م، 6م، 8م، 10م) بتغطي دوام العيادة المعتاد.
     * أي موعد قبل الساعة 8 صباحاً بينضم لأقرب فترة (8ص) بدل ما يضيع من الحساب.
     */
    private function peakHours(): array
    {
        $labels = ['8ص', '10ص', '12م', '2م', '4م', '6م', '8م', '10م'];
        $bucketStarts = [8, 10, 12, 14, 16, 18, 20, 22];
        $values = array_fill(0, count($labels), 0);

        if ($this->tableExists('patient_appointments') && $this->columnExists('patient_appointments', 'appointment_time')) {
            $rows = DB::table('patient_appointments')
                ->selectRaw('SUBSTRING(appointment_time, 1, 2) as hour_part, COUNT(*) as total')
                ->groupBy('hour_part')
                ->pluck('total', 'hour_part');

            foreach ($rows as $hourPart => $count) {
                $hour = (int) $hourPart;
                $bucketIndex = 0;

                foreach ($bucketStarts as $i => $start) {
                    $end = $bucketStarts[$i + 1] ?? 24;
                    if ($hour >= $start && $hour < $end) {
                        $bucketIndex = $i;
                        break;
                    }
                }

                $values[$bucketIndex] += (int) $count;
            }
        }

        $hasData = array_sum($values) > 0;
        $max = $hasData ? max($values) : 1;
        $peakIndex = $hasData ? array_search(max($values), $values) : 0;

        return [
            'labels' => $labels,
            'values' => $values,
            'max' => $max,
            'peak_index' => $peakIndex,
            'peak_label' => $labels[$peakIndex],
            'has_data' => $hasData,
        ];
    }

    private function latestRows(string $table, int $limit)
    {
        if (!$this->tableExists($table)) {
            return collect();
        }

        return DB::table($table)
            ->when($this->columnExists($table, 'created_at'), fn ($query) => $query->orderByDesc('created_at'))
            ->limit($limit)
            ->get();
    }

    private function monthlyCounts(string $table, string $dateColumn, int $months = 6): array
    {
        $labels = [];
        $values = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->copy()->subMonths($i);
            $start = $date->copy()->startOfMonth();
            $end = $date->copy()->endOfMonth();

            $labels[] = $date->translatedFormat('M Y');
            $values[] = $this->countBetweenDates($table, $dateColumn, $start, $end);
        }

        return compact('labels', 'values');
    }

    private function topDoctorsByPatients()
    {
        if (!$this->tableExists('doctor_profiles') || !$this->tableExists('users')) {
            return collect();
        }

        $query = DB::table('doctor_profiles')
            ->leftJoin('users', 'users.id', '=', 'doctor_profiles.user_id')
            ->select('doctor_profiles.id', 'users.name', 'users.email');

        if ($this->tableExists('patient_profiles') && $this->columnExists('patient_profiles', 'doctor_profile_id')) {
            $query->selectRaw("(
                select count(*) from patient_profiles
                where patient_profiles.doctor_profile_id = doctor_profiles.id
                " . ($this->columnExists('patient_profiles', 'doctor_request_status') ? "and patient_profiles.doctor_request_status = 'approved'" : '') . "
            ) as patients_count");
        } else {
            $query->selectRaw('0 as patients_count');
        }

        if ($this->tableExists('patient_appointments') && $this->columnExists('patient_appointments', 'doctor_profile_id')) {
            $query->selectRaw('(select count(*) from patient_appointments where patient_appointments.doctor_profile_id = doctor_profiles.id) as appointments_count');
        } else {
            $query->selectRaw('0 as appointments_count');
        }

        return $query->orderByDesc('patients_count')->limit(5)->get();
    }

    private function tableExists(string $table): bool
    {
        return Schema::hasTable($table);
    }

    private function columnExists(string $table, string $column): bool
    {
        return $this->tableExists($table) && Schema::hasColumn($table, $column);
    }

    private function countTable(string $table): int
    {
        return $this->tableExists($table) ? (int) DB::table($table)->count() : 0;
    }

    private function countWhere(string $table, string $column, $value): int
    {
        return $this->columnExists($table, $column) ? (int) DB::table($table)->where($column, $value)->count() : 0;
    }

    private function countWhereIn(string $table, string $column, array $values): int
    {
        return $this->columnExists($table, $column) ? (int) DB::table($table)->whereIn($column, $values)->count() : 0;
    }

    private function countNull(string $table, string $column): int
    {
        return $this->columnExists($table, $column) ? (int) DB::table($table)->whereNull($column)->count() : 0;
    }

    private function sumColumn(string $table, string $column): int
    {
        return $this->columnExists($table, $column) ? (int) DB::table($table)->sum($column) : 0;
    }

    private function avgColumn(string $table, string $column): float
    {
        return $this->columnExists($table, $column) ? round((float) DB::table($table)->avg($column), 1) : 0.0;
    }

    private function countCreatedBetween(string $table, $start, $end): int
    {
        return $this->columnExists($table, 'created_at')
            ? (int) DB::table($table)->whereBetween('created_at', [$start, $end])->count()
            : 0;
    }

    private function countBetweenDates(string $table, string $column, $start, $end): int
    {
        return $this->columnExists($table, $column)
            ? (int) DB::table($table)->whereBetween($column, [$start->toDateString(), $end->toDateString()])->count()
            : 0;
    }

    private function sumBetweenDates(string $table, string $dateColumn, string $sumColumn, $start, $end): int
    {
        return $this->columnExists($table, $dateColumn) && $this->columnExists($table, $sumColumn)
            ? (int) DB::table($table)->whereBetween($dateColumn, [$start->toDateString(), $end->toDateString()])->sum($sumColumn)
            : 0;
    }

    private function countUpcomingAppointments(string $today): int
    {
        if (!$this->columnExists('patient_appointments', 'appointment_date')) {
            return 0;
        }

        return (int) DB::table('patient_appointments')
            ->whereDate('appointment_date', '>=', $today)
            ->when($this->columnExists('patient_appointments', 'status'), function ($query) {
                $query->whereIn('status', ['pending', 'confirmed', 'approved', 'reschedule_requested']);
            })
            ->count();
    }

    /**
     * بيقارن عدد السجلات بآخر 7 أيام مع الـ 7 أيام اللي قبلها (نافذة متحركة
     * مش أسبوع تقويمي)، عشان نطلع اتجاه واضح لكل رقم بالهيرو.
     */
    private function weeklyTrend(string $table, string $dateColumn, ?string $whereColumn = null, $whereValue = null): array
    {
        if (!$this->columnExists($table, $dateColumn)) {
            return ['current' => 0, 'previous' => 0, 'percent' => 0, 'direction' => 'flat'];
        }

        $now = now();
        $currentStart = $now->copy()->subDays(6)->startOfDay();
        $previousStart = $now->copy()->subDays(13)->startOfDay();
        $previousEnd = $now->copy()->subDays(7)->endOfDay();

        $applyWhere = function ($query) use ($whereColumn, $whereValue) {
            if ($whereColumn !== null && $this->columnExists($query->from, $whereColumn)) {
                $query->where($whereColumn, $whereValue);
            }
            return $query;
        };

        $currentQuery = DB::table($table)->whereBetween($dateColumn, [$currentStart, $now]);
        $applyWhere($currentQuery);
        $current = (int) $currentQuery->count();

        $previousQuery = DB::table($table)->whereBetween($dateColumn, [$previousStart, $previousEnd]);
        $applyWhere($previousQuery);
        $previous = (int) $previousQuery->count();

        if ($previous <= 0) {
            $percent = $current > 0 ? 100 : 0;
        } else {
            $percent = (int) round((($current - $previous) / $previous) * 100);
        }

        $direction = 'flat';
        if ($current > $previous) {
            $direction = 'up';
        } elseif ($current < $previous) {
            $direction = 'down';
        }

        return [
            'current' => $current,
            'previous' => $previous,
            'percent' => abs($percent),
            'direction' => $direction,
        ];
    }

    private function percent(int $value, int $total): int
    {
        if ($total <= 0) {
            return 0;
        }

        return (int) min(100, max(0, round(($value / $total) * 100)));
    }
}
