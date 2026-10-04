<?php

namespace App\Services\Media;

use App\Models\User;
use App\Support\FontFile;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Geometry\Factories\RectangleFactory;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Typography\FontFactory;

/**
 * รูปโปรไฟล์ถ่ายสด + ลายน้ำ (ไรเดอร์รอบ 2, 2026-10-04)
 *
 * ไฟล์ต้นฉบับเก็บใน private disk — คนอื่นเห็นได้เฉพาะรูปที่ฝังลายน้ำรหัสผู้ดู
 * ทุกที่ที่ส่งรูปคนให้แอป (ไรเดอร์ ผู้ซื้อ เจ้าของร้าน) ต้องเรียก urlFor() เท่านั้น (คีย์ `photo_url`)
 *
 * ขั้นตอน:
 *   1. store()  — ตรวจไฟล์ (ชนิด + ถอดรหัสจริง + ขนาดพิกเซล) → หมุนตาม EXIF → ย่อ ≤ 1024 px
 *                 → เข้ารหัส JPEG ใหม่ (GD ไม่เขียน EXIF/GPS กลับ) → profile-photos/{user_id}/{uuid}.jpg
 *   2. urlFor() — signed URL อายุ ~60 นาที ไปที่ api.v1.media.profile-photo (นอกกลุ่ม auth)
 *                 path มีแค่ id เจ้าของรูป + รหัสผู้ดู (HMAC ไม่ใช่ id จริง) + เวอร์ชันรูป
 *   3. watermarkedJpeg() — วาดลายน้ำเฉียงทั้งภาพ "THAI PROMPT · VIEWER <code> · <d/m/Y>" + ป้าย TP มุมขวาล่าง
 *                 แคชไฟล์ต่อ (เจ้าของรูป, เวอร์ชัน, ผู้ดู, วัน) ใน profile-photo-cache/{Ymd}/...
 *                 ลบแคชเก่าทุกวันด้วย `profile-photo:purge-cache`
 *
 * ⚠️ ลายน้ำเป็นตัวอักษรละตินเท่านั้น — prod ไม่มีฟอนต์ไทยในระบบ (ใช้ resources/fonts/DejaVuSans.ttf ที่อยู่ในรีโป)
 */
class ProfilePhotoService
{
    /** private disk (storage/app) — ห้ามใช้ public */
    public const DISK = 'local';

    public const ORIGINAL_DIR = 'profile-photos';

    public const CACHE_DIR = 'profile-photo-cache';

    /** ชื่อ route ของรูปพร้อมลายน้ำ (routes/api.php — ข้างเส้นเอกสารไรเดอร์) */
    public const ROUTE = 'api.v1.media.profile-photo';

    /** ขนาดไฟล์อัปโหลดสูงสุด (KB) */
    public const MAX_UPLOAD_KB = 8192;

    /** ด้านยาวสุดของรูปที่เก็บ (px) */
    public const MAX_EDGE = 1024;

    /** ด้านสั้นต่ำสุดของรูปต้นฉบับ (px) — เล็กกว่านี้เห็นหน้าไม่ชัด */
    public const MIN_EDGE = 160;

    /** จำนวนพิกเซลสูงสุดที่ยอมถอดรหัส (กัน decompression bomb กินแรมเครื่อง) */
    public const MAX_SOURCE_PIXELS = 50_000_000;

    public const JPEG_QUALITY = 85;

    public const RENDER_QUALITY = 82;

    /** อายุลิงก์ (นาที) — ปัดเวลาหมดอายุเป็นช่วงละ 30 นาที ให้ URL เดิมใช้แคชรูปในแอปได้ */
    public const URL_TTL_MINUTES = 60;

    public const URL_BUCKET_MINUTES = 30;

    public const TIMEZONE = 'Asia/Bangkok';

    /** ตัวอักษรของรหัสผู้ดู 32 ตัว (ไม่มี I O 0 1 ที่อ่านสับสน) */
    public const CODE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public const CODE_LENGTH = 6;

    /** รหัสเมื่อไม่รู้ตัวผู้ดู — มีตัว I ซึ่งไม่อยู่ใน CODE_ALPHABET จึงไม่ชนกับรหัสจริง */
    public const GUEST_CODE = 'PUBLIC';

