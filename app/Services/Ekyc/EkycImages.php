<?php

namespace App\Services\Ekyc;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;

/**
 * รูปของ eKYC: ตรวจ + แปลงเป็น JPEG สะอาด · เก็บแบบเข้ารหัส · ถอดรหัสให้แอดมิน · ลบ
 *
 * - เก็บบน private disk (config ekyc.disk) ที่ ekyc/{user_id}/{session_id}/{ชนิด}-{สุ่ม}.enc
 * - เนื้อไฟล์ = Crypt::encryptString(ไบต์ JPEG) → ไฟล์บนดิสก์ไม่ใช่รูป เปิดตรงๆ ไม่ได้
 * - JPEG ถูกเข้ารหัสใหม่ด้วย GD ทุกครั้ง (หมุนตาม EXIF แล้วตัด EXIF/GPS ทิ้ง)
 */
class EkycImages
{
    public const ROOT = 'ekyc';

    /** ด้านยาวสุดของรูปบัตรที่เก็บ/ส่งให้ AI (px) */
    public const CARD_MAX_EDGE = 2000;

    /** ด้านสั้นต่ำสุดของรูปบัตร (px) — เล็กกว่านี้อ่านตัวหนังสือไม่ออก */
    public const CARD_MIN_EDGE = 300;

    /** ด้านยาวสุดของเฟรมใบหน้า (px) */
    public const FRAME_MAX_EDGE = 1280;

    /** ด้านสั้นต่ำสุดของเฟรมใบหน้า (px) */
    public const FRAME_MIN_EDGE = 160;

    /** กันรูปยักษ์กินแรม (decompression bomb) — 25 ล้านพิกเซล ≈ 100 MB ใน GD */
    public const MAX_SOURCE_PIXELS = 25_000_000;

    public const JPEG_QUALITY = 90;

    /**
     * กรอบรูปหน้าบนบัตรประชาชนไทย (สัดส่วนของบัตร) — ใช้เมื่อ AI ไม่ได้ส่งกรอบมา
     * รูปถ่ายอยู่ด้านขวาล่างของบัตร (บัตรที่แอปครอปขอบด้วยตัวสแกนเอกสารแล้ว)
     *
     * @var array{0: float, 1: float, 2: float, 3: float} x, y, กว้าง, สูง
     */
    public const CARD_FACE_REGION = [0.70, 0.28, 0.28, 0.68];

    private ?ImageManager $manager = null;

