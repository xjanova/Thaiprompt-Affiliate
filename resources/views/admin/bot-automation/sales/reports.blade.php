@extends('layouts.admin')

@section('title', 'Sales Reports')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Sales Reports</h1>
        <div>
            <button class="btn btn-success" onclick="exportReport('excel')">
                <i class="fas fa-file-excel mr-2"></i>Export Excel
            </button>
            <button class="btn btn-danger" onclick="exportReport('pdf')">
                <i class="fas fa-file-pdf mr-2"></i>Export PDF
            </button>
            <a href="{{ route('admin.bot-automation.sales.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-2"></i>Back
            </a>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Report Filters</h6>
        </div>
        <div class="card-body">
            <form>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="dateFrom">Date From</label>
                            <input type="date" class="form-control" id="dateFrom" name="date_from">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="dateTo">Date To</label>
                            <input type="date" class="form-control" id="dateTo" name="date_to">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="bot">Bot</label>
                            <select class="form-control" id="bot" name="bot_id">
                                <option value="">All Bots</option>
                                @foreach($bots ?? [] as $bot)
                                <option value="{{ $bot->id }}">{{ $bot->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-search mr-2"></i>Generate Report
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Revenue</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">${{ number_format($reportData['total_revenue'] ?? 0, 2) }}</div>
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
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Closed Deals</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $reportData['closed_deals'] ?? '0' }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-handshake fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Avg Deal Size</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">${{ number_format($reportData['avg_deal_size'] ?? 0, 2) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-bar fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Win Rate</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($reportData['win_rate'] ?? 0, 1) }}%</div>
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
                    <h6 class="m-0 font-weight-bold text-primary">Revenue Trend</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="revenueTrendChart"></canvas>
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
                                <small class="text-success">${{ number_format($bot->revenue ?? 0, 0) }}</small>
                            </div>
                            <p class="mb-1 small text-muted">{{ $bot->deals_count ?? '0' }} deals closed</p>
                        </div>
                        @empty
                        <div class="text-center text-muted py-3">
                            <p>No data available</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Sales by Bot</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Bot Name</th>
                            <th>Total Leads</th>
                            <th>Conversions</th>
                            <th>Conversion Rate</th>
                            <th>Total Revenue</th>
                            <th>Avg Deal Size</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($salesByBot ?? [] as $bot)
                        <tr>
                            <td>{{ $bot->name ?? 'N/A' }}</td>
                            <td>{{ $bot->total_leads ?? '0' }}</td>
                            <td>{{ $bot->conversions ?? '0' }}</td>
                            <td>{{ number_format($bot->conversion_rate ?? 0, 1) }}%</td>
                            <td>${{ number_format($bot->total_revenue ?? 0, 2) }}</td>
                            <td>${{ number_format($bot->avg_deal_size ?? 0, 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">No data available</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function exportReport(format) {
    alert('Exporting report as ' + format.toUpperCase() + '... This feature is under development.');
}
</script>
@endsection
