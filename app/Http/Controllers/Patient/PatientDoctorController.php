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

class PatientDoctorController extends Controller
{
    use PatientContextHelpers;

    public function recommendedDoctorsPage(): View
    {
        return view('patient.recommended-doctors', $this->dashboardData([
            'pageTitle' => 'الأطباء المناسبون',
            'activePage' => 'profile',
        ]));
    }


    public function myDoctor(): View
{
    $user = auth()->user();

    $data = $this->dashboardData([
        'pageTitle' => 'طبيبي',
        'activePage' => 'my-doctor',
    ]);

    $profile = $this->patientProfile($user?->id);
    $selectedDoctor = $this->selectedDoctor($profile);

    $state = 'no_doctor';

    if (! empty($selectedDoctor['is_selected'])) {
        $requestStatus = $selectedDoctor['request_status'] ?? null;

        $state = match ($requestStatus) {
            'approved' => 'approved',
            'rejected', 'declined' => 'rejected',
            default => 'pending',
        };
    }

    $doctor = $selectedDoctor;
    $doctorArticles = [];

    if (! empty($selectedDoctor['id']) && $this->tableExists('doctor_profiles') && $this->tableExists('users')) {
        $select = [
            'doctor_profiles.id',
            'doctor_profiles.user_id',
            'users.name as user_name',
            'users.email as user_email',
        ];

        foreach (['avatar', 'profile_photo_path', 'image', 'photo', 'gender'] as $column) {
            if ($this->columnExists('users', $column)) {
                $select[] = 'users.' . $column . ' as user_' . $column;
            }
        }

        foreach ([
            'bio',
            'about',
            'description',
            'specialty',
            'specialization',
            'years_experience',
            'experience_years',
            'photo_path',
            'avatar',
            'image',
            'consultation_type',
            'gender',
            'doctor_gender',
        ] as $column) {
            if ($this->columnExists('doctor_profiles', $column)) {
                $select[] = 'doctor_profiles.' . $column . ' as doctor_' . $column;
            }
        }

        $doctorRow = DB::table('doctor_profiles')
            ->join('users', 'users.id', '=', 'doctor_profiles.user_id')
            ->where('doctor_profiles.id', $selectedDoctor['id'])
            ->select($select)
            ->first();

        if ($doctorRow) {
            $specialty = $this->doctorSpecialty((int) $doctorRow->id);

            $gender = $this->normalizeGender(
                $this->valueFrom($doctorRow, ['doctor_doctor_gender', 'doctor_gender', 'user_gender'], 'unknown')
            );

            $consultationKey = $this->normalizeConsultationType(
                $this->valueFrom($doctorRow, ['doctor_consultation_type'], 'online')
            );

            $photo = $this->valueFrom($doctorRow, [
                'doctor_photo_path',
                'doctor_avatar',
                'doctor_image',
                'user_avatar',
                'user_profile_photo_path',
                'user_image',
                'user_photo',
            ]);

            $experience = (int) $this->valueFrom($doctorRow, [
                'doctor_years_experience',
                'doctor_experience_years',
            ], 5);

            $bio = $this->valueFrom($doctorRow, [
                'doctor_bio',
                'doctor_about',
                'doctor_description',
            ], 'طبيب مختص يساعدك على بناء متابعة صحية مناسبة لحالتك وهدفك.');

            $review = $this->doctorReviewSummary((int) $doctorRow->id);

            $preferredGender = (string) $this->valueFrom($profile, ['preferred_doctor_gender'], 'any');
            $preferredType = (string) $this->valueFrom($profile, ['preferred_consultation_type'], 'any');

            $preferredGender = in_array($preferredGender, ['female', 'male'], true) ? $preferredGender : 'any';
            $preferredType = in_array($preferredType, ['online', 'clinic'], true) ? $preferredType : 'any';

            $match = $this->doctorMatch(
                $profile,
                $specialty,
                $doctorRow,
                $gender,
                $consultationKey,
                $preferredGender,
                $preferredType,
                $experience
            );

            $hasReviews = (bool) ($review['has_reviews'] ?? ((int) ($review['count'] ?? 0) > 0));

            $doctorArticles = $this->doctorArticles((int) $doctorRow->id, (int) $doctorRow->user_id);

            $doctor = array_merge($selectedDoctor, [
                'id' => (int) $doctorRow->id,
                'user_id' => (int) $doctorRow->user_id,
                'name' => 'د. ' . ($doctorRow->user_name ?: 'طبيب اتزان'),
                'email' => $doctorRow->user_email ?? null,
                'specialty' => $specialty,
                'avatar' => $this->imageUrl($photo) ?: $this->placeholderImage($doctorRow->user_name ?: 'طبيب'),
                'bio' => $bio,
                'gender' => $gender,
                'gender_label' => $gender === 'female' ? 'طبيبة' : ($gender === 'male' ? 'طبيب' : 'غير محدد'),
                'consultation_key' => $consultationKey,
                'consultation_type' => $consultationKey === 'clinic' ? 'حضوري' : 'أونلاين',
                'experience' => $experience,
                'rating' => $review['average'] ?? null,
                'reviews_count' => $review['count'] ?? 0,
                'has_reviews' => $hasReviews,
                'match_score' => $match['score'],
                'match_reason' => $match['reason'],
                'badges' => $match['badges'],
            ]);
        }
    }

    $myReview = null;

        if (! empty($doctor['id']) && $this->tableExists('doctor_reviews')) {
            $doctorReviewColumn = $this->firstExistingColumn('doctor_reviews', [
                'doctor_profile_id',
                'doctor_id',
            ]);

            $patientReviewColumn = $this->firstExistingColumn('doctor_reviews', [
                'patient_user_id',
                'user_id',
                'patient_id',
            ]);

            if ($doctorReviewColumn && $patientReviewColumn) {
                $patientReviewValue = (int) $user?->id;

                $myReview = DB::table('doctor_reviews')
                    ->where($doctorReviewColumn, $doctor['id'])
                    ->where($patientReviewColumn, $patientReviewValue)
                    ->first();
                }
        }


    $data['myDoctorPage'] = [
        'state' => $state,
        'doctor' => $doctor,
        'articles' => $doctorArticles,
        'nextAppointment' => $data['nextAppointment'] ?? null,
        'can_book' => $state === 'approved',
        'can_message' => $state === 'approved',
        'can_review' => $state === 'approved',
        'my_review' => $myReview,
    ];

    return view('patient.my-doctor', $data);
}


