<?php

namespace App\Http\Controllers\Seller;

use App\Exceptions\ShopException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderMessage;
use App\Models\OrderTrackingHistory;
use App\Models\ShippingProvider;
use App\Services\Shop\SellerOrderService;
use App\Services\Shop\ShopPresenter;
use App\Support\Shop\PaymentMethod;
use Illuminate\Http\Request;

/**
 * จัดการคำสั่งซื้อฝั่งร้าน (เว็บ /seller/orders)
 *
 * 🛒 (2026-09-25) SELLER-03/13/14: การยืนยัน/ส่งของ/ส่งถึง/ยกเลิก/เรียกไรเดอร์ ใช้ SellerOrderService
 *    ตัวเดียวกับแอป — ส่งของได้เฉพาะออเดอร์ที่จ่ายแล้วหรือ COD · ออเดอร์หลายร้านไม่เขียนทับกัน
 */
class OrderManagementController extends Controller
{
    public function __construct(private readonly SellerOrderService $sellerOrders) {}

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
            'shippingAddress',
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

        // ปุ่มที่ร้านกดได้ + บริษัทขนส่ง (ฟอร์มเลขพัสดุใช้ shipping_provider_id) + สถานะไรเดอร์
        $fullOrder = Order::with('items')->find($order->id);
        $allowedActions = $fullOrder ? $this->sellerOrders->allowedActions($fullOrder, (int) auth()->id()) : [];
        $shippingProviders = ShippingProvider::active()->ordered()->get();
        $riderSummary = ShopPresenter::riderSummary($order);
        $paymentMethodLabel = PaymentMethod::labelTh($order->payment_method);

