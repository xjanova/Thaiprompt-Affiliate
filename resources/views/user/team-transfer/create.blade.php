@extends('layouts.user-v4')

@section('title', 'สร้างคำขอย้ายทีม')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px; max-width:800px; margin-inline:auto; width:100%;">

    {{-- ── Hero ─────────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:20px 24px; background:linear-gradient(120deg, color-mix(in srgb, #7c5cbf 18%, transparent), transparent 70%);">
            <a href="{{ route('user.team-transfer.index') }}" style="display:inline-flex; align-items:center; gap:6px; color:var(--ink2); text-decoration:none; font-size:13px; margin-bottom:12px;"><i class="fas fa-arrow-left"></i> กลับไปรายการคำขอ</a>
            <div style="text-align:center;">
                <span class="tp-tile" style="width:64px; height:64px; border-radius:18px; font-size:26px; background:#7c5cbf; margin:0 auto 12px;"><i class="fas fa-exchange-alt" style="color:#fff;"></i></span>
                <h1 style="font-size:clamp(20px,4vw,28px); font-weight:800; margin:0 0 4px;">✏️ สร้างคำขอย้ายทีม</h1>
                <div style="font-size:13px; color:var(--ink2);">กรอกข้อมูลเพื่อขอย้ายไปยังแม่ทีมใหม่</div>
            </div>
        </div>
    </div>

    @if($errors->any())
        <div class="tp-card" style="padding:16px; border-left:4px solid #d9534f;">
            <div style="font-weight:800; color:#d9534f; margin-bottom:8px;"><i class="fas fa-exclamation-circle" style="margin-right:6px;"></i>กรุณาแก้ไขข้อผิดพลาด:</div>
            <ul style="margin:0; padding-left:18px; font-size:13px; color:var(--ink); display:flex; flex-direction:column; gap:4px;">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif
    @if(session('error'))
        <div class="tp-card" style="padding:14px 16px; border-left:4px solid #d9534f;">
            <span style="color:var(--ink); font-weight:600;"><i class="fas fa-exclamation-circle" style="color:#d9534f; margin-right:8px;"></i>{{ session('error') }}</span>
        </div>
    @endif

    {{-- ── แม่ทีมปัจจุบัน ─────────────────────────────────────── --}}
    <div class="tp-card" style="padding:20px; border-left:4px solid #5689b8;">
        <div class="tp-section-h" style="margin-bottom:14px;"><i class="fas fa-info-circle" style="margin-right:6px;"></i>ข้อมูลแม่ทีมปัจจุบัน</div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px;">
            <div><p style="font-size:11px; color:var(--ink2); margin:0 0 2px;">ชื่อแม่ทีม</p><p style="font-weight:700; color:var(--ink); margin:0;">{{ $currentSponsor->user->name ?? 'N/A' }}</p></div>
            <div><p style="font-size:11px; color:var(--ink2); margin:0 0 2px;">รหัสสมาชิก</p><p style="font-weight:700; color:var(--ink); margin:0;">{{ $currentSponsor->member_code ?? 'N/A' }}</p></div>
        </div>
    </div>

    {{-- ── ฟอร์ม ─────────────────────────────────────────────── --}}
    <form method="POST" action="{{ route('user.team-transfer.store') }}" x-data="transferForm()">
        @csrf
        <div class="tp-card" style="padding:24px; display:flex; flex-direction:column; gap:20px;">
            <div>
                <label for="new_sponsor_code" style="display:block; font-size:13px; font-weight:600; color:var(--ink); margin-bottom:8px;">รหัสแม่ทีมใหม่ <span style="color:#d9534f;">*</span></label>
                <input type="text" id="new_sponsor_code" name="new_sponsor_code" value="{{ old('new_sponsor_code') }}" required maxlength="50"
                       class="tp-input" placeholder="กรอกรหัสสมาชิกของแม่ทีมใหม่" x-model="sponsorCode" @input="validateSponsorCode()">
                <p style="margin:8px 0 0; font-size:13px; color:var(--ink2);">💡 กรอกรหัสสมาชิกของแม่ทีมที่คุณต้องการย้ายไป</p>
            </div>

            <div>
                <label for="reason" style="display:block; font-size:13px; font-weight:600; color:var(--ink); margin-bottom:8px;">เหตุผลในการย้าย</label>
                <textarea id="reason" name="reason" rows="4" maxlength="500" class="tp-input" style="resize:vertical;"
                          placeholder="อธิบายเหตุผลที่ต้องการย้ายทีม (ไม่บังคับ)" x-model="reason" @input="updateCharCount()">{{ old('reason') }}</textarea>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-top:8px;">
                    <p style="font-size:13px; color:var(--ink2); margin:0;">📝 ระบุเหตุผลจะช่วยให้แม่ทีมเดิมพิจารณาอนุมัติได้ง่ายขึ้น</p>
                    <p style="font-size:13px; color:var(--ink2); margin:0;" x-text="`${charCount}/500`"></p>
                </div>
            </div>

            <div>
                <label for="notes" style="display:block; font-size:13px; font-weight:600; color:var(--ink); margin-bottom:8px;">หมายเหตุเพิ่มเติม</label>
                <textarea id="notes" name="notes" rows="3" maxlength="500" class="tp-input" style="resize:vertical;" placeholder="ข้อมูลเพิ่มเติม (ไม่บังคับ)">{{ old('notes') }}</textarea>
            </div>

            {{-- ค่าธรรมเนียม --}}
            <div class="tp-card" style="padding:16px; border-left:4px solid #d9a441;">
                <div style="display:flex; gap:12px;">
                    <i class="fas fa-info-circle" style="color:#d9a441; margin-top:2px;"></i>
                    <div>
                        <div style="font-weight:800; color:var(--ink); margin-bottom:6px;">💰 ค่าธรรมเนียมการย้ายทีม</div>
                        <p style="font-size:13px; color:var(--ink); margin:0 0 6px;">ค่าธรรมเนียม: <span style="font-weight:800;">{{ number_format($transferFee, 2) }} บาท</span></p>
                        <ul style="margin:0; padding-left:18px; font-size:13px; color:var(--ink2); display:flex; flex-direction:column; gap:2px;">
                            <li>คุณต้องชำระค่าธรรมเนียมหลังจากแม่ทีมเดิมอนุมัติคำขอ</li>
                            <li>ยอดเงินจะถูกหักจาก Wallet ของคุณ</li>
                            <li>หากยกเลิกคำขอจะได้รับเงินคืนทันที</li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- ขั้นตอน --}}
            <div class="tp-card" style="padding:16px; border-left:4px solid #5689b8;">
                <div style="font-weight:800; color:var(--ink); margin-bottom:12px;"><i class="fas fa-check-circle" style="margin-right:6px;"></i>ขั้นตอนการย้ายทีม</div>
                <ol style="margin:0; padding:0; list-style:none; display:flex; flex-direction:column; gap:8px;">
                    @foreach(['ส่งคำขอย้ายทีม','รอแม่ทีมเดิมอนุมัติ (หรือปฏิเสธ)','ชำระค่าธรรมเนียม '.number_format($transferFee, 2).' บาท','Admin ดำเนินการย้ายทีม','เสร็จสิ้น - คุณได้ย้ายไปยังแม่ทีมใหม่'] as $i => $stepText)
                        <li style="display:flex; align-items:flex-start; gap:12px; font-size:13px; color:var(--ink);">
                            <span style="flex-shrink:0; width:24px; height:24px; background:#5689b8; color:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:11px;">{{ $i + 1 }}</span>
                            <span>{{ $stepText }}</span>
                        </li>
                    @endforeach
                </ol>
            </div>

            {{-- ปุ่ม --}}
            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                <a href="{{ route('user.team-transfer.index') }}" class="tp-btn" style="flex:1; min-width:120px; justify-content:center; text-align:center;">ยกเลิก</a>
                <button type="submit" class="tp-btn tp-btn-primary" style="flex:1; min-width:120px; justify-content:center; background:#5689b8; border-color:#5689b8;"
                        :disabled="!isValid" :style="!isValid ? 'opacity:.5; cursor:not-allowed;' : ''">
                    <i class="fas fa-paper-plane"></i> ส่งคำขอย้ายทีม
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function transferForm() {
    return {
        sponsorCode: '{{ old('new_sponsor_code') }}',
        reason: '{{ old('reason') }}',
        charCount: {{ strlen(old('reason', '')) }},
        isValid: false,

        init() {
            this.validateSponsorCode();
        },

        validateSponsorCode() {
            this.isValid = this.sponsorCode.trim().length > 0;
        },

        updateCharCount() {
            this.charCount = this.reason.length;
        }
    }
}
</script>
@endpush
@endsection
