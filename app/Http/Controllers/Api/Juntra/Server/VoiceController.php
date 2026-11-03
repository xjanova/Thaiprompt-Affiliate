<?php

namespace App\Http\Controllers\Api\Juntra\Server;

use App\Http\Controllers\Controller;
use App\Models\FortuneCharacterVoiceClip;
use App\Services\Fortune\FortuneCharacterVoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 🎭 (2026-11-03) เสียงพากย์ข้อความแม่หมอ/บุษบา ให้ห้องแชทเว็บ จันทรา.online
 *
 * on-demand + cache: เว็บขอเฉพาะตอนลูกค้ากดฟัง — ข้อความเดิม = ไฟล์เดิม (ไม่เสียเครดิตซ้ำ)
 *
 *   POST /api/v1/juntra/server/voice/speak   {persona: maemor|busaba|kunsai, text, wait?: 0-20}
 *        200 {data: {status: completed, url}} · 202 {data: {status: pending, clip_id}} → poll ต่อ
 *   GET  /api/v1/juntra/server/voice/clips/{clip}?wait=0-20
 *
 * ⚠️ ไม่ได้แตะ JuntraChatService — endpoint แยกตามที่ตกลงกับ session ย้ายแชท
 */
class VoiceController extends Controller
{
    /** ข้อความที่ส่งต่อให้ลูกค้าเห็นได้ — สาเหตุจริง (เช่น เครดิต X-DREAMER หมด) เก็บไว้ในแอดมิน */
    protected const PUBLIC_ERROR = 'สร้างเสียงไม่สำเร็จ ลองใหม่อีกครั้งภายหลังนะคะ';

    public function speak(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'persona' => 'required|in:'.implode(',', array_keys(FortuneCharacterVoiceService::PERSONAS)),
            'text' => 'required|string|max:6000',
            'wait' => 'nullable|integer|min:0|max:20',
        ]);

        // ⚠️ ห้าม inject ผ่าน constructor — container จะสร้าง FortuneTellingSetting ว่างๆ ให้แทนค่ากลาง
        $result = (new FortuneCharacterVoiceService)->request(
            $validated['persona'],
            $validated['text'],
            'chat',
            null,
            (int) ($validated['wait'] ?? 8)
        );

        $clip = $result['clip'];
        $data = $clip?->toPublicArray();
        $error = $result['error'];

        // 502 = ฝั่ง X-DREAMER ล้ม → ไม่ส่งสาเหตุดิบออกไป (429 โควต้า/503 ยังไม่ตั้งค่า ส่งตามจริง)
        if (! $result['success'] && $result['code'] === 502) {
            $error = self::PUBLIC_ERROR;
            if ($data) {
                $data['error'] = self::PUBLIC_ERROR;
            }
        }

        return response()->json([
            'success' => $result['success'],
            'data' => $data,
            'error' => $error,
        ], $result['success'] ? ($clip?->isCompleted() ? 200 : 202) : $result['code']);
    }

    public function show(Request $request, FortuneCharacterVoiceClip $clip): JsonResponse
    {
        $wait = max(0, min(20, (int) $request->query('wait', 0)));
        $clip = (new FortuneCharacterVoiceService)->refresh($clip, $wait);
        $failed = $clip->status === FortuneCharacterVoiceClip::STATUS_FAILED;
        $data = $clip->toPublicArray();
        if ($failed) {
            $data['error'] = self::PUBLIC_ERROR;
        }

        return response()->json([
            'success' => ! $failed,
            'data' => $data,
            'error' => $failed ? self::PUBLIC_ERROR : null,
        ], $failed ? 502 : ($clip->isCompleted() ? 200 : 202));
    }
}
