<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VideoAutoSetting;
use App\Models\VideoAutoPlatform;
use App\Models\VideoAutoTemplate;
use App\Models\VideoAutoProject;
use App\Models\VideoAutoJob;
use App\Models\VideoAutoSchedule;
use App\Services\VideoAutomation\VideoAutomationService;
use App\Services\VideoAutomation\SunoApiService;
use App\Services\VideoAutomation\FreepikApiService;
use App\Services\VideoAutomation\VideoCreatorService;
use App\Services\VideoAutomation\YouTubeApiService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Video Automation Controller
 *
 * จัดการระบบ Video Automation ในส่วน Admin
 * รวมถึง Dashboard, Settings, Templates, Projects, Platforms
 */
class VideoAutomationController extends Controller
{
    /**
     * Video Automation Service
     *
     * @var VideoAutomationService
     */
    protected VideoAutomationService $automationService;

    /**
     * สร้าง instance ใหม่
     */
    public function __construct()
    {
        $this->automationService = new VideoAutomationService();
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
        // ดึงสถิติ
        $stats = $this->automationService->getStatistics();

        // ดึงโปรเจกต์ล่าสุด
        $recentProjects = VideoAutoProject::with('template')
            ->latest()
            ->limit(10)
            ->get();

        // ดึง jobs ล่าสุด
        $recentJobs = VideoAutoJob::with('project')
            ->latest()
            ->limit(10)
            ->get();

        // ตรวจสอบสถานะ APIs
        $apiStatus = $this->automationService->checkAllApiStatus();

        return view('admin.video-automation.dashboard', [
            'stats' => $stats,
            'recentProjects' => $recentProjects,
            'recentJobs' => $recentJobs,
            'apiStatus' => $apiStatus,
            'pageTitle' => 'Video Automation Dashboard',
        ]);
    }

    /**
     * ดึงสถิติ Dashboard (API)
     *
     * @return JsonResponse
     */
    public function getDashboardStats(): JsonResponse
    {
        $stats = $this->automationService->getStatistics();
        $apiStatus = $this->automationService->checkAllApiStatus();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'apiStatus' => $apiStatus,
            ],
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
        // ดึง settings ทั้งหมดแยกตาม group
        $settingsByGroup = VideoAutoSetting::active()
            ->ordered()
            ->get()
            ->groupBy('group');

        // ดึง platforms ที่เชื่อมต่อ
        $platforms = VideoAutoPlatform::ordered()->get();

