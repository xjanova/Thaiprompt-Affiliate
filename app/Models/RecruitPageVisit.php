<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * RecruitPageVisit Model
 *
 * สถิติการเข้าชมหน้า Recruit แต่ละครั้ง
 * ใช้สำหรับวิเคราะห์การตลาด (Marketing Analytics)
 *
 * @property int $id
 * @property int $team_leader_id แม่ทีมเจ้าของหน้า
 * @property string $visitor_identifier IP + Browser fingerprint hash
 * @property string|null $ip_address IP Address
 * @property string|null $user_agent Browser User Agent
 * @property string|null $device_type อุปกรณ์
 * @property string|null $browser Browser name
 * @property string|null $os Operating system
 * @property string|null $country Country code
 * @property string|null $city City name
 * @property string|null $source_url URL ที่เข้ามา
 * @property string|null $referrer_url URL ที่อ้างอิง
 * @property array|null $utm_params UTM Parameters
 * @property \Carbon\Carbon $visited_at เวลาที่เข้าชม
 * @property int|null $time_spent เวลาที่อยู่บนหน้า (วินาที)
 * @property bool $scrolled_to_bottom เลื่อนดูจนถึงท้ายหน้า
 * @property bool $clicked_register_button กดปุ่มสมัคร
 * @property string|null $session_id Session ID
 * @property bool $is_unique_visit เป็น visit ใหม่หรือไม่
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class RecruitPageVisit extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'recruit_page_visits';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'team_leader_id',
        'visitor_identifier',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'os',
        'country',
        'city',
        'source_url',
        'referrer_url',
        'utm_params',
        'visited_at',
        'time_spent',
        'scrolled_to_bottom',
        'clicked_register_button',
        'session_id',
        'is_unique_visit',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'visited_at' => 'datetime',
            'scrolled_to_bottom' => 'boolean',
            'clicked_register_button' => 'boolean',
            'is_unique_visit' => 'boolean',
            'utm_params' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * ความสัมพันธ์กับ User (แม่ทีม)
     *
     * @return BelongsTo
     */
    public function teamLeader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'team_leader_id');
    }

    /**
     * บันทึกการเข้าชม
     *
     * @param int $teamLeaderId
     * @param string $visitorIdentifier
     * @param array $data
     * @return RecruitPageVisit
     */
    public static function recordVisit(
        int $teamLeaderId,
        string $visitorIdentifier,
        array $data = []
    ): RecruitPageVisit {
        // ตรวจสอบว่าเคยเข้าชมหรือยัง (ภายใน 24 ชม.)
        $recentVisit = self::where('visitor_identifier', $visitorIdentifier)
            ->where('team_leader_id', $teamLeaderId)
            ->where('visited_at', '>', now()->subDay())
            ->exists();

        $isUniqueVisit = !$recentVisit;

        // สร้างบันทึกการเข้าชม
        $visit = self::create(array_merge([
            'team_leader_id' => $teamLeaderId,
            'visitor_identifier' => $visitorIdentifier,
            'visited_at' => now(),
            'is_unique_visit' => $isUniqueVisit,
        ], $data));

        // อัพเดทสถิติของ customization (เฉพาะ unique visit)
        if ($isUniqueVisit) {
            $customization = RecruitCustomization::where('user_id', $teamLeaderId)->first();
            if ($customization) {
                $customization->incrementViews();
            }
        }

        return $visit;
    }

    /**
     * Scope: เฉพาะ unique visits
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnique($query)
    {
        return $query->where('is_unique_visit', true);
    }

    /**
     * Scope: เฉพาะที่กดปุ่มสมัคร
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeClickedRegister($query)
    {
        return $query->where('clicked_register_button', true);
    }

    /**
     * Scope: เฉพาะที่เลื่อนดูจนถึงท้ายหน้า
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeScrolledToBottom($query)
    {
        return $query->where('scrolled_to_bottom', true);
    }

    /**
     * Scope: ภายในช่วงเวลา
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Carbon\Carbon $from
     * @param \Carbon\Carbon $to
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBetween($query, $from, $to)
    {
        return $query->whereBetween('visited_at', [$from, $to]);
    }
}
