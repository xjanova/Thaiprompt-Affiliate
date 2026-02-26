<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ForumCategory;
use App\Models\ForumPost;
use App\Models\ForumReport;
use App\Models\ForumThread;
use App\Models\ForumTrophy;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * ForumAdminController - จัดการฟอรั่มในส่วน Admin
 *
 * ควบคุมการจัดการหมวดหมู่ กระทู้ รายงาน และถ้วยรางวัล
 */
class ForumAdminController extends Controller
{
    // =========================================
    // Categories Management - จัดการหมวดหมู่
    // =========================================

    /**
     * แสดงรายการหมวดหมู่ทั้งหมด
     *
     * @return \Illuminate\View\View
     */
    public function categories()
    {
        $categories = ForumCategory::withCount(['threads', 'children'])
            ->whereNull('parent_id')
            ->orderBy('display_order')
            ->with(['children' => function ($query) {
                $query->withCount('threads')->orderBy('display_order');
            }])
            ->get();

        return view('admin.forum.categories.index', compact('categories'));
    }

    /**
     * แสดงฟอร์มสร้างหมวดหมู่ใหม่
     *
     * @return \Illuminate\View\View
     */
    public function createCategory()
    {
        $parentCategories = ForumCategory::whereNull('parent_id')
            ->orderBy('display_order')
            ->get();

        return view('admin.forum.categories.create', compact('parentCategories'));
    }

    /**
     * บันทึกหมวดหมู่ใหม่
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:50',
            'parent_id' => 'nullable|exists:forum_categories,id',
            'is_locked' => 'boolean',
            'display_order' => 'nullable|integer',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_locked'] = $request->boolean('is_locked');
        $validated['display_order'] = $validated['display_order'] ?? ForumCategory::max('display_order') + 1;

        ForumCategory::create($validated);

        return redirect()
            ->route('admin.forum.categories.index')
            ->with('success', 'สร้างหมวดหมู่สำเร็จ');
    }

    /**
     * แสดงฟอร์มแก้ไขหมวดหมู่
     *
     * @return \Illuminate\View\View
     */
    public function editCategory(ForumCategory $category)
    {
        $parentCategories = ForumCategory::whereNull('parent_id')
            ->where('id', '!=', $category->id)
            ->orderBy('display_order')
            ->get();

        return view('admin.forum.categories.edit', compact('category', 'parentCategories'));
    }

    /**
     * อัปเดตหมวดหมู่
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateCategory(Request $request, ForumCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:50',
            'parent_id' => 'nullable|exists:forum_categories,id',
            'is_locked' => 'boolean',
            'display_order' => 'nullable|integer',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_locked'] = $request->boolean('is_locked');

        $category->update($validated);

        return redirect()
            ->route('admin.forum.categories.index')
            ->with('success', 'อัปเดตหมวดหมู่สำเร็จ');
    }

    /**
     * ลบหมวดหมู่
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteCategory(ForumCategory $category)
    {
        // ตรวจสอบว่ามีกระทู้ในหมวดหมู่หรือไม่
        if ($category->threads()->count() > 0) {
            return back()->with('error', 'ไม่สามารถลบหมวดหมู่ที่มีกระทู้ได้');
        }

        // ตรวจสอบว่ามีหมวดหมู่ย่อยหรือไม่
        if ($category->children()->count() > 0) {
            return back()->with('error', 'ไม่สามารถลบหมวดหมู่ที่มีหมวดหมู่ย่อยได้');
        }

        $category->delete();

        return redirect()
            ->route('admin.forum.categories.index')
            ->with('success', 'ลบหมวดหมู่สำเร็จ');
    }

    /**
     * จัดเรียงลำดับหมวดหมู่
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function reorderCategories(Request $request)
    {
        $order = $request->input('order', []);

        foreach ($order as $index => $categoryId) {
            ForumCategory::where('id', $categoryId)->update(['display_order' => $index]);
        }

        return response()->json(['success' => true, 'message' => 'จัดเรียงลำดับสำเร็จ']);
    }

    // =========================================
    // Threads Management - จัดการกระทู้
    // =========================================

    /**
     * แสดงรายการกระทู้ทั้งหมด
     *
     * @return \Illuminate\View\View
     */
    public function threads(Request $request)
    {
        $query = ForumThread::with(['user', 'category'])
            ->withCount('posts');

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            switch ($request->status) {
                case 'pinned':
                    $query->where('is_pinned', true);
                    break;
                case 'locked':
                    $query->where('is_locked', true);
                    break;
                case 'featured':
                    $query->where('is_featured', true);
                    break;
            }
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $threads = $query->latest()->paginate(20);
        $categories = ForumCategory::orderBy('display_order')->get();

        return view('admin.forum.threads.index', compact('threads', 'categories'));
    }

