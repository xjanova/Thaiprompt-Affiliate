{{--
 | หน้ารายละเอียดสินค้าตลาดสด (taladsod.listing) — ธีม V4 (frontend-v4)
 | Controller: FreshMarket\HomeController@listing
 | ตัวแปร: $listing (seller, category, optionGroups.options), $optionGroups (API: id,name,selection_type,is_required,min_select,max_select,rule_label,options[]),
 |         $relatedListings, $reviews, $reviewStats, $deliveryBaseRate, $deliveryPerKm, $paymentMethods, $riderEnabled, $isOwner,
 |         $shopPresence (presencePayload), $isFollowing, $followersCount, $shopEndpoints{location,follow,unfollow}
 | ใส่ตะกร้า: POST taladsod.cart.items.store (JSON) {listing_id, quantity, option_ids[], note} — ราคาจริงคิดที่เซิร์ฟเวอร์
 | สั่งเลย: ใส่ตะกร้าแล้วไป taladsod.checkout/{seller} · ค่าส่ง: GET taladsod.api.delivery-quote?listing_id&latitude&longitude&quantity
 --}}
@extends('layouts.frontend-v4')

@section('title', $listing->title.' · ตลาดสดไทยพร้อม')
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags((string) ($listing->description ?: $listing->title.' จาก '.($listing->seller?->shop_name ?? 'ตลาดสดไทยพร้อม'))), 155))

@section('meta')
    <meta property="og:type" content="product">
    <meta property="og:title" content="{{ $listing->title }} · ตลาดสดไทยพร้อม">
    <meta property="og:description" content="{{ \Illuminate\Support\Str::limit(strip_tags((string) $listing->description), 150) }}">
    @if($listing->primary_image)
        <meta property="og:image" content="{{ \Illuminate\Support\Str::startsWith($listing->primary_image, ['http://', 'https://']) ? $listing->primary_image : url($listing->primary_image) }}">
    @endif
@endsection

@php
    $ui = \App\Support\TaladsodWebUi::class;
    $seller = $listing->seller;
    $presence = $shopPresence ?? [];
    $shopOpen = (bool) ($presence['is_open'] ?? false);
    $shopLive = (bool) ($presence['live_location_sharing'] ?? false);
    $available = $listing->isAvailableForPurchase();
    $canOrder = ! $isOwner && $available && (bool) ($presence['can_order'] ?? false);
    $images = collect(is_array($listing->images) ? $listing->images : [])
        ->prepend($listing->main_image_url)
        ->filter(fn ($u) => is_string($u) && $u !== '')
        ->unique()
        ->values()
        ->all();
    $slug = $listing->slug;

    $cfg = [
        'listingId' => (int) $listing->id,
        'basePrice' => round((float) $listing->price, 2),
        'unit' => $listing->unit ?: 'ชิ้น',
        'maxQty' => max(1, (int) $listing->max_order_quantity),
        'groups' => $optionGroups,
        'images' => $images,
        'isGuest' => ! auth()->check(),
        'canOrder' => $canOrder,
        'cartAddUrl' => route('taladsod.cart.items.store'),
        'cartUrl' => route('taladsod.cart'),
        'checkoutUrl' => $seller ? route('taladsod.checkout', ['seller' => $seller->id]) : route('taladsod.cart'),
        'loginUrl' => route('taladsod.login-continue', ['to' => '/taladsod/listing/'.$slug]),
        'quoteUrl' => route('taladsod.api.delivery-quote'),
        'riderEnabled' => (bool) $riderEnabled,
        'follow' => [
            'following' => (bool) $isFollowing,
            'count' => (int) $followersCount,
            'followUrl' => $shopEndpoints['follow'] ?? null,
            'unfollowUrl' => $shopEndpoints['unfollow'] ?? null,
        ],
    ];

    $avgRating = (float) ($reviewStats->avg_rating ?? 0);
    $reviewTotal = (int) ($reviewStats->total ?? 0);
@endphp

@section('content')
<x-theme-v4.shop-kit />
@include('taladsod.partials.kit')
<x-theme-v4.public-header active="taladsod" :search="true" :search-action="route('taladsod.search')" search-name="q"
                          search-placeholder="ค้นหาของสด อาหาร หรือร้านใกล้บ้าน..." :suggest="false" />

