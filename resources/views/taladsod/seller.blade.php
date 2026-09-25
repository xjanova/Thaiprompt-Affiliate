{{--
 | หน้าร้านตลาดสด (taladsod.seller) — ธีม V4 (frontend-v4)
 | Controller: FreshMarket\HomeController@seller
 | ตัวแปร: $seller (user), $listings (paginator + category + option_groups_count), $isOwner,
 |         $presence (presencePayload), $isFollowing, $followersCount, $shopEndpoints{location, follow, unfollow}
 | ตำแหน่งร้าน: poll GET shopEndpoints.location ทุก 15 วินาที (ร้านส่งตำแหน่งสด) / 60 วินาที (เปิดอยู่) / 120 วินาที (ปิดอยู่)
 |   → {data:{shop_id, is_open, is_mobile, location{latitude,longitude,label,updated_at,is_live,source}|null, closes_at, closed_message}}
 | ติดตามร้าน: POST/DELETE shopEndpoints.follow|unfollow (JSON) → {data:{is_following, followers_count}}
 --}}
@extends('layouts.frontend-v4')

@section('title', $seller->shop_name.' · ตลาดสดไทยพร้อม')
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags((string) ($seller->shop_description ?: 'ร้าน '.$seller->shop_name.' ในตลาดสดไทยพร้อม สั่งออนไลน์ รับเองหรือให้ไรเดอร์ส่ง')), 155))

@php
    $ui = \App\Support\TaladsodWebUi::class;
    $open = (bool) ($presence['is_open'] ?? false);
    $live = (bool) ($presence['live_location_sharing'] ?? false);
    $location = $presence['location'] ?? null;
    $mobile = $seller->isMobileShop();

    $liveCfg = [
        'locationUrl' => $shopEndpoints['location'],
        'open' => $open,
        'live' => $live,
        'location' => $location,
        'closesAt' => $presence['closes_at'] ?? null,
        'mobile' => $mobile,
        'isOwner' => (bool) $isOwner,
        'isGuest' => ! auth()->check(),
        'loginUrl' => route('taladsod.login-continue', ['to' => '/taladsod/seller/'.$seller->id]),
        'follow' => [
            'following' => (bool) $isFollowing,
            'count' => (int) $followersCount,
            'followUrl' => $shopEndpoints['follow'],
            'unfollowUrl' => $shopEndpoints['unfollow'],
        ],
        'shareUrl' => route('taladsod.seller', $seller->id),
        'shareTitle' => $seller->shop_name,
    ];
@endphp

@section('content')
<x-theme-v4.shop-kit />
@include('taladsod.partials.kit')
<x-theme-v4.leaflet />
<x-theme-v4.public-header active="taladsod" :search="true" :search-action="route('taladsod.search')" search-name="q"
                          search-placeholder="ค้นหาของสด อาหาร หรือร้านใกล้บ้าน..." :suggest="false" />

