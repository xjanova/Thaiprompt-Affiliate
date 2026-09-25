@extends('layouts.seller-v4')

@section('title', 'รายการขาย POS')

@php
    $payLabels = ['cash' => '💵 เงินสด', 'card' => '💳 บัตร', 'qr' => '📱 QR', 'bank_transfer' => '🏦 โอน', 'e-wallet' => '👛 e-Wallet', 'credit' => '🧾 เครดิต', 'multiple' => '🔀 หลายช่องทาง', 'other' => '• อื่น ๆ'];
    $statusMap = ['completed' => ['สำเร็จ', 'ok'], 'refunded' => ['คืนเงิน', 'bad'], 'void' => ['ยกเลิก', 'muted'], 'pending' => ['รอดำเนินการ', 'warn']];
    $sum = $summary ?? ['count' => $transactions->total(), 'total' => 0, 'by_method' => []];
    $by = $sum['by_method'] ?? [];
    $th = 'padding:11px 14px; text-align:left; font-size:10.5px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px; white-space:nowrap;';
    $td = 'padding:12px 14px; font-size:13px; color:var(--ink);';
    $row = 'border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);';
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    <x-seller-kit.header title="รายการขาย POS" icon="🧾" crumb="ร้านค้า · POS"
                         subtitle="ประวัติการขายหน้าร้านทั้งหมด ค้นตามอุปกรณ์และช่วงวันที่ได้">
        <a href="{{ route('seller.pos.terminal') }}" class="tp-btn tp-btn-primary tp-btn-sm">🛒 เปิดหน้าขาย</a>
    </x-seller-kit.header>

    @include('seller.pos.partials.nav')

    <form method="GET" action="{{ route('seller.pos.transactions') }}" class="tp-card" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); gap:12px; align-items:end;">
        <div>
            <label for="f-device" style="font-size:12px; font-weight:700; color:var(--ink2);">อุปกรณ์</label>
            <select id="f-device" name="device_id" class="tp-input" style="margin-top:6px;">
                <option value="">ทั้งหมด</option>
                @foreach($devices as $dev)
                    <option value="{{ $dev->id }}" @selected((string) request('device_id') === (string) $dev->id)>{{ $dev->device_name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="f-from" style="font-size:12px; font-weight:700; color:var(--ink2);">ตั้งแต่วันที่</label>
            <input id="f-from" type="date" name="date_from" value="{{ request('date_from') }}" class="tp-input" style="margin-top:6px;">
        </div>
        <div>
            <label for="f-to" style="font-size:12px; font-weight:700; color:var(--ink2);">ถึงวันที่</label>
            <input id="f-to" type="date" name="date_to" value="{{ request('date_to') }}" class="tp-input" style="margin-top:6px;">
        </div>
        <div style="display:flex; gap:8px;">
            <button type="submit" class="tp-btn tp-btn-primary" style="flex:1;">🔍 ค้นหา</button>
            <a href="{{ route('seller.pos.transactions') }}" class="tp-btn">ล้าง</a>
        </div>
    </form>

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:14px;">
        <x-seller-kit.stat label="จำนวนรายการ" :value="number_format($sum['count'])" icon="🧾" tone="info" hint="ตามตัวกรองปัจจุบัน" />
        <x-seller-kit.stat label="ยอดขายรวม" :value="'฿'.number_format($sum['total'], 2)" icon="💰" tone="gold" />
        <x-seller-kit.stat label="เงินสด" :value="'฿'.number_format($by['cash']['total'] ?? 0, 2)" icon="💵" tone="ok" :hint="number_format($by['cash']['count'] ?? 0).' รายการ'" />
        <x-seller-kit.stat label="บัตร / QR / โอน" :value="'฿'.number_format(($by['card']['total'] ?? 0) + ($by['qr']['total'] ?? 0) + ($by['bank_transfer']['total'] ?? 0), 2)" icon="📱" tone="violet"
                           :hint="number_format(($by['card']['count'] ?? 0) + ($by['qr']['count'] ?? 0) + ($by['bank_transfer']['count'] ?? 0)).' รายการ'" />
    </div>

    <div class="tp-card" style="padding:0; overflow:hidden;">
        @if($transactions->count() > 0)
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; min-width:860px;">
                    <thead>
                        <tr style="background:color-mix(in srgb, var(--ink2) 8%, transparent);">
                            <th style="{{ $th }}">วันที่</th>
                            <th style="{{ $th }}">เลขรายการ</th>
                            <th style="{{ $th }}">อุปกรณ์</th>
                            <th style="{{ $th }}">ลูกค้า</th>
                            <th style="{{ $th }}">วิธีชำระ</th>
                            <th style="{{ $th }} text-align:right;">ยอดเงิน</th>
                            <th style="{{ $th }} text-align:center;">สถานะ</th>
                            <th style="{{ $th }}"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transactions as $transaction)
                            @php
                                [$stLabel, $stTone] = $statusMap[$transaction->status] ?? [$transaction->status, 'muted'];
                            @endphp
                            <tr style="{{ $row }}">
                                <td style="{{ $td }} white-space:nowrap;" class="tp-num">{{ optional($transaction->transaction_date)->format('d/m/Y H:i') }}</td>
                                <td style="{{ $td }}">
                                    <div class="tp-num" style="font-weight:700;">{{ $transaction->transaction_code }}</div>
                                    @if($transaction->receipt_number)<div class="tp-num" style="font-size:11px; color:var(--ink2);">{{ $transaction->receipt_number }}</div>@endif
                                </td>
                                <td style="{{ $td }}">{{ $transaction->posDevice->device_name ?? '-' }}</td>
                                <td style="{{ $td }}">
                                    @if($transaction->customer_name)
                                        <div>{{ $transaction->customer_name }}</div>
                                        @if($transaction->customer_phone)<div class="tp-num" style="font-size:11px; color:var(--ink2);">{{ $transaction->customer_phone }}</div>@endif
                                    @else
                                        <span style="color:var(--ink2);">ลูกค้าหน้าร้าน</span>
                                    @endif
                                </td>
                                <td style="{{ $td }} white-space:nowrap;">{{ $payLabels[$transaction->payment_method] ?? $transaction->payment_method }}</td>
                                <td style="{{ $td }} text-align:right;">
                                    <div class="tp-num" style="font-weight:800;">฿{{ number_format($transaction->total_amount, 2) }}</div>
                                    @if($transaction->discount_amount > 0)<div class="tp-num" style="font-size:11px; color:var(--tp-bad, #d9534f);">ส่วนลด −฿{{ number_format($transaction->discount_amount, 2) }}</div>@endif
                                </td>
                                <td style="{{ $td }} text-align:center;"><x-seller-kit.pill :tone="$stTone">{{ $stLabel }}</x-seller-kit.pill></td>
                                <td style="{{ $td }} text-align:right; white-space:nowrap;">
                                    <a href="{{ route('seller.pos.transactions.show', $transaction) }}" style="font-weight:700; color:var(--deep1); text-decoration:none;">ดู →</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($transactions->hasPages())
                <div style="padding:14px 18px; border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">{{ $transactions->links() }}</div>
            @endif
        @else
            <x-seller-kit.empty icon="🧾" title="ไม่พบรายการขาย" text="ยังไม่มีการขาย หรือลองปรับตัวกรองใหม่">
                <a href="{{ route('seller.pos.terminal') }}" class="tp-btn tp-btn-primary tp-btn-sm">🛒 เริ่มขาย</a>
            </x-seller-kit.empty>
        @endif
    </div>
</div>
@endsection
