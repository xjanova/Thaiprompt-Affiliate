<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    /**
     * Display all products (Shop homepage)
     */
    public function index(Request $request)
    {
        $query = Product::with(['category', 'seller'])
            ->active()
            ->inStock();

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Category Filter
        if ($request->filled('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        // Price Range Filter
        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // Brand Filter
        if ($request->filled('brand')) {
            $query->where('brand', $request->brand);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'newest');
        switch ($sortBy) {
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
            case 'newest':
            default:
                $query->latest('published_at');
                break;
        }

        $products = $query->paginate(24);

        // Get categories for filter
        $categories = ProductCategory::active()
            ->root()
            ->orderBy('sort_order')
            ->get();

        // Get unique brands
        $brands = Product::active()
            ->whereNotNull('brand')
            ->distinct('brand')
            ->pluck('brand')
            ->sort();

        // Featured products
        $featuredProducts = Product::with(['category', 'seller'])
            ->active()
            ->featured()
            ->inStock()
            ->take(8)
            ->get();

        return view('shop.index', compact(
            'products',
            'categories',
            'brands',
            'featuredProducts'
        ));
    }

    /**
     * Display product details
     */
    public function show($slug)
    {
        $product = Product::with([
            'category',
            'seller',
            'images',
            'variants',
            'approvedReviews.user'
        ])
            ->where('slug', $slug)
            ->firstOrFail();

        // Increment view count
        $product->incrementViewCount();

        // Related products
        $relatedProducts = Product::with(['category', 'seller'])
            ->active()
            ->inStock()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->take(8)
            ->get();

        // Check if user has purchased this product (for review)
        $hasPurchased = false;
        if (auth()->check()) {
            $hasPurchased = $product->orderItems()
                ->whereHas('order', function ($q) {
                    $q->where('user_id', auth()->id())
                      ->where('status', 'completed');
                })
                ->exists();
        }

        return view('shop.show', compact(
            'product',
            'relatedProducts',
            'hasPurchased'
        ));
    }

    /**
     * Get products by category
     */
    public function category($slug, Request $request)
    {
        $category = ProductCategory::where('slug', $slug)
            ->active()
            ->firstOrFail();

        $query = Product::with(['category', 'seller'])
            ->active()
            ->inStock()
            ->where('category_id', $category->id);

        // Apply same filters and sorting as index
        // ... (similar to index method)

        $products = $query->paginate(24);

        return view('shop.category', compact('category', 'products'));
    }

    /**
     * Quick search API
     */
    public function quickSearch(Request $request)
    {
        $search = $request->get('q', '');

        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $products = Product::active()
            ->inStock()
            ->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            })
            ->select('id', 'name', 'slug', 'price', 'main_image_url')
            ->take(10)
            ->get();

        return response()->json($products);
    }
}
