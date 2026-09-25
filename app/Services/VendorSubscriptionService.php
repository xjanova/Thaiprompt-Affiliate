<?php

namespace App\Services;

use App\Models\PlatformWallet;
use App\Models\User;
use App\Models\VendorPackage;
use App\Models\VendorStore;
use App\Models\VendorSubscription;
use App\Models\WalletTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * แพ็กเกจร้านค้า — เปิดใช้แพ็กเกจเสียเงิน "หลังชำระเงินจริงเท่านั้น" (audit SELLER-06 / SELLER-08)
 *
 * กติกา:
 *  - แพ็กเกจราคาพิเศษ (is_custom_pricing หรือ Enterprise ราคา 0) → สมัครเองไม่ได้ ให้ติดต่อทีมงาน/แอดมินกำหนด
 *  - แพ็กเกจฟรี (ราคา 0 และไม่ใช่ราคาพิเศษ) → เปิดใช้ทันที
 *  - แพ็กเกจเสียเงิน → สร้าง subscription สถานะ pending (ร้านยังใช้แพ็กเกจเดิม/แพ็กเกจฟรี)
 *    → ชำระด้วยกระเป๋าเงิน (payWithWallet) → หักเงินจริง + รายได้เข้ากระเป๋า fee ของแพลตฟอร์ม
 *    → ค่อยเปลี่ยนแพ็กเกจร้าน + อัตรา GP ตามแพ็กเกจ
 *  - ชำระซ้ำ/กดซ้ำ ไม่หักเงินซ้ำ (lock แถว subscription + ตรวจรายการ wallet เดิม)
 */
class VendorSubscriptionService
{
    public const WALLET_REFERENCE_TYPE = 'vendor_subscription';

    public function __construct(protected ?WalletService $wallets = null)
    {
        $this->wallets = $wallets ?? app(WalletService::class);
    }

    /**
     * แพ็กเกจราคาพิเศษ (ต้องให้แอดมินกำหนด) หรือไม่
     */
    public function isCustomPricing(VendorPackage $package): bool
    {
        if (filter_var($package->getAttribute('is_custom_pricing'), FILTER_VALIDATE_BOOLEAN)) {
            return true;
        }

        // กันกรณีคอลัมน์ยังไม่ถูก migrate: Enterprise ราคา 0 = ราคาตามตกลง ไม่ใช่ฟรี
        return $package->package_slug === 'enterprise' && (float) $package->price <= 0;
    }

    /**
     * แพ็กเกจฟรีจริง (สมัครเองได้ ไม่ต้องจ่าย)
     */
    public function isFree(VendorPackage $package): bool
    {
        return (float) $package->price <= 0 && ! $this->isCustomPricing($package);
    }

    /**
     * ยอดที่ต้องจ่ายต่อรอบ (null = รอบนี้ไม่มีราคาให้เลือก เช่น รายปีที่ไม่ได้ตั้งราคา)
     */
    public function priceFor(VendorPackage $package, string $subscriptionType): ?float
    {
        if ($subscriptionType === 'yearly') {
            $yearly = $package->yearly_price;

            return ($yearly !== null && (float) $yearly > 0) ? round((float) $yearly, 2) : null;
        }

        return (float) $package->price > 0 ? round((float) $package->price, 2) : null;
    }

    /**
     * เปิดใช้แพ็กเกจฟรีให้ร้านทันที
     */
    public function activateFree(VendorStore $store, VendorPackage $package): VendorStore
    {
        if (! $this->isFree($package)) {
            throw new \DomainException('แพ็กเกจนี้ไม่ใช่แพ็กเกจฟรี');
        }

        $store->update([
            'package_id' => $package->id,
            'subscription_status' => 'active',
            'subscription_started_at' => now(),
            'subscription_expires_at' => null,
            'trial_ends_at' => null,
            'commission_rate' => $package->commission_rate,
        ]);

        return $store->refresh();
    }

