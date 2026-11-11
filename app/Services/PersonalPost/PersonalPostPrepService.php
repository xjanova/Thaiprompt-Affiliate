<?php

namespace App\Services\PersonalPost;

use App\Models\AiApiKey;
use App\Models\FortuneTellingSetting;
use App\Models\PersonalPostDraft;
use App\Models\Setting;
use App\Services\FortuneAIService;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * เตรียมโพสต์ส่วนตัวล่วงหน้า (บทความไทย + ภาพ) วันละ 4 โพสต์
 *
 * ขั้นตอน:
 *  1. ดึงผู้สมัครจาก Google Trends TH + ข่าวเทค (TrendSourceService)
 *  2. ให้ AI เลือก 4 หัวข้อที่เหมาะกับเฟซบุ๊กส่วนตัว (มีข่าวเทคอย่างน้อย 2)
 *  3. เขียนบทความไทยจากหัวข่าวจริงเท่านั้น
 *  4. ให้ AI เขียน prompt ภาพ → สร้างภาพด้วย OpenAI gpt-image (สไตล์ ChatGPT)
 *     สำรอง: Gemini image จาก AI Pool → Pollinations (ฟรี)
 *
 * ⚠️ ระบบไม่โพสต์ให้ — Meta ไม่มี API โพสต์ลงโปรไฟล์ส่วนตัว เจ้าของกดโพสต์เองเท่านั้น
 */
class PersonalPostPrepService
{
    /** คีย์ตั้งค่าใน settings */
    public const SETTING_ENABLED = 'personal_posts.enabled';

    public const SETTING_PROFILE_URL = 'personal_posts.profile_url';

    public const SETTING_TEXT_PROVIDER = 'personal_posts.text_provider';

    public const SETTING_IMAGE_MODEL = 'personal_posts.image_model';

    public const DEFAULT_PROFILE_URL = 'https://www.facebook.com/profile.php?id=100075062616964';

    /** ผู้ให้บริการ AI เขียนบทความที่เลือกได้ */
    public const TEXT_PROVIDERS = [
        'gemini' => 'Google Gemini (ฟรี tier)',
        'openai' => 'OpenAI (ChatGPT) 💰',
    ];

    /** โมเดลภาพ OpenAI — ID เดียวกับ ContentCampaignAutoPostService::POOL_IMAGE_MODELS (ยืนยันกับ API แล้ว) */
    public const OPENAI_IMAGE_MODELS = [
        'gpt-image-2' => 'gpt-image-2 (ใหม่สุด แบบ ChatGPT) 💰',
        'gpt-image-1.5' => 'gpt-image-1.5 💰',
        'gpt-image-1' => 'gpt-image-1 💰',
        'gpt-image-1-mini' => 'gpt-image-1-mini (ถูกสุด)',
    ];

    protected const GEMINI_IMAGE_FALLBACK = 'gemini-2.5-flash-image';

    /** หัวข้อต้องห้าม — ไม่เหมาะกับโพสต์ส่วนตัวสายเทค/ไลฟ์สไตล์ */
    protected const AVOID_TOPICS = 'การตาย อุบัติเหตุ อาชญากรรม การเมือง หวย/พนัน ผลบอลพนัน เนื้อหาผู้ใหญ่ ดราม่าส่วนตัวของคนทั่วไป ข่าวโศกนาฏกรรม';

    /** คำต้องห้ามที่ตัดทิ้งก่อนถึง AI (ไทย + อังกฤษ) */
    protected const BLOCK_PATTERN = '/(เสียชีวิต|ศพ|อุบัติเหตุ|รถชน|ฆาตกรรม|ข่มขืน|ระเบิด|หวย|ลอตเตอรี่|สลากกินแบ่ง|พนัน|บาคาร่า|แทงบอล|เลือกตั้ง|โป๊|'
        .'\bdead\b|\bdeath\b|\bdies\b|\bkilled\b|murder|shooting|lottery|casino|betting|porn)/iu';

    public function __construct(protected TrendSourceService $sources) {}

