<?php

namespace Tests\Feature;

use App\Http\Controllers\Auth\FacebookLoginController;
use App\Http\Controllers\Auth\GoogleLoginController;
use App\Models\FacebookOAuthSetting;
use App\Models\GoogleOAuthSetting;
use App\Models\User;
use App\Services\Auth\FreshSocialOAuth;
use App\Services\TwoFactorService;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Passport\ClientRepository;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\FacebookProvider;
use Laravel\Socialite\Two\GoogleProvider;
use Mockery;
use Tests\TestCase;

/**
 * เดินผ่าน callback จริงและ Passport จริง จำลองเฉพาะ HTTP ของผู้ให้บริการกับการหาบัญชี
 *
 * @example php vendor/bin/phpunit tests/Feature/FreshSocialOAuthTest.php
 *
 * @tip ใช้ SQLite ในหน่วยความจำ ไม่รัน migration ทั้งระบบหรือแตะฐานข้อมูลผู้ใช้
 */
class FreshSocialOAuthTest extends TestCase
{
    private const CALLBACK = 'https://juntra.example/auth/thaiprompt/callback';

    private const NONCE = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

    private static ?array $keys = null;

    private $client;

    private string $secret;

    private User $selected;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([\App\Http\Middleware\SetLocale::class]);
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => false,
            'app.key' => 'base64:'.base64_encode(str_repeat('s', 32)),
            'services.facebook.client_id' => null,
        ]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        $this->app->forgetInstance('encrypter');
        Crypt::clearResolvedInstance('encrypter');
        Cache::flush();
        if (self::$keys === null) {
            $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
            openssl_pkey_export($key, $private);
            self::$keys = [$private, openssl_pkey_get_details($key)['key']];
        }
        config(['passport.private_key' => self::$keys[0], 'passport.public_key' => self::$keys[1]]);

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->timestamp('blocked_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('fortune_readings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('facebook_user_id')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->softDeletes();
        });
        foreach ([
            '2016_06_01_000001_create_oauth_auth_codes_table.php',
            '2016_06_01_000002_create_oauth_access_tokens_table.php',
            '2016_06_01_000003_create_oauth_refresh_tokens_table.php',
            '2016_06_01_000004_create_oauth_clients_table.php',
            '2026_07_17_000000_add_skip_authorization_to_oauth_clients.php',
            '2026_04_27_120000_create_facebook_oauth_settings_table.php',
            '2026_09_27_120000_create_google_oauth_settings_table.php',
            '2026_11_12_000001_add_social_binding_to_oauth_auth_codes.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        FacebookOAuthSetting::create(['app_id' => 'existing-fb-id', 'app_secret' => 'existing-fb-secret', 'is_enabled' => true]);
        GoogleOAuthSetting::create(['client_id' => 'existing-google-id', 'client_secret' => 'existing-google-secret', 'is_active' => true]);
        $this->client = app(ClientRepository::class)->create(null, 'Juntra', self::CALLBACK, 'oauth_users');
        $this->secret = $this->client->plainSecret;
        $this->client->forceFill(['skip_authorization' => true])->save();
        $this->selected = $this->user('ผู้ใช้จากผู้ให้บริการ');

        $twoFactor = Mockery::mock(TwoFactorService::class);
        $twoFactor->shouldReceive('isRequired')->andReturn(false);
        $this->app->instance(TwoFactorService::class, $twoFactor);
        foreach (['facebook' => FacebookLoginController::class, 'google' => GoogleLoginController::class] as $provider => $controller) {
            $mock = Mockery::mock($controller, $provider === 'google'
                ? [app(\App\Services\Auth\MobileAppLogin::class), app(\App\Services\Auth\SocialLoginGuard::class)] : [])
                ->makePartial()->shouldAllowMockingProtectedMethods();
            $mock->shouldReceive('findOrCreateUser')->andReturnUsing(fn () => $this->selected);
            $this->app->instance($controller, $mock);

            Socialite::extend($provider, function ($app) use ($provider) {
                $class = $provider === 'facebook' ? FacebookProvider::class : GoogleProvider::class;
                $driver = new $class($app['request'], config('services.'.$provider.'.client_id'),
                    config('services.'.$provider.'.client_secret'), config('services.'.$provider.'.redirect'));
                $handler = new MockHandler([
                    new Response(200, [], json_encode(['access_token' => 'provider-access', 'expires_in' => 3600])),
                    new Response(200, [], json_encode(['id' => 'provider-user', 'sub' => 'provider-user',
                        'name' => 'ผู้ใช้ทดสอบ', 'email' => 'selected@example.com', 'email_verified' => true])),
                ]);

                return $driver->setHttpClient(new HttpClient(['handler' => HandlerStack::create($handler)]));
            });
        }
    }

    private function user(string $name): User
    {
        $id = DB::table('users')->insertGetId(['name' => $name, 'email' => uniqid().'@example.com']);

        return User::findOrFail($id);
    }

    private function parameters(array $extra = []): array
    {
        return $extra + ['response_type' => 'code', 'client_id' => (string) $this->client->id,
            'redirect_uri' => self::CALLBACK, 'scope' => 'read profile email', 'state' => 'juntra-request-state'];
    }

    private function begin(string $provider = 'facebook', array $extra = []): string
    {
        Socialite::forgetDrivers();
        $response = $this->get('/oauth/social/authorize?'.http_build_query($this->parameters($extra)
            + ['provider' => $provider, 'social_nonce' => self::NONCE]))->assertRedirect();
        $this->assertStringContainsString($provider === 'facebook' ? 'facebook.com/' : 'accounts.google.com/', $response->headers->get('Location'));
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $providerQuery);
        $this->assertSame($provider === 'facebook' ? 'reauthenticate' : 'select_account',
            $providerQuery[$provider === 'facebook' ? 'auth_type' : 'prompt']);
        $this->assertGuest('web');

        return session('state');
    }

    private function providerCallback(string $provider, string $state, array $extra = [])
    {
        Socialite::forgetDrivers();

        return $this->get('/auth/'.$provider.'/callback?'.http_build_query($extra + ['state' => $state, 'code' => 'provider-code']));
    }

    private function code(string $provider = 'facebook', array $extra = []): string
    {
        $state = $this->begin($provider, $extra);
        $redirect = $this->providerCallback($provider, $state)->assertRedirect();
        $response = $this->get($redirect->headers->get('Location'))->assertRedirect();
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->assertSame('juntra-request-state', $query['state']);
        $this->assertNotEmpty($query['code']);

        return $query['code'];
    }

    private function exchange(string $code, array $extra = [], bool $social = true)
    {
        return $this->postJson('/oauth/token', $extra + ['grant_type' => 'authorization_code',
            'client_id' => (string) $this->client->id, 'client_secret' => $this->secret,
            'redirect_uri' => self::CALLBACK, 'code' => $code]
            + ($social ? ['social_provider' => 'facebook', 'social_nonce' => self::NONCE] : []));
    }

    public function test_discovery_advertises_only_enabled_configured_providers(): void
    {
        GoogleOAuthSetting::first()->update(['is_active' => false]);
        $this->getJson('/api/oauth/social/providers')->assertOk()->assertExactJson([
            'data' => ['flow' => 'juntra-social-v1', 'providers' => [['id' => 'facebook', 'name' => 'Facebook']]],
        ])->assertDontSee('existing-fb-secret');
        FacebookOAuthSetting::first()->update(['app_secret' => '']);
        $this->getJson('/api/oauth/social/providers')->assertJsonPath('data.providers', []);
    }

    public function test_existing_session_cannot_issue_a_code_until_the_chosen_provider_returns(): void
    {
        $old = $this->user('บัญชีค้าง');
        $this->actingAs($old);
        $this->begin();
        $this->get('/oauth/authorize?'.http_build_query($this->parameters()))->assertRedirectContains('error=access_denied');
        $this->assertSame(0, DB::table('oauth_auth_codes')->count());
        $code = $this->code();
        $this->assertAuthenticatedAs($this->selected, 'web');
        $response = $this->exchange($code)->assertOk()
            ->assertJsonPath('social_provider', 'facebook')->assertJsonPath('social_nonce', self::NONCE);
        $payload = app(FreshSocialOAuth::class)->decodeCode($code);
        $this->assertSame((string) $this->selected->id, (string) $payload['user_id']);
        $this->assertNotEmpty($response->json('access_token'));
        $this->getJson('/api/user', ['Authorization' => 'Bearer '.$response->json('access_token')])
            ->assertOk()->assertJsonPath('id', $this->selected->id)->assertJsonPath('name', $this->selected->name);
    }

    public function test_google_uses_its_real_callback_and_reuses_existing_settings(): void
    {
        $code = $this->code('google');
        $this->assertSame('existing-google-id', config('services.google.client_id'));
        $this->exchange($code, ['social_provider' => 'google'])->assertOk()->assertJsonPath('social_provider', 'google');
    }

    public function test_token_exchange_rejects_wrong_provider_wrong_nonce_and_missing_binding(): void
    {
        $code = $this->code();
        $this->exchange($code, ['social_provider' => 'google'])->assertStatus(400)->assertJsonPath('error', 'invalid_grant');
        $this->exchange($code, ['social_nonce' => str_repeat('b', 64)])->assertStatus(400)->assertJsonPath('error', 'invalid_grant');
        $this->exchange($code, [], false)->assertStatus(400)->assertJsonPath('error', 'invalid_grant');
        $this->exchange($code)->assertOk();
        $this->exchange($code)->assertStatus(400)->assertJsonPath('error', 'invalid_grant');
    }

    public function test_disabled_provider_is_rejected_before_start_and_after_code_issuance(): void
    {
        $code = $this->code();
        FacebookOAuthSetting::first()->update(['is_enabled' => false]);
        $this->exchange($code)->assertStatus(400)->assertJsonPath('error', 'invalid_grant');
        $this->getJson('/oauth/social/authorize?'.http_build_query($this->parameters()
            + ['provider' => 'facebook', 'social_nonce' => self::NONCE]))->assertStatus(422);
    }

    public function test_callback_state_provider_denial_and_expiry_fail_closed(): void
    {
        foreach (['wrong_state', 'wrong_provider', 'denied', 'expired'] as $failure) {
            $state = $this->begin();
            if ($failure === 'expired') {
                $this->travel(16)->minutes();
            }
            $this->providerCallback($failure === 'wrong_provider' ? 'google' : 'facebook',
                $failure === 'wrong_state' ? 'attacker-state' : $state,
                $failure === 'denied' ? ['error' => 'access_denied'] : [])
                ->assertRedirect(self::CALLBACK.'?error=access_denied&state=juntra-request-state');
            $this->assertGuest('web');
            $this->assertSame(0, DB::table('oauth_auth_codes')->count());
            $this->travelBack();
        }
    }

    public function test_unregistered_redirect_never_becomes_an_error_redirect_target(): void
    {
        $this->getJson('/oauth/social/authorize?'.http_build_query($this->parameters(['redirect_uri' => 'https://evil.example'])
            + ['provider' => 'facebook', 'social_nonce' => self::NONCE]))->assertStatus(401)->assertHeaderMissing('Location');
        $this->assertFalse(session()->has(FreshSocialOAuth::SESSION));
    }

    public function test_consent_approval_preserves_the_binding_and_rejects_account_swap(): void
    {
        $this->client->forceFill(['skip_authorization' => false])->save();
        $state = $this->begin();
        $redirect = $this->providerCallback('facebook', $state)->assertRedirect();
        $this->get($redirect->headers->get('Location'))->assertOk()->assertSee('auth_token');
        $authToken = session('authToken');
        $this->actingAs($this->user('บัญชีที่สลับเข้า'));
        $this->post('/oauth/authorize', ['client_id' => $this->client->id, 'state' => 'juntra-request-state', 'auth_token' => $authToken])
            ->assertRedirectContains('error=access_denied');
        $this->assertSame(0, DB::table('oauth_auth_codes')->count());

        $state = $this->begin();
        $redirect = $this->providerCallback('facebook', $state)->assertRedirect();
        $this->get($redirect->headers->get('Location'))->assertOk();
        $response = $this->post('/oauth/authorize', ['client_id' => $this->client->id,
            'state' => 'juntra-request-state', 'auth_token' => session('authToken')])->assertRedirect();
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->exchange($query['code'])->assertOk()->assertJsonPath('social_nonce', self::NONCE);
    }

    public function test_authorization_parameters_cannot_be_swapped_after_provider_callback(): void
    {
        $state = $this->begin();
        $this->providerCallback('facebook', $state)->assertRedirect();
        $this->get('/oauth/authorize?'.http_build_query($this->parameters(['state' => 'swapped-state'])))
            ->assertRedirectContains('error=access_denied');
        $this->assertSame(0, DB::table('oauth_auth_codes')->count());
    }

    public function test_ordinary_sso_still_works_and_cannot_claim_social_proof(): void
    {
        $this->actingAs($this->selected);
        $response = $this->get('/oauth/authorize?'.http_build_query($this->parameters()))->assertRedirect();
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->exchange($query['code'])->assertStatus(400)->assertJsonPath('error', 'invalid_grant');
        $this->exchange($query['code'], [], false)->assertOk()->assertJsonMissingPath('social_provider')->assertJsonMissingPath('social_nonce');
    }

    public function test_pkce_is_preserved_and_enforced_by_passport(): void
    {
        $verifier = str_repeat('v', 64);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        $code = $this->code('facebook', ['code_challenge' => $challenge, 'code_challenge_method' => 'S256']);
        $this->exchange($code, ['code_verifier' => str_repeat('w', 64)])->assertStatus(400);
        $this->exchange($code, ['code_verifier' => $verifier])->assertOk();
    }

    public function test_social_migration_can_run_twice(): void
    {
        $migration = require database_path('migrations/2026_11_12_000001_add_social_binding_to_oauth_auth_codes.php');
        $migration->up();
        $this->assertTrue(Schema::hasColumns('oauth_auth_codes', ['social_provider', 'social_nonce_hash', 'social_code_hash']));
        Auth::guard('web')->logout();
    }

    public function test_missing_or_malformed_nonce_and_unknown_provider_never_start_a_flow(): void
    {
        foreach ([['provider' => 'line', 'social_nonce' => self::NONCE],
            ['provider' => 'facebook'], ['provider' => 'facebook', 'social_nonce' => 'short'],
            ['provider' => 'facebook', 'social_nonce' => ['array']]] as $extra) {
            $this->getJson('/oauth/social/authorize?'.http_build_query($this->parameters() + $extra))->assertStatus(422);
            $this->assertFalse(session()->has(FreshSocialOAuth::SESSION));
        }
    }

    public function test_consent_requires_its_auth_token_and_can_be_denied(): void
    {
        $this->client->forceFill(['skip_authorization' => false])->save();
        $state = $this->begin();
        $redirect = $this->providerCallback('facebook', $state)->assertRedirect();
        $this->get($redirect->headers->get('Location'))->assertOk();
        $this->post('/oauth/authorize', ['client_id' => $this->client->id])->assertRedirectContains('error=access_denied');
        $this->assertSame(0, DB::table('oauth_auth_codes')->count());

        $state = $this->begin();
        $redirect = $this->providerCallback('facebook', $state)->assertRedirect();
        $this->get($redirect->headers->get('Location'))->assertOk();
        $this->delete('/oauth/authorize', ['client_id' => $this->client->id, 'auth_token' => session('authToken')])
            ->assertRedirectContains('error=access_denied');
        $this->assertSame(0, DB::table('oauth_auth_codes')->count());
        $this->assertFalse(session()->has(FreshSocialOAuth::SESSION));
    }

    public function test_a_suspended_social_account_cannot_finish_the_bridge(): void
    {
        $this->selected->forceFill(['blocked_at' => now()])->saveQuietly();
        $state = $this->begin();
        $this->providerCallback('facebook', $state)->assertRedirectContains('error=access_denied');
        $this->assertGuest('web');
        $this->assertSame(0, DB::table('oauth_auth_codes')->count());
    }

    public function test_a_callback_is_still_rejected_when_socialite_session_state_was_overwritten(): void
    {
        $state = $this->begin();
        session()->put('state', 'another-login-state');
        $this->providerCallback('facebook', $state)->assertRedirectContains('error=access_denied');
        $this->assertGuest('web');
        $this->assertSame(0, DB::table('oauth_auth_codes')->count());
    }

    public function test_ordinary_facebook_and_google_web_callbacks_keep_their_previous_destination(): void
    {
        foreach (['facebook', 'google'] as $provider) {
            Auth::guard('web')->logout();
            Socialite::forgetDrivers();
            $response = $this->get('/auth/'.$provider)->assertRedirect();
            parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $providerQuery);
            if ($provider === 'facebook') {
                $this->assertArrayNotHasKey('auth_type', $providerQuery);
            }
            $this->providerCallback($provider, session('state'))->assertRedirect(
                route($provider === 'facebook' ? 'user.wallet.index' : 'user.home'));
            $this->assertAuthenticatedAs($this->selected, 'web');
            $this->assertFalse(session()->has(FreshSocialOAuth::SESSION));
        }
    }

    public function test_social_exchange_still_requires_the_registered_client_secret_and_redirect(): void
    {
        $code = $this->code();
        $this->exchange($code, ['client_secret' => 'wrong-secret'])->assertStatus(401);
        $this->exchange($code, ['redirect_uri' => 'https://evil.example'])->assertStatus(401);
        $this->exchange($code)->assertOk();
    }

    public function test_consent_cannot_be_swapped_to_a_different_authorization_request(): void
    {
        $this->client->forceFill(['skip_authorization' => false])->save();
        $state = $this->begin();
        $redirect = $this->providerCallback('facebook', $state)->assertRedirect();
        $this->get($redirect->headers->get('Location'))->assertOk();
        session('authRequest')->setState('another-request');
        $this->post('/oauth/authorize', ['client_id' => $this->client->id, 'auth_token' => session('authToken')])
            ->assertRedirectContains('error=access_denied');
        $this->assertSame(0, DB::table('oauth_auth_codes')->count());
    }
}
