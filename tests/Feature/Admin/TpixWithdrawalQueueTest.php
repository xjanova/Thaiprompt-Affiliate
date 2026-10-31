<?php

namespace Tests\Feature\Admin;

use App\Models\CryptoCurrency;
use App\Models\CryptoTransaction;
use App\Models\CryptoWallet;
use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * คิวถอน TPIX ฝั่งแอดมิน: ยืนยันว่าส่งแล้ว ปฏิเสธพร้อมคืนยอด และกันการคืนซ้ำ
 */
class TpixWithdrawalQueueTest extends TestCase
{
    use RefreshDatabase;

    private const ADDRESS = '0x1234567890abcdef1234567890abcdef12345678';

    private const HASH = '0xaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    private User $admin;

    private User $member;

    private CryptoCurrency $currency;

    private CryptoWallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin', 'name' => 'แอดมินคิว']);
        $this->member = User::factory()->create(['role' => 'user', 'name' => 'สมาชิกถอน']);
        $this->currency = $this->makeCurrency('TPIX');
        $this->wallet = $this->makeWallet($this->member, $this->currency, '10.000000000000000001');
    }

    private function makeCurrency(string $code): CryptoCurrency
    {
        return CryptoCurrency::create([
            'code' => $code,
            'name' => $code,
            'symbol' => $code,
            'network' => strtolower($code),
            'decimals' => 18,
            'min_withdrawal' => 0.1,
            'max_withdrawal' => 1000,
            'withdrawal_fee' => 0.01,
            'is_active' => true,
            'withdrawal_enabled' => true,
        ]);
    }

    private function makeWallet(User $user, CryptoCurrency $currency, string $balance): CryptoWallet
    {
        return CryptoWallet::create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'wallet_type' => 'custodial',
            'balance' => $balance,
            'status' => 'active',
        ]);
    }

    private function makeWithdrawal(array $overrides = []): CryptoTransaction
    {
        return CryptoTransaction::create(array_merge([
            'user_id' => $this->member->id,
            'crypto_currency_id' => $this->currency->id,
            'crypto_wallet_id' => $this->wallet->id,
            'type' => 'withdrawal',
            'amount' => '2.500000000000000001',
            'fee' => '0.010000000000000000',
            'to_address' => self::ADDRESS,
            'status' => 'pending',
            'network' => 'tpix',
        ], $overrides));
    }

    private function confirm(CryptoTransaction $withdrawal, ?string $hash = self::HASH): TestResponse
    {
        return $this->actingAs($this->admin)
            ->from(route('admin.tpix.withdrawals.index'))
            ->post(route('admin.tpix.withdrawals.confirm', $withdrawal->id), ['tx_hash' => $hash]);
    }

    private function reject(CryptoTransaction $withdrawal, ?string $reason = 'ที่อยู่ปลายทางไม่ถูกต้อง'): TestResponse
    {
        return $this->actingAs($this->admin)
            ->from(route('admin.tpix.withdrawals.index'))
            ->post(route('admin.tpix.withdrawals.reject', $withdrawal->id), ['reason' => $reason]);
    }

    private function balance(): string
    {
        return (string) CryptoWallet::withTrashed()->whereKey($this->wallet->id)->value('balance');
    }

    public function test_queue_lists_pending_oldest_first_and_says_it_does_not_send_coins(): void
    {
        $newer = $this->makeWithdrawal(['to_address' => '0x2222222222222222222222222222222222222222']);
        $older = $this->makeWithdrawal(['to_address' => '0x1111111111111111111111111111111111111111']);
        CryptoTransaction::whereKey($older->id)->update(['created_at' => now()->subDay()]);

        $this->actingAs($this->admin)
            ->get(route('admin.tpix.withdrawals.index'))
            ->assertOk()
            ->assertSee('หน้านี้ไม่ได้ส่งเหรียญให้')
            ->assertSee('สมาชิกถอน')
            ->assertSeeInOrder([$older->to_address, $newer->to_address])
            ->assertSee('5.000000000000000002 TPIX')
            ->assertSee('ถ้าปฏิเสธคืน 2.510000000000000001');
    }

    public function test_queue_opens_when_empty(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.tpix.withdrawals.index'))
            ->assertOk()
            ->assertSee('ไม่มีรายการรอดำเนินการ')
            ->assertSee('ยังไม่มีประวัติ');
    }

    public function test_queue_hides_rows_that_are_not_tpix_withdrawals(): void
    {
        $this->makeWithdrawal(['type' => 'deposit', 'to_address' => '0xdeposit']);
        $this->makeWithdrawal(['crypto_currency_id' => $this->makeCurrency('USDT')->id, 'to_address' => '0xusdt']);
        $this->makeWithdrawal(['network' => 'bsc', 'to_address' => '0xbsc']);

        $this->actingAs($this->admin)
            ->get(route('admin.tpix.withdrawals.index'))
            ->assertOk()
            ->assertDontSee('0xdeposit')
            ->assertDontSee('0xusdt')
            ->assertDontSee('0xbsc');
    }

    public function test_members_and_guests_cannot_reach_the_queue(): void
    {
        $withdrawal = $this->makeWithdrawal();

        $this->get(route('admin.tpix.withdrawals.index'))->assertRedirect();

        // middleware role ส่งสมาชิกกลับหน้าของตัวเอง ไม่ได้ตอบ 403
        $this->actingAs($this->member)
            ->get(route('admin.tpix.withdrawals.index'))
            ->assertRedirect()
            ->assertDontSee(self::ADDRESS);
        $this->actingAs($this->member)
            ->post(route('admin.tpix.withdrawals.reject', $withdrawal->id), ['reason' => 'ขอคืนเอง'])
            ->assertRedirect();
        $this->actingAs($this->member)
            ->post(route('admin.tpix.withdrawals.confirm', $withdrawal->id), ['tx_hash' => self::HASH])
            ->assertRedirect();

        $this->assertSame('pending', $withdrawal->fresh()->status);
        $this->assertSame('10.000000000000000001', $this->balance());
    }

    public function test_confirm_records_the_hash_and_reviewer_without_touching_the_balance(): void
    {
        $withdrawal = $this->makeWithdrawal();

        $this->confirm($withdrawal, '0xABCDEF'.Str::repeat('a', 58))
            ->assertRedirect(route('admin.tpix.withdrawals.index'))
            ->assertSessionHas('success', 'บันทึกการส่งเหรียญแล้ว');

        $fresh = $withdrawal->fresh();
        $this->assertSame('confirmed', $fresh->status);
        $this->assertSame('0xabcdef'.Str::repeat('a', 58), $fresh->tx_hash);
        $this->assertNotNull($fresh->confirmed_at);
        $this->assertNotNull($fresh->completed_at);
        $this->assertNotNull($fresh->reviewed_at);
        $this->assertSame($this->admin->id, (int) $fresh->reviewed_by);
        $this->assertSame('10.000000000000000001', $this->balance());
    }

    public function test_confirm_refuses_a_malformed_hash_with_a_thai_message(): void
    {
        $withdrawal = $this->makeWithdrawal();

        foreach ([null, 'abc', '0x123', self::HASH.'a', str_replace('a', 'z', self::HASH)] as $hash) {
            $this->confirm($withdrawal, $hash)
                ->assertRedirect(route('admin.tpix.withdrawals.index'))
                ->assertSessionHas('error');
        }

        $this->assertSame('pending', $withdrawal->fresh()->status);
        $this->assertNull($withdrawal->fresh()->tx_hash);
    }

    public function test_confirm_refuses_a_hash_already_used_by_another_row(): void
    {
        $used = $this->makeWithdrawal(['status' => 'confirmed', 'tx_hash' => self::HASH]);
        $withdrawal = $this->makeWithdrawal();

        $this->confirm($withdrawal)->assertSessionHas('error', 'tx hash นี้ถูกบันทึกในรายการอื่นแล้ว');

        // ตัวพิมพ์ต่างกันก็ยังเป็น hash เดียวกัน
        $this->confirm($withdrawal, '0x'.strtoupper(substr(self::HASH, 2)))
            ->assertSessionHas('error', 'tx hash นี้ถูกบันทึกในรายการอื่นแล้ว');

        // แถวที่ลบแบบ soft delete ยังติด unique index อยู่
        $used->delete();
        $this->confirm($withdrawal)->assertSessionHas('error', 'tx hash นี้ถูกบันทึกในรายการอื่นแล้ว');

        $this->assertSame('pending', $withdrawal->fresh()->status);
    }

    public function test_two_admins_saving_the_same_hash_at_once_get_a_thai_message(): void
    {
        $withdrawal = $this->makeWithdrawal();

        // จำลองอีกคนบันทึก hash นี้ลงไปหลังเช็คซ้ำผ่านแล้ว แต่ก่อนแถวนี้จะถูกเขียน
        CryptoTransaction::updating(function (CryptoTransaction $row) {
            if ($row->tx_hash === self::HASH && ! CryptoTransaction::where('tx_hash', self::HASH)->exists()) {
                $this->makeWithdrawal(['status' => 'confirmed', 'tx_hash' => self::HASH]);
            }
        });

        $this->confirm($withdrawal)->assertSessionHas('error', 'tx hash นี้ถูกบันทึกในรายการอื่นแล้ว');

        $this->assertSame('pending', $withdrawal->fresh()->status);
    }

    public function test_reject_refunds_amount_plus_fee_exactly_and_saves_the_reason(): void
    {
        $withdrawal = $this->makeWithdrawal();

        $this->reject($withdrawal)
            ->assertRedirect(route('admin.tpix.withdrawals.index'))
            ->assertSessionHas('success', 'ปฏิเสธรายการและคืนยอดแล้ว');

        $fresh = $withdrawal->fresh();
        $this->assertSame('cancelled', $fresh->status);
        $this->assertSame('ที่อยู่ปลายทางไม่ถูกต้อง', $fresh->error_message);
        $this->assertSame($this->admin->id, (int) $fresh->reviewed_by);
        $this->assertNotNull($fresh->reviewed_at);
        $this->assertNull($fresh->tx_hash);
        $this->assertSame('12.510000000000000002', $this->balance());
    }

    public function test_pressing_reject_twice_refunds_once(): void
    {
        $withdrawal = $this->makeWithdrawal();

        $this->reject($withdrawal)->assertSessionHas('success');
        $this->reject($withdrawal, 'กดซ้ำ')->assertSessionHas('error', 'รายการนี้ดำเนินการไปแล้ว');

        $this->assertSame('12.510000000000000002', $this->balance());
        $this->assertSame('ที่อยู่ปลายทางไม่ถูกต้อง', $withdrawal->fresh()->error_message);
    }

    public function test_a_closed_row_cannot_change_outcome(): void
    {
        $sent = $this->makeWithdrawal();
        $this->confirm($sent)->assertSessionHas('success');
        $this->reject($sent)->assertSessionHas('error', 'รายการนี้ดำเนินการไปแล้ว');
        $this->assertSame('confirmed', $sent->fresh()->status);
        $this->assertSame('10.000000000000000001', $this->balance());

        $refused = $this->makeWithdrawal();
        $this->reject($refused)->assertSessionHas('success');
        $this->confirm($refused, str_replace('a', 'b', self::HASH))->assertSessionHas('error', 'รายการนี้ดำเนินการไปแล้ว');
        $this->assertSame('cancelled', $refused->fresh()->status);
        $this->assertNull($refused->fresh()->tx_hash);
    }

    public function test_actions_refuse_rows_outside_the_tpix_queue(): void
    {
        $rows = [
            $this->makeWithdrawal(['type' => 'deposit']),
            $this->makeWithdrawal(['crypto_currency_id' => $this->makeCurrency('USDT')->id]),
            $this->makeWithdrawal(['network' => 'bsc']),
        ];

        foreach ($rows as $row) {
            $this->reject($row)->assertSessionHas('error', 'ไม่พบรายการถอนนี้');
            $this->confirm($row)->assertSessionHas('error', 'ไม่พบรายการถอนนี้');
            $this->assertSame('pending', $row->fresh()->status);
        }

        $this->assertSame('10.000000000000000001', $this->balance());
    }

    public function test_reject_needs_a_reason_and_keeps_the_typed_value_on_that_row_only(): void
    {
        $first = $this->makeWithdrawal();
        $second = $this->makeWithdrawal();

        $this->reject($first, '')->assertSessionHas('error', 'กรุณากรอกเหตุผลที่ปฏิเสธ');
        $this->reject($first, Str::repeat('ก', 501))->assertSessionHas('error', 'เหตุผลยาวได้ไม่เกิน 500 ตัวอักษร');
        $this->assertSame('pending', $first->fresh()->status);

        $bad = 'ค่าที่พิมพ์ค้างไว้';
        $this->confirm($second, $bad)->assertSessionHas('tpix_withdrawal_row', $second->id);

        $html = $this->actingAs($this->admin)->get(route('admin.tpix.withdrawals.index'))->getContent();
        $this->assertSame(1, substr_count($html, 'value="'.$bad.'"'));
    }

    public function test_refund_reaches_a_suspended_or_deleted_wallet(): void
    {
        $withdrawal = $this->makeWithdrawal();
        $this->wallet->update(['status' => 'suspended']);
        $this->wallet->delete();

        $this->reject($withdrawal)->assertSessionHas('success');

        $this->assertSame('12.510000000000000002', $this->balance());
    }

    public function test_member_is_told_the_outcome_and_sees_the_reason(): void
    {
        $refused = $this->makeWithdrawal();
        $sent = $this->makeWithdrawal();

        $this->reject($refused, 'ที่อยู่อยู่ผิดเครือข่าย');
        $this->confirm($sent);

        $this->assertSame(2, Notification::where('user_id', $this->member->id)->where('type', 'withdrawal')->count());
        $this->assertTrue(Notification::where('user_id', $this->member->id)->where('message', 'like', '%ที่อยู่อยู่ผิดเครือข่าย%')->exists());

        $this->actingAs($this->member)
            ->get(route('user.tpix.withdrawal'))
            ->assertOk()
            ->assertSee('ระบบคืนยอดแล้ว · เหตุผล: ที่อยู่อยู่ผิดเครือข่าย')
            ->assertSee(self::HASH);
    }

    public function test_failed_notification_does_not_undo_the_outcome(): void
    {
        $this->mock(NotificationService::class, function ($mock) {
            $mock->shouldReceive('create')->andThrow(new Exception('แจ้งเตือนล่ม'));
        });

        $sent = $this->makeWithdrawal();
        $refused = $this->makeWithdrawal();

        $this->confirm($sent)->assertSessionHas('success');
        $this->reject($refused)->assertSessionHas('success');

        $this->assertSame('confirmed', $sent->fresh()->status);
        $this->assertSame('cancelled', $refused->fresh()->status);
        $this->assertSame('12.510000000000000002', $this->balance());
    }

    public function test_history_shows_who_closed_each_row(): void
    {
        $sent = $this->makeWithdrawal();
        $refused = $this->makeWithdrawal();
        $this->confirm($sent);
        $this->reject($refused, 'เหตุผลในประวัติ');

        $this->actingAs($this->admin)
            ->get(route('admin.tpix.withdrawals.index'))
            ->assertOk()
            ->assertSee(self::HASH)
            ->assertSee('เหตุผลในประวัติ')
            ->assertSee('โดย แอดมินคิว')
            ->assertSee('ไม่มีรายการรอดำเนินการ');
    }

    public function test_new_withdrawal_alerts_admins_and_enters_the_queue(): void
    {
        $this->actingAs($this->member)
            ->post(route('user.tpix.withdrawal.process'), ['address' => self::ADDRESS, 'amount' => '1.5'])
            ->assertRedirect(route('user.tpix.wallet'));

        $this->assertTrue(Notification::where('user_id', $this->admin->id)->where('title', 'คำขอถอน TPIX ใหม่')->exists());

        $this->actingAs($this->admin)
            ->get(route('admin.tpix.withdrawals.index'))
            ->assertOk()
            ->assertSee(self::ADDRESS);
    }

    public function test_failed_admin_alert_does_not_fail_the_withdrawal(): void
    {
        $this->mock(NotificationService::class, function ($mock) {
            $mock->shouldReceive('notifyAdminTpixWithdrawal')->andThrow(new Exception('แจ้งเตือนล่ม'));
        });

        $this->actingAs($this->member)
            ->post(route('user.tpix.withdrawal.process'), ['address' => self::ADDRESS, 'amount' => '1.5'])
            ->assertRedirect(route('user.tpix.wallet'))
            ->assertSessionHas('success');

        $this->assertSame(1, CryptoTransaction::where('type', 'withdrawal')->count());
    }

    public function test_menu_points_at_the_queue(): void
    {
        $items = collect(config('menus.admin'))->firstWhere('id', 'wallet-crypto')['submenu'] ?? [];

        $this->assertContains('admin.tpix.withdrawals.index', array_column($items, 'route'));
    }
}
