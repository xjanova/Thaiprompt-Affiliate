<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FortuneContentCampaign;
use App\Models\FortuneContentPost;
use App\Services\ContentCampaignAutoPostService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * จัดการแคมเปญคอนเทนต์อัตโนมัติ (Admin Panel)
 *
 * Features:
 * - CRUD แคมเปญ — แนวคอนเทนต์กำหนดเองด้วยคีย์เวิร์ด หรือ custom prompt
 * - ตารางเวลาโพสรายแคมเปญ (อิสระจากกัน)
 * - เปิด/ปิดรายแคมเปญ
 * - กดโพสทันที (manual trigger)
 * - ดู log โพสย้อนหลัง + image prompt ที่ AI สร้าง
 */
class ContentCampaignController extends Controller
{
    /**
     * แสดงหน้า dashboard แคมเปญคอนเทนต์
     */
    public function index()
    {
        $campaigns = FortuneContentCampaign::withCount('posts')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $recentPosts = FortuneContentPost::with('campaign')
            ->latest()
            ->limit(30)
            ->get();

        // สถิติคร่าวๆ
        $stats = [
            'total_campaigns' => $campaigns->count(),
            'enabled_campaigns' => $campaigns->where('is_enabled', true)->count(),
            // ใช้ where ตรงๆ (คอลัมน์เป็น date อยู่แล้ว) — whereDate ครอบ DATE() ทำให้ index ใช้ไม่ได้
            'posted_today' => FortuneContentPost::where('post_date', today()->toDateString())
                ->where('status', FortuneContentPost::STATUS_POSTED)
                ->count(),
            'failed_today' => FortuneContentPost::where('post_date', today()->toDateString())
                ->where('status', FortuneContentPost::STATUS_FAILED)
                ->count(),
        ];

        return view('admin.fortune.campaigns.index', [
            'campaigns' => $campaigns,
            'recentPosts' => $recentPosts,
            'stats' => $stats,
            'pageTitle' => 'แคมเปญคอนเทนต์อัตโนมัติ',
        ]);
    }

    /**
     * สร้างแคมเปญใหม่
     */
    public function store(Request $request)
    {
        $validated = $this->validateCampaign($request);

        // slug อัตโนมัติจากชื่อ (transliterate ไม่ได้กับไทย → ใช้ random suffix กันชน)
        $slug = Str::slug($validated['name_th']);
        if ($slug === '') {
            $slug = 'campaign';
        }
        $base = $slug;
        $i = 1;
        while (FortuneContentCampaign::where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$i);
        }

        $campaign = FortuneContentCampaign::create(array_merge(
            $this->campaignAttributes($request, $validated),
            [
                'slug' => $slug,
                'sort_order' => (int) (FortuneContentCampaign::max('sort_order') + 1),
            ]
        ));

