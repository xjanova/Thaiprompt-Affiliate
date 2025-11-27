<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

/**
 * PosClockRecord Model
 *
 * บันทึกการลงเวลาเข้า-ออกของพนักงาน POS
 *
 * @property int $id
 * @property int $pos_staff_assignment_id
 * @property int|null $pos_session_id
 * @property int|null $work_shift_id
 * @property string $work_date วันที่ทำงาน
 * @property \Carbon\Carbon|null $clock_in_at เวลาเข้า
 * @property \Carbon\Carbon|null $clock_out_at เวลาออก
 * @property \Carbon\Carbon|null $break_start_at เริ่มพัก
 * @property \Carbon\Carbon|null $break_end_at สิ้นสุดพัก
 * @property string|null $clock_in_method วิธีลงเวลาเข้า
 * @property string|null $clock_out_method วิธีลงเวลาออก
 * @property string $status สถานะ
 * @property int $scheduled_minutes เวลาตามกะ
 * @property int $worked_minutes เวลาทำงานจริง
 * @property int $overtime_minutes OT
 * @property int $late_minutes มาสาย
 * @property int $early_out_minutes ออกก่อน
 * @property int $break_minutes พักเกิน
 * @property int $transactions_count จำนวนรายการ
 * @property float $sales_amount ยอดขาย
 * @property float $cash_handled เงินสดที่รับ
 * @property float $tips_received ทิป
 */
class PosClockRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'pos_staff_assignment_id',
        'pos_session_id',
        'work_shift_id',
        'work_date',
        'clock_in_at',
        'clock_out_at',
        'break_start_at',
        'break_end_at',
        'clock_in_method',
        'clock_out_method',
        'status',
        'scheduled_minutes',
        'worked_minutes',
        'overtime_minutes',
        'late_minutes',
        'early_out_minutes',
        'break_minutes',
        'transactions_count',
        'sales_amount',
        'cash_handled',
        'tips_received',
        'notes',
        'clock_in_notes',
        'clock_out_notes',
        'clock_in_latitude',
        'clock_in_longitude',
        'clock_out_latitude',
        'clock_out_longitude',
        'is_modified',
        'modified_by',
        'modified_at',
        'modification_reason',
    ];

    protected $casts = [
        'work_date' => 'date',
        'clock_in_at' => 'datetime',
        'clock_out_at' => 'datetime',
        'break_start_at' => 'datetime',
        'break_end_at' => 'datetime',
        'modified_at' => 'datetime',
        'sales_amount' => 'decimal:2',
        'cash_handled' => 'decimal:2',
        'tips_received' => 'decimal:2',
        'is_modified' => 'boolean',
        'clock_in_latitude' => 'decimal:7',
        'clock_in_longitude' => 'decimal:7',
        'clock_out_latitude' => 'decimal:7',
        'clock_out_longitude' => 'decimal:7',
    ];

    /**
     * ความสัมพันธ์กับ PosStaffAssignment
     */
    public function staffAssignment(): BelongsTo
    {
        return $this->belongsTo(PosStaffAssignment::class, 'pos_staff_assignment_id');
    }

    /**
     * ความสัมพันธ์กับ PosSession
     */
    public function posSession(): BelongsTo
    {
        return $this->belongsTo(PosSession::class);
    }

    /**
     * ความสัมพันธ์กับ WorkShift
     */
    public function workShift(): BelongsTo
    {
        return $this->belongsTo(WorkShift::class);
    }

    /**
     * ความสัมพันธ์กับ User (ผู้แก้ไข)
     */
    public function modifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'modified_by');
    }

    /**
     * Clock In
     */
    public function clockIn(string $method = 'pin', array $options = []): void
    {
        $now = now();

        $this->update([
            'clock_in_at' => $now,
            'clock_in_method' => $method,
            'status' => 'clocked_in',
            'clock_in_notes' => $options['notes'] ?? null,
            'clock_in_latitude' => $options['latitude'] ?? null,
            'clock_in_longitude' => $options['longitude'] ?? null,
        ]);

        // คำนวณความล่าช้า
        if ($this->workShift) {
            $scheduledStart = Carbon::parse($this->work_date->format('Y-m-d') . ' ' . $this->workShift->start_time);
            $lateMinutes = $now->gt($scheduledStart) ? $now->diffInMinutes($scheduledStart) : 0;

            if ($lateMinutes > 0) {
                $this->update([
                    'late_minutes' => $lateMinutes,
                    'status' => 'late',
                ]);
            }
        }
    }

    /**
     * Clock Out
     */
    public function clockOut(string $method = 'pin', array $options = []): void
    {
        $now = now();

        // คำนวณเวลาทำงาน
        $workedMinutes = 0;
        if ($this->clock_in_at) {
            $workedMinutes = $this->clock_in_at->diffInMinutes($now) - $this->break_minutes;
        }

        // คำนวณ OT และออกก่อน
        $overtimeMinutes = 0;
        $earlyOutMinutes = 0;

        if ($this->workShift) {
            $scheduledEnd = Carbon::parse($this->work_date->format('Y-m-d') . ' ' . $this->workShift->end_time);

            if ($this->workShift->crosses_midnight) {
                $scheduledEnd->addDay();
            }

            if ($now->gt($scheduledEnd)) {
                $overtimeMinutes = $now->diffInMinutes($scheduledEnd);
            } elseif ($now->lt($scheduledEnd)) {
                $earlyOutMinutes = $scheduledEnd->diffInMinutes($now);
            }
        }

        $this->update([
            'clock_out_at' => $now,
            'clock_out_method' => $method,
            'status' => 'clocked_out',
            'worked_minutes' => $workedMinutes,
            'overtime_minutes' => $overtimeMinutes,
            'early_out_minutes' => $earlyOutMinutes,
            'clock_out_notes' => $options['notes'] ?? null,
            'clock_out_latitude' => $options['latitude'] ?? null,
            'clock_out_longitude' => $options['longitude'] ?? null,
        ]);
    }

    /**
     * Start Break
     */
    public function startBreak(): void
    {
        $this->update([
            'break_start_at' => now(),
            'status' => 'on_break',
        ]);
    }

    /**
     * End Break
     */
    public function endBreak(): void
    {
        $breakMinutes = 0;

        if ($this->break_start_at) {
            $breakMinutes = $this->break_start_at->diffInMinutes(now());
        }

        $this->update([
            'break_end_at' => now(),
            'break_minutes' => $this->break_minutes + $breakMinutes,
            'status' => 'clocked_in',
        ]);
    }

    /**
     * อัพเดทสถิติยอดขาย
     */
    public function updateSalesStats(float $amount, float $cash = 0): void
    {
        $this->increment('transactions_count');
        $this->increment('sales_amount', $amount);

        if ($cash > 0) {
            $this->increment('cash_handled', $cash);
        }
    }

    /**
     * เวลาทำงานจริง (ชั่วโมง)
     */
    public function getWorkedHoursAttribute(): float
    {
        return round($this->worked_minutes / 60, 2);
    }

    /**
     * สถานะภาษาไทย
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'clocked_in' => 'กำลังทำงาน',
            'on_break' => 'พักเบรก',
            'clocked_out' => 'ออกงานแล้ว',
            'absent' => 'ขาดงาน',
            'late' => 'มาสาย',
            'early_out' => 'ออกก่อนเวลา',
            default => $this->status,
        };
    }

    /**
     * สีสถานะ
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'clocked_in' => 'green',
            'on_break' => 'yellow',
            'clocked_out' => 'gray',
            'absent' => 'red',
            'late' => 'orange',
            'early_out' => 'orange',
            default => 'gray',
        };
    }

    /**
     * Scope: For staff
     */
    public function scopeForStaff($query, $staffId)
    {
        return $query->where('pos_staff_assignment_id', $staffId);
    }

    /**
     * Scope: For date range
     */
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('work_date', [$startDate, $endDate]);
    }

    /**
     * Scope: Today
     */
    public function scopeToday($query)
    {
        return $query->where('work_date', now()->toDateString());
    }

    /**
     * Scope: Currently clocked in
     */
    public function scopeClockedIn($query)
    {
        return $query->whereIn('status', ['clocked_in', 'on_break', 'late']);
    }

    /**
     * สร้าง record สำหรับวันนี้
     */
    public static function createForToday(int $staffId, ?int $shiftId = null): self
    {
        return static::firstOrCreate(
            [
                'pos_staff_assignment_id' => $staffId,
                'work_date' => now()->toDateString(),
                'work_shift_id' => $shiftId,
            ],
            [
                'status' => 'clocked_in',
            ]
        );
    }
}
