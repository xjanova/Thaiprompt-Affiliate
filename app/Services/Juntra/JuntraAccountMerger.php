<?php

namespace App\Services\Juntra;

use App\Models\FortuneCommission;
use App\Models\FortuneReading;
use App\Models\JuntraAccount;
use App\Models\MlmMember;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\MlmTeamTransferService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 🌙 (2026-09-21) รวมบัญชีลูกค้าจันทรา — Thaiprompt เป็นตัวหลัก (เจ้าของสั่ง)
 *
 * เคส: ลูกค้าซื้อบนจันทราก่อนผูก Thaiprompt → ระบบสร้างผู้ใช้เงาให้ (linked_via = auto)
 *   ต่อมาลูกค้าผูกบัญชี Thaiprompt จริง → ย้ายทุกอย่างของผู้ใช้เงาเข้าบัญชีจริง:
 *     - บิลดูดวง (fortune_readings.user_id) และค่าแนะนำทั้งที่ได้รับและที่เกิดจากบิลของเขา
 *     - ยอดในกระเป๋า (ถอนจากเงา → เข้าบัญชีจริง มีรายการทั้งสองฝั่ง)
 *     - ตำแหน่งในผัง: บัญชีจริงยังไม่อยู่ในผัง = รับตำแหน่งของเงาไปทั้งตำแหน่ง (ผู้แนะนำ+ลูกทีมเดิม)
 *       บัญชีจริงมีตำแหน่งอยู่แล้ว = คงตำแหน่งของบัญชีจริง ลูกทีมของเงาย้ายมาอยู่ใต้บัญชีจริง
 *       แล้วตำแหน่งของเงาถูกปิด (inactive)
 *
 * ทำทั้งหมดใน transaction เดียว — ทำไม่ได้ส่วนใด (เช่นการย้ายทำให้ผังวนลูป) = ไม่รวมเลย แล้วบันทึกไว้
 *   ให้แอดมินดู ลูกค้ายังใช้บัญชีเงาได้ตามเดิม ไม่มีอะไรหาย
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

        $real = User::find($thaipromptUserId);
        $shadow = User::find($account->user_id);
        if (! $real || ! $shadow) {
            return $account;
        }

        // บัญชีจริงเป็นของลูกค้าจันทราอีกคนแล้ว — รวมซ้อนไม่ได้ (ให้แอดมินดู)
        if (JuntraAccount::where('user_id', $real->id)->whereKeyNot($account->id)->exists()) {
            Log::warning('Juntra merge: บัญชี Thaiprompt นี้ผูกกับลูกค้าจันทราคนอื่นแล้ว — ไม่รวม', [
                'juntra_account_id' => $account->id,
                'thaiprompt_user_id' => $real->id,
            ]);

            return $account;
        }

        try {
            DB::transaction(function () use ($account, $shadow, $real) {
                $this->mergeTree($shadow, $real);

                FortuneReading::where('user_id', $shadow->id)->update(['user_id' => $real->id]);
                FortuneCommission::where('user_id', $shadow->id)->update(['user_id' => $real->id]);
                FortuneCommission::where('from_user_id', $shadow->id)->update(['from_user_id' => $real->id]);

                $this->moveWallet($shadow, $real);

                $account->update([
                    'user_id' => $real->id,
                    'linked_via' => JuntraAccount::LINKED_VIA_SSO,
                    'merged_from_user_id' => $shadow->id,
                    'merged_at' => now(),
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('Juntra merge: รวมบัญชีไม่สำเร็จ — ยังใช้บัญชีเดิมอยู่ ต้องให้แอดมินดู', [
                'juntra_account_id' => $account->id,
                'shadow_user_id' => $shadow->id,
                'thaiprompt_user_id' => $real->id,
                'error' => $e->getMessage(),
            ]);

            return $account->fresh() ?? $account;
        }

        foreach ([$shadow->id, $real->id] as $uid) {
            $this->reads->forgetCachesFor($uid);
        }
        if ($member = MlmMember::where('user_id', $real->id)->first()) {
            $this->reads->forgetCachesForUpline($member);
        }

        Log::info('Juntra merge: รวมบัญชีลูกค้าจันทราเข้าบัญชี Thaiprompt แล้ว', [
            'juntra_account_id' => $account->id,
            'shadow_user_id' => $shadow->id,
            'thaiprompt_user_id' => $real->id,
        ]);

        return $account->fresh()->setRelation('user', $real);
    }

    /** ตำแหน่งในผัง — Thaiprompt เป็นตัวหลัก */
    private function mergeTree(User $shadow, User $real): void
    {
        $shadowMember = MlmMember::where('user_id', $shadow->id)->lockForUpdate()->first();
        if (! $shadowMember) {
            return;
        }

        $realMember = MlmMember::where('user_id', $real->id)->lockForUpdate()->first();
        if (! $realMember) {
            // บัญชีจริงยังไม่อยู่ในผัง → รับตำแหน่งของเงาไปทั้งตำแหน่ง (id สมาชิกเดิม = ค่าแนะนำเดิมตามมาเอง)
            $shadowMember->update(['user_id' => $real->id]);

            return;
        }

        // ทั้งคู่อยู่ในผัง → คงตำแหน่งบัญชีจริง ลูกทีมของเงาย้ายมาใต้บัญชีจริง (โยกตัวนับทีม/เส้นทางด้วย)
        $children = MlmMember::where('unilevel_sponsor_id', $shadowMember->id)->get();
        foreach ($children as $child) {
            if ((int) $child->id === (int) $realMember->id) {
                throw new \RuntimeException('บัญชีจริงอยู่ใต้บัญชีเงาโดยตรง — ต้องให้แอดมินจัดผังเอง');
            }
            $this->transfers->adminDirectTransfer($child, [
                'new_unilevel_sponsor_id' => $realMember->id,
                'admin_notes' => "รวมบัญชีจันทรา: ย้ายจากผู้ใช้ #{$shadow->id} ไปบัญชี Thaiprompt #{$real->id}",
                'notify' => false,
            ], User::find(1) ?? $real);
        }

        FortuneCommission::where('mlm_member_id', $shadowMember->id)->update(['mlm_member_id' => $realMember->id]);
        FortuneCommission::where('from_mlm_member_id', $shadowMember->id)->update(['from_mlm_member_id' => $realMember->id]);

        $shadowMember->update(['status' => 'inactive']);
    }

    /** ยอดในกระเป๋าเงา → บัญชีจริง (ล็อกทั้งสองใบตามลำดับ id — กันชนกับการจ่ายค่าแนะนำพร้อมกัน) */
    private function moveWallet(User $shadow, User $real): void
    {
        $wallets = Wallet::whereIn('user_id', [$shadow->id, $real->id])->orderBy('id')->lockForUpdate()->get()->keyBy('user_id');
        $from = $wallets->get($shadow->id);
        $amount = $from ? (float) $from->balance : 0.0;
        if ($amount <= 0) {
            return;
        }

        $to = $wallets->get($real->id) ?? tap(Wallet::create([
            'user_id' => $real->id,
            'balance' => 0,
            'currency' => 'THB',
            'status' => 'active',
        ]))->refresh();

        $note = "รวมบัญชีจันทรา: ผู้ใช้ #{$shadow->id} → บัญชี Thaiprompt #{$real->id}";

        WalletTransaction::create([
            'wallet_id' => $from->id,
            'user_id' => $shadow->id,
            'type' => 'transfer_out',
            'amount' => $amount,
            'balance_before' => $amount,
            'balance_after' => 0,
            'currency' => $from->currency,
            'description' => $note,
            'status' => 'completed',
            'metadata' => ['mode' => 'juntra_account_merge', 'to_user_id' => $real->id],
            'completed_at' => now(),
        ]);
        $movedIncome = (float) ($from->total_income ?? 0);
        $from->update(['balance' => 0, 'total_income' => 0, 'last_transaction_at' => now()]);

        $before = (float) $to->balance;
        WalletTransaction::create([
            'wallet_id' => $to->id,
            'user_id' => $real->id,
            'type' => 'transfer_in',
            'amount' => $amount,
            'balance_before' => $before,
            'balance_after' => $before + $amount,
            'currency' => $to->currency,
            'description' => $note,
            'status' => 'completed',
            'metadata' => ['mode' => 'juntra_account_merge', 'from_user_id' => $shadow->id],
            'completed_at' => now(),
        ]);
        $to->update([
            'balance' => $before + $amount,
            'total_income' => (float) ($to->total_income ?? 0) + $movedIncome,
            'last_transaction_at' => now(),
        ]);
    }
}
