<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * LabelTemplate Model
 *
 * จัดเก็บ template สำหรับออกฉลาก บาร์โค้ด QR Code
 * ใช้สำหรับทำสติ๊กเกอร์ติดผลิตภัณฑ์
 *
 * @property int $id
 * @property int|null $user_id เจ้าของ template
 * @property string $name ชื่อ template
 * @property string|null $description คำอธิบาย
 * @property string|null $category หมวดหมู่
 * @property float $paper_width ความกว้างกระดาษ (mm)
 * @property float $paper_height ความสูงกระดาษ (mm)
 * @property string $paper_unit หน่วย (mm, cm, in)
 * @property string|null $paper_type ประเภทกระดาษ
 * @property int $columns จำนวนคอลัมน์
 * @property int $rows จำนวนแถว
 * @property float $gap_horizontal ระยะห่างแนวนอน
 * @property float $gap_vertical ระยะห่างแนวตั้ง
 * @property float $margin_top ขอบบน
 * @property float $margin_left ขอบซ้าย
 * @property float $margin_right ขอบขวา
 * @property float $margin_bottom ขอบล่าง
 * @property array|null $elements องค์ประกอบทั้งหมด
 * @property array|null $settings ตั้งค่าทั่วไป
 * @property array|null $print_settings ตั้งค่าการพิมพ์
 * @property bool $is_public แชร์สาธารณะ
 * @property bool $is_system เป็น system template
 * @property bool $is_active เปิดใช้งาน
 * @property int $usage_count จำนวนครั้งที่ใช้
 * @property string|null $thumbnail_path path รูป thumbnail
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 *
 * @author Claude AI
 */
