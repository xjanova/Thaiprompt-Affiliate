@extends('layouts.admin')

@section('title', 'การวิเคราะห์การมีส่วนร่วมของบอท')

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
                <div class="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center border-2 border-white/30 animate-pulse">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-4xl font-bold text-white mb-2">การวิเคราะห์การมีส่วนร่วม</h1>
                    <p class="text-violet-100 text-lg">วิเคราะห์พฤติกรรมและการใช้งานของผู้ใช้กับบอท</p>
                </div>
            </div>
            <a href="{{ route('admin.bot-automation.analytics.index') }}"
               class="px-6 py-3 bg-white/20 backdrop-blur-sm text-white rounded-xl font-semibold hover:bg-white/30 transition-all duration-300 border-2 border-white/30 flex items-center gap-2 shadow-lg hover:shadow-xl">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                กลับไปแดชบอร์ด
            </a>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {{-- Card 1: Total Users --}}
        <div class="bg-gradient-to-br from-violet-50 to-purple-100 dark:from-violet-900/20 dark:to-purple-800/20 rounded-2xl shadow-lg border border-violet-200 dark:border-violet-800 overflow-hidden transform transition-all duration-300 hover:scale-105 hover:shadow-xl animate-fade-in">
            <div class="p-6">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center shadow-lg mb-4">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <p class="text-sm font-semibold text-violet-700 dark:text-violet-300 uppercase tracking-wide mb-1">ผู้ใช้ทั้งหมด</p>
                <p class="text-4xl font-bold text-violet-600 dark:text-violet-400">{{ $totalUsers ?? '0' }}</p>
            </div>
        </div>

        {{-- Card 2: Active Users --}}
        <div class="bg-gradient-to-br from-emerald-50 to-green-100 dark:from-emerald-900/20 dark:to-green-800/20 rounded-2xl shadow-lg border border-emerald-200 dark:border-emerald-800 overflow-hidden transform transition-all duration-300 hover:scale-105 hover:shadow-xl animate-fade-in">
            <div class="p-6">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-emerald-500 to-green-600 flex items-center justify-center shadow-lg mb-4">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-300 uppercase tracking-wide mb-1">ผู้ใช้ที่ใช้งาน</p>
                <p class="text-4xl font-bold text-emerald-600 dark:text-emerald-400">{{ $activeUsers ?? '0' }}</p>
            </div>
        </div>

        {{-- Card 3: Total Interactions --}}
        <div class="bg-gradient-to-br from-cyan-50 to-blue-100 dark:from-cyan-900/20 dark:to-blue-800/20 rounded-2xl shadow-lg border border-cyan-200 dark:border-cyan-800 overflow-hidden transform transition-all duration-300 hover:scale-105 hover:shadow-xl animate-fade-in">
            <div class="p-6">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center shadow-lg mb-4">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </div>
                <p class="text-sm font-semibold text-cyan-700 dark:text-cyan-300 uppercase tracking-wide mb-1">การโต้ตอบทั้งหมด</p>
                <p class="text-4xl font-bold text-cyan-600 dark:text-cyan-400">{{ $totalInteractions ?? '0' }}</p>
            </div>
        </div>

        {{-- Card 4: Avg Sessions --}}
        <div class="bg-gradient-to-br from-amber-50 to-orange-100 dark:from-amber-900/20 dark:to-orange-800/20 rounded-2xl shadow-lg border border-amber-200 dark:border-amber-800 overflow-hidden transform transition-all duration-300 hover:scale-105 hover:shadow-xl animate-fade-in">
            <div class="p-6">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center shadow-lg mb-4">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                    </svg>
                </div>
                <p class="text-sm font-semibold text-amber-700 dark:text-amber-300 uppercase tracking-wide mb-1">เซสชั่นเฉลี่ย</p>
                <p class="text-4xl font-bold text-amber-600 dark:text-amber-400">{{ $avgSessions ?? '0' }}</p>
            </div>
        </div>
    </div>

    {{-- Engagement Trends Chart --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-200 dark:border-slate-700 overflow-hidden mb-8">
        <div class="border-b border-gray-200 dark:border-slate-700 bg-gradient-to-r from-violet-50 to-purple-50 dark:from-slate-800 dark:to-slate-800 px-6 py-4">
            <h2 class="text-xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
                <svg class="w-6 h-6 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
                แนวโน้มการมีส่วนร่วม
            </h2>
        </div>
        <div class="p-6">
            <div class="chart-area">
                <canvas id="engagementChart"></canvas>
            </div>
        </div>
    </div>

    {{-- User Engagement Details Table --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-200 dark:border-slate-700 overflow-hidden">
        <div class="border-b border-gray-200 dark:border-slate-700 bg-gradient-to-r from-violet-50 to-purple-50 dark:from-slate-800 dark:to-slate-800 px-6 py-4">
            <h2 class="text-xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
                <svg class="w-6 h-6 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                รายละเอียดการมีส่วนร่วมของผู้ใช้
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                <thead class="bg-gray-50 dark:bg-slate-900">
                    <tr>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">วันที่</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">ผู้ใช้ที่ใช้งาน</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">ผู้ใช้ใหม่</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">การโต้ตอบ</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">ระยะเวลาเฉลี่ย</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">อัตราการมีส่วนร่วม</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse($engagementData ?? [] as $data)
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors duration-200">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">{{ $data->date ?? 'N/A' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $data->active_users ?? '0' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $data->new_users ?? '0' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $data->interactions ?? '0' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $data->avg_duration ?? '0' }} นาที</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $data->engagement_rate ?? '0' }}%</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            <div class="flex flex-col items-center justify-center">
                                <svg class="w-16 h-16 text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                </svg>
                                <p class="text-lg font-medium">ไม่มีข้อมูลการมีส่วนร่วม</p>
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
