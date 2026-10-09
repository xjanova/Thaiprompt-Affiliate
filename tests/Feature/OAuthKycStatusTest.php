<?php

namespace Tests\Feature;

use App\Models\OAuthUser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Laravel\Passport\Client;
use Laravel\Passport\Passport;
use Laravel\Passport\Scope;
use Tests\TestCase;

/**
 * 🪪 (2026-10-09) ผล KYC สำหรับ TPIX TRADE — GET /api/oauth/kyc
 *
 * ห้ามหลุด:
 *   1. ส่งแค่สถานะ — ไม่มีเลขบัตร/ชื่อ/วันเกิด/รูปหลุดไปกับคำตอบ (PDPA)
 *   2. token ที่ไม่ได้รับ scope `kyc` (เช่นของจันทรา: read profile email) ต้องอ่านไม่ได้
 *   3. client ของ TPIX ต้องมีหน้าขออนุญาต (skip_authorization=false) — ไม่ใช่ auto-login แบบจันทรา
 *
 * ใช้ sqlite :memory: แบบเดียวกับ BuildsJuntraServerSchema — ห้ามแตะ MySQL ในเครื่อง
 */
class OAuthKycStatusTest extends TestCase
{
    /** คู่กุญแจ RSA ของ Passport — guard api-oauth ต้องมีแม้ตอน actingAs (สร้างครั้งเดียวต่อรอบเทสต์) */
    private static ?array $passportKeys = null;

    protected function setUp(): void
    {
        parent::setUp();

        if (self::$passportKeys === null) {
            $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
            openssl_pkey_export($key, $private);
            self::$passportKeys = [$private, openssl_pkey_get_details($key)['key']];
        }

        config([
            'passport.private_key' => self::$passportKeys[0],
            'passport.public_key' => self::$passportKeys[1],
        ]);

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => false,
            'app.key' => 'base64:'.base64_encode(str_repeat('k', 32)),
        ]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        $this->app->forgetInstance('encrypter');
        Crypt::clearResolvedInstance('encrypter');

