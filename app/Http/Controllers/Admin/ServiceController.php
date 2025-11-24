<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * ServiceController - จัดการบริการ (Admin)
 *
 * CRUD สำหรับบริการ
 */
class ServiceController extends Controller
{
    /**
     * แสดงรายการบริการทั้งหมด
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = Service::with(['category', 'owner']);

        // ค้นหา
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // กรองตามหมวดหมู่
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // กรองตามสถานะ
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            } elseif ($request->status === 'featured') {
                $query->featured();
            }
        }

        $services = $query->latest()->paginate(15);
        $categories = ServiceCategory::active()->ordered()->get();

        return view('admin.services.index', [
            'services' => $services,
            'categories' => $categories,
            'pageTitle' => 'จัดการบริการ',
        ]);
    }

    /**
     * แสดงรายการบริการที่ถูกบล็อก
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function blocked(Request $request)
    {
        $query = Service::with(['category', 'owner', 'blockedByUser'])
            ->blocked(); // ใช้ scope blocked()

        // ค้นหา
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('block_reason', 'like', "%{$search}%");
            });
        }

        // กรองตามหมวดหมู่
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $services = $query->latest('blocked_at')->paginate(15);
        $categories = ServiceCategory::active()->ordered()->get();

        return view('admin.services.blocked', [
            'services' => $services,
            'categories' => $categories,
            'pageTitle' => 'บริการที่ถูกบล็อก',
        ]);
    }

    /**
     * แสดงฟอร์มสร้างบริการใหม่
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $categories = ServiceCategory::active()->ordered()->get();

        return view('admin.services.create', [
            'categories' => $categories,
            'pageTitle' => 'สร้างบริการใหม่',
        ]);
    }

    /**
     * บันทึกบริการใหม่
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:service_categories,id',
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:services,slug',
            'description' => 'nullable|string',
            'base_price' => 'required|numeric|min:0',
            'duration_minutes' => 'required|integer|min:1',
            'images' => 'nullable|array',
            'images.*' => 'string|max:500',
            'options' => 'nullable|array',
            'requires_location' => 'boolean',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'min_booking_hours' => 'nullable|integer|min:0',
            'max_bookings_per_day' => 'nullable|integer|min:1',
            'cancellation_hours' => 'nullable|integer|min:0',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        // เจ้าของบริการ (admin)
        $validated['owner_id'] = auth()->id();

        // สร้าง slug อัตโนมัติ
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $service = Service::create($validated);

        return redirect()
            ->route('admin.services.index')
            ->with('success', 'สร้างบริการสำเร็จ');
    }

    /**
     * แสดงรายละเอียดบริการ
     *
     * @param Service $service
     * @return \Illuminate\View\View
     */
    public function show(Service $service)
    {
        $service->load(['category', 'owner', 'pricingRules']);
        $service->loadCount(['bookings', 'reviews']);

        return view('admin.services.show', [
            'service' => $service,
            'pageTitle' => 'บริการ: ' . $service->name,
        ]);
    }

    /**
     * แสดงฟอร์มแก้ไข
     *
     * @param Service $service
     * @return \Illuminate\View\View
     */
    public function edit(Service $service)
    {
        $categories = ServiceCategory::active()->ordered()->get();

        return view('admin.services.edit', [
            'service' => $service,
            'categories' => $categories,
            'pageTitle' => 'แก้ไขบริการ: ' . $service->name,
        ]);
    }

