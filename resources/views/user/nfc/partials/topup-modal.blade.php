{{--
    โมดัลเติมเงินเข้าการ์ด NFC จากกระเป๋าเงิน (POST user.nfc.topup: amount + pin 6 หลัก)
    รับ:
      $walletBalance  ยอดกระเป๋าเงินที่ใช้เติมได้
      $action         URL ของการ์ดใบนี้ (หน้ารายละเอียด) — ไม่ส่ง = ใช้ topUpAction/topUpName จาก Alpine (หน้ารวมการ์ด)
      $cardName       ชื่อการ์ด (ใช้คู่กับ $action)
    ต้องอยู่ใต้ x-data ที่มี topUp (เปิด/ปิด) — ช่องจำนวนเงินอ้างด้วย x-ref="topUpAmount"
--}}
@php
    $action = $action ?? null;
@endphp
<div class="nvm-modal" x-show="topUp" x-cloak x-transition.opacity role="dialog" aria-modal="true" aria-labelledby="nvm-nfc-top-h" @click.self="topUp = false" @keydown.escape.window="topUp = false">
    <div class="nvm-card nvm-modal__box nvm-sn-modal">
        <h2 id="nvm-nfc-top-h">เติมเงินจากกระเป๋า</h2>
        <form class="nvm-form nvm-gap-t" method="POST" @if($action) action="{{ $action }}" @else :action="topUpAction" @endif x-data="{ busy: false }" @submit="if (busy) { $event.preventDefault() } else { busy = true }">
            @csrf
            <div class="nvm-field">
                <span class="nvm-label">การ์ด</span>
                @if($action)
                    <b>{{ $cardName ?? 'การ์ด NFC' }}</b>
                @else
                    <b x-text="topUpName"></b>
                @endif
            </div>
            <div class="nvm-field">
                <label class="nvm-label" for="nvm-nfc-top-amt">จำนวนเงิน (บาท) <em>*</em></label>
                <div class="nvm-amount">
                    <span aria-hidden="true">฿</span>
                    <input id="nvm-nfc-top-amt" class="nvm-input nvm-num" type="number" name="amount" required min="1" max="100000" step="0.01" inputmode="decimal" x-ref="topUpAmount" value="{{ old('amount') }}">
                </div>
                <small class="nvm-note nvm-num">ยอดกระเป๋าเงินคงเหลือ ฿{{ number_format((float) $walletBalance, 2) }}</small>
                @error('amount')<span class="nvm-err">{{ $message }}</span>@enderror
            </div>
            <div class="nvm-field">
                <label class="nvm-label" for="nvm-nfc-top-pin">PIN กระเป๋าเงิน 6 หลัก <em>*</em></label>
                <input id="nvm-nfc-top-pin" class="nvm-input nvm-otp" type="password" name="pin" required minlength="6" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" autocomplete="off">
                @error('pin')<span class="nvm-err">{{ $message }}</span>@enderror
            </div>
            <div class="nvm-filter__btns">
                <button type="button" class="nvm-btn nvm-btn--line" @click="topUp = false">ยกเลิก</button>
                <button type="submit" class="nvm-btn nvm-btn--gold" :disabled="busy"><svg class="nvm-ico"><use href="#nvm-i-plus"/></svg><span x-text="busy ? 'กำลังเติมเงิน…' : 'เติมเงิน'">เติมเงิน</span></button>
            </div>
        </form>
    </div>
</div>
