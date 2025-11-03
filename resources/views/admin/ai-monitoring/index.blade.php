@extends('layouts.admin')
@section('title', 'AI Monitoring Dashboard')
@push('styles')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        --warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        --info-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        --dark-gradient: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
    }

    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
    }

    .dashboard-header {
        background: white;
        border-radius: 15px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }

    .dashboard-header h1 {
        font-weight: 700;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin: 0;
        font-size: 2.5rem;
    }

    .dashboard-header .subtitle {
        color: #6c757d;
        margin-top: 10px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .live-indicator {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: rgba(40, 167, 69, 0.1);
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 0.85rem;
        color: #28a745;
        font-weight: 600;
    }

    .live-pulse {
        width: 8px;
        height: 8px;
        background: #28a745;
        border-radius: 50%;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.5; transform: scale(1.2); }
    }

    .metric-card {
        background: white;
        border-radius: 20px;
        padding: 25px;
        margin-bottom: 25px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        border: none;
        position: relative;
        overflow: hidden;
    }

    .metric-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 50px rgba(0,0,0,0.15);
    }

    .metric-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--primary-gradient);
    }

    .metric-card.success::before { background: var(--success-gradient); }
    .metric-card.warning::before { background: var(--warning-gradient); }
    .metric-card.info::before { background: var(--info-gradient); }
    .metric-card.dark::before { background: var(--dark-gradient); }

    .metric-icon {
        width: 60px;
        height: 60px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: white;
        margin-bottom: 15px;
        background: var(--primary-gradient);
    }

    .metric-card.success .metric-icon { background: var(--success-gradient); }
    .metric-card.warning .metric-icon { background: var(--warning-gradient); }
    .metric-card.info .metric-icon { background: var(--info-gradient); }
    .metric-card.dark .metric-icon { background: var(--dark-gradient); }

    .metric-label {
        font-size: 0.9rem;
        color: #6c757d;
        font-weight: 500;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .metric-value {
        font-size: 2.5rem;
        font-weight: 700;
        color: #2c3e50;
        margin: 0;
        line-height: 1;
    }

    .metric-trend {
        display: flex;
        align-items: center;
        gap: 5px;
        margin-top: 10px;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .trend-up {
        color: #28a745;
    }

    .trend-down {
        color: #dc3545;
    }

    .trend-neutral {
        color: #6c757d;
    }

    .chart-card {
        background: white;
        border-radius: 20px;
        padding: 30px;
        margin-bottom: 25px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.08);
        border: none;
    }

    .chart-card .card-header {
        background: transparent;
        border: none;
        padding: 0 0 20px 0;
        margin-bottom: 20px;
        border-bottom: 2px solid #f0f0f0;
    }

    .chart-card .card-header h6 {
        font-size: 1.1rem;
        font-weight: 700;
        color: #2c3e50;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .chart-card .card-header h6 i {
        color: #667eea;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .status-excellent {
        background: rgba(40, 167, 69, 0.1);
        color: #28a745;
    }

    .status-good {
        background: rgba(0, 123, 255, 0.1);
        color: #007bff;
    }

    .status-warning {
        background: rgba(255, 193, 7, 0.1);
        color: #ffc107;
    }

    .status-critical {
        background: rgba(220, 53, 69, 0.1);
        color: #dc3545;
    }

    .metric-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #f0f0f0;
    }

    .metric-footer small {
        color: #6c757d;
        font-size: 0.8rem;
    }

    .refresh-indicator {
        position: fixed;
        top: 20px;
        right: 20px;
        background: white;
        padding: 10px 20px;
        border-radius: 30px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.85rem;
        color: #6c757d;
        z-index: 1000;
        opacity: 0;
        transition: opacity 0.3s;
    }

    .refresh-indicator.active {
        opacity: 1;
    }

    .refresh-indicator i {
        color: #667eea;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    canvas {
        max-height: 300px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <h1><i class="fas fa-chart-line"></i> AI Monitoring Dashboard</h1>
        <div class="subtitle">
            <span class="live-indicator">
                <span class="live-pulse"></span>
                LIVE
            </span>
            <span>Real-time monitoring and analytics for your AI systems</span>
        </div>
    </div>

    <!-- Refresh Indicator -->
    <div class="refresh-indicator" id="refreshIndicator">
        <i class="fas fa-sync-alt"></i>
        <span>Updating...</span>
    </div>

    <!-- Metric Cards -->
    <div class="row">
        <!-- CPU Usage Card -->
        <div class="col-lg-3 col-md-6">
            <div class="metric-card">
                <div class="metric-icon">
                    <i class="fas fa-microchip"></i>
                </div>
                <div class="metric-label">CPU Usage</div>
                <h2 class="metric-value">
                    <span id="cpu-usage">{{ number_format($summary['cpu_usage'], 1) }}</span><small style="font-size: 1.5rem; color: #6c757d;">%</small>
                </h2>
                <div class="metric-footer">
                    <span id="cpu-status" class="status-badge status-good">
                        <i class="fas fa-check-circle"></i> Normal
                    </span>
                    <small><i class="far fa-clock"></i> Updated now</small>
                </div>
            </div>
        </div>

        <!-- Memory Usage Card -->
        <div class="col-lg-3 col-md-6">
            <div class="metric-card success">
                <div class="metric-icon">
                    <i class="fas fa-memory"></i>
                </div>
                <div class="metric-label">Memory Usage</div>
                <h2 class="metric-value">
                    <span id="memory-usage">{{ number_format($summary['memory_usage'], 1) }}</span><small style="font-size: 1.5rem; color: #6c757d;">%</small>
                </h2>
                <div class="metric-footer">
                    <span id="memory-status" class="status-badge status-good">
                        <i class="fas fa-check-circle"></i> Normal
                    </span>
                    <small><i class="far fa-clock"></i> Updated now</small>
                </div>
            </div>
        </div>

        <!-- Requests/min Card -->
        <div class="col-lg-3 col-md-6">
            <div class="metric-card info">
                <div class="metric-icon">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <div class="metric-label">Requests/min</div>
                <h2 class="metric-value" id="requests-per-min">{{ $summary['requests_per_minute'] }}</h2>
                <div class="metric-footer">
                    <span id="requests-trend" class="metric-trend trend-neutral">
                        <i class="fas fa-minus"></i> Stable
                    </span>
                    <small><i class="far fa-clock"></i> Updated now</small>
                </div>
            </div>
        </div>

        <!-- Active Conversations Card -->
        <div class="col-lg-3 col-md-6">
            <div class="metric-card dark">
                <div class="metric-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <div class="metric-label">Active Conversations</div>
                <h2 class="metric-value" id="active-conversations">{{ $summary['active_conversations'] }}</h2>
                <div class="metric-footer">
                    <span id="conversations-trend" class="metric-trend trend-neutral">
                        <i class="fas fa-minus"></i> Stable
                    </span>
                    <small><i class="far fa-clock"></i> Updated now</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 1 -->
    <div class="row mt-4">
        <div class="col-lg-6">
            <div class="chart-card">
                <div class="card-header">
                    <h6>
                        <i class="fas fa-chart-area"></i>
                        Requests Volume (24h)
                    </h6>
                </div>
                <div class="card-body p-0">
                    <canvas id="requestsChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="chart-card">
                <div class="card-header">
                    <h6>
                        <i class="fas fa-tachometer-alt"></i>
                        Response Time (24h)
                    </h6>
                </div>
                <div class="card-body p-0">
                    <canvas id="responseTimeChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 2 -->
    <div class="row">
        <div class="col-lg-6">
            <div class="chart-card">
                <div class="card-header">
                    <h6>
                        <i class="fas fa-microchip"></i>
                        CPU Usage Trend (24h)
                    </h6>
                </div>
                <div class="card-body p-0">
                    <canvas id="cpuChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="chart-card">
                <div class="card-header">
                    <h6>
                        <i class="fas fa-memory"></i>
                        Memory Usage Trend (24h)
                    </h6>
                </div>
                <div class="card-body p-0">
                    <canvas id="memoryChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let charts = {};
let previousMetrics = {};

// Chart.js default configuration
Chart.defaults.font.family = "'Inter', sans-serif";
Chart.defaults.color = '#6c757d';

// Helper function to create gradient
function createGradient(ctx, color1, color2) {
    const gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, color1);
    gradient.addColorStop(1, color2);
    return gradient;
}