<main class="ts-scope ts-has-bottom-bar" style="flex:1; padding-bottom:44px;" x-data="tsListing({{ \Illuminate\Support\Js::from($cfg) }})">
    <div class="sf-wrap">
        @include('taladsod.partials.nav', ['active' => null])

        <nav class="sf-breadcrumb" aria-label="เส้นทางหน้า" style="margin:4px 0 16px;">
            <a href="{{ route('taladsod.home') }}">ตลาดสด</a>
            <i class="fas fa-chevron-right" style="font-size:9px;" aria-hidden="true"></i>
            @if($listing->category)
                <a href="{{ route('taladsod.category', $listing->category->slug) }}">{{ $listing->category->name }}</a>
                <i class="fas fa-chevron-right" style="font-size:9px;" aria-hidden="true"></i>
            @endif
            <span style="color:var(--ink); font-weight:600;">{{ \Illuminate\Support\Str::limit($listing->title, 40) }}</span>
        </nav>

        @if($isOwner)
            <div class="sf-note sf-note-info" style="margin-bottom:14px; display:flex; flex-wrap:wrap; gap:10px; align-items:center; justify-content:space-between;">
                <span><i class="fas fa-store" aria-hidden="true"></i> นี่คือสินค้าในร้านของคุณ — ลูกค้าจะเห็นหน้านี้แบบเดียวกัน</span>
                <a href="{{ route('taladsod.listing.edit', $listing->id) }}" class="ts-btn3d sm ts-tone-gold"><i class="fas fa-pen" aria-hidden="true"></i> แก้ไขสินค้า</a>
            </div>
        @endif

        <div class="ts-pdp">
            {{-- ════════ รูปสินค้า ════════ --}}
            <div class="a-gallery">
                <div class="tp-card" style="padding:12px;">
                    <div style="position:relative; aspect-ratio:1/1; max-height:560px; width:100%; border-radius:18px; overflow:hidden; background:var(--surf); box-shadow:var(--inset-sm); display:grid; place-items:center;"
                         x-on:touchstart.passive="sx = $event.touches[0].clientX" x-on:touchend="swipe($event.changedTouches[0].clientX)">
                        @if(count($images) > 0)
                            <img :src="shownImage" src="{{ $images[0] }}" alt="{{ $listing->title }}" style="width:100%; height:100%; object-fit:cover; transition:opacity .25s ease;">
                        @else
                            <span style="font-size:90px; opacity:.45;" aria-hidden="true">🥬</span>
                        @endif
                        <div class="sf-badges">
                            @if($listing->is_organic)
                                <span class="sf-badge sf-badge-ok"><i class="fas fa-leaf" aria-hidden="true"></i> อินทรีย์</span>
                            @endif
                            @if($listing->freshness_level)
                                <span class="sf-badge sf-badge-deep"><i class="fas fa-seedling" aria-hidden="true"></i> {{ $listing->freshness_level }}</span>
                            @endif
                            @if($listing->cashback_percent)
                                <span class="sf-badge sf-badge-gold">เงินคืน {{ rtrim(rtrim(number_format((float) $listing->cashback_percent, 1), '0'), '.') }}%</span>
                            @endif
                        </div>
                        @if(count($images) > 1)
                            <button type="button" class="ts-icon-btn" style="position:absolute; left:10px; top:50%; transform:translateY(-50%);" x-on:click="go(idx - 1)" aria-label="รูปก่อนหน้า"><i class="fas fa-chevron-left" aria-hidden="true"></i></button>
                            <button type="button" class="ts-icon-btn" style="position:absolute; right:10px; top:50%; transform:translateY(-50%);" x-on:click="go(idx + 1)" aria-label="รูปถัดไป"><i class="fas fa-chevron-right" aria-hidden="true"></i></button>
                        @endif
                    </div>
                    @if(count($images) > 1)
                        <div class="sf-scroll" style="margin-top:10px; padding-bottom:4px;">
                            @foreach($images as $i => $img)
                                <button type="button" class="sf-thumb" style="border:0; padding:0; cursor:pointer; width:64px; height:64px;"
                                        :style="{ outline: idx === {{ $i }} && !(preferOption && optionImage) ? '3px solid var(--accent1)' : 'none', outlineOffset: '2px' }"
                                        x-on:click="go({{ $i }})" aria-label="ดูรูปที่ {{ $i + 1 }}">
                                    <img src="{{ $img }}" alt="" loading="lazy">
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- ════════ ร้าน + รายละเอียด + รีวิว ════════ --}}
            <div class="a-info sf-stack">
                {{-- ร้าน --}}
                @if($seller)
                    <div class="tp-card" style="padding:16px;">
                        <div style="display:flex; gap:14px; align-items:flex-start; flex-wrap:wrap;">
                            <a href="{{ route('taladsod.seller', $seller->id) }}" class="ts-avatar" style="text-decoration:none;" aria-label="ไปหน้าร้าน {{ $seller->shop_name }}">
                                @if($seller->shop_image)
                                    <img src="{{ $seller->shop_image }}" alt="">
                                @else
                                    {{ $ui::initial($seller->shop_name) }}
                                @endif
                            </a>
                            <div style="flex:1 1 200px; min-width:0;" class="ts-stack">
                                <div style="gap:6px; display:flex; flex-direction:column;">
                                    <a href="{{ route('taladsod.seller', $seller->id) }}" style="text-decoration:none; color:var(--ink); font-size:16px; font-weight:800;">
                                        {{ $seller->shop_name }}
                                        @if($seller->is_verified)
                                            <i class="fas fa-circle-check" style="color:var(--ts-info); font-size:14px;" title="ร้านยืนยันแล้ว"></i>
                                        @endif
                                    </a>
                                    <div class="ts-row" style="gap:6px;">
                                        @if($shopOpen)
                                            <span class="ts-pill ts-tone-ok"><span class="ts-dot {{ $shopLive ? 'live' : '' }}"></span> {{ $shopLive ? 'เปิดอยู่ · ตำแหน่งสด' : 'เปิดอยู่' }}</span>
                                        @else
                                            <span class="ts-pill ts-tone-muted"><i class="fas fa-moon" aria-hidden="true"></i> ปิดอยู่</span>
                                        @endif
                                        @if($seller->isMobileShop())
                                            <span class="ts-pill ts-tone-deep"><i class="fas fa-cart-flatbed" aria-hidden="true"></i> รถเข็น/ตลาดนัด</span>
                                        @endif
                                        <span class="ts-pill ts-tone-gold"><span class="ts-star-view">★</span> {{ number_format((float) $seller->rating_average, 1) }}</span>
                                    </div>
                                    @if($shopOpen && (! empty($presence['location_label']) || ! empty($presence['closes_at'])))
                                        <span class="ts-muted ts-small">
                                            @if(! empty($presence['location_label']))<i class="fas fa-map-pin" aria-hidden="true"></i> {{ $presence['location_label'] }}@endif
                                            @if(! empty($presence['closes_at'])) · ปิด {{ $ui::time($presence['closes_at']) }} น.@endif
                                        </span>
                                    @elseif(! $shopOpen)
                                        <span class="ts-muted ts-small">{{ \App\Models\FreshMarketSeller::CLOSED_MESSAGE }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="ts-row" style="gap:8px;">
                                @unless($isOwner)
                                    <button type="button" class="ts-btn3d sm" :class="follow.following ? 'soft' : 'ts-tone-bad'" x-on:click="toggleFollow()" :disabled="followBusy"
                                            :aria-pressed="follow.following ? 'true' : 'false'">
                                        <i class="fas" :class="followBusy ? 'fa-circle-notch ts-spin' : (follow.following ? 'fa-heart' : 'fa-heart-circle-plus')" aria-hidden="true"></i>
                                        <span x-text="follow.following ? 'ติดตามแล้ว' : 'ติดตามร้าน'">{{ $isFollowing ? 'ติดตามแล้ว' : 'ติดตามร้าน' }}</span>
                                        <span class="ts-num" style="opacity:.8; font-size:12px;" x-text="follow.count">{{ $followersCount }}</span>
                                    </button>
                                @endunless
                                <a href="{{ route('taladsod.seller', $seller->id) }}" class="tp-btn"><i class="fas fa-store" aria-hidden="true"></i> ดูเมนูร้าน</a>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- รายละเอียดสินค้า --}}
                @if($listing->description)
                    <div class="tp-card">
                        <h2 class="ts-h2" style="margin-bottom:10px;"><i class="fas fa-circle-info" style="color:var(--accent2);" aria-hidden="true"></i> รายละเอียด</h2>
                        <div class="sf-prose">{!! nl2br(e($listing->description)) !!}</div>
                    </div>
                @endif

                {{-- รีวิว --}}
                <div class="tp-card">
                    <div class="ts-row" style="justify-content:space-between; margin-bottom:10px;">
                        <h2 class="ts-h2"><i class="fas fa-star" style="color:var(--sf-star, #e6b347);" aria-hidden="true"></i> รีวิวจากผู้ซื้อ</h2>
                        @if($reviewTotal > 0)
                            <span class="ts-row" style="gap:6px;"><b class="ts-num" style="font-size:20px;">{{ number_format($avgRating, 1) }}</b><span class="ts-muted ts-small">จาก {{ number_format($reviewTotal) }} รีวิว</span></span>
                        @endif
                    </div>
                    @forelse($reviews as $review)
                        <div class="ts-line">
                            <span class="ts-avatar" style="width:40px; height:40px; font-size:15px; border-radius:13px;">{{ $ui::initial($review->buyer?->name, 'ผ') }}</span>
                            <div style="flex:1; min-width:0;">
                                <div class="ts-row" style="justify-content:space-between; gap:6px;">
                                    <b style="font-size:13.5px;">{{ $review->buyer?->name ? \Illuminate\Support\Str::mask($review->buyer->name, '*', 2, max(0, mb_strlen($review->buyer->name) - 3)) : 'ผู้ซื้อ' }}</b>
                                    <span class="ts-muted ts-small">{{ $ui::shortDate($review->updated_at, false) }}</span>
                                </div>
                                <div class="ts-star-view" style="font-size:13px;" aria-label="{{ (int) $review->buyer_rating }} ดาว">{{ str_repeat('★', (int) $review->buyer_rating) }}<span style="opacity:.25;">{{ str_repeat('★', max(0, 5 - (int) $review->buyer_rating)) }}</span></div>
                                @if($review->buyer_review)
                                    <p style="margin:6px 0 0; font-size:13.5px; line-height:1.6; overflow-wrap:anywhere;">{{ $review->buyer_review }}</p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="ts-empty" style="padding:18px;">
                            <span class="em" aria-hidden="true">💬</span>
                            <span class="ts-muted">ยังไม่มีรีวิว — สั่งแล้วมาเล่าให้เพื่อนๆ ฟังนะ</span>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- ════════ ขวา: ราคา + ตัวเลือก + สั่งซื้อ ════════ --}}
            <aside class="a-buy" aria-label="สั่งซื้อสินค้า">
                <div class="tp-card ts-stack" style="padding:20px; gap:16px;">
                    <div>
                        <h1 class="sf-h1" style="font-size:clamp(22px,3.6vw,28px);">{{ $listing->title }}</h1>
                        <div class="ts-row" style="gap:8px; margin-top:10px; align-items:baseline;">
                            <span class="ts-money" style="font-size:30px;">฿<span x-text="money(unitPrice)">{{ $ui::money($listing->price) }}</span></span>
                            <span class="ts-muted">/ {{ $listing->unit ?: 'ชิ้น' }}</span>
                            @if($listing->compare_at_price && (float) $listing->compare_at_price > (float) $listing->price)
                                <span class="sf-compare">฿{{ $ui::money($listing->compare_at_price) }}</span>
                                <span class="ts-pill solid ts-tone-bad">-{{ (int) round($listing->discount_percentage) }}%</span>
                            @endif
                        </div>
                        <div class="ts-row" style="gap:6px; margin-top:8px;">
                            @if(! $available)
                                <span class="ts-pill ts-tone-bad"><i class="fas fa-box-open" aria-hidden="true"></i> หมดชั่วคราว</span>
                            @elseif($listing->tracksStock() && (int) $listing->quantity_available <= 5)
                                <span class="ts-pill ts-tone-warn"><i class="fas fa-fire" aria-hidden="true"></i> เหลือ {{ (int) $listing->quantity_available }} {{ $listing->unit }}</span>
                            @elseif(! $listing->tracksStock())
                                <span class="ts-pill ts-tone-ok"><i class="fas fa-fire-burner" aria-hidden="true"></i> ทำสดตามสั่ง</span>
                            @endif
                            @if($reviewTotal > 0)
                                <span class="ts-pill ts-tone-gold"><span class="ts-star-view">★</span> {{ number_format($avgRating, 1) }} ({{ $reviewTotal }})</span>
                            @endif
                        </div>
                    </div>

                    {{-- ตัวเลือกสินค้า --}}
                    <template x-for="group in groups" :key="group.id">
                        <fieldset style="border:0; margin:0; padding:0; min-width:0;" :id="'grp-' + group.id">
                            <legend style="padding:0; width:100%;">
                                <span class="ts-row" style="justify-content:space-between; gap:6px;">
                                    <b style="font-size:14.5px;" x-text="group.name"></b>
                                    <span class="ts-pill" :class="group.min_select > 0 ? 'ts-tone-bad' : 'ts-tone-muted'" x-text="group.rule_label"></span>
                                </span>
                            </legend>
                            <div class="ts-stack" style="gap:8px; margin-top:10px;">
                                <template x-for="opt in group.options" :key="opt.id">
                                    <button type="button" class="ts-choice"
                                            :class="{ 'is-on': isSelected(group, opt), 'is-off': !opt.is_available }"
                                            :aria-pressed="isSelected(group, opt) ? 'true' : 'false'" :disabled="!opt.is_available"
                                            x-on:click="toggle(group, opt)">
                                        <span class="ind" :class="group.selection_type === 'multi' ? 'box' : ''"><i class="fas fa-check" aria-hidden="true"></i></span>
                                        <img class="img" :src="opt.image_url" alt="" x-show="opt.image_url" loading="lazy">
                                        <span class="name">
                                            <span x-text="opt.name"></span>
                                            <span class="ts-muted ts-small block" style="font-weight:600;" x-show="!opt.is_available">หมดชั่วคราว</span>
                                        </span>
                                        <span class="delta" x-text="opt.price_delta > 0 ? '+฿' + money(opt.price_delta) : 'ไม่บวกเพิ่ม'"></span>
                                    </button>
                                </template>
                            </div>
                            <p class="ts-err" x-show="groupErrors[group.id]" x-text="groupErrors[group.id]" x-cloak></p>
                        </fieldset>
                    </template>

                    {{-- จำนวน + โน้ต --}}
                    <div class="ts-row" style="justify-content:space-between;">
                        <span class="ts-label" style="margin:0;">จำนวน</span>
                        <div class="sf-qty" role="group" aria-label="จำนวน">
                            <button type="button" x-on:click="qty = Math.max(1, qty - 1)" aria-label="ลดจำนวน">−</button>
                            <input type="number" x-model.number="qty" min="1" :max="maxQty" inputmode="numeric" aria-label="จำนวน" x-on:change="qty = Math.min(maxQty, Math.max(1, parseInt(qty) || 1))">
                            <button type="button" x-on:click="qty = Math.min(maxQty, qty + 1)" aria-label="เพิ่มจำนวน">+</button>
                        </div>
                    </div>
                    <div>
                        <label class="ts-label" for="ts-note">โน้ตถึงร้าน <span class="ts-muted" style="font-weight:600;">(ไม่บังคับ)</span></label>
                        <input id="ts-note" type="text" class="tp-input" maxlength="255" x-model="note" placeholder="เช่น ไม่ใส่ผัก เผ็ดน้อย แยกน้ำ">
                    </div>

                    <div class="sf-total">
                        <span class="ts-muted">รวม <span x-text="qty"></span> {{ $listing->unit ?: 'ชิ้น' }}</span>
                        <span class="tp-num">฿<span x-text="money(total)">{{ $ui::money($listing->price) }}</span></span>
                    </div>

                    {{-- ปุ่มสั่งซื้อ --}}
                    @if($isOwner)
                        <div class="sf-note sf-note-info">ร้านของคุณ — สั่งซื้อสินค้าของตัวเองไม่ได้</div>
                    @elseif(! $available)
                        <div class="sf-note sf-note-warn"><i class="fas fa-box-open" aria-hidden="true"></i> สินค้าหมดชั่วคราว ติดตามร้านไว้ได้เลย</div>
                    @else
                        @if(! $shopOpen)
                            <div class="sf-note sf-note-warn"><i class="fas fa-moon" aria-hidden="true"></i> ร้านปิดอยู่ — ใส่ตะกร้าเก็บไว้ได้ แล้วสั่งเมื่อร้านเปิด</div>
                        @endif
                        <div class="ts-grid" style="--ts-min:150px; gap:10px;">
                            <button type="button" class="ts-btn3d soft block" x-on:click="add(false)" :disabled="adding">
                                <i class="fas" :class="adding === 'cart' ? 'fa-circle-notch ts-spin' : 'fa-basket-shopping'" aria-hidden="true"></i> ใส่ตะกร้า
                            </button>
                            @if($shopOpen)
                                <button type="button" class="ts-btn3d ts-tone-gold block" x-on:click="add(true)" :disabled="adding">
                                    <i class="fas" :class="adding === 'buy' ? 'fa-circle-notch ts-spin' : 'fa-bolt'" aria-hidden="true"></i> สั่งเลย
                                </button>
                            @endif
                        </div>
                    @endif

                    {{-- วิธีจ่าย + ค่าส่ง --}}
                    <div class="ts-stack" style="gap:10px; padding-top:4px;">
                        <div class="ts-row" style="gap:6px;">
                            @if(in_array('wallet', $paymentMethods, true))
                                <span class="ts-pill ts-tone-info"><i class="fas fa-wallet" aria-hidden="true"></i> จ่ายด้วยกระเป๋าเงิน</span>
                            @endif
                            @if(in_array('cod', $paymentMethods, true))
                                <span class="ts-pill ts-tone-ok"><i class="fas fa-money-bill-wave" aria-hidden="true"></i> เก็บเงินปลายทาง</span>
                            @endif
                            <span class="ts-pill ts-tone-muted"><i class="fas fa-person-walking" aria-hidden="true"></i> รับเองที่ร้าน</span>
                        </div>
                        @if($riderEnabled)
                            <div class="tp-inset" style="border-radius:16px; padding:12px 14px;">
                                <div class="ts-row" style="justify-content:space-between;">
                                    <span style="font-size:13px; font-weight:700;"><i class="fas fa-motorcycle" style="color:var(--accent2);" aria-hidden="true"></i> ไรเดอร์ส่งถึงบ้าน</span>
                                    <span class="ts-muted ts-small">เริ่ม ฿{{ $ui::money($deliveryBaseRate) }} + ฿{{ $ui::money($deliveryPerKm) }}/กม.</span>
                                </div>
                                <div x-show="quote" x-cloak class="ts-row" style="margin-top:8px; gap:6px;">
                                    <span class="ts-pill solid ts-tone-ok" x-show="quote && quote.available">ค่าส่ง ฿<span x-text="quote ? money(quote.total_fee) : ''"></span></span>
                                    <span class="ts-muted ts-small" x-show="quote && quote.available" x-text="quote ? ('ระยะ ' + window.ts.distance(quote.distance_km) + ' · ประมาณ ' + quote.estimated_duration_minutes + ' นาที') : ''"></span>
                                </div>
                                <p class="ts-err" x-show="quoteError" x-text="quoteError" x-cloak></p>
                                <button type="button" class="tp-btn tp-btn-sm" style="margin-top:8px;" x-on:click="checkDelivery()" :disabled="quoting">
                                    <i class="fas" :class="quoting ? 'fa-circle-notch ts-spin' : 'fa-location-crosshairs'" aria-hidden="true"></i>
                                    <span x-text="quote ? 'คำนวณใหม่' : 'เช็คค่าส่งถึงตำแหน่งฉัน'">เช็คค่าส่งถึงตำแหน่งฉัน</span>
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </aside>
        </div>

        {{-- ════════ สินค้าที่เกี่ยวข้อง ════════ --}}
        @if($relatedListings->count() > 0)
            <section class="sf-section" aria-labelledby="ts-related-h">
                <div class="sf-section-h">
                    <h2 id="ts-related-h" class="sf-title">จากร้านนี้และหมวดเดียวกัน</h2>
                </div>
                <div class="sf-grid">
                    @foreach($relatedListings as $related)
                        @include('taladsod.partials.listing-card', ['listing' => $related])
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    {{-- แถบสั่งซื้อติดล่างจอ (มือถือ) --}}
    @if($canOrder || (! $isOwner && $available))
        <div class="ts-bottom-bar">
            <div style="flex:1; min-width:0;">
                <div class="ts-muted ts-small">รวม <span x-text="qty"></span> {{ $listing->unit ?: 'ชิ้น' }}</div>
                <div class="ts-money" style="font-size:21px;">฿<span x-text="money(total)">{{ $ui::money($listing->price) }}</span></div>
            </div>
            <button type="button" class="ts-btn3d soft sm" x-on:click="add(false)" :disabled="adding" aria-label="ใส่ตะกร้า"><i class="fas fa-basket-shopping" aria-hidden="true"></i></button>
            @if($shopOpen)
                <button type="button" class="ts-btn3d ts-tone-gold" x-on:click="add(true)" :disabled="adding"><i class="fas fa-bolt" aria-hidden="true"></i> สั่งเลย</button>
            @else
                <button type="button" class="ts-btn3d ts-tone-gold" x-on:click="add(false)" :disabled="adding"><i class="fas fa-basket-shopping" aria-hidden="true"></i> เก็บใส่ตะกร้า</button>
            @endif
        </div>
    @endif
