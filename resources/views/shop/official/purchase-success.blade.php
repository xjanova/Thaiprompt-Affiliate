{{--
 | ซื้อด้วย Coins สำเร็จ (ร้านค้าทางการ) — ธีม V4 (frontend-v4)
 | ข้อมูลจาก OfficialShopController@purchaseSuccess: $order (items.product) — เป็นของผู้ใช้คนนี้เท่านั้น
 --}}
@extends('layouts.frontend-v4')

@section('title', 'ซื้อสำเร็จ - ร้านค้าทางการ')

@section('content')
<x-theme-v4.shop-kit />
<x-theme-v4.public-header active="orders" />

<main style="flex:1; padding-bottom:40px;">
    <section class="sf-wrap" style="padding-top:28px; max-width:720px; text-align:center;">
        <span style="width:88px; height:88px; margin:0 auto; border-radius:30px; display:grid; place-items:center; font-size:40px; color:var(--on-accent, #fff); background:linear-gradient(135deg, var(--sf-ok, #4f9e7e), var(--sf-ok2, #3b8467)); box-shadow:var(--card-shadow); animation:tpPop .5s ease both;">
            <i class="fas fa-check"></i>
        </span>
        <h1 class="sf-h1" style="margin-top:14px;">ซื้อสำเร็จ!</h1>
        <p class="tp-muted" style="margin:6px 0 0;">ขอบคุณที่ซื้อสินค้าจากร้านค้าทางการ</p>
    </section>

    <section class="sf-wrap sf-stack" style="padding-top:20px; max-width:720px;">
        <div class="tp-card sf-stack" style="gap:12px;">
            <div class="sf-row"><span>เลขที่คำสั่งซื้อ</span><strong class="tp-num">{{ $order->order_number }}</strong></div>
            <div class="sf-row"><span>วันที่</span><strong class="tp-num">{{ $order->created_at->timezone('Asia/Bangkok')->format('d/m/Y H:i') }}</strong></div>

            <div class="tp-section-h" style="margin-top:6px;">รายการสินค้า</div>
            @foreach($order->items as $item)
                @php $psImg = $item->product ? (\App\Services\Shop\ShopPresenter::productImages($item->product)[0] ?? null) : null; @endphp
                <div style="display:flex; align-items:center; gap:12px; padding:10px 12px; border-radius:16px; background:var(--surf); box-shadow:var(--raise);">
                    <span class="sf-thumb" style="width:56px; height:56px;">@if($psImg)<img src="{{ $psImg }}" alt="" loading="lazy">@else 📦 @endif</span>
                    <span style="flex:1; min-width:0;">
                        <span style="display:block; font-weight:700; color:var(--ink); overflow-wrap:anywhere;">{{ $item->product_name }}</span>
                        <span class="tp-muted" style="font-size:12.5px;">× {{ (int) $item->quantity }}</span>
                    </span>
                    <span class="tp-num" style="font-weight:800; color:var(--deep2);"><i class="fas fa-coins"></i> {{ number_format((float) ($item->subtotal_coins ?? ((float) $item->price_coins * (int) $item->quantity)), 0) }}</span>
                </div>
            @endforeach

            <div class="sf-total">
                <span style="font-weight:800; color:var(--ink);">Coins ที่ใช้</span>
                <span class="tp-num" style="color:var(--deep2);"><i class="fas fa-coins"></i> {{ number_format((float) ($order->coins_used ?? 0), 0) }}</span>
            </div>
            <div class="sf-note sf-note-ok"><i class="fas fa-circle-check"></i> คำสั่งซื้อสำเร็จเรียบร้อย</div>
        </div>

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:10px;">
            <a href="{{ route('official-shop.index') }}" class="sf-btn3d"><i class="fas fa-crown"></i> กลับร้านค้าทางการ</a>
            <a href="{{ route('orders.index') }}" class="sf-btn3d is-soft"><i class="fas fa-receipt"></i> ดูประวัติคำสั่งซื้อ</a>
        </div>
        <div style="display:flex; justify-content:center; gap:14px; flex-wrap:wrap; font-size:13px;">
            <a href="{{ route('home') }}" style="color:var(--deep1); text-decoration:none;"><i class="fas fa-house"></i> หน้าหลัก</a>
            <a href="{{ route('user.coin-shop.index') }}" style="color:var(--deep1); text-decoration:none;"><i class="fas fa-coins"></i> Coin Shop</a>
        </div>
    </section>
</main>

<x-theme-v4.public-footer />
@endsection
