<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use App\Models\Affiliate;
use App\Models\Commission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class WikiController extends Controller
{
    /**
     * Display the main wiki page
     */
    public function index()
    {
        $stats = $this->getSystemStats();

        return view('frontend.wiki.index', compact('stats'));
    }

    /**
     * Get wiki content for a specific category
     */
    public function getContent(Request $request, $category, $section = null)
    {
        $stats = $this->getSystemStats();

        // Load the appropriate content file
        $contentPath = resource_path("views/frontend/wiki/content/{$category}.blade.php");

        if (!File::exists($contentPath)) {
            return response()->json([
                'error' => 'Content not found',
                'message' => "หมวดหมู่ {$category} ไม่พบในระบบ"
            ], 404);
        }

        // Return the rendered content
        $content = view("frontend.wiki.content.{$category}", compact('stats', 'section'))->render();

        return response()->json([
            'success' => true,
            'category' => $category,
            'section' => $section,
            'content' => $content,
            'stats' => $stats
        ]);
    }

    /**
     * Get system statistics
     */
    private function getSystemStats()
    {
        // Get version from CHANGELOG
        $changelogPath = base_path('CHANGELOG.md');
        $version = 'v2.138.0';

        if (File::exists($changelogPath)) {
            $content = File::get($changelogPath);
            if (preg_match('/\[v([\d.]+)\]/', $content, $matches)) {
                $version = 'v' . $matches[1];
            }
        }

        // Count database elements
        $migrationsCount = count(File::files(database_path('migrations')));
        $modelsCount = count(File::files(app_path('Models')));

        // Count controllers recursively
        $controllersCount = 0;
        $controllerFiles = File::allFiles(app_path('Http/Controllers'));
        foreach ($controllerFiles as $file) {
            if ($file->getExtension() === 'php') {
                $controllersCount++;
            }
        }

        // Count services
        $servicesCount = 0;
        if (File::isDirectory(app_path('Services'))) {
            $serviceFiles = File::allFiles(app_path('Services'));
            foreach ($serviceFiles as $file) {
                if ($file->getExtension() === 'php') {
                    $servicesCount++;
                }
            }
        }

        // Get Windows UI RGB colors
        $windowsRgb = [
            'primary_rgb' => \App\Models\WindowsUiSetting::get('primary_rgb', '59, 130, 246'),
            'secondary_rgb' => \App\Models\WindowsUiSetting::get('secondary_rgb', '139, 92, 246'),
            'accent_rgb' => \App\Models\WindowsUiSetting::get('accent_rgb', '236, 72, 153'),
        ];

        return [
            'version' => $version,
            'last_updated' => date('Y-m-d'),
            'total_users' => User::count(),
            'total_affiliates' => Affiliate::count(),
            'total_commissions' => Commission::count(),
            'database_tables' => $migrationsCount,
            'database_models' => $modelsCount,
            'http_controllers' => $controllersCount,
            'services_count' => $servicesCount,
            'api_endpoints' => 20,
            'windows_rgb' => $windowsRgb,
        ];
    }

    /**
     * Search wiki content
     */
    public function search(Request $request)
    {
        $query = $request->input('q');

        // TODO: Implement full-text search across wiki content

        return response()->json([
            'success' => true,
            'query' => $query,
            'results' => []
        ]);
    }
}
