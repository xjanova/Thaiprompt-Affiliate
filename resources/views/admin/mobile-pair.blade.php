@extends('layouts.admin')

@section('title', 'เชื่อมต่อ Admin Mobile App')

@push('styles')
<style>
    .pair-glow {
        box-shadow: 0 0 60px -10px rgba(168, 85, 247, 0.45),
                    0 0 30px -10px rgba(236, 72, 153, 0.35);
    }
    .pair-code-box {
        font-family: 'Plus Jakarta Sans', 'Noto Sans Thai', monospace;
        letter-spacing: 0.5em;
    }
</style>
@endpush

@section('content')
<div class="container mx-auto px-4 py-8" x-data="adminMobilePair()" x-init="init()">

    {{-- Header --}}
    <div class="mb-8 text-center">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            🔗 เชื่อมต่อ Admin Mobile App
        </h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">
            สแกน QR code นี้ด้วย Thaiprompt Admin App บนมือถือเพื่อจับคู่อุปกรณ์
        </p>
    </div>

    {{-- Card --}}
    <div class="max-w-md mx-auto">
        <div class="bg-gradient-to-br from-purple-600 via-pink-600 to-orange-500 p-1 rounded-3xl pair-glow">
            <div class="bg-white dark:bg-gray-900 rounded-3xl p-8">

                {{-- State: Loading --}}
                <template x-if="loading">
                    <div class="flex flex-col items-center justify-center py-16">
                        <div class="animate-spin h-12 w-12 border-4 border-purple-500 border-t-transparent rounded-full"></div>
                        <p class="mt-4 text-gray-600 dark:text-gray-300">กำลังสร้างรหัสจับคู่...</p>
                    </div>
                </template>

                {{-- State: Pending (QR shown, waiting for app to claim) --}}
                <template x-if="!loading && status === 'pending' && qrPayload">
                    <div class="flex flex-col items-center">
                        {{-- QR --}}
                        <div class="bg-white p-4 rounded-2xl shadow-inner border border-gray-200">
                            <div id="qrcode"></div>
                        </div>

                        {{-- Pair code (manual entry fallback) --}}
                        <div class="mt-6 text-center">
                            <p class="text-xs uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-2">
                                หรือกรอกรหัสด้วยมือ
                            </p>
                            <div class="pair-code-box text-3xl font-extrabold text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/20 px-6 py-3 rounded-xl">
                                <span x-text="pairCode"></span>
                            </div>
                        </div>

                        {{-- Countdown + status --}}
                        <div class="mt-6 flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                            <span class="inline-block w-2 h-2 rounded-full bg-yellow-500 animate-pulse"></span>
                            <span>กำลังรอแอปจับคู่ ·</span>
                            <span>หมดอายุใน <span x-text="countdown"></span> วินาที</span>
                        </div>

                        {{-- Admin email --}}
                        <div class="mt-2 text-xs text-gray-400">
                            จะจับคู่กับ: <strong x-text="adminEmail"></strong>
                        </div>

                        @if(auth()->user()?->twoFactorSettings?->enabled)
                            <div class="mt-3 px-3 py-2 bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300 rounded-lg text-xs">
                                ⚠️ ต้องใส่รหัส 2FA ของคุณในแอปก่อนจับคู่สำเร็จ
                            </div>
                        @endif

                        {{-- Cancel button --}}
                        <button @click="cancel()" class="mt-6 px-6 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-red-600 transition">
                            ยกเลิก
                        </button>
                    </div>
                </template>

                {{-- State: Claimed (app paired successfully) --}}
                <template x-if="status === 'claimed'">
                    <div class="flex flex-col items-center py-12 text-center">
                        <div class="w-20 h-20 rounded-full bg-gradient-to-br from-green-400 to-green-600 grid place-items-center text-white text-4xl mb-4">
                            ✓
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">จับคู่สำเร็จ!</h2>
                        <p class="mt-2 text-gray-600 dark:text-gray-400">
                            อุปกรณ์: <strong x-text="claimedDevice"></strong>
                        </p>
                        <button @click="generate()" class="mt-6 px-6 py-2 bg-purple-600 text-white rounded-xl hover:bg-purple-700">
                            จับคู่อุปกรณ์อื่น
                        </button>
                    </div>
                </template>

                {{-- State: Expired --}}
                <template x-if="status === 'expired'">
                    <div class="flex flex-col items-center py-12 text-center">
                        <div class="text-5xl mb-4">⏱️</div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">รหัสหมดอายุ</h2>
                        <p class="mt-2 text-gray-600 dark:text-gray-400 text-sm">
                            กรุณาสร้างรหัสใหม่
                        </p>
                        <button @click="generate()" class="mt-6 px-6 py-2 bg-purple-600 text-white rounded-xl hover:bg-purple-700">
                            สร้างรหัสใหม่
                        </button>
                    </div>
                </template>

                {{-- Error --}}
                <template x-if="error">
                    <div class="text-center py-8">
                        <div class="text-red-500 mb-2">❌ <span x-text="error"></span></div>
                        <button @click="generate()" class="mt-4 text-purple-600 hover:underline text-sm">
                            ลองใหม่
                        </button>
                    </div>
                </template>

            </div>
        </div>

        {{-- Help text --}}
        <div class="mt-8 text-center text-sm text-gray-500 dark:text-gray-400 space-y-2">
            <p><strong>ขั้นตอน:</strong></p>
            <ol class="text-left list-decimal list-inside max-w-sm mx-auto space-y-1">
                <li>เปิด Thaiprompt Admin App บนมือถือ</li>
                <li>เลือก "สแกน QR เพื่อเข้าสู่ระบบ"</li>
                <li>ส่องกล้องไปที่ QR ด้านบน</li>
                @if(auth()->user()?->twoFactorSettings?->enabled)
                    <li>ใส่รหัส 2FA จาก authenticator app</li>
                @endif
                <li>เสร็จสิ้น พร้อมใช้งาน 🎉</li>
            </ol>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
