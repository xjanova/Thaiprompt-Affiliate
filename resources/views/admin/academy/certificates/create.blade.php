@extends('layouts.admin-v3')

@section('title', 'สร้างใบประกาศนียบัตร')

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

.create-container {
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

.page-subtitle {
    font-size: 16px;
    opacity: 0.95;
    margin: 0;
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

.form-input, .form-textarea, .form-select {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid var(--gray-200);
    border-radius: 8px;
    font-size: 14px;
}

.form-input:focus, .form-textarea:focus, .form-select:focus {
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
    .create-container {
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
<div class="create-container">
    <!-- Header -->
    <div class="page-header">
        <h1 class="page-title">➕ สร้างใบประกาศนียบัตร</h1>
        <p class="page-subtitle">สร้างใบประกาศนียบัตรด้วยตนเอง (Manual)</p>
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
        <form action="{{ route('admin.academy.certificates.store') }}" method="POST">
            @csrf

            <!-- Student Selection -->
            <div class="form-section">
                <h3 class="section-title">ข้อมูลนักเรียน</h3>

                <div class="form-group">
                    <label class="form-label">
                        เลือกนักเรียน <span class="required">*</span>
                    </label>
                    <select name="user_id" class="form-select" required onchange="fillUserData(this)">
                        <option value="">-- เลือกนักเรียน --</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}"
                                    data-name="{{ $user->name }}"
                                    data-email="{{ $user->email }}"
                                    {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->email }})
                            </option>
                        @endforeach
                    </select>
                    <small class="form-help">เลือกผู้ใช้ที่จะรับใบประกาศ</small>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">ชื่อที่แสดงบนใบประกาศ</label>
                        <input type="text" name="student_name" id="student_name" class="form-input"
                               value="{{ old('student_name') }}" placeholder="จะใช้ชื่อจากระบบถ้าไม่ระบุ">
                    </div>

                    <div class="form-group">
                        <label class="form-label">อีเมลที่แสดงบนใบประกาศ</label>
                        <input type="email" name="student_email" id="student_email" class="form-input"
                               value="{{ old('student_email') }}" placeholder="จะใช้อีเมลจากระบบถ้าไม่ระบุ">
                    </div>
                </div>
            </div>

            <!-- Course Selection -->
            <div class="form-section">
                <h3 class="section-title">ข้อมูลคอร์ส</h3>

                <div class="form-group">
                    <label class="form-label">
                        เลือกคอร์ส <span class="required">*</span>
                    </label>
                    <select name="article_id" class="form-select" required>
                        <option value="">-- เลือกคอร์ส --</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" {{ old('article_id') == $course->id ? 'selected' : '' }}>
                                {{ $course->title }}
                            </option>
                        @endforeach
                    </select>
                    <small class="form-help">เลือกคอร์สที่นักเรียนเรียนจบ</small>
                </div>
            </div>

            <!-- Performance Data -->
            <div class="form-section">
                <h3 class="section-title">ข้อมูลผลการเรียน</h3>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">คะแนน Quiz (%)</label>
                        <input type="number" name="quiz_score" class="form-input"
                               value="{{ old('quiz_score') }}" min="0" max="100" step="0.01"
                               placeholder="เช่น 85.5">
                        <small class="form-help">คะแนนเฉลี่ยจาก Quiz (ถ้ามี)</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">ชั่วโมงเรียนทั้งหมด</label>
                        <input type="number" name="total_hours" class="form-input"
                               value="{{ old('total_hours') }}" min="0" step="0.1"
                               placeholder="เช่น 12.5">
                        <small class="form-help">จำนวนชั่วโมงที่ใช้เรียน</small>
                    </div>
                </div>
            </div>

            <!-- Additional Info -->
            <div class="form-section">
                <h3 class="section-title">ข้อมูลเพิ่มเติม (ถ้ามี)</h3>

                <div class="form-group">
                    <label class="form-label">ข้อความพิเศษ</label>
                    <textarea name="custom_text" class="form-textarea"
                              placeholder="ข้อความพิเศษที่จะแสดงบนใบประกาศ เช่น ยินดีด้วย! คุณได้รับรางวัลผู้เรียนดีเด่น">{{ old('custom_text') }}</textarea>
                    <small class="form-help">ข้อความนี้จะปรากฏบนใบประกาศ (ถ้ามี)</small>
                </div>
            </div>

            <!-- Actions -->
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <a href="{{ route('admin.academy.certificates.index') }}" class="btn btn-secondary">
                    ยกเลิก
                </a>
                <button type="submit" class="btn btn-primary">
                    💾 สร้างใบประกาศ
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function fillUserData(select) {
    const selectedOption = select.options[select.selectedIndex];
    if (selectedOption.value) {
        document.getElementById('student_name').value = selectedOption.getAttribute('data-name');
        document.getElementById('student_email').value = selectedOption.getAttribute('data-email');
    } else {
        document.getElementById('student_name').value = '';
        document.getElementById('student_email').value = '';
    }
}
</script>
@endpush
@endsection
