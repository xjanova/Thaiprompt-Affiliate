{{--
    TPIX Staking — พูลที่เปิดอยู่ + Stake ของฉัน (ธีมโนวาเต็ม)
    - $pools = พูลสถานะ active (เรียง APY มาก→น้อย) · $myStakes = Stake ที่ยัง active ของสมาชิกเอง
    - เดิมหน้านี้แสดง "แผนลงทุน" ตัวอย่างที่เขียนตายตัวใน JS (ROI 0.5-1.5%/วัน, กดลงทุนแล้วขึ้น "สำเร็จ (Demo)")
      และตำแหน่งปลอม ไม่ได้ใช้ข้อมูลจริงจาก controller เลย → ตอนนี้แสดงพูลและ Stake จริงจากฐานข้อมูล
--}}
@extends('layouts.user-nova')

@section('title', 'TPIX Staking')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/dex-staking.css') }}?v={{ filemtime(public_path('theme-nova/member/dex-staking.css')) }}">@endpush

@php
    $num = function ($n, int $d = 4) {
        $s = number_format((float) $n, $d, '.', ',');

        return str_contains($s, '.') ? rtrim(rtrim($s, '0'), '.') : $s;
    };
    $abbr = fn (?string $symbol) => mb_strtoupper(mb_substr((string) $symbol, 0, 3));
    $thDate = fn ($d) => $d ? $d->copy()->locale('th')->translatedFormat('j M Y') : '-';
    $myStakes = $myStakes->filter(fn ($s) => $s->pool && $s->pool->token)->values();
    $totalRewards = $myStakes->sum(fn ($s) => (float) $s->rewards_earned);
    $poolCount = $myStakes->pluck('pool_id')->unique()->count();
@endphp

@section('content')
<div class="nvm-stack">

<section class="nvm-ph" aria-labelledby="nvm-dx-st-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <div class="nvm-eyebrow nvm-latin">TPIX Staking</div>
            <h1 id="nvm-dx-st-h">Stake เหรียญ รับรางวัล</h1>
            <p class="nvm-ph__lead">นำเหรียญที่ถืออยู่ไปฝากในพูล Stake แล้วรับรางวัลตามอัตรา APY ของแต่ละพูล</p>
            <div class="nvm-pills">
                <span class="nvm-pill nvm-pill--glass"><svg class="nvm-ico"><use href="#nvm-i-lock"/></svg>Stake ที่เปิดอยู่ <span class="nvm-num">{{ number_format($myStakes->count()) }}</span></span>
                <a class="nvm-pill nvm-pill--glass" href="{{ route('user.staking.history') }}"><svg class="nvm-ico"><use href="#nvm-i-history"/></svg>ประวัติ Stake</a>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/deco/crystal.webp') }}" alt="" width="200" height="200">
    </div>
</section>

<section class="nvm-card nvm-alert nvm-dx-risk" role="note">
    <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></span>
    <div class="nvm-alert__txt">
        <b>คำเตือนความเสี่ยง</b>
        <ul>
            <li>มูลค่าเหรียญขึ้นลงได้ ผลตอบแทนในอดีตไม่รับประกันผลในอนาคต</li>
            <li>ถอนก่อนครบระยะล็อกอาจมีค่าปรับตามเงื่อนไขของพูล</li>
            <li>ศึกษาเงื่อนไขของแต่ละพูลให้ละเอียด และใช้เฉพาะเหรียญที่พร้อมรับความเสี่ยงได้</li>
        </ul>
    </div>
</section>

