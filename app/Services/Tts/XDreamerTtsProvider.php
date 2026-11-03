<?php

namespace App\Services\Tts;

use App\Models\FortuneTellingSetting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 🎭 (2026-11-03) X-DREAMER (aixman, ai.xman4289.com) — ElevenLabs V3 ผ่าน kie.ai
 *
 * เว็บของเจ้าของเอง เรียกผ่าน MCP (JSON-RPC over HTTP) ด้วย key ลูกค้า `axm-…`
 *   POST {base}/api/mcp  Authorization: Bearer axm-…
 *   - tools/call generate_speech {text, model, voice} → {id, status, result_urls}
 *   - tools/call get_generation  {id, wait_seconds≤45} → {status, result_urls, error}
 *   - tools/call list_models     {kind: 'speech'}       → รายชื่อเสียงของแต่ละโมเดล
 *   - tools/call get_credits                            → เครดิตคงเหลือ
 * (/api/v1 ของ aixman มีแค่ chat/models — งานเสียงต้องไปทาง MCP)
 *
 * งานเป็น async: สั่งแล้วได้ id → poll จนเสร็จ → โหลด mp3 จาก result_urls[0]
 * คิดเครดิตตามความยาวข้อความ (ต่อ 1,000 ตัวอักษร) — โมเดลนี้อ่านได้สูงสุด 5,000 ตัวอักษรต่อครั้ง
 * แท็กอารมณ์ของ v3 เช่น [softly] [laughs] [whispers] ถูกอ่านเป็นน้ำเสียง ไม่อ่านออกเสียง
 *
 * 🔒 key ห้ามโผล่ใน log / error — error ทุกข้อความผ่าน scrub() และไม่ log header
 */
class XDreamerTtsProvider implements TtsProviderInterface
{
    public const BASE_URL = 'https://ai.xman4289.com';

    public const DEFAULT_MODEL = 'xd/elevenlabs-v3';

    /** ตัวอักษรสูงสุดต่อคำสั่งของ ElevenLabs V3 บน X-DREAMER */
    public const MAX_CHARS = 5000;

    /**
     * เสียง ElevenLabs ที่ X-DREAMER เปิดให้ใช้ (สำเนาจาก ELEVENLABS_VOICES ใน aixman kie-catalog.ts)
     * ใช้เมื่อดึง list_models ไม่ได้ — รายชื่อจริงดึงสดจาก API ที่ listVoices()
     *
     * @var array<int, array{id: string, name: string, gender: string}>
     */
    public const FALLBACK_VOICES = [
        ['id' => 'hpp4J3VqNfWAUOO0d1Us', 'name' => 'Bella — หญิง สุภาพ อบอุ่น', 'gender' => 'female'],
        ['id' => 'EkK5I93UQWFDigLMpZcX', 'name' => 'James — ชาย ทุ้ม มีพลัง', 'gender' => 'male'],
        ['id' => 'BZgkqPqms7Kj9ulSkVzn', 'name' => 'Eve — หญิง สดใส ร่าเริง', 'gender' => 'female'],
        ['id' => 'nPczCjzI2devNBz1zQrb', 'name' => 'Brian — ชาย ทุ้มลึก นุ่มนวล', 'gender' => 'male'],
        ['id' => '5l5f8iK3YPeGga21rQIX', 'name' => 'Adeline — หญิง คุยเป็นกันเอง', 'gender' => 'female'],
        ['id' => 'UgBBYS2sOqTuMpoF3BR0', 'name' => 'Mark — ชาย พูดเป็นธรรมชาติ', 'gender' => 'male'],
        ['id' => 'FGY2WhTYpPnrIDTdsKH5', 'name' => 'Laura — หญิง กระตือรือร้น', 'gender' => 'female'],
        ['id' => 'TX3LPaxmHKxFdv7VOQHJ', 'name' => 'Liam — ชาย สไตล์ครีเอเตอร์', 'gender' => 'male'],
        ['id' => 'kPzsL2i3teMYv0FxEYQ6', 'name' => 'Brittney — หญิง วัยรุ่น สนุก', 'gender' => 'female'],
        ['id' => 'DYkrAHD8iwork3YSUBbs', 'name' => 'Tom — ชาย เล่าเรื่อง หนังสือเสียง', 'gender' => 'male'],
        ['id' => '56AoDkrOh6qfVPDXZ7Pt', 'name' => 'Cassidy — หญิง ชัดถ้อยชัดคำ', 'gender' => 'female'],
        ['id' => 'x70vRnQBMBu4FAYhjJbO', 'name' => 'Nathan — ชาย ดีเจวิทยุ', 'gender' => 'male'],
        ['id' => 'Z3R5wn05IrDiVCyEkUrK', 'name' => 'Arabella — หญิง ลึกลับ มีอารมณ์', 'gender' => 'female'],
        ['id' => 'DGzg6RaUqxGRTHSBjfgF', 'name' => 'Brock — ชาย ดุดัน หนักแน่น', 'gender' => 'male'],
    ];

