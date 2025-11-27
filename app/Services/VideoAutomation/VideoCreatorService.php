<?php

namespace App\Services\VideoAutomation;

use App\Models\VideoAutoSetting;
use App\Models\VideoAutoJob;
use App\Models\VideoAutoProject;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Video Creator Service
 *
 * สร้างวีดีโอจากภาพและเพลงโดยใช้ FFmpeg
 *
 * @see https://ffmpeg.org/documentation.html
 */
class VideoCreatorService
{
    /**
     * Path ของ FFmpeg
     *
     * @var string
     */
    protected string $ffmpegPath;

    /**
     * Path ของ FFprobe
     *
     * @var string
     */
    protected string $ffprobePath;

    /**
     * ความละเอียดวีดีโอ
     *
     * @var array<string, array>
     */
    protected const RESOLUTIONS = [
        '720p' => ['width' => 1280, 'height' => 720],
        '1080p' => ['width' => 1920, 'height' => 1080],
        '1440p' => ['width' => 2560, 'height' => 1440],
        '4k' => ['width' => 3840, 'height' => 2160],
    ];

    /**
     * สร้าง instance ใหม่
     */
    public function __construct()
    {
        $this->loadSettings();
    }

    /**
     * โหลดการตั้งค่าจาก database
     *
     * @return void
     */
    protected function loadSettings(): void
    {
        $this->ffmpegPath = VideoAutoSetting::getValue('ffmpeg_path', 'ffmpeg');
        $this->ffprobePath = VideoAutoSetting::getValue('ffprobe_path', 'ffprobe');
    }

