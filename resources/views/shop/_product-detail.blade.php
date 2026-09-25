{{--
 | ส่วนรายละเอียดสินค้า (แกลเลอรี + ราคา + ปุ่มซื้อ + แท็บรายละเอียด/รีวิว/การจัดส่ง) ใช้ร่วม:
 |   shop.show · shop.official.show · vendor-store.product-show
 | ต้องมี <x-theme-v4.shop-kit /> ในหน้า
 |
 | ตัวแปร (ส่งผ่าน @include):
 |   $product (บังคับ — โหลด images, approvedReviews.user มาแล้ว)
 |   $pdAffiliate bool, $pdPlatform string, $pdPlatformLabel string, $pdOutbound string  (สินค้า affiliate — ลิงก์ขาออกต้องผ่าน /go/{code})
 |   $pdCashback array|null (CashbackService::calculateProductCashback), $pdShipping array|null (ShippingService::getShippingDisplayInfo)
 |   $pdStore VendorStore|null (ไม่ส่ง = resolveStore()), $pdStoreHref string|null
 |   $pdCoin array|null ['price' => coins ต่อชิ้น, 'balance' => coins ของผู้ใช้, 'action' => url ฟอร์มซื้อด้วย Coins]
 --}}
@php
    $pdAffiliate = (bool) ($pdAffiliate ?? false);
    $pdPlatform = (string) ($pdPlatform ?? '');
    $pdPlatformLabel = (string) ($pdPlatformLabel ?? 'Lazada');
    $pdOutbound = (string) ($pdOutbound ?? '#');
    $pdCashback = $pdCashback ?? null;
    $pdShipping = $pdShipping ?? null;
    $pdCoin = $pdCoin ?? null;
    $pdStore = $pdStore ?? $product->resolveStore();
    $pdStoreHref = $pdStoreHref ?? ($pdStore ? route('store.show', $pdStore->store_slug) : null);

    $pdImages = \App\Services\Shop\ShopPresenter::productImages($product);
    $pdPrice = (float) $product->price;
    $pdCompare = $product->compare_at_price !== null ? (float) $product->compare_at_price : null;
    $pdOnSale = $pdCompare !== null && $pdCompare > $pdPrice;
    $pdDiscount = $pdOnSale && $pdCompare > 0 ? (int) round(($pdCompare - $pdPrice) / $pdCompare * 100) : 0;
    $pdInStock = $product->isInStock() && ($product->stock_status ?? 'in_stock') === 'in_stock' && $product->purchaseBlockReason() === null;
    $pdMaxQty = $product->track_inventory ? max(1, (int) $product->stock_quantity) : 99;
    $pdLowStock = $product->track_inventory && $pdInStock && (int) $product->stock_quantity < (int) ($product->low_stock_threshold ?? 5);
    $pdRating = (float) ($product->rating_average ?? 0);
    $pdReviews = $product->approvedReviews ?? collect();
    $pdDist = [];
    foreach ([5, 4, 3, 2, 1] as $pdStar) {
        $pdDist[$pdStar] = $pdReviews->where('rating', $pdStar)->count();
    }
    $pdReviewTotal = max(1, $pdReviews->count());
    $pdDescription = \App\Support\Shop\SafeHtml::clean($product->description);
    $pdPoints = (float) ($product->pv_value ?? 0);
    $pdFav = false;
    if (auth()->check()) {
        try {
            $pdFav = \App\Models\ProductFavorite::where('user_id', auth()->id())->where('product_id', $product->id)->exists();
        } catch (\Throwable $e) {
            $pdFav = false;
        }
    }
    $pdStars = fn (float $r) => str_repeat('★', (int) round($r)).str_repeat('☆', max(0, 5 - (int) round($r)));
    $pdAffBg = $pdPlatform === 'aliexpress'
        ? 'linear-gradient(180deg, var(--sf-aliexpress2, #ff6a00) 0%, var(--sf-aliexpress, #e62e04) 100%)'
        : 'linear-gradient(180deg, var(--sf-lazada2, #f57224) 0%, var(--sf-lazada, #0f146d) 100%)';
