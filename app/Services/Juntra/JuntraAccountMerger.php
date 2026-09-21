<?php

namespace App\Services\Juntra;

use App\Models\JuntraAccount;
use App\Models\MlmMember;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\MlmTeamTransferService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * 🌙 (2026-09-21) รวมบัญชีลูกค้าจันทรา — Thaiprompt เป็นตัวหลัก (เจ้าของสั่ง)
 *
 * เคส: ลูกค้าซื้อบนจันทราก่อนผูก Thaiprompt → ระบบสร้างผู้ใช้เงาให้ (linked_via = auto)
 *   ต่อมาลูกค้าผูกบัญชี Thaiprompt จริง → ย้ายทุกอย่างของผู้ใช้เงาเข้าบัญชีจริง:
 *     - บิลดูดวง (fortune_readings.user_id) และค่าแนะนำทั้งที่ได้รับและที่เกิดจากบิลของเขา
 *     - ยอดในกระเป๋า (ถอนจากเงา → เข้าบัญชีจริง มีรายการทั้งสองฝั่ง · ยอดติดลบก็ย้ายตามคน)
 *     - ตำแหน่งในผัง: บัญชีจริงยังไม่อยู่ในผัง = รับตำแหน่งของเงาไปทั้งตำแหน่ง (ผู้แนะนำ+ลูกทีมเดิม)
 *       บัญชีจริงมีตำแหน่งอยู่แล้ว = คงตำแหน่งของบัญชีจริง ลูกทีมของเงาย้ายมาอยู่ใต้บัญชีจริง
 *       แล้วตำแหน่งของเงาถูกปิด (inactive) — ลิงก์เชิญเดิมของเงายังพามาที่บัญชีจริง (pickSponsor)
 *
 * ทำทั้งหมดใน transaction เดียว — ทำไม่ได้ส่วนใด (เช่นบัญชีจริงอยู่ใต้ผังของเงา) = ไม่รวมเลย
 *   จดไว้ที่ merge_failed_at แล้วเว้น 1 วันก่อนลองใหม่ (ให้แอดมินจัดผังก่อน) ลูกค้ายังใช้บัญชีเงาได้ตามเดิม
 *
 * บิลที่กำลังแจกค่าแนะนำพร้อมกับตอนรวม (ต่างล็อกกัน) อาจเขียนลงผู้ใช้เงาหลังรวมเสร็จ —
 *   sweep() เก็บตกซ้ำได้ทุกเมื่อ และ juntra:sweep-merged-accounts เรียกให้ทุก 5 นาที
 */
class JuntraAccountMerger
{
    public function __construct(
        private MlmTeamTransferService $transfers,
        private JuntraMlmReadService $reads,
    ) {}

    /**
     * @return JuntraAccount บัญชีหลังรวม (หรือตัวเดิมถ้ารวมไม่ได้)
     */
    public function mergeIntoThaiprompt(JuntraAccount $account, int $thaipromptUserId): JuntraAccount
    {
        if ($account->linked_via !== JuntraAccount::LINKED_VIA_AUTO || (int) $account->user_id === $thaipromptUserId) {
            return $account;
        }

        // เพิ่งรวมไม่สำเร็จ — ไม่ลองทุกบิล (แต่ละครั้งล็อกผังแล้ว rollback) รอแอดมินจัดผังก่อน
        if ($account->merge_failed_at && $account->merge_failed_at->gt(now()->subHours(JuntraAccount::MERGE_RETRY_HOURS))) {
            return $account;
        }

        $real = User::find($thaipromptUserId);
        $shadow = User::find($account->user_id);
        if (! $real || ! $shadow) {
            return $account;
        }

        // บัญชีจริงเป็นของลูกค้าจันทราอีกคนแล้ว — รวมซ้อนไม่ได้
        if (JuntraAccount::where('user_id', $real->id)->whereKeyNot($account->id)->exists()) {
            return $this->markFailed($account, $real->id, 'บัญชี Thaiprompt นี้ผูกกับลูกค้าจันทราคนอื่นแล้ว');
        }

        $shadowMemberBefore = MlmMember::where('user_id', $shadow->id)->first();

        try {
            DB::transaction(function () use ($account, $shadow, $real) {
                $keptShadowPosition = $this->mergeTree($shadow, $real);

                $this->moveLeftovers($shadow->id, $real->id);

                $account->update([
                    'user_id' => $real->id,
                    'linked_via' => JuntraAccount::LINKED_VIA_SSO,
                    // รับตำแหน่งเงาไป = ยังเป็นตำแหน่งที่จันทราสร้าง · คงตำแหน่งเดิมของบัญชีจริง = จัดการที่หลังบ้านแม่หมอ
                    'enrolled_member_id' => $keptShadowPosition ? $account->enrolled_member_id : null,
                    'merged_from_user_id' => $shadow->id,
                    'merged_at' => now(),
                    'merge_failed_at' => null,
                    'merge_error' => null,
                ]);
            });
        } catch (\Throwable $e) {
            return $this->markFailed($account->fresh() ?? $account, $real->id, $e->getMessage());
        }

        $this->forgetCaches($shadow->id, $real->id, $shadowMemberBefore);

        Log::info('Juntra merge: รวมบัญชีลูกค้าจันทราเข้าบัญชี Thaiprompt แล้ว', [
            'juntra_account_id' => $account->id,
            'shadow_user_id' => $shadow->id,
            'thaiprompt_user_id' => $real->id,
        ]);

        return $account->fresh()->setRelation('user', $real);
    }

