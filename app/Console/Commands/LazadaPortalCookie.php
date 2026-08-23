<?php

namespace App\Console\Commands;

use App\Models\MarketplaceAccount;
use App\Services\Marketplace\LazadaPortalSearch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;

/**
 * 🍪 ตั้ง/ตรวจคุกกี้พอร์ทัล Lazada affiliate
 *
 * คุกกี้นี้คือกุญแจเดียวที่เปิดทางไปหาของสายมูราคาถูก (25-1,859฿)
 * ซึ่ง API ทางการให้ไม่ได้ (ราคากลางฟีด = 192,857฿)
 *
 * 🔒 ความปลอดภัย
 *   - เก็บ **เข้ารหัส** ใน `additional_credentials` ไม่ใช่ .env / marketplace_settings
 *   - ไม่พิมพ์ค่าคุกกี้ออกหน้าจอหรือ log แม้บางส่วน
 *   - `--check` ทดสอบด้วยการค้นจริง 1 ครั้ง (คำที่ไม่มีความหมาย) แล้วดูสถานะ
 *
 * Usage:
 *   php artisan lazada:portal-cookie --check
 *   php artisan lazada:portal-cookie --from-file=/tmp/cookie.txt
 *   php artisan lazada:portal-cookie --clear
 */
class LazadaPortalCookie extends Command
{
    protected $signature = 'lazada:portal-cookie
                            {--account=2 : id ของ MarketplaceAccount}
                            {--from-file= : อ่านคุกกี้จากไฟล์ (ปลอดภัยกว่าพิมพ์บน command line)}
                            {--check : ทดสอบว่าคุกกี้ที่เก็บไว้ยังใช้ได้ไหม}
                            {--clear : ลบคุกกี้ที่เก็บไว้}';

    protected $description = '🍪 ตั้ง/ตรวจคุกกี้พอร์ทัล Lazada affiliate (กุญแจไปหาของสายมูราคาถูก)';

    public function handle(): int
    {
        $account = MarketplaceAccount::find((int) $this->option('account'));
        if (! $account) {
            $this->error('❌ ไม่พบบัญชี Lazada id='.$this->option('account'));

            return self::FAILURE;
        }

        if ($this->option('clear')) {
            return $this->clear($account);
        }

        $file = trim((string) $this->option('from-file'));
        if ($file !== '') {
            if (! is_file($file)) {
                $this->error("❌ ไม่พบไฟล์: {$file}");

                return self::FAILURE;
            }

            $cookie = trim((string) file_get_contents($file));
            if ($cookie === '') {
                $this->error('❌ ไฟล์ว่าง');

                return self::FAILURE;
            }

            $creds = (array) ($account->additional_credentials ?? []);
            $creds[LazadaPortalSearch::CRED_KEY] = Crypt::encryptString($cookie);
            $account->update(['additional_credentials' => $creds]);

            // ⚠️ บอกแค่ความยาว ไม่โชว์ค่า
            $this->info('✅ บันทึกคุกกี้แล้ว (ความยาว '.strlen($cookie).' ตัวอักษร · เข้ารหัสเก็บ)');
            $this->line('   ลบไฟล์ต้นทางด้วย: shred -u '.escapeshellarg($file));
            $this->newLine();

            return $this->check($account);
        }

        if ($this->option('check')) {
            return $this->check($account);
        }

        $this->showInstructions();

        return self::SUCCESS;
    }

    /**
     * ทดสอบคุกกี้ด้วยการค้นจริง
     */
    private function check(MarketplaceAccount $account): int
    {
        $portal = new LazadaPortalSearch($account);

        $this->line('🔍 กำลังทดสอบด้วยการค้นจริง...');
        $r = $portal->search('ปี่เซี้ยะ', 1, 5);

        return match ($r['status']) {
            'ok' => tap(self::SUCCESS, function () use ($r) {
                $this->info('✅ คุกกี้ใช้ได้ — ค้นเจอ '.count($r['items']).' ชิ้น');
                foreach (array_slice($r['items'], 0, 3) as $it) {
                    $this->line(sprintf('   %6s฿ %4s%% | %s',
                        round($it['price']), $it['commission'], mb_substr($it['title'], 0, 42)));
                }
                $this->newLine();
                $this->line('   พร้อมไล่เก็บแล้ว: php artisan lazada:mu-scan --target=1200');
            }),
            'auth_dead' => tap(self::FAILURE, function () use ($r) {
                $this->error('🔒 คุกกี้ใช้ไม่ได้: '.$r['error']);
                $this->newLine();
                $this->showInstructions();
            }),
            default => tap(self::FAILURE, fn () => $this->error('⚠️ พอร์ทัลตอบผิดรูป: '.$r['error'])),
        };
    }

    /**
     * ลบคุกกี้
     */
    private function clear(MarketplaceAccount $account): int
    {
        $creds = (array) ($account->additional_credentials ?? []);
        unset($creds[LazadaPortalSearch::CRED_KEY], $creds[LazadaPortalSearch::CRED_LAST_OK]);
        $account->update(['additional_credentials' => $creds]);

        $this->info('✅ ลบคุกกี้แล้ว');

        return self::SUCCESS;
    }

    /**
     * วิธีเอาคุกกี้มา — เขียนให้คนที่ไม่ใช่โปรแกรมเมอร์ทำตามได้
     */
    private function showInstructions(): void
    {
        $this->newLine();
        $this->info('📋 วิธีเอาคุกกี้มา (ทำครั้งเดียว ~1 นาที)');
        $this->line('');
        $this->line('  1. เปิด Chrome เข้า https://adsense.lazada.co.th แล้วล็อกอินให้เรียบร้อย');
        $this->line('  2. กด F12 เปิด DevTools → แท็บ Network');
        $this->line('  3. ค้นสินค้าอะไรก็ได้ในหน้าเว็บ 1 ครั้ง');
        $this->line('  4. ในรายการ Network หาไฟล์ชื่อ searchSkuOffer.json แล้วคลิก');
        $this->line('  5. เลื่อนหา Request Headers → บรรทัด cookie:');
        $this->line('  6. คลิกขวาที่ค่านั้น → Copy value  (ยาวมาก ปกติ)');
        $this->line('  7. เอามาวางให้ผม หรือใส่ไฟล์แล้วรัน:');
        $this->line('       php artisan lazada:portal-cookie --from-file=/tmp/cookie.txt');
        $this->newLine();
        $this->warn('⚠️ คุกกี้นี้ = สิทธิ์เข้าบัญชี affiliate ของคุณ');
        $this->line('   ระบบเก็บแบบเข้ารหัส ไม่พิมพ์ลง log และไม่ส่งออกไปไหน');
        $this->line('   ถ้าเปลี่ยนใจ ลบได้ทุกเมื่อ: php artisan lazada:portal-cookie --clear');
    }
}
