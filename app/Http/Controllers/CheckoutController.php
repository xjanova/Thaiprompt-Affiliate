<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ShippingAddress;
use App\Models\ShoppingCart;
use App\Models\UniquePaymentAmount;
use App\Models\Wallet;
use App\Notifications\NewOrderNotification;
use App\Services\CashbackService;
use App\Services\Payment\OrderStripeService;
use App\Services\Payment\PaymentService;
use App\Services\Payment\PromptPayProvider;
use App\Services\ShippingService;
use App\Services\WalletService;
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
        $this->cashbackService = new CashbackService(new WalletService);
        $this->paymentService = new PaymentService;
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
            if (! $item->isAvailable() || ! $item->hasEnoughStock()) {
                return redirect()->route('cart.index')->with('error', 'มีสินค้าบางรายการหมดสต็อกหรือไม่พร้อมจำหน่าย');
            }
        }

        // ✅ ตรวจสอบว่ามี virtual products หรือไม่
        $hasVirtualProducts = $cartItems->contains(function ($item) {
            return $item->product->is_virtual;
        });
        $hasPhysicalProducts = $cartItems->contains(function ($item) {
            return ! $item->product->is_virtual;
        });

        // Get user's shipping addresses (เฉพาะเมื่อมีสินค้าที่ต้องส่ง)
        $addresses = $hasPhysicalProducts
            ? ShippingAddress::where('user_id', auth()->id())->orderBy('is_default', 'desc')->get()
            : collect();

        // Calculate totals
        $subtotal = $cartItems->sum('subtotal');
        // ✅ Virtual products ไม่มีค่าส่ง - ใช้ ShippingService คำนวณตามสินค้า
        $shippingFee = 0;
        if ($hasPhysicalProducts) {
            $shippingService = new ShippingService;
            $shippingResult = $shippingService->calculateForCart($cartItems);
            $shippingFee = $shippingResult['total_shipping'];
        }
        $total = $subtotal + $shippingFee;

        // Calculate cashback preview
        $cashbackPreview = $this->cashbackService->getCashbackPreview($cartItems, $total);

        // Calculate PV and earnings preview
        $pvPreview = $this->calculatePvPreview($cartItems);

        // Calculate earnings summary (สำหรับลูกค้า - เฉพาะสิ่งที่จะได้รับ)
        $earningsSummary = [
            'cashback_total' => $cashbackPreview['total_cashback'] ?? 0,
            'pv_total' => $pvPreview['total_pv'],
            'pv_breakdown' => $pvPreview['breakdown'],
        ];

        // Get available payment methods
        $paymentMethods = $this->paymentService->getAvailablePaymentMethods();

        // Get user wallet balance for wallet payment
        $wallet = Wallet::where('user_id', auth()->id())->first();
        $walletBalance = $wallet ? $wallet->balance : 0;

        return view('shop.checkout', compact(
            'cartItems',
            'addresses',
            'hasPhysicalProducts',
            'subtotal',
            'shippingFee',
            'total',
            'cashbackPreview',
            'pvPreview',
            'earningsSummary',
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
            return ! $item->product->is_virtual;
        });

        // ✅ Validation: shipping_address_id required เฉพาะเมื่อมีสินค้าที่ต้องส่ง
        $validationRules = [
            'payment_method' => 'required|in:wallet,promptpay,bank_transfer,credit_card,cash_on_delivery,paysolutions',
            'customer_notes' => 'nullable|string|max:1000',
        ];

        if ($hasPhysicalProducts) {
            $validationRules['shipping_address_id'] = [
                'required',
                \Illuminate\Validation\Rule::exists('shipping_addresses', 'id')
                    ->where('user_id', auth()->id())
                    ->whereNull('deleted_at'),
            ];
        }

        $request->validate($validationRules);

        // Check stock availability again
        foreach ($cartItems as $item) {
            if (! $item->isAvailable() || ! $item->hasEnoughStock()) {
                return redirect()->route('cart.index')->with('error', 'มีสินค้าบางรายการหมดสต็อกหรือไม่พร้อมจำหน่าย');
            }
        }

        DB::beginTransaction();

        try {
            // ✅ Get shipping address (เฉพาะสินค้าที่ต้องส่ง, ตรวจสอบ user_id ด้วย)
            $shippingAddress = $hasPhysicalProducts && $request->shipping_address_id
                ? ShippingAddress::where('user_id', auth()->id())
                    ->findOrFail($request->shipping_address_id)
                : null;

            // Calculate totals
            $subtotal = $cartItems->sum('subtotal');
            // ✅ Virtual products ไม่มีค่าส่ง - ใช้ ShippingService คำนวณตามสินค้า
            $shippingFee = 0;
            if ($hasPhysicalProducts) {
                $shippingService = new ShippingService;
                $shippingResult = $shippingService->calculateForCart($cartItems);
                $shippingFee = $shippingResult['total_shipping'];
            }
            $total = $subtotal + $shippingFee;
            $platformCommission = 0;
            $sellerEarning = 0;

            // Calculate cashback (ใช้ getCashbackPreview เพราะยังไม่มี Order)
            // ⚠️ แก้ไข: calculateOrderCashback รับ Order object ไม่ใช่ cartItems
            $cashbackPreview = $this->cashbackService->getCashbackPreview($cartItems, $total);
            $cashbackAmount = $cashbackPreview['total_cashback'];

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
                    'seller_id' => $product->seller_id ?? \App\Models\Product::getOfficialSellerId(),
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
                \Log::error('Failed to send order notification: '.$e->getMessage());
            }

            // Send notification to sellers
            foreach ($order->items->groupBy('seller_id') as $sellerId => $items) {
                try {
                    $seller = \App\Models\User::find($sellerId);
                    if ($seller) {
                        $seller->notify(new NewOrderNotification($order));
                    }
                } catch (\Exception $e) {
                    \Log::error('Failed to send seller notification: '.$e->getMessage());
                }
            }

            DB::commit();

            // Redirect to payment page
            return redirect()->route('checkout.payment', $order->id);

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'เกิดข้อผิดพลาด: '.$e->getMessage());
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

        // ถ้าไม่มี transaction แต่ order ยังรอชำระอยู่ → สร้าง transaction ใหม่
        if (! $transaction) {
            if ($order->canRetryPayment()) {
                $transaction = $this->paymentService->createOrderPayment(
                    $order,
                    $order->payment_method
                );
            } else {
                return redirect()->route('orders.show', $order->id)
                    ->with('error', 'ไม่พบรายการชำระเงิน');
            }
        }

        // ถ้า transaction หมดอายุ หรือ failed → สร้าง transaction ใหม่อัตโนมัติ
        if ($transaction->isExpired() || $transaction->status === 'failed') {
            if ($order->canRetryPayment()) {
                if ($transaction->status !== 'failed') {
                    $transaction->markAsFailed('หมดอายุ - สร้างรายการใหม่อัตโนมัติ');
                }

                // สร้าง transaction ใหม่
                $transaction = $this->paymentService->createOrderPayment(
                    $order,
                    $order->payment_method
                );
            } else {
                return redirect()->route('orders.show', $order->id)
                    ->with('error', 'รายการชำระเงินหมดอายุ กรุณาติดต่อเจ้าหน้าที่');
            }
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

        if (! $transaction) {
            return back()->with('error', 'ไม่พบรายการชำระเงิน');
        }

        // 💳 บัตรเครดิต/เดบิต → ใช้ Stripe Checkout (บัญชีเดียวกับดูดวง)
        //    redirect ไปหน้าจ่ายของ Stripe (hosted, PCI-safe) แล้วกลับมาที่ stripe.return
        if ($order->payment_method === 'credit_card') {
            $stripe = app(OrderStripeService::class);

            if (! $stripe->isEnabled()) {
                return back()->with('error', 'การชำระด้วยบัตรยังไม่พร้อมใช้งาน กรุณาเลือกวิธีชำระเงินอื่น');
            }

            $session = $stripe->createCheckoutSession($order, $transaction);

            if (! ($session['success'] ?? false)) {
                return back()->with('error', $session['error'] ?? 'ไม่สามารถเริ่มการชำระด้วยบัตรได้ กรุณาลองใหม่');
            }

            return redirect()->away($session['url']);
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
                            \Log::error('Failed to process cashback: '.$e->getMessage());
                        }
                    }

                    // ✅ ถ้าเป็น Wallet Topup Package - เพิ่มยอดเข้า Wallet
                    $this->processWalletTopupIfApplicable($order);

                    return redirect()->route('checkout.success', $order->id)
                        ->with('success', 'ชำระเงินสำเร็จ');
                }

                // For async payments (PromptPay, Bank Transfer, etc.)
                // ใช้ Post/Redirect/Get pattern เพื่อป้องกันการ POST ซ้ำเมื่อ refresh
                // ทศนิยมจะไม่เปลี่ยนเมื่อ refresh เพราะไม่สร้าง transaction ใหม่
                return redirect()->route('checkout.processing', $order->id);
            }

            return back()->with('error', $result['message'] ?? 'การชำระเงินล้มเหลว');

        } catch (\Exception $e) {
            \Log::error('Payment processing failed: '.$e->getMessage());

            return back()->with('error', 'เกิดข้อผิดพลาด: '.$e->getMessage());
        }
    }

    /**
     * Show payment processing page (GET - for PRG pattern)
     * ใช้ข้อมูล transaction ที่สร้างไว้แล้ว ไม่สร้างทศนิยมใหม่
     * เมื่อ refresh หน้า ยอดทศนิยมจะเหมือนเดิม
     */
    public function paymentProcessing($orderId)
    {
        $order = Order::with(['items.product', 'shippingAddress', 'paymentTransaction'])
            ->where('id', $orderId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        // ถ้าชำระแล้ว → redirect ไปหน้า success
        if ($order->payment_status === 'paid') {
            return redirect()->route('checkout.success', $order->id);
        }

        $transaction = $order->paymentTransaction;

        if (! $transaction) {
            return redirect()->route('orders.show', $order->id)
                ->with('error', 'ไม่พบรายการชำระเงิน');
        }

        // ถ้า transaction หมดอายุ หรือ failed → สร้างใหม่อัตโนมัติ
        if ($transaction->isExpired() || $transaction->status === 'failed') {
            if ($order->canRetryPayment()) {
                if ($transaction->status !== 'failed') {
                    $transaction->markAsFailed('หมดอายุ - สร้างรายการใหม่อัตโนมัติ');
                }
                $transaction = $this->paymentService->createOrderPayment(
                    $order,
                    $order->payment_method
                );
                // Process payment ใหม่เพื่อสร้าง unique amount + QR Code
                $result = $this->paymentService->processPayment($transaction, []);
                $transaction = $result['transaction'];
            } else {
                return redirect()->route('orders.show', $order->id)
                    ->with('error', 'รายการชำระเงินหมดอายุ กรุณาติดต่อเจ้าหน้าที่');
            }
        }

        // ⚡ Backward compatibility: ถ้า transaction ยังไม่มี unique amount (สร้างก่อน fix)
        // ให้สร้าง unique amount ให้ทันที (เฉพาะ promptpay/bank_transfer)
        if (in_array($transaction->payment_method, ['promptpay', 'bank_transfer'])) {
            $metadata = $transaction->metadata ?? [];
            if (empty($metadata['unique_amount_id']) && config('smschecker.enabled', true)) {
                \Log::info('SMS Checker: Transaction #'.$transaction->id.' ยังไม่มี unique amount - กำลังสร้างให้');
                try {
                    $uniqueAmount = UniquePaymentAmount::generate(
                        $transaction->amount,
                        $transaction->id,
                        $transaction->type ?? 'order',
                        config('smschecker.unique_amount_expiry', 60)
                    );

                    if ($uniqueAmount) {
                        $metadata['original_amount'] = $transaction->amount;
                        $metadata['unique_amount_id'] = $uniqueAmount->id;
                        $metadata['decimal_suffix'] = $uniqueAmount->decimal_suffix;

                        $transaction->update([
                            'amount' => $uniqueAmount->unique_amount,
                            'metadata' => $metadata,
                        ]);

                        $transaction = $transaction->fresh();

                        \Log::info('SMS Checker: สร้าง unique amount สำเร็จ (backward compat)', [
                            'transaction_id' => $transaction->id,
                            'original_amount' => $metadata['original_amount'],
                            'unique_amount' => $uniqueAmount->unique_amount,
                            'suffix' => $uniqueAmount->decimal_suffix,
                        ]);
                    }
                } catch (\Exception $e) {
                    \Log::error('SMS Checker: backward compat unique amount failed: '.$e->getMessage());
                }
            }
        }

        // สร้าง paymentData จาก transaction ที่มีอยู่แล้ว (ไม่สร้างใหม่)
        $paymentData = [
            'qr_code' => $transaction->promptpay_qr_code,
            'qr_code_image' => $transaction->promptpay_qr_code,
            'ref_no' => $transaction->promptpay_ref_no,
            'gateway' => $transaction->gateway,
            'gateway_transaction_id' => $transaction->gateway_transaction_id,
        ];

        // ดึงข้อมูล PromptPay account สำหรับแสดงในหน้าชำระเงิน
        $promptpayInfo = [];
        if ($order->payment_method === 'promptpay') {
            try {
                $promptpayProvider = app(PromptPayProvider::class);
                $promptpayInfo = $promptpayProvider->getAccountInfo();

                // ✅ ถ้ายังไม่มี QR image (transaction เก่าที่สร้างก่อน fix) → สร้างใหม่ทันที
                if (empty($paymentData['qr_code_image']) || ! str_starts_with($paymentData['qr_code_image'] ?? '', 'data:image/')) {
                    $qrImage = $promptpayProvider->generateQrDataUri((float) $transaction->amount);
                    if (! empty($qrImage)) {
                        $paymentData['qr_code'] = $qrImage;
                        $paymentData['qr_code_image'] = $qrImage;

                        // บันทึกลง DB เพื่อไม่ต้องสร้างใหม่ทุกครั้ง
                        $transaction->update(['promptpay_qr_code' => $qrImage]);
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('PromptPay info loading failed: '.$e->getMessage());
            }
        }

        return view('shop.payment-processing', [
            'order' => $order,
            'transaction' => $transaction,
            'paymentData' => $paymentData,
            'promptpayInfo' => $promptpayInfo,
        ]);
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
            \Log::error('Payment callback failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * 💳 กลับมาจากหน้าจ่ายบัตรของ Stripe (success_url)
     * ยืนยันการจ่ายแบบ synchronous (retrieve session) แล้ว complete order ทันที
     */
    public function stripeReturn(Request $request, $orderId)
    {
        $order = Order::where('id', $orderId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        if ($order->payment_status === 'paid') {
            return redirect()->route('checkout.success', $order->id)->with('success', 'ชำระเงินสำเร็จ');
        }

        $sessionId = $request->query('session_id');
        if ($sessionId) {
            try {
                $stripe = app(OrderStripeService::class);
                $session = $stripe->retrieveSession($sessionId);

                // ตรวจว่า session นี้เป็นของออเดอร์นี้จริง ก่อน complete (กันสลับ)
                if ($session && (string) ($session['metadata']['order_id'] ?? '') === (string) $order->id) {
                    $stripe->completeOrderFromSession($session);
                }
            } catch (\Exception $e) {
                \Log::error('Stripe return verify failed: '.$e->getMessage());
            }
        }

        $order->refresh();

        if ($order->payment_status === 'paid') {
            return redirect()->route('checkout.success', $order->id)->with('success', 'ชำระเงินสำเร็จ');
        }

        // ยังไม่ยืนยัน (async / ปิดแท็บ) → ระบบ webhook+poll จะตามอัปเดตให้
        return redirect()->route('orders.show', $order->id)
            ->with('info', 'ระบบกำลังตรวจสอบการชำระเงิน สถานะจะอัปเดตให้อัตโนมัติเมื่อยืนยันสำเร็จ');
    }

    /**
     * 💳 ยกเลิกการจ่ายบัตร (cancel_url) → กลับมาหน้าชำระเงิน
     */
    public function stripeCancel(Request $request, $orderId)
    {
        $order = Order::where('id', $orderId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        return redirect()->route('checkout.payment', $order->id)
            ->with('error', 'ยกเลิกการชำระด้วยบัตรแล้ว — ลองใหม่อีกครั้ง หรือเลือกวิธีชำระเงินอื่นได้');
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
     * คำนวณ PV ที่ลูกค้าจะได้รับ
     *
     * @param  \Illuminate\Support\Collection  $cartItems
     */
    /**
     * แก้ Bug PV-3: ใช้สูตรเดียวกับ OrderDistributionService.calculateTotalPvFromOrder()
     * pv_value เป็น percentage (เช่น 10 = 10% ของราคาสินค้า)
     * ไม่ fallback ใช้ price เพราะสินค้าที่ไม่มี PV = ไม่มี commission
     */
    private function calculatePvPreview($cartItems): array
    {
        $totalPv = 0;
        $breakdown = [];

        foreach ($cartItems as $item) {
            $product = $item->product;
            $quantity = $item->quantity;

            // ดึง PV percentage จาก product (เช่น 10 หมายถึง 10%)
            $pvPercentage = (float) ($product->pv_value ?? 0);

            if ($pvPercentage > 0) {
                // คำนวณ PV = (ราคาสินค้า × จำนวน / 100) × PV%
                // ตรงกับสูตร OrderDistributionService: ($item->total / 100) * $pvPercentage
                $itemTotal = $product->price * $quantity;
                $itemPv = ($itemTotal / 100) * $pvPercentage;
                $totalPv += $itemPv;

                $breakdown[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'pv_percentage' => $pvPercentage,
                    'item_total' => $itemTotal,
                    'quantity' => $quantity,
                    'total_pv' => round($itemPv, 2),
                ];
            }
        }

        return [
            'total_pv' => round($totalPv, 2),
            'breakdown' => $breakdown,
        ];
    }

    /**
     * คำนวณค่าจัดส่ง (Fallback สำหรับกรณีที่ไม่มี cart items)
     *
     * @param  float  $subtotal  ยอดรวมสินค้า
     * @return float ค่าจัดส่ง
     */
    private function calculateShippingFee($subtotal)
    {
        // ใช้ค่าเริ่มต้นจาก ShippingService
        if ($subtotal >= ShippingService::DEFAULT_FREE_SHIPPING_THRESHOLD) {
            return 0;
        }

        return ShippingService::DEFAULT_SHIPPING_FEE;
    }

    /**
     * ประมวลผล Wallet Topup Package (เพิ่มยอดเข้า Wallet)
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
            \Log::error('[Wallet Topup] เกิดข้อผิดพลาด: '.$e->getMessage());
            // ไม่ throw exception เพื่อไม่ให้กระทบกับการทำงานของ order
        }
    }
}
