@extends('layouts.admin-v3')

@section('title', 'จัดการสมาชิก - Retention')

@section('content')
<div class="container-fluid retention-users-page">
    {{-- Header with Actions --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-header-users">
                <div class="header-left">
                    <a href="{{ route('admin.retention.index') }}" class="btn-back">
                        <i class="fas fa-arrow-left"></i>
                        <span>กลับหน้าหลัก</span>
                    </a>
                </div>
                <div class="header-center">
                    <div class="page-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="page-text">
                        <h1 class="page-title">จัดการสมาชิก</h1>
                        <p class="page-subtitle">Member Management</p>
                    </div>
                </div>
                <div class="header-right">
                    <span class="member-count">
                        <i class="fas fa-user-friends"></i>
                        {{ $users->total() }} สมาชิก
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="filter-card">
                <form method="GET" action="{{ route('admin.retention.users') }}" class="filter-form">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="filter-group">
                                <label class="filter-label">
                                    <i class="fas fa-search"></i>
                                    ค้นหา
                                </label>
                                <input type="text" name="search" class="filter-input"
                                       placeholder="ชื่อ หรือ อีเมล..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="filter-group">
                                <label class="filter-label">
                                    <i class="fas fa-filter"></i>
                                    สถานะ
                                </label>
                                <select name="status" class="filter-input">
                                    <option value="">ทั้งหมด</option>
                                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>
                                        ใช้งานอยู่
                                    </option>
                                    <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>
                                        หมดอายุ
                                    </option>
                                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>
                                        ไม่ใช้งาน
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="filter-group">
                                <label class="filter-label">
                                    <i class="fas fa-sort"></i>
                                    เรียงตาม
                                </label>
                                <select name="sort" class="filter-input">
                                    <option value="renewal_date" {{ request('sort') === 'renewal_date' ? 'selected' : '' }}>
                                        วันต่ออายุ
                                    </option>
                                    <option value="points" {{ request('sort') === 'points' ? 'selected' : '' }}>
                                        แต้ม
                                    </option>
                                    <option value="name" {{ request('sort') === 'name' ? 'selected' : '' }}>
                                        ชื่อ
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="filter-actions">
                                <button type="submit" class="btn-filter btn-filter-primary">
                                    <i class="fas fa-search"></i>
                                    <span>ค้นหา</span>
                                </button>
                                <a href="{{ route('admin.retention.users') }}" class="btn-filter btn-filter-secondary">
                                    <i class="fas fa-redo"></i>
                                    <span>รีเซ็ต</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Users Grid --}}
    <div class="row">
        <div class="col-12">
            <div class="users-container">
                @forelse($users as $userStatus)
                    @php
                        $user = $userStatus->user;
                        $days = $userStatus->daysRemaining();
                        $healthColor = $userStatus->getHealthColor();
                        $percentage = $userStatus->required_points > 0
                            ? min(100, ($userStatus->current_points / $userStatus->required_points) * 100)
                            : 0;
                    @endphp

                    <div class="user-card">
                        {{-- Card Header --}}
                        <div class="user-card-header">
                            <div class="user-avatar-section">
                                <div class="user-avatar">
                                    {{ substr($user->name, 0, 1) }}
                                </div>
                                <div class="user-info">
                                    <h3 class="user-name">
                                        <i class="fas fa-user"></i>
                                        {{ $user->name }}
                                    </h3>
                                    <p class="user-email">
                                        <i class="fas fa-envelope"></i>
                                        {{ $user->email }}
                                    </p>
                                </div>
                            </div>
                            <div class="user-status-badge">
                                @if($userStatus->status === 'active')
                                    <span class="status-badge status-active">
                                        <i class="fas fa-check-circle"></i>
                                        ใช้งานอยู่
                                    </span>
                                @elseif($userStatus->status === 'expired')
                                    <span class="status-badge status-expired">
                                        <i class="fas fa-times-circle"></i>
                                        หมดอายุ
                                    </span>
                                @else
                                    <span class="status-badge status-inactive">
                                        <i class="fas fa-minus-circle"></i>
                                        ไม่ใช้งาน
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Card Body - Stats Grid --}}
                        <div class="user-card-body">
                            <div class="stats-grid">
                                <div class="stat-item">
                                    <div class="stat-icon stat-icon-primary">
                                        <i class="fas fa-coins"></i>
                                    </div>
                                    <div class="stat-details">
                                        <span class="stat-label">แต้มปัจจุบัน</span>
                                        <span class="stat-value">{{ number_format($userStatus->current_points, 0) }}</span>
                                    </div>
                                </div>

                                <div class="stat-item">
                                    <div class="stat-icon stat-icon-warning">
                                        <i class="fas fa-bullseye"></i>
                                    </div>
                                    <div class="stat-details">
                                        <span class="stat-label">แต้มที่ต้องการ</span>
                                        <span class="stat-value">{{ number_format($userStatus->required_points, 0) }}</span>
                                    </div>
                                </div>

                                <div class="stat-item">
                                    <div class="stat-icon stat-icon-success">
                                        <i class="fas fa-calendar-check"></i>
                                    </div>
                                    <div class="stat-details">
                                        <span class="stat-label">วันต่ออายุ</span>
                                        <span class="stat-value">
                                            {{ $userStatus->next_renewal_date ? $userStatus->next_renewal_date->format('d/m/Y') : '-' }}
                                        </span>
                                    </div>
                                </div>

                                <div class="stat-item">
                                    <div class="stat-icon stat-icon-danger">
                                        <i class="fas fa-hourglass-half"></i>
                                    </div>
                                    <div class="stat-details">
                                        <span class="stat-label">วันที่เหลือ</span>
                                        <span class="stat-value stat-value-{{ $healthColor === 'green' ? 'success' : ($healthColor === 'yellow' ? 'warning' : 'danger') }}">
                                            {{ $days }} วัน
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {{-- Progress Bar --}}
                            <div class="progress-section">
                                <div class="progress-header">
                                    <span class="progress-label">
                                        <i class="fas fa-tasks"></i>
                                        ความสมบูรณ์
                                    </span>
                                    <span class="progress-percentage">{{ number_format($percentage, 1) }}%</span>
                                </div>
                                <div class="progress-bar-wrapper">
                                    <div class="progress-bar-fill {{ $percentage >= 100 ? 'progress-complete' : ($percentage >= 50 ? 'progress-half' : 'progress-low') }}"
                                         style="width: {{ min(100, $percentage) }}%">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Card Footer --}}
                        <div class="user-card-footer">
                            <a href="{{ route('admin.retention.users.show', $user->id) }}" class="btn-view-details">
                                <i class="fas fa-eye"></i>
                                <span>ดูรายละเอียด</span>
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="empty-state">
                        <div class="empty-state-content">
                            <div class="empty-icon">
                                <i class="fas fa-users-slash"></i>
                            </div>
                            <h3 class="empty-title">ไม่พบข้อมูลสมาชิก</h3>
                            <p class="empty-description">ไม่มีสมาชิกที่ตรงกับเงื่อนไขการค้นหา</p>
                            <a href="{{ route('admin.retention.users') }}" class="btn-empty-action">
                                <i class="fas fa-redo"></i>
                                รีเซ็ตตัวกรอง
                            </a>
                        </div>
                    </div>
                @endforelse
            </div>

            {{-- Pagination --}}
            @if($users->hasPages())
            <div class="pagination-wrapper">
                {{ $users->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

<style>
/* Global Styles */
.retention-users-page {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: 100vh;
    padding: 30px 0;
}

/* Page Header */
.page-header-users {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    position: relative;
    overflow: hidden;
}

.page-header-users::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
    border-radius: 50%;
}

.header-left,
.header-center,
.header-right {
    position: relative;
    z-index: 1;
}

.header-center {
    display: flex;
    align-items: center;
    gap: 15px;
    flex: 1;
    justify-content: center;
}

.page-icon {
    width: 60px;
    height: 60px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    color: #fff;
}

.page-text .page-title {
    color: #fff;
    font-size: 28px;
    font-weight: 700;
    margin: 0;
}

.page-text .page-subtitle {
    color: rgba(255, 255, 255, 0.9);
    font-size: 14px;
    margin: 3px 0 0 0;
}

.btn-back {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    color: #fff;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 12px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
}

.btn-back:hover {
    background: rgba(255, 255, 255, 0.3);
    border-color: rgba(255, 255, 255, 0.5);
    transform: translateX(-5px);
    color: #fff;
}

.member-count {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    color: #fff;
    font-size: 14px;
    font-weight: 600;
}

/* Filter Card */
.filter-card {
    background: #fff;
    border-radius: 16px;
    padding: 25px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.filter-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 600;
    color: #374151;
}

.filter-label i {
    color: #667eea;
}

.filter-input {
    width: 100%;
    padding: 10px 14px;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    font-size: 14px;
    transition: all 0.3s ease;
    background: #f9fafb;
}

.filter-input:focus {
    outline: none;
    border-color: #667eea;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.filter-actions {
    display: flex;
    gap: 10px;
    align-items: flex-end;
    height: 100%;
}

.btn-filter {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 18px;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    flex: 1;
    justify-content: center;
}

.btn-filter-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-filter-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.btn-filter-secondary {
    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
    color: #374151;
}

.btn-filter-secondary:hover {
    background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%);
    transform: translateY(-2px);
}

