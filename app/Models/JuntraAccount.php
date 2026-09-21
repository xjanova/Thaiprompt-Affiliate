<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 🌙 ลูกค้าเว็บ/แอพจันทรา 1 คน ↔ ผู้ใช้ Thaiprompt 1 คน (ถาวร)
 *
 * สายงานและค่าแนะนำทั้งหมดของลูกค้าจันทราผูกกับ user_id นี้ — เปลี่ยนได้ทางเดียวคือ
 * JuntraAccountMerger (เจ้าของสั่ง: ลูกค้าที่ซื้อก่อนผูก Thaiprompt → รวมเข้าบัญชี Thaiprompt
 * เป็นตัวหลัก) ซึ่งย้ายบิล ค่าแนะนำ กระเป๋า และตำแหน่งในผังตามไปทั้งหมด
 *
 * @property int $juntra_user_id users.id ฝั่งเว็บจันทรา
 * @property int $user_id
 * @property string $linked_via sso = ผู้ใช้ Thaiprompt ที่ลูกค้าผูกเอง · auto = ระบบสร้างให้
 */
class JuntraAccount extends Model
{
    public const LINKED_VIA_SSO = 'sso';

    public const LINKED_VIA_AUTO = 'auto';

    protected $fillable = ['juntra_user_id', 'user_id', 'linked_via', 'merged_from_user_id', 'merged_at'];

    protected $casts = [
        'juntra_user_id' => 'integer',
        'user_id' => 'integer',
        'merged_from_user_id' => 'integer',
        'merged_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
