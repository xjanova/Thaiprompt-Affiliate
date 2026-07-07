<?php

namespace App\Services\Fortune;

use App\Models\FortuneDataDeletionRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * 🗑️ Trait: PDPA "ลบข้อมูลของฉัน" — flow ยืนยัน 2 ขั้นในแชท (2026-07-07)
 *
 * ใช้กับ FortuneConversationService — ลูกค้าพิมพ์ "ลบข้อมูลของฉัน" เพื่อใช้สิทธิ์ PDPA
 *
 * Flow:
 *   1. ลูกค้าพิมพ์ "ลบข้อมูลของฉัน" → บอทเตือน (คำทำนาย+บทสนทนาถูกลบ / สิทธิ์เงินปันผล-
 *      ส่วนแบ่ง-สายงานยุติ นับจากวันแจ้งลบ / กู้คืนไม่ได้) + ปุ่มยืนยัน/ยกเลิก → ตั้ง Cache pending
 *   2. ลูกค้ากด "ยืนยันลบถาวร" → ลบจริงผ่าน FortunePdpaDeletionService → แจ้งผล
 *      ลูกค้ากด "ไม่ลบ/ยกเลิก" → ล้าง pending → แจ้ง "ยกเลิกแล้ว"
 *
 * ⚠️ วางไว้ต้น processMessage (หลัง in-prediction guard) — กันไม่ให้ guard อื่น
 *    (silence/cancel-dialog/smart-skip) กลืนคำว่า "ยืนยันลบ" ก่อนถึง handler นี้
 */
trait FortunePdpaDeletionTrait
{
    /** Cache prefix — มีคำขอลบค้างรอยืนยัน (เก็บ ref) */
    protected const PDPA_PENDING_PREFIX = 'fortune:pdpa_pending:';

    /** TTL (วินาที) ของ flag รอยืนยัน — 10 นาที */
    protected const PDPA_PENDING_TTL = 600;

    /**
     * 🔔 จุดเข้าหลัก — เรียกจาก processMessage
     *
     * @return array|null response (ถ้าเกี่ยวกับ PDPA) / null (ไม่เกี่ยว → flow ปกติ)
     */
    protected function handlePdpaDeletionFlow(string $uid, string $messageText, ?array $userProfile = null): ?array
    {
        if (empty($uid)) {
            return null;
        }

        $platform = $this->currentPlatform ?? $this->detectPlatformFromUserId($uid);
        $pendingKey = self::PDPA_PENDING_PREFIX."{$platform}:{$uid}";

        // ── มีคำขอค้างรอยืนยันอยู่ → ตีความคำตอบ ──
        if (Cache::has($pendingKey)) {
            // ยกเลิกก่อน (กัน "ไม่ลบ" ไปเข้าเงื่อนไขยืนยัน)
            if ($this->matchesPdpaCancel($messageText)) {
                Cache::forget($pendingKey);
                Log::info('🗑️ PDPA: ลูกค้ายกเลิกคำขอลบข้อมูล', ['platform' => $platform, 'user_id' => $uid]);

                return [
                    'action' => 'pdpa_delete_cancelled',
                    'message' => "🙏 รับทราบค่ะ — ยกเลิกการลบข้อมูลแล้ว ข้อมูลทั้งหมดของเจ้าชะตายังอยู่ครบนะคะ ✨\n\n"
                        .'ถ้าต้องการดูดวง พิมพ์ "ดูดวง" ได้เลยค่ะ',
                    'reading' => null,
                ];
            }

            // ยืนยันลบ → ลบจริง
            if ($this->matchesPdpaConfirm($messageText)) {
                Cache::forget($pendingKey);

                return $this->executePdpaDeletion($platform, $uid);
            }

            // พิมพ์อย่างอื่น → ย้ำให้กดปุ่มยืนยัน/ยกเลิก (ไม่ลบ ไม่ปล่อยหลุด)
            Log::info('🗑️ PDPA: มีคำขอลบค้าง + ข้อความไม่ชัด → ย้ำให้กดยืนยัน/ยกเลิก', [
                'platform' => $platform,
                'user_id' => $uid,
                'text_preview' => mb_substr($messageText, 0, 40),
            ]);

            return $this->buildPdpaConfirmResponse(true);
        }

        // ── ยังไม่มีคำขอค้าง → ตรวจว่าลูกค้าขอลบข้อมูลหรือไม่ ──
        if (! $this->isPdpaDeleteRequest($messageText)) {
            return null; // ไม่เกี่ยว
        }

        Cache::put($pendingKey, now()->toIso8601String(), self::PDPA_PENDING_TTL);
        Log::info('🗑️ PDPA: ลูกค้าขอลบข้อมูล → แสดงคำเตือน + ปุ่มยืนยัน', ['platform' => $platform, 'user_id' => $uid]);

        return $this->buildPdpaConfirmResponse(false);
    }