    /**
     * เตรียมโพสต์ทั้งวัน — เลือกหัวข้อแล้วสร้างทุกช่องที่ยังไม่พร้อม
     *
     * @param  bool  $force  true = เลือกหัวข้อใหม่และสร้างใหม่ทุกช่อง (ยกเว้นช่องที่โพสต์แล้ว)
     * @return array{created:int, ready:int, failed:int, skipped:int}
     */
    public function prepareDay(Carbon $date, bool $force = false): array
    {
        $date = $date->copy()->startOfDay();
        $stats = ['created' => 0, 'ready' => 0, 'failed' => 0, 'skipped' => 0];

        $existing = PersonalPostDraft::whereDate('post_date', $date)->get()->keyBy('slot');

        // ช่องที่ต้องทำ: ยังไม่มี / ล้ม / force (แต่ไม่แตะช่องที่โพสต์แล้วหรือเจ้าของข้าม)
        $slotsToFill = [];
        for ($slot = 1; $slot <= PersonalPostDraft::SLOTS_PER_DAY; $slot++) {
            $draft = $existing->get($slot);
            if ($draft && in_array($draft->status, [PersonalPostDraft::STATUS_POSTED, PersonalPostDraft::STATUS_SKIPPED], true)) {
                $stats['skipped']++;

                continue;
            }
            if ($draft && ! $force && $draft->status === PersonalPostDraft::STATUS_READY) {
                $stats['skipped']++;

                continue;
            }
            $slotsToFill[] = $slot;
        }

        if ($slotsToFill === []) {
            return $stats;
        }

        // หัวข้อที่ใช้ไปแล้วใน 3 วัน + ช่องที่เก็บไว้ของวันนี้ → กันซ้ำ
        $usedTitles = PersonalPostDraft::query()
            ->where(fn ($q) => $q
                // 3 วันก่อนหน้า
                ->where(fn ($q2) => $q2->whereDate('post_date', '<', $date)->whereDate('post_date', '>=', $date->copy()->subDays(3)))
                // ช่องของวันนี้ที่เก็บไว้ (โพสต์แล้ว/ข้าม/พร้อมแล้ว)
                ->orWhere(fn ($q2) => $q2->whereDate('post_date', $date)->whereNotIn('slot', $slotsToFill)))
            ->pluck('source_title')
            ->filter()
            ->values()
            ->all();

        $picks = $this->pickTopics($this->sources->candidates(), count($slotsToFill), $usedTitles);

        foreach ($slotsToFill as $i => $slot) {
            $pick = $picks[$i] ?? null;
            if ($pick === null) {
                $stats['failed']++;
                PersonalPostDraft::updateOrCreate(
                    ['post_date' => $date->toDateString(), 'slot' => $slot],
                    ['status' => PersonalPostDraft::STATUS_FAILED, 'error' => 'ไม่มีหัวข้อเทรนด์/ข่าวให้เลือก (ดึงฟีดไม่ได้)']
                );

                continue;
            }

            $draft = PersonalPostDraft::updateOrCreate(
                ['post_date' => $date->toDateString(), 'slot' => $slot],
                [
                    'source_type' => $pick['source_type'],
                    'topic' => $pick['topic'],
                    'source_title' => Str::limit($pick['title'], 495, ''),
                    'source_url' => $pick['url'] ? Str::limit($pick['url'], 995, '') : null,
                    'source_name' => $pick['source_name'] ? Str::limit($pick['source_name'], 145, '') : null,
                    'news_context' => $pick['headlines'],
                    'caption' => null,
                    'image_prompt' => null,
                    'image_path' => null,
                    'image_provider' => null,
                    'text_provider' => null,
                    'status' => PersonalPostDraft::STATUS_DRAFT,
                    'error' => null,
                    'posted_at' => null,
                ]
            );
            $stats['created']++;

            $this->generate($draft) ? $stats['ready']++ : $stats['failed']++;
        }

        return $stats;
    }

