<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceProduct;
use App\Models\MarketplacePlatform;
use App\Models\MarketplaceAccount;
use Illuminate\Http\Request;

/**
 * Admin Marketplace Product Controller
 *
 * จัดการสินค้าที่ Sync มาจาก Marketplace (Lazada, Shopee, TikTok Shop)
 */
class MarketplaceProductController extends Controller
{
    /**
     * แสดงรายการสินค้า Marketplace ทั้งหมด
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = MarketplaceProduct::with(['platform', 'account']);

        // กรองตาม platform
        if ($request->filled('platform')) {
            $query->where('platform_id', $request->platform);
        }

        // กรองตาม account
        if ($request->filled('account')) {
            $query->where('account_id', $request->account);
        }

        // กรองตามสถานะ
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            } elseif ($request->status === 'available') {
                $query->where('is_available', true);
            } elseif ($request->status === 'out_of_stock') {
                $query->where('stock_quantity', 0);
            }
        }

        // ค้นหาตามชื่อสินค้า
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('external_product_id', 'like', "%{$search}%")
                    ->orWhere('external_sku', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%");
            });
        }

        // กรองตามช่วงราคา
        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // เรียงลำดับ
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $products = $query->paginate(20)->withQueryString();
        $platforms = MarketplacePlatform::where('is_active', true)->get();
        $accounts = MarketplaceAccount::where('status', 'active')->get();

        // สถิติภาพรวม
        $stats = [
            'total_products' => MarketplaceProduct::count(),
            'active_products' => MarketplaceProduct::where('is_active', true)->count(),
            'available_products' => MarketplaceProduct::where('is_available', true)->count(),
            'out_of_stock' => MarketplaceProduct::where('stock_quantity', 0)->count(),
            'total_clicks' => MarketplaceProduct::sum('click_count'),
            'total_sales' => MarketplaceProduct::sum('sales_count'),
        ];

        return view('admin.marketplace.products.index', compact(
            'products',
            'platforms',
            'accounts',
            'stats'
        ));
    }

    /**
     * แสดงรายละเอียดสินค้า
     *
     * @param MarketplaceProduct $product
     * @return \Illuminate\View\View
     */
    public function show(MarketplaceProduct $product)
    {
        $product->load(['platform', 'account', 'affiliateLinks', 'orderItems.order']);

        // สถิติสินค้า
        $stats = [
            'total_links' => $product->affiliateLinks()->count(),
            'total_clicks' => $product->click_count ?? 0,
            'total_shares' => $product->share_count ?? 0,
            'total_views' => $product->view_count ?? 0,
            'total_orders' => $product->orderItems()->count(),
            'total_sales' => $product->orderItems()->sum('total_amount'),
        ];

        return view('admin.marketplace.products.show', compact('product', 'stats'));
    }

    /**
     * แก้ไขสถานะสินค้า
     *
     * @param Request $request
     * @param MarketplaceProduct $product
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, MarketplaceProduct $product)
    {
        $validated = $request->validate([
            'is_active' => 'boolean',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $product->update($validated);

        return redirect()
            ->route('admin.marketplace.products.show', $product)
            ->with('success', 'อัพเดทสินค้าสำเร็จ');
    }

    /**
     * ลบสินค้า
     *
     * @param MarketplaceProduct $product
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(MarketplaceProduct $product)
    {
        $product->affiliateLinks()->delete();
        $product->delete();

        return redirect()
            ->route('admin.marketplace.products.index')
            ->with('success', 'ลบสินค้าสำเร็จ');
    }

    /**
     * เปิด/ปิดสถานะสินค้าหลายรายการ
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkAction(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:marketplace_products,id',
            'action' => 'required|in:activate,deactivate,delete',
        ]);

        $count = 0;
        switch ($validated['action']) {
            case 'activate':
                $count = MarketplaceProduct::whereIn('id', $validated['ids'])
                    ->update(['is_active' => true]);
                $message = "เปิดใช้งาน {$count} รายการสำเร็จ";
                break;

            case 'deactivate':
                $count = MarketplaceProduct::whereIn('id', $validated['ids'])
                    ->update(['is_active' => false]);
                $message = "ปิดใช้งาน {$count} รายการสำเร็จ";
                break;

            case 'delete':
                $count = MarketplaceProduct::whereIn('id', $validated['ids'])->delete();
                $message = "ลบ {$count} รายการสำเร็จ";
                break;
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'count' => $count,
        ]);
    }
}
