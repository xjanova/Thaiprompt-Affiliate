@extends('layouts.admin-v4')

@section('title', 'Payment Banner — QR + ลายธนาคาร')

@section('content')
{{-- 🎨 หน้า Payment Banner — ธีม V4 "นวลทองคำ"
     ฝัง Dynamic QR ในภาพ banner ธนาคาร → ลด FB detection + filename hash ต่างทุกบิล
     คงฟอร์ม upload (multipart/file)/field/route/AJAX preview/Alpine paymentBannerEditor() เดิม 100%
     scripts stack: ฟังก์ชัน Alpine ย้ายไป scripts stack ตามมาตรฐาน V4 --}}
<div style="display:flex;flex-direction:column;gap:18px;" x-data="paymentBannerEditor()">

    {{-- ===== HEADER ===== --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
        <div>
            <div class="tp-muted" style="font-size:12px;letter-spacing:.08em;text-transform:uppercase;margin-bottom:6px;">
                หลังบ้าน · ระบบดูดวง · Payment Banner
            </div>
            <h1 class="tp-num" style="font-size:26px;margin:0;display:flex;align-items:center;gap:10px;">
                <i class="fas fa-image" style="color:var(--accent1);"></i> Payment Banner — QR + ลายธนาคาร
            </h1>
            <p class="tp-muted" style="margin:6px 0 0;font-size:13px;">
                ฝัง Dynamic QR ในภาพ banner ธนาคาร → ลด FB detection + filename hash ต่างทุกบิล
            </p>
        </div>
        @if(Route::has('admin.fortune.payment-banner.download'))
            <a href="{{ route('admin.fortune.payment-banner.download') }}" class="tp-btn">
                <i class="fas fa-download"></i> ดาวน์โหลด Template ปัจจุบัน
            </a>
        @endif
    </div>

    {{-- ===== Validation errors ===== --}}
    {{-- 🔴 layout admin-v4 แปลง session flash → toast อัตโนมัติ แต่ไม่ครอบ $errors (validation)
         จึงคงการแสดง error ของฟอร์ม update ไว้เป็น card V4 เพื่อไม่ให้ฟังก์ชันหาย --}}
    @if ($errors->any())
        <div class="tp-card" style="border-left:3px solid #d9534f;background:rgba(217,83,79,.06);">
            <div style="display:flex;align-items:center;gap:8px;color:#d9534f;font-weight:600;font-size:14px;margin-bottom:6px;">
                <i class="fas fa-triangle-exclamation"></i> ตรวจสอบข้อมูลอีกครั้ง
            </div>
            @foreach ($errors->all() as $error)
                <div style="font-size:13px;color:var(--ink);">• {{ $error }}</div>
            @endforeach
        </div>
    @endif

    {{-- ===== 2-COLUMN: ตั้งค่า + Preview ===== --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(360px,1fr));gap:18px;align-items:start;">

        {{-- ===================== LEFT: Settings Form ===================== --}}
        <div class="tp-card">
            <div class="tp-section-h" style="display:flex;align-items:center;gap:8px;margin-bottom:16px;">
                <i class="fas fa-sliders" style="color:var(--accent1);"></i>
                <span style="font-weight:700;font-size:15px;">ตั้งค่า Banner</span>
            </div>

            <form method="POST" action="{{ route('admin.fortune.payment-banner.update') }}" enctype="multipart/form-data">
                @csrf

                {{-- Toggle เปิดใช้ Banner Composite --}}
                <label style="display:flex;align-items:flex-start;gap:12px;cursor:pointer;">
                    <input type="checkbox" name="payment_banner_enabled" value="1"
                           style="width:18px;height:18px;margin-top:2px;accent-color:var(--accent1);cursor:pointer;"
                           {{ $settings->isPaymentBannerEnabled() ? 'checked' : '' }}>
                    <div>
                        <div style="font-weight:600;color:var(--ink);font-size:14px;">เปิดใช้ Banner Composite</div>
                        <div class="tp-muted" style="font-size:12px;margin-top:2px;">ปิด = ส่ง QR เปลือยเหมือนเดิม</div>
                    </div>
                </label>

                <div class="tp-divider" style="margin:18px 0;"></div>

                {{-- Banner upload --}}
                <div>
                    <label style="display:block;font-weight:600;color:var(--ink);font-size:14px;margin-bottom:6px;">
                        <i class="fas fa-upload" style="color:var(--accent2);"></i>
                        Upload Banner Template (PNG/JPG, สูงสุด 4MB)
                    </label>
                    <p class="tp-muted" style="font-size:12px;margin:0 0 12px;line-height:1.6;">
                        ⭐ แนะนำขนาด <strong style="color:var(--ink);">1200x1600px</strong> — ระบบจะวาด QR + ยอด + bill ทับลงไป
                        <br>ปล่อยว่าง = ใช้ default ที่ระบบ generate (ม่วงเข้ม + ดาว + Thai font)
                    </p>

                    <div class="tp-well tp-input" style="padding:0;">
                        <input type="file" name="payment_banner_image" accept="image/png,image/jpeg"
                               style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:13px;cursor:pointer;">
                    </div>
                </div>

                <div class="tp-divider" style="margin:18px 0;"></div>

                {{-- QR position + size --}}
                <div>
                    <label style="display:block;font-weight:600;color:var(--ink);font-size:14px;margin-bottom:10px;">
                        <i class="fas fa-up-down-left-right" style="color:var(--accent2);"></i>
                        ตำแหน่ง + ขนาด QR ใน Banner
                    </label>
                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;">
                        <div>
                            <label class="tp-muted" style="display:block;font-size:11px;margin-bottom:5px;">X (px)</label>
                            <div class="tp-well tp-input" style="padding:0;">
                                <input type="number" name="payment_banner_qr_x" min="0" max="2000"
                                       value="{{ $settings->payment_banner_qr_x ?? 100 }}"
                                       style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;">
                            </div>
                        </div>
                        <div>
                            <label class="tp-muted" style="display:block;font-size:11px;margin-bottom:5px;">Y (px)</label>
                            <div class="tp-well tp-input" style="padding:0;">
                                <input type="number" name="payment_banner_qr_y" min="0" max="2000"
                                       value="{{ $settings->payment_banner_qr_y ?? 150 }}"
                                       style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;">
                            </div>
                        </div>
                        <div>
                            <label class="tp-muted" style="display:block;font-size:11px;margin-bottom:5px;">Size (px)</label>
                            <div class="tp-well tp-input" style="padding:0;">
                                <input type="number" name="payment_banner_qr_size" min="100" max="1000"
                                       value="{{ $settings->payment_banner_qr_size ?? 400 }}"
                                       style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;">
                            </div>
                        </div>
                    </div>
                    <p class="tp-muted" style="font-size:12px;margin:10px 0 0;">
                        💡 X=0, Y=0 = ระบบคำนวณกลางอัตโนมัติ
                    </p>
                </div>

                <div class="tp-divider" style="margin:18px 0;"></div>

                {{-- ปุ่มบันทึก --}}
                <button type="submit" class="tp-btn tp-btn-primary" style="width:100%;justify-content:center;">
                    <i class="fas fa-floppy-disk"></i> บันทึก
                </button>
            </form>

            {{-- Reset form (แยกฟอร์มกัน validation conflict) --}}
            <form method="POST" action="{{ route('admin.fortune.payment-banner.reset') }}"
                  onsubmit="return confirm('รีเซ็ตเป็น banner default ของระบบใช่ไหม?');"
                  style="margin-top:12px;">
                @csrf
                <button type="submit" class="tp-btn" style="width:100%;justify-content:center;">
                    <i class="fas fa-rotate-left"></i> รีเซ็ตเป็น Banner Default ของระบบ
                </button>
            </form>
        </div>

        {{-- ===================== RIGHT: Preview ===================== --}}
        <div class="tp-card">
            <div class="tp-section-h" style="display:flex;align-items:center;gap:8px;margin-bottom:16px;">
                <i class="fas fa-eye" style="color:var(--accent1);"></i>
                <span style="font-weight:700;font-size:15px;">Preview</span>
            </div>

            {{-- Current Template --}}
            <div style="margin-bottom:18px;">
                <div style="font-weight:600;color:var(--ink);font-size:13px;margin-bottom:8px;">
                    <i class="fas fa-panorama" style="color:var(--accent2);"></i> Template ปัจจุบัน
                </div>
                @if ($customBannerUrl)
                    <div class="tp-pill tp-pill-gold" style="font-size:11px;margin-bottom:8px;">
                        <i class="fas fa-thumbtack"></i> Admin Upload
                    </div>
                    <img src="{{ $customBannerUrl }}" alt="Custom Banner"
                         style="width:100%;border-radius:14px;box-shadow:var(--raise);">
                @elseif ($defaultBannerUrl)
                    <div class="tp-pill tp-pill-soft" style="font-size:11px;margin-bottom:8px;">
                        <i class="fas fa-thumbtack"></i> Default (ระบบ generate)
                    </div>
                    <img src="{{ $defaultBannerUrl }}" alt="Default Banner"
                         style="width:100%;border-radius:14px;box-shadow:var(--raise);">
                @else
                    <div class="tp-card tp-inset-sm" style="text-align:center;padding:24px;color:#d9534f;font-size:13px;">
                        <i class="fas fa-triangle-exclamation"></i>
                        ยังไม่มี template — ลองกด "บันทึก" หรือ "ทดสอบ Generate"
                    </div>
                @endif
            </div>

            <div class="tp-divider" style="margin:16px 0;"></div>

            {{-- Test Preview --}}
            <div>
                <div style="font-weight:600;color:var(--ink);font-size:13px;margin-bottom:12px;">
                    <i class="fas fa-flask" style="color:var(--accent2);"></i> ทดสอบ Composite (banner + QR + ยอด)
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                    <div>
                        <label class="tp-muted" style="display:block;font-size:11px;margin-bottom:5px;">PromptPay ID</label>
                        <div class="tp-well tp-input" style="padding:0;">
                            <input type="text" x-model="testPromptpay" placeholder="0066885782958"
                                   style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:13px;">
                        </div>
                    </div>
                    <div>
                        <label class="tp-muted" style="display:block;font-size:11px;margin-bottom:5px;">ยอด (บาท)</label>
                        <div class="tp-well tp-input" style="padding:0;">
                            <input type="number" step="0.01" x-model="testAmount" placeholder="39.07"
                                   style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:13px;">
                        </div>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
                    <div>
                        <label class="tp-muted" style="display:block;font-size:11px;margin-bottom:5px;">ธนาคาร</label>
                        <div class="tp-well tp-input" style="padding:0;">
                            <input type="text" x-model="testBank" placeholder="กสิกรไทย"
                                   style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:13px;">
                        </div>
                    </div>
                    <div>
                        <label class="tp-muted" style="display:block;font-size:11px;margin-bottom:5px;">เลขบัญชี</label>
                        <div class="tp-well tp-input" style="padding:0;">
                            <input type="text" x-model="testAccount" placeholder="066-8-85782958"
                                   style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:13px;">
                        </div>
                    </div>
                </div>

                <button @click="generatePreview()" :disabled="loading"
                        class="tp-btn tp-btn-primary" style="width:100%;justify-content:center;"
                        :style="loading ? 'opacity:.5;cursor:not-allowed;' : ''">
                    <span x-show="!loading"><i class="fas fa-flask"></i> ทดสอบ Generate</span>
                    <span x-show="loading"><i class="fas fa-spinner fa-spin"></i> กำลังสร้าง...</span>
                </button>

                {{-- Result --}}
                <div x-show="previewUrl" x-cloak style="margin-top:16px;">
                    <div style="font-weight:600;color:#5aa07e;font-size:12px;margin-bottom:8px;">
                        <i class="fas fa-circle-check"></i> ผลลัพธ์ที่ลูกค้าจะได้รับ
                    </div>
                    <img :src="previewUrl" alt="Preview"
                         style="width:100%;border-radius:14px;box-shadow:var(--raise);border:2px solid #5aa07e;">
                    <div style="margin-top:10px;font-size:12px;">
                        <a :href="previewUrl" target="_blank"
                           style="color:var(--accent2);text-decoration:none;font-weight:600;">
                            <i class="fas fa-up-right-from-square"></i> เปิดในแท็บใหม่
                        </a>
                    </div>
                </div>

                <div x-show="error" x-cloak class="tp-card tp-inset-sm"
                     style="margin-top:16px;border-left:3px solid #d9534f;background:rgba(217,83,79,.06);color:#d9534f;font-size:13px;padding:12px 14px;">
                    <i class="fas fa-triangle-exclamation"></i> <span x-text="error"></span>
                </div>
            </div>

            <div class="tp-divider" style="margin:16px 0;"></div>

            {{-- Info --}}
            <div class="tp-card tp-inset-sm" style="font-size:12px;color:var(--ink2);line-height:1.7;padding:14px 16px;">
                <div style="margin-bottom:4px;">📌 <strong style="color:var(--ink);">Anti-FB-detection:</strong> ฝัง QR ใน design ดูเหมือน promotional graphic → ลด suspicion</div>
                <div style="margin-bottom:4px;">📌 <strong style="color:var(--ink);">Dynamic QR ทุกครั้ง:</strong> filename hash ของ bill+amount+time → ภาพไม่ซ้ำ → FB perceptual hash ไม่ match</div>
                <div>📌 <strong style="color:var(--ink);">SMS Checker:</strong> ยอด+ทศนิยมยังคงเดิม → จับคู่บิลปกติ</div>
            </div>
        </div>
    </div>
</div>
@endsection

{{-- scripts stack: Alpine component paymentBannerEditor() — คง AJAX endpoint/payload/logic เดิม 100% --}}
@push('scripts')
<script>
function paymentBannerEditor() {
    return {
        testPromptpay: '{{ $settings->promptpay_id ?? "0066885782958" }}',
        testAmount: 39.07,
        testBank: 'กสิกรไทย',
        testAccount: '066-8-85782958',
        loading: false,
        previewUrl: null,
        error: null,

        async generatePreview() {
            this.loading = true;
            this.error = null;
            this.previewUrl = null;

            try {
                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('test_promptpay', this.testPromptpay);
                formData.append('test_amount', this.testAmount);
                formData.append('test_bank', this.testBank);
                formData.append('test_account', this.testAccount);

                const res = await fetch('{{ route("admin.fortune.payment-banner.preview") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const data = await res.json();

                if (data.success) {
                    // Cache-bust
                    this.previewUrl = data.preview_url + '?t=' + Date.now();
                } else {
                    this.error = data.error || 'สร้าง preview ไม่สำเร็จ';
                }
            } catch (e) {
                this.error = 'เครือข่ายขัดข้อง: ' + e.message;
            } finally {
                this.loading = false;
            }
        },
    };
}
</script>
@endpush
