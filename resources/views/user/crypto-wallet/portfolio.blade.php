{{--
    พอร์ตคริปโตของฉัน (ธีมโนวาเต็ม)
    CryptoWalletController::portfolio → $wallet · $balances (code => currency/balance/balance_thb) · $totalValueTHB · $recentTransactions (20 รายการล่าสุด) · $totalTransactions (จำนวนทั้งหมดจริง)
    แสดงเฉพาะตัวเลขจริงจากยอดคงเหลือ — เดิมมีกำไร/ขาดทุนปลอม (ต้นทุน = มูลค่า × 0.85) และกราฟสุ่มด้วย rand()
    และหน้าแตก 500 (string % int) — ระบบยังไม่เก็บต้นทุนจึงไม่แสดงกำไร/ขาดทุน
--}}
@extends('layouts.user-nova')

@section('title', 'พอร์ตคริปโตของฉัน')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/crypto-wallet.css') }}?v={{ filemtime(public_path('theme-nova/member/crypto-wallet.css')) }}">@endpush

@php
    $total = (float) $totalValueTHB;
    $holdings = collect($balances)->filter(fn ($b) => (float) $b['balance'] > 0)->sortByDesc(fn ($b) => (float) $b['balance_thb']);
    $share = fn ($b) => $total > 0 ? round(((float) $b['balance_thb'] / $total) * 100, 1) : 0;
    $nvR = fn ($name) => \Illuminate\Support\Facades\Route::has($name);
    $tiles = array_filter([
        ['images/nova/wallet/deposit.webp', 'ฝากเหรียญ', 'Deposit', 'user.crypto-wallet.deposit'],
        ['images/nova/wallet/withdraw.webp', 'ถอนเหรียญ', 'Withdraw', 'user.crypto-wallet.withdraw'],
        ['images/nova/wallet/transfer.webp', 'แลกเปลี่ยน', 'Exchange', 'user.crypto-wallet.exchange'],
        ['images/nova/deco/crystal.webp', 'ราคาตลาด', 'Markets', 'user.crypto-wallet.trading'],
    ], fn ($t) => $nvR($t[3]));
    $kpis = [
        ['มูลค่ารวม (ประมาณ)', 'Total value', '฿'.number_format($total, 2), 'images/nova/deco/coins.webp'],
        ['เหรียญที่ถืออยู่', 'Holdings', number_format($holdings->count()).' สกุล', 'images/nova/wallet/card.webp'],
        ['ธุรกรรมทั้งหมด', 'Transactions', number_format($totalTransactions).' รายการ', 'images/nova/deco/scroll.webp'],
    ];
@endphp

@section('content')

<div class="nvm-stack">

<section class="nvm-ph" aria-labelledby="nvm-cwp-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.crypto-wallet.index') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>กระเป๋าคริปโต</a>
            <div class="nvm-eyebrow nvm-latin">Portfolio</div>
            <h1 id="nvm-cwp-h">พอร์ตของฉัน</h1>
            <div class="nvm-bal">
                <small>มูลค่ารวมทั้งหมด (ประมาณ)</small>
                <b class="nvm-foil nvm-num">฿{{ number_format($total, 2) }}</b>
            </div>
            <p class="nvm-ph__lead">คำนวณจากยอดคงเหลือจริงกับราคาล่าสุด</p>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/wallet/card.webp') }}" alt="" width="200" height="200">
    </div>
</section>

@include('user.crypto-wallet.partials.flash')

<section class="nvm-kpis" aria-label="สรุปพอร์ต">
    @foreach($kpis as [$label, $en, $value, $img])
        <div class="nvm-card nvm-kpi">
            <img class="nvm-kpi__img" src="{{ asset($img) }}" alt="" width="78" height="78" loading="lazy">
            <div class="nvm-kpi__lbl">{{ $label }}</div>
            <div class="nvm-kpi__en nvm-latin">{{ $en }}</div>
            <div class="nvm-kpi__val nvm-num">{{ $value }}</div>
        </div>
    @endforeach
