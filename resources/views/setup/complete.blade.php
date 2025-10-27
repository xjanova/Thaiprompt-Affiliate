@extends('setup.layout')

@section('title', 'การติดตั้งเสร็จสมบูรณ์')

@section('content')
<div class="text-center">
    <div class="mb-6">
        <i class="fas fa-check-circle text-6xl text-green-500"></i>
    </div>

    <h2 class="text-3xl font-bold text-gray-800 mb-4">
        การติดตั้งเสร็จสมบูรณ์!
    </h2>

    <p class="text-gray-600 mb-8 text-lg">
        ระบบ ThaiPrompt Marketplace พร้อมใช้งานแล้ว
    </p>

    <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6 text-left">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-check-circle text-green-400"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-green-800">การติดตั้งสำเร็จ!</h3>
                <div class="mt-2 text-sm text-green-700">
                    <p>สิ่งที่เราได้ทำให้คุณแล้ว:</p>
                    <ul class="list-disc list-inside mt-2">
                        <li>ตั้งค่าการเชื่อมต่อฐานข้อมูล</li>
                        <li>สร้างตารางในฐานข้อมูลทั้งหมด</li>
                        <li>สร้างบัญชีผู้ดูแลระบบ</li>
                        <li>ตั้งค่าระบบเบื้องต้น</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6 text-left">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-info-circle text-blue-400"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">ขั้นตอนถัดไป:</h3>
                <div class="mt-2 text-sm text-blue-700">
                    <ul class="list-decimal list-inside">
                        <li>เข้าสู่ระบบด้วยบัญชีผู้ดูแลที่สร้างไว้</li>
                        <li>ตั้งค่าระบบในหน้า Admin Settings</li>
                        <li>กำหนด Payment Gateway (Stripe, PromptPay)</li>
                        <li>ตั้งค่า Email และการแจ้งเตือน</li>
                        <li>กำหนด MLM Commission Structure</li>
                        <li>เริ่มต้นใช้งานระบบ!</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="grid md:grid-cols-3 gap-4 mb-8">
        <div class="bg-gray-50 p-4 rounded-lg">
            <i class="fas fa-book text-2xl text-blue-600 mb-2"></i>
            <h3 class="font-semibold text-gray-800 mb-1">เอกสารประกอบ</h3>
            <p class="text-sm text-gray-600">อ่านคู่มือการใช้งานได้ที่ README.md</p>
        </div>

        <div class="bg-gray-50 p-4 rounded-lg">
            <i class="fas fa-cog text-2xl text-purple-600 mb-2"></i>
            <h3 class="font-semibold text-gray-800 mb-1">การตั้งค่า</h3>
            <p class="text-sm text-gray-600">ตั้งค่าระบบได้ที่เมนู Admin Settings</p>
        </div>

        <div class="bg-gray-50 p-4 rounded-lg">
            <i class="fas fa-life-ring text-2xl text-green-600 mb-2"></i>
            <h3 class="font-semibold text-gray-800 mb-1">ช่วยเหลือ</h3>
            <p class="text-sm text-gray-600">ติดต่อ support@thaiprompt.com</p>
        </div>
    </div>

    <div class="flex justify-center gap-4">
        <a href="{{ route('login') }}" class="inline-flex items-center px-8 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-lg transition duration-300">
            <i class="fas fa-sign-in-alt mr-2"></i>
            เข้าสู่ระบบ
        </a>
    </div>

    <div class="mt-8 text-sm text-gray-500">
        <p>ขอบคุณที่เลือกใช้ ThaiPrompt Marketplace!</p>
    </div>
</div>
@endsection
