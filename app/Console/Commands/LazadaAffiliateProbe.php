<?php

namespace App\Console\Commands;

use App\Models\MarketplaceAccount;
use App\Services\Marketplace\LazadaAffiliateService;
use Illuminate\Console\Command;

/**
 * ค้น endpoint จริงของ Lazada Affiliate Open API (discovery)
 *
 * ใช้หลังจากแอดมินกรอก App Key/Secret ในหน้า Lazada Hub → การเชื่อมต่อ (ชนิด affiliate_native)
 * แล้วรันบน prod (IP ไทย datacenter ยิง Lazada ได้):
 *
 *   php artisan lazada:affiliate-probe {accountId}
 *
 * ผลลัพธ์บอกว่า endpoint ไหน "ลายเซ็นผ่าน" (คีย์ถูก) และตัวไหนมีอยู่จริง → นำไปเติม
 * searchProducts()/generatePromotionLink() ตาม spec จริง โดยไม่ต้องเดา
 *
 * 🔒 ไม่พิมพ์คีย์/ลายเซ็นออกมา — โชว์เฉพาะ path + code + message
 */
class LazadaAffiliateProbe extends Command
{
    protected $signature = 'lazada:affiliate-probe {account : ID ของ marketplace_accounts (ชนิด affiliate_native)}';

    protected $description = 'ยิง Lazada Affiliate Open API เพื่อค้น endpoint จริง + ยืนยันว่าคีย์/ลายเซ็นถูกต้อง';

    public function handle(): int
    {
        $id = (int) $this->argument('account');
        $account = MarketplaceAccount::find($id);

        if (! $account) {
            $this->error("ไม่พบบัญชี marketplace_accounts id={$id}");

            return self::FAILURE;
        }

        if (empty($account->app_key) || empty($account->app_secret)) {
            $this->error('บัญชีนี้ยังไม่มี App Key / App Secret — กรอกในหน้า Lazada Hub → การเชื่อมต่อ ก่อน');

            return self::FAILURE;
        }

        $this->info("🔎 กำลัง probe Lazada Affiliate API สำหรับบัญชี #{$id} ({$account->account_name})...");

        $service = new LazadaAffiliateService($account);

        // 1) ทดสอบว่าคีย์/ลายเซ็นถูกต้องก่อน
        [$ok, $msg] = $service->testConnection();
        $this->line('');
        $this->line($ok ? "✅ testConnection: {$msg}" : "❌ testConnection: {$msg}");
        $this->line('');

        // 2) รายงานผลราย endpoint (discovery)
        $rows = [];
        foreach ($service->probe() as $r) {
            $rows[] = [
                $r['path'],
                $r['authOk'] ? '✓' : '✗',
                $r['code'],
                $r['message'],
            ];
        }

        $this->table(['Endpoint', 'ลายเซ็นผ่าน', 'code', 'message'], $rows);

        $this->line('');
        $this->info('อ่านผล: แถวที่ "ลายเซ็นผ่าน = ✓" และ code ไม่ใช่กลุ่ม auth → endpoint นั้นรับคีย์เราแล้ว');
        $this->info('code = "0"/ว่าง → endpoint นั้นทำงานเต็ม (เอาไปใช้ค้นสินค้า/สร้างลิงก์ได้)');

        return self::SUCCESS;
    }
}
