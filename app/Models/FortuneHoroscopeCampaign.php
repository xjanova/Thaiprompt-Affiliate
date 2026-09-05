<?php

namespace App\Models;

use App\Services\FortuneChartService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * FortuneHoroscopeCampaign Model
 *
 * แคมเปญโพสดวงรายวันอัตโนมัติ
 * ตั้งค่า AI สร้างคำทำนาย 8 วันเกิด (7 วัน + พุธกลางคืน) + ภาพ → โพสลง FB/LINE ตามเวลา
 *
 * @property int $id
 * @property string $name ชื่อแคมเปญ
 * @property string|null $description คำอธิบาย
 * @property bool $post_to_facebook โพสลง Facebook
 * @property bool $post_to_line โพสลง LINE
 * @property bool $use_fortune_settings_tokens ใช้ token จาก FortuneTellingSetting
 * @property string|null $facebook_page_id
 * @property string|null $facebook_page_token (encrypted)
 * @property string|null $line_channel_access_token (encrypted)
 * @property string $ai_text_provider
 * @property string|null $text_prompt_template
 * @property string $ai_image_provider
 * @property string $ai_image_model
 * @property string $image_size
 * @property string|null $image_style
 * @property string|null $image_prompt_template
 * @property string $schedule_time เวลาโพส HH:mm
 * @property string $timezone
 * @property array|null $active_days วันที่โพส [0-6]
 * @property array|null $target_birth_days วันเกิดที่สร้าง [0-6]
 * @property bool $include_image สร้างรูปด้วย
 * @property bool $include_lucky_info แทรกสีมงคล/เลขมงคล
 * @property string $post_format single/combined
 * @property string|null $post_header_template
 * @property string|null $post_footer_template
 * @property string $status draft/active/paused/cancelled
 * @property Carbon|null $last_generated_at
 * @property Carbon|null $last_posted_at
 * @property string|null $last_error
 * @property int|null $created_by
 * @property bool $enable_auto_hashtags เปิด/ปิด AI สร้าง hashtags อัตโนมัติ
 * @property string|null $custom_hashtags hashtags กำหนดเอง
 * @property bool $enable_cta เปิด/ปิด Call-to-Action
 * @property string|null $cta_text ข้อความ CTA
 * @property bool $enable_engagement_hooks เปิด/ปิดข้อความ engagement
 * @property string|null $page_name ชื่อเพจ/แบรนด์
 * @property string|null $page_mention @mention เพจ
 * @property int $line_monthly_quota โควต้า LINE broadcast ต่อเดือน
 * @property int $line_used_this_month จำนวน broadcast ที่ใช้ไปเดือนนี้
 * @property int $line_quota_warning_threshold เตือนเมื่อเหลือน้อยกว่า
 * @property Carbon|null $line_quota_reset_at วันที่ reset โควต้า
 */
class FortuneHoroscopeCampaign extends Model
{
    use SoftDeletes;

    protected $table = 'fortune_horoscope_campaigns';

    // สถานะ
    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * ชื่อวันเกิดภาษาไทย — index 0=อาทิตย์ … 6=เสาร์
     *
     * 🌙 (2026-09-05) index 7 = "พุธกลางคืน" (ดาวเจ้าเรือน = ราหู) วันเกิดที่ 8 ตามตำราไทย
     *    ⚠️ ตัวนี้เป็น "ดัชนีวันเกิด" ไม่ใช่ "วันในสัปดาห์" — ห้ามเอาไป index ด้วย
     *    Carbon::dayOfWeek ของวันที่โพส (ซึ่งมีแค่ 0–6)
     */
    public const THAI_DAYS = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์', 'พุธกลางคืน'];

