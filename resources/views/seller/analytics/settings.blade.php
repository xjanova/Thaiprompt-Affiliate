@extends('layouts.seller')

@section('title', 'ตั้งค่า Analytics')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white rounded-2xl shadow-xl p-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">⚙️ ตั้งค่า Analytics</h1>
                <p class="text-gray-600 mt-2">จัดการการติดตามและเก็บข้อมูลวิเคราะห์</p>
            </div>
            <a href="{{ route('seller.analytics.index') }}" class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
                ← กลับไปหน้า Analytics
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
        {{ session('error') }}
    </div>
    @endif

    <!-- Analytics Settings Form -->
    <div class="bg-white rounded-2xl shadow-xl p-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">📊 การตั้งค่าทั่วไป</h2>

        <form method="POST" action="{{ route('seller.analytics.settings.update') }}" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Data Retention -->
            <div>
                <h3 class="text-lg font-semibold text-gray-800 mb-4">🗄️ การเก็บข้อมูล</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            จำนวนวันที่เก็บข้อมูล
                        </label>
                        <input type="number" name="retention_days" value="{{ $settings->retention_days }}"
                            min="7" max="730" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <p class="text-sm text-gray-500 mt-1">ข้อมูลที่เก่ากว่านี้จะถูกลบโดยอัตโนมัติ (ขั้นต่ำ 7 วัน, สูงสุด 730 วัน)</p>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="auto_cleanup_enabled" value="1"
                            {{ $settings->auto_cleanup_enabled ? 'checked' : '' }}
                            id="auto_cleanup_enabled"
                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                        <label for="auto_cleanup_enabled" class="ml-2 text-sm font-medium text-gray-700">
                            เปิดใช้งานการล้างข้อมูลอัตโนมัติ
                        </label>
                    </div>
                </div>
            </div>

            <hr>

            <!-- Tracking Settings -->
            <div>
                <h3 class="text-lg font-semibold text-gray-800 mb-4">🔍 การติดตาม</h3>
                <div class="space-y-3">
                    <div class="flex items-center">
                        <input type="checkbox" name="track_visits" value="1"
                            {{ $settings->track_visits ? 'checked' : '' }}
                            id="track_visits"
                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                        <label for="track_visits" class="ml-2 text-sm font-medium text-gray-700">
                            ติดตามการเข้าชม (Page Views)
                        </label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="track_unique_visitors" value="1"
                            {{ $settings->track_unique_visitors ? 'checked' : '' }}
                            id="track_unique_visitors"
                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                        <label for="track_unique_visitors" class="ml-2 text-sm font-medium text-gray-700">
                            ติดตามผู้เยี่ยมชมไม่ซ้ำ (Unique Visitors)
                        </label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="track_session_duration" value="1"
                            {{ $settings->track_session_duration ? 'checked' : '' }}
                            id="track_session_duration"
                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                        <label for="track_session_duration" class="ml-2 text-sm font-medium text-gray-700">
                            ติดตามระยะเวลาการใช้งาน (Session Duration)
                        </label>
                    </div>
                </div>
            </div>

            <hr>

            <!-- Privacy Settings -->
            <div>
                <h3 class="text-lg font-semibold text-gray-800 mb-4">🔒 ความเป็นส่วนตัว</h3>
                <div class="space-y-3">
                    <div class="flex items-center">
                        <input type="checkbox" name="anonymize_ip" value="1"
                            {{ $settings->anonymize_ip ? 'checked' : '' }}
                            id="anonymize_ip"
                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                        <label for="anonymize_ip" class="ml-2 text-sm font-medium text-gray-700">
                            ซ่อน IP Address (เก็บแบบ Hash)
                        </label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="respect_dnt" value="1"
                            {{ $settings->respect_dnt ? 'checked' : '' }}
                            id="respect_dnt"
                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                        <label for="respect_dnt" class="ml-2 text-sm font-medium text-gray-700">
                            เคารพ Do Not Track (DNT)
                        </label>
                    </div>
                </div>
            </div>

            <hr>

            <!-- Email Reports -->
            <div>
                <h3 class="text-lg font-semibold text-gray-800 mb-4">📧 รายงานทางอีเมล</h3>
                <div class="space-y-3">
                    <div class="flex items-center">
                        <input type="checkbox" name="email_weekly_reports" value="1"
                            {{ $settings->email_weekly_reports ? 'checked' : '' }}
                            id="email_weekly_reports"
                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                        <label for="email_weekly_reports" class="ml-2 text-sm font-medium text-gray-700">
                            ส่งรายงานรายสัปดาห์
                        </label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="email_monthly_reports" value="1"
                            {{ $settings->email_monthly_reports ? 'checked' : '' }}
                            id="email_monthly_reports"
                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                        <label for="email_monthly_reports" class="ml-2 text-sm font-medium text-gray-700">
                            ส่งรายงานรายเดือน
                        </label>
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                    💾 บันทึกการตั้งค่า
                </button>
            </div>
        </form>
    </div>

    <!-- Manual Cleanup -->
    <div class="bg-white rounded-2xl shadow-xl p-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">🗑️ ล้างข้อมูลด้วยตัวเอง</h2>

        <div class="bg-yellow-50 border border-yellow-300 text-yellow-800 px-4 py-3 rounded-lg mb-4">
            <p class="font-medium">⚠️ คำเตือน</p>
            <p class="text-sm mt-1">การล้างข้อมูลจะไม่สามารถกู้คืนได้ กรุณาตรวจสอบให้แน่ใจก่อนทำการล้างข้อมูล</p>
        </div>

        <form method="POST" action="{{ route('seller.analytics.cleanup') }}" class="space-y-4"
            onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการล้างข้อมูลเก่า? การกระทำนี้ไม่สามารถย้อนกลับได้');">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    ล้างข้อมูลที่เก่ากว่า (วัน)
                </label>
                <input type="number" name="older_than_days" value="90"
                    min="7" max="730" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                <p class="text-sm text-gray-500 mt-1">ข้อมูลที่เก่ากว่าจำนวนวันนี้จะถูกลบทั้งหมด</p>
            </div>

            <div class="flex items-center">
                <input type="checkbox" name="confirm" value="1" required
                    id="confirm_cleanup"
                    class="w-4 h-4 text-red-600 bg-gray-100 border-gray-300 rounded focus:ring-red-500">
                <label for="confirm_cleanup" class="ml-2 text-sm font-medium text-gray-700">
                    ฉันเข้าใจและต้องการล้างข้อมูลเก่า
                </label>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-medium">
                    🗑️ ล้างข้อมูลเก่า
                </button>
            </div>
        </form>

        <div class="mt-6 p-4 bg-gray-50 rounded-lg">
            <h4 class="font-semibold text-gray-800 mb-2">ข้อมูลปัจจุบัน</h4>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-600">การตั้งค่าระยะเวลาเก็บข้อมูล:</span>
                    <span class="font-semibold text-gray-800">{{ $settings->retention_days }} วัน</span>
                </div>
                <div>
                    <span class="text-gray-600">ล้างข้อมูลอัตโนมัติ:</span>
                    <span class="font-semibold {{ $settings->auto_cleanup_enabled ? 'text-green-600' : 'text-red-600' }}">
                        {{ $settings->auto_cleanup_enabled ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
