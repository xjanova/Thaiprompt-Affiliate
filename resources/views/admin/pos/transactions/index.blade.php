@extends('layouts.admin-v3')

@section('title', 'รายการขาย POS')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">💰 รายการขาย POS</h1>
            <p class="text-gray-500 mt-1">รายการธุรกรรมการขายทั้งหมด</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">ค้นหา</label>
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="รหัส, ใบเสร็จ, ลูกค้า"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">วิธีชำระเงิน</label>
                <select name="payment_method" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">ทั้งหมด</option>
                    <option value="cash" {{ request('payment_method') == 'cash' ? 'selected' : '' }}>เงินสด</option>
                    <option value="card" {{ request('payment_method') == 'card' ? 'selected' : '' }}>บัตร</option>
                    <option value="qr" {{ request('payment_method') == 'qr' ? 'selected' : '' }}>QR Code</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">สถานะ</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">ทั้งหมด</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>สำเร็จ</option>
                    <option value="refunded" {{ request('status') == 'refunded' ? 'selected' : '' }}>คืนเงิน</option>
                    <option value="void" {{ request('status') == 'void' ? 'selected' : '' }}>ยกเลิก</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">วันที่เริ่ม</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    ค้นหา
                </button>
                <a href="{{ route('admin.pos.transactions.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                    รีเซ็ต
                </a>
            </div>
        </form>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="text-3xl mb-2">📊</div>
            <div class="text-sm text-gray-500">รายการทั้งหมด</div>
            <div class="text-2xl font-bold text-gray-900">{{ number_format($transactions->total()) }}</div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="text-3xl mb-2">💵</div>
            <div class="text-sm text-gray-500">เงินสด</div>
            <div class="text-lg font-bold text-green-600">
                {{ number_format($transactions->where('payment_method', 'cash')->count()) }}
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="text-3xl mb-2">💳</div>
            <div class="text-sm text-gray-500">บัตร</div>
            <div class="text-lg font-bold text-blue-600">
                {{ number_format($transactions->where('payment_method', 'card')->count()) }}
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="text-3xl mb-2">📱</div>
            <div class="text-sm text-gray-500">QR Code</div>
            <div class="text-lg font-bold text-purple-600">
                {{ number_format($transactions->where('payment_method', 'qr')->count()) }}
            </div>
        </div>
    </div>

    <!-- Transactions List -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วันที่</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">รหัส</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ร้านค้า</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ลูกค้า</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วิธีชำระ</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">จำนวนเงิน</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะ</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">การจัดการ</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($transactions as $transaction)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $transaction->transaction_date->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ $transaction->transaction_code }}</div>
                            @if($transaction->receipt_number)
                            <div class="text-xs text-gray-500">{{ $transaction->receipt_number }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900">{{ $transaction->store->store_name ?? 'N/A' }}</div>
                            <div class="text-xs text-gray-500">{{ $transaction->posDevice->device_name ?? 'N/A' }}</div>
                        </td>
                        <td class="px-6 py-4">
                            @if($transaction->customer_name)
                                <div class="text-sm text-gray-900">{{ $transaction->customer_name }}</div>
                                @if($transaction->customer_phone)
                                <div class="text-xs text-gray-500">{{ $transaction->customer_phone }}</div>
                                @endif
                            @else
                                <span class="text-sm text-gray-400">Walk-in</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($transaction->payment_method === 'cash')
                                <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-700 rounded">💵 เงินสด</span>
                            @elseif($transaction->payment_method === 'card')
                                <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-700 rounded">💳 บัตร</span>
                            @elseif($transaction->payment_method === 'qr')
                                <span class="px-2 py-1 text-xs font-medium bg-purple-100 text-purple-700 rounded">📱 QR</span>
                            @else
                                <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-700 rounded">{{ $transaction->payment_method }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="text-sm font-bold text-gray-900">฿{{ number_format($transaction->total_amount, 2) }}</div>
                            @if($transaction->discount_amount > 0)
                            <div class="text-xs text-red-600">ส่วนลด -฿{{ number_format($transaction->discount_amount, 2) }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($transaction->status === 'completed')
                                <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-700 rounded-full">✅ สำเร็จ</span>
                            @elseif($transaction->status === 'refunded')
                                <span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-700 rounded-full">↩️ คืนเงิน</span>
                            @elseif($transaction->status === 'void')
                                <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-700 rounded-full">❌ ยกเลิก</span>
                            @else
                                <span class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-700 rounded-full">{{ $transaction->status }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-medium">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.pos.transactions.show', $transaction) }}"
                                   class="text-blue-600 hover:text-blue-900" title="ดูรายละเอียด">
                                    👁️
                                </a>
                                <a href="{{ route('admin.pos.transactions.receipt', $transaction) }}" target="_blank"
                                   class="text-green-600 hover:text-green-900" title="ดูใบเสร็จ">
                                    📄
                                </a>
                                @if($transaction->status === 'completed' && auth()->user()->isSuperAdmin())
                                <form action="{{ route('admin.pos.transactions.refund', $transaction) }}" method="POST"
                                      onsubmit="return confirm('ต้องการคืนเงินรายการนี้?')" class="inline">
                                    @csrf
                                    <button type="submit" class="text-red-600 hover:text-red-900" title="คืนเงิน">
                                        ↩️
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <div class="text-gray-400 text-lg">ไม่พบรายการขาย</div>
                            <p class="text-gray-500 text-sm mt-2">ลองปรับเปลี่ยนตัวกรองการค้นหา</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transactions->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $transactions->links() }}
        </div>
        @endif
    </div>

    <!-- Quick Export -->
    @if(auth()->user()->isSuperAdmin())
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-bold text-gray-900">ส่งออกข้อมูล</h3>
                <p class="text-sm text-gray-500 mt-1">ส่งออกรายการขายเป็นไฟล์ CSV</p>
            </div>
            <a href="{{ route('admin.pos.transactions.export') }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}"
               class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                📥 Export CSV
            </a>
        </div>
    </div>
    @endif
</div>
@endsection
