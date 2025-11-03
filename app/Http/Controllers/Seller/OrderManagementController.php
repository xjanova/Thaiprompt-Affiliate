<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderManagementController extends Controller
{
    /**
     * Display seller's orders
     */
    public function index(Request $request)
    {
        $status = $request->get('status');

        // Get orders that contain seller's products
        $query = Order::with(['items' => function ($q) {
            $q->where('seller_id', auth()->id());
        }, 'user', 'shippingAddress'])
            ->whereHas('items', function ($q) {
                $q->where('seller_id', auth()->id());
            })
            ->latest();

        if ($status) {
            $query->where('status', $status);
        }

        $orders = $query->paginate(20);

        // Statistics
        $stats = [
            'total' => Order::whereHas('items', function ($q) {
                $q->where('seller_id', auth()->id());
            })->count(),
            'pending' => Order::pending()->whereHas('items', function ($q) {
                $q->where('seller_id', auth()->id());
            })->count(),
            'processing' => Order::processing()->whereHas('items', function ($q) {
                $q->where('seller_id', auth()->id());
            })->count(),
            'shipped' => Order::shipped()->whereHas('items', function ($q) {
                $q->where('seller_id', auth()->id());
            })->count(),
        ];

        return view('seller.orders.index', compact('orders', 'stats'));
    }

    /**
     * Show order details
     */
    public function show($id)
    {
        $order = Order::with([
            'items' => function ($q) {
                $q->where('seller_id', auth()->id())->with('product');
            },
            'user',
            'shippingAddress'
        ])
            ->whereHas('items', function ($q) {
                $q->where('seller_id', auth()->id());
            })
            ->findOrFail($id);

        // Calculate seller's totals for this order
        $sellerItems = $order->items->where('seller_id', auth()->id());
        $sellerTotal = $sellerItems->sum('total');
        $sellerCommission = $sellerItems->sum('commission_amount');
        $sellerEarning = $sellerItems->sum('seller_earning');

        return view('seller.orders.show', compact(
            'order',
            'sellerItems',
            'sellerTotal',
            'sellerCommission',
            'sellerEarning'
        ));
    }

    /**
     * Update order item status
     */
    public function updateItemStatus(Request $request, $orderId, $itemId)
    {
        $request->validate([
            'status' => 'required|in:processing,shipped,delivered,completed',
        ]);

        $orderItem = OrderItem::where('id', $itemId)
            ->where('seller_id', auth()->id())
            ->whereHas('order', function ($q) use ($orderId) {
                $q->where('id', $orderId);
            })
            ->firstOrFail();

        $orderItem->status = $request->status;
        $orderItem->save();

        // Update order status if all items are in same status
        $order = $orderItem->order;
        $allItems = $order->items;

        if ($allItems->every(fn($item) => $item->status === $request->status)) {
            $order->status = $request->status;

            if ($request->status === 'shipped') {
                $order->shipped_at = now();
            } elseif ($request->status === 'delivered') {
                $order->delivered_at = now();
            }

            $order->save();
        }

        return back()->with('success', 'อัพเดตสถานะเรียบร้อยแล้ว');
    }

    /**
     * Add tracking number
     */
    public function addTracking(Request $request, $orderId)
    {
        $request->validate([
            'tracking_number' => 'required|string|max:100',
            'shipping_provider' => 'required|string|max:100',
        ]);

        $order = Order::whereHas('items', function ($q) {
            $q->where('seller_id', auth()->id());
        })->findOrFail($orderId);

        $order->tracking_number = $request->tracking_number;
        $order->shipping_provider = $request->shipping_provider;
        $order->status = 'shipped';
        $order->shipped_at = now();
        $order->save();

        // Update all seller's items in this order
        OrderItem::where('order_id', $orderId)
            ->where('seller_id', auth()->id())
            ->update(['status' => 'shipped']);

        return back()->with('success', 'เพิ่มเลขพัสดุเรียบร้อยแล้ว');
    }

    /**
     * Print order (for packing slip)
     */
    public function print($id)
    {
        $order = Order::with([
            'items' => function ($q) {
                $q->where('seller_id', auth()->id())->with('product');
            },
            'user',
            'shippingAddress'
        ])
            ->whereHas('items', function ($q) {
                $q->where('seller_id', auth()->id());
            })
            ->findOrFail($id);

        return view('seller.orders.print', compact('order'));
    }
}
