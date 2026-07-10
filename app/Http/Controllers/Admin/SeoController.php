<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SeoMeta;
use App\Models\Setting;
use Illuminate\Http\Request;

/**
 * จัดการระบบ SEO (Meta Tags, Open Graph, Structured Data, robots)
 *
 * รองรับธีม V4 "นวลทองคำ" — หน้า index/create/edit/settings/analysis
 */
class SeoController extends Controller
{
    /**
     * แสดงรายการ SEO Meta ทั้งหมด + สถิติสรุปด้านบน
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $seoMetas = SeoMeta::orderBy('page_type')->orderBy('language')->paginate(20);

        // สถิติสรุป (คำนวณจากทุกแถว ไม่ใช่เฉพาะหน้าปัจจุบัน)
        $all = SeoMeta::all();
        $stats = [
            'total' => $all->count(),
            'indexed' => $all->where('index', true)->count(),
            'noindex' => $all->where('index', false)->count(),
            'with_structured' => $all->filter(fn ($m) => filled($m->structured_data))->count(),
        ];

        return view('admin.seo.index', compact('seoMetas', 'stats'));
    }

    /**
     * แสดงหน้าตั้งค่า SEO ทั่วเว็บไซต์ (ค่า default ที่ SeoService ใช้)
     *
     * @return \Illuminate\View\View
     */
    public function settings()
    {
        $settings = [
            'site_name' => Setting::get('site_name', config('app.name')),
            'site_description' => Setting::get('site_description', ''),
            'og_default_image' => Setting::get('og_default_image', ''),
            'twitter_default_image' => Setting::get('twitter_default_image', ''),
            'contact_email' => Setting::get('contact_email', ''),
            'logo' => Setting::get('logo', ''),
        ];

        return view('admin.seo.settings', compact('settings'));
    }

    /**
     * บันทึกการตั้งค่า SEO ทั่วเว็บไซต์
     *
     * ค่าเหล่านี้ SeoService ใช้เป็น default + ป้อน structured data (Organization/WebSite)
     * ⚠️ คง group เดิมของ key ที่มีอยู่แล้ว เพื่อไม่ให้ไปกระทบหน้า settings อื่น
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'site_name' => 'nullable|string|max:255',
            'site_description' => 'nullable|string|max:500',
            'og_default_image' => 'nullable|string|max:1024',
            'twitter_default_image' => 'nullable|string|max:1024',
            'contact_email' => 'nullable|email|max:255',
        ]);

        foreach ($validated as $key => $value) {
            // คง group เดิมถ้ามี key อยู่แล้ว มิฉะนั้นจัดเข้ากลุ่ม 'seo'
            $existingGroup = optional(Setting::where('key', $key)->first())->group;
            Setting::set($key, $value ?? '', 'string', $existingGroup ?? 'seo');
        }

        return redirect()
            ->route('admin.seo.settings')
            ->with('success', 'บันทึกการตั้งค่า SEO ทั่วเว็บไซต์สำเร็จแล้ว');
    }

    /**
     * แสดงหน้าวิเคราะห์ประสิทธิภาพ SEO (คะแนนสุขภาพ + ความสมบูรณ์ + ความครอบคลุม)
     *
     * @return \Illuminate\View\View
     */
    public function analysis()
    {
        $all = SeoMeta::all();
        $total = $all->count();

        // ── ความสมบูรณ์ของแต่ละ field (นับจำนวนแถวที่กรอกครบ) ──
        $completeness = [
            'meta_title' => $all->filter(fn ($m) => filled($m->meta_title))->count(),
            'meta_description' => $all->filter(fn ($m) => filled($m->meta_description))->count(),
            'og_image' => $all->filter(fn ($m) => filled($m->og_image))->count(),
            'twitter' => $all->filter(fn ($m) => filled($m->twitter_title) || filled($m->twitter_description))->count(),
            'canonical_url' => $all->filter(fn ($m) => filled($m->canonical_url))->count(),
            'structured_data' => $all->filter(fn ($m) => filled($m->structured_data))->count(),
        ];

        // ── สถานะ index / by-language ──
        $indexed = $all->where('index', true)->count();
        $noindex = $all->where('index', false)->count();
        $byLanguage = $all->groupBy('language')->map->count()->toArray();

        // ── คุณภาพความยาว title/description (อิงคำแนะนำ Google) ──
        $titleGood = $all->filter(fn ($m) => mb_strlen((string) $m->meta_title) >= 30 && mb_strlen((string) $m->meta_title) <= 60)->count();
        $descGood = $all->filter(fn ($m) => mb_strlen((string) $m->meta_description) >= 120 && mb_strlen((string) $m->meta_description) <= 160)->count();

        // ── คะแนนสุขภาพ SEO (0-100, ถ่วงน้ำหนัก) ──
        $score = $total === 0 ? 0 : (int) round(
            ($completeness['meta_title'] / $total) * 20 +
            ($completeness['meta_description'] / $total) * 20 +
            ($completeness['og_image'] / $total) * 15 +
            ($completeness['structured_data'] / $total) * 15 +
            ($completeness['canonical_url'] / $total) * 10 +
            ($titleGood / $total) * 10 +
            ($descGood / $total) * 10
        );

        // ── ความครอบคลุม: หน้าสำคัญที่ควรมี SEO ──
        $pageTypes = $this->getPageTypes();
        $byPageType = $all->groupBy('page_type');
        $coverage = [];
        foreach ($pageTypes as $key => $label) {
            $rows = $byPageType->get($key, collect());
            $coverage[] = [
                'page_type' => $key,
                'label' => $label,
                'has_th' => $rows->firstWhere('language', 'th') !== null,
                'has_en' => $rows->firstWhere('language', 'en') !== null,
                'configured' => $rows->isNotEmpty(),
                'indexed' => $rows->isNotEmpty() ? (bool) $rows->first()->index : null,
            ];
        }
        $configuredCount = collect($coverage)->where('configured', true)->count();

        $analysis = [
            'score' => $score,
            'total' => $total,
            'indexed' => $indexed,
            'noindex' => $noindex,
            'by_language' => $byLanguage,
            'completeness' => $completeness,
            'title_good' => $titleGood,
            'desc_good' => $descGood,
            'coverage' => $coverage,
            'configured_count' => $configuredCount,
            'page_type_total' => count($pageTypes),
            'structured_pct' => $total === 0 ? 0 : (int) round($completeness['structured_data'] / $total * 100),
        ];

        return view('admin.seo.analysis', compact('analysis'));
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
            'structured_data' => 'nullable|json',
            'index' => 'boolean',
            'follow' => 'boolean',
        ]);

        $validated['index'] = $request->boolean('index', true);
        $validated['follow'] = $request->boolean('follow', true);

        // structured_data ถูก cast เป็น array ใน model — decode JSON string เป็น array ก่อนบันทึก
        $validated['structured_data'] = $request->filled('structured_data')
            ? json_decode($request->input('structured_data'), true)
            : null;

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
            'structured_data' => 'nullable|json',
            'index' => 'boolean',
            'follow' => 'boolean',
        ]);

        $validated['index'] = $request->boolean('index');
        $validated['follow'] = $request->boolean('follow');

        // structured_data ถูก cast เป็น array ใน model — decode JSON string เป็น array ก่อนบันทึก
        $validated['structured_data'] = $request->filled('structured_data')
            ? json_decode($request->input('structured_data'), true)
            : null;

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
