{{--
    หน้าคำสั่งซื้อที่จัดส่งแล้ว (ธีม V4) — มีเลขพัสดุแล้ว รอส่งถึงลูกค้า
--}}
@extends('layouts.seller-v4')

@section('title', 'จัดส่งแล้ว')

@push('styles')
    @include('seller.partials.v4-styles')
@endpush

@section('content')
<div class="sv4-page">

    <x-seller-v4.header title="จัดส่งแล้ว" subtitle="คำสั่งซื้อที่ส่งออกแล้ว รอส่งถึงลูกค้า — อัปเดตสถานะระหว่างทางได้" icon="🚚" />

    @include('seller.orders.partials.shipping-tabs', ['active' => 'shipped'])

    <div class="sv4-stats">
        <x-seller-v4.stat label="กำลังจัดส่ง" :value="number_format($stats['shipped'] ?? 0)" icon="🚚" :color="\App\Support\Seller\SellerUi::VIOLET" hint="คำสั่งซื้อ" />
        <x-seller-v4.stat label="อยู่ระหว่างทาง" :value="number_format($stats['in_transit'] ?? 0)" icon="📍" :color="\App\Support\Seller\SellerUi::INFO" hint="พัสดุ" />
    </div>

    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:16px 20px; display:flex; align-items:center; gap:9px;">
            <span style="width:10px; height:10px; border-radius:50%; background:{{ \App\Support\Seller\SellerUi::VIOLET }}; animation:tpPulse 1.6s ease-in-out infinite;"></span>
            <div class="sv4-h2">คำสั่งซื้อที่กำลังจัดส่ง</div>
        </div>

        @if($orders->count() > 0)
            @foreach($orders as $order)
                @include('seller.orders.partials.shipping-card', ['order' => $order, 'mode' => 'shipped'])
            @endforeach
            <div style="padding:14px 18px; border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                {{ $orders->links('vendor.pagination.tp-v4') }}
            </div>
        @else
            <x-seller-v4.empty icon="📦" title="ไม่มีคำสั่งซื้อที่กำลังจัดส่ง" text="คำสั่งซื้อที่กรอกเลขพัสดุแล้วจะแสดงที่นี่">
                <a href="{{ route('seller.orders.pending-shipping') }}" class="tp-btn tp-btn-sm">📋 ดูรอจัดส่ง</a>
            </x-seller-v4.empty>
        @endif
    </div>
</div>
@endsection