    /**
     * ตรวจไฟล์ (ชนิดจริง + ขนาด) แล้วแปลงเป็น JPEG สะอาด
     *
     * @return string ไบต์ JPEG ใหม่
     *
     * @throws EkycException EKYC_BAD_IMAGE
     */
    public function normalize(UploadedFile $file, int $maxEdge, int $minEdge): string
    {
        $real = $file->getRealPath();
        if (! is_string($real) || $real === '' || ! is_file($real)) {
            throw EkycException::badImage();
        }

        // ดูหัวไฟล์จริง ไม่เชื่อนามสกุล/Content-Type ที่แอปส่งมา
        $info = @getimagesize($real);
        if (! is_array($info) || ! in_array($info[2] ?? null, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            throw EkycException::badImage('รองรับเฉพาะรูป JPG, PNG หรือ WebP');
        }

        $width = (int) ($info[0] ?? 0);
        $height = (int) ($info[1] ?? 0);
        if ($width <= 0 || $height <= 0 || $width * $height > self::MAX_SOURCE_PIXELS) {
            throw EkycException::badImage('รูปมีขนาดใหญ่เกินไป กรุณาถ่ายใหม่');
        }
        if (min($width, $height) < $minEdge) {
            throw EkycException::badImage('รูปเล็กเกินไป ระบบอ่านไม่ชัด กรุณาถ่ายใหม่ให้ใกล้ขึ้น');
        }

        $this->ensureMemory();

        try {
            $image = $this->manager()->read($real);
            $image->scaleDown($maxEdge, $maxEdge);

            return (string) $image->toJpeg(quality: self::JPEG_QUALITY, strip: true);
        } catch (\Throwable $e) {
            Log::info('eKYC: image decode failed', ['error' => class_basename($e)]);

            throw EkycException::badImage();
        }
    }

    /**
     * ตัดเฉพาะรูปหน้าจากรูปบัตร
     *
     * @param  mixed  $box  กรอบจาก AI (ถ้ามี): [x, y, w, h] หรือ {x, y, w, h} — เป็นสัดส่วน 0..1 หรือพิกเซลก็ได้
     * @return string|null JPEG ของหน้าบนบัตร · null = ตัดไม่ได้
     */
    public function cropCardFace(string $cardJpeg, mixed $box = null): ?string
    {
        try {
            $this->ensureMemory();
            $image = $this->manager()->read($cardJpeg);
            $width = $image->width();
            $height = $image->height();

            [$x, $y, $w, $h] = $this->resolveBox($box, $width, $height);

            // เผื่อขอบรอบหน้า 12% ให้แอดมินเห็นทรงผม/คาง
            $padX = (int) round($w * 0.12);
            $padY = (int) round($h * 0.12);
            $x = max(0, $x - $padX);
            $y = max(0, $y - $padY);
            $w = min($width - $x, $w + 2 * $padX);
            $h = min($height - $y, $h + 2 * $padY);

            if ($w < 24 || $h < 24) {
                return null;
            }

            $image->crop($w, $h, $x, $y);

            return (string) $image->toJpeg(quality: self::JPEG_QUALITY, strip: true);
        } catch (\Throwable $e) {
            Log::info('eKYC: card face crop failed', ['error' => class_basename($e)]);

            return null;
        }
    }

    /**
     * ลายนิ้วมือภาพ (difference hash 256 บิต + หน้ากากบิตที่เชื่อถือได้) — ไว้จับการเอาภาพชุดเดิมมาส่งซ้ำข้ามรอบ/ข้ามบัญชี
     *
     * ย่อภาพเหลือ 17×16 (เฉลี่ยทั้งพื้นที่) แล้วเทียบความสว่างช่องติดกัน → 1 บิตต่อคู่
     * คู่ที่สว่างใกล้กันมาก (พื้นเรียบ) บิตพลิกได้แค่จากการเข้ารหัส JPEG ใหม่ → ไม่นับ (หน้ากาก = 0)
     * ภาพเดิมที่ถูกเข้ารหัสใหม่/ปรับแสงทั้งภาพ ได้ค่าเกือบเท่าเดิม · ภาพที่ถ่ายสดใหม่ต่างกันหลายสิบบิต
     * ไม่ใช่ข้อมูลชีวมิติ (ย้อนกลับเป็นใบหน้าไม่ได้) แต่ลบไปพร้อมแถวเสมอ
     *
     * @return string|null hex 128 ตัว (64 = บิต · 64 = หน้ากาก) · null = อ่านภาพไม่ได้
     */
    public function dhash(string $jpeg): ?string
    {
        $src = @imagecreatefromstring($jpeg);
        if ($src === false) {
            return null;
        }

        $w = 17;
        $h = 16;
        $small = imagecreatetruecolor($w, $h);
        imagecopyresampled($small, $src, 0, 0, 0, 0, $w, $h, imagesx($src), imagesy($src));
        imagedestroy($src);

        // ต่างกันไม่ถึง ~3 ระดับสีเทา (สเกล 299/587/114 × 255) = บิตไม่นิ่ง
        $margin = 3000;
        $bits = '';
        $mask = '';
        for ($y = 0; $y < $h; $y++) {
            $prev = null;
            for ($x = 0; $x < $w; $x++) {
                $rgb = imagecolorat($small, $x, $y);
                $luma = (($rgb >> 16) & 0xFF) * 299 + (($rgb >> 8) & 0xFF) * 587 + ($rgb & 0xFF) * 114;
                if ($prev !== null) {
                    $bits .= $luma > $prev ? '1' : '0';
                    $mask .= abs($luma - $prev) > $margin ? '1' : '0';
                }
                $prev = $luma;
            }
        }
        imagedestroy($small);

        return self::bitsToHex($bits).self::bitsToHex($mask);
    }

    /**
     * จำนวนบิตที่ต่างกันของลายนิ้วมือ 2 ตัว (นับเฉพาะบิตที่นิ่งทั้งสองภาพ) · null = เทียบไม่ได้/ข้อมูลน้อยเกิน
     */
    public static function hashDistance(?string $a, ?string $b): ?int
    {
        [$bitsA, $maskA] = self::splitHash($a);
        [$bitsB, $maskB] = self::splitHash($b);
        if ($bitsA === null || $bitsB === null) {
            return null;
        }

        $both = $maskA & $maskB;
        // บิตที่นิ่งร่วมกันน้อยเกินไป = ไม่พอจะบอกว่าเป็นภาพเดียวกัน
        if (self::popcount($both) < 64) {
            return null;
        }

        return self::popcount(($bitsA ^ $bitsB) & $both);
    }

    /**
     * ลายนิ้วมือนี้มีข้อมูลพอจะใช้เทียบไหม — ภาพสีเรียบ/มืดสนิท (ปิดกล้อง) แทบไม่มีบิตที่นิ่ง
     * ภาพแบบนี้ "ซ้ำกัน" ได้เองโดยไม่ได้โกง จึงไม่เอามาใช้จับภาพซ้ำ
     */
    public static function hashInformative(?string $hash): bool
    {
        [$bits, $mask] = self::splitHash($hash);
        if ($bits === null) {
            return false;
        }

        $stable = self::popcount($mask);
        if ($stable < 64) {
            return false;
        }

        $ones = self::popcount($bits & $mask);

        return $ones >= (int) ($stable * 0.1) && $ones <= $stable - (int) ($stable * 0.1);
    }

    /**
     * @return array{0: string|null, 1: string|null} ไบต์ของบิต + ไบต์ของหน้ากาก
     */
    private static function splitHash(?string $hash): array
    {
        if (! is_string($hash) || strlen($hash) !== 128 || ! ctype_xdigit($hash)) {
            return [null, null];
        }

        return [(string) hex2bin(substr($hash, 0, 64)), (string) hex2bin(substr($hash, 64))];
    }

    private static function bitsToHex(string $bits): string
    {
        $hex = '';
        foreach (str_split($bits, 4) as $nibble) {
            $hex .= dechex((int) bindec($nibble));
        }

        return $hex;
    }

    private static function popcount(string $bytes): int
    {
        $count = 0;
        foreach (str_split($bytes) as $byte) {
            $n = ord($byte);
            while ($n) {
                $count += $n & 1;
                $n >>= 1;
            }
        }

        return $count;
    }

    /**
     * path ใหม่ของไฟล์ในรอบยืนยัน (สุ่มชื่อทุกครั้ง — เขียนทับกันพร้อมกันไม่ได้)
     */
    public function newPath(int $userId, string $sessionId, string $kind): string
    {
        $session = preg_replace('/[^a-zA-Z0-9-]/', '', $sessionId) ?: 'unknown';
        $kind = preg_replace('/[^a-z_]/', '', $kind) ?: 'file';

        return self::ROOT.'/'.$userId.'/'.$session.'/'.$kind.'-'.Str::lower(Str::random(16)).'.enc';
    }

    /**
     * เข้ารหัสแล้วเขียนลง private disk
     *
     * @throws \RuntimeException เขียนไม่สำเร็จ
     */
    public function putEncrypted(string $path, string $bytes): void
    {
        if (! $this->disk()->put($path, Crypt::encryptString($bytes))) {
            throw new \RuntimeException('eKYC: cannot write encrypted file');
        }
    }

    /**
     * อ่าน + ถอดรหัส · ไม่มีไฟล์/ถอดไม่ได้ = null
     */
    public function getDecrypted(?string $path): ?string
    {
        if (! $this->safePath($path)) {
            return null;
        }

        try {
            $raw = $this->disk()->get($path);
            if (! is_string($raw) || $raw === '') {
                return null;
            }

            return Crypt::decryptString($raw);
        } catch (DecryptException $e) {
            Log::warning('eKYC: cannot decrypt file', ['error' => class_basename($e)]);

            return null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * ลบไฟล์แบบไม่โยน error
     *
     * @param  array<int, string|null>  $paths
     */
    public function deleteQuietly(array $paths): void
    {
        foreach ($paths as $path) {
            if (! $this->safePath($path)) {
                continue;
            }

            try {
                $this->disk()->delete($path);
            } catch (\Throwable $e) {
                Log::info('eKYC: file delete skipped', ['error' => class_basename($e)]);
            }
        }
    }

    /**
     * ลบโฟลเดอร์ของรอบยืนยันทั้งโฟลเดอร์
     */
    public function deleteSessionDir(int $userId, ?string $sessionId): void
    {
        $session = preg_replace('/[^a-zA-Z0-9-]/', '', (string) $sessionId);
        if ($userId <= 0 || $session === '' || $session === null) {
            return;
        }

        $this->deleteDirectoryQuietly(self::ROOT.'/'.$userId.'/'.$session);
    }

    /**
     * ลบรูป eKYC ทั้งหมดของผู้ใช้ (ใช้ตอนลบบัญชี)
     */
    public function deleteUserDir(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        $this->deleteDirectoryQuietly(self::ROOT.'/'.$userId);
    }

    /**
     * path อยู่ใต้ ekyc/ และไม่มี .. (กันอ่าน/ลบไฟล์นอกโฟลเดอร์)
     */
    public function safePath(?string $path): bool
    {
        return is_string($path)
            && $path !== ''
            && str_starts_with($path, self::ROOT.'/')
            && ! str_contains($path, '..');
    }

    /**
     * แปลงกรอบจาก AI เป็นพิกเซล — ไม่มี/ผิดรูป = ตำแหน่งรูปหน้าตามแบบบัตรประชาชนไทย
     *
     * @return array{0: int, 1: int, 2: int, 3: int}
     */
    private function resolveBox(mixed $box, int $width, int $height): array
    {
        $values = null;
        if (is_array($box)) {
            if (array_is_list($box) && count($box) === 4) {
                $values = $box;
            } elseif (isset($box['x'], $box['y'], $box['w'], $box['h'])) {
                $values = [$box['x'], $box['y'], $box['w'], $box['h']];
            }
        }

        if ($values !== null && count(array_filter($values, 'is_numeric')) === 4) {
            $values = array_map('floatval', $values);
            // ค่าทุกตัว ≤ 1.5 = สัดส่วน · มากกว่านั้น = พิกเซล
            $relative = max($values) <= 1.5;
            [$x, $y, $w, $h] = $relative
                ? [$values[0] * $width, $values[1] * $height, $values[2] * $width, $values[3] * $height]
                : $values;

            if ($w > 0 && $h > 0) {
                $x = (int) max(0, min($width - 1, round($x)));
                $y = (int) max(0, min($height - 1, round($y)));

                return [$x, $y, (int) min($width - $x, round($w)), (int) min($height - $y, round($h))];
            }
        }

        [$rx, $ry, $rw, $rh] = self::CARD_FACE_REGION;

        return [(int) round($rx * $width), (int) round($ry * $height), (int) round($rw * $width), (int) round($rh * $height)];
    }

    private function deleteDirectoryQuietly(string $dir): void
    {
        try {
            $this->disk()->deleteDirectory($dir);
        } catch (\Throwable $e) {
            Log::info('eKYC: directory delete skipped', ['error' => class_basename($e)]);
        }
    }

    private function disk(): Filesystem
    {
        return Storage::disk((string) config('ekyc.disk', 'local'));
    }

    private function manager(): ImageManager
    {
        return $this->manager ??= new ImageManager(new GdDriver, autoOrientation: true, decodeAnimation: false, strip: true);
    }

    /**
     * GD ใช้แรมราว 5 ไบต์/พิกเซล — ยกเพดานชั่วคราวถ้าตั้งไว้ต่ำ
     */
    private function ensureMemory(): void
    {
        $current = trim((string) ini_get('memory_limit'));
        if ($current === '-1' || $current === '') {
            return;
        }

        $number = (int) $current;
        $bytes = match (strtolower(substr($current, -1))) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };

        if ($bytes > 0 && $bytes < 512 * 1024 * 1024) {
            @ini_set('memory_limit', '512M');
        }
    }
}