        return view('admin.video-automation.settings', [
            'settingsByGroup' => $settingsByGroup,
            'platforms' => $platforms,
            'groups' => VideoAutoSetting::GROUPS,
            'pageTitle' => 'ตั้งค่า Video Automation',
        ]);
    }

    /**
     * บันทึก Settings
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function saveSettings(Request $request): JsonResponse
    {
        try {
            $settings = $request->input('settings', []);

            foreach ($settings as $key => $value) {
                $setting = VideoAutoSetting::where('key', $key)->first();

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
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ทดสอบการเชื่อมต่อ API
     *
     * @param Request $request
     * @param string $apiType
     * @return JsonResponse
     */
    public function testApiConnection(Request $request, string $apiType): JsonResponse
    {
        $result = match ($apiType) {
            'suno' => (new SunoApiService())->testConnection(),
            'freepik' => (new FreepikApiService())->testConnection(),
            'ffmpeg' => (new VideoCreatorService())->testConnection(),
            'youtube' => (new YouTubeApiService())->testConnection(),
            default => ['success' => false, 'message' => 'API ไม่รู้จัก'],
        };

        return response()->json($result);
    }

    // =========================================
    // Platforms
    // =========================================

    /**
     * แสดงรายการ Platforms
     *
     * @return \Illuminate\View\View
     */
    public function platforms()
    {
        $platforms = VideoAutoPlatform::ordered()->get();

        return view('admin.video-automation.platforms', [
            'platforms' => $platforms,
            'platformTypes' => VideoAutoPlatform::PLATFORM_TYPES,
            'pageTitle' => 'จัดการ Platforms',
        ]);
    }

    /**
     * บันทึก Platform
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function savePlatform(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'nullable|exists:video_auto_platforms,id',
            'platform_type' => 'required|string',
            'name' => 'required|string|max:255',
            'channel_id' => 'nullable|string',
            'channel_name' => 'nullable|string',
            'auto_publish' => 'boolean',
            'is_active' => 'boolean',
        ]);

        try {
            if ($validated['id']) {
                $platform = VideoAutoPlatform::findOrFail($validated['id']);
                $platform->update($validated);
            } else {
                $platform = VideoAutoPlatform::create($validated);
            }

            return response()->json([
                'success' => true,
                'message' => 'บันทึก Platform เรียบร้อยแล้ว',
                'data' => $platform,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ลบ Platform
     *
     * @param int $id
     * @return JsonResponse
     */
    public function deletePlatform(int $id): JsonResponse
    {
        try {
            $platform = VideoAutoPlatform::findOrFail($id);
            $platform->delete();

            return response()->json([
                'success' => true,
                'message' => 'ลบ Platform เรียบร้อยแล้ว',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * เชื่อมต่อ YouTube
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function connectYouTube(Request $request)
    {
        $youtubeService = new YouTubeApiService();
        $redirectUri = route('admin.video-automation.youtube.callback');
        $authUrl = $youtubeService->getAuthorizationUrl($redirectUri);

        return redirect($authUrl);
    }

    /**
     * YouTube OAuth Callback
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function youtubeCallback(Request $request)
    {
        $code = $request->get('code');

        if (!$code) {
            return redirect()->route('admin.video-automation.platforms')
                ->with('error', 'ไม่ได้รับ authorization code');
        }

        try {
            $youtubeService = new YouTubeApiService();
            $redirectUri = route('admin.video-automation.youtube.callback');
            $result = $youtubeService->exchangeCodeForToken($code, $redirectUri);

            if (!$result['success']) {
                throw new \Exception($result['error']);
            }

            $data = $result['data'];
            $channel = $data['channel'] ?? null;

            // สร้างหรืออัพเดท platform
            $platform = VideoAutoPlatform::updateOrCreate(
                [
                    'platform_type' => 'youtube',
                    'channel_id' => $channel['id'] ?? null,
                ],
                [
                    'name' => 'YouTube - ' . ($channel['snippet']['title'] ?? 'Unknown'),
                    'channel_name' => $channel['snippet']['title'] ?? null,
                    'channel_url' => 'https://youtube.com/channel/' . ($channel['id'] ?? ''),
                    'access_token' => $data['access_token'],
                    'refresh_token' => $data['refresh_token'] ?? null,
                    'token_expires_at' => now()->addSeconds($data['expires_in'] ?? 3600),
                    'status' => 'connected',
                    'last_connected_at' => now(),
                    'is_active' => true,
                ]
            );

            return redirect()->route('admin.video-automation.platforms')
                ->with('success', 'เชื่อมต่อ YouTube สำเร็จ!');

        } catch (\Exception $e) {
            return redirect()->route('admin.video-automation.platforms')
                ->with('error', 'เชื่อมต่อล้มเหลว: ' . $e->getMessage());
        }
    }

    // =========================================
    // Templates
    // =========================================

    /**
     * แสดงรายการ Templates
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function templates(Request $request)
    {
        $query = VideoAutoTemplate::query();

        // ค้นหา
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // กรองตาม genre
        if ($request->filled('genre')) {
            $query->where('music_genre', $request->get('genre'));
        }

        $templates = $query->ordered()->paginate(12);

        return view('admin.video-automation.templates.index', [
            'templates' => $templates,
            'genres' => VideoAutoTemplate::MUSIC_GENRES,
            'moods' => VideoAutoTemplate::MUSIC_MOODS,
            'imageStyles' => VideoAutoTemplate::IMAGE_STYLES,
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
        return view('admin.video-automation.templates.create', [
            'genres' => VideoAutoTemplate::MUSIC_GENRES,
            'moods' => VideoAutoTemplate::MUSIC_MOODS,
            'imageStyles' => VideoAutoTemplate::IMAGE_STYLES,
            'transitions' => VideoAutoTemplate::TRANSITIONS,
            'resolutions' => VideoAutoTemplate::VIDEO_RESOLUTIONS,
            'platforms' => VideoAutoPlatform::active()->get(),
            'pageTitle' => 'สร้างเทมเพลตใหม่',
        ]);
    }

    /**
     * บันทึก Template ใหม่
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function storeTemplate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'music_genre' => 'nullable|string',
            'music_mood' => 'nullable|string',
            'music_style' => 'nullable|string',
            'music_duration' => 'nullable|integer|min:30|max:600',
            'music_prompt' => 'nullable|string',
            'image_style' => 'nullable|string',
            'image_theme' => 'nullable|string',
            'images_count' => 'nullable|integer|min:1|max:50',
            'video_resolution' => 'nullable|string',
            'slide_duration' => 'nullable|integer|min:1|max:30',
            'transition_type' => 'nullable|string',
            'target_platforms' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        try {
            $validated['created_by'] = auth()->id();
            $validated['slug'] = Str::slug($validated['name']);

            $template = VideoAutoTemplate::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'สร้างเทมเพลตเรียบร้อยแล้ว',
                'data' => $template,
                'redirect' => route('admin.video-automation.templates'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * แสดงฟอร์มแก้ไข Template
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function editTemplate(int $id)
    {
        $template = VideoAutoTemplate::findOrFail($id);

        return view('admin.video-automation.templates.edit', [
            'template' => $template,
            'genres' => VideoAutoTemplate::MUSIC_GENRES,
            'moods' => VideoAutoTemplate::MUSIC_MOODS,
            'imageStyles' => VideoAutoTemplate::IMAGE_STYLES,
            'transitions' => VideoAutoTemplate::TRANSITIONS,
            'resolutions' => VideoAutoTemplate::VIDEO_RESOLUTIONS,
            'platforms' => VideoAutoPlatform::active()->get(),
            'pageTitle' => 'แก้ไขเทมเพลต: ' . $template->name,
        ]);
    }

    /**
     * อัพเดท Template
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateTemplate(Request $request, int $id): JsonResponse
    {
        $template = VideoAutoTemplate::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'music_genre' => 'nullable|string',
            'music_mood' => 'nullable|string',
            'music_style' => 'nullable|string',
            'music_duration' => 'nullable|integer|min:30|max:600',
            'music_prompt' => 'nullable|string',
            'image_style' => 'nullable|string',
            'image_theme' => 'nullable|string',
            'images_count' => 'nullable|integer|min:1|max:50',
            'video_resolution' => 'nullable|string',
            'slide_duration' => 'nullable|integer|min:1|max:30',
            'transition_type' => 'nullable|string',
            'target_platforms' => 'nullable|array',
            'is_active' => 'boolean',
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
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ลบ Template
     *
     * @param int $id
     * @return JsonResponse
     */
    public function deleteTemplate(int $id): JsonResponse
    {
        try {
            $template = VideoAutoTemplate::findOrFail($id);
            $template->delete();

            return response()->json([
                'success' => true,
                'message' => 'ลบเทมเพลตเรียบร้อยแล้ว',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================
    // Projects
    // =========================================

    /**
     * แสดงรายการ Projects
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function projects(Request $request)
    {
        $query = VideoAutoProject::with(['template', 'creator']);

        // ค้นหา
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where('name', 'like', "%{$search}%");
        }

        // กรองตาม status
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $projects = $query->latest()->paginate(20);

        return view('admin.video-automation.projects.index', [
            'projects' => $projects,
            'statuses' => VideoAutoProject::STATUSES,
            'pageTitle' => 'จัดการโปรเจกต์',
        ]);
    }

    /**
     * แสดงฟอร์มสร้าง Project
     *
     * @return \Illuminate\View\View
     */
    public function createProject()
    {
        $templates = VideoAutoTemplate::active()->ordered()->get();

        return view('admin.video-automation.projects.create', [
            'templates' => $templates,
            'genres' => VideoAutoTemplate::MUSIC_GENRES,
            'moods' => VideoAutoTemplate::MUSIC_MOODS,
            'imageStyles' => VideoAutoTemplate::IMAGE_STYLES,
            'platforms' => VideoAutoPlatform::active()->connected()->get(),
            'pageTitle' => 'สร้างโปรเจกต์ใหม่',
        ]);
    }

    /**
     * บันทึก Project ใหม่
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function storeProject(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'template_id' => 'nullable|exists:video_auto_templates,id',
            'music_genre' => 'nullable|string',
            'music_mood' => 'nullable|string',
            'music_prompt' => 'nullable|string',
            'image_style' => 'nullable|string',
            'image_prompt' => 'nullable|string',
            'images_count' => 'nullable|integer|min:1|max:50',
            'video_title' => 'nullable|string',
            'video_description' => 'nullable|string',
            'video_tags' => 'nullable|array',
            'target_platforms' => 'nullable|array',
            'is_scheduled' => 'boolean',
            'scheduled_at' => 'nullable|date',
            'auto_run' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            $validated['created_by'] = auth()->id();
            $validated['status'] = 'draft';
            $validated['uuid'] = Str::uuid();

            // ถ้าเลือก template ให้ใช้ค่าจาก template
            if ($validated['template_id']) {
                $template = VideoAutoTemplate::find($validated['template_id']);
                $project = VideoAutoProject::createFromTemplate($template, $validated);
            } else {
                $project = VideoAutoProject::create($validated);
            }

            // ถ้าเลือก auto_run ให้เริ่มทำงานเลย
            if ($request->get('auto_run')) {
                $project->status = 'pending';
                $project->save();

                // สร้าง job
                $job = VideoAutoJob::create([
                    'project_id' => $project->id,
                    'job_type' => 'full_pipeline',
                    'status' => 'pending',
                    'triggered_by' => auth()->id(),
                    'trigger_source' => 'manual',
                ]);

                // TODO: Dispatch to queue
                // dispatch(new ProcessVideoAutomationJob($project, $job));
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'สร้างโปรเจกต์เรียบร้อยแล้ว',
                'data' => $project,
                'redirect' => route('admin.video-automation.projects.show', $project->id),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * แสดงรายละเอียด Project
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function showProject(int $id)
    {
        $project = VideoAutoProject::with(['template', 'creator', 'jobs', 'logs'])
            ->findOrFail($id);

        return view('admin.video-automation.projects.show', [
            'project' => $project,
            'pageTitle' => 'โปรเจกต์: ' . $project->name,
        ]);
    }

    /**
     * เริ่มรัน Project
     *
     * @param int $id
     * @return JsonResponse
     */
    public function runProject(int $id): JsonResponse
    {
        try {
            $project = VideoAutoProject::findOrFail($id);

            if (!in_array($project->status, ['draft', 'failed'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่สามารถเริ่มโปรเจกต์ที่มีสถานะ ' . $project->status_label,
                ], 400);
            }

            $project->status = 'pending';
            $project->save();

            // สร้าง job
            $job = VideoAutoJob::create([
                'project_id' => $project->id,
                'job_type' => 'full_pipeline',
                'status' => 'pending',
                'triggered_by' => auth()->id(),
                'trigger_source' => 'manual',
            ]);

            // รันทันที (sync) - ในการใช้งานจริงควร dispatch to queue
            $result = $this->automationService->runFullPipeline($project, $job);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['success'] ? 'เริ่มรันโปรเจกต์เรียบร้อยแล้ว' : $result['error'],
                'job_id' => $job->id,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ลบ Project
     *
     * @param int $id
     * @return JsonResponse
     */
    public function deleteProject(int $id): JsonResponse
    {
        try {
            $project = VideoAutoProject::findOrFail($id);
            $project->delete();

            return response()->json([
                'success' => true,
                'message' => 'ลบโปรเจกต์เรียบร้อยแล้ว',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================
    // Jobs
    // =========================================

    /**
     * แสดงรายการ Jobs
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function jobs(Request $request)
    {
        $query = VideoAutoJob::with('project');

        // กรองตาม status
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        // กรองตาม job_type
        if ($request->filled('type')) {
            $query->where('job_type', $request->get('type'));
        }

        $jobs = $query->latest()->paginate(20);

        return view('admin.video-automation.jobs.index', [
            'jobs' => $jobs,
            'jobTypes' => VideoAutoJob::JOB_TYPES,
            'statuses' => VideoAutoJob::STATUSES,
            'pageTitle' => 'จัดการ Jobs',
        ]);
    }

    /**
     * ดึง logs ของ Job
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getJobLogs(int $id): JsonResponse
    {
        $job = VideoAutoJob::with('logs')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $job->logs()->latest('logged_at')->limit(100)->get(),
        ]);
    }

    /**
     * Retry Job ที่ล้มเหลว
     *
     * @param int $id
     * @return JsonResponse
     */
    public function retryJob(int $id): JsonResponse
    {
        try {
            $job = VideoAutoJob::findOrFail($id);

            if (!$job->can_retry) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่สามารถ retry job นี้ได้',
                ], 400);
            }

            $job->retry();

            // TODO: Dispatch to queue
            // dispatch(new ProcessVideoAutomationJob($job->project, $job));

            return response()->json([
                'success' => true,
                'message' => 'เริ่ม retry เรียบร้อยแล้ว',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================
    // Schedules
    // =========================================

    /**
     * แสดงรายการ Schedules
     *
     * @return \Illuminate\View\View
     */
    public function schedules()
    {
        $schedules = VideoAutoSchedule::with('template')->latest()->paginate(20);

        return view('admin.video-automation.schedules.index', [
            'schedules' => $schedules,
            'frequencyTypes' => VideoAutoSchedule::FREQUENCY_TYPES,
            'pageTitle' => 'จัดการ Schedules',
        ]);
    }

    /**
     * บันทึก Schedule
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function saveSchedule(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'nullable|exists:video_auto_schedules,id',
            'template_id' => 'required|exists:video_auto_templates,id',
            'name' => 'required|string|max:255',
            'frequency_type' => 'required|in:once,daily,weekly,monthly,custom',
            'run_at' => 'nullable|date',
            'daily_time' => 'nullable|date_format:H:i',
            'weekly_days' => 'nullable|array',
            'weekly_time' => 'nullable|date_format:H:i',
            'monthly_days' => 'nullable|array',
            'monthly_time' => 'nullable|date_format:H:i',
            'cron_expression' => 'nullable|string',
            'videos_per_run' => 'nullable|integer|min:1|max:10',
            'auto_publish' => 'boolean',
            'publish_platforms' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        try {
            if ($validated['id']) {
                $schedule = VideoAutoSchedule::findOrFail($validated['id']);
                $schedule->update($validated);
            } else {
                $validated['created_by'] = auth()->id();
                $schedule = VideoAutoSchedule::create($validated);
            }

            // อัพเดทเวลารันครั้งต่อไป
            $schedule->updateNextRunAt();

            return response()->json([
                'success' => true,
                'message' => 'บันทึก Schedule เรียบร้อยแล้ว',
                'data' => $schedule,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ลบ Schedule
     *
     * @param int $id
     * @return JsonResponse
     */
    public function deleteSchedule(int $id): JsonResponse
    {
        try {
            $schedule = VideoAutoSchedule::findOrFail($id);
            $schedule->delete();

            return response()->json([
                'success' => true,
                'message' => 'ลบ Schedule เรียบร้อยแล้ว',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================
    // Documentation
    // =========================================

    /**
     * แสดงหน้า Documentation
     *
     * @return \Illuminate\View\View
     */
    public function documentation()
    {
        return view('admin.video-automation.documentation', [
            'pageTitle' => 'คู่มือการใช้งาน Video Automation',
        ]);
    }
}
