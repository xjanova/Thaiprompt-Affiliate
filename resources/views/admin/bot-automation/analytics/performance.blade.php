@extends('layouts.admin-v3')

@section('title', 'การวิเคราะห์ประสิทธิภาพบอท')

@push('styles')
<style>
    @keyframes fade-in {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-fade-in {
        animation: fade-in 0.5s ease-out forwards;
    }

    .animate-fade-in:nth-child(1) { animation-delay: 0.1s; }
    .animate-fade-in:nth-child(2) { animation-delay: 0.2s; }
    .animate-fade-in:nth-child(3) { animation-delay: 0.3s; }
    .animate-fade-in:nth-child(4) { animation-delay: 0.4s; }
</style>
@endpush

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- Header พร้อม gradient background --}}
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-violet-500 via-purple-600 to-fuchsia-700 dark:from-violet-900 dark:via-purple-900 dark:to-fuchsia-950 p-8 shadow-2xl">
        {{-- SVG Pattern Background --}}
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iMC4xIj48cGF0aCBkPSJNMzYgMzBoLTZ2LTZoNnYtNmg2djZoNnY2aC02djZoLTZ2LTZ6Ii8+PC9nPjwvZz48L3N2Zz4=')] opacity-30"></div>

        <div class="relative flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl glass-fusion backdrop-blur-sm flex items-center justify-center border-2 border-white/30 animate-pulse" border border-white/20 dark:border-white/10>
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <div>
                    <h1 data-translate class="text-4xl font-bold text-white mb-2">การวิเคราะห์ประสิทธิภาพ</h1>
                    <p data-translate class="text-violet-100 text-lg">ติดตามและวิเคราะห์ประสิทธิภาพการทำงานของบอท</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                {{-- Language Switcher --}}
                <div class="relative inline-block" x-data="{ open: false }">
                    <button @click="open = !open" class="px-4 py-2 glass-fusion backdrop-blur-sm text-white rounded-xl hover:glass-fusion transition-all duration-200 border border-white/30 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                        </svg>
                        <span data-translate>ภาษา</span>
                    </button>

                    <div x-show="open" @click.away="open = false" x-transition
                         class="absolute right-0 mt-2 w-48 glass-fusion dark:bg-slate-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 dark:border-slate-700 overflow-hidden z-50" border border-white/20 dark:border-white/10>
                        <a href="/lang/th" class="block px-4 py-3 hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-slate-700 transition-colors">
                            <span class="mr-2">🇹🇭</span> <span data-translate>ไทย</span>
                        </a>
                        <a href="/lang/en" class="block px-4 py-3 hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-slate-700 transition-colors">
                            <span class="mr-2">🇬🇧</span> English
                        </a>
                        <a href="/lang/zh" class="block px-4 py-3 hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-slate-700 transition-colors">
                            <span class="mr-2">🇨🇳</span> 中文
                        </a>
                        <a href="/lang/ja" class="block px-4 py-3 hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-slate-700 transition-colors">
                            <span class="mr-2">🇯🇵</span> 日本語
                        </a>
                    </div>
                </div>

                <a href="{{ route('admin.bot-automation.analytics.index') }}"
                   class="px-6 py-3 glass-fusion backdrop-blur-sm text-white rounded-xl font-semibold hover:glass-fusion transition-all duration-300 border-2 border-white/30 flex items-center gap-2 shadow-lg hover:shadow-xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span data-translate>กลับไปแดshบอร์ด</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {{-- Card 1: Avg Response Time --}}
        <div class="bg-gradient-to-br from-violet-50 to-purple-100 dark:from-violet-900/20 dark:to-purple-800/20 rounded-2xl shadow-lg border border-violet-200 dark:border-violet-800 overflow-hidden transform transition-all duration-300 hover:scale-105 hover:shadow-xl animate-fade-in">
            <div class="p-6">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center shadow-lg mb-4">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p data-translate class="text-sm font-semibold text-violet-700 dark:text-violet-300 uppercase tracking-wide mb-1">เวลาตอบกลับเฉลี่ย</p>
                <p class="text-4xl font-bold text-violet-600 dark:text-violet-400">{{ $avgResponseTime ?? '0' }}<span class="text-xl">ms</span></p>
            </div>
        </div>

        {{-- Card 2: Success Rate --}}
        <div class="bg-gradient-to-br from-emerald-50 to-green-100 dark:from-emerald-900/20 dark:to-green-800/20 rounded-2xl shadow-lg border border-emerald-200 dark:border-emerald-800 overflow-hidden transform transition-all duration-300 hover:scale-105 hover:shadow-xl animate-fade-in">
            <div class="p-6">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-emerald-500 to-green-600 flex items-center justify-center shadow-lg mb-4">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p data-translate class="text-sm font-semibold text-emerald-700 dark:text-emerald-300 uppercase tracking-wide mb-1">อัตราความสำเร็จ</p>
                <p class="text-4xl font-bold text-emerald-600 dark:text-emerald-400">{{ $successRate ?? '0' }}<span class="text-xl">%</span></p>
            </div>
        </div>

        {{-- Card 3: Uptime --}}
        <div class="bg-gradient-to-br from-cyan-50 to-blue-100 dark:from-cyan-900/20 dark:to-blue-800/20 rounded-2xl shadow-lg border border-cyan-200 dark:border-cyan-800 overflow-hidden transform transition-all duration-300 hover:scale-105 hover:shadow-xl animate-fade-in">
            <div class="p-6">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center shadow-lg mb-4">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                    </svg>
                </div>
                <p data-translate class="text-sm font-semibold text-cyan-700 dark:text-cyan-300 uppercase tracking-wide mb-1">เวลาทำงาน</p>
                <p class="text-4xl font-bold text-cyan-600 dark:text-cyan-400">{{ $uptime ?? '0' }}<span class="text-xl">%</span></p>
            </div>
        </div>

        {{-- Card 4: Error Rate --}}
        <div class="bg-gradient-to-br from-amber-50 to-orange-100 dark:from-amber-900/20 dark:to-orange-800/20 rounded-2xl shadow-lg border border-amber-200 dark:border-amber-800 overflow-hidden transform transition-all duration-300 hover:scale-105 hover:shadow-xl animate-fade-in">
            <div class="p-6">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center shadow-lg mb-4">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <p data-translate class="text-sm font-semibold text-amber-700 dark:text-amber-300 uppercase tracking-wide mb-1">อัตราข้อผิดพลาด</p>
                <p class="text-4xl font-bold text-amber-600 dark:text-amber-400">{{ $errorRate ?? '0' }}<span class="text-xl">%</span></p>
            </div>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        {{-- Performance Trends Chart --}}
        <div class="lg:col-span-2 glass-fusion dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 dark:border-slate-700 overflow-hidden" border border-white/20 dark:border-white/10>
            <div class="border-b border-gray-200 dark:border-gray-700 dark:border-slate-700 bg-gradient-to-r from-violet-50 to-purple-50 dark:from-slate-800 dark:to-slate-800 px-6 py-4">
                <h2 data-translate class="text-xl font-bold text-gray-900 dark:text-white dark:text-white flex items-center gap-2">
                    <svg class="w-6 h-6 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    แนวโน้มประสิทธิภาพ
                </h2>
            </div>
            <div class="p-6">
                <div class="chart-area">
                    <canvas id="performanceChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Top Performing Bots --}}
        <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 dark:border-slate-700 overflow-hidden" border border-white/20 dark:border-white/10>
            <div class="border-b border-gray-200 dark:border-gray-700 dark:border-slate-700 bg-gradient-to-r from-violet-50 to-purple-50 dark:from-slate-800 dark:to-slate-800 px-6 py-4">
                <h2 data-translate class="text-xl font-bold text-gray-900 dark:text-white dark:text-white flex items-center gap-2">
                    <svg class="w-6 h-6 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                    </svg>
                    บอทที่มีประสิทธิภาพสูงสุด
                </h2>
            </div>
            <div class="p-6">
                <div class="space-y-3">
                    @forelse($topBots ?? [] as $index => $bot)
                    <div class="p-4 rounded-xl bg-gradient-to-r from-violet-50 to-purple-50 dark:from-violet-900/10 dark:to-purple-900/10 border border-violet-200 dark:border-violet-800 hover:shadow-md transition-all duration-200">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center text-white font-bold text-sm">
                                    {{ $index + 1 }}
                                </div>
                                <h3 class="font-semibold text-gray-900 dark:text-white">{{ $bot->name }}</h3>
                            </div>
                            <span class="text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ $bot->success_rate ?? '0' }}%</span>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400 ml-11">{{ $bot->executions ?? '0' }} <span data-translate>การทำงาน</span></p>
                    </div>
                    @empty
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400 dark:text-gray-400">
                        <svg class="w-12 h-12 mx-auto mb-3 text-gray-400 dark:text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                        <p data-translate class="font-medium">ไม่มีข้อมูลประสิทธิภาพ</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Bot Performance Breakdown Table --}}
    <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 dark:border-slate-700 overflow-hidden" border border-white/20 dark:border-white/10>
        <div class="border-b border-gray-200 dark:border-gray-700 dark:border-slate-700 bg-gradient-to-r from-violet-50 to-purple-50 dark:from-slate-800 dark:to-slate-800 px-6 py-4">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white dark:text-white flex items-center gap-2">
                <svg class="w-6 h-6 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                รายละเอียดประสิทธิภาพแต่ละบอท
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                <thead class="bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-slate-900">
                    <tr>
                        <th data-translate scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">ชื่อบอท</th>
                        <th data-translate scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">การทำงาน</th>
                        <th data-translate scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">อัตราสำเร็จ</th>
                        <th data-translate scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">เวลาตอบกลับเฉลี่ย</th>
                        <th data-translate scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">จำนวนข้อผิดพลาด</th>
                        <th data-translate scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">ทำงานล่าสุด</th>
                    </tr>
                </thead>
                <tbody class="glass-fusion dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse($performanceData ?? [] as $data)
                    <tr class="hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-slate-700/50 transition-colors duration-200">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">{{ $data->bot_name ?? 'N/A' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300">{{ $data->executions ?? '0' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300">{{ $data->success_rate ?? '0' }}%</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300">{{ $data->avg_response_time ?? '0' }} ms</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300">{{ $data->error_count ?? '0' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300">{{ $data->last_execution ?? 'N/A' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400 dark:text-gray-400">
                            <div class="flex flex-col items-center justify-center">
                                <svg class="w-16 h-16 text-gray-400 dark:text-gray-600 dark:text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                </svg>
                                <p data-translate class="text-lg font-medium">ไม่มีข้อมูลประสิทธิภาพ</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
