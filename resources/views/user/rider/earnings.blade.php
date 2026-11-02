{{--
    รายได้ไรเดอร์ (user.rider.earnings) — ?period=today|week|month|all (ค่าเริ่มต้น week) · ธีมโนวาเต็ม
    Controller: User\RiderController@earnings
    ตัวแปร: rider, period, periodOptions, summary (ชุดเดียวกับ API /rider/earnings), dailyEarnings, monthlyEarnings,
            walletBalance, recentJobs (paginator ของงานที่สำเร็จ), kyc{status, verified, required_for_withdrawal}, pageTitle
--}}
@extends('layouts.user-nova')

@section('title', $pageTitle ?? 'รายได้ไรเดอร์')

@push('styles')
    <link rel="stylesheet" href="{{ asset('theme-nova/member/rider.css') }}?v={{ filemtime(public_path('theme-nova/member/rider.css')) }}">
@endpush

@php
    $ui = \App\Support\RiderWebUi::class;
    $gross = (float) ($summary['gross_earnings'] ?? 0);
    $jobsCount = (int) ($summary['completed_jobs'] ?? 0);
    $avgPerJob = $jobsCount > 0 ? $gross / $jobsCount : 0;
    $bars = $ui::dailyBars($dailyEarnings ?? [], $period, $summary['from'] ?? null);
    $bestDay = collect($bars)->sortByDesc('earnings')->first();
    $kycVerified = (bool) ($kyc['verified'] ?? false);
    $periodLabel = $periodOptions[$period] ?? 'สัปดาห์นี้';

    // การ์ดตัวเลข: [ป้ายไทย, ป้ายอังกฤษ, ค่า, ภาพ, ข้อความท้าย]
    $kpis = [
        ['รายได้เดือนนี้', 'This month', '฿'.$ui::money($monthlyEarnings), 'deco/coins', 'นับจากวันที่ 1 ของเดือน'],
        ['รายได้สะสมทั้งหมด', 'All time', '฿'.$ui::money($summary['total_earnings_all_time'] ?? 0), 'deco/garland', 'ตั้งแต่เริ่มเป็นไรเดอร์'],
        ['งานเก็บเงินปลายทาง ('.$periodLabel.')', 'Cash on delivery', number_format((int) ($summary['cod_jobs'] ?? 0)).' งาน', 'wallet/scale',
            'เก็บ ฿'.$ui::money($summary['cod_collected'] ?? 0).' · นำส่งเข้าระบบ ฿'.$ui::money(abs((float) ($summary['cod_remitted'] ?? 0)))],
    ];
@endphp