        return redirect()
            ->route('admin.fortune.content-campaigns.index')
            ->with('success', "สร้างแคมเปญ \"{$campaign->name_th}\" สำเร็จ ✅ (อย่าลืมเปิดใช้งาน)");
    }

    /**
     * อัพเดทแคมเปญ
     */
    public function update(Request $request, FortuneContentCampaign $campaign)
    {
        $validated = $this->validateCampaign($request);

        $campaign->update($this->campaignAttributes($request, $validated));

        return redirect()
            ->route('admin.fortune.content-campaigns.index')
            ->with('success', "อัพเดทแคมเปญ \"{$campaign->name_th}\" สำเร็จ ✅");
    }

    /**
     * เปิด/ปิดแคมเปญ (toggle เร็วจากหน้า list)
     */
    public function toggle(FortuneContentCampaign $campaign)
    {
        $campaign->update(['is_enabled' => ! $campaign->is_enabled]);

        $state = $campaign->is_enabled ? 'เปิด' : 'ปิด';

        return redirect()
            ->route('admin.fortune.content-campaigns.index')
            ->with('success', "{$state}แคมเปญ \"{$campaign->name_th}\" แล้ว ✅");
    }

    /**
     * 🛑 ปิดทุกแคมเปญพร้อมกัน (emergency kill switch)
     *
     * ใช้ตอนเกิดเหตุ เช่น AI พ่นเนื้อหาเพี้ยน / เพจโดน flag — หยุดโพสอัตโนมัติทั้งหมดทันที
     */
    public function disableAll()
    {
        $count = FortuneContentCampaign::where('is_enabled', true)->update(['is_enabled' => false]);

        return redirect()
            ->route('admin.fortune.content-campaigns.index')
            ->with('success', "🛑 ปิดแคมเปญทั้งหมดแล้ว ({$count} แคมเปญ) — โพสอัตโนมัติหยุดทันที");
    }

    /**
     * ลบแคมเปญ (โพสเก่าถูกลบตาม cascade — เก็บบน FB ไว้ ไม่ยุ่ง)
     */
    public function destroy(FortuneContentCampaign $campaign)
    {
        $name = $campaign->name_th;
        $campaign->delete();

        return redirect()
            ->route('admin.fortune.content-campaigns.index')
            ->with('success', "ลบแคมเปญ \"{$name}\" แล้ว");
    }

    /**
     * โพสทันที (manual trigger) — เรียก service ตรง
     */
    public function publishNow(Request $request, FortuneContentCampaign $campaign, ContentCampaignAutoPostService $service)
    {
        $validated = $request->validate([
            'slot' => ['nullable', 'string', 'regex:/^\d{1,2}:\d{2}$/'],
            'force' => 'sometimes|boolean',
        ]);

        // ไม่ระบุ slot → ใช้เวลาปัจจุบันปัดเป็น HH:MM (กดซ้ำนาทีเดิม = idempotent)
        $slot = $validated['slot'] ?? now('Asia/Bangkok')->format('H:i');

        $result = $service->generateAndPublish(
            campaign: $campaign,
            slotTime: $slot,
            date: now('Asia/Bangkok'),
            force: $request->boolean('force'),
        );

        if ($result['success']) {
            $msg = "โพสสำเร็จ! แคมเปญ: {$campaign->name_th}";
            if (! empty($result['sub_topic'])) {
                $msg .= " — {$result['sub_topic']}";
            }
            if (! empty($result['url'])) {
                $msg .= " (URL: {$result['url']})";
            }

            return redirect()->route('admin.fortune.content-campaigns.index')
                ->with('success', $msg);
        }

        return redirect()->route('admin.fortune.content-campaigns.index')
            ->with('error', '❌ โพสล้มเหลว: '.($result['message'] ?? 'unknown'));
    }

    /**
     * ดูรายละเอียดโพสเดี่ยว (modal AJAX endpoint)
     */
    public function showPost(FortuneContentPost $post)
    {
        $post->load('campaign');

        return response()->json([
            'id' => $post->id,
            'campaign' => $post->campaign?->name_th,
            'post_date' => $post->post_date->toDateString(),
            'slot_time' => $post->slot_time,
            'sub_topic' => $post->sub_topic,
            'caption' => $post->caption,
            'hashtags' => $post->hashtags,
            'image_prompt' => $post->image_prompt,
            'image_url' => $post->image_url,
            'image_provider' => $post->image_provider,
            'sources' => $post->sources,
            'search_provider' => $post->search_provider,
            'status' => $post->status,
            'fb_post_url' => $post->fb_post_url,
            'error_message' => $post->error_message,
            'posted_at' => $post->posted_at?->toDateTimeString(),
        ]);
    }

    /**
     * Validation rules ร่วมของ store/update
     */
    protected function validateCampaign(Request $request): array
    {
        $validated = $request->validate([
            'name_th' => 'required|string|max:200',
            'description_th' => 'nullable|string|max:2000',
            'emoji' => 'nullable|string|max:10',
            'is_enabled' => 'sometimes|boolean',

            // ตารางเวลา — textarea 1 บรรทัด/เวลา หรือ comma-separated
            'schedule_text' => 'nullable|string|max:1000',

            'topic_source' => 'sometimes|in:inline,mystic_topics',
            'persona' => 'nullable|string|max:200',
            'content_prompt' => 'nullable|string|max:5000',
            'image_style_hint' => 'nullable|string|max:500',

            // Pools — textarea 1 บรรทัด/รายการ
            'keywords_text' => 'nullable|string|max:3000',
            'sub_topic_pool_text' => 'nullable|string|max:5000',
            'hashtag_pool_text' => 'nullable|string|max:3000',
            'search_queries_text' => 'nullable|string|max:3000',

            'use_web_search' => 'sometimes|boolean',
            'caption_min' => 'nullable|integer|min:100|max:2000',
            'caption_max' => 'nullable|integer|min:200|max:3000',
            'hashtag_count' => 'nullable|integer|min:0|max:10',
        ]);

        // ✋ Cross-field: max ต้องไม่น้อยกว่า min (กัน prompt "ความยาว 2000-200" + โดนตัดทิ้งเงียบๆ)
        if (! empty($validated['caption_min']) && ! empty($validated['caption_max'])
            && (int) $validated['caption_max'] < (int) $validated['caption_min']) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'caption_max' => 'ความยาว caption สูงสุดต้องไม่น้อยกว่าความยาวต่ำสุด',
            ]);
        }

        return $validated;
    }

    /**
     * แปลง request → attributes สำหรับ create/update
     */
    protected function campaignAttributes(Request $request, array $validated): array
    {
        return [
            'name_th' => $validated['name_th'],
            'description_th' => $validated['description_th'] ?? null,
            'emoji' => $validated['emoji'] ?? null,
            'is_enabled' => $request->boolean('is_enabled'),
            'schedule' => $this->parseSchedule($validated['schedule_text'] ?? null),
            'topic_source' => $validated['topic_source'] ?? FortuneContentCampaign::SOURCE_INLINE,
            'persona' => $validated['persona'] ?? null,
            'content_prompt' => $validated['content_prompt'] ?? null,
            'image_style_hint' => $validated['image_style_hint'] ?? null,
            'keywords' => $this->textToArray($validated['keywords_text'] ?? null),
            'sub_topic_pool' => $this->textToArray($validated['sub_topic_pool_text'] ?? null),
            'hashtag_pool' => $this->textToArray($validated['hashtag_pool_text'] ?? null),
            'search_queries' => $this->textToArray($validated['search_queries_text'] ?? null),
            'use_web_search' => $request->boolean('use_web_search'),
            'caption_min' => $validated['caption_min'] ?? null,
            'caption_max' => $validated['caption_max'] ?? null,
            'hashtag_count' => $validated['hashtag_count'] ?? null,
        ];
    }

    /**
     * แปลง schedule textarea/comma → array "HH:MM" (normalize + dedupe + sort)
     *
     * รับ "8", "08:00", "8:30" — pattern เดียวกับระบบสายมูเดิม
     */
    protected function parseSchedule(?string $text): array
    {
        if ($text === null || trim($text) === '') {
            return [];
        }

        $parts = preg_split('/[\r\n,]+/', $text) ?: [];
        $slots = [];
        foreach ($parts as $time) {
            $time = trim($time);
            if ($time === '') {
                continue;
            }
            // ใช้ normalizer กลาง — ห้าม normalize เองที่นี่ (slot_time เป็น unique key)
            $key = FortuneContentCampaign::normalizeSlot($time);
            if ($key !== null) {
                $slots[] = $key;
            }
        }

        $slots = array_values(array_unique($slots));
        sort($slots);

        return $slots;
    }

    /**
     * แปลง textarea (1 บรรทัด = 1 รายการ) → array
     */
    protected function textToArray(?string $text): array
    {
        if ($text === null) {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
        $items = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line !== '') {
                $items[] = $line;
            }
        }

        return $items;
    }
}
