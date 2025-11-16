@extends('layouts.user-arrow-x')

@section('title', 'ระบบรักษายอด - Membership Retention')

@section('content')
<div class="container-fluid retention-dashboard-page">
    {{-- Hero Header with Life Power Widget --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="dashboard-hero">
                <div class="hero-background">
                    <div class="gradient-orb orb-1"></div>
                    <div class="gradient-orb orb-2"></div>
                    <div class="gradient-orb orb-3"></div>
                </div>
                <div class="hero-content">
                    <div class="hero-title-section">
                        <div class="title-icon">
                            <i class="fas fa-heartbeat"></i>
                        </div>
                        <div class="title-content">
                            <h1 class="hero-title">ระบบรักษายอด</h1>
                            <p class="hero-subtitle">Membership Retention System</p>
                        </div>
                    </div>
                    <div class="hero-actions">
                        <a href="{{ route('user.retention.how-it-works') }}" class="hero-action-btn">
                            <i class="fas fa-book-open"></i>
                            <span>คู่มือการใช้งาน</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Life Power Widget --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="widget-wrapper">
                @include('components.life-power-widget')
            </div>
        </div>
    </div>

    {{-- Enhanced Statistics Cards --}}
    <div class="row g-4 mb-5">
        <div class="col-12">
            <div class="section-header">
                <div class="section-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="section-title-group">
                    <h2 class="section-title">สถิติการรักษายอด</h2>
                    <p class="section-subtitle">ภาพรวมประสิทธิภาพของคุณ</p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="modern-stat-card card-primary">
                <div class="stat-card-header">
                    <div class="stat-card-icon">
                        <div class="icon-wrapper">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="icon-glow"></div>
                    </div>
                </div>
                <div class="stat-card-body">
                    <div class="stat-number">{{ $statistics['consecutive_months'] }}</div>
                    <div class="stat-label">เดือนติดต่อกัน</div>
                    <div class="stat-description">Consecutive Months</div>
                </div>
                <div class="stat-card-footer">
                    <div class="progress-indicator">
                        <div class="progress-bar-mini" style="width: {{ min(100, $statistics['consecutive_months'] * 10) }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="modern-stat-card card-success">
                <div class="stat-card-header">
                    <div class="stat-card-icon">
                        <div class="icon-wrapper">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="icon-glow"></div>
                    </div>
                </div>
                <div class="stat-card-body">
                    <div class="stat-number">{{ $statistics['achieved_count'] }}</div>
                    <div class="stat-label">ผ่านเกณฑ์</div>
                    <div class="stat-description">Achieved</div>
                </div>
                <div class="stat-card-footer">
                    <div class="stat-badge badge-success">
                        <i class="fas fa-arrow-up"></i>
                        <span>เยี่ยมมาก!</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="modern-stat-card card-danger">
                <div class="stat-card-header">
                    <div class="stat-card-icon">
                        <div class="icon-wrapper">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="icon-glow"></div>
                    </div>
                </div>
                <div class="stat-card-body">
                    <div class="stat-number">{{ $statistics['failed_count'] }}</div>
                    <div class="stat-label">ไม่ผ่านเกณฑ์</div>
                    <div class="stat-description">Failed</div>
                </div>
                <div class="stat-card-footer">
                    @if($statistics['failed_count'] > 0)
                    <a href="{{ route('user.retention.repair') }}" class="stat-action-link">
                        <i class="fas fa-tools"></i>
                        <span>ซ่อมสิทธิ์</span>
                    </a>
                    @else
                    <div class="stat-badge badge-muted">
                        <i class="fas fa-shield-alt"></i>
                        <span>ปลอดภัย</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="modern-stat-card card-warning">
                <div class="stat-card-header">
                    <div class="stat-card-icon">
                        <div class="icon-wrapper">
                            <i class="fas fa-tools"></i>
                        </div>
                        <div class="icon-glow"></div>
                    </div>
                </div>
                <div class="stat-card-body">
                    <div class="stat-number">{{ $statistics['repair_count'] }}</div>
                    <div class="stat-label">ซ่อมสิทธิ์แล้ว</div>
                    <div class="stat-description">Repaired</div>
                </div>
                <div class="stat-card-footer">
                    <div class="stat-badge badge-warning">
                        <i class="fas fa-wrench"></i>
                        <span>{{ $statistics['repair_count'] }} ครั้ง</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions Section --}}
    <div class="row g-4 mb-5">
        <div class="col-12">
            <div class="section-header">
                <div class="section-icon">
                    <i class="fas fa-rocket"></i>
                </div>
                <div class="section-title-group">
                    <h2 class="section-title">ดำเนินการด่วน</h2>
                    <p class="section-subtitle">เครื่องมือช่วยเหลือของคุณ</p>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6">
            <a href="{{ route('user.retention.repair') }}" class="action-card">
                <div class="action-card-icon action-icon-warning">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="action-card-content">
                    <h3 class="action-card-title">ซ่อมสิทธิ์</h3>
                    <p class="action-card-description">ซื้อซ่อมเดือนที่ไม่ผ่านเกณฑ์เพื่อกู้คืนสิทธิ์</p>
                    <div class="action-card-badge badge-warning">
                        <i class="fas fa-wrench"></i>
                        <span>Repair Rights</span>
                    </div>
                </div>
                <div class="action-card-arrow">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>
        </div>

        <div class="col-lg-4 col-md-6">
            <a href="{{ route('user.retention.advance-renewal') }}" class="action-card">
                <div class="action-card-icon action-icon-success">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <div class="action-card-content">
                    <h3 class="action-card-title">เติมวันล่วงหน้า</h3>
                    <p class="action-card-description">ซื้อสิทธิ์ล่วงหน้าพร้อมส่วนลดพิเศษ 10%</p>
                    <div class="action-card-badge badge-success">
                        <i class="fas fa-percentage"></i>
                        <span>10% Discount</span>
                    </div>
                </div>
                <div class="action-card-arrow">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>
        </div>

        <div class="col-lg-4 col-md-6">
            <a href="{{ route('user.retention.how-it-works') }}" class="action-card">
                <div class="action-card-icon action-icon-info">
                    <i class="fas fa-book-open"></i>
                </div>
                <div class="action-card-content">
                    <h3 class="action-card-title">คู่มือการใช้งาน</h3>
                    <p class="action-card-description">เรียนรู้วิธีการทำงานของระบบรักษายอด</p>
                    <div class="action-card-badge badge-info">
                        <i class="fas fa-graduation-cap"></i>
                        <span>Learn More</span>
                    </div>
                </div>
                <div class="action-card-arrow">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>
        </div>
    </div>

    {{-- History Timeline Section --}}
    <div class="row">
        <div class="col-12">
            <div class="modern-card">
                <div class="modern-card-header">
                    <div class="card-header-left">
                        <div class="card-header-icon">
                            <i class="fas fa-history"></i>
                        </div>
                        <div class="card-header-title-group">
                            <h3 class="card-header-title">ประวัติการรักษายอด</h3>
                            <p class="card-header-subtitle">บันทึกผลการดำเนินงาน</p>
                        </div>
                    </div>
                    <div class="card-header-right">
                        <div class="filter-tabs">
                            <button class="filter-tab active" data-filter="all">
                                <i class="fas fa-list"></i>
                                ทั้งหมด
                            </button>
                            <button class="filter-tab" data-filter="achieved">
                                <i class="fas fa-check"></i>
                                ผ่าน
                            </button>
                            <button class="filter-tab" data-filter="failed">
                                <i class="fas fa-times"></i>
                                ไม่ผ่าน
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modern-card-body">
                    <div class="history-timeline">
                        @forelse($history as $index => $record)
                        <div class="timeline-item" data-status="{{ $record->status }}">
                            <div class="timeline-marker">
                                <div class="marker-dot marker-dot-{{ $record->status === 'achieved' ? 'success' : ($record->status === 'failed' ? 'danger' : 'warning') }}">
                                    @if($record->status === 'achieved')
                                        <i class="fas fa-check"></i>
                                    @elseif($record->status === 'failed')
                                        <i class="fas fa-times"></i>
                                    @else
                                        <i class="fas fa-clock"></i>
                                    @endif
                                </div>
                                @if($index < count($history) - 1)
                                <div class="marker-line"></div>
                                @endif
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-card">
                                    <div class="timeline-card-header">
                                        <div class="timeline-date">
                                            <i class="fas fa-calendar"></i>
                                            <span>{{ $record->period_month }}</span>
                                        </div>
                                        <div class="timeline-status">
                                            @if($record->status === 'achieved')
                                                <span class="status-badge status-success">
                                                    <i class="fas fa-check-circle"></i>
                                                    ผ่านเกณฑ์
                                                </span>
                                            @elseif($record->status === 'failed')
                                                <span class="status-badge status-danger">
                                                    <i class="fas fa-times-circle"></i>
                                                    ไม่ผ่านเกณฑ์
                                                </span>
                                            @else
                                                <span class="status-badge status-warning">
                                                    <i class="fas fa-clock"></i>
                                                    รอดำเนินการ
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="timeline-card-body">
                                        <div class="points-display">
                                            <div class="points-item">
                                                <div class="points-icon points-icon-target">
                                                    <i class="fas fa-bullseye"></i>
                                                </div>
                                                <div class="points-details">
                                                    <span class="points-label">เป้าหมาย</span>
                                                    <span class="points-value">{{ number_format($record->required_points, 0) }}</span>
                                                </div>
                                            </div>
                                            <div class="points-divider">
                                                <i class="fas fa-arrow-right"></i>
                                            </div>
                                            <div class="points-item">
                                                <div class="points-icon points-icon-earned">
                                                    <i class="fas fa-star"></i>
                                                </div>
                                                <div class="points-details">
                                                    <span class="points-label">ทำได้</span>
                                                    <span class="points-value">{{ number_format($record->earned_points, 0) }}</span>
                                                </div>
                                            </div>
                                            <div class="points-divider">
                                                <i class="fas fa-equals"></i>
                                            </div>
                                            <div class="points-item">
                                                <div class="points-icon points-icon-percentage">
                                                    <i class="fas fa-percentage"></i>
                                                </div>
                                                <div class="points-details">
                                                    <span class="points-label">เปอร์เซ็นต์</span>
                                                    <span class="points-value">{{ number_format(($record->earned_points / max($record->required_points, 1)) * 100, 1) }}%</span>
                                                </div>
                                            </div>
                                        </div>
                                        @if($record->status === 'achieved')
                                        <div class="timeline-progress">
                                            <div class="progress-bar-timeline">
                                                <div class="progress-fill-timeline progress-success" style="width: {{ min(100, ($record->earned_points / max($record->required_points, 1)) * 100) }}%">
                                                    <div class="progress-shimmer"></div>
                                                </div>
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                    @if($record->achieved_at)
                                    <div class="timeline-card-footer">
                                        <div class="achievement-date">
                                            <i class="fas fa-trophy"></i>
                                            <span>บรรลุเมื่อ: {{ $record->achieved_at->format('d/m/Y H:i') }}</span>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="empty-history-state">
                            <div class="empty-icon">
                                <i class="fas fa-inbox"></i>
                            </div>
                            <h3 class="empty-title">ยังไม่มีประวัติการรักษายอด</h3>
                            <p class="empty-description">เริ่มต้นการรักษายอดของคุณวันนี้!</p>
                            <a href="{{ route('user.retention.how-it-works') }}" class="empty-action-btn">
                                <i class="fas fa-book-open"></i>
                                <span>เรียนรู้เพิ่มเติม</span>
                            </a>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Global Styles */
