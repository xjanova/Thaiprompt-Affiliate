{{--
    รายงานภาพรวมผู้บริหาร (Executive Summary)
    แสดงข้อมูลสรุปทุกระบบพร้อมกราฟและ Export
--}}
@extends('layouts.admin')

@section('title', $pageTitle ?? 'รายงานภาพรวมผู้บริหาร')

@section('content')
<div x-data="executiveReport()" class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="mb-8">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <nav class="flex mb-3" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-2">
                            <li><a href="{{ route('admin.unified-reports.index') }}"
                                   class="text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400">
                                ศูนย์รายงาน</a></li>
                            <li><span class="text-gray-400">/</span></li>
                            <li class="text-gray-900 dark:text-white font-medium">ภาพรวมผู้บริหาร</li>
                        </ol>
                    </nav>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                        {{ $pageTitle ?? 'รายงานภาพรวมผู้บริหาร' }}
                    </h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        ช่วงเวลา: {{ $summary['period']['start'] ?? '-' }} ถึง {{ $summary['period']['end'] ?? '-' }}
                    </p>
                </div>

                {{-- Controls --}}
                <div class="flex flex-wrap gap-3">
                    {{-- Period Selector --}}
                    <select x-model="selectedPeriod" @change="changePeriod()"
                            class="px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600
                                   rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                        <option value="today">วันนี้</option>
                        <option value="week">7 วันที่ผ่านมา</option>
                        <option value="month" selected>30 วันที่ผ่านมา</option>
                        <option value="quarter">3 เดือนที่ผ่านมา</option>
                        <option value="year">1 ปีที่ผ่านมา</option>
                    </select>

                    {{-- Export Buttons --}}
                    <div class="flex gap-2">
                        <a href="{{ route('admin.unified-reports.export', ['type' => 'executive', 'period' => $period]) }}"
                           class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white
                                  font-medium rounded-lg shadow transition-colors">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Excel
                        </a>
                        <a href="{{ route('admin.unified-reports.export-csv', ['type' => 'executive', 'period' => $period]) }}"
                           class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white
                                  font-medium rounded-lg shadow transition-colors">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            CSV
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick Stats Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            {{-- Total Users --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700
                        transform hover:scale-105 transition-all duration-300">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">ผู้ใช้ทั้งหมด</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                            {{ number_format($summary['users']['total'] ?? 0) }}
                        </p>
                        <p class="text-sm text-green-600 dark:text-green-400 mt-1">
                            +{{ number_format($summary['users']['new'] ?? 0) }} ใหม่
                        </p>
                    </div>
                    <div class="w-14 h-14 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Total Revenue --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700
                        transform hover:scale-105 transition-all duration-300">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">รายได้รวม</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                            ฿{{ number_format($summary['revenue']['total'] ?? 0, 2) }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">ในช่วงเวลานี้</p>
                    </div>
                    <div class="w-14 h-14 rounded-full bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- MLM Affiliates --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700
                        transform hover:scale-105 transition-all duration-300">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">สมาชิก MLM</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                            {{ number_format($summary['mlm']['total_affiliates'] ?? 0) }}
                        </p>
                        <p class="text-sm text-green-600 dark:text-green-400 mt-1">
                            +{{ number_format($summary['mlm']['new_affiliates'] ?? 0) }} ใหม่
                        </p>
                    </div>
                    <div class="w-14 h-14 rounded-full bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Orders --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700
                        transform hover:scale-105 transition-all duration-300">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">คำสั่งซื้อ</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                            {{ number_format($summary['ecommerce']['total_orders'] ?? 0) }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            {{ number_format($summary['ecommerce']['completed_orders'] ?? 0) }} สำเร็จ
                        </p>
                    </div>
                    <div class="w-14 h-14 rounded-full bg-gradient-to-br from-orange-500 to-red-600 flex items-center justify-center">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Charts Section --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            {{-- Revenue Trend Chart --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                    แนวโน้มรายได้
                </h3>
                <div class="h-72">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            {{-- Users Trend Chart --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    แนวโน้มผู้ใช้ใหม่
                </h3>
                <div class="h-72">
                    <canvas id="usersChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Revenue Breakdown --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            {{-- Revenue by Source --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">รายได้ตามแหล่ง</h3>
                <div class="h-64">
                    <canvas id="revenueSourceChart"></canvas>
                </div>
            </div>

            {{-- Commission Summary --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">คอมมิชชั่น MLM</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <span class="text-gray-600 dark:text-gray-300">คอมมิชชั่นรวม</span>
                        <span class="font-semibold text-gray-900 dark:text-white">
                            ฿{{ number_format($summary['mlm']['total_commissions'] ?? 0, 2) }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                        <span class="text-green-600 dark:text-green-400">จ่ายแล้ว</span>
                        <span class="font-semibold text-green-700 dark:text-green-300">
                            ฿{{ number_format($summary['mlm']['paid_commissions'] ?? 0, 2) }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                        <span class="text-yellow-600 dark:text-yellow-400">รอจ่าย</span>
                        <span class="font-semibold text-yellow-700 dark:text-yellow-300">
                            ฿{{ number_format($summary['mlm']['pending_commissions'] ?? 0, 2) }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Wallet Summary --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">กระเป๋าเงิน</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <span class="text-gray-600 dark:text-gray-300">ยอดคงเหลือรวม</span>
                        <span class="font-semibold text-gray-900 dark:text-white">
                            ฿{{ number_format($summary['wallet']['total_balance'] ?? 0, 2) }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <span class="text-blue-600 dark:text-blue-400">ฝากรวม</span>
                        <span class="font-semibold text-blue-700 dark:text-blue-300">
                            ฿{{ number_format($summary['wallet']['total_deposits'] ?? 0, 2) }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                        <span class="text-red-600 dark:text-red-400">ถอนรวม</span>
                        <span class="font-semibold text-red-700 dark:text-red-300">
                            ฿{{ number_format($summary['wallet']['total_withdrawals'] ?? 0, 2) }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                        <span class="text-purple-600 dark:text-purple-400">Net Flow</span>
                        <span class="font-semibold text-purple-700 dark:text-purple-300">
                            ฿{{ number_format($summary['wallet']['net_flow'] ?? 0, 2) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Detailed Tables --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- System Summary Table --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">สรุปตามระบบ</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ระบบ</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">รายได้</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">E-commerce</td>
                                <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-white">
                                    ฿{{ number_format($summary['revenue']['ecommerce'] ?? 0, 2) }}
                                </td>
                            </tr>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">โรงแรม</td>
                                <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-white">
                                    ฿{{ number_format($summary['revenue']['hotel'] ?? 0, 2) }}
                                </td>
                            </tr>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">POS</td>
                                <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-white">
                                    ฿{{ number_format($summary['revenue']['pos'] ?? 0, 2) }}
                                </td>
                            </tr>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">บริการ</td>
                                <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-white">
                                    ฿{{ number_format($summary['revenue']['service'] ?? 0, 2) }}
                                </td>
                            </tr>
                            <tr class="bg-gray-50 dark:bg-gray-700 font-semibold">
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">รวมทั้งหมด</td>
                                <td class="px-4 py-3 text-sm text-right text-indigo-600 dark:text-indigo-400">
                                    ฿{{ number_format($summary['revenue']['total'] ?? 0, 2) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Other Systems Status --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ระบบอื่นๆ</h3>
                <div class="space-y-4">
                    {{-- AI Bot --}}
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">AI Bot</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $summary['ai_bot']['total_bots'] ?? 0 }} bots
                                </p>
                            </div>
                        </div>
                        <span class="text-sm font-medium text-green-600 dark:text-green-400">
                            {{ $summary['ai_bot']['active_bots'] ?? 0 }} active
                        </span>
                    </div>

                    {{-- Hotel --}}
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">โรงแรม</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $summary['hotel']['total_bookings'] ?? 0 }} การจอง
                                </p>
                            </div>
                        </div>
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">
                            ฿{{ number_format($summary['hotel']['total_revenue'] ?? 0, 0) }}
                        </span>
                    </div>

                    {{-- POS --}}
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center">
                                <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">POS</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $summary['pos']['total_transactions'] ?? 0 }} transactions
                                </p>
                            </div>
                        </div>
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">
                            ฿{{ number_format($summary['pos']['total_revenue'] ?? 0, 0) }}
                        </span>
                    </div>

                    {{-- Crypto --}}
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center">
                                <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">Crypto/TPIX</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $summary['crypto']['total_transactions'] ?? 0 }} transactions
                                </p>
                            </div>
                        </div>
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">
                            {{ number_format($summary['crypto']['total_volume'] ?? 0, 4) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="mt-8 text-center text-sm text-gray-500 dark:text-gray-400">
            รายงานสร้างเมื่อ: {{ $summary['generated_at'] ?? now()->toIso8601String() }}
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function executiveReport() {
    return {
        selectedPeriod: '{{ $period }}',

        init() {
            this.initCharts();
        },

        changePeriod() {
            window.location.href = '{{ route("admin.unified-reports.executive") }}?period=' + this.selectedPeriod;
        },

        initCharts() {
            // Revenue Trend Chart
            const revenueTrends = @json($trends['revenue'] ?? []);
            const revenueCtx = document.getElementById('revenueChart');
            if (revenueCtx && revenueTrends.length > 0) {
                new Chart(revenueCtx, {
                    type: 'line',
                    data: {
                        labels: revenueTrends.map(item => item.date),
                        datasets: [{
                            label: 'รายได้',
                            data: revenueTrends.map(item => item.total),
                            borderColor: '#10B981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            fill: true,
                            tension: 0.4,
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
                                    callback: function(value) {
                                        return '฿' + value.toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Users Trend Chart
            const userTrends = @json($trends['users'] ?? []);
            const usersCtx = document.getElementById('usersChart');
            if (usersCtx && userTrends.length > 0) {
                new Chart(usersCtx, {
                    type: 'bar',
                    data: {
                        labels: userTrends.map(item => item.date),
                        datasets: [{
                            label: 'ผู้ใช้ใหม่',
                            data: userTrends.map(item => item.count),
                            backgroundColor: 'rgba(79, 70, 229, 0.8)',
                            borderRadius: 8,
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
                                beginAtZero: true
                            }
                        }
                    }
                });
            }

            // Revenue Source Chart (Doughnut)
            const revenueSourceCtx = document.getElementById('revenueSourceChart');
            if (revenueSourceCtx) {
                const revenueData = @json($summary['revenue'] ?? []);
                new Chart(revenueSourceCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['E-commerce', 'โรงแรม', 'POS', 'บริการ'],
                        datasets: [{
                            data: [
                                revenueData.ecommerce || 0,
                                revenueData.hotel || 0,
                                revenueData.pos || 0,
                                revenueData.service || 0
                            ],
                            backgroundColor: [
                                '#6366F1',
                                '#10B981',
                                '#F59E0B',
                                '#EC4899'
                            ],
                            borderWidth: 0,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '60%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    usePointStyle: true,
                                }
                            }
                        }
                    }
                });
            }
        }
    }
}
</script>
@endpush
@endsection
