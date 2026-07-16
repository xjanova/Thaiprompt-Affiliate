@extends('layouts.user-v4')

@section('title', 'ถอนเงิน')

@push('scripts')
@if(config('turnstile.enabled') && config('turnstile.points.withdrawal_request'))
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
@endif
@endpush

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── หัวข้อ (Hero) ─────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:20px 24px; background:linear-gradient(120deg, color-mix(in srgb, #d9534f 16%, transparent), transparent 70%);">
            <div style="display:flex; flex-wrap:wrap; align-items:center; gap:14px;">
                <a href="{{ route('user.wallet.index') }}" class="tp-icon-btn" title="กลับ"><i class="fas fa-arrow-left"></i></a>
                <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:23px; background:#d9534f;"><i class="fas fa-hand-holding-usd" style="color:#fff;"></i></span>
                <div style="flex:1; min-width:200px;">
                    <h1 style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">ถอนเงิน</h1>
                    <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">ถอนเงินจากกระเป๋าของคุณ</div>
                </div>
            </div>

            {{-- ยอดเงินที่ถอนได้ --}}
            <div style="margin-top:18px; padding:18px 22px; border-radius:18px; box-shadow:var(--inset);">
                <div style="font-size:12.5px; color:var(--ink2);">ยอดเงินที่สามารถถอนได้</div>
                <div class="tp-num" style="font-size:clamp(30px,6vw,46px); font-weight:800; line-height:1.1; margin-top:4px; color:var(--deep1);">฿{{ number_format($wallet->balance, 2) }}</div>
            </div>
        </div>
    </div>

    {{-- ── แบบฟอร์มถอนเงิน ───────────────────────────────────── --}}
    <div class="tp-card" style="padding:18px;">
        <div class="tp-section-h" style="margin-bottom:18px;">📝 แบบฟอร์มถอนเงิน</div>

        @if($paymentMethods->isEmpty())
            <div style="text-align:center; padding:40px 20px; border-radius:14px; box-shadow:var(--inset-sm);">
                <div style="font-size:46px;">🏦</div>
                <div style="font-weight:700; font-size:16px; margin-top:10px;">ยังไม่มีช่องทางรับเงิน</div>
                <div style="font-size:13px; color:var(--ink2); margin-top:4px;">กรุณาเพิ่มช่องทางรับเงินก่อนทำการถอน</div>
                <a href="{{ route('user.wallet.payment-methods') }}" class="tp-btn tp-btn-primary" style="margin-top:16px;">เพิ่มช่องทางรับเงิน</a>
            </div>
        @else
            @php
                // นโยบายค่าธรรมเนียม → Alpine (ห้ามใช้ @json กับ closure — ดู rule blade directive)
                $feePolicyJs = \Illuminate\Support\Js::from($feePolicy);
            @endphp
            <form method="POST" action="{{ route('user.wallet.withdraw.submit') }}"
                  style="display:flex; flex-direction:column; gap:18px;"
                  x-data="{
                      policy: {{ $feePolicyJs }},
                      amount: '',
                      get amt() { const a = parseFloat(this.amount); return isNaN(a) || a <= 0 ? 0 : a; },
                      get fee() {
                          if (this.amt <= 0) return 0;
                          let f = this.policy.fee_type === 'percentage'
                              ? this.amt * this.policy.fee_amount / 100
                              : this.policy.fee_amount;
                          f = Math.max(this.policy.fee_min, Math.min(this.policy.fee_max, f));
                          return Math.round(f * 100) / 100;
                      },
                      get tax() {
                          if (! this.policy.tax_enabled || this.amt <= 0 || this.amt < this.policy.tax_threshold) return 0;
                          return Math.round(this.amt * this.policy.tax_percentage) / 100;
                      },
                      get net() { return Math.max(0, Math.round((this.amt - this.fee - this.tax) * 100) / 100); },
                      fmt(n) { return n.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
                  }">
                @csrf

                <div>
                    <label style="display:block; font-size:11.5px; font-weight:600; color:var(--ink2); margin-bottom:6px;">เลือกช่องทางรับเงิน</label>
                    <select name="payment_method_id" required class="tp-input">
                        <option value="">-- เลือกช่องทางรับเงิน --</option>
                        @foreach($paymentMethods as $method)
                            <option value="{{ $method->id }}">
                                @if($method->type === 'bank_transfer')
                                    🏦 {{ $method->bank_name }} - {{ $method->account_name }} ({{ substr($method->account_number, -4) }})
                                @elseif($method->type === 'promptpay')
                                    💳 PromptPay - {{ $method->account_name }} ({{ substr($method->account_number, -4) }})
                                @elseif($method->type === 'paypal')
                                    💰 PayPal - {{ $method->paypal_email }}
                                @endif
                                @if($method->is_default)
                                    ⭐ (ค่าเริ่มต้น)
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label style="display:block; font-size:11.5px; font-weight:600; color:var(--ink2); margin-bottom:6px;">จำนวนเงิน (บาท)</label>
                    <input type="number"
                           name="amount"
                           step="0.01"
                           min="{{ max(1, $feePolicy['min_amount']) }}"
                           max="{{ $wallet->balance }}"
                           required
                           x-model="amount"
                           class="tp-input"
                           placeholder="ระบุจำนวนเงินที่ต้องการถอน">
                    <div style="font-size:11px; color:var(--ink2); margin-top:5px;">ยอดเงินสูงสุดที่ถอนได้: <span class="tp-num">฿{{ number_format($wallet->balance, 2) }}</span></div>
                </div>

                {{-- สรุปยอดสุทธิ — คำนวณสดตามนโยบายจริงจาก WalletSetting --}}
                <div x-show="amt > 0" x-cloak style="padding:14px 16px; border-radius:14px; box-shadow:var(--inset-sm);">
                    <div style="display:flex; flex-direction:column; gap:6px; font-size:13px;">
                        <div style="display:flex; justify-content:space-between; color:var(--ink2);">
                            <span>ยอดถอน</span><span class="tp-num" x-text="'฿' + fmt(amt)"></span>
                        </div>
                        <div style="display:flex; justify-content:space-between; color:var(--ink2);">
                            <span>ค่าธรรมเนียม</span><span class="tp-num" x-text="'-฿' + fmt(fee)"></span>
                        </div>
                        <div x-show="tax > 0" style="display:flex; justify-content:space-between; color:var(--ink2);">
                            <span>ภาษีหัก ณ ที่จ่าย</span><span class="tp-num" x-text="'-฿' + fmt(tax)"></span>
                        </div>
                        <div style="display:flex; justify-content:space-between; font-weight:800; border-top:1px solid color-mix(in srgb, var(--ink2) 25%, transparent); padding-top:6px;">
                            <span>ยอดที่จะได้รับ</span><span class="tp-num" style="color:var(--deep1);" x-text="'฿' + fmt(net)"></span>
                        </div>
                    </div>
                </div>

                @if($wallet->hasPIN())
                {{-- PIN เฉพาะกระเป๋าที่ตั้งไว้ — ลูกค้าที่ไม่เคยตั้ง PIN ไม่ต้องกรอก
                     (เดิมบังคับกรอกทุกคนแต่ backend ทิ้งค่า = คนตั้ง PIN จริงถอนไม่ได้) --}}
                <div>
                    <label style="display:block; font-size:11.5px; font-weight:600; color:var(--ink2); margin-bottom:6px;">PIN กระเป๋าเงิน (6 หลัก)</label>
                    <input type="password"
                           name="pin"
                           maxlength="6"
                           pattern="[0-9]{6}"
                           required
                           class="tp-input"
                           placeholder="ระบุ PIN 6 หลัก">
                    <div style="font-size:11px; color:var(--ink2); margin-top:5px;">กรอก PIN เพื่อยืนยันการถอนเงิน</div>
                </div>
                @endif

                <div>
                    <label style="display:block; font-size:11.5px; font-weight:600; color:var(--ink2); margin-bottom:6px;">หมายเหตุ (ถ้ามี)</label>
                    <textarea name="user_note"
                              rows="3"
                              maxlength="500"
                              class="tp-input"
                              placeholder="หมายเหตุเพิ่มเติม (ไม่เกิน 500 ตัวอักษร)"></textarea>
                </div>

                {{-- เงื่อนไขการถอนเงิน --}}
                <div style="padding:14px 16px; border-radius:14px; box-shadow:var(--inset-sm); border-left:4px solid #5689b8;">
                    <div style="font-weight:700; font-size:13px; color:#5689b8; margin-bottom:8px;">📋 เงื่อนไขการถอนเงิน</div>
                    <ul style="margin:0; padding:0; list-style:none; display:flex; flex-direction:column; gap:5px; font-size:12.5px; color:var(--ink2);">
                        <li>• จำนวนเงินขั้นต่ำในการถอนคือ {{ number_format($feePolicy['min_amount']) }} บาท</li>
                        <li>• ค่าธรรมเนียม
                            @if($feePolicy['fee_type'] === 'percentage')
                                {{ rtrim(rtrim(number_format($feePolicy['fee_amount'], 2), '0'), '.') }}%
                                (ขั้นต่ำ {{ number_format($feePolicy['fee_min']) }} บาท@if($feePolicy['fee_max'] < 999999) สูงสุด {{ number_format($feePolicy['fee_max']) }} บาท@endif)
                            @else
                                {{ number_format($feePolicy['fee_amount'], 2) }} บาทต่อรายการ
                            @endif
                        </li>
                        @if($feePolicy['tax_enabled'] && $feePolicy['tax_percentage'] > 0)
                        <li>• ยอดตั้งแต่ {{ number_format($feePolicy['tax_threshold']) }} บาทขึ้นไป หักภาษี ณ ที่จ่าย {{ rtrim(rtrim(number_format($feePolicy['tax_percentage'], 2), '0'), '.') }}%</li>
                        @endif
                        <li>• ระบบจะตรวจสอบ และโอนเข้าบัญชีเป็นรอบ — วันที่ 2 และ 17 ของทุกเดือน</li>
                        <li>• ชื่อบัญชีรับเงินต้องตรงกับชื่อที่ยืนยันตัวตน (KYC)</li>
                        <li>• หากพบปัญหา กรุณาติดต่อฝ่ายสนับสนุน</li>
                    </ul>
                </div>

                @if(config('turnstile.enabled') && config('turnstile.points.withdrawal_request'))
                <div style="display:flex; justify-content:center;">
                    <div class="cf-turnstile"
                         data-sitekey="{{ config('turnstile.site_key') }}"
                         data-theme="{{ config('turnstile.theme') }}"
                         data-size="{{ config('turnstile.size') }}">
                    </div>
                </div>
                @endif

                <div style="display:flex; flex-wrap:wrap; gap:12px;">
                    <a href="{{ route('user.wallet.index') }}" class="tp-btn" style="flex:1; min-width:120px; text-align:center; justify-content:center;">ยกเลิก</a>
                    <button type="submit" class="tp-btn tp-btn-primary" style="flex:1; min-width:120px; justify-content:center; background:#d9534f; border-color:#d9534f;">ยืนยันการถอนเงิน</button>
                </div>
            </form>
        @endif
    </div>

    {{-- ── คำขอถอนเงินล่าสุด ─────────────────────────────────── --}}
    <div class="tp-card" style="padding:18px;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:14px;">
            <div class="tp-section-h">📋 คำขอถอนเงินล่าสุด</div>
            <a href="{{ route('user.wallet.withdrawals') }}" class="tp-btn tp-btn-sm">ดูทั้งหมด →</a>
        </div>
        <div style="text-align:center; padding:32px 20px; color:var(--ink2);">
            <div style="font-size:42px; opacity:.5;">💸</div>
            <div style="font-size:13px; margin-top:8px;">คลิก "ดูทั้งหมด" เพื่อดูประวัติการถอนเงิน</div>
        </div>
    </div>

    {{-- ── คำแนะนำ ───────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:18px 22px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            <div class="tp-section-h" style="margin-bottom:12px;">💡 คำแนะนำ</div>
            <ul style="margin:0; padding:0; list-style:none; display:flex; flex-direction:column; gap:8px; font-size:13px; color:var(--ink);">
                <li style="display:flex; align-items:flex-start; gap:8px;"><span style="color:#5aa07e;">✅</span><span>ตรวจสอบช่องทางรับเงินให้ถูกต้องก่อนถอน</span></li>
                <li style="display:flex; align-items:flex-start; gap:8px;"><span style="color:#5aa07e;">✅</span><span>กรอกจำนวนเงินที่ต้องการถอนอย่างระมัดระวัง</span></li>
                <li style="display:flex; align-items:flex-start; gap:8px;"><span style="color:#5aa07e;">✅</span><span>เก็บรักษา PIN ของคุณไว้เป็นความลับ</span></li>
                <li style="display:flex; align-items:flex-start; gap:8px;"><span style="color:#5aa07e;">✅</span><span>หากลืม PIN สามารถรีเซ็ตได้ที่หน้าโปรไฟล์</span></li>
            </ul>
        </div>
    </div>
</div>
@endsection
