{{--
 | หน้าร้านวันนี้ (แดชบอร์ดผู้ขายตลาดสด — taladsod.seller.dashboard) — ธีม V4 (user-v4)
 | Controller: FreshMarket\HomeController@sellerDashboard
 | ตัวแปร: $seller (listings 10), $recentOrders, $stats (object), $pendingOrders, $gpDebt, $gpRate, $gpFree, $maxCashbackPercent,
 |         $referralLinks{token, web_url, line_url}|null, $subscription{type, expires_at, is_paid, monthly_fee, fee_mode, can_create_listing},
 |         $presence (presencePayload(true) + has_fixed_location, followers_count, live_send_interval_seconds),
 |         $presenceEndpoints{presence, open, location, close, mobile_mode}
 | เปิดร้าน: POST open {latitude?, longitude?, location_label?, closes_at? (HH:MM), live_location_sharing}
 | ตำแหน่งสด: POST location {latitude, longitude, location_label?} ทุก 30 วินาทีระหว่างเปิดหน้านี้ · ปิดร้าน: POST close · โหมดร้าน: POST mobile_mode {is_mobile}
 | ออเดอร์ใหม่: poll GET taladsod.seller.orders.poll ทุก 20 วินาที
 --}}
@extends('layouts.user-v4')

@section('title', 'หน้าร้านวันนี้ · '.$seller->shop_name)

@php
    $ui = \App\Support\TaladsodWebUi::class;
    $visible = $seller->isVisibleToBuyers();
    $closesHm = ! empty($presence['closes_at']) ? $ui::time($presence['closes_at']) : '';
    $latestOrderId = (int) ($recentOrders->max('id') ?? 0);

    $presenceCfg = [
        'p' => $presence,
        'endpoints' => $presenceEndpoints,
        'pollUrl' => route('taladsod.seller.orders.poll'),
        'ordersUrl' => route('taladsod.seller.orders', ['status' => 'pending']),
        'pending' => (int) $pendingOrders,
        'latestOrderId' => $latestOrderId,
        'closesAt' => $closesHm !== '—' ? $closesHm : '',
        'fixed' => $seller->hasPickupLocation() ? ['lat' => (float) $seller->latitude, 'lng' => (float) $seller->longitude] : null,
    ];
@endphp

@section('content')
<x-theme-v4.shop-kit />
@include('taladsod.partials.kit')
<x-theme-v4.leaflet />

