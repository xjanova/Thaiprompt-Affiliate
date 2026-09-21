<?php

namespace Tests\Feature;

use App\Http\Controllers\Pos\PosApiController;
use App\Models\User;
use App\Rules\NotReservedEmailDomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 🔒 (2026-09-21) /pos/* เปิดเฉพาะเจ้าของร้าน/แอดมิน + ลูกค้าหน้าร้านไม่ให้อีเมลต้องสร้างได้
 *
 * บั๊กที่เทสต์นี้ตรึง:
 *   1. กลุ่ม /pos หุ้มแค่ 'auth' → ลูกค้าทั่วไปที่ล็อกอินสร้างบัญชีผู้ใช้ใหม่ได้ (จองอีเมลคนอื่นไว้ก่อนได้)
 *      และค้น/ดึงรายชื่อ-อีเมล-เบอร์ของผู้ใช้ทั้งระบบได้ · ตอนนี้ใช้ role เดียวกับหลังบ้านร้าน /seller/*
 *   2. users.email เป็น NOT NULL (+ MySQL strict) แต่ POS รับอีเมลว่างได้ → ลูกค้าหน้าร้าน
 *      ที่ไม่ให้อีเมลได้ 500 ทุกครั้ง · ตอนนี้ได้อีเมลแทนที่สุ่ม ในโดเมนสงวนที่ผู้ใช้จองเองไม่ได้
 */
class PosCustomerApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // .env.testing มี APP_KEY ความยาวผิด — session/cookie ของเว็บต้องใช้ encrypter จริง
        config(['app.key' => 'base64:'.base64_encode(str_repeat('p', 32))]);
        $this->app->forgetInstance('encrypter');
        \Illuminate\Support\Facades\Crypt::clearResolvedInstance('encrypter');
    }

    public function test_a_plain_customer_gets_403_from_the_pos_api(): void
    {
        $customer = User::factory()->create(['role' => 'user']);
        $before = User::count();

        $this->actingAs($customer)
            ->postJson('/pos/api/customers', ['name' => 'บัญชีที่ลูกค้าแอบสร้าง', 'email' => 'victim@example.com'])
            ->assertForbidden();
        $this->actingAs($customer)
            ->getJson('/pos/api/customers/search?q=example')
            ->assertForbidden();
        $this->actingAs($customer)
            ->getJson('/pos/api/customers/updated?since=2000-01-01')
            ->assertForbidden();

        $this->assertSame($before, User::count());
        $this->assertDatabaseMissing('users', ['email' => 'victim@example.com']);
    }

    /** เปิดหน้าเว็บ POS ด้วยเบราว์เซอร์ (ไม่ใช่ API) ยังเด้งไปแดชบอร์ดของตัวเองแบบเดิม */
    public function test_a_plain_customer_opening_the_cashier_page_is_sent_to_their_own_area(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'user']))
            ->get('/pos/cashier?device_code=X')
            ->assertRedirect(route('user.home'));
    }

    public function test_a_walk_in_customer_without_email_can_be_created(): void
    {
        $response = $this->actingAs(User::factory()->create(['role' => 'seller']))
            ->postJson('/pos/api/customers', ['name' => 'ลูกค้าหน้าร้าน', 'phone' => '0812345678'])
            ->assertCreated();

        $customer = User::findOrFail($response->json('data.id'));
        $this->assertSame('ลูกค้าหน้าร้าน', $customer->name);
        $this->assertSame('0812345678', $customer->phone);
        $this->assertMatchesRegularExpression(
            '/^walkin_[a-z0-9]{20}@'.preg_quote(PosApiController::WALK_IN_EMAIL_DOMAIN, '/').'$/',
            $customer->email
        );
        // ผู้ใช้สมัคร/แก้โปรไฟล์ด้วยอีเมลแทนนี้ไม่ได้ และไม่ปนกับบัญชีสังเคราะห์ของบอท (@thaiprompt.local)
        $this->assertTrue(NotReservedEmailDomain::isReserved($customer->email));
        $this->assertStringEndsNotWith('@thaiprompt.local', $customer->email);
        $this->assertFalse((bool) $customer->bot_provisioned);

        // สองคนไม่ให้อีเมลเหมือนกัน → ไม่ชน unique
        $this->postJson('/pos/api/customers', ['name' => 'ลูกค้าคนที่สอง'])->assertCreated();
    }

    /**
     * ชื่อไทยที่ไม่มีรูป เคยทำให้แปลง User เป็น JSON ไม่ได้ (Malformed UTF-8) — profile_picture_url
     * ตัดอักษรแรกด้วย substr() ได้ไบต์ครึ่งตัว · POS สร้างลูกค้าชื่อไทยจึงได้ 500 ทุกครั้งแม้ให้อีเมล
     */
    public function test_a_thai_name_without_a_picture_serialises_to_json(): void
    {
        $user = User::factory()->make(['name' => 'สมศรี ใจดี', 'profile_picture' => null, 'line_picture_url' => null]);

        $this->assertNotFalse(json_encode($user->toArray()));
        $this->assertStringEndsWith(
            'name='.rawurlencode('ส').'&background=random&color=fff&size=200',
            $user->profile_picture_url
        );
        $this->assertStringContainsString('name=J&', User::factory()->make(['name' => 'john'])->profile_picture_url);
    }

    public function test_a_given_email_is_kept_and_admins_can_use_pos_too(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->postJson('/pos/api/customers', ['name' => 'ลูกค้ามีอีเมล', 'email' => 'shopper@example.com'])
            ->assertCreated()
            ->assertJsonPath('data.email', 'shopper@example.com');

        $this->postJson('/pos/api/customers', ['name' => 'อีเมลซ้ำ', 'email' => 'shopper@example.com'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }
}
