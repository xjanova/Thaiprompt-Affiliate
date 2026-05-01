<?php

namespace App\Http\Controllers\Api\Admin\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Admin Mobile API: Marketplace Orders
 */
class MarketplaceOrdersController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = MarketplaceOrder::query()->with('platform');

        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(function ($q) use ($s) {
                $q->where('order_number', 'like', "%{$s}%")
                    ->orWhere('customer_name', 'like', "%{$s}%")
                    ->orWhere('external_order_id', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('order_status', $request->input('status'));
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->input('payment_status'));
        }

        $perPage = (int) $request->input('per_page', 20);
        $perPage = max(1, min(100, $perPage));

        $orders = $query->orderByDesc('ordered_at')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $orders->items() ? array_map(fn ($o) => [
                    'id' => $o['id'],
                    'order_number' => $o['order_number'],
                    'external_order_id' => $o['external_order_id'],
                    'platform' => $o['platform']?->code ?? 'unknown',
                    'customer_name' => $o['customer_name'],
                    'total_amount' => (float) $o['total_amount'],
                    'commission_amount' => (float) $o['commission_amount'],
                    'order_status' => $o['order_status'],
                    'payment_status' => $o['payment_status'],
                    'fulfillment_status' => $o['fulfillment_status'] ?? null,
                    'ordered_at' => $o['ordered_at'] ? \Carbon\Carbon::parse($o['ordered_at'])->toIso8601String() : null,
                ], $orders->items()) : [],
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }
}
