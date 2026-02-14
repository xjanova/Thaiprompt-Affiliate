<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiContentGeneration;
use App\Models\AiContentProject;
use App\Models\AiContentSetting;
use App\Models\AiContentTemplate;
use App\Models\AiContentUsageLog;
use App\Services\AiContentWriter\ClaudeService;
use App\Services\AiContentWriter\ContentWriterService;
use App\Services\AiContentWriter\GeminiService;
use App\Services\AiContentWriter\OpenAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * AI Content Writer Controller
 *
 * จัดการระบบ AI Content Writer ในส่วน Admin
 * รวมถึง Dashboard, Settings, Templates, Projects, Generations
 */
class AiContentWriterController extends Controller
{
    /**
     * Content Writer Service
     */
    protected ContentWriterService $writerService;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->writerService = new ContentWriterService;
    }

    // =========================================
    // Dashboard
    // =========================================

    /**
     * แสดงหน้า Dashboard
     *
     * @return \Illuminate\View\View
     */
    public function dashboard()
    {
        $stats = $this->writerService->getStatistics();
        $apiStatus = $this->writerService->checkAllApiStatus();

        // โปรเจกต์ล่าสุด
        $recentProjects = AiContentProject::with(['template', 'user'])
            ->latest()
            ->limit(10)
            ->get();

        // Generations ล่าสุด
        $recentGenerations = AiContentGeneration::with(['project', 'user'])
            ->latest()
            ->limit(10)
            ->get();

        // Templates ยอดนิยม
        $popularTemplates = AiContentTemplate::active()
            ->popular()
            ->limit(5)
            ->get();

        return view('admin.ai-content-writer.dashboard', [
            'stats' => $stats,
            'apiStatus' => $apiStatus,
            'recentProjects' => $recentProjects,
            'recentGenerations' => $recentGenerations,
            'popularTemplates' => $popularTemplates,
            'pageTitle' => 'AI Content Writer Dashboard',
        ]);
    }

    // =========================================
    // Settings
    // =========================================

    /**
     * แสดงหน้า Settings
     *
     * @return \Illuminate\View\View
     */
    public function settings()
    {
        $settingsByGroup = AiContentSetting::active()
            ->ordered()
            ->get()
            ->groupBy('group');

        $providers = $this->writerService->getAvailableProviders();

        return view('admin.ai-content-writer.settings', [
            'settingsByGroup' => $settingsByGroup,
            'providers' => $providers,
            'groups' => AiContentSetting::GROUPS,
            'pageTitle' => 'ตั้งค่า AI Content Writer',
        ]);
    }

    /**
     * บันทึก Settings
     */
    public function saveSettings(Request $request): JsonResponse
    {
        try {
            $settings = $request->input('settings', []);

            foreach ($settings as $key => $value) {
                $setting = AiContentSetting::where('key', $key)->first();

                if ($setting) {
                    $setting->setEncodedValue($value);
                    $setting->save();
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'บันทึกการตั้งค่าเรียบร้อยแล้ว',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * ทดสอบการเชื่อมต่อ API
     */
    public function testApiConnection(string $provider): JsonResponse
    {
        $result = $this->writerService->testProvider($provider);

        return response()->json($result);
    }

    // =========================================
    // Templates
    // =========================================

    /**
     * แสดงรายการ Templates
     *
     * @return \Illuminate\View\View
     */
    public function templates(Request $request)
    {
        $query = AiContentTemplate::query();

        // ค้นหา
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // กรองตาม content_type
        if ($request->filled('type')) {
            $query->ofType($request->get('type'));
        }

        // กรองตาม category
        if ($request->filled('category')) {
            $query->inCategory($request->get('category'));
        }

        $templates = $query->ordered()->paginate(12);

        return view('admin.ai-content-writer.templates.index', [
            'templates' => $templates,
            'contentTypes' => AiContentTemplate::CONTENT_TYPES,
            'categories' => AiContentTemplate::CATEGORIES,
            'pageTitle' => 'จัดการเทมเพลต',
        ]);
    }

    /**
     * แสดงฟอร์มสร้าง Template
     *
     * @return \Illuminate\View\View
     */
    public function createTemplate()
    {
        return view('admin.ai-content-writer.templates.create', [
            'contentTypes' => AiContentTemplate::CONTENT_TYPES,
            'categories' => AiContentTemplate::CATEGORIES,
            'providers' => AiContentTemplate::AI_PROVIDERS,
            'tones' => AiContentTemplate::TONES,
            'outputFormats' => AiContentTemplate::OUTPUT_FORMATS,
            'models' => $this->getAllModels(),
            'pageTitle' => 'สร้างเทมเพลตใหม่',
        ]);
    }

    /**
     * บันทึก Template ใหม่
     */
    public function storeTemplate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'content_type' => 'required|string',
            'category' => 'nullable|string',
            'system_prompt' => 'nullable|string',
            'user_prompt_template' => 'required|string',
            'variables' => 'nullable|array',
            'default_provider' => 'required|string',
            'default_model' => 'nullable|string',
            'temperature' => 'nullable|numeric|min:0|max:2',
            'max_tokens' => 'nullable|integer|min:100|max:8000',
            'output_format' => 'nullable|string',
            'language' => 'nullable|string',
            'tone' => 'nullable|string',
            'icon' => 'nullable|string',
            'color' => 'nullable|string',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        try {
            $validated['uuid'] = (string) Str::uuid();
            $validated['slug'] = Str::slug($validated['name']);
            $validated['created_by'] = auth()->id();

            $template = AiContentTemplate::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'สร้างเทมเพลตเรียบร้อยแล้ว',
                'data' => $template,
                'redirect' => route('admin.ai-content-writer.templates'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * แสดงฟอร์มแก้ไข Template
     *
     * @return \Illuminate\View\View
     */
    public function editTemplate(int $id)
    {
        $template = AiContentTemplate::findOrFail($id);

        return view('admin.ai-content-writer.templates.edit', [
            'template' => $template,
            'contentTypes' => AiContentTemplate::CONTENT_TYPES,
            'categories' => AiContentTemplate::CATEGORIES,
            'providers' => AiContentTemplate::AI_PROVIDERS,
            'tones' => AiContentTemplate::TONES,
            'outputFormats' => AiContentTemplate::OUTPUT_FORMATS,
            'models' => $this->getAllModels(),
            'pageTitle' => 'แก้ไขเทมเพลต: '.$template->name,
        ]);
    }

    /**
     * อัพเดท Template
     */
    public function updateTemplate(Request $request, int $id): JsonResponse
    {
        $template = AiContentTemplate::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'content_type' => 'required|string',
            'category' => 'nullable|string',
            'system_prompt' => 'nullable|string',
            'user_prompt_template' => 'required|string',
            'variables' => 'nullable|array',
            'default_provider' => 'required|string',
            'default_model' => 'nullable|string',
            'temperature' => 'nullable|numeric|min:0|max:2',
            'max_tokens' => 'nullable|integer|min:100|max:8000',
            'output_format' => 'nullable|string',
            'language' => 'nullable|string',
            'tone' => 'nullable|string',
            'icon' => 'nullable|string',
            'color' => 'nullable|string',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        try {
            $template->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'อัพเดทเทมเพลตเรียบร้อยแล้ว',
                'data' => $template,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * ลบ Template
     */
    public function deleteTemplate(int $id): JsonResponse
    {
        try {
            $template = AiContentTemplate::findOrFail($id);
            $template->delete();

            return response()->json([
                'success' => true,
                'message' => 'ลบเทมเพลตเรียบร้อยแล้ว',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
            ], 500);
        }
    }

    // =========================================
    // Projects
    // =========================================

    /**
     * แสดงรายการ Projects
     *
     * @return \Illuminate\View\View
     */
    public function projects(Request $request)
    {
        $query = AiContentProject::with(['template', 'user']);

        // ค้นหา
        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->get('search').'%');
        }

        // กรองตาม status
        if ($request->filled('status')) {
            $query->withStatus($request->get('status'));
        }

        // กรองตาม content_type
        if ($request->filled('type')) {
            $query->ofType($request->get('type'));
        }

        $projects = $query->latest()->paginate(20);

        return view('admin.ai-content-writer.projects.index', [
            'projects' => $projects,
            'statuses' => AiContentProject::STATUSES,
            'contentTypes' => AiContentTemplate::CONTENT_TYPES,
            'pageTitle' => 'จัดการโปรเจกต์',
        ]);
    }

    /**
     * แสดงรายละเอียด Project
     *
     * @return \Illuminate\View\View
     */
    public function showProject(int $id)
    {
        $project = AiContentProject::with(['template', 'user', 'generations'])
            ->findOrFail($id);

        return view('admin.ai-content-writer.projects.show', [
            'project' => $project,
            'pageTitle' => 'โปรเจกต์: '.$project->name,
        ]);
    }

    /**
     * ลบ Project
     */
    public function deleteProject(int $id): JsonResponse
    {
        try {
            $project = AiContentProject::findOrFail($id);
            $project->delete();

            return response()->json([
                'success' => true,
                'message' => 'ลบโปรเจกต์เรียบร้อยแล้ว',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage(),
            ], 500);
        }
    }

    // =========================================
    // Generations
    // =========================================

    /**
     * แสดงรายการ Generations
     *
     * @return \Illuminate\View\View
     */
    public function generations(Request $request)
    {
        $query = AiContentGeneration::with(['project', 'user']);

        // กรองตาม status
        if ($request->filled('status')) {
            $query->withStatus($request->get('status'));
        }

        // กรองตาม provider
        if ($request->filled('provider')) {
            $query->ofProvider($request->get('provider'));
        }

        $generations = $query->latest()->paginate(20);

        return view('admin.ai-content-writer.generations.index', [
            'generations' => $generations,
            'statuses' => AiContentGeneration::STATUSES,
            'providers' => AiContentTemplate::AI_PROVIDERS,
            'pageTitle' => 'ประวัติการสร้าง Content',
        ]);
    }

    /**
     * แสดงรายละเอียด Generation
     *
     * @return \Illuminate\View\View
     */
    public function showGeneration(int $id)
    {
        $generation = AiContentGeneration::with(['project', 'template', 'user'])
            ->findOrFail($id);

        return view('admin.ai-content-writer.generations.show', [
            'generation' => $generation,
            'pageTitle' => 'Generation #'.$generation->id,
        ]);
    }

    // =========================================
    // Usage Logs
    // =========================================

    /**
     * แสดงรายการ Usage Logs
     *
     * @return \Illuminate\View\View
     */
    public function usageLogs(Request $request)
    {
        $query = AiContentUsageLog::with('user');

        // กรองตาม provider
        if ($request->filled('provider')) {
            $query->ofProvider($request->get('provider'));
        }

        // กรองตามช่วงเวลา
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->get('date_to').' 23:59:59');
        }

        $logs = $query->latest('created_at')->paginate(50);

        // สถิติรวม
        $totalStats = [
            'total_requests' => AiContentUsageLog::count(),
            'total_tokens' => AiContentUsageLog::sum('total_tokens'),
            'total_cost_usd' => AiContentUsageLog::sum('cost_usd'),
            'success_rate' => AiContentUsageLog::count() > 0
                ? round(AiContentUsageLog::successful()->count() / AiContentUsageLog::count() * 100, 1)
                : 0,
        ];

        return view('admin.ai-content-writer.usage-logs.index', [
            'logs' => $logs,
            'totalStats' => $totalStats,
            'providers' => AiContentTemplate::AI_PROVIDERS,
            'pageTitle' => 'Usage Logs',
        ]);
    }

    // =========================================
    // Quick Generate (Playground)
    // =========================================

    /**
     * แสดงหน้า Quick Generate
     *
     * @return \Illuminate\View\View
     */
    public function playground()
    {
        $templates = AiContentTemplate::active()->ordered()->get();
        $providers = $this->writerService->getAvailableProviders();

        return view('admin.ai-content-writer.playground', [
            'templates' => $templates,
            'providers' => $providers,
            'models' => $this->getAllModels(),
            'pageTitle' => 'AI Playground',
        ]);
    }

    /**
     * Quick Generate
     */
    public function quickGenerate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'system_prompt' => 'nullable|string',
            'user_prompt' => 'required|string',
            'provider' => 'required|string',
            'model' => 'required|string',
            'temperature' => 'nullable|numeric|min:0|max:2',
            'max_tokens' => 'nullable|integer|min:100|max:8000',
        ]);

        $result = $this->writerService->generate(
            $validated['system_prompt'] ?? '',
            $validated['user_prompt'],
            auth()->id(),
            [
                'provider' => $validated['provider'],
                'model' => $validated['model'],
                'temperature' => $validated['temperature'] ?? 0.7,
                'max_tokens' => $validated['max_tokens'] ?? 2000,
            ]
        );

        return response()->json($result);
    }

    // =========================================
    // Helper Methods
    // =========================================

    /**
     * ดึงรายการ Models ทั้งหมด
     */
    protected function getAllModels(): array
    {
        return [
            'openai' => OpenAiService::MODELS,
            'claude' => ClaudeService::MODELS,
            'gemini' => GeminiService::MODELS,
        ];
    }
}