</main>

<x-theme-v4.public-footer />
@endsection

@push('scripts')
<script>
    /**
     * หน้าสินค้า: เลือกตัวเลือก (บังคับ/ไม่บังคับ) + ราคาสด + จำนวน + ใส่ตะกร้า/สั่งเลย + ค่าส่ง + ติดตามร้าน
     * ราคาที่แสดงเป็นแค่ตัวอย่าง — เซิร์ฟเวอร์คิดราคาจริงจากรหัสตัวเลือกเสมอ
     */
    window.tsListing = function (cfg) {
        return {
            groups: cfg.groups || [],
            images: cfg.images || [],
            idx: 0, sx: null,
            selected: {},
            groupErrors: {},
            qty: 1, maxQty: cfg.maxQty || 999, note: '',
            adding: false,
            quote: null, quoteError: '', quoting: false,
            follow: cfg.follow, followBusy: false,

            money(n) { return window.ts.money(n); },

            get optionsPrice() {
                let sum = 0;
                this.groups.forEach((g) => {
                    (this.selected[g.id] || []).forEach((id) => {
                        const o = g.options.find((x) => x.id === id);
                        if (o) { sum += Number(o.price_delta) || 0; }
                    });
                });
                return Math.round(sum * 100) / 100;
            },
            get unitPrice() { return Math.round((Number(cfg.basePrice) + this.optionsPrice) * 100) / 100; },
            get total() { return Math.round(this.unitPrice * (Number(this.qty) || 1) * 100) / 100; },

            /** รูปของตัวเลือกแบบเลือกเดียวที่เลือกอยู่ (เช่น กะเพรากุ้ง) มาก่อนรูปหลัก */
            get optionImage() {
                for (const g of this.groups) {
                    if (g.selection_type === 'multi') { continue; }
                    const id = (this.selected[g.id] || [])[0];
                    const o = id ? g.options.find((x) => x.id === id) : null;
                    if (o && o.image_url) { return o.image_url; }
                }
                return null;
            },
            get shownImage() { return (this.preferOption && this.optionImage) || this.images[this.idx] || ''; },
            preferOption: false,

            go(k) {
                if (this.images.length < 2) { return; }
                this.idx = (k + this.images.length) % this.images.length;
                this.preferOption = false;
            },
            swipe(x) {
                if (this.sx === null) { return; }
                const dx = x - this.sx;
                this.sx = null;
                if (Math.abs(dx) > 40) { this.go(this.idx + (dx < 0 ? 1 : -1)); }
            },

            isSelected(g, o) { return (this.selected[g.id] || []).includes(o.id); },

            toggle(g, o) {
                if (!o.is_available) { return; }
                const cur = (this.selected[g.id] || []).slice();
                const has = cur.includes(o.id);

                if (g.selection_type !== 'multi') {
                    // เลือกเดียว: กดซ้ำเพื่อยกเลิกได้เฉพาะกลุ่มไม่บังคับ
                    this.selected[g.id] = has ? (g.min_select > 0 ? cur : []) : [o.id];
                    this.preferOption = true;
                } else if (has) {
                    this.selected[g.id] = cur.filter((id) => id !== o.id);
                } else {
                    if (g.max_select && cur.length >= g.max_select) {
                        this.groupErrors[g.id] = 'เลือกได้ไม่เกิน ' + g.max_select + ' อย่าง';
                        return;
                    }
                    cur.push(o.id);
                    this.selected[g.id] = cur;
                }
                this.groupErrors[g.id] = '';
            },

            validate() {
                let firstBad = null;
                this.groups.forEach((g) => {
                    const n = (this.selected[g.id] || []).length;
                    if (n < (g.min_select || 0)) {
                        this.groupErrors[g.id] = g.min_select === 1 ? 'กรุณาเลือก ' + g.name : 'กรุณาเลือก ' + g.name + ' อย่างน้อย ' + g.min_select + ' อย่าง';
                        firstBad = firstBad || g;
                    }
                });
                if (firstBad) {
                    const el = document.getElementById('grp-' + firstBad.id);
                    if (el) { el.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
                    window.ts.notify('กรุณาเลือกตัวเลือกให้ครบก่อนสั่ง', 'error');
                    return false;
                }
                return true;
            },

            optionIds() {
                const ids = [];
                Object.keys(this.selected).forEach((k) => (this.selected[k] || []).forEach((id) => ids.push(id)));
                return ids;
            },

            async add(buyNow) {
                if (cfg.isGuest) {
                    window.ts.notify('กรุณาเข้าสู่ระบบก่อนสั่งซื้อ', 'info');
                    setTimeout(() => { window.location.href = cfg.loginUrl; }, 600);
                    return;
                }
                if (this.adding || !this.validate()) { return; }
                this.adding = buyNow ? 'buy' : 'cart';

                const r = await window.ts.post(cfg.cartAddUrl, {
                    listing_id: cfg.listingId,
                    quantity: Math.min(this.maxQty, Math.max(1, parseInt(this.qty) || 1)),
                    option_ids: this.optionIds(),
                    note: this.note || null
                });

                if (!r.ok) {
                    this.adding = false;
                    window.ts.notify(r.message, 'error');
                    return;
                }
                if (r.data && typeof r.data.items_count !== 'undefined') {
                    window.dispatchEvent(new CustomEvent('ts-cart-count', { detail: { count: r.data.items_count } }));
                }
                if (buyNow) {
                    window.location.href = cfg.checkoutUrl;
                    return;
                }
                this.adding = false;
                window.ts.notify('ใส่ตะกร้าแล้ว — แตะ "ตะกร้า" ด้านบนเพื่อสั่งซื้อ', 'success');
            },

            async checkDelivery() {
                this.quoting = true;
                this.quoteError = '';
                try {
                    const p = await window.ts.geo();
                    const r = await window.ts.get(cfg.quoteUrl, { listing_id: cfg.listingId, latitude: p.lat, longitude: p.lng, quantity: this.qty });
                    if (r.data && r.data.available) {
                        this.quote = r.data;
                    } else {
                        this.quote = null;
                        this.quoteError = r.message || 'คำนวณค่าส่งไม่สำเร็จ';
                    }
                } catch (e) {
                    this.quoteError = (e && e.message) || 'หาตำแหน่งไม่ได้';
                } finally {
                    this.quoting = false;
                }
            },

            async toggleFollow() {
                if (cfg.isGuest) {
                    window.location.href = cfg.loginUrl;
                    return;
                }
                if (this.followBusy) { return; }
                this.followBusy = true;
                const r = this.follow.following
                    ? await window.ts.del(this.follow.unfollowUrl)
                    : await window.ts.post(this.follow.followUrl);
                this.followBusy = false;
                if (!r.ok) {
                    window.ts.notify(r.message, 'error');
                    return;
                }
                this.follow.following = !!r.data.is_following;
                this.follow.count = Number(r.data.followers_count) || 0;
                window.ts.notify(r.message, 'success');
            }
        };
    };
</script>
@endpush
