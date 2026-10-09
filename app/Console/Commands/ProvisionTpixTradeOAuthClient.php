<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Laravel\Passport\ClientRepository;

/**
 * 🪪 (2026-10-09) สร้าง OAuth2 client ให้ TPIX TRADE ใช้ผล KYC ของ Thaiprompt
 *
 * รันครั้งเดียวบน prod (หลัง deploy):
 *   php artisan oauth:provision-tpix-trade-client --redirect=https://<โดเมน TPIX TRADE>/kyc/thaiprompt/callback
 *
 * ต่างจาก client ของจันทรา (ProvisionJuntraOAuthClient) ตรงนี้:
 *   - skip_authorization = **false** → ลูกค้าเห็นหน้าขออนุญาตของ Thaiprompt ทุกครั้งที่เชื่อมครั้งแรก
 *     การส่งผล KYC ไปให้อีกบริษัท/อีกบริการต้องเป็นการยินยอมของเจ้าของข้อมูลเอง (PDPA)
 *     ไม่ใช่ auto-login แบบ SSO ของเว็บเราเอง
 *   - TPIX ขอ scope `kyc` อย่างเดียว ไม่ขอ email/profile
 *
 * secret เขียนลงไฟล์ 0600 ครั้งเดียว — **ไม่ echo ออก stdout/log** (เหมือนของจันทรา)
 */
class ProvisionTpixTradeOAuthClient extends Command
{
    protected $signature = 'oauth:provision-tpix-trade-client
                            {--redirect= : callback URI ของ TPIX TRADE (ต้องตรงทุกตัวอักษร)}
                            {--name=TPIX TRADE : ชื่อที่ลูกค้าเห็นบนหน้าขออนุญาต}';

    protected $description = '🪪 สร้าง OAuth2 client (auth_code, มีหน้าขออนุญาต) ให้ TPIX TRADE ดึงผล KYC';

    public function handle(ClientRepository $clients): int
    {
        $redirect = trim((string) $this->option('redirect'));
        $name = trim((string) $this->option('name')) ?: 'TPIX TRADE';

        // Passport เทียบ redirect แบบ byte-for-byte — ต้องเป็น https และเป็น path callback ของ TPIX
        if (! preg_match('#^https://[^\s/]+/kyc/thaiprompt/callback$#', $redirect)) {
            $this->error('ต้องระบุ --redirect=https://<โดเมน TPIX TRADE>/kyc/thaiprompt/callback');

            return self::FAILURE;
        }

        if (method_exists($clients, 'createAuthorizationCodeGrantClient')) {
            $client = $clients->createAuthorizationCodeGrantClient($name, [$redirect], true);
        } else {
            // create(userId, name, redirect, provider, personalAccess, password, confidential)
            $client = $clients->create(null, $name, $redirect, 'oauth_users', false, false, true);
        }

        $plainSecret = $client->plainSecret ?? $client->secret;

        // ⚠️ ตั้ง false ชัดๆ — ห้ามลอกของจันทรา (true) มาใช้ ลูกค้าต้องกดอนุญาตเอง
        $client->forceFill(['skip_authorization' => false])->save();

        $dir = storage_path('app/private');
        File::ensureDirectoryExists($dir, 0700);
        $file = $dir.'/tpix-trade-oauth-'.date('Ymd_His').'.json';
        File::put($file, json_encode([
            'client_id' => (string) $client->getKey(),
            'client_secret' => $plainSecret,
            'base_url' => 'https://main.thaiprompt.online',
            'redirect_uri' => $redirect,
            'scope' => 'kyc',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        @chmod($file, 0600);

        $this->info('✅ สร้าง client ของ TPIX TRADE สำเร็จ');
        $this->line('   client_id : '.$client->getKey());
        $this->line('   redirect  : '.$redirect);
        $this->line('   skip_auth : false (ลูกค้าเห็นหน้าขออนุญาต)');
        $this->line('   secret    : เขียนลงไฟล์แล้ว (ไม่แสดงที่นี่)');
        $this->line('   ไฟล์      : '.$file.' (chmod 0600)');
        $this->newLine();
        $this->warn('   → ใส่ client_id/secret ใน .env ของ TPIX TRADE (THAIPROMPT_OAUTH_CLIENT_ID/SECRET) แล้ว shred ไฟล์นี้ทิ้ง');

        return self::SUCCESS;
    }
}