<div class="nvm-split">
    <section class="nvm-card nvm-box" aria-labelledby="nvm-dx-stp-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Pools</small><h2 id="nvm-dx-stp-h">พูล Stake ที่เปิดอยู่</h2></div></div>

        @if($pools->isEmpty())
            <div class="nvm-empty">
                <img src="{{ asset('images/nova/deco/crystal.webp') }}" alt="" width="86" height="86" loading="lazy">
                <b>ยังไม่มีพูล Stake ที่เปิดอยู่</b>
                เมื่อมีพูลเปิดให้ Stake จะแสดงที่นี่
            </div>
        @else
            <div class="nvm-dx-pools">
                @foreach($pools as $pool)
                    @php
                        $cap = (float) ($pool->pool_cap ?? 0);
                        $fill = $cap > 0 ? min(100, (float) $pool->total_staked / $cap * 100) : null;
                    @endphp
                    <article class="nvm-card nvm-dx-pool">
                        <div class="nvm-dx-pool__head">
                            <span class="nvm-dx-coin" aria-hidden="true">{{ $abbr($pool->token->symbol) }}</span>
                            <div>
                                <b>{{ $pool->name ?: $pool->token->symbol }}</b>
                                <small>{{ $pool->token->name }} ({{ $pool->token->symbol }})</small>
                            </div>
                        </div>
                        <div class="nvm-pills">
                            <span class="nvm-chip nvm-chip--ok">APY <span class="nvm-num">{{ $num($pool->apy, 2) }}%</span></span>
                            <span class="nvm-chip"><svg class="nvm-ico"><use href="#nvm-i-lock"/></svg>ล็อก <span class="nvm-num">{{ number_format((int) $pool->lock_period_days) }}</span> วัน</span>
                        </div>
                        <div class="nvm-minis">
                            <div class="nvm-mini"><small>ขั้นต่ำ</small><b class="nvm-num">{{ $num($pool->min_stake_amount) }}</b></div>
                            <div class="nvm-mini"><small>Stake รวม</small><b class="nvm-num">{{ $num($pool->total_staked, 2) }}</b></div>
                        </div>
                        @if($fill !== null)
                            <div class="nvm-meter">
                                <div class="nvm-meter__row"><span>เต็มพูลแล้ว</span><b class="nvm-num">{{ $num($fill, 1) }}%</b></div>
                                <div class="nvm-meter__bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ round($fill) }}" aria-label="สัดส่วนที่ Stake แล้วของพูล"><i class="nvm-dx-bar" style="--w: {{ round($fill, 1) }}%"></i></div>
                            </div>
                        @endif
                        <div class="nvm-dx-pool__acts">
                            <a class="nvm-btn nvm-btn--line nvm-btn--sm" href="{{ route('user.staking.show', $pool->id) }}"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>รายละเอียด</a>
                            <a class="nvm-btn nvm-btn--gold nvm-btn--sm" href="{{ route('user.staking.stake-form', $pool->id) }}"><svg class="nvm-ico"><use href="#nvm-i-plus"/></svg>Stake</a>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <aside class="nvm-stack">
        <section class="nvm-card nvm-box" aria-labelledby="nvm-dx-sum-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Summary</small><h2 id="nvm-dx-sum-h">สรุปของฉัน</h2></div></div>
            <div class="nvm-minis">
                <div class="nvm-mini"><small>Stake ที่เปิดอยู่</small><b class="nvm-num">{{ number_format($myStakes->count()) }}</b></div>
                <div class="nvm-mini"><small>ในพูล</small><b class="nvm-num">{{ number_format($poolCount) }}</b></div>
                <div class="nvm-mini nvm-mini--wide"><small>รางวัลสะสม (รวมทุกเหรียญ)</small><b class="nvm-num is-in">{{ $num($totalRewards) }}</b></div>
            </div>
        </section>

        <section class="nvm-card nvm-box" aria-labelledby="nvm-dx-my-h">
            <div class="nvm-sec-h">
                <div><small class="nvm-latin">My stakes</small><h2 id="nvm-dx-my-h">Stake ของฉัน</h2></div>
                <a href="{{ route('user.staking.history') }}">ประวัติ<svg class="nvm-ico"><use href="#nvm-i-chev"/></svg></a>
            </div>
            @if($myStakes->isEmpty())
                <div class="nvm-empty">
                    <img src="{{ asset('images/nova/wallet/empty.webp') }}" alt="" width="86" height="86" loading="lazy">
                    <b>ยังไม่มี Stake ที่เปิดอยู่</b>
                    เลือกพูลด้านซ้ายเพื่อเริ่ม Stake
                </div>
            @else
                <ul class="nvm-txs">
                    @foreach($myStakes as $stake)
                        @php $locked = $stake->unlock_at && $stake->unlock_at->isFuture(); @endphp
                        <li class="nvm-tx">
                            <span class="nvm-tx__ic {{ $locked ? '' : 'is-in' }}"><svg class="nvm-ico"><use href="#nvm-i-{{ $locked ? 'lock' : 'check' }}"/></svg></span>
                            <div class="nvm-tx__t">
                                <b><a class="nvm-link" href="{{ route('user.staking.show', $stake->pool_id) }}">{{ $stake->pool->name ?: $stake->pool->token->symbol }}</a></b>
                                <small>{{ $locked ? 'ปลดล็อก '.$thDate($stake->unlock_at) : 'ปลดล็อกแล้ว' }}</small>
                            </div>
                            <div class="nvm-tx__r">
                                <span class="nvm-tx__amt nvm-num">{{ $num($stake->amount) }} {{ $stake->pool->token->symbol }}</span>
                                <small class="nvm-help nvm-num">รางวัล {{ $num($stake->rewards_earned) }}</small>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </aside>
</div>

</div>
@endsection
