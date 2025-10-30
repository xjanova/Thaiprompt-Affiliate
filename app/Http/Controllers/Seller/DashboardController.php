<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Display seller dashboard
     */
    public function index()
    {
        $user = Auth::user();

        // Sales statistics (placeholder - will be implemented later)
        $totalSales = 0;
        $pendingSales = 0;
        $completedSales = 0;
        $totalRevenue = 0;

        // Monthly revenue for the last 12 months
        $monthlyRevenue = collect();
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthlyRevenue->push([
                'month' => $date->format('M Y'),
                'total' => 0 // Placeholder
            ]);
        }

        // Sales growth
        $salesGrowth = 0;

        // Product stats
        $totalProducts = 0;
        $activeProducts = 0;
        $outOfStockProducts = 0;

        // Recent orders (placeholder)
        $recentOrders = collect();

        // Top selling products (placeholder)
        $topProducts = collect();

        return view('seller.dashboard', compact(
            'user',
            'totalSales',
            'pendingSales',
            'completedSales',
            'totalRevenue',
            'monthlyRevenue',
            'salesGrowth',
            'totalProducts',
            'activeProducts',
            'outOfStockProducts',
            'recentOrders',
            'topProducts'
        ));
    }

    /**
     * Display seller products
     */
    public function products()
    {
        $user = Auth::user();
        // Products list will be implemented later
        $products = collect();

        return view('seller.products', compact('products', 'user'));
    }

    /**
     * Display seller sales
     */
    public function sales()
    {
        $user = Auth::user();
        // Sales list will be implemented later
        $sales = collect();

        return view('seller.sales', compact('sales', 'user'));
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
     * Display seller profile
     */
    public function profile()
    {
        $user = Auth::user();
        return view('seller.profile', compact('user'));
    }
}