    /** สถานะที่จบแล้วของ aixman */
    protected const DONE = ['completed', 'failed', 'cancelled'];

    /** ไฟล์เสียงใหญ่สุดที่ยอมโหลด (กันโดนส่งไฟล์ยักษ์กลับมา) */
    protected const MAX_AUDIO_BYTES = 20 * 1024 * 1024;

    protected string $baseUrl;

    /**
     * @param  string|null  $apiKey  ส่งตรงได้ (เช่นหน้าแอดมินทดสอบ key ก่อนบันทึก) — null = อ่านจากค่ากลาง
     */
    public function __construct(
        protected FortuneTellingSetting $settings,
        protected ?string $apiKey = null,
        ?string $baseUrl = null,
    ) {
        $this->baseUrl = rtrim($baseUrl ?: (string) config('services.xdreamer.base_url', self::BASE_URL), '/');
    }

    public function name(): string
    {
        return 'xdreamer';
    }

    public function isAvailable(): bool
    {
        return $this->key() !== null;
    }

    /**
     * โมเดลเสียงที่ใช้ (แอดมินเปลี่ยนได้ ค่าเริ่มต้น ElevenLabs V3)
     */
    public function model(): string
    {
        $model = trim((string) ($this->settings->xdreamer_tts_model ?? ''));

        return $model !== '' ? $model : self::DEFAULT_MODEL;
    }

