@extends('layouts.admin')

@section('title', 'Bot Automation Dashboard')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                🤖 Bot Automation
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                ระบบอัตโนมัติสำหรับจัดการ Bot และการทำงานอัตโนมัติ
            </p>
        </div>
        <div class="mt-4 md:mt-0 flex gap-3">
            <a href="{{ route('admin.bot-automation.automations.create') }}"
               class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-medium rounded-xl shadow-lg hover:shadow-xl transition-all duration-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                สร้าง Automation
            </a>
            <a href="{{ route('admin.bot-automation.platforms.index') }}"
               class="inline-flex items-center gap-2 px-6 py-3 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 font-medium rounded-xl border border-gray-300 dark:border-gray-600 shadow hover:shadow-lg transition-all duration-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Platform Connections
            </a>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {{-- Total Automations --}}
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-2xl p-6 border border-blue-200 dark:border-blue-700">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-blue-600 dark:bg-blue-500 rounded-xl shadow-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                    </svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-gray-900 dark:text-white mb-1">
                {{ number_format($stats['total_automations']) }}
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
                Automations ทั้งหมด
            </div>
        </div>

        {{-- Active Automations --}}
        <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-2xl p-6 border border-green-200 dark:border-green-700">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-green-600 dark:bg-green-500 rounded-xl shadow-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-gray-900 dark:text-white mb-1">
                {{ number_format($stats['active_automations']) }}
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
                กำลังทำงาน
            </div>
        </div>

        {{-- Total Executions --}}
        <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 rounded-2xl p-6 border border-purple-200 dark:border-purple-700">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-purple-600 dark:bg-purple-500 rounded-xl shadow-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-gray-900 dark:text-white mb-1">
                {{ number_format($stats['total_executions']) }}
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
                การทำงานทั้งหมด
            </div>
        </div>

        {{-- Success Rate --}}
        <div class="bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-900/20 dark:to-orange-800/20 rounded-2xl p-6 border border-orange-200 dark:border-orange-700">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-orange-600 dark:bg-orange-500 rounded-xl shadow-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-gray-900 dark:text-white mb-1">
                {{ number_format($stats['success_rate'], 1) }}%
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
                อัตราความสำเร็จ
            </div>
        </div>
    </div>

    {{-- Main Content Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left Column - Recent Automations & Executions --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Recent Automations --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                        Automations ล่าสุด
                    </h2>
                    <a href="{{ route('admin.bot-automation.automations.index') }}"
                       class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                        ดูทั้งหมด →
                    </a>
                </div>
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($recentAutomations as $automation)
                        <div class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <a href="{{ route('admin.bot-automation.automations.show', $automation) }}"
                                       class="text-base font-medium text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400">
                                        {{ $automation->name }}
                                    </a>
                                    <div class="mt-1 flex items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
                                        <span class="inline-flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                            </svg>
                                            {{ ucfirst(str_replace('_', ' ', $automation->automation_type)) }}
                                        </span>
                                        <span>•</span>
                                        <span>{{ $automation->created_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                                <div>
                                    @if($automation->is_active)
                                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-xs font-medium rounded-full">
                                            <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-xs font-medium rounded-full">
                                            Inactive
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            <svg class="w-12 h-12 mx-auto mb-3 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                            </svg>
                            <p>ยังไม่มี Automation</p>
                            <a href="{{ route('admin.bot-automation.automations.create') }}" class="text-blue-600 dark:text-blue-400 hover:underline mt-2 inline-block">
                                สร้าง Automation แรก
                            </a>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Recent Executions --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                        การทำงานล่าสุด
                    </h2>
                </div>
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($recentExecutions as $execution)
                        <div class="px-6 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                            <div class="flex items-center justify-between">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                        {{ $execution->automation->name ?? 'Unknown' }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $execution->created_at->format('d M Y H:i') }}
                                    </p>
                                </div>
                                <div class="ml-4 flex-shrink-0">
                                    @if($execution->status === 'completed')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400">
                                            Success
                                        </span>
                                    @elseif($execution->status === 'failed')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400">
                                            Failed
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400">
                                            {{ ucfirst($execution->status) }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                            ยังไม่มีการทำงาน
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Right Column - Quick Links & Stats --}}
        <div class="space-y-6">
            {{-- Automation Types --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                    ประเภท Automation
                </h2>
                <div class="space-y-3">
                    @php
                        $typeIcons = [
                            'scheduled_post' => '📅',
                            'customer_support' => '💬',
                            'sales_assistant' => '💰',
                            'engagement' => '❤️',
                            'analytics' => '📊',
                        ];
                        $typeLabels = [
                            'scheduled_post' => 'โพสต์ตามกำหนด',
                            'customer_support' => 'ซัพพอร์ตลูกค้า',
                            'sales_assistant' => 'ผู้ช่วยขาย',
                            'engagement' => 'เพิ่ม Engagement',
                            'analytics' => 'วิเคราะห์ข้อมูล',
                        ];
                    @endphp
                    @forelse($automationsByType as $type => $count)
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <div class="flex items-center gap-2">
                                <span class="text-2xl">{{ $typeIcons[$type] ?? '🤖' }}</span>
                                <span class="text-sm text-gray-700 dark:text-gray-300">
                                    {{ $typeLabels[$type] ?? ucfirst(str_replace('_', ' ', $type)) }}
                                </span>
                            </div>
                            <span class="text-sm font-bold text-gray-900 dark:text-white">
                                {{ $count }}
                            </span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">
                            ยังไม่มีข้อมูล
                        </p>
                    @endforelse
                </div>
            </div>

            {{-- Quick Links --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                    เมนูด่วน
                </h2>
                <div class="space-y-2">
                    <a href="{{ route('admin.bot-automation.automations.index') }}"
                       class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition group">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400 group-hover:text-blue-600 dark:group-hover:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <span class="text-sm text-gray-700 dark:text-gray-300 group-hover:text-blue-600 dark:group-hover:text-blue-400">
                            จัดการ Automations
                        </span>
                    </a>
                    <a href="{{ route('admin.bot-automation.templates.index') }}"
                       class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition group">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400 group-hover:text-purple-600 dark:group-hover:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                        </svg>
                        <span class="text-sm text-gray-700 dark:text-gray-300 group-hover:text-purple-600 dark:group-hover:text-purple-400">
                            Templates
                        </span>
                    </a>
                    <a href="{{ route('admin.bot-automation.support.index') }}"
                       class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition group">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400 group-hover:text-green-600 dark:group-hover:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <span class="text-sm text-gray-700 dark:text-gray-300 group-hover:text-green-600 dark:group-hover:text-green-400">
                            Customer Support
                        </span>
                    </a>
                    <a href="{{ route('admin.bot-automation.analytics.index') }}"
                       class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition group">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400 group-hover:text-orange-600 dark:group-hover:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <span class="text-sm text-gray-700 dark:text-gray-300 group-hover:text-orange-600 dark:group-hover:text-orange-400">
                            Analytics
                        </span>
                    </a>
                </div>
            </div>

            {{-- Platform Status --}}
            <div class="bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-2xl p-6 border border-indigo-200 dark:border-indigo-800">
                <div class="flex items-center gap-3 mb-3">
                    <div class="p-2 bg-indigo-600 dark:bg-indigo-500 rounded-lg">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">
                        Platform Connections
                    </h3>
                </div>
                <div class="text-3xl font-bold text-indigo-600 dark:text-indigo-400 mb-1">
                    {{ $platformConnections }}
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                    Platform เชื่อมต่ออยู่
                </p>
                <a href="{{ route('admin.bot-automation.platforms.index') }}"
                   class="inline-flex items-center gap-2 text-sm text-indigo-600 dark:text-indigo-400 hover:underline font-medium">
                    จัดการ Platforms
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
