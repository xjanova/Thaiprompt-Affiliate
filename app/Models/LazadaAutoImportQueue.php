<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * คิวนำเข้าสินค้าอัตโนมัติ Lazada Hub — ระบบค่อยๆ ดึงเข้า catalog ทีละน้อย
 */
class LazadaAutoImportQueue extends Model
{
    protected $table = 'lazada_auto_import_queue';

    protected $fillable = [
        'url',
        'price_min',
        'price_max',
        'fulfillment_mode',
        'status',
        'attempts',
        'error',
        'marketplace_product_id',
        'batch_label',
        'created_by',
    ];

    protected $casts = [
        'price_min' => 'decimal:2',
        'price_max' => 'decimal:2',
        'attempts' => 'integer',
    ];
}
