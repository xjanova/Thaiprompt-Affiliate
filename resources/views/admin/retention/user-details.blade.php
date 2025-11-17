@extends('layouts.admin-v3')

@section('title', 'รายละเอียดสมาชิก - Retention')

@section('content')
<div class="container-fluid retention-user-details-page">
    {{-- Back Button & Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-navigation">
                <a href="{{ route('admin.retention.index') }}" class="btn-back">
                    <i class="fas fa-arrow-left"></i>
                    <span>กลับหน้าหลัก</span>
                </a>
            </div>
        </div>
    </div>

    {{-- User Profile Header --}}
    <div class="row mb-5">
        <div class="col-12">
            <div class="user-profile-card">
                <div class="profile-background"></div>
                <div class="profile-content">
                    <div class="profile-avatar">
                        {{ substr($user->name, 0, 1) }}
                    </div>
                    <div class="profile-info">
                        <h2 class="profile-name">
                            <i class="fas fa-user"></i>
                            {{ $user->name }}
                        </h2>
                        <p class="profile-email">
                            <i class="fas fa-envelope"></i>
                            {{ $user->email }}
                        </p>
                        <div class="profile-meta">
                            <span class="meta-item">
                                <i class="fas fa-id-badge"></i>
                                ID: {{ $user->id }}
                            </span>
                            <span class="meta-item">
                                <i class="fas fa-calendar-plus"></i>
                                สมัครเมื่อ: {{ $user->created_at->format('d/m/Y') }}
                            </span>
                        </div>
                    </div>
                    @if($status)
                    <div class="profile-status">
                        @if($status->status === 'active')
                            <span class="status-badge-large status-active">
                                <i class="fas fa-check-circle"></i>
                                ใช้งานอยู่
                            </span>
                        @elseif($status->status === 'expired')
                            <span class="status-badge-large status-expired">
                                <i class="fas fa-times-circle"></i>
                                หมดอายุ
                            </span>
                        @else
                            <span class="status-badge-large status-inactive">
                                <i class="fas fa-minus-circle"></i>
                                ไม่ใช้งาน
                            </span>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Statistics Cards --}}
    @if($statistics)
    <div class="row g-4 mb-5">
        <div class="col-md-6 col-lg-3">
            <div class="info-card info-card-primary">
                <div class="info-icon">
                    <i class="fas fa-coins"></i>
                </div>
                <div class="info-content">
                    <h4 class="info-label">แต้มทั้งหมด</h4>
                    <h3 class="info-value">{{ number_format($statistics['total_points'] ?? 0, 2) }}</h3>
                    <p class="info-subtitle">Total Points</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="info-card info-card-success">
                <div class="info-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="info-content">
                    <h4 class="info-label">แต้มเดือนนี้</h4>
                    <h3 class="info-value">{{ number_format($statistics['current_month_points'] ?? 0, 2) }}</h3>
                    <p class="info-subtitle">This Month</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="info-card info-card-warning">
                <div class="info-icon">
                    <i class="fas fa-sync-alt"></i>
                </div>
                <div class="info-content">
                    <h4 class="info-label">ต่ออายุสำเร็จ</h4>
                    <h3 class="info-value">{{ $statistics['successful_renewals'] ?? 0 }}</h3>
                    <p class="info-subtitle">Success Count</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="info-card info-card-danger">
                <div class="info-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="info-content">
                    <h4 class="info-label">ต่ออายุล้มเหลว</h4>
                    <h3 class="info-value">{{ $statistics['failed_renewals'] ?? 0 }}</h3>
                    <p class="info-subtitle">Failed Count</p>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Current Status --}}
    @if($status)
    <div class="row mb-5">
        <div class="col-12">
            <div class="modern-card">
                <div class="modern-card-header">
                    <div class="card-header-content">
                        <div class="card-icon">
                            <i class="fas fa-heartbeat"></i>
                        </div>
                        <div class="card-text">
                            <h3 class="card-title">สถานะ Retention ปัจจุบัน</h3>
                            <p class="card-subtitle">Current Retention Status</p>
                        </div>
                    </div>
                </div>
                <div class="modern-card-body">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="detail-item">
                                <div class="detail-icon detail-icon-primary">
                                    <i class="fas fa-coins"></i>
                                </div>
                                <div class="detail-content">
                                    <label class="detail-label">แต้มปัจจุบัน</label>
                                    <h4 class="detail-value">{{ number_format($status->current_points, 2) }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-item">
                                <div class="detail-icon detail-icon-warning">
                                    <i class="fas fa-bullseye"></i>
                                </div>
                                <div class="detail-content">
                                    <label class="detail-label">แต้มที่ต้องการ</label>
                                    <h4 class="detail-value">{{ number_format($status->required_points, 2) }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-item">
                                <div class="detail-icon detail-icon-info">
                                    <i class="fas fa-percentage"></i>
                                </div>
                                <div class="detail-content">
                                    <label class="detail-label">ความสมบูรณ์</label>
                                    <h4 class="detail-value">
                                        @php
                                            $percentage = $status->required_points > 0
                                                ? min(100, ($status->current_points / $status->required_points) * 100)
                                                : 0;
                                        @endphp
                                        {{ number_format($percentage, 1) }}%
                                    </h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-item">
                                <div class="detail-icon detail-icon-success">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div class="detail-content">
                                    <label class="detail-label">วันต่ออายุถัดไป</label>
                                    <h4 class="detail-value">
                                        {{ $status->next_renewal_date ? $status->next_renewal_date->format('d/m/Y') : '-' }}
                                    </h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-item">
                                <div class="detail-icon detail-icon-danger">
                                    <i class="fas fa-hourglass-half"></i>
                                </div>
                                <div class="detail-content">
                                    <label class="detail-label">วันที่เหลือ</label>
                                    <h4 class="detail-value">
                                        @php
                                            $days = $status->daysRemaining();
                                        @endphp
                                        {{ $days }} วัน
                                    </h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-item">
                                <div class="detail-icon detail-icon-purple">
                                    <i class="fas fa-info-circle"></i>
                                </div>
                                <div class="detail-content">
                                    <label class="detail-label">สถานะ</label>
                                    <h4 class="detail-value">
                                        @if($status->status === 'active')
                                            <span class="text-success">ใช้งานอยู่</span>
                                        @elseif($status->status === 'expired')
                                            <span class="text-danger">หมดอายุ</span>
                                        @else
                                            <span class="text-secondary">ไม่ใช้งาน</span>
                                        @endif
                                    </h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Progress Bar --}}
                    <div class="progress-section mt-4">
                        <label class="progress-label">
                            <i class="fas fa-tasks"></i>
                            ความคืบหน้า
                        </label>
                        <div class="custom-progress">
                            <div class="custom-progress-bar" style="width: {{ min(100, $percentage) }}%">
                                <span class="progress-text">{{ number_format($percentage, 1) }}%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Renewal History --}}
    <div class="row">
        <div class="col-12">
            <div class="modern-card">
                <div class="modern-card-header">
                    <div class="card-header-content">
                        <div class="card-icon">
                            <i class="fas fa-history"></i>
                        </div>
                        <div class="card-text">
                            <h3 class="card-title">ประวัติการต่ออายุ</h3>
                            <p class="card-subtitle">Renewal History</p>
                        </div>
                    </div>
                </div>
                <div class="modern-card-body">
                    @if($history->count() > 0)
                    <div class="table-responsive">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>
                                        <i class="fas fa-calendar"></i>
                                        รอบเดือน
                                    </th>
                                    <th>
                                        <i class="fas fa-coins"></i>
                                        แต้มที่ได้
                                    </th>
                                    <th>
                                        <i class="fas fa-bullseye"></i>
                                        แต้มที่ต้องการ
                                    </th>
                                    <th>
                                        <i class="fas fa-balance-scale"></i>
                                        ผลต่าง
                                    </th>
                                    <th>
                                        <i class="fas fa-info-circle"></i>
                                        สถานะ
                                    </th>
                                    <th>
                                        <i class="fas fa-wrench"></i>
                                        การซ่อม
                                    </th>
                                    <th>
                                        <i class="fas fa-clock"></i>
                                        วันที่
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($history as $record)
                                <tr>
                                    <td>
                                        <span class="month-badge">
                                            <i class="fas fa-calendar-alt"></i>
                                            {{ $record->period_month }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="points-badge">
                                            <i class="fas fa-coins"></i>
                                            {{ number_format($record->points_earned, 2) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="points-badge points-required">
                                            <i class="fas fa-bullseye"></i>
                                            {{ number_format($record->points_required, 2) }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $difference = $record->points_earned - $record->points_required;
                                        @endphp
                                        <span class="difference-badge {{ $difference >= 0 ? 'difference-positive' : 'difference-negative' }}">
                                            <i class="fas fa-{{ $difference >= 0 ? 'arrow-up' : 'arrow-down' }}"></i>
                                            {{ number_format(abs($difference), 2) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($record->renewal_successful)
                                            <span class="status-badge status-active">
                                                <i class="fas fa-check-circle"></i>
                                                สำเร็จ
                                            </span>
                                        @else
                                            <span class="status-badge status-expired">
                                                <i class="fas fa-times-circle"></i>
                                                ล้มเหลว
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($record->repair_cost > 0)
                                            <span class="repair-badge">
                                                <i class="fas fa-tools"></i>
                                                ซ่อม {{ number_format($record->repair_cost, 2) }} บาท
                                            </span>
                                        @else
                                            <span class="text-muted">
                                                <i class="fas fa-minus"></i>
                                                -
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="date-badge">
                                            <i class="fas fa-clock"></i>
                                            {{ $record->created_at->format('d/m/Y H:i') }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    @if($history->hasPages())
                    <div class="pagination-wrapper">
                        {{ $history->links() }}
                    </div>
                    @endif
                    @else
                    <div class="empty-state">
                        <div class="empty-state-content">
                            <i class="fas fa-inbox"></i>
                            <p>ยังไม่มีประวัติการต่ออายุ</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Global Styles */
.retention-user-details-page {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: 100vh;
    padding: 30px 0;
}

/* Navigation */
.page-navigation {
    margin-bottom: 20px;
}

.btn-back {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 12px 24px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    border: none;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.btn-back:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 25px rgba(102, 126, 234, 0.4);
    color: #fff;
}

/* User Profile Card */
.user-profile-card {
    background: #fff;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    position: relative;
}

.profile-background {
    height: 150px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    position: relative;
}

.profile-background::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 400px;
    height: 400px;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
    border-radius: 50%;
}

.profile-content {
    padding: 0 30px 30px;
    display: flex;
    align-items: flex-end;
    gap: 25px;
    margin-top: -60px;
    position: relative;
}

.profile-avatar {
    width: 120px;
    height: 120px;
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 48px;
    font-weight: 700;
    text-transform: uppercase;
    border: 5px solid #fff;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    flex-shrink: 0;
}

.profile-info {
    flex: 1;
    padding-top: 60px;
}

.profile-name {
    font-size: 28px;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 8px 0;
    display: flex;
    align-items: center;
    gap: 12px;
}

.profile-name i {
    font-size: 24px;
    color: #667eea;
}

.profile-email {
    font-size: 16px;
    color: #6b7280;
    margin: 0 0 15px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.profile-email i {
    color: #9ca3af;
}

.profile-meta {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.meta-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
    border-radius: 10px;
    font-size: 13px;
    color: #374151;
    font-weight: 500;
}

.meta-item i {
    color: #667eea;
}

.profile-status {
    padding-top: 60px;
    flex-shrink: 0;
}

.status-badge-large {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 14px 24px;
    border-radius: 14px;
    font-size: 16px;
    font-weight: 700;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.status-badge-large.status-active {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    color: #065f46;
}

.status-badge-large.status-expired {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    color: #991b1b;
}

.status-badge-large.status-inactive {
    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
    color: #374151;
}

/* Info Cards */
.info-card {
    background: #fff;
    border-radius: 16px;
    padding: 25px;
    display: flex;
    align-items: center;
    gap: 20px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    height: 100%;
    position: relative;
    overflow: hidden;
}

.info-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, transparent 0%, rgba(255, 255, 255, 0.1) 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.info-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
}

.info-card:hover::before {
    opacity: 1;
}

.info-card-primary { border-left: 4px solid #667eea; }
.info-card-success { border-left: 4px solid #10b981; }
.info-card-warning { border-left: 4px solid #f59e0b; }
.info-card-danger { border-left: 4px solid #ef4444; }

.info-icon {
    width: 60px;
    height: 60px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 26px;
    flex-shrink: 0;
}

.info-card-primary .info-icon {
    background: linear-gradient(135deg, #ddd6fe 0%, #c4b5fd 100%);
    color: #5b21b6;
}

.info-card-success .info-icon {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    color: #065f46;
}

.info-card-warning .info-icon {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    color: #78350f;
}

.info-card-danger .info-icon {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    color: #991b1b;
}

.info-content {
    flex: 1;
}

.info-label {
    font-size: 13px;
    font-weight: 600;
    color: #6b7280;
    margin: 0 0 5px 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-value {
    font-size: 28px;
    font-weight: 800;
    color: #1f2937;
    margin: 0 0 5px 0;
}

.info-subtitle {
    font-size: 12px;
    color: #9ca3af;
    margin: 0;
}

/* Modern Cards (reuse from index) */
.modern-card {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
    border: none;
    overflow: hidden;
    transition: all 0.3s ease;
}

.modern-card:hover {
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12);
}

.modern-card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 25px 30px;
    position: relative;
    overflow: hidden;
}

.modern-card-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 200px;
    height: 200px;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
    border-radius: 50%;
}

.card-header-content {
    display: flex;
    align-items: center;
    gap: 15px;
    position: relative;
    z-index: 1;
}

.card-icon {
    width: 50px;
    height: 50px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    color: #fff;
}

.card-text .card-title {
    color: #fff;
    font-size: 20px;
    font-weight: 700;
    margin: 0;
}

.card-text .card-subtitle {
    color: rgba(255, 255, 255, 0.85);
    font-size: 13px;
    margin: 3px 0 0 0;
}

.modern-card-body {
    padding: 30px;
}

/* Detail Items */
.detail-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
    border-radius: 14px;
    transition: all 0.3s ease;
}

.detail-item:hover {
    transform: translateX(5px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
}

.detail-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: #fff;
    flex-shrink: 0;
}

.detail-icon-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.detail-icon-success { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
.detail-icon-warning { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
.detail-icon-danger { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
.detail-icon-info { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }
.detail-icon-purple { background: linear-gradient(135deg, #a855f7 0%, #9333ea 100%); }

.detail-content {
    flex: 1;
}

.detail-label {
    font-size: 12px;
    font-weight: 600;
    color: #6b7280;
    margin: 0 0 5px 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.detail-value {
    font-size: 20px;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}

/* Progress Section */
.progress-section {
    padding: 25px;
    background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
    border-radius: 14px;
}

.progress-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    font-weight: 700;
    color: #374151;
    margin-bottom: 12px;
}

.custom-progress {
    height: 40px;
    background: #e5e7eb;
    border-radius: 20px;
    overflow: hidden;
    position: relative;
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
}

.custom-progress-bar {
    height: 100%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding-right: 15px;
    transition: width 0.6s ease;
    position: relative;
}

.custom-progress-bar::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    animation: shimmer 2s infinite;
}

@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

.progress-text {
    font-size: 14px;
    font-weight: 700;
    color: #fff;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
    position: relative;
    z-index: 1;
}

/* Modern Table */
.modern-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.modern-table thead tr {
    background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
}

.modern-table thead th {
    padding: 16px 20px;
    text-align: left;
    font-size: 13px;
    font-weight: 700;
    color: #374151;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid #e5e7eb;
}

.modern-table thead th i {
    margin-right: 6px;
    color: #667eea;
}

.modern-table thead th:first-child {
    border-radius: 12px 0 0 0;
}

.modern-table thead th:last-child {
    border-radius: 0 12px 0 0;
}

.modern-table tbody tr {
    background: #fff;
    transition: all 0.3s ease;
}

.modern-table tbody tr:hover {
    background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
    transform: scale(1.01);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.modern-table tbody td {
    padding: 18px 20px;
    font-size: 14px;
    color: #374151;
    border-bottom: 1px solid #f3f4f6;
}

/* Badges */
.month-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    color: #3730a3;
}

.points-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    color: #78350f;
}

.points-required {
    background: linear-gradient(135deg, #ddd6fe 0%, #c4b5fd 100%);
    color: #5b21b6;
}

.difference-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 700;
}

.difference-positive {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    color: #065f46;
}

.difference-negative {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    color: #991b1b;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
}

.status-active {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    color: #065f46;
}

.status-expired {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    color: #991b1b;
}

.repair-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: linear-gradient(135deg, #fed7aa 0%, #fdba74 100%);
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    color: #7c2d12;
}

.date-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    color: #1e40af;
}

/* Empty State */
.empty-state {
    padding: 60px 20px;
    text-align: center;
}

.empty-state-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 15px;
}

.empty-state-content i {
    font-size: 48px;
    color: #d1d5db;
}

.empty-state-content p {
    font-size: 16px;
    color: #6b7280;
    margin: 0;
}

/* Pagination */
.pagination-wrapper {
    margin-top: 25px;
    padding-top: 20px;
    border-top: 2px solid #f3f4f6;
    display: flex;
    justify-content: center;
}

/* Responsive */
@media (max-width: 768px) {
    .profile-content {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }

    .profile-info {
        padding-top: 20px;
    }

    .profile-status {
        padding-top: 0;
    }

    .profile-name {
        justify-content: center;
    }

    .profile-email {
        justify-content: center;
    }

    .profile-meta {
        justify-content: center;
    }

    .detail-item {
        flex-direction: column;
        text-align: center;
    }
}
</style>
@endsection
