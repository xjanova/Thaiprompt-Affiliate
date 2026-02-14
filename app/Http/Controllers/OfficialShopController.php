<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\VideoCoin;
use App\Services\CashbackService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Official Shop Controller
 *
 * จัดการหน้าร้านของระบบ (Admin/Official Shop)
 * แยกออกจากร้านผู้เช่าโดยสิ้นเชิง
 */
class OfficialShopController extends Controller
{
    /**
     * แสดงหน้าร้านของระบบ
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // Query สินค้าของระบบเท่านั้น (Official Shop)
        $query = Product::with(['category'])
            ->active()
            ->inStock()
            ->officialShop(); // เฉพาะสินค้าของระบบ

        // ค้นหา
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // กรองตามหมวดหมู่
        if ($request->filled('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        // กรองตามช่วงราคา
        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // กรองตามยี่ห้อ
        if ($request->filled('brand')) {
            $query->where('brand', $request->brand);
        }

        // เรียงลำดับ
        $sortBy = $request->get('sort_by', 'newest');
        switch ($sortBy) {
            case 'popular':
                $query->orderBy('sales_count', 'desc');
                break;
            case 'price_low':
                $query->orderBy('price', 'asc');
                break;
            case 'price_high':
                $query->orderBy('price', 'desc');
                break;
            case 'rating':
                $query->orderBy('rating_average', 'desc');
                break;
            case 'newest':
            default:
                $query->latest('published_at');
                break;
        }

        $products = $query->paginate(24)->withQueryString();

        // Get categories for filter (เฉพาะที่มีสินค้าของระบบ)
        $categories = ProductCategory::active()
            ->whereHas('products', function ($q) {
                $q->active()->inStock()->officialShop();
            })
            ->root()
            ->orderBy('sort_order')
            ->get();

        // Get unique brands from official products
        $brands = Product::active()
            ->inStock()
            ->officialShop()
            ->whereNotNull('brand')
            ->distinct('brand')
            ->pluck('brand')
            ->sort();

        // สินค้าแนะนำของระบบ
        $featuredProducts = Product::with(['category'])
            ->active()
            ->featured()
            ->inStock()
            ->officialShop()
            ->take(8)
            ->get();

        // สถิติร้านของระบบ
        $stats = [
            'official' => Product::active()->inStock()->officialShop()->count(),
            'featured' => Product::active()->featured()->inStock()->officialShop()->count(),
            'categories' => $categories->count(),
        ];

        return view('shop.official.index', compact(
            'products',
            'categories',
            'brands',
            'featuredProducts',
            'stats'
        ));
    }

    /**
     * แสดงรายละเอียดสินค้าของระบบ
     *
     * @param  string  $slug
     * @return \Illuminate\View\View
     */
    public function show($slug)
    {
        $product = Product::with([
            'category',
            'images',
            'variants',
            'approvedReviews.user',
        ])
            ->officialShop() // ต้องเป็นสินค้าของระบบ
            ->where('slug', $slug)
            ->firstOrFail();

        // Increment view count
        $product->incrementViewCount();

        // Related products (จากระบบเท่านั้น)
        $relatedProducts = Product::with(['category'])
            ->active()
            ->inStock()
            ->officialShop()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->take(8)
            ->get();

        // Check if user has purchased this product (for review)
        $hasPurchased = false;
        if (auth()->check()) {
            $hasPurchased = $product->orderItems()
                ->whereHas('order', function ($q) {
                    $q->where('user_id', auth()->id())
                        ->where('status', 'completed');
                })
                ->exists();
        }

        // Calculate potential cashback
        $cashbackService = new CashbackService(new WalletService);
        $cashbackInfo = $cashbackService->calculateProductCashback($product, $product->price, 1);

        // Get user's coin balance
        $coinBalance = 0;
        if (auth()->check()) {
            $videoCoin = auth()->user()->videoCoin;
            $coinBalance = $videoCoin ? $videoCoin->balance : 0;
        }

        return view('shop.official.show', compact(
            'product',
            'relatedProducts',
            'hasPurchased',
            'cashbackInfo',
            'coinBalance'
        ));
    }

    /**
     * แสดงสินค้าตามหมวดหมู่ (ของระบบ)
     *
     * @param  string  $slug
     * @return \Illuminate\View\View
     */
    public function category($slug, Request $request)
    {
        $category = ProductCategory::where('slug', $slug)
            ->active()
            ->firstOrFail();

        $query = Product::with(['category'])
            ->active()
            ->inStock()
            ->officialShop() // เฉพาะของระบบ
            ->where('category_id', $category->id);

        // Apply same filters and sorting as index
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'newest');
        switch ($sortBy) {
            case 'popular':
                $query->orderBy('sales_count', 'desc');
                break;
            case 'price_low':
                $query->orderBy('price', 'asc');
                break;
            case 'price_high':
                $query->orderBy('price', 'desc');
                break;
            case 'rating':
                $query->orderBy('rating_average', 'desc');
                break;
            case 'newest':
            default:
                $query->latest('published_at');
                break;
        }

        $products = $query->paginate(24)->withQueryString();

        // Stats
        $stats = [
            'official' => $query->count(),
        ];

