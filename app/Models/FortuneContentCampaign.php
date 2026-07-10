<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * แคมเปญคอนเทนต์อัตโนมัติ — 1 แคมเปญ = 1 แนวคอนเทนต์ + ตารางเวลาของตัวเอง
 *
 * ทำงานอิสระจากกัน: แต่ละแคมเปญโพสคนละเวลา คนละแนว
 * แต่ใช้ pipeline เดียวกัน (FortuneAIService + WebSearch + AI image + FB Page เดิม)
 *
 * @property int $id
 * @property string $slug
 * @property string $name_th ชื่อแคมเปญ
 * @property string|null $description_th คำอธิบายแนว (AI ใช้เป็นทิศทาง)
 * @property string|null $emoji
 * @property bool $is_enabled
 * @property array|null $schedule เวลาโพส ["07:30","19:00"]
 * @property string $topic_source inline | mystic_topics
 * @property array|null $keywords คีย์เวิร์ดแนวคอนเทนต์
 * @property string|null $content_prompt custom prompt (override)
 * @property string|null $persona บุคลิกผู้เขียน
 * @property array|null $sub_topic_pool
 * @property array|null $hashtag_pool
 * @property array|null $search_queries
 * @property bool $use_web_search
 * @property int|null $caption_min
 * @property int|null $caption_max
 * @property int|null $hashtag_count
 * @property string|null $image_style_hint
 * @property int $post_count
 * @property \Carbon\Carbon|null $last_posted_at
 */
class FortuneContentCampaign extends Model
{
    protected $table = 'fortune_content_campaigns';

    public const SOURCE_INLINE = 'inline';

    public const SOURCE_MYSTIC_TOPICS = 'mystic_topics';

    protected $fillable = [
        'slug',
        'name_th',
        'description_th',
        'emoji',
        'is_enabled',
        'schedule',
        'topic_source',
        'keywords',
        'content_prompt',
        'persona',
        'sub_topic_pool',
        'hashtag_pool',
        'search_queries',
        'use_web_search',
        'caption_min',
        'caption_max',
        'hashtag_count',
        'image_style_hint',
        'post_count',
        'last_posted_at',
        'sort_order',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'schedule' => 'array',
        'keywords' => 'array',
        'sub_topic_pool' => 'array',
        'hashtag_pool' => 'array',
        'search_queries' => 'array',
        'use_web_search' => 'boolean',
        'caption_min' => 'integer',
        'caption_max' => 'integer',
        'hashtag_count' => 'integer',
        'post_count' => 'integer',
        'last_posted_at' => 'datetime',
        'sort_order' => 'integer',
    ];

    /**
     * ความสัมพันธ์: โพสทั้งหมดของแคมเปญนี้
     */
    public function posts(): HasMany
    {
        return $this->hasMany(FortuneContentPost::class, 'campaign_id');
    }

    /**
     * ⭐ Normalizer กลางของ slot เวลา — ใช้ที่เดียวทั้งระบบ (model/service/controller)
     *
     * slot_time เป็น unique key กันโพสซ้ำ — ถ้า normalize ต่างกันคนละจุด key จะไม่ match
     * แล้วโพสซ้ำได้ จึงห้ามมี regex แปลงเวลาแยกที่อื่น
     *
     * รับ "8", "08:00", "8:30", int 8 → คืน "HH:MM" หรือ null ถ้า format ผิด
     */
    public static function normalizeSlot(string|int $time): ?string
    {
        $hour = null;
        $minute = 0;

        if (is_int($time)) {
            $hour = $time;
        } elseif (preg_match('/^(\d{1,2})(?::(\d{2}))?$/', trim($time), $m)) {
            $hour = (int) $m[1];
            $minute = isset($m[2]) ? (int) $m[2] : 0;
        }

        if ($hour === null || $hour < 0 || $hour > 23 || $minute < 0 || $minute > 59) {
            return null;
        }

        return sprintf('%02d:%02d', $hour, $minute);
    }

    /**
     * อ่าน schedule เป็น slot list พร้อม normalize — [{hour, minute, key:"HH:MM"}]
     *
     * รับได้ทั้ง "8", "08:00", "8:30" (pattern เดียวกับระบบสายมูเดิม)
     *
     * @return array<array{hour:int,minute:int,key:string}>
     */
    public function scheduleSlots(): array
    {
        $raw = $this->schedule;

        // เผื่อข้อมูลเก่า/seed ที่หลุด cast มาเป็น JSON string
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($raw)) {
            return [];
        }

        $slots = [];
        foreach ($raw as $time) {
            if (! is_string($time) && ! is_int($time)) {
                continue;
            }
            $key = static::normalizeSlot($time);
            if ($key === null) {
                continue;
            }
            [$h, $mn] = explode(':', $key);
            $slots[$key] = ['hour' => (int) $h, 'minute' => (int) $mn, 'key' => $key];
        }

        ksort($slots);

        return array_values($slots);
    }

    /**
     * สุ่มหัวข้อย่อย 1 ตัวจาก pool — null = ให้ AI เลือกแง่มุมเอง
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
     * สุ่ม hashtags N ตัวจาก pool (ไม่ซ้ำ)
     *
     * @return array<string>
     */
    public function pickHashtags(int $count): array
    {
        $pool = $this->hashtag_pool ?? [];
        if (empty($pool) || $count <= 0) {
            return [];
        }

        $shuffled = $pool;
        shuffle($shuffled);

        return array_slice($shuffled, 0, min($count, count($shuffled)));
    }

    /**
     * สุ่มคำค้น web search — ผูกกับ sub_topic ถ้ามี
     */
    public function pickSearchQuery(?string $subTopic = null): string
    {
        $pool = $this->search_queries ?? [];
        if (! empty($pool)) {
            $base = $pool[array_rand($pool)];

            return $subTopic ? "{$subTopic} {$base}" : $base;
        }

        // ไม่มี pool → สร้างจาก sub_topic + keyword แรก
        $keywords = $this->keywords ?? [];
        $kw = ! empty($keywords) ? $keywords[array_rand($keywords)] : $this->name_th;

        return trim(($subTopic ? "{$subTopic} " : '').$kw);
    }

    /**
     * fallback image keywords — ใช้เมื่อ AI สร้าง image prompt จากเนื้อหาไม่สำเร็จ
     */
    public function fallbackImageSeed(): string
    {
        $keywords = $this->keywords ?? [];
        if (empty($keywords)) {
            return 'peaceful thai lifestyle scenery';
        }

        $shuffled = $keywords;
        shuffle($shuffled);

        return implode(', ', array_slice($shuffled, 0, 3));
    }

    /**
     * บันทึกสถิติหลังโพสสำเร็จ
     */
    public function markPosted(): void
    {
        $this->increment('post_count');
        $this->update(['last_posted_at' => now()]);
    }
}
