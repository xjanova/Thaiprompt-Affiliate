<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FortuneSavedQuestion;
use App\Models\FortuneTellingSetting;
use App\Services\FacebookWebhookService;
use App\Services\LineFortuneService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * จัดการคำถามที่ AI ตอบไม่ได้ — แอดมินดูและตอบกลับ
 *
 * รองรับทั้ง LINE และ Facebook Messenger
 */
class FortuneSavedQuestionsController extends Controller
{
    /**
     * แสดงรายการคำถามที่บันทึกไว้
     */
    public function index(Request $request)
    {
        $query = FortuneSavedQuestion::query()->latest();

        // กรองตามสถานะ
        if ($request->filled('status')) {
            if ($request->status === 'pending') {
                $query->pending();
            } elseif ($request->status === 'replied') {
                $query->replied();
            }
        }

        // กรองตาม platform
        if ($request->filled('platform') && in_array($request->platform, ['line', 'facebook'])) {
            $query->where('platform', $request->platform);
        }

        // ค้นหา
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('question', 'like', "%{$search}%")
                    ->orWhere('user_name', 'like', "%{$search}%")
                    ->orWhere('platform_user_id', 'like', "%{$search}%");
            });
        }

        $questions = $query->paginate(20)->withQueryString();

        // สถิติ
        $stats = [
            'total' => FortuneSavedQuestion::count(),
            'pending' => FortuneSavedQuestion::pending()->count(),
            'replied' => FortuneSavedQuestion::replied()->count(),
            'line' => FortuneSavedQuestion::where('platform', 'line')->count(),
            'facebook' => FortuneSavedQuestion::where('platform', 'facebook')->count(),
        ];

        return view('admin.fortune.saved-questions.index', [
            'questions' => $questions,
            'stats' => $stats,
            'pageTitle' => 'คำถามที่รอแอดมินตอบ',
        ]);
    }

    /**
     * แอดมินตอบคำถาม + ส่งกลับหาผู้ใช้ (รองรับทั้ง LINE + Facebook)
     */
    public function reply(Request $request, FortuneSavedQuestion $question)
    {
        $validated = $request->validate([
            'admin_reply' => 'required|string|max:2000',
        ]);

        $question->markAsReplied(
            $validated['admin_reply'],
            auth()->id()
        );

        // 📚 (2026-06-06) เก็บคู่ Q&A เข้า RAG (fortune_admin_qa) ให้บอทเรียนรู้คำตอบจริงของแอดมิน
        //    คำถามหน้านี้คือ gap ที่ AI ตอบไม่ได้ → คำตอบแอดมิน = ข้อมูลสอนคุณภาพสูงสุด
        $this->captureAdminQAForRag($question, $validated['admin_reply']);

        // ส่งคำตอบกลับหาผู้ใช้ (อัตโนมัติแยก platform)
        $result = $this->sendReplyToUser($question, $validated['admin_reply']);

        $platformLabel = $question->platform === 'facebook' ? 'Facebook' : 'LINE';

        if ($result['sent']) {
            $message = "ส่งคำตอบกลับหาผู้ใช้ผ่าน {$platformLabel} สำเร็จ ✅";

            return redirect()
                ->route('admin.fortune.saved-questions.index')
                ->with('success', $message);
        }

        $errorDetail = $result['error'] ? " ({$result['error']})" : '';
        $message = "บันทึกคำตอบแล้ว แต่ส่งผ่าน {$platformLabel} ไม่สำเร็จ{$errorDetail} ⚠️";

        return redirect()
            ->route('admin.fortune.saved-questions.index')
            ->with('error', $message);
    }

    /**
     * เก็บคู่ Q&A เข้า RAG (fortune_admin_qa) ผ่าน CaptureAdminQAJob
     *
     * - ใช้ explicitQuestion = คำถามที่ลูกค้าฝากไว้ (ไม่เดาจาก history → กัน Q ผิด/null)
     * - เคารพ toggle admin_qa_capture_enabled — ถ้าแอดมินปิดไว้ ก็ไม่เก็บ
     * - non-blocking: error ไม่กระทบการตอบ/ส่งคำตอบ
     *
     * หมายเหตุ: ส่งคำตอบผ่าน API จากหน้านี้ → FB echo จะมี app_id = บอทเรา → echo handler skip
     *           จึงไม่เกิด capture ซ้ำกับ path FB Page Inbox
     */
    protected function captureAdminQAForRag(FortuneSavedQuestion $question, string $adminReply): void
    {
        try {
            $settings = FortuneTellingSetting::getSettings();

            // ปิด capture ทั้งระบบ → ไม่เก็บ
            if (! (bool) ($settings->admin_qa_capture_enabled ?? true)) {
                return;
            }

            \App\Jobs\CaptureAdminQAJob::dispatch(
                $question->platform ?: 'line',
                $question->platform_user_id,
                $adminReply,
                auth()->id(), // รู้ตัวแอดมินที่ตอบ (ต่างจาก FB Page Inbox ที่ไม่รู้)
                [
                    'source' => 'saved_question',
                    'saved_question_id' => $question->id,
                    'reason' => $question->reason,
                ],
                $question->question, // explicitQuestion — คำถามที่ลูกค้าฝากไว้
            );
        } catch (\Throwable $e) {
            Log::warning('Fortune SavedQuestion: dispatch CaptureAdminQAJob ล้มเหลว', [
                'question_id' => $question->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * ส่งคำตอบกลับหาผู้ใช้ — อัตโนมัติแยก LINE / Facebook
     *
     * @return array{sent: bool, error: string|null}
     */
    protected function sendReplyToUser(FortuneSavedQuestion $question, string $reply): array
    {
        try {
            $platform = $question->platform ?? 'line';
            $userId = $question->platform_user_id;

            if (empty($userId)) {
                Log::warning('Fortune SavedQuestion: ไม่มี user ID สำหรับส่งคำตอบ', [
                    'question_id' => $question->id,
                ]);

                return ['sent' => false, 'error' => 'ไม่พบ User ID ของผู้ใช้ในระบบ'];
            }

            // ✅ Safety net: ตรวจสอบ platform จาก user ID format
            // LINE user ID: ขึ้นต้นด้วย "U" + 32 hex chars (33 ตัว)
            // Facebook PSID: ตัวเลขล้วน 15-20 หลัก
            $detectedPlatform = $this->detectPlatformFromUserId($userId);
            if ($detectedPlatform !== $platform) {
                Log::warning('Fortune SavedQuestion: platform ไม่ตรงกับ user ID format — ใช้ค่าที่ตรวจจับได้', [
                    'question_id' => $question->id,
                    'stored_platform' => $platform,
                    'detected_platform' => $detectedPlatform,
                    'user_id' => $userId,
                ]);
                $platform = $detectedPlatform;

                // อัพเดท platform ที่ถูกต้องลง DB ด้วย
                $question->update(['platform' => $platform]);
            }

            // สร้างข้อความตอบกลับ
            $message = "📝 แอดมินตอบกลับคำถามของคุณค่ะ\n\n"
                ."❓ คำถาม: {$question->question}\n\n"
                ."💬 คำตอบ: {$reply}\n\n"
                ."หากมีคำถามเพิ่มเติม พิมพ์ถามได้เลยนะคะ ✨";

            $sent = false;

            // ✅ แยก platform ส่งข้อความ
            if ($platform === 'facebook') {
                $sent = $this->sendViaFacebook($userId, $message, $question);
            } elseif ($platform === 'line') {
                $sent = $this->sendViaLine($userId, $message, $question);
            } else {
                Log::warning('Fortune SavedQuestion: platform ไม่รู้จัก', [
                    'question_id' => $question->id,
                    'platform' => $platform,
                ]);

                return ['sent' => false, 'error' => "platform '{$platform}' ไม่รองรับ"];
            }

            if ($sent) {
                $question->update(['is_sent_to_user' => true]);
                Log::info('Fortune SavedQuestion: ส่งคำตอบกลับสำเร็จ', [
                    'question_id' => $question->id,
                    'platform' => $platform,
                    'user_id' => $userId,
                ]);
            } else {
                Log::warning('Fortune SavedQuestion: ส่งคำตอบกลับไม่สำเร็จ', [
                    'question_id' => $question->id,
                    'platform' => $platform,
                    'user_id' => $userId,
                ]);
            }

            $platformLabel = $platform === 'facebook' ? 'Facebook' : 'LINE';

            return [
                'sent' => $sent,
                'error' => $sent ? null : "ส่งข้อความผ่าน {$platformLabel} API ไม่สำเร็จ — ดู Log เพิ่มเติม",
            ];
        } catch (\Exception $e) {
            Log::error('Fortune SavedQuestion: ส่งคำตอบกลับหาผู้ใช้ไม่สำเร็จ', [
                'question_id' => $question->id,
                'platform' => $question->platform ?? 'unknown',
                'user_id' => $question->platform_user_id,
                'error' => $e->getMessage(),
                'trace' => mb_substr($e->getTraceAsString(), 0, 500),
            ]);

            return ['sent' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * ส่งข้อความผ่าน Facebook Messenger
     */
    protected function sendViaFacebook(string $userId, string $message, FortuneSavedQuestion $question): bool
    {
        try {
            $settings = FortuneTellingSetting::getSettings();
            $fbService = new FacebookWebhookService($settings);

            // ⛔ (2026-08-13) เดิมตั้ง MESSAGE_TAG + CONFIRMED_EVENT_UPDATE — ใช้ไม่ได้ 2 ชั้น:
            //   1) คีย์ผิด — sendMessage อ่าน `message_tag` ไม่ใช่ `tag`
            //      ⇒ ส่ง messaging_type=MESSAGE_TAG ออกไป **โดยไม่มี tag** = FB ปฏิเสธเสมอ
            //   2) ต่อให้คีย์ถูก CONFIRMED_EVENT_UPDATE ก็ถูก Meta ยกเลิกแล้ว (subcode 1893061
            //      — ยืนยันด้วย fortune:fb-tag-probe --to=<PSID จริง> เมื่อ 2026-08-13)
            //   ⇒ RESPONSE คือทางเดียวที่ส่งถึงจริง (แอดมินตอบตอนลูกค้ายังคุยอยู่ = อยู่ในกรอบ 24 ชม.)
            return $fbService->sendMessage($userId, $message, [
                'messaging_type' => 'RESPONSE',
            ]);
        } catch (\Exception $e) {
            Log::error('Fortune SavedQuestion: ส่ง Facebook ล้มเหลว', [
                'question_id' => $question->id,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * ส่งข้อความผ่าน LINE — ใช้ admin priority (ข้าม Gatekeeper)
     */
    protected function sendViaLine(string $userId, string $message, FortuneSavedQuestion $question): bool
    {
        try {
            $settings = FortuneTellingSetting::getSettings();
            $lineService = new LineFortuneService($settings);

            // ✅ ใช้ sendAdminMessage — ข้าม Gatekeeper throttle
            // เพราะแอดมินตอบคำถามต้องส่งถึงผู้ใช้ทันที
            return $lineService->sendAdminMessage($userId, $message);
        } catch (\Exception $e) {
            Log::error('Fortune SavedQuestion: ส่ง LINE ล้มเหลว', [
                'question_id' => $question->id,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * ส่งคำตอบซ้ำหาผู้ใช้ (กรณีส่งไม่สำเร็จครั้งแรก)
     */
    public function resend(FortuneSavedQuestion $question)
    {
        if (! $question->is_replied || ! $question->admin_reply) {
            return redirect()
                ->route('admin.fortune.saved-questions.index')
                ->with('error', 'ยังไม่ได้ตอบคำถามนี้');
        }

        $result = $this->sendReplyToUser($question, $question->admin_reply);
        $platformLabel = $question->platform === 'facebook' ? 'Facebook' : 'LINE';

        if ($result['sent']) {
            return redirect()
                ->route('admin.fortune.saved-questions.index')
                ->with('success', "ส่งคำตอบกลับหาผู้ใช้ผ่าน {$platformLabel} สำเร็จ ✅");
        }

        $errorDetail = $result['error'] ? " ({$result['error']})" : '';

        return redirect()
            ->route('admin.fortune.saved-questions.index')
            ->with('error', "ส่งคำตอบผ่าน {$platformLabel} ไม่สำเร็จ{$errorDetail} กรุณาลองใหม่");
    }

    /**
     * ลบคำถาม
     */
    public function destroy(FortuneSavedQuestion $question)
    {
        $question->delete();

        return redirect()
            ->route('admin.fortune.saved-questions.index')
            ->with('success', 'ลบคำถามเรียบร้อยแล้ว');
    }

    /**
     * ตรวจจับ platform จาก user ID format
     *
     * LINE user ID: ขึ้นต้นด้วย "U" + hex 32 ตัว (รวม 33 ตัว) เช่น "U1234abcd..."
     * Facebook PSID: ตัวเลขล้วน 15-20 หลัก เช่น "26165964502999706"
     *
     * @return string 'line' หรือ 'facebook'
     */
    protected function detectPlatformFromUserId(string $userId): string
    {
        // LINE user ID: ขึ้นต้นด้วย U + hex 32 ตัว
        if (preg_match('/^U[0-9a-fA-F]{32}$/', $userId)) {
            return 'line';
        }

        // Facebook PSID: ตัวเลขล้วน 10+ หลัก
        if (preg_match('/^\d{10,}$/', $userId)) {
            return 'facebook';
        }

        // ไม่แน่ใจ → ใช้ heuristic: ถ้าเป็นตัวเลขล้วน → facebook
        if (ctype_digit($userId)) {
            return 'facebook';
        }

        // Default: line
        return 'line';
    }
}
