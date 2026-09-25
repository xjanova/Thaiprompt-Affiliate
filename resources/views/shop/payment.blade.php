{{--
 | ชำระเงินของคำสั่งซื้อ (เลือกวิธีไว้แล้ว) — ธีม V4 (frontend-v4)
 | ข้อมูลจาก CheckoutController@payment: $order (items.product, shippingAddress, paymentTransaction), $transaction, $walletBalance
 | ฟอร์ม POST checkout.payment.process (ไม่มีฟิลด์อื่น — วิธีจ่ายอ่านจากออเดอร์)
 --}}
@extends('layouts.frontend-v4')

@section('title', 'ชำระเงิน #'.$order->order_number)

@php
    $pmMethod = \App\Support\Shop\PaymentMethod::normalize((string) $order->payment_method);
    $pmTotal = (float) $order->total_amount;
    $pmWallet = (float) ($walletBalance ?? 0);
    $pmShort = $pmMethod === 'wallet' && $pmWallet + 0.0001 < $pmTotal;
    $pmShipping = \App\Services\Shop\ShopPresenter::shipping($order);
    $pmExplain = (array) config('smschecker.customer_explanation', []);
    $pmLabels = [
        'wallet' => ['กระเป๋าเงินไทยพร๊อมท์', 'fa-wallet'],
        'promptpay' => ['พร้อมเพย์ (สแกน QR)', 'fa-qrcode'],
        'credit_card' => ['บัตรเครดิต / เดบิต', 'fa-credit-card'],
        'bank_transfer' => ['โอนผ่านธนาคาร', 'fa-building-columns'],
        'cod' => ['เก็บเงินปลายทาง', 'fa-money-bill-wave'],
        'paysolutions' => ['PaySolutions', 'fa-credit-card'],
    ];
    [$pmLabel, $pmIcon] = $pmLabels[$pmMethod] ?? [\App\Support\Shop\PaymentMethod::labelTh($pmMethod), 'fa-money-check'];
@endphp

@section('content')
<x-theme-v4.shop-kit />
<x-theme-v4.public-header active="orders" />

