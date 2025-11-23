@extends('layouts.admin-v3')

@section('title', 'ออกบัตร NFC ใหม่')

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.nfc-cards.index') }}"
           class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
            <i class="fas fa-arrow-left text-xl"></i>
        </a>
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <i class="fas fa-credit-card text-blue-600 dark:text-blue-400"></i>
                ออกบัตร NFC ใหม่
            </h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                กรอกข้อมูลเพื่อออกบัตร NFC ใหม่สำหรับระบบ
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
        <form action="{{ route('admin.nfc-cards.store') }}" method="POST"
              x-data="nfcCardForm()"
              x-init="init()">
            @csrf

            <div class="p-6 space-y-6">
                {{-- Card Number Section --}}
                <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-hashtag text-blue-600"></i>
                        ข้อมูลบัตร
                    </h2>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {{-- Card Number --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-credit-card mr-1"></i>
                                เลขบัตร <span class="text-red-500">*</span>
                            </label>
                            <div class="space-y-2">
                                <input type="text"
                                       name="card_number"
                                       x-ref="cardNumber"
                                       value="{{ old('card_number') }}"
                                       :disabled="autoGenerateNumber"
                                       required
                                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white disabled:bg-gray-100 dark:disabled:bg-gray-900 disabled:cursor-not-allowed font-mono">
                                <label class="flex items-center gap-2">
                                    <input type="checkbox"
                                           x-model="autoGenerateNumber"
                                           @change="generateCardNumber"
                                           checked
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">สร้างเลขบัตรอัตโนมัติ</span>
                                </label>
                            </div>
                        </div>

                        {{-- Card Name --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-tag mr-1"></i>
                                ชื่อบัตร (ไม่บังคับ)
                            </label>
                            <input type="text"
                                   name="card_name"
                                   value="{{ old('card_name') }}"
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
                                   checked
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

                {{-- Balance & Limits Section --}}
                <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-wallet text-green-600"></i>
                        ยอดเงินและวงเงิน
                    </h2>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {{-- Initial Balance --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <i class="fas fa-coins mr-1"></i>
                                ยอดเงินเริ่มต้น (บาท)
                            </label>
                            <input type="number"
                                   name="initial_balance"
                                   value="{{ old('initial_balance', 0) }}"
                                   min="0"
                                   step="0.01"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                <i class="fas fa-info-circle"></i>
                                จำนวนเงินที่จะเติมให้บัตรเมื่อออกบัตร
                            </p>
                        </div>

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
                                   value="{{ old('credit_limit', 0) }}"
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
                                   value="{{ old('expires_at') }}"
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

                {{-- NFC Read/Write Section --}}
                <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-wifi text-indigo-600"></i>
                        อ่านและเขียนบัตร NFC
                    </h2>

                    {{-- NFC Capability Check --}}
                    <div x-show="!nfcSupported" class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-500 p-4 rounded-lg mb-4">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-400 text-xl mt-0.5"></i>
                            <div>
                                <h3 class="font-semibold text-yellow-800 dark:text-yellow-300 mb-1">อุปกรณ์ไม่รองรับ NFC</h3>
                                <p class="text-sm text-yellow-700 dark:text-yellow-400">
                                    เบราว์เซอร์ของคุณไม่รองรับ Web NFC API โปรดใช้ Chrome บน Android (เวอร์ชัน 89+) และเปิดใช้งานผ่าน HTTPS
                                </p>
                            </div>
                        </div>
                    </div>

                    <div x-show="nfcSupported" class="space-y-4">
                        {{-- Read NFC Card Button --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <button type="button"
                                        @click="readNFCCard()"
                                        :disabled="nfcReading || nfcWriting"
                                        class="w-full px-6 py-4 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 disabled:from-gray-400 disabled:to-gray-500 text-white rounded-xl font-semibold shadow-lg transition-all flex items-center justify-center gap-3 disabled:cursor-not-allowed">
                                    <i class="fas" :class="nfcReading ? 'fa-spinner fa-spin' : 'fa-book-reader'"></i>
                                    <span x-text="nfcReading ? 'กำลังอ่านบัตร...' : 'อ่านบัตร NFC'"></span>
                                </button>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 text-center">
                                    <i class="fas fa-info-circle"></i>
                                    แตะบัตร NFC ที่เครื่องอ่าน
                                </p>
                            </div>

                            <div>
                                <button type="button"
                                        @click="writeNFCCard()"
                                        :disabled="!cardDataReady || nfcReading || nfcWriting"
                                        class="w-full px-6 py-4 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 disabled:from-gray-400 disabled:to-gray-500 text-white rounded-xl font-semibold shadow-lg transition-all flex items-center justify-center gap-3 disabled:cursor-not-allowed">
                                    <i class="fas" :class="nfcWriting ? 'fa-spinner fa-spin' : 'fa-pen'"></i>
                                    <span x-text="nfcWriting ? 'กำลังเขียนข้อมูล...' : 'เขียนข้อมูลลงบัตร'"></span>
                                </button>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 text-center">
                                    <i class="fas fa-info-circle"></i>
                                    เขียนข้อมูลและรหัสป้องกันปลอม
                                </p>
                            </div>
                        </div>

                        {{-- NFC Read Result --}}
                        <div x-show="nfcReadData" x-transition class="bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 rounded-xl p-6 border border-blue-200 dark:border-blue-800">
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                <i class="fas fa-check-circle text-green-600"></i>
                                ข้อมูลที่อ่านได้จากบัตร
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Serial Number (UID)</p>
                                    <p class="text-sm font-mono font-semibold text-gray-900 dark:text-white bg-white dark:bg-gray-800 px-3 py-2 rounded-lg" x-text="nfcReadData?.uid || '-'"></p>
                                </div>

                                <div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Card Type</p>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white bg-white dark:bg-gray-800 px-3 py-2 rounded-lg" x-text="nfcReadData?.cardType || '-'"></p>
                                </div>

                                <div class="md:col-span-2" x-show="nfcReadData?.records && nfcReadData.records.length > 0">
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">NDEF Records</p>
                                    <div class="space-y-2">
                                        <template x-for="(record, index) in nfcReadData?.records" :key="index">
                                            <div class="bg-white dark:bg-gray-800 px-3 py-2 rounded-lg">
                                                <div class="flex items-center justify-between">
                                                    <span class="text-xs font-semibold text-gray-600 dark:text-gray-400" x-text="record.type"></span>
                                                    <span class="text-sm text-gray-900 dark:text-white" x-text="record.data"></span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- NFC Write Success --}}
                        <div x-show="nfcWriteSuccess" x-transition class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl p-6 border border-green-200 dark:border-green-800">
                            <div class="flex items-start gap-3">
                                <i class="fas fa-check-circle text-green-600 dark:text-green-400 text-2xl mt-1"></i>
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-900 dark:text-white mb-2">เขียนข้อมูลลงบัตรสำเร็จ!</h3>
                                    <div class="space-y-2 text-sm text-gray-700 dark:text-gray-300">
                                        <p>✓ เขียนหมายเลขบัตร: <span class="font-mono font-semibold" x-text="nfcWriteData?.cardNumber"></span></p>
                                        <p>✓ เขียนรหัสป้องกันปลอม: <span class="font-mono text-xs" x-text="nfcWriteData?.antiCounterfeitCode?.substring(0, 32) + '...'"></span></p>
                                        <p>✓ เขียน Signature: <span class="text-green-600 dark:text-green-400 font-semibold">Success</span></p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                            <i class="fas fa-shield-alt mr-1"></i>
                                            บัตรถูกป้องกันด้วยระบบเข้ารหัสและลายเซ็นดิจิทัล
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- NFC Error --}}
                        <div x-show="nfcError" x-transition class="bg-gradient-to-r from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20 rounded-xl p-6 border border-red-200 dark:border-red-800">
                            <div class="flex items-start gap-3">
                                <i class="fas fa-exclamation-circle text-red-600 dark:text-red-400 text-2xl mt-1"></i>
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-900 dark:text-white mb-2">เกิดข้อผิดพลาด</h3>
                                    <p class="text-sm text-gray-700 dark:text-gray-300" x-text="nfcErrorMessage"></p>
                                </div>
                                <button type="button" @click="nfcError = false; nfcErrorMessage = ''" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
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
                              class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white resize-none">{{ old('notes') }}</textarea>
                </div>
            </div>

            {{-- Form Actions --}}
            <div class="bg-gray-50 dark:bg-gray-700/50 px-6 py-4 rounded-b-xl flex justify-between items-center gap-4">
                <a href="{{ route('admin.nfc-cards.index') }}"
                   class="px-6 py-3 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg font-semibold hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors flex items-center gap-2">
                    <i class="fas fa-times"></i>
                    ยกเลิก
                </a>

                <div class="flex gap-2">
                    <button type="reset"
                            class="px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white rounded-lg font-semibold transition-colors flex items-center gap-2">
                        <i class="fas fa-redo"></i>
                        รีเซ็ต
                    </button>
                    <button type="submit"
                            class="px-8 py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white rounded-lg font-semibold shadow-lg transition-all flex items-center gap-2">
                        <i class="fas fa-check"></i>
                        ออกบัตร NFC
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Help Card --}}
    <div class="mt-6 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl p-6 border border-blue-200 dark:border-blue-800">
        <h3 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
            <i class="fas fa-lightbulb text-yellow-500"></i>
            คำแนะนำ
        </h3>
        <ul class="space-y-2 text-sm text-gray-700 dark:text-gray-300">
            <li class="flex items-start gap-2">
                <i class="fas fa-check text-green-600 mt-0.5"></i>
                <span><strong>เลขบัตร:</strong> จะถูกสร้างอัตโนมัติ หรือสามารถกรอกเองได้</span>
            </li>
            <li class="flex items-start gap-2">
                <i class="fas fa-check text-green-600 mt-0.5"></i>
                <span><strong>ประเภทบัตร:</strong> มาตรฐาน, พรีเมียม, หรือวีไอพี ขึ้นอยู่กับสิทธิที่ต้องการให้</span>
            </li>
            <li class="flex items-start gap-2">
                <i class="fas fa-check text-green-600 mt-0.5"></i>
                <span><strong>ยอดเงินเริ่มต้น:</strong> จำนวนเงินที่จะมีในบัตรทันทีหลังออกบัตร</span>
            </li>
            <li class="flex items-start gap-2">
                <i class="fas fa-check text-green-600 mt-0.5"></i>
                <span><strong>วงเงินเครดิต:</strong> ระบุถ้าต้องการให้ใช้เงินเกินยอดคงเหลือได้</span>
            </li>
            <li class="flex items-start gap-2">
                <i class="fas fa-check text-green-600 mt-0.5"></i>
                <span><strong>วันหมดอายุ:</strong> บัตรจะใช้งานไม่ได้หลังจากวันที่กำหนด</span>
            </li>
        </ul>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* เอฟเฟกต์แอนิเมชันสำหรับ radio card selection */
    input[type="radio"]:checked + div {
        animation: pulse-once 0.3s ease-in-out;
    }

    @keyframes pulse-once {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.02); }
    }
