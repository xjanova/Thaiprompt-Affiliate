{{--
 | สั่งซื้อสำเร็จ — ธีม V4 (frontend-v4)
 | ข้อมูลจาก CheckoutController@success: $order (items.product, shippingAddress)
 | ใช้หลังชำระด้วยกระเป๋าเงิน / เก็บเงินปลายทาง / พร้อมเพย์ที่ยืนยันแล้ว / บัตร
 --}}
@extends('layouts.frontend-v4')

@section('title', 'สั่งซื้อสำเร็จ #'.$order->order_number)

@php
    $osPaid = $order->payment_status === 'paid';
    $osCod = $order->isCod();
    $osShipping = \App\Services\Shop\ShopPresenter::shipping($order);
    $osRider = \App\Services\Shop\ShopPresenter::riderSummary($order);
    $osSiblings = $order->checkout_group
        ? \App\Models\Order::where('user_id', $order->user_id)->where('checkout_group', $order->checkout_group)
            ->where('id', '!=', $order->id)->orderBy('id')->get(['id', 'order_number', 'total_amount', 'payment_status', 'payment_method'])
        : collect();
    $osCanPay = ! $osPaid && ! $osCod && $order->canRetryPayment();
@endphp

@section('content')
<x-theme-v4.shop-kit />
<x-theme-v4.public-header active="orders" />

