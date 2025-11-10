@extends('layouts.seller')

@section('title', 'คอมมิชชั่น')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-green-600 via-emerald-600 to-teal-600 rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-40 h-40 bg-white opacity-10 rounded-full"></div>
        <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-32 h-32 bg-white opacity-10 rounded-full"></div>
        <div class="relative z-10">
            <h1 class="text-3xl md:text-4xl font-bold mb-2">💰 คอมมิชชั่น</h1>
            <p class="text-green-100">รายได้และคอมมิชชั่นของคุณ</p>
        </div>
    </div>

    <!-- Commission Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="text-4xl">💵</div>
            </div>
            <p class="text-white text-opacity-80 text-sm mb-1">คอมมิชชั่นทั้งหมด</p>
            <p class="text-3xl md:text-4xl font-bold">฿{{ number_format($totalCommissions, 2) }}</p>
            <p class="text-xs text-white text-opacity-70 mt-2">Total Commissions</p>
        </div>

        <div class="bg-gradient-to-br from-yellow-500 to-amber-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="text-4xl">⏳</div>
            </div>
            <p class="text-white text-opacity-80 text-sm mb-1">รอการจ่าย</p>
            <p class="text-3xl md:text-4xl font-bold">฿{{ number_format($pendingCommissions, 2) }}</p>
            <p class="text-xs text-white text-opacity-70 mt-2">Pending</p>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="text-4xl">✓</div>
            </div>
            <p class="text-white text-opacity-80 text-sm mb-1">จ่ายแล้ว</p>
            <p class="text-3xl md:text-4xl font-bold">฿{{ number_format($paidCommissions, 2) }}</p>
            <p class="text-xs text-white text-opacity-70 mt-2">Paid</p>
        </div>
    </div>

    <!-- Commission List -->
    <div class="bg-white rounded-2xl shadow-xl p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
            <span>📋</span>
            <span>ประวัติคอมมิชชั่น</span>
        </h2>

        @if(empty($commissions) || count($commissions) === 0)
            <div class="text-center py-12">
                <span class="text-6xl block mb-4">💰</span>
                <p class="text-gray-500 text-lg font-medium mb-2">ยังไม่มีคอมมิชชั่น</p>
                <p class="text-gray-400 text-sm mb-6">เริ่มขายสินค้าเพื่อรับคอมมิชชั่น</p>
                <a href="{{ route('seller.products.index') }}" class="inline-block px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-lg hover:opacity-90 transition">
                    ไปที่สินค้าของฉัน
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">วันที่</th>
                            <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">รายการ</th>
                            <th class="text-right py-3 px-4 text-sm font-semibold text-gray-700">จำนวนเงิน</th>
                            <th class="text-center py-3 px-4 text-sm font-semibold text-gray-700">สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($commissions as $commission)
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="py-4 px-4 text-sm text-gray-600">
                                    {{ $commission->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="py-4 px-4">
                                    <p class="font-semibold text-gray-900">{{ $commission->description }}</p>
                                    <p class="text-xs text-gray-500">{{ $commission->type }}</p>
                                </td>
                                <td class="py-4 px-4 text-right">
                                    <p class="font-bold text-green-600">฿{{ number_format($commission->amount, 2) }}</p>
                                </td>
                                <td class="py-4 px-4 text-center">
                                    @if($commission->status === 'paid')
                                        <span class="inline-block px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">จ่ายแล้ว</span>
                                    @elseif($commission->status === 'pending')
                                        <span class="inline-block px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-semibold">รอจ่าย</span>
                                    @else
                                        <span class="inline-block px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-xs font-semibold">{{ $commission->status }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- How Commissions Work -->
    <div class="bg-blue-50 border border-blue-200 rounded-2xl p-6">
        <h3 class="text-lg font-bold text-blue-900 mb-4 flex items-center gap-2">
            <span>ℹ️</span>
            <span>วิธีการคำนวณคอมมิชชั่น</span>
        </h3>
        <div class="space-y-3 text-sm text-blue-800">
            <p>• คอมมิชชั่นจะถูกคำนวณจากยอดขายสุทธิของสินค้าที่ขายได้</p>
            <p>• คอมมิชชั่นจะถูกจ่ายเมื่อลูกค้าได้รับสินค้าและยืนยันแล้ว</p>
            <p>• คุณสามารถถอนคอมมิชชั่นได้ผ่านหน้ากระเป๋าเงิน</p>
            <p>• คอมมิชชั่นจะถูกอัปเดตแบบเรียลไทม์</p>
        </div>
    </div>
</div>
@endsection
