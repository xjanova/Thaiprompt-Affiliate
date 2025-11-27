<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use App\Models\MlmMember;
use App\Models\MlmCommission;
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
     * ดึงข้อมูลสถิติของระบบ
     *
     * สถิติจะถูกดึงแบบ real-time จากโครงสร้างโปรเจคจริง
     * และ cache ไว้เพื่อประสิทธิภาพ
     */
    private function getSystemStats()
    {
        // ใช้ cache เพื่อประสิทธิภาพ (cache 1 ชั่วโมง)
        return cache()->remember('wiki_system_stats', 3600, function () {
            // ดึง version จาก VERSION file
            $versionPath = base_path('VERSION');
            $version = '3.232.0';

            if (File::exists($versionPath)) {
                $version = trim(File::get($versionPath));
            }

            // นับ migrations
            $migrationsCount = 0;
            if (File::isDirectory(database_path('migrations'))) {
                $migrationFiles = File::allFiles(database_path('migrations'));
                foreach ($migrationFiles as $file) {
                    if ($file->getExtension() === 'php') {
                        $migrationsCount++;
                    }
                }
            }

            // นับ models (รวม subdirectories)
            $modelsCount = 0;
            if (File::isDirectory(app_path('Models'))) {
                $modelFiles = File::allFiles(app_path('Models'));
                foreach ($modelFiles as $file) {
                    if ($file->getExtension() === 'php') {
                        $modelsCount++;
                    }
                }
            }

            // นับ controllers (รวม subdirectories)
            $controllersCount = 0;
            if (File::isDirectory(app_path('Http/Controllers'))) {
                $controllerFiles = File::allFiles(app_path('Http/Controllers'));
                foreach ($controllerFiles as $file) {
                    if ($file->getExtension() === 'php') {
                        $controllersCount++;
                    }
                }
            }

            // นับ services (รวม subdirectories)
            $servicesCount = 0;
            if (File::isDirectory(app_path('Services'))) {
                $serviceFiles = File::allFiles(app_path('Services'));
                foreach ($serviceFiles as $file) {
                    if ($file->getExtension() === 'php') {
                        $servicesCount++;
                    }
                }
            }

            // นับ API endpoints จาก routes
            $apiEndpoints = 0;
            $routeFiles = ['api.php', 'admin.php', 'user.php', 'seller.php'];
            foreach ($routeFiles as $routeFile) {
                $routePath = base_path("routes/{$routeFile}");
                if (File::exists($routePath)) {
                    $content = File::get($routePath);
                    // นับจำนวน Route:: definitions
                    $apiEndpoints += preg_match_all('/Route::(get|post|put|patch|delete|any|match|resource|apiResource)\s*\(/i', $content);
                }
            }

            // นับ Business Categories (จาก Models)
            $businessCategories = 27; // จากการวิเคราะห์โครงสร้าง

            // ดึง Windows UI RGB colors
            $windowsRgb = [
                'primary_rgb' => \App\Models\WindowsUiSetting::get('primary_rgb', '59, 130, 246'),
                'secondary_rgb' => \App\Models\WindowsUiSetting::get('secondary_rgb', '139, 92, 246'),
                'accent_rgb' => \App\Models\WindowsUiSetting::get('accent_rgb', '236, 72, 153'),
            ];

            return [
                'version' => $version,
                'last_updated' => date('Y-m-d'),
                'total_users' => User::count(),
                'total_affiliates' => MlmMember::count(),
                'total_commissions' => MlmCommission::count(),
                'database_tables' => $migrationsCount,
                'database_models' => $modelsCount,
                'http_controllers' => $controllersCount,
                'services_count' => $servicesCount,
                'api_endpoints' => $apiEndpoints,
                'business_categories' => $businessCategories,
                'windows_rgb' => $windowsRgb,
            ];
        });
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