    /**
     * เก็บตกของที่ยังค้างกับผู้ใช้เงาหลังรวมบัญชี (บิล/ค่าแนะนำ/ยอดกระเป๋า) — เรียกซ้ำได้
     *
     * @return bool มีของย้ายรอบนี้
     */
    public function sweep(JuntraAccount $account): bool
    {
        $shadowId = (int) $account->merged_from_user_id;
        $realId = (int) $account->user_id;
        if ($shadowId === 0 || $shadowId === $realId) {
            return false;
        }

        $moved = DB::transaction(fn () => $this->moveLeftovers($shadowId, $realId));

        if ($moved) {
            $this->forgetCaches($shadowId, $realId, MlmMember::where('user_id', $shadowId)->first());
            Log::warning('Juntra merge: เก็บตกของที่เข้าบัญชีเงาหลังรวมบัญชี', [
                'juntra_account_id' => $account->id,
                'shadow_user_id' => $shadowId,
                'thaiprompt_user_id' => $realId,
            ]);
        }

        return $moved;
    }

    /**
     * ตำแหน่งในผัง — Thaiprompt เป็นตัวหลัก
     *
     * @return bool true = บัญชีจริงรับตำแหน่งของเงาไป (ตำแหน่งยังเป็นของที่จันทราสร้าง)
     */
    private function mergeTree(User $shadow, User $real): bool
    {
        $shadowMember = MlmMember::where('user_id', $shadow->id)->lockForUpdate()->first();
        if (! $shadowMember) {
            return false;
        }

        $realMember = MlmMember::where('user_id', $real->id)->lockForUpdate()->first();
        if (! $realMember) {
            // บัญชีจริงยังไม่อยู่ในผัง → รับตำแหน่งของเงาไปทั้งตำแหน่ง (id สมาชิกเดิม = ค่าแนะนำเดิมตามมาเอง)
            $shadowMember->update(['user_id' => $real->id]);

            return true;
        }

        // ทั้งคู่อยู่ในผัง → คงตำแหน่งบัญชีจริง ลูกทีมของเงาย้ายมาใต้บัญชีจริง (โยกตัวนับทีม/เส้นทางด้วย)
        //   บัญชีจริงอยู่ใต้ผังของเงา = ย้ายไม่ได้ (ผังวนลูป) — adminDirectTransfer โยนเอง ทั้งก้อน rollback
        foreach (MlmMember::where('unilevel_sponsor_id', $shadowMember->id)->get() as $child) {
            $this->transfers->adminDirectTransfer($child, [
                'new_unilevel_sponsor_id' => $realMember->id,
                'admin_notes' => "รวมบัญชีจันทรา: ย้ายจากผู้ใช้ #{$shadow->id} ไปบัญชี Thaiprompt #{$real->id}",
                'notify' => false,
            ], User::find(1) ?? $real);
        }

        $shadowMember->update(['status' => 'inactive']);

        return false;
    }

    /**
     * ย้ายทุกอย่างที่ยังผูกกับผู้ใช้เงา → บัญชีจริง (ต้องอยู่ใน transaction)
     *
     * ใช้ query builder ตรง ๆ — ติดแถวที่ถูก soft delete ไปด้วย (ประวัติต้องตามคนไปทั้งหมด)
     */
    private function moveLeftovers(int $shadowId, int $realId): bool
    {
        $moved = DB::table('fortune_readings')->where('user_id', $shadowId)->update(['user_id' => $realId]);
        $moved += DB::table('fortune_commissions')->where('user_id', $shadowId)->update(['user_id' => $realId]);
        $moved += DB::table('fortune_commissions')->where('from_user_id', $shadowId)->update(['from_user_id' => $realId]);

        // ตำแหน่งเงาที่ถูกปิด (บัญชีจริงมีตำแหน่งอยู่แล้ว) — ค่าแนะนำที่ผูกตำแหน่งนั้นย้ายไปตำแหน่งจริง
        $shadowMemberId = MlmMember::where('user_id', $shadowId)->value('id');
        $realMemberId = MlmMember::where('user_id', $realId)->value('id');
        if ($shadowMemberId && $realMemberId) {
            $moved += DB::table('fortune_commissions')->where('mlm_member_id', $shadowMemberId)->update(['mlm_member_id' => $realMemberId]);
            $moved += DB::table('fortune_commissions')->where('from_mlm_member_id', $shadowMemberId)->update(['from_mlm_member_id' => $realMemberId]);
        }

        return $this->moveWallet($shadowId, $realId) || $moved > 0;
    }

