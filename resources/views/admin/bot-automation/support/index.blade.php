@extends('layouts.admin')

@section('title', 'Bot Support Dashboard')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Bot Support Dashboard</h1>
        <a href="{{ route('admin.bot-automation.support.tickets.index') }}" class="btn btn-primary">
            <i class="fas fa-ticket-alt mr-2"></i>View All Tickets
        </a>
    </div>

    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Tickets</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalTickets ?? '0' }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-ticket-alt fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Open Tickets</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $openTickets ?? '0' }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-folder-open fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Resolved Today</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $resolvedToday ?? '0' }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Avg Response Time</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $avgResponseTime ?? '0' }}h</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Ticket Trends</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="ticketTrendsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Tickets by Priority</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie">
                        <canvas id="priorityChart"></canvas>
                    </div>
                    <div class="mt-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span><i class="fas fa-circle text-danger mr-2"></i>High</span>
                            <span class="font-weight-bold">{{ $highPriority ?? '0' }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span><i class="fas fa-circle text-warning mr-2"></i>Medium</span>
                            <span class="font-weight-bold">{{ $mediumPriority ?? '0' }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span><i class="fas fa-circle text-info mr-2"></i>Low</span>
                            <span class="font-weight-bold">{{ $lowPriority ?? '0' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Tickets</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Subject</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentTickets ?? [] as $ticket)
                                <tr>
                                    <td>{{ $ticket->id }}</td>
                                    <td>{{ Str::limit($ticket->subject ?? 'N/A', 30) }}</td>
                                    <td>
                                        <span class="badge badge-{{ $ticket->priority == 'high' ? 'danger' : ($ticket->priority == 'medium' ? 'warning' : 'info') }}">
                                            {{ ucfirst($ticket->priority ?? 'N/A') }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $ticket->status == 'open' ? 'warning' : ($ticket->status == 'resolved' ? 'success' : 'secondary') }}">
                                            {{ ucfirst($ticket->status ?? 'N/A') }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.bot-automation.support.tickets.show', $ticket->id ?? 0) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No recent tickets</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Top Issues</h6>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        @forelse($topIssues ?? [] as $issue)
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">{{ $issue->category ?? 'N/A' }}</h6>
                                <span class="badge badge-primary badge-pill">{{ $issue->count ?? '0' }}</span>
                            </div>
                            <p class="mb-1 small text-muted">{{ $issue->description ?? 'No description' }}</p>
                        </div>
                        @empty
                        <div class="text-center text-muted py-3">
                            <p>No issues to display</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
