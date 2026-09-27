<?php

namespace App\Logging;

use App\Support\SafeLog;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Throwable;

/**
 * 🔐 ตาข่ายชั้นสุดท้าย: ปิดบัง secret ใน "ทุก" log record ก่อนเขียนลงไฟล์
 *
 * ทำไมต้องมี (2026-09-27): มี catch block ที่ log $e->getMessage() หลายร้อยจุดทั่วแอป
 *   (FacebookWebhookService อย่างเดียว ~30 จุด + Gemini `?key=` + Telegram `/bot<token>/`)
 *   ไล่ครอบทีละจุดไม่มีทางครบ และโค้ดใหม่ก็จะลืมอีก ⇒ ครอบที่ชั้น Monolog ครั้งเดียวจบ
 *   รวมถึง exception ที่หลุดไปถึง Handler ของ Laravel (log ข้อความ + ['exception' => $e])
 *
 * สิ่งที่ทำ:
 *   - message ของ record → SafeLog::redactSecrets()
 *   - context / extra: สตริงทุกตัวผ่าน redactSecrets(), key ที่ชื่อเป็นความลับ (access_token,
 *     client_secret, api_key, ...) ถูกแทนทั้งค่า, ไล่ array ซ้อนได้ถึง MAX_DEPTH ชั้น
 *   - Throwable ใน context → แก้ข้อความของ exception (และ previous ทั้งสาย) ในตัว object
 *     เพราะ formatter ของ Monolog พิมพ์ $e->getMessage() ของทั้งสายลงไฟล์เอง
 *
 * ⚠️ ห้ามโยน exception ออกจากตัวนี้เด็ดขาด — processor พัง = Log::*() ทุกตัวพัง = request 500 ทั้งแอป
 *    ทุกอย่างจึงครอบ try/catch แล้วคืน record เดิม
 *
 * ผูกเข้าทุก channel ที่ AppServiceProvider::register() ผ่าน RedactSecretsTap
 */
final class RedactSecretsProcessor implements ProcessorInterface
{
    /** ความลึกสูงสุดของ array ซ้อนใน context ที่จะไล่ (กันโครงสร้างใหญ่/วนซ้ำกิน CPU) */
    private const MAX_DEPTH = 6;

    /** ไล่ previous exception สูงสุดกี่ชั้น */
    private const MAX_PREVIOUS = 10;

    public function __invoke(LogRecord $record): LogRecord
    {
        try {
            return $record->with(
                message: SafeLog::redactSecrets($record->message),
                context: $this->scrubArray($record->context, 0),
                extra: $this->scrubArray($record->extra, 0),
            );
        } catch (Throwable) {
            return $record;
        }
    }

    /**
     * @param  array<mixed>  $data
     * @return array<mixed>
     */
    private function scrubArray(array $data, int $depth): array
    {
        foreach ($data as $key => $value) {
            // key ที่ชื่อบอกว่าเป็นความลับ → ปิดทั้งค่า ไม่ต้องเดารูปแบบ
            if (is_string($key) && is_string($value) && $value !== '' && SafeLog::isSecretKey($key)) {
                $data[$key] = SafeLog::MASK;

                continue;
            }

            $data[$key] = $this->scrubValue($value, $depth);
        }

        return $data;
    }

    private function scrubValue(mixed $value, int $depth): mixed
    {
        if (is_string($value)) {
            return SafeLog::redactSecrets($value);
        }

        if ($value instanceof Throwable) {
            $this->scrubThrowable($value);

            return $value;
        }

        if (is_array($value) && $depth < self::MAX_DEPTH) {
            return $this->scrubArray($value, $depth + 1);
        }

        return $value;
    }

    /**
     * แก้ข้อความของ exception ทั้งสาย (ConnectionException ของ Laravel ห่อ ConnectException
     * ของ Guzzle ไว้ใน previous — URL ที่มี token อยู่ทั้งสองชั้น)
     *
     * แก้ใน object ตรง ๆ ผ่าน Reflection เพื่อให้รูปแบบ log (class, file:line, stack trace) เหมือนเดิมทุกอย่าง
     * แตะเฉพาะ exception ที่มี secret จริงเท่านั้น
     */
    private function scrubThrowable(Throwable $e): void
    {
        for ($i = 0, $current = $e; $current !== null && $i < self::MAX_PREVIOUS; $current = $current->getPrevious(), $i++) {
            $message = $current->getMessage();
            $clean = SafeLog::redactSecrets($message);
            if ($clean === $message) {
                continue;
            }

            try {
                $owner = $current instanceof \Exception ? \Exception::class : \Error::class;
                (new \ReflectionProperty($owner, 'message'))->setValue($current, $clean);
            } catch (Throwable) {
                // แก้ไม่ได้ (ไม่น่าเกิด) — ปล่อยไป ดีกว่าทำ log หาย
            }
        }
    }
}
