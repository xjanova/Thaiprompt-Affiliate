<?php

namespace Tests\Feature\Money;

use App\Services\OrderDistributionService;
use App\Services\WalletService;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * เทสต์ที่ไม่แตะฐานข้อมูล (รันบนเครื่อง dev ได้)
 *
 * - การแบ่งค่าส่งตามน้ำหนัก: ผลรวมต้องเท่าค่าส่งพอดีทุกกรณี (ปัดสตางค์แล้วไม่หาย/ไม่เกิน)
 * - route ถอนเงิน/บัญชีรับเงินของแอป ลงทะเบียนครบ ชี้ไปเมธอดที่มีจริง และมี auth:sanctum + throttle
 * - WalletService รองรับประเภทรายการที่ตรงกับ enum wallet_transactions.type เท่านั้น
 */
class MoneyWiringTest extends TestCase
{
    public function test_shipping_is_split_by_weight_and_sums_exactly(): void
    {
        $shares = OrderDistributionService::allocateShippingShares(100.0, [10 => 1.0, 20 => 1.0, 30 => 1.0]);

        $this->assertEqualsWithDelta(100.00, array_sum($shares), 0.0001);
        $this->assertEqualsWithDelta(33.33, $shares[10], 0.0001);
        $this->assertEqualsWithDelta(33.33, $shares[20], 0.0001);
        $this->assertEqualsWithDelta(33.34, $shares[30], 0.0001);
    }

    public function test_shipping_goes_only_to_sellers_with_weight(): void
    {
        $shares = OrderDistributionService::allocateShippingShares(60.0, [1 => 0.0, 2 => 40.0, 3 => 20.0]);

        $this->assertSame(0.0, $shares[1]);
        $this->assertEqualsWithDelta(40.00, $shares[2], 0.0001);
        $this->assertEqualsWithDelta(20.00, $shares[3], 0.0001);
    }

    public function test_single_seller_gets_all_shipping_and_zero_fee_gives_zero(): void
    {
        $this->assertSame([7 => 45.5], OrderDistributionService::allocateShippingShares(45.5, [7 => 123.0]));
        $this->assertSame([7 => 0.0, 8 => 0.0], OrderDistributionService::allocateShippingShares(0.0, [7 => 1.0, 8 => 2.0]));
    }

    public function test_all_zero_weights_split_evenly(): void
    {
        $shares = OrderDistributionService::allocateShippingShares(10.0, [4 => 0.0, 5 => 0.0]);

        $this->assertEqualsWithDelta(10.00, array_sum($shares), 0.0001);
        $this->assertEqualsWithDelta(5.00, $shares[4], 0.0001);
    }

    public function test_odd_cents_never_leak(): void
    {
        foreach ([0.01, 0.99, 13.37, 99.99, 1234.57] as $fee) {
            $shares = OrderDistributionService::allocateShippingShares($fee, [1 => 3.0, 2 => 7.0, 3 => 11.0]);
            $this->assertEqualsWithDelta($fee, array_sum($shares), 0.00001, "fee {$fee}");
            foreach ($shares as $share) {
                $this->assertGreaterThanOrEqual(0.0, $share);
            }
        }
    }

    public function test_wallet_api_routes_exist_and_point_to_real_methods(): void
    {
        $expected = [
            'api.v1.wallet.withdraw.info' => ['GET', 'info'],
            'api.v1.wallet.withdraw.preview' => ['GET', 'preview'],
            'api.v1.wallet.withdraw' => ['POST', 'withdraw'],
            'api.v1.wallet.withdrawals' => ['GET', 'history'],
            'api.v1.wallet.withdrawals.cancel' => ['POST', 'cancel'],
            'api.v1.wallet.bank-accounts' => ['GET', 'bankAccounts'],
            'api.v1.wallet.bank-accounts.store' => ['POST', 'storeBankAccount'],
            'api.v1.wallet.bank-accounts.destroy' => ['DELETE', 'destroyBankAccount'],
            'api.v1.wallet.bank-accounts.delete' => ['POST', 'destroyBankAccount'],
            'api.v1.wallet.bank-accounts.default' => ['POST', 'setDefaultBankAccount'],
            'api.v1.wallet.pin' => ['POST', 'setPin'],
        ];

        foreach ($expected as $name => [$method, $action]) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route, "route {$name} ต้องมีอยู่");
            $this->assertContains($method, $route->methods(), "route {$name} ต้องเป็น {$method}");
            $this->assertSame(
                \App\Http\Controllers\Api\V1\WalletWithdrawalApiController::class.'@'.$action,
                $route->getActionName()
            );
            $this->assertTrue(method_exists(\App\Http\Controllers\Api\V1\WalletWithdrawalApiController::class, $action));
            $this->assertContains('auth:sanctum', $route->gatherMiddleware(), "route {$name} ต้องล็อกอิน");
        }

        $withdraw = Route::getRoutes()->getByName('api.v1.wallet.withdraw');
        $this->assertContains('throttle:5,1,api-withdraw', $withdraw->gatherMiddleware());
        $this->assertSame('api/v1/wallet/withdraw', $withdraw->uri());
    }

    public function test_seller_money_routes_point_to_existing_methods(): void
    {
        foreach ([
            'seller.wallet.withdraw.submit' => 'submitWithdrawal',
            'seller.wallet.withdrawal.cancel' => 'cancelWithdrawal',
            'seller.wallet.index' => 'walletIndex',
        ] as $name => $method) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route, "route {$name} ต้องมีอยู่");
            $this->assertTrue(method_exists(\App\Http\Controllers\Seller\DashboardController::class, $method));
        }

        foreach (['seller.packages.payment' => 'payment', 'seller.packages.process-payment' => 'processPayment', 'seller.packages.subscribe' => 'subscribe'] as $name => $method) {
            $this->assertNotNull(Route::getRoutes()->getByName($name));
            $this->assertTrue(method_exists(\App\Http\Controllers\Seller\PackageController::class, $method));
        }

        $this->assertNotNull(Route::getRoutes()->getByName('admin.ecommerce.orders.payment-status.update'));
    }

    public function test_wallet_service_types_match_enum(): void
    {
        $enum = ['deposit', 'withdrawal', 'transfer_in', 'transfer_out', 'commission', 'refund', 'fee', 'bonus'];

        foreach (array_merge(WalletService::CREDIT_TYPES, WalletService::DEBIT_TYPES) as $type) {
            $this->assertContains($type, $enum);
        }
    }
}
