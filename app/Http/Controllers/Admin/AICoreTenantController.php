<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AICoreTenant;
use App\Models\AICoreFeature;
use App\Services\AICoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * AI Core Tenant Controller
 *
 * จัดการ Tenants และ Feature Access
 * Multi-tenancy management
 */
class AICoreTenantController extends Controller
{
    /**
     * @var AICoreService
     */
    protected $aiCoreService;

    /**
     * Constructor
     */
    public function __construct(AICoreService $aiCoreService)
    {
        $this->aiCoreService = $aiCoreService;
    }

    /**
     * แสดงรายการ Tenants ทั้งหมด
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $tenants = AICoreTenant::with('user')
            ->withCount(['featureAccess', 'usageLogs'])
            ->latest()
            ->paginate(20);

        return view('admin.ai-core.tenants.index', compact('tenants'));
    }

    /**
     * แสดงฟอร์มสร้าง Tenant ใหม่
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $tenantTypes = ['user', 'organization', 'reseller'];
        $statuses = ['active', 'suspended', 'trial', 'expired'];
        $subscriptionTiers = ['free', 'basic', 'pro', 'enterprise'];

        return view('admin.ai-core.tenants.create', compact(
            'tenantTypes',
            'statuses',
            'subscriptionTiers'
        ));
    }

    /**
     * บันทึก Tenant ใหม่
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'tenant_key' => 'required|string|max:100|unique:ai_core_tenants,tenant_key',
            'tenant_name' => 'required|string|max:255',
            'tenant_type' => 'required|in:user,organization,reseller',
            'status' => 'required|in:active,suspended,trial,expired',
            'is_enabled' => 'boolean',
            'subscription_tier' => 'nullable|in:free,basic,pro,enterprise',
            'subscription_starts_at' => 'nullable|date',
            'subscription_ends_at' => 'nullable|date|after:subscription_starts_at',
            'auto_renew' => 'boolean',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'billing_address' => 'nullable|string',
            'tax_id' => 'nullable|string|max:50',
        ]);

        try {
            $result = $this->aiCoreService->createTenant(
                $validated['user_id'] ?? null,
                $validated
            );

            if ($result['success']) {
                return redirect()
                    ->route('admin.ai-core.tenants.show', $result['data'])
                    ->with('success', 'สร้าง Tenant สำเร็จ');
            }

            return back()
                ->withInput()
                ->with('error', $result['message']);
        } catch (\Exception $e) {
            Log::error('AI Core: สร้าง tenant ล้มเหลว', [
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * แสดงรายละเอียด Tenant
     *
     * @param AICoreTenant $tenant
     * @return \Illuminate\View\View
     */
    public function show(AICoreTenant $tenant)
    {
        $tenant->load([
            'user',
            'featureAccess.feature',
            'usageLogs' => function ($query) {
                $query->latest()->limit(50);
            },
            'quotas',
            'alerts' => function ($query) {
                $query->unresolved()->latest();
            },
        ]);

        // สถิติ
        $stats = [
            'total_features' => $tenant->featureAccess()->count(),
            'active_features' => $tenant->featureAccess()->where('is_enabled', true)->where('status', 'approved')->count(),
            'total_usage' => $tenant->usageLogs()->count(),
            'usage_today' => $tenant->usageLogs()->whereDate('created_at', today())->count(),
            'usage_month' => $tenant->usageLogs()->whereMonth('created_at', now()->month)->count(),
        ];

        return view('admin.ai-core.tenants.show', compact('tenant', 'stats'));
    }

    /**
     * แสดงฟอร์มแก้ไข Tenant
     *
     * @param AICoreTenant $tenant
     * @return \Illuminate\View\View
     */
    public function edit(AICoreTenant $tenant)
    {
        $tenantTypes = ['user', 'organization', 'reseller'];
        $statuses = ['active', 'suspended', 'trial', 'expired'];
        $subscriptionTiers = ['free', 'basic', 'pro', 'enterprise'];

        return view('admin.ai-core.tenants.edit', compact(
            'tenant',
            'tenantTypes',
            'statuses',
            'subscriptionTiers'
        ));
    }

