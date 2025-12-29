<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CoinPurchase;
use App\Models\CoinShopProduct;
use App\Models\Rank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Admin CoinShopController
 *
 * จัดการสินค้าในร้านค้า Video Coins (Admin)
 */
class CoinShopController extends Controller
{
    /**
     * หน้าจัดการสินค้าทั้งหมด
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = CoinShopProduct::with(['creator', 'minRank'])
            ->withCount('purchases');

        // กรองตามสถานะ
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            } elseif ($request->status === 'out_of_stock') {
                $query->whereNotNull('stock_quantity')->where('stock_quantity', '<=', 0);
            }
        }

        // กรองตามประเภท
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // ค้นหา
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('name_th', 'like', "%{$search}%")
                    ->orWhere('id', $search);
            });
        }

        // เรียงลำดับ
        $sortBy = $request->get('sort', 'latest');
        switch ($sortBy) {
            case 'price_low':
                $query->orderBy('price_coins', 'asc');
                break;
            case 'price_high':
                $query->orderBy('price_coins', 'desc');
                break;
            case 'popular':
                $query->orderBy('total_sold', 'desc');
                break;
            case 'name':
                $query->orderBy('name', 'asc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }

        $products = $query->paginate(15);

        // สถิติ
        $stats = [
            'total_products' => CoinShopProduct::count(),
            'active_products' => CoinShopProduct::where('is_active', true)->count(),
            'out_of_stock' => CoinShopProduct::whereNotNull('stock_quantity')
                ->where('stock_quantity', '<=', 0)->count(),
            'total_sold' => CoinShopProduct::sum('total_sold'),
        ];

        return view('admin.coin-shop.index', [
            'products' => $products,
            'stats' => $stats,
            'types' => CoinShopProduct::TYPES,
            'currentStatus' => $request->status,
            'currentType' => $request->type,
            'currentSort' => $sortBy,
            'search' => $request->search,
            'pageTitle' => 'จัดการสินค้า Coin Shop',
        ]);
    }

    /**
     * หน้าสร้างสินค้าใหม่
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $ranks = Rank::orderBy('level')->get();

        return view('admin.coin-shop.create', [
            'types' => CoinShopProduct::TYPES,
            'ranks' => $ranks,
            'pageTitle' => 'เพิ่มสินค้าใหม่',
        ]);
    }

    /**
     * บันทึกสินค้าใหม่
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_th' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'description_th' => 'nullable|string',
            'thumbnail' => 'nullable|image|max:2048',
            'icon' => 'nullable|string|max:10',
            'type' => 'required|in:'.implode(',', array_keys(CoinShopProduct::TYPES)),
            'category' => 'nullable|string|max:100',
            'price_coins' => 'required|numeric|min:0',
            'original_price_coins' => 'nullable|numeric|min:0',
            'price_money' => 'nullable|numeric|min:0',
            'value_amount' => 'nullable|numeric|min:0',
            'value_unit' => 'nullable|string|max:50',
            'stock_quantity' => 'nullable|integer|min:0',
            'max_per_user' => 'nullable|integer|min:1',
            'max_per_day' => 'nullable|integer|min:1',
            'min_level' => 'nullable|integer|min:1',
            'min_rank_id' => 'nullable|exists:ranks,id',
            'require_kyc' => 'boolean',
            'sale_start_at' => 'nullable|date',
            'sale_end_at' => 'nullable|date|after_or_equal:sale_start_at',
            'use_expire_at' => 'nullable|date',
            'use_expire_days' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_limited' => 'boolean',
            'is_new' => 'boolean',
            'priority' => 'nullable|integer',
            'sort_order' => 'nullable|integer',
        ]);

        // อัปโหลดรูปภาพ
        if ($request->hasFile('thumbnail')) {
            $validated['thumbnail'] = $request->file('thumbnail')
                ->store('coin-shop', 'public');
        }

        // ตั้งค่า audit
        $validated['created_by'] = auth()->id();

        $product = CoinShopProduct::create($validated);

        return redirect()
            ->route('admin.coin-shop.index')
            ->with('success', "เพิ่มสินค้า \"{$product->display_name}\" สำเร็จ");
    }

    /**
     * หน้าแก้ไขสินค้า
     *
     * @return \Illuminate\View\View
     */
    public function edit(CoinShopProduct $product)
    {
        $ranks = Rank::orderBy('level')->get();

        return view('admin.coin-shop.edit', [
            'product' => $product,
            'types' => CoinShopProduct::TYPES,
            'ranks' => $ranks,
            'pageTitle' => 'แก้ไขสินค้า: '.$product->display_name,
        ]);
    }

