<?php

namespace App\Models;

use App\Services\Kyc\KycDocumentStorage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KycVerification extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'id_card_image',
        'selfie_image',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
        'submitted_at',
        'extracted_data',
        // 🪪 AI eKYC (2026-10-04) — เขียนจาก EkycService เท่านั้น
        'method',
        'ekyc_session_id',
        'consent_at',
        'consent_version',
        'id_number_encrypted',
        'id_number_hash',
        'id_last4',
        'name_th',
        'name_en',
        'birth_date',
        'card_expiry',
        'ai_decision',
        'ai_reasons',
        'ai_face_match',
        'ai_liveness',
        'ai_real',
        'ai_card_real',
        'ai_ocr_confidence',
        'ai_model_version',
        'card_face_path',
        'best_frame_path',
        'processed_at',
        'ekyc_challenges',
        'ekyc_expires_at',
        'ekyc_step',
        'ekyc_frame_hashes',
    ];

    /**
     * ห้ามหลุดออกไปกับ toArray()/JSON (เลขบัตร + path ไฟล์ส่วนตัว)
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'id_number_encrypted',
        'id_number_hash',
        'card_face_path',
        'best_frame_path',
        'ekyc_challenges',
        'ekyc_frame_hashes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'reviewed_at' => 'datetime',
        'submitted_at' => 'datetime',
        'extracted_data' => 'array',
        // 🪪 AI eKYC
        'consent_at' => 'datetime',
        'id_number_encrypted' => 'encrypted',
        'birth_date' => 'date',
        'card_expiry' => 'date',
        'ai_reasons' => 'array',
        'ai_face_match' => 'float',
        'ai_liveness' => 'float',
        'ai_real' => 'float',
        'ai_card_real' => 'float',
        'ai_ocr_confidence' => 'float',
        'processed_at' => 'datetime',
        'ekyc_challenges' => 'array',
        'ekyc_expires_at' => 'datetime',
        'ekyc_frame_hashes' => 'array',
    ];

    /** ช่องทางยืนยันตัวตน */
    public const METHOD_MANUAL = 'manual';

    public const METHOD_EKYC = 'ekyc';

    /**
     * สถานะเพิ่มเติมของแถว eKYC (นอกจาก draft/pending/approved/rejected เดิม)
     * - retake      = รอบนี้ไม่ผ่านชัดเจน (AI หรือแอดมินขอให้ถ่ายใหม่) — ผู้ใช้เริ่มรอบใหม่ได้
     * - superseded  = คำขอที่ค้างอยู่ถูกแทนด้วยการยืนยัน eKYC ที่ผ่านแล้ว
     */
    public const STATUS_RETAKE = 'retake';

    public const STATUS_SUPERSEDED = 'superseded';

    /**
     * Get the user that owns the KYC verification.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who reviewed the KYC.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * แถวนี้มาจาก AI eKYC หรือไม่
     */
    public function isEkyc(): bool
    {
        return $this->method === self::METHOD_EKYC;
    }

    /**
     * Check if KYC is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if KYC is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if KYC is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * ตรวจสอบว่ามี "ไฟล์จริง" ของรูปบัตรประชาชนอยู่บนดิสก์หรือไม่
     *
     * ⚠️ สำคัญ: มีพาธในฐานข้อมูล ≠ มีไฟล์จริง
     * แถวเดโมจาก seeder และแถวเก่าที่ไฟล์ถูกลบ จะมีพาธค้างอยู่แต่เปิดไม่ขึ้น
     * ถ้าเช็คแค่ ! empty($kyc->id_card_image) หน้าแอดมินจะโชว์ไอคอนรูปแตกโดยไม่บอกสาเหตุ
     */
    public function hasIdCardImage(): bool
    {
        return $this->imageFileExists($this->id_card_image);
    }

    /**
     * ตรวจสอบว่ามี "ไฟล์จริง" ของรูปถ่ายคู่บัตรอยู่บนดิสก์หรือไม่
     */
    public function hasSelfieImage(): bool
    {
        return $this->imageFileExists($this->selfie_image);
    }

    /**
     * URL ของรูปบัตรประชาชน — คืน null เมื่อไม่มีไฟล์จริง
     *
     * @param  string  $audience  owner = หน้าสมาชิก · admin = หลังบ้าน · signed = แอปมือถือ (signed URL อายุสั้น)
     * @return string|null URL ของ route ที่ตรวจสิทธิ์ (ไม่ใช่ /storage/…) หรือ null ถ้าไฟล์หาย
     */
    public function idCardImageUrl(string $audience = 'owner'): ?string
    {
        return $this->documentImageUrl('id_card', $audience);
    }

    /**
     * URL ของรูปถ่ายคู่บัตร — คืน null เมื่อไม่มีไฟล์จริง
     *
     * @param  string  $audience  owner = หน้าสมาชิก · admin = หลังบ้าน · signed = แอปมือถือ (signed URL อายุสั้น)
     */
    public function selfieImageUrl(string $audience = 'owner'): ?string
    {
        return $this->documentImageUrl('selfie', $audience);
    }

    /**
     * 🔒 (2026-10-29) URL ของรูปเอกสาร KYC แบบเดิม — ชี้ไป route ที่ตรวจสิทธิ์เสมอ
     *
     * เดิมคืน asset('storage/…') = ใครได้ลิงก์ก็เปิดบัตรประชาชนได้ · รูป eKYC ใช้ admin.kyc.image ชนิด card/card_face/best_frame
     *
     * @param  string  $kind  id_card | selfie
     * @param  string  $audience  owner | admin | signed
     */
    public function documentImageUrl(string $kind, string $audience = 'owner'): ?string
    {
        $documents = app(KycDocumentStorage::class);

        if (! $documents->exists($documents->pathFor($this, $kind))) {
            return null;
        }

        return match ($audience) {
            'admin' => route('admin.kyc.image', [$this, $kind]),
            'signed' => $documents->signedUrl($this, $kind),
            default => route('user.kyc.image', [$this, $kind]),
        };
    }

    /**
     * เช็คไฟล์อย่างปลอดภัย (private disk ก่อน แล้วค่อย public disk ของไฟล์เก่าที่ยังไม่ย้าย)
     *
     * @param  string|null  $path  พาธที่เก็บในฐานข้อมูล
     */
    protected function imageFileExists(?string $path): bool
    {
        if ($this->isEkyc()) {
            return false;
        }

        return app(KycDocumentStorage::class)->exists($path);
    }

    /**
     * เฉพาะแถว AI eKYC
     */
    public function scopeEkyc($query)
    {
        return $query->where('method', self::METHOD_EKYC);
    }

    /**
     * Scope a query to only include pending verifications.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include approved verifications.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope a query to only include rejected verifications.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
}
