{{--
    แดชบอร์ดไรเดอร์ (user.rider.dashboard) — Theme V4 นวลทองคำ
    Controller: User\RiderController@index
    ตัวแปร: rider, riderData (ชุดเดียวกับ API /rider/status), stats, activeJob, activeJobData, todayEarnings,
            weekEarnings, availableJobs, availableReason, canAcceptJobs, blockReason, recentJobs, pageTitle
    ปุ่ม: POST user.rider.availability {availability, latitude?, longitude?} · POST user.rider.consent {location_consent}
          POST user.rider.location (ส่งตำแหน่งทุก 60 วิ ระหว่างเปิดรับงาน) · รับงาน POST user.rider.jobs.accept/{job}
--}}
@extends('layouts.user-v4')

@section('title', $pageTitle ?? 'แดชบอร์ดไรเดอร์')

@push('styles')
    @include('user.rider.partials.styles')
@endpush

@php
    $ui = \App\Support\RiderWebUi::class;
    $rd = $riderData;
    $availability = (string) ($rd['availability'] ?? 'offline');
    $hasConsent = (bool) ($rd['permissions']['location_consent'] ?? false);
    $profileUrl = $rd['document_urls']['profile'] ?? null;
    $onlineBlock = $rd['online_block_reason'] ?? null;
    $weekBars = $ui::dailyBars($weekEarnings['daily'] ?? [], 'week', $weekEarnings['from'] ?? null);
    $todayJobs = (int) ($todayEarnings['completed_jobs'] ?? 0);
    $heroImage = file_exists(public_path('images/taladsod/banner-rider.webp')) ? asset('images/taladsod/banner-rider.webp') : null;

    $dashCfg = [
        'availability' => $availability,
        'consent' => $hasConsent,
        'hasActiveJob' => $activeJobData !== null,
        'canGoOnline' => (bool) ($rd['can_go_online'] ?? false),
        'onlineBlock' => $onlineBlock['message'] ?? null,
        'locationFresh' => (bool) ($rd['last_location']['is_fresh'] ?? false),
        'urls' => [
            'availability' => route('user.rider.availability'),
            'consent' => route('user.rider.consent'),
            'location' => route('user.rider.location'),
        ],
    ];

    $availabilityLabel = match ($availability) {
        'online' => 'พร้อมรับงาน',
        'busy' => 'กำลังส่งงาน',
        default => 'ปิดรับงาน',
    };

    $recentRows = collect($recentJobs)->map(fn ($j) => [
        'id' => (int) $j->id,
        'job_number' => (string) $j->job_number,
        'job_type' => $j->job_type,
        'job_type_text' => $j->job_type_text,
        'status' => (string) $j->status,
        'status_text' => $j->status_text,
        'rider_earnings' => (float) $j->rider_earnings,
        'cod_amount' => (float) $j->cod_amount,
        'created_at' => $j->created_at,
        'completed_at' => $j->completed_at,
    ])->all();
@endphp

