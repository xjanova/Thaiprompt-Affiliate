<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\MlmCommission;
use App\Models\MlmMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

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

        if (! File::exists($contentPath)) {
            return response()->json([
                'error' => 'Content not found',
                'message' => "หมวดหมู่ {$category} ไม่พบในระบบ",
            ], 404);
        }

        // Return the rendered content
        $content = view("frontend.wiki.content.{$category}", compact('stats', 'section'))->render();

        return response()->json([
            'success' => true,
            'category' => $category,
            'section' => $section,
            'content' => $content,
            'stats' => $stats,
        ]);
    }

    /**
     * ดึงข้อมูลสถิติของระบบ
     *
     * สถิติจะถูกดึงแบบ real-time จากโครงสร้างโปรเจคจริง
     * และ cache ไว้เพื่อประสิทธิภาพ
     *
     * @return array ข้อมูลสถิติระบบ
     */
    private function getSystemStats(): array
    {
        // ใช้ cache เพื่อประสิทธิภาพ (cache 1 ชั่วโมง)
        return cache()->remember('wiki_system_stats', 3600, function () {
            // ดึง version จาก VERSION file
            $versionPath = base_path('VERSION');
            $version = '3.485.333';

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

            // นับ API endpoints จาก routes (ครอบคลุมทุก route files)
            $apiEndpoints = 0;
            $routeFiles = [
                'api.php', 'admin.php', 'user.php', 'seller.php', 'web.php',
                'hotel-admin.php', 'software_sales.php', 'bot_automation.php',
                'pos.php', 'provider.php', 'sms_payment_api.php', 'forum.php',
                'taladsod.php',
            ];
            foreach ($routeFiles as $routeFile) {
                $routePath = base_path("routes/{$routeFile}");
                if (File::exists($routePath)) {
                    $content = File::get($routePath);
                    // นับจำนวน Route:: definitions
                    $apiEndpoints += preg_match_all('/Route::(get|post|put|patch|delete|any|match|resource|apiResource)\s*\(/i', $content);
                }
            }

            // นับ Business Categories (จาก Models)
            $businessCategories = 30; // จากการวิเคราะห์โครงสร้าง (อัพเดท 2026-03)

            // ดึง Windows UI RGB colors (พร้อม fallback ถ้าตารางยังไม่มี)
            $windowsRgb = $this->getSafeWindowsRgb();

            // ดึงสถิติจากฐานข้อมูล (พร้อม fallback ถ้าตารางยังไม่มี)
            $dbStats = $this->getSafeDatabaseStats();

            return [
                'version' => $version,
                'last_updated' => date('Y-m-d'),
                'total_users' => $dbStats['users'],
                'total_affiliates' => $dbStats['affiliates'],
                'total_commissions' => $dbStats['commissions'],
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
     * ดึง Windows UI RGB settings อย่างปลอดภัย
     *
     * ถ้าตารางยังไม่มีหรือเกิด error จะ return ค่า default
     *
     * @return array RGB color settings
     */
    private function getSafeWindowsRgb(): array
    {
        $defaultRgb = [
            'primary_rgb' => '59, 130, 246',
            'secondary_rgb' => '139, 92, 246',
            'accent_rgb' => '236, 72, 153',
        ];

        try {
            // ตรวจสอบว่าตารางมีอยู่หรือไม่
            if (! Schema::hasTable('windows_ui_settings')) {
                return $defaultRgb;
            }

            return [
                'primary_rgb' => \App\Models\WindowsUiSetting::get('primary_rgb', $defaultRgb['primary_rgb']),
                'secondary_rgb' => \App\Models\WindowsUiSetting::get('secondary_rgb', $defaultRgb['secondary_rgb']),
                'accent_rgb' => \App\Models\WindowsUiSetting::get('accent_rgb', $defaultRgb['accent_rgb']),
            ];
        } catch (\Exception $e) {
            Log::warning('Wiki: ไม่สามารถดึง Windows UI RGB ได้: '.$e->getMessage());

            return $defaultRgb;
        }
    }

    /**
     * ดึงสถิติจากฐานข้อมูลอย่างปลอดภัย
     *
     * ถ้าตารางยังไม่มีหรือเกิด error จะ return ค่า default
     *
     * @return array สถิติผู้ใช้, affiliates, commissions
     */
    private function getSafeDatabaseStats(): array
    {
        $defaultStats = [
            'users' => 0,
            'affiliates' => 0,
            'commissions' => 0,
        ];

        try {
            // นับ users
            if (Schema::hasTable('users')) {
                $defaultStats['users'] = User::count();
            }

            // นับ affiliates (mlm_members)
            if (Schema::hasTable('mlm_members')) {
                $defaultStats['affiliates'] = MlmMember::count();
            }

            // นับ commissions (mlm_commissions)
            if (Schema::hasTable('mlm_commissions')) {
                $defaultStats['commissions'] = MlmCommission::count();
            }

            return $defaultStats;
        } catch (\Exception $e) {
            Log::warning('Wiki: ไม่สามารถดึงสถิติฐานข้อมูลได้: '.$e->getMessage());

            return $defaultStats;
        }
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
            'results' => [],
        ]);
    }
}
