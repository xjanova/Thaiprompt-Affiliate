<?php

namespace App\Services\Fortune;

use App\Models\FortuneTellingSetting;
use App\Models\SlipOkAccount;
use Illuminate\Support\Collection;

/**
 * 🪪 SlipOK Account Pool Resolver (2026-06-09)
 *
 * เลือกบัญชี SlipOK ที่จะใช้ยิง API ตามโหมดที่แอดมินตั้ง — กัน quota ฟรี (~100/เดือน) ตันทั้งระบบ
 *
 * โหมด:
 *   - near_empty : ใช้บัญชี priority ต่ำสุดไปเรื่อยๆ พอเหลือ < threshold → สลับบัญชีถัดไป
 *   - failover   : ใช้จนหมด/พัง แล้วค่อยสลับ (auto-failover-on-error เปิดเสมอทุกโหมด)
 *   - balance    : เฉลี่ย — เลือกบัญชีที่เหลือโควต้ามากสุด
 *
 * Backward-compat: ถ้า pool ปิด หรือไม่มีบัญชี → คืน null → SlipOkService ใช้ key เดี่ยวเดิม
 *
 * @see SlipOkService
 */
class SlipOkAccountPool
{
    protected FortuneTellingSetting $settings;

    public function __construct(?FortuneTellingSetting $settings = null)
    {
        $this->settings = $settings ?? FortuneTellingSetting::getSettings();
    }

    /** เปิดใช้ระบบ pool หรือไม่ (ปิด = ใช้ key เดี่ยวเดิม) */
    public function isEnabled(): bool
    {
        return (bool) ($this->settings->slipok_pool_enabled ?? false)
            && SlipOkAccount::query()->where('is_active', true)->exists();
    }

    /** โหมดหมุนบัญชีปัจจุบัน */
    public function mode(): string
    {
        $mode = (string) ($this->settings->slipok_pool_mode ?? SlipOkAccount::MODE_NEAR_EMPTY);

        return array_key_exists($mode, SlipOkAccount::MODES) ? $mode : SlipOkAccount::MODE_NEAR_EMPTY;
    }

    /** เกณฑ์ "ใกล้หมด" สำหรับโหมด near_empty */
    public function threshold(): int
    {
        return max(1, (int) ($this->settings->slipok_pool_threshold ?? 10));
    }

    /**
     * บัญชีที่พร้อมยิงจริง (active + ไม่พัก + เหลือโควต้า) — reset ตัวนับข้ามเดือนให้ด้วย
     *
     * @return Collection<int, SlipOkAccount>
     */
    public function usableAccounts(): Collection
    {
        return SlipOkAccount::available()->get()
            ->each(fn (SlipOkAccount $a) => $a->resetMonthlyIfNeeded())
            ->filter(fn (SlipOkAccount $a) => $a->remainingQuota() > 0)
            ->values();
    }

    /**
     * 🎯 เลือกบัญชีที่จะใช้ยิงครั้งนี้ (null = ไม่มีบัญชีพร้อม → fallback key เดี่ยว)
     */
    public function pick(): ?SlipOkAccount
    {
        $accounts = $this->usableAccounts();
        if ($accounts->isEmpty()) {
            return null;
        }

        return match ($this->mode()) {
            SlipOkAccount::MODE_BALANCE => $this->pickBalance($accounts),
            SlipOkAccount::MODE_FAILOVER => $this->pickFailover($accounts),
            default => $this->pickNearEmpty($accounts),
        };
    }

    /**
     * near_empty: priority ต่ำสุดที่เหลือ > threshold; ถ้าไม่มี → ตัวที่เหลือมากสุด (ยังพอใช้)
     */
    protected function pickNearEmpty(Collection $accounts): SlipOkAccount
    {
        $threshold = $this->threshold();

        // เรียงตาม priority อยู่แล้ว (scopeAvailable) → ตัวแรกที่เหลือ > threshold
        $aboveThreshold = $accounts->first(fn (SlipOkAccount $a) => $a->remainingQuota() > $threshold);
        if ($aboveThreshold) {
            return $aboveThreshold;
        }

        // ทุกตัวใกล้หมดแล้ว → ใช้ตัวที่เหลือมากสุด (รีดให้คุ้มก่อนหมดจริง)
        return $accounts->sortByDesc(fn (SlipOkAccount $a) => $a->remainingQuota())->first();
    }

    /**
     * failover: priority ต่ำสุดที่ยังใช้ได้ (ใช้ตัวเดิมจนหมด/พัง แล้วค่อยขยับ)
     */
    protected function pickFailover(Collection $accounts): SlipOkAccount
    {
        return $accounts->first();
    }

    /**
     * balance: ตัวที่เหลือโควต้ามากสุด (tie → priority ต่ำสุด)
     */
    protected function pickBalance(Collection $accounts): SlipOkAccount
    {
        return $accounts
            ->sort(function (SlipOkAccount $a, SlipOkAccount $b) {
                $cmp = $b->remainingQuota() <=> $a->remainingQuota();

                return $cmp !== 0 ? $cmp : ($a->priority <=> $b->priority);
            })
            ->first();
    }

    /**
     * บัญชีถัดไปหลังจากตัวที่ระบุพัง/หมด (สำหรับ auto-failover ภายในคำขอเดียว)
     *
     * @param  array<int>  $excludeIds  ids ที่ลองแล้วในรอบนี้ (กันวน)
     */
    public function nextAfter(array $excludeIds): ?SlipOkAccount
    {
        $accounts = $this->usableAccounts()
            ->filter(fn (SlipOkAccount $a) => ! in_array($a->id, $excludeIds, true))
            ->values();

        if ($accounts->isEmpty()) {
            return null;
        }

        // ใช้ logic เดียวกับ pick() กับชุดที่เหลือ
        return match ($this->mode()) {
            SlipOkAccount::MODE_BALANCE => $this->pickBalance($accounts),
            default => $accounts->first(),
        };
    }

    /** จำนวนบัญชี active ทั้งหมด (ใช้กำหนด max attempts failover) */
    public function activeCount(): int
    {
        return SlipOkAccount::query()->where('is_active', true)->count();
    }
}
