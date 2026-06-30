<?php

namespace App\Http\Controllers\Admin\LazadaHub;

use App\Http\Controllers\Controller;
use App\Models\EveProductWish;
use App\Models\LazadaAutoImportQueue;
use App\Models\MarketplaceSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * Lazada Hub — นำเข้าอัตโนมัติ (คิว URL ค่อยๆ ดึง) + คำขอจากลูกค้า (Eve wishes)
 *
 * - แอดมินวางลิงก์สินค้า + ตั้งช่วงราคา/โหมด → เข้าคิว → cron ดึงทีละน้อยจนครบ
 * - ดู "ของที่ลูกค้าอยากได้" ที่น้อง Eve เก็บไว้ (ตอนค้นในร้านไม่เจอ) เพื่อตามไปหามาเติม
 */
class AutoImportController extends Controller
{
    private const MAX_ENQUEUE = 200;

    public function index()
    {
        $hasQueue = Schema::hasTable('lazada_auto_import_queue');
        $hasWishes = Schema::hasTable('eve_product_wishes');

        $counts = ['pending' => 0, 'done' => 0, 'skipped' => 0, 'failed' => 0];
        $items = collect();
        if ($hasQueue) {
            foreach (LazadaAutoImportQueue::selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status') as $s => $c) {
                $counts[$s] = (int) $c;
            }
            $items = LazadaAutoImportQueue::latest('id')->limit(60)->get();
        }

        // คำขอลูกค้า — สรุปยอดฮิต + รายการล่าสุด
        $topWishes = collect();
        $recentWishes = collect();
        if ($hasWishes) {
            $topWishes = EveProductWish::selectRaw('query, COUNT(*) as cnt, MAX(created_at) as last_at')
                ->where('status', 'pending')
                ->groupBy('query')
                ->orderByDesc('cnt')
                ->limit(25)
                ->get();
            $recentWishes = EveProductWish::latest('id')->limit(40)->get();
        }

        return view('admin.lazada-hub.auto-import.index', [
            'pageTitle' => 'นำเข้าอัตโนมัติ + คำขอลูกค้า',
            'enabled' => filter_var(MarketplaceSetting::get('lazada_auto_sync_enabled', false), FILTER_VALIDATE_BOOLEAN),
            'counts' => $counts,
            'items' => $items,
            'topWishes' => $topWishes,
            'recentWishes' => $recentWishes,
            'defaultMode' => (string) MarketplaceSetting::get('lazada_default_fulfillment_mode', 'affiliate'),
        ]);
    }

    /**
     * เพิ่มลิงก์เข้าคิว
     */
    public function enqueue(Request $request)
    {
        $data = $request->validate([
            'urls' => ['required', 'string', 'max:60000'],
            'price_min' => ['nullable', 'numeric', 'min:0'],
            'price_max' => ['nullable', 'numeric', 'min:0'],
            'fulfillment_mode' => ['nullable', 'in:affiliate,resell'],
            'batch_label' => ['nullable', 'string', 'max:100'],
        ]);

        // แยกลิงก์ทีละบรรทัด + เฉพาะ Lazada + กันซ้ำกับที่ยัง pending
        $urls = collect(preg_split('/\r\n|\r|\n/', $data['urls']))
            ->map(fn ($u) => trim($u))
            ->filter(fn ($u) => $u !== '' && preg_match('#lazada\.co\.th#i', $u))
            ->unique()
            ->take(self::MAX_ENQUEUE)
            ->values();

        if ($urls->isEmpty()) {
            return back()->with('error', 'ไม่พบลิงก์ Lazada ที่ถูกต้อง (รองรับเฉพาะ lazada.co.th)');
        }

        $existing = LazadaAutoImportQueue::where('status', 'pending')->pluck('url')->all();
        $added = 0;
        foreach ($urls as $url) {
            if (in_array($url, $existing, true)) {
                continue;
            }
            LazadaAutoImportQueue::create([
                'url' => $url,
                'price_min' => $data['price_min'] ?? null,
                'price_max' => $data['price_max'] ?? null,
                'fulfillment_mode' => $data['fulfillment_mode'] ?? null,
                'batch_label' => $data['batch_label'] ?? null,
                'status' => 'pending',
                'created_by' => auth()->id(),
            ]);
            $added++;
        }

        return back()->with('success', "เพิ่มเข้าคิว {$added} ลิงก์ — ระบบจะค่อยๆ ดึงเข้าให้ทุก 5 นาที (เปิดสวิตช์ด้านบนด้วยนะ)");
    }

    /**
     * เปิด/ปิด auto-import
     */
    public function toggle(Request $request)
    {
        $enabled = $request->boolean('enabled');
        MarketplaceSetting::set('lazada_auto_sync_enabled', $enabled ? 'true' : 'false', 'boolean', 'เปิด auto-sync ราคา/สต็อก/ออเดอร์ + auto-import คิว');

        return back()->with('success', $enabled ? 'เปิด auto-import แล้ว' : 'ปิด auto-import แล้ว');
    }

    /**
     * ลองใหม่ (failed → pending)
     */
    public function retry(LazadaAutoImportQueue $item)
    {
        $item->update(['status' => 'pending', 'attempts' => 0, 'error' => null]);

        return back()->with('success', 'ใส่กลับคิวแล้ว');
    }

    public function destroy(LazadaAutoImportQueue $item)
    {
        $item->delete();

        return back()->with('success', 'ลบออกจากคิวแล้ว');
    }

    /**
     * ล้างรายการที่จบแล้ว (done/skipped/failed)
     */
    public function clearFinished()
    {
        LazadaAutoImportQueue::whereIn('status', ['done', 'skipped', 'failed'])->delete();

        return back()->with('success', 'ล้างรายการที่จบแล้วออก');
    }

    /**
     * อัพเดทสถานะคำขอลูกค้า (fulfilled = หาให้แล้ว / none = ไม่ทำ)
     */
    public function wishStatus(Request $request, EveProductWish $wish)
    {
        $status = $request->validate(['status' => ['required', 'in:pending,fulfilled,none']])['status'];
        $wish->update(['status' => $status]);

        return back()->with('success', 'อัพเดทคำขอแล้ว');
    }
}
