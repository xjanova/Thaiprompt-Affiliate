<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FortunePage;
use App\Models\FortunePageRepost;
use App\Services\Fortune\FortuneRepostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * 🔁 หลังบ้าน "รีโพสลงเพจสาขา" — เปิด/ปิด ตั้งค่า ดูแผน และดูประวัติรายสาขา
 *
 * เจ้าของสั่ง (2026-11-07): "ทำส่วนควบคุม ปุ่มเปิดปิดการรีโพส และจัดการต่างๆ ไว้หลังบ้าน
 *                           และรองรับการขยายสาขาในอนาคต เพจอื่นๆ ได้ด้วย"
 *
 * ทุกเพจ Facebook ในตาราง fortune_pages โผล่ที่นี่อัตโนมัติ — เพิ่มสาขาใหม่ไม่ต้องแก้โค้ด
 */
class FortuneRepostController extends Controller
{
    public function __construct(protected FortuneRepostService $service) {}

    /**
     * หน้ารวม: การ์ดตั้งค่าทุกสาขา + ประวัติล่าสุด
     */
    public function index(Request $request): View
    {
        $pages = FortunePage::query()
            ->where('platform', 'facebook')
            ->orderByDesc('repost_enabled')
            ->orderByDesc('is_active')
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get();

        $since = Carbon::now()->subDays(7);

        // ยอดย้อนหลัง 7 วัน แยกสถานะ ต่อสาขา — คิวรีเดียว
        $stats = FortunePageRepost::query()
            ->select('fortune_page_id', 'status', DB::raw('COUNT(*) as total'))
            ->where('slot_at', '>=', $since)
            ->groupBy('fortune_page_id', 'status')
            ->get()
            ->groupBy('fortune_page_id')
            ->map(fn ($rows) => $rows->pluck('total', 'status')->all());

        $nextSlots = FortunePageRepost::query()
            ->select('fortune_page_id', DB::raw('MIN(slot_at) as next_at'))
            ->where('status', FortunePageRepost::STATUS_SCHEDULED)
            ->where('slot_at', '>', Carbon::now())
            ->groupBy('fortune_page_id')
            ->pluck('next_at', 'fortune_page_id');

        $filterPageId = $request->integer('page_id') ?: null;
        $filterStatus = in_array($request->query('status'), ['sending', 'scheduled', 'published', 'failed', 'cancelled'], true)
            ? $request->query('status')
            : null;

        $history = FortunePageRepost::query()
            ->with(['page:id,name,brand_name', 'sourcePage:id,name,brand_name'])
            ->when($filterPageId, fn ($q) => $q->where('fortune_page_id', $filterPageId))
            ->when($filterStatus, fn ($q) => $q->where('status', $filterStatus))
            ->orderByDesc('slot_at')
            ->paginate(30)
            ->withQueryString();

        $defaultSource = FortunePage::query()
            ->where('platform', 'facebook')
            ->where('is_default', true)
            ->first();

        return view('admin.fortune.reposts.index', [
            'pageTitle' => 'รีโพสลงเพจสาขา',
            'pages' => $pages,
            'stats' => $stats,
            'nextSlots' => $nextSlots,
            'history' => $history,
            'filterPageId' => $filterPageId,
            'filterStatus' => $filterStatus,
            'defaultSource' => $defaultSource,
            'defaults' => FortunePage::REPOST_DEFAULTS,
        ]);
    }