    public function doctorDetails(int $doctorProfile): View
{
    $user = auth()->user();

    $data = $this->dashboardData([
        'pageTitle' => 'تفاصيل الطبيب',
        'activePage' => 'profile',
    ]);

    $patientProfile = $this->patientProfile($user?->id);

    if (! $this->tableExists('doctor_profiles') || ! $this->tableExists('users')) {
        abort(404);
    }

    $select = [
        'doctor_profiles.id',
        'doctor_profiles.user_id',
        'users.name as user_name',
        'users.email as user_email',
    ];

    foreach (['avatar', 'profile_photo_path', 'image', 'photo', 'gender'] as $column) {
        if ($this->columnExists('users', $column)) {
            $select[] = 'users.' . $column . ' as user_' . $column;
        }
    }

    foreach ([
        'bio',
        'about',
        'description',
        'specialty',
        'specialization',
        'years_experience',
        'experience_years',
        'photo_path',
        'avatar',
        'image',
        'consultation_type',
        'gender',
        'doctor_gender',
    ] as $column) {
        if ($this->columnExists('doctor_profiles', $column)) {
            $select[] = 'doctor_profiles.' . $column . ' as doctor_' . $column;
        }
    }

    $doctorRow = DB::table('doctor_profiles')
        ->join('users', 'users.id', '=', 'doctor_profiles.user_id')
        ->where('doctor_profiles.id', $doctorProfile)
        ->select($select)
        ->first();

    abort_if(! $doctorRow, 404);

    $specialty = $this->doctorSpecialty((int) $doctorRow->id);

    $gender = $this->normalizeGender(
        $this->valueFrom($doctorRow, ['doctor_doctor_gender', 'doctor_gender', 'user_gender'], 'unknown')
    );

    $consultationKey = $this->normalizeConsultationType(
        $this->valueFrom($doctorRow, ['doctor_consultation_type'], 'online')
    );

    $photo = $this->valueFrom($doctorRow, [
        'doctor_photo_path',
        'doctor_avatar',
        'doctor_image',
        'user_avatar',
        'user_profile_photo_path',
        'user_image',
        'user_photo',
    ]);

    $experience = (int) $this->valueFrom($doctorRow, [
        'doctor_years_experience',
        'doctor_experience_years',
    ], 5);

    $bio = $this->valueFrom($doctorRow, [
        'doctor_bio',
        'doctor_about',
        'doctor_description',
    ], 'طبيب مختص يساعدك على بناء متابعة صحية مناسبة لحالتك وهدفك.');

    $review = $this->doctorReviewSummary((int) $doctorRow->id);

    $preferredGender = (string) $this->valueFrom($patientProfile, ['preferred_doctor_gender'], 'any');
    $preferredType = (string) $this->valueFrom($patientProfile, ['preferred_consultation_type'], 'any');

    $preferredGender = in_array($preferredGender, ['female', 'male'], true) ? $preferredGender : 'any';
    $preferredType = in_array($preferredType, ['online', 'clinic'], true) ? $preferredType : 'any';

    $match = $this->doctorMatch(
        $patientProfile,
        $specialty,
        $doctorRow,
        $gender,
        $consultationKey,
        $preferredGender,
        $preferredType,
        $experience
    );

    $currentDoctor = $this->selectedDoctor($patientProfile);

    $isCurrentDoctor = ! empty($currentDoctor['id'])
        && (int) $currentDoctor['id'] === (int) $doctorRow->id;

    $doctorRequestStatus = $currentDoctor['request_status'] ?? null;

    $doctor = [
        'id' => (int) $doctorRow->id,
        'user_id' => (int) $doctorRow->user_id,
        'name' => 'د. ' . ($doctorRow->user_name ?: 'طبيب اتزان'),
        'email' => $doctorRow->user_email ?? null,
        'specialty' => $specialty,
        'avatar' => $this->imageUrl($photo) ?: $this->placeholderImage($doctorRow->user_name ?: 'طبيب'),
        'bio' => $bio,
        'gender' => $gender,
        'gender_label' => $gender === 'female' ? 'طبيبة' : ($gender === 'male' ? 'طبيب' : 'غير محدد'),
        'consultation_key' => $consultationKey,
        'consultation_type' => $consultationKey === 'clinic' ? 'حضوري' : 'أونلاين',
        'experience' => $experience,
        'rating' => $review['average'],
        'reviews_count' => $review['count'],
        'has_reviews' => $review['has_reviews'] ?? false,
        'match_score' => $match['score'],
        'match_reason' => $match['reason'],
        'badges' => $match['badges'],
        'articles' => $this->doctorArticles((int) $doctorRow->id, (int) $doctorRow->user_id),
        'is_current_doctor' => $isCurrentDoctor,
        'request_status' => $isCurrentDoctor ? $doctorRequestStatus : null,
    ];

    $data['doctorDetails'] = $doctor;

    return view('patient.doctor-details', $data);
}


