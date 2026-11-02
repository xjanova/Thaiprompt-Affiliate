{{--
    ตำแหน่งสภาพคล่องของฉัน — TPIX DEX (ธีมโนวาเต็ม)
    - $positions = ตำแหน่ง active ของสมาชิกเอง (พร้อม pool.tokenA/tokenB)
    - เดิมหน้าโหลดผ่าน /api/v1/tpix/dex/my-positions ซึ่งไม่มี route อยู่จริง → ตอนนี้เรนเดอร์จากเซิร์ฟเวอร์
--}}
@extends('layouts.user-nova')

@section('title', 'ตำแหน่งสภาพคล่องของฉัน - TPIX DEX')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/dex-staking.css') }}?v={{ filemtime(public_path('theme-nova/member/dex-staking.css')) }}">@endpush

@php
    $num = function ($n, int $d = 4) {
        $s = number_format((float) $n, $d, '.', ',');

        return str_contains($s, '.') ? rtrim(rtrim($s, '0'), '.') : $s;
    };
    $abbr = fn (?string $symbol) => mb_strtoupper(mb_substr((string) $symbol, 0, 3));
    $positions = $positions->filter(fn ($p) => $p->pool && $p->pool->tokenA && $p->pool->tokenB)->values();

    $totalValue = $positions->sum(fn ($p) => (float) $p->getCurrentValueTPIX());
    $totalFees = $positions->sum(fn ($p) => (float) $p->fees_earned);
    $unclaimed = $positions->sum(fn ($p) => (float) $p->unclaimed_fees);
@endphp

@section('content')
<div class="nvm-stack">

<section class="nvm-ph" aria-labelledby="nvm-dx-mp-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.dex.pools') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>พูลสภาพคล่อง</a>
            <div class="nvm-eyebrow nvm-latin">My positions</div>
            <h1 id="nvm-dx-mp-h">ตำแหน่งสภาพคล่องของฉัน</h1>
            <p class="nvm-ph__lead">ดูส่วนแบ่งในแต่ละพูล ค่าธรรมเนียมที่ได้ เพิ่มหรือถอนสภาพคล่องได้จากที่นี่</p>
            <div class="nvm-hero__actions">
                <a class="nvm-btn nvm-btn--gold" href="{{ route('user.dex.add-liquidity') }}"><svg class="nvm-ico"><use href="#nvm-i-plus"/></svg>เพิ่มสภาพคล่อง</a>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/wallet/card.webp') }}" alt="" width="200" height="200">
    </div>
</section>

<section class="nvm-kpis" aria-label="สรุปตำแหน่งของฉัน">
    @foreach([
        ['จำนวนตำแหน่ง', 'Positions', number_format($positions->count())],
        ['มูลค่ารวม', 'Total value', $num($totalValue, 2).' TPIX'],
        ['ค่าธรรมเนียมที่ได้', 'Fees earned', $num($totalFees)],
        ['ค่าธรรมเนียมยังไม่รับ', 'Unclaimed', $num($unclaimed)],
    ] as [$label, $en, $value])
        <div class="nvm-card nvm-kpi">
            <div class="nvm-kpi__lbl">{{ $label }}</div>
            <div class="nvm-kpi__en nvm-latin">{{ $en }}</div>
            <div class="nvm-kpi__val nvm-num">{{ $value }}</div>
        </div>
    @endforeach
</section>

<section class="nvm-card nvm-box" aria-labelledby="nvm-dx-mpl-h">
    <div class="nvm-sec-h"><div><small class="nvm-latin">Positions</small><h2 id="nvm-dx-mpl-h">ตำแหน่งที่เปิดอยู่</h2></div></div>

    @if($positions->isEmpty())
        <div class="nvm-empty">
            <img src="{{ asset('images/nova/wallet/empty.webp') }}" alt="" width="86" height="86" loading="lazy">
            <b>ยังไม่มีตำแหน่งสภาพคล่อง</b>
            เพิ่มสภาพคล่องเข้าพูลเพื่อรับส่วนแบ่งค่าธรรมเนียม
            <div><a class="nvm-btn nvm-btn--gold nvm-btn--sm" href="{{ route('user.dex.add-liquidity') }}"><svg class="nvm-ico"><use href="#nvm-i-plus"/></svg>เพิ่มสภาพคล่อง</a></div>
        </div>
    @else
        <div class="nvm-dx-pools">
            @foreach($positions as $position)
                @php
                    $pool = $position->pool;
                    $roi = $position->roi;
                @endphp
                <article class="nvm-card nvm-dx-pool">
                    <div class="nvm-dx-pool__head">
                        <span class="nvm-dx-pair" aria-hidden="true"><span class="nvm-dx-coin">{{ $abbr($pool->tokenA->symbol) }}</span><span class="nvm-dx-coin nvm-dx-coin--b">{{ $abbr($pool->tokenB->symbol) }}</span></span>
                        <div>
                            <b>{{ $pool->tokenA->symbol }} / {{ $pool->tokenB->symbol }}</b>
                            <small>ถือมา <span class="nvm-num">{{ number_format($position->duration_days) }}</span> วัน</small>
                        </div>
                    </div>
                    <div class="nvm-pills">
                        <span class="nvm-chip nvm-chip--gold">ส่วนแบ่ง <span class="nvm-num">{{ $num($position->share_percentage, 4) }}%</span></span>
                        @if($roi !== null)
                            <span class="nvm-chip {{ $roi >= 0 ? 'nvm-chip--ok' : 'nvm-chip--bad' }}">ROI <span class="nvm-num">{{ $num($roi, 2) }}%</span></span>
                        @endif
                    </div>
                    <dl class="nvm-kv">
                        <dt>{{ $pool->tokenA->symbol }}</dt><dd class="nvm-num">{{ $num($position->token_a_amount) }}</dd>
                        <dt>{{ $pool->tokenB->symbol }}</dt><dd class="nvm-num">{{ $num($position->token_b_amount) }}</dd>
                        <dt>LP Token</dt><dd class="nvm-num">{{ $num($position->lp_tokens) }}</dd>
                        <dt>มูลค่าปัจจุบัน</dt><dd class="nvm-num">{{ $num($position->getCurrentValueTPIX()) }} TPIX</dd>
                        <dt>ค่าธรรมเนียมที่ได้</dt><dd class="nvm-num">{{ $num($position->fees_earned) }}</dd>
                    </dl>
                    <div class="nvm-dx-pool__acts">
                        <a class="nvm-btn nvm-btn--line nvm-btn--sm" href="{{ route('user.dex.add-liquidity', ['pool' => $pool->id]) }}"><svg class="nvm-ico"><use href="#nvm-i-plus"/></svg>เพิ่ม</a>
                        <a class="nvm-btn nvm-btn--gold nvm-btn--sm" href="{{ route('user.dex.remove-liquidity', $position->id) }}"><svg class="nvm-ico"><use href="#nvm-i-down"/></svg>ถอน</a>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</section>

</div>
@endsection
