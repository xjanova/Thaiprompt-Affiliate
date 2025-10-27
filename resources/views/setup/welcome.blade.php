@extends('setup.layout')

@section('title', 'ยินดีต้อนรับ')

@section('content')
<div class="text-center">
    <div class="mb-6">
        <i class="fas fa-rocket text-6xl text-indigo-600"></i>
    </div>

    <h2 class="text-3xl font-bold text-gray-800 mb-4">
        ยินดีต้อนรับสู่ ThaiPrompt Marketplace!
    </h2>

    <p class="text-gray-600 mb-8 text-lg">
        ตัวช่วยติดตั้งนี้จะแนะนำคุณผ่านขั้นตอนการติดตั้งระบบ<br>
        ใช้เวลาเพียงไม่กี่นาทีเท่านั้น
    </p>

    <div class="grid md:grid-cols-2 gap-6 mb-8">
        <div class="bg-blue-50 p-6 rounded-lg">
            <i class="fas fa-shopping-cart text-3xl text-blue-600 mb-3"></i>
            <h3 class="font-semibold text-gray-800 mb-2">Multi-Vendor Marketplace</h3>
            <p class="text-sm text-gray-600">ระบบตลาดออนไลน์หลายผู้ขาย พร้อมระบบจัดการร้านค้า</p>
        </div>

        <div class="bg-purple-50 p-6 rounded-lg">
            <i class="fas fa-sitemap text-3xl text-purple-600 mb-3"></i>
            <h3 class="font-semibold text-gray-800 mb-2">MLM System</h3>
            <p class="text-sm text-gray-600">ระบบตลาดแชร์แบบครบวงจร พร้อมติดตามรายได้</p>
        </div>

        <div class="bg-green-50 p-6 rounded-lg">
            <i class="fas fa-hotel text-3xl text-green-600 mb-3"></i>
            <h3 class="font-semibold text-gray-800 mb-2">Hotel Management</h3>
            <p class="text-sm text-gray-600">ระบบจัดการโรงแรมและการจองห้องพัก</p>
        </div>

        <div class="bg-yellow-50 p-6 rounded-lg">
            <i class="fas fa-credit-card text-3xl text-yellow-600 mb-3"></i>
            <h3 class="font-semibold text-gray-800 mb-2">Payment Gateway</h3>
            <p class="text-sm text-gray-600">รองรับการชำระเงินหลากหลายช่องทาง</p>
        </div>
    </div>

    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6 text-left">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-triangle text-yellow-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-yellow-700">
                    <strong>คำเตือน:</strong> ก่อนเริ่มต้น โปรดแน่ใจว่าคุณมี:
                </p>
                <ul class="list-disc list-inside text-sm text-yellow-700 mt-2">
                    <li>ข้อมูลการเชื่อมต่อฐานข้อมูล MySQL</li>
                    <li>สิทธิ์ในการเขียนไฟล์ในโฟลเดอร์ storage/ และ bootstrap/cache/</li>
                    <li>PHP 8.1 หรือสูงกว่า</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="flex justify-center gap-4">
        <a href="{{ route('setup.requirements') }}" class="inline-flex items-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-md transition duration-300">
            เริ่มต้นติดตั้ง
            <i class="fas fa-arrow-right ml-2"></i>
        </a>
    </div>
</div>
@endsection
