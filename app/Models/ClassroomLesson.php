<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 🎬 บทเรียน (วิดีโอ YouTube แบบ unlisted) ในคอร์สห้องเรียน
 *
 * @property int $id
 * @property int $course_id
 * @property string $title
 * @property string $youtube_id รหัสวิดีโอ 11 ตัว
 * @property int $duration_seconds ความยาวที่แอดมินกรอก (ใช้คิดเกณฑ์ดูครบ 90%)
 * @property int $sort_order
 * @property bool $is_active
 */
class ClassroomLesson extends Model
{
    /** ดูครบกี่ส่วนของความยาว ถึงนับว่าเรียนบทนี้จบ */
    public const COMPLETE_RATIO = 0.9;

    protected $table = 'classroom_lessons';

    protected $fillable = [
        'course_id',
        'title',
        'youtube_id',
        'duration_seconds',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'course_id' => 'integer',
        'duration_seconds' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(ClassroomCourse::class, 'course_id');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(ClassroomLessonProgress::class, 'lesson_id');
    }

    /**
     * เวลาดูขั้นต่ำ (วินาที) ที่ต้องสะสมให้ครบจึงนับว่าจบบท
     */
    public function requiredSeconds(): int
    {
        return max(1, (int) ceil(max(0, $this->duration_seconds) * self::COMPLETE_RATIO));
    }

    /**
     * ความยาวแบบ mm:ss (หรือ h:mm:ss) สำหรับแสดงผล
     */
    public function durationLabel(): string
    {
        return self::formatDuration($this->duration_seconds);
    }

    public static function formatDuration(int $seconds): string
    {
        $seconds = max(0, $seconds);
        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        $s = $seconds % 60;

        return $h > 0 ? sprintf('%d:%02d:%02d', $h, $m, $s) : sprintf('%d:%02d', $m, $s);
    }

    /**
     * แปลง "mm:ss" / "h:mm:ss" / "ss" เป็นวินาที — รูปแบบผิดคืน null
     */
    public static function parseDuration(?string $text): ?int
    {
        $text = trim((string) $text);
        if ($text === '' || ! preg_match('/^\d{1,4}(:\d{1,2}){0,2}$/', $text)) {
            return null;
        }

        $parts = array_map('intval', explode(':', $text));
        foreach (array_slice($parts, 1) as $part) {
            if ($part > 59) {
                return null;
            }
        }

        $seconds = 0;
        foreach ($parts as $part) {
            $seconds = $seconds * 60 + $part;
        }

        return $seconds;
    }

    public function thumbnailUrl(): string
    {
        return 'https://i.ytimg.com/vi/'.$this->youtube_id.'/hqdefault.jpg';
    }
}
