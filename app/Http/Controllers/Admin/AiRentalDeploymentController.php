<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiRentalDeployment;
use App\Models\AiRentalCloudConfig;
use App\Models\AiRentalModel;
use App\Services\AiRentalDeploymentService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

/**
 * Deployment Management Controller
 *
 * จัดการ AI Model Deployments บน Cloud GPU
 */
class AiRentalDeploymentController extends Controller
{
    /**
     * Deployment Service
     *
     * @var AiRentalDeploymentService
     */
    protected AiRentalDeploymentService $deploymentService;

    /**
     * Constructor
     *
     * @param AiRentalDeploymentService $deploymentService
     */
    public function __construct(AiRentalDeploymentService $deploymentService)
    {
        $this->deploymentService = $deploymentService;
    }

    /**
     * แสดงรายการ Deployments
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        // ดึง deployments ของ user
        $query = AiRentalDeployment::where('user_id', $user->id)
            ->with(['cloudConfig.cloudProvider', 'model']);

        // กรองตาม status
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // กรองตาม model
        if ($modelId = $request->input('model')) {
            $query->where('model_id', $modelId);
        }

        // กรองตาม config
        if ($configId = $request->input('config')) {
            $query->where('cloud_config_id', $configId);
        }

        // เรียงตามล่าสุด
        $deployments = $query->latest()->paginate(10)->withQueryString();

        // ดึงข้อมูลสำหรับ filters
        $models = AiRentalModel::active()->ordered()->get();
        $configs = AiRentalCloudConfig::where('user_id', $user->id)
            ->active()
            ->get();

        // สถิติ
        $stats = [
            'total' => AiRentalDeployment::where('user_id', $user->id)->count(),
            'running' => AiRentalDeployment::where('user_id', $user->id)->running()->count(),
            'stopped' => AiRentalDeployment::where('user_id', $user->id)->stopped()->count(),
            'failed' => AiRentalDeployment::where('user_id', $user->id)->failed()->count(),
            'total_cost' => AiRentalDeployment::where('user_id', $user->id)->sum('total_cost'),
            'total_hours' => AiRentalDeployment::where('user_id', $user->id)->sum('total_hours'),
        ];

        return view('admin.ai-rental.deployments.index', compact(
            'deployments',
            'models',
            'configs',
            'stats'
        ));
    }

    /**
     * แสดงฟอร์มสร้าง Deployment ใหม่
     *
     * @return View|RedirectResponse
     */
    public function create(): View|RedirectResponse
    {
        $user = Auth::user();

        // ดึง configs ที่เปิดใช้งานของ user
        $configs = AiRentalCloudConfig::where('user_id', $user->id)
            ->active()
            ->with('cloudProvider')
            ->get();

        // เช็คว่ามี config หรือยัง
        if ($configs->isEmpty()) {
            return redirect()
                ->route('admin.ai-rental.configs.create')
                ->with('info', 'กรุณาสร้าง Configuration ก่อนทำการ Deploy');
        }

        // ดึง models ที่เปิดใช้งาน
        $models = AiRentalModel::active()->ordered()->get();

        // Default config (ถ้ามี)
        $defaultConfig = $configs->where('is_default', true)->first() ?? $configs->first();

        return view('admin.ai-rental.deployments.create', compact('configs', 'models', 'defaultConfig'));
    }

