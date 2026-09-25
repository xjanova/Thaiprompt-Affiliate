{{--
 | หน้าร้านค้าหลัก (Storefront) — ธีม V4 (frontend-v4)
 | ข้อมูลจาก StorefrontController@index:
 |   $banners, $categories, $flashDeals, $flashDealEndTime, $flashDealCheckedAt, $featuredStores,
 |   $products (paginator), $stats, $browseMode ('home'|'browse'), $activeCategory, $categoryCover
 | โหลดสินค้าเพิ่ม: กดปุ่มเท่านั้น (GET storefront.products ตอบ JSON) — ห้ามโหลดอัตโนมัติตอนเลื่อน (เจ้าของสั่งไว้)
 --}}
@extends('layouts.frontend-v4')

@section('title', 'ร้านค้าออนไลน์ - สินค้าคุณภาพ ราคาดี ส่งทั่วไทย')
@section('meta_description', 'ช้อปสินค้าคุณภาพหลากหลายหมวดหมู่ ราคาพิเศษ ส่งทั่วประเทศ ส่งด่วนด้วยไรเดอร์ในพื้นที่ ร้านค้าทางการและร้านค้าคุณภาพ')

@php
    // ค่าตัวกรองจาก query string — บังคับเป็น string เสมอ (กัน ?q[]=x)
    $sfSearch = is_scalar(request('search')) ? (string) request('search') : (is_scalar(request('q')) ? (string) request('q') : '');
    $sfShopType = is_scalar(request('shop_type')) ? (string) request('shop_type') : 'all';
    $sfSortBy = is_scalar(request('sort_by')) ? (string) request('sort_by') : 'newest';
    $sfTag = is_scalar(request('tag')) ? (string) request('tag') : '';
    $sfCategory = is_scalar(request('category')) ? (string) request('category') : '';

    // รายการโปรดของผู้ใช้ (query เดียวต่อหน้า)
    $sfFavIds = [];
    if (auth()->check()) {
        try {
            $sfIds = collect($products->items())->pluck('id')
                ->merge(($flashDeals ?? collect())->pluck('id'))
                ->filter()->unique()->values()->all();
            if ($sfIds !== []) {
                $sfFavIds = \App\Models\ProductFavorite::where('user_id', auth()->id())
                    ->whereIn('product_id', $sfIds)->pluck('product_id')->map(fn ($id) => (int) $id)->all();
            }
        } catch (\Throwable $e) {
            $sfFavIds = [];
        }
    }

    // ลิงก์ในแบนเนอร์มาจากแอดมิน — รับเฉพาะ http(s) หรือ path ภายใน
    $sfSafeUrl = function ($url) {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }

        return (str_starts_with($url, '/') || preg_match('#^https?://#i', $url)) ? $url : null;
    };

    $sfFreeShip = \App\Services\ShippingService::DEFAULT_FREE_SHIPPING_THRESHOLD;
    $sfSortOptions = [
        'newest' => 'ใหม่ล่าสุด',
        'popular' => 'ยอดนิยม',
        'price_low' => 'ราคาต่ำ → สูง',
        'price_high' => 'ราคาสูง → ต่ำ',
        'rating' => 'คะแนนสูงสุด',
        'discount' => 'ลดราคามากสุด',
    ];
    $sfTabs = [
        'all' => ['label' => 'ทั้งหมด', 'icon' => 'fa-border-all', 'count' => $stats['all'] ?? 0],
        'official' => ['label' => 'ร้านทางการ', 'icon' => 'fa-circle-check', 'count' => $stats['official'] ?? 0],
        'premium' => ['label' => 'เรตติ้งสูง', 'icon' => 'fa-star', 'count' => $stats['premium'] ?? 0],
    ];
    $sfTabUrl = fn (string $type) => route('storefront.index', array_filter(array_merge(request()->except(['page', 'shop_type']), $type === 'all' ? [] : ['shop_type' => $type]), fn ($v) => $v !== null && $v !== ''));
    $sfCoverService = app(\App\Services\CategoryImageService::class);
@endphp

@section('content')
<x-theme-v4.shop-kit />
<x-theme-v4.public-header active="shop" :search="true" :search-value="$sfSearch" />

