<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AccountingFlowaccountSyncLog Model
 *
 * บันทึกประวัติการซิงค์ข้อมูลกับ FlowAccount
 *
 * @property int $id
 * @property int $connection_id
 * @property string $type ประเภทข้อมูล (contact, product, invoice, expense)
 * @property string $action การดำเนินการ (create, update, delete, sync)
 * @property int|null $record_id ID ของ record ในระบบ
 * @property string|null $flowaccount_id ID ใน FlowAccount
 * @property bool $success สำเร็จหรือไม่
 * @property string|null $error_message ข้อความ error
 * @property array|null $request_data ข้อมูล request
 * @property array|null $response_data ข้อมูล response
 * @property \Carbon\Carbon|null $synced_at เวลาที่ซิงค์
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class AccountingFlowaccountSyncLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'accounting_flowaccount_sync_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'connection_id',
        'type',
        'action',
        'record_id',
        'flowaccount_id',
        'success',
        'error_message',
        'request_data',
        'response_data',
        'synced_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'success' => 'boolean',
        'request_data' => 'array',
        'response_data' => 'array',
        'synced_at' => 'datetime',
    ];

    /**
     * ความสัมพันธ์กับ Connection
     *
     * @return BelongsTo
     */
    public function connection(): BelongsTo
    {
        return $this->belongsTo(AccountingFlowaccountConnection::class, 'connection_id');
    }

    /**
     * Scope สำหรับกรองตามประเภท
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope สำหรับกรองเฉพาะที่สำเร็จ
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSuccessful($query)
    {
        return $query->where('success', true);
    }

    /**
     * Scope สำหรับกรองเฉพาะที่ล้มเหลว
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailed($query)
    {
        return $query->where('success', false);
    }

    /**
     * Scope สำหรับกรองตามวันที่
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $startDate
     * @param string|null $endDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDateRange($query, string $startDate, ?string $endDate = null)
    {
        $query->whereDate('synced_at', '>=', $startDate);

        if ($endDate) {
            $query->whereDate('synced_at', '<=', $endDate);
        }

        return $query;
    }

    /**
     * ดึง label สำหรับ type
     *
     * @return string
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'contact' => 'ผู้ติดต่อ',
            'product' => 'สินค้า',
            'invoice' => 'ใบแจ้งหนี้',
            'expense' => 'ค่าใช้จ่าย',
            'tax_invoice' => 'ใบกำกับภาษี',
            'cash_invoice' => 'ใบกำกับภาษีเงินสด',
            'receivable_invoice' => 'ใบแจ้งหนี้',
            'quotation' => 'ใบเสนอราคา',
            'billing_note' => 'ใบวางบิล',
            'receipt' => 'ใบเสร็จรับเงิน',
            default => $this->type,
        };
    }

    /**
     * ดึง label สำหรับ action
     *
     * @return string
     */
    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'create' => 'สร้าง',
            'update' => 'อัปเดต',
            'delete' => 'ลบ',
            'sync' => 'ซิงค์',
            'payment' => 'บันทึกชำระเงิน',
            'update_status' => 'อัปเดตสถานะ',
            default => $this->action,
        };
    }

    /**
     * ดึง badge class สำหรับ status
     *
     * @return string
     */
    public function getStatusBadgeAttribute(): string
    {
        return $this->success
            ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
            : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300';
    }
}
