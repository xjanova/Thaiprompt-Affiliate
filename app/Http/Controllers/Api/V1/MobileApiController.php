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
}
