@extends('layouts.seller')

@section('title', 'รายละเอียดอุปกรณ์ POS')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">📱 {{ $device->device_name }}</h1>
            <p class="text-gray-500 mt-1">{{ $device->device_code }}</p>
        </div>
        <a href="{{ route('seller.pos.devices') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
            ← กลับ
        </a>
    </div>

    <!-- Device Status Card -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Info -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">ข้อมูลอุปกรณ์</h3>
            <div class="space-y-3">
                <div>
                    <span class="text-sm text-gray-500">ประเภท</span>
                    <div class="mt-1">
                        @if($device->device_type === 'premium')
                            <span class="px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-sm font-medium">💎 Premium</span>
                        @else
                            <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-medium">📱 Standard</span>
                        @endif
                    </div>
                </div>
                <div>
                    <span class="text-sm text-gray-500">สถานะ</span>
                    <div class="mt-1">
                        @if($device->is_online)
                            <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium">🟢 ออนไลน์</span>
                        @else
                            <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm font-medium">⚫ ออฟไลน์</span>
                        @endif
                    </div>
                </div>
                <div>
                    <span class="text-sm text-gray-500">สถานะแพคเกจ</span>
                    <div class="mt-1">
                        @if($device->subscription_status === 'active')
                            <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium">✅ ใช้งาน</span>
                        @elseif($device->subscription_status === 'trial')
                            <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-medium">🆓 ทดลอง</span>
                        @elseif($device->subscription_status === 'expired')
                            <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-sm font-medium">⚠️ หมดอายุ</span>
                        @else
                            <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm font-medium">{{ $device->subscription_status }}</span>
                        @endif
                    </div>
                </div>
                @if($device->last_active_at)
                <div>
                    <span class="text-sm text-gray-500">ใช้งานล่าสุด</span>
                    <div class="text-sm font-medium text-gray-900 mt-1">{{ $device->last_active_at->diffForHumans() }}</div>
                </div>
                @endif
            </div>
        </div>

        <!-- Subscription Info -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">ข้อมูลแพคเกจ</h3>
            <div class="space-y-3">
                @if($device->subscription_starts_at)
                <div>
                    <span class="text-sm text-gray-500">วันเริ่มแพคเกจ</span>
                    <div class="text-sm font-medium text-gray-900 mt-1">{{ $device->subscription_starts_at->format('d/m/Y') }}</div>
                </div>
                @endif
                @if($device->subscription_ends_at)
                <div>
                    <span class="text-sm text-gray-500">วันหมดอายุ</span>
                    <div class="text-sm font-medium text-gray-900 mt-1">{{ $device->subscription_ends_at->format('d/m/Y') }}</div>
                    @if($device->subscription_ends_at->isFuture())
                    <div class="text-xs text-gray-500 mt-1">เหลืออีก {{ $device->subscription_ends_at->diffInDays() }} วัน</div>
                    @endif
                </div>
                @endif
                <div>
                    <span class="text-sm text-gray-500">ค่าบริการรายเดือน</span>
                    <div class="text-lg font-bold text-green-600 mt-1">฿{{ number_format($device->monthly_fee, 2) }}</div>
                </div>
                @if($device->auto_renew)
                <div class="mt-2">
                    <span class="px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-xs font-medium">🔄 ต่ออายุอัตโนมัติ</span>
                </div>
                @endif
            </div>
        </div>

        <!-- Features -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">ฟีเจอร์</h3>
            <div class="space-y-2">
                <div class="flex items-center justify-between p-2 rounded {{ $device->dual_screen_enabled ? 'bg-blue-50' : 'bg-gray-50' }}">
                    <span class="text-sm {{ $device->dual_screen_enabled ? 'text-blue-900' : 'text-gray-500' }}">Dual Screen</span>
                    <span class="text-lg">{{ $device->dual_screen_enabled ? '✅' : '❌' }}</span>
                </div>
                <div class="flex items-center justify-between p-2 rounded {{ $device->offline_mode_enabled ? 'bg-purple-50' : 'bg-gray-50' }}">
                    <span class="text-sm {{ $device->offline_mode_enabled ? 'text-purple-900' : 'text-gray-500' }}">Offline Mode</span>
                    <span class="text-lg">{{ $device->offline_mode_enabled ? '✅' : '❌' }}</span>
                </div>
                <div class="flex items-center justify-between p-2 rounded {{ $device->receipt_printer_enabled ? 'bg-green-50' : 'bg-gray-50' }}">
                    <span class="text-sm {{ $device->receipt_printer_enabled ? 'text-green-900' : 'text-gray-500' }}">Receipt Printer</span>
                    <span class="text-lg">{{ $device->receipt_printer_enabled ? '✅' : '❌' }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
            <div class="text-sm opacity-80">รายการทั้งหมด</div>
            <div class="text-3xl font-bold mt-1">{{ number_format($stats['total_transactions']) }}</div>
            <div class="text-xs opacity-70 mt-1">รายการ</div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
            <div class="text-sm opacity-80">รายการวันนี้</div>
            <div class="text-3xl font-bold mt-1">{{ number_format($stats['today_transactions']) }}</div>
            <div class="text-xs opacity-70 mt-1">รายการ</div>
        </div>

        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg p-6 text-white">
            <div class="text-sm opacity-80">ยอดขายทั้งหมด</div>
            <div class="text-3xl font-bold mt-1">฿{{ number_format($stats['total_sales'], 0) }}</div>
            <div class="text-xs opacity-70 mt-1">บาท</div>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
            <div class="text-sm opacity-80">ยอดขายวันนี้</div>
            <div class="text-3xl font-bold mt-1">฿{{ number_format($stats['today_sales'], 0) }}</div>
            <div class="text-xs opacity-70 mt-1">บาท</div>
        </div>
    </div>

    <!-- Active Session -->
    @if($stats['active_session'])
    <div class="bg-yellow-50 border-2 border-yellow-200 rounded-xl shadow-lg p-6">
        <div class="flex items-start gap-4">
            <div class="text-4xl">🔐</div>
            <div class="flex-1">
                <h3 class="text-lg font-bold text-gray-900 mb-2">เซสชั่นที่เปิดอยู่</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <span class="text-sm text-gray-600">พนักงาน</span>
                        <div class="text-sm font-medium text-gray-900">{{ $stats['active_session']->user->name ?? 'N/A' }}</div>
                    </div>
                    <div>
                        <span class="text-sm text-gray-600">เริ่มเซสชั่น</span>
                        <div class="text-sm font-medium text-gray-900">{{ $stats['active_session']->opened_at->format('d/m/Y H:i') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Recent Transactions -->
    @if($device->transactions->count() > 0)
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">รายการขายล่าสุด (10 รายการ)</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">วันที่</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">รหัส</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">วิธีชำระ</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">จำนวนเงิน</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">สถานะ</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">การจัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($device->transactions()->latest('transaction_date')->limit(10)->get() as $transaction)
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
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
