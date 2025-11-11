@extends('layouts.user')

@section('title', 'ประวัติ PV (Point Value)')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-amber-600 via-orange-600 to-red-600 rounded-2xl shadow-2xl p-8 text-white">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                <span class="text-3xl">⭐</span>
            </div>
            <div>
                <h1 class="text-3xl font-bold">ประวัติ PV (Point Value)</h1>
                <p class="text-amber-100 mt-1">ประวัติการสะสมและใช้คะแนน</p>
            </div>
        </div>
    </div>

    <!-- PV Summary -->
    <div class="grid md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <span class="text-2xl">💎</span>
                </div>
                <div class="flex-1">
                    <div class="text-sm text-gray-600">PV รวม</div>
                    <div class="text-2xl font-bold text-gray-800">
                        {{ number_format($member->total_pv ?? 0, 0) }}
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <span class="text-2xl">➕</span>
                </div>
                <div class="flex-1">
                    <div class="text-sm text-gray-600">PV ได้รับ</div>
                    <div class="text-2xl font-bold text-green-600">
                        +{{ number_format($transactions->where('transaction_type', 'credit')->sum('pv_amount'), 0) }}
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                    <span class="text-2xl">➖</span>
                </div>
                <div class="flex-1">
                    <div class="text-sm text-gray-600">PV ใช้ไป</div>
                    <div class="text-2xl font-bold text-red-600">
                        -{{ number_format($transactions->where('transaction_type', 'debit')->sum('pv_amount'), 0) }}
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <span class="text-2xl">📅</span>
                </div>
                <div class="flex-1">
                    <div class="text-sm text-gray-600">PV เดือนนี้</div>
                    <div class="text-2xl font-bold text-purple-600">
                        {{ number_format($transactions->filter(fn($t) => $t->created_at->isCurrentMonth())->sum('pv_amount'), 0) }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl shadow-xl p-6">
        <form method="GET" class="grid md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">ประเภท</label>
                <select name="type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">ทั้งหมด</option>
                    <option value="credit" {{ request('type') === 'credit' ? 'selected' : '' }}>รับ PV</option>
                    <option value="debit" {{ request('type') === 'debit' ? 'selected' : '' }}>ใช้ PV</option>
                </select>
            </div>

            <div class="md:col-span-2 flex items-end gap-2">
                <button type="submit" class="flex-1 px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition-colors">
                    🔍 ค้นหา
                </button>
                <a href="{{ url()->current() }}" class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-semibold transition-colors">
                    ล้าง
                </a>
            </div>
        </form>
    </div>

    <!-- Transactions Table -->
    <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                <span>📋</span> ประวัติการทำรายการ
            </h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100 border-b-2 border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">วันที่</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">ประเภท</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">รายละเอียด</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">PV</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">หมายเหตุ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($transactions as $transaction)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $transaction->created_at->format('d/m/Y') }}</div>
                                <div class="text-xs text-gray-500">{{ $transaction->created_at->format('H:i') }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($transaction->transaction_type === 'credit')
                                    <span class="px-3 py-1 text-xs font-semibold bg-green-100 text-green-800 rounded-full">
                                        ➕ รับ PV
                                    </span>
                                @else
                                    <span class="px-3 py-1 text-xs font-semibold bg-red-100 text-red-800 rounded-full">
                                        ➖ ใช้ PV
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($transaction->order)
                                    <div class="text-sm font-medium text-gray-900">
                                        คำสั่งซื้อ #{{ $transaction->order->id }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ $transaction->order->total_amount ? '฿' . number_format($transaction->order->total_amount, 2) : '' }}
                                    </div>
                                @elseif($transaction->product)
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $transaction->product->name ?? 'สินค้า' }}
                                    </div>
                                @else
                                    <span class="text-gray-500 text-sm">{{ $transaction->description ?? '-' }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-lg font-bold
                                    @if($transaction->transaction_type === 'credit') text-green-600
                                    @else text-red-600
                                    @endif">
                                    {{ $transaction->transaction_type === 'credit' ? '+' : '-' }}{{ number_format($transaction->pv_amount, 0) }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $transaction->notes ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <span class="text-4xl mb-4 block">⭐</span>
                                <p class="text-gray-600">ยังไม่มีประวัติการทำรายการ</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($transactions->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>

    <!-- PV Info -->
    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl shadow-xl p-6 border border-blue-100">
        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
            <span>💡</span> เกี่ยวกับ PV (Point Value)
        </h3>
        <div class="space-y-2 text-sm text-gray-700">
            <p>• <strong>PV</strong> คือคะแนนที่ได้จากการซื้อสินค้าหรือบริการ</p>
            <p>• PV ใช้สำหรับคำนวณค่าคอมมิชชั่นและการจัดอันดับ</p>
            <p>• PV สะสมจะช่วยให้คุณมีสิทธิ์รับโบนัสและสิทธิพิเศษต่างๆ</p>
            <p>• ตรวจสอบเงื่อนไขการใช้ PV กับทีมงานของเรา</p>
        </div>
    </div>
</div>
@endsection
