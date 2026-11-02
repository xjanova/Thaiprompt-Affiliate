{{--
    ติดตามผู้ให้บริการแบบสด (ธีมโนวาเต็ม)
    ข้อมูลจาก User\ServiceBookingController::trackBooking (คำขอ HTML) — $booking, $serviceLocation, $trackable
    - ดึงสถานะซ้ำทุก 15 วินาที: GET user.bookings.track (Accept: application/json)
    - แชร์ตำแหน่งตัวเอง (เลือกเปิดเอง): POST user.bookings.location {latitude, longitude}
    🐛 เดิมไม่มีอะไรเรนเดอร์หน้านี้ (ลิงก์ "ติดตาม" ได้ JSON ดิบ) และหน้าเดิมเรียก /api/v1 ที่ต้องใช้โทเคน → 401 ทุกครั้ง
--}}
@extends('layouts.user-nova')

@section('title', 'ติดตามการจอง #'.$booking->booking_number)

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/orders-shipping.css') }}?v={{ filemtime(public_path('theme-nova/member/orders-shipping.css')) }}">@endpush

@php
    $bk = $booking;
    $hasHome = $serviceLocation && $serviceLocation->latitude && $serviceLocation->longitude;
    $trackCfg = [
        'url' => route('user.bookings.track', $bk),
        'shareUrl' => route('user.bookings.location', $bk),
        'trackable' => (bool) $trackable,
        'status' => $bk->status,
        'statusText' => $bk->status_text,
        'home' => $hasHome ? ['lat' => (float) $serviceLocation->latitude, 'lng' => (float) $serviceLocation->longitude] : null,
    ];
@endphp

@section('content')
@include('shop.orders.partials.map')

<div class="nvm-stack" x-data="nvmOsBookingTrack(@js($trackCfg))">

<section class="nvm-ph" aria-labelledby="nvm-os-bt-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.bookings.show', $bk) }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>รายละเอียดการจอง</a>
            <div class="nvm-eyebrow nvm-latin">Live tracking</div>
            <h1 id="nvm-os-bt-h">ติดตามผู้ให้บริการ</h1>
            <p class="nvm-ph__lead"><span>{{ $bk->service?->name ?? 'บริการ' }}</span> · <span class="nvm-num">#{{ $bk->booking_number }}</span></p>
            <div class="nvm-pills">
                <span class="nvm-pill nvm-pill--glass" x-text="statusText">{{ $bk->status_text }}</span>
                <span class="nvm-pill nvm-pill--ok nvm-os-live" x-show="trackable" @if(! $trackable) x-cloak @endif><span class="nvm-os-dot is-on" aria-hidden="true"></span>อัปเดตสด</span>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/deco/scooter.webp') }}" alt="" width="200" height="200">
    </div>
</section>

{{-- ยังไม่ถึง/เลยช่วงติดตาม --}}
<section class="nvm-card nvm-alert nvm-alert--info" role="status" x-show="! trackable" @if($trackable) x-cloak @endif>
    <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></span>
    <div class="nvm-alert__txt">
        <b>ยังติดตามตำแหน่งไม่ได้ตอนนี้</b>
        <p>ติดตามได้ตั้งแต่ผู้ให้บริการรับงานจนถึงระหว่างให้บริการ — สถานะตอนนี้: <span x-text="statusText">{{ $bk->status_text }}</span></p>
    </div>
</section>

