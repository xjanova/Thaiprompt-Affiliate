<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServicePricingRule;
use Illuminate\Http\Request;

/**
 * ServicePricingRuleController - จัดการกฎการคิดราคา (Admin)
 *
 * ตั้งค่าราคาตามระยะทาง, ช่วงเวลา, หมวดหมู่
 */
class ServicePricingRuleController extends Controller
{
    /**
     * แสดงรายการกฎการคิดราคาทั้งหมด
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = ServicePricingRule::with(['service', 'category']);

        // กรองตามประเภท
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // กรองตามสถานะ
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // ค้นหา
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('description', 'like', "%{$request->search}%");
            });
        }

        $rules = $query->orderBy('priority', 'desc')->paginate(20);

        // สถิติ
        $stats = [
            'total' => ServicePricingRule::count(),
            'active' => ServicePricingRule::where('is_active', true)->count(),
            'inactive' => ServicePricingRule::where('is_active', false)->count(),
            'distance_based' => ServicePricingRule::where('type', 'distance')->count(),
            'time_based' => ServicePricingRule::where('type', 'time')->count(),
            'fixed' => ServicePricingRule::where('type', 'fixed')->count(),
        ];

        return view('admin.service-pricing-rules.index', [
            'rules' => $rules,
            'stats' => $stats,
            'pageTitle' => 'กฎการคิดราคาบริการ',
        ]);
    }

    /**
     * แสดงฟอร์มสร้างกฎใหม่
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $services = Service::where('is_active', true)->get();
        $categories = ServiceCategory::where('is_active', true)->get();

        return view('admin.service-pricing-rules.create', [
            'services' => $services,
            'categories' => $categories,
            'pageTitle' => 'เพิ่มกฎการคิดราคาใหม่',
        ]);
    }

    /**
     * บันทึกกฎใหม่
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:fixed,distance,time,zone,peak',
            'service_id' => 'nullable|exists:services,id',
            'category_id' => 'nullable|exists:service_categories,id',
            'base_price' => 'required|numeric|min:0',
            'price_per_km' => 'nullable|numeric|min:0',
            'price_per_minute' => 'nullable|numeric|min:0',
            'min_distance' => 'nullable|numeric|min:0',
            'max_distance' => 'nullable|numeric|min:0',
            'multiplier' => 'nullable|numeric|min:0',
            'priority' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        ServicePricingRule::create($validated);

        return redirect()
            ->route('admin.service-pricing-rules.index')
            ->with('success', 'เพิ่มกฎการคิดราคาสำเร็จ');
    }

    /**
     * แสดงรายละเอียดกฎ
     *
     * @return \Illuminate\View\View
     */
    public function show(ServicePricingRule $pricingRule)
    {
        $pricingRule->load(['service', 'category']);

        return view('admin.service-pricing-rules.show', [
            'rule' => $pricingRule,
            'pageTitle' => 'รายละเอียดกฎ: '.$pricingRule->name,
        ]);
    }

    /**
     * แสดงฟอร์มแก้ไขกฎ
     *
     * @return \Illuminate\View\View
     */
    public function edit(ServicePricingRule $pricingRule)
    {
        $services = Service::where('is_active', true)->get();
        $categories = ServiceCategory::where('is_active', true)->get();

        return view('admin.service-pricing-rules.edit', [
            'rule' => $pricingRule,
            'services' => $services,
            'categories' => $categories,
            'pageTitle' => 'แก้ไขกฎ: '.$pricingRule->name,
        ]);
    }

    /**
     * อัพเดทกฎ
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, ServicePricingRule $pricingRule)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:fixed,distance,time,zone,peak',
            'service_id' => 'nullable|exists:services,id',
            'category_id' => 'nullable|exists:service_categories,id',
            'base_price' => 'required|numeric|min:0',
            'price_per_km' => 'nullable|numeric|min:0',
            'price_per_minute' => 'nullable|numeric|min:0',
            'min_distance' => 'nullable|numeric|min:0',
            'max_distance' => 'nullable|numeric|min:0',
            'multiplier' => 'nullable|numeric|min:0',
            'priority' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $pricingRule->update($validated);

        return redirect()
            ->route('admin.service-pricing-rules.index')
            ->with('success', 'อัพเดทกฎการคิดราคาสำเร็จ');
    }

    /**
     * ลบกฎ
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(ServicePricingRule $pricingRule)
    {
        $pricingRule->delete();

        return redirect()
            ->route('admin.service-pricing-rules.index')
            ->with('success', 'ลบกฎการคิดราคาสำเร็จ');
    }

    /**
     * เปิด/ปิดการใช้งานกฎ
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function toggleActive(ServicePricingRule $pricingRule)
    {
        $pricingRule->update([
            'is_active' => ! $pricingRule->is_active,
        ]);

        $status = $pricingRule->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน';

        return redirect()
            ->back()
            ->with('success', "{$status}กฎการคิดราคาสำเร็จ");
    }
}
