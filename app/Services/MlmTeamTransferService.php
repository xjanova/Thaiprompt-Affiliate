<?php

namespace App\Services;

use App\Models\MlmMember;
use App\Models\MlmTeamTransferRequest;
use App\Models\User;
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
 */
class MlmTeamTransferService
{
    protected NotificationService $notificationService;

    protected LineService $lineService;

    /**
     * Constructor
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
     * @param  MlmMember  $member  สมาชิกที่ขอย้าย
     * @param  MlmMember  $newSponsor  แม่ทีมใหม่
     * @param  array  $data  ข้อมูลเพิ่มเติม
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
     * @param  User  $approver  แม่ทีมเก่า (user)
     *
     * @throws \Exception
     */
    public function approveTransfer(
        MlmTeamTransferRequest $request,
        User $approver
    ): MlmTeamTransferRequest {
        // ตรวจสอบว่าเป็นแม่ทีมเก่าหรือไม่
        if (! $request->isOldSponsor($approver->id)) {
            throw new \Exception('คุณไม่ใช่แม่ทีมเก่าของสมาชิกนี้');
        }

        // ตรวจสอบว่าสามารถอนุมัติได้หรือไม่
        if (! $request->canBeApproved()) {
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
     * @param  User  $rejecter  แม่ทีมเก่า (user)
     * @param  string|null  $reason  เหตุผล
     *
     * @throws \Exception
     */
    public function rejectTransfer(
        MlmTeamTransferRequest $request,
        User $rejecter,
        ?string $reason = null
    ): MlmTeamTransferRequest {
        // ตรวจสอบว่าเป็นแม่ทีมเก่าหรือไม่
        if (! $request->isOldSponsor($rejecter->id)) {
            throw new \Exception('คุณไม่ใช่แม่ทีมเก่าของสมาชิกนี้');
        }

        // ตรวจสอบว่าสามารถปฏิเสธได้หรือไม่
        if (! $request->canBeRejected()) {
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
     * @param  User  $payer  ผู้ชำระเงิน
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
        if (! $request->canBePaid()) {
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
     * @param  User  $admin  Admin ที่ดำเนินการ
     * @param  string|null  $notes  หมายเหตุ
     *
     * @throws \Exception
     */
    public function processTransfer(
        MlmTeamTransferRequest $request,
        User $admin,
        ?string $notes = null
    ): MlmTeamTransferRequest {
        // ตรวจสอบว่าสามารถดำเนินการได้หรือไม่
        if (! $request->canBeProcessed()) {
            throw new \Exception('ไม่สามารถดำเนินการคำขอนี้ได้');
        }

        // 🐛 Fix 2026-07-24: เช็ค cycle ก่อนย้าย (เดิม path workflow ไม่เช็คเลย
        // ต่างจาก adminDirectTransfer) — ย้ายไปใต้ลูกทีมตัวเอง = ผังเป็นวงจร
        if ($request->new_unilevel_sponsor_id && $request->newSponsor
            && $this->isDownline($request->member, $request->newSponsor)) {
            throw new \Exception('ไม่สามารถย้ายไปหาลูกทีมของตัวเองได้ (Unilevel)');
        }

        if ($request->new_binary_parent_id && $request->newBinaryParent
            && $this->isBinaryDownline($request->member, $request->newBinaryParent)) {
            throw new \Exception('ไม่สามารถย้ายไปหาลูกทีม Binary ของตัวเองได้');
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
                'unilevel_path' => $request->newSponsor->unilevel_path.'/'.$request->newSponsor->id,
                'binary_parent_id' => $request->new_binary_parent_id,
                'binary_position' => $request->new_binary_position,
            ]);

            // อัพเดท binary path (ถ้ามี)
            if ($request->new_binary_parent_id) {
                $this->updateBinaryPath($member);
            }

            // 🐛 Fix 2026-07-24: อัพเดท path/level ของลูกทีมทั้ง subtree
            // (เดิม path workflow แก้เฉพาะตัว member ที่ย้าย → path ของ downline พังทั้งสาย)
            $this->updateUnilevelDescendantPaths($member);
            if ($request->new_binary_parent_id) {
                $this->updateBinaryDescendantPaths($member);
            }

            // โยกตัวนับทีม (delta ตามขนาด subtree) + rebuild genealogy
            $this->afterTransferAdjustments($member, $oldData);

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
     */
    protected function updateBinaryPath(MlmMember $member): void
    {
        // สร้าง binary path ใหม่
        // 🐛 Fix 2026-07-24: เพิ่ม visited + depth cap กัน loop ไม่รู้จบถ้าผังมี cycle
        // และกัน null parent (binary_parent_id ชี้ member ที่ถูกลบ)
        $path = [];
        $visited = [];
        $current = $member;

        while ($current && $current->binary_parent_id && count($path) < 100) {
            if (isset($visited[$current->binary_parent_id])) {
                break; // เจอ cycle — หยุดทันที
            }
            $visited[$current->binary_parent_id] = true;
            $path[] = $current->binary_parent_id;
            $current = $current->binaryParent;
        }

        $member->update([
            'binary_path' => implode('/', array_reverse($path)),
        ]);
    }

    /**
     * ปรับตัวนับทีม + rebuild genealogy หลังย้ายทีม (ใช้ร่วมทั้ง workflow และ admin direct)
     *
     * 🐛 Fix 2026-07-24: เดิมย้ายทีมแล้วไม่โยก total_team_members / left_leg_members /
     * right_leg_members ออกจากสายเก่าเข้าสายใหม่ และไม่ rebuild mlm_genealogy
     * → ตัวนับค้างผิดถาวร + closure table ชี้สายเก่า
     *
     * หมายเหตุเรื่อง leg PV: PV สะสมในอดีต (left/right_leg_pv) "คงไว้ที่สายเก่า"
     * โดยเจตนา — เพราะยอดคงเหลือคือ PV ที่ยังไม่จับคู่ซึ่งเกิดขณะอยู่สายเก่า
     * การถอนย้อนหลังทำไม่ได้แม่นยำ (บางส่วนถูกจับคู่จ่ายไปแล้ว) และเสี่ยงติดลบ
     * PV ใหม่หลังย้ายจะไหลตามสายใหม่ถูกต้องผ่าน binaryParent chain
     *
     * @param  MlmMember  $member  สมาชิกที่ย้ายแล้ว (ข้อมูลใหม่ใน DB แล้ว)
     * @param  array  $oldData  ข้อมูลก่อนย้าย (binary_parent_id, binary_position, ...)
     */
    protected function afterTransferAdjustments(MlmMember $member, array $oldData): void
    {
        $binaryMoved = ($oldData['binary_parent_id'] ?? null) != $member->binary_parent_id
            || ($oldData['binary_position'] ?? null) != $member->binary_position;

        if ($binaryMoved) {
            // ขนาด subtree (ตัวเอง + downline binary ทั้งหมด)
            $subtreeSize = $this->countBinarySubtreeSize($member);

            // ─── สายเก่า: ลบออก ───
            if (! empty($oldData['binary_parent_id'])) {
                $oldParent = MlmMember::find($oldData['binary_parent_id']);

                if ($oldParent) {
                    if (in_array($oldData['binary_position'] ?? '', ['left', 'right'], true)) {
                        $col = $oldData['binary_position'] === 'left' ? 'left_leg_members' : 'right_leg_members';
                        $oldParent->update([$col => max(0, (int) $oldParent->{$col} - $subtreeSize)]);
                    }

                    $this->adjustUplineTeamCounts($oldParent, -$subtreeSize);
                }
            }

            // ─── สายใหม่: บวกเข้า ───
            if ($member->binary_parent_id) {
                $newParent = MlmMember::find($member->binary_parent_id);

                if ($newParent) {
                    if (in_array($member->binary_position, ['left', 'right'], true)) {
                        $col = $member->binary_position === 'left' ? 'left_leg_members' : 'right_leg_members';
                        $newParent->increment($col, $subtreeSize);
                    }

                    $this->adjustUplineTeamCounts($newParent, $subtreeSize);
                }
            }
        }

        // ─── rebuild genealogy (closure table) ของ subtree ที่ย้าย ───
        try {
            app(MlmGenealogyService::class)->rebuildGenealogyForSubtree($member);
        } catch (\Throwable $e) {
            // genealogy เป็นตารางรายงาน — ล้มเหลวไม่ควรทำให้การย้ายล้ม
            Log::error('Rebuild genealogy หลังย้ายทีมล้มเหลว', [
                'member_id' => $member->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * นับขนาด subtree binary (รวมตัวเอง) ด้วย BFS
     */
    protected function countBinarySubtreeSize(MlmMember $member, int $maxNodes = 5000): int
    {
        $count = 0;
        $queue = [$member->id];
        $visited = [];

        while (! empty($queue) && $count < $maxNodes) {
            $currentId = array_shift($queue);

            if (isset($visited[$currentId])) {
                continue;
            }
            $visited[$currentId] = true;
            $count++;

            foreach (MlmMember::where('binary_parent_id', $currentId)->pluck('id') as $childId) {
                $queue[] = $childId;
            }
        }

        return $count;
    }

    /**
     * ปรับ total_team_members ของ upline binary ทั้งสาย (เริ่มจาก $start รวมตัวมันเอง)
     *
     * @param  MlmMember|null  $start  จุดเริ่ม (parent ตรงของสมาชิกที่ย้าย)
     * @param  int  $delta  จำนวนที่ปรับ (+/-)
     */
    protected function adjustUplineTeamCounts(?MlmMember $start, int $delta): void
    {
        $current = $start;
        $visited = [];

        for ($i = 0; $i < 100 && $current; $i++) {
            if (isset($visited[$current->id])) {
                break; // เจอ cycle
            }
            $visited[$current->id] = true;

            $current->update([
                'total_team_members' => max(0, (int) $current->total_team_members + $delta),
            ]);

            $current = $current->binary_parent_id
                ? MlmMember::find($current->binary_parent_id)
                : null;
        }
    }

    /**
     * ยกเลิกคำขอย้ายทีม
     *
     * @param  User  $user  ผู้ยกเลิก
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
        if (! $request->canBeCancelled()) {
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
                "🔔 มีคำขอย้ายทีมใหม่\n\n".
                "สมาชิก: %s (รหัส: %s)\n".
                "ขอย้ายไปยังแม่ทีมใหม่: %s\n\n".
                'กรุณาพิจารณาอนุมัติคำขอนี้',
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
                "✅ คำขอย้ายทีมได้รับการอนุมัติ\n\n".
                "แม่ทีมเก่า: %s\n".
                "แม่ทีมใหม่: %s\n".
                "ค่าธรรมเนียม: %s บาท\n\n".
                'กรุณาชำระค่าธรรมเนียมเพื่อดำเนินการต่อ',
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
                "❌ คำขอย้ายทีมถูกปฏิเสธ\n\n".
                "แม่ทีมเก่า: %s\n".
                "เหตุผล: %s\n\n".
                'หากต้องการข้อมูลเพิ่มเติม กรุณาติดต่อแม่ทีมของคุณ',
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
                "💰 การย้ายทีม - ชำระเงินแล้ว\n\n".
                "สมาชิก: %s (รหัส: %s)\n".
                "ค่าธรรมเนียม: %s บาท\n".
                "จาก: %s\n".
                "ไปยัง: %s\n\n".
                'กรุณาดำเนินการตรวจสอบและอนุมัติ',
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
                "🎉 การย้ายทีมเสร็จสมบูรณ์\n\n".
                "คุณได้ย้ายจากทีมของ %s\n".
                "ไปยังทีมของ %s เรียบร้อยแล้ว\n\n".
                'ขอบคุณที่ใช้บริการ!',
                $oldSponsor->name,
                $newSponsor->name
            );
            $this->sendLineMessage($member, $memberLineMessage);

            // 2. ข้อความสำหรับแม่ทีมเก่า
            $oldSponsorLineMessage = sprintf(
                "👋 สมาชิกย้ายออกจากทีม\n\n".
                "สมาชิก: %s (รหัส: %s)\n".
                "ได้ย้ายไปยังทีมของ %s แล้ว\n\n".
                'ขอบคุณสำหรับการดูแล',
                $member->name,
                $request->member->member_code,
                $newSponsor->name
            );
            $this->sendLineMessage($oldSponsor, $oldSponsorLineMessage);

            // 3. ข้อความสำหรับแม่ทีมใหม่
            $newSponsorLineMessage = sprintf(
                "🎊 สมาชิกใหม่เข้าร่วมทีม\n\n".
                "สมาชิก: %s (รหัส: %s)\n".
                "ได้ย้ายจากทีมของ %s\n".
                "มาเป็นสมาชิกในทีมของคุณแล้ว\n\n".
                'ยินดีต้อนรับสู่ทีม!',
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
     * @param  User  $user  ผู้ใช้ที่จะส่ง
     * @param  string  $message  ข้อความที่จะส่ง
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
     * @param  array  $users  รายการผู้ใช้
     * @param  string  $message  ข้อความที่จะส่ง
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

    /**
     * ย้ายทีมโดยตรงจาก Admin (ไม่ต้องผ่าน workflow)
     *
     * ฟีเจอร์นี้ให้ Admin สามารถย้ายสมาชิก MLM ไปยังทีมใหม่ได้โดยตรง
     * รองรับทั้ง Unilevel และ Binary plan
     *
     * @param  MlmMember  $member  สมาชิกที่จะย้าย
     * @param  array  $transferData  ข้อมูลการย้าย
     *                               - new_unilevel_sponsor_id (int|null): ID ของ sponsor ใหม่ใน Unilevel
     *                               - new_binary_parent_id (int|null): ID ของ parent ใหม่ใน Binary
     *                               - new_binary_position (string|null): ตำแหน่งใหม่ใน Binary ('left' หรือ 'right')
     *                               - admin_notes (string|null): หมายเหตุจาก Admin
     * @param  User  $admin  Admin ที่ดำเนินการ
     * @return array ผลลัพธ์การย้าย
     *
     * @throws \Exception
     */
    public function adminDirectTransfer(
        MlmMember $member,
        array $transferData,
        User $admin
    ): array {
        // ตรวจสอบข้อมูล
        $this->validateAdminDirectTransfer($member, $transferData);

        return DB::transaction(function () use ($member, $transferData, $admin) {
            $results = [
                'unilevel_transferred' => false,
                'binary_transferred' => false,
                'old_data' => [],
                'new_data' => [],
            ];

            // บันทึกข้อมูลเก่า
            $results['old_data'] = [
                'unilevel_sponsor_id' => $member->unilevel_sponsor_id,
                'unilevel_level' => $member->unilevel_level,
                'unilevel_path' => $member->unilevel_path,
                'binary_parent_id' => $member->binary_parent_id,
                'binary_position' => $member->binary_position,
                'binary_path' => $member->binary_path,
            ];

            // ย้าย Unilevel (ถ้ามีการระบุ)
            if (! empty($transferData['new_unilevel_sponsor_id'])) {
                $newSponsor = MlmMember::findOrFail($transferData['new_unilevel_sponsor_id']);

                $member->update([
                    'unilevel_sponsor_id' => $newSponsor->id,
                    'unilevel_level' => $newSponsor->unilevel_level + 1,
                    'unilevel_path' => $newSponsor->unilevel_path
                        ? $newSponsor->unilevel_path.'/'.$newSponsor->id
                        : (string) $newSponsor->id,
                ]);

                // อัพเดทลูกทีมของสมาชิกที่ย้าย (recursive update paths)
                $this->updateUnilevelDescendantPaths($member);

                $results['unilevel_transferred'] = true;
            }

            // ย้าย Binary (ถ้ามีการระบุ)
            if (! empty($transferData['new_binary_parent_id']) && ! empty($transferData['new_binary_position'])) {
                $newBinaryParent = MlmMember::findOrFail($transferData['new_binary_parent_id']);

                $member->update([
                    'binary_parent_id' => $newBinaryParent->id,
                    'binary_position' => $transferData['new_binary_position'],
                ]);

                // อัพเดท binary path
                $this->updateBinaryPath($member);

                // อัพเดทลูกทีม Binary ของสมาชิกที่ย้าย
                $this->updateBinaryDescendantPaths($member);

                $results['binary_transferred'] = true;
            }

            // 🐛 Fix 2026-07-24: โยกตัวนับทีม (delta ตาม subtree) + rebuild genealogy
            // (เดิม admin direct transfer ก็ไม่โยกตัวนับ/genealogy เช่นกัน)
            $this->afterTransferAdjustments($member->fresh(), $results['old_data']);

            // บันทึกข้อมูลใหม่
            $member->refresh();
            $results['new_data'] = [
                'unilevel_sponsor_id' => $member->unilevel_sponsor_id,
                'unilevel_level' => $member->unilevel_level,
                'unilevel_path' => $member->unilevel_path,
                'binary_parent_id' => $member->binary_parent_id,
                'binary_position' => $member->binary_position,
                'binary_path' => $member->binary_path,
            ];

            // Log การย้าย
            Log::info('Admin direct team transfer completed', [
                'member_id' => $member->id,
                'member_code' => $member->member_code,
                'admin_id' => $admin->id,
                'admin_name' => $admin->name,
                'old_data' => $results['old_data'],
                'new_data' => $results['new_data'],
                'admin_notes' => $transferData['admin_notes'] ?? null,
            ]);

            // ส่งการแจ้งเตือน
            $this->sendAdminDirectTransferNotifications($member, $results, $admin);

            return $results;
        });
    }

    /**
     * ตรวจสอบความถูกต้องสำหรับ Admin Direct Transfer
     *
     *
     * @throws \Exception
     */
    protected function validateAdminDirectTransfer(MlmMember $member, array $transferData): void
    {
        // ตรวจสอบว่ามีการระบุอย่างน้อย 1 อย่าง (Unilevel หรือ Binary)
        $hasUnilevel = ! empty($transferData['new_unilevel_sponsor_id']);
        $hasBinary = ! empty($transferData['new_binary_parent_id']) && ! empty($transferData['new_binary_position']);

        if (! $hasUnilevel && ! $hasBinary) {
            throw new \Exception('กรุณาระบุ Sponsor ใหม่ (Unilevel) หรือ Parent ใหม่ (Binary) อย่างน้อย 1 อย่าง');
        }

        // ตรวจสอบ Unilevel Transfer
        if ($hasUnilevel) {
            $newSponsorId = $transferData['new_unilevel_sponsor_id'];

            // ตรวจสอบว่าไม่ใช่ตัวเอง
            if ($member->id == $newSponsorId) {
                throw new \Exception('ไม่สามารถย้ายไปหาตัวเองได้ (Unilevel)');
            }

            // ตรวจสอบว่า sponsor ใหม่มีอยู่จริง
            $newSponsor = MlmMember::find($newSponsorId);
            if (! $newSponsor) {
                throw new \Exception('ไม่พบ Sponsor ใหม่ที่ระบุ (Unilevel)');
            }

            // ตรวจสอบว่า sponsor ใหม่ไม่ใช่ลูกทีมของตัวเอง
            if ($this->isDownline($member, $newSponsor)) {
                throw new \Exception('ไม่สามารถย้ายไปหาลูกทีมของตัวเองได้ (Unilevel)');
            }

            // ตรวจสอบว่าไม่ใช่ sponsor เดิม
            if ($member->unilevel_sponsor_id == $newSponsorId) {
                throw new \Exception('Sponsor ใหม่เหมือนกับ Sponsor เดิม (Unilevel)');
            }
        }

        // ตรวจสอบ Binary Transfer
        if ($hasBinary) {
            $newBinaryParentId = $transferData['new_binary_parent_id'];
            $newBinaryPosition = $transferData['new_binary_position'];

            // ตรวจสอบว่าไม่ใช่ตัวเอง
            if ($member->id == $newBinaryParentId) {
                throw new \Exception('ไม่สามารถย้ายไปหาตัวเองได้ (Binary)');
            }

            // ตรวจสอบว่า parent ใหม่มีอยู่จริง
            $newBinaryParent = MlmMember::find($newBinaryParentId);
            if (! $newBinaryParent) {
                throw new \Exception('ไม่พบ Binary Parent ใหม่ที่ระบุ');
            }

            // ตรวจสอบ position
            if (! in_array($newBinaryPosition, ['left', 'right'])) {
                throw new \Exception('ตำแหน่ง Binary ต้องเป็น left หรือ right เท่านั้น');
            }

            // ตรวจสอบว่า parent ใหม่ไม่ใช่ลูกทีม Binary ของตัวเอง
            if ($this->isBinaryDownline($member, $newBinaryParent)) {
                throw new \Exception('ไม่สามารถย้ายไปหาลูกทีม Binary ของตัวเองได้');
            }

            // ตรวจสอบว่าตำแหน่งว่างหรือไม่
            $existingChild = MlmMember::where('binary_parent_id', $newBinaryParentId)
                ->where('binary_position', $newBinaryPosition)
                ->where('id', '!=', $member->id)
                ->first();

            if ($existingChild) {
                throw new \Exception("ตำแหน่ง {$newBinaryPosition} ของ Binary Parent นี้ไม่ว่าง (มีสมาชิก: {$existingChild->member_code})");
            }

            // ตรวจสอบว่าไม่ใช่ parent เดิม + ตำแหน่งเดิม
            if ($member->binary_parent_id == $newBinaryParentId && $member->binary_position == $newBinaryPosition) {
                throw new \Exception('Binary Parent และตำแหน่งใหม่เหมือนกับเดิม');
            }
        }
    }

    /**
     * ตรวจสอบว่า $potential_downline เป็นลูกทีม Binary ของ $member หรือไม่
     *
     * 🐛 Fix 2026-07-24: ห้ามเชื่อ binary_path อย่างเดียว — path ในระบบมี 2 รูปแบบปนกัน
     * (แบบ id "12/45/89" จาก transfer และแบบตำแหน่ง "/left/right" จาก registration)
     * แบบตำแหน่งทำให้เช็ค in_array(id) พลาดเสมอ → cycle check หลุด
     * จึงใช้ path เป็น fast-path ยืนยัน "ใช่" เท่านั้น ส่วนคำตอบ "ไม่ใช่" ต้องเดินขึ้นจริง
     */
    protected function isBinaryDownline(MlmMember $member, MlmMember $potentialDownline): bool
    {
        // fast-path: ถ้า path เป็นแบบ id และเจอ id ใน path → ยืนยันได้ทันที
        if ($potentialDownline->binary_path
            && in_array((string) $member->id, explode('/', $potentialDownline->binary_path), true)) {
            return true;
        }

        // เดินขึ้นตาม binary_parent_id จริง (พร้อม cycle guard + depth cap)
        $visited = [];
        $current = $potentialDownline;

        for ($i = 0; $i < 100 && $current && $current->binary_parent_id; $i++) {
            if ($current->binary_parent_id == $member->id) {
                return true;
            }

            if (isset($visited[$current->binary_parent_id])) {
                break; // เจอ cycle
            }
            $visited[$current->binary_parent_id] = true;

            $current = MlmMember::find($current->binary_parent_id);
        }

        return false;
    }

    /**
     * อัพเดท unilevel path ของลูกทีมทั้งหมด (recursive)
     */
    protected function updateUnilevelDescendantPaths(MlmMember $member): void
    {
        // ดึงลูกทีมตรงของสมาชิก
        $children = MlmMember::where('unilevel_sponsor_id', $member->id)->get();

        foreach ($children as $child) {
            // สร้าง path ใหม่
            $newPath = $member->unilevel_path
                ? $member->unilevel_path.'/'.$member->id
                : (string) $member->id;

            $child->update([
                'unilevel_level' => $member->unilevel_level + 1,
                'unilevel_path' => $newPath,
            ]);

            // Recursive update
            $this->updateUnilevelDescendantPaths($child);
        }
    }

    /**
     * อัพเดท binary path ของลูกทีม Binary ทั้งหมด (recursive)
     */
    protected function updateBinaryDescendantPaths(MlmMember $member): void
    {
        // ดึงลูกทีม Binary ของสมาชิก
        $children = MlmMember::where('binary_parent_id', $member->id)->get();

        foreach ($children as $child) {
            // อัพเดท path
            $this->updateBinaryPath($child);

            // Recursive update
            $this->updateBinaryDescendantPaths($child);
        }
    }

    /**
     * ส่งการแจ้งเตือนสำหรับ Admin Direct Transfer
     */
    protected function sendAdminDirectTransferNotifications(
        MlmMember $member,
        array $results,
        User $admin
    ): void {
        try {
            $memberUser = $member->user;
            if (! $memberUser) {
                return;
            }

            // สร้างข้อความ
            $transferTypes = [];
            if ($results['unilevel_transferred']) {
                $newSponsor = MlmMember::find($results['new_data']['unilevel_sponsor_id']);
                $transferTypes[] = "Unilevel → {$newSponsor?->user?->name}";
            }
            if ($results['binary_transferred']) {
                $newParent = MlmMember::find($results['new_data']['binary_parent_id']);
                $transferTypes[] = "Binary → {$newParent?->user?->name} ({$results['new_data']['binary_position']})";
            }

            // ส่ง LINE notification
            $lineMessage = sprintf(
                "📢 แจ้งเตือนการย้ายทีมโดย Admin\n\n".
                "สมาชิก: %s (รหัส: %s)\n".
                "การย้าย: %s\n".
                "ดำเนินการโดย: %s\n\n".
                'หากมีข้อสงสัย กรุณาติดต่อทีมงาน',
                $memberUser->name,
                $member->member_code,
                implode("\n", $transferTypes),
                $admin->name
            );

            $this->sendLineMessage($memberUser, $lineMessage);

        } catch (\Exception $e) {
            Log::error('Failed to send admin direct transfer notification', [
                'member_id' => $member->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * ดึงข้อมูล Binary Children ที่ว่างของ member
     *
     * @return array ตำแหน่งที่ว่าง ['left' => bool, 'right' => bool]
     */
    public function getAvailableBinaryPositions(MlmMember $member): array
    {
        $leftOccupied = MlmMember::where('binary_parent_id', $member->id)
            ->where('binary_position', 'left')
            ->exists();

        $rightOccupied = MlmMember::where('binary_parent_id', $member->id)
            ->where('binary_position', 'right')
            ->exists();

        return [
            'left' => ! $leftOccupied,
            'right' => ! $rightOccupied,
        ];
    }

    /**
     * ค้นหาสมาชิกสำหรับ autocomplete
     *
     * @param  string  $search  คำค้นหา (ชื่อ, อีเมล, member_code)
     * @param  int|null  $excludeId  ID ที่ต้องการยกเว้น
     * @param  int  $limit  จำนวนผลลัพธ์สูงสุด
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function searchMembersForTransfer(
        string $search,
        ?int $excludeId = null,
        int $limit = 20
    ) {
        $query = MlmMember::with('user')
            ->where('status', 'active');

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $query->where(function ($q) use ($search) {
            $q->where('member_code', 'like', "%{$search}%")
                ->orWhereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
        });

        return $query->limit($limit)->get();
    }
}