<main class="ts-scope" style="flex:1; padding-bottom:44px;" x-data="tsShopLive({{ \Illuminate\Support\Js::from($liveCfg) }})">
    <div class="sf-wrap">
        @include('taladsod.partials.nav', ['active' => null])

        @if($isOwner)
            <div class="sf-note sf-note-info" style="margin-bottom:14px; display:flex; flex-wrap:wrap; gap:10px; align-items:center; justify-content:space-between;">
                <span><i class="fas fa-eye" aria-hidden="true"></i> คุณกำลังดูหน้าร้านของตัวเองแบบที่ลูกค้าเห็น</span>
                <a href="{{ route('taladsod.seller.dashboard') }}" class="ts-btn3d sm ts-tone-gold"><i class="fas fa-store" aria-hidden="true"></i> ไปหน้าจัดการร้าน</a>
            </div>
        @endif

        {{-- ════════ หัวร้าน ════════ --}}
        <section class="ts-hero" style="min-height:0;">
            <img class="ts-hero-img" src="{{ asset('images/taladsod/banner-market.webp') }}" alt="">
            <div class="ts-hero-in" style="min-height:0; max-width:none; padding-top:clamp(60px, 12vw, 120px);">
                <div style="display:flex; gap:16px; align-items:flex-end; flex-wrap:wrap;">
                    <span class="ts-avatar lg" style="border:3px solid var(--ts-on);">
                        @if($seller->shop_image)
                            <img src="{{ $seller->shop_image }}" alt="รูปร้าน {{ $seller->shop_name }}">
                        @else
                            {{ $ui::initial($seller->shop_name) }}
                        @endif
                    </span>
                    <div style="flex:1 1 240px; min-width:0;">
                        <h1 class="ts-hero-title" style="font-size:clamp(24px,4.6vw,36px);">
                            {{ $seller->shop_name }}
                            @if($seller->is_verified)
                                <i class="fas fa-circle-check" style="font-size:.6em; vertical-align:middle;" title="ร้านยืนยันแล้ว" aria-label="ร้านยืนยันแล้ว"></i>
                            @endif
                        </h1>
                        <div class="ts-row" style="gap:6px; margin-top:8px;">
                            <span class="ts-pill solid" :class="open ? 'ts-tone-ok' : 'ts-tone-muted'">
                                <span class="ts-dot" style="background:var(--ts-on);" :class="open && live ? 'live' : ''"></span>
                                <span x-text="open ? (live ? 'เปิดอยู่ · ตำแหน่งสด' : 'เปิดอยู่') : 'ปิดอยู่'">{{ $open ? ($live ? 'เปิดอยู่ · ตำแหน่งสด' : 'เปิดอยู่') : 'ปิดอยู่' }}</span>
                            </span>
                            @if($mobile)
                                <span class="ts-pill solid ts-tone-deep"><i class="fas fa-cart-flatbed" aria-hidden="true"></i> รถเข็น/ตลาดนัด</span>
                            @endif
                            <span class="ts-pill solid ts-tone-gold"><i class="fas fa-star" aria-hidden="true"></i> {{ number_format((float) $seller->rating_average, 1) }} ({{ number_format((int) $seller->rating_count) }})</span>
                            <span class="ts-pill solid ts-tone-info"><i class="fas fa-bag-shopping" aria-hidden="true"></i> ขายแล้ว {{ number_format((int) $seller->total_sales) }}</span>
                        </div>
                    </div>
                    <div class="ts-row" style="gap:8px;">
                        @unless($isOwner)
                            <button type="button" class="ts-btn3d" :class="follow.following ? 'soft' : 'ts-tone-bad'" x-on:click="toggleFollow()" :disabled="followBusy"
                                    :aria-pressed="follow.following ? 'true' : 'false'">
                                <i class="fas" :class="followBusy ? 'fa-circle-notch ts-spin' : (follow.following ? 'fa-heart' : 'fa-heart-circle-plus')" aria-hidden="true"></i>
                                <span x-text="follow.following ? 'ติดตามแล้ว' : 'ติดตามร้าน'">{{ $isFollowing ? 'ติดตามแล้ว' : 'ติดตามร้าน' }}</span>
                                <span class="ts-num" style="font-size:12px; opacity:.85;" x-text="follow.count">{{ $followersCount }}</span>
                            </button>
                        @endunless
                        <button type="button" class="ts-btn3d soft" x-on:click="share()" aria-label="แชร์ร้านนี้"><i class="fas fa-share-nodes" aria-hidden="true"></i></button>
                    </div>
                </div>
            </div>
        </section>

        {{-- ════════ สถานะ + ตำแหน่งร้าน ════════ --}}
        <section class="ts-grid" style="--ts-min:300px; margin-top:18px; align-items:start;">
            <div class="tp-card ts-stack">
                <h2 class="ts-h2"><i class="fas fa-location-dot" style="color:var(--accent2);" aria-hidden="true"></i> ร้านอยู่ที่ไหนตอนนี้</h2>

                <div x-show="open && location" @if(! ($open && $location)) x-cloak @endif class="ts-stack" style="gap:10px;">
                    <div class="ts-map" x-ref="map" aria-label="แผนที่ตำแหน่งร้าน"></div>
                    <div class="ts-row" style="justify-content:space-between; gap:8px;">
                        <span class="ts-muted ts-small" style="min-width:0;">
                            <i class="fas fa-map-pin" aria-hidden="true"></i> <span x-text="(location && location.label) || 'ตำแหน่งร้าน'">{{ $location['label'] ?? 'ตำแหน่งร้าน' }}</span>
                            <template x-if="live && location && location.updated_at">
                                <span> · อัปเดต <span x-text="window.ts.timeAgo(location.updated_at)"></span></span>
                            </template>
                        </span>
                        <a :href="directionsUrl" target="_blank" rel="noopener" class="tp-btn tp-btn-sm" x-show="location"><i class="fas fa-diamond-turn-right" aria-hidden="true"></i> นำทาง</a>
                    </div>
                    <p class="ts-help" x-show="live" style="margin:0;"><span class="ts-dot live ts-tone-ok" style="display:inline-block; margin-right:6px;"></span>ร้านเคลื่อนที่ส่งตำแหน่งสด — แผนที่อัปเดตทุก 15 วินาที</p>
                </div>

                <div x-show="open && !location" @if(! ($open && ! $location)) x-cloak @endif class="sf-note sf-note-info">
                    ร้านเปิดอยู่แต่ยังไม่ได้ปักหมุดตำแหน่ง — สั่งให้ไรเดอร์ส่ง หรือทักร้านได้หลังสั่งซื้อ
                </div>

                <div x-show="!open" @if($open) x-cloak @endif class="ts-empty tp-inset" style="border-radius:18px; padding:26px 16px;">
                    <span class="em" aria-hidden="true">🌙</span>
                    <b style="font-size:15px;">ร้านปิดอยู่</b>
                    <span class="ts-muted ts-small">{{ \App\Models\FreshMarketSeller::CLOSED_MESSAGE }}</span>
                    @unless($isOwner)
                        <button type="button" class="ts-btn3d sm ts-tone-bad" x-show="!follow.following" x-on:click="toggleFollow()" :disabled="followBusy">
                            <i class="fas fa-bell" aria-hidden="true"></i> แจ้งเตือนฉันเมื่อร้านเปิด
                        </button>
                        <span class="ts-pill ts-tone-ok" x-show="follow.following"><i class="fas fa-bell" aria-hidden="true"></i> เปิดแจ้งเตือนแล้ว</span>
                    @endunless
                </div>
            </div>

            <div class="tp-card ts-stack">
                <h2 class="ts-h2"><i class="fas fa-store" style="color:var(--accent2);" aria-hidden="true"></i> เกี่ยวกับร้าน</h2>
                @if($seller->shop_description)
                    <p style="margin:0; font-size:14px; line-height:1.7; overflow-wrap:anywhere;">{!! nl2br(e($seller->shop_description)) !!}</p>
                @else
                    <p class="ts-muted" style="margin:0;">ร้านนี้ยังไม่ได้เขียนแนะนำร้าน</p>
                @endif
                <div class="ts-stack" style="gap:2px;">
                    <div class="ts-kv"><span>ประเภทร้าน</span><b>{{ $mobile ? 'รถเข็น / ตลาดนัด (ย้ายที่ได้)' : 'ร้านประจำที่' }}</b></div>
                    @if(! $mobile && ($seller->district || $seller->province))
                        <div class="ts-kv"><span>พื้นที่</span><b>{{ trim(($seller->district ?? '').' '.($seller->province ?? '')) }}</b></div>
                    @elseif($seller->province)
                        <div class="ts-kv"><span>จังหวัด</span><b>{{ $seller->province }}</b></div>
                    @endif
                    <div class="ts-kv" x-show="open && closesAt"><span>ปิดร้านเวลา</span><b x-text="window.ts.hhmm(closesAt) + ' น.'">{{ ! empty($presence['closes_at']) ? $ui::time($presence['closes_at']).' น.' : '' }}</b></div>
                    <div class="ts-kv"><span>ผู้ติดตาม</span><b class="ts-num" x-text="follow.count">{{ number_format((int) $followersCount) }}</b></div>
                    <div class="ts-kv"><span>เปิดร้านกับเราตั้งแต่</span><b>{{ $ui::date($seller->created_at, false) }}</b></div>
                </div>
            </div>
        </section>

        {{-- ════════ เมนู / สินค้า ════════ --}}
        <section class="sf-section" aria-labelledby="ts-menu-h">
            <div class="sf-section-h">
                <div>
                    <div class="sf-kicker">สั่งได้เลย</div>
                    <h2 id="ts-menu-h" class="sf-title">เมนูและสินค้าของร้าน</h2>
                </div>
                <span class="ts-pill ts-tone-gold">{{ number_format($listings->total()) }} รายการ</span>
            </div>
            @if($listings->count() > 0)
                <div class="sf-grid">
                    @foreach($listings as $listing)
                        @include('taladsod.partials.listing-card', ['listing' => $listing, 'shop' => $seller, 'hideShop' => true])
                    @endforeach
                </div>
                @if($listings->hasPages())
                    <div style="margin-top:20px;">{{ $listings->links('vendor.pagination.tp-v4') }}</div>
                @endif
            @else
                <div class="tp-card ts-empty">
                    <span class="em" aria-hidden="true">📦</span>
                    <b>ร้านยังไม่มีสินค้าที่เปิดขาย</b>
                    <span class="ts-muted ts-small">ติดตามร้านไว้ เดี๋ยวมีเมนูใหม่จะเห็นก่อนใคร</span>
                </div>
            @endif
        </section>
    </div>
