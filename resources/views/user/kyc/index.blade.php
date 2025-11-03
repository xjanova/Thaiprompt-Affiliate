@extends('layouts.user')

@section('title', 'ยืนยันตัวตน (KYC)')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <!-- Header -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h1 class="text-2xl font-bold text-gray-900">ยืนยันตัวตน (KYC)</h1>
        <p class="text-sm text-gray-600 mt-1">ยืนยันตัวตนเพื่อความปลอดภัยและเพิ่มความน่าเชื่อถือของบัญชี</p>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl p-4">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 rounded-xl p-4">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
        </div>
    @endif

    @if(session('warning'))
        <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-xl p-4">
            <i class="fas fa-exclamation-triangle mr-2"></i>{{ session('warning') }}
        </div>
    @endif

    @if(session('info'))
        <div class="bg-blue-50 border border-blue-200 text-blue-800 rounded-xl p-4">
            <i class="fas fa-info-circle mr-2"></i>{{ session('info') }}
        </div>
    @endif

    <!-- KYC Status Card -->
    <div class="bg-white rounded-xl shadow-md p-6">
        @if(auth()->user()->kyc_status === 'not_submitted')
            <div class="text-center py-8">
                <div class="w-24 h-24 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-id-card text-4xl text-gray-400"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-900 mb-2">คุณยังไม่ได้ยืนยันตัวตน</h2>
                <p class="text-gray-600 mb-6">ยืนยันตัวตนเพื่อเพิ่มความปลอดภัยและความน่าเชื่อถือของบัญชีของคุณ</p>
                <a href="{{ route('user.kyc.create') }}"
                   class="inline-flex items-center px-6 py-3 bg-gradient-primary text-white rounded-lg hover:opacity-90 transition">
                    <i class="fas fa-upload mr-2"></i>
                    เริ่มยืนยันตัวตน
                </a>
            </div>
        @elseif(auth()->user()->kyc_status === 'pending')
            <div class="text-center py-8">
                <div class="w-24 h-24 mx-auto mb-4 bg-yellow-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-clock text-4xl text-yellow-600"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-900 mb-2">กำลังรอการตรวจสอบ</h2>
                <p class="text-gray-600 mb-4">เอกสารของคุณกำลังอยู่ระหว่างการตรวจสอบโดยแอดมิน</p>

                @if($kycVerification)
                    <div class="bg-gray-50 rounded-lg p-4 max-w-md mx-auto text-left">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-gray-600">วันที่ส่ง:</span>
                            <span class="text-sm font-medium text-gray-900">
                                {{ $kycVerification->submitted_at ? $kycVerification->submitted_at->format('d/m/Y H:i') : '-' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">สถานะ:</span>
                            <span class="inline-block px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-medium">
                                รอการตรวจสอบ
                            </span>
                        </div>
                    </div>

                    <a href="{{ route('user.kyc.show', $kycVerification) }}"
                       class="inline-flex items-center px-4 py-2 mt-4 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                        <i class="fas fa-eye mr-2"></i>
                        ดูรายละเอียด
                    </a>
                @endif
            </div>
        @elseif(auth()->user()->kyc_status === 'approved')
            <div class="text-center py-8">
                <div class="w-24 h-24 mx-auto mb-4 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-check-circle text-4xl text-green-600"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-900 mb-2">ยืนยันตัวตนสำเร็จ</h2>
                <p class="text-gray-600 mb-4">บัญชีของคุณได้รับการยืนยันตัวตนเรียบร้อยแล้ว</p>

                @if($kycVerification)
                    <div class="bg-green-50 rounded-lg p-4 max-w-md mx-auto text-left">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-gray-600">วันที่อนุมัติ:</span>
                            <span class="text-sm font-medium text-gray-900">
                                {{ auth()->user()->kyc_verified_at ? auth()->user()->kyc_verified_at->format('d/m/Y H:i') : '-' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">สถานะ:</span>
                            <span class="inline-block px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">
                                <i class="fas fa-check mr-1"></i>อนุมัติแล้ว
                            </span>
                        </div>
                    </div>

                    <a href="{{ route('user.kyc.show', $kycVerification) }}"
                       class="inline-flex items-center px-4 py-2 mt-4 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                        <i class="fas fa-eye mr-2"></i>
                        ดูรายละเอียด
                    </a>
                @endif
            </div>
        @elseif(auth()->user()->kyc_status === 'rejected')
            <div class="text-center py-8">
                <div class="w-24 h-24 mx-auto mb-4 bg-red-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-times-circle text-4xl text-red-600"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-900 mb-2">ไม่ผ่านการยืนยัน</h2>
                <p class="text-gray-600 mb-4">เอกสารของคุณไม่ผ่านการตรวจสอบ</p>

                @if($kycVerification && $kycVerification->rejection_reason)
                    <div class="bg-red-50 rounded-lg p-4 max-w-md mx-auto text-left mb-4">
                        <p class="text-sm text-gray-600 mb-2">เหตุผลในการปฏิเสธ:</p>
                        <p class="text-sm text-red-800">{{ $kycVerification->rejection_reason }}</p>
                    </div>
                @endif

                <a href="{{ route('user.kyc.create') }}"
                   class="inline-flex items-center px-6 py-3 bg-gradient-primary text-white rounded-lg hover:opacity-90 transition">
                    <i class="fas fa-redo mr-2"></i>
                    ส่งคำขอใหม่
                </a>
            </div>
        @endif
    </div>

    <!-- Information Card -->
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
        <h3 class="text-lg font-bold text-blue-900 mb-4">
            <i class="fas fa-info-circle mr-2"></i>ข้อมูลเกี่ยวกับการยืนยันตัวตน
        </h3>
        <ul class="space-y-2 text-sm text-blue-800">
            <li class="flex items-start">
                <i class="fas fa-check-circle mt-1 mr-2 text-blue-600"></i>
                <span>การยืนยันตัวตนช่วยเพิ่มความปลอดภัยและความน่าเชื่อถือของบัญชี</span>
            </li>
            <li class="flex items-start">
                <i class="fas fa-check-circle mt-1 mr-2 text-blue-600"></i>
                <span>คุณจะต้องอัปโหลดรูปบัตรประชาชนและรูปถ่ายตัวเองพร้อมบัตรประชาชน</span>
            </li>
            <li class="flex items-start">
                <i class="fas fa-check-circle mt-1 mr-2 text-blue-600"></i>
                <span>ข้อมูลของคุณจะถูกเก็บเป็นความลับและปลอดภัย</span>
            </li>
            <li class="flex items-start">
                <i class="fas fa-check-circle mt-1 mr-2 text-blue-600"></i>
                <span>การตรวจสอบอาจใช้เวลา 1-3 วันทำการ</span>
            </li>
        </ul>
    </div>
</div>
@endsection