class LabelTemplate extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'label_templates';

    /**
     * ฟิลด์ที่สามารถกำหนดค่าได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'category',
        'paper_width',
        'paper_height',
        'paper_unit',
        'paper_type',
        'columns',
        'rows',
        'gap_horizontal',
        'gap_vertical',
        'margin_top',
        'margin_left',
        'margin_right',
        'margin_bottom',
        'elements',
        'settings',
        'print_settings',
        'is_public',
        'is_system',
        'is_active',
        'usage_count',
        'thumbnail_path',
        // POS specific fields
        'is_pos_template',
        'pos_category',
        'printer_type',
        'is_continuous_roll',
    ];

    /**
     * การแปลงประเภทข้อมูล
     *
     * @var array<string, string>
     */
    protected $casts = [
        'paper_width' => 'float',
        'paper_height' => 'float',
        'columns' => 'integer',
        'rows' => 'integer',
        'gap_horizontal' => 'float',
        'gap_vertical' => 'float',
        'margin_top' => 'float',
        'margin_left' => 'float',
        'margin_right' => 'float',
        'margin_bottom' => 'float',
        'elements' => 'array',
        'settings' => 'array',
        'print_settings' => 'array',
        'is_public' => 'boolean',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'usage_count' => 'integer',
        // POS specific casts
        'is_pos_template' => 'boolean',
        'is_continuous_roll' => 'boolean',
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
        'paper_width' => 50,
        'paper_height' => 30,
        'paper_unit' => 'mm',
        'columns' => 1,
        'rows' => 1,
        'gap_horizontal' => 0,
        'gap_vertical' => 0,
        'margin_top' => 0,
        'margin_left' => 0,
        'margin_right' => 0,
        'margin_bottom' => 0,
        'is_public' => false,
        'is_system' => false,
        'is_active' => true,
        'usage_count' => 0,
    ];

    /**
     * ความสัมพันธ์กับ User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ความสัมพันธ์กับ Labels ที่ใช้ template นี้
     */
    public function labels(): HasMany
    {
        return $this->hasMany(Label::class, 'template_id');
    }

    /**
     * Scope: เฉพาะ template ที่เป็นสาธารณะ
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope: เฉพาะ template ของระบบ
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope: เฉพาะ template ที่เปิดใช้งาน
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
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
     * Scope: template ยอดนิยม
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePopular($query, int $limit = 10)
    {
        return $query->orderBy('usage_count', 'desc')->limit($limit);
    }

    /**
     * เพิ่มจำนวนการใช้งาน
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * คำนวณขนาดฉลากแต่ละชิ้น (กรณีพิมพ์หลายชิ้นต่อแผ่น)
     *
     * @return array{width: float, height: float}
     */
    public function getLabelSizeAttribute(): array
    {
        $usableWidth = $this->paper_width - $this->margin_left - $this->margin_right;
        $usableHeight = $this->paper_height - $this->margin_top - $this->margin_bottom;

        $totalGapH = ($this->columns - 1) * $this->gap_horizontal;
        $totalGapV = ($this->rows - 1) * $this->gap_vertical;

        return [
            'width' => ($usableWidth - $totalGapH) / $this->columns,
            'height' => ($usableHeight - $totalGapV) / $this->rows,
        ];
    }

    /**
     * คำนวณจำนวนฉลากต่อแผ่น
     */
    public function getLabelsPerSheetAttribute(): int
    {
        return $this->columns * $this->rows;
    }

    /**
     * ดึง default elements structure
     */
    public static function getDefaultElements(): array
    {
        return [
            'text' => [],      // Text elements
            'barcode' => [],   // Barcode elements
            'qrcode' => [],    // QR Code elements
            'image' => [],     // Image elements
            'shape' => [],     // Shape elements (line, rectangle, etc.)
        ];
    }

    /**
     * ดึง default settings structure
     */
    public static function getDefaultSettings(): array
    {
        return [
            'background_color' => '#ffffff',
            'border_enabled' => false,
            'border_color' => '#000000',
            'border_width' => 1,
            'grid_enabled' => true,
            'grid_size' => 5, // mm
            'snap_to_grid' => true,
        ];
    }

    /**
     * ดึง default print settings structure
     */
    public static function getDefaultPrintSettings(): array
    {
        return [
            'copies' => 1,
            'orientation' => 'portrait',
            'scale' => 100,
            'color_mode' => 'color',
            'quality' => 'high',
            'mirror' => false,
        ];
    }

    /**
     * ความสัมพันธ์กับ PosLabelPrints
     */
    public function posLabelPrints(): HasMany
    {
        return $this->hasMany(PosLabelPrint::class, 'template_id');
    }

    /**
     * Scope: เฉพาะ template สำหรับ POS
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePosTemplates($query)
    {
        return $query->where('is_pos_template', true);
    }

    /**
     * Scope: กรองตาม POS category
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForPosCategory($query, string $category)
    {
        return $query->where('pos_category', $category);
    }

    /**
     * Scope: เฉพาะ template สำหรับ Product Label
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeProductLabels($query)
    {
        return $query->where('pos_category', 'product_label');
    }

    /**
     * Scope: เฉพาะ template สำหรับ Shipping Label
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeShippingLabels($query)
    {
        return $query->where('pos_category', 'shipping_label');
    }

    /**
     * Scope: รองรับเครื่องพิมพ์แบบม้วน
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeContinuousRoll($query)
    {
        return $query->where('is_continuous_roll', true);
    }

    /**
     * Scope: กรองตามประเภทเครื่องพิมพ์
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForPrinterType($query, string $type)
    {
        return $query->where(function ($q) use ($type) {
            $q->where('printer_type', $type)
                ->orWhere('printer_type', 'any');
        });
    }

    /**
     * ตรวจสอบว่าเป็น template สำหรับ POS หรือไม่
     */
    public function isPosTemplate(): bool
    {
        return $this->is_pos_template === true;
    }

    /**
     * ตรวจสอบว่ารองรับการพิมพ์แบบม้วนหรือไม่
     */
    public function supportsContinuousRoll(): bool
    {
        return $this->is_continuous_roll === true;
    }

    /**
     * ดึงชื่อประเภท POS category
     */
    public function getPosCategoryNameAttribute(): ?string
    {
        if (! $this->pos_category) {
            return null;
        }

        return match ($this->pos_category) {
            'product_label' => 'ฉลากสินค้า',
            'shipping_label' => 'ใบปะสินค้า',
            'price_tag' => 'ฉลากราคา',
            'barcode_only' => 'Barcode',
            'receipt' => 'ใบเสร็จ',
            'other' => 'อื่นๆ',
            default => $this->pos_category,
        };
    }

    /**
     * ดึงชื่อประเภทเครื่องพิมพ์
     */
    public function getPrinterTypeNameAttribute(): string
    {
        return match ($this->printer_type) {
            'thermal_roll' => 'Thermal Roll (ม้วน)',
            'thermal_label' => 'Thermal Label (ฉลาก)',
            'inkjet' => 'Inkjet',
            'laser' => 'Laser',
            'any' => 'ทุกประเภท',
            default => $this->printer_type ?? 'ทุกประเภท',
        };
    }
}
