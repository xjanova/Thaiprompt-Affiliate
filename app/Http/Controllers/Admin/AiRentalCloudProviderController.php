<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiRentalCloudProvider;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Cloud Provider Management Controller
 *
 * จัดการ Cloud GPU Providers (CRUD)
 */
class AiRentalCloudProviderController extends Controller
{
    /**
     * แสดงรายการ Cloud Providers ทั้งหมด
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        // เริ่มต้น query
        $query = AiRentalCloudProvider::query();

        // ค้นหา
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // กรองตาม tier
        if ($tier = $request->input('tier')) {
            if ($tier === 'free') {
                $query->freeTier();
            } elseif ($tier === 'paid') {
                $query->paidTier();
            }
        }

        // กรองตามสถานะ
        if ($request->has('active')) {
            $active = $request->input('active');
            $query->where('is_active', $active === '1');
        }

        // เรียงลำดับ
        $sortBy = $request->input('sort', 'order');
        switch ($sortBy) {
            case 'name':
                $query->orderBy('name');
                break;
            case 'popular':
                $query->popular();
                break;
            case 'rating':
                $query->orderBy('average_rating', 'desc');
                break;
            default: // order
                $query->ordered();
                break;
        }

        // Pagination
        $providers = $query->paginate(15)->withQueryString();

        // สถิติ
        $stats = [
            'total' => AiRentalCloudProvider::count(),
            'active' => AiRentalCloudProvider::active()->count(),
            'free' => AiRentalCloudProvider::freeTier()->count(),
            'paid' => AiRentalCloudProvider::paidTier()->count(),
        ];

        return view('admin.ai-rental.cloud-providers.index', compact('providers', 'stats'));
    }

    /**
     * แสดงฟอร์มสร้าง Provider ใหม่
     *
     * @return View
     */
    public function create(): View
    {
        return view('admin.ai-rental.cloud-providers.create');
    }

    /**
     * บันทึก Provider ใหม่
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        // Validation
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:ai_rental_cloud_providers,slug',
            'description' => 'nullable|string',
            'tier' => 'required|in:free,paid,both',
            'website_url' => 'required|url',
            'signup_url' => 'nullable|url',
            'documentation_url' => 'nullable|url',
            'pricing_url' => 'nullable|url',

            // GPU & Pricing
            'gpu_types' => 'nullable|array',
            'min_price_per_hour' => 'nullable|numeric|min:0',
            'max_price_per_hour' => 'nullable|numeric|min:0',

            // Features
            'supports_huggingface' => 'boolean',
            'supports_docker' => 'boolean',
            'supports_jupyter' => 'boolean',
            'supports_ssh' => 'boolean',
            'supports_api' => 'boolean',
            'supports_spot_instances' => 'boolean',

            // Free Tier
            'free_tier_available' => 'boolean',
            'free_tier_hours_per_month' => 'nullable|integer|min:0',
            'free_tier_gpu_types' => 'nullable|array',
            'free_tier_limitations' => 'nullable|string',

            // Ratings & Status
            'average_rating' => 'nullable|numeric|min:0|max:5',
            'is_active' => 'boolean',
            'is_recommended' => 'boolean',
            'display_order' => 'nullable|integer|min:0',
        ]);

        // สร้าง provider
        $provider = AiRentalCloudProvider::create($validated);

        return redirect()
            ->route('admin.ai-rental.cloud-providers.index')
            ->with('success', "เพิ่ม Cloud Provider '{$provider->name}' สำเร็จ!");
    }

    /**
     * แสดงรายละเอียด Provider
     *
     * @param AiRentalCloudProvider $cloudProvider
     * @return View
     */
    public function show(AiRentalCloudProvider $cloudProvider): View
    {
        // โหลด relationships
        $cloudProvider->load(['cloudConfigs', 'deployments']);

        // สถิติการใช้งาน
        $stats = [
            'total_configs' => $cloudProvider->cloudConfigs()->count(),
            'active_configs' => $cloudProvider->cloudConfigs()->active()->count(),
            'total_deployments' => $cloudProvider->deployments()->count(),
            'running_deployments' => $cloudProvider->deployments()->running()->count(),
            'total_hours' => $cloudProvider->deployments()->sum('total_hours'),
            'total_cost' => $cloudProvider->deployments()->sum('total_cost'),
        ];

        return view('admin.ai-rental.cloud-providers.show', compact('cloudProvider', 'stats'));
    }

    /**
     * แสดงฟอร์มแก้ไข Provider
     *
     * @param AiRentalCloudProvider $cloudProvider
     * @return View
     */
    public function edit(AiRentalCloudProvider $cloudProvider): View
    {
        return view('admin.ai-rental.cloud-providers.edit', compact('cloudProvider'));
    }

