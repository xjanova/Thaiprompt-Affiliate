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
            ->orderBy('featured_home_order', 'asc')
            ->get();

        $availableStores = VendorStore::where('is_active', true)
            ->where('is_verified', true)
            ->where(function($query) {
                $query->where('is_featured_home', false)
                      ->orWhereNull('is_featured_home');
            })
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
            'order.*' => 'required|integer',
        ]);

        foreach ($request->order as $storeId => $order) {
            VendorStore::where('id', $storeId)
                ->update(['featured_home_order' => $order]);
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
