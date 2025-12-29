<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VendorStore;
use Illuminate\Http\Request;

/**
 * VendorStoreController - จัดการร้านค้าทั้งหมดในระบบ (Admin)
 *
 * Controller นี้ให้ Admin สามารถดูและจัดการร้านค้าของ Vendor ทั้งหมด
 * รวมถึงการอนุมัติ, ปิด/เปิดใช้งาน, และตั้งค่าร้านค้าแนะนำ
 */
class VendorStoreController extends Controller
{
    /**
     * แสดงรายการร้านค้าทั้งหมด
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = VendorStore::with(['user', 'products'])
            ->withCount('products');

        // ค้นหาด้วยชื่อร้าน
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('store_name', 'like', "%{$search}%")
                    ->orWhere('store_slug', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        // กรองตามสถานะ
        if ($request->filled('status')) {
            switch ($request->status) {
                case 'active':
                    $query->where('is_active', true);
                    break;
                case 'inactive':
                    $query->where('is_active', false);
                    break;
                case 'featured':
                    $query->where('is_featured_home', true);
                    break;
                case 'verified':
                    $query->where('is_verified', true);
                    break;
            }
        }

        // เรียงลำดับ
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $stores = $query->paginate(20);

        // สถิติ
        $stats = [
            'total' => VendorStore::count(),
            'active' => VendorStore::where('is_active', true)->count(),
            'featured' => VendorStore::where('is_featured_home', true)->count(),
            'verified' => VendorStore::where('is_verified', true)->count(),
        ];

        return view('admin.storefront.vendor-stores.index', compact('stores', 'stats'));
    }

    /**
     * แสดงรายละเอียดร้านค้า
     *
     * @return \Illuminate\View\View
     */
    public function show(VendorStore $store)
    {
        $store->load(['user', 'products' => function ($query) {
            $query->latest()->limit(10);
        }]);

        // สถิติร้านค้า
        $stats = [
            'products_count' => $store->products()->count(),
            'orders_count' => $store->orders()->count(),
            'total_revenue' => $store->orders()
                ->where('payment_status', 'paid')
                ->sum('total_amount'),
            'avg_rating' => $store->reviews()->avg('rating') ?? 0,
            'reviews_count' => $store->reviews()->count(),
        ];

        return view('admin.storefront.vendor-stores.show', compact('store', 'stats'));
    }

    /**
     * แสดงฟอร์มแก้ไขร้านค้า
     *
     * @return \Illuminate\View\View
     */
    public function edit(VendorStore $store)
    {
        return view('admin.storefront.vendor-stores.edit', compact('store'));
    }

    /**
     * อัพเดทข้อมูลร้านค้า
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, VendorStore $store)
    {
        $validated = $request->validate([
            'store_name' => 'required|string|max:255',
            'store_description' => 'nullable|string|max:1000',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
            'is_verified' => 'boolean',
            'is_featured_home' => 'boolean',
            'primary_color' => 'nullable|string|max:10',
            'secondary_color' => 'nullable|string|max:10',
        ]);

        $store->update($validated);

        return redirect()
            ->route('admin.storefront.vendor-stores.index')
            ->with('success', 'อัพเดทร้านค้า "'.$store->store_name.'" สำเร็จ');
    }

    /**
     * สลับสถานะ Active/Inactive
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleStatus(VendorStore $store)
    {
        $store->is_active = ! $store->is_active;
        $store->save();

        return response()->json([
            'success' => true,
            'is_active' => $store->is_active,
            'message' => $store->is_active
                ? 'เปิดใช้งานร้านค้า "'.$store->store_name.'" แล้ว'
                : 'ปิดใช้งานร้านค้า "'.$store->store_name.'" แล้ว',
        ]);
    }

    /**
     * สลับสถานะ Featured
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleFeatured(VendorStore $store)
    {
        $store->is_featured_home = ! $store->is_featured_home;

        // ถ้าเป็น featured ใหม่ ให้ตั้ง order
        if ($store->is_featured_home) {
            $maxOrder = VendorStore::where('is_featured_home', true)->max('featured_home_order') ?? 0;
            $store->featured_home_order = $maxOrder + 1;
        } else {
            $store->featured_home_order = null;
        }

        $store->save();

        return response()->json([
            'success' => true,
            'is_featured' => $store->is_featured_home,
            'message' => $store->is_featured_home
                ? 'เพิ่มร้าน "'.$store->store_name.'" เป็นร้านค้าแนะนำแล้ว'
                : 'นำร้าน "'.$store->store_name.'" ออกจากร้านค้าแนะนำแล้ว',
        ]);
    }

    /**
     * ลบร้านค้า (Soft Delete)
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(VendorStore $store)
    {
        $storeName = $store->store_name;

        // Soft delete
        $store->delete();

        return redirect()
            ->route('admin.storefront.vendor-stores.index')
            ->with('success', 'ลบร้านค้า "'.$storeName.'" สำเร็จ');
    }
}
