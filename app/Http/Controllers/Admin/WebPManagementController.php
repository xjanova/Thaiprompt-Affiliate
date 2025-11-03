<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WebPService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class WebPManagementController extends Controller
{
    protected WebPService $webpService;

    public function __construct(WebPService $webpService)
    {
        $this->webpService = $webpService;
    }

    /**
     * Display WebP management dashboard
     */
    public function index()
    {
        // Get statistics
        $stats = $this->getStatistics();

        return view('admin.webp.index', compact('stats'));
    }

    /**
     * Get WebP statistics
     */
    private function getStatistics(): array
    {
        $directories = ['branding', 'avatars', 'rich-menus', 'withdrawal-slips', 'page-loaders'];
        $stats = [
            'total_images' => 0,
            'webp_images' => 0,
            'non_webp_images' => 0,
            'total_size' => 0,
            'webp_size' => 0,
            'potential_savings' => 0,
            'directories' => [],
        ];

        foreach ($directories as $dir) {
            if (!Storage::disk('public')->exists($dir)) {
                continue;
            }

            $files = Storage::disk('public')->allFiles($dir);
            $dirStats = [
                'name' => $dir,
                'total' => 0,
                'webp' => 0,
                'non_webp' => 0,
                'size' => 0,
            ];

            foreach ($files as $file) {
                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

                // Skip non-image files
                if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
                    continue;
                }

                $size = Storage::disk('public')->size($file);

                if ($extension === 'webp') {
                    $stats['webp_images']++;
                    $stats['webp_size'] += $size;
                    $dirStats['webp']++;
                } elseif (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                    $stats['non_webp_images']++;
                    $stats['total_size'] += $size;
                    $dirStats['non_webp']++;

                    // Estimate 30% savings with WebP
                    $stats['potential_savings'] += ($size * 0.3);
                }

                $stats['total_images']++;
                $dirStats['total']++;
                $dirStats['size'] += $size;
            }

            if ($dirStats['total'] > 0) {
                $stats['directories'][] = $dirStats;
            }
        }

        return $stats;
    }

    /**
     * Convert images to WebP
     */
    public function convert(Request $request)
    {
        $validated = $request->validate([
            'directory' => 'nullable|string|in:branding,avatars,rich-menus,withdrawal-slips,page-loaders,all',
            'quality' => 'nullable|integer|min:1|max:100',
            'delete_original' => 'nullable|boolean',
        ]);

        $directory = $validated['directory'] ?? 'all';
        $quality = $validated['quality'] ?? 85;
        $deleteOriginal = $validated['delete_original'] ?? false;

        try {
            // Build artisan command
            $command = 'images:convert-webp';
            $params = ['--quality' => $quality];

            if ($directory !== 'all') {
                $params['--directory'] = $directory;
            }

            if ($deleteOriginal) {
                $params['--delete-original'] = true;
            }

            // Run command
            $exitCode = Artisan::call($command, $params);

            if ($exitCode === 0) {
                // Get command output
                $output = Artisan::output();

                return redirect()->route('admin.webp.index')
                    ->with('success', 'แปลงรูปภาพเป็น WebP สำเร็จแล้ว!')
                    ->with('command_output', $output);
            } else {
                return redirect()->route('admin.webp.index')
                    ->with('error', 'เกิดข้อผิดพลาดในการแปลงรูปภาพ');
            }
        } catch (\Exception $e) {
            Log::error('WebP conversion error: ' . $e->getMessage());

            return redirect()->route('admin.webp.index')
                ->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Get directory details via AJAX
     */
    public function directoryDetails(Request $request)
    {
        $directory = $request->input('directory');

        if (!$directory || !Storage::disk('public')->exists($directory)) {
            return response()->json(['error' => 'Directory not found'], 404);
        }

        $files = Storage::disk('public')->allFiles($directory);
        $fileList = [];

        foreach ($files as $file) {
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

            // Skip non-image files
            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
                continue;
            }

            $size = Storage::disk('public')->size($file);
            $webpPath = preg_replace('/\.(jpg|jpeg|png|gif)$/i', '.webp', $file);
            $hasWebP = Storage::disk('public')->exists($webpPath);

            $fileList[] = [
                'name' => basename($file),
                'path' => $file,
                'extension' => $extension,
                'size' => $size,
                'size_formatted' => $this->formatBytes($size),
                'is_webp' => $extension === 'webp',
                'has_webp_version' => $hasWebP,
                'url' => Storage::disk('public')->url($file),
            ];
        }

        return response()->json([
            'directory' => $directory,
            'files' => $fileList,
            'total' => count($fileList),
        ]);
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
