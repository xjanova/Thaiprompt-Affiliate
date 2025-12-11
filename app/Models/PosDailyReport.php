<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * POS Daily Report Model
 *
 * รายงานยอดขายรายวันจากเครื่อง POS
 *
 * @property int $id
 * @property int $pos_terminal_id
 * @property int $shop_id
 * @property \Carbon\Carbon $report_date วันที่รายงาน
 * @property float $total_sales ยอดขายรวม
 * @property int $total_orders จำนวนออเดอร์
 * @property int $total_items จำนวนรายการสินค้า
 * @property float $cash_sales ยอดขายเงินสด
 * @property float $card_sales ยอดขายบัตร
 * @property float $other_sales ยอดขายอื่นๆ
 * @property float $discount_total ส่วนลดรวม
 * @property float $tax_total ภาษีรวม
 * @property array|null $hourly_sales ยอดขายรายชั่วโมง
 * @property array|null $top_products สินค้าขายดี
 * @property \Carbon\Carbon|null $synced_at เวลาที่ sync ข้อมูล
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class PosDailyReport extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'pos_daily_reports';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'pos_terminal_id',
        'shop_id',
        'report_date',
        'total_sales',
        'total_orders',
        'total_items',
        'cash_sales',
        'card_sales',
        'other_sales',
        'discount_total',
        'tax_total',
        'hourly_sales',
        'top_products',
        'synced_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'report_date' => 'date',
        'total_sales' => 'decimal:2',
        'cash_sales' => 'decimal:2',
        'card_sales' => 'decimal:2',
        'other_sales' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'total_orders' => 'integer',
        'total_items' => 'integer',
        'hourly_sales' => 'array',
        'top_products' => 'array',
        'synced_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * ความสัมพันธ์กับ POS Terminal
     *
     * @return BelongsTo
     */
    public function terminal(): BelongsTo
    {
        return $this->belongsTo(PosTerminal::class, 'pos_terminal_id');
    }

    /**
     * ความสัมพันธ์กับร้านค้า
     *
     * @return BelongsTo
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Scope: รายงานของวันนี้
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeToday($query)
    {
        return $query->where('report_date', today());
    }

    /**
     * Scope: รายงานในช่วงวันที่
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $startDate
     * @param string $endDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDateRange($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('report_date', [$startDate, $endDate]);
    }

    /**
     * คำนวณ average order value
     *
     * @return float
     */
    public function getAverageOrderValueAttribute(): float
    {
        if ($this->total_orders === 0) {
            return 0;
        }

        return round($this->total_sales / $this->total_orders, 2);
    }

    /**
     * คำนวณ average items per order
     *
     * @return float
     */
    public function getAverageItemsPerOrderAttribute(): float
    {
        if ($this->total_orders === 0) {
            return 0;
        }

        return round($this->total_items / $this->total_orders, 2);
    }

    /**
     * หาชั่วโมงที่ขายดีที่สุด
     *
     * @return int|null
     */
    public function getPeakHourAttribute(): ?int
    {
        if (!$this->hourly_sales || empty($this->hourly_sales)) {
            return null;
        }

        $maxHour = null;
        $maxSales = 0;

        foreach ($this->hourly_sales as $hour => $sales) {
            if ($sales > $maxSales) {
                $maxSales = $sales;
                $maxHour = (int) $hour;
            }
        }

        return $maxHour;
    }

    /**
     * สร้างหรืออัพเดทรายงานวันนี้
     *
     * @param int $terminalId
     * @param array $data
     * @return static
     */
    public static function updateOrCreateToday(int $terminalId, array $data): static
    {
        $terminal = PosTerminal::findOrFail($terminalId);

        return static::updateOrCreate(
            [
                'pos_terminal_id' => $terminalId,
                'report_date' => today(),
            ],
            array_merge($data, [
                'shop_id' => $terminal->shop_id,
                'synced_at' => now(),
            ])
        );
    }
}
