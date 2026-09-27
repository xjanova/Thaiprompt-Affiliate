<?php

namespace Tests\Unit\Logging;

use App\Logging\RedactSecretsProcessor;
use App\Logging\RedactSecretsTap;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Log\Logger;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\TestHandler;
use Monolog\Logger as Monolog;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * 🔐 ตาข่ายชั้น Monolog: ทุก log record ต้องไม่มี token เหลือ ไม่ว่า catch block จะลืมครอบ SafeLog หรือไม่ (2026-09-27)
 *
 * เทสต์ล้วน ไม่ boot แอป — ประกอบ Monolog + tap เองแล้ว log ของจริงผ่านมัน
 */
class RedactSecretsProcessorTest extends TestCase
{
    private static function pageToken(): string
    {
        return 'EAAG'.str_repeat('Qw7', 40);
    }

    /** @return array{0: Monolog, 1: TestHandler} */
    private function tappedLogger(): array
    {
        $handler = new TestHandler;
        $monolog = new Monolog('testing', [$handler]);
        (new RedactSecretsTap)(new Logger($monolog));

        return [$monolog, $handler];
    }

    /**
     * เคสจริงบน prod: Laravel ConnectionException ห่อ Guzzle ConnectException — URL ที่มี token อยู่ทั้งสองชั้น
     * แล้ว catch block เขียน $e->getMessage() ลง log ตรง ๆ + Handler ของ Laravel แนบ exception ไว้ใน context
     */
    #[Test]
    public function connection_exception_is_scrubbed_in_message_context_and_rendered_output(): void
    {
        $token = self::pageToken();
        $url = "https://graph.facebook.com/v22.0/me/conversations?user_id=42&access_token={$token}";
        $guzzle = new ConnectException("cURL error 56: Connection reset by peer for {$url}", new Request('GET', $url));
        $e = new ConnectionException($guzzle->getMessage(), 0, $guzzle);

        [$monolog, $handler] = $this->tappedLogger();
        $monolog->warning('FB conversations fallback exception: '.$e->getMessage(), [
            'error' => $e->getMessage(),
            'nested' => ['deeper' => ['url' => $url]],
            'access_token' => $token,
            'exception' => $e,
        ]);

        $record = $handler->getRecords()[0];
        $formatter = new LineFormatter(null, null, true, true);
        $formatter->includeStacktraces();
        $rendered = $formatter->format($record);

        $this->assertStringNotContainsString($token, $rendered, 'ไฟล์ log ที่เรนเดอร์ออกมา (รวม exception + previous + stack trace) ต้องไม่มี token');
        $this->assertStringContainsString('access_token=[REDACTED]', $record->message);
        $this->assertStringContainsString('user_id=42&access_token=[REDACTED]', $record->context['error']);
        $this->assertStringContainsString('access_token=[REDACTED]', $record->context['nested']['deeper']['url']);
        $this->assertSame('[REDACTED]', $record->context['access_token']);
        $this->assertStringContainsString('access_token=[REDACTED]', $guzzle->getMessage(), 'previous exception ต้องถูกแก้ด้วย');
        // ส่วนที่ใช้ไล่ปัญหาต้องอยู่ครบ
        $this->assertStringContainsString('cURL error 56: Connection reset by peer', $rendered);
        // context ถูก json_encode ตอนเรนเดอร์ ชื่อคลาสจึงเป็น Illuminate\\Http\\... — เช็คท่อนท้ายพอ
        $this->assertStringContainsString('ConnectionException(code: 0)', $rendered);
        $this->assertStringContainsString('[previous exception]', $rendered);
    }

    /** Error (ไม่ใช่ Exception) ก็ต้องแก้ได้ — property message อยู่คนละคลาสแม่ */
    #[Test]
    public function php_errors_are_scrubbed_too(): void
    {
        $token = self::pageToken();
        $error = new \TypeError("bad arg https://graph.facebook.com/me?access_token={$token}");

        (new RedactSecretsProcessor)(new \Monolog\LogRecord(
            new \DateTimeImmutable, 'testing', \Monolog\Level::Error, 'boom', ['exception' => $error]
        ));

        $this->assertSame('bad arg https://graph.facebook.com/me?access_token=[REDACTED]', $error->getMessage());
    }

    #[Test]
    public function extra_fields_and_non_string_values_are_handled(): void
    {
        $record = (new RedactSecretsProcessor)(new \Monolog\LogRecord(
            new \DateTimeImmutable, 'testing', \Monolog\Level::Info, 'ok',
            ['count' => 3, 'flag' => true, 'nothing' => null, 'obj' => new \stdClass, 'token_type' => 'bearer'],
            ['url' => 'https://api.telegram.org/bot123456789:AAH'.str_repeat('p', 32).'/getMe'],
        ));

        $this->assertSame(3, $record->context['count']);
        $this->assertTrue($record->context['flag']);
        $this->assertNull($record->context['nothing']);
        $this->assertInstanceOf(\stdClass::class, $record->context['obj']);
        $this->assertSame('bearer', $record->context['token_type']);
        $this->assertSame('https://api.telegram.org/bot123456789:[REDACTED]/getMe', $record->extra['url']);
    }

    /**
     * single/daily ของ Laravel มี PsrLogMessageProcessor เติม {placeholder} — ต้องปิดบัง "หลัง" มันเติมแล้ว
     * ไม่งั้น token ที่ส่งผ่าน context แล้วถูกเติมเข้าข้อความจะหลุดดิบ ๆ
     */
    #[Test]
    public function placeholders_are_filled_before_redaction(): void
    {
        $token = 'lineSecret'.str_repeat('Zz9', 20);
        $handler = new TestHandler;
        $monolog = new Monolog('testing', [$handler], [new \Monolog\Processor\PsrLogMessageProcessor]);
        (new RedactSecretsTap)(new Logger($monolog));

        $monolog->warning('verify ล้มเหลว https://api.line.me/oauth2/v2.1/verify?access_token={t}', ['t' => $token]);

        $message = $handler->getRecords()[0]->message;
        $this->assertStringNotContainsString($token, $message);
        $this->assertStringContainsString('verify?access_token=[REDACTED]', $message);
        $processors = $monolog->getProcessors();
        $this->assertInstanceOf(\Monolog\Processor\PsrLogMessageProcessor::class, $processors[0]);
        $this->assertInstanceOf(RedactSecretsProcessor::class, end($processors), 'ตัวปิดบังต้องรันเป็นตัวสุดท้าย');
    }

    /** channel stack ดึง processor ของ channel ลูกมาด้วย — tap ซ้ำต้องไม่ติด processor ซ้อน */
    #[Test]
    public function tap_is_idempotent(): void
    {
        $monolog = new Monolog('testing', [new TestHandler]);
        $logger = new Logger($monolog);

        (new RedactSecretsTap)($logger);
        (new RedactSecretsTap)($logger);

        $ours = array_filter($monolog->getProcessors(), fn ($p) => $p instanceof RedactSecretsProcessor);
        $this->assertCount(1, $ours);
    }
}
