<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PosDevice;
use App\Models\PosTransaction;
use App\Models\PosSession;
use App\Models\VendorStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosDashboardController extends Controller
{
    public function index()
    {
        $stats = $this->getStatistics();
        $recentTransactions = $this->getRecentTransactions();
        $topStores = $this->getTopStores();
        $deviceStatus = $this->getDeviceStatus();

        return view('admin.pos.dashboard', compact(
            'stats',
            'recentTransactions',
            'topStores',
            'deviceStatus'
        ));
    }

    protected function getStatistics()
    {
        $today = today();
        $thisMonth = now()->startOfMonth();

        return [
            // Device Statistics
            'total_devices' => PosDevice::count(),
            'active_devices' => PosDevice::active()->count(),
            'online_devices' => PosDevice::online()->count(),
            'trial_devices' => PosDevice::where('subscription_status', 'trial')->count(),
            'subscribed_devices' => PosDevice::where('subscription_status', 'active')->count(),
            'expired_devices' => PosDevice::where('subscription_status', 'expired')->count(),

            // Transaction Statistics
            'total_transactions' => PosTransaction::count(),
            'today_transactions' => PosTransaction::whereDate('transaction_date', $today)->count(),
            'month_transactions' => PosTransaction::whereDate('transaction_date', '>=', $thisMonth)->count(),

            // Sales Statistics
            'total_sales' => PosTransaction::sum('total_amount'),
            'today_sales' => PosTransaction::whereDate('transaction_date', $today)->sum('total_amount'),
            'month_sales' => PosTransaction::whereDate('transaction_date', '>=', $thisMonth)->sum('total_amount'),
            'average_transaction' => PosTransaction::avg('total_amount'),

            // Session Statistics
            'active_sessions' => PosSession::open()->count(),
            'total_sessions' => PosSession::count(),
            'today_sessions' => PosSession::whereDate('opened_at', $today)->count(),

            // Payment Methods
            'cash_sales' => PosTransaction::where('payment_method', 'cash')->sum('total_amount'),
            'card_sales' => PosTransaction::where('payment_method', 'card')->sum('total_amount'),
            'qr_sales' => PosTransaction::where('payment_method', 'qr')->sum('total_amount'),

            // Refund Statistics
            'total_refunds' => PosTransaction::where('status', 'refunded')->count(),
            'refund_amount' => PosTransaction::where('status', 'refunded')->sum('total_amount'),
            'refund_rate' => $this->calculateRefundRate(),
        ];
    }

    protected function getRecentTransactions($limit = 10)
    {
        return PosTransaction::with(['posDevice', 'store', 'user'])
            ->latest('transaction_date')
            ->limit($limit)
            ->get();
    }

    protected function getTopStores($limit = 10)
    {
        $storeStats = DB::table('pos_transactions')
            ->select('store_id')
            ->selectRaw('COUNT(id) as transaction_count')
            ->selectRaw('SUM(total_amount) as total_sales')
            ->whereDate('transaction_date', '>=', now()->startOfMonth())
            ->whereNotNull('store_id')
            ->groupBy('store_id')
            ->orderByDesc('total_sales')
            ->limit($limit)
            ->get();

        $storeIds = $storeStats->pluck('store_id');

        $stores = VendorStore::whereIn('id', $storeIds)->get()->keyBy('id');

        return $storeStats->map(function ($stat) use ($stores) {
            $store = $stores->get($stat->store_id);
            if ($store) {
                $store->transaction_count = $stat->transaction_count;
                $store->total_sales = $stat->total_sales;
                return $store;
            }
            return null;
        })->filter();
    }

    protected function getDeviceStatus()
    {
        return [
            'by_status' => PosDevice::select('subscription_status')
                ->selectRaw('COUNT(*) as count')
                ->groupBy('subscription_status')
                ->get()
                ->pluck('count', 'subscription_status'),

            'by_type' => PosDevice::select('device_type')
                ->selectRaw('COUNT(*) as count')
                ->groupBy('device_type')
                ->get()
                ->pluck('count', 'device_type'),

            'online_offline' => [
                'online' => PosDevice::where('is_online', true)->count(),
                'offline' => PosDevice::where('is_online', false)->count(),
            ],
        ];
    }

    protected function calculateRefundRate()
    {
        $totalTransactions = PosTransaction::count();
        if ($totalTransactions === 0) {
            return 0;
        }

        $refundCount = PosTransaction::where('status', 'refunded')->count();
        return round(($refundCount / $totalTransactions) * 100, 2);
    }

    public function salesChart(Request $request)
    {
        $period = $request->get('period', '7days'); // 7days, 30days, 12months

        $data = match ($period) {
            '7days' => $this->getSalesDataByDays(7),
            '30days' => $this->getSalesDataByDays(30),
            '12months' => $this->getSalesDataByMonths(12),
            default => $this->getSalesDataByDays(7),
        };

        return response()->json($data);
    }

    protected function getSalesDataByDays($days)
    {
        $data = PosTransaction::select(
                DB::raw('DATE(transaction_date) as date'),
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(total_amount) as total_sales')
            )
            ->whereDate('transaction_date', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $data->pluck('date')->map(fn($date) => date('M d', strtotime($date))),
            'transactions' => $data->pluck('transaction_count'),
            'sales' => $data->pluck('total_sales'),
        ];
    }

    protected function getSalesDataByMonths($months)
    {
        $data = PosTransaction::select(
                DB::raw('DATE_FORMAT(transaction_date, "%Y-%m") as month'),
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(total_amount) as total_sales')
            )
            ->whereDate('transaction_date', '>=', now()->subMonths($months))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return [
            'labels' => $data->pluck('month')->map(fn($month) => date('M Y', strtotime($month . '-01'))),
            'transactions' => $data->pluck('transaction_count'),
            'sales' => $data->pluck('total_sales'),
        ];
    }

    public function analytics(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->subDays(30));
        $dateTo = $request->get('date_to', now());

        $analytics = [
            'payment_methods' => $this->getPaymentMethodAnalytics($dateFrom, $dateTo),
            'hourly_sales' => $this->getHourlySalesAnalytics($dateFrom, $dateTo),
            'device_performance' => $this->getDevicePerformance($dateFrom, $dateTo),
            'store_performance' => $this->getStorePerformance($dateFrom, $dateTo),
        ];

        return view('admin.pos.analytics', compact('analytics', 'dateFrom', 'dateTo'));
    }

    protected function getPaymentMethodAnalytics($dateFrom, $dateTo)
    {
        return PosTransaction::select('payment_method')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('SUM(total_amount) as total')
            ->whereBetween('transaction_date', [$dateFrom, $dateTo])
            ->groupBy('payment_method')
            ->get();
    }

    protected function getHourlySalesAnalytics($dateFrom, $dateTo)
    {
        return PosTransaction::select(
                DB::raw('HOUR(transaction_date) as hour'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total_amount) as total')
            )
            ->whereBetween('transaction_date', [$dateFrom, $dateTo])
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();
    }

    protected function getDevicePerformance($dateFrom, $dateTo, $limit = 20)
    {
        $deviceStats = DB::table('pos_transactions')
            ->select('pos_device_id')
            ->selectRaw('COUNT(id) as transaction_count')
            ->selectRaw('SUM(total_amount) as total_sales')
            ->whereBetween('transaction_date', [$dateFrom, $dateTo])
            ->whereNotNull('pos_device_id')
            ->groupBy('pos_device_id')
            ->orderByDesc('total_sales')
            ->limit($limit)
            ->get();

        $deviceIds = $deviceStats->pluck('pos_device_id');

        $devices = PosDevice::with('store')->whereIn('id', $deviceIds)->get()->keyBy('id');

        return $deviceStats->map(function ($stat) use ($devices) {
            $device = $devices->get($stat->pos_device_id);
            if ($device) {
                $device->transaction_count = $stat->transaction_count;
                $device->total_sales = $stat->total_sales;
                return $device;
            }
            return null;
        })->filter();
    }

    protected function getStorePerformance($dateFrom, $dateTo, $limit = 20)
    {
        $storeStats = DB::table('pos_transactions')
            ->select('store_id')
            ->selectRaw('COUNT(id) as transaction_count')
            ->selectRaw('SUM(total_amount) as total_sales')
            ->selectRaw('AVG(total_amount) as average_sale')
            ->whereBetween('transaction_date', [$dateFrom, $dateTo])
            ->whereNotNull('store_id')
            ->groupBy('store_id')
            ->orderByDesc('total_sales')
            ->limit($limit)
            ->get();

        $storeIds = $storeStats->pluck('store_id');

        $stores = VendorStore::whereIn('id', $storeIds)->get()->keyBy('id');

        return $storeStats->map(function ($stat) use ($stores) {
            $store = $stores->get($stat->store_id);
            if ($store) {
                $store->transaction_count = $stat->transaction_count;
                $store->total_sales = $stat->total_sales;
                $store->average_sale = $stat->average_sale;
                return $store;
            }
            return null;
        })->filter();
    }
}
