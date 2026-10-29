<?php

namespace App\Services\Kyc;

use App\Models\KycVerification;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * 🔒 รูปเอกสาร KYC แบบเดิม (อัปโหลดเอง: รูปบัตรประชาชน + รูปถ่ายคู่บัตร) — ที่เก็บ · เปิดดู · ลบ
 *
 * (2026-10-29) เดิมเก็บบน public disk (storage/app/public/kyc/…) = ใครได้ URL /storage/… ก็เปิดบัตรประชาชนลูกค้าได้
 * ตอนนี้:
 * - ไฟล์ใหม่อยู่บน private disk (local) ที่ kyc-private/{user_id}/… — ไม่มี symlink ออกเว็บ
 * - เปิดดูผ่าน route ที่ตรวจสิทธิ์เท่านั้น: เจ้าของ (user.kyc.image) · แอดมิน (admin.kyc.image)
 *   · แอปมือถือ (api.v1.kyc.image = signed URL อายุสั้น เพราะ <img> ในแอปแนบ token ไม่ได้)
 * - ไฟล์เก่าบน public disk ยังอ่านได้ระหว่างย้าย (ย้ายด้วย `php artisan kyc:move-images-private`)
 *
 * รูป AI eKYC (ekyc/… เข้ารหัส) ไม่ได้อยู่ที่นี่ — ดู App\Services\Ekyc\EkycImages
 *
 * ⚠️ deploy.sh: storage/app/kyc-private ต้องอยู่ในรายการ -e ของ git clean + persist_private_storage_dir
 *    ไม่งั้นรูปถูกลบทุก deploy
 */
class KycDocumentStorage
{
    /** disk ส่วนตัว (storage/app) — เว็บเปิดตรงไม่ได้ */
    public const DISK = 'local';

    /** โฟลเดอร์ราก ของไฟล์ใหม่บน DISK */
    public const ROOT = 'kyc-private';

    /** disk เดิมที่ไฟล์เก่ายังค้างอยู่ระหว่างย้าย */
    public const LEGACY_DISK = 'public';

    /** โฟลเดอร์ของไฟล์เก่าบน public disk (kyc-temp = รูปชั่วคราวของ OCR ก่อน #2600) */
    public const LEGACY_DIRECTORIES = ['kyc', 'kyc-temp'];

    /** ชนิดรูป → คอลัมน์ใน kyc_verifications */
    public const KINDS = [
        'id_card' => 'id_card_image',
        'selfie' => 'selfie_image',
    ];

    /** อายุ signed URL ของแอปมือถือ (นาที) */
    public const SIGNED_URL_MINUTES = 30;

    /**
     * โฟลเดอร์เก็บรูปของผู้ใช้คนหนึ่งบน DISK
     */
    public function directoryFor(int $userId): string
    {
        return self::ROOT.'/'.$userId;
    }

    /**
     * พาธนี้เป็นไฟล์แบบใหม่ (private) หรือไม่
     */
    public function isPrivatePath(?string $path): bool
    {
        return is_string($path)
            && str_starts_with($path, self::ROOT.'/')
            && ! str_contains($path, '..');
    }

    /**
     * หาว่าไฟล์อยู่ disk ไหน — private ก่อน แล้วค่อย public (ไฟล์เก่าที่ยังไม่ย้าย)
     *
     * @return string|null ชื่อ disk · null = ไม่มีไฟล์ / พาธไม่ปลอดภัย / เป็นรูป eKYC
     */
    public function diskFor(?string $path): ?string
    {
        if (blank($path) || str_contains($path, '..') || str_starts_with($path, 'ekyc/')) {
            return null;
        }

        try {
            // พาธแบบใหม่อ่านจาก private disk เท่านั้น — พาธอื่นห้ามแตะ local disk (มีไฟล์ลับอื่นอยู่ใน storage/app)
            if ($this->isPrivatePath($path)) {
                return Storage::disk(self::DISK)->exists($path) ? self::DISK : null;
            }

            return Storage::disk(self::LEGACY_DISK)->exists($path) ? self::LEGACY_DISK : null;
        } catch (\Throwable $e) {
            // ดิสก์มีปัญหา (permission/symlink) — ถือว่าไม่มีไฟล์ ดีกว่าให้หน้าเว็บพัง
            Log::info('KYC document: disk check failed', ['error' => class_basename($e)]);

            return null;
        }
    }

