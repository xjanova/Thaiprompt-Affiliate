<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FreshMarketCategory;
use App\Models\FreshMarketListing;
use App\Models\FreshMarketOrder;
use App\Models\FreshMarketSeller;
use App\Models\FreshMarketSetting;
use Illuminate\Http\Request;

/**
 * FreshMarketController - Admin Panel ตลาดสดไทยพร๊อม
 *
 * จัดการ: Dashboard, Settings, Categories, Sellers, Listings, Orders, Commissions
 */
class FreshMarketController extends Controller
{
    // ===== Dashboard =====

    /**
     * แดชบอร์ดภาพรวมตลาดสด
     */
    public function dashboard()
    {
        $stats = [
            'total_sellers' => FreshMarketSeller::count(),
            'active_sellers' => FreshMarketSeller::active()->count(),
            'total_listings' => FreshMarketListing::count(),
            'active_listings' => FreshMarketListing::active()->count(),
            'total_orders' => FreshMarketOrder::count(),
            'pending_orders' => FreshMarketOrder::pending()->count(),
            'completed_orders' => FreshMarketOrder::completed()->count(),
            'total_revenue' => FreshMarketOrder::completed()->sum('total_amount'),
            'total_platform_fees' => FreshMarketOrder::completed()->sum('platform_fee'),
            'total_categories' => FreshMarketCategory::count(),
        ];

        // ออเดอร์ล่าสุด
        $recentOrders = FreshMarketOrder::with(['buyer:id,name', 'seller:id,shop_name'])
            ->latest()
            ->limit(10)
            ->get();

        // ผู้ขายใหม่ล่าสุด
        $recentSellers = FreshMarketSeller::with('user:id,name')
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.fresh-market.dashboard', compact('stats', 'recentOrders', 'recentSellers'));
    }

    // ===== Settings =====

    /**
     * หน้าตั้งค่าระบบ
     */
    public function settings()
    {
        $settings = FreshMarketSetting::getSettings();

        return view('admin.fresh-market.settings', compact('settings'));
    }

