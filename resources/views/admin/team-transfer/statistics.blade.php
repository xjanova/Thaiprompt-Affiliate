@extends('layouts.admin')

@section('title', 'สถิติการย้ายทีม')

@section('content')
<div class="container-fluid px-4 py-6">
    {{-- Header --}}
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                📊 สถิติการย้ายทีม
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                วิเคราะห์ข้อมูลและแนวโน้มการย้ายทีม
            </p>
        </div>

        <a href="{{ route('admin.team-transfer.index') }}"
           class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-lg transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            กลับไปรายการคำขอ
        </a>
    </div>

    {{-- Date Filter --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6 border border-gray-200 dark:border-gray-700">
        <form method="GET" action="{{ route('admin.team-transfer.statistics') }}" class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label for="start_date" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    วันที่เริ่มต้น
                </label>
                <input type="date"
                       id="start_date"
                       name="start_date"
                       value="{{ $startDate }}"
                       class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:text-white">
            </div>

            <div class="flex-1 min-w-[200px]">
                <label for="end_date" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    วันที่สิ้นสุด
                </label>
                <input type="date"
                       id="end_date"
                       name="end_date"
                       value="{{ $endDate }}"
                       class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:text-white">
            </div>

            <button type="submit"
                    class="px-6 py-2 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold rounded-lg shadow-lg transition">
                ค้นหา
            </button>
        </form>
    </div>

    <div class="grid lg:grid-cols-2 gap-6 mb-6">
        {{-- By Status --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">สถิติตามสถานะ</h2>

            @if($byStatus->count() > 0)
                <div class="space-y-4">
                    @foreach($byStatus as $stat)
                        @php
                            $total = $byStatus->sum('count');
                            $percentage = $total > 0 ? ($stat->count / $total) * 100 : 0;
                            $statusLabel = match($stat->status) {
                                'pending' => 'รออนุมัติ',
                                'approved' => 'อนุมัติแล้ว',
                                'rejected' => 'ปฏิเสธ',
                                'paid' => 'ชำระเงินแล้ว',
                                'processing' => 'กำลังดำเนินการ',
                                'completed' => 'เสร็จสิ้น',
                                'cancelled' => 'ยกเลิก',
                                default => $stat->status
                            };
                            $statusColor = match($stat->status) {
                                'pending' => 'bg-yellow-500',
                                'approved' => 'bg-green-500',
                                'rejected' => 'bg-red-500',
                                'paid' => 'bg-blue-500',
                                'processing' => 'bg-purple-500',
                                'completed' => 'bg-emerald-500',
                                'cancelled' => 'bg-gray-500',
                                default => 'bg-gray-500'
                            };
                        @endphp

                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ $statusLabel }}
                                </span>
                                <span class="text-sm font-bold text-gray-900 dark:text-white">
                                    {{ number_format($stat->count) }} ({{ number_format($percentage, 1) }}%)
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                                <div class="{{ $statusColor }} h-3 rounded-full transition-all duration-500"
                                     style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-bold text-gray-700 dark:text-gray-300">รวมทั้งหมด</span>
                        <span class="text-lg font-bold text-gray-900 dark:text-white">
                            {{ number_format($byStatus->sum('count')) }} คำขอ
                        </span>
                    </div>
                </div>
            @else
                <div class="text-center py-12">
                    <svg class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400">ไม่มีข้อมูลในช่วงเวลานี้</p>
                </div>
            @endif
        </div>

        {{-- Daily Chart --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">สถิติรายวัน</h2>

            @if($byDate->count() > 0)
                <div class="space-y-3">
                    @foreach($byDate as $stat)
                        @php
                            $maxCount = $byDate->max('count');
                            $percentage = $maxCount > 0 ? ($stat->count / $maxCount) * 100 : 0;
                            $date = \Carbon\Carbon::parse($stat->date);
                        @endphp

                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ $date->locale('th')->isoFormat('D MMM') }}
                                </span>
                                <span class="text-sm font-bold text-gray-900 dark:text-white">
                                    {{ number_format($stat->count) }}
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="bg-gradient-to-r from-blue-500 to-purple-600 h-2 rounded-full transition-all duration-500"
                                     style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <div class="grid grid-cols-2 gap-4 text-center">
                        <div>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">เฉลี่ยต่อวัน</p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">
                                {{ number_format($byDate->avg('count'), 1) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">สูงสุดต่อวัน</p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">
                                {{ number_format($byDate->max('count')) }}
                            </p>
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center py-12">
                    <svg class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400">ไม่มีข้อมูลในช่วงเวลานี้</p>
                </div>
            @endif
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        {{-- Top Losing Sponsors --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">แม่ทีมที่มีลูกทีมย้ายออกมากที่สุด</h2>
                <div class="px-3 py-1 bg-red-100 dark:bg-red-900/30 rounded-full">
                    <span class="text-xs font-bold text-red-700 dark:text-red-300">📉 Losing</span>
                </div>
            </div>

            @if($topLosing->count() > 0)
                <div class="space-y-4">
                    @foreach($topLosing as $index => $sponsor)
                        <div class="flex items-center gap-4 p-4 bg-gradient-to-r from-red-50 to-transparent dark:from-red-900/10 dark:to-transparent rounded-lg border border-red-100 dark:border-red-900/30 hover:shadow-md transition">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-gradient-to-br from-red-500 to-red-600 text-white rounded-full flex items-center justify-center font-bold text-lg">
                                    {{ $index + 1 }}
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-gray-900 dark:text-white truncate">
                                    {{ $sponsor->oldSponsor->user->name ?? 'N/A' }}
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $sponsor->oldSponsor->member_code ?? 'N/A' }}
                                </p>
                            </div>
                            <div class="flex-shrink-0 text-right">
                                <p class="text-2xl font-bold text-red-600 dark:text-red-400">
                                    {{ number_format($sponsor->count) }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">คำขอ</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <svg class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400">ไม่มีข้อมูลในช่วงเวลานี้</p>
                </div>
            @endif
        </div>

        {{-- Top Gaining Sponsors --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">แม่ทีมที่มีลูกทีมย้ายเข้ามากที่สุด</h2>
                <div class="px-3 py-1 bg-green-100 dark:bg-green-900/30 rounded-full">
                    <span class="text-xs font-bold text-green-700 dark:text-green-300">📈 Gaining</span>
                </div>
            </div>

            @if($topGaining->count() > 0)
                <div class="space-y-4">
                    @foreach($topGaining as $index => $sponsor)
                        <div class="flex items-center gap-4 p-4 bg-gradient-to-r from-green-50 to-transparent dark:from-green-900/10 dark:to-transparent rounded-lg border border-green-100 dark:border-green-900/30 hover:shadow-md transition">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-green-600 text-white rounded-full flex items-center justify-center font-bold text-lg">
                                    {{ $index + 1 }}
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-gray-900 dark:text-white truncate">
                                    {{ $sponsor->newSponsor->user->name ?? 'N/A' }}
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $sponsor->newSponsor->member_code ?? 'N/A' }}
                                </p>
                            </div>
                            <div class="flex-shrink-0 text-right">
                                <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                                    {{ number_format($sponsor->count) }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">คำขอ</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <svg class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400">ไม่มีข้อมูลในช่วงเวลานี้</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Insights --}}
    @if($byStatus->count() > 0 || $byDate->count() > 0)
        <div class="mt-6 bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 rounded-xl p-6 border border-blue-200 dark:border-blue-800">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">💡 Insights</h3>
            <div class="grid md:grid-cols-3 gap-4">
                @if($byStatus->where('status', 'completed')->first())
                    <div class="bg-white/50 dark:bg-gray-800/50 rounded-lg p-4">
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">อัตราสำเร็จ</p>
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                            {{ number_format(($byStatus->where('status', 'completed')->first()->count / $byStatus->sum('count')) * 100, 1) }}%
                        </p>
                    </div>
                @endif

                @if($byStatus->where('status', 'rejected')->first())
                    <div class="bg-white/50 dark:bg-gray-800/50 rounded-lg p-4">
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">อัตราปฏิเสธ</p>
                        <p class="text-2xl font-bold text-red-600 dark:text-red-400">
                            {{ number_format(($byStatus->where('status', 'rejected')->first()->count / $byStatus->sum('count')) * 100, 1) }}%
                        </p>
                    </div>
                @endif

                @if($byStatus->where('status', 'pending')->first())
                    <div class="bg-white/50 dark:bg-gray-800/50 rounded-lg p-4">
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">รออนุมัติ</p>
                        <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                            {{ number_format($byStatus->where('status', 'pending')->first()->count) }}
                        </p>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection
