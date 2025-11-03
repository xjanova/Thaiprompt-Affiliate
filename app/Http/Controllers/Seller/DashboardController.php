<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\VendorStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display seller dashboard
     */
    public function index()
    {
        $user = Auth::user();
        $sellerId = $user->id;

        // Get or create vendor store
        $store = VendorStore::where('user_id', $sellerId)->first();

        if (!$store) {
            // Create default store for seller
            $store = VendorStore::create([
                'user_id' => $sellerId,
                'store_name' => $user->name . "'s Store",
                'store_slug' => \Str::slug($user->name . '-store-' . $sellerId),
                'status' => 'active',
                'subscription_status' => 'trial',
                'trial_ends_at' => now()->addDays(30),
            ]);
        }

        // Get package information
        $package = $store->package;

        // Sales statistics
        $totalSales = OrderItem::where('seller_id', $sellerId)->count();
        $pendingSales = OrderItem::where('seller_id', $sellerId)
            ->whereIn('status', ['pending', 'processing'])
            ->count();
        $completedSales = OrderItem::where('seller_id', $sellerId)
            ->where('status', 'completed')
            ->count();

        // Revenue statistics
        $totalRevenue = OrderItem::where('seller_id', $sellerId)
            ->whereHas('order', function ($q) {
                $q->where('payment_status', 'paid');
            })
            ->sum('seller_earning');

        // Today's revenue
        $todayRevenue = OrderItem::where('seller_id', $sellerId)
            ->whereHas('order', function ($q) {
                $q->where('payment_status', 'paid')
                  ->whereDate('created_at', today());
            })
            ->sum('seller_earning');

        // Previous month revenue for growth calculation
        $currentMonthRevenue = OrderItem::where('seller_id', $sellerId)
            ->whereHas('order', function ($q) {
                $q->where('payment_status', 'paid')
                  ->whereMonth('created_at', now()->month)
                  ->whereYear('created_at', now()->year);
            })
            ->sum('seller_earning');

        $previousMonthRevenue = OrderItem::where('seller_id', $sellerId)
            ->whereHas('order', function ($q) {
                $q->where('payment_status', 'paid')
                  ->whereMonth('created_at', now()->subMonth()->month)
                  ->whereYear('created_at', now()->subMonth()->year);
            })
            ->sum('seller_earning');

        $salesGrowth = 0;
        if ($previousMonthRevenue > 0) {
            $salesGrowth = (($currentMonthRevenue - $previousMonthRevenue) / $previousMonthRevenue) * 100;
        }

        // Monthly revenue for the last 12 months
        $monthlyRevenue = collect();
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $revenue = OrderItem::where('seller_id', $sellerId)
                ->whereHas('order', function ($q) use ($date) {
                    $q->where('payment_status', 'paid')
                      ->whereMonth('created_at', $date->month)
                      ->whereYear('created_at', $date->year);
                })
                ->sum('seller_earning');

            $monthlyRevenue->push([
                'month' => $date->format('M Y'),
                'total' => $revenue
            ]);
        }

        // Product stats
        $totalProducts = Product::where('seller_id', $sellerId)->count();
        $activeProducts = Product::where('seller_id', $sellerId)->where('is_active', true)->count();
        $outOfStockProducts = Product::where('seller_id', $sellerId)->outOfStock()->count();
        $lowStockProducts = Product::where('seller_id', $sellerId)->lowStock()->count();

        // Recent orders
        $recentOrders = Order::with(['items' => function ($q) use ($sellerId) {
            $q->where('seller_id', $sellerId);
        }, 'user'])
            ->whereHas('items', function ($q) use ($sellerId) {
                $q->where('seller_id', $sellerId);
            })
            ->latest()
            ->take(10)
            ->get();

        // Top selling products
        $topProducts = Product::where('seller_id', $sellerId)
            ->orderBy('sales_count', 'desc')
            ->take(5)
            ->get();

        // Store visitors (last 30 days) - Mock data for now
        $totalVisitors = rand(500, 5000);
        $conversionRate = $totalSales > 0 ? ($completedSales / $totalSales) * 100 : 0;

        return view('seller.dashboard', compact(
            'user',
            'store',
            'package',
            'totalSales',
            'pendingSales',
            'completedSales',
            'totalRevenue',
            'todayRevenue',
            'monthlyRevenue',
            'salesGrowth',
            'totalProducts',
            'activeProducts',
            'outOfStockProducts',
            'lowStockProducts',
            'recentOrders',
            'topProducts',
            'totalVisitors',
            'conversionRate'
        ));
    }

    /**
     * Display seller analytics
     */
    public function analytics()
    {
        $user = Auth::user();

        // Analytics data (placeholder)
        $analyticsData = [
            'page_views' => 0,
            'unique_visitors' => 0,
            'conversion_rate' => 0,
            'average_order_value' => 0,
        ];

        return view('seller.analytics', compact('analyticsData', 'user'));
    }

    /**
     * Display seller marketing
     */
    public function marketing()
    {
        $user = Auth::user();
        return view('seller.marketing', compact('user'));
    }

    /**
     * Display seller profile
     */
    public function profile()
    {
        $user = Auth::user();
        return view('seller.profile', compact('user'));
    }
}
