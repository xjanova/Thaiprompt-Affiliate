<?php

namespace App\Console\Commands;

use App\Models\MarketplaceAccount;
use App\Services\Marketplace\LazadaConversionSyncService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * ดึงรายงาน conversion ของ Lazada Affiliate → สร้าง/อัปเดตออเดอร์ในตาราง marketplace_orders
 *
 * ตัวอย่างการใช้:
 *   php artisan lazada:sync-conversions 3                    # เดือนปัจจุบัน
 *   php artisan lazada:sync-conversions 3 --month=2026-06    # เจาะจงเดือน
 *   php artisan lazada:sync-conversions 3 --months=3         # เดือนนี้ + ย้อนหลังอีก 2 เดือน
 *   php artisan lazada:sync-conversions 3 --months=3 --dry   # ซ้อมแห้ง ไม่เขียน DB
 *
 * 🚨 ค่าเริ่มต้นของระบบคือ "ไม่จ่าย" — ออเดอร์จะถูกอนุมัติค่าคอมอัตโนมัติก็ต่อเมื่อ
 *    ลาซาด้ายืนยันว่าเคลียร์ค่าคอมให้เราแล้ว + ระบุตัวผู้แนะนำได้แน่ชัด + แอดมินเปิดธง
 *    lazada_conversion_mapping_verified (ยืนยันว่า mapping ชื่อฟิลด์ตรงกับของจริงแล้ว)
 *    นอกนั้นค้าง pending รอคนตรวจทั้งหมด
 *
 * ⚠️ API รองรับช่วงวันที่ภายใน "เดือนเดียว" เท่านั้น → คำสั่งนี้จึงยิงทีละเดือนเสมอ
 */
class LazadaSyncConversions extends Command
{
    protected $signature = 'lazada:sync-conversions
        {account : ID ของ marketplace_accounts (ชนิด affiliate_native)}
        {--month= : เดือนที่ต้องการ รูปแบบ YYYY-MM (ไม่ระบุ = เดือนปัจจุบัน)}
        {--months=1 : ย้อนหลังกี่เดือน นับรวมเดือนเริ่มต้น (ยิงทีละเดือนตามข้อจำกัด API)}
        {--dry : ซ้อมแห้ง — ดึงข้อมูลมานับอย่างเดียว ไม่เขียนอะไรลงฐานข้อมูล}';

    protected $description = 'ซิงก์รายงาน conversion ของ Lazada Affiliate เข้าตาราง marketplace_orders (ค่าเริ่มต้น = ไม่อนุมัติจ่าย)';

