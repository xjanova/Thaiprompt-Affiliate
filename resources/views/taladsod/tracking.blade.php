{{--
 | หน้าติดตามไรเดอร์ของลูกค้า (taladsod.track.show — ลิงก์ token ไม่ต้อง login) — ธีม V4 (frontend-v4)
 | Controller: FreshMarket\RiderTrackingController@show
 | ตัวแปร: $job, $rider (null = ยังไม่มีไรเดอร์), $order, $orderNumber, $riderPhotoUrl, $riderPhone (เฉพาะงานยังวิ่ง), $isActive,
 |         $location (getCurrentLocation), $customerLocation, $pickupLocation, $token, $pollInterval (ms), $customerPollInterval,
 |         $deliveryEndpoints ({source, order_id, rider_location, share_location} เฉพาะเจ้าของออเดอร์ที่ login อยู่ | null)
 | Poll: GET taladsod.track.location → {success, location{available,...}, job_status, job_status_text, gps_active, is_active} · 404 TRACKING_EXPIRED
 |       GET taladsod.track.route → {route[{lat,lng,speed,time}]}
 | ไม่มีตำแหน่งไรเดอร์ให้ดู → แสดง job_status_text แทนแผนที่ · ข้อความจากผู้ใช้เข้า JS ผ่าน Js::from และแสดงด้วย textContent เท่านั้น
 --}}
@extends('layouts.frontend-v4')

@section('title', 'ติดตามไรเดอร์ · งาน #'.$job->job_number)

@section('meta')
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
@endsection

@php
    $ui = \App\Support\TaladsodWebUi::class;
    $rw = \App\Support\RiderWebUi::class;
    $status = (string) $job->status;
    $available = (bool) ($location['available'] ?? false);
    $steps = $rw::progressSteps($status, [
        'accepted_at' => $job->accepted_at,
        'picked_up_at' => $job->picked_up_at,
        'delivered_at' => $job->delivered_at,
        'completed_at' => $job->completed_at,
    ]);
    $stepLabels = [
        'accepted' => 'ไรเดอร์รับงาน',
        'picking_up' => 'กำลังไปรับของ',
        'picked_up' => 'รับของแล้ว',
        'delivering' => 'กำลังมาส่ง',
        'completed' => 'ส่งถึงแล้ว',
    ];
    $stepIcons = [
        'accepted' => 'fa-user-check',
        'picking_up' => 'fa-store',
        'picked_up' => 'fa-box',
        'delivering' => 'fa-motorcycle',
        'completed' => 'fa-house-circle-check',
    ];
    $tone = match (true) {
        in_array($status, ['cancelled', 'failed'], true) => 'bad',
        in_array($status, ['delivered', 'completed'], true) => 'ok',
        $status === 'pending' => 'warn',
        default => 'info',
    };
    $validPoint = fn ($p) => $rw::validPoint($p['latitude'] ?? null, $p['longitude'] ?? null);
    $vehicleText = $rider ? ($rider->vehicle_type_text ?? $rider->vehicle_type) : null;
    $statusText = (string) ($location['job_status_text'] ?? $job->status_text);
    $reasonText = match ($location['reason'] ?? null) {
        'consent_missing' => 'ไรเดอร์ยังไม่ได้เปิดแชร์ตำแหน่ง — จะเห็นบนแผนที่เมื่อไรเดอร์เริ่มแชร์',
        'job_not_active' => $status === 'pending'
            ? 'กำลังหาไรเดอร์ใกล้ร้าน เมื่อมีคนรับงานจะเห็นตำแหน่งที่นี่'
            : 'การจัดส่งจบแล้ว จึงไม่แสดงตำแหน่งไรเดอร์',
        default => 'กำลังรอสัญญาณ GPS จากไรเดอร์',
    };

    $trackCfg = [
        'locationUrl' => route('taladsod.track.location', $token),
        'routeUrl' => route('taladsod.track.route', $token),
        'pollMs' => max(10000, (int) ($pollInterval ?? 30000)),
        // active = งานวิ่งอยู่ (เห็นไรเดอร์/แชร์ตำแหน่งได้) · watch = งานยังไม่จบ → poll ต่อ (รวมตอนรอไรเดอร์รับงาน)
        'active' => (bool) $isActive,
        'watch' => ! $job->isTerminal(),
        'status' => $status,
        'statusText' => $statusText,
        'reasonText' => $reasonText,
        'rider' => $available ? ['lat' => (float) $location['latitude'], 'lng' => (float) $location['longitude'], 'updated_at' => $location['updated_at'] ?? null] : null,
        'pickup' => $validPoint($pickupLocation) ? ['lat' => (float) $pickupLocation['latitude'], 'lng' => (float) $pickupLocation['longitude']] : null,
        'dropoff' => $validPoint($customerLocation) ? ['lat' => (float) $customerLocation['latitude'], 'lng' => (float) $customerLocation['longitude']] : null,
        'labels' => [
            'pickup' => (string) ($pickupLocation['contact_name'] ?? 'จุดรับสินค้า'),
            'rider' => (string) ($rider?->full_name ?? 'ไรเดอร์'),
        ],
        'share' => $deliveryEndpoints ? [
            'riderLocationUrl' => $deliveryEndpoints['rider_location'],
            'shareUrl' => $deliveryEndpoints['share_location'],
        ] : null,
    ];
