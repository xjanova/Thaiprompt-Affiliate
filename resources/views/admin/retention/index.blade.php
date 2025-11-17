@extends('layouts.admin-v3')

@section('title', 'จัดการระบบรักษายอด - Admin')

@section('content')
<div class="container-fluid retention-admin-page">
    <div class="row">
        <div class="col-12">
            <div class="page-header mb-5">
                <div class="page-header-content">
                    <div class="page-icon">
                        <i class="fas fa-heartbeat"></i>
                    </div>
                    <div class="page-text">
                        <h1 class="page-title">จัดการระบบรักษายอด</h1>
                        <p class="page-subtitle">Membership Retention Management</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="row g-4 mb-5">
        <div class="col-md-6 col-lg-3">
            <div class="stat-card stat-card-primary">
                <div class="stat-card-header">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-badge">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
                <div class="stat-content">
                    <h3 class="stat-number">{{ number_format($totalUsers) }}</h3>
                    <p class="stat-label">สมาชิกทั้งหมด</p>
                    <div class="stat-footer">
                        <span class="stat-trend">Total Members</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="stat-card stat-card-success">
                <div class="stat-card-header">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-badge">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
                <div class="stat-content">
                    <h3 class="stat-number">{{ number_format($activeUsers) }}</h3>
                    <p class="stat-label">ใช้งานอยู่</p>
                    <div class="stat-footer">
                        <span class="stat-trend">Active Now</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="stat-card stat-card-danger">
                <div class="stat-card-header">
                    <div class="stat-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-badge">
                        <i class="fas fa-exclamation"></i>
                    </div>
                </div>
                <div class="stat-content">
                    <h3 class="stat-number">{{ number_format($expiredUsers) }}</h3>
                    <p class="stat-label">หมดอายุ</p>
                    <div class="stat-footer">
                        <span class="stat-trend">Expired</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="stat-card stat-card-warning">
                <div class="stat-card-header">
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-badge pulse">
                        <i class="fas fa-bell"></i>
                    </div>
                </div>
                <div class="stat-content">
                    <h3 class="stat-number">{{ number_format($expiringUsers) }}</h3>
                    <p class="stat-label">ใกล้หมดอายุ</p>
                    <div class="stat-footer">
                        <span class="stat-trend">Within 7 days</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Settings Panel --}}
    <div class="row mb-5">
        <div class="col-12">
            <div class="modern-card">
                <div class="modern-card-header">
                    <div class="card-header-content">
                        <div class="card-icon">
                            <i class="fas fa-cog"></i>
                        </div>
                        <div class="card-text">
                            <h3 class="card-title">การตั้งค่าระบบ</h3>
                            <p class="card-subtitle">System Configuration</p>
                        </div>
                    </div>
                </div>
                <div class="modern-card-body">
                    <form id="settingsForm">
                        @csrf
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="form-group-modern">
                                    <label class="form-label-modern">
                                        <i class="fas fa-coins text-primary"></i>
                                        แต้มขั้นต่ำต่อเดือน
                                    </label>
                                    <input type="number" class="form-control-modern" name="minimum_points_per_month"
                                           value="{{ $settings['minimum_points_per_month'] ?? 1000 }}" step="0.01"
                                           placeholder="ระบุจำนวนแต้ม">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group-modern">
                                    <label class="form-label-modern">
                                        <i class="fas fa-wrench text-warning"></i>
                                        ค่าซ่อมต่อแต้ม (เท่า)
                                    </label>
                                    <input type="number" class="form-control-modern" name="repair_cost_per_point"
                                           value="{{ $settings['repair_cost_per_point'] ?? 1.5 }}" step="0.1"
                                           placeholder="ระบุอัตราค่าซ่อม">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group-modern">
                                    <label class="form-label-modern">
                                        <i class="fas fa-percent text-success"></i>
                                        ส่วนลดเติมล่วงหน้า (0.9 = ลด 10%)
                                    </label>
                                    <input type="number" class="form-control-modern" name="advance_renewal_discount"
                                           value="{{ $settings['advance_renewal_discount'] ?? 0.9 }}" step="0.01" min="0" max="1"
                                           placeholder="0.9">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group-modern">
                                    <label class="form-label-modern">
                                        <i class="fas fa-calendar-alt text-info"></i>
                                        ระยะเวลาผ่อนผัน (วัน)
                                    </label>
                                    <input type="number" class="form-control-modern" name="grace_period_days"
                                           value="{{ $settings['grace_period_days'] ?? 3 }}"
                                           placeholder="จำนวนวัน">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group-modern">
                                    <label class="form-label-modern">
                                        <i class="fas fa-bell text-warning"></i>
                                        แจ้งเตือนก่อนหมดอายุ (วัน)
                                    </label>
                                    <input type="number" class="form-control-modern" name="warning_days_before_expiry"
                                           value="{{ $settings['warning_days_before_expiry'] ?? 7 }}"
                                           placeholder="จำนวนวัน">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group-modern">
                                    <label class="form-label-modern">
                                        <i class="fas fa-power-off text-success"></i>
                                        เปิดใช้งานระบบ
                                    </label>
                                    <select class="form-control-modern" name="enable_retention_system">
                                        <option value="1" {{ ($settings['enable_retention_system'] ?? 'true') === 'true' ? 'selected' : '' }}>✓ เปิด</option>
                                        <option value="0" {{ ($settings['enable_retention_system'] ?? 'true') === 'false' ? 'selected' : '' }}>✕ ปิด</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group-modern">
                                    <label class="form-label-modern">
                                        <i class="fas fa-ban text-danger"></i>
                                        บล็อกคอมมิชชั่นหากหมดอายุ
                                    </label>
                                    <select class="form-control-modern" name="block_commission_if_expired">
                                        <option value="1" {{ ($settings['block_commission_if_expired'] ?? 'true') === 'true' ? 'selected' : '' }}>✓ เปิด</option>
                                        <option value="0" {{ ($settings['block_commission_if_expired'] ?? 'true') === 'false' ? 'selected' : '' }}>✕ ปิด</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn-modern btn-modern-primary">
                                <i class="fas fa-save"></i>
                                <span>บันทึกการตั้งค่า</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Manual Operations --}}
    <div class="row mb-5">
        <div class="col-12">
            <div class="modern-card">
                <div class="modern-card-header">
                    <div class="card-header-content">
                        <div class="card-icon">
                            <i class="fas fa-tools"></i>
                        </div>
                        <div class="card-text">
                            <h3 class="card-title">การดำเนินการด้วยตนเอง</h3>
                            <p class="card-subtitle">Manual Operations</p>
                        </div>
                    </div>
                </div>
                <div class="modern-card-body">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <button class="action-card action-card-info" onclick="processRenewal()">
                                <div class="action-icon">
                                    <i class="fas fa-sync"></i>
                                </div>
                                <div class="action-content">
                                    <h4>ประมวลผลรายเดือน</h4>
                                    <p>Monthly Processing</p>
                                </div>
                            </button>
                        </div>
                        <div class="col-md-4">
                            <button class="action-card action-card-warning" onclick="expireUsers()">
                                <div class="action-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="action-content">
                                    <h4>ตรวจสอบและหมดอายุ</h4>
                                    <p>Check & Expire Users</p>
                                </div>
                            </button>
                        </div>
                        <div class="col-md-4">
                            <a href="{{ route('admin.retention.users') }}" class="action-card action-card-primary">
                                <div class="action-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="action-content">
                                    <h4>จัดการสมาชิก</h4>
                                    <p>Manage Members</p>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Statuses --}}
    <div class="row">
        <div class="col-12">
            <div class="modern-card">
                <div class="modern-card-header">
                    <div class="card-header-content">
                        <div class="card-icon">
                            <i class="fas fa-list"></i>
                        </div>
                        <div class="card-text">
                            <h3 class="card-title">สถานะล่าสุด</h3>
                            <p class="card-subtitle">Recent Status Updates</p>
                        </div>
                    </div>
                </div>
                <div class="modern-card-body">
                    <div class="table-responsive">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>ชื่อ</th>
                                    <th>อีเมล</th>
                                    <th>สถานะ</th>
                                    <th>แต้มปัจจุบัน</th>
                                    <th>แต้มที่ต้องการ</th>
                                    <th>วันต่ออายุ</th>
                                    <th>วันที่เหลือ</th>
                                    <th>การดำเนินการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentStatuses as $status)
                                <tr>
                                    <td>
                                        <div class="user-cell">
                                            <div class="user-avatar">
                                                {{ substr($status->user->name, 0, 1) }}
                                            </div>
                                            <span class="user-name">{{ $status->user->name }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-muted">{{ $status->user->email }}</span>
                                    </td>
                                    <td>
                                        @if($status->status === 'active')
                                            <span class="status-badge status-active">
                                                <i class="fas fa-check-circle"></i>
                                                ใช้งานอยู่
                                            </span>
                                        @elseif($status->status === 'expired')
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
                                    </td>
                                    <td>
                                        <span class="points-badge">
                                            <i class="fas fa-coins"></i>
                                            {{ number_format($status->current_points, 2) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="points-badge points-required">
                                            <i class="fas fa-bullseye"></i>
                                            {{ number_format($status->required_points, 2) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="date-badge">
                                            <i class="fas fa-calendar"></i>
                                            {{ $status->next_renewal_date ? $status->next_renewal_date->format('d/m/Y') : '-' }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $days = $status->daysRemaining();
                                            $color = $status->getHealthColor();
                                        @endphp
                                        <span class="days-badge days-{{ $color === 'green' ? 'success' : ($color === 'yellow' ? 'warning' : ($color === 'orange' ? 'warning' : 'danger')) }}">
                                            <i class="fas fa-hourglass-half"></i>
                                            {{ $days }} วัน
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.retention.users.show', $status->user_id) }}" class="btn-action">
                                            <i class="fas fa-eye"></i>
                                            <span>ดูรายละเอียด</span>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="empty-state">
                                        <div class="empty-state-content">
                                            <i class="fas fa-inbox"></i>
                                            <p>ยังไม่มีข้อมูล</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Global Styles */
.retention-admin-page {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: 100vh;
    padding: 30px 0;
}

/* Page Header */
.page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
    position: relative;
    overflow: hidden;
}

.page-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
    border-radius: 50%;
}

.page-header-content {
    display: flex;
    align-items: center;
    gap: 20px;
    position: relative;
    z-index: 1;
}

.page-icon {
    width: 70px;
    height: 70px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    color: #fff;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
}

.page-text .page-title {
    color: #fff;
    font-size: 32px;
    font-weight: 700;
    margin: 0;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.page-text .page-subtitle {
    color: rgba(255, 255, 255, 0.9);
    font-size: 16px;
    margin: 5px 0 0 0;
    font-weight: 400;
}

/* Statistics Cards */
.stat-card {
    background: #fff;
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 2px solid transparent;
    position: relative;
    overflow: hidden;
    height: 100%;
}

.stat-card::before {
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

.stat-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 50px rgba(0, 0, 0, 0.15);
}

.stat-card:hover::before {
    opacity: 1;
}

.stat-card-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
}

.stat-card-success {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: #fff;
}

.stat-card-danger {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    color: #fff;
}

.stat-card-warning {
    background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);
    color: #fff;
}

.stat-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.stat-icon {
    width: 60px;
    height: 60px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.stat-badge {
    width: 36px;
    height: 36px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
}

.stat-badge.pulse {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
        opacity: 1;
    }
    50% {
        transform: scale(1.1);
        opacity: 0.8;
    }
}

.stat-content .stat-number {
    font-size: 42px;
    font-weight: 800;
    margin: 0 0 8px 0;
    line-height: 1;
}

.stat-content .stat-label {
    font-size: 16px;
    font-weight: 500;
    margin: 0 0 12px 0;
    opacity: 0.95;
}

.stat-footer {
    padding-top: 12px;
    border-top: 1px solid rgba(255, 255, 255, 0.2);
}

.stat-trend {
    font-size: 13px;
    opacity: 0.8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Modern Cards */
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

/* Modern Forms */
.form-group-modern {
    margin-bottom: 0;
}

.form-label-modern {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    font-weight: 600;
    color: #333;
    margin-bottom: 10px;
}

.form-label-modern i {
    font-size: 16px;
}

.form-control-modern {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    font-size: 15px;
    transition: all 0.3s ease;
    background: #f9fafb;
}

.form-control-modern:focus {
    outline: none;
    border-color: #667eea;
    background: #fff;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

.form-control-modern:hover {
    border-color: #9ca3af;
}

.form-actions {
    margin-top: 30px;
    padding-top: 25px;
    border-top: 2px solid #f3f4f6;
    display: flex;
    justify-content: flex-end;
}

.btn-modern {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 14px 28px;
    border: none;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    text-decoration: none;
    position: relative;
    overflow: hidden;
}

.btn-modern::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    transform: translate(-50%, -50%);
    transition: width 0.6s, height 0.6s;
}

.btn-modern:hover::before {
    width: 300px;
    height: 300px;
}

.btn-modern span {
    position: relative;
    z-index: 1;
}

.btn-modern i {
    position: relative;
    z-index: 1;
}

.btn-modern-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.btn-modern-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 25px rgba(102, 126, 234, 0.5);
}

/* Action Cards */
.action-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 15px;
    padding: 30px 20px;
    background: #fff;
    border: 2px solid #e5e7eb;
    border-radius: 16px;
    text-decoration: none;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    width: 100%;
    position: relative;
    overflow: hidden;
}

.action-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, transparent 0%, rgba(0, 0, 0, 0.02) 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.action-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 35px rgba(0, 0, 0, 0.15);
    border-color: transparent;
}

.action-card:hover::before {
    opacity: 1;
}

.action-card-info {
    border-color: #3b82f6;
}

.action-card-info:hover {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: #fff;
}

.action-card-warning {
    border-color: #f59e0b;
}

.action-card-warning:hover {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: #fff;
}

.action-card-primary {
    border-color: #667eea;
}

.action-card-primary:hover {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
}

.action-icon {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 30px;
    color: #374151;
    transition: all 0.3s ease;
    position: relative;
    z-index: 1;
}

.action-card:hover .action-icon {
    background: rgba(255, 255, 255, 0.2);
    color: #fff;
    transform: scale(1.1) rotate(5deg);
}

.action-content {
    text-align: center;
    position: relative;
    z-index: 1;
}

.action-content h4 {
    font-size: 17px;
    font-weight: 700;
    margin: 0 0 5px 0;
    color: #1f2937;
    transition: color 0.3s ease;
}

.action-card:hover .action-content h4 {
    color: #fff;
}

.action-content p {
    font-size: 13px;
    margin: 0;
    color: #6b7280;
    transition: color 0.3s ease;
}

.action-card:hover .action-content p {
    color: rgba(255, 255, 255, 0.9);
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

.modern-table tbody tr:last-child td:first-child {
    border-radius: 0 0 0 12px;
}

.modern-table tbody tr:last-child td:last-child {
    border-radius: 0 0 12px 0;
}

/* User Cell */
.user-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}

.user-avatar {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 700;
    font-size: 16px;
    text-transform: uppercase;
}

.user-name {
    font-weight: 600;
    color: #1f2937;
}

/* Status Badges */
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

.status-inactive {
    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
    color: #374151;
}

/* Points Badges */
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

/* Date Badge */
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

/* Days Badge */
.days-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
}

