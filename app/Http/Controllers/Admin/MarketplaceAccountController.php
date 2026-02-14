<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceAccount;
use App\Models\MarketplacePlatform;
use App\Models\User;
use App\Services\Marketplace\MarketplaceFactory;
use App\Services\Marketplace\MarketplaceSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Admin Marketplace Account Controller
 *
 * จัดการบัญชี Marketplace (Lazada, Shopee, TikTok Shop)
 * สำหรับระบบ Affiliate API
 */
class MarketplaceAccountController extends Controller
{
    /**
     * แสดงรายการบัญชี Marketplace ทั้งหมด
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = MarketplaceAccount::with(['platform', 'user']);

        // กรองตาม platform
        if ($request->filled('platform')) {
            $query->where('platform_id', $request->platform);
        }

        // กรองตามสถานะ
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // ค้นหาตามชื่อบัญชีหรือร้านค้า
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('account_name', 'like', "%{$search}%")
                    ->orWhere('shop_name', 'like', "%{$search}%")
                    ->orWhere('shop_id', 'like', "%{$search}%");
            });
        }

        $accounts = $query->latest()->paginate(15)->withQueryString();
        $platforms = MarketplacePlatform::where('is_active', true)->get();

        // สถิติภาพรวม
        $stats = [
            'total_accounts' => MarketplaceAccount::count(),
            'active_accounts' => MarketplaceAccount::where('status', 'active')->count(),
            'total_products' => MarketplaceAccount::sum('total_products_synced'),
            'total_orders' => MarketplaceAccount::sum('total_orders_synced'),
            'total_commission' => MarketplaceAccount::sum('total_commission'),
        ];

        return view('admin.marketplace.accounts.index', compact(
            'accounts',
            'platforms',
            'stats'
        ));
    }

    /**
     * แสดงฟอร์มสร้างบัญชีใหม่
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $platforms = MarketplacePlatform::where('is_active', true)->get();
        $users = User::orderBy('name')->get();

        return view('admin.marketplace.accounts.create', compact('platforms', 'users'));
    }

    /**
     * บันทึกบัญชีใหม่
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'platform_id' => 'required|exists:marketplace_platforms,id',
            'account_name' => 'required|string|max:255',
            'shop_id' => 'nullable|string|max:255',
            'shop_name' => 'nullable|string|max:255',
            'app_key' => 'required|string|max:500',
            'app_secret' => 'required|string|max:500',
            'access_token' => 'nullable|string|max:2000',
            'refresh_token' => 'nullable|string|max:2000',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'auto_sync_products' => 'boolean',
            'auto_sync_orders' => 'boolean',
            'sync_frequency' => 'nullable|in:hourly,daily,weekly',
        ]);

        // ตั้งค่าเริ่มต้น
        $validated['status'] = 'pending';
        $validated['auto_sync_products'] = $request->boolean('auto_sync_products');
        $validated['auto_sync_orders'] = $request->boolean('auto_sync_orders');

        // เพิ่ม additional credentials สำหรับ Shopee
        if ($request->filled('partner_id')) {
            $validated['additional_credentials'] = [
                'partner_id' => $request->partner_id,
                'partner_key' => $request->partner_key,
            ];
        }

        $account = MarketplaceAccount::create($validated);

        return redirect()
            ->route('admin.marketplace.accounts.show', $account)
            ->with('success', 'สร้างบัญชี Marketplace สำเร็จ');
    }

    /**
     * แสดงรายละเอียดบัญชี
     *
     * @return \Illuminate\View\View
     */
    public function show(MarketplaceAccount $account)
    {
        $account->load(['platform', 'user', 'products', 'orders', 'syncLogs' => function ($q) {
            $q->latest()->limit(10);
        }]);

        // สถิติบัญชี
        $stats = [
            'total_products' => $account->products()->count(),
            'active_products' => $account->products()->where('is_active', true)->count(),
            'total_orders' => $account->orders()->count(),
            'pending_orders' => $account->orders()->where('order_status', 'pending')->count(),
            'total_sales' => $account->total_sales ?? 0,
            'total_commission' => $account->total_commission ?? 0,
        ];

        return view('admin.marketplace.accounts.show', compact('account', 'stats'));
    }

    /**
     * แสดงฟอร์มแก้ไขบัญชี
     *
     * @return \Illuminate\View\View
     */
    public function edit(MarketplaceAccount $account)
    {
        $platforms = MarketplacePlatform::where('is_active', true)->get();
        $users = User::orderBy('name')->get();

        return view('admin.marketplace.accounts.edit', compact('account', 'platforms', 'users'));
    }

