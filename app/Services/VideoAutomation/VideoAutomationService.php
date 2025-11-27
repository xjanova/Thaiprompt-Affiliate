<?php

namespace App\Services\VideoAutomation;

use App\Models\VideoAutoProject;
use App\Models\VideoAutoJob;
use App\Models\VideoAutoTemplate;
use App\Models\VideoAutoSchedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Video Automation Service
 *
 * Orchestrator หลักสำหรับควบคุม Flow การสร้างวีดีโออัตโนมัติ
 *
 * Flow การทำงาน:
 * 1. สร้างเพลงด้วย Suno AI
 * 2. สร้างภาพด้วย Freepik AI
 * 3. รวมเป็นวีดีโอด้วย FFmpeg
 * 4. โพสไปยัง Social Media
 */
class VideoAutomationService
{
    /**
     * Services
     */
    protected SunoApiService $sunoService;
    protected FreepikApiService $freepikService;
    protected VideoCreatorService $videoCreatorService;
    protected SocialPublisherService $publisherService;

    /**
     * สร้าง instance ใหม่
     */
    public function __construct()
    {
        $this->sunoService = new SunoApiService();
        $this->freepikService = new FreepikApiService();
        $this->videoCreatorService = new VideoCreatorService();
        $this->publisherService = new SocialPublisherService();
    }

