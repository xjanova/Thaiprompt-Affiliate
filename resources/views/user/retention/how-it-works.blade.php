@extends('layouts.user-arrow-x')

@section('title', 'คู่มือการใช้งาน - How It Works')

@section('content')
{{-- หน้าคู่มือการใช้งาน - V3 Tailwind + Alpine.js --}}
<div class="min-h-screen p-4 md:p-6 lg:p-8">

    {{-- Hero Header --}}
    <div class="relative overflow-hidden rounded-2xl md:rounded-3xl mb-6 md:mb-8
                bg-gradient-to-br from-blue-500 via-cyan-500 to-teal-500
                dark:from-blue-800 dark:via-cyan-800 dark:to-teal-800
                p-6 md:p-8 lg:p-10 shadow-2xl">

        {{-- Animated Background --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-20 -right-20 w-40 h-40 md:w-60 md:h-60
                        bg-white/10 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute -bottom-20 -left-20 w-40 h-40 md:w-60 md:h-60
                        bg-white/10 rounded-full blur-3xl animate-pulse"
                 style="animation-delay: 1s;"></div>
        </div>

        {{-- Content --}}
        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4 md:gap-6">
            <div class="flex items-center gap-4">
                {{-- Icon --}}
                <div class="w-16 h-16 md:w-20 md:h-20 bg-white/20 backdrop-blur-xl
                            rounded-2xl flex items-center justify-center
                            shadow-lg border border-white/20">
                    <i class="fas fa-book-open text-white text-3xl md:text-4xl"></i>
                </div>

                {{-- Title --}}
                <div>
                    <h1 class="text-2xl md:text-3xl lg:text-4xl font-bold text-white drop-shadow-lg">
                        คู่มือการใช้งาน
                    </h1>
                    <p class="text-white/80 text-sm md:text-base mt-1">
                        เรียนรู้วิธีการทำงานของระบบรักษายอด
                    </p>
                </div>
            </div>

            {{-- Back Button --}}
            <a href="{{ route('user.retention.index') }}"
               class="inline-flex items-center gap-3 px-6 py-3 bg-white/20 backdrop-blur-xl
                      border border-white/30 rounded-xl text-white font-semibold
                      hover:bg-white/30 transition-all duration-300
                      shadow-lg hover:shadow-xl transform hover:scale-105">
                <i class="fas fa-arrow-left"></i>
                <span>กลับหน้าหลัก</span>
            </a>
        </div>
    </div>

    {{-- Quick Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-6 md:mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-5
                    border border-gray-100 dark:border-gray-700
                    hover:shadow-xl transition-shadow">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-600
                            rounded-xl flex items-center justify-center">
                    <i class="fas fa-calendar-check text-white text-xl"></i>
                </div>
            </div>
            <div class="text-3xl font-bold text-gray-900 dark:text-white">30</div>
            <div class="text-sm text-gray-600 dark:text-gray-400">วันต่อรอบ</div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-5
                    border border-gray-100 dark:border-gray-700
                    hover:shadow-xl transition-shadow">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600
                            rounded-xl flex items-center justify-center">
                    <i class="fas fa-coins text-white text-xl"></i>
                </div>
            </div>
            <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($settings['minimum_points_per_month'] ?? 1000, 0) }}</div>
            <div class="text-sm text-gray-600 dark:text-gray-400">แต้มต่อรอบ</div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-5
                    border border-gray-100 dark:border-gray-700
                    hover:shadow-xl transition-shadow">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-12 h-12 bg-gradient-to-br from-amber-500 to-orange-600
                            rounded-xl flex items-center justify-center">
                    <i class="fas fa-clock text-white text-xl"></i>
                </div>
            </div>
            <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ $settings['grace_period_days'] ?? 3 }}</div>
            <div class="text-sm text-gray-600 dark:text-gray-400">วันผ่อนผัน</div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-5
                    border border-gray-100 dark:border-gray-700
                    hover:shadow-xl transition-shadow">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-cyan-600
                            rounded-xl flex items-center justify-center">
                    <i class="fas fa-bell text-white text-xl"></i>
                </div>
            </div>
            <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ $settings['warning_days_before_expiry'] ?? 7 }}</div>
            <div class="text-sm text-gray-600 dark:text-gray-400">วันแจ้งเตือน</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 md:gap-8">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- What is Retention --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden
                        border border-gray-100 dark:border-gray-700">
                <div class="bg-gradient-to-r from-purple-500 to-pink-600
                            dark:from-purple-700 dark:to-pink-800 p-5">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                            <i class="fas fa-question-circle text-white text-2xl"></i>
                        </div>
                        <h2 class="text-xl font-bold text-white">ระบบรักษายอดคืออะไร?</h2>
                    </div>
                </div>
                <div class="p-6">
                    <p class="text-gray-600 dark:text-gray-400 mb-6 leading-relaxed">
                        ระบบรักษายอด (Membership Retention) เป็นระบบที่ช่วยให้คุณสามารถรักษาสถานภาพการเป็นสมาชิกและสิทธิ์ในการรับคอมมิชชั่นจากระบบ MLM โดยคุณจะต้องสะสมแต้มให้ถึงเป้าหมายที่กำหนดในแต่ละรอบ 30 วัน
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl border border-purple-200 dark:border-purple-800">
                            <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center mb-3">
                                <i class="fas fa-bullseye text-white"></i>
                            </div>
                            <h4 class="font-bold text-gray-900 dark:text-white mb-1">เป้าหมาย</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                สะสม <strong class="text-purple-600 dark:text-purple-400">{{ number_format($settings['minimum_points_per_month'] ?? 1000, 0) }} แต้ม</strong>/รอบ
                            </p>
                        </div>

                        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
                            <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center mb-3">
                                <i class="fas fa-calendar text-white"></i>
                            </div>
                            <h4 class="font-bold text-gray-900 dark:text-white mb-1">รอบการคำนวณ</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                ทุก <strong class="text-blue-600 dark:text-blue-400">30 วัน</strong> อัตโนมัติ
                            </p>
                        </div>

                        <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-xl border border-green-200 dark:border-green-800">
                            <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center mb-3">
                                <i class="fas fa-coins text-white"></i>
                            </div>
                            <h4 class="font-bold text-gray-900 dark:text-white mb-1">สิทธิ์ที่ได้รับ</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                <strong class="text-green-600 dark:text-green-400">รับคอมมิชชั่น</strong> ตลอด
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- How to Earn Points --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden
                        border border-gray-100 dark:border-gray-700">
                <div class="bg-gradient-to-r from-amber-500 to-orange-600
                            dark:from-amber-700 dark:to-orange-800 p-5">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                            <i class="fas fa-star text-white text-2xl"></i>
                        </div>
                        <h2 class="text-xl font-bold text-white">วิธีสะสมแต้ม</h2>
                    </div>
                </div>
                <div class="p-6 space-y-4">
                    <div class="flex items-start gap-4 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl
                                border border-gray-100 dark:border-gray-700">
                        <div class="w-12 h-12 bg-gradient-to-br from-amber-400 to-orange-500
                                    rounded-xl flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-shopping-cart text-white text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold text-gray-900 dark:text-white mb-1">สั่งซื้อสินค้า</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                ทุกการสั่งซื้อจะได้รับแต้มตามจำนวน PV (Point Value)
                            </p>
                        </div>
                        <div class="text-right">
                            <span class="text-2xl font-bold text-amber-600 dark:text-amber-400">1x</span>
                            <div class="text-xs text-gray-500">PV = แต้ม</div>
                        </div>
                    </div>

                    <div class="flex items-start gap-4 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl
                                border border-gray-100 dark:border-gray-700">
                        <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-emerald-500
                                    rounded-xl flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-coins text-white text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold text-gray-900 dark:text-white mb-1">รับคอมมิชชั่น</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                ทุกคอมมิชชั่นจะถูกแปลงเป็นแต้มอัตโนมัติ
                            </p>
                        </div>
                        <div class="text-right">
                            <span class="text-2xl font-bold text-green-600 dark:text-green-400">1x</span>
                            <div class="text-xs text-gray-500">บาท = แต้ม</div>
                        </div>
                    </div>

                    <div class="flex items-start gap-4 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl
                                border border-gray-100 dark:border-gray-700">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-400 to-cyan-500
                                    rounded-xl flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-users text-white text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold text-gray-900 dark:text-white mb-1">แนะนำสมาชิกใหม่</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                เมื่อดาวน์ไลน์ของคุณสั่งซื้อสินค้า คุณจะได้รับแต้มเพิ่มเติม
                            </p>
                        </div>
                        <div class="text-right">
                            <span class="text-2xl font-bold text-blue-600 dark:text-blue-400">+</span>
                            <div class="text-xs text-gray-500">โบนัส</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Status Flow --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden
                        border border-gray-100 dark:border-gray-700">
                <div class="bg-gradient-to-r from-indigo-500 to-purple-600
                            dark:from-indigo-700 dark:to-purple-800 p-5">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                            <i class="fas fa-sitemap text-white text-2xl"></i>
                        </div>
                        <h2 class="text-xl font-bold text-white">สถานะการรักษายอด</h2>
                    </div>
                </div>
                <div class="p-6">
                    {{-- Status Flow Diagram --}}
                    <div class="flex flex-col md:flex-row items-center justify-center gap-4 md:gap-8 mb-6">
                        <div class="text-center">
                            <div class="w-20 h-20 bg-gradient-to-br from-green-500 to-emerald-600
                                        rounded-full flex items-center justify-center shadow-lg mx-auto mb-3">
                                <i class="fas fa-check text-white text-3xl"></i>
                            </div>
                            <div class="font-bold text-gray-900 dark:text-white">ใช้งานอยู่</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Active</div>
                        </div>

                        <div class="hidden md:block text-gray-300 dark:text-gray-600">
                            <i class="fas fa-arrow-right text-3xl"></i>
                        </div>
                        <div class="md:hidden text-gray-300 dark:text-gray-600">
                            <i class="fas fa-arrow-down text-3xl"></i>
                        </div>

                        <div class="text-center">
                            <div class="w-20 h-20 bg-gradient-to-br from-amber-500 to-orange-600
                                        rounded-full flex items-center justify-center shadow-lg mx-auto mb-3">
                                <i class="fas fa-exclamation text-white text-3xl"></i>
                            </div>
                            <div class="font-bold text-gray-900 dark:text-white">เตือน</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">&lt; 7 วัน</div>
                        </div>

                        <div class="hidden md:block text-gray-300 dark:text-gray-600">
                            <i class="fas fa-arrow-right text-3xl"></i>
                        </div>
                        <div class="md:hidden text-gray-300 dark:text-gray-600">
                            <i class="fas fa-arrow-down text-3xl"></i>
                        </div>

                        <div class="text-center">
                            <div class="w-20 h-20 bg-gradient-to-br from-red-500 to-pink-600
                                        rounded-full flex items-center justify-center shadow-lg mx-auto mb-3">
                                <i class="fas fa-times text-white text-3xl"></i>
                            </div>
                            <div class="font-bold text-gray-900 dark:text-white">หมดอายุ</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Expired</div>
                        </div>
                    </div>

                    <div class="p-4 bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-200 dark:border-red-800">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-exclamation-triangle text-red-500 text-xl mt-0.5"></i>
                            <div>
                                <h4 class="font-bold text-red-700 dark:text-red-400 mb-1">หมายเหตุสำคัญ</h4>
                                <p class="text-sm text-red-600 dark:text-red-300">
                                    หากสถานะหมดอายุ คุณจะไม่สามารถรับคอมมิชชั่นจากระบบ MLM ได้ จนกว่าจะซ่อมสิทธิ์หรือเติมวันล่วงหน้า
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- FAQ --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden
                        border border-gray-100 dark:border-gray-700"
                 x-data="{ openFaq: null }">
                <div class="bg-gradient-to-r from-cyan-500 to-blue-600
                            dark:from-cyan-700 dark:to-blue-800 p-5">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                            <i class="fas fa-question text-white text-2xl"></i>
                        </div>
                        <h2 class="text-xl font-bold text-white">คำถามที่พบบ่อย</h2>
                    </div>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach([
                        ['q' => 'ถ้าไม่ทำตามเป้าหมายจะเกิดอะไรขึ้น?', 'a' => 'หากคุณไม่สะสมแต้มถึงเป้าหมายภายในรอบ 30 วัน สถานะจะเปลี่ยนเป็น "หมดอายุ" และคุณจะไม่สามารถรับคอมมิชชั่นจากระบบ MLM ได้ จนกว่าจะซ่อมสิทธิ์หรือเติมวันล่วงหน้า'],
                        ['q' => 'แต้มจะหมดอายุหรือไม่?', 'a' => 'ใช่ แต้มจะถูกรีเซ็ตเป็น 0 เมื่อสิ้นสุดรอบการคำนวณ (ทุก 30 วัน) ไม่ว่าคุณจะผ่านเกณฑ์หรือไม่ก็ตาม'],
                        ['q' => 'ฉันสามารถเติมวันล่วงหน้าได้สูงสุดกี่เดือน?', 'a' => 'คุณสามารถเติมวันล่วงหน้าได้สูงสุด 12 เดือน พร้อมรับส่วนลด 10% ทันที!'],
                        ['q' => 'ค่าซ่อมสิทธิ์คำนวณอย่างไร?', 'a' => 'ค่าซ่อมสิทธิ์ = (แต้มที่ต้องการ - แต้มที่ได้) × ' . number_format($settings['repair_cost_per_point'] ?? 1.5, 2) . ' บาท']
                    ] as $index => $faq)
                        <div>
                            <button @click="openFaq = openFaq === {{ $index }} ? null : {{ $index }}"
                                    class="flex items-center justify-between w-full p-4 text-left
                                           hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                                <span class="font-semibold text-gray-900 dark:text-white">{{ $faq['q'] }}</span>
                                <i class="fas fa-chevron-down text-gray-500 transition-transform"
                                   :class="openFaq === {{ $index }} ? 'transform rotate-180' : ''"></i>
                            </button>
                            <div x-show="openFaq === {{ $index }}"
                                 x-collapse
                                 class="px-4 pb-4 text-gray-600 dark:text-gray-400">
                                {{ $faq['a'] }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Quick Actions --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden
                        border border-gray-100 dark:border-gray-700">
                <div class="bg-gradient-to-r from-purple-500 to-pink-600
                            dark:from-purple-700 dark:to-pink-800 p-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-rocket text-white text-xl"></i>
                        </div>
                        <h3 class="text-lg font-bold text-white">ดำเนินการด่วน</h3>
                    </div>
                </div>
                <div class="p-5 space-y-3">
                    <a href="{{ route('user.retention.index') }}"
                       class="flex items-center justify-center gap-2 w-full px-4 py-3
                              bg-gradient-to-r from-purple-500 to-pink-600
                              text-white font-semibold rounded-xl shadow-lg
                              hover:shadow-xl transform hover:scale-[1.02] transition-all">
                        <i class="fas fa-chart-line"></i>
                        <span>ตรวจสอบสถานะ</span>
                    </a>

                    <a href="{{ route('user.retention.repair') }}"
                       class="flex items-center justify-center gap-2 w-full px-4 py-3
                              bg-gradient-to-r from-amber-500 to-orange-600
                              text-white font-semibold rounded-xl shadow-lg
                              hover:shadow-xl transform hover:scale-[1.02] transition-all">
                        <i class="fas fa-tools"></i>
                        <span>ซ่อมสิทธิ์</span>
                    </a>

                    <a href="{{ route('user.retention.advance-renewal') }}"
                       class="flex items-center justify-center gap-2 w-full px-4 py-3
                              bg-gradient-to-r from-green-500 to-emerald-600
                              text-white font-semibold rounded-xl shadow-lg
                              hover:shadow-xl transform hover:scale-[1.02] transition-all">
                        <i class="fas fa-plus-circle"></i>
                        <span>เติมวันล่วงหน้า</span>
                    </a>
                </div>
            </div>

            {{-- Repair Info --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-5
                        border border-gray-100 dark:border-gray-700">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-amber-500 to-orange-600
                                rounded-xl flex items-center justify-center">
                        <i class="fas fa-tools text-white text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">ซ่อมสิทธิ์</h3>
                </div>
                <ul class="space-y-3 text-sm text-gray-600 dark:text-gray-400">
                    <li class="flex items-center gap-2">
                        <i class="fas fa-check-circle text-amber-500"></i>
                        <span>ค่าซ่อม: <strong class="text-amber-600 dark:text-amber-400">{{ number_format($settings['repair_cost_per_point'] ?? 1.5, 2) }} บาท/แต้ม</strong></span>
                    </li>
                    <li class="flex items-center gap-2">
                        <i class="fas fa-check-circle text-amber-500"></i>
                        <span>คำนวณจากแต้มที่ขาดเท่านั้น</span>
                    </li>
                    <li class="flex items-center gap-2">
                        <i class="fas fa-check-circle text-amber-500"></i>
                        <span>กู้คืนสถานะทันที</span>
                    </li>
                </ul>
            </div>

            {{-- Advance Renewal Info --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-5
                        border border-gray-100 dark:border-gray-700">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600
                                rounded-xl flex items-center justify-center">
                        <i class="fas fa-plus-circle text-white text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">เติมวันล่วงหน้า</h3>
                </div>
                <ul class="space-y-3 text-sm text-gray-600 dark:text-gray-400">
                    <li class="flex items-center gap-2">
                        <i class="fas fa-check-circle text-green-500"></i>
                        <span>ส่วนลดพิเศษ: <strong class="text-green-600 dark:text-green-400">10%</strong></span>
                    </li>
                    <li class="flex items-center gap-2">
                        <i class="fas fa-check-circle text-green-500"></i>
                        <span>เลือกได้ 1-12 เดือน</span>
                    </li>
                    <li class="flex items-center gap-2">
                        <i class="fas fa-check-circle text-green-500"></i>
                        <span>ไม่ต้องกังวลเรื่องหมดอายุ</span>
                    </li>
                </ul>
            </div>

            {{-- Tips --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-5
                        border border-gray-100 dark:border-gray-700">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-cyan-600
                                rounded-xl flex items-center justify-center">
                        <i class="fas fa-lightbulb text-white text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">เคล็ดลับ</h3>
                </div>
                <ul class="space-y-3">
                    @foreach([
                        'ติดตามสถานะผ่าน Dashboard เป็นประจำ',
                        'ตั้งเป้าหมายทำยอดให้ครบก่อนหมดรอบ 7 วัน',
                        'ใช้ระบบเติมวันล่วงหน้าเพื่อความมั่นใจ',
                        'เปิดการแจ้งเตือนเพื่อไม่พลาดข้อมูลสำคัญ'
                    ] as $tip)
                        <li class="flex items-start gap-2 p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg
                                   border border-amber-100 dark:border-amber-800">
                            <i class="fas fa-star text-amber-500 mt-0.5"></i>
                            <span class="text-sm text-amber-800 dark:text-amber-300 font-medium">{{ $tip }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
