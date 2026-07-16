<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Laravel\Passport\ClientRepository;

/**
 * สร้าง OAuth2 authorization_code client (confidential) ให้ juntraweb (จันทรา.online)
 *
 * 🔐 (2026-07-17) รันครั้งเดียวบน prod หลัง passport:keys
 *   - provider = oauth_users (ตรงกับ guard api-oauth)
 *   - redirect = callback ของ juntra (punycode เป๊ะ — Passport เทียบ byte-for-byte)
 *   - skip_authorization = true → auto-login ไม่มีหน้า consent
 *   - เขียน client_id + plain secret ลงไฟล์ 0600 (storage/app/private) ครั้งเดียว
 *     เพื่อ copy ไป juntra — **ไม่ echo secret ออก stdout/log**
 */
class ProvisionJuntraOAuthClient extends Command
{
    protected $signature = 'oauth:provision-juntra-client
                            {--redirect=https://xn--82c4af5bzdj.online/auth/thaiprompt/callback : callback URI ของ juntra}
                            {--name=Juntra Chantra SSO : ชื่อ client}';

    protected $description = '🔐 สร้าง OAuth2 client (auth_code, skip-consent) ให้ juntraweb SSO';

    public function handle(ClientRepository $clients): int
    {
        $redirect = (string) $this->option('redirect');
        $name = (string) $this->option('name');

        // สร้าง confidential authorization_code client — รองรับ signature ต่างกัน
        // ข้าม minor ของ Passport 12.x
        if (method_exists($clients, 'createAuthorizationCodeGrantClient')) {
            // Passport 12.x ใหม่: (name, redirectUris[], confidential, user?, enableDeviceFlow?)
            $client = $clients->createAuthorizationCodeGrantClient(
                $name,
                [$redirect],
                true,
            );
        } else {
            // Passport 12.x เก่า: create(userId, name, redirect, provider, personalAccess, password, confidential)
            $client = $clients->create(
                null,
                $name,
                $redirect,
                'oauth_users',
                false,
                false,
                true,
            );
        }

        // เก็บ plain secret ก่อนหาย (hashClientSecrets เปิดอยู่ → DB เก็บ hash)
        $plainSecret = $client->plainSecret ?? $client->secret;

        // trust client นี้ → ข้าม consent (auto-login)
        $client->forceFill(['skip_authorization' => true])->save();

        // เขียนไฟล์ 0600 ครั้งเดียวเพื่อ copy ไป juntra — ไม่ print secret
        $dir = storage_path('app/private');
        File::ensureDirectoryExists($dir, 0700);
        $file = $dir.'/juntra-oauth-'.date('Ymd_His').'.json';
        File::put($file, json_encode([
            'client_id' => (string) $client->getKey(),
            'client_secret' => $plainSecret,
            'base_url' => 'https://main.thaiprompt.online',
            'redirect_uri' => $redirect,
            'provider' => 'oauth_users',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        @chmod($file, 0600);

        $this->info('✅ สร้าง client สำเร็จ');
        $this->line('   client_id : '.$client->getKey());
        $this->line('   redirect  : '.$redirect);
        $this->line('   skip_auth : true');
        $this->line('   secret    : เขียนลงไฟล์แล้ว (ไม่แสดงในนี้)');
        $this->line('   ไฟล์      : '.$file.' (chmod 0600)');
        $this->newLine();
        $this->warn('   → copy ไฟล์นี้ไปเครื่อง juntra แล้วรัน Setting::put ตาม runbook แล้ว shred ทิ้งทั้ง 2 เครื่อง');

        return self::SUCCESS;
    }
}