    /**
     * บันทึก Deployment ใหม่
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        // Validation
        $validated = $request->validate([
            'cloud_config_id' => 'required|exists:ai_rental_cloud_configs,id',
            'model_id' => 'required|exists:ai_rental_models,id',
            'instance_type' => 'required|string|max:100',
            'deployment_name' => 'nullable|string|max:255',
            'environment_vars' => 'nullable|array',
            'auto_stop_minutes' => 'nullable|integer|min:5|max:1440',
        ]);

        // เช็คว่า config เป็นของ user หรือไม่
        $config = AiRentalCloudConfig::where('id', $validated['cloud_config_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        // โหลด model
        $model = AiRentalModel::findOrFail($validated['model_id']);

        try {
            // สร้าง deployment ผ่าน service
            $deployment = $this->deploymentService->createDeployment(
                user: $user,
                config: $config,
                model: $model,
                instanceType: $validated['instance_type'],
                deploymentName: $validated['deployment_name'] ?? null,
                environmentVars: $validated['environment_vars'] ?? [],
                autoStopMinutes: $validated['auto_stop_minutes'] ?? null
            );

            return redirect()
                ->route('admin.ai-rental.deployments.show', $deployment)
                ->with('success', "เริ่ม Deployment '{$deployment->name}' สำเร็จ! กำลังเตรียมระบบ...");

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', "ไม่สามารถสร้าง Deployment ได้: {$e->getMessage()}");
        }
    }

    /**
     * แสดงรายละเอียด Deployment
     *
     * @param AiRentalDeployment $deployment
     * @return View
     */
    public function show(AiRentalDeployment $deployment): View
    {
        // เช็คสิทธิ์
        $this->authorize('view', $deployment);

        // โหลด relationships
        $deployment->load([
            'cloudConfig.cloudProvider',
            'model',
            'user'
        ]);

        // คำนวณ metrics
        $metrics = [
            'uptime_minutes' => $deployment->getUptimeMinutes(),
            'estimated_cost' => $deployment->getEstimatedCost(),
            'cost_per_hour' => $deployment->getCostPerHour(),
            'status_color' => $this->getStatusColor($deployment->status),
            'can_start' => in_array($deployment->status, ['stopped', 'failed']),
            'can_stop' => $deployment->status === 'running',
            'can_restart' => $deployment->status === 'running',
        ];

        return view('admin.ai-rental.deployments.show', compact('deployment', 'metrics'));
    }

    /**
     * ลบ Deployment
     *
     * @param AiRentalDeployment $deployment
     * @return RedirectResponse
     */
    public function destroy(AiRentalDeployment $deployment): RedirectResponse
    {
        // เช็คสิทธิ์
        $this->authorize('delete', $deployment);

        // ห้ามลบถ้ากำลังรันอยู่
        if ($deployment->status === 'running') {
            return redirect()
                ->back()
                ->with('error', 'กรุณาหยุด Deployment ก่อนลบ');
        }

        $name = $deployment->name;

        try {
            // ลบ deployment
            $deployment->delete();

            return redirect()
                ->route('admin.ai-rental.deployments.index')
                ->with('success', "ลบ Deployment '{$name}' สำเร็จ");

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', "ไม่สามารถลบ Deployment ได้: {$e->getMessage()}");
        }
    }

    /**
     * เริ่ม Deployment
     *
     * @param AiRentalDeployment $deployment
     * @return RedirectResponse
     */
    public function start(AiRentalDeployment $deployment): RedirectResponse
    {
        // เช็คสิทธิ์
        $this->authorize('update', $deployment);

        // เช็คว่าหยุดอยู่หรือไม่
        if (!in_array($deployment->status, ['stopped', 'failed'])) {
            return redirect()
                ->back()
                ->with('error', 'Deployment นี้ไม่สามารถเริ่มได้ในขณะนี้');
        }

        try {
            // เริ่ม deployment ผ่าน service
            $this->deploymentService->startDeployment($deployment);

            return redirect()
                ->back()
                ->with('success', "กำลังเริ่ม Deployment '{$deployment->name}'...");

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', "ไม่สามารถเริ่ม Deployment ได้: {$e->getMessage()}");
        }
    }

    /**
     * หยุด Deployment
     *
     * @param AiRentalDeployment $deployment
     * @return RedirectResponse
     */
    public function stop(AiRentalDeployment $deployment): RedirectResponse
    {
        // เช็คสิทธิ์
        $this->authorize('update', $deployment);

        // เช็คว่ากำลังรันอยู่หรือไม่
        if ($deployment->status !== 'running') {
            return redirect()
                ->back()
                ->with('error', 'Deployment นี้ไม่ได้รันอยู่');
        }

        try {
            // หยุด deployment ผ่าน service
            $this->deploymentService->stopDeployment($deployment);

            return redirect()
                ->back()
                ->with('success', "กำลังหยุด Deployment '{$deployment->name}'...");

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', "ไม่สามารถหยุด Deployment ได้: {$e->getMessage()}");
        }
    }

    /**
     * รีสตาร์ท Deployment
     *
     * @param AiRentalDeployment $deployment
     * @return RedirectResponse
     */
    public function restart(AiRentalDeployment $deployment): RedirectResponse
    {
        // เช็คสิทธิ์
        $this->authorize('update', $deployment);

        // เช็คว่ากำลังรันอยู่หรือไม่
        if ($deployment->status !== 'running') {
            return redirect()
                ->back()
                ->with('error', 'Deployment ต้องรันอยู่ถึงจะ restart ได้');
        }

        try {
            // รีสตาร์ท deployment ผ่าน service
            $this->deploymentService->restartDeployment($deployment);

            return redirect()
                ->back()
                ->with('success', "กำลัง Restart Deployment '{$deployment->name}'...");

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', "ไม่สามารถ Restart Deployment ได้: {$e->getMessage()}");
        }
    }

