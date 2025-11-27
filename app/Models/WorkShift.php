<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

/**
 * WorkShift Model
 *
 * กะการทำงานสำหรับพนักงาน
 *
 * @property int $id
 * @property int|null $store_id
 * @property string $name ชื่อกะ (กะเช้า, กะบ่าย, กะดึก)
 * @property string $code รหัสกะ (MORNING, AFTERNOON, NIGHT)
 * @property string|null $description คำอธิบาย
 * @property string $start_time เวลาเริ่มกะ
 * @property string $end_time เวลาสิ้นสุดกะ
 * @property bool $crosses_midnight กะข้ามวัน
 * @property int $break_duration_minutes เวลาพัก (นาที)
 * @property string|null $break_start_time เวลาเริ่มพัก
 * @property string|null $break_end_time เวลาสิ้นสุดพัก
 * @property float $hourly_rate_multiplier ตัวคูณค่าแรง
 * @property float $daily_allowance เบี้ยเลี้ยงต่อกะ
 * @property string $color สี
 * @property string|null $icon ไอคอน
 * @property bool $is_active สถานะ
 * @property int $sort_order ลำดับ
 */
class WorkShift extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'store_id',
        'name',
        'code',
        'description',
        'start_time',
        'end_time',
        'crosses_midnight',
        'break_duration_minutes',
        'break_start_time',
        'break_end_time',
        'hourly_rate_multiplier',
        'daily_allowance',
        'color',
        'icon',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'crosses_midnight' => 'boolean',
        'is_active' => 'boolean',
        'hourly_rate_multiplier' => 'decimal:2',
        'daily_allowance' => 'decimal:2',
    ];

    /**
     * ความสัมพันธ์กับ VendorStore
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(VendorStore::class, 'store_id');
    }

    /**
     * ความสัมพันธ์กับ PosStaffAssignment
     */
    public function staffAssignments(): HasMany
    {
        return $this->hasMany(PosStaffAssignment::class);
    }

    /**
     * ความสัมพันธ์กับ PosClockRecord
     */
    public function clockRecords(): HasMany
    {
        return $this->hasMany(PosClockRecord::class);
    }

    /**
     * คำนวณชั่วโมงทำงานของกะ
     */
    public function getWorkingHoursAttribute(): float
    {
        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);

        if ($this->crosses_midnight) {
            $end->addDay();
        }

        $totalMinutes = $start->diffInMinutes($end);
        $workMinutes = $totalMinutes - $this->break_duration_minutes;

        return round($workMinutes / 60, 2);
    }

    /**
     * ตรวจสอบว่าเวลาปัจจุบันอยู่ในกะหรือไม่
     */
    public function isCurrentlyActive(): bool
    {
        $now = Carbon::now();
        $currentTime = $now->format('H:i:s');

        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);

        if ($this->crosses_midnight) {
            // กะข้ามวัน (เช่น 22:00 - 06:00)
            return $currentTime >= $start->format('H:i:s') || $currentTime <= $end->format('H:i:s');
        }

        return $currentTime >= $start->format('H:i:s') && $currentTime <= $end->format('H:i:s');
    }

    /**
     * รูปแบบเวลาแสดงผล
     */
    public function getTimeRangeAttribute(): string
    {
        $start = Carbon::parse($this->start_time)->format('H:i');
        $end = Carbon::parse($this->end_time)->format('H:i');

        return "{$start} - {$end}";
    }

    /**
     * Scope: Active shifts
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
        return $query->where(function ($q) use ($storeId) {
            $q->where('store_id', $storeId)
              ->orWhereNull('store_id'); // Global shifts
        });
    }

    /**
     * Scope: Ordered by sort
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('start_time');
    }
}
