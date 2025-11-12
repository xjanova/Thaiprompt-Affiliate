@extends('layouts.admin')

@section('title', 'Bot Performance Analytics')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Bot Performance Analytics</h1>
        <a href="{{ route('admin.bot-automation.analytics.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
        </a>
    </div>

    <div class="row">
        <!-- Performance Metrics -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Avg Response Time</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $avgResponseTime ?? '0' }}ms</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Success Rate</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $successRate ?? '0' }}%</div>
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
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Uptime</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $uptime ?? '0' }}%</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-server fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Error Rate</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $errorRate ?? '0' }}%</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
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
                    <h6 class="m-0 font-weight-bold text-primary">Performance Trends</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="performanceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Top Performing Bots</h6>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        @forelse($topBots ?? [] as $bot)
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">{{ $bot->name }}</h6>
                                <small>{{ $bot->success_rate ?? '0' }}%</small>
                            </div>
                            <p class="mb-1 text-muted small">{{ $bot->executions ?? '0' }} executions</p>
                        </div>
                        @empty
                        <div class="text-center text-muted py-3">
                            <p>No performance data available</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Bot Performance Breakdown</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Bot Name</th>
                            <th>Executions</th>
                            <th>Success Rate</th>
                            <th>Avg Response Time</th>
                            <th>Error Count</th>
                            <th>Last Execution</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($performanceData ?? [] as $data)
                        <tr>
                            <td>{{ $data->bot_name ?? 'N/A' }}</td>
                            <td>{{ $data->executions ?? '0' }}</td>
                            <td>{{ $data->success_rate ?? '0' }}%</td>
                            <td>{{ $data->avg_response_time ?? '0' }}ms</td>
                            <td>{{ $data->error_count ?? '0' }}</td>
                            <td>{{ $data->last_execution ?? 'N/A' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">No performance data available</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
