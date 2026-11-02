{{--
 | หน้าร้านวันนี้ (แดชบอร์ดผู้ขายตลาดสด — taladsod.seller.dashboard) — ธีมโนวาเต็ม (layouts.user-nova)
 | Controller: FreshMarket\HomeController@sellerDashboard (ยังไม่มีร้าน → หน้าสมัคร)
 | ตัวแปร: $seller (listings 10), $recentOrders, $stats (object), $pendingOrders, $gpDebt, $gpRate, $gpFree, $maxCashbackPercent,
 |         $referralLinks{token, web_url, line_url}|null, $subscription{type, expires_at, is_paid, monthly_fee, fee_mode, can_create_listing},
 |         $presence (presencePayload(true) + has_fixed_location, followers_count, live_send_interval_seconds),
 |         $presenceEndpoints{presence, open, location, close, mobile_mode}
 | เปิดร้าน: POST open {latitude?, longitude?, location_label?, closes_at? (HH:MM), live_location_sharing}
 | ตำแหน่งสด: POST location {latitude, longitude, location_label?} ทุก 30 วินาทีระหว่างเปิดหน้านี้ · ปิดร้าน: POST close · โหมดร้าน: POST mobile_mode {is_mobile}
 | ออเดอร์ใหม่: poll GET taladsod.seller.orders.poll ทุก 20 วินาที
 --}}
@extends('layouts.user-nova')

@section('title', 'หน้าร้านวันนี้ · '.$seller->shop_name)

@php
    $ui = \App\Support\TaladsodWebUi::class;
    $visible = $seller->isVisibleToBuyers();
    $closesHm = ! empty($presence['closes_at']) ? $ui::time($presence['closes_at']) : '';
    $latestOrderId = (int) ($recentOrders->max('id') ?? 0);
    $isOpen = ! empty($presence['is_open']);
    $isMobile = ! empty($presence['is_mobile']);

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

    // การ์ดตัวเลข: [ลิงก์, ป้าย, ภาพ, ค่า, คำอธิบาย, ผูกค่ากับตัวนับสด]
    $kpis = [
        [route('taladsod.seller.orders', ['status' => 'pending']), 'รอรับออเดอร์', 'images/nova/icons/bag.webp', number_format((int) $stats->pending_orders), 'แตะเพื่อรับออเดอร์', true],
        [route('taladsod.seller.orders'), 'กำลังทำ/ส่ง', 'images/nova/icons/scooter.webp', number_format((int) $stats->active_orders), 'ออเดอร์ที่ยังไม่จบ', false],
        [route('taladsod.seller.earnings'), 'ยอดขายรวม', 'images/nova/deco/coins.webp', '฿'.$ui::money($stats->total_revenue), number_format((int) $stats->total_sales).' ออเดอร์สำเร็จ · ดูรายได้', false],
        [route('taladsod.seller.listings'), 'สินค้า/เมนู', 'images/nova/icons/basket.webp', number_format((int) $stats->total_listings), 'จัดการเมนู', false],
    ];
@endphp

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/taladsod.css') }}?v={{ filemtime(public_path('theme-nova/member/taladsod.css')) }}">@endpush

@section('content')
@include('taladsod.partials.kit-js')
<x-theme-v4.leaflet />

<div class="nvm-stack" x-data="tsPresence({{ \Illuminate\Support\Js::from($presenceCfg) }})">

