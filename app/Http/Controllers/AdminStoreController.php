<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\VendorStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminStoreController extends Controller
{
    /**
     * Display the admin store homepage
     */
    public function index(Request $request)
    {
        // Get or create admin store
        $adminStore = $this->getAdminStore();

        // Get product categories
        $categories = ProductCategory::withCount('products')->get();

        // Build query for products
        $query = Product::with(['seller', 'category'])
            ->where('store_id', $adminStore->id)
            ->where('is_active', true);

        // Apply filters
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // Apply sorting
        switch ($request->input('sort_by', 'newest')) {
            case 'popular':
                $query->orderBy('sales_count', 'desc');
                break;
            case 'price_low':
                $query->orderBy('price', 'asc');
                break;
            case 'price_high':
                $query->orderBy('price', 'desc');
                break;
            case 'rating':
                $query->orderBy('rating_average', 'desc');
                break;
            default:
                $query->latest();
        }

        $products = $query->paginate(16);

        // Get featured products
        $featuredProducts = Product::where('store_id', $adminStore->id)
            ->where('is_featured', true)
            ->where('is_active', true)
            ->limit(8)
            ->get();

        return view('admin-store.index', compact('adminStore', 'products', 'categories', 'featuredProducts'));
    }

    /**
     * Display product details
     */
    public function show($id)
    {
        $adminStore = $this->getAdminStore();

        $product = Product::with(['seller', 'category'])
            ->where('store_id', $adminStore->id)
            ->where('is_active', true)
            ->findOrFail($id);

        // Increment view count
        $product->increment('view_count');

        // Get related products
        $relatedProducts = Product::where('store_id', $adminStore->id)
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('is_active', true)
            ->limit(4)
            ->get();

        return view('admin-store.show', compact('adminStore', 'product', 'relatedProducts'));
    }

    /**
     * Get or create admin store
     */
    private function getAdminStore()
    {
        // Find admin user (assuming user_id = 1 is admin)
        $adminUserId = 1;

        $store = VendorStore::where('user_id', $adminUserId)->first();

        if (!$store) {
            // Create default admin store
            $store = VendorStore::create([
                'user_id' => $adminUserId,
                'store_name' => 'Admin Store',
                'store_slug' => 'admin-store',
                'store_description' => 'Official admin store with curated products',
                'status' => 'active',
                'is_active' => true,
                'is_verified' => true,
                'subscription_status' => 'active',
            ]);
        }

        return $store;
    }
}
