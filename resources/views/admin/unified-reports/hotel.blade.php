{{-- รายงานโรงแรม --}}
@extends('layouts.admin-v3')
@section('title', $pageTitle ?? 'รายงานโรงแรม')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-8 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <nav class="flex mb-3 text-sm">
                    <a href="{{ route('admin.unified-reports.index') }}" class="text-gray-500 hover:text-indigo-600 dark:text-gray-400">ศูนย์รายงาน</a>
                    <span class="mx-2 text-gray-400">/</span>
                    <span class="text-gray-900 dark:text-white font-medium">โรงแรม</span>
                </nav>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                    <div class="p-2 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    {{ $pageTitle }}
                </h1>
            </div>
            <a href="{{ route('admin.unified-reports.export', ['type' => 'hotel', 'period' => $period]) }}"
               class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow">Export Excel</a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">การจองทั้งหมด</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ number_format($report['summary']['total_bookings'] ?? 0) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">การจองสำเร็จ</p>
                <p class="text-3xl font-bold text-green-600 dark:text-green-400 mt-2">{{ number_format($report['summary']['completed_bookings'] ?? 0) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">รายได้รวม</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">฿{{ number_format($report['summary']['total_revenue'] ?? 0, 2) }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">รายได้ตามโรงแรม</h3>
                <div class="space-y-3">
                    @forelse(($report['revenue_by_hotel'] ?? []) as $hotel)
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <span class="text-gray-700 dark:text-gray-300">{{ $hotel['hotel']['name'] ?? 'N/A' }}</span>
                        <span class="font-semibold text-green-600 dark:text-green-400">฿{{ number_format($hotel['total_revenue'] ?? 0, 2) }}</span>
                    </div>
                    @empty
                    <p class="text-gray-500 text-center py-4">ไม่มีข้อมูล</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">สถานะการจอง</h3>
                <div class="space-y-3">
                    @forelse(($report['booking_status'] ?? []) as $status)
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
