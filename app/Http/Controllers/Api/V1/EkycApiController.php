<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\SellerAppResponses;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Ekyc\EkycException;
use App\Services\Ekyc\EkycService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * 🪪 AI eKYC สำหรับแอป (2026-10-04) — รูปแบบคำตอบ SellerAppResponses {success, data, message} / {success:false, code, message}
 *
 * - GET   /ekyc/status
 * - POST  /ekyc/sessions                    {consent: true, consent_version}
 * - POST  /ekyc/sessions/{id}/id-card       multipart image
 * - PATCH /ekyc/sessions/{id}/id-card       {name_th?, birth_date?}
 * - POST  /ekyc/sessions/{id}/face          multipart frames[] + labels[]
 *
 * ทุกเส้นอยู่ใต้ auth:sanctum · รอบของคนอื่น = 404 · ห้ามคืน path ไฟล์/เลขบัตรเต็ม
 */
class EkycApiController extends Controller
{
    use SellerAppResponses;

    public function __construct(private readonly EkycService $ekyc) {}

    /**
     * GET /ekyc/status
     */
    public function status(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->ok($this->ekyc->status($user));
    }

    /**
     * POST /ekyc/sessions → { session_id, challenges[3], expires_at, attempts_left }
     */
    public function start(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->run(fn () => $this->ok(
            $this->ekyc->startSession($user, $request->input('consent'), $request->input('consent_version')),
            'เริ่มยืนยันตัวตนแล้ว',
            201
        ));
    }

    /**
     * POST /ekyc/sessions/{id}/id-card (multipart image)
     */
    public function idCard(Request $request, string $id): JsonResponse
    {
        $bad = $this->imageErrors($request, 'image');
        if ($bad !== null) {
            return $bad;
        }

        /** @var User $user */
        $user = $request->user();

        return $this->run(function () use ($user, $id, $request) {
            $result = $this->ekyc->submitIdCard($user, $id, $request->file('image'));

            return $this->ok($result, $result['status'] === 'retake' ? 'กรุณาถ่ายรูปบัตรใหม่อีกครั้ง' : 'อ่านข้อมูลบัตรแล้ว');
        });
    }

    /**
     * PATCH /ekyc/sessions/{id}/id-card {name_th?, birth_date?}
     */
    public function correctIdCard(Request $request, string $id): JsonResponse
    {
        $data = $this->validateOrFail($request, [
            'name_th' => ['nullable', 'string', 'min:2', 'max:120', 'regex:/^[\p{Thai}\s.]+$/u'],
            'birth_date' => ['nullable', 'date_format:Y-m-d', 'after:1900-01-01', 'before:today'],
        ], [
            'name_th.min' => 'กรุณากรอกชื่อ-นามสกุลภาษาไทยให้ครบ',
            'name_th.max' => 'ชื่อยาวเกินไป',
            'name_th.regex' => 'กรุณากรอกชื่อ-นามสกุลเป็นภาษาไทยตามบัตร',
            'birth_date.date_format' => 'รูปแบบวันเกิดไม่ถูกต้อง',
            'birth_date.after' => 'วันเกิดไม่ถูกต้อง',
            'birth_date.before' => 'วันเกิดไม่ถูกต้อง',
        ]);
        if ($data instanceof JsonResponse) {
            return $data;
        }

        if (($data['name_th'] ?? null) === null && ($data['birth_date'] ?? null) === null) {
            return $this->fail('VALIDATION_ERROR', 'กรุณาระบุข้อมูลที่ต้องการแก้ไข', 422);
        }

        /** @var User $user */
        $user = $request->user();

        return $this->run(fn () => $this->ok($this->ekyc->correctIdCard($user, $id, $data), 'บันทึกการแก้ไขแล้ว'));
    }

    /**
     * POST /ekyc/sessions/{id}/face (multipart frames[] + labels[])
     */
    public function face(Request $request, string $id): JsonResponse
    {
        $maxFrames = max(2, (int) config('ekyc.max_frames', 8));

        $labels = $request->input('labels');
        if (! is_array($labels) || $labels === [] || count($labels) > $maxFrames) {
            return $this->fail('EKYC_CHALLENGE_MISMATCH', 'ลำดับท่าทางไม่ตรงกับที่ระบบให้ทำ กรุณาเริ่มสแกนใบหน้าใหม่', 422);
        }

        $frames = $request->file('frames');
        if (! is_array($frames) || count($frames) < 2 || count($frames) > $maxFrames) {
            return $this->fail('EKYC_BAD_IMAGE', 'ส่งรูปใบหน้าไม่ครบ กรุณาสแกนใบหน้าใหม่', 422);
        }

        $bad = $this->imageErrors($request, 'frames.*');
        if ($bad !== null) {
            return $bad;
        }

        /** @var User $user */
        $user = $request->user();

        return $this->run(function () use ($user, $id, $frames, $labels) {
            $result = $this->ekyc->submitFace($user, $id, array_values($frames), array_values($labels));

            $message = match ($result['decision']) {
                'approved' => 'ยืนยันตัวตนสำเร็จ',
                'review' => 'ส่งข้อมูลให้เจ้าหน้าที่ตรวจแล้ว จะแจ้งผลให้ทราบโดยเร็ว',
                default => 'ยังยืนยันไม่ผ่าน กรุณาลองใหม่อีกครั้ง',
            };

            return $this->ok($result, $message);
        });
    }

    /**
     * ตรวจไฟล์รูป (ชนิด/ขนาด) → EKYC_BAD_IMAGE ภาษาไทย · ผ่าน = null
     */
    private function imageErrors(Request $request, string $field): ?JsonResponse
    {
        $maxKb = (int) config('ekyc.max_image_kb', 8192);

        $validator = Validator::make($request->all(), [
            $field => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', 'max:'.$maxKb],
        ]);

        if (! $validator->fails()) {
            return null;
        }

        $failed = $validator->failed();
        $rules = array_map('strtolower', array_keys((array) reset($failed)));

        $message = match (true) {
            in_array('required', $rules, true), in_array('file', $rules, true) => 'กรุณาถ่ายรูปใหม่อีกครั้ง',
            in_array('max', $rules, true) => 'รูปมีขนาดใหญ่เกิน 8 MB',
            default => 'รองรับเฉพาะรูป JPG, PNG หรือ WebP',
        };

        return $this->fail('EKYC_BAD_IMAGE', $message, 422);
    }

    /**
     * รันขั้นตอน — แปลง EkycException เป็นคำตอบ · error อื่น = ข้อความไทยกลางๆ (ห้ามส่งข้อความ exception ดิบ)
     */
    private function run(callable $callback): JsonResponse
    {
        try {
            return $callback();
        } catch (EkycException $e) {
            return $this->fail($e->errorCode, $e->getMessage(), $e->status, $e->data);
        } catch (\Throwable $e) {
            Log::error('EkycApi: failed', ['error' => class_basename($e)]);

            return $this->fail('EKYC_ERROR', 'ระบบยืนยันตัวตนขัดข้องชั่วคราว กรุณาลองใหม่อีกครั้ง', 500);
        }
    }
}
