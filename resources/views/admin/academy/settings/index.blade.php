@extends('layouts.admin')

@section('title', 'ตั้งค่า Academy System')

@push('styles')
<style>
:root {
    --primary: #6366f1;
    --primary-dark: #4f46e5;
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
    --gray-50: #f9fafb;
    --gray-100: #f3f4f6;
    --gray-200: #e5e7eb;
    --gray-600: #4b5563;
    --gray-700: #374151;
    --gray-900: #111827;
}

body {
    background: var(--gray-50);
    font-family: 'Inter', -apple-system, sans-serif;
}

.settings-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 32px 20px;
}

.settings-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 16px;
    padding: 32px;
    color: white;
    margin-bottom: 32px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.settings-title {
    font-size: 32px;
    font-weight: 800;
    margin: 0 0 8px 0;
}

.settings-subtitle {
    font-size: 16px;
    opacity: 0.95;
    margin: 0;
}

/* Tabs */
.tabs-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    margin-bottom: 24px;
    overflow: hidden;
}

.tabs-nav {
    display: flex;
    border-bottom: 2px solid var(--gray-100);
    overflow-x: auto;
    scrollbar-width: thin;
}

.tab-button {
    padding: 16px 24px;
    border: none;
    background: none;
    color: var(--gray-600);
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    border-bottom: 3px solid transparent;
    transition: all 0.2s;
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: 8px;
}

.tab-button:hover {
    color: var(--primary);
    background: var(--gray-50);
}

.tab-button.active {
    color: var(--primary);
    border-bottom-color: var(--primary);
}

.tab-content {
    padding: 32px;
}

.tab-pane {
    display: none;
}

.tab-pane.active {
    display: block;
}

/* Form Sections */
.form-section {
    margin-bottom: 32px;
}

.section-title {
    font-size: 18px;
    font-weight: 700;
    color: var(--gray-900);
    margin: 0 0 16px 0;
    padding-bottom: 12px;
    border-bottom: 2px solid var(--gray-100);
}

.form-group {
    margin-bottom: 20px;
}

.form-label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: var(--gray-700);
    margin-bottom: 8px;
}

.form-label .required {
    color: var(--danger);
}

.form-input,
.form-textarea,
.form-select {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid var(--gray-200);
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.2s;
}

.form-input:focus,
.form-textarea:focus,
.form-select:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.form-textarea {
    resize: vertical;
    min-height: 100px;
}

.form-help {
    font-size: 12px;
    color: var(--gray-600);
    margin-top: 4px;
}

/* Color Picker */
.color-input-group {
    display: flex;
    gap: 12px;
    align-items: center;
}

.color-preview {
    width: 48px;
    height: 48px;
    border-radius: 8px;
    border: 2px solid var(--gray-200);
}

/* File Upload */
.upload-area {
    border: 2px dashed var(--gray-200);
    border-radius: 12px;
    padding: 32px;
    text-align: center;
    transition: all 0.2s;
    cursor: pointer;
}

.upload-area:hover {
    border-color: var(--primary);
    background: var(--gray-50);
}

.upload-icon {
    font-size: 48px;
    margin-bottom: 12px;
}

.upload-text {
    font-size: 14px;
    color: var(--gray-600);
    margin-bottom: 8px;
}

.upload-hint {
    font-size: 12px;
    color: var(--gray-600);
}

.file-input {
    display: none;
}

/* Image Preview */
.image-preview {
    margin-top: 16px;
}

.preview-img {
    max-width: 200px;
    max-height: 200px;
    border-radius: 8px;
    border: 1px solid var(--gray-200);
}

/* Signature List */
.signature-list {
    display: grid;
    gap: 16px;
}

.signature-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px;
    background: var(--gray-50);
    border-radius: 8px;
    border: 1px solid var(--gray-200);
}

.signature-img {
    width: 120px;
    height: 60px;
    object-fit: contain;
    background: white;
    border-radius: 4px;
    border: 1px solid var(--gray-200);
}

