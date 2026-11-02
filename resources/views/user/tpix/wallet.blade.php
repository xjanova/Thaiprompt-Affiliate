{{--
    กระเป๋า TPIX ของสมาชิก (ธีมโนวาเต็ม)
    - ตัวเลขทุกตัวมาจาก TPIXWalletController::index — เดิมหน้านี้โชว์ราคา/กำไร/Staking ปลอม และรายการค้างหมุนโหลด
    - ไม่แสดงที่อยู่รับฝากที่นี่: ที่อยู่ที่ระบบสุ่มอาจไม่มี private key (หน้าฝากเป็นคนตัดสินว่าแสดงได้หรือไม่)
--}}
@extends('layouts.user-nova')

@section('title', 'กระเป๋า TPIX')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/tpix-tokens.css') }}?v={{ filemtime(public_path('theme-nova/member/tpix-tokens.css')) }}">@endpush

@php
    $nvR = fn ($name) => \Illuminate\Support\Facades\Route::has($name);
    $tpix = fn ($n) => number_format((float) $n, 8);
    $walletActive = $wallet->isActive();
    // ไม่มีอัตราบาทล่าสุดให้ซ่อน ดีกว่าโชว์ 0 ข้างยอดจริง
    $tpixRate = $tpixCurrency->getCachedCurrentRate();

    // เมนูด่วน: [ภาพ, ป้ายไทย, ป้ายอังกฤษ, route]
    $tiles = array_filter([
        ['images/nova/wallet/transfer.webp', 'ส่ง TPIX', 'Send', 'user.tpix.send'],
        ['images/nova/wallet/deposit.webp', 'ฝาก', 'Deposit', 'user.tpix.deposit'],
        ['images/nova/wallet/withdraw.webp', 'ถอน', 'Withdraw', 'user.tpix.withdrawal'],
        ['images/nova/deco/scroll.webp', 'ประวัติ', 'History', 'user.tpix.transactions'],
    ], fn ($t) => $nvR($t[3]));

    $kpis = [
        ['ฝากเข้าแล้ว', 'Received', $tpix($stats['total_received'] ?? 0), 'images/nova/wallet/deposit.webp', 'รายการฝากที่ยืนยันแล้ว'],
        ['ถอนออกแล้ว', 'Withdrawn', $tpix($stats['total_sent'] ?? 0), 'images/nova/wallet/withdraw.webp', 'รายการถอนที่ยืนยันแล้ว'],
        ['ฝากรอยืนยัน', 'Pending in', number_format((int) ($stats['pending_deposits'] ?? 0)), 'images/nova/wallet/hourglass.webp', 'รายการ'],
        ['ถอนรอดำเนินการ', 'Pending out', number_format((int) ($stats['pending_withdrawals'] ?? 0)), 'images/nova/wallet/hourglass.webp', 'รายการ'],
    ];

    $ecosystem = array_filter([
        ['user.dex.swap', 'swap', 'แลกเปลี่ยนเหรียญ', 'DEX Swap'],
        ['user.staking.index', 'award', 'Staking', 'ฝากเหรียญรับรางวัล'],
        ['user.dex.pools', 'grid', 'Liquidity Pools', 'เพิ่มสภาพคล่อง'],
        ['user.tokens.index', 'store', 'ตลาดเหรียญ', 'ดูเหรียญลูกบน TPIX'],
    ], fn ($e) => $nvR($e[0]));

    $blockNumber = is_numeric($networkInfo['blockNumber'] ?? null) ? (int) $networkInfo['blockNumber'] : null;
    $gasGwei = is_numeric($networkInfo['gasPrice'] ?? null) ? $networkInfo['gasPrice'] / 1000000000 : null;
@endphp

@section('content')

<div class="nvm-stack">

