@extends('layouts.admin-v3')

@section('title', 'Keyword Analytics & Performance')

@section('content')
<div class="container-fluid px-4 py-6" x-data="analyticsData">
    {{-- Header พร้อม LINE Green theme --}}
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-[#00B900] via-[#00D000] to-[#00E600] p-8 shadow-2xl">
        {{-- Animated Background --}}
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS1vcGFjaXR5PSIwLjA1IiBzdHJva2Utd2lkdGg9IjEiLz48L3BhdHRlcm4+PC9kZWZzPjxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbGw9InVybCgjZ3JpZCkiLz48L3N2Zz4=')] opacity-40"></div>

        <div class="relative">
            <a href="{{ route('admin.line-bot.keywords.index') }}"
               class="inline-flex items-center gap-2 text-white hover:text-green-50 mb-4 font-semibold transition-colors">
                <i class="fas fa-arrow-left"></i>
                <span>กลับไปยังรายการ Keywords</span>
            </a>

            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl glass-fusion backdrop-blur-xl flex items-center justify-center shadow-xl border border-white/20">
                    <i class="fas fa-chart-line text-white text-3xl drop-shadow-lg"></i>
                </div>
                <div>
                    <h1 class="text-4xl font-black text-white drop-shadow-lg tracking-tight">📊 Keyword Analytics</h1>
                    <p class="text-green-50 text-lg font-medium mt-2">วิเคราะห์การใช้งาน Keywords และประสิทธิภาพของ Hybrid Bot</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Statistics Cards พร้อม animated counters --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
        <div class="group glass-fusion backdrop-blur-xl rounded-2xl p-6 border border-white/20 dark:border-white/10 shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1 font-semibold">Keywords ทั้งหมด</p>
                    <h3 class="text-3xl font-black bg-gradient-to-r from-[#00B900] to-[#00E600] bg-clip-text text-transparent"
                        x-init="animateCounter($el, {{ $stats['total_keywords'] }})">0</h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#00B900] to-[#00E600] flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                    <i class="fas fa-key text-white text-xl"></i>
                </div>
            </div>
        </div>

        <div class="group glass-fusion backdrop-blur-xl rounded-2xl p-6 border border-white/20 dark:border-white/10 shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1 font-semibold">ใช้งานอยู่</p>
                    <h3 class="text-3xl font-black bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-transparent"
                        x-init="animateCounter($el, {{ $stats['active_keywords'] }})">0</h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                    <i class="fas fa-check-circle text-white text-xl"></i>
                </div>
            </div>
        </div>

        <div class="group glass-fusion backdrop-blur-xl rounded-2xl p-6 border border-white/20 dark:border-white/10 shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1 font-semibold">Avg Priority</p>
                    <h3 class="text-3xl font-black bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">{{ number_format($stats['avg_priority'], 1) }}</h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                    <i class="fas fa-arrow-up text-white text-xl"></i>
                </div>
            </div>
        </div>

        <div class="group glass-fusion backdrop-blur-xl rounded-2xl p-6 border border-white/20 dark:border-white/10 shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1 font-semibold">หมวดหมู่</p>
                    <h3 class="text-3xl font-black bg-gradient-to-r from-yellow-600 to-orange-600 bg-clip-text text-transparent"
                        x-init="animateCounter($el, {{ $stats['by_category']->count() }})">0</h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-yellow-500 to-orange-600 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                    <i class="fas fa-th text-white text-xl"></i>
                </div>
            </div>
        </div>

        <div class="group glass-fusion backdrop-blur-xl rounded-2xl p-6 border border-white/20 dark:border-white/10 shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1 font-semibold">ประเภทตอบ</p>
                    <h3 class="text-3xl font-black bg-gradient-to-r from-cyan-600 to-blue-600 bg-clip-text text-transparent"
                        x-init="animateCounter($el, {{ $stats['by_response_type']->count() }})">0</h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                    <i class="fas fa-reply text-white text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Section --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        {{-- Category Distribution --}}
        <div class="glass-fusion backdrop-blur-xl rounded-2xl shadow-xl border border-white/20 dark:border-white/10 p-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#00B900] to-[#00E600] flex items-center justify-center shadow-lg">
                    <i class="fas fa-pie-chart text-white"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Keywords by Category</h2>
            </div>
            <div class="aspect-square mb-4">
                <canvas id="categoryChart"></canvas>
            </div>
            <div class="space-y-2">
                @foreach($stats['by_category'] as $category => $count)
                    <div class="flex items-center justify-between p-3 glass-fusion backdrop-blur-sm rounded-lg">
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ ucfirst($category) }}</span>
                        <span class="text-lg font-black bg-gradient-to-r from-[#00B900] to-[#00E600] bg-clip-text text-transparent">{{ $count }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Response Type Distribution --}}
        <div class="glass-fusion backdrop-blur-xl rounded-2xl shadow-xl border border-white/20 dark:border-white/10 p-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center shadow-lg">
                    <i class="fas fa-chart-bar text-white"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Response Types</h2>
            </div>
            <div class="aspect-square mb-4">
                <canvas id="responseTypeChart"></canvas>
            </div>
            <div class="space-y-2">
                @foreach($stats['by_response_type'] as $type => $count)
                    <div class="flex items-center justify-between p-3 glass-fusion backdrop-blur-sm rounded-lg">
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ ucfirst($type) }}</span>
                        <span class="text-lg font-black bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-transparent">{{ $count }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Priority Distribution --}}
        <div class="glass-fusion backdrop-blur-xl rounded-2xl shadow-xl border border-white/20 dark:border-white/10 p-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center shadow-lg">
                    <i class="fas fa-signal text-white"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Priority Distribution</h2>
            </div>
            <div class="aspect-square mb-4">
                <canvas id="priorityChart"></canvas>
            </div>
            <div class="space-y-2">
                @foreach($priorityData['labels'] as $key => $label)
                    <div class="flex items-center justify-between p-3 glass-fusion backdrop-blur-sm rounded-lg">
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ $label }}</span>
                        <span class="text-lg font-black bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">{{ $priorityData['data'][$key] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Import/Export Section --}}
    <div class="glass-fusion backdrop-blur-xl rounded-2xl shadow-xl border border-white/20 dark:border-white/10 p-8 mb-8">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center shadow-lg">
                <i class="fas fa-exchange-alt text-white"></i>
            </div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Import/Export Keywords</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            {{-- Export --}}
            <div class="p-6 glass-fusion backdrop-blur-sm rounded-xl border border-gray-200 dark:border-gray-600">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 rounded-xl bg-green-100 dark:bg-green-500/20 flex items-center justify-center">
                        <i class="fas fa-download text-green-600 dark:text-green-400 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">📥 Export Keywords</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">บันทึกเป็น JSON</p>
                    </div>
                </div>
                <p class="text-gray-600 dark:text-gray-400 mb-4 text-sm">บันทึก Keywords ทั้งหมดเป็นไฟล์ JSON สำหรับสำรองข้อมูลหรือโอนย้าย</p>
                <a href="{{ route('admin.line-bot.keywords.export') }}"
                   class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-bold rounded-xl transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                    <i class="fas fa-download"></i>
                    <span>Export JSON</span>
                </a>
            </div>

            {{-- Import --}}
            <div class="p-6 glass-fusion backdrop-blur-sm rounded-xl border border-gray-200 dark:border-gray-600">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-100 dark:bg-blue-500/20 flex items-center justify-center">
                        <i class="fas fa-upload text-blue-600 dark:text-blue-400 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">📤 Import Keywords</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">นำเข้าจาก JSON</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.line-bot.keywords.import') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <input type="file" name="file" accept=".json" required
                        class="block w-full px-4 py-3 glass-fusion backdrop-blur-sm rounded-xl border-2 border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-[#00B900] file:text-white hover:file:bg-[#009900]">
                    <label class="flex items-center gap-2 p-3 glass-fusion backdrop-blur-sm rounded-lg cursor-pointer">
                        <input type="checkbox" name="skip_existing" value="1" class="w-4 h-4 text-[#00B900] rounded focus:ring-[#00B900]/20">
                        <span class="text-sm text-gray-700 dark:text-gray-300 font-medium">ข้ามถ้า Keyword มีอยู่แล้ว</span>
                    </label>
                    <button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white font-bold rounded-xl transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                        <i class="fas fa-upload mr-2"></i>Import JSON
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Keywords Table --}}
    <div class="glass-fusion backdrop-blur-xl rounded-2xl shadow-xl border border-white/20 dark:border-white/10 overflow-hidden">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg">
                    <i class="fas fa-list text-white"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Keywords List</h2>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-gray-100 to-gray-50 dark:from-gray-800 dark:to-gray-700">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">Keyword</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">Category</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-gray-900 dark:text-white">Priority</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-gray-900 dark:text-white">Status</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-gray-900 dark:text-white">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($keywords as $keyword)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-[#00B900]/20 flex items-center justify-center">
                                        <i class="fas fa-key text-[#00B900] dark:text-[#00E600]"></i>
                                    </div>
                                    <span class="font-bold text-gray-900 dark:text-white">{{ $keyword->keyword }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold
                                    @if($keyword->category === 'faq') bg-cyan-100 dark:bg-cyan-500/20 text-cyan-800 dark:text-cyan-300
                                    @elseif($keyword->category === 'support') bg-yellow-100 dark:bg-yellow-500/20 text-yellow-800 dark:text-yellow-300
                                    @elseif($keyword->category === 'product') bg-green-100 dark:bg-green-500/20 text-green-800 dark:text-green-300
                                    @else bg-gray-100 dark:bg-gray-500/20 text-gray-800 dark:text-gray-300
                                    @endif">
                                    {{ ucfirst($keyword->category) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="text-xl font-black bg-gradient-to-r from-[#00B900] to-[#00E600] bg-clip-text text-transparent">{{ $keyword->priority }}</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($keyword->is_active)
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-[#00B900]/20 text-[#00B900] dark:text-[#00E600]">
                                        <i class="fas fa-check-circle mr-1"></i>Active
                                    </span>
                                @else
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-100 dark:bg-red-500/20 text-red-800 dark:text-red-300">
                                        <i class="fas fa-ban mr-1"></i>Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('admin.line-bot.keywords.clone', $keyword) }}"
                                   class="inline-flex items-center gap-2 px-4 py-2 bg-purple-100 dark:bg-purple-500/20 text-purple-700 dark:text-purple-300 rounded-lg hover:bg-purple-200 dark:hover:bg-purple-500/30 transition text-sm font-semibold">
                                    <i class="fas fa-clone"></i>
                                    <span>Clone</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                <i class="fas fa-inbox text-4xl mb-4 opacity-50"></i>
                                <p class="font-semibold">ไม่มี Keywords</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('analyticsData', () => ({
        // Animated counter
        animateCounter(element, target) {
            let current = 0;
            const increment = target / 50;
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    element.textContent = target;
                    clearInterval(timer);
                } else {
                    element.textContent = Math.floor(current);
                }
            }, 20);
        }
    }));
});

