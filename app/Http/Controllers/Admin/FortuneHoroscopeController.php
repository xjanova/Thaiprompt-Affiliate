<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FortuneHoroscopeCampaign;
use App\Models\FortuneHoroscopeContent;
use App\Models\FortuneHoroscopePost;
use App\Services\FortuneHoroscopePublishService;
use App\Services\FortuneHoroscopeService;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * จัดการแคมเปญโพสดวงรายวันอัตโนมัติ
 *
 * CRUD แคมเปญ + สร้างเนื้อหา + โพสลง Facebook/LINE
 */
class FortuneHoroscopeController extends Controller
{
    /**
     * แสดงรายการแคมเปญ
     */
    public function index(Request $request)
    {
        $query = FortuneHoroscopeCampaign::with('creator')
            ->withCount(['contents', 'posts']);

        // กรองตามสถานะ
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // ค้นหา
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $campaigns = $query->latest()->paginate(15);

        // สถิติรวม
        $stats = [
            'total' => FortuneHoroscopeCampaign::count(),
            'active' => FortuneHoroscopeCampaign::active()->count(),
            'contents_today' => FortuneHoroscopeContent::whereDate('target_date', today())->generated()->count(),
            'posts_today' => FortuneHoroscopePost::whereDate('target_date', today())->posted()->count(),
        ];

        return view('admin.fortune.horoscope.index', compact('campaigns', 'stats'));
    }

    /**
     * แสดงฟอร์มสร้างแคมเปญ
     */
    public function create()
    {
        $campaign = new FortuneHoroscopeCampaign;

        return view('admin.fortune.horoscope.form', [
            'campaign' => $campaign,
            'isEdit' => false,
            'pageTitle' => 'สร้างแคมเปญดวงรายวัน',
        ]);
    }

    /**
     * บันทึกแคมเปญใหม่
     */
    public function store(Request $request)
    {
        $validated = $this->validateCampaign($request);
        $validated['created_by'] = auth()->id();

        $campaign = FortuneHoroscopeCampaign::create($validated);

        return redirect()
            ->route('admin.fortune.horoscope.index')
            ->with('success', "สร้างแคมเปญ \"{$campaign->name}\" สำเร็จ");
    }

    /**
     * แสดงฟอร์มแก้ไขแคมเปญ
     */
    public function edit(FortuneHoroscopeCampaign $campaign)
    {
        return view('admin.fortune.horoscope.form', [
            'campaign' => $campaign,
            'isEdit' => true,
            'pageTitle' => 'แก้ไขแคมเปญ: '.$campaign->name,
        ]);
    }

    /**
     * อัพเดทแคมเปญ
     */
    public function update(Request $request, FortuneHoroscopeCampaign $campaign)
    {
        $validated = $this->validateCampaign($request);
        $campaign->update($validated);

        return redirect()
            ->route('admin.fortune.horoscope.index')
            ->with('success', "อัพเดทแคมเปญ \"{$campaign->name}\" สำเร็จ");
    }

    /**
     * ลบแคมเปญ
     */
    public function destroy(FortuneHoroscopeCampaign $campaign)
    {
        $name = $campaign->name;
        $campaign->delete();

        return redirect()
            ->route('admin.fortune.horoscope.index')
            ->with('success', "ลบแคมเปญ \"{$name}\" สำเร็จ");
    }

    /**
     * เปิดใช้งานแคมเปญ
     */
    public function activate(FortuneHoroscopeCampaign $campaign)
    {
        $campaign->activate();

        return back()->with('success', "เปิดใช้งาน \"{$campaign->name}\" สำเร็จ");
    }

    /**
     * หยุดแคมเปญชั่วคราว
     */
    public function pause(FortuneHoroscopeCampaign $campaign)
    {
        $campaign->pause();

        return back()->with('success', "หยุด \"{$campaign->name}\" ชั่วคราว");
    }

    /**
     * สร้างเนื้อหาทันที (manual trigger)
     */
    public function generateNow(Request $request, FortuneHoroscopeCampaign $campaign)
    {
        $targetDate = $request->filled('date')
            ? Carbon::parse($request->date)
            : Carbon::now('Asia/Bangkok');

        // รัน sync เพื่อให้ admin เห็นผลทันที
        $service = new FortuneHoroscopeService;
        $result = $service->generateDailyContent($campaign, $targetDate);

        $msg = "สร้างเนื้อหาดวงวันที่ {$targetDate->format('d/m/Y')}: สำเร็จ {$result['success']} / ล้มเหลว {$result['failed']}";

        if ($result['failed'] > 0) {
            return back()->with('warning', $msg);
        }

        return back()->with('success', $msg);
    }

    /**
     * โพสทันที (manual trigger)
     */
    public function publishNow(FortuneHoroscopeCampaign $campaign)
    {
        $targetDate = Carbon::now('Asia/Bangkok');

        $publishService = new FortuneHoroscopePublishService;
        $result = $publishService->createAndPublishPosts($campaign, $targetDate);

        $msg = "โพสสำเร็จ {$result['posts_published']} / สร้าง {$result['posts_created']}";

        if (! empty($result['errors'])) {
            $msg .= ' | ข้อผิดพลาด: '.implode(', ', $result['errors']);

            return back()->with('warning', $msg);
        }

        return back()->with('success', $msg);
    }

