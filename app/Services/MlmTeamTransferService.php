<?php

namespace App\Services;

use App\Models\MlmMember;
use App\Models\MlmTeamTransferRequest;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * MlmTeamTransferService
 *
 * จัดการ business logic สำหรับการย้ายทีม MLM
 *
 * Workflow:
 * 1. สมาชิกขอย้าย → สร้าง Transfer Request (status: pending)
 * 2. แม่ทีมเก่าอนุมัติ → status: approved
 * 3. สมาชิกชำระเงิน → status: paid
 * 4. Admin ดำเนินการย้าย → status: completed
 *
 * @package App\Services
 */
class MlmTeamTransferService
{
    /**
     * @var NotificationService
     */
    protected NotificationService $notificationService;

    /**
     * @var LineService
     */
    protected LineService $lineService;

    /**
     * Constructor
     *
     * @param NotificationService $notificationService
     * @param LineService $lineService
     */
    public function __construct(
        NotificationService $notificationService,
        LineService $lineService
    ) {
        $this->notificationService = $notificationService;
        $this->lineService = $lineService;
    }

    /**
     * สร้างคำขอย้ายทีมใหม่
     *
     * @param MlmMember $member สมาชิกที่ขอย้าย
     * @param MlmMember $newSponsor แม่ทีมใหม่
     * @param array $data ข้อมูลเพิ่มเติม
     * @return MlmTeamTransferRequest
     *
     * @throws \Exception
     */
    public function createTransferRequest(
        MlmMember $member,
        MlmMember $newSponsor,
        array $data = []
    ): MlmTeamTransferRequest {
        // ตรวจสอบข้อมูล
        $this->validateTransferRequest($member, $newSponsor);

        return DB::transaction(function () use ($member, $newSponsor, $data) {
            // สร้าง Transfer Request
            $request = MlmTeamTransferRequest::create([
                'mlm_member_id' => $member->id,
                'user_id' => $member->user_id,
                'old_unilevel_sponsor_id' => $member->unilevel_sponsor_id,
                'new_unilevel_sponsor_id' => $newSponsor->id,
                'old_binary_parent_id' => $member->binary_parent_id,
                'old_binary_position' => $member->binary_position,
                'new_binary_parent_id' => $data['new_binary_parent_id'] ?? null,
                'new_binary_position' => $data['new_binary_position'] ?? null,
                'status' => MlmTeamTransferRequest::STATUS_PENDING,
                'transfer_fee' => $data['transfer_fee'] ?? 100.00,
                'reason' => $data['reason'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            // ส่งการแจ้งเตือนไปยังแม่ทีมเก่า
            $this->sendApprovalNotification($request);

            Log::info('Team transfer request created', [
                'request_id' => $request->id,
                'member_id' => $member->id,
                'old_sponsor' => $member->unilevel_sponsor_id,
                'new_sponsor' => $newSponsor->id,
            ]);

            return $request;
        });
    }

    /**
     * ตรวจสอบความถูกต้องของคำขอย้ายทีม
     *
     * @param MlmMember $member
     * @param MlmMember $newSponsor
     * @return void
     *
     * @throws \Exception
     */
    protected function validateTransferRequest(
        MlmMember $member,
        MlmMember $newSponsor
    ): void {
        // ตรวจสอบว่าสมาชิกมี status = active
        if ($member->status !== 'active') {
            throw new \Exception('สมาชิกต้องมีสถานะ Active เท่านั้น');
        }

        // ตรวจสอบว่าแม่ทีมใหม่ไม่ใช่ตัวเอง
        if ($member->id === $newSponsor->id) {
            throw new \Exception('ไม่สามารถย้ายไปหาตัวเองได้');
        }

        // ตรวจสอบว่าแม่ทีมใหม่ไม่ใช่ลูกทีมของตัวเอง
        if ($this->isDownline($member, $newSponsor)) {
            throw new \Exception('ไม่สามารถย้ายไปหาลูกทีมของตัวเองได้');
        }

        // ตรวจสอบว่าไม่มีคำขอย้ายที่รออนุมัติอยู่
        $pendingRequest = MlmTeamTransferRequest::where('mlm_member_id', $member->id)
            ->whereIn('status', [
                MlmTeamTransferRequest::STATUS_PENDING,
                MlmTeamTransferRequest::STATUS_APPROVED,
                MlmTeamTransferRequest::STATUS_PAID,
                MlmTeamTransferRequest::STATUS_PROCESSING,
            ])
            ->first();

        if ($pendingRequest) {
            throw new \Exception('มีคำขอย้ายทีมที่รออนุมัติอยู่แล้ว');
        }
    }

    /**
     * ตรวจสอบว่า $potential_downline เป็นลูกทีมของ $member หรือไม่
     *
     * @param MlmMember $member
     * @param MlmMember $potentialDownline
     * @return bool
     */
    protected function isDownline(MlmMember $member, MlmMember $potentialDownline): bool
    {
        // ตรวจสอบจาก unilevel_path
        if ($potentialDownline->unilevel_path) {
            $path = explode('/', $potentialDownline->unilevel_path);
            return in_array($member->id, $path);
        }

        return false;
    }

    /**
     * อนุมัติคำขอย้ายทีม (โดยแม่ทีมเก่า)
     *
     * @param MlmTeamTransferRequest $request
     * @param User $approver แม่ทีมเก่า (user)
     * @return MlmTeamTransferRequest
     *
     * @throws \Exception
     */
    public function approveTransfer(
        MlmTeamTransferRequest $request,
        User $approver
    ): MlmTeamTransferRequest {
        // ตรวจสอบว่าเป็นแม่ทีมเก่าหรือไม่
        if (!$request->isOldSponsor($approver->id)) {
            throw new \Exception('คุณไม่ใช่แม่ทีมเก่าของสมาชิกนี้');
        }

        // ตรวจสอบว่าสามารถอนุมัติได้หรือไม่
        if (!$request->canBeApproved()) {
            throw new \Exception('ไม่สามารถอนุมัติคำขอนี้ได้');
        }

        return DB::transaction(function () use ($request, $approver) {
            // อัพเดทสถานะ
            $request->update([
                'status' => MlmTeamTransferRequest::STATUS_APPROVED,
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);

            // ส่งการแจ้งเตือนไปยังสมาชิกที่ขอย้าย
            $this->sendApprovedNotification($request);

            Log::info('Team transfer request approved', [
                'request_id' => $request->id,
                'approver_id' => $approver->id,
            ]);

            return $request->fresh();
        });
    }

    /**
     * ปฏิเสธคำขอย้ายทีม (โดยแม่ทีมเก่า)
     *
     * @param MlmTeamTransferRequest $request
     * @param User $rejecter แม่ทีมเก่า (user)
     * @param string|null $reason เหตุผล
     * @return MlmTeamTransferRequest
     *
     * @throws \Exception
     */
    public function rejectTransfer(
        MlmTeamTransferRequest $request,
        User $rejecter,
        ?string $reason = null
    ): MlmTeamTransferRequest {
        // ตรวจสอบว่าเป็นแม่ทีมเก่าหรือไม่
        if (!$request->isOldSponsor($rejecter->id)) {
            throw new \Exception('คุณไม่ใช่แม่ทีมเก่าของสมาชิกนี้');
        }

        // ตรวจสอบว่าสามารถปฏิเสธได้หรือไม่
        if (!$request->canBeRejected()) {
            throw new \Exception('ไม่สามารถปฏิเสธคำขอนี้ได้');
        }

        return DB::transaction(function () use ($request, $rejecter, $reason) {
            // อัพเดทสถานะ
            $request->update([
                'status' => MlmTeamTransferRequest::STATUS_REJECTED,
                'rejected_by' => $rejecter->id,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);

            // ส่งการแจ้งเตือนไปยังสมาชิกที่ขอย้าย
            $this->sendRejectedNotification($request);

            Log::info('Team transfer request rejected', [
                'request_id' => $request->id,
                'rejecter_id' => $rejecter->id,
                'reason' => $reason,
            ]);

            return $request->fresh();
        });
    }

    /**
     * ชำระค่าธรรมเนียมการย้ายทีม
     *
     * @param MlmTeamTransferRequest $request
     * @param User $payer ผู้ชำระเงิน
     * @return MlmTeamTransferRequest
     *
     * @throws \Exception
     */
    public function payTransferFee(
        MlmTeamTransferRequest $request,
        User $payer
    ): MlmTeamTransferRequest {
        // ตรวจสอบว่าเป็นเจ้าของคำขอหรือไม่
        if ($request->user_id !== $payer->id) {
            throw new \Exception('คุณไม่ใช่เจ้าของคำขอนี้');
        }

        // ตรวจสอบว่าสามารถชำระเงินได้หรือไม่
        if (!$request->canBePaid()) {
            throw new \Exception('ไม่สามารถชำระเงินคำขอนี้ได้');
        }

        // ตรวจสอบยอดเงินใน Wallet
        $walletService = app(WalletService::class);
        $balance = $walletService->getBalance($payer->id);

        if ($balance < $request->transfer_fee) {
            throw new \Exception('ยอดเงินใน Wallet ไม่เพียงพอ');
        }

        return DB::transaction(function () use ($request, $payer, $walletService) {
            // หักเงินจาก Wallet
            $transaction = $walletService->deduct(
                $payer->id,
                $request->transfer_fee,
                'team_transfer_fee',
                "ค่าธรรมเนียมการย้ายทีม (Request #{$request->id})",
                [
                    'transfer_request_id' => $request->id,
                ]
            );

            // อัพเดทสถานะ
            $request->update([
                'status' => MlmTeamTransferRequest::STATUS_PAID,
                'wallet_transaction_id' => $transaction->id,
                'paid_at' => now(),
            ]);

            // ส่งการแจ้งเตือนไปยัง Admin
            $this->sendPaidNotificationToAdmin($request);

            Log::info('Team transfer fee paid', [
                'request_id' => $request->id,
                'payer_id' => $payer->id,
                'amount' => $request->transfer_fee,
                'transaction_id' => $transaction->id,
            ]);

            return $request->fresh();
        });
    }

    /**
     * ดำเนินการย้ายทีม (โดย Admin)
     *
     * @param MlmTeamTransferRequest $request
     * @param User $admin Admin ที่ดำเนินการ
     * @param string|null $notes หมายเหตุ
     * @return MlmTeamTransferRequest
     *
     * @throws \Exception
     */
    public function processTransfer(
        MlmTeamTransferRequest $request,
        User $admin,
        ?string $notes = null
    ): MlmTeamTransferRequest {
        // ตรวจสอบว่าสามารถดำเนินการได้หรือไม่
        if (!$request->canBeProcessed()) {
            throw new \Exception('ไม่สามารถดำเนินการคำขอนี้ได้');
        }

        // ตรวจสอบว่าตำแหน่งใหม่ว่างหรือไม่ (ถ้ามีการระบุ)
        if ($request->new_binary_parent_id && $request->new_binary_position) {
            $this->validateBinaryPosition(
                $request->newBinaryParent,
                $request->new_binary_position
            );
        }

        return DB::transaction(function () use ($request, $admin, $notes) {
            // อัพเดทสถานะเป็น processing
            $request->update([
                'status' => MlmTeamTransferRequest::STATUS_PROCESSING,
            ]);

            // ดำเนินการย้ายทีม
            $member = $request->member;

            // บันทึกข้อมูลเก่า
            $oldData = [
                'unilevel_sponsor_id' => $member->unilevel_sponsor_id,
                'unilevel_level' => $member->unilevel_level,
                'unilevel_path' => $member->unilevel_path,
                'binary_parent_id' => $member->binary_parent_id,
                'binary_position' => $member->binary_position,
            ];

            // อัพเดทข้อมูลใหม่
            $member->update([
                'unilevel_sponsor_id' => $request->new_unilevel_sponsor_id,
                'unilevel_level' => $request->newSponsor->unilevel_level + 1,
                'unilevel_path' => $request->newSponsor->unilevel_path . '/' . $request->newSponsor->id,
                'binary_parent_id' => $request->new_binary_parent_id,
                'binary_position' => $request->new_binary_position,
            ]);

            // อัพเดท binary path (ถ้ามี)
            if ($request->new_binary_parent_id) {
                $this->updateBinaryPath($member);
            }

            // อัพเดทสถานะคำขอ
            $request->update([
                'status' => MlmTeamTransferRequest::STATUS_COMPLETED,
                'processed_by' => $admin->id,
                'processed_at' => now(),
                'admin_notes' => $notes,
            ]);

            // ส่งการแจ้งเตือนไปยังสมาชิก, แม่ทีมเก่า, แม่ทีมใหม่
            $this->sendCompletedNotifications($request);

            Log::info('Team transfer completed', [
                'request_id' => $request->id,
                'member_id' => $member->id,
                'admin_id' => $admin->id,
                'old_data' => $oldData,
                'new_data' => [
                    'unilevel_sponsor_id' => $member->unilevel_sponsor_id,
                    'binary_parent_id' => $member->binary_parent_id,
                    'binary_position' => $member->binary_position,
                ],
            ]);

            return $request->fresh();
        });
    }

    /**
     * ตรวจสอบว่าตำแหน่ง binary ว่างหรือไม่
     *
     * @param MlmMember $parent
     * @param string $position
     * @return void
     *
     * @throws \Exception
     */
    protected function validateBinaryPosition(MlmMember $parent, string $position): void
    {
        $existingChild = MlmMember::where('binary_parent_id', $parent->id)
            ->where('binary_position', $position)
            ->first();

        if ($existingChild) {
            throw new \Exception("ตำแหน่ง {$position} ของ binary parent นี้ไม่ว่าง");
        }
    }

    /**
     * อัพเดท binary path หลังจากย้ายทีม
     *
     * @param MlmMember $member
     * @return void
     */
    protected function updateBinaryPath(MlmMember $member): void
    {
        // สร้าง binary path ใหม่
        $path = [];
        $current = $member;

        while ($current->binary_parent_id) {
            $path[] = $current->binary_parent_id;
            $current = $current->binaryParent;
        }

        $member->update([
            'binary_path' => implode('/', array_reverse($path)),
        ]);
    }

    /**
     * ยกเลิกคำขอย้ายทีม
     *
     * @param MlmTeamTransferRequest $request
     * @param User $user ผู้ยกเลิก
     * @return MlmTeamTransferRequest
     *
     * @throws \Exception
     */
    public function cancelTransfer(
        MlmTeamTransferRequest $request,
        User $user
    ): MlmTeamTransferRequest {
        // ตรวจสอบว่าเป็นเจ้าของคำขอหรือไม่
        if ($request->user_id !== $user->id) {
            throw new \Exception('คุณไม่ใช่เจ้าของคำขอนี้');
        }

        // ตรวจสอบว่าสามารถยกเลิกได้หรือไม่
        if (!$request->canBeCancelled()) {
            throw new \Exception('ไม่สามารถยกเลิกคำขอนี้ได้');
        }

        return DB::transaction(function () use ($request) {
            // ถ้าชำระเงินแล้ว → คืนเงิน
            if ($request->paid_at && $request->wallet_transaction_id) {
                $walletService = app(WalletService::class);
                $walletService->add(
                    $request->user_id,
                    $request->transfer_fee,
                    'team_transfer_refund',
                    "คืนค่าธรรมเนียมการย้ายทีม (Request #{$request->id})",
                    [
                        'transfer_request_id' => $request->id,
                        'original_transaction_id' => $request->wallet_transaction_id,
                    ]
                );
            }

            // อัพเดทสถานะ
            $request->update([
                'status' => MlmTeamTransferRequest::STATUS_CANCELLED,
            ]);

            Log::info('Team transfer request cancelled', [
                'request_id' => $request->id,
                'user_id' => $request->user_id,
            ]);

            return $request->fresh();
        });
    }

    /**
     * ส่งการแจ้งเตือนขออนุมัติไปยังแม่ทีมเก่า
     *
     * @param MlmTeamTransferRequest $request
     * @return void
     */
    protected function sendApprovalNotification(MlmTeamTransferRequest $request): void
    {
        try {
            $oldSponsor = $request->oldSponsor->user;

            // ส่ง Bell notification (in-app)
            $this->notificationService->notifyTeamTransferRequest($oldSponsor, [
                'request_id' => $request->id,
                'member_name' => $request->user->name,
                'member_code' => $request->member->member_code,
                'new_sponsor_name' => $request->newSponsor->user->name ?? 'N/A',
                'action_url' => route('user.team-transfer.approvals'),
                'notifiable_type' => get_class($request),
                'notifiable_id' => $request->id,
            ]);

            // ส่ง LINE direct message (ถ้ามี LINE user ID)
            $lineMessage = sprintf(
                "🔔 มีคำขอย้ายทีมใหม่\n\n" .
                "สมาชิก: %s (รหัส: %s)\n" .
                "ขอย้ายไปยังแม่ทีมใหม่: %s\n\n" .
                "กรุณาพิจารณาอนุมัติคำขอนี้",
                $request->user->name,
                $request->member->member_code,
                $request->newSponsor->user->name ?? 'N/A'
            );
            $this->sendLineMessage($oldSponsor, $lineMessage);

        } catch (\Exception $e) {
            Log::error('Failed to send approval notification', [
                'request_id' => $request->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * ส่งการแจ้งเตือนว่าคำขอได้รับการอนุมัติแล้ว
     *
     * @param MlmTeamTransferRequest $request
     * @return void
     */
    protected function sendApprovedNotification(MlmTeamTransferRequest $request): void
    {
        try {
            $member = $request->user;

            // ส่ง Bell notification (in-app)
            $this->notificationService->notifyTeamTransferApproved($member, [
                'request_id' => $request->id,
                'old_sponsor_name' => $request->oldSponsor->user->name ?? 'N/A',
                'new_sponsor_name' => $request->newSponsor->user->name ?? 'N/A',
                'transfer_fee' => $request->transfer_fee,
                'action_url' => route('user.team-transfer.show', $request->id),
                'notifiable_type' => get_class($request),
                'notifiable_id' => $request->id,
            ]);

            // ส่ง LINE direct message
            $lineMessage = sprintf(
                "✅ คำขอย้ายทีมได้รับการอนุมัติ\n\n" .
                "แม่ทีมเก่า: %s\n" .
                "แม่ทีมใหม่: %s\n" .
                "ค่าธรรมเนียม: %s บาท\n\n" .
                "กรุณาชำระค่าธรรมเนียมเพื่อดำเนินการต่อ",
                $request->oldSponsor->user->name ?? 'N/A',
                $request->newSponsor->user->name ?? 'N/A',
                number_format($request->transfer_fee, 2)
            );
            $this->sendLineMessage($member, $lineMessage);
        } catch (\Exception $e) {
            Log::error('Failed to send approved notification', [
                'request_id' => $request->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * ส่งการแจ้งเตือนว่าคำขอถูกปฏิเสธ
     *
     * @param MlmTeamTransferRequest $request
     * @return void
     */
    protected function sendRejectedNotification(MlmTeamTransferRequest $request): void
    {
        try {
            $member = $request->user;

            // ส่ง Bell notification (in-app)
            $this->notificationService->notifyTeamTransferRejected($member, [
                'request_id' => $request->id,
                'rejection_reason' => $request->rejection_reason ?? 'ไม่ระบุเหตุผล',
                'old_sponsor_name' => $request->oldSponsor->user->name ?? 'N/A',
                'action_url' => route('user.team-transfer.show', $request->id),
                'notifiable_type' => get_class($request),
                'notifiable_id' => $request->id,
            ]);

            // ส่ง LINE direct message
            $lineMessage = sprintf(
                "❌ คำขอย้ายทีมถูกปฏิเสธ\n\n" .
                "แม่ทีมเก่า: %s\n" .
                "เหตุผล: %s\n\n" .
                "หากต้องการข้อมูลเพิ่มเติม กรุณาติดต่อแม่ทีมของคุณ",
                $request->oldSponsor->user->name ?? 'N/A',
                $request->rejection_reason ?? 'ไม่ระบุเหตุผล'
            );
            $this->sendLineMessage($member, $lineMessage);
        } catch (\Exception $e) {
            Log::error('Failed to send rejected notification', [
                'request_id' => $request->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * ส่งการแจ้งเตือนไปยัง Admin ว่ามีการชำระเงินแล้ว
     *
     * @param MlmTeamTransferRequest $request
     * @return void
     */
    protected function sendPaidNotificationToAdmin(MlmTeamTransferRequest $request): void
    {
        try {
            // ดึงรายการ Admin users
            $admins = User::role('admin')->get();

            if ($admins->isEmpty()) {
                return;
            }

            // ส่ง Bell notification (in-app) ให้ทุก Admin
            $this->notificationService->notifyTeamTransferPaid($admins->all(), [
                'request_id' => $request->id,
                'member_name' => $request->user->name,
                'member_code' => $request->member->member_code,
                'transfer_fee' => $request->transfer_fee,
                'action_url' => route('admin.team-transfer.edit', $request->id),
                'notifiable_type' => get_class($request),
                'notifiable_id' => $request->id,
            ]);

            // ส่ง LINE direct message to admin
            $lineMessage = sprintf(
                "💰 การย้ายทีม - ชำระเงินแล้ว\n\n" .
                "สมาชิก: %s (รหัส: %s)\n" .
                "ค่าธรรมเนียม: %s บาท\n" .
                "จาก: %s\n" .
                "ไปยัง: %s\n\n" .
                "กรุณาดำเนินการตรวจสอบและอนุมัติ",
                $request->user->name,
                $request->member->member_code,
                number_format($request->transfer_fee, 2),
                $request->oldSponsor->user->name ?? 'N/A',
                $request->newSponsor->user->name ?? 'N/A'
            );
            $this->sendLineMessageToMultipleUsers($admins->all(), $lineMessage);
        } catch (\Exception $e) {
            Log::error('Failed to send paid notification to admin', [
                'request_id' => $request->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * ส่งการแจ้งเตือนว่าการย้ายทีมเสร็จสมบูรณ์
     *
     * @param MlmTeamTransferRequest $request
     * @return void
     */
    protected function sendCompletedNotifications(MlmTeamTransferRequest $request): void
    {
        try {
            $member = $request->user;
            $oldSponsor = $request->oldSponsor->user;
            $newSponsor = $request->newSponsor->user;

            $requestData = [
                'request_id' => $request->id,
                'member_name' => $member->name,
                'member_code' => $request->member->member_code,
                'old_sponsor_name' => $oldSponsor->name,
                'new_sponsor_name' => $newSponsor->name,
                'action_url' => route('user.team-transfer.show', $request->id),
                'notifiable_type' => get_class($request),
                'notifiable_id' => $request->id,
            ];

            // แจ้งเตือนสมาชิกที่ขอย้าย
            $this->notificationService->notifyTeamTransferCompletedToMember($member, $requestData);

            // แจ้งเตือนแม่ทีมเก่า
            $this->notificationService->notifyTeamMemberLeft($oldSponsor, $requestData);

            // แจ้งเตือนแม่ทีมใหม่
            $this->notificationService->notifyNewTeamMember($newSponsor, $requestData);

            // ส่ง LINE direct messages to all parties

            // 1. ข้อความสำหรับสมาชิก
            $memberLineMessage = sprintf(
                "🎉 การย้ายทีมเสร็จสมบูรณ์\n\n" .
                "คุณได้ย้ายจากทีมของ %s\n" .
                "ไปยังทีมของ %s เรียบร้อยแล้ว\n\n" .
                "ขอบคุณที่ใช้บริการ!",
                $oldSponsor->name,
                $newSponsor->name
            );
            $this->sendLineMessage($member, $memberLineMessage);

            // 2. ข้อความสำหรับแม่ทีมเก่า
            $oldSponsorLineMessage = sprintf(
                "👋 สมาชิกย้ายออกจากทีม\n\n" .
                "สมาชิก: %s (รหัส: %s)\n" .
                "ได้ย้ายไปยังทีมของ %s แล้ว\n\n" .
                "ขอบคุณสำหรับการดูแล",
                $member->name,
                $request->member->member_code,
                $newSponsor->name
            );
            $this->sendLineMessage($oldSponsor, $oldSponsorLineMessage);

            // 3. ข้อความสำหรับแม่ทีมใหม่
            $newSponsorLineMessage = sprintf(
                "🎊 สมาชิกใหม่เข้าร่วมทีม\n\n" .
                "สมาชิก: %s (รหัส: %s)\n" .
                "ได้ย้ายจากทีมของ %s\n" .
                "มาเป็นสมาชิกในทีมของคุณแล้ว\n\n" .
                "ยินดีต้อนรับสู่ทีม!",
                $member->name,
                $request->member->member_code,
                $oldSponsor->name
            );
            $this->sendLineMessage($newSponsor, $newSponsorLineMessage);
        } catch (\Exception $e) {
            Log::error('Failed to send completed notifications', [
                'request_id' => $request->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * ส่ง LINE message ไปยัง user (ถ้ามี LINE user ID)
     *
     * @param User $user ผู้ใช้ที่จะส่ง
     * @param string $message ข้อความที่จะส่ง
     * @return bool สำเร็จหรือไม่
     */
    protected function sendLineMessage(User $user, string $message): bool
    {
        // ตรวจสอบว่ามี LINE user ID หรือไม่
        if (empty($user->line_user_id)) {
            Log::debug('User does not have LINE user ID', [
                'user_id' => $user->id,
                'user_name' => $user->name,
            ]);
            return false;
        }

        try {
            // ส่ง LINE push message
            $sent = $this->lineService->sendPushMessage(
                $user->line_user_id,
                $message
            );

            if ($sent) {
                Log::info('LINE message sent successfully', [
                    'user_id' => $user->id,
                    'line_user_id' => $user->line_user_id,
                ]);
            } else {
                Log::warning('LINE message failed to send', [
                    'user_id' => $user->id,
                    'line_user_id' => $user->line_user_id,
                ]);
            }

            return $sent;

        } catch (\Exception $e) {
            Log::error('LINE message exception', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * ส่ง LINE message ไปยังหลาย users
     *
     * @param array $users รายการผู้ใช้
     * @param string $message ข้อความที่จะส่ง
     * @return int จำนวนที่ส่งสำเร็จ
     */
    protected function sendLineMessageToMultipleUsers(array $users, string $message): int
    {
        $successCount = 0;

        foreach ($users as $user) {
            if ($this->sendLineMessage($user, $message)) {
                $successCount++;
            }
        }

        return $successCount;
    }
}
