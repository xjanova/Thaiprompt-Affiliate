<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FortuneMarketingCampaign;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\FortuneAIService;
use App\Services\FortuneChannelManager;
use App\Services\FortuneChartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Fortune Marketing Controller
 *
 * จัดการแคมเปญการตลาดดูดวงอัตโนมัติ
 * ตั้งหัวข้อ + เวลา + เป้าหมาย → AI สร้างข้อความ → ส่งอัตโนมัติ
 */
class FortuneMarketingController extends Controller
{
    /**
     * แสดงรายการแคมเปญ
     */
    public function index(Request $request)
    {
        $query = FortuneMarketingCampaign::query()
            ->with('creator')
            ->orderBy('created_at', 'desc');

        // กรองตามสถานะ
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // ค้นหา
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('topic', 'like', "%{$search}%");
            });
        }

        $campaigns = $query->paginate(15)->withQueryString();

        // สถิติ
        $stats = [
            'total' => FortuneMarketingCampaign::count(),
            'active' => FortuneMarketingCampaign::active()->count(),
            'completed' => FortuneMarketingCampaign::completed()->count(),
            'total_sent' => FortuneMarketingCampaign::sum('sent_count'),
            'draft' => FortuneMarketingCampaign::draft()->count(),
        ];

        return view('admin.fortune.marketing.index', [
            'campaigns' => $campaigns,
            'stats' => $stats,
            'pageTitle' => 'การตลาดดูดวงอัตโนมัติ',
        ]);
    }

    /**
     * ฟอร์มสร้างแคมเปญใหม่
     */
    public function create()
    {
        return view('admin.fortune.marketing.form', [
            'campaign' => null,
            'pageTitle' => 'สร้างแคมเปญใหม่',
        ]);
    }

    /**
     * บันทึกแคมเปญใหม่
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'topic' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'message_template' => 'nullable|string|max:2000',
            'ai_prompt' => 'nullable|string|max:2000',
            'use_ai_generate' => 'boolean',
            'target_audience' => 'required|in:all,paid,recent,new',
            'target_limit' => 'nullable|integer|min:1|max:10000',
            'target_platform' => 'required|in:all,facebook,line',
            'schedule_type' => 'required|in:once,daily,weekly',
            'scheduled_at' => 'nullable|date',
        ]);

        $validated['use_ai_generate'] = $request->boolean('use_ai_generate');
        $validated['created_by'] = auth()->id();

        // สร้างแคมเปญ
        $campaign = FortuneMarketingCampaign::create($validated);

        // ถ้าใช้ AI → สร้างข้อความทันที
        if ($campaign->use_ai_generate && $campaign->topic) {
            $this->generateAIMessage($campaign);
        }

        return redirect()
            ->route('admin.fortune.marketing.index')
            ->with('success', "สร้างแคมเปญ \"{$campaign->name}\" สำเร็จ");
    }

    /**
     * ฟอร์มแก้ไขแคมเปญ
     */
    public function edit(FortuneMarketingCampaign $campaign)
    {
        return view('admin.fortune.marketing.form', [
            'campaign' => $campaign,
            'pageTitle' => "แก้ไข: {$campaign->name}",
        ]);
    }

    /**
     * อัปเดตแคมเปญ
     */
    public function update(Request $request, FortuneMarketingCampaign $campaign)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'topic' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'message_template' => 'nullable|string|max:2000',
            'ai_prompt' => 'nullable|string|max:2000',
            'use_ai_generate' => 'boolean',
            'target_audience' => 'required|in:all,paid,recent,new',
            'target_limit' => 'nullable|integer|min:1|max:10000',
            'target_platform' => 'required|in:all,facebook,line',
            'schedule_type' => 'required|in:once,daily,weekly',
            'scheduled_at' => 'nullable|date',
        ]);

        $validated['use_ai_generate'] = $request->boolean('use_ai_generate');
        $campaign->update($validated);

        return redirect()
            ->route('admin.fortune.marketing.index')
            ->with('success', "อัปเดตแคมเปญ \"{$campaign->name}\" สำเร็จ");
    }

    /**
     * ให้ AI สร้างข้อความ (AJAX)
     */
    public function generateMessage(Request $request, FortuneMarketingCampaign $campaign)
    {
        $this->generateAIMessage($campaign);

        return back()->with('success', 'AI สร้างข้อความสำเร็จ!');
    }

    /**
     * ดูตัวอย่างข้อความ (AJAX)
     */
    public function preview(Request $request)
    {
        $topic = $request->input('topic', 'ดูดวงฟรี');
        $aiPrompt = $request->input('ai_prompt');

        try {
            $settings = FortuneTellingSetting::getSettings();
            $aiService = new FortuneAIService($settings);

            $prompt = $aiPrompt ?: $this->buildMarketingPrompt($topic);
            $result = $aiService->generateWithRetryAndFallback(
                questions: [$prompt],
                readingType: 'basic'
            );

            return response()->json([
                'success' => true,
                'message' => $result['response'],
                'provider' => $result['provider'] ?? 'unknown',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * เปิดใช้งานแคมเปญ (ตั้งเวลา)
     */
    public function activate(FortuneMarketingCampaign $campaign)
    {
        if (! $campaign->getMessageToSend()) {
            return back()->with('error', 'ต้องมีข้อความก่อนเปิดใช้งาน (สร้างด้วย AI หรือพิมพ์เอง)');
        }

        if (! $campaign->scheduled_at && $campaign->schedule_type === 'once') {
            return back()->with('error', 'กรุณาตั้งเวลาส่งก่อน');
        }

        $campaign->activate();

        return back()->with('success', "เปิดใช้งานแคมเปญ \"{$campaign->name}\" สำเร็จ");
    }

    /**
     * หยุดชั่วคราว
     */
    public function pause(FortuneMarketingCampaign $campaign)
    {
        $campaign->pause();

        return back()->with('success', "หยุดแคมเปญ \"{$campaign->name}\" ชั่วคราว");
    }

    /**
     * ยกเลิก
     */
    public function cancel(FortuneMarketingCampaign $campaign)
    {
        $campaign->cancel();

        return back()->with('success', "ยกเลิกแคมเปญ \"{$campaign->name}\" สำเร็จ");
    }

    /**
     * ส่งทันที (manual trigger)
     */
    public function sendNow(FortuneMarketingCampaign $campaign)
    {
        if (! $campaign->getMessageToSend()) {
            return back()->with('error', 'ต้องมีข้อความก่อนส่ง');
        }

        try {
            $result = $this->executeCampaign($campaign);

            return back()->with('success', "ส่งสำเร็จ {$result['sent']} คน (ล้มเหลว {$result['failed']} คน)");
        } catch (\Exception $e) {
            return back()->with('error', 'เกิดข้อผิดพลาด: '.$e->getMessage());
        }
    }

    /**
     * ลบแคมเปญ
     */
    public function destroy(FortuneMarketingCampaign $campaign)
    {
        $name = $campaign->name;
        $campaign->delete();

        return redirect()
            ->route('admin.fortune.marketing.index')
            ->with('success', "ลบแคมเปญ \"{$name}\" สำเร็จ");
    }

    // ============================================================
    // Private Methods
    // ============================================================

    /**
     * ให้ AI สร้างข้อความการตลาด
     */
    private function generateAIMessage(FortuneMarketingCampaign $campaign): void
    {
        try {
            $settings = FortuneTellingSetting::getSettings();
            $aiService = new FortuneAIService($settings);

            $prompt = $campaign->ai_prompt ?: $this->buildMarketingPrompt($campaign->topic ?? $campaign->name);

            $result = $aiService->generateWithRetryAndFallback(
                questions: [$prompt],
                readingType: 'basic'
            );

            $campaign->update([
                'ai_generated_message' => $result['response'],
            ]);

        } catch (\Exception $e) {
            Log::error('AI generate marketing message failed', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
            ]);
            $campaign->update(['last_error' => 'AI สร้างข้อความล้มเหลว: '.$e->getMessage()]);
        }
    }

    /**
     * สร้าง prompt สำหรับ AI ทำข้อความการตลาด
     */
    private function buildMarketingPrompt(string $topic): string
    {
        return <<<PROMPT
คุณคือ "แม่หมอจันทรา" หมอดูสาวสวยอายุ 35 ปี ที่มีพลังหยั่งรู้ทำนายแม่นยำมาก
เป็นกันเอง พูดเพราะ น่าเชื่อถือ

เขียนข้อความการตลาดเชิญชวนลูกค้าเก่ามาดูดวงกับจันทรา โดยมีเนื้อหาเกี่ยวกับ:
หัวข้อ: {$topic}

กฎ:
- ใช้ภาษาไทยทั้งหมด เป็นกันเอง อบอุ่น
- ความยาว 100-200 คำ
- เริ่มด้วยทักทาย + เรื่องที่น่าสนใจ
- แทรกเรื่องดวงชะตา ราศี ให้น่าติดตาม
- ปิดด้วย call-to-action เชิญชวนมาดูดวง
- ใส่ emoji ให้ดูมีชีวิตชีวา
- ห้ามแนะนำเรื่องการลงทุน หรือขายสินค้า
- ให้ความรู้สึกเหมือนเพื่อนส่งข้อความมาบอก
- อ้างอิงดาวหรือราศีตามช่วงเวลาที่เหมาะสม
PROMPT;
    }

    /**
     * ส่งแคมเปญจริง
     */
    public function executeCampaign(FortuneMarketingCampaign $campaign): array
    {
        $campaign->markSending();

        $settings = FortuneTellingSetting::getSettings();
        $channelManager = new FortuneChannelManager($settings);
        $chartService = app(FortuneChartService::class);
        $message = $campaign->getMessageToSend();

        // ดึงรายชื่อเป้าหมาย
        $recipients = $this->getRecipients($campaign);
        $campaign->update(['total_target' => $recipients->count()]);

        $sent = 0;
        $failed = 0;

        foreach ($recipients as $recipient) {
            try {
                $platformService = $channelManager->getPlatform($recipient->platform);
                if (! $platformService) {
                    $failed++;

                    continue;
                }

                // ✅ สร้างและส่งภาพดวงดาว (Birth Chart) ก่อนข้อความ
                try {
                    $chartUrl = null;
                    $name = $recipient->facebook_user_name ?? 'คุณ';
                    $birthDate = $recipient->birth_date;

                    if ($birthDate) {
                        $chartUrl = $chartService->generateBirthChart(
                            $birthDate instanceof \Carbon\Carbon ? $birthDate->format('Y-m-d') : $birthDate,
                            $name
                        );
                    } else {
                        $chartUrl = $chartService->generateQuickChart($name);
                    }

                    if ($chartUrl) {
                        // บันทึก chart URL ลงใน reading ล่าสุดของผู้ใช้
                        FortuneReading::where('facebook_user_id', $recipient->facebook_user_id)
                            ->latest()
                            ->first()
                            ?->update(['reading_image_url' => $chartUrl]);

                        $platformService->sendImage($recipient->facebook_user_id, $chartUrl);
                        usleep(500000); // 0.5 วินาที ให้ภาพส่งก่อน
                    }
                } catch (\Exception $chartErr) {
                    // ส่ง chart ไม่ได้ ไม่ blocking → ยังส่งข้อความต่อ
                    Log::warning('Marketing campaign: ส่ง chart image ไม่สำเร็จ', [
                        'campaign_id' => $campaign->id,
                        'user_id' => $recipient->facebook_user_id,
                        'error' => $chartErr->getMessage(),
                    ]);
                }

                // ส่งข้อความคำทำนาย
                $success = $platformService->sendMessage($recipient->facebook_user_id, $message, ['from_admin' => true]);
                $success ? $sent++ : $failed++;

                // หน่วงเวลาเพื่อไม่ให้โดน rate limit
                usleep(300000); // 0.3 วินาที
            } catch (\Exception $e) {
                $failed++;
                Log::warning('Marketing campaign send failed', [
                    'campaign_id' => $campaign->id,
                    'user_id' => $recipient->facebook_user_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $campaign->markCompleted($sent, $failed);

        Log::info('Marketing campaign executed', [
            'campaign_id' => $campaign->id,
            'name' => $campaign->name,
            'sent' => $sent,
            'failed' => $failed,
        ]);

        return ['sent' => $sent, 'failed' => $failed];
    }

    /**
     * ดึงรายชื่อเป้าหมาย
     *
     * 🌙 (2026-05-25) USER RULE: ห้าม DM ลูกค้าที่ดูดวงสำเร็จในเดือนเดียวกันแล้ว
     *   → exclude facebook_user_id ที่มี is_paid=true reading created_at >= startOfMonth()
     *   → ลูกค้าจะติดต่อมาเองได้ปกติ (inbound) — แค่ไม่ถูก outbound DM ซ้ำ
     *   เหตุผล: ลูกค้าจ่ายแล้วยังโดน pitch ขายซ้ำ = สแปม + เสียประสบการณ์
     */
    private function getRecipients(FortuneMarketingCampaign $campaign)
    {
        $query = FortuneReading::query()
            ->select(
                'facebook_user_id',
                'platform',
                DB::raw('MAX(facebook_user_name) as facebook_user_name'),
                DB::raw('MAX(birth_date) as birth_date')
            )
            ->groupBy('facebook_user_id', 'platform');

        // กรอง platform
        if ($campaign->target_platform !== 'all') {
            $query->where('platform', $campaign->target_platform);
        }

        // กรองกลุ่มเป้าหมาย
        switch ($campaign->target_audience) {
            case FortuneMarketingCampaign::TARGET_PAID:
                $query->having(DB::raw('SUM(CASE WHEN is_paid = 1 THEN 1 ELSE 0 END)'), '>', 0);
                break;

            case FortuneMarketingCampaign::TARGET_RECENT:
                $query->having(DB::raw('MAX(created_at)'), '>=', now()->subDays(7));
                break;

            case FortuneMarketingCampaign::TARGET_NEW:
                $query->having(DB::raw('COUNT(*)'), '=', 1);
                break;
        }

        // 🌙 (2026-05-25) Exclude ลูกค้าที่ดูดวงสำเร็จในเดือนนี้แล้ว — ห้าม DM ซ้ำ
        //   ใช้ HAVING SUM แทน whereNotIn subquery — เพราะ query หลักมี groupBy อยู่แล้ว
        //   เงื่อนไข: ในกลุ่ม facebook_user_id เดียวกัน ต้องไม่มี row ที่
        //   is_paid=1 AND created_at >= startOfMonth() (ไม่งั้นถูกตัด)
        $monthStart = now()->startOfMonth();
        $query->havingRaw(
            'SUM(CASE WHEN is_paid = 1 AND created_at >= ? THEN 1 ELSE 0 END) = 0',
            [$monthStart]
        );

        // จำกัดจำนวน
        if ($campaign->target_limit) {
            $query->limit($campaign->target_limit);
        }

        $recipients = $query->get();

        Log::info('Fortune marketing: getRecipients post-filter', [
            'campaign_id' => $campaign->id,
            'target_audience' => $campaign->target_audience,
            'target_platform' => $campaign->target_platform,
            'recipients_count' => $recipients->count(),
            'month_start_exclude' => $monthStart->toDateString(),
            'note' => 'excluded users with is_paid=true reading this calendar month',
        ]);

        return $recipients;
    }
}