    protected $fillable = [
        'name',
        'description',
        'post_to_facebook',
        'post_to_line',
        'use_fortune_settings_tokens',
        'facebook_page_id',
        'facebook_page_token',
        'line_channel_access_token',
        'ai_text_provider',
        'text_prompt_template',
        'ai_image_provider',
        'ai_image_model',
        'image_size',
        'image_style',
        'image_prompt_template',
        'schedule_time',
        'timezone',
        'active_days',
        'target_birth_days',
        'include_image',
        'include_lucky_info',
        'post_format',
        'post_header_template',
        'post_footer_template',
        'enable_auto_hashtags',
        'custom_hashtags',
        'enable_cta',
        'cta_text',
        'enable_engagement_hooks',
        'page_name',
        'page_mention',
        'line_monthly_quota',
        'line_used_this_month',
        'line_quota_warning_threshold',
        'line_quota_reset_at',
        'status',
        'last_generated_at',
        'last_posted_at',
        'last_error',
        'created_by',
    ];

    protected $casts = [
        'post_to_facebook' => 'boolean',
        'post_to_line' => 'boolean',
        'use_fortune_settings_tokens' => 'boolean',
        'facebook_page_token' => 'encrypted',
        'line_channel_access_token' => 'encrypted',
        'active_days' => 'array',
        'target_birth_days' => 'array',
        'include_image' => 'boolean',
        'include_lucky_info' => 'boolean',
        'enable_auto_hashtags' => 'boolean',
        'enable_cta' => 'boolean',
        'enable_engagement_hooks' => 'boolean',
        'line_monthly_quota' => 'integer',
        'line_used_this_month' => 'integer',
        'line_quota_warning_threshold' => 'integer',
        'last_generated_at' => 'datetime',
        'last_posted_at' => 'datetime',
        'line_quota_reset_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'draft',
        'ai_text_provider' => 'auto',
        'ai_image_provider' => 'pollinations',
        'ai_image_model' => 'flux',
        'image_size' => '1024x1024',
        'schedule_time' => '06:00',
        'timezone' => 'Asia/Bangkok',
        'post_to_facebook' => false,
        'post_to_line' => false,
        'use_fortune_settings_tokens' => true,
        'include_image' => true,
        'include_lucky_info' => true,
        'post_format' => 'combined',
        'enable_auto_hashtags' => true,
        'enable_cta' => true,
        'enable_engagement_hooks' => true,
        'line_monthly_quota' => 500,
        'line_used_this_month' => 0,
        'line_quota_warning_threshold' => 50,
    ];

    // ============================================================
    // Relationships
    // ============================================================

    /**
     * เนื้อหาที่ AI สร้าง
     */
    public function contents(): HasMany
    {
        return $this->hasMany(FortuneHoroscopeContent::class, 'campaign_id');
    }

    /**
     * ประวัติการโพส
     */
    public function posts(): HasMany
    {
        return $this->hasMany(FortuneHoroscopePost::class, 'campaign_id');
    }

    /**
     * แอดมินที่สร้าง
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ============================================================
    // Scopes
    // ============================================================

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * แคมเปญที่พร้อมสร้างเนื้อหา (active + ถึงเวลา + วันนี้เป็นวันที่กำหนด)
     */
    public function scopeReadyToGenerate($query)
    {
        // 🩹 (2026-09-01) scope ถูกเรียกแบบ static — `$this->timezone` ที่นี่ได้ค่า default
        //   จาก $attributes เสมอ ไม่ใช่ timezone ของแถวแคมเปญ (บังเอิญถูกเพราะทุกแคมเปญ
        //   เป็น Bangkok) — อ่าน config ให้ตรงความจริง ส่วน per-row timezone เทียบไม่ได้ใน SQL เดียว
        return $query->where('status', self::STATUS_ACTIVE)
            ->where(function ($q) {
                // ยังไม่เคยสร้างวันนี้
                $q->whereNull('last_generated_at')
                    ->orWhereDate('last_generated_at', '<', now(config('app.timezone', 'Asia/Bangkok'))->toDateString());
            });
    }

