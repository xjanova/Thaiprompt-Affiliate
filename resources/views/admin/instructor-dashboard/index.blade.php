@extends('layouts.admin-v3')

@section('title', 'แดชบอร์ดผู้สอน - Academy')

@section('content')
<div class="space-y-6">
    <!-- Welcome Header with Animation -->
    <div class="relative overflow-hidden bg-gradient-to-r from-purple-600 via-pink-600 to-indigo-600 dark:from-purple-800 dark:via-pink-800 dark:to-indigo-800 rounded-2xl shadow-2xl p-8 text-white">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-40 h-40 bg-white dark:bg-gray-800 rounded-full opacity-10 blur-3xl"></div>
        <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-32 h-32 bg-white dark:bg-gray-800 rounded-full opacity-10 blur-2xl"></div>
        <div class="relative">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-bold mb-2 animate-fade-in">👨‍🏫 แดชบอร์ดผู้สอน</h1>
                    <p class="text-purple-100 dark:text-purple-200 text-lg">ยินดีต้อนรับกลับมา {{ Auth::user()->name }}!</p>
                    <p class="text-purple-200 dark:text-purple-300 text-sm mt-1">{{ now()->format('l, d F Y - H:i') }} น.</p>
                </div>
                <div class="text-right">
                    <div class="text-sm text-purple-200 dark:text-purple-300 mb-1">นักเรียนทั้งหมด</div>
                    <div class="text-5xl font-bold">{{ number_format($stats['total_students']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards Grid with Hover Animation -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Total Courses -->
        <div class="group relative bg-white dark:bg-slate-800 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 p-6 overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-purple-400 to-purple-600 dark:from-purple-600 dark:to-purple-800 rounded-full opacity-10 group-hover:opacity-20 transition-opacity -mr-16 -mt-16"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-purple-700 dark:from-purple-600 dark:to-purple-800 rounded-xl flex items-center justify-center text-2xl shadow-lg">
                        📚
                    </div>
                    <span class="px-3 py-1 bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 rounded-full text-xs font-bold">
                        +2 ใหม่
                    </span>
                </div>
                <p class="text-gray-600 dark:text-gray-400 text-sm font-medium mb-1">คอร์สทั้งหมด</p>
                <p class="text-4xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_courses']) }}</p>
            </div>
        </div>

        <!-- Total Students -->
        <div class="group relative bg-white dark:bg-slate-800 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 p-6 overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-pink-400 to-pink-600 dark:from-pink-600 dark:to-pink-800 rounded-full opacity-10 group-hover:opacity-20 transition-opacity -mr-16 -mt-16"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-pink-500 to-pink-700 dark:from-pink-600 dark:to-pink-800 rounded-xl flex items-center justify-center text-2xl shadow-lg">
                        👥
                    </div>
                    <span class="px-3 py-1 bg-pink-100 dark:bg-pink-900 text-pink-800 dark:text-pink-200 rounded-full text-xs font-bold">
                        +{{ rand(15, 45) }}%
                    </span>
                </div>
                <p class="text-gray-600 dark:text-gray-400 text-sm font-medium mb-1">นักเรียนทั้งหมด</p>
                <p class="text-4xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_students']) }}</p>
            </div>
        </div>

        <!-- Revenue -->
        <div class="group relative bg-white dark:bg-slate-800 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 p-6 overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-green-400 to-emerald-600 dark:from-green-600 dark:to-emerald-800 rounded-full opacity-10 group-hover:opacity-20 transition-opacity -mr-16 -mt-16"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-emerald-700 dark:from-green-600 dark:to-emerald-800 rounded-xl flex items-center justify-center text-2xl shadow-lg">
                        💰
                    </div>
                    <span class="px-3 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-full text-xs font-bold">
                        +12%
                    </span>
                </div>
                <p class="text-gray-600 dark:text-gray-400 text-sm font-medium mb-1">รายได้รวม</p>
                <p class="text-4xl font-bold text-gray-900 dark:text-white">฿{{ number_format($stats['total_revenue']) }}</p>
            </div>
        </div>

        <!-- Rating -->
        <div class="group relative bg-white dark:bg-slate-800 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 p-6 overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-yellow-400 to-orange-600 dark:from-yellow-600 dark:to-orange-800 rounded-full opacity-10 group-hover:opacity-20 transition-opacity -mr-16 -mt-16"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-yellow-500 to-orange-700 dark:from-yellow-600 dark:to-orange-800 rounded-xl flex items-center justify-center text-2xl shadow-lg">
                        ⭐
                    </div>
                    <span class="px-3 py-1 bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 rounded-full text-xs font-bold">
                        เยี่ยม!
                    </span>
                </div>
                <p class="text-gray-600 dark:text-gray-400 text-sm font-medium mb-1">คะแนนเฉลี่ย</p>
                <p class="text-4xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['avg_rating'], 1) }}</p>
            </div>
        </div>

        <!-- Completed -->
        <div class="group relative bg-white dark:bg-slate-800 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 p-6 overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-blue-400 to-cyan-600 dark:from-blue-600 dark:to-cyan-800 rounded-full opacity-10 group-hover:opacity-20 transition-opacity -mr-16 -mt-16"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-cyan-700 dark:from-blue-600 dark:to-cyan-800 rounded-xl flex items-center justify-center text-2xl shadow-lg">
                        ✅
                    </div>
                    <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-full text-xs font-bold">
                        +{{ rand(5, 15) }}%
                    </span>
                </div>
                <p class="text-gray-600 dark:text-gray-400 text-sm font-medium mb-1">เรียนจบแล้ว</p>
                <p class="text-4xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['completed_enrollments']) }}</p>
            </div>
        </div>

        <!-- Views -->
        <div class="group relative bg-white dark:bg-slate-800 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 p-6 overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-indigo-400 to-purple-600 dark:from-indigo-600 dark:to-purple-800 rounded-full opacity-10 group-hover:opacity-20 transition-opacity -mr-16 -mt-16"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-indigo-500 to-purple-700 dark:from-indigo-600 dark:to-purple-800 rounded-xl flex items-center justify-center text-2xl shadow-lg">
                        👁️
                    </div>
                    <span class="px-3 py-1 bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200 rounded-full text-xs font-bold">
                        +{{ rand(20, 50) }}%
                    </span>
                </div>
                <p class="text-gray-600 dark:text-gray-400 text-sm font-medium mb-1">จำนวนวิว</p>
                <p class="text-4xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_views']) }}</p>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Monthly Stats Chart -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">📈 สถิติรายเดือน</h3>
                <span class="px-3 py-1 bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200 rounded-full text-xs font-bold">
                    6 เดือนล่าสุด
                </span>
            </div>
            <div style="height: 300px;">
                <canvas id="monthlyChart"></canvas>
            </div>
        </div>

        <!-- Course Performance Chart -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">🎯 อัตราการจบคอร์ส</h3>
                <a href="{{ route('admin.instructor.courses') }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 font-semibold text-sm">
                    ดูทั้งหมด →
                </a>
            </div>
            <div style="height: 300px;">
                <canvas id="completionChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Course Performance Table & Recent Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Course Performance Table -->
        <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">📊 ประสิทธิภาพคอร์ส</h3>
                <a href="{{ route('admin.instructor.courses') }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 font-semibold text-sm">
                    ดูทั้งหมด →
                </a>
            </div>

            @if($coursePerformance->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="text-left py-3 px-4 text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">คอร์ส</th>
                            <th class="text-center py-3 px-4 text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">นักเรียน</th>
                            <th class="text-center py-3 px-4 text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">อัตราจบ</th>
                            <th class="text-center py-3 px-4 text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">วิว</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($coursePerformance->take(5) as $course)
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                            <td class="py-4 px-4">
                                <div class="font-semibold text-gray-900 dark:text-white">{{ Str::limit($course['title'], 40) }}</div>
                                <span class="inline-block mt-1 px-2 py-1 bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 rounded text-xs font-semibold">
                                    {{ $course['category'] }}
                                </span>
                            </td>
                            <td class="py-4 px-4 text-center">
                                <span class="font-bold text-gray-900 dark:text-white">{{ number_format($course['students']) }}</span>
                            </td>
                            <td class="py-4 px-4">
                                <div class="flex flex-col items-center">
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mb-2">
                                        <div class="bg-gradient-to-r from-green-500 to-emerald-600 h-2 rounded-full transition-all duration-500" style="width: {{ $course['completion_rate'] }}%"></div>
                                    </div>
                                    <span class="text-xs font-semibold text-gray-600 dark:text-gray-400">{{ $course['completion_rate'] }}%</span>
                                </div>
                            </td>
                            <td class="py-4 px-4 text-center">
                                <span class="font-bold text-gray-900 dark:text-white">{{ number_format($course['views']) }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-12">
                <div class="text-6xl mb-4">📚</div>
                <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">ยังไม่มีคอร์ส</h4>
                <p class="text-gray-600 dark:text-gray-400 mb-4">เริ่มสร้างคอร์สแรกของคุณวันนี้!</p>
            </div>
            @endif
        </div>

        <!-- Recent Activity -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">🔔 กิจกรรมล่าสุด</h3>

            @if($recentActivities->count() > 0)
            <div class="space-y-4 max-h-96 overflow-y-auto">
                @foreach($recentActivities->take(10) as $activity)
                <div class="flex items-start gap-3 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center text-white font-bold flex-shrink-0">
                        {{ substr($activity->user->name ?? 'U', 0, 1) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $activity->user->name ?? 'Unknown' }}</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400 truncate">{{ $activity->article->title ?? 'Unknown Course' }}</p>
                        <div class="flex items-center gap-2 mt-1">
                            @if($activity->status === 'completed')
                            <span class="px-2 py-0.5 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-full text-xs font-semibold">จบ</span>
                            @elseif($activity->status === 'in_progress')
                            <span class="px-2 py-0.5 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-full text-xs font-semibold">เรียน</span>
                            @else
                            <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-full text-xs font-semibold">เริ่ม</span>
                            @endif
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $activity->updated_at->diffForHumans() }}</span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-center py-8">
                <div class="text-4xl mb-2">📭</div>
                <p class="text-gray-600 dark:text-gray-400 text-sm">ยังไม่มีกิจกรรม</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const isDark = document.documentElement.classList.contains('dark');
    const textColor = isDark ? '#9ca3af' : '#6b7280';
    const gridColor = isDark ? '#374151' : '#e5e7eb';

    // Monthly Chart
    const monthlyData = @json($monthlyStats);
    const monthlyCtx = document.getElementById('monthlyChart');

    if (monthlyCtx && monthlyData.length > 0) {
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: monthlyData.map(m => m.month),
                datasets: [
                    {
                        label: 'นักเรียนใหม่',
                        data: monthlyData.map(m => m.students),
                        borderColor: 'rgb(139, 92, 246)',
                        backgroundColor: 'rgba(139, 92, 246, 0.1)',
                        tension: 0.4,
                        fill: true,
                        borderWidth: 3
                    },
                    {
                        label: 'การลงทะเบียน',
                        data: monthlyData.map(m => m.enrollments),
                        borderColor: 'rgb(236, 72, 153)',
                        backgroundColor: 'rgba(236, 72, 153, 0.1)',
                        tension: 0.4,
                        fill: true,
                        borderWidth: 3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: { color: textColor }
                    },
                    tooltip: {
                        backgroundColor: isDark ? '#1e293b' : '#ffffff',
                        titleColor: isDark ? '#ffffff' : '#000000',
                        bodyColor: isDark ? '#ffffff' : '#000000',
                        borderColor: gridColor,
                        borderWidth: 1
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: gridColor },
                        ticks: { color: textColor }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: textColor }
                    }
                }
            }
        });
    }

    // Completion Rate Chart
    const completionCtx = document.getElementById('completionChart');
    const courseData = @json($coursePerformance->take(5));

    if (completionCtx && courseData.length > 0) {
        new Chart(completionCtx, {
            type: 'bar',
            data: {
                labels: courseData.map(c => c.title.substring(0, 20) + '...'),
                datasets: [{
                    label: 'อัตราการจบคอร์ส (%)',
                    data: courseData.map(c => c.completion_rate),
                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                    borderRadius: 8,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: isDark ? '#1e293b' : '#ffffff',
                        titleColor: isDark ? '#ffffff' : '#000000',
                        bodyColor: isDark ? '#ffffff' : '#000000',
                        borderColor: gridColor,
                        borderWidth: 1
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: { color: gridColor },
                        ticks: {
                            color: textColor,
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: textColor }
                    }
                }
            }
        });
    }
});
</script>
@endpush
