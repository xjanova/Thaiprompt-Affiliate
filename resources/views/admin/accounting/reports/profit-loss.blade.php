@extends('layouts.admin-v3')

@section('title', 'รายงานกำไร-ขาดทุน')

@section('content')
<div class="p-6">
    <!-- Header with Green-Emerald Gradient -->
    <div class="mb-8 relative overflow-hidden bg-gradient-to-r from-green-500 via-emerald-600 to-teal-600 rounded-3xl shadow-2xl p-8">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-white/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-32 h-32 bg-white/10 rounded-full blur-3xl"></div>

        <div class="relative flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.accounting.reports.index') }}"
                   class="p-3 bg-white/20 backdrop-blur-sm hover:bg-white/30 rounded-2xl transition-all hover:scale-110 group">
                    <svg class="w-6 h-6 text-white group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <div>
                    <h1 class="text-4xl font-bold text-white mb-2 flex items-center gap-3">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <span data-translate>รายงานกำไร-ขาดทุน</span>
                    </h1>
                    <p class="text-green-50 text-lg" data-translate>Profit & Loss Statement</p>
                </div>
            </div>

            <!-- Language Switcher -->
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open"
                        class="flex items-center gap-2 px-4 py-2 bg-white/20 backdrop-blur-sm hover:bg-white/30 rounded-xl text-white transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                    </svg>
                    <span class="font-medium">ภาษา</span>
                    <svg class="w-4 h-4" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                     class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-2xl overflow-hidden z-50 border-2 border-green-200 dark:border-green-900"
                     style="display: none;">
                    <button class="w-full text-left px-4 py-3 hover:bg-green-50 dark:hover:bg-gray-700 transition flex items-center gap-2 text-gray-700 dark:text-gray-300">
                        <span class="text-xl">🇹🇭</span>
                        <span>ไทย</span>
                    </button>
                    <button class="w-full text-left px-4 py-3 hover:bg-green-50 dark:hover:bg-gray-700 transition flex items-center gap-2 text-gray-700 dark:text-gray-300">
                        <span class="text-xl">🇬🇧</span>
                        <span>English</span>
                    </button>
                    <button class="w-full text-left px-4 py-3 hover:bg-green-50 dark:hover:bg-gray-700 transition flex items-center gap-2 text-gray-700 dark:text-gray-300">
                        <span class="text-xl">🇨🇳</span>
                        <span>中文</span>
                    </button>
                    <button class="w-full text-left px-4 py-3 hover:bg-green-50 dark:hover:bg-gray-700 transition flex items-center gap-2 text-gray-700 dark:text-gray-300">
                        <span class="text-xl">🇯🇵</span>
                        <span>日本語</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Filter Form -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-8 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-3 mb-4">
            <div class="p-2 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white" data-translate>เลือกช่วงเวลา</h3>
        </div>

        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" data-translate>ช่วงเวลา</label>
                <select name="period" class="w-full rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition">
                    <option value="this_month" {{ request('period') === 'this_month' ? 'selected' : '' }} data-translate>เดือนนี้</option>
                    <option value="last_month" {{ request('period') === 'last_month' ? 'selected' : '' }} data-translate>เดือนที่แล้ว</option>
                    <option value="this_quarter" {{ request('period') === 'this_quarter' ? 'selected' : '' }} data-translate>ไตรมาสนี้</option>
                    <option value="this_year" {{ request('period') === 'this_year' ? 'selected' : '' }} data-translate>ปีนี้</option>
                    <option value="custom" {{ request('period') === 'custom' ? 'selected' : '' }} data-translate>กำหนดเอง</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" data-translate>จากวันที่</label>
                <input type="date" name="from_date" value="{{ request('from_date', now()->startOfMonth()->format('Y-m-d')) }}"
                       class="w-full rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" data-translate>ถึงวันที่</label>
                <input type="date" name="to_date" value="{{ request('to_date', now()->format('Y-m-d')) }}"
                       class="w-full rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition">
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-semibold rounded-xl transition-all hover:shadow-lg flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <span data-translate>ค้นหา</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Report -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-200 dark:border-gray-700">
        <!-- Report Header -->
        <div class="p-8 bg-gradient-to-r from-green-500/10 to-emerald-500/10 dark:from-green-900/20 dark:to-emerald-900/20 border-b-2 border-green-200 dark:border-green-800">
            <div class="text-center">
                <div class="inline-flex items-center gap-3 mb-3">
                    <div class="p-3 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-2" data-translate>รายงานกำไร-ขาดทุน</h2>
                <p class="text-gray-600 dark:text-gray-400">
                    <span data-translate>ระหว่างวันที่</span>
                    <span class="font-semibold">{{ request('from_date', now()->startOfMonth()->format('d/m/Y')) }}</span>
                    <span data-translate>ถึง</span>
                    <span class="font-semibold">{{ request('to_date', now()->format('d/m/Y')) }}</span>
                </p>
            </div>
        </div>

        <div class="p-8">
            <!-- Revenue Section -->
            <div class="mb-8 bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl p-6 border-2 border-green-200 dark:border-green-800">
                <h3 class="text-xl font-bold text-green-900 dark:text-green-100 mb-4 pb-3 border-b-2 border-green-300 dark:border-green-700 flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span data-translate>รายได้</span>
                </h3>
                <div class="space-y-3 ml-4">
                    <div class="flex justify-between items-center text-gray-700 dark:text-gray-300">
                        <span class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"/>
                            </svg>
                            <span data-translate>รายได้จากการขาย</span>
                        </span>
                        <span class="font-semibold">฿{{ number_format($report['sales_revenue'] ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center text-gray-700 dark:text-gray-300">
                        <span class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"/>
                            </svg>
                            <span data-translate>รายได้อื่นๆ</span>
                        </span>
                        <span class="font-semibold">฿{{ number_format($report['other_revenue'] ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center font-bold text-gray-900 dark:text-white border-t-2 border-green-300 dark:border-green-700 pt-3 mt-2">
                        <span data-translate>รายได้รวม</span>
                        <span class="text-xl text-green-600 dark:text-green-400">฿{{ number_format($report['total_revenue'] ?? 0, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Cost of Goods Sold -->
            <div class="mb-8 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl p-6 border-2 border-blue-200 dark:border-blue-800">
                <h3 class="text-xl font-bold text-blue-900 dark:text-blue-100 mb-4 pb-3 border-b-2 border-blue-300 dark:border-blue-700 flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    <span data-translate>ต้นทุนขาย</span>
                </h3>
                <div class="space-y-3 ml-4">
                    <div class="flex justify-between items-center text-gray-700 dark:text-gray-300">
                        <span class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <span data-translate>ต้นทุนสินค้า/บริการ</span>
                        </span>
                        <span class="font-semibold text-blue-600 dark:text-blue-400">฿{{ number_format($report['cost_of_goods'] ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center font-bold text-gray-900 dark:text-white border-t-2 border-blue-300 dark:border-blue-700 pt-3 mt-2">
                        <span data-translate>กำไรขั้นต้น</span>
                        <span class="text-xl text-green-600 dark:text-green-400">฿{{ number_format($report['gross_profit'] ?? 0, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Operating Expenses -->
            <div class="mb-8 bg-gradient-to-br from-orange-50 to-red-50 dark:from-orange-900/20 dark:to-red-900/20 rounded-xl p-6 border-2 border-orange-200 dark:border-orange-800">
                <h3 class="text-xl font-bold text-orange-900 dark:text-orange-100 mb-4 pb-3 border-b-2 border-orange-300 dark:border-orange-700 flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z"/>
                    </svg>
                    <span data-translate>ค่าใช้จ่ายในการดำเนินงาน</span>
                </h3>
                <div class="space-y-3 ml-4">
                    <div class="flex justify-between items-center text-gray-700 dark:text-gray-300">
                        <span class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <span data-translate>ค่าเช่า</span>
                        </span>
                        <span class="font-semibold">฿{{ number_format($report['rent_expense'] ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center text-gray-700 dark:text-gray-300">
                        <span class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <span data-translate>เงินเดือนและค่าจ้าง</span>
                        </span>
                        <span class="font-semibold">฿{{ number_format($report['salary_expense'] ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center text-gray-700 dark:text-gray-300">
                        <span class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <span data-translate>ค่าสาธารณูปโภค</span>
                        </span>
                        <span class="font-semibold">฿{{ number_format($report['utilities_expense'] ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center text-gray-700 dark:text-gray-300">
                        <span class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <span data-translate>ค่าใช้จ่ายอื่นๆ</span>
                        </span>
                        <span class="font-semibold">฿{{ number_format($report['other_expenses'] ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center font-bold text-gray-900 dark:text-white border-t-2 border-orange-300 dark:border-orange-700 pt-3 mt-2">
                        <span data-translate>รวมค่าใช้จ่าย</span>
                        <span class="text-xl text-orange-600 dark:text-orange-400">฿{{ number_format($report['total_expenses'] ?? 0, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Net Income -->
            <div class="relative overflow-hidden bg-gradient-to-r {{ ($report['net_income'] ?? 0) >= 0 ? 'from-green-500 to-emerald-600' : 'from-red-500 to-rose-600' }} rounded-2xl p-8 shadow-2xl">
                <div class="absolute top-0 right-0 -mt-8 -mr-8 w-32 h-32 bg-white/10 rounded-full blur-3xl"></div>
                <div class="relative">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div class="flex items-center gap-3">
                            <div class="p-3 bg-white/20 backdrop-blur-sm rounded-xl">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-white/80 text-sm mb-1" data-translate>กำไร(ขาดทุน)สุทธิ</p>
                                <p class="text-4xl font-bold text-white">
                                    ฿{{ number_format($report['net_income'] ?? 0, 2) }}
                                </p>
                            </div>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4">
                            <p class="text-white/80 text-sm mb-1" data-translate>อัตรากำไรสุทธิ</p>
                            <p class="text-2xl font-bold text-white">{{ number_format($report['profit_margin'] ?? 0, 2) }}%</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="p-6 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 border-t-2 border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span data-translate>สร้างรายงานเมื่อ:</span>
                <span class="font-semibold">{{ now()->format('d/m/Y H:i:s') }}</span>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="mt-8 flex flex-wrap gap-4 justify-end">
        <button onclick="window.print()"
                class="inline-flex items-center gap-2 px-6 py-3 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 hover:border-green-500 dark:hover:border-green-500 text-gray-700 dark:text-gray-300 font-semibold rounded-xl transition-all hover:shadow-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            <span data-translate>พิมพ์</span>
        </button>
        <button onclick="exportPDF()"
                class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-red-500 to-rose-600 hover:from-red-600 hover:to-rose-700 text-white font-semibold rounded-xl transition-all hover:shadow-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
            </svg>
            <span data-translate>ส่งออก PDF</span>
        </button>
    </div>
</div>

@push('scripts')
<script>
function exportPDF() {
    alert('ฟังก์ชันส่งออก PDF จะถูกพัฒนาในอนาคต');
}
</script>
@endpush
@endsection
