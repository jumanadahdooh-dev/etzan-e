<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\DoctorProfile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DoctorDashboardController extends Controller
{
    private array $pages = [
        'dashboard' => 'الرئيسية',
        'requests' => 'طلبات الاستشارة',
        'patients' => 'مرضاي',
        'patient-details' => 'ملف المريض',
        'appointments' => 'المواعيد',
        'messages' => 'الرسائل',
        'meal-reviews' => 'مراجعة الوجبات AI',
        'plans' => 'الخطط الغذائية',
        'alerts' => 'تنبيهات المرضى',
        'articles' => 'مقالاتي',
        'reports' => 'التقارير',
        'profile' => 'ملفي الشخصي',
        'settings' => 'الإعدادات',
    ];

    /**
     * لوحة تحكم الطبيب — بيانات حقيقية بالكامل من قاعدة البيانات.
     * (قبل هيك كانت هاي الدالة بس بترجع view('doctor.dashboard') برقمين ثابتين).
     */
    public function dashboard(): View
    {
        $user = Auth::user();

        $doctorProfile = DoctorProfile::with('specialties')
            ->where('user_id', $user->id)
            ->first();

        // لو حساب الطبيب ما إله ملف طبيب مكتمل لسا (حالة حافة حقيقية، مش وهمية)
        if (!$doctorProfile) {
            // ما في ملف طبيب بعد، فمنطقياً ما في وجبات مرتبطة فيه لنعدّها
            $weeklyMealsCount = 0;

            return view('doctor.dashboard', [
                'pageTitle' => $this->pages['dashboard'],
                'activePage' => 'dashboard',
                'doctorProfile' => null,
                'stats' => $this->emptyStats(),
                'upcomingAppointments' => collect(),
                'todayPatients' => collect(),
                'recentReviews' => collect(),
                'pendingRequests' => collect(),
                'pendingRequestsCount' => 0,
                'weeklyChart' => [],
                'statusBreakdown' => ['confirmed' => 0, 'pending' => 0, 'completed' => 0, 'cancelled' => 0],
                'genderBreakdown' => ['male' => 0, 'female' => 0],
                'consultationBreakdown' => ['online' => 0, 'in_person' => 0],
                'sidebarBadges' => $this->emptySidebarBadges(),


                'weeklyMealsCount' => $weeklyMealsCount,
            ]);
        }

        $today = now()->toDateString();

        $appointmentsToday = Schema::hasTable('patient_appointments')
            ? $doctorProfile->appointments()->whereDate('appointment_date', $today)->count()
            : 0;

        $todayPatients = Schema::hasTable('patient_appointments')
            ? $doctorProfile->appointments()
                ->with('patient')
                ->whereDate('appointment_date', $today)
                ->orderBy('appointment_time')
                ->get()
            : collect();

        $activePatients = Schema::hasTable('patient_profiles') && Schema::hasColumn('patient_profiles', 'doctor_profile_id')
            ? $doctorProfile->patientProfiles()->count()
            : 0;

        $pendingRequestsCount = Schema::hasTable('patient_doctor_requests')
            ? $doctorProfile->patientRequests()->where('status', 'pending')->count()
            : 0;

        $pendingRequests = Schema::hasTable('patient_doctor_requests')
            ? $doctorProfile->patientRequests()
                ->with('patient')
                ->where('status', 'pending')
                ->latest()
                ->limit(4)
                ->get()
            : collect();

        $reviewsAvailable = Schema::hasTable('doctor_reviews');
        $avgRating = $reviewsAvailable
            ? round((float) $doctorProfile->reviews()->avg('rating'), 1)
            : 0;
        $reviewsCount = $reviewsAvailable
            ? $doctorProfile->reviews()->count()
            : 0;

        $upcomingAppointments = Schema::hasTable('patient_appointments')
            ? $doctorProfile->appointments()
                ->with('patient')
                ->whereDate('appointment_date', '>=', $today)
                ->where('status', '!=', 'cancelled')
                ->orderBy('appointment_date')
                ->orderBy('appointment_time')
                ->limit(5)
                ->get()
            : collect();

        $recentReviews = $reviewsAvailable
            ? $doctorProfile->reviews()
                ->with('patient')
                ->latest()
                ->limit(3)
                ->get()
            : collect();

        // اتجاه أسبوعي حقيقي لمواعيد الطبيب (آخر 7 أيام مقابل الـ7 اللي قبلها)
        $appointmentsTrend = Schema::hasTable('patient_appointments')
            ? $this->weeklyTrend($doctorProfile->id)
            : ['current' => 0, 'previous' => 0, 'percent' => 0, 'direction' => 'flat'];

        // توزيع المواعيد يوم بيوم لآخر 7 أيام — للرسم البياني بالداشبورد
        $weeklyChart = Schema::hasTable('patient_appointments')
            ? $this->weeklyChart($doctorProfile->id)
            : [];

        // توزيع حالات المواعيد (لمخطط دائري حقيقي) + إجمالي الشهر ونسبة الإنجاز
        $statusBreakdown = ['confirmed' => 0, 'pending' => 0, 'completed' => 0, 'cancelled' => 0];
        $monthTotal = 0;
        $completionRate = 0;

        if (Schema::hasTable('patient_appointments')) {
            $monthRows = $doctorProfile->appointments()
                ->whereYear('appointment_date', now()->year)
                ->whereMonth('appointment_date', now()->month)
                ->get();

            $monthTotal = $monthRows->count();

            foreach ($monthRows->groupBy('status') as $status => $rows) {
                if (array_key_exists($status, $statusBreakdown)) {
                    $statusBreakdown[$status] = $rows->count();
                }
            }

            $completionRate = $monthTotal > 0
                ? (int) round(($statusBreakdown['completed'] / $monthTotal) * 100)
                : 0;
        }

        // توزيع المرضى حسب الجنس — بيانات حقيقية من patient_profiles.gender
        $genderBreakdown = ['male' => 0, 'female' => 0];
        if (Schema::hasTable('patient_profiles') && Schema::hasColumn('patient_profiles', 'gender')) {
            foreach ($doctorProfile->patientProfiles()->whereNotNull('gender')->get() as $pp) {
                if (array_key_exists($pp->gender, $genderBreakdown)) {
                    $genderBreakdown[$pp->gender]++;
                }
            }
        }

        // توزيع نوع الاستشارة (عن بُعد / حضوري) — لكل مواعيد الطبيب
        $consultationBreakdown = ['online' => 0, 'in_person' => 0];
        if (Schema::hasTable('patient_appointments')) {
            $consultationBreakdown['online'] = $doctorProfile->appointments()->where('consultation_type', 'online')->count();
            $consultationBreakdown['in_person'] = $doctorProfile->appointments()->where('consultation_type', '!=', 'online')->count();
        }

        return view('doctor.dashboard', [
            'pageTitle' => $this->pages['dashboard'],
            'activePage' => 'dashboard',
            'doctorProfile' => $doctorProfile,
            'stats' => [
                'appointments_today' => $appointmentsToday,
                'active_patients' => $activePatients,
                'pending_requests' => $pendingRequestsCount,
                'avg_rating' => $avgRating,
                'reviews_count' => $reviewsCount,
                'appointments_trend' => $appointmentsTrend,
                'month_total' => $monthTotal,
                'completion_rate' => $completionRate,
            ],
            'upcomingAppointments' => $upcomingAppointments,
            'todayPatients' => $todayPatients,
            'recentReviews' => $recentReviews,
            'pendingRequests' => $pendingRequests,
            'pendingRequestsCount' => $pendingRequestsCount,
            'weeklyChart' => $weeklyChart,
            'statusBreakdown' => $statusBreakdown,
            'genderBreakdown' => $genderBreakdown,
            'consultationBreakdown' => $consultationBreakdown,
            'sidebarBadges' => [
                // بس هاد الرقم حقيقي فعلياً (مرتبط مباشرة ببيانات جبناها هلق).
                // باقي أرقام القائمة الجانبية (رسائل، مراجعة وجبات، تنبيهات، مقالات)
                // ما إلها جدول أو منطق حقيقي بالمشروع لحد الآن، فخليناها فاضية
                // بدل ما نعرض رقم وهمي — هاي صفحات لسا برا نطاق هاد التحديث.
                'requests' => $pendingRequestsCount,
                'messages' => null,
                'meal-reviews' => null,
                'alerts' => null,
                'articles' => null,
            ],
        ]);
    }

    /**
     * تبديل حالة "متاح للاستشارات" — أول تفعيل حقيقي لهاد الحقل
     * (كان عمود is_available موجود بقاعدة البيانات بس ولا واجهة كانت تغيّره).
     */
    public function toggleAvailability()
    {
        $doctorProfile = DoctorProfile::where('user_id', Auth::id())->first();

        abort_unless($doctorProfile, 404);

        $doctorProfile->is_available = !$doctorProfile->is_available;
        $doctorProfile->save();

        return back()->with('success', $doctorProfile->is_available
            ? 'صرت متاح لاستقبال استشارات جديدة.'
            : 'صرت غير متاح مؤقتاً لاستشارات جديدة.');
    }

    public function page(string $page): View
    {
        abort_unless(array_key_exists($page, $this->pages), 404);

        return view('doctor.' . $page, [
            'pageTitle' => $this->pages[$page],
            'activePage' => $page,
            'unreadMessages' => 12,
            'patientAlerts' => 5,
        ]);
    }

    /**
     * مقارنة عدد مواعيد آخر 7 أيام مع الـ7 اللي قبلها (نافذة متحركة).
     * نفس المنطق المستخدم بلوحة تحكم الإدارة، عشان الاتساق.
     */
    private function weeklyTrend(int $doctorProfileId): array
    {
        $now = now();
        $currentStart = $now->copy()->subDays(6)->startOfDay();
        $previousStart = $now->copy()->subDays(13)->startOfDay();
        $previousEnd = $now->copy()->subDays(7)->endOfDay();

        $current = \App\Models\PatientAppointment::where('doctor_profile_id', $doctorProfileId)
            ->whereBetween('created_at', [$currentStart, $now])
            ->count();

        $previous = \App\Models\PatientAppointment::where('doctor_profile_id', $doctorProfileId)
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->count();

        $percent = $previous > 0
            ? (int) round((($current - $previous) / $previous) * 100)
            : ($current > 0 ? 100 : 0);

        $direction = $current > $previous ? 'up' : ($current < $previous ? 'down' : 'flat');

        return compact('current', 'previous') + ['percent' => abs($percent), 'direction' => $direction];
    }

    /**
     * عدد المواعيد لكل يوم بآخر 7 أيام — بيانات حقيقية للرسم البياني بالداشبورد.
     */
    private function weeklyChart(int $doctorProfileId): array
    {
        $days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = \App\Models\PatientAppointment::where('doctor_profile_id', $doctorProfileId)
                ->whereDate('appointment_date', $date->toDateString())
                ->count();

            $days[] = [
                'label' => $date->locale('ar')->translatedFormat('D'),
                'date' => $date->toDateString(),
                'count' => $count,
            ];
        }

        return $days;
    }

    private function emptyStats(): array
    {
        return [
            'appointments_today' => 0,
            'active_patients' => 0,
            'pending_requests' => 0,
            'avg_rating' => 0,
            'reviews_count' => 0,
            'appointments_trend' => ['current' => 0, 'previous' => 0, 'percent' => 0, 'direction' => 'flat'],
            'month_total' => 0,
            'completion_rate' => 0,
        ];
    }

    private function emptySidebarBadges(): array
    {
        return ['requests' => 0, 'messages' => null, 'meal-reviews' => null, 'alerts' => null, 'articles' => null];
    }
}
