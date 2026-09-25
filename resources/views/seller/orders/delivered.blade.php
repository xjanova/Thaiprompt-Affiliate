{{--
    หน้าคำสั่งซื้อที่ส่งถึงลูกค้าแล้ว (ธีม V4)
--}}
@extends('layouts.seller-v4')

@section('title', 'ส่งสำเร็จ')

@push('styles')
    @include('seller.partials.v4-styles')
@endpush

@section('content')
<div class="sv4-page">

    <x-seller-v4.header title="ส่งสำเร็จ" subtitle="คำสั่งซื้อที่ส่งถึงลูกค้าเรียบร้อยแล้ว — รายได้เข้ากระเป๋าหลังครบระยะพักเงิน" icon="✅">
        <a href="{{ route('seller.wallet.index') }}" class="tp-btn tp-btn-sm">💰 ดูรายได้</a>
    </x-seller-v4.header>

    @include('seller.orders.partials.shipping-tabs', ['active' => 'delivered'])

    <div class="sv4-stats">
        <x-seller-v4.stat label="ส่งสำเร็จทั้งหมด" :value="number_format($stats['delivered'] ?? 0)" icon="🎉" :color="\App\Support\Seller\SellerUi::OK" hint="คำสั่งซื้อ" />
        <x-seller-v4.stat label="สำเร็จเดือนนี้" :value="number_format($stats['completed_this_month'] ?? 0)" icon="📅" hint="คำสั่งซื้อ" />
    </div>

    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:16px 20px;">
            <div class="sv4-h2">🎉 คำสั่งซื้อที่ส่งสำเร็จ</div>
        </div>

        @if($orders->count() > 0)
            @foreach($orders as $order)
                @include('seller.orders.partials.shipping-card', ['order' => $order, 'mode' => 'delivered'])
            @endforeach
            <div style="padding:14px 18px; border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                {{ $orders->links('vendor.pagination.tp-v4') }}
            </div>
        @else
            <x-seller-v4.empty icon="📭" title="ยังไม่มีคำสั่งซื้อที่ส่งสำเร็จ" text="เมื่อยืนยันส่งถึงลูกค้าแล้ว คำสั่งซื้อจะแสดงที่นี่">
                <a href="{{ route('seller.orders.shipped') }}" class="tp-btn tp-btn-sm">🚚 ดูที่จัดส่งแล้ว</a>
            </x-seller-v4.empty>
        @endif
    </div>
</div>
@endsection
