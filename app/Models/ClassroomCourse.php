<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 🎓 คอร์สห้องเรียน — 1 คอร์สที่เปิดอยู่ต่อบทบาท (ผู้ขาย / ไรเดอร์ / ผู้ซื้อ)
 *
 * ดูวิดีโอครบทุกบท → สอบผ่าน → ได้ป้าย "ผ่านการอบรม" ของบทบาทนั้น (ไม่บังคับ ไม่ใช่ด่านเปิดร้าน/รับงาน/สั่งของ)
 *
 * @property int $id
 * @property string $role seller|rider|buyer
 * @property string $title
 * @property string|null $description
 * @property string $rules_version รุ่นกติกา เช่น "2026-10"
 * @property int $question_count จำนวนข้อที่สุ่มต่อการสอบ
 * @property int $pass_score ตอบถูกขั้นต่ำเพื่อผ่าน
 * @property int $points_reward คะแนนความประพฤติเมื่อผ่านครั้งแรก
 * @property int $retry_cooldown_seconds ต้องรอกี่วินาทีหลังสอบไม่ผ่าน
 * @property bool $is_active
 * @property int $sort_order
 */
class ClassroomCourse extends Model
{
    public const ROLE_SELLER = 'seller';

    public const ROLE_RIDER = 'rider';

    public const ROLE_BUYER = 'buyer';

    /** บทบาททั้งหมด (ลำดับที่แสดง) */
    public const ROLES = [self::ROLE_SELLER, self::ROLE_RIDER, self::ROLE_BUYER];

    /** ชื่อบทบาทภาษาไทย */
    public const ROLE_LABELS = [
        self::ROLE_SELLER => 'ผู้ขาย',
        self::ROLE_RIDER => 'ไรเดอร์',
        self::ROLE_BUYER => 'ผู้ซื้อ',
    ];

    protected $table = 'classroom_courses';

    protected $fillable = [
        'role',
        'title',
        'description',
        'rules_version',
        'question_count',
        'pass_score',
        'points_reward',
        'retry_cooldown_seconds',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'question_count' => 'integer',
        'pass_score' => 'integer',
        'points_reward' => 'integer',
        'retry_cooldown_seconds' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function lessons(): HasMany
    {
        return $this->hasMany(ClassroomLesson::class, 'course_id');
    }

    /**
     * บทเรียนที่เปิดอยู่ เรียงตามลำดับที่แอดมินจัด
     */
    public function activeLessons(): HasMany
    {
        return $this->hasMany(ClassroomLesson::class, 'course_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ClassroomQuestion::class, 'course_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ClassroomAttempt::class, 'course_id');
    }

    public function certifications(): HasMany
    {
        return $this->hasMany(ClassroomCertification::class, 'course_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * คอร์สที่เปิดอยู่ของบทบาทนี้ (มีได้ทีละ 1 — ถ้ามีหลายตัวใช้ตัวแรกตามลำดับ)
     */
    public static function activeForRole(string $role): ?self
    {
        return static::query()->active()->where('role', $role)->ordered()->first();
    }

    public function roleLabel(): string
    {
        return self::ROLE_LABELS[$this->role] ?? $this->role;
    }

    public static function isValidRole(?string $role): bool
    {
        return in_array($role, self::ROLES, true);
    }
}
