<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessFortuneDataDeletionJob;
use App\Models\FortuneDataDeletionRequest;
use App\Models\FortuneTellingSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * 📘 Facebook Data Deletion Callback — ตาม PDPA / Facebook Platform Policy
 *
 * https://developers.facebook.com/docs/development/create-an-app/app-dashboard/data-deletion-callback
 *
 * เมื่อผู้ใช้ถอนสิทธิ์แอป / ขอลบข้อมูลผ่าน Facebook → FB ยิง POST พร้อม `signed_request`
 * (เซ็นด้วย App Secret) มาที่ endpoint นี้ เราต้อง:
 *   1. verify signed_request ด้วย App Secret
 *   2. คิวงานลบข้อมูลจริง (async — FB ต้องได้ response เร็ว)
 *   3. ตอบ JSON { url, confirmation_code } เพื่อให้ผู้ใช้เปิดหน้าเช็คสถานะได้
 */
class FacebookDataDeletionController extends Controller
{
    /**
     * รับ callback จาก Facebook (POST signed_request)
     */
    public function handle(Request $request): JsonResponse
    {
        $signedRequest = (string) $request->input('signed_request', '');

        if ($signedRequest === '') {
            return response()->json(['error' => 'missing signed_request'], 400);
        }

        $data = $this->parseSignedRequest($signedRequest);
        if ($data === null || empty($data['user_id'])) {
            Log::warning('📘 FB Data Deletion: signed_request ไม่ผ่านการตรวจสอบ');

            return response()->json(['error' => 'invalid signed_request'], 400);
        }

        $psid = (string) $data['user_id'];
        $code = 'FBDEL-'.now()->format('ymd').'-'.Str::upper(Str::random(10));

        // บันทึกคำขอ (audit + หลังบ้านหน้าเช็คสถานะ)
        try {
            FortuneDataDeletionRequest::create([
                'confirmation_code' => $code,
                'platform' => 'facebook',
                'platform_user_id' => $psid,
                'source' => FortuneDataDeletionRequest::SOURCE_FACEBOOK_CALLBACK,
                'status' => FortuneDataDeletionRequest::STATUS_PENDING,
                'requested_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('📘 FB Data Deletion: create request row failed', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'internal error'], 500);
        }

        // ลบจริงเบื้องหลัง (FB ต้องได้ response ทันที)
        ProcessFortuneDataDeletionJob::dispatch($code, 'facebook', $psid);

        Log::info('📘 FB Data Deletion: รับคำขอ + คิวงานลบ', ['confirmation_code' => $code]);

        return response()->json([
            'url' => route('fortune.data-deletion.status', ['code' => $code]),
            'confirmation_code' => $code,
        ]);
    }

    /**
     * หน้าเช็คสถานะการลบ (ผู้ใช้เปิดจากลิงก์ที่ตอบกลับ FB)
     */
    public function status(Request $request, string $code)
    {
        $deletionRequest = FortuneDataDeletionRequest::where('confirmation_code', $code)->first();

        return response()
            ->view('fortune.data-deletion-status', [
                'code' => $code,
                'request' => $deletionRequest,
            ])
            ->header('X-Robots-Tag', 'noindex');
    }

    /**
     * ตรวจสอบ + ถอดรหัส signed_request จาก Facebook
     *
     * @return array<string,mixed>|null payload (null = ไม่ผ่านการตรวจสอบ)
     */
    protected function parseSignedRequest(string $signedRequest): ?array
    {
        if (! str_contains($signedRequest, '.')) {
            return null;
        }

        [$encodedSig, $payload] = explode('.', $signedRequest, 2);

        $sig = $this->base64UrlDecode($encodedSig);
        $dataJson = $this->base64UrlDecode($payload);
        if ($sig === false || $dataJson === false) {
            return null;
        }

        $data = json_decode($dataJson, true);
        if (! is_array($data)) {
            return null;
        }

        // อัลกอริทึมต้องเป็น HMAC-SHA256
        if (strtoupper((string) ($data['algorithm'] ?? '')) !== 'HMAC-SHA256') {
            return null;
        }

        $appSecret = (string) (FortuneTellingSetting::getSettings()->facebook_app_secret ?? '');
        if ($appSecret === '') {
            // ไม่มี secret → verify ไม่ได้. Production = ปฏิเสธ ; dev = อนุญาต (สะดวก dev)
            if (app()->environment('production')) {
                Log::error('📘 FB Data Deletion: ไม่มี facebook_app_secret — ปฏิเสธคำขอใน production');

                return null;
            }

            return $data;
        }

        // signed_request เซ็นเหนือ "encoded payload string" (ส่วนหลังจุด) — ไม่ใช่ JSON ที่ถอดแล้ว
        $expectedSig = hash_hmac('sha256', $payload, $appSecret, true);
        if (! hash_equals($expectedSig, $sig)) {
            return null;
        }

        return $data;
    }

    /**
     * base64url decode (Facebook ใช้ base64url ไม่มี padding)
     *
     * @return string|false
     */
    protected function base64UrlDecode(string $input)
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $input .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($input, '-_', '+/'), true);
    }
}