{{-- ════════ หัวร้าน ════════ --}}
<section class="nvm-ph" aria-labelledby="nvm-td-sd-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <div class="nvm-eyebrow nvm-latin">My market stall</div>
            <h1 id="nvm-td-sd-h">{{ $seller->shop_name }}</h1>
            <p class="nvm-ph__lead">หน้าร้านวันนี้ — เปิด/ปิดร้าน รับออเดอร์ และดูยอดขายได้ในที่เดียว</p>
            <div class="nvm-pills">
                <span class="nvm-pill {{ $seller->status_key === 'active' ? 'nvm-pill--ok' : ($seller->status_key === 'unverified' ? 'nvm-pill--warn' : 'nvm-pill--bad') }}">{{ $seller->status_label }}</span>
                <span class="nvm-pill nvm-pill--glass"><svg class="nvm-ico"><use href="#nvm-i-users"/></svg><span x-text="p.followers_count">{{ (int) ($presence['followers_count'] ?? 0) }}</span>&nbsp;ผู้ติดตาม</span>
                <span class="nvm-pill nvm-pill--glass"><svg class="nvm-ico"><use href="#nvm-i-award"/></svg>{{ number_format((float) $stats->rating, 1) }} ({{ number_format((int) $stats->rating_count) }} รีวิว)</span>
            </div>
            <div class="nvm-hero__actions nvm-td-acts">
                <a class="nvm-btn nvm-btn--ghost nvm-btn--sm" href="{{ route('taladsod.seller', $seller->id) }}" target="_blank" rel="noopener"><svg class="nvm-ico"><use href="#nvm-i-store"/></svg>ดูหน้าร้านแบบลูกค้า</a>
            </div>
        </div>
        @if($seller->shop_image)
            {{-- รูปร้านที่เจ้าของอัปโหลดไว้ — ไม่มีรูปค่อยใช้ภาพตลาดของชุดโนวา --}}
            <img class="nvm-ph__art nvm-td-logo" src="{{ $seller->shop_image }}" alt="รูปร้าน {{ $seller->shop_name }}" width="200" height="200">
        @else
            <img class="nvm-ph__art" src="{{ asset('images/nova/svc/market.webp') }}" alt="" width="200" height="200">
        @endif
    </div>
</section>

@include('taladsod.partials.seller-nav', ['seller' => $seller, 'active' => 'dashboard'])

@if(! $visible)
    {{-- สถานะร้าน: ถูกระงับ / รอเจ้าของร้านยืนยันตัวตนในแอป (eKYC) / รอแอดมินยืนยัน — ข้อความเดียวกับแอปและ LINE --}}
    <section class="nvm-card nvm-alert" role="status">
        <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-clock"/></svg></span>
        <div class="nvm-alert__txt"><b>ลูกค้ายังไม่เห็นร้านของคุณ</b><p>{{ $seller->sellingStatusMessage() }}</p></div>
        @if($seller->needsOwnerKyc())
            <a href="{{ \App\Services\Ekyc\EkycGate::downloadUrl() }}" class="nvm-btn nvm-btn--gold nvm-btn--sm"><svg class="nvm-ico"><use href="#nvm-i-id"/></svg>ยืนยันตัวตนในแอป Thai Prompt APP</a>
        @endif
    </section>
@endif

{{-- ════════ แจ้งออเดอร์ใหม่ ════════ --}}
<a href="{{ route('taladsod.seller.orders', ['status' => 'pending']) }}" class="nvm-card nvm-alert" x-show="pending > 0" @if($pendingOrders <= 0) x-cloak @endif>
    <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-bell"/></svg></span>
    <div class="nvm-alert__txt"><b>มีออเดอร์รอรับ <span class="nvm-num" x-text="pending">{{ (int) $pendingOrders }}</span> รายการ</b><p>กดรับก่อนหมดเวลา</p></div>
    <span class="nvm-btn nvm-btn--gold nvm-btn--sm">ไปรับออเดอร์<svg class="nvm-ico"><use href="#nvm-i-chev"/></svg></span>
</a>

