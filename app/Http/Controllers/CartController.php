<?php

namespace App\Http\Controllers;

use App\Models\ShoppingCart;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display cart
     */
    public function index()
    {
        $cartItems = ShoppingCart::with(['product.category', 'product.seller'])
            ->where('user_id', auth()->id())
            ->get();

        // Calculate totals
        $subtotal = $cartItems->sum(function ($item) {
            return $item->product->price * $item->quantity;
        });

        $shippingFee = 0; // Calculate based on your logic
        $total = $subtotal + $shippingFee;

        return view('shop.cart', compact('cartItems', 'subtotal', 'shippingFee', 'total'));
    }

    /**
     * Add item to cart
     */
    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'attributes' => 'nullable|array',
        ]);

        $product = Product::findOrFail($request->product_id);

        // Check if product is available
        if (!$product->isInStock()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'สินค้านี้หมดสต็อกแล้ว'
                ], 400);
            }
            return back()->with('error', 'สินค้านี้หมดสต็อกแล้ว');
        }

        // Check stock quantity
        if ($product->track_inventory && $product->stock_quantity < $request->quantity) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'สินค้ามีจำนวนไม่เพียงพอ'
                ], 400);
            }
            return back()->with('error', 'สินค้ามีจำนวนไม่เพียงพอ');
        }

        // Check if item already exists in cart
        $cartItem = ShoppingCart::where('user_id', auth()->id())
            ->where('product_id', $request->product_id)
            ->where('selected_attributes', json_encode($request->attributes ?? []))
            ->first();

        if ($cartItem) {
            // Update quantity
            $newQuantity = $cartItem->quantity + $request->quantity;

            if ($product->track_inventory && $product->stock_quantity < $newQuantity) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'สินค้ามีจำนวนไม่เพียงพอ'
                    ], 400);
                }
                return back()->with('error', 'สินค้ามีจำนวนไม่เพียงพอ');
            }

            $cartItem->update(['quantity' => $newQuantity]);
        } else {
            // Create new cart item
            ShoppingCart::create([
                'user_id' => auth()->id(),
                'product_id' => $request->product_id,
                'quantity' => $request->quantity,
                'selected_attributes' => $request->attributes,
            ]);
        }

        // Get updated cart count
        $cartCount = ShoppingCart::where('user_id', auth()->id())->sum('quantity');

        // Return JSON for AJAX requests
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'เพิ่มสินค้าลงตะกร้าเรียบร้อยแล้ว',
                'cart_count' => $cartCount
            ]);
        }

        // ✅ ถ้ามี redirect_to_checkout parameter ให้ไปหน้า checkout ทันที (สำหรับ Wallet Topup)
        if ($request->has('redirect_to_checkout') && $request->redirect_to_checkout) {
            return redirect()->route('checkout.index')->with('success', 'เพิ่มแพ็คเกจเติมเงินเรียบร้อยแล้ว กรุณาชำระเงิน');
        }

        return redirect()->route('cart.index')->with('success', 'เพิ่มสินค้าลงตะกร้าเรียบร้อยแล้ว');
    }

    /**
     * Update cart item quantity
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $cartItem = ShoppingCart::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $product = $cartItem->product;

        // Check stock
        if ($product->track_inventory && $product->stock_quantity < $request->quantity) {
            return response()->json([
                'success' => false,
                'message' => 'สินค้ามีจำนวนไม่เพียงพอ'
            ], 400);
        }

        $cartItem->update(['quantity' => $request->quantity]);

        return response()->json([
            'success' => true,
            'message' => 'อัพเดตจำนวนสินค้าเรียบร้อย',
            'subtotal' => $cartItem->subtotal
        ]);
    }

    /**
     * Remove item from cart
     */
    public function remove($id)
    {
        $cartItem = ShoppingCart::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $cartItem->delete();

        return redirect()->route('cart.index')->with('success', 'ลบสินค้าออกจากตะกร้าเรียบร้อยแล้ว');
    }

    /**
     * Clear cart
     */
    public function clear()
    {
        ShoppingCart::where('user_id', auth()->id())->delete();

        return redirect()->route('cart.index')->with('success', 'ล้างตะกร้าสินค้าเรียบร้อยแล้ว');
    }

    /**
     * Get cart count (for header badge)
     */
    public function count()
    {
        $count = ShoppingCart::where('user_id', auth()->id())->sum('quantity');

        return response()->json(['count' => $count]);
    }
}
