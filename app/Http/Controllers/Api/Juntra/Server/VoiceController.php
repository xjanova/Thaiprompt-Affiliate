<?php

namespace App\Http\Controllers\Api\Juntra\Server;

use App\Http\Controllers\Controller;
use App\Models\FortuneCharacterVoiceClip;
use App\Models\FortuneTellingSetting;
use App\Services\Fortune\FortuneCharacterVoiceService;
use App\Services\Fortune\FortuneFreeVoiceService;
use App\Services\Fortune\FortuneVoiceTranscribeService;
use App\Services\Fortune\JuntraChatService;
use App\Services\Fortune\JuntraNarrationService;
use App\Services\Fortune\JuntraVoiceLibrary;
use App\Services\Tts\GoogleCloudTtsProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 🎭 (2026-11-03) เสียงพากย์ข้อความแม่หมอ/บุษบา ให้ห้องแชทเว็บ จันทรา.online
 *
 * on-demand + cache: เว็บขอเฉพาะตอนลูกค้ากดฟัง — ข้อความเดิม = ไฟล์เดิม (ไม่เสียโควต้าซ้ำ)
 *
 *   POST /api/v1/juntra/server/voice/speak   {persona: maemor|busaba|kunsai, text, wait?: 0-20}
 *        200 {data: {status: completed, url}} · 202 {data: {status: pending, clip_id}} (อีกคำขอกำลังสร้างอยู่) → poll ต่อ
 *   GET  /api/v1/juntra/server/voice/clips/{clip}?wait=0-20
 *   🔈 (2026-11-04) โหมดคุยด้วยเสียง (+฿100):
 *   POST /api/v1/juntra/server/voice/transcribe  multipart {audio, user_ref, persona?} → ข้อความ + เวลาของแต่ละคำ
 *   GET  /api/v1/juntra/server/voice/health      → TTS/STT พร้อมไหม + โควต้าวันนี้เหลือเท่าไร
 *   POST /api/v1/juntra/server/voice/narration   → บทเล่าคำทำนายของโรงละคร (ผ่านตัวตรวจความตรงแล้ว)
 *   POST /api/v1/juntra/server/voice/free-speak  → เสียงอ่านคำทำนายฟรี (Google Cloud → gTTS → ไม่มีเสียง) ไม่ใช้ ElevenLabs
 *
 * ⚠️ ไม่ได้แตะ JuntraChatService — endpoint แยกตามที่ตกลงกับ session ย้ายแชท
 */
class VoiceController extends Controller
{
    /** ข้อความที่ส่งต่อให้ลูกค้าเห็นได้ — สาเหตุจริง (เช่น โควต้า ElevenLabs หมด) เก็บไว้ในแอดมิน */
    protected const PUBLIC_ERROR = 'สร้างเสียงไม่สำเร็จ ลองใหม่อีกครั้งภายหลังนะคะ';

    /** ไฟล์ไมค์ใหญ่สุด (KB) — เว็บตัดที่ 30 วิ · opus ~64kbps = ~240 KB เผื่อเครื่องที่อัดเป็น m4a */
    protected const MAX_AUDIO_KB = 768;

