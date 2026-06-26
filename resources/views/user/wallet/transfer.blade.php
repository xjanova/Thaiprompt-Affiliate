@extends('layouts.user-v4')

@section('title', 'โอนเงิน')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── Hero: หัวข้อโอนเงิน + ยอดที่โอนได้ ──────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:20px 24px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            <div style="display:flex; flex-wrap:wrap; align-items:center; gap:14px;">
                <a href="{{ route('user.wallet.index') }}" class="tp-icon-btn" title="กลับ"><i class="fas fa-arrow-left"></i></a>
                <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:23px;"><i class="fas fa-paper-plane" style="color:#fff;"></i></span>
                <div style="flex:1; min-width:200px;">
                    <h1 style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">โอนเงิน</h1>
                    <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">โอนเงินให้ผู้ใช้อื่นในระบบ</div>
                </div>
            </div>

            {{-- ยอดเงินที่สามารถโอนได้ --}}
            <div style="margin-top:18px; padding:18px 22px; border-radius:18px; box-shadow:var(--inset);">
                <div style="font-size:12.5px; color:var(--ink2);">ยอดเงินที่สามารถโอนได้</div>
                <div class="tp-num" style="font-size:clamp(30px,6vw,46px); font-weight:800; line-height:1.1; margin-top:4px; color:var(--deep1);">฿{{ number_format($wallet->balance, 2) }}</div>
            </div>
        </div>
    </div>

    {{-- ── แบบฟอร์มโอนเงิน ──────────────────────────────────── --}}
    <div class="tp-card" style="padding:22px;">
        <div class="tp-section-h" style="margin-bottom:18px;">📤 แบบฟอร์มโอนเงิน</div>

        <form method="POST" action="{{ route('user.wallet.transfer.submit') }}" style="display:flex; flex-direction:column; gap:18px;">
            @csrf

            <div>
                <label style="display:block; font-size:11.5px; font-weight:600; color:var(--ink2); margin-bottom:6px;">Wallet Address ผู้รับ</label>
                <input type="text"
                       name="wallet_address"
                       required
                       class="tp-input"
                       placeholder="ระบุ Wallet Address ของผู้รับ (เช่น TPW...)">
                <p style="font-size:11px; color:var(--ink2); margin-top:6px;">Wallet Address เริ่มต้นด้วย TPW ตามด้วยอักขระ 16 ตัว</p>
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
                       placeholder="ระบุจำนวนเงินที่ต้องการโอน">
                <p style="font-size:11px; color:var(--ink2); margin-top:6px;">ยอดเงินสูงสุดที่โอนได้: ฿{{ number_format($wallet->balance, 2) }}</p>
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
                <p style="font-size:11px; color:var(--ink2); margin-top:6px;">กรอก PIN เพื่อยืนยันการโอนเงิน</p>
            </div>

            <div>
                <label style="display:block; font-size:11.5px; font-weight:600; color:var(--ink2); margin-bottom:6px;">รายละเอียด (ถ้ามี)</label>
                <textarea name="description"
                          rows="3"
                          maxlength="255"
                          class="tp-input"
                          placeholder="รายละเอียดการโอนเงิน (ไม่เกิน 255 ตัวอักษร)"></textarea>
            </div>

            {{-- ข้อควรระวัง --}}
            <div style="padding:16px 18px; border-radius:14px; box-shadow:var(--inset-sm); border-left:4px solid #e0a52e;">
                <div style="font-weight:700; font-size:13.5px; color:#e0a52e; margin-bottom:8px;">⚠️ ข้อควรระวัง</div>
                <ul style="margin:0; padding-left:18px; display:flex; flex-direction:column; gap:5px; font-size:12.5px; color:var(--ink);">
                    <li>ตรวจสอบ Wallet Address ของผู้รับให้ถูกต้อง</li>
                    <li>การโอนเงินจะเกิดขึ้นทันทีและไม่สามารถยกเลิกได้</li>
                    <li>ไม่สามารถโอนเงินให้ตัวเองได้</li>
                    <li>เก็บรักษา PIN ของคุณเป็นความลับ</li>
                </ul>
            </div>

            <div style="display:flex; flex-wrap:wrap; gap:12px;">
                <a href="{{ route('user.wallet.index') }}" class="tp-btn" style="flex:1; min-width:140px; text-align:center; justify-content:center;">
                    ยกเลิก
                </a>
                <button type="submit" class="tp-btn tp-btn-primary" style="flex:1; min-width:140px; justify-content:center;">
                    ยืนยันการโอนเงิน
                </button>
            </div>
        </form>
    </div>

    {{-- ── วิธีหา Wallet Address ผู้รับ ─────────────────────────── --}}
    <div class="tp-card" style="padding:22px;">
        <div class="tp-section-h" style="margin-bottom:16px;">❓ จะหา Wallet Address ของผู้รับได้อย่างไร?</div>

        <div style="display:flex; flex-direction:column; gap:16px;">
            <div style="display:flex; align-items:flex-start; gap:12px;">
                <span class="tp-tile" style="width:32px; height:32px; border-radius:10px; font-size:14px; flex-shrink:0;"><span style="color:#fff; font-weight:800;">1</span></span>
                <div>
                    <div style="font-weight:700; font-size:13.5px; margin-bottom:2px;">ขอจากผู้รับโดยตรง</div>
                    <div style="font-size:12.5px; color:var(--ink2);">ผู้รับสามารถดู Wallet Address ได้ที่หน้ากระเป๋าเงินของตน</div>
                </div>
            </div>

            <div style="display:flex; align-items:flex-start; gap:12px;">
                <span class="tp-tile" style="width:32px; height:32px; border-radius:10px; font-size:14px; flex-shrink:0;"><span style="color:#fff; font-weight:800;">2</span></span>
                <div>
                    <div style="font-weight:700; font-size:13.5px; margin-bottom:2px;">ตรวจสอบความถูกต้อง</div>
                    <div style="font-size:12.5px; color:var(--ink2);">Wallet Address จะเริ่มต้นด้วย "TPW" และมีความยาวทั้งหมด 19 ตัวอักษร</div>
                </div>
            </div>

            <div style="display:flex; align-items:flex-start; gap:12px;">
                <span class="tp-tile" style="width:32px; height:32px; border-radius:10px; font-size:14px; flex-shrink:0;"><span style="color:#fff; font-weight:800;">3</span></span>
                <div style="min-width:0;">
                    <div style="font-weight:700; font-size:13.5px; margin-bottom:6px;">ตัวอย่าง</div>
                    <code class="tp-num" style="display:inline-block; padding:7px 12px; border-radius:10px; box-shadow:var(--inset-sm); font-size:12px; color:var(--deep1); word-break:break-all;">{{ $wallet->wallet_address }}</code>
                    <div style="font-size:12px; color:var(--ink2); margin-top:6px;">นี่คือ Wallet Address ของคุณ</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── การโอนเงินล่าสุด ─────────────────────────────────── --}}
    <div class="tp-card" style="padding:22px;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:16px;">
            <div class="tp-section-h">📊 การโอนเงินล่าสุด</div>
            <a href="{{ route('user.wallet.transactions') }}" class="tp-btn tp-btn-sm">ดูทั้งหมด →</a>
        </div>
        <div style="text-align:center; padding:32px 20px; color:var(--ink2);">
            <div style="font-size:46px; opacity:.5;">💸</div>
            <div style="font-size:12.5px; margin-top:8px;">คลิก "ดูทั้งหมด" เพื่อดูประวัติการโอนเงิน</div>
        </div>
    </div>

    {{-- ── คำแนะนำ ──────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:20px 24px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            <div class="tp-section-h" style="margin-bottom:14px;">💡 คำแนะนำ</div>
            <ul style="margin:0; padding:0; list-style:none; display:flex; flex-direction:column; gap:8px; font-size:12.5px; color:var(--ink);">
                <li style="display:flex; align-items:flex-start; gap:8px;"><span style="color:#5aa07e;">✅</span><span>ตรวจสอบ Wallet Address ของผู้รับให้ถูกต้องทุกครั้ง</span></li>
                <li style="display:flex; align-items:flex-start; gap:8px;"><span style="color:#5aa07e;">✅</span><span>การโอนเงินจะเกิดขึ้นทันทีหลังยืนยัน</span></li>
                <li style="display:flex; align-items:flex-start; gap:8px;"><span style="color:#5aa07e;">✅</span><span>ตรวจสอบยอดเงินคงเหลือก่อนทำรายการ</span></li>
                <li style="display:flex; align-items:flex-start; gap:8px;"><span style="color:#5aa07e;">✅</span><span>รักษา PIN ของคุณไว้เป็นความลับ</span></li>
            </ul>
        </div>
    </div>
</div>
@endsection
