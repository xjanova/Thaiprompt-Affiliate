{{--
    User Rider Status - ติดตามสถานะการสมัครไรเดอร์
    แสดงสถานะและขั้นตอนการสมัคร
--}}

@extends('layouts.user')

@section('title', 'ติดตามสถานะการสมัคร')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
            ติดตามสถานะการสมัครไรเดอร์
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">
            ข้อมูลการสมัครของคุณกำลังอยู่ระหว่างการตรวจสอบ
        </p>
    </div>

    {{-- Status Card --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 mb-8 border border-gray-200/50 dark:border-gray-700/50">
        {{-- Current Status --}}
        <div class="flex items-center justify-center mb-8">
            @if($rider->status === 'pending')
                <div class="text-center">
                    <div class="w-24 h-24 mx-auto rounded-full bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center mb-4">
                        <i class="fas fa-clock text-5xl text-yellow-500"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">รอตรวจสอบ</h2>
                    <p class="text-gray-600 dark:text-gray-400 mt-2">
                        ทีมงานกำลังตรวจสอบข้อมูลของคุณ
                    </p>
                </div>
            @elseif($rider->status === 'rejected')
                <div class="text-center">
                    <div class="w-24 h-24 mx-auto rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mb-4">
                        <i class="fas fa-times-circle text-5xl text-red-500"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-red-600 dark:text-red-400">ไม่ผ่านการอนุมัติ</h2>
                    <p class="text-gray-600 dark:text-gray-400 mt-2">
                        {{ $rider->rejection_reason ?? 'กรุณาติดต่อทีมงานเพื่อขอข้อมูลเพิ่มเติม' }}
                    </p>
                </div>
            @elseif($rider->status === 'suspended')
                <div class="text-center">
                    <div class="w-24 h-24 mx-auto rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                        <i class="fas fa-ban text-5xl text-gray-500"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-600 dark:text-gray-400">ถูกระงับ</h2>
                    <p class="text-gray-600 dark:text-gray-400 mt-2">
                        บัญชีของคุณถูกระงับชั่วคราว กรุณาติดต่อทีมงาน
                    </p>
                </div>
            @endif
        </div>

        {{-- Progress Steps --}}
        <div class="relative mb-8">
            <div class="flex justify-between items-center">
                {{-- Step 1: สมัคร --}}
                <div class="flex flex-col items-center flex-1">
                    <div class="w-12 h-12 rounded-full bg-green-500 flex items-center justify-center z-10">
                        <i class="fas fa-check text-white"></i>
                    </div>
                    <p class="text-sm mt-2 text-center text-gray-700 dark:text-gray-300 font-medium">ส่งใบสมัคร</p>
                    <p class="text-xs text-gray-500">{{ $rider->created_at->thaidate('j M Y') }}</p>
                </div>

                {{-- Step 2: อัพโหลดเอกสาร --}}
                <div class="flex flex-col items-center flex-1">
                    @php
                        $hasDocuments = $rider->id_card_image || $rider->driver_license_image;
                    @endphp
                    <div class="w-12 h-12 rounded-full {{ $hasDocuments ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600' }} flex items-center justify-center z-10">
                        @if($hasDocuments)
                            <i class="fas fa-check text-white"></i>
                        @else
                            <i class="fas fa-file-alt text-white"></i>
                        @endif
                    </div>
                    <p class="text-sm mt-2 text-center text-gray-700 dark:text-gray-300 font-medium">อัพโหลดเอกสาร</p>
                    @if(!$hasDocuments)
                        <a href="{{ route('user.rider.documents') }}" class="text-xs text-purple-600 hover:underline">อัพโหลดเลย</a>
                    @endif
                </div>

                {{-- Step 3: รอตรวจสอบ --}}
                <div class="flex flex-col items-center flex-1">
                    <div class="w-12 h-12 rounded-full {{ $rider->status !== 'pending' ? ($rider->status === 'approved' ? 'bg-green-500' : 'bg-red-500') : 'bg-yellow-500' }} flex items-center justify-center z-10 animate-pulse">
                        @if($rider->status === 'approved')
                            <i class="fas fa-check text-white"></i>
                        @elseif($rider->status === 'rejected')
                            <i class="fas fa-times text-white"></i>
                        @else
                            <i class="fas fa-hourglass-half text-white"></i>
                        @endif
                    </div>
                    <p class="text-sm mt-2 text-center text-gray-700 dark:text-gray-300 font-medium">
                        {{ $rider->status === 'pending' ? 'รอตรวจสอบ' : ($rider->status === 'approved' ? 'อนุมัติแล้ว' : 'ไม่อนุมัติ') }}
                    </p>
                </div>

                {{-- Step 4: เริ่มงาน --}}
                <div class="flex flex-col items-center flex-1">
                    <div class="w-12 h-12 rounded-full {{ $rider->status === 'approved' ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600' }} flex items-center justify-center z-10">
                        <i class="fas fa-motorcycle text-white"></i>
                    </div>
                    <p class="text-sm mt-2 text-center text-gray-700 dark:text-gray-300 font-medium">เริ่มรับงาน</p>
                </div>
            </div>

            {{-- Progress Line --}}
            <div class="absolute top-6 left-0 right-0 h-0.5 bg-gray-300 dark:bg-gray-600 -z-0"></div>
            <div class="absolute top-6 left-0 h-0.5 bg-green-500 -z-0" style="width: {{ $rider->status === 'approved' ? '100%' : ($hasDocuments ? '50%' : '25%') }}"></div>
        </div>

        {{-- Application Info --}}
        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">ข้อมูลการสมัคร</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">ชื่อ-นามสกุล</p>
                    <p class="text-gray-900 dark:text-white font-medium">{{ $rider->full_name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">เบอร์โทร</p>
                    <p class="text-gray-900 dark:text-white font-medium">{{ $rider->phone }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">ยานพาหนะ</p>
                    <p class="text-gray-900 dark:text-white font-medium">{{ $rider->vehicle_type_text }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">วันที่สมัคร</p>
                    <p class="text-gray-900 dark:text-white font-medium">{{ $rider->created_at->thaidate('j M Y H:i') }}</p>
                </div>
            </div>
        </div>

        {{-- Documents Status --}}
        <div class="border-t border-gray-200 dark:border-gray-700 pt-6 mt-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">เอกสารที่อัพโหลด</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach([
                    ['key' => 'id_card_image', 'label' => 'บัตรประชาชน', 'icon' => 'fa-id-card'],
                    ['key' => 'driver_license_image', 'label' => 'ใบขับขี่', 'icon' => 'fa-car'],
                    ['key' => 'vehicle_registration_image', 'label' => 'ทะเบียนรถ', 'icon' => 'fa-file-alt'],
                    ['key' => 'profile_image', 'label' => 'รูปโปรไฟล์', 'icon' => 'fa-user'],
                ] as $doc)
                    <div class="p-4 rounded-xl border-2 {{ $rider->{$doc['key']} ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : 'border-gray-300 dark:border-gray-600' }}">
                        <div class="text-center">
                            <i class="fas {{ $doc['icon'] }} text-2xl {{ $rider->{$doc['key']} ? 'text-green-500' : 'text-gray-400' }}"></i>
                            <p class="text-sm mt-2 {{ $rider->{$doc['key']} ? 'text-green-600 dark:text-green-400' : 'text-gray-500' }}">
                                {{ $doc['label'] }}
                            </p>
                            @if($rider->{$doc['key']})
                                <span class="text-xs text-green-500"><i class="fas fa-check"></i> อัพโหลดแล้ว</span>
                            @else
                                <span class="text-xs text-gray-400">ยังไม่อัพโหลด</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            @if(!$rider->id_card_image || !$rider->driver_license_image)
                <div class="mt-4 text-center">
                    <a href="{{ route('user.rider.documents') }}"
                       class="inline-flex items-center px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-xl font-medium transition">
                        <i class="fas fa-upload mr-2"></i>
                        อัพโหลดเอกสาร
                    </a>
                </div>
            @endif
        </div>
    </div>

    {{-- Help Section --}}
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-2xl p-6 border border-blue-200 dark:border-blue-800">
        <h3 class="text-lg font-bold text-blue-900 dark:text-blue-300 mb-2">
            <i class="fas fa-question-circle mr-2"></i>
            มีคำถามหรือต้องการความช่วยเหลือ?
        </h3>
        <p class="text-blue-700 dark:text-blue-400 mb-4">
            หากมีข้อสงสัยเกี่ยวกับการสมัครหรือสถานะ สามารถติดต่อทีมงานได้ทันที
        </p>
        <a href="{{ route('user.tickets.create') }}"
           class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-medium transition">
            <i class="fas fa-headset mr-2"></i>
            ติดต่อทีมงาน
        </a>
    </div>
</div>
@endsection
