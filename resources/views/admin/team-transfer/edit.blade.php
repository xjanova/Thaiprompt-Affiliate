@extends('layouts.admin')

@section('title', 'ดำเนินการย้ายทีม #' . $request->id)

@section('content')
<div class="container-fluid px-4 py-6">
    {{-- Back Button --}}
    <a href="{{ route('admin.team-transfer.show', $request) }}"
       class="inline-flex items-center text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white mb-6 transition">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        กลับไปรายละเอียด
    </a>

    {{-- Error Messages --}}
    @if($errors->any())
        <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-red-600 dark:text-red-400 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <div class="flex-1">
                    <h3 class="text-sm font-semibold text-red-800 dark:text-red-300 mb-2">กรุณาแก้ไขข้อผิดพลาด:</h3>
                    <ul class="list-disc list-inside text-sm text-red-700 dark:text-red-400 space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-red-600 dark:text-red-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <span class="text-red-800 dark:text-red-300 font-medium">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <div class="max-w-5xl mx-auto">
        {{-- Header --}}
        <div class="bg-gradient-to-r from-purple-500 to-blue-600 rounded-xl shadow-2xl p-8 mb-8 text-white">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h1 class="text-3xl font-bold mb-1">
                        ดำเนินการย้ายทีม
                    </h1>
                    <p class="text-white/80">คำขอ #{{ $request->id }}</p>
                </div>
                <span class="px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full text-sm font-bold">
                    ชำระเงินแล้ว
                </span>
            </div>
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <p class="text-white/70 text-sm mb-1">สมาชิกที่ขอย้าย</p>
                    <p class="font-bold text-lg">{{ $request->user->name ?? 'N/A' }}</p>
                    <p class="text-white/80 text-sm">{{ $request->member->member_code ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-white/70 text-sm mb-1">ค่าธรรมเนียมที่ชำระ</p>
                    <p class="font-bold text-2xl">{{ number_format($request->transfer_fee, 2) }} บาท</p>
                    <p class="text-white/80 text-sm">ชำระเมื่อ {{ $request->paid_at->locale('th')->diffForHumans() }}</p>
                </div>
            </div>
        </div>

        {{-- Transfer Summary --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8 mb-8 border border-gray-200 dark:border-gray-700">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">สรุปการย้าย</h2>

            {{-- Transfer Visual --}}
            <div class="relative mb-8">
                <div class="grid grid-cols-2 gap-8">
                    {{-- From --}}
                    <div class="text-center">
                        <div class="inline-flex p-6 bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 rounded-2xl border-2 border-red-200 dark:border-red-800 mb-4">
                            <div class="w-16 h-16 bg-red-500 rounded-full flex items-center justify-center">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                        </div>
                        <p class="text-xs font-semibold text-red-600 dark:text-red-400 uppercase tracking-wide mb-2">แม่ทีมเดิม</p>
                        <p class="font-bold text-lg text-gray-900 dark:text-white mb-1">
                            {{ $request->oldSponsor->user->name ?? 'N/A' }}
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ $request->oldSponsor->member_code ?? 'N/A' }}
                        </p>
                    </div>

                    {{-- To --}}
                    <div class="text-center">
                        <div class="inline-flex p-6 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-2xl border-2 border-green-200 dark:border-green-800 mb-4">
                            <div class="w-16 h-16 bg-green-500 rounded-full flex items-center justify-center">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                        </div>
                        <p class="text-xs font-semibold text-green-600 dark:text-green-400 uppercase tracking-wide mb-2">แม่ทีมใหม่</p>
                        <p class="font-bold text-lg text-gray-900 dark:text-white mb-1">
                            {{ $request->newSponsor->user->name ?? 'N/A' }}
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ $request->newSponsor->member_code ?? 'N/A' }}
                        </p>
                    </div>
                </div>

                {{-- Arrow --}}
                <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                    <div class="w-16 h-16 bg-white dark:bg-gray-800 rounded-full border-4 border-blue-500 flex items-center justify-center shadow-2xl">
                        <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Reason --}}
            @if($request->reason)
                <div class="mb-6">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">เหตุผลในการย้าย</h3>
                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                        <p class="text-gray-700 dark:text-gray-300">{{ $request->reason }}</p>
                    </div>
                </div>
            @endif

            {{-- Warning Box --}}
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border-2 border-yellow-200 dark:border-yellow-800 rounded-xl p-6">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 w-8 h-8 bg-yellow-500 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h4 class="text-sm font-bold text-yellow-800 dark:text-yellow-300 mb-2">⚠️ คำเตือนก่อนดำเนินการ</h4>
                        <ul class="text-sm text-yellow-700 dark:text-yellow-400 space-y-1 ml-4 list-disc">
                            <li>การย้ายทีมจะมีผลทันทีและไม่สามารถยกเลิกได้</li>
                            <li>ระบบจะอัพเดท Unilevel Path และ Binary Tree โดยอัตโนมัติ</li>
                            <li>Commission และ Rank จะถูกคำนวณใหม่ตามโครงสร้างใหม่</li>
                            <li>สมาชิกจะได้รับการแจ้งเตือนผ่าน LINE และ Email</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- Process Form --}}
        <form method="POST" action="{{ route('admin.team-transfer.process', $request) }}" x-data="{ isProcessing: false }">
            @csrf

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8 border border-gray-200 dark:border-gray-700">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">หมายเหตุจาก Admin</h2>

                <div class="mb-8">
                    <label for="admin_notes" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        หมายเหตุ (ไม่บังคับ)
                    </label>
                    <textarea id="admin_notes"
                              name="admin_notes"
                              rows="6"
                              maxlength="1000"
                              class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:text-white transition resize-none"
                              placeholder="เพิ่มหมายเหตุเกี่ยวกับการดำเนินการ (จะแสดงให้สมาชิกเห็น)"
                              x-model="notes">{{ old('admin_notes') }}</textarea>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        💡 สมาชิกจะสามารถดูหมายเหตุนี้ได้
                    </p>
                </div>

                {{-- Confirmation Checklist --}}
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-6 mb-8 border border-gray-200 dark:border-gray-600">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">✅ Checklist ก่อนดำเนินการ</h3>
                    <div class="space-y-3">
                        <label class="flex items-start gap-3 cursor-pointer group">
                            <input type="checkbox"
                                   required
                                   class="mt-1 w-5 h-5 text-blue-600 border-gray-300 dark:border-gray-600 rounded focus:ring-2 focus:ring-blue-500 dark:bg-gray-700">
                            <span class="text-sm text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white transition">
                                ข้อมูลแม่ทีมใหม่ถูกต้อง และไม่เป็นลูกทีมของสมาชิกที่ขอย้าย
                            </span>
                        </label>
                        <label class="flex items-start gap-3 cursor-pointer group">
                            <input type="checkbox"
                                   required
                                   class="mt-1 w-5 h-5 text-blue-600 border-gray-300 dark:border-gray-600 rounded focus:ring-2 focus:ring-blue-500 dark:bg-gray-700">
                            <span class="text-sm text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white transition">
                                สมาชิกได้ชำระค่าธรรมเนียมครบถ้วนแล้ว
                            </span>
                        </label>
                        <label class="flex items-start gap-3 cursor-pointer group">
                            <input type="checkbox"
                                   required
                                   class="mt-1 w-5 h-5 text-blue-600 border-gray-300 dark:border-gray-600 rounded focus:ring-2 focus:ring-blue-500 dark:bg-gray-700">
                            <span class="text-sm text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white transition">
                                ฉันเข้าใจว่าการย้ายทีมจะมีผลทันทีและไม่สามารถยกเลิกได้
                            </span>
                        </label>
                        <label class="flex items-start gap-3 cursor-pointer group">
                            <input type="checkbox"
                                   required
                                   class="mt-1 w-5 h-5 text-blue-600 border-gray-300 dark:border-gray-600 rounded focus:ring-2 focus:ring-blue-500 dark:bg-gray-700">
                            <span class="text-sm text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white transition">
                                ระบบจะส่งการแจ้งเตือนไปยังสมาชิกทุกฝ่ายที่เกี่ยวข้องโดยอัตโนมัติ
                            </span>
                        </label>
                    </div>
                </div>

                {{-- Submit Buttons --}}
                <div class="flex flex-col-reverse sm:flex-row gap-4">
                    <a href="{{ route('admin.team-transfer.show', $request) }}"
                       class="flex-1 sm:flex-initial px-8 py-4 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-lg text-center transition">
                        ยกเลิก
                    </a>
                    <button type="submit"
                            @click="isProcessing = true"
                            :disabled="isProcessing"
                            class="flex-1 px-8 py-4 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 disabled:from-gray-400 disabled:to-gray-500 text-white font-bold rounded-lg shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 disabled:transform-none transition-all duration-200 flex items-center justify-center">
                        <svg class="w-6 h-6 mr-2" x-show="!isProcessing" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <svg class="w-6 h-6 mr-2 animate-spin" x-show="isProcessing" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="isProcessing ? 'กำลังดำเนินการ...' : 'ยืนยันดำเนินการย้ายทีม'">ยืนยันดำเนินการย้ายทีม</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
