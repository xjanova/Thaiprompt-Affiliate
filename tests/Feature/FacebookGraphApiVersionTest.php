<?php

namespace Tests\Feature;

use App\Exceptions\SocialLoginRefusedException;
use App\Http\Controllers\Admin\FacebookOAuthController;
use App\Http\Controllers\Auth\FacebookLoginController;
use App\Models\FacebookOAuthSetting;
use App\Models\FortuneContentPost;
use App\Models\FortuneDailyHoroscopePost;
use App\Models\FortuneHoroscopeCampaign;
use App\Models\FortuneHoroscopePost;
use App\Models\FortuneMysticPost;
use App\Services\ContentCampaignAutoPostService;
use App\Services\DailyHoroscopeAutoPostService;
use App\Services\Fortune\FortunePageContext;
use App\Services\FortuneHoroscopePublishService;
use App\Services\MysticContentAutoPostService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\FacebookProvider;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

class FacebookGraphApiVersionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'app.key' => 'base64:'.base64_encode(str_repeat('g', 32)),
        ]);
        DB::purge('sqlite');
        Cache::flush();
        FortunePageContext::forget();
        Http::preventStrayRequests();
        (require database_path('migrations/2026_04_27_120000_create_facebook_oauth_settings_table.php'))->up();
        Schema::create('fortune_telling_settings', function (Blueprint $table) {
            $table->id();
            $table->string('facebook_page_id');
            $table->string('facebook_page_token');
            $table->softDeletes();
        });
        DB::table('fortune_telling_settings')->insert(['facebook_page_id' => '123', 'facebook_page_token' => 'test-page-token']);
    }

    protected function tearDown(): void
    {
        FortunePageContext::forget();
        parent::tearDown();
    }

    public function test_migration_upgrades_existing_rows_and_database_default_and_invalidates_cache(): void
    {
        $settings = FacebookOAuthSetting::create(['graph_version' => 'v18.0', 'app_secret' => 'keep-secret']);
        Cache::put('facebook_oauth_settings', $settings, 3600);
        $migration = require database_path('migrations/2026_11_13_000001_upgrade_facebook_graph_api_version.php');
        $migration->up();
        $migration->up();

        $this->assertSame('v27.0', $settings->fresh()->graph_version);
        $this->assertSame('keep-secret', $settings->fresh()->app_secret);
        $this->assertNull(Cache::get('facebook_oauth_settings'));
        $this->assertSame('v27.0', (new FacebookOAuthSetting)->graph_version);

        $id = DB::table('facebook_oauth_settings')->insertGetId(['app_id' => 'raw-insert']);
        $this->assertSame('v27.0', DB::table('facebook_oauth_settings')->where('id', $id)->value('graph_version'));

        $migration->down();
        $this->assertSame('v27.0', $settings->fresh()->graph_version);
    }

    public function test_admin_cannot_restore_an_old_version(): void
    {
        $this->expectException(ValidationException::class);
        app(FacebookOAuthController::class)->update(Request::create('/', 'POST', ['graph_version' => 'v21.0']));
    }

    public function test_saving_other_settings_also_upgrades_an_old_version(): void
    {
        $settings = FacebookOAuthSetting::create(['graph_version' => 'v18.0']);
        app(FacebookOAuthController::class)->update(Request::create('/', 'POST', ['app_id' => 'test-app']));

        $this->assertSame('v27.0', $settings->fresh()->graph_version);
    }

    public function test_login_redirect_token_exchange_profile_and_avatar_use_v27_even_with_stale_settings(): void
    {
        FacebookOAuthSetting::create([
            'app_id' => 'test-app', 'app_secret' => 'test-secret', 'graph_version' => 'v18.0',
            'is_enabled' => true, 'redirect_uri' => 'https://example.test/auth/facebook/callback',
        ]);
        $history = [];
        $stack = HandlerStack::create(new MockHandler([
            new Response(200, [], json_encode(['access_token' => 'test-user-token'])),
            new Response(200, [], json_encode(['id' => '456', 'name' => 'Test User', 'email' => 'test@example.test'])),
        ]));
        $stack->push(Middleware::history($history));
        Socialite::extend('facebook', fn ($app) => (new FacebookProvider(
            $app['request'], config('services.facebook.client_id'), config('services.facebook.client_secret'),
            config('services.facebook.redirect')
        ))->setHttpClient(new Client(['handler' => $stack])));
        Socialite::forgetDrivers();

        $request = $this->providerRequest('/auth/facebook');
        $redirect = app(FacebookLoginController::class)->redirect($request);
        $this->assertSame('/v27.0/dialog/oauth', parse_url($redirect->getTargetUrl(), PHP_URL_PATH));
        parse_str(parse_url($redirect->getTargetUrl(), PHP_URL_QUERY), $query);

        Socialite::forgetDrivers();
        $request = $this->providerRequest('/auth/facebook/callback?'.http_build_query(['code' => 'test-code', 'state' => $query['state']]));
        $controller = Mockery::mock(FacebookLoginController::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $controller->shouldReceive('findOrCreateUser')->once()->andReturnUsing(function ($user) {
            $this->assertSame('456', $user->getId());
            $this->assertSame('https://graph.facebook.com/v27.0/456/picture?type=normal', $user->getAvatar());
            throw new SocialLoginRefusedException('test stops after fetching the profile');
        });
        $controller->callback($request);

        $this->assertCount(2, $history);
        $this->assertSame('/v27.0/oauth/access_token', $history[0]['request']->getUri()->getPath());
        $this->assertSame('/v27.0/me', $history[1]['request']->getUri()->getPath());
        parse_str($history[1]['request']->getUri()->getQuery(), $profileQuery);
        $this->assertSame('id,name,email', $profileQuery['fields']);
    }

    public static function autoPublishers(): array
    {
        $cases = [];
        foreach ([
            'daily' => [DailyHoroscopeAutoPostService::class, FortuneDailyHoroscopePost::class],
            'mystic' => [MysticContentAutoPostService::class, FortuneMysticPost::class],
            'content' => [ContentCampaignAutoPostService::class, FortuneContentPost::class],
            'horoscope' => [FortuneHoroscopePublishService::class, FortuneHoroscopePost::class],
        ] as $name => [$service, $post]) {
            foreach ([false, true] as $image) {
                $cases[$name.($image ? '-photo' : '-text')] = [$service, $post, $image];
            }
        }

        return $cases;
    }

    #[DataProvider('autoPublishers')]
    public function test_auto_publishers_send_text_and_photos_to_v27(string $serviceClass, string $postClass, bool $image): void
    {
        Http::fake(['graph.facebook.com/v27.0/*' => Http::response(['id' => '123_789', 'post_id' => '123_789'])]);
        $service = app($serviceClass);
        $post = new $postClass;
        $post->forceFill(['caption' => 'Test caption', 'post_content' => 'Test caption']);
        $post->image_url = $image ? 'https://example.test/photo.jpg' : null;
        $arguments = [$post];
        if ($serviceClass === FortuneHoroscopePublishService::class) {
            $post->image_urls = $image ? ['https://example.test/photo.jpg'] : [];
            $campaign = new FortuneHoroscopeCampaign;
            $campaign->forceFill(['use_fortune_settings_tokens' => true]);
            $arguments[] = $campaign;
        }

        $result = (new ReflectionMethod($service, 'publishToFacebook'))->invoke($service, ...$arguments);

        $this->assertSame('123_789', $result['post_id']);
        $edge = $image ? 'photos' : 'feed';
        Http::assertSent(fn ($r) => $r->method() === 'POST'
            && $r->url() === 'https://graph.facebook.com/v27.0/123/'.$edge
            && $r['access_token'] === 'test-page-token'
            && $r[$image ? 'caption' : 'message'] === 'Test caption');
    }

    #[DataProvider('deletingPublishers')]
    public function test_auto_publishers_delete_posts_through_v27(string $serviceClass): void
    {
        Http::fake(['graph.facebook.com/v27.0/*' => Http::response(['success' => true])]);
        $service = app($serviceClass);

        $this->assertTrue((new ReflectionMethod($service, 'deleteFromFacebook'))->invoke($service, '123_789'));
        Http::assertSent(fn ($r) => $r->method() === 'DELETE' && $r->url() === 'https://graph.facebook.com/v27.0/123_789');
    }

    public static function deletingPublishers(): array
    {
        return [[DailyHoroscopeAutoPostService::class], [MysticContentAutoPostService::class], [ContentCampaignAutoPostService::class]];
    }

    private function providerRequest(string $url): Request
    {
        $request = Request::create($url);
        $request->setLaravelSession(app('session.store'));
        $this->app->instance('request', $request);

        return $request;
    }
}