    /** ฟอนต์ละตินที่ลองตามลำดับ — ตัวแรกอยู่ในรีโป ใช้ได้บน prod โดยไม่ต้องลงฟอนต์ระบบ */
    private const FONT_CANDIDATES = [
        'fonts/DejaVuSans.ttf', // resource_path()
        '/usr/share/fonts/dejavu/DejaVuSans.ttf',
        '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
        '/usr/share/fonts/dejavu-sans-fonts/DejaVuSans.ttf',
    ];

    private ?ImageManager $manager = null;

    private string|false|null $fontPath = null;

    // =====================================================
    // สถานะ / URL
    // =====================================================

    /**
     * URL รูปพร้อมลายน้ำของ $subject ที่ $viewer เปิดดู (signed URL อายุสั้น)
     *
     * ยังไม่มีรูปถ่ายสด → คืนรูปโปรไฟล์สาธารณะเดิม (LINE/อัปโหลดเก่า) ถ้ามี
     *
     * @return string|null null = ไม่มีรูปเลย
     */
    public function urlFor(User $subject, ?User $viewer): ?string
    {
        $version = $this->photoVersion($subject);

        if ($version === null) {
            return $this->legacyUrl($subject);
        }

        try {
            return URL::temporarySignedRoute(self::ROUTE, $this->urlExpiresAt(), [
                'subject' => (int) $subject->getKey(),
                'viewer' => $this->viewerCode($viewer?->getKey()),
                'version' => $version,
            ]);
        } catch (\Throwable $e) {
            Log::warning('ProfilePhoto: cannot sign url', [
                'subject_id' => $subject->getKey(),
                'error' => class_basename($e),
            ]);

            return null;
        }
    }

    /**
     * มีรูปถ่ายสดแล้วหรือยัง
     */
    public function hasPhoto(User $user): bool
    {
        return ! empty($user->profile_photo_private_path);
    }

    /**
     * เวอร์ชันของรูปปัจจุบัน (เปลี่ยนทุกครั้งที่ถ่ายใหม่) — null = ยังไม่มีรูป
     */
    public function photoVersion(User $user): ?string
    {
        $path = (string) ($user->profile_photo_private_path ?? '');

        return $path === '' ? null : substr(sha1($path), 0, 12);
    }

    /**
     * รหัสผู้ดู 6 ตัวที่ฝังในลายน้ำ — HMAC จาก app key (เดาย้อนเป็น user id ไม่ได้ ไม่เรียงตาม id)
     *
     * แอดมินหาว่ารูปหลุดจากบัญชีไหน: คำนวณ viewerCode() ของผู้ใช้ที่เกี่ยวข้องแล้วเทียบ
     */
    public function viewerCode(int|string|null $viewerId): string
    {
        $id = (int) $viewerId;
        if ($id <= 0) {
            return self::GUEST_CODE;
        }

        $hash = hash_hmac('sha256', 'profile-photo-viewer:'.$id, (string) config('app.key'), true);

        $code = '';
        for ($i = 0; $i < self::CODE_LENGTH; $i++) {
            $code .= self::CODE_ALPHABET[ord($hash[$i]) % 32];
        }

        return $code;
    }

    /**
     * ข้อมูลสำหรับ GET/POST /me/profile-photo
     *
     * @return array{has_photo: bool, taken_at: string|null, photo_url: string|null, required: bool}
     */
    public function statusPayload(User $user): array
    {
        $takenAt = $user->profile_photo_taken_at;

        return [
            'has_photo' => $this->hasPhoto($user),
            'taken_at' => $this->hasPhoto($user) && $takenAt ? $takenAt->toIso8601String() : null,
            'photo_url' => $this->urlFor($user, $user),
            'required' => filter_var(config('profile_photo.required', true), FILTER_VALIDATE_BOOL),
        ];
    }

