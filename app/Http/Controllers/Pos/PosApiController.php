<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\PosCategory;
use App\Models\PosDevice;
use App\Models\PosSetting;
use App\Models\PosTransaction;
use App\Models\PosTransactionItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * POS API Controller
 *
 * จัดการ API endpoints สำหรับ POS PWA
 * รองรับ Offline-First Architecture
 *
 * @version 1.0.0
 */
class PosApiController extends Controller
{
    // ============================================
    // PRODUCTS
    // ============================================

    /**
     * ดึงสินค้าทั้งหมดสำหรับ offline cache
     */
    public function getAllProducts(Request $request): JsonResponse
    {
        try {
            $storeId = $request->input('store_id');

            $query = Product::query()
                ->where('is_active', true)
                ->where('is_blocked', false)
                ->select([
                    'id',
                    'name',
                    'sku',
                    'barcode',
                    'price',
                    'compare_at_price',
                    'cost_price',
                    'stock_quantity',
                    'track_inventory',
                    'low_stock_threshold',
                    'vat_percentage',
                    'category_id',
                    'image',
                    'is_active',
                    'updated_at',
                ]);

            // กรองตามร้านค้า
            if ($storeId) {
                $query->where('vendor_store_id', $storeId);
            }

            $products = $query->get();

            // แปลง image path เป็น URL
            $products = $products->map(function ($product) {
                if ($product->image) {
                    $product->image_url = asset('storage/'.$product->image);
                }

                return $product;
            });

            return response()->json([
                'success' => true,
                'data' => $products,
                'count' => $products->count(),
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            Log::error('POS API: Failed to get products', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถดึงข้อมูลสินค้าได้',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ดึงสินค้าที่อัพเดทตั้งแต่ timestamp ที่กำหนด
     */
    public function getUpdatedProducts(Request $request): JsonResponse
    {
        try {
            $since = $request->input('since');
            $storeId = $request->input('store_id');

            if (! $since) {
                return $this->getAllProducts($request);
            }

            $query = Product::query()
                ->where('updated_at', '>=', $since)
                ->select([
                    'id',
                    'name',
                    'sku',
                    'barcode',
                    'price',
                    'compare_at_price',
                    'cost_price',
                    'stock_quantity',
                    'track_inventory',
                    'low_stock_threshold',
                    'vat_percentage',
                    'category_id',
                    'image',
                    'is_active',
                    'is_blocked',
                    'updated_at',
                    'deleted_at',
                ])
                ->withTrashed(); // รวม soft deleted เพื่อ sync การลบ

            if ($storeId) {
                $query->where('vendor_store_id', $storeId);
            }

            $products = $query->get();

            return response()->json([
                'success' => true,
                'data' => $products,
                'count' => $products->count(),
                'since' => $since,
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            Log::error('POS API: Failed to get updated products', [
                'error' => $e->getMessage(),
                'since' => $since ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถดึงข้อมูลสินค้าที่อัพเดทได้',
            ], 500);
        }
    }

    /**
     * ดึงสินค้าเดี่ยวตาม ID
     */
    public function getProduct(int $id): JsonResponse
    {
        try {
            $product = Product::find($id);

            if (! $product) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบสินค้า',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $product,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถดึงข้อมูลสินค้าได้',
            ], 500);
        }
    }

    /**
     * ค้นหาสินค้า
     */
    public function searchProducts(Request $request): JsonResponse
    {
        try {
            $query = $request->input('q', '');
            $categoryId = $request->input('category_id');
            $storeId = $request->input('store_id');
            $limit = $request->input('limit', 50);

            $productsQuery = Product::query()
                ->where('is_active', true)
                ->where('is_blocked', false);

            // ค้นหาตาม query
            if ($query) {
                $productsQuery->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('sku', 'like', "%{$query}%")
                        ->orWhere('barcode', $query);
                });
            }

            // กรองตาม category
            if ($categoryId) {
                $productsQuery->where('category_id', $categoryId);
            }

            // กรองตามร้านค้า
            if ($storeId) {
                $productsQuery->where('vendor_store_id', $storeId);
            }

            $products = $productsQuery->limit($limit)->get();

            return response()->json([
                'success' => true,
                'data' => $products,
                'count' => $products->count(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถค้นหาสินค้าได้',
            ], 500);
        }
    }

    // ============================================
    // CATEGORIES
    // ============================================

    /**
     * ดึงหมวดหมู่ทั้งหมด
     */
    public function getAllCategories(Request $request): JsonResponse
    {
        try {
            $storeId = $request->input('store_id');

            $query = PosCategory::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name');

            if ($storeId) {
                $query->where('vendor_store_id', $storeId);
            }

            $categories = $query->get();

            return response()->json([
                'success' => true,
                'data' => $categories,
                'count' => $categories->count(),
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถดึงข้อมูลหมวดหมู่ได้',
            ], 500);
        }
    }

    /**
     * ดึงหมวดหมู่ที่อัพเดท
     */
    public function getUpdatedCategories(Request $request): JsonResponse
    {
        try {
            $since = $request->input('since');

            if (! $since) {
                return $this->getAllCategories($request);
            }

            $categories = PosCategory::query()
                ->where('updated_at', '>=', $since)
                ->withTrashed()
                ->get();

            return response()->json([
                'success' => true,
                'data' => $categories,
                'count' => $categories->count(),
                'since' => $since,
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถดึงข้อมูลหมวดหมู่ได้',
            ], 500);
        }
    }

    // ============================================
    // CUSTOMERS
    // ============================================

    /**
     * ดึงลูกค้าทั้งหมด
     */
    public function getAllCustomers(Request $request): JsonResponse
    {
        try {
            $storeId = $request->input('store_id');

            // ดึงลูกค้าที่เกี่ยวข้องกับร้านค้า
            $customers = User::query()
                ->whereHas('orders', function ($q) use ($storeId) {
                    if ($storeId) {
                        $q->where('vendor_store_id', $storeId);
                    }
                })
                ->orWhereHas('posTransactions', function ($q) use ($storeId) {
                    if ($storeId) {
                        $q->whereHas('device', function ($d) use ($storeId) {
                            $d->where('vendor_store_id', $storeId);
                        });
                    }
                })
                ->select([
                    'id',
                    'name',
                    'email',
                    'phone',
                    'member_code',
                    'points',
                    'updated_at',
                ])
                ->limit(1000)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $customers,
                'count' => $customers->count(),
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถดึงข้อมูลลูกค้าได้',
            ], 500);
        }
    }

    /**
     * ดึงลูกค้าที่อัพเดท
     */
    public function getUpdatedCustomers(Request $request): JsonResponse
    {
        try {
            $since = $request->input('since');

            if (! $since) {
                return $this->getAllCustomers($request);
            }

            $customers = User::query()
                ->where('updated_at', '>=', $since)
                ->select([
                    'id',
                    'name',
                    'email',
                    'phone',
                    'member_code',
                    'points',
                    'updated_at',
                ])
                ->get();

            return response()->json([
                'success' => true,
                'data' => $customers,
                'count' => $customers->count(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถดึงข้อมูลลูกค้าได้',
            ], 500);
        }
    }

    /**
     * ค้นหาลูกค้า
     */
    public function searchCustomers(Request $request): JsonResponse
    {
        try {
            $query = $request->input('q', '');
            $limit = $request->input('limit', 20);

            $customers = User::query()
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('phone', 'like', "%{$query}%")
                        ->orWhere('email', 'like', "%{$query}%")
                        ->orWhere('member_code', $query);
                })
                ->select(['id', 'name', 'email', 'phone', 'member_code', 'points'])
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $customers,
                'count' => $customers->count(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถค้นหาลูกค้าได้',
            ], 500);
        }
    }

