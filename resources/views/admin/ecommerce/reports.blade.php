{{--
    รายงานยอดขาย E-commerce
    ออกแบบตาม V3 Design System - Tailwind CSS + Alpine.js + Chart.js

    แสดงข้อมูล:
    - สรุปยอดขาย (Summary Stats)
    - กราฟแนวโน้มยอดขาย (Sales Trend Chart)
    - ยอดขายตามหมวดหมู่ (Category Performance)
    - สถานะคำสั่งซื้อ (Order Status Distribution)
    - สินค้าขายดี (Top Products)
    - สถิติลูกค้า (Customer Stats)
--}}
@extends('layouts.admin-v3')

@section('title', 'รายงานยอดขาย E-commerce')

@section('content')
<div x-data="ecommerceReports()" class="min-h-screen py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- ===== HEADER SECTION ===== --}}
        <div class="mb-8">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                {{-- Title & Breadcrumb --}}
                <div>
                    <nav class="flex mb-3 text-sm" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-2">
                            <li>
                                <a href="{{ route('admin.dashboard') }}" class="text-gray-500 hover:text-orange-600 dark:text-gray-400 dark:hover:text-orange-400 transition-colors">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                                    </svg>
                                </a>
                            </li>
                            <li><span class="text-gray-400 dark:text-gray-500">/</span></li>
                            <li>
                                <a href="{{ route('admin.ecommerce.dashboard') }}" class="text-gray-500 hover:text-orange-600 dark:text-gray-400 dark:hover:text-orange-400 transition-colors">
                                    E-commerce
                                </a>
                            </li>
                            <li><span class="text-gray-400 dark:text-gray-500">/</span></li>
                            <li class="text-gray-900 dark:text-white font-medium">รายงาน</li>
                        </ol>
                    </nav>

                    <h1 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white flex items-center gap-4">
                        <div class="p-3 bg-gradient-to-br from-orange-500 to-red-600 rounded-2xl shadow-lg shadow-orange-500/30">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <span>รายงานยอดขาย</span>
                    </h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        ช่วงเวลา: {{ \Carbon\Carbon::parse($dateFrom)->locale('th')->translatedFormat('d M Y') }}
                        ถึง {{ \Carbon\Carbon::parse($dateTo)->locale('th')->translatedFormat('d M Y') }}
                    </p>
                </div>

                {{-- Controls --}}
                <div class="flex flex-wrap gap-3">
                    {{-- Period Selector --}}
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open"
                                class="inline-flex items-center px-4 py-2.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all shadow-sm">
                            <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span x-text="periodLabels[selectedPeriod]">30 วันที่ผ่านมา</span>
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-show="open"
                             @click.away="open = false"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 z-50 overflow-hidden">
                            <template x-for="(label, key) in periodLabels" :key="key">
                                <a :href="'?period=' + key"
                                   class="block px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-orange-50 dark:hover:bg-gray-700 transition-colors"
                                   :class="{ 'bg-orange-50 dark:bg-gray-700 text-orange-600 dark:text-orange-400 font-medium': selectedPeriod === key }">
                                    <span x-text="label"></span>
                                </a>
                            </template>
                        </div>
                    </div>

                    {{-- Export Buttons --}}
                    <div class="flex gap-2">
                        <a href="{{ route('admin.unified-reports.export', ['type' => 'ecommerce', 'period' => $period ?? 'month']) }}"
                           class="inline-flex items-center px-4 py-2.5 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-medium rounded-xl shadow-lg shadow-green-500/30 transition-all transform hover:scale-105">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Excel
                        </a>
                        <a href="{{ route('admin.unified-reports.export-csv', ['type' => 'ecommerce', 'period' => $period ?? 'month']) }}"
                           class="inline-flex items-center px-4 py-2.5 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-xl shadow-lg transition-all transform hover:scale-105">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            CSV
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== SUMMARY STATS CARDS ===== --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            {{-- รายได้รวม --}}
            <div class="group bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl p-6 border border-gray-100 dark:border-gray-700 transform hover:scale-105 hover:-translate-y-1 transition-all duration-300 overflow-hidden relative">
                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-green-500 to-emerald-600"></div>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">รายได้รวม</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                            ฿{{ number_format($summary['total_revenue'] ?? 0, 0) }}
                        </p>
                        @if(($summary['revenue_growth'] ?? 0) != 0)
                            <p class="text-sm mt-2 flex items-center gap-1 {{ ($summary['revenue_growth'] ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                @if(($summary['revenue_growth'] ?? 0) >= 0)
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                                @else
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                @endif
                                {{ abs($summary['revenue_growth'] ?? 0) }}% จากช่วงก่อนหน้า
                            </p>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">เทียบกับช่วงก่อนหน้า</p>
                        @endif
                    </div>
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center shadow-lg shadow-green-500/30 group-hover:scale-110 transition-transform">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- คำสั่งซื้อทั้งหมด --}}
            <div class="group bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl p-6 border border-gray-100 dark:border-gray-700 transform hover:scale-105 hover:-translate-y-1 transition-all duration-300 overflow-hidden relative">
                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-500 to-indigo-600"></div>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">คำสั่งซื้อทั้งหมด</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                            {{ number_format($summary['total_orders'] ?? 0) }}
                        </p>
                        @if(($summary['orders_growth'] ?? 0) != 0)
                            <p class="text-sm mt-2 flex items-center gap-1 {{ ($summary['orders_growth'] ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                @if(($summary['orders_growth'] ?? 0) >= 0)
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                                @else
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                @endif
                                {{ abs($summary['orders_growth'] ?? 0) }}% จากช่วงก่อนหน้า
                            </p>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">{{ $summary['completed_orders'] ?? 0 }} สำเร็จ</p>
                        @endif
                    </div>
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-lg shadow-blue-500/30 group-hover:scale-110 transition-transform">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- มูลค่าเฉลี่ยต่อคำสั่งซื้อ --}}
            <div class="group bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl p-6 border border-gray-100 dark:border-gray-700 transform hover:scale-105 hover:-translate-y-1 transition-all duration-300 overflow-hidden relative">
                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-purple-500 to-pink-600"></div>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">มูลค่าเฉลี่ย/คำสั่งซื้อ</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                            ฿{{ number_format($summary['average_order_value'] ?? 0, 0) }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Average Order Value</p>
                    </div>
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center shadow-lg shadow-purple-500/30 group-hover:scale-110 transition-transform">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- สินค้าขายได้ --}}
            <div class="group bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl p-6 border border-gray-100 dark:border-gray-700 transform hover:scale-105 hover:-translate-y-1 transition-all duration-300 overflow-hidden relative">
                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-orange-500 to-red-600"></div>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">สินค้าขายได้</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                            {{ number_format($summary['total_items_sold'] ?? 0) }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">ชิ้น</p>
                    </div>
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-orange-500 to-red-600 flex items-center justify-center shadow-lg shadow-orange-500/30 group-hover:scale-110 transition-transform">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== CHARTS SECTION ===== --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            {{-- Sales Trend Chart --}}
            <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                        </svg>
                        แนวโน้มยอดขาย
                    </h3>
                    <div class="flex items-center gap-4 text-sm">
                        <span class="flex items-center gap-1">
                            <span class="w-3 h-3 rounded-full bg-gradient-to-r from-orange-500 to-red-500"></span>
                            <span class="text-gray-600 dark:text-gray-400">รายได้</span>
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="w-3 h-3 rounded-full bg-gradient-to-r from-blue-500 to-indigo-500"></span>
                            <span class="text-gray-600 dark:text-gray-400">คำสั่งซื้อ</span>
                        </span>
                    </div>
                </div>
                <div class="h-72">
                    <canvas id="salesTrendChart"></canvas>
                </div>
            </div>

            {{-- Category Performance --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2 mb-6">
                    <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>
                    </svg>
                    ยอดขายตามหมวดหมู่
                </h3>
                <div class="h-56 mb-4">
                    <canvas id="categoryChart"></canvas>
                </div>
                <div class="space-y-2 max-h-32 overflow-y-auto">
                    @forelse($categoryPerformance->take(5) as $index => $category)
                        <div class="flex items-center justify-between text-sm">
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full" style="background-color: {{ ['#f97316', '#8b5cf6', '#06b6d4', '#10b981', '#f43f5e'][$index % 5] }}"></span>
                                <span class="text-gray-700 dark:text-gray-300 truncate max-w-[120px]">{{ $category->name ?? 'ไม่ระบุหมวดหมู่' }}</span>
                            </div>
                            <span class="font-medium text-gray-900 dark:text-white">{{ $category->percentage ?? 0 }}%</span>
                        </div>
                    @empty
                        <p class="text-gray-500 dark:text-gray-400 text-center py-4">ไม่มีข้อมูล</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ===== ORDER STATUS & CUSTOMER STATS ===== --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            {{-- Order Status Distribution --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2 mb-6">
                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                    สถานะคำสั่งซื้อ
                </h3>
                <div class="space-y-3">
                    @php
                        $statusColors = [
                            'yellow' => 'from-yellow-400 to-amber-500',
                            'blue' => 'from-blue-400 to-indigo-500',
                            'indigo' => 'from-indigo-400 to-purple-500',
                            'teal' => 'from-teal-400 to-cyan-500',
                            'green' => 'from-green-400 to-emerald-500',
                            'red' => 'from-red-400 to-rose-500',
                            'gray' => 'from-gray-400 to-slate-500',
                        ];
                        $totalOrders = $orderStatusDistribution->sum('count') ?: 1;
                    @endphp
                    @forelse($orderStatusDistribution as $status)
                        @php
                            $percentage = round(($status->count / $totalOrders) * 100, 1);
                            $gradient = $statusColors[$status->color] ?? 'from-gray-400 to-slate-500';
                        @endphp
                        <div class="group">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $status->label }}</span>
                                <span class="text-sm font-bold text-gray-900 dark:text-white">{{ number_format($status->count) }} <span class="font-normal text-gray-500">({{ $percentage }}%)</span></span>
                            </div>
                            <div class="w-full h-2.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r {{ $gradient }} rounded-full transition-all duration-500 group-hover:shadow-lg"
                                     style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8">
                            <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <p class="text-gray-500 dark:text-gray-400">ไม่มีข้อมูลคำสั่งซื้อ</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Customer Stats --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2 mb-6">
                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    สถิติลูกค้า
                </h3>
                <div class="grid grid-cols-3 gap-4">
                    {{-- Total Customers --}}
                    <div class="text-center p-4 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl">
                        <div class="w-12 h-12 mx-auto mb-3 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/30">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                        </div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($customerStats['total_customers'] ?? 0) }}</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">ลูกค้าทั้งหมด</p>
                    </div>

                    {{-- Returning Customers --}}
                    <div class="text-center p-4 bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl">
                        <div class="w-12 h-12 mx-auto mb-3 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center shadow-lg shadow-green-500/30">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                        </div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($customerStats['returning_customers'] ?? 0) }}</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">ลูกค้าขาประจำ</p>
                    </div>

                    {{-- New Customers --}}
                    <div class="text-center p-4 bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl">
                        <div class="w-12 h-12 mx-auto mb-3 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center shadow-lg shadow-purple-500/30">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                            </svg>
                        </div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($customerStats['new_customers'] ?? 0) }}</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">ลูกค้าใหม่</p>
                    </div>
                </div>

                {{-- Customer Insight --}}
                <div class="mt-6 p-4 bg-gradient-to-r from-orange-50 to-red-50 dark:from-orange-900/20 dark:to-red-900/20 rounded-xl border border-orange-100 dark:border-orange-900/30">
                    <div class="flex items-start gap-3">
                        <div class="p-2 bg-gradient-to-br from-orange-500 to-red-600 rounded-lg">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">Customer Insight</p>
                            @php
                                $retentionRate = ($customerStats['total_customers'] ?? 0) > 0
                                    ? round((($customerStats['returning_customers'] ?? 0) / ($customerStats['total_customers'] ?? 1)) * 100, 1)
                                    : 0;
                            @endphp
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                อัตราการกลับมาซื้อซ้ำ: <span class="font-bold text-orange-600 dark:text-orange-400">{{ $retentionRate }}%</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== TOP PRODUCTS TABLE ===== --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="p-6 border-b border-gray-100 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                        </svg>
                        สินค้าขายดี Top 10
                    </h3>
                    <a href="{{ route('admin.ecommerce.products') }}" class="text-sm text-orange-600 dark:text-orange-400 hover:underline flex items-center gap-1">
                        ดูทั้งหมด
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">อันดับ</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">สินค้า</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">ขายได้</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">รายได้</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">สถานะ</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($topProducts as $index => $product)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($index < 3)
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-white shadow-lg
                                            {{ $index === 0 ? 'bg-gradient-to-br from-yellow-400 to-amber-500 shadow-yellow-500/30' : '' }}
                                            {{ $index === 1 ? 'bg-gradient-to-br from-gray-300 to-gray-400 shadow-gray-400/30' : '' }}
                                            {{ $index === 2 ? 'bg-gradient-to-br from-orange-400 to-amber-600 shadow-orange-500/30' : '' }}">
                                            {{ $index + 1 }}
                                        </div>
                                    @else
                                        <span class="text-gray-500 dark:text-gray-400 font-medium pl-2">{{ $index + 1 }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        @if($product->image)
                                            <img src="{{ $product->image }}" alt="{{ $product->name }}" class="w-12 h-12 rounded-xl object-cover shadow-sm">
                                        @else
                                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 flex items-center justify-center">
                                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                            </div>
                                        @endif
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-white">{{ Str::limit($product->name, 40) }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">SKU: {{ $product->sku ?? 'N/A' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                                        {{ number_format($product->total_sales ?? 0) }} ชิ้น
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <span class="text-lg font-bold text-green-600 dark:text-green-400">฿{{ number_format($product->total_revenue ?? 0, 0) }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @if($product->is_active ?? true)
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-500 mr-1.5 animate-pulse"></span>
                                            พร้อมขาย
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                            ปิดการขาย
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                    <p class="text-gray-500 dark:text-gray-400 text-lg">ไม่มีข้อมูลสินค้าในช่วงเวลานี้</p>
                                    <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">ลองเปลี่ยนช่วงเวลาที่ต้องการดู</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ===== CATEGORY PERFORMANCE TABLE ===== --}}
        <div class="mt-8 bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="p-6 border-b border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    ประสิทธิภาพตามหมวดหมู่
                </h3>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">หมวดหมู่</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">จำนวนขาย</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">รายได้</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">สัดส่วน</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($categoryPerformance->take(10) as $index => $category)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl flex items-center justify-center shadow-sm"
                                             style="background: linear-gradient(135deg, {{ ['#f97316', '#8b5cf6', '#06b6d4', '#10b981', '#f43f5e', '#6366f1', '#14b8a6', '#f59e0b', '#ec4899', '#3b82f6'][$index % 10] }}20, {{ ['#f97316', '#8b5cf6', '#06b6d4', '#10b981', '#f43f5e', '#6366f1', '#14b8a6', '#f59e0b', '#ec4899', '#3b82f6'][$index % 10] }}40)">
                                            <svg class="w-5 h-5" style="color: {{ ['#f97316', '#8b5cf6', '#06b6d4', '#10b981', '#f43f5e', '#6366f1', '#14b8a6', '#f59e0b', '#ec4899', '#3b82f6'][$index % 10] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                            </svg>
                                        </div>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ $category->name ?? 'ไม่ระบุหมวดหมู่' }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="font-medium text-gray-900 dark:text-white">{{ number_format($category->total_sales ?? 0) }} ชิ้น</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <span class="font-bold text-green-600 dark:text-green-400">฿{{ number_format($category->total_revenue ?? 0, 0) }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-2">
                                        <div class="w-24 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full transition-all duration-500"
                                                 style="width: {{ $category->percentage ?? 0 }}%; background: linear-gradient(90deg, {{ ['#f97316', '#8b5cf6', '#06b6d4', '#10b981', '#f43f5e', '#6366f1', '#14b8a6', '#f59e0b', '#ec4899', '#3b82f6'][$index % 10] }}, {{ ['#ea580c', '#7c3aed', '#0891b2', '#059669', '#e11d48', '#4f46e5', '#0d9488', '#d97706', '#db2777', '#2563eb'][$index % 10] }})"></div>
                                        </div>
                                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400 w-12 text-right">{{ $category->percentage ?? 0 }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    ไม่มีข้อมูลหมวดหมู่
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
/**
 * E-commerce Reports Alpine.js Component
 * จัดการ state และ interactions สำหรับหน้ารายงาน
 */
function ecommerceReports() {
    return {
        selectedPeriod: '{{ $period ?? "month" }}',
        periodLabels: {
            'today': 'วันนี้',
            'yesterday': 'เมื่อวาน',
            'week': '7 วันที่ผ่านมา',
            'month': '30 วันที่ผ่านมา',
            'quarter': '3 เดือนที่ผ่านมา',
            'year': '1 ปีที่ผ่านมา'
        },

        init() {
            this.initSalesTrendChart();
            this.initCategoryChart();
        },

        /**
         * สร้างกราฟแนวโน้มยอดขาย
         */
        initSalesTrendChart() {
            const ctx = document.getElementById('salesTrendChart');
            if (!ctx) return;

            // ข้อมูลจาก Laravel
            const salesData = @json($salesReport);

            // เตรียมข้อมูลสำหรับกราฟ
            const labels = salesData.map(item => {
                const date = new Date(item.date);
                return date.toLocaleDateString('th-TH', { day: 'numeric', month: 'short' });
            });
            const revenueData = salesData.map(item => parseFloat(item.revenue) || 0);
            const ordersData = salesData.map(item => parseInt(item.orders) || 0);

            // ตรวจสอบ dark mode
            const isDark = document.documentElement.classList.contains('dark');
            const textColor = isDark ? '#9ca3af' : '#6b7280';
            const gridColor = isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'รายได้ (฿)',
                            data: revenueData,
                            borderColor: '#f97316',
                            backgroundColor: 'rgba(249, 115, 22, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            yAxisID: 'y',
                            pointBackgroundColor: '#f97316',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                        },
                        {
                            label: 'คำสั่งซื้อ',
                            data: ordersData,
                            borderColor: '#6366f1',
                            backgroundColor: 'rgba(99, 102, 241, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            yAxisID: 'y1',
                            pointBackgroundColor: '#6366f1',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6,
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
                            display: false,
                        },
                        tooltip: {
                            backgroundColor: isDark ? '#1f2937' : '#fff',
                            titleColor: isDark ? '#fff' : '#111827',
                            bodyColor: isDark ? '#d1d5db' : '#4b5563',
                            borderColor: isDark ? '#374151' : '#e5e7eb',
                            borderWidth: 1,
                            padding: 12,
                            cornerRadius: 8,
                            displayColors: true,
                            callbacks: {
                                label: function(context) {
                                    if (context.datasetIndex === 0) {
                                        return 'รายได้: ฿' + context.parsed.y.toLocaleString();
                                    }
                                    return 'คำสั่งซื้อ: ' + context.parsed.y + ' รายการ';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                color: gridColor,
                            },
                            ticks: {
                                color: textColor,
                                maxRotation: 45,
                                minRotation: 0,
                            }
                        },
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            grid: {
                                color: gridColor,
                            },
                            ticks: {
                                color: textColor,
                                callback: function(value) {
                                    if (value >= 1000000) return '฿' + (value / 1000000).toFixed(1) + 'M';
                                    if (value >= 1000) return '฿' + (value / 1000).toFixed(0) + 'K';
                                    return '฿' + value;
                                }
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            grid: {
                                drawOnChartArea: false,
                            },
                            ticks: {
                                color: textColor,
                            }
                        },
                    }
                }
            });
        },

        /**
         * สร้างกราฟวงกลมหมวดหมู่
         */
        initCategoryChart() {
            const ctx = document.getElementById('categoryChart');
            if (!ctx) return;

            // ข้อมูลจาก Laravel
            const categoryData = @json($categoryPerformance->take(5));

            const labels = categoryData.map(item => item.name || 'ไม่ระบุ');
            const data = categoryData.map(item => parseFloat(item.total_revenue) || 0);
            const colors = ['#f97316', '#8b5cf6', '#06b6d4', '#10b981', '#f43f5e'];

            // ตรวจสอบ dark mode
            const isDark = document.documentElement.classList.contains('dark');

            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: colors,
                        borderColor: isDark ? '#1f2937' : '#fff',
                        borderWidth: 3,
                        hoverOffset: 10,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: {
                        legend: {
                            display: false,
                        },
                        tooltip: {
                            backgroundColor: isDark ? '#1f2937' : '#fff',
                            titleColor: isDark ? '#fff' : '#111827',
                            bodyColor: isDark ? '#d1d5db' : '#4b5563',
                            borderColor: isDark ? '#374151' : '#e5e7eb',
                            borderWidth: 1,
                            padding: 12,
                            cornerRadius: 8,
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((context.parsed / total) * 100).toFixed(1);
                                    return context.label + ': ฿' + context.parsed.toLocaleString() + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }
    }
}
</script>
@endpush
