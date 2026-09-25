<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\AccountingActivityLog;
use App\Models\Product;
use App\Models\VendorStore;
use App\Services\Pricing\PricingEngine;
use App\Services\Pricing\StrategyAdvisor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * วางแผนราคา & กลยุทธ์ (แผงผู้ขาย)
 *
 * ทุกตัวเลขมาจาก PricingEngine / StrategyAdvisor ชุดเดียวกับตอนแบ่งเงินจริง
 * (อัตรา GP, VAT ของร้าน, ค่าแนะนำ) — หน้าเว็บห้ามคำนวณสูตรเอง
 *
 *  - GET  seller.pricing.planner  หน้าวางแผนราคา 3 กลยุทธ์ (เจาะตลาด / สมดุล / พรีเมียม)
 *  - POST seller.pricing.quote    คำนวณการแบ่งเงินของราคาเดียว (ฟอร์มสินค้าเรียกแบบ debounce)
 *  - POST seller.pricing.plan     วางแผน 3 กลยุทธ์ + คำแนะนำ/คำเตือนภาษาไทย
 *  - POST seller.pricing.apply    "ใช้ราคานี้กับสินค้า" (เฉพาะสินค้าของตัวเอง + บันทึกประวัติ)
 *
 * อัตรา GP ผู้ขายกำหนดเองไม่ได้ — ระบบอ่านจากแพลตฟอร์ม (โปรฯ GP ฟรีช่วงเปิดตัว → แพ็กเกจร้าน → ค่ากลาง)
 */
class PricingPlannerController extends Controller
{
    /** ราคาสูงสุดที่ยอมให้ตั้ง/คำนวณ (บาท) กันตัวเลขผิดพลาด */
    public const MAX_PRICE = 10000000;

    /**
     * หน้าวางแผนราคา & กลยุทธ์
     */
    public function index(Request $request)
    {
        $userId = (int) $request->user()->id;
        $store = $this->storeFor($userId);
        $engine = app(PricingEngine::class);
        $advisor = new StrategyAdvisor($engine);

        // รายการสินค้าของร้าน (ใช้เติมค่าอัตโนมัติ) — จำกัดจำนวนกันหน้าโหลดช้า
        $products = Product::where('seller_id', $userId)
            ->orderBy('name')
            ->limit(500)
            // admin_gp_rate ต้องอยู่ในรายการคอลัมน์ ไม่งั้นหน้าแสดงอัตรา GP ผิดสำหรับสินค้าที่แอดมินตั้งอัตราไว้
            ->get(['id', 'name', 'sku', 'price', 'cost_price', 'category_id', 'seller_id', 'store_id', 'is_blocked', 'admin_gp_rate']);

        $product = $request->filled('product_id')
            ? $products->firstWhere('id', (int) $request->input('product_id'))
            : null;

        $gpInfo = $engine->gpRateInfoForProduct($product ?? $this->draftProduct($userId, $store));

        $productOptions = $products->map(fn (Product $p) => [
            'id' => (int) $p->id,
            'name' => (string) $p->name,
            'sku' => (string) ($p->sku ?? ''),
            'price' => round((float) $p->price, 2),
            'cost' => $p->cost_price !== null ? round((float) $p->cost_price, 2) : null,
            'blocked' => (bool) $p->is_blocked,
        ])->values();

        return view('seller.pricing.planner', [
            'store' => $store,
            'product' => $product,
            'productOptions' => $productOptions,
            'gpInfo' => $gpInfo,
            'gpPromoActive' => $engine->gpPromoActive(),
            'gpPromoEndsAt' => $engine->gpPromoEndsAt(),
            'packages' => $this->safePackages($advisor),
            'formulaSteps' => PricingEngine::formulaStepsTh(),
            'vatRegistered' => $engine->storeVatRegistered($store),
            'mlmEnabled' => $engine->mlmEnabled(),
            'maxPrice' => self::MAX_PRICE,
        ]);
    }