    /**
     * รูปโปรไฟล์สาธารณะเดิม (ก่อนมีรูปถ่ายสด) — ไม่คืนรูปตัวอักษรอัตโนมัติ (ui-avatars)
     */
    public function legacyUrl(User $subject): ?string
    {
        $line = trim((string) ($subject->line_picture_url ?? ''));
        if ($line !== '' && preg_match('#^https?://#i', $line)) {
            return $line;
        }

        if (trim((string) ($subject->getRawOriginal('profile_picture') ?? '')) !== '') {
            $url = (string) $subject->profile_picture_url;
            if ($url !== '' && ! str_contains($url, 'ui-avatars.com')) {
                return $url;
            }
        }

        foreach (['google_avatar', 'facebook_picture_url'] as $column) {
            $value = trim((string) ($subject->getAttribute($column) ?? ''));
            if ($value !== '' && preg_match('#^https?://#i', $value)) {
                return $value;
            }
        }

        return null;
    }

    // =====================================================
    // อัปโหลด
    // =====================================================

    /**
     * บันทึกรูปถ่ายสดใหม่ (ทับรูปเดิม + ลบไฟล์เก่า)
     *
     * @throws ProfilePhotoException ไฟล์ไม่ใช่รูป / เล็กหรือใหญ่เกิน / บันทึกไม่สำเร็จ
     */
    public function store(User $user, UploadedFile $file): User
    {
        $jpeg = $this->normalizeUpload($file);

        $disk = $this->disk();
        $path = self::ORIGINAL_DIR.'/'.(int) $user->getKey().'/'.Str::uuid()->toString().'.jpg';

        if (! $disk->put($path, $jpeg)) {
            throw ProfilePhotoException::saveFailed();
        }

        try {
            // ล็อกแถวผู้ใช้ — กดถ่ายซ้ำพร้อมกัน 2 ครั้ง จะไม่เหลือไฟล์กำพร้า (คำขอหลังเห็น path ของคำขอแรกแล้วลบให้)
            $previous = DB::transaction(function () use ($user, $path) {
                /** @var User|null $locked */
                $locked = User::query()->whereKey($user->getKey())->lockForUpdate()->first();
                if (! $locked) {
                    throw ProfilePhotoException::saveFailed();
                }

                $old = $locked->profile_photo_private_path;

                // คอลัมน์ไม่อยู่ใน $fillable โดยตั้งใจ — แก้ได้จากเส้นนี้เท่านั้น
                $locked->forceFill([
                    'profile_photo_private_path' => $path,
                    'profile_photo_taken_at' => now(),
                ])->save();

                return $old;
            });
        } catch (\Throwable $e) {
            $this->deleteQuietly($path);

            if ($e instanceof ProfilePhotoException) {
                throw $e;
            }

            Log::error('ProfilePhoto: save failed', ['user_id' => $user->getKey(), 'error' => class_basename($e)]);

            throw ProfilePhotoException::saveFailed();
        }

        // ไฟล์ลบย้อนกลับไม่ได้ → ทำหลัง commit เท่านั้น
        if (is_string($previous) && $previous !== '' && $previous !== $path) {
            $this->deleteQuietly($previous);
        }
        $this->forgetRenderCache((int) $user->getKey());

        return $user->refresh();
    }

    /**
     * ตรวจ + แปลงไฟล์อัปโหลดเป็น JPEG ที่ปลอดภัย (≤ 1024 px, ไม่มี EXIF/GPS)
     *
     * @return string ไบต์ของ JPEG ใหม่
     *
     * @throws ProfilePhotoException
     */
    public function normalizeUpload(UploadedFile $file): string
    {
        $real = $file->getRealPath();
        if (! is_string($real) || $real === '' || ! is_file($real)) {
            throw ProfilePhotoException::invalid();
        }

        // ตรวจหัวไฟล์จริงก่อนถอดรหัส (ไม่เชื่อนามสกุล/Content-Type ที่ส่งมา)
        $info = @getimagesize($real);
        $allowed = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP];
        if (! is_array($info) || ! in_array($info[2] ?? null, $allowed, true)) {
            throw ProfilePhotoException::invalid();
        }

        $width = (int) ($info[0] ?? 0);
        $height = (int) ($info[1] ?? 0);
        if ($width <= 0 || $height <= 0) {
            throw ProfilePhotoException::invalid();
        }
        if ($width * $height > self::MAX_SOURCE_PIXELS) {
            throw ProfilePhotoException::tooLarge();
        }
        if (min($width, $height) < self::MIN_EDGE) {
            throw ProfilePhotoException::tooSmall();
        }

