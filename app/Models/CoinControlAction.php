<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * CoinControlAction Model
 *
 * เก็บประวัติการดำเนินการควบคุมเหรียญ เช่น mint, burn, freeze/unfreeze address
 *
 * @property int $id
 * @property int $token_id รหัสโทเค็น
 * @property int $admin_id รหัสผู้ดูแลระบบที่ทำการดำเนินการ
 * @property string $action_type ประเภทการดำเนินการ (mint, burn, freeze_address, etc.)
 * @property string|null $target_address ที่อยู่เป้าหมาย
 * @property string|null $amount จำนวนสำหรับ mint/burn
 * @property string $reason เหตุผลในการดำเนินการ
 * @property string|null $tx_hash Hash ของ transaction บน blockchain
 * @property string $status สถานะ (pending, success, failed)
 * @property string|null $error_message ข้อความ error ถ้าล้มเหลว
 * @property array|null $metadata ข้อมูลเพิ่มเติม
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class CoinControlAction extends Model
{
    use HasFactory;

    /**
     * ชื่อตารางที่เกี่ยวข้อง
     *
     * @var string
     */
    protected $table = 'coin_control_actions';

    /**
     * คอลัมน์ที่สามารถกำหนดค่าได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'token_id',
        'admin_id',
        'action_type',
        'target_address',
        'amount',
        'reason',
        'tx_hash',
        'status',
        'error_message',
        'metadata',
    ];

    /**
     * การแปลงประเภทข้อมูล
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * ความสัมพันธ์กับ TPIXToken
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function token()
    {
        return $this->belongsTo(TPIXToken::class, 'token_id');
    }

    /**
     * ความสัมพันธ์กับ User (ผู้ดูแลระบบ)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Scope สำหรับกรองเฉพาะที่สำเร็จ
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSuccess($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope สำหรับกรองเฉพาะที่ล้มเหลว
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope สำหรับกรองเฉพาะที่รอดำเนินการ
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope สำหรับกรองตามประเภทการดำเนินการ
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type ประเภทการดำเนินการ
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('action_type', $type);
    }

    /**
     * Scope สำหรับกรองตามผู้ดูแลระบบ
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $adminId รหัสผู้ดูแลระบบ
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByAdmin($query, $adminId)
    {
        return $query->where('admin_id', $adminId);
    }

    /**
     * Scope สำหรับกรองเฉพาะรายการล่าสุดตามชั่วโมงที่กำหนด
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $hours จำนวนชั่วโมง
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    /**
     * ตรวจสอบว่าสำเร็จหรือไม่
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    /**
     * ตรวจสอบว่าล้มเหลวหรือไม่
     *
     * @return bool
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * ตรวจสอบว่ารอดำเนินการหรือไม่
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * รับคำอธิบายการดำเนินการเป็นภาษาไทย
     *
     * @return string
     */
    public function getActionDescription(): string
    {
        $descriptions = [
            'mint' => 'สร้างโทเค็นใหม่',
            'burn' => 'เผาโทเค็น',
            'freeze_address' => 'ระงับที่อยู่',
            'unfreeze_address' => 'ยกเลิกระงับที่อยู่',
            'pause_contract' => 'หยุดสัญญาชั่วคราว',
            'unpause_contract' => 'เปิดใช้งานสัญญา',
            'transfer_ownership' => 'โอนความเป็นเจ้าของ',
            'update_settings' => 'อัพเดทการตั้งค่า',
            'whitelist_add' => 'เพิ่มในรายการอนุญาต',
            'whitelist_remove' => 'ลบจากรายการอนุญาต',
            'blacklist_add' => 'เพิ่มในรายการห้าม',
            'blacklist_remove' => 'ลบจากรายการห้าม',
            'update_supply' => 'อัพเดทจำนวน supply',
        ];

        return $descriptions[$this->action_type] ?? 'การดำเนินการไม่ทราบ';
    }

    /**
     * รับไอคอนสำหรับการดำเนินการ
     *
     * @return string
     */
    public function getActionIcon(): string
    {
        $icons = [
            'mint' => '➕',
            'burn' => '🔥',
            'freeze_address' => '❄️',
            'unfreeze_address' => '🔓',
            'pause_contract' => '⏸️',
            'unpause_contract' => '▶️',
            'transfer_ownership' => '🔑',
            'update_settings' => '⚙️',
            'whitelist_add' => '✅',
            'whitelist_remove' => '❎',
            'blacklist_add' => '🚫',
            'blacklist_remove' => '✔️',
            'update_supply' => '📊',
        ];

        return $icons[$this->action_type] ?? '📝';
    }

    /**
     * รับ URL สำหรับดู transaction บน explorer
     *
     * @return string|null
     */
    public function getExplorerUrl(): ?string
    {
        if (!$this->tx_hash) {
            return null;
        }

        $explorerBaseUrl = config('crypto.networks.tpix.explorer');
        return $explorerBaseUrl . '/tx/' . $this->tx_hash;
    }
}