        return view('seller.orders.show', compact(
            'order',
            'sellerItems',
            'sellerTotal',
            'sellerCommission',
            'sellerEarning',
            'allowedActions',
            'shippingProviders',
            'riderSummary',
            'paymentMethodLabel'
        ));
    }

    /**
     * ทำตามปุ่มของร้าน: confirm | request_rider | ship | deliver | cancel
     * (POST /seller/orders/{orderId}/action — ตรรกะเดียวกับ API แอป)
     */
    public function action(Request $request, $orderId)
    {
        $request->validate([
            'action' => 'required|in:'.implode(',', SellerOrderService::ACTIONS),
            'tracking_number' => 'required_if:action,ship|nullable|string|max:100',
            'shipping_provider_id' => 'nullable|integer|exists:shipping_providers,id',
            'estimated_delivery_at' => 'nullable|date|after_or_equal:today',
            'reason' => 'required_if:action,cancel|nullable|string|max:500',
        ], [
            'action.in' => 'คำสั่งไม่ถูกต้อง',
            'tracking_number.required_if' => 'กรุณากรอกหมายเลขพัสดุ',
            'shipping_provider_id.exists' => 'ไม่พบบริษัทขนส่งที่เลือก',
            'reason.required_if' => 'กรุณาระบุเหตุผลในการยกเลิก',
        ]);

        $order = Order::forSeller((int) auth()->id())->with('items')->findOrFail($orderId);

        try {
            $this->sellerOrders->perform($order, $request->user(), (string) $request->input('action'), $request->only([
                'tracking_number', 'shipping_provider_id', 'estimated_delivery_at', 'reason',
            ]));
        } catch (ShopException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            \Log::error('Seller web order action failed', [
                'order_id' => $order->id,
                'action' => $request->input('action'),
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'ทำรายการไม่สำเร็จ กรุณาลองใหม่');
        }

        return back()->with('success', match ((string) $request->input('action')) {
            'confirm' => 'ยืนยันคำสั่งซื้อแล้ว',
            'request_rider' => 'เรียกไรเดอร์แล้ว ระบบกำลังหาไรเดอร์ใกล้ร้าน',
            'ship' => 'บันทึกเลขพัสดุและเปลี่ยนสถานะเป็นจัดส่งแล้ว',
            'deliver' => 'ยืนยันส่งถึงแล้ว',
            'cancel' => 'ยกเลิกคำสั่งซื้อแล้ว',
            default => 'ดำเนินการแล้ว',
        });
    }

    /**
     * Update order item status (ฟอร์มเดิมในหน้ารายละเอียด)
     *
     * 🛒 (2026-09-25) SELLER-14: เดิมเปลี่ยนสถานะได้แม้ยังไม่จ่าย และเปลี่ยนเป็น completed เองได้
     *    ตอนนี้: processing = ยืนยัน · delivered = ส่งถึง (ต้องจัดส่งก่อน) · shipped ต้องกรอกเลขพัสดุ
     */
    public function updateItemStatus(Request $request, $orderId, $itemId)
    {
        $request->validate([
            'status' => 'required|in:processing,shipped,delivered',
        ], [
            'status.in' => 'สถานะไม่ถูกต้อง',
        ]);

        OrderItem::where('id', $itemId)
            ->where('seller_id', auth()->id())
            ->where('order_id', $orderId)
            ->firstOrFail();

        if ($request->status === 'shipped') {
            return redirect()->route('seller.orders.tracking', $orderId)
                ->with('error', 'กรุณากรอกเลขพัสดุเพื่อเปลี่ยนสถานะเป็นจัดส่งแล้ว');
        }

        $order = Order::forSeller((int) auth()->id())->with('items')->findOrFail($orderId);
        $action = $request->status === 'processing' ? 'confirm' : 'deliver';

        try {
            $this->sellerOrders->perform($order, $request->user(), $action);
        } catch (ShopException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'อัพเดตสถานะเรียบร้อยแล้ว');
    }

    /**
     * แสดงหน้าจัดการ Tracking
     *
     * @param  int  $orderId
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
            'trackingHistory.creator',
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
     *
     * 🐛 (2026-09-25) SELLER-03/14: เดิมเรียก createEntry ผิด signature (TypeError 500), ไม่เช็คว่าจ่ายแล้ว
     *    และเขียนทับเลขพัสดุ/สถานะของทั้งออเดอร์แม้มีสินค้าร้านอื่น → ใช้ action ship ของ SellerOrderService
     */
    public function addTracking(Request $request, $orderId)
    {
        $request->validate([
            'shipping_provider_id' => 'required|exists:shipping_providers,id',
            'tracking_number' => 'required|string|max:100',
            'estimated_delivery_at' => 'nullable|date|after_or_equal:today',
        ], [
            'shipping_provider_id.required' => 'กรุณาเลือกบริษัทขนส่ง',
            'shipping_provider_id.exists' => 'ไม่พบบริษัทขนส่งที่เลือก',
            'tracking_number.required' => 'กรุณากรอกหมายเลขพัสดุ',
        ]);

        $order = Order::forSeller((int) auth()->id())->with('items')->findOrFail($orderId);

        try {
            $this->sellerOrders->perform($order, $request->user(), 'ship', $request->only([
                'shipping_provider_id', 'tracking_number', 'estimated_delivery_at',
            ]));
        } catch (ShopException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            \Log::error('Seller add tracking failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);

            return back()->with('error', 'บันทึกเลขพัสดุไม่สำเร็จ กรุณาลองใหม่');
        }

        return back()->with('success', 'เพิ่มเลขพัสดุเรียบร้อยแล้ว');
    }

    /**
     * เพิ่ม Tracking History
     *
     * in_transit / out_for_delivery = บันทึกความคืบหน้า · delivered = ยืนยันส่งถึง (ผ่าน SellerOrderService)
     *
     * @param  int  $orderId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function addTrackingHistory(Request $request, $orderId)
    {
        $request->validate([
            'status' => 'required|in:in_transit,out_for_delivery,delivered',
            'description' => 'required|string|max:500',
            'location' => 'nullable|string|max:255',
        ], [
            'status.required' => 'กรุณาเลือกสถานะ',
            'status.in' => 'สถานะไม่ถูกต้อง',
            'description.required' => 'กรุณากรอกรายละเอียด',
        ]);

        $order = Order::forSeller((int) auth()->id())->with('items')->findOrFail($orderId);

        if ($request->status === 'delivered') {
            try {
                $this->sellerOrders->perform($order, $request->user(), 'deliver');
            } catch (ShopException $e) {
                return back()->with('error', $e->getMessage());
            }

            return back()->with('success', 'ยืนยันส่งถึงเรียบร้อยแล้ว');
        }

        $labels = ['in_transit' => 'อยู่ระหว่างขนส่ง', 'out_for_delivery' => 'กำลังนำส่ง'];

        OrderTrackingHistory::createEntry($order, $request->status, $labels[$request->status], [
            'description' => $request->description,
            'location' => $request->location,
            'created_by' => auth()->id(),
            'created_by_type' => 'seller',
            'meta_data' => ['seller_id' => (int) auth()->id()],
        ]);

        return back()->with('success', 'เพิ่มประวัติการจัดส่งเรียบร้อยแล้ว');
    }

    /**
     * ดึงข้อความของ Order (AJAX)
     *
     * @param  int  $orderId
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
     * @param  int  $orderId
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
     * @param  int  $orderId
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
            'shippingAddress',
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
            // 🛒 (2026-09-25) SELLER-13: รวมออเดอร์เก็บเงินปลายทาง (ยังไม่จ่ายแต่ต้องส่ง) ด้วย
            ->where(fn ($q) => $q->where('payment_status', 'paid')
                ->orWhere(fn ($cod) => $cod->where('payment_method', PaymentMethod::COD)->where('payment_status', 'pending')))
            ->whereIn('status', ['pending', 'paid', 'processing'])
            ->whereNull('tracking_number')
            ->latest();

        $orders = $query->paginate(20);

        // Statistics
        $stats = [
            'pending_shipping' => $orders->total(),
            'total_amount' => Order::whereHas('items', function ($q) {
                $q->where('seller_id', auth()->id());
            })
                ->where(fn ($q) => $q->where('payment_status', 'paid')
                    ->orWhere(fn ($cod) => $cod->where('payment_method', PaymentMethod::COD)->where('payment_status', 'pending')))
                ->whereIn('status', ['pending', 'paid', 'processing'])
                ->whereNull('tracking_number')
                ->sum('total_amount'),
        ];

        $shippingProviders = ShippingProvider::active()->ordered()->get();

        return view('seller.orders.pending-shipping', compact('orders', 'stats', 'shippingProviders'));
    }

    /**
     * แสดงคำสั่งซื้อที่จัดส่งแล้ว (มีเลขพัสดุแล้ว)
     *
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
