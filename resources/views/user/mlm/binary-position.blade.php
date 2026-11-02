{{--
    ตำแหน่งในระบบ Binary (ธีมโนวาเต็ม) — MlmDashboardController::binaryPositionPage
    ตัวแปร: $member, $position (getMemberPosition: parent/position/left_child/right_child/depth),
            $legs[left|right] (members/pv/carried_pv/sales/commission/child), $binaryEarned, $binarySettings, $referralUrl
    🐛 หน้าเดิมอ่าน $position['left_count'/'left_pv'/'left_commission'/...] ที่ไม่มีอยู่จริง → ทุกช่องเป็น 0 เสมอ
    🐛 "ลิงก์วางตำแหน่ง" ซ้าย/ขวาเดิมแนบ ?position= ที่หน้าสมัครไม่ได้อ่าน (ระบบวางตำแหน่งให้อัตโนมัติ)
       จึงเหลือลิงก์แนะนำลิงก์เดียว และบอกตามจริงว่าระบบเป็นผู้เลือกขาให้
--}}
@extends('layouts.user-nova')

@section('title', 'ตำแหน่งในระบบ Binary')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/mlm.css') }}?v={{ filemtime(public_path('theme-nova/member/mlm.css')) }}">@endpush

@php
    $nvR = fn ($name) => \Illuminate\Support\Facades\Route::has($name);
    $baht = fn ($n) => '฿'.number_format((float) $n, 2);

    $leftPv = $legs['left']['pv'];
    $rightPv = $legs['right']['pv'];
    $totalPv = $leftPv + $rightPv;
    $leftPct = $totalPv > 0 ? round($leftPv / $totalPv * 100, 1) : 50;
    $rightPct = round(100 - $leftPct, 1);
    $isBalanced = abs($leftPv - $rightPv) / max($leftPv, $rightPv, 1) <= 0.3;
    $weakPv = min($leftPv, $rightPv);
    $weakSide = $leftPv <= $rightPv ? 'ขาซ้าย' : 'ขาขวา';

    $parent = $position['parent'] ?? null;
    $sideLabel = ['left' => 'ขาซ้าย', 'right' => 'ขาขวา'];
@endphp

@section('content')

<div class="nvm-stack nvm-ml-page">

<section class="nvm-ph" aria-labelledby="nvm-mlb-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            @if($nvR('user.mlm.dashboard'))
                <a class="nvm-ph__back" href="{{ route('user.mlm.dashboard') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>แดชบอร์ด MLM</a>
            @endif
            <div class="nvm-eyebrow nvm-latin">Binary position</div>
            <h1 id="nvm-mlb-h">ตำแหน่งในระบบ Binary</h1>
            <p class="nvm-ph__lead">ยอดสมาชิก PV และรายได้ของขาซ้ายและขาขวา</p>
            <div class="nvm-pills">
                @if($member->binary_position)
                    <span class="nvm-pill nvm-pill--gold"><svg class="nvm-ico"><use href="#nvm-i-pin"/></svg>คุณอยู่{{ $sideLabel[$member->binary_position] ?? '' }}ของผู้วาง</span>
                @else
                    <span class="nvm-pill nvm-pill--gold"><svg class="nvm-ico"><use href="#nvm-i-pin"/></svg>ต้นสายงาน</span>
                @endif
                <span class="nvm-pill nvm-pill--glass">ชั้นที่ <span class="nvm-num">{{ (int) ($position['depth'] ?? 0) }}</span></span>
                @if($parent)
                    <span class="nvm-pill nvm-pill--glass">อยู่ใต้ <span class="nvm-num">{{ $parent->member_code }}</span></span>
                @endif
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/wallet/scale.webp') }}" alt="" width="200" height="200">
    </div>
</section>

<section class="nvm-ml-legs" aria-label="ยอดแต่ละขา">
    @foreach(['left', 'right'] as $side)
        @php $leg = $legs[$side]; @endphp
        <div class="nvm-card nvm-box nvm-ml-leg nvm-ml-leg--{{ $side }}">
            <div class="nvm-sec-h">
                <div><small class="nvm-latin">{{ $side === 'left' ? 'Left leg' : 'Right leg' }}</small><h2>{{ $sideLabel[$side] }}</h2></div>
                <span class="nvm-ml-leg__badge nvm-latin">{{ $side === 'left' ? 'L' : 'R' }}</span>
            </div>
            <dl class="nvm-kv">
                <dt>จำนวนสมาชิก</dt><dd class="nvm-num">{{ number_format($leg['members']) }} คน</dd>
                <dt>PV คงเหลือ</dt><dd class="nvm-num">{{ number_format($leg['pv']) }}</dd>
                <dt>PV ยกยอด</dt><dd class="nvm-num">{{ number_format($leg['carried_pv']) }}</dd>
                <dt>ยอดขายสะสม</dt><dd class="nvm-num">{{ $baht($leg['sales']) }}</dd>
                <dt>คอมมิชชั่นจากขานี้</dt><dd class="nvm-num nvm-ml-in">{{ $baht($leg['commission']) }}</dd>
                <dt>สมาชิกที่อยู่ใต้คุณโดยตรง</dt><dd>@if($leg['child'])<span class="nvm-num">{{ $leg['child']->member_code }}</span>@else<span class="nvm-ml-mute">ยังว่าง</span>@endif</dd>
            </dl>
        </div>
    @endforeach
