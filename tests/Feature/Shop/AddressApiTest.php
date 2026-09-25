<?php

namespace Tests\Feature\Shop;

use App\Models\ShippingAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\Shop\Concerns\BuildsShopFixtures;
use Tests\TestCase;

/**
 * API ที่อยู่จัดส่งของแอป (SHOP-08) — ต้องใช้ MySQL (รันบน CI)
 */
#[Group('shop')]
class AddressApiTest extends TestCase
{
    use BuildsShopFixtures;
    use RefreshDatabase;

    public function test_create_list_update_default_and_delete_address(): void
    {
        $user = $this->makeBuyer();
        Sanctum::actingAs($user);

        $first = $this->postJson('/api/v1/addresses', [
            'recipient_name' => 'สมชาย ใจดี',
            'phone_number' => '0812345678',
            'address_line_1' => '10/1 ซอยสุขุมวิท 11',
            'district' => 'วัฒนา',
            'province' => 'กรุงเทพมหานคร',
            'postal_code' => '10110',
            'latitude' => 13.7441,
            'longitude' => 100.5559,
        ])
            ->assertCreated()
            ->assertJsonPath('data.is_default', true)
            ->assertJsonPath('data.has_location', true)
            ->assertJsonPath('data.latitude', 13.7441)
            ->json('data.id');

        $second = $this->postJson('/api/v1/addresses', [
            'recipient_name' => 'สมหญิง ใจดี',
            'phone_number' => '0899999999',
            'address_line_1' => '5 ถนนนิมมานเหมินท์',
            'province' => 'เชียงใหม่',
            'postal_code' => '50200',
        ])
            ->assertCreated()
            ->assertJsonPath('data.is_default', false)
            ->assertJsonPath('data.has_location', false)
            ->json('data.id');

        $this->getJson('/api/v1/addresses')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $first);

        $this->putJson("/api/v1/addresses/{$second}", ['latitude' => 18.7961, 'longitude' => 98.9679])
            ->assertOk()
            ->assertJsonPath('data.has_location', true);

        $this->postJson("/api/v1/addresses/{$second}/default")
            ->assertOk()
            ->assertJsonPath('data.is_default', true);
        $this->assertFalse((bool) ShippingAddress::find($first)->is_default);

        // ลบที่อยู่เริ่มต้น → อีกที่อยู่กลายเป็นค่าเริ่มต้นแทน
        $this->deleteJson("/api/v1/addresses/{$second}")->assertOk();
        $this->assertSoftDeleted('shipping_addresses', ['id' => $second]);
        $this->assertTrue((bool) ShippingAddress::find($first)->is_default);
    }

    public function test_validation_messages_are_thai(): void
    {
        Sanctum::actingAs($this->makeBuyer());

        $this->postJson('/api/v1/addresses', [
            'recipient_name' => 'ทดสอบ',
            'phone_number' => '0812345678',
            'address_line_1' => 'บ้านเลขที่ 1',
            'province' => 'กรุงเทพมหานคร',
            'postal_code' => '1011',
        ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonPath('message', 'รหัสไปรษณีย์ต้องเป็นตัวเลข 5 หลัก');
    }

    public function test_cannot_touch_another_users_address(): void
    {
        $owner = $this->makeBuyer();
        $other = $this->makeBuyer();
        $address = $this->makeAddress($owner);

        Sanctum::actingAs($other);

        $this->putJson("/api/v1/addresses/{$address->id}", ['recipient_name' => 'แฮ็ก'])->assertNotFound();
        $this->deleteJson("/api/v1/addresses/{$address->id}")->assertNotFound();
        $this->postJson("/api/v1/addresses/{$address->id}/default")->assertNotFound();
        $this->getJson('/api/v1/addresses')->assertOk()->assertJsonCount(0, 'data');

        $this->assertSame('ผู้รับ ทดสอบ', $address->fresh()->recipient_name);
    }
}
