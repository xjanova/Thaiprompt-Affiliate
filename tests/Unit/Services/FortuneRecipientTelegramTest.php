<?php

namespace Tests\Unit\Services;

use App\Models\FortuneReading;
use App\Services\Fortune\FortuneRecipient;
use Tests\TestCase;

/**
 * ✈️ (2026-09-13) id ลูกค้า Telegram = 'tg_<chat id>' — กันตีเป็นลูกค้า Facebook
 *
 * id ของ Telegram เป็นตัวเลขล้วนเหมือน PSID ของ FB ⇒ ถ้าไม่มีคำนำหน้า โค้ด ~112 จุดที่แยกช่องทาง
 * ด้วยรูปทรง id จะยิง Facebook Send API ด้วยเลข Telegram (ข้อความหายเงียบ)
 *
 * @group telegram
 */
class FortuneRecipientTelegramTest extends TestCase
{
    public function test_telegram_user_id_round_trip(): void
    {
        $this->assertSame('tg_123456789', FortuneRecipient::telegramUserId(123456789));
        $this->assertSame('tg_123456789', FortuneRecipient::telegramUserId('123456789'));
        $this->assertSame('tg_123456789', FortuneRecipient::telegramUserId('tg_123456789'), 'ต้อง idempotent');
        $this->assertSame('123456789', FortuneRecipient::telegramChatId('tg_123456789'));
    }

    public function test_rejects_non_private_or_garbage_ids(): void
    {
        // id กลุ่ม/ช่องของ Telegram เป็นเลขติดลบ — ระบบรับเฉพาะแชทส่วนตัว
        $this->assertSame('', FortuneRecipient::telegramUserId('-1001234567890'));
        $this->assertSame('', FortuneRecipient::telegramUserId('abc'));
        $this->assertSame('', FortuneRecipient::telegramUserId(''));
        $this->assertSame('', FortuneRecipient::telegramUserId(null));

        // เลขเปล่า (ไม่มีคำนำหน้า) = ไม่ใช่ id Telegram ของระบบเรา → ห้ามยิง API
        $this->assertSame('', FortuneRecipient::telegramChatId('123456789'));
        $this->assertSame('', FortuneRecipient::telegramChatId('tg_'));
    }

    public function test_platform_from_user_id_shape(): void
    {
        $this->assertSame('telegram', FortuneRecipient::platformFromUserId('tg_42'));
        $this->assertSame('line', FortuneRecipient::platformFromUserId('U'.str_repeat('f', 32)));
        $this->assertSame('facebook', FortuneRecipient::platformFromUserId('26273302092329161'));
        $this->assertSame('facebook', FortuneRecipient::platformFromUserId(''));
    }

    public function test_normalize_trusts_telegram_id_over_wrong_platform(): void
    {
        // payload job เก่า/ข้อมูลเพี้ยนบอก facebook แต่ id เป็นทรง Telegram → ต้องออก Telegram
        $this->assertSame(
            ['platform' => 'telegram', 'user_id' => 'tg_42'],
            FortuneRecipient::normalize('facebook', 'tg_42')
        );
        $this->assertSame('telegram', FortuneRecipient::normalize('line', 'tg_42')['platform']);

        // platform=telegram แต่ id ไม่ใช่ทรง tg_ → ยิง Telegram ไม่ถึงแน่นอน → ค่าเริ่มต้นเดิม
        $this->assertSame('facebook', FortuneRecipient::normalize('telegram', '26273302092329161')['platform']);
    }

    public function test_platform_of_reading(): void
    {
        $reading = new FortuneReading;
        $reading->platform = 'telegram';
        $reading->facebook_user_id = 'tg_42';
        $reading->platform_user_id = 'tg_42';

        $this->assertSame(['platform' => 'telegram', 'user_id' => 'tg_42'], FortuneRecipient::resolve($reading));

        // platform ค้างเป็น facebook (ค่า default ของคอลัมน์) แต่ id เป็นทรง Telegram → Telegram
        $legacy = new FortuneReading;
        $legacy->platform = 'facebook';
        $legacy->facebook_user_id = 'tg_42';
        $this->assertSame('telegram', FortuneRecipient::platformOf($legacy));
        $this->assertSame('tg_42', FortuneRecipient::userIdOf($legacy));

        // platform=telegram แต่ไม่มี id ทรง tg_ เลย → ห้ามเดาเป็น Telegram
        $broken = new FortuneReading;
        $broken->platform = 'telegram';
        $broken->facebook_user_id = '26273302092329161';
        $this->assertSame('facebook', FortuneRecipient::platformOf($broken));
    }

    public function test_line_and_facebook_behaviour_unchanged(): void
    {
        $line = new FortuneReading;
        $line->platform = 'facebook';
        $line->facebook_user_id = 'U'.str_repeat('a', 32);
        $this->assertSame('line', FortuneRecipient::platformOf($line));

        $fb = new FortuneReading;
        $fb->platform = 'facebook';
        $fb->facebook_user_id = '26273302092329161';
        $this->assertSame(['platform' => 'facebook', 'user_id' => '26273302092329161'], FortuneRecipient::resolve($fb));

        $empty = new FortuneReading;
        $this->assertSame('facebook', FortuneRecipient::platformOf($empty));
    }
}
