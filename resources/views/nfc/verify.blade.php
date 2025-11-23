@extends('layouts.app')

@section('title', 'ตรวจสอบบัตร NFC')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-cyan-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 py-12 px-4"
     x-data="nfcVerification()">
    <div class="max-w-2xl mx-auto">

        {{-- Header --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 mb-4 rounded-full bg-gradient-to-br from-blue-500 to-cyan-500 shadow-lg">
                <i class="fas fa-shield-check text-3xl text-white"></i>
            </div>
            <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">
                ตรวจสอบบัตร NFC
            </h1>
            <p class="text-lg text-gray-600 dark:text-gray-400">
                ตรวจสอบความถูกต้องและความปลอดภัยของบัตร NFC ของคุณ
            </p>
        </div>

        {{-- Verification Card --}}
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl overflow-hidden mb-6">

            {{-- Tab Navigation --}}
            <div class="flex border-b border-gray-200 dark:border-gray-700">
                <button type="button"
                        @click="activeTab = 'scan'"
                        :class="activeTab === 'scan' ? 'border-b-2 border-blue-500 text-blue-600 dark:text-blue-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                        class="flex-1 px-6 py-4 text-sm font-medium transition-colors">
                    <i class="fas fa-nfc-symbol mr-2"></i>
                    สแกนบัตร NFC
                </button>
                <button type="button"
                        @click="activeTab = 'manual'"
                        :class="activeTab === 'manual' ? 'border-b-2 border-blue-500 text-blue-600 dark:text-blue-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                        class="flex-1 px-6 py-4 text-sm font-medium transition-colors">
                    <i class="fas fa-keyboard mr-2"></i>
                    ใส่หมายเลขบัตร
                </button>
            </div>

            <div class="p-8">

                {{-- Scan Tab --}}
                <div x-show="activeTab === 'scan'" x-transition>

                    {{-- NFC Support Check --}}
                    <div x-show="!nfcSupported" class="mb-6">
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-2xl p-6">
                            <div class="flex items-start">
                                <i class="fas fa-exclamation-triangle text-yellow-500 text-xl mt-1 mr-3"></i>
                                <div>
                                    <h3 class="text-lg font-semibold text-yellow-800 dark:text-yellow-300 mb-2">
                                        อุปกรณ์ไม่รองรับ NFC
                                    </h3>
                                    <p class="text-sm text-yellow-700 dark:text-yellow-400 mb-3">
                                        เบราว์เซอร์หรืออุปกรณ์ของคุณไม่รองรับ Web NFC API
                                    </p>
                                    <div class="text-sm text-yellow-600 dark:text-yellow-500 space-y-1">
                                        <p><strong>ข้อกำหนด:</strong></p>
                                        <ul class="list-disc list-inside ml-4 space-y-1">
                                            <li>ใช้ Chrome for Android (เวอร์ชั่น 89 ขึ้นไป)</li>
                                            <li>เปิดใช้งาน NFC บนโทรศัพท์</li>
                                            <li>เข้าถึงผ่าน HTTPS</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Scan Button --}}
                    <div x-show="nfcSupported" class="text-center">
                        <button type="button"
                                @click="scanNFCCard()"
                                :disabled="scanning"
                                class="w-full px-8 py-6 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 disabled:from-gray-400 disabled:to-gray-500 text-white text-lg font-semibold rounded-2xl shadow-xl transition-all duration-300 transform hover:scale-105 disabled:scale-100 disabled:cursor-not-allowed">
                            <i class="fas" :class="scanning ? 'fa-spinner fa-spin' : 'fa-nfc-symbol'" x-text=""></i>
                            <span class="ml-3" x-text="scanning ? 'กำลังสแกน...' : 'สแกนบัตร NFC'"></span>
                        </button>

                        <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                            <i class="fas fa-info-circle mr-2"></i>
                            แตะบัตร NFC ที่ด้านหลังของโทรศัพท์
                        </p>
                    </div>

                    {{-- Scanning Animation --}}
                    <div x-show="scanning" x-transition class="mt-8">
                        <div class="flex flex-col items-center justify-center py-12">
                            <div class="relative">
                                <div class="w-32 h-32 rounded-full border-8 border-blue-200 dark:border-blue-900"></div>
                                <div class="absolute inset-0 w-32 h-32 rounded-full border-8 border-transparent border-t-blue-600 dark:border-t-blue-400 animate-spin"></div>
                                <i class="fas fa-nfc-symbol absolute inset-0 flex items-center justify-center text-4xl text-blue-600 dark:text-blue-400"></i>
                            </div>
                            <p class="mt-6 text-lg font-medium text-gray-700 dark:text-gray-300">
                                กรุณาแตะบัตร NFC...
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-500">
                                ระบบกำลังรอรับสัญญาณจากบัตร
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Manual Tab --}}
                <div x-show="activeTab === 'manual'" x-transition>
                    <form @submit.prevent="verifyManual()">
                        <div class="mb-6">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                                หมายเลขบัตร
                            </label>
                            <input type="text"
                                   x-model="manualCardNumber"
                                   placeholder="ระบุหมายเลขบัตร NFC"
                                   class="w-full px-4 py-4 text-lg border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-xl focus:border-blue-500 dark:focus:border-blue-400 focus:ring-4 focus:ring-blue-100 dark:focus:ring-blue-900/30 transition-all"
                                   required>
                        </div>

                        <button type="submit"
                                :disabled="verifying || !manualCardNumber"
                                class="w-full px-8 py-4 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 disabled:from-gray-400 disabled:to-gray-500 text-white text-lg font-semibold rounded-2xl shadow-xl transition-all duration-300 transform hover:scale-105 disabled:scale-100 disabled:cursor-not-allowed">
                            <i class="fas" :class="verifying ? 'fa-spinner fa-spin' : 'fa-search'"></i>
                            <span class="ml-3" x-text="verifying ? 'กำลังตรวจสอบ...' : 'ตรวจสอบบัตร'"></span>
                        </button>
                    </form>
                </div>

            </div>
        </div>

        {{-- Verification Result --}}
        <div x-show="verificationResult" x-transition class="mb-6">

            {{-- Success Result --}}
            <div x-show="verificationResult?.verified === true" class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-2 border-green-200 dark:border-green-800 rounded-3xl p-8 shadow-xl">
                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center w-20 h-20 mb-4 rounded-full bg-gradient-to-br from-green-500 to-emerald-500 shadow-lg animate-bounce">
                        <i class="fas fa-check text-3xl text-white"></i>
                    </div>
                    <h2 class="text-3xl font-bold text-green-800 dark:text-green-300 mb-2">
                        ✅ บัตรถูกต้อง
                    </h2>
                    <p class="text-lg text-green-700 dark:text-green-400">
                        บัตร NFC นี้เป็นของแท้และปลอดภัย
                    </p>
                </div>

                {{-- Card Details --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 space-y-4">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                        ข้อมูลบัตร
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">หมายเลขบัตร</p>
                            <p class="text-lg font-mono font-bold text-gray-900 dark:text-white" x-text="verificationResult?.card_number"></p>
                        </div>

                        <div x-show="verificationResult?.card?.card_type">
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">ประเภทบัตร</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white" x-text="verificationResult?.card?.card_type"></p>
                        </div>

                        <div x-show="verificationResult?.card?.issued_date">
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">วันที่ออกบัตร</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white" x-text="verificationResult?.card?.issued_date"></p>
                        </div>

                        <div x-show="verificationResult?.card?.expiry_date">
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">วันหมดอายุ</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white" x-text="verificationResult?.card?.expiry_date"></p>
                        </div>
                    </div>

                    {{-- Security Checks --}}
                    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                            ผลการตรวจสอบความปลอดภัย
                        </h4>
                        <div class="space-y-2">
                            <div class="flex items-center">
                                <i class="fas fa-check-circle text-green-500 mr-3"></i>
                                <span class="text-sm text-gray-700 dark:text-gray-300">Digital Signature ถูกต้อง</span>
                            </div>
                            <div x-show="verificationResult?.checks?.uid_match" class="flex items-center">
                                <i class="fas fa-check-circle text-green-500 mr-3"></i>
                                <span class="text-sm text-gray-700 dark:text-gray-300">UID ตรงกับข้อมูลในระบบ</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Failed Result --}}
            <div x-show="verificationResult?.verified === false" class="bg-gradient-to-br from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20 border-2 border-red-200 dark:border-red-800 rounded-3xl p-8 shadow-xl">
                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center w-20 h-20 mb-4 rounded-full bg-gradient-to-br from-red-500 to-rose-500 shadow-lg animate-pulse">
                        <i class="fas fa-times text-3xl text-white"></i>
                    </div>
                    <h2 class="text-3xl font-bold text-red-800 dark:text-red-300 mb-2">
                        ❌ บัตรไม่ถูกต้อง
                    </h2>
                    <p class="text-lg text-red-700 dark:text-red-400" x-text="verificationResult?.message"></p>
                </div>

                {{-- Security Checks --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6">
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                        ผลการตรวจสอบความปลอดภัย
                    </h4>
                    <div class="space-y-2">
                        <div class="flex items-center">
                            <i class="fas" :class="verificationResult?.checks?.signature_valid ? 'fa-check-circle text-green-500' : 'fa-times-circle text-red-500'" class="mr-3"></i>
                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                Digital Signature: <span x-text="verificationResult?.checks?.signature_valid ? 'ถูกต้อง' : 'ไม่ถูกต้อง'"></span>
                            </span>
                        </div>
                        <div x-show="verificationResult?.checks?.uid_match !== undefined" class="flex items-center">
                            <i class="fas" :class="verificationResult?.checks?.uid_match ? 'fa-check-circle text-green-500' : 'fa-times-circle text-red-500'" class="mr-3"></i>
                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                UID: <span x-text="verificationResult?.checks?.uid_match ? 'ตรงกับระบบ' : 'ไม่ตรงกับระบบ'"></span>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="mt-6 bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-800 rounded-xl p-4">
                    <p class="text-sm text-red-800 dark:text-red-300">
                        <i class="fas fa-shield-exclamation mr-2"></i>
                        <strong>คำเตือน:</strong> บัตรนี้อาจเป็นของปลอมหรือไม่ได้รับอนุญาต กรุณาติดต่อผู้ดูแลระบบ
                    </p>
                </div>
            </div>

            {{-- Verify Another Button --}}
            <div class="mt-6 text-center">
                <button type="button"
                        @click="resetVerification()"
                        class="px-6 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 font-semibold rounded-xl transition-colors">
                    <i class="fas fa-redo mr-2"></i>
                    ตรวจสอบบัตรอื่น
                </button>
            </div>
        </div>

        {{-- Info Section --}}
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-2xl p-6">
            <h3 class="text-lg font-bold text-blue-900 dark:text-blue-300 mb-4">
                <i class="fas fa-info-circle mr-2"></i>
                เกี่ยวกับระบบตรวจสอบ
            </h3>
            <div class="text-sm text-blue-800 dark:text-blue-400 space-y-3">
                <p>
                    <strong>ระบบตรวจสอบบัตร NFC</strong> ใช้เทคโนโลยีการเข้ารหัสขั้นสูงเพื่อยืนยันความถูกต้องของบัตร:
                </p>
                <ul class="list-disc list-inside ml-4 space-y-2">
                    <li><strong>SHA-256 Hashing</strong> - สร้างรหัสป้องกันปลอมที่ไม่สามารถย้อนกลับได้</li>
                    <li><strong>HMAC-SHA256 Signature</strong> - ลายเซ็นดิจิทัลที่ตรวจสอบความถูกต้อง</li>
                    <li><strong>UID Binding</strong> - ผูกบัตรกับ Serial Number ไม่สามารถคัดลอกได้</li>
                    <li><strong>Timestamp Validation</strong> - ตรวจสอบเวลาในการออกบัตร</li>
                </ul>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
/**
 * Alpine.js Component: NFC Verification
 *
 * ระบบตรวจสอบความถูกต้องของบัตร NFC แบบ Public
 */
function nfcVerification() {
    return {
        // State
        activeTab: 'scan',
        nfcSupported: false,
        scanning: false,
        verifying: false,
        manualCardNumber: @json($cardNumber ?? ''),
        verificationResult: null,

        /**
         * เริ่มต้น Component
         */
        init() {
            // ตรวจสอบว่ารองรับ Web NFC API หรือไม่
            this.nfcSupported = 'NDEFReader' in window;

            // ถ้ามี card number ให้เปิด manual tab
            if (this.manualCardNumber) {
                this.activeTab = 'manual';
            }
        },

        /**
         * สแกนบัตร NFC
         */
        async scanNFCCard() {
            if (!this.nfcSupported) {
                alert('อุปกรณ์ของคุณไม่รองรับ Web NFC API');
                return;
            }

            this.scanning = true;
            this.verificationResult = null;

            try {
                const ndef = new NDEFReader();

                // เริ่มสแกน
                await ndef.scan();

                console.log('✅ NFC scan started');

                // รอข้อมูลจากบัตร
                ndef.onreading = async (event) => {
                    console.log('📖 NFC card detected');

                    const { message, serialNumber } = event;
                    const uid = this.arrayBufferToHex(serialNumber);

                    // อ่านข้อมูลจาก NDEF records
                    const cardData = this.parseNDEFRecords(message.records);

                    console.log('Card Data:', cardData);
                    console.log('UID:', uid);

                    // ตรวจสอบความถูกต้องของบัตร
                    await this.verifyCard(cardData, uid);

                    this.scanning = false;
                };

                ndef.onreadingerror = () => {
                    console.error('❌ NFC reading error');
                    alert('เกิดข้อผิดพลาดในการอ่านบัตร NFC');
                    this.scanning = false;
                };

            } catch (error) {
                console.error('❌ NFC scan error:', error);
                let errorMessage = 'ไม่สามารถสแกนบัตร NFC ได้';

                if (error.name === 'NotAllowedError') {
                    errorMessage = 'กรุณาอนุญาตให้เข้าถึง NFC';
                } else if (error.name === 'NotSupportedError') {
                    errorMessage = 'อุปกรณ์ไม่รองรับ NFC';
                }

                alert(errorMessage);
                this.scanning = false;
            }
        },

        /**
         * แปลง ArrayBuffer เป็น Hex string
         */
        arrayBufferToHex(buffer) {
            return [...new Uint8Array(buffer)]
                .map(x => x.toString(16).padStart(2, '0'))
                .join('');
        },

        /**
         * แปลง NDEF Records เป็น object
         */
        parseNDEFRecords(records) {
            const data = {};

            for (const record of records) {
                const textDecoder = new TextDecoder(record.encoding || 'utf-8');
                const text = textDecoder.decode(record.data);

                if (text.startsWith('CARD:')) {
                    data.cardNumber = text.substring(5);
                } else if (text.startsWith('AUTH:')) {
                    data.antiCounterfeitCode = text.substring(5);
                } else if (text.startsWith('SIGN:')) {
                    data.signature = text.substring(5);
                } else if (text.startsWith('UID:')) {
                    data.uidFromCard = text.substring(4);
                }
            }

            return data;
        },

        /**
         * ตรวจสอบความถูกต้องของบัตร
         */
        async verifyCard(cardData, uid) {
            this.verifying = true;

            try {
                const response = await fetch('{{ route("nfc.verify.api") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        card_number: cardData.cardNumber,
                        anti_counterfeit_code: cardData.antiCounterfeitCode,
                        signature: cardData.signature,
                        uid: uid
                    })
                });

                const result = await response.json();
                this.verificationResult = result;

                console.log('Verification Result:', result);

            } catch (error) {
                console.error('❌ Verification error:', error);
                alert('เกิดข้อผิดพลาดในการตรวจสอบบัตร');
            } finally {
                this.verifying = false;
            }
        },

        /**
         * ตรวจสอบด้วยหมายเลขบัตร (Manual)
         */
        async verifyManual() {
            if (!this.manualCardNumber) {
                return;
            }

            this.verifying = true;
            this.verificationResult = null;

            try {
                // ถ้ามีบัตรในระบบให้ไปหน้า verify ของบัตรนั้น
                window.location.href = `{{ url('/nfc/verify') }}/${this.manualCardNumber}`;

            } catch (error) {
                console.error('❌ Manual verification error:', error);
                alert('เกิดข้อผิดพลาดในการตรวจสอบบัตร');
                this.verifying = false;
            }
        },

        /**
         * รีเซ็ตการตรวจสอบ
         */
        resetVerification() {
            this.verificationResult = null;
            this.manualCardNumber = '';
            this.activeTab = 'scan';
        }
    };
}
</script>
@endpush
@endsection
