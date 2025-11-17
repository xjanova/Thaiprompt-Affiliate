@extends('layouts.admin-v3')

@section('title', 'สร้างหมวดหมู่ใหม่')

@push('styles')
<style>
:root { --primary: #4f46e5; --primary-dark: #4338ca; }
.cc-container { max-width: 1000px; margin: 0 auto; }
.cc-header { background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); border-radius: 16px; padding: 32px; color: white; margin-bottom: 32px; }
.cc-form { background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; }
.cc-section { padding: 32px; border-bottom: 1px solid #e5e7eb; }
.cc-section:last-child { border-bottom: none; }
.cc-section-title { font-size: 18px; font-weight: 700; color: #111827; margin-bottom: 20px; }
.cc-form-group { margin-bottom: 24px; }
.cc-label { display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 8px; }
.cc-required { color: #ef4444; }
.cc-input, .cc-textarea { width: 100%; padding: 12px 16px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; transition: all 0.2s; }
.cc-input:focus, .cc-textarea:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
.cc-help-text { font-size: 12px; color: #6b7280; margin-top: 4px; }
.cc-checkbox-wrapper { display: flex; align-items: center; gap: 12px; padding: 16px; background: #f9fafb; border-radius: 8px; }
.cc-checkbox { width: 20px; height: 20px; cursor: pointer; }
.cc-btn { padding: 12px 24px; border-radius: 8px; font-size: 14px; font-weight: 500; border: none; cursor: pointer; transition: all 0.2s; }
.cc-btn-primary { background: var(--primary); color: white; }
.cc-btn-primary:hover { background: var(--primary-dark); }
.cc-btn-outline { background: white; border: 1px solid #e5e7eb; color: #374151; }
.cc-btn-outline:hover { background: #f9fafb; }
.cc-icon-preview { width: 64px; height: 64px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 32px; border: 2px solid #e5e7eb; }
.cc-color-input { height: 48px; padding: 4px; }
.cc-alert { padding: 16px; border-radius: 8px; margin-bottom: 24px; }
.cc-alert-error { background: #fee2e2; border: 1px solid #ef4444; color: #991b1b; }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="cc-container">
        <!-- Header -->
        <div class="cc-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 style="font-size: 28px; font-weight: 700; margin-bottom: 8px;">สร้างหมวดหมู่ใหม่</h1>
                    <p style="opacity: 0.9; margin: 0;">เพิ่มหมวดหมู่ใหม่สำหรับจัดระเบียบเนื้อหา</p>
                </div>
                <a href="{{ route('admin.categories.index') }}" class="cc-btn" style="background: rgba(255,255,255,0.2); color: white;">
                    <i class="fas fa-arrow-left"></i> กลับ
                </a>
            </div>
        </div>

        <!-- Error Messages -->
        @if($errors->any())
            <div class="cc-alert cc-alert-error">
                <strong><i class="fas fa-exclamation-triangle"></i> มีข้อผิดพลาด:</strong>
                <ul style="margin: 8px 0 0 0; padding-left: 20px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Form -->
        <form method="POST" action="{{ route('admin.categories.store') }}">
            @csrf

            <div class="cc-form">
                <!-- Basic Information -->
                <div class="cc-section">
                    <h2 class="cc-section-title">ข้อมูลพื้นฐาน</h2>

                    <div class="cc-form-group">
                        <label class="cc-label">
                            ชื่อหมวดหมู่ <span class="cc-required">*</span>
                        </label>
                        <input type="text" name="name" class="cc-input"
                               value="{{ old('name') }}" required
                               placeholder="เช่น AI Tools, Digital Marketing">
                        <div class="cc-help-text">ชื่อหมวดหมู่ที่จะแสดงให้ผู้ใช้เห็น</div>
                        @error('name')
                            <div style="color: #ef4444; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="cc-form-group">
                        <label class="cc-label">Slug (URL)</label>
                        <input type="text" name="slug" class="cc-input"
                               value="{{ old('slug') }}"
                               placeholder="เช่น ai-tools (ถ้าไม่ระบุจะสร้างอัตโนมัติ)">
                        <div class="cc-help-text">URL สำหรับหมวดหมู่นี้ ควรเป็นตัวอักษรภาษาอังกฤษและขีดกลาง</div>
                        @error('slug')
                            <div style="color: #ef4444; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="cc-form-group">
                        <label class="cc-label">คำอธิบาย</label>
                        <textarea name="description" class="cc-textarea" rows="4"
                                  placeholder="อธิบายเกี่ยวกับหมวดหมู่นี้">{{ old('description') }}</textarea>
                        <div class="cc-help-text">คำอธิบายสั้นๆ เกี่ยวกับหมวดหมู่นี้</div>
                        @error('description')
                            <div style="color: #ef4444; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Appearance -->
                <div class="cc-section">
                    <h2 class="cc-section-title">รูปแบบและการแสดงผล</h2>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="cc-form-group">
                                <label class="cc-label">ไอคอน (Emoji)</label>
                                <div class="d-flex gap-3 align-items-center">
                                    <div class="cc-icon-preview" style="background: {{ old('color', '#4f46e5') }}22;">
                                        <span id="icon-preview">{{ old('icon', '📚') }}</span>
                                    </div>
                                    <div style="flex: 1;">
                                        <input type="text" name="icon" class="cc-input" id="icon-input"
                                               value="{{ old('icon') }}"
                                               placeholder="เช่น 📚, 🤖, 💡"
                                               maxlength="50"
                                               oninput="document.getElementById('icon-preview').textContent = this.value || '📚'">
                                        <div class="cc-help-text">ใส่ Emoji ที่ต้องการใช้เป็นไอคอน</div>
                                    </div>
                                </div>
                                @error('icon')
                                    <div style="color: #ef4444; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="cc-form-group">
                                <label class="cc-label">สีประจำหมวดหมู่</label>
                                <input type="color" name="color" class="cc-input cc-color-input" id="color-input"
                                       value="{{ old('color', '#4f46e5') }}"
                                       onchange="updateIconPreview()">
                                <div class="cc-help-text">เลือกสีที่จะใช้แสดงกับหมวดหมู่นี้</div>
                                @error('color')
                                    <div style="color: #ef4444; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="cc-form-group">
                        <label class="cc-label">ลำดับการแสดงผล</label>
                        <input type="number" name="order" class="cc-input"
                               value="{{ old('order', 0) }}"
                               min="0" step="1"
                               placeholder="0">
                        <div class="cc-help-text">ตัวเลขที่น้อยกว่าจะแสดงก่อน (0 = แรกสุด)</div>
                        @error('order')
                            <div style="color: #ef4444; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Settings -->
                <div class="cc-section">
                    <h2 class="cc-section-title">การตั้งค่า</h2>

                    <div class="cc-checkbox-wrapper">
                        <input type="checkbox" name="is_active" value="1" id="is_active" class="cc-checkbox"
                               {{ old('is_active', true) ? 'checked' : '' }}>
                        <label for="is_active" style="margin: 0; cursor: pointer; font-weight: 500;">
                            <i class="fas fa-toggle-on" style="color: var(--primary);"></i> เปิดใช้งานหมวดหมู่นี้
                        </label>
                    </div>
                    <div class="cc-help-text" style="margin-left: 32px;">หมวดหมู่ที่ปิดใช้งานจะไม่แสดงในส่วนหน้าเว็บไซต์</div>
                </div>

                <!-- Actions -->
                <div class="cc-section" style="background: #f9fafb;">
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="{{ route('admin.categories.index') }}" class="cc-btn cc-btn-outline">
                            <i class="fas fa-arrow-left"></i> กลับ
                        </a>
                        <button type="submit" class="cc-btn cc-btn-primary">
                            <i class="fas fa-plus"></i> สร้างหมวดหมู่
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function updateIconPreview() {
    const color = document.getElementById('color-input').value;
    const preview = document.querySelector('.cc-icon-preview');
    preview.style.background = color + '22';
}

// Update preview on icon input
document.getElementById('icon-input').addEventListener('input', function() {
    document.getElementById('icon-preview').textContent = this.value || '📚';
});
</script>
@endpush
