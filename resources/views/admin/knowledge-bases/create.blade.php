@extends('layouts.admin')

@section('title', 'เพิ่ม Knowledge Base - ' . $bot->name)

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">📚 เพิ่ม Knowledge Base</h4>
            <p class="text-gray-600 mb-0">สำหรับ Bot: <strong>{{ $bot->name }}</strong></p>
        </div>
        <a href="{{ route('admin.knowledge-bases.index', $bot->id) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> กลับ
        </a>
    </div>

    <!-- Form Card -->
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">ข้อมูล Knowledge Base</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.knowledge-bases.store', $bot->id) }}"
                          method="POST"
                          enctype="multipart/form-data"
                          id="knowledgeBaseForm">
                        @csrf

                        <!-- Name -->
                        <div class="mb-3">
                            <label class="form-label">ชื่อ Knowledge Base <span class="text-danger">*</span></label>
                            <input type="text"
                                   name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}"
                                   placeholder="เช่น: คู่มือการใช้งานผลิตภัณฑ์"
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div class="mb-3">
                            <label class="form-label">คำอธิบาย</label>
                            <textarea name="description"
                                      class="form-control @error('description') is-invalid @enderror"
                                      rows="2"
                                      placeholder="คำอธิบายสั้นๆ เกี่ยวกับเนื้อหาในเอกสาร">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Type Selector -->
                        <div class="mb-4">
                            <label class="form-label">ประเภทเอกสาร <span class="text-danger">*</span></label>
                            <div class="row g-2">
                                <div class="col-md-3">
                                    <div class="card type-selector" onclick="selectType('text')">
                                        <div class="card-body text-center py-3">
                                            <div style="font-size: 2rem;">📝</div>
                                            <div class="fw-bold mt-2">Text</div>
                                            <small class="text-muted">พิมพ์ข้อความ</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card type-selector" onclick="selectType('pdf')">
                                        <div class="card-body text-center py-3">
                                            <div style="font-size: 2rem;">📄</div>
                                            <div class="fw-bold mt-2">PDF</div>
                                            <small class="text-muted">อัพโหลด PDF</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card type-selector" onclick="selectType('docx')">
                                        <div class="card-body text-center py-3">
                                            <div style="font-size: 2rem;">📘</div>
                                            <div class="fw-bold mt-2">DOCX</div>
                                            <small class="text-muted">อัพโหลด Word</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card type-selector" onclick="selectType('url')">
                                        <div class="card-body text-center py-3">
                                            <div style="font-size: 2rem;">🔗</div>
                                            <div class="fw-bold mt-2">URL</div>
                                            <small class="text-muted">จาก Website</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="type" id="typeInput" value="{{ old('type', 'text') }}">
                            @error('type')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Text Input (for type=text) -->
                        <div class="mb-3 content-field" id="textField">
                            <label class="form-label">เนื้อหา <span class="text-danger">*</span></label>
                            <textarea name="content"
                                      class="form-control @error('content') is-invalid @enderror"
                                      rows="10"
                                      placeholder="พิมพ์หรือวางเนื้อหาที่นี่...">{{ old('content') }}</textarea>
                            <small class="text-muted">แนะนำ: ใช้ข้อความที่มีความยาวอย่างน้อย 100 ตัวอักษร</small>
                            @error('content')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- URL Input (for type=url) -->
                        <div class="mb-3 content-field" id="urlField" style="display: none;">
                            <label class="form-label">URL <span class="text-danger">*</span></label>
                            <input type="url"
                                   name="url"
                                   class="form-control @error('url') is-invalid @enderror"
                                   value="{{ old('url') }}"
                                   placeholder="https://example.com/article">
                            <small class="text-muted">ระบบจะดึงเนื้อหาจาก URL โดยอัตโนมัติ</small>
                            @error('url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- File Upload (for type=pdf, docx, file) -->
                        <div class="mb-3 content-field" id="fileField" style="display: none;">
                            <label class="form-label">อัพโหลดไฟล์ <span class="text-danger">*</span></label>
                            <input type="file"
                                   name="file"
                                   class="form-control @error('file') is-invalid @enderror"
                                   accept=".pdf,.docx,.doc,.txt,.md"
                                   onchange="showFileName(this)">
                            <small class="text-muted">รองรับ: PDF, DOCX, DOC, TXT, MD (สูงสุด 10MB)</small>
                            @error('file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div id="fileNameDisplay" class="mt-2"></div>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('admin.knowledge-bases.index', $bot->id) }}" class="btn btn-secondary">
                                ยกเลิก
                            </a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-check"></i> สร้าง Knowledge Base
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Info Card -->
            <div class="card mt-3 bg-light border-0">
                <div class="card-body">
                    <h6 class="mb-2"><i class="fas fa-info-circle"></i> ข้อมูลเพิ่มเติม</h6>
                    <ul class="mb-0 small">
                        <li>ระบบจะแบ่งเอกสารเป็น chunks อัตโนมัติเพื่อการค้นหาที่มีประสิทธิภาพ</li>
                        <li>Embeddings จะถูกสร้างด้วย OpenAI หรือ Local AI (ถ้าเปิดใช้งาน)</li>
                        <li>เวลาประมวลผลขึ้นอยู่กับขนาดเอกสาร (โดยปกติ 1-5 นาที)</li>
                        <li>Knowledge Base จะพร้อมใช้งานเมื่อสถานะเป็น "เสร็จสมบูรณ์"</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const initialType = document.getElementById('typeInput').value || 'text';
    selectType(initialType, false);
});

function selectType(type, updateInput = true) {
    // Update hidden input
    if (updateInput) {
        document.getElementById('typeInput').value = type;
    }

    // Update card selection
    document.querySelectorAll('.type-selector').forEach(card => {
        card.classList.remove('border-primary', 'bg-primary', 'text-white');
        card.style.cursor = 'pointer';
    });

    const selectedCard = event?.currentTarget || document.querySelector(`.type-selector:nth-child(${getTypeIndex(type)})`);
    if (selectedCard) {
        selectedCard.classList.add('border-primary', 'bg-light');
    }

    // Hide all content fields
    document.querySelectorAll('.content-field').forEach(field => {
        field.style.display = 'none';
    });

    // Show relevant field
    if (type === 'text') {
        document.getElementById('textField').style.display = 'block';
    } else if (type === 'url') {
        document.getElementById('urlField').style.display = 'block';
    } else {
        document.getElementById('fileField').style.display = 'block';
    }
}

function getTypeIndex(type) {
    const types = { 'text': 1, 'pdf': 2, 'docx': 3, 'url': 4 };
    return types[type] || 1;
}

function showFileName(input) {
    const display = document.getElementById('fileNameDisplay');
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const size = (file.size / 1024 / 1024).toFixed(2);
        display.innerHTML = `<div class="alert alert-info mb-0">
            <i class="fas fa-file"></i> <strong>${file.name}</strong> (${size} MB)
        </div>`;
    }
}

// Form submission
document.getElementById('knowledgeBaseForm').addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังประมวลผล...';
});
</script>

<style>
.type-selector {
    cursor: pointer;
    transition: all 0.2s;
    border: 2px solid transparent;
}

.type-selector:hover {
    border-color: #3B82F6;
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.type-selector.border-primary {
    border-color: #3B82F6 !important;
}
</style>
@endpush
@endsection