    /**
     * รัน Full Pipeline - ทำทุกขั้นตอนอัตโนมัติ
     *
     * @param VideoAutoProject $project
     * @param VideoAutoJob|null $job
     * @return array{success: bool, error?: string}
     */
    public function runFullPipeline(VideoAutoProject $project, ?VideoAutoJob $job = null): array
    {
        DB::beginTransaction();

        try {
            $job?->start();
            $project->updateStatus('generating', 'initializing');

            // Step 1: สร้างเพลง
            $job?->updateProgress(5, 'กำลังสร้างเพลง...');
            $project->updateStatus('generating', 'generating_music');

            $musicResult = $this->generateMusic($project, $job);
            if (!$musicResult['success']) {
                throw new \Exception('สร้างเพลงล้มเหลว: ' . $musicResult['error']);
            }

            // Step 2: ดาวน์โหลดเพลง
            $job?->updateProgress(25, 'กำลังดาวน์โหลดเพลง...');
            $project->updateStatus('generating', 'downloading_music');

            $downloadMusicResult = $this->downloadMusic($project, $musicResult['data'], $job);
            if (!$downloadMusicResult['success']) {
                throw new \Exception('ดาวน์โหลดเพลงล้มเหลว: ' . $downloadMusicResult['error']);
            }

            // Step 3: สร้างภาพ
            $job?->updateProgress(35, 'กำลังสร้างภาพ...');
            $project->updateStatus('generating', 'generating_images');

            $imagesResult = $this->generateImages($project, $job);
            if (!$imagesResult['success']) {
                throw new \Exception('สร้างภาพล้มเหลว: ' . $imagesResult['error']);
            }

            // Step 4: ดาวน์โหลดภาพ
            $job?->updateProgress(55, 'กำลังดาวน์โหลดภาพ...');
            $project->updateStatus('generating', 'downloading_images');

            $downloadImagesResult = $this->downloadImages($project, $imagesResult['images'], $job);
            if (!$downloadImagesResult['success']) {
                throw new \Exception('ดาวน์โหลดภาพล้มเหลว: ' . $downloadImagesResult['error']);
            }

            // Step 5: สร้างวีดีโอ
            $job?->updateProgress(70, 'กำลังสร้างวีดีโอ...');
            $project->updateStatus('generating', 'creating_video');

            $videoResult = $this->createVideo($project, $job);
            if (!$videoResult['success']) {
                throw new \Exception('สร้างวีดีโอล้มเหลว: ' . $videoResult['error']);
            }

            // Step 6: สร้าง Thumbnail
            $job?->updateProgress(85, 'กำลังสร้าง thumbnail...');

            $this->createThumbnail($project);

            // Step 7: อัพเดทสถานะเป็น completed
            $project->updateStatus('completed', 'completed');
            $project->progress = 100;
            $project->save();

            DB::commit();

            // Step 8: โพสอัตโนมัติ (ถ้าตั้งค่าไว้ และไม่ได้ตั้งเวลา)
            if (!$project->is_scheduled && !empty($project->target_platforms)) {
                $job?->updateProgress(90, 'กำลังโพส...');
                $project->updateStatus('publishing');

                $publishResult = $this->publisherService->publishToAll($project, [], $job);

                if ($publishResult['any_success']) {
                    $project->updateStatus('published');
                }
            }

            $job?->complete([
                'video_path' => $project->generated_video_path,
                'video_url' => $project->generated_video_url,
                'published_urls' => $project->published_urls,
            ]);

            return ['success' => true];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Video Automation Pipeline Error', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $project->recordError($e->getMessage());
            $job?->fail($e->getMessage(), $e->getTraceAsString());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * สร้างเพลง
     *
     * @param VideoAutoProject $project
     * @param VideoAutoJob|null $job
     * @return array
     */
    public function generateMusic(VideoAutoProject $project, ?VideoAutoJob $job = null): array
    {
        // สร้าง prompt
        $prompt = $project->music_prompt;

        if (empty($prompt) && $project->template) {
            $prompt = $project->template->buildMusicPrompt();
        }

        if (empty($prompt)) {
            $parts = [];
            if ($project->music_genre) $parts[] = $project->music_genre;
            if ($project->music_mood) $parts[] = $project->music_mood . ' mood';
            if ($project->music_style) $parts[] = $project->music_style;
            $prompt = implode(', ', $parts) ?: 'relaxing instrumental music';
        }

        $options = [
            'genre' => $project->music_genre,
            'mood' => $project->music_mood,
            'style' => $project->music_style,
            'instrumental' => true,
        ];

        return $this->sunoService->generateMusic($prompt, $options, $job);
    }

    /**
     * ดาวน์โหลดเพลง
     *
     * @param VideoAutoProject $project
     * @param array $musicData
     * @param VideoAutoJob|null $job
     * @return array
     */
    public function downloadMusic(VideoAutoProject $project, array $musicData, ?VideoAutoJob $job = null): array
    {
        $audioUrl = $musicData['audio_url'] ?? $musicData['url'] ?? null;

        if (!$audioUrl) {
            return [
                'success' => false,
                'error' => 'ไม่พบ URL เพลง',
            ];
        }

        $filename = 'music_' . $project->uuid . '.mp3';
        $result = $this->sunoService->downloadMusic($audioUrl, $filename, $job);

        if ($result['success']) {
            // อัพเดท project
            $project->generated_music_url = $audioUrl;
            $project->generated_music_path = $result['path'];
            $project->music_title = $musicData['title'] ?? null;
            $project->music_metadata = $musicData;
            $project->save();
        }

        return $result;
    }

    /**
     * สร้างภาพ
     *
     * @param VideoAutoProject $project
     * @param VideoAutoJob|null $job
     * @return array
     */
    public function generateImages(VideoAutoProject $project, ?VideoAutoJob $job = null): array
    {
        $imagesCount = $project->images_count ?? $project->template?->images_count ?? 10;
        $prompts = [];

        for ($i = 0; $i < $imagesCount; $i++) {
            if ($project->template) {
                $prompt = $project->template->buildImagePrompt($i);
            } else {
                $prompt = $project->image_prompt ?? 'beautiful abstract art';

                // เพิ่มความหลากหลาย
                $variations = ['vibrant', 'soft', 'dramatic', 'peaceful', 'energetic'];
                $prompt .= ', ' . $variations[$i % count($variations)];
            }

            $prompts[] = $prompt;
        }

        $options = [
            'style' => $project->image_style ?? $project->template?->image_style,
            'ratio' => $project->template?->image_ratio ?? '16:9',
        ];

        return $this->freepikService->generateMultipleImages($prompts, $options, $job);
    }

    /**
     * ดาวน์โหลดภาพ
     *
     * @param VideoAutoProject $project
     * @param array $images
     * @param VideoAutoJob|null $job
     * @return array
     */
    public function downloadImages(VideoAutoProject $project, array $images, ?VideoAutoJob $job = null): array
    {
        $result = $this->freepikService->downloadMultipleImages($images, $project->uuid, $job);

        if ($result['success']) {
            // อัพเดท project
            $project->generated_images = $result['downloaded'];
            $project->generated_images_count = count($result['downloaded']);
            $project->save();
        }

        return $result;
    }

    /**
     * สร้างวีดีโอ
     *
     * @param VideoAutoProject $project
     * @param VideoAutoJob|null $job
     * @return array
     */
    public function createVideo(VideoAutoProject $project, ?VideoAutoJob $job = null): array
    {
        $images = $project->generated_images ?? [];

        if (empty($images)) {
            return [
                'success' => false,
                'error' => 'ไม่มีภาพสำหรับสร้างวีดีโอ',
            ];
        }

        $musicPath = storage_path('app/public/' . $project->generated_music_path);

        if (!file_exists($musicPath)) {
            return [
                'success' => false,
                'error' => 'ไม่พบไฟล์เพลง',
            ];
        }

        $options = [
            'resolution' => $project->video_resolution ?? $project->template?->video_resolution ?? '1080p',
            'slide_duration' => $project->slide_duration ?? $project->template?->slide_duration ?? 5,
            'transition' => $project->transition_type ?? $project->template?->transition_type ?? 'fade',
            'transition_duration' => $project->template?->transition_duration ?? 1000,
            'enable_ken_burns' => $project->template?->enable_ken_burns ?? true,
        ];

        $result = $this->videoCreatorService->createVideo($project, $images, $musicPath, $options, $job);

        if ($result['success']) {
            // อัพเดท project
            $project->generated_video_path = $result['path'];
            $project->generated_video_url = $result['url'];
            $project->video_duration = $result['duration'] ?? null;
            $project->video_size = $result['size'] ?? null;
            $project->video_metadata = $result['metadata'] ?? null;
            $project->save();
        }

        return $result;
    }

    /**
     * สร้าง Thumbnail
     *
     * @param VideoAutoProject $project
     * @return void
     */
    protected function createThumbnail(VideoAutoProject $project): void
    {
        $videoPath = storage_path('app/public/' . $project->generated_video_path);

        if (!file_exists($videoPath)) {
            return;
        }

        $result = $this->videoCreatorService->createThumbnail($videoPath);

        if ($result['success']) {
            $project->video_thumbnail = $result['path'];
            $project->save();
        }
    }

    /**
     * สร้างโปรเจกต์จาก Template
     *
     * @param VideoAutoTemplate $template
     * @param array $overrides
     * @param int|null $createdBy
     * @return VideoAutoProject
     */
    public function createProjectFromTemplate(
        VideoAutoTemplate $template,
        array $overrides = [],
        ?int $createdBy = null
    ): VideoAutoProject {
        $project = VideoAutoProject::createFromTemplate($template, array_merge($overrides, [
            'created_by' => $createdBy,
        ]));

        return $project;
    }

    /**
     * รัน Schedule ที่ถึงเวลา
     *
     * @return array
     */
    public function processScheduledJobs(): array
    {
        $schedules = VideoAutoSchedule::dueToRun()->get();
        $results = [];

        foreach ($schedules as $schedule) {
            try {
                // สร้างโปรเจกต์
                for ($i = 0; $i < $schedule->videos_per_run; $i++) {
                    $project = $this->createProjectFromTemplate(
                        $schedule->template,
                        [
                            'name' => $schedule->name . ' - ' . now()->format('Y-m-d H:i'),
                            'target_platforms' => $schedule->publish_platforms,
                        ]
                    );

                    // สร้าง job
                    $job = VideoAutoJob::create([
                        'project_id' => $project->id,
                        'job_type' => 'full_pipeline',
                        'status' => 'pending',
                        'trigger_source' => 'schedule',
                    ]);

                    // Dispatch job ไปยัง queue
                    // dispatch(new ProcessVideoAutomationJob($project, $job));

                    $results[] = [
                        'schedule_id' => $schedule->id,
                        'project_id' => $project->id,
                        'job_id' => $job->id,
                    ];
                }

                // อัพเดท schedule
                $schedule->recordRun(true);

            } catch (\Exception $e) {
                Log::error('Schedule processing error', [
                    'schedule_id' => $schedule->id,
                    'error' => $e->getMessage(),
                ]);

                $schedule->recordRun(false, $e->getMessage());

                $results[] = [
                    'schedule_id' => $schedule->id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * ตรวจสอบสถานะการตั้งค่า APIs ทั้งหมด
     *
     * @return array
     */
    public function checkAllApiStatus(): array
    {
        return [
            'suno' => [
                'configured' => $this->sunoService->isConfigured(),
                'test' => $this->sunoService->testConnection(),
            ],
            'freepik' => [
                'configured' => $this->freepikService->isConfigured(),
                'test' => $this->freepikService->testConnection(),
            ],
            'ffmpeg' => [
                'configured' => $this->videoCreatorService->isConfigured(),
                'test' => $this->videoCreatorService->testConnection(),
            ],
            'platforms' => $this->publisherService->checkAllConnections(),
        ];
    }

    /**
     * ดึงสถิติระบบ
     *
     * @return array
     */
    public function getStatistics(): array
    {
        return [
            'total_projects' => VideoAutoProject::count(),
            'completed_projects' => VideoAutoProject::where('status', 'completed')->count(),
            'published_projects' => VideoAutoProject::where('status', 'published')->count(),
            'failed_projects' => VideoAutoProject::where('status', 'failed')->count(),
            'pending_projects' => VideoAutoProject::whereIn('status', ['pending', 'draft'])->count(),
            'in_progress' => VideoAutoProject::whereIn('status', ['generating', 'publishing'])->count(),
            'total_templates' => VideoAutoTemplate::active()->count(),
            'active_schedules' => VideoAutoSchedule::active()->count(),
            'total_jobs' => VideoAutoJob::count(),
            'jobs_today' => VideoAutoJob::whereDate('created_at', today())->count(),
        ];
    }
}
