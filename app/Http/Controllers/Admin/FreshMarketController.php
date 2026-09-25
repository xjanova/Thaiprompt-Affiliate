<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\FreshMarketException;
use App\Http\Controllers\Controller;
use App\Models\FreshMarketCategory;
use App\Models\FreshMarketListing;
use App\Models\FreshMarketOrder;
use App\Models\FreshMarketSeller;
use App\Models\FreshMarketSetting;
use App\Models\PlatformTransaction;
use App\Models\Setting;
use App\Models\WalletDebt;
use App\Models\WalletTransaction;
use App\Services\FreshMarketOrderNotifier;
use App\Services\FreshMarketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * FreshMarketController - Admin Panel ตลาดสดไทยพร๊อม
 *
 * จัดการ: Dashboard, Settings, Categories, Sellers, Listings, Orders (ยกเลิก/คืนเงิน/ปิด/เรียกไรเดอร์ใหม่), Commissions
 * ทุก action ที่กระทบเงิน/สถานะ บันทึก audit (status_history ในออเดอร์ + activity log)
 */
class FreshMarketController extends Controller
{
    /**
     * ค่าตั้งระบบตลาดสดที่เก็บใน settings (key => [type, ค่าเริ่มต้น, rule])
     * ฟอร์มส่งมาเป็น market[<ชื่อหลังจุด>] เช่น market[pending_expiry_minutes]
     */
    public const MARKET_SETTINGS = [
        'pending_expiry_minutes' => ['integer', 30, 'integer|min:5|max:1440'],
        'auto_complete_hours' => ['integer', 24, 'integer|min:1|max:720'],
        'auto_approve_sellers' => ['boolean', true, 'boolean'],
        'rider_dispatch_on_accept' => ['boolean', false, 'boolean'],
        'referral_fee_amount' => ['float', 0, 'numeric|min:0|max:1000'],
        'line_basic_id' => ['string', '', 'string|max:50'],
        'default_line_stock' => ['integer', 10, 'integer|min:1|max:10000'],
        'max_seller_gp_debt' => ['float', 500, 'numeric|min:0|max:100000'],
    ];

    // ===== Dashboard =====

    /**
     * แดชบอร์ดภาพรวมตลาดสด
     */
    public function dashboard()
    {
        $stats = [
            'total_sellers' => FreshMarketSeller::count(),
            'active_sellers' => FreshMarketSeller::active()->count(),
            'unverified_sellers' => FreshMarketSeller::where('is_verified', false)->count(),
            'total_listings' => FreshMarketListing::count(),
            'active_listings' => FreshMarketListing::active()->count(),
            'total_orders' => FreshMarketOrder::count(),
            'pending_orders' => FreshMarketOrder::pending()->count(),
            'active_orders' => FreshMarketOrder::active()->count(),
            'delivery_failed_orders' => FreshMarketOrder::where('order_status', FreshMarketOrder::STATUS_DELIVERY_FAILED)->count(),
            'completed_orders' => FreshMarketOrder::completed()->count(),
            'total_revenue' => (float) FreshMarketOrder::completed()->sum('total_amount'),
            'total_platform_fees' => (float) FreshMarketOrder::completed()->sum('platform_fee'),
            'outstanding_gp_debt' => (float) WalletDebt::active()->where('source_type', FreshMarketService::DEBT_SOURCE_GP)->sum('remaining_amount'),
            'total_categories' => FreshMarketCategory::count(),
        ];

        $recentOrders = FreshMarketOrder::with(['buyer:id,name', 'seller:id,shop_name'])
            ->latest()
            ->limit(10)
            ->get();

        $recentSellers = FreshMarketSeller::with('user:id,name')
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.fresh-market.dashboard', compact('stats', 'recentOrders', 'recentSellers'));
    }

    // ===== Settings =====

