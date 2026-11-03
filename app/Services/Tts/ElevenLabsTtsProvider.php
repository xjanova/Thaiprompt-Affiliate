<?php

namespace App\Services\Tts;

use App\Models\FortuneTellingSetting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 🎭 (2026-11-04) ElevenLabs ตรง — เสียงพากย์ตัวละคร (แม่หมอจันทรา / น้องบุษบา / แม่หมอโหมดคุณไสย)
 *
 * เจ้าของสั่งให้ต่อ ElevenLabs ตรง แทนการผ่าน X-DREAMER (X-DREAMER ยังไม่มีบริการเสียงนี้จริง)
 *   POST {base}/v1/text-to-speech/{voice_id}?output_format=mp3_44100_128   header xi-api-key
 *        body {text, model_id} → ได้ไฟล์ mp3 กลับมาทันที (sync ไม่ต้อง poll)
 *   GET  {base}/v1/voices               → เสียงในบัญชี (รวมเสียงมาตรฐานของ ElevenLabs)
 *   GET  {base}/v1/user/subscription    → โควต้าตัวอักษรคงเหลือ (ใช้ทดสอบ key ด้วย ไม่เสียโควต้า)
 *
 * คิดเงินตามจำนวนตัวอักษร · แท็กอารมณ์ของ v3 เช่น [softly] [laughs] [whispers] ถูกอ่านเป็นน้ำเสียง ไม่อ่านออกเสียง
 *
 * 🔒 key ห้ามโผล่ใน log / error — error ทุกข้อความผ่าน scrub() และไม่ log header
 */
class ElevenLabsTtsProvider implements TtsProviderInterface
{
    public const BASE_URL = 'https://api.elevenlabs.io';

    public const DEFAULT_MODEL = 'eleven_v3';

    /** ตัวอักษรสูงสุดต่อคำสั่ง (เพดานต่ำสุดของโมเดล v3 — ห้องแชทตัดที่ 1,500 อยู่แล้ว) */
    public const MAX_CHARS = 3000;

    public const OUTPUT_FORMAT = 'mp3_44100_128';

    /**
     * เสียงมาตรฐานของ ElevenLabs — ใช้เมื่อดึงรายชื่อจากบัญชีไม่ได้ (รายชื่อจริงดึงสดที่ listVoices())
     *
     * @var array<int, array{id: string, name: string, gender: string}>
     */
    public const FALLBACK_VOICES = XDreamerTtsProvider::FALLBACK_VOICES;

    /** ไฟล์เสียงใหญ่สุดที่ยอมรับ */
    protected const MAX_AUDIO_BYTES = 20 * 1024 * 1024;

    protected string $baseUrl;

    /**
     * @param  string|null  $apiKey  ส่งตรงได้ (หน้าแอดมินทดสอบ key ก่อนบันทึก) — null = อ่านจากค่ากลาง
     */
    public function __construct(
        protected FortuneTellingSetting $settings,
        protected ?string $apiKey = null,
        ?string $baseUrl = null,
    ) {
        $this->baseUrl = rtrim($baseUrl ?: (string) config('services.elevenlabs.base_url', self::BASE_URL), '/');
    }

    public function name(): string
    {
        return 'elevenlabs';
    }

    public function isAvailable(): bool
    {
        return $this->key() !== null;
    }

    /**
     * โมเดลเสียงที่ใช้ (แอดมินเปลี่ยนได้ ค่าเริ่มต้น Eleven v3)
     */
    public function model(): string
    {
        return $this->settings->getElevenlabsTtsModel() ?? self::DEFAULT_MODEL;
    }