    /**
     * อัปเดตสินค้า
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, CoinShopProduct $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_th' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'description_th' => 'nullable|string',
            'thumbnail' => 'nullable|image|max:2048',
            'icon' => 'nullable|string|max:10',
            'type' => 'required|in:'.implode(',', array_keys(CoinShopProduct::TYPES)),
            'category' => 'nullable|string|max:100',
            'price_coins' => 'required|numeric|min:0',
            'original_price_coins' => 'nullable|numeric|min:0',
            'price_money' => 'nullable|numeric|min:0',
            'value_amount' => 'nullable|numeric|min:0',
            'value_unit' => 'nullable|string|max:50',
            'stock_quantity' => 'nullable|integer|min:0',
            'max_per_user' => 'nullable|integer|min:1',
            'max_per_day' => 'nullable|integer|min:1',
            'min_level' => 'nullable|integer|min:1',
            'min_rank_id' => 'nullable|exists:ranks,id',
            'require_kyc' => 'boolean',
            'sale_start_at' => 'nullable|date',
            'sale_end_at' => 'nullable|date|after_or_equal:sale_start_at',
            'use_expire_at' => 'nullable|date',
            'use_expire_days' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_limited' => 'boolean',
            'is_new' => 'boolean',
            'priority' => 'nullable|integer',
            'sort_order' => 'nullable|integer',
        ]);

        // อัปโหลดรูปภาพใหม่
        if ($request->hasFile('thumbnail')) {
            // ลบรูปเก่า
            if ($product->thumbnail) {
                Storage::disk('public')->delete($product->thumbnail);
            }
            $validated['thumbnail'] = $request->file('thumbnail')
                ->store('coin-shop', 'public');
        }

        // ตั้งค่า audit
        $validated['updated_by'] = auth()->id();

        $product->update($validated);

        return redirect()
            ->route('admin.coin-shop.index')
            ->with('success', "อัปเดตสินค้า \"{$product->display_name}\" สำเร็จ");
    }

    /**
     * ลบสินค้า
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(CoinShopProduct $product)
    {
        $name = $product->display_name;

        // Soft delete
        $product->delete();

        return redirect()
            ->route('admin.coin-shop.index')
            ->with('success', "ลบสินค้า \"{$name}\" สำเร็จ");
    }

    /**
     * เปลี่ยนสถานะ Active
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function toggleActive(CoinShopProduct $product)
    {
        $product->update([
            'is_active' => ! $product->is_active,
            'updated_by' => auth()->id(),
        ]);

        $status = $product->is_active ? 'เปิดขาย' : 'ปิดการขาย';

        return back()->with('success', "{$status} \"{$product->display_name}\" สำเร็จ");
    }

    /**
     * หน้าจัดการคำสั่งซื้อ
     *
     * @return \Illuminate\View\View
     */
    public function purchases(Request $request)
    {
        $query = CoinPurchase::with(['user', 'product', 'processor'])
            ->latest();

        // กรองตามสถานะ
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // กรองตามประเภทสินค้า
        if ($request->filled('type')) {
            $query->where('product_type', $request->type);
        }

        // ค้นหา
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('product_name', 'like', "%{$search}%")
                    ->orWhere('redemption_code', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $purchases = $query->paginate(20);

        // สถิติ
        $stats = [
            'total_purchases' => CoinPurchase::count(),
            'pending' => CoinPurchase::where('status', 'pending')->count(),
            'processing' => CoinPurchase::where('status', 'processing')->count(),
            'completed' => CoinPurchase::where('status', 'completed')->count(),
            'total_coins_spent' => CoinPurchase::whereNotIn('status', ['cancelled', 'refunded'])
                ->sum('total_coins'),
        ];

        return view('admin.coin-shop.purchases', [
            'purchases' => $purchases,
            'stats' => $stats,
            'statuses' => CoinPurchase::STATUSES,
            'types' => CoinShopProduct::TYPES,
            'currentStatus' => $request->status,
            'currentType' => $request->type,
            'search' => $request->search,
            'pageTitle' => 'จัดการคำสั่งซื้อ Coin Shop',
        ]);
    }

    /**
     * หน้ารายละเอียดคำสั่งซื้อ (Admin)
     *
     * @return \Illuminate\View\View
     */
    public function purchaseDetail(CoinPurchase $purchase)
    {
        return view('admin.coin-shop.purchase-detail', [
            'purchase' => $purchase->load(['user', 'product', 'processor', 'refunder']),
            'statuses' => CoinPurchase::STATUSES,
            'pageTitle' => 'รายละเอียดคำสั่งซื้อ #'.$purchase->order_number,
        ]);
    }

    /**
     * อัปเดตสถานะคำสั่งซื้อ
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updatePurchaseStatus(Request $request, CoinPurchase $purchase)
    {
        $request->validate([
            'status' => 'required|in:'.implode(',', array_keys(CoinPurchase::STATUSES)),
            'note' => 'nullable|string|max:500',
            'tracking_number' => 'nullable|string|max:100',
        ]);

        $status = $request->status;
        $note = $request->note;
        $adminId = auth()->id();

        // ถ้ามีเลขพัสดุ ให้ตั้งค่าจัดส่ง
        if ($request->filled('tracking_number') && $status === 'completed') {
            $purchase->setShipped($request->tracking_number, $adminId);
        } else {
            $purchase->updateStatus($status, $note, $adminId);
        }

        return back()->with('success', 'อัปเดตสถานะสำเร็จ');
    }

    /**
     * คืนเงินคำสั่งซื้อ
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function refundPurchase(Request $request, CoinPurchase $purchase)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
            'amount' => 'nullable|numeric|min:0|max:'.$purchase->net_price,
        ]);

        if (! $purchase->is_refundable) {
            return back()->with('error', 'ไม่สามารถคืนเงินคำสั่งซื้อนี้ได้');
        }

        $success = $purchase->refund(
            auth()->id(),
            $request->reason,
            $request->amount
        );

        if ($success) {
            return back()->with('success', 'คืนเงินสำเร็จ');
        }

        return back()->with('error', 'เกิดข้อผิดพลาดในการคืนเงิน');
    }
}
