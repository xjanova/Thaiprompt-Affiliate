@extends('layouts.admin')

@section('title', 'Bot Analytics Dashboard')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Bot Analytics Dashboard</h1>
    </div>

    <div class="row">
        <!-- Summary Cards -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Executions</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalExecutions ?? '0' }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-robot fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Active Bots</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $activeBots ?? '0' }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-cogs fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Avg Response Time</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $avgResponseTime ?? '0' }}ms</div>
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
        <!-- Analytics Chart -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Bot Performance Overview</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="botPerformanceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Access</h6>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <a href="{{ route('admin.bot-automation.analytics.executions') }}" class="list-group-item list-group-item-action">
                            <i class="fas fa-play-circle mr-2"></i> View Executions
                        </a>
                        <a href="{{ route('admin.bot-automation.analytics.engagement') }}" class="list-group-item list-group-item-action">
                            <i class="fas fa-users mr-2"></i> Engagement Metrics
                        </a>
                        <a href="{{ route('admin.bot-automation.analytics.performance') }}" class="list-group-item list-group-item-action">
                            <i class="fas fa-tachometer-alt mr-2"></i> Performance Details
                        </a>
                        <a href="{{ route('admin.bot-automation.analytics.platforms') }}" class="list-group-item list-group-item-action">
                            <i class="fas fa-layer-group mr-2"></i> Platform Stats
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
