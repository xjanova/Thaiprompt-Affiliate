@extends('layouts.admin-v3')

@section('title', 'Keyword Analytics & Performance')

@section('content')
<div class="container-fluid px-4 py-6">
    {{-- Header --}}
    <div class="mb-8">
        <a href="{{ route('admin.line-bot.keywords.index') }}" class="text-purple-600 hover:text-purple-700 mb-4 inline-block">
            <i class="fas fa-arrow-left mr-2"></i>กลับไปยังรายการ Keywords
        </a>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">📊 Keyword Analytics & Performance</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">วิเคราะห์การใช้งาน Keywords และประสิทธิภาพของ Hybrid Bot</p>
    </div>

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
        {{-- Total Keywords --}}
        <div class="glass-fusion rounded-xl p-6 border border-white/20 dark:border-white/10">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Keywords ทั้งหมด</p>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['total_keywords'] }}</h3>
                </div>
                <div class="w-12 h-12 rounded-lg bg-blue-100 dark:bg-blue-500/20 flex items-center justify-center">
                    <i class="fas fa-key text-blue-600 dark:text-blue-400 text-xl"></i>
                </div>
            </div>
        </div>

        {{-- Active Keywords --}}
        <div class="glass-fusion rounded-xl p-6 border border-white/20 dark:border-white/10">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">ใช้งานอยู่</p>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['active_keywords'] }}</h3>
                </div>
                <div class="w-12 h-12 rounded-lg bg-green-100 dark:bg-green-500/20 flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-600 dark:text-green-400 text-xl"></i>
                </div>
            </div>
        </div>

        {{-- Avg Priority --}}
        <div class="glass-fusion rounded-xl p-6 border border-white/20 dark:border-white/10">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Average Priority</p>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['avg_priority'], 1) }}</h3>
                </div>
                <div class="w-12 h-12 rounded-lg bg-purple-100 dark:bg-purple-500/20 flex items-center justify-center">
                    <i class="fas fa-arrow-up text-purple-600 dark:text-purple-400 text-xl"></i>
                </div>
            </div>
        </div>

        {{-- Categories --}}
        <div class="glass-fusion rounded-xl p-6 border border-white/20 dark:border-white/10">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">หมวดหมู่</p>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['by_category']->count() }}</h3>
                </div>
                <div class="w-12 h-12 rounded-lg bg-yellow-100 dark:bg-yellow-500/20 flex items-center justify-center">
                    <i class="fas fa-th text-yellow-600 dark:text-yellow-400 text-xl"></i>
                </div>
            </div>
        </div>

        {{-- Response Types --}}
        <div class="glass-fusion rounded-xl p-6 border border-white/20 dark:border-white/10">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">ประเภทการตอบ</p>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['by_response_type']->count() }}</h3>
                </div>
                <div class="w-12 h-12 rounded-lg bg-pink-100 dark:bg-pink-500/20 flex items-center justify-center">
                    <i class="fas fa-reply text-pink-600 dark:text-pink-400 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Section --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        {{-- Category Distribution --}}
        <div class="glass-fusion rounded-2xl shadow-lg border border-white/20 dark:border-white/10 p-8">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">
                <i class="fas fa-pie-chart mr-2 text-blue-600"></i>Keywords by Category
            </h2>
            <div class="aspect-square">
                <canvas id="categoryChart"></canvas>
            </div>
            <div class="mt-4 space-y-2">
                @foreach($stats['by_category'] as $category => $count)
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">{{ ucfirst($category) }}</span>
                        <span class="font-bold text-gray-900 dark:text-white">{{ $count }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Response Type Distribution --}}
        <div class="glass-fusion rounded-2xl shadow-lg border border-white/20 dark:border-white/10 p-8">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">
                <i class="fas fa-chart-bar mr-2 text-green-600"></i>Response Types
            </h2>
            <div class="aspect-square">
                <canvas id="responseTypeChart"></canvas>
            </div>
            <div class="mt-4 space-y-2">
                @foreach($stats['by_response_type'] as $type => $count)
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">{{ ucfirst($type) }}</span>
                        <span class="font-bold text-gray-900 dark:text-white">{{ $count }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Priority Distribution --}}
        <div class="glass-fusion rounded-2xl shadow-lg border border-white/20 dark:border-white/10 p-8">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">
                <i class="fas fa-bar-chart mr-2 text-purple-600"></i>Priority Distribution
            </h2>
            <div class="aspect-square">
                <canvas id="priorityChart"></canvas>
            </div>
            <div class="mt-4 space-y-2">
                @foreach($priorityData['labels'] as $key => $label)
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">{{ $label }}</span>
                        <span class="font-bold text-gray-900 dark:text-white">{{ $priorityData['data'][$key] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Import/Export Section --}}
    <div class="glass-fusion rounded-2xl shadow-lg border border-white/20 dark:border-white/10 p-8 mb-8">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">
            <i class="fas fa-exchange-alt mr-2 text-cyan-600"></i>Import/Export Keywords
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            {{-- Export --}}
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">📥 Export Keywords</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4">บันทึก Keywords ทั้งหมดเป็นไฟล์ JSON</p>
                <a href="{{ route('admin.line-bot.keywords.export') }}"
                   class="inline-block px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-bold rounded-lg transition">
                    <i class="fas fa-download mr-2"></i>Export JSON
                </a>
            </div>

            {{-- Import --}}
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">📤 Import Keywords</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4">นำเข้า Keywords จากไฟล์ JSON</p>
                <form method="POST" action="{{ route('admin.line-bot.keywords.import') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <input type="file" name="file" accept=".json" required
                        class="block w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="skip_existing" value="1" class="w-4 h-4">
                        <span class="text-gray-700 dark:text-gray-300">ข้ามถ้า Keyword มีอยู่แล้ว</span>
                    </label>
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white font-bold rounded-lg transition">
                        <i class="fas fa-upload mr-2"></i>Import JSON
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Keywords List for Cloning --}}
    <div class="glass-fusion rounded-2xl shadow-lg border border-white/20 dark:border-white/10 p-8">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">
            <i class="fas fa-list mr-2 text-indigo-600"></i>Keywords List
        </h2>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-gray-100 to-gray-50 dark:from-gray-800 dark:to-gray-700 border-b border-gray-200 dark:border-gray-600">
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
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">{{ $keyword->keyword }}</td>
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
                                <span class="font-bold text-gray-900 dark:text-white">{{ $keyword->priority }}</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($keyword->is_active)
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 dark:bg-green-500/20 text-green-800 dark:text-green-300">
                                        ✓ Active
                                    </span>
                                @else
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-100 dark:bg-red-500/20 text-red-800 dark:text-red-300">
                                        ✗ Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('admin.line-bot.keywords.clone', $keyword) }}"
                                   class="px-4 py-2 bg-purple-100 dark:bg-purple-500/20 text-purple-700 dark:text-purple-300 rounded-lg hover:bg-purple-200 dark:hover:bg-purple-500/30 transition text-sm font-semibold inline-block">
                                    <i class="fas fa-clone mr-1"></i>Clone
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                ไม่มี Keywords
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
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
                borderColor: ['#ffffff', '#ffffff', '#ffffff', '#ffffff'],
                borderWidth: 2,
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
                borderRadius: 8,
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
                borderColor: ['#ffffff', '#ffffff', '#ffffff'],
                borderWidth: 2,
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
@endsection
