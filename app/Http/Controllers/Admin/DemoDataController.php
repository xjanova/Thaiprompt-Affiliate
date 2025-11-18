<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Controller สำหรับจัดการข้อมูล Demo ในแอดมิน
 *
 * ให้แอดมินสามารถลบข้อมูลทดสอบผ่าน UI ได้สะดวก
 *
 * @package App\Http\Controllers\Admin
 */
class DemoDataController extends Controller
{
    /**
     * รายการหมวดหมู่ Demo Data
     *
     * @var array
     */
    protected $demoCategories = [
        'users' => [
            'label' => 'ผู้ใช้ทดสอบ',
            'icon' => '👥',
            'description' => 'ลบบัญชีผู้ใช้ที่มี email @example.com, @thaiprompt.com',
            'tables' => ['users', 'affiliates', 'commissions'],
            'color' => 'blue',
        ],
        'pages' => [
            'label' => 'หน้าเพจตัวอย่าง',
            'icon' => '📄',
            'description' => 'ลบหน้า About, FAQ, Contact, Terms, Privacy',
            'tables' => ['pages'],
            'color' => 'green',
        ],
        'kyc' => [
            'label' => 'KYC ตัวอย่าง',
            'icon' => '✅',
            'description' => 'ลบข้อมูล KYC verifications ทั้งหมด',
            'tables' => ['kyc_verifications'],
            'color' => 'yellow',
        ],
        'line' => [
            'label' => 'LINE Sessions ตัวอย่าง',
            'icon' => '📱',
            'description' => 'ลบ LINE signup sessions และ bot profiles',
            'tables' => ['line_signup_sessions', 'line_bot_ai_profiles'],
            'color' => 'purple',
        ],
        'accounting' => [
            'label' => 'ข้อมูลบัญชีตัวอย่าง',
            'icon' => '💰',
            'description' => 'ลบรายการบัญชีที่มีคำว่า "demo" หรือ "ทดสอบ"',
            'tables' => ['accounting_journal_entries', 'accounting_transactions', 'accounting_accounts'],
            'color' => 'red',
        ],
    ];

    /**
     * แสดงหน้าจัดการ Demo Data
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // นับจำนวนข้อมูล demo ในแต่ละหมวดหมู่
        $stats = $this->getDemoDataStats();

        // ตรวจสอบว่าเป็น production หรือไม่
        $isProduction = app()->environment('production');

        return view('admin.demo-data.index', [
            'categories' => $this->demoCategories,
            'stats' => $stats,
            'isProduction' => $isProduction,
            'pageTitle' => 'จัดการข้อมูลทดสอบ (Demo Data)',
        ]);
    }

    /**
     * ลบข้อมูล demo ตามหมวดหมู่
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function clean(Request $request)
    {
        $request->validate([
            'category' => 'required|in:all,users,pages,kyc,line,accounting',
        ]);

        $category = $request->input('category');

        try {
            // รัน Artisan command
            $exitCode = Artisan::call('demo:reset', [
                '--' . $category => true,
                '--force' => true,
            ]);

            if ($exitCode === 0) {
                return redirect()
                    ->route('admin.demo-data.index')
                    ->with('success', 'ลบข้อมูลทดสอบสำเร็จ!');
            } else {
                return redirect()
                    ->route('admin.demo-data.index')
                    ->with('error', 'เกิดข้อผิดพลาดในการลบข้อมูล');
            }
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.demo-data.index')
                ->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * ดึงสถิติข้อมูล demo
     *
     * @return array
     */
    protected function getDemoDataStats()
    {
        $stats = [];

        foreach ($this->demoCategories as $key => $category) {
            $count = 0;

            foreach ($category['tables'] as $table) {
                if (Schema::hasTable($table)) {
                    try {
                        // นับข้อมูลตามเงื่อนไข
                        $count += $this->getTableCount($table, $key);
                    } catch (\Exception $e) {
                        // ถ้า error ให้ข้ามไป
                        continue;
                    }
                }
            }

            $stats[$key] = $count;
        }

        return $stats;
    }

    /**
     * นับจำนวนข้อมูลในตารางตามเงื่อนไข
     *
     * @param string $table
     * @param string $category
     * @return int
     */
    protected function getTableCount(string $table, string $category): int
    {
        $conditions = [
            'users' => "email LIKE '%@example.com' OR email LIKE '%@thaiprompt.com'",
            'pages' => "type IN ('about', 'faq', 'contact', 'terms', 'privacy', 'custom')",
            'kyc' => "status IN ('pending', 'approved', 'rejected')",
            'line' => null, // ทั้งหมด
            'accounting' => "description LIKE '%demo%' OR description LIKE '%ทดสอบ%'",
        ];

        // ถ้าตารางไม่ตรงกับ category ให้นับทั้งหมด
        if ($category === 'users' && $table === 'users') {
            return DB::table($table)->whereRaw($conditions['users'])->count();
        } elseif ($category === 'pages' && $table === 'pages') {
            return DB::table($table)->whereRaw($conditions['pages'])->count();
        } elseif ($category === 'kyc' && $table === 'kyc_verifications') {
            return DB::table($table)->whereRaw($conditions['kyc'])->count();
        } elseif ($category === 'accounting' && in_array($table, ['accounting_journal_entries', 'accounting_transactions', 'accounting_accounts'])) {
            return DB::table($table)->whereRaw($conditions['accounting'])->count();
        } else {
            // LINE และอื่นๆ นับทั้งหมด
            return DB::table($table)->count();
        }
    }

    /**
     * แสดง API สำหรับดึงสถิติ (AJAX)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats()
    {
        $stats = $this->getDemoDataStats();

        return response()->json([
            'success' => true,
            'data' => $stats,
            'total' => array_sum($stats),
        ]);
    }
}
