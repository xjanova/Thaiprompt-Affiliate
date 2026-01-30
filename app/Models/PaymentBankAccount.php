<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * PaymentBankAccount Model
 *
 * จัดการบัญชีธนาคารหลายบัญชีสำหรับรับชำระเงิน
 * รองรับทั้งโอนผ่านธนาคาร, PromptPay และ SMS Checker
 *
 * @property int $id
 * @property string $bank_code รหัสธนาคาร (KBANK, SCB, KTB, ...)
 * @property string $bank_name ชื่อธนาคาร
 * @property string $account_number เลขที่บัญชี
 * @property string $account_name ชื่อบัญชี
 * @property string|null $branch สาขา
 * @property string|null $promptpay_id หมายเลข PromptPay
 * @property string|null $promptpay_type ประเภท PromptPay (phone/citizen_id/ewallet)
 * @property string|null $qr_image รูป QR Code
 * @property bool $sms_checker_enabled เปิดใช้ SMS Checker ยืนยันอัตโนมัติ
 * @property bool $is_active เปิดใช้งาน
 * @property bool $is_default บัญชีหลัก
 * @property int $sort_order ลำดับการแสดง
 * @property string|null $notes หมายเหตุ
 */
class PaymentBankAccount extends Model
{
    use SoftDeletes;

    protected $table = 'payment_bank_accounts';

    protected $fillable = [
        'bank_code',
        'bank_name',
        'account_number',
        'account_name',
        'branch',
        'promptpay_id',
        'promptpay_type',
        'qr_image',
        'sms_checker_enabled',
        'is_active',
        'is_default',
        'sort_order',
        'notes',
    ];

    protected $casts = [
        'sms_checker_enabled' => 'boolean',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * รายชื่อธนาคารที่รองรับ
     *
     * @return array
     */
    public static function supportedBanks(): array
    {
        return config('smschecker.supported_banks', [
            'KBANK' => 'ธนาคารกสิกรไทย',
            'SCB' => 'ธนาคารไทยพาณิชย์',
            'KTB' => 'ธนาคารกรุงไทย',
            'BBL' => 'ธนาคารกรุงเทพ',
            'GSB' => 'ธนาคารออมสิน',
            'BAY' => 'ธนาคารกรุงศรีอยุธยา',
            'TTB' => 'ธนาคารทีทีบี',
            'PROMPTPAY' => 'พร้อมเพย์',
        ]);
    }

    // Scopes

    /**
     * เฉพาะบัญชีที่เปิดใช้งาน
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * เฉพาะบัญชีหลัก
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * เฉพาะบัญชีที่เปิด SMS Checker
     */
    public function scopeSmsCheckerEnabled($query)
    {
        return $query->where('sms_checker_enabled', true);
    }

    /**
     * เฉพาะบัญชีที่มี PromptPay
     */
    public function scopeHasPromptpay($query)
    {
        return $query->whereNotNull('promptpay_id');
    }

    /**
     * กรองตามธนาคาร
     */
    public function scopeByBank($query, string $bankCode)
    {
        return $query->where('bank_code', $bankCode);
    }

    /**
     * เรียงตาม sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('bank_name');
    }

    // Accessors

    /**
     * เลขที่บัญชีแบบ mask (แสดงเฉพาะ 4 ตัวท้าย)
     */
    public function getMaskedAccountNumberAttribute(): string
    {
        $number = $this->account_number;
        if (strlen($number) <= 4) {
            return $number;
        }
        return str_repeat('*', strlen($number) - 4) . substr($number, -4);
    }

    /**
     * PromptPay ID แบบ mask
     */
    public function getMaskedPromptpayIdAttribute(): ?string
    {
        if (empty($this->promptpay_id)) {
            return null;
        }
        $id = $this->promptpay_id;
        if (strlen($id) <= 6) {
            return $id;
        }
        return substr($id, 0, 3) . str_repeat('*', strlen($id) - 6) . substr($id, -3);
    }

    /**
     * ชื่อแสดง (ธนาคาร + เลขบัญชี mask)
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->bank_name} ({$this->masked_account_number})";
    }

    /**
     * URL ของ QR Code
     */
    public function getQrImageUrlAttribute(): ?string
    {
        if (empty($this->qr_image)) {
            return null;
        }
        return asset('storage/' . $this->qr_image);
    }

    // ความสัมพันธ์

    /**
     * ดึง SMS notifications ที่ตรงกับธนาคารนี้
     */
    public function smsNotifications()
    {
        return SmsPaymentNotification::where('bank', $this->bank_code);
    }

    // Methods

    /**
     * ตั้งเป็นบัญชีหลัก (ยกเลิก default เดิม)
     */
    public function setAsDefault(): void
    {
        // ยกเลิก default เดิม
        static::where('is_default', true)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }

    /**
     * ตรวจสอบว่ารองรับ PromptPay หรือไม่
     */
    public function hasPromptpay(): bool
    {
        return !empty($this->promptpay_id);
    }

    /**
     * ตรวจสอบว่าเปิด SMS Checker หรือไม่
     */
    public function isSmsCheckerEnabled(): bool
    {
        return $this->sms_checker_enabled === true;
    }
}
