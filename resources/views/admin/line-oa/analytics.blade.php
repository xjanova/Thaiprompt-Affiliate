@extends('layouts.admin-v3')

@section('title', 'LINE OA Analytics Dashboard')

@section('content')
<div class="container-fluid px-4 py-6" x-data="analyticsApp()">
    <!-- Animated Header with Floating Particles -->
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-blue-500 via-indigo-600 to-purple-700 p-8 shadow-2xl">
        <!-- Animated Background Pattern -->
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS1vcGFjaXR5PSIwLjEiIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNncmlkKSIvPjwvc3ZnPg==')] opacity-30"></div>

        <!-- Floating Particles Animation -->
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute w-2 h-2 bg-white/30 rounded-full animate-float-1" style="top: 20%; left: 10%;"></div>
            <div class="absolute w-3 h-3 bg-white/20 rounded-full animate-float-2" style="top: 60%; left: 80%;"></div>
            <div class="absolute w-2 h-2 bg-white/25 rounded-full animate-float-3" style="top: 40%; left: 50%;"></div>
            <div class="absolute w-4 h-4 bg-white/15 rounded-full animate-float-4" style="top: 70%; left: 20%;"></div>
            <div class="absolute w-2 h-2 bg-white/30 rounded-full animate-float-5" style="top: 30%; left: 90%;"></div>
        </div>

        <div class="relative flex items-center justify-between flex-wrap gap-4">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-14 h-14 rounded-xl glass-fusion backdrop-blur-sm flex items-center justify-center border border-white/20 dark:border-white/10 shadow-lg">
                        <i class="fas fa-chart-line text-3xl text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-white mb-1 drop-shadow-lg">LINE OA Analytics</h1>
                        <p class="text-blue-100 drop-shadow">รายงานและวิเคราะห์การสมัครสมาชิกแบบ Real-time</p>
                    </div>
                </div>
            </div>
            <div class="flex gap-3 flex-wrap">
                <!-- Period Selector -->
                <select x-model="period" @change="loadDashboard()"
                        class="px-4 py-3 glass-fusion backdrop-blur-md border border-white/30 text-white rounded-xl hover:border-white/50 transition-all duration-300 shadow-lg focus:ring-2 focus:ring-white/50 focus:outline-none cursor-pointer">
                    <option value="day">วันนี้</option>
                    <option value="week">สัปดาห์นี้</option>
                    <option value="month" selected>เดือนนี้</option>
                    <option value="all">ทั้งหมด</option>
                </select>
                <button @click="exportData('csv')"
                        class="px-6 py-3 glass-fusion backdrop-blur-md border border-white/30 text-white rounded-xl hover:border-white/50 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 hover:scale-105">
                    <i class="fas fa-download me-2"></i>Export CSV
                </button>
                <button @click="clearCache()"
                        class="px-6 py-3 glass-fusion backdrop-blur-md border border-white/30 text-white rounded-xl hover:border-white/50 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 hover:scale-105">
                    <i class="fas fa-sync me-2" :class="{'animate-spin': loading}"></i>รีเฟรช
                </button>
            </div>
        </div>
    </div>

    <!-- Loading State with Skeleton Screens -->
    <div x-show="loading" class="space-y-6">
        <!-- Skeleton Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <template x-for="i in 4" :key="i">
                <div class="glass-fusion rounded-2xl shadow-lg p-6 animate-pulse">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-xl bg-gray-300 dark:bg-gray-700"></div>
                        <div class="h-6 w-16 bg-gray-300 dark:bg-gray-700 rounded-full"></div>
                    </div>
                    <div class="h-8 w-24 bg-gray-300 dark:bg-gray-700 rounded mb-2"></div>
                    <div class="h-4 w-32 bg-gray-300 dark:bg-gray-700 rounded"></div>
                </div>
            </template>
        </div>

        <!-- Skeleton Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <template x-for="i in 2" :key="i">
                <div class="glass-fusion rounded-2xl shadow-lg p-6 animate-pulse">
                    <div class="h-6 w-48 bg-gray-300 dark:bg-gray-700 rounded mb-4"></div>
                    <div class="space-y-3">
                        <template x-for="j in 3" :key="j">
                            <div class="h-16 bg-gray-300 dark:bg-gray-700 rounded-xl"></div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Dashboard Content -->
    <div x-show="!loading"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform scale-95"
         x-transition:enter-end="opacity-100 transform scale-100"
         class="space-y-6">

        <!-- Overview Stats Cards with Animated Counters -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Total Invitations -->
            <div class="group glass-fusion rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 p-6 border border-white/20 dark:border-white/10 transform hover:scale-105 hover:-translate-y-1">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-100 to-blue-200 dark:from-blue-900/50 dark:to-blue-800/50 flex items-center justify-center transform group-hover:scale-110 group-hover:rotate-3 transition-all duration-300">
                        <i class="fas fa-envelope text-2xl text-blue-600 dark:text-blue-400"></i>
                    </div>
                    <span class="text-xs font-semibold text-blue-600 dark:text-blue-400 bg-blue-100 dark:bg-blue-900/50 px-3 py-1 rounded-full animate-pulse-slow">
                        การเชิญ
                    </span>
                </div>
                <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-1 tabular-nums"
                    x-text="animateNumber(stats.total_invitations || 0, 'invitations')"
                    x-init="animateNumber(stats.total_invitations || 0, 'invitations')"></h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">คำเชิญทั้งหมด</p>
                <div class="mt-2 flex items-center text-xs text-green-600 dark:text-green-400">
                    <i class="fas fa-arrow-up mr-1"></i>
                    <span>+12% จากเดือนที่แล้ว</span>
                </div>
            </div>

            <!-- Clicked Rate -->
            <div class="group glass-fusion rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 p-6 border border-white/20 dark:border-white/10 transform hover:scale-105 hover:-translate-y-1">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-green-100 to-green-200 dark:from-green-900/50 dark:to-green-800/50 flex items-center justify-center transform group-hover:scale-110 group-hover:rotate-3 transition-all duration-300">
                        <i class="fas fa-mouse-pointer text-2xl text-green-600 dark:text-green-400"></i>
                    </div>
                    <span class="text-xs font-semibold text-green-600 dark:text-green-400 bg-green-100 dark:bg-green-900/50 px-3 py-1 rounded-full animate-pulse-slow">
                        คลิก
                    </span>
                </div>
                <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-1" x-text="stats.click_rate ? stats.click_rate.toFixed(1) + '%' : '0%'"></h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">อัตราการคลิก</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2" x-text="(stats.total_clicked || 0) + ' คลิก'"></p>
            </div>

            <!-- Completion Rate -->
            <div class="group glass-fusion rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 p-6 border border-white/20 dark:border-white/10 transform hover:scale-105 hover:-translate-y-1">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-100 to-purple-200 dark:from-purple-900/50 dark:to-purple-800/50 flex items-center justify-center transform group-hover:scale-110 group-hover:rotate-3 transition-all duration-300">
                        <i class="fas fa-check-circle text-2xl text-purple-600 dark:text-purple-400"></i>
                    </div>
                    <span class="text-xs font-semibold text-purple-600 dark:text-purple-400 bg-purple-100 dark:bg-purple-900/50 px-3 py-1 rounded-full animate-pulse-slow">
                        สำเร็จ
                    </span>
                </div>
                <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-1" x-text="stats.completion_rate ? stats.completion_rate.toFixed(1) + '%' : '0%'"></h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">อัตราการสมัครสำเร็จ</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2" x-text="(stats.total_completed || 0) + ' คน'"></p>
            </div>

            <!-- Active Conversations -->
            <div class="group glass-fusion rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 p-6 border border-white/20 dark:border-white/10 transform hover:scale-105 hover:-translate-y-1">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-orange-100 to-orange-200 dark:from-orange-900/50 dark:to-orange-800/50 flex items-center justify-center transform group-hover:scale-110 group-hover:rotate-3 transition-all duration-300 relative">
                        <i class="fas fa-comments text-2xl text-orange-600 dark:text-orange-400"></i>
                        <span x-show="activeConversations.length > 0" class="absolute -top-1 -right-1 w-3 h-3 bg-green-500 rounded-full animate-ping"></span>
                        <span x-show="activeConversations.length > 0" class="absolute -top-1 -right-1 w-3 h-3 bg-green-500 rounded-full"></span>
                    </div>
                    <span class="text-xs font-semibold text-orange-600 dark:text-orange-400 bg-orange-100 dark:bg-orange-900/50 px-3 py-1 rounded-full animate-pulse-slow">
                        Active
                    </span>
                </div>
                <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-1" x-text="activeConversations.length || 0"></h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">การสนทนาที่กำลังดำเนินการ</p>
                <p class="text-xs text-orange-500 dark:text-orange-400 mt-2 flex items-center">
                    <span class="inline-block w-2 h-2 bg-orange-500 rounded-full mr-1 animate-pulse"></span>
                    อัปเดตอัตโนมัติทุก 30 วินาที
                </p>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Conversion Funnel -->
            <div class="glass-fusion rounded-2xl shadow-lg hover:shadow-xl p-6 border border-white/20 dark:border-white/10 transition-all duration-300 transform hover:-translate-y-1">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center mr-3 shadow-lg">
                        <i class="fas fa-filter text-white"></i>
                    </div>
                    Conversion Funnel
                </h3>
                <div class="space-y-4">
                    <template x-for="(step, index) in funnel" :key="step.step">
                        <div class="transform transition-all duration-300 hover:scale-102"
                             x-transition:enter="transition ease-out duration-300 delay-[${index * 100}ms]"
                             x-transition:enter-start="opacity-0 translate-x-4"
                             x-transition:enter-end="opacity-100 translate-x-0">
                            <div class="flex justify-between text-sm mb-2">
                                <span class="font-medium text-gray-700 dark:text-gray-300 flex items-center">
                                    <span class="w-6 h-6 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 text-white flex items-center justify-center text-xs mr-2" x-text="index + 1"></span>
                                    <span x-text="step.step"></span>
                                </span>
                                <span class="text-gray-600 dark:text-gray-400 font-semibold" x-text="step.count + ' คน (' + step.rate.toFixed(1) + '%)'"></span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 shadow-inner overflow-hidden">
                                <div class="bg-gradient-to-r from-blue-500 via-indigo-600 to-purple-600 h-3 rounded-full transition-all duration-1000 relative overflow-hidden"
                                     :style="`width: ${step.rate}%`">
                                    <div class="absolute inset-0 bg-white/30 animate-shimmer"></div>
                                </div>
                            </div>
                        </div>
                    </template>
                    <div x-show="funnel.length === 0" class="text-center py-12 text-gray-500 dark:text-gray-400">
                        <i class="fas fa-chart-bar text-5xl mb-3 opacity-30"></i>
                        <p class="text-sm">ไม่มีข้อมูล Funnel</p>
                    </div>
                </div>
            </div>

            <!-- Dropout Analysis -->
            <div class="glass-fusion rounded-2xl shadow-lg hover:shadow-xl p-6 border border-white/20 dark:border-white/10 transition-all duration-300 transform hover:-translate-y-1">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center mr-3 shadow-lg">
                        <i class="fas fa-user-slash text-white"></i>
                    </div>
                    จุดที่ผู้ใช้หลุดออกมากที่สุด
                </h3>
                <div class="space-y-3">
                    <template x-for="(dropout, index) in dropouts.slice(0, 5)" :key="dropout.step">
                        <div class="flex items-center justify-between p-4 bg-gray-50/80 dark:bg-gray-800/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-all duration-300 transform hover:scale-102 border border-gray-200/50 dark:border-gray-700/50"
                             x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="opacity-0 translate-y-2"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             :style="`transition-delay: ${index * 50}ms`">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="w-6 h-6 rounded-full bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 flex items-center justify-center text-xs font-bold" x-text="index + 1"></span>
                                    <p class="font-medium text-gray-900 dark:text-white" x-text="dropout.step"></p>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 ml-8" x-text="dropout.reason || 'ไม่ระบุสาเหตุ'"></p>
                            </div>
                            <div class="text-right">
                                <p class="text-xl font-bold text-red-600 dark:text-red-400" x-text="dropout.count"></p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">คน</p>
                            </div>
                        </div>
                    </template>
                    <div x-show="dropouts.length === 0" class="text-center py-12 text-gray-500 dark:text-gray-400">
                        <i class="fas fa-smile text-5xl mb-3 opacity-30"></i>
                        <p class="text-sm">ไม่มีข้อมูลการหลุดออก</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Leaderboard & Active Conversations -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Sponsor Leaderboard with Medals -->
            <div class="glass-fusion rounded-2xl shadow-lg hover:shadow-xl p-6 border border-white/20 dark:border-white/10 transition-all duration-300 transform hover:-translate-y-1">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-yellow-400 to-yellow-600 flex items-center justify-center mr-3 shadow-lg">
                        <i class="fas fa-trophy text-white"></i>
                    </div>
                    อันดับ Sponsor ยอดนิยม
                </h3>
                <div class="space-y-3 max-h-[480px] overflow-y-auto custom-scrollbar pr-2">
                    <template x-for="(sponsor, index) in leaderboard.slice(0, 10)" :key="sponsor.sponsor_id">
                        <div class="flex items-center gap-4 p-4 bg-gray-50/80 dark:bg-gray-800/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-all duration-300 transform hover:scale-102 border border-gray-200/50 dark:border-gray-700/50"
                             :class="{
                                 'ring-2 ring-yellow-400/50 shadow-lg shadow-yellow-500/20': index === 0,
                                 'ring-2 ring-gray-300/50 shadow-lg shadow-gray-500/20': index === 1,
                                 'ring-2 ring-orange-400/50 shadow-lg shadow-orange-500/20': index === 2
                             }"
                             x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="opacity-0 translate-y-2"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             :style="`transition-delay: ${index * 50}ms`">
                            <!-- Medal/Rank Badge -->
                            <div class="flex-shrink-0">
                                <div x-show="index === 0" class="w-10 h-10 rounded-full bg-gradient-to-br from-yellow-400 to-yellow-600 flex items-center justify-center shadow-lg transform hover:scale-110 transition-transform">
                                    <i class="fas fa-trophy text-white text-lg"></i>
                                </div>
                                <div x-show="index === 1" class="w-10 h-10 rounded-full bg-gradient-to-br from-gray-300 to-gray-400 flex items-center justify-center shadow-lg transform hover:scale-110 transition-transform">
                                    <i class="fas fa-medal text-white text-lg"></i>
                                </div>
                                <div x-show="index === 2" class="w-10 h-10 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center shadow-lg transform hover:scale-110 transition-transform">
                                    <i class="fas fa-award text-white text-lg"></i>
                                </div>
                                <div x-show="index > 2" class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center font-bold text-gray-600 dark:text-gray-400">
                                    <span x-text="index + 1"></span>
                                </div>
                            </div>

                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-gray-900 dark:text-white truncate" x-text="sponsor.sponsor_name"></p>
                                <div class="flex items-center gap-3 mt-1">
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        <i class="fas fa-check-circle text-green-500 mr-1"></i>
                                        สำเร็จ <span class="font-semibold" x-text="sponsor.completed_count"></span>
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        <i class="fas fa-users mr-1"></i>
                                        ทั้งหมด <span class="font-semibold" x-text="sponsor.total_signups"></span>
                                    </p>
                                </div>
                            </div>

                            <div class="text-right flex-shrink-0">
                                <div class="text-lg font-bold text-green-600 dark:text-green-400" x-text="sponsor.success_rate.toFixed(1) + '%'"></div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Success Rate</p>
                            </div>
                        </div>
                    </template>
                    <div x-show="leaderboard.length === 0" class="text-center py-12 text-gray-500 dark:text-gray-400">
                        <i class="fas fa-trophy text-5xl mb-3 opacity-30"></i>
                        <p class="text-sm">ยังไม่มีข้อมูล Leaderboard</p>
                    </div>
                </div>
            </div>

            <!-- Active Conversations with Pulse Animation -->
            <div class="glass-fusion rounded-2xl shadow-lg hover:shadow-xl p-6 border border-white/20 dark:border-white/10 transition-all duration-300 transform hover:-translate-y-1">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center mr-3 shadow-lg relative">
                            <i class="fas fa-comment-dots text-white"></i>
                            <span x-show="activeConversations.length > 0" class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full animate-ping"></span>
                        </div>
                        การสนทนาที่กำลังดำเนินการ
                    </h3>
                    <span x-show="activeConversations.length > 0" class="text-xs text-green-600 dark:text-green-400 flex items-center gap-1 bg-green-100 dark:bg-green-900/30 px-3 py-1 rounded-full">
                        <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                        Live
                    </span>
                </div>

                <div class="space-y-3 max-h-[420px] overflow-y-auto custom-scrollbar pr-2">
                    <template x-for="(conv, index) in activeConversations" :key="conv.id">
                        <div class="p-4 bg-gray-50/80 dark:bg-gray-800/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-all duration-300 transform hover:scale-102 border border-gray-200/50 dark:border-gray-700/50"
                             x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="opacity-0 translate-x-4"
                             x-transition:enter-end="opacity-100 translate-x-0"
                             :style="`transition-delay: ${index * 50}ms`">
                            <div class="flex justify-between items-start mb-2">
                                <div class="flex items-center gap-2 flex-1 min-w-0">
                                    <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse flex-shrink-0"></span>
                                    <p class="font-medium text-gray-900 dark:text-white truncate" x-text="conv.sponsor_name"></p>
                                </div>
                                <span class="text-xs bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 px-2 py-1 rounded-full font-semibold flex-shrink-0 ml-2"
                                      x-text="conv.progress + '%'"></span>
                            </div>

                            <!-- Progress Bar -->
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 mb-2 overflow-hidden">
                                <div class="bg-gradient-to-r from-green-500 to-emerald-500 h-1.5 rounded-full transition-all duration-500"
                                     :style="`width: ${conv.progress}%`"></div>
                            </div>

                            <div class="flex items-center justify-between text-xs">
                                <p class="text-gray-600 dark:text-gray-400 flex items-center">
                                    <i class="fas fa-tasks mr-1"></i>
                                    <span x-text="'ขั้นตอน: ' + conv.current_step"></span>
                                </p>
                                <p class="text-gray-500 dark:text-gray-400 flex items-center">
                                    <i class="far fa-clock mr-1"></i>
                                    <span x-text="formatTime(conv.started_at)"></span>
                                </p>
                            </div>
                        </div>
                    </template>

                    <!-- Enhanced Empty State -->
                    <div x-show="activeConversations.length === 0" class="text-center py-12">
                        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gray-100 dark:bg-gray-800 mb-4">
                            <i class="fas fa-comment-slash text-4xl text-gray-400 dark:text-gray-600"></i>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400 font-medium mb-1">ไม่มีการสนทนาที่กำลังดำเนินการ</p>
                        <p class="text-xs text-gray-500 dark:text-gray-500">การสนทนาใหม่จะปรากฏที่นี่แบบ Real-time</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Trends Chart with Better Placeholder -->
        <div class="glass-fusion rounded-2xl shadow-lg hover:shadow-xl p-6 border border-white/20 dark:border-white/10 transition-all duration-300 transform hover:-translate-y-1">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center mr-3 shadow-lg">
                    <i class="fas fa-chart-area text-white"></i>
                </div>
                แนวโน้มการสมัคร
            </h3>
            <div class="h-64 flex items-center justify-center">
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-gradient-to-br from-blue-100 to-indigo-100 dark:from-blue-900/30 dark:to-indigo-900/30 mb-4">
                        <i class="fas fa-chart-line text-5xl text-blue-600 dark:text-blue-400 opacity-50"></i>
                    </div>
                    <p class="text-gray-600 dark:text-gray-400 font-medium mb-2">กราฟแสดงแนวโน้ม</p>
                    <p class="text-sm text-gray-500 dark:text-gray-500">ต้องการ Chart.js หรือ ApexCharts</p>
                    <div class="mt-4 inline-flex items-center gap-2 bg-blue-100 dark:bg-blue-900/30 px-4 py-2 rounded-full">
                        <i class="fas fa-calendar-alt text-blue-600 dark:text-blue-400"></i>
                        <span class="text-sm text-blue-700 dark:text-blue-300 font-medium">
                            จำนวนข้อมูล: <span class="font-bold" x-text="trends.length"></span> วัน
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* Floating Particles Animations */
    @keyframes float-1 {
        0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.3; }
        50% { transform: translate(20px, -20px) scale(1.2); opacity: 0.6; }
    }
    @keyframes float-2 {
        0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.2; }
        50% { transform: translate(-30px, 30px) scale(1.5); opacity: 0.5; }
    }
    @keyframes float-3 {
        0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.25; }
        50% { transform: translate(15px, 25px) scale(1.3); opacity: 0.6; }
    }
    @keyframes float-4 {
        0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.15; }
        50% { transform: translate(-25px, -15px) scale(1.4); opacity: 0.4; }
    }
    @keyframes float-5 {
        0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.3; }
        50% { transform: translate(10px, -30px) scale(1.1); opacity: 0.5; }
    }

    .animate-float-1 { animation: float-1 8s ease-in-out infinite; }
    .animate-float-2 { animation: float-2 10s ease-in-out infinite; }
    .animate-float-3 { animation: float-3 7s ease-in-out infinite; }
    .animate-float-4 { animation: float-4 12s ease-in-out infinite; }
    .animate-float-5 { animation: float-5 9s ease-in-out infinite; }

    /* Shimmer Effect for Progress Bars */
    @keyframes shimmer {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }
    .animate-shimmer {
        animation: shimmer 2s infinite;
    }

    /* Pulse Slow Animation */
    @keyframes pulse-slow {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }
    .animate-pulse-slow {
        animation: pulse-slow 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }

    /* Custom Scrollbar */
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(156, 163, 175, 0.3);
        border-radius: 3px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: rgba(156, 163, 175, 0.5);
    }
    .dark .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(75, 85, 99, 0.3);
    }
    .dark .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: rgba(75, 85, 99, 0.5);
    }

    /* Scale 102 for subtle hover effect */
    .hover\:scale-102:hover {
        transform: scale(1.02);
    }
    .transform {
        transform: translateZ(0);
    }
