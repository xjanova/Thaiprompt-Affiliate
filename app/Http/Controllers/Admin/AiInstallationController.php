<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AI\SystemRequirementsChecker;
use App\Services\AI\ModelRecommendationService;
use App\Services\AI\AiInstallationService;
use App\Models\AiInstallationLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AiInstallationController extends Controller
{
    private SystemRequirementsChecker $requirementsChecker;
    private ModelRecommendationService $recommendationService;
    private AiInstallationService $installationService;

    public function __construct()
    {
        $this->requirementsChecker = new SystemRequirementsChecker();
        $this->recommendationService = new ModelRecommendationService();
        $this->installationService = new AiInstallationService();
    }

    /**
     * หน้าแรกของ Installation Wizard
     */
    public function index()
    {
        $ollamaStatus = $this->installationService->checkOllamaStatus();
        $installedModels = $this->installationService->getInstalledModels();

        // ดึง installation logs ล่าสุด
        $recentLogs = AiInstallationLog::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('admin.ai-installation.index', compact(
            'ollamaStatus',
            'installedModels',
            'recentLogs'
        ));
    }

    /**
     * ตรวจสอบทรัพยากรระบบ
     */
    public function checkRequirements()
    {
        $systemInfo = $this->requirementsChecker->checkAll();

        return response()->json([
            'success' => true,
            'data' => $systemInfo,
        ]);
    }

    /**
     * รับคำแนะนำโมเดล
     */
    public function getRecommendations()
    {
        $recommendations = $this->recommendationService->getRecommendations();

        return response()->json([
            'success' => true,
            'data' => $recommendations,
        ]);
    }

    /**
     * วิเคราะห์โมเดลเฉพาะ
     */
    public function analyzeModel(Request $request)
    {
        $request->validate([
            'model_id' => 'required|string',
        ]);

        $analysis = $this->recommendationService->analyzeModel($request->model_id);

        return response()->json([
            'success' => true,
            'data' => $analysis,
        ]);
    }

    /**
     * เริ่มการติดตั้ง
     */
    public function startInstallation(Request $request)
    {
        $request->validate([
            'installation_type' => 'required|in:deepseek,ollama,custom',
            'model_id' => 'required|string',
            'quantization' => 'required|string',
        ]);

        // ตรวจสอบว่ามีการติดตั้งที่กำลังรันอยู่หรือไม่
        $activeInstallation = AiInstallationLog::where('user_id', Auth::id())
            ->active()
            ->first();

        if ($activeInstallation) {
            return response()->json([
                'success' => false,
                'message' => 'มีการติดตั้งที่กำลังดำเนินการอยู่แล้ว',
                'active_installation_id' => $activeInstallation->id,
            ], 409);
        }

        // สร้าง installation log
        $log = $this->installationService->startInstallation(
            Auth::id(),
            $request->installation_type,
            $request->model_id,
            $request->quantization,
            $request->input('config', [])
        );

        // รันการติดตั้งใน background
        // TODO: ใช้ Queue Job สำหรับการติดตั้งแบบ async
        dispatch(function () use ($log) {
            $this->installationService->installDeepSeek($log);
        })->afterResponse();

        return response()->json([
            'success' => true,
            'message' => 'เริ่มการติดตั้งแล้ว',
            'installation_id' => $log->id,
            'data' => $log,
        ]);
    }

    /**
     * ตรวจสอบ progress การติดตั้ง
     */
    public function getProgress($installationId)
    {
        $log = AiInstallationLog::where('id', $installationId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $log->id,
                'status' => $log->status,
                'progress_percentage' => $log->progress_percentage,
                'current_step' => $log->current_step,
                'total_steps' => $log->total_steps,
                'is_completed' => $log->isCompleted(),
                'is_failed' => $log->isFailed(),
                'error_message' => $log->error_message,
                'duration' => $log->getFormattedDuration(),
                'log_output' => $log->log_output,
            ],
        ]);
    }

    /**
     * ยกเลิกการติดตั้ง
     */
    public function cancelInstallation($installationId)
    {
        $log = AiInstallationLog::where('id', $installationId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if (!$log->isInProgress()) {
            return response()->json([
                'success' => false,
                'message' => 'การติดตั้งนี้ไม่สามารถยกเลิกได้',
            ], 400);
        }

        $this->installationService->cancelInstallation($log);

        return response()->json([
            'success' => true,
            'message' => 'ยกเลิกการติดตั้งแล้ว',
        ]);
    }

    /**
     * รายการโมเดลที่ติดตั้งแล้ว
     */
    public function getInstalledModels()
    {
        $models = $this->installationService->getInstalledModels();

        return response()->json([
            'success' => true,
            'data' => $models,
        ]);
    }

    /**
     * ลบโมเดล
     */
    public function uninstallModel(Request $request)
    {
        $request->validate([
            'model_id' => 'required|string',
        ]);

        $success = $this->installationService->uninstallModel($request->model_id);

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'ลบโมเดลเรียบร้อยแล้ว',
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถลบโมเดลได้',
            ], 500);
        }
    }

    /**
     * ตรวจสอบสถานะ Ollama
     */
    public function checkOllamaStatus()
    {
        $status = $this->installationService->checkOllamaStatus();

        return response()->json([
            'success' => true,
            'data' => $status,
        ]);
    }

    /**
     * ประวัติการติดตั้ง
     */
    public function getInstallationHistory()
    {
        $logs = AiInstallationLog::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    /**
     * ดูรายละเอียด installation log
     */
    public function getInstallationLog($installationId)
    {
        $log = AiInstallationLog::where('id', $installationId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $log,
        ]);
    }

    /**
     * คำนวณพื้นที่ disk ที่ต้องการ
     */
    public function calculateDiskSpace(Request $request)
    {
        $request->validate([
            'model_id' => 'required|string',
            'quantization' => 'required|string',
        ]);

        $diskSpace = $this->recommendationService->calculateDiskSpace(
            $request->model_id,
            $request->quantization
        );

        return response()->json([
            'success' => true,
            'data' => $diskSpace,
        ]);
    }

    /**
     * รับการตั้งค่าที่เหมาะสม
     */
    public function getOptimalSettings(Request $request)
    {
        $request->validate([
            'model_id' => 'required|string',
        ]);

        $systemInfo = $this->requirementsChecker->checkAll();
        $settings = $this->recommendationService->getOptimalSettings(
            $request->model_id,
            $systemInfo
        );

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }
}
