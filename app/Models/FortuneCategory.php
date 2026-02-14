<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * FortuneCategory Model
 *
 * จัดการหมวดหมู่การทำนาย เช่น ความรัก, การเงิน, สุขภาพ, การงาน
 * แต่ละหมวดหมู่สามารถกำหนด prompt context เฉพาะได้
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $icon
 * @property string $color
 * @property string|null $description
 * @property string|null $prompt_context
 * @property string|null $example_questions
 * @property bool $is_active
 * @property int $order
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class FortuneCategory extends Model
{
    use SoftDeletes;

    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'fortune_categories';

    /**
     * คอลัมน์ที่สามารถ mass assign ได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'icon',
        'color',
        'description',
        'prompt_context',
        'example_questions',
        'is_active',
        'order',
    ];

    /**
     * การ cast ประเภทข้อมูล
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * ค่าเริ่มต้นของ attributes
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
        'order' => 0,
        'color' => '#3B82F6',
    ];

    /**
     * Boot method
     *
     * สร้าง slug อัตโนมัติจาก name
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });

        static::updating(function ($category) {
            if ($category->isDirty('name') && empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    /**
     * ดึง FortuneReading ที่เกี่ยวข้องกับหมวดหมู่นี้
     *
     * หมายเหตุ: ตาราง fortune_readings เก็บหมวดหมู่เป็น JSON array ในคอลัมน์ categories
     * จึงใช้ whereJsonContains แทน hasMany
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function readings()
    {
        return FortuneReading::whereJsonContains('categories', $this->id);
    }

    /**
     * Scope: เฉพาะหมวดหมู่ที่เปิดใช้งาน
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: เรียงตามลำดับ
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc')->orderBy('name', 'asc');
    }

    /**
     * ดึง icon ของหมวดหมู่ (หรือ default icon)
     */
    public function getIconAttribute($value): string
    {
        return $value ?? '🔮';
    }

    /**
     * ดึงตัวอย่างคำถามเป็น array
     */
    public function getExampleQuestionsArray(): array
    {
        if (empty($this->example_questions)) {
            return [];
        }

        return array_filter(explode("\n", $this->example_questions));
    }

    /**
     * ดึง prompt context สำหรับหมวดหมู่นี้
     */
    public function getPromptContextText(): string
    {
        return $this->prompt_context ?? "เกี่ยวกับ{$this->name}";
    }
}
