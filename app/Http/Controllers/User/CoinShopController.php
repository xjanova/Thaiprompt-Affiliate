<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\CoinPurchase;
use App\Models\CoinShopProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * CoinShopController
 *
 * จัดการร้านค้า Video Coins สำหรับผู้ใช้
 */
class CoinShopController extends Controller
{
    /**
     * หน้าร้านค้า - แสดงรายการสินค้าทั้งหมด
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $userLevel = $user->videoLevel?->currentLevel?->level ?? 1;

        // สร้าง query
        $query = CoinShopProduct::purchasable()
            ->forLevel($userLevel)
            ->with(['minRank']);

        // กรองตามประเภท
        if ($request->filled('type')) {
            $query->ofType($request->type);
        }

        // กรองตามหมวดหมู่
        if ($request->filled('category')) {
            $query->inCategory($request->category);
        }

        // ค้นหา
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('name_th', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('description_th', 'like', "%{$search}%");
            });
        }

        // เรียงลำดับ
        $sortBy = $request->get('sort', 'recommended');
        switch ($sortBy) {
            case 'price_low':
                $query->orderBy('price_coins', 'asc');
                break;
            case 'price_high':
                $query->orderBy('price_coins', 'desc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'popular':
                $query->orderBy('total_sold', 'desc');
                break;
            default: // recommended
                $query->ordered();
                break;
        }

        // ดึงสินค้า
        $products = $query->paginate(12);

        // ดึงสินค้าแนะนำ
        $featuredProducts = CoinShopProduct::purchasable()
            ->featured()
            ->forLevel($userLevel)
            ->ordered()
            ->take(4)
            ->get();

        // ดึงหมวดหมู่ทั้งหมด
        $categories = CoinShopProduct::active()
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category');

        // ข้อมูล Coins ของผู้ใช้
        $videoCoin = $user->videoCoin;
        $coinBalance = $videoCoin?->balance ?? 0;

        return view('user.coin-shop.index', [
            'products' => $products,
            'featuredProducts' => $featuredProducts,
            'categories' => $categories,
            'coinBalance' => $coinBalance,
            'userLevel' => $userLevel,
            'currentType' => $request->type,
            'currentCategory' => $request->category,
            'currentSort' => $sortBy,
            'search' => $request->search,
            'pageTitle' => 'ร้านค้า Coins',
        ]);
    }

    /**
     * หน้ารายละเอียดสินค้า
     *
     * @param CoinShopProduct $product
     * @return \Illuminate\View\View
     */
    public function show(CoinShopProduct $product)
    {
        $user = auth()->user();

        // เพิ่มยอดเข้าชม
        $product->incrementViewCount();

        // ตรวจสอบว่าซื้อได้หรือไม่
        $purchaseCheck = $product->canPurchase($user);

        // ดึงประวัติการซื้อของผู้ใช้
        $userPurchases = CoinPurchase::forUser($user->id)
            ->forProduct($product->id)
            ->latest()
            ->take(5)
            ->get();

        // ดึงสินค้าที่เกี่ยวข้อง
        $relatedProducts = CoinShopProduct::purchasable()
            ->where('id', '!=', $product->id)
            ->where(function ($q) use ($product) {
                $q->where('type', $product->type)
                    ->orWhere('category', $product->category);
            })
            ->ordered()
            ->take(4)
            ->get();

        // ข้อมูล Coins ของผู้ใช้
        $videoCoin = $user->videoCoin;
        $coinBalance = $videoCoin?->balance ?? 0;

        return view('user.coin-shop.show', [
            'product' => $product,
            'canPurchase' => $purchaseCheck['can_purchase'],
            'purchaseReason' => $purchaseCheck['reason'],
            'userPurchases' => $userPurchases,
            'relatedProducts' => $relatedProducts,
            'coinBalance' => $coinBalance,
            'pageTitle' => $product->display_name,
        ]);
    }