.days-success {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    color: #065f46;
}

.days-warning {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    color: #78350f;
}

.days-danger {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    color: #991b1b;
}

/* Action Button */
.btn-action {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    cursor: pointer;
}

.btn-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    color: #fff;
}

/* Empty State */
.empty-state {
    padding: 60px 20px !important;
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

/* Responsive */
@media (max-width: 768px) {
    .page-header-content {
        flex-direction: column;
        text-align: center;
    }

    .stat-card {
        margin-bottom: 20px;
    }

    .modern-card-body {
        padding: 20px;
    }

    .form-actions {
        justify-content: center;
    }
}
</style>

<script>
document.getElementById('settingsForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());

    // Convert string to number/boolean
    data.minimum_points_per_month = parseFloat(data.minimum_points_per_month);
    data.repair_cost_per_point = parseFloat(data.repair_cost_per_point);
    data.advance_renewal_discount = parseFloat(data.advance_renewal_discount);
    data.grace_period_days = parseInt(data.grace_period_days);
    data.warning_days_before_expiry = parseInt(data.warning_days_before_expiry);
    data.enable_retention_system = data.enable_retention_system === '1';
    data.block_commission_if_expired = data.block_commission_if_expired === '1';

    try {
        const response = await fetch('{{ route('admin.retention.settings.update') }}', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            alert('✅ ' + result.message);
        } else {
            alert('❌ ' + result.message);
        }
    } catch (error) {
        alert('❌ เกิดข้อผิดพลาด: ' + error.message);
    }
});

