<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FortuneMysticPost;
use App\Models\FortuneMysticTopic;
use App\Models\FortuneTellingSetting;
use App\Services\MysticContentAutoPostService;
use Illuminate\Http\Request;

/**
 * จัดการระบบโพสคอนเทนต์สายมูอัตโนมัติ (Admin Panel)
 *
 * Features:
 * - เปิด/ปิดระบบ (mystic_content_enabled)
 * - กำหนด schedule slots (เช่น 08:00, 20:00)
 * - ตั้ง Tavily/Brave API keys
 * - จัดการหมวดและ sub-topics
 * - ดู log โพสที่ผ่านมา
 * - กดโพสทันที (manual trigger)
 */
class FortuneMysticController extends Controller
{
    /**
     * แสดงหน้า dashboard ระบบคอนเทนต์สายมู
     */
    public function index()
    {
        $settings = FortuneTellingSetting::getSettings();
        $topics = FortuneMysticTopic::orderBy('sort_order')->get();
        $recentPosts = FortuneMysticPost::with('topic')
            ->latest()
            ->limit(20)
            ->get();

        // สถิติคร่าวๆ
        $stats = [
            'total_posts' => FortuneMysticPost::count(),
            'posted_today' => FortuneMysticPost::whereDate('post_date', today())
                ->where('status', FortuneMysticPost::STATUS_POSTED)
                ->count(),
            'failed_today' => FortuneMysticPost::whereDate('post_date', today())
                ->where('status', FortuneMysticPost::STATUS_FAILED)
                ->count(),
            'enabled_topics' => $topics->where('is_enabled', true)->count(),
        ];

        return view('admin.fortune.mystic.index', [
            'settings' => $settings,
            'topics' => $topics,
            'recentPosts' => $recentPosts,
            'stats' => $stats,
            'pageTitle' => 'คอนเทนต์สายมูอัตโนมัติ',
        ]);
    }

    /**
     * บันทึก settings (เปิด/ปิดระบบ + schedule + API keys)
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            // Toggles
            'daily_horoscope_per_day_enabled' => 'sometimes|boolean',
            'mystic_content_enabled' => 'sometimes|boolean',

            // Schedule (รับเป็น array หรือ comma-separated)
            'mystic_content_schedule' => 'nullable|array',
            'mystic_content_schedule.*' => 'string|regex:/^\d{1,2}(:\d{2})?$/',

            // Caption length + hashtag count
            'mystic_content_caption_min' => 'integer|min:100|max:2000',
            'mystic_content_caption_max' => 'integer|min:200|max:3000',
            'mystic_content_hashtag_count' => 'integer|min:0|max:10',

            // API keys (optional — เว้นว่างได้ ใช้ free duckduckgo อย่างเดียวก็ได้)
            'tavily_api_key' => 'nullable|string|max:500',
            'brave_search_api_key' => 'nullable|string|max:500',
        ]);

        $settings = FortuneTellingSetting::getSettings();

        // Coerce checkbox จาก request (form ที่ใช้ hidden 0 + checkbox 1)
        $settings->daily_horoscope_per_day_enabled = $request->boolean('daily_horoscope_per_day_enabled');
        $settings->mystic_content_enabled = $request->boolean('mystic_content_enabled');

        // Schedule — normalize เป็น "HH:00" string array (ตัด duplicate, sort)
        if (isset($validated['mystic_content_schedule'])) {
            $slots = [];
            foreach ($validated['mystic_content_schedule'] as $time) {
                $time = trim($time);
                if ($time === '') {
                    continue;
                }
                // "8" → "08:00"; "8:30" → "08:30"
                if (preg_match('/^(\d{1,2})(?::(\d{2}))?$/', $time, $m)) {
                    $hour = (int) $m[1];
                    $minute = isset($m[2]) ? (int) $m[2] : 0;
                    if ($hour >= 0 && $hour <= 23 && $minute >= 0 && $minute <= 59) {
                        $slots[] = sprintf('%02d:%02d', $hour, $minute);
                    }
                }
            }
            $settings->mystic_content_schedule = array_values(array_unique($slots));
        }

        if (isset($validated['mystic_content_caption_min'])) {
            $settings->mystic_content_caption_min = $validated['mystic_content_caption_min'];
        }
        if (isset($validated['mystic_content_caption_max'])) {
            $settings->mystic_content_caption_max = $validated['mystic_content_caption_max'];
        }
        if (isset($validated['mystic_content_hashtag_count'])) {
            $settings->mystic_content_hashtag_count = $validated['mystic_content_hashtag_count'];
        }

        // API keys — เก็บเฉพาะถ้ากรอกมา (เว้นว่าง = ไม่เปลี่ยน)
        if ($request->filled('tavily_api_key')) {
            $settings->tavily_api_key = $validated['tavily_api_key'];
        }
        if ($request->filled('brave_search_api_key')) {
            $settings->brave_search_api_key = $validated['brave_search_api_key'];
        }

        $settings->save();
        FortuneTellingSetting::clearSettingsCache();

        return redirect()
            ->route('admin.fortune.mystic.index')
            ->with('success', 'บันทึกการตั้งค่าสำเร็จ ✅');
    }

    /**
     * อัพเดทหมวดเดี่ยว (toggle เปิด/ปิด, แก้ pool)
     */
    public function updateTopic(Request $request, FortuneMysticTopic $topic)
    {
        $validated = $request->validate([
            'name_th' => 'sometimes|string|max:200',
            'description_th' => 'nullable|string|max:1000',
            'emoji' => 'nullable|string|max:10',
            'is_enabled' => 'sometimes|boolean',
            'sort_order' => 'integer|min:0',

            // Pool fields — รับเป็น textarea (1 บรรทัด/รายการ) แล้วแปลงเป็น array
            'sub_topic_pool_text' => 'nullable|string',
            'hashtag_pool_text' => 'nullable|string',
            'image_keywords_text' => 'nullable|string',
            'search_queries_text' => 'nullable|string',
        ]);

        $topic->name_th = $validated['name_th'] ?? $topic->name_th;
        $topic->description_th = $validated['description_th'] ?? $topic->description_th;
        $topic->emoji = $validated['emoji'] ?? $topic->emoji;
        $topic->is_enabled = $request->boolean('is_enabled');
        $topic->sort_order = $validated['sort_order'] ?? $topic->sort_order;

        // แปลง textarea → array (split by newline + trim + filter empty)
        $topic->sub_topic_pool = $this->textToArray($validated['sub_topic_pool_text'] ?? null, $topic->sub_topic_pool);
        $topic->hashtag_pool = $this->textToArray($validated['hashtag_pool_text'] ?? null, $topic->hashtag_pool);
        $topic->image_keywords = $this->textToArray($validated['image_keywords_text'] ?? null, $topic->image_keywords);
        $topic->search_queries = $this->textToArray($validated['search_queries_text'] ?? null, $topic->search_queries);

        $topic->save();

        return redirect()
            ->route('admin.fortune.mystic.index')
            ->with('success', "อัพเดทหมวด \"{$topic->name_th}\" สำเร็จ ✅");
    }

