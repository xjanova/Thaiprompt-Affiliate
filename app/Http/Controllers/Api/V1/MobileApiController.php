<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * MobileApiController
 *
 * API endpoints สำหรับ Mobile App
 * รองรับ: Register, Profile, Products, Cart, Orders
 */
class MobileApiController extends Controller
{
    // =====================================================
    // Authentication
    // =====================================================

    /**
     * สมัครสมาชิกใหม่
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'referral_code' => 'nullable|string|exists:users,referral_code',
        ], [
            'name.required' => 'กรุณากรอกชื่อ',
            'email.required' => 'กรุณากรอกอีเมล',
            'email.unique' => 'อีเมลนี้ถูกใช้งานแล้ว',
            'password.required' => 'กรุณากรอกรหัสผ่าน',
            'password.min' => 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร',
            'password.confirmed' => 'รหัสผ่านไม่ตรงกัน',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            // หา sponsor จาก referral code
            $sponsorId = null;
            if ($request->referral_code) {
                $sponsor = User::where('referral_code', $request->referral_code)->first();
                if ($sponsor) {
                    $sponsorId = $sponsor->id;
                }
            }

            // สร้าง referral code ใหม่
            $referralCode = $this->generateReferralCode();

            // สร้าง user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'referral_code' => $referralCode,
                'sponsor_id' => $sponsorId,
                'role' => 'user',
            ]);

            // สร้าง token
            $token = $user->createToken('mobile-app')->plainTextToken;

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'สมัครสมาชิกสำเร็จ',
                'data' => [
                    'token' => $token,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'referralCode' => $user->referral_code,
                        'avatar' => $user->avatar,
                        'permissions' => [],
                        'createdAt' => $user->created_at->toISOString(),
                    ],
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการสมัครสมาชิก',
            ], 500);
        }
    }

    /**
     * สร้าง referral code ใหม่
     */
    private function generateReferralCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (User::where('referral_code', $code)->exists());

