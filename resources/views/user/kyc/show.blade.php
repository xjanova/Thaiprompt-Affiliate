@extends('layouts.user-v4')

@section('title', 'รายละเอียดการยืนยันตัวตน')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── Hero ─────────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:20px 24px; background:linear-gradient(120deg, color-mix(in srgb, #7c5cbf 18%, transparent), transparent 70%);">
            <div style="display:flex; align-items:center; gap:14px; flex-wrap:wrap;">
                <a href="{{ route('user.kyc.index') }}" class="tp-icon-btn" title="กลับ"><i class="fas fa-arrow-left"></i></a>
                <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:23px; background:#7c5cbf;"><i class="fas fa-clipboard-check" style="color:#fff;"></i></span>
                <div>
                    <h1 style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">รายละเอียดการยืนยันตัวตน</h1>
                    <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">ตรวจสอบสถานะและรายละเอียดการยืนยันตัวตน</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── สถานะ ─────────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:20px;">
        <div class="tp-section-h" style="margin-bottom:14px;">สถานะการยืนยันตัวตน</div>
        <div style="display:flex; flex-direction:column; gap:12px;">
            <div style="display:flex; align-items:center; justify-content:space-between;">
                <span style="font-size:13px; color:var(--ink2);">สถานะ:</span>
                @if($kycVerification->status === 'pending')
                    <span style="display:inline-flex; padding:6px 14px; border-radius:999px; font-size:13px; font-weight:600; color:#d9a441; background:color-mix(in srgb, #d9a441 16%, transparent);"><i class="fas fa-clock" style="margin-right:5px;"></i>รอการตรวจสอบ</span>
                @elseif($kycVerification->status === 'approved')
                    <span style="display:inline-flex; padding:6px 14px; border-radius:999px; font-size:13px; font-weight:600; color:#5aa07e; background:color-mix(in srgb, #5aa07e 16%, transparent);"><i class="fas fa-check-circle" style="margin-right:5px;"></i>อนุมัติแล้ว</span>
                @elseif($kycVerification->status === 'rejected')
                    <span style="display:inline-flex; padding:6px 14px; border-radius:999px; font-size:13px; font-weight:600; color:#d9534f; background:color-mix(in srgb, #d9534f 16%, transparent);"><i class="fas fa-times-circle" style="margin-right:5px;"></i>ไม่ผ่านการตรวจสอบ</span>
                @endif
            </div>

            <div style="display:flex; align-items:center; justify-content:space-between;">
                <span style="font-size:13px; color:var(--ink2);">วันที่ส่ง:</span>
                <span style="font-size:13px; font-weight:600; color:var(--ink);">{{ $kycVerification->submitted_at ? $kycVerification->submitted_at->format('d/m/Y H:i') : '-' }}</span>
            </div>

            @if($kycVerification->reviewed_at)
                <div style="display:flex; align-items:center; justify-content:space-between;">
                    <span style="font-size:13px; color:var(--ink2);">วันที่ตรวจสอบ:</span>
                    <span style="font-size:13px; font-weight:600; color:var(--ink);">{{ $kycVerification->reviewed_at->format('d/m/Y H:i') }}</span>
                </div>
            @endif

            @if($kycVerification->reviewer)
                <div style="display:flex; align-items:center; justify-content:space-between;">
                    <span style="font-size:13px; color:var(--ink2);">ตรวจสอบโดย:</span>
                    <span style="font-size:13px; font-weight:600; color:var(--ink);">{{ $kycVerification->reviewer->name }}</span>
                </div>
            @endif

            @if($kycVerification->status === 'rejected' && $kycVerification->rejection_reason)
                <div style="padding-top:14px; border-top:1px solid color-mix(in srgb, var(--ink2) 15%, transparent);">
                    <p style="font-size:13px; color:var(--ink2); margin:0 0 8px;">เหตุผลในการปฏิเสธ:</p>
                    <div style="border-radius:10px; box-shadow:var(--inset-sm); padding:14px; border-left:4px solid #d9534f;">
                        <p style="font-size:13px; color:var(--ink); margin:0;">{{ $kycVerification->rejection_reason }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- ── เอกสาร ─────────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:20px;">
        <div class="tp-section-h" style="margin-bottom:14px;">เอกสารที่ส่ง</div>

        @if(!empty($kycVerification->extracted_data['document_type']))
            <div style="margin-bottom:16px;">
                @if($kycVerification->extracted_data['document_type'] === 'driver_license')
                    <span style="display:inline-flex; padding:4px 12px; border-radius:999px; font-size:13px; font-weight:600; color:#7c5cbf; background:color-mix(in srgb, #7c5cbf 16%, transparent);"><i class="fas fa-id-card-alt" style="margin-right:6px;"></i>ใบขับขี่</span>
                @else
                    <span style="display:inline-flex; padding:4px 12px; border-radius:999px; font-size:13px; font-weight:600; color:#5689b8; background:color-mix(in srgb, #5689b8 16%, transparent);"><i class="fas fa-id-card" style="margin-right:6px;"></i>บัตรประชาชน</span>
                @endif
            </div>
        @endif

        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:16px;">
            <div>
                <div style="font-size:13px; font-weight:600; color:var(--ink); margin-bottom:10px;">
                    @if(!empty($kycVerification->extracted_data['document_type']) && $kycVerification->extracted_data['document_type'] === 'driver_license')
                        <i class="fas fa-id-card-alt" style="margin-right:4px;"></i>รูปใบขับขี่
                    @else
                        <i class="fas fa-id-card" style="margin-right:4px;"></i>รูปบัตรประชาชน
                    @endif
                </div>
                @if($kycVerification->idCardImageUrl())
                    <div style="border-radius:10px; overflow:hidden; box-shadow:var(--inset-sm);">
                        <img src="{{ $kycVerification->idCardImageUrl() }}" alt="ID Document"
                             style="width:100%; height:auto; cursor:pointer; display:block;" onclick="openImageModal(this.src)">
                    </div>
                @else
                    {{-- ไฟล์รูปหาย — บอกลูกค้าตรงๆ ว่าต้องส่งใหม่ (ไม่โชว์พาธภายในระบบ) --}}
                    <div style="border-radius:10px; padding:28px 16px; text-align:center; border:2px dashed color-mix(in srgb, var(--ink2) 35%, transparent);">
                        <i class="fas fa-exclamation-triangle" style="font-size:24px; color:#d98a3a; display:block; margin-bottom:10px;"></i>
                        <div style="font-size:13px; font-weight:600; color:var(--ink);">ไม่พบไฟล์รูปภาพ</div>
                        <div style="font-size:12px; color:var(--ink2); margin-top:4px;">กรุณาส่งเอกสารใหม่อีกครั้ง</div>
                    </div>
                @endif
            </div>

            <div>
                <div style="font-size:13px; font-weight:600; color:var(--ink); margin-bottom:10px;">
                    @if(!empty($kycVerification->extracted_data['document_type']) && $kycVerification->extracted_data['document_type'] === 'driver_license')
                        <i class="fas fa-user-circle" style="margin-right:4px;"></i>รูปถ่ายตัวเองพร้อมใบขับขี่
                    @else
                        <i class="fas fa-user-circle" style="margin-right:4px;"></i>รูปถ่ายตัวเองพร้อมบัตรประชาชน
                    @endif
                </div>
                @if($kycVerification->selfieImageUrl())
                    <div style="border-radius:10px; overflow:hidden; box-shadow:var(--inset-sm);">
                        <img src="{{ $kycVerification->selfieImageUrl() }}" alt="Selfie with ID"
                             style="width:100%; height:auto; cursor:pointer; display:block;" onclick="openImageModal(this.src)">
                    </div>
                @else
                    {{-- ไฟล์รูปหาย — บอกลูกค้าตรงๆ ว่าต้องส่งใหม่ (ไม่โชว์พาธภายในระบบ) --}}
                    <div style="border-radius:10px; padding:28px 16px; text-align:center; border:2px dashed color-mix(in srgb, var(--ink2) 35%, transparent);">
                        <i class="fas fa-exclamation-triangle" style="font-size:24px; color:#d98a3a; display:block; margin-bottom:10px;"></i>
                        <div style="font-size:13px; font-weight:600; color:var(--ink);">ไม่พบไฟล์รูปภาพ</div>
                        <div style="font-size:12px; color:var(--ink2); margin-top:4px;">กรุณาส่งเอกสารใหม่อีกครั้ง</div>
                    </div>
                @endif
            </div>
        </div>

        <p style="font-size:11px; color:var(--ink2); margin:16px 0 0; text-align:center;"><i class="fas fa-info-circle" style="margin-right:4px;"></i>คลิกที่รูปภาพเพื่อดูขนาดเต็ม</p>
    </div>

    @if($kycVerification->status === 'rejected')
        <div class="tp-card" style="padding:24px; text-align:center;">
            <p style="color:var(--ink2); margin:0 0 16px;">หากคุณต้องการส่งเอกสารใหม่ กรุณากดปุ่มด้านล่าง</p>
            <a href="{{ route('user.kyc.create') }}" class="tp-btn tp-btn-primary" style="background:#5689b8; border-color:#5689b8;"><i class="fas fa-redo"></i> ส่งเอกสารใหม่</a>
        </div>
    @endif
</div>

{{-- Image Modal --}}
<div id="imageModal" x-data
     style="position:fixed; inset:0; background:rgba(0,0,0,0.75); z-index:50; display:none; align-items:center; justify-content:center; padding:16px;"
     onclick="closeImageModal()">
    <div style="position:relative; max-width:900px; max-height:100%;">
        <button onclick="closeImageModal()" style="position:absolute; top:16px; right:16px; color:#fff; font-size:24px; z-index:10; background:none; border:none; cursor:pointer;"><i class="fas fa-times"></i></button>
        <img id="modalImage" src="" alt="Full Size" style="max-width:100%; max-height:90vh; border-radius:10px;">
    </div>
</div>

@push('scripts')
<script>
function openImageModal(src) {
    document.getElementById('modalImage').src = src;
    document.getElementById('imageModal').style.display = 'flex';
}

function closeImageModal() {
    document.getElementById('imageModal').style.display = 'none';
}
</script>
@endpush
@endsection
