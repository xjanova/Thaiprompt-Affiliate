@extends('layouts.admin')

@section('title', 'ตั้งค่าระบบคริปโต')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-600 via-pink-600 to-red-600 rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-40 h-40 bg-white opacity-10 rounded-full"></div>
        <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-32 h-32 bg-white opacity-10 rounded-full"></div>
        <div class="relative z-10">
            <h1 class="text-3xl font-bold mb-2">⚙️ ตั้งค่าระบบคริปโต</h1>
            <p class="text-pink-100">กำหนดค่าระบบจัดการสกุลเงินดิจิทัล</p>
        </div>
    </div>

    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="bg-green-100 dark:bg-green-900 border-l-4 border-green-500 text-green-700 dark:text-green-100 p-4 rounded-lg shadow-md" role="alert">
            <div class="flex items-center">
                <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="font-semibold">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 dark:bg-red-900 border-l-4 border-red-500 text-red-700 dark:text-red-100 p-4 rounded-lg shadow-md" role="alert">
            <div class="flex items-center">
                <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="font-semibold">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    <!-- Settings Form -->
    <form action="{{ route('admin.crypto.settings.update') }}" method="POST" class="space-y-6">
        @csrf

        <!-- Auto Approval Settings -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <div class="flex items-center mb-6">
                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center text-white text-2xl mr-4">
                    ✓
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">การอนุมัติอัตโนมัติ</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ตั้งค่าการอนุมัติการถอนเงินอัตโนมัติ</p>
                </div>
            </div>

            <div class="space-y-4">
                <!-- Enable Auto Approval -->
                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div>
                        <label class="text-lg font-semibold text-gray-900 dark:text-white">เปิดใช้งานการอนุมัติอัตโนมัติ</label>
                        <p class="text-sm text-gray-600 dark:text-gray-400">อนุมัติคำขอถอนเงินที่อยู่ในขีดจำกัดโดยอัตโนมัติ</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="auto_approve_enabled" value="1" {{ ($settings['auto_approve_enabled'] ?? true) ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:start-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:border-gray-600 peer-checked:bg-green-600"></div>
                    </label>
                </div>

                <!-- Max Auto Approve Amount -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        จำนวนเงินสูงสุดที่อนุมัติอัตโนมัติ (THB)
                    </label>
                    <input type="number" name="max_auto_approve_amount" value="{{ $settings['max_auto_approve_amount'] ?? 100000 }}"
                           min="0" step="1000" required
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white">
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        คำขอถอนที่มีมูลค่าเกินกว่านี้จะต้องได้รับการอนุมัติด้วยตนเอง
                    </p>
                </div>
            </div>
        </div>

        <!-- Scanning & Processing Settings -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <div class="flex items-center mb-6">
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center text-white text-2xl mr-4">
                    🔄
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">การสแกนและประมวลผล</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ความถี่ในการตรวจสอบ Blockchain</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Deposit Scan Interval -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        ช่วงเวลาการสแกนฝากเงิน (วินาที)
                    </label>
                    <input type="number" name="deposit_scan_interval" value="{{ $settings['deposit_scan_interval'] ?? 30 }}"
                           min="10" max="300" step="10" required
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        ระบบจะสแกน Blockchain เพื่อตรวจหาการฝากเงินทุก ๆ (วินาที)
                    </p>
                </div>

                <!-- Withdrawal Process Interval -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        ช่วงเวลาการประมวลผลถอนเงิน (วินาที)
                    </label>
                    <input type="number" name="withdrawal_process_interval" value="{{ $settings['withdrawal_process_interval'] ?? 60 }}"
                           min="30" max="600" step="10" required
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        ระบบจะประมวลผลคำขอถอนเงินที่รออนุมัติทุก ๆ (วินาที)
                    </p>
                </div>
            </div>
        </div>

        <!-- Important Notice -->
        <div class="bg-yellow-50 dark:bg-yellow-900 border-l-4 border-yellow-500 p-6 rounded-lg">
            <div class="flex items-start">
                <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400 mr-3 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div>
                    <h3 class="text-lg font-bold text-yellow-800 dark:text-yellow-200 mb-2">⚠️ คำเตือนสำคัญ</h3>
                    <ul class="list-disc list-inside text-sm text-yellow-700 dark:text-yellow-300 space-y-1">
                        <li>การตั้งค่าช่วงเวลาสแกนที่สั้นเกินไปอาจทำให้เกิดภาระต่อ Blockchain Node</li>
                        <li>การอนุมัติอัตโนมัติควรมีขีดจำกัดที่เหมาะสมเพื่อความปลอดภัย</li>
                        <li>แนะนำให้ตรวจสอบ Transaction Logs เป็นประจำ</li>
                        <li>การเปลี่ยนแปลงการตั้งค่าจะมีผลทันทีหลังจากบันทึก</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="flex justify-end gap-4">
            <a href="{{ route('admin.crypto.dashboard') }}"
               class="px-6 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 font-semibold rounded-lg shadow-lg transform hover:scale-105 transition duration-200">
                ยกเลิก
            </a>
            <button type="submit"
                    class="px-8 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-semibold rounded-lg shadow-lg transform hover:scale-105 transition duration-200 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                บันทึกการตั้งค่า
            </button>
        </div>
    </form>

    <!-- Current Settings Info -->
    <div class="bg-gradient-to-r from-indigo-100 to-purple-100 dark:from-indigo-900 dark:to-purple-900 rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">📊 ค่าการตั้งค่าปัจจุบัน</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">อนุมัติอัตโนมัติ</p>
                <p class="text-xl font-bold {{ ($settings['auto_approve_enabled'] ?? true) ? 'text-green-600' : 'text-red-600' }}">
                    {{ ($settings['auto_approve_enabled'] ?? true) ? 'เปิด' : 'ปิด' }}
                </p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">จำนวนสูงสุด</p>
                <p class="text-xl font-bold text-blue-600">
                    ฿{{ number_format($settings['max_auto_approve_amount'] ?? 100000) }}
                </p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">สแกนฝาก</p>
                <p class="text-xl font-bold text-purple-600">
                    {{ $settings['deposit_scan_interval'] ?? 30 }}s
                </p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">ประมวลผลถอน</p>
                <p class="text-xl font-bold text-pink-600">
                    {{ $settings['withdrawal_process_interval'] ?? 60 }}s
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
