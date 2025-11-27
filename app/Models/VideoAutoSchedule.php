<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use Cron\CronExpression;

/**
 * VideoAutoSchedule Model
 *
 * ตารางเวลาสำหรับสร้างวีดีโออัตโนมัติ
 *
 * @property int $id
 * @property int|null $template_id เทมเพลต
 * @property string $name ชื่อตารางเวลา
 * @property string|null $description คำอธิบาย
 * @property string $frequency_type ประเภท: once, daily, weekly, monthly, custom
 * @property \Carbon\Carbon|null $run_at เวลารัน (once)
 * @property string|null $daily_time เวลารันทุกวัน
 * @property array|null $weekly_days วันในสัปดาห์
 * @property string|null $weekly_time เวลารัน (weekly)
 * @property array|null $monthly_days วันที่ในเดือน
 * @property string|null $monthly_time เวลารัน (monthly)
 * @property string|null $cron_expression Cron expression
 * @property string $timezone Timezone
 * @property int $videos_per_run จำนวนวีดีโอต่อครั้ง
 * @property bool $randomize_content สุ่มเนื้อหา
 * @property array|null $content_variations รูปแบบเนื้อหา
 * @property bool $auto_publish โพสอัตโนมัติ
 * @property int $publish_delay หน่วงโพส (นาที)
 * @property array|null $publish_platforms Platforms
 * @property bool $stagger_publishing โพสทีละ platform
 * @property int $stagger_interval ระยะห่าง (นาที)
 * @property int|null $max_videos_per_day จำกัดต่อวัน
 * @property int|null $max_videos_per_week จำกัดต่อสัปดาห์
 * @property int|null $max_videos_per_month จำกัดต่อเดือน
 * @property int|null $max_total_videos จำกัดทั้งหมด
 * @property bool $is_active เปิดใช้งาน
 * @property \Carbon\Carbon|null $last_run_at รันล่าสุด
 * @property \Carbon\Carbon|null $next_run_at รันครั้งต่อไป
 * @property int $run_count จำนวนครั้งที่รัน
 * @property int $success_count รันสำเร็จ
 * @property int $fail_count รันล้มเหลว
 * @property \Carbon\Carbon|null $valid_from เริ่มใช้งาน
 * @property \Carbon\Carbon|null $valid_until หมดอายุ
 * @property bool $pause_on_error หยุดเมื่อ error
 * @property int $consecutive_failures ล้มเหลวติดต่อกัน
 * @property int $max_consecutive_failures ล้มเหลวสูงสุด
 * @property string|null $last_error Error ล่าสุด
 * @property int|null $created_by ผู้สร้าง
 */
class VideoAutoSchedule extends Model
{
    use SoftDeletes;

    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'video_auto_schedules';

    /**
     * คอลัมน์ที่สามารถกำหนดค่าได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'template_id',
        'name',
        'description',
        'frequency_type',
        'run_at',
        'daily_time',
        'weekly_days',
        'weekly_time',
        'monthly_days',
        'monthly_time',
        'cron_expression',
        'timezone',
        'videos_per_run',
        'randomize_content',
        'content_variations',
        'auto_publish',
        'publish_delay',
        'publish_platforms',
        'stagger_publishing',
        'stagger_interval',
        'max_videos_per_day',
        'max_videos_per_week',
        'max_videos_per_month',
        'max_total_videos',
        'is_active',
        'last_run_at',
        'next_run_at',
        'run_count',
        'success_count',
        'fail_count',
        'valid_from',
        'valid_until',
        'pause_on_error',
        'consecutive_failures',
        'max_consecutive_failures',
        'last_error',
        'created_by',
    ];

    /**
     * การแปลงประเภทข้อมูล
     *
     * @var array<string, string>
     */
    protected $casts = [
        'weekly_days' => 'array',
        'monthly_days' => 'array',
        'content_variations' => 'array',
        'publish_platforms' => 'array',
        'run_at' => 'datetime',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'randomize_content' => 'boolean',
        'auto_publish' => 'boolean',
        'stagger_publishing' => 'boolean',
        'pause_on_error' => 'boolean',
        'is_active' => 'boolean',
        'videos_per_run' => 'integer',
        'publish_delay' => 'integer',
        'stagger_interval' => 'integer',
        'max_videos_per_day' => 'integer',
        'max_videos_per_week' => 'integer',
        'max_videos_per_month' => 'integer',
        'max_total_videos' => 'integer',
        'run_count' => 'integer',
        'success_count' => 'integer',
        'fail_count' => 'integer',
        'consecutive_failures' => 'integer',
        'max_consecutive_failures' => 'integer',
    ];

    /**
     * ประเภทความถี่ที่รองรับ
     *
     * @var array<string, string>
     */
    public const FREQUENCY_TYPES = [
        'once' => 'ครั้งเดียว',
        'daily' => 'ทุกวัน',
        'weekly' => 'รายสัปดาห์',
        'monthly' => 'รายเดือน',
        'custom' => 'กำหนดเอง (Cron)',
    ];