.retention-dashboard-page {
    background: linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%);
    min-height: 100vh;
    padding: 30px 0;
}

/* Dashboard Hero */
.dashboard-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 24px;
    padding: 40px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
}

.hero-background {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    overflow: hidden;
}

.gradient-orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(60px);
    opacity: 0.3;
    animation: float 20s ease-in-out infinite;
}

.orb-1 {
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, #ff6b9d 0%, transparent 70%);
    top: -100px;
    right: -50px;
    animation-delay: 0s;
}

.orb-2 {
    width: 250px;
    height: 250px;
    background: radial-gradient(circle, #4facfe 0%, transparent 70%);
    bottom: -80px;
    left: -50px;
    animation-delay: 5s;
}

.orb-3 {
    width: 200px;
    height: 200px;
    background: radial-gradient(circle, #43e97b 0%, transparent 70%);
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    animation-delay: 10s;
}

@keyframes float {
    0%, 100% {
        transform: translate(0, 0) scale(1);
    }
    33% {
        transform: translate(30px, -30px) scale(1.1);
    }
    66% {
        transform: translate(-20px, 20px) scale(0.9);
    }
}

.hero-content {
    position: relative;
    z-index: 1;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.hero-title-section {
    display: flex;
    align-items: center;
    gap: 20px;
}

.title-icon {
    width: 70px;
    height: 70px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 36px;
    color: #fff;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
}

.title-icon i {
    animation: pulse-icon 2s ease-in-out infinite;
}

@keyframes pulse-icon {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.1);
    }
}

.hero-title {
    color: #fff;
    font-size: 42px;
    font-weight: 800;
    margin: 0 0 5px 0;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.hero-subtitle {
    color: rgba(255, 255, 255, 0.9);
    font-size: 16px;
    margin: 0;
    font-weight: 500;
}

.hero-action-btn {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    padding: 14px 28px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 14px;
    color: #fff;
    font-weight: 600;
    font-size: 15px;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.hero-action-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
    color: #fff;
}

/* Widget Wrapper */
.widget-wrapper {
    margin: 0;
}

/* Section Headers */
.section-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
}

.section-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: #fff;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.section-title {
    color: #2d3748;
    font-size: 28px;
    font-weight: 700;
    margin: 0;
}