</style>
@endpush

@push('scripts')
<script>
function analyticsApp() {
    return {
        loading: true,
        period: 'month',
        stats: {},
        funnel: [],
        dropouts: [],
        leaderboard: [],
        activeConversations: [],
        trends: [],
        animatedNumbers: {},

        init() {
            this.loadDashboard();
            // Auto-refresh active conversations every 30 seconds
            setInterval(() => {
                if (!this.loading) {
                    this.loadActiveConversations();
                }
            }, 30000);
        },

        async loadDashboard() {
            this.loading = true;
            try {
                const response = await fetch(`/api/v1/line-analytics/dashboard?period=${this.period}`);
                const data = await response.json();

                if (data.success) {
                    this.stats = data.data.stats;
                    this.funnel = data.data.funnel;
                    this.dropouts = data.data.dropouts;
                    this.leaderboard = data.data.leaderboard;
                    this.trends = data.data.trends;
                }

                // Load active conversations
                await this.loadActiveConversations();
            } catch (error) {
                console.error('Error loading analytics:', error);
                this.showNotification('เกิดข้อผิดพลาดในการโหลดข้อมูล', 'error');
            } finally {
                this.loading = false;
            }
        },

        async loadActiveConversations() {
            try {
                const activeResponse = await fetch('/api/v1/line-analytics/active');
                const activeData = await activeResponse.json();
                if (activeData.success) {
                    this.activeConversations = activeData.data;
                }
            } catch (error) {
                console.error('Error loading active conversations:', error);
            }
        },

        // Animated Number Counter
        animateNumber(target, key) {
            if (!this.animatedNumbers[key]) {
                this.animatedNumbers[key] = 0;
            }

            const current = this.animatedNumbers[key];
            if (current === target) return target;

            const increment = Math.ceil((target - current) / 20);
            const next = current + increment;

            if ((increment > 0 && next >= target) || (increment < 0 && next <= target)) {
                this.animatedNumbers[key] = target;
                return target;
            }

            this.animatedNumbers[key] = next;
            setTimeout(() => {
                this.$el.textContent = this.animateNumber(target, key);
            }, 50);

            return next;
        },

        async exportData(format) {
            try {
                const response = await fetch(`/api/v1/line-analytics/export?format=${format}&period=${this.period}`);
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `line-analytics-${this.period}-${new Date().toISOString().split('T')[0]}.${format}`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
                this.showNotification('ดาวน์โหลดไฟล์สำเร็จ', 'success');
            } catch (error) {
                console.error('Error exporting data:', error);
                this.showNotification('เกิดข้อผิดพลาดในการ export ข้อมูล', 'error');
            }
        },

        async clearCache() {
            if (!confirm('คุณต้องการล้าง cache และโหลดข้อมูลใหม่หรือไม่?')) {
                return;
            }

            this.loading = true;
            try {
                await fetch('/api/v1/line-analytics/clear-cache', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                await this.loadDashboard();
                this.showNotification('ล้าง cache สำเร็จแล้ว!', 'success');
            } catch (error) {
                console.error('Error clearing cache:', error);
                this.showNotification('เกิดข้อผิดพลาดในการล้าง cache', 'error');
            }
        },

        formatTime(timestamp) {
            if (!timestamp) return '';
            const date = new Date(timestamp);
            const now = new Date();
            const diffMinutes = Math.floor((now - date) / 60000);

            if (diffMinutes < 1) return 'เมื่อสักครู่';
            if (diffMinutes < 60) return `${diffMinutes} นาทีที่แล้ว`;
            const diffHours = Math.floor(diffMinutes / 60);
            if (diffHours < 24) return `${diffHours} ชั่วโมงที่แล้ว`;
            return date.toLocaleDateString('th-TH', {
                day: 'numeric',
                month: 'short',
                hour: '2-digit',
                minute: '2-digit'
            });
        },

        showNotification(message, type = 'info') {
            // ใช้ notification system ของ admin layout (ถ้ามี)
            // หรือใช้ alert ชั่วคราว
            if (window.showNotification) {
                window.showNotification(message, type);
            } else {
                alert(message);
            }
        }
    }
}
</script>
@endpush
@endsection
