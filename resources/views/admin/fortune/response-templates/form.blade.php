@extends('layouts.admin-v4')

@section('title', $pageTitle)

@section('content')
{{-- หน้าฟอร์มเทมเพลตตอบกลับ (ใช้ร่วมทั้งสร้างและแก้ไข) ธีม V4 นวลทองคำ --}}
<div style="display:flex;flex-direction:column;gap:18px;" x-data="templateForm()">

    {{-- ส่วนหัวหน้า --}}
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;">
        <div>
            <div style="font-size:12px;letter-spacing:.08em;color:var(--ink2);text-transform:uppercase;margin-bottom:6px;">
                หลังบ้าน · ระบบดูดวง · เทมเพลตตอบกลับ
            </div>
            <h1 class="tp-num" style="font-size:26px;font-weight:800;color:var(--ink);margin:0;">
                {{ $pageTitle }}
            </h1>
        </div>
        <a href="{{ route('admin.fortune.response-templates.index') }}" class="tp-btn">
            <i class="fas fa-arrow-left"></i> กลับไปรายการ
        </a>
    </div>

    {{-- แจ้งเตือนข้อผิดพลาดจากการตรวจสอบ --}}
    @if($errors->any())
        <div class="tp-card" style="border-left:4px solid #d9534f;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;color:#d9534f;font-weight:700;">
                <i class="fas fa-circle-exclamation"></i> พบข้อผิดพลาด กรุณาตรวจสอบ
            </div>
            <ul style="margin:0;padding-left:20px;color:var(--ink2);font-size:14px;line-height:1.8;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
          action="{{ $template ? route('admin.fortune.response-templates.update', $template) : route('admin.fortune.response-templates.store') }}">
        @csrf
        @if($template)
            @method('PUT')
        @endif

        <div style="display:flex;flex-direction:column;gap:18px;">

            {{-- ข้อมูลพื้นฐาน --}}
            <div class="tp-card">
                <div class="tp-section-h" style="margin-bottom:16px;">
                    <i class="fas fa-circle-info" style="color:var(--accent1);"></i> ข้อมูลพื้นฐาน
                </div>

                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px;">
                    {{-- ชื่อเทมเพลต --}}
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:var(--ink2);margin-bottom:6px;">ชื่อเทมเพลต *</label>
                        <div class="tp-well tp-input" style="padding:0;">
                            <input type="text" name="name" value="{{ old('name', $template->name ?? '') }}"
                                   required placeholder="เช่น คำทำนายพื้นฐาน - มาตรฐาน"
                                   style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;">
                        </div>
                    </div>

                    {{-- Slug --}}
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:var(--ink2);margin-bottom:6px;">Slug (อัตโนมัติ)</label>
                        <div class="tp-well tp-input" style="padding:0;">
                            <input type="text" name="slug" value="{{ old('slug', $template->slug ?? '') }}"
                                   placeholder="auto-generated-from-name"
                                   style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;">
                        </div>
                    </div>

                    {{-- ประเภท --}}
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:var(--ink2);margin-bottom:6px;">ประเภท *</label>
                        <div class="tp-well tp-input" style="padding:0;">
                            <select name="type" required
                                    style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;cursor:pointer;">
                                <option value="basic" {{ old('type', $template->type ?? '') === 'basic' ? 'selected' : '' }}>🔮 คำทำนายพื้นฐาน</option>
                                <option value="deep" {{ old('type', $template->type ?? '') === 'deep' ? 'selected' : '' }}>🌟 คำทำนายเชิงลึก</option>
                                <option value="welcome" {{ old('type', $template->type ?? '') === 'welcome' ? 'selected' : '' }}>👋 ข้อความต้อนรับ</option>
                                <option value="payment" {{ old('type', $template->type ?? '') === 'payment' ? 'selected' : '' }}>💰 แจ้งชำระเงิน</option>
                                <option value="limit_exceeded" {{ old('type', $template->type ?? '') === 'limit_exceeded' ? 'selected' : '' }}>⏳ หมดโควต้าฟรี</option>
                                <option value="error" {{ old('type', $template->type ?? '') === 'error' ? 'selected' : '' }}>❌ ข้อผิดพลาด</option>
                            </select>
                        </div>
                    </div>

                    {{-- ลำดับ --}}
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:var(--ink2);margin-bottom:6px;">ลำดับ</label>
                        <div class="tp-well tp-input" style="padding:0;">
                            <input type="number" name="sort_order" value="{{ old('sort_order', $template->sort_order ?? 0) }}" min="0"
                                   style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;">
                        </div>
                    </div>
                </div>

                {{-- ตัวเลือกเปิดใช้งาน --}}
                <div style="display:flex;gap:24px;flex-wrap:wrap;margin-top:18px;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;color:var(--ink);font-size:14px;">
                        <input type="checkbox" name="is_active" value="1"
                               {{ old('is_active', $template->is_active ?? true) ? 'checked' : '' }}
                               style="width:18px;height:18px;accent-color:var(--accent1);cursor:pointer;">
                        เปิดใช้งาน
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;color:var(--ink);font-size:14px;">
                        <input type="checkbox" name="is_default" value="1"
                               {{ old('is_default', $template->is_default ?? false) ? 'checked' : '' }}
                               style="width:18px;height:18px;accent-color:var(--accent1);cursor:pointer;">
                        เป็นเทมเพลตเริ่มต้น
                    </label>
                </div>
            </div>

            {{-- เนื้อหาเทมเพลต --}}
            <div class="tp-card">
                <div class="tp-section-h" style="margin-bottom:16px;">
                    <i class="fas fa-pen-nib" style="color:var(--accent1);"></i> เนื้อหาเทมเพลต
                </div>

                {{-- Placeholders ที่ใช้ได้ --}}
                <div class="tp-inset" style="padding:14px 16px;border-radius:14px;margin-bottom:18px;">
                    <p style="font-size:13px;font-weight:600;color:var(--ink2);margin:0 0 10px;">
                        <i class="fas fa-tags" style="color:var(--accent1);"></i> Placeholders ที่ใช้ได้ (คลิกเพื่อเพิ่ม)
                    </p>
                    <div style="display:flex;flex-wrap:wrap;gap:8px;">
                        @foreach([
                            '{response}' => 'คำทำนายจาก AI',
                            '{user_name}' => 'ชื่อผู้ใช้',
                            '{date}' => 'วันที่ (dd/mm/yyyy)',
                            '{date_thai}' => 'วันที่ภาษาไทย',
                            '{questions}' => 'คำถาม',
                            '{reading_type}' => 'ประเภท',
                            '{reading_id}' => 'เลขที่',
                            '{rate_url}' => 'URL ให้คะแนน',
                            '{register_url}' => 'URL สมัครสมาชิก',
                            '{payment_url}' => 'URL ชำระเงิน',
                            '{remaining_free}' => 'ครั้งฟรีที่เหลือ',
                            '{max_free}' => 'ครั้งฟรีสูงสุด',
                            '{price}' => 'ราคา',
                            '{birth_date}' => 'วันเดือนปีเกิด',
                            '{zodiac}' => 'ราศี',
                        ] as $placeholder => $desc)
                            <button type="button"
                                    @click="insertPlaceholder('{{ $placeholder }}')"
                                    class="tp-pill tp-pill-soft"
                                    style="cursor:pointer;border:0;font-family:monospace;font-size:12px;"
                                    title="{{ $desc }}">
                                {{ $placeholder }}
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- ข้อความส่วนหัว --}}
                <div style="margin-bottom:16px;">
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--ink2);margin-bottom:6px;">ข้อความส่วนหัว (ก่อนคำทำนาย)</label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <input type="text" name="header_text" value="{{ old('header_text', $template->header_text ?? '') }}"
                               placeholder="เว้นว่างถ้าไม่ต้องการ"
                               style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;">
                    </div>
                </div>

                {{-- เนื้อหาหลัก --}}
                <div style="margin-bottom:16px;">
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--ink2);margin-bottom:6px;">เนื้อหาหลัก *</label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <textarea name="body" id="templateBody" rows="12" required
                                  placeholder="ใส่เนื้อหาเทมเพลต รองรับ placeholders เช่น {response}, {user_name}"
                                  style="width:100%;background:transparent;border:0;outline:0;padding:12px 14px;color:var(--ink);font-size:13px;font-family:monospace;line-height:1.6;resize:vertical;">{{ old('body', $template->body ?? '') }}</textarea>
                    </div>
                </div>

                {{-- ข้อความส่วนท้าย --}}
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--ink2);margin-bottom:6px;">ข้อความส่วนท้าย (หลังคำทำนาย)</label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <input type="text" name="footer_text" value="{{ old('footer_text', $template->footer_text ?? '') }}"
                               placeholder="เว้นว่างถ้าไม่ต้องการ"
                               style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;">
                    </div>
                </div>
            </div>

            {{-- รูปภาพ --}}
            <div class="tp-card">
                <div class="tp-section-h" style="margin-bottom:8px;">
                    <i class="fas fa-image" style="color:var(--accent1);"></i> รูปภาพ (ส่งผ่าน Messenger)
                </div>
                <p class="tp-muted" style="font-size:13px;margin:0 0 16px;">
                    ระบุ URL รูปภาพ (HTTPS) ที่จะส่งพร้อมข้อความ เช่น QR Code ชำระเงิน, รูปบัญชีธนาคาร
                </p>

                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px;">
                    {{-- รูปส่วนหัว --}}
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:var(--ink2);margin-bottom:6px;">
                            🖼️ รูปส่วนหัว (ส่งก่อนข้อความ)
                        </label>
                        <div class="tp-well tp-input" style="padding:0;">
                            <input type="url" name="header_image_url" value="{{ old('header_image_url', $template->header_image_url ?? '') }}"
                                   placeholder="https://example.com/header.jpg"
                                   x-model="headerImageUrl"
                                   style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;">
                        </div>
                        <template x-if="headerImageUrl">
                            <img :src="headerImageUrl" alt="ตัวอย่างรูปส่วนหัว"
                                 class="tp-inset-sm"
                                 style="margin-top:10px;border-radius:12px;max-height:128px;object-fit:contain;padding:6px;"
                                 x-on:error="$el.style.display='none'">
                        </template>
                    </div>

                    {{-- รูปส่วนท้าย (QR Code) --}}
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:var(--ink2);margin-bottom:6px;">
                            💰 รูปส่วนท้าย (เช่น QR Code / บัญชีธนาคาร)
                        </label>
                        <div class="tp-well tp-input" style="padding:0;">
                            <input type="url" name="footer_image_url" value="{{ old('footer_image_url', $template->footer_image_url ?? '') }}"
                                   placeholder="https://example.com/qr-code.jpg"
                                   x-model="footerImageUrl"
                                   style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;">
                        </div>
                        <template x-if="footerImageUrl">
                            <img :src="footerImageUrl" alt="ตัวอย่างรูปส่วนท้าย"
                                 class="tp-inset-sm"
                                 style="margin-top:10px;border-radius:12px;max-height:128px;object-fit:contain;padding:6px;"
                                 x-on:error="$el.style.display='none'">
                        </template>
                    </div>
                </div>
            </div>

            {{-- ดูตัวอย่าง --}}
            <div class="tp-card">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:14px;">
                    <div class="tp-section-h" style="margin:0;">
                        <i class="fas fa-eye" style="color:var(--accent1);"></i> ดูตัวอย่าง
                    </div>
                    <button type="button" @click="loadPreview()" class="tp-btn tp-btn-sm">
                        <i class="fas fa-rotate"></i> โหลดตัวอย่าง
                    </button>
                </div>
                <div x-show="previewText" class="tp-inset" style="padding:16px;border-radius:14px;">
                    <pre style="font-size:13px;color:var(--ink);white-space:pre-wrap;font-family:monospace;margin:0;line-height:1.7;" x-text="previewText"></pre>
                </div>
                <div x-show="!previewText" style="text-align:center;color:var(--ink2);padding:32px 0;">
                    <i class="fas fa-inbox" style="font-size:32px;opacity:.5;display:block;margin-bottom:8px;"></i>
                    กดปุ่ม "โหลดตัวอย่าง" เพื่อดูผลลัพธ์
                </div>
            </div>

            {{-- ปุ่มดำเนินการ --}}
            <div style="display:flex;gap:12px;flex-wrap:wrap;">
                <button type="submit" class="tp-btn tp-btn-primary">
                    <i class="fas fa-floppy-disk"></i> {{ $template ? 'บันทึกการแก้ไข' : 'สร้างเทมเพลต' }}
                </button>
                <a href="{{ route('admin.fortune.response-templates.index') }}" class="tp-btn">
                    ยกเลิก
                </a>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
