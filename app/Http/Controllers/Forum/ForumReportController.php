<?php

namespace App\Http\Controllers\Forum;

use App\Http\Controllers\Controller;
use App\Models\ForumPost;
use App\Models\ForumReport;
use App\Models\ForumThread;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * ForumReportController - จัดการการรายงานเนื้อหาในฟอรั่ม
 *
 * ผู้ใช้สามารถรายงานกระทู้หรือโพสต์ที่ไม่เหมาะสมได้
 */
class ForumReportController extends Controller
{
    /**
     * รายงานกระทู้
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function reportThread(Request $request, int $threadId)
    {
        $thread = ForumThread::findOrFail($threadId);

        // ตรวจสอบว่ารายงานซ้ำหรือไม่
        $existingReport = ForumReport::where('user_id', Auth::id())
            ->where('reportable_type', ForumThread::class)
            ->where('reportable_id', $thread->id)
            ->where('status', 'pending')
            ->first();

        if ($existingReport) {
            return response()->json([
                'success' => false,
                'message' => 'คุณได้รายงานกระทู้นี้ไปแล้ว รอการตรวจสอบ',
            ], 400);
        }

        $validated = $request->validate([
            'reason_type' => 'required|in:spam,harassment,inappropriate,misinformation,hate_speech,copyright,other',
            'reason_detail' => 'nullable|string|max:1000',
        ], [
            'reason_type.required' => 'กรุณาเลือกเหตุผลการรายงาน',
        ]);

        ForumReport::create([
            'user_id' => Auth::id(),
            'reportable_type' => ForumThread::class,
            'reportable_id' => $thread->id,
            'reason_type' => $validated['reason_type'],
            'reason_detail' => $validated['reason_detail'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'รายงานสำเร็จ ทีมงานจะตรวจสอบในเร็วๆ นี้',
        ]);
    }

    /**
     * รายงานโพสต์
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function reportPost(Request $request, int $postId)
    {
        $post = ForumPost::findOrFail($postId);

        // ตรวจสอบว่ารายงานซ้ำหรือไม่
        $existingReport = ForumReport::where('user_id', Auth::id())
            ->where('reportable_type', ForumPost::class)
            ->where('reportable_id', $post->id)
            ->where('status', 'pending')
            ->first();

        if ($existingReport) {
            return response()->json([
                'success' => false,
                'message' => 'คุณได้รายงานโพสต์นี้ไปแล้ว รอการตรวจสอบ',
            ], 400);
        }

        $validated = $request->validate([
            'reason_type' => 'required|in:spam,harassment,inappropriate,misinformation,hate_speech,copyright,other',
            'reason_detail' => 'nullable|string|max:1000',
        ], [
            'reason_type.required' => 'กรุณาเลือกเหตุผลการรายงาน',
        ]);

        ForumReport::create([
            'user_id' => Auth::id(),
            'reportable_type' => ForumPost::class,
            'reportable_id' => $post->id,
            'reason_type' => $validated['reason_type'],
            'reason_detail' => $validated['reason_detail'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'รายงานสำเร็จ ทีมงานจะตรวจสอบในเร็วๆ นี้',
        ]);
    }
}
