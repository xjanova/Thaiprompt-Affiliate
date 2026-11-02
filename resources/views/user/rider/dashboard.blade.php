{{--
    แดชบอร์ดไรเดอร์ (user.rider.dashboard) — ธีมโนวาเต็ม
    Controller: User\RiderController@index
    ตัวแปร: rider, riderData (ชุดเดียวกับ API /rider/status), stats, activeJob, activeJobData, todayEarnings,
            weekEarnings, availableJobs, availableReason, canAcceptJobs, blockReason, recentJobs, pageTitle
    (2026-10-13) รับงาน/เปิดรับงาน/ส่งตำแหน่ง ทำได้ในแอปเท่านั้น — หน้านี้อ่านอย่างเดียว + แบนเนอร์ดาวน์โหลดแอป
    ปุ่มที่เหลือ: POST user.rider.availability {availability: offline} (ปิดรับงานได้เสมอ) · POST user.rider.consent {location_consent}
--}}
@extends('layouts.user-nova')

@section('title', $pageTitle ?? 'แดชบอร์ดไรเดอร์')

@push('styles')
    <link rel="stylesheet" href="{{ asset('theme-nova/member/rider.css') }}?v={{ filemtime(public_path('theme-nova/member/rider.css')) }}">
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

    // การ์ดตัวเลข: [ป้ายไทย, ป้ายอังกฤษ, ค่า, ภาพ, ข้อความท้าย, ลิงก์]
    $kpis = [
        ['รายได้วันนี้', 'Today', '฿'.$ui::money($todayEarnings['gross_earnings'] ?? 0), 'deco/coins', 'ส่งสำเร็จ '.number_format($todayJobs).' งาน', null],
        ['สัปดาห์นี้', 'This week', '฿'.$ui::money($weekEarnings['gross_earnings'] ?? 0), 'deco/scroll', number_format((int) ($weekEarnings['completed_jobs'] ?? 0)).' งาน', null],
        ['ยอดในกระเป๋า', 'Wallet', '฿'.$ui::money($rd['wallet_balance'] ?? 0), 'icons/wallet', 'ดูรายได้และถอนเงิน →', route('user.rider.earnings')],
        ['งานสำเร็จทั้งหมด', 'Completed', number_format($stats['completed_jobs']), 'deco/garland', 'อัตราสำเร็จ '.number_format((float) $stats['completion_rate'], 0).'% · รายได้สะสม ฿'.$ui::money($stats['total_earnings']), null],
    ];

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
<div class="nvm-stack" x-data="rdDashboard({{ \Illuminate\Support\Js::from($dashCfg) }})">

    @include('user.rider.partials.nav', ['rider' => $rider, 'active' => 'dashboard'])

    {{-- ── รับงานได้ในแอปเท่านั้น (2026-10-13) ─────────────────── --}}
    @include('user.rider.partials.app-only-banner')

    {{-- ── ฮีโร่: โปรไฟล์ + สถานะรับงาน ───────────────────────── --}}
    <section class="nvm-ph" aria-labelledby="nvm-rd-dash-h">
        <div class="nvm-ph__in">
            <div class="nvm-ph__txt">
                <div class="nvm-eyebrow nvm-latin">Rider · Thai Prompt</div>
                <div class="nvm-rd-who">
                    @if($profileUrl)
                        <img src="{{ $profileUrl }}" alt="รูปโปรไฟล์ไรเดอร์" class="nvm-rd-avatar" width="64" height="64" loading="lazy">
                    @else
                        <span class="nvm-rd-avatar nvm-rd-avatar--ph" aria-hidden="true"><svg class="nvm-ico"><use href="#nvm-i-{{ $ui::novaVehicleIcon($rider->vehicle_type) }}"/></svg></span>
                    @endif
                    <div>
                        <p class="nvm-ph__lead">สวัสดี ไรเดอร์ของเรา</p>
                        <h1 id="nvm-rd-dash-h">{{ $rider->full_name }}</h1>
                    </div>
                </div>
                <div class="nvm-pills">
                    <span class="nvm-pill nvm-pill--gold"><svg class="nvm-ico"><use href="#nvm-i-{{ $ui::novaVehicleIcon($rider->vehicle_type) }}"/></svg>{{ $rider->vehicle_type_text }}{{ $rider->vehicle_plate ? ' · '.$rider->vehicle_plate : '' }}</span>
                    <span class="nvm-pill nvm-pill--glass"><svg class="nvm-ico"><use href="#nvm-i-award"/></svg>{{ number_format((float) $stats['rating'], 1) }} ({{ number_format($stats['rating_count']) }})</span>
                    @if($rider->status === 'suspended')
                        <span class="nvm-pill nvm-pill--bad"><svg class="nvm-ico"><use href="#nvm-i-lock"/></svg>ถูกระงับ</span>
                    @endif
                </div>
                {{-- สถานะรับงาน: บนเว็บปิดรับงานได้อย่างเดียว (เปิดรับงานในแอป) --}}
                <div class="nvm-hero__actions">
                    @if($availability === 'online')
                        <button type="button" class="nvm-btn nvm-btn--ghost" x-on:click="goOffline()" :disabled="busy" aria-label="ปิดรับงาน">
                            <svg class="nvm-ico"><use href="#nvm-i-out"/></svg>
                            <span x-text="busy ? 'กำลังปิดรับงาน…' : '{{ $availabilityLabel }} · แตะเพื่อปิดรับงาน'">{{ $availabilityLabel }} · แตะเพื่อปิดรับงาน</span>
                        </button>
                    @else
                        <span class="nvm-rd-state" role="status">
                            {{ $availabilityLabel }}
                            <small>{{ $availability === 'busy' ? 'อัปเดตสถานะงานในแอป' : 'เปิดรับงานในแอป' }}</small>
                        </span>
                    @endif
                </div>
            </div>
            <img class="nvm-ph__art" src="{{ asset('images/nova/earn/rider.webp') }}" alt="" width="200" height="200">
        </div>
    </section>

    {{-- ── แจ้งเตือนสถานะบัญชี ─────────────────────────────────── --}}
    @if($rider->status === 'suspended')
        <section class="nvm-card nvm-alert nvm-alert--bad" role="alert">
            <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-lock"/></svg></span>
            <div class="nvm-alert__txt">
                <b>บัญชีไรเดอร์ถูกระงับชั่วคราว</b>
                @if(!empty($rd['suspension_reason']))<p>{{ $rd['suspension_reason'] }}</p>@endif
            </div>
            <a class="nvm-btn nvm-btn--line nvm-btn--sm" href="{{ route('user.tickets.create') }}">ติดต่อทีมงาน</a>
        </section>
    @elseif($onlineBlock)
        <section class="nvm-card nvm-alert" role="status">
            <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></span>
            <div class="nvm-alert__txt">
                <b>ยังเปิดรับงานไม่ได้</b>
                <p>{{ $onlineBlock['message'] }}</p>
                @if(($onlineBlock['code'] ?? '') === 'KYC_REQUIRED')
                    <p>ยืนยันตัวตนด้วยบัตรประชาชน + ใบหน้าได้ในแอป Thai Prompt APP</p>
                @endif
            </div>
            @if(($onlineBlock['code'] ?? '') === 'DOCUMENTS_REVIEW_PENDING' || !empty($rd['documents_missing']))
                <a class="nvm-btn nvm-btn--line nvm-btn--sm" href="{{ route('user.rider.documents') }}"><svg class="nvm-ico"><use href="#nvm-i-id"/></svg>ตรวจสอบเอกสาร</a>
            @endif
        </section>
    @endif

    {{-- ── ความยินยอมแชร์ตำแหน่ง (ครั้งเดียว ก่อนรับงานแรก) ───────── --}}
    @if(!$hasConsent && $rider->status === 'approved')
        <section class="nvm-card nvm-box nvm-rd-consent" x-ref="consentCard" x-show="!consent" x-transition aria-labelledby="nvm-rd-consent-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Location sharing</small><h2 id="nvm-rd-consent-h">ยินยอมให้ลูกค้าเห็นตำแหน่งของคุณระหว่างส่งงาน</h2></div></div>
            <p class="nvm-desc">
                ต้องยินยอมก่อนรับงานแรก (ครั้งเดียวพอ) — ลูกค้าจะเห็นตำแหน่งของคุณบนแผนที่<b>เฉพาะออเดอร์ของเขา</b>
                ตั้งแต่รับงานจนส่งของเสร็จเท่านั้น ระบบหยุดแชร์อัตโนมัติเมื่องานจบหรือถูกยกเลิก
                และคุณถอนความยินยอมได้ที่หน้าตั้งค่า (เมื่อไม่มีงานค้าง)
            </p>
            <label class="nvm-check nvm-gap-t">
                <input type="checkbox" x-model="consentChecked">
                <span>ฉันเข้าใจและยินยอมแชร์ตำแหน่งกับลูกค้าระหว่างส่งงาน</span>
            </label>
            <button type="button" class="nvm-btn nvm-btn--gold nvm-gap-t" x-on:click="giveConsent()" :disabled="!consentChecked || busy">
                <svg class="nvm-ico"><use href="#nvm-i-check"/></svg><span x-text="busy ? 'กำลังบันทึก…' : 'ยินยอมและพร้อมรับงาน'">ยินยอมและพร้อมรับงาน</span>
            </button>
        </section>
    @endif

    {{-- ── ตัวเลขรายได้ ───────────────────────────────────────── --}}
    <section class="nvm-kpis" aria-label="สรุปรายได้ไรเดอร์">
        @foreach($kpis as [$label, $en, $value, $img, $foot, $href])
            @if($href)
                <a class="nvm-card nvm-kpi" href="{{ $href }}">
            @else
                <div class="nvm-card nvm-kpi">
            @endif
                <img class="nvm-kpi__img" src="{{ asset('images/nova/'.$img.'.webp') }}" alt="" width="78" height="78" loading="lazy">
                <div class="nvm-kpi__lbl">{{ $label }}</div>
                <div class="nvm-kpi__en nvm-latin">{{ $en }}</div>
                <div class="nvm-kpi__val nvm-num">{{ $value }}</div>
                <div class="nvm-kpi__foot"><small>{{ $foot }}</small></div>
            @if($href)
                </a>
            @else
                </div>
            @endif
        @endforeach
    </section>

    @if($todayJobs > 0)
        <section class="nvm-card nvm-alert nvm-alert--ok" role="status">
            <img class="nvm-alert__img" src="{{ asset('images/nova/mascot/present.webp') }}" alt="" width="58" height="58" loading="lazy">
            <div class="nvm-alert__txt"><b>เยี่ยมมาก!</b><p>วันนี้คุณส่งสำเร็จแล้ว {{ number_format($todayJobs) }} งาน รับไปแล้ว ฿{{ $ui::money($todayEarnings['gross_earnings'] ?? 0) }} — ลุยต่อเลย!</p></div>
        </section>
    @endif

    {{-- ── งานที่กำลังทำ / งานรอรับใกล้คุณ ─────────────────────── --}}
    @if($activeJobData)
        @include('user.rider.partials.current-job', ['jobData' => $activeJobData])
    @else
        <section class="nvm-stack" aria-labelledby="nvm-rd-near-h">
            <div class="nvm-sec-h">
                <div><small class="nvm-latin">Near you</small><h2 id="nvm-rd-near-h">งานใกล้คุณ</h2></div>
                <a href="{{ route('user.rider.jobs') }}">ดูงานทั้งหมด<svg class="nvm-ico"><use href="#nvm-i-chev"/></svg></a>
            </div>
            @include('user.rider.partials.available-jobs')
        </section>
    @endif

    <div class="nvm-split">
        {{-- ── งานล่าสุด ─────────────────────────────────────── --}}
        <section class="nvm-card nvm-box" aria-labelledby="nvm-rd-recent-h">
            <div class="nvm-sec-h">
                <div><small class="nvm-latin">Recent jobs</small><h2 id="nvm-rd-recent-h">งานล่าสุด</h2></div>
                <a href="{{ route('user.rider.jobs', ['tab' => 'history']) }}">ประวัติทั้งหมด<svg class="nvm-ico"><use href="#nvm-i-chev"/></svg></a>
            </div>
            @if(count($recentRows) === 0)
                <div class="nvm-empty">
                    <img src="{{ asset('images/nova/deco/scooter.webp') }}" alt="" width="86" height="86" loading="lazy">
                    <b>ยังไม่มีงาน — งานแรกของคุณกำลังจะมา!</b>
                    เปิดรับงานในแอป Thai Prompt APP แล้วรับงานที่อยู่ใกล้คุณได้เลย
                </div>
            @else
                <div class="nvm-txs nvm-tks">
                    @foreach($recentRows as $row)
                        @include('user.rider.partials.job-row', ['row' => $row])
                    @endforeach
                </div>
            @endif
        </section>

        {{-- ── กราฟรายได้สัปดาห์นี้ ───────────────────────────── --}}
        <section class="nvm-card nvm-box" aria-labelledby="nvm-rd-week-h">
            <div class="nvm-sec-h">
                <div><small class="nvm-latin">This week</small><h2 id="nvm-rd-week-h">รายได้สัปดาห์นี้</h2></div>
                <a href="{{ route('user.rider.earnings', ['period' => 'week']) }}">รายละเอียด<svg class="nvm-ico"><use href="#nvm-i-chev"/></svg></a>
            </div>
            @if(count($weekBars) > 0)
                <div class="nvm-bars" role="img" aria-label="กราฟรายได้รายวันของสัปดาห์นี้">
                    @foreach($weekBars as $i => $bar)
                        <div class="nvm-bar {{ $bar['is_today'] ? 'is-now' : '' }}" style="--h: {{ $bar['pct'] }}%; --d: {{ $i * 45 }}ms" title="{{ $bar['sub'] }}: ฿{{ $ui::money($bar['earnings']) }} ({{ $bar['jobs'] }} งาน)">
                            <em class="nvm-num">฿{{ $ui::money($bar['earnings']) }}</em><i></i><span>{{ $bar['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
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