</style>
@endpush

@push('scripts')
<script>
    /**
     * NFC Card Form - Alpine.js Component
     *
     * จัดการฟอร์มออกบัตร NFC พร้อมระบบอ่าน-เขียนบัตรจริง
     * รองรับ Web NFC API (Chrome Android 89+)
     */
    function nfcCardForm() {
        return {
            // ข้อมูลฟอร์ม
            cardType: 'standard',
            autoGenerateNumber: true,
            hasExpiry: false,
            hasCreditLimit: false,

            // NFC State
            nfcSupported: false,
            nfcReading: false,
            nfcWriting: false,
            nfcReadData: null,
            nfcWriteData: null,
            nfcWriteSuccess: false,
            nfcError: false,
            nfcErrorMessage: '',
            cardDataReady: false,

            /**
             * เริ่มต้น Component
             */
            init() {
                // สร้างเลขบัตรอัตโนมัติ
                this.generateCardNumber();

                // ตรวจสอบการรองรับ NFC
                this.checkNFCSupport();
            },

            /**
             * สร้างเลขบัตรอัตโนมัติ
             */
            generateCardNumber() {
                if (this.autoGenerateNumber && this.$refs.cardNumber) {
                    const timestamp = Date.now();
                    const random = Math.floor(Math.random() * 10000).toString().padStart(4, '0');
                    this.$refs.cardNumber.value = `NFC${timestamp}${random}`;
                    this.cardDataReady = true;
                }
            },

            /**
             * ตรวจสอบการรองรับ Web NFC API
             */
            checkNFCSupport() {
                if ('NDEFReader' in window) {
                    this.nfcSupported = true;
                    console.log('✓ Web NFC API is supported');
                } else {
                    this.nfcSupported = false;
                    console.warn('✗ Web NFC API is not supported');
                    console.info('Please use Chrome on Android (v89+) with HTTPS');
                }
            },

            /**
             * อ่านบัตร NFC
             */
            async readNFCCard() {
                if (!this.nfcSupported) {
                    this.showError('เบราว์เซอร์ไม่รองรับ Web NFC API');
                    return;
                }

                this.nfcReading = true;
                this.nfcError = false;
                this.nfcReadData = null;

                try {
                    const ndef = new NDEFReader();

                    // ขออนุญาตและเริ่มสแกน
                    await ndef.scan();

                    console.log('📱 กำลังรอการแตะบัตร NFC...');

                    // ฟังก์ชันอ่านข้อมูล
                    ndef.onreading = async (event) => {
                        console.log('✓ อ่านบัตร NFC สำเร็จ');

                        const { message, serialNumber } = event;

                        // แปลง Serial Number เป็น Hex
                        const uid = this.arrayBufferToHex(serialNumber);

                        // อ่าน NDEF Records
                        const records = [];
                        for (const record of message.records) {
                            const textDecoder = new TextDecoder(record.encoding || 'utf-8');
                            const data = textDecoder.decode(record.data);

                            records.push({
                                type: this.getRecordTypeName(record.recordType),
                                data: data,
                                id: record.id || ''
                            });
                        }

                        // เก็บข้อมูล
                        this.nfcReadData = {
                            uid: uid,
                            cardType: 'NDEF',
                            records: records,
                            timestamp: new Date().toISOString()
                        };

                        this.nfcReading = false;

                        console.log('📋 ข้อมูลที่อ่านได้:', this.nfcReadData);
                    };

                    // จับข้อผิดพลาด
                    ndef.onreadingerror = (error) => {
                        console.error('✗ อ่านบัตรล้มเหลว:', error);
                        this.showError('ไม่สามารถอ่านบัตรได้: ' + error.message);
                        this.nfcReading = false;
                    };

                    // Auto timeout หลัง 30 วินาที
                    setTimeout(() => {
                        if (this.nfcReading) {
                            this.nfcReading = false;
                            this.showError('หมดเวลารอการแตะบัตร (30 วินาที)');
                        }
                    }, 30000);

                } catch (error) {
                    console.error('✗ เกิดข้อผิดพลาด:', error);
                    this.showError(this.getNFCErrorMessage(error));
                    this.nfcReading = false;
                }
            },

            /**
             * เขียนข้อมูลลงบัตร NFC
             */
            async writeNFCCard() {
                if (!this.nfcSupported) {
                    this.showError('เบราว์เซอร์ไม่รองรับ Web NFC API');
                    return;
                }

                if (!this.$refs.cardNumber || !this.$refs.cardNumber.value) {
                    this.showError('กรุณากรอกเลขบัตรก่อน');
                    return;
                }

                this.nfcWriting = true;
                this.nfcError = false;
                this.nfcWriteSuccess = false;

                try {
                    const cardNumber = this.$refs.cardNumber.value;

                    // ขอรหัสป้องกันปลอมจาก Backend
                    console.log('🔐 กำลังสร้างรหัสป้องกันปลอม...');
                    const antiCounterfeitData = await this.generateAntiCounterfeitCode(cardNumber);

                    if (!antiCounterfeitData.success) {
                        throw new Error(antiCounterfeitData.message || 'ไม่สามารถสร้างรหัสป้องกันปลอมได้');
                    }

                    // สร้าง NDEF Message
                    const records = [
                        {
                            recordType: "text",
                            data: `CARD:${cardNumber}`
                        },
                        {
                            recordType: "text",
                            data: `AUTH:${antiCounterfeitData.code}`
                        },
                        {
                            recordType: "text",
                            data: `SIGN:${antiCounterfeitData.signature}`
                        },
                        {
                            recordType: "text",
                            data: `UID:${this.nfcReadData?.uid || 'N/A'}`
                        },
                        {
                            recordType: "text",
                            data: `ISSUED:${new Date().toISOString()}`
                        },
                        {
                            recordType: "url",
                            data: `{{ url('/') }}/nfc/verify/${cardNumber}`
                        }
                    ];

                    // เขียนลงบัตร
                    const ndef = new NDEFReader();
                    await ndef.write({ records });

                    console.log('✓ เขียนข้อมูลลงบัตรสำเร็จ');

                    // เก็บข้อมูล
                    this.nfcWriteData = {
                        cardNumber: cardNumber,
                        antiCounterfeitCode: antiCounterfeitData.code,
                        signature: antiCounterfeitData.signature,
                        timestamp: new Date().toISOString()
                    };

                    this.nfcWriteSuccess = true;
                    this.nfcWriting = false;

                    // แจ้งเตือนสำเร็จ
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'success',
                            title: 'เขียนข้อมูลสำเร็จ!',
                            text: 'บัตรพร้อมใช้งานและถูกป้องกันด้วยระบบเข้ารหัส',
                            timer: 3000,
                            timerProgressBar: true
                        });
                    }

                } catch (error) {
                    console.error('✗ เขียนบัตรล้มเหลว:', error);
                    this.showError(this.getNFCErrorMessage(error));
                    this.nfcWriting = false;
                }
            },

            /**
             * สร้างรหัสป้องกันปลอมจาก Backend
             */
            async generateAntiCounterfeitCode(cardNumber) {
                try {
                    const response = await fetch('{{ route("admin.nfc-cards.generate-anti-counterfeit") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            card_number: cardNumber,
                            uid: this.nfcReadData?.uid || null
                        })
                    });

                    return await response.json();
                } catch (error) {
                    console.error('Error generating anti-counterfeit code:', error);
                    return {
                        success: false,
                        message: error.message
                    };
                }
            },

            /**
             * แปลง ArrayBuffer เป็น Hex String
             */
            arrayBufferToHex(buffer) {
                return [...new Uint8Array(buffer)]
                    .map(x => x.toString(16).padStart(2, '0'))
                    .join(':')
                    .toUpperCase();
            },

            /**
             * รับชื่อประเภท Record
             */
            getRecordTypeName(type) {
                const types = {
                    'text': 'Text',
                    'url': 'URL',
                    'mime': 'MIME',
                    'absolute-url': 'Absolute URL',
                    'empty': 'Empty',
                    'smart-poster': 'Smart Poster',
                    'external': 'External'
                };
                return types[type] || type;
            },

            /**
             * รับข้อความ Error ที่อ่านง่าย
             */
            getNFCErrorMessage(error) {
                const errorMessages = {
                    'NotAllowedError': 'ไม่ได้รับอนุญาตให้เข้าถึง NFC กรุณาอนุญาตในเบราว์เซอร์',
                    'NotSupportedError': 'อุปกรณ์ไม่รองรับ NFC',
                    'NotReadableError': 'ไม่สามารถอ่านบัตรได้ กรุณาลองใหม่',
                    'InvalidStateError': 'สถานะ NFC ไม่ถูกต้อง',
                    'NetworkError': 'เกิดข้อผิดพลาดในการเชื่อมต่อ',
                    'AbortError': 'การอ่านถูกยกเลิก',
                    'TimeoutError': 'หมดเวลารอ'
                };

                return errorMessages[error.name] || error.message || 'เกิดข้อผิดพลาดที่ไม่ทราบสาเหตุ';
            },

            /**
             * แสดงข้อความ Error
             */
            showError(message) {
                this.nfcError = true;
                this.nfcErrorMessage = message;

                // Auto hide หลัง 5 วินาที
                setTimeout(() => {
                    this.nfcError = false;
                }, 5000);
            }
        };
    }
</script>
@endpush