<main style="flex:1; padding-bottom:40px;">
    <section class="sf-wrap" style="padding-top:26px; max-width:860px; text-align:center;">
        <span style="width:84px; height:84px; margin:0 auto; border-radius:28px; display:grid; place-items:center; font-size:38px; color:var(--on-accent, #fff); background:linear-gradient(135deg, {{ $osPaid || $osCod ? 'var(--sf-ok, #4f9e7e), var(--sf-ok2, #3b8467)' : 'var(--accent1), var(--accent2)' }}); box-shadow:var(--card-shadow); animation:tpPop .5s ease both;">
            <i class="fas {{ $osPaid || $osCod ? 'fa-check' : 'fa-hourglass-half' }}"></i>
        </span>
        <h1 class="sf-h1" style="margin-top:14px;">
            @if($osPaid)
                สั่งซื้อและชำระเงินสำเร็จ
            @elseif($osCod)
                สั่งซื้อสำเร็จ
            @else
                สร้างคำสั่งซื้อแล้ว — รอชำระเงิน
            @endif
        </h1>
        <p class="tp-muted" style="margin:6px 0 0;">หมายเลขคำสั่งซื้อ <strong class="tp-num" style="color:var(--ink);">#{{ $order->order_number }}</strong> · {{ $order->status_label }}</p>
        @if($osCod)
            <div class="sf-note sf-note-info" style="margin:14px auto 0; max-width:560px;"><i class="fas fa-money-bill-wave"></i> เตรียมเงินสด <strong class="tp-num">฿{{ number_format((float) $order->total_amount, 2) }}</strong> จ่ายให้ไรเดอร์เมื่อได้รับสินค้า</div>
        @elseif(! $osPaid)
            <div class="sf-note sf-note-warn" style="margin:14px auto 0; max-width:560px;">คำสั่งซื้อจะเริ่มดำเนินการหลังชำระเงินเรียบร้อย</div>
        @endif
    </section>

    <section class="sf-wrap sf-stack" style="padding-top:20px; max-width:860px;">
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(170px, 1fr)); gap:10px;">
            <div class="tp-card" style="padding:14px;">
                <div class="tp-muted" style="font-size:12px;">วิธีชำระเงิน</div>
                <div style="font-weight:800; color:var(--ink); margin-top:2px;">{{ \App\Support\Shop\PaymentMethod::labelTh($order->payment_method) }}</div>
            </div>
            <div class="tp-card" style="padding:14px;">
                <div class="tp-muted" style="font-size:12px;">สถานะการชำระ</div>
                <div style="font-weight:800; margin-top:2px; color:{{ $osPaid ? 'var(--sf-ok, #4f9e7e)' : 'var(--deep2)' }};">{{ $osCod && ! $osPaid ? 'จ่ายเมื่อรับของ' : \App\Services\Shop\ShopPresenter::paymentStatusLabel($order->payment_status) }}</div>
            </div>
            <div class="tp-card" style="padding:14px;">
                <div class="tp-muted" style="font-size:12px;">การจัดส่ง</div>
                <div style="font-weight:800; color:var(--ink); margin-top:2px;">{{ $order->isRiderDelivery() ? 'ส่งด่วนด้วยไรเดอร์' : ($osShipping ? 'ส่งพัสดุ' : 'สินค้าดิจิทัล') }}</div>
            </div>
            <div class="tp-card" style="padding:14px;">
                <div class="tp-muted" style="font-size:12px;">วันที่สั่งซื้อ</div>
                <div class="tp-num" style="font-weight:800; color:var(--ink); margin-top:2px;">{{ $order->created_at->timezone('Asia/Bangkok')->format('d/m/Y H:i') }}</div>
            </div>
        </div>

        @if($osRider)
            <div class="tp-card" style="display:flex; align-items:center; gap:14px; flex-wrap:wrap;">
                <span class="tp-tile" style="width:48px; height:48px; font-size:20px; background:linear-gradient(135deg, var(--accent2), var(--deep2));"><i class="fas fa-motorcycle"></i></span>
                <div style="flex:1; min-width:200px;">
                    <div style="font-weight:800; color:var(--ink);">ไรเดอร์: {{ $osRider['status_label'] ?? 'รอร้านเรียกไรเดอร์' }}</div>
                    <div class="tp-muted" style="font-size:12.5px;">เมื่อร้านเตรียมของเสร็จ ระบบจะเรียกไรเดอร์ใกล้ร้านมารับ แล้วคุณติดตามได้แบบสด</div>
                </div>
                @if(! empty($osRider['tracking_url']))
                    <a href="{{ $osRider['tracking_url'] }}" class="sf-btn3d is-alt"><i class="fas fa-location-arrow"></i> ติดตามไรเดอร์</a>
                @endif
            </div>
        @endif

        <div class="tp-card sf-stack" style="gap:10px;">
            <div class="tp-section-h">รายการสินค้า</div>
            @foreach($order->items as $item)
                @php $osImg = \App\Services\Shop\ShopPresenter::imageUrl($item->product_image); @endphp
                <div style="display:flex; gap:12px; align-items:center;">
                    <span class="sf-thumb">@if($osImg)<img src="{{ $osImg }}" alt="" loading="lazy">@else 📦 @endif</span>
                    <span style="flex:1; min-width:0;">
                        <span style="display:block; font-weight:700; color:var(--ink); overflow-wrap:anywhere;">{{ $item->product_name }}</span>
                        @if(is_array($item->product_attributes) && $item->product_attributes !== [])
                            <span class="tp-muted" style="display:block; font-size:12px;">
                                @foreach($item->product_attributes as $ak => $av)
                                    @if(is_scalar($av)){{ $ak }}: {{ $av }}@if(! $loop->last), @endif @endif
                                @endforeach
                            </span>
                        @endif
                        <span class="tp-muted" style="font-size:12px;">{{ (int) $item->quantity }} × ฿{{ number_format((float) $item->unit_price, 2) }}</span>
                    </span>
                    <span class="tp-num" style="font-weight:800; color:var(--ink);">฿{{ number_format((float) $item->total, 2) }}</span>
                </div>
            @endforeach
            <div style="border-top:1px solid color-mix(in srgb, var(--ink2) 20%, transparent); padding-top:10px; display:flex; flex-direction:column; gap:6px;">
                <div class="sf-row"><span>ยอดรวมสินค้า</span><strong class="tp-num">฿{{ number_format((float) $order->subtotal, 2) }}</strong></div>
                @if((float) $order->discount_amount > 0)
                    <div class="sf-row"><span>ส่วนลด</span><strong class="tp-num" style="color:var(--sf-ok, #4f9e7e);">-฿{{ number_format((float) $order->discount_amount, 2) }}</strong></div>
                @endif
                <div class="sf-row"><span>ค่าจัดส่ง</span><strong class="tp-num">{{ (float) $order->shipping_fee > 0 ? '฿'.number_format((float) $order->shipping_fee, 2) : 'ฟรี' }}</strong></div>
                @if((float) ($order->tax_amount ?? 0) > 0)
                    <div class="sf-row"><span>ภาษี</span><strong class="tp-num">฿{{ number_format((float) $order->tax_amount, 2) }}</strong></div>
                @endif
                <div class="sf-total"><span style="font-weight:800; color:var(--ink);">ยอดรวมทั้งหมด</span><span class="tp-num">฿{{ number_format((float) $order->total_amount, 2) }}</span></div>
            </div>
        </div>

        @if($osShipping || $order->customer_notes)
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:12px;">
                @if($osShipping)
                    <div class="tp-card">
                        <div class="tp-section-h" style="margin-bottom:8px;"><i class="fas fa-location-dot" style="color:var(--deep1);"></i> ที่อยู่จัดส่ง</div>
                        <div style="font-size:13.5px; line-height:1.65; color:var(--ink2);">
                            <strong style="color:var(--ink);">{{ $osShipping['name'] }}</strong> · {{ $osShipping['phone'] }}<br>
                            {{ $osShipping['full_address'] ?: trim(implode(' ', array_filter([$osShipping['address'], $osShipping['address_line_2'], $osShipping['subdistrict'], $osShipping['district'], $osShipping['province'], $osShipping['postal_code']]))) }}
                        </div>
                    </div>
                @endif
                @if($order->customer_notes)
                    <div class="tp-card">
                        <div class="tp-section-h" style="margin-bottom:8px;"><i class="fas fa-note-sticky" style="color:var(--deep1);"></i> หมายเหตุ</div>
                        <div style="font-size:13.5px; color:var(--ink2); overflow-wrap:anywhere;">{{ $order->customer_notes }}</div>
                    </div>
                @endif
            </div>
        @endif

        @if($osSiblings->isNotEmpty())
            <div class="tp-card sf-stack" style="gap:10px;">
                <div class="tp-section-h"><i class="fas fa-store" style="color:var(--deep1);"></i> คำสั่งซื้ออื่นจากการสั่งครั้งนี้ (แยกตามร้าน)</div>
                @foreach($osSiblings as $sib)
                    <a href="{{ route('orders.show', $sib->id) }}" style="display:flex; align-items:center; gap:10px; flex-wrap:wrap; padding:10px 12px; border-radius:14px; text-decoration:none; background:var(--surf); box-shadow:var(--raise); color:var(--ink);">
                        <strong class="tp-num" style="flex:1;">#{{ $sib->order_number }}</strong>
                        <span class="tp-num">฿{{ number_format((float) $sib->total_amount, 2) }}</span>
                        <span class="tp-pill tp-pill-soft">{{ $sib->payment_status === 'paid' ? 'ชำระแล้ว' : ($sib->payment_method === 'cod' ? 'จ่ายเมื่อรับของ' : 'รอชำระ') }}</span>
                    </a>
                @endforeach
            </div>
        @endif

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:10px;">
            @if($osCanPay)
                <form method="POST" action="{{ route('orders.retry-payment', $order->id) }}" style="margin:0; display:grid;">
                    @csrf
                    <button type="submit" class="sf-btn3d is-alt"><i class="fas fa-credit-card"></i> ชำระเงินตอนนี้</button>
                </form>
            @endif
            <a href="{{ route('orders.show', $order->id) }}" class="sf-btn3d"><i class="fas fa-receipt"></i> ดูรายละเอียดคำสั่งซื้อ</a>
            <a href="{{ route('storefront.index') }}" class="sf-btn3d is-soft"><i class="fas fa-bag-shopping"></i> ช้อปต่อ</a>
        </div>
        <p class="tp-muted" style="margin:0; text-align:center; font-size:12.5px;">มีคำถามเกี่ยวกับคำสั่งซื้อ? <a href="{{ route('contact') }}" style="color:var(--deep1); font-weight:700;">ติดต่อเรา</a></p>
    </section>
</main>

<x-theme-v4.public-footer />
@endsection
