@extends('layouts.admin')
@section('title', 'AI Monitoring Dashboard')
@push('styles')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    :root {
        --line-green: #06C755;
        --purple-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --blue-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        --pink-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        --orange-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        --line-border: #E0E0E0;
        --line-text: #333333;
        --line-text-secondary: #999999;
        --line-bg: #F7F7F7;
    }

    body {
        background: var(--line-bg);
        font-family: 'Sarabun', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    }

    .dashboard-container {
        width: 100%;
        padding: 24px;
    }

    .dashboard-header {
        background: white;
        border: 1px solid var(--line-border);
        border-radius: 12px;
        padding: 28px 32px;
        margin-bottom: 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
    }
    .dark .dashboard-header {
        background: #1e293b;
        border-color: #475569;
    }

    .header-left h1 {
        font-size: 28px;
        font-weight: 700;
        color: var(--line-text);
        margin: 0 0 8px 0;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .header-left h1 i {
        background: var(--purple-gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .header-left .subtitle {
        color: var(--line-text-secondary);
        font-size: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .live-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: linear-gradient(135deg, rgba(6, 199, 85, 0.15) 0%, rgba(6, 199, 85, 0.05) 100%);
        color: var(--line-green);
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
        border: 1px solid rgba(6, 199, 85, 0.2);
    }

    .live-dot {
        width: 8px;
        height: 8px;
        background: var(--line-green);
        border-radius: 50%;
        animation: blink 2s infinite;
        box-shadow: 0 0 8px var(--line-green);
    }

    @keyframes blink {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.4; transform: scale(0.9); }
    }

    .metrics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
        margin-bottom: 24px;
    }

    .metric-card {
        background: white;
        border: 1px solid var(--line-border);
        border-radius: 12px;
        padding: 24px;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    .dark .metric-card {
        background: #1e293b;
        border-color: #475569;
    }

    .metric-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--purple-gradient);
    }

    .metric-card:nth-child(2)::before {
        background: var(--blue-gradient);
    }

    .metric-card:nth-child(3)::before {
        background: var(--pink-gradient);
    }

    .metric-card:nth-child(4)::before {
        background: var(--orange-gradient);
    }

    .metric-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        border-color: transparent;
    }

    .metric-header {
        display: flex;
        align-items: flex-start;
        gap: 16px;
        margin-bottom: 20px;
    }

    .metric-icon {
        width: 56px;
        height: 56px;
        background: var(--purple-gradient);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    .metric-card:nth-child(2) .metric-icon {
        background: var(--blue-gradient);
        box-shadow: 0 4px 12px rgba(79, 172, 254, 0.3);
    }

    .metric-card:nth-child(3) .metric-icon {
        background: var(--pink-gradient);
        box-shadow: 0 4px 12px rgba(240, 147, 251, 0.3);
    }

    .metric-card:nth-child(4) .metric-icon {
        background: var(--orange-gradient);
        box-shadow: 0 4px 12px rgba(250, 112, 154, 0.3);
    }

    .metric-icon i {
        font-size: 24px;
        color: white;
    }

    .metric-info {
        flex: 1;
    }

    .metric-info h3 {
        font-size: 14px;
        color: var(--line-text-secondary);
        margin: 0 0 8px 0;
        font-weight: 500;
    }

    .metric-value {
        font-size: 32px;
        font-weight: 700;
        color: var(--line-text);
        margin: 0;
        line-height: 1;
    }

    .metric-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 16px;
        padding-top: 16px;
        border-top: 1px solid #F5F5F5;
    }

    .status-tag {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
    }

    .status-normal {
        background: linear-gradient(135deg, rgba(6, 199, 85, 0.1) 0%, rgba(6, 199, 85, 0.05) 100%);
        color: var(--line-green);
    }

    .status-warning {
        background: linear-gradient(135deg, rgba(255, 152, 0, 0.1) 0%, rgba(255, 152, 0, 0.05) 100%);
        color: #FF9800;
    }

    .status-critical {
        background: linear-gradient(135deg, rgba(244, 67, 54, 0.1) 0%, rgba(244, 67, 54, 0.05) 100%);
        color: #F44336;
    }

    .metric-trend {
        font-size: 13px;
        color: var(--line-text-secondary);
        display: flex;
        align-items: center;
        gap: 4px;
        font-weight: 500;
    }

    .trend-up {
        color: var(--line-green);
    }

    .trend-down {
        color: #F44336;
    }

    .charts-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(550px, 1fr));
        gap: 20px;
        margin-bottom: 24px;
    }

    .chart-section {
        background: white;
        border: 1px solid var(--line-border);
        border-radius: 12px;
        padding: 24px;
        transition: all 0.3s ease;
    }
    .dark .chart-section {
        background: #1e293b;
        border-color: #475569;
    }
    .chart-section:hover {
        box-shadow: 0 8px 24px rgba(0,0,0,0.08);
    }
    .dark .chart-section:hover {
        box-shadow: 0 8px 24px rgba(0,0,0,0.3);
    }

    .chart-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 2px solid #F5F5F5;
    }
    .dark .chart-header {
        border-bottom-color: #334155;
    }
    .chart-title {
        font-size: 18px;
        font-weight: 700;
        color: var(--line-text);
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .dark .chart-title {
        color: #f3f4f6;
    }

    .chart-section:nth-child(1) .chart-title i {
        background: var(--purple-gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .chart-section:nth-child(2) .chart-title i {
        background: var(--blue-gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .time-range {
        font-size: 13px;
        color: var(--line-text-secondary);
        padding: 6px 14px;
        background: linear-gradient(135deg, #F5F5F5 0%, #FAFAFA 100%);
        border-radius: 6px;
        font-weight: 500;
    }

    .refresh-indicator {
        position: fixed;
        top: 24px;
        right: 24px;
        background: white;
        border: 1px solid var(--line-border);
        padding: 10px 18px;
        border-radius: 24px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 14px;
        color: var(--line-text);
        z-index: 1000;
        opacity: 0;
        transition: opacity 0.3s;
        box-shadow: 0 4px 16px rgba(0,0,0,0.12);
        font-weight: 500;
    }
    .dark .refresh-indicator {
        background: #1e293b;
        border-color: #475569;
        color: #f3f4f6;
    }

    .refresh-indicator.active {
        opacity: 1;
    }

    .refresh-indicator i {
        background: var(--purple-gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        animation: rotate 1s linear infinite;
    }

    @keyframes rotate {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    canvas {
        max-height: 320px;
    }

    @media (max-width: 1200px) {
        .charts-row {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .metrics-grid {
            grid-template-columns: 1fr;
        }

        .dashboard-header {
            padding: 20px;
        }

        .header-left h1 {
            font-size: 24px;
        }
    }
</style>
@endpush

@section('content')
<div class="dashboard-container">
    <!-- Header -->
    <div class="dashboard-header">
        <div class="header-left">
            <h1>
                <i class="fas fa-chart-line"></i>
                แดชบอร์ดติดตาม AI
            </h1>
            <div class="subtitle">
                <span class="live-badge">
                    <span class="live-dot"></span>
                    สดตอนนี้
                </span>
                <span>ติดตามระบบ AI แบบเรียลไทม์</span>
            </div>
        </div>
    </div>

    <!-- Refresh Indicator -->
    <div class="refresh-indicator" id="refreshIndicator">
        <i class="fas fa-sync-alt"></i>
        <span>กำลังอัปเดต...</span>
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
                    <h3>การใช้งาน CPU</h3>
                    <div class="metric-value">
                        <span id="cpu-usage">{{ number_format($summary['cpu_usage'], 1) }}</span><span style="font-size: 18px; color: #999;">%</span>
                    </div>
                </div>
            </div>
            <div class="metric-footer">
                <span id="cpu-status" class="status-tag status-normal">
                    <i class="fas fa-check-circle"></i>
                    ปกติ
                </span>
                <span class="metric-trend">อัปเดตแล้ว</span>
            </div>
        </div>

        <!-- Memory Usage -->
        <div class="metric-card">
            <div class="metric-header">
                <div class="metric-icon">
                    <i class="fas fa-memory"></i>
                </div>
                <div class="metric-info">
                    <h3>การใช้งานหน่วยความจำ</h3>
                    <div class="metric-value">
                        <span id="memory-usage">{{ number_format($summary['memory_usage'], 1) }}</span><span style="font-size: 18px; color: #999;">%</span>
                    </div>
                </div>
            </div>
            <div class="metric-footer">
                <span id="memory-status" class="status-tag status-normal">
                    <i class="fas fa-check-circle"></i>
                    ปกติ
                </span>
                <span class="metric-trend">อัปเดตแล้ว</span>
            </div>
        </div>

        <!-- Requests/min -->
        <div class="metric-card">
            <div class="metric-header">
                <div class="metric-icon">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <div class="metric-info">
                    <h3>คำขอต่อนาที</h3>
                    <div class="metric-value" id="requests-per-min">{{ $summary['requests_per_minute'] }}</div>
                </div>
            </div>
            <div class="metric-footer">
                <span id="requests-trend" class="metric-trend">
                    <i class="fas fa-minus"></i>
                    คงที่
                </span>
                <span class="metric-trend">อัปเดตแล้ว</span>
            </div>
        </div>

        <!-- Active Conversations -->
        <div class="metric-card">
            <div class="metric-header">
                <div class="metric-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <div class="metric-info">
                    <h3>การสนทนาที่ใช้งานอยู่</h3>
                    <div class="metric-value" id="active-conversations">{{ $summary['active_conversations'] }}</div>
                </div>
            </div>
            <div class="metric-footer">
                <span id="conversations-trend" class="metric-trend">
                    <i class="fas fa-minus"></i>
                    คงที่
                </span>
                <span class="metric-trend">อัปเดตแล้ว</span>
            </div>
        </div>
    </div>

    <!-- Charts Row 1 -->
    <div class="charts-row">
        <!-- Requests Chart -->
        <div class="chart-section">
            <div class="chart-header">
                <div class="chart-title">
                    <i class="fas fa-chart-area"></i>
                    ปริมาณคำขอ
                </div>
                <div class="time-range">24 ชั่วโมงที่ผ่านมา</div>
            </div>
            <canvas id="requestsChart"></canvas>
        </div>

        <!-- Response Time Chart -->
        <div class="chart-section">
            <div class="chart-header">
                <div class="chart-title">
                    <i class="fas fa-tachometer-alt"></i>
                    เวลาตอบสนอง
                </div>
                <div class="time-range">24 ชั่วโมงที่ผ่านมา</div>
            </div>
            <canvas id="responseTimeChart"></canvas>
        </div>
    </div>

    <!-- Charts Row 2 -->
    <div class="charts-row">
        <!-- CPU Chart -->
        <div class="chart-section">
            <div class="chart-header">
                <div class="chart-title">
                    <i class="fas fa-microchip"></i>
                    แนวโน้มการใช้ CPU
                </div>
                <div class="time-range">24 ชั่วโมงที่ผ่านมา</div>
            </div>
            <canvas id="cpuChart"></canvas>
        </div>

        <!-- Memory Chart -->
        <div class="chart-section">
            <div class="chart-header">
                <div class="chart-title">
                    <i class="fas fa-memory"></i>
                    แนวโน้มการใช้หน่วยความจำ
                </div>
                <div class="time-range">24 ชั่วโมงที่ผ่านมา</div>
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

// Chart.js default configuration
Chart.defaults.font.family = "'Sarabun', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif";
Chart.defaults.color = '#999999';

// Gradient colors for each chart
const chartColors = {
    requests: {
        borderColor: '#667eea',
        gradientStart: 'rgba(102, 126, 234, 0.3)',
        gradientEnd: 'rgba(102, 126, 234, 0.01)'
    },
    response_time: {
        borderColor: '#4facfe',
        gradientStart: 'rgba(79, 172, 254, 0.3)',
        gradientEnd: 'rgba(79, 172, 254, 0.01)'
    },
    cpu: {
        borderColor: '#f093fb',
        gradientStart: 'rgba(240, 147, 251, 0.3)',
        gradientEnd: 'rgba(240, 147, 251, 0.01)'
    },
    memory: {
        borderColor: '#fa709a',
        gradientStart: 'rgba(250, 112, 154, 0.3)',
        gradientEnd: 'rgba(250, 112, 154, 0.01)'
    }
};

// Helper function to create gradient
function createGradient(ctx, colors) {
    const gradient = ctx.createLinearGradient(0, 0, 0, 320);
    gradient.addColorStop(0, colors.gradientStart);
    gradient.addColorStop(1, colors.gradientEnd);
    return gradient;
}

// Initialize charts with colorful styling
function initCharts() {
    const metrics = ['requests', 'response_time', 'cpu', 'memory'];
    const labels = {
        requests: 'คำขอ',
        response_time: 'เวลาตอบสนอง (ms)',
        cpu: 'CPU (%)',
        memory: 'หน่วยความจำ (%)'
    };

    metrics.forEach(metric => {
        $.get('/admin/ai-monitoring/timeseries?metric=' + metric, function(r) {
            const ctx = document.getElementById(metric === 'response_time' ? 'responseTimeChart' :
                                             metric === 'requests' ? 'requestsChart' :
                                             metric + 'Chart');

            if (ctx) {
                const chartCtx = ctx.getContext('2d');
                const colors = chartColors[metric];
                const gradient = createGradient(chartCtx, colors);

                charts[metric] = new Chart(chartCtx, {
                    type: 'line',
                    data: {
                        labels: r.data.labels,
                        datasets: [{
                            label: labels[metric],
                            data: r.data.data,
                            backgroundColor: gradient,
                            borderColor: colors.borderColor,
                            borderWidth: 2.5,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 0,
                            pointHoverRadius: 5,
                            pointHoverBackgroundColor: colors.borderColor,
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
                                backgroundColor: 'rgba(0, 0, 0, 0.85)',
                                padding: 12,
                                cornerRadius: 8,
                                titleFont: {
                                    size: 14,
                                    weight: '600',
                                    family: "'Sarabun', sans-serif"
                                },
                                bodyFont: {
                                    size: 13,
                                    family: "'Sarabun', sans-serif"
                                },
                                displayColors: false,
                                borderColor: colors.borderColor,
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
                                    maxTicksLimit: 10,
                                    color: '#999999',
                                    font: {
                                        size: 12,
                                        family: "'Sarabun', sans-serif"
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
                                    maxTicksLimit: 6,
                                    color: '#999999',
                                    font: {
                                        size: 12,
                                        family: "'Sarabun', sans-serif"
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

// Get status based on value
function getStatus(type, value) {
    if (type === 'cpu' || type === 'memory') {
        if (value < 70) return { class: 'status-normal', text: 'ปกติ', icon: 'check-circle' };
        if (value < 85) return { class: 'status-warning', text: 'เตือน', icon: 'exclamation-triangle' };
        return { class: 'status-critical', text: 'วิกฤต', icon: 'exclamation-circle' };
    }
    return { class: 'status-normal', text: 'ปกติ', icon: 'check-circle' };
}

// Get trend
function getTrend(current, previous) {
    if (!previous) return { class: '', icon: 'minus', text: 'คงที่' };
    const diff = current - previous;
    if (Math.abs(diff) < 0.1) return { class: '', icon: 'minus', text: 'คงที่' };
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
