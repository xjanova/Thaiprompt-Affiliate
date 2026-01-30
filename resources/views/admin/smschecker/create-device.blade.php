@extends('layouts.admin-v3')

@section('title', 'เพิ่มอุปกรณ์ SMS Checker')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                ➕ เพิ่มอุปกรณ์ SMS Checker
            </h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">
                ลงทะเบียนอุปกรณ์ Android ใหม่เพื่อรับ SMS notifications
            </p>
        </div>
        <a href="{{ route('admin.smschecker.devices') }}"
           class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
            ← กลับ
        </a>
    </div>

    {{-- ฟอร์ม --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 max-w-2xl">
        <form method="POST" action="{{ route('admin.smschecker.device-store') }}">
            @csrf

            <div class="space-y-6">
                {{-- ชื่ออุปกรณ์ --}}
                <div>
                    <label for="device_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        ชื่ออุปกรณ์ <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="device_name" name="device_name"
                           value="{{ old('device_name') }}" required
                           placeholder='เช่น "Samsung Galaxy S24" หรือ "เครื่องสำนักงาน"'
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 @error('device_name') border-red-500 @enderror">
                    @error('device_name')
                        <p class="text-red-600 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- เจ้าของ (ไม่บังคับ) --}}
                <div>
                    <label for="user_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        User ID เจ้าของ (ไม่บังคับ)
                    </label>
                    <input type="number" id="user_id" name="user_id"
                           value="{{ old('user_id') }}"
                           placeholder="เช่น 1"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 @error('user_id') border-red-500 @enderror">
                    @error('user_id')
                        <p class="text-red-600 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        ระบุ User ID ของผู้ใช้ที่เป็นเจ้าของอุปกรณ์ (ไม่จำเป็นต้องระบุ)
                    </p>
                </div>

                {{-- ข้อมูลที่จะสร้างอัตโนมัติ --}}
                <div class="bg-blue-50 dark:bg-blue-900/30 rounded-lg p-4">
                    <h3 class="text-sm font-medium text-blue-700 dark:text-blue-300 mb-2">ข้อมูลที่สร้างอัตโนมัติ</h3>
                    <ul class="text-sm text-blue-600 dark:text-blue-400 space-y-1">
                        <li>• <strong>Device ID</strong> - SMSCHK-XXXXXXXX (สุ่มอัตโนมัติ)</li>
                        <li>• <strong>API Key</strong> - 64-char hex string (สุ่มอัตโนมัติ)</li>
                        <li>• <strong>Secret Key</strong> - 64-char hex string (สุ่มอัตโนมัติ)</li>
                    </ul>
                    <p class="text-xs text-blue-500 dark:text-blue-400 mt-2">
                        หลังสร้างเสร็จ นำ API Key และ Secret Key ไปตั้งค่าในแอพ Android SMS Checker
                    </p>
                </div>

                {{-- ปุ่มบันทึก --}}
                <div class="flex items-center space-x-3">
                    <button type="submit"
                            class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                        📱 สร้างอุปกรณ์
                    </button>
                    <a href="{{ route('admin.smschecker.devices') }}"
                       class="px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                        ยกเลิก
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
