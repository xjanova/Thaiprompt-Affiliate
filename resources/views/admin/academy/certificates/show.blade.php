@extends('layouts.admin')

@section('title', 'รายละเอียดใบประกาศนียบัตร')

@push('styles')
<style>
:root {
    --primary: #6366f1;
    --success: #10b981;
    --danger: #ef4444;
    --gray-50: #f9fafb;
    --gray-100: #f3f4f6;
    --gray-200: #e5e7eb;
    --gray-600: #4b5563;
    --gray-700: #374151;
    --gray-900: #111827;
}

.show-container {
    max-width: 1200px;
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

.header-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 20px;
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

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
}

.badge-active {
    background: rgba(255, 255, 255, 0.25);
    color: white;
}

.badge-revoked {
    background: rgba(239, 68, 68, 0.2);
    color: white;
}

.actions-bar {
    display: flex;
    gap: 12px;
}

.content-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 24px;
}

.card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.card-title {
    font-size: 18px;
    font-weight: 700;
    color: var(--gray-900);
    margin: 0 0 20px 0;
    padding-bottom: 12px;
    border-bottom: 2px solid var(--gray-100);
}

.info-grid {
    display: grid;
    gap: 16px;
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 12px;
    background: var(--gray-50);
    border-radius: 8px;
}

