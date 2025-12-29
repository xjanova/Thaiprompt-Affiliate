<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model สำหรับเทมเพลต Flex Message ของ LINE
 *
 * เก็บข้อมูลเทมเพลต Flex Message ที่ใช้ในการส่ง broadcast
 * และการสื่อสารอื่นๆ ผ่าน LINE OA
 *
 * @property int $id
 * @property string $name ชื่อเทมเพลต
 * @property string $category หมวดหมู่: promotion, info, product, etc.
 * @property string|null $description คำอธิบายเทมเพลต
 * @property string|null $thumbnail รูปตัวอย่าง
 * @property array $flex_content JSON ของ Flex Message
 * @property bool $is_seed เป็นตัวอย่างจากระบบหรือไม่
 * @property bool $is_active สถานะการใช้งาน
 * @property int $usage_count จำนวนครั้งที่ใช้งาน
 * @property int|null $created_by ผู้สร้าง
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class LineFlexMessageTemplate extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * ชื่อตารางในฐานข้อมูล
     *
     * @var string
     */
    protected $table = 'line_flex_message_templates';

    /**
     * คอลัมน์ที่สามารถกำหนดค่าได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'category',
        'description',
        'thumbnail',
        'flex_content',
        'is_seed',
        'is_active',
        'usage_count',
        'created_by',
    ];

    /**
     * การแปลงประเภทข้อมูล
     *
     * @var array<string, string>
     */
    protected $casts = [
        'flex_content' => 'array',
        'is_seed' => 'boolean',
        'is_active' => 'boolean',
        'usage_count' => 'integer',
    ];

    /**
     * ความสัมพันธ์กับผู้สร้าง
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * ความสัมพันธ์กับ broadcast messages
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function broadcastMessages()
    {
        return $this->hasMany(LineBroadcastMessage::class, 'flex_template_id');
    }

    /**
     * เพิ่มจำนวนครั้งที่ใช้งาน
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Scope: เทมเพลตที่ active
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: เทมเพลตตัวอย่างจากระบบ
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSeeded($query)
    {
        return $query->where('is_seed', true);
    }

    /**
     * Scope: เทมเพลตที่ผู้ใช้สร้าง
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCustom($query)
    {
        return $query->where('is_seed', false);
    }

    /**
     * Scope: กรองตามหมวดหมู่
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * ดึง label ของหมวดหมู่
     */
    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'promotion' => '🏷️ โปรโมชั่น',
            'info' => 'ℹ️ ข้อมูล',
            'product' => '🛍️ สินค้า',
            'welcome' => '👋 ต้อนรับ',
            'notification' => '🔔 แจ้งเตือน',
            'general' => '📋 ทั่วไป',
            default => $this->category,
        };
    }
}
