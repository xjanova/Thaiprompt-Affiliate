@extends('layouts.admin-v3')

@section('title', 'วิเคราะห์คอร์ส - ' . $course->title)

@section('content')
<div class="space-y-6">
    <!-- Back Button -->
    <div>
        <a href="{{ route('admin.instructor.dashboard') }}"
           class="inline-flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
            <i class="fas fa-arrow-left"></i>
            <span>กลับไปยังแดชบอร์ดผู้สอน</span>
        </a>
    </div>

    <!-- Course Header -->
    <div class="bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 dark:from-blue-800 dark:via-indigo-800 dark:to-purple-800 rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 bg-white opacity-5 rounded-full -mr-48 -mt-48"></div>
        <div class="absolute bottom-0 left-0 w-64 h-64 bg-white opacity-5 rounded-full -ml-32 -mb-32"></div>

        <div class="relative z-10">
            <div class="flex items-start justify-between flex-wrap gap-4">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="bg-white/20 backdrop-blur-sm p-3 rounded-xl">
                            <i class="fas fa-graduation-cap text-3xl"></i>
                        </div>
                        <div>
                            <p class="text-blue-100 text-sm">วิเคราะห์คอร์ส</p>
                            <h1 class="text-3xl font-bold">{{ $course->title }}</h1>
                        </div>
                    </div>
                    @if($course->category)
                    <span class="inline-flex items-center px-4 py-2 bg-white/20 backdrop-blur-sm rounded-xl font-medium text-sm">
                        <i class="fas fa-folder mr-2"></i>
                        {{ $course->category->name }}
                    </span>
                    @endif
                </div>

                <div class="text-right">
                    <p class="text-blue-100 text-sm mb-2">คะแนนเฉลี่ย</p>
                    <div class="flex items-center gap-2 justify-end mb-2">
                        <div class="flex gap-1">
                            @for($i = 1; $i <= 5; $i++)
                                <i class="fas fa-star {{ $i <= 4.5 ? 'text-yellow-400' : 'text-gray-400' }}"></i>
                            @endfor
                        </div>
                        <span class="text-2xl font-bold">4.5</span>
                    </div>
                    <p class="text-blue-100 text-sm">จากนักเรียน {{ number_format($stats['total_enrolled']) }} คน</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Enrolled -->
        <div class="group bg-gradient-to-br from-blue-500 to-blue-700 dark:from-blue-600 dark:to-blue-900 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all duration-300 cursor-pointer relative overflow-hidden">
            <div class="absolute -right-6 -top-6 opacity-10">
                <i class="fas fa-users text-9xl"></i>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-sm font-medium opacity-90">นักเรียนทั้งหมด</p>
                    <div class="bg-white/20 p-2 rounded-lg">
                        <i class="fas fa-user-graduate text-xl"></i>
                    </div>
                </div>
                <h3 class="text-4xl font-bold mb-2">{{ number_format($stats['total_enrolled']) }}</h3>
                <div class="flex items-center text-sm">
                    <i class="fas fa-users mr-2 text-blue-200"></i>
                    <span class="opacity-90">ผู้เข้าเรียน</span>
                </div>
            </div>
        </div>

        <!-- Completion Rate -->
        <div class="group bg-gradient-to-br from-green-500 to-emerald-700 dark:from-green-600 dark:to-emerald-900 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all duration-300 cursor-pointer relative overflow-hidden">
            <div class="absolute -right-6 -top-6 opacity-10">
                <i class="fas fa-check-circle text-9xl"></i>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-sm font-medium opacity-90">อัตราจบคอร์ส</p>
                    <div class="bg-white/20 p-2 rounded-lg">
                        <i class="fas fa-trophy text-xl"></i>
                    </div>
                </div>
                <h3 class="text-4xl font-bold mb-2">{{ number_format(($stats['total_enrolled'] > 0 ? ($stats['completed'] / $stats['total_enrolled']) * 100 : 0), 1) }}%</h3>
                <div class="flex items-center text-sm">
                    <i class="fas fa-chart-line mr-2 text-green-200"></i>
                    <span class="opacity-90">{{ number_format($stats['completed']) }} คน</span>
                </div>
            </div>
        </div>

        <!-- Average Progress -->
        <div class="group bg-gradient-to-br from-purple-500 to-pink-600 dark:from-purple-600 dark:to-pink-800 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all duration-300 cursor-pointer relative overflow-hidden">
            <div class="absolute -right-6 -top-6 opacity-10">
                <i class="fas fa-tasks text-9xl"></i>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-sm font-medium opacity-90">ความคืบหน้าเฉลี่ย</p>
                    <div class="bg-white/20 p-2 rounded-lg">
                        <i class="fas fa-percentage text-xl"></i>
                    </div>
                </div>
                <h3 class="text-4xl font-bold mb-2">{{ number_format($stats['avg_progress'] ?? 0, 1) }}%</h3>
                <div class="flex items-center text-sm">
                    <i class="fas fa-spinner mr-2 text-purple-200"></i>
                    <span class="opacity-90">{{ number_format($stats['in_progress']) }} คนกำลังเรียน</span>
                </div>
            </div>
        </div>

        <!-- Total Views -->
        <div class="group bg-gradient-to-br from-orange-500 to-red-600 dark:from-orange-600 dark:to-red-800 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all duration-300 cursor-pointer relative overflow-hidden">
            <div class="absolute -right-6 -top-6 opacity-10">
                <i class="fas fa-eye text-9xl"></i>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-sm font-medium opacity-90">จำนวนครั้งดู</p>
                    <div class="bg-white/20 p-2 rounded-lg">
                        <i class="fas fa-chart-bar text-xl"></i>
                    </div>
                </div>
                <h3 class="text-4xl font-bold mb-2">{{ number_format($stats['total_views']) }}</h3>
                <div class="flex items-center text-sm">
                    <i class="fas fa-eye mr-2 text-orange-200"></i>
                    <span class="opacity-90">การเข้าชม</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Learning Analytics -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Enrollment Trend -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                    <div class="bg-gradient-to-r from-blue-500 to-purple-600 p-2 rounded-lg mr-3">
                        <i class="fas fa-chart-line text-white"></i>
                    </div>
                    แนวโน้มการลงทะเบียน
                </h2>
            </div>
            <canvas id="enrollmentChart" height="300"></canvas>
        </div>

        <!-- Progress Distribution -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                    <div class="bg-gradient-to-r from-green-500 to-emerald-600 p-2 rounded-lg mr-3">
                        <i class="fas fa-chart-pie text-white"></i>
                    </div>
                    การกระจายความคืบหน้า
                </h2>
            </div>
            <canvas id="progressChart" height="300"></canvas>
        </div>
    </div>

    <!-- Completion Status -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
            <div class="bg-gradient-to-r from-yellow-500 to-orange-600 p-2 rounded-lg mr-3">
                <i class="fas fa-tasks text-white"></i>
            </div>
            สถานะการเรียน
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Completed -->
            <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl p-6 border-2 border-green-200 dark:border-green-800">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-green-500 p-3 rounded-xl">
                        <i class="fas fa-check-circle text-white text-2xl"></i>
                    </div>
                    <span class="text-3xl font-bold text-green-600 dark:text-green-400">{{ number_format($stats['completed']) }}</span>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">จบคอร์สแล้ว</h3>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="bg-green-500 h-2 rounded-full" style="width: {{ $stats['total_enrolled'] > 0 ? ($stats['completed'] / $stats['total_enrolled']) * 100 : 0 }}%"></div>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                    {{ number_format(($stats['total_enrolled'] > 0 ? ($stats['completed'] / $stats['total_enrolled']) * 100 : 0), 1) }}% ของทั้งหมด
                </p>
            </div>

            <!-- In Progress -->
            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl p-6 border-2 border-blue-200 dark:border-blue-800">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-blue-500 p-3 rounded-xl">
                        <i class="fas fa-spinner text-white text-2xl"></i>
                    </div>
                    <span class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($stats['in_progress']) }}</span>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">กำลังเรียน</h3>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="bg-blue-500 h-2 rounded-full" style="width: {{ $stats['total_enrolled'] > 0 ? ($stats['in_progress'] / $stats['total_enrolled']) * 100 : 0 }}%"></div>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                    {{ number_format(($stats['total_enrolled'] > 0 ? ($stats['in_progress'] / $stats['total_enrolled']) * 100 : 0), 1) }}% ของทั้งหมด
                </p>
            </div>

            <!-- Not Started -->
            <div class="bg-gradient-to-br from-gray-50 to-slate-50 dark:from-gray-900/20 dark:to-slate-900/20 rounded-xl p-6 border-2 border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-gray-500 p-3 rounded-xl">
                        <i class="fas fa-pause-circle text-white text-2xl"></i>
                    </div>
                    <span class="text-3xl font-bold text-gray-600 dark:text-gray-400">{{ number_format($stats['not_started']) }}</span>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">ยังไม่เริ่ม</h3>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="bg-gray-500 h-2 rounded-full" style="width: {{ $stats['total_enrolled'] > 0 ? ($stats['not_started'] / $stats['total_enrolled']) * 100 : 0 }}%"></div>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                    {{ number_format(($stats['total_enrolled'] > 0 ? ($stats['not_started'] / $stats['total_enrolled']) * 100 : 0), 1) }}% ของทั้งหมด
                </p>
            </div>
        </div>
    </div>

    <!-- Student List -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                    <div class="bg-gradient-to-r from-purple-500 to-pink-600 p-2 rounded-lg mr-3">
                        <i class="fas fa-users text-white"></i>
                    </div>
                    รายชื่อนักเรียน ({{ number_format($students->count()) }} คน)
                </h2>

                <div class="flex gap-2">
                    <button onclick="filterStudents('all')" class="px-4 py-2 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-lg text-sm font-medium hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors">
                        ทั้งหมด
                    </button>
                    <button onclick="filterStudents('completed')" class="px-4 py-2 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-lg text-sm font-medium hover:bg-green-200 dark:hover:bg-green-900/50 transition-colors">
                        จบแล้ว
                    </button>
                    <button onclick="filterStudents('in_progress')" class="px-4 py-2 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded-lg text-sm font-medium hover:bg-purple-200 dark:hover:bg-purple-900/50 transition-colors">
                        กำลังเรียน
                    </button>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gradient-to-r from-blue-600 to-purple-600 text-white">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-medium uppercase tracking-wider">นักเรียน</th>
                        <th class="px-6 py-4 text-left text-xs font-medium uppercase tracking-wider">สถานะ</th>
                        <th class="px-6 py-4 text-left text-xs font-medium uppercase tracking-wider">ความคืบหน้า</th>
                        <th class="px-6 py-4 text-left text-xs font-medium uppercase tracking-wider">เวลาเรียน</th>
                        <th class="px-6 py-4 text-left text-xs font-medium uppercase tracking-wider">เริ่มเรียน</th>
                        <th class="px-6 py-4 text-left text-xs font-medium uppercase tracking-wider">เข้าถึงล่าสุด</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700" id="studentsTable">
                    @foreach($students as $student)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors student-row" data-status="{{ $student['status'] }}">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center text-white font-bold">
                                    {{ strtoupper(substr($student['name'], 0, 2)) }}
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-white">{{ $student['name'] }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $student['email'] }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold
                                {{ $student['status'] === 'completed' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300' : '' }}
                                {{ $student['status'] === 'in_progress' ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' : '' }}
                                {{ $student['status'] === 'not_started' ? 'bg-gray-100 dark:bg-gray-900/30 text-gray-700 dark:text-gray-300' : '' }}">
                                @if($student['status'] === 'completed')
                                    <i class="fas fa-check-circle mr-1"></i> จบแล้ว
                                @elseif($student['status'] === 'in_progress')
                                    <i class="fas fa-spinner mr-1"></i> กำลังเรียน
                                @else
                                    <i class="fas fa-pause-circle mr-1"></i> ยังไม่เริ่ม
                                @endif
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <div class="flex-1">
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                        <div class="bg-gradient-to-r from-blue-500 to-purple-600 h-2 rounded-full" style="width: {{ $student['progress'] }}%"></div>
                                    </div>
                                </div>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white w-12 text-right">{{ number_format($student['progress'], 0) }}%</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-2 text-gray-900 dark:text-white">
                                <i class="fas fa-clock text-blue-600 dark:text-blue-400"></i>
                                <span class="font-medium">{{ number_format($student['time_spent'], 1) }}</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">ชม.</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                            @if($student['started_at'])
                                {{ \Carbon\Carbon::parse($student['started_at'])->format('d/m/Y') }}
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                            @if($student['last_accessed'])
                                {{ \Carbon\Carbon::parse($student['last_accessed'])->diffForHumans() }}
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($students->isEmpty())
        <div class="p-12 text-center">
            <div class="text-gray-400 dark:text-gray-500 mb-4">
                <i class="fas fa-users text-6xl"></i>
            </div>
            <p class="text-gray-600 dark:text-gray-400">ยังไม่มีนักเรียนลงทะเบียน</p>
        </div>
        @endif
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Enrollment Chart
const enrollmentCtx = document.getElementById('enrollmentChart').getContext('2d');
new Chart(enrollmentCtx, {
    type: 'line',
    data: {
        labels: ['มค.', 'กพ.', 'มีค.', 'เมย.', 'พค.', 'มิย.'],
        datasets: [{
            label: 'การลงทะเบียน',
            data: [5, 12, 18, 25, 32, {{ $stats['total_enrolled'] }}],
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        }
    }
});

// Progress Distribution Chart
const progressCtx = document.getElementById('progressChart').getContext('2d');
new Chart(progressCtx, {
    type: 'doughnut',
    data: {
        labels: ['จบแล้ว', 'กำลังเรียน', 'ยังไม่เริ่ม'],
        datasets: [{
            data: [{{ $stats['completed'] }}, {{ $stats['in_progress'] }}, {{ $stats['not_started'] }}],
            backgroundColor: [
                'rgba(16, 185, 129, 0.8)',
                'rgba(59, 130, 246, 0.8)',
                'rgba(107, 114, 128, 0.8)'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

// Filter Students
function filterStudents(status) {
    const rows = document.querySelectorAll('.student-row');
    rows.forEach(row => {
        if (status === 'all' || row.dataset.status === status) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>
@endsection
