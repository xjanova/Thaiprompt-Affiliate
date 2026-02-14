<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Label Model
 *
 * จัดเก็บฉลากที่ผู้ใช้สร้างขึ้น (ฉลากจริงที่จะพิมพ์)
 *
 * @property int $id
 * @property int|null $user_id เจ้าของฉลาก
 * @property int|null $template_id template ที่ใช้
 * @property string $name ชื่อฉลาก
 * @property string|null $description คำอธิบาย
 * @property string|null $category หมวดหมู่
 * @property float $width ความกว้าง (mm)
 * @property float $height ความสูง (mm)
 * @property string $unit หน่วย
 * @property array $elements องค์ประกอบทั้งหมด
 * @property array|null $data_fields ฟิลด์ข้อมูล (สำหรับ batch print)
 * @property array|null $settings ตั้งค่าทั่วไป
 * @property array|null $print_settings ตั้งค่าการพิมพ์
 * @property bool $is_favorite เป็นรายการโปรด
 * @property int $print_count จำนวนครั้งที่พิมพ์
 * @property \Carbon\Carbon|null $last_printed_at พิมพ์ล่าสุดเมื่อ
 * @property string|null $thumbnail_path path รูป thumbnail
 * @property string|null $pdf_path path ไฟล์ PDF
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 *
 * @author Claude AI
 */
class Label extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'labels';

    /**
     * ฟิลด์ที่สามารถกำหนดค่าได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'template_id',
        'name',
        'description',
        'category',
        'width',
        'height',
        'unit',
        'elements',
        'data_fields',
        'settings',
        'print_settings',
        'is_favorite',
        'print_count',
        'last_printed_at',
        'thumbnail_path',
        'pdf_path',
    ];

    /**
     * การแปลงประเภทข้อมูล
     *
     * @var array<string, string>
     */
    protected $casts = [
        'width' => 'float',
        'height' => 'float',
        'elements' => 'array',
        'data_fields' => 'array',
        'settings' => 'array',
        'print_settings' => 'array',
        'is_favorite' => 'boolean',
        'print_count' => 'integer',
        'last_printed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * ค่าเริ่มต้น
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'width' => 50,
        'height' => 30,
        'unit' => 'mm',
        'is_favorite' => false,
        'print_count' => 0,
    ];

    /**
     * ความสัมพันธ์กับ User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ความสัมพันธ์กับ LabelTemplate
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(LabelTemplate::class, 'template_id');
    }

    /**
     * Scope: เฉพาะรายการโปรด
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFavorites($query)
    {
        return $query->where('is_favorite', true);
    }

    /**
     * Scope: กรองตามหมวดหมู่
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: เรียงตามการใช้งานล่าสุด
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecentlyUsed($query)
    {
        return $query->orderByDesc('last_printed_at');
    }

    /**
     * Scope: ล่าสุด
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecent($query, int $limit = 10)
    {
        return $query->orderByDesc('created_at')->limit($limit);
    }

    /**
     * สลับสถานะรายการโปรด
     */
    public function toggleFavorite(): bool
    {
        $this->is_favorite = ! $this->is_favorite;
        $this->save();

        return $this->is_favorite;
    }

    /**
     * บันทึกการพิมพ์
     *
     * @param  int  $copies  จำนวนสำเนาที่พิมพ์
     */
    public function recordPrint(int $copies = 1): void
    {
        $this->increment('print_count', $copies);
        $this->update(['last_printed_at' => now()]);
    }

    /**
     * ดึงรายการ barcode elements
     */
    public function getBarcodeElementsAttribute(): array
    {
        return $this->elements['barcode'] ?? [];
    }

    /**
     * ดึงรายการ QR code elements
     */
    public function getQrcodeElementsAttribute(): array
    {
        return $this->elements['qrcode'] ?? [];
    }

    /**
     * ดึงรายการ text elements
     */
    public function getTextElementsAttribute(): array
    {
        return $this->elements['text'] ?? [];
    }

    /**
     * ดึงรายการ image elements
     */
    public function getImageElementsAttribute(): array
    {
        return $this->elements['image'] ?? [];
    }

    /**
     * คำนวณขนาดในหน่วย pixel (สำหรับ preview)
     *
     * @param  int  $dpi  DPI สำหรับการแปลง
     * @return array{width: int, height: int}
     */
    public function getSizeInPixels(int $dpi = 96): array
    {
        // แปลง mm เป็น inches แล้วคูณ DPI
        $mmToInch = 1 / 25.4;

        return [
            'width' => (int) round($this->width * $mmToInch * $dpi),
            'height' => (int) round($this->height * $mmToInch * $dpi),
        ];
    }

    /**
     * โคลนฉลากเป็นฉลากใหม่
     *
     * @param  string|null  $newName  ชื่อใหม่
     */
    public function duplicate(?string $newName = null): Label
    {
        $clone = $this->replicate();
        $clone->name = $newName ?? $this->name.' (สำเนา)';
        $clone->print_count = 0;
        $clone->last_printed_at = null;
        $clone->thumbnail_path = null;
        $clone->pdf_path = null;
        $clone->save();

        return $clone;
    }
}
