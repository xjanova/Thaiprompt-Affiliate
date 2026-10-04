<?php

namespace Tests\Feature\Ekyc\Concerns;

use App\Models\Rider;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;

/**
 * ตัวช่วยของเทสต์ AI eKYC — ปลอมบริการ AI ด้วย Http::fake (ห้ามยิงของจริง)
 */
trait BuildsEkycFixtures
{
    /** เลขบัตรที่ผ่าน checksum จริง (เดียวกับ KycAutoCheckTest) */
    protected const VALID_ID = '1234567890121';

    /** เลขบัตรที่ checksum ผิด */
    protected const BAD_ID = '1234567890120';

    protected const AI_URL = 'http://ekyc-ai.test';

    protected const AI_KEY = 'test-ekyc-secret';

    protected function configureEkyc(): void
    {
        config([
            'ekyc.ai_url' => self::AI_URL,
            'ekyc.ai_key' => self::AI_KEY,
            'ekyc.enforce' => true,
            'ekyc.min_build' => 44,
            'ekyc.max_attempts_per_day' => 3,
            'ekyc.session_ttl_minutes' => 20,
            // prod ปิดด่านรูปถ่ายสดแล้ว — เทสต์ eKYC ไม่ให้ด่านนั้นมาบังผล
            'profile_photo.required' => false,
        ]);
    }

    /** ผลปลอมของ /v1/id-card (null = goodCard · Closure = ตัวตอบเอง) */
    protected array|\Closure|null $aiCard = null;

    /** ผลปลอมของ /v1/face/verify (null = goodFace · Closure = ตัวตอบเอง) */
    protected array|\Closure|null $aiFace = null;

    private bool $aiFaked = false;

    /**
     * ปลอมบริการ AI (ตอบผลบัตร/ใบหน้าตามที่กำหนด) — เรียกซ้ำเพื่อเปลี่ยนผลกลางเทสต์ได้ · คำขออื่นได้ 200 ว่าง
     *
     * @param  array<string, mixed>|\Closure|null  $card
     * @param  array<string, mixed>|\Closure|null  $face
     */
    protected function fakeAi(array|\Closure|null $card = null, array|\Closure|null $face = null): void
    {
        $this->aiCard = $card;
        $this->aiFace = $face;

        if ($this->aiFaked) {
            return;
        }
        $this->aiFaked = true;

        Http::fake(function (HttpRequest $request) {
            $url = $request->url();
            $spec = match (true) {
                str_ends_with($url, '/v1/id-card') => $this->aiCard ?? $this->goodCard(),
                str_ends_with($url, '/v1/face/verify') => $this->aiFace ?? $this->goodFace(),
                default => [],
            };

            return $spec instanceof \Closure ? $spec($request) : Http::response($spec, 200);
        });
    }

    /**
     * ผลอ่านบัตรที่ดีทุกข้อ
     *
     * @param  array<string, mixed>  $override
     * @return array<string, mixed>
     */
    protected function goodCard(array $override = [], array $fields = []): array
    {
        return array_replace_recursive([
            'ok' => true,
            'card_detected' => true,
            'quality' => ['blur' => 0.08, 'glare' => 0.05, 'complete' => true],
            'fields' => array_merge([
                'id_number' => self::VALID_ID,
                'name_th' => 'นาย ณัฐ ใจงาม',
                'name_en' => 'Mr. Nat Jaingam',
                'birth_date' => '1995-01-12',
                'expiry_date' => now()->addYears(5)->toDateString(),
                'issue_date' => now()->subYears(3)->toDateString(),
            ], $fields),
            'field_confidence' => ['id_number' => 0.99, 'name_th' => 0.97, 'birth_date' => 0.96, 'expiry_date' => 0.95],
            'ocr_confidence' => 0.98,
            'id_checksum_ok' => true,
            'card_face_found' => true,
            'card_real_score' => 0.93,
            'reasons' => [],
            'model_version' => 'easyocr-1.7.1+yunet-2023mar',
        ], $override);
    }

