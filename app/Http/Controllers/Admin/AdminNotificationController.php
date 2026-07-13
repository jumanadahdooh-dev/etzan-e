<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminNotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = AdminNotification::query()->latest();

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
            'total' => AdminNotification::count(),
            'unread' => AdminNotification::unread()->count(),
            'today' => AdminNotification::whereDate('created_at', today())->count(),
            'doctor_applications' => AdminNotification::where('type', 'doctor_application')->count(),
            'messages' => AdminNotification::where('type', 'message')->count(),
        ];

        return view('admin.notifications', compact('notifications', 'stats'));
    }

    public function unreadCount()
    {
        return response()->json([
            'count' => AdminNotification::unread()->count(),
        ]);
    }

    public function dropdown()
    {
        $notifications = AdminNotification::whereNull('read_at')
            ->latest()
            ->take(6)
            ->get();

        return response()->json([
            'notifications' => $notifications->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'body' => $notification->body,
                    'icon' => $notification->type_icon ?? 'fa-regular fa-bell',
                    'time' => $notification->created_at?->diffForHumans(),
                    'is_unread' => is_null($notification->read_at),
                    'url' => route('admin.notifications.open', $notification),
                ];
            }),
        ]);
    }

    public function markAllRead(Request $request)
    {
        AdminNotification::unread()->update([
            'read_at' => now(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'count' => 0,
            ]);
        }

        return back()->with('success', 'تم تعليم كل الإشعارات كمقروءة.');
    }

    public function markRead(Request $request, AdminNotification $adminNotification)
    {
        if (is_null($adminNotification->read_at)) {
            $adminNotification->update([
                'read_at' => now(),
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
            ]);
        }

        return back()->with('success', 'تم تعليم الإشعار كمقروء.');
    }

    public function open(AdminNotification $adminNotification)
    {
        if (is_null($adminNotification->read_at)) {
            $adminNotification->update([
                'read_at' => now(),
            ]);
        }

        $url = $adminNotification->url ?: route('admin.notifications.index');

        if (! Str::startsWith($url, [url('/'), '/'])) {
            $url = route('admin.notifications.index');
        }

        return redirect()->to($url);
    }
}
