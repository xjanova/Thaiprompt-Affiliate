@extends('layouts.admin')

@section('title', 'สร้างบทความใหม่')

@push('styles')
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<style>
:root {
    --primary: #4f46e5;
    --primary-dark: #4338ca;
}

.af-container {
    max-width: 1200px;
    margin: 0 auto;
}

.af-header {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    border-radius: 16px;
    padding: 32px;
    color: white;
    margin-bottom: 32px;
}

.af-form {
    background: white;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    overflow: hidden;
}

.af-section {
    padding: 32px;
    border-bottom: 1px solid #e5e7eb;
}

.af-section:last-child {
    border-bottom: none;
}

.af-section-title {
    font-size: 18px;
    font-weight: 700;
    color: #111827;
    margin-bottom: 20px;
}

.af-form-group {
    margin-bottom: 24px;
}

.af-label {
    display: block;
    font-size: 14px;
    font-weight: 500;
    color: #374151;
    margin-bottom: 8px;
}

.af-required {
    color: #ef4444;
}

.af-input,
.af-textarea,
.af-select {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.2s;
}

.af-input:focus,
.af-textarea:focus,
.af-select:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.af-editor {
    min-height: 400px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
}

.af-upload {
    border: 2px dashed #e5e7eb;
    border-radius: 12px;
    padding: 32px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
}

.af-upload:hover {
    border-color: var(--primary);
    background: #f9fafb;
}

.af-upload-icon {
    font-size: 48px;
    color: #9ca3af;
    margin-bottom: 12px;
}

.af-image-preview {
    max-width: 300px;
    border-radius: 12px;
    margin-top: 16px;
}

.af-checkbox-group {
    display: flex;
    align-items: center;
    gap: 12px;
}

.af-checkbox {
    width: 20px;
    height: 20px;
    cursor: pointer;
}

.af-btn {
    padding: 12px 24px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
}

.af-btn-primary {
    background: var(--primary);
    color: white;
}

.af-btn-primary:hover {
    background: var(--primary-dark);
}

.af-btn-outline {
    background: white;
    border: 1px solid #e5e7eb;
    color: #374151;
}

.af-prerequisite-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: #f9fafb;
    border-radius: 8px;
    margin-bottom: 8px;
}

.af-tag-input {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    padding: 8px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    min-height: 42px;
}

.af-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    background: var(--primary);
    color: white;
    border-radius: 6px;
    font-size: 13px;
}

.af-tag-remove {
    cursor: pointer;
    font-weight: bold;
}

