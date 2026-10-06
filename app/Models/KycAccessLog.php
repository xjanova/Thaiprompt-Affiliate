<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * บันทึกการเปิดดูรูป KYC ของแอดมิน (PDPA) — 1 แถวต่อการเปิดดู 1 รูป
 *
 * เขียนจาก Admin\KycController::image() เท่านั้น · ไม่มีหน้าแก้/ลบ
 * ไม่ผูก foreign key กับ kyc_verifications (แถว KYC ถูกลบตอนลบบัญชี แต่บันทึกนี้ต้องอยู่ต่อ)
 *
 * @property int $id
 * @property int $kyc_verification_id
 * @property int|null $subject_user_id เจ้าของข้อมูล
 * @property int|null $viewer_id แอดมินที่เปิดดู
 * @property string $kind card | card_face | best_frame
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Carbon\Carbon|null $created_at
 */
class KycAccessLog extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'kyc_access_logs';

    protected $fillable = [
        'kyc_verification_id',
        'subject_user_id',
        'viewer_id',
        'kind',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * บันทึกการเปิดดูรูป 1 ครั้ง — ทางเดียวที่ใช้ทั้งหลังบ้านเว็บและแอปแอดมิน (ข้อมูลเหมือนกันทุกช่องทาง)
     */
    public static function record(KycVerification $kyc, ?User $viewer, string $kind, \Illuminate\Http\Request $request): self
    {
        return self::create([
            'kyc_verification_id' => $kyc->id,
            'subject_user_id' => $kyc->user_id,
            'viewer_id' => $viewer?->id,
            'kind' => $kind,
            'ip_address' => $request->ip(),
            'user_agent' => \Illuminate\Support\Str::limit((string) $request->userAgent(), 250, ''),
        ]);
    }

    /**
     * แอดมินที่เปิดดู
     */
    public function viewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'viewer_id');
    }
}
