<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * RecruitTemplate Model
 *
 * เทมเพลตหน้า Recruit ที่แอดมินสร้างไว้
 * แม่ทีมจะใช้เทมเพลตนี้เป็นพื้นฐานแล้วปรับแต่งข้อมูลส่วนตัว
 *
 * @property int $id
 * @property string $title ชื่อเทมเพลต
 * @property string|null $description คำอธิบายเทมเพลต
 * @property string $welcome_message ข้อความต้อนรับ
 * @property string|null $pitch_message คำชักชวนตัวอย่าง
 * @property array|null $benefits ประโยชน์ที่ได้รับ
 * @property array|null $instructions คู่มือการสมัคร
 * @property array|null $required_steps ขั้นตอนที่บังคับ
 * @property array|null $features ฟีเจอร์พิเศษ
 * @property bool $require_line_friend บังคับเพิ่มเพื่อน LINE ก่อนสมัคร
 * @property bool $show_team_leader_info แสดงข้อมูลแม่ทีม
 * @property bool $show_statistics แสดงสถิติ
 * @property bool $is_active เปิดใช้งาน
 * @property bool $is_default เทมเพลต default
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class RecruitTemplate extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'recruit_templates';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'title',
        'description',
        'welcome_message',
        'pitch_message',
        'benefits',
        'instructions',
        'required_steps',
        'features',
        'require_line_friend',
        'show_team_leader_info',
        'show_statistics',
        'is_active',
        'is_default',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'benefits' => 'array',
            'instructions' => 'array',
            'required_steps' => 'array',
            'features' => 'array',
            'require_line_friend' => 'boolean',
            'show_team_leader_info' => 'boolean',
            'show_statistics' => 'boolean',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * ความสัมพันธ์กับ RecruitCustomization
     * เทมเพลตหนึ่งสามารถถูกใช้โดยหลาย customizations
     *
     * @return HasMany
     */
    public function customizations(): HasMany
    {
        return $this->hasMany(RecruitCustomization::class, 'template_id');
    }

    /**
     * Scope: เฉพาะเทมเพลตที่เปิดใช้งาน
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: เฉพาะเทมเพลต default
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * ดึงเทมเพลต default
     * ถ้าไม่มี จะส่งเทมเพลต active ตัวแรก
     *
     * @return RecruitTemplate|null
     */
    public static function getDefault(): ?RecruitTemplate
    {
        $default = self::where('is_default', true)
            ->where('is_active', true)
            ->first();

        if (!$default) {
            $default = self::where('is_active', true)->first();
        }

        return $default;
    }

    /**
     * ตั้งค่าเป็นเทมเพลต default
     * จะยกเลิก default ของเทมเพลตอื่นทั้งหมด
     *
     * @return bool
     */
    public function setAsDefault(): bool
    {
        // ยกเลิก default ทั้งหมด
        self::where('is_default', true)->update(['is_default' => false]);

        // ตั้งเป็น default
        $this->is_default = true;
        return $this->save();
    }
}