@endphp

@section('content')
<x-theme-v4.shop-kit />
@include('taladsod.partials.kit')
<x-theme-v4.leaflet />
<x-theme-v4.public-header :sticky="false" />

<main class="ts-scope" style="flex:1; padding-bottom:44px;" x-data="tsTrack({{ \Illuminate\Support\Js::from($trackCfg) }})">
    <div class="sf-wrap" style="max-width:980px;">

        {{-- ════════ หัว: สถานะงาน ════════ --}}
        <section class="tp-card" style="margin-top:18px; padding:20px;">
            <div class="ts-row" style="justify-content:space-between; gap:12px;">
                <div style="min-width:0;">
                    <div class="sf-kicker">ติดตามการจัดส่ง</div>
                    <h1 class="ts-h1" style="font-size:clamp(20px,4vw,26px);">งาน #{{ $job->job_number }}</h1>
                    @if($orderNumber)
                        <div class="ts-muted ts-small" style="margin-top:4px;">ออเดอร์ #{{ $orderNumber }}</div>
                    @endif
                </div>
                <span class="ts-pill solid ts-tone-{{ $tone }}" style="font-size:13px; padding:9px 14px;">
                    <span class="ts-dot {{ $isActive ? 'live' : '' }}" style="background:var(--ts-on);"></span>
                    <span x-text="statusText">{{ $statusText }}</span>
                </span>
            </div>

            {{-- แถบขั้นตอน --}}
            <ol style="list-style:none; margin:18px 0 0; padding:0; display:grid; grid-template-columns:repeat({{ count($steps) }}, minmax(0,1fr)); gap:6px;">
                @foreach($steps as $step)
                    @php
                        $stateTone = match ($step['state']) {
                            'done' => 'ok',
                            'current' => 'gold',
                            'stopped' => 'bad',
                            default => 'muted',
                        };
                    @endphp
                    <li style="display:flex; flex-direction:column; align-items:center; gap:6px; text-align:center; min-width:0;" class="ts-tone-{{ $stateTone }}">
                        <span style="width:40px; height:40px; border-radius:50%; display:grid; place-items:center; font-size:15px;
                                     {{ $step['state'] === 'todo' ? 'color:var(--ink2); background:var(--surf); box-shadow:var(--inset-sm);' : 'color:var(--ts-on); background:linear-gradient(135deg, color-mix(in srgb, var(--tone) 70%, var(--ts-on)), var(--tone)); box-shadow:var(--raise);' }}
                                     {{ $step['state'] === 'current' ? 'animation:tpPulse 1.8s ease-in-out infinite;' : '' }}">
                            <i class="fas {{ $step['state'] === 'stopped' ? 'fa-xmark' : ($stepIcons[$step['key']] ?? 'fa-circle') }}" aria-hidden="true"></i>
                        </span>
                        <span style="font-size:11.5px; font-weight:700; line-height:1.3; color:{{ $step['state'] === 'todo' ? 'var(--ink2)' : 'var(--ink)' }};">{{ $stepLabels[$step['key']] ?? $step['label'] }}</span>
                        @if($step['at'])
                            <span class="ts-muted" style="font-size:10.5px;">{{ $ui::time($step['at']) }}</span>
                        @endif
                    </li>
                @endforeach
            </ol>
        </section>

        {{-- ════════ แผนที่ / สถานะแทนแผนที่ ════════ --}}
        <section class="tp-card ts-stack" style="margin-top:16px; padding:14px;">
            <div x-show="expired" x-cloak class="ts-empty">
                <span class="em" aria-hidden="true">⌛</span>
                <b>ลิงก์ติดตามนี้หมดอายุแล้ว</b>
                <span class="ts-muted ts-small">การจัดส่งจบไปแล้ว ขอบคุณที่ใช้บริการค่ะ</span>
            </div>

            <div x-show="!expired">
                <div class="ts-map lg" x-ref="map" x-show="riderAvailable" @if(! $available) x-cloak @endif aria-label="แผนที่ตำแหน่งไรเดอร์"></div>

                <div x-show="!riderAvailable" @if($available) x-cloak @endif class="ts-empty" style="padding:28px 16px;">
                    <span class="tp-tile" style="width:64px; height:64px; border-radius:22px; font-size:26px;"><i class="fas {{ $status === 'pending' ? 'fa-magnifying-glass-location' : ($isActive ? 'fa-satellite-dish' : 'fa-flag-checkered') }}" aria-hidden="true"></i></span>
                    <b style="font-size:16px;" x-text="statusText">{{ $statusText }}</b>
                    <span class="ts-muted ts-small" x-text="reasonText">{{ $reasonText }}</span>
                    <button type="button" class="tp-btn tp-btn-sm" x-show="watch" x-on:click="poll(true)" :disabled="polling" style="margin-top:6px;"><i class="fas fa-rotate" :class="polling ? 'ts-spin' : ''" aria-hidden="true"></i> ตรวจสอบสถานะอีกครั้ง</button>
                </div>

                <div class="ts-row" style="justify-content:space-between; margin-top:10px;" x-show="riderAvailable" @if(! $available) x-cloak @endif>
                    <span class="ts-muted ts-small">
                        <span class="ts-dot live ts-tone-ok" style="display:inline-block; margin-right:6px;"></span>
                        <span x-text="distanceText"></span>
                        <span x-show="riderUpdatedAt"> · อัปเดต <span x-text="window.ts.timeAgo(riderUpdatedAt)"></span></span>
                    </span>
                    <button type="button" class="tp-btn tp-btn-sm" x-on:click="poll(true)" :disabled="polling"><i class="fas fa-rotate" :class="polling ? 'ts-spin' : ''" aria-hidden="true"></i> อัปเดต</button>
                </div>
            </div>
        </section>

        <div class="ts-grid" style="--ts-min:280px; margin-top:16px; align-items:start;">
            {{-- ════════ ไรเดอร์ ════════ --}}
            <section class="tp-card ts-stack">
                <h2 class="ts-h2"><i class="fas fa-motorcycle" style="color:var(--accent2);" aria-hidden="true"></i> ไรเดอร์ของคุณ</h2>
                @if($rider)
                    <div class="ts-row" style="gap:14px; flex-wrap:nowrap;">
                        <span class="ts-avatar" style="width:62px; height:62px; border-radius:20px;">
                            @if($riderPhotoUrl)
                                <img src="{{ $riderPhotoUrl }}" alt="รูปไรเดอร์" loading="lazy">
                            @else
                                <i class="fas {{ $rw::vehicleIcon($rider->vehicle_type) }}" aria-hidden="true"></i>
                            @endif
                        </span>
                        <div style="min-width:0;">
                            <b style="font-size:16px; display:block;">{{ $rider->full_name }}</b>
                            <span class="ts-muted ts-small">
                                {{ $vehicleText }}{{ $rider->vehicle_color ? ' สี'.$rider->vehicle_color : '' }}
                                @if($rider->vehicle_plate) · <b style="color:var(--ink);">{{ $rider->vehicle_plate }}</b>@endif
                            </span>
                            <div class="ts-star-view ts-small" style="margin-top:2px;">★ <span style="color:var(--ink);">{{ number_format((float) $rider->rating, 1) }}</span></div>
                        </div>
                    </div>
                    @if($riderPhone)
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $riderPhone) }}" class="ts-btn3d ts-tone-ok block"><i class="fas fa-phone" aria-hidden="true"></i> โทรหาไรเดอร์</a>
                    @endif
                @else
                    <div class="ts-empty" style="padding:14px;">
                        <span class="em" aria-hidden="true">🔍</span>
                        <b>กำลังหาไรเดอร์ใกล้ร้าน</b>
                        <span class="ts-muted ts-small">ระบบกำลังส่งงานให้ไรเดอร์ที่อยู่ใกล้ที่สุด</span>
                    </div>
                @endif

                {{-- แชร์ตำแหน่งของฉันให้ไรเดอร์ (เฉพาะเจ้าของออเดอร์) --}}
                <template x-if="share">
                    <div class="tp-inset ts-stack" style="border-radius:18px; padding:14px; gap:10px;">
                        <label class="ts-switch">
                            <input type="checkbox" :checked="sharing" x-on:change="toggleShare($event.target)" :disabled="shareBusy || !canShare">
                            <span class="track"></span>
                            <span style="font-size:14px; font-weight:700;">แชร์ตำแหน่งของฉันให้ไรเดอร์</span>
                        </label>
                        <p class="ts-help" style="margin:0;">
                            ไรเดอร์จะเห็นตำแหน่งของคุณ<b>เฉพาะออเดอร์นี้</b>ระหว่างมาส่งเท่านั้น เพื่อหาคุณเจอง่ายขึ้น
                            ระบบหยุดแชร์อัตโนมัติเมื่อส่งของเสร็จหรือยกเลิก และปิดเองได้ทุกเมื่อ (ต้องเปิดหน้านี้ไว้ระหว่างรอ)
                        </p>
                        <span class="ts-pill ts-tone-ok" x-show="sharing" style="align-self:flex-start;"><span class="ts-dot live"></span> กำลังแชร์ · ส่งล่าสุด <span x-text="lastShared ? window.ts.timeAgo(lastShared) : '—'"></span></span>
                        <span class="ts-help" x-show="!canShare" style="margin:0;">แชร์ได้เมื่อไรเดอร์รับงานแล้ว</span>
                        <p class="ts-err" x-show="shareError" x-text="shareError" x-cloak></p>
                    </div>
                </template>
            </section>

            {{-- ════════ จุดรับ / จุดส่ง ════════ --}}
            <section class="tp-card ts-stack">
                <h2 class="ts-h2"><i class="fas fa-route" style="color:var(--accent2);" aria-hidden="true"></i> เส้นทาง</h2>
                <ol class="sf-tl">
                    <li class="sf-tl-item">
                        <span class="sf-tl-dot ts-tone-ok" style="background:linear-gradient(135deg, var(--ts-ok), color-mix(in srgb, var(--ts-ok) 70%, var(--ink)));"><i class="fas fa-store" aria-hidden="true"></i></span>
                        <div style="min-width:0; padding-top:4px;">
                            <b style="font-size:13.5px;">รับของที่ {{ $pickupLocation['contact_name'] ?? 'ร้านค้า' }}</b>
                            <p class="ts-muted" style="margin:3px 0 0; font-size:12.5px; line-height:1.5; overflow-wrap:anywhere;">{{ $pickupLocation['address'] ?? 'ไม่ระบุที่อยู่' }}</p>
                        </div>
                    </li>
                    <li class="sf-tl-item">
                        <span class="sf-tl-dot"><i class="fas fa-house" aria-hidden="true"></i></span>
                        <div style="min-width:0; padding-top:4px;">
                            <b style="font-size:13.5px;">ส่งถึง</b>
                            <p class="ts-muted" style="margin:3px 0 0; font-size:12.5px; line-height:1.5; overflow-wrap:anywhere;">{{ $customerLocation['address'] ?? 'ไม่ระบุที่อยู่' }}</p>
                        </div>
                    </li>
                </ol>
                @if($order)
                    <a href="{{ route('taladsod.orders.show', $order->id) }}" class="tp-btn" rel="nofollow"><i class="fas fa-receipt" aria-hidden="true"></i> ดูรายละเอียดออเดอร์ (ต้องเข้าสู่ระบบ)</a>
                @endif
            </section>
        </div>
    </div>
