@extends('layouts.seller')

@section('title', 'ประวัติการถอนเงิน')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-40 h-40 bg-white opacity-10 rounded-full"></div>
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-4">
                <a href="{{ route('seller.wallet.index') }}" class="p-2 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg transition">
                    ← กลับ
                </a>
                <h1 class="text-3xl md:text-4xl font-bold">📋 ประวัติการถอนเงิน</h1>
            </div>
            <p class="text-indigo-100">ดูรายการคำขอถอนเงินทั้งหมดของร้านค้า</p>
        </div>
    </div>

    <!-- Success Message -->
    @if(session('success'))
    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
        <div class="flex gap-3">
            <span class="text-xl">✅</span>
            <div>
                <p class="text-green-800 font-medium">{{ session('success') }}</p>
            </div>
        </div>
    </div>
    @endif

    <!-- Error Message -->
    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
        <div class="flex gap-3">
            <span class="text-xl">⚠️</span>
            <div>
                @foreach($errors->all() as $error)
                    <p class="text-red-800 font-medium">{{ $error }}</p>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Quick Actions -->
    <div class="flex gap-4">
        <a href="{{ route('seller.wallet.withdraw') }}"
           class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-red-600 to-rose-600 text-white font-semibold rounded-lg hover:opacity-90 transition">
            💸 ถอนเงินใหม่
        </a>
    </div>

    <!-- Withdrawals Table -->
    <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
        @if($withdrawals->isEmpty())
            <div class="text-center py-16">
                <span class="text-6xl block mb-4">📭</span>
                <h3 class="text-xl font-bold text-gray-900 mb-2">ยังไม่มีประวัติการถอนเงิน</h3>
                <p class="text-gray-500 mb-6">คุณยังไม่เคยทำการถอนเงิน</p>
                <a href="{{ route('seller.wallet.withdraw') }}"
                   class="inline-block px-6 py-3 bg-gradient-to-r from-red-600 to-rose-600 text-white font-semibold rounded-lg hover:opacity-90 transition">
                    ถอนเงินเลย
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">หมายเลขคำขอ</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">วันที่</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">ช่องทางรับเงิน</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase">จำนวนเงิน</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase">สถานะ</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($withdrawals as $withdrawal)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <p class="font-medium text-gray-900">{{ $withdrawal->request_id }}</p>
                                @if($withdrawal->user_note)
                                    <p class="text-xs text-gray-500 mt-1">{{ Str::limit($withdrawal->user_note, 30) }}</p>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-gray-900">{{ $withdrawal->created_at->format('d/m/Y') }}</p>
                                <p class="text-xs text-gray-500">{{ $withdrawal->created_at->format('H:i') }} น.</p>
                            </td>
                            <td class="px-6 py-4">
                                @if($withdrawal->paymentMethod)
                                    <p class="text-gray-900">{{ $withdrawal->paymentMethod->bank_name ?? $withdrawal->payment_type_label }}</p>
                                    <p class="text-xs text-gray-500">{{ $withdrawal->paymentMethod->account_name ?? '-' }}</p>
                                @else
                                    <p class="text-gray-500">{{ $withdrawal->payment_type_label }}</p>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <p class="font-bold text-gray-900">฿{{ number_format($withdrawal->amount, 2) }}</p>
                                @if($withdrawal->fee > 0)
                                    <p class="text-xs text-gray-500">ค่าธรรมเนียม: ฿{{ number_format($withdrawal->fee, 2) }}</p>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full
                                    @if($withdrawal->status === 'pending') bg-yellow-100 text-yellow-800
                                    @elseif($withdrawal->status === 'processing') bg-blue-100 text-blue-800
                                    @elseif($withdrawal->status === 'completed') bg-green-100 text-green-800
                                    @elseif($withdrawal->status === 'approved') bg-green-100 text-green-800
                                    @elseif($withdrawal->status === 'rejected') bg-red-100 text-red-800
                                    @elseif($withdrawal->status === 'cancelled') bg-gray-100 text-gray-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ $withdrawal->status_label }}
                                </span>
                                @if($withdrawal->status === 'rejected' && $withdrawal->rejection_reason)
                                    <p class="text-xs text-red-600 mt-1">{{ Str::limit($withdrawal->rejection_reason, 30) }}</p>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($withdrawal->status === 'pending')
                                    <form action="{{ route('seller.wallet.withdrawal.cancel', $withdrawal->id) }}"
                                          method="POST"
                                          onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการยกเลิกคำขอนี้?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="text-red-600 hover:text-red-800 text-sm font-medium">
                                            ยกเลิก
                                        </button>
                                    </form>
                                @elseif($withdrawal->status === 'completed' && $withdrawal->transfer_slip)
                                    <a href="{{ $withdrawal->transfer_slip_url }}"
                                       target="_blank"
                                       class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                                        ดูสลิป
                                    </a>
                                @else
                                    <span class="text-gray-400 text-sm">-</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $withdrawals->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
