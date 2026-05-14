<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 👤 (2026-05-14) Fortune Customer Persona — บุคลิก/นิสัยลูกค้าระยะยาว
 *
 * เก็บข้อมูลเชิงลึกของลูกค้าแต่ละ ID เพื่อให้ AI ปรับ tone การคุยให้เหมาะสม
 *
 * แยกจาก LineBotConversation (24hr session) — persona เก็บถาวร update ทุกครั้งที่คุย
 *
 * @property int    $id
 * @property string $platform              'facebook' / 'line'
 * @property string $platform_user_id      FB PSID / LINE userId
 * @property string|null $display_name
 * @property array|null  $demographics      {age_range, gender_hint, job_hint, location_hint}
 * @property array|null  $traits            personality traits array
 * @property array|null  $likes
 * @property array|null  $dislikes
 * @property array|null  $conversation_themes
 * @property array|null  $communication_style
 * @property string|null $topic_tags        comma-separated (Obsidian-style)
 * @property string|null $note_markdown
 * @property int         $observation_count
 * @property \Carbon\Carbon|null $last_observed_at
 * @property \Carbon\Carbon|null $last_persona_sync_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class FortuneCustomerPersona extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'fortune_customer_personas';

    protected $fillable = [
        'platform',
        'platform_user_id',
        'display_name',
        'demographics',
        'traits',
        'likes',
        'dislikes',
        'conversation_themes',
        'communication_style',
        'topic_tags',
        'note_markdown',
        'observation_count',
        'last_observed_at',
        'last_persona_sync_at',
    ];

    protected $casts = [
        'demographics' => 'array',
        'traits' => 'array',
        'likes' => 'array',
        'dislikes' => 'array',
        'conversation_themes' => 'array',
        'communication_style' => 'array',
        'observation_count' => 'integer',
        'last_observed_at' => 'datetime',
        'last_persona_sync_at' => 'datetime',
    ];

    /**
     * 🔍 หา persona จาก platform + user_id (ไม่สร้างใหม่)
     */
    public static function findByPlatformUser(string $platform, string $userId): ?self
    {
        return self::where('platform', $platform)
            ->where('platform_user_id', $userId)
            ->first();
    }

    /**
     * 🆕 หา หรือ สร้าง persona ใหม่ (idempotent)
     */
    public static function getOrCreate(string $platform, string $userId, ?string $displayName = null): self
    {
        return self::firstOrCreate(
            ['platform' => $platform, 'platform_user_id' => $userId],
            [
                'display_name' => $displayName,
                'observation_count' => 0,
            ]
        );
    }

    /**
     * 🔄 Merge data ใหม่ (additive — ไม่ overwrite ของเก่า)
     *
     * - JSON arrays (traits, likes, etc.) → union (unique)
     * - JSON objects (demographics, communication_style) → ผสม (ค่าใหม่ทับเฉพาะที่ระบุ)
     * - topic_tags → union comma-separated
     */
    public function mergeData(array $extracted): self
    {
        // Array fields — union (unique)
        foreach (['traits', 'likes', 'dislikes', 'conversation_themes'] as $key) {
            if (! empty($extracted[$key]) && is_array($extracted[$key])) {
                $existing = $this->{$key} ?? [];
                $merged = array_values(array_unique(array_merge($existing, $extracted[$key])));
                // Cap ไม่ให้บวมเกินไป — เก็บล่าสุด 30 รายการ
                $this->{$key} = array_slice($merged, -30);
            }
        }

        // Object fields — merge (overwrite specific keys)
        foreach (['demographics', 'communication_style'] as $key) {
            if (! empty($extracted[$key]) && is_array($extracted[$key])) {
                $existing = $this->{$key} ?? [];
                $this->{$key} = array_merge($existing, $extracted[$key]);
            }
        }

        // Topic tags — union comma-separated
        if (! empty($extracted['topic_tags'])) {
            $existingTags = $this->topic_tags
                ? array_map('trim', explode(',', $this->topic_tags))
                : [];
            $newTags = is_array($extracted['topic_tags'])
                ? $extracted['topic_tags']
                : array_map('trim', explode(',', $extracted['topic_tags']));
            $merged = array_values(array_unique(array_filter(array_merge($existingTags, $newTags))));
            $this->topic_tags = implode(', ', array_slice($merged, -50));
        }

        $this->observation_count = ($this->observation_count ?? 0) + 1;
        $this->last_observed_at = now();
        $this->last_persona_sync_at = now();

        return $this;
    }

    /**
     * 🎯 สร้าง context block สำหรับ inject ใน AI system prompt
     *
     * แสดงแค่ข้อมูลที่มี (ไม่เปลือง token)
     * ใช้เพื่อปรับ tone — **ไม่ใช่อ้างตรงๆ ในคำตอบ**
     */
    public function toAiContextBlock(): string
    {
        $lines = [];

        // Demographics
        $demo = $this->demographics ?? [];
        $demoParts = [];
        if (! empty($demo['age_range']) && $demo['age_range'] !== 'unknown') {
            $demoParts[] = "อายุ ~{$demo['age_range']}";
        }
        if (! empty($demo['gender_hint']) && $demo['gender_hint'] !== 'unknown') {
            $genderTh = match ($demo['gender_hint']) {
                'male' => 'ชาย',
                'female' => 'หญิง',
                'non_binary' => 'ไม่ระบุเพศชัด',
                default => $demo['gender_hint'],
            };
            $demoParts[] = "เพศ {$genderTh}";
        }
        if (! empty($demo['job_hint']) && $demo['job_hint'] !== 'unknown') {
            $demoParts[] = "งาน: {$demo['job_hint']}";
        }
        if (! empty($demoParts)) {
            $lines[] = '• ' . implode(' / ', $demoParts);
        }

        // Traits
        if (! empty($this->traits)) {
            $lines[] = '• บุคลิก: ' . implode(', ', array_slice($this->traits, -5));
        }

        // Likes / Dislikes (cap แต่ละด้าน 3-5 รายการ)
        if (! empty($this->likes)) {
            $lines[] = '• ชอบ: ' . implode(', ', array_slice($this->likes, -5));
        }
        if (! empty($this->dislikes)) {
            $lines[] = '• ไม่ชอบ: ' . implode(', ', array_slice($this->dislikes, -3));
        }

        // Conversation themes
        if (! empty($this->conversation_themes)) {
            $lines[] = '• เคยคุยเรื่อง: ' . implode(', ', array_slice($this->conversation_themes, -3));
        }

        // Communication style
        $style = $this->communication_style ?? [];
        $styleParts = [];
        if (! empty($style['tone'])) {
            $styleParts[] = "tone: {$style['tone']}";
        }
        if (! empty($style['formality'])) {
            $styleParts[] = "formality: {$style['formality']}";
        }
        if (! empty($style['emoji_usage'])) {
            $styleParts[] = "emoji: {$style['emoji_usage']}";
        }
        if (! empty($styleParts)) {
            $lines[] = '• สไตล์การคุย: ' . implode(' / ', $styleParts);
        }

        if (empty($lines)) {
            return ''; // ไม่มีข้อมูล → ไม่ inject block
        }

        return "[👤 CUSTOMER_PERSONA — ใช้ปรับ tone เท่านั้น ห้ามอ้างตรงๆ ในคำตอบ]\n"
            . implode("\n", $lines)
            . "\n⚠️ AI ใช้ข้อมูลนี้ \"ใต้พรม\" — ปรับน้ำเสียง/คำพูดให้เข้ากับลูกค้า แต่อย่าเอ่ยถึงว่า \"จำได้ว่า...\" ตรงๆ";
    }

    /**
     * 📝 Export เป็น Markdown (ObsidianX-ready)
     *
     * Format: YAML frontmatter + sections
     * พร้อม import เข้า ObsidianX vault ได้เลย
     */
    public function toObsidianMarkdown(): string
    {
        $name = $this->display_name ?? "Customer {$this->platform_user_id}";
        $tagsList = $this->topic_tags
            ? array_map(fn ($t) => trim($t), explode(',', $this->topic_tags))
            : [];
        $tagsYaml = empty($tagsList)
            ? '[]'
            : "\n  - " . implode("\n  - ", $tagsList);

        $md = "---\n";
        $md .= "title: \"{$name}\"\n";
        $md .= "platform: {$this->platform}\n";
        $md .= "user_id: {$this->platform_user_id}\n";
        $md .= 'observation_count: ' . $this->observation_count . "\n";
        $md .= 'last_observed: ' . ($this->last_observed_at?->toIso8601String() ?? '-') . "\n";
        $md .= "tags:{$tagsYaml}\n";
        $md .= "---\n\n";
        $md .= "# {$name}\n\n";

        $demo = $this->demographics ?? [];
        if (! empty($demo)) {
            $md .= "## 👥 Demographics\n";
            foreach ($demo as $k => $v) {
                if ($v && $v !== 'unknown') {
                    $md .= "- **{$k}**: {$v}\n";
                }
            }
            $md .= "\n";
        }

        if (! empty($this->traits)) {
            $md .= "## 🎭 Traits\n";
            foreach ($this->traits as $t) {
                $md .= "- {$t}\n";
            }
            $md .= "\n";
        }

        if (! empty($this->likes)) {
            $md .= "## ❤️ Likes\n";
            foreach ($this->likes as $t) {
                $md .= "- {$t}\n";
            }
            $md .= "\n";
        }

        if (! empty($this->dislikes)) {
            $md .= "## ❌ Dislikes\n";
            foreach ($this->dislikes as $t) {
                $md .= "- {$t}\n";
            }
            $md .= "\n";
        }

        if (! empty($this->conversation_themes)) {
            $md .= "## 💬 Conversation Themes\n";
            foreach ($this->conversation_themes as $t) {
                $md .= "- {$t}\n";
            }
            $md .= "\n";
        }

        $style = $this->communication_style ?? [];
        if (! empty($style)) {
            $md .= "## 🗣️ Communication Style\n";
            foreach ($style as $k => $v) {
                $md .= "- **{$k}**: {$v}\n";
            }
            $md .= "\n";
        }

        return $md;
    }
}
