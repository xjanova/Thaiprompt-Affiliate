@extends('layouts.admin')
@section('title', 'AI Monitoring Dashboard')
@push('styles')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    :root {
        --line-green: #06C755;
        --line-green-dark: #00B900;
        --line-green-light: #E8F5E9;
        --line-border: #E0E0E0;
        --line-text: #333333;
        --line-text-secondary: #999999;
        --line-bg: #F7F7F7;
    }

    body {
        background: var(--line-bg);
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    }

    .dashboard-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .dashboard-header {
        background: white;
        border: 1px solid var(--line-border);
        border-radius: 8px;
        padding: 24px;
        margin-bottom: 20px;
    }

    .dashboard-header h1 {
        font-size: 24px;
        font-weight: 600;
        color: var(--line-text);
        margin: 0 0 8px 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .dashboard-header h1 i {
        color: var(--line-green);
    }

    .dashboard-header .subtitle {
        color: var(--line-text-secondary);
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .live-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: var(--line-green-light);
        color: var(--line-green-dark);
        padding: 4px 12px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
    }

    .live-dot {
        width: 6px;
        height: 6px;
        background: var(--line-green);
        border-radius: 50%;
        animation: blink 2s infinite;
    }

    @keyframes blink {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.3; }
    }

    .metrics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 16px;
        margin-bottom: 20px;
    }

    .metric-card {
        background: white;
        border: 1px solid var(--line-border);
        border-radius: 8px;
        padding: 20px;
        transition: border-color 0.2s;
    }

    .metric-card:hover {
        border-color: var(--line-green);
    }

    .metric-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 16px;
    }

    .metric-icon {
        width: 48px;
        height: 48px;
        background: var(--line-green-light);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .metric-icon i {
        font-size: 20px;
        color: var(--line-green);
    }

    .metric-info h3 {
        font-size: 13px;
        color: var(--line-text-secondary);
        margin: 0 0 4px 0;
        font-weight: 500;
    }

    .metric-value {
        font-size: 28px;
        font-weight: 600;
        color: var(--line-text);
        margin: 0;
        line-height: 1;
    }

    .metric-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid #F5F5F5;
    }

    .status-tag {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
    }

    .status-normal {
        background: var(--line-green-light);
        color: var(--line-green-dark);
    }

    .status-warning {
        background: #FFF3E0;
        color: #F57C00;
    }

    .status-critical {
        background: #FFEBEE;
        color: #C62828;
    }

    .metric-trend {
        font-size: 12px;
        color: var(--line-text-secondary);
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .trend-up {
        color: var(--line-green);
    }

    .trend-down {
        color: #C62828;
    }

    .chart-section {
        background: white;
        border: 1px solid var(--line-border);
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 16px;
    }

    .chart-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
        padding-bottom: 16px;
        border-bottom: 1px solid #F5F5F5;
    }

    .chart-title {
        font-size: 16px;
        font-weight: 600;
        color: var(--line-text);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .chart-title i {
        color: var(--line-green);
    }

    .time-range {
        font-size: 12px;
        color: var(--line-text-secondary);
        padding: 4px 12px;
        background: #F5F5F5;
        border-radius: 4px;
    }

    .refresh-indicator {
        position: fixed;
        top: 20px;
        right: 20px;
        background: white;
        border: 1px solid var(--line-border);
        padding: 8px 16px;
        border-radius: 20px;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        color: var(--line-text-secondary);
        z-index: 1000;
        opacity: 0;
        transition: opacity 0.3s;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .refresh-indicator.active {
        opacity: 1;
    }

    .refresh-indicator i {
        color: var(--line-green);
        animation: rotate 1s linear infinite;
    }

    @keyframes rotate {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    canvas {
        max-height: 280px;
    }

    .row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
        gap: 16px;
        margin-bottom: 16px;
    }

    @media (max-width: 768px) {
        .metrics-grid {
            grid-template-columns: 1fr;
        }

        .row {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="dashboard-container">
    <!-- Header -->
    <div class="dashboard-header">
        <h1>
            <i class="fas fa-chart-line"></i>
            AI Monitoring Dashboard
        </h1>
        <div class="subtitle">
            <span class="live-badge">
                <span class="live-dot"></span>
                LIVE
            </span>
            <span>リアルタイムでAIシステムを監視</span>
        </div>
    </div>

    <!-- Refresh Indicator -->
    <div class="refresh-indicator" id="refreshIndicator">
        <i class="fas fa-sync-alt"></i>
        <span>更新中...</span>
    </div>

    <!-- Metrics Grid -->
    <div class="metrics-grid">
        <!-- CPU Usage -->
        <div class="metric-card">
            <div class="metric-header">
                <div class="metric-icon">
                    <i class="fas fa-microchip"></i>
                </div>
                <div class="metric-info">
                    <h3>CPU使用率</h3>
                    <div class="metric-value">
                        <span id="cpu-usage">{{ number_format($summary['cpu_usage'], 1) }}</span><span style="font-size: 16px; color: #999;">%</span>
                    </div>
                </div>
            </div>
            <div class="metric-footer">
                <span id="cpu-status" class="status-tag status-normal">
                    <i class="fas fa-check-circle"></i>
                    正常
                </span>
                <span class="metric-trend">更新済み</span>
            </div>
        </div>

        <!-- Memory Usage -->
        <div class="metric-card">
            <div class="metric-header">
                <div class="metric-icon">
                    <i class="fas fa-memory"></i>
                </div>
                <div class="metric-info">
                    <h3>メモリ使用率</h3>
                    <div class="metric-value">
                        <span id="memory-usage">{{ number_format($summary['memory_usage'], 1) }}</span><span style="font-size: 16px; color: #999;">%</span>
                    </div>
                </div>
            </div>
            <div class="metric-footer">
                <span id="memory-status" class="status-tag status-normal">
                    <i class="fas fa-check-circle"></i>
                    正常
                </span>
                <span class="metric-trend">更新済み</span>
            </div>
        </div>

        <!-- Requests/min -->
        <div class="metric-card">
            <div class="metric-header">
                <div class="metric-icon">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <div class="metric-info">
                    <h3>リクエスト/分</h3>
                    <div class="metric-value" id="requests-per-min">{{ $summary['requests_per_minute'] }}</div>
                </div>
            </div>
            <div class="metric-footer">
                <span id="requests-trend" class="metric-trend">
                    <i class="fas fa-minus"></i>
                    安定
                </span>
                <span class="metric-trend">更新済み</span>
            </div>
        </div>

        <!-- Active Conversations -->
        <div class="metric-card">
            <div class="metric-header">
                <div class="metric-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <div class="metric-info">
                    <h3>アクティブな会話</h3>
                    <div class="metric-value" id="active-conversations">{{ $summary['active_conversations'] }}</div>
                </div>
            </div>
            <div class="metric-footer">
                <span id="conversations-trend" class="metric-trend">
                    <i class="fas fa-minus"></i>
                    安定
                </span>
                <span class="metric-trend">更新済み</span>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row">
        <!-- Requests Chart -->
        <div class="chart-section">
            <div class="chart-header">
                <div class="chart-title">
                    <i class="fas fa-chart-area"></i>
                    リクエスト数の推移
                </div>
                <div class="time-range">過去24時間</div>
            </div>
            <canvas id="requestsChart"></canvas>
        </div>

        <!-- Response Time Chart -->
        <div class="chart-section">
            <div class="chart-header">
                <div class="chart-title">
                    <i class="fas fa-tachometer-alt"></i>
                    応答時間の推移
                </div>
                <div class="time-range">過去24時間</div>
            </div>
            <canvas id="responseTimeChart"></canvas>
        </div>
    </div>

    <div class="row">
        <!-- CPU Chart -->
        <div class="chart-section">
            <div class="chart-header">
                <div class="chart-title">
                    <i class="fas fa-microchip"></i>
                    CPU使用率の推移
                </div>
                <div class="time-range">過去24時間</div>
            </div>
            <canvas id="cpuChart"></canvas>
        </div>

        <!-- Memory Chart -->
        <div class="chart-section">
            <div class="chart-header">
                <div class="chart-title">
                    <i class="fas fa-memory"></i>
                    メモリ使用率の推移
                </div>
                <div class="time-range">過去24時間</div>
            </div>
            <canvas id="memoryChart"></canvas>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let charts = {};
let previousMetrics = {};

// Chart.js default configuration - LINE style
Chart.defaults.font.family = "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif";
Chart.defaults.color = '#999999';

// LINE green color
const LINE_GREEN = '#06C755';
const LINE_GREEN_LIGHT = 'rgba(6, 199, 85, 0.1)';

// Helper function to create LINE-style gradient
function createLineGradient(ctx) {
    const gradient = ctx.createLinearGradient(0, 0, 0, 280);
    gradient.addColorStop(0, 'rgba(6, 199, 85, 0.2)');
    gradient.addColorStop(1, 'rgba(6, 199, 85, 0.01)');
    return gradient;
}

// Initialize charts with LINE styling
function initCharts() {
    const metrics = ['requests', 'response_time', 'cpu', 'memory'];
    const labels = {
        requests: 'リクエスト数',
        response_time: '応答時間 (ms)',
        cpu: 'CPU使用率 (%)',
        memory: 'メモリ使用率 (%)'
    };

    metrics.forEach(metric => {
        $.get('/admin/ai-monitoring/timeseries?metric=' + metric, function(r) {
            const ctx = document.getElementById(metric === 'response_time' ? 'responseTimeChart' :
                                             metric === 'requests' ? 'requestsChart' :
                                             metric + 'Chart');

            if (ctx) {
                const chartCtx = ctx.getContext('2d');
                const gradient = createLineGradient(chartCtx);

                charts[metric] = new Chart(chartCtx, {
                    type: 'line',
                    data: {
                        labels: r.data.labels,
                        datasets: [{
                            label: labels[metric],
                            data: r.data.data,
                            backgroundColor: gradient,
                            borderColor: LINE_GREEN,
                            borderWidth: 2,
                            fill: true,
                            tension: 0.3,
                            pointRadius: 0,
                            pointHoverRadius: 4,
                            pointHoverBackgroundColor: LINE_GREEN,
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
                                backgroundColor: '#333333',
                                padding: 10,
                                cornerRadius: 4,
                                titleFont: {
                                    size: 13,
                                    weight: '600'
                                },
                                bodyFont: {
                                    size: 12
                                },
                                displayColors: false,
                                borderColor: '#E0E0E0',
                                borderWidth: 1
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
                                    color: '#999999',
                                    font: {
                                        size: 11
                                    }
                                }
                            },
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: '#F5F5F5',
                                    drawBorder: false
                                },
                                ticks: {
                                    maxTicksLimit: 5,
                                    color: '#999999',
                                    font: {
                                        size: 11
                                    },
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

// Get status based on value - LINE style
function getStatus(type, value) {
    if (type === 'cpu' || type === 'memory') {
        if (value < 70) return { class: 'status-normal', text: '正常', icon: 'check-circle' };
        if (value < 85) return { class: 'status-warning', text: '警告', icon: 'exclamation-triangle' };
        return { class: 'status-critical', text: '危機', icon: 'exclamation-circle' };
    }
    return { class: 'status-normal', text: '正常', icon: 'check-circle' };
}

// Get trend
function getTrend(current, previous) {
    if (!previous) return { class: '', icon: 'minus', text: '安定' };
    const diff = current - previous;
    if (Math.abs(diff) < 0.1) return { class: '', icon: 'minus', text: '安定' };
    if (diff > 0) return { class: 'trend-up', icon: 'arrow-up', text: `+${diff.toFixed(1)}` };
    return { class: 'trend-down', icon: 'arrow-down', text: `${diff.toFixed(1)}` };
}

// Update metrics
function updateMetrics() {
    $('#refreshIndicator').addClass('active');

    $.get('/admin/ai-monitoring/dashboard-summary', function(r) {
        const data = r.data;

        // Update CPU
        const cpuStatus = getStatus('cpu', data.cpu_usage);
        $('#cpu-usage').text(data.cpu_usage.toFixed(1));
        $('#cpu-status').attr('class', 'status-tag ' + cpuStatus.class)
            .html(`<i class="fas fa-${cpuStatus.icon}"></i> ${cpuStatus.text}`);

        // Update Memory
        const memStatus = getStatus('memory', data.memory_usage);
        $('#memory-usage').text(data.memory_usage.toFixed(1));
        $('#memory-status').attr('class', 'status-tag ' + memStatus.class)
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

        // Store current metrics
        previousMetrics = { ...data };

        setTimeout(() => {
            $('#refreshIndicator').removeClass('active');
        }, 800);
    }).fail(function() {
        $('#refreshIndicator').removeClass('active');
    });
}

// Initialize
$(document).ready(function() {
    initCharts();
    setInterval(updateMetrics, 5000);
});
</script>
@endpush
