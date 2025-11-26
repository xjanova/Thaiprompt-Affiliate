<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * LabelPaperSize Model
 *
 * จัดเก็บขนาดกระดาษมาตรฐานและกระดาษที่รองรับเครื่องพิมพ์ต่างๆ
 *
 * @property int $id
 * @property string $name ชื่อ (เช่น "A4", "สติ๊กเกอร์มาตรฐาน")
 * @property string|null $name_th ชื่อภาษาไทย
 * @property string|null $description คำอธิบาย
 * @property string $category หมวดหมู่
 * @property float $width ความกว้าง (mm)
 * @property float $height ความสูง (mm)
 * @property int $labels_per_row จำนวนฉลากต่อแถว
 * @property int $labels_per_column จำนวนฉลากต่อคอลัมน์
 * @property float|null $label_width ความกว้างฉลาก
 * @property float|null $label_height ความสูงฉลาก
 * @property float $gap_horizontal ระยะห่างแนวนอน
 * @property float $gap_vertical ระยะห่างแนวตั้ง
 * @property float $margin_top ขอบบน
 * @property float $margin_left ขอบซ้าย
 * @property float $margin_right ขอบขวา
 * @property float $margin_bottom ขอบล่าง
 * @property array|null $supported_printers เครื่องพิมพ์ที่รองรับ
 * @property bool $is_active เปิดใช้งาน
 * @property bool $is_system เป็น system record
 * @property int $sort_order ลำดับการเรียง
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @author Claude AI
 */
class LabelPaperSize extends Model
{
    use HasFactory;

    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'label_paper_sizes';

    /**
     * ฟิลด์ที่สามารถกำหนดค่าได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'name_th',
        'description',
        'category',
        'width',
        'height',
        'labels_per_row',
        'labels_per_column',
        'label_width',
        'label_height',
        'gap_horizontal',
        'gap_vertical',
        'margin_top',
        'margin_left',
        'margin_right',
        'margin_bottom',
        'supported_printers',
        'is_active',
        'is_system',
        'sort_order',
    ];

    /**
     * การแปลงประเภทข้อมูล
     *
     * @var array<string, string>
     */
    protected $casts = [
        'width' => 'float',
        'height' => 'float',
        'labels_per_row' => 'integer',
        'labels_per_column' => 'integer',
        'label_width' => 'float',
        'label_height' => 'float',
        'gap_horizontal' => 'float',
        'gap_vertical' => 'float',
        'margin_top' => 'float',
        'margin_left' => 'float',
        'margin_right' => 'float',
        'margin_bottom' => 'float',
        'supported_printers' => 'array',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * ค่าเริ่มต้น
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'labels_per_row' => 1,
        'labels_per_column' => 1,
        'gap_horizontal' => 0,
        'gap_vertical' => 0,
        'margin_top' => 0,
        'margin_left' => 0,
        'margin_right' => 0,
        'margin_bottom' => 0,
        'is_active' => true,
        'is_system' => true,
        'sort_order' => 0,
    ];

    /**
     * Scope: เฉพาะที่เปิดใช้งาน
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: กรองตามหมวดหมู่
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $category
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: เรียงตามลำดับ
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * คำนวณจำนวนฉลากต่อแผ่น
     *
     * @return int
     */
    public function getTotalLabelsAttribute(): int
    {
        return $this->labels_per_row * $this->labels_per_column;
    }

    /**
     * คำนวณขนาดฉลากแต่ละชิ้น (ถ้าไม่ได้กำหนดไว้)
     *
     * @return array{width: float, height: float}
     */
    public function getCalculatedLabelSizeAttribute(): array
    {
        if ($this->label_width && $this->label_height) {
            return [
                'width' => $this->label_width,
                'height' => $this->label_height,
            ];
        }

        $usableWidth = $this->width - $this->margin_left - $this->margin_right;
        $usableHeight = $this->height - $this->margin_top - $this->margin_bottom;

        $totalGapH = ($this->labels_per_row - 1) * $this->gap_horizontal;
        $totalGapV = ($this->labels_per_column - 1) * $this->gap_vertical;

        return [
            'width' => ($usableWidth - $totalGapH) / $this->labels_per_row,
            'height' => ($usableHeight - $totalGapV) / $this->labels_per_column,
        ];
    }

    /**
     * ดึงชื่อแสดงผล (ภาษาไทยหรืออังกฤษ)
     *
     * @return string
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name_th ?: $this->name;
    }

    /**
     * ดึงขนาดในรูปแบบ string
     *
     * @return string
     */
    public function getSizeStringAttribute(): string
    {
        return "{$this->width} x {$this->height} mm";
    }

    /**
     * รายการหมวดหมู่ที่รองรับ
     *
     * @return array
     */
    public static function getCategories(): array
    {
        return [
            'standard' => 'กระดาษมาตรฐาน',
            'label' => 'กระดาษฉลาก',
            'receipt' => 'กระดาษใบเสร็จ',
            'sticker' => 'สติ๊กเกอร์',
            'thermal' => 'กระดาษความร้อน',
            'custom' => 'กำหนดเอง',
        ];
    }

    /**
     * รายการเครื่องพิมพ์ยอดนิยม
     *
     * @return array
     */
    public static function getPopularPrinters(): array
    {
        return [
            'thermal' => [
                'Zebra ZD420',
                'Zebra ZD620',
                'Zebra GK420t',
                'Brother QL-820NWB',
                'Brother QL-1110NWB',
                'DYMO LabelWriter 450',
                'DYMO LabelWriter 4XL',
                'TSC TE200',
                'TSC TE310',
                'Xprinter XP-365B',
            ],
            'inkjet' => [
                'Epson L3150',
                'HP DeskJet 2700',
                'Canon PIXMA G3010',
                'Brother DCP-T520W',
            ],
            'laser' => [
                'HP LaserJet Pro M15w',
                'Brother HL-L2350DW',
                'Canon LBP6030w',
            ],
            'receipt' => [
                'Epson TM-T82III',
                'Xprinter XP-Q200',
                'POS-5890',
                'Sunmi V2 Pro',
            ],
        ];
    }
}
