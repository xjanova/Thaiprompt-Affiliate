@extends('layouts.admin-v3')

@section('title', 'Floating Tools Settings')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                การตั้งค่า Floating Tools
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                จัดการปุ่มลอยและเครื่องมือต่างๆ ที่แสดงบนหน้าเว็บ
            </p>
        </div>

        <!-- Success Message -->
        @if(session('success'))
        <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
        @endif

        <!-- Settings Form -->
        <form method="POST" action="{{ route('admin.floating-tools.update') }}" class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            @csrf

            <div class="space-y-6">
                <!-- Enable/Disable Features -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        เครื่องมือที่เปิดใช้งาน
                    </h3>

                    <div class="space-y-3">
                        <!-- Dark Mode Toggle -->
                        <label class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                            <input
                                type="checkbox"
                                name="floating_dark_mode_enabled"
                                value="1"
                                {{ $settings['floating_dark_mode_enabled'] ? 'checked' : '' }}
                                class="w-5 h-5 text-green-600 bg-gray-100 border-gray-300 rounded focus:ring-green-500"
                            >
                            <div class="ml-3">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    🌙 Dark Mode Toggle
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    ปุ่มสลับโหมดมืด/สว่าง
                                </div>
                            </div>
                        </label>

                        <!-- Scroll to Top -->
                        <label class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                            <input
                                type="checkbox"
                                name="floating_scroll_top_enabled"
                                value="1"
                                {{ $settings['floating_scroll_top_enabled'] ? 'checked' : '' }}
                                class="w-5 h-5 text-green-600 bg-gray-100 border-gray-300 rounded focus:ring-green-500"
                            >
                            <div class="ml-3">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    ⬆️ Scroll to Top
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    ปุ่มเลื่อนขึ้นด้านบน
                                </div>
                            </div>
                        </label>

                        <!-- ChatBot -->
                        <label class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                            <input
                                type="checkbox"
                                name="floating_chatbot_enabled"
                                value="1"
                                {{ $settings['floating_chatbot_enabled'] ? 'checked' : '' }}
                                class="w-5 h-5 text-green-600 bg-gray-100 border-gray-300 rounded focus:ring-green-500"
                            >
                            <div class="ml-3">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    💬 AI ChatBot
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    ปุ่มเปิดแชทบอท AI
                                </div>
                            </div>
                        </label>

                        <!-- Language Switcher -->
                        <label class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                            <input
                                type="checkbox"
                                name="floating_language_enabled"
                                value="1"
                                {{ $settings['floating_language_enabled'] ? 'checked' : '' }}
                                class="w-5 h-5 text-green-600 bg-gray-100 border-gray-300 rounded focus:ring-green-500"
                            >
                            <div class="ml-3">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    🌐 Language Switcher
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    ปุ่มสลับภาษา
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- FAB Icon -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ไอคอนปุ่มหลัก (FAB Icon)
                    </label>
                    <input
                        type="text"
                        name="floating_fab_icon"
                        value="{{ $settings['floating_fab_icon'] }}"
                        maxlength="10"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white"
                        placeholder="⚙️"
                    >
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        ใส่ Emoji หรือ ข้อความสั้นๆ (เช่น ⚙️ 🛠️ ⭐ 🎯)
                    </p>
                </div>

                <!-- Position -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ตำแหน่งปุ่ม
                    </label>
                    <select
                        name="floating_fab_position"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white"
                    >
                        <option value="bottom-right" {{ $settings['floating_fab_position'] === 'bottom-right' ? 'selected' : '' }}>
                            มุมขวาล่าง
                        </option>
                        <option value="bottom-left" {{ $settings['floating_fab_position'] === 'bottom-left' ? 'selected' : '' }}>
                            มุมซ้ายล่าง
                        </option>
                    </select>
                </div>

                <!-- Color Theme -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        สีธีม
                    </label>
                    <div class="grid grid-cols-5 gap-3">
                        @foreach(['green' => 'เขียว', 'blue' => 'น้ำเงิน', 'purple' => 'ม่วง', 'red' => 'แดง', 'orange' => 'ส้ม'] as $color => $label)
                        <label class="flex flex-col items-center p-3 border-2 rounded-lg cursor-pointer transition {{ $settings['floating_fab_color'] === $color ? 'border-'.$color.'-500 bg-'.$color.'-50' : 'border-gray-300 hover:border-'.$color.'-300' }}">
                            <input
                                type="radio"
                                name="floating_fab_color"
                                value="{{ $color }}"
                                {{ $settings['floating_fab_color'] === $color ? 'checked' : '' }}
                                class="sr-only"
                            >
                            <div class="w-8 h-8 rounded-full bg-{{ $color }}-500 mb-1"></div>
                            <span class="text-xs text-gray-700 dark:text-gray-300">{{ $label }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex items-center justify-end space-x-3 pt-6 border-t dark:border-gray-700">
                    <a
                        href="{{ route('admin.dashboard') }}"
                        class="px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                    >
                        ยกเลิก
                    </a>
                    <button
                        type="submit"
                        class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition"
                    >
                        บันทึกการตั้งค่า
                    </button>
                </div>
            </div>
        </form>

        <!-- Preview -->
        <div class="mt-8 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-3">
                💡 วิธีใช้งาน
            </h3>
            <ul class="space-y-2 text-sm text-blue-800 dark:text-blue-200">
                <li>• เลื่อนเมาส์ไปที่ปุ่มลอย (มุมขวาล่าง) เพื่อดูเมนูเครื่องมือ</li>
                <li>• ปุ่มจะโผล่ออกมาเมื่อ hover และหายไปเมื่อเมาส์ออก</li>
                <li>• ปุ่ม Scroll to Top จะแสดงเมื่อเลื่อนหน้าลงไปมากกว่า 300px</li>
                <li>• สามารถเปิด/ปิดเครื่องมือแต่ละตัวได้อิสระ</li>
            </ul>
        </div>
    </div>
</div>
@endsection
