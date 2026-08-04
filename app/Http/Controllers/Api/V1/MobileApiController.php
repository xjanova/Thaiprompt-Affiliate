<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Services\ImageUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * MobileApiController
 *
 * API endpoints สำหรับ Mobile App
 * รองรับ: Register, Profile, Products, Cart, Orders
 */
class MobileApiController extends Controller
{
    /**
     * Cache สำหรับ Official Seller
     */
    protected static ?User $officialSeller = null;

    // =====================================================
    // Official Shop Helper (ใช้ร่วมกับ OfficialShopAdminController)
    // =====================================================

    /**
     * สร้างหรือดึง Official Shop Seller
     * ใช้ logic เดียวกันกับ OfficialShopAdminController
     */
    protected function getOrCreateOfficialSeller(): User
    {
        // ใช้ cache ถ้ามี
        if (self::$officialSeller !== null) {
            return self::$officialSeller;
        }

        $email = config('shop.official_shop.seller_email', 'official-shop@thaiprompt.com');

        // หา Official Seller จาก email
        $seller = User::where('email', $email)->first();

        if (! $seller) {
            // สร้าง Official Seller ใหม่ถ้าไม่มี (เหมือน OfficialShopAdminController)
            // ⚠️ หมายเหตุ: role อยู่ใน $guarded ดังนั้นต้องใช้ direct assignment
            $seller = User::create([
                'name' => config('shop.official_shop.name', 'Official Shop'),
                'email' => $email,
                'password' => Hash::make(Str::random(32)),
                'email_verified_at' => now(),
            ]);

            // Set role ด้วย direct assignment เพราะ role อยู่ใน $guarded
            $seller->role = 'seller';
            $seller->save();
        }

        self::$officialSeller = $seller;

        return $seller;
    }

    // =====================================================
    // Authentication
    // =====================================================

