@extends('layouts.user')

@section('title', 'คอมมิชชั่น')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <!-- Header -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h1 class="text-2xl font-bold text-gray-900">คอมมิชชั่นของฉัน</h1>
        <p class="text-sm text-gray-600 mt-1">ดูประวัติคอมมิชชั่นทั้งหมด</p>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">ทั้งหมด</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $commissions->total() }}</p>
                </div>
                <div class="text-4xl">📊</div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">รอดำเนินการ</p>
                    <p class="text-2xl font-bold text-yellow-600 mt-1">
                        {{ Auth::user()->commissions()->where('status', 'pending')->count() }}
                    </p>
                </div>
                <div class="text-4xl">⏳</div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">อนุมัติแล้ว</p>
                    <p class="text-2xl font-bold text-green-600 mt-1">
                        {{ Auth::user()->commissions()->where('status', 'approved')->count() }}
                    </p>
                </div>
                <div class="text-4xl">✅</div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">จ่ายแล้ว</p>
                    <p class="text-2xl font-bold text-blue-600 mt-1">
                        {{ Auth::user()->commissions()->where('status', 'paid')->count() }}
                    </p>
                </div>
                <div class="text-4xl">💰</div>
            </div>
        </div>
    </div>

    <!-- Commissions Table -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        @if($commissions->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                วันที่
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Order ID
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ประเภท
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                จำนวน
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                %
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                สถานะ
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                หมายเหตุ
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($commissions as $commission)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $commission->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $commission->order_id ?? '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs">
                                        {{ ucfirst($commission->type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    ฿{{ number_format($commission->amount, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $commission->percentage }}%
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                        @if($commission->status === 'pending') bg-yellow-100 text-yellow-800
                                        @elseif($commission->status === 'approved') bg-green-100 text-green-800
                                        @elseif($commission->status === 'paid') bg-blue-100 text-blue-800
                                        @else bg-red-100 text-red-800
                                        @endif">
                                        @if($commission->status === 'pending') รอดำเนินการ
                                        @elseif($commission->status === 'approved') อนุมัติแล้ว
                                        @elseif($commission->status === 'paid') จ่ายแล้ว
                                        @else ปฏิเสธ
                                        @endif
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ $commission->notes ?? '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $commissions->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <div class="text-6xl mb-4">📭</div>
                <p class="text-gray-600 text-lg">ยังไม่มีคอมมิชชั่น</p>
                <p class="text-gray-500 text-sm mt-2">เมื่อมีคอมมิชชั่นเข้ามาจะแสดงที่นี่</p>
            </div>
        @endif
    </div>
</div>
@endsection
