<?php

namespace App\Http\Controllers\Admin\LazadaHub;

use App\Http\Controllers\Controller;
use App\Models\MarketplacePlatform;
use App\Models\MarketplaceProduct;
use App\Models\MarketplaceSetting;
use App\Services\LazadaImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Lazada Hub — แคตตาล็อกสินค้า
 *
 * รวมสินค้า Lazada (ตาราง marketplace_products) แสดง ต้นทุน/markup/ราคาเรา/กำไร/โหมด
 *  - นำเข้าด้วยการวางลิงก์ (scrape) → สร้างเป็นรายการในแคตตาล็อก (source=scrape, account_id=null)
 *  - แก้ราคา/markup/โหมด รายตัว (inline)
 *  - ปุ่ม sync ราคา = ดึงราคาล่าสุดจาก Lazada มาทับ (ถ้าไม่ได้ตั้งราคาเอง)
 */
class CatalogController extends Controller
{
    public function __construct(private LazadaImportService $importer) {}

    /** จำนวนลิงก์สูงสุดต่อการนำเข้า 1 ครั้ง (กัน timeout/abuse) */
    private const MAX_IMPORT = 30;

    /**
     * รายการสินค้าในแคตตาล็อก
     */
    public function index(Request $request)
    {
        $platformId = $this->lazadaPlatformId();

        $query = MarketplaceProduct::query()
            ->where('platform_id', $platformId);

        // ฟิลเตอร์
        if ($mode = $request->query('mode')) {
            if (in_array($mode, ['affiliate', 'resell'], true)) {
                $query->where('fulfillment_mode', $mode);
            }
        }
        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($w) use ($search) {
                $w->where('name', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%");
            });
        }

        $products = $query->latest()->paginate(20)->withQueryString();

        // สถิติย่อ
        $base = MarketplaceProduct::where('platform_id', $platformId);
        $stats = [
            'total' => (clone $base)->count(),
            'affiliate' => (clone $base)->where('fulfillment_mode', 'affiliate')->count(),
            'resell' => (clone $base)->where('fulfillment_mode', 'resell')->count(),
            'published' => (clone $base)->whereNotNull('internal_product_id')->count(),
        ];