    /**
     * สร้างบทความ + ภาพของช่องเดียว
     *
     * @param  bool  $text  สร้างบทความใหม่
     * @param  bool  $image  สร้างภาพใหม่
     * @return bool true = พร้อมโพสต์
     */
    public function generate(PersonalPostDraft $draft, bool $text = true, bool $image = true): bool
    {
        @set_time_limit(600);
        $draft->update(['status' => PersonalPostDraft::STATUS_GENERATING, 'error' => null]);

        try {
            if ($text || trim((string) $draft->caption) === '') {
                [$caption, $provider] = $this->writeCaption($draft);
                $draft->update(['caption' => $caption, 'text_provider' => $provider]);
            }

            $imageError = null;
            if ($image || ! $draft->image_path) {
                $imageError = $this->makeImage($draft);
            }

            $draft->update([
                'status' => PersonalPostDraft::STATUS_READY,
                // บทความพร้อมแต่ภาพล้ม = ยังโพสต์ได้ (ข้อความล้วน) → เก็บเหตุผลไว้ให้เห็น
                'error' => $imageError,
            ]);

            return true;
        } catch (Throwable $e) {
            Log::warning('PersonalPost: สร้างโพสต์ไม่สำเร็จ', [
                'draft_id' => $draft->id,
                'error' => $e->getMessage(),
            ]);
            $draft->update([
                'status' => PersonalPostDraft::STATUS_FAILED,
                'error' => Str::limit('สร้างไม่สำเร็จ: '.$e->getMessage(), 900),
            ]);

            return false;
        }
    }

    /**
     * ให้ AI เลือกหัวข้อ — AI ล้ม = เลือกเองแบบตายตัว (ข่าวเทคครึ่งหนึ่ง + เทรนด์อันดับต้น)
     *
     * @param  array<int, array<string, mixed>>  $candidates
     * @param  array<int, string>  $usedTitles
     * @return array<int, array<string, mixed>> ผู้สมัครที่เลือก + key 'topic'
     */
    public function pickTopics(array $candidates, int $count, array $usedTitles = []): array
    {
        $used = array_map(fn ($t) => mb_strtolower(trim($t)), $usedTitles);
        $candidates = array_values(array_filter(
            $candidates,
            fn ($c) => ! in_array(mb_strtolower(trim($c['title'])), $used, true)
                // 🛡️ ตัดหัวข้อต้องห้ามด้วยคำในโค้ดก่อน — ตอน AI ล้มแล้วเลือกตามลำดับจะได้ไม่หยิบข่าวร้าย
                && ! preg_match(self::BLOCK_PATTERN, $c['title'].' '.implode(' ', array_column($c['headlines'] ?? [], 'title')))
        ));

        if ($candidates === [] || $count < 1) {
            return [];
        }

        // จำกัดรายการที่ส่ง AI — เทรนด์ 15 + ข่าวเทค 25
        $trends = array_slice(array_values(array_filter($candidates, fn ($c) => $c['source_type'] === 'google_trend')), 0, 15);
        $tech = array_slice(array_values(array_filter($candidates, fn ($c) => $c['source_type'] === 'tech_news')), 0, 25);
        $pool = array_merge($trends, $tech);

        $minTech = min(count($tech), (int) ceil($count / 2));

        try {
            $lines = [];
            foreach ($pool as $i => $c) {
                $tag = $c['source_type'] === 'google_trend' ? 'เทรนด์' : 'ข่าวเทค';
                $extra = $c['source_type'] === 'google_trend'
                    ? ' — ข่าวที่เกี่ยว: '.implode(' / ', array_map(fn ($h) => $h['title'], array_slice($c['headlines'], 0, 2)))
                    : ' ('.$c['source_name'].')';
                $traffic = $c['traffic'] ? " [ค้นหา {$c['traffic']}]" : '';
                $lines[] = "{$i}. [{$tag}] {$c['title']}{$traffic}{$extra}";
            }

            $prompt = "คุณเป็นบรรณาธิการเพจเฟซบุ๊กส่วนตัวสายเทคโนโลยีและเรื่องที่คนไทยกำลังสนใจ\n"
                ."เลือก {$count} หัวข้อจากรายการด้านล่าง เพื่อเขียนโพสต์ให้คนอยากอ่านและแชร์\n\n"
                ."กฎ:\n"
                ."1. ต้องเป็น [ข่าวเทค] อย่างน้อย {$minTech} หัวข้อ — เน้นของเปิดตัวใหม่ อัปเดตแอป/มือถือ/AI/เกม/แกดเจ็ต\n"
                ."2. [เทรนด์] เลือกเฉพาะเรื่องที่เล่าได้สนุก มีประโยชน์ หรือเกี่ยวกับเทคโนโลยี/บันเทิง/ไลฟ์สไตล์\n"
                .'3. ห้ามเลือกเรื่อง: '.self::AVOID_TOPICS."\n"
                ."4. ห้ามเลือกเรื่องซ้ำกันหรือเรื่องเดียวกันคนละสำนัก\n"
                ."5. ตั้งชื่อหัวข้อภาษาไทยสั้นๆ ไม่เกิน 60 ตัวอักษร\n\n"
                ."ตอบเป็น JSON อย่างเดียว ห้ามมีข้อความอื่น รูปแบบ:\n"
                .'{"picks":[{"index":0,"topic":"ชื่อหัวข้อไทย"}]}'
                ."\n\nรายการ:\n".implode("\n", $lines);

            [$raw] = $this->generateText($prompt, 'personal_post:pick');
            $json = $this->extractJson($raw);
            $picked = [];
            $seen = [];
            foreach (($json['picks'] ?? []) as $p) {
                $idx = (int) ($p['index'] ?? -1);
                if (! isset($pool[$idx]) || isset($seen[$idx])) {
                    continue;
                }
                $seen[$idx] = true;
                $topic = trim((string) ($p['topic'] ?? '')) ?: $pool[$idx]['title'];
                $picked[] = $pool[$idx] + ['topic' => Str::limit($topic, 250, '')];
                if (count($picked) >= $count) {
                    break;
                }
            }

            if (count($picked) >= $count) {
                return $picked;
            }

            // AI เลือกมาไม่ครบ → เติมด้วยตัวเลือกตายตัวที่ไม่ซ้ำ
            return $this->fillPicks($picked, $trends, $tech, $count, $minTech);
        } catch (Throwable $e) {
            Log::info('PersonalPost: AI เลือกหัวข้อล้ม → ใช้ลำดับตายตัว', ['error' => $e->getMessage()]);

            return $this->fillPicks([], $trends, $tech, $count, $minTech);
        }
    }

