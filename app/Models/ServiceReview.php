<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ServiceReview Model - รีวิวบริการ
 *
 * เก็บรีวิวและคะแนนจากลูกค้าหลังใช้บริการ
 * แต่ละการจอง review ได้เพียงครั้งเดียว (unique booking_id)
 *
 * @property int $id
 * @property int $booking_id
 * @property int $user_id ลูกค้าที่รีวิว
 * @property int $provider_id ผู้ให้บริการ
 * @property int|null $service_id บริการ
 * @property int $rating คะแนน 1-5
 * @property string|null $comment ความคิดเห็น
 * @property int|null $quality_rating คะแนนคุณภาพ
 * @property int|null $punctuality_rating คะแนนตรงเวลา
 * @property int|null $friendliness_rating คะแนนความเป็นมิตร
 * @property array|null $images รูปภาพรีวิว
 * @property bool $is_verified ยืนยันว่าใช้บริการจริง
 * @property bool $is_visible แสดงรีวิว
 * @property string|null $provider_response คำตอบจาก provider
 * @property \Carbon\Carbon|null $responded_at เวลาตอบกลับ
 * @property int $helpful_count จำนวนคนกด "มีประโยชน์"
 * @property int $report_count จำนวนคนรายงาน
 */
class ServiceReview extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'service_reviews';

    protected $fillable = [
        'booking_id',
        'user_id',
        'provider_id',
        'service_id',
        'rating',
        'comment',
        'quality_rating',
        'punctuality_rating',
        'friendliness_rating',
        'images',
        'is_verified',
        'is_visible',
        'provider_response',
        'responded_at',
        'helpful_count',
        'report_count',
    ];

    protected $casts = [
        'rating' => 'integer',
        'quality_rating' => 'integer',
        'punctuality_rating' => 'integer',
        'friendliness_rating' => 'integer',
        'images' => 'array',
        'is_verified' => 'boolean',
        'is_visible' => 'boolean',
        'responded_at' => 'datetime',
        'helpful_count' => 'integer',
        'report_count' => 'integer',
    ];

    //===========================================
    // Relationships
    //===========================================

    /**
     * การจอง
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(ServiceBooking::class, 'booking_id');
    }

    /**
     * ลูกค้าที่รีวิว
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * ผู้ให้บริการที่ถูกรีวิว
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(ServiceProvider::class, 'provider_id');
    }

    /**
     * บริการที่ใช้
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    //===========================================
    // Scopes
    //===========================================

    /**
     * Scope: รีวิวที่แสดงผล
     */
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    /**
     * Scope: รีวิวที่ยืนยันแล้ว
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope: เรียงตามคะแนน
     */
    public function scopeOrderByRating($query, string $direction = 'desc')
    {
        return $query->orderBy('rating', $direction);
    }

    /**
     * Scope: เฉพาะคะแนนดี (4-5 ดาว)
     */
    public function scopePositive($query)
    {
        return $query->where('rating', '>=', 4);
    }

    /**
     * Scope: เฉพาะคะแนนแย่ (1-2 ดาว)
     */
    public function scopeNegative($query)
    {
        return $query->where('rating', '<=', 2);
    }

    /**
     * Scope: ที่มีรูปภาพ
     */
    public function scopeWithImages($query)
    {
        return $query->whereNotNull('images')
            ->whereRaw('JSON_LENGTH(images) > 0');
    }

    //===========================================
    // Methods - Review Management
    //===========================================

    /**
     * Provider ตอบกลับรีวิว
     */
    public function respondByProvider(string $response): void
    {
        $this->update([
            'provider_response' => $response,
            'responded_at' => now(),
        ]);
    }

    /**
     * กด "รีวิวนี้มีประโยชน์"
     */
    public function markAsHelpful(): void
    {
        $this->increment('helpful_count');
    }

    /**
     * รายงานรีวิวนี้
     */
    public function report(): void
    {
        $this->increment('report_count');
    }

    /**
     * ซ่อนรีวิว
     */
    public function hide(): void
    {
        $this->update(['is_visible' => false]);
    }

    /**
     * แสดงรีวิว
     */
    public function show(): void
    {
        $this->update(['is_visible' => true]);
    }

    /**
     * ยืนยันว่าใช้บริการจริง
     */
    public function verify(): void
    {
        $this->update(['is_verified' => true]);
    }

    //===========================================
    // Methods - Ratings
    //===========================================

    /**
     * คำนวณคะแนนเฉลี่ยจากคะแนนย่อย
     */
    public function getAverageSubRatingsAttribute(): float
    {
        $ratings = array_filter([
            $this->quality_rating,
            $this->punctuality_rating,
            $this->friendliness_rating,
        ]);

        if (empty($ratings)) {
            return 0;
        }

        return round(array_sum($ratings) / count($ratings), 1);
    }

    /**
     * ตรวจสอบว่ามีคะแนนย่อยหรือไม่
     */
    public function hasSubRatings(): bool
    {
        return $this->quality_rating !== null
            || $this->punctuality_rating !== null
            || $this->friendliness_rating !== null;
    }

    //===========================================
    // Accessors
    //===========================================

    /**
     * คะแนนเป็นดาว (★★★★★)
     */
    public function getRatingStarsAttribute(): string
    {
        $stars = str_repeat('★', $this->rating);
        $emptyStars = str_repeat('☆', 5 - $this->rating);
        return $stars . $emptyStars;
    }

    /**
     * สีของคะแนน
     */
    public function getRatingColorAttribute(): string
    {
        return match (true) {
            $this->rating >= 4 => 'green',
            $this->rating >= 3 => 'yellow',
            default => 'red',
        };
    }

    /**
     * รูปภาพแรก
     */
    public function getFirstImageAttribute(): ?string
    {
        return $this->images[0] ?? null;
    }

    /**
     * ตรวจสอบว่ามีรูปภาพหรือไม่
     */
    public function hasImages(): bool
    {
        return !empty($this->images) && count($this->images) > 0;
    }

    /**
     * ตรวจสอบว่า provider ตอบกลับแล้วหรือยัง
     */
    public function hasProviderResponse(): bool
    {
        return !empty($this->provider_response);
    }

    /**
     * แสดงเวลาที่สร้างรีวิว
     */
    public function getCreatedAtFormattedAttribute(): string
    {
        return $this->created_at->format('d/m/Y H:i');
    }
}