        return view('admin.lazada-hub.catalog.index', [
            'pageTitle' => 'แคตตาล็อก Lazada',
            'products' => $products,
            'stats' => $stats,
            'filters' => ['mode' => $request->query('mode'), 'q' => $search],
            'defaults' => $this->priceDefaults(),
        ]);
    }

    /**
     * หน้านำเข้าเข้าแคตตาล็อก (วางลิงก์)
     */
    public function importForm()
    {
        return view('admin.lazada-hub.catalog.import', [
            'pageTitle' => 'นำเข้าเข้าแคตตาล็อก Lazada',
            'defaults' => $this->priceDefaults(),
        ]);
    }

    /**
     * พรีวิวก่อนนำเข้า (scrape) — คืนต้นทุน + ราคาเราที่คำนวณให้
     */
    public function importPreview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'urls' => ['required', 'string', 'max:20000'],
        ]);

        $urls = $this->splitUrls($validated['urls']);
        if (empty($urls)) {
            return response()->json(['success' => false, 'message' => 'กรุณาวางลิงก์อย่างน้อย 1 รายการ'], 422);
        }

        $d = $this->priceDefaults();
        $result = $this->importer->fetchMany($urls);

        $items = collect($result['ok'])->map(function (array $p) use ($d) {
            $cost = (float) $p['price'];

            return [
                'item_id' => $p['item_id'],
                'source_url' => $p['source_url'],
                'name' => $p['name'],
                'brand' => $p['brand'],
                'cost_price' => $cost,
                'selling_price' => MarketplaceProduct::computeSellingPrice($cost, $d['markup'], $d['rounding']),
                'main_image' => $p['main_image'],
                'variant_count' => count($p['variants']),
                'lazada_category' => $p['lazada_category'],
                'already' => $this->existsInCatalog($p['item_id']),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'items' => $items,
            'failed' => $result['failed'],
            'defaults' => $d,
        ]);
    }

    /**
     * นำเข้าจริง — ดึงข้อมูลใหม่ฝั่ง server (ไม่เชื่อ client) แล้วสร้าง marketplace_products
     */
    public function importStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:'.self::MAX_IMPORT],
            'items.*.url' => ['required', 'string'],
            'items.*.mode' => ['nullable', 'in:affiliate,resell'],
        ]);

        $platformId = $this->lazadaPlatformId();
        $d = $this->priceDefaults();

        $imported = [];
        $skipped = [];
        $errors = [];

        foreach ($validated['items'] as $row) {
            try {
                $data = $this->importer->fetchProductData($row['url']);

                if ($this->existsInCatalog($data['item_id'])) {
                    $skipped[] = ['name' => $data['name'], 'reason' => 'มีในแคตตาล็อกแล้ว'];

                    continue;
                }

                $cost = (float) $data['price'];
                $mode = $row['mode'] ?? $d['mode'];

                $product = MarketplaceProduct::create([
                    'account_id' => null,
                    'platform_id' => $platformId,
                    'fulfillment_mode' => $mode,
                    'external_product_id' => $data['item_id'],
                    'name' => mb_substr($data['name'], 0, 500),
                    'description' => $data['description_html'] ?? null,
                    'brand' => $data['brand'],
                    'category' => $data['lazada_category'] ?: null,
                    'price' => $cost,
                    'original_price' => $data['compare_at_price'],
                    'cost_price' => $cost,
                    'selling_price' => MarketplaceProduct::computeSellingPrice($cost, $d['markup'], $d['rounding']),
                    'markup_percent' => $d['markup'],
                    'price_is_manual' => false,
                    'currency' => 'THB',
                    'stock_quantity' => 0,
                    'is_available' => true,
                    'main_image_url' => $data['main_image'],
                    'images' => $data['images'],
                    'variants' => $data['variants'],
                    'affiliate_url' => $data['source_url'],
                    'commission_rate' => $d['commission'],
                    'source' => 'scrape',
                    'source_url' => $data['source_url'],
                    'sync_status' => 'synced',
                    'is_active' => true,
                    'last_synced_at' => now(),
                ]);

                $imported[] = ['id' => $product->id, 'name' => $product->name];
            } catch (\Throwable $e) {
                Log::warning('Lazada Hub: นำเข้าแคตตาล็อกล้มเหลว', ['url' => $row['url'], 'error' => $e->getMessage()]);
                $errors[] = ['url' => $row['url'], 'error' => $e->getMessage()];
            }
        }

        return response()->json([
            'success' => true,
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
            'summary' => sprintf('นำเข้า %d, ข้าม %d, ผิดพลาด %d', count($imported), count($skipped), count($errors)),
        ]);
    }

    /**
     * แก้ราคา/markup/โหมด รายตัว (inline)
     */
    public function update(Request $request, MarketplaceProduct $product): JsonResponse
    {
        $this->authorizeLazada($product);

        $validated = $request->validate([
            'fulfillment_mode' => ['required', 'in:affiliate,resell'],
            'markup_percent' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'selling_price' => ['nullable', 'numeric', 'min:0'],
            'price_is_manual' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $d = $this->priceDefaults();
        $cost = (float) ($product->cost_price ?? $product->price);
        $markup = $validated['markup_percent'] !== null ? (float) $validated['markup_percent'] : (float) ($product->markup_percent ?? $d['markup']);
        $manual = (bool) ($validated['price_is_manual'] ?? false);

        $product->fulfillment_mode = $validated['fulfillment_mode'];
        $product->markup_percent = $markup;
        $product->is_active = (bool) ($validated['is_active'] ?? $product->is_active);

        if ($manual && isset($validated['selling_price'])) {
            $product->selling_price = (float) $validated['selling_price'];
            $product->price_is_manual = true;
        } else {
            $product->price_is_manual = false;
            $product->selling_price = MarketplaceProduct::computeSellingPrice($cost, $markup, $d['rounding']);
        }

        $product->save();

        return response()->json([
            'success' => true,
            'message' => 'บันทึกแล้ว',
            'product' => $this->rowPayload($product->fresh()),
        ]);
    }

    /**
     * ดึงราคาล่าสุดจาก Lazada มาทับ (เฉพาะที่ไม่ได้ตั้งราคาเอง)
     */
    public function syncPrice(MarketplaceProduct $product): JsonResponse
    {
        $this->authorizeLazada($product);

        if (! $product->source_url) {
            return response()->json(['success' => false, 'message' => 'ไม่มีลิงก์ต้นทางสำหรับ sync'], 422);
        }

        try {
            $data = $this->importer->fetchProductData($product->source_url);
            $cost = (float) $data['price'];
            $d = $this->priceDefaults();

            $product->price = $cost;
            $product->cost_price = $cost;
            $product->original_price = $data['compare_at_price'];
            if (! $product->price_is_manual) {
                $markup = (float) ($product->markup_percent ?? $d['markup']);
                $product->selling_price = MarketplaceProduct::computeSellingPrice($cost, $markup, $d['rounding']);
            }
            $product->last_synced_at = now();
            $product->sync_status = 'synced';
            $product->save();

            return response()->json([
                'success' => true,
                'message' => 'อัพเดทราคาแล้ว',
                'product' => $this->rowPayload($product->fresh()),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Lazada Hub: sync ราคาล้มเหลว', ['product_id' => $product->id, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'sync ไม่สำเร็จ: '.$e->getMessage()], 200);
        }
    }

    /**
     * ลบสินค้าออกจากแคตตาล็อก
     */
    public function destroy(MarketplaceProduct $product): JsonResponse
    {
        $this->authorizeLazada($product);
        $product->delete();

        return response()->json(['success' => true, 'message' => 'ลบแล้ว']);
    }

    // ════════════════════════════════════════════════════════════
    //  Helpers
    // ════════════════════════════════════════════════════════════

    /**
     * ค่าเริ่มต้นด้านราคา (จากตั้งค่า)
     */
    private function priceDefaults(): array
    {
        return [
            'markup' => (float) MarketplaceSetting::get('lazada_default_markup_percent', 30),
            'rounding' => (string) MarketplaceSetting::get('lazada_price_rounding', 'baht'),
            'mode' => (string) MarketplaceSetting::get('lazada_default_fulfillment_mode', 'affiliate'),
            'commission' => (float) MarketplaceSetting::get('affiliate_commission_default', 5),
        ];
    }

    /**
     * payload 1 แถวสำหรับตอบกลับ AJAX
     */
    private function rowPayload(MarketplaceProduct $p): array
    {
        return [
            'id' => $p->id,
            'fulfillment_mode' => $p->fulfillment_mode,
            'cost_price' => (float) $p->effective_cost,
            'selling_price' => (float) $p->effective_selling_price,
            'markup_percent' => (float) ($p->markup_percent ?? 0),
            'price_is_manual' => (bool) $p->price_is_manual,
            'profit' => (float) $p->profit,
            'profit_percent' => (float) $p->profit_percent,
            'is_active' => (bool) $p->is_active,
            'commission_rate' => (float) ($p->commission_rate ?? 0),
        ];
    }

    /**
     * id ของแพลตฟอร์ม Lazada (firstOrCreate กันกรณี seeder ยังไม่รัน)
     */
    private function lazadaPlatformId(): int
    {
        return MarketplacePlatform::firstOrCreate(
            ['slug' => 'lazada'],
            ['name' => 'Lazada', 'is_active' => true]
        )->id;
    }

    /**
     * เคยมีในแคตตาล็อกแล้วหรือยัง (ดู external_product_id ภายใน Lazada)
     */
    private function existsInCatalog(string $itemId): bool
    {
        return MarketplaceProduct::where('platform_id', $this->lazadaPlatformId())
            ->where('external_product_id', $itemId)
            ->exists();
    }

    /**
     * แยกลิงก์ทีละบรรทัด + กันซ้ำ + จำกัดจำนวน
     */
    private function splitUrls(string $raw): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $raw))
            ->map(fn ($u) => trim($u))
            ->filter()
            ->unique()
            ->take(self::MAX_IMPORT)
            ->values()
            ->all();
    }

    /**
     * กันแก้สินค้าของแพลตฟอร์มอื่นผ่าน route ของ Lazada Hub
     */
    private function authorizeLazada(MarketplaceProduct $product): void
    {
        abort_unless($product->platform_id === $this->lazadaPlatformId(), 404);
    }
}