    public function handle(): int
    {
        $account = MarketplaceAccount::find((int) $this->argument('account'));
        if (! $account) {
            $this->error('ไม่พบบัญชี marketplace_accounts id='.$this->argument('account'));

            return self::FAILURE;
        }

        // เดือนเริ่มต้น
        $monthOption = (string) ($this->option('month') ?? '');
        if ($monthOption !== '') {
            // ⚠️ ห้ามใช้ createFromFormat('Y-m', ...) — วันจะเป็น "วันนี้" ถ้าวันนี้คือวันที่ 31
            //    แล้วเดือนที่มี 30 วันจะล้นไปเดือนถัดไป (2026-06 → 1 ก.ค.) ต้องตรึงวันที่ 1 เสมอ
            if (! preg_match('/^\d{4}-\d{2}$/', $monthOption)) {
                $this->error('รูปแบบ --month ไม่ถูกต้อง ต้องเป็น YYYY-MM (เช่น 2026-06)');

                return self::FAILURE;
            }
            try {
                $from = Carbon::createFromFormat('Y-m-d', $monthOption.'-01')->startOfMonth();
            } catch (\Throwable $e) {
                $this->error('รูปแบบ --month ไม่ถูกต้อง ต้องเป็น YYYY-MM (เช่น 2026-06)');

                return self::FAILURE;
            }
        } else {
            $from = Carbon::now()->startOfMonth();
        }

        // จำกัดไม่เกิน 24 เดือน กันเผลอพิมพ์เลขเยอะแล้วยิง API รัวเป็นร้อยครั้ง
        $months = max(1, min(24, (int) $this->option('months')));
        $dry = (bool) $this->option('dry');

        $service = new LazadaConversionSyncService($dry);

        $this->line('');
        $this->line('🔎 ซิงก์รายงาน conversion — บัญชี #'.$account->id.' ('.($account->account_name ?: '-').')');
        $this->line('   ช่วงเวลา: '.$from->format('Y-m').' ย้อนหลัง '.$months.' เดือน'.($dry ? '  [โหมดซ้อมแห้ง ไม่เขียน DB]' : ''));
        $this->line('   ธงยืนยัน mapping ('.LazadaConversionSyncService::MAPPING_VERIFIED_KEY.'): '
            .($service->isMappingVerified() ? '✅ เปิด — อนุมัติอัตโนมัติได้ถ้าผ่านด่านตรวจครบ' : '⛔ ปิด — ทุกออเดอร์จะค้าง pending (ค่าเริ่มต้น = ไม่จ่าย)'));
        $this->line('');

        $summaries = $service->syncMonths($account, $months, $from);

        // สรุปรายเดือน
        $rows = [];
        foreach ($summaries as $s) {
            $rows[] = [
                $s['month'],
                $s['fetched'],
                $s['stored'],
                $s['updated'],
                $s['attributed'],
                $s['approved'],
                $s['pending'],
                $s['failed'],
            ];
        }

        $total = $service->mergeSummaries($summaries);
        $rows[] = ['─────', '─────', '─────', '─────', '─────', '─────', '─────', '─────'];
        $rows[] = [
            'รวม',
            $total['fetched'],
            $total['stored'],
            $total['updated'],
            $total['attributed'],
            $total['approved'],
            $total['pending'],
            $total['failed'],
        ];

        $this->table(
            ['เดือน', 'ดึงมาได้', 'สร้างใหม่', 'อัปเดต', 'ระบุคนได้', 'อนุมัติ', 'ค้างตรวจ', 'ผิดพลาด'],
            $rows
        );

        // ครั้งแรกที่เจอแถวจริง — โชว์ชื่อฟิลด์เพื่อให้คนเอาไปล็อก mapping
        $keys = $service->getDiscoveredKeys();
        if ($keys) {
            $this->line('');
            $this->warn('🔑 พบชื่อฟิลด์จริงของรายงาน conversion เป็นครั้งแรก:');
            $this->line('   '.implode(', ', $keys));
            $this->line('   → เอาชื่อเหล่านี้ไปล็อก mapping ใน LazadaConversionSyncService::normalizeRow()');
            $this->line('   → ตรวจว่า map ตรงจริงแล้วค่อยเปิดธง '.LazadaConversionSyncService::MAPPING_VERIFIED_KEY);
            $this->line('   (ดูรายละเอียดเต็มใน log: "lazada conversion: discovered fields")');
        }

        if (! empty($total['errors'])) {
            $this->line('');
            $this->warn('⚠️ ข้อความแจ้งเตือน/ข้อผิดพลาด:');
            foreach (array_slice($total['errors'], 0, 20) as $err) {
                $this->line('   - '.$err);
            }
            if (count($total['errors']) > 20) {
                $this->line('   ... และอีก '.(count($total['errors']) - 20).' รายการ');
            }
        }

        $this->line('');
        if ($dry) {
            $this->info('✅ ซ้อมแห้งเสร็จสิ้น — ไม่มีการเขียนฐานข้อมูล');
        } else {
            $this->info('✅ ซิงก์เสร็จสิ้น — อนุมัติ '.$total['approved'].' รายการ / ค้างรอคนตรวจ '.$total['pending'].' รายการ');
            if ($total['pending'] > 0) {
                $this->line('   หมายเหตุ: รายการค้างจะไม่จ่ายค่าคอม/PV จนกว่าคนจะอนุมัติเอง (ตามกฎ "ค่าเริ่มต้นคือไม่จ่าย")');
            }
        }
        $this->line('');

        return self::SUCCESS;
    }
}
