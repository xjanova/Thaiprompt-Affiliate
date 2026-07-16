@extends('layouts.user-v4')

@section('title', 'ส่งเอกสารยืนยันตัวตน')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── Hero ─────────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:20px 24px; background:linear-gradient(120deg, color-mix(in srgb, #5aa07e 18%, transparent), transparent 70%);">
            <div style="display:flex; align-items:center; gap:14px; flex-wrap:wrap;">
                <a href="{{ route('user.kyc.index') }}" class="tp-icon-btn" title="กลับ"><i class="fas fa-arrow-left"></i></a>
                <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:23px; background:#5aa07e;"><i class="fas fa-upload" style="color:#fff;"></i></span>
                <div>
                    <h1 style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">ส่งเอกสารยืนยันตัวตน</h1>
                    <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">กรุณาอัปโหลดเอกสารเพื่อยืนยันตัวตน</div>
                </div>
            </div>
        </div>
    </div>

    @if(session('error'))
        <div class="tp-card" style="padding:14px 16px; border-left:4px solid #d9534f;">
            <i class="fas fa-exclamation-circle" style="color:#d9534f; margin-right:8px;"></i>{{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="tp-card" style="padding:16px; border-left:4px solid #d9534f;">
            <div style="font-weight:800; color:#d9534f; margin-bottom:8px;"><i class="fas fa-exclamation-circle" style="margin-right:6px;"></i>พบข้อผิดพลาด:</div>
            <ul style="margin:0; padding-left:18px; font-size:13px; color:var(--ink); display:flex; flex-direction:column; gap:4px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ── ตัวอย่างการถ่ายรูป ─────────────────────────────────── --}}
    <div class="tp-card" style="padding:20px;">
        <div class="tp-section-h" style="margin-bottom:16px;"><i class="fas fa-images" style="margin-right:6px;"></i>ตัวอย่างการถ่ายรูป</div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:16px;">
            <div style="border-radius:12px; box-shadow:var(--inset-sm); padding:16px;">
                <div style="font-weight:700; color:var(--ink); text-align:center; margin-bottom:12px;"><i class="fas fa-id-card" style="color:#5689b8; margin-right:6px;"></i>รูปบัตรประชาชน / ใบขับขี่</div>
                <div style="border-radius:10px; padding:16px; margin-bottom:12px; border:2px dashed color-mix(in srgb, var(--ink2) 30%, transparent);">
                    <svg style="width:100%; height:128px;" viewBox="0 0 320 180" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect width="320" height="180" rx="8" fill="white"/>
                        <rect x="10" y="10" width="300" height="160" rx="4" fill="#E5E7EB"/>
                        <text x="160" y="30" text-anchor="middle" font-size="14" fill="#374151" font-weight="bold">บัตรประจำตัวประชาชน</text>
                        <rect x="20" y="45" width="60" height="80" rx="4" fill="#D1D5DB"/>
                        <text x="50" y="90" text-anchor="middle" font-size="24" fill="#6B7280">👤</text>
                        <rect x="90" y="50" width="120" height="8" rx="2" fill="#D1D5DB"/>
                        <rect x="90" y="65" width="100" height="8" rx="2" fill="#D1D5DB"/>
                        <rect x="90" y="80" width="80" height="8" rx="2" fill="#D1D5DB"/>
                        <rect x="90" y="95" width="110" height="8" rx="2" fill="#D1D5DB"/>
                        <rect x="20" y="135" width="280" height="25" rx="4" fill="#3B82F6" fill-opacity="0.2"/>
                        <text x="160" y="152" text-anchor="middle" font-size="12" fill="#1E40AF">เลขบัตรประชาชน 13 หลัก</text>
                    </svg>
                </div>
                <div style="display:flex; flex-direction:column; gap:5px; font-size:12px; color:var(--ink2);">
                    <div><i class="fas fa-check" style="color:#5aa07e; margin-right:6px;"></i>ถ่ายทั้งหน้าบัตรให้เห็นชัดเจน</div>
                    <div><i class="fas fa-check" style="color:#5aa07e; margin-right:6px;"></i>ข้อความและตัวเลขต้องอ่านได้</div>
                    <div><i class="fas fa-check" style="color:#5aa07e; margin-right:6px;"></i>แสงสว่างเพียงพอ ไม่มืด ไม่เบลอ</div>
                </div>
            </div>

            <div style="border-radius:12px; box-shadow:var(--inset-sm); padding:16px;">
                <div style="font-weight:700; color:var(--ink); text-align:center; margin-bottom:12px;"><i class="fas fa-user-circle" style="color:#5689b8; margin-right:6px;"></i>รูปถ่ายตัวเอง + บัตร</div>
                <div style="border-radius:10px; padding:16px; margin-bottom:12px; border:2px dashed color-mix(in srgb, var(--ink2) 30%, transparent);">
                    <svg style="width:100%; height:128px;" viewBox="0 0 320 180" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect width="320" height="180" rx="8" fill="white"/>
                        <rect x="10" y="10" width="300" height="160" rx="4" fill="#E5E7EB"/>
                        <circle cx="160" cy="70" r="35" fill="#FDE68A"/>
                        <circle cx="150" cy="65" r="3" fill="#374151"/>
                        <circle cx="170" cy="65" r="3" fill="#374151"/>
                        <path d="M 150 80 Q 160 85 170 80" stroke="#374151" stroke-width="2" fill="none"/>
                        <rect x="190" y="95" width="90" height="55" rx="3" fill="#BFDBFE" stroke="#3B82F6" stroke-width="2"/>
                        <text x="235" y="115" text-anchor="middle" font-size="8" fill="#1E40AF">บัตรประชาชน</text>
                        <rect x="200" y="120" width="20" height="25" rx="2" fill="#93C5FD"/>
                        <rect x="225" y="123" width="35" height="3" rx="1" fill="#93C5FD"/>
                        <rect x="225" y="130" width="30" height="3" rx="1" fill="#93C5FD"/>
                    </svg>
                </div>
                <div style="display:flex; flex-direction:column; gap:5px; font-size:12px; color:var(--ink2);">
                    <div><i class="fas fa-check" style="color:#5aa07e; margin-right:6px;"></i>ถือบัตรไว้ใกล้ใบหน้า</div>
                    <div><i class="fas fa-check" style="color:#5aa07e; margin-right:6px;"></i>ใบหน้าและบัตรต้องชัดเจนทั้งคู่</div>
                    <div><i class="fas fa-check" style="color:#5aa07e; margin-right:6px;"></i>ไม่ปิดบังใบหน้าด้วยหมวกหรือแว่น</div>
                </div>
            </div>
        </div>
        <div style="margin-top:16px; padding:12px; border-radius:10px; box-shadow:var(--inset-sm);">
            <p style="font-size:12px; color:var(--ink2); margin:0;"><i class="fas fa-lightbulb" style="color:#d9a441; margin-right:6px;"></i><strong>เคล็ดลับ:</strong> รูปจะถูกแปลงเป็น WebP อัตโนมัติเพื่อประหยัดพื้นที่และเพิ่มความเร็ว</p>
        </div>
    </div>

    {{-- ── เงื่อนไข ──────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:20px; border-left:4px solid #5689b8;">
        <div class="tp-section-h" style="margin-bottom:14px;"><i class="fas fa-clipboard-check" style="margin-right:6px;"></i>เงื่อนไขและข้อกำหนด</div>
        <div style="display:flex; flex-direction:column; gap:12px;">
            <div style="display:flex; gap:8px;">
                <i class="fas fa-check-circle" style="color:#5689b8; margin-top:3px;"></i>
                <div><div style="font-weight:700; color:var(--ink);">บัตรประชาชน หรือ ใบขับขี่ ต้องไม่หมดอายุ</div><div style="font-size:12px; color:var(--ink2);">ตรวจสอบวันหมดอายุบนบัตร</div></div>
            </div>
            <div style="display:flex; gap:8px;">
                <i class="fas fa-check-circle" style="color:#5689b8; margin-top:3px;"></i>
                <div><div style="font-weight:700; color:var(--ink);">รองรับไฟล์: JPEG, JPG, PNG</div><div style="font-size:12px; color:var(--ink2);">ขนาดไม่เกิน 5 MB ต่อไฟล์</div></div>
            </div>
            <div style="display:flex; gap:8px;">
                <i class="fas fa-check-circle" style="color:#5689b8; margin-top:3px;"></i>
                <div><div style="font-weight:700; color:var(--ink);">รูปถ่ายต้องชัดเจน ไม่มืด ไม่เบลอ</div><div style="font-size:12px; color:var(--ink2);">ควรถ่ายในที่แสงสว่างเพียงพอ</div></div>
            </div>
        </div>
    </div>

    {{-- ── ฟอร์มอัปโหลด ──────────────────────────────────────── --}}
    <div class="tp-card" style="padding:20px;" x-data="kycUpload()">
        <form action="{{ route('user.kyc.store') }}" method="POST" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:18px;">
            @csrf

            {{-- บัตรประชาชน --}}
            <div>
                <label style="display:block; font-size:12.5px; font-weight:600; color:var(--ink); margin-bottom:8px;"><i class="fas fa-id-card" style="margin-right:4px;"></i>รูปบัตรประชาชน / ใบขับขี่ <span style="color:#d9534f;">*</span></label>
                <div style="border:2px dashed color-mix(in srgb, var(--ink2) 30%, transparent); border-radius:12px; padding:24px; text-align:center; cursor:pointer;" @click="$refs.idCardInput.click()">
                    <template x-if="!idCardPreview">
                        <div>
                            <i class="fas fa-cloud-upload-alt" style="font-size:36px; color:var(--ink2); margin-bottom:10px;"></i>
                            <p style="font-size:13px; color:var(--ink2); margin:0 0 4px;">คลิกเพื่ออัปโหลดรูปบัตรประชาชน หรือ ใบขับขี่</p>
                            <p style="font-size:11px; color:var(--ink2); margin:0;">JPEG, JPG, PNG (ไม่เกิน 5 MB)</p>
                        </div>
                    </template>
                    <template x-if="idCardPreview">
                        <div>
                            <img :src="idCardPreview" style="max-height:256px; margin:0 auto 12px; border-radius:10px;" alt="ID Card Preview">
                            <button type="button" @click.stop="removeIdCard()" class="tp-btn tp-btn-sm" style="color:#d9534f;"><i class="fas fa-trash"></i> ลบรูป</button>
                        </div>
                    </template>
                </div>
                <input type="file" name="id_card_image" id="id_card_image" accept="image/jpeg,image/jpg,image/png"
                       style="display:none;" x-ref="idCardInput" @change="previewIdCard($event)" required>
                @error('id_card_image')
                    <p style="font-size:12px; color:#d9534f; margin-top:6px;">{{ $message }}</p>
                @enderror
            </div>

            {{-- เซลฟี่ --}}
            <div>
                <label style="display:block; font-size:12.5px; font-weight:600; color:var(--ink); margin-bottom:8px;"><i class="fas fa-user-circle" style="margin-right:4px;"></i>รูปถ่ายตัวเองพร้อมบัตรประชาชน <span style="color:#d9534f;">*</span></label>
                <div style="border:2px dashed color-mix(in srgb, var(--ink2) 30%, transparent); border-radius:12px; padding:24px; text-align:center; cursor:pointer;" @click="$refs.selfieInput.click()">
                    <template x-if="!selfiePreview">
                        <div>
                            <i class="fas fa-cloud-upload-alt" style="font-size:36px; color:var(--ink2); margin-bottom:10px;"></i>
                            <p style="font-size:13px; color:var(--ink2); margin:0 0 4px;">คลิกเพื่ออัปโหลดรูปถ่ายตัวเอง</p>
                            <p style="font-size:11px; color:var(--ink2); margin:0;">JPEG, JPG, PNG (ไม่เกิน 5 MB)</p>
                        </div>
                    </template>
                    <template x-if="selfiePreview">
                        <div>
                            <img :src="selfiePreview" style="max-height:256px; margin:0 auto 12px; border-radius:10px;" alt="Selfie Preview">
                            <button type="button" @click.stop="removeSelfie()" class="tp-btn tp-btn-sm" style="color:#d9534f;"><i class="fas fa-trash"></i> ลบรูป</button>
                        </div>
                    </template>
                </div>
                <input type="file" name="selfie_image" id="selfie_image" accept="image/jpeg,image/jpg,image/png"
                       style="display:none;" x-ref="selfieInput" @change="previewSelfie($event)" required>
                @error('selfie_image')
                    <p style="font-size:12px; color:#d9534f; margin-top:6px;">{{ $message }}</p>
                @enderror
            </div>

            {{-- ปุ่ม --}}
            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                <a href="{{ route('user.kyc.index') }}" class="tp-btn" style="flex:1; min-width:120px; justify-content:center; text-align:center;"><i class="fas fa-times"></i> ยกเลิก</a>
                <button type="submit" class="tp-btn tp-btn-primary" style="flex:1; min-width:120px; justify-content:center; background:#5aa07e; border-color:#5aa07e;"><i class="fas fa-paper-plane"></i> ส่งเอกสาร</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function kycUpload() {
    return {
        idCardPreview: null,
        selfiePreview: null,

        previewIdCard(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.idCardPreview = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        },

        previewSelfie(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.selfiePreview = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        },

        removeIdCard() {
            this.idCardPreview = null;
            this.$refs.idCardInput.value = '';
        },

        removeSelfie() {
            this.selfiePreview = null;
            this.$refs.selfieInput.value = '';
        }
    };
}
</script>
@endpush
@endsection
