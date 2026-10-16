<?php

namespace App\Services\Classroom;

use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * ข้อผิดพลาดของห้องเรียนที่ส่งถึงผู้ใช้ได้ตรงๆ (ข้อความภาษาไทย + code ให้แอปแยกกรณี)
 *
 * @example throw ClassroomException::locked();  // → 423 QUIZ_LOCKED
 */
class ClassroomException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $status = 422,
        public readonly array $data = [],
    ) {
        parent::__construct($message);
    }

    public static function courseNotFound(): self
    {
        return new self('COURSE_NOT_FOUND', 'ไม่พบคอร์สนี้ หรือคอร์สปิดอยู่', 404);
    }

    public static function lessonNotFound(): self
    {
        return new self('LESSON_NOT_FOUND', 'ไม่พบบทเรียนนี้ หรือบทเรียนปิดอยู่', 404);
    }

    public static function attemptNotFound(): self
    {
        return new self('ATTEMPT_NOT_FOUND', 'ไม่พบการสอบนี้', 404);
    }

    /**
     * ยังดูบทเรียนไม่ครบ / คอร์สยังไม่มีบทเรียน / ยังไม่มีข้อสอบ
     */
    public static function locked(string $message = 'ต้องดูบทเรียนให้ครบทุกบทก่อน จึงจะเริ่มสอบได้'): self
    {
        return new self('QUIZ_LOCKED', $message, 423);
    }

    public static function cooldown(Carbon $retryAt): self
    {
        $wait = max(1, $retryAt->getTimestamp() - now()->getTimestamp());

        return new self(
            'QUIZ_COOLDOWN',
            'สอบไม่ผ่านรอบที่แล้ว กรุณารออีก '.self::waitText($wait).' แล้วสอบใหม่ได้',
            429,
            ['retry_available_at' => $retryAt->toIso8601String()]
        );
    }

    public static function expired(): self
    {
        return new self('ATTEMPT_EXPIRED', 'หมดเวลาทำข้อสอบชุดนี้แล้ว กรุณาเริ่มสอบใหม่', 410);
    }

    public static function submitted(): self
    {
        return new self('ATTEMPT_SUBMITTED', 'ส่งคำตอบชุดนี้ไปแล้ว ดูผลสอบได้เลย', 409);
    }

    public static function busy(): self
    {
        return new self('QUIZ_BUSY', 'กำลังเตรียมข้อสอบอยู่ กรุณาลองใหม่อีกครั้ง', 409);
    }

    /**
     * "45 วินาที" / "2 นาที 5 วินาที"
     */
    public static function waitText(int $seconds): string
    {
        $m = intdiv($seconds, 60);
        $s = $seconds % 60;

        if ($m === 0) {
            return $s.' วินาที';
        }

        return $s === 0 ? $m.' นาที' : $m.' นาที '.$s.' วินาที';
    }
}
