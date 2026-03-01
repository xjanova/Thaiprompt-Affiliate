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

            // ✅ ใช้ MESSAGE_TAG เพื่อส่งนอก 24-hour window
            return $fbService->sendMessage($userId, $message, [
                'messaging_type' => 'MESSAGE_TAG',
                'tag' => 'CONFIRMED_EVENT_UPDATE',
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
}
