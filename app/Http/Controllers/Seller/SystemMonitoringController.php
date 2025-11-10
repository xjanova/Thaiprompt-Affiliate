<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Services\SystemMonitoringService;
use App\Models\VendorStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SystemMonitoringController extends Controller
{
    protected SystemMonitoringService $monitoringService;

    public function __construct(SystemMonitoringService $monitoringService)
    {
        $this->monitoringService = $monitoringService;
    }

    /**
     * Display real-time system monitoring dashboard
     */
    public function index()
    {
        $user = Auth::user();
        $store = VendorStore::where('user_id', $user->id)->first();

        if (!$store) {
            return redirect()->route('seller.dashboard')
                ->with('error', 'Store not found.');
        }

        // Get initial metrics
        $metrics = $this->monitoringService->getRealtimeMetrics();
        $appInfo = $this->monitoringService->getApplicationInfo();
        $network = $this->monitoringService->getNetworkStats();

        return view('seller.analytics.system-monitoring', compact(
            'user',
            'store',
            'metrics',
            'appInfo',
            'network'
        ));
    }

    /**
     * API: Get real-time metrics (for AJAX polling)
     */
    public function apiMetrics()
    {
        $user = Auth::user();
        $store = VendorStore::where('user_id', $user->id)->first();

        if (!$store) {
            return response()->json(['error' => 'Store not found'], 404);
        }

        // Store metrics in history for charts
        $this->monitoringService->storeMetricsHistory();

        // Get current metrics
        $metrics = $this->monitoringService->getRealtimeMetrics();

        // Get historical data for charts
        $history = $this->monitoringService->getHistoricalMetrics();

        return response()->json([
            'success' => true,
            'metrics' => $metrics,
            'history' => $history,
        ]);
    }

    /**
     * API: Get application info
     */
    public function apiApplicationInfo()
    {
        $user = Auth::user();
        $store = VendorStore::where('user_id', $user->id)->first();

        if (!$store) {
            return response()->json(['error' => 'Store not found'], 404);
        }

        $appInfo = $this->monitoringService->getApplicationInfo();

        return response()->json([
            'success' => true,
            'info' => $appInfo,
        ]);
    }
}
