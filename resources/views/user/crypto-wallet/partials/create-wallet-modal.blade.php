{{--
    โมดัลสร้างกระเป๋าคริปโตแบบ custodial — POST user.crypto-wallet.create-wallet (CryptoWalletController::createWallet)
    CreateCustodialWalletRequest: pin ตัวเลข 6 หลัก + pin_confirmation · มีกระเป๋าได้ไม่เกิน 5 ใบ
    ต้องวางในองค์ประกอบที่มี x-data ซึ่งมีตัวแปร create (true = เปิดโมดัล)
--}}
<div class="nvm-modal" x-show="create" x-cloak x-transition.opacity role="dialog" aria-modal="true" aria-labelledby="nvm-cw-create-h"
     @click.self="create = false" @keydown.escape.window="create = false">
    <div class="nvm-card nvm-modal__box nvm-cw-modal">
        <img src="{{ asset('images/nova/deco/coins.webp') }}" alt="" width="110" height="110" loading="lazy">
        <h2 id="nvm-cw-create-h">สร้างกระเป๋าคริปโต</h2>
        <p>ระบบดูแลกุญแจให้ (Custodial) ตั้ง PIN 6 หลักไว้ยืนยันทุกครั้งที่ถอนเหรียญ</p>
        <form method="POST" action="{{ route('user.crypto-wallet.create-wallet') }}" class="nvm-form" x-data="{ busy: false }" @submit="busy = true" @pageshow.window="busy = false">
            @csrf
            <div class="nvm-field">
                <label class="nvm-label" for="cw-new-name">ชื่อกระเป๋า</label>
                <input id="cw-new-name" type="text" name="name" maxlength="100" class="nvm-input" value="{{ old('name', 'My Crypto Wallet') }}">
            </div>
            <div class="nvm-field">
                <label class="nvm-label" for="cw-new-pin">PIN 6 หลัก <em>*</em></label>
                <input id="cw-new-pin" type="password" name="pin" required minlength="6" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" autocomplete="new-password" class="nvm-input nvm-pin @error('pin') is-bad @enderror" placeholder="••••••">
                @error('pin')<span class="nvm-err">{{ $message }}</span>@enderror
            </div>
            <div class="nvm-field">
                <label class="nvm-label" for="cw-new-pin2">ยืนยัน PIN <em>*</em></label>
                <input id="cw-new-pin2" type="password" name="pin_confirmation" required minlength="6" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" autocomplete="new-password" class="nvm-input nvm-pin" placeholder="••••••">
            </div>
            <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-lock"/></svg>จดจำ PIN ไว้ให้ดี ต้องใช้ทุกครั้งที่ทำธุรกรรม ลืม PIN ติดต่อฝ่ายสนับสนุน</p>
            <div class="nvm-filter__btns">
                <button type="button" class="nvm-btn nvm-btn--line" @click="create = false">ยกเลิก</button>
                <button type="submit" class="nvm-btn nvm-btn--gold" :disabled="busy"><svg class="nvm-ico"><use href="#nvm-i-plus"/></svg><span x-text="busy ? 'กำลังสร้าง…' : 'สร้างกระเป๋า'">สร้างกระเป๋า</span></button>
            </div>
        </form>
    </div>
</div>