    /**
     * เติมหัวข้อแบบตายตัว: ข่าวเทคก่อนจนครบขั้นต่ำ แล้วสลับเทรนด์/เทค
     *
     * @param  array<int, array<string, mixed>>  $picked
     * @param  array<int, array<string, mixed>>  $trends
     * @param  array<int, array<string, mixed>>  $tech
     * @return array<int, array<string, mixed>>
     */
    protected function fillPicks(array $picked, array $trends, array $tech, int $count, int $minTech): array
    {
        $taken = array_map(fn ($p) => $p['title'], $picked);
        $techTaken = count(array_filter($picked, fn ($p) => $p['source_type'] === 'tech_news'));

        $queue = [];
        foreach ($tech as $c) {
            if ($techTaken + count($queue) >= $minTech) {
                break;
            }
            $queue[] = $c;
        }
        $rest = [];
        $max = max(count($trends), count($tech));
        for ($i = 0; $i < $max; $i++) {
            if (isset($trends[$i])) {
                $rest[] = $trends[$i];
            }
            if (isset($tech[$i])) {
                $rest[] = $tech[$i];
            }
        }

        foreach (array_merge($queue, $rest) as $c) {
            if (count($picked) >= $count) {
                break;
            }
            if (in_array($c['title'], $taken, true)) {
                continue;
            }
            $taken[] = $c['title'];
            $picked[] = $c + ['topic' => Str::limit($c['title'], 250, '')];
        }

        return $picked;
    }