    /**
     * 🗑️ ลบจริง — บันทึกคำขอ + เรียก service + แจ้งผล
     */
    protected function executePdpaDeletion(string $platform, string $uid): array
    {
        $code = 'PDPA-'.now()->format('ymd').'-'.Str::upper(Str::random(8));

        // บันทึกคำขอ (audit trail) — best-effort ไม่ให้ล้มทั้ง flow
        $request = null;
        try {
            $request = FortuneDataDeletionRequest::create([
                'confirmation_code' => $code,
                'platform' => $platform,
                'platform_user_id' => $uid,
                'source' => FortuneDataDeletionRequest::SOURCE_IN_CHAT,
                'status' => FortuneDataDeletionRequest::STATUS_PROCESSING,
                'requested_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('🗑️ PDPA: create deletion request row failed (non-blocking)', ['error' => $e->getMessage()]);
        }

        $result = app(FortunePdpaDeletionService::class)->deleteForCustomer($platform, $uid);

        // อัพเดทผลลง audit
        try {
            $request?->update([
                'status' => $result['ok'] ? FortuneDataDeletionRequest::STATUS_COMPLETED : FortuneDataDeletionRequest::STATUS_FAILED,
                'summary' => $result['counts'] ?? [],
                'error' => $result['ok'] ? null : mb_substr((string) ($result['error'] ?? 'unknown'), 0, 500),
                'completed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // ignore
        }

        if (! $result['ok']) {
            return [
                'action' => 'pdpa_deleted',
                'message' => "🙏 ขออภัยค่ะ เกิดข้อผิดพลาดระหว่างลบข้อมูล ยังลบไม่สำเร็จ\n\n"
                    ."กรุณาลองใหม่อีกครั้งภายหลัง หรือพิมพ์ \"คุยกับแม่หมอ\" เพื่อให้ทีมงานช่วยดำเนินการให้ค่ะ\n"
                    ."(รหัสอ้างอิง: {$code})",
                'reading' => null,
            ];
        }

        return [
            'action' => 'pdpa_deleted',
            'message' => "✅ ดำเนินการลบข้อมูลของเจ้าชะตาเรียบร้อยแล้วค่ะ\n\n"
                ."• คำทำนายและบทสนทนาทั้งหมด — ถูกลบออกจากระบบแล้ว\n"
                ."• สิทธิ์เงินปันผล / ส่วนแบ่งการตลาด / สายงาน — ยุติแล้วนับจากวันนี้\n\n"
                ."ขอบพระคุณที่เคยให้แม่หมอได้ดูแลนะคะ หากวันหน้าเปลี่ยนใจ ทักกลับมาเริ่มใหม่ได้เสมอค่ะ 🙏✨\n"
                ."(รหัสอ้างอิงการลบ: {$code})",
            'reading' => null,
        ];
    }

    /**
     * 📜 สร้าง response กล่องยืนยันลบข้อมูล (คำเตือน + ปุ่มยืนยัน/ยกเลิก)
     *
     * @param  bool  $reshow  true = ย้ำครั้งที่ 2 (ข้อความสั้นลง)
     */
    protected function buildPdpaConfirmResponse(bool $reshow): array
    {
        $message = $reshow
            ? '⚠️ กรุณากดยืนยันด้านล่างนะคะ — "🗑️ ยืนยันลบถาวร" หรือ "🙏 ไม่ลบ / ยกเลิก"'
            : "⚠️ *ยืนยันการลบข้อมูลตาม PDPA*\n"
                ."━━━━━━━━━━━━━━━\n"
                ."ถ้ายืนยันลบข้อมูลของเจ้าชะตา สิ่งเหล่านี้จะเกิดขึ้น:\n\n"
                ."• คำทำนายและบทสนทนาทั้งหมด จะถูกลบออกจากระบบ\n"
                ."• สิทธิ์ทุกอย่าง เช่น เงินปันผล ส่วนแบ่งการตลาดที่จะพึงได้ และสายงานทั้งหมด จะยุติ นับตั้งแต่วันที่แจ้งลบ\n\n"
                ."❗ ข้อมูลที่ลบแล้ว *จะไม่สามารถกู้คืนได้อีก*\n\n"
                .'ต้องการลบข้อมูลทั้งหมดจริง ๆ ใช่ไหมคะ?';

        return [
            'action' => 'pdpa_delete_confirm',
            'message' => $message,
            // FB format (title/text/payload) — LINE arm แปลงเป็น label/text ให้เอง
            'quick_replies' => [
                ['content_type' => 'text', 'title' => '🗑️ ยืนยันลบถาวร', 'text' => 'ยืนยันลบข้อมูลถาวร', 'payload' => 'ยืนยันลบข้อมูลถาวร'],
                ['content_type' => 'text', 'title' => '🙏 ไม่ลบ / ยกเลิก', 'text' => 'ไม่ลบข้อมูล', 'payload' => 'ไม่ลบข้อมูล'],
            ],
            'show_quick_replies' => true,
            'block_followups' => true,
            'reading' => null,
        ];
    }

    /**
     * ตรวจว่าข้อความ = การขอลบข้อมูลตาม PDPA หรือไม่ (strict — กัน over-match)
     */
    protected function isPdpaDeleteRequest(string $text): bool
    {
        $t = mb_strtolower(trim($text));
        if ($t === '') {
            return false;
        }

        // วลีเจาะจง (ต้องมี "ลบ" + "ข้อมูล/บัญชี" + สื่อถึงตัวเอง/ทั้งหมด) — กันชนคำสั่งอื่น
        $phrases = [
            'ลบข้อมูลของฉัน', 'ลบข้อมูลของผม', 'ลบข้อมูลของหนู', 'ลบข้อมูลของเรา',
            'ลบข้อมูลฉัน', 'ลบข้อมูลผม', 'ลบข้อมูลส่วนตัว', 'ลบข้อมูลทั้งหมด',
            'ลบข้อมูลออกจากระบบ', 'ขอลบข้อมูล', 'ต้องการลบข้อมูล', 'อยากลบข้อมูล',
            'ลบบัญชีของฉัน', 'ลบบัญชีของผม', 'ลบบัญชีของหนู', 'ลบบัญชีของเรา',
            'ขอลบบัญชี', 'ต้องการลบบัญชี', 'ลบข้อมูลของฉันออก',
            'delete my data', 'delete my account', 'delete all my data',
        ];

        foreach ($phrases as $p) {
            if (mb_strpos($t, mb_strtolower($p)) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * ตรวจว่าข้อความ = ยืนยันลบถาวร (ต้องไม่มีคำปฏิเสธปน)
     */
    protected function matchesPdpaConfirm(string $text): bool
    {
        $t = mb_strtolower(trim($text));
        if ($t === '') {
            return false;
        }

        // กัน "ไม่ยืนยันลบ" / "ยังไม่ลบ" หลุดมายืนยัน
        if (preg_match('/(ไม่|ยกเลิก|ยังไม่|อย่าลบ)/u', $t)) {
            return false;
        }

        foreach (['ยืนยันลบข้อมูลถาวร', 'ยืนยันลบถาวร', 'ยืนยันลบข้อมูล', 'ยืนยันการลบ', 'ยืนยันลบบัญชี',
            'ยืนยันลบ', 'ลบข้อมูลเลย', 'ลบถาวร', 'ลบจริง', 'ลบเลย', 'confirm delete', 'ต้องการลบจริง'] as $kw) {
            if (mb_strpos($t, $kw) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * ตรวจว่าข้อความ = ยกเลิกคำขอลบ
     */
    protected function matchesPdpaCancel(string $text): bool
    {
        $t = mb_strtolower(trim($text));
        if ($t === '') {
            return false;
        }

        foreach (['ไม่ลบ', 'ไม่ต้องลบ', 'ไม่เอาลบ', 'ยกเลิก', 'ยังไม่ลบ', 'อย่าลบ',
            'ไม่ต้องการลบ', 'เก็บไว้', 'ไม่เอา', 'cancel', 'ไม่ครับ', 'ไม่ค่ะ'] as $kw) {
            if (mb_strpos($t, $kw) !== false) {
                return true;
            }
        }

        return false;
    }
}