.signature-info {
    flex: 1;
}

.signature-name {
    font-weight: 600;
    color: var(--gray-900);
    margin-bottom: 4px;
}

.signature-title {
    font-size: 13px;
    color: var(--gray-600);
}

.signature-position {
    display: inline-block;
    padding: 4px 8px;
    background: white;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    color: var(--primary);
    margin-top: 4px;
}

/* Toggle Switch */
.toggle-switch {
    position: relative;
    display: inline-block;
    width: 52px;
    height: 28px;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: var(--gray-200);
    transition: 0.3s;
    border-radius: 28px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 20px;
    width: 20px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: 0.3s;
    border-radius: 50%;
}

input:checked + .toggle-slider {
    background-color: var(--success);
}

input:checked + .toggle-slider:before {
    transform: translateX(24px);
}

/* Buttons */
.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary {
    background: var(--primary);
    color: white;
}

.btn-primary:hover {
    background: var(--primary-dark);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.btn-danger {
    background: var(--danger);
    color: white;
}

.btn-danger:hover {
    background: #dc2626;
}

.btn-secondary {
    background: var(--gray-200);
    color: var(--gray-700);
}

.btn-secondary:hover {
    background: var(--gray-300);
}

/* Status Badge */
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
}

.status-badge.active {
    background: #d1fae5;
    color: #065f46;
}

.status-badge.inactive {
    background: #fee2e2;
    color: #991b1b;
}

/* Alert */
.alert {
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.alert-success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #6ee7b7;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fca5a5;
}

/* Loading */
.loading {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    border-top-color: white;
    animation: spin 1s ease-in-out infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 768px) {
    .settings-container {
        padding: 20px 16px;
    }

    .settings-header {
        padding: 24px 20px;
    }

    .settings-title {
        font-size: 24px;
    }

    .tab-content {
        padding: 20px;
    }

    .tabs-nav {
        flex-wrap: nowrap;
        overflow-x: auto;
    }
}
</style>
@endpush

