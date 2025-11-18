<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LineSignupTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_key',
        'template_name',
        'description',
        'flex_message_json',
        'variables',
        'is_active',
        'is_default',
        'original_template_key',
        'category',
        'usage_count',
    ];

    protected $casts = [
        'flex_message_json' => 'array',
        'variables' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Replace variables in template
     *
     * แทนที่ตัวแปรใน template ด้วยข้อมูลจริง
     *
     * @param array $data ข้อมูลสำหรับแทนที่ตัวแปร
     * @return array Flex Message JSON ที่แทนค่าแล้ว
     */
    public function render(array $data = []): array
    {
        $json = json_encode($this->flex_message_json);

        foreach ($data as $key => $value) {
            $json = str_replace("{{" . $key . "}}", $value, $json);
        }

        return json_decode($json, true);
    }

    /**
     * ตรวจสอบว่าสามารถ reset ได้หรือไม่
     *
     * Template สามารถ reset ได้เมื่อเป็น default template
     *
     * @return bool
     */
    public function canReset(): bool
    {
        return $this->is_default === true;
    }

    /**
     * ดึง default template data จาก seeder
     *
     * @return array|null
     */
    public function getDefaultData(): ?array
    {
        if (!$this->is_default) {
            return null;
        }

        // ดึงข้อมูลจาก seeder class
        $seeder = new \Database\Seeders\LineSignupTemplateSeeder();
        $reflection = new \ReflectionClass($seeder);

        // เรียก method ตาม template_key
        $methodName = 'get' . str_replace('_', '', ucwords($this->template_key, '_')) . 'Template';

        if (!$reflection->hasMethod($methodName)) {
            return null;
        }

        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return [
            'flex_message_json' => $method->invoke($seeder),
            'variables' => $this->getDefaultVariables(),
            'description' => $this->getDefaultDescription(),
        ];
    }

    /**
     * ดึง default variables สำหรับแต่ละ template
     *
     * @return array
     */
    protected function getDefaultVariables(): array
    {
        return match ($this->template_key) {
            'welcome_hero' => ['user_name'],
            'earning_calculator' => ['referrals', 'monthly_earning', 'yearly_earning'],
            'success_story' => ['member_name', 'before_income', 'after_income', 'duration', 'quote'],
            'training_course' => ['course_count', 'total_hours'],
            'quick_start_guide' => ['member_code', 'referral_code'],
            default => [],
        };
    }

    /**
     * ดึง default description สำหรับแต่ละ template
     *
     * @return string
     */
    protected function getDefaultDescription(): string
    {
        return match ($this->template_key) {
            'welcome_hero' => 'Welcome message สำหรับผู้ใช้ใหม่ที่ติดตาม LINE OA',
            'earning_calculator' => 'แสดงการคำนวณรายได้ที่เป็นไปได้',
            'success_story' => 'เรื่องราวความสำเร็จของสมาชิก',
            'training_course' => 'โปรโมทคอร์สเรียนฟรี',
            'quick_start_guide' => 'คู่มือเริ่มต้นสำหรับสมาชิกใหม่',
            default => '',
        };
    }

    /**
     * Scope: เฉพาะ default templates
     */
    public function scopeDefaults($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope: เฉพาะ active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: กรองตาม category
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
