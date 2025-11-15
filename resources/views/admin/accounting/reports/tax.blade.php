@extends('layouts.admin')

@section('title', 'รายงานภาษี')

@section('content')
<div class="p-6">
    <!-- Header with Indigo-Purple Gradient -->
    <div class="mb-8 relative overflow-hidden bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 rounded-3xl shadow-2xl p-8">
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span data-translate>รายงานภาษี</span>
                    </h1>
                    <p class="text-indigo-50 text-lg" data-translate>ภาษีขาย ภาษีซื้อ และภาษีหัก ณ ที่จ่าย</p>
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
                     class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-2xl overflow-hidden z-50 border-2 border-indigo-200 dark:border-indigo-900"
                     style="display: none;">
                    <button class="w-full text-left px-4 py-3 hover:bg-indigo-50 dark:hover:bg-gray-700 transition flex items-center gap-2 text-gray-700 dark:text-gray-300">
                        <span class="text-xl">🇹🇭</span>
                        <span>ไทย</span>
                    </button>
                    <button class="w-full text-left px-4 py-3 hover:bg-indigo-50 dark:hover:bg-gray-700 transition flex items-center gap-2 text-gray-700 dark:text-gray-300">
                        <span class="text-xl">🇬🇧</span>
                        <span>English</span>
                    </button>
                    <button class="w-full text-left px-4 py-3 hover:bg-indigo-50 dark:hover:bg-gray-700 transition flex items-center gap-2 text-gray-700 dark:text-gray-300">
                        <span class="text-xl">🇨🇳</span>
                        <span>中文</span>
                    </button>
                    <button class="w-full text-left px-4 py-3 hover:bg-indigo-50 dark:hover:bg-gray-700 transition flex items-center gap-2 text-gray-700 dark:text-gray-300">
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
            <div class="p-2 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-lg">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white" data-translate>ตัวกรองข้อมูล</h3>
        </div>

        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" data-translate>ช่วงเวลา</label>
                <select name="period" class="w-full rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition">
                    <option value="this_month" {{ request('period') === 'this_month' ? 'selected' : '' }} data-translate>เดือนนี้</option>
                    <option value="last_month" {{ request('period') === 'last_month' ? 'selected' : '' }} data-translate>เดือนที่แล้ว</option>
                    <option value="this_quarter" {{ request('period') === 'this_quarter' ? 'selected' : '' }} data-translate>ไตรมาสนี้</option>
                    <option value="this_year" {{ request('period') === 'this_year' ? 'selected' : '' }} data-translate>ปีนี้</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" data-translate>จากวันที่</label>
                <input type="date" name="from_date" value="{{ request('from_date', now()->startOfMonth()->format('Y-m-d')) }}"
                       class="w-full rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" data-translate>ถึงวันที่</label>
                <input type="date" name="to_date" value="{{ request('to_date', now()->format('Y-m-d')) }}"
                       class="w-full rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" data-translate>ประเภทภาษี</label>
                <select name="tax_type" class="w-full rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition">
                    <option value="all" {{ request('tax_type') === 'all' ? 'selected' : '' }} data-translate>ทุกประเภท</option>
                    <option value="vat_sales" {{ request('tax_type') === 'vat_sales' ? 'selected' : '' }} data-translate>ภาษีขาย (VAT)</option>
                    <option value="vat_purchase" {{ request('tax_type') === 'vat_purchase' ? 'selected' : '' }} data-translate>ภาษีซื้อ (VAT)</option>
                    <option value="withholding" {{ request('tax_type') === 'withholding' ? 'selected' : '' }} data-translate>ภาษีหัก ณ ที่จ่าย</option>
                </select>
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-semibold rounded-xl transition-all hover:shadow-lg flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <span data-translate>ค้นหา</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Tax Summary Cards with Gradients -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Output VAT Card -->
        <div class="relative overflow-hidden bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-3">
                    <div class="p-3 bg-white/20 backdrop-blur-sm rounded-xl">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-green-100 text-sm mb-1" data-translate>ภาษีขาย (Output VAT)</p>
                <p class="text-3xl font-bold mb-2">฿{{ number_format($taxSummary['output_vat'] ?? 0, 2) }}</p>
                <p class="text-xs text-green-100 opacity-80" data-translate>จากยอดขาย: ฿{{ number_format($taxSummary['total_sales'] ?? 0, 2) }}</p>
            </div>
        </div>

        <!-- Input VAT Card -->
        <div class="relative overflow-hidden bg-gradient-to-br from-orange-500 to-red-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-3">
                    <div class="p-3 bg-white/20 backdrop-blur-sm rounded-xl">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-orange-100 text-sm mb-1" data-translate>ภาษีซื้อ (Input VAT)</p>
                <p class="text-3xl font-bold mb-2">฿{{ number_format($taxSummary['input_vat'] ?? 0, 2) }}</p>
                <p class="text-xs text-orange-100 opacity-80" data-translate>จากยอดซื้อ: ฿{{ number_format($taxSummary['total_purchases'] ?? 0, 2) }}</p>
            </div>
        </div>

        <!-- VAT Payable Card -->
        <div class="relative overflow-hidden bg-gradient-to-br {{ ($taxSummary['vat_payable'] ?? 0) >= 0 ? 'from-red-500 to-rose-600' : 'from-blue-500 to-indigo-600' }} rounded-2xl shadow-xl p-6 text-white">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-3">
                    <div class="p-3 bg-white/20 backdrop-blur-sm rounded-xl">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <p class="{{ ($taxSummary['vat_payable'] ?? 0) >= 0 ? 'text-red-100' : 'text-blue-100' }} text-sm mb-1" data-translate>ภาษีค้างชำระ (VAT Payable)</p>
                <p class="text-3xl font-bold mb-2">
                    ฿{{ number_format(abs($taxSummary['vat_payable'] ?? 0), 2) }}
                </p>
                <p class="text-xs {{ ($taxSummary['vat_payable'] ?? 0) >= 0 ? 'text-red-100' : 'text-blue-100' }} opacity-80" data-translate>
                    {{ ($taxSummary['vat_payable'] ?? 0) >= 0 ? 'ต้องชำระ' : 'ขอคืน' }}
                </p>
            </div>
        </div>
    </div>

    <!-- VAT Report Table -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-200 dark:border-gray-700 mb-8">
        <div class="p-6 bg-gradient-to-r from-green-500/10 to-emerald-500/10 dark:from-green-900/20 dark:to-emerald-900/20 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white" data-translate>รายงานภาษีมูลค่าเพิ่ม (VAT)</h3>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-600">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            <span data-translate>วันที่</span>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            <span data-translate>เลขที่เอกสาร</span>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            <span data-translate>ลูกค้า/ผู้ขาย</span>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            <span data-translate>เลขผู้เสียภาษี</span>
                        </th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            <span data-translate>มูลค่าสินค้า</span>
                        </th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            <span data-translate>VAT 7%</span>
                        </th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            <span data-translate>ยอดรวม</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($vatTransactions ?? [] as $transaction)
                    <tr class="hover:bg-green-50 dark:hover:bg-gray-700/50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                            {{ $transaction->date->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-600 dark:text-blue-400 font-medium">
                            {{ $transaction->document_number }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-300">
                            {{ $transaction->contact_name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-400">
                            {{ $transaction->tax_id ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-gray-300">
                            ฿{{ number_format($transaction->base_amount, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-green-600 dark:text-green-400">
                            ฿{{ number_format($transaction->vat_amount, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-gray-300">
                            ฿{{ number_format($transaction->total_amount, 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <div class="p-4 bg-gray-100 dark:bg-gray-700 rounded-full">
                                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <p class="text-gray-500 dark:text-gray-400 font-medium" data-translate>ไม่พบข้อมูลภาษี</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse

                    @if(isset($vatTransactions) && count($vatTransactions) > 0)
                    <tr class="bg-gradient-to-r from-green-500 to-emerald-600 text-white font-bold">
                        <td colspan="4" class="px-6 py-4 text-sm">
                            <span data-translate>รวมทั้งหมด</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                            ฿{{ number_format($vatTransactions->sum('base_amount'), 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-xl">
                            ฿{{ number_format($vatTransactions->sum('vat_amount'), 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                            ฿{{ number_format($vatTransactions->sum('total_amount'), 2) }}
                        </td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <!-- Withholding Tax Report Table -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-200 dark:border-gray-700">
        <div class="p-6 bg-gradient-to-r from-red-500/10 to-rose-500/10 dark:from-red-900/20 dark:to-rose-900/20 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-gradient-to-br from-red-500 to-rose-600 rounded-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white" data-translate>ภาษีหัก ณ ที่จ่าย (Withholding Tax)</h3>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-600">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            <span data-translate>วันที่</span>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            <span data-translate>ผู้รับเงิน</span>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            <span data-translate>เลขผู้เสียภาษี</span>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            <span data-translate>ประเภท</span>
                        </th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            <span data-translate>ยอดเงิน</span>
                        </th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            <span data-translate>อัตรา</span>
                        </th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            <span data-translate>ภาษีหัก</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($withholdingTax ?? [] as $wht)
                    <tr class="hover:bg-red-50 dark:hover:bg-gray-700/50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                            {{ $wht->date->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-300">
                            {{ $wht->payee_name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-400">
                            {{ $wht->tax_id ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                            {{ $wht->wht_type }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-gray-300">
                            ฿{{ number_format($wht->amount, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-700 dark:text-gray-400">
                            {{ $wht->rate }}%
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-red-600 dark:text-red-400">
                            ฿{{ number_format($wht->wht_amount, 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <div class="p-4 bg-gray-100 dark:bg-gray-700 rounded-full">
                                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z"/>
                                    </svg>
                                </div>
                                <p class="text-gray-500 dark:text-gray-400 font-medium" data-translate>ไม่พบข้อมูลภาษีหัก ณ ที่จ่าย</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse

                    @if(isset($withholdingTax) && count($withholdingTax) > 0)
                    <tr class="bg-gradient-to-r from-red-500 to-rose-600 text-white font-bold">
                        <td colspan="4" class="px-6 py-4 text-sm">
                            <span data-translate>รวมทั้งหมด</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                            ฿{{ number_format($withholdingTax->sum('amount'), 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right">-</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-xl">
                            ฿{{ number_format($withholdingTax->sum('wht_amount'), 2) }}
                        </td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="mt-8 flex flex-wrap gap-4 justify-end">
        <button onclick="window.print()"
                class="inline-flex items-center gap-2 px-6 py-3 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 hover:border-indigo-500 dark:hover:border-indigo-500 text-gray-700 dark:text-gray-300 font-semibold rounded-xl transition-all hover:shadow-lg">
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
