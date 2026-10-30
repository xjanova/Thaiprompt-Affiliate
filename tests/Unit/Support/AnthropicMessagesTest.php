<?php

namespace Tests\Unit\Support;

use App\Support\AnthropicMessages;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * 🤖 body ของ Anthropic Messages API ต้องตรงกติกาของแต่ละรุ่น (2026-10-30)
 *
 * ถ้าผิด = API ตอบ 400 → key ใน Pool ถูกนับว่าเสีย และลูกค้าไม่ได้คำตอบ
 */
class AnthropicMessagesTest extends TestCase
{
    private const MESSAGES = [['role' => 'user', 'content' => 'สวัสดี']];

    public static function samplingModels(): array
    {
        return [
            'haiku 4.5' => ['claude-haiku-4-5', true],
            'haiku 4.5 dated' => ['claude-haiku-4-5-20251001', true],
            'sonnet 4.6' => ['claude-sonnet-4-6', true],
            'opus 4.6' => ['claude-opus-4-6', true],
            'opus 4.7' => ['claude-opus-4-7', false],
            'opus 4.8' => ['claude-opus-4-8', false],
            'sonnet 5' => ['claude-sonnet-5', false],
            'haiku 5.5' => ['claude-haiku-5-5', false],
            'opus 5.5' => ['claude-opus-5-5', false],
            'fable 5.1' => ['claude-fable-5-1', false],
            'unknown future model' => ['claude-opus-6', false],
        ];
    }

    #[Test]
    #[DataProvider('samplingModels')]
    public function temperature_is_sent_only_to_models_that_accept_it(string $model, bool $expected): void
    {
        $body = AnthropicMessages::body($model, 'sys', self::MESSAGES, 512, 0.7);

        $this->assertSame($expected, array_key_exists('temperature', $body));
    }

    #[Test]
    public function always_thinking_models_get_low_effort_and_token_headroom(): void
    {
        foreach (['claude-opus-5-5', 'claude-fable-5-1'] as $model) {
            $body = AnthropicMessages::body($model, 'sys', self::MESSAGES, 512);

            $this->assertSame(['effort' => 'low'], $body['output_config'], $model);
            $this->assertGreaterThan(512, $body['max_tokens'], $model);
            $this->assertArrayNotHasKey('thinking', $body, $model);
        }
    }

    #[Test]
    public function sonnet_5_5_turns_thinking_off_with_between_tools(): void
    {
        $body = AnthropicMessages::body('claude-sonnet-5-5', 'sys', self::MESSAGES, 512);

        $this->assertSame(['type' => 'between_tools'], $body['thinking']);
        $this->assertSame(512, $body['max_tokens']);
    }

    #[Test]
    public function default_thinking_models_send_disabled(): void
    {
        foreach (['claude-haiku-5-5', 'claude-sonnet-5', 'claude-opus-5'] as $model) {
            $body = AnthropicMessages::body($model, 'sys', self::MESSAGES, 512);

            $this->assertSame(['type' => 'disabled'], $body['thinking'], $model);
        }
    }

    #[Test]
    public function older_models_get_no_thinking_field_and_empty_system_is_omitted(): void
    {
        $body = AnthropicMessages::body('claude-haiku-4-5', '', self::MESSAGES, 256, 0.5);

        $this->assertArrayNotHasKey('thinking', $body);
        $this->assertArrayNotHasKey('output_config', $body);
        $this->assertArrayNotHasKey('system', $body);
        $this->assertSame(0.5, $body['temperature']);
    }

    #[Test]
    public function text_skips_thinking_blocks_and_joins_text_blocks(): void
    {
        $data = ['content' => [
            ['type' => 'thinking', 'thinking' => 'คิด…'],
            ['type' => 'text', 'text' => 'ดวง'],
            ['type' => 'text', 'text' => 'ดี'],
        ]];

        $this->assertSame('ดวงดี', AnthropicMessages::text($data));
        $this->assertSame('', AnthropicMessages::text(null));
        $this->assertSame('', AnthropicMessages::text(['content' => 'oops']));
    }
}
