@extends('layouts.seller')

@section('title', 'จัดการอุปกรณ์ POS')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">📱 จัดการอุปกรณ์ POS</h1>
            <p class="text-gray-500 mt-1">อุปกรณ์ขายหน้าร้านของคุณ</p>
        </div>
        <a href="{{ route('seller.pos.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
            ← กลับ
        </a>
    </div>

    <!-- Devices Grid -->
    @if($devices->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($devices as $device)
        <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-shadow">
            <!-- Device Header -->
            <div class="p-6 {{ $device->is_online ? 'bg-gradient-to-r from-green-500 to-green-600' : 'bg-gradient-to-r from-gray-400 to-gray-500' }} text-white">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <span class="text-3xl">
                            @if($device->device_type === 'premium')
                                💎
                            @else
                                📱
                            @endif
                        </span>
                        @if($device->is_online)
                            <span class="px-2 py-1 bg-white/20 text-xs rounded-full">🟢 ออนไลน์</span>
                        @else
                            <span class="px-2 py-1 bg-white/20 text-xs rounded-full">⚫ ออฟไลน์</span>
                        @endif
                    </div>
                </div>
                <h3 class="text-xl font-bold">{{ $device->device_name }}</h3>
                <p class="text-sm opacity-80">{{ $device->device_code }}</p>
            </div>

            <!-- Device Details -->
            <div class="p-6">
                <div class="space-y-3">
                    <!-- Subscription Status -->
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">สถานะแพคเกจ</span>
                        <div>
                            @if($device->subscription_status === 'active')
                                <span class="px-2 py-1 bg-green-100 text-green-700 text-xs font-medium rounded">✅ ใช้งาน</span>
                            @elseif($device->subscription_status === 'trial')
                                <span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs font-medium rounded">🆓 ทดลอง</span>
                            @elseif($device->subscription_status === 'expired')
                                <span class="px-2 py-1 bg-red-100 text-red-700 text-xs font-medium rounded">⚠️ หมดอายุ</span>
                            @else
                                <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs font-medium rounded">{{ $device->subscription_status }}</span>
                            @endif
                        </div>
                    </div>

                    <!-- Expiry Date -->
                    @if($device->subscription_ends_at)
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">วันหมดอายุ</span>
                        <span class="text-sm font-medium text-gray-900">{{ $device->subscription_ends_at->format('d/m/Y') }}</span>
                    </div>
                    @endif

                    <!-- Features -->
                    <div class="pt-3 border-t border-gray-200">
                        <div class="text-xs text-gray-500 mb-2">ฟีเจอร์</div>
                        <div class="flex flex-wrap gap-1">
                            @if($device->dual_screen_enabled)
                                <span class="px-2 py-1 bg-blue-50 text-blue-700 text-xs rounded">🖥️ Dual Screen</span>
                            @endif
                            @if($device->offline_mode_enabled)
                                <span class="px-2 py-1 bg-purple-50 text-purple-700 text-xs rounded">📡 Offline</span>
                            @endif
                            @if($device->receipt_printer_enabled)
                                <span class="px-2 py-1 bg-green-50 text-green-700 text-xs rounded">🖨️ Printer</span>
                            @endif
                        </div>
                    </div>

                    <!-- Stats -->
                    @php
                        $todayTransactions = $device->transactions()->whereDate('transaction_date', today())->count();
                        $todaySales = $device->transactions()->whereDate('transaction_date', today())->sum('total_amount');
                    @endphp
                    <div class="pt-3 border-t border-gray-200">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <div class="text-xs text-gray-500">รายการวันนี้</div>
                                <div class="text-lg font-bold text-gray-900">{{ number_format($todayTransactions) }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-500">ยอดขายวันนี้</div>
                                <div class="text-lg font-bold text-green-600">฿{{ number_format($todaySales, 0) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="mt-6 flex gap-2">
                    <a href="{{ route('seller.pos.devices.show', $device) }}"
                       class="flex-1 px-4 py-2 bg-blue-600 text-white text-center rounded-lg hover:bg-blue-700 text-sm font-medium">
                        ดูรายละเอียด
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Pagination -->
    @if($devices->hasPages())
    <div class="bg-white rounded-xl shadow-lg p-6">
        {{ $devices->links() }}
    </div>
    @endif

    @else
    <!-- No Devices -->
    <div class="bg-white rounded-xl shadow-lg p-12 text-center">
        <div class="text-6xl mb-4">📱</div>
        <h3 class="text-xl font-bold text-gray-900 mb-2">ยังไม่มีอุปกรณ์ POS</h3>
        <p class="text-gray-500 mb-6">ติดต่อผู้ดูแลระบบเพื่อเพิ่มอุปกรณ์ POS สำหรับร้านค้าของคุณ</p>
    </div>
    @endif
</div>
@endsection
