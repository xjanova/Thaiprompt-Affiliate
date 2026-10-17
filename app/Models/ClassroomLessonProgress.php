<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ⏱️ ความคืบหน้าการดูบทเรียนของผู้ใช้ 1 คน (1 แถวต่อ ผู้ใช้+บทเรียน)
 *
 * watched_seconds นับจาก heartbeat ฝั่งเซิร์ฟเวอร์เท่านั้น (ดู ClassroomProgressService) — กดข้ามไปท้ายคลิปไม่ได้เวลาเพิ่ม
 *
 * @property int $id
 * @property int $user_id
 * @property int $lesson_id
 * @property int $watched_seconds
 * @property int $last_position_seconds
 * @property \Illuminate\Support\Carbon|null $last_heartbeat_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 */
class ClassroomLessonProgress extends Model
{
    protected $table = 'classroom_lesson_progress';

    protected $fillable = [
        'user_id',
        'lesson_id',
        'watched_seconds',
        'last_position_seconds',
        'last_heartbeat_at',
        'completed_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'lesson_id' => 'integer',
        'watched_seconds' => 'integer',
        'last_position_seconds' => 'integer',
        'last_heartbeat_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(ClassroomLesson::class, 'lesson_id');
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }
}
