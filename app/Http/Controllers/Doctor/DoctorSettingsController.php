<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\DoctorProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * صفحة "الإعدادات" الحقيقية لجهة الدكتور — قبل هيك كانت view فاضي 10 أسطر.
 * فيها 3 أقسام حقيقية: تغيير كلمة المرور، التوفر للاستشارات (نفس الحقل
 * الموجود أصلاً is_available)، وجدول ساعات العمل الأسبوعي (جدول جديد).
 */
class DoctorSettingsController extends Controller
{
    private const DAYS = [0, 1, 2, 3, 4, 5, 6];

    public function show(): View
    {
        $doctorProfile = DoctorProfile::with('schedules')->where('user_id', auth()->id())->firstOrFail();

        $schedulesByDay = $doctorProfile->schedules->keyBy('day_of_week');

        return view('doctor.settings', [
            'pageTitle' => 'الإعدادات',
            'activePage' => 'settings',
            'doctorProfile' => $doctorProfile,
            'schedulesByDay' => $schedulesByDay,
            'days' => self::DAYS,
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed'],
        ], [
            'current_password.required' => 'كلمة المرور الحالية مطلوبة.',
            'password.required' => 'كلمة المرور الجديدة مطلوبة.',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل.',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
        ]);

        $user = Auth::user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'كلمة المرور الحالية غير صحيحة.']);
        }

        $user->update(['password' => $validated['password']]);

        return redirect()->route('doctor.settings')->with('success', 'تم تغيير كلمة المرور بنجاح.');
    }

    public function updateSchedule(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'days' => ['nullable', 'array'],
            'days.*.active' => ['nullable', 'boolean'],
            'days.*.start_time' => ['nullable', 'date_format:H:i'],
            'days.*.end_time' => ['nullable', 'date_format:H:i'],
        ]);

        $doctorProfile = DoctorProfile::where('user_id', auth()->id())->firstOrFail();

        foreach (self::DAYS as $day) {
            $dayInput = $validated['days'][$day] ?? null;

            if (!$dayInput || empty($dayInput['active'])) {
                $doctorProfile->schedules()->where('day_of_week', $day)->delete();
                continue;
            }

            $start = $dayInput['start_time'] ?? '09:00';
            $end = $dayInput['end_time'] ?? '17:00';

            if ($end <= $start) {
                return back()->withErrors(['days' => 'وقت النهاية لازم يكون بعد وقت البداية (يوم ' . (new \App\Models\DoctorSchedule(['day_of_week' => $day]))->day_label . ').']);
            }

            $doctorProfile->schedules()->updateOrCreate(
                ['day_of_week' => $day],
                [
                    'start_time' => $start,
                    'end_time' => $end,
                    'is_active' => true,
                ]
            );
        }

        return redirect()->route('doctor.settings')->with('success', 'تم تحديث جدول ساعات عملك.');
    }
}