    /**
     * สังเคราะห์แบบรอจนเสร็จ — ใช้ใน provider chain เดิมของบอท (เสียงทำนาย/เสียงระบบ ที่รันใน job)
     *
     * options เพิ่มเติม: 'max_wait_seconds' (ค่าเริ่ม 120 — ProcessVoiceSummaryJob timeout 180 วิ ต้องเหลือเวลาให้ fallback)
     */
    public function synthesize(string $text, array $options = []): array
    {
        $startTime = microtime(true);
        // ไม่ได้ระบุเสียง = เสียงแม่หมอที่เลือกไว้ (บอทเดิมพูดในนามแม่หมอ)
        $voice = $options['voice'] ?? null;
        // chain เดิมส่ง voice override ของ provider อื่นมาด้วย (เช่น id ของ MiniMax) — id ของ ElevenLabs = ตัวอักษร 20 ตัว
        if (! is_string($voice) || ! preg_match('/^[A-Za-z0-9]{20}$/', $voice)) {
            $voice = ($this->settings->maemor_voice_id ?: null) ?? self::FALLBACK_VOICES[0]['id'];
        }
        $outputPath = $options['output_path'] ?? null;

        if (! $outputPath) {
            return $this->failResult($startTime, 'output_path required', $voice);
        }

        $order = $this->startSpeech($text, $voice);
        if (! $order['success']) {
            return $this->failResult($startTime, $order['error'], $voice);
        }

        $maxWait = (int) ($options['max_wait_seconds'] ?? 120);
        $deadline = microtime(true) + $maxWait;
        $state = $order;

        while (! in_array($state['status'], self::DONE, true)) {
            $remaining = (int) floor($deadline - microtime(true));
            if ($remaining <= 0) {
                return $this->failResult($startTime, 'X-DREAMER ยังสร้างเสียงไม่เสร็จภายใน '.$maxWait.' วินาที (งาน #'.$order['generation_id'].')', $voice);
            }

            $state = $this->getGeneration((int) $order['generation_id'], min(45, $remaining));
            if (! $state['success']) {
                return $this->failResult($startTime, $state['error'], $voice);
            }
        }

        if ($state['status'] !== 'completed' || empty($state['result_url'])) {
            return $this->failResult($startTime, 'X-DREAMER สร้างเสียงไม่สำเร็จ: '.($state['error'] ?? $state['status']), $voice);
        }

        $download = $this->downloadAudio($state['result_url'], $outputPath);
        if (! $download['success']) {
            return $this->failResult($startTime, $download['error'], $voice);
        }

        Log::info('🎭 X-DREAMER TTS สำเร็จ', [
            'model' => $this->model(),
            'voice' => $voice,
            'generation_id' => $order['generation_id'],
            'chars' => mb_strlen($text),
            'duration_ms' => (int) round((microtime(true) - $startTime) * 1000),
        ]);

        return [
            'success' => true,
            'audio_path' => $outputPath,
            'error' => null,
            'duration_ms' => (int) round((microtime(true) - $startTime) * 1000),
            'voice_used' => $voice,
            'audio_length_ms' => null,
            'usage_chars' => mb_strlen($text),
        ];
    }

    /**
     * สั่งสร้างเสียง (ไม่รอ) — คืน id งานไว้ poll ต่อ
     *
     * @return array{success: bool, generation_id: ?int, status: string, result_url: ?string, error: ?string, credits_used: ?int}
     */
    public function startSpeech(string $text, string $voice): array
    {
        $text = trim($text);
        if ($text === '') {
            return $this->orderFail('ข้อความว่าง');
        }
        if (mb_strlen($text) > self::MAX_CHARS) {
            $text = mb_substr($text, 0, self::MAX_CHARS);
        }

        $call = $this->callTool('generate_speech', [
            'text' => $text,
            'model' => $this->model(),
            'voice' => $voice,
        ], 60);

        if (! $call['success']) {
            return $this->orderFail($call['error']);
        }

        return $this->orderState($call['data']);
    }

    /**
     * ถามสถานะงาน — wait_seconds > 0 ให้ฝั่ง X-DREAMER รอให้ (สูงสุด 45 วิ)
     *
     * @return array{success: bool, generation_id: ?int, status: string, result_url: ?string, error: ?string, credits_used: ?int}
     */
    public function getGeneration(int $id, int $waitSeconds = 0): array
    {
        $wait = max(0, min(45, $waitSeconds));
        $call = $this->callTool('get_generation', ['id' => $id, 'wait_seconds' => $wait], $wait + 20);

        if (! $call['success']) {
            return $this->orderFail($call['error']);
        }

        return $this->orderState($call['data']);
    }

