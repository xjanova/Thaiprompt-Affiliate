<?php

namespace App\Services\Fortune;

use App\Models\FortuneCharacterVoiceClip;
use App\Models\FortuneTellingSetting;
use App\Models\FortuneVoiceUsage;
use App\Services\FortuneVoiceStorageService;
use App\Services\Tts\ElevenLabsTtsProvider;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * 🎭 (2026-11-03) เสียงพากย์ตัวละคร "แม่หมอจันทรา" "น้องบุษบา" และ "แม่หมอโหมดคุณไสย" (ElevenLabs v3)
 *
 * หลักการคุมต้นทุน:
 *   - สร้างเสียงเฉพาะเมื่อมีคนขอ (on-demand) — ไม่สร้างอัตโนมัติทุกข้อความ
 *   - แคชด้วย text_hash: ข้อความ+เสียง+โมเดลเดิม = ไฟล์เดิม กดซ้ำ/หลายคนขอพร้อมกัน = สั่งครั้งเดียว
 *   - เพดานตัวอักษรต่อวันสำหรับเสียงในห้องแชท (เฉพาะที่สั่งใหม่จริง)
 *
 * (2026-11-04) ต่อ ElevenLabs ตรง (เจ้าของสั่ง — X-DREAMER ยังไม่มีบริการเสียงจริง) ได้ไฟล์ในคำขอเดียว
 *   clip สถานะ pending มีแค่ระหว่างอีกคำขอกำลังสร้างข้อความเดียวกันอยู่ → ผู้เรียก poll ต่อด้วย refresh()
 */
class FortuneCharacterVoiceService
{
    /**
     * ตัวละครที่มีเสียงพากย์
     *
     * @var array<string, array{label: string, brief: string, default_voice: string, sample_text: string}>
     */
    public const PERSONAS = [
        'maemor' => [
            'label' => 'แม่หมอจันทรา',
            'brief' => 'หญิงวัย 50+ น้ำเสียงนุ่ม อบอุ่น ขลัง น่าเชื่อถือ',
            'default_voice' => 'hpp4J3VqNfWAUOO0d1Us', // Bella — หญิง สุภาพ อบอุ่น
            'sample_text' => '[softly] สวัสดีจ้ะลูก แม่หมอจันทรารออยู่ตรงนี้แล้วนะ... [calm] ตั้งจิตให้สงบ นึกถึงเรื่องที่ค้างอยู่ในใจ แล้วค่อย ๆ เล่าให้แม่หมอฟัง ไพ่จะบอกทางให้เราเองจ้ะ',
        ],
        'busaba' => [
            'label' => 'น้องบุษบา',
            'brief' => 'หญิงวัย 20 สดใส น่ารัก ร่าเริง เป็นกันเอง',
            'default_voice' => 'BZgkqPqms7Kj9ulSkVzn', // Eve — หญิง สดใส ร่าเริง
            'sample_text' => '[excited] สวัสดีค่า บุษบาเองค่ะ! [laughs] วันนี้อยากรู้เรื่องอะไรเป็นพิเศษไหมคะ ความรัก การงาน หรือการเงิน บอกบุษบาได้เลยน้า เดี๋ยวบุษบาช่วยดูให้ค่ะ',
        ],
        // ห้องคุณไสยของเว็บจันทรา — บุคลิกต้องตรงกับห้อง จึงแยกเสียงจากแม่หมอปกติ
        'kunsai' => [
            'label' => 'แม่หมอโหมดคุณไสย',
            'brief' => 'หญิงวัย 50+ เสียงต่ำ ลึกลับ ขลัง เยือกเย็น จริงจัง',
            'default_voice' => 'Z3R5wn05IrDiVCyEkUrK', // Arabella — หญิง ลึกลับ มีอารมณ์
            'sample_text' => '[whispers] ลูกเอ๋ย... สิ่งที่ตามลูกมา มันไม่ใช่เรื่องบังเอิญ [softly] ตั้งสติไว้ แม่หมอจะเปิดไพ่ดูให้ ว่าใครทำ ทำด้วยอะไร และจะแก้อย่างไร [sighs] อย่าเพิ่งกลัวนะลูก',
        ],
    ];

