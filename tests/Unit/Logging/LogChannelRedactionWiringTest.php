<?php

namespace Tests\Unit\Logging;

use App\Logging\RedactSecretsProcessor;
use App\Logging\RedactSecretsTap;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\TestHandler;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * 🔐 AppServiceProvider ต้องติดตัวปิดบัง secret ให้ "ทุก" log channel จริง (2026-09-27)
 *
 * กันคนเผลอลบ redactSecretsInEveryLogChannel() หรือเพิ่ม config/logging.php ใหม่แล้วตัวผูกหลุด
 * ไม่แตะ DB — boot แอปอย่างเดียว
 */
class LogChannelRedactionWiringTest extends TestCase
{
    #[Test]
    public function every_configured_channel_has_the_redaction_tap(): void
    {
        $channels = (array) config('logging.channels');
        $this->assertNotEmpty($channels);

        foreach ($channels as $name => $channelConfig) {
            $this->assertContains(RedactSecretsTap::class, (array) ($channelConfig['tap'] ?? []), "channel [{$name}] ไม่ได้ติดตัวปิดบัง secret");
        }
    }

    #[Test]
    public function resolved_channels_scrub_tokens_end_to_end(): void
    {
        foreach (['stack', 'single', 'daily', 'null'] as $name) {
            $processors = Log::channel($name)->getLogger()->getProcessors();
            $ours = array_filter($processors, fn ($p) => $p instanceof RedactSecretsProcessor);
            $this->assertCount(1, $ours, "channel [{$name}] ต้องมี RedactSecretsProcessor หนึ่งตัวพอดี");
            $this->assertInstanceOf(RedactSecretsProcessor::class, end($processors), "channel [{$name}] ตัวปิดบังต้องรันเป็นตัวสุดท้าย (หลังเติม placeholder)");
        }

        $token = 'EAAG'.str_repeat('Rt5', 40);
        $handler = new TestHandler;
        $logger = Log::channel('null');
        $logger->getLogger()->pushHandler($handler);

        $logger->warning("listCommentsForPost exception: cURL error 28 for https://graph.facebook.com/v22.0/1_2/comments?access_token={$token}");

        $this->assertCount(1, $handler->getRecords());
        $message = $handler->getRecords()[0]->message;
        $this->assertStringNotContainsString($token, $message);
        $this->assertStringContainsString('comments?access_token=[REDACTED]', $message);
    }
}