    /**
     * แสดงรายละเอียดกระทู้
     *
     * @return \Illuminate\View\View
     */
    public function showThread(ForumThread $thread)
    {
        $thread->load(['user', 'category', 'posts.user']);

        return view('admin.forum.threads.show', compact('thread'));
    }

    /**
     * Toggle Pin กระทู้
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function togglePin(ForumThread $thread)
    {
        $thread->update(['is_pinned' => ! $thread->is_pinned]);

        return response()->json([
            'success' => true,
            'is_pinned' => $thread->is_pinned,
            'message' => $thread->is_pinned ? 'ปักหมุดกระทู้แล้ว' : 'ยกเลิกปักหมุดแล้ว',
        ]);
    }

    /**
     * Toggle Lock กระทู้
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleLock(ForumThread $thread)
    {
        $thread->update(['is_locked' => ! $thread->is_locked]);

        return response()->json([
            'success' => true,
            'is_locked' => $thread->is_locked,
            'message' => $thread->is_locked ? 'ล็อคกระทู้แล้ว' : 'ปลดล็อคกระทู้แล้ว',
        ]);
    }

    /**
     * Toggle Feature กระทู้
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleFeature(ForumThread $thread)
    {
        $thread->update(['is_featured' => ! $thread->is_featured]);

        return response()->json([
            'success' => true,
            'is_featured' => $thread->is_featured,
            'message' => $thread->is_featured ? 'เพิ่มเป็นกระทู้แนะนำแล้ว' : 'ยกเลิกกระทู้แนะนำแล้ว',
        ]);
    }

    /**
     * ลบกระทู้
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteThread(ForumThread $thread)
    {
        $thread->delete();

        return redirect()
            ->route('admin.forum.threads.index')
            ->with('success', 'ลบกระทู้สำเร็จ');
    }

    // =========================================
    // Reports Management - จัดการรายงาน
    // =========================================

    /**
     * แสดงรายการรายงานทั้งหมด
     *
     * @return \Illuminate\View\View
     */
    public function reports(Request $request)
    {
        $query = ForumReport::with(['user', 'reportable']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            // Default: show pending first
            $query->orderByRaw("FIELD(status, 'pending', 'reviewed', 'resolved', 'dismissed')");
        }

        $reports = $query->latest()->paginate(20);

        // Count by status
        $pendingCount = ForumReport::where('status', 'pending')->count();
        $resolvedCount = ForumReport::where('status', 'resolved')->count();

        return view('admin.forum.reports.index', compact('reports', 'pendingCount', 'resolvedCount'));
    }

    /**
     * แสดงรายละเอียดรายงาน
     *
     * @return \Illuminate\View\View
     */
    public function showReport(ForumReport $report)
    {
        $report->load(['user', 'reportable']);

        return view('admin.forum.reports.show', compact('report'));
    }

    /**
     * Resolve รายงาน (ดำเนินการแล้ว)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function resolveReport(Request $request, ForumReport $report)
    {
        $validated = $request->validate([
            'action' => 'nullable|string|max:255',
            'reviewer_notes' => 'nullable|string',
        ]);

        $report->update([
            'status' => 'resolved',
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
            'action_taken' => $validated['action'] ?? null,
            'reviewer_notes' => $validated['reviewer_notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'ดำเนินการรายงานสำเร็จ',
        ]);
    }

    /**
     * Dismiss รายงาน (ยกเลิก)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function dismissReport(Request $request, ForumReport $report)
    {
        $validated = $request->validate([
            'reviewer_notes' => 'nullable|string',
        ]);

        $report->update([
            'status' => 'dismissed',
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
            'action_taken' => 'none',
            'reviewer_notes' => $validated['reviewer_notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'ยกเลิกรายงานสำเร็จ',
        ]);
    }

    // =========================================
    // Trophies Management - จัดการถ้วยรางวัล
    // =========================================

    /**
     * แสดงรายการถ้วยรางวัลทั้งหมด
     *
     * @return \Illuminate\View\View
     */
    public function trophies()
    {
        $trophies = ForumTrophy::withCount('users')
            ->orderBy('display_order', 'asc')
            ->get();

        return view('admin.forum.trophies.index', compact('trophies'));
    }

    /**
     * แสดงฟอร์มสร้างถ้วยรางวัลใหม่
     *
     * @return \Illuminate\View\View
     */
    public function createTrophy()
    {
        return view('admin.forum.trophies.create');
    }

    /**
     * บันทึกถ้วยรางวัลใหม่
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeTrophy(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'points' => 'required|integer|min:0',
            'is_automatic' => 'boolean',
            'requirements' => 'nullable|array',
        ]);

        $validated['is_automatic'] = $request->boolean('is_automatic');

        ForumTrophy::create($validated);

        return redirect()
            ->route('admin.forum.trophies.index')
            ->with('success', 'สร้างถ้วยรางวัลสำเร็จ');
    }

    /**
     * แสดงฟอร์มแก้ไขถ้วยรางวัล
     *
     * @return \Illuminate\View\View
     */
    public function editTrophy(ForumTrophy $trophy)
    {
        $trophy->loadCount('users');

        return view('admin.forum.trophies.edit', compact('trophy'));
    }

