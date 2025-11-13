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
                true, // Show immediately
                '🔔',
                'orange'
            );
        }
    }

    /**
     * Notify admin about new commission pending approval
     */
    public function notifyAdminNewCommission($commission): void
    {
        // Get all admins with commission approval permission
        $admins = User::where('is_super_admin', true)
            ->orWhere('is_admin', true)
            ->orWhereJsonContains('permissions', 'approve_commissions')
            ->get();

        foreach ($admins as $admin) {
            $this->createForModel(
                $admin,
                $commission,
                'commission',
                'คอมมิชชันรออนุมัติ',
                "มีคอมมิชชันใหม่รออนุมัติจาก {$commission->user->name} จำนวน " . number_format($commission->amount, 2) . " บาท",
                [
                    'user_name' => $commission->user->name,
                    'amount' => $commission->amount,
                    'type' => $commission->type,
                ],
                route('admin.commissions.show', $commission->id),
                'ดูและอนุมัติ',
                'high',
                true,
                true, // Show immediately
                '💰',
                'green'
            );
        }
    }

    /**
     * Notify admin about new KYC verification request
     */
    public function notifyAdminNewKyc($kycVerification): void
    {
        // Get all admins with KYC approval permission
        $admins = User::where('is_super_admin', true)
            ->orWhere('is_admin', true)
            ->orWhereJsonContains('permissions', 'approve_kyc')
            ->get();

        foreach ($admins as $admin) {
            $this->createForModel(
                $admin,
                $kycVerification,
                'kyc',
                'ยืนยันตัวตนใหม่รออนุมัติ',
                "มีคำขอยืนยันตัวตนใหม่จาก {$kycVerification->user->name}",
                [
                    'user_name' => $kycVerification->user->name,
                    'type' => $kycVerification->verification_type ?? 'standard',
                ],
                route('admin.kyc.show', $kycVerification->id),
                'ดูและอนุมัติ',
                'high',
                true,
                true, // Show immediately
                '🆔',
                'blue'
            );
        }
    }

    /**
     * Notify admin about new support ticket
     */
    public function notifyAdminNewTicket($ticket): void
    {
        // Get all admins and support staff
        $admins = User::where('is_super_admin', true)
            ->orWhere('is_admin', true)
            ->orWhereJsonContains('permissions', 'manage_tickets')
            ->orWhere('role', 'moderator')
            ->get();

        foreach ($admins as $admin) {
            $this->createForModel(
                $admin,
                $ticket,
                'ticket',
                'ตั๋วซัพพอร์ตใหม่',
                "มีตั๋วซัพพอร์ตใหม่จาก {$ticket->user->name}: {$ticket->subject}",
                [
                    'user_name' => $ticket->user->name,
                    'subject' => $ticket->subject,
                    'category' => $ticket->category,
                    'priority' => $ticket->priority,
                ],
                route('admin.tickets.show', $ticket->id),
                'ดูและตอบ',
                $ticket->priority === 'critical' || $ticket->priority === 'high' ? 'urgent' : 'normal',
                $ticket->priority === 'critical' || $ticket->priority === 'high',
                $ticket->priority === 'critical' || $ticket->priority === 'high', // Show immediately for high/critical
                '🎫',
                $ticket->priority === 'critical' ? 'red' : ($ticket->priority === 'high' ? 'orange' : 'blue')
            );
        }
    }

    /**
     * Notify admin about unassigned high priority ticket
     */
    public function notifyAdminUnassignedTicket($ticket): void
    {
        // Get all admins
        $admins = User::where('is_super_admin', true)
            ->orWhere('is_admin', true)
            ->get();

        foreach ($admins as $admin) {
            $this->createForModel(
                $admin,
                $ticket,
                'ticket',
                'ตั๋วยังไม่มีผู้รับผิดชอบ',
                "ตั๋วลำดับความสำคัญสูง #{$ticket->ticket_number} ยังไม่มีผู้รับผิดชอบ",
                [
                    'ticket_number' => $ticket->ticket_number,
                    'subject' => $ticket->subject,
                    'priority' => $ticket->priority,
                ],
                route('admin.tickets.show', $ticket->id),
                'มอบหมายงาน',
                'urgent',
                true,
                true,
                '⚠️',
                'red'
            );
        }
    }

    /**
     * Notify user about commission approved
     */
    public function notifyCommissionApproved(User $user, $commission): Notification
    {
        return $this->createForModel(
            $user,
            $commission,
            'commission',
            'คอมมิชชันได้รับการอนุมัติ',
            "คอมมิชชันของคุณจำนวน " . number_format($commission->amount, 2) . " บาท ได้รับการอนุมัติแล้ว",
            [
                'amount' => $commission->amount,
                'type' => $commission->type,
            ],
            route('user.commissions.index'),
            'ดูคอมมิชชัน',
            'high',
            true,
            true,
            '✅',
            'green'
        );
    }

    /**
     * Notify user about commission rejected
     */
    public function notifyCommissionRejected(User $user, $commission, string $reason): Notification
    {
        return $this->createForModel(
            $user,
            $commission,
            'commission',
            'คอมมิชชันถูกปฏิเสธ',
            "คอมมิชชันของคุณถูกปฏิเสธ เหตุผล: $reason",
            [
                'amount' => $commission->amount,
                'reason' => $reason,
            ],
            route('user.commissions.index'),
            'ดูรายละเอียด',
            'high',
            true,
            true,
            '❌',
            'red'
        );
    }

    /**
     * Notify user about KYC approved
     */
    public function notifyKycApproved(User $user, $kycVerification): Notification
    {
        return $this->createForModel(
            $user,
            $kycVerification,
            'kyc',
            'การยืนยันตัวตนสำเร็จ',
            "การยืนยันตัวตนของคุณได้รับการอนุมัติแล้ว บัญชีของคุณได้รับการยืนยันแล้ว",
            [
                'verification_type' => $kycVerification->verification_type ?? 'standard',
            ],
            route('user.profile.index'),
            'ดูโปรไฟล์',
            'urgent',
            true,
            true,
            '✅',
            'green'
        );
    }

    /**
     * Notify user about KYC rejected
     */
    public function notifyKycRejected(User $user, $kycVerification, string $reason): Notification
    {
        return $this->createForModel(
            $user,
            $kycVerification,
            'kyc',
            'การยืนยันตัวตนถูกปฏิเสธ',
            "การยืนยันตัวตนของคุณถูกปฏิเสธ เหตุผล: $reason กรุณาอัพโหลดเอกสารใหม่",
            [
                'reason' => $reason,
            ],
            route('user.kyc.create'),
            'ยืนยันตัวตนใหม่',
            'urgent',
            true,
            true,
            '❌',
            'red'
        );
    }

    /**
     * Notify user about ticket reply
     */
    public function notifyTicketReply(User $user, $ticket, $reply): Notification
    {
        $isFromAdmin = $reply->user && ($reply->user->is_admin || $reply->user->is_super_admin);

        return $this->createForModel(
            $user,
            $ticket,
            'ticket',
            $isFromAdmin ? 'แอดมินตอบกลับตั๋วซัพพอร์ตของคุณ' : 'มีการตอบกลับในตั๋วซัพพอร์ต',
            "มีการตอบกลับใหม่ในตั๋ว: {$ticket->subject}",
            [
                'ticket_number' => $ticket->ticket_number,
                'subject' => $ticket->subject,
                'reply_preview' => substr(strip_tags($reply->message), 0, 100),
            ],
            route('user.tickets.show', $ticket->id),
            'ดูการตอบกลับ',
            'high',
            true,
            $isFromAdmin, // Show immediately if from admin
            '💬',
            'blue'
        );
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
        ?array $userIds = null,
        $scheduledAt = null
    ): int {
        try {
            $users = $userIds
                ? User::whereIn('id', $userIds)->get()
                : User::all();

            $isScheduled = $scheduledAt && \Carbon\Carbon::parse($scheduledAt)->isFuture();

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
                    'scheduled_at' => $scheduledAt,
                    'is_scheduled' => $isScheduled,
                    'is_sent' => !$isScheduled,
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
