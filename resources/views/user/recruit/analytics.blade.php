{{--
    สถิติการตลาดหน้า Recruit (ธีมโนวาเต็ม)
    - RecruitPageController::analytics: $daily = ทุกวันในช่วง (ไม่มีข้อมูล = 0), $days ∈ {7, 30, 90}
    - กราฟวาดด้วย CSS ล้วน (ไม่โหลด Chart.js จาก CDN แล้ว) → ใช้สีตามโหมดมืด/สว่างอัตโนมัติ
--}}
@extends('layouts.user-nova')

@section('title', 'สถิติการตลาด')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/team-recruit.css') }}?v={{ filemtime(public_path('theme-nova/member/team-recruit.css')) }}">@endpush

@php
    $pct = fn ($part, $whole) => $whole > 0 ? number_format($part / $whole * 100, 1).'%' : '0.0%';
    $funnelSteps = [
        ['search', 'การเข้าชม', $funnel['visits'], '100%'],
        ['send', 'คลิกสมัคร', $funnel['clicked_register'], $pct($funnel['clicked_register'], $funnel['visits'])],
        ['users', 'ผู้มุ่งหวัง', $funnel['leads'], $pct($funnel['leads'], $funnel['visits'])],
        ['check', 'สมัครสำเร็จ', $funnel['conversions'], $pct($funnel['conversions'], $funnel['visits'])],
    ];

    // แกนกราฟ: ค่าสูงสุดของทั้งสองชุด (อย่างน้อย 1 กันหารศูนย์)
    $peak = max(1, collect($daily)->max(fn ($d) => max($d['visits'], $d['leads'])));
    // ป้ายวันที่: ช่วงยาวแสดงห่างๆ ไม่ให้ตัวอักษรชนกัน
    $labelEvery = count($daily) > 31 ? 7 : (count($daily) > 10 ? 3 : 1);

    $deviceNames = ['mobile' => 'มือถือ', 'desktop' => 'คอมพิวเตอร์', 'tablet' => 'แท็บเล็ต'];
    $deviceTotal = max(1, (int) $deviceBreakdown->sum('count'));
@endphp

@section('content')
<div class="nvm-stack">

<section class="nvm-ph" aria-labelledby="nvm-ra-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.marketing.recruit.index') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>หน้าแนะนำสมาชิก</a>
            <div class="nvm-eyebrow nvm-latin">Marketing analytics</div>
            <h1 id="nvm-ra-h">สถิติการตลาด</h1>
            <p class="nvm-ph__lead">ดูว่ามีคนเข้าหน้า Recruit เท่าไร มาจากไหน และสมัครสำเร็จกี่คน ในช่วง {{ $days }} วันล่าสุด</p>
            <div class="nvm-hero__actions">
                <nav class="nvm-tabs" aria-label="เลือกช่วงเวลา">
                    @foreach([7, 30, 90] as $d)
                        <a href="{{ route('user.marketing.recruit.analytics', ['days' => $d]) }}" class="{{ (int) $days === $d ? 'is-on' : '' }}" @if((int) $days === $d) aria-current="page" @endif>{{ $d }} วัน</a>
                    @endforeach
                </nav>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/deco/crystal.webp') }}" alt="" width="200" height="200">
    </div>
</section>

<section class="nvm-card nvm-box" aria-labelledby="nvm-ra-fn-h">
    <div class="nvm-sec-h"><div><small class="nvm-latin">Conversion funnel</small><h2 id="nvm-ra-fn-h">เส้นทางสู่การสมัคร ({{ $days }} วันล่าสุด)</h2></div></div>
    <div class="nvm-tr-funnel">
        @foreach($funnelSteps as [$icon, $label, $value, $rate])
            <div class="nvm-mini">
                <span class="nvm-tx__ic is-in"><svg class="nvm-ico"><use href="#nvm-i-{{ $icon }}"/></svg></span>
                <small>{{ $label }}</small>
                <b class="nvm-num">{{ number_format($value) }}</b>
                <em class="nvm-num">{{ $rate }}</em>
            </div>
        @endforeach
    </div>
</section>

