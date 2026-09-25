{{--
 | หน้าแรกตลาดสดไทยพร้อม (taladsod.home) — ธีม V4 (frontend-v4)
 | Controller: FreshMarket\HomeController@index
 | ตัวแปร: $settings, $categories, $featuredListings, $latestListings, $topSellers, $serviceProviders,
 |         $banners (MobileBanner::toAppApi + href), $openShops (shopCard + url), $gpFree (bool), $nearbyShopsUrl
 | "เปิดอยู่ใกล้คุณ": GET taladsod.api.nearby-shops?lat&lng&radius → {data:{shops[], count, radius_km}}
 --}}
@extends('layouts.frontend-v4')

@section('title', 'ตลาดสดไทยพร้อม — ของสด อาหาร รถเข็นใกล้บ้าน ส่งถึงมือ')
@section('meta_description', 'ตลาดสดไทยพร้อม สั่งของสด อาหารร้อนๆ จากร้านและรถเข็นใกล้บ้าน ดูร้านที่เปิดอยู่ตอนนี้บนแผนที่ ไรเดอร์ส่งถึงมือ หรือไปรับเองที่ร้าน')

@php
    $ui = \App\Support\TaladsodWebUi::class;
    $brand = $settings->brand_name ?? 'ตลาดสดไทยพร้อม';

    // แบนเนอร์จากแอดมิน — ไม่มีเลยใช้ภาพเปิดตัว (ภาพไม่มีตัวหนังสือ เว็บวางข้อความทับ)
    $slides = collect($banners ?? [])->map(fn ($b) => [
        'title' => $b['title'] ?? $brand,
        'subtitle' => $b['subtitle'] ?? null,
        'image' => $b['image_url'],
        'cta' => $b['cta_label'] ?? 'ดูเลย',
        'href' => $b['href'] ?? route('taladsod.home'),
    ])->values()->all();

    if ($slides === []) {
        $slides[] = [
            'title' => 'ตลาดสดใกล้บ้าน ส่งถึงมือ',
            'subtitle' => 'ของสด อาหารร้อนๆ จากร้านและรถเข็นในชุมชน ดูร้านที่เปิดอยู่ตอนนี้แล้วสั่งได้เลย',
            'image' => asset('images/taladsod/banner-market.webp'),
            'cta' => 'หาร้านใกล้ฉัน',
            'href' => '#near-me',
        ];
    }

    $nearCfg = [
        'url' => $nearbyShopsUrl,
        'followedUrl' => auth()->check() ? route('taladsod.followed-shops') : null,
        'shopBaseUrl' => route('taladsod.seller', ['id' => 0]),
    ];
@endphp

@section('content')
<x-theme-v4.shop-kit />
@include('taladsod.partials.kit')
<x-theme-v4.leaflet />
<x-theme-v4.public-header active="taladsod" :search="true" :search-action="route('taladsod.search')" search-name="q"
                          search-placeholder="ค้นหาของสด อาหาร หรือร้านใกล้บ้าน..." :suggest="false" />

