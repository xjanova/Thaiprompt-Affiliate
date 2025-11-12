@extends('layouts.hotel-admin')

@section('title', 'Occupancy Report')
@section('page-title', 'Occupancy Report')

@section('content')
<div class="container-fluid p-4">
    {{-- Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body bg-gradient-primary text-white rounded">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-2"><i class="fas fa-chart-pie me-2"></i>Occupancy Report</h2>
                            <p class="mb-0 opacity-75">Monitor room occupancy and availability</p>
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
                    <form method="GET" action="{{ route('hotel-admin.reports.occupancy') }}">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label">From Date</label>
                                <input type="date" name="date_from" class="form-control" value="{{ date('Y-m-01') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">To Date</label>
                                <input type="date" name="date_to" class="form-control" value="{{ date('Y-m-d') }}">
                            </div>
                            <div class="col-md-4">
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

    {{-- Occupancy Summary --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-percent fa-3x text-primary mb-3"></i>
                    <h3 class="mb-0">0%</h3>
                    <p class="text-muted mb-0">Average Occupancy</p>
                    <small class="text-muted">For selected period</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-bed fa-3x text-success mb-3"></i>
                    <h3 class="mb-0">0</h3>
                    <p class="text-muted mb-0">Rooms Occupied</p>
                    <small class="text-muted">Currently</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-door-open fa-3x text-info mb-3"></i>
                    <h3 class="mb-0">0</h3>
                    <p class="text-muted mb-0">Rooms Available</p>
                    <small class="text-muted">Currently</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-moon fa-3x text-warning mb-3"></i>
                    <h3 class="mb-0">0</h3>
                    <p class="text-muted mb-0">Total Room Nights</p>
                    <small class="text-muted">Sold in period</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Occupancy Chart --}}
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-chart-area me-2"></i>Occupancy Trend</h5>
                </div>
                <div class="card-body">
                    <canvas id="occupancyChart" height="80"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Room Status</h5>
                </div>
                <div class="card-body">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Occupancy by Room Type --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-bed me-2"></i>Occupancy by Room Type</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Room Type</th>
                                    <th>Total Rooms</th>
                                    <th>Occupied</th>
                                    <th>Available</th>
                                    <th>Occupancy Rate</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        No data available for the selected period
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Daily Occupancy --}}
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Daily Occupancy Details</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Day</th>
                                    <th>Total Rooms</th>
                                    <th>Occupied</th>
                                    <th>Available</th>
                                    <th>Occupancy %</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        No data available for the selected period
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
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
// Occupancy Trend Chart
const occupancyCtx = document.getElementById('occupancyChart').getContext('2d');
new Chart(occupancyCtx, {
    type: 'bar',
    data: {
        labels: [],
        datasets: [{
            label: 'Occupancy %',
            data: [],
            backgroundColor: 'rgba(102, 126, 234, 0.8)',
            borderColor: 'rgb(102, 126, 234)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                max: 100,
                ticks: {
                    callback: function(value) {
                        return value + '%';
                    }
                }
            }
        },
        plugins: {
            legend: {
                display: false
            }
        }
    }
});

// Room Status Chart
const statusCtx = document.getElementById('statusChart').getContext('2d');
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: ['Occupied', 'Available', 'Maintenance'],
        datasets: [{
            data: [0, 0, 0],
            backgroundColor: [
                'rgba(16, 185, 129, 0.8)',
                'rgba(59, 130, 246, 0.8)',
                'rgba(239, 68, 68, 0.8)'
            ]
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
