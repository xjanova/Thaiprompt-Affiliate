<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailCampaign;
use App\Models\EmailLog;
use App\Models\EmailProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * สถิติและการวิเคราะห์อีเมล
 *
 * Controller สำหรับแสดงสถิติขั้นสูงของระบบอีเมล
 */
class EmailAnalyticsController extends Controller
{
    /**
     * แสดง Analytics Dashboard
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // ช่วงเวลา (default: 30 วันล่าสุด)
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        // สถิติรวม
        $overallStats = $this->getOverallStats($startDate, $endDate);

        // สถิติตาม Provider
        $providerStats = $this->getProviderStats($startDate, $endDate);

        // สถิติตาม Campaign
        $campaignStats = $this->getCampaignStats($startDate, $endDate);

        // Timeline Data (สำหรับกราฟ)
        $timelineData = $this->getTimelineData($startDate, $endDate);

        // Top Campaigns
        $topCampaigns = $this->getTopCampaigns($startDate, $endDate);

        // Recent Activity
        $recentActivity = $this->getRecentActivity();

        return view('admin.email.analytics.index', compact(
            'overallStats',
            'providerStats',
            'campaignStats',
            'timelineData',
            'topCampaigns',
            'recentActivity',
            'startDate',
            'endDate'
        ));
    }

    /**
     * ดึงสถิติรวมทั้งหมด
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    private function getOverallStats(string $startDate, string $endDate): array
    {
        // จาก EmailLog
        $emailLogStats = EmailLog::whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as sent'),
                DB::raw('SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed'),
                DB::raw('SUM(CASE WHEN opened_at IS NOT NULL THEN 1 ELSE 0 END) as opened'),
                DB::raw('SUM(CASE WHEN clicked_at IS NOT NULL THEN 1 ELSE 0 END) as clicked'),
                DB::raw('SUM(CASE WHEN bounced_at IS NOT NULL THEN 1 ELSE 0 END) as bounced')
            )
            ->first();

        // คำนวณ rates
        $total = $emailLogStats->total ?? 0;
        $sent = $emailLogStats->sent ?? 0;
        $failed = $emailLogStats->failed ?? 0;
        $opened = $emailLogStats->opened ?? 0;
        $clicked = $emailLogStats->clicked ?? 0;
        $bounced = $emailLogStats->bounced ?? 0;

        $successRate = $total > 0 ? round(($sent / $total) * 100, 2) : 0;
        $openRate = $sent > 0 ? round(($opened / $sent) * 100, 2) : 0;
        $clickRate = $sent > 0 ? round(($clicked / $sent) * 100, 2) : 0;
        $bounceRate = $total > 0 ? round(($bounced / $total) * 100, 2) : 0;

        return [
            'total' => $total,
            'sent' => $sent,
            'failed' => $failed,
            'opened' => $opened,
            'clicked' => $clicked,
            'bounced' => $bounced,
            'success_rate' => $successRate,
            'open_rate' => $openRate,
            'click_rate' => $clickRate,
            'bounce_rate' => $bounceRate,
        ];
    }

    /**
     * ดึงสถิติแยกตาม Provider
     *
     * @param string $startDate
     * @param string $endDate
     * @return \Illuminate\Support\Collection
     */
    private function getProviderStats(string $startDate, string $endDate)
    {
        return EmailLog::whereBetween('created_at', [$startDate, $endDate])
            ->select(
                'provider',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as sent'),
                DB::raw('SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed'),
                DB::raw('AVG(response_time) as avg_response_time')
            )
            ->groupBy('provider')
            ->orderByDesc('total')
            ->get()
            ->map(function ($stat) {
                $stat->success_rate = $stat->total > 0
                    ? round(($stat->sent / $stat->total) * 100, 2)
                    : 0;
                return $stat;
            });
    }

    /**
     * ดึงสถิติแยกตาม Campaign
     *
     * @param string $startDate
     * @param string $endDate
     * @return \Illuminate\Support\Collection
     */
    private function getCampaignStats(string $startDate, string $endDate)
    {
        return EmailCampaign::whereBetween('created_at', [$startDate, $endDate])
            ->select(
                'status',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total_recipients) as total_recipients'),
                DB::raw('SUM(sent_count) as sent_count'),
                DB::raw('SUM(delivered_count) as delivered_count'),
                DB::raw('SUM(opened_count) as opened_count'),
                DB::raw('SUM(clicked_count) as clicked_count')
            )
            ->groupBy('status')
            ->get();
    }

    /**
     * ดึงข้อมูล Timeline สำหรับกราฟ
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    private function getTimelineData(string $startDate, string $endDate): array
    {
        $data = EmailLog::whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as sent'),
                DB::raw('SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $data->pluck('date')->map(fn($d) => Carbon::parse($d)->format('d M'))->toArray(),
            'total' => $data->pluck('total')->toArray(),
            'sent' => $data->pluck('sent')->toArray(),
            'failed' => $data->pluck('failed')->toArray(),
        ];
    }

    /**
     * ดึง Top Campaigns ตาม Open Rate
     *
     * @param string $startDate
     * @param string $endDate
     * @return \Illuminate\Support\Collection
     */
    private function getTopCampaigns(string $startDate, string $endDate)
    {
        return EmailCampaign::whereBetween('created_at', [$startDate, $endDate])
            ->where('delivered_count', '>', 0)
            ->orderByRaw('(opened_count / delivered_count) DESC')
            ->limit(10)
            ->get(['id', 'name', 'total_recipients', 'delivered_count', 'opened_count', 'clicked_count'])
            ->map(function ($campaign) {
                $campaign->open_rate = $campaign->open_rate;
                $campaign->click_rate = $campaign->click_rate;
                return $campaign;
            });
    }

    /**
     * ดึง Recent Activity
     *
     * @return \Illuminate\Support\Collection
     */
    private function getRecentActivity()
    {
        return EmailLog::with('user')
            ->latest()
            ->limit(20)
            ->get();
    }

    /**
     * Export Analytics เป็น CSV
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function export(Request $request)
    {
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        $data = EmailLog::whereBetween('created_at', [$startDate, $endDate])
            ->with(['user', 'template'])
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'email-analytics-' . $startDate . '-to-' . $endDate . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');

            // Header Row
            fputcsv($file, [
                'Date',
                'Email',
                'Subject',
                'Status',
                'Provider',
                'Response Time (ms)',
                'Opened',
                'Clicked',
                'Bounced',
            ]);

            // Data Rows
            foreach ($data as $log) {
                fputcsv($file, [
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->recipient_email,
                    $log->subject,
                    $log->status,
                    $log->provider,
                    $log->response_time,
                    $log->opened_at ? 'Yes' : 'No',
                    $log->clicked_at ? 'Yes' : 'No',
                    $log->bounced_at ? 'Yes' : 'No',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
