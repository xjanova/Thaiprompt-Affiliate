@extends('layouts.admin')

@section('title', 'Mobile App Dashboard')

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- Header --}}
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
            <span class="text-4xl">📱</span>
            Mobile App Dashboard
        </h1>
        <p class="text-gray-500 dark:text-gray-400 mt-2">
            จัดการแอพมือถือ - Admin ควบคุมได้ 3 อย่างเท่านั้น
        </p>
    </div>

    {{-- Info Banner --}}
    <div class="bg-gradient-to-r from-blue-500 to-cyan-500 rounded-2xl p-6 mb-8 text-white">
        <div class="flex items-start gap-4">
            <div class="text-4xl">💡</div>
            <div>
                <h3 class="text-xl font-bold mb-2">แอพเป็น Standalone</h3>
                <p class="text-blue-100">
                    แอพมือถือถูกออกแบบให้เป็นอิสระจากเซิร์ฟเวอร์ การตั้งค่าส่วนใหญ่อยู่ในแอพโดยตรง
                    Admin ควบคุมได้เฉพาะ: <strong>Push Notifications</strong>, <strong>Banner โฆษณา</strong>,
                    และดู <strong>Device Analytics</strong> เท่านั้น
                </p>
            </div>
        </div>
    </div>

    {{-- Stats Overview --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        {{-- Device Stats Card --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">📊 Device Analytics</h3>
                <a href="{{ route('admin.mobile-app.analytics.index') }}"
                   class="text-blue-500 hover:text-blue-600 text-sm font-medium">
                    ดูทั้งหมด →
                </a>
            </div>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-gray-500 dark:text-gray-400">เครื่องทั้งหมด</span>
                    <span class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($deviceStats['total'] ?? 0) }}
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-500 dark:text-gray-400">Active (7 วัน)</span>
                    <span class="text-lg font-semibold text-green-500">
                        {{ number_format($deviceStats['active'] ?? 0) }}
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-500 dark:text-gray-400">วันนี้</span>
                    <span class="text-lg font-semibold text-blue-500">
                        +{{ number_format($deviceStats['today'] ?? 0) }}
                    </span>
                </div>
                <div class="flex gap-4 mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-2">
                        <span class="text-xl"></span>
                        <span class="text-sm text-gray-600 dark:text-gray-400">
                            {{ number_format($deviceStats['ios'] ?? 0) }}
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xl">🤖</span>
                        <span class="text-sm text-gray-600 dark:text-gray-400">
                            {{ number_format($deviceStats['android'] ?? 0) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Push Notification Stats Card --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">📢 Push Notifications</h3>
                <a href="{{ route('admin.mobile-app.push.index') }}"
                   class="text-blue-500 hover:text-blue-600 text-sm font-medium">
                    ดูทั้งหมด →
                </a>
            </div>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-gray-500 dark:text-gray-400">ส่งทั้งหมด</span>
                    <span class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($pushStats['total_sent'] ?? 0) }}
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-500 dark:text-gray-400">เดือนนี้</span>
                    <span class="text-lg font-semibold text-purple-500">
                        {{ number_format($pushStats['this_month'] ?? 0) }}
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-500 dark:text-gray-400">Success Rate</span>
                    <span class="text-lg font-semibold text-green-500">
                        {{ $pushStats['success_rate'] ?? 0 }}%
                    </span>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('admin.mobile-app.push.create') }}"
                   class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 bg-purple-500 hover:bg-purple-600 text-white rounded-lg transition">
                    <span>➕</span>
                    <span>ส่ง Push ใหม่</span>
                </a>
            </div>
        </div>

        {{-- Banner Stats Card --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">🖼️ Banner โฆษณา</h3>
                <a href="{{ route('admin.mobile-app.banners.index') }}"
                   class="text-blue-500 hover:text-blue-600 text-sm font-medium">
                    ดูทั้งหมด →
                </a>
            </div>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-gray-500 dark:text-gray-400">Active</span>
                    <span class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($bannerStats['active'] ?? 0) }}
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-500 dark:text-gray-400">Total Views</span>
                    <span class="text-lg font-semibold text-blue-500">
                        {{ number_format($bannerStats['total_views'] ?? 0) }}
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-500 dark:text-gray-400">CTR</span>
                    <span class="text-lg font-semibold text-green-500">
                        {{ $bannerStats['ctr'] ?? 0 }}%
                    </span>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('admin.mobile-app.banners.create') }}"
                   class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg transition">
                    <span>➕</span>
                    <span>สร้าง Banner ใหม่</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">⚡ Quick Actions</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="{{ route('admin.mobile-app.push.create') }}"
               class="flex items-center gap-4 p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl hover:bg-purple-100 dark:hover:bg-purple-900/30 transition group">
                <div class="w-12 h-12 bg-purple-500 rounded-xl flex items-center justify-center text-white text-xl">
                    📢
                </div>
                <div>
                    <h4 class="font-semibold text-gray-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400">
                        ส่ง Push Notification
                    </h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        ส่งข้อความถึงผู้ใช้แอพ
                    </p>
                </div>
            </a>

            <a href="{{ route('admin.mobile-app.banners.create') }}"
               class="flex items-center gap-4 p-4 bg-orange-50 dark:bg-orange-900/20 rounded-xl hover:bg-orange-100 dark:hover:bg-orange-900/30 transition group">
                <div class="w-12 h-12 bg-orange-500 rounded-xl flex items-center justify-center text-white text-xl">
                    🖼️
                </div>
                <div>
                    <h4 class="font-semibold text-gray-900 dark:text-white group-hover:text-orange-600 dark:group-hover:text-orange-400">
                        สร้าง Banner โฆษณา
                    </h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        เพิ่ม banner ในหน้าช้อปปิ้ง
                    </p>
                </div>
            </a>

            <a href="{{ route('admin.mobile-app.analytics.export') }}"
               class="flex items-center gap-4 p-4 bg-green-50 dark:bg-green-900/20 rounded-xl hover:bg-green-100 dark:hover:bg-green-900/30 transition group">
                <div class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center text-white text-xl">
                    📥
                </div>
                <div>
                    <h4 class="font-semibold text-gray-900 dark:text-white group-hover:text-green-600 dark:group-hover:text-green-400">
                        Export Device Data
                    </h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        ดาวน์โหลดข้อมูลเครื่องเป็น CSV
                    </p>
                </div>
            </a>
        </div>
    </div>

    {{-- Admin Control Info --}}
    <div class="mt-8 bg-gray-50 dark:bg-gray-900 rounded-2xl p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ℹ️ สิ่งที่ Admin ควบคุมได้</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="flex items-start gap-3">
                <span class="text-2xl">✅</span>
                <div>
                    <h4 class="font-medium text-gray-900 dark:text-white">Push Notifications</h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        ส่งข้อความ, โปรโมชั่น, ข่าวสารถึงผู้ใช้
                    </p>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <span class="text-2xl">✅</span>
                <div>
                    <h4 class="font-medium text-gray-900 dark:text-white">Banner โฆษณา</h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        จัดการ banner ในหน้าช้อปปิ้ง
                    </p>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <span class="text-2xl">✅</span>
                <div>
                    <h4 class="font-medium text-gray-900 dark:text-white">Device Analytics</h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        ดูสถิติเครื่องที่ลงทะเบียน, DAU, MAU
                    </p>
                </div>
            </div>
        </div>

        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
            <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-3">❌ สิ่งที่ Admin ควบคุมไม่ได้ (ตั้งค่าในแอพ)</h4>
            <div class="flex flex-wrap gap-2">
                <span class="px-3 py-1 bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-full text-sm">
                    Feature Flags
                </span>
                <span class="px-3 py-1 bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-full text-sm">
                    Theme/Colors
                </span>
                <span class="px-3 py-1 bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-full text-sm">
                    Rank Configuration
                </span>
                <span class="px-3 py-1 bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-full text-sm">
                    MLM Settings
                </span>
                <span class="px-3 py-1 bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-full text-sm">
                    UI Configuration
                </span>
            </div>
        </div>
    </div>
</div>
@endsection
