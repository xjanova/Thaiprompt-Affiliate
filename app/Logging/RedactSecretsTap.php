<?php

namespace App\Logging;

use Illuminate\Log\Logger;
use Monolog\Logger as Monolog;

/**
 * 🔐 tap ของ log channel: ติด RedactSecretsProcessor ให้ logger ตัวนั้น
 *
 * AppServiceProvider::register() เติม class นี้ลง `logging.channels.*.tap` ของ "ทุก" channel
 * (โปรเจกต์นี้ไม่มี config/logging.php ของตัวเอง ใช้ค่าตั้งต้นของเฟรมเวิร์ก จึงเติมตอนรันแทน)
 *
 * channel แบบ stack ดึง processor ของ channel ลูกมาใช้ด้วย — เช็คก่อนว่ามีแล้วหรือยัง
 * จะได้ไม่ต้องปิดบังซ้ำสองรอบต่อข้อความ
 *
 * ⚠️ ต้องเป็น processor "ตัวสุดท้าย" ที่รัน: single/daily มี PsrLogMessageProcessor (replace_placeholders)
 *    ที่เอาค่าใน context ไปเติม {placeholder} ในข้อความ — ถ้าเราปิดบังก่อน แล้วมันเติมทีหลัง
 *    `Log::warning('?access_token={t}', ['t' => $token])` จะได้ token ดิบลงไฟล์
 *    Monolog รัน processor ตามลำดับใน getProcessors() และ pushProcessor() แทรกไว้ "หน้าสุด"
 *    ⇒ ถอดตัวเดิมออกหมด ใส่ของเราก่อน แล้วใส่ตัวเดิมกลับตามลำดับเดิม = ของเราอยู่ท้ายสุด
 *    (Context processor ที่ LogManager ใส่ทีหลัง tap จะไปอยู่หน้าสุด ไม่กระทบ)
 */
final class RedactSecretsTap
{
    public function __invoke(Logger $logger): void
    {
        $monolog = $logger->getLogger();
        if (! $monolog instanceof Monolog) {
            return;
        }

        $existing = $monolog->getProcessors();
        foreach ($existing as $processor) {
            if ($processor instanceof RedactSecretsProcessor) {
                return;
            }
        }

        foreach ($existing as $_) {
            $monolog->popProcessor();
        }

        $monolog->pushProcessor(new RedactSecretsProcessor);
        foreach (array_reverse($existing) as $processor) {
            $monolog->pushProcessor($processor);
        }
    }
}