    // ============================================================
    // Methods
    // ============================================================

    /**
     * วันนี้เป็นวันที่กำหนดให้โพสหรือไม่
     */
    public function isActiveDayToday(): bool
    {
        $activeDays = $this->active_days;

        // null = ทุกวัน
        if ($activeDays === null || empty($activeDays)) {
            return true;
        }

        $todayDow = now($this->timezone)->dayOfWeek;

        return in_array($todayDow, $activeDays);
    }

    /**
     * ดึง Carbon instance ของเวลาโพสวันนี้
     */
    public function getScheduleTimeCarbon(): Carbon
    {
        $tz = $this->timezone ?? 'Asia/Bangkok';
        [$hour, $minute] = explode(':', $this->schedule_time ?? '06:00');

        return now($tz)->setTime((int) $hour, (int) $minute, 0);
    }

    /**
     * ดึงวันเกิดที่ต้องสร้างเนื้อหา (0-6)
     */
    public function getTargetBirthDays(): array
    {
        $days = $this->target_birth_days;

        // null = ทุกวันเกิด (0-6 + 7=พุธกลางคืน)
        if ($days === null || empty($days)) {
            return [0, 1, 2, 3, 4, 5, 6, FortuneChartService::WEDNESDAY_NIGHT];
        }

        // 🔢 ค่าใน DB เก็บเป็น **สตริง** (`["0","1",...]`) — ต้องแปลงเป็น int ก่อนส่งออก
        //    ไม่งั้นการเทียบแบบ strict (===) ที่ปลายทางพลาดเงียบ ๆ ทุกจุด
        $days = array_values(array_unique(array_map('intval', (array) $days)));

        // 🌙 (2026-09-05) พุธกลางวัน/พุธกลางคืนคือ "สองครึ่งของวันเดียวกัน" ตามตำราไทย
        //    แถวที่บันทึกไว้ก่อนมีวันเกิดที่ 8 ย่อมมีแค่ 0-6 เสมอ — ถ้าไม่เติมให้
        //    ฟีเจอร์นี้จะ "สร้างครบแต่ไม่เคยถูกใช้" บน prod ([[rule_feature_built_but_never_wired]])
        //    เงื่อนไข: เลือกวันพุธไว้ = ต้องได้พุธกลางคืนด้วย (ลงครึ่งเดียวคือบั๊กที่กำลังแก้อยู่)
        if (in_array(3, $days, true) && ! in_array(FortuneChartService::WEDNESDAY_NIGHT, $days, true)) {
            $days[] = FortuneChartService::WEDNESDAY_NIGHT;
        }

        return $days;
    }

    /**
     * ดึง platforms ที่เปิดใช้
     */
    public function getPlatforms(): array
    {
        $platforms = [];
        if ($this->post_to_facebook) {
            $platforms[] = 'facebook';
        }
        if ($this->post_to_line) {
            $platforms[] = 'line';
        }

        return $platforms;
    }

    /**
     * ดึง Facebook Page Token (จาก campaign หรือ FortuneTellingSetting)
     */
    public function getFacebookPageToken(): ?string
    {
        if ($this->use_fortune_settings_tokens) {
            $settings = FortuneTellingSetting::getSettings();

            return $settings->facebook_page_token ?? null;
        }

        return $this->facebook_page_token;
    }

    /**
     * ดึง Facebook Page ID (จาก campaign หรือ FortuneTellingSetting)
     */
    public function getFacebookPageId(): ?string
    {
        if ($this->use_fortune_settings_tokens) {
            $settings = FortuneTellingSetting::getSettings();

            return $settings->facebook_page_id ?? null;
        }

        return $this->facebook_page_id;
    }

    /**
     * ดึง LINE Channel Access Token (จาก campaign หรือ FortuneTellingSetting)
     */
    public function getLineChannelAccessToken(): ?string
    {
        if ($this->use_fortune_settings_tokens) {
            $settings = FortuneTellingSetting::getSettings();

            return $settings->line_channel_access_token ?? null;
        }

        return $this->line_channel_access_token;
    }

