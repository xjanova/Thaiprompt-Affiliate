<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Line Recruitment Topic Boundary Model
 *
 * กำหนดขอบเขตหัวข้อที่ AI สามารถคุยได้ในระบบรับสมัคร
 *
 * @property int $id
 * @property int $ai_setting_id
 * @property string $name ชื่อหัวข้อ
 * @property string|null $description คำอธิบายหัวข้อ
 * @property string $type ประเภท (allowed/blocked)
 * @property array|null $keywords คำหลักที่ใช้ตรวจจับ
 * @property string|null $response_message ข้อความตอบกลับ
 * @property int $priority ลำดับความสำคัญ
 * @property bool $is_active สถานะใช้งาน
 * @property array|null $example_phrases ตัวอย่างประโยค
 * @property int $hit_count จำนวนครั้งที่ถูกตรวจจับ
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class LineRecruitmentTopicBoundary extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * ตารางที่เชื่อมต่อ
     *
     * @var string
     */
    protected $table = 'line_recruitment_topic_boundaries';

    /**
     * ฟิลด์ที่สามารถกรอกได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'ai_setting_id',
        'name',
        'description',
        'type',
        'keywords',
        'response_message',
        'priority',
        'is_active',
        'example_phrases',
        'hit_count',
    ];

    /**
     * แปลงประเภทข้อมูล
     *
     * @var array<string, string>
     */
    protected $casts = [
        'keywords' => 'array',
        'example_phrases' => 'array',
        'priority' => 'integer',
        'is_active' => 'boolean',
        'hit_count' => 'integer',
    ];

    /**
     * ค่าเริ่มต้น
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'type' => 'allowed',
        'priority' => 0,
        'is_active' => true,
        'hit_count' => 0,
    ];

    /**
     * ความสัมพันธ์กับ LineBotAiSetting
     */
    public function aiSetting(): BelongsTo
    {
        return $this->belongsTo(LineBotAiSetting::class, 'ai_setting_id');
    }

    /**
     * Scope: เฉพาะที่ใช้งาน
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: เฉพาะหัวข้อที่อนุญาต
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAllowed($query)
    {
        return $query->where('type', 'allowed');
    }

    /**
     * Scope: เฉพาะหัวข้อที่ห้าม
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBlocked($query)
    {
        return $query->where('type', 'blocked');
    }

    /**
     * Scope: เรียงตามลำดับความสำคัญ
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    /**
     * ตรวจสอบว่าข้อความตรงกับ keywords หรือไม่
     *
     * @param  string  $message  ข้อความที่ต้องการตรวจสอบ
     */
    public function matchesMessage(string $message): bool
    {
        if (empty($this->keywords)) {
            return false;
        }

        $messageLower = mb_strtolower($message);

        foreach ($this->keywords as $keyword) {
            if (mb_strpos($messageLower, mb_strtolower($keyword)) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * เพิ่มจำนวนครั้งที่ถูกตรวจจับ
     */
    public function incrementHitCount(): void
    {
        $this->increment('hit_count');
    }

    /**
     * ตรวจสอบว่าเป็นหัวข้อที่อนุญาตหรือไม่
     */
    public function isAllowed(): bool
    {
        return $this->type === 'allowed';
    }

    /**
     * ตรวจสอบว่าเป็นหัวข้อที่ห้ามหรือไม่
     */
    public function isBlocked(): bool
    {
        return $this->type === 'blocked';
    }

    /**
     * รับข้อความตอบกลับ หรือข้อความเริ่มต้น
     */
    public function getResponseOrDefault(): string
    {
        if (! empty($this->response_message)) {
            return $this->response_message;
        }

        return 'ขออภัยค่ะ หัวข้อนี้อยู่นอกเหนือขอบเขตที่สามารถให้ข้อมูลได้ค่ะ กรุณาสอบถามเรื่องอื่นได้เลยค่ะ';
    }
}
