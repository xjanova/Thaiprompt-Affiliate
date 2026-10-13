<?php

namespace App\Console\Commands;

use App\Models\FreshMarketListing;
use App\Models\FreshMarketSeller;
use App\Services\Ekyc\EkycGate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 🪪 ปรับร้านตลาดสดที่มีอยู่แล้วให้ตรงกฎ "ขายได้เมื่อเจ้าของร้านยืนยันตัวตนแล้ว" (2026-10-13) — รันครั้งเดียวหลัง deploy
 *
 * ก่อนกฎนี้ ร้านใหม่ได้ is_verified = true ทันที (เปิดอนุมัติอัตโนมัติ) แม้เจ้าของยังไม่ยืนยันตัวตน
 * และตอนนั้นร้านที่ is_verified = false ก็ยังขายได้ถ้าเปิดอนุมัติอัตโนมัติ — ตอนนี้ต้อง is_verified เสมอ
 *
 * ทำ 2 อย่าง:
 *   - ปิดขาย: ร้าน is_verified แต่เจ้าของยังไม่ยืนยันตัวตน → is_verified = false (ยืนยันตัวตนเสร็จ = เปิดขายเองอัตโนมัติ)
 *   - เปิดขาย: ร้าน is_verified = false แต่เจ้าของยืนยันตัวตนแล้ว + เปิดอนุมัติอัตโนมัติ → is_verified = true
 *
 * ⚠️ ระบบไม่ได้เก็บว่าร้านไหน "แอดมินกดยืนยันเอง" ลงฐานข้อมูล (audit อยู่ใน log ไฟล์)
 *    ร้านที่แอดมินยืนยันเองแต่เจ้าของยังไม่ยืนยันตัวตนจะถูกปิดขายด้วย — ดูรายชื่อก่อน แล้วแอดมินกด "ยืนยันร้าน" ซ้ำได้ที่หลังบ้าน
 *
 * ค่าเริ่มต้น = ดูอย่างเดียว (ไม่แก้ข้อมูล)
 *
 * @example php artisan fresh-market:sync-seller-ekyc           (ดูรายชื่อที่จะเปลี่ยน)
 * @example php artisan fresh-market:sync-seller-ekyc --apply   (บันทึกจริง)
 */
class FreshMarketSyncSellerEkyc extends Command
{
    protected $signature = 'fresh-market:sync-seller-ekyc
                            {--apply : บันทึกจริง (ไม่ใส่ = ดูรายชื่ออย่างเดียว)}';

    protected $description = 'ตลาดสด: ปรับสถานะยืนยันร้านเดิมให้ตรงกฎ "ขายได้เมื่อเจ้าของร้านยืนยันตัวตน (eKYC) แล้ว"';

    public function handle(): int
    {
        if (! EkycGate::enforced()) {
            $this->warn('ปิดการบังคับยืนยันตัวตนอยู่ (ekyc.enforce = false) — ไม่มีอะไรต้องปรับ');

            return self::SUCCESS;
        }

        $autoApprove = FreshMarketListing::autoApproveSellers();
        $demote = [];
        $promote = [];

        FreshMarketSeller::query()->with('user')->chunkById(200, function ($sellers) use ($autoApprove, &$demote, &$promote) {
            foreach ($sellers as $seller) {
                $ownerVerified = $seller->user !== null && $seller->user->isKycVerified();

                if ($seller->is_verified && ! $ownerVerified) {
                    $demote[] = $seller;
                } elseif (! $seller->is_verified && $ownerVerified && $autoApprove) {
                    $promote[] = $seller;
                }
            }
        });

        $rows = fn (array $list, string $action) => array_map(fn (FreshMarketSeller $s) => [
            $s->id, $s->shop_name, $s->user_id, $s->user?->kyc_status ?? '-', $s->status_key, $action,
        ], $list);

        $this->table(
            ['seller_id', 'ร้าน', 'user_id', 'kyc_status', 'สถานะร้าน', 'จะทำ'],
            array_merge($rows($demote, 'ปิดขาย (รอยืนยันตัวตน)'), $rows($promote, 'เปิดขาย'))
        );
        $this->info('ปิดขาย '.count($demote).' ร้าน · เปิดขาย '.count($promote).' ร้าน');

        if (! $this->option('apply')) {
            $this->comment('ดูอย่างเดียว — ใส่ --apply เพื่อบันทึกจริง');

            return self::SUCCESS;
        }

        foreach ($demote as $seller) {
            $seller->update(['is_verified' => false]);
        }

        foreach ($promote as $seller) {
            $seller->update(['is_verified' => true]);
        }

        Log::info('FreshMarket: ปรับสถานะยืนยันร้านตามกฎ eKYC', [
            'demoted' => array_map(fn ($s) => $s->id, $demote),
            'promoted' => array_map(fn ($s) => $s->id, $promote),
        ]);

        $this->info('บันทึกแล้ว ✅ (ร้านที่ปิดขายจะเปิดขายเองทันทีที่เจ้าของยืนยันตัวตนในแอป)');

        return self::SUCCESS;
    }
}