    /**
     * สร้างลูกค้าใหม่
     */
    public function createCustomer(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'phone' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ข้อมูลไม่ถูกต้อง',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // สร้างลูกค้าใหม่
            $customer = User::create([
                'name' => $request->input('name'),
                'phone' => $request->input('phone'),
                'email' => $request->input('email'),
                'password' => bcrypt(str()->random(16)),
                'member_code' => 'M'.str_pad(User::max('id') + 1, 8, '0', STR_PAD_LEFT),
            ]);

            return response()->json([
                'success' => true,
                'data' => $customer,
                'message' => 'สร้างลูกค้าสำเร็จ',
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถสร้างลูกค้าได้',
            ], 500);
        }
    }

    // ============================================
    // SETTINGS
    // ============================================

    /**
     * ดึงการตั้งค่า POS
     */
    public function getSettings(Request $request): JsonResponse
    {
        try {
            $deviceId = $request->input('device_id');
            $storeId = $request->input('store_id');

            // ดึงการตั้งค่าจาก PosSetting หรือค่าเริ่มต้น
            $settings = PosSetting::where('vendor_store_id', $storeId)
                ->first();

            // ค่าเริ่มต้น
            $defaultSettings = [
                'tax_enabled' => true,
                'tax_percentage' => 7,
                'tax_inclusive' => true,
                'service_charge_enabled' => false,
                'service_charge_percentage' => 10,
                'receipt_header' => '',
                'receipt_footer' => 'ขอบคุณที่ใช้บริการ',
                'allow_negative_stock' => false,
                'require_customer' => false,
                'payment_methods' => [
                    'cash' => true,
                    'card' => true,
                    'qr' => true,
                    'wallet' => true,
                ],
                'currency' => 'THB',
                'currency_symbol' => '฿',
                'decimal_places' => 2,
            ];

            if ($settings) {
                $data = array_merge($defaultSettings, $settings->toArray());
            } else {
                $data = $defaultSettings;
            }

            return response()->json([
                'success' => true,
                'data' => $data,
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถดึงการตั้งค่าได้',
            ], 500);
        }
    }

    // ============================================
    // TRANSACTIONS (SYNC)
    // ============================================

    /**
     * Sync รายการขายจาก offline
     */
    public function syncTransaction(Request $request): JsonResponse
    {
        try {
            $transactionData = $request->input('transaction');
            $itemsData = $request->input('items', []);

            if (! $transactionData) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่มีข้อมูลรายการขาย',
                ], 400);
            }

            DB::beginTransaction();

            // เช็คว่ามี transaction นี้แล้วหรือยัง
            $existing = PosTransaction::where('transaction_number', $transactionData['transaction_number'])
                ->first();

            if ($existing) {
                DB::rollBack();

                return response()->json([
                    'success' => true,
                    'data' => ['id' => $existing->id],
                    'message' => 'รายการนี้ถูกบันทึกไปแล้ว',
                    'duplicate' => true,
                ]);
            }

            // สร้าง transaction ใหม่
            $transaction = PosTransaction::create([
                'transaction_number' => $transactionData['transaction_number'],
                'session_id' => $transactionData['session_id'] ?? null,
                'device_id' => $transactionData['device_id'] ?? null,
                'customer_id' => $transactionData['customer_id'] ?? null,
                'customer_name' => $transactionData['customer_name'] ?? null,
                'customer_phone' => $transactionData['customer_phone'] ?? null,
                'subtotal' => $transactionData['subtotal'] ?? 0,
                'discount_amount' => $transactionData['discount_amount'] ?? 0,
                'discount_type' => $transactionData['discount_type'] ?? 'fixed',
                'discount_reason' => $transactionData['discount_reason'] ?? null,
                'tax_amount' => $transactionData['tax_amount'] ?? 0,
                'service_charge' => $transactionData['service_charge'] ?? 0,
                'total_amount' => $transactionData['total_amount'] ?? 0,
                'payment_method' => $transactionData['payment_method'] ?? 'cash',
                'payment_status' => $transactionData['payment_status'] ?? 'completed',
                'cash_received' => $transactionData['cash_received'] ?? 0,
                'change_amount' => $transactionData['change_amount'] ?? 0,
                'status' => $transactionData['status'] ?? 'completed',
                'notes' => $transactionData['notes'] ?? null,
                'synced_from_offline' => true,
                'offline_created_at' => $transactionData['created_at'] ?? now(),
            ]);

            // สร้าง transaction items
            foreach ($itemsData as $item) {
                PosTransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $item['product_id'] ?? null,
                    'product_name' => $item['product_name'] ?? '',
                    'product_sku' => $item['product_sku'] ?? '',
                    'product_image' => $item['product_image'] ?? null,
                    'unit_price' => $item['unit_price'] ?? 0,
                    'quantity' => $item['quantity'] ?? 1,
                    'discount_amount' => $item['discount'] ?? 0,
                    'tax_amount' => $item['tax'] ?? 0,
                    'subtotal' => $item['subtotal'] ?? ($item['unit_price'] * $item['quantity']),
                ]);

                // อัพเดทสต็อก (ถ้าจำเป็น)
                if (! empty($item['product_id'])) {
                    $product = Product::find($item['product_id']);
                    if ($product && $product->track_inventory) {
                        $product->decrement('stock_quantity', $item['quantity']);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $transaction->id,
                    'transaction_number' => $transaction->transaction_number,
                ],
                'message' => 'บันทึกรายการขายสำเร็จ',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('POS API: Failed to sync transaction', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถบันทึกรายการขายได้',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync หลายรายการขายพร้อมกัน
     */
    public function bulkSyncTransactions(Request $request): JsonResponse
    {
        try {
            $transactions = $request->input('transactions', []);

            $synced = 0;
            $failed = 0;
            $duplicates = 0;
            $results = [];

            foreach ($transactions as $data) {
                try {
                    $request->replace([
                        'transaction' => $data['transaction'] ?? $data,
                        'items' => $data['items'] ?? [],
                    ]);

                    $response = $this->syncTransaction($request);
                    $responseData = json_decode($response->getContent(), true);

                    if ($responseData['success']) {
                        if (! empty($responseData['duplicate'])) {
                            $duplicates++;
                        } else {
                            $synced++;
                        }
                        $results[] = [
                            'local_id' => $data['id'] ?? null,
                            'server_id' => $responseData['data']['id'] ?? null,
                            'status' => 'success',
                        ];
                    } else {
                        $failed++;
                        $results[] = [
                            'local_id' => $data['id'] ?? null,
                            'status' => 'failed',
                            'error' => $responseData['message'] ?? 'Unknown error',
                        ];
                    }
                } catch (\Exception $e) {
                    $failed++;
                    $results[] = [
                        'local_id' => $data['id'] ?? null,
                        'status' => 'failed',
                        'error' => $e->getMessage(),
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'synced' => $synced,
                    'failed' => $failed,
                    'duplicates' => $duplicates,
                    'results' => $results,
                ],
                'message' => "Synced: {$synced}, Failed: {$failed}, Duplicates: {$duplicates}",
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถ sync รายการขายได้',
            ], 500);
        }
    }

    // ============================================
    // STOCK MOVEMENTS
    // ============================================

    /**
     * Sync การเคลื่อนไหวสต็อก
     */
    public function syncStockMovements(Request $request): JsonResponse
    {
        try {
            $movements = $request->input('movements', []);
            $synced = 0;

            foreach ($movements as $movement) {
                $productId = $movement['product_id'] ?? null;
                $quantity = $movement['quantity'] ?? 0;
                $type = $movement['type'] ?? 'adjustment';

                if (! $productId) {
                    continue;
                }

                $product = Product::find($productId);
                if (! $product) {
                    continue;
                }

                // อัพเดทสต็อก
                if ($type === 'sale' || $quantity < 0) {
                    $product->decrement('stock_quantity', abs($quantity));
                } else {
                    $product->increment('stock_quantity', abs($quantity));
                }

                $synced++;
            }

            return response()->json([
                'success' => true,
                'data' => ['synced' => $synced],
                'message' => "Synced {$synced} stock movements",
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถ sync การเคลื่อนไหวสต็อกได้',
            ], 500);
        }
    }

    /**
     * บันทึกการเคลื่อนไหวสต็อกเดี่ยว
     */
    public function recordStockMovement(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'product_id' => 'required|exists:products,id',
                'type' => 'required|in:in,out,adjustment,sale,return',
                'quantity' => 'required|numeric',
                'reason' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            $product = Product::find($request->input('product_id'));
            $type = $request->input('type');
            $quantity = abs($request->input('quantity'));

            // อัพเดทสต็อก
            if (in_array($type, ['out', 'sale'])) {
                $product->decrement('stock_quantity', $quantity);
            } else {
                $product->increment('stock_quantity', $quantity);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'product_id' => $product->id,
                    'new_stock' => $product->fresh()->stock_quantity,
                ],
                'message' => 'บันทึกการเคลื่อนไหวสต็อกสำเร็จ',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถบันทึกการเคลื่อนไหวสต็อกได้',
            ], 500);
        }
    }

    // ============================================
    // DEVICE & SESSION
    // ============================================

    /**
     * ลงทะเบียน/อัพเดท Device
     */
    public function registerDevice(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'device_code' => 'required|string',
                'device_name' => 'required|string',
                'device_type' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            $device = PosDevice::updateOrCreate(
                ['device_code' => $request->input('device_code')],
                [
                    'device_name' => $request->input('device_name'),
                    'device_type' => $request->input('device_type', 'pwa'),
                    'last_active_at' => now(),
                    'last_ip_address' => $request->ip(),
                    'is_online' => true,
                ]
            );

            return response()->json([
                'success' => true,
                'data' => $device,
                'message' => 'ลงทะเบียนอุปกรณ์สำเร็จ',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถลงทะเบียนอุปกรณ์ได้',
            ], 500);
        }
    }

    /**
     * Heartbeat - อัพเดทสถานะ online
     */
    public function heartbeat(Request $request): JsonResponse
    {
        try {
            $deviceId = $request->input('device_id');

            if ($deviceId) {
                PosDevice::where('id', $deviceId)->update([
                    'last_active_at' => now(),
                    'last_ip_address' => $request->ip(),
                    'is_online' => true,
                ]);
            }

            return response()->json([
                'success' => true,
                'timestamp' => now()->toISOString(),
                'server_time' => now()->format('Y-m-d H:i:s'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
            ], 500);
        }
    }

    // ============================================
    // REPORTS
    // ============================================

    /**
     * ดึงสรุปยอดขายวันนี้
     */
    public function getTodaySummary(Request $request): JsonResponse
    {
        try {
            $deviceId = $request->input('device_id');
            $sessionId = $request->input('session_id');

            $query = PosTransaction::query()
                ->whereDate('created_at', today())
                ->where('status', 'completed');

            if ($deviceId) {
                $query->where('device_id', $deviceId);
            }

            if ($sessionId) {
                $query->where('session_id', $sessionId);
            }

            $transactions = $query->get();

            $summary = [
                'total_transactions' => $transactions->count(),
                'total_sales' => $transactions->sum('total_amount'),
                'total_discount' => $transactions->sum('discount_amount'),
                'total_tax' => $transactions->sum('tax_amount'),
                'average_transaction' => $transactions->count() > 0
                    ? $transactions->sum('total_amount') / $transactions->count()
                    : 0,
                'payment_breakdown' => $transactions->groupBy('payment_method')
                    ->map(function ($group, $method) {
                        return [
                            'method' => $method,
                            'count' => $group->count(),
                            'total' => $group->sum('total_amount'),
                        ];
                    })->values(),
                'date' => today()->format('Y-m-d'),
            ];

            return response()->json([
                'success' => true,
                'data' => $summary,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถดึงสรุปยอดขายได้',
            ], 500);
        }
    }

    /**
     * ดึงรายการขายล่าสุด
     */
    public function getRecentTransactions(Request $request): JsonResponse
    {
        try {
            $deviceId = $request->input('device_id');
            $limit = $request->input('limit', 20);

            $query = PosTransaction::query()
                ->with('items')
                ->orderBy('created_at', 'desc')
                ->limit($limit);

            if ($deviceId) {
                $query->where('device_id', $deviceId);
            }

            $transactions = $query->get();

            return response()->json([
                'success' => true,
                'data' => $transactions,
                'count' => $transactions->count(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถดึงรายการขายได้',
            ], 500);
        }
    }
}
