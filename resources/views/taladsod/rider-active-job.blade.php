{{--
 | หน้างานที่กำลังส่งของไรเดอร์ (taladsod.rider.active-job) — ธีม V4 (user-v4)
 | Controller: FreshMarket\RiderTrackingController@riderActiveJob (เจ้าของงานเท่านั้น · งานจบแล้วถูกพาไปหน้ารายละเอียดงาน)
 | ตัวแปร: $job, $rider, $orderNumber, $customerLocation {latitude, longitude, address, contact_name, contact_phone, live_location},
 |         $pickupLocation {latitude, longitude, address, contact_name, contact_phone}, $gpsUpdateInterval (ms), $gpsLostTimeout (ms),
 |         $maxWarnings, $codAmount, $endpoints {location, status, deliver, fail, gps_lost, gps_off, job_detail, jobs}
 | คำสั่ง (session + CSRF, ตอบ JSON {success, message, data{job}}):
 |   POST location {latitude, longitude, accuracy?, speed?, heading?} ทุก gpsUpdateInterval
 |   POST status {status: picking_up|picked_up|delivering, photo?} · POST deliver multipart {photo*, latitude?, longitude?, cod_collected, note?}
 |   POST fail {reason_code, note? (บังคับเมื่อ other), photo?} · POST gps_lost · POST gps_off
 | แผนที่: OpenStreetMap + Leaflet (ไม่ใช้ Google Maps API key)
 --}}
@extends('layouts.user-v4')

@section('title', 'งานส่งของ #'.$job->job_number)

@push('styles')
    @include('user.rider.partials.styles')
    <style>
        /* หน้าส่งงาน: ปุ่มลอย Theme Studio / น้อง Eve ทับแถบปุ่มหลักด้านล่าง (ส่งของสำเร็จ / ส่งไม่สำเร็จ) → ซ่อนเฉพาะหน้านี้ */
        .tp-studio-fab, .eve-w { display: none !important; }
    </style>
@endpush

@php
    $ui = \App\Support\TaladsodWebUi::class;
    $rw = \App\Support\RiderWebUi::class;
    $jobData = $job->toApiDetail($rider);
    $failureReasons = array_intersect_key(\App\Models\RiderJob::FAILURE_REASONS, array_flip(\App\Models\RiderJob::RIDER_FAILURE_REASONS));
    $point = fn ($p) => $rw::validPoint($p['latitude'] ?? null, $p['longitude'] ?? null)
        ? ['lat' => (float) $p['latitude'], 'lng' => (float) $p['longitude']]
        : null;

    $ajCfg = [
        'job' => $jobData,
        'endpoints' => $endpoints,
        'sendMs' => max(10000, (int) $gpsUpdateInterval),
        'lostMs' => max(30000, (int) $gpsLostTimeout),
        'maxWarnings' => (int) $maxWarnings,
        'cod' => (float) $codAmount,
        'pickup' => $point($pickupLocation),
        'dropoff' => $point($customerLocation),
    ];
@endphp

@section('content')
<x-theme-v4.shop-kit />
@include('taladsod.partials.kit')
<x-theme-v4.leaflet />

