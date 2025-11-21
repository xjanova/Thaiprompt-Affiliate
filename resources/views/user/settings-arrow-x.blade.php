@extends('layouts.user-arrow-x')

@section('title', 'การตั้งค่า')

@section('content')
{{--
/**
 * User Settings - Arrow X Theme
 *
 * หน้าการตั้งค่าผู้ใช้แบบ Arrow X Theme พร้อม:
 * - Preferences (ภาษา, เขตเวลา)
 * - Notifications (Email, LINE, Push)
 * - Privacy (แสดงข้อมูล)
 * - UI Settings (Dark Mode, Theme)
 * - Security (Two-Factor)
 *
 * @version 3.0.0
 * @theme Arrow X
 */
--}}

<div class="space-y-6 pb-20 lg:pb-6" x-data="settingsManager()" x-init="init()">
    {{-- Page Header --}}
    <div class="glass-fusion-card rounded-2xl p-6 shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-extrabold bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 bg-clip-text text-transparent dark:from-blue-400 dark:via-purple-400 dark:to-pink-400">
                    ⚙️ การตั้งค่า
                </h1>
                <p class="mt-1 text-gray-600 dark:text-gray-400">
                    ปรับแต่งประสบการณ์การใช้งานของคุณ
                </p>
            </div>
        </div>
    </div>

    {{-- Success Message --}}
    @if(session('success'))
        <div class="glass-fusion-card rounded-xl p-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-l-4 border-green-500" x-data="{ show: true }" x-show="show" x-transition>
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-600 dark:text-green-400 text-xl mr-3"></i>
                    <span class="font-semibold text-green-800 dark:text-green-200">{{ session('success') }}</span>
                </div>
                <button @click="show = false" class="text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-200">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    @endif

    {{-- Settings Tabs --}}
    <div class="glass-fusion-card rounded-2xl overflow-hidden shadow-lg">
        {{-- Tabs Header --}}
        <div class="flex overflow-x-auto border-b border-gray-200 dark:border-gray-700">
            <button @click="activeTab = 'preferences'"
                    :class="activeTab === 'preferences' ? 'border-purple-500 text-purple-600 dark:text-purple-400' : 'border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'"
                    class="px-6 py-4 border-b-2 font-semibold text-sm whitespace-nowrap transition-colors">
                <i class="fas fa-sliders-h mr-2"></i>
                ตั้งค่าทั่วไป
            </button>
            <button @click="activeTab = 'notifications'"
                    :class="activeTab === 'notifications' ? 'border-purple-500 text-purple-600 dark:text-purple-400' : 'border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'"
                    class="px-6 py-4 border-b-2 font-semibold text-sm whitespace-nowrap transition-colors">
                <i class="fas fa-bell mr-2"></i>
                การแจ้งเตือน
            </button>
            <button @click="activeTab = 'privacy'"
                    :class="activeTab === 'privacy' ? 'border-purple-500 text-purple-600 dark:text-purple-400' : 'border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'"
                    class="px-6 py-4 border-b-2 font-semibold text-sm whitespace-nowrap transition-colors">
                <i class="fas fa-shield-alt mr-2"></i>
                ความเป็นส่วนตัว
            </button>
            <button @click="activeTab = 'appearance'"
                    :class="activeTab === 'appearance' ? 'border-purple-500 text-purple-600 dark:text-purple-400' : 'border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'"
                    class="px-6 py-4 border-b-2 font-semibold text-sm whitespace-nowrap transition-colors">
                <i class="fas fa-palette mr-2"></i>
                รูปแบบ
            </button>
            <button @click="activeTab = 'security'"
                    :class="activeTab === 'security' ? 'border-purple-500 text-purple-600 dark:text-purple-400' : 'border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'"
                    class="px-6 py-4 border-b-2 font-semibold text-sm whitespace-nowrap transition-colors">
                <i class="fas fa-lock mr-2"></i>
                ความปลอดภัย
            </button>
        </div>

        {{-- Tabs Content --}}
        <div class="p-6">
            <form action="{{ route('user.settings.update') }}" method="POST">
                @csrf
                @method('PUT')

                {{-- Preferences Tab --}}
                <div x-show="activeTab === 'preferences'" x-transition>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">
                        ตั้งค่าทั่วไป
                    </h3>

                    <div class="space-y-6">
                        {{-- Language --}}
                        <div class="flex items-start justify-between p-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl">
                            <div class="flex-1">
                                <label class="block font-semibold text-gray-900 dark:text-white mb-1">
                                    <i class="fas fa-language text-blue-500 mr-2"></i>
                                    ภาษา
                                </label>
                                <p class="text-sm text-gray-600 dark:text-gray-400">เลือกภาษาที่ใช้แสดงผลในระบบ</p>
                            </div>
                            <select name="preferred_language"
                                    class="px-4 py-2 border-2 border-gray-300 dark:border-gray-600 rounded-lg
                                           focus:ring-2 focus:ring-purple-500 focus:border-purple-500
                                           dark:bg-gray-800 dark:text-white">
                                <option value="th" {{ ($user->preferred_language ?? 'th') === 'th' ? 'selected' : '' }}>ไทย</option>
                                <option value="en" {{ ($user->preferred_language ?? 'th') === 'en' ? 'selected' : '' }}>English</option>
                            </select>
                        </div>

                        {{-- Timezone --}}
                        <div class="flex items-start justify-between p-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl">
                            <div class="flex-1">
                                <label class="block font-semibold text-gray-900 dark:text-white mb-1">
                                    <i class="fas fa-clock text-blue-500 mr-2"></i>
                                    เขตเวลา
                                </label>
                                <p class="text-sm text-gray-600 dark:text-gray-400">เลือกเขตเวลาสำหรับแสดงผล</p>
                            </div>
                            <select name="timezone"
                                    class="px-4 py-2 border-2 border-gray-300 dark:border-gray-600 rounded-lg
                                           focus:ring-2 focus:ring-purple-500 focus:border-purple-500
                                           dark:bg-gray-800 dark:text-white">
                                <option value="Asia/Bangkok" selected>Bangkok (GMT+7)</option>
                                <option value="Asia/Singapore">Singapore (GMT+8)</option>
                                <option value="Asia/Tokyo">Tokyo (GMT+9)</option>
                            </select>
                        </div>

                        {{-- Currency --}}
                        <div class="flex items-start justify-between p-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl">
                            <div class="flex-1">
                                <label class="block font-semibold text-gray-900 dark:text-white mb-1">
                                    <i class="fas fa-money-bill text-blue-500 mr-2"></i>
                                    สกุลเงิน
                                </label>
                                <p class="text-sm text-gray-600 dark:text-gray-400">สกุลเงินที่ใช้แสดงผล</p>
                            </div>
                            <select name="currency"
                                    class="px-4 py-2 border-2 border-gray-300 dark:border-gray-600 rounded-lg
                                           focus:ring-2 focus:ring-purple-500 focus:border-purple-500
                                           dark:bg-gray-800 dark:text-white">
                                <option value="THB" selected>บาท (THB)</option>
                                <option value="USD">ดอลลาร์ (USD)</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Notifications Tab --}}
                <div x-show="activeTab === 'notifications'" x-transition>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">
                        การแจ้งเตือน
                    </h3>

                    <div class="space-y-4">
                        {{-- Email Notifications --}}
                        <div class="p-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center">
                                    <i class="fas fa-envelope text-purple-500 text-xl mr-3"></i>
                                    <div>
                                        <h4 class="font-semibold text-gray-900 dark:text-white">การแจ้งเตือนทางอีเมล</h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">รับการแจ้งเตือนผ่านอีเมล</p>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="notifications[email_enabled]" value="1"
                                           {{ ($user->email_notifications ?? true) ? 'checked' : '' }}
                                           class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 dark:peer-focus:ring-purple-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-purple-600"></div>
                                </label>
                            </div>

                            <div class="ml-11 space-y-2">
                                <label class="flex items-center">
                                    <input type="checkbox" name="notifications[commission]" value="1"
                                           {{ ($user->notify_commission ?? true) ? 'checked' : '' }}
                                           class="w-4 h-4 text-purple-600 border-gray-300 dark:border-gray-600 rounded focus:ring-purple-500">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">คอมมิชชั่นใหม่</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="notifications[referral]" value="1"
                                           {{ ($user->notify_referral ?? true) ? 'checked' : '' }}
                                           class="w-4 h-4 text-purple-600 border-gray-300 dark:border-gray-600 rounded focus:ring-purple-500">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">ลูกทีมใหม่</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="notifications[rank_upgrade]" value="1"
                                           {{ ($user->notify_rank ?? true) ? 'checked' : '' }}
                                           class="w-4 h-4 text-purple-600 border-gray-300 dark:border-gray-600 rounded focus:ring-purple-500">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">เลื่อนระดับ Rank</span>
                                </label>
                            </div>
                        </div>

                        {{-- LINE Notifications --}}
                        <div class="p-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <i class="fab fa-line text-green-500 text-xl mr-3"></i>
                                    <div>
                                        <h4 class="font-semibold text-gray-900 dark:text-white">การแจ้งเตือนทาง LINE</h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">รับการแจ้งเตือนผ่าน LINE</p>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="notifications[line_enabled]" value="1"
                                           {{ ($user->line_notifications ?? false) ? 'checked' : '' }}
                                           class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-green-600"></div>
                                </label>
                            </div>
                        </div>

                        {{-- Push Notifications --}}
                        <div class="p-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <i class="fas fa-mobile-alt text-orange-500 text-xl mr-3"></i>
                                    <div>
                                        <h4 class="font-semibold text-gray-900 dark:text-white">Push Notifications</h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">รับการแจ้งเตือนบนอุปกรณ์</p>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="notifications[push_enabled]" value="1"
                                           {{ ($user->push_notifications ?? false) ? 'checked' : '' }}
                                           class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 dark:peer-focus:ring-orange-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-orange-600"></div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Privacy Tab --}}
                <div x-show="activeTab === 'privacy'" x-transition>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">
                        ความเป็นส่วนตัว
                    </h3>

                    <div class="space-y-4">
                        <div class="p-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <i class="fas fa-user-shield text-blue-500 text-xl mr-3"></i>
                                    <div>
                                        <h4 class="font-semibold text-gray-900 dark:text-white">แสดงโปรไฟล์สาธารณะ</h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">ให้ผู้อื่นเห็นโปรไฟล์ของคุณ</p>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="privacy[public_profile]" value="1"
                                           {{ ($user->public_profile ?? true) ? 'checked' : '' }}
                                           class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                </label>
                            </div>
                        </div>

                        <div class="p-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <i class="fas fa-chart-line text-blue-500 text-xl mr-3"></i>
                                    <div>
                                        <h4 class="font-semibold text-gray-900 dark:text-white">แสดงสถิติ</h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">แสดงสถิติรายได้และลูกทีม</p>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="privacy[show_stats]" value="1"
                                           {{ ($user->show_stats ?? true) ? 'checked' : '' }}
                                           class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                </label>
                            </div>
                        </div>

                        <div class="p-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <i class="fas fa-users text-blue-500 text-xl mr-3"></i>
                                    <div>
                                        <h4 class="font-semibold text-gray-900 dark:text-white">แสดงลูกทีม</h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">แสดงรายชื่อลูกทีมของคุณ</p>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="privacy[show_team]" value="1"
                                           {{ ($user->show_team ?? false) ? 'checked' : '' }}
                                           class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Appearance Tab --}}
                <div x-show="activeTab === 'appearance'" x-transition>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">
                        รูปแบบการแสดงผล
                    </h3>

                    <div class="space-y-4">
                        {{-- Dark Mode --}}
                        <div class="p-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <i class="fas fa-moon text-indigo-500 text-xl mr-3"></i>
                                    <div>
                                        <h4 class="font-semibold text-gray-900 dark:text-white">โหมดมืด (Dark Mode)</h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">เปลี่ยนเป็นธีมสีเข้ม</p>
                                    </div>
                                </div>
                                <button type="button"
                                        @click="toggleDarkMode()"
                                        class="relative inline-flex items-center cursor-pointer">
                                    <div class="w-11 h-6 bg-gray-200 rounded-full peer dark:bg-gray-700"
                                         :class="isDark ? 'bg-indigo-600' : 'bg-gray-200'">
                                        <div class="absolute top-[2px] left-[2px] bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 border rounded-full h-5 w-5 transition-all"
                                             :class="isDark ? 'translate-x-full border-white' : ''"></div>
                                    </div>
                                </button>
                            </div>
                        </div>

                        {{-- Animations --}}
                        <div class="p-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <i class="fas fa-magic text-indigo-500 text-xl mr-3"></i>
                                    <div>
                                        <h4 class="font-semibold text-gray-900 dark:text-white">เปิดใช้ Animation</h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">แสดง Animation และ Transition</p>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="appearance[animations]" value="1" checked
                                           class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Security Tab --}}
                <div x-show="activeTab === 'security'" x-transition>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">
                        ความปลอดภัย
                    </h3>

                    <div class="space-y-4">
                        {{-- Two-Factor Authentication --}}
                        <div class="p-4 bg-gradient-to-r from-red-50 to-orange-50 dark:from-red-900/20 dark:to-orange-900/20 border border-red-200 dark:border-red-800 rounded-xl">
                            <div class="flex items-start justify-between">
                                <div class="flex items-start">
                                    <i class="fas fa-shield-alt text-red-500 text-xl mr-3 mt-1"></i>
                                    <div>
                                        <h4 class="font-semibold text-gray-900 dark:text-white">การยืนยันตัวตนแบบ 2 ชั้น (2FA)</h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">เพิ่มความปลอดภัยให้บัญชีของคุณ</p>
                                        @if($user->two_factor_enabled ?? false)
                                            <span class="inline-flex items-center px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 text-xs font-semibold rounded">
                                                <i class="fas fa-check-circle mr-1"></i> เปิดใช้งานแล้ว
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 text-xs font-semibold rounded">
                                                <i class="fas fa-times-circle mr-1"></i> ยังไม่ได้เปิดใช้งาน
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <a href="{{ route('user.security.2fa') }}"
                                   class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg font-semibold transition">
                                    ตั้งค่า
                                </a>
                            </div>
                        </div>

                        {{-- Active Sessions --}}
                        <div class="p-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <i class="fas fa-desktop text-blue-500 text-xl mr-3"></i>
                                    <div>
                                        <h4 class="font-semibold text-gray-900 dark:text-white">อุปกรณ์ที่เข้าสู่ระบบ</h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">จัดการอุปกรณ์ที่เข้าสู่ระบบ</p>
                                    </div>
                                </div>
                                <a href="{{ route('user.security.sessions') }}"
                                   class="text-purple-600 dark:text-purple-400 hover:underline font-semibold">
                                    ดูทั้งหมด
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Submit Button --}}
                <div class="mt-8 flex justify-end">
                    <button type="submit"
                            class="btn-shimmer px-8 py-3 bg-gradient-to-r from-purple-600 via-pink-600 to-orange-600
                                   hover:from-purple-700 hover:via-pink-700 hover:to-orange-700
                                   text-white font-bold text-lg rounded-xl shadow-lg hover:shadow-xl
                                   transform  transition-all duration-300">
                        <i class="fas fa-save mr-2"></i>
                        บันทึกการตั้งค่า
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
/**
 * Settings Manager Alpine.js Component
 */
