<?php

namespace App\Console\Commands;

use App\Models\MarketplaceAccount;
use App\Services\Marketplace\LazadaAffiliateService;
use Illuminate\Console\Command;

/**
 * ทดสอบดึงฟีดสินค้า Lazada Affiliate Open API (endpoint จริง /marketing/product/feed)
 *
 * รันบน prod (IP ไทย ยิง Lazada ได้) หลังกรอก App Key/Secret + User Token ในบัญชี affiliate_native:
 *
 *   php artisan lazada:affiliate-feed {accountId} --limit=5
 *   php artisan lazada:affiliate-feed {accountId} --cat=<categoryL1> --page=1 --limit=20
 *
 * ครั้งแรกให้ดู --raw เพื่อยืนยันโครง response จริง (path ของ array สินค้า + ชื่อฟิลด์)
 * แล้วค่อยล็อก mapping ใน LazadaAffiliateService::extractFeedRows()/normalizeFeedItem()
 *
 * 🔒 ไม่พิมพ์ App Key/Secret/User Token — โชว์เฉพาะสถานะ + ข้อมูลสินค้า
 */
class LazadaAffiliateFeed extends Command
{
    protected $signature = 'lazada:affiliate-feed
        {account : ID ของ marketplace_accounts (ชนิด affiliate_native)}
        {--offer=1 : offerType 1=ปกติ 2=MM 3=DM}
        {--page=1 : หน้า (เริ่ม 1)}
        {--limit=5 : จำนวนต่อหน้า (1-100)}
        {--cat= : categoryL1 (ไม่ระบุ = ทุกหมวด)}
        {--link= : ทดสอบดึงลิงก์ค่าคอมของ productId นี้ (dump raw ยืนยันฟิลด์ url)}
        {--raw : พิมพ์โครง response ดิบ เพื่อยืนยัน mapping}';

    protected $description = 'ทดสอบดึงฟีดสินค้า Lazada Affiliate Open API (/marketing/product/feed)';

    public function handle(): int
    {
        $account = MarketplaceAccount::find((int) $this->argument('account'));
        if (! $account) {
            $this->error('ไม่พบบัญชี marketplace_accounts id='.$this->argument('account'));

            return self::FAILURE;
        }
        if (! $account->isNativeAffiliateProgram()) {
            $this->warn('⚠️ บัญชีนี้ program_type='.$account->program_type.' (ไม่ใช่ affiliate_native) — feed API ใช้กับ affiliate_native');
        }

        // โหมด debug: ทดสอบดึงลิงก์ค่าคอมของ productId + dump raw (ยืนยันชื่อฟิลด์ url)
        if ($pid = $this->option('link')) {
            $svc = new LazadaAffiliateService($account);
            $this->line('getProductLink('.$pid.') => '.($svc->getProductLink($pid) ?: 'NULL'));
            $raw = $svc->fetchProductLinkRaw($pid);
            $this->line('── RAW link response ──');
            $this->line(is_array($raw) ? mb_substr((string) json_encode($raw, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 0, 1600) : '(null)');

            return self::SUCCESS;
        }

        $cat = $this->option('cat');
        $res = (new LazadaAffiliateService($account))->getProductFeed(
            offerType: (int) $this->option('offer'),
            page: (int) $this->option('page'),
            limit: (int) $this->option('limit'),
            categoryL1: ($cat !== null && $cat !== '') ? (int) $cat : null,
        );

        $this->line('');
        $this->line($res['ok'] ? '✅ ดึง feed สำเร็จ' : ('❌ '.$res['error']));
        $this->line('เจอ '.count($res['items']).' รายการ (page '.$res['page'].', hasMore='.($res['hasMore'] ? 'yes' : 'no').')');
        $this->line('');

        foreach ($res['items'] as $it) {
            $this->line(sprintf(
                '- [%s] %s | %s%s | คอม %s | %s',
                $it['product_id'],
                mb_substr($it['name'], 0, 44),
                $it['price'],
                $it['currency'],
                ($it['commission_rate'] ?? 0) > 0 ? round($it['commission_rate'] * 100, 1).'%' : '-',
                $it['brand'] !== '' ? $it['brand'] : $it['seller']
            ));
        }

        // ครั้งแรก / มีปัญหา / --raw → โชว์โครง raw เพื่อยืนยัน mapping (ไม่มี key/secret/token ใน response)
        if ($this->option('raw') || ! $res['ok'] || empty($res['items'])) {
            $raw = $res['raw'];
            $this->line('');
            $this->line('── RAW structure (ยืนยัน mapping) ──');
            if (is_array($raw)) {
                $this->line('top keys: '.implode(', ', array_keys($raw)));
                $d = $raw['data'] ?? null;
                if (is_array($d)) {
                    $this->line('data keys: '.implode(', ', array_keys($d)));
                    foreach ($d as $k => $v) {
                        if (is_array($v) && isset($v[0]) && is_array($v[0])) {
                            $this->line("data.{$k}[0] fields: ".implode(', ', array_keys($v[0])));
                            break;
                        }
                    }
                }
                $this->line(mb_substr((string) json_encode($raw, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 0, 1600));
            } else {
                $this->line('(ไม่มี raw — ยังไม่ได้ยิง หรือ transport ล้ม)');
            }
        }

        return self::SUCCESS;
    }
}
