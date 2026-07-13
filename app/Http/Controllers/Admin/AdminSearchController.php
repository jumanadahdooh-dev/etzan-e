<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\DoctorApplication;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class AdminSearchController extends Controller
{
    /**
     * بحث حقيقي بالبيانات: بيدوّر بأسماء المستخدمين وإيميلاتهم، عناوين المقالات،
     * أسماء التخصصات، وطلبات انضمام الأطباء — ويرجّع أول نتائج مطابقة من كل نوع.
     */
    public function search(Request $request)
    {
        $term = trim((string) $request->query('q', ''));

        if ($term === '' || mb_strlen($term) < 2) {
            return response()->json(['results' => []]);
        }

        $results = [];

        // المستخدمون (بالاسم أو الإيميل)
        User::query()
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            })
            ->limit(5)
            ->get(['id', 'name', 'email', 'role'])
            ->each(function ($user) use (&$results) {
                $roleLabel = match ($user->role) {
                    'doctor' => 'طبيب',
                    'admin' => 'إدارة',
                    default => 'مريض',
                };

                $results[] = [
                    'type' => 'مستخدم',
                    'icon' => 'fa-solid fa-user',
                    'title' => $user->name,
                    'subtitle' => $user->email . ' · ' . $roleLabel,
                    'url' => $this->safeRoute(['admin.users.index', 'admin.admin-users'], '/admin/users') . '?search=' . urlencode($user->email),
                ];
            });

        // المقالات (بالعنوان)
        if (class_exists(Article::class)) {
            Article::query()
                ->where('title', 'like', "%{$term}%")
                ->limit(5)
                ->get(['id', 'title'])
                ->each(function ($article) use (&$results) {
                    $results[] = [
                        'type' => 'مقال',
                        'icon' => 'fa-regular fa-newspaper',
                        'title' => $article->title,
                        'subtitle' => 'محتوى صحي',
                        'url' => $this->safeRoute(['admin.articles.index'], '/admin/articles') . '/' . $article->id,
                    ];
                });
        }

        // التخصصات (بالاسم)
        if (class_exists(Specialty::class)) {
            Specialty::query()
                ->where('name', 'like', "%{$term}%")
                ->limit(5)
                ->get(['id', 'name'])
                ->each(function ($specialty) use (&$results) {
                    $results[] = [
                        'type' => 'تخصص',
                        'icon' => 'fa-solid fa-stethoscope',
                        'title' => $specialty->name,
                        'subtitle' => 'تخصص طبي',
                        'url' => $this->safeRoute(['admin.specialties.index'], '/admin/specialties'),
                    ];
                });
        }

        // طلبات انضمام الأطباء (بالاسم أو الإيميل)
        if (class_exists(DoctorApplication::class)) {
            DoctorApplication::query()
                ->where(function ($q) use ($term) {
                    $q->where('full_name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                })
                ->limit(5)
                ->get(['id', 'full_name', 'email'])
                ->each(function ($application) use (&$results) {
                    $results[] = [
                        'type' => 'طلب طبيب',
                        'icon' => 'fa-solid fa-user-doctor',
                        'title' => $application->full_name ?? $application->email,
                        'subtitle' => 'طلب انضمام',
                        'url' => $this->safeRoute(['admin.doctor-applications-show'], '/admin/doctor-applications', ['doctorApplication' => $application->id]),
                    ];
                });
        }

        return response()->json(['results' => $results]);
    }

    private function safeRoute(array $names, string $fallback, array $params = []): string
    {
        foreach ($names as $name) {
            if (Route::has($name)) {
                return route($name, $params);
            }
        }

        return $fallback;
    }
}
