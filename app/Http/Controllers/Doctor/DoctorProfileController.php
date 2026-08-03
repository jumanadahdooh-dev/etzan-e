<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\DoctorProfile;
use App\Models\Specialty;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * صفحة "ملفي الشخصي" الحقيقية لجهة الدكتور — قبل هيك كانت فورم بقيم
 * وهمية مكتوبة مباشرة بالـ view ("د. أحمد منصور"، "8 سنوات خبرة"...).
 * البيانات المعروضة بالمعاينة هون هي بالضبط يلي بيشوفها المريض بصفحة
 * تفاصيل الطبيب (PatientDoctorController::doctorDetails).
 */
class DoctorProfileController extends Controller
{
    public function show(): View
    {
        $doctorProfile = DoctorProfile::with('specialties', 'reviews')
            ->where('user_id', auth()->id())
            ->firstOrFail();

        return view('doctor.profile', [
            'pageTitle' => 'ملفي الشخصي',
            'activePage' => 'profile',
            'doctorProfile' => $doctorProfile,
            'allSpecialties' => Specialty::where('is_active', true)->orderBy('sort_order')->get(),
            'avgRating' => round((float) $doctorProfile->reviews()->avg('rating'), 1),
            'reviewsCount' => $doctorProfile->reviews()->count(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $doctorProfile = DoctorProfile::where('user_id', auth()->id())->firstOrFail();

        $validated = $request->validate([
            'workplace' => ['nullable', 'string', 'max:255'],
            'years_experience' => ['nullable', 'integer', 'min:0', 'max:70'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'specialties' => ['nullable', 'array'],
            'specialties.*' => ['exists:specialties,id'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            'photo.image' => 'صورة الملف الشخصي يجب أن تكون صورة.',
            'photo.mimes' => 'صيغة الصورة يجب أن تكون jpg أو png أو webp.',
            'photo.max' => 'حجم الصورة يجب ألا يتجاوز 4MB.',
        ]);

        $photoPath = $doctorProfile->photo_path;
        if ($request->hasFile('photo')) {
            if ($photoPath && Storage::disk('public')->exists($photoPath)) {
                Storage::disk('public')->delete($photoPath);
            }

            $photoPath = $request->file('photo')->store('doctors/photos', 'public');
        }

        $doctorProfile->update([
            'workplace' => $validated['workplace'] ?? null,
            'years_experience' => $validated['years_experience'] ?? null,
            'bio' => $validated['bio'] ?? null,
            'photo_path' => $photoPath,
        ]);

        $doctorProfile->specialties()->sync($validated['specialties'] ?? []);

        return redirect()->route('doctor.profile')->with('success', 'تم تحديث ملفك الشخصي.');
    }
}
