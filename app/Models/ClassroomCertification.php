<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 🏅 ใบรับรอง "ผ่านการอบรม" — 1 แถวต่อ ผู้ใช้+บทบาท (unique)
 *
 * rules_version ไม่ตรงกับคอร์สปัจจุบันของบทบาท = ต้องอบรมทบทวน (needs_refresh) — ป้ายยังอยู่ คะแนนไม่หาย
 *
 * @property int $id
 * @property int $user_id
 * @property string $role
 * @property int|null $course_id
 * @property string $rules_version
 * @property int|null $attempt_id
 * @property int $points_awarded
 * @property \Illuminate\Support\Carbon|null $certified_at
 */
class ClassroomCertification extends Model
{
    protected $table = 'classroom_certifications';

    protected $fillable = [
        'user_id',
        'role',
        'course_id',
        'rules_version',
        'attempt_id',
        'points_awarded',
        'certified_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'course_id' => 'integer',
        'attempt_id' => 'integer',
        'points_awarded' => 'integer',
        'certified_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(ClassroomCourse::class, 'course_id');
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ClassroomAttempt::class, 'attempt_id');
    }
}
