@extends('layouts.user-arrow-x')

@section('title', 'คอมมิชชั่น')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 border border-white/20 dark:border-gray-700/50">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">คอมมิชชั่นของฉัน</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">ดูประวัติคอมมิชชั่นทั้งหมด</p>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 border border-white/20 dark:border-gray-700/50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ทั้งหมด</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $commissions->total() }}</p>
                </div>
                <div class="text-4xl">📊</div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 border border-white/20 dark:border-gray-700/50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">รอดำเนินการ</p>
                    <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400 mt-1">
                        {{ Auth::user()->commissions()->where('status', 'pending')->count() }}
                    </p>
                </div>
                <div class="text-4xl">⏳</div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 border border-white/20 dark:border-gray-700/50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">อนุมัติแล้ว</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">
                        {{ Auth::user()->commissions()->where('status', 'approved')->count() }}
                    </p>
                </div>
                <div class="text-4xl">✅</div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 border border-white/20 dark:border-gray-700/50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">จ่ายแล้ว</p>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">
                        {{ Auth::user()->commissions()->where('status', 'paid')->count() }}
                    </p>
                </div>
                <div class="text-4xl">💰</div>
            </div>
        </div>
    </div>

    <!-- Commissions Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden border border-white/20 dark:border-gray-700/50">
        @if($commissions->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                วันที่
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Order ID
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                ประเภท
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                จำนวน
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                %
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                สถานะ
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                หมายเหตุ
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($commissions as $commission)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ $commission->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $commission->order_id ?? '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded text-xs">
                                        {{ ucfirst($commission->type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    ฿{{ number_format($commission->amount, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $commission->percentage }}%
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                        @if($commission->status === 'pending') bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400
                                        @elseif($commission->status === 'approved') bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400
                                        @elseif($commission->status === 'paid') bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400
                                        @else bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400
                                        @endif">
                                        @if($commission->status === 'pending') รอดำเนินการ
                                        @elseif($commission->status === 'approved') อนุมัติแล้ว
                                        @elseif($commission->status === 'paid') จ่ายแล้ว
                                        @else ปฏิเสธ
                                        @endif
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $commission->notes ?? '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $commissions->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <div class="text-6xl mb-4">📭</div>
                <p class="text-gray-600 dark:text-gray-400 text-lg">ยังไม่มีคอมมิชชั่น</p>
                <p class="text-gray-500 dark:text-gray-500 text-sm mt-2">เมื่อมีคอมมิชชั่นเข้ามาจะแสดงที่นี่</p>
            </div>
        @endif
    </div>
</div>
@endsection