    /**
     * ยอดในกระเป๋าเงา → บัญชีจริง ทั้งบวกและลบ (ยอดติดลบ = ค่าแนะนำที่ถูกดึงคืนหลังรวม หนี้ต้องตามคน)
     *
     * ล็อกทั้งสองใบตามลำดับ id — กันชนกับการจ่าย/ดึงคืนค่าแนะนำพร้อมกัน
     */
    private function moveWallet(int $shadowId, int $realId): bool
    {
        $wallets = Wallet::whereIn('user_id', [$shadowId, $realId])->orderBy('id')->lockForUpdate()->get()->keyBy('user_id');
        $from = $wallets->get($shadowId);
        $amount = $from ? round((float) $from->balance, 2) : 0.0;
        $income = $from ? (float) ($from->total_income ?? 0) : 0.0;
        if ($amount == 0.0 && $income == 0.0) {
            return false;
        }

        $to = $wallets->get($realId) ?? tap(Wallet::create([
            'user_id' => $realId,
            'balance' => 0,
            'currency' => 'THB',
            'status' => 'active',
        ]))->refresh();

        $note = "รวมบัญชีจันทรา: ผู้ใช้ #{$shadowId} → บัญชี Thaiprompt #{$realId}";

        if ($amount != 0.0) {
            // บวก = เงาโอนออก/บัญชีจริงรับเข้า · ลบ = กลับทิศ (ล้างหนี้ฝั่งเงา แล้วหักบัญชีจริง)
            $this->ledger($from, $shadowId, $amount > 0 ? 'transfer_out' : 'transfer_in', abs($amount), (float) $from->balance, 0.0, $note, ['to_user_id' => $realId]);
            $before = (float) $to->balance;
            $this->ledger($to, $realId, $amount > 0 ? 'transfer_in' : 'transfer_out', abs($amount), $before, $before + $amount, $note, ['from_user_id' => $shadowId]);
        }

        $from->update(['balance' => 0, 'total_income' => 0, 'last_transaction_at' => now()]);
        $to->update([
            'balance' => (float) $to->balance + $amount,
            'total_income' => (float) ($to->total_income ?? 0) + $income,
            'last_transaction_at' => now(),
        ]);

        return true;
    }

    private function ledger(Wallet $wallet, int $userId, string $type, float $amount, float $before, float $after, string $note, array $meta): void
    {
        WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'user_id' => $userId,
            'type' => $type,
            'amount' => $amount,
            'balance_before' => $before,
            'balance_after' => $after,
            'currency' => $wallet->currency,
            'description' => $note,
            'status' => 'completed',
            'metadata' => ['mode' => 'juntra_account_merge'] + $meta,
            'completed_at' => now(),
        ]);
    }

    private function markFailed(JuntraAccount $account, int $realId, string $error): JuntraAccount
    {
        $account->forceFill([
            'merge_failed_at' => now(),
            'merge_error' => Str::limit($error, 250, ''),
        ])->save();

        Log::error('Juntra merge: รวมบัญชีไม่สำเร็จ — ยังใช้บัญชีเดิมอยู่ ลองใหม่ใน '.JuntraAccount::MERGE_RETRY_HOURS.' ชม. (แอดมินจัดผังได้ก่อน)', [
            'juntra_account_id' => $account->id,
            'shadow_user_id' => $account->user_id,
            'thaiprompt_user_id' => $realId,
            'error' => $error,
        ]);

        return $account;
    }

    /** ผังของทั้งสองบัญชี + สายผู้แนะนำเดิมของเงา + สายของบัญชีจริง */
    private function forgetCaches(int $shadowId, int $realId, ?MlmMember $shadowMember): void
    {
        $this->reads->forgetCachesFor($shadowId);
        $this->reads->forgetCachesFor($realId);
        if ($shadowMember) {
            $this->reads->forgetCachesForUpline($shadowMember->fresh() ?? $shadowMember);
        }
        if ($member = MlmMember::where('user_id', $realId)->first()) {
            $this->reads->forgetCachesForUpline($member);
        }
    }
}
