@extends('layouts.admin-v3')

@section('title', 'Analytics - AI Chatbot')

@section('content')
<div class="container-fluid px-4 py-6">
    <!-- Premium Header -->
    <div class="relative mb-8 overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-600 via-purple-700 to-pink-900 p-10 shadow-2xl">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS1vcGFjaXR5PSIwLjA1IiBzdHJva2Utd2lkdGg9IjEiLz48L3BhdHRlcm4+PC9kZWZzPjxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbGw9InVybCgjZ3JpZCkiLz48L3N2Zz4=')] opacity-40"></div>

        <div class="relative flex items-center justify-between">
            <div class="flex-1">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-white/25 to-white/10 backdrop-blur-lg flex items-center justify-center shadow-xl border border-white/20">
                        <i class="fas fa-chart-bar text-4xl text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-4xl font-black text-white mb-2 drop-shadow-lg">📊 Analytics Dashboard</h1>
                        <p class="text-purple-100 text-lg font-medium">วิเคราะห์ประสิทธิภาพและการใช้งาน AI Chatbot</p>
                    </div>
                </div>
            </div>
            <a href="{{ route('admin.line-bot.ai.index') }}"
               class="px-6 py-3 glass-fusion backdrop-blur-md border border-white/25 text-white rounded-xl hover:glass-fusion transition-all duration-300 shadow-lg hover:shadow-2xl transform hover:-translate-y-1">
                <i class="fas fa-arrow-left mr-2"></i>
                <span class="font-semibold">กลับไปตั้งค่า</span>
            </a>
        </div>
    </div>

    <!-- Main Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div style="background: var(--arrow-x-primary-gradient)" class="rounded-2xl p-6 text-white shadow-xl hover:shadow-2xl transition-all transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm font-medium mb-1">การตั้งค่า AI</p>
                    <h3 class="text-4xl font-bold mb-1">{{ $stats['total_ai_settings'] }}</h3>
                    <p class="text-xs text-blue-200">{{ $stats['active_ai_settings'] }} เปิดใช้งาน</p>
                </div>
                <div class="w-14 h-14 glass-fusion rounded-xl flex items-center justify-center border border-white/20 dark:border-white/10">
                    <i class="fas fa-robot text-2xl"></i>
                </div>
            </div>
        </div>

        <div style="background: var(--arrow-x-accent-gradient)" class="rounded-2xl p-6 text-white shadow-xl hover:shadow-2xl transition-all transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm font-medium mb-1">ฐานความรู้</p>
                    <h3 class="text-4xl font-bold mb-1">{{ $stats['total_knowledge_bases'] }}</h3>
                    <p class="text-xs text-purple-200">แหล่งข้อมูลทั้งหมด</p>
                </div>
                <div class="w-14 h-14 glass-fusion rounded-xl flex items-center justify-center border border-white/20 dark:border-white/10">
                    <i class="fas fa-book text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-2xl p-6 text-white shadow-xl hover:shadow-2xl transition-all transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm font-medium mb-1">บทสนทนา</p>
                    <h3 class="text-4xl font-bold mb-1">{{ number_format($stats['total_conversations']) }}</h3>
                    <p class="text-xs text-green-200">{{ $stats['today_conversations'] }} วันนี้</p>
                </div>
                <div class="w-14 h-14 glass-fusion rounded-xl flex items-center justify-center border border-white/20 dark:border-white/10">
                    <i class="fas fa-comments text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-2xl p-6 text-white shadow-xl hover:shadow-2xl transition-all transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-orange-100 text-sm font-medium mb-1">ข้อความ</p>
                    <h3 class="text-4xl font-bold mb-1">{{ number_format($stats['total_messages']) }}</h3>
                    <p class="text-xs text-orange-200">{{ $stats['today_messages'] }} วันนี้</p>
                </div>
                <div class="w-14 h-14 glass-fusion rounded-xl flex items-center justify-center border border-white/20 dark:border-white/10">
                    <i class="fas fa-envelope text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Conversations Chart -->
        <div class="glass-fusion rounded-2xl shadow-xl border border-gray-100 p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-xl font-bold text-gray-900">บทสนทนาย้อนหลัง 7 วัน</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">จำนวนบทสนทนาแต่ละวัน</p>
                </div>
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-chart-line text-white text-xl"></i>
                </div>
            </div>
            <div class="h-64">
                <canvas id="conversationsChart"></canvas>
            </div>
        </div>

        <!-- Provider Distribution -->
        <div class="glass-fusion rounded-2xl shadow-xl border border-gray-100 p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-xl font-bold text-gray-900">การกระจายของ AI Provider</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">จำนวนข้อความต่อ Provider</p>
                </div>
                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-teal-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-chart-pie text-white text-xl"></i>
                </div>
            </div>
            <div class="h-64">
                <canvas id="providerChart"></canvas>
            </div>
        </div>
    </div>

    <!-- AI Settings Performance -->
    <div class="glass-fusion rounded-2xl shadow-xl border border-gray-100 overflow-hidden border border-white/20 dark:border-white/10">
        <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
            <h2 class="text-xl font-bold text-gray-900">ประสิทธิภาพของแต่ละ AI Setting</h2>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse($aiSettings as $setting)
                    <div class="p-5 rounded-xl border-2 border-gray-200 dark:border-gray-700 hover:border-purple-300 transition-all hover:shadow-lg">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br
                                @if($setting->provider === 'openai') from-green-500 to-emerald-600
                                @elseif($setting->provider === 'deepseek') from-blue-500 to-indigo-600
                                @elseif($setting->provider === 'anthropic') from-orange-500 to-red-600
                                @elseif($setting->provider === 'gemini') from-purple-500 to-pink-600
                                @else from-gray-500 to-gray-600
                                @endif flex items-center justify-center shadow-md">
                                <i class="fas fa-brain text-white text-lg"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-bold text-gray-900 truncate">{{ $setting->name }}</h4>
                                <p class="text-xs text-gray-600 dark:text-gray-400 uppercase font-semibold">{{ $setting->provider }}</p>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center justify-between p-2 bg-blue-50 rounded-xl">
                                <span class="text-xs font-medium text-blue-700">Knowledge Bases</span>
                                <span class="px-2 py-1 bg-blue-600 text-white rounded-md text-xs font-bold">
                                    {{ $setting->knowledgeBases->count() }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between p-2 bg-purple-50 rounded-xl">
                                <span class="text-xs font-medium text-purple-700">Status</span>
                                @if($setting->is_active)
                                    <span class="px-2 py-1 bg-green-600 text-white rounded-md text-xs font-bold flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 bg-green-300 rounded-full animate-pulse"></span>
                                        Active
                                    </span>
                                @else
                                    <span class="px-2 py-1 bg-gray-400 text-white rounded-md text-xs font-semibold">
                                        Inactive
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-3 text-center py-8 text-gray-500 dark:text-gray-400">
                        <i class="fas fa-inbox text-4xl mb-2"></i>
                        <p>ยังไม่มีการตั้งค่า AI</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Conversations Chart
const conversationsCtx = document.getElementById('conversationsChart').getContext('2d');
new Chart(conversationsCtx, {
    type: 'line',
    data: {
        labels: {!! json_encode($conversationsPerDay->pluck('date')) !!},
        datasets: [{
            label: 'บทสนทนา',
            data: {!! json_encode($conversationsPerDay->pluck('count')) !!},
            borderColor: 'rgb(99, 102, 241)',
            backgroundColor: 'rgba(99, 102, 241, 0.1)',
            tension: 0.4,
            fill: true,
            borderWidth: 3
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
                    precision: 0
                }
            }
        }
    }
});

// Provider Chart
const providerCtx = document.getElementById('providerChart').getContext('2d');
new Chart(providerCtx, {
    type: 'doughnut',
    data: {
        labels: {!! json_encode($messagesPerProvider->pluck('provider')->map(fn($p) => strtoupper($p))) !!},
        datasets: [{
            data: {!! json_encode($messagesPerProvider->pluck('count')) !!},
            backgroundColor: [
                'rgba(16, 185, 129, 0.8)',
                'rgba(59, 130, 246, 0.8)',
                'rgba(249, 115, 22, 0.8)',
                'rgba(168, 85, 247, 0.8)',
                'rgba(107, 114, 128, 0.8)'
            ],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
</script>
@endpush
@endsection
