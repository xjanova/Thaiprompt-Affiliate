@extends('layouts.user-v4')

@section('title', 'ชำระเงินผ่านพร้อมเพย์')

@php
    // ── ถอดรหัส QR payload กรณีเป็น Base64 JSON (mock QR / warning) ──────────
    $qrIsImage = !empty($qrCode) && (str_starts_with($qrCode, 'data:image/') || str_starts_with($qrCode, 'https://'));
    $qrData = null;
    if (!empty($qrCode) && !$qrIsImage) {
        $qrData = json_decode(base64_decode($qrCode), true);
    }
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── หัวข้อ (Hero) ─────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:16px; padding:20px 24px;
                    background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            <a href="{{ route('user.wallet.topup') }}" class="tp-icon-btn" title="กลับ"><i class="fas fa-arrow-left"></i></a>
            <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:24px;"><i class="fas fa-mobile-alt" style="color:#fff;"></i></span>
            <div style="flex:1; min-width:200px;">
                <h1 style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">สแกน QR Code พร้อมเพย์</h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">สแกน QR Code ด้วยแอปธนาคารเพื่อชำระเงิน</div>
            </div>
        </div>
    </div>

    {{-- ── การ์ด QR Code ─────────────────────────────────────── --}}
    <div class="tp-card" style="padding:26px;">
        <div style="text-align:center;">

            {{-- รหัสรายการ --}}
            <div style="margin-bottom:18px;">
                <div style="font-size:12px; color:var(--ink2);">รหัสรายการ</div>
                <div class="tp-num" style="font-size:17px; font-weight:800; margin-top:2px;">{{ $transaction->transaction_id }}</div>
            </div>

            {{-- จำนวนเงิน --}}
            <div style="display:inline-block; margin-bottom:22px; padding:16px 28px; border-radius:18px; box-shadow:var(--inset);">
                <div style="font-size:12.5px; color:var(--ink2); margin-bottom:4px;">จำนวนเงินที่ต้องชำระ</div>
                <div class="tp-num" style="font-size:clamp(30px,6vw,42px); font-weight:800; line-height:1.1; color:var(--deep1);">฿{{ number_format($transaction->amount, 2) }}</div>
            </div>

            {{-- QR Code --}}
            <div style="margin:14px 0 22px;">
                <div style="display:inline-block; background:#ffffff; padding:24px; border-radius:20px; box-shadow:var(--inset);">
                    @if(!empty($qrCode))
                        @if($qrIsImage)
                            {{-- QR Code เป็น data URI (BaconQrCode SVG) หรือ URL (Google Charts) → ใช้เป็น src โดยตรง --}}
                            <img src="{{ $qrCode }}"
                                 alt="PromptPay QR Code"
                                 style="width:256px; height:256px; display:block;"
                                 onerror="this.parentElement.innerHTML='<div style=\'width:256px;height:256px;display:flex;align-items:center;justify-content:center;background:#f3f4f6;border-radius:12px;color:#6b7280;text-align:center;padding:16px;\'>ไม่สามารถโหลด QR Code ได้</div>'">
                        @elseif($qrData && isset($qrData['warning']))
                            {{-- Mock QR Code - แสดงข้อความแจ้งเตือน --}}
                            <div style="width:256px; height:256px; display:flex; align-items:center; justify-content:center; background:#f3f4f6; border-radius:12px;">
                                <div style="text-align:center; padding:16px;">
                                    <div style="font-size:38px; margin-bottom:10px;">📱</div>
                                    <div style="font-size:13px; color:#4b5563;">{{ $qrData['warning'] ?? 'กรุณาตั้งค่า PromptPay' }}</div>
                                    <div style="font-size:11px; color:#6b7280; margin-top:8px;">Ref: {{ $qrData['ref_no'] ?? $refNo ?? 'N/A' }}</div>
                                </div>
                            </div>
                        @else
                            {{-- Raw QR payload text → สร้าง QR ผ่าน api.qrserver.com --}}
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data={{ urlencode($qrCode) }}"
                                 alt="PromptPay QR Code"
                                 style="width:256px; height:256px; display:block;"
                                 onerror="this.parentElement.innerHTML='<div style=\'width:256px;height:256px;display:flex;align-items:center;justify-content:center;background:#f3f4f6;border-radius:12px;color:#6b7280;text-align:center;padding:16px;\'>ไม่สามารถโหลด QR Code ได้</div>'">
                        @endif
                    @else
                        <div style="width:256px; height:256px; display:flex; align-items:center; justify-content:center; background:#f3f4f6; border-radius:12px;">
                            <div style="text-align:center; padding:16px;">
                                <div style="font-size:46px; margin-bottom:10px;">⚠️</div>
                                <div style="color:#4b5563; font-weight:700;">ไม่สามารถสร้าง QR Code ได้</div>
                                <div style="font-size:13px; color:#6b7280; margin-top:8px;">กรุณาตรวจสอบการตั้งค่า PromptPay</div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- หมายเลขอ้างอิง --}}
            @if($refNo)
            <div style="margin-bottom:20px;">
                <div style="font-size:12px; color:var(--ink2);">หมายเลขอ้างอิง</div>
                <div class="tp-num" style="font-size:19px; font-weight:800; color:#5689b8; margin-top:2px;">{{ $refNo }}</div>
            </div>
            @endif

            {{-- ตัวนับเวลาถอยหลัง --}}
            <div style="display:inline-flex; align-items:center; gap:12px; margin-bottom:20px; padding:14px 20px; border-radius:14px; box-shadow:var(--inset-sm); border-left:4px solid #e0a52e;">
                <span style="font-size:24px;">⏰</span>
                <div style="text-align:left;">
                    <div style="font-size:12px; color:var(--ink2);">กรุณาชำระเงินภายใน</div>
                    <div class="tp-num" style="font-size:18px; font-weight:800; color:#e0a52e;" id="countdown">30:00</div>
                </div>
            </div>

            {{-- สถานะการชำระเงิน --}}
            <div id="status-section" style="margin-bottom:4px;">
                <span class="tp-pill" style="color:#5689b8; background:color-mix(in srgb, #5689b8 16%, transparent); font-size:13px;">
                    <i class="fas fa-circle-notch fa-spin" style="margin-right:6px;"></i>กำลังรอการชำระเงิน...
                </span>
            </div>
        </div>
    </div>

    {{-- ── คำชี้แจงทำไมยอดโอนมีจุดทศนิยม ─────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        @include('components.sms-payment-explanation')
    </div>

    {{-- ── วิธีการชำระเงิน ────────────────────────────────────── --}}
    @php
        $steps = [
            ['1', 'เปิดแอปธนาคารบนมือถือของคุณ (กสิกร, กรุงไทย, ไทยพาณิชย์, ฯลฯ)', '#5689b8'],
            ['2', 'เลือกเมนู "สแกน QR" หรือ "จ่ายบิล"', '#5689b8'],
            ['3', 'สแกน QR Code ด้านบน', '#5689b8'],
            ['4', 'ตรวจสอบจำนวนเงิน และยืนยันการโอน', '#5689b8'],
            ['✓', 'หลังโอนเงินสำเร็จ ยอดเงินจะเข้า Wallet อัตโนมัติ', '#5aa07e'],
        ];
    @endphp
    <div class="tp-card" style="padding:20px;">
        <div class="tp-section-h" style="margin-bottom:14px;">📋 วิธีการชำระเงิน</div>
        <div style="display:flex; flex-direction:column; gap:12px;">
            @foreach($steps as [$num, $text, $color])
                <div style="display:flex; align-items:flex-start; gap:12px;">
                    <span class="tp-num" style="flex-shrink:0; width:26px; height:26px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:800; color:#fff; background:{{ $color }};">{{ $num }}</span>
                    <span style="font-size:13.5px; color:var(--ink); line-height:1.5;">{{ $text }}</span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ── มีปัญหา? ──────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:18px 20px; display:flex; align-items:flex-start; gap:14px; box-shadow:var(--inset-sm); border-left:4px solid #e0a52e;">
        <span class="tp-tile" style="width:44px; height:44px; border-radius:13px; font-size:20px; background:color-mix(in srgb, #e0a52e 18%, transparent);">❓</span>
        <div>
            <div style="font-weight:800; margin-bottom:4px;">มีปัญหา?</div>
            <div style="font-size:13px; color:var(--ink2); line-height:1.6;">
                หากชำระเงินแล้วแต่ยอดเงินยังไม่เข้า กรุณารอสักครู่ ระบบจะตรวจสอบอัตโนมัติ
                หรือติดต่อทีมงานพร้อมหมายเลขอ้างอิง:
                <strong class="tp-num" style="color:var(--ink);">{{ $refNo ?? $transaction->transaction_id }}</strong>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const transactionId = '{{ $transaction->transaction_id }}';
    const countdownEl = document.getElementById('countdown');
    const statusSection = document.getElementById('status-section');

    // ── ตัวนับเวลาถอยหลัง ─────────────────────────────────
    @if($transaction->expired_at)
    const expiredAt = new Date('{{ $transaction->expired_at->toIso8601String() }}');

    function updateCountdown() {
        const now = new Date();
        const diff = expiredAt - now;

        if (diff <= 0) {
            countdownEl.textContent = 'หมดเวลาแล้ว';
            countdownEl.style.color = '#d9534f';
            statusSection.innerHTML = `
                <span class="tp-pill" style="color:#d9534f; background:color-mix(in srgb, #d9534f 16%, transparent); font-size:13px;">
                    <span style="margin-right:6px;">❌</span>รายการหมดอายุแล้ว
                </span>
            `;
            return;
        }

        const minutes = Math.floor(diff / 60000);
        const seconds = Math.floor((diff % 60000) / 1000);
        countdownEl.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    }

    updateCountdown();
    setInterval(updateCountdown, 1000);
    @endif

    // ── ตรวจสอบสถานะการชำระเงิน (polling) ──────────────────
    function checkPaymentStatus() {
        fetch('{{ route("user.wallet.topup.check", $transaction->transaction_id) }}')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.is_completed) {
                    // ชำระเงินสำเร็จ!
                    statusSection.innerHTML = `
                        <span class="tp-pill" style="color:#5aa07e; background:color-mix(in srgb, #5aa07e 16%, transparent); font-size:13px;">
                            <span style="margin-right:6px;">✅</span>ชำระเงินสำเร็จ! กำลังนำท่านไปหน้า Wallet...
                        </span>
                    `;

                    // Redirect หลังจาก 2 วินาที
                    setTimeout(() => {
                        window.location.href = '{{ route("user.wallet.index") }}?success=topup';
                    }, 2000);
                } else if (data.data && data.data.is_expired) {
                    // หมดอายุ
                    statusSection.innerHTML = `
                        <span class="tp-pill" style="color:#d9534f; background:color-mix(in srgb, #d9534f 16%, transparent); font-size:13px;">
                            <span style="margin-right:6px;">❌</span>รายการหมดอายุแล้ว
                        </span>
                    `;
                }
            })
            .catch(error => {
                console.error('Status check error:', error);
            });
    }

    // ตรวจสอบสถานะทุก 5 วินาที
    setInterval(checkPaymentStatus, 5000);
});
</script>
@endpush
@endsection