    /**
     * หน้าตั้งค่าระบบ (ไม่ส่งค่า secret จริงไปที่ view — ส่งแค่ข้อความ mask)
     */
    public function settings()
    {
        $settings = FreshMarketSetting::getSettings();
        $lineSecretMasked = FreshMarketSetting::maskSecret($settings->line_channel_secret);
        $lineTokenMasked = FreshMarketSetting::maskSecret($settings->line_channel_access_token);
        $marketSettings = $this->currentMarketSettings();

        return view('admin.fresh-market.settings', compact('settings', 'lineSecretMasked', 'lineTokenMasked', 'marketSettings'));
    }

    /**
     * บันทึกตั้งค่า
     */
    public function updateSettings(Request $request)
    {
        $marketRules = [];
        foreach (self::MARKET_SETTINGS as $key => [$type, $default, $rule]) {
            $marketRules["market.{$key}"] = 'nullable|'.$rule;
        }

        $validated = $request->validate(array_merge([
            // LINE
            'line_channel_id' => 'nullable|string|max:50',
            'line_channel_secret' => 'nullable|string|max:100',
            'line_channel_access_token' => 'nullable|string|max:500',
            // AI Provider
            'ai_provider' => 'required|string|in:groq,openrouter,openai',
            'ai_model' => 'required|string|max:100',
            'use_global_ai_settings' => 'boolean',
            'ai_system_prompt' => 'nullable|string',
            // Bot Personality
            'bot_name' => 'nullable|string|max:50',
            'bot_personality' => 'nullable|string|max:1000',
            'bot_response_style' => 'nullable|string|in:friendly,formal,casual,funny',
            'bot_temperature' => 'nullable|numeric|min:0|max:1',
            // Greeting & Menu
            'greeting_message_template' => 'nullable|string|max:2000',
            'menu_label_buy' => 'nullable|string|max:20',
            'menu_label_sell' => 'nullable|string|max:20',
            'menu_label_rider' => 'nullable|string|max:20',
            'menu_label_chat_ai' => 'nullable|string|max:20',
            'menu_label_help' => 'nullable|string|max:20',
            'ai_enabled_in_idle' => 'boolean',
            // AI Scope
            'ai_scope_description' => 'nullable|string|max:2000',
            'ai_allowed_topics' => 'nullable|string',
            'ai_blocked_topics' => 'nullable|string',
            'ai_off_topic_message' => 'nullable|string|max:500',
            // AI Dynamic Buttons
            'ai_can_suggest_buttons' => 'boolean',
            'ai_max_buttons' => 'nullable|integer|min:1|max:13',
            // Data Access
            'ai_can_access_listings' => 'boolean',
            'ai_can_access_orders' => 'boolean',
            'ai_can_access_user_profile' => 'boolean',
            'ai_can_access_sellers' => 'boolean',
            'ai_can_access_pricing' => 'boolean',
            'ai_max_context_messages' => 'nullable|integer|min:1|max:50',
            // ค่าธรรมเนียม
            'platform_fee_percentage' => 'required|numeric|min:0|max:50',
            'monthly_subscription_fee' => 'required|numeric|min:0',
            'fee_mode' => 'required|string|in:percentage,subscription,both',
            'free_trial_days' => 'required|integer|min:0',
            'max_listings_free' => 'required|integer|min:1',
            'max_listings_subscribed' => 'required|integer|min:0',
            'default_search_radius_km' => 'required|numeric|min:1|max:100',
            'max_search_radius_km' => 'required|numeric|min:1|max:200',
            'escrow_enabled' => 'boolean',
            'cod_enabled' => 'boolean',
            'rider_enabled' => 'boolean',
            'mlm_commission_enabled' => 'boolean',
            'cashback_enabled' => 'boolean',
            'line_flex_primary_color' => 'nullable|string|max:10',
            'brand_name' => 'required|string|max:100',
            'welcome_message' => 'nullable|string',
            'market' => 'nullable|array',
        ], $marketRules));

        // credentials แยกบันทึก (guarded) — เว้นว่าง = ใช้ค่าเดิม
        $secret = $validated['line_channel_secret'] ?? null;
        $token = $validated['line_channel_access_token'] ?? null;
        unset($validated['line_channel_secret'], $validated['line_channel_access_token']);

        // รวม menu_label_* เป็น JSON menu_button_labels
        $menuLabels = [];
        foreach (['buy', 'sell', 'rider', 'chat_ai', 'help'] as $key) {
            $fieldName = "menu_label_{$key}";
            if (! empty($validated[$fieldName])) {
                $menuLabels[$key] = $validated[$fieldName];
            }
            unset($validated[$fieldName]);
        }
        if (! empty($menuLabels)) {
            $validated['menu_button_labels'] = $menuLabels;
        }

        // แปลง JSON string → array สำหรับ topics
        foreach (['ai_allowed_topics', 'ai_blocked_topics'] as $field) {
            if (isset($validated[$field]) && is_string($validated[$field])) {
                $decoded = json_decode($validated[$field], true);
                $validated[$field] = is_array($decoded) ? $decoded : null;
            }
        }

        $market = $validated['market'] ?? [];
        unset($validated['market']);

        $settings = FreshMarketSetting::getSettings();
        $settings->update($validated);
        $credentialsChanged = $settings->setLineCredentials($secret, $token);
        FreshMarketSetting::clearCache();

        // ค่าตั้งเพิ่มเติม (เฉพาะช่องที่ส่งมา)
        foreach (self::MARKET_SETTINGS as $key => [$type, $default, $rule]) {
            if (! array_key_exists($key, $market) || $market[$key] === null) {
                continue;
            }

            $value = match ($type) {
                'boolean' => filter_var($market[$key], FILTER_VALIDATE_BOOLEAN) ? '1' : '0',
                'integer' => (string) (int) $market[$key],
                'float' => (string) round((float) $market[$key], 2),
                default => ltrim(trim((string) $market[$key]), '@'),
            };

            Setting::set("fresh_market.{$key}", $value, $type, 'fresh_market');
        }

        activity()
            ->causedBy(auth()->user())
            ->withProperties([
                'fields' => array_keys($validated),
                'market' => array_keys($market),
                'line_credentials_changed' => $credentialsChanged,
            ])
            ->log('fresh_market_settings_updated');

        return redirect()->route('admin.fresh-market.settings')
            ->with('success', 'บันทึกการตั้งค่าสำเร็จ');
    }