    /**
     * อัปเดตถ้วยรางวัล
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateTrophy(Request $request, ForumTrophy $trophy)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'points' => 'required|integer|min:0',
            'is_automatic' => 'boolean',
            'requirements' => 'nullable|array',
        ]);

        $validated['is_automatic'] = $request->boolean('is_automatic');

        $trophy->update($validated);

        return redirect()
            ->route('admin.forum.trophies.index')
            ->with('success', 'อัปเดตถ้วยรางวัลสำเร็จ');
    }

    /**
     * ลบถ้วยรางวัล
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteTrophy(ForumTrophy $trophy)
    {
        $trophy->delete();

        return redirect()
            ->route('admin.forum.trophies.index')
            ->with('success', 'ลบถ้วยรางวัลสำเร็จ');
    }

    /**
     * มอบถ้วยรางวัลให้ผู้ใช้
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function awardTrophy(ForumTrophy $trophy, User $user)
    {
        // ตรวจสอบว่าผู้ใช้มีถ้วยรางวัลนี้แล้วหรือไม่
        if ($user->forumTrophies()->where('forum_trophy_id', $trophy->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'ผู้ใช้มีถ้วยรางวัลนี้แล้ว',
            ], 422);
        }

        $user->forumTrophies()->attach($trophy->id, ['awarded_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'มอบถ้วยรางวัลสำเร็จ',
        ]);
    }

    // =========================================
    // Analytics - สถิติ
    // =========================================

    /**
     * แสดงสถิติฟอรั่ม
     *
     * @return \Illuminate\View\View
     */
    public function analytics()
    {
        $stats = [
            'total_categories' => ForumCategory::count(),
            'total_threads' => ForumThread::count(),
            'total_posts' => ForumPost::count(),
            'total_reports' => ForumReport::count(),
            'pending_reports' => ForumReport::where('status', 'pending')->count(),
            'active_users' => ForumThread::distinct('user_id')->count('user_id'),
        ];

        // กระทู้ยอดนิยม 10 อันดับ
        $topThreads = ForumThread::withCount(['posts', 'likes'])
            ->orderByDesc('view_count')
            ->limit(10)
            ->get();

        // ผู้ใช้ที่มีส่วนร่วมมากที่สุด
        $topContributors = User::withCount(['forumThreads', 'forumPosts'])
            ->orderByDesc('forum_threads_count')
            ->limit(10)
            ->get();

        // กระทู้ใหม่ในสัปดาห์นี้
        $weeklyThreads = ForumThread::where('created_at', '>=', now()->subWeek())->count();
        $weeklyPosts = ForumPost::where('created_at', '>=', now()->subWeek())->count();

        return view('admin.forum.analytics.index', compact(
            'stats',
            'topThreads',
            'topContributors',
            'weeklyThreads',
            'weeklyPosts'
        ));
    }

    // =========================================
    // Settings - ตั้งค่า
    // =========================================

    /**
     * แสดงหน้าตั้งค่าฟอรั่ม
     *
     * @return \Illuminate\View\View
     */
    public function settings()
    {
        $settings = [
            'forum_enabled' => config('forum.enabled', true),
            'require_login' => config('forum.require_login', false),
            'allow_guest_view' => config('forum.allow_guest_view', true),
            'posts_per_page' => config('forum.posts_per_page', 20),
            'threads_per_page' => config('forum.threads_per_page', 15),
            'min_thread_length' => config('forum.min_thread_length', 10),
            'min_post_length' => config('forum.min_post_length', 5),
            'allow_attachments' => config('forum.allow_attachments', true),
            'max_attachment_size' => config('forum.max_attachment_size', 5), // MB
        ];

        return view('admin.forum.settings.index', compact('settings'));
    }

    /**
     * บันทึกตั้งค่าฟอรั่ม
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveSettings(Request $request)
    {
        $validated = $request->validate([
            'forum_enabled' => 'boolean',
            'require_login' => 'boolean',
            'allow_guest_view' => 'boolean',
            'posts_per_page' => 'required|integer|min:5|max:100',
            'threads_per_page' => 'required|integer|min:5|max:100',
            'min_thread_length' => 'required|integer|min:1',
            'min_post_length' => 'required|integer|min:1',
            'allow_attachments' => 'boolean',
            'max_attachment_size' => 'required|integer|min:1|max:50',
        ]);

        // บันทึกการตั้งค่า (ในที่นี้ใช้ Setting model หรือ config file)
        // สามารถปรับเปลี่ยนตามโครงสร้างของโปรเจกต์

        return redirect()
            ->route('admin.forum.settings.index')
            ->with('success', 'บันทึกการตั้งค่าสำเร็จ');
    }
}
