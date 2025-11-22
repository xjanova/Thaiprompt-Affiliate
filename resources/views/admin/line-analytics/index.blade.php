@extends('layouts.admin-v3')

@section('title', 'LINE Analytics')

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
@endpush

@section('content')
<div class="min-h-screen bg-gray-100/50 dark:bg-slate-900 py-6 px-4 sm:px-6 lg:px-8">

    <!-- LINE-Themed Header -->
    <div class="mb-8 relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#00B900] via-[#00E600] to-[#00C900] p-8 shadow-2xl shadow-green-500/30">
        <!-- Decorative Background -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 left-0 w-64 h-64 glass-fusion rounded-full -translate-x-32 -translate-y-32 border border-white/20 dark:border-white/10"></div>
            <div class="absolute bottom-0 right-0 w-96 h-96 glass-fusion rounded-full translate-x-48 translate-y-48 border border-white/20 dark:border-white/10"></div>
        </div>

        <div class="relative flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl glass-fusion backdrop-blur-md flex items-center justify-center shadow-xl border border-white/20 dark:border-white/10">
                    <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M3 3h18v18H3V3zm16 16V5H5v14h14zM7 7h4v4H7V7zm6 0h4v4h-4V7zM7 13h4v4H7v-4zm6 0h4v4h-4v-4z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-4xl font-bold text-white mb-1 flex items-center gap-2">
                        📊 LINE Analytics
                    </h1>
                    <p class="text-white/90 text-lg">
                        วิเคราะห์ข้อมูลการใช้งาน LINE Bot
                    </p>
                </div>
            </div>
            <button onclick="window.print()"
                    class="px-6 py-3 glass-fusion backdrop-blur-md border-2 border-white/40 text-white rounded-xl hover:glass-fusion transition-all duration-300 font-bold flex items-center gap-2 shadow-lg transform hover:scale-105">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                </svg>
                ส่งออกรายงาน
            </button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Messages Card -->
        <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-700 overflow-hidden transform transition-all duration-300 hover:scale-105 hover:shadow-2xl">
            <div class="bg-gradient-to-br from-[#00B900] to-[#00E600] p-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-white/90 text-sm font-bold mb-1">ข้อความทั้งหมด</p>
                        <h3 class="text-4xl font-black text-white">{{ number_format($totalMessages ?? 0) }}</h3>
                        <p class="text-white/70 text-xs mt-2">+12% จากเดือนที่แล้ว</p>
                    </div>
                    <div class="w-16 h-16 rounded-full glass-fusion flex items-center justify-center border border-white/20">
                        <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Users Card -->
        <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-700 overflow-hidden transform transition-all duration-300 hover:scale-105 hover:shadow-2xl">
            <div class="bg-gradient-to-br from-blue-500 to-cyan-500 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-white/90 text-sm font-bold mb-1">ผู้ใช้ที่ Active</p>
                        <h3 class="text-4xl font-black text-white">{{ number_format($activeUsers ?? 0) }}</h3>
                        <p class="text-white/70 text-xs mt-2">+8% จากเดือนที่แล้ว</p>
                    </div>
                    <div class="w-16 h-16 rounded-full glass-fusion flex items-center justify-center border border-white/20">
                        <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Response Rate Card -->
        <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-700 overflow-hidden transform transition-all duration-300 hover:scale-105 hover:shadow-2xl">
            <div class="bg-gradient-to-br from-purple-500 to-pink-500 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-white/90 text-sm font-bold mb-1">อัตราการตอบกลับ</p>
                        <h3 class="text-4xl font-black text-white">{{ $responseRate ?? '0' }}%</h3>
                        <p class="text-white/70 text-xs mt-2">+5% จากเดือนที่แล้ว</p>
                    </div>
                    <div class="w-16 h-16 rounded-full glass-fusion flex items-center justify-center border border-white/20">
                        <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M10 9V5l-7 7 7 7v-4.1c5 0 8.5 1.6 11 5.1-1-5-4-10-11-11z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Avg Response Time Card -->
        <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-700 overflow-hidden transform transition-all duration-300 hover:scale-105 hover:shadow-2xl">
            <div class="bg-gradient-to-br from-orange-500 to-red-500 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-white/90 text-sm font-bold mb-1">เวลาตอบกลับเฉลี่ย</p>
                        <h3 class="text-4xl font-black text-white">{{ $avgResponseTime ?? '0' }}s</h3>
                        <p class="text-white/70 text-xs mt-2">-2s จากเดือนที่แล้ว</p>
                    </div>
                    <div class="w-16 h-16 rounded-full glass-fusion flex items-center justify-center border border-white/20">
                        <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bot Accuracy Card -->
        <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-700 overflow-hidden transform transition-all duration-300 hover:scale-105 hover:shadow-2xl">
            <div class="bg-gradient-to-br from-emerald-500 to-teal-500 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-white/90 text-sm font-bold mb-1">ความแม่นยำของ Bot</p>
                        <h3 class="text-4xl font-black text-white">{{ $botAccuracy ?? '0' }}%</h3>
                        <p class="text-white/70 text-xs mt-2">+3% จากเดือนที่แล้ว</p>
                    </div>
                    <div class="w-16 h-16 rounded-full glass-fusion flex items-center justify-center border border-white/20">
                        <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Satisfaction Card -->
        <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-700 overflow-hidden transform transition-all duration-300 hover:scale-105 hover:shadow-2xl">
            <div class="bg-gradient-to-br from-yellow-500 to-amber-500 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-white/90 text-sm font-bold mb-1">ความพึงพอใจผู้ใช้</p>
                        <h3 class="text-4xl font-black text-white">{{ $userSatisfaction ?? '0' }}%</h3>
                        <p class="text-white/70 text-xs mt-2">+7% จากเดือนที่แล้ว</p>
                    </div>
                    <div class="w-16 h-16 rounded-full glass-fusion flex items-center justify-center border border-white/20">
                        <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Friends Card -->
        <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-700 overflow-hidden transform transition-all duration-300 hover:scale-105 hover:shadow-2xl">
            <div class="bg-gradient-to-br from-indigo-500 to-purple-500 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-white/90 text-sm font-bold mb-1">เพื่อน LINE ทั้งหมด</p>
                        <h3 class="text-4xl font-black text-white">{{ number_format($totalFriends ?? 0) }}</h3>
                        <p class="text-white/70 text-xs mt-2">+15% จากเดือนที่แล้ว</p>
                    </div>
                    <div class="w-16 h-16 rounded-full glass-fusion flex items-center justify-center border border-white/20">
                        <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Messages Sent Card -->
        <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-700 overflow-hidden transform transition-all duration-300 hover:scale-105 hover:shadow-2xl">
            <div class="bg-gradient-to-br from-rose-500 to-pink-500 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-white/90 text-sm font-bold mb-1">ข้อความที่ส่งออก</p>
                        <h3 class="text-4xl font-black text-white">{{ number_format($messagesSent ?? 0) }}</h3>
                        <p class="text-white/70 text-xs mt-2">+10% จากเดือนที่แล้ว</p>
                    </div>
                    <div class="w-16 h-16 rounded-full glass-fusion flex items-center justify-center border border-white/20">
                        <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Messages Over Time Chart -->
        <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-700 p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                    <svg class="w-6 h-6 text-[#00B900]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                    </svg>
                    ข้อความตามเวลา
                </h3>
            </div>
            <canvas id="messagesChart" height="80"></canvas>
        </div>

        <!-- Message Types Chart -->
        <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-700 p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                    <svg class="w-6 h-6 text-[#00B900]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>
                    </svg>
                    ประเภทข้อความ
                </h3>
            </div>
            <canvas id="messageTypesChart" height="80"></canvas>
        </div>
    </div>

    <!-- Additional Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- User Growth Chart -->
        <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-700 p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                    <svg class="w-6 h-6 text-[#00B900]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                    การเติบโตของผู้ใช้
                </h3>
            </div>
            <canvas id="userGrowthChart" height="80"></canvas>
        </div>

        <!-- Peak Hours Chart -->
        <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-700 p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                    <svg class="w-6 h-6 text-[#00B900]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    ช่วงเวลาที่คนใช้มากที่สุด
                </h3>
            </div>
            <canvas id="peakHoursChart" height="80"></canvas>
        </div>
    </div>

    <!-- Top Performing Bots Table -->
    <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-700 overflow-hidden mb-8">
        <div class="bg-gradient-to-r from-gray-50 to-slate-50 dark:from-slate-700 dark:to-slate-600 px-6 py-4 border-b-2 border-gray-200 dark:border-slate-600">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <svg class="w-6 h-6 text-[#00B900]" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
                </svg>
                Bot ที่ทำงานได้ดีที่สุด
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-100/50 dark:bg-slate-700">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">#</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">ชื่อ Bot</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">ข้อความ</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">ผู้ใช้</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">ความแม่นยำ</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">การเติบโต</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                    @for($i = 1; $i <= 5; $i++)
                        <tr class="hover:bg-gray-100/50 dark:hover:bg-slate-700/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="w-8 h-8 rounded-full bg-gradient-to-r from-[#00B900] to-[#00E600] text-white flex items-center justify-center font-bold">{{ $i }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center text-white font-bold">
                                        B{{ $i }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-gray-900 dark:text-white">LINE Bot #{{ $i }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">AI Assistant</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-700 dark:text-gray-300 font-semibold">
                                {{ number_format(rand(1000, 5000)) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-700 dark:text-gray-300 font-semibold">
                                {{ number_format(rand(100, 500)) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 bg-gray-200 dark:bg-slate-700 rounded-full h-2">
                                        <div class="bg-gradient-to-r from-[#00B900] to-[#00E600] h-2 rounded-full" style="width: {{ rand(70, 98) }}%"></div>
                                    </div>
                                    <span class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ rand(70, 98) }}%</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                                    +{{ rand(5, 20) }}%
                                </span>
                            </td>
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Activities Timeline -->
    <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-700 p-6">
        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-3">
            <svg class="w-6 h-6 text-[#00B900]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            กิจกรรมล่าสุด
        </h3>
        <div class="space-y-4">
            @for($i = 0; $i < 5; $i++)
                <div class="flex items-start gap-4 p-4 bg-gray-100/50 dark:bg-slate-700/50 rounded-xl hover:bg-gray-200/50 dark:hover:bg-slate-700 transition-colors">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-[#00B900] to-[#00E600] flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="font-bold text-gray-900 dark:text-white">ผู้ใช้ใหม่เข้าร่วมระบบ</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">มีผู้ใช้ใหม่เข้าร่วมระบบ LINE Bot</p>
                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-2">{{ now()->subMinutes(rand(5, 60))->diffForHumans() }}</p>
                    </div>
                </div>
            @endfor
        </div>
    </div>

</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check dark mode
    const isDark = document.documentElement.classList.contains('dark');
    const gridColor = isDark ? '#374151' : '#e5e7eb';
    const textColor = isDark ? '#9ca3af' : '#4b5563';

    // Messages Over Time Chart
    new Chart(document.getElementById('messagesChart'), {
        type: 'line',
        data: {
            labels: ['จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.', 'อา.'],
            datasets: [{
                label: 'ข้อความ',
                data: [120, 150, 180, 220, 200, 170, 140],
                borderColor: '#06C755',
                backgroundColor: 'rgba(6, 199, 85, 0.1)',
                fill: true,
                tension: 0.4,
                borderWidth: 3,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: isDark ? '#1e293b' : '#fff',
                    titleColor: isDark ? '#fff' : '#111',
                    bodyColor: isDark ? '#9ca3af' : '#666',
                    borderColor: gridColor,
                    borderWidth: 1
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: gridColor },
                    ticks: { color: textColor }
                },
                x: {
                    grid: { color: gridColor },
                    ticks: { color: textColor }
                }
            }
        }
    });

    // Message Types Chart
    new Chart(document.getElementById('messageTypesChart'), {
        type: 'doughnut',
        data: {
            labels: ['ข้อความ', 'รูปภาพ', 'สติกเกอร์', 'วิดีโอ', 'อื่นๆ'],
            datasets: [{
                data: [45, 25, 15, 10, 5],
                backgroundColor: [
                    '#06C755',
                    '#3b82f6',
                    '#a855f7',
                    '#f59e0b',
                    '#ef4444'
                ],
                borderWidth: 2,
                borderColor: isDark ? '#1e293b' : '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: textColor, padding: 15 }
                },
                tooltip: {
                    backgroundColor: isDark ? '#1e293b' : '#fff',
                    titleColor: isDark ? '#fff' : '#111',
                    bodyColor: isDark ? '#9ca3af' : '#666',
                    borderColor: gridColor,
                    borderWidth: 1
                }
            }
        }
    });

    // User Growth Chart
    new Chart(document.getElementById('userGrowthChart'), {
        type: 'line',
        data: {
            labels: ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.'],
            datasets: [{
                label: 'ผู้ใช้ใหม่',
                data: [30, 45, 60, 85, 120, 150],
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                fill: true,
                tension: 0.4,
                borderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: isDark ? '#1e293b' : '#fff',
                    titleColor: isDark ? '#fff' : '#111',
                    bodyColor: isDark ? '#9ca3af' : '#666',
                    borderColor: gridColor,
                    borderWidth: 1
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: gridColor },
                    ticks: { color: textColor }
                },
                x: {
                    grid: { color: gridColor },
                    ticks: { color: textColor }
                }
            }
        }
    });

    // Peak Hours Chart
    new Chart(document.getElementById('peakHoursChart'), {
        type: 'bar',
        data: {
            labels: ['00:00', '04:00', '08:00', '12:00', '16:00', '20:00'],
            datasets: [{
                label: 'จำนวนข้อความ',
                data: [20, 15, 80, 120, 150, 100],
                backgroundColor: [
                    'rgba(6, 199, 85, 0.8)',
                    'rgba(6, 199, 85, 0.7)',
                    'rgba(6, 199, 85, 0.9)',
                    'rgba(6, 199, 85, 1)',
                    'rgba(6, 199, 85, 0.95)',
                    'rgba(6, 199, 85, 0.85)'
                ],
                borderRadius: 8,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: isDark ? '#1e293b' : '#fff',
                    titleColor: isDark ? '#fff' : '#111',
                    bodyColor: isDark ? '#9ca3af' : '#666',
                    borderColor: gridColor,
                    borderWidth: 1
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: gridColor },
                    ticks: { color: textColor }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: textColor }
                }
            }
        }
    });
});
</script>
@endpush

<style>
@keyframes fade-in {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fade-in 0.3s ease-out;
}

@media print {
    .no-print {
        display: none;
    }
}
</style>
@endsection
