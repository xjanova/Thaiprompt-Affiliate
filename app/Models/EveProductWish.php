<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * EveProductWish — "ของที่ลูกค้าอยากได้" จากการคุยกับน้อง Eve
 *
 * บันทึกเมื่อค้นใน catalog เราไม่เจอ → ทีมงาน/affiliate auto-import มาเติมทีหลัง
 */
class EveProductWish extends Model
{
    protected $fillable = [
        'user_id',
        'query',
        'budget',
        'results_found',
        'status',
        'source',
        'note',
    ];

    protected $casts = [
        'budget' => 'decimal:2',
        'results_found' => 'integer',
    ];

    /**
     * ลูกค้าที่ขอ (null = ยังไม่ล็อกอินตอนคุยกับ Eve)
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
