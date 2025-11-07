@extends('layouts.seller')

@section('title', 'รายละเอียดเซสชั่น')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">🔐 รายละเอียดเซสชั่น</h1>
            <p class="text-gray-500 mt-1">รหัส: {{ $session->session_code ?? 'N/A' }}</p>
        </div>
        <a href="{{ route('seller.pos.sessions') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
            ← กลับ
        </a>
    </div>

    <!-- Session Info -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Session Details -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">ข้อมูลเซสชั่น</h3>
            <div class="space-y-3">
                <div>
                    <span class="text-sm text-gray-500">รหัสเซสชั่น</span>
                    <div class="text-sm font-medium text-gray-900 mt-1">{{ $session->session_code ?? 'N/A' }}</div>
                </div>
                <div>
                    <span class="text-sm text-gray-500">สถานะ</span>
                    <div class="mt-1">
                        @if($session->status === 'open')
                            <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium">🟢 เปิดอยู่</span>
                        @else
                            <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm font-medium">⚫ ปิดแล้ว</span>
                        @endif
                    </div>
                </div>
                <div>
                    <span class="text-sm text-gray-500">เริ่มเซสชั่น</span>
                    <div class="text-sm font-medium text-gray-900 mt-1">{{ $session->opened_at->format('d/m/Y H:i:s') }}</div>
                </div>
                @if($session->closed_at)
                <div>
                    <span class="text-sm text-gray-500">ปิดเซสชั่น</span>
                    <div class="text-sm font-medium text-gray-900 mt-1">{{ $session->closed_at->format('d/m/Y H:i:s') }}</div>
                </div>
                <div>
                    <span class="text-sm text-gray-500">ระยะเวลา</span>
                    <div class="text-sm font-medium text-gray-900 mt-1">{{ $session->opened_at->diffForHumans($session->closed_at, true) }}</div>
                </div>
                @else
                <div>
                    <span class="text-sm text-gray-500">ระยะเวลา</span>
                    <div class="text-sm font-medium text-gray-900 mt-1">{{ $session->opened_at->diffForHumans() }}</div>
                </div>
                @endif
            </div>
        </div>

        <!-- Device & User Info -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">อุปกรณ์และพนักงาน</h3>
            <div class="space-y-3">
                <div>
                    <span class="text-sm text-gray-500">อุปกรณ์</span>
                    <div class="text-sm font-medium text-gray-900 mt-1">{{ $session->posDevice->device_name ?? 'N/A' }}</div>
                    @if($session->posDevice)
                    <div class="text-xs text-gray-500">{{ $session->posDevice->device_code }}</div>
                    @endif
                </div>
                <div>
                    <span class="text-sm text-gray-500">พนักงาน</span>
                    <div class="text-sm font-medium text-gray-900 mt-1">{{ $session->user->name ?? 'N/A' }}</div>
                </div>
            </div>
        </div>

        <!-- Cash Management -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">การจัดการเงินสด</h3>
            <div class="space-y-3">
                @if($session->opening_cash)
                <div>
                    <span class="text-sm text-gray-500">เงินสดเริ่มต้น</span>
                    <div class="text-lg font-bold text-blue-600 mt-1">฿{{ number_format($session->opening_cash, 2) }}</div>
                </div>
                @endif
                @if($session->closing_cash)
                <div>
                    <span class="text-sm text-gray-500">เงินสดปิดกะ</span>
                    <div class="text-lg font-bold text-green-600 mt-1">฿{{ number_format($session->closing_cash, 2) }}</div>
                </div>
                @endif
                @if($session->expected_cash)
                <div>
                    <span class="text-sm text-gray-500">เงินสดที่ควรมี</span>
                    <div class="text-lg font-bold text-orange-600 mt-1">฿{{ number_format($session->expected_cash, 2) }}</div>
                </div>
                @endif
                @if($session->cash_difference)
                <div>
                    <span class="text-sm text-gray-500">ผลต่าง</span>
                    <div class="text-lg font-bold {{ $session->cash_difference >= 0 ? 'text-green-600' : 'text-red-600' }} mt-1">
                        ฿{{ number_format($session->cash_difference, 2) }}
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Session Transactions -->
    @if($session->transactions && $session->transactions->count() > 0)
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">รายการขายในเซสชั่นนี้</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">วันที่</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">รหัสรายการ</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">วิธีชำระ</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">จำนวนเงิน</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">สถานะ</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">การจัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($session->transactions as $transaction)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-900">
                            {{ $transaction->transaction_date->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-sm font-medium text-gray-900">{{ $transaction->transaction_code }}</div>
                        </td>
                        <td class="px-4 py-3">
                            @if($transaction->payment_method === 'cash')
                                <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-700 rounded">💵 เงินสด</span>
                            @elseif($transaction->payment_method === 'card')
                                <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-700 rounded">💳 บัตร</span>
                            @elseif($transaction->payment_method === 'qr')
                                <span class="px-2 py-1 text-xs font-medium bg-purple-100 text-purple-700 rounded">📱 QR</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <span class="text-sm font-bold text-gray-900">฿{{ number_format($transaction->total_amount, 2) }}</span>
                        </td>
                        <td class="px-4 py-3">
                            @if($transaction->status === 'completed')
                                <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-700 rounded-full">✅ สำเร็จ</span>
                            @elseif($transaction->status === 'refunded')
                                <span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-700 rounded-full">↩️ คืนเงิน</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('seller.pos.transactions.show', $transaction) }}" class="text-blue-600 hover:text-blue-900 text-sm">
                                ดูรายละเอียด →
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr>
                        <td colspan="3" class="px-4 py-3 text-right text-sm font-bold text-gray-900">รวมทั้งหมด</td>
                        <td class="px-4 py-3 text-right text-lg font-bold text-green-600">
                            ฿{{ number_format($session->transactions->sum('total_amount'), 2) }}
                        </td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @else
    <div class="bg-white rounded-xl shadow-lg p-12 text-center">
        <div class="text-6xl mb-4">📊</div>
        <h3 class="text-xl font-bold text-gray-900 mb-2">ยังไม่มีรายการขาย</h3>
        <p class="text-gray-500">ไม่มีรายการขายในเซสชั่นนี้</p>
    </div>
    @endif

    <!-- Notes -->
    @if($session->notes)
    <div class="bg-yellow-50 rounded-xl shadow p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-2">📝 หมายเหตุ</h3>
        <p class="text-sm text-gray-700">{{ $session->notes }}</p>
    </div>
    @endif
</div>
@endsection
