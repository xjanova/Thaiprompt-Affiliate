<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\UnifiedReportService;
use App\Exports\UnifiedReportExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * UnifiedReportController - ศูนย์รวมรายงานของ Admin
 *
 * รวมรายงานจากทุกระบบมาไว้ที่เดียว:
 * - รายงานภาพรวมผู้บริหาร (Executive Summary)
 * - รายงาน MLM/Affiliate
 * - รายงาน E-commerce
 * - รายงานการเงิน
 * - รายงาน AI Bot
 * - รายงานโรงแรม
 * - รายงาน POS
 * - รายงาน Crypto/TPIX
 *
 * พร้อมส่งออก Excel และแสดงกราฟวิเคราะห์
 */
class UnifiedReportController extends Controller
{
    /**
     * @var UnifiedReportService
     */
    protected UnifiedReportService $reportService;

    /**
     * สร้าง controller พร้อม inject service
     *
     * @param UnifiedReportService $reportService
     */
    public function __construct(UnifiedReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * หน้าหลักศูนย์รายงาน - แสดงรายการระบบทั้งหมด
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $systems = $this->reportService->getAvailableSystems();

        return view('admin.unified-reports.index', [
            'systems' => $systems,
            'pageTitle' => 'ศูนย์รายงานรวม',
            'pageDescription' => 'รายงานจากทุกระบบในที่เดียว พร้อมส่งออก Excel และกราฟวิเคราะห์',
        ]);
    }

