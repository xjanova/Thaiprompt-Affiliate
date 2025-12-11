<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ShippingProvider;
use App\Models\OrderTrackingHistory;
use App\Models\OrderMessage;
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
     * แสดงหน้าจัดการ Tracking
     *
     * @param int $orderId
     * @return \Illuminate\View\View
     */
    public function tracking($orderId)
    {
        $order = Order::with([
            'items' => function ($q) {
                $q->where('seller_id', auth()->id())->with('product');
            },
            'user',
            'shippingProvider',
            'trackingHistory.user',
            'messages.sender',
        ])
            ->whereHas('items', function ($q) {
                $q->where('seller_id', auth()->id());
            })
            ->findOrFail($orderId);

        $shippingProviders = ShippingProvider::active()->ordered()->get();

        return view('seller.orders.tracking', compact('order', 'shippingProviders'));
    }

    /**
     * Add tracking number (V2 - ใช้ ShippingProvider model)
     */
    public function addTracking(Request $request, $orderId)
    {
        $request->validate([
            'shipping_provider_id' => 'required|exists:shipping_providers,id',
            'tracking_number' => 'required|string|max:100',
            'estimated_delivery_at' => 'nullable|date',
        ], [
            'shipping_provider_id.required' => 'กรุณาเลือกบริษัทขนส่ง',
            'tracking_number.required' => 'กรุณากรอกหมายเลขพัสดุ',
        ]);

        $order = Order::whereHas('items', function ($q) {
            $q->where('seller_id', auth()->id());
        })->findOrFail($orderId);

        DB::beginTransaction();

        try {
            // อัพเดทข้อมูล Order
            $order->shipping_provider_id = $request->shipping_provider_id;
            $order->tracking_number = $request->tracking_number;
            $order->estimated_delivery_at = $request->estimated_delivery_at;
            $order->status = 'shipped';
            $order->shipped_at = now();
            $order->save();

            // Update all seller's items in this order
            OrderItem::where('order_id', $orderId)
                ->where('seller_id', auth()->id())
                ->update(['status' => 'shipped']);

            // สร้าง tracking history
            OrderTrackingHistory::createEntry(
                $order,
                'shipped',
                'ส่งสินค้าแล้ว - หมายเลขพัสดุ: ' . $request->tracking_number,
                auth()->id()
            );

            DB::commit();

            return back()->with('success', 'เพิ่มเลขพัสดุเรียบร้อยแล้ว');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * เพิ่ม Tracking History
     *
     * @param Request $request
     * @param int $orderId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function addTrackingHistory(Request $request, $orderId)
    {
        $request->validate([
            'status' => 'required|string|max:50',
            'description' => 'required|string|max:500',
            'location' => 'nullable|string|max:255',
        ], [
            'status.required' => 'กรุณาเลือกสถานะ',
            'description.required' => 'กรุณากรอกรายละเอียด',
        ]);

        $order = Order::whereHas('items', function ($q) {
            $q->where('seller_id', auth()->id());
        })->findOrFail($orderId);

        OrderTrackingHistory::createEntry(
            $order,
            $request->status,
            $request->description,
            auth()->id(),
            $request->location
        );

        // อัพเดทสถานะ Order ตาม tracking status
        if ($request->status === 'delivered') {
            $order->update([
                'status' => 'delivered',
                'delivered_at' => now(),
            ]);

            // Update all seller's items
            OrderItem::where('order_id', $orderId)
                ->where('seller_id', auth()->id())
                ->update(['status' => 'delivered']);
        }

        return back()->with('success', 'เพิ่มประวัติการจัดส่งเรียบร้อยแล้ว');
    }

    /**
     * ดึงข้อความของ Order (AJAX)
     *
     * @param int $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMessages($orderId)
    {
        $order = Order::whereHas('items', function ($q) {
            $q->where('seller_id', auth()->id());
        })->findOrFail($orderId);

        $messages = $order->messages()
            ->with('sender')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $messages,
        ]);
    }

    /**
     * ส่งข้อความในนามของ Seller
     *
     * @param Request $request
     * @param int $orderId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendMessage(Request $request, $orderId)
    {
        $request->validate([
            'message' => 'required|string|max:2000',
        ], [
            'message.required' => 'กรุณากรอกข้อความ',
        ]);

        $order = Order::whereHas('items', function ($q) {
            $q->where('seller_id', auth()->id());
        })->findOrFail($orderId);

        OrderMessage::send(
            $order,
            auth()->id(),
            'seller',
            $request->message
        );

        return back()->with('success', 'ส่งข้อความเรียบร้อยแล้ว');
    }

    /**
     * ทำเครื่องหมายข้อความว่าอ่านแล้ว
     *
     * @param int $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function markMessagesRead($orderId)
    {
        $order = Order::whereHas('items', function ($q) {
            $q->where('seller_id', auth()->id());
        })->findOrFail($orderId);

        OrderMessage::where('order_id', $order->id)
            ->where('sender_type', 'customer')
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
                'read_by' => auth()->id(),
            ]);

        // อัพเดทสถานะ unread ของ order
        $hasUnread = OrderMessage::where('order_id', $order->id)
            ->where('is_read', false)
            ->exists();
        $order->update(['has_unread_messages' => $hasUnread]);

        return response()->json(['success' => true]);
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

    // ========================================
    // SHIPPING MANAGEMENT
    // เมนูจัดการการจัดส่ง
    // ========================================

    /**
     * แสดงคำสั่งซื้อที่รอจัดส่ง (ยังไม่มีเลขพัสดุ)
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function pendingShipping(Request $request)
    {
        $query = Order::with(['items' => function ($q) {
            $q->where('seller_id', auth()->id())->with('product');
        }, 'user', 'shippingAddress'])
            ->whereHas('items', function ($q) {
                $q->where('seller_id', auth()->id());
            })
            ->where('payment_status', 'paid')
            ->whereIn('status', ['pending', 'confirmed', 'processing'])
            ->whereNull('tracking_number')
            ->latest();

        $orders = $query->paginate(20);

        // Statistics
        $stats = [
            'pending_shipping' => $orders->total(),
            'total_amount' => Order::whereHas('items', function ($q) {
                $q->where('seller_id', auth()->id());
            })
                ->where('payment_status', 'paid')
                ->whereIn('status', ['pending', 'confirmed', 'processing'])
                ->whereNull('tracking_number')
                ->sum('total_amount'),
        ];

        $shippingProviders = ShippingProvider::active()->ordered()->get();

        return view('seller.orders.pending-shipping', compact('orders', 'stats', 'shippingProviders'));
    }

    /**
     * แสดงคำสั่งซื้อที่จัดส่งแล้ว (มีเลขพัสดุแล้ว)
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function shipped(Request $request)
    {
        $query = Order::with(['items' => function ($q) {
            $q->where('seller_id', auth()->id())->with('product');
        }, 'user', 'shippingAddress', 'shippingProvider'])
            ->whereHas('items', function ($q) {
                $q->where('seller_id', auth()->id());
            })
            ->where('status', 'shipped')
            ->latest('shipped_at');

        $orders = $query->paginate(20);

        // Statistics
        $stats = [
            'shipped' => $orders->total(),
            'in_transit' => Order::whereHas('items', function ($q) {
                $q->where('seller_id', auth()->id());
            })
                ->where('status', 'shipped')
                ->count(),
        ];

        return view('seller.orders.shipped', compact('orders', 'stats'));
    }

    /**
     * แสดงคำสั่งซื้อที่ส่งถึงแล้ว
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function delivered(Request $request)
    {
        $query = Order::with(['items' => function ($q) {
            $q->where('seller_id', auth()->id())->with('product');
        }, 'user', 'shippingAddress', 'shippingProvider'])
            ->whereHas('items', function ($q) {
                $q->where('seller_id', auth()->id());
            })
            ->whereIn('status', ['delivered', 'completed'])
            ->latest('delivered_at');

        $orders = $query->paginate(20);

        // Statistics
        $stats = [
            'delivered' => $orders->total(),
            'completed_this_month' => Order::whereHas('items', function ($q) {
                $q->where('seller_id', auth()->id());
            })
                ->whereIn('status', ['delivered', 'completed'])
                ->whereMonth('delivered_at', now()->month)
                ->count(),
        ];

        return view('seller.orders.delivered', compact('orders', 'stats'));
    }

    // ========================================
    // CUSTOMER MESSAGES
    // แชทกับลูกค้า
    // ========================================

    /**
     * แสดงข้อความทั้งหมดจากลูกค้า
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function allMessages(Request $request)
    {
        // ดึง Orders ที่มีข้อความ
        $query = Order::with(['items' => function ($q) {
            $q->where('seller_id', auth()->id())->with('product');
        }, 'user', 'messages' => function ($q) {
            $q->latest()->limit(1);
        }])
            ->whereHas('items', function ($q) {
                $q->where('seller_id', auth()->id());
            })
            ->whereHas('messages')
            ->orderBy('last_message_at', 'desc');

        // กรองเฉพาะที่มีข้อความใหม่
        if ($request->get('filter') === 'unread') {
            $query->where('has_unread_messages', true);
        }

        $conversations = $query->paginate(20);

        // Statistics
        $stats = [
            'total_conversations' => Order::whereHas('items', function ($q) {
                $q->where('seller_id', auth()->id());
            })->whereHas('messages')->count(),
            'unread_conversations' => Order::whereHas('items', function ($q) {
                $q->where('seller_id', auth()->id());
            })->where('has_unread_messages', true)->count(),
            'messages_today' => OrderMessage::whereHas('order.items', function ($q) {
                $q->where('seller_id', auth()->id());
            })->whereDate('created_at', today())->count(),
        ];

        return view('seller.messages.index', compact('conversations', 'stats'));
    }

    /**
     * แสดงข้อความที่ยังไม่อ่าน
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function unreadMessages(Request $request)
    {
        // ดึง Orders ที่มีข้อความยังไม่อ่าน
        $query = Order::with(['items' => function ($q) {
            $q->where('seller_id', auth()->id())->with('product');
        }, 'user', 'messages' => function ($q) {
            $q->latest()->limit(1);
        }])
            ->whereHas('items', function ($q) {
                $q->where('seller_id', auth()->id());
            })
            ->where('has_unread_messages', true)
            ->orderBy('last_message_at', 'desc');

        $conversations = $query->paginate(20);

        // Statistics
        $stats = [
            'unread_conversations' => $conversations->total(),
            'unread_messages' => OrderMessage::whereHas('order.items', function ($q) {
                $q->where('seller_id', auth()->id());
            })
                ->where('sender_type', 'customer')
                ->where('is_read', false)
                ->count(),
        ];

        return view('seller.messages.unread', compact('conversations', 'stats'));
    }
}
