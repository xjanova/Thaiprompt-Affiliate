<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * UnifiedReportExport - ส่งออกรายงานรวมเป็น Excel
 *
 * รองรับการส่งออกรายงานหลายประเภท:
 * - Executive Summary
 * - MLM Report
 * - E-commerce Report
 * - Finance Report
 * - AI Bot Report
 * - Hotel Report
 * - POS Report
 * - Crypto Report
 */
class UnifiedReportExport implements WithMultipleSheets
{
    /**
     * @var string ประเภทรายงาน
     */
    protected string $type;

    /**
     * @var array ข้อมูลรายงาน
     */
    protected array $data;

    /**
     * @var string ช่วงเวลา
     */
    protected string $period;

    /**
     * สร้าง Export instance
     *
     * @param  string  $type  ประเภทรายงาน
     * @param  array  $data  ข้อมูลรายงาน
     * @param  string  $period  ช่วงเวลา
     */
    public function __construct(string $type, array $data, string $period = 'month')
    {
        $this->type = $type;
        $this->data = $data;
        $this->period = $period;
    }

    /**
     * สร้าง sheets ตามประเภทรายงาน
     */
    public function sheets(): array
    {
        return match ($this->type) {
            'executive' => $this->getExecutiveSheets(),
            'mlm' => $this->getMlmSheets(),
            'ecommerce' => $this->getEcommerceSheets(),
            'finance' => $this->getFinanceSheets(),
            'ai_bot' => $this->getAiBotSheets(),
            'hotel' => $this->getHotelSheets(),
            'pos' => $this->getPosSheets(),
            'crypto' => $this->getCryptoSheets(),
            default => $this->getExecutiveSheets(),
        };
    }

    /**
     * Sheets สำหรับ Executive Summary
     */
    protected function getExecutiveSheets(): array
    {
        return [
            new ExecutiveSummarySheet($this->data, $this->period),
            new UsersSheet($this->data['users'] ?? []),
            new RevenueSheet($this->data['revenue'] ?? []),
            new MlmSummarySheet($this->data['mlm'] ?? []),
            new EcommerceSummarySheet($this->data['ecommerce'] ?? []),
        ];
    }

    /**
     * Sheets สำหรับ MLM Report
     */
    protected function getMlmSheets(): array
    {
        return [
            new MlmSummarySheet($this->data['summary'] ?? []),
            new RankDistributionSheet($this->data['rank_distribution'] ?? []),
            new TopEarnersSheet($this->data['top_earners'] ?? []),
            new TopRecruitersSheet($this->data['top_recruiters'] ?? []),
            new CommissionByTypeSheet($this->data['commission_by_type'] ?? []),
        ];
    }

    /**
     * Sheets สำหรับ E-commerce Report
     */
    protected function getEcommerceSheets(): array
    {
        return [
            new EcommerceSummarySheet($this->data['summary'] ?? []),
            new TopProductsSheet($this->data['top_products'] ?? []),
            new SalesByCategorySheet($this->data['sales_by_category'] ?? []),
            new OrderStatusSheet($this->data['order_status_distribution'] ?? []),
        ];
    }

    /**
     * Sheets สำหรับ Finance Report
     */
    protected function getFinanceSheets(): array
    {
        return [
            new FinanceSummarySheet($this->data['summary'] ?? []),
            new TransactionTypesSheet($this->data['transaction_types'] ?? []),
            new TopWalletsSheet($this->data['top_wallets'] ?? []),
            new DailyFlowSheet($this->data['daily_flow'] ?? []),
        ];
    }

    /**
     * Sheets สำหรับ AI Bot Report
     */
    protected function getAiBotSheets(): array
    {
        return [
            new AiBotSummarySheet($this->data['summary'] ?? []),
            new PopularBotsSheet($this->data['popular_bots'] ?? []),
        ];
    }

    /**
     * Sheets สำหรับ Hotel Report
     */
    protected function getHotelSheets(): array
    {
        return [
            new HotelSummarySheet($this->data['summary'] ?? []),
            new RevenueByHotelSheet($this->data['revenue_by_hotel'] ?? []),
            new BookingStatusSheet($this->data['booking_status'] ?? []),
        ];
    }