    /**
     * ตรวจสอบว่า FFmpeg พร้อมใช้งานหรือไม่
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        exec("{$this->ffmpegPath} -version 2>&1", $output, $returnCode);
        return $returnCode === 0;
    }

    /**
     * สร้างวีดีโอจากภาพและเพลง
     *
     * @param VideoAutoProject $project โปรเจกต์
     * @param array $images รายการภาพ
     * @param string $musicPath Path ของไฟล์เพลง
     * @param array $options ตัวเลือกเพิ่มเติม
     * @param VideoAutoJob|null $job Job สำหรับ logging
     * @return array{success: bool, path?: string, error?: string}
     */
    public function createVideo(
        VideoAutoProject $project,
        array $images,
        string $musicPath,
        array $options = [],
        ?VideoAutoJob $job = null
    ): array {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'FFmpeg ไม่พร้อมใช้งาน กรุณาติดตั้ง FFmpeg ก่อน',
            ];
        }

        try {
            $job?->logInfo('เริ่มสร้างวีดีโอ', [
                'images_count' => count($images),
                'music_path' => $musicPath,
            ]);

            // กำหนดค่าเริ่มต้น
            $resolution = $options['resolution'] ?? $project->video_resolution ?? '1080p';
            $slideDuration = $options['slide_duration'] ?? $project->slide_duration ?? 5;
            $transitionType = $options['transition'] ?? $project->transition_type ?? 'fade';
            $transitionDuration = ($options['transition_duration'] ?? 1000) / 1000; // แปลงเป็นวินาที

            $res = self::RESOLUTIONS[$resolution] ?? self::RESOLUTIONS['1080p'];

            // สร้าง temp directory
            $tempDir = storage_path('app/temp/video-' . Str::random(10));
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // ดึงความยาวเพลง
            $musicDuration = $this->getAudioDuration($musicPath);
            $job?->logInfo('ความยาวเพลง', ['duration' => $musicDuration]);

            // คำนวณความยาว slide ให้พอดีกับเพลง
            if ($musicDuration > 0 && count($images) > 0) {
                $slideDuration = $musicDuration / count($images);
                $job?->logInfo('ปรับความยาว slide ให้พอดีกับเพลง', ['slide_duration' => $slideDuration]);
            }

            // เตรียมภาพ
            $job?->updateProgress(20, 'กำลังเตรียมภาพ...');
            $preparedImages = $this->prepareImages($images, $res, $tempDir, $job);

            if (count($preparedImages) === 0) {
                throw new \Exception('ไม่สามารถเตรียมภาพได้');
            }

            // สร้าง input file list
            $listFile = $tempDir . '/images.txt';
            $this->createImageList($preparedImages, $slideDuration, $listFile);

            // สร้างชื่อไฟล์ output
            $outputFilename = 'video_' . $project->uuid . '_' . time() . '.mp4';
            $outputPath = "video-automation/videos/{$outputFilename}";
            $fullOutputPath = Storage::disk('public')->path($outputPath);

            // สร้าง directory ถ้าไม่มี
            $outputDir = dirname($fullOutputPath);
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            // สร้างวีดีโอ
            $job?->updateProgress(50, 'กำลังสร้างวีดีโอ...');

            $command = $this->buildFFmpegCommand(
                $listFile,
                $musicPath,
                $fullOutputPath,
                $res,
                $transitionType,
                $transitionDuration,
                $options
            );

            $job?->logInfo('FFmpeg command', ['command' => $command]);

            // รัน FFmpeg
            exec($command . ' 2>&1', $output, $returnCode);

            if ($returnCode !== 0) {
                $error = implode("\n", $output);
                $job?->logError('FFmpeg error', ['output' => $error]);
                throw new \Exception("FFmpeg error: {$error}");
            }

            // ตรวจสอบไฟล์ output
            if (!file_exists($fullOutputPath)) {
                throw new \Exception('ไม่พบไฟล์วีดีโอที่สร้าง');
            }

            // ดึง metadata ของวีดีโอ
            $videoInfo = $this->getVideoInfo($fullOutputPath);

            // ล้าง temp files
            $this->cleanupTempFiles($tempDir);

            $job?->logInfo('สร้างวีดีโอสำเร็จ', [
                'path' => $outputPath,
                'size' => filesize($fullOutputPath),
                'duration' => $videoInfo['duration'] ?? null,
            ]);

            return [
                'success' => true,
                'path' => $outputPath,
                'full_path' => $fullOutputPath,
                'url' => Storage::disk('public')->url($outputPath),
                'size' => filesize($fullOutputPath),
                'duration' => $videoInfo['duration'] ?? null,
                'metadata' => $videoInfo,
            ];

        } catch (\Exception $e) {
            Log::error('Video Creator Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $job?->logError('Video Creator exception', ['message' => $e->getMessage()]);

            // ล้าง temp files
            if (isset($tempDir) && is_dir($tempDir)) {
                $this->cleanupTempFiles($tempDir);
            }

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * เตรียมภาพให้มีขนาดและ format ที่เหมาะสม
     *
     * @param array $images
     * @param array $resolution
     * @param string $tempDir
     * @param VideoAutoJob|null $job
     * @return array
     */
    protected function prepareImages(array $images, array $resolution, string $tempDir, ?VideoAutoJob $job = null): array
    {
        $prepared = [];
        $total = count($images);

        foreach ($images as $index => $image) {
            $job?->logInfo("เตรียมภาพ " . ($index + 1) . "/{$total}");

            $sourcePath = $image['full_path'] ?? Storage::disk('public')->path($image['path']);

            if (!file_exists($sourcePath)) {
                $job?->logError("ไม่พบภาพ: {$sourcePath}");
                continue;
            }

            $outputFile = $tempDir . '/img_' . str_pad($index, 4, '0', STR_PAD_LEFT) . '.jpg';

            // ใช้ FFmpeg ปรับขนาดภาพ
            $command = sprintf(
                '%s -i %s -vf "scale=%d:%d:force_original_aspect_ratio=decrease,pad=%d:%d:(ow-iw)/2:(oh-ih)/2:black" -y %s 2>&1',
                escapeshellarg($this->ffmpegPath),
                escapeshellarg($sourcePath),
                $resolution['width'],
                $resolution['height'],
                $resolution['width'],
                $resolution['height'],
                escapeshellarg($outputFile)
            );

            exec($command, $output, $returnCode);

            if ($returnCode === 0 && file_exists($outputFile)) {
                $prepared[] = $outputFile;
            } else {
                $job?->logError("ไม่สามารถเตรียมภาพได้: " . implode("\n", $output));
            }
        }

        return $prepared;
    }

    /**
     * สร้าง image list file สำหรับ FFmpeg concat
     *
     * @param array $images
     * @param float $duration
     * @param string $outputFile
     * @return void
     */
    protected function createImageList(array $images, float $duration, string $outputFile): void
    {
        $content = '';

        foreach ($images as $imagePath) {
            $content .= "file '{$imagePath}'\n";
            $content .= "duration {$duration}\n";
        }

        // เพิ่ม entry สุดท้ายโดยไม่มี duration (ตาม FFmpeg spec)
        if (count($images) > 0) {
            $content .= "file '{$images[count($images) - 1]}'\n";
        }

        file_put_contents($outputFile, $content);
    }

    /**
     * สร้าง FFmpeg command
     *
     * @param string $listFile
     * @param string $musicPath
     * @param string $outputPath
     * @param array $resolution
     * @param string $transitionType
     * @param float $transitionDuration
     * @param array $options
     * @return string
     */
    protected function buildFFmpegCommand(
        string $listFile,
        string $musicPath,
        string $outputPath,
        array $resolution,
        string $transitionType,
        float $transitionDuration,
        array $options = []
    ): string {
        // Base command
        $command = escapeshellarg($this->ffmpegPath);

        // Input: image list
        $command .= ' -f concat -safe 0 -i ' . escapeshellarg($listFile);

        // Input: audio
        $command .= ' -i ' . escapeshellarg($musicPath);

        // Video filters
        $filters = [];

        // Ken Burns effect (zoom pan)
        if ($options['enable_ken_burns'] ?? true) {
            $filters[] = "zoompan=z='min(zoom+0.0015,1.5)':d=25:s={$resolution['width']}x{$resolution['height']}";
        }

        // Apply filters
        if (!empty($filters)) {
            $command .= ' -vf "' . implode(',', $filters) . '"';
        }

        // Video codec settings
        $command .= ' -c:v libx264';
        $command .= ' -preset medium';
        $command .= ' -crf 23';
        $command .= ' -pix_fmt yuv420p';

        // Audio codec settings
        $command .= ' -c:a aac';
        $command .= ' -b:a 192k';

        // Sync audio with video
        $command .= ' -shortest';

        // Output
        $command .= ' -y ' . escapeshellarg($outputPath);

        return $command;
    }

    /**
     * ดึงความยาวของไฟล์เสียง
     *
     * @param string $audioPath
     * @return float
     */
    protected function getAudioDuration(string $audioPath): float
    {
        $command = sprintf(
            '%s -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 %s 2>&1',
            escapeshellarg($this->ffprobePath),
            escapeshellarg($audioPath)
        );

        $output = shell_exec($command);

        return (float) trim($output);
    }

    /**
     * ดึงข้อมูลของวีดีโอ
     *
     * @param string $videoPath
     * @return array
     */
    protected function getVideoInfo(string $videoPath): array
    {
        $command = sprintf(
            '%s -v error -show_entries format=duration,size,bit_rate:stream=width,height,codec_name -of json %s 2>&1',
            escapeshellarg($this->ffprobePath),
            escapeshellarg($videoPath)
        );

        $output = shell_exec($command);
        $data = json_decode($output, true);

        $format = $data['format'] ?? [];
        $stream = $data['streams'][0] ?? [];

        return [
            'duration' => (float) ($format['duration'] ?? 0),
            'size' => (int) ($format['size'] ?? 0),
            'bitrate' => (int) ($format['bit_rate'] ?? 0),
            'width' => (int) ($stream['width'] ?? 0),
            'height' => (int) ($stream['height'] ?? 0),
            'codec' => $stream['codec_name'] ?? '',
        ];
    }

    /**
     * ล้าง temp files
     *
     * @param string $tempDir
     * @return void
     */
    protected function cleanupTempFiles(string $tempDir): void
    {
        if (!is_dir($tempDir)) {
            return;
        }

        $files = glob($tempDir . '/*');

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        rmdir($tempDir);
    }

    /**
     * สร้าง thumbnail จากวีดีโอ
     *
     * @param string $videoPath
     * @param int $position ตำแหน่งในวีดีโอ (วินาที)
     * @return array{success: bool, path?: string, error?: string}
     */
    public function createThumbnail(string $videoPath, int $position = 5): array
    {
        try {
            $outputFilename = 'thumb_' . Str::random(10) . '.jpg';
            $outputPath = "video-automation/thumbnails/{$outputFilename}";
            $fullOutputPath = Storage::disk('public')->path($outputPath);

            // สร้าง directory ถ้าไม่มี
            $outputDir = dirname($fullOutputPath);
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            $command = sprintf(
                '%s -i %s -ss %d -vframes 1 -q:v 2 -y %s 2>&1',
                escapeshellarg($this->ffmpegPath),
                escapeshellarg($videoPath),
                $position,
                escapeshellarg($fullOutputPath)
            );

            exec($command, $output, $returnCode);

            if ($returnCode !== 0 || !file_exists($fullOutputPath)) {
                throw new \Exception('ไม่สามารถสร้าง thumbnail ได้');
            }

            return [
                'success' => true,
                'path' => $outputPath,
                'full_path' => $fullOutputPath,
                'url' => Storage::disk('public')->url($outputPath),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * ทดสอบการทำงานของ FFmpeg
     *
     * @return array{success: bool, message: string, version?: string}
     */
    public function testConnection(): array
    {
        exec("{$this->ffmpegPath} -version 2>&1", $output, $returnCode);

        if ($returnCode === 0) {
            $version = $output[0] ?? 'Unknown';

            return [
                'success' => true,
                'message' => 'FFmpeg พร้อมใช้งาน!',
                'version' => $version,
            ];
        }

        return [
            'success' => false,
            'message' => 'FFmpeg ไม่พร้อมใช้งาน กรุณาติดตั้ง FFmpeg',
        ];
    }
}
