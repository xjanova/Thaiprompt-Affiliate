<?php

namespace App\Http\Controllers\Forum;

use App\Http\Controllers\Controller;
use App\Models\ForumCategory;
use App\Models\ForumThread;
use Illuminate\Http\Request;

/**
 * ForumController - หน้าหลักของฟอรั่ม
 *
 * จัดการการแสดงผลหน้าแรกของฟอรั่ม รวมถึงหมวดหมู่และกระทู้ยอดนิยม
 */
class ForumController extends Controller
{
    /**
     * แสดงหน้าหลักฟอรั่ม
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // ดึงหมวดหมู่หลักพร้อมหมวดหมู่ย่อย
        $categories = ForumCategory::active()
            ->parentCategories()
            ->with(['children' => function ($query) {
                $query->active()->ordered();
            }])
            ->ordered()
            ->get();

        // กระทู้ล่าสุด
        $latestThreads = ForumThread::with(['user', 'category', 'lastPostUser'])
            ->latestActivity()
            ->limit(10)
            ->get();

        // กระทู้ยอดนิยม (ไลค์เยอะ)
        $popularThreads = ForumThread::with(['user', 'category'])
            ->popular()
            ->limit(5)
            ->get();

        // กระทู้แนะนำ
        $featuredThreads = ForumThread::with(['user', 'category'])
            ->featured()
            ->latest()
            ->limit(3)
            ->get();

        // สถิติ
        $stats = [
            'total_threads' => ForumThread::count(),
            'total_categories' => ForumCategory::active()->count(),
            'total_posts' => \App\Models\ForumPost::count(),
        ];

        return view('forum.index', compact(
            'categories',
            'latestThreads',
            'popularThreads',
            'featuredThreads',
            'stats'
        ));
    }

    /**
     * ค้นหากระทู้
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function search(Request $request)
    {
        $query = $request->input('q', '');

        $threads = ForumThread::with(['user', 'category'])
            ->when($query, function ($q) use ($query) {
                $q->where(function ($subQ) use ($query) {
                    $subQ->where('title', 'like', "%{$query}%")
                        ->orWhere('content', 'like', "%{$query}%");
                });
            })
            ->latestActivity()
            ->paginate(20);

        return view('forum.search', compact('threads', 'query'));
    }
}
