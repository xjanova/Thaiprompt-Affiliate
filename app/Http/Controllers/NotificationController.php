<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get user's unread notifications
     */
    public function index()
    {
        $user = auth()->user();
        $notifications = $this->notificationService->getAllNotifications($user, 20);
        $unreadCount = $user->unread_notifications_count;

        return view('notifications.index', compact('notifications', 'unreadCount'));
    }

    /**
     * Get unread notifications (for bell icon)
     */
    public function unread()
    {
        $notifications = $this->notificationService->getUnreadNotifications(auth()->user(), 10);
        $unreadCount = auth()->user()->unread_notifications_count;

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $this->notificationService->markAsRead($notification);

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back();
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        $this->notificationService->markAllAsRead(auth()->user());

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'ทำเครื่องหมายอ่านทั้งหมดแล้ว');
    }

    /**
     * Archive notification
     */
    public function archive($id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->archive();

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'เก็บการแจ้งเตือนเรียบร้อยแล้ว');
    }

    /**
     * Delete notification
     */
    public function destroy($id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'ลบการแจ้งเตือนเรียบร้อยแล้ว');
    }
}
