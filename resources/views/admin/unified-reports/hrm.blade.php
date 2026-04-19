{{-- รายงานบุคลากร (HRM) --}}
@extends('layouts.admin-v3')
@section('title', $pageTitle ?? 'รายงานบุคลากร')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="mb-8 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <nav class="flex mb-3 text-sm">
                    <a href="{{ route('admin.unified-reports.index') }}" class="text-gray-500 hover:text-indigo-600 dark:text-gray-400">ศูนย์รายงาน</a>
                    <span class="mx-2 text-gray-400">/</span>
                    <span class="text-gray-900 dark:text-white font-medium">บุคลากร</span>
                </nav>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                    <div class="p-2 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    {{ $pageTitle }}
                </h1>
            </div>
            <a href="{{ route('admin.unified-reports.export', ['type' => 'hrm', 'period' => $period]) }}"
               class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Export Excel
            </a>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">พนักงานทั้งหมด</p>
                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ number_format($report['summary']['total_employees'] ?? 0) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">คนทำงานอยู่</p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">พนักงานใหม่</p>
                    <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-3xl font-bold text-green-600 dark:text-green-400 mt-2">{{ number_format($report['summary']['new_employees'] ?? 0) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ในช่วงเวลานี้</p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">ลาออก</p>
                    <div class="p-2 bg-red-100 dark:bg-red-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zM21 12h-6"/>
                        </svg>
                    </div>
                </div>
                <p class="text-3xl font-bold text-red-600 dark:text-red-400 mt-2">{{ number_format($report['summary']['left_employees'] ?? 0) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ในช่วงเวลานี้</p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">เปลี่ยนแปลงสุทธิ</p>
                    <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                </div>
                @php $netChange = ($report['summary']['net_change'] ?? 0); @endphp
                <p class="text-3xl font-bold {{ $netChange >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }} mt-2">
                    {{ $netChange >= 0 ? '+' : '' }}{{ number_format($netChange) }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">เข้า - ออก</p>
            </div>
        </div>

        {{-- Details --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Departments --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    พนักงานตามแผนก
                </h3>
                <div class="space-y-3 max-h-80 overflow-y-auto">
                    @forelse(($report['departments'] ?? []) as $dept)
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <span class="text-gray-700 dark:text-gray-300">{{ $dept['name'] ?? 'N/A' }}</span>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($dept['count'] ?? 0) }} คน</span>
                    </div>
                    @empty
                    <p class="text-gray-500 dark:text-gray-400 text-center py-4">ไม่มีข้อมูลแผนก</p>
                    @endforelse
                </div>
            </div>

            {{-- Attendance --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                    สถิติการเข้างาน
                </h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                        <span class="text-green-700 dark:text-green-400">เข้างาน</span>
                        <span class="font-semibold text-green-700 dark:text-green-400">{{ number_format($report['attendance']['present'] ?? 0) }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                        <span class="text-red-700 dark:text-red-400">ขาดงาน</span>
                        <span class="font-semibold text-red-700 dark:text-red-400">{{ number_format($report['attendance']['absent'] ?? 0) }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                        <span class="text-yellow-700 dark:text-yellow-400">มาสาย</span>
                        <span class="font-semibold text-yellow-700 dark:text-yellow-400">{{ number_format($report['attendance']['late'] ?? 0) }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <span class="text-blue-700 dark:text-blue-400">ลา</span>
                        <span class="font-semibold text-blue-700 dark:text-blue-400">{{ number_format($report['attendance']['leave'] ?? 0) }}</span>
                    </div>
                    <div class="mt-4 p-4 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl text-white text-center">
                        <p class="text-sm opacity-90">อัตราการเข้างาน</p>
                        <p class="text-3xl font-bold">{{ $report['attendance']['attendance_rate'] ?? 0 }}%</p>
                    </div>
                </div>
            </div>

            {{-- Payroll --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700 lg:col-span-2">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    สถิติเงินเดือน
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-xl text-center">
                        <p class="text-sm text-gray-500 dark:text-gray-400">เงินเดือนรวม</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">฿{{ number_format($report['payroll']['total_payroll'] ?? 0, 2) }}</p>
                    </div>
                    <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-xl text-center">
                        <p class="text-sm text-gray-500 dark:text-gray-400">จำนวนพนักงาน</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($report['payroll']['employee_count'] ?? 0) }} คน</p>
                    </div>
                    <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-xl text-center">
                        <p class="text-sm text-gray-500 dark:text-gray-400">เฉลี่ย/คน</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">฿{{ number_format($report['payroll']['average_salary'] ?? 0, 2) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