    /**
     * วันในสัปดาห์
     *
     * @var array<int, string>
     */
    public const WEEKDAYS = [
        0 => 'อาทิตย์',
        1 => 'จันทร์',
        2 => 'อังคาร',
        3 => 'พุธ',
        4 => 'พฤหัสบดี',
        5 => 'ศุกร์',
        6 => 'เสาร์',
    ];

    // =========================================
    // Relationships
    // =========================================

    /**
     * เทมเพลตที่ใช้
     *
     * @return BelongsTo
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(VideoAutoTemplate::class, 'template_id');
    }

    /**
     * ผู้สร้าง
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // =========================================
    // Scopes
    // =========================================

    /**
     * Scope: กรองเฉพาะที่ active
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: กรองเฉพาะที่พร้อมรัน
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDueToRun($query)
    {
        return $query->active()
            ->where(function ($q) {
                $q->whereNull('next_run_at')
                    ->orWhere('next_run_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', now()->toDateString());
            })
            ->where(function ($q) {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', now()->toDateString());
            });
    }

    /**
     * Scope: กรองตาม frequency type
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('frequency_type', $type);
    }

    // =========================================
    // Accessors
    // =========================================

    /**
     * ดึง label ของ frequency type
     *
     * @return string
     */
    public function getFrequencyTypeLabelAttribute(): string
    {
        return self::FREQUENCY_TYPES[$this->frequency_type] ?? $this->frequency_type;
    }

    /**
     * ตรวจสอบว่าถึงเวลารันหรือยัง
     *
     * @return bool
     */
    public function getIsDueAttribute(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if (!$this->isWithinValidPeriod()) {
            return false;
        }

        if ($this->isExceededLimits()) {
            return false;
        }

        if ($this->isPausedDueToErrors()) {
            return false;
        }

        if (!$this->next_run_at) {
            return true;
        }

        return $this->next_run_at->isPast();
    }

    /**
     * ดึงคำอธิบาย schedule
     *
     * @return string
     */
    public function getScheduleDescriptionAttribute(): string
    {
        return match ($this->frequency_type) {
            'once' => 'ครั้งเดียวเมื่อ ' . ($this->run_at?->format('d/m/Y H:i') ?? '-'),
            'daily' => 'ทุกวันเวลา ' . ($this->daily_time ?? '-'),
            'weekly' => 'ทุกสัปดาห์ วัน' . $this->getWeekdaysDescription() . ' เวลา ' . ($this->weekly_time ?? '-'),
            'monthly' => 'ทุกเดือน วันที่ ' . implode(', ', $this->monthly_days ?? []) . ' เวลา ' . ($this->monthly_time ?? '-'),
            'custom' => 'Cron: ' . ($this->cron_expression ?? '-'),
            default => 'ไม่ระบุ',
        };
    }

    /**
     * คำนวณ success rate
     *
     * @return float
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->run_count === 0) {
            return 0;
        }

        return round(($this->success_count / $this->run_count) * 100, 2);
    }

    /**
     * ตรวจสอบว่ามี error หรือไม่
     *
     * @return bool
     */
    public function getHasErrorAttribute(): bool
    {
        return !empty($this->last_error);
    }

    // =========================================
    // Methods
    // =========================================

    /**
     * ดึงคำอธิบายวันในสัปดาห์
     *
     * @return string
     */
    protected function getWeekdaysDescription(): string
    {
        if (empty($this->weekly_days)) {
            return '-';
        }

        return collect($this->weekly_days)
            ->map(fn($day) => self::WEEKDAYS[$day] ?? $day)
            ->implode(', ');
    }

    /**
     * ตรวจสอบว่าอยู่ในช่วงเวลาที่ใช้งานได้หรือไม่
     *
     * @return bool
     */
    public function isWithinValidPeriod(): bool
    {
        $now = now()->toDateString();

        if ($this->valid_from && $this->valid_from > $now) {
            return false;
        }

        if ($this->valid_until && $this->valid_until < $now) {
            return false;
        }

        return true;
    }

    /**
     * ตรวจสอบว่าเกิน limit หรือไม่
     *
     * @return bool
     */
    public function isExceededLimits(): bool
    {
        // ตรวจสอบ total limit
        if ($this->max_total_videos && $this->run_count >= $this->max_total_videos) {
            return true;
        }

        // ตรวจสอบ daily limit
        if ($this->max_videos_per_day) {
            $todayCount = $this->getRunCountForPeriod('day');
            if ($todayCount >= $this->max_videos_per_day) {
                return true;
            }
        }

        // ตรวจสอบ weekly limit
        if ($this->max_videos_per_week) {
            $weekCount = $this->getRunCountForPeriod('week');
            if ($weekCount >= $this->max_videos_per_week) {
                return true;
            }
        }

        // ตรวจสอบ monthly limit
        if ($this->max_videos_per_month) {
            $monthCount = $this->getRunCountForPeriod('month');
            if ($monthCount >= $this->max_videos_per_month) {
                return true;
            }
        }

        return false;
    }

