<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

class MarketplaceController extends Controller
{
    /**
     * Display marketplace products for affiliate marketing
     */
    public function products(Request $request)
    {
        $query = Product::query()
            ->where('is_active', true)
            ->where('is_public_approved', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->with(['category', 'seller', 'images']);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
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

        // Commission rate filter
        if ($request->filled('min_commission')) {
            $query->where('commission_rate', '>=', $request->min_commission);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');

        switch ($sortBy) {
            case 'price_low':
                $query->orderBy('price', 'asc');
                break;
            case 'price_high':
                $query->orderBy('price', 'desc');
                break;
            case 'commission_high':
                $query->orderBy('commission_rate', 'desc');
                break;
            case 'popular':
                $query->orderBy('sales_count', 'desc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            default:
                $query->orderBy($sortBy, $sortDir);
        }

        // Only show products that are in stock
        $query->inStock();

        $products = $query->paginate(12)->withQueryString();

        // Get categories for filter
        $categories = ProductCategory::whereHas('products', function ($q) {
            $q->where('is_active', true)
                ->where('is_public_approved', true)
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now());
        })->get();

        return view('user.marketplace.products', compact('products', 'categories'));
    }
}
