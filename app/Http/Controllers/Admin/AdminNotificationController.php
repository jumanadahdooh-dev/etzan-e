<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * بعد توحيد نظام الإشعارات، إشعارات الأدمن صارت شخصية لكل أدمن (نفس نمط
 * DoctorNotificationController/PatientNotificationController) بدل الجدول
 * المشترك القديم admin_notifications (كانت حالة القراءة فيه مشتركة بين
 * كل الأدمنز — أدمن يقرأ إشعار بيختفي عند الباقيين).
 */
class AdminNotificationController extends Controller
{
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

        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        $notifications = $query->paginate(12)->withQueryString();

        $stats = [
            'total' => AppNotification::forUser(auth()->id())->count(),
            'unread' => AppNotification::forUser(auth()->id())->unread()->count(),
            'today' => AppNotification::forUser(auth()->id())->whereDate('created_at', today())->count(),
            'doctor_applications' => AppNotification::forUser(auth()->id())->where('type', 'doctor_application')->count(),
            'messages' => AppNotification::forUser(auth()->id())->where('type', 'message')->count(),
        ];

        return view('admin.notifications', compact('notifications', 'stats'));
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
            'notifications' => $notifications->map(function (AppNotification $notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'body' => $notification->body,
                    'icon' => $notification->type_icon,
                    'time' => $notification->created_at?->diffForHumans(),
                    'is_unread' => $notification->is_unread,
                    'url' => route('admin.notifications.open', $notification),
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
            return response()->json([
                'success' => true,
                'count' => 0,
            ]);
        }

        return back()->with('success', 'تم تعليم كل الإشعارات كمقروءة.');
    }

    public function markRead(Request $request, AppNotification $adminNotification)
    {
        abort_unless($adminNotification->recipient_user_id === auth()->id(), 403);

        if (is_null($adminNotification->read_at)) {
            $adminNotification->update([
                'read_at' => now(),
                'is_read' => true,
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
            ]);
        }

        return back()->with('success', 'تم تعليم الإشعار كمقروء.');
    }

    public function open(AppNotification $adminNotification)
    {
        abort_unless($adminNotification->recipient_user_id === auth()->id(), 403);

        if (is_null($adminNotification->read_at)) {
            $adminNotification->update([
                'read_at' => now(),
                'is_read' => true,
            ]);
        }

        $url = $adminNotification->url ?: route('admin.notifications.index');

        if (! Str::startsWith($url, [url('/'), '/'])) {
            $url = route('admin.notifications.index');
        }

        return redirect()->to($url);
    }
}
