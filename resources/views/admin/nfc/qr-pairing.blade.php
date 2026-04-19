{{--
    NFC Card QR Pairing Admin Panel

    หน้าจัดการการจับคู่บัตร NFC ผ่าน QR Code
    - แสดงรายการบัตรที่ยังไม่ได้จับคู่
    - สร้าง QR Code สำหรับจับคู่
    - แสดง QR Code ที่สามารถสแกนได้
    - นับถอยหลังเวลาหมดอายุของโทเค็น (15 นาที)

    @version 1.0.0
    @since 2025-02-22
--}}

@extends('layouts.admin-v3')

@section('title', 'NFC QR Card Pairing')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="nfcQRPairing()">
    {{-- Header --}}
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 dark:from-purple-400 dark:to-pink-400 bg-clip-text text-transparent">
                    <i class="fas fa-qrcode mr-3"></i>NFC QR Card Pairing
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">
                    สร้าง QR Code สำหรับจับคู่บัตร NFC กับผู้ใช้
                </p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.nfc-cards.index') }}"
                   class="px-6 py-3 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 border border-gray-200 dark:border-gray-700">
                    <i class="fas fa-arrow-left mr-2"></i>
                    ย้อนกลับ
                </a>
            </div>
        </div>
    </div>

    {{-- Info Alert --}}
    <div class="mb-8 p-6 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-xl">
        <div class="flex items-start gap-4">
            <i class="fas fa-info-circle text-blue-600 dark:text-blue-400 text-xl mt-1"></i>
            <div>
                <h3 class="font-semibold text-blue-900 dark:text-blue-100 mb-2">วิธีการใช้</h3>
                <ul class="text-sm text-blue-800 dark:text-blue-200 space-y-1">
                    <li><i class="fas fa-check mr-2"></i>เลือกบัตร NFC ที่ต้องการจับคู่</li>
                    <li><i class="fas fa-check mr-2"></i>คลิกปุ่ม "สร้าง QR Code" เพื่อสร้างโทเค็นจับคู่</li>
                    <li><i class="fas fa-check mr-2"></i>ให้ผู้ใช้สแกน QR Code ด้วยแอป NFCMasterPro</li>
                    <li><i class="fas fa-check mr-2"></i>โทเค็นจะหมดอายุใน 15 นาที</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Cards Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" x-show="!loading">
        <template x-for="card in cards" :key="card.id">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-200 dark:border-gray-700">
                {{-- Card Header --}}
                <div class="bg-gradient-to-r from-purple-500 to-pink-500 dark:from-purple-600 dark:to-pink-600 p-6 text-white">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-white/20 rounded-xl">
                            <i class="fas fa-credit-card text-2xl"></i>
                        </div>
                        <span class="px-3 py-1 bg-white/20 rounded-full text-sm font-semibold"
                              :class="card.status === 'active' ? 'bg-green-400/30 text-green-100' : 'bg-yellow-400/30 text-yellow-100'">
                            <span x-text="card.status === 'active' ? 'เปิดใช้งาน' : 'รอดำเนิน'"></span>
                        </span>
                    </div>
                    <h3 class="text-lg font-bold" x-text="card.card_name || 'Unnamed Card'"></h3>
                    <p class="text-purple-100 text-sm mt-2" x-text="card.card_number_masked"></p>
                </div>

                {{-- Card Body --}}
                <div class="p-6">
                    {{-- Card Type --}}
                    <div class="mb-4">
                        <p class="text-gray-600 dark:text-gray-400 text-sm">ประเภทบัตร</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white" x-text="card.card_type_label || card.card_type"></p>
                    </div>

                    {{-- Balance --}}
                    <div class="mb-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <p class="text-gray-600 dark:text-gray-400 text-sm mb-1">ยอดคงเหลือ</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            <span x-text="parseFloat(card.balance).toLocaleString('th-TH', {minimumFractionDigits: 2})"></span>
                            <span class="text-sm text-gray-600 dark:text-gray-400 ml-1">THB</span>
                        </p>
                    </div>

                    {{-- QR Code Container (Hidden by default) --}}
                    <div class="mb-4" :class="activeQR === card.id ? 'block' : 'hidden'">
                        <div class="bg-white p-4 rounded-lg border border-gray-200 dark:border-gray-600 mb-4">
                            <div class="flex justify-center items-center"
                                 :id="'qrcode-' + card.id"
                                 class="min-h-64">
                                <div class="text-center text-gray-400">
                                    <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                                    <p class="text-sm">กำลังสร้าง QR Code...</p>
                                </div>
                            </div>
                        </div>

                        {{-- Token Info --}}
                        <div class="bg-blue-50 dark:bg-blue-900/30 p-4 rounded-lg border border-blue-200 dark:border-blue-800 mb-4">
                            <div class="text-sm space-y-2">
                                <div>
                                    <p class="text-gray-600 dark:text-gray-400 text-xs">โทเค็นจับคู่</p>
                                    <p class="font-mono text-xs text-gray-900 dark:text-white break-all" x-text="tokenData[card.id]?.pairing_token || '---'" @click="copyToClipboard($event)" class="cursor-pointer hover:bg-blue-100 dark:hover:bg-blue-800 p-2 rounded"></p>
                                </div>
                                <div class="flex justify-between items-center">
                                    <p class="text-gray-600 dark:text-gray-400 text-xs">หมดอายุใน</p>
                                    <p class="font-bold text-lg" :class="getTimeRemaining(card.id) < 60 ? 'text-red-600' : 'text-green-600'">
                                        <span x-text="getTimeRemaining(card.id)"></span> วินาที
                                    </p>
                                </div>
                            </div>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="flex gap-2">
                            <button @click="downloadQR(card.id)"
                                    class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors duration-200 text-sm font-semibold">
                                <i class="fas fa-download mr-2"></i>
                                ดาวน์โหลด QR Code
                            </button>
                            <button @click="closeQR(card.id)"
                                    class="flex-1 px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-900 dark:text-white rounded-lg transition-colors duration-200 text-sm font-semibold">
                                <i class="fas fa-times mr-2"></i>
                                ปิด
                            </button>
                        </div>
                    </div>

                    {{-- Generate QR Button --}}
                    <button @click="generateQR(card)"
                            :disabled="activeQR === card.id || generatingQR === card.id"
                            :class="activeQR === card.id || generatingQR === card.id ? 'opacity-50 cursor-not-allowed' : 'hover:from-purple-700 hover:to-pink-700'"
                            class="w-full px-4 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg transition-all duration-200 font-semibold">
                        <span x-show="generatingQR !== card.id">
                            <i class="fas fa-qrcode mr-2"></i>
                            สร้าง QR Code
                        </span>
                        <span x-show="generatingQR === card.id">
                            <i class="fas fa-spinner fa-spin mr-2"></i>
                            กำลังสร้าง...
                        </span>
                    </button>
                </div>
            </div>
        </template>

        {{-- Empty State --}}
        <div class="col-span-full" x-show="cards.length === 0 && !loading">
            <div class="text-center py-12 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                <i class="fas fa-inbox text-5xl text-gray-300 dark:text-gray-600 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">ไม่มีบัตรที่ยังไม่ได้จับคู่</h3>
                <p class="text-gray-600 dark:text-gray-400">
                    บัตรทั้งหมดได้ถูกจับคู่แล้ว หรือไม่มีบัตรที่เปิดใช้งาน
                </p>
                <a href="{{ route('admin.nfc-cards.create') }}" class="mt-6 inline-block px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors duration-200 font-semibold">
                    <i class="fas fa-plus mr-2"></i>
                    สร้างบัตรใหม่
                </a>
            </div>
        </div>
    </div>

    {{-- Loading State --}}
    <div class="col-span-full" x-show="loading">
        <div class="text-center py-12 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
            <i class="fas fa-spinner fa-spin text-4xl text-gray-400 mb-4"></i>
            <p class="text-gray-600 dark:text-gray-400">กำลังโหลดบัตร...</p>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script>
