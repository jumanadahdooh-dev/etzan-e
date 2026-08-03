<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\DoctorProfile;
use App\Models\PatientMeal;
use App\Services\AppNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * صفحة "مراجعة الوجبات AI" الحقيقية — قبل هيك كانت view فاضي 10 أسطر.
 * تعرض وجبات مرضى الدكتور المعتمدين (نفس بيانات patient_meals الحقيقية
 * يلي المريض بيسجلها بتحليل AI)، وتتيح للدكتور يضيف ملاحظة فعلية.
 */
class DoctorMealReviewController extends Controller
{
    public function index(Request $request): View
    {
        $doctorProfile = DoctorProfile::where('user_id', auth()->id())->first();

        if (!$doctorProfile) {
            return view('doctor.meal-reviews', [
                'pageTitle' => 'مراجعة الوجبات AI',
                'activePage' => 'meal-reviews',
                'meals' => collect(),
                'patients' => collect(),
                'selectedPatientId' => null,
                'onlyUnreviewed' => false,
            ]);
        }

        $patients = $doctorProfile->patientProfiles()
            ->where('doctor_request_status', 'approved')
            ->with('user:id,name')
            ->get();

        $patientUserIds = $patients->pluck('user_id');

        $selectedPatientId = $request->integer('patient') ?: null;
        $onlyUnreviewed = $request->boolean('unreviewed');

        $meals = collect();

        if ($patientUserIds->isNotEmpty()) {
            $query = PatientMeal::whereIn('user_id', $patientUserIds)
                ->where('status', 'confirmed')
                ->with('user:id,name')
                ->orderByDesc('meal_date')
                ->orderByDesc('created_at');

            if ($selectedPatientId && $patientUserIds->contains($selectedPatientId)) {
                $query->where('user_id', $selectedPatientId);
            }

            if ($onlyUnreviewed) {
                $query->whereNull('reviewed_at');
            }

            $meals = $query->limit(50)->get();
        }

        return view('doctor.meal-reviews', [
            'pageTitle' => 'مراجعة الوجبات AI',
            'activePage' => 'meal-reviews',
            'meals' => $meals,
            'patients' => $patients,
            'selectedPatientId' => $selectedPatientId,
            'onlyUnreviewed' => $onlyUnreviewed,
        ]);
    }

    public function review(Request $request, PatientMeal $meal): RedirectResponse
    {
        $validated = $request->validate([
            'doctor_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $doctorProfile = DoctorProfile::where('user_id', auth()->id())->first();

        abort_unless($doctorProfile, 404);

        $isMyPatient = $doctorProfile->patientProfiles()
            ->where('doctor_request_status', 'approved')
            ->where('user_id', $meal->user_id)
            ->exists();

        abort_unless($isMyPatient, 403);

        $meal->update([
            'doctor_note' => $validated['doctor_note'] ?? null,
            'reviewed_at' => now(),
        ]);

        app(AppNotificationService::class)->send(
            recipientUserId: $meal->user_id,
            recipientRole: 'patient',
            type: 'meal_reviewed',
            title: 'راجع طبيبك وجبتك',
            body: 'راجع طبيبك وجبة "' . $meal->meal_name . '" وترك ملاحظة عليها.',
            url: route('patient.calories'),
            actorUserId: auth()->id()
        );

        return back()->with('success', 'تم حفظ ملاحظتك على الوجبة.');
    }
}
