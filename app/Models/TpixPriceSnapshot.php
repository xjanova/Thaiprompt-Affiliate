<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ราคา TPIX ย้อนหลัง (บันทึกไม่เกินนาทีละครั้ง) — ใช้คิดค่าเฉลี่ยกันปั่นพูล และเป็นหลักฐานราคาที่ใช้คิดเงิน
 */
class TpixPriceSnapshot extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'price_usd',
        'usd_thb',
        'price_thb',
        'reserve_tpix',
        'reserve_usdt',
        'source',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
