<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * POS Daily Report Model
 *
 * เก็บรายงานสรุปยอดขายรายวันของแต่ละ Terminal
 *
 * @property int $id
 * @property int $terminal_id
 * @property \Carbon\Carbon $report_date
 * @property float $total_sales
 * @property int $total_transactions
 * @property float $total_cash
 * @property float $total_card
 * @property float $total_qr
 * @property float $total_discount
 * @property float $total_tax
 * @property int $items_sold
 * @property array|null $hourly_sales
 * @property array|null $top_products
 * @property \Carbon\Carbon|null $synced_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class PosDailyReport extends Model
{
    use HasFactory;

    /**
     * ชื่อตาราง
     */
    protected $table = 'pos_daily_reports';

    /**
     * คอลัมน์ที่สามารถกรอกได้
     */
    protected $fillable = [
        'terminal_id',
        'report_date',
        'total_sales',
        'total_transactions',
        'total_cash',
        'total_card',
        'total_qr',
        'total_discount',
        'total_tax',
        'items_sold',
        'hourly_sales',
        'top_products',
        'synced_at',
    ];

    /**
     * Attribute Casting
     */
    protected $casts = [
        'report_date' => 'date',
        'total_sales' => 'decimal:2',
        'total_cash' => 'decimal:2',
        'total_card' => 'decimal:2',
        'total_qr' => 'decimal:2',
        'total_discount' => 'decimal:2',
        'total_tax' => 'decimal:2',
        'total_transactions' => 'integer',
        'items_sold' => 'integer',
        'hourly_sales' => 'array',
        'top_products' => 'array',
        'synced_at' => 'datetime',
    ];

    // =========================================
    // Relationships
    // =========================================

    /**
     * POS Terminal ที่รายงานนี้เป็นของ
     */
    public function terminal(): BelongsTo
    {
        return $this->belongsTo(PosTerminal::class, 'terminal_id');
    }

    // =========================================
    // Scopes
    // =========================================

    /**
     * ค้นหาตามวันที่
     */
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('report_date', $date);
    }

    /**
     * ค้นหาตามช่วงวันที่
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('report_date', [$startDate, $endDate]);
    }

    /**
     * ค้นหาตาม Terminal ID
     */
    public function scopeForTerminal($query, $terminalId)
    {
        return $query->where('terminal_id', $terminalId);
    }

    // =========================================
    // Helper Methods
    // =========================================

    /**
     * คำนวณยอดขายเฉลี่ยต่อ transaction
     */
    public function getAverageTransactionValue(): float
    {
        if ($this->total_transactions === 0) {
            return 0;
        }

        return round($this->total_sales / $this->total_transactions, 2);
    }

    /**
     * คำนวณสัดส่วนการชำระเงิน
     */
    public function getPaymentBreakdown(): array
    {
        $total = $this->total_sales;
        if ($total === 0) {
            return [
                'cash' => ['amount' => 0, 'percentage' => 0],
                'card' => ['amount' => 0, 'percentage' => 0],
                'qr' => ['amount' => 0, 'percentage' => 0],
            ];
        }

        return [
            'cash' => [
                'amount' => $this->total_cash,
                'percentage' => round(($this->total_cash / $total) * 100, 1),
            ],
            'card' => [
                'amount' => $this->total_card,
                'percentage' => round(($this->total_card / $total) * 100, 1),
            ],
            'qr' => [
                'amount' => $this->total_qr,
                'percentage' => round(($this->total_qr / $total) * 100, 1),
            ],
        ];
    }

    /**
     * ดึงชั่วโมงที่ขายดีที่สุด
     */
    public function getPeakHour(): ?int
    {
        if (empty($this->hourly_sales)) {
            return null;
        }

        $maxSales = 0;
        $peakHour = null;

        foreach ($this->hourly_sales as $hour => $sales) {
            if ($sales > $maxSales) {
                $maxSales = $sales;
                $peakHour = (int) $hour;
            }
        }

        return $peakHour;
    }
}
