@extends('setup.layout')

@section('title', 'ตรวจสอบความต้องการของระบบ')

@section('content')
<div>
    <h2 class="text-2xl font-bold text-gray-800 mb-6">ตรวจสอบความต้องการของระบบ</h2>

    <!-- PHP Version -->
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-3">เวอร์ชัน PHP</h3>
        <div class="bg-gray-50 p-4 rounded-lg">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <span class="text-gray-700">{{ $requirements['php']['name'] }}</span>
                    <span class="ml-2 text-sm text-gray-500">(ติดตั้ง: {{ $requirements['php']['value'] }})</span>
                </div>
                @if($requirements['php']['passed'])
                    <i class="fas fa-check-circle text-green-500 text-xl"></i>
                @else
                    <i class="fas fa-times-circle text-red-500 text-xl"></i>
                @endif
            </div>
        </div>
    </div>

    <!-- PHP Extensions -->
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-3">PHP Extensions</h3>
        <div class="bg-gray-50 p-4 rounded-lg space-y-2">
            @foreach($requirements['extensions'] as $extension)
            <div class="flex items-center justify-between">
                <span class="text-gray-700">{{ $extension['name'] }}</span>
                @if($extension['passed'])
                    <i class="fas fa-check-circle text-green-500"></i>
                @else
                    <i class="fas fa-times-circle text-red-500"></i>
                @endif
            </div>
            @endforeach
        </div>
    </div>

    <!-- File Permissions -->
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-3">สิทธิ์การเขียนไฟล์</h3>
        <div class="bg-gray-50 p-4 rounded-lg space-y-2">
            @foreach($requirements['permissions'] as $permission)
            <div class="flex items-center justify-between">
                <span class="text-gray-700">{{ $permission['name'] }}</span>
                @if($permission['passed'])
                    <i class="fas fa-check-circle text-green-500"></i>
                @else
                    <i class="fas fa-times-circle text-red-500"></i>
                @endif
            </div>
            @endforeach
        </div>
    </div>

    @if(!$allPassed)
    <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-circle text-red-400"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">ระบบไม่ตรงตามความต้องการ</h3>
                <div class="mt-2 text-sm text-red-700">
                    <p>โปรดแก้ไขปัญหาที่พบก่อนดำเนินการต่อ:</p>
                    <ul class="list-disc list-inside mt-2">
                        @if(!$requirements['php']['passed'])
                        <li>อัพเกรด PHP เป็นเวอร์ชัน 8.1 หรือสูงกว่า</li>
                        @endif
                        @foreach($requirements['extensions'] as $ext)
                            @if(!$ext['passed'])
                            <li>ติดตั้ง PHP extension: {{ $ext['name'] }}</li>
                            @endif
                        @endforeach
                        @foreach($requirements['permissions'] as $perm)
                            @if(!$perm['passed'])
                            <li>ตั้งค่าสิทธิ์การเขียนไฟล์สำหรับ: {{ $perm['name'] }}</li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-check-circle text-green-400"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-green-800">ระบบพร้อมสำหรับการติดตั้ง!</h3>
                <p class="mt-2 text-sm text-green-700">
                    เซิร์ฟเวอร์ของคุณตรงตามความต้องการทั้งหมดแล้ว สามารถดำเนินการต่อได้
                </p>
            </div>
        </div>
    </div>
    @endif

    <div class="flex justify-between">
        <a href="{{ route('setup.index') }}" class="inline-flex items-center px-6 py-3 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded-lg transition duration-300">
            <i class="fas fa-arrow-left mr-2"></i>
            ย้อนกลับ
        </a>
        @if($allPassed)
        <a href="{{ route('setup.database') }}" class="inline-flex items-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition duration-300">
            ถัดไป
            <i class="fas fa-arrow-right ml-2"></i>
        </a>
        @else
        <button onclick="location.reload()" class="inline-flex items-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition duration-300">
            <i class="fas fa-sync mr-2"></i>
            ตรวจสอบอีกครั้ง
        </button>
        @endif
    </div>
</div>
@endsection