    /**
     * ดำเนินการซื้อสินค้า
     *
     * @param Request $request
     * @param CoinShopProduct $product
     * @return \Illuminate\Http\RedirectResponse
     */
    public function purchase(Request $request, CoinShopProduct $product)
    {
        $request->validate([
            'quantity' => 'nullable|integer|min:1|max:100',
            'note' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();
        $quantity = $request->get('quantity', 1);

        // ตรวจสอบว่าซื้อได้หรือไม่
        $purchaseCheck = $product->canPurchase($user, $quantity);
        if (!$purchaseCheck['can_purchase']) {
            return back()
                ->with('error', $purchaseCheck['reason'])
                ->withInput();
        }

        DB::beginTransaction();
        try {
            // คำนวณราคา
            $pricePerItem = $product->price_coins;
            $totalPrice = $pricePerItem * $quantity;

            // หัก Coins จากผู้ใช้
            $videoCoin = $user->videoCoin;
            $videoCoin->subtractBalance($totalPrice, 'ซื้อสินค้า: ' . $product->display_name . ' x' . $quantity);

            // ลดสต็อก
            $product->decreaseStock($quantity);

            // สร้างคำสั่งซื้อ
            $purchase = CoinPurchase::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'price_coins' => $pricePerItem,
                'total_coins' => $totalPrice,
                'product_name' => $product->display_name,
                'product_type' => $product->type,
                'product_value' => $product->value_amount,
                'product_value_unit' => $product->value_unit,
                'status' => $this->getInitialStatus($product),
                'redemption_code' => $this->shouldGenerateCode($product) ? CoinPurchase::generateRedemptionCode() : null,
                'expires_at' => $product->calculateExpiryDate(),
                'user_note' => $request->note,
            ]);

            DB::commit();

            return redirect()
                ->route('user.coin-shop.purchase-success', $purchase)
                ->with('success', 'ซื้อสินค้าสำเร็จ!');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->with('error', 'เกิดข้อผิดพลาดในการซื้อสินค้า: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * หน้าซื้อสำเร็จ
     *
     * @param CoinPurchase $purchase
     * @return \Illuminate\View\View
     */
    public function purchaseSuccess(CoinPurchase $purchase)
    {
        $user = auth()->user();

        // ตรวจสอบว่าเป็นของผู้ใช้
        if ($purchase->user_id !== $user->id) {
            abort(403, 'ไม่มีสิทธิ์เข้าถึง');
        }

        return view('user.coin-shop.purchase-success', [
            'purchase' => $purchase->load('product'),
            'pageTitle' => 'ซื้อสินค้าสำเร็จ',
        ]);
    }

    /**
     * หน้าประวัติการซื้อ
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function myPurchases(Request $request)
    {
        $user = auth()->user();

        $query = CoinPurchase::forUser($user->id)
            ->with('product')
            ->latest();

        // กรองตามสถานะ
        if ($request->filled('status')) {
            $query->ofStatus($request->status);
        }

        // กรองตามประเภท
        if ($request->filled('type')) {
            $query->where('product_type', $request->type);
        }

        $purchases = $query->paginate(15);

        // สรุปสถิติ
        $stats = [
            'total_purchases' => CoinPurchase::forUser($user->id)->count(),
            'total_spent' => CoinPurchase::forUser($user->id)
                ->whereNotIn('status', ['cancelled', 'refunded'])
                ->sum('total_coins'),
            'active_items' => CoinPurchase::forUser($user->id)
                ->usable()
                ->count(),
        ];

        return view('user.coin-shop.my-purchases', [
            'purchases' => $purchases,
            'stats' => $stats,
            'currentStatus' => $request->status,
            'currentType' => $request->type,
            'pageTitle' => 'ประวัติการซื้อ',
        ]);
    }

    /**
     * หน้ารายละเอียดคำสั่งซื้อ
     *
     * @param CoinPurchase $purchase
     * @return \Illuminate\View\View
     */
    public function purchaseDetail(CoinPurchase $purchase)
    {
        $user = auth()->user();

        // ตรวจสอบว่าเป็นของผู้ใช้
        if ($purchase->user_id !== $user->id) {
            abort(403, 'ไม่มีสิทธิ์เข้าถึง');
        }

        return view('user.coin-shop.purchase-detail', [
            'purchase' => $purchase->load('product'),
            'pageTitle' => 'รายละเอียดคำสั่งซื้อ #' . $purchase->order_number,
        ]);
    }

    /**
     * ใช้งานรหัส/คูปอง
     *
     * @param Request $request
     * @param CoinPurchase $purchase
     * @return \Illuminate\Http\RedirectResponse
     */
    public function useItem(Request $request, CoinPurchase $purchase)
    {
        $user = auth()->user();

        // ตรวจสอบว่าเป็นของผู้ใช้
        if ($purchase->user_id !== $user->id) {
            abort(403, 'ไม่มีสิทธิ์เข้าถึง');
        }

        // ตรวจสอบว่าใช้งานได้หรือไม่
        if (!$purchase->is_usable) {
            return back()->with('error', 'ไม่สามารถใช้งานรายการนี้ได้');
        }

        $request->validate([
            'note' => 'nullable|string|max:500',
            'location' => 'nullable|string|max:255',
        ]);

        $success = $purchase->markAsUsed(
            $request->note,
            $request->location,
            ['used_via' => 'web', 'ip' => $request->ip()]
        );

        if ($success) {
            return back()->with('success', 'ใช้งานสำเร็จ!');
        }

        return back()->with('error', 'เกิดข้อผิดพลาดในการใช้งาน');
    }

    /**
     * กำหนดสถานะเริ่มต้นตามประเภทสินค้า
     *
     * @param CoinShopProduct $product
     * @return string
     */
    private function getInitialStatus(CoinShopProduct $product): string
    {
        // สินค้าดิจิทัล/คูปอง/สิทธิพิเศษ - สำเร็จทันที
        if (in_array($product->type, ['digital', 'voucher', 'coupon', 'privilege', 'exp_boost'])) {
            return 'completed';
        }

        // สินค้าจริง - รอดำเนินการ
        if ($product->type === 'physical') {
            return 'pending';
        }

        // แลกเงิน/TPIX - รอดำเนินการ
        if (in_array($product->type, ['money', 'tpix'])) {
            return 'processing';
        }

        return 'completed';
    }

    /**
     * ตรวจสอบว่าควรสร้างรหัสใช้งานหรือไม่
     *
     * @param CoinShopProduct $product
     * @return bool
     */
    private function shouldGenerateCode(CoinShopProduct $product): bool
    {
        return in_array($product->type, ['voucher', 'coupon', 'digital', 'privilege']);
    }
}
