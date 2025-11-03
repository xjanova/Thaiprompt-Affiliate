@extends('layouts.admin')
@section('title', 'AI Monitoring Dashboard')
@push('styles')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endpush
@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-3">
            <div class="card"><div class="card-body text-center">
                <h6>CPU Usage</h6>
                <h2 id="cpu-usage">{{ number_format($summary['cpu_usage'], 1) }}%</h2>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body text-center">
                <h6>Memory Usage</h6>
                <h2 id="memory-usage">{{ number_format($summary['memory_usage'], 1) }}%</h2>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body text-center">
                <h6>Requests/min</h6>
                <h2 id="requests-per-min">{{ $summary['requests_per_minute'] }}</h2>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body text-center">
                <h6>Active Conversations</h6>
                <h2 id="active-conversations">{{ $summary['active_conversations'] }}</h2>
            </div></div>
        </div>
    </div>
    <div class="row mt-4">
        <div class="col-md-6"><div class="card">
            <div class="card-header"><h6>Requests (24h)</h6></div>
            <div class="card-body"><canvas id="requestsChart"></canvas></div>
        </div></div>
        <div class="col-md-6"><div class="card">
            <div class="card-header"><h6>Response Time (24h)</h6></div>
            <div class="card-body"><canvas id="responseTimeChart"></canvas></div>
        </div></div>
    </div>
    <div class="row mt-4">
        <div class="col-md-6"><div class="card">
            <div class="card-header"><h6>CPU Usage (24h)</h6></div>
            <div class="card-body"><canvas id="cpuChart"></canvas></div>
        </div></div>
        <div class="col-md-6"><div class="card">
            <div class="card-header"><h6>Memory Usage (24h)</h6></div>
            <div class="card-body"><canvas id="memoryChart"></canvas></div>
        </div></div>
    </div>
</div>
@endsection
@push('scripts')
<script>
let charts = {};
function initCharts() {
    ['requests', 'response_time', 'cpu', 'memory'].forEach(metric => {
        $.get('/admin/ai-monitoring/timeseries?metric=' + metric, function(r) {
            const ctx = document.getElementById(metric.replace('_', '') + 'Chart');
            if (ctx) charts[metric] = new Chart(ctx, {
                type: 'line',
                data: {labels: r.data.labels, datasets: [{label: metric, data: r.data.data, borderColor: 'rgb(75,192,192)', tension: 0.1}]},
                options: {responsive: true, scales: {y: {beginAtZero: true}}}
            });
        });
    });
}
function updateMetrics() {
    $.get('/admin/ai-monitoring/dashboard-summary', function(r) {
        $('#cpu-usage').text(r.data.cpu_usage.toFixed(1) + '%');
        $('#memory-usage').text(r.data.memory_usage.toFixed(1) + '%');
        $('#requests-per-min').text(r.data.requests_per_minute);
        $('#active-conversations').text(r.data.active_conversations);
    });
}
$(document).ready(function() {
    initCharts();
    setInterval(updateMetrics, 5000);
});
</script>
@endpush