.ql-editor {
    min-height: 400px;
    font-size: 16px;
    line-height: 1.6;
}
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="af-container">
        <!-- Header -->
        <div class="af-header">
            <h1 style="font-size: 28px; font-weight: 700; margin-bottom: 8px;">สร้างบทความใหม่</h1>
            <p style="opacity: 0.9; margin: 0;">สร้างเนื้อหาการเรียนรู้ที่มีคุณภาพ</p>
        </div>

        <!-- Form -->
        <form method="POST" action="{{ route('admin.articles.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="af-form">
                <!-- Basic Info -->
                <div class="af-section">
                    <h2 class="af-section-title">ข้อมูลพื้นฐาน</h2>

                    <div class="af-form-group">
                        <label class="af-label">
                            ชื่อบทความ <span class="af-required">*</span>
                        </label>
                        <input type="text" name="title" class="af-input"
                               placeholder="ใส่ชื่อบทความที่น่าสนใจ..."
                               value="{{ old('title') }}" required>
                    </div>

                    <div class="af-form-group">
                        <label class="af-label">Slug (URL)</label>
                        <input type="text" name="slug" class="af-input"
                               placeholder="auto-generated จากชื่อบทความ"
                               value="{{ old('slug') }}">
                        <small style="color: #6b7280; font-size: 13px;">
                            เว้นว่างไว้เพื่อสร้างอัตโนมัติ
                        </small>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="af-form-group">
                                <label class="af-label">
                                    หมวดหมู่ <span class="af-required">*</span>
                                </label>
                                <select name="category_id" class="af-select" required>
                                    <option value="">เลือกหมวดหมู่</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->icon }} {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="af-form-group">
                                <label class="af-label">
                                    ระดับความยาก <span class="af-required">*</span>
                                </label>
                                <select name="difficulty" class="af-select" required>
                                    <option value="beginner" {{ old('difficulty') == 'beginner' ? 'selected' : '' }}>เริ่มต้น</option>
                                    <option value="intermediate" {{ old('difficulty') == 'intermediate' ? 'selected' : '' }}>ปานกลาง</option>
                                    <option value="advanced" {{ old('difficulty') == 'advanced' ? 'selected' : '' }}>ขั้นสูง</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="af-form-group">
                        <label class="af-label">สรุปย่อ</label>
                        <textarea name="excerpt" class="af-textarea" rows="3"
                                  placeholder="สรุปสั้นๆ ของบทความ...">{{ old('excerpt') }}</textarea>
                    </div>
                </div>

                <!-- Content -->
                <div class="af-section">
                    <h2 class="af-section-title">เนื้อหา</h2>

                    <div class="af-form-group">
                        <label class="af-label">
                            เนื้อหาบทความ <span class="af-required">*</span>
                        </label>
                        <div id="editor" class="af-editor"></div>
                        <input type="hidden" name="content" id="content-input">
                    </div>
                </div>

                <!-- Media -->
                <div class="af-section">
                    <h2 class="af-section-title">สื่อและรูปภาพ</h2>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="af-form-group">
                                <label class="af-label">ภาพหน้าปก</label>
                                <div class="af-upload" onclick="document.getElementById('thumbnail').click()">
                                    <div class="af-upload-icon">🖼️</div>
                                    <div style="color: #6b7280;">คลิกเพื่ออัพโหลดภาพ</div>
                                    <small style="color: #9ca3af;">PNG, JPG สูงสุด 2MB</small>
                                </div>
                                <input type="file" id="thumbnail" name="thumbnail"
                                       accept="image/*" style="display: none;"
                                       onchange="previewImage(this)">
                                <img id="preview" class="af-image-preview" style="display: none;">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="af-form-group">
                                <label class="af-label">URL วิดีโอ (ถ้ามี)</label>
                                <input type="url" name="video_url" class="af-input"
                                       placeholder="https://youtube.com/watch?v=..."
                                       value="{{ old('video_url') }}">
                                <small style="color: #6b7280; font-size: 13px;">
                                    YouTube, Vimeo, หรือลิงก์วิดีโออื่นๆ
                                </small>
                            </div>

                            <div class="af-form-group">
                                <label class="af-label">ระยะเวลาโดยประมาณ (นาที)</label>
                                <input type="number" name="estimated_duration" class="af-input"
                                       placeholder="15" min="0"
                                       value="{{ old('estimated_duration', 10) }}">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Prerequisites -->
                <div class="af-section">
                    <h2 class="af-section-title">บทความที่ต้องเรียนก่อน</h2>
                    <p style="color: #6b7280; font-size: 14px; margin-bottom: 16px;">
                        เลือกบทความที่ผู้เรียนต้องทำให้เสร็จก่อนจึงจะเข้าถึงบทความนี้ได้
                    </p>

                    <div id="prerequisites-container">
                        @foreach($articles as $article)
                            <div class="af-prerequisite-item">
                                <input type="checkbox" name="prerequisites[]"
                                       value="{{ $article->id }}"
                                       id="prereq-{{ $article->id }}">
                                <label for="prereq-{{ $article->id }}" style="margin: 0; cursor: pointer;">
                                    {{ $article->title }}
                                    <small style="color: #6b7280;">
                                        ({{ $article->category->name }})
                                    </small>
                                </label>
                            </div>
                        @endforeach

                        @if($articles->count() == 0)
                            <div style="text-align: center; padding: 24px; color: #6b7280;">
                                ยังไม่มีบทความที่สามารถเลือกเป็น prerequisite ได้
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Settings -->
                <div class="af-section">
                    <h2 class="af-section-title">การตั้งค่า</h2>

                    <div class="af-form-group">
                        <label class="af-label">แท็ก</label>
                        <input type="text" name="tags" class="af-input"
                               placeholder="ใส่แท็ก คั่นด้วย comma เช่น AI, Machine Learning"
                               value="{{ old('tags') }}">
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="af-form-group">
                                <div class="af-checkbox-group">
                                    <input type="checkbox" name="is_published"
                                           value="1" id="is_published" class="af-checkbox"
                                           {{ old('is_published') ? 'checked' : '' }}>
                                    <label for="is_published" style="margin: 0; cursor: pointer;">
                                        เผยแพร่บทความทันที
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="af-form-group">
                                <div class="af-checkbox-group">
                                    <input type="checkbox" name="is_featured"
                                           value="1" id="is_featured" class="af-checkbox"
                                           {{ old('is_featured') ? 'checked' : '' }}>
                                    <label for="is_featured" style="margin: 0; cursor: pointer;">
                                        บทความแนะนำ (Featured)
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="af-form-group">
                        <label class="af-label">ลำดับการแสดง</label>
                        <input type="number" name="order" class="af-input"
                               placeholder="0" min="0"
                               value="{{ old('order', 0) }}">
                        <small style="color: #6b7280; font-size: 13px;">
                            ตัวเลขน้อยจะแสดงก่อน
                        </small>
                    </div>
                </div>

                <!-- Actions -->
                <div class="af-section" style="background: #f9fafb;">
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.articles.index') }}" class="af-btn af-btn-outline">
                            <i class="fas fa-times"></i> ยกเลิก
                        </a>
                        <button type="submit" class="af-btn af-btn-primary">
                            <i class="fas fa-save"></i> บันทึกบทความ
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script>
// Initialize Quill editor
var quill = new Quill('#editor', {
    theme: 'snow',
    modules: {
        toolbar: [
            [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
            ['bold', 'italic', 'underline', 'strike'],
            ['blockquote', 'code-block'],
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
            [{ 'indent': '-1'}, { 'indent': '+1' }],
            [{ 'color': [] }, { 'background': [] }],
            [{ 'align': [] }],
            ['link', 'image', 'video'],
            ['clean']
        ]
    },
    placeholder: 'เขียนเนื้อหาบทความที่น่าสนใจ...'
});

// Sync editor content with hidden input on submit
document.querySelector('form').addEventListener('submit', function() {
    document.getElementById('content-input').value = quill.root.innerHTML;
});

// Image preview
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var preview = document.getElementById('preview');
            preview.src = e.target.result;
            preview.style.display = 'block';
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// Auto-generate slug from title
document.querySelector('input[name="title"]').addEventListener('input', function(e) {
    var slug = e.target.value
        .toLowerCase()
        .replace(/[^\u0E00-\u0E7Fa-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-');
    document.querySelector('input[name="slug"]').value = slug;
});
</script>
@endpush