@endphp

<section class="sf-wrap" style="padding-top:14px;">
    <div class="tp-card" style="padding:clamp(14px, 2.6vw, 26px);">
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(min(100%, 340px), 1fr)); gap:clamp(16px, 3vw, 32px); align-items:start;">

            {{-- ── แกลเลอรี ── --}}
            <div x-data="{ imgs: {{ \Illuminate\Support\Js::from(array_values(array_slice($pdImages, 0, 10))) }}, i: 0, zoom: false }" style="min-width:0;">
                <div style="position:relative; aspect-ratio:1/1; border-radius:22px; overflow:hidden; background:var(--surf); box-shadow:var(--inset); display:grid; place-items:center;">
                    <template x-if="imgs.length > 0">
                        <button type="button" @click="zoom = true" aria-label="ขยายรูป" style="all:unset; cursor:zoom-in; width:100%; height:100%; display:block;">
                            <img :src="imgs[i]" alt="{{ $product->name }}" style="width:100%; height:100%; object-fit:cover;">
                        </button>
                    </template>
                    <template x-if="imgs.length === 0">
                        <span style="font-size:64px; opacity:.35;" aria-hidden="true">📦</span>
                    </template>
                    <div class="sf-badges" style="top:14px; left:14px;">
                        @if($pdAffiliate)
                            <span class="sf-badge" style="background:{{ $pdPlatform === 'aliexpress' ? 'var(--sf-aliexpress, #e62e04)' : 'var(--sf-lazada, #0f146d)' }};">{{ $pdPlatformLabel }}</span>
                        @endif
                        @if($pdDiscount > 0)
                            <span class="sf-badge sf-badge-sale" style="font-size:12px;">ลด {{ $pdDiscount }}%</span>
                        @endif
                        @if($product->is_featured)
                            <span class="sf-badge sf-badge-gold" style="font-size:12px;"><i class="fas fa-star"></i> แนะนำ</span>
                        @endif
                    </div>
                    @if(! $pdInStock && ! $pdAffiliate)
                        <div style="position:absolute; inset:0; display:grid; place-items:center; background:color-mix(in srgb, var(--ink) 55%, transparent);">
                            <span class="sf-badge sf-badge-ink" style="font-size:18px; padding:12px 20px;">สินค้าหมด</span>
                        </div>
                    @elseif($pdLowStock)
                        <div style="position:absolute; left:14px; right:14px; bottom:14px;">
                            <div class="sf-note sf-note-warn" style="text-align:center; font-weight:700; -webkit-backdrop-filter:blur(6px); backdrop-filter:blur(6px);">
                                <i class="fas fa-fire"></i> เหลือเพียง {{ (int) $product->stock_quantity }} ชิ้น
                            </div>
                        </div>
                    @endif
                </div>
                <template x-if="imgs.length > 1">
                    <div class="sf-scroll" style="margin-top:12px;">
                        <template x-for="(src, k) in imgs" :key="k">
                            <button type="button" @click="i = k" :aria-label="'รูปที่ ' + (k + 1)"
                                    :style="i === k ? 'box-shadow:var(--inset-sm); outline:2px solid var(--accent1);' : 'box-shadow:var(--raise);'"
                                    style="flex:none; width:68px; height:68px; padding:0; border:0; border-radius:14px; overflow:hidden; cursor:pointer; background:var(--surf);">
                                <img :src="src" alt="" loading="lazy" style="width:100%; height:100%; object-fit:cover;">
                            </button>
                        </template>
                    </div>
                </template>

                <div x-show="zoom" x-cloak x-transition.opacity @click.self="zoom = false" @keydown.escape.window="zoom = false"
                     role="dialog" aria-modal="true" aria-label="รูปสินค้าแบบเต็มจอ" class="grid"
                     style="position:fixed; inset:0; z-index:90; place-items:center; padding:16px; background:rgba(0,0,0,.82);">
                    <img :src="imgs[i]" alt="{{ $product->name }}" style="max-width:min(960px, 100%); max-height:86vh; border-radius:18px; object-fit:contain;">
                    <button type="button" @click="zoom = false" class="tp-icon-btn" aria-label="ปิด" style="position:absolute; top:16px; right:16px;"><i class="fas fa-xmark"></i></button>
                    <template x-if="imgs.length > 1">
                        <div>
                            <button type="button" @click="i = (i - 1 + imgs.length) % imgs.length" class="tp-icon-btn" aria-label="รูปก่อนหน้า" style="position:absolute; left:16px; top:50%;"><i class="fas fa-chevron-left"></i></button>
                            <button type="button" @click="i = (i + 1) % imgs.length" class="tp-icon-btn" aria-label="รูปถัดไป" style="position:absolute; right:16px; top:50%;"><i class="fas fa-chevron-right"></i></button>
                        </div>
                    </template>
                </div>
            </div>

            {{-- ── ข้อมูลสินค้า ── --}}
            <div class="sf-stack" style="gap:14px;" x-data="{ qty: 1, max: {{ (int) $pdMaxQty }} }">
                <div style="display:flex; flex-wrap:wrap; gap:6px;">
                    @if($product->category)
                        <a href="{{ route('storefront.index', ['category' => $product->category->slug]) }}" class="tp-pill tp-pill-soft" style="text-decoration:none; padding:7px 12px;"><i class="fas fa-layer-group"></i> {{ $product->category->name }}</a>
                    @endif
                    @if($product->brand)
                        <span class="tp-pill" style="padding:7px 12px; color:var(--ink); background:var(--surf); box-shadow:var(--raise);"><i class="fas fa-certificate"></i> {{ $product->brand }}</span>
                    @endif
                    @foreach((array) ($product->tags ?? []) as $tag)
                        @if(is_scalar($tag) && trim((string) $tag) !== '')
                            <a href="{{ route('storefront.index', ['tag' => $tag]) }}" class="tp-pill" style="text-decoration:none; padding:7px 12px; color:var(--ink2); background:var(--surf); box-shadow:var(--inset-sm);">#{{ $tag }}</a>
                        @endif
                    @endforeach
                </div>

                <h1 class="sf-h1">{{ $product->name }}</h1>

                <div class="sf-meta" style="font-size:13px; gap:12px;">
                    <span><span class="sf-stars">{{ $pdStars($pdRating) }}</span> <strong style="color:var(--ink);">{{ number_format($pdRating, 1) }}</strong> ({{ number_format((int) $product->rating_count) }} รีวิว)</span>
                    <span>ขายแล้ว <strong style="color:var(--ink);">{{ number_format((int) $product->sales_count) }}</strong></span>
                    <span><i class="fas fa-eye"></i> {{ number_format((int) $product->view_count) }}</span>
                </div>

                {{-- ราคา --}}
                <div style="padding:18px; border-radius:20px; background:linear-gradient(135deg, var(--a1soft), var(--a2soft)); box-shadow:var(--inset-sm);">
                    @if($pdOnSale)
                        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                            <span class="sf-compare" style="font-size:15px;">฿{{ number_format($pdCompare, 2) }}</span>
                            <span class="sf-badge sf-badge-sale">ประหยัด ฿{{ number_format($pdCompare - $pdPrice, 2) }}</span>
                        </div>
                    @endif
                    <div class="tp-num" style="font-size:clamp(30px, 5vw, 42px); font-weight:800; line-height:1.15; color:var(--deep1); margin-top:4px;">฿{{ number_format($pdPrice, 2) }}</div>

                    @if($pdShipping)
                        <div style="display:flex; align-items:center; gap:10px; margin-top:12px; padding:10px 12px; border-radius:14px; background:var(--card-bg); box-shadow:var(--raise);">
                            <span class="tp-tile" style="width:36px; height:36px; font-size:15px;"><i class="fas {{ ($pdShipping['is_free'] ?? false) ? 'fa-truck-fast' : 'fa-truck' }}"></i></span>
                            <span style="min-width:0;">
                                <span style="display:block; font-weight:700; font-size:13.5px; color:var(--ink);">{{ $pdShipping['label'] ?? '' }}</span>
                                <span class="tp-muted" style="display:block; font-size:12px;">{{ $pdShipping['details'] ?? '' }}</span>
                            </span>
                        </div>
                    @endif

                    @if($pdCashback && ($pdCashback['cashback'] ?? 0) > 0)
                        <div class="sf-note sf-note-ok" style="margin-top:10px; font-weight:700;">
                            <i class="fas fa-coins"></i> รับเงินคืนเข้ากระเป๋า ฿{{ number_format((float) $pdCashback['cashback'], 2) }}
                            @if(($pdCashback['setting'] ?? null) && ($pdCashback['setting']->value_type ?? null) === 'percentage')
                                <span class="tp-muted" style="font-weight:600;">({{ rtrim(rtrim(number_format((float) $pdCashback['setting']->value, 2), '0'), '.') }}% ของราคาสินค้า)</span>
                            @endif
                        </div>
                    @endif
                </div>

                @if($product->short_description)
                    <div class="sf-prose" style="font-size:14px;">{!! nl2br(e($product->short_description)) !!}</div>
                @endif

                <div style="display:flex; flex-wrap:wrap; gap:8px; align-items:center;">
                    @if($pdAffiliate)
                        <span class="tp-pill tp-pill-soft" style="padding:7px 12px;"><i class="fas fa-store"></i> ขายและจัดส่งโดย {{ $pdPlatformLabel }}</span>
                    @elseif($pdInStock)
                        <span class="tp-pill" style="padding:7px 12px; color:var(--on-accent, #fff); background:linear-gradient(135deg, var(--sf-ok, #4f9e7e), var(--sf-ok2, #3b8467));"><i class="fas fa-circle-check"></i> มีสินค้าพร้อมส่ง</span>
                    @else
                        <span class="tp-pill" style="padding:7px 12px; color:var(--on-accent, #fff); background:linear-gradient(135deg, var(--sf-sale, #e0564f), var(--sf-sale2, #c43d63));"><i class="fas fa-ban"></i> {{ $product->purchaseBlockReason() ?? 'สินค้าหมด' }}</span>
                    @endif
                    @if($product->sku)
                        <span class="tp-muted" style="font-size:12px;">SKU: <span class="tp-num" style="color:var(--ink); font-weight:700;">{{ $product->sku }}</span></span>
                    @endif
                    @if($pdStore && $pdStore->canUseRiderDelivery())
                        <span class="tp-pill tp-pill-soft" style="padding:7px 12px;"><i class="fas fa-motorcycle"></i> ส่งด่วนด้วยไรเดอร์ได้</span>
                    @endif
                </div>

                {{-- ปุ่มซื้อ --}}
                @if($pdAffiliate)
                    <a href="{{ $pdOutbound }}" target="_blank" rel="noopener nofollow sponsored" class="sf-btn3d is-block" style="min-height:54px; font-size:15.5px; background:{{ $pdAffBg }};">
                        <i class="fas fa-arrow-up-right-from-square"></i> ซื้อที่ {{ $pdPlatformLabel }}
                    </a>
                    <p class="tp-muted" style="margin:0; font-size:12.5px; text-align:center; line-height:1.6;">
                        เปิดหน้า {{ $pdPlatformLabel }} ในแท็บใหม่ · ชำระเงินและจัดส่งโดย {{ $pdPlatformLabel }}
                        @if($pdPoints > 0)
                            <br>ซื้อผ่านลิงก์นี้เพื่อรับคะแนนสะสม {{ rtrim(rtrim(number_format($pdPoints, 2), '0'), '.') }} คะแนน
                        @endif
                    </p>
                @elseif($pdInStock)
                    <div style="display:flex; align-items:center; gap:14px; flex-wrap:wrap;">
                        <span style="font-weight:700; color:var(--ink);">จำนวน</span>
                        <div class="sf-qty" role="group" aria-label="จำนวนสินค้า">
                            <button type="button" @click="qty = Math.max(1, qty - 1)" aria-label="ลดจำนวน">−</button>
                            <input type="number" x-model.number="qty" min="1" :max="max" inputmode="numeric" aria-label="จำนวน"
                                   @change="qty = Math.min(max, Math.max(1, parseInt(qty) || 1))">
                            <button type="button" @click="qty = Math.min(max, qty + 1)" aria-label="เพิ่มจำนวน">+</button>
                        </div>
                        @if($product->track_inventory)
                            <span class="tp-muted" style="font-size:12.5px;">คงเหลือ {{ number_format((int) $product->stock_quantity) }} ชิ้น</span>
                        @endif
                    </div>
                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(170px, 1fr)); gap:10px;">
                        <button type="button" class="sf-btn3d is-soft" style="min-height:52px;"
                                @click="window.tpShop.addToCart({{ (int) $product->id }}, qty, { button: $el })">
                            <i class="fas fa-cart-plus"></i> เพิ่มลงตะกร้า
                        </button>
                        <button type="button" class="sf-btn3d" style="min-height:52px;"
                                @click="window.tpShop.addToCart({{ (int) $product->id }}, qty, { button: $el, redirect: @js(route('checkout.index')) })">
                            <i class="fas fa-bolt"></i> ซื้อทันที
                        </button>
                    </div>
                @else
                    <button type="button" class="sf-btn3d is-block is-disabled" disabled><i class="fas fa-ban"></i> สั่งซื้อไม่ได้ในขณะนี้</button>
                @endif

                {{-- ซื้อด้วย Coins (ร้านค้าทางการ) --}}
                @if($pdCoin && (int) ($pdCoin['price'] ?? 0) > 0 && $pdInStock)
                    <div style="padding:14px; border-radius:18px; background:linear-gradient(135deg, var(--a2soft), var(--a1soft)); box-shadow:var(--raise);">
                        <div style="display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap; align-items:center;">
                            <strong style="color:var(--ink);"><i class="fas fa-coins" style="color:var(--deep2);"></i> ซื้อด้วย Coins</strong>
                            <span class="tp-num" style="font-weight:800; color:var(--deep2);">{{ number_format((int) $pdCoin['price']) }} Coins / ชิ้น</span>
                        </div>
                        @auth
                            <div class="tp-muted" style="font-size:12.5px; margin-top:4px;">Coins ของคุณ <span class="tp-num" style="font-weight:800; color:var(--ink);">{{ number_format((int) ($pdCoin['balance'] ?? 0)) }}</span> · รวม <span class="tp-num" style="font-weight:800; color:var(--ink);" x-text="({{ (int) $pdCoin['price'] }} * qty).toLocaleString('th-TH')"></span> Coins</div>
                            <form method="POST" action="{{ $pdCoin['action'] }}" style="margin:10px 0 0;" x-data="{ busy: false }"
                                  @submit="if (busy || !confirm(@js('ยืนยันซื้อด้วย Coins?'))) { $event.preventDefault(); return; } busy = true">
                                @csrf
                                <input type="hidden" name="quantity" :value="qty">
                                <button type="submit" class="sf-btn3d is-alt is-block" :disabled="busy || {{ (int) ($pdCoin['balance'] ?? 0) }} < ({{ (int) $pdCoin['price'] }} * qty)"
                                        :class="{{ (int) ($pdCoin['balance'] ?? 0) }} < ({{ (int) $pdCoin['price'] }} * qty) && 'is-disabled'">
                                    <i class="fas" :class="busy ? 'fa-spinner fa-spin' : 'fa-coins'"></i> ซื้อด้วย Coins
                                </button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" class="sf-btn3d is-alt is-block" style="margin-top:10px;"><i class="fas fa-right-to-bracket"></i> เข้าสู่ระบบเพื่อซื้อด้วย Coins</a>
                        @endauth
                    </div>
                @endif

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                    <button type="button" class="tp-btn {{ $pdFav ? 'is-on' : '' }}" style="height:46px;"
                            data-favorited="{{ $pdFav ? '1' : '0' }}"
                            onclick="window.tpShop.toggleFavorite({{ (int) $product->id }}, this)">
                        <i class="{{ $pdFav ? 'fas' : 'far' }} fa-heart" style="color:var(--sf-sale, #e0564f);"></i> บันทึก
                    </button>
                    <button type="button" class="tp-btn" style="height:46px;"
                            @click="if (navigator.share) { navigator.share({ title: @js($product->name), url: window.location.href }).catch(() => {}); } else if (navigator.clipboard) { navigator.clipboard.writeText(window.location.href).then(() => window.tpShop.notify('คัดลอกลิงก์แล้ว', 'success')); }">
                        <i class="fas fa-share-nodes"></i> แชร์
                    </button>
                </div>

                {{-- ร้านที่ขาย --}}
                @if($pdStore && $pdStoreHref)
                    @php $pdStoreLogo = \App\Services\Shop\ShopPresenter::imageUrl($pdStore->store_logo); @endphp
                    <a href="{{ $pdStoreHref }}" style="display:flex; align-items:center; gap:12px; padding:12px 14px; border-radius:18px; text-decoration:none; background:var(--surf); box-shadow:var(--raise);">
                        <span style="width:48px; height:48px; flex:none; border-radius:14px; overflow:hidden; display:grid; place-items:center; font-size:20px; background:var(--bg); box-shadow:var(--inset-sm);">
                            @if($pdStoreLogo)
                                <img src="{{ $pdStoreLogo }}" alt="" style="width:100%; height:100%; object-fit:cover;" onerror="this.style.display='none';">
                            @else
                                🏪
                            @endif
                        </span>
                        <span style="min-width:0; flex:1;">
                            <span class="tp-muted" style="display:block; font-size:11.5px;">ขายโดย</span>
                            <span style="display:block; font-weight:800; color:var(--ink); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $pdStore->store_name }}
                                @if($pdStore->is_verified)<i class="fas fa-circle-check" style="color:var(--deep1); font-size:12px;"></i>@endif
                            </span>
                        </span>
                        <span class="tp-btn tp-btn-sm">ดูร้าน</span>
                    </a>
                @elseif($product->seller_id && $product->isOfficialShopProduct())
                    <a href="{{ route('official-shop.index') }}" style="display:flex; align-items:center; gap:12px; padding:12px 14px; border-radius:18px; text-decoration:none; background:var(--surf); box-shadow:var(--raise);">
                        <span class="tp-tile" style="width:48px; height:48px; border-radius:14px; font-size:20px;"><i class="fas fa-crown"></i></span>
                        <span style="flex:1;">
                            <span class="tp-muted" style="display:block; font-size:11.5px;">ขายโดย</span>
                            <span style="display:block; font-weight:800; color:var(--ink);">ร้านค้าทางการ ไทยพร๊อมท์</span>
                        </span>
                        <span class="tp-btn tp-btn-sm">ดูร้าน</span>
                    </a>
                @endif

                <div style="display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:8px; text-align:center;">
                    @foreach([['fa-shield-halved', 'ชำระเงินปลอดภัย'], ['fa-truck-fast', 'ติดตามพัสดุได้'], ['fa-rotate-left', 'ยกเลิกก่อนส่ง คืนเงินเข้ากระเป๋า']] as $pdTrust)
                        <div style="padding:10px 6px; border-radius:14px; background:var(--surf); box-shadow:var(--inset-sm);">
                            <i class="fas {{ $pdTrust[0] }}" style="color:var(--deep1); font-size:18px;"></i>
                            <div style="font-size:11px; font-weight:700; color:var(--ink); margin-top:5px; line-height:1.35;">{{ $pdTrust[1] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ── แท็บรายละเอียด / รีวิว / การจัดส่ง ── --}}