function settingsManager() {
    return {
        activeTab: 'preferences',
        isDark: document.documentElement.classList.contains('dark'),

        init() {
            // Listen to dark mode changes
            window.addEventListener('dark-mode-changed', (e) => {
                this.isDark = e.detail.isDark;
            });
        },

        toggleDarkMode() {
            this.isDark = !this.isDark;
            if (this.isDark) {
                document.documentElement.classList.add('dark');
                localStorage.setItem('darkMode', 'true');
            } else {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('darkMode', 'false');
            }

            // Dispatch event
            window.dispatchEvent(new CustomEvent('dark-mode-changed', {
                detail: { isDark: this.isDark }
            }));
        }
    };
}
</script>
@endpush

@push('styles')
<style>
/* Button Shimmer Effect */
.btn-shimmer {
    position: relative;
    overflow: hidden;
}

.btn-shimmer::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(
        90deg,
        transparent,
        rgba(255, 255, 255, 0.4),
        transparent
    );
    transition: left 0.5s ease;
}

.btn-shimmer:hover::before {
    left: 100%;
}

/* Glass Fusion Card */
.glass-fusion-card {
    background: linear-gradient(
        135deg,
        rgba(255, 255, 255, 0.25) 0%,
        rgba(255, 255, 255, 0.15) 100%
    );
    backdrop-filter: blur(20px) saturate(180%);
    -webkit-backdrop-filter: blur(20px) saturate(180%);
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.dark .glass-fusion-card {
    background: linear-gradient(
        135deg,
        rgba(31, 41, 55, 0.8) 0%,
        rgba(17, 24, 39, 0.7) 100%
    );
    border-color: rgba(75, 85, 99, 0.5);
}
</style>
@endpush
@endsection
