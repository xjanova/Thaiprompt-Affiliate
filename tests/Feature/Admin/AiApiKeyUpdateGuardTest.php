<?php

namespace Tests\Feature\Admin;

use App\Http\Controllers\Admin\AiApiKeyController;
use App\Models\AiApiKey;
use App\Services\AiApiKeyPoolService;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request as PsrRequest;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * 🔐 (2026-10-30, owner) แก้ key ต้องไม่ทับ key ดีด้วยค่าที่ใช้ไม่ได้
 *
 * เหตุจริง: เบราว์เซอร์เติมรหัสผ่านลงช่อง API Key ตอนแอดมินแก้โมเดล → key #37 กับ #23 โดนทับจนยิงไม่ได้
 * และ error 401 ของค่ายทวนค่าที่กรอกกลับมาเก็บใน last_test_message
 */
class AiApiKeyUpdateGuardTest extends TestCase
{
    use RefreshDatabase;

    private const ECHO = 'ab**********yz';

    private function oldSecret(): string
    {
        return 'sk-'.str_repeat('o1', 20);
    }

    private function newSecret(): string
    {
        return 'sk-'.str_repeat('n2', 20);
    }

    private function existingKey(): AiApiKey
    {
        return AiApiKey::create([
            'name' => 'เทส', 'provider' => 'openai', 'model' => 'gpt-5.6-luna', 'api_key' => $this->oldSecret(),
            'is_active' => true, 'priority' => 50, 'purpose' => 'prediction',
        ]);
    }

    /**
     * controller ที่ไม่ยิงเน็ตจริง — จำค่าที่ถูกเทส และโยน error ตามที่สั่ง
     */
    private function controller(?\Throwable $failure = null): AiApiKeyController
    {
        return new class(app(AiApiKeyPoolService::class), $failure) extends AiApiKeyController
        {
            public ?string $testedKey = null;

            public ?string $testedModel = null;

            /** @var array<int, string>|null รายชื่อโมเดลของบัญชี — null = มีโมเดลที่ตั้งไว้เสมอ */
            public ?array $listedModels = null;

            public function __construct(AiApiKeyPoolService $pool, private ?\Throwable $failure)
            {
                parent::__construct($pool);
            }

            protected function fetchModelList(AiApiKey $key): array
            {
                $this->testedKey = $key->api_key;
                $this->testedModel = $key->model;

                if ($this->failure) {
                    throw $this->failure;
                }

                $ids = $this->listedModels ?? [(string) $key->model];

                return ['data' => array_map(fn ($id) => ['id' => $id], $ids)];
            }
        };
    }

    private function update(AiApiKeyController $controller, AiApiKey $key, array $input): JsonResponse
    {
        return $controller->update(Request::create('/', 'PUT', $input + ['name' => 'ชื่อใหม่']), $key->id);
    }

    private function httpError(int $status, string $body): ClientException
    {
        return new ClientException(
            "Client error: `GET https://api.openai.com/v1/models` resulted in a `{$status}` response:\n{$body}",
            new PsrRequest('GET', 'https://api.openai.com/v1/models'),
            new PsrResponse($status, [], $body)
        );
    }

    private function incorrectKeyError(): ClientException
    {
        return $this->httpError(401, '{"error":{"message":"Incorrect API key provided: '.self::ECHO.'."}}');
    }

    public function test_rejected_key_keeps_the_old_key_but_saves_other_fields(): void
    {
        $key = $this->existingKey();
        $controller = $this->controller($this->incorrectKeyError());

        $response = $this->update($controller, $key, ['api_key' => 'not-a-real-key', 'model' => 'gpt-6-luna']);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertTrue($response->getData(true)['api_key_rejected']);
        $this->assertStringNotContainsString(self::ECHO, $response->getContent());

        // เทสด้วยค่าที่กำลังจะบันทึก ไม่ใช่ค่าเดิม
        $this->assertSame('not-a-real-key', $controller->testedKey);
        $this->assertSame('gpt-6-luna', $controller->testedModel);

        $fresh = $key->fresh();
        $this->assertSame($this->oldSecret(), $fresh->api_key);
        $this->assertSame('gpt-6-luna', $fresh->model);
        $this->assertSame('ชื่อใหม่', $fresh->name);
        $this->assertNull($fresh->last_test_failed_at, 'key เดิมยังดีอยู่ ต้องไม่ถูกตีตราว่าเทสไม่ผ่าน');
    }

