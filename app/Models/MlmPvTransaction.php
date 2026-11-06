<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MlmPvTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'mlm_member_id',
        'mlm_plan_id',
        'transaction_type',
        'order_id',
        'product_id',
        'pv_amount',
        'sales_amount',
        'previous_balance',
        'new_balance',
        'attributed_leg',
        'binary_leg_credits',
        'description',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'pv_amount' => 'decimal:2',
            'sales_amount' => 'decimal:2',
            'previous_balance' => 'decimal:2',
            'new_balance' => 'decimal:2',
            'binary_leg_credits' => 'array',
        ];
    }

    /** PV ที่นับรักษายอด (ซื้อสินค้า/ดูดวง) */
    public const RETENTION_TYPES = ['purchase', 'fortune_reading'];

    /** แถวหัก PV ของออเดอร์ที่คืนเงิน (pv_amount ติดลบ) — audit F11 / M6 */
    public const TYPE_REFUND = 'refund';

    /**
     * แถวที่นับเป็น PV รักษายอด: ซื้อสินค้า/ดูดวง ยกเว้นออเดอร์ที่คืนเงินแล้ว (มีแถว refund ของออเดอร์เดียวกัน)
     *
     * 🛡️ M6: PV ของออเดอร์ที่คืนเงินต้องไม่ทำให้สมาชิกยัง active ทั้งเดือน (F11 ฉาก 3)
     */
    public function scopeCountsForRetention($query)
    {
        $table = $query->getModel()->getTable();

        return $query->whereIn($table.'.transaction_type', self::RETENTION_TYPES)
            ->whereNotExists(function ($sub) use ($table) {
                $sub->selectRaw('1')
                    ->from('mlm_pv_transactions as pv_refund')
                    ->whereColumn('pv_refund.order_id', $table.'.order_id')
                    ->whereColumn('pv_refund.mlm_member_id', $table.'.mlm_member_id')
                    ->where('pv_refund.transaction_type', self::TYPE_REFUND);
            });
    }

    /**
     * Get the member
     */
    public function member()
    {
        return $this->belongsTo(MlmMember::class, 'mlm_member_id');
    }

    /**
     * Get the plan
     */
    public function plan()
    {
        return $this->belongsTo(MlmPlan::class, 'mlm_plan_id');
    }

    /**
     * Get the order
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the product
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the user who created this
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
