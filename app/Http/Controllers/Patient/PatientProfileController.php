<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Patient\Concerns\PatientContextHelpers;
use App\Http\Requests\Patient\BookAppointmentRequest;
use App\Http\Requests\Patient\CompleteProfileRequest;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\PatientDailyCalorieGoal;
use App\Models\PatientMeal;
use App\Services\AiMealAnalysisService;
use App\Services\AppNotificationService;
use App\Services\CalorieSuggestionService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PatientProfileController extends Controller
{
    use PatientContextHelpers;

    public function profile(): View
    {
        return view('patient.profile', $this->dashboardData([
            'pageTitle' => 'ملفي الصحي',
            'activePage' => 'profile',
        ]));
    }


    public function completeProfile(CompleteProfileRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$this->tableExists('patient_profiles')) {
            return back()->withInput()->with('error', 'جدول patient_profiles غير موجود بعد.');
        }

        $conditions = $validated['medical_conditions'] ?? [];

        if (empty($conditions)) {
            $conditions = ['none'];
        }

        $payload = [];
        $avatarPath = null;

        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('patients/avatars', 'public');
        }

        $this->setFirstExistingColumn($payload, 'patient_profiles', ['user_id'], $user->id);
        $this->setFirstExistingColumn($payload, 'patient_profiles', ['height', 'height_cm'], $validated['height_cm']);
        $this->setFirstExistingColumn($payload, 'patient_profiles', ['weight', 'weight_kg', 'current_weight'], $validated['weight_kg']);
        $this->setFirstExistingColumn($payload, 'patient_profiles', ['birth_date', 'date_of_birth'], $validated['birth_date']);
        $this->setFirstExistingColumn($payload, 'patient_profiles', ['gender'], $validated['gender']);
        $this->setFirstExistingColumn($payload, 'patient_profiles', ['phone'], $validated['phone'] ?? null);
        $this->setFirstExistingColumn($payload, 'patient_profiles', ['city'], $validated['city'] ?? null);
        $this->setFirstExistingColumn($payload, 'patient_profiles', ['health_goal', 'goal', 'main_goal', 'target_goal', 'goal_type', 'main_health_goal', 'health_objective'], $validated['health_goal']);
        $this->setFirstExistingColumn($payload, 'patient_profiles', ['activity_level'], $validated['activity_level']);
        $this->setFirstExistingColumn($payload, 'patient_profiles', ['medical_conditions', 'health_condition', 'chronic_diseases', 'diseases'], json_encode($conditions, JSON_UNESCAPED_UNICODE));
        $this->setFirstExistingColumn($payload, 'patient_profiles', ['medications'], $validated['medications'] ?? null);
        $this->setFirstExistingColumn($payload, 'patient_profiles', ['allergies'], $validated['allergies'] ?? null);
        $this->setFirstExistingColumn($payload, 'patient_profiles', ['meals_per_day'], $validated['meals_per_day'] ?? null);
        $this->setFirstExistingColumn($payload, 'patient_profiles', ['sleep_hours'], $validated['sleep_hours'] ?? null);
        $this->setFirstExistingColumn($payload, 'patient_profiles', ['water_cups'], $validated['water_cups'] ?? null);
        $this->setFirstExistingColumn($payload, 'patient_profiles', ['preferred_doctor_gender'], $validated['preferred_doctor_gender'] ?? 'any');
        $this->setFirstExistingColumn($payload, 'patient_profiles', ['preferred_consultation_type'], $validated['preferred_consultation_type'] ?? 'any');
        $this->setFirstExistingColumn($payload, 'patient_profiles', ['notes', 'patient_notes'], $validated['notes'] ?? null);

        if ($avatarPath) {
            $this->setFirstExistingColumn($payload, 'patient_profiles', ['avatar', 'profile_photo', 'photo', 'image', 'profile_photo_path'], $avatarPath);

            $userPayload = [];

            foreach (['avatar', 'profile_photo_path', 'image', 'photo'] as $column) {
                if ($this->columnExists('users', $column)) {
                    $userPayload[$column] = $avatarPath;
                    break;
                }
            }

            if (!empty($userPayload)) {
                DB::table('users')->where('id', $user->id)->update($userPayload);
            }
        }

        $this->setFirstExistingColumn($payload, 'patient_profiles', ['profile_completion', 'completion', 'completion_percentage', 'profile_completion_percentage'], 100);
        $this->setFirstExistingColumn($payload, 'patient_profiles', ['profile_completed', 'has_completed_profile', 'is_profile_complete', 'completed', 'is_completed'], true);

        if ($this->columnExists('patient_profiles', 'updated_at')) {
            $payload['updated_at'] = now();
        }

        $profile = $this->patientProfile($user->id);

        if ($profile) {
            DB::table('patient_profiles')->where('id', $profile->id)->update($payload);
        } else {
            if ($this->columnExists('patient_profiles', 'created_at')) {
                $payload['created_at'] = now();
            }

            DB::table('patient_profiles')->insert($payload);
        }

        app(CalorieSuggestionService::class)->refreshSuggestionForUser($user->id);

        return redirect()
            ->route('patient.doctors.recommended')
            ->with('success', 'تم حفظ ملفك الصحي بنجاح. هذه قائمة الأطباء المناسبين لحالتك.');
   }

}