    public function storeDoctorReview(Request $request, int $doctorProfile): RedirectResponse
    {
        $user = auth()->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $profile = $this->patientProfile($user?->id);
        $selectedDoctor = $this->selectedDoctor($profile);

        $isSelectedDoctor = ! empty($selectedDoctor['is_selected'])
            && (int) ($selectedDoctor['id'] ?? 0) === (int) $doctorProfile;

        $isApproved = ($selectedDoctor['request_status'] ?? null) === 'approved';

        if (! $isSelectedDoctor || ! $isApproved) {
            return back()->with('error', 'لا يمكنك تقييم الطبيب قبل اعتماد المتابعة.');
        }

        if (! $this->tableExists('doctor_reviews')) {
            return back()->with('error', 'جدول تقييمات الأطباء غير موجود.');
        }

        $doctorColumn = $this->firstExistingColumn('doctor_reviews', [
            'doctor_profile_id',
            'doctor_id',
        ]);

        $patientColumn = $this->firstExistingColumn('doctor_reviews', [
            'patient_user_id',
            'user_id',
            'patient_id',
        ]);

        $ratingColumn = $this->firstExistingColumn('doctor_reviews', [
            'rating',
            'stars',
            'rate',
        ]);

        $commentColumn = $this->firstExistingColumn('doctor_reviews', [
            'comment',
            'review',
            'body',
        ]);

        if (! $doctorColumn || ! $patientColumn || ! $ratingColumn) {
            return back()->with('error', 'أعمدة جدول تقييمات الأطباء غير مكتملة.');
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $patientReviewValue = (int) $user->id;

        $lookup = [
            $doctorColumn => $doctorProfile,
            $patientColumn => $patientReviewValue,
        ];

        $payload = [
            $ratingColumn => (int) $validated['rating'],
        ];

        if ($this->columnExists('doctor_reviews', 'is_recommended')) {
            $payload['is_recommended'] = $request->boolean('is_recommended');
        }

        if ($commentColumn) {
            $payload[$commentColumn] = $validated['comment'] ?? null;
        }

        if ($this->columnExists('doctor_reviews', 'status')) {
            $payload['status'] = 'published';
        }

        if ($this->columnExists('doctor_reviews', 'updated_at')) {
            $payload['updated_at'] = now();
        }

        if ($this->columnExists('doctor_reviews', 'created_at')) {
            $payload['created_at'] = now();
        }

        DB::table('doctor_reviews')->updateOrInsert($lookup, $payload);

        return back()->with('success', 'تم حفظ تقييمك للطبيب بنجاح.');
    }


    public function selectDoctor(Request $request, int $doctorProfile): RedirectResponse
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$this->tableExists('patient_profiles')) {
            return back()->with('error', 'جدول patient_profiles غير موجود.');
        }

