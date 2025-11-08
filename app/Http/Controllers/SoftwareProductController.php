<?php

namespace App\Http\Controllers;

use App\Models\SoftwareProduct;
use App\Models\SoftwareProductCategory;
use Illuminate\Http\Request;

class SoftwareProductController extends Controller
{
    /**
     * Display a listing of software products
     */
    public function index(Request $request)
    {
        $query = SoftwareProduct::with('category')
            ->active()
            ->ordered();

        // Filter by category
        if ($request->has('category')) {
            $query->where('category_id', $request->category);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('short_description', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by featured
        if ($request->boolean('featured')) {
            $query->featured();
        }

        $products = $query->paginate(12);
        $categories = SoftwareProductCategory::active()->ordered()->get();

        return view('software-products.index', compact('products', 'categories'));
    }

    /**
     * Display the specified product
     */
    public function show(string $slug)
    {
        $product = SoftwareProduct::where('slug', $slug)
            ->with(['category', 'activeOptions.activeValues'])
            ->active()
            ->firstOrFail();

        // Increment view count
        $product->incrementViewCount();

        // Get related products
        $relatedProducts = SoftwareProduct::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->active()
            ->featured()
            ->limit(4)
            ->get();

        return view('software-products.show', compact('product', 'relatedProducts'));
    }
}