    /** 🔈 (2026-11-04) งานที่ลูกค้าเป็นคนขอ — นับเพดานตัวอักษรรายวัน (sample ของแอดมินไม่นับ) */
    public const METERED_PURPOSES = ['chat', 'greeting', 'narration'];

    /** ตัวอักษรสูงสุดต่อข้อความในห้องแชท (ยาวกว่านี้ตัดทิ้ง — คุมต้นทุนต่อครั้ง) */
    public const MAX_CHAT_CHARS = 1500;

    /** ตัวอักษรสูงสุดของประโยคตัวอย่างในหน้าแอดมิน */
    public const MAX_SAMPLE_CHARS = 600;

    /** เพดานตัวอักษรที่สั่งใหม่ต่อวัน (เสียงในห้องแชท) ถ้าแอดมินไม่ได้ตั้ง */
    public const DEFAULT_DAILY_CHAR_LIMIT = 30000;

    /** งานค้าง pending นานเกินนี้ (นาที) = ถือว่าล้ม ให้สั่งใหม่ได้ (คำขอ ElevenLabs หมดเวลาที่ 90 วิ + เผื่อเก็บไฟล์) */
    protected const STALE_PENDING_MINUTES = 3;

    /** งานที่ล้มแล้ว ห้องแชทขอซ้ำได้อีกครั้งหลังกี่วินาที (กันกดรัวแล้วสั่งซ้ำ) */
    protected const FAILED_RETRY_AFTER_SECONDS = 120;

    protected FortuneTellingSetting $settings;

    protected ElevenLabsTtsProvider $provider;

    protected FortuneVoiceStorageService $storage;

    public function __construct(
        ?FortuneTellingSetting $settings = null,
        ?ElevenLabsTtsProvider $provider = null,
        ?FortuneVoiceStorageService $storage = null,
    ) {
        // ค่ากลางเสมอ — เสียงตัวละครไม่ผูกกับเพจสาขา
        $this->settings = $settings ?? FortuneTellingSetting::getGlobalSettings();
        $this->provider = $provider ?? new ElevenLabsTtsProvider($this->settings);
        $this->storage = $storage ?? new FortuneVoiceStorageService($this->settings);
    }

    public function provider(): ElevenLabsTtsProvider
    {
        return $this->provider;
    }

    public static function isPersona(?string $persona): bool
    {
        return is_string($persona) && array_key_exists($persona, self::PERSONAS);
    }

    /**
     * เสียงที่เลือกไว้ของตัวละคร (ยังไม่เลือก = ค่าเริ่มต้น)
     */
    public function voiceFor(string $persona): string
    {
        $column = $persona.'_voice_id';
        $voice = trim((string) ($this->settings->{$column} ?? ''));

        return $voice !== '' ? $voice : self::PERSONAS[$persona]['default_voice'];
    }

    /**
     * บันทึกเสียงที่เลือกให้ตัวละคร
     */
    public function selectVoice(string $persona, string $voiceId): void
    {
        $this->settings->update([$persona.'_voice_id' => $voiceId]);
        FortuneTellingSetting::clearSettingsCache();
    }

    public function dailyCharLimit(): int
    {
        $limit = (int) ($this->settings->character_voice_daily_char_limit ?? 0);

        return $limit > 0 ? $limit : self::DEFAULT_DAILY_CHAR_LIMIT;
    }

    public function charsUsedToday(): int
    {
        return (int) Cache::get($this->budgetKey(), 0);
    }

