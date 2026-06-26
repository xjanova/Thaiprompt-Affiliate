@extends('layouts.user-v4')

@section('title', 'เติมเงิน Wallet')

@php
    // จำนวนเงินด่วน (preset) สำหรับปุ่มเลือกเร็ว
    $quickAmounts = [100, 300, 500, 1000, 2000, 5000, 10000];
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── หัวข้อ (Hero) + ยอดเงินปัจจุบัน ───────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:20px 24px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            <div style="display:flex; flex-wrap:wrap; align-items:center; gap:14px;">
                @if(\Illuminate\Support\Facades\Route::has('user.wallet.index'))
                    <a href="{{ route('user.wallet.index') }}" class="tp-icon-btn" title="กลับ"><i class="fas fa-arrow-left"></i></a>
                @endif
                <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:24px;"><i class="fas fa-wallet" style="color:#fff;"></i></span>
                <div style="flex:1; min-width:200px;">
                    <h1 style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">เติมเงิน Wallet</h1>
                    <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">เลือกจำนวนเงินที่ต้องการเติม</div>
                </div>
            </div>

            {{-- ยอดเงินปัจจุบัน --}}
            <div style="margin-top:18px; padding:18px 22px; border-radius:18px; box-shadow:var(--inset);">
                <div style="font-size:12.5px; color:var(--ink2);">ยอดเงินปัจจุบัน</div>
                <div class="tp-num" style="font-size:clamp(30px,6vw,46px); font-weight:800; line-height:1.1; margin-top:4px; color:var(--deep1);">฿{{ number_format($wallet->balance, 2) }}</div>
            </div>
        </div>
    </div>

    {{-- ── ฟอร์มเลือกจำนวนเงินที่ต้องการเติม ─────────────────── --}}
    <div class="tp-card" style="padding:20px;">
        <div class="tp-section-h" style="margin-bottom:16px;">💰 เลือกจำนวนเงินที่ต้องการเติม</div>

        <form id="topup-form" action="{{ route('user.wallet.topup.process') }}" method="POST">
            @csrf
            <input type="hidden" id="topup-amount" name="amount" value="">

            {{-- ปุ่มจำนวนเงินด่วน --}}
            <div style="margin-bottom:20px;">
                <label style="display:block; font-size:11.5px; font-weight:600; color:var(--ink2); margin-bottom:10px;">เลือกจำนวนเงินด่วน</label>
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(110px,1fr)); gap:10px;">
                    @foreach($quickAmounts as $amount)
                        <button type="button"
                                onclick="selectAmount({{ $amount }})"
                                class="quick-amount-btn tp-num"
                                data-amount="{{ $amount }}"
                                style="padding:13px 8px; border-radius:14px; font-size:15px; font-weight:800; cursor:pointer; box-shadow:var(--inset-sm); background:transparent; color:var(--ink); border:2px solid transparent; transition:all .15s ease;">
                            ฿{{ number_format($amount) }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- ระบุจำนวนเงินเอง --}}
            <div style="margin-bottom:20px;">
                <label for="custom-amount" style="display:block; font-size:11.5px; font-weight:600; color:var(--ink2); margin-bottom:8px;">หรือระบุจำนวนเงินเอง</label>
                <div style="position:relative;">
                    <span class="tp-num" style="position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--ink2); font-weight:800; font-size:16px; pointer-events:none;">฿</span>
                    <input type="number"
                           id="custom-amount"
                           name="custom_amount"
                           min="100"
                           max="100000"
                           step="1"
                           placeholder="ระบุจำนวนเงิน (ขั้นต่ำ 100 บาท)"
                           class="tp-input tp-num"
                           style="padding-left:32px; font-size:16px; font-weight:700;"
                           oninput="updateCustomAmount(this.value)">
                </div>
                <div style="margin-top:8px; font-size:11.5px; color:var(--ink2);">
                    ⚠️ จำนวนเงินขั้นต่ำ 100 บาท, สูงสุด 100,000 บาท
                </div>
            </div>

            {{-- แสดงจำนวนเงินที่เลือก --}}
            <div id="selected-amount-display" style="display:none; margin-bottom:20px; padding:16px 18px; border-radius:16px; box-shadow:var(--inset); background:color-mix(in srgb, var(--accent1) 12%, transparent);">
                <div style="font-size:12px; color:var(--ink2); margin-bottom:2px;">จำนวนเงินที่เลือก</div>
                <div class="tp-num" id="selected-amount-text" style="font-size:30px; font-weight:800; color:var(--deep1);">฿0</div>
            </div>

            {{-- ปุ่มยืนยัน --}}
            <button type="submit"
                    id="submit-btn"
                    disabled
                    class="tp-btn tp-btn-primary"
                    style="width:100%; padding:15px; font-size:16px; font-weight:800; opacity:.55; cursor:not-allowed;">
                🛒 ดำเนินการเติมเงิน
            </button>
        </form>
    </div>

    {{-- ── วิธีการเติมเงิน ───────────────────────────────────── --}}
    <div class="tp-card" style="padding:18px; box-shadow:var(--inset-sm); border-left:4px solid #5689b8;">
        <div style="display:flex; align-items:flex-start; gap:14px;">
            <span class="tp-tile" style="width:42px; height:42px; border-radius:13px; font-size:19px; background:rgba(86,137,184,.18);">ℹ️</span>
            <div style="flex:1; min-width:0;">
                <div style="font-size:15px; font-weight:800; margin-bottom:8px;">วิธีการเติมเงิน</div>
                <ol style="margin:0; padding-left:18px; font-size:12.5px; color:var(--ink2); line-height:1.9;">
                    <li>เลือกจำนวนเงินที่ต้องการเติม (คลิกปุ่มด่วนหรือกรอกเอง)</li>
                    <li>กดปุ่ม "ดำเนินการเติมเงิน"</li>
                    <li>เลือกช่องทางการชำระเงิน (พร้อมเพย์, โอนธนาคาร, บัตรเครดิต, ฯลฯ)</li>
                    <li>ทำการชำระเงินตามขั้นตอน</li>
                    <li>หลังชำระเงินสำเร็จ ยอดเงินจะเข้า Wallet ทันที</li>
                </ol>
            </div>
        </div>
    </div>

    {{-- ── ใช้เงินใน Wallet ทำอะไรได้บ้าง ─────────────────────── --}}
    <div class="tp-card" style="padding:18px; box-shadow:var(--inset-sm); border-left:4px solid #5aa07e;">
        <div style="display:flex; align-items:flex-start; gap:14px;">
            <span class="tp-tile" style="width:42px; height:42px; border-radius:13px; font-size:19px; background:rgba(90,160,126,.18);">✅</span>
            <div style="flex:1; min-width:0;">
                <div style="font-size:15px; font-weight:800; margin-bottom:8px;">ใช้เงินใน Wallet ทำอะไรได้บ้าง?</div>
                <ul style="margin:0; padding-left:18px; font-size:12.5px; color:var(--ink2); line-height:1.9;">
                    <li>🎮 บันทึกคะแนนเกม Snake.io (1 แต้ม/ครั้ง)</li>
                    <li>🛍️ ซื้อสินค้าและบริการในระบบ</li>
                    <li>🤖 เช่าบอท AI และบริการ Automation</li>
                    <li>📚 ซื้อคอร์สเรียนในระบบ Academy</li>
                    <li>💼 ชำระค่าแพ็คเกจ MLM และ Affiliate</li>
                    <li>⚡ ชำระเงินรวดเร็ว ไม่ต้องกรอกข้อมูลซ้ำ</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let selectedAmount = 0;

