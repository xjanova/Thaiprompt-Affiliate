{{--
 | คำสั่งซื้อของฉัน (ร้านค้าออนไลน์) — ธีม V4 (user-v4)
 | ข้อมูลจาก OrderController@index: $orders (paginator + items.product), $statusCounts [all, pending, processing, shipped, completed]
 | ตัวกรอง ?status= · ชำระใหม่ POST orders.retry-payment · ยกเลิก (ต้องระบุเหตุผล) ที่หน้ารายละเอียด
 --}}
@extends('layouts.user-v4')

@section('title', 'คำสั่งซื้อของฉัน')

@php
    $oiStatus = is_scalar(request('status')) ? (string) request('status') : '';
    $oiTabs = [
        '' => ['label' => 'ทั้งหมด', 'icon' => 'fa-border-all', 'count' => $statusCounts['all'] ?? 0],
        'pending' => ['label' => 'รอชำระ', 'icon' => 'fa-hourglass-half', 'count' => $statusCounts['pending'] ?? 0],
        'processing' => ['label' => 'กำลังเตรียม', 'icon' => 'fa-box-open', 'count' => $statusCounts['processing'] ?? 0],
        'shipped' => ['label' => 'กำลังจัดส่ง', 'icon' => 'fa-truck-fast', 'count' => $statusCounts['shipped'] ?? 0],
        'completed' => ['label' => 'สำเร็จ', 'icon' => 'fa-circle-check', 'count' => $statusCounts['completed'] ?? 0],
    ];
    $oiTone = function (string $status): string {
        return match ($status) {
            'pending' => 'color:var(--deep2); background:var(--a2soft);',
            'paid', 'processing', 'confirmed' => 'color:var(--deep1); background:var(--a1soft);',
            'shipped', 'in_transit', 'out_for_delivery' => 'color:var(--on-accent, #fff); background:linear-gradient(135deg, var(--accent2), var(--deep2));',
            'delivered', 'completed' => 'color:var(--on-accent, #fff); background:linear-gradient(135deg, var(--sf-ok, #4f9e7e), var(--sf-ok2, #3b8467));',
            'cancelled', 'refunded' => 'color:var(--on-accent, #fff); background:color-mix(in srgb, var(--ink2) 80%, transparent);',
            default => 'color:var(--ink); background:var(--surf);',
        };
    };
@endphp

@section('content')
<x-theme-v4.shop-kit />