{{-- ════════ การ์ดเปิด/ปิดร้าน ════════ --}}
<section class="nvm-card nvm-box nvm-td-presence {{ $isOpen ? 'is-open' : '' }}" :class="p.is_open ? 'is-open' : ''" aria-labelledby="sd-presence-h">
    <div class="nvm-td-presence__head">
        <div>
            <h2 id="sd-presence-h">
                <span class="nvm-td-dot {{ $isOpen ? 'is-ok is-live' : '' }}" :class="p.is_open ? 'is-ok is-live' : ''"></span>
                <span x-text="p.is_open ? 'ร้านเปิดอยู่' : 'ร้านปิดอยู่'">{{ $isOpen ? 'ร้านเปิดอยู่' : 'ร้านปิดอยู่' }}</span>
            </h2>
            <p x-show="p.is_open" @if(! $isOpen) x-cloak @endif>
                <span x-text="p.opened_at ? ('เปิดตั้งแต่ ' + window.ts.hhmm(p.opened_at) + ' น.') : 'เปิดรับออเดอร์ตามปกติ'"></span>
                <template x-if="p.closes_at"><span> · ปิดอัตโนมัติ <span x-text="window.ts.hhmm(p.closes_at)"></span> น.</span></template>
            </p>
            <p x-show="!p.is_open" @if($isOpen) x-cloak @endif>ลูกค้าเห็นร้านแต่สั่งไม่ได้ · ผู้ติดตามได้แจ้งเตือนทันทีที่คุณเปิดร้าน</p>
        </div>
        <label class="nvm-td-switch" title="ร้านเคลื่อนที่ (รถเข็น/ตลาดนัด)">
            <span class="nvm-sw"><input type="checkbox" :checked="p.is_mobile" @checked($isMobile) x-on:change="askMobile($event.target.checked); $event.target.checked = p.is_mobile" :disabled="busy !== ''"><i></i></span>
            ร้านเคลื่อนที่
        </label>
    </div>

    {{-- ปิดอยู่: ตั้งค่าแล้วกดเปิด --}}
    <div x-show="!p.is_open" @if($isOpen) x-cloak @endif class="nvm-form nvm-gap-t">
        <div class="nvm-td-cols">
            <div class="nvm-field" x-show="p.is_mobile" @if(! $isMobile) x-cloak @endif>
                <label class="nvm-label" for="sd-label">วันนี้ขายที่ไหน</label>
                <input id="sd-label" type="text" class="nvm-input" maxlength="150" x-model="label" placeholder="เช่น ตลาดนัดหน้าโรงเรียน, ปากซอย 5">
            </div>
            <div class="nvm-field">
                <label class="nvm-label" for="sd-closes">ปิดร้านเวลา (ไม่บังคับ)</label>
                <input id="sd-closes" type="time" class="nvm-input" x-model="closesAt">
            </div>
        </div>
        <label class="nvm-td-switch" x-show="p.is_mobile" @if(! $isMobile) x-cloak @endif>
            <span class="nvm-sw"><input type="checkbox" x-model="liveWanted"><i></i></span>
            ส่งตำแหน่งสดระหว่างขาย (รถเข็นที่เดินขาย)
        </label>
        <button type="button" class="nvm-btn nvm-btn--gold nvm-w-full" x-on:click="openShop()" :disabled="busy !== ''">
            <svg class="nvm-ico"><use href="#nvm-i-pin"/></svg>
            <span x-text="busy === 'open' ? 'กำลังเปิดร้าน…' : (p.is_mobile ? 'เปิดร้านที่นี่วันนี้' : 'เปิดร้าน')">{{ $isMobile ? 'เปิดร้านที่นี่วันนี้' : 'เปิดร้าน' }}</span>
        </button>
        <span class="nvm-help" x-show="p.is_mobile" @if(! $isMobile) x-cloak @endif>ระบบใช้ GPS ของเครื่องนี้เป็นตำแหน่งร้าน — ลูกค้าเห็นตำแหน่งเฉพาะตอนร้านเปิด ปิดร้านแล้วตำแหน่งจะไม่แสดง</span>
    </div>

    {{-- เปิดอยู่ --}}
    <div x-show="p.is_open" @if(! $isOpen) x-cloak @endif class="nvm-td-2col nvm-gap-t">
        <div class="nvm-form">
            <div class="nvm-td-map" x-ref="map" x-show="p.location" aria-label="แผนที่ตำแหน่งร้านตอนนี้"></div>
            <p class="nvm-note is-bad" x-show="!p.location"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>ร้านยังไม่มีตำแหน่ง — ลูกค้าจะหาร้านบนแผนที่ไม่เจอ</p>
            <span class="nvm-help" x-show="p.location">ตำแหน่ง: <span x-text="(p.location && p.location.label) || (p.is_mobile ? 'ตำแหน่งที่เปิดร้าน' : 'ที่อยู่ร้าน')"></span></span>
        </div>
        <div class="nvm-form">
            <template x-if="p.is_mobile">
                <div class="nvm-form">
                    <label class="nvm-td-switch">
                        <span class="nvm-sw"><input type="checkbox" :checked="p.live_location_sharing" x-on:change="setLive($event.target)" :disabled="busy !== ''"><i></i></span>
                        ส่งตำแหน่งสดทุก 30 วินาที
                    </label>
                    <div class="nvm-td-live" x-show="p.live_location_sharing">
                        <span class="nvm-td-dot is-ok is-live"></span> ส่งล่าสุด <b x-text="lastSent ? window.ts.timeAgo(lastSent) : 'รอส่งครั้งแรก'"></b>
                        <p>เปิดหน้านี้ค้างไว้ระหว่างขาย (หน้าจอจะไม่ดับเอง) — ถ้าตำแหน่งเงียบเกิน {{ \App\Models\FreshMarketSeller::LIVE_LOCATION_STALE_MINUTES }} นาที ระบบปิดร้านให้อัตโนมัติ</p>
                        <span class="nvm-err" x-show="liveWarning" x-text="liveWarning"></span>
                    </div>
                    <button type="button" class="nvm-btn nvm-btn--line" x-on:click="updateLocationNow()" :disabled="busy !== ''">
                        <svg class="nvm-ico"><use href="#nvm-i-pin"/></svg><span x-text="busy === 'loc' ? 'กำลังอัปเดต…' : 'ย้ายร้านมาที่นี่ (อัปเดตตำแหน่ง)'"></span>
                    </button>
                    <input type="text" class="nvm-input" maxlength="150" x-model="label" placeholder="ชื่อจุดขาย เช่น หน้าตลาดนัดเย็น" aria-label="ชื่อจุดขาย">
                </div>
            </template>
            <div class="nvm-td-acts">
                <input type="time" class="nvm-input nvm-td-grow" x-model="closesAt" aria-label="เวลาปิดร้าน">
                <button type="button" class="nvm-btn nvm-btn--line" x-on:click="saveClosesAt()" :disabled="busy !== ''"><svg class="nvm-ico"><use href="#nvm-i-clock"/></svg>ตั้งเวลาปิด</button>
            </div>
            <button type="button" class="nvm-btn nvm-btn--bad nvm-w-full" x-on:click="confirmClose = true" :disabled="busy !== ''">
                <svg class="nvm-ico"><use href="#nvm-i-out"/></svg>ปิดร้าน
            </button>
        </div>
    </div>

    <p class="nvm-err nvm-gap-t" x-show="error" x-text="error" x-cloak role="alert"></p>
