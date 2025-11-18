@extends('layouts.admin-v3')

@section('title', 'POS Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 via-cyan-600 to-teal-600 rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-40 h-40 bg-white opacity-10 rounded-full"></div>
        <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-32 h-32 bg-white opacity-10 rounded-full"></div>
        <div class="relative z-10">
            <h1 class="text-3xl font-bold mb-2">🏪 POS Dashboard</h1>
            <p class="text-blue-100">ภาพรวมระบบ Point of Sale - {{ now()->format('d/m/Y') }}</p>
        </div>
    </div>

    <!-- Device Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Devices -->
        <div class="relative overflow-hidden bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="absolute top-0 right-0 -mt-4 -mr-4">
                <div class="w-24 h-24 bg-white opacity-10 rounded-full"></div>
            </div>
            <div class="relative z-10">
                <div class="text-5xl mb-4">📱</div>
                <p class="text-white text-opacity-80 text-sm mb-1">อุปกรณ์ทั้งหมด</p>
                <p class="text-4xl font-bold">{{ number_format($stats['total_devices']) }}</p>
                <p class="text-xs text-white text-opacity-70 mt-2">
                    Active: {{ number_format($stats['active_devices']) }}
                </p>
            </div>
        </div>

        <!-- Online Devices -->
        <div class="relative overflow-hidden bg-gradient-to-br from-green-500 to-green-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="absolute top-0 right-0 -mt-4 -mr-4">
                <div class="w-24 h-24 bg-white opacity-10 rounded-full"></div>
            </div>
            <div class="relative z-10">
                <div class="text-5xl mb-4">🟢</div>
                <p class="text-white text-opacity-80 text-sm mb-1">ออนไลน์</p>
                <p class="text-4xl font-bold">{{ number_format($stats['online_devices']) }}</p>
                <p class="text-xs text-white text-opacity-70 mt-2">
                    จาก {{ number_format($stats['active_devices']) }} อุปกรณ์
                </p>
            </div>
        </div>

        <!-- Subscribed Devices -->
        <div class="relative overflow-hidden bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="absolute top-0 right-0 -mt-4 -mr-4">
                <div class="w-24 h-24 bg-white opacity-10 rounded-full"></div>
            </div>
            <div class="relative z-10">
                <div class="text-5xl mb-4">💎</div>
                <p class="text-white text-opacity-80 text-sm mb-1">สมาชิกใช้งาน</p>
                <p class="text-4xl font-bold">{{ number_format($stats['subscribed_devices']) }}</p>
                <p class="text-xs text-white text-opacity-70 mt-2">
                    Trial: {{ number_format($stats['trial_devices']) }}
                </p>
            </div>
        </div>

        <!-- Expired Devices -->
        <div class="relative overflow-hidden bg-gradient-to-br from-red-500 to-red-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="absolute top-0 right-0 -mt-4 -mr-4">
                <div class="w-24 h-24 bg-white opacity-10 rounded-full"></div>
            </div>
            <div class="relative z-10">
                <div class="text-5xl mb-4">⚠️</div>
                <p class="text-white text-opacity-80 text-sm mb-1">หมดอายุ</p>
                <p class="text-4xl font-bold">{{ number_format($stats['expired_devices']) }}</p>
                <p class="text-xs text-white text-opacity-70 mt-2">
                    ต้องต่ออายุ
                </p>
            </div>
        </div>
    </div>

    <!-- Transaction Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Today Sales -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="text-3xl">💰</div>
                <span class="px-2 py-1 bg-green-100 text-green-600 rounded-lg text-xs font-semibold">
                    วันนี้
                </span>
            </div>
            <p class="text-gray-500 text-sm mb-1">ยอดขายวันนี้</p>
            <p class="text-2xl font-bold text-gray-900">฿{{ number_format($stats['today_sales'], 2) }}</p>
            <p class="text-xs text-gray-500 mt-2">{{ number_format($stats['today_transactions']) }} รายการ</p>
        </div>

        <!-- Month Sales -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="text-3xl">📊</div>
                <span class="px-2 py-1 bg-blue-100 text-blue-600 rounded-lg text-xs font-semibold">
                    เดือนนี้
                </span>
            </div>
            <p class="text-gray-500 text-sm mb-1">ยอดขายเดือนนี้</p>
            <p class="text-2xl font-bold text-gray-900">฿{{ number_format($stats['month_sales'], 2) }}</p>
            <p class="text-xs text-gray-500 mt-2">{{ number_format($stats['month_transactions']) }} รายการ</p>
        </div>

        <!-- Total Sales -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="text-3xl">💎</div>
                <span class="px-2 py-1 bg-purple-100 text-purple-600 rounded-lg text-xs font-semibold">
                    ทั้งหมด
                </span>
            </div>
            <p class="text-gray-500 text-sm mb-1">ยอดขายทั้งหมด</p>
            <p class="text-2xl font-bold text-gray-900">฿{{ number_format($stats['total_sales'], 2) }}</p>
            <p class="text-xs text-gray-500 mt-2">{{ number_format($stats['total_transactions']) }} รายการ</p>
        </div>

        <!-- Average Transaction -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="text-3xl">📈</div>
                <span class="px-2 py-1 bg-orange-100 text-orange-600 rounded-lg text-xs font-semibold">
                    เฉลี่ย
                </span>
            </div>
            <p class="text-gray-500 text-sm mb-1">ยอดเฉลี่ยต่อรายการ</p>
            <p class="text-2xl font-bold text-gray-900">฿{{ number_format($stats['average_transaction'], 2) }}</p>
            <p class="text-xs text-gray-500 mt-2">Active Sessions: {{ number_format($stats['active_sessions']) }}</p>
        </div>
    </div>

    <!-- Payment Methods & Session Stats -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Payment Methods -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">วิธีการชำระเงิน</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <span class="text-2xl mr-3">💵</span>
                        <div>
                            <p class="text-sm font-medium text-gray-900">เงินสด</p>
                            <p class="text-xs text-gray-500">Cash Payment</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-bold text-gray-900">฿{{ number_format($stats['cash_sales'], 2) }}</p>
                    </div>
                </div>
                <div class="border-t border-gray-200"></div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <span class="text-2xl mr-3">💳</span>
                        <div>
                            <p class="text-sm font-medium text-gray-900">บัตร</p>
                            <p class="text-xs text-gray-500">Card Payment</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-bold text-gray-900">฿{{ number_format($stats['card_sales'], 2) }}</p>
                    </div>
                </div>
                <div class="border-t border-gray-200"></div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <span class="text-2xl mr-3">📱</span>
                        <div>
                            <p class="text-sm font-medium text-gray-900">QR Code</p>
                            <p class="text-xs text-gray-500">QR Payment</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-bold text-gray-900">฿{{ number_format($stats['qr_sales'], 2) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Session & Refund Stats -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Session & Refund Statistics</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                    <div>
                        <p class="text-sm font-medium text-gray-900">Active Sessions</p>
                        <p class="text-xs text-gray-500">กำลังเปิดใช้งาน</p>
                    </div>
                    <p class="text-2xl font-bold text-green-600">{{ number_format($stats['active_sessions']) }}</p>
                </div>
                <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                    <div>
                        <p class="text-sm font-medium text-gray-900">Today Sessions</p>
                        <p class="text-xs text-gray-500">Session วันนี้</p>
                    </div>
                    <p class="text-2xl font-bold text-blue-600">{{ number_format($stats['today_sessions']) }}</p>
                </div>
                <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg">
                    <div>
                        <p class="text-sm font-medium text-gray-900">Refunds</p>
                        <p class="text-xs text-gray-500">{{ number_format($stats['total_refunds']) }} รายการ ({{ $stats['refund_rate'] }}%)</p>
                    </div>
                    <p class="text-2xl font-bold text-red-600">฿{{ number_format($stats['refund_amount'], 2) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Stores -->
    @if($topStores->count() > 0)
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900">🏆 ร้านค้าที่ขายดีที่สุด (เดือนนี้)</h3>
            <a href="{{ route('admin.pos.analytics') }}" class="text-sm text-blue-600 hover:text-blue-700">
                ดูทั้งหมด →
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ร้านค้า</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">รายการ</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">ยอดขาย</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($topStores as $index => $store)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm">
                            @if($index == 0)
                                <span class="text-2xl">🥇</span>
                            @elseif($index == 1)
                                <span class="text-2xl">🥈</span>
                            @elseif($index == 2)
                                <span class="text-2xl">🥉</span>
                            @else
                                <span class="text-gray-500">{{ $index + 1 }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-sm font-medium text-gray-900">{{ $store->store_name ?? 'N/A' }}</p>
                            <p class="text-xs text-gray-500">{{ $store->store_code ?? '-' }}</p>
                        </td>
                        <td class="px-4 py-3 text-right text-sm text-gray-900">
                            {{ number_format($store->transaction_count) }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <span class="text-sm font-bold text-green-600">
                                ฿{{ number_format($store->total_sales, 2) }}
                            </span>
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
            <h3 class="text-lg font-bold text-gray-900">📋 รายการขายล่าสุด</h3>
            <a href="{{ route('admin.pos.transactions.index') }}" class="text-sm text-blue-600 hover:text-blue-700">
                ดูทั้งหมด →
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วันที่</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ร้านค้า</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">อุปกรณ์</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วิธีชำระ</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">จำนวน</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($recentTransactions as $transaction)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-900">
                            {{ $transaction->transaction_date->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-sm font-medium text-gray-900">{{ $transaction->store->store_name ?? 'N/A' }}</p>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">
                            {{ $transaction->posDevice->device_name ?? 'N/A' }}
                        </td>
                        <td class="px-4 py-3">
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
                        <td class="px-4 py-3 text-right">
                            <span class="text-sm font-bold text-gray-900">
                                ฿{{ number_format($transaction->total_amount, 2) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @if($transaction->status === 'completed')
                                <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-700 rounded">สำเร็จ</span>
                            @elseif($transaction->status === 'refunded')
                                <span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-700 rounded">คืนเงิน</span>
                            @elseif($transaction->status === 'void')
                                <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-700 rounded">ยกเลิก</span>
                            @else
                                <span class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-700 rounded">{{ $transaction->status }}</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Device Status -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Subscription Status -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">สถานะสมาชิก</h3>
            <div class="space-y-3">
                @foreach($deviceStatus['by_status'] as $status => $count)
                <div class="flex items-center justify-between p-3 rounded-lg
                    {{ $status === 'active' ? 'bg-green-50' : ($status === 'trial' ? 'bg-blue-50' : ($status === 'expired' ? 'bg-red-50' : 'bg-gray-50')) }}">
                    <div class="flex items-center">
                        @if($status === 'active')
                            <span class="text-xl mr-2">✅</span>
                        @elseif($status === 'trial')
                            <span class="text-xl mr-2">🆓</span>
                        @elseif($status === 'expired')
                            <span class="text-xl mr-2">⚠️</span>
                        @else
                            <span class="text-xl mr-2">🔒</span>
                        @endif
                        <span class="text-sm font-medium text-gray-900 capitalize">{{ $status }}</span>
                    </div>
                    <span class="text-lg font-bold
                        {{ $status === 'active' ? 'text-green-600' : ($status === 'trial' ? 'text-blue-600' : ($status === 'expired' ? 'text-red-600' : 'text-gray-600')) }}">
                        {{ number_format($count) }}
                    </span>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Device Type -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">ประเภทอุปกรณ์</h3>
            <div class="space-y-3">
                @foreach($deviceStatus['by_type'] as $type => $count)
                <div class="flex items-center justify-between p-3 rounded-lg
                    {{ $type === 'premium' ? 'bg-purple-50' : 'bg-blue-50' }}">
                    <div class="flex items-center">
                        @if($type === 'premium')
                            <span class="text-xl mr-2">💎</span>
                        @else
                            <span class="text-xl mr-2">📱</span>
                        @endif
                        <span class="text-sm font-medium text-gray-900 capitalize">{{ $type }}</span>
                    </div>
                    <span class="text-lg font-bold
                        {{ $type === 'premium' ? 'text-purple-600' : 'text-blue-600' }}">
                        {{ number_format($count) }}
                    </span>
                </div>
                @endforeach
                <div class="border-t border-gray-200 pt-3 mt-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-900">Online / Offline</p>
                            <p class="text-xs text-gray-500">สถานะการเชื่อมต่อ</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm">
                                <span class="text-green-600 font-bold">{{ number_format($deviceStatus['online_offline']['online']) }}</span>
                                <span class="text-gray-400">/</span>
                                <span class="text-red-600 font-bold">{{ number_format($deviceStatus['online_offline']['offline']) }}</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <a href="{{ route('admin.pos.devices.index') }}" class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
            <div class="text-4xl mb-3">📱</div>
            <h3 class="text-lg font-bold text-gray-900 mb-2">จัดการอุปกรณ์</h3>
            <p class="text-sm text-gray-500">เพิ่ม แก้ไข หรือดูรายละเอียดอุปกรณ์ POS</p>
        </a>
        <a href="{{ route('admin.pos.transactions.index') }}" class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
            <div class="text-4xl mb-3">💰</div>
            <h3 class="text-lg font-bold text-gray-900 mb-2">รายการขาย</h3>
            <p class="text-sm text-gray-500">ดูประวัติและรายละเอียดการขาย</p>
        </a>
        <a href="{{ route('admin.pos.analytics') }}" class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
            <div class="text-4xl mb-3">📊</div>
            <h3 class="text-lg font-bold text-gray-900 mb-2">รายงานและสถิติ</h3>
            <p class="text-sm text-gray-500">วิเคราะห์ข้อมูลและรายงานโดยละเอียด</p>
        </a>
    </div>
</div>
@endsection