    /**
     * สมัครสมาชิกใหม่
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
            // หมายเหตุ: ไม่ต้องใส่ role เพราะ database default เป็น 'user' อยู่แล้ว
            // และ role อยู่ใน $guarded เพื่อป้องกัน privilege escalation
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'referral_code' => $referralCode,
                'sponsor_id' => $sponsorId,
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

            // Log error สำหรับ debugging
            \Log::error('Mobile Register Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'email' => $request->email,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการสมัครสมาชิก',
                // แสดง error detail เฉพาะใน debug mode
                'debug' => config('app.debug') ? $e->getMessage() : null,
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
                'address' => $user->address,
                'bio' => $user->bio,
                'bank_name' => $user->bank_name,
                'bank_account' => $user->bank_account,
                'bank_account_name' => $user->bank_account_name,
                'role' => $user->role,
                'referralCode' => $user->referral_code,
                'referralLink' => url('/register?ref='.$user->referral_code),
                'wallet_address' => $user->wallet?->wallet_address ?? null,
                'createdAt' => $user->created_at->toISOString(),
            ],
        ]);
    }

    /**
     * อัพเดทโปรไฟล์
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'avatar' => 'sometimes|nullable|string',
            'address' => 'sometimes|nullable|string|max:500',
            'bio' => 'sometimes|nullable|string|max:1000',
            'bank_name' => 'sometimes|nullable|string|max:100',
            'bank_account' => 'sometimes|nullable|string|max:50',
            'bank_account_name' => 'sometimes|nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $validator->errors(),
            ], 422);
        }

        // รับ fields ที่อนุญาต (ใช้ profile_picture แทน avatar เพราะ User model ใช้ชื่อนี้ใน fillable)
        $allowedFields = ['name', 'phone', 'address', 'bio', 'bank_name', 'bank_account', 'bank_account_name'];
        $updateData = $request->only($allowedFields);

        // Handle avatar แยก เพราะ Mobile App ส่งมาเป็น 'avatar' แต่ DB ใช้ 'profile_picture'
        if ($request->has('avatar')) {
            $updateData['profile_picture'] = $request->avatar;
        }

        // กรอง null values
        $updateData = array_filter($updateData, function ($value) {
            return $value !== null;
        });

        $user->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'อัพเดทโปรไฟล์สำเร็จ',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar' => $user->avatar,
                'address' => $user->address,
                'bio' => $user->bio,
                'bank_name' => $user->bank_name,
                'bank_account' => $user->bank_account,
                'bank_account_name' => $user->bank_account_name,
                'role' => $user->role,
                'referralCode' => $user->referral_code,
            ],
        ]);
    }

    /**
     * อัพโหลดรูปโปรไฟล์ (Avatar)
     */
    public function uploadAvatar(Request $request, ImageUploadService $imageUploadService): JsonResponse
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,webp,heic,heif|max:5120', // max 5MB, รองรับ iPhone HEIC/HEIF
        ], [
            'avatar.required' => 'กรุณาเลือกรูปภาพ',
            'avatar.image' => 'ไฟล์ต้องเป็นรูปภาพ',
            'avatar.mimes' => 'รองรับเฉพาะไฟล์ jpeg, png, jpg, gif, webp, heic, heif',
            'avatar.max' => 'ขนาดไฟล์ต้องไม่เกิน 5MB',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // ลบรูปเดิมถ้ามี (ใช้ ImageUploadService)
            if ($user->profile_picture) {
                $imageUploadService->deleteImage($user->profile_picture);
            }

            // อัพโหลดรูปใหม่ด้วย ImageUploadService
            // จะแปลงเป็น WebP และ resize อัตโนมัติ
            $path = $imageUploadService->uploadImage(
                $request->file('avatar'),
                'avatars',  // directory
                800,        // max width
                800,        // max height
                90          // quality
            );
            // Return: 'avatars/xxx.webp' (RELATIVE PATH)

            // อัพเดทข้อมูลผู้ใช้ - บันทึก PATH (ไม่ใช่ URL!)
            $user->profile_picture = $path;
            $user->save();

            // สร้าง URL สำหรับ response
            $avatarUrl = \Storage::disk('public')->url($path);

            return response()->json([
                'success' => true,
                'message' => 'อัพโหลดรูปโปรไฟล์สำเร็จ',
                'data' => [
                    'avatarUrl' => $avatarUrl,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'avatar' => $user->avatar,
                        'profile_picture_url' => $user->profile_picture_url,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Avatar Upload Error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการอัพโหลดรูป: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * ลบรูปโปรไฟล์ (Avatar)
     */
    public function deleteAvatar(ImageUploadService $imageUploadService): JsonResponse
    {
        $user = Auth::user();

        try {
            // ลบรูปถ้ามี (ใช้ ImageUploadService)
            if ($user->profile_picture) {
                $imageUploadService->deleteImage($user->profile_picture);
            }

            // อัพเดทข้อมูลผู้ใช้
            $user->profile_picture = null;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'ลบรูปโปรไฟล์สำเร็จ',
            ]);
        } catch (\Exception $e) {
            \Log::error('Avatar Delete Error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการลบรูป',
            ], 500);
        }
    }

    /**
     * เปลี่ยนรหัสผ่าน
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
        if (! Hash::check($request->current_password, $user->password)) {
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
     */
    public function getReferralCode(): JsonResponse
    {
        $user = Auth::user();

        return response()->json([
            'success' => true,
            'data' => [
                'referralCode' => $user->referral_code,
                'referralLink' => url('/register?ref='.$user->referral_code),
            ],
        ]);
    }

    // =====================================================
    // Products
    // =====================================================

    /**
     * ดึงรายการสินค้า
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
     */
    public function getProduct(int $id): JsonResponse
    {
        $product = Product::with(['category', 'reviews'])
            ->where('is_active', true)
            ->find($id);

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
    }

    /**
     * ดึงหมวดหมู่สินค้า
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

    // NOTE: Cart public methods (getCart, addToCart, updateCartItem, removeFromCart, clearCart)
    // ถูกย้ายไปส่วนท้ายของไฟล์ (ดู line ~3400+) เพื่อใช้ DB-based cart แทน cache-based

    // =====================================================
    // Cart Helper Methods (Legacy - สำหรับ backward compatibility)
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
        if (! $found) {
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
     * อัพเดทจำนวนสินค้าใน cart (Legacy - cache-based)
     */
    private function updateCartItemInCache($user, int $productId, int $quantity): array
    {
        $cart = $this->getUserCart($user);
        $items = $cart['items'] ?? [];

        if ($quantity <= 0) {
            // ลบสินค้า
            $items = array_filter($items, fn ($item) => $item['product_id'] != $productId);
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
     * ลบสินค้าออกจาก cart (Legacy - cache-based)
     */
    private function removeCartItemFromCache($user, int $productId): array
    {
        return $this->updateCartItemInCache($user, $productId, 0);
    }

    // =====================================================
    // Dashboard Charts
    // =====================================================

    /**
     * ดึงข้อมูล charts สำหรับ Dashboard
     */
    public function getDashboardCharts(): JsonResponse
    {
        $user = Auth::user();

        // ข้อมูล 7 วันล่าสุด
        $last7Days = collect(range(6, 0))->map(function ($daysAgo) {
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
                'referralLink' => url('/register?ref='.$user->referral_code),
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
     * ตรวจสอบสถานะ LINE Login สำหรับ Mobile App
     */
    public function lineStatus(): JsonResponse
    {
        try {
            $lineService = app(\App\Services\LineService::class);

            // ตรวจสอบว่า LINE Login ถูก configure และ enabled หรือไม่
            $isConfigured = $lineService->isConfigured();
            $settings = $lineService->getSettings();

            // ตรวจสอบว่ามี settings และ is_active
            $isEnabled = $settings && $settings->is_active;

            return response()->json([
                'success' => true,
                'data' => [
                    'enabled' => $isEnabled,
                    'configured' => $isConfigured,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => [
                    'enabled' => false,
                    'configured' => false,
                ],
                'message' => 'ไม่สามารถตรวจสอบสถานะ LINE Login ได้',
            ]);
        }
    }

    /**
     * ดึง LINE Login URL สำหรับ Mobile App
     */
    public function getLineLoginUrl(Request $request): JsonResponse
    {
        $lineService = app(\App\Services\LineService::class);

        if (! $lineService->isConfigured()) {
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
        // ใช้ web route แทน API route เพราะ LINE OAuth redirect มาเป็น GET request
        // Web route จะ redirect ไป app deep link (thaiprompt://login?code=xxx&state=yyy)
        $redirectUri = config('services.line.mobile_redirect_uri')
            ?? url('/auth/line/mobile-callback');

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
            // ใช้ web route redirect URI เหมือนกับตอนขอ auth URL
            $redirectUri = config('services.line.mobile_redirect_uri')
                ?? url('/auth/line/mobile-callback');

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
                'email' => $lineUserId.'@line.user', // email placeholder
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
                    'hasIdCard' => ! empty($kyc->id_card_image),
                    'hasSelfie' => ! empty($kyc->selfie_image),
                ] : null,
            ],
        ]);
    }

    /**
     * ส่ง KYC verification
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
            // รองรับ heic/heif สำหรับ iPhone และ webp สำหรับ Android
            'id_card_image' => 'required|image|mimes:jpeg,png,jpg,gif,webp,heic,heif|max:5120', // 5MB max
            'selfie_image' => 'required|image|mimes:jpeg,png,jpg,gif,webp,heic,heif|max:5120',
        ], [
            'id_card_image.required' => 'กรุณาอัพโหลดรูปบัตรประชาชน',
            'id_card_image.image' => 'ไฟล์ต้องเป็นรูปภาพ',
            'id_card_image.mimes' => 'รองรับเฉพาะไฟล์ jpeg, png, jpg, gif, webp, heic',
            'id_card_image.max' => 'ขนาดไฟล์ต้องไม่เกิน 5MB',
            'selfie_image.required' => 'กรุณาอัพโหลดรูปถ่ายคู่บัตร',
            'selfie_image.image' => 'ไฟล์ต้องเป็นรูปภาพ',
            'selfie_image.mimes' => 'รองรับเฉพาะไฟล์ jpeg, png, jpg, gif, webp, heic',
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
            $kycPath = 'kyc/'.$user->id;

            // อัพโหลดรูปบัตรประชาชน (ใช้ 'public' disk เพื่อให้ admin สามารถดูรูปได้ผ่าน URL)
            $idCardPath = $request->file('id_card_image')->store($kycPath, 'public');

            // อัพโหลดรูปถ่ายคู่บัตร
            $selfiePath = $request->file('selfie_image')->store($kycPath, 'public');

            // สร้าง KYC verification record
            $kyc = \App\Models\KycVerification::create([
                'user_id' => $user->id,
                'id_card_image' => $idCardPath,
                'selfie_image' => $selfiePath,
                'status' => 'pending',
                'submitted_at' => now(),
            ]);

            // อัพเดทสถานะ KYC ของ user (ใช้ direct assignment เพราะ kyc_status อยู่ใน $guarded)
            $user->kyc_status = 'pending';
            $user->save();

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
     */
    public function uploadKycImage(Request $request, ImageUploadService $imageUploadService): JsonResponse
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            // รองรับ heic/heif สำหรับ iPhone และ webp สำหรับ Android
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp,heic,heif|max:5120',
            'type' => 'required|in:id_card,selfie',
        ], [
            'image.required' => 'กรุณาอัพโหลดรูปภาพ',
            'image.image' => 'ไฟล์ต้องเป็นรูปภาพ',
            'image.mimes' => 'รองรับเฉพาะไฟล์ jpeg, png, jpg, gif, webp, heic',
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
            $type = $request->type;

            // อัพโหลดรูปด้วย ImageUploadService (แปลง WebP + resize)
            $imagePath = $imageUploadService->uploadImage(
                $request->file('image'),
                'kyc/'.$user->id,  // directory
                1600,                 // max width (KYC ต้องการความละเอียดสูง)
                1600,                 // max height
                95                    // quality (สูงเพื่อให้อ่าน ID card ชัดเจน)
            );

            // หา or สร้าง KYC draft
            $kyc = \App\Models\KycVerification::where('user_id', $user->id)
                ->whereIn('status', ['pending', 'draft'])
                ->latest()
                ->first();

            if (! $kyc) {
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

            // สร้าง URL สำหรับแสดงรูป
            $imageUrl = \Storage::disk('public')->url($imagePath);
            $idCardUrl = $kyc->id_card_image ? \Storage::disk('public')->url($kyc->id_card_image) : null;
            $selfieUrl = $kyc->selfie_image ? \Storage::disk('public')->url($kyc->selfie_image) : null;

            return response()->json([
                'success' => true,
                'message' => 'อัพโหลดรูปภาพสำเร็จ',
                'data' => [
                    'kycId' => $kyc->id,
                    'type' => $type,
                    'imageUrl' => $imageUrl,  // URL ของรูปที่อัพโหลด (สำหรับ preview)
                    'hasIdCard' => ! empty($kyc->id_card_image),
                    'hasSelfie' => ! empty($kyc->selfie_image),
                    'idCardUrl' => $idCardUrl,   // URL รูปบัตรประชาชน
                    'selfieUrl' => $selfieUrl,   // URL รูป selfie
                    'canSubmit' => ! empty($kyc->id_card_image) && ! empty($kyc->selfie_image),
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

        if (! $kyc) {
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

        // อัพเดทสถานะ user (ใช้ direct assignment เพราะ kyc_status อยู่ใน $guarded)
        $user->kyc_status = 'pending';
        $user->save();

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
     */
    public function getRiderStatus(): JsonResponse
    {
        $user = Auth::user();
        $rider = \App\Models\Rider::where('user_id', $user->id)->first();

        if (! $rider) {
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
     */
    public function uploadRiderDocument(Request $request): JsonResponse
    {
        $user = Auth::user();
        $rider = \App\Models\Rider::where('user_id', $user->id)->first();

        if (! $rider) {
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

        // บันทึกไฟล์ (ใช้ 'local' disk แทน 'private' เพื่อให้ทำงานได้กับ default config)
        $path = $request->file('image')->store(
            "riders/{$rider->id}/{$type}",
            'local'
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
     */
    public function updateRiderPermissions(Request $request): JsonResponse
    {
        $user = Auth::user();
        $rider = \App\Models\Rider::where('user_id', $user->id)->first();

        if (! $rider) {
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
     */
    public function setRiderAvailability(Request $request): JsonResponse
    {
        $user = Auth::user();
        $rider = \App\Models\Rider::where('user_id', $user->id)
            ->where('status', 'approved')
            ->first();

        if (! $rider) {
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
        if ($request->availability === 'online' && ! $rider->gps_permission_granted) {
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
     */
    public function updateRiderLocation(Request $request): JsonResponse
    {
        $user = Auth::user();
        $rider = \App\Models\Rider::where('user_id', $user->id)
            ->where('status', 'approved')
            ->first();

        if (! $rider) {
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
                'hasActiveJob' => ! is_null($activeJob),
                'jobId' => $activeJob?->id,
                'isTracking' => ! is_null($activeJob),
            ],
        ]);
    }

    /**
     * ดึงงานที่รอไรเดอร์
     */
    public function getAvailableJobs(Request $request): JsonResponse
    {
        $user = Auth::user();
        $rider = \App\Models\Rider::where('user_id', $user->id)
            ->where('status', 'approved')
            ->where('availability', 'online')
            ->first();

        if (! $rider) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเปิดรับงานก่อน',
            ], 400);
        }

        // ดึงงานที่รอไรเดอร์ใกล้เคียง
        $query = \App\Models\RiderJob::where('status', 'pending');

        // ถ้ามีตำแหน่งล่าสุด ให้จัดเรียงตามระยะทาง
        if ($rider->last_latitude && $rider->last_longitude) {
            $query->selectRaw('*,
                (6371 * acos(cos(radians(?)) * cos(radians(pickup_latitude)) *
                cos(radians(pickup_longitude) - radians(?)) +
                sin(radians(?)) * sin(radians(pickup_latitude)))) AS distance_km',
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
     */
    public function acceptJob(Request $request, int $jobId): JsonResponse
    {
        $user = Auth::user();
        $rider = \App\Models\Rider::where('user_id', $user->id)
            ->where('status', 'approved')
            ->first();

        if (! $rider) {
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

        if (! $job) {
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
     */
    public function getCurrentJob(): JsonResponse
    {
        $user = Auth::user();
        $rider = \App\Models\Rider::where('user_id', $user->id)->first();

        if (! $rider) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลไรเดอร์',
            ], 400);
        }

        $job = \App\Models\RiderJob::where('rider_id', $rider->id)
            ->whereIn('status', ['accepted', 'picking_up', 'picked_up', 'delivering'])
            ->first();

        if (! $job) {
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
     */
    public function updateJobStatus(Request $request, int $jobId): JsonResponse
    {
        $user = Auth::user();
        $rider = \App\Models\Rider::where('user_id', $user->id)->first();

        if (! $rider) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลไรเดอร์',
            ], 400);
        }

        $job = \App\Models\RiderJob::where('id', $jobId)
            ->where('rider_id', $rider->id)
            ->first();

        if (! $job) {
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
                'message' => 'ไม่สามารถสร้าง Ticket ได้: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * ดูรายละเอียด ticket
     */
    public function getTicket(int $ticketId): JsonResponse
    {
        $user = Auth::user();

        $ticket = \App\Models\SupportTicket::where('id', $ticketId)
            ->where('user_id', $user->id)
            ->with('messages.user')
            ->first();

        if (! $ticket) {
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
     */
    public function replyTicket(Request $request, int $ticketId): JsonResponse
    {
        $user = Auth::user();

        $ticket = \App\Models\SupportTicket::where('id', $ticketId)
            ->where('user_id', $user->id)
            ->first();

        if (! $ticket) {
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
     */
    public function rateTicket(Request $request, int $ticketId): JsonResponse
    {
        $user = Auth::user();

        $ticket = \App\Models\SupportTicket::where('id', $ticketId)
            ->where('user_id', $user->id)
            ->whereIn('status', ['resolved', 'closed'])
            ->first();

        if (! $ticket) {
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
     */
    public function markNotificationRead(Request $request, int $notificationId): JsonResponse
    {
        $user = Auth::user();

        $notification = \App\Models\UserNotification::where('id', $notificationId)
            ->where('user_id', $user->id)
            ->first();

        if (! $notification) {
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
     */
    public function deleteNotification(Request $request, int $notificationId): JsonResponse
    {
        $user = Auth::user();

        $notification = \App\Models\UserNotification::where('id', $notificationId)
            ->where('user_id', $user->id)
            ->first();

        if (! $notification) {
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
     */
    public function getRankDetail(int $rankId): JsonResponse
    {
        $rank = \App\Models\Rank::with(['requirements', 'bonuses'])
            ->find($rankId);

        if (! $rank) {
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
     */
    public function getUserRankProgress(): JsonResponse
    {
        $user = Auth::user();

        // ดึง current rank
        $currentRank = $user->currentRank;
        if (! $currentRank) {
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
                'referralLink' => url('/register?ref='.$user->referral_code),
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
                    'profile_picture_url' => $user->profile_picture_url,
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
     *
     * ⚠️ ใช้ผัง MLM จริง (mlm_members.unilevel_sponsor_id — สายที่คอมมิชชั่นวิ่งจริง)
     * เพื่อให้ผังในแอปตรงกับผังบนเว็บและการจ่ายคอม
     * fallback เป็น users.sponsor_id เฉพาะ user ที่ยังไม่เป็นสมาชิก MLM
     */
    private function buildTeamTree(int $userId, int $depth, int $currentDepth = 0): array
    {
        $member = \App\Models\MlmMember::where('user_id', $userId)->first();

        if ($member) {
            return $this->buildMlmTeamTree($member, $depth, $currentDepth);
        }

        return $this->buildLegacyTeamTree($userId, $depth, $currentDepth);
    }

    /**
     * สร้างผังทีมจากตาราง mlm_members (unilevel tree — แหล่งเดียวกับ engine จ่ายคอม)
     */
    private function buildMlmTeamTree(\App\Models\MlmMember $member, int $depth, int $currentDepth = 0): array
    {
        if ($currentDepth >= $depth) {
            return [];
        }

        // ⚠️ ห้าม eager-load 'rank' บน MlmMember (ไม่มี relation นี้) — ใช้ user.currentRank
        $children = $member->unilevelChildren()
            ->with(['user.currentRank:id,name,name_th,icon,color'])
            ->withCount('unilevelChildren')
            ->get();

        return $children->map(function ($child) use ($depth, $currentDepth) {
            $childUser = $child->user;
            $rank = $childUser?->currentRank;

            return [
                'id' => $child->user_id,
                'name' => $childUser->name ?? 'Unknown',
                'avatar' => $childUser->avatar ?? null,
                'profile_picture_url' => $childUser->profile_picture_url ?? null,
                'level' => $currentDepth + 1,
                'rank' => $rank ? [
                    'name' => $rank->name,
                    'nameTh' => $rank->name_th ?? $rank->name,
                    'icon' => $rank->icon,
                    'color' => $rank->color,
                ] : null,
                'childrenCount' => $child->unilevel_children_count,
                'isActive' => $child->status === 'active',
                'joinedAt' => $child->created_at->format('Y-m-d'),
                'children' => $this->buildMlmTeamTree($child, $depth, $currentDepth + 1),
            ];
        })->toArray();
    }

    /**
     * สร้างผังทีมจาก users.sponsor_id (legacy — เฉพาะ user ที่ไม่มี mlm_member)
     */
    private function buildLegacyTeamTree(int $userId, int $depth, int $currentDepth = 0): array
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
                'profile_picture_url' => $child->profile_picture_url,
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
                'children' => $this->buildLegacyTeamTree($child->id, $depth, $currentDepth + 1),
            ];
        })->toArray();
    }

    /**
     * นับสมาชิกทั้งหมดในทีม (ใช้ mlm_members ถ้าเป็นสมาชิก MLM)
     */
    private function countTotalTeam(int $userId, int $maxDepth = 10): int
    {
        $member = \App\Models\MlmMember::where('user_id', $userId)->first();

        $total = 0;

        if ($member) {
            // BFS บน mlm_members.unilevel_sponsor_id
            $currentLevel = [$member->id];
            for ($i = 0; $i < $maxDepth && ! empty($currentLevel); $i++) {
                $children = \App\Models\MlmMember::whereIn('unilevel_sponsor_id', $currentLevel)->pluck('id')->toArray();
                $total += count($children);
                $currentLevel = $children;
            }

            return $total;
        }

        $currentLevel = [$userId];
        for ($i = 0; $i < $maxDepth && ! empty($currentLevel); $i++) {
            $children = \App\Models\User::whereIn('sponsor_id', $currentLevel)->pluck('id')->toArray();
            $total += count($children);
            $currentLevel = $children;
        }

        return $total;
    }

    /**
     * เช็คว่า target อยู่ใต้สายงานของ ancestor หรือไม่ (เดินขึ้นจาก target — O(depth))
     *
     * 🔐 ใช้กัน IDOR: ผู้เรียกดูข้อมูลได้เฉพาะ downline ของตัวเองเท่านั้น
     * เดินตาม mlm_members.unilevel_sponsor_id ก่อน (สายจริง) แล้ว fallback users.sponsor_id
     */
    private function isInDownline(int $ancestorUserId, int $targetUserId, int $maxDepth = 30): bool
    {
        if ($ancestorUserId === $targetUserId) {
            return true;
        }

        // ─── เดินขึ้นตามผัง MLM ───
        $targetMember = \App\Models\MlmMember::where('user_id', $targetUserId)->first();

        if ($targetMember) {
            $visited = [];
            $current = $targetMember;

            for ($i = 0; $i < $maxDepth; $i++) {
                $sponsorId = $current->unilevel_sponsor_id;
                if (! $sponsorId || isset($visited[$sponsorId])) {
                    break; // ถึงราก หรือเจอ cycle
                }
                $visited[$sponsorId] = true;

                $current = \App\Models\MlmMember::find($sponsorId);
                if (! $current) {
                    break;
                }
                if ((int) $current->user_id === $ancestorUserId) {
                    return true;
                }
            }

            return false;
        }

        // ─── fallback: เดินขึ้นตาม users.sponsor_id ───
        $visited = [];
        $currentId = $targetUserId;

        for ($i = 0; $i < $maxDepth; $i++) {
            $sponsorId = \App\Models\User::where('id', $currentId)->value('sponsor_id');
            if (! $sponsorId || isset($visited[$sponsorId])) {
                break;
            }
            if ((int) $sponsorId === $ancestorUserId) {
                return true;
            }
            $visited[$sponsorId] = true;
            $currentId = $sponsorId;
        }

        return false;
    }

    /**
     * รวบรวม user_id ของ downline ทั้งหมดในสายงาน (BFS จำกัดความลึก)
     *
     * ใช้สำหรับ scope การค้นหาสมาชิกให้อยู่ในทีมตัวเองเท่านั้น
     *
     * @return array<int> รายการ user_id ของ downline (ไม่รวมตัวเอง)
     */
    private function collectDownlineUserIds(int $userId, int $maxDepth = 10): array
    {
        $member = \App\Models\MlmMember::where('user_id', $userId)->first();

        $userIds = [];

        if ($member) {
            // BFS บน mlm_members แล้ว map เป็น user_id
            $currentLevel = [$member->id];
            for ($i = 0; $i < $maxDepth && ! empty($currentLevel); $i++) {
                $children = \App\Models\MlmMember::whereIn('unilevel_sponsor_id', $currentLevel)
                    ->get(['id', 'user_id']);
                if ($children->isEmpty()) {
                    break;
                }
                $userIds = array_merge($userIds, $children->pluck('user_id')->all());
                $currentLevel = $children->pluck('id')->all();
            }

            return array_values(array_unique($userIds));
        }

        // fallback: BFS บน users.sponsor_id
        $currentLevel = [$userId];
        for ($i = 0; $i < $maxDepth && ! empty($currentLevel); $i++) {
            $children = \App\Models\User::whereIn('sponsor_id', $currentLevel)->pluck('id')->all();
            if (empty($children)) {
                break;
            }
            $userIds = array_merge($userIds, $children);
            $currentLevel = $children;
        }

        return array_values(array_unique($userIds));
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

    /**
     * ดึง children ของ member ใน MLM tree
     */
    public function getTeamTreeChildren(int $userId): JsonResponse
    {
        $user = Auth::user();

        // 🔐 กัน IDOR: ดูได้เฉพาะ subtree ของตัวเองหรือ downline ในสายตัวเองเท่านั้น
        // (เดิมเช็คแค่ "target มี sponsor" → ใครก็ดึง subtree ของคนอื่นได้ทั้งระบบ)
        if (! $this->isInDownline($user->id, $userId)) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบสมาชิกในสายงานของคุณ',
            ], 404);
        }

        $children = $this->buildTeamTree($userId, 2);

        return response()->json([
            'success' => true,
            'data' => [
                'children' => $children,
            ],
        ]);
    }

    /**
     * ค้นหาสมาชิกในทีม
     */
    public function searchTeamMember(Request $request): JsonResponse
    {
        $user = Auth::user();
        $query = $request->get('q', '');
        $limit = min($request->get('limit', 20), 50);

        if (strlen($query) < 2) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        // 🔐 กัน IDOR: ค้นหาได้เฉพาะสมาชิกในสายงานของตัวเองเท่านั้น
        // (เดิมค้น user ทั้งระบบ + คืนอีเมลเต็ม = PII รั่วทั้งฐาน)
        $downlineUserIds = $this->collectDownlineUserIds($user->id);

        if (empty($downlineUserIds)) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $members = \App\Models\User::whereIn('id', $downlineUserIds)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('id', 'like', "%{$query}%");
            })
            ->with('currentRank:id,name,name_th,icon,color')
            ->limit($limit)
            ->get()
            ->map(function ($member) {
                // ปกปิดอีเมลบางส่วนในผลค้นหา (ลด PII รั่ว)
                $maskedEmail = $member->email
                    ? \Illuminate\Support\Str::mask($member->email, '*', 2, max(1, strpos($member->email, '@') - 4))
                    : null;

                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $maskedEmail,
                    'avatar' => $member->avatar,
                    'profile_picture_url' => $member->profile_picture_url,
                    'rank' => $member->currentRank ? [
                        'name' => $member->currentRank->name,
                        'nameTh' => $member->currentRank->name_th ?? $member->currentRank->name,
                        'icon' => $member->currentRank->icon,
                        'color' => $member->currentRank->color,
                    ] : null,
                    'joinedAt' => $member->created_at->format('Y-m-d'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $members,
        ]);
    }

    /**
     * ดึงข้อมูล profile ของสมาชิกในทีม
     */
    public function getMemberProfile(int $userId): JsonResponse
    {
        $user = Auth::user();

        // 🔐 กัน IDOR: ดูโปรไฟล์ได้เฉพาะตัวเองหรือสมาชิกในสายงานตัวเองเท่านั้น
        // (เดิมไม่มีการตรวจสิทธิ์ → ไล่ id ดึงชื่อ+อีเมลของ user ทั้งระบบได้)
        if (! $this->isInDownline($user->id, $userId)) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบสมาชิกในสายงานของคุณ',
            ], 404);
        }

        $member = \App\Models\User::with('currentRank:id,name,name_th,icon,color')
            ->find($userId);

        if (! $member) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบสมาชิก',
            ], 404);
        }

        $directReferrals = \App\Models\User::where('sponsor_id', $userId)->count();
        $totalTeam = $this->countTotalTeam($userId);

        // อีเมลเต็มเห็นได้เฉพาะตัวเอง — ลูกทีมเห็นแบบปกปิดบางส่วน (ลด PII รั่ว)
        $email = $member->email;
        if ($userId !== $user->id && $email) {
            $email = \Illuminate\Support\Str::mask($email, '*', 2, max(1, strpos($email, '@') - 4));
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $email,
                'avatar' => $member->avatar,
                'profile_picture_url' => $member->profile_picture_url,
                'rank' => $member->currentRank ? [
                    'id' => $member->currentRank->id,
                    'name' => $member->currentRank->name,
                    'nameTh' => $member->currentRank->name_th ?? $member->currentRank->name,
                    'icon' => $member->currentRank->icon,
                    'color' => $member->currentRank->color,
                ] : null,
                'statistics' => [
                    'directReferrals' => $directReferrals,
                    'totalTeamMembers' => $totalTeam,
                ],
                'joinedAt' => $member->created_at->format('Y-m-d'),
                'isActive' => $member->created_at >= now()->subDays(30),
            ],
        ]);
    }

    // =====================================================
    // Commission System
    // =====================================================

    /**
     * ดึงรายการคอมมิชชัน
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

            if (! $product->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'สินค้านี้ไม่พร้อมจำหน่าย',
                ], 400);
            }

            // 🚫 สินค้า affiliate ใส่ตะกร้าไม่ได้ (เราไม่ได้สต๊อก/ไม่ได้ส่งเอง)
            //    ต้องกันที่นี่ด้วย ไม่ใช่เฉพาะฝั่งเว็บ ไม่งั้นแอปมือถือยังสร้างออเดอร์ผีได้
            if ($product->is_affiliate) {
                return response()->json([
                    'success' => false,
                    'message' => 'สินค้านี้ต้องสั่งซื้อที่แพลตฟอร์มต้นทางค่ะ',
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

            if (! $cart || $cart->items->isEmpty()) {
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

            if (! $cart || $cart->items->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่มีสินค้าในตะกร้า',
                ], 400);
            }

            // ตรวจสอบสต็อกทุกรายการ
            foreach ($cart->items as $item) {
                if (! $item->isAvailable()) {
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

            // ดึงข้อมูลที่อยู่จัดส่ง (ถ้ามี)
            $shippingAddressSnapshot = null;
            if ($request->shipping_address_id) {
                $shippingAddress = \App\Models\UserAddress::where('id', $request->shipping_address_id)
                    ->where('user_id', $user->id)
                    ->first();

                if ($shippingAddress) {
                    $shippingAddressSnapshot = [
                        'name' => $shippingAddress->name,
                        'phone' => $shippingAddress->phone,
                        'address' => $shippingAddress->address,
                        'subdistrict' => $shippingAddress->subdistrict,
                        'district' => $shippingAddress->district,
                        'province' => $shippingAddress->province,
                        'postal_code' => $shippingAddress->postal_code,
                    ];
                }
            }

            // สร้าง Order
            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => 'ORD-'.strtoupper(Str::random(8)),
                'status' => $request->payment_method === 'cod' ? 'pending' : 'processing',
                'payment_method' => $request->payment_method,
                'payment_status' => $request->payment_method === 'wallet' ? 'paid' : 'pending',
                'subtotal' => $totalPrice,
                'shipping_fee' => $shippingFee,
                'discount_amount' => $discount,
                'total_amount' => $grandTotal,
                'shipping_address_id' => $request->shipping_address_id,
                'shipping_address_snapshot' => $shippingAddressSnapshot,
                'customer_notes' => $request->note,
            ]);

            // สร้าง Order Items
            foreach ($cart->items as $item) {
                $price = $item->product->discount_price ?? $item->product->price;
                $pv = $item->product->pv_value ?? round($item->product->price * 0.1, 2);

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'seller_id' => $item->product->seller_id ?? \App\Models\Product::getOfficialSellerId(),
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

            // 🐛 Fix 2026-07-24: ออเดอร์ wallet ถูกสร้างเป็น paid ตั้งแต่ create
            // → OrderObserver (จับเฉพาะ 'updated' + wasChanged) ไม่ยิง
            // → MLM Commission/PV/Cashback/Distribution ไม่ประมวลผลเรียลไทม์
            // ต้อง trigger ตรงนี้หลัง commit (OrderItems ครบแล้ว) — idempotent ด้วย isOrderDistributed
            if ($order->payment_status === 'paid') {
                try {
                    $distributionService = app(\App\Services\OrderDistributionService::class);

                    if (! $distributionService->isOrderDistributed($order)) {
                        // จ่าย Cashback ให้ลูกค้า (มี dup-guard ภายใน)
                        app(\App\Services\CashbackService::class)->processOrderCashback($order);

                        // แบ่งเงิน Seller + เก็บ Fee/VAT/MLM Pool + คำนวณ MLM Commission
                        $distributionService->processOrderDistribution($order->fresh(['items']));
                    }
                } catch (\Throwable $distErr) {
                    // ไม่ให้ error ฝั่ง distribution ทำ checkout ล้ม — batch ทุก 5 นาทีจะเก็บตก
                    \Log::error('Mobile checkout: order distribution ล้มเหลว (batch จะ retry)', [
                        'order_id' => $order->id,
                        'error' => $distErr->getMessage(),
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'สร้างคำสั่งซื้อสำเร็จ',
                'data' => [
                    'orderId' => $order->id,
                    'orderNumber' => $order->order_number,
                    'status' => $order->status,
                    'paymentStatus' => $order->payment_status,
                    'total' => $order->total_amount,
                    'subtotal' => $order->subtotal,
                    'shippingFee' => $order->shipping_fee,
                    'discount' => $order->discount_amount,
                    'pvEarned' => $totalPV,
                    'paymentMethod' => $request->payment_method,
                    'shippingAddress' => $shippingAddressSnapshot,
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

            if (! $wallet) {
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

            if (! $recipient) {
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
            if (! $senderWallet->pin_hash || ! Hash::check($request->pin, $senderWallet->pin_hash)) {
                return response()->json([
                    'success' => false,
                    'message' => 'PIN ไม่ถูกต้อง',
                ], 403);
            }

            // ค้นหา Wallet ผู้รับ
            $recipientWallet = \App\Models\Wallet::where('wallet_address', $request->wallet_address)
                ->where('status', 'active')
                ->first();

            if (! $recipientWallet) {
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
                    'message' => 'ยอดเงินไม่เพียงพอ (ต้องการ '.number_format($totalDeduction, 2).' บาท)',
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
                'description' => 'โอนเงินไปยัง '.$recipientWallet->wallet_address,
                'reference' => 'TF'.time().rand(1000, 9999),
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
                'description' => 'รับโอนเงินจาก '.$senderWallet->wallet_address,
                'reference' => 'TF'.time().rand(1000, 9999),
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

    // =====================================================
    // GPS Sharing - แชร์ตำแหน่ง GPS ให้ Admin ดู
    // =====================================================

    /**
     * แชร์ตำแหน่ง GPS
     */
    public function shareGpsLocation(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเข้าสู่ระบบ',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'altitude' => 'nullable|numeric',
            'accuracy' => 'nullable|numeric',
            'speed' => 'nullable|numeric',
            'heading' => 'nullable|numeric',
            'battery_level' => 'nullable|integer|between:0,100',
            'device_model' => 'nullable|string|max:100',
            'os_version' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // บันทึกหรืออัพเดทตำแหน่ง GPS
            $gpsData = [
                'user_id' => $user->id,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'altitude' => $request->altitude,
                'accuracy' => $request->accuracy,
                'speed' => $request->speed,
                'heading' => $request->heading,
                'battery_level' => $request->battery_level,
                'device_model' => $request->device_model,
                'os_version' => $request->os_version,
                'is_sharing' => true,
                'last_update' => now(),
            ];

            // ใช้ updateOrCreate เพื่อบันทึก GPS
            DB::table('user_gps_locations')->updateOrInsert(
                ['user_id' => $user->id],
                array_merge($gpsData, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );

            return response()->json([
                'success' => true,
                'message' => 'อัพเดทตำแหน่งสำเร็จ',
            ]);
        } catch (\Exception $e) {
            \Log::error('Share GPS Error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการอัพเดทตำแหน่ง',
            ], 500);
        }
    }

    /**
     * หยุดแชร์ตำแหน่ง GPS
     */
    public function stopGpsSharing(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเข้าสู่ระบบ',
            ], 401);
        }

        try {
            // อัพเดทสถานะเป็นหยุดแชร์
            DB::table('user_gps_locations')
                ->where('user_id', $user->id)
                ->update([
                    'is_sharing' => false,
                    'updated_at' => now(),
                ]);

            return response()->json([
                'success' => true,
                'message' => 'หยุดแชร์ตำแหน่งสำเร็จ',
            ]);
        } catch (\Exception $e) {
            \Log::error('Stop GPS Sharing Error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด',
            ], 500);
        }
    }

    // =====================================================
    // Store Listing - รายการร้านค้า
    // =====================================================

    /**
     * ดึงรายการร้านค้าทางการ (Official Stores)
     */
    public function getOfficialStores(): JsonResponse
    {
        try {
            // ดึงร้านค้าที่ verified และ active
            $stores = \App\Models\VendorStore::where('is_active', true)
                ->where('is_verified', true)
                ->orderBy('rating_average', 'desc')
                ->limit(20)
                ->get();

            $formattedStores = $stores->map(function ($store) {
                return [
                    'id' => (string) $store->id,
                    'name' => $store->store_name,
                    'logo' => $store->store_logo ? url($store->store_logo) : null,
                    'rating' => (float) ($store->rating_average ?? 4.5),
                    'isOfficial' => true,
                    'isFeatured' => (bool) $store->is_featured_home,
                    'productCount' => $store->total_products ?? 0,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedStores,
            ]);
        } catch (\Exception $e) {
            \Log::error('Get Official Stores Error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }
    }

    /**
     * ดึงรายการร้านแนะนำติดดาว (Featured Stores)
     */
    public function getFeaturedStores(): JsonResponse
    {
        try {
            // ดึงร้านค้าที่ featured และ active
            $stores = \App\Models\VendorStore::where('is_active', true)
                ->where('is_featured_home', true)
                ->orderBy('featured_home_order', 'asc')
                ->orderBy('rating_average', 'desc')
                ->limit(20)
                ->get();

            $formattedStores = $stores->map(function ($store) {
                return [
                    'id' => (string) $store->id,
                    'name' => $store->store_name,
                    'logo' => $store->store_logo ? url($store->store_logo) : null,
                    'rating' => (float) ($store->rating_average ?? 4.5),
                    'isOfficial' => (bool) $store->is_verified,
                    'isFeatured' => true,
                    'productCount' => $store->total_products ?? 0,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedStores,
            ]);
        } catch (\Exception $e) {
            \Log::error('Get Featured Stores Error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }
    }

    /**
     * ดึงรายละเอียดร้านค้า
     */
    public function getStoreDetail(string $storeId): JsonResponse
    {
        try {
            $store = \App\Models\VendorStore::where('id', $storeId)
                ->where('is_active', true)
                ->first();

            if (! $store) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบร้านค้า',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => (string) $store->id,
                    'name' => $store->store_name,
                    'description' => $store->store_description,
                    'logo' => $store->store_logo ? url($store->store_logo) : null,
                    'banner' => $store->store_banner ? url($store->store_banner) : null,
                    'rating' => (float) ($store->rating_average ?? 4.5),
                    'ratingCount' => $store->rating_count ?? 0,
                    'isOfficial' => (bool) $store->is_verified,
                    'isFeatured' => (bool) $store->is_featured_home,
                    'productCount' => $store->total_products ?? 0,
                    'followerCount' => 0, // ไม่มีฟิลด์นี้ในตอนนี้
                    'joinedAt' => $store->created_at?->format('Y-m-d'),
                    'responseRate' => 98, // Mock value
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Get Store Detail Error', [
                'store_id' => $storeId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด',
            ], 500);
        }
    }

    /**
     * ดึงสินค้าของร้านค้า
     */
    public function getStoreProducts(Request $request, string $storeId): JsonResponse
    {
        try {
            $store = \App\Models\VendorStore::where('id', $storeId)
                ->where('is_active', true)
                ->first();

            if (! $store) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบร้านค้า',
                ], 404);
            }

            $page = $request->get('page', 1);
            $limit = min($request->get('limit', 10), 50);

            $products = Product::where('store_id', $store->id)
                ->where('is_active', true)
                ->orderBy('created_at', 'desc')
                ->paginate($limit, ['*'], 'page', $page);

            $formattedProducts = $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'image' => $product->image ? url($product->image) : null,
                    'price' => (float) $product->price,
                    'discount_price' => $product->discount_price ? (float) $product->discount_price : null,
                    'rating' => $product->rating ?? 0,
                    'review_count' => $product->review_count ?? 0,
                    'pv' => $product->pv_value ?? round($product->price * 0.1),
                    'commission_rate' => $product->commission_rate ?? 0,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedProducts,
                'pagination' => [
                    'total' => $products->total(),
                    'currentPage' => $products->currentPage(),
                    'lastPage' => $products->lastPage(),
                    'hasMore' => $products->hasMorePages(),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Get Store Products Error', [
                'store_id' => $storeId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด',
            ], 500);
        }
    }

    // =====================================================
    // Premium Store (Official Shop)
    // =====================================================

    /**
     * ดึงข้อมูลร้านพรีเมี่ยม (Official Shop)
     * มีร้านเดียวในระบบที่ขายสินค้าจากแพลตฟอร์ม
     */
    public function getPremiumStore(): JsonResponse
    {
        try {
            // ดึงหรือสร้าง Official Seller (ใช้ logic เดียวกับ Admin Panel)
            $seller = $this->getOrCreateOfficialSeller();

            // นับจำนวนสินค้าที่ active ทั้งหมด (ไม่เฉพาะ Official เพื่อให้ตรงกับที่แสดงในแอพ)
            $productCount = Product::where('is_active', true)->count();

            // นับจำนวนสินค้าแนะนำทั้งหมด
            $featuredCount = Product::where('is_active', true)
                ->where('is_featured', true)
                ->count();

            // นับจำนวนสินค้า Official Shop (สำหรับแสดงใน UI)
            $officialProductCount = Product::where('seller_id', $seller->id)
                ->where('is_active', true)
                ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => 'premium',
                    'sellerId' => $seller->id,
                    'name' => config('shop.official_shop.name', 'Thaiprompt Shop'),
                    'description' => config('shop.official_shop.description', 'ร้านค้าทางการของระบบ สินค้าคุณภาพสูง คอมมิชชั่นสูง'),
                    'logo' => config('shop.official_shop.logo') ?: asset('images/premium-store-logo.png'),
                    'banner' => config('shop.official_shop.banner') ?: asset('images/premium-store-banner.png'),
                    'rating' => 5.0,
                    'ratingCount' => $productCount > 0 ? $productCount * 5 : 100,
                    'isOfficial' => true,
                    'isPremium' => true,
                    'productCount' => $productCount,
                    'officialProductCount' => $officialProductCount,
                    'featuredCount' => $featuredCount,
                    'verified' => true,
                    'commissionRate' => config('shop.official_shop.default_commission_rate', 25),
                    'features' => [
                        'สินค้าของแท้ 100%',
                        'รับประกันคุณภาพ',
                        'คอมมิชชั่นสูง',
                        'จัดส่งรวดเร็ว',
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Get Premium Store Error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด',
            ], 500);
        }
    }

    /**
     * ดึงสินค้าจากร้านพรีเมี่ยม (Official Shop)
     */
    public function getPremiumStoreProducts(Request $request): JsonResponse
    {
        try {
            // ดึงหรือสร้าง Official Seller (ใช้ logic เดียวกับ Admin Panel)
            $seller = $this->getOrCreateOfficialSeller();

            $page = $request->get('page', 1);
            $limit = min($request->get('limit', 10), 50);
            $category = $request->get('category');
            $featured = $request->boolean('featured', false);
            $search = $request->get('search');
            $officialOnly = $request->boolean('official_only', false);

            // เริ่มต้น query สำหรับสินค้า active ทั้งหมด
            $query = Product::where('is_active', true)
                ->with('category');

            // ถ้าต้องการเฉพาะ Official Shop หรือมีสินค้า Official Shop
            if ($officialOnly) {
                $query->where('seller_id', $seller->id);
            } else {
                // ดึงทุกสินค้า แต่ให้ Official Shop มาก่อน
                $query->orderByRaw('CASE WHEN seller_id = ? THEN 0 ELSE 1 END', [$seller->id]);
            }

            // กรองตามหมวดหมู่
            if ($category) {
                $query->where('category_id', $category);
            }

            // กรองเฉพาะสินค้าแนะนำ
            if ($featured) {
                $query->where('is_featured', true);
            }

            // ค้นหา
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // เรียงลำดับ: Featured ก่อน, ตามด้วย Created At
            $query->orderBy('is_featured', 'desc')
                ->orderBy('created_at', 'desc');

            $products = $query->paginate($limit, ['*'], 'page', $page);

            $formattedProducts = $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'description' => $product->short_description ?: \Str::limit($product->description, 100),
                    'image' => $product->main_image_url ? url($product->main_image_url) : null,
                    'images' => $product->image_urls ?? [],
                    'price' => (float) $product->price,
                    'discount_price' => $product->compare_at_price && $product->compare_at_price > $product->price
                        ? (float) $product->price
                        : null,
                    'original_price' => $product->compare_at_price ? (float) $product->compare_at_price : null,
                    'category' => $product->category?->name,
                    'categoryId' => $product->category_id,
                    'rating' => (float) ($product->rating_average ?? 0),
                    'review_count' => $product->rating_count ?? 0,
                    'pv' => (float) ($product->pv_value ?? round($product->price * 0.1)),
                    'commission_rate' => (float) ($product->commission_rate ?? 0),
                    'is_featured' => (bool) $product->is_featured,
                    'stock_status' => $product->stock_status ?? 'in_stock',
                    'brand' => $product->brand,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedProducts,
                'pagination' => [
                    'total' => $products->total(),
                    'currentPage' => $products->currentPage(),
                    'lastPage' => $products->lastPage(),
                    'perPage' => $products->perPage(),
                    'hasMore' => $products->hasMorePages(),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Get Premium Store Products Error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด',
            ], 500);
        }
    }

    // =====================================================
    // Academy - ระบบการเรียนรู้ออนไลน์
    // =====================================================

    /**
     * ดึงรายการหลักสูตร
     */
    public function getCourses(Request $request): JsonResponse
    {
        try {
            $category = $request->get('category', 'all');

            // หลักสูตรตัวอย่าง (สามารถเปลี่ยนเป็นดึงจาก DB ได้)
            $courses = [
                [
                    'id' => 'mlm-basics',
                    'title' => 'พื้นฐาน MLM สู่ความสำเร็จ',
                    'description' => 'เรียนรู้หลักการ MLM ตั้งแต่เริ่มต้น สร้างทีมและรายได้อย่างยั่งยืน',
                    'instructor' => 'อ.สมชาย รวยดี',
                    'duration' => '4 ชั่วโมง',
                    'lessons' => 12,
                    'level' => 'beginner',
                    'price' => 0,
                    'originalPrice' => null,
                    'rating' => 4.8,
                    'students' => 1250,
                    'icon' => '📚',
                    'gradientColors' => ['#10B981', '#059669'],
                    'isFree' => true,
                    'isNew' => false,
                    'isPopular' => true,
                ],
                [
                    'id' => 'crypto-trading',
                    'title' => 'เทรด Crypto สำหรับมือใหม่',
                    'description' => 'เข้าใจตลาด Cryptocurrency และเริ่มต้นเทรดอย่างมืออาชีพ',
                    'instructor' => 'อ.วิทย์ คริปโต',
                    'duration' => '6 ชั่วโมง',
                    'lessons' => 18,
                    'level' => 'beginner',
                    'price' => 990,
                    'originalPrice' => 1990,
                    'rating' => 4.9,
                    'students' => 856,
                    'icon' => '🪙',
                    'gradientColors' => ['#F59E0B', '#D97706'],
                    'isFree' => false,
                    'isNew' => true,
                    'isPopular' => false,
                ],
                [
                    'id' => 'affiliate-marketing',
                    'title' => 'Affiliate Marketing Masterclass',
                    'description' => 'สร้างรายได้ Passive Income จาก Affiliate Marketing',
                    'instructor' => 'อ.นิดา แอฟฟิลิเอท',
                    'duration' => '8 ชั่วโมง',
                    'lessons' => 24,
                    'level' => 'intermediate',
                    'price' => 1490,
                    'originalPrice' => 2990,
                    'rating' => 4.7,
                    'students' => 632,
                    'icon' => '💰',
                    'gradientColors' => ['#8B5CF6', '#6D28D9'],
                    'isFree' => false,
                    'isNew' => false,
                    'isPopular' => true,
                ],
                [
                    'id' => 'leadership',
                    'title' => 'ภาวะผู้นำและการสร้างทีม',
                    'description' => 'พัฒนาทักษะผู้นำ สร้างทีมที่แข็งแกร่ง',
                    'instructor' => 'อ.ประสิทธิ์ ลีดเดอร์',
                    'duration' => '5 ชั่วโมง',
                    'lessons' => 15,
                    'level' => 'advanced',
                    'price' => 1990,
                    'originalPrice' => null,
                    'rating' => 4.6,
                    'students' => 423,
                    'icon' => '👥',
                    'gradientColors' => ['#EC4899', '#DB2777'],
                    'isFree' => false,
                    'isNew' => false,
                    'isPopular' => false,
                ],
                [
                    'id' => 'digital-marketing',
                    'title' => 'Digital Marketing 2024',
                    'description' => 'เทคนิคการตลาดดิจิทัลล่าสุด Facebook, TikTok, LINE',
                    'instructor' => 'อ.มาร์ค ดิจิทัล',
                    'duration' => '10 ชั่วโมง',
                    'lessons' => 30,
                    'level' => 'intermediate',
                    'price' => 2490,
                    'originalPrice' => 4990,
                    'rating' => 4.8,
                    'students' => 1089,
                    'icon' => '📱',
                    'gradientColors' => ['#3B82F6', '#2563EB'],
                    'isFree' => false,
                    'isNew' => true,
                    'isPopular' => true,
                ],
                [
                    'id' => 'personal-finance',
                    'title' => 'วางแผนการเงินส่วนบุคคล',
                    'description' => 'จัดการเงิน ออม ลงทุน สู่อิสรภาพทางการเงิน',
                    'instructor' => 'อ.เงินทอง มั่งมี',
                    'duration' => '3 ชั่วโมง',
                    'lessons' => 10,
                    'level' => 'beginner',
                    'price' => 0,
                    'originalPrice' => null,
                    'rating' => 4.5,
                    'students' => 2150,
                    'icon' => '💳',
                    'gradientColors' => ['#14B8A6', '#0D9488'],
                    'isFree' => true,
                    'isNew' => false,
                    'isPopular' => false,
                ],
            ];

            // กรองตาม category
            if ($category === 'free') {
                $courses = array_filter($courses, fn ($c) => $c['isFree']);
            } elseif ($category === 'popular') {
                $courses = array_filter($courses, fn ($c) => $c['isPopular']);
            } elseif ($category === 'new') {
                $courses = array_filter($courses, fn ($c) => $c['isNew']);
            }

            return response()->json([
                'success' => true,
                'data' => array_values($courses),
                'stats' => [
                    'totalCourses' => 50,
                    'totalStudents' => 10000,
                    'avgRating' => 4.8,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }
    }

    /**
     * ดึงรายละเอียดหลักสูตร
     */
    public function getCourseDetail(string $courseId): JsonResponse
    {
        // ตัวอย่าง: หาหลักสูตรจาก ID
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $courseId,
                'title' => 'หลักสูตรตัวอย่าง',
                'description' => 'รายละเอียดหลักสูตร...',
                'lessons' => [
                    ['id' => 1, 'title' => 'บทที่ 1: แนะนำ', 'duration' => '10:00', 'isFree' => true],
                    ['id' => 2, 'title' => 'บทที่ 2: พื้นฐาน', 'duration' => '15:00', 'isFree' => false],
                    ['id' => 3, 'title' => 'บทที่ 3: ขั้นสูง', 'duration' => '20:00', 'isFree' => false],
                ],
            ],
        ]);
    }

    /**
     * ดึงหลักสูตรของฉัน
     */
    public function getMyCourses(): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเข้าสู่ระบบ',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'enrolled' => [],
                'completed' => [],
                'inProgress' => [],
            ],
        ]);
    }

    // =====================================================
    // Watch & Earn - ดูคลิปได้เงิน
    // =====================================================

    /**
     * ดึงรายการวิดีโอ
     */
    public function getVideos(Request $request): JsonResponse
    {
        try {
            $category = $request->get('category', 'all');
            $user = Auth::user();

            // วิดีโอตัวอย่าง
            $videos = [
                [
                    'id' => '1',
                    'title' => 'วิธีสร้างรายได้ออนไลน์ 2024',
                    'thumbnail' => '🎬',
                    'duration' => '2:30',
                    'reward' => 5,
                    'platform' => 'tiktok',
                    'views' => '125K',
                    'isWatched' => false,
                    'category' => 'การเงิน',
                ],
                [
                    'id' => '2',
                    'title' => 'เคล็ดลับการลงทุนสำหรับมือใหม่',
                    'thumbnail' => '📈',
                    'duration' => '5:45',
                    'reward' => 10,
                    'platform' => 'youtube',
                    'views' => '89K',
                    'isWatched' => false,
                    'category' => 'การลงทุน',
                ],
                [
                    'id' => '3',
                    'title' => 'MLM ทำอย่างไรให้สำเร็จ',
                    'thumbnail' => '👥',
                    'duration' => '3:15',
                    'reward' => 7,
                    'platform' => 'tiktok',
                    'views' => '256K',
                    'isWatched' => false,
                    'category' => 'MLM',
                ],
                [
                    'id' => '4',
                    'title' => 'รีวิวสินค้าขายดี',
                    'thumbnail' => '🛒',
                    'duration' => '4:20',
                    'reward' => 8,
                    'platform' => 'facebook',
                    'views' => '67K',
                    'isWatched' => false,
                    'category' => 'รีวิว',
                ],
                [
                    'id' => '5',
                    'title' => 'Crypto 101 สำหรับผู้เริ่มต้น',
                    'thumbnail' => '🪙',
                    'duration' => '8:00',
                    'reward' => 15,
                    'platform' => 'youtube',
                    'views' => '432K',
                    'isWatched' => false,
                    'category' => 'Crypto',
                ],
                [
                    'id' => '6',
                    'title' => 'เทคนิคการขายออนไลน์',
                    'thumbnail' => '💰',
                    'duration' => '3:45',
                    'reward' => 6,
                    'platform' => 'tiktok',
                    'views' => '198K',
                    'isWatched' => false,
                    'category' => 'การขาย',
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $videos,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }
    }

    /**
     * ดึงรายได้จากการดูวิดีโอ
     */
    public function getVideoEarnings(): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเข้าสู่ระบบ',
            ], 401);
        }

        // ข้อมูลรายได้ตัวอย่าง
        return response()->json([
            'success' => true,
            'data' => [
                'totalEarned' => 125,
                'todayEarned' => 35,
                'videosWatched' => 6,
                'dailyGoal' => 10,
                'dailyBonus' => 50,
            ],
        ]);
    }

    /**
     * บันทึกการดูวิดีโอ
     */
    public function submitVideoWatch(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเข้าสู่ระบบ',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'video_id' => 'required|string',
            'watch_duration' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
            ], 422);
        }

        try {
            // ตัวอย่าง: ให้รางวัลจากการดูวิดีโอ
            $reward = rand(5, 15);

            // บันทึกลง DB (สามารถเพิ่มตารางได้ภายหลัง)
            // VideoWatch::create([...]);

            return response()->json([
                'success' => true,
                'message' => "ยินดีด้วย! คุณได้รับ ฿{$reward}",
                'data' => [
                    'reward' => $reward,
                    'newTotal' => 125 + $reward,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด',
            ], 500);
        }
    }

    // =====================================================
    // Web Session API (สำหรับเปิดหน้าเว็บจากแอพ)
    // =====================================================

    /**
     * สร้าง one-time token สำหรับเปิดหน้าเว็บพร้อม authentication
     *
     * ใช้สำหรับ: Wallet topup, Payment pages, Profile settings
     */
    public function generateWebSessionToken(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $redirectPath = $request->input('redirect_path', '/user/wallet/topup');

            // สร้าง one-time token
            $token = Str::random(64);
            $tokenHash = hash('sha256', $token);

            // บันทึกลง Cache (หมดอายุใน 5 นาที)
            $cacheKey = 'web_session_token:'.$tokenHash;
            \Cache::put($cacheKey, [
                'user_id' => $user->id,
                'redirect_path' => $redirectPath,
                'created_at' => now()->toISOString(),
                'ip' => $request->ip(),
            ], now()->addMinutes(5));

            // สร้าง URL
            $baseUrl = rtrim(config('app.url'), '/');
            $webUrl = $baseUrl.'/mobile-web-session?token='.$token;

            // ถ้ามี query params เพิ่มเติม (เช่น amount)
            if ($request->has('query_params')) {
                $queryParams = $request->input('query_params');
                if (is_array($queryParams)) {
                    $webUrl .= '&'.http_build_query($queryParams);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'สร้าง session token สำเร็จ',
                'data' => [
                    'url' => $webUrl,
                    'expires_in' => 300, // 5 นาที (วินาที)
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Generate Web Session Token Error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถสร้าง session token ได้',
            ], 500);
        }
    }

    // =====================================================
    // Seller Order Management (Mobile App)
    // =====================================================

    /**
     * ดึงรายการ Orders ที่ผู้ขายต้องจัดการ
     */
    public function getSellerOrders(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            // ตรวจสอบว่าเป็น seller หรือไม่
            if (! $user->is_seller && ! $user->hasRole('seller')) {
                return response()->json([
                    'success' => false,
                    'message' => 'คุณไม่มีสิทธิ์เข้าถึงส่วนนี้',
                ], 403);
            }

            $status = $request->get('status');
            $perPage = $request->get('per_page', 20);

            // ดึง orders ที่มีสินค้าของผู้ขาย
            $query = Order::with([
                'items' => function ($q) use ($user) {
                    $q->where('seller_id', $user->id)->with('product:id,name,image');
                },
                'user:id,name,avatar',
            ])
                ->whereHas('items', function ($q) use ($user) {
                    $q->where('seller_id', $user->id);
                })
                ->latest();

            // Filter by status
            if ($status && $status !== 'all') {
                $query->where('status', $status);
            }

            $orders = $query->paginate($perPage);

            // Statistics
            $stats = [
                'pending' => Order::pending()->whereHas('items', fn ($q) => $q->where('seller_id', $user->id))->count(),
                'processing' => Order::processing()->whereHas('items', fn ($q) => $q->where('seller_id', $user->id))->count(),
                'shipped' => Order::shipped()->whereHas('items', fn ($q) => $q->where('seller_id', $user->id))->count(),
                'completed' => Order::completed()->whereHas('items', fn ($q) => $q->where('seller_id', $user->id))->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'orders' => $orders->items(),
                    'pagination' => [
                        'current_page' => $orders->currentPage(),
                        'last_page' => $orders->lastPage(),
                        'per_page' => $orders->perPage(),
                        'total' => $orders->total(),
                    ],
                    'stats' => $stats,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Get Seller Orders Error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถดึงข้อมูล Orders ได้',
            ], 500);
        }
    }

    /**
     * ดูรายละเอียด Order สำหรับผู้ขาย
     */
    public function getSellerOrderDetail(int $orderId): JsonResponse
    {
        try {
            $user = Auth::user();

            $order = Order::with([
                'items' => function ($q) use ($user) {
                    $q->where('seller_id', $user->id)->with('product');
                },
                'user:id,name,email,phone,avatar',
                'shippingProviderRelation',
                'trackingHistory',
            ])
                ->whereHas('items', function ($q) use ($user) {
                    $q->where('seller_id', $user->id);
                })
                ->find($orderId);

            if (! $order) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบ Order',
                ], 404);
            }

            // คำนวณยอดสำหรับผู้ขาย
            $sellerItems = $order->items;
            $sellerTotal = $sellerItems->sum('total');
            $sellerEarning = $sellerItems->sum('seller_earning');

            return response()->json([
                'success' => true,
                'data' => [
                    'order' => [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'status' => $order->status,
                        'status_label' => $order->status_label,
                        'payment_status' => $order->payment_status,
                        'created_at' => $order->created_at->toISOString(),
                        'shipped_at' => $order->shipped_at?->toISOString(),
                        'delivered_at' => $order->delivered_at?->toISOString(),
                    ],
                    'customer' => [
                        'name' => $order->user->name ?? 'ลูกค้า',
                        'phone' => $order->shipping_address_snapshot['phone'] ?? null,
                        'avatar' => $order->user->avatar ?? null,
                    ],
                    'shipping' => $order->shipping_address_snapshot,
                    'items' => $sellerItems->map(fn ($item) => [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product_name ?? $item->product?->name,
                        'product_image' => $item->product?->image,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'total' => $item->total,
                        'status' => $item->status,
                    ]),
                    'seller_total' => $sellerTotal,
                    'seller_earning' => $sellerEarning,
                    'tracking' => [
                        'tracking_number' => $order->tracking_number,
                        'shipping_provider' => $order->shippingProviderRelation ? [
                            'id' => $order->shippingProviderRelation->id,
                            'name' => $order->shippingProviderRelation->name,
                            'logo' => $order->shippingProviderRelation->logo,
                        ] : null,
                        'tracking_url' => $order->tracking_url,
                        'estimated_delivery_at' => $order->estimated_delivery_at?->toISOString(),
                    ],
                    'tracking_history' => $order->trackingHistory->map(fn ($h) => [
                        'id' => $h->id,
                        'status' => $h->status,
                        'title' => $h->title,
                        'description' => $h->description,
                        'location' => $h->location,
                        'tracked_at' => $h->tracked_at->toISOString(),
                    ]),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Get Seller Order Detail Error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถดึงข้อมูล Order ได้',
            ], 500);
        }
    }

    /**
     * อัพเดท Tracking Number สำหรับ Order
     */
    public function updateOrderTracking(Request $request, int $orderId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'shipping_provider_id' => 'required|exists:shipping_providers,id',
            'tracking_number' => 'required|string|max:100',
            'estimated_delivery_at' => 'nullable|date',
        ], [
            'shipping_provider_id.required' => 'กรุณาเลือกบริษัทขนส่ง',
            'shipping_provider_id.exists' => 'ไม่พบบริษัทขนส่ง',
            'tracking_number.required' => 'กรุณากรอกหมายเลขพัสดุ',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = Auth::user();

            $order = Order::whereHas('items', function ($q) use ($user) {
                $q->where('seller_id', $user->id);
            })->find($orderId);

            if (! $order) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบ Order หรือคุณไม่มีสิทธิ์',
                ], 404);
            }

            DB::beginTransaction();

            // ดึงข้อมูล Shipping Provider
            $shippingProvider = \App\Models\ShippingProvider::find($request->shipping_provider_id);

            // อัพเดท Order
            $order->shipping_provider_id = $request->shipping_provider_id;
            $order->shipping_provider = $shippingProvider->name;
            $order->tracking_number = $request->tracking_number;
            $order->estimated_delivery_at = $request->estimated_delivery_at;
            $order->status = 'shipped';
            $order->shipped_at = now();
            $order->save();

            // อัพเดท items ของผู้ขาย
            OrderItem::where('order_id', $orderId)
                ->where('seller_id', $user->id)
                ->update(['status' => 'shipped']);

            // สร้าง tracking history
            \App\Models\OrderTrackingHistory::createEntry(
                $order,
                'shipped',
                'จัดส่งสินค้าแล้ว',
                [
                    'description' => "หมายเลขพัสดุ: {$request->tracking_number} ({$shippingProvider->name})",
                    'tracking_number' => $request->tracking_number,
                    'shipping_provider' => $shippingProvider->name,
                    'created_by_type' => 'seller',
                ]
            );

            DB::commit();

            // สร้าง tracking URL
            $trackingUrl = $shippingProvider->getTrackingLink($request->tracking_number);

            return response()->json([
                'success' => true,
                'message' => 'บันทึกข้อมูลการจัดส่งเรียบร้อยแล้ว',
                'data' => [
                    'tracking_number' => $request->tracking_number,
                    'shipping_provider' => $shippingProvider->name,
                    'tracking_url' => $trackingUrl,
                    'status' => 'shipped',
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Update Order Tracking Error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถบันทึกข้อมูลได้: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * เพิ่มประวัติ Tracking (สถานะการขนส่ง)
     */
    public function addOrderTrackingHistory(Request $request, int $orderId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:processing,shipped,in_transit,out_for_delivery,delivered',
            'description' => 'required|string|max:500',
            'location' => 'nullable|string|max:255',
        ], [
            'status.required' => 'กรุณาเลือกสถานะ',
            'status.in' => 'สถานะไม่ถูกต้อง',
            'description.required' => 'กรุณากรอกรายละเอียด',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = Auth::user();

            $order = Order::whereHas('items', function ($q) use ($user) {
                $q->where('seller_id', $user->id);
            })->find($orderId);

            if (! $order) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบ Order หรือคุณไม่มีสิทธิ์',
                ], 404);
            }

            // สถานะ label ภาษาไทย
            $statusLabels = [
                'processing' => 'กำลังเตรียมสินค้า',
                'shipped' => 'จัดส่งแล้ว',
                'in_transit' => 'อยู่ระหว่างขนส่ง',
                'out_for_delivery' => 'กำลังนำส่ง',
                'delivered' => 'ส่งถึงแล้ว',
            ];

            // สร้าง tracking history
            $history = \App\Models\OrderTrackingHistory::createEntry(
                $order,
                $request->status,
                $statusLabels[$request->status] ?? $request->status,
                [
                    'description' => $request->description,
                    'location' => $request->location,
                    'created_by_type' => 'seller',
                ]
            );

            // อัพเดทสถานะ Order ถ้าเป็น delivered
            if ($request->status === 'delivered') {
                $order->update([
                    'status' => 'delivered',
                    'delivered_at' => now(),
                ]);

                // อัพเดท items
                OrderItem::where('order_id', $orderId)
                    ->where('seller_id', $user->id)
                    ->update(['status' => 'delivered']);
            }

            return response()->json([
                'success' => true,
                'message' => 'บันทึกประวัติการจัดส่งเรียบร้อย',
                'data' => [
                    'id' => $history->id,
                    'status' => $history->status,
                    'title' => $history->title,
                    'description' => $history->description,
                    'location' => $history->location,
                    'tracked_at' => $history->tracked_at->toISOString(),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Add Order Tracking History Error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถบันทึกข้อมูลได้',
            ], 500);
        }
    }

    /**
     * ดึงรายการบริษัทขนส่ง
     */
    public function getShippingProviders(): JsonResponse
    {
        try {
            $providers = \App\Models\ShippingProvider::active()
                ->ordered()
                ->get()
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'code' => $p->code,
                    'name' => $p->name,
                    'name_en' => $p->name_en,
                    'logo' => $p->logo,
                    'hotline' => $p->hotline,
                ]);

            return response()->json([
                'success' => true,
                'data' => $providers,
            ]);
        } catch (\Exception $e) {
            \Log::error('Get Shipping Providers Error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถดึงข้อมูลบริษัทขนส่งได้',
            ], 500);
        }
    }
}
