@extends('layouts.seller')

@section('title', 'AI Insights - Analytics')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-gradient-to-r from-purple-600 via-pink-600 to-red-600 rounded-2xl shadow-2xl p-8 text-white">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-4xl font-bold">🤖 AI-Powered Insights</h1>
                <p class="text-lg mt-2 opacity-90">Machine Learning วิเคราะห์ธุรกิจของคุณ</p>
            </div>
            <a href="{{ route('seller.analytics.index') }}" class="px-6 py-3 bg-white/20 backdrop-blur-sm text-white rounded-xl hover:bg-white/30 transition">
                ← กลับหน้าหลัก
            </a>
        </div>
    </div>

    <!-- AI Insights Cards -->
    @if(!empty($insights))
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach($insights as $insight)
        <div class="bg-white rounded-2xl shadow-xl p-6 border-l-4 {{
            $insight['type'] === 'success' ? 'border-green-500' :
            ($insight['type'] === 'warning' ? 'border-yellow-500' :
            ($insight['type'] === 'danger' ? 'border-red-500' : 'border-blue-500'))
        }}">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-2xl">
                            @if($insight['type'] === 'success') ✅
                            @elseif($insight['type'] === 'warning') ⚠️
                            @elseif($insight['type'] === 'danger') 🚨
                            @else 💡
                            @endif
                        </span>
                        <h3 class="text-xl font-bold text-gray-800">{{ $insight['title'] }}</h3>
                    </div>
                    <p class="text-gray-700 mb-3">{{ $insight['message'] }}</p>
                    <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                        <p class="text-sm font-semibold text-gray-600">💡 คำแนะนำ:</p>
                        <p class="text-sm text-gray-700">{{ $insight['action'] }}</p>
                    </div>
                </div>
                <span class="ml-4 px-3 py-1 bg-{{
                    $insight['priority'] === 'high' ? 'red' :
                    ($insight['priority'] === 'medium' ? 'yellow' : 'gray')
                }}-100 text-{{
                    $insight['priority'] === 'high' ? 'red' :
                    ($insight['priority'] === 'medium' ? 'yellow' : 'gray')
                }}-800 text-xs font-semibold rounded-full">
                    {{ strtoupper($insight['priority']) }}
                </span>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="bg-white rounded-2xl shadow-xl p-12 text-center">
        <div class="text-6xl mb-4">📊</div>
        <p class="text-xl text-gray-600">ยังไม่มีข้อมูลเพียงพอสำหรับ AI Analysis</p>
        <p class="text-gray-500 mt-2">เก็บข้อมูลอย่างน้อย 7 วันเพื่อให้ AI วิเคราะห์</p>
    </div>
    @endif

    <!-- Revenue Forecast -->
    <div class="bg-white rounded-2xl shadow-xl p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">📈 Revenue Forecast (AI Prediction)</h2>
                <p class="text-gray-600">ทำนายรายได้ด้วย Linear Regression ML Model</p>
            </div>
            @if($revenueForecast['confidence'] > 0)
            <div class="text-right">
                <div class="text-3xl font-bold {{ $revenueForecast['trend'] === 'up' ? 'text-green-600' : 'text-red-600' }}">
                    {{ $revenueForecast['trend'] === 'up' ? '📈' : '📉' }}
                </div>
                <p class="text-sm text-gray-600">Confidence: {{ $revenueForecast['confidence'] }}%</p>
            </div>
            @endif
        </div>

        @if($revenueForecast['confidence'] > 0)
        <div class="mb-6">
            <canvas id="forecastChart" height="80"></canvas>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-blue-50 rounded-lg p-4">
                <p class="text-sm text-gray-600 mb-1">Trend Direction</p>
                <p class="text-2xl font-bold {{ $revenueForecast['trend'] === 'up' ? 'text-green-600' : 'text-red-600' }}">
                    {{ $revenueForecast['trend'] === 'up' ? '↗️ Upward' : '↘️ Downward' }}
                </p>
            </div>
            <div class="bg-purple-50 rounded-lg p-4">
                <p class="text-sm text-gray-600 mb-1">Daily Average</p>
                <p class="text-2xl font-bold text-purple-600">฿{{ number_format($revenueForecast['daily_average'], 2) }}</p>
            </div>
            <div class="bg-green-50 rounded-lg p-4">
                <p class="text-sm text-gray-600 mb-1">Next 7 Days Forecast</p>
                <p class="text-2xl font-bold text-green-600">
                    ฿{{ number_format(array_sum(array_column(array_slice($revenueForecast['predictions'], 0, 7), 'predicted_revenue')), 2) }}
                </p>
            </div>
        </div>
        @else
        <p class="text-center text-gray-500 py-8">ต้องการข้อมูลอย่างน้อย 14 วันเพื่อทำนาย</p>
        @endif
    </div>

    <!-- Anomaly Detection -->
    @if(!empty($anomalies))
    <div class="bg-white rounded-2xl shadow-xl p-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">🔍 Anomaly Detection</h2>
        <p class="text-gray-600 mb-4">ตรวจพบพฤติกรรมผิดปกติในข้อมูล (Z-Score > 2.5)</p>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Metric</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Value</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expected</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Severity</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($anomalies as $anomaly)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-900">{{ \Carbon\Carbon::parse($anomaly['date'])->format('M d, Y') }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 font-medium">{{ ucwords(str_replace('_', ' ', $anomaly['metric'])) }}</td>
                        <td class="px-4 py-3 text-sm font-bold {{ $anomaly['type'] === 'spike' ? 'text-green-600' : 'text-red-600' }}">
                            {{ number_format($anomaly['value'], 2) }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ number_format($anomaly['expected'], 2) }}</td>
                        <td class="px-4 py-3 text-sm">
                            <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $anomaly['type'] === 'spike' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $anomaly['type'] === 'spike' ? '⬆️ Spike' : '⬇️ Drop' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $anomaly['severity'] === 'high' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800' }}">
                                {{ strtoupper($anomaly['severity']) }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Funnel Analysis -->
    <div class="bg-white rounded-2xl shadow-xl p-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">🔄 Conversion Funnel</h2>
        <p class="text-gray-600 mb-6">วิเคราะห์การเดินทางของลูกค้า (Last 30 Days)</p>

        <div class="space-y-4">
            @foreach($funnel as $stage)
            <div class="relative">
                <div class="flex items-center justify-between mb-2">
                    <span class="font-semibold text-gray-800">{{ $stage['stage'] }}</span>
                    <div class="text-right">
                        <span class="text-lg font-bold text-gray-800">{{ number_format($stage['count']) }}</span>
                        <span class="text-sm text-gray-600 ml-2">({{ $stage['percentage'] }}%)</span>
                    </div>
                </div>
                <div class="relative w-full h-12 bg-gray-200 rounded-lg overflow-hidden">
                    <div class="absolute h-full bg-gradient-to-r from-blue-500 to-purple-500 transition-all duration-1000"
                        style="width: {{ $stage['percentage'] }}%">
                        <div class="flex items-center justify-center h-full text-white font-semibold">
                            {{ number_format($stage['count']) }} users
                        </div>
                    </div>
                </div>
                @if($stage['drop_off'] > 0)
                <p class="text-xs text-red-600 mt-1">⚠️ {{ $stage['drop_off'] }}% drop-off at this stage</p>
                @endif
            </div>
            @endforeach
        </div>
    </div>

    <!-- Quick Access to Other AI Features -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <a href="{{ route('seller.analytics.segmentation') }}" class="bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl shadow-xl p-6 text-white hover:scale-105 transition transform">
            <div class="text-4xl mb-3">👥</div>
            <h3 class="text-2xl font-bold mb-2">Customer Segmentation</h3>
            <p class="text-sm opacity-90">RFM Analysis - แบ่งกลุ่มลูกค้าอัจฉริยะ</p>
        </a>

        <a href="{{ route('seller.analytics.cohort') }}" class="bg-gradient-to-br from-green-500 to-teal-600 rounded-2xl shadow-xl p-6 text-white hover:scale-105 transition transform">
            <div class="text-4xl mb-3">📊</div>
            <h3 class="text-2xl font-bold mb-2">Cohort Analysis</h3>
            <p class="text-sm opacity-90">ติดตามพฤติกรรมลูกค้าตามช่วงเวลา</p>
        </a>

        <a href="{{ route('seller.analytics.products') }}" class="bg-gradient-to-br from-orange-500 to-red-600 rounded-2xl shadow-xl p-6 text-white hover:scale-105 transition transform">
            <div class="text-4xl mb-3">🏆</div>
            <h3 class="text-2xl font-bold mb-2">Product Performance</h3>
            <p class="text-sm opacity-90">AI Ranking สินค้าขายดี</p>
        </a>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@if($revenueForecast['confidence'] > 0)
<script>
    const forecastCtx = document.getElementById('forecastChart').getContext('2d');
    new Chart(forecastCtx, {
        type: 'line',
        data: {
            labels: @json(array_column($revenueForecast['predictions'], 'date')),
            datasets: [{
                label: 'Predicted Revenue (฿)',
                data: @json(array_column($revenueForecast['predictions'], 'predicted_revenue')),
                borderColor: 'rgb(147, 51, 234)',
                backgroundColor: 'rgba(147, 51, 234, 0.1)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointRadius: 4,
                pointHoverRadius: 6,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Predicted: ฿' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '฿' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
</script>
@endif
@endpush
@endsection