<section class="nvm-ph" aria-labelledby="nvm-tpw-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <div class="nvm-eyebrow nvm-latin">TPIX wallet</div>
            <h1 id="nvm-tpw-h">กระเป๋า TPIX</h1>
            <div class="nvm-bal">
                <small>ยอดคงเหลือ</small>
                <b class="nvm-foil nvm-num nvm-tx-hbal">{{ $tpix($wallet->balance) }}</b><em>TPIX</em>
            </div>
            <div class="nvm-pills">
                @if($walletActive)
                    <span class="nvm-pill nvm-pill--ok"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg>พร้อมใช้งาน</span>
                @else
                    <span class="nvm-pill nvm-pill--bad"><svg class="nvm-ico"><use href="#nvm-i-lock"/></svg>กระเป๋าถูกระงับการใช้งาน</span>
                @endif
                @if($tpixRate)
                    <span class="nvm-pill nvm-pill--glass nvm-num">≈ ฿{{ number_format((float) $wallet->balance * (float) $tpixRate->rate_thb, 2) }}</span>
                @endif
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/deco/coins.webp') }}" alt="" width="200" height="200">
    </div>
</section>

@if(count($tiles))
    <nav class="nvm-tiles nvm-tiles--4" aria-label="เมนูกระเป๋า TPIX">
        @foreach($tiles as [$img, $label, $en, $route])
            <a class="nvm-card nvm-tile" href="{{ route($route) }}">
                <img src="{{ asset($img) }}" alt="" width="76" height="76" loading="lazy">
                <b>{{ $label }}</b>
                <small class="nvm-latin">{{ $en }}</small>
            </a>
        @endforeach
    </nav>
@endif

<section class="nvm-kpis" aria-label="สรุปกระเป๋า TPIX">
    @foreach($kpis as [$label, $en, $value, $img, $foot])
        <div class="nvm-card nvm-kpi">
            <img class="nvm-kpi__img" src="{{ asset($img) }}" alt="" width="78" height="78" loading="lazy">
            <div class="nvm-kpi__lbl">{{ $label }}</div>
            <div class="nvm-kpi__en nvm-latin">{{ $en }}</div>
            <div class="nvm-kpi__val nvm-kpi__val--txt nvm-num">{{ $value }}</div>
            <div class="nvm-kpi__foot"><small>{{ $foot }}</small></div>
        </div>
    @endforeach
</section>