<script>
    function adminMobilePair() {
        return {
            loading: false,
            error: null,
            pairCode: '',
            qrPayload: '',
            status: 'idle', // idle | pending | claimed | expired
            adminEmail: '{{ auth()->user()?->email }}',
            claimedDevice: '',
            expiresIn: 300,
            countdown: 300,
            pollTimer: null,
            countdownTimer: null,

            async init() {
                await this.generate();
            },

            async generate() {
                this.cleanup();
                this.loading = true;
                this.error = null;
                this.status = 'idle';

                try {
                    const res = await fetch('{{ route('admin.mobile-pair.init') }}', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        },
                        credentials: 'same-origin',
                    });
                    const json = await res.json();
                    if (!json.success) throw new Error(json.message || 'สร้างรหัสไม่สำเร็จ');

                    this.pairCode = json.data.pair_code;
                    this.qrPayload = json.data.qr_payload;
                    this.expiresIn = json.data.expires_in;
                    this.countdown = this.expiresIn;
                    this.status = 'pending';

                    this.$nextTick(() => this.renderQr(this.qrPayload));
                    this.startPolling();
                    this.startCountdown();
                } catch (e) {
                    this.error = e.message;
                } finally {
                    this.loading = false;
                }
            },

            renderQr(payload) {
                const el = document.getElementById('qrcode');
                if (!el) return;
                el.innerHTML = '';
                const qr = qrcode(0, 'M');
                qr.addData(payload);
                qr.make();
                el.innerHTML = qr.createImgTag(6, 8);
            },

            startPolling() {
                this.pollTimer = setInterval(async () => {
                    if (this.status !== 'pending') return;
                    try {
                        const url = '{{ route('admin.mobile-pair.status') }}?pair_code=' + encodeURIComponent(this.pairCode);
                        const res = await fetch(url, { credentials: 'same-origin' });
                        const json = await res.json();
                        if (json.success && json.data) {
                            if (json.data.status === 'claimed') {
                                this.status = 'claimed';
                                this.claimedDevice = json.data.device_name || '';
                                this.cleanup();
                            } else if (json.data.status === 'expired') {
                                this.status = 'expired';
                                this.cleanup();
                            }
                        }
                    } catch (e) {
                        // ignore network blip
                    }
                }, 2000);
            },

            startCountdown() {
                this.countdownTimer = setInterval(() => {
                    this.countdown--;
                    if (this.countdown <= 0) {
                        this.status = 'expired';
                        this.cleanup();
                    }
                }, 1000);
            },

            async cancel() {
                if (!this.pairCode) return;
                try {
                    await fetch('{{ route('admin.mobile-pair.cancel') }}', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ pair_code: this.pairCode }),
                    });
                } catch (e) { /* ignore */ }
                this.status = 'expired';
                this.cleanup();
            },

            cleanup() {
                if (this.pollTimer) { clearInterval(this.pollTimer); this.pollTimer = null; }
                if (this.countdownTimer) { clearInterval(this.countdownTimer); this.countdownTimer = null; }
            },
        };
    }
</script>
@endpush
@endsection
