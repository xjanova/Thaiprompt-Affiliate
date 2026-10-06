@extends('layouts.admin-v3')

@section('title', 'เชื่อมต่อ Admin Mobile App')

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
        <div class="bg-gradient-to-br from-purple-600 via-pink-600 to-orange-500 p-1 rounded-3xl shadow-2xl shadow-purple-500/40">
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
                            <div class="font-mono tracking-[0.5em] text-3xl font-extrabold text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/20 px-6 py-3 rounded-xl">
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

    {{-- เครื่องที่จับคู่แล้ว (ทุกแอดมิน) --}}
    <div class="max-w-3xl mx-auto mt-12" x-data="adminPairedDevices()" x-init="load()"
         @admin-pair-claimed.window="load()">
        <div class="flex items-center justify-between gap-3 mb-4">
            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">📱 เครื่องที่จับคู่แล้ว</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400" x-show="!loading && !error">
                    <span x-text="total"></span> เครื่อง · แอดมิน <span x-text="admins"></span> คน
                </p>
            </div>
            <button @click="load()" :disabled="loading"
                    class="px-4 py-2 text-sm rounded-xl bg-white/70 dark:bg-gray-800/70 backdrop-blur border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 hover:bg-white dark:hover:bg-gray-800 disabled:opacity-50 transition">
                <i class="fas fa-sync-alt" :class="loading && 'animate-spin'"></i> รีเฟรช
            </button>
        </div>

        <template x-if="error">
            <div class="p-4 rounded-2xl bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 text-sm" x-text="error"></div>
        </template>

        <template x-if="!loading && !error && devices.length === 0">
            <div class="p-8 rounded-2xl bg-white/70 dark:bg-gray-800/70 backdrop-blur border border-gray-200 dark:border-gray-700 text-center text-gray-500 dark:text-gray-400">
                ยังไม่มีเครื่องที่จับคู่แอปแอดมิน
            </div>
        </template>

        <div class="space-y-3">
            <template x-for="d in devices" :key="d.id">
                <div class="flex flex-col sm:flex-row sm:items-center gap-3 p-4 rounded-2xl bg-white/80 dark:bg-gray-800/80 backdrop-blur border border-gray-200 dark:border-gray-700 shadow-sm">
                    <div class="w-11 h-11 shrink-0 rounded-xl grid place-items-center text-white bg-gradient-to-br from-purple-500 to-pink-500">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-semibold text-gray-900 dark:text-white truncate" x-text="d.device_name"></span>
                            <span x-show="d.is_me" class="px-2 py-0.5 text-xs rounded-full bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300">เครื่องของฉัน</span>
                            <span class="px-2 py-0.5 text-xs rounded-full"
                                  :class="d.push_enabled ? 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300' : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400'"
                                  x-text="d.push_enabled ? '🔔 รับแจ้งเตือน' : '🔕 ยังไม่รับแจ้งเตือน'"></span>
                        </div>
                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400 flex flex-wrap gap-x-3 gap-y-1">
                            <span>👤 <span x-text="d.admin.name"></span></span>
                            <span x-text="d.method === 'qr' ? 'จับคู่ด้วย QR' : 'ล็อกอินด้วยรหัสผ่าน'"></span>
                            <span>ใช้ล่าสุด <span x-text="formatTime(d.last_used_at)"></span></span>
                            <span>จับคู่เมื่อ <span x-text="formatTime(d.paired_at)"></span></span>
                        </div>
                    </div>
                    <button x-show="d.can_revoke" @click="revoke(d)" :disabled="busyId === d.id"
                            class="w-full sm:w-auto px-4 py-2 text-sm rounded-xl border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 disabled:opacity-50 transition">
                        <span x-text="busyId === d.id ? 'กำลังถอด...' : 'ถอดเครื่อง'"></span>
                    </button>
                </div>
            </template>
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
                                window.dispatchEvent(new CustomEvent('admin-pair-claimed'));
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

    // รายการเครื่องที่จับคู่แล้ว + ถอดเครื่อง
    function adminPairedDevices() {
        return {
            loading: false,
            error: null,
            devices: [],
            total: 0,
            admins: 0,
            busyId: null,

            async load() {
                this.loading = true;
                this.error = null;
                try {
                    const res = await fetch('{{ route('admin.mobile-pair.devices') }}', {
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin',
                    });
                    const json = await res.json();
                    if (!res.ok || !json.success) throw new Error(json.message || 'โหลดรายการเครื่องไม่สำเร็จ');
                    this.devices = json.data.devices;
                    this.total = json.data.total;
                    this.admins = json.data.admins;
                } catch (e) {
                    this.error = e.message;
                } finally {
                    this.loading = false;
                }
            },

            async revoke(device) {
                const who = device.is_me ? 'ของคุณ' : 'ของ ' + device.admin.name;
                if (!confirm('ถอดเครื่อง "' + device.device_name + '" ' + who + '?\nแอปบนเครื่องนั้นจะออกจากระบบทันที')) return;
                this.busyId = device.id;
                try {
                    const url = '{{ route('admin.mobile-pair.devices.revoke', ['tokenId' => 0]) }}'.replace(/\/0\/revoke$/, '/' + device.id + '/revoke');
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        },
                        credentials: 'same-origin',
                    });
                    const json = await res.json();
                    if (!res.ok || !json.success) throw new Error(json.message || 'ถอดเครื่องไม่สำเร็จ');
                    await this.load();
                } catch (e) {
                    alert(e.message);
                } finally {
                    this.busyId = null;
                }
            },

            formatTime(iso) {
                if (!iso) return 'ยังไม่เคยใช้';
                return new Date(iso).toLocaleString('th-TH', { dateStyle: 'medium', timeStyle: 'short' });
            },
        };
    }
</script>
@endpush
@endsection
