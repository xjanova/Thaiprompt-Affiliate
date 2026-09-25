@extends('layouts.seller-v4')

@section('title', 'รายละเอียดเซสชัน '.($session->session_code ?: '#'.$session->id))

@php
    $payLabels = ['cash' => '💵 เงินสด', 'card' => '💳 บัตร', 'qr' => '📱 QR', 'bank_transfer' => '🏦 โอน', 'other' => '• อื่น ๆ'];
    $statusMap = ['completed' => ['สำเร็จ', 'ok'], 'refunded' => ['คืนเงิน', 'bad'], 'void' => ['ยกเลิก', 'muted'], 'pending' => ['รอดำเนินการ', 'warn']];
    $th = 'padding:11px 14px; text-align:left; font-size:10.5px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px; white-space:nowrap;';
    $td = 'padding:12px 14px; font-size:13px; color:var(--ink);';
    $row = 'border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);';
    $transactions = $session->transactions ?? collect();
    $diff = $session->cash_difference;
    $duration = $session->opened_at
        ? ($session->closed_at ? $session->opened_at->diffForHumans($session->closed_at, true) : $session->opened_at->diffForHumans(null, true))
        : '—';
    $cashRows = [
        ['เงินสดเปิดกะ', $session->opening_cash, 'info'],
        ['เงินสดที่ควรมี', $session->expected_cash, 'gold'],
        ['เงินสดปิดกะ (นับจริง)', $session->closing_cash, 'ok'],
    ];
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    <x-seller-kit.header :title="'เซสชัน '.($session->session_code ?: '#'.$session->id)" icon="🔐" crumb="ร้านค้า · POS · เซสชันการขาย"
                         :subtitle="($session->posDevice->device_name ?? '-').' · พนักงาน '.($session->user->name ?? '-')">
        <x-seller-kit.pill :tone="$session->status === 'open' ? 'ok' : 'muted'">{{ $session->status === 'open' ? '● เปิดอยู่' : 'ปิดแล้ว' }}</x-seller-kit.pill>
        <a href="{{ route('seller.pos.sessions') }}" class="tp-btn tp-btn-sm">← เซสชันทั้งหมด</a>
    </x-seller-kit.header>

    @include('seller.pos.partials.nav')

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:14px;">
        <x-seller-kit.stat label="จำนวนรายการ" :value="number_format($transactions->count())" icon="🧾" tone="info" />
        <x-seller-kit.stat label="ยอดขายรวม" :value="'฿'.number_format((float) $transactions->sum('total_amount'), 2)" icon="💰" tone="gold" />
        <x-seller-kit.stat label="ระยะเวลากะ" :value="$duration" icon="⏱️" tone="violet" />
        @if(! is_null($diff))
            <x-seller-kit.stat label="ผลต่างเงินสด" :value="($diff >= 0 ? '+' : '').'฿'.number_format((float) $diff, 2)" icon="⚖️" :tone="(float) $diff < 0 ? 'bad' : 'ok'" />
        @endif
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:16px;">
        <div class="tp-card" style="display:flex; flex-direction:column; gap:10px;">
            <div class="tp-section-h">ข้อมูลเซสชัน</div>
            <div style="display:flex; justify-content:space-between; font-size:13px;"><span style="color:var(--ink2);">เปิดกะ</span><span class="tp-num">{{ optional($session->opened_at)->format('d/m/Y H:i:s') }}</span></div>
            <div style="display:flex; justify-content:space-between; font-size:13px;"><span style="color:var(--ink2);">ปิดกะ</span><span class="tp-num">{{ $session->closed_at ? $session->closed_at->format('d/m/Y H:i:s') : '—' }}</span></div>
            <div style="display:flex; justify-content:space-between; font-size:13px;"><span style="color:var(--ink2);">อุปกรณ์</span>
                @if($session->posDevice)
                    <a href="{{ route('seller.pos.devices.show', $session->posDevice) }}" style="font-weight:700; color:var(--deep1); text-decoration:none;">{{ $session->posDevice->device_name }}</a>
                @else
                    <span>-</span>
                @endif
            </div>
            <div style="display:flex; justify-content:space-between; font-size:13px;"><span style="color:var(--ink2);">พนักงาน</span><span>{{ $session->user->name ?? '-' }}</span></div>
        </div>

        <div class="tp-card" style="display:flex; flex-direction:column; gap:10px;">
            <div class="tp-section-h">💵 เงินสดในลิ้นชัก</div>
            @foreach($cashRows as [$cLabel, $cValue, $cTone])
                <div style="display:flex; justify-content:space-between; font-size:13px;">
                    <span style="color:var(--ink2);">{{ $cLabel }}</span>
                    <span class="tp-num" style="font-weight:800;">{{ is_null($cValue) ? '—' : '฿'.number_format((float) $cValue, 2) }}</span>
                </div>
            @endforeach
            <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:6px; margin-top:4px;">
                <div class="tp-inset-sm" style="border-radius:12px; padding:8px; text-align:center;"><div style="font-size:10.5px; color:var(--ink2);">เงินสด</div><div class="tp-num" style="font-weight:800; font-size:13px;">฿{{ number_format((float) $session->total_cash_sales, 0) }}</div></div>
                <div class="tp-inset-sm" style="border-radius:12px; padding:8px; text-align:center;"><div style="font-size:10.5px; color:var(--ink2);">บัตร</div><div class="tp-num" style="font-weight:800; font-size:13px;">฿{{ number_format((float) $session->total_card_sales, 0) }}</div></div>
                <div class="tp-inset-sm" style="border-radius:12px; padding:8px; text-align:center;"><div style="font-size:10.5px; color:var(--ink2);">อื่น ๆ</div><div class="tp-num" style="font-weight:800; font-size:13px;">฿{{ number_format((float) $session->total_other_sales, 0) }}</div></div>
            </div>
        </div>

        @if($session->opening_notes || $session->closing_notes)
            <div class="tp-card" style="display:flex; flex-direction:column; gap:10px;">
                <div class="tp-section-h">📝 หมายเหตุ</div>
                @if($session->opening_notes)<div style="font-size:13px; line-height:1.6;"><span style="color:var(--ink2);">ตอนเปิดกะ:</span> {{ $session->opening_notes }}</div>@endif
                @if($session->closing_notes)<div style="font-size:13px; line-height:1.6;"><span style="color:var(--ink2);">ตอนปิดกะ:</span> {{ $session->closing_notes }}</div>@endif
            </div>
        @endif
    </div>

    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div class="tp-section-h" style="padding:16px 18px;">🧾 รายการขายในเซสชันนี้</div>
        @if($transactions->count() > 0)
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; min-width:640px;">
                    <thead>
                        <tr style="background:color-mix(in srgb, var(--ink2) 8%, transparent);">
                            <th style="{{ $th }}">วันที่</th>
                            <th style="{{ $th }}">เลขรายการ</th>
                            <th style="{{ $th }}">วิธีชำระ</th>
                            <th style="{{ $th }} text-align:right;">ยอดเงิน</th>
                            <th style="{{ $th }} text-align:center;">สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transactions as $transaction)
                            @php
                                [$stLabel, $stTone] = $statusMap[$transaction->status] ?? [$transaction->status, 'muted'];
                            @endphp
                            <tr style="{{ $row }}">
                                <td style="{{ $td }} white-space:nowrap;" class="tp-num">{{ optional($transaction->transaction_date)->format('d/m/Y H:i') }}</td>
                                <td style="{{ $td }}"><a href="{{ route('seller.pos.transactions.show', $transaction) }}" class="tp-num" style="font-weight:700; color:var(--deep1); text-decoration:none;">{{ $transaction->transaction_code }}</a></td>
                                <td style="{{ $td }}">{{ $payLabels[$transaction->payment_method] ?? $transaction->payment_method }}</td>
                                <td style="{{ $td }} text-align:right; font-weight:800;" class="tp-num">฿{{ number_format($transaction->total_amount, 2) }}</td>
                                <td style="{{ $td }} text-align:center;"><x-seller-kit.pill :tone="$stTone">{{ $stLabel }}</x-seller-kit.pill></td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="{{ $row }} background:color-mix(in srgb, var(--accent1) 8%, transparent);">
                            <td colspan="3" style="{{ $td }} text-align:right; font-weight:800;">รวมทั้งหมด</td>
                            <td style="{{ $td }} text-align:right; font-weight:800; font-size:15px; color:var(--deep1);" class="tp-num">฿{{ number_format((float) $transactions->sum('total_amount'), 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @else
            <x-seller-kit.empty icon="🧾" title="ยังไม่มีรายการขายในเซสชันนี้" />
        @endif
    </div>
</div>
@endsection
