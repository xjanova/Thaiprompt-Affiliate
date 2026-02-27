<?php

namespace App\Http\Controllers\Forum;

use App\Http\Controllers\Controller;
use App\Models\ForumCategory;
use App\Models\ForumLike;
use App\Models\ForumThread;
use App\Services\ForumTrophyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * ForumThreadController - จัดการกระทู้ในฟอรั่ม
 *
 * สร้าง แก้ไข ลบ และแสดงกระทู้
 */
class ForumThreadController extends Controller
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
     * แสดงกระทู้
     *
     * @return \Illuminate\View\View
     */
    public function show(string $slug)
    {
        // ดึงกระทู้พร้อมข้อมูลที่เกี่ยวข้อง
        $thread = ForumThread::where('slug', $slug)
            ->with([
                'user.forumTrophies',
                'category.parent',
                'lastPostUser',
            ])
            ->firstOrFail();

        // เพิ่มจำนวนการเข้าชม
        $thread->incrementViews();

        // ดึงโพสต์/คอมเมนต์ พร้อม nested replies
        $posts = $thread->posts()
            ->with(['user.forumTrophies', 'replies.user.forumTrophies'])
            ->topLevel()
            ->visible()
            ->orderBy('is_best_answer', 'desc')
            ->orderBy('likes_count', 'desc')
            ->orderBy('created_at')
            ->paginate(20);

        // ตรวจสอบว่าผู้ใช้กดไลค์หรือยัง
        $isLiked = false;
        if (Auth::check()) {
            $isLiked = $thread->isLikedBy(Auth::user());
        }

        // โทรฟี่ของผู้สร้างกระทู้
        $authorTrophies = $this->trophyService->getDisplayTrophies($thread->user);
        $authorNameColor = $this->trophyService->getNameColor($thread->user);

        // Breadcrumbs
        $breadcrumbs = $this->buildBreadcrumbs($thread);

        // กระทู้ที่เกี่ยวข้อง
        $relatedThreads = ForumThread::where('category_id', $thread->category_id)
            ->where('id', '!=', $thread->id)
            ->latest()
            ->limit(5)
            ->get();

        return view('forum.thread.show', compact(
            'thread',
            'posts',
            'isLiked',
            'authorTrophies',
            'authorNameColor',
            'breadcrumbs',
            'relatedThreads'
        ));
    }

    /**
     * แสดงฟอร์มสร้างกระทู้ใหม่
     *
     * @return \Illuminate\View\View
     */
    public function create(Request $request)
    {
        $categorySlug = $request->input('category');
        $category = null;

        if ($categorySlug) {
            $category = ForumCategory::where('slug', $categorySlug)
                ->active()
                ->where('is_locked', false)
                ->first();
        }

        // ดึงหมวดหมู่ที่สามารถสร้างกระทู้ได้
        $categories = ForumCategory::active()
            ->where('is_locked', false)
            ->ordered()
            ->get()
            ->groupBy('parent_id');

        return view('forum.thread.create', compact('categories', 'category'));
    }

    /**
     * บันทึกกระทู้ใหม่
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:forum_categories,id',
            'title' => 'required|string|min:5|max:255',
            'content' => 'required|string|min:20',
        ], [
            'category_id.required' => 'กรุณาเลือกหมวดหมู่',
            'title.required' => 'กรุณากรอกหัวข้อกระทู้',
            'title.min' => 'หัวข้อกระทู้ต้องมีอย่างน้อย 5 ตัวอักษร',
            'content.required' => 'กรุณากรอกเนื้อหากระทู้',
            'content.min' => 'เนื้อหากระทู้ต้องมีอย่างน้อย 20 ตัวอักษร',
        ]);

        // ตรวจสอบว่าหมวดหมู่ไม่ถูกล็อค
        $category = ForumCategory::findOrFail($validated['category_id']);
        if ($category->is_locked) {
            return back()->withErrors(['category_id' => 'หมวดหมู่นี้ถูกล็อคไม่สามารถสร้างกระทู้ได้']);
        }

        // ตรวจสอบว่าผู้ใช้ไม่ถูกแบน
        if (Auth::user()->forum_banned_until && Auth::user()->forum_banned_until > now()) {
            return back()->withErrors(['content' => 'คุณถูกแบนจากการโพสต์จนถึง '.Auth::user()->forum_banned_until->format('d/m/Y H:i')]);
        }

        // สร้างกระทู้
        $thread = ForumThread::create([
            'category_id' => $validated['category_id'],
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'content' => strip_tags($validated['content'], '<p><br><b><i><u><strong><em><ul><ol><li><a><h2><h3><h4><blockquote><code><pre>'), // Sanitize HTML
            'excerpt' => Str::limit(strip_tags($validated['content']), 200),
        ]);

        // ตรวจสอบและให้โทรฟี่
        $this->trophyService->checkAndAwardTrophies(Auth::user());

        return redirect()
            ->route('forum.thread.show', $thread->slug)
            ->with('success', 'สร้างกระทู้สำเร็จ!');
    }

    /**
     * แสดงฟอร์มแก้ไขกระทู้
     *
     * @return \Illuminate\View\View
     */
    public function edit(string $slug)
    {
        $thread = ForumThread::where('slug', $slug)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $categories = ForumCategory::active()
            ->where('is_locked', false)
            ->ordered()
            ->get()
            ->groupBy('parent_id');

        return view('forum.thread.edit', compact('thread', 'categories'));
    }

    /**
     * อัปเดตกระทู้
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, string $slug)
    {
        $thread = ForumThread::where('slug', $slug)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $validated = $request->validate([
            'title' => 'required|string|min:5|max:255',
            'content' => 'required|string|min:20',
        ], [
            'title.required' => 'กรุณากรอกหัวข้อกระทู้',
            'content.required' => 'กรุณากรอกเนื้อหากระทู้',
        ]);

        $thread->update([
            'title' => $validated['title'],
            'content' => strip_tags($validated['content'], '<p><br><b><i><u><strong><em><ul><ol><li><a><h2><h3><h4><blockquote><code><pre>'),
            'excerpt' => Str::limit(strip_tags($validated['content']), 200),
        ]);

        return redirect()
            ->route('forum.thread.show', $thread->slug)
            ->with('success', 'แก้ไขกระทู้สำเร็จ!');
    }

    /**
     * ลบกระทู้
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(string $slug)
    {
        $thread = ForumThread::where('slug', $slug)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $categorySlug = $thread->category->slug;
        $thread->delete();

        return redirect()
            ->route('forum.category', $categorySlug)
            ->with('success', 'ลบกระทู้สำเร็จ!');
    }

    /**
     * กดไลค์/ยกเลิกไลค์กระทู้
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleLike(Request $request, int $id)
    {
        $thread = ForumThread::findOrFail($id);
        $result = ForumLike::toggleLike(Auth::user(), $thread);

        return response()->json($result);
    }

    /**
     * สร้าง breadcrumbs สำหรับกระทู้
     */
    protected function buildBreadcrumbs(ForumThread $thread): array
    {
        $breadcrumbs = [
            ['name' => 'ฟอรั่ม', 'url' => route('forum.index')],
        ];

        // เพิ่มหมวดหมู่หลัก
        if ($thread->category->parent) {
            $breadcrumbs[] = [
                'name' => $thread->category->parent->name,
                'url' => route('forum.category', $thread->category->parent->slug),
            ];
        }

        // เพิ่มหมวดหมู่
        $breadcrumbs[] = [
            'name' => $thread->category->name,
            'url' => route('forum.category', $thread->category->slug),
        ];

        // เพิ่มกระทู้
        $breadcrumbs[] = [
            'name' => Str::limit($thread->title, 50),
            'url' => null,
        ];

        return $breadcrumbs;
    }
}
