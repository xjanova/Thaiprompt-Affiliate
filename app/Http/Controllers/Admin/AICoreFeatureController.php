<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AICoreFeature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * AI Core Feature Controller
 *
 * จัดการ AI Features ทั้ง 8 กลุ่ม
 * CRUD operations สำหรับ features
 */
class AICoreFeatureController extends Controller
{
    /**
     * แสดงรายการ Features ทั้งหมด
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $features = AICoreFeature::withCount(['featureAccess', 'usageLogs'])
            ->orderBy('display_order')
            ->orderBy('category')
            ->paginate(20);

        return view('admin.ai-core.features.index', compact('features'));
    }

    /**
     * แสดงฟอร์มสร้าง Feature ใหม่
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $categories = AICoreFeature::distinct()->pluck('category')->sort();
        $quotaTypes = ['none', 'count', 'usage', 'duration', 'token'];
        $pricingTiers = ['free', 'basic', 'pro', 'enterprise'];
        $resetPeriods = ['daily', 'weekly', 'monthly', 'yearly', 'never'];

        return view('admin.ai-core.features.create', compact(
            'categories',
            'quotaTypes',
            'pricingTiers',
            'resetPeriods'
        ));
    }

    /**
     * บันทึก Feature ใหม่
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'feature_key' => 'required|string|max:100|unique:ai_core_features,feature_key',
            'feature_name' => 'required|string|max:255',
            'feature_name_en' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string|max:50',
            'icon' => 'nullable|string|max:100',
            'display_order' => 'required|integer|min:0',
            'is_enabled' => 'boolean',
            'is_visible' => 'boolean',
            'requires_approval' => 'boolean',
            'quota_type' => 'required|in:none,count,usage,duration,token',
            'default_quota_limit' => 'nullable|integer|min:0',
            'quota_reset_period' => 'nullable|in:daily,weekly,monthly,yearly,never',
            'pricing_tier' => 'nullable|in:free,basic,pro,enterprise',
            'base_price' => 'nullable|numeric|min:0',
            'usage_price' => 'nullable|numeric|min:0',
            'allowed_roles' => 'nullable|array',
            'required_permissions' => 'nullable|array',
            'provider_name' => 'nullable|string|max:255',
            'provider_version' => 'nullable|string|max:50',
        ]);

        try {
            $feature = AICoreFeature::create($validated);

            return redirect()
                ->route('admin.ai-core.features.index')
                ->with('success', 'สร้าง Feature สำเร็จ: ' . $feature->feature_name);
        } catch (\Exception $e) {
            Log::error('AI Core: สร้าง feature ล้มเหลว', [
                'error' => $e->getMessage(),
                'data' => $validated,
            ]);

            return back()
                ->withInput()
                ->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * แสดงรายละเอียด Feature
     *
     * @param AICoreFeature $feature
     * @return \Illuminate\View\View
     */
    public function show(AICoreFeature $feature)
    {
        $feature->load([
            'featureAccess' => function ($query) {
                $query->with('tenant')->latest();
            },
            'usageLogs' => function ($query) {
                $query->latest()->limit(50);
            },
            'quotas',
            'schedules',
            'alerts' => function ($query) {
                $query->unresolved()->latest();
            },
        ]);

        // สถิติการใช้งาน
        $stats = [
            'total_access' => $feature->featureAccess()->count(),
            'active_access' => $feature->featureAccess()->where('is_enabled', true)->where('status', 'approved')->count(),
            'total_usage' => $feature->usageLogs()->count(),
            'usage_today' => $feature->usageLogs()->whereDate('created_at', today())->count(),
            'usage_month' => $feature->usageLogs()->whereMonth('created_at', now()->month)->count(),
            'success_rate' => $this->calculateSuccessRate($feature),
        ];

        return view('admin.ai-core.features.show', compact('feature', 'stats'));
    }

