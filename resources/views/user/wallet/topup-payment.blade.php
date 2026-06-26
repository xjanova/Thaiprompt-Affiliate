@extends('layouts.user-v4')

@section('title', 'ชำระเงินเติมเงิน')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── Hero: เลือกวิธีชำระเงิน ─────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:16px; padding:20px 24px;
                    background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            <a href="{{ route('user.wallet.topup') }}" class="tp-icon-btn" title="กลับ"><i class="fas fa-arrow-left"></i></a>
            <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:24px;"><i class="fas fa-hand-holding-usd" style="color:#fff;"></i></span>
            <div style="flex:1; min-width:200px;">
                <h1 style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">เลือกวิธีชำระเงิน</h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">เลือกช่องทางการชำระเงินที่สะดวกสำหรับคุณ</div>
            </div>
        </div>
    </div>

    {{-- ── ข้อมูลรายการ ─────────────────────────────────────── --}}
    <div class="tp-card" style="padding:18px;">
        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:16px;">
            <div style="min-width:0;">
                <div style="font-size:11.5px; font-weight:600; color:var(--ink2);">รหัสรายการ</div>
                <div class="tp-num" style="font-size:17px; font-weight:800; margin-top:2px;">{{ $transaction->transaction_id }}</div>
            </div>
            <div style="text-align:right;">
                <div style="font-size:11.5px; font-weight:600; color:var(--ink2);">จำนวนเงินที่ต้องชำระ</div>
                <div class="tp-num" style="font-size:clamp(26px,5vw,34px); font-weight:800; line-height:1.1; margin-top:2px; color:var(--deep1);">฿{{ number_format($transaction->amount, 2) }}</div>
            </div>
        </div>

        {{-- นับเวลาถอยหลัง --}}
        <div style="margin-top:16px; padding:14px 16px; border-radius:14px; box-shadow:var(--inset-sm); border-left:4px solid #e0a52e; display:flex; align-items:center; gap:12px;">
            <span style="font-size:24px;">⏰</span>
            <div>
                <div style="font-size:12px; color:var(--ink2);">กรุณาชำระเงินภายใน</div>
                <div class="tp-num" id="countdown" style="font-size:17px; font-weight:800; color:#e0a52e;">
                    {{ $transaction->expired_at ? $transaction->expired_at->diffForHumans() : '30 นาที' }}
                </div>
            </div>
        </div>
    </div>

    {{-- ── เลือกช่องทางชำระเงิน ──────────────────────────────── --}}
    <div class="tp-card" style="padding:18px;">
        <div class="tp-section-h" style="margin-bottom:16px;">เลือกช่องทางชำระเงิน</div>

        <form action="{{ route('user.wallet.topup.payment.process', $transaction->transaction_id) }}" method="POST" id="payment-form">
            @csrf

            <div style="display:flex; flex-direction:column; gap:12px;">
                @forelse($paymentGateways as $gateway)
                    <label class="payment-option" style="display:block; cursor:pointer;">
                        <input type="radio"
                               name="payment_method"
                               value="{{ $gateway->code }}"
                               class="peer"
                               style="position:absolute; opacity:0; pointer-events:none;"
                               required>
                        <div class="po-box" style="padding:14px 16px; border-radius:14px; box-shadow:var(--inset-sm); transition:all .15s;">
                            <div style="display:flex; align-items:center; gap:14px;">
                                <div style="font-size:28px; line-height:1;" >{{ $gateway->icon ?? '💳' }}</div>
                                <div style="flex:1; min-width:0;">
                                    <div style="font-weight:700; font-size:14px;">{{ $gateway->name }}</div>
                                    <div style="font-size:12px; color:var(--ink2); margin-top:1px;">{{ $gateway->description }}</div>
                                    @if($gateway->fees && isset($gateway->fees['deposit_fee_amount']) && $gateway->fees['deposit_fee_amount'] > 0)
                                        <div style="font-size:11px; color:#e0a52e; margin-top:3px;">
                                            ค่าธรรมเนียม: {{ $gateway->fees['deposit_fee_type'] === 'percentage' ? $gateway->fees['deposit_fee_amount'] . '%' : '฿' . number_format($gateway->fees['deposit_fee_amount'], 2) }}
                                        </div>
                                    @else
                                        <div style="font-size:11px; color:#5aa07e; margin-top:3px;">ไม่มีค่าธรรมเนียม</div>
                                    @endif
                                </div>
                                <span class="po-check" style="width:24px; height:24px; border-radius:50%; box-shadow:var(--inset-sm); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                    <i class="fas fa-check" style="font-size:11px; color:#fff; opacity:0;"></i>
                                </span>
                            </div>
                        </div>
                    </label>
                @empty
                    {{-- ── ช่องทางสำรอง ── --}}
                    <label class="payment-option" style="display:block; cursor:pointer;">
                        <input type="radio" name="payment_method" value="promptpay" class="peer" style="position:absolute; opacity:0; pointer-events:none;" required>
                        <div class="po-box" style="padding:14px 16px; border-radius:14px; box-shadow:var(--inset-sm); transition:all .15s;">
                            <div style="display:flex; align-items:center; gap:14px;">
                                <div style="font-size:28px; line-height:1;">💳</div>
                                <div style="flex:1; min-width:0;">
                                    <div style="font-weight:700; font-size:14px;">พร้อมเพย์ (PromptPay)</div>
                                    <div style="font-size:12px; color:var(--ink2); margin-top:1px;">ชำระเงินผ่าน QR Code พร้อมเพย์</div>
                                    <div style="font-size:11px; color:#5aa07e; margin-top:3px;">ไม่มีค่าธรรมเนียม</div>
                                </div>
                                <span class="po-check" style="width:24px; height:24px; border-radius:50%; box-shadow:var(--inset-sm); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                    <i class="fas fa-check" style="font-size:11px; color:#fff; opacity:0;"></i>
                                </span>
                            </div>
                        </div>
                    </label>

                    <label class="payment-option" style="display:block; cursor:pointer;">
                        <input type="radio" name="payment_method" value="bank_transfer" class="peer" style="position:absolute; opacity:0; pointer-events:none;">
                        <div class="po-box" style="padding:14px 16px; border-radius:14px; box-shadow:var(--inset-sm); transition:all .15s;">
                            <div style="display:flex; align-items:center; gap:14px;">
                                <div style="font-size:28px; line-height:1;">🏦</div>
                                <div style="flex:1; min-width:0;">
                                    <div style="font-weight:700; font-size:14px;">โอนผ่านธนาคาร</div>
                                    <div style="font-size:12px; color:var(--ink2); margin-top:1px;">โอนเงินผ่านธนาคารและอัพโหลดสลิป</div>
                                    <div style="font-size:11px; color:#5aa07e; margin-top:3px;">ไม่มีค่าธรรมเนียม</div>
                                </div>
                                <span class="po-check" style="width:24px; height:24px; border-radius:50%; box-shadow:var(--inset-sm); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                    <i class="fas fa-check" style="font-size:11px; color:#fff; opacity:0;"></i>
                                </span>
                            </div>
                        </div>
                    </label>
                @endforelse
            </div>

            {{-- ปุ่มยืนยัน --}}
            <button type="submit" id="submit-btn" disabled
                    class="tp-btn tp-btn-primary"
                    style="width:100%; margin-top:18px; padding:14px 20px; font-size:15px; font-weight:800;">
                <span id="btn-text">กรุณาเลือกวิธีชำระเงิน</span>
                <span id="btn-loading" style="display:none;">
                    <i class="fas fa-circle-notch fa-spin" style="margin-right:6px;"></i>กำลังดำเนินการ...
                </span>
            </button>
        </form>
    </div>

    {{-- ── ความปลอดภัย ──────────────────────────────────────── --}}
    <div class="tp-card" style="padding:18px; display:flex; align-items:flex-start; gap:14px; box-shadow:var(--inset-sm); border-left:4px solid #5689b8;">
        <span class="tp-tile" style="width:44px; height:44px; border-radius:13px; font-size:20px; background:color-mix(in srgb, #5689b8 18%, transparent);"><i class="fas fa-lock" style="color:#5689b8;"></i></span>
        <div>
            <div style="font-weight:700; font-size:15px; margin-bottom:6px;">ความปลอดภัย</div>
            <ul style="margin:0; padding-left:18px; font-size:12.5px; color:var(--ink2); display:flex; flex-direction:column; gap:4px; list-style:disc;">
                <li>ข้อมูลการชำระเงินของคุณถูกเข้ารหัสและปลอดภัย</li>
                <li>เงินจะเข้า Wallet ทันทีหลังชำระเงินสำเร็จ</li>
                <li>หากมีปัญหา สามารถติดต่อทีมงานได้ตลอด 24 ชั่วโมง</li>
            </ul>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('payment-form');
    const submitBtn = document.getElementById('submit-btn');
    const btnText = document.getElementById('btn-text');
    const btnLoading = document.getElementById('btn-loading');
    const paymentOptions = document.querySelectorAll('input[name="payment_method"]');
    const amountText = @json('฿' . number_format($transaction->amount, 2));

    // อัพเดทสไตล์การ์ดที่ถูกเลือก (แทน peer-checked ของ tailwind)
    function refreshSelection() {
        paymentOptions.forEach(function(opt) {
            const label = opt.closest('.payment-option');
            if (!label) return;
            const box = label.querySelector('.po-box');
            const check = label.querySelector('.po-check');
            const checkIcon = check ? check.querySelector('i') : null;
            if (opt.checked) {
                if (box) {
                    box.style.boxShadow = 'none';
                    box.style.background = 'color-mix(in srgb, var(--accent1) 16%, transparent)';
                    box.style.outline = '2px solid var(--accent1)';
                }
                if (check) check.style.background = 'var(--accent2)';
                if (checkIcon) checkIcon.style.opacity = '1';
            } else {
                if (box) {
                    box.style.boxShadow = 'var(--inset-sm)';
                    box.style.background = 'transparent';
                    box.style.outline = 'none';
                }
                if (check) check.style.background = 'transparent';
                if (checkIcon) checkIcon.style.opacity = '0';
            }
        });
    }

    // เปิดปุ่มเมื่อเลือกวิธีชำระเงิน
    paymentOptions.forEach(function(option) {
        option.addEventListener('change', function() {
            submitBtn.disabled = false;
            btnText.textContent = 'ดำเนินการชำระเงิน ' + amountText;
            refreshSelection();
        });
    });

    // แสดง loading เมื่อ submit
    form.addEventListener('submit', function() {
        submitBtn.disabled = true;
        btnText.style.display = 'none';
        btnLoading.style.display = 'inline';
    });

    // นับเวลาถอยหลัง
    @if($transaction->expired_at)
    const expiredAt = new Date(@json($transaction->expired_at->toIso8601String()));
    const countdownEl = document.getElementById('countdown');

    function updateCountdown() {
        const now = new Date();
        const diff = expiredAt - now;

        if (diff <= 0) {
            countdownEl.textContent = 'หมดเวลาแล้ว';
            countdownEl.style.color = '#d9534f';
            submitBtn.disabled = true;
            return;
        }

        const minutes = Math.floor(diff / 60000);
        const seconds = Math.floor((diff % 60000) / 1000);
        countdownEl.textContent = minutes + ' นาที ' + seconds + ' วินาที';
    }

    updateCountdown();
    setInterval(updateCountdown, 1000);
    @endif
});
</script>
@endpush
@endsection