    /**
     * ดู Logs
     *
     * @param AiRentalDeployment $deployment
     * @return View
     */
    public function logs(AiRentalDeployment $deployment): View
    {
        // เช็คสิทธิ์
        $this->authorize('view', $deployment);

        // โหลด relationships
        $deployment->load(['cloudConfig.cloudProvider', 'model']);

        // ดึง logs (mock data สำหรับตอนนี้)
        $logs = $deployment->logs ?? $this->getMockLogs();

        return view('admin.ai-rental.deployments.logs', compact('deployment', 'logs'));
    }

    /**
     * ดึง Logs แบบ real-time (AJAX)
     *
     * @param AiRentalDeployment $deployment
     * @return JsonResponse
     */
    public function fetchLogs(AiRentalDeployment $deployment): JsonResponse
    {
        // เช็คสิทธิ์
        $this->authorize('view', $deployment);

        try {
            // ดึง logs ผ่าน service
            $logs = $this->deploymentService->fetchLogs($deployment);

            return response()->json([
                'success' => true,
                'logs' => $logs,
                'status' => $deployment->status,
                'uptime' => $deployment->getUptimeMinutes(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * อัพเดท Status (Background job callback)
     *
     * @param Request $request
     * @param AiRentalDeployment $deployment
     * @return JsonResponse
     */
    public function updateStatus(Request $request, AiRentalDeployment $deployment): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,starting,running,stopping,stopped,failed,error',
            'instance_id' => 'nullable|string',
            'endpoint_url' => 'nullable|url',
            'error_message' => 'nullable|string',
        ]);

        try {
            $deployment->update([
                'status' => $validated['status'],
                'instance_id' => $validated['instance_id'] ?? $deployment->instance_id,
                'endpoint_url' => $validated['endpoint_url'] ?? $deployment->endpoint_url,
            ]);

            // ถ้า status เป็น running ให้บันทึก deployed_at
            if ($validated['status'] === 'running' && !$deployment->deployed_at) {
                $deployment->update(['deployed_at' => now()]);
            }

            // ถ้า status เป็น stopped ให้บันทึก stopped_at และคำนวณต้นทุน
            if ($validated['status'] === 'stopped' && !$deployment->stopped_at) {
                $this->deploymentService->finalizeDeployment($deployment);
            }

            return response()->json([
                'success' => true,
                'message' => 'อัพเดท status สำเร็จ',
                'deployment' => $deployment->fresh(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * แสดงหน้า Test Deployment
     *
     * @param AiRentalDeployment $deployment
     * @return View
     */
    public function test(AiRentalDeployment $deployment): View
    {
        // เช็คสิทธิ์
        $this->authorize('view', $deployment);

        // โหลด relationships
        $deployment->load(['cloudConfig.cloudProvider', 'model']);

        return view('admin.ai-rental.deployments.test', compact('deployment'));
    }

    /**
     * แสดงหน้า Analytics Dashboard
     *
     * @param Request $request
     * @return View
     */
    public function analytics(Request $request): View
    {
        $user = Auth::user();

        // ดึงข้อมูลสถิติ
        $stats = [
            'total_deployments' => AiRentalDeployment::where('user_id', $user->id)->count(),
            'running_now' => AiRentalDeployment::where('user_id', $user->id)->running()->count(),
            'total_hours' => AiRentalDeployment::where('user_id', $user->id)->sum('total_hours'),
            'total_cost' => AiRentalDeployment::where('user_id', $user->id)->sum('total_cost'),
        ];

        // Top Models (Top 5)
        $stats['top_models'] = AiRentalDeployment::where('user_id', $user->id)
            ->selectRaw('model_id, COUNT(*) as deployments, SUM(total_hours) as hours')
            ->groupBy('model_id')
            ->orderByDesc('deployments')
            ->limit(5)
            ->with('model')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->model->name ?? 'Unknown',
                    'deployments' => $item->deployments,
                    'hours' => round($item->hours, 1),
                ];
            });

        // Top Providers (Top 3)
        $stats['top_providers'] = AiRentalDeployment::where('user_id', $user->id)
            ->selectRaw('cloud_config_id, COUNT(*) as deployments, SUM(total_cost) as cost')
            ->groupBy('cloud_config_id')
            ->orderByDesc('cost')
            ->limit(3)
            ->with('cloudConfig.cloudProvider')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->cloudConfig->cloudProvider->name ?? 'Unknown',
                    'deployments' => $item->deployments,
                    'cost' => round($item->cost, 2),
                ];
            });

        // Recent Activities (Last 10)
        $stats['recent_activities'] = AiRentalDeployment::where('user_id', $user->id)
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($deployment) {
                $statusMessages = [
                    'running' => "Deployment '{$deployment->name}' is running",
                    'stopped' => "Deployment '{$deployment->name}' was stopped",
                    'failed' => "Deployment '{$deployment->name}' failed",
                ];
                return [
                    'message' => $statusMessages[$deployment->status] ?? "Deployment '{$deployment->name}' created",
                    'time' => $deployment->updated_at->diffForHumans(),
                ];
            });

        // ดึงรายการ deployments สำหรับตาราง (Latest 20)
        $deployments = AiRentalDeployment::where('user_id', $user->id)
            ->with(['model', 'cloudConfig.cloudProvider'])
            ->latest()
            ->limit(20)
            ->get();

        return view('admin.ai-rental.analytics', compact('stats', 'deployments'));
    }