    /**
     * คำนวณการแบ่งเงินของราคาเดียว (JSON)
     *
     * ฟิลด์: price*, quantity, cost, product_id, shipping_fee, pv (ใช้เฉพาะฟอร์มสร้างสินค้าเมื่อเปิดระบบแนะนำ)
     */
    public function quote(Request $request): JsonResponse
    {
        $data = $request->validate([
            'price' => 'required|numeric|min:0|max:'.self::MAX_PRICE,
            'quantity' => 'nullable|integer|min:1|max:9999',
            'cost' => 'nullable|numeric|min:0|max:'.self::MAX_PRICE,
            'product_id' => 'nullable|integer',
            'shipping_fee' => 'nullable|numeric|min:0|max:100000',
            'pv' => 'nullable|numeric|min:0|max:1000000',
        ], $this->messages());

        $userId = (int) $request->user()->id;
        $engine = app(PricingEngine::class);

        try {
            $product = $this->ownedProduct($userId, $data['product_id'] ?? null);
            $subject = $product ?? $this->draftProduct($userId, $this->storeFor($userId), $data['pv'] ?? null);

            $overrides = [];
            if (isset($data['cost']) && (float) $data['cost'] > 0) {
                $overrides['cost_per_unit'] = (float) $data['cost'];
            } else {
                // ไม่กรอกต้นทุน = ไม่คำนวณกำไร (ไม่ใช้ต้นทุนเดิมของสินค้าแทน กันผู้ขายสับสน)
                $overrides['cost_per_unit'] = null;
            }
            if (isset($data['shipping_fee'])) {
                $overrides['shipping_fee'] = (float) $data['shipping_fee'];
            }
            if ($product !== null && isset($data['pv']) && $engine->mlmEnabled()) {
                // ฟอร์มแก้ไขสินค้า: ใช้ PV ที่กำลังพิมพ์ (ยังไม่บันทึก)
                $overrides['pv'] = (float) $data['pv'];
            }

            $breakdown = $engine->breakdown(
                (float) $data['price'],
                (int) ($data['quantity'] ?? 1),
                $engine->optionsForProduct($subject, $overrides)
            );

            $gpInfo = $engine->gpRateInfoForProduct($subject);

            return response()->json([
                'success' => true,
                'message' => 'คำนวณแล้ว',
                'data' => $breakdown->toArray() + [
                    'gp_label_th' => $gpInfo['label_th'],
                    'gp_source' => $gpInfo['source'],
                    'gp_promo_active' => $engine->gpPromoActive(),
                ],
            ]);
        } catch (InvalidArgumentException $e) {
            return $this->fail('INVALID_INPUT', $e->getMessage(), 422);
        } catch (\Throwable $e) {
            Log::error('Seller pricing quote failed', ['user_id' => $userId, 'error' => $e->getMessage()]);

            return $this->fail('QUOTE_FAILED', 'คำนวณราคาไม่สำเร็จ กรุณาลองใหม่อีกครั้ง', 500);
        }
    }