    /**
     * เขียนบทความไทยจากหัวข่าวจริง
     *
     * @return array{0:string, 1:string} [บทความ, provider]
     */
    protected function writeCaption(PersonalPostDraft $draft): array
    {
        $facts = [];
        foreach ((array) $draft->news_context as $h) {
            $line = '- '.($h['title'] ?? '');
            if (! empty($h['source'])) {
                $line .= " ({$h['source']})";
            }
            if (! empty($h['summary'])) {
                $line .= "\n  สรุป: ".$h['summary'];
            }
            $facts[] = $line;
        }
        $factText = $facts ? implode("\n", $facts) : '- '.$draft->source_title;
        $kind = $draft->source_type === 'tech_news' ? 'ข่าวเทคโนโลยี' : 'เรื่องที่คนไทยกำลังค้นหาใน Google';
        $today = Carbon::parse($draft->post_date)->locale('th')->translatedFormat('j F Y');

        $prompt = "เขียนโพสต์เฟซบุ๊กส่วนตัวภาษาไทย 1 โพสต์ เรื่อง \"{$draft->topic}\" ({$kind}) ประจำวันที่ {$today}\n\n"
            ."ข้อเท็จจริงที่มี (ใช้ได้เฉพาะนี้เท่านั้น):\n{$factText}\n\n"
            ."กฎ:\n"
            ."1. บรรทัดแรกเป็นพาดหัวดึงดูด มีอีโมจิ 1-2 ตัว\n"
            ."2. เล่าว่าเกิดอะไรขึ้น สำคัญยังไง และกระทบคนทั่วไปอย่างไร แบ่งย่อหน้าสั้นๆ อ่านง่ายบนมือถือ\n"
            ."3. ใช้ข้อเท็จจริงจากรายการข้างบนเท่านั้น ห้ามแต่งตัวเลข ราคา วันที่ สเปก หรือคำพูดของใครเพิ่ม — ไม่รู้ให้เล่าแบบภาพรวม\n"
            ."4. น้ำเสียงเป็นกันเอง เหมือนคนชอบเทคเล่าให้เพื่อนฟัง ใช้คำว่า \"เรา\" ได้\n"
            ."5. ชื่อแบรนด์/ผลิตภัณฑ์เขียนภาษาอังกฤษตามเดิม\n"
            ."6. ปิดท้ายด้วยคำถามชวนคอมเมนต์ 1 ประโยค\n"
            ."7. บรรทัดสุดท้ายใส่แฮชแท็ก 4-7 อัน ภาษาไทยหรืออังกฤษที่เกี่ยวข้อง\n"
            ."8. ห้ามใช้ markdown (** หรือ ##) ห้ามใส่ลิงก์\n"
            ."9. ความยาว 500-1100 ตัวอักษร\n\n"
            .'เริ่มเขียนโพสต์เลย:';

        [$text, $provider] = $this->generateText($prompt, 'personal_post:caption');
        $text = $this->cleanCaption($text);

        if (mb_strlen($text) < 120) {
            throw new Exception('AI ตอบบทความสั้นเกินไป');
        }

        return [$text, $provider];
    }

    /**
     * สร้างภาพประกอบ — คืนข้อความผิดพลาด (null = สำเร็จ)
     */
    protected function makeImage(PersonalPostDraft $draft): ?string
    {
        $prompt = $this->buildImagePrompt($draft);
        $draft->update(['image_prompt' => $prompt]);

        $relative = sprintf(
            'personal-posts/%s/slot%d-%s.png',
            Carbon::parse($draft->post_date)->format('Y-m-d'),
            $draft->slot,
            now()->format('His')
        );

        $chosen = (string) Setting::get(self::SETTING_IMAGE_MODEL, 'gpt-image-2');
        if (! isset(self::OPENAI_IMAGE_MODELS[$chosen])) {
            $chosen = 'gpt-image-2';
        }
        // ลองโมเดลที่เลือกก่อน แล้วไล่รุ่นอื่นของ OpenAI (บางคีย์ยังไม่มีสิทธิ์รุ่นใหม่)
        $models = array_values(array_unique(array_merge([$chosen], array_keys(self::OPENAI_IMAGE_MODELS))));

        foreach (AiApiKey::forProvider('openai')->available()->orderByDesc('priority')->limit(2)->get() as $key) {
            foreach ($models as $model) {
                $b64 = $this->callOpenAiImage($key, $model, $prompt);
                if ($b64 && $this->storeBinary($relative, base64_decode($b64, true) ?: '')) {
                    $this->replaceImage($draft, $relative, "openai:{$model}");

                    return null;
                }
            }
        }

        foreach (AiApiKey::forProvider('gemini')->available()->orderByDesc('priority')->limit(2)->get() as $key) {
            $b64 = $this->callGeminiImage($key, self::GEMINI_IMAGE_FALLBACK, $prompt);
            if ($b64 && $this->storeBinary($relative, base64_decode($b64, true) ?: '')) {
                $this->replaceImage($draft, $relative, 'gemini:'.self::GEMINI_IMAGE_FALLBACK);

                return 'OpenAI สร้างภาพไม่ได้ — ใช้ภาพจาก Gemini แทน';
            }
        }

        try {
            $url = 'https://image.pollinations.ai/prompt/'.rawurlencode(mb_substr($prompt, 0, 800))
                .'?width=1080&height=1080&nologo=true&model=flux&seed='.($draft->id * 10 + $draft->slot);
            $resp = Http::timeout(60)->withHeaders(['Accept' => 'image/*'])->get($url);
            if ($resp->successful() && str_starts_with((string) $resp->header('Content-Type'), 'image/')
                && $this->storeBinary($relative, $resp->body())) {
                $this->replaceImage($draft, $relative, 'pollinations:flux');

                return 'OpenAI/Gemini สร้างภาพไม่ได้ — ใช้ภาพสำรอง (Pollinations)';
            }
        } catch (Throwable $e) {
            Log::info('PersonalPost: Pollinations ล้ม', ['error' => $e->getMessage()]);
        }

        return 'สร้างภาพไม่สำเร็จทุกผู้ให้บริการ — กด "สร้างภาพใหม่" อีกครั้ง หรือโพสต์เป็นข้อความล้วน';
    }