        // คอลัมน์ขั้นต่ำที่เส้นนี้อ่านจริง (migration users ตัวจริงเขียนแบบ MySQL-only)
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('id_card_number')->nullable();
            $table->string('kyc_status')->nullable();
            $table->timestamp('kyc_verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();   // User ใช้ SoftDeletes
        });

        Schema::create('kyc_verifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('status');
            $table->string('method')->nullable();
            $table->timestamps();
        });

        foreach ([
            '2016_06_01_000002_create_oauth_access_tokens_table.php',
            '2016_06_01_000004_create_oauth_clients_table.php',
            '2026_07_17_000000_add_skip_authorization_to_oauth_clients.php',
        ] as $file) {
            (require database_path('migrations/'.$file))->up();
        }
    }

    private function user(array $attrs = []): OAuthUser
    {
        $id = DB::table('users')->insertGetId(array_merge([
            'name' => 'สมชาย ใจดี',
            'email' => 'somchai@example.com',
            'id_card_number' => '1101700203451',
            'kyc_status' => 'not_submitted',
            'created_at' => now(),
            'updated_at' => now(),
        ], $attrs));

        return OAuthUser::query()->findOrFail($id);
    }

    public function test_a_verified_customer_is_reported_as_approved_with_the_date_and_method(): void
    {
        $user = $this->user(['kyc_status' => 'approved', 'kyc_verified_at' => '2026-10-05 10:00:00']);
        DB::table('kyc_verifications')->insert(['user_id' => $user->id, 'status' => 'approved', 'method' => 'ekyc']);
        Passport::actingAs($user, ['kyc'], 'api-oauth');

        $response = $this->getJson('/api/oauth/kyc')->assertOk();

        $response->assertExactJson([
            'sub' => (string) $user->id,
            'kyc_status' => 'approved',
            'verified' => true,
            'verified_at' => $response->json('verified_at'),
            'method' => 'ekyc',
        ]);
        $this->assertStringStartsWith('2026-10-05', $response->json('verified_at'));
    }

    public function test_the_answer_never_carries_identity_data(): void
    {
        $user = $this->user(['kyc_status' => 'approved', 'kyc_verified_at' => now()]);
        Passport::actingAs($user, ['kyc'], 'api-oauth');

        $body = $this->getJson('/api/oauth/kyc')->assertOk()->getContent();

        foreach (['1101700203451', 'สมชาย', 'somchai@example.com', 'id_card', 'email', 'name'] as $secret) {
            $this->assertStringNotContainsString($secret, $body);
        }
    }

    public function test_a_customer_still_in_review_is_pending_and_not_verified(): void
    {
        Passport::actingAs($this->user(['kyc_status' => 'pending']), ['kyc'], 'api-oauth');

        $this->getJson('/api/oauth/kyc')
            ->assertOk()
            ->assertJson(['kyc_status' => 'pending', 'verified' => false, 'verified_at' => null, 'method' => null]);
    }

    public function test_a_customer_who_never_submitted_is_none(): void
    {
        Passport::actingAs($this->user(), ['kyc'], 'api-oauth');

        $this->getJson('/api/oauth/kyc')->assertOk()->assertJson(['kyc_status' => 'none', 'verified' => false]);
    }

    public function test_a_token_without_the_kyc_scope_cannot_read_it(): void
    {
        // token ของจันทราได้ read profile email — ต้องไม่ได้ผล KYC ติดมาด้วย
        Passport::actingAs($this->user(['kyc_status' => 'approved']), ['read', 'profile', 'email'], 'api-oauth');

        $this->getJson('/api/oauth/kyc')->assertForbidden();
    }

    public function test_a_guest_is_rejected(): void
    {
        $this->getJson('/api/oauth/kyc')->assertUnauthorized();
    }

    public function test_the_provision_command_creates_a_client_that_asks_for_consent(): void
    {
        $before = glob(storage_path('app/private/tpix-trade-oauth-*.json')) ?: [];

        $this->artisan('oauth:provision-tpix-trade-client', [
            '--redirect' => 'https://trade.example.com/kyc/thaiprompt/callback',
        ])->assertSuccessful();

        $client = Client::query()->latest('id')->firstOrFail();
        $this->assertSame('TPIX TRADE', $client->name);
        $this->assertFalse((bool) $client->skip_authorization);
        $this->assertSame('https://trade.example.com/kyc/thaiprompt/callback', $client->redirect);

        // ไฟล์ secret ที่คำสั่งเขียน — ลบทิ้งหลังเทสต์ ไม่ให้ค้างในเครื่อง
        foreach (array_diff(glob(storage_path('app/private/tpix-trade-oauth-*.json')) ?: [], $before) as $file) {
            File::delete($file);
        }
    }

    public function test_the_provision_command_refuses_a_redirect_that_is_not_the_tpix_callback(): void
    {
        $this->artisan('oauth:provision-tpix-trade-client', ['--redirect' => 'http://evil.example/steal'])
            ->assertFailed();

        $this->assertSame(0, Client::query()->count());
    }

    public function test_the_consent_page_is_thai_and_posts_back_to_passport(): void
    {
        $client = new Client(['name' => 'TPIX TRADE']);
        $client->id = 7;

        $html = view('passport::authorize', [
            'client' => $client,
            'user' => $this->user(),
            'scopes' => [new Scope('kyc', Passport::scopes()->firstWhere('id', 'kyc')->description)],
            'request' => Request::create('/oauth/authorize', 'GET', ['state' => 'abc123']),
            'authToken' => 'tok-1',
        ])->render();

        $this->assertStringContainsString('TPIX TRADE', $html);
        $this->assertStringContainsString('อนุญาต', $html);
        $this->assertStringContainsString('ไม่รวมเลขบัตร', $html);
        $this->assertStringContainsString(route('passport.authorizations.approve'), $html);
        $this->assertStringContainsString('value="abc123"', $html);
        $this->assertStringContainsString('value="tok-1"', $html);
    }
}
