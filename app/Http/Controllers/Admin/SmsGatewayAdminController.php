<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmsCheckerDevice;
use App\Models\SmsGatewayPricing;
use App\Models\SmsGatewaySubscription;
use App\Models\VendorStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * SMS Gateway Admin Controller
 *
 * จัดการ SMS Payment Gateway สำหรับ Admin Panel
 * - CRUD แพ็กเกจราคา
 * - ดู subscriptions ทั้งหมด
 * - ดูร้านค้าที่ใช้ระบบ
 * - สถิติรายได้
 */
class SmsGatewayAdminController extends Controller
{
    // ========================================
    // PRICING MANAGEMENT
    // ========================================

    /**
     * รายการแพ็กเกจราคา
     *
     * GET /admin/sms-gateway/pricing
     */
    public function pricing()
    {
        $plans = SmsGatewayPricing::orderBy('sort_order')
            ->orderBy('price')
            ->get();

        return view('admin.sms-gateway.pricing', compact('plans'));
    }

    /**
     * สร้างแพ็กเกจราคาใหม่
     *
     * POST /admin/sms-gateway/pricing
     */
    public function storePricing(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'plan_type' => 'required|in:monthly,yearly',
            'price' => 'required|numeric|min:0',
            'max_devices' => 'required|integer|min:1',
            'trial_days' => 'required|integer|min:0',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        SmsGatewayPricing::create([
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'plan_type' => $request->input('plan_type'),
            'price' => $request->input('price'),
            'max_devices' => $request->input('max_devices'),
            'trial_days' => $request->input('trial_days', 7),
            'is_active' => $request->boolean('is_active', true),
            'is_featured' => $request->boolean('is_featured', false),
            'sort_order' => $request->input('sort_order', 0),
            'features_json' => $request->input('features_json'),
        ]);

        return back()->with('success', 'สร้างแพ็กเกจราคาสำเร็จ');
    }

    /**
     * อัพเดทแพ็กเกจราคา
     *
     * PUT /admin/sms-gateway/pricing/{id}
     */
    public function updatePricing(Request $request, int $id)
    {
        $plan = SmsGatewayPricing::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'plan_type' => 'sometimes|in:monthly,yearly',
            'price' => 'sometimes|numeric|min:0',
            'max_devices' => 'sometimes|integer|min:1',
            'trial_days' => 'sometimes|integer|min:0',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $plan->update($request->only([
            'name', 'description', 'plan_type', 'price',
            'max_devices', 'trial_days', 'is_active', 'is_featured',
            'sort_order', 'features_json',
        ]));

        return back()->with('success', 'อัพเดทแพ็กเกจราคาสำเร็จ');
    }

    /**
     * ลบแพ็กเกจราคา
     *
     * DELETE /admin/sms-gateway/pricing/{id}
     */
    public function deletePricing(int $id)
    {
        $plan = SmsGatewayPricing::findOrFail($id);

        // เช็คว่ามี subscription ที่ใช้อยู่หรือไม่
        $activeCount = $plan->subscriptions()
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->count();

        if ($activeCount > 0) {
            return back()->with('error', "ไม่สามารถลบได้ มี subscription ที่ active อยู่ {$activeCount} รายการ");
        }

        $plan->delete();

        return back()->with('success', 'ลบแพ็กเกจราคาสำเร็จ');
    }

    // ========================================
    // SUBSCRIPTIONS MANAGEMENT
    // ========================================

    /**
     * รายการ subscriptions ทั้งหมด
     *
     * GET /admin/sms-gateway/subscriptions
     */
    public function subscriptions(Request $request)
    {
        $query = SmsGatewaySubscription::with(['store', 'user', 'pricing'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Filter by store
        if ($storeId = $request->input('store_id')) {
            $query->where('store_id', $storeId);
        }

        // Search
        if ($search = $request->input('search')) {
            $query->whereHas('store', function ($q) use ($search) {
                $q->where('store_name', 'like', "%{$search}%");
            });
        }

        $subscriptions = $query->paginate(20);

        return view('admin.sms-gateway.subscriptions', compact('subscriptions'));
    }

    /**
     * เปิด/ปิดสถานะ subscription
     *
     * POST /admin/sms-gateway/subscriptions/{id}/toggle
     */
    public function toggleSubscription(int $id)
    {
        $subscription = SmsGatewaySubscription::findOrFail($id);

        if ($subscription->status === 'active') {
            $subscription->cancel('Cancelled by admin');
        } else {
            $subscription->update([
                'status' => 'active',
                'cancelled_at' => null,
            ]);
        }

        return back()->with('success', 'อัพเดทสถานะ subscription สำเร็จ');
    }

    // ========================================
    // STORES + DEVICES OVERVIEW
    // ========================================

    /**
     * ร้านค้าที่ใช้ระบบ SMS Gateway
     *
     * GET /admin/sms-gateway/stores
     */
    public function stores(Request $request)
    {
        $query = VendorStore::query()
            ->with(['smsGatewaySubscription', 'smsCheckerDevices'])
            ->whereHas('smsCheckerDevices')
            ->orWhereHas('smsGatewaySubscriptions');

        if ($search = $request->input('search')) {
            $query->where('store_name', 'like', "%{$search}%");
        }

        $stores = $query->orderBy('store_name')
            ->paginate(20);

        return view('admin.sms-gateway.stores', compact('stores'));
    }

    // ========================================
    // REVENUE STATISTICS
    // ========================================

    /**
     * สถิติรายได้จาก SMS Gateway subscriptions
     *
     * GET /admin/sms-gateway/revenue
     */
    public function revenue(Request $request)
    {
        $period = $request->input('period', 'month');

        // สรุปรายได้
        $totalRevenue = SmsGatewaySubscription::where('payment_status', 'paid')
            ->sum('price');

        $monthlyRevenue = SmsGatewaySubscription::where('payment_status', 'paid')
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('price');

        $activeSubscriptions = SmsGatewaySubscription::where('status', 'active')
            ->where('expires_at', '>', now())
            ->count();

        $trialSubscriptions = SmsGatewaySubscription::where('status', 'trial')
            ->where('expires_at', '>', now())
            ->count();

        $totalDevices = SmsCheckerDevice::where('status', 'active')->count();
        $adminDevices = SmsCheckerDevice::adminDevices()->where('status', 'active')->count();
        $storeDevices = $totalDevices - $adminDevices;

        $expiringSoon = SmsGatewaySubscription::expiringSoon()->count();

        $stats = [
            'total_revenue' => $totalRevenue,
            'monthly_revenue' => $monthlyRevenue,
            'active_subscriptions' => $activeSubscriptions,
            'trial_subscriptions' => $trialSubscriptions,
            'total_devices' => $totalDevices,
            'admin_devices' => $adminDevices,
            'store_devices' => $storeDevices,
            'expiring_soon' => $expiringSoon,
        ];

        return view('admin.sms-gateway.revenue', compact('stats'));
    }
}
