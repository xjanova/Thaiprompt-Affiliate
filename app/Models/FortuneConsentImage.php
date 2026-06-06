<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

/**
 * Fortune Consent Image Model
 *
 * รูป "กติกาก่อนจองคิว" ที่ส่งให้ลูกค้าพร้อมคำเตือน
 * (clone โครงจาก FortuneBanner — สุ่ม/หมุนเวียนได้ + เลือก scope ต่อรูป)
 *
 * usage_scope:
 *   - 'consent' = แสดงเฉพาะตอนอ่านกติกาก่อนสร้างบิล
 *   - 'cancel'  = ส่งเฉพาะตอนลูกค้าสร้างบิลแล้วกดยกเลิก (เจตนาเบี้ยว)
 *   - 'both'    = ใช้ทั้งสองจังหวะ
 *
 * @property int $id
 * @property string $name ชื่อภายใน
 * @property string|null $description คำอธิบาย
 * @property string $usage_scope ใช้ตอนไหน (consent|cancel|both)
 * @property string $image_path path รูปใน storage
 * @property int|null $width
 * @property int|null $height
 * @property int|null $file_size
 * @property bool $is_active
 * @property int $sort_order
 * @property int $send_count
 * @property \Carbon\Carbon|null $last_sent_at
 * @property int|null $uploaded_by
 */
class FortuneConsentImage extends Model
{
    use SoftDeletes;

    /** scope: แสดงตอนอ่านกติกา */
    public const SCOPE_CONSENT = 'consent';

    /** scope: ส่งตอนยกเลิกบิล */
    public const SCOPE_CANCEL = 'cancel';

    /** scope: ใช้ทั้งสองจังหวะ (consent + cancel — legacy ไม่รวม expire) */
    public const SCOPE_BOTH = 'both';

    /** scope: ส่งตอนบิลหมดเวลาเอง (auto-expire — โทนนุ่ม) */
    public const SCOPE_EXPIRE = 'expire';

    /** scope: ใช้ทุกจังหวะ (consent + cancel + expire) */
    public const SCOPE_ALL = 'all';

    protected $table = 'fortune_consent_images';

    protected $fillable = [
        'name',
        'description',
        'usage_scope',
        'image_path',
        'width',
        'height',
        'file_size',
        'is_active',
        'sort_order',
        'send_count',
        'last_sent_at',
        'uploaded_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'send_count' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'file_size' => 'integer',
        'last_sent_at' => 'datetime',
    ];

    /**
     * URL เต็มของรูป (HTTPS public URL ที่ FB/LINE เข้าถึงได้)
     *
     * เก็บที่ storage/app/public/fortune/consent/ → /storage/fortune/consent/...
     * (รอด deploy.sh git clean เพราะ exclude 'storage/app/public/*')
     */
    public function getImageUrlAttribute(): string
    {
        $path = (string) $this->image_path;

        // legacy public_path (ถ้ามี)
        if (str_starts_with($path, 'images/')) {
            return asset($path);
        }

        return asset('storage/'.ltrim($path, '/'));
    }

    /**
     * Scope: เฉพาะที่ active
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: เรียงตาม sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('id', 'asc');
    }

    /**
     * Scope: เฉพาะรูปที่ใช้กับจังหวะ $scope (รวม 'both' เสมอ)
     *
     * @param  string  $scope  'consent' | 'cancel'
     */
    public function scopeForScope($query, string $scope)
    {
        // ทุกจังหวะ → match scope ที่ระบุ + 'all' เสมอ
        $match = [$scope, self::SCOPE_ALL];

        // legacy 'both' = consent + cancel (ไม่รวม expire ที่เป็นจังหวะใหม่)
        if (in_array($scope, [self::SCOPE_CONSENT, self::SCOPE_CANCEL], true)) {
            $match[] = self::SCOPE_BOTH;
        }

        return $query->whereIn('usage_scope', array_values(array_unique($match)));
    }

