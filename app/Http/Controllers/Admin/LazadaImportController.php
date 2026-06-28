<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use App\Models\User;
use App\Services\LazadaImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * นำเข้าสินค้าจาก Lazada เข้าสู่ระบบร้านค้าของเรา
 *
 * แอดมินวางลิงก์ "หน้าสินค้า" ของ Lazada (เลือกเอง) → ระบบดึงข้อมูล (preview)
 * → แอดมินติ๊กเลือก + เลือกหมวดหมู่ → นำเข้าเป็นสินค้าจริงที่ขายได้
 *
 * รูปภาพ = ลิงก์ตรงจาก Lazada CDN (ตามที่เจ้าของเลือก)
 * ตรรกะดึงข้อมูล/สร้างสินค้า/หมวดหมู่ อยู่ใน LazadaImportService (reuse ได้)
 */
class LazadaImportController extends Controller
{
    public function __construct(private LazadaImportService $importer) {}

    /** ช่วงราคาเริ่มต้นสำหรับเดโม (บาท) */
    private const DEFAULT_PRICE_MIN = 200;

    private const DEFAULT_PRICE_MAX = 15000;

    /**
     * แสดงหน้าเครื่องมือนำเข้า
     */
    public function form()
    {
        // ตรวจให้แน่ใจว่ามีหมวด "ไอที / คอมพิวเตอร์"
        $this->importer->ensureItCategory();

        $categories = ProductCategory::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.ecommerce.lazada-import.index', [
            'pageTitle' => 'นำเข้าสินค้าจาก Lazada',
            'categories' => $categories,
            'priceMin' => self::DEFAULT_PRICE_MIN,
            'priceMax' => self::DEFAULT_PRICE_MAX,
        ]);
    }

    /**
     * ดึงข้อมูลสินค้าจากลิงก์ที่วางมา (preview ก่อนนำเข้า)
     */
    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'urls' => ['required', 'string', 'max:20000'],
            'price_min' => ['nullable', 'numeric', 'min:0'],
            'price_max' => ['nullable', 'numeric', 'min:0'],
        ]);

        // แยกลิงก์ทีละบรรทัด + กันซ้ำ + จำกัดจำนวนต่อครั้ง (กัน timeout/abuse)
        $urls = collect(preg_split('/\r\n|\r|\n/', $validated['urls']))
            ->map(fn ($u) => trim($u))
            ->filter()
            ->unique()
            ->take(30)
            ->values()
            ->all();

        if (empty($urls)) {
            return response()->json(['success' => false, 'message' => 'กรุณาวางลิงก์สินค้าอย่างน้อย 1 รายการ'], 422);
        }

        $priceMin = $validated['price_min'] ?? self::DEFAULT_PRICE_MIN;
        $priceMax = $validated['price_max'] ?? self::DEFAULT_PRICE_MAX;

        $result = $this->importer->fetchMany($urls);

        $items = collect($result['ok'])->map(function (array $p) use ($priceMin, $priceMax) {
            return [
                'item_id' => $p['item_id'],
                'source_url' => $p['source_url'],
                'name' => $p['name'],
                'brand' => $p['brand'],
                'price' => $p['price'],
                'compare_at_price' => $p['compare_at_price'],
                'main_image' => $p['main_image'],
                'image_count' => count($p['images']),
                'variant_count' => count($p['variants']),
                'variants' => $p['variants'],
                'lazada_category' => $p['lazada_category'],
                'short_description' => $p['short_description'],
                'in_range' => $p['price'] >= $priceMin && $p['price'] <= $priceMax,
                'already_imported' => $this->importer->isAlreadyImported($p['item_id']),
                'suggested_category_id' => $this->importer->suggestCategoryId($p['lazada_category'], $p['name']),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'items' => $items,
            'failed' => $result['failed'],
            'price_min' => (float) $priceMin,
            'price_max' => (float) $priceMax,
        ]);
    }

    /**
     * นำเข้าสินค้าที่เลือก
     *
     * เพื่อความปลอดภัย: เรา "ดึงข้อมูลใหม่จากลิงก์" ตอนนำเข้า (ไม่เชื่อ name/price/รูป
     * ที่ส่งมาจาก client ซึ่งอาจถูกดัดแปลง) — server เป็นแหล่งความจริงเสมอ
     */
    public function import(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:30'],
            'items.*.url' => ['required', 'string'],
            'items.*.category_id' => ['required', 'integer', 'exists:product_categories,id'],
        ]);

        $sellerId = $this->resolveSellerId();
        if (! $sellerId) {
            return response()->json(['success' => false, 'message' => 'ไม่พบผู้ใช้สำหรับตั้งเป็นผู้ขาย'], 422);
        }

        $imported = [];
        $skipped = [];
        $errors = [];

        foreach ($validated['items'] as $row) {
            try {
                $data = $this->importer->fetchProductData($row['url']);

                if ($this->importer->isAlreadyImported($data['item_id'])) {
                    $skipped[] = ['name' => $data['name'], 'reason' => 'นำเข้าแล้วก่อนหน้านี้'];

                    continue;
                }

                $product = $this->importer->createProductFromData($data, $sellerId, (int) $row['category_id']);
                $imported[] = ['id' => $product->id, 'name' => $product->name, 'price' => (float) $product->price];
            } catch (\Throwable $e) {
                Log::warning('Lazada import: นำเข้าล้มเหลว', ['url' => $row['url'], 'error' => $e->getMessage()]);
                $errors[] = ['url' => $row['url'], 'error' => $e->getMessage()];
            }
        }

        return response()->json([
            'success' => true,
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
            'summary' => sprintf('นำเข้า %d รายการ, ข้าม %d, ผิดพลาด %d', count($imported), count($skipped), count($errors)),
        ]);
    }

    /**
     * เลือก seller สำหรับสินค้าที่นำเข้า — ใช้แอดมินที่ล็อกอิน
     */
    private function resolveSellerId(): ?int
    {
        $id = auth()->id();
        if ($id) {
            return $id;
        }

        $admin = User::where('role', 'admin')->first() ?? User::first();

        return $admin?->id;
    }
}
