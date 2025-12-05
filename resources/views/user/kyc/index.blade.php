@extends('layouts.user-arrow-x')

@section('title', 'ยืนยันตัวตน (KYC)')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">ยืนยันตัวตน (KYC)</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">ยืนยันตัวตนเพื่อความปลอดภัยและเพิ่มความน่าเชื่อถือของบัญชี</p>
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

    {{-- OCR Success Message --}}
    @if(session('ocr_success'))
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-300 rounded-xl p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="fas fa-robot text-2xl text-green-600"></i>
                </div>
                <div class="ml-3 flex-1">
                    <h3 class="text-sm font-bold text-green-900">
                        <i class="fas fa-check-circle mr-1"></i>{{ session('ocr_success') }}
                    </h3>
                    <p class="text-xs text-green-700 mt-1">
                        ข้อมูลจากบัตรประชาชนถูกอ่านและบันทึกอัตโนมัติแล้ว แอดมินจะตรวจสอบความถูกต้องอีกครั้ง
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- OCR Error/Warning Message --}}
    @if(session('ocr_error'))
        <div class="bg-gradient-to-r from-orange-50 to-red-50 border-2 border-orange-300 rounded-xl p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-2xl text-orange-600"></i>
                </div>
                <div class="ml-3 flex-1">
                    <h3 class="text-sm font-bold text-orange-900">
                        <i class="fas fa-robot mr-1"></i>ระบบ OCR: {{ session('ocr_error') }}
                    </h3>
                    @if(session('ocr_suggestion'))
                        <p class="text-xs text-orange-700 mt-2">
                            <i class="fas fa-lightbulb mr-1"></i><strong>คำแนะนำ:</strong> {{ session('ocr_suggestion') }}
                        </p>
                    @endif
                    <div class="mt-3 p-2 bg-white dark:bg-gray-800 bg-opacity-60 rounded text-xs text-gray-700 dark:text-gray-300">
                        <i class="fas fa-info-circle mr-1"></i>
                        <strong>หมายเหตุ:</strong> คำขอของคุณถูกส่งเรียบร้อยแล้ว แต่ระบบไม่สามารถอ่านข้อมูลจากบัตรอัตโนมัติได้
                        แอดมินจะกรอกข้อมูลด้วยตนเองในขั้นตอนการตรวจสอบ
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- KYC Status Card -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
        {{-- null หรือ 'not_submitted' = ยังไม่ได้ส่งเอกสาร --}}
        @if(auth()->user()->kyc_status === 'not_submitted' || auth()->user()->kyc_status === null)
            <div class="text-center py-8">
                <div class="w-24 h-24 mx-auto mb-4 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                    <i class="fas fa-id-card text-4xl text-gray-400"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">คุณยังไม่ได้ยืนยันตัวตน</h2>
                <p class="text-gray-600 dark:text-gray-400 mb-6">ยืนยันตัวตนเพื่อเพิ่มความปลอดภัยและความน่าเชื่อถือของบัญชีของคุณ</p>
                <a href="{{ route('user.kyc.create') }}"
                   class="inline-flex items-center px-6 py-3 bg-gradient-primary text-white rounded-lg hover:opacity-90 transition">
                    <i class="fas fa-upload mr-2"></i>
                    เริ่มยืนยันตัวตน
                </a>
            </div>
        @elseif(auth()->user()->kyc_status === 'pending')
            <div class="text-center py-8">
                {{-- สถานะ Badge ที่ชัดเจน --}}
                <div class="mb-6">
                    <span class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-yellow-400 to-orange-500 text-white rounded-full text-lg font-bold shadow-lg animate-pulse">
                        <i class="fas fa-hourglass-half mr-3 text-2xl"></i>
                        กำลังรอตรวจสอบเอกสาร
                    </span>
                </div>

                <div class="w-24 h-24 mx-auto mb-4 bg-yellow-100 dark:bg-yellow-900/30 rounded-full flex items-center justify-center ring-4 ring-yellow-300 dark:ring-yellow-700">
                    <i class="fas fa-clock text-4xl text-yellow-600 dark:text-yellow-400 animate-spin" style="animation-duration: 3s;"></i>
                </div>

                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    <i class="fas fa-file-alt text-yellow-500 mr-2"></i>
                    เอกสารอยู่ระหว่างการตรวจสอบ
                </h2>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    ทีมงานกำลังตรวจสอบเอกสารของคุณ โปรดรอผลการตรวจสอบภายใน <strong class="text-yellow-600">1-3 วันทำการ</strong>
                </p>

                @if($kycVerification)
                    <div class="bg-gradient-to-br from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 border-2 border-yellow-200 dark:border-yellow-800 rounded-xl p-6 max-w-md mx-auto text-left shadow-md">
                        <h3 class="font-bold text-yellow-800 dark:text-yellow-300 mb-4 text-center">
                            <i class="fas fa-clipboard-list mr-2"></i>รายละเอียดการส่งเอกสาร
                        </h3>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-2 bg-white/50 dark:bg-gray-800/50 rounded-lg">
                                <span class="text-sm text-gray-600 dark:text-gray-400 flex items-center">
                                    <i class="fas fa-calendar-alt mr-2 text-yellow-500"></i>วันที่ส่ง:
                                </span>
                                <span class="text-sm font-bold text-gray-900 dark:text-white">
                                    {{ $kycVerification->submitted_at ? $kycVerification->submitted_at->format('d/m/Y H:i') : '-' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between p-2 bg-white/50 dark:bg-gray-800/50 rounded-lg">
                                <span class="text-sm text-gray-600 dark:text-gray-400 flex items-center">
                                    <i class="fas fa-info-circle mr-2 text-yellow-500"></i>สถานะ:
                                </span>
                                <span class="inline-flex items-center px-3 py-1 bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-300 rounded-full text-xs font-bold">
                                    <i class="fas fa-spinner fa-spin mr-1"></i>
                                    รอการตรวจสอบ
                                </span>
                            </div>
                        </div>
                    </div>

                    <a href="{{ route('user.kyc.show', $kycVerification) }}"
                       class="inline-flex items-center px-6 py-3 mt-6 bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white rounded-xl shadow-lg hover:shadow-xl transition-all">
                        <i class="fas fa-eye mr-2"></i>
                        ดูเอกสารที่ส่ง
                    </a>
                @endif

                {{-- คำแนะนำขณะรอ --}}
                <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl max-w-md mx-auto">
                    <p class="text-sm text-blue-800 dark:text-blue-300">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>หมายเหตุ:</strong> คุณจะได้รับการแจ้งเตือนเมื่อการตรวจสอบเสร็จสิ้น
                    </p>
                </div>
            </div>
        @elseif(auth()->user()->kyc_status === 'approved')
            <div class="text-center py-8">
                {{-- สถานะ Badge ที่ชัดเจน --}}
                <div class="mb-6">
                    <span class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-full text-lg font-bold shadow-lg">
                        <i class="fas fa-shield-check mr-3 text-2xl"></i>
                        ยืนยันตัวตนเรียบร้อยแล้ว
                    </span>
                </div>

                <div class="w-24 h-24 mx-auto mb-4 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center ring-4 ring-green-300 dark:ring-green-700">
                    <i class="fas fa-check-circle text-4xl text-green-600 dark:text-green-400"></i>
                </div>

                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    <i class="fas fa-user-check text-green-500 mr-2"></i>
                    บัญชีได้รับการยืนยันแล้ว
                </h2>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    คุณสามารถใช้งานฟีเจอร์ทั้งหมดได้อย่างเต็มรูปแบบ รวมถึงการถอนเงิน
                </p>

                @if($kycVerification)
                    <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-2 border-green-200 dark:border-green-800 rounded-xl p-6 max-w-md mx-auto text-left shadow-md">
                        <h3 class="font-bold text-green-800 dark:text-green-300 mb-4 text-center">
                            <i class="fas fa-certificate mr-2"></i>ข้อมูลการยืนยัน
                        </h3>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-2 bg-white/50 dark:bg-gray-800/50 rounded-lg">
                                <span class="text-sm text-gray-600 dark:text-gray-400 flex items-center">
                                    <i class="fas fa-calendar-check mr-2 text-green-500"></i>วันที่อนุมัติ:
                                </span>
                                <span class="text-sm font-bold text-gray-900 dark:text-white">
                                    {{ auth()->user()->kyc_verified_at ? auth()->user()->kyc_verified_at->format('d/m/Y H:i') : '-' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between p-2 bg-white/50 dark:bg-gray-800/50 rounded-lg">
                                <span class="text-sm text-gray-600 dark:text-gray-400 flex items-center">
                                    <i class="fas fa-award mr-2 text-green-500"></i>สถานะ:
                                </span>
                                <span class="inline-flex items-center px-3 py-1 bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300 rounded-full text-xs font-bold">
                                    <i class="fas fa-check-circle mr-1"></i>
                                    อนุมัติแล้ว
                                </span>
                            </div>
                        </div>
                    </div>

                    <a href="{{ route('user.kyc.show', $kycVerification) }}"
                       class="inline-flex items-center px-6 py-3 mt-6 bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white rounded-xl shadow-lg hover:shadow-xl transition-all">
                        <i class="fas fa-eye mr-2"></i>
                        ดูรายละเอียด
                    </a>
                @endif

                {{-- สิทธิพิเศษที่ได้รับ --}}
                <div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-3 max-w-lg mx-auto">
                    <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg text-center">
                        <i class="fas fa-money-bill-wave text-green-500 text-xl mb-1"></i>
                        <p class="text-xs text-green-700 dark:text-green-400 font-semibold">ถอนเงินได้</p>
                    </div>
                    <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg text-center">
                        <i class="fas fa-shield-alt text-green-500 text-xl mb-1"></i>
                        <p class="text-xs text-green-700 dark:text-green-400 font-semibold">บัญชีปลอดภัย</p>
                    </div>
                    <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg text-center">
                        <i class="fas fa-star text-green-500 text-xl mb-1"></i>
                        <p class="text-xs text-green-700 dark:text-green-400 font-semibold">ฟีเจอร์ครบ</p>
                    </div>
                </div>
            </div>
        @elseif(auth()->user()->kyc_status === 'rejected')
            <div class="text-center py-8">
                {{-- สถานะ Badge ที่ชัดเจน --}}
                <div class="mb-6">
                    <span class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-red-500 to-rose-600 text-white rounded-full text-lg font-bold shadow-lg">
                        <i class="fas fa-exclamation-triangle mr-3 text-2xl"></i>
                        ไม่ผ่านการตรวจสอบ
                    </span>
                </div>

                <div class="w-24 h-24 mx-auto mb-4 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center ring-4 ring-red-300 dark:ring-red-700">
                    <i class="fas fa-times-circle text-4xl text-red-600 dark:text-red-400"></i>
                </div>

                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    <i class="fas fa-file-times text-red-500 mr-2"></i>
                    เอกสารไม่ผ่านการตรวจสอบ
                </h2>
                <p class="text-gray-600 dark:text-gray-400 mb-4">กรุณาตรวจสอบเหตุผลด้านล่าง และส่งเอกสารใหม่</p>

                @if($kycVerification && $kycVerification->rejection_reason)
                    <div class="bg-gradient-to-br from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20 border-2 border-red-300 dark:border-red-800 rounded-xl p-6 max-w-md mx-auto text-left mb-6 shadow-md">
                        <h3 class="font-bold text-red-800 dark:text-red-300 mb-3 flex items-center">
                            <i class="fas fa-exclamation-circle mr-2"></i>เหตุผลในการปฏิเสธ
                        </h3>
                        <p class="text-red-700 dark:text-red-400 bg-white/50 dark:bg-gray-800/50 p-3 rounded-lg">
                            {{ $kycVerification->rejection_reason }}
                        </p>
                    </div>
                @endif

                <a href="{{ route('user.kyc.create') }}"
                   class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-xl shadow-lg hover:shadow-xl transition-all font-bold text-lg">
                    <i class="fas fa-redo mr-3"></i>
                    ส่งเอกสารใหม่
                </a>

                {{-- คำแนะนำ --}}
                <div class="mt-6 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl max-w-md mx-auto">
                    <p class="text-sm text-yellow-800 dark:text-yellow-300">
                        <i class="fas fa-lightbulb mr-2"></i>
                        <strong>คำแนะนำ:</strong> ถ่ายรูปบัตรให้ชัดเจน มีแสงสว่างเพียงพอ และเห็นข้อมูลครบถ้วน
                    </p>
                </div>
            </div>
        @else
            {{-- Fallback: กรณี kyc_status เป็น null หรือค่าอื่นที่ไม่รู้จัก ให้แสดงแบบ not_submitted --}}
            <div class="text-center py-8">
                <div class="w-24 h-24 mx-auto mb-4 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                    <i class="fas fa-id-card text-4xl text-gray-400"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">คุณยังไม่ได้ยืนยันตัวตน</h2>
                <p class="text-gray-600 dark:text-gray-400 mb-6">ยืนยันตัวตนเพื่อเพิ่มความปลอดภัยและความน่าเชื่อถือของบัญชีของคุณ</p>
                <a href="{{ route('user.kyc.create') }}"
                   class="inline-flex items-center px-6 py-3 bg-gradient-primary text-white rounded-lg hover:opacity-90 transition">
                    <i class="fas fa-upload mr-2"></i>
                    เริ่มยืนยันตัวตน
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
                <span>คุณจะต้องอัปโหลดรูปบัตรประชาชน หรือ ใบขับขี่ และรูปถ่ายตัวเองพร้อมบัตร</span>
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