<div class="ts-scope rd-scope ts-stack" style="gap:16px; max-width:900px; margin:0 auto; padding-bottom:100px;" x-data="tsActiveJob({{ \Illuminate\Support\Js::from($ajCfg) }})">
    @include('user.rider.partials.nav', ['rider' => $rider, 'active' => 'jobs'])

    {{-- ════════ GPS หาย: เต็มจอ ════════ --}}
    <div class="ts-dialog-bg" x-show="gpsLost" x-cloak style="align-items:center; z-index:120;">
        <div class="tp-card ts-dialog ts-stack" role="alertdialog" aria-modal="true" aria-labelledby="aj-gps-h" style="text-align:center; align-items:center;">
            <span class="tp-tile" style="width:72px; height:72px; border-radius:24px; font-size:30px; background:linear-gradient(135deg, var(--ts-bad), color-mix(in srgb, var(--ts-bad) 70%, var(--ink)));"><i class="fas fa-location-crosshairs" aria-hidden="true"></i></span>
            <h2 id="aj-gps-h" class="ts-h2" style="justify-content:center; font-size:20px;">GPS ไม่ทำงาน</h2>
            <p class="ts-muted" style="margin:0; font-size:13.5px; line-height:1.6;">ต้องเปิดตำแหน่งตลอดการส่งงาน เพื่อให้ลูกค้าและร้านติดตามได้ — เปิด "ตำแหน่ง/Location" ในเครื่อง แล้วกดลองใหม่</p>
            <span class="ts-pill ts-tone-bad">เตือนครั้งที่ <b x-text="warningCount"></b> / <span x-text="cfg.maxWarnings"></span></span>
            <button type="button" class="ts-btn3d ts-tone-ok block" x-on:click="retryGps()"><i class="fas fa-rotate" aria-hidden="true"></i> ลองเปิด GPS อีกครั้ง</button>
            <button type="button" class="tp-btn ts-btn-ghost ts-tone-bad" x-show="warningCount >= cfg.maxWarnings" x-on:click="askGpsOff = true"><i class="fas fa-power-off" aria-hidden="true"></i> ปิด GPS และหยุดงาน</button>
        </div>
    </div>

    {{-- ════════ แถบสถานะ GPS ════════ --}}
    <div class="tp-card ts-row" style="justify-content:space-between; padding:10px 16px; position:sticky; top:8px; z-index:30;" :class="gpsActive ? 'ts-tone-ok' : 'ts-tone-bad'">
        <span class="ts-row" style="gap:8px; font-size:13px; font-weight:700;">
            <span class="ts-dot" :class="gpsActive ? 'live' : ''"></span>
            <span x-text="gpsActive ? 'GPS ทำงานปกติ · ส่งตำแหน่งให้ลูกค้าอยู่' : 'กำลังหาตำแหน่ง GPS...'">กำลังหาตำแหน่ง GPS...</span>
        </span>
        <span class="ts-muted ts-small">
            <span x-show="accuracy" x-text="accuracy ? ('แม่นยำ ±' + Math.round(accuracy) + ' ม.') : ''"></span>
            <span x-show="speed > 0" x-text="speed > 0 ? (' · ' + Math.round(speed * 3.6) + ' กม./ชม.') : ''"></span>
        </span>
    </div>

    {{-- ════════ หัวงาน ════════ --}}
    <section class="tp-card ts-stack" style="gap:10px;">
        <div class="ts-row" style="justify-content:space-between;">
            <div style="min-width:0;">
                <div class="sf-kicker">งานที่กำลังทำ</div>
                <h1 class="ts-h1" style="font-size:clamp(20px,4vw,26px);">#{{ $job->job_number }}</h1>
                @if($orderNumber)
                    <div class="ts-muted ts-small">ออเดอร์ #{{ $orderNumber }}</div>
                @endif
            </div>
            <span class="ts-pill solid ts-tone-info" style="font-size:13px; padding:9px 14px;"><i class="fas fa-motorcycle" aria-hidden="true"></i> <span x-text="job.status_text">{{ $job->status_text }}</span></span>
        </div>
        <div class="ts-grid" style="--ts-min:140px; gap:10px;">
            <div class="tp-inset" style="border-radius:14px; padding:10px 12px;"><span class="ts-muted ts-small">ค่าส่งที่คุณได้</span><div class="ts-money" style="font-size:20px;">฿{{ $ui::money($jobData['rider_earnings'] ?? $job->rider_earnings) }}</div></div>
            <div class="tp-inset" style="border-radius:14px; padding:10px 12px;"><span class="ts-muted ts-small">ระยะทาง</span><div class="ts-num" style="font-size:20px;">{{ $ui::distance($job->distance_km) }}</div></div>
            @if($codAmount > 0)
                <div class="tp-inset ts-tone-warn" style="border-radius:14px; padding:10px 12px; box-shadow:var(--inset-sm), 0 0 0 2px color-mix(in srgb, var(--ts-warn) 50%, transparent);">
                    <span class="ts-muted ts-small">เก็บเงินปลายทาง</span><div class="ts-num" style="font-size:20px; color:var(--deep2);">฿{{ $ui::money($codAmount) }}</div>
                </div>
            @endif
        </div>
        @if($jobData['items_summary'] ?? null)
            <p class="ts-muted" style="margin:0; font-size:13px;"><i class="fas fa-box" aria-hidden="true"></i> {{ $jobData['items_summary'] }}</p>
        @endif
    </section>

    {{-- ════════ แผนที่ ════════ --}}
    <section class="tp-card ts-stack" style="padding:12px; gap:10px;">
        <div class="ts-map lg" x-ref="map" aria-label="แผนที่ตำแหน่งของคุณและจุดหมาย"></div>
        <div class="ts-row" style="gap:8px;">
            <a :href="directionsUrl" target="_blank" rel="noopener" class="ts-btn3d ts-tone-info" style="flex:1;"><i class="fas fa-diamond-turn-right" aria-hidden="true"></i> <span x-text="beforePickup ? 'นำทางไปร้าน' : 'นำทางไปหาลูกค้า'">นำทาง</span></a>
            <button type="button" class="ts-icon-btn" x-on:click="centerOnMe()" aria-label="ไปที่ตำแหน่งของฉัน"><i class="fas fa-location-crosshairs" aria-hidden="true"></i></button>
        </div>
        <p class="ts-help" style="margin:0;" x-show="job.customer_live_location"><span class="ts-dot live ts-tone-ok" style="display:inline-block; margin-right:6px;"></span>ลูกค้าแชร์ตำแหน่งสดให้คุณ (หมุดสีส้ม)</p>
    </section>

    {{-- ════════ จุดหมาย ════════ --}}
    <div class="ts-grid" style="--ts-min:280px; align-items:start;">
        <section class="tp-card ts-stack" :style="{ boxShadow: beforePickup ? 'var(--card-shadow), 0 0 0 2px color-mix(in srgb, var(--ts-ok) 55%, transparent)' : 'var(--card-shadow)', opacity: beforePickup ? 1 : 0.75 }">
            <h2 class="ts-h2"><i class="fas fa-store" style="color:var(--ts-ok);" aria-hidden="true"></i> จุดรับของ</h2>
            <b style="font-size:14.5px;">{{ $pickupLocation['contact_name'] ?? 'ร้านค้า' }}</b>
            <p class="ts-muted" style="margin:0; font-size:13px; line-height:1.6; overflow-wrap:anywhere;">{{ $pickupLocation['address'] ?? '—' }}</p>
            @if(! empty($pickupLocation['contact_phone']))
                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $pickupLocation['contact_phone']) }}" class="tp-btn"><i class="fas fa-phone" aria-hidden="true"></i> โทรหาร้าน</a>
            @endif
        </section>
        <section class="tp-card ts-stack" :style="{ boxShadow: !beforePickup ? 'var(--card-shadow), 0 0 0 2px color-mix(in srgb, var(--accent2) 55%, transparent)' : 'var(--card-shadow)' }">
            <h2 class="ts-h2"><i class="fas fa-house" style="color:var(--accent2);" aria-hidden="true"></i> จุดส่งของ</h2>
            <b style="font-size:14.5px;">{{ $customerLocation['contact_name'] ?? 'ลูกค้า' }}</b>
            <p class="ts-muted" style="margin:0; font-size:13px; line-height:1.6; overflow-wrap:anywhere;">{{ $customerLocation['address'] ?? '—' }}</p>
            @if(! empty($jobData['dropoff']['notes']))
                <p class="sf-note sf-note-info" style="margin:0;">โน้ต: {{ $jobData['dropoff']['notes'] }}</p>
            @endif
            @if(! empty($customerLocation['contact_phone']))
                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $customerLocation['contact_phone']) }}" class="tp-btn"><i class="fas fa-phone" aria-hidden="true"></i> โทรหาลูกค้า</a>
            @endif
        </section>
    </div>

    <a href="{{ $endpoints['job_detail'] }}" class="ts-link ts-small" style="align-self:center;">ดูรายละเอียดงาน / คืนงาน <i class="fas fa-arrow-right" aria-hidden="true"></i></a>

    {{-- ════════ ปุ่มหลักติดล่างจอ ════════ --}}
    <div class="ts-bottom-bar" style="display:flex;">
        <div class="ts-row" style="gap:8px; width:100%; max-width:900px; margin:0 auto; flex-wrap:nowrap;">
            <template x-if="has('picking_up')">
                <button type="button" class="ts-btn3d soft" x-on:click="setStatus('picking_up')" :disabled="busy"><i class="fas fa-route" aria-hidden="true"></i><span class="sf-hide-sm"> กำลังไปร้าน</span></button>
            </template>
            <template x-if="has('picked_up')">
                <button type="button" class="ts-btn3d ts-tone-ok" style="flex:1;" x-on:click="dialog = 'pickup'" :disabled="busy"><i class="fas fa-box" aria-hidden="true"></i> รับของแล้ว</button>
            </template>
            <template x-if="has('delivering')">
                <button type="button" class="ts-btn3d soft" x-on:click="setStatus('delivering')" :disabled="busy"><i class="fas fa-motorcycle" aria-hidden="true"></i><span class="sf-hide-sm"> เริ่มนำส่ง</span></button>
            </template>
            <template x-if="has('deliver')">
                <button type="button" class="ts-btn3d ts-tone-gold" style="flex:1;" x-on:click="dialog = 'deliver'" :disabled="busy"><i class="fas fa-flag-checkered" aria-hidden="true"></i> ส่งของสำเร็จ</button>
            </template>
            <template x-if="has('fail')">
                <button type="button" class="ts-btn3d soft" x-on:click="dialog = 'fail'" :disabled="busy" aria-label="ส่งไม่สำเร็จ"><i class="fas fa-triangle-exclamation" style="color:var(--ts-bad);" aria-hidden="true"></i></button>
            </template>
        </div>
    </div>

    {{-- ════════ กล่องรับของ ════════ --}}
    <div class="ts-dialog-bg" x-show="dialog === 'pickup'" x-cloak x-transition.opacity x-on:click.self="dialog = null">
        <div class="tp-card ts-dialog ts-stack" role="dialog" aria-modal="true" aria-labelledby="aj-pick-h">
            <h2 id="aj-pick-h" class="ts-h2"><i class="fas fa-box" style="color:var(--ts-ok);" aria-hidden="true"></i> ยืนยันรับของจากร้าน</h2>
            <label class="tp-inset" style="border-radius:14px; padding:14px; cursor:pointer; display:flex; gap:10px; align-items:center;">
                <i class="fas fa-camera" aria-hidden="true"></i>
                <span x-text="pickupPhoto ? pickupPhoto.name : 'ถ่ายรูปของที่รับ (ไม่บังคับ)'"></span>
                <input type="file" accept="image/*" capture="environment" style="position:absolute; width:1px; height:1px; opacity:0;" x-on:change="pickupPhoto = $event.target.files[0] || null">
            </label>
            <div class="ts-row" style="justify-content:flex-end;">
                <button type="button" class="tp-btn" x-on:click="dialog = null">ยกเลิก</button>
                <button type="button" class="ts-btn3d ts-tone-ok sm" x-on:click="setStatus('picked_up')" :disabled="busy"><i class="fas" :class="busy ? 'fa-circle-notch ts-spin' : 'fa-check'" aria-hidden="true"></i> รับของแล้ว</button>
            </div>
        </div>
    </div>

    {{-- ════════ กล่องส่งของสำเร็จ ════════ --}}
    <div class="ts-dialog-bg" x-show="dialog === 'deliver'" x-cloak x-transition.opacity x-on:click.self="dialog = null">
        <div class="tp-card ts-dialog ts-stack" role="dialog" aria-modal="true" aria-labelledby="aj-del-h">
            <h2 id="aj-del-h" class="ts-h2"><i class="fas fa-flag-checkered" style="color:var(--accent2);" aria-hidden="true"></i> ส่งของสำเร็จ</h2>
            <label class="tp-inset" style="border-radius:14px; padding:14px; cursor:pointer; display:flex; gap:10px; align-items:center;">
                <i class="fas fa-camera" aria-hidden="true"></i>
                <span x-text="deliveryPhoto ? deliveryPhoto.name : 'ถ่ายรูปยืนยันการส่ง (บังคับ)'"></span>
                <input type="file" accept="image/*" capture="environment" style="position:absolute; width:1px; height:1px; opacity:0;" x-on:change="deliveryPhoto = $event.target.files[0] || null">
            </label>
            @if($codAmount > 0)
                <label class="ts-row" style="gap:10px; flex-wrap:nowrap; cursor:pointer; min-height:44px;">
                    <input type="checkbox" x-model="codCollected" style="width:22px; height:22px; flex:none; accent-color:var(--accent1);">
                    <span style="font-size:14px; font-weight:700;">เก็บเงินสดจากลูกค้าแล้ว ฿{{ $ui::money($codAmount) }}</span>
                </label>
            @endif
            <input type="text" class="tp-input" maxlength="500" x-model="deliverNote" placeholder="หมายเหตุ (ไม่บังคับ) เช่น ฝากไว้ที่ป้อมยาม" aria-label="หมายเหตุการส่ง">
            <div class="ts-row" style="justify-content:flex-end;">
                <button type="button" class="tp-btn" x-on:click="dialog = null">ยกเลิก</button>
                <button type="button" class="ts-btn3d ts-tone-gold sm" x-on:click="deliver()" :disabled="busy || !deliveryPhoto || (cfg.cod > 0 && !codCollected)">
                    <i class="fas" :class="busy ? 'fa-circle-notch ts-spin' : 'fa-check'" aria-hidden="true"></i> ยืนยันส่งสำเร็จ
                </button>
            </div>
        </div>
    </div>

    {{-- ════════ กล่องส่งไม่สำเร็จ ════════ --}}
    <div class="ts-dialog-bg" x-show="dialog === 'fail'" x-cloak x-transition.opacity x-on:click.self="dialog = null">
        <div class="tp-card ts-dialog ts-stack" role="dialog" aria-modal="true" aria-labelledby="aj-fail-h">
            <h2 id="aj-fail-h" class="ts-h2"><i class="fas fa-triangle-exclamation" style="color:var(--ts-bad);" aria-hidden="true"></i> ส่งของไม่สำเร็จ</h2>
            <p class="ts-muted" style="margin:0; font-size:13px;">ใช้เมื่อพยายามส่งแล้วแต่ส่งไม่ได้จริง — ทีมงานจะติดต่อลูกค้าและร้านต่อ</p>
            <div class="ts-stack" style="gap:8px;">
                @foreach($failureReasons as $code => $label)
                    <button type="button" class="ts-choice" :class="failReason === @js($code) ? 'is-on' : ''" x-on:click="failReason = @js($code)">
                        <span class="ind"><i class="fas fa-check" aria-hidden="true"></i></span><span class="name">{{ $label }}</span>
                    </button>
                @endforeach
            </div>
            <textarea class="tp-input" rows="2" maxlength="500" x-model="failNote" :placeholder="failReason === 'other' ? 'อธิบายเหตุผล (บังคับ)' : 'รายละเอียดเพิ่มเติม (ไม่บังคับ)'" aria-label="รายละเอียด"></textarea>
            <div class="ts-row" style="justify-content:flex-end;">
                <button type="button" class="tp-btn" x-on:click="dialog = null">ยกเลิก</button>
                <button type="button" class="ts-btn3d ts-tone-bad sm" x-on:click="fail()" :disabled="busy || !failReason || (failReason === 'other' && !failNote.trim())">
                    <i class="fas" :class="busy ? 'fa-circle-notch ts-spin' : 'fa-check'" aria-hidden="true"></i> ยืนยัน
                </button>
            </div>
        </div>
    </div>

    {{-- ════════ ยืนยันปิด GPS ════════ --}}
    <div class="ts-dialog-bg" x-show="askGpsOff" x-cloak style="z-index:130;" x-on:click.self="askGpsOff = false">
        <div class="tp-card ts-dialog ts-stack" role="alertdialog" aria-modal="true" aria-labelledby="aj-off-h">
            <h2 id="aj-off-h" class="ts-h2"><i class="fas fa-power-off" style="color:var(--ts-bad);" aria-hidden="true"></i> หยุดงานนี้จริงหรือ?</h2>
            <p class="ts-muted" style="margin:0; font-size:13.5px;">การปิด GPS ระหว่างส่งงานอาจทำให้งานถูกยกเลิกและมีผลต่อคะแนนของคุณ</p>
            <div class="ts-row" style="justify-content:flex-end;">
                <button type="button" class="tp-btn" x-on:click="askGpsOff = false">กลับไปเปิด GPS</button>
                <button type="button" class="ts-btn3d ts-tone-bad sm" x-on:click="confirmGpsOff()" :disabled="busy">ยืนยันหยุดงาน</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    /**
     * งานที่กำลังส่ง (ไรเดอร์บนเว็บ): ติดตาม GPS + ส่งตำแหน่งตามรอบ + ตรวจ GPS หาย + ปุ่มตามขั้นตอนจาก allowed_actions ของเซิร์ฟเวอร์
     */
    window.tsActiveJob = function (cfg) {
        let map = null;
        let meMarker = null;
        let destMarker = null;
        let liveMarker = null;
        let line = null;
        let watchId = null;
        let sendTimer = null;
        let lostTimer = null;
        let lastFix = null;

        return {
            cfg: cfg, job: cfg.job, busy: false, dialog: null, askGpsOff: false,
            lat: null, lng: null, accuracy: null, speed: null, heading: null,
            gpsActive: false, gpsLost: false, warningCount: 0,
            pickupPhoto: null, deliveryPhoto: null, codCollected: false, deliverNote: '',
            failReason: '', failNote: '',

            get beforePickup() { return ['accepted', 'picking_up'].includes(this.job.status); },
            get destination() { return this.beforePickup ? cfg.pickup : (this.customerLive || cfg.dropoff); },
            get customerLive() {
                const c = this.job.customer_live_location;
                return c ? { lat: Number(c.latitude), lng: Number(c.longitude) } : null;
            },
            get directionsUrl() {
                const d = this.destination;
                return d ? 'https://www.google.com/maps/dir/?api=1&travelmode=driving&destination=' + encodeURIComponent(d.lat + ',' + d.lng) : '#';
            },

            has(action) { return (this.job.allowed_actions || []).includes(action); },

            init() {
                this.$nextTick(() => this.drawMap());
                this.startGps();
            },

            // ===== GPS =====
            startGps() {
                if (!('geolocation' in navigator)) {
                    this.gpsLost = true;
                    return;
                }
                watchId = navigator.geolocation.watchPosition((p) => this.onFix(p), (e) => this.onGpsError(e), { enableHighAccuracy: true, maximumAge: 10000, timeout: 20000 });
                clearInterval(sendTimer);
                sendTimer = setInterval(() => this.sendLocation(), cfg.sendMs);
                clearInterval(lostTimer);
                lostTimer = setInterval(() => {
                    if (lastFix && Date.now() - lastFix > cfg.lostMs && !this.gpsLost) { this.markLost(); }
                }, 5000);
            },

            onFix(p) {
                const first = this.lat === null;
                this.lat = p.coords.latitude;
                this.lng = p.coords.longitude;
                this.accuracy = p.coords.accuracy;
                this.speed = p.coords.speed;
                this.heading = p.coords.heading;
                lastFix = Date.now();
                this.gpsActive = true;
                this.gpsLost = false;
                this.drawMap();
                if (first) { this.sendLocation(); }
            },

            onGpsError(e) {
                this.gpsActive = false;
                if (e && e.code === 1) {
                    this.markLost();
                } else {
                    setTimeout(() => { if (!this.gpsActive) { this.markLost(); } }, 10000);
                }
            },

            async markLost() {
                this.gpsLost = true;
                this.gpsActive = false;
                const r = await window.ts.post(cfg.endpoints.gps_lost, {});
                if (r.ok && r.data && r.data.warning_count !== undefined) { this.warningCount = Number(r.data.warning_count) || 0; }
            },

            retryGps() {
                if (watchId !== null) { navigator.geolocation.clearWatch(watchId); }
                this.startGps();
            },

            async confirmGpsOff() {
                this.busy = true;
                const r = await window.ts.post(cfg.endpoints.gps_off, {});
                this.busy = false;
                if (!r.ok) { window.ts.notify(r.message, 'error'); return; }
                this.stop();
                window.location.href = cfg.endpoints.job_detail;
            },

            async sendLocation() {
                if (this.lat === null || document.visibilityState !== 'visible') { return; }
                await window.ts.post(cfg.endpoints.location, {
                    job_id: this.job.id, latitude: this.lat, longitude: this.lng,
                    accuracy: this.accuracy, speed: this.speed, heading: this.heading
                });
            },

            stop() {
                if (watchId !== null) { navigator.geolocation.clearWatch(watchId); watchId = null; }
                clearInterval(sendTimer);
                clearInterval(lostTimer);
            },

            // ===== แผนที่ =====
            drawMap() {
                if (!window.tpMap || !window.tpMap.ready()) { return; }
                const start = this.lat !== null ? { lat: this.lat, lng: this.lng } : (this.destination || cfg.pickup);
                if (!start) { return; }
                if (!map) {
                    map = window.tpMap.create(this.$refs.map, start.lat, start.lng, 15);
                    if (!map) { return; }
                    [250, 900].forEach((ms) => setTimeout(() => map && map.invalidateSize(), ms));
                }
                const dest = this.beforePickup ? cfg.pickup : cfg.dropoff;
                if (dest) {
                    if (!destMarker) {
                        destMarker = window.tpMap.pin(map, dest.lat, dest.lng, this.beforePickup ? 'shop' : 'home');
                    } else {
                        destMarker.setLatLng([dest.lat, dest.lng]);
                        destMarker.setIcon(window.tpMap.icon(this.beforePickup ? 'shop' : 'home'));
                    }
                }
                const live = this.customerLive;
                if (live && !this.beforePickup) {
                    if (!liveMarker) { liveMarker = window.tpMap.pin(map, live.lat, live.lng, 'home'); } else { liveMarker.setLatLng([live.lat, live.lng]); }
                } else if (liveMarker) {
                    map.removeLayer(liveMarker);
                    liveMarker = null;
                }
                if (this.lat !== null) {
                    if (!meMarker) {
                        meMarker = window.tpMap.pin(map, this.lat, this.lng, 'rider');
                        map.panTo([this.lat, this.lng]);
                    } else {
                        meMarker.setLatLng([this.lat, this.lng]);
                    }
                    const target = this.destination;
                    if (target) {
                        const pts = [[this.lat, this.lng], [target.lat, target.lng]];
                        if (!line) { line = window.L.polyline(pts, { dashArray: '8 8', weight: 4, opacity: 0.7 }).addTo(map); } else { line.setLatLngs(pts); }
                    }
                }
            },

            centerOnMe() {
                if (map && this.lat !== null) { map.setView([this.lat, this.lng], 16); }
            },

            // ===== คำสั่งงาน =====
            applyJob(r) {
                if (r.data && r.data.job) {
                    this.job = r.data.job;
                    this.$nextTick(() => this.drawMap());
                }
            },

            async setStatus(status) {
                if (this.busy) { return; }
                this.busy = true;
                const fd = new FormData();
                fd.append('status', status);
                if (status === 'picked_up' && this.pickupPhoto) { fd.append('photo', this.pickupPhoto); }
                const r = await window.ts.post(cfg.endpoints.status, fd);
                this.busy = false;
                if (!r.ok) { window.ts.notify(r.message, 'error'); return; }
                this.dialog = null;
                this.applyJob(r);
                window.ts.notify(r.message || 'อัปเดตสถานะแล้ว', 'success');
            },

            async deliver() {
                if (this.busy || !this.deliveryPhoto) { return; }
                this.busy = true;
                const fd = new FormData();
                fd.append('photo', this.deliveryPhoto);
                if (this.lat !== null) { fd.append('latitude', this.lat); fd.append('longitude', this.lng); }
                if (cfg.cod > 0) { fd.append('cod_collected', this.codCollected ? '1' : '0'); }
                if (this.deliverNote.trim()) { fd.append('note', this.deliverNote.trim()); }
                const r = await window.ts.post(cfg.endpoints.deliver, fd);
                if (!r.ok) {
                    this.busy = false;
                    window.ts.notify(r.message, 'error');
                    return;
                }
                this.stop();
                window.ts.notify(r.message || 'ส่งของสำเร็จ!', 'success');
                setTimeout(() => { window.location.href = cfg.endpoints.job_detail; }, 900);
            },

            async fail() {
                if (this.busy || !this.failReason) { return; }
                this.busy = true;
                const fd = new FormData();
                fd.append('reason_code', this.failReason);
                if (this.failNote.trim()) { fd.append('note', this.failNote.trim()); }
                const r = await window.ts.post(cfg.endpoints.fail, fd);
                if (!r.ok) {
                    this.busy = false;
                    window.ts.notify(r.message, 'error');
                    return;
                }
                this.stop();
                window.ts.notify(r.message || 'บันทึกแล้ว', 'info');
                setTimeout(() => { window.location.href = cfg.endpoints.job_detail; }, 900);
            }
        };
    };
</script>
@endpush
