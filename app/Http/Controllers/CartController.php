<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ShoppingCart;
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

        // 🚫 สินค้า affiliate (Lazada/AliExpress) ใส่ตะกร้าเราไม่ได้
        //    เราไม่ได้สต๊อก ไม่ได้เก็บเงิน และไม่ได้จัดส่งเอง — ต้องไปจบที่แพลตฟอร์มต้นทาง
        //    ถ้าปล่อยให้เข้าตะกร้า จะเกิดออเดอร์ที่ไม่มีใครส่งของได้
        if ($product->is_affiliate) {
            $message = 'สินค้านี้ต้องสั่งซื้อที่แพลตฟอร์มต้นทางค่ะ กดปุ่มซื้อในหน้ารายละเอียดสินค้าได้เลย';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'redirect' => route('shop.show', $product->slug ?: $product->id),
                ], 400);
            }

            return redirect()
                ->route('shop.show', $product->slug ?: $product->id)
                ->with('error', $message);
        }

        // Check if product is available
        if (! $product->isInStock()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'สินค้านี้หมดสต็อกแล้ว',
                ], 400);
            }

            return back()->with('error', 'สินค้านี้หมดสต็อกแล้ว');
        }

        // Check stock quantity
        if ($product->track_inventory && $product->stock_quantity < $request->quantity) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'สินค้ามีจำนวนไม่เพียงพอ',
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
                        'message' => 'สินค้ามีจำนวนไม่เพียงพอ',
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
                'cart_count' => $cartCount,
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
                'message' => 'สินค้ามีจำนวนไม่เพียงพอ',
            ], 400);
        }

        $cartItem->update(['quantity' => $request->quantity]);

        return response()->json([
            'success' => true,
            'message' => 'อัพเดตจำนวนสินค้าเรียบร้อย',
            'subtotal' => $cartItem->subtotal,
        ]);
    }

    /**
     * Remove item from cart
     *
     * รองรับทั้ง AJAX และ redirect
     */
    public function remove(Request $request, $id)
    {
        $cartItem = ShoppingCart::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $cartItem->delete();

        // Return JSON สำหรับ AJAX requests
        if ($request->expectsJson() || $request->ajax()) {
            $cartCount = ShoppingCart::where('user_id', auth()->id())->sum('quantity');

            return response()->json([
                'success' => true,
                'message' => 'ลบสินค้าออกจากตะกร้าเรียบร้อยแล้ว',
                'cart_count' => $cartCount,
            ]);
        }

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

    /**
     * ดึงข้อมูล mini cart สำหรับแสดงใน topbar dropdown
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function mini()
    {
        // ดึงสินค้าในตะกร้า (ทุกรายการ เพื่อแสดงใน slide panel)
        $cartItems = ShoppingCart::with(['product' => function ($query) {
            $query->select('id', 'name', 'price', 'main_image_url');
        }])
            ->where('user_id', auth()->id())
            ->latest()
            ->get();

        // จัดรูปแบบข้อมูลสำหรับ JSON
        $items = $cartItems->map(function ($item) {
            // รูปสินค้า: Product::getMainImageUrlAttribute() คืน URL พร้อมใช้แล้ว
            //   (URL เต็ม → คืนเดิม / path → /storage/...) เหมือนที่หน้าอื่นเรนเดอร์ {{ $product->main_image_url }} ดิบ
            // ❌ ห้าม wrap Storage::url ซ้ำ — เคยทำให้ Lazada CDN URL พัง (double-prefix)
            $imageUrl = $item->product?->main_image_url;

            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'name' => $item->product->name ?? 'สินค้าไม่พบ',
                'price' => $item->product->price ?? 0,
                'quantity' => $item->quantity,
                'image' => $imageUrl,
            ];
        });

        // คำนวณยอดรวม
        $total = $cartItems->sum(function ($item) {
            return ($item->product->price ?? 0) * $item->quantity;
        });

        $count = $cartItems->sum('quantity');

        return response()->json([
            'items' => $items,
            'count' => $count,
            'total' => $total,
        ]);
    }
}
