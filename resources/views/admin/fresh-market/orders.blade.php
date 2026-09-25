{{--
 | ออเดอร์ตลาดสด (admin.fresh-market.orders) — ธีม V4
 | ตัวแปรจาก Admin\FreshMarketController@orders: $orders (paginator, with buyer, seller, listing), $statuses [order_status => ชื่อไทย]
 | ตัวกรอง GET: status (รองรับชื่อเก่า confirmed/ready_for_pickup), payment_status (pending|paid|released|refunded), delivery_type (pickup|rider), search
 | ใช้คอลัมน์จริง order_status / seller_earning — กดแถวเพื่อจัดการ (ยกเลิก/คืนเงิน/ปิดออเดอร์/เรียกไรเดอร์ใหม่) ที่หน้ารายละเอียด
--}}
@extends('layouts.admin-v4')

@section('title', 'ออเดอร์ตลาดสด')

@section('content')
@include('admin.riders.partials.v4-kit')
@php
    $paymentStatuses = ['pending' => 'รอชำระ / เก็บปลายทาง', 'paid' => 'ชำระแล้ว (ระบบถือเงิน)', 'released' => 'โอนให้ร้านแล้ว', 'refunded' => 'คืนเงินแล้ว'];
    $deliveryTypes = ['rider' => 'ไรเดอร์ส่ง', 'pickup' => 'ลูกค้ารับเอง'];
    $currentStatus = \App\Models\FreshMarketOrder::normalizeStatus(request('status'));
    $hasFilter = request()->hasAny(['status', 'payment_status', 'delivery_type', 'search']);
