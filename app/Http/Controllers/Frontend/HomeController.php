<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\StorefrontController;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * หน้าแรกของเว็บ ('/') — ปัจจุบันคือ "หน้าร้าน" (storefront)
     *
     * owner สั่งสลับ: หน้าแรก = หน้าร้าน · หน้าแรกเดิม (ธีม V4 นวลทองคำ) = หน้า "รู้จักเรา" → about()
     * ยังคุม 2 อย่างไว้ที่นี่:
     *   1. setup guard (ติดตั้งใหม่ → เด้งไปหน้า setup)
     *   2. ชื่อ route 'home' → ลิงก์ route('home') ทั่วเว็บไม่ต้องแก้สักจุด
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function index(Request $request)
    {
        // ตรวจสอบว่าระบบต้อง setup หรือไม่
        // ⚠️ ห้ามลบ — ติดตั้งใหม่ที่ยังไม่มี super admin ต้องเด้งไปหน้า setup ก่อนเสมอ
        //    (ถ้าปล่อยผ่านจะเจอหน้าร้านเปล่าๆ แทนตัวช่วยติดตั้ง)
        if (! User::where('is_super_admin', true)->exists()) {
            return redirect()->route('setup.index');
        }

        // 🏠 หน้าแรกใหม่ = "หน้าร้าน" (storefront) ตามที่ owner สั่งสลับ
        //    คงชื่อ route 'home' ไว้เหมือนเดิม → ลิงก์ route('home') 64 จุดทั่วเว็บยังถูกต้อง
        //    (ความหมาย "หน้าแรก" ไม่เปลี่ยน แค่เนื้อหาเปลี่ยนเป็นหน้าร้าน)
        //    หน้าแรกเดิม (ธีม V4 นวลทองคำ) ย้ายไปเป็นหน้า "รู้จักเรา" → about()
        return app(StorefrontController::class)->index($request);
    }

    /**
     * หน้า "รู้จักเรา" — คือหน้าแรกเดิม (ธีม V4 นวลทองคำ) ที่ย้ายมาหลังสลับหน้าแรกเป็นหน้าร้าน
     *
     * owner เลือกให้รวมหน้าแนะนำตัวทั้งหมดเหลือหน้าเดียว → ทั้ง /about และ /about-us
     * มาจบที่หน้านี้ (aboutProfessional() redirect มาที่นี่) เมนู footer + navigation ชี้ที่เดียวกัน
     *
     * หมายเหตุ: หน้าเดิม frontend.about (สถิติเชิงเทคนิค: จำนวนตาราง/migration/controller)
     * ถูกเลิกใช้ตามคำสั่ง owner — ไฟล์ view ยังอยู่ใน git ถ้าต้องการย้อนกลับ
     *
     * @return \Illuminate\View\View
     */
    public function about()
    {
        // สินค้าแนะนำ (เฉพาะสินค้าที่พร้อมขายจริง: active + ไม่ซ่อน + ไม่บล็อก)
        $products = Product::publicVisible()
            ->with('images')
            ->latest('published_at')
            ->take(8)
            ->get();

        // สถิติแพลตฟอร์มสำหรับโชว์บนหน้ารู้จักเรา
        $stats = [
            'products' => Product::publicVisible()->count(),
            'categories' => ProductCategory::where('is_active', true)->count(),
            'members' => User::count(),
        ];

        return view('frontend.home-v4', [
            'products' => $products,
            'stats' => $stats,
        ]);
    }

    /**
     * /about-us — รวมเข้ากับหน้า "รู้จักเรา" หน้าเดียว (owner สั่งให้เหลือหน้าเดียว)
     *
     * ใช้ redirect ถาวร (301) แทนการ render ซ้ำ เพื่อไม่ให้เกิดเนื้อหาซ้ำสองที่อยู่ (duplicate content)
     * และคงชื่อ route 'about.professional' ไว้ → ลิงก์เดิมใน navigation ยังใช้ได้ ไม่ 404
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function aboutProfessional()
    {
        return redirect()->route('about', [], 301);
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