<div style="max-width:1100px; margin:0 auto; display:flex; flex-direction:column; gap:16px;">
    <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px;">
        <div>
            <h1 style="margin:0; font-size:clamp(22px, 3.6vw, 28px); font-weight:800; color:var(--ink);"><i class="fas fa-receipt" style="color:var(--deep1);"></i> คำสั่งซื้อของฉัน</h1>
            <p class="tp-muted" style="margin:4px 0 0; font-size:13.5px;">ติดตามสถานะ ชำระเงิน และรีวิวสินค้าจากร้านค้าออนไลน์</p>
        </div>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <a href="{{ route('taladsod.orders') }}" class="tp-btn" style="text-decoration:none; height:44px;"><i class="fas fa-carrot"></i> คำสั่งซื้อตลาดสด</a>
            <a href="{{ route('storefront.index') }}" class="tp-btn tp-btn-primary" style="text-decoration:none; height:44px;"><i class="fas fa-bag-shopping"></i> ช้อปต่อ</a>
        </div>
    </div>

    <div class="sf-scroll" role="tablist" aria-label="สถานะคำสั่งซื้อ">
        @foreach($oiTabs as $key => $tab)
            <a href="{{ route('orders.index', $key === '' ? [] : ['status' => $key]) }}" role="tab" aria-selected="{{ $oiStatus === $key ? 'true' : 'false' }}"
               class="sf-chip {{ $oiStatus === $key ? 'is-on' : '' }}">
                <i class="fas {{ $tab['icon'] }}"></i> {{ $tab['label'] }}
                <span class="tp-num" style="opacity:.8;">{{ number_format((int) $tab['count']) }}</span>
            </a>
        @endforeach
    </div>

    @forelse($orders as $order)
        @php
            $oiItems = $order->items;
            $oiCanPay = $order->canRetryPayment() && ! $order->isCod();
        @endphp
        <article class="tp-card sf-stack" style="gap:12px;">
            <div style="display:flex; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; gap:10px;">
                <div style="min-width:0;">
                    <div style="display:flex; flex-wrap:wrap; align-items:center; gap:8px;">
                        <a href="{{ route('orders.show', $order->id) }}" class="tp-num" style="font-weight:800; font-size:16px; color:var(--ink); text-decoration:none;">#{{ $order->order_number }}</a>
                        <span class="tp-pill" style="{{ $oiTone((string) $order->status) }}">{{ $order->status_label }}</span>
                        @if($order->isRiderDelivery())
                            <span class="tp-pill tp-pill-soft"><i class="fas fa-motorcycle"></i> ไรเดอร์</span>
                        @endif
                    </div>
                    <div class="tp-muted" style="font-size:12.5px; margin-top:4px;">
                        {{ $order->created_at->timezone('Asia/Bangkok')->format('d/m/Y H:i') }} · {{ number_format((int) $order->total_items) }} ชิ้น · {{ \App\Support\Shop\PaymentMethod::labelTh($order->payment_method) }}
                    </div>
                </div>
                <div style="text-align:right;">
                    <div class="tp-muted" style="font-size:11.5px;">ยอดรวม</div>
                    <div class="tp-num" style="font-size:20px; font-weight:800; color:var(--deep1);">฿{{ number_format((float) $order->total_amount, 2) }}</div>
                </div>
            </div>

            <div style="display:flex; flex-direction:column; gap:8px;">
                @foreach($oiItems->take(3) as $item)
                    @php $oiImg = \App\Services\Shop\ShopPresenter::imageUrl($item->product_image); @endphp
                    <div style="display:flex; align-items:center; gap:10px;">
                        <span class="sf-thumb" style="width:52px; height:52px;">@if($oiImg)<img src="{{ $oiImg }}" alt="" loading="lazy">@else 📦 @endif</span>
                        <span style="flex:1; min-width:0; font-size:13px; font-weight:600; color:var(--ink); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $item->product_name }}</span>
                        <span class="tp-muted tp-num" style="font-size:12.5px;">× {{ (int) $item->quantity }}</span>
                    </div>
                @endforeach
                @if($oiItems->count() > 3)
                    <span class="tp-muted" style="font-size:12.5px;">และอีก {{ $oiItems->count() - 3 }} รายการ</span>
                @endif
            </div>

            @if($order->tracking_number)
                <div class="sf-note sf-note-info" style="font-size:12.5px; padding:8px 12px;"><i class="fas fa-barcode"></i> เลขพัสดุ <strong class="tp-num">{{ $order->tracking_number }}</strong></div>
            @endif

            <div style="display:flex; flex-wrap:wrap; gap:8px;">
                <a href="{{ route('orders.show', $order->id) }}" class="tp-btn" style="text-decoration:none; height:44px;"><i class="fas fa-eye"></i> ดูรายละเอียด</a>
                @if($oiCanPay)
                    <form method="POST" action="{{ route('orders.retry-payment', $order->id) }}" style="margin:0;" x-data="{ busy: false }" @submit="if (busy) { $event.preventDefault(); } busy = true">
                        @csrf
                        <button type="submit" class="tp-btn tp-btn-primary" style="height:44px;" :disabled="busy"><i class="fas fa-credit-card"></i> ชำระเงินตอนนี้</button>
                    </form>
                @endif
                @if($order->status === 'delivered')
                    <form method="POST" action="{{ route('orders.confirm-received', $order->id) }}" style="margin:0;"
                          onsubmit="return confirm(@js('ยืนยันว่าได้รับสินค้าครบถ้วนแล้ว?'))">
                        @csrf
                        <button type="submit" class="tp-btn tp-btn-primary" style="height:44px;"><i class="fas fa-check"></i> ได้รับสินค้าแล้ว</button>
                    </form>
                @endif
                @if($order->canBeCancelled())
                    <a href="{{ route('orders.show', $order->id) }}#cancel" class="tp-btn" style="text-decoration:none; height:44px; color:var(--sf-sale, #e0564f);"><i class="fas fa-ban"></i> ยกเลิก</a>
                @endif
            </div>
        </article>
    @empty
        <div class="tp-card" style="text-align:center; padding:40px 16px;">
            <div style="font-size:52px;" aria-hidden="true">🧾</div>
            <h2 style="margin:10px 0 6px; font-size:19px; font-weight:800; color:var(--ink);">{{ $oiStatus === '' ? 'ยังไม่มีคำสั่งซื้อ' : 'ไม่มีคำสั่งซื้อในสถานะนี้' }}</h2>
            <p class="tp-muted" style="margin:0 0 16px;">เลือกซื้อสินค้าคุณภาพจากร้านค้าออนไลน์ได้เลย</p>
            <a href="{{ route('storefront.index') }}" class="sf-btn3d"><i class="fas fa-bag-shopping"></i> เริ่มช้อปปิ้ง</a>
        </div>
    @endforelse

    @if($orders->hasPages())
        <div>{{ $orders->withQueryString()->links(view()->exists('vendor.pagination.tp-v4') ? 'vendor.pagination.tp-v4' : null) }}</div>
    @endif
</div>
@endsection