    /**
     * ประวัติเนื้อหาที่สร้าง
     */
    public function contentHistory(Request $request, FortuneHoroscopeCampaign $campaign)
    {
        $date = $request->filled('date')
            ? Carbon::parse($request->date)
            : Carbon::now('Asia/Bangkok');

        $contents = FortuneHoroscopeContent::where('campaign_id', $campaign->id)
            ->whereDate('target_date', $date)
            ->orderBy('birth_day')
            ->get();

        return view('admin.fortune.horoscope.content-history', [
            'campaign' => $campaign,
            'contents' => $contents,
            'selectedDate' => $date,
        ]);
    }

    /**
     * ประวัติการโพส
     */
    public function postHistory(Request $request, FortuneHoroscopeCampaign $campaign)
    {
        $posts = $campaign->posts()
            ->latest()
            ->paginate(20);

        return view('admin.fortune.horoscope.post-history', [
            'campaign' => $campaign,
            'posts' => $posts,
        ]);
    }

    /**
     * Preview เนื้อหาดวงวันนี้ (AJAX)
     */
    public function previewContent(FortuneHoroscopeCampaign $campaign, ?string $date = null)
    {
        $targetDate = $date ? Carbon::parse($date) : Carbon::now('Asia/Bangkok');

        $contents = FortuneHoroscopeContent::where('campaign_id', $campaign->id)
            ->whereDate('target_date', $targetDate)
            ->where('status', FortuneHoroscopeContent::STATUS_GENERATED)
            ->orderBy('birth_day')
            ->get();

        if ($contents->isEmpty()) {
            return response()->json(['html' => '<p class="text-gray-500 dark:text-gray-400">ยังไม่มีเนื้อหาสำหรับวันนี้</p>']);
        }

        $publishService = new FortuneHoroscopePublishService;
        $previewText = $publishService->composePostContent($campaign, $contents, $targetDate);

        return response()->json([
            'html' => nl2br(e($previewText)),
            'contents_count' => $contents->count(),
            'images' => $contents->pluck('image_url')->filter()->values(),
        ]);
    }

    /**
     * Validate campaign data
     */
    protected function validateCampaign(Request $request): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'post_to_facebook' => 'boolean',
            'post_to_line' => 'boolean',
            'use_fortune_settings_tokens' => 'boolean',
            'facebook_page_id' => 'nullable|string|max:255',
            'facebook_page_token' => 'nullable|string',
            'line_channel_access_token' => 'nullable|string',
            'ai_text_provider' => 'nullable|string|max:50',
            'text_prompt_template' => 'nullable|string',
            'ai_image_provider' => 'nullable|string|max:50',
            'ai_image_model' => 'nullable|string|max:50',
            'image_size' => 'nullable|string|max:20',
            'image_style' => 'nullable|string|max:50',
            'image_prompt_template' => 'nullable|string',
            'schedule_time' => 'nullable|string|max:5',
            'timezone' => 'nullable|string|max:50',
            'active_days' => 'nullable|array',
            'active_days.*' => 'integer|between:0,6',
            // 🌙 0-6 = วันในสัปดาห์ · 7 = พุธกลางคืน (ราหู) วันเกิดที่ 8 ตามตำราไทย
            //    ⚠️ `active_days` ด้านบนคือ "วันที่โพส" (ปฏิทิน) ต้องคง 0-6 ห้ามขยายตาม
            'target_birth_days' => 'nullable|array',
            'target_birth_days.*' => 'integer|between:0,7',
            'include_image' => 'boolean',
            'include_lucky_info' => 'boolean',
            'post_format' => 'nullable|string|in:single,combined',
            'post_header_template' => 'nullable|string',
            'post_footer_template' => 'nullable|string',
            // Smart Marketing fields
            'enable_auto_hashtags' => 'boolean',
            'custom_hashtags' => 'nullable|string',
            'enable_cta' => 'boolean',
            'cta_text' => 'nullable|string',
            'enable_engagement_hooks' => 'boolean',
            'page_name' => 'nullable|string|max:255',
            'page_mention' => 'nullable|string|max:255',
            // LINE Quota fields
            'line_monthly_quota' => 'nullable|integer|min:0',
            'line_quota_warning_threshold' => 'nullable|integer|min:0',
        ]);

        // boolean defaults
        $validated['post_to_facebook'] = $request->boolean('post_to_facebook');
        $validated['post_to_line'] = $request->boolean('post_to_line');
        $validated['use_fortune_settings_tokens'] = $request->boolean('use_fortune_settings_tokens', true);
        $validated['include_image'] = $request->boolean('include_image', true);
        $validated['include_lucky_info'] = $request->boolean('include_lucky_info', true);
        $validated['enable_auto_hashtags'] = $request->boolean('enable_auto_hashtags', true);
        $validated['enable_cta'] = $request->boolean('enable_cta', true);
        $validated['enable_engagement_hooks'] = $request->boolean('enable_engagement_hooks', true);

        return $validated;
    }
}
