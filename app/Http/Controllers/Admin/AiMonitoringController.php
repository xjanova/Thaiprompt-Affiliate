<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AI\AiMonitoringService;
use Illuminate\Http\Request;

class AiMonitoringController extends Controller
{
    private AiMonitoringService $monitoringService;

    public function __construct()
    {
        $this->monitoringService = new AiMonitoringService();
    }

    /**
     * หน้า Monitoring Dashboard
     */
    public function index()
    {
        $summary = $this->monitoringService->getDashboardSummary();

        return view('admin.ai-monitoring.index', compact('summary'));
    }

    /**
     * ดึง Real-time Metrics (สำหรับ AJAX polling)
     */
    public function getRealtime Metrics()
    {
        $metrics = $this->monitoringService->getAllMetrics();

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ]);
    }

    /**
     * ดึง System Metrics
     */
    public function getSystemMetrics()
    {
        $metrics = $this->monitoringService->getSystemMetrics();

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ]);
    }

    /**
     * ดึง Request Metrics
     */
    public function getRequestMetrics(Request $request)
    {
        $minutes = $request->input('minutes', 60);
        $metrics = $this->monitoringService->getRequestMetrics($minutes);

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ]);
    }

    /**
     * ดึง Conversation Metrics
     */
    public function getConversationMetrics()
    {
        $metrics = $this->monitoringService->getConversationMetrics();

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ]);
    }

    /**
     * ดึง Model Metrics
     */
    public function getModelMetrics()
    {
        $metrics = $this->monitoringService->getModelMetrics();

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ]);
    }

    /**
     * ดึง Provider Metrics
     */
    public function getProviderMetrics()
    {
        $metrics = $this->monitoringService->getProviderMetrics();

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ]);
    }

    /**
     * ดึง Time Series Data สำหรับกราฟ
     */
    public function getTimeSeriesData(Request $request)
    {
        $request->validate([
            'metric' => 'required|in:requests,response_time,tokens,cost,cpu,memory,gpu',
            'hours' => 'sometimes|integer|min:1|max:168',
            'interval_minutes' => 'sometimes|integer|min:1|max:60',
        ]);

        $metric = $request->input('metric');
        $hours = $request->input('hours', 24);
        $intervalMinutes = $request->input('interval_minutes', 30);

        $data = $this->monitoringService->getTimeSeriesData($metric, $hours, $intervalMinutes);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * บันทึก System Metrics (เรียกจาก cron job หรือ scheduler)
     */
    public function recordMetrics()
    {
        $this->monitoringService->recordSystemMetrics();

        return response()->json([
            'success' => true,
            'message' => 'บันทึก metrics สำเร็จ',
        ]);
    }

    /**
     * ดึงสรุปสำหรับ Dashboard
     */
    public function getDashboardSummary()
    {
        $summary = $this->monitoringService->getDashboardSummary();

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }
}
