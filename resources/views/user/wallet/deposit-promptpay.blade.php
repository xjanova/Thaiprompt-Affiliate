@extends('layouts.user-v4')

@section('title', 'ชำระเงินผ่าน PromptPay')

@php
    // ── เตรียมค่า QR Code (รองรับ data URI / URL / raw payload) ──────────
    $qrCode = $result['qr_code'] ?? '';
    $qrIsDirect = !empty($qrCode) && (str_starts_with($qrCode, 'data:image/') || str_starts_with($qrCode, 'https://'));
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── หัวข้อ (Hero) ─────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:16px; padding:20px 24px;
                    background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            @if(\Illuminate\Support\Facades\Route::has('user.wallet.deposit'))
                <a href="{{ route('user.wallet.deposit') }}" class="tp-icon-btn" title="กลับ"><i class="fas fa-arrow-left"></i></a>
            @endif
            <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:24px;"><i class="fas fa-mobile-alt" style="color:#fff;"></i></span>
            <div style="flex:1; min-width:200px;">
                <h1 style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">ชำระเงินผ่าน PromptPay</h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">สแกน QR Code เพื่อชำระเงิน</div>
            </div>
        </div>
    </div>

    {{-- ── ข้อมูลรายการฝากเงิน ───────────────────────────────── --}}
    <div class="tp-card" style="padding:24px;">
        <div style="text-align:center;">
            <span class="tp-tile" style="width:60px; height:60px; border-radius:18px; font-size:26px; margin:0 auto; background:color-mix(in srgb, #5aa07e 18%, transparent);"><i class="fas fa-check-circle" style="color:#5aa07e;"></i></span>
            <h2 style="font-size:clamp(18px,3.5vw,22px); font-weight:800; margin:14px 0 4px;">รายการฝากเงินของคุณ</h2>
            <div style="font-size:13px; color:var(--ink2);">กรุณาสแกน QR Code ด้านล่างเพื่อชำระเงิน</div>
        </div>

        <div style="margin-top:18px; padding:20px; border-radius:18px; box-shadow:var(--inset);">
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:18px;">
                <div>
                    <div style="font-size:11.5px; font-weight:600; color:var(--ink2); margin-bottom:4px;">จำนวนเงิน</div>
                    <div class="tp-num" style="font-size:clamp(26px,5vw,32px); font-weight:800; color:var(--deep1);">฿{{ number_format($result['amount'], 2) }}</div>
                </div>
                <div>
                    <div style="font-size:11.5px; font-weight:600; color:var(--ink2); margin-bottom:4px;">รหัสอ้างอิง</div>
                    <div class="tp-num" style="font-size:16px; font-weight:700;">{{ $result['reference'] }}</div>
                </div>
                <div>
                    <div style="font-size:11.5px; font-weight:600; color:var(--ink2); margin-bottom:4px;">วิธีชำระเงิน</div>
                    <div style="font-size:15px; font-weight:700;">{{ $result['payment_method'] === 'promptpay' ? 'พร้อมเพย์' : $result['payment_method'] }}</div>
                </div>
                <div>
                    <div style="font-size:11.5px; font-weight:600; color:var(--ink2); margin-bottom:4px;">สถานะ</div>
                    <span class="tp-pill" style="color:#e0a52e; background:color-mix(in srgb, #e0a52e 16%, transparent);">{{ $result['status'] === 'pending' ? 'รอการชำระเงิน' : $result['status'] }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ── QR Code ──────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:24px;">
        <div class="tp-section-h" style="text-align:center; margin-bottom:18px;">สแกน QR Code เพื่อชำระเงิน</div>

        <div style="display:flex; flex-direction:column; align-items:center;">
            {{-- กรอบ QR Code (พื้นขาวเสมอเพื่อให้สแกนได้ทุกธีม) --}}
            <div style="background:#fff; padding:20px; border-radius:20px; box-shadow:var(--inset); border:4px solid color-mix(in srgb, #5aa07e 35%, transparent); display:inline-block;">
                @if($qrIsDirect)
                    {{-- QR Code เป็น data URI (BaconQrCode) หรือ URL (Google Charts) → ใช้เป็น src โดยตรง --}}
                    <img src="{{ $qrCode }}"
                         alt="PromptPay QR Code"
                         style="width:256px; height:256px; max-width:80vw;"
                         onerror="this.parentElement.innerHTML='<div style=\'width:256px;height:256px;display:flex;align-items:center;justify-content:center;background:#f3f4f6;border-radius:12px;\'><p style=\'color:#6b7280;text-align:center;padding:16px;\'>ไม่สามารถโหลด QR Code ได้</p></div>'">
                @elseif(!empty($qrCode))
                    {{-- QR Code เป็น raw payload text → สร้างผ่าน api.qrserver.com --}}
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data={{ urlencode($qrCode) }}"
                         alt="PromptPay QR Code"
                         style="width:256px; height:256px; max-width:80vw;"
                         onerror="this.parentElement.innerHTML='<div style=\'width:256px;height:256px;display:flex;align-items:center;justify-content:center;background:#f3f4f6;border-radius:12px;\'><p style=\'color:#6b7280;text-align:center;padding:16px;\'>ไม่สามารถโหลด QR Code ได้</p></div>'">
                @else
                    {{-- ไม่มี QR Code → แสดงข้อความแจ้ง --}}
                    <div style="width:256px; height:256px; max-width:80vw; background:#f3f4f6; display:flex; align-items:center; justify-content:center; border-radius:12px;">
                        <div style="text-align:center; padding:16px;">
                            <div style="font-size:42px; margin-bottom:10px;">⚠️</div>
                            <div style="color:#374151; font-weight:700;">ไม่สามารถสร้าง QR Code ได้</div>
                            <div style="font-size:12px; color:#6b7280; margin-top:8px;">กรุณาตรวจสอบการตั้งค่า PromptPay<br>หรือติดต่อผู้ดูแลระบบ</div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- รหัสอ้างอิง + ปุ่มคัดลอก --}}
            <div style="margin-top:18px; text-align:center;">
                <div style="font-size:12.5px; color:var(--ink2); margin-bottom:8px;">หรือใช้รหัสอ้างอิงนี้</div>
                <div style="display:flex; align-items:center; justify-content:center; gap:8px;">
                    <code class="tp-num" style="padding:9px 16px; border-radius:11px; box-shadow:var(--inset-sm); font-size:16px; font-weight:700; color:var(--deep1);">{{ $result['reference'] }}</code>
                    <button type="button" onclick="copyReference()" class="tp-icon-btn" title="คัดลอกรหัสอ้างอิง"><i class="fas fa-copy"></i></button>
                </div>
            </div>

            {{-- ตัวนับเวลาถอยหลัง --}}
            <div style="margin-top:18px; text-align:center;">
                <div style="font-size:12.5px; color:var(--ink2); margin-bottom:6px;">QR Code หมดอายุใน</div>
                <div id="countdown" class="tp-num" style="font-size:clamp(26px,6vw,32px); font-weight:800; color:#d9534f;">15:00</div>
            </div>
        </div>
    </div>

    {{-- ── คำชี้แจงทำไมยอดโอนมีจุดทศนิยม ─────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        @include('components.sms-payment-explanation')
    </div>

    {{-- ── วิธีการชำระเงิน ───────────────────────────────────── --}}
    @php
        $steps = [
            ['1', 'เปิดแอปธนาคารหรือแอปพร้อมเพย์', 'เช่น Mobile Banking, TrueMoney Wallet, Rabbit LINE Pay, ฯลฯ', 'var(--deep1)'],
            ['2', 'เลือกสแกน QR Code', 'ใช้กล้องในแอปสแกน QR Code ด้านบน', 'var(--deep1)'],
            ['3', 'ตรวจสอบจำนวนเงิน', 'ตรวจสอบว่าจำนวนเงินถูกต้อง: ฿' . number_format($result['amount'], 2), 'var(--deep1)'],
            ['4', 'ยืนยันการชำระเงิน', 'กดยืนยันการโอนเงินในแอป', 'var(--deep1)'],
            ['✓', 'รอการตรวจสอบ', 'ระบบจะตรวจสอบการชำระเงินอัตโนมัติภายใน 1-5 นาที', '#5aa07e'],
        ];
    @endphp
    <div class="tp-card" style="padding:24px;">
        <div class="tp-section-h" style="margin-bottom:18px;">📱 วิธีการชำระเงิน</div>
        <div style="display:flex; flex-direction:column; gap:16px;">
            @foreach($steps as [$no, $title, $desc, $color])
                <div style="display:flex; align-items:flex-start; gap:14px;">
                    <span class="tp-tile" style="flex-shrink:0; width:40px; height:40px; border-radius:13px; font-size:16px; font-weight:800; background:color-mix(in srgb, {{ $color }} 16%, transparent); color:{{ $color }};">{{ $no }}</span>
                    <div>
                        <div style="font-weight:700; font-size:14px;">{{ $title }}</div>
                        <div style="font-size:12.5px; color:var(--ink2); margin-top:2px;">{{ $desc }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ── ตรวจสอบสถานะการชำระเงิน ───────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:20px 24px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            <div class="tp-section-h" style="margin-bottom:4px;">ตรวจสอบสถานะการชำระเงิน</div>
            <div style="font-size:12.5px; color:var(--ink2);">กดปุ่มด้านล่างเพื่อตรวจสอบสถานะหลังจากโอนเงินแล้ว</div>
            <div style="display:flex; flex-wrap:wrap; gap:12px; margin-top:16px;">
                <button type="button" onclick="checkPaymentStatus()" id="check-status-btn" class="tp-btn tp-btn-primary" style="flex:1; min-width:180px; justify-content:center;">
                    <i class="fas fa-search"></i> ตรวจสอบสถานะ
                </button>
                @if(\Illuminate\Support\Facades\Route::has('user.wallet.index'))
                    <a href="{{ route('user.wallet.index') }}" class="tp-btn" style="flex:1; min-width:180px; justify-content:center;">กลับหน้าหลัก</a>
                @endif
            </div>
        </div>
    </div>

    {{-- ── คำเตือน ───────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:16px 18px; display:flex; align-items:flex-start; gap:14px; box-shadow:var(--inset-sm); border-left:4px solid #e0a52e;">
        <span class="tp-tile" style="flex-shrink:0; width:40px; height:40px; border-radius:13px; font-size:18px; background:color-mix(in srgb, #e0a52e 18%, transparent);"><i class="fas fa-exclamation-triangle" style="color:#e0a52e;"></i></span>
        <div style="font-size:12.5px; color:var(--ink); line-height:1.6;">
            <strong>คำเตือน:</strong> QR Code นี้จะหมดอายุใน 15 นาที หากหมดอายุ กรุณาสร้างรายการใหม่
        </div>
    </div>
</div>

@push('scripts')
<script>
// Countdown Timer — ใช้ JavaScript Date เพื่อความแม่นยำ (ไม่พึ่ง server-side diffInSeconds)
const expiresAt = new Date(@json($result['expires_at']->toIso8601String()));

function updateCountdown() {
    const now = new Date();
    const diff = expiresAt - now; // milliseconds
    const el = document.getElementById('countdown');
    if (!el) return;

    if (diff <= 0) {
        el.textContent = 'หมดเวลา';
        el.style.color = 'var(--ink2)';
        return;
    }

    const minutes = Math.floor(diff / 60000);
    const seconds = Math.floor((diff % 60000) / 1000);
    el.textContent =
        `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;

    if (diff <= 60000) {
        el.style.opacity = (el.style.opacity === '0.5') ? '1' : '0.5';
    }

    setTimeout(updateCountdown, 1000);
}

updateCountdown();

// Copy Reference — คัดลอกรหัสอ้างอิง
function copyReference() {
    const reference = @json($result['reference']);
    navigator.clipboard.writeText(reference).then(() => {
        if (window.showNotification) {
            window.showNotification('คัดลอกรหัสอ้างอิงสำเร็จ!', 'success');
        }
    }).catch(() => {
        if (window.showNotification) {
            window.showNotification('คัดลอกไม่สำเร็จ', 'error');
        }
    });
}

// Check Payment Status — ไปยัง endpoint ตรวจสอบการชำระเงิน
function checkPaymentStatus() {
    const btn = document.getElementById('check-status-btn');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> กำลังตรวจสอบ...';
    }

    // Redirect to verify endpoint
    window.location.href = @json(route('user.wallet.deposit.verify', $result['reference']));
}

// Auto-check every 30 seconds
setInterval(() => {
    // In production, use AJAX to check status without page reload
    console.log('Auto-checking payment status...');
}, 30000);
</script>
@endpush
@endsection
