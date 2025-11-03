<?php

namespace App\Http\Controllers;

use App\Models\ShoppingCart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ShippingAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
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

        // Get user's shipping addresses
        $addresses = ShippingAddress::where('user_id', auth()->id())
            ->orderBy('is_default', 'desc')
            ->get();

        // Calculate totals
        $subtotal = $cartItems->sum('subtotal');
        $shippingFee = $this->calculateShippingFee($subtotal);
        $total = $subtotal + $shippingFee;

        return view('shop.checkout', compact(
            'cartItems',
            'addresses',
            'subtotal',
            'shippingFee',
            'total'
        ));
    }

    /**
     * Process checkout and create order
     */
    public function process(Request $request)
    {
        $request->validate([
            'shipping_address_id' => 'required|exists:shipping_addresses,id',
            'payment_method' => 'required|in:promptpay,bank_transfer,credit_card,cod',
            'customer_notes' => 'nullable|string|max:1000',
        ]);

        $cartItems = ShoppingCart::with(['product.seller'])
            ->where('user_id', auth()->id())
            ->get();

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'ตะกร้าสินค้าของคุณว่างเปล่า');
        }

        DB::beginTransaction();

        try {
            // Get shipping address
            $shippingAddress = ShippingAddress::findOrFail($request->shipping_address_id);

            // Calculate totals
            $subtotal = $cartItems->sum('subtotal');
            $shippingFee = $this->calculateShippingFee($subtotal);
            $total = $subtotal + $shippingFee;
            $platformCommission = 0;
            $sellerEarning = 0;

            // Create order
            $order = Order::create([
                'user_id' => auth()->id(),
                'shipping_address_id' => $request->shipping_address_id,
                'status' => 'pending',
                'payment_method' => $request->payment_method,
                'payment_status' => 'pending',
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
                'total_amount' => $total,
                'customer_notes' => $request->customer_notes,
                'shipping_address_snapshot' => $shippingAddress->toArray(),
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

                // Decrease product stock
                $product->decreaseStock($cartItem->quantity);

                // Update sales count
                $product->increment('sales_count', $cartItem->quantity);

                $platformCommission += $commissionAmount;
                $sellerEarning += $itemSellerEarning;
            }

            // Update order commission
            $order->update([
                'platform_commission' => $platformCommission,
                'seller_earning' => $sellerEarning,
            ]);

            // Clear cart
            ShoppingCart::where('user_id', auth()->id())->delete();

            DB::commit();

            return redirect()->route('checkout.success', $order->id);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
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
}
