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

    /**
     * Get immediate notifications (for popup)
     */
    public function immediate()
    {
        $notifications = $this->notificationService->getImmediateNotifications(auth()->user());

        // Mark notifications as shown
        foreach ($notifications as $notification) {
            $this->notificationService->markAsShown($notification);
        }

        return response()->json([
            'notifications' => $notifications->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'icon' => $notification->icon,
                    'color' => $notification->color,
                    'priority' => $notification->priority,
                    'action_url' => $notification->action_url,
                    'action_text' => $notification->action_text,
                    'created_at' => $notification->created_at->diffForHumans(),
                ];
            }),
        ]);
    }

    /**
     * Bulk delete notifications
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'integer',
        ]);

        $count = auth()->user()
            ->notifications()
            ->whereIn('id', $request->notification_ids)
            ->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'count' => $count,
                'message' => "ลบการแจ้งเตือน {$count} รายการเรียบร้อยแล้ว"
            ]);
        }

        return redirect()->back()->with('success', "ลบการแจ้งเตือน {$count} รายการเรียบร้อยแล้ว");
    }

    /**
     * Bulk mark as read
     */
    public function bulkMarkAsRead(Request $request)
    {
        $request->validate([
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'integer',
        ]);

        $count = auth()->user()
            ->notifications()
            ->whereIn('id', $request->notification_ids)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'count' => $count,
                'message' => "ทำเครื่องหมายอ่าน {$count} รายการเรียบร้อยแล้ว"
            ]);
        }

        return redirect()->back()->with('success', "ทำเครื่องหมายอ่าน {$count} รายการเรียบร้อยแล้ว");
    }

    /**
     * Delete all read notifications
     */
    public function deleteAllRead()
    {
        $count = auth()->user()
            ->notifications()
            ->read()
            ->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'count' => $count,
                'message' => "ลบการแจ้งเตือนที่อ่านแล้ว {$count} รายการเรียบร้อยแล้ว"
            ]);
        }

        return redirect()->back()->with('success', "ลบการแจ้งเตือนที่อ่านแล้ว {$count} รายการเรียบร้อยแล้ว");
    }
}
