<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

/**
 * Fortune Banner Model
 *
 * รูปแบนเนอร์ที่ส่งให้ลูกค้าเมื่อ reaction/comment/welcome
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string $image_path
 * @property int|null $width
 * @property int|null $height
 * @property int|null $file_size
 * @property bool $is_active
 * @property int $sort_order
 * @property int $send_count
 * @property \Carbon\Carbon|null $last_sent_at
 * @property int|null $uploaded_by
 */
class FortuneBanner extends Model
{
    use SoftDeletes;

    protected $table = 'fortune_banners';

    protected $fillable = [
        'name',
        'description',
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
     * URL เต็มของแบนเนอร์ (HTTPS public URL ที่ FB/LINE เข้าถึงได้)
     *
     * Legacy: 'images/fortune/banners/banner-X.jpg' → /images/fortune/banners/banner-X.jpg
     *   (เก่า — เก็บที่ public_path() ถูก deploy.sh git clean -fdx ลบ จึงมักจะ 404)
     *
     * New: 'fortune/banners/banner-X.jpg' → /storage/fortune/banners/banner-X.jpg
     *   (ใหม่ — เก็บที่ storage/app/public/ ผ่าน Laravel Storage convention,
     *    deploy.sh excludes 'storage/app/public/*' → ปลอดภัยทุก deploy)
     */
    public function getImageUrlAttribute(): string
    {
        $path = (string) $this->image_path;

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
     * เลือก banner ที่จะส่ง ตาม strategy
     *
     * @param  string  $strategy  'random' | 'rotation' | 'sequential'
     * @return self|null
     */
    public static function pickByStrategy(string $strategy = 'rotation'): ?self
    {
        $banners = self::active()->ordered()->get();

        if ($banners->isEmpty()) {
            return null;
        }

        return match ($strategy) {
            'random' => $banners->random(),
            'rotation' => self::pickRotation($banners),
            'sequential' => $banners->first(), // สำหรับ sequential = banner แรกตาม sort_order
            default => $banners->random(),
        };
    }

    /**
     * Rotation: วนรอบโดยใช้ cache counter
     * banner คนละตัวต่อการเรียกแต่ละครั้ง
     */
    protected static function pickRotation($banners): self
    {
        $count = $banners->count();
        $index = (int) Cache::increment('fortune_banner_rotation') % $count;

        return $banners[$index];
    }

    /**
     * เพิ่มสถิติการส่ง
     */
    public function recordSend(): void
    {
        $this->increment('send_count');
        $this->update(['last_sent_at' => now()]);
    }
}
