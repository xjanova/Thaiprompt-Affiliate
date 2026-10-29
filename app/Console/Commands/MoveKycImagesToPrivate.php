<?php

namespace App\Console\Commands;

use App\Models\KycVerification;
use App\Services\Kyc\KycDocumentStorage;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * 🔒 ย้ายรูปเอกสาร KYC แบบเดิม (บัตร/คู่บัตร) จาก public disk ไป private disk แล้วอัปเดตพาธในฐานข้อมูล (2026-10-29)
 *
 * เดิมรูปอยู่ storage/app/public/kyc/… = ใครได้ URL /storage/… ก็เปิดบัตรประชาชนลูกค้าได้
 * โค้ดใหม่เขียนลง private disk แล้ว และอ่านได้ทั้ง 2 ที่ระหว่างย้าย (KycDocumentStorage::diskFor)
 *
 * ทีละไฟล์: คัดลอก → ตรวจขนาด → อัปเดตแถวแบบมีเงื่อนไข (พาธยังเป็นค่าเดิม) → ลบต้นฉบับบน public
 * - รันซ้ำได้ (idempotent): แถวที่ย้ายแล้วมีพาธ kyc-private/… จะไม่ถูกเลือกอีก
 * - แถวถูกแก้ระหว่างย้าย → ทิ้งสำเนาใหม่ ไม่แตะแถว · ไฟล์บน public หาย → นับเป็น missing ไม่แตะแถว
 * - ไม่แตะแถว AI eKYC (รูปเข้ารหัสอยู่ private disk อยู่แล้ว) · ไม่เปลี่ยน updated_at · ไม่ยิง observer/แจ้งเตือน
 * - --orphans: ไฟล์ใต้ public/kyc และ public/kyc-temp ที่ไม่มีแถวไหนชี้ (รูปที่ถูกแทนที่ / OCR ชั่วคราวที่ค้าง)
 *   → ย้ายเข้า private disk เหมือนกัน (ไม่ลบทิ้ง — ลบเองได้ภายหลังที่ kyc-private/orphans และ kyc-private/{user_id}/orphan-*)
 *
 * Usage:
 *   php artisan kyc:move-images-private --dry-run      ดูจำนวนก่อน ไม่แตะอะไร
 *   php artisan kyc:move-images-private --isolated     ย้ายจริง (กันรันซ้อน)
 *   php artisan kyc:move-images-private --orphans --isolated
 */
class MoveKycImagesToPrivate extends Command implements Isolatable
{
    protected $signature = 'kyc:move-images-private
                            {--dry-run : แสดงจำนวนที่จะย้าย ไม่แตะไฟล์หรือฐานข้อมูล}
                            {--orphans : ย้ายไฟล์กำพร้าใต้ public/kyc และ public/kyc-temp ที่ไม่มีแถวชี้ด้วย}
                            {--chunk=200 : จำนวนแถวต่อรอบ}';

    protected $description = 'ย้ายรูปเอกสาร KYC แบบเดิมจาก public disk ไป private disk และอัปเดตพาธในฐานข้อมูล';

    /** @var array<string, int> */
    private array $stats = [
        'rows' => 0,
        'moved' => 0,
        'missing' => 0,
        'skipped' => 0,
        'failed' => 0,
        'orphans_moved' => 0,
        'orphans_failed' => 0,
    ];

    public function __construct(private readonly KycDocumentStorage $documents)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunk = max(1, (int) $this->option('chunk'));

        if ($dryRun) {
            $this->warn('🔍 dry-run — ไม่แตะไฟล์หรือฐานข้อมูล');
        }

        $this->legacyRows()->chunkById($chunk, function ($rows) use ($dryRun) {
            foreach ($rows as $row) {
                $this->stats['rows']++;

                foreach (KycDocumentStorage::KINDS as $kind => $column) {
                    $path = $row->{$column};
                    if (blank($path) || $this->documents->isPrivatePath($path)) {
                        continue;
                    }

                    $this->stats[$this->moveRowImage((int) $row->id, (int) $row->user_id, $column, $kind, $path, $dryRun)]++;
                }
            }
        });

        if ($this->option('orphans')) {
            $this->moveOrphans($dryRun);
        }

        $this->table(['รายการ', 'จำนวน'], collect($this->stats)->map(fn ($count, $key) => [$key, $count])->values()->all());

        if (! $dryRun) {
            Log::info('KYC images moved to private disk', $this->stats);
        }

        if ($this->stats['failed'] > 0 || $this->stats['orphans_failed'] > 0) {
            $this->error('มีไฟล์ที่ย้ายไม่สำเร็จ — ดู storage/logs แล้วรันซ้ำได้ (ไฟล์ที่ย้ายแล้วจะถูกข้าม)');

            return self::FAILURE;
        }

        $this->info($dryRun ? 'dry-run เสร็จ' : 'ย้ายเสร็จ');

