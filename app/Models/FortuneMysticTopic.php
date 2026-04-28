<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * หมวดหมู่คอนเทนต์สายมู (สายมู / แก้เคล็ด / ปัญหาชีวิต / สิ่งลี้ลับ / รู้หรือไม่ทั่วโลก)
 *
 * แต่ละ row เก็บ pool ของ:
 * - sub_topic_pool: ชื่อ sub-topic สุ่ม (เช่น "แก้เคล็ดเงินไหลออก")
 * - hashtag_pool: hashtag ที่จะหมุนใช้
 * - image_keywords: keyword สำหรับ AI image gen
 * - search_queries: คำค้นไป Tavily/Brave เพื่อหาแหล่งข้อมูล
 */
class FortuneMysticTopic extends Model
{
    use HasFactory;

    protected $table = 'fortune_mystic_topics';

    protected $fillable = [
        'slug',
        'name_th',
        'description_th',
        'emoji',
        'sub_topic_pool',
        'hashtag_pool',
        'image_keywords',
        'search_queries',
        'caption_prompt_template',
        'is_enabled',
        'sort_order',
        'use_count',
        'last_used_at',
    ];

    protected $casts = [
        'sub_topic_pool' => 'array',
        'hashtag_pool' => 'array',
        'image_keywords' => 'array',
        'search_queries' => 'array',
        'is_enabled' => 'boolean',
        'sort_order' => 'integer',
        'use_count' => 'integer',
        'last_used_at' => 'datetime',
    ];

    /**
     * สุ่มเลือก topic ตามกลยุทธ์ "ใช้น้อยสุดมาก่อน" (least-recently-used)
     *
     * @return self|null
     */
    public static function pickNext(): ?self
    {
        return static::where('is_enabled', true)
            ->orderBy('use_count', 'asc')
            ->orderBy('last_used_at', 'asc')
            ->orderBy('sort_order', 'asc')
            ->first();
    }

    /**
     * บันทึกว่าถูกใช้ (เพิ่ม count + เซ็ต last_used_at)
     */
    public function markUsed(): void
    {
        $this->increment('use_count');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * สุ่ม sub-topic 1 ตัวจาก pool
     */
    public function pickSubTopic(): ?string
    {
        $pool = $this->sub_topic_pool ?? [];
        if (empty($pool)) {
            return null;
        }

        return $pool[array_rand($pool)];
    }

    /**
     * สุ่ม search query 1 ตัวจาก pool — ผูกกับ sub_topic ถ้าใส่มา
     */
    public function pickSearchQuery(?string $subTopic = null): string
    {
        $pool = $this->search_queries ?? [];
        if (empty($pool)) {
            return $subTopic ?: $this->name_th;
        }

        $base = $pool[array_rand($pool)];

        // ถ้ามี sub_topic — รวมเข้าด้วย เพื่อให้ search ตรงประเด็นมากขึ้น
        return $subTopic ? "{$subTopic} {$base}" : $base;
    }

    /**
     * สุ่ม hashtags N ตัวจาก pool (ห้ามซ้ำ)
     *
     * @param  int  $count
     * @return array<string>
     */
    public function pickHashtags(int $count = 6): array
    {
        $pool = $this->hashtag_pool ?? [];
        if (empty($pool)) {
            return [];
        }

        $count = min($count, count($pool));

        // shuffle + take
        $shuffled = $pool;
        shuffle($shuffled);

        return array_slice($shuffled, 0, $count);
    }

    /**
     * สุ่ม image keywords 1-3 ตัว → ส่งให้ AI image gen
     *
     * @return string  comma-separated keywords
     */
    public function pickImagePromptSeed(): string
    {
        $pool = $this->image_keywords ?? [];
        if (empty($pool)) {
            return 'mystical thai temple';
        }

        $count = min(3, count($pool));
        $shuffled = $pool;
        shuffle($shuffled);

        return implode(', ', array_slice($shuffled, 0, $count));
    }
}