    /**
     * ดึงสถิติแบบ Real-time (API)
     *
     * @return JsonResponse
     */
    public function getStats(): JsonResponse
    {
        $user = Auth::user();

        $stats = [
            'total_deployments' => AiRentalDeployment::where('user_id', $user->id)->count(),
            'running_now' => AiRentalDeployment::where('user_id', $user->id)->running()->count(),
            'stopped' => AiRentalDeployment::where('user_id', $user->id)->stopped()->count(),
            'failed' => AiRentalDeployment::where('user_id', $user->id)->failed()->count(),
            'total_hours' => AiRentalDeployment::where('user_id', $user->id)->sum('total_hours'),
            'total_cost' => AiRentalDeployment::where('user_id', $user->id)->sum('total_cost'),
            'total_requests' => AiRentalDeployment::where('user_id', $user->id)->sum('total_requests'),
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * ดึงข้อมูล Chart แบบ Real-time (API)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getChartData(Request $request): JsonResponse
    {
        $user = Auth::user();
        $days = $request->input('days', 7);

        // Cost Over Time (Last N days)
        $costData = [];
        $labels = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('M d');

            $cost = AiRentalDeployment::where('user_id', $user->id)
                ->whereDate('created_at', $date->toDateString())
                ->sum('total_cost');

            $costData[] = round($cost, 2);
        }

        // GPU Usage Distribution
        $gpuUsage = AiRentalDeployment::where('user_id', $user->id)
            ->selectRaw('instance_type, COUNT(*) as count, SUM(total_hours) as hours')
            ->groupBy('instance_type')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->instance_type,
                    'count' => $item->count,
                    'hours' => round($item->hours, 1),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'cost_over_time' => [
                    'labels' => $labels,
                    'data' => $costData,
                ],
                'gpu_usage' => $gpuUsage,
            ],
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * ดึงสีสำหรับ status
     *
     * @param string $status
     * @return string
     */
    protected function getStatusColor(string $status): string
    {
        return match($status) {
            'running' => 'green',
            'starting' => 'blue',
            'stopping' => 'yellow',
            'stopped' => 'gray',
            'failed', 'error' => 'red',
            default => 'gray',
        };
    }

    /**
     * สร้าง Mock Logs (สำหรับทดสอบ)
     *
     * @return array
     */
    protected function getMockLogs(): array
    {
        return [
            [
                'timestamp' => now()->subMinutes(10)->toIso8601String(),
                'level' => 'info',
                'message' => 'Starting deployment...',
            ],
            [
                'timestamp' => now()->subMinutes(9)->toIso8601String(),
                'level' => 'info',
                'message' => 'Pulling Docker image...',
            ],
            [
                'timestamp' => now()->subMinutes(7)->toIso8601String(),
                'level' => 'info',
                'message' => 'Loading model weights...',
            ],
            [
                'timestamp' => now()->subMinutes(5)->toIso8601String(),
                'level' => 'success',
                'message' => 'Model loaded successfully',
            ],
            [
                'timestamp' => now()->subMinutes(4)->toIso8601String(),
                'level' => 'info',
                'message' => 'Starting API server on port 8000...',
            ],
            [
                'timestamp' => now()->subMinutes(3)->toIso8601String(),
                'level' => 'success',
                'message' => 'Deployment is ready! Endpoint: https://api.example.com/v1/inference',
            ],
        ];
    }
}
