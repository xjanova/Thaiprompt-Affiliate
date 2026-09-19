<?php

namespace Tests\Feature;

use App\Models\AiGenProvider;
use App\Services\AiGen\CloudflareAiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * 🖼️ Cloudflare Workers AI — payload ต้องตรงกับ schema ของแต่ละโมเดล (2026-09-19)
 *
 * Cloudflare รัดกุม schema ขาเข้าแล้ว: ส่งคีย์ที่โมเดลไม่รู้จักไป **ตีกลับทั้งคำขอ**
 *   "Bad input: Additional or unevaluated properties '/num_steps, /width, /height, /seed' not allowed"
 * ไม่ใช่เมินคีย์นั้นทิ้งแบบ API ทั่วไป ⇒ โค้ดที่ส่งเผื่อไว้ = ตาย 400 ทุกครั้ง
 *
 * ยิงกับ API จริงแล้ว (2026-09-19) ชุดที่ผ่าน:
 *   flux-1-schnell        {prompt, steps}                                   → 200 JSON base64
 *   stable-diffusion-xl-* {prompt, num_steps, width, height, seed, negative} → 200 image/png
 */
class CloudflareImagePayloadTest extends TestCase
{
    use RefreshDatabase;

    private function provider(): CloudflareAiProvider
    {
        $model = AiGenProvider::create([
            'name' => 'Cloudflare AI',
            'slug' => 'cloudflare-ai',
            'type' => 'image',
            'is_active' => true,
        ]);

        // getConfig() อ่านจาก DB ก่อน → ไม่มีก็ตกไป config/services.php
        config([
            'services.cloudflare.api_token' => 'test-token',
            'services.cloudflare.account_id' => 'test-account',
        ]);

        return new CloudflareAiProvider($model);
    }

    /** @return array<string, mixed> body ที่ถูกยิงออกไปจริง */
    private function sentBody(): array
    {
        $sent = [];
        Http::assertSent(function ($request) use (&$sent) {
            $sent = $request->data();

            return true;
        });

        return $sent;
    }

    #[Test]
    public function flux_sends_only_prompt_and_steps(): void
    {
        Http::fake(['api.cloudflare.com/*' => Http::response(['result' => ['image' => base64_encode('x')]], 200)]);

        $this->provider()->generateImage('lotus at night', [
            'model' => 'flux-1-schnell',
            'size' => '1024x1024',
            'seed' => 12345,
            'steps' => 4,
            'negative_prompt' => 'text, watermark',
        ]);

        $body = $this->sentBody();

        $this->assertArrayHasKey('prompt', $body);
        $this->assertSame(4, $body['steps'] ?? null);

        // คีย์พวกนี้คือตัวที่ Cloudflare ตีกลับ — ห้ามหลุดไปแม้ผู้เรียกจะส่งมา
        foreach (['num_steps', 'width', 'height', 'seed', 'negative_prompt'] as $forbidden) {
            $this->assertArrayNotHasKey($forbidden, $body, "flux ห้ามส่ง {$forbidden}");
        }
    }

    #[Test]
    public function flux_clamps_steps_into_the_one_to_eight_range(): void
    {
        Http::fake(['api.cloudflare.com/*' => Http::response(['result' => ['image' => base64_encode('x')]], 200)]);

        // steps นอกช่วง 1-8 ก็ 400 เหมือนกัน — ต้องหนีบก่อนส่ง
        $this->provider()->generateImage('lotus', ['model' => 'flux-1-schnell', 'steps' => 50]);

        $this->assertSame(8, $this->sentBody()['steps'] ?? null);
    }

    #[Test]
    public function sdxl_still_gets_the_full_parameter_set(): void
    {
        Http::fake(['api.cloudflare.com/*' => Http::response('binary-png', 200, ['content-type' => 'image/png'])]);

        $this->provider()->generateImage('lotus at night', [
            'model' => 'sdxl-lightning',
            'size' => '1024x1024',
            'seed' => 12345,
            'steps' => 4,
            'negative_prompt' => 'text, watermark',
        ]);

        $body = $this->sentBody();

        $this->assertSame(4, $body['num_steps'] ?? null);
        $this->assertSame(1024, $body['width'] ?? null);
        $this->assertSame(1024, $body['height'] ?? null);
        $this->assertSame(12345, $body['seed'] ?? null);
        $this->assertSame('text, watermark', $body['negative_prompt'] ?? null);
        $this->assertArrayNotHasKey('steps', $body, 'SDXL ใช้ num_steps ไม่ใช่ steps');
    }

    #[Test]
    public function an_unknown_model_falls_back_to_flux_and_its_payload_rules(): void
    {
        Http::fake(['api.cloudflare.com/*' => Http::response(['result' => ['image' => base64_encode('x')]], 200)]);

        // แคมเปญเก่าเก็บชื่อโมเดลของ Pollinations ไว้ ('flux') — ต้องไม่ทำให้ payload เพี้ยน
        $this->provider()->generateImage('lotus', ['model' => 'flux', 'seed' => 999]);

        $body = $this->sentBody();

        $this->assertArrayHasKey('steps', $body);
        $this->assertArrayNotHasKey('seed', $body);
    }
}
