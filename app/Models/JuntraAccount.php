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
 * @property int|null $enrolled_member_id ตำแหน่งในผังที่จันทราสร้างให้ (ย้ายสายจากหลังบ้านจันทราได้เฉพาะตัวนี้)
 * @property \Illuminate\Support\Carbon|null $released_at จันทราแจ้งลบบัญชี user_ref นี้แล้ว (ยังชี้ผู้ใช้เดิมจนมีบัญชีใหม่มาผูก)
 * @property int|null $released_juntra_user_id user_ref เดิม หลังแถวนี้ถูกชี้ไปบัญชีจันทราใหม่
 */
class JuntraAccount extends Model
{
    public const LINKED_VIA_SSO = 'sso';

    public const LINKED_VIA_AUTO = 'auto';

    /** รวมบัญชีไม่สำเร็จแล้วเว้นกี่ชั่วโมงก่อนลองใหม่ (ให้แอดมินจัดผังก่อน) */
    public const MERGE_RETRY_HOURS = 24;

    protected $fillable = [
        'juntra_user_id', 'user_id', 'linked_via', 'enrolled_member_id',
        'merged_from_user_id', 'merged_at', 'merge_failed_at', 'merge_error',
        // 🌙 (2026-11-03) จันทราลบบัญชี → ปลดให้บัญชีจันทราใหม่ผูก Thaiprompt คนเดิมได้ (ไม่สร้างเงา)
        'released_at', 'released_juntra_user_id',
    ];

    protected $casts = [
        'juntra_user_id' => 'integer',
        'user_id' => 'integer',
        'enrolled_member_id' => 'integer',
        'merged_from_user_id' => 'integer',
        'merged_at' => 'datetime',
        'merge_failed_at' => 'datetime',
        'released_at' => 'datetime',
        'released_juntra_user_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** ตำแหน่งนี้จันทราสร้างให้ลูกค้าจันทรา — หลังบ้านจันทราจัดการได้ */
    public static function managesMember(int $memberId): bool
    {
        return static::where('enrolled_member_id', $memberId)->exists();
    }
}