</section>

{{-- ════════ ตัวเลข ════════ --}}
<section class="nvm-kpis" aria-label="สรุปร้านวันนี้">
    @foreach($kpis as [$href, $label, $img, $value, $foot, $live])
        <a class="nvm-card nvm-kpi" href="{{ $href }}">
            <img class="nvm-kpi__img" src="{{ asset($img) }}" alt="" width="78" height="78" loading="lazy">
            <div class="nvm-kpi__lbl">{{ $label }}</div>
            <div class="nvm-kpi__val nvm-num" @if($live) x-text="pending" @endif>{{ $value }}</div>
            <div class="nvm-kpi__foot"><small>{{ $foot }}</small></div>
        </a>
    @endforeach
</section>

<div class="nvm-split">
    {{-- ════════ ออเดอร์ล่าสุด ════════ --}}
    <section class="nvm-card nvm-box" aria-labelledby="sd-orders-h">
        <div class="nvm-sec-h">
            <div><small class="nvm-latin">Recent orders</small><h2 id="sd-orders-h">ออเดอร์ล่าสุด</h2></div>
            <a href="{{ route('taladsod.seller.orders') }}">ดูทั้งหมด<svg class="nvm-ico"><use href="#nvm-i-chev"/></svg></a>
        </div>
        @if($recentOrders->isNotEmpty())
            <div class="nvm-txs nvm-tks">
                @foreach($recentOrders->take(6) as $order)
                    @php
                        $orderLines = $order->lineItems();
                        $first = $orderLines->first();
                    @endphp
                    <a href="{{ route('taladsod.seller.orders.show', $order) }}" class="nvm-tx">
                        <span class="nvm-tx__ic {{ in_array($order->order_status, ['delivered', 'completed'], true) ? 'is-in' : '' }}"><svg class="nvm-ico"><use href="#nvm-i-{{ $ui::novaOrderIcon($order->order_status) }}"/></svg></span>
                        <span class="nvm-tx__t">
                            <b>#{{ $order->order_number }} · {{ $first?->title }}{{ $orderLines->count() > 1 ? ' +'.($orderLines->count() - 1) : '' }}</b>
                            <small>{{ $order->buyer?->name ?? 'ลูกค้า' }} · {{ $ui::shortDate($order->created_at) }}</small>
                        </span>
                        <span class="nvm-tx__r">
                            <span class="nvm-tx__amt nvm-num">฿{{ $ui::money($order->total_amount) }}</span>
                            <span class="{{ $ui::novaChip($ui::orderTone($order->order_status)) }}">{{ $order->status_label }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        @else
            <div class="nvm-empty">
                <img src="{{ asset('images/nova/icons/bag.webp') }}" alt="" width="86" height="86" loading="lazy">
                <b>ยังไม่มีออเดอร์</b>
                เปิดร้านแล้วชวนลูกค้ามากดติดตามร้านได้เลย
            </div>
        @endif
    </section>

    <div class="nvm-stack">
        {{-- ════════ ค่าธรรมเนียม GP ════════ --}}
        <section class="nvm-card nvm-box" aria-labelledby="sd-gp-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Selling fee</small><h2 id="sd-gp-h">ค่าธรรมเนียมการขาย (GP)</h2></div></div>
            @if($gpFree)
                <div class="nvm-status"><svg class="nvm-ico"><use href="#nvm-i-gift"/></svg><span>ฟรี GP ช่วงเปิดตัว<small>ร้านได้รับเต็มราคาสินค้า</small></span></div>
            @else
                <dl class="nvm-kv"><dt>อัตรา GP ตอนนี้</dt><dd>{{ rtrim(rtrim(number_format((float) $gpRate, 2), '0'), '.') }}% ของยอดสินค้า</dd></dl>
            @endif
            @if($gpDebt > 0)
                <p class="nvm-note is-bad"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>มียอด GP ค้างชำระ ฿{{ $ui::money($gpDebt) }} (จากออเดอร์เก็บเงินปลายทาง) — ระบบหักจากรายได้ออเดอร์ถัดไปอัตโนมัติ</p>
            @endif
            <a href="{{ route('taladsod.seller.earnings') }}" class="nvm-btn nvm-btn--line nvm-gap-t"><svg class="nvm-ico"><use href="#nvm-i-cash"/></svg>ดูรายได้และเงินเข้า</a>
        </section>

        {{-- ════════ สมาชิกรายเดือน (เฉพาะโหมดที่ใช้สมาชิก) ════════ --}}
        @if(($subscription['fee_mode'] ?? 'percentage') !== 'percentage')
            <section class="nvm-card nvm-box" aria-labelledby="sd-sub-h" x-data="{ ask: false, sending: false }">
                <div class="nvm-sec-h"><div><small class="nvm-latin">Membership</small><h2 id="sd-sub-h">สมาชิกร้าน</h2></div></div>
                <dl class="nvm-kv">
                    <dt>แพ็กเกจ</dt><dd>{{ $subscription['is_paid'] ? 'สมาชิกรายเดือน' : 'ฟรี' }}</dd>
                    @if($subscription['expires_at'])
                        <dt>ใช้ได้ถึง</dt><dd class="nvm-num">{{ $ui::date($subscription['expires_at'], false) }}</dd>
                    @endif
                </dl>
                @unless($subscription['can_create_listing'])
                    <p class="nvm-note is-bad"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>ลงขายครบโควต้าแล้ว — สมัครสมาชิกเพื่อลงขายเพิ่ม</p>
                @endunless
                <button type="button" class="nvm-btn nvm-btn--gold nvm-gap-t" x-on:click="ask = true"><svg class="nvm-ico"><use href="#nvm-i-award"/></svg>{{ $subscription['is_paid'] ? 'ต่ออายุ' : 'สมัครสมาชิก' }} ฿{{ $ui::money($subscription['monthly_fee']) }}/เดือน</button>
                <div class="nvm-modal" x-show="ask" x-cloak x-transition.opacity x-on:click.self="ask = false" x-on:keydown.escape.window="ask = false">
                    <form method="POST" action="{{ route('taladsod.seller.subscribe') }}" class="nvm-card nvm-modal__box" role="dialog" aria-modal="true" aria-labelledby="sd-sub-ask-h" x-on:submit="sending = true">
                        @csrf
                        <h2 id="sd-sub-ask-h">ยืนยันชำระค่าสมาชิก ฿{{ $ui::money($subscription['monthly_fee']) }}</h2>
                        <p>ระบบหักเงินจากกระเป๋าเงินของคุณทันที</p>
                        <div class="nvm-filter__btns">
                            <button type="button" class="nvm-btn nvm-btn--line" x-on:click="ask = false">ยกเลิก</button>
                            <button type="submit" class="nvm-btn nvm-btn--gold" :disabled="sending">ยืนยันชำระ</button>
                        </div>
                    </form>
                </div>
            </section>
        @endif

        {{-- ════════ ลิงก์ชวนลูกค้า ════════ --}}
        @if($referralLinks)
            <section class="nvm-card nvm-box" aria-labelledby="sd-ref-h">
                <div class="nvm-sec-h"><div><small class="nvm-latin">Invite customers</small><h2 id="sd-ref-h">ชวนลูกค้ามาที่ร้าน</h2></div></div>
                <p class="nvm-desc">ส่งลิงก์นี้ให้ลูกค้าประจำ สมัครแล้วสั่งจากร้านคุณได้ทันที</p>
                <div class="nvm-copy nvm-copy--box">
                    <code>{{ $referralLinks['web_url'] }}</code>
                    <button type="button" x-on:click="nvmCopy(@js($referralLinks['web_url']), 'คัดลอกลิงก์แล้ว')" aria-label="คัดลอกลิงก์ชวนลูกค้า"><svg class="nvm-ico"><use href="#nvm-i-copy"/></svg>คัดลอก</button>
                </div>
                @if($referralLinks['line_url'])
                    <a href="{{ $referralLinks['line_url'] }}" target="_blank" rel="noopener" class="nvm-btn nvm-btn--line nvm-gap-t"><svg class="nvm-ico"><use href="#nvm-i-line"/></svg>ลิงก์เพิ่มเพื่อน LINE</a>
                @endif
            </section>
        @endif
    </div>
</div>

{{-- ════════ เมนูของร้าน ════════ --}}
<section class="nvm-card nvm-box" aria-labelledby="sd-list-h">
    <div class="nvm-sec-h">
        <div><small class="nvm-latin">My menu</small><h2 id="sd-list-h">เมนูของร้าน</h2></div>
        <a href="{{ route('taladsod.seller.listings') }}">ทั้งหมด<svg class="nvm-ico"><use href="#nvm-i-chev"/></svg></a>
    </div>
    @if($seller->listings->count() > 0)
        <div class="nvm-td-grid nvm-td-grid--sm">
            @foreach($seller->listings->take(6) as $listing)
                @php $st = $ui::listingStatus($listing); @endphp
                <a href="{{ route('taladsod.listing.edit', $listing->id) }}" class="nvm-td-mini">
                    <span class="nvm-td-thumb">
                        @if($listing->primary_image)
                            <img src="{{ $listing->primary_image }}" alt="" loading="lazy">
                        @else
                            <svg class="nvm-ico"><use href="#nvm-i-basket"/></svg>
                        @endif
                    </span>
                    <span class="nvm-td-item__txt">
                        <b>{{ $listing->title }}</b>
                        <span class="nvm-td-price nvm-num">฿{{ $ui::money($listing->price) }}</span>
                        <span class="{{ $ui::novaChip($st['tone']) }}">{{ $st['label'] }}</span>
                    </span>
                </a>
            @endforeach
        </div>
        @if($subscription['can_create_listing'])
            <a href="{{ route('taladsod.listing.create') }}" class="nvm-btn nvm-btn--gold nvm-gap-t"><svg class="nvm-ico"><use href="#nvm-i-plus"/></svg>ลงขายใหม่</a>
        @endif
    @else
        <div class="nvm-empty">
            <img src="{{ asset('images/nova/icons/basket.webp') }}" alt="" width="86" height="86" loading="lazy">
            <b>ยังไม่มีเมนู</b>
            ลงเมนูแรกแล้วกดเปิดร้านได้เลย
            <div><a href="{{ route('taladsod.listing.create') }}" class="nvm-btn nvm-btn--gold"><svg class="nvm-ico"><use href="#nvm-i-plus"/></svg>ลงเมนูแรก</a></div>
        </div>
    @endif
</section>

{{-- กล่องยืนยันปิดร้าน --}}
<div class="nvm-modal" x-show="confirmClose" x-cloak x-transition.opacity x-on:click.self="confirmClose = false" x-on:keydown.escape.window="confirmClose = false">
    <div class="nvm-card nvm-modal__box" role="dialog" aria-modal="true" aria-labelledby="sd-close-h">
        <h2 id="sd-close-h">ปิดร้านตอนนี้?</h2>
        <p>ลูกค้าจะสั่งใหม่ไม่ได้ และหยุดแสดงตำแหน่งร้าน — ออเดอร์ที่รับไว้แล้วยังทำต่อได้ตามปกติ</p>
        <div class="nvm-filter__btns">
            <button type="button" class="nvm-btn nvm-btn--line" x-on:click="confirmClose = false">ยังไม่ปิด</button>
            <button type="button" class="nvm-btn nvm-btn--bad" x-on:click="closeShop()" :disabled="busy !== ''"><svg class="nvm-ico"><use href="#nvm-i-out"/></svg>ปิดร้าน</button>
        </div>
    </div>
</div>

{{-- กล่องยืนยันเปลี่ยนประเภทร้าน --}}
<div class="nvm-modal" x-show="confirmMobile !== null" x-cloak x-transition.opacity x-on:click.self="confirmMobile = null" x-on:keydown.escape.window="confirmMobile = null">
    <div class="nvm-card nvm-modal__box" role="dialog" aria-modal="true" aria-labelledby="sd-mode-h">
        <h2 id="sd-mode-h" x-text="confirmMobile ? 'เปลี่ยนเป็นร้านเคลื่อนที่?' : 'เปลี่ยนเป็นร้านประจำที่?'"></h2>
        <p x-text="confirmMobile
            ? 'ร้านจะปิดไว้ก่อน แล้วกด “เปิดร้านที่นี่วันนี้” เมื่อไปถึงจุดขาย ลูกค้าเห็นตำแหน่งเฉพาะตอนร้านเปิด'
            : 'ลูกค้าจะเห็นร้านตามที่อยู่ที่ลงทะเบียนไว้ และร้านเปิดตามปกติ'"></p>
        <div class="nvm-filter__btns">
            <button type="button" class="nvm-btn nvm-btn--line" x-on:click="confirmMobile = null">ยกเลิก</button>
            <button type="button" class="nvm-btn nvm-btn--gold" x-on:click="setMobile(confirmMobile)" :disabled="busy !== ''">ยืนยัน</button>
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