    /**
     * ขอไฟล์เสียงของข้อความ — เจอในแคชคืนทันที ไม่เจอค่อยสั่งสร้าง
     *
     * @param  string  $purpose  'chat' (ห้องแชท) | 'greeting' (ทักลูกค้าคุ้นเคย) | 'narration' (เล่าคำทำนาย)
     *                           — สามอย่างนี้นับเพดานรายวัน · 'sample' (แอดมินฟังเทียบ) ไม่นับ
     * @param  int  $waitSeconds  (คงไว้ให้ผู้เรียกเดิม) ElevenLabs ตอบไฟล์ในคำขอเดียว ไม่ต้องรอ
     * @param  string|null  $userRef  🔈 (2026-11-04) ลูกค้าฝั่งเว็บที่ขอเสียง — ลงบันทึกต้นทุน fortune_voice_usages
     * @return array{success: bool, clip: ?FortuneCharacterVoiceClip, error: ?string, code: int}
     */
    public function request(string $persona, string $text, string $purpose = 'chat', ?string $voiceId = null, int $waitSeconds = 0, ?string $userRef = null): array
    {
        $metered = in_array($purpose, self::METERED_PURPOSES, true);
        if (! self::isPersona($persona)) {
            return $this->fail('ไม่รู้จักตัวละครนี้', 422);
        }
        if (! $this->provider->isAvailable()) {
            return $this->fail('ยังไม่ได้ตั้ง ElevenLabs API key', 503);
        }

        $clean = $this->prepareText($text, $purpose === 'sample' ? self::MAX_SAMPLE_CHARS : self::MAX_CHAT_CHARS);
        if ($clean === '') {
            return $this->fail('ไม่มีข้อความให้อ่าน', 422);
        }

        $voice = $voiceId ?: $this->voiceFor($persona);
        $model = $this->provider->model();
        // รวม persona ในกุญแจ — แม่หมอกับโหมดคุณไสยเลือกเสียงเดียวกันได้ ไฟล์ต้องแยกโฟลเดอร์ตามตัวละคร
        $hash = hash('sha256', implode('|', ['elevenlabs', $model, $voice, $persona, $clean]));

        $clip = FortuneCharacterVoiceClip::where('text_hash', $hash)->first();

        if ($clip && $clip->isCompleted()) {
            $clip->increment('hits');

            return $this->ok($clip);
        }

        if ($clip && $clip->isPending() && ! $this->isStale($clip)) {
            return $this->ok($clip);
        }

        if ($clip && $clip->status === FortuneCharacterVoiceClip::STATUS_FAILED
            && $metered
            && $clip->updated_at && $clip->updated_at->gt(now()->subSeconds(self::FAILED_RETRY_AFTER_SECONDS))) {
            return ['success' => false, 'clip' => $clip, 'error' => $clip->error ?: 'สร้างเสียงไม่สำเร็จ', 'code' => 502];
        }

        // 🔒 กันสองคำขอพร้อมกันสั่งงานซ้ำ (เสียโควต้าสองรอบ) — นานกว่า timeout ของ ElevenLabs (90 วิ)
        $lock = Cache::lock('fortune:charvoice:'.$hash, 120);
        if (! $lock->get()) {
            $clip = FortuneCharacterVoiceClip::where('text_hash', $hash)->first();

            return $clip ? $this->ok($clip) : $this->fail('กำลังสร้างเสียงอยู่ ลองใหม่อีกครั้ง', 409);
        }

        try {
            $chars = mb_strlen($clean);

            if ($metered && $this->charsUsedToday() + $chars > $this->dailyCharLimit()) {
                Log::warning('🎭 เสียงตัวละคร: ชนเพดานตัวอักษรรายวัน', [
                    'used' => $this->charsUsedToday(),
                    'limit' => $this->dailyCharLimit(),
                ]);

                return $this->fail('วันนี้ใช้โควต้าเสียงครบแล้ว', 429);
            }

            $clip = $this->upsertPending($clip, $hash, [
                'persona' => $persona,
                'purpose' => $purpose,
                'provider' => 'elevenlabs',
                'model' => $model,
                'voice_id' => $voice,
                'text' => $clean,
                'chars' => $chars,
            ]);

            if ($metered) {
                // นับก่อนยิง — ล้มกลางทางก็นับ (ElevenLabs อาจคิดไปแล้ว) กันโดนยิงซ้ำจนโควต้าไหล
                $this->addUsage($chars);
            }

            $clip = $this->synthesizeInto($clip, $clean, $voice);

            FortuneVoiceUsage::record([
                'kind' => 'tts',
                'purpose' => $purpose,
                'provider' => 'elevenlabs',
                'persona' => $persona,
                'user_ref' => $userRef,
                'chars' => $chars,
                'success' => $clip->status !== FortuneCharacterVoiceClip::STATUS_FAILED,
                'meta' => ['clip_id' => $clip->id, 'model' => $model],
            ]);
        } finally {
            $lock->release();
        }

        return $clip->status === FortuneCharacterVoiceClip::STATUS_FAILED
            ? ['success' => false, 'clip' => $clip, 'error' => $clip->error, 'code' => 502]
            : $this->ok($clip);
    }

