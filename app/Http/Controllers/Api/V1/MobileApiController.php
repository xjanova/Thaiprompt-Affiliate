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

    // =====================================================
    // LINE Login (Mobile)
    // =====================================================

    /**
     * ดึง LINE Login URL สำหรับ Mobile App
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getLineLoginUrl(Request $request): JsonResponse
    {
        $lineService = app(\App\Services\LineService::class);

        if (!$lineService->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'LINE Login ยังไม่ได้ตั้งค่า',
            ], 400);
        }

        // สร้าง state สำหรับ CSRF protection
        $state = Str::random(40);

        // ดึง referral code ถ้ามี
        $referralCode = $request->get('ref');

        // สร้าง callback URL สำหรับ mobile
        // Mobile app จะใช้ deep link หรือ universal link
        $redirectUri = config('services.line.mobile_redirect_uri')
            ?? url('/api/v1/auth/line/mobile-callback');

        $authUrl = $lineService->getAuthorizationUrl($state, $redirectUri);

        return response()->json([
            'success' => true,
            'data' => [
                'authUrl' => $authUrl,
                'state' => $state,
            ],
        ]);
    }

    /**
     * LINE Login callback สำหรับ Mobile App
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function lineLoginCallback(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
            'state' => 'required|string',
            'referral_code' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ครบถ้วน',
                'errors' => $validator->errors(),
            ], 422);
        }

        $lineService = app(\App\Services\LineService::class);
        $tokenService = app(\App\Services\LineTokenService::class);

        try {
            // แลก code เป็น access token
            $redirectUri = config('services.line.mobile_redirect_uri')
                ?? url('/api/v1/auth/line/mobile-callback');

            $tokenData = $lineService->getAccessToken($request->code, $redirectUri);
            $accessToken = $tokenData['access_token'];

            // ดึงข้อมูล profile
            $profile = $lineService->getUserProfile($accessToken);
            $lineUserId = $profile['userId'];
            $displayName = $profile['displayName'] ?? 'LINE User';
            $pictureUrl = $profile['pictureUrl'] ?? null;

            // ค้นหา user ด้วย LINE User ID
            $user = User::where('line_user_id', $lineUserId)->first();

            if ($user) {
                // User มีอยู่แล้ว - อัพเดทข้อมูล LINE
                $user->update([
                    'line_display_name' => $displayName,
                    'line_picture_url' => $pictureUrl,
                    'line_linked_at' => now(),
                    'line_verified' => true,
                ]);

                // เก็บ access token
                $expiresIn = $tokenData['expires_in'] ?? null;
                $tokenService->storeAccessToken($user, $accessToken, $expiresIn);

                // สร้าง API token
                $apiToken = $user->createToken('mobile-app-line')->plainTextToken;

                return response()->json([
                    'success' => true,
                    'message' => 'เข้าสู่ระบบด้วย LINE สำเร็จ',
                    'data' => [
                        'isNewUser' => false,
                        'token' => $apiToken,
                        'user' => [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'role' => $user->role,
                            'referralCode' => $user->referral_code,
                            'avatar' => $user->avatar ?? $pictureUrl,
                            'lineDisplayName' => $displayName,
                            'linePictureUrl' => $pictureUrl,
                        ],
                    ],
                ]);
            }

            // User ใหม่ - สร้างบัญชี
            DB::beginTransaction();

            // หา sponsor จาก referral code
            $sponsorId = null;
            if ($request->referral_code) {
                $sponsor = User::where('referral_code', $request->referral_code)->first();
                if ($sponsor) {
                    $sponsorId = $sponsor->id;
                }
            }

            // สร้าง referral code
            $referralCode = $this->generateReferralCode();

            // สร้าง user ใหม่
            $user = User::create([
                'name' => $displayName,
                'email' => $lineUserId . '@line.user', // email placeholder
                'password' => Hash::make(Str::random(32)), // random password
                'referral_code' => $referralCode,
                'sponsor_id' => $sponsorId,
                'role' => 'user',
                'line_user_id' => $lineUserId,
                'line_display_name' => $displayName,
                'line_picture_url' => $pictureUrl,
                'line_linked_at' => now(),
                'line_verified' => true,
            ]);

            // เก็บ access token
            $expiresIn = $tokenData['expires_in'] ?? null;
            $tokenService->storeAccessToken($user, $accessToken, $expiresIn);

            // สร้าง API token
            $apiToken = $user->createToken('mobile-app-line')->plainTextToken;

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'สร้างบัญชีและเข้าสู่ระบบด้วย LINE สำเร็จ',
                'data' => [
                    'isNewUser' => true,
                    'token' => $apiToken,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'referralCode' => $user->referral_code,
                        'avatar' => $pictureUrl,
                        'lineDisplayName' => $displayName,
                        'linePictureUrl' => $pictureUrl,
                    ],
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('LINE Login Mobile Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการเข้าสู่ระบบด้วย LINE',
            ], 500);
        }
    }

    // =====================================================
    // Wallet API (Mobile)
    // =====================================================

    /**
     * ดึงข้อมูลกระเป๋าเงิน
     *
     * @return JsonResponse
     */
    public function getWallet(): JsonResponse
    {
        $user = Auth::user();

        try {
            $walletService = app(\App\Services\WalletService::class);
            $wallet = $walletService->getOrCreateWallet($user);
            $statistics = $walletService->getWalletStatistics($wallet);

            return response()->json([
                'success' => true,
                'data' => [
                    'balance' => $wallet->balance ?? 0,
                    'availableBalance' => $wallet->available_balance ?? $wallet->balance ?? 0,
                    'pendingBalance' => $wallet->pending_balance ?? 0,
                    'totalIncome' => $statistics['total_income'] ?? 0,
                    'totalExpense' => $statistics['total_expense'] ?? 0,
                    'thisMonthIncome' => $statistics['this_month_income'] ?? 0,
                    'thisMonthExpense' => $statistics['this_month_expense'] ?? 0,
                    'currency' => $wallet->currency ?? 'THB',
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Get Wallet Error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถดึงข้อมูลกระเป๋าเงินได้',
            ], 500);
        }
    }

    /**
     * ดึงประวัติธุรกรรม
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getWalletTransactions(Request $request): JsonResponse
    {
        $user = Auth::user();
        $page = $request->get('page', 1);
        $perPage = min($request->get('per_page', 15), 50);
        $type = $request->get('type'); // in, out, all

        try {
            $walletService = app(\App\Services\WalletService::class);
            $wallet = $walletService->getOrCreateWallet($user);

            $query = $wallet->transactions()
                ->with('relatedWallet.user:id,name')
                ->latest();

            // กรองตามประเภท
            if ($type === 'in') {
                $query->where('amount', '>', 0);
            } elseif ($type === 'out') {
                $query->where('amount', '<', 0);
            }

            $transactions = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'items' => $transactions->map(function ($tx) {
                        return [
                            'id' => $tx->id,
                            'type' => $tx->amount > 0 ? 'in' : 'out',
                            'amount' => abs($tx->amount),
                            'title' => $tx->description ?? $this->getTransactionTitle($tx),
                            'status' => $tx->status ?? 'completed',
                            'date' => $tx->created_at->format('d M Y H:i'),
                            'dateRelative' => $tx->created_at->diffForHumans(),
                            'referenceType' => $tx->reference_type,
                            'referenceId' => $tx->reference_id,
                        ];
                    }),
                    'pagination' => [
                        'currentPage' => $transactions->currentPage(),
                        'lastPage' => $transactions->lastPage(),
                        'perPage' => $transactions->perPage(),
                        'total' => $transactions->total(),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Get Wallet Transactions Error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถดึงประวัติธุรกรรมได้',
            ], 500);
        }
    }

    /**
     * แปลงประเภท transaction เป็นชื่อภาษาไทย
     */
    private function getTransactionTitle($transaction): string
    {
        $types = [
            'commission' => 'คอมมิชชัน',
            'bonus' => 'โบนัส',
            'withdrawal' => 'ถอนเงิน',
            'deposit' => 'เติมเงิน',
            'transfer_in' => 'รับโอนเงิน',
            'transfer_out' => 'โอนเงิน',
            'purchase' => 'ซื้อสินค้า',
            'refund' => 'คืนเงิน',
            'cashback' => 'เงินคืน',
            'admin_adjustment' => 'ปรับยอดโดยแอดมิน',
        ];

        return $types[$transaction->type] ?? ($transaction->description ?? 'ธุรกรรม');
    }

    // =====================================================
    // KYC API (Mobile)
    // =====================================================

    /**
     * ดึงสถานะ KYC
     *
     * @return JsonResponse
     */
    public function getKycStatus(): JsonResponse
    {
        $user = Auth::user();

        $kyc = \App\Models\KycVerification::where('user_id', $user->id)
            ->latest()
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'status' => $user->kyc_status ?? 'not_submitted',
                'verifiedAt' => $user->kyc_verified_at?->format('Y-m-d H:i:s'),
                'submission' => $kyc ? [
                    'id' => $kyc->id,
                    'status' => $kyc->status,
                    'submittedAt' => $kyc->submitted_at?->format('Y-m-d H:i:s'),
                    'reviewedAt' => $kyc->reviewed_at?->format('Y-m-d H:i:s'),
                    'rejectionReason' => $kyc->rejection_reason,
                    'hasIdCard' => !empty($kyc->id_card_image),
                    'hasSelfie' => !empty($kyc->selfie_image),
                ] : null,
            ],
        ]);
    }

    /**
     * ส่ง KYC verification
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function submitKyc(Request $request): JsonResponse
    {
        $user = Auth::user();

        // ตรวจสอบว่ามี KYC ที่รออนุมัติอยู่หรือไม่
        $existingKyc = \App\Models\KycVerification::where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if ($existingKyc) {
            return response()->json([
                'success' => false,
                'message' => 'คุณมีคำขอ KYC ที่รอการตรวจสอบอยู่แล้ว',
            ], 400);
        }

        // ถ้าได้รับการอนุมัติแล้ว
        if ($user->kyc_status === 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'บัญชีของคุณได้รับการยืนยันตัวตนแล้ว',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'id_card_image' => 'required|image|mimes:jpeg,png,jpg|max:5120', // 5MB max
            'selfie_image' => 'required|image|mimes:jpeg,png,jpg|max:5120',
        ], [
            'id_card_image.required' => 'กรุณาอัพโหลดรูปบัตรประชาชน',
            'id_card_image.image' => 'ไฟล์ต้องเป็นรูปภาพ',
            'id_card_image.max' => 'ขนาดไฟล์ต้องไม่เกิน 5MB',
            'selfie_image.required' => 'กรุณาอัพโหลดรูปถ่ายคู่บัตร',
            'selfie_image.image' => 'ไฟล์ต้องเป็นรูปภาพ',
            'selfie_image.max' => 'ขนาดไฟล์ต้องไม่เกิน 5MB',
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

            // สร้าง directory สำหรับเก็บไฟล์ KYC
            $kycPath = 'kyc/' . $user->id;

            // อัพโหลดรูปบัตรประชาชน
            $idCardPath = $request->file('id_card_image')->store($kycPath, 'private');

            // อัพโหลดรูปถ่ายคู่บัตร
            $selfiePath = $request->file('selfie_image')->store($kycPath, 'private');

            // สร้าง KYC verification record
            $kyc = \App\Models\KycVerification::create([
                'user_id' => $user->id,
                'id_card_image' => $idCardPath,
                'selfie_image' => $selfiePath,
                'status' => 'pending',
                'submitted_at' => now(),
            ]);

            // อัพเดทสถานะ KYC ของ user
            $user->update([
                'kyc_status' => 'pending',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'ส่งเอกสารยืนยันตัวตนเรียบร้อย รอการตรวจสอบภายใน 24-48 ชั่วโมง',
                'data' => [
                    'kycId' => $kyc->id,
                    'status' => 'pending',
                    'submittedAt' => $kyc->submitted_at->format('Y-m-d H:i:s'),
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('KYC Submit Error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการส่งเอกสาร กรุณาลองใหม่',
            ], 500);
        }
    }

    /**
     * อัพโหลดรูปภาพ KYC แบบแยกทีละรูป (สำหรับ mobile app)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadKycImage(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'type' => 'required|in:id_card,selfie',
        ], [
            'image.required' => 'กรุณาอัพโหลดรูปภาพ',
            'image.image' => 'ไฟล์ต้องเป็นรูปภาพ',
            'image.max' => 'ขนาดไฟล์ต้องไม่เกิน 5MB',
            'type.required' => 'กรุณาระบุประเภทเอกสาร',
            'type.in' => 'ประเภทเอกสารไม่ถูกต้อง',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // สร้าง directory สำหรับเก็บไฟล์ KYC
            $kycPath = 'kyc/' . $user->id;
            $type = $request->type;

            // อัพโหลดรูป
            $imagePath = $request->file('image')->store($kycPath, 'private');

            // หา or สร้าง KYC draft
            $kyc = \App\Models\KycVerification::where('user_id', $user->id)
                ->whereIn('status', ['pending', 'draft'])
                ->latest()
                ->first();

            if (!$kyc) {
                // สร้าง draft ใหม่
                $kyc = \App\Models\KycVerification::create([
                    'user_id' => $user->id,
                    'status' => 'draft',
                    'id_card_image' => $type === 'id_card' ? $imagePath : null,
                    'selfie_image' => $type === 'selfie' ? $imagePath : null,
                ]);
            } else {
                // อัพเดท existing
                $column = $type === 'id_card' ? 'id_card_image' : 'selfie_image';
                $kyc->update([$column => $imagePath]);
            }

            return response()->json([
                'success' => true,
                'message' => 'อัพโหลดรูปภาพสำเร็จ',
                'data' => [
                    'kycId' => $kyc->id,
                    'type' => $type,
                    'hasIdCard' => !empty($kyc->id_card_image),
                    'hasSelfie' => !empty($kyc->selfie_image),
                    'canSubmit' => !empty($kyc->id_card_image) && !empty($kyc->selfie_image),
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('KYC Image Upload Error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการอัพโหลด กรุณาลองใหม่',
            ], 500);
        }
    }

    /**
     * ยืนยันส่ง KYC (หลังจาก upload รูปครบแล้ว)
     *
     * @return JsonResponse
     */
    public function confirmKycSubmission(): JsonResponse
    {
        $user = Auth::user();

        // หา KYC draft ที่มีเอกสารครบ
        $kyc = \App\Models\KycVerification::where('user_id', $user->id)
            ->whereIn('status', ['draft'])
            ->whereNotNull('id_card_image')
            ->whereNotNull('selfie_image')
            ->latest()
            ->first();

        if (!$kyc) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาอัพโหลดเอกสารให้ครบถ้วนก่อน',
            ], 400);
        }

        // อัพเดทสถานะเป็น pending
        $kyc->update([
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        // อัพเดทสถานะ user
        $user->update([
            'kyc_status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'ส่งเอกสารยืนยันตัวตนเรียบร้อย รอการตรวจสอบภายใน 24-48 ชั่วโมง',
            'data' => [
                'kycId' => $kyc->id,
                'status' => 'pending',
                'submittedAt' => $kyc->submitted_at->format('Y-m-d H:i:s'),
            ],
        ]);
    }
}