    /**
     * บันทึกการตั้งค่ารีโพสของสาขา
     */
    public function update(Request $request, FortunePage $fortunePage): RedirectResponse
    {
        $validated = $request->validate([
            'repost_source_page_id' => [
                'nullable', 'integer',
                Rule::exists('fortune_pages', 'id')->where('platform', 'facebook'),
                Rule::notIn([$fortunePage->id]),
            ],
            'repost_offset_minutes' => 'required|integer|min:5|max:720',
            'repost_max_per_day' => 'required|integer|min:0|max:50',
            'pick_order' => 'required|in:newest,oldest,random',
            'caption_suffix' => 'nullable|string|max:500',
            'exclude_keywords' => 'nullable|string|max:2000',
            'media_types' => 'required|array|min:1',
            'media_types.*' => 'in:photo,video,text',
            'lookback_days' => 'required|integer|min:1|max:180',
            'lookahead_hours' => 'required|integer|min:1|max:48',
        ], [
            'repost_source_page_id.not_in' => 'เพจต้นทางต้องไม่ใช่เพจเดียวกับสาขานี้',
            'media_types.required' => 'เลือกประเภทโพสอย่างน้อย 1 อย่าง',
            'repost_offset_minutes.min' => 'เวลาเหลื่อมต้องอย่างน้อย 5 นาที',
        ]);

        $keywords = preg_split('/[\r\n,]+/u', (string) ($validated['exclude_keywords'] ?? '')) ?: [];

        $fortunePage->fill([
            'repost_source_page_id' => $validated['repost_source_page_id'] ?? null,
            'repost_offset_minutes' => (int) $validated['repost_offset_minutes'],
            'repost_max_per_day' => (int) $validated['repost_max_per_day'],
            'repost_settings' => [
                'pick_order' => $validated['pick_order'],
                'caption_suffix' => trim((string) ($validated['caption_suffix'] ?? '')),
                'exclude_keywords' => array_values(array_unique(array_filter(array_map('trim', $keywords)))),
                'media_types' => array_values(array_unique($validated['media_types'])),
                'lookback_days' => (int) $validated['lookback_days'],
                'lookahead_hours' => (int) $validated['lookahead_hours'],
            ],
        ])->save();

        return redirect()
            ->route('admin.fortune.reposts.index', ['page_id' => $fortunePage->id])
            ->with('success', "บันทึกการตั้งค่ารีโพสของ \"{$fortunePage->display_label}\" แล้ว — มีผลรอบถัดไป (ทุก 15 นาที)");
    }

    /**
     * สวิตช์เปิด/ปิดรีโพส
     *
     * ⚠️ ปิด = ยกเลิกโพสที่ตั้งเวลาไว้ล่วงหน้าทั้งหมดด้วย
     *    ไม่งั้นแอดมินกดปิดแล้วโพสที่ตั้งไว้ใน Facebook ยังทยอยขึ้นอีกเป็นวัน = สวนคำสั่งคน
     */
    public function toggle(FortunePage $fortunePage): RedirectResponse
    {
        if (! $fortunePage->repost_enabled) {
            $problem = $this->readinessProblem($fortunePage);

            if ($problem !== null) {
                return back()->with('error', $problem);
            }

            $fortunePage->repost_enabled = true;
            $fortunePage->save();

            return back()->with('success', "เปิดรีโพสลงเพจ \"{$fortunePage->display_label}\" แล้ว — ระบบจะเริ่มตั้งเวลาในรอบถัดไป (ภายใน 15 นาที) หรือกด \"ตั้งเวลาเดี๋ยวนี้\"");
        }

        $fortunePage->repost_enabled = false;
        $fortunePage->save();

        $pending = FortunePageRepost::query()
            ->where('fortune_page_id', $fortunePage->id)
            ->where('status', FortunePageRepost::STATUS_SCHEDULED)
            ->where('slot_at', '>', Carbon::now())
            ->get();

        $cancelled = 0;
        $errors = 0;

        foreach ($pending as $repost) {
            $repost->setRelation('page', $fortunePage);
            $this->service->cancel($repost)['ok'] ? $cancelled++ : $errors++;
        }

        $message = "ปิดรีโพสลงเพจ \"{$fortunePage->display_label}\" แล้ว";

        if ($cancelled > 0) {
            $message .= " · ยกเลิกโพสที่ตั้งเวลาไว้ {$cancelled} โพส";
        }

        if ($errors > 0) {
            return back()->with('warning', $message." · ยกเลิกไม่สำเร็จ {$errors} โพส (ดูในประวัติด้านล่าง แล้วลองกดยกเลิกทีละโพส)");
        }

        return back()->with('success', $message);
    }

