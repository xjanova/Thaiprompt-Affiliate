{{--
 | สมัครเปิดร้านค้า (สมาชิกทั่วไป) — SELLER-07
 | ตัวแปร: $user, $store (VendorStore|null), $state (can_apply|pending|rejected|approved|seller|role_not_eligible), $kycApproved (bool)
 | ฟอร์ม POST → route('user.seller-apply.store')
 --}}
@extends('layouts.user-v4')

@section('title', 'สมัครเปิดร้านค้า')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px; max-width:820px;">

    {{-- ── Hero ─────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:22px 24px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 18%, transparent), transparent 70%);">
            <div style="display:flex; align-items:center; gap:14px;">
                <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:24px;">🏪</span>
                <div>
                    <h1 style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0; color:var(--ink);">เปิดร้านค้าบนไทยพร๊อมท์</h1>
                    <div style="font-size:13px; color:var(--ink2); margin-top:3px;">ขายสินค้าให้สมาชิกทั่วประเทศ ส่งพัสดุหรือให้ไรเดอร์ในพื้นที่ไปส่งก็ได้</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── สถานะคำขอ ─────────────────────────────────────── --}}
    @if(in_array($state, ['seller', 'approved'], true))
        <div class="tp-card" style="padding:22px 24px;">
            <div class="tp-section-h" style="margin-bottom:8px;">✅ คุณเป็นผู้ขายแล้ว</div>
            <p style="font-size:14px; color:var(--ink2); margin:0 0 14px;">
                ร้านของคุณได้รับการอนุมัติแล้ว เข้าหลังร้านเพื่อยืนยันตัวตน (KYC) และเริ่มลงสินค้า
            </p>
            <a href="{{ route('seller.onboarding.index') }}" class="tp-btn tp-btn-primary" style="text-decoration:none;">
                <i class="fas fa-store"></i> <span>ไปหลังร้าน</span>
            </a>
        </div>
    @elseif($state === 'pending')
        <div class="tp-card" style="padding:22px 24px;">
            <div class="tp-section-h" style="margin-bottom:8px;">⏳ คำขอของคุณกำลังรอตรวจสอบ</div>
            <p style="font-size:14px; color:var(--ink2); margin:0 0 6px;">
                ร้าน <strong style="color:var(--ink);">{{ $store->store_name }}</strong>
                ส่งคำขอเมื่อ {{ $store->updated_at?->format('d/m/Y H:i') }}
            </p>
            <p style="font-size:13px; color:var(--ink2); margin:0;">ทีมงานจะแจ้งผลผ่านการแจ้งเตือน (ในเว็บและแอป)</p>
            @unless($kycApproved)
                <div class="tp-inset-sm" style="margin-top:14px; padding:12px 14px; border-radius:12px; font-size:13px; color:var(--ink2);">
                    💡 ระหว่างรอ ยืนยันตัวตน (KYC) ไว้ก่อนได้เลย ร้านจะเปิดขายได้ทันทีหลังอนุมัติ
                    <a href="{{ route('user.kyc.index') }}" style="color:var(--deep1); font-weight:600;">ยืนยันตัวตน</a>
                </div>
            @endunless
        </div>
    @elseif($state === 'role_not_eligible')
        <div class="tp-card" style="padding:22px 24px;">
            <div class="tp-section-h" style="margin-bottom:8px;">ℹ️ บัญชีประเภทนี้สมัครเปิดร้านเองไม่ได้</div>
            <p style="font-size:14px; color:var(--ink2); margin:0 0 14px;">
                บัญชีของคุณมีบทบาทเฉพาะอยู่แล้ว หากต้องการเปิดร้านค้าด้วย กรุณาติดต่อทีมงาน
            </p>
            <a href="{{ route('user.tickets.index') }}" class="tp-btn tp-btn-sm" style="text-decoration:none;">
                <i class="fas fa-headset"></i> <span>ติดต่อทีมงาน</span>
            </a>
        </div>
    @endif

    {{-- ── ฟอร์มสมัคร (ยื่นครั้งแรก หรือยื่นใหม่หลังถูกปฏิเสธ) ── --}}
    @if(in_array($state, ['can_apply', 'rejected'], true))
        @if($state === 'rejected' && $store?->suspension_reason)
            <div class="tp-card" style="padding:18px 22px; border-left:4px solid #d9534f;">
                <div style="font-weight:700; color:var(--ink); margin-bottom:4px;">คำขอครั้งก่อนยังไม่ผ่านการอนุมัติ</div>
                <div style="font-size:13.5px; color:var(--ink2);">เหตุผล: {{ $store->suspension_reason }}</div>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:6px;">แก้ไขข้อมูลด้านล่างแล้วยื่นใหม่ได้เลย</div>
            </div>
        @endif

        <form method="POST" action="{{ route('user.seller-apply.store') }}" class="tp-card" style="padding:22px 24px; display:flex; flex-direction:column; gap:16px;"
              x-data="{ businessType: @js(old('business_type', $store?->business_type ?? 'individual')), submitting: false }"
              @submit="submitting = true">
            @csrf

            @if($errors->any())
                <div style="padding:12px 14px; border-radius:12px; background:color-mix(in srgb, #d9534f 12%, transparent); color:#b43c38; font-size:13.5px;">
                    <ul style="margin:0; padding-left:18px;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="tp-section-h">ข้อมูลร้านค้า</div>

            <label style="display:flex; flex-direction:column; gap:6px;">
                <span style="font-size:13px; font-weight:600; color:var(--ink);">ชื่อร้าน *</span>
                <input type="text" name="store_name" class="tp-input" maxlength="100" required
                       value="{{ old('store_name', $store?->store_name) }}" placeholder="เช่น ร้านผักป้าแดง">
            </label>

            <div style="display:flex; flex-direction:column; gap:6px;">
                <span style="font-size:13px; font-weight:600; color:var(--ink);">ประเภทผู้ขาย *</span>
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <label class="tp-inset-sm" style="display:flex; align-items:center; gap:8px; padding:10px 14px; border-radius:12px; cursor:pointer; min-height:44px;">
                        <input type="radio" name="business_type" value="individual" x-model="businessType"> <span>บุคคลธรรมดา</span>
                    </label>
                    <label class="tp-inset-sm" style="display:flex; align-items:center; gap:8px; padding:10px 14px; border-radius:12px; cursor:pointer; min-height:44px;">
                        <input type="radio" name="business_type" value="company" x-model="businessType"> <span>นิติบุคคล (บริษัท/หจก.)</span>
                    </label>
                </div>
            </div>

            <div x-show="businessType === 'company'" x-cloak style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:14px;">
                <label style="display:flex; flex-direction:column; gap:6px;">
                    <span style="font-size:13px; font-weight:600; color:var(--ink);">ชื่อบริษัท *</span>
                    <input type="text" name="company_name" class="tp-input" maxlength="255"
                           value="{{ old('company_name', $store?->company_name) }}">
                </label>
                <label style="display:flex; flex-direction:column; gap:6px;">
                    <span style="font-size:13px; font-weight:600; color:var(--ink);">เลขประจำตัวผู้เสียภาษี (13 หลัก) *</span>
                    <input type="text" name="tax_id" class="tp-input" inputmode="numeric" maxlength="13"
                           value="{{ old('tax_id', $store?->tax_id) }}">
                </label>
            </div>

            <label style="display:flex; flex-direction:column; gap:6px;">
                <span style="font-size:13px; font-weight:600; color:var(--ink);">เบอร์โทรร้าน *</span>
                <input type="tel" name="store_phone" class="tp-input" inputmode="numeric" maxlength="10" required
                       value="{{ old('store_phone', $store?->store_phone ?? $user->phone) }}" placeholder="0812345678">
            </label>

            <label style="display:flex; flex-direction:column; gap:6px;">
                <span style="font-size:13px; font-weight:600; color:var(--ink);">รายละเอียดร้าน</span>
                <textarea name="store_description" class="tp-input" rows="3" maxlength="1000"
                          placeholder="ขายอะไร จุดเด่นของร้าน">{{ old('store_description', $store?->store_description) }}</textarea>
            </label>

            <div class="tp-section-h" style="margin-top:6px;">ที่อยู่ร้าน / จุดรับสินค้า</div>

            <label style="display:flex; flex-direction:column; gap:6px;">
                <span style="font-size:13px; font-weight:600; color:var(--ink);">ที่อยู่ *</span>
                <textarea name="store_address" class="tp-input" rows="2" maxlength="500" required
                          placeholder="บ้านเลขที่ ถนน ตำบล">{{ old('store_address', $store?->store_address) }}</textarea>
            </label>

            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:14px;">
                <label style="display:flex; flex-direction:column; gap:6px;">
                    <span style="font-size:13px; font-weight:600; color:var(--ink);">อำเภอ/เขต *</span>
                    <input type="text" name="store_city" class="tp-input" maxlength="100" required
                           value="{{ old('store_city', $store?->store_city) }}">
                </label>
                <label style="display:flex; flex-direction:column; gap:6px;">
                    <span style="font-size:13px; font-weight:600; color:var(--ink);">จังหวัด *</span>
                    <input type="text" name="store_state" class="tp-input" maxlength="100" required
                           value="{{ old('store_state', $store?->store_state) }}">
                </label>
                <label style="display:flex; flex-direction:column; gap:6px;">
                    <span style="font-size:13px; font-weight:600; color:var(--ink);">รหัสไปรษณีย์ *</span>
                    <input type="text" name="store_postal_code" class="tp-input" inputmode="numeric" maxlength="5" required
                           value="{{ old('store_postal_code', $store?->store_postal_code) }}">
                </label>
            </div>

            <div class="tp-inset-sm" style="padding:12px 14px; border-radius:12px; font-size:13px; color:var(--ink2); line-height:1.7;">
                ขั้นตอนหลังส่งคำขอ: ทีมงานตรวจสอบ → อนุมัติ → คุณยืนยันตัวตน (KYC) ที่หลังร้าน → เริ่มลงสินค้าได้
                @unless($kycApproved)
                    <br>💡 ยืนยันตัวตนไว้ก่อนได้ที่ <a href="{{ route('user.kyc.index') }}" style="color:var(--deep1); font-weight:600;">หน้า KYC</a>
                @endunless
            </div>

            <label style="display:flex; align-items:flex-start; gap:10px; font-size:13.5px; color:var(--ink); cursor:pointer;">
                <input type="checkbox" name="accept_terms" value="1" style="width:18px; height:18px; margin-top:2px;" {{ old('accept_terms') ? 'checked' : '' }} required>
                <span>ฉันยืนยันว่าข้อมูลถูกต้อง และยอมรับ
                    <a href="{{ route('terms-of-service') }}" target="_blank" style="color:var(--deep1);">ข้อกำหนดการใช้บริการ</a>
                    และ <a href="{{ route('privacy-policy') }}" target="_blank" style="color:var(--deep1);">นโยบายความเป็นส่วนตัว</a>
                </span>
            </label>

            <div>
                <button type="submit" class="tp-btn tp-btn-primary" style="min-height:44px;" :disabled="submitting">
                    <i class="fas fa-paper-plane"></i>
                    <span x-text="submitting ? 'กำลังส่ง...' : @js($state === 'rejected' ? 'ยื่นคำขอใหม่' : 'ส่งคำขอเปิดร้าน')"></span>
                </button>
            </div>
        </form>
    @endif
</div>
@endsection
