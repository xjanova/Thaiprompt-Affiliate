@extends('layouts.seller-v4')

@section('title', 'รายการขาย '.$transaction->transaction_code)

@php
    $payLabels = ['cash' => '💵 เงินสด', 'card' => '💳 บัตร', 'qr' => '📱 QR', 'bank_transfer' => '🏦 โอนเงิน', 'e-wallet' => '👛 e-Wallet', 'credit' => '🧾 เครดิต', 'multiple' => '🔀 หลายช่องทาง', 'other' => '• อื่น ๆ'];
    $statusMap = ['completed' => ['สำเร็จ', 'ok'], 'refunded' => ['คืนเงิน', 'bad'], 'void' => ['ยกเลิก', 'muted'], 'pending' => ['รอดำเนินการ', 'warn']];
    [$stLabel, $stTone] = $statusMap[$transaction->status] ?? [$transaction->status, 'muted'];
    $payStatus = ['completed' => ['ชำระแล้ว', 'ok'], 'paid' => ['ชำระแล้ว', 'ok'], 'refunded' => ['คืนเงินแล้ว', 'bad'], 'partial_refund' => ['คืนเงินบางส่วน', 'warn'], 'void' => ['ยกเลิก', 'muted']];
    [$psLabel, $psTone] = $payStatus[$transaction->payment_status] ?? [$transaction->payment_status, 'muted'];
    $items = $transaction->items ?? collect();
    $paid = $transaction->amount_paid;
    $th = 'padding:11px 14px; text-align:left; font-size:10.5px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px; white-space:nowrap;';
    $td = 'padding:12px 14px; font-size:13px; color:var(--ink);';
    $row = 'border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);';
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    <x-seller-kit.header :title="$transaction->transaction_code" icon="🧾" crumb="ร้านค้า · POS · รายการขาย"
                         :subtitle="'เลขใบเสร็จ '.($transaction->receipt_number ?: '—').' · '.optional($transaction->transaction_date)->format('d/m/Y H:i:s')">
        <x-seller-kit.pill :tone="$stTone">{{ $stLabel }}</x-seller-kit.pill>
        <a href="{{ route('seller.pos.receipt', $transaction) }}" target="_blank" rel="noopener" class="tp-btn tp-btn-primary tp-btn-sm">🖨️ พิมพ์ใบเสร็จ</a>
        <a href="{{ route('seller.pos.transactions') }}" class="tp-btn tp-btn-sm">← รายการขาย</a>
    </x-seller-kit.header>

    @include('seller.pos.partials.nav')

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:16px;">
        <div class="tp-card" style="display:flex; flex-direction:column; gap:10px;">
            <div class="tp-section-h">อุปกรณ์และพนักงาน</div>
            <div style="display:flex; justify-content:space-between; font-size:13px;"><span style="color:var(--ink2);">อุปกรณ์</span>
                @if($transaction->posDevice)
                    <a href="{{ route('seller.pos.devices.show', $transaction->posDevice) }}" style="font-weight:700; color:var(--deep1); text-decoration:none;">{{ $transaction->posDevice->device_name }}</a>
                @else
                    <span>-</span>
                @endif
            </div>
            <div style="display:flex; justify-content:space-between; font-size:13px;"><span style="color:var(--ink2);">พนักงานขาย</span><span>{{ $transaction->user->name ?? '-' }}</span></div>
            @if($transaction->posSession)
                <div style="display:flex; justify-content:space-between; font-size:13px;"><span style="color:var(--ink2);">เซสชัน</span>
                    <a href="{{ route('seller.pos.sessions.show', $transaction->posSession) }}" class="tp-num" style="font-weight:700; color:var(--deep1); text-decoration:none;">{{ $transaction->posSession->session_code ?: '#'.$transaction->posSession->id }}</a>
                </div>
            @endif
        </div>

        <div class="tp-card" style="display:flex; flex-direction:column; gap:10px;">
            <div class="tp-section-h">ข้อมูลลูกค้า</div>
            @if($transaction->customer_name || $transaction->customer_phone || $transaction->customer_email)
                <div style="display:flex; justify-content:space-between; font-size:13px;"><span style="color:var(--ink2);">ชื่อ</span><span>{{ $transaction->customer_name ?: '-' }}</span></div>
                @if($transaction->customer_phone)<div style="display:flex; justify-content:space-between; font-size:13px;"><span style="color:var(--ink2);">โทร</span><span class="tp-num">{{ $transaction->customer_phone }}</span></div>@endif
                @if($transaction->customer_email)<div style="display:flex; justify-content:space-between; font-size:13px;"><span style="color:var(--ink2);">อีเมล</span><span style="overflow-wrap:anywhere;">{{ $transaction->customer_email }}</span></div>@endif
            @else
                <div style="text-align:center; padding:10px 0; color:var(--ink2); font-size:13px;"><div style="font-size:28px;" aria-hidden="true">🚶</div>ลูกค้าหน้าร้าน (ไม่ได้บันทึกข้อมูล)</div>
            @endif
        </div>

        <div class="tp-card" style="display:flex; flex-direction:column; gap:10px;">
            <div class="tp-section-h">การชำระเงิน</div>
            <div style="display:flex; justify-content:space-between; font-size:13px;"><span style="color:var(--ink2);">วิธีชำระ</span><span style="font-weight:700;">{{ $payLabels[$transaction->payment_method] ?? $transaction->payment_method }}</span></div>
            <div style="display:flex; justify-content:space-between; font-size:13px;"><span style="color:var(--ink2);">สถานะ</span><x-seller-kit.pill :tone="$psTone">{{ $psLabel }}</x-seller-kit.pill></div>
            @if(! is_null($paid))
                <div style="display:flex; justify-content:space-between; font-size:13px;"><span style="color:var(--ink2);">รับเงิน</span><span class="tp-num">฿{{ number_format((float) $paid, 2) }}</span></div>
                <div style="display:flex; justify-content:space-between; font-size:13px;"><span style="color:var(--ink2);">เงินทอน</span><span class="tp-num">฿{{ number_format((float) $transaction->change_amount, 2) }}</span></div>
            @endif
        </div>
    </div>

    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div class="tp-section-h" style="padding:16px 18px;">📦 รายการสินค้า</div>
        @if($items->count() > 0)
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; min-width:620px;">
                    <thead>
                        <tr style="background:color-mix(in srgb, var(--ink2) 8%, transparent);">
                            <th style="{{ $th }}">#</th>
                            <th style="{{ $th }}">สินค้า</th>
                            <th style="{{ $th }} text-align:right;">ราคา</th>
                            <th style="{{ $th }} text-align:center;">จำนวน</th>
                            <th style="{{ $th }} text-align:right;">ส่วนลด</th>
                            <th style="{{ $th }} text-align:right;">รวม</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $i => $item)
                            @php
                                $lineTotal = $item->total ?? ($item->unit_price * $item->quantity);
                            @endphp
                            <tr style="{{ $row }}">
                                <td style="{{ $td }}" class="tp-num">{{ $i + 1 }}</td>
                                <td style="{{ $td }}">
                                    <div style="font-weight:700;">{{ $item->product_name }}</div>
                                    @if($item->product_sku)<div class="tp-num" style="font-size:11px; color:var(--ink2);">SKU {{ $item->product_sku }}</div>@endif
                                </td>
                                <td style="{{ $td }} text-align:right;" class="tp-num">฿{{ number_format((float) $item->unit_price, 2) }}</td>
                                <td style="{{ $td }} text-align:center;" class="tp-num">{{ $item->quantity }}</td>
                                <td style="{{ $td }} text-align:right; color:var(--tp-bad, #d9534f);" class="tp-num">{{ (float) $item->discount_amount > 0 ? '−฿'.number_format((float) $item->discount_amount, 2) : '—' }}</td>
                                <td style="{{ $td }} text-align:right; font-weight:800;" class="tp-num">฿{{ number_format((float) $lineTotal, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-seller-kit.empty icon="📦" title="ไม่มีรายการสินค้า" />
        @endif

        {{-- สรุปยอด --}}
        <div style="padding:16px 18px; border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent); display:flex; justify-content:flex-end;">
            <div style="width:100%; max-width:340px; display:flex; flex-direction:column; gap:6px; font-size:13px;">
                <div style="display:flex; justify-content:space-between;"><span style="color:var(--ink2);">ยอดรวมสินค้า</span><span class="tp-num">฿{{ number_format((float) $transaction->subtotal, 2) }}</span></div>
                @if($transaction->discount_amount > 0)
                    <div style="display:flex; justify-content:space-between;"><span style="color:var(--ink2);">ส่วนลด</span><span class="tp-num" style="color:var(--tp-bad, #d9534f);">−฿{{ number_format((float) $transaction->discount_amount, 2) }}</span></div>
                @endif
                @if($transaction->tax_amount > 0)
                    <div style="display:flex; justify-content:space-between;"><span style="color:var(--ink2);">ภาษีมูลค่าเพิ่ม</span><span class="tp-num">฿{{ number_format((float) $transaction->tax_amount, 2) }}</span></div>
                @endif
                <div style="display:flex; justify-content:space-between; align-items:baseline; padding-top:8px; border-top:1px solid color-mix(in srgb, var(--ink2) 20%, transparent);">
                    <span style="font-weight:800;">ยอดชำระทั้งหมด</span>
                    <span class="tp-num" style="font-size:22px; font-weight:800; color:var(--deep1);">฿{{ number_format((float) $transaction->total_amount, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    @if($transaction->notes || $transaction->refund_reason)
        <div class="tp-card" style="border-left:4px solid var(--accent1);">
            <div class="tp-section-h">📝 หมายเหตุ</div>
            @if($transaction->notes)<p style="margin:8px 0 0; font-size:13px; line-height:1.65;">{{ $transaction->notes }}</p>@endif
            @if($transaction->refund_reason)<p style="margin:8px 0 0; font-size:13px; line-height:1.65; color:var(--tp-bad, #d9534f);">เหตุผลคืนเงิน: {{ $transaction->refund_reason }}</p>@endif
        </div>
    @endif
</div>
@endsection
