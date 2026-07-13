<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DoctorNotificationController extends Controller
{
    /**
     * خريطة أيقونات موثوقة لكل أنواع الإشعارات الحقيقية يلي المشروع
     * بيبعتها للطبيب فعلياً (من PatientHomeController وDoctorPatientRequestController)،
     * حتى لو الموديل نفسه ما بيعرف النوع.
     */
    private const TYPE_ICONS = [
        'doctor_followup_request_received' => 'user-plus',
        'appointment_request_received' => 'calendar-plus',
        'appointment_request_updated' => 'calendar-clock',
        'message_received' => 'message-circle',
        'doctor_request_approved' => 'circle-check',
        'doctor_request_rejected' => 'circle-x',
        'doctor_followup_completed' => 'star',
    ];

    private function iconFor(AppNotification $notification): string
    {
        return self::TYPE_ICONS[$notification->type] ?? ($notification->type_icon ?? 'bell-ring');
    }

    public function index(Request $request)
    {
        $query = AppNotification::forUser(auth()->id())->latest();

        if ($request->filled('status')) {
            if ($request->status === 'unread') {
                $query->unread();
            }
            if ($request->status === 'read') {
                $query->read();
            }
        }

        $notifications = $query->paginate(12)->withQueryString();

        $stats = [
            'total' => AppNotification::forUser(auth()->id())->count(),
            'unread' => AppNotification::forUser(auth()->id())->unread()->count(),
            'today' => AppNotification::forUser(auth()->id())->whereDate('created_at', today())->count(),
        ];

        return view('doctor.notifications', [
            'pageTitle' => 'الإشعارات',
            'activePage' => 'notifications',
            'notifications' => $notifications,
            'stats' => $stats,
            'typeIcons' => self::TYPE_ICONS,
        ]);
    }

    public function unreadCount()
    {
        return response()->json([
            'count' => AppNotification::forUser(auth()->id())->unread()->count(),
        ]);
    }

    public function dropdown()
    {
        $notifications = AppNotification::forUser(auth()->id())
            ->unread()
            ->latest()
            ->take(6)
            ->get();

        return response()->json([
            'notifications' => $notifications->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'body' => $notification->body,
                    'icon' => $this->iconFor($notification),
                    'time' => $notification->created_at?->diffForHumans(),
                    'is_unread' => $notification->is_unread,
                    'url' => route('doctor.notifications.open', $notification),
                ];
            }),
        ]);
    }

    public function markAllRead(Request $request)
    {
        AppNotification::forUser(auth()->id())->unread()->update([
            'read_at' => now(),
            'is_read' => true,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'تم تعليم كل الإشعارات كمقروءة.');
    }

    public function markRead(Request $request, AppNotification $appNotification)
    {
        abort_unless($appNotification->recipient_user_id === auth()->id(), 403);

        if (is_null($appNotification->read_at)) {
            $appNotification->update(['read_at' => now(), 'is_read' => true]);
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'تم تعليم الإشعار كمقروء.');
    }

    public function open(AppNotification $appNotification)
    {
        abort_unless($appNotification->recipient_user_id === auth()->id(), 403);

        if (is_null($appNotification->read_at)) {
            $appNotification->update(['read_at' => now(), 'is_read' => true]);
        }

        $url = $appNotification->url ?: route('doctor.notifications.index');

        if (! Str::startsWith($url, [url('/'), '/'])) {
            $url = route('doctor.notifications.index');
        }

        return redirect()->to($url);
    }
}
