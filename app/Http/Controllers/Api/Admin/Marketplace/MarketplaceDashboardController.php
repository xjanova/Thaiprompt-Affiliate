<?php

namespace App\Http\Controllers\Api\Admin\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceAccount;
use App\Models\MarketplaceCommission;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceProduct;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Admin Mobile API: Marketplace Dashboard
 */
class MarketplaceDashboardController extends Controller
{
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
                'platforms' => $this->getPlatformsSummary(),
                'period' => $period,
                'generated_at' => now()->toIso8601String(),
            ],
        ]);
    }

    private function getHeroStats(Carbon $start): array
    {
        $stats = [
            'total_revenue_thb' => 0.0,
            'orders_count' => 0,
            'products_count' => 0,
            'pending_commissions_thb' => 0.0,
        ];

        try {
            $stats['total_revenue_thb'] = (float) MarketplaceOrder::where('ordered_at', '>=', $start)
                ->where('payment_status', 'paid')
                ->sum('total_amount');
            $stats['orders_count'] = MarketplaceOrder::where('ordered_at', '>=', $start)->count();
            $stats['products_count'] = MarketplaceProduct::where('is_available', true)->count();
            $stats['pending_commissions_thb'] = (float) MarketplaceCommission::where('status', 'pending')
                ->sum('commission_amount');
        } catch (\Throwable $e) {
            // table missing
        }

        return $stats;
    }

    private function getPlatformsSummary(): array
    {
        try {
            return MarketplaceAccount::with('platform')
                ->where('is_active', true)
                ->get()
                ->map(fn ($a) => [
                    'id' => $a->id,
                    'name' => $a->name ?? $a->platform?->name ?? 'Unknown',
                    'platform' => $a->platform?->code,
                    'is_active' => (bool) $a->is_active,
                    'last_sync_at' => $a->last_synced_at?->toIso8601String(),
                ])
                ->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }
}
