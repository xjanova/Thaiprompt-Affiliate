<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FortunePage;
use App\Services\Fortune\WebFunnelReport;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * 📈 (2026-11-14) ยอดเข้าห้องแม่หมอ — เส้นทางลูกค้าจากแชทเพจ FB ไปเว็บจันทรา ทีละขั้น
 *
 * ได้กล่องลิงก์ → กดปุ่มเอง → แตะลิงก์เข้าเว็บได้ → คุยกับแม่หมอ → ซื้อคำทำนาย
 * อ่านอย่างเดียวจาก fortune_web_funnel_events (WebFunnelTracker เป็นคนบันทึก)
 */
class FortuneWebFunnelController extends Controller
{
    /** ปุ่มช่วงเวลาสำเร็จรูป (จำนวนวันรวมวันนี้) */
    public const PRESETS = [1 => 'วันนี้', 7 => '7 วัน', 30 => '30 วัน', 90 => '90 วัน'];

    /**
     * แสดงตัวนับตามช่วงวันที่ + สาขา
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request, WebFunnelReport $report)
    {
        $validated = $request->validate([
            'days' => 'nullable|integer|in:'.implode(',', array_keys(self::PRESETS)),
            'from' => 'nullable|date_format:Y-m-d',
            'to' => 'nullable|date_format:Y-m-d',
            'page' => 'nullable|integer|min:1',
        ]);

        $today = CarbonImmutable::today();
        $days = (int) ($validated['days'] ?? 7);

        if (! empty($validated['from']) || ! empty($validated['to'])) {
            // ช่วงที่เลือกเอง — สลับให้ถ้าใส่กลับด้าน · ห้ามเลยวันนี้ · ยาวสุด MAX_DAYS วัน
            $from = CarbonImmutable::createFromFormat('Y-m-d', $validated['from'] ?? $validated['to'])->startOfDay();
            $to = CarbonImmutable::createFromFormat('Y-m-d', $validated['to'] ?? $validated['from'])->startOfDay();
            if ($from->gt($to)) {
                [$from, $to] = [$to, $from];
            }
            if ($to->gt($today)) {
                $to = $today;
            }
            if ($from->gt($to)) {
                $from = $to;
            }
            if ($from->diffInDays($to) >= WebFunnelReport::MAX_DAYS) {
                $from = $to->subDays(WebFunnelReport::MAX_DAYS - 1);
            }
            $days = null;
        } else {
            $to = $today;
            $from = $today->subDays($days - 1);
        }

        $pageId = isset($validated['page']) ? (int) $validated['page'] : null;

        $pages = Schema::hasTable('fortune_pages')
            ? FortunePage::query()->orderBy('id')->get(['id', 'name'])
            : collect();

        return view('admin.fortune.web-funnel.index', [
            'report' => $report->summary($from, $to, $pageId),
            'days' => $days,
            'presets' => self::PRESETS,
            'pages' => $pages,
            'pageId' => $pageId,
        ]);
    }
}