<main style="flex:1; padding-bottom:40px;">
    <section class="sf-wrap" style="padding-top:20px; max-width:980px;">
        <nav class="sf-breadcrumb" aria-label="เส้นทาง">
            <a href="{{ route('orders.index') }}">คำสั่งซื้อของฉัน</a>
            <span aria-hidden="true">/</span>
            <a href="{{ route('orders.show', $order->id) }}">#{{ $order->order_number }}</a>
            <span aria-hidden="true">/</span>
            <span style="color:var(--ink); font-weight:600;">ชำระเงิน</span>
        </nav>
        <h1 class="sf-h1" style="margin-top:8px;">ชำระเงิน</h1>
        <p class="tp-muted" style="margin:6px 0 0;">คำสั่งซื้อ #{{ $order->order_number }} · ยอดชำระ <strong class="tp-num" style="color:var(--ink);">฿{{ number_format($pmTotal, 2) }}</strong></p>
    </section>

    @if(session('error'))
        <section class="sf-wrap" style="padding-top:14px; max-width:980px;">
            <div class="sf-note sf-note-err" role="alert"><i class="fas fa-circle-exclamation"></i> {{ session('error') }}</div>
        </section>
    @endif

    <section class="sf-wrap" style="padding-top:16px; max-width:980px;">
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(min(100%, 340px), 1fr)); gap:16px; align-items:start;">
            <div class="tp-card sf-stack" style="gap:14px;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <span class="tp-tile" style="width:48px; height:48px; border-radius:16px; font-size:20px;"><i class="fas {{ $pmIcon }}"></i></span>
                    <div>
                        <div class="tp-muted" style="font-size:12px;">วิธีชำระเงิน</div>
                        <div style="font-weight:800; color:var(--ink);">{{ $pmLabel }}</div>
                    </div>
                </div>

                @if($pmMethod === 'cod')
                    <div class="sf-note sf-note-info">
                        <strong>ชำระเงินสดเมื่อได้รับสินค้า</strong><br>
                        ยอดที่ต้องจ่ายปลายทาง <strong class="tp-num">฿{{ number_format($pmTotal, 2) }}</strong> — ไม่ต้องทำรายการเพิ่มที่หน้านี้
                    </div>
                    <a href="{{ route('orders.show', $order->id) }}" class="sf-btn3d is-block">ดูสถานะคำสั่งซื้อ</a>
                @else
                    <form method="POST" action="{{ route('checkout.payment.process', $order->id) }}" class="sf-stack" style="gap:12px;"
                          x-data="{ busy: false }" @submit="if (busy) { $event.preventDefault(); } busy = true">
                        @csrf

                        @if($pmMethod === 'wallet')
                            <div style="padding:14px; border-radius:16px; background:var(--surf); box-shadow:var(--inset-sm);">
                                <div class="sf-row"><span>ยอดคงเหลือในกระเป๋า</span><strong class="tp-num">฿{{ number_format($pmWallet, 2) }}</strong></div>
                                <div class="sf-row" style="margin-top:6px;"><span>ยอดที่ต้องชำระ</span><strong class="tp-num">฿{{ number_format($pmTotal, 2) }}</strong></div>
                                <div class="sf-row" style="margin-top:6px;"><span>คงเหลือหลังชำระ</span><strong class="tp-num" style="color:{{ $pmShort ? 'var(--sf-sale, #e0564f)' : 'var(--sf-ok, #4f9e7e)' }};">฿{{ number_format($pmWallet - $pmTotal, 2) }}</strong></div>
                            </div>
                            @if($pmShort)
                                <div class="sf-note sf-note-err">ยอดเงินในกระเป๋าไม่เพียงพอ กรุณาเติมเงินก่อนชำระ</div>
                                <a href="{{ route('user.wallet.topup') }}" class="sf-btn3d is-alt is-block"><i class="fas fa-plus"></i> เติมเงินกระเป๋า</a>
                                <button type="submit" class="sf-btn3d is-block is-disabled" disabled>ยอดเงินไม่เพียงพอ</button>
                            @else
                                <button type="submit" class="sf-btn3d is-block" style="min-height:54px;" :disabled="busy"><i class="fas" :class="busy ? 'fa-spinner fa-spin' : 'fa-lock'"></i> ยืนยันชำระ ฿{{ number_format($pmTotal, 2) }}</button>
                            @endif
                        @elseif($pmMethod === 'promptpay')
                            <div style="text-align:center; padding:16px; border-radius:16px; background:var(--surf); box-shadow:var(--inset-sm);">
                                <div class="tp-muted" style="font-size:12.5px;">ยอดที่ต้องชำระ</div>
                                <div class="tp-num" style="font-size:32px; font-weight:800; color:var(--deep1);">฿{{ number_format($pmTotal, 2) }}</div>
                            </div>
                            <div class="sf-note sf-note-info" style="font-size:12.5px;">
                                <strong>💡 {{ $pmExplain['title'] ?? 'ทำไมยอดโอนมีจุดทศนิยม?' }}</strong><br>
                                {{ $pmExplain['note'] ?? 'กรุณาโอนตามยอดที่แสดงทุกประการ (รวมจุดทศนิยม) เพื่อให้ระบบยืนยันอัตโนมัติ ไม่ต้องรอแอดมิน' }}
                            </div>
                            <button type="submit" class="sf-btn3d is-block" style="min-height:54px;" :disabled="busy"><i class="fas" :class="busy ? 'fa-spinner fa-spin' : 'fa-qrcode'"></i> สร้าง QR พร้อมเพย์</button>
                        @elseif($pmMethod === 'credit_card')
                            <div class="sf-row" style="padding:14px; border-radius:16px; background:var(--surf); box-shadow:var(--inset-sm);"><span>ยอดที่ต้องชำระ</span><strong class="tp-num" style="font-size:22px; color:var(--deep1);">฿{{ number_format($pmTotal, 2) }}</strong></div>
                            <p class="tp-muted" style="margin:0; font-size:12.5px;"><i class="fas fa-shield-halved" style="color:var(--sf-ok, #4f9e7e);"></i> ชำระผ่านหน้าที่ปลอดภัยของ Stripe — เราไม่เก็บเลขบัตรของคุณ · Visa / Mastercard / JCB</p>
                            <button type="submit" class="sf-btn3d is-block" style="min-height:54px;" :disabled="busy"><i class="fas" :class="busy ? 'fa-spinner fa-spin' : 'fa-credit-card'"></i> ไปชำระด้วยบัตรอย่างปลอดภัย</button>
                        @elseif($pmMethod === 'bank_transfer')
                            <div class="sf-note sf-note-info" style="font-size:12.5px;">
                                <strong>💡 {{ $pmExplain['title'] ?? 'ทำไมยอดโอนมีจุดทศนิยม?' }}</strong><br>
                                {{ $pmExplain['note'] ?? 'กรุณาโอนตามยอดที่แสดงทุกประการ (รวมจุดทศนิยม) เพื่อให้ระบบยืนยันอัตโนมัติ ไม่ต้องรอแอดมิน' }}
                            </div>
                            <button type="submit" class="sf-btn3d is-block" style="min-height:54px;" :disabled="busy"><i class="fas" :class="busy ? 'fa-spinner fa-spin' : 'fa-building-columns'"></i> ดูเลขบัญชีและยอดโอน</button>
                        @else
                            <button type="submit" class="sf-btn3d is-block" style="min-height:54px;" :disabled="busy"><i class="fas fa-lock"></i> ดำเนินการชำระเงิน</button>
                        @endif
                    </form>
                @endif

                <a href="{{ route('orders.show', $order->id) }}" class="tp-btn" style="text-decoration:none; height:46px;"><i class="fas fa-arrow-left"></i> กลับไปดูคำสั่งซื้อ</a>
            </div>

            <div class="tp-card sf-stack" style="gap:12px;">
                <div class="tp-section-h">สรุปคำสั่งซื้อ</div>
                @foreach($order->items as $item)
                    <div style="display:flex; gap:10px; align-items:center;">
                        <span class="sf-thumb" style="width:52px; height:52px;">
                            @php $pmImg = \App\Services\Shop\ShopPresenter::imageUrl($item->product_image); @endphp
                            @if($pmImg)<img src="{{ $pmImg }}" alt="" loading="lazy">@else 📦 @endif
                        </span>
                        <span style="flex:1; min-width:0;">
                            <span style="display:block; font-size:13px; font-weight:600; color:var(--ink); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $item->product_name }}</span>
                            <span class="tp-muted" style="font-size:12px;">× {{ (int) $item->quantity }}</span>
                        </span>
                        <span class="tp-num" style="font-weight:700; font-size:13px;">฿{{ number_format((float) $item->total, 2) }}</span>
                    </div>
                @endforeach
                <div class="sf-row"><span>ยอดรวมสินค้า</span><strong class="tp-num">฿{{ number_format((float) $order->subtotal, 2) }}</strong></div>
                <div class="sf-row"><span>ค่าจัดส่ง</span><strong class="tp-num">{{ (float) $order->shipping_fee > 0 ? '฿'.number_format((float) $order->shipping_fee, 2) : 'ฟรี' }}</strong></div>
                @if((float) $order->discount_amount > 0)
                    <div class="sf-row"><span>ส่วนลด</span><strong class="tp-num" style="color:var(--sf-ok, #4f9e7e);">-฿{{ number_format((float) $order->discount_amount, 2) }}</strong></div>
                @endif
                <div class="sf-total"><span style="font-weight:800; color:var(--ink);">รวมทั้งหมด</span><span class="tp-num">฿{{ number_format($pmTotal, 2) }}</span></div>

                <div style="padding-top:10px; border-top:1px solid color-mix(in srgb, var(--ink2) 20%, transparent);">
                    <div style="font-weight:700; color:var(--ink); margin-bottom:6px;"><i class="fas fa-location-dot" style="color:var(--deep1);"></i> ที่อยู่จัดส่ง</div>
                    @if($pmShipping)
                        <div style="font-size:13px; line-height:1.6; color:var(--ink2);">
                            <strong style="color:var(--ink);">{{ $pmShipping['name'] }}</strong> · {{ $pmShipping['phone'] }}<br>
                            {{ $pmShipping['full_address'] ?: trim(implode(' ', array_filter([$pmShipping['address'], $pmShipping['address_line_2'], $pmShipping['subdistrict'], $pmShipping['district'], $pmShipping['province'], $pmShipping['postal_code']]))) }}
                        </div>
                    @else
                        <div class="tp-muted" style="font-size:13px;"><i class="fas fa-cloud-arrow-down"></i> สินค้าดิจิทัล — ไม่ต้องจัดส่ง</div>
                    @endif
                </div>

                @if($transaction)
                    <div class="tp-muted" style="font-size:11.5px; display:flex; flex-direction:column; gap:3px;">
                        <span>เลขรายการ: <span class="tp-num">{{ $transaction->transaction_id }}</span></span>
                        @if($transaction->expired_at)
                            <span>หมดอายุ: {{ $transaction->expired_at->timezone('Asia/Bangkok')->format('d/m/Y H:i') }} น.</span>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </section>
</main>

<x-theme-v4.public-footer />
@endsection