@section('content')
<div class="settings-container">
    <!-- Header -->
    <div class="settings-header">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1 class="settings-title">⚙️ ตั้งค่า Academy System</h1>
                <p class="settings-subtitle">จัดการการตั้งค่าทั้งหมดของระบบ Academy</p>
            </div>
            <div>
                <span class="status-badge {{ $settings->is_active ? 'active' : 'inactive' }}">
                    {{ $settings->is_active ? '✅ เปิดใช้งาน' : '❌ ปิดใช้งาน' }}
                </span>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            <span>✅</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-error">
            <span>❌</span>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Tabs Container -->
    <div class="tabs-container">
        <div class="tabs-nav">
            <button class="tab-button active" onclick="switchTab('basic')">
                <span>📝</span>
                <span>ข้อมูลพื้นฐาน</span>
            </button>
            <button class="tab-button" onclick="switchTab('branding')">
                <span>🎨</span>
                <span>โลโก้และสี</span>
            </button>
            <button class="tab-button" onclick="switchTab('certificate')">
                <span>📜</span>
                <span>ใบประกาศนียบัตร</span>
            </button>
            <button class="tab-button" onclick="switchTab('signatures')">
                <span>✍️</span>
                <span>ลายเซ็น</span>
            </button>
            <button class="tab-button" onclick="switchTab('email')">
                <span>✉️</span>
                <span>อีเมล</span>
            </button>
            <button class="tab-button" onclick="switchTab('course')">
                <span>📚</span>
                <span>คอร์ส</span>
            </button>
            <button class="tab-button" onclick="switchTab('instructor')">
                <span>👨‍🏫</span>
                <span>ครูผู้สอน</span>
            </button>
        </div>

        <div class="tab-content">
            <!-- Basic Settings Tab -->
            <div id="basic-tab" class="tab-pane active">
                <form action="{{ route('admin.academy.settings.update-basic') }}" method="POST">
                    @csrf
                    <div class="form-section">
                        <h3 class="section-title">ข้อมูลทั่วไป</h3>

                        <div class="form-group">
                            <label class="form-label">
                                ชื่อ Academy <span class="required">*</span>
                            </label>
                            <input type="text" name="academy_name" class="form-input"
                                   value="{{ old('academy_name', $settings->academy_name) }}" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">คำอธิบาย</label>
                            <textarea name="academy_description" class="form-textarea"
                                      rows="4">{{ old('academy_description', $settings->academy_description) }}</textarea>
                            <small class="form-help">แนะนำ Academy ของคุณให้ผู้เรียนรู้จัก</small>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                            <div class="form-group">
                                <label class="form-label">อีเมล</label>
                                <input type="email" name="academy_email" class="form-input"
                                       value="{{ old('academy_email', $settings->academy_email) }}">
                            </div>

                            <div class="form-group">
                                <label class="form-label">เบอร์โทร</label>
                                <input type="text" name="academy_phone" class="form-input"
                                       value="{{ old('academy_phone', $settings->academy_phone) }}">
                            </div>

                            <div class="form-group">
                                <label class="form-label">เว็บไซต์</label>
                                <input type="url" name="academy_website" class="form-input"
                                       value="{{ old('academy_website', $settings->academy_website) }}">
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 12px;">
                        <button type="submit" class="btn btn-primary">
                            <span>💾</span>
                            <span>บันทึกการตั้งค่า</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Branding Tab -->
            <div id="branding-tab" class="tab-pane">
                <div class="form-section">
                    <h3 class="section-title">โลโก้</h3>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px;">
                        <!-- Main Logo -->
                        <div>
                            <label class="form-label">โลโก้หลัก</label>
                            <div class="upload-area" onclick="document.getElementById('logo-main').click()">
                                <div class="upload-icon">🖼️</div>
                                <div class="upload-text">คลิกเพื่ออัปโหลด</div>
                                <div class="upload-hint">PNG, JPG (สูงสุด 2MB)</div>
                            </div>
                            <input type="file" id="logo-main" class="file-input" accept="image/*"
                                   onchange="uploadLogo(this, 'main')">
                            @if($settings->logo_url)
                                <div class="image-preview">
                                    <img src="{{ $settings->logo_url }}" class="preview-img" alt="Logo">
                                </div>
                            @endif
                        </div>

                        <!-- Small Logo -->
                        <div>
                            <label class="form-label">โลโก้เล็ก (สำหรับใบประกาศ)</label>
                            <div class="upload-area" onclick="document.getElementById('logo-small').click()">
                                <div class="upload-icon">🏷️</div>
                                <div class="upload-text">คลิกเพื่ออัปโหลด</div>
                                <div class="upload-hint">PNG, JPG (สูงสุด 2MB)</div>
                            </div>
                            <input type="file" id="logo-small" class="file-input" accept="image/*"
                                   onchange="uploadLogo(this, 'small')">
                            @if($settings->small_logo_url)
                                <div class="image-preview">
                                    <img src="{{ $settings->small_logo_url }}" class="preview-img" alt="Small Logo">
                                </div>
                            @endif
                        </div>

                        <!-- Favicon -->
                        <div>
                            <label class="form-label">Favicon</label>
                            <div class="upload-area" onclick="document.getElementById('logo-favicon').click()">
                                <div class="upload-icon">⭐</div>
                                <div class="upload-text">คลิกเพื่ออัปโหลด</div>
                                <div class="upload-hint">ICO, PNG (32x32px)</div>
                            </div>
                            <input type="file" id="logo-favicon" class="file-input" accept="image/*"
                                   onchange="uploadLogo(this, 'favicon')">
                            @if($settings->favicon_url)
                                <div class="image-preview">
                                    <img src="{{ $settings->favicon_url }}" class="preview-img" alt="Favicon">
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <form action="{{ route('admin.academy.settings.update-basic') }}" method="POST">
                    @csrf
                    <div class="form-section">
                        <h3 class="section-title">สีประจำ Academy</h3>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                            <div class="form-group">
                                <label class="form-label">สีหลัก (Primary)</label>
                                <div class="color-input-group">
                                    <input type="color" name="primary_color"
                                           value="{{ old('primary_color', $settings->primary_color) }}"
                                           class="color-preview">
                                    <input type="text" name="primary_color_text"
                                           value="{{ old('primary_color', $settings->primary_color) }}"
                                           class="form-input" placeholder="#6366f1">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">สีรอง (Secondary)</label>
                                <div class="color-input-group">
                                    <input type="color" name="secondary_color"
                                           value="{{ old('secondary_color', $settings->secondary_color) }}"
                                           class="color-preview">
                                    <input type="text" name="secondary_color_text"
                                           value="{{ old('secondary_color', $settings->secondary_color) }}"
                                           class="form-input" placeholder="#06b6d4">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end;">
                        <button type="submit" class="btn btn-primary">
                            <span>💾</span>
                            <span>บันทึกสี</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Certificate Tab -->
            <div id="certificate-tab" class="tab-pane">
                <form action="{{ route('admin.academy.settings.update-certificate') }}" method="POST">
                    @csrf
                    <div class="form-section">
                        <h3 class="section-title">Template ใบประกาศนียบัตร</h3>

                        <div class="form-group">
                            <label class="form-label">ประเภท Template</label>
                            <select name="certificate_template_type" class="form-select">
                                <option value="default" {{ $settings->certificate_template_type === 'default' ? 'selected' : '' }}>
                                    Template เริ่มต้น
                                </option>
                                <option value="custom" {{ $settings->certificate_template_type === 'custom' ? 'selected' : '' }}>
                                    กำหนดเอง (HTML)
                                </option>
                                <option value="uploaded" {{ $settings->certificate_template_type === 'uploaded' ? 'selected' : '' }}>
                                    อัปโหลดไฟล์
                                </option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">รูปแบบขอบ</label>
                            <select name="certificate_border_style" class="form-select">
                                <option value="classic" {{ $settings->certificate_border_style === 'classic' ? 'selected' : '' }}>
                                    คลาสสิก
                                </option>
                                <option value="modern" {{ $settings->certificate_border_style === 'modern' ? 'selected' : '' }}>
                                    โมเดิร์น
                                </option>
                                <option value="minimal" {{ $settings->certificate_border_style === 'minimal' ? 'selected' : '' }}>
                                    มินิมอล
                                </option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">HTML Header (ถ้ามี)</label>
                            <textarea name="certificate_header_html" class="form-textarea"
                                      rows="3">{{ old('certificate_header_html', $settings->certificate_header_html) }}</textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label">HTML Footer (ถ้ามี)</label>
                            <textarea name="certificate_footer_html" class="form-textarea"
                                      rows="3">{{ old('certificate_footer_html', $settings->certificate_footer_html) }}</textarea>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="section-title">การตั้งเลขที่ใบประกาศ</h3>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                            <div class="form-group">
                                <label class="form-label">Prefix</label>
                                <input type="text" name="certificate_number_prefix" class="form-input"
                                       value="{{ old('certificate_number_prefix', $settings->certificate_number_prefix) }}"
                                       placeholder="CERT">
                            </div>

                            <div class="form-group">
                                <label class="form-label">ความยาวตัวเลข</label>
                                <input type="number" name="certificate_number_length" class="form-input"
                                       value="{{ old('certificate_number_length', $settings->certificate_number_length) }}"
                                       min="4" max="20">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">รูปแบบเลขที่</label>
                            <input type="text" name="certificate_number_format" class="form-input"
                                   value="{{ old('certificate_number_format', $settings->certificate_number_format) }}"
                                   placeholder="CERT-{YEAR}{MONTH}-{RANDOM}">
                            <small class="form-help">ใช้: {YEAR}, {MONTH}, {DAY}, {RANDOM}</small>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end;">
                        <button type="submit" class="btn btn-primary">
                            <span>💾</span>
                            <span>บันทึกการตั้งค่า</span>
                        </button>
                    </div>
                </form>

                <!-- Upload Background & Templates -->
                <div class="form-section" style="margin-top: 32px;">
                    <h3 class="section-title">ไฟล์ Template</h3>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px;">
                        <div>
                            <label class="form-label">พื้นหลังใบประกาศ</label>
                            <div class="upload-area" onclick="document.getElementById('cert-bg').click()">
                                <div class="upload-icon">🎨</div>
                                <div class="upload-text">อัปโหลดพื้นหลัง</div>
                                <div class="upload-hint">PNG, JPG (สูงสุด 5MB)</div>
                            </div>
                            <input type="file" id="cert-bg" class="file-input" accept="image/*"
                                   onchange="uploadCertificateBackground(this)">
                        </div>

                        <div>
                            <label class="form-label">Word Template</label>
                            <div class="upload-area" onclick="document.getElementById('cert-word').click()">
                                <div class="upload-icon">📄</div>
                                <div class="upload-text">อัปโหลด .docx</div>
                                <div class="upload-hint">DOCX (สูงสุด 10MB)</div>
                            </div>
                            <input type="file" id="cert-word" class="file-input" accept=".doc,.docx"
                                   onchange="uploadCertificateTemplate(this, 'word')">
                        </div>

                        <div>
                            <label class="form-label">PDF Template</label>
                            <div class="upload-area" onclick="document.getElementById('cert-pdf').click()">
                                <div class="upload-icon">📑</div>
                                <div class="upload-text">อัปโหลด .pdf</div>
                                <div class="upload-hint">PDF (สูงสุด 10MB)</div>
                            </div>
                            <input type="file" id="cert-pdf" class="file-input" accept=".pdf"
                                   onchange="uploadCertificateTemplate(this, 'pdf')">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Signatures Tab -->
            <div id="signatures-tab" class="tab-pane">
                <div class="form-section">
                    <h3 class="section-title">ลายเซ็นบนใบประกาศ</h3>

                    <div class="signature-list" id="signature-list">
                        @forelse($settings->signatures_with_urls ?? [] as $index => $signature)
                            <div class="signature-item">
                                <img src="{{ $signature['signature_url'] }}" class="signature-img" alt="Signature">
                                <div class="signature-info">
                                    <div class="signature-name">{{ $signature['name'] }}</div>
                                    <div class="signature-title">{{ $signature['title'] }}</div>
                                    <span class="signature-position">{{ ucfirst($signature['position']) }}</span>
                                </div>
                                <button type="button" class="btn btn-danger" onclick="removeSignature({{ $index }})">
                                    🗑️ ลบ
                                </button>
                            </div>
                        @empty
                            <div style="text-align: center; padding: 40px; color: var(--gray-600);">
                                <div style="font-size: 48px; margin-bottom: 16px;">✍️</div>
                                <div>ยังไม่มีลายเซ็น</div>
                            </div>
                        @endforelse
                    </div>

                    <div style="margin-top: 24px;">
                        <button type="button" class="btn btn-primary" onclick="document.getElementById('add-signature-form').style.display='block'">
                            ➕ เพิ่มลายเซ็น
                        </button>
                    </div>

                    <!-- Add Signature Form -->
                    <div id="add-signature-form" style="display: none; margin-top: 24px; padding: 24px; background: var(--gray-50); border-radius: 12px;">
                        <h4 style="margin-bottom: 16px;">เพิ่มลายเซ็นใหม่</h4>
                        <form id="signature-form" onsubmit="addSignature(event)">
                            <div style="display: grid; gap: 16px;">
                                <div class="form-group">
                                    <label class="form-label">ชื่อผู้เซ็น</label>
                                    <input type="text" name="name" class="form-input" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">ตำแหน่ง</label>
                                    <input type="text" name="title" class="form-input" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">ตำแหน่งบนใบประกาศ</label>
                                    <select name="position" class="form-select" required>
                                        <option value="left">ซ้าย</option>
                                        <option value="center">กลาง</option>
                                        <option value="right">ขวา</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">ไฟล์ลายเซ็น</label>
                                    <input type="file" name="signature" class="form-input" accept="image/*" required>
                                </div>
                                <div style="display: flex; gap: 12px;">
                                    <button type="submit" class="btn btn-primary">บันทึก</button>
                                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('add-signature-form').style.display='none'">ยกเลิก</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Email Tab -->
            <div id="email-tab" class="tab-pane">
                <form action="{{ route('admin.academy.settings.update-email') }}" method="POST">
                    @csrf
                    <div class="form-section">
                        <h3 class="section-title">การตั้งค่าอีเมล</h3>

                        <div class="form-group">
                            <label style="display: flex; align-items: center; gap: 12px;">
                                <label class="toggle-switch">
                                    <input type="checkbox" name="send_certificate_email" value="1"
                                           {{ $settings->send_certificate_email ? 'checked' : '' }}>
                                    <span class="toggle-slider"></span>
                                </label>
                                <span class="form-label" style="margin: 0;">ส่งอีเมลเมื่อได้รับใบประกาศ</span>
                            </label>
                        </div>

                        <div class="form-group">
                            <label class="form-label">หัวเรื่องอีเมล</label>
                            <input type="text" name="certificate_email_subject" class="form-input"
                                   value="{{ old('certificate_email_subject', $settings->certificate_email_subject) }}"
                                   placeholder="ยินดีด้วย! คุณได้รับใบประกาศนียบัตร">
                        </div>

                        <div class="form-group">
                            <label class="form-label">เนื้อหาอีเมล</label>
                            <textarea name="certificate_email_body" class="form-textarea"
                                      rows="6">{{ old('certificate_email_body', $settings->certificate_email_body) }}</textarea>
                            <small class="form-help">ใช้ {course_title}, {student_name} สำหรับแทนค่า</small>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end;">
                        <button type="submit" class="btn btn-primary">
                            <span>💾</span>
                            <span>บันทึกการตั้งค่า</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Course Tab -->
            <div id="course-tab" class="tab-pane">
                <form action="{{ route('admin.academy.settings.update-course') }}" method="POST">
                    @csrf
                    <div class="form-section">
                        <h3 class="section-title">เงื่อนไขการรับใบประกาศ</h3>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                            <div class="form-group">
                                <label class="form-label">ความคืบหน้าขั้นต่ำ (%)</label>
                                <input type="number" name="min_completion_percentage" class="form-input"
                                       value="{{ old('min_completion_percentage', $settings->min_completion_percentage) }}"
                                       min="0" max="100">
                            </div>

                            <div class="form-group">
                                <label class="form-label">คะแนน Quiz ขั้นต่ำ (%)</label>
                                <input type="number" name="min_quiz_score" class="form-input"
                                       value="{{ old('min_quiz_score', $settings->min_quiz_score) }}"
                                       min="0" max="100">
                            </div>
                        </div>

                        <div class="form-group">
                            <label style="display: flex; align-items: center; gap: 12px;">
                                <label class="toggle-switch">
                                    <input type="checkbox" name="require_all_quizzes" value="1"
                                           {{ $settings->require_all_quizzes ? 'checked' : '' }}>
                                    <span class="toggle-slider"></span>
                                </label>
                                <span class="form-label" style="margin: 0;">ต้องทำ Quiz ทั้งหมด</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="section-title">ฟีเจอร์คอร์ส</h3>

                        <div style="display: grid; gap: 16px;">
                            <label style="display: flex; align-items: center; gap: 12px;">
                                <label class="toggle-switch">
                                    <input type="checkbox" name="enable_student_reviews" value="1"
                                           {{ $settings->enable_student_reviews ? 'checked' : '' }}>
                                    <span class="toggle-slider"></span>
                                </label>
                                <span class="form-label" style="margin: 0;">เปิดให้นักเรียนรีวิว</span>
                            </label>

                            <label style="display: flex; align-items: center; gap: 12px;">
                                <label class="toggle-switch">
                                    <input type="checkbox" name="enable_course_discussions" value="1"
                                           {{ $settings->enable_course_discussions ? 'checked' : '' }}>
                                    <span class="toggle-slider"></span>
                                </label>
                                <span class="form-label" style="margin: 0;">เปิดห้องสนทนา</span>
                            </label>

                            <label style="display: flex; align-items: center; gap: 12px;">
                                <label class="toggle-switch">
                                    <input type="checkbox" name="enable_course_notes" value="1"
                                           {{ $settings->enable_course_notes ? 'checked' : '' }}>
                                    <span class="toggle-slider"></span>
                                </label>
                                <span class="form-label" style="margin: 0;">เปิดระบบจดโน้ต</span>
                            </label>

                            <label style="display: flex; align-items: center; gap: 12px;">
                                <label class="toggle-switch">
                                    <input type="checkbox" name="enable_course_downloads" value="1"
                                           {{ $settings->enable_course_downloads ? 'checked' : '' }}>
                                    <span class="toggle-slider"></span>
                                </label>
                                <span class="form-label" style="margin: 0;">อนุญาตให้ดาวน์โหลดเอกสาร</span>
                            </label>

                            <label style="display: flex; align-items: center; gap: 12px;">
                                <label class="toggle-switch">
                                    <input type="checkbox" name="enable_course_preview" value="1"
                                           {{ $settings->enable_course_preview ? 'checked' : '' }}>
                                    <span class="toggle-slider"></span>
                                </label>
                                <span class="form-label" style="margin: 0;">อนุญาตให้ดูตัวอย่างคอร์ส</span>
                            </label>

                            <label style="display: flex; align-items: center; gap: 12px;">
                                <label class="toggle-switch">
                                    <input type="checkbox" name="require_enrollment_approval" value="1"
                                           {{ $settings->require_enrollment_approval ? 'checked' : '' }}>
                                    <span class="toggle-slider"></span>
                                </label>
                                <span class="form-label" style="margin: 0;">ต้องได้รับอนุมัติก่อนลงเรียน</span>
                            </label>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end;">
                        <button type="submit" class="btn btn-primary">
                            <span>💾</span>
                            <span>บันทึกการตั้งค่า</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Instructor Tab -->
            <div id="instructor-tab" class="tab-pane">
                <form action="{{ route('admin.academy.settings.update-instructor') }}" method="POST">
                    @csrf
                    <div class="form-section">
                        <h3 class="section-title">การตั้งค่าครูผู้สอน</h3>

                        <div class="form-group">
                            <label style="display: flex; align-items: center; gap: 12px;">
                                <label class="toggle-switch">
                                    <input type="checkbox" name="allow_instructors_create_courses" value="1"
                                           {{ $settings->allow_instructors_create_courses ? 'checked' : '' }}>
                                    <span class="toggle-slider"></span>
                                </label>
                                <span class="form-label" style="margin: 0;">อนุญาตให้ครูสร้างคอร์สเอง</span>
                            </label>
                        </div>

                        <div class="form-group">
                            <label style="display: flex; align-items: center; gap: 12px;">
                                <label class="toggle-switch">
                                    <input type="checkbox" name="require_course_approval" value="1"
                                           {{ $settings->require_course_approval ? 'checked' : '' }}>
                                    <span class="toggle-slider"></span>
                                </label>
                                <span class="form-label" style="margin: 0;">คอร์สต้องได้รับอนุมัติก่อนเผยแพร่</span>
                            </label>
                        </div>

                        <div class="form-group">
                            <label class="form-label">ค่าคอมมิชชั่นครูผู้สอน (%)</label>
                            <input type="number" name="instructor_commission_percentage" class="form-input"
                                   value="{{ old('instructor_commission_percentage', $settings->instructor_commission_percentage) }}"
                                   min="0" max="100">
                            <small class="form-help">เปอร์เซ็นต์รายได้ที่ครูจะได้รับจากการขายคอร์ส</small>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end;">
                        <button type="submit" class="btn btn-primary">
                            <span>💾</span>
                            <span>บันทึกการตั้งค่า</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function switchTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-pane').forEach(pane => {
        pane.classList.remove('active');
    });

    // Remove active class from all buttons
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('active');
    });

    // Show selected tab
    document.getElementById(tabName + '-tab').classList.add('active');
    event.target.closest('.tab-button').classList.add('active');
}

