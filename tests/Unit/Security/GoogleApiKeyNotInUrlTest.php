<?php

namespace Tests\Unit\Security;

use App\Models\FortuneTellingSetting;
use App\Services\Tts\GoogleCloudTtsProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * 🔐 Google API key ต้องไปทาง header x-goog-api-key ไม่ใช่ ?key= ใน URL (2026-09-27)
 *
 * ทำไม: error ของ Guzzle / Laravel Http พิมพ์ URL เต็มไว้ใน $e->getMessage() — คีย์ใน URL
 *   จึงหลุดไปทุกที่ที่ข้อความนั้นไปถึงนอก log processor (exception ที่ rethrow ดิบ, failed_jobs.exception,
 *   JSON ที่ตอบกลับ) ย้ายไป header แล้วคีย์ไม่อยู่ใน URL เลยตั้งแต่ต้น
 *
 * ยืนยันก่อนย้ายด้วยคีย์ปลอม (curl): generativelanguage / texttospeech / vision / youtube v3
 *   อ่าน header จริง ("API key not valid") · Maps web services ไม่อ่าน header → ยังใช้ key ใน query ตามเดิม
 *
 * ไม่แตะ DB
 */
class GoogleApiKeyNotInUrlTest extends TestCase
{
    /**
     * ไฟล์ที่ยังต้องใส่ key ใน URL เพราะปลายทางไม่รองรับ header
     *   RiderGpsTrackingService: URL ของ Maps Static API ที่เอาไปใส่ <img src> ให้เบราว์เซอร์โหลดเอง
     */
    private const ALLOWED = [
        'Services/RiderGpsTrackingService.php',
    ];

    /**
     * กันถอยหลัง: สตริงในโค้ดทั้ง app/ ห้ามมี ?key= / &key= (ไม่นับคอมเมนต์ — อ่านผ่าน tokenizer)
     */
    #[Test]
    public function app_source_has_no_key_query_string_literals(): void
    {
        $offenders = [];
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path(), RecursiveDirectoryIterator::SKIP_DOTS));

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen(app_path()) + 1));
            if (in_array($relative, self::ALLOWED, true)) {
                continue;
            }

            foreach (token_get_all(file_get_contents($file->getPathname())) as $token) {
                if (is_array($token)
                    && in_array($token[0], [T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE], true)
                    && preg_match('/[?&]key=/', $token[1])) {
                    $offenders[] = "app/{$relative}:{$token[2]}  {$token[1]}";
                }
            }
        }

        $this->assertSame([], $offenders, "ห้ามใส่ API key ใน query string — ใช้ ->withHeaders(['x-goog-api-key' => \$key]) แทน:\n".implode("\n", $offenders));
    }

    /**
     * พฤติกรรมจริง: Google TTS ส่งคีย์ทาง header และ URL ที่ยิงออกไปไม่มีคีย์
     */
    #[Test]
    public function google_tts_sends_the_key_in_the_header_not_the_url(): void
    {
        $key = 'fake-tts-key-'.str_repeat('q', 20);
        config(['services.google.tts_api_key' => $key]);
        Http::fake([
            'texttospeech.googleapis.com/*' => Http::response(['audioContent' => base64_encode('ID3-fake-mp3')], 200),
        ]);
        $output = sys_get_temp_dir().'/tts-'.uniqid().'.mp3';

        try {
            $result = (new GoogleCloudTtsProvider(new FortuneTellingSetting))->synthesize('ทดสอบเสียง', ['output_path' => $output]);
        } finally {
            @unlink($output);
        }

        $this->assertTrue($result['success'], (string) $result['error']);
        Http::assertSent(fn (Request $request) => $request->url() === 'https://texttospeech.googleapis.com/v1/text:synthesize'
            && $request->hasHeader('x-goog-api-key', $key));
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), $key));
    }
}