.info-label {
    font-size: 13px;
    font-weight: 600;
    color: var(--gray-600);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-value {
    font-size: 14px;
    font-weight: 600;
    color: var(--gray-900);
    text-align: right;
}

.cert-preview {
    border: 2px solid var(--gray-200);
    border-radius: 8px;
    overflow: hidden;
    margin-top: 20px;
}

.cert-preview iframe {
    width: 100%;
    height: 600px;
    border: none;
}

.btn {
    padding: 10px 20px;
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

.btn-success {
    background: var(--success);
    color: white;
}

.btn-success:hover {
    background: #059669;
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

.quick-actions {
    display: grid;
    gap: 12px;
}

.action-btn {
    width: 100%;
    justify-content: flex-start;
}

.revoke-info {
    background: #fee2e2;
    border: 1px solid #fca5a5;
    border-radius: 8px;
    padding: 16px;
    margin-top: 16px;
}

.revoke-title {
    font-weight: 700;
    color: #991b1b;
    margin-bottom: 8px;
}

.revoke-text {
    font-size: 14px;
    color: #991b1b;
    margin: 0;
}

@media (max-width: 1024px) {
    .content-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .show-container {
        padding: 20px 16px;
    }

    .page-header {
        padding: 24px 20px;
    }

    .page-title {
        font-size: 24px;
    }

    .header-top {
        flex-direction: column;
        gap: 16px;
    }

    .actions-bar {
        flex-wrap: wrap;
        width: 100%;
    }

    .actions-bar .btn {
        flex: 1;
        justify-content: center;
    }
}
</style>
@endpush

@section('content')
<div class="show-container">
    <!-- Header -->
    <div class="page-header">
        <div class="header-top">
            <div>
                <h1 class="page-title">📜 รายละเอียดใบประกาศนียบัตร</h1>
                <p class="cert-number">{{ $certificate->certificate_number }}</p>
            </div>
            <div>
                @if($certificate->is_revoked)
                    <span class="status-badge badge-revoked">
                        ❌ ถูกเพิกถอนแล้ว
                    </span>
                @else
                    <span class="status-badge badge-active">
                        ✅ ใช้งานได้
                    </span>
                @endif
            </div>
        </div>

        <div class="actions-bar">
            <a href="{{ route('admin.academy.certificates.index') }}" class="btn btn-secondary">
                ← กลับ
            </a>
            <a href="{{ route('admin.academy.certificates.edit', $certificate->id) }}" class="btn btn-primary">
                ✏️ แก้ไข
            </a>
            <a href="{{ route('admin.certificates.download', $certificate->id) }}" class="btn btn-success" target="_blank">
                📥 ดาวน์โหลด
            </a>
            @if(!$certificate->is_revoked)
                <button onclick="revokeCertificate()" class="btn btn-danger">
                    🚫 เพิกถอน
                </button>
            @else
                <button onclick="restoreCertificate()" class="btn btn-success">
                    ♻️ กู้คืน
                </button>
            @endif
        </div>
    </div>

    <div class="content-grid">
        <!-- Main Content -->
        <div>
            <!-- Certificate Details -->
            <div class="card">
                <h2 class="card-title">ข้อมูลใบประกาศ</h2>

                <div class="info-grid">
                    <div class="info-row">
                        <span class="info-label">นักเรียน</span>
                        <span class="info-value">{{ $certificate->student_name }}</span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">อีเมล</span>
                        <span class="info-value">{{ $certificate->student_email }}</span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">คอร์ส</span>
                        <span class="info-value">{{ $certificate->course_title }}</span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">ครูผู้สอน</span>
                        <span class="info-value">{{ $certificate->instructor_name }}</span>
                    </div>

                    @if($certificate->quiz_score)
                        <div class="info-row">
                            <span class="info-label">คะแนน Quiz</span>
                            <span class="info-value" style="color: var(--success);">
                                {{ number_format($certificate->quiz_score, 2) }}%
                            </span>
                        </div>
                    @endif

                    @if($certificate->total_hours)
                        <div class="info-row">
                            <span class="info-label">ชั่วโมงเรียน</span>
                            <span class="info-value">{{ number_format($certificate->total_hours, 1) }} ชม.</span>
                        </div>
                    @endif

                    <div class="info-row">
                        <span class="info-label">วันที่ออกใบประกาศ</span>
                        <span class="info-value">{{ $certificate->issued_at->format('d/m/Y H:i') }}</span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">รหัสยืนยัน</span>
                        <span class="info-value" style="font-family: monospace;">
                            {{ $certificate->verification_code }}
                        </span>
                    </div>

                    @if($certificate->custom_text)
                        <div class="info-row" style="flex-direction: column; align-items: flex-start;">
                            <span class="info-label">ข้อความพิเศษ</span>
                            <p style="margin: 8px 0 0 0; color: var(--gray-700); font-size: 14px;">
                                {{ $certificate->custom_text }}
                            </p>
                        </div>
                    @endif
                </div>

                @if($certificate->is_revoked)
                    <div class="revoke-info">
                        <div class="revoke-title">ใบประกาศนี้ถูกเพิกถอนแล้ว</div>
                        <p class="revoke-text">
                            <strong>วันที่เพิกถอน:</strong> {{ $certificate->revoked_at->format('d/m/Y H:i') }}
                        </p>
                        @if($certificate->revoked_reason)
                            <p class="revoke-text">
                                <strong>เหตุผล:</strong> {{ $certificate->revoked_reason }}
                            </p>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Certificate Preview -->
            <div class="card" style="margin-top: 24px;">
                <h2 class="card-title">ตัวอย่างใบประกาศ</h2>

                @if($certificate->pdf_path && Storage::disk('public')->exists($certificate->pdf_path))
                    <div class="cert-preview">
                        <iframe src="{{ Storage::disk('public')->url($certificate->pdf_path) }}"></iframe>
                    </div>
                @else
                    <div style="text-align: center; padding: 60px 20px; background: var(--gray-50); border-radius: 8px;">
                        <div style="font-size: 48px; margin-bottom: 16px;">📄</div>
                        <p style="color: var(--gray-600); margin: 0;">ไม่พบไฟล์ใบประกาศ</p>
                        <p style="color: var(--gray-600); font-size: 14px; margin: 8px 0 0 0;">
                            อาจจะยังไม่ได้สร้างหรือถูกลบไปแล้ว
                        </p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Sidebar -->
        <div>
            <!-- Quick Actions -->
            <div class="card">
                <h2 class="card-title">การกระทำ</h2>

                <div class="quick-actions">
                    <a href="{{ route('certificate.verify', $certificate->verification_code) }}"
                       target="_blank"
                       class="btn btn-primary action-btn">
                        🔍 ตรวจสอบใบประกาศ (Public)
                    </a>

                    <a href="{{ route('certificate.share', $certificate->id) }}"
                       target="_blank"
                       class="btn btn-secondary action-btn">
                        🔗 หน้าแชร์
                    </a>

                    <button onclick="copyVerificationLink()" class="btn btn-secondary action-btn">
                        📋 คัดลอกลิงก์ตรวจสอบ
                    </button>

                    <a href="mailto:{{ $certificate->student_email }}" class="btn btn-secondary action-btn">
                        ✉️ ส่งอีเมลถึงนักเรียน
                    </a>
                </div>
            </div>

            <!-- User Info -->
            <div class="card" style="margin-top: 24px;">
                <h2 class="card-title">ข้อมูลผู้ใช้</h2>

                <div style="text-align: center; margin-bottom: 16px;">
                    <div style="width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #667eea, #764ba2); margin: 0 auto 12px; display: flex; align-items: center; justify-content: center; font-size: 32px; color: white; font-weight: 700;">
                        {{ strtoupper(substr($certificate->user->name, 0, 1)) }}
                    </div>
                    <div style="font-weight: 700; font-size: 16px; color: var(--gray-900);">
                        {{ $certificate->user->name }}
                    </div>
                    <div style="font-size: 14px; color: var(--gray-600); margin-top: 4px;">
                        {{ $certificate->user->email }}
                    </div>
                </div>

                <a href="{{ route('admin.users.show', $certificate->user->id) }}"
                   class="btn btn-secondary"
                   style="width: 100%; justify-content: center;">
                    👤 ดูโปรไฟล์ผู้ใช้
                </a>
            </div>

            <!-- Course Info -->
            <div class="card" style="margin-top: 24px;">
                <h2 class="card-title">ข้อมูลคอร์ส</h2>

                <div style="margin-bottom: 16px;">
                    @if($certificate->article->thumbnail_url)
                        <img src="{{ $certificate->article->thumbnail_url }}"
                             style="width: 100%; border-radius: 8px; margin-bottom: 12px;"
                             alt="Course thumbnail">
                    @endif

                    <h3 style="font-size: 16px; font-weight: 700; color: var(--gray-900); margin: 0 0 8px 0;">
                        {{ $certificate->article->title }}
                    </h3>

                    <p style="font-size: 14px; color: var(--gray-600); margin: 0;">
                        {{ Str::limit($certificate->article->description ?? 'ไม่มีคำอธิบาย', 100) }}
                    </p>
                </div>

                <a href="{{ route('admin.articles.edit', $certificate->article->id) }}"
                   class="btn btn-secondary"
                   style="width: 100%; justify-content: center;">
                    📝 จัดการคอร์ส
                </a>
            </div>

            <!-- System Info -->
            <div class="card" style="margin-top: 24px;">
                <h2 class="card-title">ข้อมูลระบบ</h2>

                <div style="font-size: 13px; color: var(--gray-600); line-height: 1.8;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span>Created:</span>
                        <span>{{ $certificate->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span>Updated:</span>
                        <span>{{ $certificate->updated_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span>ID:</span>
                        <span style="font-family: monospace;">#{{ $certificate->id }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function revokeCertificate() {
    const reason = prompt('กรุณาระบุเหตุผลในการเพิกถอน (ถ้ามี):');
    if (reason === null) return;

    fetch('{{ route("admin.academy.certificates.revoke", $certificate->id) }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ revoked_reason: reason })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ เพิกถอนใบประกาศเรียบร้อยแล้ว');
            location.reload();
        } else {
            alert('❌ เกิดข้อผิดพลาด');
        }
    });
}

function restoreCertificate() {
    if (!confirm('ต้องการกู้คืนใบประกาศนี้?')) return;

    fetch('{{ route("admin.academy.certificates.restore", $certificate->id) }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ กู้คืนใบประกาศเรียบร้อยแล้ว');
            location.reload();
        } else {
            alert('❌ เกิดข้อผิดพลาด');
        }
    });
}

function copyVerificationLink() {
    const link = '{{ route("certificate.verify", $certificate->verification_code) }}';
    navigator.clipboard.writeText(link).then(() => {
        alert('✅ คัดลอกลิงก์เรียบร้อยแล้ว!');
    }).catch(() => {
        prompt('คัดลอกลิงก์นี้:', link);
    });
}
</script>
@endpush
@endsection
