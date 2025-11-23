@extends('layouts.admin-v3')

@section('title', 'LINE Membership Signup Dashboard')

@section('content')
{{--
    LINE Membership Signup Dashboard - V3 Theme
    ใช้ Tailwind CSS + Alpine.js + Modern UI Design
    Dark Mode Support + Responsive + Glassmorphism
--}}
<div class="min-h-screen" x-data="lineSignupDashboard()" x-init="init()">
    {{-- Page Header with Gradient Background --}}
    <div class="relative mb-8 rounded-2xl bg-gradient-to-br from-blue-500 via-purple-500 to-pink-500 p-8 shadow-2xl overflow-hidden">
        {{-- Animated Background Pattern --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 40px 40px;"></div>
        </div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="text-white">
                <div class="flex items-center gap-3 mb-2">
                    <div class="p-3 bg-white/20 backdrop-blur-sm rounded-xl">
                        <i class="fas fa-user-plus text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl md:text-4xl font-bold tracking-tight">
                            LINE Membership Signup
                        </h1>
                        <p class="text-white/90 text-sm md:text-base mt-1">
                            AI-Powered Signup System Dashboard
                        </p>
                    </div>
                </div>
            </div>

            {{-- Date Range Filter --}}
            <div class="flex items-center gap-3">
                <select
                    x-model="dateRange"
                    @change="handleDateRangeChange()"
                    class="px-4 py-3 bg-white/20 backdrop-blur-md border border-white/30 rounded-xl text-white font-medium focus:outline-none focus:ring-2 focus:ring-white/50 transition-all duration-300 hover:bg-white/30 cursor-pointer"
                >
                    <option value="7" {{ $dateRange == 7 ? 'selected' : '' }}>Last 7 Days</option>
                    <option value="30" {{ $dateRange == 30 ? 'selected' : '' }}>Last 30 Days</option>
                    <option value="90" {{ $dateRange == 90 ? 'selected' : '' }}>Last 90 Days</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Stats Cards with Glassmorphism --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
        {{-- Total Sessions Card --}}
        <div class="group relative bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-500 overflow-hidden transform hover:-translate-y-1">
            {{-- Gradient Border Effect --}}
            <div class="absolute inset-0 bg-gradient-to-br from-blue-400 to-blue-600 opacity-0 group-hover:opacity-100 transition-opacity duration-500 -z-10"></div>
            <div class="absolute inset-[2px] bg-white dark:bg-gray-800 rounded-2xl"></div>

            <div class="relative p-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider mb-2">
                            Total Sessions
                        </p>
                        <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-1">
                            {{ number_format($stats['total_sessions']) }}
                        </h3>
                        <div class="flex items-center gap-1 text-xs text-green-600 dark:text-green-400">
                            <i class="fas fa-arrow-up"></i>
                            <span>+12.5%</span>
                        </div>
                    </div>
                    <div class="flex-shrink-0">
                        <div class="w-16 h-16 flex items-center justify-center rounded-2xl bg-gradient-to-br from-blue-400 to-blue-600 shadow-lg transform group-hover:rotate-12 transition-transform duration-500">
                            <i class="fas fa-users text-2xl text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Completed Card --}}
        <div class="group relative bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-500 overflow-hidden transform hover:-translate-y-1">
            <div class="absolute inset-0 bg-gradient-to-br from-green-400 to-green-600 opacity-0 group-hover:opacity-100 transition-opacity duration-500 -z-10"></div>
            <div class="absolute inset-[2px] bg-white dark:bg-gray-800 rounded-2xl"></div>

            <div class="relative p-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-xs font-bold text-green-600 dark:text-green-400 uppercase tracking-wider mb-2">
                            Completed
                        </p>
                        <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-1">
                            {{ number_format($stats['completed']) }}
                        </h3>
                        <div class="flex items-center gap-1 text-xs text-green-600 dark:text-green-400">
                            <i class="fas fa-arrow-up"></i>
                            <span>+8.2%</span>
                        </div>
                    </div>
                    <div class="flex-shrink-0">
                        <div class="w-16 h-16 flex items-center justify-center rounded-2xl bg-gradient-to-br from-green-400 to-green-600 shadow-lg transform group-hover:rotate-12 transition-transform duration-500">
                            <i class="fas fa-check-circle text-2xl text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Active Now Card --}}
        <div class="group relative bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-500 overflow-hidden transform hover:-translate-y-1">
            <div class="absolute inset-0 bg-gradient-to-br from-cyan-400 to-cyan-600 opacity-0 group-hover:opacity-100 transition-opacity duration-500 -z-10"></div>
            <div class="absolute inset-[2px] bg-white dark:bg-gray-800 rounded-2xl"></div>

            <div class="relative p-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-xs font-bold text-cyan-600 dark:text-cyan-400 uppercase tracking-wider mb-2">
                            Active Now
                        </p>
                        <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-1">
                            {{ number_format($stats['active']) }}
                        </h3>
                        <div class="flex items-center gap-1">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-cyan-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-cyan-500"></span>
                            </span>
                            <span class="text-xs text-gray-600 dark:text-gray-400">Live</span>
                        </div>
                    </div>
                    <div class="flex-shrink-0">
                        <div class="w-16 h-16 flex items-center justify-center rounded-2xl bg-gradient-to-br from-cyan-400 to-cyan-600 shadow-lg transform group-hover:rotate-12 transition-transform duration-500">
                            <i class="fas fa-clock text-2xl text-white"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Completion Rate Card --}}
        <div class="group relative bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-500 overflow-hidden transform hover:-translate-y-1">
            <div class="absolute inset-0 bg-gradient-to-br from-amber-400 to-amber-600 opacity-0 group-hover:opacity-100 transition-opacity duration-500 -z-10"></div>
            <div class="absolute inset-[2px] bg-white dark:bg-gray-800 rounded-2xl"></div>

            <div class="relative p-6">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex-1">
                        <p class="text-xs font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider mb-2">
                            Completion Rate
                        </p>
                        <h3 class="text-3xl font-bold text-gray-900 dark:text-white">
                            {{ number_format($stats['completion_rate'], 1) }}%
                        </h3>
                    </div>
                    <div class="flex-shrink-0">
                        <div class="w-16 h-16 flex items-center justify-center rounded-2xl bg-gradient-to-br from-amber-400 to-amber-600 shadow-lg transform group-hover:rotate-12 transition-transform duration-500">
                            <i class="fas fa-percentage text-2xl text-white"></i>
                        </div>
                    </div>
                </div>
                {{-- Progress Bar --}}
                <div class="relative w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                    <div
                        class="absolute top-0 left-0 h-full bg-gradient-to-r from-amber-400 to-amber-600 rounded-full transition-all duration-1000 ease-out"
                        style="width: {{ $stats['completion_rate'] }}%"
                        x-init="$el.style.width = '0%'; setTimeout(() => $el.style.width = '{{ $stats['completion_rate'] }}%', 100)"
                    ></div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-8">
        {{-- Daily Signups Chart --}}
        <div class="xl:col-span-2 bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 transition-all duration-300 hover:shadow-2xl">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-gradient-to-br from-blue-400 to-purple-500 rounded-lg">
                        <i class="fas fa-chart-area text-white"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                        Daily Signups Trend
                    </h2>
                </div>
            </div>
            <div class="relative h-80">
                <canvas id="dailySignupsChart"></canvas>
            </div>
        </div>

        {{-- Step Funnel --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 transition-all duration-300 hover:shadow-2xl">
            <div class="flex items-center gap-3 mb-6">
                <div class="p-2 bg-gradient-to-br from-purple-400 to-pink-500 rounded-lg">
                    <i class="fas fa-funnel-dollar text-white"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                    Signup Funnel
                </h2>
            </div>

            <div class="space-y-4">
                @foreach($stepFunnel as $step)
                <div class="group">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">
                            {{ $step->step_name }}
                        </span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $step->visitors }} visitors
                        </span>
                    </div>
                    @php
                        $percentage = $step->visitors > 0 ? ($step->completions / $step->visitors) * 100 : 0;
                        $colorClass = $percentage >= 75 ? 'from-green-400 to-green-600' :
                                     ($percentage >= 50 ? 'from-yellow-400 to-yellow-600' : 'from-red-400 to-red-600');
                    @endphp
                    <div class="relative h-8 bg-gray-200 dark:bg-gray-700 rounded-lg overflow-hidden">
                        <div
                            class="absolute top-0 left-0 h-full bg-gradient-to-r {{ $colorClass }} flex items-center justify-center text-white text-sm font-bold transition-all duration-1000 ease-out group-hover:shadow-lg"
                            style="width: {{ $percentage }}%"
                            x-init="$el.style.width = '0%'; setTimeout(() => $el.style.width = '{{ $percentage }}%', 200)"
                        >
                            <span class="drop-shadow">{{ number_format($percentage, 0) }}%</span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-8">
        {{-- Recent Sessions Table --}}
        <div class="xl:col-span-2 bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden transition-all duration-300 hover:shadow-2xl">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-gradient-to-br from-blue-400 to-cyan-500 rounded-lg">
                            <i class="fas fa-list text-white"></i>
                        </div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                            Recent Signup Sessions
                        </h2>
                    </div>
                    <a href="{{ route('admin.line-membership-signup.sessions') }}"
                       class="px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-lg font-medium transition-all duration-300 hover:shadow-lg transform hover:-translate-y-0.5">
                        View All
                    </a>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">LINE User</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Step</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Progress</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Started</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($recentSessions as $session)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/30 transition-colors duration-200">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                #{{ $session->id }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-600 dark:text-gray-400 font-mono">
                                    {{ Str::limit($session->line_user_id, 10) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-cyan-100 dark:bg-cyan-900/30 text-cyan-800 dark:text-cyan-300">
                                    {{ $session->current_step }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($session->status === 'completed')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">
                                        <i class="fas fa-check-circle"></i>
                                        Completed
                                    </span>
                                @elseif($session->status === 'active')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                                        <span class="relative flex h-2 w-2">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
                                        </span>
                                        Active
                                    </span>
                                @elseif($session->status === 'abandoned')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        Abandoned
                                    </span>
                                @else
                                    <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300">
                                        {{ $session->status }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="w-24 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div
                                        class="h-full bg-gradient-to-r from-blue-400 to-blue-600 rounded-full transition-all duration-500"
                                        style="width: {{ $session->getProgressPercentage() }}%"
                                    ></div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                {{ $session->started_at->diffForHumans() }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('admin.line-membership-signup.sessions.show', $session) }}"
                                   class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/50 transition-colors duration-200">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                                        <i class="fas fa-inbox text-2xl text-gray-400"></i>
                                    </div>
                                    <p class="text-gray-500 dark:text-gray-400 font-medium">No signup sessions yet</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Top Referrers --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 transition-all duration-300 hover:shadow-2xl">
            <div class="flex items-center gap-3 mb-6">
                <div class="p-2 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-lg">
                    <i class="fas fa-trophy text-white"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                    Top Referrers
                </h2>
            </div>

            <div class="space-y-4">
                @forelse($topPerformers as $index => $performer)
                <div class="flex items-center justify-between p-4 rounded-xl bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-900/50 dark:to-gray-800/50 hover:shadow-md transition-all duration-300 transform hover:-translate-y-1">
                    <div class="flex items-center gap-4">
                        <div class="flex-shrink-0 w-12 h-12 flex items-center justify-center rounded-xl
                            {{ $index === 0 ? 'bg-gradient-to-br from-yellow-400 to-yellow-600' :
                               ($index === 1 ? 'bg-gradient-to-br from-gray-300 to-gray-500' :
                               ($index === 2 ? 'bg-gradient-to-br from-orange-400 to-orange-600' : 'bg-gray-200 dark:bg-gray-700')) }}
                        ">
                            @if($index === 0)
                                <i class="fas fa-trophy text-xl text-white"></i>
                            @elseif($index === 1)
                                <i class="fas fa-medal text-xl text-white"></i>
                            @elseif($index === 2)
                                <i class="fas fa-medal text-xl text-white"></i>
                            @else
                                <span class="text-sm font-bold text-gray-600 dark:text-gray-400">{{ $index + 1 }}</span>
                            @endif
                        </div>
                        <div>
                            <div class="font-bold text-gray-900 dark:text-white">
                                {{ $performer->name }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                                {{ $performer->referral_code }}
                            </div>
                        </div>
                    </div>
                    <div>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-bold rounded-full bg-gradient-to-r from-green-400 to-green-600 text-white shadow-lg">
                            <i class="fas fa-users text-xs"></i>
                            {{ $performer->signup_count }}
                        </span>
                    </div>
                </div>
                @empty
                <div class="flex flex-col items-center justify-center py-12">
                    <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-3">
                        <i class="fas fa-chart-bar text-2xl text-gray-400"></i>
                    </div>
                    <p class="text-gray-500 dark:text-gray-400 font-medium">No data yet</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 transition-all duration-300 hover:shadow-2xl">
        <div class="flex items-center gap-3 mb-6">
            <div class="p-2 bg-gradient-to-br from-indigo-400 to-purple-500 rounded-lg">
                <i class="fas fa-tools text-white"></i>
            </div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                Quick Actions
            </h2>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <a href="{{ route('admin.line-membership-signup.templates') }}"
               class="group relative overflow-hidden p-6 rounded-xl bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 border-2 border-blue-200 dark:border-blue-700 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                <div class="flex flex-col items-center gap-3 text-center">
                    <div class="w-14 h-14 flex items-center justify-center rounded-xl bg-gradient-to-br from-blue-400 to-blue-600 text-white shadow-lg transform group-hover:rotate-12 transition-transform duration-500">
                        <i class="fas fa-file-alt text-xl"></i>
                    </div>
                    <span class="font-bold text-gray-900 dark:text-white">Manage Templates</span>
                </div>
            </a>

            <a href="{{ route('admin.line-membership-signup.invitations') }}"
               class="group relative overflow-hidden p-6 rounded-xl bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 border-2 border-green-200 dark:border-green-700 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                <div class="flex flex-col items-center gap-3 text-center">
                    <div class="w-14 h-14 flex items-center justify-center rounded-xl bg-gradient-to-br from-green-400 to-green-600 text-white shadow-lg transform group-hover:rotate-12 transition-transform duration-500">
                        <i class="fas fa-link text-xl"></i>
                    </div>
                    <span class="font-bold text-gray-900 dark:text-white">View Invitations</span>
                </div>
            </a>

            <a href="{{ route('admin.line-membership-signup.rewards.index') }}"
               class="group relative overflow-hidden p-6 rounded-xl bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-900/20 dark:to-amber-800/20 border-2 border-amber-200 dark:border-amber-700 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                <div class="flex flex-col items-center gap-3 text-center">
                    <div class="w-14 h-14 flex items-center justify-center rounded-xl bg-gradient-to-br from-amber-400 to-amber-600 text-white shadow-lg transform group-hover:rotate-12 transition-transform duration-500">
                        <i class="fas fa-gift text-xl"></i>
                    </div>
                    <span class="font-bold text-gray-900 dark:text-white">Manage Rewards</span>
                </div>
            </a>

            <a href="{{ route('admin.line-membership-signup.export.sessions') }}"
               class="group relative overflow-hidden p-6 rounded-xl bg-gradient-to-br from-cyan-50 to-cyan-100 dark:from-cyan-900/20 dark:to-cyan-800/20 border-2 border-cyan-200 dark:border-cyan-700 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                <div class="flex flex-col items-center gap-3 text-center">
                    <div class="w-14 h-14 flex items-center justify-center rounded-xl bg-gradient-to-br from-cyan-400 to-cyan-600 text-white shadow-lg transform group-hover:rotate-12 transition-transform duration-500">
                        <i class="fas fa-download text-xl"></i>
                    </div>
                    <span class="font-bold text-gray-900 dark:text-white">Export Data</span>
                </div>
            </a>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
/**
 * LINE Signup Dashboard - Alpine.js Component
 *
 * จัดการ state และ interactions สำหรับ dashboard
 */
function lineSignupDashboard() {
    return {
        dateRange: {{ $dateRange }},
        chart: null,

        /**
         * เริ่มต้น component
         */
        init() {
            this.initChart();
        },

        /**
         * จัดการการเปลี่ยน date range
         */
        handleDateRangeChange() {
            window.location.href = `{{ route("admin.line-membership-signup.index") }}?range=${this.dateRange}`;
        },

        /**
         * สร้าง Daily Signups Chart
         */
        initChart() {
            const dailySignupsData = @json($dailySignups);
            const ctx = document.getElementById('dailySignupsChart');

            if (!ctx) return;

            // ตรวจสอบ dark mode
            const isDarkMode = document.documentElement.classList.contains('dark');
            const textColor = isDarkMode ? '#e5e7eb' : '#374151';
            const gridColor = isDarkMode ? '#374151' : '#e5e7eb';

            this.chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: dailySignupsData.map(d => d.date),
                    datasets: [
                        {
                            label: 'Total Signups',
                            data: dailySignupsData.map(d => d.total),
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            borderWidth: 3,
                            tension: 0.4,
                            fill: true,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            pointBackgroundColor: 'rgb(59, 130, 246)',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                        },
                        {
                            label: 'Completed',
                            data: dailySignupsData.map(d => d.completed),
                            borderColor: 'rgb(16, 185, 129)',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            borderWidth: 3,
                            tension: 0.4,
                            fill: true,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            pointBackgroundColor: 'rgb(16, 185, 129)',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                color: textColor,
                                font: {
                                    size: 12,
                                    weight: 'bold'
                                },
                                padding: 15,
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        },
                        tooltip: {
                            backgroundColor: isDarkMode ? '#1f2937' : '#ffffff',
                            titleColor: isDarkMode ? '#ffffff' : '#111827',
                            bodyColor: isDarkMode ? '#e5e7eb' : '#374151',
                            borderColor: isDarkMode ? '#374151' : '#e5e7eb',
                            borderWidth: 1,
                            padding: 12,
                            displayColors: true,
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.parsed.y;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: textColor,
                                stepSize: 1,
                                font: {
                                    size: 11
                                }
                            },
                            grid: {
                                color: gridColor,
                                drawBorder: false
                            }
                        },
                        x: {
                            ticks: {
                                color: textColor,
                                font: {
                                    size: 11
                                }
                            },
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }
    };
}
</script>
@endpush
@endsection