    /**
     * สังเคราะห์ข้อความเป็น mp3 ลงไฟล์ output_path
     *
     * options: 'voice' (id เสียง ElevenLabs — ไม่ระบุ/ไม่ใช่รูปแบบ ElevenLabs = เสียงแม่หมอที่เลือกไว้), 'output_path'
     */
    public function synthesize(string $text, array $options = []): array
    {
        $startTime = microtime(true);
        $voice = $options['voice'] ?? null;
        // chain เสียงเดิมของบอทส่ง voice ของ provider อื่นมาได้ (เช่น id ของ MiniMax) — id ของ ElevenLabs = ตัวอักษร 20 ตัว
        if (! is_string($voice) || ! preg_match('/^[A-Za-z0-9]{20}$/', $voice)) {
            $voice = ($this->settings->maemor_voice_id ?: null) ?? self::FALLBACK_VOICES[0]['id'];
        }
        $outputPath = $options['output_path'] ?? null;

        if (! $outputPath) {
            return $this->failResult($startTime, 'output_path required', $voice);
        }

        $key = $this->key();
        if ($key === null) {
            return $this->failResult($startTime, 'ยังไม่ได้ตั้ง ElevenLabs API key', $voice);
        }

        $text = trim($text);
        if ($text === '') {
            return $this->failResult($startTime, 'ข้อความว่าง', $voice);
        }
        if (mb_strlen($text) > self::MAX_CHARS) {
            $text = mb_substr($text, 0, self::MAX_CHARS);
        }

        try {
            // ⚠️ ห้าม retry เอง — timeout หลังคำขอถึง ElevenLabs แล้ว = สั่งซ้ำ = เสียโควต้าสองรอบ
            $response = Http::withHeaders(['xi-api-key' => $key, 'Accept' => 'audio/mpeg'])
                ->connectTimeout(12)
                ->timeout((int) ($options['timeout'] ?? 90))
                ->post($this->baseUrl.'/v1/text-to-speech/'.rawurlencode($voice).'?output_format='.self::OUTPUT_FORMAT, [
                    'text' => $text,
                    'model_id' => $this->model(),
                ]);
        } catch (\Throwable $e) {
            $error = 'เชื่อมต่อ ElevenLabs ไม่ได้: '.$this->scrub($e->getMessage());
            Log::warning('ElevenLabs TTS: connection fail', ['error' => mb_substr($error, 0, 200)]);

            return $this->failResult($startTime, $error, $voice);
        }

        if (! $response->successful()) {
            $error = $this->errorFrom($response);
            Log::warning('ElevenLabs TTS: HTTP fail', ['status' => $response->status(), 'error' => mb_substr($error, 0, 200)]);

            return $this->failResult($startTime, $error, $voice);
        }

        $body = $response->body();
        $bytes = strlen($body);
        if ($bytes < 100 || str_starts_with(ltrim($body), '{')) {
            return $this->failResult($startTime, 'ElevenLabs ส่งไฟล์เสียงว่างหรือเสียกลับมา', $voice);
        }
        if ($bytes > self::MAX_AUDIO_BYTES) {
            return $this->failResult($startTime, 'ไฟล์เสียงใหญ่เกินกำหนด', $voice);
        }

        $dir = dirname($outputPath);
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        file_put_contents($outputPath, $body);

        $durationMs = (int) round((microtime(true) - $startTime) * 1000);
        Log::info('🎭 ElevenLabs TTS สำเร็จ', [
            'model' => $this->model(),
            'voice' => $voice,
            'chars' => mb_strlen($text),
            'bytes' => $bytes,
            'duration_ms' => $durationMs,
        ]);

        return [
            'success' => true,
            'audio_path' => $outputPath,
            'error' => null,
            'duration_ms' => $durationMs,
            'voice_used' => $voice,
            'audio_length_ms' => null,
            'usage_chars' => mb_strlen($text),
        ];
    }

    /**
     * รายชื่อเสียงในบัญชี ElevenLabs (ดึงไม่ได้ = รายชื่อสำรองในไฟล์นี้)
     *
     * @return array{voices: array<int, array{id: string, name: string, gender: string}>, live: bool, max_chars: int}
     */
    public function listVoices(): array
    {
        $response = $this->get('/v1/voices');

        if ($response['success']) {
            $voices = [];
            foreach (($response['data']['voices'] ?? []) as $v) {
                if (! is_array($v) || empty($v['voice_id'])) {
                    continue;
                }
                $labels = is_array($v['labels'] ?? null) ? $v['labels'] : [];
                $hint = trim(implode(' · ', array_filter([
                    $labels['accent'] ?? null,
                    $labels['age'] ?? null,
                    $labels['description'] ?? ($labels['descriptive'] ?? null),
                ], fn ($x) => is_string($x) && $x !== '')));

                $voices[] = [
                    'id' => (string) $v['voice_id'],
                    'name' => (string) ($v['name'] ?? $v['voice_id']).($hint !== '' ? ' — '.$hint : ''),
                    'gender' => strtolower((string) ($labels['gender'] ?? '')),
                ];
            }

            if ($voices !== []) {
                return ['voices' => $voices, 'live' => true, 'max_chars' => self::MAX_CHARS];
            }
        }

        return ['voices' => self::FALLBACK_VOICES, 'live' => false, 'max_chars' => self::MAX_CHARS];
    }

    /**
     * โควต้าตัวอักษรคงเหลือของรอบบิล — ใช้ทดสอบ key ด้วย (ไม่เสียโควต้า)
     *
     * key ที่จำกัดสิทธิ์ (ไม่ได้เปิด user_read) อ่านโควต้าไม่ได้ แต่ยังสร้างเสียงได้ → นับว่า key ใช้ได้ (balance = null)
     *
     * @return array{success: bool, balance: ?int, limit: ?int, error: ?string}
     */
    public function credits(): array
    {
        $response = $this->get('/v1/user/subscription');

        if ($response['success']) {
            $used = (int) ($response['data']['character_count'] ?? 0);
            $limit = (int) ($response['data']['character_limit'] ?? 0);

            return ['success' => true, 'balance' => max(0, $limit - $used), 'limit' => $limit, 'error' => null];
        }

        if ($response['detail_status'] === 'missing_permissions') {
            return ['success' => true, 'balance' => null, 'limit' => null, 'error' => null];
        }

        return ['success' => false, 'balance' => null, 'limit' => null, 'error' => $response['error']];
    }

