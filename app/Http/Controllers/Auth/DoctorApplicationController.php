<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\DoctorApplication;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DoctorApplicationController extends Controller
{
    public function create()
    {
        $specialties = Specialty::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('auth.join-doctor', compact('specialties'));
    }

    public function checkEmail(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = trim(strtolower($request->email));

        $existsInUsers = User::whereRaw('LOWER(email) = ?', [$email])->exists();
        $existsInApplications = DoctorApplication::whereRaw('LOWER(email) = ?', [$email])->exists();

        if ($existsInUsers) {
            return response()->json([
                'valid' => false,
                'message' => 'هذا البريد الإلكتروني مستخدم بالفعل في النظام.',
            ]);
        }

        if ($existsInApplications) {
            return response()->json([
                'valid' => false,
                'message' => 'يوجد طلب انضمام سابق بهذا البريد الإلكتروني.',
            ]);
        }

        return response()->json([
            'valid' => true,
            'message' => '',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
                Rule::unique('doctor_applications', 'email'),
            ],
            'phone' => ['required', 'string', 'max:30'],
            'workplace' => ['required', 'string', 'max:255'],
            'specialty' => [
                'required',
                'string',
                'max:255',
                Rule::exists('specialties', 'name')->where(function ($query) {
                    return $query->where('is_active', true);
                }),
            ],
            'experience_years' => ['required', 'integer', 'min:0', 'max:60'],
            'license_number' => ['required', 'string', 'max:255'],
            'bio' => ['required', 'string', 'max:3000'],
            'profile_photo' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'license_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:8192'],
            'cv_file' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:8192'],
            'agreement' => ['accepted'],
        ], [
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.email' => 'صيغة البريد الإلكتروني غير صحيحة.',
            'email.unique' => 'هذا البريد مستخدم مسبقًا.',
            'full_name.required' => 'الاسم الكامل مطلوب.',
            'phone.required' => 'رقم الهاتف مطلوب.',
            'workplace.required' => 'مكان العمل الحالي مطلوب.',
            'specialty.required' => 'التخصص مطلوب.',
            'specialty.exists' => 'التخصص المختار غير متاح حاليًا.',
            'experience_years.required' => 'سنوات الخبرة مطلوبة.',
            'license_number.required' => 'رقم الترخيص مطلوب.',
            'bio.required' => 'النبذة المهنية مطلوبة.',
            'profile_photo.required' => 'الصورة الشخصية مطلوبة.',
            'license_file.required' => 'إثبات مزاولة المهنة مطلوب.',
            'agreement.accepted' => 'يجب تأكيد صحة البيانات قبل الإرسال.',
        ]);

        $profilePhotoPath = $request->file('profile_photo')
            ->store('doctor-applications/profile-photos', 'public');

        $licenseFilePath = $request->file('license_file')
            ->store('doctor-applications/license-files', 'public');

        $cvFilePath = $request->hasFile('cv_file')
            ? $request->file('cv_file')->store('doctor-applications/cv-files', 'public')
            : null;

        $doctorApplication = DoctorApplication::create([
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'workplace' => $validated['workplace'],
            'specialty' => $validated['specialty'],
            'experience_years' => $validated['experience_years'],
            'license_number' => $validated['license_number'],
            'bio' => $validated['bio'],
            'profile_photo_path' => $profilePhotoPath,
            'license_file_path' => $licenseFilePath,
            'cv_file_path' => $cvFilePath,
            'status' => 'pending',
        ]);

        AdminNotification::create([
            'type' => 'doctor_application',
            'title' => 'طلب طبيب جديد',
            'body' => 'تم إرسال طلب انضمام جديد من ' . $doctorApplication->full_name,
            'url' => route('admin.doctor-applications-show', $doctorApplication->id),
            'read_at' => null,
        ]);

        return redirect()->route('join-doctor')->with([
            'doctor_apply_success' => 'تم إرسال طلبك بنجاح',
            'doctor_apply_status' => 'قيد المراجعة',
            'doctor_apply_note' => 'تم استلام طلبك بنجاح، يرجى مراجعة بريدك الإلكتروني لمتابعة حالة الطلب أو أي تحديث من إدارة منصة اتزان.',
        ]);
    }
}
