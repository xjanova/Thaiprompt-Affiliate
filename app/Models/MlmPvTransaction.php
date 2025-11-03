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
        ];
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
