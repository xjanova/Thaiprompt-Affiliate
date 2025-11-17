@extends('layouts.admin-v3')

@section('title', 'แก้ไขใบประกาศนียบัตร')

@push('styles')
<style>
:root {
    --primary: #6366f1;
    --gray-50: #f9fafb;
    --gray-100: #f3f4f6;
    --gray-200: #e5e7eb;
    --gray-600: #4b5563;
    --gray-700: #374151;
    --gray-900: #111827;
}

.edit-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 32px 20px;
}

.page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 16px;
    padding: 32px;
    color: white;
    margin-bottom: 32px;
}

.page-title {
    font-size: 32px;
    font-weight: 800;
    margin: 0 0 8px 0;
}

.cert-number {
    font-family: 'Monaco', monospace;
    font-size: 18px;
    opacity: 0.95;
}

.form-card {
    background: white;
    border-radius: 12px;
    padding: 32px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

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
    color: #ef4444;
}

.form-input, .form-textarea {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid var(--gray-200);
    border-radius: 8px;
    font-size: 14px;
}

.form-input:focus, .form-textarea:focus {
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

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.info-box {
    background: var(--gray-50);
    border: 1px solid var(--gray-200);
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 20px;
}

.info-label {
    font-size: 12px;
    font-weight: 600;
    color: var(--gray-600);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 4px;
}

.info-value {
    font-size: 14px;
    font-weight: 600;
    color: var(--gray-900);
}

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
    text-decoration: none;
}

.btn-primary {
    background: var(--primary);
    color: white;
}

.btn-primary:hover {
    background: #4f46e5;
    transform: translateY(-1px);
}

.btn-secondary {
    background: var(--gray-200);
    color: var(--gray-700);
}

.btn-secondary:hover {
    background: var(--gray-300);
}

.alert {
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fca5a5;
}

@media (max-width: 768px) {
    .edit-container {
        padding: 20px 16px;
    }

    .page-header {
        padding: 24px 20px;
    }

    .page-title {
        font-size: 24px;
    }

    .form-card {
        padding: 24px 20px;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }
}
</style>
@endpush

@section('content')
<div class="edit-container">
    <!-- Header -->
    <div class="page-header">
        <h1 class="page-title">✏️ แก้ไขใบประกาศนียบัตร</h1>
        <p class="cert-number">{{ $certificate->certificate_number }}</p>
    </div>

    @if($errors->any())
        <div class="alert alert-error">
            <strong>⚠️ พบข้อผิดพลาด:</strong>
            <ul style="margin: 8px 0 0 20px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Form -->
    <div class="form-card">
        <form action="{{ route('admin.academy.certificates.update', $certificate->id) }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Basic Info (Read-only) -->
            <div class="form-section">
                <h3 class="section-title">ข้อมูลพื้นฐาน</h3>

                <div class="form-grid">
                    <div class="info-box">
                        <div class="info-label">นักเรียน</div>
                        <div class="info-value">{{ $certificate->user->name }}</div>
                    </div>

                    <div class="info-box">
                        <div class="info-label">คอร์ส</div>
                        <div class="info-value">{{ $certificate->article->title }}</div>
                    </div>

                    <div class="info-box">
                        <div class="info-label">เลขที่ใบประกาศ</div>
                        <div class="info-value">{{ $certificate->certificate_number }}</div>
                    </div>

                    <div class="info-box">
                        <div class="info-label">รหัสยืนยัน</div>
                        <div class="info-value">{{ $certificate->verification_code }}</div>
                    </div>
                </div>
            </div>

            <!-- Editable Fields -->
            <div class="form-section">
                <h3 class="section-title">ข้อมูลที่แสดงบนใบประกาศ</h3>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">
                            ชื่อนักเรียน <span class="required">*</span>
                        </label>
                        <input type="text" name="student_name" class="form-input"
                               value="{{ old('student_name', $certificate->student_name) }}" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            อีเมลนักเรียน <span class="required">*</span>
                        </label>
                        <input type="email" name="student_email" class="form-input"
                               value="{{ old('student_email', $certificate->student_email) }}" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            ชื่อครูผู้สอน <span class="required">*</span>
                        </label>
                        <input type="text" name="instructor_name" class="form-input"
                               value="{{ old('instructor_name', $certificate->instructor_name) }}" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            วันที่ออกใบประกาศ <span class="required">*</span>
                        </label>
                        <input type="date" name="issued_at" class="form-input"
                               value="{{ old('issued_at', $certificate->issued_at->format('Y-m-d')) }}" required>
                    </div>
                </div>
            </div>

            <!-- Performance Data -->
            <div class="form-section">
                <h3 class="section-title">ข้อมูลผลการเรียน</h3>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">คะแนน Quiz (%)</label>
                        <input type="number" name="quiz_score" class="form-input"
                               value="{{ old('quiz_score', $certificate->quiz_score) }}"
                               min="0" max="100" step="0.01">
                        <small class="form-help">คะแนนเฉลี่ยจาก Quiz</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">ชั่วโมงเรียนทั้งหมด</label>
                        <input type="number" name="total_hours" class="form-input"
                               value="{{ old('total_hours', $certificate->total_hours) }}"
                               min="0" step="0.1">
                        <small class="form-help">จำนวนชั่วโมงที่ใช้เรียน</small>
                    </div>
                </div>
            </div>

            <!-- Custom Text -->
            <div class="form-section">
                <h3 class="section-title">ข้อความพิเศษ</h3>

                <div class="form-group">
                    <label class="form-label">ข้อความพิเศษบนใบประกาศ</label>
                    <textarea name="custom_text" class="form-textarea">{{ old('custom_text', $certificate->custom_text) }}</textarea>
                    <small class="form-help">ข้อความนี้จะปรากฏบนใบประกาศ (ถ้ามี)</small>
                </div>
            </div>

            <!-- Warning -->
            <div style="background: #fef3c7; border: 1px solid #fcd34d; border-radius: 8px; padding: 16px; margin-bottom: 24px;">
                <strong style="color: #92400e;">⚠️ คำเตือน:</strong>
                <p style="margin: 4px 0 0 0; color: #92400e; font-size: 14px;">
                    การแก้ไขข้อมูลจะสร้าง PDF ใบประกาศใหม่ทันที และเขียนทับไฟล์เดิม
                </p>
            </div>

            <!-- Actions -->
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <a href="{{ route('admin.academy.certificates.index') }}" class="btn btn-secondary">
                    ยกเลิก
                </a>
                <button type="submit" class="btn btn-primary">
                    💾 บันทึกการแก้ไข
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
