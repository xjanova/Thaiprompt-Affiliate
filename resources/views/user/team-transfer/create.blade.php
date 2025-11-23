@extends('layouts.user')

@section('title', 'สร้างคำขอย้ายทีม')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Back Button --}}
    <a href="{{ route('user.team-transfer.index') }}"
       class="inline-flex items-center text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white mb-6 transition">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        กลับไปรายการคำขอ
    </a>

    <div class="max-w-3xl mx-auto">
        {{-- Header --}}
        <div class="text-center mb-8">
            <div class="w-20 h-20 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                สร้างคำขอย้ายทีม
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                กรอกข้อมูลเพื่อขอย้ายไปยังแม่ทีมใหม่
            </p>
        </div>

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

        {{-- Current Sponsor Info --}}
        <div class="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 rounded-xl p-6 mb-8 border border-blue-200 dark:border-blue-800">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                ข้อมูลแม่ทีมปัจจุบัน
            </h3>
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">ชื่อแม่ทีม</p>
                    <p class="font-semibold text-gray-900 dark:text-white">
                        {{ $currentSponsor->user->name ?? 'N/A' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">รหัสสมาชิก</p>
                    <p class="font-semibold text-gray-900 dark:text-white">
                        {{ $currentSponsor->member_code ?? 'N/A' }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Transfer Form --}}
        <form method="POST" action="{{ route('user.team-transfer.store') }}" x-data="transferForm()">
            @csrf

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 md:p-8 border border-gray-200 dark:border-gray-700 space-y-6">
                {{-- New Sponsor Code --}}
                <div>
                    <label for="new_sponsor_code" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        รหัสแม่ทีมใหม่ <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="text"
                               id="new_sponsor_code"
                               name="new_sponsor_code"
                               value="{{ old('new_sponsor_code') }}"
                               required
                               maxlength="50"
                               class="w-full px-4 py-3 pl-12 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:text-white transition"
                               placeholder="กรอกรหัสสมาชิกของแม่ทีมใหม่"
                               x-model="sponsorCode"
                               @input="validateSponsorCode()">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        💡 กรอกรหัสสมาชิกของแม่ทีมที่คุณต้องการย้ายไป
                    </p>
                </div>

                {{-- Reason --}}
                <div>
                    <label for="reason" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        เหตุผลในการย้าย
                    </label>
                    <textarea id="reason"
                              name="reason"
                              rows="4"
                              maxlength="500"
                              class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:text-white transition resize-none"
                              placeholder="อธิบายเหตุผลที่ต้องการย้ายทีม (ไม่บังคับ)"
                              x-model="reason"
                              @input="updateCharCount()">{{ old('reason') }}</textarea>
                    <div class="flex justify-between items-center mt-2">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            📝 ระบุเหตุผลจะช่วยให้แม่ทีมเดิมพิจารณาอนุมัติได้ง่ายขึ้น
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400" x-text="`${charCount}/500`"></p>
                    </div>
                </div>

                {{-- Notes --}}
                <div>
                    <label for="notes" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        หมายเหตุเพิ่มเติม
                    </label>
                    <textarea id="notes"
                              name="notes"
                              rows="3"
                              maxlength="500"
                              class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:text-white transition resize-none"
                              placeholder="ข้อมูลเพิ่มเติม (ไม่บังคับ)">{{ old('notes') }}</textarea>
                </div>

                {{-- Transfer Fee Info --}}
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div class="flex-1">
                            <h4 class="text-sm font-semibold text-yellow-800 dark:text-yellow-300 mb-2">
                                💰 ค่าธรรมเนียมการย้ายทีม
                            </h4>
                            <p class="text-sm text-yellow-700 dark:text-yellow-400 mb-2">
                                ค่าธรรมเนียม: <span class="font-bold">{{ number_format($transferFee, 2) }} บาท</span>
                            </p>
                            <ul class="text-sm text-yellow-700 dark:text-yellow-400 space-y-1 ml-4 list-disc">
                                <li>คุณต้องชำระค่าธรรมเนียมหลังจากแม่ทีมเดิมอนุมัติคำขอ</li>
                                <li>ยอดเงินจะถูกหักจาก Wallet ของคุณ</li>
                                <li>หากยกเลิกคำขอจะได้รับเงินคืนทันที</li>
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Workflow Info --}}
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <h4 class="text-sm font-semibold text-blue-800 dark:text-blue-300 mb-3 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        ขั้นตอนการย้ายทีม
                    </h4>
                    <ol class="text-sm text-blue-700 dark:text-blue-400 space-y-2">
                        <li class="flex items-start">
                            <span class="flex-shrink-0 w-6 h-6 bg-blue-600 dark:bg-blue-500 text-white rounded-full flex items-center justify-center text-xs mr-3 mt-0.5">1</span>
                            <span>ส่งคำขอย้ายทีม</span>
                        </li>
                        <li class="flex items-start">
                            <span class="flex-shrink-0 w-6 h-6 bg-blue-600 dark:bg-blue-500 text-white rounded-full flex items-center justify-center text-xs mr-3 mt-0.5">2</span>
                            <span>รอแม่ทีมเดิมอนุมัติ (หรือปฏิเสธ)</span>
                        </li>
                        <li class="flex items-start">
                            <span class="flex-shrink-0 w-6 h-6 bg-blue-600 dark:bg-blue-500 text-white rounded-full flex items-center justify-center text-xs mr-3 mt-0.5">3</span>
                            <span>ชำระค่าธรรมเนียม {{ number_format($transferFee, 2) }} บาท</span>
                        </li>
                        <li class="flex items-start">
                            <span class="flex-shrink-0 w-6 h-6 bg-blue-600 dark:bg-blue-500 text-white rounded-full flex items-center justify-center text-xs mr-3 mt-0.5">4</span>
                            <span>Admin ดำเนินการย้ายทีม</span>
                        </li>
                        <li class="flex items-start">
                            <span class="flex-shrink-0 w-6 h-6 bg-blue-600 dark:bg-blue-500 text-white rounded-full flex items-center justify-center text-xs mr-3 mt-0.5">5</span>
                            <span>เสร็จสิ้น - คุณได้ย้ายไปยังแม่ทีมใหม่</span>
                        </li>
                    </ol>
                </div>

                {{-- Submit Buttons --}}
                <div class="flex flex-col-reverse sm:flex-row gap-3 pt-4">
                    <a href="{{ route('user.team-transfer.index') }}"
                       class="flex-1 sm:flex-initial px-6 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-lg text-center transition">
                        ยกเลิก
                    </a>
                    <button type="submit"
                            class="flex-1 px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 dark:from-blue-500 dark:to-blue-600 dark:hover:from-blue-600 dark:hover:to-blue-700 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-center"
                            :disabled="!isValid"
                            :class="{ 'opacity-50 cursor-not-allowed': !isValid }">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        ส่งคำขอย้ายทีม
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function transferForm() {
    return {
        sponsorCode: '{{ old('new_sponsor_code') }}',
        reason: '{{ old('reason') }}',
        charCount: {{ strlen(old('reason', '')) }},
        isValid: false,

        init() {
            this.validateSponsorCode();
        },

        validateSponsorCode() {
            this.isValid = this.sponsorCode.trim().length > 0;
        },

        updateCharCount() {
            this.charCount = this.reason.length;
        }
    }
}
</script>
@endpush
@endsection
