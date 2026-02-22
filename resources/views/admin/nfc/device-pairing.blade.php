{{--
    NFC Device Pairing — App-Backend Connection

    หน้าสร้าง QR Code สำหรับจับคู่แอพ NFCMasterPro กับระบบหลังบ้าน
    - สร้าง QR ที่แอพสแกนเพื่อตั้งค่า API อัตโนมัติ
    - แสดง QR Code พร้อมนับถอยหลัง (60 นาที)
    - แสดงรายการอุปกรณ์ที่จับคู่แล้ว

    @version 1.0.0
--}}

@extends('layouts.admin')

@section('title', 'NFC Device Pairing')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="devicePairing()">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">🔗 จับคู่แอพ NFC</h1>
            <p class="text-gray-500 mt-1">สร้าง QR Code เพื่อเชื่อมต่อแอพ NFCMasterPro กับระบบ</p>
        </div>
        <a href="{{ route('admin.nfc.index') }}" class="text-blue-600 hover:text-blue-800">
            ← กลับหน้า NFC
        </a>
    </div>

    {{-- Generate QR Section --}}
    <div class="bg-white rounded-xl shadow-sm border p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">สร้าง QR จับคู่แอพ</h2>

        {{-- QR Display --}}
        <div x-show="!qrPayload" class="text-center py-8">
            <div class="w-48 h-48 mx-auto bg-gray-100 rounded-xl flex items-center justify-center mb-4">
                <span class="text-6xl text-gray-300">📱</span>
            </div>
            <p class="text-gray-500 mb-4">กดปุ่มด้านล่างเพื่อสร้าง QR Code สำหรับจับคู่แอพ</p>
            <button @click="generateQR()"
                class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-medium"
                :disabled="loading"
                :class="{ 'opacity-50 cursor-not-allowed': loading }">
                <span x-show="!loading">🔗 สร้าง QR จับคู่</span>
                <span x-show="loading">⏳ กำลังสร้าง...</span>
            </button>
        </div>

        <div x-show="qrPayload" class="text-center py-4">
            {{-- QR Code --}}
            <div class="inline-block bg-white p-4 rounded-xl border-2 border-indigo-200 shadow-lg mb-4">
                <div id="qrcode" class="w-64 h-64"></div>
            </div>

            {{-- Countdown --}}
            <div class="mb-4">
                <p class="text-sm text-gray-500">QR หมดอายุใน</p>
                <p class="text-2xl font-mono font-bold"
                   :class="countdown <= 300 ? 'text-red-600' : 'text-indigo-600'"
                   x-text="formatCountdown()"></p>
            </div>

            {{-- Instructions --}}
            <div class="bg-indigo-50 rounded-lg p-4 text-left max-w-md mx-auto mb-4">
                <h3 class="font-semibold text-indigo-800 mb-2">📱 วิธีใช้</h3>
                <ol class="text-sm text-indigo-700 space-y-1 list-decimal list-inside">
                    <li>เปิดแอพ NFCMasterPro</li>
                    <li>ไปที่ Dashboard → กดปุ่ม "จับคู่"</li>
                    <li>สแกน QR Code นี้</li>
                    <li>แอพจะตั้งค่า API อัตโนมัติ</li>
                </ol>
            </div>

            {{-- Manual Code --}}
            <div class="bg-gray-50 rounded-lg p-4 text-left max-w-md mx-auto mb-4">
                <h3 class="font-semibold text-gray-700 mb-2">⌨️ หรือป้อนรหัสเชื่อมต่อในแอพ</h3>
                <div class="bg-white border rounded-md p-3 font-mono text-xs text-gray-600 break-all max-h-32 overflow-y-auto"
                     x-text="qrPayload"></div>
                <button @click="copyPayload()"
                    class="mt-2 px-4 py-2 text-sm bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition">
                    📋 คัดลอก
                </button>
            </div>

            {{-- Regenerate --}}
            <button @click="generateQR()"
                class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-medium"
                :disabled="loading">
                🔄 สร้าง QR ใหม่
            </button>
        </div>
    </div>

    {{-- Info Section --}}
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
        <h3 class="font-semibold text-amber-800 mb-2">ℹ️ เกี่ยวกับการจับคู่แอพ</h3>
        <ul class="text-sm text-amber-700 space-y-1">
            <li>• QR Code มีอายุ 60 นาที หลังหมดอายุต้องสร้างใหม่</li>
            <li>• QR ประกอบด้วย API URL, API Key, และ Device Token</li>
            <li>• เมื่อแอพสแกนจะตั้งค่าเชื่อมต่อ API อัตโนมัติ ไม่ต้องกรอกเอง</li>
            <li>• สามารถยกเลิกการจับคู่ได้จากแอพ (Settings → Unpair)</li>
        </ul>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
function devicePairing() {
    return {
        loading: false,
        qrPayload: null,
        countdown: 0,
        countdownInterval: null,

        async generateQR() {
            this.loading = true;
            try {
                const response = await fetch('/api/v1/nfc/devices/generate-qr', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                });

                const data = await response.json();

                if (data.success) {
                    this.qrPayload = data.data.qr_payload;
                    this.countdown = data.data.expires_in_minutes * 60;

                    // Generate QR code
                    this.$nextTick(() => {
                        const el = document.getElementById('qrcode');
                        if (el) {
                            el.innerHTML = '';
                            new QRCode(el, {
                                text: this.qrPayload,
                                width: 256,
                                height: 256,
                                correctLevel: QRCode.CorrectLevel.M,
                            });
                        }
                    });

                    // Start countdown
                    this.startCountdown();
                } else {
                    alert('Error: ' + (data.error || 'Failed to generate QR'));
                }
            } catch (error) {
                alert('Connection error: ' + error.message);
            }
            this.loading = false;
        },

        startCountdown() {
            if (this.countdownInterval) clearInterval(this.countdownInterval);
            this.countdownInterval = setInterval(() => {
                this.countdown--;
                if (this.countdown <= 0) {
                    clearInterval(this.countdownInterval);
                    this.qrPayload = null;
                }
            }, 1000);
        },

        formatCountdown() {
            const min = Math.floor(this.countdown / 60);
            const sec = this.countdown % 60;
            return `${String(min).padStart(2, '0')}:${String(sec).padStart(2, '0')}`;
        },

        copyPayload() {
            if (this.qrPayload) {
                navigator.clipboard.writeText(this.qrPayload);
                alert('คัดลอกแล้ว!');
            }
        },

        destroy() {
            if (this.countdownInterval) clearInterval(this.countdownInterval);
        }
    };
}
</script>
@endpush
