<?php

namespace Tests\Unit\Services;

use App\Models\FortuneTellingSetting;
use App\Services\FacebookWebhookService;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FacebookPageBlockTest extends TestCase
{
    private function service(): FacebookWebhookService
    {
        $settings = new FortuneTellingSetting;
        $settings->exists = true;
        $settings->facebook_page_id = 'page-test';
        $settings->facebook_page_token = 'test-token';

        return new FacebookWebhookService($settings);
    }

    public static function responses(): array
    {
        return [
            'success' => [['success' => true], 200, true],
            'psid' => [['123' => true], 200, true],
            'boolean' => [true, 200, true],
            'refused' => [['success' => false], 200, false],
            'psid refused' => [['123' => false], 200, false],
            'empty' => [[], 200, false],
            'unexpected' => [['data' => []], 200, false],
            'permission denied' => [['error' => ['code' => 283, 'message' => 'permission denied']], 403, false],
            'error inside 200' => [['error' => ['code' => 283]], 200, false],
        ];
    }

    #[DataProvider('responses')]
    public function test_page_block_requires_explicit_facebook_confirmation(mixed $body, int $status, bool $expected): void
    {
        Http::preventStrayRequests();
        Http::fake(['graph.facebook.com/*' => Http::response(json_encode($body), $status)]);
        $service = $this->service();

        $this->assertSame($expected, $service->blockPageUser('123'));
        Http::assertSent(fn ($r) => $r->method() === 'POST'
            && str_ends_with($r->url(), '/page-test/blocked') && $r['psid'] === '123');
        if (! $expected) {
            $this->assertNotEmpty($service->lastFetchError);
        }
    }

    #[DataProvider('responses')]
    public function test_page_unblock_requires_explicit_facebook_confirmation(mixed $body, int $status, bool $expected): void
    {
        Http::preventStrayRequests();
        Http::fake(['graph.facebook.com/*' => Http::response(json_encode($body), $status)]);

        $this->assertSame($expected, $this->service()->unblockPageUser('123'));
        Http::assertSent(fn ($r) => $r->method() === 'DELETE'
            && str_ends_with($r->url(), '/page-test/blocked') && $r['psid'] === '123');
    }
}