    /**
     * สร้าง subscription รอชำระเงิน (ไม่เปลี่ยนแพ็กเกจของร้านจนกว่าจะจ่าย)
     */
    public function createPendingSubscription(VendorStore $store, VendorPackage $package, string $subscriptionType): VendorSubscription
    {
        if ($this->isCustomPricing($package)) {
            throw new \DomainException('แพ็กเกจนี้เป็นแพ็กเกจราคาพิเศษ กรุณาติดต่อทีมงานเพื่อสมัคร');
        }

        $subscriptionType = $subscriptionType === 'yearly' ? 'yearly' : 'monthly';
        $amount = $this->priceFor($package, $subscriptionType);
        if ($amount === null) {
            throw new \DomainException($subscriptionType === 'yearly'
                ? 'แพ็กเกจนี้ยังไม่เปิดให้ชำระแบบรายปี กรุณาเลือกรายเดือน'
                : 'แพ็กเกจนี้ไม่มีค่าบริการรายเดือน');
        }

        return DB::transaction(function () use ($store, $package, $subscriptionType, $amount) {
            // subscription รอชำระของแพ็กเกจ/รอบเดียวกันที่ค้างอยู่ → ใช้ตัวเดิม (กดซ้ำไม่สร้างซ้ำ)
            $existing = VendorSubscription::where('store_id', $store->id)
                ->where('package_id', $package->id)
                ->where('subscription_type', $subscriptionType)
                ->where('status', 'pending')
                ->where('payment_status', 'pending')
                ->lockForUpdate()
                ->latest('id')
                ->first();

            [$start, $end] = $this->periodFrom(now(), $subscriptionType);

            if ($existing) {
                $existing->update([
                    'amount' => $amount,
                    'currency' => $package->currency ?: 'THB',
                    'started_at' => $start,
                    'expires_at' => $end,
                ]);

                return $existing->refresh();
            }

            return VendorSubscription::create([
                'store_id' => $store->id,
                'package_id' => $package->id,
                'subscription_type' => $subscriptionType,
                'amount' => $amount,
                'currency' => $package->currency ?: 'THB',
                // วันที่คาดการณ์ (หน้าแสดงผลใช้) — ตั้งใหม่อีกครั้งตอนชำระเงินจริง
                'started_at' => $start,
                'expires_at' => $end,
                'payment_status' => 'pending',
                'status' => 'pending',
                'auto_renew' => false,
            ]);
        });
    }

    /**
     * ยอดรวมที่ต้องจ่ายของ subscription (ค่าแพ็กเกจ + ค่าแรกเข้าถ้ามีและยังไม่เคยจ่าย)
     */
    public function totalDue(VendorSubscription $subscription): float
    {
        $setupFee = (float) ($subscription->package?->setup_fee ?? 0);
        $paidBefore = $setupFee > 0 && VendorSubscription::where('store_id', $subscription->store_id)
            ->where('package_id', $subscription->package_id)
            ->where('payment_status', 'paid')
            ->where('id', '!=', $subscription->id)
            ->exists();

        return round((float) $subscription->amount + ($paidBefore ? 0.0 : $setupFee), 2);
    }