    /**
     * Sheets สำหรับ POS Report
     */
    protected function getPosSheets(): array
    {
        return [
            new PosSummarySheet($this->data['summary'] ?? []),
            new PeakHoursSheet($this->data['peak_hours'] ?? []),
            new PaymentMethodsSheet($this->data['payment_methods'] ?? []),
        ];
    }

    /**
     * Sheets สำหรับ Crypto Report
     */
    protected function getCryptoSheets(): array
    {
        return [
            new CryptoSummarySheet($this->data['summary'] ?? []),
            new CryptoTransactionTypesSheet($this->data['transaction_types'] ?? []),
        ];
    }
}

/**
 * Base Sheet Class - คลาสพื้นฐานสำหรับทุก Sheet
 */
abstract class BaseReportSheet implements FromCollection, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    protected array $data;

    protected string $title;

    public function __construct(array $data, string $title = 'Sheet')
    {
        $this->data = $data;
        $this->title = $title;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            // Header row style
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],
            // All cells border
            'A:Z' => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC'],
                    ],
                ],
            ],
        ];
    }
}

/**
 * Executive Summary Sheet
 */
class ExecutiveSummarySheet extends BaseReportSheet
{
    protected string $period;

    public function __construct(array $data, string $period)
    {
        parent::__construct($data, 'สรุปภาพรวม');
        $this->period = $period;
    }

    public function headings(): array
    {
        return ['หมวดหมู่', 'รายการ', 'ค่า', 'หน่วย'];
    }

    public function collection(): Collection
    {
        $rows = collect();

        // ข้อมูลช่วงเวลา
        $rows->push(['ช่วงเวลา', 'เริ่มต้น', $this->data['period']['start'] ?? '-', '']);
        $rows->push(['ช่วงเวลา', 'สิ้นสุด', $this->data['period']['end'] ?? '-', '']);
        $rows->push(['', '', '', '']);

        // ผู้ใช้
        if (isset($this->data['users'])) {
            $rows->push(['ผู้ใช้', 'จำนวนทั้งหมด', $this->data['users']['total'] ?? 0, 'คน']);
            $rows->push(['ผู้ใช้', 'ผู้ใช้ใหม่', $this->data['users']['new'] ?? 0, 'คน']);
            $rows->push(['ผู้ใช้', 'ผู้ใช้ Active', $this->data['users']['active'] ?? 0, 'คน']);
            $rows->push(['ผู้ใช้', 'ยืนยันอีเมลแล้ว', $this->data['users']['verified'] ?? 0, 'คน']);
        }

        $rows->push(['', '', '', '']);

        // รายได้
        if (isset($this->data['revenue'])) {
            $rows->push(['รายได้', 'รายได้รวม', number_format($this->data['revenue']['total'] ?? 0, 2), 'บาท']);
            $rows->push(['รายได้', 'E-commerce', number_format($this->data['revenue']['ecommerce'] ?? 0, 2), 'บาท']);
            $rows->push(['รายได้', 'โรงแรม', number_format($this->data['revenue']['hotel'] ?? 0, 2), 'บาท']);
            $rows->push(['รายได้', 'POS', number_format($this->data['revenue']['pos'] ?? 0, 2), 'บาท']);
            $rows->push(['รายได้', 'บริการ', number_format($this->data['revenue']['service'] ?? 0, 2), 'บาท']);
        }

        $rows->push(['', '', '', '']);

        // MLM
        if (isset($this->data['mlm'])) {
            $rows->push(['MLM', 'สมาชิกทั้งหมด', $this->data['mlm']['total_affiliates'] ?? 0, 'คน']);
            $rows->push(['MLM', 'สมาชิกใหม่', $this->data['mlm']['new_affiliates'] ?? 0, 'คน']);
            $rows->push(['MLM', 'สมาชิก Active', $this->data['mlm']['active_affiliates'] ?? 0, 'คน']);
            $rows->push(['MLM', 'คอมมิชชั่นรวม', number_format($this->data['mlm']['total_commissions'] ?? 0, 2), 'บาท']);
            $rows->push(['MLM', 'คอมมิชชั่นจ่ายแล้ว', number_format($this->data['mlm']['paid_commissions'] ?? 0, 2), 'บาท']);
            $rows->push(['MLM', 'คอมมิชชั่นรอจ่าย', number_format($this->data['mlm']['pending_commissions'] ?? 0, 2), 'บาท']);
        }

        return $rows;
    }
}

