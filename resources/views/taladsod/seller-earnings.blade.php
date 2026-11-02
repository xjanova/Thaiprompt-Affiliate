{{--
 | รายได้ร้านตลาดสด (taladsod.seller.earnings) — ธีมโนวาเต็ม (layouts.user-nova)
 | Controller: FreshMarket\HomeController@sellerEarnings
 | ตัวแปร: $seller, $periods [today|week|month|all => {label, data{orders, gross, gp, net}}], $daily [14 วัน: {date, label, orders, net}],
 |         $pending {orders, held_net, cod_to_collect}, $gpDebt, $gpRate, $gpFree, $gpFreeUntil (Carbon|null),
 |         $walletBalance, $payouts (WalletTransaction 10), $recentCompleted (ออเดอร์สำเร็จ 15 + items)
 | กราฟ: CSS ล้วน (.nvm-bars — ความสูงส่งผ่านตัวแปร --h)
 --}}
@extends('layouts.user-nova')

@section('title', 'รายได้ร้าน · '.$seller->shop_name)

@php
    $ui = \App\Support\TaladsodWebUi::class;
    $maxNet = max(1, (float) collect($daily)->max('net'));
    $sum14 = round((float) collect($daily)->sum('net'), 2);
    $orders14 = (int) collect($daily)->sum('orders');
    $walletUrl = \Illuminate\Support\Facades\Route::has('user.wallet.index') ? route('user.wallet.index') : null;
    $periodArt = [
        'today' => 'images/nova/deco/coins.webp',
        'week' => 'images/nova/wallet/deposit.webp',
        'month' => 'images/nova/deco/scroll.webp',
        'all' => 'images/nova/earn/store.webp',
    ];
    $gpLabel = rtrim(rtrim(number_format((float) $gpRate, 2), '0'), '.');
@endphp

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/taladsod.css') }}?v={{ filemtime(public_path('theme-nova/member/taladsod.css')) }}">@endpush

@section('content')

<div class="nvm-stack">

<section class="nvm-ph" aria-labelledby="nvm-td-se-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <div class="nvm-eyebrow nvm-latin">Shop earnings</div>
            <h1 id="nvm-td-se-h">รายได้ร้าน</h1>
            <p class="nvm-ph__lead">นับจากออเดอร์ที่ลูกค้ายืนยันรับของแล้ว (เสร็จสิ้น) · ยอดสุทธิ = ยอดขาย − GP</p>
            @if($walletUrl)
                <div class="nvm-hero__actions nvm-td-acts">
                    <a class="nvm-btn nvm-btn--gold" href="{{ $walletUrl }}"><svg class="nvm-ico"><use href="#nvm-i-wallet"/></svg>กระเป๋าเงิน ฿{{ $ui::money($walletBalance) }}</a>
                </div>
            @endif
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/earn/store.webp') }}" alt="" width="200" height="200">
    </div>
</section>

@include('taladsod.partials.seller-nav', ['seller' => $seller, 'active' => 'earnings'])

@if($gpFree)
    <section class="nvm-card nvm-alert nvm-alert--ok" role="status">
        <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-gift"/></svg></span>
        <div class="nvm-alert__txt"><b>ฟรี GP ช่วงเปิดตัว{{ $gpFreeUntil ? ' ถึง '.$ui::date($gpFreeUntil, false) : '' }}</b><p>ออเดอร์ใหม่ไม่ถูกหักค่าธรรมเนียม</p></div>
    </section>
@endif

{{-- ════════ สรุปตามช่วงเวลา ════════ --}}
<section class="nvm-kpis" aria-label="สรุปรายได้ตามช่วงเวลา">
    @foreach($periods as $key => $period)
        <div class="nvm-card nvm-kpi">
            <img class="nvm-kpi__img" src="{{ asset($periodArt[$key] ?? 'images/nova/deco/coins.webp') }}" alt="" width="78" height="78" loading="lazy">
            <div class="nvm-kpi__lbl">{{ $period['label'] }}</div>
            <div class="nvm-kpi__val nvm-num">฿{{ $ui::money($period['data']['net']) }}</div>
            <div class="nvm-kpi__foot"><small>{{ number_format($period['data']['orders']) }} ออเดอร์ · ยอดขาย ฿{{ $ui::money($period['data']['gross']) }} · GP ฿{{ $ui::money($period['data']['gp']) }}</small></div>
        </div>
    @endforeach
</section>

