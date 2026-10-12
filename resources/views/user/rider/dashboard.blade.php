{{--
    แดชบอร์ดไรเดอร์ (user.rider.dashboard) — Theme V4 นวลทองคำ
    Controller: User\RiderController@index
    ตัวแปร: rider, riderData (ชุดเดียวกับ API /rider/status), stats, activeJob, activeJobData, todayEarnings,
            weekEarnings, availableJobs, availableReason, canAcceptJobs, blockReason, recentJobs, pageTitle
    (2026-10-13) รับงาน/เปิดรับงาน/ส่งตำแหน่ง ทำได้ในแอปเท่านั้น — หน้านี้อ่านอย่างเดียว + แบนเนอร์ดาวน์โหลดแอป
    ปุ่มที่เหลือ: POST user.rider.availability {availability: offline} (ปิดรับงานได้เสมอ) · POST user.rider.consent {location_consent}
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
        'urls' => [
            'availability' => route('user.rider.availability'),
            'consent' => route('user.rider.consent'),
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
     x-on:rd-need-consent.window="focusConsent()">

    @include('user.rider.partials.nav', ['rider' => $rider, 'active' => 'dashboard'])

    {{-- ── รับงานได้ในแอปเท่านั้น (2026-10-13) ─────────────────── --}}
    @include('user.rider.partials.app-only-banner')

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
            {{-- สถานะรับงาน: บนเว็บปิดรับงานได้อย่างเดียว (เปิดรับงานในแอป) --}}
            @if($availability === 'online')
                <button type="button" class="rd-switch" x-on:click="goOffline()" :disabled="busy"
                        aria-pressed="true" aria-label="ปิดรับงาน">
                    <span class="knob rd-tone-ok">
                        <i class="fas" :class="busy ? 'fa-circle-notch rd-spin' : 'fa-power-off'"></i>
                    </span>
                    <span style="text-align:left;">
                        <span style="display:block;">{{ $availabilityLabel }}</span>
                        <span class="rd-small rd-muted" style="display:block; font-weight:600;">แตะเพื่อปิดรับงาน</span>
                    </span>
                </button>
            @else
                <div class="rd-switch" role="status" aria-label="สถานะรับงาน: {{ $availabilityLabel }}">
                    <span class="knob {{ $availability === 'busy' ? 'rd-tone-info' : 'rd-tone-muted' }}">
                        <i class="fas {{ $availability === 'busy' ? 'fa-person-biking' : 'fa-mobile-screen-button' }}"></i>
                    </span>
                    <span style="text-align:left;">
                        <span style="display:block;">{{ $availabilityLabel }}</span>
                        <span class="rd-small rd-muted" style="display:block; font-weight:600;">
                            {{ $availability === 'busy' ? 'อัปเดตสถานะงานในแอป' : 'เปิดรับงานในแอป' }}
                        </span>
                    </span>
                </div>
            @endif
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
                @if(($onlineBlock['code'] ?? '') === 'KYC_REQUIRED')
                    <div style="margin-top:6px;" class="rd-small">ยืนยันตัวตนด้วยบัตรประชาชน + ใบหน้าได้ในแอป Thai Prompt APP</div>
                @endif
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
                <div class="rd-muted rd-small" style="margin-top:4px;">เปิดรับงานในแอป Thai Prompt APP แล้วรับงานที่อยู่ใกล้คุณได้เลย</div>
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
            // ปิดรับงาน (ทำได้บนเว็บ) — เปิดรับงาน/ส่งตำแหน่งทำในแอปเท่านั้น
            async goOffline() {
                if (this.busy || this.availability !== 'online') { return; }
                this.busy = true;
                const res = await window.rdApi.post(cfg.urls.availability, { availability: 'offline' });
                if (res.ok) {
                    this.availability = (res.data && res.data.availability) ? res.data.availability : 'offline';
                    window.rdApi.toast(res.message || 'ปิดรับงานแล้ว', 'success');
                    setTimeout(() => window.location.reload(), 600);
                    return;
                }
                this.busy = false;
                window.rdApi.toast(res.message, 'error');
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
