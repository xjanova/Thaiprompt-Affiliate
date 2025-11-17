@extends('layouts.admin-v3')

@section('title', 'ตัวอย่างการจัดวางสมาชิก MLM')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white dark:text-white mb-2">ตัวอย่างการจัดวางสมาชิก MLM</h1>
        <p class="text-gray-600 dark:text-gray-400 dark:text-gray-400">ดูแบบอนิเมชั่นแสดงวิธีการจัดวางสมาชิกใหม่แบบต่างๆ</p>
    </div>

    <!-- Info Alert -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6 mb-8">
        <div class="flex items-start">
            <i class="fas fa-info-circle text-blue-600 dark:text-blue-400 text-2xl mt-0.5 mr-4"></i>
            <div>
                <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-300 mb-2">เกี่ยวกับการจัดวางอัตโนมัติ (Auto Placement)</h3>
                <p class="text-blue-800 dark:text-blue-400 mb-3">
                    การจัดวางอัตโนมัติช่วยให้ระบบกำหนดตำแหน่งของสมาชิกใหม่โดยอัตโนมัติตามกลยุทธ์ที่เลือก
                    ช่วยสร้างความสมดุลและเพิ่มโอกาสในการสร้างคอมมิชชั่น
                </p>
                <ul class="space-y-2 text-sm text-blue-700 dark:text-blue-400">
                    <li class="flex items-start">
                        <i class="fas fa-circle text-xs text-blue-600 dark:text-blue-400 mr-2 mt-1"></i>
                        <span><strong>Left First:</strong> เหมาะสำหรับผู้ที่ต้องการสร้างขาซ้ายให้แข็งแรงก่อน</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-circle text-xs text-blue-600 dark:text-blue-400 mr-2 mt-1"></i>
                        <span><strong>Right First:</strong> เหมาะสำหรับผู้ที่ต้องการสร้างขาขวาให้แข็งแรงก่อน</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-circle text-xs text-blue-600 dark:text-blue-400 mr-2 mt-1"></i>
                        <span><strong>Fill Level:</strong> เติมเต็มทีละชั้น สร้างโครงสร้างที่เป็นระเบียบ</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-circle text-xs text-blue-600 dark:text-blue-400 mr-2 mt-1"></i>
                        <span><strong>Weak Leg:</strong> วางสมาชิกในขาที่อ่อนกว่าเพื่อสร้างความสมดุล</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-circle text-xs text-blue-600 dark:text-blue-400 mr-2 mt-1"></i>
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
    <div class="mt-8 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 border border-purple-200 dark:border-purple-800 rounded-xl p-6">
        <div class="flex items-start">
            <i class="fas fa-cog text-purple-600 dark:text-purple-400 text-2xl mt-0.5 mr-4"></i>
            <div>
                <h3 class="text-lg font-semibold text-purple-900 dark:text-purple-300 mb-2">การตั้งค่ากลยุทธ์การจัดวาง</h3>
                <p class="text-purple-800 dark:text-purple-400">
                    คุณสามารถกำหนดกลยุทธ์การจัดวางเริ่มต้นได้ที่
                    <a href="{{ route('admin.mlm.settings.index') }}" class="underline font-semibold hover:text-purple-900 dark:hover:text-purple-300">
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
           class="bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white px-8 py-3 rounded-xl font-medium shadow-lg transition-all duration-200 flex items-center gap-2">
            <i class="fas fa-arrow-left"></i>
            กลับไปที่ตั้งค่า MLM
        </a>
    </div>
</div>
@endsection
