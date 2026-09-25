<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VendorStore;
use Illuminate\Http\Request;

class FeaturedStoreController extends Controller
{
    /**
     * Display featured stores management
     */
    public function index()
    {
        $featuredStores = VendorStore::where('is_featured_home', true)
            ->withCount('products')
            ->orderBy('featured_home_order', 'asc')
            ->get();

        // เลือกได้เฉพาะร้านที่เปิดขาย + ยืนยันแล้ว + ไม่ถูกระงับ
        $availableStores = VendorStore::where('is_active', true)
            ->where('is_verified', true)
            ->whereNotIn('status', ['suspended', 'closed', 'pending'])
            ->where(function ($query) {
                $query->where('is_featured_home', false)
                    ->orWhereNull('is_featured_home');
            })
            ->withCount('products')
            ->orderBy('rating_average', 'desc')
            ->get();

        return view('admin.featured-stores.index', compact('featuredStores', 'availableStores'));
    }

    /**
     * Add store to featured
     */
    public function addToFeatured(Request $request, $storeId)
    {
        $store = VendorStore::findOrFail($storeId);

        // Get max order
        $maxOrder = VendorStore::where('is_featured_home', true)->max('featured_home_order') ?? 0;

        $store->update([
            'is_featured_home' => true,
            'featured_home_order' => $maxOrder + 1,
        ]);

        return redirect()->back()->with('success', 'เพิ่มร้านค้าลงในหน้าแรกเรียบร้อยแล้ว');
    }

    /**
     * Remove store from featured
     */
    public function removeFromFeatured($storeId)
    {
        $store = VendorStore::findOrFail($storeId);

        $store->update([
            'is_featured_home' => false,
            'featured_home_order' => null,
        ]);

        // Reorder remaining featured stores
        $this->reorderFeaturedStores();

        return redirect()->back()->with('success', 'ลบร้านค้าออกจากหน้าแรกเรียบร้อยแล้ว');
    }

    /**
     * Update featured order
     */
    public function updateOrder(Request $request)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'required|integer|min:1',
        ], [
            'order.required' => 'ไม่พบลำดับที่จะบันทึก',
        ]);

        // อัปเดตเฉพาะร้านที่เป็นร้านแนะนำอยู่จริง (กันส่ง id อื่นมาแก้ลำดับ)
        \Illuminate\Support\Facades\DB::transaction(function () use ($request) {
            foreach ($request->order as $storeId => $order) {
                VendorStore::where('id', (int) $storeId)
                    ->where('is_featured_home', true)
                    ->update(['featured_home_order' => (int) $order]);
            }
        });

        // หน้า V4 ส่งแบบ AJAX (SortableJS) → ตอบ JSON · ฟอร์มปกติ → redirect กลับ
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'อัปเดตลำดับการแสดงเรียบร้อยแล้ว',
            ]);
        }

        return redirect()->back()->with('success', 'อัปเดตลำดับการแสดงเรียบร้อยแล้ว');
    }

    /**
     * Reorder featured stores sequentially
     */
    private function reorderFeaturedStores()
    {
        $stores = VendorStore::where('is_featured_home', true)
            ->orderBy('featured_home_order', 'asc')
            ->get();

        foreach ($stores as $index => $store) {
            $store->update(['featured_home_order' => $index + 1]);
        }
    }
}