    /**
     * สถานะล่าสุดของ clip — pending = อีกคำขอกำลังสร้างอยู่ · ค้างนานเกิน = ถือว่าล้ม ให้สั่งใหม่ได้
     *
     * @param  int  $waitSeconds  (คงไว้ให้ผู้เรียกเดิม) ไม่ได้ใช้แล้ว
     */
    public function refresh(FortuneCharacterVoiceClip $clip, int $waitSeconds = 0): FortuneCharacterVoiceClip
    {
        $clip = $clip->fresh() ?? $clip;

        if ($clip->isPending() && $this->isStale($clip)) {
            $clip->update(['status' => FortuneCharacterVoiceClip::STATUS_FAILED, 'error' => 'สร้างเสียงไม่เสร็จ (ค้างนานเกินไป)']);
        }

        return $clip;
    }

    /**
     * เสียงตัวอย่างที่สร้างไว้แล้วของตัวละคร (ล่าสุดต่อเสียง) — ใช้แสดงในหน้าแอดมิน
     *
     * @return array<string, array<string, mixed>> key = voice_id
     */
    public function samplesFor(string $persona): array
    {
        return FortuneCharacterVoiceClip::where('persona', $persona)
            ->where('purpose', 'sample')
            ->orderByDesc('id')
            ->get()
            ->unique('voice_id')
            ->mapWithKeys(fn (FortuneCharacterVoiceClip $c) => [$c->voice_id => $c->toPublicArray()])
            ->all();
    }

    /**
     * ลอก markdown/emoji/ป้ายระบบออก ให้เหลือแต่สิ่งที่ควรอ่านออกเสียง (คงแท็กอารมณ์ [softly] ของ v3)
     */
    public function prepareText(string $text, int $maxChars): string
    {
        $clean = preg_replace('/\[\[[^\]]*\]\]/u', ' ', $text);            // [[OFFER:...]] ป้ายระบบแชท
        $clean = preg_replace('#https?://\S+#u', ' ', $clean);              // ลิงก์ไม่ต้องอ่าน
        $clean = preg_replace('/\*\*(.+?)\*\*/u', '$1', $clean);
        $clean = preg_replace('/\*(.+?)\*/u', '$1', $clean);
        $clean = preg_replace('/__(.+?)__/u', '$1', $clean);
        $clean = preg_replace('/`(.+?)`/u', '$1', $clean);
        $clean = preg_replace('/^\s*(?:#+|[-*•]+)\s*/mu', '', $clean);
        $clean = preg_replace('/[─━═┃│┄┅┆┇┈┉┊┋]+/u', '', $clean);
        $clean = preg_replace('/[\x{1F000}-\x{1FFFF}\x{2600}-\x{27BF}\x{2300}-\x{23FF}\x{FE0F}\x{200D}]/u', '', $clean);
        $clean = preg_replace("/\n{2,}/u", '... ', (string) $clean);
        $clean = preg_replace('/\s+/u', ' ', (string) $clean);
        // จุดไข่ปลาที่เหลือค้างหัว/ท้าย (จากย่อหน้าที่ถูกลอกเนื้อหาออก) ไม่ต้องอ่าน
        $clean = preg_replace('/^(?:\s*\.\.\.)+|(?:\s*\.\.\.)+\s*$/u', '', (string) $clean);
        $clean = trim((string) $clean);

        return mb_strlen($clean) > $maxChars ? rtrim(mb_substr($clean, 0, $maxChars)) : $clean;
    }

