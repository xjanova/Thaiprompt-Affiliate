{{-- รายงาน POS --}}
@extends('layouts.admin-v3')
@section('title', $pageTitle ?? 'รายงาน POS')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-8 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <nav class="flex mb-3 text-sm">
                    <a href="{{ route('admin.unified-reports.index') }}" class="text-gray-500 hover:text-indigo-600 dark:text-gray-400">ศูนย์รายงาน</a>
                    <span class="mx-2 text-gray-400">/</span>
                    <span class="text-gray-900 dark:text-white font-medium">POS</span>
                </nav>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                    <div class="p-2 bg-gradient-to-br from-orange-500 to-amber-600 rounded-xl">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    {{ $pageTitle }}
                </h1>
            </div>
            <a href="{{ route('admin.unified-reports.export', ['type' => 'pos', 'period' => $period]) }}"
               class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow">Export Excel</a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Transaction ทั้งหมด</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ number_format($report['summary']['total_transactions'] ?? 0) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">รายได้รวม</p>
                <p class="text-3xl font-bold text-green-600 dark:text-green-400 mt-2">฿{{ number_format($report['summary']['total_revenue'] ?? 0, 2) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">เฉลี่ย/Transaction</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">฿{{ number_format($report['summary']['average_transaction'] ?? 0, 2) }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ชั่วโมงขายดี</h3>
                <div class="space-y-2 max-h-80 overflow-y-auto">
                    @forelse(($report['peak_hours'] ?? []) as $hour)
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <span class="text-gray-700 dark:text-gray-300">{{ sprintf('%02d:00', $hour['hour'] ?? 0) }}</span>
                        <div class="text-right">
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $hour['transactions'] ?? 0 }} tx</span>
                            <span class="text-green-600 dark:text-green-400 text-sm ml-2">฿{{ number_format($hour['revenue'] ?? 0, 0) }}</span>
                        </div>
                    </div>
                    @empty
                    <p class="text-gray-500 text-center py-4">ไม่มีข้อมูล</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">วิธีชำระเงิน</h3>
                <div class="space-y-3">
                    @forelse(($report['payment_methods'] ?? []) as $method)
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <span class="text-gray-700 dark:text-gray-300 capitalize">{{ $method['payment_method'] ?? 'N/A' }}</span>
                        <div class="text-right">
                            <span class="font-semibold text-gray-900 dark:text-white">฿{{ number_format($method['total'] ?? 0, 2) }}</span>
                            <span class="text-gray-500 text-sm ml-2">({{ $method['count'] ?? 0 }})</span>
                        </div>
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
