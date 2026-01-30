@extends('layouts.admin-v3')

@section('title', 'บัญชีธนาคาร & SMS Payment')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                🏦 บัญชีธนาคาร & SMS Payment
            </h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">
                จัดการบัญชีรับชำระเงิน, PromptPay และ SMS Checker
            </p>
        </div>
        <a href="{{ route('admin.payment-bank-accounts.create') }}"
           class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
            + เพิ่มบัญชี
        </a>
    </div>

    {{-- Flash Message --}}
    @if(session('success'))
    <div class="bg-green-100 dark:bg-green-900 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-300 px-4 py-3 rounded-lg">
        {{ session('success') }}
    </div>
    @endif

    {{-- สถิติ --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">บัญชีทั้งหมด</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">เปิดใช้งาน</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['active'] }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">SMS Checker</p>
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['sms_enabled'] }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">PromptPay</p>
            <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $stats['promptpay'] }}</p>
        </div>
    </div>

    {{-- SMS Checker อุปกรณ์ --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">📱 อุปกรณ์ SMS Checker</h2>
            <a href="{{ route('admin.smschecker.devices') }}"
               class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                จัดการอุปกรณ์ →
            </a>
        </div>
        @if($smsDevices->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                @foreach($smsDevices as $device)
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3 flex items-center space-x-3">
                    <span class="text-2xl">📱</span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                            {{ $device->device_name ?? $device->device_id }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            🟢 Active
                            @if($device->last_active_at)
                                | {{ $device->last_active_at->diffForHumans() }}
                            @endif
                        </p>
                    </div>
                    <a href="{{ route('admin.smschecker.device-show', $device) }}"
                       class="px-2 py-1 text-xs bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 rounded hover:bg-blue-200 dark:hover:bg-blue-800">
                        QR
                    </a>
                </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-6 text-gray-500 dark:text-gray-400">
                <p>ยังไม่มีอุปกรณ์ SMS Checker ที่ active</p>
                <a href="{{ route('admin.smschecker.create-device') }}"
                   class="text-blue-600 dark:text-blue-400 hover:underline text-sm mt-1 inline-block">
                    + สร้างอุปกรณ์ใหม่
                </a>
            </div>
        @endif

        {{-- คำอธิบาย SMS Checker --}}
        <div class="mt-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
            <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-2">ทำไมต้องใช้ SMS Checker?</h3>
            <p class="text-xs text-gray-600 dark:text-gray-400">
                SMS Checker เป็นแอพ Android ที่ตรวจจับ SMS จากธนาคาร เมื่อลูกค้าโอนเงินเข้าบัญชี
                ระบบจะจับคู่ยอดเงินอัตโนมัติ ไม่ต้องรอแอดมินยืนยัน
                ยอดโอนจะมีจุดทศนิยม (เช่น ฿500.37) เพื่อระบุรายการได้อย่างแม่นยำ
            </p>
        </div>
    </div>

    {{-- รายการบัญชีธนาคาร --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">🏦 บัญชีรับชำระเงิน</h2>
        </div>

        @if($accounts->count() > 0)
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            @foreach($accounts as $account)
            <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition {{ !$account->is_active ? 'opacity-50' : '' }}">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        {{-- ไอคอนธนาคาร --}}
                        <div class="w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-lg font-bold">
                            {{ substr($account->bank_code, 0, 2) }}
                        </div>

                        <div>
                            <div class="flex items-center space-x-2">
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{ $account->bank_name }}
                                </p>
                                @if($account->is_default)
                                    <span class="px-2 py-0.5 text-xs bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300 rounded-full">
                                        ⭐ หลัก
                                    </span>
                                @endif
                                @if(!$account->is_active)
                                    <span class="px-2 py-0.5 text-xs bg-gray-100 text-gray-500 dark:bg-gray-600 dark:text-gray-400 rounded-full">
                                        ปิดใช้งาน
                                    </span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $account->account_name }} | {{ $account->account_number }}
                                @if($account->branch)
                                    | สาขา {{ $account->branch }}
                                @endif
                            </p>
                            <div class="flex items-center space-x-3 mt-1">
                                @if($account->hasPromptpay())
                                    <span class="text-xs text-purple-600 dark:text-purple-400">
                                        💜 PromptPay: {{ $account->masked_promptpay_id }}
                                    </span>
                                @endif
                                @if($account->sms_checker_enabled)
                                    <span class="text-xs text-blue-600 dark:text-blue-400">
                                        📱 SMS Checker
                                    </span>
                                @endif
                                @if($account->qr_image)
                                    <span class="text-xs text-green-600 dark:text-green-400">
                                        📷 QR Code
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center space-x-2">
                        @if(!$account->is_default && $account->is_active)
                        <form method="POST" action="{{ route('admin.payment-bank-accounts.set-default', $account) }}">
                            @csrf
                            <button type="submit" class="px-2 py-1 text-xs bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300 rounded hover:bg-yellow-200 dark:hover:bg-yellow-800 transition">
                                ตั้งหลัก
                            </button>
                        </form>
                        @endif

                        <form method="POST" action="{{ route('admin.payment-bank-accounts.toggle', $account) }}">
                            @csrf
                            <button type="submit" class="px-2 py-1 text-xs {{ $account->is_active ? 'bg-gray-100 text-gray-600 dark:bg-gray-600 dark:text-gray-300' : 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' }} rounded hover:opacity-80 transition">
                                {{ $account->is_active ? 'ปิด' : 'เปิด' }}
                            </button>
                        </form>

                        <a href="{{ route('admin.payment-bank-accounts.edit', $account) }}"
                           class="px-2 py-1 text-xs bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300 rounded hover:bg-blue-200 dark:hover:bg-blue-800 transition">
                            แก้ไข
                        </a>

                        <form method="POST" action="{{ route('admin.payment-bank-accounts.destroy', $account) }}"
                              onsubmit="return confirm('ต้องการลบบัญชีนี้?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-2 py-1 text-xs bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300 rounded hover:bg-red-200 dark:hover:bg-red-800 transition">
                                ลบ
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
            <p class="text-lg mb-2">ยังไม่มีบัญชีธนาคาร</p>
            <a href="{{ route('admin.payment-bank-accounts.create') }}"
               class="text-blue-600 dark:text-blue-400 hover:underline">
                + เพิ่มบัญชีธนาคารตอนนี้
            </a>
        </div>
        @endif
    </div>

    {{-- คำอธิบายลูกค้า --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">💡 ข้อความอธิบายลูกค้า</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            ข้อความนี้จะแสดงให้ลูกค้าเห็นในหน้าชำระเงิน อธิบายว่าทำไมยอดโอนมีจุดทศนิยม
        </p>
        @include('components.sms-payment-explanation')
    </div>
</div>
@endsection