    /**
     * รายงานภาพรวมผู้บริหาร (Executive Summary)
     *
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function executive(Request $request)
    {
        $period = $request->input('period', 'month');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $summary = $this->reportService->getExecutiveSummary($period, $startDate, $endDate);
        $trends = $this->reportService->getTrends($period);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => $summary,
                    'trends' => $trends,
                ],
            ]);
        }

        return view('admin.unified-reports.executive', [
            'summary' => $summary,
            'trends' => $trends,
            'period' => $period,
            'pageTitle' => 'รายงานภาพรวมผู้บริหาร',
        ]);
    }

    /**
     * รายงาน MLM/Affiliate
     *
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function mlm(Request $request)
    {
        $period = $request->input('period', 'month');
        $report = $this->reportService->getMlmReport($period);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $report,
            ]);
        }

        return view('admin.unified-reports.mlm', [
            'report' => $report,
            'period' => $period,
            'pageTitle' => 'รายงาน MLM/Affiliate',
        ]);
    }

    /**
     * รายงาน E-commerce
     *
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function ecommerce(Request $request)
    {
        $period = $request->input('period', 'month');
        $report = $this->reportService->getEcommerceReport($period);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $report,
            ]);
        }

        return view('admin.unified-reports.ecommerce', [
            'report' => $report,
            'period' => $period,
            'pageTitle' => 'รายงาน E-commerce',
        ]);
    }

    /**
     * รายงานการเงิน/กระเป๋าเงิน
     *
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function finance(Request $request)
    {
        $period = $request->input('period', 'month');
        $report = $this->reportService->getFinanceReport($period);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $report,
            ]);
        }

        return view('admin.unified-reports.finance', [
            'report' => $report,
            'period' => $period,
            'pageTitle' => 'รายงานการเงิน',
        ]);
    }

    /**
     * รายงาน AI Bot
     *
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function aiBot(Request $request)
    {
        $period = $request->input('period', 'month');
        $report = $this->reportService->getAiBotReport($period);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $report,
            ]);
        }

        return view('admin.unified-reports.ai-bot', [
            'report' => $report,
            'period' => $period,
            'pageTitle' => 'รายงาน AI Bot',
        ]);
    }

    /**
     * รายงานโรงแรม
     *
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function hotel(Request $request)
    {
        $period = $request->input('period', 'month');
        $report = $this->reportService->getHotelReport($period);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $report,
            ]);
        }

        return view('admin.unified-reports.hotel', [
            'report' => $report,
            'period' => $period,
            'pageTitle' => 'รายงานโรงแรม',
        ]);
    }

    /**
     * รายงาน POS
     *
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function pos(Request $request)
    {
        $period = $request->input('period', 'month');
        $report = $this->reportService->getPosReport($period);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $report,
            ]);
        }

        return view('admin.unified-reports.pos', [
            'report' => $report,
            'period' => $period,
            'pageTitle' => 'รายงาน POS',
        ]);
    }

    /**
     * รายงาน Crypto/TPIX
     *
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function crypto(Request $request)
    {
        $period = $request->input('period', 'month');
        $report = $this->reportService->getCryptoReport($period);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $report,
            ]);
        }

        return view('admin.unified-reports.crypto', [
            'report' => $report,
            'period' => $period,
            'pageTitle' => 'รายงาน Crypto/TPIX',
        ]);
    }

    /**
     * รายงาน HRM/บุคลากร
     *
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function hrm(Request $request)
    {
        $period = $request->input('period', 'month');
        $report = $this->reportService->getHrmReport($period);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $report,
            ]);
        }

        return view('admin.unified-reports.hrm', [
            'report' => $report,
            'period' => $period,
            'pageTitle' => 'รายงานบุคลากร (HRM)',
        ]);
    }

    /**
     * รายงานการเรียนรู้
     *
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function learning(Request $request)
    {
        $period = $request->input('period', 'month');
        $report = $this->reportService->getLearningReport($period);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $report,
            ]);
        }

        return view('admin.unified-reports.learning', [
            'report' => $report,
            'period' => $period,
            'pageTitle' => 'รายงานการเรียนรู้',
        ]);
    }

    /**
     * ข้อมูลแนวโน้ม (AJAX endpoint สำหรับกราฟ)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function trends(Request $request)
    {
        $period = $request->input('period', 'month');
        $groupBy = $request->input('group_by', 'day');

        $trends = $this->reportService->getTrends($period, $groupBy);

        return response()->json([
            'success' => true,
            'data' => $trends,
        ]);
    }

    /**
     * ส่งออกรายงานเป็น Excel
     *
     * @param Request $request
     * @param string $type ประเภทรายงาน
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export(Request $request, string $type = 'executive')
    {
        $period = $request->input('period', 'month');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // ดึงข้อมูลตามประเภทรายงาน
        $data = match ($type) {
            'executive' => $this->reportService->getExecutiveSummary($period, $startDate, $endDate),
            'mlm' => $this->reportService->getMlmReport($period),
            'ecommerce' => $this->reportService->getEcommerceReport($period),
            'finance' => $this->reportService->getFinanceReport($period),
            'ai_bot' => $this->reportService->getAiBotReport($period),
            'hotel' => $this->reportService->getHotelReport($period),
            'pos' => $this->reportService->getPosReport($period),
            'crypto' => $this->reportService->getCryptoReport($period),
            'hrm' => $this->reportService->getHrmReport($period),
            'learning' => $this->reportService->getLearningReport($period),
            default => $this->reportService->getExecutiveSummary($period, $startDate, $endDate),
        };

        $filename = "report-{$type}-" . now()->format('Y-m-d-His') . '.xlsx';

        return Excel::download(new UnifiedReportExport($type, $data, $period), $filename);
    }

    /**
     * ส่งออกรายงานเป็น CSV (fallback)
     *
     * @param Request $request
     * @param string $type ประเภทรายงาน
     * @return \Illuminate\Http\Response
     */
    public function exportCsv(Request $request, string $type = 'executive')
    {
        $period = $request->input('period', 'month');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // ดึงข้อมูลตามประเภทรายงาน
        $data = match ($type) {
            'executive' => $this->reportService->getExecutiveSummary($period, $startDate, $endDate),
            'mlm' => $this->reportService->getMlmReport($period),
            'ecommerce' => $this->reportService->getEcommerceReport($period),
            'finance' => $this->reportService->getFinanceReport($period),
            default => $this->reportService->getExecutiveSummary($period, $startDate, $endDate),
        };

        $filename = "report-{$type}-" . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($data, $type) {
            $file = fopen('php://output', 'w');

            // UTF-8 BOM สำหรับ Excel
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header ตามประเภทรายงาน
            $this->writeCsvHeaders($file, $type);

            // ข้อมูล
            $this->writeCsvData($file, $data, $type);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * เขียน header ของ CSV ตามประเภทรายงาน
     *
     * @param resource $file
     * @param string $type
     * @return void
     */
    protected function writeCsvHeaders($file, string $type): void
    {
        $headers = match ($type) {
            'executive' => ['หมวด', 'รายการ', 'ค่า', 'หน่วย'],
            'mlm' => ['รายการ', 'จำนวน', 'มูลค่า', 'หมายเหตุ'],
            'ecommerce' => ['รายการ', 'จำนวน', 'มูลค่า', 'หมายเหตุ'],
            'finance' => ['ประเภท', 'จำนวนรายการ', 'มูลค่า', 'หมายเหตุ'],
            default => ['รายการ', 'ค่า'],
        };

        fputcsv($file, $headers);
    }

    /**
     * เขียนข้อมูล CSV ตามประเภทรายงาน
     *
     * @param resource $file
     * @param array $data
     * @param string $type
     * @return void
     */
    protected function writeCsvData($file, array $data, string $type): void
    {
        if ($type === 'executive') {
            // ข้อมูลผู้ใช้
            if (isset($data['users'])) {
                fputcsv($file, ['ผู้ใช้', 'จำนวนทั้งหมด', $data['users']['total'], 'คน']);
                fputcsv($file, ['ผู้ใช้', 'ผู้ใช้ใหม่', $data['users']['new'], 'คน']);
                fputcsv($file, ['ผู้ใช้', 'ผู้ใช้ active', $data['users']['active'], 'คน']);
            }

            // รายได้
            if (isset($data['revenue'])) {
                fputcsv($file, ['รายได้', 'รายได้รวม', $data['revenue']['total'], 'บาท']);
                fputcsv($file, ['รายได้', 'E-commerce', $data['revenue']['ecommerce'], 'บาท']);
                fputcsv($file, ['รายได้', 'โรงแรม', $data['revenue']['hotel'], 'บาท']);
                fputcsv($file, ['รายได้', 'POS', $data['revenue']['pos'], 'บาท']);
            }

            // MLM
            if (isset($data['mlm'])) {
                fputcsv($file, ['MLM', 'สมาชิกทั้งหมด', $data['mlm']['total_affiliates'], 'คน']);
                fputcsv($file, ['MLM', 'สมาชิกใหม่', $data['mlm']['new_affiliates'], 'คน']);
                fputcsv($file, ['MLM', 'คอมมิชชั่นรวม', $data['mlm']['total_commissions'], 'บาท']);
            }
        } else {
            // แปลงข้อมูลแบบ recursive
            $this->flattenArrayToCsv($file, $data);
        }
    }

    /**
     * แปลง array เป็น CSV แบบ recursive
     *
     * @param resource $file
     * @param array $data
     * @param string $prefix
     * @return void
     */
    protected function flattenArrayToCsv($file, array $data, string $prefix = ''): void
    {
        foreach ($data as $key => $value) {
            $label = $prefix ? "{$prefix}.{$key}" : $key;

            if (is_array($value)) {
                if (isset($value[0]) && is_array($value[0])) {
                    // เป็น array of objects
                    foreach ($value as $i => $item) {
                        $this->flattenArrayToCsv($file, $item, "{$label}[{$i}]");
                    }
                } else {
                    $this->flattenArrayToCsv($file, $value, $label);
                }
            } else {
                fputcsv($file, [$label, $value]);
            }
        }
    }

    /**
     * Dashboard วิเคราะห์ธุรกิจ (Business Intelligence)
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function businessIntelligence(Request $request)
    {
        $period = $request->input('period', 'month');
        $summary = $this->reportService->getExecutiveSummary($period);
        $trends = $this->reportService->getTrends($period);
        $mlmReport = $this->reportService->getMlmReport($period);
        $ecommerceReport = $this->reportService->getEcommerceReport($period);
        $financeReport = $this->reportService->getFinanceReport($period);

        return view('admin.unified-reports.business-intelligence', [
            'summary' => $summary,
            'trends' => $trends,
            'mlmReport' => $mlmReport,
            'ecommerceReport' => $ecommerceReport,
            'financeReport' => $financeReport,
            'period' => $period,
            'pageTitle' => 'Business Intelligence Dashboard',
            'pageDescription' => 'วิเคราะห์ภาพรวมธุรกิจจากทุกมิติ',
        ]);
    }

    /**
     * เปรียบเทียบช่วงเวลา (Period Comparison)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function compare(Request $request)
    {
        $period1 = $request->input('period1', 'month');
        $period2 = $request->input('period2', 'year');

        $data1 = $this->reportService->getExecutiveSummary($period1);
        $data2 = $this->reportService->getExecutiveSummary($period2);

        // คำนวณการเปลี่ยนแปลง
        $comparison = [
            'users' => [
                'period1' => $data1['users']['new'],
                'period2' => $data2['users']['new'],
                'change' => $this->calculateChange($data1['users']['new'], $data2['users']['new']),
            ],
            'revenue' => [
                'period1' => $data1['revenue']['total'],
                'period2' => $data2['revenue']['total'],
                'change' => $this->calculateChange($data1['revenue']['total'], $data2['revenue']['total']),
            ],
            'mlm_commissions' => [
                'period1' => $data1['mlm']['total_commissions'],
                'period2' => $data2['mlm']['total_commissions'],
                'change' => $this->calculateChange($data1['mlm']['total_commissions'], $data2['mlm']['total_commissions']),
            ],
            'ecommerce_orders' => [
                'period1' => $data1['ecommerce']['total_orders'],
                'period2' => $data2['ecommerce']['total_orders'],
                'change' => $this->calculateChange($data1['ecommerce']['total_orders'], $data2['ecommerce']['total_orders']),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $comparison,
        ]);
    }

    /**
     * คำนวณเปอร์เซ็นต์การเปลี่ยนแปลง
     *
     * @param float $current
     * @param float $previous
     * @return array
     */
    protected function calculateChange(float $current, float $previous): array
    {
        if ($previous == 0) {
            return [
                'percentage' => $current > 0 ? 100 : 0,
                'direction' => $current > 0 ? 'up' : 'neutral',
            ];
        }

        $percentage = (($current - $previous) / $previous) * 100;

        return [
            'percentage' => round($percentage, 2),
            'direction' => $percentage > 0 ? 'up' : ($percentage < 0 ? 'down' : 'neutral'),
        ];
    }
}