/* Users Container */
.users-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 25px;
}

/* User Card */
.user-card {
    background: #fff;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 2px solid transparent;
}

.user-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
    border-color: #667eea;
}

/* Card Header */
.user-card-header {
    padding: 20px;
    background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
}

.user-avatar-section {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
}

.user-avatar {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 20px;
    font-weight: 700;
    text-transform: uppercase;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.user-info {
    flex: 1;
    min-width: 0;
}

.user-name {
    font-size: 16px;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 4px 0;
    display: flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-name i {
    font-size: 14px;
    color: #667eea;
    flex-shrink: 0;
}

.user-email {
    font-size: 13px;
    color: #6b7280;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-email i {
    font-size: 12px;
    flex-shrink: 0;
}

.user-status-badge {
    flex-shrink: 0;
}

/* Status Badges */
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
}

.status-active {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    color: #065f46;
}

.status-expired {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    color: #991b1b;
}

.status-inactive {
    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
    color: #374151;
}

/* Card Body */
.user-card-body {
    padding: 20px;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    margin-bottom: 20px;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px;
    background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
    border-radius: 12px;
    transition: all 0.3s ease;
}

.stat-item:hover {
    transform: translateX(3px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    color: #fff;
    flex-shrink: 0;
}

.stat-icon-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.stat-icon-warning {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.stat-icon-success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.stat-icon-danger {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.stat-details {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
    flex: 1;
}

.stat-label {
    font-size: 11px;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.stat-value {
    font-size: 15px;
    font-weight: 700;
    color: #1f2937;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.stat-value-success { color: #059669; }
.stat-value-warning { color: #d97706; }
.stat-value-danger { color: #dc2626; }

/* Progress Section */
.progress-section {
    margin-top: 15px;
}

.progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.progress-label {
    font-size: 12px;
    font-weight: 600;
    color: #6b7280;
    display: flex;
    align-items: center;
    gap: 6px;
}

.progress-label i {
    color: #667eea;
}

.progress-percentage {
    font-size: 13px;
    font-weight: 700;
    color: #1f2937;
}

.progress-bar-wrapper {
    height: 10px;
    background: #e5e7eb;
    border-radius: 10px;
    overflow: hidden;
}

.progress-bar-fill {
    height: 100%;
    border-radius: 10px;
    transition: width 0.6s ease;
    position: relative;
}

.progress-complete {
    background: linear-gradient(90deg, #10b981 0%, #059669 100%);
}

.progress-half {
    background: linear-gradient(90deg, #f59e0b 0%, #d97706 100%);
}

.progress-low {
    background: linear-gradient(90deg, #ef4444 0%, #dc2626 100%);
}

/* Card Footer */
.user-card-footer {
    padding: 15px 20px;
    background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
    border-top: 2px solid #e5e7eb;
}

.btn-view-details {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    padding: 12px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-view-details:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    color: #fff;
}

/* Empty State */
.empty-state {
    grid-column: 1 / -1;
    padding: 60px 20px;
    text-align: center;
}

.empty-state-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 20px;
    max-width: 400px;
    margin: 0 auto;
}

.empty-icon {
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 48px;
    color: #9ca3af;
}

.empty-title {
    font-size: 24px;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}

.empty-description {
    font-size: 16px;
    color: #6b7280;
    margin: 0;
}

.btn-empty-action {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    border: none;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-empty-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    color: #fff;
}

/* Pagination */
.pagination-wrapper {
    margin-top: 30px;
    padding-top: 25px;
    border-top: 2px solid rgba(255, 255, 255, 0.3);
    display: flex;
    justify-content: center;
}

/* Responsive Design */
@media (max-width: 1400px) {
    .users-container {
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    }
}

@media (max-width: 992px) {
    .users-container {
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    }

    .page-header-users {
        flex-direction: column;
        text-align: center;
    }

    .header-center {
        order: -1;
    }
}

@media (max-width: 768px) {
    .users-container {
        grid-template-columns: 1fr;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }

    .filter-actions {
        flex-direction: column;
    }

    .btn-filter {
        width: 100%;
    }

    .page-header-users {
        padding: 20px;
    }

    .page-icon {
        width: 50px;
        height: 50px;
        font-size: 24px;
    }

    .page-title {
        font-size: 24px;
    }
}

@media (max-width: 480px) {
    .user-card-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .user-status-badge {
        width: 100%;
    }

    .status-badge {
        width: 100%;
        justify-content: center;
    }
}
</style>

{{-- เพิ่ม FontAwesome CDN ถ้ายังไม่มี --}}
@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
@endpush
@endsection
