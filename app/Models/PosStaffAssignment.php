<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;

/**
 * PosStaffAssignment Model
 *
 * กำหนดพนักงานขาย POS และสิทธิ์การเข้าถึง
 *
 * @property int $id
 * @property int $employee_id
 * @property int|null $pos_device_id
 * @property int|null $work_shift_id
 * @property int $store_id
 * @property string $staff_code รหัสพนักงานขาย
 * @property string|null $pin_code PIN 4-6 หลัก
 * @property string|null $rfid_tag RFID card
 * @property string|null $fingerprint_id ลายนิ้วมือ
 * @property array|null $pos_permissions สิทธิ์การเข้าถึงเมนู
 * @property float $max_discount_percent ส่วนลดสูงสุด (%)
 * @property float $max_discount_amount ส่วนลดสูงสุด (บาท)
 * @property bool $can_void_transaction สามารถยกเลิกรายการ
 * @property bool $can_refund สามารถคืนเงิน
 * @property bool $can_open_drawer สามารถเปิดลิ้นชัก
 * @property bool $can_view_reports สามารถดูรายงาน
 * @property bool $can_manage_inventory สามารถจัดการสต็อก
 * @property string|null $effective_from วันที่เริ่มทำงาน
 * @property string|null $effective_to วันที่สิ้นสุด
 * @property array|null $working_days วันทำงาน
 * @property bool $is_active สถานะ
 * @property bool $is_primary พนักงานหลัก
 */
class PosStaffAssignment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'pos_device_id',
        'work_shift_id',
        'store_id',
        'staff_code',
        'pin_code',
        'rfid_tag',
        'fingerprint_id',
        'pos_permissions',
        'max_discount_percent',
        'max_discount_amount',
        'can_void_transaction',
        'can_refund',
        'can_open_drawer',
        'can_view_reports',
        'can_manage_inventory',
        'effective_from',
        'effective_to',
        'working_days',
        'is_active',
        'is_primary',
        'total_transactions',
        'total_sales',
        'last_login_at',
        'last_transaction_at',
    ];

    protected $casts = [
        'pos_permissions' => 'array',
        'working_days' => 'array',
        'max_discount_percent' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'can_void_transaction' => 'boolean',
        'can_refund' => 'boolean',
        'can_open_drawer' => 'boolean',
        'can_view_reports' => 'boolean',
        'can_manage_inventory' => 'boolean',
        'is_active' => 'boolean',
        'is_primary' => 'boolean',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'last_login_at' => 'datetime',
        'last_transaction_at' => 'datetime',
        'total_sales' => 'decimal:2',
    ];

    /**
     * PIN code จะถูก hash เมื่อตั้งค่า
     */
    public function setPinCodeAttribute($value)
    {
        if ($value && strlen($value) >= 4) {
            $this->attributes['pin_code'] = Hash::make($value);
        }
    }

    /**
     * ตรวจสอบ PIN code
     */
    public function verifyPin(string $pin): bool
    {
        return Hash::check($pin, $this->pin_code);
    }

    /**
     * ความสัมพันธ์กับ Employee
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * ความสัมพันธ์กับ PosDevice
     */
    public function posDevice(): BelongsTo
    {
        return $this->belongsTo(PosDevice::class);
    }

    /**
     * ความสัมพันธ์กับ WorkShift
     */
    public function workShift(): BelongsTo
    {
        return $this->belongsTo(WorkShift::class);
    }

    /**
     * ความสัมพันธ์กับ VendorStore
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(VendorStore::class, 'store_id');
    }

    /**
     * ความสัมพันธ์กับ PosClockRecord
     */
    public function clockRecords(): HasMany
    {
        return $this->hasMany(PosClockRecord::class);
    }

    /**
     * ตรวจสอบว่ามีสิทธิ์หรือไม่
     */
    public function hasPermission(string $permission): bool
    {
        $permissions = $this->pos_permissions ?? [];
        return in_array($permission, $permissions) || in_array('all', $permissions);
    }

    /**
     * ตรวจสอบส่วนลดที่ให้ได้
     */
    public function canApplyDiscount(float $amount, float $percentage = null): bool
    {
        if ($percentage !== null && $this->max_discount_percent > 0) {
            if ($percentage > $this->max_discount_percent) {
                return false;
            }
        }

        if ($this->max_discount_amount > 0 && $amount > $this->max_discount_amount) {
            return false;
        }

        return true;
    }

    /**
     * ชื่อพนักงาน
     */
    public function getStaffNameAttribute(): string
    {
        return $this->employee ? $this->employee->full_name : 'Unknown';
    }

    /**
     * ชื่อพนักงาน (ภาษาไทย)
     */
    public function getStaffNameThAttribute(): string
    {
        return $this->employee ? $this->employee->full_name_th : 'ไม่ทราบ';
    }

    /**
     * ตรวจสอบว่าวันนี้ทำงานหรือไม่
     */
    public function isWorkingToday(): bool
    {
        $today = now()->dayOfWeek; // 0 = Sunday, 6 = Saturday
        $workingDays = $this->working_days ?? [1, 2, 3, 4, 5]; // Default: Mon-Fri

        return in_array($today, $workingDays);
    }

    /**
     * ตรวจสอบว่ายังมีผลบังคับใช้หรือไม่
     */
    public function isEffective(): bool
    {
        $now = now()->startOfDay();

        if ($this->effective_from && $now->lt($this->effective_from)) {
            return false;
        }

        if ($this->effective_to && $now->gt($this->effective_to)) {
            return false;
        }

        return true;
    }

    /**
     * อัพเดทสถิติการทำรายการ
     */
    public function updateTransactionStats(float $amount): void
    {
        $this->increment('total_transactions');
        $this->increment('total_sales', $amount);
        $this->update(['last_transaction_at' => now()]);
    }

    /**
     * บันทึกการ login
     */
    public function recordLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    /**
     * สร้างรหัสพนักงานขาย
     */
    public static function generateStaffCode(int $storeId): string
    {
        $prefix = 'POS';
        $count = static::where('store_id', $storeId)->count() + 1;

        return sprintf('%s-%d-%04d', $prefix, $storeId, $count);
    }

    /**
     * Scope: Active staff
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: For store
     */
    public function scopeForStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    /**
     * Scope: For device
     */
    public function scopeForDevice($query, $deviceId)
    {
        return $query->where(function ($q) use ($deviceId) {
            $q->where('pos_device_id', $deviceId)
              ->orWhereNull('pos_device_id'); // Can use any device
        });
    }

    /**
     * Scope: Currently effective
     */
    public function scopeEffective($query)
    {
        $now = now()->startOfDay();

        return $query
            ->where(function ($q) use ($now) {
                $q->whereNull('effective_from')
                  ->orWhere('effective_from', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', $now);
            });
    }

    /**
     * Default permissions
     */
    public static function getDefaultPermissions(): array
    {
        return [
            'sales' => 'การขาย',
            'refund' => 'คืนเงิน',
            'discount' => 'ส่วนลด',
            'void' => 'ยกเลิกรายการ',
            'reports' => 'รายงาน',
            'inventory' => 'จัดการสต็อก',
            'settings' => 'ตั้งค่า',
            'customers' => 'ลูกค้า',
            'clock' => 'ลงเวลา',
            'drawer' => 'เปิดลิ้นชัก',
        ];
    }
}
