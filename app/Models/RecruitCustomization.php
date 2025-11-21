<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * RecruitCustomization Model
 *
 * ข้อมูลที่แม่ทีมปรับแต่งเอง สำหรับหน้า Recruit
 * แต่ละ user มีได้แค่ 1 recruit page
 *
 * @property int $id
 * @property int $user_id แม่ทีม
 * @property int|null $template_id เทมเพลตที่ใช้
 * @property string|null $custom_name ชื่อที่แสดง
 * @property string|null $custom_phone เบอร์โทรติดต่อ
 * @property string|null $custom_address ที่อยู่
 * @property string|null $custom_image รูปภาพแม่ทีม
 * @property string|null $custom_pitch คำชักชวนส่วนตัว
 * @property bool $is_active เปิดใช้งานหน้า recruit
 * @property int $total_views จำนวนการเข้าชมทั้งหมด
 * @property int $total_leads จำนวน leads ทั้งหมด
 * @property int $total_conversions จำนวนคนที่สมัครสำเร็จ
 * @property float $conversion_rate อัตราการแปลง (%)
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class RecruitCustomization extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'recruit_customizations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'template_id',
        'custom_name',
        'custom_phone',
        'custom_address',
        'custom_image',
        'custom_pitch',
        'is_active',
        'total_views',
        'total_leads',
        'total_conversions',
        'conversion_rate',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'total_views' => 'integer',
            'total_leads' => 'integer',
            'total_conversions' => 'integer',
            'conversion_rate' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * ความสัมพันธ์กับ User (แม่ทีม)
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ความสัมพันธ์กับ RecruitTemplate
     *
     * @return BelongsTo
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(RecruitTemplate::class, 'template_id');
    }

    /**
     * ความสัมพันธ์กับ LeadLock
     * ผู้มุ่งหวังทั้งหมดของแม่ทีมคนนี้
     *
     * @return HasMany
     */
    public function leadLocks(): HasMany
    {
        return $this->hasMany(LeadLock::class, 'team_leader_id', 'user_id');
    }

    /**
     * ความสัมพันธ์กับ RecruitPageVisit
     * การเข้าชมทั้งหมดของแม่ทีมคนนี้
     *
     * @return HasMany
     */
    public function visits(): HasMany
    {
        return $this->hasMany(RecruitPageVisit::class, 'team_leader_id', 'user_id');
    }

    /**
     * Scope: เฉพาะที่เปิดใช้งาน
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * เพิ่มจำนวนการเข้าชม
     *
     * @return void
     */
    public function incrementViews(): void
    {
        $this->increment('total_views');
        $this->updateConversionRate();
    }

    /**
     * เพิ่มจำนวน leads
     *
     * @return void
     */
    public function incrementLeads(): void
    {
        $this->increment('total_leads');
        $this->updateConversionRate();
    }

    /**
     * เพิ่มจำนวนการแปลง (conversion)
     *
     * @return void
     */
    public function incrementConversions(): void
    {
        $this->increment('total_conversions');
        $this->updateConversionRate();
    }

    /**
     * อัพเดทอัตราการแปลง (Conversion Rate)
     *
     * @return void
     */
    public function updateConversionRate(): void
    {
        if ($this->total_leads > 0) {
            $this->conversion_rate = ($this->total_conversions / $this->total_leads) * 100;
            $this->save();
        }
    }

    /**
     * ดึง URL หน้า Recruit
     *
     * @return string
     */
    public function getRecruitUrl(): string
    {
        $mlmMember = MlmMember::where('user_id', $this->user_id)->first();
        $memberCode = $mlmMember?->member_code ?? $this->user_id;

        return route('recruit.show', ['member_code' => $memberCode]);
    }

    /**
     * ดึงรูปภาพที่ใช้แสดง
     * ลำดับความสำคัญ: custom_image > user->profile_picture_url
     *
     * @return string
     */
    public function getDisplayImage(): string
    {
        if ($this->custom_image) {
            // ถ้ามีรูป custom
            if (filter_var($this->custom_image, FILTER_VALIDATE_URL)) {
                return $this->custom_image;
            }
            return asset('storage/' . $this->custom_image);
        }

        // ใช้รูปจาก user profile
        return $this->user->profile_picture_url;
    }

    /**
     * ดึงชื่อที่ใช้แสดง
     * ลำดับความสำคัญ: custom_name > user->name
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->custom_name ?? $this->user->name;
    }
}
