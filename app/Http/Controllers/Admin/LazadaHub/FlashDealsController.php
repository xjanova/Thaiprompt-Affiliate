<?php

namespace App\Http\Controllers\Admin\LazadaHub;

use App\Http\Controllers\Controller;
use App\Http\Controllers\StorefrontController;
use App\Jobs\ScanLazadaDealsJob;
use App\Models\MarketplaceSetting;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\LazadaDealSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * ⚡ ตั้งค่า Flash Deals — "ระบบหยิบของที่ Lazada ลดราคาจริงมาขึ้นหน้าแรกเอง"
 *
 * แอดมินคุมได้ 4 อย่างจากหน้านี้:
 *   1. คำค้นที่ใช้กวาด (+ หมวดปลายทางบนหน้าร้าน)
 *   2. เกณฑ์คัดของ — ส่วนลด / ค่าคอม / ช่วงราคา / ด่านคุณภาพ (เรตติ้ง-รีวิว-ยอดขาย)
 *   3. เพดานการยิง + อายุความสดของดีล
 *   4. กดกวาดเดี๋ยวนี้ / ล้างดีลออกจากหน้าแรกทันที
 *
 * 🚨 ทุกค่าที่บันทึกจากหน้านี้ลง `marketplace_settings` และถูกอ่านกลับผ่าน
 *    `LazadaDealSettings` **ที่เดียว** — ห้ามให้ที่อื่นอ่าน config('lazada-deals.*') ตรง ๆ
 *    ไม่งั้นแอดมินแก้แล้วจะมีผลแค่บางหน้า (ดูเหตุผลเต็มใน LazadaDealSettings)
 */
class FlashDealsController extends Controller
{
    /** เพิ่มคำค้นได้สูงสุดกี่คำ — มากกว่านี้รอบกวาดจะนานเกินอายุ job */
    private const MAX_SEEDS = 40;

