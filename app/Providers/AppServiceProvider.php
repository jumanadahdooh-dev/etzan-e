<?php

namespace App\Providers;

use App\Models\AppNotification;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // 5 محاولات بالدقيقة لكل إيميل + IP، و 20 لكل IP (حتى ما يجرب عدة إيميلات من نفس الجهاز)
        RateLimiter::for('login', function (Request $request) {
            $email = Str::lower((string) $request->input('email'));

            return [
                Limit::perMinute(5)->by($email . '|' . $request->ip()),
                Limit::perMinute(20)->by($request->ip()),
            ];
        });

        View::composer([
            'layouts.patient',
            'patient.*',
            'patient.partials.*',
            'patient.topbar',
        ], function ($view) {
            $user = Auth::user();

            if (! $user) {
                $view->with([
                    'notificationsCount' => 0,
                    'patientNotifications' => [],
                    'unreadMessages' => 0,
                ]);

                return;
            }

            $notificationsQuery = AppNotification::query()
                ->where('recipient_user_id', $user->id)
                ->where('recipient_role', 'patient')
                ->latest();

            $notificationsCount = (clone $notificationsQuery)
                ->where(function ($query) {
                    $query->whereNull('read_at')
                        ->orWhere('is_read', false);
                })
                ->count();

            $patientNotifications = (clone $notificationsQuery)
                ->take(8)
                ->get()
                ->map(function ($notification) {
                    return [
                        'id' => $notification->id,
                        'type' => $notification->type,
                        'title' => $notification->title,
                        'body' => $notification->body,
                        'url' => $notification->url ?: route('patient.notifications'),
                        'is_read' => ! is_null($notification->read_at) || (bool) ($notification->is_read ?? false),
                        'time' => optional($notification->created_at)->diffForHumans(),
                    ];
                })
                ->toArray();

            $view->with([
                'notificationsCount' => $notificationsCount,
                'patientNotifications' => $patientNotifications,
                'unreadMessages' => $view->getData()['unreadMessages'] ?? 0,
            ]);
        });
    }
}