<div class="nvm-split">
    {{-- ════════ กราฟ 14 วัน ════════ --}}
    <section class="nvm-card nvm-chart" aria-labelledby="se-chart-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Last 14 days</small><h2 id="se-chart-h">รายได้สุทธิ 14 วันล่าสุด</h2></div></div>
        <div class="nvm-chart__sum"><b class="nvm-num">฿{{ $ui::money($sum14) }}</b><span>รวม {{ number_format($orders14) }} ออเดอร์</span></div>
        <div class="nvm-bars nvm-td-bars" role="img" aria-label="กราฟรายได้สุทธิรายวัน 14 วันล่าสุด รวม {{ $ui::money($sum14) }} บาท">
            @foreach($daily as $i => $day)
                @php $h = $day['net'] > 0 ? max(3, round($day['net'] / $maxNet * 100)) : 2; @endphp
                <div class="nvm-bar {{ $day['net'] > 0 ? '' : 'is-zero' }} {{ $loop->last ? 'is-now' : '' }}" style="--h: {{ $h }}%; --d: {{ $i * 40 }}ms" title="{{ $day['label'] }}: ฿{{ $ui::money($day['net']) }} ({{ $day['orders'] }} ออเดอร์)">
                    <em>฿{{ $ui::money($day['net']) }}</em><i></i><span>{{ $day['label'] }}</span>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ════════ เงินที่กำลังจะได้ ════════ --}}
    <section class="nvm-card nvm-box" aria-labelledby="se-pending-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Coming in</small><h2 id="se-pending-h">เงินที่กำลังจะได้</h2></div></div>
        <dl class="nvm-kv">
            <dt>ออเดอร์ที่ยังไม่จบ</dt><dd class="nvm-num">{{ number_format($pending['orders']) }} รายการ</dd>
            <dt>จ่ายแล้วผ่านกระเป๋าเงิน (ระบบถือไว้)</dt><dd class="nvm-num">฿{{ $ui::money($pending['held_net']) }}</dd>
            <dt>เงินสดเก็บปลายทาง (ยังไม่ได้รับ)</dt><dd class="nvm-num">฿{{ $ui::money($pending['cod_to_collect']) }}</dd>
            <dt>อัตรา GP ตอนนี้</dt><dd>{{ $gpFree ? 'ฟรี' : $gpLabel.'%' }}</dd>
        </dl>
        <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>เงินจากกระเป๋าเงินโอนเข้าร้านทันทีที่ลูกค้ายืนยันรับของ (หรือระบบปิดออเดอร์อัตโนมัติหลังส่งถึง)</p>
        @if($gpDebt > 0)
            <p class="nvm-note is-bad"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>GP ค้างชำระ ฿{{ $ui::money($gpDebt) }} — หักจากรายได้ออเดอร์ถัดไปอัตโนมัติ</p>
        @endif
    </section>
</div>

<div class="nvm-split">
    {{-- ════════ ออเดอร์สำเร็จล่าสุด ════════ --}}
    <section class="nvm-card nvm-box" aria-labelledby="se-orders-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Completed</small><h2 id="se-orders-h">ออเดอร์สำเร็จล่าสุด</h2></div></div>
        @if($recentCompleted->isNotEmpty())
            <div class="nvm-txs nvm-tks">
                @foreach($recentCompleted as $order)
                    @php $first = $order->lineItems()->first(); @endphp
                        <a class="nvm-tx" href="{{ route('taladsod.seller.orders.show', $order) }}">
                            <span class="nvm-tx__ic is-in"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg></span>
                            <span class="nvm-tx__t">
                                <b>#{{ $order->order_number }} · {{ $first?->title }}</b>
                                <small>{{ $ui::shortDate($order->completed_at) }} · {{ $ui::paymentShortLabel($order->payment_method) }}</small>
                            </span>
                            <span class="nvm-tx__r">
                                <span class="nvm-tx__amt is-in nvm-num">+฿{{ $ui::money($order->seller_earning) }}</span>
                                <time>GP ฿{{ $ui::money($order->platform_fee) }}</time>
                            </span>
                        </a>
                @endforeach
            </div>
        @else
            <div class="nvm-empty">
                <img src="{{ asset('images/nova/deco/lotus.webp') }}" alt="" width="86" height="86" loading="lazy">
                <b>ยังไม่มีออเดอร์ที่เสร็จสิ้น</b>
                เปิดร้านแล้วรับออเดอร์แรก รายได้จะเริ่มขึ้นที่นี่
            </div>
        @endif
    </section>

    {{-- ════════ เงินเข้ากระเป๋า ════════ --}}
    <section class="nvm-card nvm-box" aria-labelledby="se-payout-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Payouts</small><h2 id="se-payout-h">เงินเข้ากระเป๋าจากตลาดสด</h2></div></div>
        @if($payouts->isNotEmpty())
            <ul class="nvm-txs">
                @foreach($payouts as $tx)
                    <li class="nvm-tx">
                        <span class="nvm-tx__ic is-in"><svg class="nvm-ico"><use href="#nvm-i-down"/></svg></span>
                        <span class="nvm-tx__t"><b>{{ $tx->description }}</b><small>{{ $ui::date($tx->created_at) }}</small></span>
                        <span class="nvm-tx__r"><span class="nvm-tx__amt is-in nvm-num">+฿{{ $ui::money($tx->amount) }}</span></span>
                    </li>
                @endforeach
            </ul>
        @else
            <div class="nvm-empty">
                <img src="{{ asset('images/nova/wallet/empty.webp') }}" alt="" width="86" height="86" loading="lazy">
                <b>ยังไม่มีเงินเข้ากระเป๋า</b>
                เงินเข้าเมื่อลูกค้าที่จ่ายผ่านกระเป๋าเงินยืนยันรับของ
            </div>
        @endif
        @if($walletUrl)
            <a href="{{ $walletUrl }}" class="nvm-btn nvm-btn--line nvm-gap-t"><svg class="nvm-ico"><use href="#nvm-i-wallet"/></svg>ดูกระเป๋าเงิน / ถอนเงิน</a>
        @endif
    </section>
</div>

</div>
@endsection
