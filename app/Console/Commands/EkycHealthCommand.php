<?php

namespace App\Console\Commands;

use App\Services\Ekyc\EkycAiClient;
use Illuminate\Console\Command;

/**
 * 🪪 ตรวจว่าบริการ AI eKYC (docker ekyc-ai) ตอบได้และกุญแจ X-Ekyc-Key ถูกต้อง — ใช้หลัง deploy
 *
 * ไม่พิมพ์กุญแจหรือ URL เต็มออกหน้าจอ
 *
 * Usage: php artisan ekyc:health
 */
class EkycHealthCommand extends Command
{
    protected $signature = 'ekyc:health';

    protected $description = 'ตรวจสถานะบริการ AI eKYC';

    public function handle(EkycAiClient $ai): int
    {
        if (! $ai->configured()) {
            $this->error('ยังไม่ได้ตั้ง EKYC_AI_URL / EKYC_AI_KEY (แก้ .env แล้ว php artisan config:cache)');

            return self::FAILURE;
        }

        $result = $ai->health();
        if (! $result['available'] || ($result['data']['ok'] ?? false) !== true) {
            $this->error('บริการ AI ไม่พร้อม: '.($result['error'] ?? 'NOT_OK').' ('.$result['ms'].' ms)');

            return self::FAILURE;
        }

        $models = (array) ($result['data']['models'] ?? []);
        $this->info('บริการ AI พร้อม ('.$result['ms'].' ms) · โมเดล: '.(empty($models) ? '-' : json_encode($models, JSON_UNESCAPED_UNICODE)));

        return self::SUCCESS;
    }
}