// Initialize charts with modern styling
function initCharts() {
    const chartConfigs = {
        requests: {
            id: 'requestsChart',
            colors: ['rgba(102, 126, 234, 0.8)', 'rgba(118, 75, 162, 0.2)'],
            borderColor: '#667eea',
            label: 'Requests'
        },
        response_time: {
            id: 'responsetimeChart',
            colors: ['rgba(79, 172, 254, 0.8)', 'rgba(0, 242, 254, 0.2)'],
            borderColor: '#4facfe',
            label: 'Response Time (ms)'
        },
        cpu: {
            id: 'cpuChart',
            colors: ['rgba(17, 153, 142, 0.8)', 'rgba(56, 239, 125, 0.2)'],
            borderColor: '#11998e',
            label: 'CPU Usage (%)'
        },
        memory: {
            id: 'memoryChart',
            colors: ['rgba(240, 147, 251, 0.8)', 'rgba(245, 87, 108, 0.2)'],
            borderColor: '#f093fb',
            label: 'Memory Usage (%)'
        }
    };

    Object.keys(chartConfigs).forEach(metric => {
        $.get('/admin/ai-monitoring/timeseries?metric=' + metric, function(r) {
            const config = chartConfigs[metric];
            const ctx = document.getElementById(config.id);

            if (ctx) {
                const chartCtx = ctx.getContext('2d');
                const gradient = createGradient(chartCtx, config.colors[0], config.colors[1]);

                charts[metric] = new Chart(chartCtx, {
                    type: 'line',
                    data: {
                        labels: r.data.labels,
                        datasets: [{
                            label: config.label,
                            data: r.data.data,
                            backgroundColor: gradient,
                            borderColor: config.borderColor,
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 0,
                            pointHoverRadius: 6,
                            pointHoverBackgroundColor: config.borderColor,
                            pointHoverBorderColor: '#fff',
                            pointHoverBorderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        interaction: {
                            intersect: false,
                            mode: 'index'
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                padding: 12,
                                cornerRadius: 8,
                                titleFont: {
                                    size: 14,
                                    weight: 'bold'
                                },
                                bodyFont: {
                                    size: 13
                                },
                                displayColors: false
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false,
                                    drawBorder: false
                                },
                                ticks: {
                                    maxTicksLimit: 8,
                                    color: '#9ca3af'
                                }
                            },
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)',
                                    drawBorder: false
                                },
                                ticks: {
                                    maxTicksLimit: 6,
                                    color: '#9ca3af',
                                    callback: function(value) {
                                        return value.toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                });
            }
        });
    });
}

// Get status based on value
function getStatus(type, value) {
    if (type === 'cpu' || type === 'memory') {
        if (value < 60) return { class: 'status-excellent', text: 'Excellent', icon: 'check-circle' };
        if (value < 75) return { class: 'status-good', text: 'Good', icon: 'check-circle' };
        if (value < 90) return { class: 'status-warning', text: 'Warning', icon: 'exclamation-triangle' };
        return { class: 'status-critical', text: 'Critical', icon: 'exclamation-circle' };
    }
    return { class: 'status-good', text: 'Normal', icon: 'check-circle' };
}

// Get trend
function getTrend(current, previous) {
    if (!previous) return { class: 'trend-neutral', icon: 'minus', text: 'Stable' };
    const diff = current - previous;
    if (Math.abs(diff) < 0.1) return { class: 'trend-neutral', icon: 'minus', text: 'Stable' };
    if (diff > 0) return { class: 'trend-up', icon: 'arrow-up', text: `+${diff.toFixed(1)}%` };
    return { class: 'trend-down', icon: 'arrow-down', text: `${diff.toFixed(1)}%` };
}

// Update metrics with animations
function updateMetrics() {
    $('#refreshIndicator').addClass('active');

    $.get('/admin/ai-monitoring/dashboard-summary', function(r) {
        const data = r.data;

        // Update CPU
        const cpuStatus = getStatus('cpu', data.cpu_usage);
        $('#cpu-usage').text(data.cpu_usage.toFixed(1));
        $('#cpu-status').attr('class', 'status-badge ' + cpuStatus.class)
            .html(`<i class="fas fa-${cpuStatus.icon}"></i> ${cpuStatus.text}`);

        // Update Memory
        const memStatus = getStatus('memory', data.memory_usage);
        $('#memory-usage').text(data.memory_usage.toFixed(1));
        $('#memory-status').attr('class', 'status-badge ' + memStatus.class)
            .html(`<i class="fas fa-${memStatus.icon}"></i> ${memStatus.text}`);

        // Update Requests
        const reqTrend = getTrend(data.requests_per_minute, previousMetrics.requests_per_minute);
        $('#requests-per-min').text(data.requests_per_minute.toLocaleString());
        $('#requests-trend').attr('class', 'metric-trend ' + reqTrend.class)
            .html(`<i class="fas fa-${reqTrend.icon}"></i> ${reqTrend.text}`);

        // Update Conversations
        const convTrend = getTrend(data.active_conversations, previousMetrics.active_conversations);
        $('#active-conversations').text(data.active_conversations.toLocaleString());
        $('#conversations-trend').attr('class', 'metric-trend ' + convTrend.class)
            .html(`<i class="fas fa-${convTrend.icon}"></i> ${convTrend.text}`);

        // Store current metrics for next comparison
        previousMetrics = { ...data };

        setTimeout(() => {
            $('#refreshIndicator').removeClass('active');
        }, 1000);
    }).fail(function() {
        $('#refreshIndicator').removeClass('active');
    });
}

// Initialize on page load
$(document).ready(function() {
    initCharts();
    setInterval(updateMetrics, 5000);
});
</script>
@endpush
