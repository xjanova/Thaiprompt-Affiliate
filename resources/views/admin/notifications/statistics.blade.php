@extends('layouts.admin')

@section('title', 'สถิติการแจ้งเตือน')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 dark:text-white">📊 สถิติการแจ้งเตือน</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">ภาพรวมและวิเคราะห์การแจ้งเตือนในระบบ</p>
            </div>
            <a href="{{ route('admin.notifications.index') }}" class="px-6 py-3 bg-gradient-to-r from-gray-600 to-gray-700 text-white rounded-lg hover:from-gray-700 hover:to-gray-800 transition-all shadow-md hover:shadow-lg">
                ← กลับไปรายการ
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Notifications -->
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-all duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm font-medium">การแจ้งเตือนทั้งหมด</p>
                    <p class="text-4xl font-bold mt-2">{{ number_format($stats['total']) }}</p>
                </div>
                <div class="bg-white/20 rounded-full p-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Unread Notifications -->
        <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-all duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-red-100 text-sm font-medium">ยังไม่ได้อ่าน</p>
                    <p class="text-4xl font-bold mt-2">{{ number_format($stats['unread']) }}</p>
                    @if($stats['total'] > 0)
                        <p class="text-red-100 text-xs mt-1">{{ round(($stats['unread'] / $stats['total']) * 100, 1) }}%</p>
                    @endif
                </div>
                <div class="bg-white/20 rounded-full p-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Read Notifications -->
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-all duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm font-medium">อ่านแล้ว</p>
                    <p class="text-4xl font-bold mt-2">{{ number_format($stats['read']) }}</p>
                    <p class="text-green-100 text-xs mt-1">อัตราการอ่าน {{ $stats['read_rate'] }}%</p>
                </div>
                <div class="bg-white/20 rounded-full p-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Broadcast Notifications -->
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-all duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm font-medium">ประกาศทั่วไป</p>
                    <p class="text-4xl font-bold mt-2">{{ number_format($stats['broadcast']) }}</p>
                    @if($stats['total'] > 0)
                        <p class="text-purple-100 text-xs mt-1">{{ round(($stats['broadcast'] / $stats['total']) * 100, 1) }}%</p>
                    @endif
                </div>
                <div class="bg-white/20 rounded-full p-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Time-based Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
            <div class="flex items-center">
                <div class="bg-orange-100 dark:bg-orange-900/30 rounded-lg p-3">
                    <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-gray-600 dark:text-gray-400 text-sm">วันนี้</p>
                    <p class="text-2xl font-bold text-gray-800 dark:text-white">{{ number_format($stats['today']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
            <div class="flex items-center">
                <div class="bg-indigo-100 dark:bg-indigo-900/30 rounded-lg p-3">
                    <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-gray-600 dark:text-gray-400 text-sm">สัปดาห์นี้</p>
                    <p class="text-2xl font-bold text-gray-800 dark:text-white">{{ number_format($stats['this_week']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
            <div class="flex items-center">
                <div class="bg-pink-100 dark:bg-pink-900/30 rounded-lg p-3">
                    <svg class="w-6 h-6 text-pink-600 dark:text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-gray-600 dark:text-gray-400 text-sm">เดือนนี้</p>
                    <p class="text-2xl font-bold text-gray-800 dark:text-white">{{ number_format($stats['this_month']) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Type Distribution Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
            <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-4">การแจ้งเตือนตามประเภท</h3>
            <div class="relative" style="height: 300px;">
                <canvas id="typeChart"></canvas>
            </div>
            <div class="mt-4 space-y-2">
                @foreach($stats['by_type'] as $item)
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">{{ $item->type }}</span>
                        <span class="font-semibold text-gray-800 dark:text-white">{{ number_format($item->count) }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Priority Distribution Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
            <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-4">ระดับความสำคัญ</h3>
            <div class="relative" style="height: 300px;">
                <canvas id="priorityChart"></canvas>
            </div>
            <div class="mt-4 space-y-2">
                @foreach($stats['by_priority'] as $item)
                    @php
                        $priorityColors = [
                            'low' => 'text-green-600',
                            'normal' => 'text-blue-600',
                            'high' => 'text-orange-600',
                            'urgent' => 'text-red-600'
                        ];
                        $priorityLabels = [
                            'low' => 'ต่ำ',
                            'normal' => 'ปกติ',
                            'high' => 'สูง',
                            'urgent' => 'เร่งด่วน'
                        ];
                    @endphp
                    <div class="flex items-center justify-between text-sm">
                        <span class="{{ $priorityColors[$item->priority] ?? 'text-gray-600' }} font-medium">
                            {{ $priorityLabels[$item->priority] ?? $item->priority }}
                        </span>
                        <span class="font-semibold text-gray-800 dark:text-white">{{ number_format($item->count) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Read Rate Progress -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 mb-8">
        <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-4">อัตราการอ่านการแจ้งเตือน</h3>
        <div class="flex items-center">
            <div class="flex-1">
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-6 overflow-hidden">
                    <div class="bg-gradient-to-r from-green-400 to-green-600 h-6 rounded-full flex items-center justify-center text-white text-sm font-bold transition-all duration-1000 ease-out"
                         style="width: {{ $stats['read_rate'] }}%">
                        {{ $stats['read_rate'] }}%
                    </div>
                </div>
                <div class="flex justify-between mt-2 text-sm text-gray-600 dark:text-gray-400">
                    <span>อ่านแล้ว: {{ number_format($stats['read']) }}</span>
                    <span>ยังไม่ได้อ่าน: {{ number_format($stats['unread']) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Notifications -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
        <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-4">การแจ้งเตือนล่าสุด</h3>
        <div class="space-y-4">
            @forelse($stats['recent'] as $notification)
                <div class="flex items-start p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <div class="flex-shrink-0">
                        @if($notification->icon)
                            <span class="text-2xl">{{ $notification->icon }}</span>
                        @else
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                                </svg>
                            </div>
                        @endif
                    </div>
                    <div class="ml-4 flex-1">
                        <div class="flex items-start justify-between">
                            <div>
                                <h4 class="font-semibold text-gray-800 dark:text-white">{{ $notification->title }}</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ Str::limit($notification->message, 100) }}</p>
                                <div class="flex items-center gap-3 mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    <span>{{ $notification->user ? $notification->user->name : 'ทุกคน' }}</span>
                                    <span>•</span>
                                    <span>{{ $notification->created_at->diffForHumans() }}</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                @if($notification->is_broadcast)
                                    <span class="px-2 py-1 text-xs bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400 rounded-full">Broadcast</span>
                                @endif
                                @if($notification->show_immediately)
                                    <span class="px-2 py-1 text-xs bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400 rounded-full">ทันที</span>
                                @endif
                                @if($notification->read_at)
                                    <span class="px-2 py-1 text-xs bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 rounded-full">อ่านแล้ว</span>
                                @else
                                    <span class="px-2 py-1 text-xs bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 rounded-full">ยังไม่อ่าน</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-gray-500 dark:text-gray-400 text-center py-8">ยังไม่มีการแจ้งเตือน</p>
            @endforelse
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Type Distribution Chart
    const typeCtx = document.getElementById('typeChart').getContext('2d');
    const typeData = @json($stats['by_type']);

    new Chart(typeCtx, {
        type: 'doughnut',
        data: {
            labels: typeData.map(item => item.type),
            datasets: [{
                data: typeData.map(item => item.count),
                backgroundColor: [
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(16, 185, 129, 0.8)',
                    'rgba(245, 158, 11, 0.8)',
                    'rgba(239, 68, 68, 0.8)',
                    'rgba(139, 92, 246, 0.8)',
                    'rgba(236, 72, 153, 0.8)',
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        font: {
                            size: 12
                        }
                    }
                }
            }
        }
    });

    // Priority Distribution Chart
    const priorityCtx = document.getElementById('priorityChart').getContext('2d');
    const priorityData = @json($stats['by_priority']);
    const priorityLabels = {
        'low': 'ต่ำ',
        'normal': 'ปกติ',
        'high': 'สูง',
        'urgent': 'เร่งด่วน'
    };
    const priorityColors = {
        'low': 'rgba(16, 185, 129, 0.8)',
        'normal': 'rgba(59, 130, 246, 0.8)',
        'high': 'rgba(245, 158, 11, 0.8)',
        'urgent': 'rgba(239, 68, 68, 0.8)'
    };

    new Chart(priorityCtx, {
        type: 'bar',
        data: {
            labels: priorityData.map(item => priorityLabels[item.priority] || item.priority),
            datasets: [{
                label: 'จำนวน',
                data: priorityData.map(item => item.count),
                backgroundColor: priorityData.map(item => priorityColors[item.priority] || 'rgba(107, 114, 128, 0.8)'),
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
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
});
</script>
@endsection
