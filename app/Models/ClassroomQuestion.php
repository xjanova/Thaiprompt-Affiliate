<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ❓ ข้อสอบในคลังของคอร์ส (ตัวเลือก 2–6 ข้อ · คำตอบที่ถูก 1 ข้อ · คำอธิบายสั้น)
 *
 * correct_index ห้ามส่งออกไปกับข้อสอบ — เฉลยอยู่ฝั่งเซิร์ฟเวอร์เท่านั้น
 *
 * @property int $id
 * @property int $course_id
 * @property string $question
 * @property array<int, string> $choices
 * @property int $correct_index
 * @property string|null $explanation
 * @property bool $is_active
 */
class ClassroomQuestion extends Model
{
    public const MIN_CHOICES = 2;

    public const MAX_CHOICES = 6;

    protected $table = 'classroom_questions';

    protected $fillable = [
        'course_id',
        'question',
        'choices',
        'correct_index',
        'explanation',
        'is_active',
    ];

    protected $casts = [
        'course_id' => 'integer',
        'choices' => 'array',
        'correct_index' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $hidden = ['correct_index'];

    public function course(): BelongsTo
    {
        return $this->belongsTo(ClassroomCourse::class, 'course_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * ตัวเลือกเป็นรายการข้อความล้วน (กันข้อมูลเก่า/แก้มือที่ไม่ใช่ array)
     *
     * @return array<int, string>
     */
    public function choiceList(): array
    {
        return array_values(array_map('strval', is_array($this->choices) ? $this->choices : []));
    }

    public function correctChoice(): ?string
    {
        return $this->choiceList()[$this->correct_index] ?? null;
    }
}
