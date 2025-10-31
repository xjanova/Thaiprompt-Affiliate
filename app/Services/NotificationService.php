<?php

namespace App\Services;

use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;
use Exception;

class NotificationService
{
    /**
     * Create a notification
     */
    public function create(
        User $user,
        string $type,
        string $title,
        string $message,
        array $data = [],
        ?string $actionUrl = null,
        ?string $actionText = null,
        string $priority = 'normal',
        bool $isImportant = false,
        bool $showImmediately = false,
        ?string $icon = null,
        ?string $color = null
    ): Notification {
        try {
            return Notification::create([
                'user_id' => $user->id,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'data' => $data,
                'action_url' => $actionUrl,
                'action_text' => $actionText,
                'priority' => $priority,
                'is_important' => $isImportant,
                'show_immediately' => $showImmediately,
                'icon' => $icon ?? $this->getDefaultIcon($type),
                'color' => $color ?? $this->getDefaultColor($type),
            ]);
        } catch (Exception $e) {
            Log::error('Failed to create notification: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create notification with notifiable model
     */
    public function createForModel(
        User $user,
        $model,
        string $type,
        string $title,
        string $message,
        array $data = [],
        ?string $actionUrl = null,
        ?string $actionText = null
    ): Notification {
        $notification = $this->create($user, $type, $title, $message, $data, $actionUrl, $actionText);

        $notification->update([
            'notifiable_type' => get_class($model),
            'notifiable_id' => $model->id,
        ]);

        return $notification;
    }

    /**
     * Notify about deposit
     */
    public function notifyDeposit(User $user, float $amount, string $currency = 'THB'): Notification
    {
        return $this->create(
            $user,
            'deposit',
            'เติมเงินสำเร็จ',
            "คุณได้เติมเงินเข้ากระเป๋าเงินจำนวน " . number_format($amount, 2) . " $currency",
            ['amount' => $amount, 'currency' => $currency],
            route('user.wallet.index'),
            'ดูกระเป๋าเงิน',
            'normal',
            false,
            '💵',
            'green'
        );
    }

    /**
     * Notify about withdrawal request
     */
    public function notifyWithdrawalRequest(User $user, $withdrawalRequest): Notification
    {
        return $this->createForModel(
            $user,
            $withdrawalRequest,
            'withdrawal',
            'คำขอถอนเงินถูกส่งแล้ว',
            "คำขอถอนเงินจำนวน {$withdrawalRequest->formatted_amount} ของคุณกำลังรอการอนุมัติจากแอดมิน",
            [
                'request_id' => $withdrawalRequest->request_id,
                'amount' => $withdrawalRequest->amount,
                'net_amount' => $withdrawalRequest->net_amount,
            ],
            route('user.wallet.withdrawals'),
            'ดูคำขอถอนเงิน',
            'normal',
            false,
            '💸',
            'blue'
        );
    }

    /**
     * Notify about withdrawal approved
     */
    public function notifyWithdrawalApproved(User $user, $withdrawalRequest): Notification
    {
        return $this->createForModel(
            $user,
            $withdrawalRequest,
            'withdrawal',
            'คำขอถอนเงินได้รับการอนุมัติ',
            "คำขอถอนเงินจำนวน {$withdrawalRequest->formatted_net_amount} ของคุณได้รับการอนุมัติแล้ว กรุณารอการโอนเงิน",
            [
                'request_id' => $withdrawalRequest->request_id,
                'amount' => $withdrawalRequest->net_amount,
            ],
            route('user.wallet.withdrawals'),
            'ดูคำขอถอนเงิน',
            'high',
            true,
            '✅',
            'green'
        );
    }

    /**
     * Notify about withdrawal rejected
     */
    public function notifyWithdrawalRejected(User $user, $withdrawalRequest, string $reason): Notification
    {
        return $this->createForModel(
            $user,
            $withdrawalRequest,
            'withdrawal',
            'คำขอถอนเงินถูกปฏิเสธ',
            "คำขอถอนเงินจำนวน {$withdrawalRequest->formatted_amount} ของคุณถูกปฏิเสธ เหตุผล: $reason",
            [
                'request_id' => $withdrawalRequest->request_id,
                'reason' => $reason,
            ],
            route('user.wallet.withdrawals'),
            'ดูคำขอถอนเงิน',
            'high',
            true,
            '❌',
            'red'
        );
    }

    /**
     * Notify about withdrawal completed with transfer slip
     */
    public function notifyWithdrawalCompleted(User $user, $withdrawalRequest): Notification
    {
        return $this->createForModel(
            $user,
            $withdrawalRequest,
            'withdrawal',
            'การถอนเงินเสร็จสมบูรณ์',
            "การถอนเงินจำนวน {$withdrawalRequest->formatted_net_amount} เสร็จสมบูรณ์แล้ว กรุณาตรวจสอบบัญชีของคุณ",
            [
                'request_id' => $withdrawalRequest->request_id,
                'amount' => $withdrawalRequest->net_amount,
                'transfer_slip' => $withdrawalRequest->transfer_slip_url,
            ],
            route('user.wallet.withdrawals'),
            'ดูสลิปการโอน',
            'urgent',
            true,
            '🎉',
            'green'
        );
    }

    /**
     * Notify about transfer received
     */
    public function notifyTransferReceived(User $user, float $amount, User $fromUser, string $currency = 'THB'): Notification
    {
        return $this->create(
            $user,
            'transfer',
            'รับเงินโอน',
            "คุณได้รับเงินโอนจาก {$fromUser->name} จำนวน " . number_format($amount, 2) . " $currency",
            [
                'amount' => $amount,
                'currency' => $currency,
                'from_user_id' => $fromUser->id,
                'from_user_name' => $fromUser->name,
            ],
            route('user.wallet.transactions'),
            'ดูธุรกรรม',
            'normal',
            false,
            '📥',
            'blue'
        );
    }

    /**
     * Notify about commission earned
     */
    public function notifyCommissionEarned(User $user, float $amount, string $currency = 'THB'): Notification
    {
        return $this->create(
            $user,
            'commission',
            'ได้รับคอมมิชชั่น',
            "คุณได้รับคอมมิชชั่นจำนวน " . number_format($amount, 2) . " $currency",
            ['amount' => $amount, 'currency' => $currency],
            route('user.wallet.transactions'),
            'ดูธุรกรรม',
            'high',
            true,
            '💰',
            'green'
        );
    }

    /**
     * Notify admin about new withdrawal request
     */
    public function notifyAdminNewWithdrawal($withdrawalRequest): void
    {
        // Get all admins with withdrawal approval permission
        $admins = User::where('is_super_admin', true)
            ->orWhereJsonContains('permissions', 'approve_withdrawals')
            ->get();

        foreach ($admins as $admin) {
            $this->createForModel(
                $admin,
                $withdrawalRequest,
                'withdrawal',
                'คำขอถอนเงินใหม่',
                "มีคำขอถอนเงินใหม่จาก {$withdrawalRequest->user->name} จำนวน {$withdrawalRequest->formatted_amount}",
                [
                    'request_id' => $withdrawalRequest->request_id,
                    'user_name' => $withdrawalRequest->user->name,
                    'amount' => $withdrawalRequest->amount,
                ],
                route('admin.withdrawals.show', $withdrawalRequest->id),
                'ดูและอนุมัติ',
                'high',
                true,
                '🔔',
                'orange'
            );
        }
    }

    /**
     * Get default icon for notification type
     */
    protected function getDefaultIcon(string $type): string
    {
        return match($type) {
            'deposit' => '💵',
            'withdrawal' => '💸',
            'transfer' => '📤',
            'commission' => '💰',
            'wallet' => '👛',
            'system' => '⚙️',
            'alert' => '⚠️',
            'announcement' => '📢',
            default => '🔔',
        };
    }

    /**
     * Get default color for notification type
     */
    protected function getDefaultColor(string $type): string
    {
        return match($type) {
            'deposit' => 'green',
            'withdrawal' => 'blue',
            'transfer' => 'blue',
            'commission' => 'green',
            'wallet' => 'purple',
            'system' => 'gray',
            'alert' => 'red',
            'announcement' => 'blue',
            default => 'blue',
        };
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Notification $notification): void
    {
        $notification->markAsRead();
    }

    /**
     * Mark all user notifications as read
     */
    public function markAllAsRead(User $user): void
    {
        $user->notifications()->unread()->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Get user's unread notifications
     */
    public function getUnreadNotifications(User $user, int $limit = 10)
    {
        return $user->notifications()
            ->unread()
            ->notExpired()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get user's all notifications
     */
    public function getAllNotifications(User $user, int $perPage = 20)
    {
        return $user->notifications()
            ->active()
            ->notExpired()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Delete old notifications
     */
    public function deleteOldNotifications(int $days = 90): int
    {
        return Notification::where('created_at', '<', now()->subDays($days))
            ->where('is_important', false)
            ->delete();
    }

    /**
     * Delete expired notifications
     */
    public function deleteExpiredNotifications(): int
    {
        return Notification::where('expires_at', '<', now())
            ->delete();
    }

    /**
     * Broadcast notification to all users
     */
    public function broadcast(
        string $type,
        string $title,
        string $message,
        array $data = [],
        ?string $actionUrl = null,
        ?string $actionText = null,
        string $priority = 'normal',
        bool $isImportant = false,
        bool $showImmediately = false,
        ?string $icon = null,
        ?string $color = null,
        ?array $userIds = null
    ): int {
        try {
            $users = $userIds
                ? User::whereIn('id', $userIds)->get()
                : User::all();

            $count = 0;
            foreach ($users as $user) {
                Notification::create([
                    'user_id' => $user->id,
                    'type' => $type,
                    'title' => $title,
                    'message' => $message,
                    'data' => $data,
                    'action_url' => $actionUrl,
                    'action_text' => $actionText,
                    'priority' => $priority,
                    'is_important' => $isImportant,
                    'show_immediately' => $showImmediately,
                    'is_broadcast' => true,
                    'icon' => $icon ?? $this->getDefaultIcon($type),
                    'color' => $color ?? $this->getDefaultColor($type),
                ]);
                $count++;
            }

            return $count;
        } catch (Exception $e) {
            Log::error('Failed to broadcast notification: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get immediate notifications for user
     */
    public function getImmediateNotifications(User $user)
    {
        return $user->notifications()
            ->immediate()
            ->unread()
            ->whereNull('shown_at')
            ->notExpired()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Mark notification as shown
     */
    public function markAsShown(Notification $notification): void
    {
        $notification->markAsShown();
    }
}