    public function speak(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'persona' => 'required|in:'.implode(',', array_keys(FortuneCharacterVoiceService::personas())),
            'text' => 'required|string|max:6000',
            'wait' => 'nullable|integer|min:0|max:20',
            // 🔈 (2026-11-04) ไม่ส่ง = chat (เว็บรุ่นเดิม) · ทุกตัวนับเพดานรายวัน
            'purpose' => 'sometimes|nullable|in:'.implode(',', FortuneCharacterVoiceService::METERED_PURPOSES),
            // ลูกค้าที่ขอ — ใช้แยกถัง throttle ต่อคน + บันทึกต้นทุน
            'user_ref' => 'sometimes|nullable|string|max:64|regex:/^[A-Za-z0-9_-]+$/',
        ]);

        // ⚠️ ห้าม inject ผ่าน constructor — container จะสร้าง FortuneTellingSetting ว่างๆ ให้แทนค่ากลาง
        $result = (new FortuneCharacterVoiceService)->request(
            $validated['persona'],
            $validated['text'],
            $validated['purpose'] ?? 'chat',
            null,
            (int) ($validated['wait'] ?? 8),
            $validated['user_ref'] ?? null,
        );

        $clip = $result['clip'];
        $data = $clip?->toPublicArray();
        $error = $result['error'];

        // 502 = ฝั่ง ElevenLabs ล้ม → ไม่ส่งสาเหตุดิบออกไป (429 โควต้า/503 ยังไม่ตั้งค่า ส่งตามจริง)
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

    /**
     * 🎙️ (2026-11-04) ถอดเสียงไมค์ของลูกค้า — ทางสำรองเมื่อเบราว์เซอร์ถอดเองไม่ได้ (iOS ใน FB/LINE ฯลฯ)
     *
     * multipart: audio (≤ 768 KB ≈ 30 วิของ opus ที่เบราว์เซอร์อัด) · user_ref · persona?
     * 200 {data: {text, words:[{text,start,end}], language, duration, provider}}
     * 422 ไฟล์ผิด · 429 ครบเพดานรายวัน · 503 ยังไม่ตั้ง key · 502 ถอดไม่สำเร็จ (ไม่ส่งสาเหตุดิบ)
     */
    public function transcribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'audio' => 'required|file|max:'.self::MAX_AUDIO_KB
                .'|mimetypes:audio/webm,video/webm,audio/ogg,application/ogg,audio/mp4,video/mp4,audio/x-m4a,audio/aac,audio/mpeg,audio/wav,audio/x-wav,audio/wave',
            'user_ref' => 'required|string|max:64|regex:/^[A-Za-z0-9_-]+$/',
            'persona' => 'sometimes|nullable|in:'.implode(',', array_keys(FortuneCharacterVoiceService::PERSONAS)),
        ]);

        $file = $validated['audio'];
        $result = (new FortuneVoiceTranscribeService)->transcribe(
            $file->getRealPath(),
            $file->getMimeType(),
            $validated['persona'] ?? null,
            $validated['user_ref'],
        );

        return response()->json([
            'success' => $result['success'],
            'data' => $result['data'],
            'error' => $result['code'] === 502 ? 'ถอดเสียงไม่สำเร็จ ลองพูดใหม่หรือพิมพ์แทนนะคะ' : $result['error'],
        ], $result['code']);
    }

    /**
     * 🔈 (2026-11-04) ระบบเสียงพร้อมขายไหม — เว็บซ่อนปุ่มซื้อเสียง (+฿100) เมื่อ TTS ใช้ไม่ได้หรือโควต้าวันนี้ใกล้หมด
     * ไม่ยิงไปหา ElevenLabs (เช็คแค่ว่ามี key) — เรียกได้บ่อย
     */
    /**
     * 🎭 (2026-11-04) คลังคลิปของตัวละคร — เฉพาะบทที่เปิดใช้และสร้างไฟล์แล้ว
     *
     * GET /api/v1/juntra/server/voice/library?persona=busaba
     * 200 {data: {persona, clips: {ack_1: {text, category, url}, …}}} — ส่ง id เหล่านี้กลับมาเป็น voice_clips ของ /chat/send ได้เลย
     */
    public function library(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'persona' => 'required|string|in:'.implode(',', array_keys(FortuneCharacterVoiceService::personas())),
        ]);

        return response()->json(['success' => true, 'data' => [
            'persona' => $validated['persona'],
            'clips' => JuntraVoiceLibrary::playable($validated['persona']),
        ]]);
    }

    public function health(): JsonResponse
    {
        $tts = new FortuneCharacterVoiceService;
        $stt = new FortuneVoiceTranscribeService;
        $ttsLimit = $tts->dailyCharLimit();
        $ttsUsed = $tts->charsUsedToday();
        $sttLimit = $stt->dailySecondsLimit();
        $sttUsed = $stt->secondsUsedToday();
        $free = new FortuneFreeVoiceService;
        $freeUsed = $free->googleCharsThisMonth();

        return response()->json(['success' => true, 'data' => [
            'tts' => [
                'available' => $tts->provider()->isAvailable(),
                'provider' => 'elevenlabs',
                'chars_used_today' => $ttsUsed,
                'chars_limit' => $ttsLimit,
                'chars_left' => max(0, $ttsLimit - $ttsUsed),
            ],
            'stt' => [
                'available' => $stt->provider()->isAvailable(),
                'provider' => $stt->provider()->name(),
                'seconds_used_today' => $sttUsed,
                'seconds_limit' => $sttLimit,
                'seconds_left' => max(0, $sttLimit - $sttUsed),
            ],
            // 🎬 เสียงอ่านคำทำนายฟรี — google = มีกุญแจ + ยังไม่ถึงเพดานเดือน · ไม่งั้น gTTS
            'free' => [
                'provider' => $free->monthlyCap() > $freeUsed && (new GoogleCloudTtsProvider(FortuneTellingSetting::getGlobalSettings()))->isAvailable() ? 'google_tts' : 'gtts',
                'google_chars_this_month' => $freeUsed,
                'google_chars_limit' => $free->monthlyCap(),
            ],
        ]]);
    }

    /**
     * 🎬 (2026-11-04) บทเล่าคำทำนายของโรงละคร — สัญญาข้อ 23.1
     *
     * {persona, spread, reading_text, cards:[{position, name_th, reversed}], birth_known, budget_chars?, user_ref}
     * 200 {data: {script_hash, chapters:[{anchor, kind, tone, energy, say}], fidelity:{passed, rewritten, extractive}}}
     * AI ล่มก็ยังได้ 200 — บทเป็นประโยคจากคำทำนายเอง (ไม่สร้างเสียงเอง เว็บเรียก /speak ทีละบท)
     */
    public function narration(Request $request, JuntraNarrationService $narration): JsonResponse
    {
        $validated = $request->validate([
            'persona' => 'required|in:'.implode(',', JuntraChatService::PERSONAS),
            'spread' => 'required|string|max:20|regex:/^[a-z_]+$/',
            'reading_text' => 'required|string|min:20|max:20000',
            'cards' => 'sometimes|array|max:20',
            'cards.*.position' => 'required|integer|min:1|max:20',
            'cards.*.name_th' => 'nullable|string|max:60',
            'cards.*.reversed' => 'sometimes|boolean',
            'birth_known' => 'sometimes|boolean',
            'budget_chars' => 'sometimes|nullable|integer|min:100|max:2000',
            'user_ref' => 'required|string|max:64|regex:/^[A-Za-z0-9_-]+$/',
        ]);

        $data = $narration->narrate(
            $validated['persona'],
            $validated['spread'],
            $validated['reading_text'],
            $validated['cards'] ?? [],
            (bool) ($validated['birth_known'] ?? false),
            isset($validated['budget_chars']) ? (int) $validated['budget_chars'] : null,
            $validated['user_ref'],
        );

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * 🎬 (2026-11-04) เสียงอ่านคำทำนายฟรี ทีละบท — ลูกค้าที่ไม่ได้ซื้อเสียงสมจริง
     *
     * {persona, text (บทจาก /narration มีแท็กได้ — ลอกให้), user_ref}
     * 200 {data: {status: completed, url, provider: google_tts|gtts}} · {status: none} = ทุกทางล้ม → เว็บเดินบทเงียบ
     */
    public function freeSpeak(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'persona' => 'required|in:'.implode(',', JuntraChatService::PERSONAS),
            'text' => 'required|string|max:1200',
            'user_ref' => 'required|string|max:64|regex:/^[A-Za-z0-9_-]+$/',
        ]);

        $result = (new FortuneFreeVoiceService)->speak($validated['persona'], $validated['text'], $validated['user_ref']);

        return response()->json(['success' => $result['status'] === 'completed', 'data' => $result]);
    }
}