/**
 * เลือกจำนวนเงินจากปุ่มด่วน
 */
function selectAmount(amount) {
    selectedAmount = amount;

    // ล้างค่า custom input
    document.getElementById('custom-amount').value = '';

    // อัพเดต UI
    updateUI(amount);

    // ตั้งค่าจำนวนเงินใน hidden field
    document.getElementById('topup-amount').value = amount;

    // Highlight ปุ่มที่เลือก (ใช้ inline style ตามธีม V4)
    document.querySelectorAll('.quick-amount-btn').forEach(btn => {
        if (parseInt(btn.dataset.amount) === amount) {
            btn.style.borderColor = 'var(--accent1)';
            btn.style.background = 'color-mix(in srgb, var(--accent1) 14%, transparent)';
            btn.style.color = 'var(--deep1)';
        } else {
            btn.style.borderColor = 'transparent';
            btn.style.background = 'transparent';
            btn.style.color = 'var(--ink)';
        }
    });
}

/**
 * อัพเดตจำนวนเงินจาก custom input
 */
function updateCustomAmount(value) {
    const amount = parseInt(value) || 0;

    if (amount < 100 && amount > 0) {
        return; // ไม่ทำอะไรถ้าน้อยกว่า 100
    }

    selectedAmount = amount;

    // ล้าง highlight ปุ่มด่วน
    document.querySelectorAll('.quick-amount-btn').forEach(btn => {
        btn.style.borderColor = 'transparent';
        btn.style.background = 'transparent';
        btn.style.color = 'var(--ink)';
    });

    // อัพเดต UI
    updateUI(amount);

    // ตั้งค่าจำนวนเงินใน hidden field
    document.getElementById('topup-amount').value = amount;
}

/**
 * อัพเดต UI
 */
function updateUI(amount) {
    const displayEl = document.getElementById('selected-amount-display');
    const amountTextEl = document.getElementById('selected-amount-text');
    const submitBtn = document.getElementById('submit-btn');

    if (amount >= 100) {
        displayEl.style.display = 'block';
        amountTextEl.textContent = '฿' + amount.toLocaleString();
        submitBtn.disabled = false;
        submitBtn.style.opacity = '1';
        submitBtn.style.cursor = 'pointer';
    } else {
        displayEl.style.display = 'none';
        submitBtn.disabled = true;
        submitBtn.style.opacity = '.55';
        submitBtn.style.cursor = 'not-allowed';
    }
}

// Form validation
document.getElementById('topup-form').addEventListener('submit', function(e) {
    if (selectedAmount < 100) {
        e.preventDefault();
        if (window.showNotification) {
            window.showNotification('กรุณาเลือกจำนวนเงินขั้นต่ำ 100 บาท', 'error');
        } else {
            alert('กรุณาเลือกจำนวนเงินขั้นต่ำ 100 บาท');
        }
        return false;
    }

    if (selectedAmount > 100000) {
        e.preventDefault();
        if (window.showNotification) {
            window.showNotification('จำนวนเงินสูงสุด 100,000 บาท', 'error');
        } else {
            alert('จำนวนเงินสูงสุด 100,000 บาท');
        }
        return false;
    }

    console.log('[Topup] เลือกจำนวนเงิน:', selectedAmount, 'บาท');
    return true;
});
</script>
@endpush
@endsection
