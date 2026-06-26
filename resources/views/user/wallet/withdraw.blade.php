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
            <form method="POST" action="{{ route('user.wallet.withdraw.submit') }}" style="display:flex; flex-direction:column; gap:18px;">
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
                           min="1"
                           max="{{ $wallet->balance }}"
                           required
                           class="tp-input"
                           placeholder="ระบุจำนวนเงินที่ต้องการถอน">
                    <div style="font-size:11px; color:var(--ink2); margin-top:5px;">ยอดเงินสูงสุดที่ถอนได้: <span class="tp-num">฿{{ number_format($wallet->balance, 2) }}</span></div>
                </div>

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
                        <li>• จำนวนเงินขั้นต่ำในการถอนคือ 100 บาท</li>
                        <li>• ระบบจะตรวจสอบและดำเนินการภายใน 1-3 วันทำการ</li>
                        <li>• กรุณาตรวจสอบข้อมูลบัญชีให้ถูกต้อง</li>
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
