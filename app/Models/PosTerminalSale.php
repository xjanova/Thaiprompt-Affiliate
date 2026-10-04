<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * บิลขายหน้าร้านที่เครื่อง POS อัปโหลด (บันทึกอย่างเดียว — ไม่แตะเงิน/สต็อก ไม่ใช่ออเดอร์ร้านค้า)
 *
 * @property int $id
 * @property int $pos_terminal_id
 * @property int|null $store_id
 * @property string $local_id
 * @property string $total
 * @property array $items [{product_id, name, qty, price, line_total}]
 * @property string|null $payment_method
 * @property \Carbon\Carbon|null $sold_at
 */
class PosTerminalSale extends Model
{
    protected $table = 'pos_terminal_sales';

    protected $fillable = [
        'pos_terminal_id',
        'store_id',
        'local_id',
        'total',
        'items',
        'payment_method',
        'sold_at',
    ];

    protected $casts = [
        'items' => 'array',
        'total' => 'decimal:2',
        'sold_at' => 'datetime',
    ];

    /**
     * เครื่อง POS ที่อัปโหลด
     */
    public function terminal(): BelongsTo
    {
        return $this->belongsTo(PosTerminal::class, 'pos_terminal_id');
    }

    /**
     * ร้านของเครื่อง
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(VendorStore::class, 'store_id');
    }
}