    /**
     * ให้ AI แปลงบทความเป็น prompt ภาพอังกฤษ — AI ล้ม = prompt ตายตัวจากหัวข้อ
     */
    protected function buildImagePrompt(PersonalPostDraft $draft): string
    {
        $base = 'Square 1:1 social media cover image, modern clean 3D render style, vibrant cinematic lighting, '
            .'high detail, eye-catching composition, no watermark, no logos, no brand trademarks.';

        try {
            $ask = "อ่านโพสต์ภาษาไทยด้านล่าง แล้วเขียน image generation prompt ภาษาอังกฤษ 1 บรรทัด (25-60 คำ)\n"
                ."บรรยายฉากที่สื่อเรื่องนี้ชัดเจน สีสันสดใส ดึงสายตาบนฟีดเฟซบุ๊ก\n"
                ."กฎ: ห้ามใส่ชื่อคนจริงหรือหน้าคนดังจริง ห้ามโลโก้แบรนด์ ถ้าจะมีตัวหนังสือในภาพให้เป็นพาดหัวอังกฤษสั้นๆ ไม่เกิน 4 คำ\n"
                ."ตอบเฉพาะ prompt บรรทัดเดียว\n\n"
                ."หัวข้อ: {$draft->topic}\nโพสต์:\n".mb_substr((string) $draft->caption, 0, 1200);

            [$raw] = $this->generateText($ask, 'personal_post:image_prompt');
            $line = '';
            foreach (preg_split('/\R/u', trim($raw)) as $l) {
                $l = trim(preg_replace('/^(image\s*)?prompt\s*[:：]\s*/i', '', trim($l, " \t\"'`")));
                if ($l !== '') {
                    $line = $l;
                    break;
                }
            }

            $ascii = $line === '' ? 0 : strlen(preg_replace('/[^\x20-\x7E]/', '', $line)) / max(1, strlen($line));
            if (mb_strlen($line) >= 30 && $ascii >= 0.85) {
                return Str::limit($line, 900, '').' '.$base;
            }
        } catch (Throwable $e) {
            Log::info('PersonalPost: AI เขียน prompt ภาพล้ม → ใช้ prompt ตายตัว', ['error' => $e->getMessage()]);
        }

        $subject = $draft->source_type === 'tech_news'
            ? 'futuristic technology news concept, glowing gadgets and digital interface panels'
            : 'trending topic concept, lively modern scene';

        return "{$subject}, inspired by: ".preg_replace('/[^\x20-\x7E]/', ' ', (string) $draft->source_title).'. '.$base;
    }