</main>

<x-theme-v4.public-footer />
@endsection

@push('scripts')
<script>
    /**
     * ติดตามไรเดอร์สด: poll ตำแหน่ง + เส้นทาง, หยุดเมื่องานจบ/ลิงก์หมดอายุ/แท็บถูกซ่อน
     * ผู้ซื้อที่ login เป็นเจ้าของออเดอร์ เปิดแชร์ตำแหน่งตัวเองให้ไรเดอร์ได้ (ส่งทุก 30 วินาทีระหว่างเปิดหน้านี้)
     */
    window.tsTrack = function (cfg) {
        let map = null;
        let riderMarker = null;
        let routeLine = null;
        let pollTimer = null;
        let routeTimer = null;
        let shareTimer = null;

        const R = 6371;
        const rad = (d) => d * Math.PI / 180;
        const km = (a, b) => {
            const dLat = rad(b.lat - a.lat);
            const dLng = rad(b.lng - a.lng);
            const x = Math.sin(dLat / 2) ** 2 + Math.cos(rad(a.lat)) * Math.cos(rad(b.lat)) * Math.sin(dLng / 2) ** 2;
            return R * 2 * Math.atan2(Math.sqrt(x), Math.sqrt(1 - x));
        };

        // สถานะจบงานแล้ว → หยุด poll
        const TERMINAL = ['completed', 'cancelled', 'failed'];

        return {
            active: !!cfg.active, watch: !!cfg.watch, status: cfg.status, statusText: cfg.statusText || '', reasonText: cfg.reasonText || '',
            rider: cfg.rider, riderUpdatedAt: cfg.rider ? cfg.rider.updated_at : null,
            expired: false, polling: false,
            share: cfg.share, sharing: false, shareBusy: false, canShare: !!cfg.active, lastShared: null, shareError: '',

            get riderAvailable() { return !!this.rider && !this.expired; },

            get distanceText() {
                if (!this.rider || !cfg.dropoff) { return 'ไรเดอร์กำลังเดินทาง'; }
                const d = km(this.rider, cfg.dropoff);
                const mins = Math.max(1, Math.round(d / 25 * 60));
                return 'ไรเดอร์อยู่ห่างคุณ ' + window.ts.distance(d) + ' · ประมาณ ' + mins + ' นาที';
            },

            init() {
                this.$nextTick(() => this.drawMap());
                // งานยังไม่จบ (รวมตอนรอไรเดอร์รับงาน) → poll ต่อ เพื่อให้เห็นเมื่อมีไรเดอร์รับงาน/เปลี่ยนสถานะ
                if (this.watch) { this.schedule(); }
                if (this.active) { this.loadRoute(); }
                if (this.share) { this.loadShareState(); }
                document.addEventListener('visibilitychange', () => {
                    if (document.visibilityState === 'visible' && this.watch) { this.poll(); }
                });
            },

            schedule() {
                clearTimeout(pollTimer);
                pollTimer = setTimeout(() => this.poll(), cfg.pollMs);
            },

            async poll(manual) {
                clearTimeout(pollTimer);
                if (!manual && document.visibilityState !== 'visible') { return; }
                this.polling = true;
                const r = await window.ts.get(cfg.locationUrl);
                this.polling = false;

                if (r.status === 404) {
                    this.expired = true;
                    this.active = false;
                    this.watch = false;
                    this.stopShareLoop();
                    return;
                }
                if (r.body && r.body.success) {
                    const loc = r.body.location || {};
                    const prev = this.status;
                    this.status = r.body.job_status;
                    this.statusText = r.body.job_status_text || this.statusText;
                    this.active = !!r.body.is_active;
                    this.watch = TERMINAL.indexOf(String(this.status)) === -1;
                    this.canShare = this.active;
                    if (loc.available) {
                        this.rider = { lat: Number(loc.latitude), lng: Number(loc.longitude) };
                        this.riderUpdatedAt = loc.updated_at || null;
                        this.$nextTick(() => this.drawMap());
                    } else {
                        this.rider = null;
                        this.reasonText = loc.reason === 'consent_missing'
                            ? 'ไรเดอร์ยังไม่ได้เปิดแชร์ตำแหน่ง — จะเห็นบนแผนที่เมื่อไรเดอร์เริ่มแชร์'
                            : (this.active ? 'กำลังรอสัญญาณ GPS จากไรเดอร์' : 'การจัดส่งจบแล้ว จึงไม่แสดงตำแหน่งไรเดอร์');
                    }
                    // สถานะเปลี่ยน (เช่น รับของแล้ว / ส่งถึงแล้ว) → โหลดหน้าใหม่ให้แถบขั้นตอนตรง
                    if (prev && prev !== this.status) {
                        window.ts.notify('สถานะอัปเดต: ' + this.statusText, 'info');
                        setTimeout(() => window.location.reload(), 1600);
                        return;
                    }
                    if (!this.active) {
                        this.stopShareLoop();
                    }
                }
                // poll ต่อจนกว่างานจบ (รอไรเดอร์ / ไรเดอร์คืนงานแล้วรอคนใหม่ ก็ยังอัปเดตเอง)
                if (this.watch) { this.schedule(); }
            },

            async loadRoute() {
                clearTimeout(routeTimer);
                const r = await window.ts.get(cfg.routeUrl);
                if (r.body && Array.isArray(r.body.route) && map && r.body.route.length > 1) {
                    const pts = r.body.route.map((p) => [p.lat, p.lng]);
                    if (!routeLine) {
                        // สีเส้นทางตามธีม (อ่านตัวแปร CSS) — อ่านไม่ได้ใช้สีเริ่มต้นของ Leaflet
                        const themeColor = getComputedStyle(document.documentElement).getPropertyValue('--accent2').trim();
                        const opts = { weight: 5, opacity: 0.75 };
                        if (themeColor) { opts.color = themeColor; }
                        routeLine = window.L.polyline(pts, opts).addTo(map);
                    } else {
                        routeLine.setLatLngs(pts);
                    }
                }
                if (this.active) { routeTimer = setTimeout(() => this.loadRoute(), 60000); }
            },

            drawMap() {
                if (!this.riderAvailable || !window.tpMap || !window.tpMap.ready()) { return; }
                if (!map) {
                    map = window.tpMap.create(this.$refs.map, this.rider.lat, this.rider.lng, 15);
                    if (!map) { return; }
                    if (cfg.pickup) { window.tpMap.pin(map, cfg.pickup.lat, cfg.pickup.lng, 'shop'); }
                    if (cfg.dropoff) { window.tpMap.pin(map, cfg.dropoff.lat, cfg.dropoff.lng, 'home'); }
                    riderMarker = window.tpMap.pin(map, this.rider.lat, this.rider.lng, 'rider');
                    const bounds = [[this.rider.lat, this.rider.lng]];
                    if (cfg.pickup) { bounds.push([cfg.pickup.lat, cfg.pickup.lng]); }
                    if (cfg.dropoff) { bounds.push([cfg.dropoff.lat, cfg.dropoff.lng]); }
                    if (bounds.length > 1) { map.fitBounds(bounds, { padding: [40, 40], maxZoom: 16 }); }
                    [250, 900].forEach((ms) => setTimeout(() => map && map.invalidateSize(), ms));
                    return;
                }
                riderMarker.setLatLng([this.rider.lat, this.rider.lng]);
                map.invalidateSize();
            },

            // ===== แชร์ตำแหน่งผู้ซื้อ =====

            async loadShareState() {
                const r = await window.ts.get(this.share.riderLocationUrl);
                if (r.ok && r.data) {
                    this.canShare = !!r.data.can_share_location;
                    this.sharing = !!(r.data.customer_sharing && r.data.customer_sharing.enabled);
                    this.lastShared = r.data.customer_sharing ? r.data.customer_sharing.last_shared_at : null;
                    if (this.sharing) { this.startShareLoop(); }
                }
            },

            // el = สวิตช์ที่ผู้ใช้กด — ทำไม่สำเร็จต้องตั้งสวิตช์กลับเอง
            // (:checked ไม่วาดใหม่ถ้าค่า sharing ไม่เปลี่ยน → สวิตช์ค้าง "เปิด" ทั้งที่ไม่ได้แชร์)
            async toggleShare(el) {
                const on = !!el.checked;
                this.shareBusy = true;
                this.shareError = '';
                if (!on) {
                    this.stopShareLoop();
                    const r = await window.ts.post(this.share.shareUrl, { share: 0 });
                    this.shareBusy = false;
                    this.sharing = false;
                    el.checked = false;
                    if (!r.ok) { this.shareError = r.message; }
                    return;
                }
                try {
                    const p = await window.ts.geo();
                    const r = await window.ts.post(this.share.shareUrl, { share: 1, latitude: p.lat, longitude: p.lng });
                    if (!r.ok) {
                        this.sharing = false;
                        this.shareError = r.message;
                    } else {
                        this.sharing = true;
                        this.lastShared = new Date().toISOString();
                        this.startShareLoop();
                        window.ts.notify(r.message, 'success');
                    }
                } catch (e) {
                    this.sharing = false;
                    this.shareError = (e && e.message) || 'หาตำแหน่งไม่ได้';
                } finally {
                    this.shareBusy = false;
                    el.checked = this.sharing;
                }
            },

            startShareLoop() {
                clearInterval(shareTimer);
                shareTimer = setInterval(async () => {
                    if (!this.sharing || document.visibilityState !== 'visible') { return; }
                    try {
                        const p = await window.ts.geo({ maximumAge: 15000 });
                        const r = await window.ts.post(this.share.shareUrl, { share: 1, latitude: p.lat, longitude: p.lng });
                        if (r.ok) {
                            this.lastShared = new Date().toISOString();
                        } else if (r.status === 409) {
                            // งานจบแล้ว → ระบบหยุดแชร์ให้เอง
                            this.sharing = false;
                            this.stopShareLoop();
                        }
                    } catch (e) {
                        // หาตำแหน่งไม่ได้รอบนี้ — ลองใหม่รอบหน้า
                    }
                }, 30000);
            },

            stopShareLoop() {
                clearInterval(shareTimer);
                shareTimer = null;
            }
        };
    };
</script>
@endpush
