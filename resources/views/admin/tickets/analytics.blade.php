@extends('layouts.admin')

@section('title', 'Ticket Analytics')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Ticket Analytics</h1>
    </div>

    <!-- Date Range Filter -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.tickets.analytics') }}" class="form-inline">
                <div class="form-group mr-3">
                    <label for="date_from" class="mr-2">From:</label>
                    <input type="date" name="date_from" id="date_from" class="form-control" value="{{ request('date_from', $dateFrom->format('Y-m-d')) }}">
                </div>
                <div class="form-group mr-3">
                    <label for="date_to" class="mr-2">To:</label>
                    <input type="date" name="date_to" id="date_to" class="form-control" value="{{ request('date_to', $dateTo->format('Y-m-d')) }}">
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter mr-2"></i>Filter
                </button>
            </form>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Tickets</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $analytics['total_tickets'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-ticket-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Avg Response Time</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $analytics['avg_response_time'] ?? 0 }} min</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Avg Resolution Time</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $analytics['avg_resolution_time'] ?? 0 }} min</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">SLA Compliance</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $analytics['sla_compliance'] ?? 100 }}%</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row">
        <!-- Tickets by Status -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Tickets by Status</h6>
                </div>
                <div class="card-body">
                    @if(isset($analytics['tickets_by_status']) && $analytics['tickets_by_status']->isNotEmpty())
                        <ul class="list-group">
                            @foreach($analytics['tickets_by_status'] as $status => $count)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                {{ ucfirst($status) }}
                                <span class="badge badge-primary badge-pill">{{ $count }}</span>
                            </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted">No data available</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Tickets by Priority -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Tickets by Priority</h6>
                </div>
                <div class="card-body">
                    @if(isset($analytics['tickets_by_priority']) && $analytics['tickets_by_priority']->isNotEmpty())
                        <ul class="list-group">
                            @foreach($analytics['tickets_by_priority'] as $priority => $count)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                {{ ucfirst($priority) }}
                                <span class="badge badge-{{ $priority === 'critical' ? 'danger' : ($priority === 'high' ? 'warning' : 'info') }} badge-pill">{{ $count }}</span>
                            </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted">No data available</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Top Agents -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Top Performing Agents</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Agent</th>
                            <th>Resolved Tickets</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($analytics['top_agents'] ?? [] as $agent)
                        <tr>
                            <td>{{ $agent->assignedTo->name ?? 'N/A' }}</td>
                            <td>{{ $agent->resolved_count }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" class="text-center text-muted">No data available</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
