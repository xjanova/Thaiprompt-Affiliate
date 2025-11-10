@extends('layouts.seller')

@section('title', 'ตั้งค่า')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-40 h-40 bg-white opacity-10 rounded-full"></div>
        <div class="relative z-10">
            <h1 class="text-3xl md:text-4xl font-bold mb-2">⚙️ ตั้งค่า</h1>
            <p class="text-indigo-100">จัดการการตั้งค่าบัญชีและร้านค้า</p>
        </div>
    </div>

    <!-- Settings Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Store Settings -->
        <a href="{{ route('seller.store.settings') }}" class="bg-white rounded-2xl shadow-xl p-6 hover:shadow-2xl transition transform hover:scale-105">
            <div class="flex items-start gap-4">
                <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center text-white text-3xl">
                    🏪
                </div>
                <div class="flex-1">
                    <h2 class="text-xl font-bold text-gray-900 mb-2">ตั้งค่าร้านค้า</h2>
                    <p class="text-gray-600 text-sm">แก้ไขข้อมูลร้านค้า ชื่อร้าน โลโก้ และรายละเอียดอื่นๆ</p>
                </div>
            </div>
        </a>

        <!-- Profile Settings -->
        <a href="{{ route('seller.profile') }}" class="bg-white rounded-2xl shadow-xl p-6 hover:shadow-2xl transition transform hover:scale-105">
            <div class="flex items-start gap-4">
                <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl flex items-center justify-center text-white text-3xl">
                    👤
                </div>
                <div class="flex-1">
                    <h2 class="text-xl font-bold text-gray-900 mb-2">โปรไฟล์</h2>
                    <p class="text-gray-600 text-sm">จัดการข้อมูลส่วนตัวและการตั้งค่าบัญชี</p>
                </div>
            </div>
        </a>

        <!-- POS Settings -->
        <a href="{{ route('seller.pos.settings') }}" class="bg-white rounded-2xl shadow-xl p-6 hover:shadow-2xl transition transform hover:scale-105">
            <div class="flex items-start gap-4">
                <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center text-white text-3xl">
                    🏪
                </div>
                <div class="flex-1">
                    <h2 class="text-xl font-bold text-gray-900 mb-2">ตั้งค่า POS</h2>
                    <p class="text-gray-600 text-sm">ตั้งค่าระบบขายหน้าร้าน (Point of Sale)</p>
                </div>
            </div>
        </a>

        <!-- Analytics Settings -->
        <a href="{{ route('seller.analytics.settings') }}" class="bg-white rounded-2xl shadow-xl p-6 hover:shadow-2xl transition transform hover:scale-105">
            <div class="flex items-start gap-4">
                <div class="w-16 h-16 bg-gradient-to-br from-orange-500 to-red-600 rounded-xl flex items-center justify-center text-white text-3xl">
                    📊
                </div>
                <div class="flex-1">
                    <h2 class="text-xl font-bold text-gray-900 mb-2">ตั้งค่า Analytics</h2>
                    <p class="text-gray-600 text-sm">จัดการการตั้งค่าระบบวิเคราะห์ข้อมูล</p>
                </div>
            </div>
        </a>

        <!-- Package/Subscription -->
        <a href="{{ route('seller.packages') }}" class="bg-white rounded-2xl shadow-xl p-6 hover:shadow-2xl transition transform hover:scale-105">
            <div class="flex items-start gap-4">
                <div class="w-16 h-16 bg-gradient-to-br from-yellow-500 to-amber-600 rounded-xl flex items-center justify-center text-white text-3xl">
                    📦
                </div>
                <div class="flex-1">
                    <h2 class="text-xl font-bold text-gray-900 mb-2">แพ็กเกจ</h2>
                    <p class="text-gray-600 text-sm">จัดการแพ็กเกจและการสมัครสมาชิก</p>
                    @if($store && $store->package)
                        <p class="text-xs text-green-600 font-semibold mt-2">แพ็กเกจปัจจุบัน: {{ $store->package->name }}</p>
                    @endif
                </div>
            </div>
        </a>

        <!-- Wallet Settings -->
        <a href="{{ route('seller.wallet.index') }}" class="bg-white rounded-2xl shadow-xl p-6 hover:shadow-2xl transition transform hover:scale-105">
            <div class="flex items-start gap-4">
                <div class="w-16 h-16 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center text-white text-3xl">
                    💳
                </div>
                <div class="flex-1">
                    <h2 class="text-xl font-bold text-gray-900 mb-2">กระเป๋าเงิน</h2>
                    <p class="text-gray-600 text-sm">จัดการกระเป๋าเงินและการถอนเงิน</p>
                </div>
            </div>
        </a>
    </div>

    <!-- Account Information -->
    <div class="bg-white rounded-2xl shadow-xl p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-6">ข้อมูลบัญชี</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อ</label>
                <div class="px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg">
                    {{ $user->name }}
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">อีเมล</label>
                <div class="px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg">
                    {{ $user->email }}
                </div>
            </div>
            @if($store)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อร้านค้า</label>
                    <div class="px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg">
                        {{ $store->store_name }}
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">สถานะร้านค้า</label>
                    <div class="px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg">
                        @if($store->status === 'active')
                            <span class="text-green-600 font-semibold">✓ เปิดใช้งาน</span>
                        @else
                            <span class="text-gray-600">ปิดใช้งาน</span>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