<div class="ts-scope ts-stack" style="gap:16px;" x-data="tsPresence({{ \Illuminate\Support\Js::from($presenceCfg) }})">
    @include('taladsod.partials.seller-nav', ['seller' => $seller, 'active' => 'dashboard'])

    {{-- ════════ หัวร้าน ════════ --}}
    <div class="ts-row" style="justify-content:space-between; gap:12px;">
        <div class="ts-row" style="gap:12px; flex-wrap:nowrap; min-width:0;">
            <span class="ts-avatar">
                @if($seller->shop_image)
                    <img src="{{ $seller->shop_image }}" alt="">
                @else
                    {{ $ui::initial($seller->shop_name) }}
                @endif
            </span>
            <div style="min-width:0;">
                <h1 class="ts-h1" style="font-size:clamp(20px,4vw,26px);">{{ $seller->shop_name }}</h1>
                <div class="ts-row" style="gap:6px; margin-top:4px;">
                    <span class="ts-pill {{ $seller->status_key === 'active' ? 'ts-tone-ok' : ($seller->status_key === 'unverified' ? 'ts-tone-warn' : 'ts-tone-bad') }}">{{ $seller->status_label }}</span>
                    <span class="ts-pill ts-tone-bad"><i class="fas fa-heart" aria-hidden="true"></i> <span x-text="p.followers_count">{{ (int) ($presence['followers_count'] ?? 0) }}</span> ผู้ติดตาม</span>
                    <span class="ts-pill ts-tone-gold"><span class="ts-star-view">★</span> {{ number_format((float) $stats->rating, 1) }} ({{ number_format((int) $stats->rating_count) }})</span>
                </div>
            </div>
        </div>
        <a href="{{ route('taladsod.seller', $seller->id) }}" class="tp-btn" target="_blank" rel="noopener"><i class="fas fa-store" aria-hidden="true"></i> ดูหน้าร้านแบบลูกค้า</a>
    </div>

    @if(! $visible)
        <div class="sf-note sf-note-warn" role="status">
            <i class="fas fa-hourglass-half" aria-hidden="true"></i>
            {{ $seller->is_suspended ? 'ร้านถูกระงับชั่วคราว — ลูกค้ามองไม่เห็นร้าน กรุณาติดต่อทีมงาน' : 'ร้านรอแอดมินยืนยัน — ลงเมนูเตรียมไว้ได้เลย ลูกค้าจะเห็นร้านเมื่อยืนยันแล้ว' }}
        </div>
    @endif

    {{-- ════════ แจ้งออเดอร์ใหม่ ════════ --}}
    <a href="{{ route('taladsod.seller.orders', ['status' => 'pending']) }}" class="ts-refresh" x-show="pending > 0" @if($pendingOrders <= 0) x-cloak @endif style="text-decoration:none; position:relative; top:0;">
        <span><i class="fas fa-bell" aria-hidden="true"></i> มีออเดอร์รอรับ <b class="ts-num" x-text="pending">{{ (int) $pendingOrders }}</b> รายการ — กดรับก่อนหมดเวลา</span>
        <span class="ts-btn3d soft sm">ไปรับออเดอร์ <i class="fas fa-arrow-right" aria-hidden="true"></i></span>
    </a>

    {{-- ════════ การ์ดเปิด/ปิดร้าน ════════ --}}
    <section class="tp-card" style="padding:0; overflow:hidden;" aria-labelledby="sd-presence-h">
        <div style="padding:clamp(16px,3vw,24px);"
             :style="{ background: p.is_open ? 'linear-gradient(135deg, color-mix(in srgb, var(--ts-ok) 16%, transparent), transparent 70%)' : 'linear-gradient(135deg, var(--a1soft), transparent 70%)' }">
            <div class="ts-row" style="justify-content:space-between; gap:12px;">
                <div style="min-width:0;">
                    <h2 id="sd-presence-h" class="ts-h2" style="font-size:18px;">
                        <span class="ts-dot" :class="p.is_open ? 'live ts-tone-ok' : 'ts-tone-muted'"></span>
                        <span x-text="p.is_open ? 'ร้านเปิดอยู่' : 'ร้านปิดอยู่'">{{ ! empty($presence['is_open']) ? 'ร้านเปิดอยู่' : 'ร้านปิดอยู่' }}</span>
                    </h2>
                    <p class="ts-muted" style="margin:6px 0 0; font-size:13px;" x-show="p.is_open">
                        <span x-text="p.opened_at ? ('เปิดตั้งแต่ ' + window.ts.hhmm(p.opened_at) + ' น.') : 'เปิดรับออเดอร์ตามปกติ'"></span>
                        <template x-if="p.closes_at"><span> · ปิดอัตโนมัติ <span x-text="window.ts.hhmm(p.closes_at)"></span> น.</span></template>
                    </p>
                    <p class="ts-muted" style="margin:6px 0 0; font-size:13px;" x-show="!p.is_open">ลูกค้าเห็นร้านแต่สั่งไม่ได้ · ผู้ติดตามได้แจ้งเตือนทันทีที่คุณเปิดร้าน</p>
                </div>
                <label class="ts-switch" title="ร้านเคลื่อนที่ (รถเข็น/ตลาดนัด)">
                    <input type="checkbox" :checked="p.is_mobile" x-on:change="askMobile($event.target.checked); $event.target.checked = p.is_mobile" :disabled="busy !== ''">
                    <span class="track"></span>
                    <span style="font-size:13px; font-weight:700;"><i class="fas fa-cart-flatbed" aria-hidden="true"></i> ร้านเคลื่อนที่</span>
                </label>
            </div>

            {{-- ปิดอยู่: ตั้งค่าแล้วกดเปิด --}}
            <div x-show="!p.is_open" class="ts-stack" style="margin-top:16px; gap:12px;">
                <div class="ts-grid" style="--ts-min:200px; gap:10px;">
                    <div x-show="p.is_mobile">
                        <label class="ts-label" for="sd-label">วันนี้ขายที่ไหน</label>
                        <input id="sd-label" type="text" class="tp-input" maxlength="150" x-model="label" placeholder="เช่น ตลาดนัดหน้าโรงเรียน, ปากซอย 5">
                    </div>
                    <div>
                        <label class="ts-label" for="sd-closes">ปิดร้านเวลา <span class="ts-muted" style="font-weight:600;">(ไม่บังคับ)</span></label>
                        <input id="sd-closes" type="time" class="tp-input" x-model="closesAt">
                    </div>
                </div>
                <label class="ts-switch" x-show="p.is_mobile">
                    <input type="checkbox" x-model="liveWanted">
                    <span class="track"></span>
                    <span style="font-size:13.5px; font-weight:700;">ส่งตำแหน่งสดระหว่างขาย (รถเข็นที่เดินขาย)</span>
                </label>
                <button type="button" class="ts-btn3d ts-tone-ok lg block" x-on:click="openShop()" :disabled="busy !== ''">
                    <i class="fas" :class="busy === 'open' ? 'fa-circle-notch ts-spin' : 'fa-location-crosshairs'" aria-hidden="true"></i>
                    <span x-text="p.is_mobile ? 'เปิดร้านที่นี่วันนี้' : 'เปิดร้าน'">{{ ! empty($presence['is_mobile']) ? 'เปิดร้านที่นี่วันนี้' : 'เปิดร้าน' }}</span>
                </button>
                <p class="ts-help" style="margin:0;" x-show="p.is_mobile">ระบบใช้ GPS ของเครื่องนี้เป็นตำแหน่งร้าน — ลูกค้าเห็นตำแหน่งเฉพาะตอนร้านเปิด ปิดร้านแล้วตำแหน่งจะไม่แสดง</p>
            </div>

            {{-- เปิดอยู่ --}}
            <div x-show="p.is_open" x-cloak class="ts-stack" style="margin-top:16px; gap:12px;">
                <div class="ts-grid" style="--ts-min:260px; gap:12px; align-items:start;">
                    <div class="ts-stack" style="gap:10px;">
                        <div class="ts-map sm" x-ref="map" x-show="p.location" aria-label="แผนที่ตำแหน่งร้านตอนนี้"></div>
                        <div class="sf-note sf-note-warn" x-show="!p.location">ร้านยังไม่มีตำแหน่ง — ลูกค้าจะหาร้านบนแผนที่ไม่เจอ</div>
                        <span class="ts-muted ts-small" x-show="p.location">
                            <i class="fas fa-map-pin" aria-hidden="true"></i> <span x-text="(p.location && p.location.label) || (p.is_mobile ? 'ตำแหน่งที่เปิดร้าน' : 'ที่อยู่ร้าน')"></span>
                        </span>
                    </div>
                    <div class="ts-stack" style="gap:10px;">
                        <template x-if="p.is_mobile">
                            <div class="ts-stack" style="gap:10px;">
                                <label class="ts-switch">
                                    <input type="checkbox" :checked="p.live_location_sharing" x-on:change="setLive($event.target)" :disabled="busy !== ''">
                                    <span class="track"></span>
                                    <span style="font-size:13.5px; font-weight:700;">ส่งตำแหน่งสดทุก 30 วินาที</span>
                                </label>
                                <div class="tp-inset" style="border-radius:14px; padding:10px 12px;" x-show="p.live_location_sharing">
                                    <span class="ts-small"><span class="ts-dot live ts-tone-ok" style="display:inline-block; margin-right:6px;"></span>
                                        ส่งล่าสุด <b x-text="lastSent ? window.ts.timeAgo(lastSent) : 'รอส่งครั้งแรก'"></b></span>
                                    <p class="ts-help" style="margin:4px 0 0;">เปิดหน้านี้ค้างไว้ระหว่างขาย (หน้าจอจะไม่ดับเอง) — ถ้าตำแหน่งเงียบเกิน {{ \App\Models\FreshMarketSeller::LIVE_LOCATION_STALE_MINUTES }} นาที ระบบปิดร้านให้อัตโนมัติ</p>
                                    <p class="ts-err" style="margin:4px 0 0;" x-show="liveWarning" x-text="liveWarning"></p>
                                </div>
                                <button type="button" class="ts-btn3d ts-tone-info" x-on:click="updateLocationNow()" :disabled="busy !== ''">
                                    <i class="fas" :class="busy === 'loc' ? 'fa-circle-notch ts-spin' : 'fa-location-arrow'" aria-hidden="true"></i> ย้ายร้านมาที่นี่ (อัปเดตตำแหน่ง)
                                </button>
                                <input type="text" class="tp-input" maxlength="150" x-model="label" placeholder="ชื่อจุดขาย เช่น หน้าตลาดนัดเย็น" aria-label="ชื่อจุดขาย">
                            </div>
                        </template>
                        <div class="ts-row" style="gap:8px; flex-wrap:nowrap;">
                            <input type="time" class="tp-input" x-model="closesAt" aria-label="เวลาปิดร้าน" style="flex:1;">
                            <button type="button" class="tp-btn" x-on:click="saveClosesAt()" :disabled="busy !== ''"><i class="fas fa-clock" aria-hidden="true"></i> ตั้งเวลาปิด</button>
                        </div>
                        <button type="button" class="ts-btn3d ts-tone-bad block" x-on:click="confirmClose = true" :disabled="busy !== ''">
                            <i class="fas fa-power-off" aria-hidden="true"></i> ปิดร้าน
                        </button>
                    </div>
                </div>
            </div>

            <p class="ts-err" x-show="error" x-text="error" x-cloak role="alert"></p>
        </div>
    </section>

    {{-- ════════ ตัวเลข ════════ --}}
    <div class="ts-grid" style="--ts-min:160px;">
        <a href="{{ route('taladsod.seller.orders', ['status' => 'pending']) }}" class="tp-card tp-card-hover ts-stat ts-tone-warn" style="text-decoration:none; color:inherit;">
            <span class="ic"><i class="fas fa-bell" aria-hidden="true"></i></span>
            <span class="lbl">รอรับออเดอร์</span>
            <span class="val" x-text="pending">{{ number_format((int) $stats->pending_orders) }}</span>
            <span class="sub">แตะเพื่อรับออเดอร์</span>
        </a>
        <a href="{{ route('taladsod.seller.orders') }}" class="tp-card tp-card-hover ts-stat ts-tone-info" style="text-decoration:none; color:inherit;">
            <span class="ic"><i class="fas fa-fire-burner" aria-hidden="true"></i></span>
            <span class="lbl">กำลังทำ/ส่ง</span>
            <span class="val">{{ number_format((int) $stats->active_orders) }}</span>
            <span class="sub">ออเดอร์ที่ยังไม่จบ</span>
        </a>
        <a href="{{ route('taladsod.seller.earnings') }}" class="tp-card tp-card-hover ts-stat ts-tone-ok" style="text-decoration:none; color:inherit;">
            <span class="ic"><i class="fas fa-sack-dollar" aria-hidden="true"></i></span>
            <span class="lbl">ยอดขายรวม</span>
            <span class="val">฿{{ $ui::money($stats->total_revenue) }}</span>
            <span class="sub">{{ number_format((int) $stats->total_sales) }} ออเดอร์สำเร็จ · ดูรายได้</span>
        </a>
        <a href="{{ route('taladsod.seller.listings') }}" class="tp-card tp-card-hover ts-stat ts-tone-gold" style="text-decoration:none; color:inherit;">
            <span class="ic"><i class="fas fa-bowl-food" aria-hidden="true"></i></span>
            <span class="lbl">สินค้า/เมนู</span>
            <span class="val">{{ number_format((int) $stats->total_listings) }}</span>
            <span class="sub">จัดการเมนู</span>
        </a>
    </div>

    <div class="ts-grid" style="--ts-min:320px; align-items:start;">
        {{-- ════════ ออเดอร์ล่าสุด ════════ --}}
        <section class="tp-card ts-stack" aria-labelledby="sd-orders-h">
            <div class="ts-row" style="justify-content:space-between;">
                <h2 id="sd-orders-h" class="ts-h2"><i class="fas fa-receipt" style="color:var(--accent2);" aria-hidden="true"></i> ออเดอร์ล่าสุด</h2>
                <a href="{{ route('taladsod.seller.orders') }}" class="ts-link ts-small">ดูทั้งหมด <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
            </div>
            @forelse($recentOrders->take(6) as $order)
                @php $first = $order->lineItems()->first(); @endphp
                <a href="{{ route('taladsod.seller.orders.show', $order) }}" class="ts-line" style="text-decoration:none; color:var(--ink); align-items:center;">
                    <span class="tp-tile ts-tone-{{ $ui::orderTone($order->order_status) }}" style="width:40px; height:40px; border-radius:13px; background:linear-gradient(135deg, color-mix(in srgb, var(--tone) 72%, var(--ts-on)), var(--tone));">
                        <i class="fas {{ $ui::orderIcon($order->order_status) }}" aria-hidden="true"></i>
                    </span>
                    <span style="flex:1; min-width:0;">
                        <b style="display:block; font-size:13.5px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">#{{ $order->order_number }} · {{ $first?->title }}{{ $order->lineItems()->count() > 1 ? ' +'.($order->lineItems()->count() - 1) : '' }}</b>
                        <span class="ts-muted ts-small">{{ $order->buyer?->name ?? 'ลูกค้า' }} · {{ $ui::shortDate($order->created_at) }} · {{ $order->status_label }}</span>
                    </span>
                    <b class="ts-num" style="font-size:14px;">฿{{ $ui::money($order->total_amount) }}</b>
                </a>
            @empty
                <div class="ts-empty" style="padding:18px;">
                    <span class="em" aria-hidden="true">📭</span>
                    <span class="ts-muted">ยังไม่มีออเดอร์ — เปิดร้านแล้วชวนลูกค้ามากดติดตามร้านได้เลย</span>
                </div>
            @endforelse
        </section>

        <div class="ts-stack">
            {{-- ════════ ค่าธรรมเนียม GP ════════ --}}
            <section class="tp-card ts-stack" aria-labelledby="sd-gp-h">
                <h2 id="sd-gp-h" class="ts-h2"><i class="fas fa-percent" style="color:var(--accent2);" aria-hidden="true"></i> ค่าธรรมเนียมการขาย (GP)</h2>
                @if($gpFree)
                    <div class="sf-note sf-note-ok"><i class="fas fa-gift" aria-hidden="true"></i> <b>ฟรี GP ช่วงเปิดตัว</b> — ร้านได้รับเต็มราคาสินค้า</div>
                @else
                    <div class="ts-kv"><span>อัตรา GP ตอนนี้</span><b>{{ rtrim(rtrim(number_format((float) $gpRate, 2), '0'), '.') }}% ของยอดสินค้า</b></div>
                @endif
                @if($gpDebt > 0)
                    <div class="sf-note sf-note-warn">
                        มียอด GP ค้างชำระ <b>฿{{ $ui::money($gpDebt) }}</b> (จากออเดอร์เก็บเงินปลายทาง) — ระบบหักจากรายได้ออเดอร์ถัดไปอัตโนมัติ
                    </div>
                @endif
                <a href="{{ route('taladsod.seller.earnings') }}" class="tp-btn"><i class="fas fa-chart-column" aria-hidden="true"></i> ดูรายได้และเงินเข้า</a>
            </section>

            {{-- ════════ สมาชิกรายเดือน (เฉพาะโหมดที่ใช้สมาชิก) ════════ --}}
            @if(($subscription['fee_mode'] ?? 'percentage') !== 'percentage')
                <section class="tp-card ts-stack" aria-labelledby="sd-sub-h" x-data="{ ask: false, sending: false }">
                    <h2 id="sd-sub-h" class="ts-h2"><i class="fas fa-crown" style="color:var(--accent1);" aria-hidden="true"></i> สมาชิกร้าน</h2>
                    <div class="ts-kv"><span>แพ็กเกจ</span><b>{{ $subscription['is_paid'] ? 'สมาชิกรายเดือน' : 'ฟรี' }}</b></div>
                    @if($subscription['expires_at'])
                        <div class="ts-kv"><span>ใช้ได้ถึง</span><b>{{ $ui::date($subscription['expires_at'], false) }}</b></div>
                    @endif
                    @unless($subscription['can_create_listing'])
                        <div class="sf-note sf-note-warn">ลงขายครบโควต้าแล้ว — สมัครสมาชิกเพื่อลงขายเพิ่ม</div>
                    @endunless
                    <button type="button" class="ts-btn3d ts-tone-gold" x-on:click="ask = true"><i class="fas fa-crown" aria-hidden="true"></i> {{ $subscription['is_paid'] ? 'ต่ออายุ' : 'สมัครสมาชิก' }} ฿{{ $ui::money($subscription['monthly_fee']) }}/เดือน</button>
                    <div class="ts-dialog-bg" x-show="ask" x-cloak x-transition.opacity x-on:click.self="ask = false" x-on:keydown.escape.window="ask = false">
                        <form method="POST" action="{{ route('taladsod.seller.subscribe') }}" class="tp-card ts-dialog ts-stack" role="dialog" aria-modal="true" x-on:submit="sending = true">
                            @csrf
                            <h2 class="ts-h2">ยืนยันชำระค่าสมาชิก ฿{{ $ui::money($subscription['monthly_fee']) }}</h2>
                            <p class="ts-muted" style="margin:0; font-size:13.5px;">ระบบหักเงินจากกระเป๋าเงินของคุณทันที</p>
                            <div class="ts-row" style="justify-content:flex-end;">
                                <button type="button" class="tp-btn" x-on:click="ask = false">ยกเลิก</button>
                                <button type="submit" class="ts-btn3d ts-tone-gold sm" :disabled="sending">ยืนยันชำระ</button>
                            </div>
                        </form>
                    </div>
                </section>
            @endif

            {{-- ════════ ลิงก์ชวนลูกค้า ════════ --}}
            @if($referralLinks)
                <section class="tp-card ts-stack" aria-labelledby="sd-ref-h" x-data="{ copied: false }">
                    <h2 id="sd-ref-h" class="ts-h2"><i class="fas fa-share-nodes" style="color:var(--accent2);" aria-hidden="true"></i> ชวนลูกค้ามาที่ร้าน</h2>
                    <p class="ts-muted" style="margin:0; font-size:13px;">ส่งลิงก์นี้ให้ลูกค้าประจำ สมัครแล้วสั่งจากร้านคุณได้ทันที</p>
                    <div class="ts-row" style="gap:8px; flex-wrap:nowrap;">
                        <input type="text" class="tp-input" readonly value="{{ $referralLinks['web_url'] }}" aria-label="ลิงก์ชวนลูกค้า" x-on:focus="$event.target.select()">
                        <button type="button" class="ts-icon-btn" aria-label="คัดลอกลิงก์"
                                x-on:click="navigator.clipboard.writeText(@js($referralLinks['web_url'])).then(() => { copied = true; window.ts.notify('คัดลอกลิงก์แล้ว', 'success'); setTimeout(() => copied = false, 2000); })">
                            <i class="fas" :class="copied ? 'fa-check' : 'fa-copy'" aria-hidden="true"></i>
                        </button>
                    </div>
                    @if($referralLinks['line_url'])
                        <a href="{{ $referralLinks['line_url'] }}" target="_blank" rel="noopener" class="tp-btn"><i class="fab fa-line" aria-hidden="true"></i> ลิงก์เพิ่มเพื่อน LINE</a>
                    @endif
                </section>
            @endif
        </div>
    </div>

    {{-- ════════ เมนูของร้าน ════════ --}}
    <section class="tp-card ts-stack" aria-labelledby="sd-list-h">
        <div class="ts-row" style="justify-content:space-between;">
            <h2 id="sd-list-h" class="ts-h2"><i class="fas fa-bowl-food" style="color:var(--accent2);" aria-hidden="true"></i> เมนูของร้าน</h2>
            <div class="ts-row" style="gap:8px;">
                <a href="{{ route('taladsod.seller.listings') }}" class="tp-btn tp-btn-sm">ทั้งหมด</a>
                @if($subscription['can_create_listing'])
                    <a href="{{ route('taladsod.listing.create') }}" class="ts-btn3d sm ts-tone-gold"><i class="fas fa-plus" aria-hidden="true"></i> ลงขายใหม่</a>
                @endif
            </div>
        </div>
        @if($seller->listings->count() > 0)
            <div class="ts-grid" style="--ts-min:230px; gap:10px;">
                @foreach($seller->listings->take(6) as $listing)
                    @php $st = $ui::listingStatus($listing); @endphp
                    <a href="{{ route('taladsod.listing.edit', $listing->id) }}" class="ts-row" style="gap:10px; flex-wrap:nowrap; padding:10px; border-radius:16px; text-decoration:none; color:var(--ink); background:var(--surf); box-shadow:var(--raise);">
                        <span class="sf-thumb" style="width:54px; height:54px;">
                            @if($listing->primary_image)
                                <img src="{{ $listing->primary_image }}" alt="" loading="lazy">
                            @else
                                <span aria-hidden="true">🥬</span>
                            @endif
                        </span>
                        <span style="min-width:0; flex:1;">
                            <b style="display:block; font-size:13.5px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $listing->title }}</b>
                            <span class="ts-money" style="font-size:14px;">฿{{ $ui::money($listing->price) }}</span>
                            <span class="ts-pill ts-tone-{{ $st['tone'] }}" style="padding:3px 8px; font-size:10.5px;">{{ $st['label'] }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        @else
            <div class="ts-empty" style="padding:18px;">
                <span class="em" aria-hidden="true">🍳</span>
                <b>ยังไม่มีเมนู</b>
                <a href="{{ route('taladsod.listing.create') }}" class="ts-btn3d sm ts-tone-gold"><i class="fas fa-plus" aria-hidden="true"></i> ลงเมนูแรก</a>
            </div>
        @endif
    </section>

    {{-- กล่องยืนยันปิดร้าน --}}
    <div class="ts-dialog-bg" x-show="confirmClose" x-cloak x-transition.opacity x-on:click.self="confirmClose = false" x-on:keydown.escape.window="confirmClose = false">
        <div class="tp-card ts-dialog ts-stack" role="dialog" aria-modal="true" aria-labelledby="sd-close-h">
            <h2 id="sd-close-h" class="ts-h2"><i class="fas fa-power-off" style="color:var(--ts-bad);" aria-hidden="true"></i> ปิดร้านตอนนี้?</h2>
            <p class="ts-muted" style="margin:0; font-size:13.5px;">ลูกค้าจะสั่งใหม่ไม่ได้ และหยุดแสดงตำแหน่งร้าน — ออเดอร์ที่รับไว้แล้วยังทำต่อได้ตามปกติ</p>
            <div class="ts-row" style="justify-content:flex-end;">
                <button type="button" class="tp-btn" x-on:click="confirmClose = false">ยังไม่ปิด</button>
                <button type="button" class="ts-btn3d ts-tone-bad sm" x-on:click="closeShop()" :disabled="busy !== ''"><i class="fas fa-power-off" aria-hidden="true"></i> ปิดร้าน</button>
            </div>
        </div>
    </div>

    {{-- กล่องยืนยันเปลี่ยนเป็นร้านเคลื่อนที่ขณะเปิดอยู่ --}}
    <div class="ts-dialog-bg" x-show="confirmMobile !== null" x-cloak x-transition.opacity x-on:click.self="confirmMobile = null" x-on:keydown.escape.window="confirmMobile = null">
        <div class="tp-card ts-dialog ts-stack" role="dialog" aria-modal="true" aria-labelledby="sd-mode-h">
            <h2 id="sd-mode-h" class="ts-h2"><i class="fas fa-cart-flatbed" style="color:var(--accent2);" aria-hidden="true"></i>
                <span x-text="confirmMobile ? 'เปลี่ยนเป็นร้านเคลื่อนที่?' : 'เปลี่ยนเป็นร้านประจำที่?'"></span></h2>
            <p class="ts-muted" style="margin:0; font-size:13.5px;" x-text="confirmMobile
                ? 'ร้านจะปิดไว้ก่อน แล้วกด “เปิดร้านที่นี่วันนี้” เมื่อไปถึงจุดขาย ลูกค้าเห็นตำแหน่งเฉพาะตอนร้านเปิด'
                : 'ลูกค้าจะเห็นร้านตามที่อยู่ที่ลงทะเบียนไว้ และร้านเปิดตามปกติ'"></p>
            <div class="ts-row" style="justify-content:flex-end;">
                <button type="button" class="tp-btn" x-on:click="confirmMobile = null">ยกเลิก</button>
                <button type="button" class="ts-btn3d ts-tone-gold sm" x-on:click="setMobile(confirmMobile)" :disabled="busy !== ''">ยืนยัน</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    /**
     * หน้าร้านวันนี้: เปิด/ปิดร้าน, ตำแหน่งร้าน (รถเข็น), ส่งตำแหน่งสดทุก 30 วิ ระหว่างเปิดหน้านี้, ตรวจออเดอร์ใหม่ทุก 20 วิ
     * ค่าทั้งหมดมาจากเซิร์ฟเวอร์หลังทุกคำสั่ง (ไม่เดาสถานะเอง)
     */
    window.tsPresence = function (cfg) {
        let map = null;
        let marker = null;
        let liveTimer = null;
        let pollTimer = null;
        let wakeLock = null;
        let latestOrderId = cfg.latestOrderId || 0;

        return {
            p: cfg.p, busy: '', error: '',
            label: cfg.p.location_label || '', closesAt: cfg.closesAt || '', liveWanted: !!cfg.p.live_location_sharing,
            lastSent: cfg.p.location_updated_at || null, liveWarning: '',
            confirmClose: false, confirmMobile: null,
            pending: cfg.pending || 0,

            init() {
                this.$nextTick(() => this.drawMap());
                if (this.p.is_open && this.p.is_mobile && this.p.live_location_sharing) { this.startLive(); }
                pollTimer = setTimeout(() => this.pollOrders(), 20000);
                document.addEventListener('visibilitychange', () => {
                    if (document.visibilityState === 'visible') {
                        if (liveTimer) { this.requestWakeLock(); this.sendLive(); }
                        this.pollOrders();
                    }
                });
            },

            apply(r) {
                if (r.data) {
                    this.p = Object.assign({}, this.p, r.data);
                    if (this.p.location_label && !this.label) { this.label = this.p.location_label; }
                    this.$nextTick(() => this.drawMap());
                }
            },

            async openShop() {
                this.busy = 'open';
                this.error = '';
                const body = {
                    location_label: this.label || null,
                    closes_at: this.closesAt || null,
                    live_location_sharing: this.p.is_mobile && this.liveWanted ? 1 : 0
                };
                // ร้านเคลื่อนที่ (หรือร้านที่ยังไม่มีที่อยู่ประจำ) ต้องใช้ตำแหน่งตอนนี้
                if (this.p.is_mobile || !this.p.has_fixed_location) {
                    try {
                        const pos = await window.ts.geo();
                        body.latitude = pos.lat;
                        body.longitude = pos.lng;
                    } catch (e) {
                        this.busy = '';
                        this.error = e.message;
                        return;
                    }
                }
                const r = await window.ts.post(cfg.endpoints.open, body);
                this.busy = '';
                if (!r.ok) {
                    this.error = r.message;
                    return;
                }
                this.apply(r);
                this.lastSent = new Date().toISOString();
                window.ts.notify(r.message, 'success');
                if (this.p.is_mobile && this.p.live_location_sharing) { this.startLive(); }
            },

            async saveClosesAt() {
                if (!this.p.is_open) { return; }
                this.busy = 'open';
                this.error = '';
                const body = { closes_at: this.closesAt || null, location_label: this.label || null, live_location_sharing: this.p.live_location_sharing ? 1 : 0 };
                if (this.p.is_mobile && this.p.location) {
                    body.latitude = this.p.location.latitude;
                    body.longitude = this.p.location.longitude;
                }
                const r = await window.ts.post(cfg.endpoints.open, body);
                this.busy = '';
                if (!r.ok) { this.error = r.message; return; }
                this.apply(r);
                window.ts.notify('บันทึกเวลาปิดร้านแล้ว', 'success');
            },

            async closeShop() {
                this.busy = 'close';
                const r = await window.ts.post(cfg.endpoints.close, {});
                this.busy = '';
                this.confirmClose = false;
                if (!r.ok) { this.error = r.message; return; }
                this.stopLive();
                this.apply(r);
                window.ts.notify(r.message, 'success');
            },

            async updateLocationNow() {
                this.busy = 'loc';
                this.error = '';
                try {
                    const pos = await window.ts.geo();
                    const r = await window.ts.post(cfg.endpoints.location, { latitude: pos.lat, longitude: pos.lng, location_label: this.label || null });
                    if (!r.ok) {
                        this.error = r.message;
                    } else {
                        this.p = Object.assign({}, this.p, { location: r.data.location, is_open: r.data.is_open, location_updated_at: r.data.location_updated_at, location_label: this.label || this.p.location_label });
                        this.lastSent = new Date().toISOString();
                        this.$nextTick(() => this.drawMap());
                        window.ts.notify('ย้ายตำแหน่งร้านแล้ว', 'success');
                    }
                } catch (e) {
                    this.error = e.message;
                } finally {
                    this.busy = '';
                }
            },

            // el = สวิตช์ที่กด — ไม่ได้สิทธิ์ GPS / บันทึกไม่สำเร็จ ต้องตั้งสวิตช์กลับตามสถานะจริงเอง
            async setLive(el) {
                const on = !!el.checked;
                this.busy = 'live';
                this.error = '';
                try {
                    // ปิดส่งตำแหน่งสด = ใช้จุดที่ร้านอยู่ตอนนี้ได้เลย ไม่ต้องขอ GPS ใหม่
                    const here = (!on && this.p.location) ? { lat: Number(this.p.location.latitude), lng: Number(this.p.location.longitude) } : null;
                    const pos = here || await window.ts.geo();
                    const r = await window.ts.post(cfg.endpoints.open, {
                        latitude: pos.lat, longitude: pos.lng, location_label: this.label || null, live_location_sharing: on ? 1 : 0
                    });
                    if (!r.ok) { this.error = r.message; return; }
                    this.apply(r);
                    this.lastSent = new Date().toISOString();
                    if (this.p.live_location_sharing) { this.startLive(); } else { this.stopLive(); }
                    window.ts.notify(on ? 'เริ่มส่งตำแหน่งสดแล้ว' : 'หยุดส่งตำแหน่งสดแล้ว', 'success');
                } catch (e) {
                    this.error = e.message;
                } finally {
                    this.busy = '';
                    el.checked = !!this.p.live_location_sharing;
                }
            },

            askMobile(on) {
                if (on === this.p.is_mobile) { return; }
                this.confirmMobile = on;
            },

            async setMobile(on) {
                this.busy = 'mode';
                const r = await window.ts.post(cfg.endpoints.mobile_mode, { is_mobile: on ? 1 : 0 });
                this.busy = '';
                this.confirmMobile = null;
                if (!r.ok) { this.error = r.message; return; }
                this.stopLive();
                this.apply(r);
                window.ts.notify(r.message, 'success');
            },

            // ===== ส่งตำแหน่งสด =====

            startLive() {
                clearInterval(liveTimer);
                const every = Math.max(15, Number(this.p.live_send_interval_seconds) || 30) * 1000;
                liveTimer = setInterval(() => this.sendLive(), every);
                this.requestWakeLock();
            },

            stopLive() {
                clearInterval(liveTimer);
                liveTimer = null;
                this.liveWarning = '';
                if (wakeLock) { wakeLock.release().catch(() => {}); wakeLock = null; }
            },

            async sendLive() {
                if (!this.p.is_open || !this.p.live_location_sharing) { this.stopLive(); return; }
                if (document.visibilityState !== 'visible') {
                    this.liveWarning = 'หน้านี้ถูกซ่อนอยู่ — เบราว์เซอร์อาจหยุดส่งตำแหน่ง กรุณาเปิดหน้านี้ค้างไว้';
                    return;
                }
                try {
                    const pos = await window.ts.geo({ maximumAge: 10000, timeout: 20000 });
                    const r = await window.ts.post(cfg.endpoints.location, { latitude: pos.lat, longitude: pos.lng });
                    if (r.ok) {
                        this.lastSent = new Date().toISOString();
                        this.liveWarning = '';
                        this.p = Object.assign({}, this.p, { location: r.data.location, location_updated_at: r.data.location_updated_at });
                        this.$nextTick(() => this.drawMap());
                    } else if (r.status === 409) {
                        // ร้านถูกปิด (หมดเวลา/ปิดจากเครื่องอื่น) → โหลดสถานะล่าสุด
                        this.stopLive();
                        const s = await window.ts.get(cfg.endpoints.presence);
                        this.apply(s);
                        window.ts.notify(r.message, 'info');
                    } else {
                        this.liveWarning = r.message;
                    }
                } catch (e) {
                    this.liveWarning = e.message;
                }
            },

            async requestWakeLock() {
                try {
                    if ('wakeLock' in navigator && document.visibilityState === 'visible') {
                        wakeLock = await navigator.wakeLock.request('screen');
                    }
                } catch (e) {
                    wakeLock = null;
                }
            },

            // ===== แผนที่ตำแหน่งร้าน =====

            drawMap() {
                if (!this.p.is_open || !this.p.location || !window.tpMap || !window.tpMap.ready()) { return; }
                const lat = Number(this.p.location.latitude);
                const lng = Number(this.p.location.longitude);
                if (!map) {
                    map = window.tpMap.create(this.$refs.map, lat, lng, 16);
                    if (!map) { return; }
                    marker = window.tpMap.pin(map, lat, lng, 'shop');
                    [250, 900].forEach((ms) => setTimeout(() => map && map.invalidateSize(), ms));
                    return;
                }
                marker.setLatLng([lat, lng]);
                map.panTo([lat, lng]);
                map.invalidateSize();
            },

            // ===== ออเดอร์ใหม่ =====

            async pollOrders() {
                clearTimeout(pollTimer);
                if (document.visibilityState === 'visible') {
                    const r = await window.ts.get(cfg.pollUrl);
                    if (r.ok && r.data) {
                        const newest = Number(r.data.latest_order_id) || 0;
                        if (newest > latestOrderId && Number(r.data.pending) > 0) {
                            window.ts.notify('มีออเดอร์ใหม่เข้ามา!', 'success');
                            this.beep();
                        }
                        latestOrderId = Math.max(latestOrderId, newest);
                        this.pending = Number(r.data.pending) || 0;
                        window.dispatchEvent(new CustomEvent('ts-pending-count', { detail: { count: this.pending } }));
                    }
                }
                pollTimer = setTimeout(() => this.pollOrders(), 20000);
            },

            beep() {
                try {
                    const Ctx = window.AudioContext || window.webkitAudioContext;
                    if (!Ctx) { return; }
                    const ctx = new Ctx();
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.frequency.value = 880;
                    gain.gain.setValueAtTime(0.15, ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.6);
                    osc.start();
                    osc.stop(ctx.currentTime + 0.6);
                } catch (e) {
                    // เบราว์เซอร์ไม่ให้เล่นเสียงก่อนผู้ใช้แตะหน้าจอ — ข้ามได้
                }
            }
        };
    };
</script>
@endpush
