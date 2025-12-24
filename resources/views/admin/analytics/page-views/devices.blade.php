{{--
/**
 * หน้า Device & Browser Analytics
 */
--}}

@extends('layouts.admin')

@section('title', 'Device Analytics')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <span class="text-3xl">📱</span>
                อุปกรณ์และ Browser
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                วิเคราะห์อุปกรณ์ที่ผู้เข้าชมใช้
            </p>
        </div>

        <div class="mt-4 md:mt-0 flex items-center gap-4">
            <select onchange="window.location.href = '?period=' + this.value"
                    class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg">
                <option value="7days" {{ $period === '7days' ? 'selected' : '' }}>7 วันล่าสุด</option>
                <option value="30days" {{ $period === '30days' ? 'selected' : '' }}>30 วันล่าสุด</option>
            </select>

            <a href="{{ route('admin.analytics.page-views.index') }}"
               class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg flex items-center gap-2 transition">
                <i class="fas fa-arrow-left"></i>
                <span>กลับ</span>
            </a>
        </div>
    </div>

    {{-- Device Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
        @foreach($deviceStats as $device)
        @php
            $icons = ['desktop' => '🖥️', 'mobile' => '📱', 'tablet' => '📲'];
            $colors = ['desktop' => 'from-blue-500 to-blue-600', 'mobile' => 'from-green-500 to-green-600', 'tablet' => 'from-purple-500 to-purple-600'];
            $icon = $icons[$device->device_type] ?? '📟';
            $color = $colors[$device->device_type] ?? 'from-gray-500 to-gray-600';
        @endphp
        <div class="bg-gradient-to-r {{ $color }} rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-lg opacity-90">{{ ucfirst($device->device_type ?? 'Unknown') }}</p>
                    <p class="text-4xl font-bold mt-2">{{ number_format($device->views) }}</p>
                </div>
                <span class="text-5xl">{{ $icon }}</span>
            </div>
        </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Browser Stats --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <span>🌐</span>
                    Browser ที่ใช้
                </h3>
            </div>
            <div class="p-6">
                <div class="h-64">
                    <canvas id="browserChart"></canvas>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($browserStats as $browser)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-6 py-3 text-sm text-gray-900 dark:text-white">{{ $browser->browser }}</td>
                            <td class="px-6 py-3 text-right text-sm font-semibold text-blue-600 dark:text-blue-400">{{ number_format($browser->views) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Platform Stats --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <span>💻</span>
                    ระบบปฏิบัติการ
                </h3>
            </div>
            <div class="p-6">
                <div class="h-64">
                    <canvas id="platformChart"></canvas>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($platformStats as $platform)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-6 py-3 text-sm text-gray-900 dark:text-white">{{ $platform->platform }}</td>
                            <td class="px-6 py-3 text-right text-sm font-semibold text-purple-600 dark:text-purple-400">{{ number_format($platform->views) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const browserData = @json($browserStats);
    new Chart(document.getElementById('browserChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: browserData.map(d => d.browser),
            datasets: [{
                data: browserData.map(d => d.views),
                backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#06b6d4', '#84cc16'],
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'right', labels: { boxWidth: 12 } } }
        }
    });

    const platformData = @json($platformStats);
    new Chart(document.getElementById('platformChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: platformData.map(d => d.platform),
            datasets: [{
                data: platformData.map(d => d.views),
                backgroundColor: ['#8b5cf6', '#ec4899', '#06b6d4', '#84cc16', '#f97316', '#3b82f6', '#10b981', '#ef4444'],
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'right', labels: { boxWidth: 12 } } }
        }
    });
});
</script>
@endpush
@endsection