</main>

<x-theme-v4.public-footer />
@endsection

@push('scripts')
<script>
    /**
     * หน้าร้าน: สถานะเปิด/ปิด + ตำแหน่งร้านสด (poll) + ติดตามร้าน + แชร์ร้าน
     * หยุด poll เมื่อแท็บถูกซ่อน (ประหยัดแบต/เน็ต) แล้วกลับมาทำต่อเมื่อเปิดดู
     */
    window.tsShopLive = function (cfg) {
        let map = null;
        let marker = null;
        let timer = null;

        return {
            open: !!cfg.open, live: !!cfg.live, location: cfg.location || null, closesAt: cfg.closesAt || null,
            follow: cfg.follow, followBusy: false,

            get directionsUrl() {
                if (!this.location) { return '#'; }
                return 'https://www.google.com/maps/dir/?api=1&destination=' + encodeURIComponent(this.location.latitude + ',' + this.location.longitude);
            },

            init() {
                this.$nextTick(() => this.drawMap());
                this.schedule();
                document.addEventListener('visibilitychange', () => {
                    if (document.visibilityState === 'visible') { this.poll(); } else { clearTimeout(timer); }
                });
            },

            interval() { return this.open ? (this.live ? 15000 : 60000) : 120000; },

            schedule() {
                clearTimeout(timer);
                timer = setTimeout(() => this.poll(), this.interval());
            },

            async poll() {
                clearTimeout(timer);
                if (document.visibilityState !== 'visible') { return; }
                const r = await window.ts.get(cfg.locationUrl);
                if (r.ok && r.data) {
                    const wasOpen = this.open;
                    this.open = !!r.data.is_open;
                    this.location = r.data.location || null;
                    this.live = !!(this.location && this.location.is_live);
                    this.closesAt = r.data.closes_at || null;
                    if (wasOpen !== this.open) {
                        window.ts.notify(this.open ? 'ร้านเปิดแล้ว! สั่งได้เลย' : 'ร้านเพิ่งปิด', this.open ? 'success' : 'info');
                        // ป้าย "ร้านปิดอยู่" บนการ์ดเมนูมาจากเซิร์ฟเวอร์ → โหลดหน้าใหม่ให้ตรงสถานะ
                        setTimeout(() => window.location.reload(), 1500);
                        return;
                    }
                    this.$nextTick(() => this.drawMap());
                }
                this.schedule();
            },

            drawMap() {
                if (!this.open || !this.location || !window.tpMap || !window.tpMap.ready()) { return; }
                const lat = Number(this.location.latitude);
                const lng = Number(this.location.longitude);
                if (!map) {
                    map = window.tpMap.create(this.$refs.map, lat, lng, 16);
                    if (!map) { return; }
                    [250, 900].forEach((ms) => setTimeout(() => map && map.invalidateSize(), ms));
                }
                map.invalidateSize();
                const icon = window.L.divIcon({
                    className: '',
                    html: '<div class="tp-map-pin is-shop' + (this.live ? ' ts-pin-live' : '') + '"><i class="fas ' + (cfg.mobile ? 'fa-cart-flatbed' : 'fa-store') + '"></i></div>',
                    iconSize: [40, 40], iconAnchor: [20, 40]
                });
                if (!marker) {
                    marker = window.L.marker([lat, lng], { icon: icon }).addTo(map);
                } else {
                    marker.setLatLng([lat, lng]);
                    marker.setIcon(icon);
                }
                map.panTo([lat, lng]);
            },

            async toggleFollow() {
                if (cfg.isGuest) {
                    window.ts.notify('กรุณาเข้าสู่ระบบเพื่อติดตามร้าน', 'info');
                    setTimeout(() => { window.location.href = cfg.loginUrl; }, 600);
                    return;
                }
                if (this.followBusy) { return; }
                this.followBusy = true;
                const r = this.follow.following ? await window.ts.del(this.follow.unfollowUrl) : await window.ts.post(this.follow.followUrl);
                this.followBusy = false;
                if (!r.ok) {
                    window.ts.notify(r.message, 'error');
                    return;
                }
                this.follow.following = !!r.data.is_following;
                this.follow.count = Number(r.data.followers_count) || 0;
                window.ts.notify(r.message, 'success');
            },

            async share() {
                try {
                    if (navigator.share) {
                        await navigator.share({ title: cfg.shareTitle, url: cfg.shareUrl });
                        return;
                    }
                    await navigator.clipboard.writeText(cfg.shareUrl);
                    window.ts.notify('คัดลอกลิงก์ร้านแล้ว', 'success');
                } catch (e) {
                    // ผู้ใช้กดยกเลิกการแชร์ — ไม่ต้องแจ้งอะไร
                }
            }
        };
    };
</script>
@endpush
