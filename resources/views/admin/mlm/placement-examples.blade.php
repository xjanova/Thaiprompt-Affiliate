@extends('layouts.admin')

@section('title', 'ตัวอย่างการจัดวางสมาชิก MLM')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">ตัวอย่างการจัดวางสมาชิก MLM</h1>
        <p class="text-gray-600">ดูแบบอนิเมชั่นแสดงวิธีการจัดวางสมาชิกใหม่แบบต่างๆ</p>
    </div>

    <!-- Info Alert -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-6 mb-8">
        <div class="flex items-start">
            <svg class="w-6 h-6 text-blue-600 mt-0.5 mr-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <h3 class="text-lg font-semibold text-blue-900 mb-2">เกี่ยวกับการจัดวางอัตโนมัติ (Auto Placement)</h3>
                <p class="text-blue-800 mb-3">
                    การจัดวางอัตโนมัติช่วยให้ระบบกำหนดตำแหน่งของสมาชิกใหม่โดยอัตโนมัติตามกลยุทธ์ที่เลือก
                    ช่วยสร้างความสมดุลและเพิ่มโอกาสในการสร้างคอมมิชชั่น
                </p>
                <ul class="space-y-2 text-sm text-blue-700">
                    <li class="flex items-start">
                        <span class="text-blue-600 mr-2">•</span>
                        <span><strong>Left First:</strong> เหมาะสำหรับผู้ที่ต้องการสร้างขาซ้ายให้แข็งแรงก่อน</span>
                    </li>
                    <li class="flex items-start">
                        <span class="text-blue-600 mr-2">•</span>
                        <span><strong>Right First:</strong> เหมาะสำหรับผู้ที่ต้องการสร้างขาขวาให้แข็งแรงก่อน</span>
                    </li>
                    <li class="flex items-start">
                        <span class="text-blue-600 mr-2">•</span>
                        <span><strong>Fill Level:</strong> เติมเต็มทีละชั้น สร้างโครงสร้างที่เป็นระเบียบ</span>
                    </li>
                    <li class="flex items-start">
                        <span class="text-blue-600 mr-2">•</span>
                        <span><strong>Weak Leg:</strong> วางสมาชิกในขาที่อ่อนกว่าเพื่อสร้างความสมดุล</span>
                    </li>
                    <li class="flex items-start">
                        <span class="text-blue-600 mr-2">•</span>
                        <span><strong>Balanced:</strong> สลับซ้าย-ขวาเพื่อความสมดุลสูงสุด</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Placement Examples Grid -->
    <div class="space-y-8">
        <!-- Left First -->
        <x-mlm.placement-animation type="left_first" />

        <!-- Right First -->
        <x-mlm.placement-animation type="right_first" />

        <!-- Fill Level -->
        <x-mlm.placement-animation type="fill_level" />

        <!-- Weak Leg -->
        <x-mlm.placement-animation type="weak_leg" />

        <!-- Balanced -->
        <x-mlm.placement-animation type="balanced" />
    </div>

    <!-- Settings Note -->
    <div class="mt-8 bg-gradient-to-r from-purple-50 to-pink-50 border border-purple-200 rounded-xl p-6">
        <div class="flex items-start">
            <svg class="w-6 h-6 text-purple-600 mt-0.5 mr-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <div>
                <h3 class="text-lg font-semibold text-purple-900 mb-2">การตั้งค่ากลยุทธ์การจัดวาง</h3>
                <p class="text-purple-800">
                    คุณสามารถกำหนดกลยุทธ์การจัดวางเริ่มต้นได้ที่
                    <a href="{{ route('admin.mlm.settings.index') }}" class="underline font-semibold hover:text-purple-900">
                        ตั้งค่าระบบ MLM
                    </a>
                    ในส่วน "Placement Strategies"
                </p>
            </div>
        </div>
    </div>

    <!-- Back Button -->
    <div class="mt-8 flex justify-center">
        <a href="{{ route('admin.mlm.settings.index') }}"
           class="bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white px-8 py-3 rounded-lg font-medium shadow-lg transition-all duration-200 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            กลับไปที่ตั้งค่า MLM
        </a>
    </div>
</div>
@endsection