/**
 * Users Sheet
 */
class UsersSheet extends BaseReportSheet
{
    public function __construct(array $data)
    {
        parent::__construct($data, 'ผู้ใช้');
    }

    public function headings(): array
    {
        return ['รายการ', 'จำนวน'];
    }

    public function collection(): Collection
    {
        return collect([
            ['จำนวนผู้ใช้ทั้งหมด', $this->data['total'] ?? 0],
            ['ผู้ใช้ใหม่ในช่วงนี้', $this->data['new'] ?? 0],
            ['ผู้ใช้ที่ Active', $this->data['active'] ?? 0],
            ['ยืนยันอีเมลแล้ว', $this->data['verified'] ?? 0],
        ]);
    }
}

/**
 * Revenue Sheet
 */
class RevenueSheet extends BaseReportSheet
{
    public function __construct(array $data)
    {
        parent::__construct($data, 'รายได้');
    }

    public function headings(): array
    {
        return ['แหล่งรายได้', 'มูลค่า (บาท)'];
    }

    public function collection(): Collection
    {
        return collect([
            ['E-commerce', number_format($this->data['ecommerce'] ?? 0, 2)],
            ['โรงแรม', number_format($this->data['hotel'] ?? 0, 2)],
            ['POS', number_format($this->data['pos'] ?? 0, 2)],
            ['บริการ', number_format($this->data['service'] ?? 0, 2)],
            ['', ''],
            ['รวมทั้งหมด', number_format($this->data['total'] ?? 0, 2)],
        ]);
    }
}

/**
 * MLM Summary Sheet
 */
class MlmSummarySheet extends BaseReportSheet
{
    public function __construct(array $data)
    {
        parent::__construct($data, 'MLM สรุป');
    }

    public function headings(): array
    {
        return ['รายการ', 'จำนวน/มูลค่า'];
    }

    public function collection(): Collection
    {
        return collect([
            ['สมาชิกทั้งหมด', $this->data['total_affiliates'] ?? 0],
            ['สมาชิกใหม่', $this->data['new_affiliates'] ?? 0],
            ['สมาชิก Active', $this->data['active_affiliates'] ?? 0],
            ['', ''],
            ['คอมมิชชั่นรวม', number_format($this->data['total_commissions'] ?? 0, 2).' บาท'],
            ['จ่ายแล้ว', number_format($this->data['paid_commissions'] ?? 0, 2).' บาท'],
            ['รอจ่าย', number_format($this->data['pending_commissions'] ?? 0, 2).' บาท'],
        ]);
    }
}

/**
 * E-commerce Summary Sheet
 */
class EcommerceSummarySheet extends BaseReportSheet
{
    public function __construct(array $data)
    {
        parent::__construct($data, 'E-commerce สรุป');
    }

    public function headings(): array
    {
        return ['รายการ', 'จำนวน/มูลค่า'];
    }

    public function collection(): Collection
    {
        return collect([
            ['คำสั่งซื้อทั้งหมด', $this->data['total_orders'] ?? 0],
            ['คำสั่งซื้อสำเร็จ', $this->data['completed_orders'] ?? 0],
            ['', ''],
            ['รายได้รวม', number_format($this->data['total_revenue'] ?? 0, 2).' บาท'],
            ['มูลค่าเฉลี่ยต่อคำสั่งซื้อ', number_format($this->data['average_order_value'] ?? 0, 2).' บาท'],
            ['', ''],
            ['สินค้าทั้งหมด', $this->data['total_products'] ?? 0],
        ]);
    }
}

/**
 * Rank Distribution Sheet
 */
class RankDistributionSheet extends BaseReportSheet
{
    public function __construct(array $data)
    {
        parent::__construct($data, 'การกระจาย Rank');
    }

    public function headings(): array
    {
        return ['Rank', 'จำนวนสมาชิก'];
    }

    public function collection(): Collection
    {
        return collect($this->data)->map(function ($item) {
            return [
                $item['name'] ?? $item->name ?? '-',
                $item['count'] ?? $item->count ?? 0,
            ];
        });
    }
}

