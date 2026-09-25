{{--
    รายได้ไรเดอร์ (user.rider.earnings) — ?period=today|week|month|all (ค่าเริ่มต้น week)
    Controller: User\RiderController@earnings
    ตัวแปร: rider, period, periodOptions, summary (ชุดเดียวกับ API /rider/earnings), dailyEarnings, monthlyEarnings,
            walletBalance, recentJobs (paginator ของงานที่สำเร็จ), kyc{status, verified, required_for_withdrawal}, pageTitle
--}}
@extends('layouts.user-v4')

@section('title', $pageTitle ?? 'รายได้ไรเดอร์')

@push('styles')
    @include('user.rider.partials.styles')
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
@endphp

@section('content')
<div class="rd-scope">
    @include('user.rider.partials.nav', ['rider' => $rider, 'active' => 'earnings'])

    {{-- ── ยอดรายได้ตามช่วงเวลา ─────────────────────────────────── --}}
    <section class="tp-card rd-hero">
        <div class="rd-hero-in" style="flex-direction:column; align-items:stretch;">
            <div class="rd-row" style="justify-content:space-between;">
                <div class="rd-row" style="gap:12px;">
                    <span class="tp-tile" style="width:48px; height:48px; border-radius:16px; font-size:20px;"><i class="fas fa-coins"></i></span>
                    <div>
                        <h1 class="rd-h1">รายได้ของคุณ</h1>
                        <div class="rd-muted rd-small">รายได้ค่าส่งจากงานที่ส่งสำเร็จ · {{ $periodLabel }}</div>
                    </div>
                </div>
            </div>
            <div class="rd-row" role="group" aria-label="เลือกช่วงเวลา">
                @foreach($periodOptions as $key => $label)
                    <a href="{{ route('user.rider.earnings', ['period' => $key]) }}" class="rd-chip {{ $period === $key ? 'on' : '' }}" @if($period === $key) aria-current="true" @endif>{{ $label }}</a>
                @endforeach
            </div>
            <div class="rd-row" style="align-items:flex-end; gap:18px;">
                <div>
                    <div class="rd-small rd-muted">รายได้{{ $periodLabel }}</div>
                    <div class="rd-money" style="font-size:clamp(36px, 10vw, 52px); line-height:1.05;">฿{{ $ui::money($gross) }}</div>
                </div>
                <div class="rd-row" style="gap:8px; padding-bottom:6px;">
                    <span class="rd-pill rd-tone-ok"><i class="fas fa-circle-check"></i> {{ number_format($jobsCount) }} งาน</span>
                    @if($jobsCount > 0)
                        <span class="rd-pill rd-tone-gold"><i class="fas fa-calculator"></i> เฉลี่ย ฿{{ $ui::money($avgPerJob) }}/งาน</span>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- ── กระเป๋าเงิน + ถอนเงิน ──────────────────────────────── --}}
    <div class="rd-grid" style="--rd-min:200px;">
        <div class="tp-card rd-stat featured rd-tone-gold">
            <span class="ic"><i class="fas fa-wallet"></i></span>
            <span class="lbl">ยอดในกระเป๋าเงิน</span>
            <span class="val">฿{{ $ui::money($walletBalance) }}</span>
            <div class="rd-row" style="margin-top:6px;">
                @if($kycVerified)
                    <a href="{{ route('user.wallet.withdraw') }}" class="rd-btn3d rd-tone-gold sm"><i class="fas fa-money-bill-transfer"></i> ถอนเงิน</a>
                @else
                    <a href="{{ route('user.kyc.index') }}" class="rd-btn3d rd-tone-info sm"><i class="fas fa-id-card"></i> ยืนยันตัวตนก่อนถอน</a>
                @endif
                <a href="{{ route('user.wallet.transactions') }}" class="tp-btn tp-btn-sm">ประวัติกระเป๋า</a>
            </div>
        </div>
        <div class="tp-card rd-stat rd-tone-ok">
            <span class="ic"><i class="fas fa-calendar"></i></span>
            <span class="lbl">รายได้เดือนนี้</span>
            <span class="val">฿{{ $ui::money($monthlyEarnings) }}</span>
            <span class="sub">นับจากวันที่ 1 ของเดือน</span>
        </div>
        <div class="tp-card rd-stat rd-tone-info">
            <span class="ic"><i class="fas fa-trophy"></i></span>
            <span class="lbl">รายได้สะสมทั้งหมด</span>
            <span class="val">฿{{ $ui::money($summary['total_earnings_all_time'] ?? 0) }}</span>
            <span class="sub">ตั้งแต่เริ่มเป็นไรเดอร์</span>
        </div>
        <div class="tp-card rd-stat rd-tone-warn">
            <span class="ic"><i class="fas fa-money-bill-wave"></i></span>
            <span class="lbl">งานเก็บเงินปลายทาง ({{ $periodLabel }})</span>
            <span class="val">{{ number_format((int) ($summary['cod_jobs'] ?? 0)) }} งาน</span>
            <span class="sub">เก็บ ฿{{ $ui::money($summary['cod_collected'] ?? 0) }} · นำส่งเข้าระบบ ฿{{ $ui::money(abs((float) ($summary['cod_remitted'] ?? 0))) }}</span>
        </div>
    </div>

    @if(!$kycVerified)
        <div class="rd-alert rd-tone-info">
            <i class="fas fa-id-card"></i>
            <div>
                <b>ถอนรายได้ต้องยืนยันตัวตน (KYC) ก่อน</b> — ทำครั้งเดียว ใช้เวลาไม่กี่นาที
                <div style="margin-top:6px;"><a href="{{ route('user.kyc.index') }}" class="rd-link">ยืนยันตัวตนตอนนี้ <i class="fas fa-arrow-right"></i></a></div>
            </div>
        </div>
    @endif

    @if((int) ($summary['unsettled_jobs'] ?? 0) > 0)
        <div class="rd-alert rd-tone-warn">
            <i class="fas fa-hourglass-half"></i>
            <div>มี <b>{{ number_format((int) $summary['unsettled_jobs']) }} งาน</b> ที่ระบบกำลังเคลียร์เงิน (เช่นรอหักยอดนำส่งเงินปลายทาง) — ระบบจะลองใหม่อัตโนมัติ กรุณาคงเงินในกระเป๋าไว้ให้พอ</div>
        </div>
    @endif

    {{-- ── กราฟรายได้รายวัน ─────────────────────────────────────── --}}
    <section class="tp-card rd-stack">
        <div class="rd-row" style="justify-content:space-between;">
            <h2 class="rd-h2"><i class="fas fa-chart-column" style="color:var(--accent1);"></i> รายได้รายวัน</h2>
            @if($bestDay && $bestDay['earnings'] > 0)
                <span class="rd-pill rd-tone-gold"><i class="fas fa-crown"></i> วันที่ดีที่สุด {{ $bestDay['sub'] }} · ฿{{ $ui::money($bestDay['earnings']) }}</span>
            @endif
        </div>
        @if(count($bars) > 0)
            <div class="rd-scroll-x">
                <div class="tp-bars rd-bars" role="img" aria-label="กราฟรายได้รายวัน{{ $periodLabel }}" style="min-width:{{ count($bars) > 10 ? count($bars) * 26 : 0 }}px;">
                    @foreach($bars as $bar)
                        <div class="col {{ $bar['is_today'] ? 'today' : '' }}" title="{{ $bar['sub'] }}: ฿{{ $ui::money($bar['earnings']) }} ({{ $bar['jobs'] }} งาน)">
                            <span class="amt">{{ count($bars) <= 10 && $bar['earnings'] > 0 ? '฿'.$ui::money($bar['earnings']) : '' }}</span>
                            <div class="stack"><i class="bar {{ $bar['is_today'] ? 'b' : 'a' }}" style="height:{{ $bar['pct'] }}%; display:block;"></i></div>
                            <span class="lbl">{{ $bar['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="rd-muted" style="font-size:13px;">
                <i class="fas fa-circle-info"></i> เลือก "สัปดาห์นี้" หรือ "เดือนนี้" เพื่อดูกราฟรายได้รายวัน
            </div>
        @endif
    </section>

    {{-- ── รายการงานที่ได้รายได้ ───────────────────────────────── --}}
    <section class="tp-card rd-stack">
        <div class="rd-row" style="justify-content:space-between;">
            <h2 class="rd-h2"><i class="fas fa-receipt" style="color:var(--accent1);"></i> งานที่ส่งสำเร็จ</h2>
            <span class="rd-muted rd-small">ทั้งหมด {{ number_format($recentJobs->total()) }} งาน</span>
        </div>
        @if($recentJobs->count() === 0)
            <div class="rd-empty">
                <div class="ic"><i class="fas fa-piggy-bank"></i></div>
                <div style="font-weight:800; font-size:16px;">ยังไม่มีรายได้</div>
                <p class="rd-muted" style="font-size:13px; margin:6px auto 14px; max-width:380px;">ส่งงานแรกให้สำเร็จ รายได้จะเข้ากระเป๋าทันทีหลังกดส่งสำเร็จ</p>
                <a href="{{ route('user.rider.jobs', ['tab' => 'available']) }}" class="rd-btn3d rd-tone-gold sm"><i class="fas fa-bolt"></i> ดูงานรอรับ</a>
            </div>
        @else
            <div class="rd-list">
                @foreach($recentJobs as $j)
                    <a href="{{ route('user.rider.jobs.show', $j->id) }}" class="rd-item rd-tone-ok">
                        <span class="ic"><i class="fas {{ $ui::jobIcon($j->job_type) }}"></i></span>
                        <span class="main">
                            <span class="ttl" style="display:block;">{{ $j->job_type_text }} · #{{ $j->job_number }}</span>
                            <span class="sub" style="display:block;">
                                {{ $ui::date($j->completed_at) }} · ค่าส่ง ฿{{ $ui::money($j->total_fee) }}
                                @if((float) $j->cod_amount > 0) · COD ฿{{ $ui::money($j->cod_amount) }} @endif
                            </span>
                        </span>
                        <span class="end">
                            <span class="rd-money" style="font-size:16px; color:var(--rd-ok);">+฿{{ $ui::money($j->rider_earnings) }}</span>
                            <span class="rd-small rd-muted" style="display:block; margin-top:3px;">
                                @if($j->earnings_settled_at && ((float) $j->cod_amount <= 0 || $j->cod_settled_at))
                                    <i class="fas fa-circle-check" style="color:var(--rd-ok);"></i> เข้ากระเป๋าแล้ว
                                @else
                                    <i class="fas fa-hourglass-half" style="color:var(--rd-warn);"></i> กำลังเคลียร์เงิน
                                @endif
                            </span>
                        </span>
                    </a>
                @endforeach
            </div>
            @if($recentJobs->hasPages())
                <div class="rd-row" style="justify-content:space-between;">
                    @if($recentJobs->previousPageUrl())
                        <a href="{{ $recentJobs->previousPageUrl() }}" class="tp-btn"><i class="fas fa-chevron-left"></i> ก่อนหน้า</a>
                    @else
                        <span></span>
                    @endif
                    <span class="rd-muted rd-small">หน้า {{ $recentJobs->currentPage() }} / {{ $recentJobs->lastPage() }}</span>
                    @if($recentJobs->nextPageUrl())
                        <a href="{{ $recentJobs->nextPageUrl() }}" class="tp-btn">ถัดไป <i class="fas fa-chevron-right"></i></a>
                    @else
                        <span></span>
                    @endif
                </div>
            @endif
        @endif
    </section>
</div>
@endsection
