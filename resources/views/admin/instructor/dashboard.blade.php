@extends('layouts.admin')

@section('title', 'Instructor Dashboard - Academy')

@push('styles')
<style>
:root {
    --primary-color: #6366f1;
    --primary-dark: #4f46e5;
    --primary-light: #818cf8;
    --secondary-color: #06b6d4;
    --success-color: #10b981;
    --warning-color: #f59e0b;
    --danger-color: #ef4444;
    --purple: #8b5cf6;
    --pink: #ec4899;
    --gray-50: #f9fafb;
    --gray-100: #f3f4f6;
    --gray-200: #e5e7eb;
    --gray-300: #d1d5db;
    --gray-600: #4b5563;
    --gray-700: #374151;
    --gray-800: #1f2937;
    --gray-900: #111827;
}

/* Page Header */
.instructor-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 24px;
    padding: 48px;
    color: white;
    margin-bottom: 32px;
    position: relative;
    overflow: hidden;
}

.instructor-header::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 50%;
    height: 100%;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    opacity: 0.1;
}

.instructor-title {
    font-size: 36px;
    font-weight: 800;
    margin-bottom: 12px;
}

.instructor-subtitle {
    font-size: 18px;
    opacity: 0.95;
}

/* Stats Cards */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 24px;
    margin-bottom: 32px;
}

.stat-card {
    background: white;
    border-radius: 16px;
    padding: 24px;
    border: 1px solid var(--gray-200);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 100px;
    height: 100px;
    background: var(--stat-gradient);
    opacity: 0.1;
    border-radius: 50%;
    transform: translate(30%, -30%);
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px -10px rgba(0, 0, 0, 0.15);
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    margin-bottom: 16px;
    background: var(--stat-gradient);
}

.stat-label {
    font-size: 14px;
    color: var(--gray-600);
    margin-bottom: 8px;
    font-weight: 500;
}

.stat-value {
    font-size: 32px;
    font-weight: 800;
    color: var(--gray-900);
    margin-bottom: 8px;
}