    /**
     * ดึงจำนวนครั้งที่รันในช่วงเวลา
     *
     * @param string $period day, week, month
     * @return int
     */
    protected function getRunCountForPeriod(string $period): int
    {
        // TODO: implement by counting related projects
        // For now, return 0
        return 0;
    }

    /**
     * ตรวจสอบว่าหยุดเพราะ error หรือไม่
     *
     * @return bool
     */
    public function isPausedDueToErrors(): bool
    {
        return $this->pause_on_error
            && $this->consecutive_failures >= $this->max_consecutive_failures;
    }

    /**
     * คำนวณเวลารันครั้งต่อไป
     *
     * @return Carbon|null
     */
    public function calculateNextRunAt(): ?Carbon
    {
        $tz = $this->timezone ?? config('app.timezone', 'Asia/Bangkok');

        return match ($this->frequency_type) {
            'once' => $this->run_at && $this->run_at->isFuture() ? $this->run_at : null,
            'daily' => $this->calculateNextDaily($tz),
            'weekly' => $this->calculateNextWeekly($tz),
            'monthly' => $this->calculateNextMonthly($tz),
            'custom' => $this->calculateNextCron($tz),
            default => null,
        };
    }

    /**
     * คำนวณเวลารันครั้งต่อไป (daily)
     *
     * @param string $tz
     * @return Carbon|null
     */
    protected function calculateNextDaily(string $tz): ?Carbon
    {
        if (!$this->daily_time) {
            return null;
        }

        $next = Carbon::parse($this->daily_time, $tz);

        if ($next->isPast()) {
            $next->addDay();
        }

        return $next->setTimezone('UTC');
    }

    /**
     * คำนวณเวลารันครั้งต่อไป (weekly)
     *
     * @param string $tz
     * @return Carbon|null
     */
    protected function calculateNextWeekly(string $tz): ?Carbon
    {
        if (empty($this->weekly_days) || !$this->weekly_time) {
            return null;
        }

        $now = Carbon::now($tz);
        $candidates = [];

        foreach ($this->weekly_days as $day) {
            $candidate = $now->copy()->startOfWeek()->addDays($day);
            $candidate->setTimeFromTimeString($this->weekly_time);

            if ($candidate->isPast()) {
                $candidate->addWeek();
            }

            $candidates[] = $candidate;
        }

        return collect($candidates)->min()->setTimezone('UTC');
    }

    /**
     * คำนวณเวลารันครั้งต่อไป (monthly)
     *
     * @param string $tz
     * @return Carbon|null
     */
    protected function calculateNextMonthly(string $tz): ?Carbon
    {
        if (empty($this->monthly_days) || !$this->monthly_time) {
            return null;
        }

        $now = Carbon::now($tz);
        $candidates = [];

        foreach ($this->monthly_days as $day) {
            $candidate = $now->copy()->startOfMonth()->addDays($day - 1);
            $candidate->setTimeFromTimeString($this->monthly_time);

            if ($candidate->isPast()) {
                $candidate->addMonth();
            }

            $candidates[] = $candidate;
        }

        return collect($candidates)->min()->setTimezone('UTC');
    }

    /**
     * คำนวณเวลารันครั้งต่อไป (cron)
     *
     * @param string $tz
     * @return Carbon|null
     */
    protected function calculateNextCron(string $tz): ?Carbon
    {
        if (!$this->cron_expression) {
            return null;
        }

        try {
            $cron = new CronExpression($this->cron_expression);
            $next = Carbon::instance($cron->getNextRunDate())->setTimezone($tz);
            return $next->setTimezone('UTC');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * อัพเดทเวลารันครั้งต่อไป
     *
     * @return void
     */
    public function updateNextRunAt(): void
    {
        $this->next_run_at = $this->calculateNextRunAt();
        $this->save();
    }

    /**
     * บันทึกผลการรัน
     *
     * @param bool $success
     * @param string|null $error
     * @return void
     */
    public function recordRun(bool $success, ?string $error = null): void
    {
        $this->last_run_at = now();
        $this->increment('run_count');

        if ($success) {
            $this->increment('success_count');
            $this->consecutive_failures = 0;
            $this->last_error = null;
        } else {
            $this->increment('fail_count');
            $this->increment('consecutive_failures');
            $this->last_error = $error;
        }

        // อัพเดทเวลารันครั้งต่อไป
        $this->next_run_at = $this->calculateNextRunAt();

        // ถ้าเป็น once และรันแล้ว ให้ปิด
        if ($this->frequency_type === 'once') {
            $this->is_active = false;
        }

        $this->save();
    }

    /**
     * เปิดใช้งาน schedule
     *
     * @return void
     */
    public function activate(): void
    {
        $this->is_active = true;
        $this->consecutive_failures = 0;
        $this->last_error = null;
        $this->updateNextRunAt();
    }

    /**
     * ปิดใช้งาน schedule
     *
     * @return void
     */
    public function deactivate(): void
    {
        $this->is_active = false;
        $this->next_run_at = null;
        $this->save();
    }

    /**
     * Reset error status
     *
     * @return void
     */
    public function resetErrors(): void
    {
        $this->consecutive_failures = 0;
        $this->last_error = null;
        $this->save();
    }
}