<div class="nvm-split">
    <section class="nvm-card nvm-box" aria-labelledby="nvm-ra-day-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Daily</small><h2 id="nvm-ra-day-h">การเข้าชมและผู้มุ่งหวังรายวัน</h2></div></div>
        <div class="nvm-tr-legend"><span><i></i>การเข้าชม</span><span><i class="is-lead"></i>ผู้มุ่งหวัง</span></div>
        <div class="nvm-tr-chart">
            <div class="nvm-tr-chart__in" role="img" aria-label="กราฟแท่งการเข้าชมและผู้มุ่งหวังรายวัน {{ count($daily) }} วัน">
                @foreach($daily as $i => $day)
                    <div class="nvm-tr-day" title="{{ $day['date']->locale('th')->translatedFormat('j M') }} · เข้าชม {{ number_format($day['visits']) }} · ผู้มุ่งหวัง {{ number_format($day['leads']) }}">
                        <div class="nvm-tr-day__bars">
                            <i style="--h: {{ round($day['visits'] / $peak * 100, 1) }}%"></i>
                            <i class="is-lead" style="--h: {{ round($day['leads'] / $peak * 100, 1) }}%"></i>
                        </div>
                        <span class="nvm-num">{{ $i % $labelEvery === 0 ? $day['date']->format('j/n') : '' }}</span>
                    </div>
                @endforeach
            </div>
        </div>
        @if(collect($daily)->sum('visits') + collect($daily)->sum('leads') === 0)
            <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>ยังไม่มีการเข้าชมในช่วงนี้ ลองแชร์ลิงก์หน้า Recruit ดูนะ</p>
        @endif
    </section>

    <section class="nvm-card nvm-box" aria-labelledby="nvm-ra-dev-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Devices</small><h2 id="nvm-ra-dev-h">การเข้าชมตามอุปกรณ์</h2></div></div>
        @forelse($deviceBreakdown as $device)
            @php $share = round($device->count / $deviceTotal * 100, 1); @endphp
            <div class="nvm-meter">
                <div class="nvm-meter__row"><span>{{ $deviceNames[$device->device_type] ?? ($device->device_type ?: 'ไม่ระบุ') }}</span><b class="nvm-num">{{ number_format($device->count) }} · {{ $share }}%</b></div>
                <div class="nvm-meter__bar nvm-tr-meter" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ round($share) }}"><i style="--w: {{ $share }}%"></i></div>
            </div>
        @empty
            <div class="nvm-empty">
                <img src="{{ asset('images/nova/wallet/phone.webp') }}" alt="" width="86" height="86" loading="lazy">
                <b>ยังไม่มีข้อมูลอุปกรณ์</b>
                เมื่อมีคนเข้าชม ระบบจะแยกให้ว่าใช้มือถือหรือคอมพิวเตอร์
            </div>
        @endforelse
    </section>
</div>

<section class="nvm-card nvm-box" aria-labelledby="nvm-ra-ref-h">
    <div class="nvm-sec-h"><div><small class="nvm-latin">Top referrers</small><h2 id="nvm-ra-ref-h">แหล่งอ้างอิงยอดนิยม (10 อันดับ)</h2></div></div>
    @if($topReferrers->count() > 0)
        <ul class="nvm-txs">
            @foreach($topReferrers as $index => $referrer)
                <li class="nvm-tx">
                    <span class="nvm-tx__ic nvm-num">{{ $index + 1 }}</span>
                    <div class="nvm-tx__t">
                        <b>{{ parse_url($referrer->referrer_url, PHP_URL_HOST) ?: $referrer->referrer_url }}</b>
                        <small>{{ \Illuminate\Support\Str::limit($referrer->referrer_url, 60) }}</small>
                    </div>
                    <div class="nvm-tx__r"><span class="nvm-chip nvm-chip--info nvm-num">{{ number_format($referrer->count) }} ครั้ง</span></div>
                </li>
            @endforeach
        </ul>
    @else
        <div class="nvm-empty">
            <img src="{{ asset('images/nova/deco/scroll.webp') }}" alt="" width="86" height="86" loading="lazy">
            <b>ยังไม่มีข้อมูลแหล่งอ้างอิง</b>
            เมื่อมีคนกดลิงก์มาจากเว็บอื่น (เช่น Facebook) จะแสดงที่นี่
        </div>
    @endif
</section>

</div>
@endsection
