<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DemoDataUserGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Controller สำหรับจัดการข้อมูล Demo ในแอดมิน
 *
 * ให้แอดมินสามารถลบข้อมูลทดสอบผ่าน UI ได้สะดวก
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
            // 🛡️ (2026-10-04) K1-4: เลือกผ่าน DemoDataUserGuard — รายชื่อจริงแสดงในส่วน "ตรวจก่อนลบ" ของหน้านี้
            'description' => 'ลบบัญชี @example.com / @thaiprompt.com ที่ไม่ใช่ร้านทางการ แอดมิน เจ้าของร้าน ผู้ขายตลาดสด ไรเดอร์ หรือผู้ที่มีออเดอร์/เงินจริง',
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
            'description' => 'ลบข้อมูล KYC ของผู้ใช้ทดสอบที่ลบได้เท่านั้น (KYC ลูกค้าจริงไม่ถูกแตะ)',
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
        // แผนการลบผู้ใช้ทดสอบ (ยังไม่ลบ) — ใช้ทั้งนับสถิติและแสดงรายชื่อให้ตรวจก่อนกด
        $userPlan = $this->guard()->plan();

        // นับจำนวนข้อมูล demo ในแต่ละหมวดหมู่
        $stats = $this->getDemoDataStats($userPlan);

        // ตรวจสอบว่าเป็น production หรือไม่
        $isProduction = app()->environment('production');

        return view('admin.demo-data.index', [
            'categories' => $this->demoCategories,
            'stats' => $stats,
            'isProduction' => $isProduction,
            'userPlan' => $userPlan,
            'reasonLabels' => DemoDataUserGuard::REASON_LABELS,
            'pageTitle' => 'จัดการข้อมูลทดสอบ (Demo Data)',
        ]);
    }

    private function guard(): DemoDataUserGuard
    {
        return app(DemoDataUserGuard::class);
    }

    /**
     * ลบข้อมูล demo ตามหมวดหมู่
     *
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
                '--'.$category => true,
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
            // ไม่ส่งข้อความ exception ดิบขึ้นหน้าเว็บ (อาจมี SQL/รายละเอียดระบบ)
            \Illuminate\Support\Facades\Log::error('DemoData: clean failed', ['category' => $category, 'error' => $e->getMessage()]);

            return redirect()
                ->route('admin.demo-data.index')
                ->with('error', 'เกิดข้อผิดพลาดในการลบข้อมูล กรุณาตรวจสอบ log');
        }
    }

    /**
     * ดึงสถิติข้อมูล demo
     *
     * @param  array{deletable: array<int, array{id:int}>, protected: array<int, mixed>}|null  $userPlan  แผนจาก DemoDataUserGuard (ไม่ส่ง = คำนวณใหม่)
     * @return array
     */
    protected function getDemoDataStats(?array $userPlan = null)
    {
        $stats = [];

        // 🛡️ (2026-10-04) K1-4: หมวดผู้ใช้/KYC นับเฉพาะแถวที่ demo:reset จะลบจริง
        $userPlan ??= $this->guard()->plan();
        $demoUserIds = array_map(fn (array $row) => (int) $row['id'], $userPlan['deletable']);

        foreach ($this->demoCategories as $key => $category) {
            if ($key === 'users') {
                $stats[$key] = count($demoUserIds);

                continue;
            }

            if ($key === 'kyc') {
                $stats[$key] = $this->countKycOf($demoUserIds);

                continue;
            }

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
     */
    protected function getTableCount(string $table, string $category): int
    {
        // SECURITY: conditions ถูกกำหนดไว้ในโค้ดเท่านั้น ห้ามรับค่าจาก user input
        // (ผู้ใช้/KYC นับผ่าน DemoDataUserGuard ใน getDemoDataStats แล้ว — ไม่มาถึงตรงนี้)
        $conditions = [
            'pages' => "type IN ('about', 'faq', 'contact', 'terms', 'privacy', 'custom')",
            'line' => null, // ทั้งหมด
            'accounting' => "description LIKE '%demo%' OR description LIKE '%ทดสอบ%'",
        ];

        // ถ้าตารางไม่ตรงกับ category ให้นับทั้งหมด
        if ($category === 'pages' && $table === 'pages') {
            return DB::table($table)->whereRaw($conditions['pages'])->count();
        } elseif ($category === 'accounting' && in_array($table, ['accounting_journal_entries', 'accounting_transactions', 'accounting_accounts'])) {
            return DB::table($table)->whereRaw($conditions['accounting'])->count();
        } else {
            // LINE และอื่นๆ นับทั้งหมด
            return DB::table($table)->count();
        }
    }

    /**
     * จำนวน KYC ของผู้ใช้ชุดนี้
     *
     * @param  array<int, int>  $userIds
     */
    protected function countKycOf(array $userIds): int
    {
        if ($userIds === [] || ! Schema::hasTable('kyc_verifications')) {
            return 0;
        }

        $count = 0;
        foreach (array_chunk($userIds, 500) as $chunk) {
            $count += DB::table('kyc_verifications')->whereIn('user_id', $chunk)->count();
        }

        return $count;
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
