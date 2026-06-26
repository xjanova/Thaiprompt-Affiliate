@extends('layouts.user-v4')

@section('title', 'สถานะการเติมเงิน')

@php
    // ── แมปสถานะ → สี/ไอคอน/ข้อความ (ใช้ hex แทน dynamic tailwind ใน V4) ──
    $st = $transaction->status;
    $statusMap = [
        'completed'  => ['#5aa07e', '✅', 'ชำระเงินสำเร็จ!'],
        'processing' => ['#5689b8', '⏳', 'กำลังดำเนินการ...'],
        'pending'    => ['#e0a52e', '⏳', 'รอการชำระเงิน'],
        'failed'     => ['#d9534f', '❌', 'การชำระเงินล้มเหลว'],
        'cancelled'  => ['var(--ink2)', '🚫', 'ยกเลิกแล้ว'],
    ];
    [$stColor, $stEmoji, $stLabel] = $statusMap[$st] ?? ['var(--ink2)', '❓', $st];

    // ── ป้ายวิธีชำระเงิน ──
    $payMethodMap = [
        'promptpay'     => '💳 พร้อมเพย์',
        'bank_transfer' => '🏦 โอนธนาคาร',
        'credit_card'   => '💳 บัตรเครดิต',
    ];
    $payMethodLabel = $payMethodMap[$transaction->payment_method] ?? $transaction->payment_method;
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px; max-width:760px; margin:0 auto;">

    {{-- ── Hero ──────────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:16px; padding:20px 24px;
                    background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            <a href="{{ route('user.wallet.index') }}" class="tp-icon-btn" title="กลับ"><i class="fas fa-arrow-left"></i></a>
            <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:24px;"><i class="fas fa-chart-line" style="color:#fff;"></i></span>
            <div style="flex:1; min-width:200px;">
                <h1 style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">สถานะการเติมเงิน</h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">ตรวจสอบสถานะรายการเติมเงินของคุณ</div>
            </div>
        </div>
    </div>

    {{-- ── การ์ดสถานะธุรกรรม ─────────────────────────────────── --}}
    <div class="tp-card" style="padding:24px;">
        <div style="text-align:center;">
            {{-- ไอคอนสถานะ --}}
            <div style="margin-bottom:18px;">
                <div style="width:96px; height:96px; margin:0 auto; border-radius:50%; display:flex; align-items:center; justify-content:center;
                            font-size:46px; background:color-mix(in srgb, {{ $stColor }} 16%, transparent);">
                    @if($st === 'processing')
                        <i class="fas fa-circle-notch fa-spin" style="color:{{ $stColor }}; font-size:42px;"></i>
                    @else
                        {{ $stEmoji }}
                    @endif
                </div>
                <h2 style="margin:16px 0 0; font-size:24px; font-weight:800; color:{{ $stColor }};">{{ $stLabel }}</h2>
            </div>

            {{-- รายละเอียดธุรกรรม --}}
            <div style="max-width:440px; margin:0 auto; display:flex; flex-direction:column; gap:12px; text-align:left;">
                <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; padding:13px 15px; border-radius:13px; box-shadow:var(--inset-sm);">
                    <span style="font-size:12.5px; color:var(--ink2);">รหัสรายการ</span>
                    <span class="tp-num" style="font-weight:800; color:var(--ink);">{{ $transaction->transaction_id }}</span>
                </div>

                <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; padding:13px 15px; border-radius:13px; box-shadow:var(--inset-sm);">
                    <span style="font-size:12.5px; color:var(--ink2);">จำนวนเงิน</span>
                    <span class="tp-num" style="font-size:24px; font-weight:800; color:#5aa07e;">฿{{ number_format($transaction->amount, 2) }}</span>
                </div>

                <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; padding:13px 15px; border-radius:13px; box-shadow:var(--inset-sm);">
                    <span style="font-size:12.5px; color:var(--ink2);">วิธีชำระเงิน</span>
                    <span style="font-size:13px; font-weight:600; color:var(--ink);">{{ $payMethodLabel }}</span>
                </div>

                <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; padding:13px 15px; border-radius:13px; box-shadow:var(--inset-sm);">
                    <span style="font-size:12.5px; color:var(--ink2);">สร้างเมื่อ</span>
                    <span class="tp-num" style="font-size:13px; color:var(--ink);">{{ $transaction->created_at->format('d/m/Y H:i:s') }}</span>
                </div>

                @if($transaction->paid_at)
                <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; padding:13px 15px; border-radius:13px; box-shadow:var(--inset-sm); border-left:4px solid #5aa07e;">
                    <span style="font-size:12.5px; color:#5aa07e;">ชำระเงินเมื่อ</span>
                    <span class="tp-num" style="font-size:13px; font-weight:600; color:#5aa07e;">{{ $transaction->paid_at->format('d/m/Y H:i:s') }}</span>
                </div>
                @endif

                @if($transaction->expired_at && !$transaction->isCompleted())
                <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; padding:13px 15px; border-radius:13px; box-shadow:var(--inset-sm); border-left:4px solid #e0a52e;">
                    <span style="font-size:12.5px; color:#e0a52e;">หมดอายุเมื่อ</span>
                    <span class="tp-num" style="font-size:13px; font-weight:600; color:#e0a52e;" id="expiry-time">{{ $transaction->expired_at->format('d/m/Y H:i:s') }}</span>
                </div>
                @endif
            </div>

            {{-- ปุ่มดำเนินการ --}}
            <div style="margin-top:24px; display:flex; flex-wrap:wrap; gap:12px; justify-content:center;">
                @if($transaction->status === 'pending' && !$transaction->isExpired())
                    <a href="{{ route('user.wallet.topup.payment', $transaction->transaction_id) }}" class="tp-btn tp-btn-primary">💳 ดำเนินการชำระเงิน</a>
                @endif

                @if($transaction->status === 'completed')
                    <a href="{{ route('user.wallet.index') }}" class="tp-btn tp-btn-primary">👛 ไปยัง Wallet</a>
                @endif

                @if(in_array($transaction->status, ['failed', 'cancelled']) || $transaction->isExpired())
                    <a href="{{ route('user.wallet.topup') }}" class="tp-btn tp-btn-primary">🔄 สร้างรายการใหม่</a>
                @endif

                <a href="{{ route('user.wallet.index') }}" class="tp-btn">← กลับหน้าหลัก</a>
            </div>
        </div>
    </div>
</div>

@if(in_array($transaction->status, ['pending', 'processing']))
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ตรวจสอบสถานะอัตโนมัติสำหรับรายการที่ pending/processing
    function checkStatus() {
        fetch('{{ route("user.wallet.topup.check", $transaction->transaction_id) }}')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.is_completed) {
                    // โหลดหน้าใหม่เพื่อแสดงผลสำเร็จ
                    window.location.reload();
                }
            })
            .catch(error => console.error('Status check error:', error));
    }

    // ตรวจสอบทุก 5 วินาที
    setInterval(checkStatus, 5000);
});
</script>
@endpush
@endif
@endsection