        return self::SUCCESS;
    }

    /**
     * แถวแบบเดิม (ไม่ใช่ eKYC) ที่ยังมีพาธบน public disk อย่างน้อย 1 รูป
     */
    private function legacyRows()
    {
        return DB::table('kyc_verifications')
            ->select(['id', 'user_id', ...array_values(KycDocumentStorage::KINDS)])
            ->where(fn ($q) => $q->whereNull('method')->orWhere('method', '!=', KycVerification::METHOD_EKYC))
            ->where(function ($q) {
                foreach (KycDocumentStorage::KINDS as $column) {
                    $q->orWhere(fn ($w) => $w->whereNotNull($column)
                        ->where($column, '!=', '')
                        ->where($column, 'not like', KycDocumentStorage::ROOT.'/%'));
                }
            });
    }

    /**
     * ย้ายรูป 1 คอลัมน์ของ 1 แถว
     *
     * @return string moved | missing | skipped | failed
     */
    private function moveRowImage(int $id, int $userId, string $column, string $kind, string $path, bool $dryRun): string
    {
        if (str_contains($path, '..') || str_starts_with($path, 'ekyc/')) {
            return 'skipped';
        }

        $legacy = $this->documents->legacyDisk();
        if (! $legacy->exists($path)) {
            return 'missing';
        }

        if ($dryRun) {
            return 'moved';
        }

        $target = $this->documents->directoryFor($userId).'/'.$kind.'-'.Str::random(40).'.'.$this->extensionOf($path);

        if (! $this->copyToPrivate($path, $target)) {
            Log::warning('KYC move: copy failed', ['kyc_id' => $id, 'column' => $column]);

            return 'failed';
        }

        // อัปเดตเฉพาะเมื่อพาธยังเป็นค่าเดิม (ผู้ใช้อัปโหลดใหม่ระหว่างย้าย → ไม่ทับของใหม่)
        $updated = DB::table('kyc_verifications')
            ->where('id', $id)
            ->where($column, $path)
            ->update([$column => $target]);

        if ($updated === 0) {
            $this->documents->privateDisk()->delete($target);

            return 'skipped';
        }

        // ลบต้นฉบับเมื่อไม่มีแถวอื่นชี้ไฟล์เดียวกันแล้ว (ไม่งั้นแถวนั้นจะกลายเป็นรูปหาย)
        if (! $this->referencedByAnyRow($path)) {
            $legacy->delete($path);
        }

        return 'moved';
    }

    /**
     * ไฟล์ใต้ public/kyc และ public/kyc-temp ที่ไม่มีแถวไหนชี้ → ย้ายเข้า private disk
     */
    private function moveOrphans(bool $dryRun): void
    {
        $legacy = $this->documents->legacyDisk();

        // พาธบน public ที่ยังมีแถวชี้ (ไฟล์หาย/ย้ายไม่สำเร็จ) — ห้ามแตะ
        $referenced = [];
        foreach (KycDocumentStorage::KINDS as $column) {
            foreach (DB::table('kyc_verifications')->whereNotNull($column)->where($column, 'not like', KycDocumentStorage::ROOT.'/%')->pluck($column) as $path) {
                $referenced[$path] = true;
            }
        }

        foreach (KycDocumentStorage::LEGACY_DIRECTORIES as $directory) {
            try {
                $files = $legacy->allFiles($directory);
            } catch (\Throwable $e) {
                $this->warn("อ่านโฟลเดอร์ {$directory} ไม่ได้: ".class_basename($e));

                continue;
            }

            foreach ($files as $file) {
                if (isset($referenced[$file]) || str_starts_with(basename($file), '.') || str_contains($file, '..')) {
                    continue;
                }

                if ($dryRun) {
                    $this->stats['orphans_moved']++;

                    continue;
                }

                $target = $this->orphanTarget($file);
                if ($this->copyToPrivate($file, $target)) {
                    $legacy->delete($file);
                    $this->stats['orphans_moved']++;
                } else {
                    $this->stats['orphans_failed']++;
                }
            }
        }
    }

    /**
     * ที่เก็บไฟล์กำพร้า — รู้เจ้าของจากพาธ kyc/{user_id}/… → ไปอยู่โฟลเดอร์ผู้ใช้ (ลบพร้อมบัญชีได้)
     */
    private function orphanTarget(string $file): string
    {
        $suffix = Str::random(16).'.'.$this->extensionOf($file);

        if (preg_match('#^kyc/(\d+)/#', $file, $m) === 1) {
            return $this->documents->directoryFor((int) $m[1]).'/orphan-'.$suffix;
        }

        return KycDocumentStorage::ROOT.'/orphans/'.$suffix;
    }

    /**
     * คัดลอกจาก public ไป private แล้วตรวจขนาด — ไม่ตรง = ลบสำเนาทิ้ง
     */
    private function copyToPrivate(string $from, string $to): bool
    {
        try {
            $bytes = $this->documents->legacyDisk()->get($from);
            if (! is_string($bytes)) {
                return false;
            }

            $private = $this->documents->privateDisk();
            if (! $private->put($to, $bytes) || $private->size($to) !== strlen($bytes)) {
                $private->delete($to);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('KYC move: copy exception', ['error' => class_basename($e)]);

            return false;
        }
    }

    private function referencedByAnyRow(string $path): bool
    {
        return DB::table('kyc_verifications')
            ->where(function ($q) use ($path) {
                foreach (KycDocumentStorage::KINDS as $column) {
                    $q->orWhere($column, $path);
                }
            })
            ->exists();
    }

    private function extensionOf(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return preg_match('/^[a-z0-9]{1,5}$/', $extension) === 1 ? $extension : 'img';
    }
}