function uploadLogo(input, type) {
    if (!input.files || !input.files[0]) return;

    const formData = new FormData();
    formData.append('logo', input.files[0]);
    formData.append('type', type);
    formData.append('_token', '{{ csrf_token() }}');

    fetch('{{ route("admin.academy.settings.upload-logo") }}', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ อัปโหลดสำเร็จ!');
            location.reload();
        } else {
            alert('❌ เกิดข้อผิดพลาด: ' + data.message);
        }
    })
    .catch(error => {
        alert('❌ เกิดข้อผิดพลาด');
        console.error(error);
    });
}

function uploadCertificateBackground(input) {
    if (!input.files || !input.files[0]) return;

    const formData = new FormData();
    formData.append('background', input.files[0]);
    formData.append('_token', '{{ csrf_token() }}');

    fetch('{{ route("admin.academy.settings.upload-certificate-background") }}', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ อัปโหลดสำเร็จ!');
        } else {
            alert('❌ เกิดข้อผิดพลาด: ' + data.message);
        }
    });
}

function uploadCertificateTemplate(input, type) {
    if (!input.files || !input.files[0]) return;

    const formData = new FormData();
    formData.append('template', input.files[0]);
    formData.append('type', type);
    formData.append('_token', '{{ csrf_token() }}');

    fetch('{{ route("admin.academy.settings.upload-certificate-template") }}', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ อัปโหลดสำเร็จ!');
        } else {
            alert('❌ เกิดข้อผิดพลาด: ' + data.message);
        }
    });
}

function addSignature(event) {
    event.preventDefault();

    const form = event.target;
    const formData = new FormData(form);
    formData.append('_token', '{{ csrf_token() }}');

    fetch('{{ route("admin.academy.settings.add-signature") }}', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ เพิ่มลายเซ็นสำเร็จ!');
            location.reload();
        } else {
            alert('❌ เกิดข้อผิดพลาด: ' + data.message);
        }
    });
}

function removeSignature(index) {
    if (!confirm('ต้องการลบลายเซ็นนี้?')) return;

    fetch(`{{ url('admin/academy/settings/remove-signature') }}/${index}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ ลบสำเร็จ!');
            location.reload();
        } else {
            alert('❌ เกิดข้อผิดพลาด');
        }
    });
}

// Sync color picker with text input
document.querySelectorAll('input[type="color"]').forEach(colorInput => {
    const textInput = colorInput.nextElementSibling;
    if (textInput && textInput.type === 'text') {
        colorInput.addEventListener('input', () => {
            textInput.value = colorInput.value;
        });
        textInput.addEventListener('input', () => {
            if (/^#[0-9A-F]{6}$/i.test(textInput.value)) {
                colorInput.value = textInput.value;
            }
        });
    }
});
</script>
@endpush
@endsection
