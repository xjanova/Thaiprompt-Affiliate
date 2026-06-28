<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\User;

class HomeController extends Controller
{
    /**
     * Show the homepage - Professional & Trustworthy Design
     *
     * แสดงหน้าแรกใหม่ที่ออกแบบมาให้:
     * - มีความน่าเชื่อถือ มืออาชีพ
     * - ซ่อน sidebar เสมอ (ใช้ burger menu)
     * - มี top navigation bar
     * - แสดงข้อมูลโครงการปัจจุบัน
     * - **เชื่อมต่อกับระบบ Homepage Manager**
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // ตรวจสอบว่าระบบต้อง setup หรือไม่
        if (! User::where('is_super_admin', true)->exists()) {
            return redirect()->route('setup.index');
        }

        // สินค้าแนะนำสำหรับหน้าแรก (เฉพาะสินค้าที่พร้อมขายจริง: active + ไม่ซ่อน + ไม่บล็อก)
        $products = \App\Models\Product::publicVisible()
            ->with('images')
            ->latest('published_at')
            ->take(8)
            ->get();

        // สถิติแพลตฟอร์มสำหรับโชว์บนหน้าแรก
        $stats = [
            'products' => \App\Models\Product::publicVisible()->count(),
            'categories' => \App\Models\ProductCategory::where('is_active', true)->count(),
            'members' => User::count(),
        ];

        // หน้าแรกธีม V4 "นวลทองคำ" (อิงไฟล์ต้นแบบ .theme-new) + สินค้าแนะนำ + ลิงก์ร้านค้า
        return view('frontend.home-v4', [
            'products' => $products,
            'stats' => $stats,
        ]);
    }

    /**
     * Show the about page
     */
    public function about()
    {
        // Get version from package.json
        $version = '2.79.0'; // Default fallback
        $packageJsonPath = base_path('package.json');

        if (file_exists($packageJsonPath)) {
            $packageJson = json_decode(file_get_contents($packageJsonPath), true);
            if (isset($packageJson['version'])) {
                $version = $packageJson['version'];
            }
        }

        // Get comprehensive project stats
        $stats = [
            'version' => $version,
            'last_updated' => date('Y-m-d'),
            'total_users' => User::count(),
            'total_affiliates' => \App\Models\MlmMember::count(),
            'total_commissions' => \App\Models\MlmCommission::count(),
            'total_earnings' => \App\Models\MlmCommission::whereIn('status', ['approved', 'paid'])->sum('commission_amount') ?? 0,
            'database_tables' => 105,
            'database_models' => 113,
            'http_controllers' => 91,
            'migrations_count' => 136,
            'services_count' => 30,
            'api_endpoints' => 20,
        ];

        // Get MLM organization stats for visualization
        $mlmOrgStats = [
            'total_members' => \App\Models\MlmMember::count(),
            'active_members' => \App\Models\MlmMember::where('status', 'active')->count(),
            'total_levels' => \DB::table('mlm_genealogy')->max('depth') ?? 0,
            'monthly_growth' => \App\Models\MlmMember::whereMonth('created_at', date('m'))->count(),
        ];

        return view('frontend.about', compact('stats', 'mlmOrgStats'));
    }

    /**
     * Show the professional about page (PR version)
     */
    public function aboutProfessional()
    {
        // Get version from package.json (real-time)
        $version = '2.79.0'; // Default fallback
        $packageJsonPath = base_path('package.json');

        if (file_exists($packageJsonPath)) {
            $packageJson = json_decode(file_get_contents($packageJsonPath), true);
            if (isset($packageJson['version'])) {
                $version = $packageJson['version'];
            }
        }

        // Get comprehensive project stats
        $stats = [
            'version' => $version,
            'last_updated' => date('Y-m-d'),
            'total_users' => User::count(),
            'total_affiliates' => \App\Models\MlmMember::count(),
            'total_commissions' => \App\Models\MlmCommission::count(),
            'total_earnings' => \App\Models\MlmCommission::whereIn('status', ['approved', 'paid'])->sum('commission_amount') ?? 0,
            'database_tables' => 105,
            'database_models' => 113,
            'http_controllers' => 91,
            'migrations_count' => 136,
            'services_count' => 30,
            'api_endpoints' => 20,
        ];

        return view('frontend.about-professional', compact('stats'));
    }

    /**
     * Show the platform wiki (knowledge base)
     */
    public function platformWiki()
    {
        // Get version from CHANGELOG
        $version = '1.159.0'; // Default fallback
        $changelogPath = base_path('CHANGELOG.md');

        if (file_exists($changelogPath)) {
            $changelog = file_get_contents($changelogPath);
            // Extract latest version from CHANGELOG
            if (preg_match('/##\s*\[v?(\d+\.\d+\.\d+)\]/', $changelog, $matches)) {
                $version = $matches[1];
            }
        }

        // Get comprehensive project stats
        $stats = [
            'version' => $version,
            'last_updated' => date('Y-m-d'),
            'total_users' => User::count(),
            'total_affiliates' => \App\Models\MlmMember::count(),
            'total_commissions' => \App\Models\MlmCommission::count(),
            'database_tables' => 105,
            'database_models' => 113,
            'http_controllers' => 91,
            'migrations_count' => 136,
            'services_count' => 30,
            'api_endpoints' => 20,
        ];

        return view('frontend.platform-wiki', compact('stats'));
    }

    /**
     * Show the contact page
     */
    public function contact()
    {
        return view('frontend.contact');
    }

    // หน้าเก่า 3 Doors (investors, developers, community) และ presentation ถูกลบออกแล้ว
    // เปลี่ยนไปใช้หน้าแรกใหม่ที่มืออาชีพและน่าเชื่อถือกว่า
    // @deprecated 2025-12-03
}
