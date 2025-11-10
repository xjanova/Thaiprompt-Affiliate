@extends('layouts.millennium')

@section('title', 'Real-time System Monitoring')

@section('content')
<style>
    .monitoring-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 12px;
        padding: 24px;
        color: white;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        margin-bottom: 24px;
    }

    .monitoring-card.cpu {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }

    .monitoring-card.memory {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }

    .monitoring-card.disk {
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    }

    .monitoring-card.connections {
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    }

    .metric-value {
        font-size: 48px;
        font-weight: 700;
        margin: 12px 0;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
    }

    .metric-label {
        font-size: 14px;
        opacity: 0.9;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .metric-details {
        margin-top: 12px;
        font-size: 13px;
        opacity: 0.85;
    }

    .chart-container {
        background: white;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        margin-left: 8px;
    }

    .status-good {
        background: rgba(255,255,255,0.3);
        color: white;
    }

    .status-warning {
        background: rgba(255,165,0,0.3);
        color: #ff6b00;
    }

    .status-critical {
        background: rgba(255,0,0,0.3);
        color: #ff3333;
    }

    .status-danger {
        background: rgba(139,0,0,0.4);
        color: #ff0000;
        animation: pulse 1s infinite;
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.6; }
    }

    .realtime-indicator {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(34, 197, 94, 0.1);
        padding: 8px 16px;
        border-radius: 20px;
        margin-bottom: 16px;
    }

    .realtime-dot {
        width: 8px;
        height: 8px;
        background: #22c55e;
        border-radius: 50%;
        animation: blink 1.5s infinite;
    }

    @keyframes blink {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.3; }
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-top: 24px;
    }

    .info-item {
        background: #f8fafc;
        padding: 16px;
        border-radius: 8px;
        border-left: 4px solid #667eea;
    }

    .info-label {
        font-size: 12px;
        color: #64748b;
        text-transform: uppercase;
        margin-bottom: 4px;
    }

    .info-value {
        font-size: 16px;
        font-weight: 600;
        color: #1e293b;
    }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>🖥️ Real-time System Monitoring</h1>
        <div class="realtime-indicator">
            <div class="realtime-dot"></div>
            <span style="color: #22c55e; font-weight: 600;">LIVE</span>
            <span style="color: #64748b; font-size: 12px;">Updates every 2s</span>
        </div>
    </div>

    <!-- Metrics Cards Row -->
    <div class="row">
        <!-- CPU Card -->
        <div class="col-md-3">
            <div class="monitoring-card cpu">
                <div class="metric-label">⚡ CPU Usage</div>
                <div class="metric-value" id="cpu-value">{{ number_format($metrics['cpu']['percentage'], 1) }}%</div>
                <span class="status-badge status-{{ $metrics['cpu']['status'] }}" id="cpu-status">
                    {{ strtoupper($metrics['cpu']['status']) }}
                </span>
                <div class="metric-details">
                    <div>Load: <span id="cpu-load">{{ $metrics['cpu']['load_1min'] }}</span></div>
                    <div>Cores: {{ $metrics['cpu']['cpu_cores'] }}</div>
                </div>
            </div>
        </div>

        <!-- Memory Card -->
        <div class="col-md-3">
            <div class="monitoring-card memory">
                <div class="metric-label">💾 Memory Usage</div>
                <div class="metric-value" id="memory-value">{{ number_format($metrics['memory']['percentage'], 1) }}%</div>
                <span class="status-badge status-{{ $metrics['memory']['status'] }}" id="memory-status">
                    {{ strtoupper($metrics['memory']['status']) }}
                </span>
                <div class="metric-details">
                    <div>Used: <span id="memory-used">{{ $metrics['memory']['used'] }}</span></div>
                    <div>Total: {{ $metrics['memory']['total'] }}</div>
                </div>
            </div>
        </div>

        <!-- Disk Card -->
        <div class="col-md-3">
            <div class="monitoring-card disk">
                <div class="metric-label">💿 Disk Usage</div>
                <div class="metric-value" id="disk-value">{{ number_format($metrics['disk']['percentage'], 1) }}%</div>
                <span class="status-badge status-{{ $metrics['disk']['status'] }}" id="disk-status">
                    {{ strtoupper($metrics['disk']['status']) }}
                </span>
                <div class="metric-details">
                    <div>Used: <span id="disk-used">{{ $metrics['disk']['used'] }}</span></div>
                    <div>Total: {{ $metrics['disk']['total'] }}</div>
                </div>
            </div>
        </div>

        <!-- Connections Card -->
        <div class="col-md-3">
            <div class="monitoring-card connections">
                <div class="metric-label">🔌 Active Connections</div>
                <div class="metric-value" id="connections-value">{{ $metrics['connections']['total'] }}</div>
                <span class="status-badge status-good">ACTIVE</span>
                <div class="metric-details">
                    <div>Sessions: <span id="sessions-count">{{ $metrics['connections']['sessions'] }}</span></div>
                    <div>Database: <span id="db-connections">{{ $metrics['connections']['database'] }}</span></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Real-time Charts -->
    <div class="row">
        <!-- CPU Chart -->
        <div class="col-md-6">
            <div class="chart-container">
                <h4>📈 CPU Usage (Last 60 seconds)</h4>
                <canvas id="cpuChart" height="80"></canvas>
            </div>
        </div>

        <!-- Memory Chart -->
        <div class="col-md-6">
            <div class="chart-container">
                <h4>📊 Memory Usage (Last 60 seconds)</h4>
                <canvas id="memoryChart" height="80"></canvas>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Connections Chart -->
        <div class="col-md-6">
            <div class="chart-container">
                <h4>🔌 Active Connections (Last 60 seconds)</h4>
                <canvas id="connectionsChart" height="80"></canvas>
            </div>
        </div>

        <!-- Database & Cache Info -->
        <div class="col-md-6">
            <div class="chart-container">
                <h4>🗄️ Database & Cache</h4>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Database Size</div>
                        <div class="info-value" id="db-size">{{ $metrics['database']['size'] ?? 'N/A' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Tables</div>
                        <div class="info-value" id="db-tables">{{ $metrics['database']['tables'] ?? 0 }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Cache Driver</div>
                        <div class="info-value" id="cache-driver">{{ strtoupper($metrics['cache']['driver'] ?? 'N/A') }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Cache Status</div>
                        <div class="info-value" id="cache-status">{{ strtoupper($metrics['cache']['status'] ?? 'N/A') }}</div>
                    </div>
                    @if(isset($metrics['cache']['hit_rate']))
                    <div class="info-item">
                        <div class="info-label">Cache Hit Rate</div>
                        <div class="info-value" id="cache-hit-rate">{{ $metrics['cache']['hit_rate'] }}%</div>
                    </div>
                    @endif
                    @if(isset($metrics['database']['queries_per_second']))
                    <div class="info-item">
                        <div class="info-label">Queries/Second</div>
                        <div class="info-value" id="queries-per-sec">{{ $metrics['database']['queries_per_second'] }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Application Info -->
    <div class="chart-container">
        <h4>🚀 Application Information</h4>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">PHP Version</div>
                <div class="info-value">{{ $appInfo['php_version'] }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Laravel Version</div>
                <div class="info-value">{{ $appInfo['laravel_version'] }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Environment</div>
                <div class="info-value">{{ strtoupper($appInfo['environment']) }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Cache Driver</div>
                <div class="info-value">{{ strtoupper($appInfo['cache_driver']) }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Session Driver</div>
                <div class="info-value">{{ strtoupper($appInfo['session_driver']) }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Queue Driver</div>
                <div class="info-value">{{ strtoupper($appInfo['queue_driver']) }}</div>
            </div>
        </div>
    </div>

    @if(count($network) > 0)
    <!-- Network Stats -->
    <div class="chart-container">
        <h4>🌐 Network Statistics</h4>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Interface</th>
                        <th>Received</th>
                        <th>Transmitted</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($network as $iface)
                    <tr>
                        <td><strong>{{ $iface['interface'] }}</strong></td>
                        <td>{{ $iface['received'] }}</td>
                        <td>{{ $iface['transmitted'] }}</td>
                        <td>{{ $iface['total'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    // Initialize charts
    const cpuChartCtx = document.getElementById('cpuChart').getContext('2d');
    const memoryChartCtx = document.getElementById('memoryChart').getContext('2d');
    const connectionsChartCtx = document.getElementById('connectionsChart').getContext('2d');

    // Chart configuration
    const chartConfig = {
        type: 'line',
        options: {
            responsive: true,
            maintainAspectRatio: true,
            animation: {
                duration: 500
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    };

    // CPU Chart
    const cpuChart = new Chart(cpuChartCtx, {
        ...chartConfig,
        data: {
            labels: [],
            datasets: [{
                label: 'CPU %',
                data: [],
                borderColor: 'rgb(245, 87, 108)',
                backgroundColor: 'rgba(245, 87, 108, 0.1)',
                fill: true,
                tension: 0.4
            }]
        }
    });

    // Memory Chart
    const memoryChart = new Chart(memoryChartCtx, {
        ...chartConfig,
        data: {
            labels: [],
            datasets: [{
                label: 'Memory %',
                data: [],
                borderColor: 'rgb(79, 172, 254)',
                backgroundColor: 'rgba(79, 172, 254, 0.1)',
                fill: true,
                tension: 0.4
            }]
        }
    });

    // Connections Chart
    const connectionsChart = new Chart(connectionsChartCtx, {
        ...chartConfig,
        data: {
            labels: [],
            datasets: [{
                label: 'Connections',
                data: [],
                borderColor: 'rgb(250, 112, 154)',
                backgroundColor: 'rgba(250, 112, 154, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            ...chartConfig.options,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });

    // Update metrics
    function updateMetrics() {
        fetch('{{ route('seller.analytics.system-monitoring.api-metrics') }}')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const metrics = data.metrics;
                    const history = data.history;

                    // Update CPU
                    document.getElementById('cpu-value').textContent = metrics.cpu.percentage.toFixed(1) + '%';
                    document.getElementById('cpu-load').textContent = metrics.cpu.load_1min;
                    updateStatusBadge('cpu-status', metrics.cpu.status);

                    // Update Memory
                    document.getElementById('memory-value').textContent = metrics.memory.percentage.toFixed(1) + '%';
                    document.getElementById('memory-used').textContent = metrics.memory.used;
                    updateStatusBadge('memory-status', metrics.memory.status);

                    // Update Disk
                    document.getElementById('disk-value').textContent = metrics.disk.percentage.toFixed(1) + '%';
                    document.getElementById('disk-used').textContent = metrics.disk.used;
                    updateStatusBadge('disk-status', metrics.disk.status);

                    // Update Connections
                    document.getElementById('connections-value').textContent = metrics.connections.total;
                    document.getElementById('sessions-count').textContent = metrics.connections.sessions;
                    document.getElementById('db-connections').textContent = metrics.connections.database;

                    // Update charts with history
                    if (history && history.length > 0) {
                        const labels = history.map(h => h.timestamp);
                        const cpuData = history.map(h => h.cpu);
                        const memoryData = history.map(h => h.memory);
                        const connectionsData = history.map(h => h.connections);

                        cpuChart.data.labels = labels;
                        cpuChart.data.datasets[0].data = cpuData;
                        cpuChart.update('none');

                        memoryChart.data.labels = labels;
                        memoryChart.data.datasets[0].data = memoryData;
                        memoryChart.update('none');

                        connectionsChart.data.labels = labels;
                        connectionsChart.data.datasets[0].data = connectionsData;
                        connectionsChart.update('none');
                    }
                }
            })
            .catch(error => {
                console.error('Error fetching metrics:', error);
            });
    }

    function updateStatusBadge(elementId, status) {
        const element = document.getElementById(elementId);
        element.className = 'status-badge status-' + status;
        element.textContent = status.toUpperCase();
    }

    // Update every 2 seconds
    setInterval(updateMetrics, 2000);

    // Initial update
    updateMetrics();
</script>
@endsection
