<?php

namespace Tests\Feature\Money;

use App\Models\VendorPackage;
use App\Models\VendorStore;
use App\Models\WalletTransaction;
use App\Services\VendorSubscriptionService;
use Illuminate\Support\Str;

/**
 * แพ็กเกจร้านค้า (audit SELLER-06 / SELLER-08)
 *
 * - Enterprise ราคา 0 = ราคาพิเศษ สมัครเองไม่ได้ (ไม่ใช่ "ฟรี")
 * - แพ็กเกจเสียเงินเปลี่ยนให้ร้านหลังหักเงินจริงเท่านั้น และกดจ่ายซ้ำไม่หักซ้ำ
 */
class SellerPackagePaymentTest extends MoneyTestCase
{
    private function package(string $slug, float $price, float $gp, array $extra = []): VendorPackage
    {
        return VendorPackage::create(array_merge([
            'package_name' => ucfirst($slug),
            'package_slug' => $slug.'-'.Str::random(4),
            'display_name' => 'แพ็กเกจ '.$slug,
            'price' => $price,
            'yearly_price' => $price > 0 ? $price * 10 : null,
            'setup_fee' => 0,
            'currency' => 'THB',
            'commission_rate' => $gp,
            'trial_days' => 0,
            'sort_order' => 1,
            'is_active' => true,
        ], $extra));
    }

    public function test_enterprise_zero_price_is_custom_pricing_not_free(): void
    {
        $enterprise = VendorPackage::create([
            'package_name' => 'Enterprise',
            'package_slug' => 'enterprise',
            'display_name' => 'แพ็คเกจองค์กร',
            'price' => 0,
            'currency' => 'THB',
            'commission_rate' => 5,
            'trial_days' => 30,
            'is_active' => true,
        ]);

        $service = app(VendorSubscriptionService::class);
        $this->assertTrue($service->isCustomPricing($enterprise));
        $this->assertFalse($service->isFree($enterprise));

        $seller = $this->makeSeller(15.0);
        $store = VendorStore::where('user_id', $seller->id)->sole();

        $this->expectException(\DomainException::class);
        $service->createPendingSubscription($store, $enterprise, 'monthly');
    }

    public function test_paid_package_activates_only_after_wallet_payment_and_charges_once(): void
    {
        $free = $this->package('free', 0, 15, ['is_default' => true]);
        $premium = $this->package('premium', 2999, 7);

        $seller = $this->makeSeller(15.0);
        $store = VendorStore::where('user_id', $seller->id)->sole();
        app(VendorSubscriptionService::class)->activateFree($store, $free);

        $service = app(VendorSubscriptionService::class);
        $subscription = $service->createPendingSubscription($store->fresh(), $premium, 'monthly');

        // ยังไม่จ่าย → ร้านยังเป็นแพ็กเกจฟรี อัตรา GP เดิม
        $this->assertSame((int) $free->id, (int) $store->fresh()->package_id);
        $this->assertSame('pending', $subscription->status);

        // เงินไม่พอ → จ่ายไม่ได้
        try {
            $service->payWithWallet($subscription, $seller);
            $this->fail('ต้องจ่ายไม่ได้เมื่อเงินไม่พอ');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('ไม่พอ', $e->getMessage());
        }

        \App\Models\Wallet::where('user_id', $seller->id)->update(['balance' => 5000]);

        $paid = $service->payWithWallet($subscription->fresh(), $seller->fresh());
        $again = $service->payWithWallet($subscription->fresh(), $seller->fresh());

        $this->assertSame('paid', $paid->payment_status);
        $this->assertSame('active', $again->status);
        $this->assertEqualsWithDelta(2001.00, $this->walletBalance($seller), 0.001);
        $this->assertSame(1, WalletTransaction::where('reference_type', VendorSubscriptionService::WALLET_REFERENCE_TYPE)
            ->where('reference_id', $subscription->id)
            ->count());

        $store->refresh();
        $this->assertSame((int) $premium->id, (int) $store->package_id);
        $this->assertSame('active', $store->subscription_status);
        $this->assertEqualsWithDelta(7.00, (float) $store->commission_rate, 0.001);
        $this->assertEqualsWithDelta(2999.00, $this->platformBalance('fee'), 0.001);
    }
}