    /**
     * อัพเดทบริการ
     *
     * @param Request $request
     * @param Service $service
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Service $service)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:service_categories,id',
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:services,slug,' . $service->id,
            'description' => 'nullable|string',
            'base_price' => 'required|numeric|min:0',
            'duration_minutes' => 'required|integer|min:1',
            'images' => 'nullable|array',
            'images.*' => 'string|max:500',
            'options' => 'nullable|array',
            'requires_location' => 'boolean',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'min_booking_hours' => 'nullable|integer|min:0',
            'max_bookings_per_day' => 'nullable|integer|min:1',
            'cancellation_hours' => 'nullable|integer|min:0',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $service->update($validated);

        return redirect()
            ->route('admin.services.index')
            ->with('success', 'อัพเดทบริการสำเร็จ');
    }

    /**
     * ลบบริการ
     *
     * @param Service $service
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Service $service)
    {
        // ตรวจสอบว่ามีการจองหรือไม่
        if ($service->bookings()->count() > 0) {
            return redirect()
                ->back()
                ->with('error', 'ไม่สามารถลบบริการได้ เนื่องจากมีการจองแล้ว');
        }

        $service->delete();

        return redirect()
            ->route('admin.services.index')
            ->with('success', 'ลบบริการสำเร็จ');
    }

    /**
     * เปลี่ยนสถานะ active/inactive
     *
     * @param Service $service
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleActive(Service $service)
    {
        $service->is_active = !$service->is_active;
        $service->save();

        return response()->json([
            'success' => true,
            'is_active' => $service->is_active,
            'message' => $service->is_active ? 'เปิดใช้งานแล้ว' : 'ปิดใช้งานแล้ว',
        ]);
    }

    /**
     * เปลี่ยนสถานะ featured
     *
     * @param Service $service
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleFeatured(Service $service)
    {
        $service->is_featured = !$service->is_featured;
        $service->save();

        return response()->json([
            'success' => true,
            'is_featured' => $service->is_featured,
            'message' => $service->is_featured ? 'ตั้งเป็นแนะนำแล้ว' : 'ยกเลิกแนะนำแล้ว',
        ]);
    }

    /**
     * คำนวณราคาตัวอย่างตามระยะทาง
     *
     * @param Request $request
     * @param Service $service
     * @return \Illuminate\Http\JsonResponse
     */
    public function calculatePrice(Request $request, Service $service)
    {
        $validated = $request->validate([
            'distance_km' => 'required|numeric|min:0',
        ]);

        $totalPrice = $service->calculateTotalPrice($validated['distance_km']);

        return response()->json([
            'success' => true,
            'base_price' => $service->base_price,
            'distance_km' => $validated['distance_km'],
            'distance_price' => $service->calculateDistancePrice($validated['distance_km']),
            'total_price' => $totalPrice,
        ]);
    }

    /**
     * บล็อกบริการ (Admin)
     *
     * @param Request $request
     * @param Service $service
     * @return \Illuminate\Http\RedirectResponse
     */
    public function blockService(Request $request, Service $service)
    {
        $validated = $request->validate([
            'block_reason' => 'required|string|max:1000',
        ]);

        try {
            // อัปเดตสถานะบล็อก
            $service->update([
                'is_blocked' => true,
                'blocked_at' => now(),
                'blocked_by' => auth()->id(),
                'block_reason' => $validated['block_reason'],
                'is_active' => false, // ปิดการแสดงบริการอัตโนมัติ
            ]);

            // ส่งการแจ้งเตือนให้ผู้ให้บริการ
            if ($service->owner) {
                $service->owner->notify(new \App\Notifications\ServiceBlockedNotification($service));
            }

            // บันทึก Activity Log
            activity()
                ->performedOn($service)
                ->causedBy(auth()->user())
                ->withProperties([
                    'reason' => $validated['block_reason'],
                    'owner_id' => $service->owner_id,
                ])
                ->log('บล็อกบริการ');

            return redirect()->back()->with('success', 'บล็อกบริการเรียบร้อยแล้ว และแจ้งเตือนผู้ให้บริการแล้ว');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * ปลดบล็อกบริการ (Admin)
     *
     * @param Service $service
     * @return \Illuminate\Http\RedirectResponse
     */
    public function unblockService(Service $service)
    {
        try {
            // อัปเดตสถานะปลดบล็อก
            $service->update([
                'is_blocked' => false,
                'unblocked_at' => now(),
                'unblocked_by' => auth()->id(),
                // หมายเหตุ: ไม่ต้องเปิด is_active อัตโนมัติ ให้ผู้ให้บริการเปิดเอง
            ]);

            // ส่งการแจ้งเตือนให้ผู้ให้บริการ
            if ($service->owner) {
                $service->owner->notify(new \App\Notifications\ServiceUnblockedNotification($service));
            }

            // บันทึก Activity Log
            activity()
                ->performedOn($service)
                ->causedBy(auth()->user())
                ->withProperties([
                    'owner_id' => $service->owner_id,
                ])
                ->log('ปลดบล็อกบริการ');

            return redirect()->back()->with('success', 'ปลดบล็อกบริการเรียบร้อยแล้ว และแจ้งเตือนผู้ให้บริการแล้ว');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }
}