.section-subtitle {
    color: #718096;
    font-size: 14px;
    margin: 0;
}

/* Modern Stat Cards */
.modern-stat-card {
    background: #fff;
    border-radius: 20px;
    padding: 25px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
    height: 100%;
}

.modern-stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    transform: scaleX(0);
    transform-origin: left;
    transition: transform 0.3s ease;
}

.modern-stat-card:hover::before {
    transform: scaleX(1);
}

.modern-stat-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 35px rgba(0, 0, 0, 0.12);
}

.card-primary::before {
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
}

.card-success::before {
    background: linear-gradient(90deg, #10b981 0%, #059669 100%);
}

.card-danger::before {
    background: linear-gradient(90deg, #ef4444 0%, #dc2626 100%);
}

.card-warning::before {
    background: linear-gradient(90deg, #f59e0b 0%, #d97706 100%);
}

.stat-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.stat-card-icon {
    position: relative;
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.icon-wrapper {
    width: 100%;
    height: 100%;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    color: #fff;
    position: relative;
    z-index: 1;
    transition: transform 0.3s ease;
}

.modern-stat-card:hover .icon-wrapper {
    transform: scale(1.1) rotate(5deg);
}

.card-primary .icon-wrapper {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.card-success .icon-wrapper {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.card-danger .icon-wrapper {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.card-warning .icon-wrapper {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.icon-glow {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 100%;
    height: 100%;
    border-radius: 16px;
    filter: blur(15px);
    opacity: 0.5;
    z-index: 0;
}

.card-primary .icon-glow {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.card-success .icon-glow {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.card-danger .icon-glow {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.card-warning .icon-glow {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.stat-card-body {
    margin-bottom: 15px;
}

.stat-number {
    font-size: 48px;
    font-weight: 800;
    color: #1a202c;
    line-height: 1;
    margin-bottom: 8px;
    background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.stat-label {
    font-size: 16px;
    font-weight: 600;
    color: #4a5568;
    margin-bottom: 4px;
}

.stat-description {
    font-size: 12px;
    color: #a0aec0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-card-footer {
    padding-top: 15px;
    border-top: 2px solid #f7fafc;
}

.progress-bar-mini {
    height: 4px;
    border-radius: 2px;
    transition: width 0.5s ease;
}

.card-primary .progress-bar-mini {
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
}

.card-success .progress-bar-mini {
    background: linear-gradient(90deg, #10b981 0%, #059669 100%);
}

.card-danger .progress-bar-mini {
    background: linear-gradient(90deg, #ef4444 0%, #dc2626 100%);
}

.card-warning .progress-bar-mini {
    background: linear-gradient(90deg, #f59e0b 0%, #d97706 100%);
}

.progress-indicator {
    width: 100%;
    height: 4px;
    background: #e2e8f0;
    border-radius: 2px;
    overflow: hidden;
}

.stat-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
}

.badge-success {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    color: #065f46;
}

.badge-warning {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    color: #92400e;
}

.badge-muted {
    background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%);
    color: #6b7280;
}

.stat-action-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border-radius: 8px;
    color: #92400e;
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
}

.stat-action-link:hover {
    background: linear-gradient(135deg, #fde68a 0%, #fcd34d 100%);
    transform: translateY(-2px);
    color: #78350f;
}

/* Action Cards */
.action-card {
    background: #fff;
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 20px;
    position: relative;
    overflow: hidden;
    height: 100%;
}

.action-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
    transform: scaleY(0);
    transform-origin: bottom;
    transition: transform 0.3s ease;
}

.action-card:hover::before {
    transform: scaleY(1);
    transform-origin: top;
}

.action-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 35px rgba(0, 0, 0, 0.12);
}

.action-card-icon {
    width: 70px;
    height: 70px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    color: #fff;
    flex-shrink: 0;
    transition: transform 0.3s ease;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
}

.action-card:hover .action-card-icon {
    transform: scale(1.1) rotate(-5deg);
}

.action-icon-warning {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.action-icon-success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.action-icon-info {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
}

.action-card-content {
    flex: 1;
}

.action-card-title {
    font-size: 22px;
    font-weight: 700;
    color: #1a202c;
    margin: 0 0 8px 0;
}

.action-card-description {
    font-size: 14px;
    color: #718096;
    margin: 0 0 12px 0;
    line-height: 1.6;
}

.action-card-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge-info {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    color: #1e40af;
}

.action-card-arrow {
    font-size: 24px;
    color: #cbd5e0;
    transition: all 0.3s ease;
}

.action-card:hover .action-card-arrow {
    color: #667eea;
    transform: translateX(5px);
}

/* Modern Card */
.modern-card {
    background: #fff;
    border-radius: 24px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    overflow: hidden;
}

.modern-card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.card-header-left {
    display: flex;
    align-items: center;
    gap: 15px;
}

.card-header-icon {
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

.card-header-title {
    color: #fff;
    font-size: 24px;
    font-weight: 700;
    margin: 0 0 5px 0;
}

.card-header-subtitle {
    color: rgba(255, 255, 255, 0.9);
    font-size: 14px;
    margin: 0;
}

.filter-tabs {
    display: flex;
    gap: 10px;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    padding: 6px;
    border-radius: 12px;
}

.filter-tab {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    background: transparent;
    border: none;
    border-radius: 10px;
    color: rgba(255, 255, 255, 0.8);
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.filter-tab:hover {
    background: rgba(255, 255, 255, 0.15);
    color: #fff;
}

.filter-tab.active {
    background: rgba(255, 255, 255, 0.25);
    color: #fff;
}

.modern-card-body {
    padding: 30px;
}

/* History Timeline */
.history-timeline {
    position: relative;
}

.timeline-item {
    display: flex;
    gap: 25px;
    margin-bottom: 30px;
}

.timeline-item:last-child {
    margin-bottom: 0;
}

.timeline-marker {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex-shrink: 0;
}

.marker-dot {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: #fff;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    position: relative;
    z-index: 1;
}

.marker-dot-success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.marker-dot-danger {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.marker-dot-warning {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.marker-line {
    width: 3px;
    flex: 1;
    background: linear-gradient(180deg, #e2e8f0 0%, transparent 100%);
    margin-top: 10px;
}

.timeline-content {
    flex: 1;
}

.timeline-card {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-radius: 16px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
    border: 2px solid #f1f3f5;
}

.timeline-card:hover {
    transform: translateX(5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    border-color: #e2e8f0;
}

.timeline-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 15px;
}

.timeline-date {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 16px;
    font-weight: 600;
    color: #2d3748;
}

.timeline-date i {
    color: #667eea;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
}

.status-success {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    color: #065f46;
}

.status-danger {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    color: #991b1b;
}

.status-warning {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    color: #92400e;
}

.timeline-card-body {
    margin-bottom: 15px;
}

.points-display {
    display: flex;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}

.points-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.points-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    color: #fff;
}

.points-icon-target {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
}

.points-icon-earned {
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
}

.points-icon-percentage {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.points-details {
    display: flex;
    flex-direction: column;
}

.points-label {
    font-size: 11px;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.points-value {
    font-size: 18px;
    font-weight: 700;
    color: #1f2937;
}

.points-divider {
    color: #cbd5e0;
    font-size: 16px;
}

.timeline-progress {
    margin-top: 15px;
}

.progress-bar-timeline {
    width: 100%;
    height: 8px;
    background: #e5e7eb;
    border-radius: 4px;
    overflow: hidden;
}

.progress-fill-timeline {
    height: 100%;
    border-radius: 4px;
    position: relative;
    transition: width 0.5s ease;
}

.progress-success {
    background: linear-gradient(90deg, #10b981 0%, #059669 100%);
}

.progress-shimmer {
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
    animation: shimmer 2s ease-in-out infinite;
}

@keyframes shimmer {
    to {
        left: 100%;
    }
}

.timeline-card-footer {
    padding-top: 15px;
    border-top: 2px solid #f3f4f6;
}

.achievement-date {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 13px;
    color: #6b7280;
}

.achievement-date i {
    color: #fbbf24;
}

/* Empty State */
.empty-history-state {
    text-align: center;
    padding: 80px 20px;
}

.empty-icon {
    width: 120px;
    height: 120px;
    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 60px;
    color: #9ca3af;
    margin: 0 auto 25px;
}

.empty-title {
    font-size: 24px;
    font-weight: 700;
    color: #374151;
    margin: 0 0 10px 0;
}

.empty-description {
    font-size: 16px;
    color: #6b7280;
    margin: 0 0 30px 0;
}

.empty-action-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 14px 28px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 14px;
    color: #fff;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.empty-action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    color: #fff;
}

/* Responsive */
@media (max-width: 992px) {
    .hero-title {
        font-size: 32px;
    }

    .section-title {
        font-size: 24px;
    }

    .points-display {
        flex-direction: column;
        align-items: stretch;
    }

    .points-divider {
        display: none;
    }

    .filter-tabs {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 768px) {
    .hero-content {
        flex-direction: column;
        text-align: center;
    }

    .hero-title-section {
        flex-direction: column;
    }

    .dashboard-hero {
        padding: 30px 20px;
    }

    .modern-card-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .filter-tabs {
        flex-direction: column;
    }

    .filter-tab {
        justify-content: center;
    }

    .timeline-item {
        gap: 15px;
    }

    .timeline-card {
        padding: 20px;
    }

    .action-card {
        flex-direction: column;
        text-align: center;
    }

    .action-card-arrow {
        transform: rotate(90deg);
    }

    .action-card:hover .action-card-arrow {
        transform: rotate(90deg) translateX(5px);
    }
}
</style>

<script>
// Filter functionality
document.addEventListener('DOMContentLoaded', function() {
    const filterTabs = document.querySelectorAll('.filter-tab');
    const timelineItems = document.querySelectorAll('.timeline-item');

    filterTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Update active state
            filterTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            // Filter items
            const filter = this.dataset.filter;
            timelineItems.forEach(item => {
                if (filter === 'all' || item.dataset.status === filter) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
});
</script>

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
@endpush
@endsection