</section>

<div class="nvm-split">
    <section class="nvm-card nvm-box" aria-labelledby="nvm-cwp-al-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Allocation</small><h2 id="nvm-cwp-al-h">สัดส่วนสินทรัพย์</h2></div></div>
        @if($holdings->isNotEmpty())
            <div class="nvm-cw-alloc">
                @foreach($holdings as $code => $b)
                    @php $pct = $share($b); @endphp
                    <div class="nvm-meter">
                        <div class="nvm-meter__row">
                            <span class="nvm-cw-cell">@include('user.crypto-wallet.partials.coin', ['code' => $code])<span><b>{{ $code }}</b><small>{{ $b['currency']->name }}</small></span></span>
                            <b class="nvm-num">{{ number_format($pct, 1) }}%</b>
                        </div>
                        <div class="nvm-meter__bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $pct }}" aria-label="สัดส่วน {{ $code }}"><i style="--w: {{ $pct }}%"></i></div>
                    </div>
                @endforeach
            </div>

            <div class="nvm-table-wrap nvm-gap-t">
                <table class="nvm-table nvm-cw-table">
                    <thead>
                        <tr>
                            <th scope="col">สินทรัพย์</th>
                            <th scope="col" class="r">ยอดคงเหลือ</th>
                            <th scope="col" class="r">มูลค่า (บาท)</th>
                            <th scope="col" class="r">สัดส่วน</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($holdings as $code => $b)
                            <tr>
                                <td><div class="nvm-cw-cell">@include('user.crypto-wallet.partials.coin', ['code' => $code])<div><b>{{ $code }}</b><small>{{ $b['currency']->name }}</small></div></div></td>
                                <td class="r nvm-num">{{ number_format((float) $b['balance'], 8) }}</td>
                                <td class="r nvm-num">฿{{ number_format((float) $b['balance_thb'], 2) }}</td>
                                <td class="r nvm-num">{{ number_format($share($b), 1) }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="nvm-empty">
                <img src="{{ asset('images/nova/wallet/empty.webp') }}" alt="" width="86" height="86" loading="lazy">
                <b>ยังไม่มีเหรียญในพอร์ต</b>
                ฝากหรือซื้อเหรียญเพื่อเริ่มสร้างพอร์ต
            </div>
        @endif
        <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>ระบบยังไม่บันทึกต้นทุนการซื้อ จึงยังไม่แสดงกำไร/ขาดทุน</p>
    </section>

    <aside class="nvm-stack">
        @if(count($tiles))
            <nav class="nvm-tiles" aria-label="ทางลัดพอร์ต">
                @foreach($tiles as [$img, $label, $en, $route])
                    <a class="nvm-card nvm-tile" href="{{ route($route) }}">
                        <img src="{{ asset($img) }}" alt="" width="76" height="76" loading="lazy">
                        <b>{{ $label }}</b>
                        <small class="nvm-latin">{{ $en }}</small>
                    </a>
                @endforeach
            </nav>
        @endif

        <section class="nvm-card nvm-box" aria-labelledby="nvm-cwp-tx-h">
            <div class="nvm-sec-h">
                <div><small class="nvm-latin">Activity</small><h2 id="nvm-cwp-tx-h">ธุรกรรมล่าสุด</h2></div>
                <a href="{{ route('user.crypto-wallet.transactions') }}">ทั้งหมด<svg class="nvm-ico"><use href="#nvm-i-chev"/></svg></a>
            </div>
            @if($recentTransactions->isNotEmpty())
                <ul class="nvm-txs">
                    @foreach($recentTransactions as $tx)
                        @include('user.crypto-wallet.partials.tx-row', ['tx' => $tx])
                    @endforeach
                </ul>
            @else
                <div class="nvm-empty"><b>ยังไม่มีธุรกรรม</b></div>
            @endif
        </section>
    </aside>
</div>

</div>
@endsection