@section('content')
<div class="nvm-stack">
    @include('user.rider.partials.nav', ['rider' => $rider, 'active' => 'earnings'])

    {{-- ── ยอดรายได้ตามช่วงเวลา ─────────────────────────────────── --}}
    <section class="nvm-ph" aria-labelledby="nvm-rd-earn-h">
        <div class="nvm-ph__in">
            <div class="nvm-ph__txt">
                <div class="nvm-eyebrow nvm-latin">Rider earnings</div>
                <h1 id="nvm-rd-earn-h">รายได้ของคุณ</h1>
                <p class="nvm-ph__lead">รายได้ค่าส่งจากงานที่ส่งสำเร็จ · {{ $periodLabel }}</p>
                <div class="nvm-bal">
                    <small>รายได้{{ $periodLabel }}</small>
                    <b class="nvm-foil nvm-num">฿{{ $ui::money($gross) }}</b>
                </div>
                <div class="nvm-pills">
                    <span class="nvm-pill nvm-pill--ok"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg>{{ number_format($jobsCount) }} งาน</span>
                    @if($jobsCount > 0)
                        <span class="nvm-pill nvm-pill--glass"><svg class="nvm-ico"><use href="#nvm-i-cash"/></svg>เฉลี่ย ฿{{ $ui::money($avgPerJob) }}/งาน</span>
                    @endif
                </div>
            </div>
            <img class="nvm-ph__art" src="{{ asset('images/nova/deco/coins.webp') }}" alt="" width="200" height="200">
        </div>
    </section>

    <nav class="nvm-tabs" aria-label="เลือกช่วงเวลา">
        @foreach($periodOptions as $key => $label)
            <a href="{{ route('user.rider.earnings', ['period' => $key]) }}" class="{{ $period === $key ? 'is-on' : '' }}" @if($period === $key) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>

    {{-- ── กระเป๋าเงิน + ถอนเงิน ──────────────────────────────── --}}
    <section class="nvm-card nvm-alert nvm-alert--ok" aria-label="ยอดในกระเป๋าเงิน">
        <img class="nvm-alert__img" src="{{ asset('images/nova/icons/wallet.webp') }}" alt="" width="58" height="58" loading="lazy">
        <div class="nvm-alert__txt">
            <b>ยอดในกระเป๋าเงิน</b>
            <p>รายได้ค่าส่งเข้ากระเป๋าเงินของคุณหลังส่งสำเร็จ</p>
        </div>
        <div class="nvm-alert__side"><b class="nvm-num">฿{{ $ui::money($walletBalance) }}</b></div>
        <div class="nvm-rd-acts">
            @if($kycVerified)
                <a href="{{ route('user.wallet.withdraw') }}" class="nvm-btn nvm-btn--gold nvm-btn--sm"><svg class="nvm-ico"><use href="#nvm-i-cash"/></svg>ถอนเงิน</a>
            @else
                <a href="{{ route('user.kyc.index') }}" class="nvm-btn nvm-btn--gold nvm-btn--sm"><svg class="nvm-ico"><use href="#nvm-i-id"/></svg>ยืนยันตัวตนก่อนถอน</a>
            @endif
            <a href="{{ route('user.wallet.transactions') }}" class="nvm-btn nvm-btn--line nvm-btn--sm"><svg class="nvm-ico"><use href="#nvm-i-history"/></svg>ประวัติกระเป๋า</a>
        </div>
    </section>

    <section class="nvm-kpis nvm-kpis--3" aria-label="สรุปรายได้">
        @foreach($kpis as [$label, $en, $value, $img, $foot])
            <div class="nvm-card nvm-kpi">
                <img class="nvm-kpi__img" src="{{ asset('images/nova/'.$img.'.webp') }}" alt="" width="78" height="78" loading="lazy">
                <div class="nvm-kpi__lbl">{{ $label }}</div>
                <div class="nvm-kpi__en nvm-latin">{{ $en }}</div>
                <div class="nvm-kpi__val nvm-num">{{ $value }}</div>
                <div class="nvm-kpi__foot"><small>{{ $foot }}</small></div>
            </div>
        @endforeach
    </section>

    @if(!$kycVerified)
        <a class="nvm-card nvm-alert nvm-alert--info" href="{{ route('user.kyc.index') }}">
            <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-id"/></svg></span>
            <div class="nvm-alert__txt"><b>ถอนรายได้ต้องยืนยันตัวตน (KYC) ก่อน</b><p>ทำครั้งเดียว ใช้เวลาไม่กี่นาที — แตะเพื่อยืนยันตัวตนตอนนี้</p></div>
        </a>
    @endif

    @if((int) ($summary['unsettled_jobs'] ?? 0) > 0)
        <section class="nvm-card nvm-alert" role="status">
            <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-clock"/></svg></span>
            <div class="nvm-alert__txt">
                <b>มี {{ number_format((int) $summary['unsettled_jobs']) }} งานที่ระบบกำลังเคลียร์เงิน</b>
                <p>เช่นรอหักยอดนำส่งเงินปลายทาง — ระบบจะลองใหม่อัตโนมัติ กรุณาคงเงินในกระเป๋าไว้ให้พอ</p>
            </div>
        </section>
    @endif

    {{-- ── กราฟรายได้รายวัน ─────────────────────────────────────── --}}
    <section class="nvm-card nvm-box" aria-labelledby="nvm-rd-daily-h">
        <div class="nvm-sec-h">
            <div><small class="nvm-latin">Daily</small><h2 id="nvm-rd-daily-h">รายได้รายวัน</h2></div>
            @if($bestDay && $bestDay['earnings'] > 0)
                <span class="nvm-chip nvm-chip--gold"><svg class="nvm-ico"><use href="#nvm-i-award"/></svg>วันที่ดีที่สุด {{ $bestDay['sub'] }} · ฿{{ $ui::money($bestDay['earnings']) }}</span>
            @endif
        </div>
        @if(count($bars) > 0)
            <div class="nvm-rd-scroll" style="--n: {{ count($bars) }}">
                <div class="nvm-bars" role="img" aria-label="กราฟรายได้รายวัน{{ $periodLabel }}">
                    @foreach($bars as $i => $bar)
                        <div class="nvm-bar {{ $bar['is_today'] ? 'is-now' : '' }}" style="--h: {{ $bar['pct'] }}%; --d: {{ $i * 30 }}ms" title="{{ $bar['sub'] }}: ฿{{ $ui::money($bar['earnings']) }} ({{ $bar['jobs'] }} งาน)">
                            <em class="nvm-num">฿{{ $ui::money($bar['earnings']) }}</em><i></i><span>{{ $bar['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>เลือก "สัปดาห์นี้" หรือ "เดือนนี้" เพื่อดูกราฟรายได้รายวัน</p>
        @endif
    </section>

    {{-- ── รายการงานที่ได้รายได้ ───────────────────────────────── --}}
    <section class="nvm-card nvm-box" aria-labelledby="nvm-rd-paid-h">
        <div class="nvm-sec-h">
            <div><small class="nvm-latin">Completed jobs</small><h2 id="nvm-rd-paid-h">งานที่ส่งสำเร็จ</h2></div>
            <span class="nvm-chip nvm-num">ทั้งหมด {{ number_format($recentJobs->total()) }} งาน</span>
        </div>
        @if($recentJobs->count() === 0)
            <div class="nvm-empty">
                <img src="{{ asset('images/nova/wallet/empty.webp') }}" alt="" width="86" height="86" loading="lazy">
                <b>ยังไม่มีรายได้</b>
                ส่งงานแรกให้สำเร็จ รายได้จะเข้ากระเป๋าทันทีหลังกดส่งสำเร็จ
                <div><a href="{{ route('user.rider.jobs', ['tab' => 'available']) }}" class="nvm-btn nvm-btn--gold nvm-btn--sm"><svg class="nvm-ico"><use href="#nvm-i-pin"/></svg>ดูงานรอรับ</a></div>
            </div>
        @else
            <div class="nvm-txs nvm-tks">
                @foreach($recentJobs as $j)
                    @php $settled = $j->earnings_settled_at && ((float) $j->cod_amount <= 0 || $j->cod_settled_at); @endphp
                    <a class="nvm-tx" href="{{ route('user.rider.jobs.show', $j->id) }}">
                        <span class="nvm-tx__ic is-in"><svg class="nvm-ico"><use href="#nvm-i-{{ $ui::novaJobIcon($j->job_type) }}"/></svg></span>
                        <div class="nvm-tx__t">
                            <b>{{ $j->job_type_text }} · <span class="nvm-num">#{{ $j->job_number }}</span></b>
                            <small class="nvm-num">{{ $ui::date($j->completed_at) }} · ค่าส่ง ฿{{ $ui::money($j->total_fee) }}@if((float) $j->cod_amount > 0) · COD ฿{{ $ui::money($j->cod_amount) }}@endif</small>
                        </div>
                        <div class="nvm-tx__r">
                            <span class="nvm-tx__amt nvm-num is-in">+฿{{ $ui::money($j->rider_earnings) }}</span>
                            <span class="nvm-chip {{ $settled ? 'nvm-chip--ok' : 'nvm-chip--warn' }}">{{ $settled ? 'เข้ากระเป๋าแล้ว' : 'กำลังเคลียร์เงิน' }}</span>
                        </div>
                    </a>
                @endforeach
            </div>
            @if($recentJobs->hasPages())
                <div class="nvm-pager nvm-gap-t">{{ $recentJobs->links() }}</div>
            @endif
        @endif
    </section>
</div>
@endsection
