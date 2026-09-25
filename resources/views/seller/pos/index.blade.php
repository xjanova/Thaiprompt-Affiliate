@extends('layouts.seller-v4')

@section('title', 'ระบบขายหน้าร้าน (POS)')

@php
    // ป้ายวิธีชำระเงิน / สถานะรายการขาย (ใช้ซ้ำในตาราง)
    $payLabels = ['cash' => '💵 เงินสด', 'card' => '💳 บัตร', 'qr' => '📱 QR', 'bank_transfer' => '🏦 โอน', 'other' => '• อื่น ๆ'];
    $statusMap = ['completed' => ['สำเร็จ', 'ok'], 'refunded' => ['คืนเงิน', 'bad'], 'void' => ['ยกเลิก', 'muted'], 'pending' => ['รอดำเนินการ', 'warn']];
    $th = 'padding:11px 14px; text-align:left; font-size:10.5px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px; white-space:nowrap;';
    $td = 'padding:12px 14px; font-size:13px; color:var(--ink); vertical-align:middle;';
    $row = 'border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);';

    $quick = [
        ['🛒', 'เปิดหน้าขาย', route('seller.pos.terminal')],
        ['🖥️', 'ลงทะเบียนเครื่อง POS', route('seller.pos.terminals')],
        ['📱', 'จัดการอุปกรณ์', route('seller.pos.devices')],
        ['🧾', 'รายการขาย', route('seller.pos.transactions')],
        ['🔐', 'เซสชันการขาย', route('seller.pos.sessions')],
        ['⚙️', 'ตั้งค่า POS', route('seller.pos.settings')],
    ];
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    <x-seller-kit.header title="ระบบขายหน้าร้าน (POS)" icon="🏪" crumb="ร้านค้า · POS"
                         subtitle="ขายหน้าร้าน ดูยอดขาย และจัดการอุปกรณ์ทั้งหมดของร้านในที่เดียว">
        <a href="{{ route('seller.pos.terminal') }}" class="tp-btn tp-btn-primary">🛒 เปิดหน้าขาย</a>
        <a href="{{ route('seller.pos.analytics') }}" class="tp-btn">📈 รายงาน</a>
    </x-seller-kit.header>

    @include('seller.pos.partials.nav')

    {{-- สถิติหลัก --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:14px;">
        <x-seller-kit.stat label="อุปกรณ์ทั้งหมด" :value="number_format($stats['total_devices'])" icon="📱" tone="info" hint="จำนวนอุปกรณ์ที่ลงทะเบียน" />
        <x-seller-kit.stat label="ออนไลน์ / พร้อมใช้" :value="number_format($stats['online_devices']).' / '.number_format($stats['active_devices'])" icon="✅" tone="ok" :hint="'เซสชันที่เปิดอยู่ '.number_format($stats['active_sessions'])" />
        <x-seller-kit.stat label="ยอดขายวันนี้" :value="'฿'.number_format($stats['today_sales'], 2)" icon="💰" tone="gold" :hint="number_format($stats['today_transactions']).' รายการ'" />
        <x-seller-kit.stat label="ยอดขายเดือนนี้" :value="'฿'.number_format($stats['month_sales'], 2)" icon="📊" tone="violet" :hint="number_format($stats['month_transactions']).' รายการ'" />
    </div>

    {{-- เมนูด่วน --}}
    <div class="tp-card" style="padding:18px 20px;">
        <div class="tp-section-h" style="margin-bottom:12px;">⚡ เมนูด่วน</div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(130px,1fr)); gap:12px;">
            @foreach($quick as [$qIcon, $qLabel, $qUrl])
                <a href="{{ $qUrl }}" class="tp-card tp-card-hover" style="padding:16px 10px; text-align:center; text-decoration:none; box-shadow:var(--card-shadow-sm);">
                    <div style="font-size:28px;" aria-hidden="true">{{ $qIcon }}</div>
                    <div style="font-size:12.5px; font-weight:700; color:var(--ink); margin-top:6px;">{{ $qLabel }}</div>
                </a>
            @endforeach
        </div>
    </div>

    {{-- เซสชันที่เปิดอยู่ --}}
    @if($activeSessions->count() > 0)
        <div class="tp-card" style="padding:0; overflow:hidden;">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; padding:16px 18px;">
                <div class="tp-section-h">🔐 เซสชันที่เปิดอยู่</div>
                <a href="{{ route('seller.pos.sessions') }}" style="font-size:12.5px; font-weight:700; color:var(--deep1); text-decoration:none;">ดูทั้งหมด →</a>
            </div>
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; min-width:560px;">
                    <thead>
                        <tr style="background:color-mix(in srgb, var(--ink2) 8%, transparent);">
                            <th style="{{ $th }}">อุปกรณ์</th>
                            <th style="{{ $th }}">พนักงาน</th>
                            <th style="{{ $th }}">เริ่มเซสชัน</th>
                            <th style="{{ $th }} text-align:right;">ระยะเวลา</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($activeSessions as $session)
                            <tr style="{{ $row }}">
                                <td style="{{ $td }}">
                                    <a href="{{ route('seller.pos.sessions.show', $session) }}" style="font-weight:700; color:var(--ink); text-decoration:none;">{{ $session->posDevice->device_name ?? '-' }}</a>
                                    <div class="tp-num" style="font-size:11px; color:var(--ink2);">{{ $session->posDevice->device_code ?? '' }}</div>
                                </td>
                                <td style="{{ $td }}">{{ $session->user->name ?? '-' }}</td>
                                <td style="{{ $td }}" class="tp-num">{{ optional($session->opened_at)->format('d/m/Y H:i') }}</td>
                                <td style="{{ $td }} text-align:right; color:var(--ink2);">{{ optional($session->opened_at)->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- รายการขายล่าสุด --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; padding:16px 18px;">
            <div class="tp-section-h">💰 รายการขายล่าสุด</div>
            @if($recentTransactions->count() > 0)
                <a href="{{ route('seller.pos.transactions') }}" style="font-size:12.5px; font-weight:700; color:var(--deep1); text-decoration:none;">ดูทั้งหมด →</a>
            @endif
        </div>
        @if($recentTransactions->count() > 0)
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; min-width:720px;">
                    <thead>
                        <tr style="background:color-mix(in srgb, var(--ink2) 8%, transparent);">
                            <th style="{{ $th }}">วันที่</th>
                            <th style="{{ $th }}">เลขรายการ</th>
                            <th style="{{ $th }}">อุปกรณ์</th>
                            <th style="{{ $th }}">วิธีชำระ</th>
                            <th style="{{ $th }} text-align:right;">ยอดเงิน</th>
                            <th style="{{ $th }} text-align:center;">สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentTransactions as $transaction)
                            @php
                                [$stLabel, $stTone] = $statusMap[$transaction->status] ?? [$transaction->status, 'muted'];
                            @endphp
                            <tr style="{{ $row }}">
                                <td style="{{ $td }} white-space:nowrap;" class="tp-num">{{ optional($transaction->transaction_date)->format('d/m/Y H:i') }}</td>
                                <td style="{{ $td }}">
                                    <a href="{{ route('seller.pos.transactions.show', $transaction) }}" class="tp-num" style="font-weight:700; color:var(--deep1); text-decoration:none;">{{ $transaction->transaction_code }}</a>
                                    @if($transaction->receipt_number)
                                        <div class="tp-num" style="font-size:11px; color:var(--ink2);">{{ $transaction->receipt_number }}</div>
                                    @endif
                                </td>
                                <td style="{{ $td }}">{{ $transaction->posDevice->device_name ?? '-' }}</td>
                                <td style="{{ $td }}">{{ $payLabels[$transaction->payment_method] ?? $transaction->payment_method }}</td>
                                <td style="{{ $td }} text-align:right; font-weight:800;" class="tp-num">฿{{ number_format($transaction->total_amount, 2) }}</td>
                                <td style="{{ $td }} text-align:center;"><x-seller-kit.pill :tone="$stTone">{{ $stLabel }}</x-seller-kit.pill></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-seller-kit.empty icon="🧾" title="ยังไม่มีรายการขาย" text="เปิดหน้าขายหน้าร้านหรือเชื่อมเครื่อง POS แล้วรายการขายจะแสดงที่นี่">
                <a href="{{ route('seller.pos.terminal') }}" class="tp-btn tp-btn-primary tp-btn-sm">🛒 เริ่มขายเลย</a>
            </x-seller-kit.empty>
        @endif
    </div>

    {{-- คำแนะนำเริ่มต้น --}}
    <div class="tp-card" style="padding:18px 20px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
        <div style="display:flex; gap:14px; align-items:flex-start;">
            <span class="tp-tile" style="width:44px; height:44px; font-size:20px;" aria-hidden="true">💡</span>
            <div>
                <div class="tp-section-h">เริ่มต้นใช้งาน POS</div>
                <ul style="margin:8px 0 0; padding-left:18px; font-size:13px; line-height:1.8; color:var(--ink);">
                    <li>ตั้งค่าใบเสร็จ ภาษี และวิธีชำระเงินที่เมนู “ตั้งค่า”</li>
                    <li>ขายผ่านเว็บได้ทันทีที่ “ขายหน้าร้าน” หรือเชื่อมโปรแกรม POS ด้วย API Key ที่ “เครื่อง POS”</li>
                    <li>ติดตามยอดขายรายวันและสินค้าขายดีได้ที่ “รายงาน”</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
