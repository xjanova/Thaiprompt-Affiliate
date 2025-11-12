@extends('layouts.admin')

@section('title', 'Bot Sales Dashboard')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Bot Sales Dashboard</h1>
        <div>
            <a href="{{ route('admin.bot-automation.sales.reports') }}" class="btn btn-info">
                <i class="fas fa-chart-bar mr-2"></i>View Reports
            </a>
            <a href="{{ route('admin.bot-automation.sales.pipeline') }}" class="btn btn-primary">
                <i class="fas fa-funnel-dollar mr-2"></i>View Pipeline
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Revenue</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">${{ number_format($totalRevenue ?? 0, 2) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Conversions</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalConversions ?? '0' }}</div>
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
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Leads</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalLeads ?? '0' }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Conversion Rate</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($conversionRate ?? 0, 1) }}%</div>
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
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Sales Overview</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Access</h6>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <a href="{{ route('admin.bot-automation.sales.leads.index') }}" class="list-group-item list-group-item-action">
                            <i class="fas fa-user-plus mr-2"></i>Manage Leads
                            <span class="badge badge-primary float-right">{{ $totalLeads ?? '0' }}</span>
                        </a>
                        <a href="{{ route('admin.bot-automation.sales.conversions.index') }}" class="list-group-item list-group-item-action">
                            <i class="fas fa-chart-line mr-2"></i>View Conversions
                            <span class="badge badge-success float-right">{{ $totalConversions ?? '0' }}</span>
                        </a>
                        <a href="{{ route('admin.bot-automation.sales.pipeline') }}" class="list-group-item list-group-item-action">
                            <i class="fas fa-funnel-dollar mr-2"></i>Sales Pipeline
                        </a>
                        <a href="{{ route('admin.bot-automation.sales.reports') }}" class="list-group-item list-group-item-action">
                            <i class="fas fa-file-alt mr-2"></i>Sales Reports
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Recent Sales Activity</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Lead</th>
                            <th>Bot</th>
                            <th>Stage</th>
                            <th>Value</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentActivity ?? [] as $activity)
                        <tr>
                            <td>{{ isset($activity->date) ? date('M d, Y', strtotime($activity->date)) : 'N/A' }}</td>
                            <td>{{ $activity->lead_name ?? 'N/A' }}</td>
                            <td>{{ $activity->bot_name ?? 'N/A' }}</td>
                            <td>{{ $activity->stage ?? 'N/A' }}</td>
                            <td>${{ number_format($activity->value ?? 0, 2) }}</td>
                            <td>
                                @if($activity->status == 'converted')
                                    <span class="badge badge-success">Converted</span>
                                @elseif($activity->status == 'in_progress')
                                    <span class="badge badge-primary">In Progress</span>
                                @else
                                    <span class="badge badge-secondary">{{ ucfirst($activity->status) }}</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.bot-automation.sales.leads.show', $activity->lead_id ?? 0) }}" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">No recent activity</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
