@extends('layouts.seller-v4')

@section('title', 'จัดการคำสั่งซื้อ')

@push('styles')
    @include('seller.partials.v4-styles')
@endpush

@php
    use App\Support\Seller\SellerUi;

    // แท็บกรองสถานะ (ค่าที่ส่งไปตรงกับ ?status= ที่คอนโทรลเลอร์รองรับ)
    $tabs = [
        '' => 'ทั้งหมด',
        'pending' => 'รอดำเนินการ',
        'paid' => 'ชำระแล้ว',
        'processing' => 'กำลังเตรียม',
        'shipped' => 'จัดส่งแล้ว',
        'delivered' => 'ส่งถึงแล้ว',
        'completed' => 'สำเร็จ',
        'cancelled' => 'ยกเลิก',
    ];
    $currentStatus = (string) request('status', '');
@endphp

@section('content')
<div class="sv4-page">

    <x-seller-v4.header title="จัดการคำสั่งซื้อ" subtitle="ออเดอร์ทั้งหมดที่มีสินค้าของร้านคุณ" icon="🧾">
        <a href="{{ route('seller.orders.pending-shipping') }}" class="tp-btn tp-btn-sm">📋 รอจัดส่ง</a>
        <a href="{{ route('seller.orders.shipped') }}" class="tp-btn tp-btn-sm">🚚 จัดส่งแล้ว</a>
        <a href="{{ route('seller.orders.delivered') }}" class="tp-btn tp-btn-sm">✅ สำเร็จ</a>
    </x-seller-v4.header>

    <div class="sv4-stats">
        <x-seller-v4.stat label="คำสั่งซื้อทั้งหมด" :value="number_format($stats['total'] ?? 0)" icon="📦" />
        <x-seller-v4.stat label="รอดำเนินการ" :value="number_format($stats['pending'] ?? 0)" icon="⏳" :color="SellerUi::WARN"
                          :href="route('seller.orders.index', ['status' => 'pending'])" />
        <x-seller-v4.stat label="กำลังเตรียมสินค้า" :value="number_format($stats['processing'] ?? 0)" icon="🔄" :color="SellerUi::INFO"
                          :href="route('seller.orders.index', ['status' => 'processing'])" />
        <x-seller-v4.stat label="จัดส่งแล้ว" :value="number_format($stats['shipped'] ?? 0)" icon="🚚" :color="SellerUi::VIOLET"
                          :href="route('seller.orders.index', ['status' => 'shipped'])" />
    </div>

    <nav class="sv4-tabs" aria-label="กรองตามสถานะ">
        @foreach($tabs as $value => $label)
            <a href="{{ $value === '' ? route('seller.orders.index') : route('seller.orders.index', ['status' => $value]) }}"
               class="sv4-tab {{ $currentStatus === $value ? 'on' : '' }}">{{ $label }}</a>
        @endforeach
    </nav>

    <div class="tp-card" style="padding:0; overflow:hidden;">
        @if($orders->count() > 0)
            <div class="sv4-table-wrap">
                <table class="sv4-table">
                    <thead>
                        <tr>
                            <th>เลขที่คำสั่งซื้อ</th>
                            <th>ลูกค้า</th>
                            <th class="sv4-hide-sm">สินค้า</th>
                            <th style="text-align:right;">รายได้ร้าน</th>
                            <th style="text-align:center;">สถานะ</th>
                            <th class="sv4-hide-sm" style="text-align:right;">วันที่</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orders as $order)
                            @php
                                $sellerItems = $order->items->where('seller_id', auth()->id());
                                $sellerNet = $sellerItems->sum('seller_earning');
                                $statusColor = SellerUi::orderStatusColor($order->status);
                            @endphp
                            <tr>
                                <td style="white-space:nowrap;">
                                    <a href="{{ route('seller.orders.show', $order) }}" class="sv4-link tp-num">#{{ $order->order_number }}</a>
                                    <div style="display:flex; gap:5px; margin-top:4px; flex-wrap:wrap;">
                                        @if($order->isRiderDelivery())
                                            <span class="sv4-pill" style="{{ SellerUi::pill(SellerUi::INFO) }}">🛵 ไรเดอร์</span>
                                        @endif
                                        @if($order->isCod())
                                            <span class="sv4-pill" style="{{ SellerUi::pill(SellerUi::WARN) }}">💵 ปลายทาง</span>
                                        @endif
                                        @if($order->has_unread_messages)
                                            <span class="sv4-pill" style="{{ SellerUi::pill(SellerUi::BAD) }}">💬 ข้อความใหม่</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:9px; min-width:0;">
                                        <span class="tp-tile" style="width:30px; height:30px; border-radius:9px; font-size:12px; font-weight:800;">{{ mb_substr($order->user->name ?? 'ล', 0, 1) }}</span>
                                        <div style="min-width:0;">
                                            <div style="font-weight:700; overflow-wrap:anywhere;">{{ $order->user->name ?? 'ลูกค้า' }}</div>
                                            <div style="font-size:11px; color:var(--ink2); overflow-wrap:anywhere;">{{ $order->user->phone ?? $order->user->email ?? '' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="sv4-hide-sm">{{ $sellerItems->count() }} รายการ</td>
                                <td class="tp-num" style="text-align:right; white-space:nowrap; font-weight:800;">฿{{ number_format($sellerNet, 2) }}</td>
                                <td style="text-align:center;">
                                    <span class="sv4-pill" style="{{ SellerUi::pill($statusColor) }}">{{ $order->status_label }}</span>
                                </td>
                                <td class="sv4-hide-sm tp-num" style="text-align:right; white-space:nowrap;">
                                    <div>{{ $order->created_at->format('d/m/Y') }}</div>
                                    <div style="font-size:11px; color:var(--ink2);">{{ $order->created_at->format('H:i') }}</div>
                                </td>
                                <td style="text-align:right;">
                                    <a href="{{ route('seller.orders.show', $order) }}" class="tp-btn tp-btn-sm">ดู</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="padding:14px 18px; border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                {{ $orders->appends(request()->query())->links('vendor.pagination.tp-v4') }}
            </div>
        @else
            <x-seller-v4.empty icon="🛍️" title="ยังไม่มีคำสั่งซื้อ"
                               :text="$currentStatus !== '' ? 'ไม่มีคำสั่งซื้อในสถานะนี้' : 'คำสั่งซื้อจะแสดงที่นี่เมื่อมีลูกค้าสั่งสินค้าจากร้านของคุณ'">
                @if($currentStatus !== '')
                    <a href="{{ route('seller.orders.index') }}" class="tp-btn tp-btn-sm">ดูทั้งหมด</a>
                @else
                    <a href="{{ route('seller.products.create') }}" class="tp-btn tp-btn-sm tp-btn-primary">➕ เพิ่มสินค้า</a>
                @endif
            </x-seller-v4.empty>
        @endif
    </div>
</div>
@endsection
