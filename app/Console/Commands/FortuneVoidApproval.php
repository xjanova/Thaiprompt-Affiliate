<?php

namespace App\Console\Commands;

use App\Models\FortuneReading;
use Illuminate\Console\Command;

/**
 * ⛔ (2026-06-08) ยกเลิกการอนุมัติบิลดูดวงที่อนุมัติผิด (Void Approval)
 *
 * Use case: แอดมินกด Force Approve ผิดบิล/ผิดคน → บิลขึ้น "จ่ายแล้ว ✓" ทั้งที่ลูกค้าไม่ได้จ่าย
 *   command นี้ถอยกลับเป็น "ยังไม่จ่าย" + ปลด UPA/SMS + ดึงคืน commission
 *   (engine เดียวกับปุ่มยกเลิกอนุมัติในหน้าเว็บ — FortuneReading::voidApproval)
 *
 * รองรับทั้ง Celtic + Deep (รับได้ทั้ง bill_reference และ numeric id)
 *
 * Usage:
 *   php artisan fortune:void-approval FTU-260605-U1661 FTU-260530-H3965
 *   php artisan fortune:void-approval 4968 --reason="แอดมินอนุมัติผิด ลูกค้าไม่ได้จ่าย"
 *   php artisan fortune:void-approval 4968 --dry       # แสดงเฉยๆ ไม่ทำจริง
 *   php artisan fortune:void-approval 4106 --force     # ยอมยกเลิกแม้ลูกค้าใช้บริการไปแล้ว
 */
class FortuneVoidApproval extends Command
{
    protected $signature = 'fortune:void-approval
                            {bills* : เลขบิล (FTU-...) หรือ id ของ reading}
                            {--reason= : เหตุผลที่ยกเลิก (เก็บใน log)}
                            {--force : ยอมยกเลิกแม้ลูกค้าใช้บริการไปแล้ว (เปิดไพ่/ได้คำทำนาย)}
                            {--dry : Dry run — แสดงสิ่งที่จะทำ ไม่บันทึกจริง}';

    protected $description = 'ยกเลิกการอนุมัติบิลดูดวงที่อนุมัติผิด — คืนเป็นยังไม่จ่าย + ปลด UPA/SMS + ดึงคืน commission';

    public function handle(): int
    {
        $bills = (array) $this->argument('bills');
        $reason = $this->option('reason') ?: 'ยกเลิกผ่าน CLI (อนุมัติผิด)';
        $force = (bool) $this->option('force');
        $isDry = (bool) $this->option('dry');

        $ok = 0;
        $skip = 0;
        $fail = 0;

        foreach ($bills as $token) {
            $token = trim((string) $token);
            if ($token === '') {
                continue;
            }

            $reading = is_numeric($token)
                ? FortuneReading::find((int) $token)
                : FortuneReading::where('bill_reference', $token)->first();

            if (! $reading) {
                $this->error("  ❌ ไม่พบบิล: {$token}");
                $fail++;

                continue;
            }

            if (! $reading->is_paid) {
                $this->warn("  ⏭️  #{$reading->id} {$reading->bill_reference} — ยังไม่จ่าย ข้าม");
                $skip++;

                continue;
            }

            // กันบิลที่ลูกค้าใช้บริการไปแล้ว (ต้อง --force)
            $consumed = $reading->reading_type === FortuneReading::READING_TYPE_CELTIC_CROSS
                ? ($reading->getCelticPickedCount() > 0 || (int) ($reading->celtic_questions_used ?? 0) > 0)
                : ! empty($reading->deep_response);

            if ($consumed && ! $force) {
                $this->warn("  ⚠️  #{$reading->id} {$reading->bill_reference} — ลูกค้าใช้บริการไปแล้ว ข้าม (ใส่ --force ถ้าต้องการ)");
                $skip++;

                continue;
            }

            $this->info("  #{$reading->id} {$reading->bill_reference} ({$reading->reading_type}) — ".($reading->facebook_user_name ?? '-'));

            if ($isDry) {
                $this->line('     [DRY] จะคืน is_paid=false + ปลด UPA/SMS + ดึงคืน commission');

                continue;
            }

            $result = $reading->voidApproval($reason, null);

            if ($result['ok'] ?? false) {
                $this->line('     ✅ '.implode(', ', $result['reverted']));
                foreach (($result['warnings'] ?? []) as $w) {
                    $this->warn('     ⚠️ '.$w);
                }
                $ok++;
            } else {
                $this->error('     ❌ '.($result['message'] ?? 'ล้มเหลว'));
                $fail++;
            }
        }

        $this->newLine();
        $this->info("📊 สรุป: ok={$ok} skip={$skip} fail={$fail}");

        return $fail > 0 ? 1 : 0;
    }
}
