<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get all notifications for user.
     */
    public function index()
    {
        $notifications = auth()->user()->notifications()->orderBy('created_at', 'desc')->paginate(20);
        return view('notifications.index', compact('notifications'));
    }

    /**
     * Get unread notifications count.
     */
    public function unreadCount()
    {
        return response()->json([
            'count' => auth()->user()->unreadNotifications()->count(),
        ]);
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(Notification $notification)
    {
        $notification->markAsRead();
        return response()->json(['success' => true]);
    }

    /**
     * Mark all as read.
     */
    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications()->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Get latest notifications (for bell).
     */
    public function latest()
    {
        $notifications = auth()->user()
            ->notifications()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json($notifications);
    }
}
