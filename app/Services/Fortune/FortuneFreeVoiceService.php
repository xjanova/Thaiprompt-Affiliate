<?php

namespace App\Services\Fortune;

use App\Models\FortuneTellingSetting;
use App\Models\FortuneVoiceUsage;
use App\Models\Setting;
use App\Services\FortuneVoiceStorageService;
use App\Services\TelegramAlertService;
use App\Services\Tts\GoogleCloudTtsProvider;
use App\Services\Tts\GttsProvider;
use App\Services\Tts\TtsProviderInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * 🎬 (2026-11-04) เสียงอ่านคำทำนายฟรี — ลูกค้าที่ไม่ได้ซื้อเสียงสมจริง ฿100 (ข้อ 20.2 / 21.2)
 *
 * ลำดับ: Google Cloud TTS (ถ้าแอดมินใส่กุญแจ + ยังไม่ถึงเพดานเดือน) → gTTS → ไม่มีเสียง (โรงละครเดินบทเงียบ)
 * ❌ ไม่ใช้ ElevenLabs ในชั้นนี้เด็ดขาด
 *
 * - ลอกแท็กอารมณ์ [softly] ออกก่อน (ไม่งั้นอ่านออกเสียงคำว่า softly)
 * - ข้อความเดิม + เสียงเดิม = ไฟล์เดิม (แคช 30 วัน) — ฟังซ้ำไม่นับเพดาน
 * - เพดานตัวอักษร Google ต่อเดือน `google_tts_monthly_char_cap` (ค่าเริ่ม 800,000 ต่ำกว่าโควตาฟรีของ Neural2/WaveNet 1 ล้าน)
 *   นับจาก fortune_voice_usages ของเดือนนี้ · ถึงเพดาน = ไป gTTS อัตโนมัติ (ไม่โดนเก็บเงินเกินโควตาฟรี) · 80% เตือน Telegram
 */
class FortuneFreeVoiceService
{
    public const MONTHLY_CAP_SETTING = 'google_tts_monthly_char_cap';

    public const DEFAULT_MONTHLY_CAP = 800000;

    /** เสียงไทยของ Google ต่อห้อง (JSON {persona: voice name}) — ว่าง = ค่าเริ่มของห้อง */
    public const ROOM_VOICES_SETTING = 'fortune_google_tts_room_voices';

    public const DEFAULT_ROOM_VOICES = ['maemor' => 'th-TH-Neural2-C', 'busaba' => 'th-TH-Standard-A', 'kunsai' => 'th-TH-Neural2-C'];

    /** ห้องคุณไสยพูดช้ากว่า ขรึมกว่า · บุษบาเร็วสดใส */
    private const ROOM_RATES = ['maemor' => 0.92, 'busaba' => 1.02, 'kunsai' => 0.88];

    public const ALERT_AT = 0.8;

    private const CACHE_TTL_SEC = 60 * 60 * 24 * 30;

    /** ⚠️ สร้างด้วย new เท่านั้น — ให้ container inject จะได้ FortuneTellingSetting ว่าง ๆ แทนค่ากลาง */
    public function __construct(
        private ?FortuneTellingSetting $settings = null,
        private ?TtsProviderInterface $google = null,
        private ?TtsProviderInterface $gtts = null,
    ) {
        $this->settings ??= FortuneTellingSetting::getGlobalSettings();
        $this->google ??= new GoogleCloudTtsProvider($this->settings);
        $this->gtts ??= new GttsProvider;
    }

    /**
     * @return array{status:'completed'|'none', url:?string, provider:?string}
     */
    public function speak(string $persona, string $text, ?string $userRef = null): array
    {
        $persona = JuntraChatService::isPersona($persona) ? $persona : JuntraChatService::DEFAULT_PERSONA;
        $plain = JuntraVoiceLibrary::plain($text);
        if ($plain === '') {
            return ['status' => 'none', 'url' => null, 'provider' => null];
        }

        $voice = $this->roomVoice($persona);
        $cacheKey = 'free_voice:'.hash('sha256', $persona.'|'.$voice.'|'.$plain);
        $hit = Cache::get($cacheKey);
        if (is_array($hit) && ! empty($hit['url'])) {
            return ['status' => 'completed'] + $hit;
        }

        $chars = mb_strlen($plain);
        $attempts = [];
        if ($this->google->isAvailable() && $this->googleCharsThisMonth() + $chars <= $this->monthlyCap()) {
            $attempts[] = [$this->google, ['voice' => $voice, 'speaking_rate' => self::ROOM_RATES[$persona] ?? 0.95]];
        }
        $attempts[] = [$this->gtts, ['language' => 'th']];

        foreach ($attempts as [$provider, $options]) {
            $out = $this->synthesize($provider, $plain, $options);
            FortuneVoiceUsage::record([
                'kind' => 'tts_free',
                'purpose' => 'narration',
                'provider' => $provider->name(),
                'persona' => $persona,
                'user_ref' => $userRef,
                'chars' => $chars,
                'success' => $out !== null,
            ]);
            if ($out === null) {
                continue;
            }

            $result = ['url' => $out, 'provider' => $provider->name()];
            Cache::put($cacheKey, $result, self::CACHE_TTL_SEC);
            if ($provider->name() === 'google_tts') {
                $this->alertNearCap();
            }

            return ['status' => 'completed'] + $result;
        }

        return ['status' => 'none', 'url' => null, 'provider' => null];
    }