    /**
     * ดูแผนตัวอย่าง (ไม่โพสจริง) — ใช้ได้แม้ยังไม่เปิดสวิตช์
     */
    public function preview(FortunePage $fortunePage): JsonResponse
    {
        try {
            $report = $this->service->run($fortunePage, dryRun: true, force: true);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => 'ดูแผนไม่สำเร็จ: '.\App\Support\SafeLog::redactSecrets($e->getMessage()),
                'planned' => [],
                'skipped' => [],
            ]);
        }

        return response()->json($report);
    }

    /**
     * ตั้งเวลาเดี๋ยวนี้ (ไม่ต้องรอ cron) — ต้องเปิดสวิตช์ก่อน
     */
    public function runNow(FortunePage $fortunePage): RedirectResponse
    {
        if (! $fortunePage->repost_enabled) {
            return back()->with('error', 'เปิดสวิตช์รีโพสของสาขานี้ก่อน แล้วค่อยกดตั้งเวลา');
        }

        try {
            $this->service->reconcile($fortunePage);
            $report = $this->service->run($fortunePage);
        } catch (\Throwable $e) {
            return back()->with('error', 'ตั้งเวลาไม่สำเร็จ: '.\App\Support\SafeLog::redactSecrets($e->getMessage()));
        }

        $failed = collect($report['planned'])->where('status', FortunePageRepost::STATUS_FAILED)->count();

        return redirect()
            ->route('admin.fortune.reposts.index', ['page_id' => $fortunePage->id])
            ->with($report['ok'] && $failed === 0 ? 'success' : 'warning', $report['message']);
    }

    /**
     * ยกเลิกโพสที่ตั้งเวลาไว้ 1 โพส (ลบออกจาก Facebook)
     */
    public function cancel(FortunePageRepost $repost): RedirectResponse
    {
        try {
            $result = $this->service->cancel($repost);
        } catch (\Throwable $e) {
            $result = ['ok' => false, 'message' => 'ยกเลิกไม่สำเร็จ: '.\App\Support\SafeLog::redactSecrets($e->getMessage())];
        }

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    /**
     * เหตุผลที่ยังเปิดรีโพสไม่ได้ (null = พร้อม)
     */
    protected function readinessProblem(FortunePage $page): ?string
    {
        // 🛑 (2026-11-13) เพจที่เจ้าของสั่งหยุดโพสต์ — ห้ามบอกว่า "เปิดแล้ว" ทั้งที่ด่านกลางจะกันทุกโพสต์
        //    ต้องปลดการหยุดก่อน (เป็นการตัดสินใจของเจ้าของ) แล้วค่อยกลับมาเปิดสวิตช์
        if ((new \App\Support\FacebookPagePostingGuard)->isPaused((string) $page->external_page_id)) {
            return 'เพจนี้ถูกสั่งหยุดโพสต์ไว้ — ปลดการหยุดก่อนด้วยคำสั่ง '
                .'php artisan fortune:facebook-posting-pause --page-id='.$page->external_page_id
                .' --expected-name="ชื่อเพจบน Facebook" --resume แล้วค่อยเปิดสวิตช์';
        }

        if (! $page->is_active) {
            return 'สาขานี้ปิดใช้งานอยู่ — เปิดสาขาในหน้า "สาขา / เพจแม่หมอ" ก่อน';
        }

        if (empty($page->page_access_token)) {
            return 'เพจสาขานี้ยังไม่มี token — เชื่อมเพจในหน้า "สาขา / เพจแม่หมอ" ก่อน';
        }

        $source = $this->service->resolveSource($page);

        if (! $source) {
            return 'ยังไม่มีเพจต้นทาง — เลือกเพจต้นทางในการตั้งค่า หรือกำหนดสาขาหลัก';
        }

        if ((int) $source->id === (int) $page->id) {
            return 'สาขานี้เป็นเพจต้นทางของตัวเองไม่ได้ — เลือกเพจต้นทางอื่น';
        }

        if (empty($source->page_access_token)) {
            return "เพจต้นทาง \"{$source->display_label}\" ยังไม่มี token";
        }

        if ($page->repostConfig()['media_types'] === []) {
            return 'ยังไม่ได้เลือกประเภทโพสที่จะรีโพส — กดบันทึกการตั้งค่าก่อน';
        }

        return null;
    }
}
