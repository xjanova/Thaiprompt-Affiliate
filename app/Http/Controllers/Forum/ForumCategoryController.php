<?php

namespace App\Http\Controllers\Forum;

use App\Http\Controllers\Controller;
use App\Models\ForumCategory;
use App\Models\ForumThread;
use Illuminate\Http\Request;

/**
 * ForumCategoryController - จัดการหมวดหมู่ฟอรั่ม
 *
 * แสดงรายการกระทู้ในแต่ละหมวดหมู่
 */
class ForumCategoryController extends Controller
{
    /**
     * แสดงกระทู้ในหมวดหมู่
     *
     * @param string $slug
     * @return \Illuminate\View\View
     */
    public function show(string $slug)
    {
        // ดึงหมวดหมู่
        $category = ForumCategory::where('slug', $slug)
            ->active()
            ->with(['parent', 'children' => function ($query) {
                $query->active()->ordered();
            }])
            ->firstOrFail();

        // ดึงกระทู้ในหมวดหมู่ พร้อม pagination
        $threads = ForumThread::with(['user', 'lastPostUser'])
            ->where('category_id', $category->id)
            ->latestActivity()
            ->paginate(20);

        // กระทู้ปักหมุด
        $pinnedThreads = ForumThread::with(['user', 'lastPostUser'])
            ->where('category_id', $category->id)
            ->pinned()
            ->latestActivity()
            ->get();

        // Breadcrumb
        $breadcrumbs = $this->buildBreadcrumbs($category);

        return view('forum.category', compact(
            'category',
            'threads',
            'pinnedThreads',
            'breadcrumbs'
        ));
    }

    /**
     * สร้าง breadcrumbs สำหรับหมวดหมู่
     *
     * @param ForumCategory $category
     * @return array
     */
    protected function buildBreadcrumbs(ForumCategory $category): array
    {
        $breadcrumbs = [];
        $current = $category;

        while ($current) {
            array_unshift($breadcrumbs, [
                'name' => $current->name,
                'url' => route('forum.category', $current->slug),
            ]);
            $current = $current->parent;
        }

        // เพิ่มหน้าแรกฟอรั่ม
        array_unshift($breadcrumbs, [
            'name' => 'ฟอรั่ม',
            'url' => route('forum.index'),
        ]);

        return $breadcrumbs;
    }
}
