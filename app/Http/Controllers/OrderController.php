<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\ProductReview;
use App\Services\TrackingService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display user's orders
     */
    public function index(Request $request)
    {
        $status = $request->get('status');

        $query = Order::with(['items.product'])
            ->where('user_id', auth()->id())
            ->latest();

        if ($status) {
            $query->where('status', $status);
        }

        $orders = $query->paginate(15);

        $statusCounts = [
            'all' => Order::where('user_id', auth()->id())->count(),
            'pending' => Order::where('user_id', auth()->id())->pending()->count(),
            'processing' => Order::where('user_id', auth()->id())->processing()->count(),
            'shipped' => Order::where('user_id', auth()->id())->shipped()->count(),
            'completed' => Order::where('user_id', auth()->id())->completed()->count(),
        ];

        return view('shop.orders.index', compact('orders', 'statusCounts'));
    }

    /**
     * Show order details
     */
    public function show($id)
    {
        $order = Order::with([
            'items.product',
            'items.reviews',
            'shippingAddress',
            'shippingProviderRelation',
            'trackingHistory',
        ])
            ->where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        return view('shop.orders.show', compact('order'));
    }

    /**
     * Cancel order
     */
    public function cancel(Request $request, $id)
    {
        $order = Order::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        if (! $order->canBeCancelled()) {
            return back()->with('error', 'ไม่สามารถยกเลิกคำสั่งซื้อนี้ได้');
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $order->cancel($request->reason);

        return back()->with('success', 'ยกเลิกคำสั่งซื้อเรียบร้อยแล้ว');
    }

    /**
     * Confirm order received (mark as completed)
     */
    public function confirmReceived($id)
    {
        $order = Order::where('id', $id)
            ->where('user_id', auth()->id())
            ->where('status', 'delivered')
            ->firstOrFail();

        $order->markAsCompleted();

        return back()->with('success', 'ยืนยันการรับสินค้าเรียบร้อยแล้ว');
    }

    /**
     * ชำระเงินใหม่สำหรับคำสั่งซื้อที่ยังไม่ได้ชำระ
     * redirect ไปหน้า payment ซึ่งจะสร้าง transaction ใหม่อัตโนมัติ
     */
    public function retryPayment($id)
    {
        $order = Order::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        if (! $order->canRetryPayment()) {
            return back()->with('error', 'ไม่สามารถชำระเงินสำหรับคำสั่งซื้อนี้ได้');
        }

        return redirect()->route('checkout.payment', $order->id);
    }

    /**
     * Show review form for order item
     */
    public function showReviewForm($orderId, $itemId)
    {
        // อนุญาตให้รีวิวได้เมื่อรับสินค้าแล้ว (delivered) หรือยืนยันรับสินค้าแล้ว (completed)
        $order = Order::where('id', $orderId)
            ->where('user_id', auth()->id())
            ->whereIn('status', ['delivered', 'completed'])
            ->firstOrFail();

        $item = $order->items()->findOrFail($itemId);

        // Check if already reviewed
        if ($item->hasReview()) {
            return redirect()->route('orders.show', $orderId)
                ->with('error', 'คุณได้รีวิวสินค้านี้แล้ว');
        }

        return view('shop.orders.review', compact('order', 'item'));
    }

    /**
     * Submit product review
     */
    public function submitReview(Request $request, $orderId, $itemId)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:200',
            'comment' => 'required|string|max:1000',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|max:2048',
        ]);

        // อนุญาตให้รีวิวได้เมื่อรับสินค้าแล้ว (delivered) หรือยืนยันรับสินค้าแล้ว (completed)
        $order = Order::where('id', $orderId)
            ->where('user_id', auth()->id())
            ->whereIn('status', ['delivered', 'completed'])
            ->firstOrFail();

        $item = $order->items()->findOrFail($itemId);

        // Check if already reviewed
        if ($item->hasReview()) {
            return back()->with('error', 'คุณได้รีวิวสินค้านี้แล้ว');
        }

        // Handle image uploads
        $imageUrls = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('reviews', 'public');
                $imageUrls[] = $path;
            }
        }

        // Create review
        ProductReview::create([
            'product_id' => $item->product_id,
            'user_id' => auth()->id(),
            'order_item_id' => $item->id,
            'rating' => $request->rating,
            'title' => $request->title,
            'comment' => $request->comment,
            'images' => $imageUrls,
            'is_verified_purchase' => true,
            'is_approved' => true,
        ]);

        // Update product rating
        $item->product->updateRating();

        return redirect()->route('orders.show', $orderId)
            ->with('success', 'ขอบคุณสำหรับรีวิวของคุณ');
    }

    /**
     * ดึงข้อมูลติดตามพัสดุแบบ realtime (AJAX)
     *
     * @param  int  $id  Order ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function trackingRealtime($id)
    {
        $order = Order::with(['shippingProviderRelation', 'trackingHistory'])
            ->where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        if (! $order->tracking_number) {
            return response()->json([
                'success' => false,
                'message' => 'คำสั่งซื้อนี้ยังไม่มีเลขพัสดุ',
            ]);
        }

        $trackingService = new TrackingService;
        $forceRefresh = request()->boolean('refresh', false);
        $result = $trackingService->trackOrder($order, $forceRefresh);

        return response()->json($result);
    }
}