    public function index()
    {
        $freshHours = LazadaDealSettings::freshHours();

        $live = Product::query()
            ->publicVisible()
            ->inStock()
            ->whereNotNull('deal_verified_at')
            ->where('deal_verified_at', '>=', now()->subHours($freshHours))
            ->whereNotNull('compare_at_price')
            ->whereColumn('compare_at_price', '>', 'price')
            ->orderByDesc('deal_discount_percent')
            ->limit(60)
            ->get();

        return view('admin.lazada-hub.flash-deals.index', [
            'pageTitle' => 'Flash Deals',
            'enabled' => LazadaDealSettings::enabled(),
            'filters' => LazadaDealSettings::filters(),
            'limits' => LazadaDealSettings::limits(),
            'freshHours' => $freshHours,
            'rescanHours' => LazadaDealSettings::rescanHours(),
            'seeds' => LazadaDealSettings::seeds(),
            'categories' => ProductCategory::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'slug']),
            'live' => $live,
            'lastRun' => LazadaDealSettings::lastRun(),
            'isRunning' => Cache::has(ScanLazadaDealsJob::LOCK_KEY),
        ]);
    }

    /**
     * บันทึกค่าตั้ง
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],

            'min_discount_percent' => ['required', 'integer', 'min:1', 'max:95'],
            'max_discount_percent' => ['required', 'integer', 'min:1', 'max:99'],
            'min_commission_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'min_price' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'max_price' => ['required', 'numeric', 'min:1', 'max:1000000'],
            'min_rating' => ['required', 'numeric', 'min:0', 'max:5'],
            'min_reviews' => ['required', 'integer', 'min:0', 'max:100000'],
            'min_sold' => ['required', 'integer', 'min:0', 'max:1000000'],

            'pages_per_keyword' => ['required', 'integer', 'min:1', 'max:5'],
            'publish_limit' => ['required', 'integer', 'min:1', 'max:60'],
            'max_per_seed' => ['required', 'integer', 'min:1', 'max:20'],
            'link_budget' => ['required', 'integer', 'min:1', 'max:120'],
            'fresh_hours' => ['required', 'integer', 'min:1', 'max:168'],
            'rescan_hours' => ['required', 'integer', 'min:1', 'max:24'],

            'seeds' => ['nullable', 'array', 'max:'.self::MAX_SEEDS],
            'seeds.*.keyword' => ['nullable', 'string', 'max:120'],
            'seeds.*.category' => ['nullable', 'string', 'max:120'],
        ], [], [
            'min_discount_percent' => 'ส่วนลดขั้นต่ำ',
            'max_discount_percent' => 'ส่วนลดสูงสุด',
            'min_commission_percent' => 'ค่าคอมขั้นต่ำ',
            'fresh_hours' => 'อายุความสดของดีล',
            'rescan_hours' => 'รอบตรวจราคาซ้ำ',
        ]);

        // ── ตรวจความสมเหตุสมผลข้ามช่อง (validator รายช่องจับไม่ได้) ──
        if ($data['min_discount_percent'] > $data['max_discount_percent']) {
            return back()->withInput()
                ->withErrors(['min_discount_percent' => 'ส่วนลดขั้นต่ำต้องไม่มากกว่าส่วนลดสูงสุด — ไม่งั้นจะไม่มีสินค้าชิ้นไหนผ่านเลย']);
        }
        if ($data['min_price'] > $data['max_price']) {
            return back()->withInput()
                ->withErrors(['min_price' => 'ราคาต่ำสุดต้องไม่มากกว่าราคาสูงสุด']);
        }

        // 🚨 อายุความสดต้องยาวกว่ารอบตรวจซ้ำ ไม่งั้นดีลจะหมดอายุก่อนรอบถัดไปมาถึง
        //    ⇒ หน้าแรกจะว่างเป็นช่วง ๆ ทุกวัน โดยไม่มีอะไรฟ้องว่าตั้งค่าผิด
        if ($data['fresh_hours'] <= $data['rescan_hours']) {
            return back()->withInput()->withErrors([
                'fresh_hours' => 'อายุความสด ('.$data['fresh_hours'].' ชม.) ต้องมากกว่ารอบตรวจซ้ำ ('.$data['rescan_hours'].' ชม.) '
                    .'— แนะนำอย่างน้อย 2 เท่า เผื่อรอบกวาดพลาดไป 1 ครั้ง หน้าแรกจะได้ไม่ว่าง',
            ]);
        }

        // ── คำค้น: ตัดแถวว่าง + ตัดคำซ้ำ ──
        $seeds = [];
        $seen = [];
        foreach ($data['seeds'] ?? [] as $row) {
            $keyword = trim((string) ($row['keyword'] ?? ''));
            if ($keyword === '') {
                continue;
            }
            $key = mb_strtolower($keyword);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $category = trim((string) ($row['category'] ?? ''));
            $seeds[] = ['keyword' => $keyword, 'category' => $category !== '' ? $category : null];
        }

        // 🚨 ห้ามบันทึกคำค้นเป็นลิสต์ว่าง — LazadaDealSettings::seeds() ตีความว่าง = ถอยไปใช้ชุดใน config
        //    แอดมินที่ลบคำค้นออกหมดเพราะอยากหยุดกวาด จะได้ผลกลับหัวคือกวาดด้วยชุดปริยาย 21 คำ
        //    ไม่มีคำค้นเลย = ต้องปิดสวิตช์ใหญ่ให้แทน แล้วคงคำค้นเดิมไว้
        $enabled = (bool) ($data['enabled'] ?? false);
        if (empty($seeds)) {
            $enabled = false;
            $seeds = LazadaDealSettings::seeds();
        }

        $this->put(LazadaDealSettings::KEY_ENABLED, $enabled ? 'true' : 'false', 'boolean', 'เปิด/ปิดการกวาดดีลอัตโนมัติ');
        $this->put(LazadaDealSettings::KEY_SEEDS, json_encode($seeds, JSON_UNESCAPED_UNICODE), 'json', 'คำค้นที่ใช้กวาดดีล');

        foreach ([
            'min_discount_percent' => 'ส่วนลดขั้นต่ำ (%)',
            'max_discount_percent' => 'ส่วนลดสูงสุดที่ยังเชื่อถือได้ (%)',
            'min_commission_percent' => 'ค่าคอมขั้นต่ำ (%)',
            'min_price' => 'ราคาต่ำสุด (บาท)',
            'max_price' => 'ราคาสูงสุด (บาท)',
            'min_rating' => 'เรตติ้งขั้นต่ำ',
            'min_reviews' => 'จำนวนรีวิวขั้นต่ำ',
            'min_sold' => 'ยอดขายขั้นต่ำ',
            'pages_per_keyword' => 'กวาดกี่หน้าต่อคำค้น',
            'publish_limit' => 'เอาขึ้นหน้าแรกสูงสุดกี่ชิ้น',
            'max_per_seed' => 'คำค้นละไม่เกินกี่ชิ้น',
            'link_budget' => 'ขอลิงก์ค่าคอมสูงสุดกี่ชิ้นต่อรอบ',
            'fresh_hours' => 'ดีลสดได้กี่ชั่วโมง',
            'rescan_hours' => 'รอบตรวจราคาซ้ำ (ชม.)',
        ] as $key => $description) {
            $type = in_array($key, ['min_commission_percent', 'min_price', 'max_price', 'min_rating'], true) ? 'float' : 'integer';
            $this->put(LazadaDealSettings::PREFIX.$key, (string) $data[$key], $type, $description);
        }

        LazadaDealSettings::flush();
        Cache::forget(StorefrontController::FLASH_DEALS_CACHE_KEY);

        $message = $enabled
            ? 'บันทึกแล้ว — กวาดอัตโนมัติเปิดอยู่ · คำค้น '.count($seeds).' คำ'
            : 'บันทึกแล้ว — ปิดการกวาดอัตโนมัติ (ดีลที่ขึ้นอยู่จะค่อย ๆ หมดอายุไปเองใน '.$data['fresh_hours'].' ชม.)';

        return redirect()->route('admin.lazada-hub.flash-deals.index')->with('success', $message);
    }

    /**
     * กวาดเดี๋ยวนี้ — โยนเข้าคิว (ใช้เวลา 1-5 นาที ยิงในคำขอเว็บตรง ๆ ไม่ได้)
     */
    public function scan(Request $request)
    {
        // 🔒 กันกดรัว: Cache::add เป็น atomic บน redis — ใครได้ true คนนั้นได้ dispatch
        if (! Cache::add(ScanLazadaDealsJob::LOCK_KEY, now()->toIso8601String(), ScanLazadaDealsJob::LOCK_TTL)) {
            return back()->with('error', 'กำลังกวาดอยู่แล้ว — รอรอบนี้จบก่อน (ปกติ 1-5 นาที) แล้วรีเฟรชหน้านี้');
        }

        ScanLazadaDealsJob::dispatch();

        return back()->with('success', 'สั่งกวาดแล้ว — ใช้เวลา 1-5 นาที กดรีเฟรชหน้านี้เพื่อดูผล');
    }

    /**
     * ล้างดีลออกจากหน้าแรกทันที
     *
     * ⚠️ ล้างแค่ "ป้ายลดราคา" ไม่ได้ลบสินค้า ไม่ได้แตะราคาขาย
     *    สินค้ายังอยู่ในร้าน ยังกดซื้อได้ ยังได้ค่าคอมเหมือนเดิม
     */
    public function clear(Request $request)
    {
        $cleared = Product::where('is_affiliate', true)
            ->where('external_platform', 'lazada')
            ->whereNotNull('deal_verified_at')
            ->update([
                'compare_at_price' => null,
                'deal_discount_percent' => null,
                'deal_verified_at' => null,
            ]);

        Cache::forget(StorefrontController::FLASH_DEALS_CACHE_KEY);

        return back()->with('success', 'ล้างแล้ว '.$cleared.' ชิ้น — แถบ Flash Deals หน้าแรกจะหายไปจนกว่าจะกวาดรอบใหม่ (สินค้ายังอยู่ในร้านตามเดิม)');
    }

    /**
     * เขียนค่าลง marketplace_settings
     */
    private function put(string $key, string $value, string $type, string $description): void
    {
        MarketplaceSetting::set($key, $value, $type, $description);
    }
}
