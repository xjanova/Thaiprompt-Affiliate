@extends('layouts.seller-v4')

@section('title', 'อุปกรณ์ POS - '.$device->device_name)

@php
    $subMap = ['active' => ['ใช้งาน', 'ok'], 'trial' => ['ทดลองใช้', 'info'], 'expired' => ['หมดอายุ', 'bad'], 'suspended' => ['ระงับ', 'bad'], 'cancelled' => ['ยกเลิก', 'muted']];
    [$subLabel, $subTone] = $subMap[$device->subscription_status] ?? [$device->subscription_status ?: 'ไม่ระบุ', 'muted'];
    $payLabels = ['cash' => '💵 เงินสด', 'card' => '💳 บัตร', 'qr' => '📱 QR', 'bank_transfer' => '🏦 โอน', 'other' => '• อื่น ๆ'];
    $statusMap = ['completed' => ['สำเร็จ', 'ok'], 'refunded' => ['คืนเงิน', 'bad'], 'void' => ['ยกเลิก', 'muted'], 'pending' => ['รอดำเนินการ', 'warn']];
    $recent = $device->transactions()->latest('transaction_date')->limit(10)->get();
    $activeSession = $stats['active_session'] ?? null;

    $th = 'padding:11px 14px; text-align:left; font-size:10.5px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px; white-space:nowrap;';
    $td = 'padding:12px 14px; font-size:13px; color:var(--ink);';
    $row = 'border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);';
    $features = [
        ['🖥️', 'จอฝั่งลูกค้า (Dual Screen)', $device->dual_screen_enabled],
        ['📡', 'ใช้งานออฟไลน์', $device->offline_mode_enabled],
        ['🖨️', 'เครื่องพิมพ์ใบเสร็จ', $device->receipt_printer_enabled],
    ];
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    <x-seller-kit.header :title="$device->device_name" icon="📱" crumb="ร้านค้า · POS · อุปกรณ์" :subtitle="'รหัสอุปกรณ์ '.($device->device_code ?? '—')">
        <a href="{{ route('seller.pos.devices') }}" class="tp-btn tp-btn-sm">← อุปกรณ์ทั้งหมด</a>
    </x-seller-kit.header>

    @include('seller.pos.partials.nav')

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:14px;">
        <x-seller-kit.stat label="รายการทั้งหมด" :value="number_format($stats['total_transactions'])" icon="🧾" tone="info" />
        <x-seller-kit.stat label="รายการวันนี้" :value="number_format($stats['today_transactions'])" icon="📅" tone="ok" />
        <x-seller-kit.stat label="ยอดขายทั้งหมด" :value="'฿'.number_format($stats['total_sales'], 2)" icon="💰" tone="gold" />
        <x-seller-kit.stat label="ยอดขายวันนี้" :value="'฿'.number_format($stats['today_sales'], 2)" icon="📈" tone="violet" />
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:16px;">
        <div class="tp-card" style="display:flex; flex-direction:column; gap:10px;">
            <div class="tp-section-h">ข้อมูลอุปกรณ์</div>
            <div style="display:flex; justify-content:space-between; font-size:13px;"><span style="color:var(--ink2);">ประเภท</span><span style="font-weight:700;">{{ $device->device_type === 'premium' ? '💎 Premium' : ($device->device_type === 'web' ? '🌐 หน้าขายบนเว็บ' : '📱 '.($device->device_type ?: 'Standard')) }}</span></div>
            <div style="display:flex; justify-content:space-between; font-size:13px;"><span style="color:var(--ink2);">สถานะ</span><x-seller-kit.pill :tone="$device->is_online ? 'ok' : 'muted'">{{ $device->is_online ? '● ออนไลน์' : '○ ออฟไลน์' }}</x-seller-kit.pill></div>
            <div style="display:flex; justify-content:space-between; font-size:13px;"><span style="color:var(--ink2);">เปิดใช้งาน</span><x-seller-kit.pill :tone="$device->is_active ? 'ok' : 'bad'">{{ $device->is_active ? 'เปิด' : 'ปิด' }}</x-seller-kit.pill></div>
            @if($device->last_online_at)
                <div style="display:flex; justify-content:space-between; font-size:13px;"><span style="color:var(--ink2);">ออนไลน์ล่าสุด</span><span>{{ $device->last_online_at->diffForHumans() }}</span></div>
            @endif
            @if($device->last_transaction_at)
                <div style="display:flex; justify-content:space-between; font-size:13px;"><span style="color:var(--ink2);">ขายล่าสุด</span><span>{{ $device->last_transaction_at->diffForHumans() }}</span></div>
            @endif
        </div>

        <div class="tp-card" style="display:flex; flex-direction:column; gap:10px;">
            <div class="tp-section-h">แพ็กเกจอุปกรณ์</div>
            <div style="display:flex; justify-content:space-between; font-size:13px;"><span style="color:var(--ink2);">สถานะ</span><x-seller-kit.pill :tone="$subTone">{{ $subLabel }}</x-seller-kit.pill></div>
            @if($device->subscription_starts_at)
                <div style="display:flex; justify-content:space-between; font-size:13px;"><span style="color:var(--ink2);">เริ่ม</span><span class="tp-num">{{ $device->subscription_starts_at->format('d/m/Y') }}</span></div>
            @endif
            @if($device->subscription_ends_at)
                <div style="display:flex; justify-content:space-between; font-size:13px;">
                    <span style="color:var(--ink2);">หมดอายุ</span>
                    <span class="tp-num">{{ $device->subscription_ends_at->format('d/m/Y') }}@if($device->subscription_ends_at->isFuture()) <span style="color:var(--ink2);">(อีก {{ (int) now()->diffInDays($device->subscription_ends_at) }} วัน)</span>@endif</span>
                </div>
            @endif
            <div style="display:flex; justify-content:space-between; font-size:13px;"><span style="color:var(--ink2);">ค่าบริการรายเดือน</span><span class="tp-num" style="font-weight:800; color:var(--deep1);">฿{{ number_format((float) $device->monthly_fee, 2) }}</span></div>
            @if($device->auto_renew)<span class="tp-pill tp-pill-soft" style="align-self:flex-start;">🔄 ต่ออายุอัตโนมัติ</span>@endif
        </div>

        <div class="tp-card" style="display:flex; flex-direction:column; gap:8px;">
            <div class="tp-section-h">ฟีเจอร์</div>
            @foreach($features as [$fIcon, $fLabel, $fOn])
                <div class="tp-inset-sm" style="border-radius:12px; padding:9px 12px; display:flex; justify-content:space-between; align-items:center; font-size:13px; {{ $fOn ? '' : 'opacity:.6;' }}">
                    <span>{{ $fIcon }} {{ $fLabel }}</span>
                    <x-seller-kit.pill :tone="$fOn ? 'ok' : 'muted'">{{ $fOn ? 'เปิด' : 'ปิด' }}</x-seller-kit.pill>
                </div>
            @endforeach
        </div>
    </div>

    @if($activeSession)
        <div class="tp-card" style="border-left:4px solid var(--accent1);">
            <div style="display:flex; flex-wrap:wrap; gap:14px; align-items:center;">
                <span class="tp-tile" style="width:44px; height:44px; font-size:20px;" aria-hidden="true">🔐</span>
                <div style="flex:1; min-width:200px;">
                    <div class="tp-section-h">มีเซสชันการขายเปิดอยู่</div>
                    <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">
                        พนักงาน {{ $activeSession->user->name ?? '-' }} · เริ่ม {{ optional($activeSession->opened_at)->format('d/m/Y H:i') }}
                    </div>
                </div>
                <a href="{{ route('seller.pos.sessions.show', $activeSession) }}" class="tp-btn tp-btn-sm">ดูเซสชัน →</a>
            </div>
        </div>
    @endif

    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div class="tp-section-h" style="padding:16px 18px;">🧾 รายการขายล่าสุด (10 รายการ)</div>
        @if($recent->count() > 0)
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
                        @foreach($recent as $transaction)
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
                </table>
            </div>
        @else
            <x-seller-kit.empty icon="🧾" title="อุปกรณ์นี้ยังไม่มีรายการขาย" />
        @endif
    </div>
</div>
@endsection