    /**
     * บันทึกตั้งค่า
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
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
        ]);

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

        $settings = FreshMarketSetting::getSettings();
        $settings->update($validated);
        FreshMarketSetting::clearCache();

        return redirect()->route('admin.fresh-market.settings')
            ->with('success', 'บันทึกการตั้งค่าสำเร็จ');
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

        $validated['sort_order'] = FreshMarketCategory::max('sort_order') + 1;

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
     * ลบหมวดหมู่
     */
    public function destroyCategory(FreshMarketCategory $category)
    {
        if ($category->listings()->exists()) {
            return redirect()->route('admin.fresh-market.categories')
                ->with('error', 'ไม่สามารถลบหมวดหมู่ที่มีสินค้าได้');
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
        $order = $request->input('order', []);

        foreach ($order as $index => $id) {
            FreshMarketCategory::where('id', $id)->update(['sort_order' => $index + 1]);
        }

        return response()->json(['success' => true]);
    }

    // ===== Sellers =====

    /**
     * รายการผู้ขาย
     */
    public function sellers(Request $request)
    {
        $query = FreshMarketSeller::with('user:id,name,email')
            ->withCount('listings', 'orders');

        // กรอง
        if ($request->filled('status')) {
            match ($request->status) {
                'active' => $query->where('is_active', true)->where('is_suspended', false),
                'suspended' => $query->where('is_suspended', true),
                'unverified' => $query->where('is_verified', false),
                default => null,
            };
        }

        // ค้นหา
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('shop_name', 'LIKE', "%{$search}%")
                    ->orWhereHas('user', fn ($q2) => $q2->where('name', 'LIKE', "%{$search}%"));
            });
        }

        $sellers = $query->latest()->paginate(20);

        return view('admin.fresh-market.sellers', compact('sellers'));
    }

    /**
     * รายละเอียดผู้ขาย
     */
    public function showSeller(FreshMarketSeller $seller)
    {
        $seller->load(['user', 'listings' => fn ($q) => $q->latest()->limit(10), 'orders' => fn ($q) => $q->latest()->limit(10)]);

        return view('admin.fresh-market.seller-detail', compact('seller'));
    }

    /**
     * ยืนยันผู้ขาย
     */
    public function verifySeller(FreshMarketSeller $seller)
    {
        $seller->update(['is_verified' => true]);

        return redirect()->back()->with('success', 'ยืนยันผู้ขายสำเร็จ');
    }

    /**
     * ระงับผู้ขาย
     */
    public function suspendSeller(FreshMarketSeller $seller)
    {
        $seller->update(['is_suspended' => true, 'is_active' => false]);

        return redirect()->back()->with('success', 'ระงับผู้ขายสำเร็จ');
    }

    /**
     * เปิดใช้งานผู้ขาย
     */
    public function activateSeller(FreshMarketSeller $seller)
    {
        $seller->update(['is_suspended' => false, 'is_active' => true]);

        return redirect()->back()->with('success', 'เปิดใช้งานผู้ขายสำเร็จ');
    }

    // ===== Listings =====

    /**
     * รายการสินค้า
     */
    public function listings(Request $request)
    {
        $query = FreshMarketListing::with([
            'seller:id,shop_name',
            'category:id,name,icon',
        ]);

        // กรอง
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // ค้นหา
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $listings = $query->latest()->paginate(20);
        $categories = FreshMarketCategory::active()->orderBy('sort_order')->get();

        return view('admin.fresh-market.listings', compact('listings', 'categories'));
    }

    /**
     * รายละเอียดสินค้า
     */
    public function showListing(FreshMarketListing $listing)
    {
        $listing->load(['seller', 'category', 'orders' => fn ($q) => $q->latest()->limit(10)]);

        return view('admin.fresh-market.listing-detail', compact('listing'));
    }

    /**
     * อนุมัติสินค้า
     */
    public function approveListing(FreshMarketListing $listing)
    {
        $listing->update(['status' => 'active', 'is_available' => true]);

        return redirect()->back()->with('success', 'อนุมัติสินค้าสำเร็จ');
    }

    /**
     * ระงับสินค้า
     */
    public function suspendListing(FreshMarketListing $listing)
    {
        $listing->update(['status' => 'suspended', 'is_available' => false]);

        return redirect()->back()->with('success', 'ระงับสินค้าสำเร็จ');
    }

    // ===== Orders =====

    /**
     * รายการออเดอร์
     */
    public function orders(Request $request)
    {
        $query = FreshMarketOrder::with([
            'buyer:id,name',
            'seller:id,shop_name',
            'listing:id,title',
        ]);

        // กรอง
        if ($request->filled('status')) {
            $query->where('order_status', $request->status);
        }

        // ค้นหา
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'LIKE', "%{$search}%")
                    ->orWhereHas('buyer', fn ($q2) => $q2->where('name', 'LIKE', "%{$search}%"));
            });
        }

        $orders = $query->latest()->paginate(20);

        return view('admin.fresh-market.orders', compact('orders'));
    }

    /**
     * รายละเอียดออเดอร์
     */
    public function showOrder(FreshMarketOrder $order)
    {
        $order->load(['buyer', 'seller', 'listing', 'riderJob']);

        return view('admin.fresh-market.order-detail', compact('order'));
    }

    // ===== Commissions =====

    /**
     * รายงาน MLM + Cashback
     */
    public function commissions()
    {
        $stats = [
            'total_platform_fees' => FreshMarketOrder::completed()->sum('platform_fee'),
            'total_cashback' => FreshMarketOrder::where('cashback_processed', true)->sum('cashback_amount'),
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
     * ส่งข้อความทดสอบ
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

        // แสดง error ละเอียดเพื่อให้ admin debug ได้
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
}
