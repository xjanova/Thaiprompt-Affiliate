<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FortuneSavedQuestion;
use App\Services\LineFortuneService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * จัดการคำถามที่ AI ตอบไม่ได้ — แอดมินดูและตอบกลับ
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
        ];

        return view('admin.fortune.saved-questions.index', [
            'questions' => $questions,
            'stats' => $stats,
            'pageTitle' => 'คำถามที่รอแอดมินตอบ',
        ]);
    }

    /**
     * แอดมินตอบคำถาม + ส่งกลับหาผู้ใช้ผ่าน LINE
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

        // ส่งคำตอบกลับหาผู้ใช้ผ่าน LINE
        $sent = $this->sendReplyToUser($question, $validated['admin_reply']);

        $message = $sent
            ? 'ตอบคำถามและส่งกลับหาผู้ใช้เรียบร้อยแล้ว'
            : 'บันทึกคำตอบแล้ว แต่ส่งข้อความกลับหาผู้ใช้ไม่สำเร็จ';

        return redirect()
            ->route('admin.fortune.saved-questions.index')
            ->with($sent ? 'success' : 'warning', $message);
    }

    /**
     * ส่งคำตอบกลับหาผู้ใช้ผ่าน LINE
     */
    protected function sendReplyToUser(FortuneSavedQuestion $question, string $reply): bool
    {
        try {
            if ($question->platform !== 'line') {
                return false;
            }

            $lineService = new LineFortuneService();

            // ส่งข้อความตอบกลับพร้อมอ้างอิงคำถามเดิม
            $message = "📝 แอดมินตอบกลับคำถามของคุณค่ะ\n\n"
                . "❓ คำถาม: {$question->question}\n\n"
                . "💬 คำตอบ: {$reply}\n\n"
                . "หากมีคำถามเพิ่มเติม พิมพ์ถามได้เลยนะคะ ✨";

            $sent = $lineService->sendMessage($question->platform_user_id, $message);

            if ($sent) {
                $question->update(['is_sent_to_user' => true]);
            }

            return $sent;
        } catch (\Exception $e) {
            Log::warning('Fortune: ส่งคำตอบกลับหาผู้ใช้ไม่สำเร็จ', [
                'question_id' => $question->id,
                'user_id' => $question->platform_user_id,
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

        $sent = $this->sendReplyToUser($question, $question->admin_reply);

        return redirect()
            ->route('admin.fortune.saved-questions.index')
            ->with($sent ? 'success' : 'error', $sent ? 'ส่งคำตอบกลับหาผู้ใช้สำเร็จ' : 'ส่งคำตอบไม่สำเร็จ กรุณาลองใหม่');
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
