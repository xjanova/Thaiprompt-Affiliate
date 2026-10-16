<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 📝 การสอบ 1 ครั้ง — ข้อที่สุ่มได้ + ลำดับตัวเลือกที่สลับ + เฉลยฝั่งเซิร์ฟเวอร์ (answer_key) · หมดอายุ 30 นาที
 *
 * @property int $id
 * @property int $user_id
 * @property int $course_id
 * @property array<int, int> $question_ids
 * @property array<string, array<int, int>>|null $choice_order {question_id: [ลำดับตัวเลือกเดิม]}
 * @property array<string, int>|null $answer_key {question_id: ตำแหน่งที่ถูกตามลำดับที่แสดง}
 * @property array<string, int>|null $answers {question_id: ตำแหน่งที่ผู้สอบเลือก}
 * @property int|null $score
 * @property int $total
 * @property int $pass_score
 * @property bool $passed
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $submitted_at
 * @property \Illuminate\Support\Carbon|null $expires_at
 */
class ClassroomAttempt extends Model
{
    /** เวลาทำข้อสอบต่อครั้ง (นาที) */
    public const TTL_MINUTES = 30;

    protected $table = 'classroom_attempts';

    protected $fillable = [
        'user_id',
        'course_id',
        'question_ids',
        'choice_order',
        'answer_key',
        'answers',
        'score',
        'total',
        'pass_score',
        'passed',
        'started_at',
        'submitted_at',
        'expires_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'course_id' => 'integer',
        'question_ids' => 'array',
        'choice_order' => 'array',
        'answer_key' => 'array',
        'answers' => 'array',
        'score' => 'integer',
        'total' => 'integer',
        'pass_score' => 'integer',
        'passed' => 'boolean',
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /** เฉลยห้ามหลุดออกไปกับ toArray()/JSON */
    protected $hidden = ['answer_key'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(ClassroomCourse::class, 'course_id');
    }

    public function isSubmitted(): bool
    {
        return $this->submitted_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * ยังทำต่อได้ (ยังไม่ส่ง และยังไม่หมดเวลา)
     */
    public function isOpen(): bool
    {
        return ! $this->isSubmitted() && ! $this->isExpired();
    }

    /**
     * @return array<int, int>
     */
    public function questionIds(): array
    {
        return array_values(array_map('intval', is_array($this->question_ids) ? $this->question_ids : []));
    }
}
