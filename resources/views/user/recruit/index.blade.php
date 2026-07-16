@extends('layouts.user-v4')

@section('title', 'หน้าแนะนำสมาชิก')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;" x-data="recruitPageManager()">

    {{-- ── Hero + สถิติ ──────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:20px 24px; background:linear-gradient(120deg, color-mix(in srgb, #c05a8f 18%, transparent), transparent 70%);">
            <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:14px; margin-bottom:18px;">
                <div style="display:flex; align-items:center; gap:14px;">
                    <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:22px; background:#c05a8f;"><i class="fas fa-bullhorn" style="color:#fff;"></i></span>
                    <div>
                        <h1 style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">📢 หน้าแนะนำสมาชิก</h1>
                        <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">จัดการข้อมูลและดูสถิติหน้า Recruit ของคุณ</div>
                    </div>
                </div>
                <a href="{{ $recruitUrl }}" target="_blank" class="tp-btn tp-btn-sm"><i class="fas fa-external-link-alt"></i> ดูหน้า Recruit</a>
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:12px;">
                <div class="tp-card" style="padding:14px; text-align:center;">
                    <div style="font-size:11px; color:var(--ink2); margin-bottom:2px;">การเข้าชม</div>
                    <div class="tp-num" style="font-size:26px; font-weight:800; color:var(--deep1);">{{ number_format($stats['total_views']) }}</div>
                    <div style="font-size:11px; color:var(--ink2); margin-top:2px;">+{{ $stats['views_last_7_days'] }} ใน 7 วัน</div>
                </div>
                <div class="tp-card" style="padding:14px; text-align:center;">
                    <div style="font-size:11px; color:var(--ink2); margin-bottom:2px;">ผู้มุ่งหวัง</div>
                    <div class="tp-num" style="font-size:26px; font-weight:800; color:#c05a8f;">{{ number_format($stats['total_leads']) }}</div>
                    <div style="font-size:11px; color:var(--ink2); margin-top:2px;">+{{ $stats['leads_last_7_days'] }} ใน 7 วัน</div>
                </div>
                <div class="tp-card" style="padding:14px; text-align:center;">
                    <div style="font-size:11px; color:var(--ink2); margin-bottom:2px;">สมาชิกใหม่</div>
                    <div class="tp-num" style="font-size:26px; font-weight:800; color:#5aa07e;">{{ number_format($stats['total_conversions']) }}</div>
                    <div style="font-size:11px; color:var(--ink2); margin-top:2px;">คนสมัครสำเร็จ</div>
                </div>
                <div class="tp-card" style="padding:14px; text-align:center;">
                    <div style="font-size:11px; color:var(--ink2); margin-bottom:2px;">อัตราสำเร็จ</div>
                    <div class="tp-num" style="font-size:26px; font-weight:800; color:#5689b8;">{{ number_format($stats['conversion_rate'], 1) }}%</div>
                    <div style="font-size:11px; color:var(--ink2); margin-top:2px;">Conversion Rate</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── ฟอร์มแก้ไขข้อมูล ──────────────────────────────────── --}}
    <div class="tp-card" style="padding:24px;">
        <div class="tp-section-h" style="margin-bottom:18px;"><i class="fas fa-edit" style="color:#c05a8f; margin-right:8px;"></i>แก้ไขข้อมูลส่วนตัว</div>

        <form action="{{ route('user.marketing.recruit.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:16px;">
                <div>
                    <label style="display:block; font-size:12.5px; font-weight:600; color:var(--ink); margin-bottom:6px;"><i class="fas fa-user" style="color:#c05a8f; margin-right:6px;"></i>ชื่อที่แสดง</label>
                    <input type="text" name="custom_name" value="{{ old('custom_name', $customization->custom_name) }}" class="tp-input" placeholder="ชื่อที่ต้องการให้แสดง (ถ้าไม่กรอกจะใช้ชื่อจากโปรไฟล์)">
                    @error('custom_name')<p style="margin-top:6px; font-size:12px; color:#d9534f;">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label style="display:block; font-size:12.5px; font-weight:600; color:var(--ink); margin-bottom:6px;"><i class="fas fa-phone" style="color:#5689b8; margin-right:6px;"></i>เบอร์โทรติดต่อ</label>
                    <input type="text" name="custom_phone" value="{{ old('custom_phone', $customization->custom_phone) }}" class="tp-input" placeholder="เบอร์โทรสำหรับให้ผู้สนใจติดต่อ">
                    @error('custom_phone')<p style="margin-top:6px; font-size:12px; color:#d9534f;">{{ $message }}</p>@enderror
                </div>
                <div style="grid-column:1/-1;">
                    <label style="display:block; font-size:12.5px; font-weight:600; color:var(--ink); margin-bottom:6px;"><i class="fas fa-map-marker-alt" style="color:#d9534f; margin-right:6px;"></i>ที่อยู่</label>
                    <textarea name="custom_address" rows="3" class="tp-input" style="resize:vertical;" placeholder="ที่อยู่ที่ต้องการแสดง">{{ old('custom_address', $customization->custom_address) }}</textarea>
                    @error('custom_address')<p style="margin-top:6px; font-size:12px; color:#d9534f;">{{ $message }}</p>@enderror
                </div>
                <div style="grid-column:1/-1;">
                    <label style="display:block; font-size:12.5px; font-weight:600; color:var(--ink); margin-bottom:6px;"><i class="fas fa-comment-dots" style="color:#5aa07e; margin-right:6px;"></i>คำชักชวนส่วนตัว</label>
                    <textarea name="custom_pitch" rows="5" class="tp-input" style="resize:vertical;" placeholder="เขียนคำชักชวนของคุณเอง (ถ้าไม่กรอกจะใช้ข้อความจากเทมเพลต)">{{ old('custom_pitch', $customization->custom_pitch) }}</textarea>
                    @error('custom_pitch')<p style="margin-top:6px; font-size:12px; color:#d9534f;">{{ $message }}</p>@enderror
                </div>
                <div style="grid-column:1/-1;">
                    <label style="display:block; font-size:12.5px; font-weight:600; color:var(--ink); margin-bottom:6px;"><i class="fas fa-image" style="color:#c05a8f; margin-right:6px;"></i>รูปภาพ</label>
                    @if($customization->custom_image)
                        <div style="margin-bottom:12px;">
                            <img src="{{ $customization->getDisplayImage() }}" alt="Current Image" style="width:128px; height:128px; border-radius:12px; object-fit:cover; box-shadow:var(--inset-sm);">
                        </div>
                    @endif
                    <input type="file" name="custom_image" accept="image/*" class="tp-input">
                    <p style="margin-top:6px; font-size:11px; color:var(--ink2);">รองรับไฟล์: JPG, PNG, GIF (สูงสุด 2MB)</p>
                    @error('custom_image')<p style="margin-top:6px; font-size:12px; color:#d9534f;">{{ $message }}</p>@enderror
                </div>
                <div style="grid-column:1/-1;">
                    <label style="display:inline-flex; align-items:center; cursor:pointer; gap:10px;">
                        <input type="checkbox" name="is_active" value="1" {{ $customization->is_active ? 'checked' : '' }} style="width:18px; height:18px;">
                        <span style="font-size:13px; font-weight:600; color:var(--ink);"><i class="fas fa-toggle-on" style="color:#5aa07e; margin-right:6px;"></i>เปิดใช้งานหน้า Recruit</span>
                    </label>
                </div>
            </div>

            <div style="margin-top:24px; display:flex; gap:12px; flex-wrap:wrap;">
                <button type="submit" class="tp-btn tp-btn-primary" style="background:#c05a8f; border-color:#c05a8f;"><i class="fas fa-save"></i> บันทึกการเปลี่ยนแปลง</button>
                <a href="{{ route('user.marketing.recruit.leads') }}" class="tp-btn" style="background:#5689b8; border-color:#5689b8; color:#fff;"><i class="fas fa-users"></i> ดูผู้มุ่งหวัง</a>
                <a href="{{ route('user.marketing.recruit.analytics') }}" class="tp-btn" style="background:#5aa07e; border-color:#5aa07e; color:#fff;"><i class="fas fa-chart-bar"></i> สถิติการตลาด</a>
            </div>
        </form>
    </div>

    {{-- ── QR + ลิงก์ ────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:24px;">
        <div class="tp-section-h" style="margin-bottom:18px;"><i class="fas fa-qrcode" style="color:#5689b8; margin-right:8px;"></i>QR Code และลิงก์แนะนำ</div>

        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:24px;">
            <div style="text-align:center;">
                <div style="display:inline-block; background:#fff; padding:24px; border-radius:16px; box-shadow:var(--raise); margin-bottom:16px;">
                    <div id="qrcode"></div>
                </div>
                <div>
                    <button @click="downloadQR()" class="tp-btn"><i class="fas fa-download"></i> ดาวน์โหลด QR Code</button>
                </div>
            </div>

            <div style="display:flex; flex-direction:column; gap:16px;">
                <div>
                    <label style="display:block; font-size:12.5px; font-weight:600; color:var(--ink); margin-bottom:6px;">ลิงก์หน้า Recruit:</label>
                    <div style="display:flex; gap:10px;">
                        <input type="text" id="recruitUrl" value="{{ $recruitUrl }}" readonly class="tp-input" style="flex:1; font-family:monospace; font-size:13px;">
                        <button @click="copyUrl('recruitUrl')" class="tp-btn" style="background:#5689b8; border-color:#5689b8; color:#fff;"><i class="fas fa-copy"></i></button>
                    </div>
                </div>
                <div>
                    <label style="display:block; font-size:12.5px; font-weight:600; color:var(--ink); margin-bottom:6px;">รหัสสมาชิก:</label>
                    <div style="display:flex; gap:10px;">
                        <input type="text" id="memberCode" value="{{ $mlmMember->member_code }}" readonly class="tp-input" style="flex:1; font-family:monospace; font-size:20px; font-weight:800; text-align:center;">
                        <button @click="copyUrl('memberCode')" class="tp-btn" style="background:#c05a8f; border-color:#c05a8f; color:#fff;"><i class="fas fa-copy"></i></button>
                    </div>
                </div>
                <div style="padding-top:8px;">
                    <p style="font-size:12.5px; font-weight:600; color:var(--ink); margin:0 0 10px;">แชร์ผ่าน:</p>
                    <div style="display:flex; gap:10px; flex-wrap:wrap;">
                        <button @click="shareVia('line')" class="tp-btn" style="flex:1; min-width:100px; justify-content:center; background:#5aa07e; border-color:#5aa07e; color:#fff;"><i class="fab fa-line"></i> LINE</button>
                        <button @click="shareVia('facebook')" class="tp-btn" style="flex:1; min-width:100px; justify-content:center; background:#3b5998; border-color:#3b5998; color:#fff;"><i class="fab fa-facebook"></i> Facebook</button>
                        <button @click="shareVia('twitter')" class="tp-btn" style="flex:1; min-width:100px; justify-content:center; background:#4a9fd4; border-color:#4a9fd4; color:#fff;"><i class="fab fa-twitter"></i> Twitter</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
function recruitPageManager() {
    return {
        recruitUrl: '{{ $recruitUrl }}',
        memberCode: '{{ $mlmMember->member_code }}',
        qrcode: null,

        init() {
            // Generate QR Code
            this.qrcode = new QRCode(document.getElementById("qrcode"), {
                text: this.recruitUrl,
                width: 200,
                height: 200,
                colorDark: "#000000",
                colorLight: "#ffffff",
            });
        },

        copyUrl(elementId) {
            const input = document.getElementById(elementId);
            input.select();
            navigator.clipboard.writeText(input.value).then(() => {
                alert('✅ คัดลอกแล้ว!');
            });
        },

        downloadQR() {
            const canvas = document.querySelector('#qrcode canvas');
            const url = canvas.toDataURL("image/png");
            const link = document.createElement('a');
            link.download = `recruit-qr-${this.memberCode}.png`;
            link.href = url;
            link.click();
        },

        shareVia(platform) {
            const message = `มาร่วมเป็นส่วนหนึ่งกับทีมของฉัน! 🚀`;

            switch(platform) {
                case 'line':
                    window.open(`https://line.me/R/msg/text/?${encodeURIComponent(message + '\n' + this.recruitUrl)}`, '_blank');
                    break;
                case 'facebook':
                    window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(this.recruitUrl)}`, '_blank');
                    break;
                case 'twitter':
                    window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(message)}&url=${encodeURIComponent(this.recruitUrl)}`, '_blank');
                    break;
            }
        }
    }
}
</script>
@endpush
@endsection
