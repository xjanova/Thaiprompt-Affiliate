<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * รายการสินค้าในออเดอร์ตลาดสด (snapshot ตอนสั่ง — ราคา/ชื่อ/ตัวเลือกไม่เปลี่ยนตามสินค้าภายหลัง)
 *
 * @property int $id
 * @property int $order_id
 * @property int|null $listing_id
 * @property string $title
 * @property string|null $unit
 * @property string|null $image_url
 * @property int $quantity
 * @property float $base_price
 * @property float $options_price
 * @property float $unit_price
 * @property float $line_total
 * @property array|null $selected_options [{group_id, group_name, option_id, name, price_delta}]
 * @property string|null $note
 * @property bool $track_stock
 * @property bool $stock_deducted
 * @property float|null $gp_rate
 * @property float $platform_fee
 * @property float $cashback_amount
 */
class FreshMarketOrderItem extends Model
{
    protected $table = 'fresh_market_order_items';

    protected $fillable = [
        'order_id',
        'listing_id',
        'title',
        'unit',
        'image_url',
        'quantity',
        'base_price',
        'options_price',
        'unit_price',
        'line_total',
        'selected_options',
        'note',
        'track_stock',
        'stock_deducted',
        'gp_rate',
        'platform_fee',
        'cashback_amount',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'base_price' => 'decimal:2',
        'options_price' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'selected_options' => 'array',
        'track_stock' => 'boolean',
        'stock_deducted' => 'boolean',
        'gp_rate' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'cashback_amount' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(FreshMarketOrder::class, 'order_id');
    }

    /**
     * สินค้า (รวมที่ถูกลบแบบ soft delete)
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(FreshMarketListing::class, 'listing_id')->withTrashed();
    }

    /**
     * ชื่อตัวเลือกที่เลือก ต่อกันด้วยจุลภาค (เช่น "กุ้ง, ไข่ดาว")
     */
    public function optionsLabel(): string
    {
        $names = collect($this->selected_options ?? [])
            ->pluck('name')
            ->filter(fn ($n) => is_string($n) && $n !== '')
            ->all();

        return implode(', ', $names);
    }

    /**
     * ข้อความสั้นหนึ่งบรรทัด: "ผัดกะเพราราดข้าว (กุ้ง, ไข่ดาว) x2 จาน"
     */
    public function summaryLine(): string
    {
        $options = $this->optionsLabel();
        $title = $this->title.($options !== '' ? " ({$options})" : '');

        return trim("{$title} x{$this->quantity} ".($this->unit ?? ''));
    }

    /**
     * ข้อมูลสำหรับ API (ตัวเลขเป็น number)
     *
     * @param  bool  $withMoney  true = แสดง GP/แคชแบ็คต่อบรรทัด (ฝั่งร้าน/แอดมิน)
     */
    public function toApiArray(bool $withMoney = false): array
    {
        $data = [
            'id' => (int) $this->id,
            'listing_id' => $this->listing_id !== null ? (int) $this->listing_id : null,
            'title' => $this->title,
            'unit' => $this->unit,
            'image_url' => $this->image_url,
            'quantity' => (int) $this->quantity,
            'base_price' => round((float) $this->base_price, 2),
            'options_price' => round((float) $this->options_price, 2),
            'unit_price' => round((float) $this->unit_price, 2),
            'line_total' => round((float) $this->line_total, 2),
            'selected_options' => collect($this->selected_options ?? [])->map(fn ($o) => [
                'group_id' => isset($o['group_id']) ? (int) $o['group_id'] : null,
                'group_name' => $o['group_name'] ?? null,
                'option_id' => isset($o['option_id']) ? (int) $o['option_id'] : null,
                'name' => $o['name'] ?? '',
                'price_delta' => round((float) ($o['price_delta'] ?? 0), 2),
            ])->values()->all(),
            'options_label' => $this->optionsLabel(),
            'note' => $this->note,
        ];

        if ($withMoney) {
            $data['gp_rate'] = $this->gp_rate !== null ? (float) $this->gp_rate : null;
            $data['platform_fee'] = round((float) $this->platform_fee, 2);
            $data['cashback_amount'] = round((float) $this->cashback_amount, 2);
        }

        return $data;
    }
}