<div class="nvm-split">
    <section class="nvm-card nvm-box" aria-labelledby="nvm-tpw-tx-h">
        <div class="nvm-sec-h">
            <div><small class="nvm-latin">Recent activity</small><h2 id="nvm-tpw-tx-h">ธุรกรรมล่าสุด</h2></div>
            <a href="{{ route('user.tpix.transactions') }}">ดูทั้งหมด<svg class="nvm-ico"><use href="#nvm-i-chev"/></svg></a>
        </div>
        @if($recentTransactions->isNotEmpty())
            <div class="nvm-txs nvm-tks">
                @foreach($recentTransactions as $transaction)
                    @include('user.tpix.partials.tx-row')
                @endforeach
            </div>
        @else
            <div class="nvm-empty">
                <img src="{{ asset('images/nova/wallet/empty.webp') }}" alt="" width="86" height="86" loading="lazy">
                <b>ยังไม่มีธุรกรรม</b>
                เมื่อมีการฝาก ส่ง หรือถอน TPIX รายการจะแสดงที่นี่
            </div>
        @endif
    </section>

    <div class="nvm-stack">
        <section class="nvm-card nvm-box" aria-labelledby="nvm-tpw-tk-h">
            <div class="nvm-sec-h">
                <div><small class="nvm-latin">Holdings</small><h2 id="nvm-tpw-tk-h">เหรียญลูกที่ถือครอง</h2></div>
                @if($nvR('user.tokens.index'))
                    <a href="{{ route('user.tokens.index') }}">ตลาดเหรียญ<svg class="nvm-ico"><use href="#nvm-i-chev"/></svg></a>
                @endif
            </div>
            @if($tokenHoldings->isNotEmpty())
                <ul class="nvm-tx-list">
                    @foreach($tokenHoldings as $holding)
                        @php
                            // ลิงก์ไปหน้าเหรียญเฉพาะที่เปิดดูได้ — เหรียญที่ถูกระงับ/ยังไม่ขึ้นเครือข่าย หน้า show ตอบ 404 กับคนที่ไม่ใช่ผู้สร้าง
                            $hToken = $holding->token;
                            $hOpen = $hToken && (in_array($hToken->status, \App\Http\Controllers\User\TokenController::PUBLIC_STATUSES, true)
                                || (int) $hToken->creator_id === (int) auth()->id());
                        @endphp
                        <li>
                            @if($hOpen)
                                <a class="nvm-tx-head" href="{{ route('user.tokens.show', $holding->token_id) }}">
                            @else
                                <div class="nvm-tx-head">
                            @endif
                                <span class="nvm-tx-coin">{{ mb_substr((string) ($hToken?->symbol ?? '?'), 0, 1) }}</span>
                                <div>
                                    <b>{{ $hToken?->name ?? 'เหรียญ' }}</b>
                                    <small>{{ $hToken?->symbol }}</small>
                                    @unless($hOpen)
                                        <span class="nvm-pill nvm-pill--bad">{{ $hToken?->status === 'suspended' ? 'ถูกระงับ' : 'ยังไม่เปิดให้ใช้งาน' }}</span>
                                    @endunless
                                </div>
                            @if($hOpen)
                                </a>
                            @else
                                </div>
                            @endif
                            <span class="nvm-tx-amt nvm-num">{{ number_format((float) $holding->balance, 4) }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="nvm-empty">
                    <b>ยังไม่มีเหรียญลูก</b>
                    เหรียญที่คุณได้รับหรือสร้างเองจะแสดงที่นี่
                </div>
            @endif
        </section>

        @if(count($ecosystem))
            <section class="nvm-card nvm-box" aria-labelledby="nvm-tpw-eco-h">
                <div class="nvm-sec-h"><div><small class="nvm-latin">Ecosystem</small><h2 id="nvm-tpw-eco-h">บริการบน TPIX</h2></div></div>
                <div class="nvm-acts">
                    @foreach($ecosystem as [$route, $icon, $title, $sub])
                        <a class="nvm-act" href="{{ route($route) }}">
                            <span class="nvm-act__ic ok"><svg class="nvm-ico"><use href="#nvm-i-{{ $icon }}"/></svg></span>
                            <span class="nvm-act__t"><b>{{ $title }}</b><small>{{ $sub }}</small></span>
                            <svg class="nvm-ico"><use href="#nvm-i-chev"/></svg>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="nvm-card nvm-box" aria-labelledby="nvm-tpw-net-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Network</small><h2 id="nvm-tpw-net-h">สถานะเครือข่าย</h2></div></div>
            @if($networkInfo)
                <dl class="nvm-kv">
                    <dt>สถานะ</dt><dd><span class="nvm-chip nvm-chip--ok">เชื่อมต่อได้</span></dd>
                    @if($blockNumber !== null)<dt>บล็อกล่าสุด</dt><dd class="nvm-num">{{ number_format($blockNumber) }}</dd>@endif
                    @if($gasGwei !== null)<dt>ค่า Gas</dt><dd class="nvm-num">{{ rtrim(rtrim(number_format($gasGwei, 4), '0'), '.') }} Gwei</dd>@endif
                </dl>
            @else
                <div class="nvm-status is-warn">
                    <svg class="nvm-ico"><use href="#nvm-i-info"/></svg>
                    <span>ขณะนี้เชื่อมต่อเครือข่ายไม่ได้<small>ยอดในกระเป๋ายังถูกต้องตามระบบ</small></span>
                </div>
            @endif
        </section>
    </div>
</div>

</div>
@endsection