@endphp
<div x-data="{}" style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== หัวเรื่อง ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div style="display:flex; align-items:center; gap:14px;">
            <a href="{{ route('admin.fresh-market.dashboard') }}" class="tp-icon-btn" title="กลับแดชบอร์ดตลาดสด"><i class="fas fa-arrow-left"></i></a>
            <div>
                <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ตลาดสด · ออเดอร์</div>
                <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">ออเดอร์ตลาดสด 📦</h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">ติดตามทุกออเดอร์ และเข้าไปยกเลิก/คืนเงิน ปิดออเดอร์ หรือเรียกไรเดอร์ใหม่ได้ที่หน้ารายละเอียด</div>
            </div>
        </div>
    </div>

    @include('admin.riders.partials.flash')

    {{-- ===== ตัวกรอง ===== --}}
    <div class="tp-card" style="padding:16px; display:flex; flex-direction:column; gap:12px;">
        <div style="display:flex; flex-wrap:wrap; gap:6px;">
            <a href="{{ route('admin.fresh-market.orders', array_filter(['payment_status' => request('payment_status'), 'delivery_type' => request('delivery_type'), 'search' => request('search')])) }}"
               class="tp-btn tp-btn-sm {{ $currentStatus === null ? 'tp-btn-primary' : '' }}">ทุกสถานะ</a>
            @foreach ($statuses as $value => $label)
                <a href="{{ route('admin.fresh-market.orders', array_filter(['status' => $value, 'payment_status' => request('payment_status'), 'delivery_type' => request('delivery_type'), 'search' => request('search')])) }}"
                   class="tp-btn tp-btn-sm {{ $currentStatus === $value ? 'tp-btn-primary' : '' }}">{{ $label }}</a>
            @endforeach
        </div>
        <form method="GET" action="{{ route('admin.fresh-market.orders') }}" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,190px),1fr)); gap:10px; align-items:end;">
            @if ($currentStatus)<input type="hidden" name="status" value="{{ $currentStatus }}">@endif
            <input type="search" name="search" value="{{ request('search') }}" class="tp-input" placeholder="เลขออเดอร์ ชื่อผู้ซื้อ หรือชื่อร้าน">
            <select name="payment_status" class="tp-input">
                <option value="">การชำระเงินทั้งหมด</option>
                @foreach ($paymentStatuses as $value => $label)
                    <option value="{{ $value }}" @selected(request('payment_status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="delivery_type" class="tp-input">
                <option value="">การจัดส่งทั้งหมด</option>
                @foreach ($deliveryTypes as $value => $label)
                    <option value="{{ $value }}" @selected(request('delivery_type') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <button type="submit" class="tp-btn tp-btn-primary"><i class="fas fa-magnifying-glass"></i> ค้นหา</button>
                @if ($hasFilter)
                    <a href="{{ route('admin.fresh-market.orders') }}" class="tp-btn"><i class="fas fa-rotate-left"></i> ล้าง</a>
                @endif
            </div>
        </form>
    </div>

    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; padding:14px 18px;">
            <div class="tp-section-h"><i class="fas fa-receipt"></i> รายการออเดอร์</div>
            <span style="font-size:12px; color:var(--ink2);">ทั้งหมด {{ number_format($orders->total()) }} ออเดอร์</span>
        </div>
        <div style="overflow-x:auto;">
            <table style="width:100%; min-width:1000px; border-collapse:collapse; font-size:13px;">
                <thead>
                    <tr style="text-align:left; font-size:11px; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">
                        <th style="padding:10px 18px;">ออเดอร์</th>
                        <th style="padding:10px 12px;">ผู้ซื้อ / ร้าน</th>
                        <th style="padding:10px 12px;">สินค้า</th>
                        <th style="padding:10px 12px; text-align:right;">ยอดเงิน</th>
                        <th style="padding:10px 12px;">การชำระเงิน</th>
                        <th style="padding:10px 12px;">สถานะ</th>
                        <th style="padding:10px 18px; text-align:right;"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr class="w1-row" style="box-shadow:inset 0 1px 0 color-mix(in srgb, var(--ink2) 14%, transparent);">
                            <td style="padding:12px 18px;">
                                <a href="{{ route('admin.fresh-market.orders.show', $order) }}" class="w1-link tp-num">#{{ $order->order_number }}</a>
                                <div style="font-size:11.5px; color:var(--ink2);">{{ $order->created_at?->thaidate('j M Y H:i') }}</div>
                                <div style="font-size:11.5px; color:var(--ink2);"><i class="fas {{ $order->delivery_type === 'rider' ? 'fa-motorcycle' : 'fa-person-walking' }}"></i> {{ $deliveryTypes[$order->delivery_type] ?? $order->delivery_type }}</div>
                            </td>
                            <td style="padding:12px;">
                                <div>{{ $order->buyer?->name ?? '-' }}</div>
                                <div style="font-size:11.5px;">
                                    @if ($order->seller)
                                        <a href="{{ route('admin.fresh-market.sellers.show', $order->seller_id) }}" class="w1-link">{{ $order->seller->shop_name }}</a>
                                    @else
                                        <span style="color:var(--ink2);">-</span>
                                    @endif
                                </div>
                            </td>
                            <td style="padding:12px; max-width:260px;">
                                {{-- ออเดอร์หลายรายการ: สรุปทุกรายการ (ออเดอร์เก่าใช้สินค้าเดียวจากคอลัมน์เดิม) --}}
                                @php $itemsSummary = $order->riderItemsSummary(); @endphp
                                <div style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="{{ $itemsSummary }}">{{ $itemsSummary }}</div>
                            </td>
                            <td style="padding:12px; text-align:right; white-space:nowrap;" class="tp-num">
                                <b>฿{{ number_format((float) $order->total_amount, 2) }}</b>
                                @if ((float) $order->delivery_fee > 0)
                                    <div style="font-size:11.5px; color:var(--ink2);">+ ค่าส่ง ฿{{ number_format((float) $order->delivery_fee, 2) }}</div>
                                @endif
                                @if ((float) $order->refunded_amount > 0)
                                    <div style="font-size:11.5px; color:color-mix(in srgb, var(--w-violet) 74%, var(--ink));">คืนแล้ว ฿{{ number_format((float) $order->refunded_amount, 2) }}</div>
                                @endif
                            </td>
                            <td style="padding:12px;">
                                <div style="font-size:12px; margin-bottom:4px;">{{ $order->payment_method === 'cod' ? 'เก็บเงินปลายทาง' : ($order->payment_method === 'wallet' || $order->payment_method === 'escrow' ? 'Wallet' : $order->payment_method) }}</div>
                                @include('admin.riders.partials.status', ['statusKind' => 'payment', 'statusValue' => $order->payment_status, 'statusLabel' => $order->payment_status_label])
                            </td>
                            <td style="padding:12px;">@include('admin.riders.partials.status', ['statusKind' => 'fm_order', 'statusValue' => $order->order_status, 'statusLabel' => null])</td>
                            <td style="padding:12px 18px; text-align:right;">
                                <a href="{{ route('admin.fresh-market.orders.show', $order) }}" class="tp-btn tp-btn-sm tp-btn-primary"><i class="fas fa-eye"></i> จัดการ</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding:44px 18px; text-align:center; color:var(--ink2);">
                                <i class="fas fa-receipt" style="font-size:30px; opacity:.5; display:block; margin-bottom:10px;"></i>
                                {{ $hasFilter ? 'ไม่พบออเดอร์ตามเงื่อนไขที่เลือก' : 'ยังไม่มีออเดอร์ตลาดสด' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($orders->hasPages())
        <div>{{ $orders->links() }}</div>
    @endif
</div>
@endsection
