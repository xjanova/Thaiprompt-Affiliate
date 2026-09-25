<?php

namespace Tests\Feature\Platform;

use App\Models\MobileDevice;
use App\Models\Order;
use App\Models\User;
use App\Models\Wallet;
use App\Services\AccountDeletionService;
use App\Support\ContactInfo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * ลบบัญชี (PLAY-05 / CC-01) — แอป, เว็บ และแอดมิน ใช้ AccountDeletionService ตัวเดียวกัน
 *
 * ล็อกไว้:
 *   - ต้องยืนยัน "ลบบัญชี" + รหัสผ่าน (บัญชีอีเมล) ก่อนลบ
 *   - บล็อกพร้อมเหตุผลภาษาไทยเมื่อยังมีเงินในกระเป๋า / ออเดอร์ค้าง / เป็นแอดมิน
 *   - ลบ = ปกปิด PII + soft delete + เพิกถอน token + ถอด push — ไม่ลบแถวถาวร (FK cascade ของคู่ค้า)
 *   - สมัครใหม่ด้วยอีเมลเดิมได้ · ออเดอร์เก่ายังเห็นผู้ซื้อ (ชื่อที่ปกปิดแล้ว)
 *
 * ใช้ DB (MySQL บน CI)
 */
class AccountDeletionTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'secret-pass-123';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_confirmation_text_and_password_are_required(): void
    {
        [$user, $token] = $this->apiUser();

        $this->withToken($token)->deleteJson('/api/v1/account', [])
            ->assertStatus(422)
            ->assertJson(['success' => false, 'code' => 'CONFIRMATION_REQUIRED']);

        $this->withToken($token)->deleteJson('/api/v1/account', ['confirm_text' => AccountDeletionService::CONFIRM_TEXT])
            ->assertStatus(422)
            ->assertJson(['code' => 'PASSWORD_REQUIRED']);

        $this->withToken($token)->deleteJson('/api/v1/account', [
            'confirm_text' => AccountDeletionService::CONFIRM_TEXT,
            'password' => 'wrong-password',
        ])->assertStatus(422)->assertJson(['code' => 'PASSWORD_INCORRECT']);

        $this->assertNotSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_line_account_can_delete_without_password(): void
    {
        [$user, $token] = $this->apiUser(['line_user_id' => 'U'.str_repeat('a', 32)]);

        $this->withToken($token)->deleteJson('/api/v1/account', ['confirm_text' => AccountDeletionService::CONFIRM_TEXT])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertNull(DB::table('users')->where('id', $user->id)->value('line_user_id'));
    }

    public function test_wallet_balance_blocks_deletion_with_thai_reason(): void
    {
        [$user, $token] = $this->apiUser();
        Wallet::factory()->create(['user_id' => $user->id, 'balance' => 150.50]);

        $this->withToken($token)->getJson('/api/v1/account/deletion-check')
            ->assertOk()
            ->assertJsonPath('data.can_delete', false)
            ->assertJsonPath('data.blockers.0.code', 'WALLET_NOT_EMPTY')
            ->assertJsonPath('data.requires_password', true)
            ->assertJsonPath('data.confirm_text', AccountDeletionService::CONFIRM_TEXT);

        $response = $this->withToken($token)->deleteJson('/api/v1/account', [
            'confirm_text' => AccountDeletionService::CONFIRM_TEXT,
            'password' => self::PASSWORD,
        ]);

        $response->assertStatus(409)->assertJson(['success' => false, 'code' => 'WALLET_NOT_EMPTY']);
        $this->assertStringContainsString('150.50', $response->json('message'));
        $this->assertNotSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_active_order_blocks_deletion(): void
    {
        [$user, $token] = $this->apiUser();
        $this->insertOrder($user, 'processing', 'paid');

        $this->withToken($token)->deleteJson('/api/v1/account', [
            'confirm_text' => AccountDeletionService::CONFIRM_TEXT,
            'password' => self::PASSWORD,
        ])->assertStatus(409)->assertJson(['code' => 'ACTIVE_ORDERS']);
    }

    public function test_admin_account_cannot_delete_itself_through_the_app(): void
    {
        [, $token] = $this->apiUser(['role' => 'admin']);

        $this->withToken($token)->deleteJson('/api/v1/account', [
            'confirm_text' => AccountDeletionService::CONFIRM_TEXT,
            'password' => self::PASSWORD,
        ])->assertStatus(409)->assertJson(['code' => 'ADMIN_ACCOUNT']);
    }

    public function test_successful_deletion_anonymizes_revokes_and_soft_deletes(): void
    {
        [$user, $token] = $this->apiUser(['phone' => '0812345678', 'name' => 'สมหญิง ใจดี']);
        $originalEmail = $user->email;
        $orderId = $this->insertOrder($user, 'completed', 'paid');

        MobileDevice::create([
            'device_id' => 'dev-'.$user->id,
            'platform' => 'android',
            'app_version' => '1.0.0',
            'push_token' => 'ExponentPushToken[abc123]',
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        $response = $this->withToken($token)->deleteJson('/api/v1/account', [
            'confirm_text' => AccountDeletionService::CONFIRM_TEXT,
            'password' => self::PASSWORD,
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertStringStartsWith('DEL-', (string) $response->json('data.reference'));

        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $row = DB::table('users')->where('id', $user->id)->first();
        $this->assertSame(AccountDeletionService::ANONYMIZED_NAME, $row->name);
        $this->assertSame('deleted+'.$user->id.'@invalid', $row->email);
        $this->assertNull($row->phone);
        $this->assertNotNull($row->pdpa_deleted_at);

        // เพิกถอน token + ถอด push ของเครื่อง
        $this->assertSame(0, DB::table('personal_access_tokens')->where('tokenable_id', $user->id)->count());
        $this->assertNull(DB::table('mobile_devices')->where('device_id', 'dev-'.$user->id)->value('user_id'));

        // token เดิมใช้ไม่ได้แล้ว
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/me')->assertStatus(401);

        // ออเดอร์เก่ายังอยู่ และยังโหลดผู้ซื้อได้ (ชื่อที่ปกปิดแล้ว)
        $order = Order::find($orderId);
        $this->assertNotNull($order);
        $this->assertSame(AccountDeletionService::ANONYMIZED_NAME, $order->user?->name);

        // สมัครใหม่ด้วยอีเมลเดิมได้ (unique ไม่ค้าง)
        $again = User::factory()->create(['email' => $originalEmail]);
        $this->assertNotSame($user->id, $again->id);
    }

    public function test_public_page_explains_deletion_and_shows_support_email(): void
    {
        $this->get('/account/delete')
            ->assertOk()
            ->assertSee('ลบบัญชีไทยพร๊อมท์')
            ->assertSee(ContactInfo::supportEmail())
            ->assertSee(route('account.delete.login'), false);

        // ลิงก์จากนโยบายความเป็นส่วนตัว
        $this->get('/privacy-policy')->assertOk()->assertSee(route('account.delete'), false);
    }

    public function test_logged_in_page_shows_form_or_blockers(): void
    {
        $user = User::factory()->create(['password' => Hash::make(self::PASSWORD)]);

        $this->actingAs($user, 'web')->get('/account/delete')
            ->assertOk()
            ->assertSee('ลบบัญชีของฉัน')
            ->assertSee('name="confirm_text"', false);

        Wallet::factory()->create(['user_id' => $user->id, 'balance' => 20]);

        $this->actingAs($user, 'web')->get('/account/delete')
            ->assertOk()
            ->assertSee('ยังลบบัญชีไม่ได้ในตอนนี้')
            ->assertDontSee('name="confirm_text"', false);
    }

    public function test_web_form_deletes_account_and_logs_out(): void
    {
        $user = User::factory()->create(['password' => Hash::make(self::PASSWORD)]);

        $this->actingAs($user, 'web')
            ->post('/account/delete', [
                'confirm_text' => AccountDeletionService::CONFIRM_TEXT,
                'password' => self::PASSWORD,
            ])
            ->assertRedirect(route('account.delete'))
            ->assertSessionHas('account_deleted_ref');

        $this->assertGuest('web');
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_web_form_rejects_wrong_confirmation(): void
    {
        $user = User::factory()->create(['password' => Hash::make(self::PASSWORD)]);

        $this->actingAs($user, 'web')
            ->from('/account/delete')
            ->post('/account/delete', ['confirm_text' => 'ลบ', 'password' => self::PASSWORD])
            ->assertRedirect('/account/delete')
            ->assertSessionHasErrors('confirm_text');

        $this->assertNotSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_admin_delete_is_soft_and_anonymizes_instead_of_cascading(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create();
        $orderId = $this->insertOrder($target, 'completed', 'paid');

        $this->actingAs($admin, 'web')
            ->delete(route('admin.users.destroy', $target))
            ->assertRedirect(route('admin.users.index'));

        $this->assertSoftDeleted('users', ['id' => $target->id]);
        $this->assertSame('deleted+'.$target->id.'@invalid', DB::table('users')->where('id', $target->id)->value('email'));
        $this->assertTrue(DB::table('orders')->where('id', $orderId)->exists(), 'ออเดอร์ต้องไม่หายตาม (ไม่มี FK cascade)');
    }

    public function test_admin_delete_is_blocked_while_money_is_pending(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create();
        Wallet::factory()->create(['user_id' => $target->id, 'balance' => 10]);

        $this->actingAs($admin, 'web')
            ->from(route('admin.users.index'))
            ->delete(route('admin.users.destroy', $target))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('error');

        $this->assertNotSoftDeleted('users', ['id' => $target->id]);
    }

    /**
     * @return array{0: User, 1: string}
     */
    private function apiUser(array $attributes = []): array
    {
        $user = User::factory()->create($attributes + ['password' => Hash::make(self::PASSWORD)]);

        return [$user, $user->createToken('mobile-app')->plainTextToken];
    }

    private function insertOrder(User $user, string $status, string $paymentStatus): int
    {
        return (int) DB::table('orders')->insertGetId([
            'order_number' => 'T'.$user->id.'-'.uniqid(),
            'user_id' => $user->id,
            'subtotal' => 100,
            'total_amount' => 100,
            'status' => $status,
            'payment_status' => $paymentStatus,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