        if (!$this->tableExists('doctor_profiles')) {
            return back()->with('error', 'جدول doctor_profiles غير موجود.');
        }

        $doctorExists = DB::table('doctor_profiles')->where('id', $doctorProfile)->exists();

        if (!$doctorExists) {
            return back()->with('error', 'الطبيب المحدد غير موجود.');
        }

        $profile = $this->patientProfile($user->id);

        if (!$profile || $this->profileCompletion($profile) < 100) {
            return redirect()->route('patient.profile')->with('error', 'أكمل ملفك الصحي أولاً قبل اختيار الطبيب.');
        }

        $payload = [];
        $this->setFirstExistingColumn($payload, 'patient_profiles', ['doctor_profile_id', 'selected_doctor_id', 'doctor_id'], $doctorProfile);
        $this->setFirstExistingColumn($payload, 'patient_profiles', ['has_selected_doctor', 'doctor_selected', 'is_doctor_selected'], true);
        $this->setFirstExistingColumn($payload, 'patient_profiles', ['doctor_request_status'], 'pending');

        if ($this->columnExists('patient_profiles', 'updated_at')) {
            $payload['updated_at'] = now();
        }

        if (empty($payload)) {
            return back()->with('error', 'لا يوجد عمود مناسب لحفظ طلب الطبيب في جدول patient_profiles.');
        }

        DB::table('patient_profiles')->where('id', $profile->id)->update($payload);

        $this->createAppNotification(
            recipientUserId: $user->id,
            actorUserId: null,
            type: 'doctor_request_pending',
            title: 'تم إرسال طلب المتابعة',
            body: 'تم إرسال طلب اختيار الطبيب، وسيتم إشعارك عند موافقة الطبيب أو اعتذاره.',
            url: route('patient.profile')
        );

        $doctorUserId = $this->doctorUserIdFromProfile($doctorProfile);

        if ($doctorUserId) {
            $this->createAppNotification(
                recipientUserId: $doctorUserId,
                actorUserId: $user->id,
                type: 'doctor_followup_request_received',
                title: 'طلب متابعة جديد',
                body: 'وصل طلب متابعة جديد من مريض. راجع الطلب ثم اختر الموافقة أو الاعتذار.',
                url: url('/doctor/patients'),
                relatedId: $profile->id ?? null,
                relatedType: 'doctor_followup_request',
                recipientRole: 'doctor',
                data: [
                    'patient_user_id' => $user->id,
                    'patient_profile_id' => $profile->id ?? null,
                    'doctor_profile_id' => $doctorProfile,
                ]
            );
        }

        $this->setFirstExistingColumn($payload, 'patient_profiles', ['doctor_request_status'], 'pending');

        return redirect()
            ->route('patient.doctor.current')
            ->with('success', 'تم إرسال طلب اختيار الطبيب. بانتظار موافقة الطبيب.');

            }

}
