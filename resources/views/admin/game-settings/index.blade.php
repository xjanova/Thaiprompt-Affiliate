@extends('layouts.admin')

@section('title', 'ตั้งค่าเกม')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
            ⚙️ ตั้งค่าเกม
        </h1>
        <p class="text-gray-600 dark:text-gray-400">
            จัดการการตั้งค่าเซิฟเวอร์เกม IP, Port และคอนฟิกต่างๆ
        </p>
    </div>

    {{-- Success/Error Messages --}}
    @if(session('success'))
        <div class="mb-6 bg-green-100 dark:bg-green-900/30 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-400 px-6 py-4 rounded-lg flex items-center">
            <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-400 px-6 py-4 rounded-lg flex items-center">
            <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- Settings Form --}}
    <form action="{{ route('admin.game-settings.update') }}" method="POST">
        @csrf
        @method('PUT')

        @foreach($settings as $group => $groupSettings)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden mb-6">
                {{-- Group Header --}}
                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4">
                    <h2 class="text-xl font-bold text-white flex items-center">
                        @if($group === 'snake_io')
                            🐍 Snake.io Configuration
                        @elseif($group === 'general')
                            🎮 General Game Settings
                        @else
                            ⚙️ {{ ucfirst($group) }}
                        @endif
                    </h2>
                </div>

                {{-- Settings Fields --}}
                <div class="p-6 space-y-6">
                    @foreach($groupSettings as $setting)
                        <div class="border-b border-gray-200 dark:border-gray-700 pb-6 last:border-0 last:pb-0">
                            <label for="{{ $setting->key }}" class="block mb-2">
                                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ $setting->description ?? $setting->key }}
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">
                                    ({{ $setting->key }})
                                </span>
                            </label>

                            @if($setting->type === 'boolean')
                                {{-- Boolean Toggle --}}
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input
                                        type="checkbox"
                                        name="{{ $setting->key }}"
                                        value="true"
                                        {{ $setting->value === 'true' ? 'checked' : '' }}
                                        class="sr-only peer"
                                    >
                                    <div class="w-14 h-7 bg-gray-300 dark:bg-gray-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-indigo-600"></div>
                                    <span class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ $setting->value === 'true' ? 'เปิด' : 'ปิด' }}
                                    </span>
                                </label>
                                <input type="hidden" name="{{ $setting->key }}" value="false">

                            @elseif($setting->type === 'integer')
                                {{-- Integer Input --}}
                                <input
                                    type="number"
                                    id="{{ $setting->key }}"
                                    name="{{ $setting->key }}"
                                    value="{{ $setting->value }}"
                                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg
                                           bg-white dark:bg-gray-700
                                           text-gray-900 dark:text-gray-100
                                           focus:ring-2 focus:ring-indigo-500 focus:border-transparent
                                           transition duration-200"
                                    placeholder="กรอกตัวเลข"
                                >

                            @else
                                {{-- String/Text Input --}}
                                <input
                                    type="text"
                                    id="{{ $setting->key }}"
                                    name="{{ $setting->key }}"
                                    value="{{ $setting->value }}"
                                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg
                                           bg-white dark:bg-gray-700
                                           text-gray-900 dark:text-gray-100
                                           focus:ring-2 focus:ring-indigo-500 focus:border-transparent
                                           transition duration-200
                                           @if($setting->key === 'snake_io_server_ip') font-mono @endif"
                                    placeholder="กรอกข้อมูล"
                                >
                            @endif

                            @if($setting->key === 'snake_io_server_ip')
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    💡 ตัวอย่าง: 123.253.62.251 หรือ localhost
                                </p>
                            @elseif($setting->key === 'snake_io_server_port')
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    💡 Port สำหรับ API Server (แนะนำ: 8080, 3000, หรือ 80)
                                </p>
                            @elseif($setting->key === 'snake_io_ws_port')
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    💡 Port สำหรับ WebSocket/Laravel Reverb (default: 6001)
                                </p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        {{-- Submit Button --}}
        <div class="flex justify-end gap-4">
            <a href="{{ route('admin.dashboard') }}"
               class="px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                ยกเลิก
            </a>
            <button type="submit"
                    class="px-8 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-semibold rounded-lg shadow-lg transform hover:scale-105 transition duration-200 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                บันทึกการตั้งค่า
            </button>
        </div>
    </form>

    {{-- Info Box --}}
    <div class="mt-8 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="ml-4">
                <h3 class="text-sm font-semibold text-blue-800 dark:text-blue-300 mb-2">
                    ℹ️ ข้อมูลเพิ่มเติม
                </h3>
                <div class="text-sm text-blue-700 dark:text-blue-400 space-y-1">
                    <p>• การเปลี่ยนแปลงจะมีผลทันทีหลังจากบันทึก</p>
                    <p>• ระบบใช้ Cache 1 ชั่วโมง เพื่อประสิทธิภาพ</p>
                    <p>• สามารถเปลี่ยน IP/Port ได้ตามเซิฟเวอร์ที่ต้องการ</p>
                    <p>• สำหรับ Production ควรใช้ HTTPS และ Port 443</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
