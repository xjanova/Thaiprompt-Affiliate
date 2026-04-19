{{-- รายงาน E-commerce --}}
@extends('layouts.admin-v3')
@section('title', $pageTitle ?? 'รายงาน E-commerce')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="mb-8 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <nav class="flex mb-3 text-sm">
                    <a href="{{ route('admin.unified-reports.index') }}" class="text-gray-500 hover:text-indigo-600 dark:text-gray-400">ศูนย์รายงาน</a>
                    <span class="mx-2 text-gray-400">/</span>
                    <span class="text-gray-900 dark:text-white font-medium">E-commerce</span>
                </nav>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                    <div class="p-2 bg-gradient-to-br from-orange-500 to-red-600 rounded-xl">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    {{ $pageTitle }}
                </h1>
            </div>
            <a href="{{ route('admin.unified-reports.export', ['type' => 'ecommerce', 'period' => $period]) }}"
               class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export Excel
            </a>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">คำสั่งซื้อทั้งหมด</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ number_format($report['summary']['total_orders'] ?? 0) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">คำสั่งซื้อสำเร็จ</p>
                <p class="text-3xl font-bold text-green-600 dark:text-green-400 mt-2">{{ number_format($report['summary']['completed_orders'] ?? 0) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">รายได้รวม</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">฿{{ number_format($report['summary']['total_revenue'] ?? 0, 2) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">มูลค่าเฉลี่ย/คำสั่งซื้อ</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">฿{{ number_format($report['summary']['average_order_value'] ?? 0, 2) }}</p>
            </div>
        </div>

        {{-- Charts and Tables --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Top Products --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">สินค้าขายดี</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead><tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">#</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">สินค้า</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ขายได้</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">รายได้</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse(($report['top_products'] ?? []) as $i => $product)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $i + 1 }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $product['name'] ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-white">{{ number_format($product['total_sold'] ?? 0) }}</td>
                                <td class="px-4 py-3 text-sm text-right text-green-600 dark:text-green-400">฿{{ number_format($product['total_revenue'] ?? 0, 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="px-4 py-8 text-center text-gray-500">ไม่มีข้อมูล</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Order Status Distribution --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">สถานะคำสั่งซื้อ</h3>
                <div class="space-y-3">
                    @forelse(($report['order_status_distribution'] ?? []) as $status)
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <span class="text-gray-700 dark:text-gray-300 capitalize">{{ $status['status'] ?? 'N/A' }}</span>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($status['count'] ?? 0) }} รายการ</span>
                    </div>
                    @empty
                    <p class="text-gray-500 text-center py-4">ไม่มีข้อมูล</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