    /**
     * แสดงฟอร์มแก้ไข Feature
     *
     * @param AICoreFeature $feature
     * @return \Illuminate\View\View
     */
    public function edit(AICoreFeature $feature)
    {
        $categories = AICoreFeature::distinct()->pluck('category')->sort();
        $quotaTypes = ['none', 'count', 'usage', 'duration', 'token'];
        $pricingTiers = ['free', 'basic', 'pro', 'enterprise'];
        $resetPeriods = ['daily', 'weekly', 'monthly', 'yearly', 'never'];

        return view('admin.ai-core.features.edit', compact(
            'feature',
            'categories',
            'quotaTypes',
            'pricingTiers',
            'resetPeriods'
        ));
    }

    /**
     * อัปเดต Feature
     *
     * @param Request $request
     * @param AICoreFeature $feature
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, AICoreFeature $feature)
    {
        $validated = $request->validate([
            'feature_key' => 'required|string|max:100|unique:ai_core_features,feature_key,' . $feature->id,
            'feature_name' => 'required|string|max:255',
            'feature_name_en' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string|max:50',
            'icon' => 'nullable|string|max:100',
            'display_order' => 'required|integer|min:0',
            'is_enabled' => 'boolean',
            'is_visible' => 'boolean',
            'requires_approval' => 'boolean',
            'quota_type' => 'required|in:none,count,usage,duration,token',
            'default_quota_limit' => 'nullable|integer|min:0',
            'quota_reset_period' => 'nullable|in:daily,weekly,monthly,yearly,never',
            'pricing_tier' => 'nullable|in:free,basic,pro,enterprise',
            'base_price' => 'nullable|numeric|min:0',
            'usage_price' => 'nullable|numeric|min:0',
            'allowed_roles' => 'nullable|array',
            'required_permissions' => 'nullable|array',
            'provider_name' => 'nullable|string|max:255',
            'provider_version' => 'nullable|string|max:50',
        ]);

        try {
            $feature->update($validated);

            return redirect()
                ->route('admin.ai-core.features.show', $feature)
                ->with('success', 'อัปเดต Feature สำเร็จ');
        } catch (\Exception $e) {
            Log::error('AI Core: อัปเดต feature ล้มเหลว', [
                'feature_id' => $feature->id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * ลบ Feature
     *
     * @param AICoreFeature $feature
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(AICoreFeature $feature)
    {
        try {
            // ตรวจสอบว่ามีการใช้งานอยู่หรือไม่
            $activeAccess = $feature->featureAccess()->where('is_enabled', true)->count();

            if ($activeAccess > 0) {
                return back()->with('error', 'ไม่สามารถลบ Feature ได้ เนื่องจากมี Tenant ที่กำลังใช้งานอยู่ ' . $activeAccess . ' รายการ');
            }

            $featureName = $feature->feature_name;
            $feature->delete();

            return redirect()
                ->route('admin.ai-core.features.index')
                ->with('success', 'ลบ Feature สำเร็จ: ' . $featureName);
        } catch (\Exception $e) {
            Log::error('AI Core: ลบ feature ล้มเหลว', [
                'feature_id' => $feature->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * เปิด/ปิดใช้งาน Feature
     *
     * @param AICoreFeature $feature
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggle(AICoreFeature $feature)
    {
        try {
            $feature->update(['is_enabled' => !$feature->is_enabled]);

            return response()->json([
                'success' => true,
                'message' => $feature->is_enabled ? 'เปิดใช้งาน Feature แล้ว' : 'ปิดใช้งาน Feature แล้ว',
                'is_enabled' => $feature->is_enabled,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * คำนวณอัตราความสำเร็จ
     *
     * @param AICoreFeature $feature
     * @return float
     */
    private function calculateSuccessRate(AICoreFeature $feature): float
    {
        $total = $feature->usageLogs()->count();

        if ($total === 0) {
            return 0;
        }

        $success = $feature->usageLogs()->where('status', 'success')->count();

        return round(($success / $total) * 100, 2);
    }
}
