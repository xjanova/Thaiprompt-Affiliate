@extends('layouts.hotel-admin')

@section('title', 'Hotel Admin Dashboard')

@section('content')
<div class="hotel-admin-dashboard">
    {{-- Hero Header --}}
    <div class="dashboard-hero">
        <div class="hero-background">
            <div class="gradient-orb orb-1"></div>
            <div class="gradient-orb orb-2"></div>
            <div class="gradient-orb orb-3"></div>
        </div>
        <div class="hero-content">
            <div class="hero-left">
                <div class="hero-icon">
                    <i class="fas fa-hotel"></i>
                </div>
                <div class="hero-text">
                    <h1 class="hero-title">
                        @if(auth()->user()->is_admin)
                            Super Admin Dashboard
                        @else
                            {{ $hotel->name ?? 'Hotel' }} Dashboard
                        @endif
                    </h1>
                    <p class="hero-subtitle">
                        @if(auth()->user()->is_admin)
                            Manage all hotels and bookings
                        @else
                            Manage your hotel operations
                        @endif
                    </p>
                </div>
            </div>
            <div class="hero-right">
                <div class="today-date">
                    <i class="fas fa-calendar-day"></i>
                    <span>{{ now()->format('d M Y') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Statistics --}}
    <div class="row g-4 mb-5">
        <div class="col-lg-3 col-md-6">
            <div class="stat-card stat-card-primary">
                <div class="stat-card-header">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-trend positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>{{ $statistics['total_bookings_today'] }}</span>
                    </div>
                </div>
                <div class="stat-card-body">
                    <div class="stat-number">{{ number_format($statistics['total_bookings']) }}</div>
                    <div class="stat-label">Total Bookings</div>
                    <div class="stat-description">
                        {{ number_format($statistics['total_bookings_this_month']) }} this month
                    </div>
                </div>
                <div class="stat-card-footer">
                    <a href="{{ route('hotel-admin.bookings.index') }}" class="stat-link">
                        View All <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="stat-card stat-card-success">
                <div class="stat-card-header">
                    <div class="stat-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-trend positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>฿{{ number_format($statistics['revenue_today'], 0) }}</span>
                    </div>
                </div>
                <div class="stat-card-body">
                    <div class="stat-number">฿{{ number_format($statistics['total_revenue'], 0) }}</div>
                    <div class="stat-label">Total Revenue</div>
                    <div class="stat-description">
                        ฿{{ number_format($statistics['revenue_this_month'], 0) }} this month
                    </div>
                </div>
                <div class="stat-card-footer">
                    <a href="{{ route('hotel-admin.reports.revenue') }}" class="stat-link">
                        View Report <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="stat-card stat-card-warning">
                <div class="stat-card-header">
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-trend">
                        <i class="fas fa-star"></i>
                        <span>{{ number_format($statistics['average_rating'], 1) }}</span>
                    </div>
                </div>
                <div class="stat-card-body">
                    <div class="stat-number">{{ number_format($statistics['total_reviews']) }}</div>
                    <div class="stat-label">Total Reviews</div>
                    <div class="stat-description">
                        {{ number_format($statistics['pending_reviews']) }} pending approval
                    </div>
                </div>
                <div class="stat-card-footer">
                    <a href="{{ route('hotel-admin.reviews.index') }}" class="stat-link">
                        Manage Reviews <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="stat-card stat-card-info">
                <div class="stat-card-header">
                    <div class="stat-icon">
                        <i class="fas fa-hotel"></i>
                    </div>
                    <div class="stat-trend">
                        <i class="fas fa-check-circle"></i>
                        <span>{{ $statistics['active_hotels'] }}</span>
                    </div>
                </div>
                <div class="stat-card-body">
                    <div class="stat-number">{{ number_format($statistics['total_hotels']) }}</div>
                    <div class="stat-label">
                        @if(auth()->user()->is_admin)
                            Total Hotels
                        @else
                            Your Hotel
                        @endif
                    </div>
                    <div class="stat-description">
                        Active and operational
                    </div>
                </div>
                <div class="stat-card-footer">
                    @if(auth()->user()->is_admin)
                    <a href="{{ route('hotel-admin.hotels.index') }}" class="stat-link">
                        Manage Hotels <i class="fas fa-arrow-right"></i>
                    </a>
                    @else
                    <a href="{{ route('hotel-admin.hotel.settings') }}" class="stat-link">
                        Hotel Settings <i class="fas fa-arrow-right"></i>
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Booking Status Overview --}}
    <div class="row g-4 mb-5">
        <div class="col-lg-8">
            <div class="modern-card">
                <div class="card-header">
                    <div class="card-header-left">
                        <h3 class="card-title">
                            <i class="fas fa-chart-line"></i>
                            Booking Trends
                        </h3>
                        <p class="card-subtitle">Last 12 months performance</p>
                    </div>
                    <div class="card-header-right">
                        <div class="chart-tabs">
                            <button class="chart-tab active" data-chart="bookings">
                                <i class="fas fa-calendar"></i> Bookings
                            </button>
                            <button class="chart-tab" data-chart="revenue">
                                <i class="fas fa-dollar-sign"></i> Revenue
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="bookingTrendsChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="modern-card">
                <div class="card-header">
                    <div class="card-header-left">
                        <h3 class="card-title">
                            <i class="fas fa-tasks"></i>
                            Booking Status
                        </h3>
                    </div>
                </div>
                <div class="card-body">
                    <div class="status-list">
                        <div class="status-item">
                            <div class="status-info">
                                <div class="status-icon status-pending">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="status-details">
                                    <span class="status-label">Pending</span>
                                    <span class="status-count">{{ $statistics['pending_bookings'] }}</span>
                                </div>
                            </div>
                            <div class="status-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill status-pending-fill" style="width: {{ $statistics['total_bookings'] > 0 ? ($statistics['pending_bookings'] / $statistics['total_bookings'] * 100) : 0 }}%"></div>
                                </div>
                            </div>
                        </div>

                        <div class="status-item">
                            <div class="status-info">
                                <div class="status-icon status-confirmed">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="status-details">
                                    <span class="status-label">Confirmed</span>
                                    <span class="status-count">{{ $statistics['confirmed_bookings'] }}</span>
                                </div>
                            </div>
                            <div class="status-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill status-confirmed-fill" style="width: {{ $statistics['total_bookings'] > 0 ? ($statistics['confirmed_bookings'] / $statistics['total_bookings'] * 100) : 0 }}%"></div>
                                </div>
                            </div>
                        </div>

                        <div class="status-item">
                            <div class="status-info">
                                <div class="status-icon status-checkedin">
                                    <i class="fas fa-door-open"></i>
                                </div>
                                <div class="status-details">
                                    <span class="status-label">Checked In</span>
                                    <span class="status-count">{{ $statistics['checked_in_bookings'] }}</span>
                                </div>
                            </div>
                            <div class="status-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill status-checkedin-fill" style="width: {{ $statistics['total_bookings'] > 0 ? ($statistics['checked_in_bookings'] / $statistics['total_bookings'] * 100) : 0 }}%"></div>
                                </div>
                            </div>
                        </div>

                        <div class="status-item">
                            <div class="status-info">
                                <div class="status-icon status-completed">
                                    <i class="fas fa-check-double"></i>
                                </div>
                                <div class="status-details">
                                    <span class="status-label">Completed</span>
                                    <span class="status-count">{{ $statistics['completed_bookings'] }}</span>
                                </div>
                            </div>
                            <div class="status-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill status-completed-fill" style="width: {{ $statistics['total_bookings'] > 0 ? ($statistics['completed_bookings'] / $statistics['total_bookings'] * 100) : 0 }}%"></div>
                                </div>
                            </div>
                        </div>

                        <div class="status-item">
                            <div class="status-info">
                                <div class="status-icon status-cancelled">
                                    <i class="fas fa-times-circle"></i>
                                </div>
                                <div class="status-details">
                                    <span class="status-label">Cancelled</span>
                                    <span class="status-count">{{ $statistics['cancelled_bookings'] }}</span>
                                </div>
                            </div>
                            <div class="status-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill status-cancelled-fill" style="width: {{ $statistics['total_bookings'] > 0 ? ($statistics['cancelled_bookings'] / $statistics['total_bookings'] * 100) : 0 }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Google Maps Section --}}
    <div class="row g-4 mb-5">
        <div class="col-12">
            <div class="modern-card">
                <div class="card-header">
                    <div class="card-header-left">
                        <h3 class="card-title">
                            <i class="fas fa-map-marked-alt"></i>
                            Hotel Locations
                        </h3>
                        <p class="card-subtitle">Interactive map view</p>
                    </div>
                    <div class="card-header-right">
                        <button class="btn btn-sm btn-primary" id="refreshMapBtn">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div id="hotelMap" style="height: 500px; width: 100%;"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Activity --}}
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="modern-card">
                <div class="card-header">
                    <div class="card-header-left">
                        <h3 class="card-title">
                            <i class="fas fa-clock"></i>
                            Recent Bookings
                        </h3>
                    </div>
                    <div class="card-header-right">
                        <a href="{{ route('hotel-admin.bookings.index') }}" class="btn btn-sm btn-ghost">
                            View All
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="activity-list">
                        @forelse($recentBookings as $booking)
                        <div class="activity-item">
                            <div class="activity-icon booking-icon-{{ $booking->status }}">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-header">
                                    <h4 class="activity-title">{{ $booking->hotel->name ?? 'N/A' }}</h4>
                                    <span class="activity-badge badge-{{ $booking->status }}">
                                        {{ ucfirst($booking->status) }}
                                    </span>
                                </div>
                                <p class="activity-description">
                                    {{ $booking->user->name ?? 'Guest' }} -
                                    {{ $booking->roomType->name ?? 'Room' }} -
                                    {{ $booking->number_of_rooms }} room(s)
                                </p>
                                <div class="activity-footer">
                                    <span class="activity-time">
                                        <i class="fas fa-clock"></i>
                                        {{ $booking->created_at->diffForHumans() }}
                                    </span>
                                    <span class="activity-price">
                                        <i class="fas fa-dollar-sign"></i>
                                        ฿{{ number_format($booking->total_price, 2) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p>No recent bookings</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="modern-card">
                <div class="card-header">
                    <div class="card-header-left">
                        <h3 class="card-title">
                            <i class="fas fa-star"></i>
                            Recent Reviews
                        </h3>
                    </div>
                    <div class="card-header-right">
                        <a href="{{ route('hotel-admin.reviews.index') }}" class="btn btn-sm btn-ghost">
                            View All
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="activity-list">
                        @forelse($recentReviews as $review)
                        <div class="activity-item">
                            <div class="activity-icon review-icon">
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-header">
                                    <h4 class="activity-title">{{ $review->hotel->name ?? 'N/A' }}</h4>
                                    <div class="rating-stars">
                                        @for($i = 1; $i <= 5; $i++)
                                            <i class="fas fa-star {{ $i <= $review->overall_rating ? 'active' : '' }}"></i>
                                        @endfor
                                    </div>
                                </div>
                                <p class="activity-description">
                                    "{{ Str::limit($review->comment, 100) }}"
                                </p>
                                <div class="activity-footer">
                                    <span class="activity-author">
                                        <i class="fas fa-user"></i>
                                        {{ $review->user->name ?? 'Guest' }}
                                    </span>
                                    <span class="activity-time">
                                        <i class="fas fa-clock"></i>
                                        {{ $review->created_at->diffForHumans() }}
                                    </span>
                                    @if(!$review->is_approved)
                                    <span class="activity-badge badge-warning">
                                        Pending Approval
                                    </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p>No recent reviews</p>
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
.hotel-admin-dashboard {
    padding: 30px;
    background: linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%);
    min-height: 100vh;
}

/* Dashboard Hero */
.dashboard-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 24px;
    padding: 40px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
    margin-bottom: 30px;
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
    0%, 100% { transform: translate(0, 0) scale(1); }
    33% { transform: translate(30px, -30px) scale(1.1); }
    66% { transform: translate(-20px, 20px) scale(0.9); }
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

.hero-left {
    display: flex;
    align-items: center;
    gap: 20px;
}

.hero-icon {
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
}

.hero-title {
    color: #fff;
    font-size: 38px;
    font-weight: 800;
    margin: 0 0 5px 0;
}

.hero-subtitle {
    color: rgba(255, 255, 255, 0.9);
    font-size: 16px;
    margin: 0;
}

.today-date {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 20px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border-radius: 14px;
    color: #fff;
    font-size: 16px;
    font-weight: 600;
}

/* Stat Cards */
.stat-card {
    background: #fff;
    border-radius: 20px;
    padding: 25px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    height: 100%;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    transform: scaleX(0);
    transition: transform 0.3s ease;
}

.stat-card:hover::before {
    transform: scaleX(1);
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 35px rgba(0, 0, 0, 0.12);
}

.stat-card-primary::before { background: linear-gradient(90deg, #667eea 0%, #764ba2 100%); }
.stat-card-success::before { background: linear-gradient(90deg, #10b981 0%, #059669 100%); }
.stat-card-warning::before { background: linear-gradient(90deg, #f59e0b 0%, #d97706 100%); }
.stat-card-info::before { background: linear-gradient(90deg, #3b82f6 0%, #2563eb 100%); }

.stat-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    color: #fff;
}

.stat-card-primary .stat-icon { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.stat-card-success .stat-icon { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
.stat-card-warning .stat-icon { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
.stat-card-info .stat-icon { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }

.stat-trend {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: #f1f5f9;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    color: #475569;
}

.stat-trend.positive {
    background: #d1fae5;
    color: #065f46;
}

.stat-number {
    font-size: 42px;
    font-weight: 800;
    color: #1a202c;
    line-height: 1;
    margin-bottom: 8px;
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
}

.stat-card-footer {
    padding-top: 15px;
    border-top: 2px solid #f7fafc;
    margin-top: 15px;
}

.stat-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #667eea;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
}

.stat-link:hover {
    color: #764ba2;
    gap: 12px;
}

/* Modern Card */
.modern-card {
    background: #fff;
    border-radius: 24px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    overflow: hidden;
}

.card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 25px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.card-header-left {
    flex: 1;
}

.card-title {
    color: #fff;
    font-size: 20px;
    font-weight: 700;
    margin: 0 0 5px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.card-subtitle {
    color: rgba(255, 255, 255, 0.9);
    font-size: 13px;
    margin: 0;
}

.card-body {
    padding: 30px;
}

.chart-tabs {
    display: flex;
    gap: 10px;
    background: rgba(255, 255, 255, 0.1);
    padding: 6px;
    border-radius: 12px;
}

.chart-tab {
    padding: 10px 18px;
    background: transparent;
    border: none;
    border-radius: 10px;
    color: rgba(255, 255, 255, 0.8);
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.chart-tab:hover {
    background: rgba(255, 255, 255, 0.15);
    color: #fff;
}

.chart-tab.active {
    background: rgba(255, 255, 255, 0.25);
    color: #fff;
}

/* Status List */
.status-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.status-item {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.status-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.status-icon {
    width: 45px;
    height: 45px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: #fff;
}

.status-pending { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
.status-confirmed { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
.status-checkedin { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }
.status-completed { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); }
.status-cancelled { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }

.status-details {
    flex: 1;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.status-label {
    font-size: 15px;
    font-weight: 600;
    color: #374151;
}

.status-count {
    font-size: 24px;
    font-weight: 800;
    color: #1f2937;
}

.status-progress {
    width: 100%;
}

.progress-bar {
    width: 100%;
    height: 8px;
    background: #e5e7eb;
    border-radius: 4px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    border-radius: 4px;
    transition: width 0.5s ease;
}

.status-pending-fill { background: linear-gradient(90deg, #f59e0b 0%, #d97706 100%); }
.status-confirmed-fill { background: linear-gradient(90deg, #10b981 0%, #059669 100%); }
.status-checkedin-fill { background: linear-gradient(90deg, #3b82f6 0%, #2563eb 100%); }
.status-completed-fill { background: linear-gradient(90deg, #8b5cf6 0%, #7c3aed 100%); }
.status-cancelled-fill { background: linear-gradient(90deg, #ef4444 0%, #dc2626 100%); }

/* Activity List */
.activity-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.activity-item {
    display: flex;
    gap: 15px;
    padding: 15px;
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-radius: 16px;
    border: 2px solid #f1f3f5;
    transition: all 0.3s ease;
}

.activity-item:hover {
    transform: translateX(5px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    border-color: #e2e8f0;
}

.activity-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    color: #fff;
    flex-shrink: 0;
}

.booking-icon-pending { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
.booking-icon-confirmed { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
.booking-icon-checked_in { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }
.booking-icon-completed { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); }
.booking-icon-cancelled { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
.review-icon { background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); }

.activity-content {
    flex: 1;
}

.activity-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.activity-title {
    font-size: 16px;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}

.activity-badge {
    padding: 4px 10px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.badge-pending { background: #fef3c7; color: #92400e; }
.badge-confirmed { background: #d1fae5; color: #065f46; }
.badge-checked_in { background: #dbeafe; color: #1e40af; }
.badge-completed { background: #ede9fe; color: #5b21b6; }
.badge-cancelled { background: #fee2e2; color: #991b1b; }
.badge-warning { background: #fef3c7; color: #92400e; }

.activity-description {
    font-size: 14px;
    color: #6b7280;
    margin: 0 0 10px 0;
}

.activity-footer {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    font-size: 12px;
    color: #9ca3af;
}

.activity-time, .activity-price, .activity-author {
    display: flex;
    align-items: center;
    gap: 5px;
}

.rating-stars {
    display: flex;
    gap: 3px;
}

.rating-stars i {
    font-size: 14px;
    color: #d1d5db;
}

.rating-stars i.active {
    color: #fbbf24;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #9ca3af;
}

.empty-state i {
    font-size: 48px;
    margin-bottom: 15px;
}

.empty-state p {
    margin: 0;
    font-size: 16px;
}

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 12px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

.btn-sm {
    padding: 8px 16px;
    font-size: 14px;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.btn-ghost {
    background: rgba(255, 255, 255, 0.2);
    color: #fff;
    border: 2px solid rgba(255, 255, 255, 0.3);
}

.btn-ghost:hover {
    background: rgba(255, 255, 255, 0.3);
    border-color: rgba(255, 255, 255, 0.5);
}

/* Google Maps */
#hotelMap {
    border-radius: 0 0 24px 24px;
}

/* Responsive */
@media (max-width: 768px) {
    .hotel-admin-dashboard {
        padding: 15px;
    }

    .dashboard-hero {
        padding: 25px 20px;
    }

    .hero-content {
        flex-direction: column;
        text-align: center;
    }

    .hero-left {
        flex-direction: column;
    }

    .hero-title {
        font-size: 28px;
    }

    .card-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .chart-tabs {
        width: 100%;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAPS_API_KEY') }}&callback=initMap" async defer></script>

<script>
// Booking Trends Chart
const bookingTrendsData = @json($bookingTrends);

const ctx = document.getElementById('bookingTrendsChart').getContext('2d');
let currentChart = 'bookings';

const chartData = {
    labels: bookingTrendsData.map(item => item.month),
    datasets: [{
        label: 'Bookings',
        data: bookingTrendsData.map(item => item.bookings),
        borderColor: 'rgb(102, 126, 234)',
        backgroundColor: 'rgba(102, 126, 234, 0.1)',
        tension: 0.4,
        fill: true,
        pointRadius: 5,
        pointHoverRadius: 7,
    }]
};

const bookingTrendsChart = new Chart(ctx, {
    type: 'line',
    data: chartData,
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                mode: 'index',
                intersect: false,
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleColor: '#fff',
                bodyColor: '#fff',
                borderColor: 'rgba(102, 126, 234, 0.5)',
                borderWidth: 1,
                padding: 12,
                displayColors: false,
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)',
                },
                ticks: {
                    color: '#6b7280',
                }
            },
            x: {
                grid: {
                    display: false,
                },
                ticks: {
                    color: '#6b7280',
                }
            }
        }
    }
});

// Chart Tab Switching
document.querySelectorAll('.chart-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.chart-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');

        const chartType = this.dataset.chart;

        if (chartType === 'bookings') {
            bookingTrendsChart.data.datasets[0].label = 'Bookings';
            bookingTrendsChart.data.datasets[0].data = bookingTrendsData.map(item => item.bookings);
            bookingTrendsChart.data.datasets[0].borderColor = 'rgb(102, 126, 234)';
            bookingTrendsChart.data.datasets[0].backgroundColor = 'rgba(102, 126, 234, 0.1)';
        } else {
            bookingTrendsChart.data.datasets[0].label = 'Revenue (฿)';
            bookingTrendsChart.data.datasets[0].data = bookingTrendsData.map(item => item.revenue);
            bookingTrendsChart.data.datasets[0].borderColor = 'rgb(16, 185, 129)';
            bookingTrendsChart.data.datasets[0].backgroundColor = 'rgba(16, 185, 129, 0.1)';
        }

        bookingTrendsChart.update();
    });
});

// Google Maps
let map;
let markers = [];

function initMap() {
    // Default center (Thailand)
    const defaultCenter = { lat: 13.7563, lng: 100.5018 };

    map = new google.maps.Map(document.getElementById('hotelMap'), {
        zoom: 12,
        center: defaultCenter,
        styles: [
            {
                "featureType": "all",
                "elementType": "geometry",
                "stylers": [{"color": "#f5f5f5"}]
            },
            {
                "featureType": "water",
                "elementType": "geometry",
                "stylers": [{"color": "#c9e9ff"}]
            },
            {
                "featureType": "road",
                "elementType": "geometry",
                "stylers": [{"lightness": 100}, {"visibility": "simplified"}]
            }
        ]
    });

    loadHotels();
}

function loadHotels() {
    fetch('{{ route('hotel-admin.map-data') }}')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.hotels.length > 0) {
                // Clear existing markers
                markers.forEach(marker => marker.setMap(null));
                markers = [];

                const bounds = new google.maps.LatLngBounds();

                data.hotels.forEach(hotel => {
                    const position = {
                        lat: parseFloat(hotel.latitude),
                        lng: parseFloat(hotel.longitude)
                    };

                    const marker = new google.maps.Marker({
                        position: position,
                        map: map,
                        title: hotel.name,
                        icon: {
                            path: google.maps.SymbolPath.CIRCLE,
                            scale: 10,
                            fillColor: '#667eea',
                            fillOpacity: 0.9,
                            strokeColor: '#fff',
                            strokeWeight: 3,
                        }
                    });

                    const infoWindow = new google.maps.InfoWindow({
                        content: `
                            <div style="padding: 10px; max-width: 250px;">
                                <h3 style="margin: 0 0 10px 0; font-size: 16px; font-weight: 700; color: #1f2937;">
                                    ${hotel.name}
                                </h3>
                                <p style="margin: 0 0 8px 0; font-size: 13px; color: #6b7280;">
                                    <i class="fas fa-map-marker-alt"></i> ${hotel.address}
                                </p>
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                                    <div style="display: flex; gap: 2px;">
                                        ${Array(5).fill(0).map((_, i) =>
                                            `<i class="fas fa-star" style="color: ${i < hotel.rating ? '#fbbf24' : '#d1d5db'}; font-size: 12px;"></i>`
                                        ).join('')}
                                    </div>
                                    <span style="font-size: 13px; color: #6b7280; font-weight: 600;">
                                        ${hotel.rating.toFixed(1)}
                                    </span>
                                </div>
                                <a href="/hotel-admin/hotels/${hotel.id}"
                                   style="display: inline-block; padding: 8px 16px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; text-decoration: none; border-radius: 8px; font-size: 13px; font-weight: 600;">
                                    View Details
                                </a>
                            </div>
                        `
                    });

                    marker.addListener('click', () => {
                        infoWindow.open(map, marker);
                    });

                    markers.push(marker);
                    bounds.extend(position);
                });

                // Fit map to show all markers
                if (data.hotels.length > 1) {
                    map.fitBounds(bounds);
                } else {
                    map.setCenter(bounds.getCenter());
                    map.setZoom(15);
                }
            }
        })
        .catch(error => console.error('Error loading hotel data:', error));
}

// Refresh Map Button
document.getElementById('refreshMapBtn').addEventListener('click', function() {
    this.innerHTML = '<i class="fas fa-sync-alt fa-spin"></i> Refreshing...';
    loadHotels();
    setTimeout(() => {
        this.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh';
    }, 1000);
});
</script>

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
@endpush
@endsection
