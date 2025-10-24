<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Order;
use App\Models\Product;
use App\Models\Commission;
use App\Models\MlmNetwork;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Show admin dashboard
     */
    public function index()
    {
        $stats = $this->getStatistics();
        $recentOrders = $this->getRecentOrders();
        $topVendors = $this->getTopVendors();
        $recentUsers = $this->getRecentUsers();
        $chartData = $this->getChartData();

        return view('admin.dashboard', compact(
            'stats',
            'recentOrders',
            'topVendors',
            'recentUsers',
            'chartData'
        ));
    }

    /**
     * Get dashboard statistics
     */
    protected function getStatistics(): array
    {
        return [
            'total_users' => User::count(),
            'total_vendors' => Vendor::count(),
            'active_vendors' => Vendor::where('status', 'active')->count(),
            'pending_vendors' => Vendor::where('status', 'pending')->count(),
            'total_products' => Product::count(),
            'total_orders' => Order::count(),
            'total_revenue' => Order::where('payment_status', 'paid')->sum('total_amount'),
            'monthly_revenue' => Order::where('payment_status', 'paid')
                ->whereMonth('created_at', now()->month)
                ->sum('total_amount'),
            'total_commissions' => Commission::sum('amount'),
            'pending_commissions' => Commission::where('status', 'pending')->sum('amount'),
            'mlm_members' => MlmNetwork::distinct('user_id')->count(),
        ];
    }

    /**
     * Get recent orders
     */
    protected function getRecentOrders()
    {
        return Order::with(['user', 'vendor'])
            ->latest()
            ->take(10)
            ->get();
    }

    /**
     * Get top vendors by sales
     */
    protected function getTopVendors()
    {
        return Vendor::withCount('orders')
            ->withSum('orders as total_sales', 'total_amount')
            ->orderBy('total_sales', 'desc')
            ->take(10)
            ->get();
    }

    /**
     * Get recent users
     */
    protected function getRecentUsers()
    {
        return User::with('roles')
            ->latest()
            ->take(10)
            ->get();
    }

    /**
     * Get chart data for dashboard
     */
    protected function getChartData(): array
    {
        // Sales by month (last 12 months)
        $salesByMonth = Order::where('payment_status', 'paid')
            ->where('created_at', '>=', now()->subMonths(12))
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('SUM(total_amount) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // New users by month
        $usersByMonth = User::where('created_at', '>=', now()->subMonths(12))
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Vendors by status
        $vendorsByStatus = Vendor::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();

        // Commission by type
        $commissionByType = Commission::select('type', DB::raw('SUM(amount) as total'))
            ->groupBy('type')
            ->get();

        // Top products by sales
        $topProducts = Product::withCount('orderItems')
            ->orderBy('order_items_count', 'desc')
            ->take(10)
            ->get();

        return [
            'sales_by_month' => $salesByMonth,
            'users_by_month' => $usersByMonth,
            'vendors_by_status' => $vendorsByStatus,
            'commission_by_type' => $commissionByType,
            'top_products' => $topProducts,
        ];
    }

    /**
     * Get API data for charts
     */
    public function getChartDataApi()
    {
        return response()->json($this->getChartData());
    }
}
