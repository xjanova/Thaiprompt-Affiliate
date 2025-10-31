<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationManagementController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Display all notifications
     */
    public function index(Request $request)
    {
        $query = Notification::with('user')
            ->orderBy('created_at', 'desc');

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by broadcast
        if ($request->filled('is_broadcast')) {
            $query->where('is_broadcast', $request->is_broadcast);
        }

        // Filter by priority
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        $notifications = $query->paginate(20);

        return view('admin.notifications.index', compact('notifications'));
    }

    /**
     * Show create notification form
     */
    public function create()
    {
        $users = User::select('id', 'name', 'email')->get();
        return view('admin.notifications.create', compact('users'));
    }

    /**
     * Store new notification
     */
    public function store(Request $request)
    {
        $request->validate([
            'recipient_type' => 'required|in:single,multiple,all',
            'user_ids' => 'required_if:recipient_type,single,multiple|array',
            'user_ids.*' => 'exists:users,id',
            'type' => 'required|string|max:50',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'action_url' => 'nullable|string|max:255',
            'action_text' => 'nullable|string|max:100',
            'priority' => 'required|in:low,normal,high,urgent',
            'is_important' => 'boolean',
            'show_immediately' => 'boolean',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:50',
            'expires_at' => 'nullable|date',
        ]);

        try {
            $data = [
                'sent_by_admin' => auth()->id(),
                'sent_at' => now()->toDateTimeString(),
            ];

            if ($request->recipient_type === 'all') {
                // Send to all users
                $count = $this->notificationService->broadcast(
                    $request->type,
                    $request->title,
                    $request->message,
                    $data,
                    $request->action_url,
                    $request->action_text,
                    $request->priority,
                    $request->boolean('is_important'),
                    $request->boolean('show_immediately'),
                    $request->icon,
                    $request->color
                );

                return redirect()
                    ->route('admin.notifications.index')
                    ->with('success', "ส่งการแจ้งเตือนถึง {$count} ผู้ใช้เรียบร้อยแล้ว");
            } else {
                // Send to specific users
                $userIds = $request->user_ids;
                $count = $this->notificationService->broadcast(
                    $request->type,
                    $request->title,
                    $request->message,
                    $data,
                    $request->action_url,
                    $request->action_text,
                    $request->priority,
                    $request->boolean('is_important'),
                    $request->boolean('show_immediately'),
                    $request->icon,
                    $request->color,
                    $userIds
                );

                return redirect()
                    ->route('admin.notifications.index')
                    ->with('success', "ส่งการแจ้งเตือนถึง {$count} ผู้ใช้เรียบร้อยแล้ว");
            }
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'เกิดข้อผิดพลาดในการส่งการแจ้งเตือน: ' . $e->getMessage());
        }
    }

    /**
     * Show notification details
     */
    public function show($id)
    {
        $notification = Notification::with('user')->findOrFail($id);
        return view('admin.notifications.show', compact('notification'));
    }

    /**
     * Delete notification
     */
    public function destroy($id)
    {
        $notification = Notification::findOrFail($id);
        $notification->delete();

        return redirect()
            ->route('admin.notifications.index')
            ->with('success', 'ลบการแจ้งเตือนเรียบร้อยแล้ว');
    }

    /**
     * Delete multiple notifications
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'exists:notifications,id',
        ]);

        Notification::whereIn('id', $request->notification_ids)->delete();

        return redirect()
            ->route('admin.notifications.index')
            ->with('success', 'ลบการแจ้งเตือนที่เลือกเรียบร้อยแล้ว');
    }

    /**
     * Get notification statistics
     */
    public function statistics()
    {
        $stats = [
            'total' => Notification::count(),
            'unread' => Notification::unread()->count(),
            'read' => Notification::read()->count(),
            'broadcast' => Notification::broadcast()->count(),
            'immediate' => Notification::immediate()->count(),
            'by_type' => Notification::selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->get(),
            'by_priority' => Notification::selectRaw('priority, COUNT(*) as count')
                ->groupBy('priority')
                ->get(),
        ];

        return response()->json($stats);
    }
}
