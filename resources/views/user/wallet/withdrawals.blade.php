@extends('layouts.user-v4')

@section('title', 'ประวัติการถอนเงิน')

@php
    use Illuminate\Support\Str;

    // ── map สีสถานะ (status_color) → hex สำหรับ V4 (เลี่ยง dynamic tailwind class) ──
    $wStatusColor = function ($c) {
        return match ($c) {
            'green', 'emerald' => '#5aa07e',
            'yellow', 'amber' => '#e0a52e',
            'red', 'rose' => '#d9534f',
            'blue', 'indigo' => '#5689b8',
            default => 'var(--ink2)',
        };
    };

    // ── สถิติ 4 ใบ (จาก $statistics ที่ controller ส่งมา) ──
    $wStatCards = [
        ['label' => 'ถอนทั้งหมด',   'val' => '฿' . number_format($statistics['total_amount'] ?? 0, 2), 'color' => 'var(--deep1)', 'soft' => 'color-mix(in srgb, var(--accent1) 16%, transparent)', 'icon' => '💰'],
        ['label' => 'รอดำเนินการ', 'val' => number_format($statistics['pending_count'] ?? 0),         'color' => '#e0a52e',      'soft' => 'rgba(224,165,46,.16)',  'icon' => '⏳'],
        ['label' => 'สำเร็จ',       'val' => number_format($statistics['completed_count'] ?? 0),       'color' => '#5aa07e',      'soft' => 'rgba(90,160,126,.16)',  'icon' => '✅'],
        ['label' => 'ปฏิเสธ',       'val' => number_format($statistics['rejected_count'] ?? 0),        'color' => '#d9534f',      'soft' => 'rgba(217,83,79,.16)',   'icon' => '❌'],
    ];
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── หัวข้อ (Hero) ──────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:16px; padding:20px 24px;
                    background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            <a href="{{ route('user.wallet.index') }}" class="tp-icon-btn" title="กลับ"><i class="fas fa-arrow-left"></i></a>
            <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:24px;"><i class="fas fa-clipboard-list" style="color:#fff;"></i></span>
            <div style="flex:1; min-width:200px;">
                <h1 style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">ประวัติการถอนเงิน</h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">รายการคำขอถอนเงินทั้งหมด</div>
            </div>
            <a href="{{ route('user.wallet.withdraw') }}" class="tp-btn tp-btn-primary">💸 ถอนเงิน</a>
        </div>
    </div>

    {{-- ── สถิติ 4 ใบ ───────────────────────────────────────── --}}
    @if(isset($statistics))
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:16px;">
        @foreach($wStatCards as $c)
            <div class="tp-card" style="padding:18px;">
                <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
                    <div style="font-size:12.5px; color:var(--ink2); font-weight:600;">{{ $c['label'] }}</div>
                    <span class="tp-tile" style="width:40px; height:40px; border-radius:12px; font-size:18px; background:{{ $c['soft'] }};">{{ $c['icon'] }}</span>
                </div>
                <div class="tp-num" style="font-size:26px; font-weight:800; margin-top:10px; color:{{ $c['color'] }};">{{ $c['val'] }}</div>
            </div>
        @endforeach
    </div>
    @endif

    {{-- ── ตารางคำขอถอนเงิน ─────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; align-items:center; gap:10px; padding:18px 20px; border-bottom:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
            <div class="tp-section-h">📋 รายการคำขอถอนเงิน</div>
        </div>

        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; font-size:13px;">
                <thead>
                    <tr style="text-align:left; color:var(--ink2); box-shadow:var(--inset-sm);">
                        <th style="padding:13px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; white-space:nowrap;">รหัสคำขอ</th>
                        <th style="padding:13px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; white-space:nowrap;">จำนวนเงิน</th>
                        <th style="padding:13px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; white-space:nowrap;">ช่องทางรับเงิน</th>
                        <th style="padding:13px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; text-align:center; white-space:nowrap;">สถานะ</th>
                        <th style="padding:13px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; text-align:right; white-space:nowrap;">วันที่สร้าง</th>
                        <th style="padding:13px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; text-align:center; white-space:nowrap;">การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($withdrawals as $withdrawal)
                        @php $stColor = $wStatusColor($withdrawal->status_color); @endphp
                        <tr style="border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                            {{-- รหัสคำขอ --}}
                            <td style="padding:12px 16px; vertical-align:top;">
                                <div class="tp-num" style="font-size:12.5px; font-weight:700; color:var(--ink);">{{ $withdrawal->request_id }}</div>
                                @if($withdrawal->user_note)
                                    <div style="font-size:11px; color:var(--ink2); margin-top:2px;">{{ Str::limit($withdrawal->user_note, 30) }}</div>
                                @endif
                            </td>
                            {{-- จำนวนเงิน --}}
                            <td style="padding:12px 16px; vertical-align:top; white-space:nowrap;">
                                <div class="tp-num" style="font-size:13px; font-weight:800; color:var(--ink);">฿{{ number_format($withdrawal->amount, 2) }}</div>
                                @if($withdrawal->fee > 0)
                                    <div class="tp-num" style="font-size:11px; color:var(--ink2);">ค่าธรรมเนียม: ฿{{ number_format($withdrawal->fee, 2) }}</div>
                                @endif
                                @if($withdrawal->net_amount != $withdrawal->amount)
                                    <div class="tp-num" style="font-size:11px; font-weight:700; color:#5aa07e;">รับสุทธิ: ฿{{ number_format($withdrawal->net_amount, 2) }}</div>
                                @endif
                            </td>
                            {{-- ช่องทางรับเงิน --}}
                            <td style="padding:12px 16px; vertical-align:top;">
                                @if($withdrawal->paymentMethod)
                                    <div style="font-size:12.5px; font-weight:600; color:var(--ink);">
                                        @if($withdrawal->payment_type === 'bank_transfer')
                                            🏦 {{ $withdrawal->paymentMethod->bank_name }}
                                        @elseif($withdrawal->payment_type === 'promptpay')
                                            💳 PromptPay
                                        @elseif($withdrawal->payment_type === 'paypal')
                                            💰 PayPal
                                        @else
                                            {{ $withdrawal->payment_type_label }}
                                        @endif
                                    </div>
                                    <div style="font-size:11px; color:var(--ink2);">{{ $withdrawal->paymentMethod->name }}</div>
                                @else
                                    <div style="font-size:12.5px; color:var(--ink2);">-</div>
                                @endif
                            </td>
                            {{-- สถานะ --}}
                            <td style="padding:12px 16px; text-align:center; vertical-align:top;">
                                <span class="tp-pill" style="color:{{ $stColor }}; background:color-mix(in srgb, {{ $stColor }} 16%, transparent);">{{ $withdrawal->status_label }}</span>
                                @if($withdrawal->rejection_reason)
                                    <div style="font-size:11px; color:#d9534f; margin-top:4px;">{{ Str::limit($withdrawal->rejection_reason, 30) }}</div>
                                @endif
                            </td>
                            {{-- วันที่สร้าง --}}
                            <td class="tp-num" style="padding:12px 16px; text-align:right; vertical-align:top; white-space:nowrap;">
                                <div style="color:var(--ink);">{{ $withdrawal->created_at->format('d/m/Y') }}</div>
                                <div style="font-size:11px; color:var(--ink2);">{{ $withdrawal->created_at->format('H:i') }}</div>
                                @if($withdrawal->completed_at)
                                    <div style="font-size:11px; color:#5aa07e; margin-top:2px;">เสร็จ: {{ $withdrawal->completed_at->format('d/m/Y') }}</div>
                                @endif
                            </td>
                            {{-- การจัดการ --}}
                            <td style="padding:12px 16px; text-align:center; vertical-align:top; white-space:nowrap;">
                                @if($withdrawal->isPending())
                                    <form method="POST" action="{{ route('user.wallet.withdrawal.cancel', $withdrawal->id) }}" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                onclick="return confirm('คุณแน่ใจหรือไม่ที่จะยกเลิกคำขอนี้?')"
                                                class="tp-btn tp-btn-sm"
                                                style="color:#d9534f; background:color-mix(in srgb, #d9534f 14%, transparent);">
                                            ยกเลิก
                                        </button>
                                    </form>
                                @elseif($withdrawal->transfer_slip_url)
                                    <a href="{{ $withdrawal->transfer_slip_url }}"
                                       target="_blank"
                                       class="tp-btn tp-btn-sm"
                                       style="color:#5689b8; background:rgba(86,137,184,.14);">
                                        ดูสลิป
                                    </a>
                                @else
                                    <span style="font-size:11px; color:var(--ink2);">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding:56px 20px; text-align:center; color:var(--ink2);">
                                <div style="font-size:52px; opacity:.5;">📭</div>
                                <div style="font-weight:700; font-size:15px; margin-top:8px;">ยังไม่มีรายการคำขอถอนเงิน</div>
                                <a href="{{ route('user.wallet.withdraw') }}" class="tp-btn tp-btn-primary tp-btn-sm" style="margin-top:14px;">สร้างคำขอถอนเงิน</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ── Pagination ──────────────────────────────────────── --}}
        @if($withdrawals->hasPages())
            <div style="padding:14px 16px; border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                {{ $withdrawals->links() }}
            </div>
        @endif
    </div>

    {{-- ── ข้อมูลเพิ่มเติม ─────────────────────────────────────── --}}
    <div class="tp-card" style="padding:20px;">
        <div class="tp-section-h" style="margin-bottom:14px;">ℹ️ ข้อมูลเพิ่มเติม</div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:18px;">
            <div style="padding:14px 16px; border-radius:14px; box-shadow:var(--inset-sm);">
                <div style="font-weight:700; font-size:13px; margin-bottom:8px;">สถานะการถอนเงิน</div>
                <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:6px; font-size:12.5px; color:var(--ink2);">
                    <li>🟡 <strong style="color:var(--ink);">รอดำเนินการ</strong> - คำขอยังไม่ได้รับการตรวจสอบ</li>
                    <li>🔵 <strong style="color:var(--ink);">กำลังดำเนินการ</strong> - กำลังโอนเงิน</li>
                    <li>🟢 <strong style="color:var(--ink);">สำเร็จ</strong> - โอนเงินสำเร็จแล้ว</li>
                    <li>🔴 <strong style="color:var(--ink);">ถูกปฏิเสธ</strong> - คำขอถูกปฏิเสธ</li>
                </ul>
            </div>
            <div style="padding:14px 16px; border-radius:14px; box-shadow:var(--inset-sm);">
                <div style="font-weight:700; font-size:13px; margin-bottom:8px;">ระยะเวลาดำเนินการ</div>
                <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:6px; font-size:12.5px; color:var(--ink2);">
                    <li>• คำขอจะได้รับการตรวจสอบภายใน 24 ชั่วโมง</li>
                    <li>• การโอนเงินใช้เวลา 1-3 วันทำการ</li>
                    <li>• คุณสามารถยกเลิกคำขอที่ยังรอดำเนินการได้</li>
                    <li>• ติดต่อฝ่ายสนับสนุนหากมีปัญหา</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