    /**
     * โพสทันที (manual trigger) — เรียก service ตรง ไม่ผ่าน scheduler
     */
    public function publishNow(Request $request, MysticContentAutoPostService $service)
    {
        $validated = $request->validate([
            'slot' => 'required|integer|min:0|max:23',
            'force' => 'sometimes|boolean',
        ]);

        $force = $request->boolean('force');
        $result = $service->generateAndPublish(
            slotHour: $validated['slot'],
            date: now('Asia/Bangkok'),
            force: $force
        );

        if ($result['success']) {
            // โพสใหม่สำเร็จ → มี topic/sub_topic; กรณี "โพสแล้ว ข้าม" → ไม่มี key เหล่านี้ (กัน Undefined array key)
            if (! empty($result['topic'])) {
                $msg = "โพสสำเร็จ! หมวด: {$result['topic']} — ".($result['sub_topic'] ?? '');
                if (! empty($result['url'])) {
                    $msg .= " (URL: {$result['url']})";
                }
            } else {
                $msg = $result['message'] ?? 'ดำเนินการสำเร็จ';
            }

            return redirect()->route('admin.fortune.mystic.index')
                ->with('success', $msg);
        }

        return redirect()->route('admin.fortune.mystic.index')
            ->with('error', '❌ โพสล้มเหลว: '.($result['message'] ?? 'unknown'));
    }

    /**
     * ดู caption ของโพสเดี่ยว (modal AJAX endpoint)
     */
    public function showPost(FortuneMysticPost $post)
    {
        $post->load('topic');

        return response()->json([
            'id' => $post->id,
            'post_date' => $post->post_date->toDateString(),
            'slot_hour' => $post->slot_hour,
            'topic' => $post->topic?->name_th,
            'sub_topic' => $post->sub_topic,
            'caption' => $post->caption,
            'hashtags' => $post->hashtags,
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
     * แปลง textarea (1 บรรทัด = 1 รายการ) → array
     * ถ้า input เป็น null → คืน default (รักษาของเดิม)
     */
    protected function textToArray(?string $text, ?array $default = null): array
    {
        if ($text === null) {
            return $default ?? [];
        }

        $lines = preg_split('/\r\n|\r|\n/', $text);
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
