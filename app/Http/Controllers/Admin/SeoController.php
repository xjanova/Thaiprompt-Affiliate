<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SeoMeta;
use Illuminate\Http\Request;

class SeoController extends Controller
{
    /**
     * Display a listing of SEO meta.
     */
    public function index()
    {
        $seoMetas = SeoMeta::orderBy('page_type')->orderBy('language')->paginate(20);

        return view('admin.seo.index', compact('seoMetas'));
    }

    /**
     * Show the form for creating new SEO meta.
     */
    public function create()
    {
        $pageTypes = $this->getPageTypes();
        $languages = $this->getLanguages();

        return view('admin.seo.create', compact('pageTypes', 'languages'));
    }

    /**
     * Store a newly created SEO meta.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'page_type' => 'required|string|max:255',
            'language' => 'required|string|max:10',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string',
            'og_image' => 'nullable|string',
            'og_type' => 'nullable|string|max:50',
            'twitter_card' => 'nullable|string|max:50',
            'twitter_title' => 'nullable|string|max:255',
            'twitter_description' => 'nullable|string',
            'twitter_image' => 'nullable|string',
            'canonical_url' => 'nullable|string',
            'index' => 'boolean',
            'follow' => 'boolean',
        ]);

        $validated['index'] = $request->boolean('index', true);
        $validated['follow'] = $request->boolean('follow', true);

        SeoMeta::create($validated);

        return redirect()
            ->route('admin.seo.index')
            ->with('success', 'เพิ่ม SEO Meta สำเร็จแล้ว');
    }

    /**
     * Show the form for editing SEO meta.
     */
    public function edit(SeoMeta $seo)
    {
        $pageTypes = $this->getPageTypes();
        $languages = $this->getLanguages();

        return view('admin.seo.edit', compact('seo', 'pageTypes', 'languages'));
    }

    /**
     * Update the specified SEO meta.
     */
    public function update(Request $request, SeoMeta $seo)
    {
        $validated = $request->validate([
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string',
            'og_image' => 'nullable|string',
            'og_type' => 'nullable|string|max:50',
            'twitter_card' => 'nullable|string|max:50',
            'twitter_title' => 'nullable|string|max:255',
            'twitter_description' => 'nullable|string',
            'twitter_image' => 'nullable|string',
            'canonical_url' => 'nullable|string',
            'index' => 'boolean',
            'follow' => 'boolean',
        ]);

        $validated['index'] = $request->boolean('index');
        $validated['follow'] = $request->boolean('follow');

        $seo->update($validated);

        return redirect()
            ->route('admin.seo.index')
            ->with('success', 'อัปเดต SEO Meta สำเร็จแล้ว');
    }

    /**
     * Remove the specified SEO meta.
     */
    public function destroy(SeoMeta $seo)
    {
        $seo->delete();

        return redirect()
            ->route('admin.seo.index')
            ->with('success', 'ลบ SEO Meta สำเร็จแล้ว');
    }

    /**
     * Get available page types
     */
    private function getPageTypes(): array
    {
        return [
            'home' => 'หน้าหลัก',
            'about' => 'เกี่ยวกับเรา',
            'contact' => 'ติดต่อเรา',
            'affiliates' => 'สมาชิก Affiliate',
            'commissions' => 'ค่าคอมมิชชั่น',
            'register' => 'สมัครสมาชิก',
            'login' => 'เข้าสู่ระบบ',
            'dashboard' => 'แดชบอร์ด',
        ];
    }

    /**
     * Get available languages
     */
    private function getLanguages(): array
    {
        return [
            'th' => 'ไทย',
            'en' => 'English',
        ];
    }
}
