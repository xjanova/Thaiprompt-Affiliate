@extends('layouts.admin')

@section('title', 'แดชบอร์ดวิเคราะห์บอท')

@section('content')
<div class="space-y-6">
    {{-- Header พร้อม Gradient และ Pattern สำหรับ Analytics --}}
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-violet-500 via-purple-600 to-fuchsia-700 dark:from-violet-900 dark:via-purple-900 dark:to-fuchsia-950 p-8 shadow-2xl">
        {{-- Background Pattern SVG --}}
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS1vcGFjaXR5PSIwLjEiIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNncmlkKSIvPjwvc3ZnPg==')] opacity-30"></div>

        <div class="relative flex items-center justify-between w-full">
            <div class="flex items-center gap-4">
                {{-- Icon พร้อม Animation --}}
                <div class="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center border-2 border-white/30 animate-pulse">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <div>
                    <h1 data-translate class="text-4xl font-bold text-white mb-2">แดชบอร์ดวิเคราะห์บอท</h1>
                    <p data-translate class="text-violet-100 text-lg">ข้อมูลการทำงานและประสิทธิภาพของบอทอัตโนมัติ</p>
                </div>
            </div>

            {{-- Language Switcher --}}
            <div class="relative inline-block" x-data="{ open: false }">
                <button @click="open = !open" class="px-4 py-2 bg-white/20 backdrop-blur-sm text-white rounded-xl hover:bg-white/30 transition-all duration-200 border border-white/30 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                    </svg>
                    <span data-translate>ภาษา</span>
                </button>

                <div x-show="open" @click.away="open = false" x-transition
                     class="absolute right-0 mt-2 w-48 bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-gray-200 dark:border-slate-700 overflow-hidden z-50">
                    <a href="/lang/th" class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                        <span class="mr-2">🇹🇭</span> <span data-translate>ไทย</span>
                    </a>
                    <a href="/lang/en" class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                        <span class="mr-2">🇬🇧</span> English
                    </a>
                    <a href="/lang/zh" class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                        <span class="mr-2">🇨🇳</span> 中文
                    </a>
                    <a href="/lang/ja" class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                        <span class="mr-2">🇯🇵</span> 日本語
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Stats Cards Grid พร้อม Gradient แบบ Modern --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {{-- Card 1: Total Executions --}}
        <div class="bg-gradient-to-br from-violet-50 to-purple-100 dark:from-violet-900/20 dark:to-purple-800/20 rounded-2xl shadow-lg border border-violet-200 dark:border-violet-800 overflow-hidden transform transition-all duration-300 hover:scale-105 hover:shadow-xl animate-fade-in">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center shadow-lg">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>
                <div class="space-y-1">
                    <p data-translate class="text-sm font-semibold text-violet-700 dark:text-violet-300 uppercase tracking-wide">ครั้งที่ทำงานทั้งหมด</p>
                    <p class="text-4xl font-bold text-violet-600 dark:text-violet-400">{{ number_format($totalExecutions ?? 0) }}</p>
                </div>
            </div>
        </div>

        {{-- Card 2: Success Rate --}}
        <div class="bg-gradient-to-br from-green-50 to-emerald-100 dark:from-green-900/20 dark:to-emerald-800/20 rounded-2xl shadow-lg border border-green-200 dark:border-green-800 overflow-hidden transform transition-all duration-300 hover:scale-105 hover:shadow-xl animate-fade-in" style="animation-delay: 0.1s;">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center shadow-lg">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="space-y-1">
                    <p data-translate class="text-sm font-semibold text-green-700 dark:text-green-300 uppercase tracking-wide">อัตราความสำเร็จ</p>
                    <p class="text-4xl font-bold text-green-600 dark:text-green-400">{{ $successRate ?? '0' }}%</p>
                </div>
            </div>
        </div>

        {{-- Card 3: Active Bots --}}
        <div class="bg-gradient-to-br from-cyan-50 to-blue-100 dark:from-cyan-900/20 dark:to-blue-800/20 rounded-2xl shadow-lg border border-cyan-200 dark:border-cyan-800 overflow-hidden transform transition-all duration-300 hover:scale-105 hover:shadow-xl animate-fade-in" style="animation-delay: 0.2s;">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center shadow-lg">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="space-y-1">
                    <p data-translate class="text-sm font-semibold text-cyan-700 dark:text-cyan-300 uppercase tracking-wide">บอทที่ทำงาน</p>
                    <p class="text-4xl font-bold text-cyan-600 dark:text-cyan-400">{{ $activeBots ?? '0' }}</p>
                </div>
            </div>
        </div>

        {{-- Card 4: Avg Response Time --}}
        <div class="bg-gradient-to-br from-amber-50 to-orange-100 dark:from-amber-900/20 dark:to-orange-800/20 rounded-2xl shadow-lg border border-amber-200 dark:border-amber-800 overflow-hidden transform transition-all duration-300 hover:scale-105 hover:shadow-xl animate-fade-in" style="animation-delay: 0.3s;">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center shadow-lg">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="space-y-1">
                    <p data-translate class="text-sm font-semibold text-amber-700 dark:text-amber-300 uppercase tracking-wide">เวลาตอบสนองเฉลี่ย</p>
                    <p class="text-4xl font-bold text-amber-600 dark:text-amber-400">{{ $avgResponseTime ?? '0' }}<span class="text-xl">ms</span></p>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content Grid: Chart + Quick Links --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Analytics Chart (2 columns) --}}
        <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
            <div class="bg-gradient-to-r from-violet-500 to-fuchsia-600 dark:from-violet-900 dark:to-fuchsia-900 px-6 py-4">
                <h3 class="text-xl font-bold text-white flex items-center">
                    <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                    </svg>
                    <span data-translate>ภาพรวมประสิทธิภาพบอท</span>
                </h3>
            </div>
            <div class="p-6">
                <div class="chart-area" style="min-height: 400px;">
                    <canvas id="botPerformanceChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Quick Access Links (1 column) --}}
        <div class="space-y-6">
            {{-- Sticky Sidebar --}}
            <div class="bg-gradient-to-br from-violet-600 to-fuchsia-700 dark:from-violet-900 dark:to-fuchsia-950 rounded-2xl shadow-2xl p-6 text-white sticky top-6">
                <h3 class="text-xl font-bold mb-6 flex items-center">
                    <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <span data-translate>เข้าถึงอย่างรวดเร็ว</span>
                </h3>

                <div class="space-y-3">
                    {{-- Link 1: Executions --}}
                    <a href="{{ route('admin.bot-automation.analytics.executions') }}"
                       class="flex items-center gap-3 p-4 bg-white/10 hover:bg-white/20 backdrop-blur-sm rounded-xl transition-all duration-200 transform hover:scale-105 border border-white/20">
                        <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p data-translate class="font-semibold text-white">ดูการทำงาน</p>
                            <p data-translate class="text-xs text-violet-200">รายละเอียดการ Execute</p>
                        </div>
                        <svg class="w-5 h-5 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>

                    {{-- Link 2: Engagement --}}
                    <a href="{{ route('admin.bot-automation.analytics.engagement') }}"
                       class="flex items-center gap-3 p-4 bg-white/10 hover:bg-white/20 backdrop-blur-sm rounded-xl transition-all duration-200 transform hover:scale-105 border border-white/20">
                        <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p data-translate class="font-semibold text-white">Engagement Metrics</p>
                            <p data-translate class="text-xs text-violet-200">วัดการมีส่วนร่วม</p>
                        </div>
                        <svg class="w-5 h-5 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>

                    {{-- Link 3: Performance --}}
                    <a href="{{ route('admin.bot-automation.analytics.performance') }}"
                       class="flex items-center gap-3 p-4 bg-white/10 hover:bg-white/20 backdrop-blur-sm rounded-xl transition-all duration-200 transform hover:scale-105 border border-white/20">
                        <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p data-translate class="font-semibold text-white">รายละเอียดประสิทธิภาพ</p>
                            <p data-translate class="text-xs text-violet-200">ข้อมูล Performance</p>
                        </div>
                        <svg class="w-5 h-5 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>

                    {{-- Link 4: Platforms --}}
                    <a href="{{ route('admin.bot-automation.analytics.platforms') }}"
                       class="flex items-center gap-3 p-4 bg-white/10 hover:bg-white/20 backdrop-blur-sm rounded-xl transition-all duration-200 transform hover:scale-105 border border-white/20">
                        <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p data-translate class="font-semibold text-white">สถิติแพลตฟอร์ม</p>
                            <p data-translate class="text-xs text-violet-200">ข้อมูลแต่ละแพลตฟอร์ม</p>
                        </div>
                        <svg class="w-5 h-5 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Animations Style --}}
@push('styles')
<style>
    @keyframes fade-in {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-fade-in {
        animation: fade-in 0.5s ease-out forwards;
        opacity: 0;
    }
</style>
@endpush
@endsection