// Category Chart
const categoryCtx = document.getElementById('categoryChart').getContext('2d');
new Chart(categoryCtx, {
    type: 'doughnut',
    data: {
        labels: @json($categoryData['labels']),
        datasets: [{
            data: @json($categoryData['data']),
            backgroundColor: [
                '#06B6D4',
                '#FBBF24',
                '#10B981',
                '#8B5CF6',
            ],
            borderColor: '#ffffff',
            borderWidth: 3,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false,
            }
        }
    }
});

// Response Type Chart
const responseTypeCtx = document.getElementById('responseTypeChart').getContext('2d');
new Chart(responseTypeCtx, {
    type: 'bar',
    data: {
        labels: @json($responseTypeData['labels']),
        datasets: [{
            label: 'Count',
            data: @json($responseTypeData['data']),
            backgroundColor: '#10B981',
            borderColor: '#059669',
            borderWidth: 2,
            borderRadius: 10,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        plugins: {
            legend: {
                display: false,
            }
        },
        scales: {
            x: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1,
                }
            }
        }
    }
});

// Priority Chart
const priorityCtx = document.getElementById('priorityChart').getContext('2d');
new Chart(priorityCtx, {
    type: 'doughnut',
    data: {
        labels: @json($priorityData['labels']),
        datasets: [{
            data: @json($priorityData['data']),
            backgroundColor: [
                '#EF4444',
                '#F59E0B',
                '#10B981',
            ],
            borderColor: '#ffffff',
            borderWidth: 3,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false,
            }
        }
    }
});
</script>
@endpush

@vite(['resources/js/app.js'])
@endsection
