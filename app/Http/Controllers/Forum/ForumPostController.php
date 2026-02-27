<?php

namespace App\Http\Controllers\Forum;

use App\Http\Controllers\Controller;
use App\Models\ForumLike;
use App\Models\ForumPost;
use App\Models\ForumThread;
use App\Services\ForumTrophyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * ForumPostController - จัดการโพสต์/คอมเมนต์ในฟอรั่ม
 *
 * สร้าง แก้ไข ลบ คอมเมนต์และการตอบกลับ
 */
class ForumPostController extends Controller
{
    protected ForumTrophyService $trophyService;

    /**
     * สร้าง instance
     */
    public function __construct(ForumTrophyService $trophyService)
    {
        $this->trophyService = $trophyService;
    }

    /**
     * สร้างโพสต์/คอมเมนต์ใหม่
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function store(Request $request, int $threadId)
    {
        $thread = ForumThread::findOrFail($threadId);

        // ตรวจสอบว่ากระทู้ไม่ถูกล็อค
        if ($thread->is_locked) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'กระทู้นี้ถูกล็อคไม่สามารถตอบกลับได้'], 403);
            }

            return back()->withErrors(['content' => 'กระทู้นี้ถูกล็อคไม่สามารถตอบกลับได้']);
        }

        // ตรวจสอบว่าผู้ใช้ไม่ถูกแบน
        if (Auth::user()->forum_banned_until && Auth::user()->forum_banned_until > now()) {
            $message = 'คุณถูกแบนจากการโพสต์จนถึง '.Auth::user()->forum_banned_until->format('d/m/Y H:i');
            if ($request->expectsJson()) {
                return response()->json(['error' => $message], 403);
            }

            return back()->withErrors(['content' => $message]);
        }

        $validated = $request->validate([
            'content' => 'required|string|min:5',
            'parent_id' => 'nullable|exists:forum_posts,id',
        ], [
            'content.required' => 'กรุณากรอกเนื้อหา',
            'content.min' => 'เนื้อหาต้องมีอย่างน้อย 5 ตัวอักษร',
        ]);

        // สร้างโพสต์
        $post = ForumPost::create([
            'thread_id' => $thread->id,
            'user_id' => Auth::id(),
            'parent_id' => $validated['parent_id'] ?? null,
            'content' => strip_tags($validated['content'], '<p><br><b><i><u><strong><em><ul><ol><li><a><h2><h3><h4><blockquote><code><pre>'),
        ]);

        // ตรวจสอบและให้โทรฟี่
        $this->trophyService->checkAndAwardTrophies(Auth::user());

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'ตอบกลับสำเร็จ!',
                'post' => $post->load('user.forumTrophies'),
            ]);
        }

        return redirect()
            ->route('forum.thread.show', $thread->slug)
            ->with('success', 'ตอบกลับสำเร็จ!');
    }

    /**
     * อัปเดตโพสต์
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function update(Request $request, int $id)
    {
        $post = ForumPost::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $validated = $request->validate([
            'content' => 'required|string|min:5',
        ], [
            'content.required' => 'กรุณากรอกเนื้อหา',
            'content.min' => 'เนื้อหาต้องมีอย่างน้อย 5 ตัวอักษร',
        ]);

        $post->update([
            'content' => strip_tags($validated['content'], '<p><br><b><i><u><strong><em><ul><ol><li><a><h2><h3><h4><blockquote><code><pre>'),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'แก้ไขสำเร็จ!',
                'post' => $post,
            ]);
        }

        return redirect()
            ->route('forum.thread.show', $post->thread->slug)
            ->with('success', 'แก้ไขสำเร็จ!');
    }

    /**
     * ลบโพสต์
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, int $id)
    {
        $post = ForumPost::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $threadSlug = $post->thread->slug;
        $post->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'ลบสำเร็จ!',
            ]);
        }

        return redirect()
            ->route('forum.thread.show', $threadSlug)
            ->with('success', 'ลบโพสต์สำเร็จ!');
    }

    /**
     * กดไลค์/ยกเลิกไลค์โพสต์
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleLike(Request $request, int $id)
    {
        $post = ForumPost::findOrFail($id);
        $result = ForumLike::toggleLike(Auth::user(), $post);

        return response()->json($result);
    }

    /**
     * ตั้งเป็น Best Answer
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function markBestAnswer(Request $request, int $id)
    {
        $post = ForumPost::findOrFail($id);
        $thread = $post->thread;

        // ตรวจสอบว่าผู้ใช้เป็นเจ้าของกระทู้
        if ($thread->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'คุณไม่มีสิทธิ์ตั้ง Best Answer สำหรับกระทู้นี้',
            ], 403);
        }

        // ตั้งเป็น best answer
        $post->markAsBestAnswer();

        // ตรวจสอบและให้โทรฟี่
        $this->trophyService->checkAndAwardTrophies($post->user);

        return response()->json([
            'success' => true,
            'message' => 'ตั้งเป็นคำตอบที่ดีที่สุดสำเร็จ!',
        ]);
    }
}
