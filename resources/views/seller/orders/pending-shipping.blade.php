{{--
    หน้าคำสั่งซื้อที่รอจัดส่ง (ธีม V4)
    แสดงออเดอร์ที่ชำระแล้ว/เก็บเงินปลายทาง ที่ยังไม่มีเลขพัสดุ — พัสดุ = กรอกเลขพัสดุ · ไรเดอร์ = เรียกไรเดอร์
--}}
@extends('layouts.seller-v4')

@section('title', 'รอจัดส่ง')

@push('styles')
    @include('seller.partials.v4-styles')
@endpush

@section('content')
<div class="sv4-page">

    <x-seller-v4.header title="รอจัดส่ง" subtitle="ออเดอร์ที่พร้อมส่ง — กรอกเลขพัสดุ หรือเรียกไรเดอร์มารับของ" icon="📋" />

    @include('seller.orders.partials.shipping-tabs', ['active' => 'pending'])

    <div class="sv4-stats">
        <x-seller-v4.stat label="รอจัดส่ง" :value="number_format($stats['pending_shipping'] ?? 0)" icon="📦" :color="\App\Support\Seller\SellerUi::WARN" hint="คำสั่งซื้อ" />
        <x-seller-v4.stat label="มูลค่ารวม" :value="'฿' . number_format($stats['total_amount'] ?? 0, 0)" icon="💰" hint="ยอดสั่งซื้อที่รอส่ง" />
    </div>

    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:16px 20px; display:flex; align-items:center; gap:9px;">
            <span style="width:10px; height:10px; border-radius:50%; background:{{ \App\Support\Seller\SellerUi::WARN }}; animation:tpPulse 1.6s ease-in-out infinite;"></span>
            <div class="sv4-h2">คำสั่งซื้อที่รอจัดส่ง</div>
        </div>

        @if($orders->count() > 0)
            @foreach($orders as $order)
                @include('seller.orders.partials.shipping-card', ['order' => $order, 'mode' => 'pending', 'actions' => $orderActions[$order->id] ?? []])
            @endforeach
            <div style="padding:14px 18px; border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                {{ $orders->links('vendor.pagination.tp-v4') }}
            </div>
        @else
            <x-seller-v4.empty icon="✅" title="ไม่มีคำสั่งซื้อที่รอจัดส่ง" text="คุณจัดส่งครบทุกออเดอร์แล้ว เยี่ยมมาก!">
                <a href="{{ route('seller.orders.index') }}" class="tp-btn tp-btn-sm">ดูคำสั่งซื้อทั้งหมด</a>
            </x-seller-v4.empty>
        @endif
    </div>
</div>
@endsection
