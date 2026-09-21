<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 🌙 ป้าย "บิลจันทราเลขนี้ถูกยกเลิกแล้ว" สำหรับคำสั่งยกเลิกที่มาถึงก่อนตัวบิล
 *
 * บิลเลขนี้ที่มาถึงทีหลังจะไม่แจกค่าแนะนำ (ลูกค้าได้เงินคืนไปแล้ว)
 */
class JuntraVoidedBill extends Model
{
    protected $fillable = ['juntra_bill_id', 'reason'];

    protected $casts = ['juntra_bill_id' => 'integer'];
}