    /**
     * สร้างเสียงจาก ElevenLabs แล้วเก็บไฟล์ที่ storage ของเราเอง
     */
    protected function synthesizeInto(FortuneCharacterVoiceClip $clip, string $text, string $voice): FortuneCharacterVoiceClip
    {
        $tempPath = storage_path('app/tmp-voice').'/charvoice-'.Str::random(16).'.mp3';

        try {
            $result = $this->provider->synthesize($text, ['voice' => $voice, 'output_path' => $tempPath]);
            if (! $result['success']) {
                $clip->update(['status' => FortuneCharacterVoiceClip::STATUS_FAILED, 'error' => $this->short($result['error'])]);

                return $clip;
            }

            $relative = 'fortune-voice/characters/'.$clip->persona.'/'.$clip->text_hash.'.mp3';
            $stored = $this->storage->putAudio($tempPath, $relative);
            if (! $stored['success'] || empty($stored['url'])) {
                $clip->update(['status' => FortuneCharacterVoiceClip::STATUS_FAILED, 'error' => 'เก็บไฟล์เสียงไม่สำเร็จ']);

                return $clip;
            }

            $clip->update([
                'status' => FortuneCharacterVoiceClip::STATUS_COMPLETED,
                'audio_path' => $relative,
                'audio_disk' => $stored['disk'],
                'audio_url' => $stored['url'],
                'error' => null,
                'completed_at' => now(),
            ]);

            Log::info('🎭 เสียงตัวละครพร้อมแล้ว', [
                'clip_id' => $clip->id,
                'persona' => $clip->persona,
                'purpose' => $clip->purpose,
                'voice' => $clip->voice_id,
                'chars' => $clip->chars,
            ]);

            return $clip;
        } finally {
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    /**
     * สร้างแถว pending ใหม่ หรือรีเซ็ตแถวเดิมที่ล้ม/ค้าง — ชน unique (สองเครื่องพร้อมกัน) = ใช้แถวเดิม
     */
    protected function upsertPending(?FortuneCharacterVoiceClip $clip, string $hash, array $attributes): FortuneCharacterVoiceClip
    {
        $reset = $attributes + [
            'status' => FortuneCharacterVoiceClip::STATUS_PENDING,
            'remote_generation_id' => null,
            'error' => null,
        ];

        if ($clip) {
            $clip->update($reset);

            return $clip;
        }

        try {
            return FortuneCharacterVoiceClip::create($reset + ['text_hash' => $hash]);
        } catch (QueryException $e) {
            $existing = FortuneCharacterVoiceClip::where('text_hash', $hash)->first();
            if (! $existing) {
                throw $e;
            }
            $existing->update($reset);

            return $existing;
        }
    }

    protected function isStale(FortuneCharacterVoiceClip $clip): bool
    {
        return $clip->updated_at === null
            || $clip->updated_at->lt(now()->subMinutes(self::STALE_PENDING_MINUTES));
    }

    protected function addUsage(int $chars): void
    {
        $key = $this->budgetKey();
        Cache::add($key, 0, now()->addDays(2));
        Cache::increment($key, $chars);
    }

    protected function budgetKey(): string
    {
        return 'fortune:charvoice:chars:'.now('Asia/Bangkok')->format('Ymd');
    }

    protected function short(?string $error): string
    {
        return mb_substr((string) ($error ?: 'unknown'), 0, 480);
    }

    protected function ok(FortuneCharacterVoiceClip $clip): array
    {
        return ['success' => true, 'clip' => $clip, 'error' => null, 'code' => $clip->isCompleted() ? 200 : 202];
    }

    protected function fail(string $error, int $code): array
    {
        return ['success' => false, 'clip' => null, 'error' => $error, 'code' => $code];
    }
}
