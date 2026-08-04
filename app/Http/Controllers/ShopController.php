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
        // ⚠️ ต้องกรอง visible() + notBlocked() ด้วย
        //    lazada:publish-storefront ตั้ง is_hidden = true ให้สินค้าที่ยัง stage ไว้ (ยังไม่สั่ง --visible)
        //    ถ้าไม่กรอง สินค้าที่ตั้งใจซ่อน/ถูกบล็อกจะโผล่ในบล็อกนี้ทั้งที่ไม่ควรเห็น
        $relatedProducts = Product::with(['category', 'seller'])
            ->active()
            ->visible()
            ->notBlocked()
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

        // ── สินค้า affiliate (Lazada/AliExpress) ────────────────────────────
        //    เราไม่ได้สต๊อกเอง → ไม่มีตะกร้า/ค่าส่ง/cashback ของเรา
        //    หน้านี้ทำหน้าที่ "หน้ารายละเอียดกลาง" ก่อนส่งลูกค้าออกไปซื้อที่แพลตฟอร์มต้นทาง
        $isAffiliate = (bool) $product->is_affiliate && ! empty($product->affiliate_url);
        $platform = (string) ($product->external_platform ?? '');
        $platformLabel = $platform === 'aliexpress' ? 'AliExpress' : 'Lazada';
        // สีประจำแพลตฟอร์ม — ต้องแยกตาม $platform ห้าม hardcode Lazada
        // เพราะหน้านี้เป็นหน้า landing ของสินค้า AliExpress ด้วย
        $platformAccent = $platform === 'aliexpress' ? '#e62e04' : '#0f146d';
        $platformAccent2 = $platform === 'aliexpress' ? '#ff6a00' : '#f57224';

        // ลิงก์ขาออก — ผ่าน /go/{code} เพื่อบันทึกว่า "ใครเป็นคนกด"
        // Lazada ไม่รับ subId → click log ของเราคือหลักฐานชิ้นเดียวที่ผูกยอดขายกลับหาผู้แนะนำได้
        // ผู้ที่ไม่ได้ล็อกอิน = ไม่มีใครให้ผูก จึงใช้ลิงก์ดิบตามเดิม
        $outboundUrl = (string) ($product->affiliate_url ?? '');
        if ($isAffiliate) {
            $authUser = auth()->user();
            if ($authUser instanceof \App\Models\User) {
                $codes = \App\Models\MarketplaceAffiliateLink::shortCodesForProducts($authUser, [$product]);
                if (! empty($codes[$product->id])) {
                    $outboundUrl = route('affiliate.go', $codes[$product->id]);
                }
            }
        }

        // คำนวณ cashback ที่เป็นไปได้ (ข้ามสำหรับ affiliate — เงินไม่ได้เข้าระบบเรา)
        // ⚠️ ต้องกำหนดเป็น null เสมอ ไม่ใช่ปล่อยไม่ประกาศ
        //    compact() บนตัวแปรที่ไม่มีอยู่จะเตือนแล้วตัดคีย์ทิ้ง → blade ที่อ่านค่าจะพัง
        $cashbackInfo = null;
        $shippingInfo = null;
        if (! $isAffiliate) {
            $cashbackService = new CashbackService(new WalletService);
            $cashbackInfo = $cashbackService->calculateProductCashback($product, $product->price, 1);

            // ข้อมูลค่าจัดส่งสำหรับแสดงผล
            $shippingService = new \App\Services\ShippingService;
            $shippingInfo = $shippingService->getShippingDisplayInfo($product);
        }

        return view('shop.show', compact(
            'product',
            'relatedProducts',
            'hasPurchased',
            'cashbackInfo',
            'shippingInfo',
            'isAffiliate',
            'platform',
            'platformLabel',
            'platformAccent',
            'platformAccent2',
            'outboundUrl'
        ));
    }
}