    /**
     * GET แบบอ่านอย่างเดียว (retry เมื่อเชื่อมต่อไม่ได้)
     *
     * @return array{success: bool, data: array, error: ?string, detail_status: ?string}
     */
    protected function get(string $path): array
    {
        $key = $this->key();
        if ($key === null) {
            return ['success' => false, 'data' => [], 'error' => 'ยังไม่ได้ตั้ง ElevenLabs API key', 'detail_status' => null];
        }

        try {
            $response = Http::withHeaders(['xi-api-key' => $key])
                ->acceptJson()
                ->connectTimeout(12)
                ->timeout(20)
                ->retry(2, 1500, fn ($e) => $e instanceof ConnectionException, throw: false)
                ->get($this->baseUrl.$path);
        } catch (\Throwable $e) {
            return ['success' => false, 'data' => [], 'error' => 'เชื่อมต่อ ElevenLabs ไม่ได้: '.$this->scrub($e->getMessage()), 'detail_status' => null];
        }

        if (! $response->successful()) {
            return ['success' => false, 'data' => [], 'error' => $this->errorFrom($response), 'detail_status' => $this->detailStatus($response)];
        }

        $json = $response->json();

        return is_array($json)
            ? ['success' => true, 'data' => $json, 'error' => null, 'detail_status' => null]
            : ['success' => false, 'data' => [], 'error' => 'ElevenLabs ตอบกลับไม่ใช่ JSON', 'detail_status' => null];
    }

    /**
     * แปลง error ของ ElevenLabs เป็นข้อความที่แอดมินอ่านแล้วรู้ว่าต้องทำอะไร
     */
    protected function errorFrom(Response $response): string
    {
        $status = $this->detailStatus($response);
        $message = $this->detailMessage($response);

        $known = match ($status) {
            'invalid_api_key' => 'ElevenLabs ไม่รับ API key นี้ (key ผิดหรือถูกลบ)',
            'missing_permissions' => 'key นี้ไม่มีสิทธิ์ใช้งานส่วนนี้ — เปิดสิทธิ์ Text to Speech ให้ key ในหน้า ElevenLabs',
            'quota_exceeded' => 'โควต้าตัวอักษร ElevenLabs หมดแล้ว',
            'voice_not_found' => 'ไม่พบเสียงนี้ในบัญชี ElevenLabs — เพิ่มเสียงเข้า My Voices หรือเลือกเสียงอื่น',
            'too_many_concurrent_requests', 'system_busy' => 'ElevenLabs คิวเต็ม รอสักครู่แล้วลองใหม่',
            default => null,
        };

        if ($known !== null) {
            return $known;
        }

        return match (true) {
            $response->status() === 401 => 'ElevenLabs ไม่รับ API key นี้'.($message ? ': '.$message : ''),
            $response->status() === 429 => 'เรียก ElevenLabs ถี่เกินไป รอสักครู่แล้วลองใหม่',
            default => 'ElevenLabs ตอบ HTTP '.$response->status().($message ? ': '.$message : ''),
        };
    }

    protected function detailStatus(Response $response): ?string
    {
        $detail = $response->json('detail');

        return is_array($detail) && isset($detail['status']) && is_string($detail['status']) ? $detail['status'] : null;
    }

    protected function detailMessage(Response $response): ?string
    {
        $detail = $response->json('detail');
        $message = match (true) {
            is_string($detail) => $detail,
            is_array($detail) && isset($detail['message']) => (string) $detail['message'],
            is_array($detail) && isset($detail[0]['msg']) => (string) $detail[0]['msg'],
            default => null,
        };

        return $message !== null ? mb_substr($this->scrub($message), 0, 300) : null;
    }

    protected function key(): ?string
    {
        $key = $this->apiKey !== null ? trim($this->apiKey) : $this->settings->getElevenlabsApiKey();

        return $key !== null && $key !== '' ? $key : null;
    }

    /**
     * ลบ key ออกจากข้อความ (กันหลุดไปกับ error/log ทุกกรณี)
     */
    protected function scrub(string $message): string
    {
        $key = $this->key();
        if ($key !== null) {
            $message = str_replace($key, '[key]', $message);
        }

        return (string) preg_replace('/sk_[A-Za-z0-9]{8,}/', 'sk_[key]', $message);
    }

    protected function failResult(float $startTime, ?string $error, ?string $voice): array
    {
        return [
            'success' => false,
            'audio_path' => null,
            'error' => $error ?: 'unknown',
            'duration_ms' => (int) round((microtime(true) - $startTime) * 1000),
            'voice_used' => $voice,
            'audio_length_ms' => null,
            'usage_chars' => 0,
        ];
    }
}