<main style="flex:1; padding-bottom:40px;">

    {{-- ════════ HERO (เฉพาะหน้าแรกของร้านค้า) ════════ --}}
    @if($browseMode === 'home')
        <section class="sf-wrap" style="padding-top:22px;">
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(min(100%, 300px), 1fr)); gap:16px; align-items:stretch;">
                {{-- แบนเนอร์หมุน --}}
                <div style="grid-column:1 / -1; min-width:0;"
                     x-data="{ i: 0, n: {{ count($banners) }}, t: null, sx: null,
                               go(k) { this.i = (k + this.n) % this.n; },
                               start() { this.stop(); if (this.n > 1) { this.t = setInterval(() => this.go(this.i + 1), 6000); } },
                               stop() { clearInterval(this.t); this.t = null; },
                               swipeEnd(x) { if (this.sx !== null && Math.abs(x - this.sx) > 40) { this.go(this.i + (x < this.sx ? 1 : -1)); } this.sx = null; this.start(); } }"
                     x-init="start()" @mouseenter="stop()" @mouseleave="start()"
                     @touchstart.passive="sx = $event.touches[0].clientX; stop()" @touchend="swipeEnd($event.changedTouches[0].clientX)">
                    <div style="position:relative; overflow:hidden; border-radius:26px; min-height:clamp(220px, 34vw, 360px); box-shadow:var(--card-shadow); background:linear-gradient(135deg, var(--accent1), var(--accent2));">
                        @foreach($banners as $bi => $banner)
                            @php
                                $bImg = \App\Services\Shop\ShopPresenter::imageUrl($banner['image'] ?? null);
                                $bCta = $sfSafeUrl($banner['cta_url'] ?? null);
                            @endphp
                            <div x-show="i === {{ $bi }}" x-transition.opacity.duration.500ms @if($bi > 0) x-cloak @endif
                                 style="position:absolute; inset:0; display:flex; align-items:center;">
                                @if($bImg)
                                    <img src="{{ $bImg }}" alt="" aria-hidden="true" @if($bi === 0) fetchpriority="high" @else loading="lazy" @endif
                                         style="position:absolute; inset:0; width:100%; height:100%; object-fit:cover;">
                                    <div aria-hidden="true" style="position:absolute; inset:0; background:linear-gradient(90deg, rgba(0,0,0,.58) 0%, rgba(0,0,0,.28) 55%, rgba(0,0,0,0) 100%);"></div>
                                @else
                                    <div aria-hidden="true" style="position:absolute; inset:0; background:radial-gradient(600px 300px at 85% 20%, rgba(255,255,255,.22), transparent 60%);"></div>
                                @endif
                                <div style="position:relative; z-index:1; padding:clamp(22px, 4vw, 46px); max-width:620px; color:var(--on-accent, #fff);">
                                    @if(! empty($banner['badge']))
                                        <span style="display:inline-flex; padding:6px 12px; border-radius:20px; font-size:12px; font-weight:800; background:rgba(255,255,255,.22); -webkit-backdrop-filter:blur(6px); backdrop-filter:blur(6px);">{{ $banner['badge'] }}</span>
                                    @endif
                                    <h1 style="margin:12px 0 8px; font-size:clamp(24px, 4.6vw, 42px); line-height:1.15; font-weight:800; letter-spacing:-.5px; text-shadow:0 2px 10px rgba(0,0,0,.25);">{{ $banner['title'] ?? '' }}</h1>
                                    @if(! empty($banner['subtitle']))
                                        <p style="margin:0 0 16px; font-size:clamp(13.5px, 1.8vw, 16px); line-height:1.6; opacity:.95;">{{ $banner['subtitle'] }}</p>
                                    @endif
                                    <div style="display:flex; align-items:center; gap:16px; flex-wrap:wrap;">
                                        @if($bCta)
                                            <a href="{{ $bCta }}" class="sf-btn3d is-soft" style="color:var(--deep1);">{{ $banner['cta_text'] ?? 'ช้อปเลย' }} <i class="fas fa-arrow-right"></i></a>
                                        @endif
                                        @if(! empty($banner['highlight']))
                                            <div>
                                                <div class="tp-num" style="font-size:clamp(22px, 3.6vw, 32px); font-weight:800; line-height:1;">{{ $banner['highlight'] }}</div>
                                                <div style="font-size:12px; opacity:.9;">{{ $banner['highlight_label'] ?? '' }}</div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        @if(count($banners) > 1)
                            <button type="button" @click="go(i - 1)" aria-label="แบนเนอร์ก่อนหน้า" class="tp-icon-btn sf-hide-sm" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); z-index:2; opacity:.9;"><i class="fas fa-chevron-left"></i></button>
                            <button type="button" @click="go(i + 1)" aria-label="แบนเนอร์ถัดไป" class="tp-icon-btn sf-hide-sm" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); z-index:2; opacity:.9;"><i class="fas fa-chevron-right"></i></button>
                            <div style="position:absolute; left:0; right:0; bottom:12px; z-index:2; display:flex; justify-content:center; gap:6px;">
                                @foreach($banners as $bi => $banner)
                                    <button type="button" @click="go({{ $bi }})" :aria-current="(i === {{ $bi }}).toString()" aria-label="แบนเนอร์ที่ {{ $bi + 1 }}"
                                            :style="i === {{ $bi }} ? 'width:26px; background:var(--on-accent, #fff);' : 'width:10px; background:rgba(255,255,255,.55);'"
                                            style="height:10px; border:0; border-radius:10px; cursor:pointer; transition:width .25s ease;"></button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                {{-- การ์ดต้อนรับ / คูปอง / สถิติ --}}
                <div class="tp-card" style="display:flex; flex-direction:column; gap:12px; justify-content:center;">
                    @auth
                        <div style="display:flex; align-items:center; gap:12px;">
                            <span class="tp-tile" style="width:48px; height:48px; border-radius:16px; font-size:20px;"><i class="fas fa-user"></i></span>
                            <div style="min-width:0;">
                                <div class="tp-muted" style="font-size:12px;">ยินดีต้อนรับกลับมา</div>
                                <div style="font-weight:800; color:var(--ink); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ auth()->user()->name }}</div>
                            </div>
                        </div>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
                            <a href="{{ route('orders.index') }}" class="tp-btn" style="text-decoration:none;"><i class="fas fa-receipt"></i> คำสั่งซื้อ</a>
                            <a href="{{ route('cart.index') }}" class="tp-btn" style="text-decoration:none;"><i class="fas fa-cart-shopping"></i> ตะกร้า</a>
                        </div>
                    @else
                        <div>
                            <div style="font-weight:800; font-size:16px; color:var(--ink);">เข้าสู่ระบบเพื่อช้อปเต็มรูปแบบ</div>
                            <p class="tp-muted" style="margin:4px 0 0; font-size:13px; line-height:1.6;">เก็บคูปอง ติดตามคำสั่งซื้อ และจ่ายด้วยกระเป๋าเงินได้ทันที</p>
                        </div>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
                            <a href="{{ route('login') }}" class="tp-btn" style="text-decoration:none;">เข้าสู่ระบบ</a>
                            <a href="{{ route('register') }}" class="tp-btn tp-btn-primary" style="text-decoration:none;">สมัครฟรี</a>
                        </div>
                    @endauth
                </div>
                <a href="{{ auth()->check() ? route('user.coupons.available') : route('login') }}" class="tp-card tp-card-hover" style="text-decoration:none; display:flex; align-items:center; gap:14px;">
                    <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:22px;"><i class="fas fa-ticket"></i></span>
                    <span style="min-width:0;">
                        <span style="display:block; font-weight:800; color:var(--ink);">คูปองส่วนลด</span>
                        <span class="tp-muted" style="display:block; font-size:12.5px; margin-top:2px;">เก็บคูปองจากร้านค้าที่ร่วมรายการ แล้วใช้ตอนชำระเงิน</span>
                    </span>
                    <i class="fas fa-chevron-right tp-muted" style="margin-left:auto;"></i>
                </a>
                <div class="tp-card" style="display:grid; grid-template-columns:1fr 1fr; gap:10px; text-align:center;">
                    <div>
                        <div class="tp-num" style="font-size:24px; font-weight:800; color:var(--deep1);">{{ number_format($stats['all'] ?? 0) }}</div>
                        <div class="tp-muted" style="font-size:12px;">สินค้าพร้อมขาย</div>
                    </div>
                    <div>
                        <div class="tp-num" style="font-size:24px; font-weight:800; color:var(--deep2);">{{ number_format($stats['stores'] ?? 0) }}</div>
                        <div class="tp-muted" style="font-size:12px;">ร้านค้าคุณภาพ</div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- ════════ หัวหมวด (เมื่อเลือกดูหมวด) ════════ --}}
    @if($browseMode === 'browse' && $activeCategory)
        @php
            $chMode = $categoryCover['mode'] ?? 'glyph';
            $chUrls = array_values(array_filter((array) ($categoryCover['urls'] ?? [])));
            $chIcon = $categoryCover['icon'] ?? 'fas fa-tags';
            $chTotal = method_exists($products, 'total') ? $products->total() : $products->count();
        @endphp
        <section class="sf-wrap" style="padding-top:22px;">
            <div style="position:relative; overflow:hidden; border-radius:24px; box-shadow:var(--card-shadow); background:var(--card-bg);">
                <div aria-hidden="true" style="position:absolute; inset:0; opacity:.35;">
                    @if($chMode === 'image' && ! empty($chUrls[0]))
                        <img src="{{ $chUrls[0] }}" alt="" style="width:100%; height:100%; object-fit:cover;" loading="lazy" onerror="this.style.display='none';">
                    @elseif($chMode === 'mosaic' && count($chUrls) > 1)
                        <div style="display:grid; grid-template-columns:repeat(4, 1fr); height:100%;">
                            @foreach(array_slice($chUrls, 0, 4) as $u)
                                <img src="{{ $u }}" alt="" style="width:100%; height:100%; object-fit:cover;" loading="lazy" onerror="this.style.display='none';">
                            @endforeach
                        </div>
                    @endif
                </div>
                <div aria-hidden="true" style="position:absolute; inset:0; background:linear-gradient(90deg, var(--card-bg) 20%, color-mix(in srgb, var(--card-bg) 60%, transparent) 100%);"></div>
                <div style="position:relative; padding:clamp(20px, 3.6vw, 34px);">
                    <nav class="sf-breadcrumb" aria-label="เส้นทางหมวดหมู่">
                        <a href="{{ route('storefront.index') }}">ร้านค้า</a>
                        @if($activeCategory->parent)
                            <span aria-hidden="true">/</span>
                            <a href="{{ route('storefront.index', ['category' => $activeCategory->parent->slug]) }}">{{ $activeCategory->parent->name }}</a>
                        @endif
                        <span aria-hidden="true">/</span>
                        <span style="color:var(--ink); font-weight:700;">{{ $activeCategory->name }}</span>
                    </nav>
                    <div style="display:flex; align-items:flex-start; gap:14px; margin-top:10px;">
                        <span class="tp-tile" style="width:54px; height:54px; border-radius:17px; font-size:22px;"><i class="{{ $chIcon }}"></i></span>
                        <div style="min-width:0;">
                            <h1 class="sf-h1">{{ $activeCategory->name }}</h1>
                            @if($activeCategory->description)
                                <p class="tp-muted" style="margin:6px 0 0; font-size:14px; line-height:1.6; max-width:640px;">{{ \Illuminate\Support\Str::limit($activeCategory->description, 180) }}</p>
                            @endif
                            <div style="margin-top:8px;"><span class="tp-pill tp-pill-soft">{{ number_format($chTotal) }} รายการ</span></div>
                        </div>
                    </div>
                    @if($activeCategory->children && $activeCategory->children->count() > 0)
                        <div class="sf-scroll" style="margin-top:14px;">
                            @foreach($activeCategory->children as $child)
                                <a href="{{ route('storefront.index', ['category' => $child->slug]) }}" class="sf-chip">{{ $child->name }}</a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endif

    {{-- ════════ FLASH DEALS (ดีลที่ยืนยันกับปลายทางแล้วเท่านั้น) ════════ --}}
    @if($browseMode === 'home' && $flashDeals && $flashDeals->count() > 0)
        <section class="sf-wrap sf-section">
            <div class="tp-card" style="padding:clamp(16px, 2.6vw, 24px);"
                 x-data="{ end: new Date({{ \Illuminate\Support\Js::from($flashDealEndTime ?? now()->addHours(3)->toIso8601String()) }}).getTime(), h: '00', m: '00', s: '00',
                           tick() { const d = Math.max(0, this.end - Date.now()); this.h = String(Math.floor(d / 3600000)).padStart(2, '0'); this.m = String(Math.floor(d % 3600000 / 60000)).padStart(2, '0'); this.s = String(Math.floor(d % 60000 / 1000)).padStart(2, '0'); } }"
                 x-init="tick(); setInterval(() => tick(), 1000)">
                <div class="sf-section-h">
                    <div>
                        <div class="sf-kicker"><i class="fas fa-bolt"></i> FLASH DEALS</div>
                        <h2 class="sf-title">ดีลเด็ด ราคายืนยันแล้ว</h2>
                        <div class="tp-muted" style="font-size:12.5px; margin-top:4px;">
                            ตรวจราคารอบถัดไปใน
                            <span class="tp-num" style="font-weight:800; color:var(--deep1);"><span x-text="h"></span>:<span x-text="m"></span>:<span x-text="s"></span></span>
                            @if($flashDealCheckedAt)
                                · ตรวจล่าสุด {{ $flashDealCheckedAt->timezone('Asia/Bangkok')->format('H:i') }} น.
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('storefront.index', ['deals' => 1, 'sort_by' => 'discount']) }}" class="sf-btn3d" style="min-height:44px;">ดูดีลทั้งหมด <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="sf-scroll" style="--sf-min:170px;">
                    @foreach($flashDeals as $deal)
                        <div style="width:176px; flex:none;">
                            <x-theme-v4.product-card :product="$deal" :favorited="in_array((int) $deal->id, $sfFavIds, true)" />
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ════════ หมวดหมู่ (หน้าแรก) ════════ --}}
    @if($browseMode === 'home' && $categories && $categories->count() > 0)
        <section class="sf-wrap sf-section">
            <div class="sf-section-h">
                <div>
                    <div class="sf-kicker">SHOP BY CATEGORY</div>
                    <h2 class="sf-title">ช้อปตามหมวดหมู่</h2>
                </div>
            </div>
            <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(140px, 1fr)); gap:12px;">
                @foreach($categories->take(12) as $cat)
                    @php
                        $catCover = $sfCoverService->cover($cat);
                        $catImg = $catCover['urls'][0] ?? null;
                        $catIcon = $catCover['icon'] ?? 'fas fa-tags';
                        $catCount = (int) ($cat->total_products_count ?? $cat->products_count ?? 0);
                    @endphp
                    <a href="{{ route('storefront.index', ['category' => $cat->slug]) }}" class="tp-card tp-card-hover" style="padding:14px 10px; text-decoration:none; display:flex; flex-direction:column; align-items:center; gap:9px; text-align:center;">
                        <span style="width:62px; height:62px; border-radius:18px; overflow:hidden; display:grid; place-items:center; background:linear-gradient(135deg, var(--a1soft), var(--a2soft)); box-shadow:var(--inset-sm); color:var(--deep1); font-size:24px;">
                            @if($catImg)
                                <img src="{{ $catImg }}" alt="" loading="lazy" style="width:100%; height:100%; object-fit:cover;" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                <i class="{{ $catIcon }}" style="display:none;"></i>
                            @else
                                <i class="{{ $catIcon }}"></i>
                            @endif
                        </span>
                        <span style="font-size:13px; font-weight:700; color:var(--ink); line-height:1.35;">{{ $cat->name }}</span>
                        @if($catCount > 0)
                            <span class="tp-muted tp-num" style="font-size:11px;">{{ number_format($catCount) }} รายการ</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ════════ ร้านค้าแนะนำ ════════ --}}
    <section class="sf-wrap sf-section">
        <div class="sf-section-h">
            <div>
                <div class="sf-kicker">FEATURED STORES</div>
                <h2 class="sf-title">ร้านค้าแนะนำ</h2>
            </div>
            <a href="{{ route('storefront.stores') }}" class="tp-btn" style="text-decoration:none;">ดูร้านทั้งหมด <i class="fas fa-arrow-right"></i></a>
        </div>
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(230px, 1fr)); gap:14px;">
            <x-theme-v4.store-card :official="true" :count="$stats['official'] ?? null" />
            @foreach(($featuredStores ?? collect())->take(6) as $fStore)
                <x-theme-v4.store-card :store="$fStore" :products="$fStore->products" />
            @endforeach
        </div>
    </section>

    {{-- ════════ ตัวกรองที่ใช้อยู่ ════════ --}}
    @if($sfTag !== '' || $sfSearch !== '' || $sfCategory !== '' || request()->boolean('deals'))
        <section class="sf-wrap" style="padding-top:18px;">
            <div style="display:flex; align-items:center; flex-wrap:wrap; gap:8px;">
                <span class="tp-muted" style="font-size:12.5px; font-weight:600;">กำลังกรอง:</span>
                @if($sfSearch !== '')
                    <a href="{{ route('storefront.index', array_filter(request()->except(['search', 'q', 'page']))) }}" class="sf-chip is-on" title="ลบตัวกรองนี้">ค้นหา: “{{ \Illuminate\Support\Str::limit($sfSearch, 30) }}” <i class="fas fa-xmark"></i></a>
                @endif
                @if($sfTag !== '')
                    <a href="{{ route('storefront.index', array_filter(request()->except(['tag', 'page']))) }}" class="sf-chip is-on" title="ลบตัวกรองนี้">แท็ก: {{ \Illuminate\Support\Str::limit($sfTag, 30) }} <i class="fas fa-xmark"></i></a>
                @endif
                @if($sfCategory !== '')
                    <a href="{{ route('storefront.index', array_filter(request()->except(['category', 'page']))) }}" class="sf-chip is-on" title="ลบตัวกรองนี้">หมวด: {{ $activeCategory->name ?? \Illuminate\Support\Str::limit($sfCategory, 30) }} <i class="fas fa-xmark"></i></a>
                @endif
                @if(request()->boolean('deals'))
                    <a href="{{ route('storefront.index', array_filter(request()->except(['deals', 'page']))) }}" class="sf-chip is-on" title="ลบตัวกรองนี้">เฉพาะดีลเด็ด <i class="fas fa-xmark"></i></a>
                @endif
                <a href="{{ route('storefront.index') }}" class="sf-chip">ล้างตัวกรองทั้งหมด</a>
            </div>
        </section>
    @endif

    {{-- ════════ สินค้าทั้งหมด ════════ --}}
    <section class="sf-wrap sf-section" id="products">
        <div class="tp-card" style="padding:clamp(14px, 2.4vw, 22px);">
            <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px; margin-bottom:16px;">
                <div class="sf-scroll" style="padding-bottom:4px;" role="tablist" aria-label="ประเภทร้าน">
                    @foreach($sfTabs as $tabKey => $tab)
                        <a href="{{ $sfTabUrl($tabKey) }}#products" role="tab" aria-selected="{{ $sfShopType === $tabKey ? 'true' : 'false' }}"
                           class="sf-chip {{ $sfShopType === $tabKey ? 'is-on' : '' }}">
                            <i class="fas {{ $tab['icon'] }}"></i> {{ $tab['label'] }}
                            <span class="tp-num" style="opacity:.8;">{{ number_format($tab['count']) }}</span>
                        </a>
                    @endforeach
                </div>
                <form method="GET" action="{{ route('storefront.index') }}" style="display:flex; align-items:center; gap:8px;">
                    @foreach(request()->except(['sort_by', 'page']) as $qk => $qv)
                        @if(is_scalar($qv))
                            <input type="hidden" name="{{ $qk }}" value="{{ $qv }}">
                        @endif
                    @endforeach
                    <label for="sf-sort" class="tp-muted" style="font-size:12.5px; font-weight:600; white-space:nowrap;">เรียงตาม</label>
                    <select id="sf-sort" name="sort_by" class="tp-input" style="height:44px; padding:0 12px; width:auto; min-width:150px;" onchange="this.form.submit()">
                        @foreach($sfSortOptions as $sk => $sl)
                            <option value="{{ $sk }}" @selected($sfSortBy === $sk)>{{ $sl }}</option>
                        @endforeach
                    </select>
                    <noscript><button type="submit" class="tp-btn">ใช้</button></noscript>
                </form>
            </div>

            @if($products->count() > 0)
                <div x-data="tpStorefrontMore({{ \Illuminate\Support\Js::from([
                        'url' => route('storefront.products'),
                        'page' => $products->currentPage(),
                        'hasMore' => $products->hasMorePages(),
                        'total' => $products->total(),
                        'shown' => $products->count(),
                    ]) }})">
                <div class="sf-grid">
                    @foreach($products as $product)
                        <x-theme-v4.product-card :product="$product" :favorited="in_array((int) $product->id, $sfFavIds, true)" />
                    @endforeach

                    {{-- สินค้าที่โหลดเพิ่ม (หน้าตาเดียวกับการ์ดด้านบน) --}}
                    <template x-for="p in items" :key="p.id">
                        <article class="sf-card">
                            <a :href="p.url">
                                <div class="sf-media">
                                    <img :src="p.main_image_url" :alt="p.name" loading="lazy" decoding="async">
                                    <div class="sf-badges">
                                        <template x-if="p.is_affiliate"><span class="sf-badge" style="background:var(--sf-lazada, #0f146d);" x-text="p.external_platform === 'aliexpress' ? 'AliExpress' : 'Lazada'"></span></template>
                                        <template x-if="p.discount > 0"><span class="sf-badge sf-badge-sale" x-text="'-' + p.discount + '%'"></span></template>
                                        <template x-if="p.is_featured"><span class="sf-badge sf-badge-gold">แนะนำ</span></template>
                                    </div>
                                    <template x-if="p.free_shipping && !p.is_affiliate"><span class="sf-ribbon">ส่งฟรี</span></template>
                                </div>
                                <div class="sf-body">
                                    <div class="sf-name" x-text="p.name"></div>
                                    <div style="display:flex; align-items:baseline; gap:7px; flex-wrap:wrap;">
                                        <span class="sf-price" x-text="'฿' + Number(p.price || 0).toLocaleString('th-TH')"></span>
                                        <template x-if="p.compare_at_price && Number(p.compare_at_price) > Number(p.price || 0)">
                                            <span class="sf-compare" x-text="'฿' + Number(p.compare_at_price).toLocaleString('th-TH')"></span>
                                        </template>
                                    </div>
                                    <div class="sf-meta">
                                        <template x-if="Number(p.rating_average) > 0"><span><span class="sf-stars">★</span> <span x-text="Number(p.rating_average).toFixed(1)"></span></span></template>
                                        <template x-if="Number(p.sales_count) > 0"><span x-text="'ขายแล้ว ' + Number(p.sales_count).toLocaleString('th-TH')"></span></template>
                                        <template x-if="Number(p.pv) > 0"><span class="sf-points"><i class="fas fa-star"></i> <span x-text="'คะแนนสะสม ' + Number(p.pv).toLocaleString('th-TH')"></span></span></template>
                                    </div>
                                </div>
                            </a>
                            <div class="sf-card-cta">
                                <template x-if="p.is_affiliate"><a :href="p.url" class="tp-btn" style="text-decoration:none;"><i class="fas fa-eye"></i> ดูรายละเอียด</a></template>
                                <template x-if="!p.is_affiliate"><button type="button" class="tp-btn tp-btn-primary" @click="window.tpShop.addToCart(p.id, 1, { button: $el })"><i class="fas fa-cart-plus"></i> ใส่ตะกร้า</button></template>
                            </div>
                        </article>
                    </template>
                </div>

                    <div style="display:flex; flex-direction:column; align-items:center; gap:10px; margin-top:18px;">
                        <button type="button" class="sf-btn3d" x-show="hasMore" @click="loadMore()" :disabled="loading">
                            <i class="fas" :class="loading ? 'fa-spinner fa-spin' : 'fa-chevron-down'"></i>
                            <span x-text="loading ? 'กำลังโหลด...' : 'โหลดสินค้าเพิ่มเติม'"></span>
                        </button>
                        <p x-show="error" x-cloak x-text="error" class="sf-note sf-note-err" style="margin:0;" role="alert"></p>
                        <p class="tp-muted" style="margin:0; font-size:12.5px;">แสดง <span class="tp-num" style="font-weight:800; color:var(--ink);" x-text="shown"></span> จาก <span class="tp-num" style="font-weight:800; color:var(--ink);" x-text="total"></span> รายการ</p>
                    </div>
                </div>
            @else
                <div style="text-align:center; padding:40px 12px;">
                    <div style="font-size:46px;" aria-hidden="true">🔎</div>
                    <h3 style="margin:10px 0 6px; font-size:18px; font-weight:800; color:var(--ink);">ไม่พบสินค้า</h3>
                    <p class="tp-muted" style="margin:0 0 16px; font-size:13.5px;">ลองค้นหาด้วยคำอื่น หรือล้างตัวกรอง</p>
                    <a href="{{ route('storefront.index') }}" class="sf-btn3d">ดูสินค้าทั้งหมด</a>
                </div>
            @endif
        </div>
    </section>

    {{-- ════════ จุดเด่น + รับข่าวสาร (หน้าแรก) ════════ --}}
    @if($browseMode === 'home')
        <section class="sf-wrap sf-section">
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:14px;">
                @foreach([
                    ['icon' => 'fa-truck-fast', 'title' => 'ส่งฟรีทั่วไทย', 'desc' => 'เมื่อซื้อครบ ฿'.number_format($sfFreeShip)],
                    ['icon' => 'fa-motorcycle', 'title' => 'ส่งด่วนด้วยไรเดอร์', 'desc' => 'ร้านใกล้บ้าน ติดตามไรเดอร์ได้สด'],
                    ['icon' => 'fa-shield-halved', 'title' => 'ชำระเงินปลอดภัย', 'desc' => 'กระเป๋าเงิน พร้อมเพย์ เก็บเงินปลายทาง'],
                    ['icon' => 'fa-award', 'title' => 'ร้านค้าคุณภาพ', 'desc' => 'ร้านทางการและร้านที่ยืนยันตัวตนแล้ว'],
                ] as $benefit)
                    <div class="tp-card" style="display:flex; align-items:center; gap:14px;">
                        <span class="tp-tile" style="width:50px; height:50px; border-radius:16px; font-size:20px;"><i class="fas {{ $benefit['icon'] }}"></i></span>
                        <span>
                            <span style="display:block; font-weight:800; color:var(--ink);">{{ $benefit['title'] }}</span>
                            <span class="tp-muted" style="display:block; font-size:12.5px; margin-top:2px;">{{ $benefit['desc'] }}</span>
                        </span>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="sf-wrap sf-section">
            <div style="position:relative; overflow:hidden; padding:clamp(24px, 4vw, 40px); border-radius:28px; color:var(--on-accent, #fff); background:linear-gradient(135deg, var(--accent1), var(--accent2)); box-shadow:0 14px 40px rgba(0,0,0,.16);"
                 x-data="{ email: '', msg: '',
                           submit() {
                               const e = (this.email || '').trim();
                               if (!e) { this.msg = 'กรุณากรอกอีเมลของคุณ'; return; }
                               if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e)) { this.msg = 'รูปแบบอีเมลไม่ถูกต้อง กรุณาตรวจสอบอีกครั้ง'; return; }
                               this.msg = 'ขออภัย ระบบรับข่าวสารทางอีเมลยังไม่เปิดให้บริการ ระหว่างนี้ติดตามโปรโมชั่นได้ที่หน้าร้านค้าโดยตรง';
                           } }">
                @if(file_exists(public_path('images/art/newsletter-bg.webp')))
                    <img src="{{ asset('images/art/newsletter-bg.webp') }}" alt="" aria-hidden="true" loading="lazy" decoding="async"
                         style="position:absolute; inset:0; width:100%; height:100%; object-fit:cover; opacity:.3; mix-blend-mode:soft-light; pointer-events:none;">
                @endif
                <div style="position:relative; max-width:640px; margin:0 auto; text-align:center;">
                    <h2 style="margin:0 0 8px; font-size:clamp(22px, 3.6vw, 30px); font-weight:800; text-shadow:0 1px 3px rgba(0,0,0,.14);">รับข่าวสารและโปรโมชั่นพิเศษ</h2>
                    <p style="margin:0 0 18px; opacity:.94;">สมัครรับข่าวสารเพื่อรับส่วนลดพิเศษก่อนใคร</p>
                    <form @submit.prevent="submit()" style="display:flex; flex-wrap:wrap; gap:10px; justify-content:center;">
                        <label for="sf-newsletter" style="position:absolute; left:-9999px;">อีเมล</label>
                        <input id="sf-newsletter" type="email" x-model="email" placeholder="กรอกอีเมลของคุณ" class="tp-input" style="flex:1 1 240px; max-width:360px; height:48px;">
                        <button type="submit" class="sf-btn3d is-soft" style="color:var(--deep1);">สมัครรับข่าวสาร</button>
                    </form>
                    <p x-show="msg" x-cloak x-text="msg" role="status" aria-live="polite" style="margin:14px auto 0; max-width:520px; padding:10px 14px; border-radius:14px; font-size:13px; background:rgba(0,0,0,.18);"></p>
                </div>
            </div>
        </section>
    @endif