function nfcQRPairing() {
    return {
        cards: [],
        loading: true,
        activeQR: null,
        generatingQR: null,
        tokenData: {},
        expiryTimes: {},

        async init() {
            await this.loadAvailableCards();
            // Update countdown every second
            setInterval(() => this.updateCountdowns(), 1000);
        },

        async loadAvailableCards() {
            try {
                this.loading = true;
                const response = await fetch('{{ route("api.v1.nfc.pairing.available-cards") }}', {
                    headers: {
                        'Authorization': 'Bearer ' + document.querySelector('meta[name="api-token"]')?.content || '',
                        'Accept': 'application/json',
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.cards = data.data;
                } else {
                    console.error('Error loading cards:', data.error);
                    this.showToast('خطأ في تحميل البطاقات', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                this.showToast('حدث خطأ أثناء تحميل البطاقات', 'error');
            } finally {
                this.loading = false;
            }
        },

        async generateQR(card) {
            this.generatingQR = card.id;

            try {
                const response = await fetch('{{ route("api.v1.nfc.pairing.generate") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + document.querySelector('meta[name="api-token"]')?.content || '',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({
                        card_number: card.card_number_masked.replace(/\*/g, '').substring(0, 8)
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.tokenData[card.id] = data.data;
                    this.expiryTimes[card.id] = 15 * 60; // 15 minutes in seconds
                    this.activeQR = card.id;

                    // Wait for DOM to render
                    await this.$nextTick();
                    this.renderQRCode(card.id, data.data.qr_payload);
                    this.showToast('تم إنشاء رمز QR بنجاح', 'success');
                } else {
                    this.showToast(data.error || 'خطأ في إنشاء رمز QR', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                this.showToast('حدث خطأ أثناء إنشاء رمز QR', 'error');
            } finally {
                this.generatingQR = null;
            }
        },

        renderQRCode(cardId, payload) {
            const container = document.getElementById(`qrcode-${cardId}`);
            if (!container) return;

            // Clear previous QR code
            container.innerHTML = '';

            // Generate new QR code
            new QRCode(container, {
                text: payload,
                width: 250,
                height: 250,
                colorDark: '#000000',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.H
            });
        },

        getTimeRemaining(cardId) {
            const remaining = this.expiryTimes[cardId] || 0;
            return Math.max(0, remaining);
        },

        updateCountdowns() {
            Object.keys(this.expiryTimes).forEach(cardId => {
                this.expiryTimes[cardId]--;

                if (this.expiryTimes[cardId] <= 0) {
                    this.closeQR(parseInt(cardId));
                    this.showToast('انتهت صلاحية رمز QR', 'warning');
                }
            });
        },

        closeQR(cardId) {
            this.activeQR = null;
            delete this.tokenData[cardId];
            delete this.expiryTimes[cardId];
        },

        downloadQR(cardId) {
            const canvas = document.querySelector(`#qrcode-${cardId} canvas`);
            if (!canvas) {
                this.showToast('رمز QR غير متاح', 'error');
                return;
            }

            const link = document.createElement('a');
            link.href = canvas.toDataURL('image/png');
            link.download = `nfc-pairing-${this.tokenData[cardId]?.pairing_token.substring(0, 8)}.png`;
            link.click();

            this.showToast('تم تنزيل رمز QR', 'success');
        },

        copyToClipboard(event) {
            const text = event.target.textContent;
            navigator.clipboard.writeText(text).then(() => {
                this.showToast('تم نسخ الرمز', 'success');
            });
        },

        showToast(message, type = 'info') {
            // Simple toast notification - implement based on your UI framework
            console.log(`[${type}] ${message}`);
            alert(message);
        }
    };
}
</script>

<style>
    [x-cloak] {
        display: none !important;
    }
</style>
@endsection
