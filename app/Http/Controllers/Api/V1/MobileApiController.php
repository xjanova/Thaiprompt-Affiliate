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
                    'walletAddress' => $wallet->wallet_address ?? null,
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
     * ดึงยอดคงเหลือกระเป๋าเงิน (แบบ lightweight สำหรับ topbar)
     *
     * @return JsonResponse
     */
    public function getWalletBalance(): JsonResponse
    {
        $user = Auth::user();

        try {
            $walletService = app(\App\Services\WalletService::class);
            $wallet = $walletService->getOrCreateWallet($user);

            return response()->json([
                'success' => true,
                'balance' => (float) ($wallet->balance ?? 0),
                'currency' => $wallet->currency ?? 'THB',
            ]);
        } catch (\Exception $e) {
            \Log::error('Get Wallet Balance Error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'balance' => 0,
                'message' => 'ไม่สามารถดึงยอดคงเหลือได้',
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

    // =====================================================
    // Rider APIs
    // =====================================================

    /**
     * ดึงสถานะการสมัครเป็นไรเดอร์
     *
     * @return JsonResponse
     */
    public function getRiderStatus(): JsonResponse
    {
        $user = Auth::user();
        $rider = \App\Models\Rider::where('user_id', $user->id)->first();

        if (!$rider) {
            return response()->json([
                'success' => true,
                'data' => [
                    'isRider' => false,
                    'status' => null,
                    'message' => 'คุณยังไม่ได้สมัครเป็นไรเดอร์',
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'isRider' => true,
                'riderId' => $rider->id,
                'status' => $rider->status,
                'statusText' => $rider->status_text,
                'availability' => $rider->availability,
                'availabilityText' => $rider->availability_text,
                'vehicleType' => $rider->vehicle_type,
                'vehicleTypeText' => $rider->vehicle_type_text,
                'rating' => $rider->rating,
                'totalJobs' => $rider->total_jobs,
                'completedJobs' => $rider->completed_jobs,
                'completionRate' => $rider->completion_rate,
                'totalEarnings' => $rider->total_earnings,
                'permissions' => [
                    'gps' => $rider->gps_permission_granted,
                    'camera' => $rider->camera_permission_granted,
                    'microphone' => $rider->microphone_permission_granted,
                    'notification' => $rider->notification_permission_granted,
                    'allGranted' => $rider->has_all_permissions,
                ],
                'rejectionReason' => $rider->rejection_reason,
                'approvedAt' => $rider->approved_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * สมัครเป็นไรเดอร์
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function registerRider(Request $request): JsonResponse
    {
        $user = Auth::user();

        // ตรวจสอบว่าสมัครแล้วหรือยัง
        $existingRider = \App\Models\Rider::where('user_id', $user->id)->first();
        if ($existingRider) {
            return response()->json([
                'success' => false,
                'message' => 'คุณได้สมัครเป็นไรเดอร์แล้ว',
                'data' => [
                    'riderId' => $existingRider->id,
                    'status' => $existingRider->status,
                ],
            ], 400);
        }

        // Validate
        $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'id_card_number' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date',
            'address' => 'nullable|string',
            'province' => 'nullable|string',
            'district' => 'nullable|string',
            'vehicle_type' => 'required|in:motorcycle,car,bicycle,walk',
            'vehicle_plate' => 'nullable|string|max:20',
            'vehicle_brand' => 'nullable|string',
            'vehicle_color' => 'nullable|string',
        ]);

        // สร้างไรเดอร์ใหม่
        $rider = \App\Models\Rider::create([
            'user_id' => $user->id,
            'full_name' => $request->full_name,
            'phone' => $request->phone,
            'id_card_number' => $request->id_card_number,
            'birth_date' => $request->birth_date,
            'address' => $request->address,
            'province' => $request->province,
            'district' => $request->district,
            'vehicle_type' => $request->vehicle_type,
            'vehicle_plate' => $request->vehicle_plate,
            'vehicle_brand' => $request->vehicle_brand,
            'vehicle_color' => $request->vehicle_color,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'สมัครเป็นไรเดอร์สำเร็จ รอการอนุมัติจากเจ้าหน้าที่',
            'data' => [
                'riderId' => $rider->id,
                'status' => $rider->status,
                'statusText' => $rider->status_text,
            ],
        ]);
    }

    /**
     * อัพโหลดเอกสารไรเดอร์
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadRiderDocument(Request $request): JsonResponse
    {
        $user = Auth::user();
        $rider = \App\Models\Rider::where('user_id', $user->id)->first();

        if (!$rider) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาสมัครเป็นไรเดอร์ก่อน',
            ], 400);
        }

        // Validate
        $request->validate([
            'type' => 'required|in:id_card,driver_license,vehicle_registration,profile',
            'image' => 'required|image|max:10240', // Max 10MB
        ]);

        $type = $request->type;
        $columnMap = [
            'id_card' => 'id_card_image',
            'driver_license' => 'driver_license_image',
            'vehicle_registration' => 'vehicle_registration_image',
            'profile' => 'profile_image',
        ];

        // บันทึกไฟล์
        $path = $request->file('image')->store(
            "riders/{$rider->id}/{$type}",
            'private'
        );

        // อัพเดท rider
        $column = $columnMap[$type];
        $rider->update([$column => $path]);

        return response()->json([
            'success' => true,
            'message' => 'อัพโหลดเอกสารสำเร็จ',
            'data' => [
                'type' => $type,
                'uploaded' => true,
            ],
        ]);
    }

    /**
     * บันทึกสิทธิ์ที่ได้รับจากผู้ใช้
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateRiderPermissions(Request $request): JsonResponse
    {
        $user = Auth::user();
        $rider = \App\Models\Rider::where('user_id', $user->id)->first();

        if (!$rider) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาสมัครเป็นไรเดอร์ก่อน',
            ], 400);
        }

        // Validate
        $request->validate([
            'gps' => 'nullable|boolean',
            'camera' => 'nullable|boolean',
            'microphone' => 'nullable|boolean',
            'notification' => 'nullable|boolean',
        ]);

        // บันทึกสิทธิ์
        $rider->grantPermissions([
            'gps' => $request->gps,
            'camera' => $request->camera,
            'microphone' => $request->microphone,
            'notification' => $request->notification,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'บันทึกสิทธิ์เรียบร้อย',
            'data' => [
                'permissions' => [
                    'gps' => $rider->fresh()->gps_permission_granted,
                    'camera' => $rider->fresh()->camera_permission_granted,
                    'microphone' => $rider->fresh()->microphone_permission_granted,
                    'notification' => $rider->fresh()->notification_permission_granted,
                    'allGranted' => $rider->fresh()->has_all_permissions,
                ],
            ],
        ]);
    }

    /**
     * ตั้งค่าสถานะออนไลน์/ออฟไลน์
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function setRiderAvailability(Request $request): JsonResponse
    {
        $user = Auth::user();
        $rider = \App\Models\Rider::where('user_id', $user->id)
            ->where('status', 'approved')
            ->first();

        if (!$rider) {
            return response()->json([
                'success' => false,
                'message' => 'คุณยังไม่ได้รับการอนุมัติเป็นไรเดอร์',
            ], 400);
        }

        // Validate
        $request->validate([
            'availability' => 'required|in:online,offline',
        ]);

        // ตรวจสอบสิทธิ์ GPS ก่อนเปิดออนไลน์
        if ($request->availability === 'online' && !$rider->gps_permission_granted) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาอนุญาตการเข้าถึงตำแหน่งก่อนเปิดรับงาน',
                'requirePermission' => 'gps',
            ], 400);
        }

        if ($request->availability === 'online') {
            $rider->goOnline();
        } else {
            $rider->goOffline();
        }

        return response()->json([
            'success' => true,
            'message' => $request->availability === 'online' ? 'เปิดรับงานแล้ว' : 'ปิดรับงานแล้ว',
            'data' => [
                'availability' => $rider->fresh()->availability,
                'availabilityText' => $rider->fresh()->availability_text,
            ],
        ]);
    }

    /**
     * อัพเดทตำแหน่ง GPS
     * เก็บเฉพาะตอนที่มีงานเท่านั้น
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateRiderLocation(Request $request): JsonResponse
    {
        $user = Auth::user();
        $rider = \App\Models\Rider::where('user_id', $user->id)
            ->where('status', 'approved')
            ->first();

        if (!$rider) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลไรเดอร์',
            ], 400);
        }

        // Validate
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'altitude' => 'nullable|numeric',
            'accuracy' => 'nullable|numeric',
            'speed' => 'nullable|numeric',
            'heading' => 'nullable|numeric|between:0,360',
            'battery_level' => 'nullable|integer|between:0,100',
            'is_charging' => 'nullable|boolean',
            'activity_type' => 'nullable|in:still,walking,running,cycling,driving,unknown',
            'device_model' => 'nullable|string',
            'os_version' => 'nullable|string',
        ]);

        // อัพเดทตำแหน่งล่าสุดของไรเดอร์
        $rider->updateLocation($request->latitude, $request->longitude);

        // ดึงงานที่กำลังดำเนินการอยู่
        $activeJob = \App\Models\RiderJob::where('rider_id', $rider->id)
            ->whereIn('status', ['accepted', 'picking_up', 'picked_up', 'delivering'])
            ->first();

        // บันทึกประวัติตำแหน่งเฉพาะเมื่อมีงาน
        if ($activeJob) {
            \App\Models\RiderLocation::recordLocation($rider->id, [
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'altitude' => $request->altitude,
                'accuracy' => $request->accuracy,
                'speed' => $request->speed,
                'heading' => $request->heading,
                'battery_level' => $request->battery_level,
                'is_charging' => $request->is_charging,
                'activity_type' => $request->activity_type,
                'device_model' => $request->device_model,
                'os_version' => $request->os_version,
                'recorded_at' => now(),
            ], $activeJob->id);
        }

        return response()->json([
            'success' => true,
            'message' => 'อัพเดทตำแหน่งสำเร็จ',
            'data' => [
                'hasActiveJob' => !is_null($activeJob),
                'jobId' => $activeJob?->id,
                'isTracking' => !is_null($activeJob),
            ],
        ]);
    }

    /**
     * ดึงงานที่รอไรเดอร์
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAvailableJobs(Request $request): JsonResponse
    {
        $user = Auth::user();
        $rider = \App\Models\Rider::where('user_id', $user->id)
            ->where('status', 'approved')
            ->where('availability', 'online')
            ->first();

        if (!$rider) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเปิดรับงานก่อน',
            ], 400);
        }

        // ดึงงานที่รอไรเดอร์ใกล้เคียง
        $query = \App\Models\RiderJob::where('status', 'pending');

        // ถ้ามีตำแหน่งล่าสุด ให้จัดเรียงตามระยะทาง
        if ($rider->last_latitude && $rider->last_longitude) {
            $query->selectRaw("*,
                (6371 * acos(cos(radians(?)) * cos(radians(pickup_latitude)) *
                cos(radians(pickup_longitude) - radians(?)) +
                sin(radians(?)) * sin(radians(pickup_latitude)))) AS distance_km",
                [$rider->last_latitude, $rider->last_longitude, $rider->last_latitude])
                ->having('distance_km', '<=', 10) // ภายใน 10 กม.
                ->orderBy('distance_km');
        } else {
            $query->latest();
        }

        $jobs = $query->limit(20)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'jobs' => $jobs->map(function ($job) {
                    return [
                        'id' => $job->id,
                        'jobNumber' => $job->job_number,
                        'jobType' => $job->job_type,
                        'jobTypeText' => $job->job_type_text,
                        'title' => $job->title,
                        'pickup' => [
                            'address' => $job->pickup_address,
                            'latitude' => $job->pickup_latitude,
                            'longitude' => $job->pickup_longitude,
                        ],
                        'delivery' => [
                            'address' => $job->delivery_address,
                            'latitude' => $job->delivery_latitude,
                            'longitude' => $job->delivery_longitude,
                        ],
                        'distanceKm' => $job->distance_km ?? null,
                        'totalFee' => $job->total_fee,
                        'riderEarnings' => $job->rider_earnings,
                        'createdAt' => $job->created_at->format('Y-m-d H:i:s'),
                    ];
                }),
                'riderLocation' => [
                    'latitude' => $rider->last_latitude,
                    'longitude' => $rider->last_longitude,
                ],
            ],
        ]);
    }

    /**
     * รับงาน
     *
     * @param Request $request
     * @param int $jobId
     * @return JsonResponse
     */
    public function acceptJob(Request $request, int $jobId): JsonResponse
    {
        $user = Auth::user();
        $rider = \App\Models\Rider::where('user_id', $user->id)
            ->where('status', 'approved')
            ->first();

        if (!$rider) {
            return response()->json([
                'success' => false,
                'message' => 'คุณยังไม่ได้รับการอนุมัติเป็นไรเดอร์',
            ], 400);
        }

        // ตรวจสอบว่ามีงานค้างอยู่หรือไม่
        $activeJob = \App\Models\RiderJob::where('rider_id', $rider->id)
            ->whereIn('status', ['accepted', 'picking_up', 'picked_up', 'delivering'])
            ->first();

        if ($activeJob) {
            return response()->json([
                'success' => false,
                'message' => 'คุณมีงานที่ยังไม่เสร็จอยู่',
                'data' => [
                    'activeJobId' => $activeJob->id,
                ],
            ], 400);
        }

        $job = \App\Models\RiderJob::find($jobId);

        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบงานนี้',
            ], 404);
        }

        if ($job->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'งานนี้ถูกรับไปแล้ว',
            ], 400);
        }

        // อัปเดทงาน
        $job->rider_id = $rider->id;
        $job->accept();

        return response()->json([
            'success' => true,
            'message' => 'รับงานสำเร็จ! กรุณาเดินทางไปรับของ',
            'data' => [
                'job' => [
                    'id' => $job->id,
                    'jobNumber' => $job->job_number,
                    'status' => $job->status,
                    'statusText' => $job->status_text,
                    'pickup' => [
                        'address' => $job->pickup_address,
                        'latitude' => $job->pickup_latitude,
                        'longitude' => $job->pickup_longitude,
                        'contactName' => $job->pickup_contact_name,
                        'contactPhone' => $job->pickup_contact_phone,
                        'notes' => $job->pickup_notes,
                    ],
                    'delivery' => [
                        'address' => $job->delivery_address,
                        'latitude' => $job->delivery_latitude,
                        'longitude' => $job->delivery_longitude,
                        'contactName' => $job->delivery_contact_name,
                        'contactPhone' => $job->delivery_contact_phone,
                        'notes' => $job->delivery_notes,
                    ],
                ],
                'trackingEnabled' => true,
                'message' => 'ระบบจะเริ่มติดตามตำแหน่งของคุณเพื่อแจ้งลูกค้า',
            ],
        ]);
    }

    /**
     * ดึงงานปัจจุบันของไรเดอร์
     *
     * @return JsonResponse
     */
    public function getCurrentJob(): JsonResponse
    {
        $user = Auth::user();
        $rider = \App\Models\Rider::where('user_id', $user->id)->first();

        if (!$rider) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลไรเดอร์',
            ], 400);
        }

        $job = \App\Models\RiderJob::where('rider_id', $rider->id)
            ->whereIn('status', ['accepted', 'picking_up', 'picked_up', 'delivering'])
            ->first();

        if (!$job) {
            return response()->json([
                'success' => true,
                'data' => [
                    'hasJob' => false,
                    'job' => null,
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'hasJob' => true,
                'job' => [
                    'id' => $job->id,
                    'jobNumber' => $job->job_number,
                    'jobType' => $job->job_type,
                    'jobTypeText' => $job->job_type_text,
                    'title' => $job->title,
                    'description' => $job->description,
                    'status' => $job->status,
                    'statusText' => $job->status_text,
                    'pickup' => [
                        'address' => $job->pickup_address,
                        'latitude' => $job->pickup_latitude,
                        'longitude' => $job->pickup_longitude,
                        'contactName' => $job->pickup_contact_name,
                        'contactPhone' => $job->pickup_contact_phone,
                        'notes' => $job->pickup_notes,
                    ],
                    'delivery' => [
                        'address' => $job->delivery_address,
                        'latitude' => $job->delivery_latitude,
                        'longitude' => $job->delivery_longitude,
                        'contactName' => $job->delivery_contact_name,
                        'contactPhone' => $job->delivery_contact_phone,
                        'notes' => $job->delivery_notes,
                    ],
                    'distanceKm' => $job->distance_km,
                    'totalFee' => $job->total_fee,
                    'riderEarnings' => $job->rider_earnings,
                    'acceptedAt' => $job->accepted_at?->format('Y-m-d H:i:s'),
                    'pickedUpAt' => $job->picked_up_at?->format('Y-m-d H:i:s'),
                ],
                'isTracking' => true,
            ],
        ]);
    }

    /**
     * อัพเดทสถานะงาน
     *
     * @param Request $request
     * @param int $jobId
     * @return JsonResponse
     */
    public function updateJobStatus(Request $request, int $jobId): JsonResponse
    {
        $user = Auth::user();
        $rider = \App\Models\Rider::where('user_id', $user->id)->first();

        if (!$rider) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลไรเดอร์',
            ], 400);
        }

        $job = \App\Models\RiderJob::where('id', $jobId)
            ->where('rider_id', $rider->id)
            ->first();

        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบงานนี้',
            ], 404);
        }

        // Validate
        $request->validate([
            'status' => 'required|in:picking_up,picked_up,delivering,delivered,completed,cancelled',
            'proof_image' => 'nullable|image|max:10240',
            'signature_image' => 'nullable|image|max:10240',
            'cancellation_reason' => 'required_if:status,cancelled|string|max:500',
        ]);

        $newStatus = $request->status;

        try {
            switch ($newStatus) {
                case 'picking_up':
                    $job->arrivedAtPickup();
                    $message = 'ถึงจุดรับของแล้ว';
                    break;

                case 'picked_up':
                    $proofImage = null;
                    if ($request->hasFile('proof_image')) {
                        $proofImage = $request->file('proof_image')->store(
                            "rider_jobs/{$job->id}/pickup_proof",
                            'public'
                        );
                    }
                    $job->pickUp($proofImage);
                    $message = 'รับของเรียบร้อย กำลังเดินทางไปส่ง';
                    break;

                case 'delivering':
                    $job->startDelivery();
                    $message = 'กำลังจัดส่ง';
                    break;

                case 'delivered':
                    $proofImage = null;
                    $signatureImage = null;
                    if ($request->hasFile('proof_image')) {
                        $proofImage = $request->file('proof_image')->store(
                            "rider_jobs/{$job->id}/delivery_proof",
                            'public'
                        );
                    }
                    if ($request->hasFile('signature_image')) {
                        $signatureImage = $request->file('signature_image')->store(
                            "rider_jobs/{$job->id}/signature",
                            'public'
                        );
                    }
                    $job->deliver($proofImage, $signatureImage);
                    $message = 'ส่งของเรียบร้อยแล้ว';
                    break;

                case 'completed':
                    $job->complete();
                    $message = 'งานเสร็จสิ้น! ขอบคุณครับ';
                    break;

                case 'cancelled':
                    $job->cancel($request->cancellation_reason, 'rider');
                    $message = 'ยกเลิกงานแล้ว';
                    break;

                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'สถานะไม่ถูกต้อง',
                    ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'jobId' => $job->id,
                    'status' => $job->fresh()->status,
                    'statusText' => $job->fresh()->status_text,
                    'isTracking' => $job->fresh()->is_trackable,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    // =====================================================
    // Support Tickets
    // =====================================================

    /**
     * ดึงรายการ tickets ของผู้ใช้
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTickets(Request $request): JsonResponse
    {
        $user = Auth::user();

        $tickets = \App\Models\SupportTicket::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => [
                'tickets' => $tickets->map(function ($ticket) {
                    return [
                        'id' => $ticket->id,
                        'ticketNumber' => $ticket->ticket_number,
                        'subject' => $ticket->subject,
                        'category' => $ticket->category,
                        'categoryText' => $ticket->category_text,
                        'priority' => $ticket->priority,
                        'priorityText' => $ticket->priority_text,
                        'status' => $ticket->status,
                        'statusText' => $ticket->status_text,
                        'hasUnreadAdminMessage' => $ticket->has_unread_admin_message,
                        'messageCount' => $ticket->message_count,
                        'createdAt' => $ticket->created_at->format('Y-m-d H:i:s'),
                        'lastMessageAt' => $ticket->last_message_at?->format('Y-m-d H:i:s'),
                    ];
                }),
                'pagination' => [
                    'total' => $tickets->total(),
                    'currentPage' => $tickets->currentPage(),
                    'lastPage' => $tickets->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * สร้าง ticket ใหม่
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createTicket(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Validate
        $validator = Validator::make($request->all(), [
            'subject' => 'required|string|max:255',
            'category' => 'nullable|in:general,account,payment,rider,technical,complaint,suggestion,other',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'message' => 'required|string|max:5000',
        ], [
            'subject.required' => 'กรุณากรอกหัวข้อ',
            'message.required' => 'กรุณากรอกข้อความ',
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

            // สร้าง ticket
            $ticket = \App\Models\SupportTicket::create([
                'user_id' => $user->id,
                'subject' => $request->subject,
                'category' => $request->category ?? 'general',
                'priority' => $request->priority ?? 'medium',
            ]);

            // เพิ่มข้อความแรก
            $ticket->addMessage($user->id, $request->message, false);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'สร้าง Ticket สำเร็จ',
                'data' => [
                    'ticketId' => $ticket->id,
                    'ticketNumber' => $ticket->ticket_number,
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถสร้าง Ticket ได้: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ดูรายละเอียด ticket
     *
     * @param int $ticketId
     * @return JsonResponse
     */
    public function getTicket(int $ticketId): JsonResponse
    {
        $user = Auth::user();

        $ticket = \App\Models\SupportTicket::where('id', $ticketId)
            ->where('user_id', $user->id)
            ->with('messages.user')
            ->first();

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบ Ticket นี้',
            ], 404);
        }

        // อ่านข้อความจากแอดมินทั้งหมด
        $ticket->messages()
            ->where('is_from_admin', true)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return response()->json([
            'success' => true,
            'data' => [
                'ticket' => [
                    'id' => $ticket->id,
                    'ticketNumber' => $ticket->ticket_number,
                    'subject' => $ticket->subject,
                    'category' => $ticket->category,
                    'categoryText' => $ticket->category_text,
                    'priority' => $ticket->priority,
                    'priorityText' => $ticket->priority_text,
                    'status' => $ticket->status,
                    'statusText' => $ticket->status_text,
                    'satisfactionRating' => $ticket->satisfaction_rating,
                    'createdAt' => $ticket->created_at->format('Y-m-d H:i:s'),
                    'resolvedAt' => $ticket->resolved_at?->format('Y-m-d H:i:s'),
                    'closedAt' => $ticket->closed_at?->format('Y-m-d H:i:s'),
                ],
                'messages' => $ticket->messages->map(function ($message) {
                    return [
                        'id' => $message->id,
                        'message' => $message->message,
                        'isFromAdmin' => $message->is_from_admin,
                        'userName' => $message->user->name ?? 'Admin',
                        'attachments' => $message->attachments,
                        'isRead' => $message->is_read,
                        'createdAt' => $message->created_at->format('Y-m-d H:i:s'),
                    ];
                }),
            ],
        ]);
    }

    /**
     * ส่งข้อความใน ticket
     *
     * @param Request $request
     * @param int $ticketId
     * @return JsonResponse
     */
    public function replyTicket(Request $request, int $ticketId): JsonResponse
    {
        $user = Auth::user();

        $ticket = \App\Models\SupportTicket::where('id', $ticketId)
            ->where('user_id', $user->id)
            ->first();

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบ Ticket นี้',
            ], 404);
        }

        if ($ticket->status === 'closed') {
            return response()->json([
                'success' => false,
                'message' => 'Ticket นี้ปิดแล้ว ไม่สามารถตอบกลับได้',
            ], 400);
        }

        // Validate
        $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        // เพิ่มข้อความ
        $message = $ticket->addMessage($user->id, $request->message, false);

        // ถ้าสถานะเป็น waiting ให้เปลี่ยนเป็น open
        if ($ticket->status === 'waiting') {
            $ticket->update(['status' => 'open']);
        }

        return response()->json([
            'success' => true,
            'message' => 'ส่งข้อความสำเร็จ',
            'data' => [
                'messageId' => $message->id,
                'createdAt' => $message->created_at->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * ให้คะแนนความพึงพอใจ
     *
     * @param Request $request
     * @param int $ticketId
     * @return JsonResponse
     */
    public function rateTicket(Request $request, int $ticketId): JsonResponse
    {
        $user = Auth::user();

        $ticket = \App\Models\SupportTicket::where('id', $ticketId)
            ->where('user_id', $user->id)
            ->whereIn('status', ['resolved', 'closed'])
            ->first();

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบ Ticket นี้หรือยังไม่ได้แก้ไข',
            ], 404);
        }

        // Validate
        $request->validate([
            'rating' => 'required|integer|between:1,5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $ticket->rate($request->rating, $request->comment);

        return response()->json([
            'success' => true,
            'message' => 'ขอบคุณสำหรับการให้คะแนน',
        ]);
    }

    // =====================================================
    // Notifications
    // =====================================================

    /**
     * ดึงรายการการแจ้งเตือน
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getNotifications(Request $request): JsonResponse
    {
        $user = Auth::user();

        $notifications = \App\Models\UserNotification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        $unreadCount = \App\Models\UserNotification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'unreadCount' => $unreadCount,
                'notifications' => $notifications->map(function ($notification) {
                    return [
                        'id' => $notification->id,
                        'title' => $notification->title,
                        'body' => $notification->body,
                        'type' => $notification->type,
                        'typeText' => $notification->type_text,
                        'icon' => $notification->icon,
                        'image' => $notification->image,
                        'actionUrl' => $notification->action_url,
                        'isRead' => $notification->is_read,
                        'data' => $notification->data,
                        'createdAt' => $notification->created_at->format('Y-m-d H:i:s'),
                        'timeAgo' => $notification->time_ago,
                    ];
                }),
                'pagination' => [
                    'total' => $notifications->total(),
                    'currentPage' => $notifications->currentPage(),
                    'lastPage' => $notifications->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * ทำเครื่องหมายว่าอ่านแล้ว
     *
     * @param Request $request
     * @param int $notificationId
     * @return JsonResponse
     */
    public function markNotificationRead(Request $request, int $notificationId): JsonResponse
    {
        $user = Auth::user();

        $notification = \App\Models\UserNotification::where('id', $notificationId)
            ->where('user_id', $user->id)
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบการแจ้งเตือนนี้',
            ], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'ทำเครื่องหมายว่าอ่านแล้ว',
        ]);
    }

    /**
     * ทำเครื่องหมายทั้งหมดว่าอ่านแล้ว
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function markAllNotificationsRead(Request $request): JsonResponse
    {
        $user = Auth::user();

        \App\Models\UserNotification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'ทำเครื่องหมายทั้งหมดว่าอ่านแล้ว',
        ]);
    }

    /**
     * ลบการแจ้งเตือน
     *
     * @param Request $request
     * @param int $notificationId
     * @return JsonResponse
     */
    public function deleteNotification(Request $request, int $notificationId): JsonResponse
    {
        $user = Auth::user();

        $notification = \App\Models\UserNotification::where('id', $notificationId)
            ->where('user_id', $user->id)
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบการแจ้งเตือนนี้',
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบการแจ้งเตือนสำเร็จ',
        ]);
    }

    // =====================================================
    // Push Notification Token
    // =====================================================

    /**
     * บันทึก Push Token
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function registerPushToken(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Validate
        $request->validate([
            'token' => 'required|string',
            'platform' => 'nullable|in:android,ios,web',
            'device_id' => 'nullable|string',
            'device_name' => 'nullable|string',
            'app_version' => 'nullable|string',
        ]);

        $token = \App\Models\UserNotificationToken::registerToken($user->id, $request->token, [
            'provider' => Str::startsWith($request->token, 'ExponentPushToken') ? 'expo' : 'fcm',
            'platform' => $request->platform ?? 'android',
            'device_id' => $request->device_id,
            'device_name' => $request->device_name,
            'app_version' => $request->app_version,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'บันทึก Push Token สำเร็จ',
            'data' => [
                'tokenId' => $token->id,
            ],
        ]);
    }

    /**
     * ลบ Push Token
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function removePushToken(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Validate
        $request->validate([
            'token' => 'required|string',
        ]);

        \App\Models\UserNotificationToken::where('user_id', $user->id)
            ->where('token', $request->token)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบ Push Token สำเร็จ',
        ]);
    }

    /**
     * จำนวนการแจ้งเตือนที่ยังไม่อ่าน
     *
     * @return JsonResponse
     */
    public function getUnreadNotificationCount(): JsonResponse
    {
        $user = Auth::user();

        $count = \App\Models\UserNotification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'unreadCount' => $count,
            ],
        ]);
    }

    // =====================================================
    // Rank System
    // =====================================================

    /**
     * ดึงรายการ Rank ทั้งหมด
     *
     * @return JsonResponse
     */
    public function getRanks(): JsonResponse
    {
        $ranks = \App\Models\Rank::where('is_active', true)
            ->orderBy('level', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'ranks' => $ranks->map(function ($rank) {
                    return [
                        'id' => $rank->id,
                        'name' => $rank->name,
                        'nameTh' => $rank->name_th ?? $rank->name,
                        'description' => $rank->description,
                        'descriptionTh' => $rank->description_th ?? $rank->description,
                        'level' => $rank->level,
                        'icon' => $rank->icon,
                        'color' => $rank->color,
                        'badgeIcon' => $rank->badge_icon,
                        'avatarFrame' => $rank->avatar_frame,
                        'commissionRate' => $rank->commission_rate,
                        'bonusMultiplier' => $rank->bonus_multiplier,
                        'promotionBonus' => $rank->promotion_bonus,
                        'minPoints' => $rank->min_points,
                        'minReferrals' => $rank->min_referrals,
                        'minSales' => $rank->min_sales,
                        'privileges' => $rank->privileges ?? [],
                        'isDefault' => $rank->is_default,
                        'isTopTier' => $rank->is_top_tier,
                    ];
                }),
            ],
        ]);
    }

    /**
     * ดึงรายละเอียด Rank
     *
     * @param int $rankId
     * @return JsonResponse
     */
    public function getRankDetail(int $rankId): JsonResponse
    {
        $rank = \App\Models\Rank::with(['requirements', 'bonuses'])
            ->find($rankId);

        if (!$rank) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูล Rank',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'rank' => [
                    'id' => $rank->id,
                    'name' => $rank->name,
                    'nameTh' => $rank->name_th ?? $rank->name,
                    'description' => $rank->description,
                    'descriptionTh' => $rank->description_th ?? $rank->description,
                    'level' => $rank->level,
                    'icon' => $rank->icon,
                    'color' => $rank->color,
                    'badgeIcon' => $rank->badge_icon,
                    'avatarFrame' => $rank->avatar_frame,
                    'frameAnimation' => $rank->frame_animation,
                    'commissionRate' => $rank->commission_rate,
                    'bonusMultiplier' => $rank->bonus_multiplier,
                    'promotionBonus' => $rank->promotion_bonus,
                    'maxDownlineLevelBonus' => $rank->max_downline_level_bonus,
                    'unilevelCommissionLevels' => $rank->unilevel_commission_levels,
                    'privileges' => $rank->privileges ?? [],
                    'minPoints' => $rank->min_points,
                    'minReferrals' => $rank->min_referrals,
                    'minSales' => $rank->min_sales,
                    'monthlyMaintenancePv' => $rank->monthly_maintenance_pv,
                    'withdrawalFeeDiscount' => $rank->withdrawal_fee_discount,
                    'maxWithdrawalsPerMonth' => $rank->max_withdrawals_per_month,
                ],
                'requirements' => $rank->requirements->map(function ($req) {
                    return [
                        'id' => $req->id,
                        'type' => $req->type,
                        'typeText' => $this->getRequirementTypeText($req->type),
                        'value' => $req->value,
                        'operator' => $req->operator,
                        'description' => $req->description,
                    ];
                }),
                'bonuses' => $rank->bonuses->map(function ($bonus) {
                    return [
                        'id' => $bonus->id,
                        'type' => $bonus->type,
                        'rewardType' => $bonus->reward_type,
                        'amount' => $bonus->amount,
                        'percentage' => $bonus->percentage,
                        'description' => $bonus->description,
                    ];
                }),
            ],
        ]);
    }

    /**
     * ดึงความคืบหน้า Rank ของผู้ใช้
     *
     * @return JsonResponse
     */
    public function getUserRankProgress(): JsonResponse
    {
        $user = Auth::user();

        // ดึง current rank
        $currentRank = $user->currentRank;
        if (!$currentRank) {
            $currentRank = \App\Models\Rank::where('is_default', true)->first()
                ?? \App\Models\Rank::orderBy('level', 'asc')->first();
        }

        // ดึง next rank
        $nextRank = \App\Models\Rank::where('level', '>', $currentRank->level ?? 0)
            ->where('is_active', true)
            ->orderBy('level', 'asc')
            ->first();

        // คำนวณสถิติของผู้ใช้
        $totalReferrals = \App\Models\User::where('sponsor_id', $user->id)->count();
        $activeReferrals = \App\Models\User::where('sponsor_id', $user->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        // ดึง rank points (ถ้ามี)
        $rankPoints = $user->rank_points ?? 0;
        $totalSales = $user->total_sales ?? 0;
        $teamSales = $user->team_sales ?? 0;

        // คำนวณ progress ไปยัง rank ถัดไป
        $progressData = [];
        if ($nextRank) {
            $requirements = $nextRank->requirements ?? collect();
            foreach ($requirements as $req) {
                $currentValue = $this->getRequirementValue($user, $req->type, $totalReferrals, $rankPoints, $totalSales, $teamSales);
                $progressData[] = [
                    'type' => $req->type,
                    'typeText' => $this->getRequirementTypeText($req->type),
                    'currentValue' => $currentValue,
                    'requiredValue' => $req->value,
                    'progress' => $req->value > 0 ? min(100, round(($currentValue / $req->value) * 100)) : 100,
                    'completed' => $this->checkRequirementMet($currentValue, $req->value, $req->operator),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'currentRank' => $currentRank ? [
                    'id' => $currentRank->id,
                    'name' => $currentRank->name,
                    'nameTh' => $currentRank->name_th ?? $currentRank->name,
                    'level' => $currentRank->level,
                    'icon' => $currentRank->icon,
                    'color' => $currentRank->color,
                    'badgeIcon' => $currentRank->badge_icon,
                    'avatarFrame' => $currentRank->avatar_frame,
                    'commissionRate' => $currentRank->commission_rate,
                    'privileges' => $currentRank->privileges ?? [],
                ] : null,
                'nextRank' => $nextRank ? [
                    'id' => $nextRank->id,
                    'name' => $nextRank->name,
                    'nameTh' => $nextRank->name_th ?? $nextRank->name,
                    'level' => $nextRank->level,
                    'icon' => $nextRank->icon,
                    'color' => $nextRank->color,
                    'promotionBonus' => $nextRank->promotion_bonus,
                ] : null,
                'statistics' => [
                    'rankPoints' => $rankPoints,
                    'totalReferrals' => $totalReferrals,
                    'activeReferrals' => $activeReferrals,
                    'totalSales' => $totalSales,
                    'teamSales' => $teamSales,
                ],
                'progress' => $progressData,
                'overallProgress' => count($progressData) > 0
                    ? round(collect($progressData)->avg('progress'))
                    : 100,
                'isMaxRank' => is_null($nextRank),
            ],
        ]);
    }

    /**
     * ดึง Leaderboard
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getLeaderboard(Request $request): JsonResponse
    {
        $type = $request->get('type', 'referrals'); // referrals, sales, earnings
        $period = $request->get('period', 'all'); // all, month, week
        $limit = min($request->get('limit', 20), 100);

        $query = \App\Models\User::query()
            ->select('users.id', 'users.name', 'users.avatar', 'users.rank_id')
            ->with('currentRank:id,name,name_th,icon,color');

        // กรองตามช่วงเวลา
        if ($period === 'month') {
            $startDate = now()->startOfMonth();
        } elseif ($period === 'week') {
            $startDate = now()->startOfWeek();
        } else {
            $startDate = null;
        }

        // เรียงลำดับตามประเภท
        switch ($type) {
            case 'sales':
                $query->orderBy('total_sales', 'desc');
                break;
            case 'earnings':
                $query->orderBy('total_earnings', 'desc');
                break;
            case 'referrals':
            default:
                $query->withCount(['referrals as referral_count' => function ($q) use ($startDate) {
                    if ($startDate) {
                        $q->where('created_at', '>=', $startDate);
                    }
                }])->orderBy('referral_count', 'desc');
                break;
        }

        $leaders = $query->limit($limit)->get();

        // หาตำแหน่งของผู้ใช้ปัจจุบัน
        $user = Auth::user();
        $userRank = null;
        foreach ($leaders as $index => $leader) {
            if ($leader->id === $user->id) {
                $userRank = $index + 1;
                break;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'type' => $type,
                'period' => $period,
                'leaders' => $leaders->map(function ($leader, $index) {
                    return [
                        'position' => $index + 1,
                        'userId' => $leader->id,
                        'name' => $leader->name,
                        'avatar' => $leader->avatar,
                        'rank' => $leader->currentRank ? [
                            'name' => $leader->currentRank->name,
                            'nameTh' => $leader->currentRank->name_th ?? $leader->currentRank->name,
                            'icon' => $leader->currentRank->icon,
                            'color' => $leader->currentRank->color,
                        ] : null,
                        'value' => $leader->referral_count ?? $leader->total_sales ?? $leader->total_earnings ?? 0,
                    ];
                }),
                'currentUserPosition' => $userRank,
            ],
        ]);
    }

    /**
     * แปลงประเภท requirement เป็นภาษาไทย
     */
    private function getRequirementTypeText(string $type): string
    {
        $types = [
            'points' => 'คะแนนสะสม',
            'referrals' => 'จำนวนคนแนะนำ',
            'sales' => 'ยอดขายส่วนตัว',
            'active_referrals' => 'คนแนะนำที่ active',
            'team_sales' => 'ยอดขายทั้งทีม',
            'consecutive_months' => 'เดือนต่อเนื่อง',
            'diamond_legs' => 'ลูกทีมระดับ Diamond',
            'crown_legs' => 'ลูกทีมระดับ Crown',
            'royal_legs' => 'ลูกทีมระดับ Royal',
        ];

        return $types[$type] ?? $type;
    }

    /**
     * ดึงค่าปัจจุบันของ requirement
     */
    private function getRequirementValue($user, string $type, int $totalReferrals, float $rankPoints, float $totalSales, float $teamSales)
    {
        switch ($type) {
            case 'points':
                return $rankPoints;
            case 'referrals':
                return $totalReferrals;
            case 'sales':
                return $totalSales;
            case 'team_sales':
                return $teamSales;
            case 'active_referrals':
                return \App\Models\User::where('sponsor_id', $user->id)
                    ->where('created_at', '>=', now()->subDays(30))
                    ->count();
            default:
                return 0;
        }
    }

    /**
     * ตรวจสอบว่าผ่านเงื่อนไขหรือไม่
     */
    private function checkRequirementMet($currentValue, $requiredValue, $operator = '>='): bool
    {
        switch ($operator) {
            case '>=':
                return $currentValue >= $requiredValue;
            case '>':
                return $currentValue > $requiredValue;
            case '=':
                return $currentValue == $requiredValue;
            case '<=':
                return $currentValue <= $requiredValue;
            case '<':
                return $currentValue < $requiredValue;
            default:
                return $currentValue >= $requiredValue;
        }
    }

    // =====================================================
    // MLM / Affiliate Network
    // =====================================================

    /**
     * ดึงข้อมูล Affiliate ของผู้ใช้
     *
     * @return JsonResponse
     */
    public function getMyAffiliate(): JsonResponse
    {
        $user = Auth::user();

        // ดึงข้อมูล affiliate
        $affiliate = \App\Models\Affiliate::where('user_id', $user->id)->first();

        // ดึงสถิติ
        $directReferrals = \App\Models\User::where('sponsor_id', $user->id)->count();
        $totalTeamMembers = $this->countTotalTeam($user->id);

        // ดึงรายได้
        $totalEarnings = \App\Models\MlmCommission::where('user_id', $user->id)
            ->where('status', 'paid')
            ->sum('commission_amount') ?? 0;

        $thisMonthEarnings = \App\Models\MlmCommission::where('user_id', $user->id)
            ->where('status', 'paid')
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('commission_amount') ?? 0;

        $pendingEarnings = \App\Models\MlmCommission::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'approved'])
            ->sum('commission_amount') ?? 0;

        return response()->json([
            'success' => true,
            'data' => [
                'referralCode' => $user->referral_code,
                'referralLink' => url('/register?ref=' . $user->referral_code),
                'statistics' => [
                    'directReferrals' => $directReferrals,
                    'totalTeamMembers' => $totalTeamMembers,
                    'activeMembers' => $this->countActiveMembers($user->id),
                ],
                'earnings' => [
                    'total' => $totalEarnings,
                    'thisMonth' => $thisMonthEarnings,
                    'pending' => $pendingEarnings,
                ],
                'rank' => $user->currentRank ? [
                    'id' => $user->currentRank->id,
                    'name' => $user->currentRank->name,
                    'nameTh' => $user->currentRank->name_th ?? $user->currentRank->name,
                    'icon' => $user->currentRank->icon,
                    'color' => $user->currentRank->color,
                    'commissionRate' => $user->currentRank->commission_rate,
                ] : null,
            ],
        ]);
    }

    /**
     * ดึงรายชื่อลูกทีมโดยตรง
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getDirectReferrals(Request $request): JsonResponse
    {
        $user = Auth::user();
        $page = $request->get('page', 1);
        $perPage = min($request->get('per_page', 20), 50);

        $referrals = \App\Models\User::where('sponsor_id', $user->id)
            ->with('currentRank:id,name,name_th,icon,color')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'referrals' => $referrals->map(function ($referral) {
                    return [
                        'id' => $referral->id,
                        'name' => $referral->name,
                        'email' => $referral->email,
                        'avatar' => $referral->avatar,
                        'rank' => $referral->currentRank ? [
                            'name' => $referral->currentRank->name,
                            'nameTh' => $referral->currentRank->name_th ?? $referral->currentRank->name,
                            'icon' => $referral->currentRank->icon,
                            'color' => $referral->currentRank->color,
                        ] : null,
                        'totalReferrals' => \App\Models\User::where('sponsor_id', $referral->id)->count(),
                        'isActive' => $referral->created_at >= now()->subDays(30),
                        'joinedAt' => $referral->created_at->format('Y-m-d'),
                        'daysAgo' => $referral->created_at->diffInDays(now()),
                    ];
                }),
                'pagination' => [
                    'total' => $referrals->total(),
                    'currentPage' => $referrals->currentPage(),
                    'lastPage' => $referrals->lastPage(),
                    'perPage' => $referrals->perPage(),
                ],
            ],
        ]);
    }

    /**
     * ดึงผังทีม (Unilevel Tree)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTeamTree(Request $request): JsonResponse
    {
        $user = Auth::user();
        $depth = min($request->get('depth', 3), 5);

        $tree = $this->buildTeamTree($user->id, $depth);

        return response()->json([
            'success' => true,
            'data' => [
                'root' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'avatar' => $user->avatar,
                    'rank' => $user->currentRank ? [
                        'name' => $user->currentRank->name,
                        'nameTh' => $user->currentRank->name_th ?? $user->currentRank->name,
                        'icon' => $user->currentRank->icon,
                        'color' => $user->currentRank->color,
                    ] : null,
                ],
                'children' => $tree,
                'totalMembers' => $this->countTotalTeam($user->id),
                'depth' => $depth,
            ],
        ]);
    }

    /**
     * สร้างผังทีมแบบ recursive
     */
    private function buildTeamTree(int $userId, int $depth, int $currentDepth = 0): array
    {
        if ($currentDepth >= $depth) {
            return [];
        }

        $children = \App\Models\User::where('sponsor_id', $userId)
            ->with('currentRank:id,name,name_th,icon,color')
            ->get();

        return $children->map(function ($child) use ($depth, $currentDepth) {
            $childrenCount = \App\Models\User::where('sponsor_id', $child->id)->count();
            return [
                'id' => $child->id,
                'name' => $child->name,
                'avatar' => $child->avatar,
                'level' => $currentDepth + 1,
                'rank' => $child->currentRank ? [
                    'name' => $child->currentRank->name,
                    'nameTh' => $child->currentRank->name_th ?? $child->currentRank->name,
                    'icon' => $child->currentRank->icon,
                    'color' => $child->currentRank->color,
                ] : null,
                'childrenCount' => $childrenCount,
                'isActive' => $child->created_at >= now()->subDays(30),
                'joinedAt' => $child->created_at->format('Y-m-d'),
                'children' => $this->buildTeamTree($child->id, $depth, $currentDepth + 1),
            ];
        })->toArray();
    }

    /**
     * นับสมาชิกทั้งหมดในทีม
     */
    private function countTotalTeam(int $userId, int $maxDepth = 10): int
    {
        $total = 0;
        $currentLevel = [$userId];

        for ($i = 0; $i < $maxDepth && !empty($currentLevel); $i++) {
            $children = \App\Models\User::whereIn('sponsor_id', $currentLevel)->pluck('id')->toArray();
            $total += count($children);
            $currentLevel = $children;
        }

        return $total;
    }

    /**
     * นับสมาชิกที่ active
     */
    private function countActiveMembers(int $userId): int
    {
        return \App\Models\User::where('sponsor_id', $userId)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();
    }

    // =====================================================
    // Commission System
    // =====================================================

    /**
     * ดึงรายการคอมมิชชัน
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getCommissions(Request $request): JsonResponse
    {
        $user = Auth::user();
        $status = $request->get('status'); // pending, approved, paid, rejected
        $type = $request->get('type'); // unilevel_direct, unilevel_indirect, binary_pair, etc.
        $perPage = min($request->get('per_page', 20), 50);

        $query = \App\Models\MlmCommission::where('user_id', $user->id);

        if ($status) {
            $query->where('status', $status);
        }

        if ($type) {
            $query->where('type', $type);
        }

        $commissions = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // สรุปตามสถานะ
        $summary = [
            'pending' => \App\Models\MlmCommission::where('user_id', $user->id)->where('status', 'pending')->sum('commission_amount'),
            'approved' => \App\Models\MlmCommission::where('user_id', $user->id)->where('status', 'approved')->sum('commission_amount'),
            'paid' => \App\Models\MlmCommission::where('user_id', $user->id)->where('status', 'paid')->sum('commission_amount'),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $summary,
                'commissions' => $commissions->map(function ($commission) {
                    return [
                        'id' => $commission->id,
                        'type' => $commission->type,
                        'typeText' => $this->getCommissionTypeText($commission->type),
                        'level' => $commission->level,
                        'amount' => $commission->commission_amount,
                        'pvAmount' => $commission->pv_amount,
                        'salesAmount' => $commission->sales_amount,
                        'percentage' => $commission->percentage,
                        'status' => $commission->status,
                        'statusText' => $this->getCommissionStatusText($commission->status),
                        'fromMember' => $commission->fromMember ? [
                            'id' => $commission->fromMember->id,
                            'name' => $commission->fromMember->user->name ?? 'N/A',
                        ] : null,
                        'approvedAt' => $commission->approved_at?->format('Y-m-d H:i:s'),
                        'paidAt' => $commission->paid_at?->format('Y-m-d H:i:s'),
                        'createdAt' => $commission->created_at->format('Y-m-d H:i:s'),
                    ];
                }),
                'pagination' => [
                    'total' => $commissions->total(),
                    'currentPage' => $commissions->currentPage(),
                    'lastPage' => $commissions->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * ดึงสรุปรายได้
     *
     * @return JsonResponse
     */
    public function getEarningsSummary(): JsonResponse
    {
        $user = Auth::user();

        // รายได้รวมทั้งหมด (จ่ายแล้ว)
        $totalEarnings = \App\Models\MlmCommission::where('user_id', $user->id)
            ->where('status', 'paid')
            ->sum('commission_amount');

        // รายได้เดือนนี้
        $thisMonthEarnings = \App\Models\MlmCommission::where('user_id', $user->id)
            ->where('status', 'paid')
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('commission_amount');

        // รายได้เดือนที่แล้ว
        $lastMonthEarnings = \App\Models\MlmCommission::where('user_id', $user->id)
            ->where('status', 'paid')
            ->whereMonth('paid_at', now()->subMonth()->month)
            ->whereYear('paid_at', now()->subMonth()->year)
            ->sum('commission_amount');

        // รายได้รอดำเนินการ
        $pendingEarnings = \App\Models\MlmCommission::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'approved'])
            ->sum('commission_amount');

        // รายได้ตามประเภท
        $earningsByType = \App\Models\MlmCommission::where('user_id', $user->id)
            ->where('status', 'paid')
            ->selectRaw('type, SUM(commission_amount) as total')
            ->groupBy('type')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->type => $item->total];
            });

        // รายได้ 7 วันล่าสุด
        $last7Days = collect(range(6, 0))->map(function ($daysAgo) use ($user) {
            $date = now()->subDays($daysAgo);
            $amount = \App\Models\MlmCommission::where('user_id', $user->id)
                ->where('status', 'paid')
                ->whereDate('paid_at', $date->format('Y-m-d'))
                ->sum('commission_amount');

            return [
                'date' => $date->format('Y-m-d'),
                'day' => $date->locale('th')->dayName,
                'amount' => $amount ?? 0,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'totalEarnings' => $totalEarnings,
                'thisMonthEarnings' => $thisMonthEarnings,
                'lastMonthEarnings' => $lastMonthEarnings,
                'pendingEarnings' => $pendingEarnings,
                'growthPercent' => $lastMonthEarnings > 0
                    ? round((($thisMonthEarnings - $lastMonthEarnings) / $lastMonthEarnings) * 100, 2)
                    : 0,
                'earningsByType' => [
                    'unilevelDirect' => $earningsByType['unilevel_direct'] ?? 0,
                    'unilevelIndirect' => $earningsByType['unilevel_indirect'] ?? 0,
                    'binaryPair' => $earningsByType['binary_pair'] ?? 0,
                    'sponsorBonus' => $earningsByType['sponsor_bonus'] ?? 0,
                    'rankBonus' => $earningsByType['rank_bonus'] ?? 0,
                    'leadershipBonus' => $earningsByType['leadership_bonus'] ?? 0,
                ],
                'chart' => $last7Days,
            ],
        ]);
    }

    /**
     * แปลงประเภทคอมมิชชันเป็นภาษาไทย
     */
    private function getCommissionTypeText(string $type): string
    {
        $types = [
            'unilevel_direct' => 'คอมมิชชันลูกตรง',
            'unilevel_indirect' => 'คอมมิชชันลูกของลูก',
            'binary_pair' => 'คอมมิชชัน Binary',
            'sponsor_bonus' => 'โบนัสผู้แนะนำ',
            'rank_bonus' => 'โบนัสระดับ',
            'leadership_bonus' => 'โบนัสผู้นำ',
            'pool_bonus' => 'โบนัส Pool',
        ];

        return $types[$type] ?? $type;
    }

    /**
     * แปลงสถานะคอมมิชชันเป็นภาษาไทย
     */
    private function getCommissionStatusText(string $status): string
    {
        $statuses = [
            'pending' => 'รอดำเนินการ',
            'approved' => 'อนุมัติแล้ว',
            'paid' => 'จ่ายแล้ว',
            'rejected' => 'ปฏิเสธ',
        ];

        return $statuses[$status] ?? $status;
    }

    // =====================================================
    // Cart API - ระบบตะกร้าสินค้า
    // =====================================================

    /**
     * ดึงข้อมูลตะกร้าสินค้า (พร้อมคำนวณทุกอย่างจาก server)
     *
     * @return JsonResponse
     */
    public function getCart(): JsonResponse
    {
        $user = Auth::user();

        try {
            // หาหรือสร้างตะกร้าของ user
            $cart = \App\Models\Cart::firstOrCreate(
                ['user_id' => $user->id],
                ['session_id' => null]
            );

            // โหลด items พร้อม product
            $cart->load(['items.product']);

            // คำนวณยอดรวมทั้งหมดจาก server
            $items = $cart->items->map(function ($item) {
                $product = $item->product;
                $price = $product->discount_price ?? $product->price;
                $pvValue = $product->pv_value ?? round($product->price * 0.1, 2);
                $commissionRate = $product->commission_rate ?? 0;

                return [
                    'id' => $item->id,
                    'productId' => $product->id,
                    'productName' => $product->name,
                    'productImage' => $product->image ?? $product->getFirstImageUrl(),
                    'price' => (float) $price,
                    'originalPrice' => (float) $product->price,
                    'quantity' => (int) $item->quantity,
                    'subtotal' => (float) ($price * $item->quantity),
                    'pvValue' => (float) $pvValue,
                    'pvSubtotal' => (float) ($pvValue * $item->quantity),
                    'commissionRate' => (float) $commissionRate,
                    'attributes' => $item->attributes,
                    'isAvailable' => $product->is_active && $product->isInStock(),
                    'stock' => $product->stock_quantity ?? 999,
                ];
            });

            // คำนวณยอดรวมทั้งหมด
            $totalItems = $items->sum('quantity');
            $totalPrice = $items->sum('subtotal');
            $totalPV = $items->sum('pvSubtotal');

            // คำนวณค่าส่ง (ฟรีเมื่อซื้อ ≥ 500)
            $shippingFee = $totalPrice >= 500 ? 0 : 50;

            // คำนวณคอมมิชชัน preview
            $totalCommission = $items->sum(function ($item) {
                return $item['subtotal'] * ($item['commissionRate'] / 100);
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'cartId' => $cart->id,
                    'items' => $items->values(),
                    'summary' => [
                        'totalItems' => $totalItems,
                        'totalPrice' => round($totalPrice, 2),
                        'totalPV' => round($totalPV, 2),
                        'shippingFee' => round($shippingFee, 2),
                        'grandTotal' => round($totalPrice + $shippingFee, 2),
                        'estimatedCommission' => round($totalCommission, 2),
                    ],
                    'freeShippingThreshold' => 500,
                    'amountToFreeShipping' => max(0, 500 - $totalPrice),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Get Cart Error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถดึงข้อมูลตะกร้าได้',
            ], 500);
        }
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
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'integer|min:1|max:99',
            'attributes' => 'nullable|array',
        ], [
            'product_id.required' => 'กรุณาระบุสินค้า',
            'product_id.exists' => 'ไม่พบสินค้านี้',
            'quantity.min' => 'จำนวนต้องมากกว่า 0',
            'quantity.max' => 'จำนวนต้องไม่เกิน 99',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();

        try {
            DB::beginTransaction();

            // ตรวจสอบสินค้า
            $product = Product::findOrFail($request->product_id);

            if (!$product->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'สินค้านี้ไม่พร้อมจำหน่าย',
                ], 400);
            }

            // หาหรือสร้างตะกร้า
            $cart = \App\Models\Cart::firstOrCreate(
                ['user_id' => $user->id],
                ['session_id' => null]
            );

            $quantity = $request->quantity ?? 1;
            $attributes = $request->attributes;

            // ตรวจสอบว่ามีสินค้านี้อยู่ในตะกร้าแล้วหรือไม่
            $existingItem = $cart->items()
                ->where('product_id', $product->id)
                ->when($attributes, function ($q) use ($attributes) {
                    $q->where('attributes', json_encode($attributes));
                })
                ->first();

            if ($existingItem) {
                // อัพเดทจำนวน
                $newQuantity = $existingItem->quantity + $quantity;

                // ตรวจสอบสต็อก
                if ($product->track_inventory && $newQuantity > $product->stock_quantity) {
                    return response()->json([
                        'success' => false,
                        'message' => "สินค้าคงเหลือ {$product->stock_quantity} ชิ้น",
                    ], 400);
                }

                $existingItem->update(['quantity' => $newQuantity]);
            } else {
                // ตรวจสอบสต็อก
                if ($product->track_inventory && $quantity > $product->stock_quantity) {
                    return response()->json([
                        'success' => false,
                        'message' => "สินค้าคงเหลือ {$product->stock_quantity} ชิ้น",
                    ], 400);
                }

                // สร้าง item ใหม่
                $cart->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'price' => $product->discount_price ?? $product->price,
                    'attributes' => $attributes,
                ]);
            }

            DB::commit();

            // Return updated cart
            return $this->getCart();
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Add to Cart Error', [
                'user_id' => $user->id,
                'product_id' => $request->product_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถเพิ่มสินค้าลงตะกร้าได้',
            ], 500);
        }
    }

    /**
     * อัพเดทจำนวนสินค้าในตะกร้า
     *
     * @param Request $request
     * @param int $itemId
     * @return JsonResponse
     */
    public function updateCartItem(Request $request, int $itemId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:0|max:99',
        ], [
            'quantity.required' => 'กรุณาระบุจำนวน',
            'quantity.min' => 'จำนวนต้องไม่ต่ำกว่า 0',
            'quantity.max' => 'จำนวนต้องไม่เกิน 99',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();

        try {
            // หา cart item
            $cartItem = \App\Models\CartItem::whereHas('cart', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })->findOrFail($itemId);

            $quantity = (int) $request->quantity;

            if ($quantity === 0) {
                // ลบ item
                $cartItem->delete();
            } else {
                // ตรวจสอบสต็อก
                $product = $cartItem->product;
                if ($product->track_inventory && $quantity > $product->stock_quantity) {
                    return response()->json([
                        'success' => false,
                        'message' => "สินค้าคงเหลือ {$product->stock_quantity} ชิ้น",
                    ], 400);
                }

                $cartItem->update(['quantity' => $quantity]);
            }

            // Return updated cart
            return $this->getCart();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบรายการสินค้านี้ในตะกร้า',
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Update Cart Item Error', [
                'user_id' => $user->id,
                'item_id' => $itemId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถอัพเดทตะกร้าได้',
            ], 500);
        }
    }

    /**
     * ลบสินค้าออกจากตะกร้า
     *
     * @param int $itemId
     * @return JsonResponse
     */
    public function removeFromCart(int $itemId): JsonResponse
    {
        $user = Auth::user();

        try {
            // หา cart item
            $cartItem = \App\Models\CartItem::whereHas('cart', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })->findOrFail($itemId);

            $cartItem->delete();

            // Return updated cart
            return $this->getCart();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบรายการสินค้านี้ในตะกร้า',
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Remove from Cart Error', [
                'user_id' => $user->id,
                'item_id' => $itemId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถลบสินค้าจากตะกร้าได้',
            ], 500);
        }
    }

    /**
     * ล้างตะกร้าทั้งหมด
     *
     * @return JsonResponse
     */
    public function clearCart(): JsonResponse
    {
        $user = Auth::user();

        try {
            $cart = \App\Models\Cart::where('user_id', $user->id)->first();

            if ($cart) {
                $cart->clear();
            }

            return response()->json([
                'success' => true,
                'message' => 'ล้างตะกร้าเรียบร้อย',
                'data' => [
                    'items' => [],
                    'summary' => [
                        'totalItems' => 0,
                        'totalPrice' => 0,
                        'totalPV' => 0,
                        'shippingFee' => 50,
                        'grandTotal' => 0,
                        'estimatedCommission' => 0,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Clear Cart Error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถล้างตะกร้าได้',
            ], 500);
        }
    }

    /**
     * ใช้โค้ดส่วนลด
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function applyPromoCode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:50',
        ], [
            'code.required' => 'กรุณาใส่โค้ดส่วนลด',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();
        $code = strtoupper($request->code);

        try {
            // หาตะกร้า
            $cart = \App\Models\Cart::where('user_id', $user->id)
                ->with(['items.product'])
                ->first();

            if (!$cart || $cart->items->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่มีสินค้าในตะกร้า',
                ], 400);
            }

            // คำนวณยอดรวม
            $totalPrice = $cart->items->sum(function ($item) {
                $price = $item->product->discount_price ?? $item->product->price;
                return $price * $item->quantity;
            });

            // ตรวจสอบโค้ด (Mock - ควรมี PromoCode Model จริง)
            $discount = 0;
            $discountType = 'fixed';
            $discountMessage = '';

            if ($code === 'FIRST10') {
                $discount = $totalPrice * 0.10;
                $discountType = 'percent';
                $discountMessage = 'ส่วนลด 10% สำหรับออเดอร์แรก';
            } elseif ($code === 'FREE50') {
                $discount = 50;
                $discountType = 'fixed';
                $discountMessage = 'ส่วนลด ฿50';
            } elseif ($code === 'FREESHIP') {
                $discount = 50; // คืนค่าส่ง
                $discountType = 'shipping';
                $discountMessage = 'ฟรีค่าจัดส่ง';
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'โค้ดส่วนลดไม่ถูกต้องหรือหมดอายุ',
                ], 400);
            }

            // คำนวณค่าส่ง
            $shippingFee = $totalPrice >= 500 ? 0 : 50;
            if ($discountType === 'shipping') {
                $shippingFee = 0;
            }

            $grandTotal = $totalPrice + $shippingFee - ($discountType !== 'shipping' ? $discount : 0);

            return response()->json([
                'success' => true,
                'message' => $discountMessage,
                'data' => [
                    'code' => $code,
                    'discountType' => $discountType,
                    'discountAmount' => round($discount, 2),
                    'totalPrice' => round($totalPrice, 2),
                    'shippingFee' => round($shippingFee, 2),
                    'grandTotal' => round(max(0, $grandTotal), 2),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Apply Promo Code Error', [
                'user_id' => $user->id,
                'code' => $code,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถใช้โค้ดส่วนลดได้',
            ], 500);
        }
    }

    /**
     * สร้างคำสั่งซื้อ (Checkout)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkout(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'payment_method' => 'required|string|in:wallet,bank,card,cod',
            'shipping_address_id' => 'nullable|integer',
            'promo_code' => 'nullable|string|max:50',
            'note' => 'nullable|string|max:500',
        ], [
            'payment_method.required' => 'กรุณาเลือกวิธีชำระเงิน',
            'payment_method.in' => 'วิธีชำระเงินไม่ถูกต้อง',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();

        try {
            DB::beginTransaction();

            // หาตะกร้า
            $cart = \App\Models\Cart::where('user_id', $user->id)
                ->with(['items.product'])
                ->first();

            if (!$cart || $cart->items->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่มีสินค้าในตะกร้า',
                ], 400);
            }

            // ตรวจสอบสต็อกทุกรายการ
            foreach ($cart->items as $item) {
                if (!$item->isAvailable()) {
                    return response()->json([
                        'success' => false,
                        'message' => "สินค้า '{$item->product->name}' หมดสต็อกหรือไม่พร้อมจำหน่าย",
                    ], 400);
                }
            }

            // คำนวณยอดรวม
            $totalPrice = 0;
            $totalPV = 0;
            foreach ($cart->items as $item) {
                $price = $item->product->discount_price ?? $item->product->price;
                $pv = $item->product->pv_value ?? round($item->product->price * 0.1, 2);
                $totalPrice += $price * $item->quantity;
                $totalPV += $pv * $item->quantity;
            }

            // คำนวณค่าส่งและส่วนลด
            $shippingFee = $totalPrice >= 500 ? 0 : 50;
            $discount = 0;

            // ตรวจสอบโค้ดส่วนลด (simplified)
            if ($request->promo_code) {
                $code = strtoupper($request->promo_code);
                if ($code === 'FIRST10') {
                    $discount = $totalPrice * 0.10;
                } elseif ($code === 'FREE50') {
                    $discount = 50;
                } elseif ($code === 'FREESHIP') {
                    $shippingFee = 0;
                }
            }

            $grandTotal = $totalPrice + $shippingFee - $discount;

            // ชำระเงินด้วย wallet
            if ($request->payment_method === 'wallet') {
                $walletService = app(\App\Services\WalletService::class);
                $wallet = $walletService->getOrCreateWallet($user);

                if ($wallet->balance < $grandTotal) {
                    return response()->json([
                        'success' => false,
                        'message' => 'ยอดเงินในกระเป๋าไม่เพียงพอ',
                        'data' => [
                            'required' => $grandTotal,
                            'available' => $wallet->balance,
                            'shortfall' => $grandTotal - $wallet->balance,
                        ],
                    ], 400);
                }
            }

            // สร้าง Order
            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => 'ORD-' . strtoupper(Str::random(8)),
                'status' => $request->payment_method === 'cod' ? 'pending' : 'processing',
                'payment_method' => $request->payment_method,
                'payment_status' => $request->payment_method === 'wallet' ? 'paid' : 'pending',
                'subtotal' => $totalPrice,
                'shipping_fee' => $shippingFee,
                'discount' => $discount,
                'total' => $grandTotal,
                'pv_total' => $totalPV,
                'promo_code' => $request->promo_code,
                'note' => $request->note,
            ]);

            // สร้าง Order Items
            foreach ($cart->items as $item) {
                $price = $item->product->discount_price ?? $item->product->price;
                $pv = $item->product->pv_value ?? round($item->product->price * 0.1, 2);

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $price,
                    'subtotal' => $price * $item->quantity,
                    'pv_value' => $pv,
                    'pv_subtotal' => $pv * $item->quantity,
                    'attributes' => $item->attributes,
                ]);

                // ลดสต็อก
                if ($item->product->track_inventory) {
                    $item->product->decrement('stock_quantity', $item->quantity);
                }
            }

            // หักเงินจาก wallet
            if ($request->payment_method === 'wallet') {
                $walletService->withdraw(
                    $wallet,
                    $grandTotal,
                    "ชำระคำสั่งซื้อ #{$order->order_number}",
                    ['order_id' => $order->id]
                );
            }

            // ล้างตะกร้า
            $cart->clear();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'สร้างคำสั่งซื้อสำเร็จ',
                'data' => [
                    'orderId' => $order->id,
                    'orderNumber' => $order->order_number,
                    'status' => $order->status,
                    'paymentStatus' => $order->payment_status,
                    'total' => $order->total,
                    'pvEarned' => $totalPV,
                    'paymentMethod' => $request->payment_method,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Checkout Error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถสร้างคำสั่งซื้อได้ กรุณาลองใหม่',
            ], 500);
        }
    }

    // =====================================================
    // Wallet Lookup & Transfer API (Mobile)
    // =====================================================

    /**
     * ค้นหา Wallet Address
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function lookupWalletAddress(Request $request): JsonResponse
    {
        $user = Auth::user();
        $address = $request->query('address');

        if (empty($address) || strlen($address) < 10) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาระบุ Wallet Address ที่ถูกต้อง',
            ], 422);
        }

        try {
            // ค้นหา Wallet จาก address
            $wallet = \App\Models\Wallet::where('wallet_address', $address)
                ->where('status', 'active')
                ->first();

            if (!$wallet) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบ Wallet นี้ในระบบ',
                ], 404);
            }

            // ตรวจสอบว่าไม่ใช่ตัวเอง
            if ($wallet->user_id === $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่สามารถโอนเงินให้ตัวเองได้',
                ], 400);
            }

            // ดึงข้อมูล User เจ้าของ Wallet
            $recipient = $wallet->user;

            if (!$recipient) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบผู้ใช้เจ้าของ Wallet นี้',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'user_id' => $recipient->id,
                    'name' => $recipient->name,
                    'avatar' => $recipient->avatar_url ?? null,
                    'wallet_address' => $wallet->wallet_address,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Wallet Lookup Error', [
                'user_id' => $user->id,
                'address' => $address,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการค้นหา Wallet',
            ], 500);
        }
    }

    /**
     * โอนเงินไปยัง Wallet อื่น
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function transferMoney(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'wallet_address' => 'required|string|min:10',
            'amount' => 'required|numeric|min:10',
            'pin' => 'required|string|size:6',
            'note' => 'nullable|string|max:200',
        ], [
            'wallet_address.required' => 'กรุณาระบุ Wallet Address ผู้รับ',
            'amount.required' => 'กรุณาระบุจำนวนเงิน',
            'amount.min' => 'โอนขั้นต่ำ 10 บาท',
            'pin.required' => 'กรุณาระบุ PIN',
            'pin.size' => 'PIN ต้องเป็น 6 หลัก',
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

            $walletService = app(\App\Services\WalletService::class);

            // ดึง Wallet ผู้โอน
            $senderWallet = $walletService->getOrCreateWallet($user);

            // ตรวจสอบ PIN
            if (!$senderWallet->pin_hash || !Hash::check($request->pin, $senderWallet->pin_hash)) {
                return response()->json([
                    'success' => false,
                    'message' => 'PIN ไม่ถูกต้อง',
                ], 403);
            }

            // ค้นหา Wallet ผู้รับ
            $recipientWallet = \App\Models\Wallet::where('wallet_address', $request->wallet_address)
                ->where('status', 'active')
                ->first();

            if (!$recipientWallet) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบ Wallet ผู้รับ',
                ], 404);
            }

            // ตรวจสอบว่าไม่ใช่ตัวเอง
            if ($recipientWallet->user_id === $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่สามารถโอนเงินให้ตัวเองได้',
                ], 400);
            }

            // คำนวณค่าธรรมเนียม (1%)
            $amount = (float) $request->amount;
            $feeRate = 0.01;
            $fee = min(max(round($amount * $feeRate, 2), 0), 100);
            $totalDeduction = $amount + $fee;

            // ตรวจสอบยอดเงิน
            if ($senderWallet->balance < $totalDeduction) {
                return response()->json([
                    'success' => false,
                    'message' => 'ยอดเงินไม่เพียงพอ (ต้องการ ' . number_format($totalDeduction, 2) . ' บาท)',
                ], 400);
            }

            // หักเงินผู้โอน
            $senderWallet->decrement('balance', $totalDeduction);

            // เพิ่มเงินผู้รับ
            $recipientWallet->increment('balance', $amount);

            // บันทึก Transaction ผู้โอน
            \App\Models\WalletTransaction::create([
                'wallet_id' => $senderWallet->id,
                'user_id' => $user->id,
                'type' => 'transfer_out',
                'amount' => -$totalDeduction,
                'fee' => $fee,
                'balance_after' => $senderWallet->balance,
                'description' => 'โอนเงินไปยัง ' . $recipientWallet->wallet_address,
                'reference' => 'TF' . time() . rand(1000, 9999),
                'metadata' => [
                    'recipient_wallet' => $recipientWallet->wallet_address,
                    'recipient_user_id' => $recipientWallet->user_id,
                    'amount' => $amount,
                    'fee' => $fee,
                    'note' => $request->note ?? null,
                ],
                'status' => 'completed',
            ]);

            // บันทึก Transaction ผู้รับ
            \App\Models\WalletTransaction::create([
                'wallet_id' => $recipientWallet->id,
                'user_id' => $recipientWallet->user_id,
                'type' => 'transfer_in',
                'amount' => $amount,
                'fee' => 0,
                'balance_after' => $recipientWallet->balance,
                'description' => 'รับโอนเงินจาก ' . $senderWallet->wallet_address,
                'reference' => 'TF' . time() . rand(1000, 9999),
                'metadata' => [
                    'sender_wallet' => $senderWallet->wallet_address,
                    'sender_user_id' => $user->id,
                    'amount' => $amount,
                    'note' => $request->note ?? null,
                ],
                'status' => 'completed',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'โอนเงินสำเร็จ',
                'data' => [
                    'amount' => $amount,
                    'fee' => $fee,
                    'total_deduction' => $totalDeduction,
                    'new_balance' => $senderWallet->balance,
                    'recipient' => [
                        'name' => $recipientWallet->user->name ?? 'Unknown',
                        'wallet_address' => $recipientWallet->wallet_address,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Wallet Transfer Error', [
                'user_id' => $user->id,
                'recipient_address' => $request->wallet_address,
                'amount' => $request->amount,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการโอนเงิน กรุณาลองใหม่',
            ], 500);
        }
    }
}
