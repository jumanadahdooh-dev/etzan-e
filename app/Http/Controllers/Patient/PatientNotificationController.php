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

class PatientNotificationController extends Controller
{
    use PatientContextHelpers;

    public function notifications(): View
    {
        return view('patient.notifications', $this->dashboardData([
            'pageTitle' => 'الإشعارات',
            'activePage' => 'notifications',
        ]));
    }


    public function liveNotifications(): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'notifications_count' => 0,
                'unread_messages' => 0,
                'notifications' => [],
                'messages' => [],
                'support_messages' => [],
            ]);
        }

        $profile = $this->patientProfile($user->id);
        $doctor = $this->selectedDoctor($profile);

        return response()->json([
            'notifications_count' => $this->unreadAppNotificationsCount($user->id),
            'unread_messages' => $this->unreadMessagesCount($user->id),
            'notifications' => $this->patientNotifications($user->id, 5),
            'messages' => $this->latestMessages($user->id, $doctor['user_id'] ?? null),
            'support_messages' => $this->supportMessages($user->id),
        ]);
    }


    public function markNotificationRead(int $notification): RedirectResponse
    {
        $user = auth()->user();

        if (! $user) {
            return back();
        }

        app(AppNotificationService::class)->markAsRead(
            notificationId: $notification,
            recipientUserId: (int) $user->id
        );

        return back();
    }


    public function markAllNotificationsRead(): RedirectResponse
        {
            $user = auth()->user();

            if (! $user) {
                return back();
            }

            app(AppNotificationService::class)->markAllAsRead(
                recipientUserId: (int) $user->id,
                recipientRole: 'patient'
            );

            return back()->with('success', 'تم تحديد كل الإشعارات كمقروءة.');
        }

}
