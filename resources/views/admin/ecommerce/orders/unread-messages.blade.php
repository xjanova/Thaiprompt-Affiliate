@extends('layouts.admin-v4')

@section('title', 'ออเดอร์ที่มีข้อความยังไม่อ่าน')

@php
    $th = 'padding:12px 14px; text-align:left; font-size:11px; font-weight:700; color:var(--ink2); letter-spacing:.3px; white-space:nowrap;';
    $td = 'padding:12px 14px; font-size:13px; color:var(--ink); vertical-align:middle;';
@endphp

@section('content')
{{-- 💬 ออเดอร์ที่ลูกค้า/ร้านส่งข้อความมาแล้วแอดมินยังไม่เปิดอ่าน (ECommerceController@ordersWithUnreadMessages) --}}
<div style="display:flex; flex-direction:column; gap:18px;">
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · อีคอมเมิร์ซ · ข้อความ</div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">ข้อความยังไม่อ่าน 💬</h1>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">ออเดอร์ที่มีข้อความจากลูกค้าหรือร้านค้ารอให้ทีมงานตอบ</div>
        </div>
        <a href="{{ route('admin.ecommerce.orders.index') }}" class="tp-btn tp-btn-sm"><i class="fas fa-arrow-left"></i> รายการออเดอร์</a>
    </div>

    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%; min-width:720px; border-collapse:collapse;">
                <thead>
                    <tr>
                        <th style="{{ $th }}">ออเดอร์</th>
                        <th style="{{ $th }}">ลูกค้า</th>
                        <th style="{{ $th }}">สถานะ</th>
                        <th style="{{ $th }}">ข้อความล่าสุด</th>
                        <th style="{{ $th }} text-align:right;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr style="border-top:1px solid color-mix(in srgb, var(--ink2) 12%, transparent);">
                            <td style="{{ $td }} white-space:nowrap;">
                                <a href="{{ route('admin.ecommerce.orders.show', $order) }}" class="tp-num" style="font-weight:700; color:var(--deep1); text-decoration:none;">#{{ $order->order_number }}</a>
                                <div style="font-size:11.5px; color:var(--ink2);" class="tp-num">฿{{ number_format((float) $order->total_amount, 2) }}</div>
                            </td>
                            <td style="{{ $td }}">
                                <div style="font-weight:600;">{{ $order->user?->name ?? 'ไม่ระบุ' }}</div>
                                <div style="font-size:11.5px; color:var(--ink2);">{{ $order->user?->email }}</div>
                            </td>
                            <td style="{{ $td }} white-space:nowrap;"><span class="tp-pill tp-pill-soft">{{ $order->status_label }}</span></td>
                            <td style="{{ $td }} white-space:nowrap; color:var(--ink2);">
                                {{ $order->last_message_at ? $order->last_message_at->diffForHumans() : '-' }}
                            </td>
                            <td style="{{ $td }} text-align:right; white-space:nowrap;">
                                <a href="{{ route('admin.ecommerce.orders.tracking', ['order' => $order, 'tab' => 'chat']) }}" class="tp-btn tp-btn-sm tp-btn-primary"><i class="fas fa-reply"></i> เปิดแชท</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="padding:44px 16px; text-align:center; color:var(--ink2);">
                                <i class="fas fa-circle-check" style="font-size:30px; display:block; margin-bottom:8px; opacity:.5;"></i>
                                อ่านข้อความครบทุกออเดอร์แล้ว
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($orders->hasPages())
        <div>{{ $orders->links() }}</div>
    @endif
</div>
@endsection