@section('content')
<div class="rd-scope" x-data="rdDashboard({{ \Illuminate\Support\Js::from($dashCfg) }})"
     x-on:rd-refresh-location.window="refreshLocation(true)"
     x-on:rd-need-consent.window="focusConsent()">

    @include('user.rider.partials.nav', ['rider' => $rider, 'active' => 'dashboard'])

    {{-- ── หัวการ์ด: โปรไฟล์ + สวิตช์รับงาน ───────────────────── --}}
    <section class="tp-card rd-hero">
        @if($heroImage)
            <div class="rd-hero-img" style="background-image:url('{{ $heroImage }}');" aria-hidden="true"></div>
        @endif
        <div class="rd-hero-in">
            @if($profileUrl)
                <img src="{{ $profileUrl }}" alt="รูปโปรไฟล์ไรเดอร์" class="rd-avatar" loading="lazy">
            @else
                <span class="tp-tile" style="width:60px; height:60px; border-radius:20px; font-size:24px;"><i class="fas {{ $ui::vehicleIcon($rider->vehicle_type) }}"></i></span>
            @endif
            <div style="flex:1 1 220px; min-width:0;">
                <div class="rd-muted rd-small">สวัสดี ไรเดอร์ของเรา 👋</div>
                <h1 class="rd-h1">{{ $rider->full_name }}</h1>
                <div class="rd-row" style="gap:8px; margin-top:6px;">
                    <span class="rd-pill rd-tone-gold"><i class="fas {{ $ui::vehicleIcon($rider->vehicle_type) }}"></i> {{ $rider->vehicle_type_text }}{{ $rider->vehicle_plate ? ' · '.$rider->vehicle_plate : '' }}</span>
                    <span class="rd-pill rd-tone-warn"><i class="fas fa-star"></i> {{ number_format((float) $stats['rating'], 1) }} ({{ number_format($stats['rating_count']) }})</span>
                    @if($rider->status === 'suspended')
                        <span class="rd-pill solid rd-tone-bad"><i class="fas fa-ban"></i> ถูกระงับ</span>
                    @endif
                </div>
            </div>
            <button type="button" class="rd-switch" x-on:click="toggle()"
                    :disabled="busy || availability === 'busy'"
                    :aria-pressed="availability === 'online' ? 'true' : 'false'"
                    aria-label="เปิดหรือปิดรับงาน">
                <span class="knob {{ $availability === 'online' ? 'rd-tone-ok' : ($availability === 'busy' ? 'rd-tone-info' : 'rd-tone-muted') }}"
                      :class="availability === 'online' ? 'rd-tone-ok' : (availability === 'busy' ? 'rd-tone-info' : 'rd-tone-muted')">
                    <i class="fas" :class="busy ? 'fa-circle-notch rd-spin' : (availability === 'busy' ? 'fa-person-biking' : 'fa-power-off')"></i>
                </span>
                <span style="text-align:left;">
                    <span style="display:block;" x-text="availability === 'online' ? 'พร้อมรับงาน' : (availability === 'busy' ? 'กำลังส่งงาน' : 'ปิดรับงาน')">{{ $availabilityLabel }}</span>
                    <span class="rd-small rd-muted" style="display:block; font-weight:600;"
                          x-text="availability === 'online' ? 'แตะเพื่อปิดรับงาน' : (availability === 'busy' ? 'ส่งงานให้เสร็จก่อน' : 'แตะเพื่อเปิดรับงาน')">
                        {{ $availability === 'online' ? 'แตะเพื่อปิดรับงาน' : ($availability === 'busy' ? 'ส่งงานให้เสร็จก่อน' : 'แตะเพื่อเปิดรับงาน') }}
                    </span>
                </span>
            </button>
        </div>
    </section>

    {{-- ── แจ้งเตือนสถานะบัญชี ─────────────────────────────────── --}}
    @if($rider->status === 'suspended')
        <div class="rd-alert rd-tone-bad">
            <i class="fas fa-ban"></i>
            <div>
                <b>บัญชีไรเดอร์ถูกระงับชั่วคราว</b>
                @if(!empty($rd['suspension_reason'])) — {{ $rd['suspension_reason'] }} @endif
                <div style="margin-top:6px;"><a href="{{ route('user.tickets.create') }}" class="rd-link"><i class="fas fa-headset"></i> ติดต่อทีมงาน</a></div>
            </div>
        </div>
    @elseif($onlineBlock)
        <div class="rd-alert rd-tone-warn">
            <i class="fas fa-circle-exclamation"></i>
            <div>
                <b>ยังเปิดรับงานไม่ได้:</b> {{ $onlineBlock['message'] }}
                @if(($onlineBlock['code'] ?? '') === 'DOCUMENTS_REVIEW_PENDING' || !empty($rd['documents_missing']))
                    <div style="margin-top:6px;"><a href="{{ route('user.rider.documents') }}" class="rd-link"><i class="fas fa-folder-open"></i> ตรวจสอบเอกสาร</a></div>
                @endif
            </div>
        </div>
    @endif

    {{-- ── ความยินยอมแชร์ตำแหน่ง (ครั้งเดียว ก่อนรับงานแรก) ───────── --}}
    @if(!$hasConsent && $rider->status === 'approved')
        <section class="tp-card rd-stack" x-ref="consentCard" x-show="!consent" x-transition
                 style="box-shadow:var(--card-shadow), 0 0 0 2px color-mix(in srgb, var(--rd-info) 45%, transparent);">
            <div class="rd-row" style="gap:12px; flex-wrap:nowrap; align-items:flex-start;">
                <span class="tp-tile rd-tone-info" style="width:46px; height:46px; border-radius:15px; font-size:19px; background:linear-gradient(135deg, color-mix(in srgb, var(--rd-info) 75%, var(--rd-on)), var(--rd-info));"><i class="fas fa-location-dot"></i></span>
                <div>
                    <div class="rd-h2">ยินยอมให้ลูกค้าเห็นตำแหน่งของคุณระหว่างส่งงาน</div>
                    <p class="rd-muted" style="font-size:13px; margin:6px 0 0; line-height:1.6;">
                        ต้องยินยอมก่อนรับงานแรก (ครั้งเดียวพอ) — ลูกค้าจะเห็นตำแหน่งของคุณบนแผนที่<b>เฉพาะออเดอร์ของเขา</b>
                        ตั้งแต่รับงานจนส่งของเสร็จเท่านั้น ระบบหยุดแชร์อัตโนมัติเมื่องานจบหรือถูกยกเลิก
                        และคุณถอนความยินยอมได้ที่หน้าตั้งค่า (เมื่อไม่มีงานค้าง)
                    </p>
                </div>
            </div>
            <label class="rd-row" style="gap:10px; cursor:pointer; min-height:44px; flex-wrap:nowrap;">
                <input type="checkbox" x-model="consentChecked" style="width:22px; height:22px; accent-color:var(--accent1); flex:none;">
                <span style="font-size:13.5px; font-weight:600;">ฉันเข้าใจและยินยอมแชร์ตำแหน่งกับลูกค้าระหว่างส่งงาน</span>
            </label>
            <div>
                <button type="button" class="rd-btn3d rd-tone-info" x-on:click="giveConsent()" :disabled="!consentChecked || busy">
                    <i class="fas" :class="busy ? 'fa-circle-notch rd-spin' : 'fa-shield-heart'"></i> ยินยอมและพร้อมรับงาน
                </button>
            </div>
        </section>
    @endif

    {{-- ── ตัวเลขรายได้ ───────────────────────────────────────── --}}
    <div class="rd-grid" style="--rd-min:170px;">
        <div class="tp-card rd-stat featured rd-tone-gold">
            <span class="ic"><i class="fas fa-sun"></i></span>
            <span class="lbl">รายได้วันนี้</span>
            <span class="val">฿{{ $ui::money($todayEarnings['gross_earnings'] ?? 0) }}</span>
            <span class="sub">ส่งสำเร็จ {{ number_format($todayJobs) }} งาน</span>
        </div>
        <div class="tp-card rd-stat rd-tone-ok">
            <span class="ic"><i class="fas fa-calendar-week"></i></span>
            <span class="lbl">สัปดาห์นี้</span>
            <span class="val">฿{{ $ui::money($weekEarnings['gross_earnings'] ?? 0) }}</span>
            <span class="sub">{{ number_format((int) ($weekEarnings['completed_jobs'] ?? 0)) }} งาน</span>
        </div>
        <a href="{{ route('user.rider.earnings') }}" class="tp-card tp-card-hover rd-stat rd-tone-info" style="text-decoration:none;">
            <span class="ic"><i class="fas fa-wallet"></i></span>
            <span class="lbl">ยอดในกระเป๋า</span>
            <span class="val">฿{{ $ui::money($rd['wallet_balance'] ?? 0) }}</span>
            <span class="sub">ดูรายได้และถอนเงิน <i class="fas fa-arrow-right" style="font-size:10px;"></i></span>
        </a>
        <div class="tp-card rd-stat rd-tone-warn">
            <span class="ic"><i class="fas fa-trophy"></i></span>
            <span class="lbl">งานสำเร็จทั้งหมด</span>
            <span class="val">{{ number_format($stats['completed_jobs']) }}</span>
            <span class="sub">อัตราสำเร็จ {{ number_format((float) $stats['completion_rate'], 0) }}% · รายได้สะสม ฿{{ $ui::money($stats['total_earnings']) }}</span>
        </div>
    </div>

    @if($todayJobs > 0)
        <div class="rd-alert rd-tone-ok">
            <i class="fas fa-champagne-glasses"></i>
            <div><b>เยี่ยมมาก!</b> วันนี้คุณส่งสำเร็จแล้ว {{ number_format($todayJobs) }} งาน รับไปแล้ว ฿{{ $ui::money($todayEarnings['gross_earnings'] ?? 0) }} — ลุยต่อเลย!</div>
        </div>
    @endif

    {{-- ── งานที่กำลังทำ ───────────────────────────────────────── --}}
    @if($activeJobData)
        @include('user.rider.partials.current-job', ['jobData' => $activeJobData])
    @endif

    {{-- ── งานรอรับใกล้คุณ ─────────────────────────────────────── --}}
    @if(!$activeJobData)
        <section class="rd-stack">
            <div class="rd-row" style="justify-content:space-between;">
                <h2 class="rd-h2"><i class="fas fa-bolt" style="color:var(--accent1);"></i> งานใกล้คุณ</h2>
                <a href="{{ route('user.rider.jobs') }}" class="rd-link rd-small">ดูงานทั้งหมด <i class="fas fa-arrow-right"></i></a>
            </div>
            @include('user.rider.partials.available-jobs')
        </section>
    @endif

    {{-- ── กราฟรายได้สัปดาห์นี้ ─────────────────────────────────── --}}
    <section class="tp-card rd-stack">
        <div class="rd-row" style="justify-content:space-between;">
            <h2 class="rd-h2"><i class="fas fa-chart-column" style="color:var(--accent1);"></i> รายได้สัปดาห์นี้</h2>
            <a href="{{ route('user.rider.earnings', ['period' => 'week']) }}" class="rd-link rd-small">รายละเอียด <i class="fas fa-arrow-right"></i></a>
        </div>
        @if(count($weekBars) > 0)
            <div class="tp-bars rd-bars" role="img" aria-label="กราฟรายได้รายวันของสัปดาห์นี้">
                @foreach($weekBars as $bar)
                    <div class="col {{ $bar['is_today'] ? 'today' : '' }}" title="{{ $bar['sub'] }}: ฿{{ $ui::money($bar['earnings']) }} ({{ $bar['jobs'] }} งาน)">
                        <span class="amt">{{ $bar['earnings'] > 0 ? '฿'.$ui::money($bar['earnings']) : '' }}</span>
                        <div class="stack"><i class="bar {{ $bar['is_today'] ? 'b' : 'a' }}" style="height:{{ $bar['pct'] }}%; display:block;"></i></div>
                        <span class="lbl">{{ $bar['label'] }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    {{-- ── งานล่าสุด ───────────────────────────────────────────── --}}
    <section class="tp-card rd-stack">
        <div class="rd-row" style="justify-content:space-between;">
            <h2 class="rd-h2"><i class="fas fa-clock-rotate-left" style="color:var(--accent1);"></i> งานล่าสุด</h2>
            <a href="{{ route('user.rider.jobs', ['tab' => 'history']) }}" class="rd-link rd-small">ประวัติทั้งหมด <i class="fas fa-arrow-right"></i></a>
        </div>
        @if(count($recentRows) === 0)
            <div class="rd-empty" style="padding:18px;">
                <div class="ic"><i class="fas fa-flag-checkered"></i></div>
                <div style="font-weight:700;">ยังไม่มีงาน — งานแรกของคุณกำลังจะมา!</div>
                <div class="rd-muted rd-small" style="margin-top:4px;">เปิดรับงานไว้ แล้วรับงานที่อยู่ใกล้คุณได้เลย</div>
            </div>
        @else
            <div class="rd-list">
                @foreach($recentRows as $row)
                    @include('user.rider.partials.job-row', ['row' => $row])
                @endforeach
            </div>
        @endif
    </section>
</div>
@endsection

@push('scripts')
    @include('user.rider.partials.scripts')
    <script>
    window.rdDashboard = function (cfg) {
        return {
            availability: cfg.availability,
            consent: !!cfg.consent,
            consentChecked: false,
            busy: false,
            locating: false,
            heartbeat: null,
            init() {
                if (this.availability === 'online') {
                    // เปิดรับงานอยู่ → ส่งตำแหน่งทันทีถ้าตำแหน่งเก่า แล้วส่งทุก 60 วินาทีระหว่างเปิดหน้านี้ (กันระบบปิดรับงานอัตโนมัติ)
                    if (!cfg.locationFresh) { this.refreshLocation(false); }
                    this.heartbeat = setInterval(() => { if (!document.hidden) { this.refreshLocation(false); } }, 60000);
                    document.addEventListener('visibilitychange', () => {
                        if (!document.hidden && this.availability === 'online') { this.refreshLocation(false); }
                    });
                }
            },
            destroy() {
                if (this.heartbeat) { clearInterval(this.heartbeat); this.heartbeat = null; }
            },
            async refreshLocation(manual) {
                if (this.locating) { return; }
                this.locating = true;
                const pos = await window.rdApi.geo(12000);
                if (!pos) {
                    this.locating = false;
                    if (manual) { window.rdApi.toast('อ่านตำแหน่งไม่ได้ กรุณาอนุญาตให้เว็บเข้าถึงตำแหน่งในเบราว์เซอร์', 'error'); }
                    return;
                }
                const res = await window.rdApi.post(cfg.urls.location, {
                    latitude: pos.latitude, longitude: pos.longitude, accuracy: pos.accuracy,
                    speed: pos.speed, heading: pos.heading
                });
                this.locating = false;
                if (!manual) { return; }
                if (res.ok) {
                    window.rdApi.toast('อัปเดตตำแหน่งแล้ว กำลังค้นหางานใกล้คุณ...', 'success');
                    setTimeout(() => window.location.reload(), 700);
                } else {
                    window.rdApi.toast(res.message, 'error');
                }
            },
            async toggle() {
                if (this.busy) { return; }
                if (this.availability === 'busy') {
                    window.rdApi.toast('คุณมีงานที่กำลังทำอยู่ ส่งงานให้เสร็จก่อนนะ', 'info');
                    return;
                }
                const goOnline = this.availability !== 'online';
                if (goOnline && !cfg.canGoOnline && cfg.onlineBlock) {
                    window.rdApi.toast(cfg.onlineBlock, 'warning');
                    return;
                }
                this.busy = true;
                const payload = { availability: goOnline ? 'online' : 'offline' };
                if (goOnline) {
                    const pos = await window.rdApi.geo(12000);
                    if (pos) { payload.latitude = pos.latitude; payload.longitude = pos.longitude; }
                }
                const res = await window.rdApi.post(cfg.urls.availability, payload);
                if (res.ok) {
                    this.availability = (res.data && res.data.availability) ? res.data.availability : payload.availability;
                    window.rdApi.toast(res.message || (goOnline ? 'เปิดรับงานแล้ว' : 'ปิดรับงานแล้ว'), 'success');
                    setTimeout(() => window.location.reload(), 600);
                    return;
                }
                this.busy = false;
                if (res.code === 'GPS_REQUIRED') {
                    window.rdApi.toast('กรุณาอนุญาตให้เว็บเข้าถึงตำแหน่ง (แตะไอคอนแม่กุญแจข้างช่องที่อยู่เว็บ) แล้วลองอีกครั้ง', 'error');
                } else {
                    window.rdApi.toast(res.message, 'error');
                }
            },
            focusConsent() {
                if (this.$refs.consentCard) { this.$refs.consentCard.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
            },
            async giveConsent() {
                if (!this.consentChecked || this.busy) { return; }
                this.busy = true;
                const res = await window.rdApi.post(cfg.urls.consent, { location_consent: true });
                this.busy = false;
                if (res.ok) {
                    this.consent = true;
                    window.rdApi.toast('บันทึกความยินยอมแล้ว พร้อมรับงานได้เลย!', 'success');
                    setTimeout(() => window.location.reload(), 700);
                } else {
                    window.rdApi.toast(res.message, 'error');
                }
            }
        };
    };
    </script>
@endpush