/**
 * Top Earners Sheet
 */
class TopEarnersSheet extends BaseReportSheet
{
    public function __construct(array $data)
    {
        parent::__construct($data, 'ผู้มีรายได้สูงสุด');
    }

    public function headings(): array
    {
        return ['ลำดับ', 'ชื่อ', 'อีเมล', 'รายได้รวม (บาท)'];
    }

    public function collection(): Collection
    {
        return collect($this->data)->map(function ($item, $index) {
            return [
                $index + 1,
                $item['user']['name'] ?? '-',
                $item['user']['email'] ?? '-',
                number_format($item['total_earned'] ?? 0, 2),
            ];
        });
    }
}

/**
 * Top Recruiters Sheet
 */
class TopRecruitersSheet extends BaseReportSheet
{
    public function __construct(array $data)
    {
        parent::__construct($data, 'ผู้แนะนำสูงสุด');
    }

    public function headings(): array
    {
        return ['ลำดับ', 'ชื่อ', 'อีเมล', 'จำนวนผู้ถูกแนะนำ'];
    }

    public function collection(): Collection
    {
        return collect($this->data)->map(function ($item, $index) {
            return [
                $index + 1,
                $item['name'] ?? '-',
                $item['email'] ?? '-',
                $item['referrals_count'] ?? 0,
            ];
        });
    }
}

/**
 * Commission By Type Sheet
 */
class CommissionByTypeSheet extends BaseReportSheet
{
    public function __construct(array $data)
    {
        parent::__construct($data, 'คอมมิชชั่นตามประเภท');
    }

    public function headings(): array
    {
        return ['ประเภท', 'จำนวนรายการ', 'มูลค่ารวม (บาท)'];
    }

    public function collection(): Collection
    {
        return collect($this->data)->map(function ($item) {
            return [
                $item['type'] ?? '-',
                $item['count'] ?? 0,
                number_format($item['total'] ?? 0, 2),
            ];
        });
    }
}

/**
 * Top Products Sheet
 */
class TopProductsSheet extends BaseReportSheet
{
    public function __construct(array $data)
    {
        parent::__construct($data, 'สินค้าขายดี');
    }

    public function headings(): array
    {
        return ['ลำดับ', 'ชื่อสินค้า', 'จำนวนขาย', 'รายได้ (บาท)'];
    }

    public function collection(): Collection
    {
        return collect($this->data)->map(function ($item, $index) {
            return [
                $index + 1,
                $item['name'] ?? '-',
                $item['total_sold'] ?? 0,
                number_format($item['total_revenue'] ?? 0, 2),
            ];
        });
    }
}

/**
 * Sales By Category Sheet
 */
class SalesByCategorySheet extends BaseReportSheet
{
    public function __construct(array $data)
    {
        parent::__construct($data, 'ยอดขายตามหมวด');
    }

    public function headings(): array
    {
        return ['หมวดหมู่', 'รายได้ (บาท)'];
    }

    public function collection(): Collection
    {
        return collect($this->data)->map(function ($item) {
            return [
                $item['name'] ?? '-',
                number_format($item['total_revenue'] ?? 0, 2),
            ];
        });
    }
}

/**
 * Order Status Sheet
 */
class OrderStatusSheet extends BaseReportSheet
{
    public function __construct(array $data)
    {
        parent::__construct($data, 'สถานะคำสั่งซื้อ');
    }

    public function headings(): array
    {
        return ['สถานะ', 'จำนวน'];
    }

    public function collection(): Collection
    {
        return collect($this->data)->map(function ($item) {
            return [
                $item['status'] ?? '-',
                $item['count'] ?? 0,
            ];
        });
    }
}

/**
 * Finance Summary Sheet
 */
class FinanceSummarySheet extends BaseReportSheet
{
    public function __construct(array $data)
    {
        parent::__construct($data, 'การเงินสรุป');
    }

    public function headings(): array
    {
        return ['รายการ', 'มูลค่า'];
    }

