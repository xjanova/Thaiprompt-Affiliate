{{--
    หน้ารายได้ของผู้ให้บริการ
    แสดงยอดรายได้, สถิติ, และรายการงานที่เสร็จสิ้น
--}}
@extends('layouts.app')

@section('title', $pageTitle ?? 'รายได้ของฉัน')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{-- Header --}}
    <div class="mb-6 p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 shadow-xl">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-green-600 to-emerald-600 dark:from-green-400 dark:to-emerald-400 bg-clip-text text-transparent">
                    <i class="fas fa-wallet mr-3"></i>{{ $pageTitle ?? 'รายได้ของฉัน' }}
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">ติดตามรายได้และสถิติการทำงานของคุณ</p>
            </div>
            @if(isset($completedBookings) && $completedBookings->count() > 0)
                <a href="{{ route('provider.earnings.export', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                    <i class="fas fa-download mr-2"></i>ดาวน์โหลดรายงาน
                </a>
            @endif
        </div>
    </div>

    {{-- Date Filter --}}
    <div class="mb-6 p-4 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 shadow-xl">
        <form action="{{ route('provider.earnings.index') }}" method="GET" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">วันที่เริ่มต้น</label>
                <input type="date" name="start_date" value="{{ $startDate }}" class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">วันที่สิ้นสุด</label>
                <input type="date" name="end_date" value="{{ $endDate }}" class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                <i class="fas fa-filter mr-2"></i>กรอง
            </button>
        </form>
    </div>

    {{-- Total Earnings --}}
    <div class="mb-6 p-8 rounded-2xl bg-gradient-to-br from-green-500 to-emerald-600 text-white shadow-xl">
        <div class="text-center">
            <p class="text-lg opacity-80 mb-2">รายได้รวมในช่วงเวลาที่เลือก</p>
            <p class="text-5xl font-bold">฿{{ number_format($totalEarnings ?? 0, 2) }}</p>
            <p class="mt-2 opacity-70">{{ $startDate }} - {{ $endDate }}</p>
        </div>
    </div>

    {{-- Monthly Stats --}}
    @if(isset($monthlyStats) && count($monthlyStats) > 0)
        <div class="mb-6 p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 shadow-xl">
            <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-white">
                <i class="fas fa-chart-bar mr-2 text-blue-500"></i>สถิติรายเดือน (ปีนี้)
            </h2>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                @php
                    $thaiMonths = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
                @endphp
                @foreach($monthlyStats as $month)
                    <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50 text-center">
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $thaiMonths[$month->month] ?? $month->month }}</p>
                        <p class="text-lg font-bold text-green-600 dark:text-green-400">฿{{ number_format($month->total ?? 0) }}</p>
                        <p class="text-xs text-gray-500">{{ $month->jobs ?? 0 }} งาน</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Daily Earnings --}}
    @if(isset($dailyEarnings) && count($dailyEarnings) > 0)
        <div class="mb-6 p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 shadow-xl">
            <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-white">
                <i class="fas fa-calendar-day mr-2 text-green-500"></i>รายได้รายวัน
            </h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">วันที่</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">จำนวนงาน</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">รายได้</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($dailyEarnings as $day)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($day->date)->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-sm text-center text-gray-600 dark:text-gray-400">{{ $day->jobs }} งาน</td>
                                <td class="px-4 py-3 text-sm text-right font-semibold text-green-600 dark:text-green-400">฿{{ number_format($day->total ?? 0, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Completed Bookings --}}
    <div class="p-6 rounded-2xl backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 shadow-xl">
        <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-white">
            <i class="fas fa-list-check mr-2 text-blue-500"></i>รายการงานที่เสร็จสิ้น
        </h2>
        @if(isset($completedBookings) && $completedBookings->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">เลขที่จอง</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">บริการ</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">วันที่เสร็จ</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">รายได้</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($completedBookings as $booking)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-3">
                                    <span class="font-mono text-sm text-gray-900 dark:text-white">{{ $booking->booking_number }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $booking->service->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $booking->completed_at ? $booking->completed_at->format('d/m/Y H:i') : '-' }}</td>
                                <td class="px-4 py-3 text-sm text-right font-semibold text-green-600 dark:text-green-400">฿{{ number_format($booking->provider_earnings ?? 0, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                {{ $completedBookings->withQueryString()->links() }}
            </div>
        @else
            <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                <i class="fas fa-inbox text-4xl mb-3"></i>
                <p>ไม่มีงานที่เสร็จสิ้นในช่วงเวลานี้</p>
            </div>
        @endif
    </div>
</div>
@endsection
