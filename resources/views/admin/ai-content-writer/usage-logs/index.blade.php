@extends('layouts.admin')

@section('title', $pageTitle ?? 'Usage Logs')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Usage Logs</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">ติดตามการใช้งาน API และค่าใช้จ่าย</p>
        </div>
        <a href="{{ route('admin.ai-content-writer.dashboard') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
            <i class="fas fa-arrow-left mr-2"></i> กลับ Dashboard
        </a>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-5 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm">Tokens ทั้งหมด</p>
                    <p class="text-2xl font-bold">{{ number_format($summary['total_tokens'] ?? 0) }}</p>
                </div>
                <i class="fas fa-chart-bar text-3xl text-purple-300"></i>
            </div>
        </div>
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-5 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm">ค่าใช้จ่ายรวม</p>
                    <p class="text-2xl font-bold">${{ number_format($summary['total_cost'] ?? 0, 2) }}</p>
                </div>
                <i class="fas fa-coins text-3xl text-green-300"></i>
            </div>
        </div>
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-5 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm">API Calls</p>
                    <p class="text-2xl font-bold">{{ number_format($summary['total_calls'] ?? 0) }}</p>
                </div>
                <i class="fas fa-server text-3xl text-blue-300"></i>
            </div>
        </div>
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl p-5 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-orange-100 text-sm">เวลาเฉลี่ย</p>
                    <p class="text-2xl font-bold">{{ number_format($summary['avg_response_time'] ?? 0, 1) }}s</p>
                </div>
                <i class="fas fa-clock text-3xl text-orange-300"></i>
            </div>
        </div>
    </div>

    {{-- Provider Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach(['openai' => ['OpenAI', 'fa-robot', 'green'], 'claude' => ['Claude', 'fa-brain', 'purple'], 'gemini' => ['Gemini', 'fa-gem', 'blue']] as $key => [$name, $icon, $color])
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3 mb-4">
                <div class="p-2 rounded-lg bg-{{ $color }}-100 dark:bg-{{ $color }}-900/30 text-{{ $color }}-600 dark:text-{{ $color }}-400">
                    <i class="fas {{ $icon }} fa-lg"></i>
                </div>
                <h3 class="font-semibold text-gray-900 dark:text-white">{{ $name }}</h3>
            </div>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500 dark:text-gray-400">Calls</span>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($providerStats[$key]['calls'] ?? 0) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500 dark:text-gray-400">Tokens</span>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($providerStats[$key]['tokens'] ?? 0) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500 dark:text-gray-400">Cost</span>
                    <span class="font-semibold text-{{ $color }}-600 dark:text-{{ $color }}-400">${{ number_format($providerStats[$key]['cost'] ?? 0, 2) }}</span>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
        <form method="GET" class="flex flex-wrap gap-4">
            <select name="provider" class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                <option value="">ทุก Provider</option>
                <option value="openai" {{ request('provider') == 'openai' ? 'selected' : '' }}>OpenAI</option>
                <option value="claude" {{ request('provider') == 'claude' ? 'selected' : '' }}>Claude</option>
                <option value="gemini" {{ request('provider') == 'gemini' ? 'selected' : '' }}>Gemini</option>
            </select>
            <select name="action" class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                <option value="">ทุก Action</option>
                <option value="generate" {{ request('action') == 'generate' ? 'selected' : '' }}>Generate</option>
                <option value="playground" {{ request('action') == 'playground' ? 'selected' : '' }}>Playground</option>
            </select>
            <select name="date" class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                <option value="">ทุกช่วงเวลา</option>
                <option value="today" {{ request('date') == 'today' ? 'selected' : '' }}>วันนี้</option>
                <option value="week" {{ request('date') == 'week' ? 'selected' : '' }}>7 วันที่ผ่านมา</option>
                <option value="month" {{ request('date') == 'month' ? 'selected' : '' }}>30 วันที่ผ่านมา</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">
                <i class="fas fa-filter mr-2"></i> กรอง
            </button>
            @if(request()->hasAny(['provider', 'action', 'date']))
            <a href="{{ route('admin.ai-content-writer.usage-logs') }}" class="px-4 py-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                <i class="fas fa-times mr-2"></i> ล้าง
            </a>
            @endif
        </form>
    </div>

    {{-- Logs Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">วันที่/เวลา</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Provider</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Model</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Action</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tokens</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Cost</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Time</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($logs as $log)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                            {{ $log->created_at->format('d/m/Y H:i:s') }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full {{ $log->provider === 'openai' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : ($log->provider === 'claude' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400') }}">
                                {{ ucfirst($log->provider) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                            {{ $log->model }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                            {{ $log->action }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white text-right">
                            {{ number_format($log->total_tokens ?? 0) }}
                        </td>
                        <td class="px-6 py-4 text-sm text-green-600 dark:text-green-400 text-right font-medium">
                            ${{ number_format($log->cost_usd ?? 0, 4) }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 text-right">
                            {{ number_format(($log->response_time_ms ?? 0) / 1000, 2) }}s
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            <i class="fas fa-chart-line text-4xl mb-4"></i>
                            <p>ยังไม่มีข้อมูล Usage</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="mt-6">
        {{ $logs->links() }}
    </div>

    {{-- Daily Chart --}}
    @if(count($dailyStats) > 0)
    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
        <h3 class="font-semibold text-gray-900 dark:text-white mb-4">กราฟการใช้งานรายวัน (7 วันล่าสุด)</h3>
        <canvas id="usageChart" height="100"></canvas>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('usageChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode(array_column($dailyStats, 'date')) !!},
                datasets: [
                    {
                        label: 'Tokens',
                        data: {!! json_encode(array_column($dailyStats, 'tokens')) !!},
                        borderColor: 'rgb(147, 51, 234)',
                        backgroundColor: 'rgba(147, 51, 234, 0.1)',
                        tension: 0.3,
                        fill: true,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Cost ($)',
                        data: {!! json_encode(array_column($dailyStats, 'cost')) !!},
                        borderColor: 'rgb(34, 197, 94)',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        tension: 0.3,
                        fill: true,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Tokens'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Cost ($)'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                }
            }
        });
    </script>
    @endpush
    @endif
</div>
@endsection
