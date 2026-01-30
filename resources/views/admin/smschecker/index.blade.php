@extends('layouts.admin-v3')

@section('title', 'SMS Payment Checker')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                📱 SMS Payment Checker
            </h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">
                ระบบตรวจสอบการชำระเงินผ่าน SMS อัตโนมัติ
            </p>
        </div>
        <a href="{{ route('admin.smschecker.device-create') }}"
           class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
            + เพิ่มอุปกรณ์ใหม่
        </a>
    </div>

    {{-- สถิติภาพรวม --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        {{-- อุปกรณ์ --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">อุปกรณ์ทั้งหมด</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $deviceStats['total'] }}</p>
                </div>
                <div class="text-4xl">📱</div>
            </div>
            <div class="mt-3 flex items-center space-x-3 text-sm">
                <span class="text-green-600">🟢 {{ $deviceStats['active'] }} active</span>
                <span class="text-gray-400">⚪ {{ $deviceStats['inactive'] }} inactive</span>
                <span class="text-red-600">🔴 {{ $deviceStats['blocked'] }} blocked</span>
            </div>
        </div>

        {{-- Notifications วันนี้ --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">SMS วันนี้</p>
                    <p class="text-3xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ $notificationStats['today'] }}</p>
                </div>
                <div class="text-4xl">📨</div>
            </div>
            <div class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                ทั้งหมด {{ number_format($notificationStats['total']) }} รายการ
            </div>
        </div>

        {{-- จับคู่สำเร็จ --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">จับคู่สำเร็จ</p>
                    <p class="text-3xl font-bold text-green-600 dark:text-green-400 mt-1">{{ $notificationStats['matched'] }}</p>
                </div>
                <div class="text-4xl">✅</div>
            </div>
            <div class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                รอจับคู่ {{ $notificationStats['pending'] }} รายการ
            </div>
        </div>

        {{-- Unique Amounts --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Unique Amounts Active</p>
                    <p class="text-3xl font-bold text-purple-600 dark:text-purple-400 mt-1">{{ $amountStats['active'] }}</p>
                </div>
                <div class="text-4xl">💰</div>
            </div>
            <div class="mt-3 flex items-center space-x-3 text-sm">
                <span class="text-green-600">{{ $amountStats['used'] }} ใช้แล้ว</span>
                <span class="text-gray-400">{{ $amountStats['expired'] }} หมดอายุ</span>
            </div>
        </div>
    </div>

    {{-- Quick Links --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <a href="{{ route('admin.smschecker.devices') }}"
           class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition group">
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center text-2xl group-hover:scale-110 transition">
                    📱
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white">จัดการอุปกรณ์</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">ดูและจัดการอุปกรณ์ SMS Checker ทั้งหมด</p>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.smschecker.notifications') }}"
           class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition group">
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center text-2xl group-hover:scale-110 transition">
                    📨
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white">ประวัติ SMS</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">ดูประวัติ SMS Notifications ที่ได้รับ</p>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.smschecker.device-create') }}"
           class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition group">
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center text-2xl group-hover:scale-110 transition">
                    ➕
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white">เพิ่มอุปกรณ์</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">ลงทะเบียนอุปกรณ์ใหม่ในระบบ</p>
                </div>
            </div>
        </a>
    </div>

    {{-- อุปกรณ์ล่าสุดที่ Active --}}
    @if($recentDevices->count() > 0)
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">📱 อุปกรณ์ที่ Active ล่าสุด</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">อุปกรณ์</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Device ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">สถานะ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Active ล่าสุด</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($recentDevices as $device)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <td class="px-6 py-4">
                            <a href="{{ route('admin.smschecker.device-show', $device) }}"
                               class="text-blue-600 dark:text-blue-400 hover:underline font-medium">
                                {{ $device->device_name ?? 'ไม่มีชื่อ' }}
                            </a>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 font-mono">
                            {{ $device->device_id }}
                        </td>
                        <td class="px-6 py-4">
                            @if($device->status === 'active')
                                <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300 rounded-full">🟢 Active</span>
                            @elseif($device->status === 'inactive')
                                <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-600 dark:text-gray-300 rounded-full">⚪ Inactive</span>
                            @else
                                <span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300 rounded-full">🔴 Blocked</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                            {{ $device->last_active_at ? $device->last_active_at->diffForHumans() : 'ไม่เคย' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- SMS Notifications ล่าสุด --}}
    @if($recentNotifications->count() > 0)
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">📨 SMS Notifications ล่าสุด</h2>
            <a href="{{ route('admin.smschecker.notifications') }}"
               class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                ดูทั้งหมด →
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">เวลา</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ธนาคาร</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ประเภท</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">จำนวนเงิน</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">สถานะ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($recentNotifications as $notification)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                            {{ $notification->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                            {{ $notification->bank }}
                        </td>
                        <td class="px-6 py-4">
                            @if($notification->type === 'credit')
                                <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300 rounded-full">💰 เงินเข้า</span>
                            @else
                                <span class="px-2 py-1 text-xs font-medium bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300 rounded-full">💸 เงินออก</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm font-medium text-right {{ $notification->type === 'credit' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $notification->type === 'credit' ? '+' : '-' }}{{ number_format($notification->amount, 2) }} บาท
                        </td>
                        <td class="px-6 py-4">
                            @switch($notification->status)
                                @case('pending')
                                    <span class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300 rounded-full">⏳ รอจับคู่</span>
                                    @break
                                @case('matched')
                                    <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300 rounded-full">🔗 จับคู่แล้ว</span>
                                    @break
                                @case('confirmed')
                                    <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300 rounded-full">✅ ยืนยันแล้ว</span>
                                    @break
                                @case('rejected')
                                    <span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300 rounded-full">❌ ปฏิเสธ</span>
                                    @break
                                @case('expired')
                                    <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-600 dark:text-gray-300 rounded-full">⏰ หมดอายุ</span>
                                    @break
                            @endswitch
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