        return $code;
    }

    // =====================================================
    // Profile
    // =====================================================

    /**
     * ดึงข้อมูลโปรไฟล์
     *
     * @return JsonResponse
     */
    public function getProfile(): JsonResponse
    {
        $user = Auth::user();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar' => $user->avatar,
                'role' => $user->role,
                'referralCode' => $user->referral_code,
                'referralLink' => url('/register?ref=' . $user->referral_code),
                'createdAt' => $user->created_at->toISOString(),
            ],
        ]);
    }

    /**
     * อัพเดทโปรไฟล์
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'avatar' => 'sometimes|nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user->update($request->only(['name', 'phone', 'avatar']));

        return response()->json([
            'success' => true,
            'message' => 'อัพเดทโปรไฟล์สำเร็จ',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar' => $user->avatar,
                'role' => $user->role,
                'referralCode' => $user->referral_code,
            ],
        ]);
    }

    /**
     * เปลี่ยนรหัสผ่าน
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'current_password.required' => 'กรุณากรอกรหัสผ่านปัจจุบัน',
            'password.required' => 'กรุณากรอกรหัสผ่านใหม่',
            'password.min' => 'รหัสผ่านใหม่ต้องมีอย่างน้อย 8 ตัวอักษร',
            'password.confirmed' => 'รหัสผ่านใหม่ไม่ตรงกัน',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();

        // ตรวจสอบรหัสผ่านปัจจุบัน
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'รหัสผ่านปัจจุบันไม่ถูกต้อง',
            ], 400);
        }

        // อัพเดทรหัสผ่าน
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'เปลี่ยนรหัสผ่านสำเร็จ',
        ]);
    }

    /**
     * ดึง Referral Code
     *
     * @return JsonResponse
     */
    public function getReferralCode(): JsonResponse
    {
        $user = Auth::user();

        return response()->json([
            'success' => true,
            'data' => [
                'referralCode' => $user->referral_code,
                'referralLink' => url('/register?ref=' . $user->referral_code),
            ],
        ]);
    }

    // =====================================================
    // Products
    // =====================================================

    /**
     * ดึงรายการสินค้า
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getProducts(Request $request): JsonResponse
    {
        $query = Product::query()
            ->where('is_active', true)
            ->with('category');

        // Filter by category
        if ($request->has('category') && $request->category) {
            $query->where('category_id', $request->category);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginate or all
        if ($request->has('per_page')) {
            $products = $query->paginate($request->per_page);
        } else {
            $products = $query->limit(50)->get();
        }

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    /**
     * ดึงรายละเอียดสินค้า
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getProduct(int $id): JsonResponse
    {
        $product = Product::with(['category', 'reviews'])
            ->where('is_active', true)
            ->find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบสินค้า',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
    }

    /**
     * ดึงหมวดหมู่สินค้า
     *
     * @return JsonResponse
     */
    public function getProductCategories(): JsonResponse
    {
        $categories = ProductCategory::withCount('products')
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    // =====================================================
    // Cart (ใช้ Session/Cache สำหรับ Guest, DB สำหรับ User)
    // =====================================================

    /**
     * ดึงตะกร้าสินค้า
     *
     * @return JsonResponse
     */
    public function getCart(): JsonResponse
    {
        $user = Auth::user();

        // ดึง cart จาก cache หรือ DB
        $cart = $this->getUserCart($user);

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $cart['items'] ?? [],
                'total' => $cart['total'] ?? 0,
                'itemCount' => $cart['item_count'] ?? 0,
            ],
        ]);
    }

    /**
     * เพิ่มสินค้าลงตะกร้า
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function addToCart(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();
        $product = Product::find($request->product_id);

        if (!$product || !$product->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'สินค้าไม่พร้อมจำหน่าย',
            ], 400);
        }

        // เพิ่มลง cart
        $cart = $this->addItemToCart($user, $product, $request->quantity);

        return response()->json([
            'success' => true,
            'message' => 'เพิ่มสินค้าลงตะกร้าแล้ว',
            'data' => [
                'items' => $cart['items'],
                'total' => $cart['total'],
                'itemCount' => $cart['item_count'],
            ],
        ]);
    }

    /**
     * อัพเดทจำนวนสินค้าในตะกร้า
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateCart(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();
        $cart = $this->updateCartItem($user, $request->product_id, $request->quantity);

        return response()->json([
            'success' => true,
            'message' => 'อัพเดทตะกร้าแล้ว',
            'data' => [
                'items' => $cart['items'],
                'total' => $cart['total'],
                'itemCount' => $cart['item_count'],
            ],
        ]);
    }

    /**
     * ลบสินค้าออกจากตะกร้า
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function removeFromCart(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();
        $cart = $this->removeCartItem($user, $request->product_id);

        return response()->json([
            'success' => true,
            'message' => 'ลบสินค้าออกจากตะกร้าแล้ว',
            'data' => [
                'items' => $cart['items'],
                'total' => $cart['total'],
                'itemCount' => $cart['item_count'],
            ],
        ]);
    }

    // =====================================================
    // Cart Helper Methods
    // =====================================================

    /**
     * ดึง cart ของ user
     */
    private function getUserCart($user): array
    {
        $cacheKey = "cart_{$user->id}";
        return cache()->get($cacheKey, [
            'items' => [],
            'total' => 0,
            'item_count' => 0,
        ]);
    }

    /**
     * บันทึก cart
     */
    private function saveUserCart($user, array $cart): void
    {
        $cacheKey = "cart_{$user->id}";
        cache()->put($cacheKey, $cart, now()->addDays(7));
    }

    /**
     * เพิ่มสินค้าลง cart
     */
    private function addItemToCart($user, $product, int $quantity): array
    {
        $cart = $this->getUserCart($user);
        $items = $cart['items'] ?? [];

        // ตรวจสอบว่ามีสินค้านี้ใน cart หรือยัง
        $found = false;
        foreach ($items as &$item) {
            if ($item['product_id'] == $product->id) {
                $item['quantity'] += $quantity;
                $item['subtotal'] = $item['quantity'] * $item['price'];
                $found = true;
                break;
            }
        }

        // ถ้ายังไม่มี ให้เพิ่มใหม่
        if (!$found) {
            $items[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'image' => $product->image,
                'price' => $product->discount_price ?? $product->price,
                'original_price' => $product->price,
                'quantity' => $quantity,
                'subtotal' => ($product->discount_price ?? $product->price) * $quantity,
            ];
        }

        // คำนวณ total
        $total = collect($items)->sum('subtotal');
        $itemCount = collect($items)->sum('quantity');

        $cart = [
            'items' => $items,
            'total' => $total,
            'item_count' => $itemCount,
        ];

        $this->saveUserCart($user, $cart);

        return $cart;
    }

    /**
     * อัพเดทจำนวนสินค้าใน cart
     */
    private function updateCartItem($user, int $productId, int $quantity): array
    {
        $cart = $this->getUserCart($user);
        $items = $cart['items'] ?? [];

        if ($quantity <= 0) {
            // ลบสินค้า
            $items = array_filter($items, fn($item) => $item['product_id'] != $productId);
            $items = array_values($items);
        } else {
            // อัพเดทจำนวน
            foreach ($items as &$item) {
                if ($item['product_id'] == $productId) {
                    $item['quantity'] = $quantity;
                    $item['subtotal'] = $item['quantity'] * $item['price'];
                    break;
                }
            }
        }

        // คำนวณ total
        $total = collect($items)->sum('subtotal');
        $itemCount = collect($items)->sum('quantity');

        $cart = [
            'items' => $items,
            'total' => $total,
            'item_count' => $itemCount,
        ];

        $this->saveUserCart($user, $cart);

        return $cart;
    }

    /**
     * ลบสินค้าออกจาก cart
     */
    private function removeCartItem($user, int $productId): array
    {
        return $this->updateCartItem($user, $productId, 0);
    }

    // =====================================================
    // Dashboard Charts
    // =====================================================

    /**
     * ดึงข้อมูล charts สำหรับ Dashboard
     *
     * @return JsonResponse
     */
    public function getDashboardCharts(): JsonResponse
    {
        $user = Auth::user();

        // ข้อมูล 7 วันล่าสุด
        $last7Days = collect(range(6, 0))->map(function ($daysAgo) use ($user) {
            $date = now()->subDays($daysAgo)->format('Y-m-d');
            $dayName = now()->subDays($daysAgo)->locale('th')->dayName;

            // นับรายได้วันนั้น (ถ้ามี Commission model)
            $earnings = 0; // Commission::where('user_id', $user->id)->whereDate('created_at', $date)->sum('amount');

            return [
                'date' => $date,
                'day' => $dayName,
                'earnings' => $earnings,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'earnings_chart' => $last7Days,
                'summary' => [
                    'total_this_week' => $last7Days->sum('earnings'),
                    'average_per_day' => $last7Days->avg('earnings'),
                ],
            ],
        ]);
    }

    // =====================================================
    // Referral
    // =====================================================

    /**
     * ดึง Referral Link และ Stats
     *
     * @return JsonResponse
     */
    public function getReferralLink(): JsonResponse
    {
        $user = Auth::user();

        // นับจำนวน referrals
        $totalReferrals = User::where('sponsor_id', $user->id)->count();
        $activeReferrals = User::where('sponsor_id', $user->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'referralCode' => $user->referral_code,
                'referralLink' => url('/register?ref=' . $user->referral_code),
                'totalReferrals' => $totalReferrals,
                'activeReferrals' => $activeReferrals,
                'pendingReferrals' => 0,
                'totalEarnings' => 0, // คำนวณจาก Commission model
                'monthlyEarnings' => 0,
            ],
        ]);
    }
}
