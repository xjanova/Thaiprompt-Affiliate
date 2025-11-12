@extends('layouts.admin')

@section('title', 'LINE Membership Signup Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-user-plus text-primary"></i>
                LINE Membership Signup
            </h1>
            <p class="text-muted mb-0">AI-Powered Signup System Dashboard</p>
        </div>
        <div>
            <select id="dateRangeFilter" class="form-select">
                <option value="7" {{ $dateRange == 7 ? 'selected' : '' }}>Last 7 Days</option>
                <option value="30" {{ $dateRange == 30 ? 'selected' : '' }}>Last 30 Days</option>
                <option value="90" {{ $dateRange == 90 ? 'selected' : '' }}>Last 90 Days</option>
            </select>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <!-- Total Sessions -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Sessions
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($stats['total_sessions']) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Completed -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Completed
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($stats['completed']) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Active Now
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($stats['active']) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Completion Rate -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Completion Rate
                            </div>
                            <div class="row no-gutters align-items-center">
                                <div class="col-auto">
                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">
                                        {{ number_format($stats['completion_rate'], 1) }}%
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="progress progress-sm mr-2">
                                        <div class="progress-bar bg-warning" role="progressbar"
                                             style="width: {{ $stats['completion_rate'] }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-percentage fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Daily Signups Chart -->
        <div class="col-xl-8 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-area"></i>
                        Daily Signups Trend
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="dailySignupsChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Step Funnel -->
        <div class="col-xl-4 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-funnel-dollar"></i>
                        Signup Funnel
                    </h6>
                </div>
                <div class="card-body">
                    @foreach($stepFunnel as $step)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small class="text-uppercase font-weight-bold">{{ $step->step_name }}</small>
                            <small class="text-muted">{{ $step->visitors }} visitors</small>
                        </div>
                        <div class="progress" style="height: 20px;">
                            @php
                                $percentage = $step->visitors > 0 ? ($step->completions / $step->visitors) * 100 : 0;
                            @endphp
                            <div class="progress-bar bg-success" role="progressbar"
                                 style="width: {{ $percentage }}%"
                                 title="{{ number_format($percentage, 1) }}% completed">
                                {{ number_format($percentage, 0) }}%
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Sessions -->
        <div class="col-xl-8 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-list"></i>
                        Recent Signup Sessions
                    </h6>
                    <a href="{{ route('admin.line-membership-signup.sessions') }}" class="btn btn-sm btn-primary">
                        View All
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>LINE User</th>
                                    <th>Step</th>
                                    <th>Status</th>
                                    <th>Progress</th>
                                    <th>Started</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentSessions as $session)
                                <tr>
                                    <td>{{ $session->id }}</td>
                                    <td>
                                        <small class="text-muted">{{ Str::limit($session->line_user_id, 10) }}</small>
                                    </td>
                                    <td>
                                        <span class="badge badge-info">{{ $session->current_step }}</span>
                                    </td>
                                    <td>
                                        @if($session->status === 'completed')
                                            <span class="badge badge-success">Completed</span>
                                        @elseif($session->status === 'active')
                                            <span class="badge badge-primary">Active</span>
                                        @elseif($session->status === 'abandoned')
                                            <span class="badge badge-warning">Abandoned</span>
                                        @else
                                            <span class="badge badge-secondary">{{ $session->status }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 8px; width: 80px;">
                                            <div class="progress-bar" role="progressbar"
                                                 style="width: {{ $session->getProgressPercentage() }}%"></div>
                                        </div>
                                    </td>
                                    <td>
                                        <small>{{ $session->started_at->diffForHumans() }}</small>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.line-membership-signup.sessions.show', $session) }}"
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        No signup sessions yet
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Performers -->
        <div class="col-xl-4 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-trophy"></i>
                        Top Referrers
                    </h6>
                </div>
                <div class="card-body">
                    @forelse($topPerformers as $index => $performer)
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex align-items-center">
                            <div class="mr-3">
                                @if($index === 0)
                                    <i class="fas fa-trophy text-warning fa-lg"></i>
                                @elseif($index === 1)
                                    <i class="fas fa-medal text-secondary fa-lg"></i>
                                @elseif($index === 2)
                                    <i class="fas fa-medal text-danger fa-lg"></i>
                                @else
                                    <span class="text-muted">{{ $index + 1 }}</span>
                                @endif
                            </div>
                            <div>
                                <div class="font-weight-bold">{{ $performer->name }}</div>
                                <small class="text-muted">{{ $performer->referral_code }}</small>
                            </div>
                        </div>
                        <div>
                            <span class="badge badge-success badge-pill">
                                {{ $performer->signup_count }} signups
                            </span>
                        </div>
                    </div>
                    @empty
                    <p class="text-muted text-center py-4">No data yet</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-tools"></i>
                        Quick Actions
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('admin.line-membership-signup.templates') }}" class="btn btn-outline-primary btn-block">
                                <i class="fas fa-file-alt"></i> Manage Templates
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('admin.line-membership-signup.invitations') }}" class="btn btn-outline-success btn-block">
                                <i class="fas fa-link"></i> View Invitations
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('admin.line-membership-signup.rewards') }}" class="btn btn-outline-warning btn-block">
                                <i class="fas fa-gift"></i> Manage Rewards
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('admin.line-membership-signup.export.sessions') }}" class="btn btn-outline-info btn-block">
                                <i class="fas fa-download"></i> Export Data
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
// Daily Signups Chart
const dailySignupsData = @json($dailySignups);
const ctx = document.getElementById('dailySignupsChart').getContext('2d');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: dailySignupsData.map(d => d.date),
        datasets: [
            {
                label: 'Total Signups',
                data: dailySignupsData.map(d => d.total),
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1
            },
            {
                label: 'Completed',
                data: dailySignupsData.map(d => d.completed),
                borderColor: 'rgb(54, 162, 235)',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                tension: 0.1
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'top',
            },
            title: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Date range filter
document.getElementById('dateRangeFilter').addEventListener('change', function() {
    window.location.href = '{{ route("admin.line-membership-signup.index") }}?range=' + this.value;
});
</script>
@endpush
@endsection