    /**
     * รายชื่อเสียงของโมเดลที่ใช้ — ดึงสดจาก list_models (ไม่ได้ = รายชื่อสำรองในไฟล์นี้)
     *
     * @return array{voices: array<int, array{id: string, name: string, gender: string}>, live: bool, max_chars: int}
     */
    public function listVoices(): array
    {
        $call = $this->callTool('list_models', ['kind' => 'speech'], 20);
        $wanted = strtolower($this->model());

        if ($call['success']) {
            foreach (($call['data']['models'] ?? []) as $m) {
                if (strtolower((string) ($m['id'] ?? '')) !== $wanted || empty($m['voices'])) {
                    continue;
                }

                $voices = [];
                foreach ($m['voices'] as $v) {
                    if (! is_array($v) || empty($v['id'])) {
                        continue;
                    }
                    $voices[] = [
                        'id' => (string) $v['id'],
                        'name' => (string) ($v['name'] ?? $v['id']),
                        'gender' => (string) ($v['gender'] ?? ''),
                    ];
                }

                if ($voices !== []) {
                    return ['voices' => $voices, 'live' => true, 'max_chars' => (int) ($m['max_characters'] ?? self::MAX_CHARS)];
                }
            }
        }

        return ['voices' => self::FALLBACK_VOICES, 'live' => false, 'max_chars' => self::MAX_CHARS];
    }

    /**
     * เครดิตคงเหลือในบัญชี X-DREAMER — ใช้ทดสอบ key ด้วย (ไม่เสียเครดิต)
     *
     * @return array{success: bool, balance: ?int, error: ?string}
     */
    public function credits(): array
    {
        $call = $this->callTool('get_credits', [], 20);

        if (! $call['success']) {
            return ['success' => false, 'balance' => null, 'error' => $call['error']];
        }

        return ['success' => true, 'balance' => (int) ($call['data']['balance'] ?? 0), 'error' => null];
    }

    /**
     * โหลดไฟล์เสียงผลลัพธ์ลงเครื่อง — รับเฉพาะ https และไม่ส่ง key ไปกับคำขอนี้ (ไฟล์อยู่บน storage สาธารณะ)
     *
     * @return array{success: bool, error: ?string, bytes: int}
     */
    public function downloadAudio(string $url, string $outputPath): array
    {
        if (! str_starts_with(strtolower($url), 'https://')) {
            return ['success' => false, 'error' => 'URL ไฟล์เสียงไม่ใช่ https', 'bytes' => 0];
        }

        try {
            $response = Http::connectTimeout(12)->timeout(60)->retry(2, 1500, fn ($e) => $e instanceof ConnectionException, throw: false)->get($url);
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'โหลดไฟล์เสียงไม่สำเร็จ: '.$this->scrub($e->getMessage()), 'bytes' => 0];
        }

        if (! $response->successful()) {
            return ['success' => false, 'error' => 'โหลดไฟล์เสียงไม่สำเร็จ (HTTP '.$response->status().')', 'bytes' => 0];
        }

        $body = $response->body();
        $bytes = strlen($body);
        if ($bytes < 100) {
            return ['success' => false, 'error' => 'ไฟล์เสียงว่างหรือเสีย', 'bytes' => $bytes];
        }
        if ($bytes > self::MAX_AUDIO_BYTES) {
            return ['success' => false, 'error' => 'ไฟล์เสียงใหญ่เกินกำหนด', 'bytes' => $bytes];
        }

        $dir = dirname($outputPath);
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        file_put_contents($outputPath, $body);

