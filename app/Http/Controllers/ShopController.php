<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CashbackService;
use App\Services\WalletService;

/**
 * ShopController (Legacy)
 *
 * เหลือเฉพาะ show() สำหรับ route /shop/{slug}
 * routes อื่นๆ (/shop/, /shop/category/) เป็น redirect closures ใน web.php
 * ที่ redirect ไป StorefrontController แล้ว
 */
class ShopController extends Controller
{
    /**
     * แสดงรายละเอียดสินค้า
     */
    public function show($slug)
    {
        $product = Product::with([
            'category',
            'seller',
            'images',
            'variants',
            'approvedReviews.user',
        ])
            ->where(function ($query) use ($slug) {
                // ค้นหาด้วย slug ก่อน ถ้าไม่เจอลองค้นหาด้วย ID (กรณี slug ว่าง เช่น ชื่อสินค้าภาษาไทย)
                $query->where('slug', $slug);
                if (is_numeric($slug)) {
                    $query->orWhere('id', $slug);
                }
            })
            ->firstOrFail();

        // Increment view count
        $product->incrementViewCount();

        // สินค้าที่เกี่ยวข้อง
        $relatedProducts = Product::with(['category', 'seller'])
            ->active()
            ->inStock()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->take(8)
            ->get();

        // เช็คว่า user เคยซื้อสินค้านี้หรือไม่ (สำหรับรีวิว)
        $hasPurchased = false;
        if (auth()->check()) {
            $hasPurchased = $product->orderItems()
                ->whereHas('order', function ($q) {
                    $q->where('user_id', auth()->id())
                        ->where('status', 'completed');
                })
                ->exists();
        }

        // คำนวณ cashback ที่เป็นไปได้
        $cashbackService = new CashbackService(new WalletService);
        $cashbackInfo = $cashbackService->calculateProductCashback($product, $product->price, 1);

        // ข้อมูลค่าจัดส่งสำหรับแสดงผล
        $shippingService = new \App\Services\ShippingService;
        $shippingInfo = $shippingService->getShippingDisplayInfo($product);

        return view('shop.show', compact(
            'product',
            'relatedProducts',
            'hasPurchased',
            'cashbackInfo',
            'shippingInfo'
        ));
    }
}
