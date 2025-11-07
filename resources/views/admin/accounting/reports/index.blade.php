@extends('layouts.admin')

@section('title', 'รายงาน')

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">รายงาน</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">รายงานทางการเงินและบัญชี</p>
    </div>

    <!-- Report Categories -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Financial Reports -->
        <a href="{{ route('admin.accounting.reports.profit-loss') }}"
           class="bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-lg transition-all p-6 group">
            <div class="flex items-start">
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="fas fa-chart-line text-2xl text-green-600"></i>
                </div>
                <div class="ml-4 flex-1">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">รายงานกำไร-ขาดทุน</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">แสดงรายได้และค่าใช้จ่าย</p>
                </div>
                <i class="fas fa-chevron-right text-gray-400 group-hover:translate-x-1 transition-transform"></i>
            </div>
        </a>

        <a href="{{ route('admin.accounting.reports.balance-sheet') }}"
           class="bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-lg transition-all p-6 group">
            <div class="flex items-start">
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="fas fa-balance-scale text-2xl text-blue-600"></i>
                </div>
                <div class="ml-4 flex-1">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">งบดุล</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">สินทรัพย์ หนี้สิน และทุน</p>
                </div>
                <i class="fas fa-chevron-right text-gray-400 group-hover:translate-x-1 transition-transform"></i>
            </div>
        </a>

        <a href="{{ route('admin.accounting.reports.cash-flow') }}"
           class="bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-lg transition-all p-6 group">
            <div class="flex items-start">
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="fas fa-exchange-alt text-2xl text-purple-600"></i>
                </div>
                <div class="ml-4 flex-1">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">กระแสเงินสด</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">การไหลเข้า-ออกของเงินสด</p>
                </div>
                <i class="fas fa-chevron-right text-gray-400 group-hover:translate-x-1 transition-transform"></i>
            </div>
        </a>

        <a href="{{ route('admin.accounting.reports.sales') }}"
           class="bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-lg transition-all p-6 group">
            <div class="flex items-start">
                <div class="w-12 h-12 bg-emerald-100 dark:bg-emerald-900 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="fas fa-dollar-sign text-2xl text-emerald-600"></i>
                </div>
                <div class="ml-4 flex-1">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">รายงานยอดขาย</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">สรุปยอดขายและลูกค้า</p>
                </div>
                <i class="fas fa-chevron-right text-gray-400 group-hover:translate-x-1 transition-transform"></i>
            </div>
        </a>

        <a href="{{ route('admin.accounting.reports.expenses') }}"
           class="bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-lg transition-all p-6 group">
            <div class="flex items-start">
                <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="fas fa-receipt text-2xl text-orange-600"></i>
                </div>
                <div class="ml-4 flex-1">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">รายงานค่าใช้จ่าย</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">สรุปค่าใช้จ่ายทั้งหมด</p>
                </div>
                <i class="fas fa-chevron-right text-gray-400 group-hover:translate-x-1 transition-transform"></i>
            </div>
        </a>

        <a href="{{ route('admin.accounting.reports.tax') }}"
           class="bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-lg transition-all p-6 group">
            <div class="flex items-start">
                <div class="w-12 h-12 bg-red-100 dark:bg-red-900 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="fas fa-file-invoice-dollar text-2xl text-red-600"></i>
                </div>
                <div class="ml-4 flex-1">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">รายงานภาษี</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ภาษีขาย ภาษีซื้อ และภงด.</p>
                </div>
                <i class="fas fa-chevron-right text-gray-400 group-hover:translate-x-1 transition-transform"></i>
            </div>
        </a>
    </div>

    <!-- Quick Stats -->
    <div class="mt-8">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">ภาพรวมเดือนนี้</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-gray-600 dark:text-gray-400 text-sm">รายได้</div>
                <div class="text-2xl font-bold text-green-600">฿{{ number_format($stats['revenue'] ?? 0, 2) }}</div>
                <div class="text-xs text-gray-500 mt-1">
                    @if(($stats['revenue_change'] ?? 0) > 0)
                        <i class="fas fa-arrow-up text-green-600"></i> {{ number_format($stats['revenue_change'] ?? 0, 1) }}%
                    @else
                        <i class="fas fa-arrow-down text-red-600"></i> {{ number_format(abs($stats['revenue_change'] ?? 0), 1) }}%
                    @endif
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-gray-600 dark:text-gray-400 text-sm">ค่าใช้จ่าย</div>
                <div class="text-2xl font-bold text-orange-600">฿{{ number_format($stats['expenses'] ?? 0, 2) }}</div>
                <div class="text-xs text-gray-500 mt-1">
                    @if(($stats['expenses_change'] ?? 0) > 0)
                        <i class="fas fa-arrow-up text-red-600"></i> {{ number_format($stats['expenses_change'] ?? 0, 1) }}%
                    @else
                        <i class="fas fa-arrow-down text-green-600"></i> {{ number_format(abs($stats['expenses_change'] ?? 0), 1) }}%
                    @endif
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-gray-600 dark:text-gray-400 text-sm">กำไรสุทธิ</div>
                <div class="text-2xl font-bold {{ ($stats['profit'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    ฿{{ number_format($stats['profit'] ?? 0, 2) }}
                </div>
                <div class="text-xs text-gray-500 mt-1">
                    อัตรากำไร {{ number_format($stats['profit_margin'] ?? 0, 1) }}%
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-gray-600 dark:text-gray-400 text-sm">ลูกหนี้คงค้าง</div>
                <div class="text-2xl font-bold text-red-600">฿{{ number_format($stats['receivables'] ?? 0, 2) }}</div>
                <div class="text-xs text-gray-500 mt-1">
                    {{ number_format($stats['overdue_invoices'] ?? 0) }} รายการเกินกำหนด
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
