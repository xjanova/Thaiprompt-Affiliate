@extends('layouts.admin-v3')

@section('title', 'Certificate - ' . $certificate->certificate_number)

@push('styles')
<style>
body {
    background: #f8f9fa;
}

.certificate-viewer {
    max-width: 1400px;
    margin: 32px auto;
    padding: 0 16px;
}

.viewer-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 16px;
}

.btn-group {
    display: flex;
    gap: 12px;
}

.btn {
    padding: 12px 24px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
}

.btn-primary {
    background: #6366f1;
    color: white;
    border: none;
}

.btn-primary:hover {
    background: #4f46e5;
    transform: translateY(-2px);
}

.btn-secondary {
    background: white;
    color: #6366f1;
    border: 2px solid #6366f1;
}

.btn-secondary:hover {
    background: #6366f1;
    color: white;
}

.btn-success {
    background: #10b981;
    color: white;
    border: none;
}

.btn-success:hover {
    background: #059669;
}

.certificate-frame {
    background: white;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.certificate-preview-wrapper {
    max-width: 100%;
    overflow: auto;
}

@media print {
    .viewer-actions,
    .navbar,
    .sidebar {
        display: none !important;
    }

    .certificate-frame {
        box-shadow: none;
        padding: 0;
    }
}
</style>
@endpush

@section('content')
<div class="certificate-viewer">
    <!-- Actions Bar -->
    <div class="viewer-actions">
        <a href="{{ route('admin.certificates.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i>
            กลับ
        </a>

        <div class="btn-group">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i>
                พิมพ์
            </button>
            <a href="{{ route('admin.certificates.download', $certificate->id) }}" class="btn btn-primary">
                <i class="fas fa-download"></i>
                ดาวน์โหลด
            </a>
            <button onclick="shareCertificate()" class="btn btn-success">
                <i class="fas fa-share-alt"></i>
                แชร์
            </button>
        </div>
    </div>

    <!-- Certificate Preview -->
    <div class="certificate-frame">
        <div class="certificate-preview-wrapper">
            @include('certificates.template', ['certificate' => $certificate])
        </div>
    </div>

    <!-- Share Info -->
    <div class="mt-4 p-4 bg-white rounded">
        <h5 class="mb-3">ข้อมูลการแชร์</h5>
        <div class="mb-3">
            <label class="form-label small text-muted">ลิงก์ตรวจสอบใบประกาศ</label>
            <div class="input-group">
                <input type="text" class="form-control" id="verifyLink" readonly value="{{ $certificate->verification_url }}">
                <button class="btn btn-outline-secondary" onclick="copyToClipboard('verifyLink')">
                    <i class="fas fa-copy"></i>
                </button>
            </div>
        </div>
        <div>
            <label class="form-label small text-muted">รหัสยืนยัน</label>
            <div class="input-group">
                <input type="text" class="form-control" id="verifyCode" readonly value="{{ $certificate->verification_code }}">
                <button class="btn btn-outline-secondary" onclick="copyToClipboard('verifyCode')">
                    <i class="fas fa-copy"></i>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function copyToClipboard(elementId) {
    const input = document.getElementById(elementId);
    input.select();
    document.execCommand('copy');

    // Show feedback
    alert('คัดลอกแล้ว!');
}

function shareCertificate() {
    const url = '{{ $certificate->verification_url }}';
    const text = 'ตรวจสอบใบประกาศนียบัตรของฉัน: {{ $certificate->course_title }}';

    if (navigator.share) {
        navigator.share({
            title: 'Certificate',
            text: text,
            url: url
        }).catch(console.error);
    } else {
        copyToClipboard('verifyLink');
        alert('คัดลอกลิงก์แชร์แล้ว!');
    }
}
</script>
@endpush