        return ['success' => true, 'error' => null, 'bytes' => $bytes];
    }

    /**
     * เรียก MCP tool หนึ่งตัว
     *
     * @return array{success: bool, data: array, error: ?string}
     */
    protected function callTool(string $tool, array $arguments, int $timeoutSeconds): array
    {
        $key = $this->key();
        if ($key === null) {
            return ['success' => false, 'data' => [], 'error' => 'ยังไม่ได้ตั้ง X-DREAMER API key'];
        }

        $payload = [
            'jsonrpc' => '2.0',
            'id' => random_int(1, PHP_INT_MAX),
            'method' => 'tools/call',
            'params' => ['name' => $tool, 'arguments' => (object) $arguments],
        ];

        try {
            $request = Http::withToken($key)
                ->acceptJson()
                ->connectTimeout(12)
                ->timeout($timeoutSeconds);

            // retry เฉพาะ tool ที่อ่านอย่างเดียว — generate_speech ห้าม retry เอง
            //   (timeout หลังคำขอถึงเซิร์ฟเวอร์แล้ว = สั่งซ้ำ = เสียเครดิตสองรอบ)
            if ($tool !== 'generate_speech') {
                $request = $request->retry(2, 2000, fn ($e) => $e instanceof ConnectionException, throw: false);
            }

            $response = $request->post($this->baseUrl.'/api/mcp', $payload);
        } catch (\Throwable $e) {
            $error = 'เชื่อมต่อ X-DREAMER ไม่ได้: '.$this->scrub($e->getMessage());
            Log::warning('X-DREAMER TTS: connection fail', ['tool' => $tool, 'error' => mb_substr($error, 0, 200)]);

            return ['success' => false, 'data' => [], 'error' => $error];
        }

        if ($response->status() === 401) {
            return ['success' => false, 'data' => [], 'error' => 'X-DREAMER ไม่รับ API key นี้ (key ผิดหรือถูกยกเลิก)'];
        }
        if ($response->status() === 429) {
            return ['success' => false, 'data' => [], 'error' => 'เรียก X-DREAMER ถี่เกินไป รอสักครู่แล้วลองใหม่'];
        }
        if (! $response->successful()) {
            Log::warning('X-DREAMER TTS: HTTP fail', ['tool' => $tool, 'status' => $response->status()]);

            return ['success' => false, 'data' => [], 'error' => 'X-DREAMER ตอบ HTTP '.$response->status()];
        }

        $json = $response->json();
        if (! is_array($json)) {
            return ['success' => false, 'data' => [], 'error' => 'X-DREAMER ตอบกลับไม่ใช่ JSON'];
        }
        if (isset($json['error'])) {
            return ['success' => false, 'data' => [], 'error' => 'X-DREAMER: '.$this->scrub((string) ($json['error']['message'] ?? 'error'))];
        }

        $result = $json['result'] ?? [];
        if (! empty($result['isError'])) {
            // ความผิดพลาดของ tool เป็นข้อความอ่านได้ (เช่น เครดิตไม่พอ / เสียงไม่รู้จัก)
            $text = (string) ($result['content'][0]['text'] ?? 'tool error');

            return ['success' => false, 'data' => [], 'error' => 'X-DREAMER: '.mb_substr($this->scrub($text), 0, 300)];
        }

        $data = $result['structuredContent'] ?? null;
        if (! is_array($data)) {
            $data = json_decode((string) ($result['content'][0]['text'] ?? ''), true);
        }

        return is_array($data)
            ? ['success' => true, 'data' => $data, 'error' => null]
            : ['success' => false, 'data' => [], 'error' => 'X-DREAMER ตอบกลับรูปแบบไม่รู้จัก'];
    }

    /**
     * แปลงผล generate_speech / get_generation เป็นรูปแบบเดียวกัน
     */
    protected function orderState(array $data): array
    {
        $urls = is_array($data['result_urls'] ?? null) ? $data['result_urls'] : [];
        $status = (string) ($data['status'] ?? 'pending');

        return [
            'success' => true,
            'generation_id' => isset($data['id']) ? (int) $data['id'] : null,
            'status' => $status,
            'result_url' => isset($urls[0]) ? (string) $urls[0] : null,
            'error' => isset($data['error']) && $data['error'] !== null ? $this->scrub((string) $data['error']) : null,
            'credits_used' => isset($data['credits_used']) ? (int) $data['credits_used'] : null,
        ];
    }

    protected function orderFail(?string $error): array
    {
        return [
            'success' => false,
            'generation_id' => null,
            'status' => 'failed',
            'result_url' => null,
            'error' => $error ?: 'unknown',
            'credits_used' => null,
        ];
    }

    protected function key(): ?string
    {
        $key = $this->apiKey !== null ? trim($this->apiKey) : $this->settings->getXdreamerApiKey();

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

        return (string) preg_replace('/axm-[A-Za-z0-9_\-]{4,}/', 'axm-[key]', $message);
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