async function processRenewal() {
    if (!confirm('คุณต้องการประมวลผลรายเดือนใช่หรือไม่?')) {
        return;
    }

    try {
        const response = await fetch('{{ route('admin.retention.process-renewal') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        const result = await response.json();

        if (result.success) {
            alert('✅ ประมวลผลสำเร็จ\n\nดำเนินการ: ' + result.data.processed + '\nสำเร็จ: ' + result.data.succeeded + '\nล้มเหลว: ' + result.data.failed);
            window.location.reload();
        } else {
            alert('❌ ' + result.message);
        }
    } catch (error) {
        alert('❌ เกิดข้อผิดพลาด: ' + error.message);
    }
}

async function expireUsers() {
    if (!confirm('คุณต้องการตรวจสอบและหมดอายุผู้ใช้ใช่หรือไม่?')) {
        return;
    }

    try {
        const response = await fetch('{{ route('admin.retention.expire-users') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        const result = await response.json();

        if (result.success) {
            alert('✅ ตรวจสอบสำเร็จ\n\nดำเนินการ: ' + result.data.processed + '\nหมดอายุ: ' + result.data.expired);
            window.location.reload();
        } else {
            alert('❌ ' + result.message);
        }
    } catch (error) {
        alert('❌ เกิดข้อผิดพลาด: ' + error.message);
    }
}
</script>
@endsection