        $this->ensureMemory();

        try {
            // autoOrientation = หมุนพิกเซลตาม EXIF ก่อน (เพราะ EXIF จะหายตอนเข้ารหัสใหม่)
            $image = $this->manager()->read($real);
            $image->scaleDown(self::MAX_EDGE, self::MAX_EDGE);

            // GD เขียน JPEG ใหม่จากพิกเซลล้วน — EXIF/GPS/IPTC/ICC เดิมไม่ติดมา
            return (string) $image->toJpeg(quality: self::JPEG_QUALITY, strip: true);
        } catch (\Throwable $e) {
            Log::info('ProfilePhoto: decode failed', ['error' => class_basename($e)]);

            throw ProfilePhotoException::invalid();
        }
    }

    // =====================================================
    // รูปพร้อมลายน้ำ
    // =====================================================

    /**
     * ไบต์ JPEG ของรูป $subject พร้อมลายน้ำรหัส $viewerCode (วันนี้ เวลาไทย) — ใช้แคชถ้ามี
     *
     * @return string|null null = ไม่มีรูป / เวอร์ชันไม่ตรง (ถ่ายใหม่แล้ว) / ไฟล์ต้นฉบับหาย
     */
    public function watermarkedJpeg(User $subject, string $viewerCode, string $version): ?string
    {
        $current = $this->photoVersion($subject);
        if ($current === null || ! hash_equals($current, $version)) {
            return null;
        }

        $day = CarbonImmutable::now(self::TIMEZONE);
        $cachePath = sprintf('%s/%s/%d/%s-%s.jpg', self::CACHE_DIR, $day->format('Ymd'), (int) $subject->getKey(), $version, $viewerCode);

        $disk = $this->disk();

        try {
            if ($disk->exists($cachePath)) {
                $cached = $disk->get($cachePath);
                if (is_string($cached) && $cached !== '') {
                    return $cached;
                }
            }
        } catch (\Throwable) {
            // แคชอ่านไม่ได้ → วาดใหม่
        }

        $original = null;
        try {
            $original = $disk->get((string) $subject->profile_photo_private_path);
        } catch (\Throwable) {
            $original = null;
        }
        if (! is_string($original) || $original === '') {
            return null;
        }

        $jpeg = $this->renderWatermark($original, $viewerCode, $day->format('d/m/Y'));

        $this->writeCache($disk, $cachePath, $jpeg);

        return $jpeg;
    }

    /**
     * วาดลายน้ำลงรูป (ไม่แตะแคช) — ใช้ตรงๆ ในเทสต์ได้
     *
     * @param  string  $jpeg  ไบต์รูปต้นฉบับ (JPEG ที่ normalize แล้ว)
     * @param  string  $dateLabel  วันที่แบบ d/m/Y (เวลาไทย)
     */
    public function renderWatermark(string $jpeg, string $viewerCode, string $dateLabel): string
    {
        $this->ensureMemory();

        $manager = $this->manager();
        $image = $manager->read($jpeg);
        $width = $image->width();
        $height = $image->height();
        $short = max(1, min($width, $height));

        $font = $this->fontPath();
        $text = sprintf('THAI PROMPT · VIEWER %s · %s', $viewerCode, $dateLabel);
        if ($font === null) {
            // ฟอนต์ในตัวของ GD ไม่มีตัว "·" → ใช้ขีดแทน
            $text = str_replace('·', '-', $text);
        }

        // 1) ชั้นลายน้ำโปร่งใส: ตัวหนังสือขาวทึบ + ขอบเข้ม แล้วค่อยวางทับทั้งชั้นแบบโปร่ง
        //    (Intervention บังคับสีขอบตัวอักษรต้องทึบ — ทำความโปร่งตอนวางชั้นแทน)
        $overlay = $manager->create($width, $height);
        $this->drawTiledText($overlay, $text, $font, $width, $height, $short);
        $image->place($overlay, 'top-left', 0, 0, 38);

        // 2) ป้าย "TP" มุมขวาล่าง (พื้นกรมท่าโปร่ง + ขอบทอง)
        $this->drawCornerBadge($image, $font, $width, $height, $short);

        return (string) $image->toJpeg(quality: self::RENDER_QUALITY, strip: true);
    }

    // =====================================================
    // แคช
    // =====================================================

    /**
     * ลบแคชรูปพร้อมลายน้ำที่เก่ากว่า $keepDays วัน (นับตามวันไทย)
     *
     * @return int จำนวนโฟลเดอร์วันที่ลบ
     */
    public function purgeRenderCache(int $keepDays = 1): int
    {
        $disk = $this->disk();
        $cutoff = (int) CarbonImmutable::now(self::TIMEZONE)->subDays(max(0, $keepDays))->format('Ymd');
        $deleted = 0;

        foreach ($disk->directories(self::CACHE_DIR) as $dir) {
            $name = basename($dir);
            if (! preg_match('/^\d{8}$/', $name) || (int) $name >= $cutoff) {
                continue;
            }

            if ($disk->deleteDirectory($dir)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * ลบแคชของผู้ใช้คนหนึ่ง (วันนี้ + เมื่อวาน) — เรียกหลังถ่ายรูปใหม่
     */
    public function forgetRenderCache(int $userId): void
    {
        $today = CarbonImmutable::now(self::TIMEZONE);

        foreach ([$today, $today->subDay()] as $day) {
            try {
                $this->disk()->deleteDirectory(self::CACHE_DIR.'/'.$day->format('Ymd').'/'.$userId);
            } catch (\Throwable) {
                // แคชลบไม่ได้ไม่เป็นไร — เวอร์ชันรูปเปลี่ยนแล้ว แคชเก่าไม่ถูกใช้อีก
            }
        }
    }

    // =====================================================
    // ภายใน
    // =====================================================

    /**
     * เวลาหมดอายุของลิงก์ — ปัดลงเป็นช่วงละ 30 นาทีแล้วบวก 60 นาที
     * ⇒ ลิงก์ใช้ได้อย่างน้อย 30 นาที และ URL เหมือนเดิมตลอดช่วง (แอปแคชรูปได้)
     */
    private function urlExpiresAt(): \DateTimeInterface
    {
        $bucket = self::URL_BUCKET_MINUTES * 60;
        $start = intdiv(now()->getTimestamp(), $bucket) * $bucket;

        return CarbonImmutable::createFromTimestamp($start + self::URL_TTL_MINUTES * 60);
    }

    /**
     * วาดข้อความซ้ำเป็นแถวเฉียง 30° ให้คลุมทั้งภาพ (แถวคี่เลื่อนครึ่งช่อง)
     */
    private function drawTiledText(ImageInterface $overlay, string $text, ?string $font, int $width, int $height, int $short): void
    {
        $size = max(11, (int) round($short / 34));
        $angle = 30.0;

        if ($font !== null) {
            $box = @imagettfbbox($size * 0.76, 0, $font, $text);
            $textWidth = is_array($box) ? abs($box[2] - $box[0]) : (int) (mb_strlen($text) * $size * 0.6);
        } else {
            // ฟอนต์ในตัว GD เบอร์ 1 = กว้าง 5 px ต่ออักษร และหมุนไม่ได้
            $textWidth = strlen($text) * 5;
            $angle = 0.0;
        }

        $stepU = max(40, $textWidth + (int) round($size * 2.5));
        $stepV = max(14, (int) round($size * 4.2));

        $rad = deg2rad($angle);
        $ux = cos($rad);
        $uy = -sin($rad);
        $vx = sin($rad);
        $vy = cos($rad);

        $cx = $width / 2;
        $cy = $height / 2;
        $reach = sqrt($width * $width + $height * $height) / 2 + $textWidth;
        $nu = (int) ceil($reach / $stepU) + 1;
        $nv = (int) ceil($reach / $stepV) + 1;
        $margin = $textWidth / 2 + $size;

        for ($j = -$nv; $j <= $nv; $j++) {
            $shift = ($j % 2 !== 0) ? $stepU / 2 : 0;

            for ($i = -$nu; $i <= $nu; $i++) {
                $along = $i * $stepU + $shift;
                $x = $cx + $along * $ux + $j * $stepV * $vx;
                $y = $cy + $along * $uy + $j * $stepV * $vy;

                // ข้ามจุดที่ข้อความไม่แตะภาพเลย
                if ($x < -$margin || $x > $width + $margin || $y < -$margin || $y > $height + $margin) {
                    continue;
                }

                $overlay->text($text, (int) round($x), (int) round($y), function (FontFactory $f) use ($font, $size, $angle) {
                    if ($font !== null) {
                        $f->filename($font);
                    }
                    $f->size($size);
                    $f->color('rgb(255, 255, 255)');
                    $f->stroke('rgb(15, 23, 42)', 1);
                    $f->align('center');
                    $f->valign('middle');
                    $f->angle(-$angle);
                });
            }
        }
    }

    /**
     * ป้าย "TP" สี่เหลี่ยมเล็กมุมขวาล่าง
     */
    private function drawCornerBadge(ImageInterface $image, ?string $font, int $width, int $height, int $short): void
    {
        $badge = max(26, (int) round($short / 14));
        $margin = max(6, (int) round($short / 60));
        $x = $width - $badge - $margin;
        $y = $height - $badge - $margin;

        if ($x < 0 || $y < 0) {
            return;
        }

        $image->drawRectangle($x, $y, function (RectangleFactory $r) use ($badge) {
            $r->size($badge, $badge);
            $r->background('rgba(10, 22, 40, 0.6)');
            $r->border('rgb(212, 175, 55)', max(1, (int) round($badge / 28)));
        });

        $image->text('TP', $x + intdiv($badge, 2), $y + intdiv($badge, 2), function (FontFactory $f) use ($font, $badge) {
            if ($font !== null) {
                $f->filename($font);
            }
            $f->size(max(10, (int) round($badge * 0.46)));
            $f->color('rgb(244, 215, 120)');
            $f->align('center');
            $f->valign('middle');
        });
    }

    /**
     * เขียนแคชแบบไฟล์ชั่วคราว → ย้ายทับ (คนอ่านพร้อมกันไม่เจอไฟล์ครึ่งๆ)
     */
    private function writeCache(Filesystem $disk, string $cachePath, string $jpeg): void
    {
        $tmp = $cachePath.'.tmp-'.Str::random(8);

        try {
            if ($disk->put($tmp, $jpeg)) {
                if ($disk->exists($cachePath)) {
                    $disk->delete($tmp);

                    return;
                }
                $disk->move($tmp, $cachePath);
            }
        } catch (\Throwable $e) {
            $this->deleteQuietly($tmp);
            Log::info('ProfilePhoto: cache write skipped', ['error' => class_basename($e)]);
        }
    }

    /**
     * path ฟอนต์ละตินตัวแรกที่เป็นฟอนต์จริง (FontFile::isReal) — null = ใช้ฟอนต์ในตัวของ GD
     */
    private function fontPath(): ?string
    {
        if ($this->fontPath === null) {
            $paths = array_map(
                fn (string $p) => str_starts_with($p, '/') ? $p : resource_path($p),
                self::FONT_CANDIDATES
            );
            $this->fontPath = FontFile::firstReal($paths) ?? false;
        }

        return $this->fontPath === false ? null : $this->fontPath;
    }

    private function manager(): ImageManager
    {
        return $this->manager ??= new ImageManager(new GdDriver, autoOrientation: true, decodeAnimation: false, strip: true);
    }

    private function disk(): Filesystem
    {
        return Storage::disk(self::DISK);
    }

    private function deleteQuietly(string $path): void
    {
        try {
            if ($path !== '' && ! str_contains($path, '..')) {
                $this->disk()->delete($path);
            }
        } catch (\Throwable) {
            // ลบไม่ได้ไม่ถือว่าพัง
        }
    }

    /**
     * ถอดรหัสรูปใหญ่ด้วย GD ใช้แรมราว 5 ไบต์/พิกเซล — ยกเพดานชั่วคราวถ้าตั้งไว้ต่ำ
     */
    private function ensureMemory(): void
    {
        $current = (string) ini_get('memory_limit');
        if ($current === '-1') {
            return;
        }

        $bytes = $this->toBytes($current);
        if ($bytes > 0 && $bytes < 512 * 1024 * 1024) {
            @ini_set('memory_limit', '512M');
        }
    }

    private function toBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }

        $unit = strtolower(substr($value, -1));
        $number = (int) $value;

        return match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
