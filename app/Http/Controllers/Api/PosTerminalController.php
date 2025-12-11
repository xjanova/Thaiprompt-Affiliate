<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PosTerminal;
use App\Models\PosApiKey;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * POS Terminal API Controller
 *
 * จัดการการลงทะเบียนและ Authentication ของเครื่อง POS
 * ใช้ระบบ 2 กุญแจ: Product Key + API Key
 */
class PosTerminalController extends Controller
{
    /**
     * ลงทะเบียนเครื่อง POS ใหม่
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        // ตรวจสอบ API Key จาก header
        $apiKey = $request->header('X-API-Key');
        if (empty($apiKey)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาระบุ API Key',
                'error_code' => 'MISSING_API_KEY'
            ], 401);
        }

        // หา API Key ในระบบ
        $posApiKey = PosApiKey::where('key', $apiKey)
            ->where('is_active', true)
            ->first();

        if (!$posApiKey) {
            return response()->json([
                'success' => false,
                'message' => 'API Key ไม่ถูกต้องหรือถูกระงับ',
                'error_code' => 'INVALID_API_KEY'
            ], 401);
        }

        // ตรวจสอบจำนวนเครื่องที่ลงทะเบียนได้
        if ($posApiKey->max_terminals > 0) {
            $currentTerminals = PosTerminal::where('api_key_id', $posApiKey->id)->count();
            if ($currentTerminals >= $posApiKey->max_terminals) {
                return response()->json([
                    'success' => false,
                    'message' => "ไม่สามารถลงทะเบียนได้ เกินจำนวนที่กำหนด ({$posApiKey->max_terminals} เครื่อง)",
                    'error_code' => 'MAX_TERMINALS_REACHED'
                ], 403);
            }
        }

        // Validate request
        $validated = $request->validate([
            'product_key' => 'required|string|max:50',
            'device_id' => 'required|string|max:100',
            'device_name' => 'nullable|string|max:255',
            'device_model' => 'nullable|string|max:100',
            'platform' => 'nullable|string|max:50',
            'app_version' => 'nullable|string|max:20',
        ]);

        // ตรวจสอบว่า Product Key นี้ลงทะเบียนแล้วหรือยัง
        $existingTerminal = PosTerminal::where('product_key', $validated['product_key'])->first();

        if ($existingTerminal) {
            // ตรวจสอบว่า Device ID ตรงกันหรือไม่
            if ($existingTerminal->device_id !== $validated['device_id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product Key นี้ถูกลงทะเบียนกับเครื่องอื่นแล้ว',
                    'error_code' => 'PRODUCT_KEY_IN_USE'
                ], 409);
            }

            // Device เดิม - อัพเดทข้อมูล
            $existingTerminal->update([
                'device_name' => $validated['device_name'] ?? $existingTerminal->device_name,
                'device_model' => $validated['device_model'] ?? $existingTerminal->device_model,
                'platform' => $validated['platform'] ?? $existingTerminal->platform,
                'app_version' => $validated['app_version'] ?? $existingTerminal->app_version,
                'last_seen_at' => now(),
            ]);

            $terminal = $existingTerminal;
        } else {
            // สร้าง terminal ใหม่
            $terminal = PosTerminal::create([
                'api_key_id' => $posApiKey->id,
                'shop_id' => $posApiKey->shop_id,
                'product_key' => $validated['product_key'],
                'device_id' => $validated['device_id'],
                'device_name' => $validated['device_name'] ?? 'POS Terminal',
                'device_model' => $validated['device_model'],
                'platform' => $validated['platform'],
                'app_version' => $validated['app_version'],
                'is_active' => true,
                'registered_at' => now(),
                'last_seen_at' => now(),
            ]);
        }

        // ดึงข้อมูลร้าน
        $shop = $posApiKey->shop;

        return response()->json([
            'success' => true,
            'message' => 'ลงทะเบียนสำเร็จ',
            'data' => [
                'shop_name' => $shop?->name ?? 'ร้านค้า',
                'shop_id' => $shop?->id ?? 0,
                'pos_terminal_id' => $terminal->id,
                'expires_at' => $posApiKey->expires_at,
            ]
        ]);
    }

    /**
     * ตรวจสอบสถานะ License
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function validate(Request $request)
    {
        // ตรวจสอบ headers
        $apiKey = $request->header('X-API-Key');
        $productKey = $request->header('X-Product-Key');
        $deviceId = $request->header('X-Device-ID');

        if (empty($apiKey) || empty($productKey)) {
            return response()->json([
                'valid' => false,
                'message' => 'กรุณาระบุ API Key และ Product Key'
            ], 401);
        }

        // หา API Key
        $posApiKey = PosApiKey::where('key', $apiKey)
            ->where('is_active', true)
            ->first();

        if (!$posApiKey) {
            return response()->json([
                'valid' => false,
                'message' => 'API Key ไม่ถูกต้องหรือถูกระงับ'
            ], 401);
        }

        // ตรวจสอบวันหมดอายุ
        if ($posApiKey->expires_at && $posApiKey->expires_at < now()) {
            return response()->json([
                'valid' => false,
                'message' => 'API Key หมดอายุแล้ว'
            ], 403);
        }

        // หา Terminal
        $terminal = PosTerminal::where('product_key', $productKey)
            ->where('api_key_id', $posApiKey->id)
            ->first();

        if (!$terminal) {
            return response()->json([
                'valid' => false,
                'message' => 'ไม่พบเครื่อง POS ที่ลงทะเบียน'
            ], 404);
        }

        // ตรวจสอบ Device ID (ถ้าระบุมา)
        if (!empty($deviceId) && $terminal->device_id !== $deviceId) {
            return response()->json([
                'valid' => false,
                'message' => 'Device ID ไม่ตรงกับที่ลงทะเบียน'
            ], 403);
        }

        // ตรวจสอบสถานะ terminal
        if (!$terminal->is_active) {
            return response()->json([
                'valid' => false,
                'message' => 'เครื่อง POS นี้ถูกระงับการใช้งาน'
            ], 403);
        }

        // อัพเดท last seen
        $terminal->update(['last_seen_at' => now()]);

        return response()->json([
            'valid' => true,
            'message' => 'License ถูกต้อง',
            'expires_at' => $posApiKey->expires_at
        ]);
    }

    /**
     * Sync สินค้าจาก Server
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncProducts(Request $request)
    {
        $terminal = $this->authenticateTerminal($request);
        if (!$terminal) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่ได้รับอนุญาต'
            ], 401);
        }

        // ดึงสินค้าจากร้าน
        $products = \App\Models\Product::where('shop_id', $terminal->shop_id)
            ->where('is_active', true)
            ->with(['category'])
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'sku' => $p->sku,
                'barcode' => $p->barcode,
                'name' => $p->name,
                'price' => $p->price,
                'cost' => $p->cost,
                'stock' => $p->stock,
                'category_id' => $p->category_id,
                'category_name' => $p->category?->name,
                'image_url' => $p->image_url,
                'updated_at' => $p->updated_at?->toISOString(),
            ]);

        // อัพเดท last sync
        $terminal->update(['last_sync_at' => now()]);

        return response()->json([
            'success' => true,
            'data' => $products,
            'synced_at' => now()->toISOString()
        ]);
    }

    /**
     * Sync หมวดหมู่จาก Server
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncCategories(Request $request)
    {
        $terminal = $this->authenticateTerminal($request);
        if (!$terminal) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่ได้รับอนุญาต'
            ], 401);
        }

        // ดึงหมวดหมู่จากร้าน
        $categories = \App\Models\Category::where('shop_id', $terminal->shop_id)
            ->where('is_active', true)
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'icon' => $c->icon,
                'color' => $c->color,
                'sort_order' => $c->sort_order,
            ]);

        return response()->json([
            'success' => true,
            'data' => $categories,
            'synced_at' => now()->toISOString()
        ]);
    }

    /**
     * อัพโหลดคำสั่งซื้อจาก POS ไป Server
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadOrders(Request $request)
    {
        $terminal = $this->authenticateTerminal($request);
        if (!$terminal) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่ได้รับอนุญาต'
            ], 401);
        }

        $validated = $request->validate([
            'orders' => 'required|array',
            'orders.*.local_id' => 'required|string',
            'orders.*.total' => 'required|numeric',
            'orders.*.items' => 'required|array',
            'orders.*.created_at' => 'required|date',
        ]);

        $uploadedCount = 0;
        $errors = [];

        foreach ($validated['orders'] as $orderData) {
            try {
                // ตรวจสอบว่าเคย sync แล้วหรือยัง
                $existing = \App\Models\Order::where('pos_local_id', $orderData['local_id'])
                    ->where('pos_terminal_id', $terminal->id)
                    ->first();

                if ($existing) {
                    continue; // ข้าม order ที่เคย sync แล้ว
                }

                // สร้าง order ใหม่
                $order = \App\Models\Order::create([
                    'shop_id' => $terminal->shop_id,
                    'pos_terminal_id' => $terminal->id,
                    'pos_local_id' => $orderData['local_id'],
                    'total' => $orderData['total'],
                    'status' => 'completed',
                    'created_at' => $orderData['created_at'],
                ]);

                // สร้าง order items
                foreach ($orderData['items'] as $item) {
                    $order->items()->create([
                        'product_id' => $item['product_id'] ?? null,
                        'product_name' => $item['name'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['price'],
                        'line_total' => $item['quantity'] * $item['price'],
                    ]);
                }

                $uploadedCount++;
            } catch (\Exception $e) {
                $errors[] = [
                    'local_id' => $orderData['local_id'],
                    'error' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'success' => true,
            'uploaded' => $uploadedCount,
            'errors' => $errors,
            'synced_at' => now()->toISOString()
        ]);
    }

    /**
     * รายงานยอดขาย
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reportSales(Request $request)
    {
        $terminal = $this->authenticateTerminal($request);
        if (!$terminal) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่ได้รับอนุญาต'
            ], 401);
        }

        $validated = $request->validate([
            'date' => 'required|date',
            'total_sales' => 'required|numeric',
            'total_orders' => 'required|integer',
            'total_items' => 'required|integer',
            'cash_sales' => 'nullable|numeric',
            'card_sales' => 'nullable|numeric',
            'other_sales' => 'nullable|numeric',
        ]);

        // บันทึกรายงาน
        \App\Models\PosDailyReport::updateOrCreate(
            [
                'pos_terminal_id' => $terminal->id,
                'report_date' => $validated['date'],
            ],
            [
                'shop_id' => $terminal->shop_id,
                'total_sales' => $validated['total_sales'],
                'total_orders' => $validated['total_orders'],
                'total_items' => $validated['total_items'],
                'cash_sales' => $validated['cash_sales'] ?? 0,
                'card_sales' => $validated['card_sales'] ?? 0,
                'other_sales' => $validated['other_sales'] ?? 0,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'บันทึกรายงานสำเร็จ'
        ]);
    }

    /**
     * Authenticate POS Terminal จาก headers
     *
     * @param Request $request
     * @return PosTerminal|null
     */
    private function authenticateTerminal(Request $request): ?PosTerminal
    {
        $apiKey = $request->header('X-API-Key');
        $productKey = $request->header('X-Product-Key');

        if (empty($apiKey) || empty($productKey)) {
            return null;
        }

        // หา API Key
        $posApiKey = PosApiKey::where('key', $apiKey)
            ->where('is_active', true)
            ->first();

        if (!$posApiKey) {
            return null;
        }

        // ตรวจสอบวันหมดอายุ
        if ($posApiKey->expires_at && $posApiKey->expires_at < now()) {
            return null;
        }

        // หา Terminal
        $terminal = PosTerminal::where('product_key', $productKey)
            ->where('api_key_id', $posApiKey->id)
            ->where('is_active', true)
            ->first();

        if ($terminal) {
            $terminal->update(['last_seen_at' => now()]);
        }

        return $terminal;
    }
}