    /**
     * อัปเดต Tenant
     *
     * @param Request $request
     * @param AICoreTenant $tenant
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, AICoreTenant $tenant)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'tenant_key' => 'required|string|max:100|unique:ai_core_tenants,tenant_key,' . $tenant->id,
            'tenant_name' => 'required|string|max:255',
            'tenant_type' => 'required|in:user,organization,reseller',
            'status' => 'required|in:active,suspended,trial,expired',
            'is_enabled' => 'boolean',
            'subscription_tier' => 'nullable|in:free,basic,pro,enterprise',
            'subscription_starts_at' => 'nullable|date',
            'subscription_ends_at' => 'nullable|date|after:subscription_starts_at',
            'auto_renew' => 'boolean',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'billing_address' => 'nullable|string',
            'tax_id' => 'nullable|string|max:50',
        ]);

        try {
            $tenant->update($validated);

            return redirect()
                ->route('admin.ai-core.tenants.show', $tenant)
                ->with('success', 'อัปเดต Tenant สำเร็จ');
        } catch (\Exception $e) {
            Log::error('AI Core: อัปเดต tenant ล้มเหลว', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * ลบ Tenant
     *
     * @param AICoreTenant $tenant
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(AICoreTenant $tenant)
    {
        try {
            $tenantName = $tenant->tenant_name;
            $tenant->delete();

            return redirect()
                ->route('admin.ai-core.tenants.index')
                ->with('success', 'ลบ Tenant สำเร็จ: ' . $tenantName);
        } catch (\Exception $e) {
            Log::error('AI Core: ลบ tenant ล้มเหลว', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * เปิด/ปิดใช้งาน Tenant
     *
     * @param AICoreTenant $tenant
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggle(AICoreTenant $tenant)
    {
        try {
            $tenant->update(['is_enabled' => !$tenant->is_enabled]);

            return response()->json([
                'success' => true,
                'message' => $tenant->is_enabled ? 'เปิดใช้งาน Tenant แล้ว' : 'ปิดใช้งาน Tenant แล้ว',
                'is_enabled' => $tenant->is_enabled,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * แสดงหน้าจัดการ Feature Access ของ Tenant
     *
     * @param AICoreTenant $tenant
     * @return \Illuminate\View\View
     */
    public function features(AICoreTenant $tenant)
    {
        $tenant->load('featureAccess.feature');

        $availableFeatures = AICoreFeature::where('is_enabled', true)
            ->orderBy('category')
            ->orderBy('display_order')
            ->get();

        $tenantFeatures = $this->aiCoreService->getTenantFeatures($tenant->id, false);

        return view('admin.ai-core.tenants.features', compact(
            'tenant',
            'availableFeatures',
            'tenantFeatures'
        ));
    }

    /**
     * เปิดใช้งาน Feature สำหรับ Tenant
     *
     * @param Request $request
     * @param AICoreTenant $tenant
     * @param AICoreFeature $feature
     * @return \Illuminate\Http\RedirectResponse
     */
    public function enableFeature(Request $request, AICoreTenant $tenant, AICoreFeature $feature)
    {
        $validated = $request->validate([
            'access_level' => 'nullable|in:none,read,write,full',
            'quota_limit' => 'nullable|integer|min:0',
            'is_trial' => 'nullable|boolean',
            'trial_days' => 'nullable|integer|min:1',
            'access_days' => 'nullable|integer|min:1',
        ]);

        try {
            $config = [
                'access_level' => $validated['access_level'] ?? 'full',
                'quota_limit' => $validated['quota_limit'] ?? null,
                'is_trial' => $validated['is_trial'] ?? false,
            ];

            if (!empty($validated['trial_days'])) {
                $config['trial_ends_at'] = now()->addDays($validated['trial_days']);
            }

            if (!empty($validated['access_days'])) {
                $config['access_ends_at'] = now()->addDays($validated['access_days']);
            }

            $result = $this->aiCoreService->enableFeatureForTenant(
                $feature->feature_key,
                $tenant->id,
                $config
            );

            if ($result['success']) {
                return back()->with('success', 'เปิดใช้งาน Feature สำเร็จ: ' . $feature->feature_name);
            }

            return back()->with('error', $result['message']);
        } catch (\Exception $e) {
            Log::error('AI Core: เปิดใช้งาน feature สำหรับ tenant ล้มเหลว', [
                'tenant_id' => $tenant->id,
                'feature_id' => $feature->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * ปิดใช้งาน Feature สำหรับ Tenant
     *
     * @param AICoreTenant $tenant
     * @param AICoreFeature $feature
     * @return \Illuminate\Http\RedirectResponse
     */
    public function disableFeature(AICoreTenant $tenant, AICoreFeature $feature)
    {
        try {
            $result = $this->aiCoreService->disableFeatureForTenant(
                $feature->feature_key,
                $tenant->id
            );

            if ($result['success']) {
                return back()->with('success', 'ปิดใช้งาน Feature สำเร็จ: ' . $feature->feature_name);
            }

            return back()->with('error', $result['message']);
        } catch (\Exception $e) {
            Log::error('AI Core: ปิดใช้งาน feature สำหรับ tenant ล้มเหลว', [
                'tenant_id' => $tenant->id,
                'feature_id' => $feature->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }
}