</section>

<div class="nvm-split">
    <section class="nvm-card nvm-box" aria-labelledby="nvm-mlb-bal-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Balance</small><h2 id="nvm-mlb-bal-h">ความสมดุลของทีม</h2></div></div>
        <div class="nvm-meter__row"><span>ซ้าย <b class="nvm-num">{{ number_format($leftPct, 1) }}%</b></span><span>ขวา <b class="nvm-num">{{ number_format($rightPct, 1) }}%</b></span></div>
        <div class="nvm-ml-balance" role="img" aria-label="สัดส่วน PV ขาซ้าย {{ $leftPct }}% ขาขวา {{ $rightPct }}%" style="--l: {{ $leftPct }}%">
            <i class="is-left"></i><i class="is-right"></i>
        </div>
        @if($isBalanced)
            <div class="nvm-status nvm-gap-t">
                <svg class="nvm-ico"><use href="#nvm-i-check"/></svg>
                <span>ทีมสมดุลดี<small>PV สองขาต่างกันไม่เกิน 30% เหมาะกับการจับคู่ Binary</small></span>
            </div>
        @else
            <div class="nvm-status is-warn nvm-gap-t">
                <svg class="nvm-ico"><use href="#nvm-i-info"/></svg>
                <span>ทีมยังไม่สมดุล<small>เพิ่ม PV ใน{{ $weakSide }} (ขาอ่อน) เพื่อจับคู่ได้มากขึ้น</small></span>
            </div>
        @endif

        <div class="nvm-minis nvm-ml-minis3 nvm-gap-t">
            <div class="nvm-mini"><small>PV ขาอ่อน</small><b class="nvm-num">{{ number_format($weakPv) }}</b></div>
            <div class="nvm-mini"><small>ค่าคอมต่อคู่ ({{ $binarySettings['pairing_type'] }})</small><b class="nvm-num">{{ $baht($binarySettings['pair_commission']) }}</b></div>
            <div class="nvm-mini"><small>รายได้ Binary สะสม</small><b class="nvm-num is-in">{{ $baht($binaryEarned) }}</b></div>
        </div>
    </section>

    <div class="nvm-stack">
        <section class="nvm-card nvm-box" aria-labelledby="nvm-mlb-link-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Invite</small><h2 id="nvm-mlb-link-h">ชวนสมาชิกเข้าทีม</h2></div></div>
            <p class="nvm-desc">ระบบเลือกขาและตำแหน่งว่างให้สมาชิกใหม่อัตโนมัติ ใช้ลิงก์แนะนำนี้ได้ทั้งสองขา</p>
            <div class="nvm-copy nvm-copy--box">
                <code>{{ $referralUrl }}</code>
                <button type="button" x-data @click="nvmCopy(@js($referralUrl), 'คัดลอกลิงก์แนะนำแล้ว')"><svg class="nvm-ico"><use href="#nvm-i-copy"/></svg>คัดลอก</button>
            </div>
        </section>

        <section class="nvm-card nvm-box" aria-labelledby="nvm-mlb-tip-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Tips</small><h2 id="nvm-mlb-tip-h">เคล็ดลับระบบ Binary</h2></div></div>
            <ol class="nvm-steps">
                <li>รักษาความสมดุล เพิ่มสมาชิกทั้งสองขาให้ใกล้เคียงกัน</li>
                <li>โบนัสจับคู่คิดจาก PV ของขาที่น้อยกว่า</li>
                <li>PV ส่วนเกินของขาแข็งยกยอดไปรอบถัดไปตามระยะที่กำหนด</li>
                <li>ไม่ใช่แค่จำนวนคน PV ของทีมสำคัญเช่นกัน</li>
            </ol>
        </section>
    </div>
</div>

</div>
@endsection
