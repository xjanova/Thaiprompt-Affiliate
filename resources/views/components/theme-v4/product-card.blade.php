{{--
 | การ์ดสินค้า ธีม V4 (ใช้กับกริดสินค้าทุกหน้าร้าน) — ต้องมี <x-theme-v4.shop-kit /> ในหน้า
 |
 | ตัวอย่าง:  <x-theme-v4.product-card :product="$product" :favorited="in_array($product->id, $favIds)" />
 |
 | props:
 |   product    Product|object   สินค้า (รองรับ object จำลองของหน้า preview: name, price, sale_price, discount_percent, primary_image_url, rating_average, sales_count)
 |   href       string|null      ลิงก์ไปหน้ารายละเอียด (ค่าเริ่มต้น shop.show)
 |   favorited  bool             อยู่ในรายการโปรดแล้ว
 |   showCart   bool             แสดงปุ่มใส่ตะกร้า
 |   points     bool             แสดงป้าย "คะแนนสะสม" (ถ้าสินค้ามีคะแนน)
 |   preview    bool             โหมดตัวอย่าง (ปิดปุ่มทั้งหมด ลิงก์เป็น #)
 --}}
@props([
    'product',
    'href' => null,
    'favorited' => false,
    'showCart' => true,
    'points' => true,
    'preview' => false,
])

@php
    $pcModel = $product instanceof \App\Models\Product;
    $pcId = (int) ($product->id ?? 0);
    $pcName = (string) ($product->name ?? 'สินค้า');

    if ($pcModel) {
        $pcPrice = (float) $product->price;
        $pcCompare = $product->compare_at_price !== null ? (float) $product->compare_at_price : null;
        $pcImage = \App\Services\Shop\ShopPresenter::productImages($product)[0] ?? null;
    } else {
        // object จำลอง (หน้า preview ของผู้ขาย)
        $pcPrice = (float) ($product->sale_price ?? $product->price ?? 0);
        $pcCompare = isset($product->sale_price) && $product->sale_price ? (float) ($product->price ?? 0) : null;
        $pcImage = $product->primary_image_url ?? ($product->image ?? null);
    }

    $pcDiscount = 0;
    if ($pcModel && (int) ($product->deal_discount_percent ?? 0) > 0) {
        $pcDiscount = (int) $product->deal_discount_percent;
    } elseif ($pcCompare !== null && $pcCompare > $pcPrice && $pcCompare > 0) {
        $pcDiscount = (int) round(($pcCompare - $pcPrice) / $pcCompare * 100);
    } elseif (! $pcModel && (int) ($product->discount_percent ?? 0) > 0) {
        $pcDiscount = (int) $product->discount_percent;
    }

    $pcAffiliate = $pcModel && (bool) $product->is_affiliate && ! empty($product->affiliate_url);
    $pcPlatform = $pcAffiliate && ($product->external_platform ?? '') === 'aliexpress' ? 'AliExpress' : 'Lazada';
    $pcInStock = $pcModel ? $product->isInStock() : true;
    $pcOfficial = $pcModel && $product->seller_id && $product->isOfficialShopProduct();

    // คะแนนสะสม (ค่าเดิมคือ PV — ใช้ถ้อยคำกลางๆ เพราะหน้าเว็บอาจเปิดในแอป)
    $pcPoints = 0.0;
    if ($points && $pcModel) {
        if ($product->relationLoaded('mlmProductPv') && $product->mlmProductPv && $product->mlmProductPv->count() > 0) {
            $pcPoints = (float) $product->mlmProductPv->avg('pv_value');
        } elseif ($pcAffiliate) {
            $pcPoints = (float) ($product->pv_value ?? 0);
        }
    }

    // ป้ายการจัดส่ง
    $pcRibbon = null;
    $pcRibbonWarm = false;
    if ($pcAffiliate) {
        $pcRibbon = ($product->shipping_speed ?? '') === 'slow' ? 'ส่งจากต่างประเทศ' : 'ส่งไว ในไทย';
        $pcRibbonWarm = ($product->shipping_speed ?? '') === 'slow';
    } elseif ($pcModel && ! $product->is_virtual) {
        $pcShipMethod = $product->shipping_method ?? 'store_default';
        if ($pcShipMethod === 'free' || ($pcShipMethod === 'store_default' && $pcPrice >= \App\Services\ShippingService::DEFAULT_FREE_SHIPPING_THRESHOLD)) {
            $pcRibbon = 'ส่งฟรี';
        } elseif ($pcShipMethod === 'flat_rate' && (float) ($product->shipping_fee ?? 0) > 0) {
            $pcRibbon = 'ค่าส่ง ฿'.number_format((float) $product->shipping_fee, 0);
            $pcRibbonWarm = true;
        }
    }

    // ส่งด้วยไรเดอร์ได้ (เฉพาะเมื่อโหลดร้านมาแล้ว — ไม่ยิง query เพิ่มต่อการ์ด)
    $pcRider = $pcModel && $product->relationLoaded('store') && $product->store && $product->store->canUseRiderDelivery();

    $pcHref = $preview ? '#' : ($href ?? (\Illuminate\Support\Facades\Route::has('shop.show') && $pcModel ? route('shop.show', $product->slug ?: $product->id) : '#'));
    $pcRating = (float) ($product->rating_average ?? 0);
    $pcSales = (int) ($product->sales_count ?? 0);
@endphp

<article class="sf-card" {{ $attributes }}>
    <a href="{{ $pcHref }}" @if($preview) tabindex="-1" aria-disabled="true" @endif>
        <div class="sf-media">
            @if($pcImage)
                <img src="{{ $pcImage }}" alt="{{ $pcName }}" loading="lazy" decoding="async" width="300" height="300"
                     onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='block';">
                <span class="sf-noimg" style="display:none;" aria-hidden="true">📦</span>
            @else
                <span class="sf-noimg" aria-hidden="true">📦</span>
            @endif

            <div class="sf-badges">
                @if($pcAffiliate)
                    <span class="sf-badge" style="background:{{ $pcPlatform === 'AliExpress' ? 'var(--sf-aliexpress, #e62e04)' : 'var(--sf-lazada, #0f146d)' }};">{{ $pcPlatform }}</span>
                @endif
                @if($pcDiscount > 0)
                    <span class="sf-badge sf-badge-sale">-{{ $pcDiscount }}%</span>
                @endif
                @if($pcOfficial)
                    <span class="sf-badge sf-badge-deep"><i class="fas fa-circle-check"></i> ทางการ</span>
                @endif
                @if($pcModel && $product->is_featured)
                    <span class="sf-badge sf-badge-gold">แนะนำ</span>
                @endif
                @if($pcRider)
                    <span class="sf-badge sf-badge-ok"><i class="fas fa-motorcycle"></i> ไรเดอร์</span>
                @endif
            </div>

            @if(! $pcInStock)
                <span class="sf-ribbon" style="background:color-mix(in srgb, var(--ink) 80%, transparent);">สินค้าหมด</span>
            @elseif($pcRibbon)
                <span class="sf-ribbon {{ $pcRibbonWarm ? 'is-warm' : '' }}">{{ $pcRibbon }}</span>
            @endif
        </div>

        <div class="sf-body">
            <div class="sf-name">{{ $pcName }}</div>
            <div style="display:flex; align-items:baseline; gap:7px; flex-wrap:wrap;">
                <span class="sf-price">฿{{ number_format($pcPrice, $pcPrice == floor($pcPrice) ? 0 : 2) }}</span>
                @if($pcCompare !== null && $pcCompare > $pcPrice)
                    <span class="sf-compare">฿{{ number_format($pcCompare, 0) }}</span>
                @endif
            </div>
            @if($pcRating > 0 || $pcSales > 0 || $pcPoints > 0)
                <div class="sf-meta">
                    @if($pcRating > 0)
                        <span><span class="sf-stars">★</span> {{ number_format($pcRating, 1) }}</span>
                    @endif
                    @if($pcSales > 0)
                        <span>ขายแล้ว {{ number_format($pcSales) }}</span>
                    @endif
                    @if($pcPoints > 0)
                        <span class="sf-points"><i class="fas fa-star"></i> คะแนนสะสม {{ rtrim(rtrim(number_format($pcPoints, 2), '0'), '.') }}</span>
                    @endif
                </div>
            @endif
        </div>
    </a>

    @if($pcModel && ! $preview)
        <button type="button" class="sf-fav {{ $favorited ? 'is-on' : '' }}"
                data-favorited="{{ $favorited ? '1' : '0' }}"
                aria-pressed="{{ $favorited ? 'true' : 'false' }}"
                aria-label="{{ $favorited ? 'นำออกจากรายการโปรด' : 'เพิ่มในรายการโปรด' }}"
                title="{{ $favorited ? 'นำออกจากรายการโปรด' : 'เพิ่มในรายการโปรด' }}"
                onclick="window.tpShop && window.tpShop.toggleFavorite({{ $pcId }}, this)">
            <i class="{{ $favorited ? 'fas' : 'far' }} fa-heart"></i>
        </button>
    @endif

    @if($showCart)
        <div class="sf-card-cta">
            @if($preview)
                <span class="tp-btn tp-btn-primary" style="opacity:.8; cursor:default;"><i class="fas fa-cart-plus"></i> ใส่ตะกร้า</span>
            @elseif($pcAffiliate)
                <a href="{{ $pcHref }}" class="tp-btn" style="text-decoration:none;"><i class="fas fa-eye"></i> ดูรายละเอียด</a>
            @elseif($pcModel && $pcInStock)
                <button type="button" class="tp-btn tp-btn-primary" onclick="window.tpShop && window.tpShop.addToCart({{ $pcId }}, 1, { button: this })">
                    <i class="fas fa-cart-plus"></i> ใส่ตะกร้า
                </button>
            @else
                <span class="tp-btn" style="opacity:.55; cursor:not-allowed;">สินค้าหมด</span>
            @endif
        </div>
    @endif
</article>
