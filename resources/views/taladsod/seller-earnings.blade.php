{{--
 | รายได้ร้านตลาดสด (taladsod.seller.earnings) — ธีม V4 (user-v4)
 | Controller: FreshMarket\HomeController@sellerEarnings
 | ตัวแปร: $seller, $periods [today|week|month|all => {label, data{orders, gross, gp, net}}], $daily [14 วัน: {date, label, orders, net}],
 |         $pending {orders, held_net, cod_to_collect}, $gpDebt, $gpRate, $gpFree, $gpFreeUntil (Carbon|null),
 |         $walletBalance, $payouts (WalletTransaction 10), $recentCompleted (ออเดอร์สำเร็จ 15 + items)
 | กราฟ: CSS ล้วน (.tp-bars)
 --}}
@extends('layouts.user-v4')

@section('title', 'รายได้ร้าน · '.$seller->shop_name)

@php
    $ui = \App\Support\TaladsodWebUi::class;
    $maxNet = max(1, (float) collect($daily)->max('net'));
    $sum14 = round((float) collect($daily)->sum('net'), 2);
    $orders14 = (int) collect($daily)->sum('orders');
    $walletUrl = \Illuminate\Support\Facades\Route::has('user.wallet.index') ? route('user.wallet.index') : null;
@endphp

@section('content')
<x-theme-v4.shop-kit />
@include('taladsod.partials.kit')