<div class="nvm-split">
    <div class="nvm-stack">
        <section class="nvm-card nvm-box" aria-labelledby="nvm-os-bt-map-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Map</small><h2 id="nvm-os-bt-map-h">ตำแหน่งบนแผนที่</h2></div></div>
            <div class="nvm-os-map nvm-os-map--lg" x-ref="map" role="img" aria-label="แผนที่ตำแหน่งผู้ให้บริการและสถานที่ให้บริการ"></div>
            <p class="nvm-help nvm-gap-t" x-show="! hasMap" x-cloak>ยังไม่มีพิกัดให้แสดงบนแผนที่</p>

            <div class="nvm-minis nvm-gap-t">
                <div class="nvm-mini"><small>ระยะทาง</small><b class="nvm-num" x-text="distance !== null ? distance + ' กม.' : '—'">—</b></div>
                <div class="nvm-mini"><small>ถึงในอีกประมาณ</small><b class="nvm-num" x-text="eta !== null ? eta + ' นาที' : '—'">—</b></div>
                <div class="nvm-mini"><small>อัปเดตล่าสุด</small><b class="nvm-num" x-text="updatedAt || '—'">—</b></div>
            </div>
            <p class="nvm-desc nvm-gap-t" x-show="note" x-cloak x-text="note"></p>
        </section>
    </div>

    <aside class="nvm-stack">
        <section class="nvm-card nvm-box" aria-labelledby="nvm-os-bt-pv-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Provider</small><h2 id="nvm-os-bt-pv-h">ผู้ให้บริการ</h2></div></div>
            <div class="nvm-os-who">
                <span class="nvm-os-who__av"><svg class="nvm-ico"><use href="#nvm-i-user"/></svg></span>
                <div class="nvm-os-who__t">
                    <b x-text="providerName || 'กำลังหาผู้ให้บริการ'">{{ $bk->provider ? ($bk->provider->display_name ?: $bk->provider->name) : 'กำลังหาผู้ให้บริการ' }}</b>
                </div>
            </div>
            <div class="nvm-os-acts nvm-gap-t" x-show="trackable && providerPhone" x-cloak>
                <a class="nvm-btn nvm-btn--line nvm-btn--sm" :href="'tel:' + providerPhone"><svg class="nvm-ico"><use href="#nvm-i-id"/></svg>โทรติดต่อ</a>
            </div>
        </section>

        <section class="nvm-card nvm-box" aria-labelledby="nvm-os-bt-sh-h" x-show="trackable" @if(! $trackable) x-cloak @endif>
            <div class="nvm-sec-h"><div><small class="nvm-latin">Share location</small><h2 id="nvm-os-bt-sh-h">แชร์ตำแหน่งของฉัน</h2></div></div>
            <p class="nvm-desc">ช่วยให้ผู้ให้บริการหาคุณเจอง่ายขึ้น ส่งเฉพาะตอนที่หน้านี้เปิดอยู่ ปิดได้ทุกเมื่อ</p>
            <label class="nvm-check nvm-os-share nvm-gap-t">
                <input type="checkbox" :checked="sharing" @change="toggleShare($event.target.checked)" :disabled="! canGeo">
                <span x-text="sharing ? 'กำลังแชร์ตำแหน่ง' : 'แชร์ตำแหน่งให้ผู้ให้บริการ'">แชร์ตำแหน่งให้ผู้ให้บริการ</span>
            </label>
            <p class="nvm-help" x-show="! canGeo" x-cloak>เบราว์เซอร์นี้ไม่รองรับการระบุตำแหน่ง</p>
        </section>

        @if($serviceLocation)
            <section class="nvm-card nvm-box" aria-labelledby="nvm-os-bt-loc-h">
                <div class="nvm-sec-h"><div><small class="nvm-latin">Location</small><h2 id="nvm-os-bt-loc-h">สถานที่ให้บริการ</h2></div></div>
                <ul class="nvm-os-lines">
                    <li><svg class="nvm-ico"><use href="#nvm-i-pin"/></svg><span>{{ $serviceLocation->address }}</span></li>
                    @if($serviceLocation->landmark)
                        <li><svg class="nvm-ico"><use href="#nvm-i-info"/></svg><span>จุดสังเกต: {{ $serviceLocation->landmark }}</span></li>
                    @endif
                </ul>
            </section>
        @endif

        <a class="nvm-btn nvm-btn--line nvm-btn--block" href="{{ route('user.bookings.show', $bk) }}"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>ดูรายละเอียดการจอง</a>
    </aside>
</div>

</div>
@endsection

