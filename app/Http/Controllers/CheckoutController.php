<?php

namespace App\Http\Controllers;

use App\Models\ShoppingCart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ShippingAddress;
use App\Models\Wallet;
use App\Services\CashbackService;
use App\Services\WalletService;
use App\Services\Payment\PaymentService;
use App\Notifications\NewOrderNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class CheckoutController extends Controller
{
    protected CashbackService $cashbackService;
    protected PaymentService $paymentService;

    public function __construct()
    {
        $this->middleware('auth');
        $this->cashbackService = new CashbackService(new WalletService());
        $this->paymentService = new PaymentService();
    }

    /**
     * Show checkout page
     */
    public function index()
    {
        $cartItems = ShoppingCart::with(['product.category', 'product.seller'])
            ->where('user_id', auth()->id())
            ->get();

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'ตะกร้าสินค้าของคุณว่างเปล่า');
        }

        // Check stock availability
        foreach ($cartItems as $item) {
            if (!$item->isAvailable() || !$item->hasEnoughStock()) {
                return redirect()->route('cart.index')->with('error', 'มีสินค้าบางรายการหมดสต็อกหรือไม่พร้อมจำหน่าย');
            }
        }

        // ✅ ตรวจสอบว่ามี virtual products หรือไม่
        $hasVirtualProducts = $cartItems->contains(function ($item) {
            return $item->product->is_virtual;
        });
        $hasPhysicalProducts = $cartItems->contains(function ($item) {
            return !$item->product->is_virtual;
        });

        // Get user's shipping addresses (เฉพาะเมื่อมีสินค้าที่ต้องส่ง)
        $addresses = $hasPhysicalProducts
            ? ShippingAddress::where('user_id', auth()->id())->orderBy('is_default', 'desc')->get()
            : collect();

        // Calculate totals
        $subtotal = $cartItems->sum('subtotal');
        // ✅ Virtual products ไม่มีค่าส่ง
        $shippingFee = $hasPhysicalProducts ? $this->calculateShippingFee($subtotal) : 0;
        $total = $subtotal + $shippingFee;

        // Calculate cashback preview
        $cashbackPreview = $this->cashbackService->getCashbackPreview($cartItems, $total);

        // Get available payment methods
        $paymentMethods = $this->paymentService->getAvailablePaymentMethods();

        // Get user wallet balance for wallet payment
        $wallet = Wallet::where('user_id', auth()->id())->first();
        $walletBalance = $wallet ? $wallet->balance : 0;

        return view('shop.checkout', compact(
            'cartItems',
            'addresses',
            'subtotal',
            'shippingFee',
            'total',
            'cashbackPreview',
            'paymentMethods',
            'walletBalance'
        ));
    }

    /**
     * Process checkout and create order
     */
    public function process(Request $request)
    {
        $cartItems = ShoppingCart::with(['product.seller'])
            ->where('user_id', auth()->id())
            ->get();

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'ตะกร้าสินค้าของคุณว่างเปล่า');
        }

        // ✅ ตรวจสอบว่ามีสินค้าที่ต้องส่งหรือไม่
        $hasPhysicalProducts = $cartItems->contains(function ($item) {
            return !$item->product->is_virtual;
        });

        // ✅ Validation: shipping_address_id required เฉพาะเมื่อมีสินค้าที่ต้องส่ง
        $validationRules = [
            'payment_method' => 'required|in:wallet,promptpay,bank_transfer,credit_card,cash_on_delivery,paysolutions',
            'customer_notes' => 'nullable|string|max:1000',
        ];

        if ($hasPhysicalProducts) {
            $validationRules['shipping_address_id'] = 'required|exists:shipping_addresses,id';
        }

        $request->validate($validationRules);

        // Check stock availability again
        foreach ($cartItems as $item) {
            if (!$item->isAvailable() || !$item->hasEnoughStock()) {
                return redirect()->route('cart.index')->with('error', 'มีสินค้าบางรายการหมดสต็อกหรือไม่พร้อมจำหน่าย');
            }
        }

        DB::beginTransaction();

        try {
            // ✅ Get shipping address (เฉพาะสินค้าที่ต้องส่ง)
            $shippingAddress = $hasPhysicalProducts && $request->shipping_address_id
                ? ShippingAddress::findOrFail($request->shipping_address_id)
                : null;

            // Calculate totals
            $subtotal = $cartItems->sum('subtotal');
            // ✅ Virtual products ไม่มีค่าส่ง
            $shippingFee = $hasPhysicalProducts ? $this->calculateShippingFee($subtotal) : 0;
            $total = $subtotal + $shippingFee;
            $platformCommission = 0;
            $sellerEarning = 0;

            // Calculate cashback
            $cashbackAmount = $this->cashbackService->calculateOrderCashback($cartItems, $total);

            // Create order (don't decrease stock yet - will be done after payment)
            $order = Order::create([
                'user_id' => auth()->id(),
                // ✅ Virtual products ไม่ต้องมี shipping address
                'shipping_address_id' => $shippingAddress ? $request->shipping_address_id : null,
                'status' => 'pending',
                'payment_method' => $request->payment_method,
                'payment_status' => 'pending',
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
                'total_amount' => $total,
                'cashback_amount' => $cashbackAmount,
                'cashback_processed' => false,
                'customer_notes' => $request->customer_notes,
                // ✅ Virtual products ไม่ต้องมี shipping address snapshot
                'shipping_address_snapshot' => $shippingAddress ? $shippingAddress->toArray() : null,
            ]);

            // Create order items
            foreach ($cartItems as $cartItem) {
                $product = $cartItem->product;
                $itemSubtotal = $product->price * $cartItem->quantity;
                $commissionAmount = $product->calculateCommission($itemSubtotal);
                $itemSellerEarning = $product->calculateSellerEarning($itemSubtotal);

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'seller_id' => $product->seller_id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'product_image' => $product->main_image_url,
                    'product_attributes' => $cartItem->selected_attributes,
                    'unit_price' => $product->price,
                    'quantity' => $cartItem->quantity,
                    'subtotal' => $itemSubtotal,
                    'total' => $itemSubtotal,
                    'commission_rate' => $product->commission_rate,
                    'commission_amount' => $commissionAmount,
                    'seller_earning' => $itemSellerEarning,
                    'status' => 'pending',
                ]);

                $platformCommission += $commissionAmount;
                $sellerEarning += $itemSellerEarning;
            }

            // Update order commission
            $order->update([
                'platform_commission' => $platformCommission,
                'seller_earning' => $sellerEarning,
            ]);

            // Create payment transaction
            $paymentTransaction = $this->paymentService->createOrderPayment(
                $order,
                $request->payment_method
            );

            // Clear cart
            ShoppingCart::where('user_id', auth()->id())->delete();

            // Send notification to customer
            try {
                $order->user->notify(new NewOrderNotification($order));
            } catch (\Exception $e) {
                \Log::error('Failed to send order notification: ' . $e->getMessage());
            }

            // Send notification to sellers
            foreach ($order->items->groupBy('seller_id') as $sellerId => $items) {
                try {
                    $seller = \App\Models\User::find($sellerId);
                    if ($seller) {
                        $seller->notify(new NewOrderNotification($order));
                    }
                } catch (\Exception $e) {
                    \Log::error('Failed to send seller notification: ' . $e->getMessage());
                }
            }

            DB::commit();

            // Redirect to payment page
            return redirect()->route('checkout.payment', $order->id);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Show payment page
     */
    public function payment($orderId)
    {
        $order = Order::with(['items.product', 'shippingAddress', 'paymentTransaction'])
            ->where('id', $orderId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        // Check if order is already paid
        if ($order->payment_status === 'paid') {
            return redirect()->route('checkout.success', $order->id);
        }

        // Get payment transaction
        $transaction = $order->paymentTransaction;

        if (!$transaction) {
            return redirect()->route('orders.show', $order->id)
                ->with('error', 'ไม่พบรายการชำระเงิน');
        }

        // Check if transaction expired
        if ($transaction->isExpired()) {
            return redirect()->route('orders.show', $order->id)
                ->with('error', 'รายการชำระเงินหมดอายุ กรุณาติดต่อเจ้าหน้าที่');
        }

        // Get wallet balance if payment method is wallet
        $walletBalance = 0;
        if ($order->payment_method === 'wallet') {
            $wallet = Wallet::where('user_id', auth()->id())->first();
            $walletBalance = $wallet ? $wallet->balance : 0;
        }

        return view('shop.payment', compact('order', 'transaction', 'walletBalance'));
    }

    /**
     * Process payment
     */
    public function processPayment(Request $request, $orderId)
    {
        $order = Order::with('paymentTransaction')
            ->where('id', $orderId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        // Check if order is already paid
        if ($order->payment_status === 'paid') {
            return redirect()->route('checkout.success', $order->id);
        }

        $transaction = $order->paymentTransaction;

        if (!$transaction) {
            return back()->with('error', 'ไม่พบรายการชำระเงิน');
        }

        // Process payment based on method
        try {
            $result = $this->paymentService->processPayment($transaction, $request->all());

            if ($result['success']) {
                // If payment completed immediately (e.g., wallet)
                if ($result['transaction']->status === 'completed') {
                    // Process cashback
                    if ($order->cashback_amount > 0) {
                        try {
                            $this->cashbackService->processOrderCashback($order);
                        } catch (\Exception $e) {
                            \Log::error('Failed to process cashback: ' . $e->getMessage());
                        }
                    }

                    // ✅ ถ้าเป็น Wallet Topup Package - เพิ่มยอดเข้า Wallet
                    $this->processWalletTopupIfApplicable($order);

                    return redirect()->route('checkout.success', $order->id)
                        ->with('success', 'ชำระเงินสำเร็จ');
                }

                // For async payments (PromptPay, Bank Transfer, etc.)
                return view('shop.payment-processing', [
                    'order' => $order,
                    'transaction' => $result['transaction'],
                    'paymentData' => $result['data'],
                ]);
            }

            return back()->with('error', $result['message'] ?? 'การชำระเงินล้มเหลว');

        } catch (\Exception $e) {
            \Log::error('Payment processing failed: ' . $e->getMessage());
            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Payment callback (for gateway webhooks)
     */
    public function paymentCallback(Request $request, $transactionId)
    {
        try {
            $verified = $this->paymentService->verifyPayment($transactionId, $request->all());

            if ($verified) {
                return response()->json(['success' => true]);
            }

            return response()->json(['success' => false, 'message' => 'Verification failed'], 400);

        } catch (\Exception $e) {
            \Log::error('Payment callback failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Show order success page
     */
    public function success($orderId)
    {
        $order = Order::with(['items.product', 'shippingAddress'])
            ->where('id', $orderId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        return view('shop.order-success', compact('order'));
    }

    /**
     * Calculate shipping fee
     */
    private function calculateShippingFee($subtotal)
    {
        // Free shipping for orders over 1000 baht
        if ($subtotal >= 1000) {
            return 0;
        }

        // Flat rate shipping
        return 50;
    }

    /**
     * ประมวลผล Wallet Topup Package (เพิ่มยอดเข้า Wallet)
     *
     * @param Order $order
     * @return void
     */
    private function processWalletTopupIfApplicable(Order $order): void
    {
        try {
            // ดึง order items และตรวจสอบว่ามี virtual products (wallet topup) หรือไม่
            $walletTopupItems = $order->items()->with('product')->get()->filter(function ($item) {
                return $item->product && $item->product->is_virtual && $item->product->category
                    && $item->product->category->slug === 'wallet-topup';
            });

            if ($walletTopupItems->isEmpty()) {
                return; // ไม่มี topup packages
            }

            $user = $order->user;
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0]
            );

            // เพิ่มยอดเงินเข้า wallet สำหรับแต่ละ topup package
            foreach ($walletTopupItems as $item) {
                $topupAmount = $item->product->price * $item->quantity;
                $balanceBefore = $wallet->balance;

                // เพิ่มยอดเข้า wallet
                $wallet->balance += $topupAmount;
                $wallet->save();

                // สร้างคำอธิบายที่ละเอียด
                $description = sprintf(
                    "💰 เติมเงิน Wallet จำนวน ฿%s\nผ่านแพ็คเกจ: %s\nOrder: #%s\nวันที่: %s",
                    number_format($topupAmount, 2),
                    $item->product->name,
                    $order->id,
                    now()->format('d/m/Y H:i:s')
                );

                // บันทึก transaction พร้อมรายละเอียดครบถ้วน
                $wallet->transactions()->create([
                    'type' => 'topup',
                    'amount' => $topupAmount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $wallet->balance,
                    'status' => 'completed',
                    'description' => $description,
                    'reference_type' => 'order',
                    'reference_id' => $order->id,
                    'metadata' => json_encode([
                        'transaction_type' => 'wallet_topup',
                        'purpose' => 'เติมเงินเข้า Wallet',
                        'order_id' => $order->id,
                        'order_number' => $order->id,
                        'product_id' => $item->product->id,
                        'product_name' => $item->product->name,
                        'product_sku' => $item->product->sku,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->product->price,
                        'total_amount' => $topupAmount,
                        'payment_method' => $order->payment_method,
                        'payment_status' => $order->payment_status,
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'user_email' => $user->email,
                        'timestamp' => now()->toIso8601String(),
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                    ]),
                ]);

                \Log::info("[Wallet Topup] เพิ่มยอดเงิน ฿{$topupAmount} เข้า wallet ของ {$user->name} (User #{$user->id}, Order #{$order->id})");
            }

        } catch (\Exception $e) {
            \Log::error('[Wallet Topup] เกิดข้อผิดพลาด: ' . $e->getMessage());
            // ไม่ throw exception เพื่อไม่ให้กระทบกับการทำงานของ order
        }
    }
}
