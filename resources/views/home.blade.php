@extends('layouts.app')

@section('title', 'หน้าแรก')

@section('content')
<!-- Hero Section -->
<div class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
        <div class="text-center">
            <h1 class="text-4xl md:text-6xl font-bold mb-6">
                ยินดีต้อนรับสู่ {{ config('app.name') }}
            </h1>
            <p class="text-xl md:text-2xl mb-8 text-indigo-100">
                ตลาดกลางออนไลน์สำหรับผู้ประกอบการไทย<br>พร้อมระบบ MLM ที่ยุติธรรม
            </p>
            <div class="flex flex-col sm:flex-row justify-center space-y-4 sm:space-y-0 sm:space-x-4">
                <a href="{{ route('products.index') }}" class="px-8 py-3 bg-white text-indigo-600 rounded-lg font-semibold hover:bg-gray-100 transition">
                    เลือกซื้อสินค้า
                </a>
                @guest
                    <a href="{{ route('register') }}" class="px-8 py-3 bg-indigo-800 text-white rounded-lg font-semibold hover:bg-indigo-900 transition">
                        สมัครสมาชิก
                    </a>
                @endguest
            </div>
        </div>
    </div>
</div>

<!-- Features Section -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="text-center mb-12">
        <h2 class="text-3xl font-bold text-gray-900 mb-4">ทำไมต้องเลือกเรา?</h2>
        <p class="text-lg text-gray-600">ระบบครบวงจรสำหรับผู้ประกอบการและลูกค้า</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="text-center p-6 bg-white rounded-lg shadow-md">
            <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            <h3 class="text-xl font-semibold mb-2">ซื้อขายง่าย</h3>
            <p class="text-gray-600">ระบบที่ใช้งานง่าย รวดเร็ว ปลอดภัย</p>
        </div>

        <div class="text-center p-6 bg-white rounded-lg shadow-md">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="text-xl font-semibold mb-2">รายได้เสริม MLM</h3>
            <p class="text-gray-600">สร้างรายได้จากการแนะนำเพื่อน</p>
        </div>

        <div class="text-center p-6 bg-white rounded-lg shadow-md">
            <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
            <h3 class="text-xl font-semibold mb-2">เปิดร้านค้าออนไลน์</h3>
            <p class="text-gray-600">สร้างร้านค้าของคุณเองได้ฟรี</p>
        </div>
    </div>
</div>

<!-- Featured Products -->
@if(isset($featuredProducts) && $featuredProducts->count() > 0)
<div class="bg-gray-50 py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-3xl font-bold text-gray-900">สินค้าแนะนำ</h2>
            <a href="{{ route('products.index') }}" class="text-indigo-600 hover:text-indigo-500 font-medium">
                ดูทั้งหมด →
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($featuredProducts as $product)
                @include('components.product-card', ['product' => $product])
            @endforeach
        </div>
    </div>
</div>
@endif

<!-- CTA Section -->
<div class="bg-indigo-700 text-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl font-bold mb-4">พร้อมเริ่มต้นแล้วหรือยัง?</h2>
        <p class="text-xl mb-8 text-indigo-100">สมัครสมาชิกวันนี้และรับสิทธิพิเศษมากมาย</p>
        @guest
            <a href="{{ route('register') }}" class="inline-block px-8 py-3 bg-white text-indigo-600 rounded-lg font-semibold hover:bg-gray-100 transition">
                สมัครสมาชิกฟรี
            </a>
        @else
            <a href="{{ route('mlm.dashboard') }}" class="inline-block px-8 py-3 bg-white text-indigo-600 rounded-lg font-semibold hover:bg-gray-100 transition">
                ดูแดชบอร์ด MLM
            </a>
        @endguest
    </div>
</div>

<!-- Stats Section -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
        <div>
            <div class="text-4xl font-bold text-indigo-600 mb-2">{{ $stats['users'] ?? '10,000+' }}</div>
            <div class="text-gray-600">สมาชิก</div>
        </div>
        <div>
            <div class="text-4xl font-bold text-indigo-600 mb-2">{{ $stats['vendors'] ?? '500+' }}</div>
            <div class="text-gray-600">ร้านค้า</div>
        </div>
        <div>
            <div class="text-4xl font-bold text-indigo-600 mb-2">{{ $stats['products'] ?? '50,000+' }}</div>
            <div class="text-gray-600">สินค้า</div>
        </div>
        <div>
            <div class="text-4xl font-bold text-indigo-600 mb-2">{{ $stats['orders'] ?? '100,000+' }}</div>
            <div class="text-gray-600">ออเดอร์</div>
        </div>
    </div>
</div>
@endsection
