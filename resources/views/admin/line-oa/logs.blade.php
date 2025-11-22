@extends('layouts.admin-v3')

@section('title', 'ประวัติการใช้งาน LINE')

@section('content')
<div class="space-y-6" x-data="lineLogsManager()">
    <!-- Enhanced Header with Gradient & Particles -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-green-500 via-blue-600 to-purple-700 p-8 shadow-2xl">
        <!-- Floating Particles Background -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute w-64 h-64 bg-white/10 rounded-full blur-3xl -top-32 -left-32 animate-float"></div>
            <div class="absolute w-96 h-96 bg-white/10 rounded-full blur-3xl -bottom-48 -right-48 animate-float-delayed"></div>
            <div class="absolute w-32 h-32 bg-white/20 rounded-full blur-2xl top-1/2 left-1/2 animate-pulse"></div>
        </div>

        <!-- Header Content -->
        <div class="relative z-10">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center space-x-4">
                    <!-- LINE Icon -->
                    <div class="w-16 h-16 bg-white/20 backdrop-blur-xl rounded-2xl flex items-center justify-center shadow-xl transform hover:scale-110 transition-transform duration-300">
                        <svg class="w-10 h-10 text-white" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.771.039 1.086l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                        </svg>
                    </div>

                    <div>
                        <h1 class="text-3xl font-bold text-white drop-shadow-lg">ประวัติการใช้งาน LINE</h1>
                        <p class="mt-1 text-white/90 drop-shadow">ประวัติการ login, register และการเชื่อมต่อบัญชี LINE</p>
                    </div>
                </div>

                <div class="flex items-center space-x-3">
                    <!-- Auto Refresh Toggle -->
                    <div class="bg-white/20 backdrop-blur-xl rounded-xl px-4 py-2 shadow-lg">
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input type="checkbox" x-model="autoRefresh" @change="toggleAutoRefresh" class="w-4 h-4 rounded text-green-500 focus:ring-2 focus:ring-white">
                            <span class="text-sm text-white font-medium">
                                <span x-show="autoRefresh">🔄</span> Auto Refresh
                            </span>
                        </label>
                    </div>

                    <!-- Export Button -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="px-6 py-3 bg-white/20 backdrop-blur-xl text-white font-semibold rounded-xl hover:bg-white/30 transition-all duration-300 shadow-lg flex items-center space-x-2 transform hover:scale-105">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span>Export</span>
                        </button>

                        <!-- Export Dropdown -->
                        <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-2xl overflow-hidden z-20 border border-gray-200 dark:border-gray-700">
                            <button @click="exportData('csv'); open = false" class="w-full px-4 py-3 text-left hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors flex items-center space-x-2">
                                <svg class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span class="text-gray-700 dark:text-gray-300">Export as CSV</span>
                            </button>
                            <button @click="exportData('json'); open = false" class="w-full px-4 py-3 text-left hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors flex items-center space-x-2">
                                <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                                </svg>
                                <span class="text-gray-700 dark:text-gray-300">Export as JSON</span>
                            </button>
                        </div>
                    </div>

                    <!-- Back Button -->
                    <a href="{{ route('admin.line-oa.index') }}" class="px-6 py-3 bg-white/20 backdrop-blur-xl text-white font-semibold rounded-xl hover:bg-white/30 transition-all duration-300 shadow-lg transform hover:scale-105">
                        ← กลับ
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Advanced Filters -->
    <div class="glass-card rounded-2xl p-6 shadow-xl border border-white/20 dark:border-white/10 backdrop-blur-xl">
        <form method="GET" action="{{ route('admin.line-oa.logs') }}" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Search -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        🔍 ค้นหา
                    </label>
                    <div class="relative">
                        <input type="text"
                               name="search"
                               value="{{ request('search') }}"
                               placeholder="ชื่อ, อีเมล, LINE User ID"
                               class="w-full pl-10 pr-4 py-3 bg-white/50 dark:bg-gray-800/50 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                        <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>

                <!-- Action Type -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        ⚡ ประเภทการกระทำ
                    </label>
                    <select name="action"
                            class="w-full px-4 py-3 bg-white/50 dark:bg-gray-800/50 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                        <option value="">ทั้งหมด</option>
                        <option value="login" {{ request('action') === 'login' ? 'selected' : '' }}>🔐 Login</option>
                        <option value="register" {{ request('action') === 'register' ? 'selected' : '' }}>✨ Register</option>
                        <option value="register_initiated" {{ request('action') === 'register_initiated' ? 'selected' : '' }}>🚀 Register Started</option>
                        <option value="link" {{ request('action') === 'link' ? 'selected' : '' }}>🔗 Link Account</option>
                        <option value="unlink" {{ request('action') === 'unlink' ? 'selected' : '' }}>🔓 Unlink Account</option>
                    </select>
                </div>

                <!-- Date From -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        📅 วันที่เริ่มต้น
                    </label>
                    <input type="date"
                           name="date_from"
                           value="{{ request('date_from') }}"
                           class="w-full px-4 py-3 bg-white/50 dark:bg-gray-800/50 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                </div>

                <!-- Date To -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        📅 วันที่สิ้นสุด
                    </label>
                    <input type="date"
                           name="date_to"
                           value="{{ request('date_to') }}"
                           class="w-full px-4 py-3 bg-white/50 dark:bg-gray-800/50 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center space-x-3">
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all duration-300 shadow-lg transform hover:scale-105 flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        <span>กรอง</span>
                    </button>

                    <a href="{{ route('admin.line-oa.logs') }}" class="px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-semibold rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition-all duration-300 shadow-lg flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        <span>ล้างตัวกรอง</span>
                    </a>
                </div>

                <!-- Results Count -->
                <div class="text-sm text-gray-600 dark:text-gray-400 font-medium">
                    พบ <span class="text-blue-600 dark:text-blue-400 font-bold">{{ $logs->total() }}</span> รายการ
                </div>
            </div>
        </form>
    </div>

    <!-- Loading State -->
    <div x-show="loading" x-transition class="space-y-4">
        @for($i = 0; $i < 5; $i++)
        <div class="glass-card rounded-2xl p-6 shadow-xl border border-white/20 dark:border-white/10 animate-pulse">
            <div class="flex items-start space-x-4">
                <div class="w-12 h-12 bg-gray-300 dark:bg-gray-700 rounded-xl"></div>
                <div class="flex-1 space-y-3">
                    <div class="h-4 bg-gray-300 dark:bg-gray-700 rounded w-1/4"></div>
                    <div class="h-3 bg-gray-300 dark:bg-gray-700 rounded w-1/2"></div>
                    <div class="h-3 bg-gray-300 dark:bg-gray-700 rounded w-1/3"></div>
                </div>
            </div>
        </div>
        @endfor
    </div>

    <!-- Log Entry Cards -->
    <div x-show="!loading" class="space-y-4">
        @forelse($logs as $log)
        <div class="glass-card rounded-2xl shadow-xl border border-white/20 dark:border-white/10 backdrop-blur-xl overflow-hidden transform hover:scale-[1.02] transition-all duration-300 hover:shadow-2xl">
            <div class="p-6">
                <div class="flex items-start justify-between">
                    <!-- Left Side: Icon & Main Info -->
                    <div class="flex items-start space-x-4 flex-1">
                        <!-- Action Icon -->
                        <div class="flex-shrink-0">
                            @php
                                $iconConfig = [
                                    'login' => ['bg' => 'bg-gradient-to-br from-blue-500 to-blue-600', 'icon' => 'M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1'],
                                    'register' => ['bg' => 'bg-gradient-to-br from-green-500 to-green-600', 'icon' => 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z'],
                                    'register_initiated' => ['bg' => 'bg-gradient-to-br from-yellow-500 to-yellow-600', 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z'],
                                    'link' => ['bg' => 'bg-gradient-to-br from-purple-500 to-purple-600', 'icon' => 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1'],
                                    'unlink' => ['bg' => 'bg-gradient-to-br from-red-500 to-red-600', 'icon' => 'M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21'],
                                ];
                                $config = $iconConfig[$log->action] ?? ['bg' => 'bg-gradient-to-br from-gray-500 to-gray-600', 'icon' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'];
                            @endphp
                            <div class="{{ $config['bg'] }} w-14 h-14 rounded-xl flex items-center justify-center shadow-lg transform hover:rotate-12 transition-transform duration-300">
                                <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $config['icon'] }}" />
                                </svg>
                            </div>
                        </div>

                        <!-- Main Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center space-x-3 mb-2">
                                <!-- Action Badge -->
                                @php
                                    $badgeConfig = [
                                        'login' => ['bg' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300', 'label' => '🔐 Login'],
                                        'register' => ['bg' => 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300', 'label' => '✨ Register'],
                                        'register_initiated' => ['bg' => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300', 'label' => '🚀 Register Started'],
                                        'link' => ['bg' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300', 'label' => '🔗 Link Account'],
                                        'unlink' => ['bg' => 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300', 'label' => '🔓 Unlink Account'],
                                    ];
                                    $badge = $badgeConfig[$log->action] ?? ['bg' => 'bg-gray-100 dark:bg-gray-900/30 text-gray-800 dark:text-gray-300', 'label' => $log->action];
                                @endphp
                                <span class="px-3 py-1 {{ $badge['bg'] }} rounded-full text-xs font-bold shadow-sm">
                                    {{ $badge['label'] }}
                                </span>

                                <!-- Timestamp with Relative Time -->
                                <span class="text-sm text-gray-500 dark:text-gray-400 flex items-center space-x-1">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>{{ $log->created_at->diffForHumans() }}</span>
                                    <span class="text-xs text-gray-400">({{ $log->created_at->format('d/m/Y H:i:s') }})</span>
                                </span>
                            </div>

                            <!-- User Info -->
                            <div class="space-y-2">
                                @if($log->user)
                                <div class="flex items-center space-x-2">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    <div>
                                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $log->user->name }}</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">{{ $log->user->email }}</span>
                                    </div>
                                </div>
                                @endif

                                <!-- LINE User ID -->
                                <div class="flex items-center space-x-2">
                                    <svg class="w-5 h-5 text-gray-400" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.771.039 1.086l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                                    </svg>
                                    <code class="px-3 py-1 bg-gray-100 dark:bg-gray-800 rounded-lg text-xs font-mono text-gray-700 dark:text-gray-300">
                                        {{ $log->line_user_id }}
                                    </code>
                                </div>

                                <!-- IP Address -->
                                @if($log->ip_address)
                                <div class="flex items-center space-x-2">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                    </svg>
                                    <span class="text-xs text-gray-600 dark:text-gray-400">{{ $log->ip_address }}</span>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Right Side: Actions -->
                    <div class="flex flex-col items-end space-y-2">
                        @if($log->metadata)
                        <button @click="showMetadata(@js($log->metadata))"
                                class="px-4 py-2 bg-gradient-to-r from-blue-500 to-indigo-500 text-white rounded-xl hover:from-blue-600 hover:to-indigo-600 transition-all duration-300 shadow-lg text-sm font-medium transform hover:scale-105 flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>รายละเอียด</span>
                        </button>
                        @endif
                    </div>
                </div>

                <!-- Expandable Details (if metadata exists) -->
                @if($log->metadata)
                <div x-data="{ expanded: false }" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button @click="expanded = !expanded" class="text-sm text-blue-600 dark:text-blue-400 hover:underline flex items-center space-x-1">
                        <svg class="w-4 h-4 transform transition-transform" :class="{ 'rotate-90': expanded }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                        <span x-text="expanded ? 'ซ่อนรายละเอียด' : 'แสดงรายละเอียดเพิ่มเติม'"></span>
                    </button>

                    <div x-show="expanded" x-collapse class="mt-3">
                        <pre class="bg-gray-100 dark:bg-gray-800 p-4 rounded-xl text-xs overflow-auto max-h-64 text-gray-700 dark:text-gray-300">{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                </div>
                @endif
            </div>
        </div>
        @empty
        <!-- Beautiful Empty State -->
        <div class="glass-card rounded-2xl shadow-xl border border-white/20 dark:border-white/10 backdrop-blur-xl p-16 text-center">
            <div class="max-w-md mx-auto">
                <!-- Empty State Illustration -->
                <div class="mb-6">
                    <div class="w-32 h-32 mx-auto bg-gradient-to-br from-blue-100 to-purple-100 dark:from-blue-900/30 dark:to-purple-900/30 rounded-full flex items-center justify-center">
                        <svg class="w-16 h-16 text-blue-400 dark:text-blue-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                </div>

                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">ไม่พบประวัติการใช้งาน</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    ยังไม่มีประวัติการใช้งาน LINE หรือลองปรับเปลี่ยนตัวกรองเพื่อดูข้อมูลอื่น
                </p>

                <a href="{{ route('admin.line-oa.logs') }}" class="inline-flex items-center space-x-2 px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all duration-300 shadow-lg transform hover:scale-105">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span>รีเซ็ตตัวกรอง</span>
                </a>
            </div>
        </div>
        @endforelse
    </div>

    <!-- Modern Pagination -->
    @if($logs->hasPages())
    <div class="glass-card rounded-2xl shadow-xl border border-white/20 dark:border-white/10 backdrop-blur-xl p-6">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="text-sm text-gray-600 dark:text-gray-400">
                แสดง <span class="font-bold text-gray-900 dark:text-white">{{ $logs->firstItem() }}</span>
                ถึง <span class="font-bold text-gray-900 dark:text-white">{{ $logs->lastItem() }}</span>
                จาก <span class="font-bold text-gray-900 dark:text-white">{{ $logs->total() }}</span> รายการ
            </div>

            <div class="flex items-center space-x-2">
                {{-- Previous --}}
                @if ($logs->onFirstPage())
                    <span class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-400 dark:text-gray-500 rounded-xl cursor-not-allowed">
                        ← ก่อนหน้า
                    </span>
                @else
                    <a href="{{ $logs->previousPageUrl() }}" class="px-4 py-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-blue-50 dark:hover:bg-gray-700 transition-colors shadow-sm">
                        ← ก่อนหน้า
                    </a>
                @endif

                {{-- Page Numbers --}}
                <div class="hidden md:flex items-center space-x-1">
                    @foreach(range(1, $logs->lastPage()) as $page)
                        @if($page == $logs->currentPage())
                            <span class="px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl font-bold shadow-lg">
                                {{ $page }}
                            </span>
                        @elseif($page == 1 || $page == $logs->lastPage() || abs($page - $logs->currentPage()) <= 2)
                            <a href="{{ $logs->url($page) }}" class="px-4 py-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-blue-50 dark:hover:bg-gray-700 transition-colors shadow-sm">
                                {{ $page }}
                            </a>
                        @elseif(abs($page - $logs->currentPage()) == 3)
                            <span class="px-2 text-gray-400">...</span>
                        @endif
                    @endforeach
                </div>

                {{-- Next --}}
                @if ($logs->hasMorePages())
                    <a href="{{ $logs->nextPageUrl() }}" class="px-4 py-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-blue-50 dark:hover:bg-gray-700 transition-colors shadow-sm">
                        ถัดไป →
                    </a>
                @else
                    <span class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-400 dark:text-gray-500 rounded-xl cursor-not-allowed">
                        ถัดไป →
                    </span>
                @endif
            </div>
        </div>
    </div>
    @endif

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        @php
            $stats = [
                [
                    'title' => 'Total Logins',
                    'value' => \App\Models\LineLoginLog::where('action', 'login')->count(),
                    'icon' => 'M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1',
                    'gradient' => 'from-blue-500 to-blue-600',
                    'bg' => 'from-blue-500/20 to-blue-600/20'
                ],
                [
                    'title' => 'Total Registers',
                    'value' => \App\Models\LineLoginLog::where('action', 'register')->count(),
                    'icon' => 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z',
                    'gradient' => 'from-green-500 to-green-600',
                    'bg' => 'from-green-500/20 to-green-600/20'
                ],
                [
                    'title' => 'Linked Accounts',
                    'value' => \App\Models\User::whereNotNull('line_user_id')->count(),
                    'icon' => 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1',
                    'gradient' => 'from-purple-500 to-purple-600',
                    'bg' => 'from-purple-500/20 to-purple-600/20'
                ],
                [
                    'title' => 'Today',
                    'value' => \App\Models\LineLoginLog::whereDate('created_at', today())->count(),
                    'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
                    'gradient' => 'from-yellow-500 to-orange-500',
                    'bg' => 'from-yellow-500/20 to-orange-500/20'
                ]
            ];
        @endphp

        @foreach($stats as $stat)
        <div class="glass-card rounded-2xl shadow-xl border border-white/20 dark:border-white/10 backdrop-blur-xl p-6 transform hover:scale-105 transition-all duration-300 hover:shadow-2xl">
            <div class="flex items-center space-x-4">
                <div class="flex-shrink-0">
                    <div class="w-16 h-16 bg-gradient-to-br {{ $stat['gradient'] }} rounded-2xl flex items-center justify-center shadow-lg transform hover:rotate-12 transition-transform duration-300">
                        <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $stat['icon'] }}" />
                        </svg>
                    </div>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">{{ $stat['title'] }}</p>
                    <p class="text-3xl font-bold bg-gradient-to-r {{ $stat['gradient'] }} bg-clip-text text-transparent">
                        {{ number_format($stat['value']) }}
                    </p>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Enhanced Metadata Modal -->
    <div x-show="metadataModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div @click="metadataModal = false" class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-75 backdrop-blur-sm"></div>

            <!-- Modal panel -->
            <div class="inline-block align-bottom glass-card rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-white/20 dark:border-white/10"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-bold text-white flex items-center space-x-2">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>รายละเอียด Metadata</span>
                        </h3>
                        <button @click="metadataModal = false" class="text-white hover:text-gray-200 transition-colors">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Content -->
                <div class="px-6 py-4">
                    <pre x-text="metadataContent" class="bg-gray-100 dark:bg-gray-800 p-4 rounded-xl text-sm overflow-auto max-h-96 text-gray-700 dark:text-gray-300 font-mono"></pre>
                </div>

                <!-- Footer -->
                <div class="bg-gray-50 dark:bg-gray-800/50 px-6 py-4 flex justify-end space-x-3">
                    <button @click="copyMetadata()" class="px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors shadow-lg flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        <span>คัดลอก</span>
                    </button>
                    <button @click="metadataModal = false" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                        ปิด
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* Floating Animation */
    @keyframes float {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-20px) rotate(5deg); }
    }

    @keyframes float-delayed {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-30px) rotate(-5deg); }
    }

    .animate-float {
        animation: float 6s ease-in-out infinite;
    }

    .animate-float-delayed {
        animation: float-delayed 8s ease-in-out infinite;
    }

    /* Glass Card Effect */
    .glass-card {
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    }

    .dark .glass-card {
        background: rgba(17, 24, 39, 0.7);
    }

    /* Smooth transitions */
    * {
        transition-property: background-color, border-color, color, fill, stroke, opacity, box-shadow, transform;
        transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
        transition-duration: 150ms;
    }
