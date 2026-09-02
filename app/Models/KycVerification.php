<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

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
    ];

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
     * @return string|null URL เต็มสำหรับใส่ใน <img src="..."> หรือ null ถ้าไฟล์หาย
     */
    public function idCardImageUrl(): ?string
    {
        return $this->hasIdCardImage() ? asset('storage/'.$this->id_card_image) : null;
    }

    /**
     * URL ของรูปถ่ายคู่บัตร — คืน null เมื่อไม่มีไฟล์จริง
     *
     * @return string|null URL เต็มสำหรับใส่ใน <img src="..."> หรือ null ถ้าไฟล์หาย
     */
    public function selfieImageUrl(): ?string
    {
        return $this->hasSelfieImage() ? asset('storage/'.$this->selfie_image) : null;
    }

    /**
     * เช็คไฟล์บน public disk อย่างปลอดภัย
     *
     * @param  string|null  $path  พาธที่เก็บในฐานข้อมูล (relative กับ storage/app/public)
     */
    protected function imageFileExists(?string $path): bool
    {
        if (blank($path)) {
            return false;
        }

        try {
            return Storage::disk('public')->exists($path);
        } catch (\Throwable $e) {
            // ดิสก์มีปัญหา (permission/symlink) — ถือว่าไม่มีไฟล์ ดีกว่าให้หน้าแอดมินพัง
            return false;
        }
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
