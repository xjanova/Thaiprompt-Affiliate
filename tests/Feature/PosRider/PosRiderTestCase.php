<?php

namespace Tests\Feature\PosRider;

use App\Models\PosApiKey;
use App\Models\PosTerminal;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Models\VendorStore;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Shop\Concerns\BuildsShopFixtures;
use Tests\TestCase;

/**
 * ฐานของเทสต์ POS → ไรเดอร์ Thai Prompt (ต้องใช้ MySQL)
 *
 * ร้านมาตรฐาน: เปิดส่งไรเดอร์ + ปักหมุดจุดรับของ (สีลม) · เครื่อง POS ยืนยันแล้ว 1 เครื่อง
 * ลูกค้ามาตรฐาน: กระเป๋าเงิน 1,000 บาท ตั้ง PIN แล้ว
 */
abstract class PosRiderTestCase extends TestCase
{
    use BuildsShopFixtures;
    use RefreshDatabase;

    /** secret สำหรับถอด Product Key ในเทสต์ */
    protected const POS_SECRET = 'pos-test-secret-key';

    /** PIN มาตรฐานของลูกค้า */
    protected const PIN = '482915';

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
        Queue::fake();
        Notification::fake();
        Cache::flush();

        config(['pos.secret_key' => self::POS_SECRET]);

        $this->setGpRate(10);
        Setting::set('rider.max_distance_km', '15', 'float', 'rider');
        Setting::set('rider.require_deposit', '0', 'boolean', 'rider');
    }

    protected function tearDown(): void
    {
        $this->travelBack();

        parent::tearDown();
    }

    // =====================================================
    // fixtures
    // =====================================================

    /**
     * ร้านที่เปิดส่งด้วยไรเดอร์
     *
     * @return array{0: User, 1: VendorStore}
     */
    protected function makeRiderStore(array $overrides = []): array
    {
        return $this->makeSellerWithStore(array_merge(['rider_delivery_enabled' => true], $overrides));
    }

    /**
     * เครื่อง POS ที่ยืนยันแล้วของร้าน + headers สำหรับเรียก API
     *
     * Product Key = base64(XOR(json{device_id}, secret)) — รูปแบบที่ PosTerminalAuthenticator ถอดได้
     *
     * @return array{0: PosTerminal, 1: array<string, string>}
     */
    protected function makeTerminal(VendorStore $store): array
    {
        $deviceId = 'DEV-'.Str::upper(Str::random(10));
        $productKey = base64_encode($this->xor(json_encode(['device_id' => $deviceId]), self::POS_SECRET));

        $apiKey = PosApiKey::create([
            'shop_id' => $store->id,
            'name' => 'เครื่องทดสอบ',
            'is_active' => true,
            'is_blocked' => false,
        ]);

        $terminal = PosTerminal::create([
            'shop_id' => $store->id,
            'api_key_id' => $apiKey->id,
            'product_key' => $productKey,
            'device_id' => $deviceId,
            'device_name' => 'POS ทดสอบ',
            'status' => PosTerminal::STATUS_ACTIVE,
            'is_verified' => true,
            'verified_at' => now(),
        ]);

        return [$terminal->fresh(), [
            'X-API-Key' => $apiKey->key,
            'X-Product-Key' => $productKey,
            'X-Device-ID' => $deviceId,
            'Accept' => 'application/json',
        ]];
    }

    /**
     * ลูกค้าที่มีเงินในกระเป๋า + ตั้ง PIN แล้ว
     */
    protected function makeCustomer(float $balance = 1000, ?string $pin = self::PIN): User
    {
        $user = $this->makeBuyer($balance);

        if ($pin !== null) {
            app(WalletService::class)->getOrCreateWallet($user)->setPIN($pin);
        }

        return $user->fresh();
    }

    /**
     * สินค้าของร้าน (ราคา 60 สต็อก 10 ค่าเริ่มต้น)
     */
    protected function makeStoreProduct(User $seller, VendorStore $store, array $overrides = []): Product
    {
        return $this->makeProduct($seller, $store, array_merge(['price' => 60, 'stock_quantity' => 10], $overrides));
    }

    // =====================================================
    // ตัวช่วยเรียก API
    // =====================================================

    /**
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>  $payload
     */
    protected function posCreate(array $headers, array $payload): TestResponse
    {
        return $this->postJson('/api/pos/delivery-requests', $payload, $headers);
    }

    /**
     * สร้างคำขอมาตรฐานแล้วคืน token (ไม่มีคำนำหน้า)
     *
     * @param  array<string, string>  $headers
     * @param  array<int, array<string, mixed>>  $items
     * @return array{0: int, 1: string} [id, token]
     */
    protected function createRequest(array $headers, array $items, array $extra = []): array
    {
        $response = $this->posCreate($headers, array_merge([
            'local_id' => 'DL-'.Str::random(6),
            'items' => $items,
        ], $extra))->assertCreated();

        $payload = (string) $response->json('data.qr_payload');
        $this->assertStringStartsWith('TPPOS1.', $payload);

        return [(int) $response->json('data.id'), substr($payload, strlen('TPPOS1.'))];
    }

    protected function customerShow(User $user, string $token, ?int $addressId = null): TestResponse
    {
        Sanctum::actingAs($user);

        return $this->getJson('/api/v1/pos-requests/'.$token.($addressId !== null ? '?address_id='.$addressId : ''));
    }

    protected function customerPay(User $user, string $token, int $addressId, ?string $pin = self::PIN, ?string $idempotencyKey = null): TestResponse
    {
        Sanctum::actingAs($user);

        $headers = $idempotencyKey === '' ? [] : ['Idempotency-Key' => $idempotencyKey ?? (string) Str::uuid()];

        return $this->postJson('/api/v1/pos-requests/'.$token.'/pay', array_filter([
            'address_id' => $addressId,
            'pin' => $pin,
        ], fn ($v) => $v !== null), $headers);
    }

    private function xor(string $data, string $key): string
    {
        $out = '';
        for ($i = 0; $i < strlen($data); $i++) {
            $out .= chr(ord($data[$i]) ^ ord($key[$i % strlen($key)]));
        }

        return $out;
    }
}
