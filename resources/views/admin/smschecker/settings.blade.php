@extends('layouts.admin-v3')

@section('title', 'ตั้งค่า SMS Payment Checker')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                ⚙️ ตั้งค่า SMS Payment Checker
            </h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">
                ตั้งค่าการเชื่อมต่อและคู่มือการใช้งานแอพ SmsChecker
            </p>
        </div>
        <a href="{{ route('admin.smschecker.index') }}"
           class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
            ← กลับ Dashboard
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Getting Started --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-blue-500 to-indigo-600">
                <h2 class="text-lg font-semibold text-white flex items-center">
                    ⚡ เริ่มต้นใช้งาน
                </h2>
            </div>
            <div class="p-6 space-y-6">
                {{-- Step 1 --}}
                <div class="flex gap-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                        <span class="text-blue-600 dark:text-blue-400 font-bold">1</span>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-1">ดาวน์โหลดแอพ SmsChecker</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">ติดตั้งแอพ SmsChecker บนมือถือ Android ที่รับ SMS จากธนาคาร</p>
                        <a href="https://github.com/xjanova/SmsChecker/releases/latest" target="_blank"
                           class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-xl transition-colors">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                            </svg>
                            ดาวน์โหลด APK
                        </a>
                    </div>
                </div>

                {{-- Step 2 --}}
                <div class="flex gap-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                        <span class="text-blue-600 dark:text-blue-400 font-bold">2</span>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-1">สร้างอุปกรณ์ใหม่</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">สร้าง Device ใหม่เพื่อรับ API Key และ QR Code</p>
                        <a href="{{ route('admin.smschecker.device-create') }}"
                           class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition-colors">
                            ➕ สร้างอุปกรณ์ใหม่
                        </a>
                    </div>
                </div>

                {{-- Step 3 --}}
                <div class="flex gap-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                        <span class="text-blue-600 dark:text-blue-400 font-bold">3</span>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-1">สแกน QR Code</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">เปิดแอพ SmsChecker แล้วสแกน QR Code ที่แสดงหลังสร้าง Device เพื่อเชื่อมต่ออัตโนมัติ</p>
                    </div>
                </div>

                {{-- Step 4 --}}
                <div class="flex gap-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                        <span class="text-blue-600 dark:text-blue-400 font-bold">4</span>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-1">ให้สิทธิ์อ่าน SMS</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">อนุญาตให้แอพอ่าน SMS เพื่อตรวจจับการแจ้งเตือนจากธนาคาร</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Server Info --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                    🖥️ ข้อมูล Server
                </h2>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Server URL</label>
                    <div class="flex items-center gap-2">
                        <code class="flex-1 px-4 py-3 bg-gray-100 dark:bg-gray-700 rounded-xl text-sm font-mono text-gray-900 dark:text-white overflow-x-auto">
                            {{ config('app.url') }}
                        </code>
                        <button onclick="navigator.clipboard.writeText('{{ config('app.url') }}'); alert('คัดลอกแล้ว!')"
                                class="px-3 py-3 bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 rounded-xl transition-colors"
                                title="คัดลอก">
                            📋
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">API Endpoint</label>
                    <code class="block w-full px-4 py-3 bg-gray-100 dark:bg-gray-700 rounded-xl text-sm font-mono text-gray-900 dark:text-white overflow-x-auto">
                        {{ config('app.url') }}/api/v1/sms-payment/notify
                    </code>
                </div>
            </div>
        </div>
    </div>

    {{-- Current Settings --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">📋 การตั้งค่าปัจจุบัน</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">การตั้งค่าถูกจัดเก็บใน <code>config/smschecker.php</code></p>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">ระบบ</h4>
                    <p class="mt-1 text-lg font-semibold {{ $settings['enabled'] ? 'text-green-600' : 'text-red-600' }}">
                        {{ $settings['enabled'] ? '✅ เปิดใช้งาน' : '❌ ปิดใช้งาน' }}
                    </p>
                </div>

                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">อายุ Unique Amount</h4>
                    <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ $settings['unique_amount_expiry'] }} นาที</p>
                </div>

                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Sync Interval</h4>
                    <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ $settings['sync_interval'] }} วินาที</p>
                </div>

                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">โหมดอนุมัติเริ่มต้น</h4>
                    <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ ucfirst($settings['default_approval_mode']) }}</p>
                </div>

                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">อนุมัติอัตโนมัติ</h4>
                    <p class="mt-1 text-lg font-semibold {{ $settings['auto_confirm_matched'] ? 'text-green-600' : 'text-orange-600' }}">
                        {{ $settings['auto_confirm_matched'] ? '✅ เปิด' : '⚠️ ต้องอนุมัติเอง' }}
                    </p>
                </div>

                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Rate Limit</h4>
                    <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ $settings['rate_limit_per_minute'] }} ครั้ง/นาที</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Supported Banks --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">🏦 ธนาคารที่รองรับ</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">แอพจะตรวจจับ SMS แจ้งเงินเข้าจากธนาคารเหล่านี้อัตโนมัติ</p>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                @foreach($supportedBanks as $code => $name)
                <div class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 dark:bg-gray-700/50">
                    <div class="w-10 h-10 rounded-full bg-white dark:bg-gray-600 flex items-center justify-center shadow text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">
                        {{ strtoupper(substr($code, 0, 3)) }}
                    </div>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $name }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