.stat-change {
    font-size: 13px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.stat-change.positive {
    color: var(--success-color);
}

.stat-change.negative {
    color: var(--danger-color);
}

/* Gradient variations */
.stat-card.courses { --stat-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.stat-card.students { --stat-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
.stat-card.revenue { --stat-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
.stat-card.rating { --stat-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
.stat-card.completed { --stat-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
.stat-card.views { --stat-gradient: linear-gradient(135deg, #30cfd0 0%, #330867 100%); }

/* Course Table */
.dashboard-card {
    background: white;
    border-radius: 16px;
    padding: 32px;
    border: 1px solid var(--gray-200);
    margin-bottom: 24px;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.card-title {
    font-size: 22px;
    font-weight: 700;
    color: var(--gray-900);
}

.btn-primary {
    padding: 12px 24px;
    background: var(--primary-color);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
}

/* Course Performance Table */
.course-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 8px;
}

.course-table thead th {
    padding: 12px 16px;
    text-align: left;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    color: var(--gray-600);
    letter-spacing: 0.5px;
    border: none;
}

.course-table tbody tr {
    background: var(--gray-50);
    transition: all 0.2s;
}

.course-table tbody tr:hover {
    background: var(--gray-100);
    transform: scale(1.01);
}

.course-table tbody td {
    padding: 16px;
    border: none;
}

.course-table tbody td:first-child {
    border-radius: 8px 0 0 8px;
}

.course-table tbody td:last-child {
    border-radius: 0 8px 8px 0;
}

.course-title-cell {
    font-weight: 600;
    color: var(--gray-900);
}

.course-category {
    display: inline-block;
    padding: 4px 12px;
    background: var(--primary-color);
    color: white;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    margin-top: 4px;
}

.progress-cell {
    min-width: 150px;
}

.progress-bar-container {
    width: 100%;
    height: 8px;
    background: var(--gray-200);
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 4px;
}

.progress-bar-inner {
    height: 100%;
    background: linear-gradient(90deg, var(--success-color), #34d399);
    border-radius: 4px;
    transition: width 0.5s ease;
}

.progress-text {
    font-size: 12px;
    color: var(--gray-600);
    font-weight: 600;
}

/* Activity Feed */
.activity-feed {
    list-style: none;
    padding: 0;
    margin: 0;
}

.activity-item {
    display: flex;
    gap: 16px;
    padding: 16px;
    border-radius: 12px;
    transition: background 0.2s;
}

.activity-item:hover {
    background: var(--gray-50);
}

.activity-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: var(--primary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    flex-shrink: 0;
}

.activity-content {
    flex: 1;
}

.activity-text {
    font-size: 14px;
    color: var(--gray-900);
    margin-bottom: 4px;
}

.activity-time {
    font-size: 12px;
    color: var(--gray-600);
}

/* Chart Container */
.chart-container {
    height: 300px;
    margin-top: 24px;
}

/* Quick Actions */
.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 32px;
}

.quick-action-btn {
    padding: 20px;
    background: white;
    border: 2px solid var(--gray-200);
    border-radius: 12px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    color: var(--gray-900);
}

.quick-action-btn:hover {
    border-color: var(--primary-color);
    background: var(--primary-color);
    color: white;
    transform: translateY(-2px);
}

.quick-action-icon {
    font-size: 32px;
    margin-bottom: 12px;
}

.quick-action-text {
    font-weight: 600;
    font-size: 14px;
}

/* Responsive */
@media (max-width: 768px) {
    .instructor-header {
        padding: 32px 24px;
    }

    .instructor-title {
        font-size: 28px;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }

    .dashboard-card {
        padding: 20px;
    }

    .course-table {
        font-size: 14px;
    }
}
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="instructor-header">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="instructor-title">👨‍🏫 Instructor Dashboard</h1>
                <p class="instructor-subtitle">
                    ยินดีต้อนรับกลับมา {{ Auth::user()->name }}! นี่คือภาพรวมของคอร์สและนักเรียนของคุณ
                </p>
            </div>
            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                <a href="{{ route('admin.articles.create') }}" class="btn-primary">
                    <i class="fas fa-plus"></i>
                    สร้างคอร์สใหม่
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card courses">
            <div class="stat-icon">
                📚
            </div>
            <div class="stat-label">คอร์สทั้งหมด</div>
            <div class="stat-value">{{ number_format($stats['total_courses']) }}</div>
            <div class="stat-change positive">
                <i class="fas fa-arrow-up"></i> +2 เดือนนี้
            </div>
        </div>

        <div class="stat-card students">
            <div class="stat-icon">
                👥
            </div>
            <div class="stat-label">นักเรียนทั้งหมด</div>
            <div class="stat-value">{{ number_format($stats['total_students']) }}</div>
            <div class="stat-change positive">
                <i class="fas fa-arrow-up"></i> +{{ rand(15, 45) }}% จากเดือนที่แล้ว
            </div>
        </div>

        <div class="stat-card revenue">
            <div class="stat-icon">
                💰
            </div>
            <div class="stat-label">รายได้รวม</div>
            <div class="stat-value">฿{{ number_format($stats['total_revenue']) }}</div>
            <div class="stat-change positive">
                <i class="fas fa-arrow-up"></i> +12% จากเดือนที่แล้ว
            </div>
        </div>

        <div class="stat-card rating">
            <div class="stat-icon">
                ⭐
            </div>
            <div class="stat-label">คะแนนเฉลี่ย</div>
            <div class="stat-value">{{ number_format($stats['avg_rating'], 1) }}</div>
            <div class="stat-change">
                จาก {{ $stats['total_students'] }} รีวิว
            </div>
        </div>

        <div class="stat-card completed">
            <div class="stat-icon">
                ✅
            </div>
            <div class="stat-label">เรียนจบ</div>
            <div class="stat-value">{{ number_format($stats['completed_enrollments']) }}</div>
            <div class="stat-change positive">
                <i class="fas fa-arrow-up"></i> {{ rand(5, 15) }}% จากเดือนที่แล้ว
            </div>
        </div>

        <div class="stat-card views">
            <div class="stat-icon">
                👁️
            </div>
            <div class="stat-label">จำนวนวิว</div>
            <div class="stat-value">{{ number_format($stats['total_views']) }}</div>
            <div class="stat-change positive">
                <i class="fas fa-arrow-up"></i> +{{ rand(20, 50) }}% จากเดือนที่แล้ว
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <a href="{{ route('admin.articles.create') }}" class="quick-action-btn">
            <div class="quick-action-icon">➕</div>
            <div class="quick-action-text">สร้างคอร์สใหม่</div>
        </a>
        <a href="{{ route('admin.articles.index') }}" class="quick-action-btn">
            <div class="quick-action-icon">📝</div>
            <div class="quick-action-text">จัดการคอร์ส</div>
        </a>
        <a href="#" class="quick-action-btn">
            <div class="quick-action-icon">💬</div>
            <div class="quick-action-text">ข้อความจากนักเรียน</div>
        </a>
        <a href="#" class="quick-action-btn">
            <div class="quick-action-icon">📊</div>
            <div class="quick-action-text">รายงานผล</div>
        </a>
    </div>

    <div class="row">
        <!-- Course Performance -->
        <div class="col-lg-8">
            <div class="dashboard-card">
                <div class="card-header">
                    <h2 class="card-title">ประสิทธิภาพคอร์ส</h2>
                    <a href="{{ route('admin.articles.index') }}" class="text-primary" style="text-decoration: none; font-weight: 600;">
                        ดูทั้งหมด <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>

                @if($coursePerformance->count() > 0)
                <div class="table-responsive">
                    <table class="course-table">
                        <thead>
                            <tr>
                                <th>คอร์ส</th>
                                <th>นักเรียน</th>
                                <th>อัตราจบคอร์ส</th>
                                <th>วิว</th>
                                <th>คะแนน</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($coursePerformance->take(5) as $course)
                            <tr>
                                <td>
                                    <div class="course-title-cell">{{ $course['title'] }}</div>
                                    <span class="course-category">{{ $course['category'] }}</span>
                                </td>
                                <td>
                                    <strong>{{ number_format($course['students']) }}</strong> คน
                                </td>
                                <td class="progress-cell">
                                    <div class="progress-bar-container">
                                        <div class="progress-bar-inner" style="width: {{ $course['completion_rate'] }}%"></div>
                                    </div>
                                    <div class="progress-text">{{ $course['completion_rate'] }}% ({{ $course['completed'] }}/{{ $course['students'] }})</div>
                                </td>
                                <td>
                                    <strong>{{ number_format($course['views']) }}</strong>
                                </td>
                                <td>
                                    <span style="color: var(--warning-color); font-weight: 700;">
                                        ⭐ {{ number_format($course['rating'], 1) }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-5">
                    <div style="font-size: 64px; margin-bottom: 16px;">📚</div>
                    <h3 style="font-weight: 600; margin-bottom: 12px;">ยังไม่มีคอร์ส</h3>
                    <p class="text-muted mb-4">เริ่มสร้างคอร์สแรกของคุณและแชร์ความรู้กับนักเรียน</p>
                    <a href="{{ route('admin.articles.create') }}" class="btn-primary">
                        <i class="fas fa-plus me-2"></i>สร้างคอร์สใหม่
                    </a>
                </div>
                @endif
            </div>

            <!-- Monthly Chart -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2 class="card-title">สถิติรายเดือน</h2>
                </div>
                <div class="chart-container">
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="col-lg-4">
            <div class="dashboard-card">
                <div class="card-header">
                    <h2 class="card-title">กิจกรรมล่าสุด</h2>
                </div>

                @if($recentActivities->count() > 0)
                <ul class="activity-feed">
                    @foreach($recentActivities->take(8) as $activity)
                    <li class="activity-item">
                        <div class="activity-avatar">
                            {{ substr($activity->user->name ?? 'U', 0, 1) }}
                        </div>
                        <div class="activity-content">
                            <div class="activity-text">
                                <strong>{{ $activity->user->name ?? 'Unknown' }}</strong>
                                @if($activity->status === 'completed')
                                    <span class="text-success">เรียนจบ</span>
                                @elseif($activity->status === 'in_progress')
                                    <span class="text-primary">กำลังเรียน</span>
                                @else
                                    <span class="text-muted">เริ่มเรียน</span>
                                @endif
                                <br>
                                <span class="text-muted">{{ $activity->article->title ?? 'Unknown Course' }}</span>
                            </div>
                            <div class="activity-time">
                                {{ $activity->updated_at->diffForHumans() }}
                            </div>
                        </div>
                    </li>
                    @endforeach
                </ul>
                @else
                <div class="text-center py-4">
                    <p class="text-muted">ยังไม่มีกิจกรรม</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
$(document).ready(function() {
    // Monthly Chart
    const monthlyData = @json($monthlyStats);

    const ctx = document.getElementById('monthlyChart');
    if (ctx && monthlyData.length > 0) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: monthlyData.map(m => m.month),
                datasets: [
                    {
                        label: 'นักเรียนใหม่',
                        data: monthlyData.map(m => m.students),
                        borderColor: 'rgb(99, 102, 241)',
                        backgroundColor: 'rgba(99, 102, 241, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'การลงทะเบียน',
                        data: monthlyData.map(m => m.enrollments),
                        borderColor: 'rgb(16, 185, 129)',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
});
</script>
@endpush
