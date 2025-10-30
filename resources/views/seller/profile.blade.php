@extends('layouts.seller')

@section('title', 'โปรไฟล์')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-2xl shadow-xl p-6">
        <h1 class="text-3xl font-bold text-gray-800 mb-4">โปรไฟล์</h1>

        <div class="max-w-2xl">
            <div class="flex items-center space-x-6 mb-8">
                <div class="w-24 h-24 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-full flex items-center justify-center text-white text-4xl font-bold">
                    {{ substr($user->name, 0, 1) }}
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">{{ $user->name }}</h2>
                    <p class="text-gray-600">{{ $user->email }}</p>
                    <span class="inline-block mt-2 px-3 py-1 bg-cyan-100 text-cyan-800 rounded-full text-sm font-semibold">ผู้ขาย</span>
                </div>
            </div>

            <div class="space-y-4">
                <div class="border-t pt-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">ข้อมูลบัญชี</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อ</label>
                            <input type="text" value="{{ $user->name }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg" readonly>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">อีเมล</label>
                            <input type="email" value="{{ $user->email }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg" readonly>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">บทบาท</label>
                            <input type="text" value="ผู้ขาย" class="w-full px-4 py-2 border border-gray-300 rounded-lg" readonly>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">วันที่สมัคร</label>
                            <input type="text" value="{{ $user->created_at->format('d/m/Y') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg" readonly>
                        </div>
                    </div>
                </div>

                <div class="border-t pt-4">
                    <p class="text-gray-500 text-sm">ระบบจัดการโปรไฟล์แบบเต็มรูปแบบกำลังพัฒนา</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