    /**
     * OpenAI gpt-image → base64 PNG (ห้ามส่ง response_format — ตระกูล gpt-image ไม่รับ)
     */
    protected function callOpenAiImage(AiApiKey $key, string $model, string $prompt): ?string
    {
        try {
            $baseUrl = rtrim($key->resolveBaseUrl() ?: 'https://api.openai.com/v1', '/');
            $response = Http::timeout(150)
                ->withToken($key->api_key)
                ->post("{$baseUrl}/images/generations", [
                    'model' => $model,
                    'prompt' => mb_substr($prompt, 0, 4000),
                    'n' => 1,
                    'size' => '1024x1024',
                ]);

            if (! $response->successful()) {
                Log::info('PersonalPost: OpenAI image ไม่สำเร็จ', [
                    'model' => $model,
                    'key_id' => $key->id,
                    'status' => $response->status(),
                    'error' => $response->json('error.message', 'unknown'),
                ]);

                return null;
            }

            return $response->json('data.0.b64_json') ?: null;
        } catch (Throwable $e) {
            Log::info('PersonalPost: OpenAI image exception', ['model' => $model, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Gemini image (สำรอง) → base64 — ใช้ header ไม่ฝังคีย์ใน URL
     */
    protected function callGeminiImage(AiApiKey $key, string $model, string $prompt): ?string
    {
        try {
            $baseUrl = rtrim($key->resolveBaseUrl() ?: 'https://generativelanguage.googleapis.com/v1beta', '/');
            $response = Http::timeout(90)
                ->withHeaders(['x-goog-api-key' => $key->api_key])
                ->post("{$baseUrl}/models/{$model}:generateContent", [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => ['responseModalities' => ['TEXT', 'IMAGE']],
                ]);

            if (! $response->successful()) {
                return null;
            }

            foreach ($response->json('candidates.0.content.parts', []) as $part) {
                if (! empty($part['inlineData']['data'])) {
                    return $part['inlineData']['data'];
                }
            }
        } catch (Throwable $e) {
            Log::info('PersonalPost: Gemini image exception', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * เรียก AI ข้อความผ่าน FortuneAIService (หมุนคีย์ pool + fallback ข้าม provider เหมือนแคมเปญคอนเทนต์)
     *
     * ⚠️ สร้าง FortuneAIService เฉพาะตอนจะยิงจริง — new เฉยๆ ก็จองคีย์ pool แล้ว
     *
     * @return array{0:string, 1:string} [ข้อความ, provider]
     */
    protected function generateText(string $prompt, string $context): array
    {
        $provider = (string) Setting::get(self::SETTING_TEXT_PROVIDER, 'gemini');
        if (! isset(self::TEXT_PROVIDERS[$provider])) {
            $provider = 'gemini';
        }

        $ai = new FortuneAIService(FortuneTellingSetting::getSettings(), preferredProvider: $provider);
        $result = $ai->generateWithRetryAndFallback(
            questions: [$prompt],
            userProfile: null,
            userPosts: null,
            promptTemplate: null,
            readingType: 'basic',
            birthDate: null,
            userContext: $context,
        );

        $text = trim((string) ($result['response'] ?? ''));
        if ($text === '') {
            throw new Exception('AI ไม่ตอบข้อความ');
        }

        return [$text, (string) ($result['provider'] ?? $provider)];
    }

    /**
     * ดึง JSON ก้อนแรกจากคำตอบ AI (รองรับ ```json ... ```)
     *
     * @return array<string, mixed>
     */
    protected function extractJson(string $raw): array
    {
        if (preg_match('/\{[\s\S]*\}/u', $raw, $m)) {
            $data = json_decode($m[0], true);
            if (is_array($data)) {
                return $data;
            }
        }

        throw new Exception('AI ไม่ได้ตอบเป็น JSON');
    }

    /**
     * เก็บกวาดบทความ: ตัด markdown, คำนำของ AI, ลิงก์
     */
    protected function cleanCaption(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/^```[a-z]*\n?|```$/m', '', $text);
        $text = str_replace(['**', '__'], '', $text);
        $text = preg_replace('/^#{1,6}\s+/m', '', $text);
        $text = preg_replace('~https?://\S+~u', '', $text);
        // คำนำแบบ "นี่คือโพสต์..." / "แน่นอนค่ะ"
        $text = preg_replace('/^(แน่นอน[^\n]*|นี่คือโพสต์[^\n]*|ได้เลย[^\n]*)\n+/u', '', trim($text));
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }

    protected function storeBinary(string $relative, string $binary): bool
    {
        if ($binary === '' || strlen($binary) < 1000) {
            return false;
        }

        $absolute = storage_path('app/public/'.$relative);
        if (! is_dir(dirname($absolute))) {
            mkdir(dirname($absolute), 0755, true);
        }

        return file_put_contents($absolute, $binary) !== false;
    }

    /**
     * ใส่ภาพใหม่ แล้วลบภาพเก่าของช่องนี้ (กันไฟล์ค้างเวลากดสร้างใหม่หลายรอบ)
     */
    protected function replaceImage(PersonalPostDraft $draft, string $relative, string $provider): void
    {
        $old = $draft->image_path;
        $draft->update(['image_path' => $relative, 'image_provider' => $provider]);

        if ($old && $old !== $relative && str_starts_with($old, 'personal-posts/')) {
            $oldAbs = storage_path('app/public/'.$old);
            if (is_file($oldAbs)) {
                @unlink($oldAbs);
            }
        }
    }
}
