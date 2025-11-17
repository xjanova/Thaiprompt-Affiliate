@extends('layouts.admin-v3')

@section('title', 'รายละเอียดการส่งอีเมล - Email Log')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-cyan-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 py-8 transition-colors duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header Section -->
        <div class="mb-8 animate-fade-in">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white font-bold shadow-lg text-xl
                            @if($log->status == 'sent') bg-gradient-to-br from-green-500 to-green-600
                            @elseif($log->status == 'failed') bg-gradient-to-br from-red-500 to-red-600
                            @elseif($log->status == 'pending') bg-gradient-to-br from-yellow-500 to-yellow-600
                            @else bg-gradient-to-br from-slate-500 to-slate-600
                            @endif">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 dark:from-blue-400 dark:to-cyan-400 bg-clip-text text-transparent">
                                รายละเอียดการส่งอีเมล
                            </h1>
                            <p class="mt-1 text-slate-600 dark:text-slate-400">
                                Log ID: <span class="font-mono text-sm">#{{ $log->id }}</span>
                            </p>
                        </div>
                    </div>
                </div>
                <a href="{{ route('admin.email.logs') }}"
                   class="inline-flex items-center px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg shadow-sm hover:shadow-md transition-all duration-200 text-slate-700 dark:text-slate-200 hover:scale-105">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    กลับไป Logs
                </a>
            </div>
        </div>

        <!-- Status Banner -->
        <div class="mb-6 rounded-xl shadow-lg p-6 border transition-all duration-300 animate-slide-up
            @if($log->status == 'sent') bg-gradient-to-r from-green-500 to-green-600 border-green-600
            @elseif($log->status == 'failed') bg-gradient-to-r from-red-500 to-red-600 border-red-600
            @elseif($log->status == 'pending') bg-gradient-to-r from-yellow-500 to-yellow-600 border-yellow-600
            @else bg-gradient-to-r from-slate-500 to-slate-600 border-slate-600
            @endif text-white">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center">
                        @if($log->status == 'sent')
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        @elseif($log->status == 'failed')
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        @elseif($log->status == 'pending')
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        @endif
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold">
                            @if($log->status == 'sent') ส่งสำเร็จ
                            @elseif($log->status == 'failed') ส่งล้มเหลว
                            @elseif($log->status == 'pending') รอดำเนินการ
                            @else {{ ucfirst($log->status) }}
                            @endif
                        </h2>
                        <p class="text-white/90 text-sm mt-1">
                            {{ $log->created_at ? $log->created_at->format('d/m/Y H:i:s') : '-' }}
                        </p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-white/90 text-sm mb-1">Provider</p>
                    <p class="text-xl font-bold">{{ strtoupper($log->provider ?? 'N/A') }}</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Email Information -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6 border border-slate-200 dark:border-slate-700 animate-slide-up transition-all duration-300" style="animation-delay: 0.1s">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100 mb-4 flex items-center">
                        <svg class="w-6 h-6 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        ข้อมูลอีเมล
                    </h2>

                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs text-slate-500 dark:text-slate-400 font-medium flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                                    </svg>
                                    ผู้รับ
                                </label>
                                <p class="text-sm font-semibold text-slate-900 dark:text-slate-100 mt-2 break-all">
                                    {{ $log->to_email }}
                                </p>
                            </div>
                            <div>
                                <label class="text-xs text-slate-500 dark:text-slate-400 font-medium flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    ชื่อผู้รับ
                                </label>
                                <p class="text-sm font-semibold text-slate-900 dark:text-slate-100 mt-2">
                                    {{ $log->to_name ?? 'N/A' }}
                                </p>
                            </div>
                        </div>

                        <div>
                            <label class="text-xs text-slate-500 dark:text-slate-400 font-medium flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                                </svg>
                                หัวเรื่อง
                            </label>
                            <div class="mt-2 p-4 bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                                <p class="text-base font-semibold text-slate-900 dark:text-slate-100">
                                    {{ $log->subject }}
                                </p>
                            </div>
                        </div>

                        @if($log->message_id)
                        <div>
                            <label class="text-xs text-slate-500 dark:text-slate-400 font-medium flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                                </svg>
                                Message ID
                            </label>
                            <p class="text-xs font-mono text-slate-600 dark:text-slate-400 mt-2 break-all bg-slate-100 dark:bg-slate-700 p-2 rounded">
                                {{ $log->message_id }}
                            </p>
                        </div>
                        @endif

                        @if($log->user)
                        <div>
                            <label class="text-xs text-slate-500 dark:text-slate-400 font-medium flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                เกี่ยวข้องกับสมาชิก
                            </label>
                            <div class="mt-2 flex items-center space-x-3 p-3 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-lg flex items-center justify-center text-white font-bold">
                                    {{ substr($log->user->name, 0, 1) }}
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $log->user->name }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $log->user->email }}</p>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Email Content Preview -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700 overflow-hidden animate-slide-up transition-all duration-300" style="animation-delay: 0.2s">
                    <div class="bg-gradient-to-r from-slate-50 to-slate-100 dark:from-slate-700 dark:to-slate-800 p-4 border-b border-slate-200 dark:border-slate-700">
                        <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100 flex items-center">
                            <svg class="w-6 h-6 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            ตัวอย่างเนื้อหาอีเมล
                        </h2>
                    </div>

                    <div class="p-6">
                        <!-- Tab Navigation -->
                        <div class="flex space-x-2 mb-4 border-b border-slate-200 dark:border-slate-700">
                            <button onclick="switchTab('html')" id="tab-html" class="px-4 py-2 font-medium text-sm border-b-2 border-blue-500 text-blue-600 dark:text-blue-400 transition-colors duration-200">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                                </svg>
                                HTML
                            </button>
                            <button onclick="switchTab('text')" id="tab-text" class="px-4 py-2 font-medium text-sm border-b-2 border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 transition-colors duration-200">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Plain Text
                            </button>
                        </div>

                        <!-- HTML Content -->
                        <div id="content-html" class="tab-content">
                            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg overflow-hidden">
                                <div class="bg-slate-100 dark:bg-slate-800 px-4 py-2 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                                    <span class="text-xs text-slate-600 dark:text-slate-400 font-medium">HTML Preview</span>
                                    <button onclick="copyContent('html')" class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium">
                                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                        </svg>
                                        คัดลอก
                                    </button>
                                </div>
                                <div class="p-6 max-h-96 overflow-y-auto bg-white dark:bg-slate-950">
                                    <div class="prose dark:prose-invert max-w-none">
                                        {!! $log->body_html ?? '<p class="text-slate-500 dark:text-slate-400">ไม่มีเนื้อหา HTML</p>' !!}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Plain Text Content -->
                        <div id="content-text" class="tab-content hidden">
                            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg overflow-hidden">
                                <div class="bg-slate-100 dark:bg-slate-800 px-4 py-2 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                                    <span class="text-xs text-slate-600 dark:text-slate-400 font-medium">Plain Text Preview</span>
                                    <button onclick="copyContent('text')" class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium">
                                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                        </svg>
                                        คัดลอก
                                    </button>
                                </div>
                                <div class="p-6 max-h-96 overflow-y-auto bg-slate-50 dark:bg-slate-950">
                                    <pre class="text-sm text-slate-900 dark:text-slate-100 whitespace-pre-wrap font-mono">{{ $log->body_text ?? 'ไม่มีเนื้อหาแบบ Plain Text' }}</pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tracking Events Timeline -->
                @if($log->opened_at || $log->clicked_at || $log->bounced_at)
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6 border border-slate-200 dark:border-slate-700 animate-slide-up transition-all duration-300" style="animation-delay: 0.3s">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100 mb-6 flex items-center">
                        <svg class="w-6 h-6 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        ไทม์ไลน์การติดตาม
                    </h2>

                    <div class="space-y-4">
                        <!-- Sent Event -->
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center shadow-lg">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1 bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border-l-4 border-blue-500">
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="font-semibold text-slate-900 dark:text-slate-100">อีเมลถูกส่ง</h3>
                                    <span class="text-xs text-slate-500 dark:text-slate-400">
                                        {{ $log->created_at ? $log->created_at->format('H:i:s') : '-' }}
                                    </span>
                                </div>
                                <p class="text-sm text-slate-600 dark:text-slate-400">
                                    อีเมลถูกส่งผ่าน {{ strtoupper($log->provider ?? 'Unknown') }} สำเร็จ
                                </p>
                            </div>
                        </div>

                        @if($log->opened_at)
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center shadow-lg">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 19v-8.93a2 2 0 01.89-1.664l7-4.666a2 2 0 012.22 0l7 4.666A2 2 0 0121 10.07V19M3 19a2 2 0 002 2h14a2 2 0 002-2M3 19l6.75-4.5M21 19l-6.75-4.5M3 10l6.75 4.5M21 10l-6.75 4.5m0 0l-1.14.76a2 2 0 01-2.22 0l-1.14-.76"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1 bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border-l-4 border-green-500">
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="font-semibold text-slate-900 dark:text-slate-100">อีเมลถูกเปิด</h3>
                                    <span class="text-xs text-slate-500 dark:text-slate-400">
                                        {{ $log->opened_at->format('H:i:s') }}
                                    </span>
                                </div>
                                <p class="text-sm text-slate-600 dark:text-slate-400">
                                    ผู้รับเปิดอ่านอีเมลเรียบร้อยแล้ว
                                </p>
                            </div>
                        </div>
                        @endif

                        @if($log->clicked_at)
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-purple-600 rounded-full flex items-center justify-center shadow-lg">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1 bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4 border-l-4 border-purple-500">
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="font-semibold text-slate-900 dark:text-slate-100">คลิกลิงก์</h3>
                                    <span class="text-xs text-slate-500 dark:text-slate-400">
                                        {{ $log->clicked_at->format('H:i:s') }}
                                    </span>
                                </div>
                                <p class="text-sm text-slate-600 dark:text-slate-400">
                                    ผู้รับคลิกลิงก์ในอีเมลแล้ว
                                </p>
                            </div>
                        </div>
                        @endif

                        @if($log->bounced_at)
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-gradient-to-br from-red-500 to-red-600 rounded-full flex items-center justify-center shadow-lg">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1 bg-red-50 dark:bg-red-900/20 rounded-lg p-4 border-l-4 border-red-500">
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="font-semibold text-slate-900 dark:text-slate-100">Bounced</h3>
                                    <span class="text-xs text-slate-500 dark:text-slate-400">
                                        {{ $log->bounced_at->format('H:i:s') }}
                                    </span>
                                </div>
                                <p class="text-sm text-slate-600 dark:text-slate-400">
                                    อีเมลถูกตีกลับ (Bounce)
                                </p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

            </div>

            <!-- Right Column -->
            <div class="space-y-6">

                <!-- Delivery Stats -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6 border border-slate-200 dark:border-slate-700 animate-slide-right transition-all duration-300">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        สถิติการส่ง
                    </h2>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 rounded-lg
                            @if($log->status == 'sent') bg-gradient-to-r from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20
                            @elseif($log->status == 'failed') bg-gradient-to-r from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20
                            @else bg-gradient-to-r from-slate-50 to-slate-100 dark:from-slate-700/20 dark:to-slate-600/20
                            @endif">
                            <span class="text-sm text-slate-700 dark:text-slate-300">สถานะ</span>
                            <span class="text-sm font-bold
                                @if($log->status == 'sent') text-green-600 dark:text-green-400
                                @elseif($log->status == 'failed') text-red-600 dark:text-red-400
                                @else text-slate-600 dark:text-slate-400
                                @endif">
                                {{ ucfirst($log->status) }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between p-3 bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-lg">
                            <span class="text-sm text-slate-700 dark:text-slate-300">Provider</span>
                            <span class="text-sm font-bold text-blue-600 dark:text-blue-400">{{ strtoupper($log->provider ?? 'N/A') }}</span>
                        </div>

                        <div class="flex items-center justify-between p-3 bg-gradient-to-r from-cyan-50 to-cyan-100 dark:from-cyan-900/20 dark:to-cyan-800/20 rounded-lg">
                            <span class="text-sm text-slate-700 dark:text-slate-300">ความพยายาม</span>
                            <span class="text-sm font-bold text-cyan-600 dark:text-cyan-400">{{ $log->attempts ?? 1 }}</span>
                        </div>
                    </div>
                </div>

                <!-- Tracking Stats -->
                @if($log->opened_at || $log->clicked_at)
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6 border border-slate-200 dark:border-slate-700 animate-slide-right transition-all duration-300" style="animation-delay: 0.1s">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        การติดตาม
                    </h2>

                    <div class="space-y-3">
                        @if($log->opened_at)
                        <div class="flex items-center space-x-3 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div class="flex-1">
                                <p class="text-xs text-slate-600 dark:text-slate-400">เปิดอ่านเมื่อ</p>
                                <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                                    {{ $log->opened_at->format('d/m/Y H:i') }}
                                </p>
                            </div>
                        </div>
                        @endif

                        @if($log->clicked_at)
                        <div class="flex items-center space-x-3 p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                            <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/>
                            </svg>
                            <div class="flex-1">
                                <p class="text-xs text-slate-600 dark:text-slate-400">คลิกเมื่อ</p>
                                <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                                    {{ $log->clicked_at->format('d/m/Y H:i') }}
                                </p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Error Details -->
                @if($log->status == 'failed' && $log->error_message)
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6 border border-red-200 dark:border-red-800 animate-slide-right transition-all duration-300" style="animation-delay: 0.2s">
                    <h2 class="text-lg font-bold text-red-600 dark:text-red-400 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        รายละเอียด Error
                    </h2>

                    <div class="p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                        <p class="text-sm text-red-900 dark:text-red-200 whitespace-pre-wrap">
                            {{ $log->error_message }}
                        </p>
                    </div>
                </div>
                @endif

                <!-- Metadata -->
                @if($log->metadata && is_array($log->metadata) && count($log->metadata) > 0)
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6 border border-slate-200 dark:border-slate-700 animate-slide-right transition-all duration-300" style="animation-delay: 0.3s">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                        Metadata
                    </h2>

                    <div class="space-y-2">
                        @foreach($log->metadata as $key => $value)
                        <div class="flex items-start space-x-2 p-2 bg-slate-50 dark:bg-slate-700/50 rounded">
                            <span class="text-xs font-mono text-slate-500 dark:text-slate-400">{{ $key }}:</span>
                            <span class="text-xs text-slate-900 dark:text-slate-100 flex-1 break-all">{{ is_array($value) ? json_encode($value) : $value }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Timestamps -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6 border border-slate-200 dark:border-slate-700 animate-slide-right transition-all duration-300" style="animation-delay: 0.4s">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        เวลา
                    </h2>

                    <div class="space-y-3">
                        <div>
                            <label class="text-xs text-slate-500 dark:text-slate-400 font-medium">สร้างเมื่อ</label>
                            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100 mt-1">
                                {{ $log->created_at ? $log->created_at->format('d/m/Y H:i:s') : '-' }}
                            </p>
                            @if($log->created_at)
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                ({{ $log->created_at->diffForHumans() }})
                            </p>
                            @endif
                        </div>

                        @if($log->sent_at)
                        <div>
                            <label class="text-xs text-slate-500 dark:text-slate-400 font-medium">ส่งเมื่อ</label>
                            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100 mt-1">
                                {{ $log->sent_at->format('d/m/Y H:i:s') }}
                            </p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                ({{ $log->sent_at->diffForHumans() }})
                            </p>
                        </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>

<script>
function switchTab(tab) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('[id^="tab-"]').forEach(el => {
        el.classList.remove('border-blue-500', 'text-blue-600', 'dark:text-blue-400');
        el.classList.add('border-transparent', 'text-slate-500', 'dark:text-slate-400');
    });

    // Show selected tab
    document.getElementById('content-' + tab).classList.remove('hidden');
    const tabBtn = document.getElementById('tab-' + tab);
    tabBtn.classList.add('border-blue-500', 'text-blue-600', 'dark:text-blue-400');
    tabBtn.classList.remove('border-transparent', 'text-slate-500', 'dark:text-slate-400');
}

function copyContent(type) {
    const content = type === 'html'
        ? @json($log->body_html ?? '')
        : @json($log->body_text ?? '');

    navigator.clipboard.writeText(content).then(() => {
        alert('คัดลอกเนื้อหาเรียบร้อยแล้ว!');
    });
}
</script>

<style>
@keyframes fade-in {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes slide-up {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes slide-right {
    from { opacity: 0; transform: translateX(20px); }
    to { opacity: 1; transform: translateX(0); }
}

.animate-fade-in {
    animation: fade-in 0.6s ease-out forwards;
}

.animate-slide-up {
    animation: slide-up 0.6s ease-out forwards;
}

.animate-slide-right {
    animation: slide-right 0.6s ease-out forwards;
}
</style>
@endsection
