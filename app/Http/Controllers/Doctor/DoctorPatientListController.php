<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DoctorPatientListController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();

        $doctorProfile = DB::table('doctor_profiles')->where('user_id', $user->id)->first();

        if (!$doctorProfile) {
            return view('doctor.patients', [
                'pageTitle' => 'مرضاي',
                'activePage' => 'patients',
                'patients' => collect(),
                'search' => '',
            ]);
        }

        $search = trim((string) $request->get('search'));

        $select = [
            'patient_profiles.id',
            'patient_profiles.user_id',
            'patient_profiles.gender',
            'patient_profiles.birth_date',
            'patient_profiles.health_goal',
            'patient_profiles.medical_conditions',
            'patient_profiles.updated_at',
            'users.name as patient_name',
            'users.email as patient_email',
        ];

        foreach (['avatar', 'profile_photo', 'photo', 'image', 'profile_photo_path'] as $avatarColumn) {
            if (Schema::hasColumn('patient_profiles', $avatarColumn)) {
                $select[] = 'patient_profiles.' . $avatarColumn;
            }
        }

        $query = DB::table('patient_profiles')
            ->join('users', 'users.id', '=', 'patient_profiles.user_id')
            ->where('patient_profiles.doctor_profile_id', $doctorProfile->id)
            ->where('patient_profiles.doctor_request_status', 'approved')
            ->select($select);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%");
            });
        }

        $patients = $query->orderByDesc('patient_profiles.updated_at')->get()->map(function ($row) {
            $row->age = !empty($row->birth_date)
                ? (int) \Illuminate\Support\Carbon::parse($row->birth_date)->age
                : null;

            $conditions = [];
            if (!empty($row->medical_conditions)) {
                $decoded = json_decode($row->medical_conditions, true);
                if (is_array($decoded)) {
                    $conditions = array_values(array_filter($decoded, fn ($c) => $c !== 'none'));
                }
            }
            $row->conditions = $conditions;
            $row->avatarUrl = $this->resolveAvatarUrl($row);

            return $row;
        });

        return view('doctor.patients', [
            'pageTitle' => 'مرضاي',
            'activePage' => 'patients',
            'patients' => $patients,
            'search' => $search,
        ]);
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
}