    /**
     * เปิดใช้งาน
     */
    public function activate(): self
    {
        $this->update(['status' => self::STATUS_ACTIVE]);

        return $this->fresh();
    }

    /**
     * หยุดชั่วคราว
     */
    public function pause(): self
    {
        $this->update(['status' => self::STATUS_PAUSED]);

        return $this->fresh();
    }

    /**
     * ยกเลิก
     */
    public function cancel(): self
    {
        $this->update(['status' => self::STATUS_CANCELLED]);

        return $this->fresh();
    }

    /**
     * บันทึก error
     */
    public function markError(string $error): self
    {
        $this->update(['last_error' => $error]);

        return $this->fresh();
    }

    // ============================================================
    // LINE Quota Management
    // ============================================================

    /**
     * เช็คว่า LINE โควต้ายังเหลือหรือไม่
     */
    public function hasLineQuotaRemaining(): bool
    {
        $this->resetLineQuotaIfNeeded();

        return $this->line_used_this_month < $this->line_monthly_quota;
    }

    /**
     * คำนวณโควต้า LINE ที่เหลือ
     */
    public function getLineQuotaRemainingAttribute(): int
    {
        return max(0, $this->line_monthly_quota - $this->line_used_this_month);
    }

    /**
     * เช็คว่าโควต้า LINE ใกล้หมดหรือยัง
     */
    public function isLineQuotaLow(): bool
    {
        return $this->line_quota_remaining <= $this->line_quota_warning_threshold;
    }

    /**
     * ใช้โควต้า LINE 1 ครั้ง
     */
    public function incrementLineUsage(): self
    {
        $this->increment('line_used_this_month');

        return $this->fresh();
    }

    /**
     * รีเซ็ตโควต้า LINE ถ้าขึ้นเดือนใหม่
     */
    public function resetLineQuotaIfNeeded(): void
    {
        $resetAt = $this->line_quota_reset_at;

        // ถ้ายังไม่เคย reset หรือ reset เดือนก่อน → reset
        if (! $resetAt || $resetAt->month !== now()->month || $resetAt->year !== now()->year) {
            $this->update([
                'line_used_this_month' => 0,
                'line_quota_reset_at' => now(),
            ]);
        }
    }

    /**
     * ดึง platforms ที่เปิดใช้ (เช็ค LINE quota ด้วย)
     */
    public function getActivePlatforms(): array
    {
        $platforms = [];

        if ($this->post_to_facebook) {
            $platforms[] = 'facebook';
        }

        if ($this->post_to_line) {
            // เช็ค LINE quota ก่อนอนุญาต
            if ($this->hasLineQuotaRemaining()) {
                $platforms[] = 'line';
            } else {
                \Illuminate\Support\Facades\Log::warning('FortuneHoroscope: LINE โควต้าหมด', [
                    'campaign_id' => $this->id,
                    'used' => $this->line_used_this_month,
                    'quota' => $this->line_monthly_quota,
                ]);
            }
        }

        return $platforms;
    }

    // ============================================================
    // Smart Marketing Helpers
    // ============================================================

