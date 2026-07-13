<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Services\AppNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * ملاحظة مهمة (تم اكتشافها بعد تحقيق كامل):
 * طلبات متابعة المريض للطبيب لا تُخزَّن في جدول patient_doctor_requests،
 * وإنما مباشرة داخل جدول patient_profiles عبر:
 *   - doctor_profile_id  (رقم الطبيب المختار)
 *   - has_selected_doctor (true عند الاختيار)
 *   - doctor_request_status ('pending' | 'approved' | 'rejected')
 * (راجعي PatientHomeController::selectDoctor()).
 *
 * لذلك أعيد بناء هذا الكونترولر ليقرأ من نفس المصدر الحقيقي بدل الجدول
 * المنفصل الذي لم يعد أي مسار حقيقي يكتب إليه.
 */
class DoctorPatientRequestController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $doctorProfile = DB::table('doctor_profiles')
            ->where('user_id', $user->id)
            ->first();

        if (!$doctorProfile) {
            return back()->with('error', 'لا يوجد ملف طبيب مرتبط بهذا الحساب.');
        }

        $requests = collect();

        if (Schema::hasTable('patient_profiles')) {
            $select = [
                'patient_profiles.id as id',
                'patient_profiles.user_id as patient_id',
                'patient_profiles.doctor_request_status as status',
                'patient_profiles.health_goal',
                'patient_profiles.medical_conditions',
                'patient_profiles.updated_at as created_at',
                'users.name as patient_name',
                'users.email as patient_email',
            ];

            foreach (['avatar', 'profile_photo', 'photo', 'image', 'profile_photo_path'] as $avatarColumn) {
                if (Schema::hasColumn('patient_profiles', $avatarColumn)) {
                    $select[] = 'patient_profiles.' . $avatarColumn;
                }
            }

            $requests = DB::table('patient_profiles')
                ->join('users', 'users.id', '=', 'patient_profiles.user_id')
                ->where('patient_profiles.doctor_profile_id', $doctorProfile->id)
                ->select($select)
                ->orderByDesc('patient_profiles.updated_at')
                ->get()
                ->map(function ($row) {
                    // توحيد اسم الحالة حتى تطابق ما تتوقعه الواجهة (pending/approved/rejected)
                    $row->status = $row->status ?: 'pending';
                    $row->avatarUrl = $this->resolveAvatarUrl($row);
                    return $row;
                });
        }

        return view('doctor.requests', [
            'pageTitle' => 'طلبات المتابعة',
            'activePage' => 'patient_requests',
            'requests' => $requests,
        ]);
    }

    public function approve(Request $request, int $doctorRequest): RedirectResponse
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $doctorProfile = DB::table('doctor_profiles')->where('user_id', $user->id)->first();

        // $doctorRequest هنا هو id سجل patient_profiles نفسه
        $profileRow = DB::table('patient_profiles')
            ->where('id', $doctorRequest)
            ->where('doctor_profile_id', $doctorProfile?->id)
            ->first();

        if (!$profileRow) {
            return back()->with('error', 'الطلب غير موجود.');
        }

        if (($profileRow->doctor_request_status ?? 'pending') !== 'pending') {
            return back()->with('error', 'لا يمكن تعديل هذا الطلب الآن.');
        }

        DB::table('patient_profiles')
            ->where('id', $profileRow->id)
            ->update([
                'doctor_request_status' => 'approved',
                'updated_at' => now(),
            ]);

        $this->notifyPatient(
            (int) $profileRow->user_id,
            (int) $user->id,
            'doctor_request_approved',
            'تمت الموافقة على طلب المتابعة',
            'وافق الطبيب على طلبك، ويمكنك الآن بدء المتابعة وحجز موعد.',
            route('patient.profile')
        );

        return back()->with('success', 'تمت الموافقة على طلب المريض.');
    }

    public function reject(Request $request, int $doctorRequest): RedirectResponse
    {
        $validated = $request->validate([
            'doctor_response' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $doctorProfile = DB::table('doctor_profiles')->where('user_id', $user->id)->first();

        $profileRow = DB::table('patient_profiles')
            ->where('id', $doctorRequest)
            ->where('doctor_profile_id', $doctorProfile?->id)
            ->first();

        if (!$profileRow) {
            return back()->with('error', 'الطلب غير موجود.');
        }

        if (($profileRow->doctor_request_status ?? 'pending') !== 'pending') {
            return back()->with('error', 'لا يمكن تعديل هذا الطلب الآن.');
        }

        DB::table('patient_profiles')
            ->where('id', $profileRow->id)
            ->update([
                'doctor_request_status' => 'rejected',
                'updated_at' => now(),
            ]);

        $this->notifyPatient(
            (int) $profileRow->user_id,
            (int) $user->id,
            'doctor_request_rejected',
            'اعتذر الطبيب عن قبول طلب المتابعة',
            $validated['doctor_response'] ?? 'يمكنك اختيار طبيب آخر مناسب لحالتك.',
            route('patient.doctors.recommended')
        );

        return back()->with('success', 'تم الاعتذار عن الطلب وإشعار المريض.');
    }

    public function complete(int $doctorRequest): RedirectResponse
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $doctorProfile = DB::table('doctor_profiles')->where('user_id', $user->id)->first();

        $profileRow = DB::table('patient_profiles')
            ->where('id', $doctorRequest)
            ->where('doctor_profile_id', $doctorProfile?->id)
            ->where('doctor_request_status', 'approved')
            ->first();

        if (!$profileRow) {
            return back()->with('error', 'لا يوجد طلب معتمد يمكن إنهاؤه.');
        }

        // لا نغيّر doctor_request_status هون عمداً، حتى لا نكسر منطق selectedDoctor()
        // بجهة المريض (اللي بيتعامل بس مع approved/rejected/pending).
        // إنهاء المتابعة هلق هو إشعار فقط، بدون تغيير حالة الاعتماد.
        $this->notifyPatient(
            (int) $profileRow->user_id,
            (int) $user->id,
            'doctor_followup_completed',
            'يمكنك الآن تقييم الطبيب',
            'بعد انتهاء المتابعة، يمكنك مشاركة تقييمك للطبيب من صفحة الأطباء.',
            route('patient.doctors.recommended')
        );

        return back()->with('success', 'تم إشعار المريض بإمكانية تقييم الطبيب.');
    }

    private function resolveAvatarUrl(object $row): ?string
    {
        $path = null;

        foreach (['avatar', 'profile_photo', 'photo', 'image', 'profile_photo_path'] as $column) {
            if (!empty($row->{$column} ?? null)) {
                $path = $row->{$column};
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

    /**
     * إرسال إشعار حقيقي عبر نفس خدمة الإشعارات المستخدمة بباقي المشروع
     * (AppNotificationService) بدل الإدخال اليدوي المباشر بالجدول،
     * حتى نتجنب مشاكل الأعمدة الإلزامية (زي actor_user_id) ونحافظ على
     * نفس السلوك المستخدم في PatientHomeController.
     */
    private function notifyPatient(
        int $recipientUserId,
        ?int $actorUserId,
        string $type,
        string $title,
        ?string $body,
        ?string $url
    ): void {
        if (!$recipientUserId) {
            return;
        }

        app(AppNotificationService::class)->send(
            recipientUserId: $recipientUserId,
            recipientRole: 'patient',
            type: $type,
            title: $title,
            body: $body,
            url: $url,
            actorUserId: $actorUserId
        );
    }
}
