@extends('layouts.admin-v4')

@section('title', 'คำสั่งซื้อร้านค้า')

@php
    // โทนสีสถานะ — ใช้ตัวแปร CSS (มีค่าสำรอง) ให้เปลี่ยนตามธีม/โหมดมืดได้
    $c = [
        'ok' => 'var(--tp-ok,#5aa07e)',
        'bad' => 'var(--tp-bad,#d9534f)',
        'warn' => 'var(--tp-warn,#e0a52e)',
        'info' => 'var(--tp-info,#5689b8)',
        'violet' => 'var(--tp-violet,#8c6fd6)',
        'mute' => 'var(--ink2)',
    ];
    $pill = fn (string $color) => "background:color-mix(in srgb, {$color} 16%, transparent); color:{$color};";

    $statusColor = [
        'pending' => $c['warn'], 'paid' => $c['info'], 'processing' => $c['info'],
        'shipped' => $c['violet'], 'delivered' => $c['ok'], 'completed' => $c['ok'],
        'cancelled' => $c['bad'], 'refunded' => $c['mute'],
    ];
    $statusOptions = [
        'pending' => 'รอดำเนินการ / รอชำระ', 'paid' => 'ชำระแล้ว รอร้านยืนยัน', 'processing' => 'กำลังเตรียมสินค้า',
        'shipped' => 'จัดส่งแล้ว', 'delivered' => 'ส่งถึงแล้ว', 'completed' => 'สำเร็จ',
        'cancelled' => 'ยกเลิก', 'refunded' => 'คืนเงิน',
    ];
    $paymentColor = ['pending' => $c['warn'], 'paid' => $c['ok'], 'failed' => $c['bad'], 'refunded' => $c['mute']];

    $th = 'padding:12px 14px; text-align:left; font-size:11px; font-weight:700; color:var(--ink2); letter-spacing:.3px; white-space:nowrap;';
    $td = 'padding:12px 14px; font-size:13px; color:var(--ink); vertical-align:middle;';
    $lbl = 'display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;';
    $stats = $stats ?? [];
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== หัวหน้า ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · อีคอมเมิร์ซ · คำสั่งซื้อ</div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">คำสั่งซื้อร้านค้า 🧾</h1>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">ติดตามออเดอร์ทุกร้าน สถานะการชำระเงิน และการจัดส่ง</div>
        </div>
        <div style="display:flex; gap:9px; flex-wrap:wrap;">
            <a href="{{ route('admin.ecommerce.orders.unread-messages') }}" class="tp-btn tp-btn-sm">
                <i class="fas fa-comments"></i> ข้อความยังไม่อ่าน
                @if(($stats['unread_messages'] ?? 0) > 0)
                    <span class="tp-pill tp-pill-gold tp-num">{{ number_format($stats['unread_messages']) }}</span>
                @endif
            </a>
            <a href="{{ route('admin.ecommerce.reports') }}" class="tp-btn tp-btn-sm"><i class="fas fa-chart-column"></i> รายงานยอดขาย</a>
        </div>
    </div>

    {{-- ===== KPI ===== --}}
    @if(!empty($stats))
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:14px;">
            @foreach([
                ['ทั้งหมด', $stats['total'] ?? 0, 'fa-layer-group', null, []],
                ['รอดำเนินการ', $stats['pending'] ?? 0, 'fa-clock', $c['warn'], ['status' => 'pending']],
                ['ต้องเตรียม/ส่ง', $stats['to_fulfil'] ?? 0, 'fa-box-open', $c['info'], ['status' => 'processing']],
                ['กำลังขนส่ง', $stats['shipped'] ?? 0, 'fa-truck-fast', $c['violet'], ['status' => 'shipped']],
                ['ยังไม่ชำระ', $stats['unpaid'] ?? 0, 'fa-wallet', $c['bad'], ['payment_status' => 'pending']],
            ] as [$label, $value, $icon, $color, $query])
                <a href="{{ route('admin.ecommerce.orders.index', $query) }}" class="tp-card tp-card-hover" style="padding:16px; text-decoration:none; color:inherit;">
                    <div style="display:flex; align-items:center; gap:12px;">
                        <div class="tp-tile" style="width:40px; height:40px; font-size:16px; {{ $color ? 'background:'.$color.';' : '' }}">
                            <i class="fas {{ $icon }}"></i>
                        </div>
                        <div>
                            <div class="tp-num" style="font-size:24px; font-weight:800; line-height:1;">{{ number_format($value) }}</div>
                            <div style="font-size:12px; color:var(--ink2); margin-top:3px;">{{ $label }}</div>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif

    {{-- ===== ตัวกรอง ===== --}}
    <div class="tp-card" style="padding:18px;">
        <form method="GET" action="{{ route('admin.ecommerce.orders.index') }}"
              style="display:grid; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); gap:14px; align-items:end;">
            <div style="grid-column:1 / -1; min-width:0;">
                <label style="{{ $lbl }}">🔍 ค้นหา</label>
                <input type="text" name="search" value="{{ request('search') }}" class="tp-input" placeholder="เลขที่คำสั่งซื้อ ชื่อ หรืออีเมลลูกค้า">
            </div>
            <div>
                <label style="{{ $lbl }}">สถานะออเดอร์</label>
                <select name="status" class="tp-input">
                    <option value="">ทุกสถานะ</option>
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="{{ $lbl }}">การชำระเงิน</label>
                <select name="payment_status" class="tp-input">
                    <option value="">ทั้งหมด</option>
                    <option value="pending" @selected(request('payment_status') === 'pending')>รอชำระเงิน</option>
                    <option value="paid" @selected(request('payment_status') === 'paid')>ชำระแล้ว</option>
                    <option value="failed" @selected(request('payment_status') === 'failed')>ชำระไม่สำเร็จ</option>
                    <option value="refunded" @selected(request('payment_status') === 'refunded')>คืนเงินแล้ว</option>
                </select>
            </div>
            <div>
                <label style="{{ $lbl }}">การจัดส่ง</label>
                <select name="delivery_method" class="tp-input">
                    <option value="">ทั้งหมด</option>
                    <option value="parcel" @selected(request('delivery_method') === 'parcel')>📦 พัสดุ</option>
                    <option value="rider" @selected(request('delivery_method') === 'rider')>🛵 ไรเดอร์</option>
                </select>
            </div>
            <div>
                <label style="{{ $lbl }}">ตั้งแต่วันที่</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="tp-input">
            </div>
            <div>
                <label style="{{ $lbl }}">ถึงวันที่</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="tp-input">
            </div>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <button type="submit" class="tp-btn tp-btn-primary"><i class="fas fa-magnifying-glass"></i> กรอง</button>
                <a href="{{ route('admin.ecommerce.orders.index') }}" class="tp-btn"><i class="fas fa-rotate-left"></i> ล้าง</a>
            </div>
        </form>
    </div>

    {{-- ===== ตารางออเดอร์ ===== --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:14px 18px; display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; box-shadow:var(--inset-sm);">
            <div class="tp-section-h">รายการคำสั่งซื้อ</div>
            <div style="font-size:12px; color:var(--ink2);">พบ <span class="tp-num">{{ number_format($orders->total()) }}</span> รายการ</div>
        </div>
        <div style="overflow-x:auto;">
            <table style="width:100%; min-width:980px; border-collapse:collapse;">
                <thead>
                    <tr>
                        <th style="{{ $th }}">เลขที่</th>
                        <th style="{{ $th }}">ลูกค้า</th>
                        <th style="{{ $th }}">ร้าน</th>
                        <th style="{{ $th }} text-align:right;">ยอดรวม</th>
                        <th style="{{ $th }}">สถานะ</th>
                        <th style="{{ $th }}">ชำระเงิน</th>
                        <th style="{{ $th }}">จัดส่ง</th>
                        <th style="{{ $th }}">วันที่</th>
                        <th style="{{ $th }} text-align:right;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        @php
                            $sColor = $statusColor[$order->status] ?? $c['mute'];
                            $pColor = $paymentColor[$order->payment_status] ?? $c['mute'];
                            $isRider = ($order->delivery_method ?? 'parcel') === 'rider';
                        @endphp
                        <tr style="border-top:1px solid color-mix(in srgb, var(--ink2) 12%, transparent);">
                            <td style="{{ $td }} white-space:nowrap;">
                                <a href="{{ route('admin.ecommerce.orders.show', $order) }}" class="tp-num" style="font-weight:700; color:var(--deep1); text-decoration:none;">#{{ $order->order_number }}</a>
                                @if($order->has_unread_messages)
                                    <span class="tp-pill" style="{{ $pill($c['bad']) }} margin-left:4px;" title="มีข้อความยังไม่อ่าน"><i class="fas fa-comment-dots"></i></span>
                                @endif
                                <div style="font-size:11.5px; color:var(--ink2); margin-top:2px;">{{ number_format($order->items->sum('quantity')) }} ชิ้น</div>
                            </td>
                            <td style="{{ $td }}">
                                <div style="font-weight:600; max-width:190px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $order->user?->name ?: 'ไม่ระบุ' }}</div>
                                <div style="font-size:11.5px; color:var(--ink2); max-width:190px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $order->user?->email }}</div>
                            </td>
                            <td style="{{ $td }}">
                                @if($order->store)
                                    <a href="{{ route('admin.storefront.vendor-stores.show', $order->store) }}" style="color:var(--ink); text-decoration:none; max-width:160px; display:inline-block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $order->store->store_name }}</a>
                                @else
                                    <span style="color:var(--ink2);">หลายร้าน / ไม่ระบุ</span>
                                @endif
                            </td>
                            <td style="{{ $td }} text-align:right; white-space:nowrap;">
                                <span class="tp-num" style="font-weight:700;">฿{{ number_format((float) $order->total_amount, 2) }}</span>
                                <div style="font-size:11px; color:var(--ink2);">{{ \App\Support\Shop\PaymentMethod::labelTh($order->payment_method) }}</div>
                            </td>
                            <td style="{{ $td }} white-space:nowrap;"><span class="tp-pill" style="{{ $pill($sColor) }}">{{ $order->status_label }}</span></td>
                            <td style="{{ $td }} white-space:nowrap;"><span class="tp-pill" style="{{ $pill($pColor) }}">{{ \App\Services\Shop\ShopPresenter::paymentStatusLabel($order->payment_status) }}</span></td>
                            <td style="{{ $td }} white-space:nowrap; color:var(--ink2);">
                                {{ $isRider ? '🛵 ไรเดอร์' : '📦 พัสดุ' }}
                                @if($order->tracking_number)
                                    <div class="tp-num" style="font-size:11px;">{{ $order->tracking_number }}</div>
                                @endif
                            </td>
                            <td style="{{ $td }} white-space:nowrap;">
                                <div>{{ $order->created_at?->format('d/m/Y') }}</div>
                                <div style="font-size:11.5px; color:var(--ink2);">{{ $order->created_at?->format('H:i') }}</div>
                            </td>
                            <td style="{{ $td }} text-align:right; white-space:nowrap;">
                                <a href="{{ route('admin.ecommerce.orders.show', $order) }}" class="tp-btn tp-btn-sm tp-btn-primary"><i class="fas fa-eye"></i> ดู</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="padding:44px 16px; text-align:center; color:var(--ink2);">
                                <i class="fas fa-inbox" style="font-size:30px; display:block; margin-bottom:8px; opacity:.5;"></i>
                                ไม่พบคำสั่งซื้อตามเงื่อนไขนี้
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