    /**
     * ผลตรวจใบหน้าที่ดีทุกข้อ
     *
     * @param  array<string, mixed>  $override
     * @return array<string, mixed>
     */
    protected function goodFace(array $override = []): array
    {
        $base = [
            'ok' => true,
            'faces_found' => 4,
            'same_person_across_frames' => true,
            'liveness' => [
                'passed' => true,
                'score' => 0.97,
                'challenges' => ['blink' => true, 'turn_left' => true, 'turn_right' => true, 'smile' => true, 'nod' => true],
            ],
            'anti_spoof' => ['real_score' => 0.95],
            'match' => ['cosine' => 0.62, 'score' => 0.86],
            'best_frame_index' => 0,
            'reasons' => [],
            'model_version' => 'sface-2021dec+minifasnet-v2',
        ];

        foreach ($override as $key => $value) {
            $base[$key] = is_array($value) && is_array($base[$key] ?? null) && ! array_is_list($value)
                ? array_replace($base[$key], $value)
                : $value;
        }

        return $base;
    }

    /**
     * JPEG ลายไล่สี
     */
    protected function jpeg(int $width = 1000, int $height = 640): string
    {
        $img = imagecreatetruecolor($width, $height);
        for ($y = 0; $y < $height; $y += 8) {
            $color = imagecolorallocate($img, (int) (40 + ($y / max(1, $height)) * 120), 110, 150);
            imagefilledrectangle($img, 0, $y, $width, $y + 7, $color);
        }

        ob_start();
        imagejpeg($img, null, 90);

        return (string) ob_get_clean();
    }

    protected function cardFile(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('card.jpg', $this->jpeg(1000, 640));
    }

    protected function frameFile(int $i = 0): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('frame'.$i.'.jpg', $this->jpeg(480, 640));
    }

    /**
     * ผู้ใช้ใหม่ที่ล็อกอินผ่าน Sanctum แล้ว
     */
    protected function ekycUser(array $attributes = []): User
    {
        $user = User::factory()->create(array_merge(['name' => 'ณัฐ ใจงาม'], $attributes));
        Sanctum::actingAs($user);

        return $user;
    }

    protected function startSession(): TestResponse
    {
        return $this->postJson('/api/v1/ekyc/sessions', ['consent' => true, 'consent_version' => '2026-10-04']);
    }

    /**
     * เริ่มรอบ → คืน [session_id, challenges]
     *
     * @return array{0: string, 1: array<int, string>}
     */
    protected function openSession(): array
    {
        $res = $this->startSession()->assertCreated();

        return [(string) $res->json('data.session_id'), (array) $res->json('data.challenges')];
    }

    protected function postCard(string $sessionId): TestResponse
    {
        return $this->post('/api/v1/ekyc/sessions/'.$sessionId.'/id-card', ['image' => $this->cardFile()], ['Accept' => 'application/json']);
    }

    /**
     * ส่งเฟรมใบหน้าตามป้ายที่กำหนด (ค่าเริ่มต้น = neutral + คำสั่งของรอบ)
     *
     * @param  array<int, string>  $labels
     */
    protected function postFace(string $sessionId, array $labels): TestResponse
    {
        $frames = [];
        foreach ($labels as $i => $label) {
            $frames[] = $this->frameFile($i);
        }

        return $this->post('/api/v1/ekyc/sessions/'.$sessionId.'/face', [
            'frames' => $frames,
            'labels' => $labels,
        ], ['Accept' => 'application/json']);
    }

    /**
     * ทำครบทั้งรอบ (เริ่ม → บัตร → ใบหน้า) แล้วคืนคำตอบของขั้นใบหน้า
     */
    protected function runFullFlow(): TestResponse
    {
        [$sid, $challenges] = $this->openSession();
        $this->postCard($sid)->assertOk();

        return $this->postFace($sid, array_merge(['neutral'], $challenges));
    }

    protected function makeRider(?User $user = null): Rider
    {
        $user ??= User::factory()->create();

        $rider = new Rider;
        $rider->forceFill([
            'user_id' => $user->id,
            'full_name' => 'ไรเดอร์ '.$user->id,
            'phone' => '08'.str_pad((string) $user->id, 8, '0', STR_PAD_LEFT),
            'status' => 'approved',
            'availability' => 'offline',
            'rider_type' => 'delivery',
            'vehicle_type' => 'motorcycle',
            'gps_permission_granted' => true,
            'last_latitude' => 13.73,
            'last_longitude' => 100.52,
            'last_location_update' => now(),
            'share_location_consent_at' => now(),
            'approved_at' => now(),
        ])->save();

        app(WalletService::class)->getOrCreateWallet($user);

        return $rider->fresh();
    }
}
