<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    /**
     * Display main shop (system products only, not seller products)
     */
    public function index(Request $request)
    {
        $query = Product::query()
            ->whereNull('seller_id') // Only system products (main shop)
            ->active()
            ->inStock()
            ->with(['category', 'images']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('short_description', 'like', "%{$search}%");
            });
        }

        // Category filter
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        // Price range filter
        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // Sorting
        $sort = $request->get('sort', 'latest');
        switch ($sort) {
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
            default: // latest
                $query->orderBy('created_at', 'desc');
                break;
        }

        $products = $query->paginate(12);

        // Get categories for filter
        $categories = ProductCategory::whereHas('products', function($q) {
            $q->whereNull('seller_id')->active();
        })->get();

        return view('user.shop.index', compact('products', 'categories'));
    }

    /**
     * Display product detail
     */
    public function show($slug)
    {
        $product = Product::whereNull('seller_id') // Only system products
            ->active()
            ->where('slug', $slug)
            ->with(['category', 'images', 'approvedReviews.user', 'variants'])
            ->firstOrFail();

        // Increment view count
        $product->incrementViewCount();

        // Get related products
        $relatedProducts = Product::whereNull('seller_id')
            ->active()
            ->inStock()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->limit(4)
            ->get();

        return view('user.shop.show', compact('product', 'relatedProducts'));
    }
}