    public function test_gemini_style_invalid_key_is_rejected_too(): void
    {
        $key = $this->existingKey();

        $response = $this->update(
            $this->controller($this->httpError(400, '{"error":{"message":"API key not valid.","details":[{"reason":"API_KEY_INVALID"}]}}')),
            $key,
            ['api_key' => $this->newSecret()]
        );

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame($this->oldSecret(), $key->fresh()->api_key);
    }

    public function test_accepted_key_is_saved_and_marked_passed(): void
    {
        $key = $this->existingKey();

        $response = $this->update($this->controller(), $key, ['api_key' => $this->newSecret()]);

        $this->assertTrue($response->getData(true)['success']);
        $this->assertFalse($response->getData(true)['warning']);

        $fresh = $key->fresh();
        $this->assertSame($this->newSecret(), $fresh->api_key);
        $this->assertNotNull($fresh->last_test_passed_at);
        $this->assertNull($fresh->last_test_failed_at);
    }

    /**
     * ค่ายล่ม / key จำกัดสิทธิ์อ่าน /models ไม่ได้ ≠ key เสีย — ต้องเปลี่ยน key ได้ พร้อมคำเตือน
     */
    public function test_inconclusive_test_still_saves_the_key_with_a_warning(): void
    {
        $failures = [
            'network' => new ConnectException('cURL error 28: timed out', new PsrRequest('GET', 'https://api.openai.com/v1/models')),
            'restricted key' => $this->httpError(403, '{"error":{"message":"Missing scopes: model.read"}}'),
        ];

        foreach ($failures as $label => $failure) {
            $key = $this->existingKey();

            $response = $this->update($this->controller($failure), $key, ['api_key' => $this->newSecret()]);

            $this->assertTrue($response->getData(true)['success'], $label);
            $this->assertTrue($response->getData(true)['warning'], $label);
            $this->assertSame($this->newSecret(), $key->fresh()->api_key, $label);
            $this->assertNotNull($key->fresh()->last_test_failed_at, $label);
        }
    }

    public function test_key_is_not_tested_when_the_field_is_left_empty(): void
    {
        $key = $this->existingKey();
        $controller = $this->controller($this->incorrectKeyError());

        $response = $this->update($controller, $key, ['api_key' => '', 'model' => $key->model]);

        $this->assertTrue($response->getData(true)['success']);
        $this->assertNull($controller->testedKey);
        $this->assertSame($this->oldSecret(), $key->fresh()->api_key);
    }

    public function test_changing_only_the_model_warns_when_the_account_lacks_it(): void
    {
        $key = $this->existingKey();
        $controller = $this->controller();
        $controller->listedModels = ['gpt-5.6-luna'];

        $response = $this->update($controller, $key, ['model' => 'gpt-6-luna']);

        $this->assertTrue($response->getData(true)['success']);
        $this->assertTrue($response->getData(true)['warning']);
        $this->assertStringContainsString('gpt-6-luna', $response->getData(true)['message']);
        $this->assertSame('gpt-6-luna', $key->fresh()->model, 'เตือนเฉยๆ ไม่ขวางการบันทึก');
    }

    public function test_changing_only_the_model_ignores_a_failed_check(): void
    {
        $key = $this->existingKey();

        $response = $this->update($this->controller($this->incorrectKeyError()), $key, ['model' => 'gpt-6-luna']);

        $this->assertTrue($response->getData(true)['success']);
        $this->assertFalse($response->getData(true)['warning']);
        $this->assertSame('gpt-6-luna', $key->fresh()->model);
        $this->assertNull($key->fresh()->last_test_failed_at);
    }
}