    public function collection(): Collection
    {
        return collect([
            ['ยอดคงเหลือรวม', number_format($this->data['total_balance'] ?? 0, 2).' บาท'],
            ['ฝากรวม', number_format($this->data['total_deposits'] ?? 0, 2).' บาท'],
            ['ถอนรวม', number_format($this->data['total_withdrawals'] ?? 0, 2).' บาท'],
            ['จำนวน Transaction', $this->data['total_transactions'] ?? 0],
            ['', ''],
            ['Net Flow', number_format($this->data['net_flow'] ?? 0, 2).' บาท'],
        ]);
    }
}

/**
 * Transaction Types Sheet
 */
class TransactionTypesSheet extends BaseReportSheet
{
    public function __construct(array $data)
    {
        parent::__construct($data, 'ประเภท Transaction');
    }

    public function headings(): array
    {
        return ['ประเภท', 'จำนวน', 'มูลค่า (บาท)'];
    }

    public function collection(): Collection
    {
        return collect($this->data)->map(function ($item) {
            return [
                $item['type'] ?? '-',
                $item['count'] ?? 0,
                number_format($item['total'] ?? 0, 2),
            ];
        });
    }
}

/**
 * Top Wallets Sheet
 */
class TopWalletsSheet extends BaseReportSheet
{
    public function __construct(array $data)
    {
        parent::__construct($data, 'กระเป๋าเงินยอดสูง');
    }

    public function headings(): array
    {
        return ['ลำดับ', 'ชื่อผู้ใช้', 'อีเมล', 'ยอดคงเหลือ (บาท)'];
    }

    public function collection(): Collection
    {
        return collect($this->data)->map(function ($item, $index) {
            return [
                $index + 1,
                $item['user']['name'] ?? '-',
                $item['user']['email'] ?? '-',
                number_format($item['balance'] ?? 0, 2),
            ];
        });
    }
}

/**
 * Daily Flow Sheet
 */
class DailyFlowSheet extends BaseReportSheet
{
    public function __construct(array $data)
    {
        parent::__construct($data, 'Flow รายวัน');
    }

    public function headings(): array
    {
        return ['วันที่', 'ฝาก (บาท)', 'ถอน (บาท)', 'Net (บาท)'];
    }

    public function collection(): Collection
    {
        return collect($this->data)->map(function ($item) {
            $deposits = $item['deposits'] ?? 0;
            $withdrawals = $item['withdrawals'] ?? 0;

            return [
                $item['date'] ?? '-',
                number_format($deposits, 2),
                number_format($withdrawals, 2),
                number_format($deposits - $withdrawals, 2),
            ];
        });
    }
}

/**
 * AI Bot Summary Sheet
 */
class AiBotSummarySheet extends BaseReportSheet
{
    public function __construct(array $data)
    {
        parent::__construct($data, 'AI Bot สรุป');
    }

    public function headings(): array
    {
        return ['รายการ', 'จำนวน'];
    }

    public function collection(): Collection
    {
        return collect([
            ['Bot ทั้งหมด', $this->data['total_bots'] ?? 0],
            ['Bot ที่ Active', $this->data['active_bots'] ?? 0],
            ['ข้อความทั้งหมด', $this->data['total_messages'] ?? 0],
        ]);
    }
}

/**
 * Popular Bots Sheet
 */
class PopularBotsSheet extends BaseReportSheet
{
    public function __construct(array $data)
    {
        parent::__construct($data, 'Bot ยอดนิยม');
    }

    public function headings(): array
    {
        return ['ลำดับ', 'ชื่อ Bot', 'จำนวนข้อความ'];
    }

    public function collection(): Collection
    {
        return collect($this->data)->map(function ($item, $index) {
            return [
                $index + 1,
                $item['name'] ?? '-',
                $item['messages_count'] ?? 0,
            ];
        });
    }
}

/**
 * Hotel Summary Sheet
 */
class HotelSummarySheet extends BaseReportSheet
{
    public function __construct(array $data)
    {
        parent::__construct($data, 'โรงแรมสรุป');
    }

    public function headings(): array
    {
        return ['รายการ', 'จำนวน/มูลค่า'];
    }

    public function collection(): Collection
    {
        return collect([
            ['การจองทั้งหมด', $this->data['total_bookings'] ?? 0],
            ['การจองสำเร็จ', $this->data['completed_bookings'] ?? 0],
            ['รายได้รวม', number_format($this->data['total_revenue'] ?? 0, 2).' บาท'],
        ]);
    }
}

