<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 🔐 เข้ารหัสค่าลับของการเชื่อมต่อแพลตฟอร์มแชทบอท (2026-09-14)
 *
 * เดิม token ทุกตัวใน chatbot_platform_integrations เป็นข้อความเปล่า — โมเดลแค่ซ่อนจาก JSON ($hidden)
 * ใครเปิดฐานข้อมูลหรือไฟล์ backup ได้ = ได้ token บอท LINE/Telegram/Discord/Facebook ของลูกค้าไปใช้ต่อทันที
 * ตอนนี้โมเดลใช้ cast 'encrypted' (APP_KEY) — migration นี้เตรียมคอลัมน์ + เข้ารหัสแถวที่มีอยู่
 *
 * 1) telegram_bot_token / discord_bot_token เป็น varchar(255) — ค่าที่เข้ารหัสแล้วยาวเกิน 255 → ขยายเป็น TEXT
 * 2) แถวเดิมที่ยังเป็นข้อความเปล่า → เข้ารหัส · แถวที่ถอดรหัสได้อยู่แล้วข้าม (รันซ้ำได้)
 *    ค่าว่าง '' → null (cast 'encrypted' ถอดรหัส '' ไม่ได้ จะพังตอนอ่าน)
 *    prod ตอนเขียน migration นี้มี 0 แถว แต่ฐาน dev/staging อาจมี
 *
 * ⚠️ เป็น ALTER TABLE — ห้ามใช้ Schema::hasTable() + return
 */
return new class extends Migration
{
    /** คอลัมน์ค่าลับทั้งหมด — ต้องตรงกับ cast 'encrypted' ใน ChatbotPlatformIntegration */
    private const SECRET_COLUMNS = [
        'access_token',
        'refresh_token',
        'webhook_secret',
        'line_channel_secret',
        'line_channel_access_token',
        'fb_app_secret',
        'telegram_bot_token',
        'discord_bot_token',
    ];

    /**
     * ขยายคอลัมน์ + เข้ารหัสค่าที่ยังเป็นข้อความเปล่า
     */
    public function up(): void
    {
        // ALTER เฉพาะคอลัมน์ที่ยังไม่ใช่ TEXT — รันซ้ำแล้วไม่ ALTER ซ้ำ (DDL ของ MySQL commit ทรานแซกชันที่ค้างอยู่ทิ้ง)
        $widen = array_filter(
            ['telegram_bot_token', 'discord_bot_token'],
            fn (string $column) => ! in_array(Schema::getColumnType('chatbot_platform_integrations', $column), ['text', 'mediumtext', 'longtext'], true),
        );

        if ($widen !== []) {
            Schema::table('chatbot_platform_integrations', function (Blueprint $table) use ($widen) {
                foreach ($widen as $column) {
                    $table->text($column)->nullable()->change();
                }
            });
        }

        $this->rewriteSecrets(function (string $value): string|false|null {
            if ($value === '') {
                return null;
            }

            return $this->isEncrypted($value) ? false : Crypt::encryptString($value);
        });
    }

    /**
     * ถอดรหัสกลับเป็นข้อความเปล่า + หดคอลัมน์คืน
     */
    public function down(): void
    {
        $this->rewriteSecrets(function (string $value): string|false {
            return $this->isEncrypted($value) ? Crypt::decryptString($value) : false;
        });

        Schema::table('chatbot_platform_integrations', function (Blueprint $table) {
            $table->string('telegram_bot_token')->nullable()->change();
            $table->string('discord_bot_token')->nullable()->change();
        });
    }

    /**
     * ไล่ทุกแถว (รวมแถวที่ soft delete) แล้วเขียนค่าลับใหม่ตาม $transform
     *
     * @param  callable(string): (string|false|null)  $transform  คืน false = ไม่ต้องแก้ค่านี้
     */
    private function rewriteSecrets(callable $transform): void
    {
        DB::table('chatbot_platform_integrations')
            ->select(array_merge(['id'], self::SECRET_COLUMNS))
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($transform) {
                foreach ($rows as $row) {
                    $changes = [];

                    foreach (self::SECRET_COLUMNS as $column) {
                        if ($row->{$column} === null) {
                            continue;
                        }

                        $rewritten = $transform((string) $row->{$column});
                        if ($rewritten !== false) {
                            $changes[$column] = $rewritten;
                        }
                    }

                    if ($changes !== []) {
                        DB::table('chatbot_platform_integrations')->where('id', $row->id)->update($changes);
                    }
                }
            });
    }

    private function isEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);

            return true;
        } catch (DecryptException) {
            return false;
        }
    }
};