<div class="ts-scope ts-stack" style="gap:16px;">
    @include('taladsod.partials.seller-nav', ['seller' => $seller, 'active' => 'earnings'])

    <div class="ts-row" style="justify-content:space-between;">
        <div>
            <h1 class="ts-h1">รายได้ร้าน</h1>
            <p class="ts-muted" style="margin:6px 0 0; font-size:13.5px;">นับจากออเดอร์ที่ลูกค้ายืนยันรับของแล้ว (เสร็จสิ้น) · ยอดสุทธิ = ยอดขาย − GP</p>
        </div>
        @if($walletUrl)
            <a href="{{ $walletUrl }}" class="ts-btn3d ts-tone-info"><i class="fas fa-wallet" aria-hidden="true"></i> กระเป๋าเงิน ฿{{ $ui::money($walletBalance) }}</a>
        @endif
    </div>

    @if($gpFree)
        <div class="sf-note sf-note-ok"><i class="fas fa-gift" aria-hidden="true"></i> <b>ฟรี GP ช่วงเปิดตัว</b>{{ $gpFreeUntil ? ' ถึง '.$ui::date($gpFreeUntil, false) : '' }} — ออเดอร์ใหม่ไม่ถูกหักค่าธรรมเนียม</div>
    @endif

    {{-- ════════ สรุปตามช่วงเวลา ════════ --}}
    <div class="ts-grid" style="--ts-min:190px;">
        @foreach($periods as $key => $period)
            <div class="tp-card ts-stat {{ $key === 'today' ? 'ts-tone-gold' : ($key === 'week' ? 'ts-tone-ok' : ($key === 'month' ? 'ts-tone-info' : 'ts-tone-deep')) }}">
                <span class="ic"><i class="fas {{ $key === 'today' ? 'fa-sun' : ($key === 'week' ? 'fa-calendar-week' : ($key === 'month' ? 'fa-calendar' : 'fa-infinity')) }}" aria-hidden="true"></i></span>
                <span class="lbl">{{ $period['label'] }}</span>
                <span class="val">฿{{ $ui::money($period['data']['net']) }}</span>
                <span class="sub">{{ number_format($period['data']['orders']) }} ออเดอร์ · ยอดขาย ฿{{ $ui::money($period['data']['gross']) }} · GP ฿{{ $ui::money($period['data']['gp']) }}</span>
            </div>
        @endforeach
    </div>

    <div class="ts-grid" style="--ts-min:320px; align-items:start;">
        {{-- ════════ กราฟ 14 วัน ════════ --}}
        <section class="tp-card ts-stack" aria-labelledby="se-chart-h">
            <div class="ts-row" style="justify-content:space-between;">
                <h2 id="se-chart-h" class="ts-h2"><i class="fas fa-chart-column" style="color:var(--accent2);" aria-hidden="true"></i> รายได้สุทธิ 14 วันล่าสุด</h2>
                <span class="ts-muted ts-small">รวม ฿{{ $ui::money($sum14) }} · {{ number_format($orders14) }} ออเดอร์</span>
            </div>
            <div class="tp-bars" style="height:190px; gap:6px;" role="img" aria-label="กราฟรายได้สุทธิรายวัน 14 วันล่าสุด รวม {{ $ui::money($sum14) }} บาท">
                @foreach($daily as $day)
                    <div class="col" title="{{ $day['label'] }}: ฿{{ $ui::money($day['net']) }} ({{ $day['orders'] }} ออเดอร์)">
                        <div class="stack">
                            <span class="bar a" style="width:70%; max-width:26px; height:{{ $day['net'] > 0 ? max(3, round($day['net'] / $maxNet * 100)) : 2 }}%; {{ $day['net'] > 0 ? '' : 'opacity:.25;' }}"></span>
                        </div>
                        <span class="lbl">{{ $day['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- ════════ เงินที่กำลังจะได้ ════════ --}}
        <section class="tp-card ts-stack" aria-labelledby="se-pending-h">
            <h2 id="se-pending-h" class="ts-h2"><i class="fas fa-hourglass-half" style="color:var(--accent2);" aria-hidden="true"></i> เงินที่กำลังจะได้</h2>
            <div class="ts-kv"><span>ออเดอร์ที่ยังไม่จบ</span><b>{{ number_format($pending['orders']) }} รายการ</b></div>
            <div class="ts-kv"><span>จ่ายแล้วผ่านกระเป๋าเงิน (ระบบถือไว้)</span><b class="ts-money">฿{{ $ui::money($pending['held_net']) }}</b></div>
            <div class="ts-kv"><span>เงินสดเก็บปลายทาง (ยังไม่ได้รับ)</span><b>฿{{ $ui::money($pending['cod_to_collect']) }}</b></div>
            <p class="ts-help" style="margin:0;">เงินจากกระเป๋าเงินโอนเข้าร้านทันทีที่ลูกค้ายืนยันรับของ (หรือระบบปิดออเดอร์อัตโนมัติหลังส่งถึง)</p>
            @if($gpDebt > 0)
                <div class="sf-note sf-note-warn">GP ค้างชำระ ฿{{ $ui::money($gpDebt) }} — หักจากรายได้ออเดอร์ถัดไปอัตโนมัติ</div>
            @endif
            <div class="ts-kv"><span>อัตรา GP ตอนนี้</span><b>{{ $gpFree ? 'ฟรี' : rtrim(rtrim(number_format((float) $gpRate, 2), '0'), '.').'%' }}</b></div>
        </section>
    </div>

    <div class="ts-grid" style="--ts-min:320px; align-items:start;">
        {{-- ════════ ออเดอร์สำเร็จล่าสุด ════════ --}}
        <section class="tp-card" aria-labelledby="se-orders-h">
            <h2 id="se-orders-h" class="ts-h2" style="margin-bottom:6px;"><i class="fas fa-circle-check" style="color:var(--ts-ok);" aria-hidden="true"></i> ออเดอร์สำเร็จล่าสุด</h2>
            @forelse($recentCompleted as $order)
                @php $first = $order->lineItems()->first(); @endphp
                <a href="{{ route('taladsod.seller.orders.show', $order) }}" class="ts-line" style="text-decoration:none; color:var(--ink); align-items:center;">
                    <span style="flex:1; min-width:0;">
                        <b style="display:block; font-size:13.5px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">#{{ $order->order_number }} · {{ $first?->title }}</b>
                        <span class="ts-muted ts-small">{{ $ui::shortDate($order->completed_at) }} · {{ $ui::paymentShortLabel($order->payment_method) }}</span>
                    </span>
                    <span style="text-align:right;">
                        <b class="ts-num" style="display:block; color:var(--ts-ok);">+฿{{ $ui::money($order->seller_earning) }}</b>
                        <span class="ts-muted ts-small">GP ฿{{ $ui::money($order->platform_fee) }}</span>
                    </span>
                </a>
            @empty
                <div class="ts-empty" style="padding:18px;">
                    <span class="em" aria-hidden="true">🌱</span>
                    <span class="ts-muted">ยังไม่มีออเดอร์ที่เสร็จสิ้น</span>
                </div>
            @endforelse
        </section>

        {{-- ════════ เงินเข้ากระเป๋า ════════ --}}
        <section class="tp-card" aria-labelledby="se-payout-h">
            <h2 id="se-payout-h" class="ts-h2" style="margin-bottom:6px;"><i class="fas fa-money-bill-transfer" style="color:var(--accent2);" aria-hidden="true"></i> เงินเข้ากระเป๋าจากตลาดสด</h2>
            @forelse($payouts as $tx)
                <div class="ts-line" style="align-items:center;">
                    <span class="tp-tile ts-tone-ok" style="width:36px; height:36px; border-radius:12px; font-size:14px; background:linear-gradient(135deg, var(--ts-ok), color-mix(in srgb, var(--ts-ok) 70%, var(--ink)));"><i class="fas fa-arrow-down" aria-hidden="true"></i></span>
                    <span style="flex:1; min-width:0;">
                        <span style="display:block; font-size:13px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $tx->description }}</span>
                        <span class="ts-muted ts-small">{{ $ui::date($tx->created_at) }}</span>
                    </span>
                    <b class="ts-num" style="color:var(--ts-ok);">+฿{{ $ui::money($tx->amount) }}</b>
                </div>
            @empty
                <div class="ts-empty" style="padding:18px;">
                    <span class="em" aria-hidden="true">👛</span>
                    <span class="ts-muted">ยังไม่มีเงินเข้ากระเป๋า — เงินเข้าเมื่อลูกค้าที่จ่ายผ่านกระเป๋าเงินยืนยันรับของ</span>
                </div>
            @endforelse
            @if($walletUrl)
                <a href="{{ $walletUrl }}" class="tp-btn" style="margin-top:10px;"><i class="fas fa-wallet" aria-hidden="true"></i> ดูกระเป๋าเงิน / ถอนเงิน</a>
            @endif
        </section>
    </div>
</div>
@endsection
