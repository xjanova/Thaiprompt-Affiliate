<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\VendorStore;
use Illuminate\Http\Request;

class VendorStoreController extends Controller
{
    /**
     * Display a vendor's public storefront
     */
    public function show(Request $request, string $slug)
    {
        $store = VendorStore::where('store_slug', $slug)
            ->where('is_active', true)
            ->with(['owner', 'package'])
            ->firstOrFail();

        // Build products query
        $query = Product::where('seller_id', $store->user_id)
            ->where('is_active', true)
            ->with(['category', 'images']);

        // Category filter
        if ($request->filled('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('short_description', 'like', "%{$search}%");
            });
        }

        // Price range filter
        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // Sort
        $sortBy = $request->get('sort', 'latest');
        switch ($sortBy) {
            case 'price_low':
                $query->orderBy('price', 'asc');
                break;
            case 'price_high':
                $query->orderBy('price', 'desc');
                break;
            case 'popular':
                $query->orderBy('sales_count', 'desc');
                break;
            case 'rating':
                $query->orderBy('rating_average', 'desc');
                break;
            case 'name':
                $query->orderBy('name', 'asc');
                break;
            default: // latest
                $query->orderBy('created_at', 'desc');
        }

        $products = $query->paginate(12)->withQueryString();

        // Get categories for this store's products
        $categories = ProductCategory::whereHas('products', function ($q) use ($store) {
            $q->where('seller_id', $store->user_id)
              ->where('is_active', true);
        })->get();

        // Store statistics
        $stats = [
            'total_products' => Product::where('seller_id', $store->user_id)
                ->where('is_active', true)
                ->count(),
            'total_sales' => $store->total_orders ?? 0,
            'rating' => $store->rating_average ?? 0,
            'rating_count' => $store->rating_count ?? 0,
        ];

        return view('vendor-store.show', compact('store', 'products', 'categories', 'stats'));
    }
}