    /**
     * สร้าง Hashtags อัจฉริยะ ตามวัน/ดวง/เทรนด์
     */
    public function generateSmartHashtags(Carbon $targetDate, ?int $birthDay = null): string
    {
        $hashtags = [];

        // === 1. Custom hashtags ที่ผู้ใช้กำหนดเอง (ใส่ทุกครั้ง) ===
        if (! empty($this->custom_hashtags)) {
            $customTags = array_filter(array_map('trim', preg_split('/[\s,]+/', $this->custom_hashtags)));
            foreach ($customTags as $tag) {
                // เติม # ถ้ายังไม่มี
                $hashtags[] = str_starts_with($tag, '#') ? $tag : "#{$tag}";
            }
        }

        // === 2. Auto hashtags จาก AI-based logic ===
        if ($this->enable_auto_hashtags) {
            // วันในสัปดาห์
            $thaiDayNames = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];
            $todayDow = $targetDate->dayOfWeek;
            $todayThaiName = $thaiDayNames[$todayDow];

            // Core hashtags (ใส่ทุกโพส)
            $hashtags[] = '#ดวงรายวัน';
            $hashtags[] = '#ดูดวง';
            $hashtags[] = '#โหราศาสตร์ไทย';
            $hashtags[] = '#หมอดู';

            // วันเกิดถ้าระบุ
            if ($birthDay !== null) {
                $birthDayName = self::THAI_DAYS[$birthDay] ?? '';
                if ($birthDayName) {
                    $hashtags[] = "#คนเกิดวัน{$birthDayName}";
                    $hashtags[] = "#ดวงวัน{$birthDayName}";
                }
            }

            // วันในสัปดาห์ที่โพส
            $hashtags[] = "#วัน{$todayThaiName}";

            // เดือนไทย
            $thaiMonths = [
                1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม',
                4 => 'เมษายน', 5 => 'พฤษภาคม', 6 => 'มิถุนายน',
                7 => 'กรกฎาคม', 8 => 'สิงหาคม', 9 => 'กันยายน',
                10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม',
            ];
            $monthName = $thaiMonths[$targetDate->month] ?? '';
            if ($monthName) {
                $hashtags[] = "#ดวง{$monthName}";
            }

            // พ.ศ.
            $thaiYear = $targetDate->year + 543;
            $hashtags[] = "#ดวง{$thaiYear}";

            // Engagement hashtags (สร้าง engagement)
            $engagementTags = [
                '#เจ้าชนะ', '#ดวงดี', '#ดวงปัง', '#โชคลาภ',
                '#การเงิน', '#ความรัก', '#การงาน',
                '#เลขมงคล', '#สีมงคล', '#ทิศมงคล',
                '#ดูดวงฟรี', '#ดูดวงวันนี้',
            ];

            // สุ่ม 3-5 engagement tags เพื่อให้หลากหลาย
            $shuffled = collect($engagementTags)->shuffle();
            $numTags = min(4, $shuffled->count());
            foreach ($shuffled->take($numTags) as $tag) {
                $hashtags[] = $tag;
            }

            // Trending day-specific tags
            $daySpecificTags = $this->getDaySpecificTags($todayDow);
            foreach ($daySpecificTags as $tag) {
                $hashtags[] = $tag;
            }

            // Page/Brand tag
            if (! empty($this->page_name)) {
                $cleanName = str_replace(' ', '', $this->page_name);
                $hashtags[] = "#{$cleanName}";
            }
        }

        // ลบ duplicates, จำกัด 30 tags (FB limit)
        $unique = array_unique($hashtags);

