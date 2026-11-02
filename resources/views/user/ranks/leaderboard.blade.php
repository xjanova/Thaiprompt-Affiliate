{{--
    กระดานผู้นำ (ธีมโนวาเต็ม) — RankController::leaderboard
    $leaderboard: [['user' => User, 'rank' => ?Rank, 'points' => int, ...]] เรียงคะแนนมากไปน้อย สูงสุด 100 คน
    $myPosition: อันดับจริงของตัวเอง (null = ยังไม่มียศ จึงยังไม่อยู่บนกระดาน)
    🔒 หน้านี้สมาชิกทุกคนเปิดได้ — แสดงแค่ชื่อ ไม่แสดงอีเมลของคนอื่น (เดิมโชว์อีเมลทั้ง 100 คน)
--}}
@extends('layouts.user-nova')

@section('title', 'กระดานผู้นำ')

@php
    $me = auth()->user();
    $rankName = fn ($r) => $r ? ($r->name_th ?: $r->name) : null;
    $total = count($leaderboard);
    $inTop = collect($leaderboard)->search(fn ($row) => $row['user']->id === $me->id);
    $podium = $total >= 3 ? array_slice($leaderboard, 0, 3) : [];
@endphp

@section('content')

<div class="nvm-stack">

<section class="nvm-ph" aria-labelledby="nvm-lb-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.ranks.dashboard') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>ระดับของฉัน</a>
            <div class="nvm-eyebrow nvm-latin">Leaderboard</div>
            <h1 id="nvm-lb-h">กระดานผู้นำ</h1>
            <p class="nvm-ph__lead">อันดับคะแนนสะสมของสมาชิกที่มียศแล้ว {{ $total ? number_format($total).' อันดับแรก' : '' }}</p>
            <div class="nvm-pills">
                @if($myPosition)
                    <span class="nvm-pill nvm-pill--gold"><svg class="nvm-ico"><use href="#nvm-i-award"/></svg>อันดับของคุณ <span class="nvm-num">#{{ number_format($myPosition) }}</span></span>
                @else
                    <span class="nvm-pill nvm-pill--glass">รับยศแรกเพื่อขึ้นกระดาน</span>
                @endif
                <span class="nvm-pill nvm-pill--glass"><span class="nvm-num">{{ number_format((int) ($me->rank_points ?? 0)) }}</span> คะแนน</span>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/deco/crystal.webp') }}" alt="" width="200" height="200">
    </div>
</section>

@if($myPosition && $inTop === false)
    <section class="nvm-card nvm-alert nvm-alert--info">
        <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></span>
        <div class="nvm-alert__txt"><b>คุณอยู่อันดับ #{{ number_format($myPosition) }}</b><p>สะสมคะแนนเพิ่มเพื่อติด {{ number_format($total) }} อันดับแรกบนกระดาน</p></div>
    </section>
@endif

{{-- ── โพเดียม 3 อันดับแรก (บนจอกว้างเรียง 2-1-3) ────────── --}}
@if($podium)
<ol class="nvm-rk-podium" aria-label="3 อันดับแรก">
    @foreach($podium as $i => $row)
        <li class="nvm-card nvm-rk-pod nvm-rk-pod--{{ $i + 1 }} {{ $row['user']->id === $me->id ? 'is-me' : '' }}">
            <span class="nvm-rk-pod__no nvm-num">{{ $i + 1 }}</span>
            <b class="nvm-rk-pod__name">{{ $row['user']->name }}</b>
            @if($row['rank'])
                <span class="nvm-rk-pod__rank"><x-nova.rank-badge :rank="$row['rank']" size="sm" />{{ $rankName($row['rank']) }}</span>
            @endif
            <span class="nvm-rk-pod__pts nvm-num">{{ number_format((int) $row['points']) }}<small> คะแนน</small></span>
            @if($row['user']->id === $me->id)<span class="nvm-chip nvm-chip--gold">คุณ</span>@endif
        </li>
    @endforeach
</ol>
@endif

<section class="nvm-card nvm-box" aria-labelledby="nvm-lb-all-h">
    <div class="nvm-sec-h"><div><small class="nvm-latin">All members</small><h2 id="nvm-lb-all-h">ผู้นำทั้งหมด</h2></div></div>

    @if($total)
        <div class="nvm-table-wrap">
            <table class="nvm-table nvm-rk-table">
                <thead>
                    <tr><th scope="col">อันดับ</th><th scope="col">สมาชิก</th><th scope="col">ระดับ</th><th scope="col" class="r">คะแนน</th></tr>
                </thead>
                <tbody>
                    @foreach($leaderboard as $i => $row)
                        @php $isMe = $row['user']->id === $me->id; @endphp
                        <tr class="{{ $isMe ? 'is-me' : '' }}" @if($isMe) aria-current="true" @endif>
                            <td><span class="nvm-rk-no nvm-rk-no--{{ $i < 3 ? $i + 1 : 'x' }} nvm-num">{{ $i + 1 }}</span></td>
                            <td>
                                <span class="nvm-rk-who">
                                    <span class="nvm-rk-av" aria-hidden="true">{{ mb_substr((string) $row['user']->name, 0, 1) }}</span>
                                    <b>{{ $row['user']->name }}</b>
                                    @if($isMe)<span class="nvm-chip nvm-chip--gold">คุณ</span>@endif
                                </span>
                            </td>
                            <td>
                                @if($row['rank'])
                                    <span class="nvm-rk-who"><x-nova.rank-badge :rank="$row['rank']" size="sm" />{{ $rankName($row['rank']) }}</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="r"><b class="nvm-num">{{ number_format((int) $row['points']) }}</b></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="nvm-empty">
            <img src="{{ asset('images/nova/deco/lotus.webp') }}" alt="" width="86" height="86" loading="lazy">
            <b>ยังไม่มีสมาชิกบนกระดานผู้นำ</b>
            เริ่มสะสมคะแนนเพื่อเป็นคนแรกบนกระดาน
        </div>
    @endif
</section>

</div>
@endsection
