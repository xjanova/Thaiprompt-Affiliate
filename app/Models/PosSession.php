<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'user_id',
        'session_id',
        'opening_balance',
        'closing_balance',
        'expected_balance',
        'difference',
        'opened_at',
        'closed_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'expected_balance' => 'decimal:2',
        'difference' => 'decimal:2',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    // Relationships
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sales()
    {
        return $this->hasMany(PosSale::class);
    }

    // Helper Methods
    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function getTotalSales(): float
    {
        return $this->sales->sum('total');
    }
}

class PosSale extends Model
{
    use HasFactory;

    protected $fillable = [
        'pos_session_id',
        'vendor_id',
        'customer_id',
        'receipt_number',
        'subtotal',
        'tax',
        'discount',
        'total',
        'payment_method',
        'amount_paid',
        'change_given',
        'notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'change_given' => 'decimal:2',
    ];

    // Relationships
    public function session()
    {
        return $this->belongsTo(PosSession::class, 'pos_session_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function items()
    {
        return $this->hasMany(PosSaleItem::class);
    }
}

class PosSaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'pos_sale_id',
        'product_id',
        'product_name',
        'price',
        'quantity',
        'subtotal',
        'discount',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
    ];

    // Relationships
    public function sale()
    {
        return $this->belongsTo(PosSale::class, 'pos_sale_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