<section class="sf-wrap sf-section" x-data="{ tab: 'description' }">
    <div class="sf-tabs" role="tablist" aria-label="ข้อมูลสินค้า">
        <button type="button" role="tab" class="sf-tab" :class="tab === 'description' && 'is-on'" :aria-selected="(tab === 'description').toString()" @click="tab = 'description'"><i class="fas fa-file-lines"></i> รายละเอียด</button>
        <button type="button" role="tab" class="sf-tab" :class="tab === 'reviews' && 'is-on'" :aria-selected="(tab === 'reviews').toString()" @click="tab = 'reviews'"><i class="fas fa-star"></i> รีวิว ({{ number_format($pdReviews->count()) }})</button>
        <button type="button" role="tab" class="sf-tab" :class="tab === 'shipping' && 'is-on'" :aria-selected="(tab === 'shipping').toString()" @click="tab = 'shipping'"><i class="fas fa-truck"></i> การจัดส่ง</button>
    </div>

    <div class="tp-card" style="margin-top:14px; padding:clamp(16px, 3vw, 28px);">
        <div x-show="tab === 'description'" role="tabpanel">
            @if($pdDescription !== '')
                <div class="sf-prose">{!! $pdDescription !!}</div>
            @else
                <p class="tp-muted" style="margin:0;">ผู้ขายยังไม่ได้ใส่รายละเอียดสินค้า</p>
            @endif
            @if($product->brand || $product->weight || $product->dimensions)
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:10px; margin-top:18px;">
                    @if($product->brand)
                        <div class="sf-note sf-note-info"><strong>ยี่ห้อ</strong><br>{{ $product->brand }}</div>
                    @endif
                    @if($product->weight)
                        <div class="sf-note sf-note-info"><strong>น้ำหนัก</strong><br>{{ $product->weight }} กก.</div>
                    @endif
                    @if($product->dimensions)
                        <div class="sf-note sf-note-info"><strong>ขนาด</strong><br>{{ is_array($product->dimensions) ? implode(' × ', array_filter($product->dimensions, 'is_scalar')) : $product->dimensions }}</div>
                    @endif
                </div>
            @endif
        </div>

        <div x-show="tab === 'reviews'" x-cloak role="tabpanel">
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:18px; align-items:center; margin-bottom:18px;">
                <div style="text-align:center;">
                    <div class="tp-num" style="font-size:44px; font-weight:800; color:var(--ink); line-height:1;">{{ number_format($pdRating, 1) }}</div>
                    <div class="sf-stars" style="font-size:20px; margin-top:4px;">{{ $pdStars($pdRating) }}</div>
                    <div class="tp-muted" style="font-size:12.5px;">จาก {{ number_format($pdReviews->count()) }} รีวิว</div>
                </div>
                <div style="display:flex; flex-direction:column; gap:6px;">
                    @foreach($pdDist as $pdStarKey => $pdCnt)
                        <div style="display:flex; align-items:center; gap:8px; font-size:12px; color:var(--ink2);">
                            <span class="tp-num" style="width:26px;">{{ $pdStarKey }}★</span>
                            <span style="flex:1; height:8px; border-radius:8px; background:var(--surf); box-shadow:var(--inset-sm); overflow:hidden;">
                                <span style="display:block; height:100%; width:{{ round($pdCnt / $pdReviewTotal * 100) }}%; background:linear-gradient(90deg, var(--accent1), var(--accent2));"></span>
                            </span>
                            <span class="tp-num" style="width:26px; text-align:right;">{{ $pdCnt }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            @forelse($pdReviews as $review)
                <article style="display:flex; gap:12px; padding:14px 0; border-top:1px solid color-mix(in srgb, var(--ink2) 18%, transparent);">
                    <span class="tp-tile" style="width:42px; height:42px; border-radius:14px; font-weight:800;">{{ mb_substr((string) ($review->user->name ?? 'ผ'), 0, 1) }}</span>
                    <div style="min-width:0; flex:1;">
                        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:8px;">
                            <strong style="color:var(--ink);">{{ $review->user->name ?? 'ผู้ใช้' }}</strong>
                            <span class="sf-stars">{{ $pdStars((float) $review->rating) }}</span>
                            <span class="tp-muted" style="font-size:12px;">{{ optional($review->created_at)->diffForHumans() }}</span>
                            @if($review->is_verified_purchase)
                                <span class="tp-pill tp-pill-soft"><i class="fas fa-circle-check"></i> ซื้อจริง</span>
                            @endif
                        </div>
                        @if($review->title)
                            <div style="font-weight:700; color:var(--ink); margin-top:6px;">{{ $review->title }}</div>
                        @endif
                        @if($review->comment)
                            <p style="margin:4px 0 0; color:var(--ink); line-height:1.7; font-size:14px; overflow-wrap:anywhere;">{{ $review->comment }}</p>
                        @endif
                        @if(is_array($review->images) && count($review->images) > 0)
                            <div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:10px;">
                                @foreach($review->images as $reviewImage)
                                    @if(is_string($reviewImage))
                                        <img src="{{ \App\Services\Shop\ShopPresenter::imageUrl($reviewImage) }}" alt="รูปรีวิว" loading="lazy" style="width:78px; height:78px; object-fit:cover; border-radius:12px; box-shadow:var(--raise);">
                                    @endif
                                @endforeach
                            </div>
                        @endif
                        @if($review->seller_response)
                            <div class="sf-note sf-note-info" style="margin-top:10px;">
                                <strong><i class="fas fa-store"></i> ร้านตอบกลับ</strong>
                                @if($review->seller_responded_at)
                                    <span class="tp-muted" style="font-size:11.5px;">· {{ $review->seller_responded_at->diffForHumans() }}</span>
                                @endif
                                <div style="margin-top:4px;">{{ $review->seller_response }}</div>
                            </div>
                        @endif
                    </div>
                </article>
            @empty
                <div style="text-align:center; padding:26px 10px;">
                    <div style="font-size:40px;" aria-hidden="true">💬</div>
                    <p class="tp-muted" style="margin:8px 0 0;">ยังไม่มีรีวิวสำหรับสินค้านี้</p>
                </div>
            @endforelse
        </div>

        <div x-show="tab === 'shipping'" x-cloak role="tabpanel">
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:12px;">
                @if($pdAffiliate)
                    <div class="sf-note sf-note-info"><strong><i class="fas fa-store"></i> จัดส่งโดย {{ $pdPlatformLabel }}</strong><br>ติดตามพัสดุได้ที่แอปหรือเว็บ {{ $pdPlatformLabel }}</div>
                @else
                    <div class="sf-note sf-note-info"><strong><i class="fas fa-truck-fast"></i> ส่งพัสดุทั่วไทย</strong><br>ส่งฟรีเมื่อซื้อครบ ฿{{ number_format(\App\Services\ShippingService::DEFAULT_FREE_SHIPPING_THRESHOLD) }} (ตามเงื่อนไขร้าน)</div>
                    @if($pdStore && $pdStore->canUseRiderDelivery())
                        <div class="sf-note sf-note-ok"><strong><i class="fas fa-motorcycle"></i> ส่งด่วนด้วยไรเดอร์</strong><br>เลือกได้ตอนชำระเงิน ค่าส่งตามระยะทางจริง ติดตามไรเดอร์ได้สด</div>
                    @endif
                    <div class="sf-note sf-note-info"><strong><i class="fas fa-location-crosshairs"></i> ติดตามคำสั่งซื้อ</strong><br>ดูสถานะและเลขพัสดุได้ที่หน้า "คำสั่งซื้อของฉัน"</div>
                @endif
            </div>
        </div>
    </div>
</section>
