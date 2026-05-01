<?php

namespace App\Http\Controllers\Api\Admin\Fortune;

use App\Http\Controllers\Controller;
use App\Models\FortuneCategory;
use App\Models\FortuneReading;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Admin Mobile API: Fortune Dashboard
 *
 * รวมสถิติสำหรับหน้า "ดูดวง & ทาโรต์" บนมือถือ:
 * - Hero (crystal ball)
 * - 4-stat: รายได้/เดือน, sessions, avg rating, active now
 * - Services summary: 6 services with sessions/revenue
 * - Recent readings count
 */
class FortuneDashboardController extends Controller
{
    /**
     * GET /api/admin/fortune/dashboard
     */
    public function index(Request $request): JsonResponse
    {
        $period = $request->input('period', 'month');
        $start = match ($period) {
            'today' => Carbon::now()->startOfDay(),
            'week' => Carbon::now()->startOfWeek(),
            default => Carbon::now()->startOfMonth(),
        };

        return response()->json([
            'success' => true,
            'data' => [
                'hero' => $this->getHeroStats($start),
                'services_summary' => $this->getServicesSummary($start),
                'period' => $period,
                'generated_at' => now()->toIso8601String(),
            ],
        ]);
    }

    private function getHeroStats(Carbon $start): array
    {
        $stats = [
            'monthly_revenue_thb' => 0.0,
            'sessions_count' => 0,
            'avg_rating' => 0.0,
            'active_now' => 0,
        ];

        try {
            $stats['monthly_revenue_thb'] = (float) FortuneReading::where('is_paid', true)
                ->where('created_at', '>=', $start)
                ->sum('amount_paid');

            $stats['sessions_count'] = FortuneReading::where('created_at', '>=', $start)->count();

            $stats['avg_rating'] = round((float) FortuneReading::whereNotNull('rating')
                ->where('created_at', '>=', $start)
                ->avg('rating'), 2);

            // active_now = readings ที่ user_id และยังไม่ตอบ (response_type=pending) ใน 1 ชม.
            $stats['active_now'] = FortuneReading::whereNotNull('user_id')
                ->where('responded_at', null)
                ->where('created_at', '>=', now()->subHour())
                ->count();
        } catch (\Throwable $e) {
            // table missing? ส่ง 0
        }

        return $stats;
    }

    private function getServicesSummary(Carbon $start): array
    {
        try {
            $categories = FortuneCategory::query()
                ->where('is_active', true)
                ->orderBy('order')
                ->get();

            return $categories->map(function ($cat) use ($start) {
                // หา reading ที่ category นี้ — categories เป็น JSON array ในแต่ละ reading
                // ใช้ JSON_CONTAINS (MySQL) หรือ whereJsonContains (Laravel)
                $sessions = 0;
                $revenue = 0.0;

                try {
                    $query = FortuneReading::where('created_at', '>=', $start);
                    // whereJsonContains ใช้ได้ถ้า categories field เป็น array
                    $query = $query->whereJsonContains('categories', $cat->slug);

                    $sessions = (clone $query)->count();
                    $revenue = (float) (clone $query)->where('is_paid', true)->sum('amount_paid');
                } catch (\Throwable $e) {
                    //
                }

                return [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'slug' => $cat->slug,
                    'color' => $cat->color,
                    'icon' => $cat->icon,
                    'sessions' => $sessions,
                    'revenue_thb' => round($revenue, 2),
                    'is_active' => (bool) $cat->is_active,
                ];
            })->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }
}
