@extends('layouts.app')

@section('title', 'หน้าแรก')

@section('content')
<div class="relative overflow-hidden">
    <!-- Hero Section -->
    <section class="relative bg-gradient-to-r from-indigo-600 to-purple-600 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
            <div class="text-center">
                <h1 class="text-5xl font-bold mb-6 animate-fade-in">
                    ยินดีต้อนรับสู่ TP-Affiliate
                </h1>
                <p class="text-xl mb-8 text-indigo-100">
                    ระบบ Affiliate Marketing ที่ทันสมัย มืออาชีพ และพร้อมใช้งาน
                </p>
                <div class="flex gap-4 justify-center">
                    @guest
                        <a href="{{ route('register') }}" class="inline-flex items-center px-8 py-4 bg-white text-indigo-600 font-semibold rounded-lg shadow-lg hover:bg-gray-100 transition duration-300">
                            เริ่มต้นใช้งาน
                        </a>
                        <a href="{{ route('login') }}" class="inline-flex items-center px-8 py-4 bg-indigo-500 text-white font-semibold rounded-lg shadow-lg hover:bg-indigo-700 transition duration-300">
                            เข้าสู่ระบบ
                        </a>
                    @else
                        <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center px-8 py-4 bg-white text-indigo-600 font-semibold rounded-lg shadow-lg hover:bg-gray-100 transition duration-300">
                            เข้าสู่แดชบอร์ด
                        </a>
                    @endguest
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">คุณสมบัติเด่น</h2>
                <p class="text-xl text-gray-600">ระบบที่ครบครันสำหรับการทำ Affiliate Marketing</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="p-6 bg-gray-50 rounded-xl shadow-md hover:shadow-xl transition">
                    <div class="text-4xl mb-4">🎯</div>
                    <h3 class="text-xl font-semibold mb-2">ติดตั้งง่าย</h3>
                    <p class="text-gray-600">ติดตั้งได้ภายใน 2 นาที ไม่ต้องแก้ไขโค้ด</p>
                </div>

                <div class="p-6 bg-gray-50 rounded-xl shadow-md hover:shadow-xl transition">
                    <div class="text-4xl mb-4">🎨</div>
                    <h3 class="text-xl font-semibold mb-2">UI/UX สวยงาม</h3>
                    <p class="text-gray-600">ออกแบบมาอย่างมืออาชีพ รองรับทุกอุปกรณ์</p>
                </div>

                <div class="p-6 bg-gray-50 rounded-xl shadow-md hover:shadow-xl transition">
                    <div class="text-4xl mb-4">👑</div>
                    <h3 class="text-xl font-semibold mb-2">ระบบจัดการ</h3>
                    <p class="text-gray-600">จัดการทุกอย่างได้ง่ายจาก Dashboard</p>
                </div>

                <div class="p-6 bg-gray-50 rounded-xl shadow-md hover:shadow-xl transition">
                    <div class="text-4xl mb-4">📊</div>
                    <h3 class="text-xl font-semibold mb-2">รายงานแบบเรียลไทม์</h3>
                    <p class="text-gray-600">ดูสถิติและรายงานแบบเรียลไทม์</p>
                </div>

                <div class="p-6 bg-gray-50 rounded-xl shadow-md hover:shadow-xl transition">
                    <div class="text-4xl mb-4">🔐</div>
                    <h3 class="text-xl font-semibold mb-2">ปลอดภัย</h3>
                    <p class="text-gray-600">ระบบรักษาความปลอดภัยระดับสูง</p>
                </div>

                <div class="p-6 bg-gray-50 rounded-xl shadow-md hover:shadow-xl transition">
                    <div class="text-4xl mb-4">⚡</div>
                    <h3 class="text-xl font-semibold mb-2">รวดเร็ว</h3>
                    <p class="text-gray-600">โหลดเร็ว ประมวลผลเร็ว ใช้งานลื่นไหล</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">สถิติของเรา</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                <div class="bg-white p-8 rounded-xl shadow-md text-center">
                    <div class="text-5xl font-bold text-indigo-600 mb-2">{{ $stats['total_users'] }}</div>
                    <div class="text-gray-600">ผู้ใช้ทั้งหมด</div>
                </div>

                <div class="bg-white p-8 rounded-xl shadow-md text-center">
                    <div class="text-5xl font-bold text-purple-600 mb-2">{{ $stats['total_affiliates'] }}</div>
                    <div class="text-gray-600">Affiliates</div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 bg-gradient-to-r from-indigo-600 to-purple-600 text-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl font-bold mb-6">พร้อมเริ่มต้นแล้วหรือยัง?</h2>
            <p class="text-xl mb-8 text-indigo-100">สมัครสมาชิกวันนี้ และเริ่มสร้างรายได้จาก Affiliate Marketing</p>
            @guest
                <a href="{{ route('register') }}" class="inline-flex items-center px-8 py-4 bg-white text-indigo-600 font-semibold rounded-lg shadow-lg hover:bg-gray-100 transition duration-300">
                    สมัครเลย ฟรี!
                </a>
            @endguest
        </div>
    </section>
</div>
@endsection