        return implode(' ', array_slice($unique, 0, 30));
    }

    /**
     * สร้าง Engagement Hook (ข้อความกระตุ้น)
     */
    public function generateEngagementHook(int $birthDay): string
    {
        if (! $this->enable_engagement_hooks) {
            return '';
        }

        $dayName = self::THAI_DAYS[$birthDay] ?? '';

        // 🚫 (2026-09-05) เจ้าของสั่ง "ถอด" — ชุดเดิมทั้ง 7 ประโยคเป็น engagement bait ล้วน
        //    (แท็กเพื่อน / กดไลค์ / กดแชร์ / คอมเมนต์บอก) ซึ่ง Meta ลดการมองเห็น **ทั้งเพจ**
        //    ไม่ใช่แค่โพสนั้น — เพจคือต้นทางของลูกค้าทุกรายในระบบ
        //
        // ⚠️ ไม่ทำให้เมธอดคืนค่าว่างถาวร เพราะสวิตช์ `enable_engagement_hooks` ยังอยู่ในหน้าแอดมิน
        //    ถ้าปล่อยให้เป็นสวิตช์ที่เปิดแล้วไม่มีอะไรเกิดขึ้น = กับดักของคนที่มาแก้ทีหลัง
        //    ⇒ เปลี่ยนเป็น "ประโยคปิดท้ายที่ไม่ขออะไรจากคนอ่าน" แทน เปิดสวิตช์แล้วปลอดภัย
        //    (ต่อให้มีคนเผลอเขียน bait กลับมา FacebookContentPolicy::clean() กวาดให้อีกชั้น)
        $hooks = [
            "ใครเกิดวัน{$dayName} ลองอ่านทวนอีกรอบ แล้ววางแผนวันนี้ตามนั้น",
            "ดวงใบนี้เป็นของคนเกิดวัน{$dayName} ทุกคน ส่วนของเจ้าชะตาเองยังมีรายละเอียดมากกว่านี้",
            "คนเกิดวัน{$dayName} จำจังหวะดีของวันนี้ไว้ให้แม่น แล้วใช้ให้เต็มที่",
            "ดาวเปลี่ยนทุกวัน คนเกิดวัน{$dayName} แวะมาอ่านใหม่พรุ่งนี้ได้",
            'อ่านแล้วตรงไม่ตรง อยู่ที่พื้นดวงเดิมของแต่ละคนด้วย',
            "คนเกิดวัน{$dayName} วันนี้เน้นเรื่องที่หมอบอกไว้ข้างบนก่อน อย่างอื่นค่อยว่ากัน",
            'ดวงชี้ทางได้ แต่คนเดินคือเจ้าชะตาเอง',
        ];

        // สุ่ม hook ต่างกันตามวัน
        $index = (now()->dayOfYear + $birthDay) % count($hooks);

        return $hooks[$index];
    }

    /**
     * สร้าง CTA (Call-to-Action)
     */
    public function getCta(): string
    {
        if (! $this->enable_cta) {
            return '';
        }

        if (! empty($this->cta_text)) {
            return $this->cta_text;
        }

        // Default CTA
        $mention = $this->page_mention ? " {$this->page_mention}" : '';

        return "🔮 อยากรู้ดวงละเอียดกว่านี้? ทักมาเลย{$mention}\n📱 ดูดวงส่วนตัว AI ตอบทันที 24 ชม.";
    }

    /**
     * Tags เฉพาะวัน (เทรนด์ตามวัน)
     */
    protected function getDaySpecificTags(int $dayOfWeek): array
    {
        return match ($dayOfWeek) {
            0 => ['#วันอาทิตย์', '#สุขสันต์วันอาทิตย์'],
            1 => ['#วันจันทร์', '#สดใสวันจันทร์', '#MondayMotivation'],
            2 => ['#วันอังคาร'],
            3 => ['#วันพุธ', '#กลางสัปดาห์'],
            4 => ['#วันพฤหัสบดี', '#ใกล้วันหยุด'],
            5 => ['#วันศุกร์', '#สุขสันต์วันศุกร์', '#TGIF'],
            6 => ['#วันเสาร์', '#สุขสันต์วันหยุด', '#weekend'],
            default => [],
        };
    }

    /**
     * Label สถานะภาษาไทย
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'แบบร่าง',
            self::STATUS_ACTIVE => 'กำลังทำงาน',
            self::STATUS_PAUSED => 'หยุดชั่วคราว',
            self::STATUS_CANCELLED => 'ยกเลิก',
            default => $this->status,
        };
    }

    /**
     * Badge color สำหรับ UI
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'gray',
            self::STATUS_ACTIVE => 'green',
            self::STATUS_PAUSED => 'yellow',
            self::STATUS_CANCELLED => 'red',
            default => 'gray',
        };
    }
}
