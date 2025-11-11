@extends('layouts.hotel-admin')

@section('title', 'Revenue Report')
@section('page-title', 'Revenue Report')

@section('content')
<div class="container-fluid p-4">
    {{-- Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body bg-gradient-primary text-white rounded">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-2"><i class="fas fa-chart-line me-2"></i>Revenue Report</h2>
                            <p class="mb-0 opacity-75">Track your hotel's revenue performance</p>
                        </div>
                        <div>
                            <button class="btn btn-light" onclick="window.print()">
                                <i class="fas fa-print me-2"></i>Print Report
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Date Range Filter --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="GET" action="{{ route('hotel-admin.reports.revenue') }}">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label">From Date</label>
                                <input type="date" name="date_from" class="form-control" value="{{ date('Y-m-01') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">To Date</label>
                                <input type="date" name="date_to" class="form-control" value="{{ date('Y-m-d') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Group By</label>
                                <select name="group_by" class="form-select">
                                    <option value="day">Daily</option>
                                    <option value="week">Weekly</option>
                                    <option value="month" selected>Monthly</option>
                                    <option value="year">Yearly</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search me-2"></i>Generate Report
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-dollar-sign fa-3x text-success mb-3"></i>
                    <h3 class="mb-0">฿0</h3>
                    <p class="text-muted mb-0">Total Revenue</p>
                    <small class="text-success">
                        <i class="fas fa-arrow-up me-1"></i>0% from last period
                    </small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-calendar-check fa-3x text-primary mb-3"></i>
                    <h3 class="mb-0">0</h3>
                    <p class="text-muted mb-0">Total Bookings</p>
                    <small class="text-success">
                        <i class="fas fa-arrow-up me-1"></i>0% from last period
                    </small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-chart-bar fa-3x text-info mb-3"></i>
                    <h3 class="mb-0">฿0</h3>
                    <p class="text-muted mb-0">Average Booking Value</p>
                    <small class="text-muted">Per booking</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-bed fa-3x text-warning mb-3"></i>
                    <h3 class="mb-0">฿0</h3>
                    <p class="text-muted mb-0">Revenue Per Room</p>
                    <small class="text-muted">Average</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Revenue Chart --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-chart-area me-2"></i>Revenue Trend</h5>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Revenue Breakdown --}}
    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-table me-2"></i>Detailed Revenue Breakdown</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Period</th>
                                    <th>Bookings</th>
                                    <th>Revenue</th>
                                    <th>Avg. Booking</th>
                                    <th>Growth</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        No data available for the selected period
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Revenue by Room Type</h5>
                </div>
                <div class="card-body">
                    <canvas id="roomTypeChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
@media print {
    .sidebar, .topbar, button, .no-print {
        display: none !important;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Revenue Trend Chart
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
new Chart(revenueCtx, {
    type: 'line',
    data: {
        labels: [],
        datasets: [{
            label: 'Revenue (฿)',
            data: [],
            borderColor: 'rgb(102, 126, 234)',
            backgroundColor: 'rgba(102, 126, 234, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        }
    }
});

// Room Type Chart
const roomTypeCtx = document.getElementById('roomTypeChart').getContext('2d');
new Chart(roomTypeCtx, {
    type: 'doughnut',
    data: {
        labels: ['No Data'],
        datasets: [{
            data: [1],
            backgroundColor: ['#e5e7eb']
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
</script>
@endsection