    public function monthlyCap(): int
    {
        try {
            $cap = (int) Setting::get(self::MONTHLY_CAP_SETTING, self::DEFAULT_MONTHLY_CAP);
        } catch (\Throwable) {
            $cap = self::DEFAULT_MONTHLY_CAP;
        }

        return $cap > 0 ? $cap : self::DEFAULT_MONTHLY_CAP;
    }

    /** ตัวอักษรที่ส่ง Google สำเร็จเดือนนี้ (เวลาไทย) */
    public function googleCharsThisMonth(): int
    {
        try {
            return (int) FortuneVoiceUsage::query()
                ->where('kind', 'tts_free')
                ->where('provider', 'google_tts')
                ->where('success', true)
                ->where('created_at', '>=', now('Asia/Bangkok')->startOfMonth()->utc())
                ->sum('chars');
        } catch (\Throwable) {
            return 0;
        }
    }

    /** @return array<string,string> persona → ชื่อเสียง Google */
    public static function roomVoices(): array
    {
        try {
            $stored = json_decode((string) Setting::get(self::ROOM_VOICES_SETTING, ''), true);
        } catch (\Throwable) {
            $stored = null;
        }
        $voices = self::DEFAULT_ROOM_VOICES;
        foreach (is_array($stored) ? $stored : [] as $persona => $voice) {
            if (isset($voices[$persona]) && is_string($voice) && preg_match('/^[a-z]{2}-[A-Z]{2}-[A-Za-z0-9-]+$/', $voice)) {
                $voices[$persona] = $voice;
            }
        }

        return $voices;
    }

    public function roomVoice(string $persona): string
    {
        return self::roomVoices()[$persona] ?? self::DEFAULT_ROOM_VOICES['maemor'];
    }

    /** ไฟล์ mp3 ใน storage → URL · ไม่สำเร็จ = null (ลองตัวถัดไป) */
    private function synthesize(TtsProviderInterface $provider, string $text, array $options): ?string
    {
        $token = Str::random(40);
        $temp = storage_path("app/tmp-voice/{$token}.mp3");

        try {
            $result = $provider->synthesize($text, $options + ['output_path' => $temp]);
            if (! ($result['success'] ?? false) || ! is_file($temp) || filesize($temp) < 1000) {
                Log::info('🎬 เสียงอ่านฟรี: '.$provider->name().' ไม่สำเร็จ', ['error' => mb_substr((string) ($result['error'] ?? ''), 0, 200)]);

                return null;
            }

            $stored = (new FortuneVoiceStorageService($this->settings))->putAudio($temp, "fortune-free-voice/{$token}.mp3");

            return $stored['success'] ? $stored['url'] : null;
        } catch (\Throwable $e) {
            Log::warning('🎬 เสียงอ่านฟรี: '.$provider->name().' ล้ม', ['error' => mb_substr($e->getMessage(), 0, 200)]);

            return null;
        } finally {
            if (is_file($temp)) {
                @unlink($temp);
            }
        }
    }

    /** ใช้ Google ถึง 80% ของเพดานเดือนนี้ → เตือนแอดมินครั้งเดียวต่อเดือน */
    private function alertNearCap(): void
    {
        $cap = $this->monthlyCap();
        $used = $this->googleCharsThisMonth();
        if ($used < $cap * self::ALERT_AT) {
            return;
        }

        $month = now('Asia/Bangkok')->format('Y-m');
        app(TelegramAlertService::class)->send(
            '🔊 เสียงอ่านคำทำนายฟรี (Google Cloud) ใช้ไป '.number_format($used).' / '.number_format($cap)." ตัวอักษรแล้วในเดือน {$month}\n"
            .'ถึงเพดานเมื่อไหร่ ระบบสลับไปใช้ gTTS เอง (ไม่เสียเงิน) · ปรับเพดานได้ที่หน้าเสียงตัวละคร',
            'google_tts_cap_80:'.$month,
            60 * 24 * 31,
        );
    }
}
