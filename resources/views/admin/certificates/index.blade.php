@extends('layouts.admin')

@section('title', 'My Certificates')

@push('styles')
<style>
:root {
    --primary-color: #6366f1;
    --success-color: #10b981;
    --gray-50: #f9fafb;
    --gray-100: #f3f4f6;
    --gray-200: #e5e7eb;
    --gray-600: #4b5563;
    --gray-700: #374151;
    --gray-900: #111827;
}

body {
    background: var(--gray-50);
}

.certificates-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 32px 16px;
}

.page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 24px;
    padding: 48px;
    color: white;
    margin-bottom: 32px;
    text-align: center;
}

.page-title {
    font-size: 42px;
    font-weight: 800;
    margin-bottom: 12px;
}

.page-subtitle {
    font-size: 18px;
    opacity: 0.95;
}

.certificates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 24px;
    margin-bottom: 32px;
}

.certificate-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    border: 2px solid var(--gray-200);
    transition: all 0.3s ease;
    cursor: pointer;
}

.certificate-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    border-color: var(--primary-color);
}

.certificate-preview {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 40px 24px;
    text-align: center;
    position: relative;
    height: 200px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.certificate-icon {
    font-size: 64px;
    margin-bottom: 16px;
}

.certificate-number {
    font-size: 14px;
    font-weight: 700;
    opacity: 0.9;
    font-family: 'Courier New', monospace;
}

.certificate-content {
    padding: 24px;
}

.course-title {
    font-size: 20px;
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: 12px;
    line-height: 1.3;
}

.certificate-meta {
    display: flex;
    gap: 16px;
    margin-bottom: 16px;
    flex-wrap: wrap;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: var(--gray-600);
}

.meta-item i {
    color: var(--primary-color);
}

.certificate-actions {
    display: flex;
    gap: 8px;
}

.btn-view {
    flex: 1;
    padding: 12px;
    background: var(--primary-color);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    text-align: center;
}

.btn-view:hover {
    background: #4f46e5;
    transform: translateY(-2px);
}

.btn-download {
    flex: 1;
    padding: 12px;
    background: white;
    color: var(--primary-color);
    border: 2px solid var(--primary-color);
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    text-align: center;
}

.btn-download:hover {
    background: var(--primary-color);
    color: white;
}

.empty-state {
    background: white;
    border-radius: 16px;
    padding: 64px 32px;
    text-align: center;
}

.empty-icon {
    font-size: 80px;
    margin-bottom: 24px;
    opacity: 0.5;
}

.empty-title {
    font-size: 24px;
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: 12px;
}

.empty-text {
    font-size: 16px;
    color: var(--gray-600);
    margin-bottom: 24px;
}

.btn-primary {
    padding: 14px 32px;
    background: var(--primary-color);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    transition: all 0.2s;
}

.btn-primary:hover {
    background: #4f46e5;
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(99, 102, 241, 0.3);
}

.stats-row {
    display: flex;
    justify-content: center;
    gap: 48px;
    margin-top: 24px;
}

.stat-item {
    text-align: center;
}

.stat-value {
    font-size: 36px;
    font-weight: 800;
    display: block;
}

.stat-label {
    font-size: 14px;
    opacity: 0.9;
}

@media (max-width: 768px) {
    .certificates-grid {
        grid-template-columns: 1fr;
    }

    .page-header {
        padding: 32px 24px;
    }

    .page-title {
        font-size: 32px;
    }
}
</style>
@endpush

@section('content')
<div class="certificates-container">
    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-title">🎓 My Certificates</h1>
        <p class="page-subtitle">ใบประกาศนียบัตรของคุณ</p>

        <div class="stats-row">
            <div class="stat-item">
                <span class="stat-value">{{ $certificates->count() }}</span>
                <span class="stat-label">ใบประกาศทั้งหมด</span>
            </div>
        </div>
    </div>

    @if($certificates->count() > 0)
        <!-- Certificates Grid -->
        <div class="certificates-grid">
            @foreach($certificates as $certificate)
            <div class="certificate-card">
                <div class="certificate-preview">
                    <div class="certificate-icon">🎓</div>
                    <div class="certificate-number">{{ $certificate->certificate_number }}</div>
                </div>

                <div class="certificate-content">
                    <h3 class="course-title">{{ $certificate->course_title }}</h3>

                    <div class="certificate-meta">
                        <div class="meta-item">
                            <i class="fas fa-calendar"></i>
                            <span>{{ $certificate->formatted_issued_date }}</span>
                        </div>
                        @if($certificate->total_hours > 0)
                        <div class="meta-item">
                            <i class="fas fa-clock"></i>
                            <span>{{ $certificate->total_hours }} ชั่วโมง</span>
                        </div>
                        @endif
                        @if($certificate->quiz_score)
                        <div class="meta-item">
                            <i class="fas fa-star"></i>
                            <span>{{ number_format($certificate->quiz_score, 1) }}%</span>
                        </div>
                        @endif
                    </div>

                    <div class="certificate-actions">
                        <a href="{{ route('admin.certificates.show', $certificate->id) }}" class="btn-view">
                            <i class="fas fa-eye me-2"></i>View
                        </a>
                        <a href="{{ route('admin.certificates.download', $certificate->id) }}" class="btn-download">
                            <i class="fas fa-download me-2"></i>Download
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    @else
        <!-- Empty State -->
        <div class="empty-state">
            <div class="empty-icon">📜</div>
            <h2 class="empty-title">ยังไม่มีใบประกาศนียบัตร</h2>
            <p class="empty-text">เรียนจบคอร์สเพื่อรับใบประกาศนียบัตรของคุณ</p>
            <a href="{{ route('admin.learning-center.index') }}" class="btn-primary">
                <i class="fas fa-graduation-cap me-2"></i>เริ่มเรียน
            </a>
        </div>
    @endif
</div>
@endsection