</main>

<x-theme-v4.public-footer />
<x-eve.widget surface="storefront" />
@endsection

@push('scripts')
<script>
    /**
     * โหลดสินค้าเพิ่ม (กดปุ่มเท่านั้น) — ใช้ query เดิมของหน้า + page ถัดไป
     */
    function tpStorefrontMore(cfg) {
        return {
            items: [],
            page: cfg.page,
            hasMore: !!cfg.hasMore,
            total: cfg.total,
            shown: cfg.shown,
            loading: false,
            error: '',
            async loadMore() {
                if (this.loading || !this.hasMore) { return; }
                this.loading = true;
                this.error = '';
                try {
                    const params = new URLSearchParams(window.location.search);
                    params.set('page', this.page + 1);
                    const res = await fetch(cfg.url + '?' + params.toString(), { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) { throw new Error('HTTP ' + res.status); }
                    const data = await res.json();
                    const list = Array.isArray(data.products) ? data.products : [];
                    const seen = new Set(this.items.map(p => p.id));
                    list.forEach(p => { if (!seen.has(p.id)) { this.items.push(p); } });
                    this.page = data.current_page || (this.page + 1);
                    this.hasMore = !!data.has_more && list.length > 0;
                    this.total = data.total || this.total;
                    this.shown = cfg.shown + this.items.length;
                } catch (e) {
                    this.error = 'โหลดสินค้าเพิ่มไม่สำเร็จ กรุณาลองกดอีกครั้ง';
                } finally {
                    this.loading = false;
                }
            }
        };
    }
</script>
@endpush