// ฟังก์ชัน Alpine สำหรับฟอร์มเทมเพลต — แทรก placeholder, ตัวอย่างรูป, โหลดตัวอย่างผ่าน AJAX
function templateForm() {
    return {
        headerImageUrl: '{{ old('header_image_url', $template->header_image_url ?? '') }}',
        footerImageUrl: '{{ old('footer_image_url', $template->footer_image_url ?? '') }}',
        previewText: '',

        // แทรก placeholder ลงในตำแหน่ง cursor ของช่องเนื้อหาหลัก
        insertPlaceholder(placeholder) {
            const textarea = document.getElementById('templateBody');
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const text = textarea.value;
            textarea.value = text.substring(0, start) + placeholder + text.substring(end);
            textarea.selectionStart = textarea.selectionEnd = start + placeholder.length;
            textarea.focus();
        },

        // โหลดตัวอย่างผลลัพธ์จากเซิร์ฟเวอร์ (AJAX)
        async loadPreview() {
            try {
                const body = document.getElementById('templateBody').value;
                const headerText = document.querySelector('[name="header_text"]').value;
                const footerText = document.querySelector('[name="footer_text"]').value;

                const response = await fetch('{{ route("admin.fortune.response-templates.preview") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({ body, header_text: headerText, footer_text: footerText }),
                });

                const data = await response.json();
                if (data.success) {
                    this.previewText = data.preview;
                }
            } catch (e) {
                this.previewText = 'เกิดข้อผิดพลาดในการโหลดตัวอย่าง';
            }
        }
    }
}
</script>
@endpush