<main class="ts-scope" style="flex:1; padding-bottom:44px;">
    <div class="sf-wrap">
        @include('taladsod.partials.nav', ['active' => 'home'])

        {{-- ════════ แบนเนอร์แคมเปญ ════════ --}}
        {{-- ts-hero-stack: ทุกสไลด์ซ้อนอยู่ช่องเดียวกัน (grid) → ตอนเปลี่ยนสไลด์ ความสูงไม่กระโดด
             หยุดเลื่อนเฉพาะตอนเมาส์ชี้ (จอสัมผัสไม่หยุดค้าง) --}}
        <section aria-label="แบนเนอร์แคมเปญ" class="ts-hero-stack" style="padding-top:6px; position:relative;"
                 x-data="tsHeroSlider({{ count($slides) }})"
                 x-on:pointerenter="if ($event.pointerType === 'mouse') { hover = true; stop(); }"
                 x-on:pointerleave="if ($event.pointerType === 'mouse') { hover = false; start(); }"
                 x-on:touchstart.passive="sx = $event.touches[0].clientX" x-on:touchend="swipe($event.changedTouches[0].clientX)">
            @foreach($slides as $i => $slide)
                <div class="ts-hero" x-show="i === {{ $i }}" @if($i > 0) x-cloak @endif x-transition.opacity.duration.500ms>
                    <img class="ts-hero-img" src="{{ $slide['image'] }}" alt="" @if($i > 0) loading="lazy" @endif>
                    <div class="ts-hero-in">
                        <span class="ts-pill solid ts-tone-gold" style="align-self:flex-start;"><i class="fas fa-carrot" aria-hidden="true"></i> {{ $brand }}</span>
                        <h1 class="ts-hero-title">{{ $slide['title'] }}</h1>
                        @if($slide['subtitle'])
                            <p class="ts-hero-sub">{{ $slide['subtitle'] }}</p>
                        @endif
                        <div class="ts-row" style="margin-top:6px;">
                            <a href="{{ $slide['href'] }}" class="ts-btn3d ts-tone-gold"><i class="fas fa-arrow-right" aria-hidden="true"></i> {{ $slide['cta'] }}</a>
                            @if($i === 0)
                                <a href="#near-me" class="ts-btn3d soft sf-hide-sm"><i class="fas fa-location-crosshairs" aria-hidden="true"></i> ร้านที่เปิดอยู่ใกล้ฉัน</a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
            @if(count($slides) > 1)
                <div class="ts-hero-dots" aria-label="เลือกแบนเนอร์">
                    @foreach($slides as $i => $slide)
                        <button type="button" x-on:click="go({{ $i }})" :class="i === {{ $i }} ? 'is-on' : ''" aria-label="แบนเนอร์ที่ {{ $i + 1 }}"></button>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- ════════ หมวดหมู่ ════════ --}}
        @if($categories->count() > 0)
            <section class="sf-section" aria-labelledby="ts-cats-h" style="padding-top:22px;">
                <div class="sf-section-h" style="margin-bottom:10px;">
                    <h2 id="ts-cats-h" class="ts-h2"><i class="fas fa-layer-group" style="color:var(--accent2);" aria-hidden="true"></i> เลือกตามหมวด</h2>
                    <a href="{{ route('taladsod.search') }}" class="ts-link ts-small">ค้นหาทั้งหมด <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                </div>
                <div class="sf-scroll">
                    @foreach($categories as $category)
                        <a href="{{ route('taladsod.category', $category->slug) }}" class="ts-cat">
                            <span class="ts-cat-ic">
                                @if($category->image_url)
                                    <img src="{{ $category->image_url }}" alt="" loading="lazy">
                                @else
                                    {{ $category->icon ?: '🛒' }}
                                @endif
                            </span>
                            <span>{{ $category->name }}</span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ════════ เปิดอยู่ใกล้คุณ (แผนที่ + รายการร้าน) ════════ --}}
        <section id="near-me" class="sf-section" aria-labelledby="ts-near-h" x-data="tsNearby({{ \Illuminate\Support\Js::from($nearCfg) }})" style="scroll-margin-top:90px;">
            <div class="sf-section-h">
                <div>
                    <div class="sf-kicker">ร้าน · รถเข็น · ตลาดนัด</div>
                    <h2 id="ts-near-h" class="sf-title">เปิดอยู่ใกล้คุณ</h2>
                </div>
                <div class="ts-row" style="gap:8px;">
                    <template x-for="r in [2, 5, 10, 20]" :key="r">
                        <button type="button" class="sf-chip" :class="radius === r ? 'is-on' : ''" x-on:click="setRadius(r)" x-text="r + ' กม.'"></button>
                    </template>
                </div>
            </div>

            <div class="tp-card" style="padding:14px;">
                <div class="ts-grid" style="--ts-min:320px; align-items:start;">
                    {{-- แผนที่ --}}
                    <div>
                        <div class="ts-map" x-ref="map" x-show="located" x-cloak aria-label="แผนที่ร้านที่เปิดอยู่ใกล้คุณ"></div>
                        <div class="ts-map ts-map-empty" x-show="!located" style="background:linear-gradient(160deg, var(--a1soft), var(--surf));">
                            <div class="ts-stack" style="align-items:center; max-width:320px;">
                                <span class="tp-tile" style="width:64px; height:64px; border-radius:22px; font-size:26px;"><i class="fas fa-map-location-dot" aria-hidden="true"></i></span>
                                <b style="font-size:15px; color:var(--ink);">ดูร้านที่เปิดอยู่รอบตัวคุณบนแผนที่</b>
                                <span>รวมรถเข็นและร้านตลาดนัดที่เปิดวันนี้ — ตำแหน่งของคุณใช้ค้นหาเท่านั้น ไม่ถูกบันทึก</span>
                                <button type="button" class="ts-btn3d ts-tone-gold" x-on:click="locate()" :disabled="busy">
                                    <i class="fas" :class="busy ? 'fa-circle-notch ts-spin' : 'fa-location-crosshairs'" aria-hidden="true"></i>
                                    <span x-text="busy ? 'กำลังหาตำแหน่ง...' : 'แชร์ตำแหน่งเพื่อค้นหา'">แชร์ตำแหน่งเพื่อค้นหา</span>
                                </button>
                                <span class="ts-err" x-show="error" x-text="error" x-cloak></span>
                            </div>
                        </div>
                    </div>

                    {{-- รายการร้าน --}}
                    <div class="ts-stack" style="gap:10px;">
                        {{-- ก่อนแชร์ตำแหน่ง: ร้านที่เปิดอยู่ตอนนี้ --}}
                        <div class="ts-stack" style="gap:10px;" x-show="!located">
                            <div class="ts-row" style="justify-content:space-between;">
                                <b style="font-size:14px;"><span class="ts-dot live ts-tone-ok" style="display:inline-block; margin-right:6px;"></span>เปิดอยู่ตอนนี้</b>
                                <span class="ts-muted ts-small">{{ count($openShops) }} ร้าน</span>
                            </div>
                            @forelse($openShops as $shop)
                                @include('taladsod.partials.shop-card', ['shop' => $shop])
                            @empty
                                <div class="ts-empty tp-inset" style="border-radius:18px;">
                                    <span class="em" aria-hidden="true">🌙</span>
                                    <b>ยังไม่มีร้านเปิดตอนนี้</b>
                                    <span class="ts-muted ts-small">ติดตามร้านที่ชอบ แล้วเราจะแจ้งเตือนเมื่อร้านเปิด</span>
                                </div>
                            @endforelse
                        </div>

                        {{-- หลังแชร์ตำแหน่ง: ร้านใกล้คุณเรียงตามระยะ --}}
                        <div class="ts-stack" style="gap:10px;" x-show="located" x-cloak>
                            <div class="ts-row" style="justify-content:space-between;">
                                <b style="font-size:14px;" x-text="shops.length > 0 ? ('พบ ' + shops.length + ' ร้านในรัศมี ' + radius + ' กม.') : ('ยังไม่มีร้านเปิดในรัศมี ' + radius + ' กม.')"></b>
                                <button type="button" class="tp-btn tp-btn-sm" x-on:click="locate()" :disabled="busy">
                                    <i class="fas fa-rotate" :class="busy ? 'ts-spin' : ''" aria-hidden="true"></i> อัปเดต
                                </button>
                            </div>
                            <template x-for="shop in shops" :key="shop.id">
                                <a :href="shop.url" class="ts-shop" x-on:mouseenter="focusShop(shop)">
                                    <span class="ts-avatar">
                                        <template x-if="shop.shop_image"><img :src="shop.shop_image" alt="" loading="lazy"></template>
                                        <template x-if="!shop.shop_image"><span x-text="(shop.shop_name || 'ร').substring(0, 1)"></span></template>
                                    </span>
                                    <span style="flex:1; min-width:0; display:flex; flex-direction:column; gap:6px;">
                                        <b style="font-size:14.5px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" x-text="shop.shop_name"></b>
                                        <span class="ts-row" style="gap:6px;">
                                            <span class="ts-pill ts-tone-ok"><span class="ts-dot" :class="shop.location && shop.location.is_live ? 'live' : ''"></span> <span x-text="shop.location && shop.location.is_live ? 'เปิดอยู่ · ตำแหน่งสด' : 'เปิดอยู่'"></span></span>
                                            <span class="ts-pill ts-tone-deep" x-show="shop.is_mobile"><i class="fas fa-cart-flatbed" aria-hidden="true"></i> รถเข็น/ตลาดนัด</span>
                                            <span class="ts-pill ts-tone-info"><i class="fas fa-location-dot" aria-hidden="true"></i> <span x-text="window.ts.distance(shop.distance_km)"></span></span>
                                        </span>
                                        <span class="ts-muted ts-small" x-show="shop.location && shop.location.label" style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                            <i class="fas fa-map-pin" aria-hidden="true"></i> <span x-text="shop.location ? shop.location.label : ''"></span>
                                        </span>
                                        <span class="ts-thumbs" x-show="(shop.top_items || []).length > 0" aria-hidden="true">
                                            <template x-for="item in (shop.top_items || [])" :key="item.id">
                                                <img :src="item.image_url || ''" alt="" loading="lazy" x-show="item.image_url">
                                            </template>
                                        </span>
                                    </span>
                                </a>
                            </template>
                            <div class="ts-empty tp-inset" style="border-radius:18px;" x-show="shops.length === 0">
                                <span class="em" aria-hidden="true">🧭</span>
                                <b>ยังไม่มีร้านเปิดใกล้คุณ</b>
                                <span class="ts-muted ts-small">ลองขยายรัศมีค้นหา หรือดูสินค้าทั้งหมดด้านล่าง</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ร้านที่ติดตาม (เฉพาะผู้ที่ login) --}}
            <div x-show="followed.length > 0" x-cloak style="margin-top:16px;">
                <b style="display:block; font-size:14px; margin-bottom:8px;"><i class="fas fa-heart" style="color:var(--ts-bad);" aria-hidden="true"></i> ร้านที่คุณติดตาม</b>
                <div class="sf-scroll">
                    <template x-for="shop in followed" :key="'f' + shop.id">
                        <a :href="shop.url" class="sf-chip" style="min-height:44px;">
                            <span class="ts-dot" :class="shop.is_open ? 'ts-tone-ok live' : 'ts-tone-muted'"></span>
                            <span x-text="shop.shop_name"></span>
                            <span class="ts-muted ts-small" x-text="shop.is_open ? 'เปิดอยู่' : 'ปิดอยู่'"></span>
                        </a>
                    </template>
                </div>
            </div>
        </section>

        {{-- ════════ ชวนเปิดร้าน / เป็นไรเดอร์ ════════ --}}
        <section class="sf-section" aria-label="ร่วมเป็นส่วนหนึ่งของตลาดสด">
            <div class="ts-grid" style="--ts-min:300px;">
                <a href="{{ route('taladsod.landing.seller') }}" class="ts-hero" style="min-height:200px; text-decoration:none;">
                    <img class="ts-hero-img" src="{{ asset('images/taladsod/banner-merchant.webp') }}" alt="" loading="lazy">
                    <div class="ts-hero-in" style="min-height:200px;">
                        @if($gpFree)
                            <span class="ts-pill solid ts-tone-ok" style="align-self:flex-start;"><i class="fas fa-gift" aria-hidden="true"></i> ฟรี GP ช่วงเปิดตัว</span>
                        @endif
                        <h2 class="ts-hero-title" style="font-size:clamp(20px,3.6vw,28px);">เปิดร้านฟรี ขายได้ทันที</h2>
                        <p class="ts-hero-sub">รถเข็น ตลาดนัด ร้านเล็ก เปิดร้านที่ไหนก็บอกลูกค้าได้ด้วยปุ่มเดียว</p>
                        <span class="ts-btn3d ts-tone-gold sm" style="align-self:flex-start;">เปิดร้านเลย <i class="fas fa-arrow-right" aria-hidden="true"></i></span>
                    </div>
                </a>
                <a href="{{ route('taladsod.landing.rider') }}" class="ts-hero" style="min-height:200px; text-decoration:none;">
                    <img class="ts-hero-img" src="{{ asset('images/taladsod/banner-rider.webp') }}" alt="" loading="lazy">
                    <div class="ts-hero-in" style="min-height:200px;">
                        <span class="ts-pill solid ts-tone-info" style="align-self:flex-start;"><i class="fas fa-motorcycle" aria-hidden="true"></i> รับสมัครไรเดอร์</span>
                        <h2 class="ts-hero-title" style="font-size:clamp(20px,3.6vw,28px);">มาเป็นไรเดอร์ รับงานใกล้บ้าน</h2>
                        <p class="ts-hero-sub">เลือกเวลาเอง ค่าส่งเข้ากระเป๋าทันทีที่ส่งสำเร็จ</p>
                        <span class="ts-btn3d ts-tone-info sm" style="align-self:flex-start;">สมัครไรเดอร์ <i class="fas fa-arrow-right" aria-hidden="true"></i></span>
                    </div>
                </a>
            </div>
        </section>

        {{-- ════════ สินค้าแนะนำ ════════ --}}
        @if($featuredListings->count() > 0)
            <section class="sf-section" aria-labelledby="ts-featured-h">
                <div class="sf-section-h">
                    <div>
                        <div class="sf-kicker">คัดมาให้</div>
                        <h2 id="ts-featured-h" class="sf-title">สินค้าแนะนำ</h2>
                    </div>
                </div>
                <div class="sf-grid">
                    @foreach($featuredListings as $listing)
                        @include('taladsod.partials.listing-card', ['listing' => $listing])
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ════════ มาใหม่ ════════ --}}
        <section class="sf-section" aria-labelledby="ts-latest-h">
            <div class="sf-section-h">
                <div>
                    <div class="sf-kicker">สดใหม่ทุกวัน</div>
                    <h2 id="ts-latest-h" class="sf-title">เมนูและของสดมาใหม่</h2>
                </div>
                <a href="{{ route('taladsod.search') }}" class="ts-link ts-small">ดูทั้งหมด <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
            </div>
            @if($latestListings->count() > 0)
                <div class="sf-grid">
                    @foreach($latestListings as $listing)
                        @include('taladsod.partials.listing-card', ['listing' => $listing])
                    @endforeach
                </div>
            @else
                <div class="tp-card ts-empty">
                    <span class="em" aria-hidden="true">🧺</span>
                    <b style="font-size:16px;">ร้านค้ากำลังทยอยลงสินค้า</b>
                    <span class="ts-muted">อยากขายของในตลาดสด? เปิดร้านฟรีได้เลยวันนี้</span>
                    <a href="{{ route('taladsod.landing.seller') }}" class="ts-btn3d ts-tone-gold sm"><i class="fas fa-store" aria-hidden="true"></i> เปิดร้านฟรี</a>
                </div>
            @endif
        </section>

        {{-- ════════ ร้านยอดนิยม ════════ --}}
        @if($topSellers->count() > 0)
            <section class="sf-section" aria-labelledby="ts-top-h">
                <div class="sf-section-h">
                    <div>
                        <div class="sf-kicker">รีวิวดี ลูกค้าประจำเยอะ</div>
                        <h2 id="ts-top-h" class="sf-title">ร้านยอดนิยม</h2>
                    </div>
                </div>
                <div class="ts-grid" style="--ts-min:280px;">
                    @foreach($topSellers as $seller)
                        @include('taladsod.partials.shop-card', ['shop' => [
                            'id' => $seller->id,
                            'shop_name' => $seller->shop_name,
                            'shop_image' => $seller->shop_image,
                            'rating_average' => (float) $seller->rating_average,
                            'rating_count' => (int) $seller->rating_count,
                            'is_verified' => (bool) $seller->is_verified,
                            'is_mobile' => $seller->isMobileShop(),
                            'is_open' => $seller->isOpenNow(),
                            'presence' => $seller->presencePayload(),
                            'url' => route('taladsod.seller', $seller->id),
                        ]])
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ════════ ช่างบริการ ════════ --}}
        @if(isset($serviceProviders) && $serviceProviders->count() > 0)
            <section class="sf-section" aria-labelledby="ts-svc-h">
                <div class="sf-section-h">
                    <div>
                        <div class="sf-kicker">ช่างใกล้บ้าน</div>
                        <h2 id="ts-svc-h" class="sf-title">ช่างบริการพร้อมรับงาน</h2>
                    </div>
                </div>
                <div class="ts-grid" style="--ts-min:230px;">
                    @foreach($serviceProviders as $provider)
                        <div class="tp-card" style="display:flex; gap:12px; align-items:center; padding:14px;">
                            <span class="ts-avatar" style="width:46px; height:46px; font-size:17px;">{{ $ui::initial(data_get($provider, 'display_name') ?: data_get($provider, 'name'), 'ช') }}</span>
                            <span style="min-width:0;">
                                <b style="display:block; font-size:14px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ data_get($provider, 'display_name') ?: data_get($provider, 'name', 'ช่างบริการ') }}</b>
                                <span class="ts-muted ts-small"><span class="ts-star-view">★</span> {{ number_format((float) data_get($provider, 'rating', 0), 1) }} · งานสำเร็จ {{ number_format((int) data_get($provider, 'total_bookings', 0)) }} งาน</span>
                            </span>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</main>