    /**
     * ชำระค่าแพ็กเกจด้วยกระเป๋าเงิน แล้วเปิดใช้แพ็กเกจ (ครั้งเดียว)
     *
     * @throws \DomainException ข้อความไทยที่แสดงผู้ใช้ได้ (ยอดไม่พอ / PIN ผิด / สถานะไม่ถูกต้อง)
     */
    public function payWithWallet(VendorSubscription $subscription, User $user, ?string $pin = null): VendorSubscription
    {
        $wallet = $this->wallets->getOrCreateWallet($user);

        // ตรวจ PIN นอก transaction เพื่อให้ตัวนับการกรอกผิดถูกบันทึกจริง
        if ($wallet->hasPIN() && ! $wallet->verifyPIN((string) $pin)) {
            $wallet->incrementFailedAttempts();
            throw new \DomainException('PIN กระเป๋าเงินไม่ถูกต้อง');
        }

        return DB::transaction(function () use ($subscription, $user, $wallet) {
            /** @var VendorSubscription|null $locked */
            $locked = VendorSubscription::whereKey($subscription->id)->lockForUpdate()->first();
            if (! $locked) {
                throw new \DomainException('ไม่พบรายการสมัครแพ็กเกจ');
            }

            $store = VendorStore::whereKey($locked->store_id)->lockForUpdate()->first();
            if (! $store || (int) $store->user_id !== (int) $user->id) {
                throw new \DomainException('ไม่พบร้านค้าของคุณ');
            }

            // จ่ายแล้ว → ไม่หักซ้ำ
            if ($locked->payment_status === 'paid') {
                return $locked;
            }

            if ($locked->status !== 'pending') {
                throw new \DomainException('รายการสมัครแพ็กเกจนี้ไม่อยู่ในสถานะรอชำระเงิน');
            }

            $package = $locked->package;
            if (! $package || ! $package->is_active || $this->isCustomPricing($package)) {
                throw new \DomainException('แพ็กเกจนี้ไม่เปิดให้ชำระเงินเองแล้ว กรุณาติดต่อทีมงาน');
            }

            $total = $this->totalDue($locked);
            if ($total <= 0) {
                throw new \DomainException('ยอดชำระไม่ถูกต้อง');
            }

            $existingTx = WalletTransaction::where('reference_type', self::WALLET_REFERENCE_TYPE)
                ->where('reference_id', $locked->id)
                ->where('user_id', $user->id)
                ->first();

            if (! $existingTx) {
                if (! $wallet->isActive()) {
                    throw new \DomainException('กระเป๋าเงินของคุณถูกระงับชั่วคราว');
                }
                if ((float) $wallet->fresh()->balance < $total) {
                    throw new \DomainException('ยอดเงินในกระเป๋าไม่พอ ต้องชำระ '.number_format($total, 2).' บาท กรุณาเติมเงินก่อน');
                }

                $existingTx = $this->wallets->deductForService(
                    $wallet,
                    $total,
                    "ค่าแพ็กเกจร้านค้า {$package->display_name} ({$locked->subscription_type})",
                    self::WALLET_REFERENCE_TYPE,
                    (int) $locked->id,
                    ['package_id' => $package->id, 'store_id' => $store->id]
                );

                // รายได้ค่าแพ็กเกจเป็นของแพลตฟอร์ม
                $income = PlatformWallet::getFeeWallet()->addFunds($total, 'vendor_subscription', 'VendorSubscription', (int) $locked->id, [
                    'store_id' => $store->id,
                    'package_id' => $package->id,
                    'wallet_transaction_id' => $existingTx->id,
                ]);
                $income->update([
                    'related_user_id' => $user->id,
                    'description' => "ค่าแพ็กเกจร้าน {$package->display_name} ร้าน #{$store->id}",
                ]);
            }

            // ต่ออายุจากวันหมดอายุเดิมถ้ายังเป็นแพ็กเกจเดียวกันและยังไม่หมด ไม่งั้นเริ่มนับวันนี้
            $base = ($store->package_id === $package->id
                && $store->subscription_status === 'active'
                && $store->subscription_expires_at
                && Carbon::parse($store->subscription_expires_at)->isFuture())
                ? Carbon::parse($store->subscription_expires_at)
                : now();
            [$start, $end] = $this->periodFrom($base, $locked->subscription_type);

            $locked->update([
                'payment_status' => 'paid',
                'paid_at' => now(),
                'payment_method' => 'wallet',
                'payment_transaction_id' => $existingTx->transaction_id,
                'status' => 'active',
                'started_at' => $start,
                'expires_at' => $end,
                'next_billing_date' => $end->toDateString(),
            ]);

            // ปิด subscription ที่ใช้งานอยู่ตัวอื่นของร้าน (เปลี่ยนแพ็กเกจ)
            VendorSubscription::where('store_id', $store->id)
                ->where('id', '!=', $locked->id)
                ->where('status', 'active')
                ->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'cancellation_reason' => 'เปลี่ยนเป็นแพ็กเกจ '.$package->display_name,
                    'auto_renew' => false,
                ]);

            $store->update([
                'package_id' => $package->id,
                'subscription_status' => 'active',
                'subscription_started_at' => $store->subscription_started_at ?? now(),
                'subscription_expires_at' => $end,
                'trial_ends_at' => null,
                'commission_rate' => $package->commission_rate,
            ]);

            Log::info('Vendor subscription paid with wallet', [
                'subscription_id' => $locked->id,
                'store_id' => $store->id,
                'package_id' => $package->id,
                'amount' => $total,
            ]);

            return $locked->refresh();
        });
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function periodFrom(Carbon $start, string $subscriptionType): array
    {
        $start = $start->copy();
        $end = $subscriptionType === 'yearly' ? $start->copy()->addYear() : $start->copy()->addMonth();

        return [$start, $end];
    }
}