@push('scripts')
<script>
    /**
     * หน้าติดตามการจอง: ดึงสถานะซ้ำ + ขยับหมุดผู้ให้บริการ + แชร์ตำแหน่ง (เลือกเปิดเอง)
     * หยุดดึงเองเมื่อจบงาน/ยกเลิก หรือออกจากหน้า
     */
    function nvmOsBookingTrack(cfg) {
        const ENDED = ['completed', 'cancelled', 'provider_rejected'];
        const csrf = () => (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
        const notify = (m, t) => window.dispatchEvent(new CustomEvent('notify', { detail: { message: m, type: t || 'info' } }));

        return {
            trackable: cfg.trackable,
            status: cfg.status,
            statusText: cfg.statusText,
            providerName: '',
            providerPhone: '',
            distance: null,
            eta: null,
            updatedAt: '',
            note: '',
            hasMap: false,
            sharing: false,
            canGeo: 'geolocation' in navigator,
            map: null,
            riderPin: null,
            timer: null,
            watchId: null,
            lastSent: 0,
            busy: false,

            init() {
                this.$nextTick(() => {
                    if (cfg.home && window.nvmOsMap && window.nvmOsMap.ready()) {
                        this.map = window.nvmOsMap.create(this.$refs.map, cfg.home.lat, cfg.home.lng, 14);
                        window.nvmOsMap.pin(this.map, cfg.home.lat, cfg.home.lng, 'home');
                        this.hasMap = true;
                    }
                    this.poll();
                    if (! ENDED.includes(this.status)) {
                        this.timer = setInterval(() => this.poll(), 15000);
                    }
                });
                window.addEventListener('pagehide', () => this.stop());
            },

            stop() {
                if (this.timer) { clearInterval(this.timer); this.timer = null; }
                this.stopShare();
            },

            async poll() {
                if (this.busy || document.hidden) { return; }
                this.busy = true;
                try {
                    const res = await fetch(cfg.url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
                    if (! res.ok) { return; }
                    const d = await res.json();
                    this.status = d.booking.status;
                    this.statusText = d.booking.status_text;
                    this.trackable = !! d.booking.trackable;
                    this.providerName = d.provider ? d.provider.name : '';
                    this.providerPhone = d.provider && d.provider.phone ? String(d.provider.phone).replace(/[^0-9+]/g, '') : '';
                    const t = d.latest_tracking;
                    this.distance = t && t.distance_to_customer_km !== null ? Number(t.distance_to_customer_km).toFixed(1) : null;
                    this.eta = t && t.estimated_arrival_minutes !== null ? Math.round(Number(t.estimated_arrival_minutes)) : null;
                    this.note = t && t.notes ? t.notes : '';
                    this.updatedAt = new Date().toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' });
                    this.moveRider(d.provider_location);
                    if (ENDED.includes(this.status) || ! this.trackable) { this.stopShare(); }
                    if (ENDED.includes(this.status)) { this.stop(); }
                } catch (e) {
                    // เน็ตหลุดชั่วคราว — รอบหน้าลองใหม่เอง
                } finally {
                    this.busy = false;
                }
            },

            moveRider(loc) {
                if (! loc || ! window.nvmOsMap || ! window.nvmOsMap.ready()) {
                    if (! loc && this.riderPin && this.map) { this.map.removeLayer(this.riderPin); this.riderPin = null; }
                    return;
                }
                const lat = Number(loc.latitude), lng = Number(loc.longitude);
                if (! this.map) {
                    this.map = window.nvmOsMap.create(this.$refs.map, lat, lng, 14);
                    this.hasMap = true;
                }
                if (this.riderPin) {
                    this.riderPin.setLatLng([lat, lng]);
                } else {
                    this.riderPin = window.nvmOsMap.pin(this.map, lat, lng, 'rider');
                }
                if (cfg.home) {
                    this.map.fitBounds([[lat, lng], [cfg.home.lat, cfg.home.lng]], { padding: [40, 40], maxZoom: 16 });
                } else {
                    this.map.panTo([lat, lng]);
                }
            },

            toggleShare(on) {
                if (! on) { this.stopShare(); return; }
                if (! this.canGeo) { return; }
                this.sharing = true;
                this.watchId = navigator.geolocation.watchPosition(
                    (p) => this.send(p.coords.latitude, p.coords.longitude),
                    () => { notify('เปิดตำแหน่งไม่ได้ กรุณาอนุญาตการเข้าถึงตำแหน่งในเบราว์เซอร์', 'error'); this.stopShare(); },
                    { enableHighAccuracy: true, maximumAge: 15000, timeout: 20000 }
                );
            },

            stopShare() {
                if (this.watchId !== null) { navigator.geolocation.clearWatch(this.watchId); this.watchId = null; }
                this.sharing = false;
            },

            async send(lat, lng) {
                // ส่งไม่ถี่เกินทุก 20 วินาที (เส้นทางจำกัด 20 ครั้ง/นาที)
                const now = Date.now();
                if (now - this.lastSent < 20000) { return; }
                this.lastSent = now;
                try {
                    const res = await fetch(cfg.shareUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
                        body: JSON.stringify({ latitude: lat, longitude: lng })
                    });
                    if (res.status === 422) { notify('การจองนี้ไม่อยู่ในช่วงที่แชร์ตำแหน่งได้แล้ว', 'info'); this.stopShare(); }
                } catch (e) {
                    // ส่งไม่ออกรอบนี้ — รอบถัดไปลองใหม่
                }
            }
        };
    }
</script>
@endpush