<x-theme-v4.public-footer />
@endsection

@push('scripts')
<script>
    /** แบนเนอร์หมุนอัตโนมัติ (ปัดซ้าย/ขวาบนมือถือได้) */
    window.tsHeroSlider = function (n) {
        return {
            i: 0, n: n, t: null, sx: null, hover: false,
            init() { this.start(); },
            // กดจุด/ปัดเปลี่ยนสไลด์ → นับเวลาใหม่ (ถ้าเมาส์ยังชี้อยู่ รอจนเมาส์ออกก่อน)
            go(k) { this.i = (k + this.n) % this.n; if (!this.hover) { this.start(); } },
            start() { this.stop(); if (this.n > 1) { this.t = setInterval(() => { this.i = (this.i + 1) % this.n; }, 6000); } },
            stop() { clearInterval(this.t); this.t = null; },
            swipe(x) {
                if (this.sx === null || this.n < 2) { return; }
                const dx = x - this.sx;
                this.sx = null;
                if (Math.abs(dx) > 40) { this.go(this.i + (dx < 0 ? 1 : -1)); }
            }
        };
    };

    /** ร้านที่เปิดอยู่ใกล้คุณ: ขอตำแหน่ง → ดึงร้าน → วาดหมุดบนแผนที่ (ร้านเคลื่อนที่ที่ส่งตำแหน่งสดมีวงแหวน) */
    window.tsNearby = function (cfg) {
        // วัตถุ Leaflet เก็บนอก state ของ Alpine (ไม่ให้ถูกห่อเป็น reactive proxy)
        let map = null;
        let layer = null;
        const markers = {};

        return {
            located: false, busy: false, error: '', radius: 5, lat: null, lng: null,
            shops: [], followed: [],

            async init() {
                if (cfg.followedUrl) { this.loadFollowed(); }
                // เคยอนุญาตตำแหน่งแล้ว → ค้นหาให้เลยโดยไม่ต้องกด
                if (await window.ts.geoGranted()) { this.locate(); }
            },

            async loadFollowed() {
                const r = await window.ts.get(cfg.followedUrl);
                if (r.ok && Array.isArray(r.data)) { this.followed = r.data.slice(0, 20); }
            },

            setRadius(r) {
                this.radius = r;
                if (this.lat !== null) { this.load(); } else { this.locate(); }
            },

            async locate() {
                this.busy = true;
                this.error = '';
                try {
                    const p = await window.ts.geo();
                    this.lat = p.lat;
                    this.lng = p.lng;
                    await this.load();
                } catch (e) {
                    this.error = (e && e.message) || 'หาตำแหน่งไม่ได้ กรุณาลองใหม่';
                } finally {
                    this.busy = false;
                }
            },

            async load() {
                this.busy = true;
                const r = await window.ts.get(cfg.url, { lat: this.lat, lng: this.lng, radius: this.radius });
                this.busy = false;
                if (!r.ok) {
                    this.error = r.message;
                    return;
                }
                this.shops = (r.data && Array.isArray(r.data.shops)) ? r.data.shops : [];
                this.located = true;
                this.$nextTick(() => this.draw());
            },

            draw() {
                if (!window.tpMap || !window.tpMap.ready()) { return; }
                if (!map) {
                    map = window.tpMap.create(this.$refs.map, this.lat, this.lng, 14);
                    layer = window.L.layerGroup().addTo(map);
                }
                map.invalidateSize();
                layer.clearLayers();
                Object.keys(markers).forEach((k) => delete markers[k]);
                layer.addLayer(window.L.marker([this.lat, this.lng], { icon: window.tpMap.icon('home') }));

                const bounds = [[this.lat, this.lng]];
                this.shops.forEach((shop) => {
                    if (!shop.location) { return; }
                    const live = !!shop.location.is_live;
                    const icon = window.L.divIcon({
                        className: '',
                        html: '<div class="tp-map-pin is-shop' + (live ? ' ts-pin-live' : '') + '"><i class="fas ' + (shop.is_mobile ? 'fa-cart-flatbed' : 'fa-store') + '"></i></div>',
                        iconSize: [40, 40], iconAnchor: [20, 40], popupAnchor: [0, -36]
                    });
                    const marker = window.L.marker([shop.location.latitude, shop.location.longitude], { icon: icon });
                    marker.bindPopup(this.popup(shop));
                    layer.addLayer(marker);
                    markers[shop.id] = marker;
                    bounds.push([shop.location.latitude, shop.location.longitude]);
                });

                if (bounds.length > 1) {
                    map.fitBounds(bounds, { padding: [36, 36], maxZoom: 16 });
                } else {
                    map.setView([this.lat, this.lng], 14);
                }
            },

            /** เนื้อหา popup สร้างด้วย textContent เท่านั้น (ชื่อร้านมาจากผู้ใช้) */
            popup(shop) {
                const box = document.createElement('div');
                box.className = 'ts-map-popup';
                const name = document.createElement('b');
                name.textContent = shop.shop_name || 'ร้านตลาดสด';
                const meta = document.createElement('span');
                meta.textContent = (shop.is_mobile ? 'รถเข็น/ตลาดนัด · ' : '') + window.ts.distance(shop.distance_km)
                    + (shop.location && shop.location.label ? ' · ' + shop.location.label : '');
                const link = document.createElement('a');
                link.href = shop.url;
                link.textContent = 'ดูเมนูและสั่งซื้อ →';
                box.append(name, meta, link);
                return box;
            },

            focusShop(shop) {
                if (map && markers[shop.id] && window.matchMedia('(hover: hover)').matches) {
                    markers[shop.id].openPopup();
                }
            }
        };
    };
</script>
@endpush
