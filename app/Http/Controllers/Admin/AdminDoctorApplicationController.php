<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\DoctorApplicationApprovedMail;
use App\Mail\DoctorApplicationRejectedMail;
use App\Models\DoctorApplication;
use App\Models\DoctorProfile;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class AdminDoctorApplicationController extends Controller
{
    public function index(Request $request)
    {
        $query = DoctorApplication::query()->latest();

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('license_number', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('specialty')) {
            $query->where('specialty', $request->specialty);
        }

        $applications = $query->paginate(8)->withQueryString();
        $this->attachApplicationInsights($applications->getCollection());

        $stats = [
            'total'    => DoctorApplication::count(),
            'pending'  => DoctorApplication::where('status', 'pending')->count(),
            'approved' => DoctorApplication::where('status', 'approved')->count(),
            'rejected' => DoctorApplication::where('status', 'rejected')->count(),
        ];

        $specialties = DoctorApplication::query()
            ->select('specialty')
            ->distinct()
            ->orderBy('specialty')
            ->pluck('specialty');

        return view('admin.doctor-applications', compact('applications', 'stats', 'specialties'));
    }

    private function attachApplicationInsights($applications): void
    {
        if (!Schema::hasTable('doctor_profiles') || !Schema::hasTable('users')) {
            return;
        }

        foreach ($applications as $application) {
            $doctorUser = DB::table('users')
                ->where('email', $application->email)
                ->where('role', 'doctor')
                ->first();

            $doctorProfile = $doctorUser
                ? DB::table('doctor_profiles')->where('user_id', $doctorUser->id)->first()
                : null;

            $patientsCount = 0;
            $appointmentsCount = 0;

            if ($doctorProfile) {
                if (Schema::hasTable('patient_profiles') && Schema::hasColumn('patient_profiles', 'doctor_profile_id')) {
                    $patientsQuery = DB::table('patient_profiles')->where('doctor_profile_id', $doctorProfile->id);

                    if (Schema::hasColumn('patient_profiles', 'doctor_request_status')) {
                        $patientsQuery->where('doctor_request_status', 'approved');
                    }

                    $patientsCount = (int) $patientsQuery->count();
                }

                if (Schema::hasTable('patient_appointments') && Schema::hasColumn('patient_appointments', 'doctor_profile_id')) {
                    $appointmentsCount = (int) DB::table('patient_appointments')
                        ->where('doctor_profile_id', $doctorProfile->id)
                        ->count();
                }
            }

            $application->setAttribute('admin_patients_count', $patientsCount);
            $application->setAttribute('admin_appointments_count', $appointmentsCount);
        }
    }

    public function show(DoctorApplication $doctorApplication)
    {
        $doctorApplication->load('reviewer');
        return view('admin.doctor-application-show', compact('doctorApplication'));
    }

    public function updateStatus(Request $request, DoctorApplication $doctorApplication)
    {
        $validated = $request->validate([
            'status'           => ['required', 'in:pending,approved,rejected'],
            'admin_note'       => ['nullable', 'string', 'max:3000'],
            'rejection_reason' => ['nullable', 'string', 'max:3000'],
        ], [
            'status.required' => 'الحالة مطلوبة.',
            'status.in'       => 'الحالة غير صحيحة.',
        ]);

        if ($validated['status'] === 'rejected' && empty($validated['rejection_reason'])) {
            return back()->withErrors([
                'rejection_reason' => 'سبب الرفض مطلوب عند رفض الطلب.',
            ])->withInput();
        }

        $oldStatus = $doctorApplication->status;

        $doctorApplication->update([
            'status'           => $validated['status'],
            'admin_note'       => $validated['admin_note'] ?? null,
            'rejection_reason' => $validated['status'] === 'rejected'
                ? ($validated['rejection_reason'] ?? null)
                : null,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        if ($validated['status'] === 'approved') {
            $user = $this->createOrUpdateDoctorUser($doctorApplication);
            $this->createOrUpdateDoctorProfile($user, $doctorApplication);
            $this->attachDoctorSpecialty($user, $doctorApplication);

            if ($oldStatus !== 'approved') {
                $setupUrl = $this->generateDoctorSetupUrl($user->email);
                Mail::to($doctorApplication->email)->send(
                    new DoctorApplicationApprovedMail($doctorApplication, $user, $setupUrl)
                );
            }
        }

        if ($validated['status'] === 'rejected' && $oldStatus !== 'rejected') {
            Mail::to($doctorApplication->email)->send(
                new DoctorApplicationRejectedMail($doctorApplication)
            );
        }

        return back()->with('success', 'تم تحديث حالة الطلب بنجاح.');
    }

    /**
     * عرض الملف مباشرة في المتصفح (صور + PDF) أو تحميله (باقي الأنواع)
     * الإصلاح: حذف الكود الميت بعد return وتوحيد المنطق
     */
    public function previewFile(DoctorApplication $doctorApplication, string $type)
 {
    $path = $this->resolveApplicationFile($doctorApplication, $type);

    if (!$path) {
        abort(404, 'لم يتم رفع هذا الملف.');
    }

    $disk = $this->applicationFileDisk($type, $path);

    if (!$disk) {
        abort(404, 'الملف غير موجود على الخادم.');
    }

    $fullPath = Storage::disk($disk)->path($path);
    $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

    $mimeMap = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp',
        'gif'  => 'image/gif',
        'pdf'  => 'application/pdf',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    $mime = $mimeMap[$extension] ?? 'application/octet-stream';

    if (str_starts_with($mime, 'image/') || $mime === 'application/pdf') {
        return response()->file($fullPath, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . basename($fullPath) . '"',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    return response()->download($fullPath, basename($fullPath));
}

    public function downloadFile(DoctorApplication $doctorApplication, string $type)
    {
        $path = $this->resolveApplicationFile($doctorApplication, $type);
        $disk = $path ? $this->applicationFileDisk($type, $path) : null;

        if (!$disk) {
            abort(404, 'الملف غير موجود.');
        }

        $fullPath = Storage::disk($disk)->path($path);
        return response()->download($fullPath, basename($fullPath));
    }

    // =====================================================
    //  PRIVATE HELPERS
    // =====================================================

    private function resolveApplicationFile(DoctorApplication $doctorApplication, string $type): ?string
    {
        return match ($type) {
            'photo'   => $doctorApplication->profile_photo_path,
            'license' => $doctorApplication->license_file_path,
            'cv'      => $doctorApplication->cv_file_path,
            default   => null,
        };
    }

    /**
     * القرص اللي فيه الملف فعليًا. الترخيص والسيرة على القرص الخاص، بس الطلبات
     * القديمة (قبل النقل) ممكن تكون لسا على public فبنرجعلها كاحتياط.
     */
    private function applicationFileDisk(string $type, string $path): ?string
    {
        $disks = $type === 'photo' ? ['public'] : [DoctorApplication::PRIVATE_DISK, 'public'];

        foreach ($disks as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return $disk;
            }
        }

        return null;
    }

    private function createOrUpdateDoctorUser(DoctorApplication $doctorApplication): User
    {
        $user = User::where('email', $doctorApplication->email)->first();

        if (!$user) {
            $user = User::create([
                'name'     => $doctorApplication->full_name,
                'email'    => $doctorApplication->email,
                'password' => Hash::make(Str::random(24)),
                'role'     => 'doctor',
            ]);
        } else {
            $user->update([
                'name' => $doctorApplication->full_name,
                'role' => 'doctor',
            ]);
        }

        return $user;
    }

    private function createOrUpdateDoctorProfile(User $user, DoctorApplication $doctorApplication): void
    {
        DoctorProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'workplace'        => $doctorApplication->workplace,
                'license_number'   => $doctorApplication->license_number,
                'years_experience' => $doctorApplication->experience_years,
                'bio'              => $doctorApplication->bio,
                'photo_path'       => $doctorApplication->profile_photo_path,
                'is_available'     => true,
            ]
        );
    }

    private function attachDoctorSpecialty(User $user, DoctorApplication $doctorApplication): void
    {
        $doctorProfile = $user->doctorProfile;
        if (!$doctorProfile) return;

        $slugBase = Str::slug($doctorApplication->specialty);
        if (blank($slugBase)) $slugBase = 'specialty';

        $specialty = Specialty::firstOrCreate(
            ['name' => $doctorApplication->specialty],
            [
                'slug'       => $slugBase . '-' . Str::lower(Str::random(5)),
                'is_active'  => true,
                'sort_order' => 0,
            ]
        );

        $doctorProfile->specialties()->syncWithoutDetaching([$specialty->id]);
    }

    private function generateDoctorSetupUrl(string $email): string
    {
        return URL::temporarySignedRoute(
            'forgot-password.direct-setup',
            now()->addHours(24),
            ['email' => $email]
        );
    }

    public function viewFile(DoctorApplication $doctorApplication, string $type)
{
    $path = $this->resolveApplicationFile($doctorApplication, $type);
    $disk = $path ? $this->applicationFileDisk($type, $path) : null;

    if (!$disk) {
        abort(404, 'الملف غير موجود.');
    }

    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

    $mimeMap = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp',
        'gif'  => 'image/gif',
        'pdf'  => 'application/pdf',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    $mime = $mimeMap[$extension] ?? 'application/octet-stream';

    $title = match ($type) {
        'photo' => 'الصورة الشخصية',
        'license' => 'إثبات مزاولة المهنة',
        'cv' => 'السيرة الذاتية',
        default => 'عرض الملف',
    };

    $fileContent = Storage::disk($disk)->get($path);
    $base64 = base64_encode($fileContent);
    $dataUri = "data:{$mime};base64,{$base64}";

    return view('admin.file-viewer', [
        'doctorApplication' => $doctorApplication,
        'type' => $type,
        'title' => $title,
        'extension' => $extension,
        'mime' => $mime,
        'dataUri' => $dataUri,
        'fileName' => basename($path),
    ]);
}
}