    /**
     * มีไฟล์จริงอยู่บนดิสก์หรือไม่ (มีพาธในฐานข้อมูล ≠ มีไฟล์จริง)
     */
    public function exists(?string $path): bool
    {
        return $this->diskFor($path) !== null;
    }

    /**
     * พาธรูปตามชนิด (id_card | selfie) ของคำขอแบบเดิม · null = ชนิดผิด หรือเป็นแถว eKYC
     */
    public function pathFor(KycVerification $kyc, string $kind): ?string
    {
        $column = self::KINDS[$kind] ?? null;

        if ($column === null || $kyc->isEkyc()) {
            return null;
        }

        return $kyc->getAttribute($column);
    }

    /**
     * ส่งไฟล์รูปออกไป — ผู้เรียกต้องตรวจสิทธิ์ก่อนเสมอ · ไม่มีไฟล์ = 404
     */
    public function response(KycVerification $kyc, string $kind): Response
    {
        $path = $this->pathFor($kyc, $kind);
        $disk = $this->diskFor($path);

        if ($disk === null) {
            abort(404);
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION)) ?: 'jpg';

        return Storage::disk($disk)->response($path, 'kyc-'.$kyc->id.'-'.$kind.'.'.$extension, [
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'",
        ], 'inline');
    }

    /**
     * signed URL อายุสั้นให้แอปมือถือเปิดรูปของตัวเอง (route ไม่ต้องแนบ token)
     */
    public function signedUrl(KycVerification $kyc, string $kind): ?string
    {
        if (! $this->exists($this->pathFor($kyc, $kind))) {
            return null;
        }

        try {
            return URL::temporarySignedRoute(
                'api.v1.kyc.image',
                now()->addMinutes(self::SIGNED_URL_MINUTES),
                ['kycVerification' => $kyc->id, 'kind' => $kind]
            );
        } catch (\Throwable $e) {
            Log::warning('KYC document: cannot sign url', ['kyc_id' => $kyc->id, 'error' => class_basename($e)]);

            return null;
        }
    }

    /**
     * ลบไฟล์ (private หรือ public ตามที่อยู่จริง) — ลบไม่ได้ไม่ถือว่าพัง
     */
    public function delete(?string $path): void
    {
        $disk = $this->diskFor($path);
        if ($disk === null) {
            return;
        }

        try {
            Storage::disk($disk)->delete($path);
        } catch (\Throwable $e) {
            Log::info('KYC document: delete skipped', ['error' => class_basename($e)]);
        }
    }

    /**
     * มีแถว kyc_verifications ไหนยังชี้พาธนี้อยู่หรือไม่ (ไฟล์เก่าบางไฟล์ถูกใช้ร่วมกันหลายแถว)
     *
     * @param  int|null  $exceptId  ไม่นับแถวนี้ (แถวที่กำลังถูกลบ/แทนที่รูป)
     */
    public function isReferenced(string $path, ?int $exceptId = null): bool
    {
        return DB::table('kyc_verifications')
            ->when($exceptId !== null, fn ($q) => $q->where('id', '!=', $exceptId))
            ->where(function ($q) use ($path) {
                foreach (self::KINDS as $column) {
                    $q->orWhere($column, $path);
                }
            })
            ->exists();
    }

    /**
     * ลบไฟล์เมื่อไม่มีแถวอื่นชี้แล้วเท่านั้น — ใช้ตอนแทนที่/ลบแถว ไม่งั้นแถวที่ใช้ไฟล์ร่วมกันจะกลายเป็นรูปหาย
     *
     * @param  int|null  $exceptId  แถวที่กำลังถูกลบ/แทนที่รูป (ไม่นับว่ายังชี้อยู่)
     */
    public function deleteIfUnreferenced(?string $path, ?int $exceptId = null): void
    {
        if (blank($path) || $this->isReferenced($path, $exceptId)) {
            return;
        }

        $this->delete($path);
    }

    /**
     * ลบรูป KYC ทั้งหมดของผู้ใช้บน private disk (ใช้ตอนลบบัญชี — กวาดไฟล์กำพร้าด้วย)
     */
    public function deleteUserDir(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        try {
            $this->privateDisk()->deleteDirectory($this->directoryFor($userId));
        } catch (\Throwable $e) {
            Log::info('KYC document: directory delete skipped', ['error' => class_basename($e)]);
        }
    }

    public function privateDisk(): Filesystem
    {
        return Storage::disk(self::DISK);
    }

    public function legacyDisk(): Filesystem
    {
        return Storage::disk(self::LEGACY_DISK);
    }
}
