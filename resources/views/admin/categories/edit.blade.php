@extends('layouts.admin-v3')

@section('title', 'แก้ไขหมวดหมู่ - ' . $category->name)

@push('styles')
<style>
:root { --primary: #4f46e5; --primary-dark: #4338ca; }
.ce-container { max-width: 1000px; margin: 0 auto; }
.ce-header { background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); border-radius: 16px; padding: 32px; color: white; margin-bottom: 32px; }
.ce-form { background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; }
.ce-section { padding: 32px; border-bottom: 1px solid #e5e7eb; }
.ce-section:last-child { border-bottom: none; }
.ce-section-title { font-size: 18px; font-weight: 700; color: #111827; margin-bottom: 20px; }
.ce-form-group { margin-bottom: 24px; }
.ce-label { display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 8px; }
.ce-required { color: #ef4444; }
.ce-input, .ce-textarea { width: 100%; padding: 12px 16px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; transition: all 0.2s; }
.ce-input:focus, .ce-textarea:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
.ce-help-text { font-size: 12px; color: #6b7280; margin-top: 4px; }
.ce-checkbox-wrapper { display: flex; align-items: center; gap: 12px; padding: 16px; background: #f9fafb; border-radius: 8px; }
.ce-checkbox { width: 20px; height: 20px; cursor: pointer; }
.ce-btn { padding: 12px 24px; border-radius: 8px; font-size: 14px; font-weight: 500; border: none; cursor: pointer; transition: all 0.2s; }
.ce-btn-primary { background: var(--primary); color: white; }
.ce-btn-primary:hover { background: var(--primary-dark); }
.ce-btn-outline { background: white; border: 1px solid #e5e7eb; color: #374151; }
.ce-btn-outline:hover { background: #f9fafb; }
.ce-stat-card { padding: 20px; background: #f3f4f6; border-radius: 10px; text-align: center; }
.ce-stat-label { font-size: 13px; color: #6b7280; margin-bottom: 8px; }
.ce-stat-value { font-size: 32px; font-weight: 700; color: #111827; }
.ce-icon-preview { width: 64px; height: 64px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 32px; border: 2px solid #e5e7eb; }
.ce-color-input { height: 48px; padding: 4px; }
.ce-alert { padding: 16px; border-radius: 8px; margin-bottom: 24px; }
.ce-alert-success { background: #d1fae5; border: 1px solid #10b981; color: #065f46; }
.ce-alert-error { background: #fee2e2; border: 1px solid #ef4444; color: #991b1b; }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="ce-container">
        <!-- Header -->
        <div class="ce-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 style="font-size: 28px; font-weight: 700; margin-bottom: 8px;">แก้ไขหมวดหมู่</h1>
                    <p style="opacity: 0.9; margin: 0;">{{ $category->name }}</p>
                </div>
                <a href="{{ route('admin.categories.index') }}" class="ce-btn" style="background: rgba(255,255,255,0.2); color: white;">
                    <i class="fas fa-arrow-left"></i> กลับ
                </a>
            </div>
        </div>

        <!-- Success/Error Messages -->
        @if(session('success'))
            <div class="ce-alert ce-alert-success">
                <i class="fas fa-check-circle"></i> {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="ce-alert ce-alert-error">
                <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="ce-alert ce-alert-error">
                <strong><i class="fas fa-exclamation-triangle"></i> มีข้อผิดพลาด:</strong>
                <ul style="margin: 8px 0 0 0; padding-left: 20px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Form -->
        <form method="POST" action="{{ route('admin.categories.update', $category) }}">
            @csrf
            @method('PUT')

            <div class="ce-form">
                <!-- Basic Information -->
                <div class="ce-section">
                    <h2 class="ce-section-title">ข้อมูลพื้นฐาน</h2>

                    <div class="ce-form-group">
                        <label class="ce-label">
                            ชื่อหมวดหมู่ <span class="ce-required">*</span>
                        </label>
                        <input type="text" name="name" class="ce-input"
                               value="{{ old('name', $category->name) }}" required
                               placeholder="เช่น AI Tools, Digital Marketing">
                        <div class="ce-help-text">ชื่อหมวดหมู่ที่จะแสดงให้ผู้ใช้เห็น</div>
                        @error('name')
                            <div style="color: #ef4444; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="ce-form-group">
                        <label class="ce-label">Slug (URL)</label>
                        <input type="text" name="slug" class="ce-input"
                               value="{{ old('slug', $category->slug) }}"
                               placeholder="เช่น ai-tools (ถ้าไม่ระบุจะสร้างอัตโนมัติ)">
                        <div class="ce-help-text">URL สำหรับหมวดหมู่นี้ ควรเป็นตัวอักษรภาษาอังกฤษและขีดกลาง</div>
                        @error('slug')
                            <div style="color: #ef4444; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="ce-form-group">
                        <label class="ce-label">คำอธิบาย</label>
                        <textarea name="description" class="ce-textarea" rows="4"
                                  placeholder="อธิบายเกี่ยวกับหมวดหมู่นี้">{{ old('description', $category->description) }}</textarea>
                        <div class="ce-help-text">คำอธิบายสั้นๆ เกี่ยวกับหมวดหมู่นี้</div>
                        @error('description')
                            <div style="color: #ef4444; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Appearance -->
                <div class="ce-section">
                    <h2 class="ce-section-title">รูปแบบและการแสดงผล</h2>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="ce-form-group">
                                <label class="ce-label">ไอคอน (Emoji)</label>
                                <div class="d-flex gap-3 align-items-center">
                                    <div class="ce-icon-preview" style="background: {{ old('color', $category->color) ?? '#f3f4f6' }}22;">
                                        <span id="icon-preview">{{ old('icon', $category->icon) ?? '📚' }}</span>
                                    </div>
                                    <div style="flex: 1;">
                                        <input type="text" name="icon" class="ce-input" id="icon-input"
                                               value="{{ old('icon', $category->icon) }}"
                                               placeholder="เช่น 📚, 🤖, 💡"
                                               maxlength="50"
                                               oninput="document.getElementById('icon-preview').textContent = this.value || '📚'">
                                        <div class="ce-help-text">ใส่ Emoji ที่ต้องการใช้เป็นไอคอน</div>
                                    </div>
                                </div>
                                @error('icon')
                                    <div style="color: #ef4444; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="ce-form-group">
                                <label class="ce-label">สีประจำหมวดหมู่</label>
                                <input type="color" name="color" class="ce-input ce-color-input" id="color-input"
                                       value="{{ old('color', $category->color) ?? '#4f46e5' }}"
                                       onchange="updateIconPreview()">
                                <div class="ce-help-text">เลือกสีที่จะใช้แสดงกับหมวดหมู่นี้</div>
                                @error('color')
                                    <div style="color: #ef4444; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="ce-form-group">
                        <label class="ce-label">ลำดับการแสดงผล</label>
                        <input type="number" name="order" class="ce-input"
                               value="{{ old('order', $category->order) ?? 0 }}"
                               min="0" step="1"
                               placeholder="0">
                        <div class="ce-help-text">ตัวเลขที่น้อยกว่าจะแสดงก่อน (0 = แรกสุด)</div>
                        @error('order')
                            <div style="color: #ef4444; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Settings -->
                <div class="ce-section">
                    <h2 class="ce-section-title">การตั้งค่า</h2>

                    <div class="ce-checkbox-wrapper">
                        <input type="checkbox" name="is_active" value="1" id="is_active" class="ce-checkbox"
                               {{ old('is_active', $category->is_active) ? 'checked' : '' }}>
                        <label for="is_active" style="margin: 0; cursor: pointer; font-weight: 500;">
                            <i class="fas fa-toggle-on" style="color: var(--primary);"></i> เปิดใช้งานหมวดหมู่นี้
                        </label>
                    </div>
                    <div class="ce-help-text" style="margin-left: 32px;">หมวดหมู่ที่ปิดใช้งานจะไม่แสดงในส่วนหน้าเว็บไซต์</div>
                </div>

                <!-- Statistics -->
                <div class="ce-section" style="background: #fafafa;">
                    <h2 class="ce-section-title">สถิติ</h2>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="ce-stat-card">
                                <div class="ce-stat-label">จำนวนบทความ</div>
                                <div class="ce-stat-value">{{ $category->articles_count ?? 0 }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="ce-stat-card">
                                <div class="ce-stat-label">สร้างเมื่อ</div>
                                <div style="font-size: 16px; font-weight: 600; color: #374151; margin-top: 8px;">
                                    {{ $category->created_at->format('d/m/Y') }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="ce-stat-card">
                                <div class="ce-stat-label">แก้ไขล่าสุด</div>
                                <div style="font-size: 16px; font-weight: 600; color: #374151; margin-top: 8px;">
                                    {{ $category->updated_at->format('d/m/Y') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="ce-section" style="background: #f9fafb;">
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="{{ route('admin.categories.index') }}" class="ce-btn ce-btn-outline">
                            <i class="fas fa-arrow-left"></i> กลับ
                        </a>
                        <div class="d-flex gap-2">
                            @if(($category->articles_count ?? 0) == 0)
                                <button type="button" class="ce-btn" style="background: #fee2e2; color: #991b1b;"
                                        onclick="if(confirm('ต้องการลบหมวดหมู่นี้หรือไม่?')) { document.getElementById('delete-form').submit(); }">
                                    <i class="fas fa-trash"></i> ลบหมวดหมู่
                                </button>
                            @endif
                            <button type="submit" class="ce-btn ce-btn-primary">
                                <i class="fas fa-save"></i> บันทึกการแก้ไข
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- Delete Form (Hidden) -->
        @if(($category->articles_count ?? 0) == 0)
            <form id="delete-form" method="POST" action="{{ route('admin.categories.destroy', $category) }}" style="display: none;">
                @csrf
                @method('DELETE')
            </form>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
function updateIconPreview() {
    const color = document.getElementById('color-input').value;
    const preview = document.querySelector('.ce-icon-preview');
    preview.style.background = color + '22';
}

// Update preview on icon input
document.getElementById('icon-input').addEventListener('input', function() {
    document.getElementById('icon-preview').textContent = this.value || '📚';
});
</script>
@endpush