    /**
     * เลือกรูปที่จะส่ง ตาม strategy + scope
     *
     * @param  string  $strategy  'random' | 'rotation' | 'sequential'
     * @param  string|null  $scope  'consent' | 'cancel' — null = ไม่กรอง scope
     * @return self|null null ถ้าไม่มีรูป active ที่ match
     */
    public static function pickByStrategy(string $strategy = 'random', ?string $scope = null): ?self
    {
        $query = self::active()->ordered();

        if ($scope !== null) {
            $query->forScope($scope);
        }

        $images = $query->get();

        if ($images->isEmpty()) {
            return null;
        }

        return match ($strategy) {
            'random' => $images->random(),
            'rotation' => self::pickRotation($images, $scope),
            'sequential' => $images->first(),
            default => $images->random(),
        };
    }

    /**
     * Rotation: วนรอบด้วย cache counter (แยก counter ต่อ scope กันชนกัน)
     */
    protected static function pickRotation($images, ?string $scope = null): self
    {
        $count = $images->count();
        $cacheKey = 'fortune_consent_rotation'.($scope ? ":{$scope}" : '');
        $index = (int) Cache::increment($cacheKey) % $count;

        return $images[$index];
    }

    /**
     * เพิ่มสถิติการส่ง
     */
    public function recordSend(): void
    {
        $this->increment('send_count');
        $this->update(['last_sent_at' => now()]);
    }

    /**
     * 🔴 ส่งคำเตือน "ยกเลิกบิล" (รูป scope=cancel + ข้อความเตือนแรง) ผ่าน platform service
     *
     * ใช้ร่วมกันโดย:
     *   - FortuneConsentGateTrait::sendCancelConsentOrWakeup (ลูกค้ากดยกเลิกแบบเบี้ยว)
     *   - FortuneReading::cancelExpiredPendingBills (ระบบยกเลิกอัตโนมัติเมื่อบิลหมดเวลา 30 นาที)
     *
     * helper เช็ค toggle `fortune_consent_cancel_enabled` เอง → ปิด = return false (caller ใช้ wakeup เดิม)
     *
     * @param  mixed  $platformService  FB/LINE service (ต้องมี sendImage()/sendMessage())
     * @return bool true = ส่งคำเตือน consent สำเร็จ / false = ปิดระบบ หรือ service ไม่พร้อม
     */
    public static function deliverCancelWarning($platformService, string $userId, string $mode = 'cancel'): bool
    {
        if (empty($userId) || ! $platformService) {
            return false;
        }

        $settings = \App\Models\FortuneTellingSetting::getSettings();

        // เลือก toggle / scope รูป / ข้อความ ตามโหมด
        //   'expire' = บิลหมดเวลาเอง (โทนนุ่ม + รูป scope=expire) — ลูกค้าอาจแค่ลืม
        //   'cancel' = ลูกค้ากดยกเลิกแบบเบี้ยว (โทนแรง + รูป scope=cancel)
        if ($mode === 'expire') {
            if (! $settings->isConsentExpireEnabled()) {
                return false;
            }
            $scope = self::SCOPE_EXPIRE;
            $text = $settings->getConsentExpireText();
        } else {
            if (! $settings->isConsentCancelEnabled()) {
                return false;
            }
            $scope = self::SCOPE_CANCEL;
            $text = $settings->getConsentCancelText();
        }

        try {
            $img = self::pickByStrategy(
                $settings->fortune_consent_pick_strategy ?? 'random',
                $scope
            );
            if ($img) {
                $platformService->sendImage($userId, $img->image_url);
                $img->recordSend();
                usleep(400000); // 400ms ให้รูปมาก่อน text
            }

            $platformService->sendMessage($userId, $text);

            return true;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Fortune: deliverCancelWarning failed (non-blocking)', [
                'user_id' => $userId,
                'mode' => $mode,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