/**
 * Revenue By Hotel Sheet
 */
class RevenueByHotelSheet extends BaseReportSheet
{
    public function __construct(array $data)
    {
        parent::__construct($data, 'รายได้ตามโรงแรม');
    }

    public function headings(): array
    {
        return ['ลำดับ', 'ชื่อโรงแรม', 'รายได้ (บาท)'];
    }

    public function collection(): Collection
    {
        return collect($this->data)->map(function ($item, $index) {
            return [
                $index + 1,
                $item['hotel']['name'] ?? '-',
                number_format($item['total_revenue'] ?? 0, 2),
            ];
        });
    }
}

/**
 * Booking Status Sheet
 */
class BookingStatusSheet extends BaseReportSheet
{
    public function __construct(array $data)
    {
        parent::__construct($data, 'สถานะการจอง');
    }

    public function headings(): array
    {
        return ['สถานะ', 'จำนวน'];
    }

    public function collection(): Collection
    {
        return collect($this->data)->map(function ($item) {
            return [
                $item['status'] ?? '-',
                $item['count'] ?? 0,
            ];
        });
    }
}

/**
 * POS Summary Sheet
 */
class PosSummarySheet extends BaseReportSheet
{
    public function __construct(array $data)
    {
        parent::__construct($data, 'POS สรุป');
    }

    public function headings(): array
    {
        return ['รายการ', 'จำนวน/มูลค่า'];
    }

    public function collection(): Collection
    {
        return collect([
            ['Transaction ทั้งหมด', $this->data['total_transactions'] ?? 0],
            ['รายได้รวม', number_format($this->data['total_revenue'] ?? 0, 2).' บาท'],
            ['เฉลี่ยต่อ Transaction', number_format($this->data['average_transaction'] ?? 0, 2).' บาท'],
        ]);
    }
}

/**
 * Peak Hours Sheet
 */
class PeakHoursSheet extends BaseReportSheet
{
    public function __construct(array $data)
    {
        parent::__construct($data, 'ชั่วโมงขายดี');
    }

    public function headings(): array
    {
        return ['ชั่วโมง', 'จำนวน Transaction', 'รายได้ (บาท)'];
    }

    public function collection(): Collection
    {
        return collect($this->data)->map(function ($item) {
            return [
                sprintf('%02d:00', $item['hour'] ?? 0),
                $item['transactions'] ?? 0,
                number_format($item['revenue'] ?? 0, 2),
            ];
        });
    }
}

/**
 * Payment Methods Sheet
 */
class PaymentMethodsSheet extends BaseReportSheet
{
    public function __construct(array $data)
    {
        parent::__construct($data, 'วิธีชำระเงิน');
    }

    public function headings(): array
    {
        return ['วิธีชำระ', 'จำนวน', 'มูลค่า (บาท)'];
    }

    public function collection(): Collection
    {
        return collect($this->data)->map(function ($item) {
            return [
                $item['payment_method'] ?? '-',
                $item['count'] ?? 0,
                number_format($item['total'] ?? 0, 2),
            ];
        });
    }
}

/**
 * Crypto Summary Sheet
 */
class CryptoSummarySheet extends BaseReportSheet
{
    public function __construct(array $data)
    {
        parent::__construct($data, 'Crypto สรุป');
    }

    public function headings(): array
    {
        return ['รายการ', 'จำนวน/มูลค่า'];
    }

    public function collection(): Collection
    {
        return collect([
            ['Transaction ทั้งหมด', $this->data['total_transactions'] ?? 0],
            ['Volume รวม', number_format($this->data['total_volume'] ?? 0, 8)],
        ]);
    }
}

/**
 * Crypto Transaction Types Sheet
 */
class CryptoTransactionTypesSheet extends BaseReportSheet
{
    public function __construct(array $data)
    {
        parent::__construct($data, 'ประเภท Crypto TX');
    }

    public function headings(): array
    {
        return ['ประเภท', 'จำนวน', 'Volume'];
    }

    public function collection(): Collection
    {
        return collect($this->data)->map(function ($item) {
            return [
                $item['type'] ?? '-',
                $item['count'] ?? 0,
                number_format($item['total'] ?? 0, 8),
            ];
        });
    }
}
