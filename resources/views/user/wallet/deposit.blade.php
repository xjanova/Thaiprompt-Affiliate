@extends('layouts.user-v4')

@section('title', 'ฝากเงิน')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px; padding-bottom:24px;">

    {{-- ── ข้อความแจ้งเตือน Flash ──────────────────────────────── --}}
    @if(session('error'))
        <div class="tp-card" style="padding:14px 18px; display:flex; align-items:center; gap:12px; box-shadow:var(--inset-sm); border-left:4px solid #d9534f;">
            <span style="font-size:22px;">❌</span>
            <p style="margin:0; font-size:13px; font-weight:600; color:#d9534f;">{{ session('error') }}</p>
        </div>
    @endif
    @if(session('success'))
        <div class="tp-card" style="padding:14px 18px; display:flex; align-items:center; gap:12px; box-shadow:var(--inset-sm); border-left:4px solid #5aa07e;">
            <span style="font-size:22px;">✅</span>
            <p style="margin:0; font-size:13px; font-weight:600; color:#5aa07e;">{{ session('success') }}</p>
        </div>
    @endif
    @if($errors->any())
        <div class="tp-card" style="padding:14px 18px; display:flex; align-items:flex-start; gap:12px; box-shadow:var(--inset-sm); border-left:4px solid #d9534f;">
            <span style="font-size:22px;">⚠️</span>
            <ul style="margin:0; padding-left:18px; font-size:13px; color:#d9534f; list-style:disc;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ── Hero: หัวข้อ + ยอดเงินปัจจุบัน ──────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:20px 24px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            <div style="display:flex; flex-wrap:wrap; align-items:center; gap:14px;">
                @if(\Illuminate\Support\Facades\Route::has('user.wallet.index'))
                    <a href="{{ route('user.wallet.index') }}" class="tp-icon-btn" title="กลับ"><i class="fas fa-arrow-left"></i></a>
                @endif
                <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:23px;"><i class="fas fa-plus-circle" style="color:#fff;"></i></span>
                <div style="flex:1; min-width:180px;">
                    <h1 style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">ฝากเงิน</h1>
                    <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">เติมเงินเข้ากระเป๋าของคุณ</div>
                </div>
            </div>

            {{-- ยอดเงินปัจจุบัน --}}
            <div style="margin-top:18px; padding:20px 22px; border-radius:18px; box-shadow:var(--inset);">
                <div style="font-size:12.5px; color:var(--ink2);">ยอดเงินปัจจุบัน</div>
                <div class="tp-num" style="font-size:clamp(32px,7vw,48px); font-weight:800; line-height:1.1; margin-top:4px; color:var(--deep1);">฿{{ number_format($wallet->balance, 2) }}</div>
            </div>
        </div>
    </div>

    {{-- ── เลือกช่องทางการชำระเงิน ───────────────────────────── --}}
    <div class="tp-card" style="padding:18px;">
        <div class="tp-section-h" style="margin-bottom:16px;">เลือกช่องทางการชำระเงิน</div>

        {{-- ⚠️ แจ้งเตือน: ยอดต่ำกว่า 100 บาท ต้องใช้ PromptPay เท่านั้น --}}
        <div id="low-amount-notice" style="display:none; margin-bottom:16px; padding:14px 16px; border-radius:13px; box-shadow:var(--inset-sm); border-left:4px solid #e0a52e;">
            <p style="margin:0; font-size:13px; color:#e0a52e; font-weight:500;">
                ⚠️ <strong>ยอดเงินต่ำกว่า 100 บาท</strong> — กรุณาใช้ช่องทาง <strong>พร้อมเพย์</strong> เท่านั้น (โอนผ่านธนาคารไม่รองรับยอดต่ำกว่า 100 บาท เนื่องจากระบบ SMS แจ้งเตือนไม่ทำงาน)
            </p>
        </div>

        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:16px;">
            @foreach($availableGateways as $key => $gateway)
                @if($gateway['enabled'])
                    <div id="gateway-card-{{ $key }}"
                         class="tp-card tp-card-hover"
                         style="padding:16px; cursor:pointer; box-shadow:var(--inset-sm);"
                         onclick="selectPaymentMethod('{{ $key }}')">
                        <div style="display:flex; align-items:flex-start; gap:14px;">
                            <span class="tp-tile" style="width:48px; height:48px; border-radius:15px; font-size:24px; flex-shrink:0;">{{ $gateway['icon'] }}</span>
                            <div style="flex:1; min-width:0;">
                                <h3 style="margin:0; font-size:15px; font-weight:700; color:var(--ink);">{{ $gateway['name'] }}</h3>
                                <p style="margin:4px 0 0; font-size:12.5px; color:var(--ink2);">{{ $gateway['description'] }}</p>

                                @if($key === 'promptpay')
                                    <div style="margin-top:10px;">
                                        <span class="tp-pill" style="color:#5aa07e; background:color-mix(in srgb, #5aa07e 16%, transparent);">⚡ ทันที</span>
                                    </div>
                                @elseif($key === 'bank_transfer')
                                    <div style="margin-top:10px; display:flex; flex-wrap:wrap; gap:6px;">
                                        <span class="tp-pill" style="color:#e0a52e; background:color-mix(in srgb, #e0a52e 16%, transparent);">⏰ รออนุมัติ</span>
                                        {{-- แจ้งเตือนขั้นต่ำ 100 บาท --}}
                                        <span class="tp-pill" style="color:#d9534f; background:color-mix(in srgb, #d9534f 16%, transparent);">ขั้นต่ำ ฿100</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>

    {{-- ── ฟอร์มชำระเงินผ่าน พร้อมเพย์ ───────────────────────── --}}
    <div id="promptpay-form" class="tp-card" style="padding:18px; display:none;">
        <div class="tp-section-h" style="margin-bottom:16px;">💳 ชำระเงินผ่าน พร้อมเพย์</div>

        <form method="POST" action="{{ route('user.wallet.deposit.promptpay') }}" style="display:flex; flex-direction:column; gap:16px;">
            @csrf
            <div>
                <label style="display:block; font-size:11.5px; font-weight:600; color:var(--ink2); margin-bottom:6px;">จำนวนเงิน (บาท)</label>
                <input type="number"
                       name="amount"
                       step="0.01"
                       min="1"
                       required
                       class="tp-input"
                       placeholder="ระบุจำนวนเงิน">
            </div>

            <div style="padding:14px 16px; border-radius:13px; box-shadow:var(--inset-sm); border-left:4px solid #5689b8;">
                <p style="margin:0; font-size:13px; color:var(--ink); font-weight:500;">
                    <strong>หมายเหตุ:</strong> หลังจากกดยืนยัน คุณจะได้รับ QR Code สำหรับชำระเงิน
                </p>
            </div>

            <div style="display:flex; gap:12px;">
                <button type="button"
                        onclick="hideAllForms()"
                        class="tp-btn"
                        style="flex:1;">
                    ยกเลิก
                </button>
                <button type="submit"
                        class="tp-btn tp-btn-primary"
                        style="flex:1;">
                    สร้าง QR Code
                </button>
            </div>
        </form>
    </div>

    {{-- ── ฟอร์มโอนผ่านธนาคาร ────────────────────────────────── --}}
    <div id="bank_transfer-form" class="tp-card" style="padding:18px; display:none;">
        <div class="tp-section-h" style="margin-bottom:16px;">🏦 โอนผ่านธนาคาร</div>

        {{-- ⚠️ แจ้งขั้นต่ำ 100 บาท --}}
        <div style="margin-bottom:16px; padding:14px 16px; border-radius:13px; box-shadow:var(--inset-sm); border-left:4px solid #e0a52e;">
            <p style="margin:0; font-size:13px; color:#e0a52e;">
                ⚠️ ขั้นต่ำ <strong>100 บาท</strong> — ยอดต่ำกว่า 100 บาท กรุณาใช้ <a href="javascript:selectPaymentMethod('promptpay')" style="color:var(--deep1); text-decoration:underline; font-weight:600;">พร้อมเพย์</a> แทน
            </p>
        </div>

        <form method="POST" action="{{ route('user.wallet.deposit.bank-transfer') }}" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:16px;">
            @csrf
            <div>
                <label style="display:block; font-size:11.5px; font-weight:600; color:var(--ink2); margin-bottom:6px;">จำนวนเงิน (บาท)</label>
                <input type="number"
                       name="amount"
                       step="0.01"
                       min="100"
                       required
                       class="tp-input"
                       placeholder="ขั้นต่ำ 100 บาท">
            </div>

            <div>
                <label style="display:block; font-size:11.5px; font-weight:600; color:var(--ink2); margin-bottom:6px;">อัพโหลดสลิปการโอนเงิน</label>
                <input type="file"
                       name="slip"
                       accept="image/*"
                       required
                       class="tp-input">
            </div>

            <div>
                <label style="display:block; font-size:11.5px; font-weight:600; color:var(--ink2); margin-bottom:6px;">หมายเหตุ (ถ้ามี)</label>
                <textarea name="note"
                          rows="3"
                          class="tp-input"
                          placeholder="หมายเหตุเพิ่มเติม"></textarea>
            </div>

            <div style="padding:14px 16px; border-radius:13px; box-shadow:var(--inset-sm); border-left:4px solid #e0a52e;">
                <p style="margin:0; font-size:13px; color:var(--ink); font-weight:500;">
                    <strong>หมายเหตุ:</strong> หลังจากส่งคำขอ แอดมินจะตรวจสอบและอนุมัติภายใน 24 ชั่วโมง
                </p>
            </div>

            <div style="display:flex; gap:12px;">
                <button type="button"
                        onclick="hideAllForms()"
                        class="tp-btn"
                        style="flex:1;">
                    ยกเลิก
                </button>
                <button type="submit"
                        class="tp-btn tp-btn-primary"
                        style="flex:1;">
                    ส่งคำขอ
                </button>
            </div>
        </form>
    </div>

    {{-- ── ฟอร์ม Stripe ──────────────────────────────────────── --}}
    <div id="stripe-form" class="tp-card" style="padding:18px; display:none;">
        <div class="tp-section-h" style="margin-bottom:16px;">💰 ชำระเงินด้วย Stripe</div>

        <div style="padding:14px 16px; border-radius:13px; box-shadow:var(--inset-sm); border-left:4px solid #5689b8;">
            <p style="margin:0; font-size:13px; color:var(--ink); font-weight:500;">
                <strong>ข้อมูล:</strong> ระบบ Stripe กำลังอยู่ในระหว่างการพัฒนา กรุณาเลือกช่องทางอื่น
            </p>
        </div>

        <button type="button"
                onclick="hideAllForms()"
                class="tp-btn"
                style="width:100%; margin-top:16px;">
            กลับ
        </button>
    </div>

    {{-- ── ฟอร์ม PayPal ──────────────────────────────────────── --}}
    <div id="paypal-form" class="tp-card" style="padding:18px; display:none;">
        <div class="tp-section-h" style="margin-bottom:16px;">💵 ชำระเงินด้วย PayPal</div>

        <div style="padding:14px 16px; border-radius:13px; box-shadow:var(--inset-sm); border-left:4px solid #5689b8;">
            <p style="margin:0; font-size:13px; color:var(--ink); font-weight:500;">
                <strong>ข้อมูล:</strong> ระบบ PayPal กำลังอยู่ในระหว่างการพัฒนา กรุณาเลือกช่องทางอื่น
            </p>
        </div>

        <button type="button"
                onclick="hideAllForms()"
                class="tp-btn"
                style="width:100%; margin-top:16px;">
            กลับ
        </button>
    </div>

    {{-- ── คำแนะนำ ──────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:20px 24px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            <div class="tp-section-h" style="margin-bottom:14px;">📋 คำแนะนำ</div>
            <ul style="margin:0; padding:0; list-style:none; display:flex; flex-direction:column; gap:10px;">
                <li style="display:flex; align-items:flex-start; gap:10px; font-size:13px; color:var(--ink);">
                    <span style="color:#5aa07e;">✅</span>
                    <span>ตรวจสอบจำนวนเงินให้ถูกต้องก่อนทำรายการ</span>
                </li>
                <li style="display:flex; align-items:flex-start; gap:10px; font-size:13px; color:var(--ink);">
                    <span style="color:#5aa07e;">✅</span>
                    <span>สำหรับการโอนธนาคาร กรุณาอัพโหลดสลิปที่ชัดเจน</span>
                </li>
                <li style="display:flex; align-items:flex-start; gap:10px; font-size:13px; color:var(--ink);">
                    <span style="color:#5aa07e;">✅</span>
                    <span>ระบบจะบันทึกรายการธุรกรรมทั้งหมดอัตโนมัติ</span>
                </li>
                <li style="display:flex; align-items:flex-start; gap:10px; font-size:13px; color:var(--ink);">
                    <span style="color:#5aa07e;">✅</span>
                    <span>หากมีปัญหา กรุณาติดต่อฝ่ายสนับสนุน</span>
                </li>
            </ul>
        </div>
    </div>
</div>

@push('scripts')
<script>
/**
 * ค่าขั้นต่ำสำหรับโอนผ่านธนาคาร (SMS ไม่แจ้งเตือนถ้ายอดต่ำกว่านี้)
 */
const BANK_TRANSFER_MIN_AMOUNT = 100;

function selectPaymentMethod(method) {
    hideAllForms();

    const formId = method + '-form';
    const formElement = document.getElementById(formId);

    if (formElement) {
        formElement.style.display = '';
        formElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

function hideAllForms() {
    document.querySelectorAll('[id$="-form"]').forEach(form => {
        form.style.display = 'none';
    });
}

/**
 * ✅ ตรวจสอบยอดเงินใน PromptPay form แบบ realtime
 * ถ้ายอดต่ำกว่า 100 → ซ่อน bank transfer card + แสดงข้อความแจ้ง
 * ถ้ายอด >= 100 → แสดง bank transfer card ปกติ
 */
function checkAmountForBankTransfer(amount) {
    const bankCard = document.getElementById('gateway-card-bank_transfer');
    const notice = document.getElementById('low-amount-notice');

    if (!bankCard || !notice) return;

    if (amount > 0 && amount < BANK_TRANSFER_MIN_AMOUNT) {
        // ยอดต่ำกว่า 100 → ซ่อน bank transfer + แจ้งเตือน
        bankCard.style.opacity = '0.5';
        bankCard.style.pointerEvents = 'none';
        bankCard.title = 'ยอดต่ำกว่า 100 บาท กรุณาใช้พร้อมเพย์';
        notice.style.display = '';
    } else {
        // ยอดปกติ → แสดง bank transfer
        bankCard.style.opacity = '';
        bankCard.style.pointerEvents = '';
        bankCard.title = '';
        notice.style.display = 'none';
    }
}

// ฟังการพิมพ์จำนวนเงินในฟอร์ม PromptPay
document.addEventListener('DOMContentLoaded', function() {
    const promptPayAmountInput = document.querySelector('#promptpay-form input[name="amount"]');
    if (promptPayAmountInput) {
        promptPayAmountInput.addEventListener('input', function() {
            checkAmountForBankTransfer(parseFloat(this.value) || 0);
        });
    }

    // ฟังการพิมพ์จำนวนเงินในฟอร์ม Bank Transfer ด้วย (ป้องกัน submit ถ้า < 100)
    const bankForm = document.querySelector('#bank_transfer-form form');
    if (bankForm) {
        bankForm.addEventListener('submit', function(e) {
            const amount = parseFloat(bankForm.querySelector('input[name="amount"]').value) || 0;
            if (amount < BANK_TRANSFER_MIN_AMOUNT) {
                e.preventDefault();
                alert('ยอดขั้นต่ำสำหรับโอนผ่านธนาคาร คือ ' + BANK_TRANSFER_MIN_AMOUNT + ' บาท\nกรุณาใช้ช่องทาง พร้อมเพย์ แทน');
                return false;
            }
        });
    }
});
</script>
@endpush
@endsection