    /**
     * อัพเดทบัญชี
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, MarketplaceAccount $account)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'account_name' => 'required|string|max:255',
            'shop_id' => 'nullable|string|max:255',
            'shop_name' => 'nullable|string|max:255',
            'app_key' => 'nullable|string|max:500',
            'app_secret' => 'nullable|string|max:500',
            'access_token' => 'nullable|string|max:2000',
            'refresh_token' => 'nullable|string|max:2000',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'auto_sync_products' => 'boolean',
            'auto_sync_orders' => 'boolean',
            'sync_frequency' => 'nullable|in:hourly,daily,weekly',
            'status' => 'nullable|in:pending,active,inactive,suspended',
        ]);

        // อัพเดทเฉพาะ credentials ที่มีการส่งมา
        if (empty($validated['app_key'])) {
            unset($validated['app_key']);
        }
        if (empty($validated['app_secret'])) {
            unset($validated['app_secret']);
        }
        if (empty($validated['access_token'])) {
            unset($validated['access_token']);
        }
        if (empty($validated['refresh_token'])) {
            unset($validated['refresh_token']);
        }

        $validated['auto_sync_products'] = $request->boolean('auto_sync_products');
        $validated['auto_sync_orders'] = $request->boolean('auto_sync_orders');

        // เพิ่ม additional credentials สำหรับ Shopee
        if ($request->filled('partner_id')) {
            $validated['additional_credentials'] = array_merge(
                $account->additional_credentials ?? [],
                [
                    'partner_id' => $request->partner_id,
                    'partner_key' => $request->partner_key,
                ]
            );
        }

        $account->update($validated);

        return redirect()
            ->route('admin.marketplace.accounts.show', $account)
            ->with('success', 'อัพเดทบัญชี Marketplace สำเร็จ');
    }

    /**
     * ลบบัญชี
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(MarketplaceAccount $account)
    {
        // ลบข้อมูลที่เกี่ยวข้อง
        $account->products()->delete();
        $account->orders()->delete();
        $account->syncLogs()->delete();
        $account->delete();

        return redirect()
            ->route('admin.marketplace.accounts.index')
            ->with('success', 'ลบบัญชี Marketplace สำเร็จ');
    }

    /**
     * ทดสอบการเชื่อมต่อ API
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function testConnection(MarketplaceAccount $account)
    {
        try {
            $apiService = MarketplaceFactory::create($account);
            $result = $apiService->testConnection();

            if ($result) {
                $account->update(['status' => 'active', 'last_error' => null]);

                return response()->json([
                    'success' => true,
                    'message' => 'เชื่อมต่อ API สำเร็จ',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถเชื่อมต่อ API ได้',
            ], 400);

        } catch (\Exception $e) {
            Log::error("Test connection failed: {$e->getMessage()}", [
                'account_id' => $account->id,
            ]);

            $account->update([
                'status' => 'inactive',
                'last_error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync สินค้าจาก Marketplace
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncProducts(MarketplaceAccount $account)
    {
        try {
            $syncService = new MarketplaceSyncService($account);
            $log = $syncService->syncProducts();

            return response()->json([
                'success' => true,
                'message' => "Sync สำเร็จ: สร้าง {$log->items_created} รายการ, อัพเดท {$log->items_updated} รายการ",
                'data' => [
                    'processed' => $log->items_processed,
                    'created' => $log->items_created,
                    'updated' => $log->items_updated,
                    'failed' => $log->items_failed,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error("Sync products failed: {$e->getMessage()}", [
                'account_id' => $account->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Sync ล้มเหลว: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync ออเดอร์จาก Marketplace
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncOrders(MarketplaceAccount $account)
    {
        try {
            $syncService = new MarketplaceSyncService($account);
            $log = $syncService->syncOrders();

            return response()->json([
                'success' => true,
                'message' => "Sync สำเร็จ: สร้าง {$log->items_created} รายการ, อัพเดท {$log->items_updated} รายการ",
                'data' => [
                    'processed' => $log->items_processed,
                    'created' => $log->items_created,
                    'updated' => $log->items_updated,
                    'failed' => $log->items_failed,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error("Sync orders failed: {$e->getMessage()}", [
                'account_id' => $account->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Sync ล้มเหลว: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync ทั้งหมด (สินค้า + ออเดอร์)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncAll(MarketplaceAccount $account)
    {
        try {
            $syncService = new MarketplaceSyncService($account);
            $results = $syncService->syncAll();

            return response()->json([
                'success' => true,
                'message' => 'Sync ทั้งหมดสำเร็จ',
                'data' => [
                    'products' => [
                        'created' => $results['products']->items_created,
                        'updated' => $results['products']->items_updated,
                    ],
                    'orders' => [
                        'created' => $results['orders']->items_created,
                        'updated' => $results['orders']->items_updated,
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            Log::error("Sync all failed: {$e->getMessage()}", [
                'account_id' => $account->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Sync ล้มเหลว: '.$e->getMessage(),
            ], 500);
        }
    }
}
