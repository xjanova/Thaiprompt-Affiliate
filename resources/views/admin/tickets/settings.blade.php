@extends('layouts.admin-v3')

@section('title', 'Ticket System Settings')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-cyan-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Alpine.js Language Switcher -->
        <div x-data="{ open: false, currentLang: '🇹🇭 ไทย' }" class="flex justify-end mb-6">
            <div class="relative">
                <button @click="open = !open"
                        class="flex items-center space-x-2 px-4 py-2 bg-white dark:bg-gray-800 rounded-xl shadow-lg hover:shadow-xl transition-all border border-gray-200 dark:border-gray-700">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300" x-text="currentLang"></span>
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open"
                     @click.away="open = false"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 transform scale-100"
                     x-transition:leave-end="opacity-0 transform scale-95"
                     class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 py-2 z-50">
                    <a href="#" @click.prevent="currentLang = '🇹🇭 ไทย'; open = false"
                       class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-gray-700">
                        🇹🇭 ไทย
                    </a>
                    <a href="#" @click.prevent="currentLang = '🇬🇧 English'; open = false"
                       class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-gray-700">
                        🇬🇧 English
                    </a>
                    <a href="#" @click.prevent="currentLang = '🇨🇳 中文'; open = false"
                       class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-gray-700">
                        🇨🇳 中文
                    </a>
                    <a href="#" @click.prevent="currentLang = '🇯🇵 日本語'; open = false"
                       class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-gray-700">
                        🇯🇵 日本語
                    </a>
                </div>
            </div>
        </div>

        <!-- Gradient Header with Back Button -->
        <div class="relative overflow-hidden bg-gradient-to-br from-blue-600 via-cyan-600 to-teal-600 rounded-3xl shadow-2xl p-8 mb-8">
            <div class="absolute inset-0 bg-black/10"></div>
            <div class="absolute top-0 right-0 -mt-20 -mr-20 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>

            <div class="relative">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center">
                        <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center mr-4">
                            <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-4xl font-bold text-white mb-2" data-translate>
                                ตั้งค่าระบบ Ticket
                            </h1>
                            <p class="text-blue-100" data-translate>
                                กำหนดค่าและปรับแต่งระบบ Support Ticket
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('admin.tickets.index') }}"
                       class="px-6 py-3 bg-white/20 backdrop-blur-sm hover:bg-white/30 rounded-xl transition-all text-white font-semibold flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        <span data-translate>กลับหน้าหลัก</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Form -->
        <form action="{{ route('admin.tickets.settings.update') }}" method="POST" class="space-y-8">
            @csrf
            @method('PUT')

            <!-- General Settings Section -->
            <div class="relative overflow-hidden bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-600 rounded-2xl shadow-2xl p-8">
                <div class="absolute inset-0 bg-black/10"></div>
                <div class="relative">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center mr-4">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-white" data-translate>
                            การตั้งค่าทั่วไป
                        </h2>
                    </div>

                    <div class="space-y-6">
                        <div>
                            <label class="block text-white font-semibold mb-3 text-sm" data-translate>
                                ความสำคัญเริ่มต้นสำหรับ Ticket ใหม่
                            </label>
                            <select name="default_priority" id="default_priority"
                                    class="w-full px-5 py-4 rounded-xl border-2 border-white/30 bg-white/10 backdrop-blur-sm text-white focus:ring-4 focus:ring-white/30 focus:border-white/50 transition-all">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-5">
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" name="auto_assign_enabled" id="auto_assign_enabled" value="1" checked
                                           class="w-5 h-5 rounded border-2 border-white/50 text-indigo-600 focus:ring-4 focus:ring-white/30 bg-white/20">
                                    <span class="ml-3 text-white font-medium" data-translate>เปิดใช้การมอบหมายอัตโนมัติ</span>
                                </label>
                            </div>

                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-5">
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" name="sla_enabled" id="sla_enabled" value="1" checked
                                           class="w-5 h-5 rounded border-2 border-white/50 text-indigo-600 focus:ring-4 focus:ring-white/30 bg-white/20">
                                    <span class="ml-3 text-white font-medium" data-translate>เปิดใช้นโยบาย SLA</span>
                                </label>
                            </div>

                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-5">
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" name="allow_user_reopen" id="allow_user_reopen" value="1" checked
                                           class="w-5 h-5 rounded border-2 border-white/50 text-indigo-600 focus:ring-4 focus:ring-white/30 bg-white/20">
                                    <span class="ml-3 text-white font-medium" data-translate>อนุญาตให้เปิด Ticket ใหม่</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Email Notifications Section -->
            <div class="relative overflow-hidden bg-gradient-to-br from-green-500 via-emerald-500 to-teal-600 rounded-2xl shadow-2xl p-8">
                <div class="absolute inset-0 bg-black/10"></div>
                <div class="relative">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center mr-4">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-white" data-translate>
                            การแจ้งเตือนทางอีเมล
                        </h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-5">
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" name="notify_admin_new_ticket" id="notify_admin_new_ticket" value="1" checked
                                       class="w-5 h-5 rounded border-2 border-white/50 text-green-600 focus:ring-4 focus:ring-white/30 bg-white/20">
                                <span class="ml-3 text-white font-medium" data-translate>แจ้งเตือน Admin เมื่อมี Ticket ใหม่</span>
                            </label>
                        </div>

                        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-5">
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" name="notify_user_reply" id="notify_user_reply" value="1" checked
                                       class="w-5 h-5 rounded border-2 border-white/50 text-green-600 focus:ring-4 focus:ring-white/30 bg-white/20">
                                <span class="ml-3 text-white font-medium" data-translate>แจ้งเตือนผู้ใช้เมื่อมีข้อความตอบกลับ</span>
                            </label>
                        </div>

                        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-5">
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" name="notify_user_status_change" id="notify_user_status_change" value="1" checked
                                       class="w-5 h-5 rounded border-2 border-white/50 text-green-600 focus:ring-4 focus:ring-white/30 bg-white/20">
                                <span class="ml-3 text-white font-medium" data-translate>แจ้งเตือนเมื่อสถานะเปลี่ยน</span>
                            </label>
                        </div>

                        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-5">
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" name="notify_sla_breach" id="notify_sla_breach" value="1" checked
                                       class="w-5 h-5 rounded border-2 border-white/50 text-green-600 focus:ring-4 focus:ring-white/30 bg-white/20">
                                <span class="ml-3 text-white font-medium" data-translate>แจ้งเตือนเมื่อฝ่าฝืน SLA</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- File Upload Settings Section -->
            <div class="relative overflow-hidden bg-gradient-to-br from-orange-500 via-amber-500 to-yellow-600 rounded-2xl shadow-2xl p-8">
                <div class="absolute inset-0 bg-black/10"></div>
                <div class="relative">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center mr-4">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-white" data-translate>
                            การตั้งค่าอัปโหลดไฟล์
                        </h2>
                    </div>

                    <div class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-white font-semibold mb-3 text-sm" data-translate>
                                    ขนาดไฟล์สูงสุด (MB)
                                </label>
                                <input type="number" name="max_file_size" id="max_file_size" value="10" min="1" max="100"
                                       class="w-full px-5 py-4 rounded-xl border-2 border-white/30 bg-white/10 backdrop-blur-sm text-white placeholder-white/60 focus:ring-4 focus:ring-white/30 focus:border-white/50 transition-all">
                            </div>

                            <div>
                                <label class="block text-white font-semibold mb-3 text-sm" data-translate>
                                    นามสกุลไฟล์ที่อนุญาต (คั่นด้วยคอมม่า)
                                </label>
                                <input type="text" name="allowed_extensions" id="allowed_extensions" value="jpg,jpeg,png,gif,pdf,doc,docx,txt,zip"
                                       class="w-full px-5 py-4 rounded-xl border-2 border-white/30 bg-white/10 backdrop-blur-sm text-white placeholder-white/60 focus:ring-4 focus:ring-white/30 focus:border-white/50 transition-all">
                            </div>
                        </div>

                        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-5">
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" name="scan_uploads" id="scan_uploads" value="1"
                                       class="w-5 h-5 rounded border-2 border-white/50 text-orange-600 focus:ring-4 focus:ring-white/30 bg-white/20">
                                <span class="ml-3 text-white font-medium" data-translate>สแกนไวรัสไฟล์ที่อัปโหลด (ต้องติดตั้ง virus scanner)</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Knowledge Base Settings Section -->
            <div class="relative overflow-hidden bg-gradient-to-br from-purple-500 via-pink-500 to-rose-600 rounded-2xl shadow-2xl p-8">
                <div class="absolute inset-0 bg-black/10"></div>
                <div class="relative">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center mr-4">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-white" data-translate>
                            การตั้งค่า Knowledge Base
                        </h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-5">
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" name="suggest_kb_articles" id="suggest_kb_articles" value="1" checked
                                       class="w-5 h-5 rounded border-2 border-white/50 text-purple-600 focus:ring-4 focus:ring-white/30 bg-white/20">
                                <span class="ml-3 text-white font-medium" data-translate>แนะนำบทความ KB เมื่อสร้าง Ticket</span>
                            </label>
                        </div>

                        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-5">
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" name="public_kb_access" id="public_kb_access" value="1" checked
                                       class="w-5 h-5 rounded border-2 border-white/50 text-purple-600 focus:ring-4 focus:ring-white/30 bg-white/20">
                                <span class="ml-3 text-white font-medium" data-translate>อนุญาตให้เข้าถึง KB แบบสาธารณะ</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Business Hours Section -->
            <div class="relative overflow-hidden bg-gradient-to-br from-blue-500 via-indigo-500 to-purple-600 rounded-2xl shadow-2xl p-8">
                <div class="absolute inset-0 bg-black/10"></div>
                <div class="relative">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center mr-4">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-white" data-translate>
                            เวลาทำการ
                        </h2>
                    </div>

                    <div class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-white font-semibold mb-3 text-sm" data-translate>
                                    เวลาเริ่มทำงาน
                                </label>
                                <input type="time" name="business_hours_start" id="business_hours_start" value="09:00"
                                       class="w-full px-5 py-4 rounded-xl border-2 border-white/30 bg-white/10 backdrop-blur-sm text-white focus:ring-4 focus:ring-white/30 focus:border-white/50 transition-all">
                            </div>

                            <div>
                                <label class="block text-white font-semibold mb-3 text-sm" data-translate>
                                    เวลาเลิกงาน
                                </label>
                                <input type="time" name="business_hours_end" id="business_hours_end" value="18:00"
                                       class="w-full px-5 py-4 rounded-xl border-2 border-white/30 bg-white/10 backdrop-blur-sm text-white focus:ring-4 focus:ring-white/30 focus:border-white/50 transition-all">
                            </div>
                        </div>

                        <div>
                            <label class="block text-white font-semibold mb-4 text-sm" data-translate>
                                วันทำงาน
                            </label>
                            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">
                                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4">
                                    <label class="flex items-center cursor-pointer">
                                        <input type="checkbox" name="working_days[]" value="1" id="monday" checked
                                               class="w-5 h-5 rounded border-2 border-white/50 text-blue-600 focus:ring-4 focus:ring-white/30 bg-white/20">
                                        <span class="ml-2 text-white text-sm font-medium">Mon</span>
                                    </label>
                                </div>
                                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4">
                                    <label class="flex items-center cursor-pointer">
                                        <input type="checkbox" name="working_days[]" value="2" id="tuesday" checked
                                               class="w-5 h-5 rounded border-2 border-white/50 text-blue-600 focus:ring-4 focus:ring-white/30 bg-white/20">
                                        <span class="ml-2 text-white text-sm font-medium">Tue</span>
                                    </label>
                                </div>
                                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4">
                                    <label class="flex items-center cursor-pointer">
                                        <input type="checkbox" name="working_days[]" value="3" id="wednesday" checked
                                               class="w-5 h-5 rounded border-2 border-white/50 text-blue-600 focus:ring-4 focus:ring-white/30 bg-white/20">
                                        <span class="ml-2 text-white text-sm font-medium">Wed</span>
                                    </label>
                                </div>
                                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4">
                                    <label class="flex items-center cursor-pointer">
                                        <input type="checkbox" name="working_days[]" value="4" id="thursday" checked
                                               class="w-5 h-5 rounded border-2 border-white/50 text-blue-600 focus:ring-4 focus:ring-white/30 bg-white/20">
                                        <span class="ml-2 text-white text-sm font-medium">Thu</span>
                                    </label>
                                </div>
                                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4">
                                    <label class="flex items-center cursor-pointer">
                                        <input type="checkbox" name="working_days[]" value="5" id="friday" checked
                                               class="w-5 h-5 rounded border-2 border-white/50 text-blue-600 focus:ring-4 focus:ring-white/30 bg-white/20">
                                        <span class="ml-2 text-white text-sm font-medium">Fri</span>
                                    </label>
                                </div>
                                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4">
                                    <label class="flex items-center cursor-pointer">
                                        <input type="checkbox" name="working_days[]" value="6" id="saturday"
                                               class="w-5 h-5 rounded border-2 border-white/50 text-blue-600 focus:ring-4 focus:ring-white/30 bg-white/20">
                                        <span class="ml-2 text-white text-sm font-medium">Sat</span>
                                    </label>
                                </div>
                                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4">
                                    <label class="flex items-center cursor-pointer">
                                        <input type="checkbox" name="working_days[]" value="0" id="sunday"
                                               class="w-5 h-5 rounded border-2 border-white/50 text-blue-600 focus:ring-4 focus:ring-white/30 bg-white/20">
                                        <span class="ml-2 text-white text-sm font-medium">Sun</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="flex justify-end">
                <button type="submit"
                        class="relative overflow-hidden group px-10 py-4 bg-gradient-to-r from-green-600 via-emerald-600 to-teal-600 text-white font-bold text-lg rounded-2xl shadow-2xl hover:shadow-3xl transition-all transform hover:scale-105">
                    <div class="absolute inset-0 bg-gradient-to-r from-green-700 via-emerald-700 to-teal-700 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <div class="relative flex items-center">
                        <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                        </svg>
                        <span data-translate>บันทึกการตั้งค่า</span>
                    </div>
                </button>
            </div>
        </form>

    </div>
</div>

<!-- Alpine.js -->
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
@endsection
