@extends('layouts.seller')

@section('title', 'ระบบขายหน้าร้าน (POS)')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">🏪 ระบบขายหน้าร้าน (POS)</h1>
            <p class="text-gray-500 mt-1">จัดการอุปกรณ์ขายหน้าร้านและยอดขายของคุณ</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('seller.pos.transactions') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                📊 รายการขาย
            </a>
            <a href="{{ route('seller.pos.analytics') }}" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                📈 รายงาน
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <!-- Total Devices -->
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-80">อุปกรณ์ทั้งหมด</p>
                    <h3 class="text-3xl font-bold mt-1">{{ $stats['total_devices'] }}</h3>
                    <p class="text-xs opacity-70 mt-1">จำนวนอุปกรณ์</p>
                </div>
                <div class="text-5xl opacity-30">📱</div>
            </div>
        </div>

        <!-- Active Devices -->
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-80">อุปกรณ์ใช้งาน</p>
                    <h3 class="text-3xl font-bold mt-1">{{ $stats['online_devices'] }}/{{ $stats['active_devices'] }}</h3>
                    <p class="text-xs opacity-70 mt-1">ออนไลน์/พร้อมใช้</p>
                </div>
                <div class="text-5xl opacity-30">✅</div>
            </div>
        </div>

        <!-- Today Sales -->
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-80">ยอดขายวันนี้</p>
                    <h3 class="text-3xl font-bold mt-1">฿{{ number_format($stats['today_sales'], 0) }}</h3>
                    <p class="text-xs opacity-70 mt-1">{{ number_format($stats['today_transactions']) }} รายการ</p>
                </div>
                <div class="text-5xl opacity-30">💰</div>
            </div>
        </div>

        <!-- Month Sales -->
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-80">ยอดขายเดือนนี้</p>
                    <h3 class="text-3xl font-bold mt-1">฿{{ number_format($stats['month_sales'], 0) }}</h3>
                    <p class="text-xs opacity-70 mt-1">{{ number_format($stats['month_transactions']) }} รายการ</p>
                </div>
                <div class="text-5xl opacity-30">📊</div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">เมนูด่วน</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="{{ route('seller.pos.devices') }}" class="flex flex-col items-center p-6 border-2 border-gray-200 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-all">
                <div class="text-4xl mb-2">📱</div>
                <span class="text-sm font-medium text-gray-700">จัดการอุปกรณ์</span>
            </a>

            <a href="{{ route('seller.pos.transactions') }}" class="flex flex-col items-center p-6 border-2 border-gray-200 rounded-lg hover:border-green-500 hover:bg-green-50 transition-all">
                <div class="text-4xl mb-2">💵</div>
                <span class="text-sm font-medium text-gray-700">รายการขาย</span>
            </a>

            <a href="{{ route('seller.pos.sessions') }}" class="flex flex-col items-center p-6 border-2 border-gray-200 rounded-lg hover:border-purple-500 hover:bg-purple-50 transition-all">
                <div class="text-4xl mb-2">🔐</div>
                <span class="text-sm font-medium text-gray-700">เซสชั่นการขาย</span>
            </a>

            <a href="{{ route('seller.pos.settings') }}" class="flex flex-col items-center p-6 border-2 border-gray-200 rounded-lg hover:border-orange-500 hover:bg-orange-50 transition-all">
                <div class="text-4xl mb-2">⚙️</div>
                <span class="text-sm font-medium text-gray-700">ตั้งค่า POS</span>
            </a>
        </div>
    </div>

    <!-- Active Sessions -->
    @if($activeSessions->count() > 0)
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900">🔐 เซสชั่นที่เปิดอยู่</h3>
            <a href="{{ route('seller.pos.sessions') }}" class="text-blue-600 hover:text-blue-800 text-sm">ดูทั้งหมด →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">อุปกรณ์</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">พนักงาน</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">เริ่มเซสชั่น</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">ระยะเวลา</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($activeSessions as $session)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="text-sm font-medium text-gray-900">{{ $session->posDevice->device_name }}</div>
                            <div class="text-xs text-gray-500">{{ $session->posDevice->device_code }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-sm text-gray-900">{{ $session->user->name ?? 'N/A' }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-sm text-gray-900">{{ $session->opened_at->format('d/m/Y H:i') }}</div>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <span class="text-sm text-gray-600">{{ $session->opened_at->diffForHumans() }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Recent Transactions -->
    @if($recentTransactions->count() > 0)
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900">💰 รายการขายล่าสุด</h3>
            <a href="{{ route('seller.pos.transactions') }}" class="text-blue-600 hover:text-blue-800 text-sm">ดูทั้งหมด →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">วันที่</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">รหัสรายการ</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">อุปกรณ์</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">วิธีชำระ</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">จำนวนเงิน</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">สถานะ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($recentTransactions as $transaction)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-900">
                            {{ $transaction->transaction_date->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('seller.pos.transactions.show', $transaction) }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">
                                {{ $transaction->transaction_code }}
                            </a>
                            @if($transaction->receipt_number)
                            <div class="text-xs text-gray-500">{{ $transaction->receipt_number }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-sm text-gray-900">{{ $transaction->posDevice->device_name ?? 'N/A' }}</div>
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
                            @elseif($transaction->status === 'void')
                                <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-700 rounded-full">❌ ยกเลิก</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @else
    <div class="bg-white rounded-xl shadow-lg p-12 text-center">
        <div class="text-6xl mb-4">📊</div>
        <h3 class="text-xl font-bold text-gray-900 mb-2">ยังไม่มีรายการขาย</h3>
        <p class="text-gray-500">เริ่มใช้งานอุปกรณ์ POS เพื่อบันทึกรายการขาย</p>
    </div>
    @endif

    <!-- Help Section -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl shadow p-6">
        <div class="flex items-start gap-4">
            <div class="text-4xl">💡</div>
            <div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">เริ่มต้นใช้งาน POS</h3>
                <ul class="text-sm text-gray-700 space-y-2">
                    <li>✓ ตรวจสอบอุปกรณ์ของคุณในเมนู "จัดการอุปกรณ์"</li>
                    <li>✓ ตั้งค่า POS ให้เหมาะสมกับร้านค้าของคุณ</li>
                    <li>✓ เริ่มเซสชั่นการขายผ่านอุปกรณ์ POS</li>
                    <li>✓ ตรวจสอบรายงานและสถิติได้ทุกเวลา</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
