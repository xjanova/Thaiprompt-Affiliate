@extends('layouts.admin-v3')

@section('title', 'แก้ไขบัตร NFC')

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.nfc-cards.show', $nfcCard) }}"
           class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
            <i class="fas fa-arrow-left text-xl"></i>
        </a>
        <div class="flex-1">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <i class="fas fa-edit text-blue-600 dark:text-blue-400"></i>
                แก้ไขบัตร NFC
            </h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                แก้ไขข้อมูลบัตร: <span class="font-mono font-semibold">{{ $nfcCard->card_number }}</span>
            </p>
        </div>
    </div>

    {{-- Error Messages --}}
    @if($errors->any())
        <div class="bg-gradient-to-r from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20 border-l-4 border-red-500 text-red-700 dark:text-red-400 px-6 py-4 rounded-lg mb-6 shadow-md">
            <div class="flex items-start gap-3">
                <i class="fas fa-exclamation-circle text-xl mt-0.5"></i>
                <div class="flex-1">
                    <h3 class="font-semibold mb-2">พบข้อผิดพลาด:</h3>
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    {{-- Form Card --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
        <form action="{{ route('admin.nfc-cards.update', $nfcCard) }}" method="POST" x-data="{
            cardType: '{{ old('card_type', $nfcCard->card_type) }}',
            hasExpiry: {{ old('expires_at', $nfcCard->expires_at) ? 'true' : 'false' }},
            hasCreditLimit: {{ old('credit_limit', $nfcCard->credit_limit) > 0 ? 'true' : 'false' }}
        }">
            @csrf
            @method('PUT')

            <div class="p-6 space-y-6">
                {{-- Card Info Section (Read-only) --}}
                <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-info-circle text-blue-600"></i>
                        ข้อมูลบัตร
                    </h2>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {{-- Card Number (Read-only) --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-credit-card mr-1"></i>
                                เลขบัตร
                            </label>
                            <div class="px-4 py-3 bg-gray-100 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg font-mono text-gray-700 dark:text-gray-300">
                                {{ $nfcCard->card_number }}
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                <i class="fas fa-lock"></i>
                                เลขบัตรไม่สามารถแก้ไขได้
                            </p>
                        </div>

                        {{-- Card Name --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-tag mr-1"></i>
                                ชื่อบัตร
                            </label>
                            <input type="text"
                                   name="card_name"
                                   value="{{ old('card_name', $nfcCard->card_name) }}"
                                   placeholder="เช่น บัตรพนักงาน, บัตร VIP"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>
                </div>

                {{-- Card Type Section --}}
                <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-layer-group text-purple-600"></i>
                        ประเภทบัตร
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        {{-- Standard Card --}}
                        <label class="relative cursor-pointer">
                            <input type="radio"
                                   name="card_type"
                                   value="standard"
                                   x-model="cardType"
                                   {{ old('card_type', $nfcCard->card_type) === 'standard' ? 'checked' : '' }}
                                   class="peer sr-only">
                            <div class="border-2 border-gray-300 dark:border-gray-600 peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20 rounded-xl p-6 transition-all hover:shadow-lg">
                                <div class="text-center">
                                    <i class="fas fa-star text-4xl text-gray-400 peer-checked:text-blue-600 mb-3"></i>
                                    <h3 class="font-semibold text-gray-900 dark:text-white mb-1">มาตรฐาน</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">บัตรทั่วไป</p>
                                </div>
                            </div>
                        </label>

                        {{-- Premium Card --}}
                        <label class="relative cursor-pointer">
                            <input type="radio"
                                   name="card_type"
                                   value="premium"
                                   x-model="cardType"
                                   {{ old('card_type', $nfcCard->card_type) === 'premium' ? 'checked' : '' }}
                                   class="peer sr-only">
                            <div class="border-2 border-gray-300 dark:border-gray-600 peer-checked:border-purple-500 peer-checked:bg-purple-50 dark:peer-checked:bg-purple-900/20 rounded-xl p-6 transition-all hover:shadow-lg">
                                <div class="text-center">
                                    <i class="fas fa-gem text-4xl text-gray-400 peer-checked:text-purple-600 mb-3"></i>
                                    <h3 class="font-semibold text-gray-900 dark:text-white mb-1">พรีเมียม</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">สิทธิพิเศษเพิ่ม</p>
                                </div>
                            </div>
                        </label>

                        {{-- VIP Card --}}
                        <label class="relative cursor-pointer">
                            <input type="radio"
                                   name="card_type"
                                   value="vip"
                                   x-model="cardType"
                                   {{ old('card_type', $nfcCard->card_type) === 'vip' ? 'checked' : '' }}
                                   class="peer sr-only">
                            <div class="border-2 border-gray-300 dark:border-gray-600 peer-checked:border-yellow-500 peer-checked:bg-yellow-50 dark:peer-checked:bg-yellow-900/20 rounded-xl p-6 transition-all hover:shadow-lg">
                                <div class="text-center">
                                    <i class="fas fa-crown text-4xl text-gray-400 peer-checked:text-yellow-600 mb-3"></i>
                                    <h3 class="font-semibold text-gray-900 dark:text-white mb-1">วีไอพี</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">สิทธิสูงสุด</p>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Limits Section --}}
                <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-wallet text-green-600"></i>
                        วงเงิน
                    </h2>

                    <div class="grid grid-cols-1 lg:grid-cols-1 gap-6">
                        {{-- Credit Limit --}}
                        <div>
                            <label class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    <i class="fas fa-credit-card mr-1"></i>
                                    วงเงินเครดิต (บาท)
                                </span>
                                <label class="flex items-center gap-2">
                                    <input type="checkbox"
                                           x-model="hasCreditLimit"
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="text-xs text-gray-600 dark:text-gray-400">เปิดใช้งาน</span>
                                </label>
                            </label>
                            <input type="number"
                                   name="credit_limit"
                                   value="{{ old('credit_limit', $nfcCard->credit_limit) }}"
                                   min="0"
                                   step="0.01"
                                   x-bind:disabled="!hasCreditLimit"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white disabled:bg-gray-100 dark:disabled:bg-gray-900 disabled:cursor-not-allowed">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                <i class="fas fa-info-circle"></i>
                                วงเงินสูงสุดที่สามารถใช้เกินยอดคงเหลือ
                            </p>
                        </div>
                    </div>

                    {{-- Current Balance Info --}}
                    <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                        <div class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <i class="fas fa-info-circle text-blue-600"></i>
                            <span>ยอดคงเหลือปัจจุบัน:</span>
                            <span class="font-bold text-blue-600 dark:text-blue-400">฿{{ number_format($nfcCard->balance, 2) }}</span>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 ml-6">
                            หากต้องการเติมเงิน กรุณาใช้ฟังก์ชัน "เติมเงิน" แทน
                        </p>
                    </div>
                </div>

                {{-- Expiry Date Section --}}
                <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-calendar-alt text-orange-600"></i>
                        วันหมดอายุ
                    </h2>

                    <div>
                        <label class="flex items-center gap-2 mb-3">
                            <input type="checkbox"
                                   x-model="hasExpiry"
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">กำหนดวันหมดอายุ</span>
                        </label>

                        <div x-show="hasExpiry" x-transition class="max-w-md">
                            <input type="date"
                                   name="expires_at"
                                   value="{{ old('expires_at', $nfcCard->expires_at ? $nfcCard->expires_at->format('Y-m-d') : '') }}"
                                   min="{{ date('Y-m-d') }}"
                                   x-bind:required="hasExpiry"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                <i class="fas fa-info-circle"></i>
                                บัตรจะหมดอายุและไม่สามารถใช้งานได้หลังวันที่กำหนด
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Notes Section --}}
                <div>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-sticky-note text-yellow-600"></i>
                        หมายเหตุ
                    </h2>

                    <textarea name="notes"
                              rows="4"
                              placeholder="บันทึกข้อมูลเพิ่มเติมเกี่ยวกับบัตรนี้..."
                              class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white resize-none">{{ old('notes', $nfcCard->notes) }}</textarea>
                </div>
            </div>

            {{-- Form Actions --}}
            <div class="bg-gray-50 dark:bg-gray-700/50 px-6 py-4 rounded-b-xl flex justify-between items-center gap-4">
                <a href="{{ route('admin.nfc-cards.show', $nfcCard) }}"
                   class="px-6 py-3 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg font-semibold hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors flex items-center gap-2">
                    <i class="fas fa-times"></i>
                    ยกเลิก
                </a>

                <button type="submit"
                        class="px-8 py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white rounded-lg font-semibold shadow-lg transition-all flex items-center gap-2">
                    <i class="fas fa-save"></i>
                    บันทึกการแก้ไข
                </button>
            </div>
        </form>
    </div>

    {{-- Card Status Info --}}
    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Card Status --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <i class="fas fa-info-circle text-blue-600"></i>
                สถานะบัตร
            </h3>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">สถานะ:</span>
                    <span class="font-semibold">
                        @if($nfcCard->status === 'active')
                            <span class="text-green-600 dark:text-green-400">
                                <i class="fas fa-check-circle"></i> ใช้งานอยู่
                            </span>
                        @elseif($nfcCard->status === 'blocked')
                            <span class="text-red-600 dark:text-red-400">
                                <i class="fas fa-ban"></i> บล็อก
                            </span>
                        @else
                            <span class="text-gray-600 dark:text-gray-400">
                                <i class="fas fa-times-circle"></i> ไม่ใช้งาน
                            </span>
                        @endif
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">การจับคู่:</span>
                    <span class="font-semibold">
                        @if($nfcCard->is_paired)
                            <span class="text-blue-600 dark:text-blue-400">
                                <i class="fas fa-link"></i> จับคู่แล้ว
                            </span>
                        @else
                            <span class="text-gray-600 dark:text-gray-400">
                                <i class="fas fa-unlink"></i> ยังไม่ได้จับคู่
                            </span>
                        @endif
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">สร้างเมื่อ:</span>
                    <span class="font-semibold text-gray-900 dark:text-white">
                        {{ $nfcCard->created_at->format('d/m/Y H:i') }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <i class="fas fa-bolt text-yellow-600"></i>
                การจัดการด่วน
            </h3>
            <div class="space-y-2">
                <a href="{{ route('admin.nfc-cards.topup-form', $nfcCard) }}"
                   class="block w-full px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors text-center">
                    <i class="fas fa-money-bill-wave mr-2"></i>
                    เติมเงิน
                </a>
                @if(!$nfcCard->is_paired)
                    <a href="{{ route('admin.nfc-cards.pair-form', $nfcCard) }}"
                       class="block w-full px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-medium transition-colors text-center">
                        <i class="fas fa-link mr-2"></i>
                        จับคู่กับผู้ใช้
                    </a>
                @endif
                <a href="{{ route('admin.nfc-cards.show', $nfcCard) }}"
                   class="block w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors text-center">
                    <i class="fas fa-eye mr-2"></i>
                    ดูรายละเอียดเต็ม
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
