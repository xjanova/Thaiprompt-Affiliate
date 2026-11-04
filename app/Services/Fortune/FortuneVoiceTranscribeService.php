<?php

namespace App\Services\Fortune;

use App\Models\FortuneTellingSetting;
use App\Models\FortuneVoiceUsage;
use App\Models\Setting;
use App\Services\Stt\ElevenLabsScribeProvider;
use App\Services\Stt\SttProviderInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 🎙️ (2026-11-04) ถอดเสียงไมค์ของลูกค้าเว็บจันทรา (ทางสำรองของ Web Speech ในเบราว์เซอร์)
 *
 * คุมงบ: เพดานวินาทีเสียงที่ถอดต่อวันทั้งเว็บ (ตาราง settings `fortune_voice_stt_daily_seconds`, ค่าเริ่ม 3 ชม. ≈ ฿22)
 * นับจากความยาวที่ Scribe ตอบกลับ (หลังจ่ายแล้ว) — เพดานจึงเป็นแบบนุ่ม เกินได้ไม่เกินหนึ่งคลิป
 * เพดานต่อลูกค้าอยู่ที่ throttle jwvoice (ต่อ user_ref) + pass ฝั่งเว็บ
 */
class FortuneVoiceTranscribeService
{
    public const DAILY_SECONDS_SETTING = 'fortune_voice_stt_daily_seconds';

    public const DEFAULT_DAILY_SECONDS = 10800;

    protected SttProviderInterface $provider;

    public function __construct(?SttProviderInterface $provider = null)
    {
        $this->provider = $provider ?? new ElevenLabsScribeProvider(FortuneTellingSetting::getGlobalSettings());
    }

    public function provider(): SttProviderInterface
    {
        return $this->provider;
    }

    public function dailySecondsLimit(): int
    {
        try {
            $limit = (int) Setting::get(self::DAILY_SECONDS_SETTING, 0);
        } catch (\Throwable) {
            $limit = 0;
        }

        return $limit > 0 ? $limit : self::DEFAULT_DAILY_SECONDS;
    }

    public function secondsUsedToday(): float
    {
        return ((int) Cache::get($this->budgetKey(), 0)) / 100;
    }

    /**
     * @return array{success:bool,code:int,error:?string,data:?array{text:string,words:array,language:?string,duration:float,provider:string}}
     */
    public function transcribe(string $path, ?string $mime, ?string $persona, ?string $userRef): array
    {
        if (! $this->provider->isAvailable()) {
            return ['success' => false, 'code' => 503, 'error' => 'ยังไม่ได้ตั้งค่าระบบถอดเสียง', 'data' => null];
        }
        if ($this->secondsUsedToday() >= $this->dailySecondsLimit()) {
            Log::warning('🎙️ ถอดเสียง: ชนเพดานรายวัน', ['used' => $this->secondsUsedToday(), 'limit' => $this->dailySecondsLimit()]);

            return ['success' => false, 'code' => 429, 'error' => 'วันนี้ใช้ระบบถอดเสียงครบโควต้าแล้ว', 'data' => null];
        }

        $result = $this->provider->transcribe($path, ['mime' => $mime, 'language' => 'th']);
        $seconds = (float) ($result['duration'] ?? 0);
        if ($seconds > 0) {
            // เก็บเป็นหน่วย 1/100 วินาที — Cache::increment รับแต่จำนวนเต็ม
            Cache::add($this->budgetKey(), 0, now()->addDays(2));
            Cache::increment($this->budgetKey(), (int) round($seconds * 100));
        }

        FortuneVoiceUsage::record([
            'kind' => 'stt',
            'purpose' => 'transcribe',
            'provider' => $this->provider->name(),
            'persona' => $persona,
            'user_ref' => $userRef,
            'seconds' => $seconds,
            'chars' => mb_strlen((string) ($result['text'] ?? '')),
            'success' => (bool) $result['success'],
            'meta' => $result['success'] ? null : ['error' => mb_substr((string) $result['error'], 0, 200)],
        ]);

        if (! $result['success']) {
            return ['success' => false, 'code' => 502, 'error' => $result['error'], 'data' => null];
        }

        return ['success' => true, 'code' => 200, 'error' => null, 'data' => [
            'text' => $result['text'],
            'words' => $result['words'],
            'language' => $result['language'],
            'duration' => $seconds,
            'provider' => $this->provider->name(),
        ]];
    }

    protected function budgetKey(): string
    {
        return 'fortune:voice:stt:cs:'.now('Asia/Bangkok')->format('Ymd');
    }
}
