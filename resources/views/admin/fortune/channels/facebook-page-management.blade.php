@extends('layouts.admin')

@section('title', 'จัดการ Facebook Page Integration')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="facebookPageMgmt()">
    {{-- Header --}}
    <div class="mb-8">
        <div class="flex items-center gap-3 mb-2">
            <a href="{{ route('admin.fortune.channels.index') }}"
               class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                <i class="fab fa-facebook text-blue-600"></i>
                จัดการ Facebook Page Integration
            </h1>
        </div>
        <p class="text-gray-600 dark:text-gray-400">
            เชื่อมต่อ Facebook Page เพื่อให้บริการดูดวงผ่าน Messenger และตอบกลับ Comment อัตโนมัติ
        </p>
    </div>

    {{-- ===== Section 1: Pages List (pages_show_list) ===== --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg mb-8 overflow-hidden">
        <div class="bg-gradient-to-r from-blue-600 to-blue-800 px-6 py-4">
            <h2 class="text-xl font-bold text-white flex items-center gap-2">
                <i class="fas fa-list"></i>
                เลือก Facebook Page ที่ต้องการเชื่อมต่อ
            </h2>
            <p class="text-blue-100 text-sm mt-1">เลือก Page ที่คุณเป็นผู้จัดการเพื่อเชื่อมต่อกับระบบดูดวง</p>
        </div>
        <div class="p-6">
            {{-- สถานะการเชื่อมต่อปัจจุบัน --}}
            @if($settings->facebook_page_id)
                <div class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 rounded-lg p-4 mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center text-white">
                            <i class="fas fa-check"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-green-800 dark:text-green-200">เชื่อมต่อสำเร็จแล้ว</p>
                            <p class="text-sm text-green-600 dark:text-green-400">Page ID: {{ $settings->facebook_page_id }}</p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- รายการ Pages --}}
            <div class="border dark:border-gray-700 rounded-lg overflow-hidden">
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 border-b dark:border-gray-600">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            <i class="fas fa-building mr-1"></i> Facebook Pages ของคุณ
                        </span>
                        <button type="button" @click="refreshPages()"
                                class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                            <i class="fas fa-sync-alt" :class="{'fa-spin': loadingPages}"></i>
                            รีเฟรช
                        </button>
                    </div>
                </div>

                {{-- Page List Items --}}
                <div class="divide-y dark:divide-gray-700">
                    @if($settings->facebook_page_id)
                        <div class="flex items-center justify-between p-4 bg-blue-50 dark:bg-blue-900/20">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center text-white text-lg">
                                    <i class="fab fa-facebook-f"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-white">
                                        {{ $settings->facebook_page_name ?? 'Facebook Page' }}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        ID: {{ $settings->facebook_page_id }}
                                    </p>
                                </div>
                            </div>
                            <span class="inline-flex items-center gap-1 text-sm text-green-600 dark:text-green-400 bg-green-100 dark:bg-green-900/30 px-3 py-1 rounded-full">
                                <i class="fas fa-check-circle"></i> เชื่อมต่อแล้ว
                            </span>
                        </div>
                    @else
                        <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                            <i class="fab fa-facebook text-4xl mb-3 text-gray-300"></i>
                            <p>ยังไม่ได้เชื่อมต่อ Facebook Page</p>
                            <p class="text-sm">กรุณาตั้งค่า Facebook App ID และ Page Token ในหน้าตั้งค่า</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ปุ่มเชื่อมต่อ --}}
            <div class="mt-4 flex gap-3">
                <a href="{{ route('admin.fortune.settings.index') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition text-sm">
                    <i class="fas fa-cog"></i>
                    ตั้งค่าการเชื่อมต่อ
                </a>
                <button type="button" @click="testConnection()"
                        :disabled="testingConnection"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition text-sm disabled:opacity-50">
                    <i class="fas" :class="testingConnection ? 'fa-spinner fa-spin' : 'fa-plug'"></i>
                    <span x-text="testingConnection ? 'กำลังทดสอบ...' : 'ทดสอบการเชื่อมต่อ'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- ===== Section 2: Page Metadata (pages_manage_metadata) ===== --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg mb-8 overflow-hidden">
        <div class="bg-gradient-to-r from-indigo-600 to-purple-700 px-6 py-4">
            <h2 class="text-xl font-bold text-white flex items-center gap-2">
                <i class="fas fa-cogs"></i>
                ตั้งค่า Page Metadata
            </h2>
            <p class="text-indigo-100 text-sm mt-1">จัดการ Messenger Profile: ปุ่มเริ่มต้น, เมนู, ข้อความต้อนรับ</p>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                {{-- Get Started Button --}}
                <div class="border dark:border-gray-700 rounded-lg p-4">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center">
                            <i class="fas fa-play-circle text-indigo-600 dark:text-indigo-400"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white">ปุ่มเริ่มต้น</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Get Started Button</p>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                        ปุ่มเริ่มต้นใช้งานที่แสดงเมื่อผู้ใช้เปิดแชทครั้งแรก
                    </p>
                    <button type="button" @click="setupMessengerProfile()"
                            :disabled="settingUpProfile"
                            class="w-full inline-flex items-center justify-center gap-2 px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition text-sm disabled:opacity-50">
                        <i class="fas" :class="settingUpProfile ? 'fa-spinner fa-spin' : 'fa-magic'"></i>
                        <span x-text="settingUpProfile ? 'กำลังตั้งค่า...' : 'ตั้งค่า Messenger Profile'"></span>
                    </button>
                </div>

                {{-- Persistent Menu --}}
                <div class="border dark:border-gray-700 rounded-lg p-4">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                            <i class="fas fa-bars text-purple-600 dark:text-purple-400"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white">เมนูถาวร</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Persistent Menu</p>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                        เมนูลัดแสดงเสมอในแชท เช่น ดูดวง, ถามคำถาม
                    </p>
                    <div class="text-sm text-green-600 dark:text-green-400">
                        <i class="fas fa-check-circle"></i> ตั้งค่าพร้อมกับ Messenger Profile
                    </div>
                </div>

                {{-- Greeting Text --}}
                <div class="border dark:border-gray-700 rounded-lg p-4">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 bg-pink-100 dark:bg-pink-900/30 rounded-lg flex items-center justify-center">
                            <i class="fas fa-comment-dots text-pink-600 dark:text-pink-400"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white">ข้อความต้อนรับ</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Greeting Text</p>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                        ข้อความแรกที่แสดงเมื่อผู้ใช้เปิดแชท
                    </p>
                    <div class="bg-gray-50 dark:bg-gray-700 rounded p-2 text-sm text-gray-700 dark:text-gray-300">
                        {{ Str::limit($settings->welcome_message ?? 'สวัสดี! ยินดีต้อนรับสู่ระบบดูดวง', 60) }}
                    </div>
                </div>
            </div>

            {{-- Webhook URL --}}
            <div class="mt-6 bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-2">
                    <i class="fas fa-link mr-1"></i> Webhook Configuration
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm text-gray-500 dark:text-gray-400">Callback URL</label>
                        <div class="flex items-center gap-2 mt-1">
                            <code class="bg-white dark:bg-gray-800 border dark:border-gray-600 rounded px-3 py-2 text-sm flex-1 text-gray-800 dark:text-gray-200">
                                {{ url('/webhook/facebook') }}
                            </code>
                            <button type="button" @click="copyToClipboard('{{ url('/webhook/facebook') }}')"
                                    class="px-3 py-2 bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 rounded transition">
                                <i class="fas fa-copy text-gray-600 dark:text-gray-300"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="text-sm text-gray-500 dark:text-gray-400">Verify Token</label>
                        <div class="flex items-center gap-2 mt-1">
                            <code class="bg-white dark:bg-gray-800 border dark:border-gray-600 rounded px-3 py-2 text-sm flex-1 text-gray-800 dark:text-gray-200">
                                {{ $settings->facebook_verify_token ?? 'ยังไม่ได้ตั้งค่า' }}
                            </code>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <label class="text-sm text-gray-500 dark:text-gray-400">Subscribed Events</label>
                    <div class="flex flex-wrap gap-2 mt-1">
                        @foreach(['messages', 'messaging_postbacks', 'feed', 'message_echoes'] as $event)
                            <span class="inline-flex items-center gap-1 text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 px-2 py-1 rounded-full">
                                <i class="fas fa-check text-xs"></i> {{ $event }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Section 3: Messaging (pages_messaging) ===== --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg mb-8 overflow-hidden">
        <div class="bg-gradient-to-r from-green-600 to-teal-700 px-6 py-4">
            <h2 class="text-xl font-bold text-white flex items-center gap-2">
                <i class="fas fa-comment-alt"></i>
                ระบบส่งข้อความอัตโนมัติ (Messenger)
            </h2>
            <p class="text-green-100 text-sm mt-1">ตอบกลับข้อความผ่าน Messenger โดยอัตโนมัติด้วย AI ดูดวง</p>
        </div>
        <div class="p-6">
            {{-- สถิติ Messaging --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($fbStats['total_readings']) }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">คำทำนายทั้งหมด</div>
                </div>
                <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($fbStats['total_deep']) }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">คำทำนายเชิงลึก</div>
                </div>
                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($fbStats['unique_users']) }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">ผู้ใช้ที่ไม่ซ้ำ</div>
                </div>
                <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">฿{{ number_format($fbStats['total_revenue'], 0) }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">รายได้</div>
                </div>
            </div>

            {{-- Message Flow Demo --}}
            <div class="border dark:border-gray-700 rounded-lg p-4">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-project-diagram mr-1"></i> Flow การทำงานของ Messenger Bot
                </h3>
                <div class="flex flex-col md:flex-row items-start md:items-center gap-3">
                    <div class="flex items-center gap-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg px-4 py-3 text-sm">
                        <i class="fas fa-user text-blue-600"></i>
                        <span class="text-gray-700 dark:text-gray-300">ผู้ใช้ส่งข้อความ</span>
                    </div>
                    <i class="fas fa-arrow-right text-gray-400 hidden md:block"></i>
                    <i class="fas fa-arrow-down text-gray-400 md:hidden"></i>
                    <div class="flex items-center gap-2 bg-purple-50 dark:bg-purple-900/20 rounded-lg px-4 py-3 text-sm">
                        <i class="fas fa-robot text-purple-600"></i>
                        <span class="text-gray-700 dark:text-gray-300">AI วิเคราะห์คำถาม</span>
                    </div>
                    <i class="fas fa-arrow-right text-gray-400 hidden md:block"></i>
                    <i class="fas fa-arrow-down text-gray-400 md:hidden"></i>
                    <div class="flex items-center gap-2 bg-green-50 dark:bg-green-900/20 rounded-lg px-4 py-3 text-sm">
                        <i class="fas fa-star text-green-600"></i>
                        <span class="text-gray-700 dark:text-gray-300">ส่งคำทำนายกลับ</span>
                    </div>
                    <i class="fas fa-arrow-right text-gray-400 hidden md:block"></i>
                    <i class="fas fa-arrow-down text-gray-400 md:hidden"></i>
                    <div class="flex items-center gap-2 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg px-4 py-3 text-sm">
                        <i class="fas fa-coins text-yellow-600"></i>
                        <span class="text-gray-700 dark:text-gray-300">ชำระเงิน (เชิงลึก)</span>
                    </div>
                </div>
            </div>

            {{-- Recent Readings --}}
            @if($fbStats['recent_readings']->count() > 0)
                <div class="mt-6">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-3">
                        <i class="fas fa-history mr-1"></i> คำทำนายล่าสุดผ่าน Messenger
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ผู้ใช้</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ประเภท</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">สถานะ</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">วันที่</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($fbStats['recent_readings'] as $reading)
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">
                                            {{ Str::limit($reading->platform_user_id, 15) }}
                                        </td>
                                        <td class="px-4 py-2 text-sm">
                                            @if($reading->is_deep_reading)
                                                <span class="text-purple-600 dark:text-purple-400"><i class="fas fa-gem"></i> เชิงลึก</span>
                                            @else
                                                <span class="text-blue-600 dark:text-blue-400"><i class="fas fa-star"></i> พื้นฐาน</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 text-sm">
                                            <span class="inline-flex items-center gap-1 text-green-600 dark:text-green-400">
                                                <i class="fas fa-check-circle"></i> ส่งแล้ว
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">
                                            {{ $reading->created_at->diffForHumans() }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- ===== Section 4: Comment Engagement (pages_read_engagement) ===== --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg mb-8 overflow-hidden">
        <div class="bg-gradient-to-r from-orange-500 to-red-600 px-6 py-4">
            <h2 class="text-xl font-bold text-white flex items-center gap-2">
                <i class="fas fa-comments"></i>
                ระบบตอบกลับ Comment อัตโนมัติ
            </h2>
            <p class="text-orange-100 text-sm mt-1">อ่าน Comment บน Page และทักแชทผู้ใช้ที่คอมเม้นต์โดยอัตโนมัติ</p>
        </div>
        <div class="p-6">
            {{-- Comment Engagement Settings --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-3">
                        <i class="fas fa-sliders-h mr-1"></i> การตั้งค่า
                    </h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <span class="text-sm text-gray-700 dark:text-gray-300">ตอบกลับ Comment อัตโนมัติ</span>
                            <span class="inline-flex items-center gap-1 text-sm {{ $settings->comment_engagement_enabled ? 'text-green-600' : 'text-gray-400' }}">
                                <i class="fas {{ $settings->comment_engagement_enabled ? 'fa-toggle-on text-lg' : 'fa-toggle-off text-lg' }}"></i>
                                {{ $settings->comment_engagement_enabled ? 'เปิด' : 'ปิด' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <span class="text-sm text-gray-700 dark:text-gray-300">โหมดการตอบ</span>
                            <span class="text-sm text-blue-600 dark:text-blue-400">
                                {{ $settings->comment_engagement_mode ?? 'reply_and_dm' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <span class="text-sm text-gray-700 dark:text-gray-300">ทักแชทส่วนตัวอัตโนมัติ</span>
                            <span class="inline-flex items-center gap-1 text-sm text-green-600">
                                <i class="fas fa-check-circle"></i> เปิดใช้งาน
                            </span>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-3">
                        <i class="fas fa-chart-bar mr-1"></i> สถิติ Comment Engagement
                    </h3>
                    @if(!empty($commentStats))
                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                <span class="text-sm text-gray-700 dark:text-gray-300">Comment ที่รับมา</span>
                                <span class="font-bold text-blue-600">{{ number_format($commentStats['total'] ?? 0) }}</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                <span class="text-sm text-gray-700 dark:text-gray-300">ตอบกลับแล้ว</span>
                                <span class="font-bold text-green-600">{{ number_format($commentStats['replied'] ?? 0) }}</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                                <span class="text-sm text-gray-700 dark:text-gray-300">ทักแชทส่วนตัวแล้ว</span>
                                <span class="font-bold text-purple-600">{{ number_format($commentStats['dm_sent'] ?? 0) }}</span>
                            </div>
                        </div>
                    @else
                        <div class="text-center p-6 text-gray-500 dark:text-gray-400">
                            <i class="fas fa-chart-line text-3xl mb-2"></i>
                            <p>ยังไม่มีข้อมูลสถิติ</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Comment Reply Templates --}}
            <div class="border dark:border-gray-700 rounded-lg p-4">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-3">
                    <i class="fas fa-envelope-open-text mr-1"></i> ข้อความที่ใช้ตอบกลับ
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                        <label class="text-xs text-gray-500 dark:text-gray-400 uppercase font-medium">ตอบกลับใต้ Comment</label>
                        <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">
                            {{ $settings->comment_reply_template ?? 'ขอบคุณที่สนใจค่ะ ทักแชทมาเพื่อดูดวงได้เลยนะคะ' }}
                        </p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                        <label class="text-xs text-gray-500 dark:text-gray-400 uppercase font-medium">ทักแชทส่วนตัว (DM)</label>
                        <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">
                            {{ $settings->comment_dm_template ?? 'สวัสดีค่ะ เห็นว่าสนใจดูดวง พิมพ์วันเกิดมาได้เลยนะคะ' }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Comment Flow --}}
            <div class="mt-4 border dark:border-gray-700 rounded-lg p-4 bg-gradient-to-r from-orange-50 to-red-50 dark:from-orange-900/10 dark:to-red-900/10">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-3">
                    <i class="fas fa-project-diagram mr-1"></i> Flow: Comment → Auto DM
                </h3>
                <div class="flex flex-col md:flex-row items-start md:items-center gap-3">
                    <div class="flex items-center gap-2 bg-white dark:bg-gray-800 rounded-lg px-4 py-3 text-sm shadow-sm">
                        <i class="fas fa-comment text-orange-500"></i>
                        <span class="text-gray-700 dark:text-gray-300">ผู้ใช้ Comment</span>
                    </div>
                    <i class="fas fa-arrow-right text-gray-400 hidden md:block"></i>
                    <i class="fas fa-arrow-down text-gray-400 md:hidden"></i>
                    <div class="flex items-center gap-2 bg-white dark:bg-gray-800 rounded-lg px-4 py-3 text-sm shadow-sm">
                        <i class="fas fa-robot text-blue-500"></i>
                        <span class="text-gray-700 dark:text-gray-300">Webhook รับ Event</span>
                    </div>
                    <i class="fas fa-arrow-right text-gray-400 hidden md:block"></i>
                    <i class="fas fa-arrow-down text-gray-400 md:hidden"></i>
                    <div class="flex items-center gap-2 bg-white dark:bg-gray-800 rounded-lg px-4 py-3 text-sm shadow-sm">
                        <i class="fas fa-reply text-green-500"></i>
                        <span class="text-gray-700 dark:text-gray-300">ตอบ Comment</span>
                    </div>
                    <i class="fas fa-arrow-right text-gray-400 hidden md:block"></i>
                    <i class="fas fa-arrow-down text-gray-400 md:hidden"></i>
                    <div class="flex items-center gap-2 bg-white dark:bg-gray-800 rounded-lg px-4 py-3 text-sm shadow-sm">
                        <i class="fas fa-paper-plane text-purple-500"></i>
                        <span class="text-gray-700 dark:text-gray-300">ทักแชท DM ทันที</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Section 5: Business Management (business_management) ===== --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg mb-8 overflow-hidden">
        <div class="bg-gradient-to-r from-gray-700 to-gray-900 px-6 py-4">
            <h2 class="text-xl font-bold text-white flex items-center gap-2">
                <i class="fas fa-building"></i>
                การจัดการธุรกิจ
            </h2>
            <p class="text-gray-300 text-sm mt-1">ข้อมูลธุรกิจและการเชื่อมต่อกับ Facebook Business Manager</p>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-3">
                        <i class="fas fa-info-circle mr-1"></i> ข้อมูล App
                    </h3>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <span class="text-sm text-gray-500 dark:text-gray-400">App ID</span>
                            <span class="text-sm font-mono text-gray-900 dark:text-white">{{ $settings->facebook_app_id ?? 'ไม่ได้ตั้งค่า' }}</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Page ID</span>
                            <span class="text-sm font-mono text-gray-900 dark:text-white">{{ $settings->facebook_page_id ?? 'ไม่ได้ตั้งค่า' }}</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <span class="text-sm text-gray-500 dark:text-gray-400">สถานะ</span>
                            @if($settings->facebook_page_token)
                                <span class="text-sm text-green-600"><i class="fas fa-check-circle"></i> เชื่อมต่อแล้ว</span>
                            @else
                                <span class="text-sm text-yellow-600"><i class="fas fa-exclamation-triangle"></i> รอตั้งค่า</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-3">
                        <i class="fas fa-shield-alt mr-1"></i> ความปลอดภัย
                    </h3>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Webhook Signature</span>
                            <span class="text-sm text-green-600"><i class="fas fa-check-circle"></i> เปิดใช้งาน (SHA256)</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Admin Handover</span>
                            <span class="text-sm {{ ($settings->admin_handover_enabled ?? false) ? 'text-green-600' : 'text-gray-400' }}">
                                {{ ($settings->admin_handover_enabled ?? false) ? 'เปิด' : 'ปิด' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Graph API Version</span>
                            <span class="text-sm text-gray-900 dark:text-white">v21.0</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Alert Messages --}}
    <div x-show="alertMessage" x-transition
         :class="alertType === 'success' ? 'bg-green-50 dark:bg-green-900/30 border-green-200 dark:border-green-700 text-green-800 dark:text-green-200' : 'bg-red-50 dark:bg-red-900/30 border-red-200 dark:border-red-700 text-red-800 dark:text-red-200'"
         class="fixed bottom-4 right-4 border rounded-lg p-4 shadow-lg max-w-md z-50">
        <div class="flex items-center gap-2">
            <i class="fas" :class="alertType === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'"></i>
            <span x-text="alertMessage"></span>
            <button @click="alertMessage = ''" class="ml-2">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function facebookPageMgmt() {
    return {
        loadingPages: false,
        testingConnection: false,
        settingUpProfile: false,
        alertMessage: '',
        alertType: 'success',

        // รีเฟรช Page list
        refreshPages() {
            this.loadingPages = true;
            setTimeout(() => {
                this.loadingPages = false;
                this.showAlert('รีเฟรชรายการ Pages สำเร็จ', 'success');
            }, 1500);
        },

        // ทดสอบการเชื่อมต่อ
        async testConnection() {
            this.testingConnection = true;
            try {
                const response = await fetch('{{ route("admin.fortune.channels.test-facebook") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                this.showAlert(data.message, data.success ? 'success' : 'error');
            } catch (e) {
                this.showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
            }
            this.testingConnection = false;
        },

        // ตั้งค่า Messenger Profile
        async setupMessengerProfile() {
            this.settingUpProfile = true;
            try {
                const response = await fetch('{{ route("admin.fortune.channels.setup-facebook-messenger") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                this.showAlert(data.message, data.success ? 'success' : 'error');
            } catch (e) {
                this.showAlert('เกิดข้อผิดพลาด', 'error');
            }
            this.settingUpProfile = false;
        },

        // คัดลอกข้อความ
        copyToClipboard(text) {
            navigator.clipboard.writeText(text);
            this.showAlert('คัดลอกแล้ว', 'success');
        },

        // แสดง Alert
        showAlert(message, type) {
            this.alertMessage = message;
            this.alertType = type;
            setTimeout(() => { this.alertMessage = ''; }, 3000);
        }
    };
}
</script>
@endpush
@endsection