    /**
     * วางแผนราคา 3 กลยุทธ์ (JSON)
     */
    public function plan(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => 'nullable|integer',
            'cost' => 'required|numeric|min:0|max:'.self::MAX_PRICE,
            'target_margin_percent' => 'nullable|numeric|min:0|max:90',
            'competitor_price' => 'nullable|numeric|min:0|max:'.self::MAX_PRICE,
            'monthly_volume' => 'nullable|integer|min:0|max:10000000',
            'fixed_monthly_cost' => 'nullable|numeric|min:0|max:'.self::MAX_PRICE,
            'delivery' => 'nullable|in:none,rider,parcel',
            'delivery_fee' => 'nullable|numeric|min:0|max:100000',
            'avg_distance_km' => 'nullable|numeric|min:0|max:100',
            'current_price' => 'nullable|numeric|min:0|max:'.self::MAX_PRICE,
            'referral_pool' => 'nullable|boolean',
        ], $this->messages());

        $userId = (int) $request->user()->id;
        $engine = app(PricingEngine::class);
        $advisor = new StrategyAdvisor($engine);
        $store = $this->storeFor($userId);

        try {
            $product = $this->ownedProduct($userId, $data['product_id'] ?? null);
            $subject = $product ?? $this->draftProduct($userId, $store);
            $gpInfo = $engine->gpRateInfoForProduct($subject);
            $pvInfo = $product !== null ? $engine->pvInfoForProduct($product) : ['pv' => 0.0, 'commission_per_pv' => null];

            // สวิตช์ "รวมค่าแนะนำ" (ฟีเจอร์เฉพาะเว็บ) — ไม่ส่งมา = ตามค่าระบบ
            $referralPool = array_key_exists('referral_pool', $data) && $data['referral_pool'] !== null
                ? (bool) $data['referral_pool']
                : $engine->mlmEnabled();

            $input = [
                'cost' => (float) $data['cost'],
                'target_margin_percent' => $data['target_margin_percent'] ?? null,
                'competitor_price' => $data['competitor_price'] ?? null,
                'monthly_volume' => $data['monthly_volume'] ?? null,
                'fixed_monthly_cost' => $data['fixed_monthly_cost'] ?? null,
                'delivery' => $data['delivery'] ?? 'none',
                'delivery_fee' => $data['delivery_fee'] ?? null,
                'avg_distance_km' => $data['avg_distance_km'] ?? null,
                'current_price' => $data['current_price'] ?? ($product !== null ? (float) $product->price : null),
                'gp_rate' => (float) $gpInfo['rate'],
                'pv' => (float) $pvInfo['pv'],
                'mlm_enabled' => $referralPool,
                'vat_registered' => $engine->storeVatRegistered($store),
                'compare_gp_rates' => $this->safePackages($advisor),
            ];
            if (! empty($pvInfo['commission_per_pv'])) {
                $input['commission_per_pv'] = (float) $pvInfo['commission_per_pv'];
            }
            // ค่า null ไม่ส่งเข้า advisor (ให้ใช้ค่าเริ่มต้นของมันเอง)
            $input = array_filter($input, fn ($v) => $v !== null);

            $plan = $advisor->plan($input);

            $market = null;
            if ($product !== null && $product->category_id) {
                $market = $advisor->marketReferenceForCategory((int) $product->category_id, $userId);
            }

            return response()->json([
                'success' => true,
                'message' => 'วางแผนราคาแล้ว',
                'data' => $plan + [
                    'gp' => $gpInfo,
                    'gp_promo_active' => $engine->gpPromoActive(),
                    'referral_pool_enabled' => $referralPool,
                    'market' => $market,
                    'product' => $product !== null ? [
                        'id' => (int) $product->id,
                        'name' => (string) $product->name,
                        'price' => round((float) $product->price, 2),
                    ] : null,
                ],
            ]);
        } catch (InvalidArgumentException $e) {
            return $this->fail('INVALID_INPUT', $e->getMessage(), 422);
        } catch (\Throwable $e) {
            Log::error('Seller pricing plan failed', ['user_id' => $userId, 'error' => $e->getMessage()]);

            return $this->fail('PLAN_FAILED', 'วางแผนราคาไม่สำเร็จ กรุณาลองใหม่อีกครั้ง', 500);
        }
    }

    /**
     * "ใช้ราคานี้กับสินค้า" — เปลี่ยนราคาสินค้าของตัวเอง + บันทึกประวัติการเปลี่ยนราคา
     *
     * กดซ้ำได้ปลอดภัย: ราคาเท่าเดิม = ไม่เขียนซ้ำ ไม่บันทึกประวัติซ้ำ
     */
    public function applyPrice(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|integer',
            'price' => 'required|numeric|min:1|max:'.self::MAX_PRICE,
            'strategy' => 'nullable|in:penetration,balanced,premium,custom',
        ], $this->messages() + [
            'product_id.required' => 'กรุณาเลือกสินค้าที่จะใช้ราคานี้',
            'price.min' => 'ราคาต้องไม่ต่ำกว่า 1 บาท',
        ]);

        $user = $request->user();
        $newPrice = round((float) $data['price'], 2);

        try {
            $result = DB::transaction(function () use ($user, $data, $newPrice) {
                /** @var Product|null $product */
                $product = Product::where('seller_id', $user->id)
                    ->whereKey((int) $data['product_id'])
                    ->lockForUpdate()
                    ->first();

                if ($product === null) {
                    return ['code' => 'PRODUCT_NOT_FOUND', 'status' => 404];
                }
                if ($product->is_blocked) {
                    return ['code' => 'PRODUCT_BLOCKED', 'status' => 409];
                }

                $oldPrice = round((float) $product->price, 2);
                if (abs($oldPrice - $newPrice) < 0.005) {
                    return ['code' => 'UNCHANGED', 'status' => 200, 'product' => $product, 'old' => $oldPrice];
                }

                $product->price = $newPrice;
                $product->save();

                AccountingActivityLog::create([
                    'user_id' => $user->id,
                    'loggable_type' => Product::class,
                    'loggable_id' => $product->id,
                    'action' => 'product.price_updated_by_planner',
                    'description' => mb_substr('ผู้ขายเปลี่ยนราคาจากหน้าวางแผนราคา '
                        .number_format($oldPrice, 2).' → '.number_format($newPrice, 2).' บาท'
                        .(! empty($data['strategy']) ? ' (กลยุทธ์ '.$data['strategy'].')' : ''), 0, 2000),
                    'old_values' => ['price' => $oldPrice],
                    'new_values' => ['price' => $newPrice, 'strategy' => $data['strategy'] ?? null],
                    'ip_address' => request()->ip(),
                ]);

                return ['code' => 'UPDATED', 'status' => 200, 'product' => $product, 'old' => $oldPrice];
            });
        } catch (\Throwable $e) {
            Log::error('Seller pricing apply failed', [
                'user_id' => $user->id,
                'product_id' => $data['product_id'],
                'error' => $e->getMessage(),
            ]);

            return $this->respond($request, false, 'APPLY_FAILED', 'บันทึกราคาไม่สำเร็จ กรุณาลองใหม่อีกครั้ง', 500);
        }

        if ($result['code'] === 'PRODUCT_NOT_FOUND') {
            return $this->respond($request, false, 'PRODUCT_NOT_FOUND', 'ไม่พบสินค้านี้ในร้านของคุณ', 404);
        }
        if ($result['code'] === 'PRODUCT_BLOCKED') {
            return $this->respond($request, false, 'PRODUCT_BLOCKED', 'สินค้านี้ถูกระงับโดยแอดมิน จึงเปลี่ยนราคาไม่ได้', 409);
        }

        Log::info('Seller applied planner price', [
            'user_id' => $user->id,
            'product_id' => $result['product']->id,
            'old_price' => $result['old'],
            'new_price' => $newPrice,
            'strategy' => $data['strategy'] ?? null,
            'changed' => $result['code'] === 'UPDATED',
        ]);

        $message = $result['code'] === 'UPDATED'
            ? 'เปลี่ยนราคา "'.$result['product']->name.'" เป็น ฿'.number_format($newPrice, 2).' แล้ว'
            : 'สินค้านี้ใช้ราคา ฿'.number_format($newPrice, 2).' อยู่แล้ว';

        return $this->respond($request, true, $result['code'], $message, 200, [
            'product_id' => (int) $result['product']->id,
            'old_price' => (float) $result['old'],
            'price' => $newPrice,
            'changed' => $result['code'] === 'UPDATED',
        ]);
    }

    // =====================================================================
    // ภายใน
    // =====================================================================

    private function storeFor(int $userId): ?VendorStore
    {
        return VendorStore::where('user_id', $userId)->orderBy('id')->first();
    }

    /**
     * สินค้าของผู้ขายคนนี้เท่านั้น (สินค้าร้านอื่น = ถือว่าไม่ได้เลือก)
     */
    private function ownedProduct(int $userId, mixed $productId): ?Product
    {
        if ($productId === null || $productId === '' || (int) $productId <= 0) {
            return null;
        }

        return Product::where('seller_id', $userId)->whereKey((int) $productId)->first();
    }

    /**
     * สินค้าร่าง (ยังไม่บันทึก) ของร้านนี้ — ใช้หาอัตรา GP/VAT ตอนสร้างสินค้าใหม่หรือวางแผนแบบไม่เลือกสินค้า
     */
    private function draftProduct(int $userId, ?VendorStore $store, mixed $pv = null): Product
    {
        return (new Product)->forceFill([
            'seller_id' => $userId,
            'store_id' => $store?->id,
            'pv_value' => is_numeric($pv) ? max(0.0, (float) $pv) : 0,
        ]);
    }

    /**
     * อัตรา GP ตามแพ็กเกจ (อ่านไม่ได้ = ไม่แสดงตารางเทียบ แทนที่จะทำให้หน้าล้ม)
     *
     * @return array<int, array<string, mixed>>
     */
    private function safePackages(StrategyAdvisor $advisor): array
    {
        try {
            return $advisor->packageGpOptions();
        } catch (\Throwable $e) {
            Log::warning('Seller pricing: package GP options unavailable', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * @return array<string, string>
     */
    private function messages(): array
    {
        return [
            'price.required' => 'กรุณากรอกราคาขาย',
            'price.numeric' => 'ราคาขายต้องเป็นตัวเลข',
            'price.max' => 'ราคาขายสูงเกินไป',
            'cost.required' => 'กรุณากรอกต้นทุนต่อชิ้น (ใส่ 0 ได้ถ้ากรอกราคาคู่แข่ง)',
            'cost.numeric' => 'ต้นทุนต้องเป็นตัวเลข',
            'target_margin_percent.max' => 'เป้ากำไรต้องไม่เกิน 90%',
            'delivery.in' => 'วิธีจัดส่งไม่ถูกต้อง',
            '*.numeric' => 'กรุณากรอกเป็นตัวเลข',
            '*.min' => 'ค่าต้องไม่ติดลบ',
            '*.integer' => 'กรุณากรอกเป็นจำนวนเต็ม',
        ];
    }

    private function fail(string $code, string $message, int $status): JsonResponse
    {
        return response()->json(['success' => false, 'code' => $code, 'message' => $message, 'data' => null], $status);
    }

    /**
     * ตอบกลับได้ทั้ง AJAX (JSON) และฟอร์มธรรมดา (redirect พร้อม flash)
     */
    private function respond(Request $request, bool $ok, string $code, string $message, int $status, array $data = [])
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => $ok, 'code' => $code, 'message' => $message, 'data' => $data ?: null], $status);
        }

        return back()->with($ok ? 'success' : 'error', $message);
    }
}
