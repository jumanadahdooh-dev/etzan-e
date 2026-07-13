<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    /**
     * عرض قائمة المستخدمين
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search'));
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $verified = $request->get('verified');
        $roleFilter = $request->get('role');
        $accountStatus = $request->get('account_status');

        $query = User::query()
            ->with(['doctorProfile', 'patientProfile'])
            ->latest();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (in_array($roleFilter, ['admin', 'doctor', 'patient'], true)) {
            $query->where('role', $roleFilter);
        } else {
            $roleFilter = '';
        }

        if (in_array($accountStatus, ['active', 'suspended'], true)) {
            $query->where('status', $accountStatus);
        } else {
            $accountStatus = '';
        }

        if (!empty($dateFrom)) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if (!empty($dateTo)) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        if ($verified === 'verified') {
            $query->whereNotNull('email_verified_at');
        } elseif ($verified === 'unverified') {
            $query->whereNull('email_verified_at');
        }

        $allUsers = $query->limit(300)->get();
        $this->attachAdminUserInsights($allUsers);

        $groupedUsers = [
            'admin' => $allUsers->where('role', 'admin')->values(),
            'doctor' => $allUsers->where('role', 'doctor')->values(),
            'patient' => $allUsers->where('role', 'patient')->values(),
        ];

        $total = User::count();
        $verifiedCount = User::whereNotNull('email_verified_at')->count();
        $unverified = max($total - $verifiedCount, 0);
        $verifiedRate = $total > 0 ? round(($verifiedCount / $total) * 100) : 0;

        $stats = [
            'total' => $total,
            'admins' => User::where('role', 'admin')->count(),
            'doctors' => User::where('role', 'doctor')->count(),
            'patients' => User::where('role', 'patient')->count(),

            'today' => User::whereDate('created_at', today())->count(),
            'new_today' => User::whereDate('created_at', today())->count(),

            'this_month' => User::whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->count(),

            'new_this_month' => User::whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->count(),

            'verified' => $verifiedCount,
            'verified_users' => $verifiedCount,
            'unverified' => $unverified,
            'unverified_users' => $unverified,
            'verified_rate' => $verifiedRate,
            'verified_percentage' => $verifiedRate,

            'doctor_profiles' => Schema::hasTable('doctor_profiles') ? DB::table('doctor_profiles')->count() : 0,
            'patient_profiles' => Schema::hasTable('patient_profiles') ? DB::table('patient_profiles')->count() : 0,
            'patient_profiles_completed' => $this->countCompletedPatientProfiles(),

            'active' => Schema::hasColumn('users', 'status') ? User::where('status', '!=', 'suspended')->count() : $total,
            'suspended' => Schema::hasColumn('users', 'status') ? User::where('status', 'suspended')->count() : 0,
        ];

        $filters = [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'verified' => $verified,
            'role' => $roleFilter,
            'account_status' => $accountStatus,
        ];

        $recentActivity = Schema::hasTable('admin_activity_logs')
            ? DB::table('admin_activity_logs')
                ->leftJoin('users', 'users.id', '=', 'admin_activity_logs.admin_id')
                ->select('admin_activity_logs.*', 'users.name as admin_name')
                ->orderByDesc('admin_activity_logs.created_at')
                ->limit(8)
                ->get()
            : collect();

        return view('admin.admin-users', compact('groupedUsers', 'stats', 'search', 'filters', 'recentActivity'));
    }

    /**
     * جلب بيانات مستخدم واحد (للـ Modal)
     * هاي الدالة كانت ناقصة تماماً — الـ JS كان بيعمل fetch لراوت GET
     * /admin/users/{id} بس ما كان في route ولا method يردّ عليه، فكان
     * بيرجع 404 وتفضل نافذة التعديل عالقة على "جاري التحميل...".
     */
    public function show($id)
    {
        $user = User::with(['doctorProfile', 'patientProfile'])->find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'تعذر الوصول للبيانات: المستخدم غير موجود.'
            ], 404);
        }

        $collection = collect([$user]);
        $this->attachAdminUserInsights($collection);

        $phone = optional($user->patientProfile)->phone;

        $activity = Schema::hasTable('admin_activity_logs')
            ? DB::table('admin_activity_logs')
                ->where('target_type', 'user')
                ->where('target_id', $user->id)
                ->orderByDesc('created_at')
                ->limit(6)
                ->get()
                ->map(fn ($log) => [
                    'action' => $log->action,
                    'description' => $log->description ?? $log->action,
                    'time' => \Carbon\Carbon::parse($log->created_at)->locale('ar')->diffForHumans(),
                ])
            : [];

        return response()->json([
            'success' => true,
            'id'      => $user->id,
            'name'    => $user->name,
            'email'   => $user->email,
            'phone'   => $phone ?: null,
            'role'    => $user->role,
            'status'  => $user->status ?? 'active',
            'created_at' => $user->created_at?->format('Y/m/d'),
            'last_login_at' => $user->last_login_at?->locale('ar')->diffForHumans() ?? 'لم يسجل الدخول بعد',
            'verified' => (bool) $user->email_verified_at,
            'insights' => $user->admin_insights ?? [],
            'activity' => $activity,
        ]);
    }

    /*
     * تحديث بيانات المستخدم (آمن للـ AJAX)
     */
    public function update(Request $request, $id)
    {
        try {
            // جلب المستخدم يدوياً لمنع أخطاء الـ Route Binding
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'تعذر الوصول للبيانات: المستخدم غير موجود.'
                ], 404);
            }

            $validated = $request->validate([
                'name'     => ['required', 'string', 'max:255'],
                'email'    => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
                'role'     => ['required', Rule::in(['admin', 'doctor', 'patient'])],
                'password' => ['nullable', 'string', 'min:8'], // أزلت confirmed مؤقتاً لتسهيل الاختبار
            ]);

            // حماية: الأدمن ما يقدر يغير دوره لغير أدمن
            if (auth()->id() === $user->id && $validated['role'] !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكنك إزالة صلاحية الأدمن من حسابك الحالي.',
                    'errors'  => ['role' => ['لا يمكنك إزالة صلاحية الأدمن من حسابك الحالي.']]
                ], 422);
            }

            $user->name  = $validated['name'];
            $user->email = $validated['email'];
            $user->role  = $validated['role'];

            if (!empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }

            $user->save();

            $this->logActivity('تعديل بيانات', $user, "تم تعديل بيانات المستخدم {$user->name}");

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث بيانات المستخدم بنجاح.',
                'user'    => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $user->role,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'خطأ في التحقق من البيانات.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'حدث خطأ بالخادم: ' . $e->getMessage()], 500);
        }
    }

    /*
     * حذف المستخدم نهائياً (آمن للـ AJAX)
     */
    public function destroy($id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json(['success' => false, 'message' => 'تعذر الوصول للبيانات: المستخدم غير موجود.'], 404);
            }

            if (auth()->id() === $user->id) {
                return response()->json(['success' => false, 'message' => 'لا يمكنك حذف حسابك الحالي.'], 403);
            }

            $userName = $user->name;

            DB::transaction(function () use ($user) {
                $user->loadMissing(['doctorProfile', 'patientProfile']);
                $doctorProfileId  = $user->doctorProfile?->id;
                $patientProfileId = $user->patientProfile?->id;

                $this->deleteByColumnIfExists('patient_appointments', 'user_id', $user->id);
                $this->deleteByColumnIfExists('patient_meals', 'user_id', $user->id);
                $this->deleteByColumnIfExists('patient_tasks', 'user_id', $user->id);

                if ($doctorProfileId) {
                    $this->deleteByColumnIfExists('patient_appointments', 'doctor_profile_id', $doctorProfileId);
                    $this->deleteByColumnIfExists('doctor_reviews', 'doctor_profile_id', $doctorProfileId);

                    if (Schema::hasTable('patient_profiles') && Schema::hasColumn('patient_profiles', 'doctor_profile_id')) {
                        DB::table('patient_profiles')->where('doctor_profile_id', $doctorProfileId)
                            ->update([
                                'doctor_profile_id' => null,
                                'doctor_request_status' => Schema::hasColumn('patient_profiles', 'doctor_request_status') ? 'pending' : null
                            ]);
                    }
                    if ($user->doctorProfile) { $user->doctorProfile()->delete(); }
                }

                if ($patientProfileId && $user->patientProfile) { $user->patientProfile()->delete(); }
                $user->delete();
            });

            $this->logActivity('حذف حساب', null, "تم حذف حساب المستخدم {$userName} (#{$id})");

            return response()->json(['success' => true, 'message' => 'تم حذف المستخدم بنجاح.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'تعذر حذف المستخدم: ' . $e->getMessage()], 500);
        }
    }

    /**
     * تعليق أو إعادة تفعيل حساب المستخدم (بديل أهدأ من الحذف النهائي)
     */
    public function toggleStatus($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'المستخدم غير موجود.'], 404);
        }

        if (auth()->id() === $user->id) {
            return response()->json(['success' => false, 'message' => 'لا يمكنك تعليق حسابك الحالي.'], 403);
        }

        $newStatus = ($user->status ?? 'active') === 'active' ? 'suspended' : 'active';
        $user->status = $newStatus;
        $user->save();

        $this->logActivity(
            $newStatus === 'suspended' ? 'تعليق حساب' : 'إعادة تفعيل حساب',
            $user,
            ($newStatus === 'suspended' ? 'تم تعليق حساب ' : 'تم إعادة تفعيل حساب ') . $user->name
        );

        return response()->json([
            'success' => true,
            'status' => $newStatus,
            'message' => $newStatus === 'suspended' ? 'تم تعليق الحساب.' : 'تم إعادة تفعيل الحساب.',
        ]);
    }

    /**
     * حذف عدة مستخدمين دفعة وحدة (Bulk Actions)
     */
    public function bulkDestroy(Request $request)
    {
        $ids = array_filter((array) $request->input('ids', []));

        if (empty($ids)) {
            return response()->json(['success' => false, 'message' => 'ما في مستخدمين محددين.'], 422);
        }

        $ids = array_values(array_diff($ids, [(string) auth()->id(), auth()->id()]));

        $deleted = 0;
        foreach ($ids as $id) {
            $response = $this->destroy($id);
            $data = json_decode($response->getContent(), true);
            if (!empty($data['success'])) {
                $deleted++;
            }
        }

        $this->logActivity('حذف جماعي', null, "تم حذف {$deleted} حساب دفعة وحدة");

        return response()->json([
            'success' => true,
            'deleted' => $deleted,
            'message' => "تم حذف {$deleted} مستخدم بنجاح.",
        ]);
    }

    /**
     * تسجيل الإجراء بسجل التدقيق (Audit Log)
     */
    private function logActivity(string $action, ?User $target, ?string $description = null): void
    {
        if (!Schema::hasTable('admin_activity_logs')) {
            return;
        }

        DB::table('admin_activity_logs')->insert([
            'admin_id' => auth()->id(),
            'action' => $action,
            'target_type' => $target ? 'user' : null,
            'target_id' => $target?->id,
            'target_label' => $target?->name,
            'description' => $description,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }






    /**
     * حساب عدد ملفات المرضى المكتملة
     */
    private function countCompletedPatientProfiles(): int
    {
        if (! Schema::hasTable('patient_profiles')) {
            return 0;
        }

        if (Schema::hasColumn('patient_profiles', 'profile_completed')) {
            return (int) DB::table('patient_profiles')
                ->where('profile_completed', true)
                ->count();
        }

        return 0;
    }

    /**
     * إرفاق رؤى إضافية للمستخدمين (للوحة الإدارة)
     */
    private function attachAdminUserInsights($users): void
    {
        foreach ($users as $user) {
            $doctorProfileId = $user->doctorProfile?->id;
            $patientProfileId = $user->patientProfile?->id;

            $doctorPatients = 0;
            $doctorAppointments = 0;
            $doctorArticles = 0;
            $doctorReviews = 0;
            $doctorRating = 0;

            if ($doctorProfileId) {
                if (Schema::hasTable('patient_profiles') && Schema::hasColumn('patient_profiles', 'doctor_profile_id')) {
                    $doctorPatientsQuery = DB::table('patient_profiles')
                        ->where('doctor_profile_id', $doctorProfileId);

                    if (Schema::hasColumn('patient_profiles', 'doctor_request_status')) {
                        $doctorPatientsQuery->where('doctor_request_status', 'approved');
                    }

                    $doctorPatients = (int) $doctorPatientsQuery->count();
                }

                if (Schema::hasTable('patient_appointments') && Schema::hasColumn('patient_appointments', 'doctor_profile_id')) {
                    $doctorAppointments = (int) DB::table('patient_appointments')
                        ->where('doctor_profile_id', $doctorProfileId)
                        ->count();
                }

                if (Schema::hasTable('articles') && Schema::hasColumn('articles', 'doctor_profile_id')) {
                    $doctorArticles = (int) DB::table('articles')
                        ->where('doctor_profile_id', $doctorProfileId)
                        ->count();
                }

                if (Schema::hasTable('doctor_reviews') && Schema::hasColumn('doctor_reviews', 'doctor_profile_id')) {
                    $doctorReviews = (int) DB::table('doctor_reviews')
                        ->where('doctor_profile_id', $doctorProfileId)
                        ->count();

                    if (Schema::hasColumn('doctor_reviews', 'rating')) {
                        $doctorRating = round((float) DB::table('doctor_reviews')
                            ->where('doctor_profile_id', $doctorProfileId)
                            ->avg('rating'), 1);
                    }
                }
            }

            $patientAppointments = 0;
            $patientMeals = 0;
            $patientTasks = 0;
            $patientDoctorStatus = $user->patientProfile?->doctor_request_status ?? 'غير محدد';

            if (($user->role ?? null) === 'patient') {
                if (Schema::hasTable('patient_appointments') && Schema::hasColumn('patient_appointments', 'user_id')) {
                    $patientAppointments = (int) DB::table('patient_appointments')
                        ->where('user_id', $user->id)
                        ->count();
                }

                if (Schema::hasTable('patient_meals') && Schema::hasColumn('patient_meals', 'user_id')) {
                    $patientMeals = (int) DB::table('patient_meals')
                        ->where('user_id', $user->id)
                        ->count();
                }

                if (Schema::hasTable('patient_tasks') && Schema::hasColumn('patient_tasks', 'user_id')) {
                    $patientTasks = (int) DB::table('patient_tasks')
                        ->where('user_id', $user->id)
                        ->count();
                }
            }

            $user->setAttribute('admin_insights', [
                'doctor_patients' => $doctorPatients,
                'doctor_appointments' => $doctorAppointments,
                'doctor_articles' => $doctorArticles,
                'doctor_reviews' => $doctorReviews,
                'doctor_rating' => $doctorRating,

                'patient_appointments' => $patientAppointments,
                'patient_meals' => $patientMeals,
                'patient_tasks' => $patientTasks,
                'patient_doctor_status' => $patientDoctorStatus,
                'patient_profile_completed' => (bool) ($user->patientProfile?->profile_completed ?? false),

                'has_doctor_profile' => (bool) $doctorProfileId,
                'has_patient_profile' => (bool) $patientProfileId,
            ]);
        }
    }

    /**
     * حذف سجلات من جدول إذا كان العمود موجوداً
     */
    private function deleteByColumnIfExists(string $table, string $column, mixed $value): void
    {
        if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
            DB::table($table)->where($column, $value)->delete();
        }
    }
}
