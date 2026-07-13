<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PatientWeightController extends Controller
{
    /**
     * تسجيل وزن جديد من جهة المريض نفسه.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'weight_kg' => ['required', 'numeric', 'min:25', 'max:350'],
            'logged_date' => ['nullable', 'date', 'before_or_equal:today'],
        ]);

        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!Schema::hasTable('patient_weight_logs')) {
            return back()->with('error', 'جدول سجل الوزن غير موجود بعد.');
        }

        $patientProfile = Schema::hasTable('patient_profiles')
            ? DB::table('patient_profiles')->where('user_id', $user->id)->first()
            : null;

        DB::table('patient_weight_logs')->insert([
            'user_id' => $user->id,
            'patient_profile_id' => $patientProfile->id ?? null,
            'doctor_profile_id' => $patientProfile->doctor_profile_id ?? null,
            'weight_kg' => $validated['weight_kg'],
            'logged_date' => $validated['logged_date'] ?? now()->toDateString(),
            'source' => 'patient',
            'note' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->syncProfileWeight($patientProfile, $validated['weight_kg']);

        return back()->with('success', 'تم تسجيل وزنك بنجاح.');
    }

    /**
     * تعديل قياس وزن سابق (بس لصاحبه).
     */
    public function update(Request $request, int $log): RedirectResponse
    {
        $validated = $request->validate([
            'weight_kg' => ['required', 'numeric', 'min:25', 'max:350'],
            'logged_date' => ['nullable', 'date', 'before_or_equal:today'],
        ]);

        $user = auth()->user();
        $row = DB::table('patient_weight_logs')->where('id', $log)->where('user_id', $user->id)->first();

        if (!$row) {
            return back()->with('error', 'القياس غير موجود.');
        }

        DB::table('patient_weight_logs')->where('id', $log)->update([
            'weight_kg' => $validated['weight_kg'],
            'logged_date' => $validated['logged_date'] ?? $row->logged_date,
            'updated_at' => now(),
        ]);

        // لو كان هاد آخر قياس زمنياً، حدّثي الملف الصحي كمان
        $latest = DB::table('patient_weight_logs')->where('user_id', $user->id)->orderByDesc('logged_date')->first();
        if ($latest && (int) $latest->id === $log) {
            $patientProfile = DB::table('patient_profiles')->where('user_id', $user->id)->first();
            $this->syncProfileWeight($patientProfile, $validated['weight_kg']);
        }

        return back()->with('success', 'تم تعديل القياس بنجاح.');
    }

    /**
     * حذف قياس وزن (بس لصاحبه).
     */
    public function destroy(int $log): RedirectResponse
    {
        $user = auth()->user();
        $row = DB::table('patient_weight_logs')->where('id', $log)->where('user_id', $user->id)->first();

        if (!$row) {
            return back()->with('error', 'القياس غير موجود.');
        }

        DB::table('patient_weight_logs')->where('id', $log)->delete();

        // بعد الحذف، خلي الملف الصحي يعكس آخر قياس متبقي (لو في)
        $latest = DB::table('patient_weight_logs')->where('user_id', $user->id)->orderByDesc('logged_date')->first();
        if ($latest) {
            $patientProfile = DB::table('patient_profiles')->where('user_id', $user->id)->first();
            $this->syncProfileWeight($patientProfile, $latest->weight_kg);
        }

        return back()->with('success', 'تم حذف القياس.');
    }

    /**
     * تحديد/تحديث هدف الوزن المستهدف.
     */
    public function setGoal(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'target_weight_kg' => ['required', 'numeric', 'min:25', 'max:350'],
        ]);

        $user = auth()->user();

        if (!Schema::hasColumn('patient_profiles', 'target_weight_kg')) {
            return back()->with('error', 'عمود هدف الوزن غير موجود بعد — لازم تشغّلي هجرة قاعدة البيانات أول.');
        }

        DB::table('patient_profiles')
            ->where('user_id', $user->id)
            ->update(['target_weight_kg' => $validated['target_weight_kg'], 'updated_at' => now()]);

        return back()->with('success', 'تم تحديد هدف الوزن بنجاح.');
    }

    private function syncProfileWeight(?object $patientProfile, $weightKg): void
    {
        if (!$patientProfile) {
            return;
        }

        $weightColumn = Schema::hasColumn('patient_profiles', 'weight')
            ? 'weight'
            : (Schema::hasColumn('patient_profiles', 'weight_kg') ? 'weight_kg' : (Schema::hasColumn('patient_profiles', 'current_weight') ? 'current_weight' : null));

        if ($weightColumn) {
            DB::table('patient_profiles')
                ->where('id', $patientProfile->id)
                ->update([$weightColumn => $weightKg, 'updated_at' => now()]);
        }
    }
}