    /**
     * อัพเดท Provider
     *
     * @param Request $request
     * @param AiRentalCloudProvider $cloudProvider
     * @return RedirectResponse
     */
    public function update(Request $request, AiRentalCloudProvider $cloudProvider): RedirectResponse
    {
        // Validation (slug unique ยกเว้นตัวเอง)
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:ai_rental_cloud_providers,slug,' . $cloudProvider->id,
            'description' => 'nullable|string',
            'tier' => 'required|in:free,paid,both',
            'website_url' => 'required|url',
            'signup_url' => 'nullable|url',
            'documentation_url' => 'nullable|url',
            'pricing_url' => 'nullable|url',

            // GPU & Pricing
            'gpu_types' => 'nullable|array',
            'min_price_per_hour' => 'nullable|numeric|min:0',
            'max_price_per_hour' => 'nullable|numeric|min:0',

            // Features
            'supports_huggingface' => 'boolean',
            'supports_docker' => 'boolean',
            'supports_jupyter' => 'boolean',
            'supports_ssh' => 'boolean',
            'supports_api' => 'boolean',
            'supports_spot_instances' => 'boolean',

            // Free Tier
            'free_tier_available' => 'boolean',
            'free_tier_hours_per_month' => 'nullable|integer|min:0',
            'free_tier_gpu_types' => 'nullable|array',
            'free_tier_limitations' => 'nullable|string',

            // Ratings & Status
            'average_rating' => 'nullable|numeric|min:0|max:5',
            'is_active' => 'boolean',
            'is_recommended' => 'boolean',
            'display_order' => 'nullable|integer|min:0',
        ]);

        // อัพเดท
        $cloudProvider->update($validated);

        return redirect()
            ->route('admin.ai-rental.cloud-providers.index')
            ->with('success', "แก้ไข '{$cloudProvider->name}' สำเร็จ!");
    }

    /**
     * ลบ Provider
     *
     * @param AiRentalCloudProvider $cloudProvider
     * @return RedirectResponse
     */
    public function destroy(AiRentalCloudProvider $cloudProvider): RedirectResponse
    {
        // ตรวจสอบว่ามี deployments ที่ใช้งานอยู่หรือไม่
        if ($cloudProvider->deployments()->running()->exists()) {
            return redirect()
                ->back()
                ->with('error', "ไม่สามารถลบ '{$cloudProvider->name}' ได้ เนื่องจากมี deployments ที่กำลังรันอยู่");
        }

        $name = $cloudProvider->name;

        // ใช้ soft delete
        $cloudProvider->delete();

        return redirect()
            ->route('admin.ai-rental.cloud-providers.index')
            ->with('success', "ลบ '{$name}' สำเร็จ!");
    }

    /**
     * เปิดใช้งาน Provider
     *
     * @param AiRentalCloudProvider $cloudProvider
     * @return RedirectResponse
     */
    public function activate(AiRentalCloudProvider $cloudProvider): RedirectResponse
    {
        $cloudProvider->update(['is_active' => true]);

        return redirect()
            ->back()
            ->with('success', "เปิดใช้งาน '{$cloudProvider->name}' แล้ว");
    }

    /**
     * ปิดใช้งาน Provider
     *
     * @param AiRentalCloudProvider $cloudProvider
     * @return RedirectResponse
     */
    public function deactivate(AiRentalCloudProvider $cloudProvider): RedirectResponse
    {
        $cloudProvider->update(['is_active' => false]);

        return redirect()
            ->back()
            ->with('success', "ปิดใช้งาน '{$cloudProvider->name}' แล้ว");
    }

    /**
     * เพิ่ม/แก้ไขคะแนน Provider
     *
     * @param Request $request
     * @param AiRentalCloudProvider $cloudProvider
     * @return RedirectResponse
     */
    public function updateRating(Request $request, AiRentalCloudProvider $cloudProvider): RedirectResponse
    {
        $validated = $request->validate([
            'rating' => 'required|numeric|min:0|max:5',
        ]);

        $cloudProvider->addReview($validated['rating']);

        return redirect()
            ->back()
            ->with('success', "อัพเดทคะแนนสำเร็จ");
    }

    /**
     * อัพเดทลำดับการแสดงผล (Drag & Drop)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateOrder(Request $request)
    {
        $validated = $request->validate([
            'orders' => 'required|array',
            'orders.*.id' => 'required|exists:ai_rental_cloud_providers,id',
            'orders.*.order' => 'required|integer|min:0',
        ]);

        foreach ($validated['orders'] as $item) {
            AiRentalCloudProvider::where('id', $item['id'])
                ->update(['display_order' => $item['order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'อัพเดทลำดับสำเร็จ',
        ]);
    }
}