</style>
@endpush

@push('scripts')
<script>
/**
 * Alpine.js Component สำหรับจัดการ LINE Logs
 */
function lineLogsManager() {
    return {
        loading: false,
        autoRefresh: false,
        refreshInterval: null,
        metadataModal: false,
        metadataContent: '',

        init() {
            // เช็ค localStorage สำหรับ auto refresh setting
            this.autoRefresh = localStorage.getItem('line_logs_auto_refresh') === 'true';
            if (this.autoRefresh) {
                this.startAutoRefresh();
            }
        },

        /**
         * Toggle auto refresh
         */
        toggleAutoRefresh() {
            localStorage.setItem('line_logs_auto_refresh', this.autoRefresh);

            if (this.autoRefresh) {
                this.startAutoRefresh();
                this.showNotification('เปิดการรีเฟรชอัตโนมัติทุก 30 วินาที', 'success');
            } else {
                this.stopAutoRefresh();
                this.showNotification('ปิดการรีเฟรชอัตโนมัติ', 'info');
            }
        },

        /**
         * เริ่ม auto refresh
         */
        startAutoRefresh() {
            this.refreshInterval = setInterval(() => {
                this.refreshLogs();
            }, 30000); // รีเฟรชทุก 30 วินาที
        },

        /**
         * หยุด auto refresh
         */
        stopAutoRefresh() {
            if (this.refreshInterval) {
                clearInterval(this.refreshInterval);
                this.refreshInterval = null;
            }
        },

        /**
         * รีเฟรชหน้า logs
         */
        refreshLogs() {
            this.loading = true;
            window.location.reload();
        },

        /**
         * แสดง metadata modal
         */
        showMetadata(data) {
            if (data && typeof data === 'object') {
                this.metadataContent = JSON.stringify(data, null, 2);
            } else {
                this.metadataContent = 'ไม่มีข้อมูล';
            }
            this.metadataModal = true;
        },

        /**
         * คัดลอก metadata
         */
        copyMetadata() {
            navigator.clipboard.writeText(this.metadataContent).then(() => {
                this.showNotification('คัดลอกข้อมูลเรียบร้อย', 'success');
            }).catch(() => {
                this.showNotification('ไม่สามารถคัดลอกข้อมูลได้', 'error');
            });
        },

        /**
         * Export ข้อมูล
         */
        exportData(format) {
            this.loading = true;

            // สร้าง URL พร้อม query parameters
            const url = new URL(window.location.href);
            url.searchParams.set('export', format);

            // Redirect ไปยัง export URL
            window.location.href = url.toString();

            setTimeout(() => {
                this.loading = false;
                this.showNotification(`กำลัง export ข้อมูลเป็น ${format.toUpperCase()}...`, 'info');
            }, 1000);
        },

        /**
         * แสดง notification
         */
        showNotification(message, type = 'info') {
            // สร้าง notification element
            const notification = document.createElement('div');
            notification.className = `fixed bottom-4 right-4 z-50 px-6 py-4 rounded-xl shadow-2xl transform transition-all duration-300 ${
                type === 'success' ? 'bg-green-500' :
                type === 'error' ? 'bg-red-500' :
                type === 'warning' ? 'bg-yellow-500' :
                'bg-blue-500'
            } text-white font-medium`;
            notification.textContent = message;

            document.body.appendChild(notification);

            // แสดง notification
            setTimeout(() => {
                notification.style.opacity = '1';
                notification.style.transform = 'translateY(0)';
            }, 100);

            // ซ่อน notification หลัง 3 วินาที
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }
    }
}

// ปิด modal เมื่อกด ESC
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const alpineData = document.querySelector('[x-data]').__x.$data;
        if (alpineData.metadataModal) {
            alpineData.metadataModal = false;
        }
    }
});
</script>
@endpush
@endsection