        return view('shop.official.category', compact('category', 'products', 'stats'));
    }

    /**
     * Quick search API (เฉพาะสินค้าของระบบ)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function quickSearch(Request $request)
    {
        $search = $request->get('q', '');

        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $products = Product::active()
            ->inStock()
            ->officialShop() // เฉพาะของระบบ
            ->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            })
            ->select('id', 'name', 'slug', 'price', 'main_image_url')
            ->take(10)
            ->get();

        return response()->json($products);
    }

    /**
     * แสดงสินค้าแนะนำของระบบ
     *
     * @return \Illuminate\View\View
     */
    public function featured()
    {
        $products = Product::with(['category'])
            ->active()
            ->featured()
            ->inStock()
            ->officialShop()
            ->latest('published_at')
            ->paginate(24);

        $stats = [
            'official' => Product::active()->inStock()->officialShop()->count(),
            'featured' => $products->total(),
        ];

        return view('shop.official.featured', compact('products', 'stats'));
    }

    /**
     * ซื้อสินค้าด้วย Coins
     *
     * @param  string  $slug
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function purchaseWithCoin(Request $request, $slug)
    {
        // ต้อง login ก่อน
        if (! auth()->check()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'กรุณาเข้าสู่ระบบก่อนซื้อสินค้า',
                ], 401);
            }

            return redirect()->route('login')->with('error', 'กรุณาเข้าสู่ระบบก่อนซื้อสินค้า');
        }

        $user = auth()->user();

        // ดึงข้อมูลสินค้า
        $product = Product::whereNull('seller_id')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        // ตรวจสอบว่าสินค้ารองรับการซื้อด้วย Coins หรือไม่
        if (! $product->allow_coin_purchase || $product->price_coins <= 0) {
            $message = 'สินค้านี้ไม่รองรับการซื้อด้วย Coins';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 400);
            }

            return back()->with('error', $message);
        }

        // ตรวจสอบจำนวน
        $quantity = max(1, intval($request->input('quantity', 1)));

        // ตรวจสอบสต็อก
        if ($product->track_inventory && $product->stock_quantity < $quantity) {
            $message = 'สินค้าในสต็อกไม่เพียงพอ';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 400);
            }

            return back()->with('error', $message);
        }

        // คำนวณราคารวม
        $totalCoins = $product->price_coins * $quantity;

        // ดึง VideoCoin ของผู้ใช้
        $videoCoin = $user->videoCoin;

        // ตรวจสอบ Coins เพียงพอหรือไม่
        if (! $videoCoin || $videoCoin->balance < $totalCoins) {
            $currentBalance = $videoCoin ? $videoCoin->balance : 0;
            $message = "Coins ไม่เพียงพอ (ต้องการ {$totalCoins} Coins, มี {$currentBalance} Coins)";
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'required' => $totalCoins,
                    'balance' => $currentBalance,
                ], 400);
            }

            return back()->with('error', $message);
        }

        try {
            DB::beginTransaction();

            // หัก Coins
            $videoCoin->deductCoins(
                $totalCoins,
                'spent_purchase',
                'Product',
                $product->id,
                "ซื้อสินค้า Official Shop: {$product->name} x {$quantity}"
            );

            // สร้าง Order
            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => 'OFF-'.strtoupper(Str::random(8)).'-'.time(),
                'status' => 'completed', // สินค้า Official Shop ถือว่าสำเร็จทันที
                'payment_status' => 'paid',
                'payment_method' => 'coins',
                'subtotal' => 0, // ไม่ใช้เงิน
                'shipping_fee' => 0,
                'discount' => 0,
                'total' => 0,
                'coins_used' => $totalCoins,
                'notes' => 'ซื้อด้วย Coins จาก Official Shop',
                'shipping_name' => $user->name,
                'shipping_phone' => $user->phone,
                'shipping_address' => $user->address,
            ]);

            // สร้าง OrderItem
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'seller_id' => $product->seller_id ?? Product::getOfficialSellerId(),
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'price' => 0,
                'price_coins' => $product->price_coins,
                'quantity' => $quantity,
                'subtotal' => 0,
                'subtotal_coins' => $totalCoins,
            ]);

            // ลดสต็อก (ถ้าติดตามสต็อก)
            if ($product->track_inventory) {
                $product->decrement('stock_quantity', $quantity);
            }

            // เพิ่มยอดขาย
            $product->increment('sales_count', $quantity);

            DB::commit();

            $message = "ซื้อสินค้าสำเร็จ! ใช้ไป {$totalCoins} Coins";

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'coins_used' => $totalCoins,
                    'new_balance' => $videoCoin->fresh()->balance,
                ]);
            }

            return redirect()->route('official-shop.purchase-success', $order->order_number)
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();

            $message = 'เกิดข้อผิดพลาด: '.$e->getMessage();
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 500);
            }

            return back()->with('error', $message);
        }
    }

    /**
     * หน้าซื้อสำเร็จ
     *
     * @param  string  $orderNumber
     * @return \Illuminate\View\View
     */
    public function purchaseSuccess($orderNumber)
    {
        $order = Order::with(['items.product'])
            ->where('order_number', $orderNumber)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        return view('shop.official.purchase-success', compact('order'));
    }
}