    /**
     * ค่าตั้งเพิ่มเติมปัจจุบัน
     *
     * @return array<string, mixed>
     */
    protected function currentMarketSettings(): array
    {
        $values = [];

        foreach (self::MARKET_SETTINGS as $key => [$type, $default]) {
            $values[$key] = Setting::get("fresh_market.{$key}", $default);
        }

        return $values;
    }

    // ===== Categories =====

    /**
     * จัดการหมวดหมู่
     */
    public function categories()
    {
        $categories = FreshMarketCategory::withCount('listings')
            ->orderBy('sort_order')
            ->get();

        return view('admin.fresh-market.categories', compact('categories'));
    }

    /**
     * สร้างหมวดหมู่ใหม่
     */
    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'icon' => 'nullable|string|max:10',
            'description' => 'nullable|string|max:500',
            'parent_id' => 'nullable|exists:fresh_market_categories,id',
            'is_active' => 'boolean',
        ]);

        $validated['sort_order'] = (int) FreshMarketCategory::max('sort_order') + 1;

        FreshMarketCategory::create($validated);

        return redirect()->route('admin.fresh-market.categories')
            ->with('success', 'สร้างหมวดหมู่สำเร็จ');
    }

    /**
     * อัพเดทหมวดหมู่
     */
    public function updateCategory(Request $request, FreshMarketCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'icon' => 'nullable|string|max:10',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $category->update($validated);

        return redirect()->route('admin.fresh-market.categories')
            ->with('success', 'อัพเดทหมวดหมู่สำเร็จ');
    }

    /**
     * เปิด/ปิดหมวดหมู่
     */
    public function toggleCategory(FreshMarketCategory $category): RedirectResponse
    {
        $category->update(['is_active' => ! $category->is_active]);

        return redirect()->route('admin.fresh-market.categories')
            ->with('success', $category->is_active ? 'เปิดใช้งานหมวดหมู่แล้ว' : 'ปิดใช้งานหมวดหมู่แล้ว');
    }

    /**
     * ลบหมวดหมู่
     */
    public function destroyCategory(FreshMarketCategory $category)
    {
        if ($category->listings()->exists()) {
            return redirect()->route('admin.fresh-market.categories')
                ->with('error', 'ไม่สามารถลบหมวดหมู่ที่มีสินค้าได้');
        }

        if ($category->children()->exists()) {
            return redirect()->route('admin.fresh-market.categories')
                ->with('error', 'ไม่สามารถลบหมวดหมู่ที่มีหมวดหมู่ย่อยได้');
        }

        $category->delete();

        return redirect()->route('admin.fresh-market.categories')
            ->with('success', 'ลบหมวดหมู่สำเร็จ');
    }

    /**
     * เรียงลำดับหมวดหมู่ (SortableJS)
     */
    public function reorderCategories(Request $request)
    {
        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer',
        ]);

        foreach ($validated['order'] as $index => $id) {
            FreshMarketCategory::where('id', $id)->update(['sort_order' => $index + 1]);
        }

        return response()->json(['success' => true, 'message' => 'บันทึกลำดับหมวดหมู่แล้ว']);
    }

    // ===== Sellers =====

    /**
     * รายการผู้ขาย
     */
    public function sellers(Request $request)
    {
        $query = FreshMarketSeller::with('user:id,name,email')
            ->withCount('listings', 'orders');

        if ($request->filled('status')) {
            match ($request->status) {
                'active' => $query->where('is_active', true)->where('is_suspended', false),
                'suspended' => $query->where('is_suspended', true),
                'unverified' => $query->where('is_verified', false),
                'verified' => $query->where('is_verified', true),
                default => null,
            };
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('shop_name', 'LIKE', "%{$search}%")
                    ->orWhere('phone', 'LIKE', "%{$search}%")
                    ->orWhereHas('user', fn ($q2) => $q2->where('name', 'LIKE', "%{$search}%"));
            });
        }

        $sellers = $query->latest()->paginate(20)->withQueryString();

        return view('admin.fresh-market.sellers', compact('sellers'));
    }

    /**
     * รายละเอียดผู้ขาย
     */
    public function showSeller(FreshMarketSeller $seller)
    {
        $seller->load([
            'user',
            'listings' => fn ($q) => $q->latest()->limit(20),
            'orders' => fn ($q) => $q->with('buyer:id,name')->latest()->limit(20),
        ]);

        $orderStats = FreshMarketOrder::where('seller_id', $seller->id)
            ->selectRaw('order_status, COUNT(*) as total')
            ->groupBy('order_status')
            ->pluck('total', 'order_status')
            ->map(fn ($v) => (int) $v)
            ->all();

        $gpDebt = $seller->outstandingGpDebt();
        $gpDebts = WalletDebt::forUser((int) $seller->user_id)
            ->where('source_type', FreshMarketService::DEBT_SOURCE_GP)
            ->latest()
            ->limit(20)
            ->get();

        $payoutTotal = (float) WalletTransaction::where('reference_type', FreshMarketService::REF_PAYOUT)
            ->where('user_id', $seller->user_id)
            ->sum('amount');

        return view('admin.fresh-market.seller-detail', compact('seller', 'orderStats', 'gpDebt', 'gpDebts', 'payoutTotal'));
    }

    /**
     * ยืนยันผู้ขาย
     */
    public function verifySeller(FreshMarketSeller $seller)
    {
        $seller->update(['is_verified' => true]);
        $this->auditSeller($seller, 'fresh_market_seller_verified');

        app(FreshMarketOrderNotifier::class)->sellerAccountEvent(
            $seller,
            'ร้านของคุณได้รับการยืนยันแล้ว',
            'สินค้าของร้าน '.$seller->shop_name.' แสดงให้ผู้ซื้อเห็นแล้ว'
        );

        return redirect()->back()->with('success', 'ยืนยันผู้ขายสำเร็จ');
    }

    /**
     * ระงับผู้ขาย (สินค้าจะถูกซ่อนจากผู้ซื้อทันที)
     */
    public function suspendSeller(Request $request, FreshMarketSeller $seller)
    {
        $reason = trim((string) $request->input('reason', ''));

        $seller->update(['is_suspended' => true, 'is_active' => false]);
        $this->auditSeller($seller, 'fresh_market_seller_suspended', ['reason' => $reason]);

        app(FreshMarketOrderNotifier::class)->sellerAccountEvent(
            $seller,
            'ร้านของคุณถูกระงับชั่วคราว',
            $reason !== '' ? 'เหตุผล: '.$reason : 'กรุณาติดต่อแอดมินเพื่อสอบถามรายละเอียด'
        );

        return redirect()->back()->with('success', 'ระงับผู้ขายสำเร็จ');
    }

    /**
     * เปิดใช้งานผู้ขาย
     */
    public function activateSeller(FreshMarketSeller $seller)
    {
        $seller->update(['is_suspended' => false, 'is_active' => true]);
        $this->auditSeller($seller, 'fresh_market_seller_activated');

        app(FreshMarketOrderNotifier::class)->sellerAccountEvent(
            $seller,
            'ร้านของคุณเปิดใช้งานแล้ว',
            'ร้าน '.$seller->shop_name.' กลับมาขายได้ตามปกติ'
        );

        return redirect()->back()->with('success', 'เปิดใช้งานผู้ขายสำเร็จ');
    }

    // ===== Listings =====

    /**
     * รายการสินค้า
     */
    public function listings(Request $request)
    {
        $query = FreshMarketListing::with([
            'seller:id,shop_name,is_verified,is_suspended',
            'category:id,name,icon',
        ]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('seller_id')) {
            $query->where('seller_id', $request->integer('seller_id'));
        }

        if ($request->filled('search')) {
            $query->search((string) $request->search);
        }

        $listings = $query->latest()->paginate(20)->withQueryString();
        $categories = FreshMarketCategory::active()->orderBy('sort_order')->get();

        return view('admin.fresh-market.listings', compact('listings', 'categories'));
    }

    /**
     * รายละเอียดสินค้า
     */
    public function showListing(FreshMarketListing $listing)
    {
        $listing->load([
            'seller.user',
            'category',
            'orders' => fn ($q) => $q->with('buyer:id,name')->latest()->limit(20),
        ]);

        $isVisibleToBuyers = $listing->isAvailableForPurchase() && $listing->sellerIsVisible();

        return view('admin.fresh-market.listing-detail', compact('listing', 'isVisibleToBuyers'));
    }

    /**
     * อนุมัติ/เปิดขายสินค้าที่ถูกระงับ (ของหมด → sold_out)
     */
    public function approveListing(FreshMarketListing $listing)
    {
        $hasStock = (int) $listing->quantity_available > 0;

        $listing->update([
            'status' => $hasStock ? 'active' : 'sold_out',
            'is_available' => $hasStock,
        ]);

        activity()->performedOn($listing)->causedBy(auth()->user())->log('fresh_market_listing_approved');

        return redirect()->back()->with('success', $hasStock ? 'อนุมัติสินค้าสำเร็จ' : 'อนุมัติแล้ว แต่สินค้าหมดสต็อก (รอร้านเติมของ)');
    }

    /**
     * ระงับสินค้า
     */
    public function suspendListing(Request $request, FreshMarketListing $listing)
    {
        $listing->update(['status' => 'suspended', 'is_available' => false]);

        activity()
            ->performedOn($listing)
            ->causedBy(auth()->user())
            ->withProperties(['reason' => (string) $request->input('reason', '')])
            ->log('fresh_market_listing_suspended');

        return redirect()->back()->with('success', 'ระงับสินค้าสำเร็จ');
    }

    // ===== Orders =====

    /**
     * รายการออเดอร์ (กรอง: status, payment_status, delivery_type, search)
     */
    public function orders(Request $request)
    {
        $query = FreshMarketOrder::with([
            'buyer:id,name',
            'seller:id,shop_name',
            'listing:id,title',
        ]);

        $query->statusFilter($request->get('status'));

        if ($request->filled('payment_status')) {
            $query->where('payment_status', (string) $request->payment_status);
        }

        if ($request->filled('delivery_type')) {
            $query->where('delivery_type', (string) $request->delivery_type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'LIKE', "%{$search}%")
                    ->orWhereHas('buyer', fn ($q2) => $q2->where('name', 'LIKE', "%{$search}%"))
                    ->orWhereHas('seller', fn ($q2) => $q2->where('shop_name', 'LIKE', "%{$search}%"));
            });
        }

        $orders = $query->latest()->paginate(20)->withQueryString();
        $statuses = collect(FreshMarketOrder::STATUSES)
            ->mapWithKeys(fn ($s) => [$s => FreshMarketOrder::statusLabel($s)])
            ->all();

        return view('admin.fresh-market.orders', compact('orders', 'statuses'));
    }

    /**
     * รายละเอียดออเดอร์ + ประวัติเงินทั้งหมดที่เกี่ยวข้อง
     */
    public function showOrder(FreshMarketOrder $order)
    {
        $order->load(['buyer', 'seller.user', 'listing', 'riderJob.rider']);

        $allowedActions = $order->allowedActions('admin');
        $canRedispatch = $order->delivery_type === 'rider'
            && in_array($order->order_status, [FreshMarketOrder::STATUS_READY, FreshMarketOrder::STATUS_DELIVERY_FAILED], true);

        $walletTransactions = WalletTransaction::whereIn('reference_type', [
            FreshMarketService::REF_PAYMENT,
            FreshMarketService::REF_REFUND,
            FreshMarketService::REF_PAYOUT,
            FreshMarketService::REF_CASHBACK,
            FreshMarketService::REF_COD_GP,
        ])
            ->where('reference_id', $order->id)
            ->orderBy('id')
            ->get();

        $platformTransactions = PlatformTransaction::where('source_type', FreshMarketOrder::class)
            ->where('source_id', $order->id)
            ->orderBy('id')
            ->get();

        $gpDebt = WalletDebt::where('source_type', FreshMarketService::DEBT_SOURCE_GP)
            ->where('source_id', $order->id)
            ->first();

        $history = $order->status_history ?? [];

        return view('admin.fresh-market.order-detail', compact(
            'order', 'allowedActions', 'canRedispatch', 'walletTransactions', 'platformTransactions', 'gpDebt', 'history'
        ));
    }

    /**
     * แอดมินยกเลิกออเดอร์ (+ คืนเงินเฉพาะที่เก็บมาแล้ว) — ต้องมีเหตุผล
     */
    public function cancelOrder(Request $request, FreshMarketOrder $order): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|min:3|max:500',
        ], [
            'reason.required' => 'กรุณาระบุเหตุผลที่ยกเลิก',
        ]);

        return $this->runAdminOrderAction($order, 'cancel', function () use ($order, $validated) {
            $cancelled = (new FreshMarketService)->cancelOrder($order, $validated['reason'], 'admin', auth()->user());
            $refund = (float) $cancelled->refunded_amount;

            return $refund > 0
                ? 'ยกเลิกออเดอร์และคืนเงิน ฿'.number_format($refund, 2).' เข้า Wallet ผู้ซื้อแล้ว'
                : 'ยกเลิกออเดอร์แล้ว (ไม่มีเงินที่ต้องคืน)';
        }, $validated['reason']);
    }

    /**
     * แอดมินปิดออเดอร์แทนผู้ซื้อ (ปล่อยเงินให้ร้าน)
     */
    public function completeOrder(Request $request, FreshMarketOrder $order): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        return $this->runAdminOrderAction($order, 'complete', function () use ($order) {
            (new FreshMarketService)->completeOrder($order, 'admin', auth()->user());

            return 'ปิดออเดอร์และโอนเงินให้ร้านเรียบร้อยแล้ว';
        }, $validated['reason'] ?? null);
    }

    /**
     * เรียกไรเดอร์ใหม่ (ออเดอร์พร้อมส่งที่ยังไม่มีไรเดอร์ / ส่งไม่สำเร็จ)
     */
    public function redispatchRider(FreshMarketOrder $order): RedirectResponse
    {
        return $this->runAdminOrderAction($order, 'redispatch', function () use ($order) {
            (new FreshMarketService)->redispatchRider($order, auth()->user());

            return 'สร้างงานไรเดอร์ใหม่และแจ้งไรเดอร์ใกล้ร้านแล้ว';
        });
    }

    // ===== Commissions =====

    /**
     * รายงาน GP + แคชแบ็ค + ค่าแนะนำ
     */
    public function commissions()
    {
        $stats = [
            'total_platform_fees' => (float) FreshMarketOrder::completed()->sum('platform_fee'),
            'total_seller_earnings' => (float) FreshMarketOrder::completed()->sum('seller_earning'),
            'total_cashback' => (float) FreshMarketOrder::where('cashback_processed', true)->sum('cashback_amount'),
            'total_referral_fees' => (float) PlatformTransaction::where('sub_type', FreshMarketService::PLATFORM_REFERRAL)->sum('amount'),
            'outstanding_gp_debt' => (float) WalletDebt::active()->where('source_type', FreshMarketService::DEBT_SOURCE_GP)->sum('remaining_amount'),
            'total_refunded' => (float) FreshMarketOrder::sum('refunded_amount'),
            // คงคีย์เดิมไว้ให้ view เก่า (เป็นจำนวนออเดอร์ ไม่ใช่เงิน)
            'total_mlm_processed' => FreshMarketOrder::where('mlm_commission_processed', true)->count(),
        ];

        $recentOrders = FreshMarketOrder::completed()
            ->with(['buyer:id,name', 'seller:id,shop_name'])
            ->latest('completed_at')
            ->limit(20)
            ->get();

        return view('admin.fresh-market.commissions', compact('stats', 'recentOrders'));
    }

    // ===== LINE Bot Test =====

    /**
     * หน้าทดสอบ LINE Bot
     */
    public function testLine()
    {
        $settings = FreshMarketSetting::getSettings();

        return view('admin.fresh-market.test-line', compact('settings'));
    }

    /**
     * ส่งข้อความทดสอบ (แอดมินกดเอง — ใช้ push 1 ครั้ง)
     */
    public function sendTestLine(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|string',
            'message' => 'required|string',
        ]);

        $lineService = new \App\Services\FreshMarketLineService;
        $result = $lineService->sendText($validated['user_id'], $validated['message']);

        if ($result) {
            return redirect()->back()->with('success', 'ส่งข้อความสำเร็จ!');
        }

        $error = $lineService->getLastError();

        return redirect()->back()->with(
            'error',
            'ส่งข้อความล้มเหลว: '.($error ?: 'ไม่ทราบสาเหตุ กรุณาตรวจสอบ log')
        );
    }

    /**
     * ตรวจสอบการเชื่อมต่อ LINE OA (AJAX)
     */
    public function verifyLineConnection()
    {
        $lineService = new \App\Services\FreshMarketLineService;
        $result = $lineService->verifyToken();

        return response()->json($result);
    }

    // ===== Helpers =====

    /**
     * รัน action ของแอดมินกับออเดอร์ + audit log
     */
    protected function runAdminOrderAction(FreshMarketOrder $order, string $action, callable $callback, ?string $reason = null): RedirectResponse
    {
        try {
            $message = $callback();

            activity()
                ->performedOn($order)
                ->causedBy(auth()->user())
                ->withProperties(['action' => $action, 'reason' => $reason])
                ->log('fresh_market_order_admin_'.$action);

            return redirect()->route('admin.fresh-market.orders.show', $order)->with('success', $message);
        } catch (FreshMarketException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('FreshMarket admin: ทำรายการออเดอร์ล้มเหลว', [
                'order_id' => $order->id,
                'action' => $action,
                'admin_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด กรุณาตรวจสอบ log แล้วลองใหม่');
        }
    }

    /**
     * บันทึก audit การจัดการร้าน
     */
    protected function auditSeller(FreshMarketSeller $seller, string $event, array $properties = []): void
    {
        activity()->performedOn($seller)->causedBy(auth()->user())->withProperties($properties)->log($event);
    }
}
